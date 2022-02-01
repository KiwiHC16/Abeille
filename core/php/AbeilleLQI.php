<?php

    /*
     * LQI collector to draw nodes table & network graph
     * Called once a day by cron, or on user request from "Network" pages.
     *
     * Starting from zigate (coordinator), send LQI request to get neighbour table
     * - Send request thru AbeilleCmd (004E cmd)
     * - Get response from AbeilleParser (804E cmd)
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

    /* Add to list a new eq (router or coordinator) to interrogate.
       Check first is not aleady in the list. */
    function newEqToInterrogate($logicId) {
        global $eqToInterrogate;
        global $knownFromJeedom;

        /* Checking if not already in the list */
        foreach ($eqToInterrogate as $Eq) {
            if ($Eq['LogicId'] == $logicId)
                return; // Already there
        }
        // TODO: What to do if mesg received does not match interrogated eq ?

        if (isset($knownFromJeedom[$logicId]))
            $eqName = $knownFromJeedom[$logicId]['name'];
        else
            $eqName = "Inconnu";
        list($netName, $addr) = explode('/', $logicId);
        $eqToInterrogate[] = array(
            "LogicId" => $logicId,
            "Name" => $eqName,
            "Addr" => $addr,
            "TableEntries" => 0,    // Nb of entries in its table
            "TableIndex" => 0,      // Index to interrogate
        );
        logMessage("", "New device to interrogate: '".$eqName."' (".$logicId.")");
    }

    /* Remove any pending messages from parser */
    function msgFromParserFlush() {
        global $queueParserToLQI;

        while (msg_receive($queueParserToLQI, 0, $msgType, 512, $msg, true, MSG_IPC_NOWAIT | MSG_NOERROR));
    }

    /* Treat request responses (804E) from parser.
       Returns: 0=OK, -1=fatal error, 1=timeout */
    function msgFromParser($eqIndex) {
        logMessage("", "msgFromParser(eqIndex=".$eqIndex.")");

        global $LQI;
        global $queueParserToLQI, $queueParserToLQIMax;
        global $eqToInterrogate;
        global $knownFromJeedom;
        // global $objKnownFromAbeille;

        $timeout = 10; // 10sec (useful when there is unknown eq interrogation during LQI collect)
        for ($t = 0; $t < $timeout; ) {
            // logMessage("", "  Queue stat=".json_encode(msg_stat_queue($queueParserToLQI)));
            $msgMax = $queueParserToLQIMax;
            if (msg_receive($queueParserToLQI, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == true) {
                /* Message received. Let's check it is the expected one */
                $msg = json_decode($msgJson);
                if ($msg->type != "804E") {
                    logMessage("", "  WARNING: Unsupported message type (".$msg->type.") => Ignored.");
                    continue;
                }
                if (hexdec($msg->startIndex) != $eqToInterrogate[$eqIndex]['TableIndex']) {
                    /* Note: this case is due to too many identical 004E messages sent to eq
                       leading to several identical 804E answers */
                    logMessage("", "  WARNING: Unexpected start index (".$msg->startIndex.") => Ignored.");
                    continue;
                }
                if ($msg->srcAddr != $eqToInterrogate[$eqIndex]['Addr']) {
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
                msg_receive($queueParserToLQI, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
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

        /* Message format reminder
            $msg = array(
                'type' => '804E',
                'srcAddr' => $SrcAddr,
                'tableEntries' => $NTableEntries,
                'tableListCount' => $NTableListCount,
                'startIndex' => $StartIndex,
                'list' => $NList
                    $N = array(
                        "Addr"     => substr($payload, $j + 0, 4),
                        "ExtPANId" => substr($payload, $j + 4, 16),
                        "ExtAddr"  => substr($payload, $j + 20, 16),
                        "Depth"    => substr($payload, $j + 36, 2),
                        "LQI"      => substr($payload, $j + 38, 2),
                        "BitMap"   => substr($payload, $j + 40, 2)
                )
            ); */

        $tableEntries = $msg->tableEntries; // Total entries on interrogated eq
        $tableListCount = $msg->tableListCount; // Number of neighbours liste in msg
        $startIndex = $msg->startIndex;
        $NList = $msg->list; // List of neighbours
        // logMessage("", "NList=".json_encode($NList));
        logMessage("", "  tableEntries=".$tableEntries.", tableListCount=".$tableListCount.", startIndex=".$startIndex);

        /* Updating collect infos for this coordinator/router */
        $eqToInterrogate[$eqIndex]['TableEntries'] = hexdec($tableEntries);
        $eqToInterrogate[$eqIndex]['TableIndex'] = hexdec($startIndex) + hexdec($tableListCount);

        $NE = $eqToInterrogate[$eqIndex]["LogicId"]; // Ex: 'Abeille1/A3B4'

        $parameters = array();
        $parameters['NE'] = $NE; // Logical ID
        if (isset($knownFromJeedom[$NE])) {
            $parameters['NE_Name'] = $knownFromJeedom[$NE]['name']; // Name
            // $parameters['NE_Objet'] = $objKnownFromAbeille[$NE]; // Parent object
            $parameters['NE_Objet'] = $knownFromJeedom[$NE]['parent']; // Parent object
            $parent = Abeille::byLogicalId($NE, 'Abeille');
            $parentIEEE = $parent->getConfiguration('IEEE', '');
            $parameters['IEEE_Address'] = $parentIEEE;
        } else {
            /* EQ is still in Zigbee network but is unknown to Jeedom/Abeille */
            $parameters['NE_Name'] = "Inconnu";
            $parameters['NE_Objet'] = "";
            $parentIEEE = "";
        }
        list($netName, $addr) = explode('/', $NE);

        /* Going thru neighbours list */
        for ($nIndex = 0; $nIndex < hexdec($tableListCount); $nIndex++ ) {
            $N = $NList[$nIndex];
            logMessage("", "  N=".json_encode($N));

            // list( $lqi, $voisineAddr, $i ) = explode("/", $message->topic);

            $parameters['Voisine'] = $netName."/".$N->Addr;
            if (isset($knownFromJeedom[$parameters['Voisine']])) {
                $parameters['Voisine_Name'] = $knownFromJeedom[$parameters['Voisine']]['name'];
                // $parameters['Voisine_Objet'] = $objKnownFromAbeille[$parameters['Voisine']];
                $parameters['Voisine_Objet'] = $knownFromJeedom[$parameters['Voisine']]['parent'];
            } else {
                $parameters['Voisine_Name'] = $parameters['Voisine'];
                $parameters['Voisine_Objet'] = "Inconnu";
            }

            $parameters['Depth'] = $N->Depth;
            $parameters['LinkQualityDec'] = hexdec($N->LQI);

            // Decode Bitmap Attribut
            // Bit map of attributes Described below: uint8_t
            // bit 0-1 Device Type (0-Coordinator 1-Router 2-End Device)    => Process
            // bit 2-3 Permit Join status (1- On 0-Off)                     => Skip no need for the time being
            // bit 4-5 Relationship (0-Parent 1-Child 2-Sibling)            => Process
            // bit 6-7 Rx On When Idle status (1-On 0-Off)                  => Process
            $Attr = hexdec($N->BitMap);
            $AttrType = $Attr & 0b00000011;
            if ($AttrType == 0) {
                $parameters['Type'] = "Coordinator";
            } else if ($AttrType == 1) {
                $parameters['Type'] = "Router";
                newEqToInterrogate($parameters['Voisine']);
            } else if ($AttrType== 2) {
                $parameters['Type'] = "End Device";
            } else { // $AttrType== 3
                $parameters['Type'] = "Unknown";
            }

            $AttrRel = ($Attr & 0b00110000) >> 4;
            if ($AttrRel == 0) {
                $parameters['Relationship'] = "Parent";
            } else if ($AttrRel == 1) {
                $parameters['Relationship'] = "Child";

                // Required by remove from zigbee feature (#1770)
                // Tcharp38: It appears that in several cases we don't have any parent IEEE
                //   might not be required if remove is using 004C cmd instead of 0026
                $kid = Abeille::byLogicalId($netName.'/'.$N->Addr, 'Abeille');
                if ($kid) { // Saving parent IEEE address
                    $kid->setConfiguration('parentIEEE', $parentIEEE);
                    $kid->save();
                } else
                    logMessage("", "  WARNING: Unkown device '".$netName."/".$N->Addr."'");
            } else if ($AttrRel == 2) {
                $parameters['Relationship'] = "Sibling";
            } else { // if ($AttrRel == 3)
                $parameters['Relationship'] = "Unknown";
            }

            $AttrRx = ($Attr & 0b11000000) >> 6;
            if ($AttrRx == 0) {
                $parameters['Rx'] = "Rx-Off";
            } else if ($AttrRx == 1) {
                $parameters['Rx'] = "Rx-On";
            } else { // 2 or 3
                $parameters['Rx'] = "Rx-Unknown";
            }

            $LQI[] = $parameters;
        }

        return 0;
    }

    /* Send msg to 'AbeilleCmd'
       Returns: 0=OK, -1=ERROR (fatal since queue issue) */
    function msgToCmd($dest, $addr, $index) {
        $msg = array();
        $msg['topic'] = "Cmd".$dest."/".$addr."/getNeighborTable";
        $msg['payload'] = "startIndex=".$index;
        logMessage("", "msgToCmd: ".json_encode($msg));

        global $queueLQIToCmd;
        if (msg_send($queueLQIToCmd, priorityInterrogation, $msg, true, false) == false) {
            logMessage('error', "msgToCmd: Unable to send message to AbeilleCmd");
            return -1;
        }
        return 0;
    }

    /* Send 1 to several table requests thru AbeilleCmd to collect neighbour table entries.
       Returns: 0=OK, -1=ERROR (stops collect for current zigate), 1=timeout */
    function interrogateEq($netName, $addr, $eqIndex) {
        global $eqToInterrogate;

        while (true) {
            $eq = $eqToInterrogate[$eqIndex]; // Read eq status
            msgToCmd($netName, $addr, sprintf("%02X", $eq['TableIndex']));
            usleep(200000); // Delay of 200ms to let response to come back
            $ret = msgFromParser($eqIndex);
            if ($ret == 1) {
                /* If time-out, cancel interrogation for current eq only */
                logMessage("", "Time-out => Cancelling interrogation of '".$eq['Name']."' (".$eq['Addr'].").");
                return 1;
            }
            if ($ret != 0) {
                /* Something failed. Stopping collect since might be due to several reasons
                   like some daemons crash & restarted */
                return -1;
            }

            $eq = $eqToInterrogate[$eqIndex]; // Read eq status
            if ($eq['TableIndex'] >= $eq['TableEntries'])
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
        logMessage("", "  Bad zigate id => aborting.");
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

    // Collecting known equipments list
    logMessage("", "Known Jeedom equipments:");
    $eqLogics = eqLogic::byType('Abeille');
    $knownFromJeedom = array();
    // $objKnownFromAbeille = array();
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId();
        $knownFromJeedom[$eqLogicId]['name'] = $eqLogic->getName();
        $eqParent = $eqLogic->getObject();
        if (!is_object($eqParent))
            $objName = "";
        else
            $objName = $eqParent->getName();
        $knownFromJeedom[$eqLogicId]['parent'] = $objName;
        // $objKnownFromAbeille[$eqLogicId] = $objName;
        logMessage("", "  Eq='".$eqLogicId."', objname='".$objName."'");
    }
    // logMessage("", "Objets connus de Jeedom: ".json_encode($objKnownFromAbeille));

    // $queueLQIToCmd = msg_get_queue($abQueues["LQIToCmd"]["id"]);
    $queueLQIToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);
    $queueParserToLQI = msg_get_queue($abQueues["parserToLQI"]["id"]);
    $queueParserToLQIMax = $abQueues["parserToLQI"]["max"];
    msgFromParserFlush(); // Flush the queue if not empty

    $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory

    for ($zgId = $zgStart; $zgId <= $zgEnd; $zgId++) {
        if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') != 'Y') {
            logMessage("", "Zigate ".$zgId." disabled => Ignored.");
            continue;
        }

        $netName = "Abeille".$zgId; // Abeille network
        $dataFile = $tmpDir."/AbeilleLQI_MapData".$netName.".json";
        $lockFile = $dataFile.".lock";
        if (file_exists($lockFile)) {
            $content = file_get_contents($lockFile);
            logMessage("", $netName." lock content: '".$content."'");
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
        $nbwritten = file_put_contents($lockFile, "init");
        if ($nbwritten < 1) {
            unlink($lockFile);
            echo "ERROR: Can't write lock file";
            logMessage("", "Unable to write lock file (".$lockFile.") => collect canceled");
            exit;
        }

        $LQI = array(); // Result from interrogations
        $eqToInterrogate = array();
        newEqToInterrogate("Abeille".$zgId."/0000");

        $done = 0;
        $eqIndex = 0; // Index of eq to interrogate
        $collectStatus = 0;
        while (true) {
            $total = count($eqToInterrogate);
            logMessage("", "Zigate ".$zgId." progress: ".$done."/".$total);

            $currentNeAddress = $eqToInterrogate[$eqIndex]['LogicId'];
            list($netName, $addr) = explode('/', $currentNeAddress);

            $NE = $currentNeAddress;

            if (isset($knownFromJeedom[$currentNeAddress]))
                $name = $knownFromJeedom[$currentNeAddress]['name'];
            else
                $name = "Inconnu-" . $currentNeAddress;

            logMessage("", "Interrogating '".$name."' (".$addr.")");
            $nbwritten = file_put_contents($lockFile, "Analyse du réseau ".$netName.": ".$done."/".$total." => interrogation de '".$name."' (".$addr.")");
            if ($nbwritten < 1) {
                echo "ERROR: Can't write lock file";
                logMessage("", "Unable to write lock file.");
                unlink($lockFile);
                exit;
            }

            $ret = interrogateEq($netName, $addr, $eqIndex);
            $done++;
            if ($ret == -1) {
                $collectStatus = -1; // Collect interrupted due to error
                logMessage("", "Collecte stopped on zigate ".$zgId." due to errors.");
                break;
            }
            if ($ret == 1) {
                $collectStatus = 1; // At least 1 interrogation canceled due to timeout
            }

            /* End of list ? */
            if (($eqIndex + 1) == count($eqToInterrogate))
                break;
            $eqIndex++;
        }

        /* Write JSON cache only if collect completed successfully or on timeout */
        if ($collectStatus != -1) {
            // Encode array to json
            $json = json_encode(array('data' => $LQI));

            // Write json to file
            if (file_put_contents($dataFile, $json)) {
                echo "Ok: ".$netName." collect ended successfully";
            } else {
                unlink($dataFile);
                echo "ERROR: Data file write pb.";
            }
        }

        // Announce end of processing with status
        switch ($collectStatus) {
        case 0: $status = "ok"; break;
        case 1: $status = "partial"; break; // Ok but some eq may be missing
        default: $status = "error"; break; // Interrupted
        }
        file_put_contents($lockFile, "done/".time()."/".$status);
    }

    logMessage("", "<<< AbeilleLQI exiting.");
?>
