<?php

    /*
     * LQI collector to draw nodes table & network graph
     * Called once a day by cron, or on user request from "Network" pages.
     *
     * Starting from zigate (coordinator), send LQI request to get neighbour table
     * - Send request thru AbeilleCmd (004E cmd)
     * - Get response from AbeilleParser (804E cmd)
     * - Identify each neighbour
     * - If neighbour is router, added to list for interrogation
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        // include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__."/../../../../core/php/core.inc.php";
    include_once("../../core/config/Abeille.config.php");
    include_once "AbeilleLog.php"; // Log library

    /* Add to list a new eq (router or coordinator) to interrogate.
       Check first is not aleady in the list. */
    function newEqToInterrogate($logicId) {
        global $eqToInterrogate;
        global $eqKnownFromAbeille;

        /* Checking if not already in the list */
        foreach ($eqToInterrogate as $Eq) {
            if ($Eq['LogicId'] == $logicId)
                return; // Already there
        }
        // TODO: What to do if mesg received does not match interrogated eq ?

        if (isset($eqKnownFromAbeille[$logicId]))
            $eqName = $eqKnownFromAbeille[$logicId];
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
        logMessage("", "Nouvel eq à interroger: '".$eqName."' (".$logicId.")");
    }

    /* Remove any pending messages from parser */
    function msgFromParserFlush() {
        global $queueKeyParserToLQI;

        $max_msg_size = 512;
        while (msg_receive($queueKeyParserToLQI, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT));
    }

    /* Treat request responses (804E) from parser.
       Returns: 0=OK, -1=fatal error, 1=timeout */
    function msgFromParser($eqIndex) {
        logMessage("", "msgFromParser(eqIndex=".$eqIndex.")");

        global $LQI;
        global $queueKeyParserToLQI;
        global $eqToInterrogate;
        global $eqKnownFromAbeille;
        global $objKnownFromAbeille;

        $timeout = 10; // 10sec (useful when there is unknown eq interrogation during LQI collect)
        for ($t = 0; $t < $timeout; ) {
            // logMessage("", "  Queue stat=".json_encode(msg_stat_queue($queueKeyParserToLQI)));
            if (msg_receive($queueKeyParserToLQI, 0, $msg_type, 1024, $msg_json, FALSE, MSG_IPC_NOWAIT, $error_code) == TRUE) {
                /* Message received. Let's check it is the expected one */
                $msg = json_decode($msg_json);
                if ($msg->Type != "804E") {
                    logMessage("", "  Unsupported message type (".$msg->Type.") => Ignored.");
                    continue;
                }
                if (hexdec($msg->StartIndex) != $eqToInterrogate[$eqIndex]['TableIndex']) {
                    /* Note: this case is due to too many identical 004E messages sent to eq
                       leading to several identical 804E answers */
                    logMessage("", "  Unexpected start index (".$msg->StartIndex.") => Ignored.");
                    continue;
                }
                break; // Valid message
            }

            if ($error_code == 42) { // No message
                sleep(1); // Sleep 1s
                $t += 1;
                continue;
            }

            /* It's an error */
            logMessage("", "  Error ".$error_code."/".posix_strerror($error_code));
            return -1;
        }
        if ($t >= $timeout) {
            logMessage("", "  Time-out !");
            return 1;
        }
        // Note: What to do if mesg received does not match interrogated eq ?
        //       This currently can't appear since requests are done sequentially
        //       so no risk to get an answer from an unexpected source.

        // logMessage("", "  Recu ".$msg_json);
        // $msg = json_decode($msg_json);
        // if ($msg->Type != "804E") {
        //     logMessage("", "  Message non LQI => Inattendu.");
        //     return -1;
        // }

        /* Message format reminder
            $msg = array(
                'Type' => '804E',
                'SrcAddr' => $SrcAddr,
                'TableEntries' => $NTableEntries,
                'TableListCount' => $NTableListCount,
                'StartIndex' => $StartIndex,
                'List' => $NList
                    $N = array(
                        "Addr"     => substr($payload, $j + 0, 4),
                        "ExtPANId" => substr($payload, $j + 4, 16),
                        "ExtAddr"  => substr($payload, $j + 20, 16),
                        "Depth"    => substr($payload, $j + 36, 2),
                        "LQI"      => substr($payload, $j + 38, 2),
                        "BitMap"   => substr($payload, $j + 40, 2)
                )
            ); */

        $tableEntries = $msg->TableEntries; // Total entries on interrogated eq
        $tableListCount = $msg->TableListCount; // Number of neighbours liste in msg
        $startIndex = $msg->StartIndex;
        $NList = $msg->List; // List of neighbours
        // logMessage("", "NList=".json_encode($NList));
        logMessage("", "  tableEntries=".$tableEntries.", tableListCount=".$tableListCount.", startIndex=".$startIndex);

        /* Updating collect infos for this coordinator/router */
        $eqToInterrogate[$eqIndex]['TableEntries'] = hexdec($tableEntries);
        $eqToInterrogate[$eqIndex]['TableIndex'] = hexdec($startIndex) + hexdec($tableListCount);

        $NE = $eqToInterrogate[$eqIndex]["LogicId"]; // Ex: 'Abeille1/Ruche', 'Abeille1/A3B4'

        $parameters = array();
        $parameters['NE'] = $NE; // Logical ID
        if (isset($eqKnownFromAbeille[$NE])) {
            $parameters['NE_Name'] = $eqKnownFromAbeille[$NE]; // Name
            $parameters['NE_Objet'] = $objKnownFromAbeille[$NE]; // Parent object
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
            if ($N->Addr == "0000") $N->Addr = "Ruche";

            $parameters['Voisine'] = $netName."/".$N->Addr;
            if (isset($eqKnownFromAbeille[$parameters['Voisine']])) {
                $parameters['Voisine_Name'] = $eqKnownFromAbeille[$parameters['Voisine']];
                $parameters['Voisine_Objet'] = $objKnownFromAbeille[$parameters['Voisine']];
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
                // For remove from zigbee feature (#1770)
                $kid = Abeille::byLogicalId($netName.'/'.$N->Addr, 'Abeille');
                if ($kid) { // Saving parent IEEE address
                    $kid->setConfiguration('parentIEEE', $parentIEEE);
                    $kid->save();
                } else
                    logMessage("", "  WARNING: Eq '".$netName."/".$N->Addr."' inconnu de Jeedom");
            } else { // $AttrType== 3
                $parameters['Type'] = "Unknown";
            }

            $AttrRel = ($Attr & 0b00110000) >> 4;
            if ($AttrRel == 0) {
                $parameters['Relationship'] = "Parent";
            } else if ($AttrRel == 1) {
                $parameters['Relationship'] = "Child";
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
        $msgAbeille = new MsgAbeille;
        $msgAbeille->message['topic'] = "Cmd".$dest."/Ruche/Management_LQI_request";
        $msgAbeille->message['payload'] = "address=" . $addr . "&StartIndex=" . $index;
        logMessage("", "msgToCmd: ".json_encode($msgAbeille));

        global $queueKeyLQIToCmd;
        if (msg_send($queueKeyLQIToCmd, priorityInterrogation, $msgAbeille, true, false) == FALSE) {
            logMessage('error', "msgToCmd: Impossible d'envoyer le message vers AbeilleCmd");
            return -1;
        }
        return 0;
    }

    /* Send 1 to several table requests thru AbeilleCmd to collect neighbour table entries.
       Returns: 0=OK, -1=ERROR (stops collect for current zigate), 1=timeout */
    function interrogateEq($netName, $addr, $eqIndex) {
        global $eqToInterrogate;

        while (TRUE) {
            $eq = $eqToInterrogate[$eqIndex]; // Read eq status
            msgToCmd($netName, $addr, sprintf("%'.02X", $eq['TableIndex']));
            usleep(200000); // Delay of 200ms to let response to come back
            $ret = msgFromParser($eqIndex);
            if ($ret == 1) {
                /* If time-out, cancel interrogation for current eq only */
                logMessage("", "Time-out => Abandon de l'interrogation de '".$eq['Name']."' (".$eq['Addr'].").");
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

    logSetConf(jeedom::getTmpFolder("Abeille")."/AbeilleLQI.log");
    logMessage("", ">>> Démarrage d'AbeilleLQI.");

    /* Note: depending on the way 'AbeilleLQI' is launched, arguments are not
       collected in the same way.
       URL => use $_GET[]
       Cmd line/shell => use $argv[] */
    if (isset($_GET['zigate'])) { // Zigate nb passed as URL ?
        $zgNb = $_GET['zigate'];
    } else if (isset($argv[1])) { // Zigate nb passed as args ?
        $zgNb = $argv[1];
    } else
        $zgNb = -1;
    if (($zgNb != -1) && (($zgNb < 1) or ($zgNb > 10))) {
        logMessage("", "  Mauvaise valeur de zigate => arret");
        exit;
    }

    if ($zgNb == -1) {
        logMessage("", "Demande d'interrogation de toutes les zigates actives");
        $zgStart = 1;
        $zgEnd = config::byKey('zigateNb', 'Abeille', '1', 1);
    } else {
        logMessage("", "Demande d'interrogation de la zigate ".$zgNb." seulement");
        $zgStart = $zgNb;
        $zgEnd = $zgNb;
    }

    // Collecting known equipments list
    logMessage("", "Equipements connus de Jeedom:");
    $eqLogics = eqLogic::byType('Abeille');
    $eqKnownFromAbeille = array();
    $objKnownFromAbeille = array();
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId();
        $eqKnownFromAbeille[$eqLogicId] = $eqLogic->getName();
        $eqParent = $eqLogic->getObject();
        if (!is_object($eqParent))
            $objName = "";
        else
            $objName = $eqParent->getName();
        $objKnownFromAbeille[$eqLogicId] = $objName;
        logMessage("", "  Eq='".$eqLogicId."', objname='".$objKnownFromAbeille[$eqLogicId]."'");
    }
    // logMessage("", "Objets connus de Jeedom: ".json_encode($objKnownFromAbeille));

    $queueKeyLQIToCmd    = msg_get_queue(queueKeyLQIToCmd);
    $queueKeyParserToLQI = msg_get_queue(queueKeyParserToLQI);
    msgFromParserFlush(); // Flush the queue if not empty

    for ($zgNb = $zgStart; $zgNb <= $zgEnd; $zgNb++) {
        if (config::byKey('AbeilleActiver'.$zgNb, 'Abeille', 'N') != 'Y') {
            logMessage("", "Zigate ".$zgNb." désactivée => Ignorée.");
            continue;
        }

        $netName = "Abeille".$zgNb; // Abeille network
        $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory
        $dataFile = $tmpDir."/AbeilleLQI_MapData".$netName.".json";
        $lockFile = $dataFile.".lock";
        if (file_exists($lockFile)) {
            $content = file_get_contents($lockFile);
            logMessage("", "Contenu ".$netName." lock: '".$content."'");
            if (substr($content, 0, 4) != "done") {
                exec("pgrep -a php | grep AbeilleLQI", $running);
                if (sizeof($running) != 0) {
                    echo 'ERROR: Collect already ongoing';
                    logMessage("", "Une collecte semble déja en cours (fichier lock présent) => nouvelle collecte interrompue");
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
            logMessage("", "Impossible d'écrire le fichier lock (".$lockFile.") => arret");
            exit;
        }

        $LQI = array(); // Result from interrogations
        $eqToInterrogate = array();
        newEqToInterrogate("Abeille".$zgNb."/Ruche");

        $done = 0;
        $eqIndex = 0; // Index of eq to interrogate
        $collectStatus = 0;
        while (TRUE) {
            $total = count($eqToInterrogate);
            logMessage("", "Zigate ".$zgNb.": progression ".$done."/".$total);

            $currentNeAddress = $eqToInterrogate[$eqIndex]['LogicId'];
            list( $netName, $addr ) = explode('/', $currentNeAddress);
            if ( $addr == "Ruche" ) { $addr = "0000"; }

            $NE = $currentNeAddress;

            $name = $eqKnownFromAbeille[$currentNeAddress];
            if (strlen($name) == 0) {
                $name = "Inconnu-" . $currentNeAddress;
            }

            logMessage("", "Interrogation de '".$name."' (".$addr.")");
            $nbwritten = file_put_contents($lockFile, "Analyse du réseau ".$netName.": ".$done."/".$total." => interrogation de '".$name."' (".$addr.")");
            if ($nbwritten < 1) {
                echo "ERROR: Can't write lock file";
                logMessage("", "Impossible d'écrire sur fichier de lock.");
                unlink($lockFile);
                exit;
            }

            $ret = interrogateEq($netName, $addr, $eqIndex);
            $done++;
            if ($ret == -1) {
                $collectStatus = -1; // Collect interrupted due to error
                logMessage("", "Collecte interrompue pour la zigate ".$zgNb." pour cause d'erreurs.");
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

    logMessage("", "<<< AbeilleLQI terminé.");
?>
