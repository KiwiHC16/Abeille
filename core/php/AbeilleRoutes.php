<?php

    /*
     * Collect routing tables from routers
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

    /* Add to list a new eq (router or coordinator) to interrogate.
       Check first is not aleady in the list. */
    function newRouter($zgId, $rLogicId) {
        global $routers;
        global $knownFromJeedom;

        /* Checking if not already in the list */
        if (!isset($routers[$zgId]))
            $routers[$zgId] = [];
        else {
            if (in_array($rLogicId, $routers[$zgId]))
                return; // Already there
        }
        // TODO: What to do if mesg received does not match interrogated eq ?

        list($net, $addr) = explode('/', $rLogicId);
        $routers[$zgId][$rLogicId] = array(
            // "logicId" => $rLogicId,
            "name" => $knownFromJeedom[$rLogicId]['name'],
            "addr" => $addr,
            "tableEntries" => 0,    // Nb of entries in its table
            "tableIndex" => 0,      // Index to interrogate
        );
        logMessage("", "  New Abeille".$zgId." router: ".$knownFromJeedom[$rLogicId]['hName']." (".$rLogicId.")");
    }

    /* Remove any pending messages from parser */
    function msgFromParserFlush() {
        global $queueParserToRoutes;

        while (msg_receive($queueParserToRoutes, 0, $msgType, 2048, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR));
    }

    /* Treat request responses (804E) from parser.
       Returns: 0=OK, -1=fatal error, 1=timeout */
    function msgFromParser($zgId, $rLogicId) {
        logMessage("", "  msgFromParser(".$rLogicId.")");

        global $queueParserToRoutes, $queueParserToRoutesMax;
        global $routers;
        global $knownFromJeedom;

        $router = &$routers[$zgId][$rLogicId];

        $timeout = 10; // 10sec (useful when there is unknown eq interrogation during LQI collect)
        for ($t = 0; $t < $timeout; ) {
            // logMessage("", "  Queue stat=".json_encode(msg_stat_queue($queueParserToRoutes)));
            $msgMax = $queueParserToRoutesMax;
            if (msg_receive($queueParserToRoutes, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == true) {
                // logMessage("", "  msgJson=".$msgJson);

                /* Message received. Let's check it is the expected one */
                $msg = json_decode($msgJson);
                if ($msg->type != "routingTable") {
                    logMessage("", "  WARNING: Unsupported message type (".$msg->type.") => Ignored.");
                    continue;
                }
                if (hexdec($msg->startIdx) != $router['tableIndex']) {
                    /* Note: this case is due to too many identical 004E messages sent to eq
                       leading to several identical 804E answers */
                    logMessage("", "  WARNING: Unexpected start index (".$msg->startIdx.") => Ignored.");
                    continue;
                }
                if ($msg->srcAddr != $router['addr']) {
                    logMessage("", "  WARNING: Unexpected source addr (".$msg->srcAddr.") => Ignored.");
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
                msg_receive($queueParserToRoutes, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                logMessage("", "  WARNING: TOO BIG msg => ignored");
                continue;
            }

            /* It's an error */
            logMessage("", "  msg_receive() ERROR: ".$errCode."/".posix_strerror($errCode));
            return -1;
        }
        if ($t >= $timeout) {
            logMessage("", "  Time-out !");
            return 1;
        }
        // Note: What to do if mesg received does not match interrogated eq ?
        //       This currently can't appear since requests are done sequentially
        //       so no risk to get an answer from an unexpected source.

        /* Message from parser: format reminder
            $msg = array(
                'type' => 'routingTable',
                'srcAddr' => $srcAddr,
                'tableEntries' => $tableEntries (number),
                'tableListCount' => $tableCount (number),
                'startIdx' => $startIdx (number),
                'table' => $table
                    'addr X' => 'nextHop X'
                    'addr Y' => 'nextHop Y'
            ); */

        logMessage("", "  msgJson=".$msgJson);
        $tableEntries = $msg->tableEntries; // Total entries in routing table
        $tableListCount = $msg->tableListCount; // Number of entries in this msg
        $startIdx = $msg->startIdx;
        // $table = $msg->table; // Routing table
        logMessage("", "  TableEntries=".$tableEntries.", TableListCount=".$tableListCount.", StartIdx=".$startIdx);

        /* Updating collect infos for this coordinator/router */
        $router['tableEntries'] = $tableEntries;
        $router['tableIndex'] = $startIdx + $tableListCount;

        // $routerLogicId = $routers[$routerIdx]["logicId"]; // Ex: 'Abeille1/A3B4'
        list($netName, $addr) = explode('/', $rLogicId);

        //
        // 'AbeilleRoutes-AbeilleX.json' format
        // 'routers' => array(
        //     'addr' => Router addr
        //     'table' => array(
        //         destAddr => nextHop
        //     )
        // )
        global $routingTable;
        if (isset($routingTable['routers'][$rLogicId])) {
            $router2 = $routingTable['routers'][$rLogicId];
        } else {
            $router2 = array(
                'addr' => $msg->srcAddr,
                'table' => $msg->table,
            );
        }
        $routingTable['routers'][$rLogicId] = $router2;

        return 0;
    }

    /* Send msg to 'AbeilleCmd'
       Returns: 0=OK, -1=ERROR (fatal since queue issue) */
    function msgToCmd($dest, $addr, $index) {
        $msg = array();
        $msg['topic'] = "Cmd".$dest."/".$addr."/getRoutingTable";
        $msg['payload'] = "startIdx=".$index;
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
        logMessage("", "  msgToCmd: ".$msgJson);

        global $queueXToCmd;
        if (msg_send($queueXToCmd, PRIO_NORM, $msgJson, false, false) == false) {
            logMessage('error', "  msgToCmd: Unable to send message to AbeilleCmd");
            return -1;
        }
        return 0;
    }

    // /* Send 1 to several table requests thru AbeilleCmd to collect neighbour table entries.
    //    Returns: 0=OK, -1=ERROR (stops collect for current zigate), 1=timeout */
    // function interrogateEq($zgId, $rLogicId, $net, $addr) {
    //     global $routers;

    //     while (true) {
    //         $router = &$routers[$zgId][$rLogicId]; // Read eq status
    //         msgToCmd($net, $addr, sprintf("%02X", $router['tableIndex']));

    //         usleep(200000); // Delay of 200ms to let response to come back
    //         $ret = msgFromParser($zgId, $rLogicId);
    //         if ($ret == 1) {
    //             /* If time-out, cancel interrogation for current eq only */
    //             logMessage("", "Time-out => Cancelling interrogation of '".$router['name']."' (".$router['addr'].").");
    //             return 1;
    //         }
    //         if ($ret != 0) {
    //             /* Something failed. Stopping collect since might be due to several reasons
    //                like some daemons crash & restarted */
    //             return -1;
    //         }

    //         // $router = $routers[$routerIdx]; // Read eq status
    //         if ($router['tableIndex'] >= $router['tableEntries'])
    //             break; // Exiting interrogation loop
    //     }

    //     return 0;
    // }

    /*--------------------------------------------------------------------------------------------------*/
    /* Main
     /*--------------------------------------------------------------------------------------------------*/
    // To test in shell mode: php AbeilleLQI.php <zgNb>

    logSetConf(jeedom::getTmpFolder("Abeille")."/AbeilleRoutes.log", true);
    logMessage("", ">>> AbeilleRoutes starting");

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

    $queueXToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);
    $queueParserToRoutes = msg_get_queue($abQueues["parserToRoutes"]["id"]);
    if ($queueParserToRoutes == false) {
        logMessage("", "ERROR: Can't get queue 'ParserToRoutes'.");
        exit;
    }
    $queueParserToRoutesMax = $abQueues["parserToRoutes"]["max"];
    msgFromParserFlush(); // Flush the queue if not empty

    // Collecting known equipments list
    logMessage("", "Jeedom known equipments:");
    $eqLogics = eqLogic::byType('Abeille');
    $knownFromJeedom = array();
    $routers = []; // List of routers
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId();
        $eqHName = $eqLogic->getHumanName();
        if (!$eqLogic->getIsEnable()) {
            logMessage("", "  ".$eqHName." (".$eqLogicId.") => DISABLED");
            continue; // Disabled
        }
        list($net, $addr) = explode('/', $eqLogicId);
        $zgId2 = substr($net, 7); // AbeilleX => X
        if (($zgId2 < $zgStart) || ($zgId2 > $zgEnd))
            continue; // Not in the scope

        $newEq = [];
        $newEq['name'] = $eqLogic->getName();
        $newEq['hName'] = $eqHName;
        $eqParent = $eqLogic->getObject();
        if (!is_object($eqParent))
            $eqPName = "";
        else
            $eqPName = $eqParent->getName();
        $newEq['parent'] = $eqPName;
        $newEq['ieee'] = $eqLogic->getConfiguration('IEEE', '');
        $newEq['icon'] = $eqLogic->getConfiguration('ab::icon', 'defaultUnknown');
        $knownFromJeedom[$eqLogicId] = $newEq;
        logMessage("", "  ".$newEq['hName']." (".$eqLogicId.")");

        // Router ?
        if ($addr == '0000')
            newRouter($zgId2, "Abeille".$zgId2."/0000");
        else {
            // If device is FFD, it is a router
            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            if (isset($zigbee['macCapa'])) {
                $mc = hexdec($zigbee['macCapa']);
                if (($mc >> 1) & 1) // Device type = 1 => FFD
                    newRouter($zgId2, $eqLogicId);
            }
        }
    }

    $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory

    for ($zgId = $zgStart; $zgId <= $zgEnd; $zgId++) {
        if (config::byKey('ab::gtwEnabled'.$zgId, 'Abeille', 'N') != 'Y') {
            logMessage("", "Zigate ".$zgId." disabled => Ignored.");
            continue;
        }

        $netName = "Abeille".$zgId; // Abeille network
        $newDataFile = $tmpDir."/AbeilleRoutes-".$netName.".json";
        // $lockFile = $newDataFile.".lock";
        // if (file_exists($lockFile)) {
        //     $content = file_get_contents($lockFile);
        //     logMessage("", $netName." lock content: '".$content."'");
        //     if (substr($content, 0, 4) != "done") {
        //         exec("pgrep -a php | grep AbeilleLQI", $running);
        //         if (sizeof($running) != 0) {
        //             echo 'ERROR: Collect already ongoing';
        //             logMessage("", "LQI collect already ongoing (lock file found) => collect canceled");
        //             exit;
        //         } else {
        //             logMessage("", "Previous LQI collect crashed. Removing lock file.");
        //             unlink($lockFile);
        //         }
        //     }
        // }
        // $nbwritten = file_put_contents($lockFile, "init");
        // if ($nbwritten < 1) {
        //     unlink($lockFile);
        //     echo "ERROR: Can't write lock file";
        //     logMessage("", "Unable to write lock file (".$lockFile.") => collect canceled");
        //     exit;
        // }

        // Result from interrogations
        $routingTable = array(
            'signature' => 'Abeille routing table',
            'net' => "Abeille".$zgId,
            'collectTime' => time(), // Time here is start of collect
            'routers' => array()
        );
        // newRouter("Abeille".$zgId."/0000");

        $collectStatus = 0;
        $done = 0;
        $total = count($routers[$zgId]);
        foreach ($routers[$zgId] as $rLogicId => $router) { // Go thru all routers
            logMessage("", "Zigate ".$zgId." progress: ".$done."/".$total);

            $name = $router['name'];
            $addr = $router['addr'];

            logMessage("", "Interrogating '".$name."' (".$addr.")");
            // $nbwritten = file_put_contents($lockFile, "Analyse du réseau ".$netName.": ".$done."/".$total." => interrogation de '".$name."' (".$addr.")");
            // if ($nbwritten < 1) {
            //     echo "ERROR: Can't write lock file";
            //     logMessage("", "Unable to write lock file.");
            //     unlink($lockFile);
            //     exit;
            // }

            // $ret = interrogateEq($zgId, $rLogicId, $netName, $addr);
            while (true) {
                // $router = &$routers[$zgId][$rLogicId]; // Read eq status
                msgToCmd($net, $addr, sprintf("%02X", $router['tableIndex']));

                usleep(200000); // Delay of 200ms to let response to come back
                $ret = msgFromParser($zgId, $rLogicId);
                if ($ret == 1) {
                    /* If time-out, cancel interrogation for current eq only */
                    // logMessage("", "Time-out => Cancelling interrogation of '".$router['name']."' (".$router['addr'].").");
                    break;
                }
                if ($ret != 0) {
                    /* Something failed. Stopping collect since might be due to several reasons
                       like some daemons crash & restarted */
                    break;
                }

                // $router = $routers[$routerIdx]; // Read eq status
                if ($router['tableIndex'] >= $router['tableEntries'])
                    break; // Exiting interrogation loop
            }

            $done++;
            if ($ret == -1) {
                $collectStatus = -1; // Collect interrupted due to error
                logMessage("", "Collecte stopped on zigate ".$zgId." due to errors.");
                break;
            }
            if ($ret == 1) {
                $collectStatus = 1; // Collect is partial due to timeout
            }
        }

        /* Write JSON cache only if collect completed successfully or on timeout */
        if ($collectStatus != -1) {
            // Storing also new output format
            $json = json_encode($routingTable);
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
    }

    logMessage("", "<<< AbeilleRoutes exiting.");
?>
