<?php

    /*
     * LQI collector to draw nodes table & network graph
     * Called once a day by cron, or on user request from "Network" pages.
     *
     * Starting from zigate (coordinator), send LQI request to get neighbour table
     * - Send request thru AbeilleCmd (004E cmd)
     * - Get response from AbeilleParser (Mgmt_lqi_rsp cmd)
     * - Identify each neighbour
     * - If neighbor is router, added to list for interrogation
     */

    include_once("../../core/config/Abeille.config.php");

    /* Developers debug features */
    if (file_exists(dbgFile)) {
        // include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__."/../../../../core/php/core.inc.php";
    include_once "AbeilleLog.php"; // Log library
    include_once __DIR__.'/../class/AbeilleTools.class.php'; // getMsgSendErr()
    define('maxRetry', 3);

    /* Add to list a new eq (router or coordinator) to interrogate.
       Check first is not aleady in the list. */
    function newRouter($logicId) {

        /* Checking if not already in the list */
        global $eqToInterrogate;
        foreach ($eqToInterrogate as $eq) {
            if ($eq['logicId'] == $logicId)
                return; // Already there
        }

        global $knownFromJeedom;
        if (isset($knownFromJeedom[$logicId]))
            $eqName = $knownFromJeedom[$logicId]['name'];
        else
            $eqName = "Inconnu";

        // // Checking if router is ready to receive 'Mgmt_lqi_req'
        // // Note: This unfortunately happens because some router return bad informations
        // if (isset($knownFromJeedom[$logicId]['zigbee']['rxOnWhenIdle'])) {
        //     $rxOn = $knownFromJeedom[$logicId]['zigbee']['rxOnWhenIdle'];
        //     if ($rxOn === 0) {
        //         logMessage("", "  New router but RX OFF: '${eqName}' (${logicId}) => Ignored as router");
        //         return;
        //     }
        // }

        list($net, $addr) = explode('/', $logicId);
        $eqToInterrogate[] = array(
            "logicId" => $logicId,
            "name" => $eqName,
            "addr" => $addr,
            "tableEntries" => 0,    // Nb of entries in its table
            "tableIndex" => 0,      // Index to interrogate
            "retry" => maxRetry,
            "completed" => false
        );
        logMessage("", "  New router: '${eqName}' (${logicId})");
    }

    /* Remove any pending messages from parser */
    function msgFromParserFlush() {
        global $queueParserToLQI;

        while (msg_receive($queueParserToLQI, 0, $msgType, 2048, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR));
    }

    /* Treat request responses (Mgmt_lqi_rsp) from parser.
       Returns: 0=OK, -1=fatal error, 1=timeout */
    function msgFromParser($eqIdx) {
        // logMessage("", "  msgFromParser(eqIndex=".$eqIdx.")");

        global $queueParserToLQI, $queueParserToLQIMax;
        global $eqToInterrogate;
        global $knownFromJeedom;

        $timeoutVal = 10; // 10sec (useful when there is unknown eq interrogation during LQI collect)
        $timeout = false;
        for ($t = 0; $t < $timeoutVal; ) {
            // logMessage("", "  Queue stat=".json_encode(msg_stat_queue($queueParserToLQI)));
            $msgMax = $queueParserToLQIMax;
            if (msg_receive($queueParserToLQI, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == true) {
                /* Message received. Let's check it is the expected one */
                $msg = json_decode($msgJson);
                if ($msg->type != "mgmtLqiRsp") {
                    logMessage("", "  msgFromParser: Unsupported message type (".$msg->type.") => Ignored.");
                    continue;
                }
                if (hexdec($msg->startIdx) != $eqToInterrogate[$eqIdx]['tableIndex']) {
                    /* Note: this case is due to too many identical 004E messages sent to eq
                       leading to several identical mgmtLqiRsp answers */
                    logMessage("", "  msgFromParser: Unexpected start index (".$msg->startIdx.") => Ignored.");
                    continue;
                }
                if ($msg->srcAddr != $eqToInterrogate[$eqIdx]['addr']) {
                    logMessage("", "  msgFromParser: Unexpected source addr (".$msg->srcAddr.") => Ignored.");
                    continue;
                }
                if ($msg->status == "C1") {
                    logMessage("", "  msgFromParser: No devices.");
                    break;
                }
                if ($msg->status != "00") {
                    logMessage("", "  msgFromParser: Wrong message status (".$msg->status.") => Ignored.");
                    continue;
                }
                break; // Valid message
            }

            if ($errCode == 42) { // No message
                sleep(1); // Sleep 1s
                $t += 1;
                continue;
            }
            if ($errCode == 7) { // Message too big
                msg_receive($queueParserToLQI, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                logMessage("", "  msgFromParser: TOO BIG msg => ignored");
                continue;
            }

            /* It's an error */
            logMessage("", "  msgFromParser: msg_receive() ERROR: ".$errCode."/".posix_strerror($errCode));
            return -1;
        }
        if ($t >= $timeoutVal) {
            logMessage("", "  msgFromParser: Time-out !");
            // return 1;
            $timeout = true;
        }
        // Note: What to do if mesg received does not match interrogated eq ?
        //       This currently can't appear since requests are done sequentially
        //       so no risk to get an answer from an unexpected source.

        /* Message format reminder
            $msg = array(
                'type' => 'mgmtLqiRsp',
                'srcAddr' => $srcAddr,
                'tableEntries' => $nTableEntries,
                'tableListCount' => $nTableListCount,
                'startIdx' => $startIdx,
                'nList' => $nList
                    $N = array(
                        "extPANId"
                        "extAddr"
                        "addr"
                        "devType"  => $bitMap1 & 0x3,
                        "rxOnWhenIdle"  => ($bitMap1 >> 2) & 0x3,
                        "relationship"  => ($bitMap1 >> 4) & 0x7,
                        "depth"
                        "lqi"
                )
            ); */

        if ($timeout == false) {
            logMessage("", "  msgFromParser: ".json_encode($msg, JSON_UNESCAPED_SLASHES));
            $tableEntries = $msg->tableEntries; // Total entries on interrogated eq
            $tableListCount = $msg->tableListCount; // Number of neighbours listed in msg
            $startIdx = $msg->startIdx;
            $nList = $msg->nList; // List of neighbours
            // logMessage("", "  TableEntries=".$tableEntries.", TableListCount=".$tableListCount.", StartIdx=".$startIdx);

            /* Updating collect infos for this coordinator/router */
            $eqToInterrogate[$eqIdx]['tableEntries'] = hexdec($tableEntries);
            $eqToInterrogate[$eqIdx]['tableIndex'] = hexdec($startIdx) + hexdec($tableListCount);
        }

        $NE = $eqToInterrogate[$eqIdx]["logicId"]; // Ex: 'Abeille1/A3B4'
        list($netName, $addr) = explode('/', $NE);

        //
        // New format (AbeilleLQI-AbeilleX.json)
        //
        if (isset($knownFromJeedom[$NE])) {
            $routerName = $knownFromJeedom[$NE]['name'];
            $routerPName = $knownFromJeedom[$NE]['parent'];
            $routerIeee = $knownFromJeedom[$NE]['ieee'];
            $routerIcon = $knownFromJeedom[$NE]['icon'];
        } else {
            $routerIeee = '';
            $routerName = '?';
            $routerPName = '?';
            $routerIcon = 'defaultUnknown';
        }
        global $lqiTable;
        if (isset($lqiTable['routers'][$NE])) {
            $router = $lqiTable['routers'][$NE];
        } else {
            $router = array(
                'addr' => $addr,
                'ieee' => $routerIeee,
                'name' => $routerName,
                'parentName' => $routerPName,
                'type' => ($addr == "0000") ? 'Coordinator' : 'Router',
                'neighbors' => array(),
                'icon' => $routerIcon,
            );
        }
        if ($timeout == true) {
            // $router['dead'] = true;
            // logMessage("", "  ${NE} router is DEAD");
        } else {
            $neighbors = $router['neighbors'];
            /* Going thru neighbours list */
            for ($nIdx = 0; $nIdx < hexdec($tableListCount); $nIdx++) {
                $N = $nList[$nIdx];

                $nLogicId = $netName."/".$N->addr;
                if (isset($knownFromJeedom[$nLogicId])) {
                    $nName = $knownFromJeedom[$nLogicId]['name'];
                    $nParentName = $knownFromJeedom[$nLogicId]['parent'];
                    $nIcon = $knownFromJeedom[$nLogicId]['icon'];
                    $zigbee = $knownFromJeedom[$nLogicId]['zigbee'];
                } else {
                    $nName = "?";
                    $nParentName = "?";
                    $nIcon = 'defaultUnknown';
                    $zigbee = [];
                }

                $newNeighbor = array(
                    'addr' => $N->addr,
                    'ieee' => $N->extAddr,
                    'name' => $nName,
                    'parentName' => $nParentName,
                    'depth' => $N->depth,
                    'lqi' => hexdec($N->lqi),
                    'icon' => $nIcon,
                );

                // Note: device type from router are often bad. Therefore using info from node descriptor instead, if device is known to Jeedom
                if (isset($zigbee['logicalType'])) {
                    $attrType = $zigbee['logicalType']; // Node descriptor/logical type info
                    logMessage("", "  Using 'logicalType' for device type");
                } else
                    $attrType = $N->devType; // Mgmt_lqi_rsp info
                if ($attrType == 0) {
                    $newNeighbor['type'] = "Coordinator";
                } else if ($attrType == 1) {
                    $newNeighbor['type'] = "Router";

                    // Addition check: Router is ready to receive 'Mgmt_lqi_req' ?
                    // Note: This unfortunately happens because some router return bad informations on their childs
                    if (isset($zigbee['rxOnWhenIdle'])) {
                        $rxOn = $zigbee['rxOnWhenIdle'];
                        if ($rxOn === 0) {
                            logMessage("", "  Router but RX OFF: '${nName}' (${nLogicId}) => Ignored as router");
                            $newNeighbor['type'] = "End Device";
                        }
                    }

                    if ($newNeighbor['type'] == "Router")
                        newRouter($nLogicId);
                } else if ($attrType== 2) {
                    $newNeighbor['type'] = "End Device";
                } else { // other
                    $newNeighbor['type'] = "Unknown";
                }

                // $attrRx = ($bitMap >> 2) & 0x3;
                $attrRx = $N->rxOnWhenIdle;
                if ($attrRx == 0) {
                    $newNeighbor['rx'] = "Rx-Off";
                } else if ($attrRx == 1) {
                    $newNeighbor['rx'] = "Rx-On";
                } else { // 2 or 3
                    $newNeighbor['rx'] = "Rx-Unknown";
                }

                // $attrRel = ($bitMap >> 4) & 0x7;
                $attrRel = $N->relationship;
                if ($attrRel == 0) {
                    $newNeighbor['relationship'] = "Parent";
                } else if ($attrRel == 1) {
                    $newNeighbor['relationship'] = "Child";

                    // Required by remove from zigbee feature (#1770)
                    // Tcharp38: It appears that in several cases we don't have any parent IEEE
                    //   might not be required if remove is using 004C cmd instead of 0026
                    $kid = Abeille::byLogicalId($netName.'/'.$N->addr, 'Abeille');
                    if ($kid) { // Saving parent IEEE address
                        $kid->setConfiguration('parentIEEE', $routerIeee);
                        $kid->save();
                    } else
                        logMessage("", "  WARNING: Unkown device '".$netName."/".$N->addr."'");
                } else if ($attrRel == 2) {
                    $newNeighbor['relationship'] = "Sibling";
                } else if ($attrRel == 3) {
                    $newNeighbor['relationship'] = "None";
                } else if ($attrRel == 4) {
                    $newNeighbor['relationship'] = "Previous";
                } else {
                    $newNeighbor['relationship'] = "Unknown";
                }

                $neighbors[$nLogicId] = $newNeighbor;
            }
            $router['neighbors'] = $neighbors;
        }
        $lqiTable['routers'][$NE] = $router;

        if ($timeout == true)
            return 1;
        return 0;
    }

    /* Send msg to 'AbeilleCmd'
       Returns: 0=OK, -1=ERROR (fatal since queue issue) */
    function msgToCmd($dest, $addr, $index) {
        $msg = array();
        $msg['topic'] = "Cmd".$dest."/".$addr."/getNeighborTable";
        $msg['payload'] = "startIndex=".$index;
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
        logMessage("", "  msgToCmd: ".$msgJson);

        global $queueXToCmd;
        if (@msg_send($queueXToCmd, 1, $msgJson, false, false, $errCode) == false) {
            $errMsg = AbeilleTools::getMsgSendErr($errCode);
            logMessage('error', "  msgToCmd() ERROR ${errCode}/${errMsg} in AbeilleLQI");
            return -1;
        }
        return 0;
    }

    function updateLockFile($msg) {
        global $lockFile;
        $lockStatus = @file_put_contents($lockFile, $msg);
        if ($lockStatus === false) {
            echo "ERROR: Can't write lock file";
            logMessage("", "Unable to write lock file.");
            @unlink($lockFile);
            exit;
        }
    }

    /* Send 1 to several table requests thru AbeilleCmd to collect neighbour table entries.
       Returns: 0=OK, -1=ERROR (stops collect for current zigate), 1=timeout */
    function interrogateEq($eqIdx) {
        // logMessage("", "  interrogateEq(${netName}, ${addr}, ${eqIdx})");

        global $eqToInterrogate;
        $logicId = $eqToInterrogate[$eqIdx]['logicId'];
        $name = $eqToInterrogate[$eqIdx]['name'];

        list($netName, $addr) = explode('/', $logicId);
        logMessage("", "Interrogating '${name}' (Addr=${addr}, EqIdx=${eqIdx})");
        global $done, $total;
        updateLockFile("${netName} - ${done}/${total}: Interrogation de '".$name."' (".$addr.")");

        while (true) {
            $eq = $eqToInterrogate[$eqIdx]; // Read eq status
            msgToCmd($netName, $addr, sprintf("%02X", $eq['tableIndex']));
            usleep(200000); // Delay of 200ms to let response to come back
            $ret = msgFromParser($eqIdx);
            if ($ret == 1) {
                /* If time-out, cancel interrogation for current eq only */
                // logMessage("", "Time-out => Cancelling interrogation of '".$eq['name']."' (".$eq['addr'].").");
                return 1;
            }
            if ($ret != 0) {
                /* Something failed. Stopping collect since might be due to several reasons
                   like some daemons crash & restarted */
                return -1;
            }

            $eq = $eqToInterrogate[$eqIdx]; // Reread eq status
            if ($eq['tableIndex'] >= $eq['tableEntries'])
                break; // Exiting interrogation loop
        }

        return 0;
    }

    /*--------------------------------------------------------------------------------------------------*/
    /* Main
     /*--------------------------------------------------------------------------------------------------*/
    // To test in shell mode: php AbeilleLQI.php <zgNb>

    logSetConf(jeedom::getTmpFolder("Abeille")."/AbeilleLQI.log", true);
    logMessage("", ">>> AbeilleLQI starting");

    /* Note: depending on the way 'AbeilleLQI' is launched, arguments are not
       collected in the same way.
       URL => use $_GET[]
       Cmd line/shell => use $argv[] */
    if (isset($_GET['zigate'])) { // Zigate nb passed as URL ?
        $zgId = $_GET['zigate'];
    } else if (isset($argv[1])) { // Zigate nb passed as args ?
        $zgId = $argv[1];
    } else
        $zgId = -1;
    if (($zgId != -1) && (($zgId < 1) or ($zgId > 10))) {
        logMessage("", "ERROR: Bad zigate id => aborting.");
        exit;
    }

    if ($zgId == -1) {
        logMessage("", "Request to interrogate all active zigates");
        $zgStart = 1;
        $zgEnd = maxNbOfZigate;
    } else {
        logMessage("", "Request to interrogate zigate ".$zgId." only");
        $zgStart = $zgId;
        $zgEnd = $zgId;
    }

    $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory

    // Checking if LQI collector is already running
    $lockFile = $tmpDir."/AbeilleLQI.lock";
    if (file_exists($lockFile)) {
        $content = file_get_contents($lockFile);
        logMessage("", "Lock content: '${content}'");
        if (substr($content, 0, 4) != "done") {
            exec("pgrep -a php | grep AbeilleLQI", $running);
            if (sizeof($running) != 0) {
                echo 'ERROR: Collect already ongoing';
                logMessage("", "LQI collect already ongoing (lock file found) => collect canceled");
                exit;
            } else {
                logMessage("", "Previous LQI collect crashed. Removing lock file.");
                unlink($lockFile);
            }
        }
    }

    // Collecting known equipments list
    logMessage("", "Known Jeedom equipments:");
    $eqLogics = eqLogic::byType('Abeille');
    $knownFromJeedom = array();
    foreach ($eqLogics as $eqLogic) {
        $eqId = $eqLogic->getId();
        $eqName = $eqLogic->getName();
        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode('/', $eqLogicId);
        if (($net == "") || ($addr == "")) {
            logMessage("", "  ${eqId}: '${eqName}' (${eqLogicId}) invalid => ignored");
            continue;
        }

        $knownFromJeedom[$eqLogicId]['name'] = $eqName;
        $eqParent = $eqLogic->getObject();
        if (!is_object($eqParent))
            $objName = "";
        else
            $objName = $eqParent->getName();
        $knownFromJeedom[$eqLogicId]['parent'] = $objName;
        $knownFromJeedom[$eqLogicId]['ieee'] = $eqLogic->getConfiguration('IEEE', '');
        $knownFromJeedom[$eqLogicId]['icon'] = $eqLogic->getConfiguration('ab::icon', 'defaultUnknown');
        $knownFromJeedom[$eqLogicId]['zigbee'] = $eqLogic->getConfiguration('ab::zigbee', []);
        logMessage("", "  ${eqId}: '${eqName}' (${eqLogicId}), Parent='${objName}'");
        logMessage("", "    Zigbee=".json_encode($knownFromJeedom[$eqLogicId]['zigbee'], JSON_UNESCAPED_SLASHES));
    }

    $queueXToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);
    $queueParserToLQI = msg_get_queue($abQueues["parserToLQI"]["id"]);
    $queueParserToLQIMax = $abQueues["parserToLQI"]["max"];
    msgFromParserFlush(); // Flush the queue if not empty

    for ($zgId = $zgStart; $zgId <= $zgEnd; $zgId++) {
        if (config::byKey('ab::gtwEnabled'.$zgId, 'Abeille', 'N') != 'Y') {
            logMessage("", "Zigate ".$zgId." disabled => Ignored.");
            continue;
        }

        $netName = "Abeille".$zgId; // Abeille network
        $newDataFile = $tmpDir."/AbeilleLQI-".$netName.".json"; // New format, replacing 'AbeilleLQI_MapDataAbeilleX.json'

        // $nbwritten = file_put_contents($lockFile, "init");
        // if ($nbwritten < 1) {
        //     unlink($lockFile);
        //     echo "ERROR: Can't write lock file";
        //     logMessage("", "Unable to write lock file (".$lockFile.") => collect canceled");
        //     exit;
        // }
        updateLockFile("init");

        $lqiTable = array(
            'signature' => 'Abeille LQI table',
            'net' => "Abeille".$zgId,
            'collectTime' => time(), // Time here is start of collect
            'routers' => array()
        ); // Result from interrogations (new format)

        $eqToInterrogate = array();
        newRouter("Abeille${zgId}/0000");

        $done = 0;
        $eqIdx = 0; // Index of eq to interrogate
        $collectStatus = 0;
        $retry = false;
        while (true) {
            $total = count($eqToInterrogate);
            if ($total == 0) {
                logMessage("", "Gateway ${zgId} progress: NO router to interrogate => abnormal.");
                break;
            }
            logMessage("", "Gateway ${zgId} progress: ${done}/${total}");

            if (!isset($eqToInterrogate[$eqIdx])) {
                logMessage("", "  ERR: eqToInterrogate[${eqIdx}] is undefined");
            } else if (!$eqToInterrogate[$eqIdx]['completed']) {
                // $routerLogicId = $eqToInterrogate[$eqIdx]['logicId'];
                // list($netName, $addr) = explode('/', $routerLogicId);

                // $NE = $routerLogicId;

                // if (isset($knownFromJeedom[$routerLogicId]))
                //     $name = $knownFromJeedom[$routerLogicId]['name'];
                // else
                //     $name = "Inconnu-" . $routerLogicId;
                // $name = $eqToInterrogate[$eqIdx]['name'];

                $ret = interrogateEq($eqIdx);
                if ($ret == -1) {
                    $collectStatus = -1; // Collect interrupted due to error
                    logMessage("", "Collecte stopped on zigate ".$zgId." due to errors.");
                    break;
                } else if ($ret == 1) { // Timeout
                    $collectStatus = 1; // At least 1 interrogation canceled due to timeout
                    if ($eqToInterrogate[$eqIdx]['retry'] != 0) {
                        $eqToInterrogate[$eqIdx]['retry']--;
                        $retry = true; // This will be retried in next round
                        updateLockFile("= Time-out => Nouvelle tentative");
                    } else {
                        $name = $eqToInterrogate[$eqIdx]['name'];
                        logMessage("", "  ${name} router is DEAD");

                        $eqToInterrogate[$eqIdx]['dead'] = true;
                        $eqToInterrogate[$eqIdx]['completed'] = true;
                        $done++;
                        updateLockFile("= Time-out => Ce routeur est considéré comme mort.");
                    }
                } else if ($ret == 0) {
                    $eqToInterrogate[$eqIdx]['completed'] = true;
                    $done++;
                    if ($eqToInterrogate[$eqIdx]['tableEntries'] == 0)
                        updateLockFile("= Aucun équipement en vie.");
                }
            }

            $eqIdx++;
            if ($eqIdx == count($eqToInterrogate)) {
                // End of list: New round for retry ?
                if ($retry) {
                    $eqIdx = 0;
                    $retry = false;
                    sleep(3); // Little pause before retrying
                    logMessage("", "New round for retry cases.");
                } else
                    break;
            }
        }

        /* Write JSON cache only if collect completed successfully or on timeout */
        if ($collectStatus != -1) {
            // Storing also new output format
            $json = json_encode($lqiTable, JSON_UNESCAPED_SLASHES);
            if (file_put_contents($newDataFile, $json)) {
                echo "Ok: ".$netName." collect ended successfully";
            } else {
                unlink($newDataFile);
                echo "ERROR: Data file write pb.";
            }
        }

        // Announce end of processing with status
        switch ($collectStatus) {
        case 0: $status = "ok"; break;
        case 1: $status = "partial"; break; // Ok but some eq may be missing
        default: $status = "error"; break; // Interrupted
        }
        // file_put_contents($lockFile, "done/".time()."/".$status);
        updateLockFile("= Collecte terminée: ".time()."/".$status);
    }

    logMessage("", "<<< AbeilleLQI exiting.");
?>
