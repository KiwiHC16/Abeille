<?php

    /*
     * AbeilleParserClass
     *
     * - Read data from FIFO file (FIFO populated by AbeilleSerialRead)
     * - translate them into a understandable message,
     * - then publish them to Abeille
     *
     * @param argv
     *      argv contient le niveau de log. typical call: /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDeamon/lib/../AbeilleParser.php debug
     *  @return
     *
     */
    class AbeilleParser  {
        // public $queueKeyParserToAbeille = null; // Old communication path to Abeille
        // public $queueKeyParserToAbeille2 = null; // New communication path to Abeille
        // public $queueXToCmd = null;
        // public $parameters_info;
        // public $actionQueue; // queue of action to be done in Parser like config des NE ou get info des NE
        public $wakeUpQueue; // queue of command to be sent when the device wakes up.
        public $whoTalked;   // store the source of messages to see if any message are waiting for them in wakeUpQueue

        function __construct() {
            global $argv;

            /* Configuring log library to use 'logMessage()' */
            logSetConf("AbeilleParser.log", true);

            parserLog("debug", "AbeilleParser constructor", "AbeilleParserClass");
            // $this->parameters_info = AbeilleTools::getParameters();

            // Seems unused
            // $this->requestedlevel = '' ? 'none' : $argv[1];
            // $GLOBALS['requestedlevel'] = $this->requestedlevel ;

            // $abQueues = $GLOBALS['abQueues'];
            // $this->queueXToCmd          = msg_get_queue($abQueues["xToCmd"]["id"]);
            // $this->queueParserToCmdAck  = msg_get_queue($abQueues["parserToCmdAck"]["id"]);
            // $this->queueParserToLQI     = msg_get_queue($abQueues["parserToLQI"]["id"]);
            // $this->queueParserToRoutes  = msg_get_queue($abQueues["parserToRoutes"]["id"]);
        }

        /* Check NPDU status */
        function checkNpdu($net, $nPdu) {
            $zgId = substr($net, 7); // AbeilleX => X
            $nPdu = hexdec($nPdu); // Hex string to number

            if (!isset($GLOBALS['zigate'.$zgId]))
                $GLOBALS['zigate'.$zgId] = [];
            if (!isset($GLOBALS['zigate'.$zgId]['nPdu'])) {
                $GLOBALS['zigate'.$zgId]['nPdu'] = $nPdu;
                $GLOBALS['zigate'.$zgId]['nPduTime'] = time();
                return;
            }
            $curNpdu = $GLOBALS['zigate'.$zgId]['nPdu'];
            if ($nPdu != $curNpdu) { // Npdu increased or reduced
                $GLOBALS['zigate'.$zgId]['nPdu'] = $nPdu;
                $GLOBALS['zigate'.$zgId]['nPduTime'] = time();
                return;
            }
            // NDPU still the same
            if ($curNpdu == 0)
                return;
            $duration = time() - $GLOBALS['zigate'.$zgId]['nPduTime'];
            if ($duration < (4 * 60))
                return; // NPDU stable since less than 3 mins.

            parserLog('warning', '  Ndpu stuck at '.$curNpdu.' for more than 4mins => SW reset.');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/0000/zgSoftReset");
            $GLOBALS['zigate'.$zgId]['nPduTime'] = time(); // Reset timer
            // message::add("Abeille", "Erreur lors du changement de mode de la Zigate.", "");
        }

        // /* Send message to 'AbeilleCmd' thru 'xToCmd' queue */
        // function msgToCmd($prio, $topic, $payload = '') {
        //     $msg = array(
        //         'priority' => $prio,
        //         'topic' => $topic,
        //         'payload' => $payload
        //     );

        //     $errCode = 0;
        //     if (msg_send($this->queueXToCmd, 1, json_encode($msg), false, false, $errCode) == false) {
        //         parserLog("debug", "  ERROR: msgToCmd(): Can't write to 'queueXToCmd', error=".$errCode);
        //     }
        // }

        /* Send message to 'AbeilleCmd' thru 'xToCmd' queue */
        function msgToCmd2($prio, $msg) {
            $errCode = 0;
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
            global $queueXToCmd;
            if (msg_send($queueXToCmd, 1, $msgJson, false, false, $errCode) == false) {
                parserLog("debug", "  ERROR: msgToCmd2(): Can't write to 'queueXToCmd', error=".$errCode);
            }
        }

        /* Send message to 'AbeilleLQI'.
           Returns: true=ok, false=ERROR */
        function msgToLQICollector($msg) {
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
            global $queueParserToLQI;
            if (msg_send($queueParserToLQI, 1, $msgJson, false, false, $errCode) == true)
                return true;

            // parserLog("error", "  msgToLQICollector(): Impossible d'envoyer le msg vers AbeilleLQI (err ".$errCode.")");
            // Note: FW 0005-03A0 periodically generate LQI requests on its own.
            parserLog("debug", "  ERROR: msgToLQICollector(): msg_send err ".$errCode);
            return false;
        }

        /* Send message to 'AbeilleRoutes'.
           Returns: true=ok, false=ERROR */
        function msgToRoutingCollector($srcAddr, $tableEntries, $tableCount, $startIdx, $table) {
            $msg = array(
                'type' => 'routingTable',
                'srcAddr' => $srcAddr,
                'tableEntries' => $tableEntries,
                'tableListCount' => $tableCount,
                'startIdx' => $startIdx,
                'table' => $table
            );
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
            global $queueParserToRoutes;
            if (msg_send($queueParserToRoutes, 1, $msgJson, false, false, $errCode) == true)
                return true;

            // parserLog("error", "  msgToRoutingCollector(): Impossible d'envoyer le msg vers AbeilleRoutes (err ".$errCode.")");
            // Note: FW 0005-03A0 periodically generate route requests on its own.
            parserLog("debug", "  ERROR: msgToRoutingCollector(): msg_send err ".$errCode);
            return false;
        }

        /* Send msg to client if queue exists.
           Purpose is to push infos to client side page (assistant) when they arrive.
         */
        function msgToClient($msg) {
            if (!isset($GLOBALS['sendToCli']))
                return;
            if ($msg['net'] != $GLOBALS['sendToCli']['net'])
                return;

            if ($msg['type'] == "deviceAnnounce") {
                if ($msg['ieee'] != $GLOBALS['sendToCli']['ieee'])
                    return; // Not the correct device
                $GLOBALS['sendToCli']['addr'] = $msg['addr']; // Updating addr
            } else {
                if ($msg['addr'] != $GLOBALS['sendToCli']['addr'])
                    return; // Not the correct device
            }

            $abQueues = $GLOBALS['abQueues'];
            $queue = msg_get_queue($abQueues['parserToCli']['id']);
            if ($queue === false)
                return; // No queue
            /* Checking if queue is there alone. Client page might be closed */
            $status = msg_stat_queue($queue);
            if ($status['msg_qnum'] >= 4) {
                parserLog('debug', '  msgToClient(): Pending messages in queue => doing nothing');
                return;
            }

            $jsonMsg = json_encode($msg, JSON_UNESCAPED_SLASHES);
            $size = strlen($jsonMsg);
            $max = $abQueues['parserToCli']['max'];
            if ($size > $max) {
                parserLog("error", "msgToClient(): Message trop gros ignoré (taille=".$size.", max=".$max.")");
                return false;
            }
            if (msg_send($queue, 1, $jsonMsg, false, false, $errCode) == false) {
                parserLog("debug", "  msgToClient(): ERROR ".$errCode);
            } else
                parserLog("debug", "  msgToClient(): Sent ".$jsonMsg);
        }

        /* Check if message is a duplication of another one using SQN.
           This allows to filter-out messages duplication (Zigate FW issue that generate several messages for the same SQN).
           Returns: true if duplicate, else false
           Note: FCF is used for cases when Zigate is answering to message. Ex add group
            [13:13:00] Abeille1, Type=8002/Data indication, Status=00, ProfId=0104, ClustId=0004, SrcEP=01, DstEP=01, SrcAddrMode=02, SrcAddr=0000, DstAddrMode=02, DstAddr=0000
            [13:13:00]   FCF=11/Cluster-specific/Cli->Serv, SQN=05, cmd=00/AddGroup
            [13:13:00]   Unsupported cluster 0004 specific cmd 00 >>> ZIGATE DEALS WITH THIS MESSAGE !!
            [13:13:00] Abeille1, Type=8002/Data indication, Status=00, ProfId=0104, ClustId=0004, SrcEP=01, DstEP=01, SrcAddrMode=02, SrcAddr=0000, DstAddrMode=02, DstAddr=0000
            [13:13:00]   FCF=19/Cluster-specific/Serv->Cli, SQN=05, cmd=00/Add group response
            [13:13:00]   Duplicated message for SQN 05 => ignoring >>> WRONG !!
         */
        function isDuplicated($net, $addr, $fcf, $sqn) {
            if (!isset($GLOBALS['devices'][$net]))
                $GLOBALS['devices'][$net] = [];
            if (!isset($GLOBALS['devices'][$net][$addr]))
                newDevice($net, $addr);

            $eq = &$GLOBALS['devices'][$net][$addr]; // Get EQ by ref
            if (!isset($eq['sqnList']))
                $eq['sqnList'] = [];

            // parserLog('debug', '  sqnList='.json_encode($eq['sqnList']));

            /* The idea is to store SQN & recept time and ignore any matching SQN during the following 2sec */
            if (isset($eq['sqnList'][$sqn])) {
                if (($eq['sqnList'][$sqn]['fcf'] == $fcf) && ($eq['sqnList'][$sqn]['time'] + 2 > time())) {
                    parserLog('debug', '  Duplicated message for SQN '.$sqn.' => ignoring');
                    return true; // Consider duplicated msg
                }
            } else
                $eq['sqnList'][$sqn] = [];

            // Create or update SQN entry
            $eq['sqnList'][$sqn]['fcf'] = $fcf;
            $eq['sqnList'][$sqn]['time'] = time();
            return false;
        }

        /* Look for a model in official or user/custom devices directories.
           Returns: true if supported, else false */
        function findModel(&$eq, $by='modelId') {
            $ma = !isset($eq['zigbee']['manufId']) ? '' : (($eq['zigbee']['manufId'] === false) ? 'false' : "'".$eq['zigbee']['manufId']."'");
            $mo = !isset($eq['zigbee']['modelId']) ? '' : (($eq['zigbee']['modelId'] === false) ? 'false' : "'".$eq['zigbee']['modelId']."'");
            $lo = !isset($eq['zigbee']['location']) ? '' : (($eq['zigbee']['location'] === false) ? 'false' : "'".$eq['zigbee']['location']."'");
            parserLog('debug', "  findModel(), manufId=".$ma.", modelId=".$mo.", loc=".$lo);

            /* If forced model, do not attempt to auto-detect proper model unless forced model is not valid */
            if (isset($eq['eqModel']['modelForced']) && $eq['eqModel']['modelForced']) {
                $modelSource = ($eq['eqModel']['modelSource'] != '') ? $eq['eqModel']['modelSource'] : 'Abeille';
                $modelPath = isset($eq['eqModel']['modelPath']) ? $eq['eqModel']['modelPath'] : $eq['eqModel']['modelName'].'/'.$eq['eqModel']['modelName'].'.json';
                $modelSig = isset($eq['eqModel']['modelSig']) ? $eq['eqModel']['modelSig'] : $eq['eqModel']['modelName'];
                $fullPath = ($modelSource == "Abeille") ? modelsDir : modelsLocalDir;
                $fullPath .= $modelPath;
                if (is_file($fullPath)) {
                    parserLog('debug', "    Forced model ({$modelPath}) is still valid");
                    return;
                } else
                    parserLog('debug', "    Forced model ({$modelPath}) is NO LONGER valid");
            }

            /* Looking for corresponding JSON if supported device.
               - Look with '<modelId>_<manufacturer>' identifier
               - If not found, look with '<modelId>' identifier
               - And if still not found, use 'defaultUnknown'
             */
            $modelSig = ''; // Successful identifier (<modelId_manuf> or <modelId> or <location>)
            $modelSource = "Abeille"; // Default location

            if (isset($eq['zigbee']['modelId']) && ($eq['zigbee']['modelId'] !== false) && ($eq['zigbee']['modelId'] != '')) {
                if (isset($eq['zigbee']['manufId']) && ($eq['zigbee']['manufId'] !== false) && ($eq['zigbee']['manufId'] != '')) {
                    /* Search by modelId AND manufacturer */
                    $identifier = $eq['zigbee']['modelId'].'_'.$eq['zigbee']['manufId'];
                     if (isset($GLOBALS['customEqList'][$identifier])) {
                        $modelSig = $identifier;
                        $modelSource = "local";
                        parserLog('debug', "  EQ is supported as user/custom config with '".$identifier."' identifier");
                    } else if (isset($GLOBALS['supportedEqList'][$identifier])) {
                        $modelSig = $identifier;
                        parserLog('debug', "  EQ is supported with '".$identifier."' identifier");
                    }
                }
                if ($modelSig == '') {
                    /* Search by modelId */
                    $identifier = $eq['zigbee']['modelId'];
                     if (isset($GLOBALS['customEqList'][$identifier])) {
                        $modelSig = $identifier;
                        $modelSource = "local";
                        parserLog('debug', "  EQ is supported as user/custom config with '".$identifier."' identifier");
                    } else if (isset($GLOBALS['supportedEqList'][$identifier])) {
                        $modelSig = $identifier;
                        parserLog('debug', "  EQ is supported with '".$identifier."' identifier");
                    }
                }
            } else if (($eq['zigbee']['location'] !== false) && ($eq['zigbee']['location'] != '')) {
                /* Search by location */
                $identifier = $eq['zigbee']['location'];
                 if (isset($GLOBALS['customEqList'][$identifier])) {
                    $modelSig = $identifier;
                    $modelSource = "local";
                    parserLog('debug', "  EQ is supported as user/custom config with '".$identifier."' location identifier");
                } else if (isset($GLOBALS['supportedEqList'][$identifier])) {
                    $modelSig = $identifier;
                    parserLog('debug', "  EQ is supported with '".$identifier."' location identifier");
                }
            }

            if ($modelSig == '') {
                $eq['eqModel']['modelSig'] = "";
                $eq['eqModel']['modelName'] = "defaultUnknown";
                $eq['eqModel']['modelSource'] = "Abeille";
                parserLog('debug', "  EQ is UNsupported. 'defaultUnknown' config will be used");
                return false;
            }

            $eq['eqModel']['modelSig'] = $modelSig;
            if ($modelSource == "Abeille")
                $eq['eqModel']['modelName'] = $GLOBALS['supportedEqList'][$modelSig]['modelName'];
            else
                $eq['eqModel']['modelName'] = $GLOBALS['customEqList'][$modelSig]['modelName'];
            $eq['eqModel']['modelSource'] = $modelSource;
            parserLog('debug', "  ModelName='".$eq['eqModel']['modelName']."', Source='{$modelSource}'");
            return true;
        } // End findModel()

        /* Cached EQ infos reminder:
            $GLOBALS['devices'][<network>][<addr>] = array(
                'zigbee' => array(
                    'ieee' => $ieee,
                    'macCapa' => '', // MAC capa from device announce
                    'rxOnWhenIdle' => undef or true/false
                    'endPoints' => [], // End points
                    'modelId' => undef/false (unsupported)/'xx'
                    'manufId' => undef/false (unsupported)/'xx'
                    'location' => undef/false (unsupported)/'xx'
                ),
                'rejoin' => '', // Rejoin info from device announce
                'status' => 'identifying', // identifying, configuring, discovering, idle
                'time' => time(),
                'mainEp' => '', // Default EP = the one giving signature (modelId/manufId)

                'modelSig' => null (undef)/'' (unsupported)
                'modelName' => '', // JSON identifier
                'modelSource' => '', // JSON location ("Abeille"=default, or "local")
            );
            'status': Tcharp38/TODO: TO BE REVISITED. NOT clear enough

                identifying: req EP list + manufacturer + modelId with special cases support.
                configuring: execute cmds with 'execAtCreation' flag
                discovering: for unknown EQ
                idle: all actions ended
        */

        /* Called on device announce. */
        function deviceAnnounce($net, $addr, $ieee, $macCapa, $rejoin) {
            $eq = &getDevice($net, $addr, $ieee, $new); // By ref
            // 'status' set to 'identifying' if new device
            parserLog('debug', '  eq='.json_encode($eq, JSON_UNESCAPED_SLASHES));

            // Removing any pending cmd (if any) to device.
            // Note that is short addr change or equipment migrated, this should already be done
            if (!$new) {
                $msg = array(
                    'type' => 'clearPending',
                    'net' => $net,
                    'addr' => $addr,
                    'ieee' => $ieee
                );
                msgToCmdAck($msg);
            }

            if (isset($eq['customization']) && isset($eq['customization']['macCapa'])) {
                $eq['zigbee']['macCapa'] = $eq['customization']['macCapa'];
                parserLog('debug', "  'macCapa' customized: ".$macCapa." => ".$eq['zigbee']['macCapa']);
            } else
                $eq['zigbee']['macCapa'] = $macCapa;
            $ao = (hexdec($eq['zigbee']['macCapa']) >> 3) & 0b1;
            $eq['zigbee']['rxOnWhenIdle'] = $ao ? true : false;
            $eq['rejoin'] = $rejoin;

            /* Checking if it's not a too fast consecutive device announce.
                Note: Assuming 4sec max per phase */
            // if (($eq['status'] != 'idle') && ($eq['time'] + 4) > time()) {
            //     if ($eq['status'] == 'identifying')
            //         parserLog('debug', '  Device identification already ongoing');
            //     else if ($eq['status'] == 'discovering')
            //         parserLog('debug', '  Device discovering already ongoing');
            //     return; // Last step is not older than 4sec
            // }

            /* Starting identification phase */
            $eq['status'] = 'identifying';
            $eq['time'] = time();

            // if ($eq['epList'] != '') {
            if (isset($eq['zigbee']['endPoints'])) {
                /* 'endPoints' is already known => trig next step */
                $epList = "";
                foreach ($eq['zigbee']['endPoints'] as $epId => $ep) {
                    if ($epList != "")
                        $epList .= "/";
                    $epList .= $epId;
                }
                $updates = [];
                $updates['epList'] = $epList;
                $this->deviceUpdates($net, $addr, '', $updates);
                return;
            }

            /* Special trick for Xiaomi for which some devices (at least v1) do not answer to "Active EP request".
                Old sensor even not answer to manufacturer request. */
            // Tcharp38: Trick too dangerous. IEEE is NXP not Xiaomi, leading to issues on ZLinky for ex.
            // $xiaomi = (substr($ieee, 0, 9) == "00158D000") ? true : false;
            // if ($xiaomi) {
            //     parserLog('debug', '  Xiaomi specific identification.');
            //     $eq['zigbee']['manufId'] = 'LUMI';
            //     $eq['epList'] = "01";
            //     $eq['epFirst'] = "01";
            //     $this->deviceUpdate($net, $addr, 'epList', $eq['epList']);
            //     return;
            // }

            /* Default identification: need EP list.
               Tcharp38 note: Some devices may not answer to active endpoints request at all. */
            parserLog('debug', '  Requesting active end points list');
            msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/getActiveEndpoints");

            /* Special trick for NXP based devices.
               - Note: 00158D=Jennic Ltd.
               - Some of them (ex: old Xiaomi) do not answer to "Active EP request" and do not send modelIdentifier themself.
               - Worse: Sending "Active EP request" may kill the device (ex: lumi.sensor_switch, #2188) which no longer respond.
            */
            $nxp = (substr($ieee, 0, 9) == "00158D000") ? true : false;
            if ($nxp) {
                parserLog('debug', '  NXP based device. Requesting modelIdentifier from EP 01');
                msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/readAttribute", "ep=01&clustId=0000&attrId=0005");
            }
        }

        /* There are device infos updates (ex: endpoints, manufId, modelId, location, ...). */
        function deviceUpdates($net, $addr, $ep, $updates = []) {
            parserLog('debug', "  deviceUpdates({$net}, {$addr}, EP={$ep}, Upd=".json_encode($updates, JSON_UNESCAPED_SLASHES).")");
            if (isset($updates['ieee']))
                $ieee = $updates['ieee'];
            else
                $ieee = null;
            $eq = &getDevice($net, $addr, $ieee, $newDev); // By ref
            // 'status' set to 'identifying' if new device

            $abUpdates = []; // Updates for Abeille.class

            foreach ($updates as $updType => $value) {
                // Log only if relevant
                if ($updType && ($eq['status'] != 'idle')) {
                    $v = ($value === false) ? "false" : "'{$value}'";
                    parserLog('debug', "    '{$updType}'={$v}. Status=".$eq['status']);
                }

                /* Updating entry: 'epList', 'manufId', 'modelId' or 'location', 'ieee', 'bindingTableSize' */
                if ($updType == 'epList') { // Active end points response
                    $epArr = explode('/', $value);
                    foreach ($epArr as $epId2) {
                        if (!isset($eq['zigbee']['endPoints'][$epId2])) {
                            $eq['zigbee']['endPoints'][$epId2] = [];
                            $endPointsUpdated = true;
                        }
                    }
                    if (isset($endPointsUpdated)) {
                        $abUpdates["endPoints"] = $eq['zigbee']['endPoints'];
                    }
                }

                // Updates from simple descriptor response
                else if ($updType == 'profId') { // Application profile ID
                    if (!isset($eq['zigbee']['endPoints'][$ep]['profId'])) {
                        $eq['zigbee']['endPoints'][$ep]['profId'] = $value;
                        $abUpdates["profId"] = $value;
                    }
                } else if ($updType == 'devId') { // Application device ID
                    if (!isset($eq['zigbee']['endPoints'][$ep]['devId'])) {
                        $eq['zigbee']['endPoints'][$ep]['devId'] = $value;
                        $abUpdates["devId"] = $value;
                    }
                } else if ($updType == 'servClusters') { // Server clusters for $ep
                    if (!isset($eq['zigbee']['endPoints'][$ep]['servClusters'])) {
                        $eq['zigbee']['endPoints'][$ep]['servClusters'] = $value;
                        $abUpdates["servClusters"] = $value;
                    }
                }

                // Updates from cluster 0000
                else if (($updType == 'modelId') || ($updType == '0000-0005')) { // Model identifier
                    if (!isset($eq['zigbee']['endPoints'][$ep]) || !isset($eq['zigbee']['endPoints'][$ep]['modelId'])) {
                        $eq['zigbee']['endPoints'][$ep]['modelId'] = $value;
                        $abUpdates["modelId"] = $value;
                    }
                    if (!isset($eq['zigbee']['modelId']) || ($eq['zigbee']['modelId'] === false)) {
                        $eq['zigbee']['modelId'] = $value;
                        if ($eq['mainEp'] == '')
                            $eq['mainEp'] = $ep;
                    }
                } else if (($updType == 'manufId') || ($updType == '0000-0004')) { // Manufacturer name
                    if (!isset($eq['zigbee']['endPoints'][$ep]) || !isset($eq['zigbee']['endPoints'][$ep]['manufId'])) {
                        $eq['zigbee']['endPoints'][$ep]['manufId'] = $value;
                        $abUpdates["manufId"] = $value;
                    }
                    if (!isset($eq['zigbee']['manufId']) || ($eq['zigbee']['manufId'] === false)) {
                        $eq['zigbee']['manufId'] = $value;
                        if ($eq['mainEp'] == '')
                            $eq['mainEp'] = $ep;
                    }
                } else if (($updType == "location") || ($updType == "0000-0010")) { // Location
                    if (!isset($eq['zigbee']['endPoints'][$ep]) || !isset($eq['zigbee']['endPoints'][$ep]['location'])) {
                        $eq['zigbee']['endPoints'][$ep]['location'] = $value;
                        $abUpdates["location"] = $value;
                    }
                    if (!isset($eq['zigbee']['location']) || ($eq['zigbee']['location'] === false))
                        $eq['zigbee']['location'] = $value;
                } else if ($updType == '0000-0006') { // Cluster 0000, attrib 0006/DateCode
                    if (!isset($eq['zigbee']['endPoints'][$ep]['dateCode']) || ($eq['zigbee']['endPoints'][$ep]['dateCode'] !== $value)) {
                        $eq['zigbee']['endPoints'][$ep]['dateCode'] = $value;
                        $abUpdates["dateCode"] = $value;
                    }
                } else if ($updType == '0000-4000') { // Cluster 0000, attrib 4000/SWBuildID
                    if (!isset($eq['zigbee']['endPoints'][$ep]['swBuildId']) || ($eq['zigbee']['endPoints'][$ep]['swBuildId'] !== $value)) {
                        $eq['zigbee']['endPoints'][$ep]['swBuildId'] = $value;
                        $abUpdates["swBuildId"] = $value;
                    }
                }

                // ?
                else if ($updType == 'groups') { // Group membership for $ep
                    if (!isset($eq['zigbee']['groups']))
                        $eq['zigbee']['groups'] = [];
                    if (!isset($eq['zigbee']['groups'][$ep])) {
                        $eq['zigbee']['groups'][$ep] = $value;
                        // getGroupMembership also sent to Abeille
                    }
                } else if ($updType == 'logicalType') { // Node descriptor/logical type
                    if (!isset($eq['zigbee']['logicalType']) || ($eq['zigbee']['logicalType'] != $value)) {
                        $eq['zigbee']['logicalType'] = $value;
                        $abUpdates["logicalType"] = $value;
                    }
                } else if ($updType == 'macCapa') { // MAC capa flags
                    if (!isset($eq['zigbee']['macCapa']) || ($eq['zigbee']['macCapa'] != $value)) {
                        $eq['zigbee']['macCapa'] = $value;
                        $ao = (hexdec($eq['zigbee']['macCapa']) >> 3) & 0b1; // Always ON
                        $eq['zigbee']['rxOnWhenIdle'] = $ao ? true : false;
                        $abUpdates["macCapa"] = $value;
                    }
                } else if ($updType == 'rxOnWhenIdle') { // RX ON when idle flag only
                    if (!isset($eq['zigbee']['rxOnWhenIdle']) || ($eq['zigbee']['rxOnWhenIdle'] != $value)) {
                        $eq['zigbee']['rxOnWhenIdle'] = $value;
                        $abUpdates["rxOnWhenIdle"] = $value;
                    }
                } else if ($updType == 'manufCode') { // Manufacturer code
                    if (!isset($eq['zigbee']['manufCode']) || ($eq['zigbee']['manufCode'] != $value)) {
                        $eq['zigbee']['manufCode'] = $value;
                        $abUpdates["manufCode"] = $value;
                    }
                } else if (($updType == 'imageType') || ($updType == '0019-0008')) { // Cluster 0019/OTA, attr 0008/ImageType
                    if (!isset($eq['zigbee']['imageType']) || ($eq['zigbee']['imageType'] != $value)) {
                        $eq['zigbee']['imageType'] = $value;
                        $abUpdates["imageType"] = $value;
                    }
                } else if ($updType == 'ieee') {
                    $eq['zigbee'][$updType] = $value;
                } else // 'bindingTableSize'
                    $eq[$updType] = $value;
            } // End foreach($updates)

            // Any new info for Abeille.class ?
            // Note: Updates are transmitted only if IEEE address is already known to have unique identification
            // Note: if count($abUpdates) != 0 then there is a diff vs what was stored
            if ((count($abUpdates) != 0) && ($eq['zigbee']['ieee'] !== null)) {
                parserLog('debug', '    Updated eq='.json_encode($eq, JSON_UNESCAPED_SLASHES));
                $msg = array(
                    'type' => 'deviceUpdates',
                    'net' => $net,
                    'addr' => $addr,
                    'ep' => $ep,
                    'updates' => $abUpdates
                );
                msgToMainD($msg);
            }

            if (($eq['status'] != "unknown_ident") && ($eq['status'] != "identifying"))
                return false; // Not in any identification phase

            /* Identification phase is key but there are unfortunately several cases:
                - Standard case zigbee compliant:
                    - The device respond to "active endpoints request".
                    - Then gives 'manufId' and 'modelId'.
                - Special case (ex: old Xiaomi):
                    - The device does not respond neither to "active endpoints request" nor to 'manufId' BUT gives its 'modelId'.
                - Special case (ex: old Xiaomi):
                    - The device does not respond neither to "active endpoints request" nor to 'manufId' AND DOES NOT send 'modelId'.
                    - In that case no choice but read EP 01 attribute 0005 to identify.
                - Special case (ex: old Profalux):
                    - The device does not support 'modelId' or 'manufId' attributes but supports 'location'
            */

            // TODO: $ret to be revisited vs expected behavior on return
            if ($eq['status'] == "unknown_ident")
                $ret = true; // Dev is unknown to Jeedom
            else
                $ret = false;

            if (!isset($eq['zigbee']['ieee'])) {
                parserLog('debug', '    Requesting IEEE');
                msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/getIeeeAddress");
                return $ret;
            }
            // IEEE is available

            if (!isset($eq['zigbee']['endPoints'])) {
                parserLog('debug', '    Requesting active endpoints list');
                msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/getActiveEndpoints");
                return $ret;
            }
            // Endpoints list is available

            // What about server clusters & groups ?
            foreach ($eq['zigbee']['endPoints'] as $epId2 => $ep2) {
                if (($epId2 == "") || ($epId2 == "00"))
                    continue; // Invalid case seen some times

                if (!isset($ep2['servClusters'])) {
                    parserLog('debug', "    'servClusters' missing: Requesting simple descriptor from EP {$epId2}");
                    msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/getSimpleDescriptor", "ep=".$epId2);
                    // break; // To reduce requests on first missing descriptor
                    return $ret;
                } else if (strpos($ep2['servClusters'], '0004') !== false) {
                    // if (isset($ep2['groups']))
                    //     parserLog('debug', '    Groups='.json_encode($ep2['groups']));
                    if (!isset($eq['zigbee']['groups']) || !isset($eq['zigbee']['groups'][$epId2])) {
                        parserLog('debug', '    Requesting groups membership for EP '.$epId2);
                        msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/getGroupMembership", "ep=".$epId2);
                        return $ret; // To reduce requests on first missing groups membership
                    }
                }
            }
            // servClusters & groups are known

            if (!isset($eq['zigbee']['manufCode']) || ($eq['zigbee']['manufCode'] === null)) {
                parserLog('debug', "    Requesting node descriptor for 'manufCode'");
                msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/getNodeDescriptor");
                return $ret;
            }

            // Check if main signature is missing but available in EP
            // Note that this is unexpected. Some bug somewhere else leads to that.
            // TODO: Better to include this part in next 'if' code block
            if (!isset($eq['zigbee']['modelId']) || !isset($eq['zigbee']['manufId']) || !isset($eq['zigbee']['location'])) {
                foreach ($eq['zigbee']['endPoints'] as $epId2 => $ep2) {
                    if (!isset($eq['zigbee']['modelId']) && isset($ep2['modelId']))
                        $eq['zigbee']['modelId'] = $ep2['modelId'];
                    if (!isset($eq['zigbee']['manufId']) && isset($ep2['manufId']))
                        $eq['zigbee']['manufId'] = $ep2['manufId'];
                    if (!isset($eq['zigbee']['location']) && isset($ep2['location']))
                        $eq['zigbee']['location'] = $ep2['location'];

                    if (isset($eq['zigbee']['modelId']) || isset($eq['zigbee']['manufId']) || isset($eq['zigbee']['location']))
                        break;
                }
            }

            // IEEE & EP list are available. Any missing info to identify device ?
            if (!isset($eq['zigbee']['modelId']) || !isset($eq['zigbee']['manufId']) || !isset($eq['zigbee']['location'])) {
                // Interrogating all EP
                // Note: in most of the cases interrogating either first EP or EP 01 is ok but sometimes
                //   the device does not support cluster 0000 in these cases.
                //   Ex: Sonoff smart plug S26R2ZB (several EPs but the first one does not support modelId nor manufId)
                foreach ($eq['zigbee']['endPoints'] as $epId2 => $ep2) {
                    if (!isset($ep2['servClusters']))
                        continue; // No servClusters list
                    if (strpos($ep2['servClusters'], '0000') === false)
                        continue; // No cluster 0000/basic in this EP

                    $missing = '';
                    $missingTxt = '';
                    if (!isset($ep2['manufId']) && (!isset($ep2['modelId']) || ($ep2['modelId'] !== false))) {
                        $missing = '0004';
                        $missingTxt = 'manufId';
                    }
                    // if (!isset($ep2['modelId']) || ($ep2['modelId'] === null)) {
                    if (!isset($ep2['modelId'])) { // Unset, set to false, or set to proper value
                        if ($missing != '') {
                            $missing .= ',';
                            $missingTxt .= '/';
                        }
                        $missing .= '0005';
                        $missingTxt .= 'modelId';
                    }
                    /* Location might be required (ex: First Profalux Zigbee) when modelId is not supported */
                    if (!isset($ep2['modelId']) || (($ep2['modelId'] === false) && !isset($ep2['location']))) {
                        if ($missing != '') {
                            $missing .= ',';
                            $missingTxt .= '/';
                        }
                        $missing .= '0010';
                        $missingTxt .= 'location';
                    }
                    if ($missing != '') {
                        parserLog('debug', "    Requesting {$missingTxt} from EP {$epId2}");
                        msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/readAttribute", "ep=".$epId2."&clustId=0000&attrId=".$missing);
                        break; // Reducing requests on first missing stuff
                    }
                }
            }

            // Check if attr 0006 & 4000 from cluster 0000 are known
            foreach ($eq['zigbee']['endPoints'] as $epId2 => $ep2) {
                if (!isset($eq['zigbee']['endPoints'][$epId2]['servClusters']['0000']))
                    continue; // No basic cluster

                $missing = '';
                $missingTxt = '';
                if (!isset($eq['zigbee']['endPoints'][$ep]['dateCode'])) {
                    $missing .= '0006';
                    $missingTxt .= 'dateCode';
                }
                if (!isset($eq['zigbee']['endPoints'][$ep]['swBuildId'])) {
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '4000';
                    $missingTxt .= 'swBuildId';
                }
                if ($missing != '') {
                    parserLog('debug', "    Requesting {$missingTxt} from EP {$epId2}");
                    msgToCmd(PRIO_HIGH, "Cmd{$net}/{$addr}/readAttribute", "ep={$epId2}&clustId=0000&attrId={$missing}");
                    break; // Reducing requests on first missing stuff
                }
            }

            /* Trying to identify device model with currently known infos:
                - if modelId is supported
                    - search for JSON with 'modelId_manuf' then 'modelId'
                - else (modelId is not supported) if location is supported
                    - search for JSON with 'location'
            */
            if (!isset($eq['zigbee']['modelId']))
                return false; // 'modelId' must be set to a value or 'false' (unsupported).
            if ($eq['zigbee']['modelId'] !== false) {
                if (!isset($eq['zigbee']['manufId'])) {
                    /* Checking if device is supported without manufacturer attribute for those who do not respond to such request
                       but if not, default config is not accepted since manufacturer may not be arrived yet.
                       For Tuya case (model=TSxxxx), manufacturer is MANDATORY. */
                    if ((substr($eq['zigbee']['modelId'], 0, 2) == "TS") && (strlen($eq['zigbee']['modelId']) == 6))
                        return false; // Tuya case. Waiting for manufacturer to return.
                    if ($this->findModel($eq, 'modelId') === false) {
                        $eq['eqModel']['modelName'] = ''; // 'defaultUnknown' case not accepted there
                        return false;
                    }
                } else {
                    /* Manufacturer & modelId attributes returned */
                    $this->findModel($eq, 'modelId');
                }
            } else if ($eq['zigbee']['location'] === null) {
                return false; // Need value or false (unsupported)
            } else if ($eq['zigbee']['location'] !== false) {
                /* ModelId UNsupported. Trying with 'location' */
                $this->findModel($eq, 'location');
            } else { // Neither modelId nor location supported ?! Ouahhh...
                parserLog('debug', "    WARNING: Neither modelId nor location supported => using default config.");
                $eq['eqModel']['modelName'] = 'defaultUnknown';
                $eq['eqModel']['modelSource'] = "Abeille";
            }
            if ($eq['eqModel']['modelName'] == '') {
                // Still not identified
                if ($eq['status'] == 'unknown_ident')
                    return true;
                return false;
            }

            /* If device is identified, 'modelName' + 'modelSource' indicates which model to use. */
            // Tcharp38: If new dev announce of already known device, should we reconfigure it anyway ?
            // TODO: No reconfigure if rejoin = 02
            // Note: rejoin seems not reliable as generated by this bad NXP stack.
            if ($eq['eqModel']['modelName'] == 'defaultUnknown')
                $this->deviceDiscover($net, $addr);
            else if ($eq['status'] == 'identifying') {
                // Special case: Profalux v2: waiting for non empty binding table before binding zigate.
                //   If not, zigate binding would kill 'remote to curtain' binding.
                $profalux = (substr($eq['zigbee']['ieee'], 0, 6) == "20918A") ? true : false;
                if ($profalux && ($eq['zigbee']['modelId'] !== false) && ($eq['zigbee']['modelId'] !== 'MAI-ZTS')) {
                    if (!isset($eq['bindingTableSize'])) {
                        parserLog('debug', '    Profalux v2: Requesting binding table size.');
                        msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/getBindingTable", "");
                        return false; // Remote still not binded with curtain
                    }
                    if ($eq['bindingTableSize'] == 0) {
                        parserLog('debug', '    Profalux v2: Waiting remote to be binded.');
                        msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/getBindingTable", "");
                        return false; // Remote still not binded with curtain
                    }
                    parserLog('debug', '    Profalux v2: Remote binded. Let\'s configure.');
                }

                $this->deviceConfigure($net, $addr);
            } else // status==unknown_ident
                $this->deviceCreate($net, $addr);
            return false;
        } // End deviceUpdates()

        /* Device has been identified and must be configured.
           Go thru EQ commands and execute all those marked 'execAtCreation' */
        function deviceConfigure($net, $addr) {
            $eq = &$GLOBALS['devices'][$net][$addr];
            parserLog('debug', "  deviceConfigure(".$net.", ".$addr.", ModelName=".$eq['eqModel']['modelName'].")");

            // Read JSON to get list of commands to execute
            if (isset($eq['eqModel']['modelPath']))
                $modelPath = $eq['eqModel']['modelPath'];
            else
                $modelPath = $eq['eqModel']['modelName']."/".$eq['eqModel']['modelName'].".json";
            $eqModel = getDeviceModel($eq['eqModel']['modelSource'], $modelPath, $eq['eqModel']['modelName']);
            if ($eqModel === false)
                return;

            $eq['status'] = 'configuring';
            if (isset($eqModel['customization'])) {
                $eq['customization'] = $eqModel['customization'];
                if (isset($eq['customization']['macCapa'])) {
                    parserLog('debug', '  Customization: updating macCapa '.$eq['zigbee']['macCapa'].' => '.$eq['customization']['macCapa']);
                    $eq['zigbee']['macCapa'] = $eq['customization']['macCapa'];
                }
            } else
                $eq['customization'] = null;
            // if (isset($eqModel['tuyaEF00']))
            //     $eq['tuyaEF00'] = $eqModel['tuyaEF00'];
            // else
            //     $eq['tuyaEF00'] = null;
            // Really required here ?
            if (isset($eqModel['private']))
                $eq['private'] = $eqModel['private'];
            else
                $eq['private'] = null;

            /* Config ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                // 'src' => 'parser',
                'type' => 'eqAnnounce',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $eq['zigbee']['ieee'],
                'ep' => $eq['mainEp'],
                'modelId' => $eq['zigbee']['modelId'],
                'manufId' => isset($eq['zigbee']['manufId']) ? $eq['zigbee']['manufId'] : '',
                'modelName' => $eq['eqModel']['modelName'],
                'modelSource' => $eq['eqModel']['modelSource'], // "Abeille" or "local"
                'macCapa' => $eq['zigbee']['macCapa'],
                'time' => time()
            );
            if (isset($eq['eqModel']['modelPath']))
                $msg['modelPath'] = $eq['eqModel']['modelPath'];
            msgToMainD($msg);

            if (!isset($eqModel['commands'])) {
                parserLog('debug', "    No cmds for configuration in JSON model.");
            } else {
                parserLog('debug', "    Configuring device.");
                $msg = array(
                    'type' => 'configureDevice',
                    'net' => $net,
                    'addr' => $addr,
                    'modelSource' => $eq['eqModel']['modelSource'],
                    'modelPath' => $modelPath,
                    'modelName' => $eq['eqModel']['modelName']
                );
                $this->msgToCmd2(PRIO_NORM, $msg);

                // $cmds = $eqModel['commands'];

                // parserLog('debug', "    cmds=".json_encode($cmds));
                // foreach ($cmds as $cmdJName => $cmd) {
                //     if (!isset($cmd['configuration']))
                //         continue; // No 'configuration' section then no 'execAtCreation'
                //     $c = $cmd['configuration'];
                //     if (!isset($c['execAtCreation']))
                //         continue;

                //     if (isset($c['execAtCreationDelay']))
                //         $delay = $c['execAtCreationDelay'];
                //     else
                //         $delay = 0;
                //     parserLog('debug', "    exec cmd '".$cmdJName."' with delay ".$delay);
                //     $topic = $c['topic'];
                //     $request = $c['request'];
                //     // TODO: #EP# defaulted to first EP but should be
                //     //       defined in cmd use if different target EP
                //     // $request = str_ireplace('#EP#', $eq['epFirst'], $request);
                //     $request = str_ireplace('#EP#', $eq['mainEp'], $request);
                //     $request = str_ireplace('#addrIEEE#', $eq['zigbee']['ieee'], $request);
                //     $request = str_ireplace('#IEEE#', $eq['zigbee']['ieee'], $request);
                //     $zgId = substr($net, 7); // 'AbeilleX' => 'X'
                //     $request = str_ireplace('#ZiGateIEEE#', $GLOBALS['zigate'.$zgId]['ieee'], $request);
                //     parserLog('debug', '      topic='.$topic.", request='".$request."'");
                //     if ($delay == 0)
                //         msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/".$topic, $request);
                //     else {
                //         $delay = time() + $delay;
                //         msgToCmd(PRIO_NORM, "TempoCmd{$net}/{$addr}/".$topic.'&time='.$delay, $request);
                //     }
                // }

                // TODO: WORK ONGOING
                // For each 'info', attempting to read corresponding cluster/attribute
                // Note: How to handle non standard zigbee attributes (ex: Tuya) ?
                // Note: This part should be done in Abeille.class once createDevice() is completed.
                // foreach ($cmds as $cmdJName => $cmd) {
                //     if ($cmd['type'] != 'info')
                //         continue; // Not 'info'
                // }
            }

            // TODO: Tcharp38: 'idle' state might be too early since execAtCreation commands might not be completed yet
            $eq['status'] = 'idle';
        }

        /* Device has been identified and must be created in Jeedom.
           This is a phantom device. */
        function deviceCreate($net, $addr) {
            $eq = &$GLOBALS['devices'][$net][$addr];
            parserLog('debug', "  deviceCreate(".$net.", ".$addr.", ModelName=".$eq['eqModel']['modelName'].")");

            /* Config ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                // 'src' => 'parser',
                'type' => 'eqAnnounce',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $eq['zigbee']['ieee'],
                // 'ep' => $eq['epFirst'],
                'ep' => $eq['mainEp'],
                'modelId' => $eq['zigbee']['modelId'],
                'manufId' => $eq['zigbee']['manufId'],
                'modelName' => $eq['eqModel']['modelName'],
                'modelSource' => $eq['eqModel']['modelSource'], // "Abeille" or "local"
                'macCapa' => $eq['zigbee']['macCapa'],
                'time' => time()
            );
            msgToMainD($msg);

            // TODO: Tcharp38: 'idle' state might be too early since execAtCreation commands might not be completed yet
            $eq['status'] = 'idle';
        }

        /* Unknown EQ. Attempting to discover it. */
        function deviceDiscover($net, $addr) {
            parserLog('debug', "  deviceDiscover()");

            // $eq = &$GLOBALS['devices'][$net][$addr];
            $eq = &getDevice($net, $addr); // Get device by ref
            $eq['status'] = 'discovering';

            parserLog('debug', '  eq='.json_encode($eq, JSON_UNESCAPED_SLASHES));

            /* EQ is unsupported. Need to interrogate it to find main supported functions */
            // $epArr = explode('/', $eq['epList']);
            // foreach ($epArr as $epId) {
            //     $eq['zigbee']['endPoints'][$epId] = [];
            //     parserLog('debug', '  Requesting simple descriptor for EP '.$epId);
            //     msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/getSimpleDescriptor", 'ep='.$epId);
            // }
            foreach ($eq['zigbee']['endPoints'] as $epId => $ep) {
                $eq['discovery']['endPoints'][$epId] = [];
                parserLog('debug', '  Requesting simple descriptor for EP '.$epId);
                msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/getSimpleDescriptor", 'ep='.$epId);
            }

            /* Discover ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                // 'src' => 'parser',
                'type' => 'eqAnnounce',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $eq['zigbee']['ieee'],
                // 'ep' => $eq['epFirst'],
                'ep' => $eq['mainEp'],
                'modelId' => $eq['zigbee']['modelId'], // Zigbee model id (cluster 0000)
                'manufId' => $eq['zigbee']['manufId'], // Zigbee manuf id (cluster 0000)
                'modelSig' => $eq['eqModel']['modelSig'], // Signature used for model identification
                'modelName' => $eq['eqModel']['modelName'],
                'modelSource' => $eq['eqModel']['modelSource'], // "Abeille" or "local"
                'macCapa' => $eq['zigbee']['macCapa'],
                'time' => time()
            );
            msgToMainD($msg);
        }

        /* Update received during discovering process */
        function discoverUpdate($net, $addr, $ep, $updType, $val1 = null, $val2 = null, $val3 = null) {
            if (!isset($GLOBALS['devices']))
                return; // No dev announce before
            if (!isset($GLOBALS['devices'][$net]))
                return; // No dev announce before
            if (!isset($GLOBALS['devices'][$net][$addr]))
                return; // No dev announce before

            parserLog('debug', "  discoverUpdate(".$net.", ".$addr.", ".$updType.")");
            $eq = &$GLOBALS['devices'][$net][$addr]; // By ref
            if (!isset($eq['discovery']))
                $eq['discovery'] = [];
            $discovery = &$eq['discovery'];
            // parserLog('debug', '    zigbee BEFORE='.json_encode($discovery));

            // List of clusters
            if ($updType == "SimpleDescriptorResponse") {
                $discovery['endPoints'][$ep]['status'] = $val1;
                if ($val1 != "00")
                    $discovery['endPoints'][$ep]['statusMsg'] = $val2;
                else {
                    foreach($val2['servClusters'] as $clustId => $clust) {
                        if (isset($discovery['endPoints'][$ep]['servClusters'][$clustId]['attributes']))
                            continue;
                        $discovery['endPoints'][$ep]['servClusters'][$clustId]['attributes'] = $clust;
                    }
                    foreach($val2['cliClusters'] as $clustId => $clust) {
                        if (isset($discovery['endPoints'][$ep]['cliClusters'][$clustId]['attributes']))
                            continue;
                        $discovery['endPoints'][$ep]['cliClusters'][$clustId]['attributes'] = $clust;
                    }

                    /* Requesting list of supported attributes */
                    foreach ($val2['servClusters'] as $clustId => $clust) {
                        msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/discoverAttributes", "ep=".$ep.'&clustId='.$clustId.'&dir=00&startAttrId=0000&maxAttrId=FF');
                    }
                    foreach ($val2['cliClusters'] as $clustId => $clust) {
                        msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/discoverAttributes", "ep=".$ep.'&clustId='.$clustId.'&dir=01&startAttrId=0000&maxAttrId=FF');
                    }
                }
            }

            // List of attributes
            else if ($updType == "DiscoverAttributesResponse") {
                $clustId = $val1;
                parserLog('debug', "    clustId=".$clustId.", isServer=".$val2);
                if ($val2) // val2 == isServer, numeric
                    $clust = &$discovery['endPoints'][$ep]['servClusters'][$clustId];
                else
                    $clust = &$discovery['endPoints'][$ep]['cliClusters'][$clustId];
                if (!isset($clust['attributes']))
                    $clust['attributes'] = [];
                $missingAttr = ''; // List of attributes whose value is missing.
                $missingAttrNb = 0;
                foreach ($val3 as $attrId => $attr) {
                    $clust['attributes'][$attrId] = [];
                    if (isset($clust['attributes'][$attrId]['value']))
                        continue;

                    if ($missingAttrNb != 0)
                        $missingAttr .= ",";
                    $missingAttr .= $attrId;
                    $missingAttrNb++;
                    if ($missingAttrNb < 4)
                        continue;

                    msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/readAttribute", "ep=".$ep."&clustId=".$clustId."&attrId=".$missingAttr);
                    $missingAttr = '';
                    $missingAttrNb = 0;
                }
                if ($missingAttrNb != 0)
                    msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/readAttribute", "ep=".$ep."&clustId=".$clustId."&attrId=".$missingAttr);
            }

            // discoverUpdate($dest, $srcAddr, $srcEp, 'DiscoverAttributesExtResponse', $clustId, $isServer, $attributes);
            else if ($updType == "DiscoverAttributesExtResponse") {
                $clustId = $val1;
                parserLog('debug', "    clustId=".$clustId.", isServer=".$val2);
                if ($val2) // val2 == isServer, numeric
                    $clust = &$discovery['endPoints'][$ep]['servClusters'][$clustId];
                else
                    $clust = &$discovery['endPoints'][$ep]['cliClusters'][$clustId];
                if (!isset($clust['attributes']))
                    $clust['attributes'] = [];
                foreach ($val3 as $attrId => $attr) {
                    $clust['attributes'][$attrId] = [];
                    if (isset($attr['dataType']))
                        $clust['attributes'][$attrId]['dataType'] = $attr['dataType'];
                    if (isset($attr['access']))
                        $clust['attributes'][$attrId]['access'] = $attr['access'];
                    if (!isset($clust['attributes'][$attrId]['value']))
                        msgToCmd(PRIO_NORM, "Cmd{$net}/{$addr}/readAttribute", "ep=".$ep."&clustId=".$clustId."&attrId=".$attrId);
                }
            }

            // discoverUpdate($dest, $srcAddr, $srcEp, 'ReadAttributesResponse', $clustId, $isServer, $attributes);
            else if ($updType == "ReadAttributesResponse") {
                $clustId = $val1;
                parserLog('debug', "    clustId=".$clustId.", isServer=".$val2);
                if ($val2) // val2 == isServer, numeric
                    $clust = &$discovery['endPoints'][$ep]['servClusters'][$clustId];
                else
                    $clust = &$discovery['endPoints'][$ep]['cliClusters'][$clustId];
                parserLog('debug', "    attributes=".json_encode($val3, JSON_UNESCAPED_SLASHES));
                foreach ($val3 as $attrId => $attr) {
                    if (!isset($clust['attributes'][$attrId]))
                        $clust['attributes'][$attrId] = [];
                    if (isset($attr['dataType']))
                        $clust['attributes'][$attrId]['dataType'] = $attr['dataType'];
                    $clust['attributes'][$attrId]['value'] = $attr['value'];
                }
            }

            parserLog('debug', '    discovery='.json_encode($discovery, JSON_UNESCAPED_SLASHES));

            // Saving 'discovery.json' in local (unsupported yet) devices
            $jsonId = $eq['zigbee']['modelId'].'_'.$eq['zigbee']['manufId'];
            // parserLog('debug', '    jsonId='.$jsonId);
            $fullPath = __DIR__."/../config/devices_local/".$jsonId;
            // parserLog('debug', '    fullPath='.$fullPath);
            if (!file_exists($fullPath))
                if (mkdir($fullPath) === false) {
                    parserLog('error', "Impossible de créer le répertoire 'devices_local/".$jsonId."'");
                    return;
                }
            $fullPath .= '/discovery.json';
            $json = json_encode($discovery, JSON_PRETTY_PRINT);
            if (file_put_contents($fullPath, $json) === false)
                parserLog('error', "Impossible d'écrire dans 'devices_local/".$jsonId."/discovery.json'");
        }

        /* Returns true if net/addr EQ is in "discovering" state, else false */
        function discoveringState($net, $addr) {
            if (!isset($GLOBALS['devices']))
                return false;
            if (!isset($GLOBALS['devices'][$net]))
                return false;
            if (!isset($GLOBALS['devices'][$net][$addr]))
                return false;
            $eq = $GLOBALS['devices'][$net][$addr];
            if ($eq['status'] != 'discovering')
                return false;
            return true;
        }

        /* Clean modelIdentifier, removing some unwanted chars.
           Note: Stops on first null char but so far we don't take care about encoding.
           Ex: 53494E2D342D322D3230002000000014000000CCCB0020B1020100900000003D = 'SIN-4-2-20        ÌË  ±    ='
         */
        function cleanModelId($hexString) {
            //remove all space in names for easier filename handling
            // $hexString = str_replace(' ', '', $hexString);

            // On enleve le / comme par exemple le nom des equipements Legrand
            // $hexString = str_replace('/', '', $hexString);

            // On enleve le * Ampoules GU10 Philips #1778
            // $hexString = str_replace('*', '', $hexString);

            // On enleve les 0x00 comme par exemple le nom des equipements Legrand
            // $hexString = str_replace("\0", '', $hexString);

            $m = '';
            for ($i = 0; $i < strlen($hexString); $i += 2) {
                $in = substr($hexString, $i, 2);
                $in2 = hexdec($in);
// parserLog('debug', 'LA'.$i."='".$in."' => hexdec=".$in2);
                if ($in2 == 0) // null char
                    break; // Assuming everything bad after

                $in2 = pack("H*", $in); // Convert to char
                if ($in2 <= ' ')
                    continue; // Ignore any control char & space
                if ($in2 == '/')
                    continue; // Ignore '/'
                if ($in2 == '*')
                    continue; // Ignore '*'
                // if ($in == '.')
                //     continue; // Ignore '.'
                $m = $m.$in2;
            }

            // Le capteur de temperature rond V1 xiaomi envoie spontanement son nom: ->lumi.sensor_ht<- mais envoie ->lumi.sens<- sur un getName
            if ($m == "lumi.sens") $m = "lumi.sensor_ht";

            // if ($hexString == "TRADFRI Signal Repeater") $hexString = "TRADFRI signal repeater";

            if ($m == "lumi.sensor_swit") $m = "lumi.sensor_switch.aq3";

            // Work-around: getName = lumi.sensor_86sw2Un avec probablement des caractere cachés alors que lorsqu'il envoie son nom spontanement c'est lumi.sensor_86sw2 ou l inverse, je ne sais plus
            if (strpos($m, "sensor_86sw2") > 2) { $m="lumi.sensor_86sw2"; }

            if (!strncasecmp($m, "lumi.", 5))
                $m = substr($m, 5); // Remove leading "lumi." case insensitive

            return $m;
        }

        /* Clean manufacturer ID, removing some unwanted chars.
           Note: $manufId = string
           Ex: 4C49564F4C4F00105449303030312020 = 'LIVOLO TI0001  '
         */
        function cleanManufId($manufId) {
            // $manufId = str_replace(' ', '', $manufId); // Remove spaces
            // $manufId = str_replace('/', '', $manufId); // Remove '/'
            // $manufId = str_replace("\0", '', $manufId);
            $m = '';
            for ($i = $j = 0; $i < strlen($manufId); $i++) {
                $in = substr($manufId, $i, 1);
                if ($in <= ' ')
                    continue; // Ignore any control char & space
                if ($in == '/')
                    continue; // Ignore '/'
                if ($in == '.')
                    continue; // Ignore '.'
                $m = $m.$in;
            }
            return $m;
        }

        // function hex2str($hex)   {
        //     $str = '';
        //     for ($i = 0; $i < strlen($hex); $i += 2) {
        //         $str .= chr(hexdec(substr($hex, $i, 2)));
        //     }

        //     return $str;
        // }

        // // Fonction dupliquée dans Abeille.
        // public function volt2pourcent($voltage) {
        //     $max = 3.135;
        //     $min = 2.8;
        //     if ( $voltage/1000 > $max ) {
        //         parserLog('debug', '  Voltage remonte par le device a plus de '.$max.'V. Je retourne 100%.' );
        //         return 100;
        //     }
        //     if ( $voltage/1000 < $min ) {
        //         parserLog('debug', '  Voltage remonte par le device a moins de '.$min.'V. Je retourne 0%.' );
        //         return 0;
        //     }
        //     return round(100-((($max-($voltage/1000))/($max-$min))*100));
        // }

        // /* Zigbee type 0x10 to string */
        // function convBoolToString($value) {
        //     if (hexdec($value) == 0)
        //         return "0";
        //     return "1"; // Any value != 0 means TRUE
        // }

        // /* Zigbee type 0x39 to string */
        // function convSingleToString($value) {
        //   return unpack('f', pack('H*', $value ))[1];
        // }

        // function convUint8ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint16ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint24ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint32ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint40ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint48ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint56ToString($value) {
        //     return base_convert($value, 16, 10);
        // }
        // function convUint64ToString($value) {
        //     return base_convert($value, 16, 10);
        // }

        // /* Zigbee types 0x28..0x2f to string */
        // function convInt8ToString($value) {
        //     $num = hexdec($value);
        //     if ($num > 0x7f) // is negative
        //         $num -= 0x100;
        //     return sprintf("%d", $num);
        // }
        // function convInt16ToString($value) {
        //     $num = hexdec($value);
        //     if ($num > 0x7fff) // is negative
        //         $num -= 0x10000;
        //     return sprintf("%d", $num);
        // }
        // function convInt24ToString($value) {
        //     $num = hexdec($value);
        //     if ($num > 0x7fffff) // is negative
        //         $num -= 0x1000000;
        //     return sprintf("%d", $num);
        // }
        // function convInt32ToString($value) {
        //     $num = hexdec($value);
        //     if ($num > 0x7fffffff) // is negative
        //         $num -= 0x100000000;
        //     $conv = sprintf("%d", $num);
        //     // parserLog('debug', 'convInt32ToString value='.$value.', conv='.$conv );
        //     return $conv;
        // }

        // /* New generic function to convert hex string to uintX/intX */
        // function convHexToNumber($hexValue, $zbType) {
        //     $num = hexdec($hexValue);
        //     switch ($zbType) {
        //     case "29": // int16
        //         if ($num > 0x7fff) // is negative
        //         $num -= 0x10000;
        //         break;
        //     default:
        //         parserLog('debug', "convHexToNumber(): Unsupported type ".$zbType);
        //         return 0;
        //     }
        //     return $num;
        // }

        // static function hexTo32Float($strHex) {
        //     $v = hexdec($strHex);
        //     $x = ($v & ((1 << 23) - 1)) + (1 << 23) * ($v >> 31 | 1);
        //     $exp = ($v >> 23 & 0xFF) - 127;
        //     return $x * pow(2, $exp - 23);
        // }

        // Convert hex string to single or double precision
        // Useful: https://www.h-schmidt.net/FloatConverter/IEEE754.html
        // Useful: https://cs.lmu.edu/~ray/demos/ieee754.html
        static function hexValueToFloat($valueHex, $single = true) {
            // Don't try to use own function. Better to use PHP implementation for efficiency
            $v = pack('H*', $valueHex);
            if ($single)
                $val = unpack('G', $v);
            else
                $val = unpack('E', $v);

            return $val[1];
        }

        /* Convert hex string to proper data type.
           'iHs' = input data (hexa string format)
           'dataType' = data type
           'raw' = true if input is raw string (need reordering), else false
           'dataSize' = data size required form some attributes (ex: 41/42) if 'raw' == 'false'.
           'oSize' = size of 'iHs' extracted part
           'oHs' is the extracted & reordered hex string value
           Returns value according to type or false if error. */
        static function decodeDataType($iHs, $dataType, $raw, $dataSize, &$oSize = null, &$oHs = null) {
            $reorder = $raw; // Data reverse required if raw input mode (decode8002)

            // Compute value size according to data type
            switch ($dataType) {
            case "10": // Boolean
            case "18": // 8-bit bitmap
            case "20": // Uint8
            case "28": // Int8
            case "30": // enum8
                $dataSize = 1;
                break;
            case "19": // map16
            case "21": // Uint16
            case "29": // Int16
            case "31": // enum16
                $dataSize = 2;
                break;
            case "0A": // Discrete: 24-bit data
            case "1A": // map24
            case "22": // Uint24
            case "2A": // Int24
                $dataSize = 3;
                break;
            case "1B": // map32
            case "23": // Uint32
            case "2B": // Int32
            case "39": // Single precision
            case "E0": // Time analog: ToD
            case "E1": // Time analog: Date
            case "E2": // Time analog: UTC
                $dataSize = 4;
                break;
            case "24": // Uint40
            case "2C": // Int40
                $dataSize = 5;
                break;
            case "25": // Uint48
            case "2D": // Int48
                $dataSize = 6;
                break;
            case "26": // Uint56
            case "2E": // Int56
                $dataSize = 7;
                break;
            case "27": // Uint64
            case "2F": // Int64
            case "3A": // Double precision
            case "F0": // IEEE addr
                $dataSize = 8;
                break;
            case "F1": // 128bits key
                $dataSize = 16;
                break;
            case "41": // String discrete: octstr
            case "42": // String discrete: string
                // parserLog('debug', "  iHs=".$iHs);
                if ($raw) {
                    $dataSize = hexdec(substr($iHs, 0, 2));
                    $iHs = substr($iHs, 2); // Skip header byte
                    $reorder = false; // Seems no reordering required.
                } // else $dataSize provided from caller
                break;
            default: // All other types, copy input to output as hex string.
                // parserLog('debug', "  decodeDataType() WARNING: Unsupported type ".$dataType);
                $dataSize = strlen($iHs) / 2;
                break;
            }

            // Updating oSize
            if ((($dataType == '41') || ($dataType == '42')) && $raw)
                $oSize = $dataSize + 1;
            else
                $oSize = $dataSize;

            // Checking size
            $l = strlen($iHs);
            if ($l < (2 * $dataSize)) {
                parserLog('debug', "  decodeDataType(type=".$dataType.") ERROR: Data too short (got=".($l/2)." B, exp=".$dataSize." B)");
                // return false;
            }

            // 'hs' is now reduced to proper size
            $hs = substr($iHs, 0, $dataSize * 2); // Truncate in case something unexpected after
            // Reordering raw bytes
            if ($reorder && ($dataSize > 1))
                $hs = AbeilleTools::reverseHex($hs);
            // parserLog('debug', "  decodeDataType(): size=".$dataSize.", hexString=".$hexString." => hs=".$hs);
            $oHs = $hs;

            // Computing value
            switch ($dataType) {
            case "10": // Boolean
            case "18": // 8-bit bitmap
            case "19": // map16
            case "1A": // map24
            case "1B": // map32
            case "20": // Uint8
            case "21": // Uint16
            case "22": // Uint24
            case "23": // Uint32
            case "24": // Uint40
            case "25": // Uint48
            case "26": // Uint56
            case "27": // Uint64
            case "30": // enum8
            case "31": // enum16
            case "E0": // Time analog: ToD
            case "E1": // Time analog: Date
            case "E2": // Time analog: UTC
                $value = hexdec($hs); // Convert to number
                break;
            case "28": // int8
                $value = hexdec($hs);
                if ($value > 0x7f) // is negative
                    $value -= 0x100;
                break;
            case "29": // int16
                $value = hexdec($hs);
                if ($value > 0x7fff) // is negative
                    $value -= 0x10000;
                break;
            case "2A": // int24
                $value = hexdec($hs);
                if ($value > 0x7fffff) // is negative
                    $value -= 0x1000000;
                break;
            case "2B": // int32
                $value = hexdec($hs);
                if ($value > 0x7fffffff) // is negative
                    $value -= 0x100000000;
                break;
            case "39": // Single precision
                $value =  AbeilleParser::hexValueToFloat($hs);
                break;
            case "3A": // Double precision
                $value =  AbeilleParser::hexValueToFloat($hs, true);
                break;
            case "41": // String discrete: octstr
            case "F0": // IEEE addr
            case "F1": // 128bits key
                $value = $hs; // No conversion. Copy extracted string.
                break;
            case "42": // String discrete: string
                // Tcharp38: How to deal with encoding ?
                $value = pack("H*", $hs);
                // Some string leads to strange result and strange behavior if returned as it is.
                // Ex: "87EC69C712"
                if (json_encode($value) == "")
                    $value = "?";
                break;
            default: // All other types, copy input to output as hex string.
                // parserLog('debug', "  decodeDataType() WARNING: Unsupported type ".$dataType);
                $value = $hs; // No conversion.
                break;
            }

            return $value;
        }

        function getFF01IdName($id) {
            $IdName = array(
                '01' => "Volt",             // Based on Xiaomi Bouton V2 Carré
                '03' => "Device Temperature", // Based on #1344
                '05' => "tbd2",             // Type Associé 21 donc 16bitUint
                '07' => "tbd3",             // Type associé 27 donc 64bitUint
                '08' => "tbd4",             // ??
                '09' => "tbd5",             // ??
                '0B' => "tbd0b",            // Type associé 20 donc 8bitUint
                '64' => "Etat SW 1 Binaire", // Based on Aqara Double Relay (mais j ai aussi un 64 pour la temperature (Temp carré V2), Etat On/Off Prise Xiaomi
                '65' => "Etat SW 2 Binaire", // Based on Aqara Double Relay (mais j ai aussi un 65 pour Humidity Temp carré V2)
                '66' => "Pression",          // Based on Temperature Capteur V2
                '6E' => "Etat SW 1 Analog",  // Based on Aqara Double Relay
                '6F' => "Etat SW 2 Analog",  // Based on Aqara Double Relay
                '94' => "tbd6",             // Type associé ??
                '95' => "Consommation",     // Based on Prise Xiaomi
                '96' => "Voltage",          // Based on #1344
                '97' => "Current",          // Based on #1344
                '98' => "Puissance",        // Based on Aqara Double Relay nad #1344
                '9A' => "tbd9A",            // Type associé 20 donc une donnée 8bitUint
                '9B' => "tbd11",            // Type associé 10 donc une donnée binaire
            );
            if (array_key_exists($id, $IdName))
                return $IdName[$id];
            return '?'.$id.'?';
        }

        // /* Decode FF01 attribut payload */
        // function decodeFF01($data) {
        //     parserLog("debug", "  decodeFF01(".$data.")");

        //     $fields = array();
        //     $dataLen = strlen($data);
        //     while ($dataLen != 0) {
        //         if ($dataLen < 4) {
        //             parserLog('debug', 'decodeFF01(): Longueur incorrecte ('.$dataLen.'). Analyse FF01 interrompue.');
        //             break;
        //         }

        //         $id = $data[0].$data[1];
        //         $type = $data[2].$data[3];
        //         $dt_arr = $this->getZbDataType($type);
        //         $len = $dt_arr[1] * 2;
        //         if ($len == 0) {
        //             /* If length is unknown we can't continue since we don't known where is next good position */
        //             parserLog('warning', 'decodeFF01(): Type de données '.$type.' non supporté. Analyse FF01 interrompue.');
        //             break;
        //         }
        //         $value = substr($data, 4, $len );
        //         $fct = 'conv'.$dt_arr[0].'ToString';
        //         if (method_exists($this, $fct)) {
        //             $valueConverted = $this->$fct($value);
        //             // parserLog('debug', 'decodeFF01(): Conversion du type '.$type.' (val='.$value.')');
        //         } else {
        //             parserLog('debug', 'decodeFF01(): Conversion du type '.$type.' non supporté');
        //             $valueConverted = "";
        //         }

        //         $fields[$this->getFF01IdName($id)] = array(
        //             'id' => $id,
        //             'type' => $type,
        //             'typename' => $dt_arr[0],
        //             'value' => $value,
        //             'valueConverted' => $valueConverted,
        //         );
        //         $data = substr($data, 4 + $len);
        //         $dataLen = strlen($data);
        //     }
        //     return $fields;
        // }

         /**
         * protocolDatas: Treat messages received from AbeilleSerialRead, check CRC, and if Ok execute proper decode function.
         *
         * @param dest          Network (ex: 'Abeille1')
         * @param datas         Message received by the zigate
         *
         * @return Status       0=OK, -1=ERROR
         */
        function protocolDatas($dest, $datas) {
            // Reminder: message format received from Zigate.
            // 01/start & 03/end markers are already removed.
            //   00-03 : Msg Type (2 bytes)
            //   04-07 : Length (2 bytes) => optional payload + LQI
            //   08-09 : crc (1 byte)
            //   10... : Optional data / payload
            //   Last  : LQI (1 byte)

            // Primary checks to be sure message is valid
            $msgSize = strlen($datas);
            $msgSizeB = $msgSize / 2;
            if ($msgSize < 10) {
                ParserLog('error', $dest.", Message corrompu (trop court, taille=".$msgSizeB."B)");
                return -1; // Too short. Min=MsgType+Len+Crc
            }

            // Payload size: real size == expected ?
            // see github: AbeilleParser Erreur CRC #1562
            $plSizeExpB = $datas[4].$datas[5].$datas[6].$datas[7];
            $plSizeExpB = hexdec($plSizeExpB);
            $plSizeB = $msgSizeB - 5;
            $plSize = $plSizeB * 2;
            if ($plSizeB != $plSizeExpB) {
                ParserLog('error', $dest.", Message corrompu (taille payload incorrecte, taille=".$plSizeB."B, att=".$plSizeExpB."B)");
                return -1;
            }

            // Computing & checking CRC
            $crc = strtolower($datas[8].$datas[9]); // Expected CRC
            $crctmp = 0;
            $crctmp = $crctmp ^ hexdec($datas[0].$datas[1]) ^ hexdec($datas[2].$datas[3]); // Type
            $crctmp = $crctmp ^ hexdec($datas[4].$datas[5]) ^ hexdec($datas[6].$datas[7]); // Size
            for ($i = 0; $i < $plSize; $i += 2) {
                $crctmp = $crctmp ^ hexdec($datas[10 + $i].$datas[10 + $i + 1]);
            }
            if (hexdec($crc) != $crctmp) {
                parserLog('error', 'ERREUR CRC: calc=0x'.dechex($crctmp).', att=0x'.$crc.'. Message ignoré: '.substr($datas, 0, 12).'...'.substr($datas, -2, 2));
                parserLog('debug', 'Mess ignoré='.$datas);
                return -1;
            }

            // Seems a valid & usable message
            $plSize -= 2; // Excluding LQI (last 2 chars)

            // Message type
            $type = $datas[0].$datas[1].$datas[2].$datas[3];

            /* Payload.
               Payload size is 'Length' - 1 (excluding LQI) but CRC takes LQI into account.
               See https://github.com/fairecasoimeme/ZiGate/issues/325# */
            $payload = substr($datas, 10, $plSize);
            // parserLog('debug', 'type='.$type.', payload='.$payload);

            // LQI: last byte or 2 last chars
            $lqi = $datas[10 + $plSize].$datas[10 + $plSize + 1];
            $lqi = hexdec($lqi);
            // parserLog('debug','Msg='.$datas." => lqi=".$lqi);

            /* To be sure there is no port changes, checking received IEEE vs stored one.
               'ab::zgIeeeAddrOk' is set to 0 on daemon start when interrogation is not done yet.
               Should be updated by 8009 or 8024 responses */
            if ($type != '8000') {
                $zgId = substr($dest, 7); // AbeilleX => X
                $ieeeStatus = $GLOBALS['zigate'.$zgId]['ieeeStatus'];
                if ($ieeeStatus == -1) {
                    parserLog('debug', $dest.', unexpected IEEE => msg '.$type." ignored. Port switch ??");
                    return 0;
                }

                if ($ieeeStatus == 0) { // Still in interrogation
                    $keyIeeeOk = str_replace('Abeille', 'ab::zgIeeeAddrOk', $dest); // AbeilleX => ab::zgIeeeAddrOkX
                    $ieeeStatus = config::byKey($keyIeeeOk, 'Abeille', '0', true);
                    $GLOBALS['zigate'.$zgId]['ieeeStatus'] = $ieeeStatus; // Updating local status

                    if ($ieeeStatus == 0) {
                        msgToCmd(PRIO_HIGH, "CmdAbeille".$zgId."/0000/zgGetNetworkStatus");

                        $acceptedBeforeZigateIdentified = array("0208", "0302", "8001", "8006", "8007", "8009", "8010", "8024", "9999");
                        if (!in_array($type, $acceptedBeforeZigateIdentified)) {
                            parserLog('debug', $dest.', ab::zgIeeeAddrOk==0 => msg '.$type." ignored. Waiting 8009 or 8024.");
                            return 0;
                        }
                    }
                }
            }

            $fct = "decode".$type;
            if (method_exists($this, $fct)) {
                $this->$fct($dest, $payload, $lqi);
            } else {
                parserLog('debug', $dest.', Type='.$type.'/'.zgGetMsgName($type).' (unused).');
            }

            return 0;
        }

        /*--------------------------------------------------------------------------------------------------*/
        /* $this->decode functions
         /*--------------------------------------------------------------------------------------------------*/

        /**
         * 004D/Device announce
         * This method process a Zigbeee annonce message coming from a device
         *  Will first decode it.
         *  Send information to Abeille to update Jeedom
         *  And start the device identification by requesting EP and IEEE to the device.
         *
         * @param $dest     Zigbee network (ex: Abeille1)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return Nothing
         */
        function decode004d($dest, $payload, $lqi) {
            /* < short address: uint16_t>
               < IEEE address: uint64_t>
               < MAC capability: uint8_t> MAC capability
                    Bit 0 - Alternate PAN Coordinator    => 1 no
                    Bit 1 - Device Type                  => 2 yes
                    Bit 2 - Power source                 => 4 yes
                    Bit 3 - Receiver On when Idle        => 8 yes
                    Bit 4 - Reserved                     => 16 no
                    Bit 5 - Reserved                     => 32 no
                    Bit 6 - Security capability          => 64 no
                    Bit 7 - Allocate Address             => 128 no
               <Rejoin information : uint8_t> (from v3.1b) => OPTIONAL !!
                    If len(payload) == 22 ==> No Join Flag
                    If len(payload) == 24 ==> Join Flag
                    When receiving a Device Annoucement the Rejoin Flag can give us some information
                    0x00 The device was not on the network.
                         Most-likely it has been reset, and all Unbind, Bind , Report, must be redone
                    0x01 The device was on the Network, but change its route
                         the device was not reset
                    0x02, 0x03 The device was on the network and coming back.
                         Here we can assumed the device was not reset. */
            /* See https://github.com/fairecasoimeme/ZiGate/issues/325# */

            $addr       = substr($payload, 0, 4);
            $ieee       = substr($payload, 4, 16);
            $macCapa    = substr($payload, 20, 2);
            if (strlen($payload) > 22)
                $rejoin = substr($payload, 22, 2);
            else
                $rejoin = "";

            $m = '004d/Device announce'.', Addr='.$addr.', ExtAddr='.$ieee.', MACCapa='.$macCapa;
            if ($rejoin != "") $m .= ', Rejoin='.$rejoin;
            parserLog('debug', $dest.', Type='.$m);

            // Work-around for https://github.com/fairecasoimeme/ZiGatev2/issues/36#
            // Note: if no 8002 before (dev announce just after restart), better to ignore instead of accept a wrong dev announce.
            $zgId = substr($dest, 7);
            if (!isset($GLOBALS['zigate'.$zgId]['fwVersionMaj'])) {
                parserLog('debug', '  WARNING: FW major currently unknown => ignoring');
                return;
            }
            $min = hexdec($GLOBALS['zigate'.$zgId]['fwVersionMin']);
            if (($GLOBALS['zigate'.$zgId]['fwVersionMaj'] == "0005") && ($min < 0x322)) { // Zigate v2 & fw < 3.22 ?
                global $last8002DevAnnounce;
                if ($addr != $last8002DevAnnounce) {
                    parserLog('debug', '  WARNING: Corrupted dev announce => ignoring');
                    return;
                }
            }

            $this->deviceAnnounce($dest, $addr, $ieee, $macCapa, $rejoin);

            // Monitor if requested
            if (isset($GLOBALS["dbgMonitorAddrExt"]) && !strcasecmp($GLOBALS["dbgMonitorAddrExt"], $ieee)) {
                monMsgFromZigate($m); // Send message to monitor
                monAddrHasChanged($addr, $ieee); // Short address has changed
                $GLOBALS["dbgMonitorAddr"] = $addr;
            }

            // $this->whoTalked[] = $dest.'/'.$addr;

            /* Send to client if required (EQ page opened) */
            $toCli = array(
                // 'src' => 'parser',
                'type' => 'deviceAnnounce',
                'net' => $dest,
                'addr' => $addr,
                'ieee' => $ieee
            );
            $this->msgToClient($toCli);

            // $zgId = substr($dest, 7);
            // if (!isset($GLOBALS['zigate'.$zgId]['permitJoin']) || ($GLOBALS['zigate'.$zgId]['permitJoin'] != "01")) {
            //     if (!isset($GLOBALS['devices'][$dest]) || !isset($GLOBALS['devices'][$dest][$addr]))
            //         parserLog('debug', '  Not in inclusion mode but trying to identify unknown device anyway.');
            //     else
            //         parserLog('debug', '  Not in inclusion mode and got a device announce for already known device.');
            // }
        }

        /* Fonction specifique pour le retour d'etat de l interrupteur Livolo. */
        function decode0100($dest, $payload, $lqi) {
            // obj -> ZiGate            0x0100
            // Read Attribute request
            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
                // <Cluster id: uint16_t> : missing
            // <direction: uint8_t>
            // <manufacturer specific: uint8_t>
            // <manufacturer id: uint16_t>
            // <number of attributes: uint8_t>
            // <attributes list: data list of uint16_t each>
            // Direction:
            //  0 – from server to client
            //  1 – from client to server
            //  Manufacturer specific :
            //  0 – No 1 – Yes
            // Probleme sur format c.f. mail avec Fred.

            // Cmd         AddrMode   Addr EPS EPD Dir Spe Id   #  #1 #2 #3 #4 #5 Value
            // 0100 0011bb 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 00    8a -> 0&0 (0)
            // 0100 0011bd 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 01    8d -> 1&0 (1)
            // 0100 0011b9 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 02    8a -> 0&1 (2)
            // 0100 0011bf 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 03    8d -> 1&1 (3)
            //             0          2    6   8   10                                   30

            $srcAddr    = substr($payload,  2,  4);
            $EPS        = substr($payload,  6,  2);
            $data       = substr($payload, 30,  2);

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            $msgDecoded = "0100/?";
            parserLog('debug', $dest.', Type='.$msgDecoded);

            // $this->msgToAbeille($dest."/".$srcAddr, "0006-".$EPS, "0000", $data);
            $attrReportN = [
                array( "name" => "0006-".$EPS."-0000", "value" => $data ),
            ];
            $toAbeille = array(
                // 'src' => 'parser',
                'type' => 'attributesReportN',
                'net' => $dest,
                'addr' => $srcAddr,
                'ep' => $ep,
                'clustId' => $clustId,
                'attributes' => $attrReportN,
                'time' => time(),
                'lqi' => $lqi
            );
            msgToMainD($toAbeille);

            // Monitor if requested
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor
        }

        // 8000/Zigate Status
        function decode8000($net, $payload, $lqi) {
            $status     = substr($payload, 0, 2);
            $sqn        = substr($payload, 2, 2);
            $packetType = substr($payload, 4, 4);
            $m = '8000/Status'
                .', Status='.$status.'/'.zgGet8000Status($status)
                .', SQN='.$sqn
                .', PacketType='.$packetType;
            $sqnAps = '';
            $l = strlen($payload);
            if ($l >= 12) {
                // FW >= 3.1d
                $sent = substr($payload, 8, 2); // 1=Sent to device, 0=zigate only cmd
                $sqnAps = substr($payload, 10, 2);
                $m .= ', Sent='.$sent.', SQNAPS='.$sqnAps;
            }
            if ($l == 16) {
                // FW >= 3.1e
                $nPdu = substr($payload, 12, 2);
                $aPdu = substr($payload, 14, 2);
                $m .= ', NPDU='.$nPdu.', APDU='.$aPdu;
            }

            parserLog('debug', $net.', Type='.$m, "8000");

            // Sending msg to cmd for flow control
            $msg = array (
                'type'          => "8000",
                'net'           => $net,
                'status'        => $status,
                'sqn'           => $sqn,
                'sqnAps'        => $sqnAps,
                'packetType'    => $packetType , // The value of the initiating command request.
            );
            if (isset($nPdu)) {
                $msg['nPDU'] = $nPdu;
                $msg['aPDU'] = $aPdu;
            }
            msgToCmdAck($msg);

            if ($packetType == "0002") {
                if ($status == "00") {
                    parserLog("debug", "  Zigate mode has been properly changed.");
                } else {
                    parserLog("error", $net.": Impossible de changer le mode de la Zigate.");
                    // message::add("Abeille", "Erreur lors du changement de mode de la Zigate.", "");
                }
            }

            // If status 06, let's reset Zigate again
            // See https://github.com/fairecasoimeme/ZiGatev2/issues/50#top
            // See https://github.com/KiwiHC16/Abeille/issues/2490#top
            if ($status == "06") {
                parserLog("debug", "  Error 06 => SW reset in 20sec");
                msgToCmd(PRIO_HIGH, "TempoCmd".$net."/0000/zgSoftReset&delay=20");
            }

            // Saving NPDU+APDU & checking NDPU. If stuck too long Zigate SW reset is required
            if (isset($nPdu)) {
                $this->checkNpdu($net, $nPdu);
                $zgId = substr($net, 7); // AbeilleX => X
                $GLOBALS['zigate'.$zgId]['aPdu'] = $aPdu;
            }
        }

        // 8001/Log message
        function decode8001($dest, $pl, $lqi) {
            $level  = substr($pl, 0, 2);
            $msg    = substr($pl, 2);
            $msg    = pack("H*", $msg);

            $m = '8001/Log message'
                .', Level='.$level
                .', Msg='.$msg;
            parserLog('debug', $dest.', Type='.$m, "8001");
        }

        // Some attributs defined in ZCL spec have a predefined formula to apply.
        // Ex: Cluster 0402, attr 0000 = temperature to be devided by 100.
        // 'attr' is passed by ref as it could be updated.
        function decode8002_ZCLCorrectAttrValue($ep, $clustId, $eq, &$attr) {
            $newVal = $attr['value'];
            $attrId  = $attr['id'];

            if (isset($eq['notStandard']) && isset($eq['notStandard'][$clustId.'-'.$ep.'-'.$attrId])) {
                $attr['comment'] = 'NOT STANDARD value';
            } else {
                if ($clustId == "0001") {
                    if ($attrId == "0020") {
                        $newVal = $newVal / 10; // Battery voltage
                    } else if ($attrId == "0021") {
                        $newVal = $newVal / 2; // Battery percent
                    }
                } else if ($clustId == "0201") { // Thermostat
                    if (($attrId == "0000") || ($attrId == "0012")) {
                        $newVal /= 100; // LocalTemperature or OccupiedHeatingSetpoint
                    }
                } else if ($clustId == "0300") {
                    if ($attrId == "0007") {
                        // Color temperature in kelvins = 1,000,000 / ColorTemperatureMireds
                        $newVal = 1000000 / $newVal;
                    }
                } else if ($clustId == "0400") {
                    if ($attrId == "0000") {
                        $newVal = ($newVal == 0 ? 0 : pow(10, ($newVal - 1) / 10000)); // Illuminance
                    }
                } else if ($clustId == "0402") {
                    if ($attrId == "0000") {
                        $newVal /= 100; // Temperature
                    }
                } else if ($clustId == "0403") {
                    if ($attrId == "0000") {
                        $newVal /= 10; // Pressure (in kPa)
                    }
                } else if ($clustId == "0405") {
                    if ($attrId == "0000") {
                        $newVal /= 100; // Humidity
                    }
                }

                if ($newVal != $attr['value']) {
                    $attr['value'] = $newVal;
                    $attr['comment'] = "ZCL corrected value";
                }
            }
        }

        /* Called from decode8002() to decode a "Read Attribute Status Record"
           Returns: false if error */
        function decode8002_ReadAttrStatusRecord($srcAddr, $hexString, &$size) {
            /* Attributes status record format:
                Attr id = 2B
                Status = 1B
                Attr data type = 1B (if status == 0)
                Attr = <according to data type> (if status == 0) */
            $l = strlen($hexString);
            if ($l < 6) {
                parserLog2('debug', $srcAddr, "  decode8002_ReadAttrStatusRecord() ERROR: Unexpected record size ".$l);
                return false;
            }
            $attrStatus = substr($hexString, 4, 2);
            $attr = array(
                'id' => substr($hexString, 2, 2).substr($hexString, 0, 2),
                'status' => $attrStatus,
                'dataType' => '',
                'valueHex' => '', // Extracted hex value
                'value' => null // Real value
            );
            $size = 6; // Amount of read bytes
            if ($attrStatus != '00') {
                $attr['value'] = false;
                return $attr;
            }

            $attr['dataType'] = substr($hexString, 6, 2);
            $hexString = substr($hexString, 8);
            $attr['value'] = $this->decodeDataType($hexString, $attr['dataType'], true, null, $dataSize, $valueHex);
            if ($attr['value'] === false)
                return false;
            $attr['valueHex'] = $valueHex;
            $size = 6 + 2 + (2 * $dataSize);
            return $attr;
        }

        /* Called from decode8002() to decode a "Report attribute"
           Returns: false if error */
        function decode8002_ReportAttribute($srcAddr, $hexString, &$size) {
            /* Attributes status record format:
                Attr id = 2B
                Attr data type = 1B
                Attr = <according to data type> */
            $l = strlen($hexString);
            if ($l < 8) { // 4 + 2 + 2 at least
                parserLog2('debug', $srcAddr, "  decode8002_ReportAttribute() ERROR: Unexpected record size ".$l);
                return false;
            }
            $attr = array(
                'id' => substr($hexString, 2, 2).substr($hexString, 0, 2),
                'dataType' => substr($hexString, 4, 2),
                'value' => null
            );
            $hexString = substr($hexString, 6);
            $attr['value'] = $this->decodeDataType($hexString, $attr['dataType'], true, null, $dataSize, $valueHex);
            if ($attr['value'] === false)
                return false;
            $attr['valueHex'] = $valueHex;
            $size = 6 + (2 * $dataSize);
            return $attr;
        }

        /* Called from decode8002() to decode Network addr response (Nwk_addr_rsp) message.
           Previously decoded by 8040. */
        function decode8002_NwkAddrRsp($net, $srcAddr, $pl, $lqi, &$devUpdates, &$toAbeille) {
            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);
            $ieee = AbeilleTools::reverseHex(substr($pl, 4, 16));
            $addr = AbeilleTools::reverseHex(substr($pl, 20, 4));

            // Log
            $m = '  NWK address response'
                    .': SQN='.$sqn
                    .', Status='.$status.'/'.zbGetZDPStatus($status)
                    .', ExtAddr='.$ieee
                    .', Addr='.$addr;

            if ($status != "00") {
                parserLog2('debug', $srcAddr, $m);
                // $unknown = $this->deviceUpdate($net, $addr, ''); // Useless
                return;
            }

            // Status == "00" => continue decoding
            $nbDevices = substr($pl, 24, 2);
            $startIdx = substr($pl, 26, 2);
            $m .= ', NbOfAssociatedDevices='.$nbDevices
                .', StartIdx='.$startIdx;
            parserLog2('debug', $srcAddr, $m);
            for ($i = 0; $i < (intval($nbDevices) * 4); $i += 4) {
                parserLog2('debug', $srcAddr, '  AssociatedDev='.substr($pl, (28 + $i), 4));
            }

            // $this->whoTalked[] = $net.'/'.$addr;

            // If device is unknown, may have pending messages for him
            $devUpdates['ieee'] = $ieee;

            $toAbeille[] = array(
                // 'src' => 'parser',
                'type' => 'nwkAddrResponse',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $ieee,
                'time' => time(),
                'lqi' => $lqi,
            );
        } // End decode8002_NwkAddrRsp()

        /* Called from decode8002() to decode IEEE addr response (IEEE_addr_rsp) message.
           Previously decoded by 8041. */
        function decode8002_IeeeAddrRsp($net, $srcAddr, $pl, $lqi, &$devUpdates, &$toAbeille) {
            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);
            $ieee = AbeilleTools::reverseHex(substr($pl, 4, 16));
            $addr = AbeilleTools::reverseHex(substr($pl, 20, 4));

            // Log
            $m = '  IEEE address response'
                    .': SQN='.$sqn
                    .', Status='.$status
                    .', ExtAddr='.$ieee
                    .', Addr='.$addr;
            // parserLog2('debug', $srcAddr, $m);

            if ($status != "00") {
                parserLog2('debug', $srcAddr, '  Status='.$status.' => Unknown error');
                // $unknown = $this->deviceUpdate($net, $addr, ''); // Useless
                return;
            }

            // Status == "00" => continue decoding
            $nbDevices = substr($pl, 24, 2);
            $startIdx = substr($pl, 26, 2);
            $m .= ', NbOfAssociatedDevices='.$nbDevices
                .', StartIndex='.$startIdx;
            parserLog2('debug', $srcAddr, $m);

            for ($i = 0; $i < (intval($nbDevices) * 4); $i += 4) {
                parserLog2('debug', $srcAddr, '    Dev='.AbeilleTools::reverseHex(substr($pl, (28 + $i), 4)));
            }

            // $this->whoTalked[] = $net.'/'.$addr;

            // If device is unknown, may have pending messages for him
            $devUpdates['ieee'] = $ieee;
            // $unknown = $this->deviceUpdates($net, $addr, '', $updates);
            // if ($unknown)
            //     return;

            $toAbeille[] = array(
                // 'src' => 'parser',
                'type' => 'ieeeAddrResponse',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $ieee,
                'time' => time(),
                'lqi' => $lqi,
            );
            // msgToMainD($msg);
        } // End decode8002_IeeeAddrRsp()

        /* Called from decode8002() to decode "Node descriptor response" message */
        function decode8002_NodeDescRsp($net, $srcAddr, $pl, &$devUpdates) {
            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);
            $addr = AbeilleTools::reverseHex(substr($pl, 4, 4));
            $m = '  Node Descriptor Response: SQN='.$sqn.', Status='.$status;
            parserLog2('debug', $srcAddr, $m);

            // Node descriptor
            $b0 = substr($pl, 8, 2); // LogicalType/ComplexDescAvail/UserDescAvail/Reserved
            $logicalType = hexdec($b0) & 0x7; // Logical type: 0=coordinator/1=router/2=end_device
            $b2 = substr($pl, 10, 2); // APS/freq band
            $macCapa = substr($pl, 12, 2); // Mac capa
            $manufCode = AbeilleTools::reverseHex(substr($pl, 14, 4));

            parserLog2('debug', $srcAddr, "  LogicalType={$logicalType}, MacCapa={$macCapa}, ManufCode={$manufCode}");

            // Infos to collect & store: logicalType, macCapa & manufCode
            $eq = getDevice($net, $srcAddr);
            // WARNING: macCapa can be overloaded by customization
            if (isset($eq['customization']) && isset($eq['customization']['macCapa'])) {
                $macCapa = $eq['customization']['macCapa'];
                parserLog2('debug', $srcAddr, "  'macCapa' customization: ".$macCapa);
            }

            if (!isset($eq['zigbee']['logicalType']) || ($logicalType != $eq['zigbee']['logicalType']))
                $devUpdates['logicalType'] = $logicalType;
            if (!isset($eq['zigbee']['macCapa']) || ($macCapa != $eq['zigbee']['macCapa']))
                $devUpdates['macCapa'] = $macCapa;
            else {
                // Check rxOn status even if macCapa is correct
                $ao = (hexdec($macCapa) >> 3) & 0b1;
                $rxOnWhenIdle = $ao ? true : false;
                if ($rxOnWhenIdle != $eq['zigbee']['rxOnWhenIdle'])
                    $devUpdates['rxOnWhenIdle'] = $rxOnWhenIdle;
            }
            if (!isset($eq['zigbee']['manufCode']) || ($manufCode != $eq['zigbee']['manufCode']))
                $devUpdates['manufCode'] = $manufCode;
        }

        /* Called from decode8002() to decode "Simple_Desc_rsp"
           Was previously handled by 8043. */
        function decode8002_SimpleDescRsp($net, $srcAddr, $pl) {
            $sqn        = substr($pl, 0, 2);
            $status     = substr($pl, 2, 2);
            $srcAddr    = substr($pl, 6, 2).substr($pl, 4, 2);
            $len        = substr($pl, 8, 2);
            $ep         = substr($pl, 10, 2);
            $profId     = substr($pl, 14, 2).substr($pl, 12, 2);
            $devId      = substr($pl, 18, 2).substr($pl, 16, 2);
            $m = '  Simple descriptor response'
                .': SQN='.$sqn
                .', Status='.$status
                .', Addr='.$srcAddr
                .', Len='.$len
                .', EP='.$ep
                .', ProfId='.$profId.'/'.zbGetProfile($profId)
                .', DevId='.$devId.'/'.zbGetDevice($profId, $devId);

            $discovering = $this->discoveringState($net, $srcAddr);
            if ($status != "00") {
                parserLog2('debug', $srcAddr, $m);
                parserLog2('debug', $srcAddr, "  Status ({$status}) != '00' => Decoding ignored");
                if ($discovering)
                    $this->discoverUpdate($net, $srcAddr, $ep, 'SimpleDescriptorResponse', $status, $statusMsg);
                return;
            }

            // Status ok => continue decoding
            $servClustCount = hexdec(substr($pl, 22, 2)); // Number of server clusters
            $servClusters = [];
            for ($i = 0; $i < ($servClustCount * 4); $i += 4) {
                $clustId = AbeilleTools::reverseHex(substr($pl, (24 + $i), 4));
                $servClusters[$clustId] = [];
            }
            $cliClustCount = hexdec(substr($pl, 24 + $i, 2));
            $cliClusters = [];
            for ($j = 0; $j < ($cliClustCount * 4); $j += 4) {
                $clustId = AbeilleTools::reverseHex(substr($pl, (24 + $i + 2 + $j), 4));
                $cliClusters[$clustId] = [];
            }

            /* Log */
            parserLog2('debug', $srcAddr, $m);
            $inputClusters = "";
            $outputClusters = "";
            if ($status == "00") {
                parserLog2('debug', $srcAddr, '  ServClustCount='.$servClustCount);
                foreach ($servClusters as $clustId => $clust) {
                    if ($inputClusters != "")
                        $inputClusters .= "/";
                    $inputClusters .= $clustId;
                    $m = '  ServCluster='.$clustId.' => '.zbGetZCLClusterName($clustId);
                    parserLog2('debug', $srcAddr, $m);
                }
                parserLog2('debug', $srcAddr, '  CliClustCount='.$cliClustCount);
                foreach ($cliClusters as $clustId => $clust) {
                    if ($outputClusters != "")
                        $outputClusters .= "/";
                    $outputClusters .= $clustId;
                    $m = '  OutCluster='.$clustId.' => '.zbGetZCLClusterName($clustId);
                    parserLog2('debug', $srcAddr, $m);
                }
            }

            // $this->whoTalked[] = $dest.'/'.$srcAddr;

            /* Record info if discovering state for this device */
            // $discovering = $this->discoveringState($net, $srcAddr);
            $devUpdates = [];
            if (!$discovering) {
                $devUpdates['profId'] = $profId;
                $devUpdates['devId'] = $devId;
                $devUpdates['servClusters'] = $inputClusters;
            } else {
                $sdr = [];
                $sdr['servClustCount'] = $servClustCount;
                $sdr['servClusters'] = $servClusters;
                $sdr['cliClustCount'] = $cliClustCount;
                $sdr['cliClusters'] = $cliClusters;
                $this->discoverUpdate($net, $srcAddr, $ep, 'SimpleDescriptorResponse', "00", $sdr);
            }

            /* Note: reporting device updates here and not and the end of decode8002
               because for this case EP00 may report infos for EP01, leading to
               clusters saved for wrong EP00 instead of 01. */
            if (count($devUpdates) != 0)
                $this->deviceUpdates($net, $srcAddr, $ep, $devUpdates);

            /* Send to client if required (EQ page opened) */
            $toCli = array(
                // 'src' => 'parser',
                'type' => 'simpleDesc',
                'net' => $net,
                'addr' => $srcAddr,
                'ep' => $ep,
                'profId' => $profId,
                'devId' => $devId,
                'inClustList' => $inputClusters, // Format: 'xxxx/yyyy/zzzz'
                'outClustList' => $outputClusters // Format: 'xxxx/yyyy/zzzz'
            );
            $this->msgToClient($toCli);
        } // End decode8002_SimpleDescRsp()

        /* Called from decode8002() to decode "Active_EP_rsp"
           Was previously handled by 8045. */
        function decode8002_ActiveEpRsp($net, $srcAddr, $pl, &$devUpdates) {
            $sqn        = substr($pl, 0, 2);
            $status     = substr($pl, 2, 2);
            $srcAddr    = substr($pl, 6, 2).substr($pl, 4, 2);
            $m = '  Active endpoints response'
               .': SQN='.$sqn
               .', Status='.$status
               .', Addr='.$srcAddr;

            if ($status != "00") {
                parserLog2('debug', $srcAddr, $m);
                parserLog2('debug', $srcAddr, '  Status != 0 => ignoring');
                return;
            }

            // Status ok => continue decoding
            $epCount    = substr($pl, 8, 2);
            $epList     = "";
            for ($i = 0; $i < (intval($epCount) * 2); $i += 2) {
                if ($i != 0)
                    $epList .= "/";
                $epList .= substr($pl, (10 + $i), 2);
                if ($i == 0) {
                    $ep = substr($pl, (10 + $i), 2);
                }
            }

            parserLog2('debug', $srcAddr, $m.', EPCount='.$epCount.', EPList='.$epList);

            // $this->whoTalked[] = $net.'/'.$srcAddr;

            /* Update equipement key infos */
            // $updates = [];
            $devUpdates['epList'] = $epList;
            // $unknown = $this->deviceUpdates($net, $srcAddr, '', $updates);
            // if ($unknown)
            //     return;

            /* Send to client */
            $toCli = array(
                // 'src' => 'parser',
                'type' => 'activeEndpoints',
                'net' => $net,
                'addr' => $srcAddr,
                'epList' => $epList
            );
            $this->msgToClient($toCli);
        } // End decode8002_ActiveEpRsp()

        /* Called from decode8002() to decode "Bind response" message (Bind_rsp, cluster 8021) */
        function decode8002_BindRsp($net, $srcAddr, $pl, $lqi, &$toAbeille) {
            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);

            $m = '  Bind response'
                .': SQN='.$sqn
                .', Status='.$status;
            parserLog2('debug', $srcAddr, $m);

            $toAbeille[] = array(
                // 'src' => 'parser',
                'type' => 'bindResponse',
                'net' => $net,
                'addr' => $srcAddr,
                'status' => $status,
                'time' => time(),
                'lqi' => $lqi,
            );
            // msgToMainD($msg);
        } // End decode8002_BindRsp()

        /* Called from decode8002() to decode "Unbind response" message (Unbind_rsp, cluster 8022) */
        function decode8002_UnbindRsp($net, $srcAddr, $pl, $lqi, &$toAbeille) {
            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);

            $m = '  Unbind response'
                .': SQN='.$sqn
                .', Status='.$status.'/'.zbGetZCLStatus($status);
            parserLog2('debug', $srcAddr, $m);

            $toAbeille[] = array(
                // 'src' => 'parser',
                'type' => 'unbindResponse',
                'net' => $net,
                'addr' => $srcAddr,
                'status' => $status,
                'time' => time(),
                'lqi' => $lqi,
            );
            // msgToMainD($msg);
        } // End decode8002_UnbindRsp()

        /* Called from decode8002() to decode "Mgmt_lqi_rsp" message */
        function decode8002_MgmtLqiRsp($dest, $srcAddr, $pl) {

            $zgId = substr($dest, 7); // 'AbeilleX' => 'X'

            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);
            $nTableEntries = substr($pl, 4, 2); // NeighborTableEntries
            $startIdx = substr($pl, 6, 2);
            $nTableListCount = substr($pl, 8, 2); // NeighborTableListCount
            $pl = substr($pl, 10);

            $m = '  Management LQI response';
            $m .= ': SQN='.$sqn.', Status='.$status.', NTableEntries='.$nTableEntries.', StartIdx='.$startIdx.', NTableListCount='.$nTableListCount;
            parserLog2('debug', $srcAddr, $m);

            $toLqiCollector = array(
                'type' => 'mgmtLqiRsp',
                'srcAddr' => $srcAddr,
                'status' => $status,
                'tableEntries' => $nTableEntries,
                'tableListCount' => 0, // Returning only nb in the current extended PAN ID
                'startIdx' => $startIdx,
                'nList' => []
            );

            // Tcharp38: I've seen 'C1' when Zigate is empty
            if ($status != "00") {
                parserLog2('debug', $srcAddr, "  Status != 00 => Decode canceled");
                $this->msgToLQICollector($toLqiCollector);
                return;
            }

            $corrupted = false;
            if (hexdec($nTableListCount) > hexdec($nTableEntries))
                $corrupted = true;
            if ((hexdec($startIdx) + hexdec($nTableListCount)) > hexdec($nTableEntries))
                $corrupted = true;
            // Each entry must be 64+64+16+2+2+3+1+2+6+8+8=176 bits=22 bytes
            if ((strlen($pl) / 2) != (hexdec($nTableListCount) * 22))
                $corrupted = true; // Wrong size
            if ($corrupted) {
                parserLog2('debug', $srcAddr, '  WARNING: Corrupted/inconsistent message => ignored');
                $toLqiCollector['status'] = '12'; // Fake but failed status
                $this->msgToLQICollector($toLqiCollector);
                return;
            }

            $nList = []; // List of neighbors
            $j = 0;
            for ($i = 0; $i < hexdec($nTableListCount); $j += 44, $i++) {
                $extPanId = AbeilleTools::reverseHex(substr($pl, $j + 0, 16));
                // Filtering-out devices from other networks
                if (isset($GLOBALS['zigate'.$zgId]['extPanId'])) {
                    if ($extPanId != $GLOBALS['zigate'.$zgId]['extPanId']) {
                        parserLog2('debug', $srcAddr, '  Alternate network (extPanId='.$extPanId.') ignored');
                        continue;
                    }
                }
                $bitMap1 = hexdec(AbeilleTools::reverseHex(substr($pl, $j + 36, 2))); // Dev type + rxOnWhenIdle + relationShip
                $bitMap2 = hexdec(AbeilleTools::reverseHex(substr($pl, $j + 38, 2))); // Permit joining
                $N = array(
                    "extPANId" => $extPanId,
                    "extAddr"  => AbeilleTools::reverseHex(substr($pl, $j + 16, 16)),
                    "addr"     => AbeilleTools::reverseHex(substr($pl, $j + 32, 4)),
                    "devType"  => $bitMap1 & 0x3,
                    "rxOnWhenIdle"  => ($bitMap1 >> 2) & 0x3,
                    "relationship"  => ($bitMap1 >> 4) & 0x7,
                    "depth"    => substr($pl, $j + 40, 2),
                    "lqi"      => substr($pl, $j + 42, 2),
                );
                $nList[] = $N; // Add to neighbors list
                $toLqiCollector['tableListCount']++;
                parserLog2('debug', $srcAddr, '  NExtPANId='.$N['extPANId']
                    .', NExtAddr='.$N['extAddr']
                    .', NAddr='.$N['addr']
                    .', NDevType='.$N['devType']
                    .', NRxOnWhenIdle='.$N['rxOnWhenIdle']
                    .', NRelationship='.$N['relationship']
                    .', NDepth='.$N['depth']
                    .', NLQI='.$N['lqi']);
            }
            $toLqiCollector['nList'] = $nList;
            $this->msgToLQICollector($toLqiCollector);

            // Foreach neighbor, let's ensure that useful infos are stored
            // Tcharp38: No reliable info to collect thru 'Mgmt_lqi_rsp' except short & IEEE addresses.
            // foreach ($nList as $N) {
            //     if ($N['addr'] == "0000")
            //         continue; // It's a zigate

            //     $eq = getDevice($dest, $N['addr'], $N['extAddr']);

            //     // Any useful infos to update ?
            //     $bitMap = hexdec($N['bitMap']);
            //     $rxOn = ($bitMap >> 2) & 0x1; // 01 = RX ON when idle
            //     if (isset($eq['customization']) && isset($eq['customization']['rxOn'])) {
            //         $rxOn = $eq['customization']['rxOn'];
            //         parserLog('debug', "  ".$N['addr'].": 'rxOnWhenIdle' customized to ".$rxOn);
            //     }

            //     $updates = [];
            //     if ($eq['zigbee']['ieee'] != $N['extAddr'])
            //         $updates['ieee'] = $N['extAddr'];
            //     if ($eq['rxOnWhenIdle'] && ($eq['rxOnWhenIdle'] != $rxOn)) {
            //         parserLog('debug', "  ".$N['addr'].": WARNING, unexpected rxOn difference: rxOnWhenIdle=".$eq['rxOnWhenIdle'].", rxOn=".$rxOn);
            //         // $updates['rxOnWhenIdle'] = $rxOn;
            //         // Update DISABLED. Seems not reliable or error in bitMap interpretation. See #2532
            //         // >     Du 0020: "addr":"0E9F","bitMap":"0225"
            //         // >     Du B78D: "addr":"0E9F","bitMap":"0229"
            //         // >     Du 342B: "addr":"0E9F","bitMap":"0229"
            //     }
            //     if ($updates != [])
            //         $this->updateDevice($dest, $N['addr'], $updates);
            // }
            foreach ($nList as $N) {
                if ($N['addr'] == "0000")
                    continue; // It's a zigate

                $updates = [];
                $updates['ieee'] = $N['extAddr'];
                $this->deviceUpdates($dest, $N['addr'], '', $updates);
            }
        } // End decode8002_MgmtLqiRsp()

        /* Called from decode8002() to decode "Routing table response" (Mgmt_Rtg_rsp) message */
        function decode8002_MgmtRtgRsp($net, $srcAddr, $pl) {
            $sqn            = substr($pl, 0, 2);
            $status         = substr($pl, 2, 2);
            $tableEntries   = hexdec(substr($pl, 4, 2));
            $startIdx       = hexdec(substr($pl, 6, 2));
            $tableListCount = hexdec(substr($pl, 8, 2));

            // Duplicated message ?
            if ($this->isDuplicated($net, $srcAddr, '', $sqn))
                return;

            $m = '  Routing table response'
                    .': SQN='.$sqn
                    .', Status='.$status
                    .', TableEntries='.$tableEntries
                    .', StartIdx='.$startIdx
                    .', TableListCount='.$tableListCount;
            parserLog2('debug', $srcAddr, $m);

            if ($status != "00") {
                parserLog2('debug', $srcAddr, "  Status != 00 => Decode canceled");
                return;
            }

            // ZigBee Specification: 2.4.4.3.3   Mgmt_Rtg_rsp
            // 3 bits (status) + 1 bit memory constrained concentrator + 1 bit many-to-one + 1 bit Route Record required + 2 bit reserved
            $statusDecode = array(
                0x00 => "Active",
                0x01 => "Discovery_Underway",
                0x02 => "Discovery_Failed",
                0x03 => "Inactive",
                0x04 => "Validation_Underway", // Got that if interrogate the Zigate
                0x05 => "Reserved",
                0x06 => "Reserved",
                0x07 => "Reserved",
            );

            $pl = substr($pl, 10);
            $routingTable = array();
            for ($i = 0; $i < $tableListCount; $i++) {

                $destAddr = AbeilleTools::reverseHex(substr($pl, 0, 4));
                $flags = hexdec(substr($pl, 4, 2));
                $statusRouting = ($flags >> 0) & 0x7;
                $manyToOne = ($flags >> 4) & 1;
                $statusDecoded = $statusDecode[$statusRouting];
                if ($manyToOne)
                    $statusDecoded .= " + Many To One";
                $nextHop = AbeilleTools::reverseHex(substr($pl, 6, 4));
                $pl = substr($pl, 10);

                $m = '  DestAddr='.$destAddr.', Status='.$statusRouting.'/'.$statusDecoded.', NextHop='.$nextHop;
                parserLog2('debug', $srcAddr, $m);

                if (($statusRouting == 0) && ($nextHop != $destAddr)) {
                    $routingTable[$destAddr] = $nextHop;
                }
            }
            // Routing table is now stored in /tmp/jeedom/Abeille/AbeilleRoutes-AbeilleX.json
            $this->msgToRoutingCollector($srcAddr, $tableEntries, $tableListCount, $startIdx, $routingTable);
        } // End decode8002_MgmtRtgRsp()

        /* Called from decode8002() to decode "Mgmt_NWK_Update_notify" (cluster=8038))
           previously handled by 804A. */
        function decode8002_MgmtNwkUpdateNotify($net, $srcAddr, $pl) {

            $sqn = substr($pl, 0, 2);
            $status = substr($pl, 2, 2);
            $ScannedChannels = AbeilleTools::reverseHex(substr($pl, 4, 8));
            $TotalTransmission = AbeilleTools::reverseHex(substr($pl, 12, 4));
            $TransmFailures = AbeilleTools::reverseHex(substr($pl, 16, 4));
            $ScannedChannelsCount = substr($pl, 20, 2);

            $m = '  Management network update response'
               .': SQN='.$sqn
               .', Status='.$status
               .', TotTx='.$TotalTransmission
               .', TxFailures='.$TransmFailures
               .', ScannedChan='.$ScannedChannels
               .', ScannedChanCount='.$ScannedChannelsCount;
            parserLog2('debug', $srcAddr, $m);

            if ($status != "00") {
                parserLog2('debug', $srcAddr, '  Status Error ('.$status.') can not process the message.');
                return;
            }

            $chan = 11; // Could need to be adapted if we change the list of channel requested, at this time all of them.
            $results = array();
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($pl, (22 + ($i * 2)), 2); // hexa value
                $results[$chan] = hexdec($Chan);
                $chan++;
            }
            $addr = substr($pl, (22 + ($i * 2)), 4);

            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list($eqNet, $eqAddr) = explode("/", $eqLogic->getLogicalId());
                if (($net == $eqNet) &&($addr == $eqAddr)) {
                    // parserLog('debug', '  Storing the information');
                    $eqLogic->setConfiguration('totalTransmission', $TotalTransmission);
                    $eqLogic->setConfiguration('transmissionFailures', $TransmFailures);
                    $eqLogic->setConfiguration('localZigbeeChannelPower', $results);
                    $eqLogic->save();
                }
            }

            parserLog2('debug', $srcAddr, '  Channels='.json_encode($results));
        } // End decode8002_MgmtNwkUpdateNotify()

        // Add group response (cluster 0004, cmd 00).
        function decode8002_AddGroupRsp($net, $srcAddr, $ep, $pl, $lqi, &$toAbeille) {
            $status = substr($pl, 0, 2);
            $grp = AbeilleTools::reverseHex(substr($pl, 2, 4));
            $m = '  Add a group response'
                .': Status='.$status.'/'.zbGetZCLStatus($status)
                .', GroupID='.$grp;
            parserLog2('debug', $srcAddr, $m);
            // $toMon[] = $m;

            if (($status == "00") || ($status == "8A")) { // Ok or duplicate
                $toAbeille[] = array(
                    // 'src' => 'parser',
                    'type' => 'addGroupResponse',
                    'net' => $net,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'groups' => $grp,
                    'time' => time(),
                    'lqi' => $lqi
                );
            }
        } // End decode8002_AddGroupRsp()

        // Get Group Membership response (cluster 0004, cmd 02)
        function decode8002_GetGroupMembership($net, $srcAddr, $ep, $pl, $lqi, &$toAbeille) {
            $capa = substr($pl, 0, 2);
            $grpCount = substr($pl, 2, 2);

            // Capacity meaning
            // 0 No further groups MAY be added.
            // 0 < Capacity < 0xfe Capacity holds the number of groups that MAY be added
            // 0xfe At least 1 further group MAY be added (exact number is unknown)
            // 0xff It is unknown if any further groups MAY be added

            $m = '  Get group membership response'
               .': Capacity='.$capa
               .', GroupCount='.$grpCount;
            parserLog2('debug', $srcAddr, $m);

            $groups = "";
            for ($i = 0; $i < hexdec($grpCount); $i++) {
                if ($i != 0)
                    $groups .= '/';
                $groups .= AbeilleTools::reverseHex(substr($pl, 4 + ($i * 4), 4));
            }
            if ($groups == "")
                $m = "  Groups: NONE";
            else
                $m = "  Groups: ".$groups;
            parserLog2('debug', $srcAddr, $m);
            // $toMon[] = $m;

            $updates = [];
            $updates['groups'] = $groups;
            $this->deviceUpdates($net, $srcAddr, $ep, $updates);

            $attributes = [];
            $attributes[] = array(
                'name' => 'Group-Membership',
                'value' => $groups,
            );
            $toAbeille[] = array(
                // 'src' => 'parser',
                'type' => 'attributesReportN',
                'net' => $net,
                'addr' => $srcAddr,
                'ep' => $ep,
                'clustId' => '0004',
                'attributes' => $attributes,
                'time' => time(),
                'lqi' => $lqi
            );
            $toAbeille[] = array(
                // 'src' => 'parser',
                'type' => 'getGroupMembershipResponse',
                'net' => $net,
                'addr' => $srcAddr,
                'ep' => $ep,
                'groups' => $groups,
                'time' => time(),
                'lqi' => $lqi
            );
        } // End decode8002_GetGroupMembership()

        // Remove group response (cluster 0004, cmd 03).
        function decode8002_RemoveGroupRsp($net, $srcAddr, $ep, $pl, $lqi, &$toAbeille) {
            $status = substr($pl, 0, 2);
            $grp = AbeilleTools::reverseHex(substr($pl, 2, 4));
            $m = '  Remove a group response'
                .': Status='.$status.'/'.zbGetZCLStatus($status)
                .', GroupID='.$grp;
            parserLog2('debug', $srcAddr, $m);
            // $toMon[] = $m;

            if ($status == "00") { // Ok
                $toAbeille[] = array(
                    // 'src' => 'parser',
                    'type' => 'removeGroupResponse',
                    'net' => $net,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'groups' => $grp,
                    'time' => time(),
                    'lqi' => $lqi
                );
            }
        } // End decode8002_RemoveGroupRsp()

        /**
         * 8002/Data indication decode function
         *
         * @param $dest     Zigbee network (ex: Abeille1)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8002($dest, $payload, $lqi) {

            // Decode first based on https://zigate.fr/documentation/commandes-zigate/
            $status         = substr($payload, 0, 2);
            $profId         = substr($payload, 2, 4);
            $clustId        = substr($payload, 6, 4);
            $srcEp          = substr($payload, 10, 2);
            $dstEp          = substr($payload, 12, 2);
            $srcAddrMode    = substr($payload, 14, 2);
            $srcAddr        = substr($payload, 16, 4);
            $dstAddrMode    = substr($payload, 20, 2);
            $dstAddr        = substr($payload, 22, 4);
            $pl = substr($payload, 26); // Keeping remaining payload

            // Log
            $msgDecoded = '8002/Data indication'
                            .', Status='.$status
                            .', ProfId='.$profId
                            .', ClustId='.$clustId
                            .", SrcEP=".$srcEp
                            .", DstEP=".$dstEp
                            .", SrcAddrMode=".$srcAddrMode
                            .", SrcAddr=".$srcAddr
                            .", DstAddrMode=".$dstAddrMode
                            .", DstAddr=".$dstAddr;
            parserLog2('debug', $srcAddr, $dest.', Type='.$msgDecoded, "8002");

            $devUpdates = []; // Device key infos updates: Sent at end of decode8002()
            $attrReportN = []; // Report attribute msg: Sent at end of decode8002()
            $readAttributesResponseN = []; // Read attr response msg: Sent at end of decode8002()
            $toAbeille = []; // Other messages type: Sent at end of decode8002()

            /* Note: the following decode can generate the following messages if required
               - toAbeille: a message for main daemon
               - readAttributesResponseN: Attributes for 'readAttributesResponseN' message to main daemon
               - attributesReportN: Attributes for 'attributesReportN' message to main daemon
               - toCli: a message for client side
               Note: Monitoring, if required, is done at end of function to give priority on decode+action.
             */

            // Profile 0000
            if ($profId == "0000") {
                // Device Announce (Device_annce)
                // Handled by decode004D(). Developped to follow https://github.com/fairecasoimeme/ZiGatev2/issues/36#
                if ($clustId == "0013") {
                    $sqn = substr($pl, 0, 2);
                    $addr = AbeilleTools::reverseHex(substr($pl, 2, 4));
                    $ieee = AbeilleTools::reverseHex(substr($pl, 6, 16));
                    $cap = substr($pl, 22, 2);
                    parserLog2('debug', $srcAddr, '  Device Announce: SQN='.$sqn.', Addr='.$addr.', IEEE='.$ieee.', Cap='.$cap);

                    // Store addr for https://github.com/fairecasoimeme/ZiGatev2/issues/36# work around
                    global $last8002DevAnnounce;
                    $last8002DevAnnounce = $addr;

                    parserLog2('debug', $srcAddr, '  Handled by decode004D');
                    // return;
                }

                // // Management Network Update Request (Mgmt_NWK_Update_req)
                // else if ($clustId == "0038") {
                //     // $this->decode8002_MgmtNwkUpdateReq($dest, $srcAddr, $pl, $toMon);
                // }

                // Netowrk addr response (NWK_addr_rsp)
                else if ($clustId == "8000") {
                    $this->decode8002_NwkAddrRsp($dest, $srcAddr, $pl, $lqi, $devUpdates, $toAbeille);
                }

                // IEEE addr response (IEEE_addr_rsp)
                else if ($clustId == "8001") {
                    $this->decode8002_IeeeAddrRsp($dest, $srcAddr, $pl, $lqi, $devUpdates, $toAbeille);
                }

                // Node Descriptor Response (Node_Desc_rsp)
                else if ($clustId == "8002") {
                    $this->decode8002_NodeDescRsp($dest, $srcAddr, $pl, $devUpdates);
                }

                // Simple descriptor response (Simple_Desc_rsp)
                else if ($clustId == "8004") {
                    $this->decode8002_SimpleDescRsp($dest, $srcAddr, $pl);
                }

                // Active Enpoints Response (Active_EP_rsp)
                else if ($clustId == "8005") {
                    $this->decode8002_ActiveEpRsp($dest, $srcAddr, $pl, $devUpdates);
                }

                // Bind Response (Bind_rsp, cluster=8021)
                else if ($clustId == "8021") {
                    $this->decode8002_BindRsp($dest, $srcAddr, $pl, $lqi, $toAbeille);
                }

                // Bind Response (Unbind_rsp, cluster=8022)
                else if ($clustId == "8022") {
                    $this->decode8002_UnbindRsp($dest, $srcAddr, $pl, $lqi, $toAbeille);
                }

                // Management LQI Response (Mgmt_Lqi_rsp)
                // No longer handled by decode804E() due to lack of reliability (see https://github.com/fairecasoimeme/ZiGate/issues/407)
                else if ($clustId == "8031") {
                    $this->decode8002_MgmtLqiRsp($dest, $srcAddr, $pl);
                }

                // Routing Table Response (Mgmt_Rtg_rsp)
                else if ($clustId == "8032") {
                    $this->decode8002_MgmtRtgRsp($dest, $srcAddr, $pl);
                }

                // Binding Table Response (Mgmt_Bind_rsp)
                else if ($clustId == "8033") {

                    /* Parser exemple
                    Abeille1, Type=8002/Data indication, Status=00, ProfId=0000, ClustId=8033, SrcEP=00, DstEP=00, SrcAddrMode=02, SrcAddr=9007, DstAddrMode=02, DstAddr=0000
                        Binding table response, SQN=12, Status=00, tableSize=2, index=0, tableCount=2
                        04CF8CDF3C77164B, 01, 0004 => EP01 @00158D0001ED3365
                        04CF8CDF3C77164B, 01, 0100 => EP01 @00158D0001ED3365 */

                    $sqn        = substr($pl, 0, 2);
                    $status     = substr($pl, 2, 2);
                    $tableSize  = hexdec(substr($pl, 4, 2));
                    $index      = hexdec(substr($pl, 6, 2));
                    $tableCount = hexdec(substr($pl, 8, 2));

                    // Duplicated message ?
                    if ($this->isDuplicated($dest, $srcAddr, '', $sqn))
                        return;

                    parserLog2('debug', $srcAddr, '  Binding table response'
                            .': SQN='.$sqn
                            .', Status='.$status
                            .', TableSize='.$tableSize
                            .', Idx='.$index
                            .', TableCount='.$tableCount, "8002");

                    $pl = substr($pl, 10);
                    for ($i = 0; $i < $tableCount; $i++) {
                        $srcIeee = AbeilleTools::reverseHex(substr($pl, 0, 16));
                        $srcEp  = substr($pl, 16, 2);
                        $clustId = AbeilleTools::reverseHex(substr($pl, 18, 4));
                        $dstAddrMode = substr($pl, 22, 2);
                        if ($dstAddrMode == "01") {
                            // 16-bit group address for DstAddr and DstEndpoint not present
                            $dstAddr = AbeilleTools::reverseHex(substr($pl, 24, 4));
                            parserLog2('debug', $srcAddr, '  '.$srcIeee.', EP '.$srcEp.', Clust '.$clustId.' => group '.$dstAddr);
                            $pl = substr($pl, 28);
                        } else if ($dstAddrMode == "03") {
                            // 64-bit extended address for DstAddr and DstEndp present
                            $destIeee = AbeilleTools::reverseHex(substr($pl, 24, 16));
                            $dstEp  = substr($pl, 40, 2);
                            parserLog2('debug', $srcAddr, '  '.$srcIeee.', EP '.$srcEp.', Clust '.$clustId.' => device '.$destIeee.', EP '.$dstEp);
                            $pl = substr($pl, 42);
                        } else {
                            parserLog2('debug', $srcAddr, '  ERROR: Unexpected DstAddrMode '.$dstAddrMode);
                            return;
                        }
                    }

                    // $updates = [];
                    $devUpdates['bindingTableSize'] = $tableSize;
                    // $this->deviceUpdates($dest, $srcAddr, $srcEp, $updates);
                } // End Mgmt_Bind_rsp

                // Management network update notify (Mgmt_NWK_Update_notify, cluster=8038)
                else if ($clustId == "8038") {
                    $this->decode8002_MgmtNwkUpdateNotify($dest, $srcAddr, $pl);
                }

                else {
                    switch ($clustId) {
                    case "0000":
                        parserLog2('debug', $srcAddr, '  NWK_addr_req => Handled by Zigate');
                        break;
                    case "0001":
                        parserLog2('debug', $srcAddr, '  IEEE_addr_req => Handled by Zigate');
                        break;
                    case "0013": // Device_annce
                        parserLog2('debug', $srcAddr, '  Device announce => Handled by decode004D');
                        break;
                    case "0038": // Mgmt_NWK_Update_req
                        parserLog2('debug', $srcAddr, '  Mgmt_NWK_Update_req => Ignored');
                        break;
                    default:
                        parserLog2('debug', $srcAddr, '  Unsupported/ignored profile 0000, cluster '.$clustId.' message');
                    }
                }
            } // End '$profId == "0000"'

            /*
             * Code hereafter is covering ZCL compliant messages.
             * Profiles: 0104/ZHA
             */

            else if ($profId !== "0104") {
                parserLog2('debug', $srcAddr, '  Unsupported/ignored profile '.$profId.' message');
                // return;
            }

            //  Profile 0104, cluster 0005 Scene (exemple: Boutons lateraux de la telecommande -)
            // else if ($clustId == "0005") {
            //     $frameCtrlField = substr($payload, 26, 2);
            //     parserLog("debug", '  Cluster 0005: FCF='.$frameCtrlField);

                // Tcharp38: WARNING: There is probably something wrong there.
                // There are cases where 0005 message is neither supported by this part nor by 8100_8102 decode.
                // Example:
                // [2021-08-30 16:44:31] Abeille1, Type=8002/Data indication, Status=00, ProfId=0104, ClustId=0005, SrcEP=01, DstEP=01, SrcAddrMode=02, SrcAddr=7C4F, DstAddrMode=02, DstAddr=0000
                // [2021-08-30 16:44:31]   FCF=08, SQN=7C, cmd=01/Read Attributes Response
                // [2021-08-30 16:44:31]   Handled by decode8100_8102
                // [2021-08-30 16:44:31] Abeille1, Type=8100/Read individual attribute response, SQN=7C, Addr=7C4F, EP=01, ClustId=0005, AttrId=0001, AttrStatus=00, AttrDataType=20, AttrSize=0001
                // [2021-08-30 16:44:31]   Processed in 8002 => dropped

                // $abeille = Abeille::byLogicalId($dest."/".$srcAddr,'Abeille');
                // $sceneStored = json_decode( $abeille->getConfiguration('sceneJson','{}') , True );

                // if ( $frameCtrlField=='05' ) {
                //     $Manufacturer   = substr($payload,30, 2).substr($payload,28, 2);
                //     if ( $Manufacturer=='117C' ) {

                //         $sqn                    = substr($payload,32, 2);
                //         $cmd                    = substr($payload,34, 2);
                //         if ( $cmd != "07" ) {
                //             parserLog("debug", '  Message can t be decoded. Looks like Telecommande Ikea Ronde but not completely.');
                //             return;
                //         }
                //         $remainingData          = substr($payload,36, 8);
                //         $value                  = substr($payload,36, 2);

                //         parserLog("debug", '  Telecommande Ikea Ronde'
                //                        .', frameCtrlField='.$frameCtrlField
                //                        .', Manufacturer='.$Manufacturer
                //                        .', SQN='.$sqn
                //                        .', cmd='.$cmd
                //                        .', value='.$value
                //                         );

                //         // $this->msgToAbeille($dest."/".$srcAddr, $clustId.'-'.$srcEp, '0000', $value);
                //         $attrReportN = [
                //             array( "name" => $clustId.'-'.$srcEp.'-0000', "value" => $value ),
                //         ];
                //         // return;
                //     }
                // }

                // Tcharp38: Moved this part in ZCL global commands decode with questions.
                // if ( $frameCtrlField=='18' ) { // Default Resp: 1 / Direction: 1 / Manu Specific: 0 / Cluster Specific: 00
                //     $sqn                    = substr($payload,28, 2);
                //     $cmd                    = substr($payload,30, 2);
                //     parserLog("debug", '  cmd :'.$cmd );

                    // if ($cmd=="01") { // Read Attribut
                    //     $attribut           = substr($payload,34, 2).substr($payload,32, 2);
                    //     $status             = substr($payload,36, 2);
                    //     if ( $status != "00" ) {
                    //         parserLog("debug", '  Attribut Read Status error.');
                    //         return;
                    //     }
                        // if ( $attribut == "0000" ) {
                        //     $dataType   = substr($payload,38, 2);
                        //     $sceneCount = substr($payload,40, 2);
                        //     $sceneStored["sceneCount"]           = $sceneCount-1; // On ZigLight need to remove one
                        //     $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        //     $abeille->save();

                        //     parserLog("debug", '  '.json_encode($sceneStored) );

                        //     return;
                        // }

                        // if ( $attribut == "0001" ) {
                        //     $dataType   = substr($payload,38, 2);
                        //     $sceneCurrent = substr($payload,40, 2);
                        //     $sceneStored["sceneCurrent"]           = $sceneCurrent;
                        //     $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        //     $abeille->save();

                        //     parserLog("debug", '  '.json_encode($sceneStored) );

                        //     return;
                        // }

                        // if ( $attribut == "0002" ) {
                        //     $dataType       = substr($payload,38, 2);
                        //     $groupCurrent   = substr($payload,40, 4);
                        //     $sceneStored["groupCurrent"]           = $groupCurrent;
                        //     $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        //     $abeille->save();

                        //     parserLog("debug", '  '.json_encode($sceneStored) );

                        //     return;
                        // }

                        // if ( $attribut == "0003" ) {
                        //     $dataType   = substr($payload,38, 2);
                        //     $sceneActive = substr($payload,40, 2);
                        //     $sceneStored["sceneActive"]           = $sceneActive;
                        //     $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        //     $abeille->save();

                        //     parserLog("debug", '  '.json_encode($sceneStored) );

                        //     return;
                        // }

                    //     parserLog("debug", '  Attribut inconnu :'.$attribut );
                    // }
                // }

                // Tcharp38: Moved to cluster specific ZCL decode
                // else if ( $frameCtrlField=='19' ) { // Default Resp: 1 / Direction: 1 / Manu Specific: 0 / Cluster Specific: 01
                //     $sqn                    = substr($payload,28, 2);
                //     $cmd                    = substr($payload,30, 2);

                //     // Add Scene Response
                //     if ( $cmd == "00" ) {
                //         $sceneStatus            = substr($payload,32, 2);

                //         if ( $sceneStatus != "00" ) {
                //             parserLog("debug", '  Status error dd Scene Response.');
                //             return;
                //         }

                //         $groupID          = substr($payload,34, 4);
                //         $sceneId          = substr($payload,36, 2);

                //         parserLog("debug", '  Add Scene Response confirmation (decoded but not processed) Please refresh with a -Get Scene Membership- : group: '.$groupID.' - scene id:'.$sceneId );
                //         return;
                //     }

                //     // View Scene Response
                //     else if ( $cmd == "01" ) {
                //         $sceneStatus            = substr($payload,32, 2);
                //         if ( $sceneStatus != "00" ) {
                //             parserLog("debug", '  Status error on scene info.');
                //             return;
                //         }
                //         $groupID            = substr($payload,34, 4);
                //         $sceneId            = substr($payload,38, 2);
                //         $transitionTime     = substr($payload,40, 4);
                //         $Length             = substr($payload,44, 2);
                //         $statusRouting      = "";
                //         $extensionSet       = ""; // 06:00:01:01:08:00:01:fe:00:03:04:13:ae:eb:51 <- to be investigated

                //         parserLog("debug", '  View Scene Response: '.$groupID.' - '.$sceneId.' ...');
                //         return;
                //     }

                //     // Remove scene response
                //     else if ( $cmd == "02" ) {
                //         $sceneStatus            = substr($payload,32, 2);
                //         if ( $sceneStatus != "00" ) {
                //             parserLog("debug", '  Status error on scene info.');
                //             return;
                //         }
                //         $groupID            = substr($payload,34, 4);
                //         $sceneId            = substr($payload,38, 2);

                //         parserLog("debug", '  Scene: '.$sceneId.' du groupe: '.$groupID.' a ete supprime.');
                //         return;
                //     }

                //     // Remove All Scene Response
                //     else if ( $cmd == "03" ) {
                //         $sceneStatus            = substr($payload,32, 2);
                //         if ( $sceneStatus != "00" ) {
                //             parserLog("debug", '  Status error Remove All Scene Response.');
                //             return;
                //         }
                //         $groupID                = substr($payload,36, 2).substr($payload,34, 2);

                //         unset($sceneStored["sceneRemainingCapacity"]);
                //         unset($sceneStored["sceneCount"]);
                //         unset($sceneStored["GroupeScene"][$groupID]);
                //         $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                //         $abeille->save();
                //     }

                //     // Store Scene Response
                //     else if ( $cmd == "04" ) {
                //         $sceneStatus            = substr($payload,32, 2);

                //         if ( $sceneStatus != "00" ) {
                //             parserLog("debug", '  Status error Store Scene Response.');
                //             return;
                //         }

                //         $groupID          = substr($payload,34, 4);
                //         $sceneId          = substr($payload,36, 2);

                //         parserLog("debug", '  store scene response confirmation (decoded but not processed) Please refresh with a -Get Scene Membership- : group: '.$groupID.' - scene id:'.$sceneId );
                //         return;
                //     }

                //     // Get Scene Membership Response
                //     else if ( $cmd == "06" ) {
                //         $sceneStatus            = substr($payload,32, 2);
                //         if ( $sceneStatus == "85" ) {  // Invalid Field
                //             $sceneRemainingCapacity = substr($payload,34, 2);
                //             $groupID                = substr($payload,36, 4);

                //             parserLog("debug", "  scene: scene capa:".$sceneRemainingCapacity.' - group: '.$groupID );

                //             $sceneStored["sceneRemainingCapacity"]        = $sceneRemainingCapacity;
                //             unset( $sceneStored["GroupeScene"][$groupID] );

                //             $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                //             $abeille->save();

                //             parserLog("debug", '  '.json_encode($sceneStored) );

                //             // $sceneStored = json_decode( Abeille::byLogicalId($dest."/".$srcAddr,'Abeille')->getConfiguration('sceneJson','{}') , True );
                //             // parserLog("debug", $dest.', Type=8002/Data indication ---------------------> '.json_encode($sceneStored) );
                //             return;
                //         }
                //         if ( $sceneStatus != "00" ) {
                //             parserLog("debug", '  Status error on scene info.');
                //             return;
                //         }

                //         $sceneRemainingCapacity = substr($payload,34, 2);
                //         $groupID                = substr($payload,38, 2).substr($payload,36, 2);
                //         $sceneCount             = substr($payload,40, 2);
                //         $sceneId = "";
                //         for ($i = 0; $i < hexdec($sceneCount); $i++) {
                //             $sceneId .= '-'.substr($payload,42+$i*2, 2);
                //         }

                //         parserLog("debug", "  scene capa:".$sceneRemainingCapacity.' - group: '.$groupID.' - scene count:'.$sceneCount.' - scene id:'.$sceneId );

                //         $sceneStored["sceneRemainingCapacity"]        = $sceneRemainingCapacity;
                //         $sceneStored["sceneCount"]                    = $sceneCount;
                //         $sceneStored["GroupeScene"][$groupID]["sceneId"]             = $sceneId;
                //         $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                //         $abeille->save();

                //         parserLog("debug",  '  '.json_encode($sceneStored) );

                //         return;
                //     }

                //     else {
                //         parserLog("debug",  '  Message can t be decoded. Cmd unknown.');
                //         return;
                //     }
                // }
            // }

            // $cmd = substr($payload, 30, 2);

            // // Tcharp38: What is cmd 'FD' ?? Can we remove ?
            // else if (($clustId == "0008") && (substr($payload, 30, 2) == "FD")) {

            //     $frameCtrlField         = substr($payload,26, 2);
            //     $sqn                    = substr($payload,28, 2);
            //     // $cmd                    = substr($payload,30, 2); if ( $cmd != "FD" ) return;
            //     $value                  = substr($payload,32, 2);

            //     parserLog('debug', '  '
            //                    .', frameCtrlField='.$frameCtrlField
            //                     .', SQN='.$sqn
            //                     .', cmd='.$cmd
            //                     .', value='.$value,
            //                      "8002"
            //                     );

            //     // $this->msgToAbeille($dest."/".$srcAddr, $clustId.'-'.$srcEp, '0000', $value);
            //     $attrReportN = [
            //         array( "name" => $clustId.'-'.$srcEp.'-0000', "value" => $value ),
            //     ];
            //     // return;
            // }

            // Tcharp38: TODO: Move to ZCL global or cluster specific part
            // WARNING: KEEP 'else if' here
            else if ($clustId == "0204") {
            } // End profile 0104 cluster 0204

            // Remontée etat relai module Legrand 20AX
            // 80020019F4000104 FC41 010102D2B9020000180B0A000030000100100084
            // Tcharp38: TODO: Move to ZCL global or cluster specific part
            else if ($clustId == "FC41") {

                $frameCtrlField         = substr($payload,26, 2);
                $sqn                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd == "0a" ) $cmd = "0a - report attribut";

                $attribute              = substr($payload,34, 2).substr($payload,32, 2);
                $dataType               = substr($payload,36, 2);
                $value                  = substr($payload,38, 2);

                parserLog2('debug', $srcAddr, '  Remontée etat relai module Legrand 20AX '
                                   .', frameCtrlField='.$frameCtrlField
                                   .', SQN='.$sqn
                                   .', cmd='.$cmd
                                   .', attribute='.$attribute
                                   .', dataType='.$dataType
                                   .', value='.$value.' - '.hexdec($value),
                                    "8002"
                                    );

                // $this->msgToAbeille($dest."/".$srcAddr, $clustId.'-'.$dstEp, $attribute, hexdec($value));
                $attrReportN = [
                    array( "name" => $clustId.'-'.$dstEp.'-'.$attribute, "value" => hexdec($value) ),
                ];

                // if ($this->debug["8002"]) $this->deamonlog('debug', 'lenght: '.strlen($payload) );
                if ( strlen($payload)>42 ) {
                    $attribute              = substr($payload,42, 2).substr($payload,40, 2);
                    $dataType               = substr($payload,44, 2);
                    $value                  = substr($payload,46, 2);

                    parserLog2('debug', $srcAddr, '  Remontée etat relai module Legrand 20AX '
                                   .', frameCtrlField='.$frameCtrlField
                                   .', SQN='.$sqn
                                   .', cmd='.$cmd
                                   .', attribute='.$attribute
                                   .', dataType='.$dataType
                                   .', value='.$value.' - '.hexdec($value),
                                    "8002"
                                    );

                    // $this->msgToAbeille($dest."/".$srcAddr, $clustId.'-'.$dstEp, $attribute, hexdec($value));
                    $attrReportN[] = [
                        array( "name" => $clustId.'-'.$dstEp.'-'.$attribute, "value" => hexdec($value) ),
                    ];
                }

                // return;
            } // End profile 0104 cluster FC41

            /* WARNING:
               If execution reached here it is assumed that message is "Zigbee cluster library" compliant
               and NOT treated above.
               Compliant profiles: 0104 (ZHA)
             */

            else {
                /* Decoding ZCL header */
                $fcf = substr($payload, 26, 2); // Frame Control Field
                $frameType = hexdec($fcf) & 3; // Bits 0 & 1: 00=global, 01=cluster specific
                $manufSpecific = (hexdec($fcf) >> 2) & 1;
                $dir = (hexdec($fcf) >> 3) & 1; // 1=sent from server side, 0=sent from client side
                if ($frameType == 0)
                    $fcfTxt = "General";
                else
                    $fcfTxt = "Cluster-specific";
                if ($dir)
                    $fcfTxt .= "/Serv->Cli";
                else
                    $fcfTxt .= "/Cli->Serv";
                if ($manufSpecific) {
                    $manufCode = AbeilleTools::reverseHex(substr($payload, 28, 4)); // 16bits for manuf specific code
                    $fcfTxt .= "/ManufCode=".$manufCode;
                    $sqn = substr($payload, 32, 2); // Sequence Number
                    $cmd = substr($payload, 34, 2); // Command
                    $msg = substr($payload, 36); // TODO: TO BE REMOVED => replaced by $pl
                    $pl = substr($payload, 36);
                } else {
                    $manufCode = '';
                    $sqn = substr($payload, 28, 2); // Sequence Number
                    $cmd = substr($payload, 30, 2); // Command
                    $msg = substr($payload, 32); // TODO: TO BE REMOVED => replaced by $pl
                    $pl = substr($payload, 32);
                }
                if ($frameType == 0) // General command
                    $m = "  FCF=".$fcf."/".$fcfTxt.", SQN=".$sqn.", cmd=".$cmd.'/'.zbGetZCLGlobalCmdName($cmd);
                else { // Cluster specific command
                    $cmdName = zbGetZCLClusterCmdName($clustId, $cmd, $dir);
                    $m = "  FCF=".$fcf."/".$fcfTxt.", SQN=".$sqn.", cmd=".$cmd.'/'.$cmdName;
                }
                parserLog2('debug', $srcAddr, $m);

                // Getting corresponding device or create if unknown
                $eq = getDevice($dest, $srcAddr);

                if ($frameType == 0) { // General command
                    /*
                    * General ZCL command
                    */

                    /* General 'Cmd' reminder
                        0x00 Read Attributes
                        0x01 Read Attributes Response
                        0x04 Write Attributes Response
                        0x05 Write Attributes No Response
                        0x07 Configure Reporting Response
                        0x09 Read Reporting Configuration Response
                        0x0a Report attributes
                        0x0b Default Response
                        0x0d Discover Attributes Response
                        0x12 Discover Commands Received Response
                        0x13 Discover Commands Generated
                        0x14 Discover Commands Generated Response
                        0x16 Discover Attributes Extended Response
                    */
                    if ($cmd == "00") { // Read Attributes
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $l = strlen($msg);
                        $attributes = "";
                        for ($i = 0; $i < $l; $i += 4) {
                            $attrId = AbeilleTools::reverseHex(substr($msg, $i, 4));
                            if ($i != 0)
                                $attributes .= "/";
                            $attributes .= $attrId;

                            if ($clustId == "000A") { // Time cluster
                                /* Memo time cluster
                                - UTCTime is an unsigned 32-bit value representing the number of seconds since 0 hours, 0 minutes, 0 seconds, 3705 on the 1st of January, 2000 UTC (Universal Coordinated Time).
                                */

                                if ($attrId == "0000") {
                                    // The Time attribute is 32 bits in length and holds the time value of a real time clock.
                                    parserLog2('debug', $srcAddr, "  Attribute 0000/Time: Zigate responding");
                                } else if ($attrId == "0002") { // TimeZone
                                    // The TimeZone attribute indicates the local time zone, as a signed offset in seconds from the Time attribute value.
                                    // Standard Time = Time + TimeZone
                                    // TO BE REVISITED as currently respondig 0
                                    $timeZone = zbFormatData(0, "2B"); // int32
                                    msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/sendReadAttributesResponse", 'ep='.$srcEp.'&clustId='.$clustId.'&attrId='.$attrId.'&status=00&attrType=2B&attrVal='.$timeZone);
                                    parserLog2('debug', $srcAddr, "  Attribute 0002/TimeZone: Answering 0 to device");
                                } else if ($attrId == "0007") { // LocalTime
                                    // Reminder: Zigbee uses 00:00:00 @1st of jan 2000 as ref
                                    //           PHP uses 00:00:00 @1st of jan 1970 (Linux ref)
                                    // Attr 0007, type uint32/0x23
                                    $lt = localtime(null, true);
                                    $localTime = @mktime($lt['tm_hour']);
                                    if ($localTime === false) { // mktime(): Epoch doesn't fit in a PHP integer
                                        parserLog2('error', $srcAddr, "  Attribute 0007/LocalTime: CAN'T answer to device. mktime() failed.");
                                    } else {
                                        $localTime -= mktime(0, 0, 0, 1, 1, 2000); // PHP to Zigbee shift
                                        $localTime = sprintf("%04X", $localTime);
                                        msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/sendReadAttributesResponse", 'ep='.$srcEp.'&clustId='.$clustId.'&attrId='.$attrId.'&status=00&attrType=23&attrVal='.$localTime);
                                        parserLog2('debug', $srcAddr, "  Attribute 0007/LocalTime: Answering to device");
                                    }
                                } else {
                                    parserLog2('debug', $srcAddr, "  WARNING: Unsupported time cluster attribute ".$attrId);
                                }
                            }
                        }

                        if ($clustId != '000A') {
                            parserLog2('debug', $srcAddr, "  Attributes: ".$attributes);
                        }
                    } // End 'Read Attributes' (cmd 00)

                    else if ($cmd == "01") { // Read Attributes Response
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        /* Command frame format:
                            ZCL header
                            Read attribut status record 1
                            ...
                            Read attribut status record n */
                        /* Attributes status record format:
                            Attr id = 2B
                            Status = 1B
                            Attr data type = 1B
                            Attr = <according to data type> */

                        $attributes = [];
                        // $devUpdates = []; // Any device information update (devUpdates[updId] = updValue)
                        $l = strlen($pl);
                        // $eq = getDevice($dest, $srcAddr); // Corresponding device
                        $size = 0;
                        for ($i = 0; $i < $l; $i += $size) {
                            $attr = $this->decode8002_ReadAttrStatusRecord($srcAddr, substr($pl, $i), $size);
                            if ($attr === false)
                                break; // Stop decode there

                            // Attribute value post correction according to ZCL spec
                            $correct = ['0001-0020', '0001-0021', '0201-0000', '0201-0012', '0300-0007', '0400-0000', '0402-0000', '0403-0000', '0405-0000'];
                            if (in_array($clustId.'-'.$attr['id'], $correct))
                                $this->decode8002_ZCLCorrectAttrValue($srcEp, $clustId, $eq, $attr);

                            // If cluster 0000, attributes manufId/modelId or location.. need to clean string
                            if ($clustId == "0000") {
                                if (($attr['id'] == "0005") || ($attr['id'] == "0010")) {
                                    $attr['value'] = $this->cleanModelId($attr['valueHex']);
                                    $attr['comment'] = "cleaned model";
                                } else if ($attr['id'] == "0004") {
                                    $attr['value'] = $this->cleanManufId($attr['value']);
                                    $attr['comment'] = "cleaned manuf";
                                }
                            }

                            $attrName = zbGetZCLAttributeName($clustId, $attr['id']);
                            if ($attr['status'] == '00') {
                                $m = '  AttrId='.$attr['id'].'/'.$attrName
                                    .', Status='.$attr['status']
                                    .', AttrType='.$attr['dataType']
                                    .', ValueHex='.$attr['valueHex'].' => ';
                                if (isset($attr['comment']))
                                    $m .= $attr['comment'].', '.$attr['value'];
                                else
                                    $m .= $attr['value'];
                            } else
                                $m = '  AttrId='.$attr['id'].'/'.$attrName
                                    .', Status='.$attr['status']
                                    .'/'.zbGetZCLStatus($attr['status']);
                            parserLog2('debug', $srcAddr, $m, "8002");

                            $attrId = $attr['id'];
                            unset($attr['id']); // Remove 'id' from object for optimization
                            $attributes[$attrId] = $attr;

                            $readAttributesResponseN[] = array(
                                'name' => $clustId.'-'.$srcEp.'-'.$attrId,
                                'value' => $attr['value'],
                            );

                            if (in_array($clustId.'-'.$attrId, ['0000-0004', '0000-0005', '0000-0006', '0000-0010', '0000-4000']))
                                $devUpdates[$clustId.'-'.$attrId] = $attr['value'];

                            if (count($attributes) != 0) {
                                // If discovering step, recording infos
                                $discovering = $this->discoveringState($dest, $srcAddr);
                                if ($discovering) {
                                    $isServer = (hexdec($fcf) >> 3) & 1;
                                    $this->discoverUpdate($dest, $srcAddr, $srcEp, 'ReadAttributesResponse', $clustId, $isServer, $attributes);
                                }

                                /* Send to client page if required (ex: EQ page opened) */
                                $toCli = array(
                                    // 'src' => 'parser',
                                    'type' => 'readAttributesResponse',
                                    'net' => $dest,
                                    'addr' => $srcAddr,
                                    'ep' => $srcEp,
                                    'clustId' => $clustId,
                                    'attributes' => $attributes,
                                    'time' => time(),
                                    'lqi' => $lqi
                                );
                            }

                            /* Tcharp38: Cluster 0005 specific case.
                                TO BE REVISITED to remove DB accesses from parser. */
                            if ($clustId == "0005") {
                                $eqLogic = Abeille::byLogicalId($dest."/".$srcAddr, 'Abeille');
                                if (is_object($eqLogic)) {
                                    $sceneStored = json_decode($eqLogic->getConfiguration('sceneJson', '{}'), true);
                                    foreach ($attributes as $attrId => $attr) {
                                        if ($attrId == "0000") {
                                            $sceneCount = $attr['value'];
                                            $sceneStored["sceneCount"] = $sceneCount - 1; // On ZigLight need to remove one
                                        } else if ($attrId == "0001") {
                                            $sceneCurrent = $attr['value'];
                                            $sceneStored["sceneCurrent"] = $sceneCurrent;
                                        } else if ($attrId == "0002") {
                                            $groupCurrent = $attr['value'];
                                            $sceneStored["groupCurrent"] = $groupCurrent;
                                        } else if ($attrId == "0003") {
                                            $sceneActive = $attr['value'];
                                            $sceneStored["sceneActive"] = $sceneActive;
                                        }
                                    }
                                    $eqLogic->setConfiguration('sceneJson', json_encode($sceneStored));
                                    $eqLogic->save();
                                    // Tcharp38: To be removed. Saving to DB slows down execution time a lot
                                    parserLog2("debug", $srcAddr, '  TODO: '.json_encode($sceneStored));
                                }
                                // return;
                            }

                            // Philips Hue specific cluster
                            // Used by RWL021, RDM001
                            // TODO: Private case to be revisited
                            if ($clustId == "FC00") {
                                $buttonEventTxt = array (
                                    '00' => 'Short press',
                                    '01' => 'Long press',
                                    '02' => 'Release short press',
                                    '03' => 'Release long press',
                                );
                                $attrId = substr($pl, 2, 2).substr($pl, 0, 2);
                                $button = $attrId;
                                // $buttonEvent = substr($payload, 24 + 2, 2);
                                $buttonEvent = substr($pl, 0 + 2, 2);
                                // $buttonDuree = hexdec(substr($payload, 24 + 6, 2));
                                $buttonDuree = hexdec(substr($pl, 0 + 6, 2));
                                parserLog2("debug", $srcAddr, "  TOBEREVISITED: Philips Hue proprietary: Button=".$button.", Event=".$buttonEvent." (".$buttonEventTxt[$buttonEvent]."), duration=".$buttonDuree);

                                $attrReportN = [
                                    array( "name" => $clustId."-".$srcEp."-".$attrId."-Event", "value" => $buttonEvent ),
                                    array( "name" => $clustId."-".$srcEp."-".$attrId."-Duree", "value" => $buttonDuree ),
                                ];
                            } // End cluster FC00
                        }
                    } // End '$cmd == "01"'

                    else if ($cmd == "04") { // Write Attributes Response
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $l = strlen($msg);
                        for ($i = 0; $i < $l; ) {
                            $status = substr($msg, $i + 0, 2);
                            $attrId = AbeilleTools::reverseHex(substr($msg, $i + 2, 4));

                            $m = "  Attr=".$attrId.", Status=".$status.'/'.zbGetZCLStatus($status);
                            parserLog2('debug', $srcAddr, $m);

                            $i += 6;
                        }
                    } // End '$cmd == "04"'

                    else if ($cmd == "07") { // Configure Reporting Response
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $l = strlen($pl);
                        for ($i = 0; $i < $l; ) {
                            $status = substr($pl, $i + 0, 2);
                            $dir = substr($pl, $i + 2, 2);
                            $attrId = AbeilleTools::reverseHex(substr($pl, $i + 4, 4));

                            $m = "  Status=".$status.'/'.zbGetZCLStatus($status).", Attr=".$attrId.", Dir=".$dir;
                            parserLog2('debug', $srcAddr, $m);

                            $i += 8;
                        }
                    } // End '$cmd == "07"'

                    else if ($cmd == "09") { // Read Reporting Configuration Response
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $status = substr($pl, 0, 2);
                        $dir = substr($pl, 2, 2);
                        $attrId = substr($pl, 6, 2).substr($pl, 4, 2);
                        if ($status == "00") {
                            $l = strlen($pl);
                            $attrType = substr($pl, 8, 2);
                            $minInterval = AbeilleTools::reverseHex(substr($pl, 10, 4));
                            $maxInterval = AbeilleTools::reverseHex(substr($pl, 14, 4));

                            // Reportable change => Variable size
                            // Note: no reportable change for boolean type
                            $attrSize = zbGetDataSize($attrType);
                            // parserLog2('debug', $srcAddr, "  AttrSize=$attrSize");
                            $change = AbeilleTools::reverseHex(substr($pl, 18, $attrSize * 2)); // Reverse for attrSize>1

                            parserLog2('debug', $srcAddr, '  Status='.$status.'/'.zbGetZCLStatus($status).', Dir='.$dir.', AttrId='.$attrId
                                .', AttrType='.$attrType.', MinInterval='.$minInterval.', MaxInterval='.$maxInterval.", Change=$change");
                        } else {
                            parserLog2('debug', $srcAddr, '  Status='.$status.'/'.zbGetZCLStatus($status).', Dir='.$dir.', AttrId='.$attrId);
                        }
                    } // End 'Read Reporting Configuration Response'

                    else if ($cmd == "0A") { // Report attributes
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        // if (isset($eq['private']))
                        //     parserLog2('debug', $srcAddr, "  Model with 'private' entry");

                        $updates = [];
                        while (($l = strlen($pl) > 0)) {
                            // Decode attribute
                            // $plOld = $pl; // Saving pl for old way support
                            $attr = $this->decode8002_ReportAttribute($srcAddr, $pl, $skipSize);
                            if ($attr === false)
                                break;
                            $pl = substr($pl, $skipSize);
                            $attrId = $attr['id'];

                            // Is this an attribute to handle as 'private' ?

                            // unset($private); // Clear in case it is set by previous attribute
                            // // parserLog('debug', 'LA eq='.json_encode($eq));
                            // if (isset($eq['private'])) {
                            //     // parserLog('debug', "  Model with 'private' entry");
                            //     foreach ($eq['private'] as $pKey => $pVal) {
                            //         $pKeyLen = strlen($pKey);
                            //         // parserLog2('debug', $srcAddr, "  clustId/attrId={$clustId}/{$attrId} vs pKey={$pKey}, len={$pKeyLen}");
                            //         if ((($pKeyLen == 4) && ($pKey != $clustId)) || // 'CCCC' (clustId) case
                            //             (($pKeyLen == 9) && ($pKey != $clustId.'-'.$attrId))) // 'CCCC-AAAA (clustId-attrId) case
                            //             continue;

                            //         $private = $pVal;
                            //         parserLog2('debug', $srcAddr, "  Private=".json_encode($private, JSON_UNESCAPED_SLASHES));
                            //         break;
                            //     }
                            // }

                            // Handling attribute
                            // if (isset($private)) {
                            //     // parserLog('debug', "  This cluster/attrib must be handled as private");
                            //     if ($private['type'] == "xiaomi") {
                            //         xiaomiReportAttribute($dest, $srcAddr, $clustId, $attr, $attrReportN);
                            //     } else {
                            //         parserLog2("error", $srcAddr, "  Ouahh... missing code to handle private cluster");
                            //     }
                            // }
                            if (isset($eq['private']) && isset($eq['private']["$clustId-$attrId"])) {
                                if ($eq['private']["$clustId-$attrId"]['type'] == "xiaomi") {
                                    xiaomiReportAttribute($dest, $srcAddr, $clustId, $attr, $attrReportN);
                                } else {
                                    parserLog2("error", $srcAddr, "  Ouahh... missing code to handle private cluster");
                                }
                            } else {
                                // Not private or obsolete way to handle this attribute as private

                                // if ($manufCode == '115F') { // Xiaomi specific
                                //     parserLog('warning', "  Old way (manufCode) to handle Xiaomi private cluster");
                                //     // parserLog('debug', "  plOld={$plOld}");
                                //     xiaomiReportAttributeOld($dest, $srcAddr, $clustId, $plOld, $attrReportN);
                                //     // $pl = ''; // Full payload treated
                                // }
                                if (($manufCode == '115F') || ($clustId == 'FCC0') || ($clustId == 'FF01')) { // Xiaomi specific: displaying debug infos
                                    $attrType = $attr['dataType'];
                                    if (($attrType == "41") || ($attrType == "42")) // Even if unhandled, displaying debug infos
                                        xiaomiDecodeTagsDebug($dest, $srcAddr, $clustId, $attrId, $attr['valueHex']);
                                }

                                // else if (isset($eq['xiaomi']) && isset($eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId])) { // Xiaomi specific without manufCode
                                //     parserLog('warning', "  Old way (fromDevice) to handle Xiaomi private cluster");
                                //     xiaomiReportAttributeOld($dest, $srcAddr, $clustId, $plOld, $attrReportN);
                                //     // $pl = ''; // Full payload treated
                                // }

                                // FC00 private cluster
                                // Used by Philips Hue on RWL021, RDM001
                                // Used by Nodon at least on SIN-4-FP-21
                                if ($clustId == "FC00") {
                                    $buttonEventTxt = array (
                                        '00' => 'Short press',
                                        '01' => 'Long press',
                                        '02' => 'Release short press',
                                        '03' => 'Release long press',
                                    );
                                    $button = $attrId;
                                    // $buttonEvent = substr($payload, 24 + 2, 2);
                                    $buttonEvent = substr($pl, 0 + 2, 2);
                                    if (isset($buttonEventTxt[$buttonEvent]))
                                        $buttonEventTxt = $buttonEventTxt[$buttonEvent];
                                    else
                                        $buttonEventTxt = "?";
                                    // $buttonDuree = hexdec(substr($payload, 24 + 6, 2));
                                    $buttonDuree = hexdec(substr($pl, 0 + 6, 2));
                                    parserLog2("debug", $srcAddr, "  TOBEREVISITED: Philips Hue proprietary: Button=".$button.", Event=".$buttonEvent."/".$buttonEventTxt."), duration=".$buttonDuree);

                                    $attrReportN[] = array( "name" => $clustId."-".$srcEp."-".$attrId."-Event", "value" => $buttonEvent );
                                    $attrReportN[] = array( "name" => $clustId."-".$srcEp."-".$attrId."-Duree", "value" => $buttonDuree );
                                } // End cluster FC00

                                // Attribute value post correction according to ZCL spec
                                $correct = ['0001-0020', '0001-0021', '0201-0000', '0201-0012', '0300-0007', '0400-0000', '0402-0000', '0403-0000', '0405-0000'];
                                if (in_array($clustId.'-'.$attr['id'], $correct))
                                    $this->decode8002_ZCLCorrectAttrValue($srcEp, $clustId, $eq, $attr);

                                // If cluster 0000, attributes manufId/modelId or location.. need to clean string
                                if ($clustId == "0000") {
                                    if (($attr['id'] == "0005") || ($attr['id'] == "0010")) {
                                        $attr['value'] = $this->cleanModelId($attr['valueHex']);
                                        $attr['comment'] = "cleaned model";
                                    } else if ($attr['id'] == "0004") {
                                        $attr['value'] = $this->cleanManufId($attr['value']);
                                        $attr['comment'] = "cleaned manuf";
                                    }
                                    if (($attr['id'] == '0004') || ($attr['id'] == '0005') || ($attr['id'] == '0010'))
                                        $updates[$clustId.'-'.$attr['id']] = $attr['value'];
                                }
                                if ($clustId == "0000") {
                                    if ($attr['id'] == '0008')
                                        $updates[$clustId.'-'.$attr['id']] = $attr['value'];
                                }

                                // Log
                                $attrName = zbGetZCLAttributeName($clustId, $attr['id']);
                                $m = '  AttrId='.$attr['id'].'/'.$attrName
                                    .', AttrType='.$attr['dataType']
                                    .', ValueHex='.$attr['valueHex'].' => ';
                                if (isset($attr['comment']))
                                    $m .= $attr['comment'].', '.$attr['value'];
                                else
                                    $m .= $attr['value'];
                                parserLog2('debug', $srcAddr, $m, "8002");

                                $attrReportN[] = array(
                                    'name' => $clustId.'-'.$srcEp.'-'.$attr['id'],
                                    'value' => $attr['value'],
                                );
                            }
                        }

                        $unknown = false;
                        if (count($updates) != 0)
                            $unknown = $this->deviceUpdates($dest, $srcAddr, $srcEp, $updates);
                        else if ($eq['status'] == "identifying")
                            // No updates but device still in ident phase. May need infos
                            $unknown = $this->deviceUpdates($dest, $srcAddr, $srcEp);
                        if ($unknown)
                            return; // So far unknown to Jeedom
                    } // End 'Report attributes'

                    else if ($cmd == "0B") { // Default Response
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $cmdId = substr($msg, 0, 2);
                        $status = substr($msg, 2, 2);

                        parserLog2('debug', $srcAddr, "  Default Response: Cmd={$cmdId}, Status={$status}/".zbGetZCLStatus($status));

                        /* Send to client if connection opened */
                        $toCli = array(
                            // 'src' => 'parser',
                            'type' => 'defaultResponse',
                            'net' => $dest,
                            'addr' => $srcAddr,
                            'ep' => $srcEp,
                            'clustId' => $clustId,
                            'fromServer' => $dir,
                            'cmd' => $cmdId,
                            'status' => $status
                        );
                    }

                    else if ($cmd == "0D") { // Discover Attributes Response
                        // Duplicated message ?
                        // if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                        //     return;
                        // Tcharp38: Need to check how to deal with 'completed' flag

                        parserLog2('debug', $srcAddr, "  Discover Attributes Response");

                        $completed = substr($msg, 2);
                        $msg = substr($msg, 2); // Skipping 'completed' status

                        // parserLog('debug', "  msg=".$msg);
                        $attributes = [];
                        // 'Attributes' => []; // Attributes
                        //      $attr['id']
                        //      $attr['dataType']
                        $l = strlen($msg);
                        for ($i = 0; $i < $l;) {
                            $attrId = substr($msg, $i + 2, 2).substr($msg, $i, 2);
                            $attr = array(
                                'dataType' => substr($msg, $i + 4, 2)
                            );
                            $attributes[$attrId] = $attr;
                            $i += 6;
                        }

                        $m = '';
                        foreach ($attributes as $attrId => $attr) {
                            if ($m != '')
                                $m .= '/';
                            $m .= $attrId;
                        }
                        parserLog2('debug', $srcAddr, '  Clust '.$clustId.': '.$m);

                        $discovering = $this->discoveringState($dest, $srcAddr);
                        if ($discovering) {
                            $isServer = (hexdec($fcf) >> 3) & 1;
                            $this->discoverUpdate($dest, $srcAddr, $srcEp, 'DiscoverAttributesResponse', $clustId, $isServer, $attributes);
                        }

                        /* Send to client if required (EQ page opened) */
                        $toCli = array(
                            // 'src' => 'parser',
                            'type' => 'discoverAttributesResponse',
                            'net' => $dest,
                            'addr' => $srcAddr,
                            'ep' => $srcEp,
                            'clustId' => $clustId,
                            'dir' => (hexdec($fcf) >> 3) & 1,
                            'attributes' => $attributes
                        );
                        // $this->msgToClient($toCli);
                        // return;
                    }

                    else if (($cmd == "12") || ($cmd == "14")) { // Discover Commands Received/Generated Response
                        // Duplicated message ?
                        // if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                        //     return;
                        // Tcharp38: Need to check how to deal with 'completed' flag

                        // if ($cmd == "12")
                        //     $toMon[] = "8002/Discover Commands Received Response"; // For monitor
                        // else
                        //     $toMon[] = "8002/Discover Commands Generated Response"; // For monitor

                        $completed = substr($msg, 2);
                        $msg = substr($msg, 2); // Skipping 'completed' status
                        $commands = [];
                        $l = strlen($msg);
                        for ($i = 0; $i < $l;) {
                            $commands[] = substr($msg, $i, 2);
                            $i += 2;
                        }
                        $m = '  Supported commands: '.implode("/", $commands);
                        parserLog2('debug', $srcAddr, $m);

                        /* Send to client if required (ex: EQ page opened) */
                        $toCli = array(
                            // 'src' => 'parser',
                            // 'type' => 'discoverCommandsReceivedResponse',
                            'net' => $dest,
                            'addr' => $srcAddr,
                            'ep' => $srcEp,
                            'clustId' => $clustId,
                            'commands' => $commands
                        );
                        if ($cmd == "12")
                            $toCli['type'] = 'discoverCommandsReceivedResponse';
                        else
                            $toCli['type'] = 'discoverCommandsGeneratedResponse';
                    }

                    else if ($cmd == "16") { // Discover Attributes Extended Response
                        // Duplicated message ?
                        // if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                        //     return;
                        // Tcharp38: Need to check how to deal with 'completed' flag

                        $completed = substr($msg, 2);
                        $msg = substr($msg, 2); // Skipping 'completed' status

                        $attributes = [];
                        $l = strlen($msg);
                        for ($i = 0; $i < $l;) {
                            $attrId = substr($msg, $i + 2, 2).substr($msg, $i, 2);
                            $attr = array(
                                'dataType' => substr($msg, $i + 4, 2),
                                'access' => substr($msg, $i + 6, 2)
                            );
                            $attributes[$attrId] = $attr;
                            $i += 8;
                        }

                        $m = '';
                        foreach ($attributes as $attrId => $attr) {
                            if ($m != '')
                                $m .= '/';
                            $m .= $attrId.'-'.$attr['dataType'].'-';
                            $access = hexdec($attr['access']);
                            if ($access & 1)
                                $m .= 'R'; // Readable
                            if ($access & 2)
                                $m .= 'W'; // Writable
                            if ($access & 4)
                                $m .= 'P'; // Reportable
                        }
                        parserLog2('debug', $srcAddr, '  Clust '.$clustId.': '.$m);

                        // $this->discoverLog('- Clust '.$clustId.': '.$m);
                        $discovering = $this->discoveringState($dest, $srcAddr);
                        if ($discovering) {
                            $isServer = (hexdec($fcf) >> 3) & 1;
                            $this->discoverUpdate($dest, $srcAddr, $srcEp, 'DiscoverAttributesExtResponse', $clustId, $isServer, $attributes);
                        }

                        /* Send to client if required (ex: EQ page opened) */
                        $toCli = array(
                            // 'src' => 'parser',
                            'type' => 'discoverAttributesExtendedResponse',
                            'net' => $dest,
                            'addr' => $srcAddr,
                            'ep' => $srcEp,
                            'clustId' => $clustId,
                            'dir' => (hexdec($fcf) >> 3) & 1,
                            'attributes' => $attributes
                        );
                    } // Discover Attributes Extended Response

                    else {
                        parserLog2("debug", $srcAddr, "  Ignored cluster {$clustId} general command {$cmd}", "8002");
                        // return;
                    }
                } else { // Cluster specific command

                    // 0004/Groups cluster specific
                    if ($clustId == "0004") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        if ($dir && ($cmd == "00")) {
                            $this->decode8002_AddGroupRsp($dest, $srcAddr, $srcEp, $pl, $lqi, $toAbeille);
                        } else if (!$dir && ($cmd == "00")) {
                            parserLog2('debug', $srcAddr, '  Handled by Zigate');
                        } else if ($dir && ($cmd == "02")) { // Get group membership response
                            $this->decode8002_GetGroupMembership($dest, $srcAddr, $srcEp, $pl, $lqi, $toAbeille);
                        } else if (!$dir && ($cmd == "02")) {
                            parserLog2('debug', $srcAddr, '  Handled by Zigate');
                        } else if ($dir && ($cmd == "03")) { // Remove group response
                            $this->decode8002_RemoveGroupRsp($dest, $srcAddr, $srcEp, $pl, $lqi, $toAbeille);
                        } else if (!$dir && ($cmd == "03")) {
                            parserLog2('debug', $srcAddr, '  Handled by Zigate');
                        } else {
                            parserLog2('debug', $srcAddr, '  Unsupported cluster 0004 specific cmd '.$cmd);
                            // return;
                        }
                    } // End clustId == "0004"

                    // 0005/Scenes cluster specific
                    else if ($clustId == "0005") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        // Add Scene Response
                        // if ( $cmd == "00" ) {
                        //     $sceneStatus = substr($payload,32, 2);

                        //     if ( $sceneStatus != "00" ) {
                        //         parserLog("debug", '  Status error dd Scene Response.');
                        //         return;
                        //     }

                        //     $groupID = substr($payload,34, 4);
                        //     $sceneId = substr($payload,36, 2);

                        //     parserLog("debug", '  Add Scene Response confirmation (decoded but not processed) Please refresh with a -Get Scene Membership- : group: '.$groupID.' - scene id:'.$sceneId );
                        //     return;
                        // }

                        // View Scene Response
                        // else if ( $cmd == "01" ) {
                        //     $sceneStatus            = substr($payload,32, 2);
                        //     if ( $sceneStatus != "00" ) {
                        //         parserLog("debug", '  Status error on scene info.');
                        //         return;
                        //     }
                        //     $groupID            = substr($payload,34, 4);
                        //     $sceneId            = substr($payload,38, 2);
                        //     $transitionTime     = substr($payload,40, 4);
                        //     $Length             = substr($payload,44, 2);
                        //     $statusRouting      = "";
                        //     $extensionSet       = ""; // 06:00:01:01:08:00:01:fe:00:03:04:13:ae:eb:51 <- to be investigated

                        //     parserLog("debug", '  View Scene Response: '.$groupID.' - '.$sceneId.' ...');
                        //     return;
                        // }

                        // Remove scene response
                        // else if ( $cmd == "02" ) {
                        //     $sceneStatus            = substr($payload,32, 2);
                        //     if ( $sceneStatus != "00" ) {
                        //         parserLog("debug", '  Status error on scene info.');
                        //         return;
                        //     }
                        //     $groupID            = substr($payload,34, 4);
                        //     $sceneId            = substr($payload,38, 2);

                        //     parserLog("debug", '  Scene: '.$sceneId.' du groupe: '.$groupID.' a ete supprime.');
                        //     return;
                        // }

                        // Remove All Scene Response
                        // else if ( $cmd == "03" ) {
                        if ( $cmd == "03" ) {
                            $sceneStatus            = substr($payload,32, 2);
                            if ( $sceneStatus != "00" ) {
                                parserLog2("debug", $srcAddr, '  Status error Remove All Scene Response.');
                                return;
                            }
                            $groupID                = substr($payload,36, 2).substr($payload,34, 2);

                            $abeille = Abeille::byLogicalId($dest."/".$srcAddr,'Abeille');
                            $sceneStored = json_decode( $abeille->getConfiguration('sceneJson','{}') , True );
                            unset($sceneStored["sceneRemainingCapacity"]);
                            unset($sceneStored["sceneCount"]);
                            unset($sceneStored["GroupeScene"][$groupID]);
                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();
                        }

                        // Store Scene Response
                        // else if ( $cmd == "04" ) {
                        //     $sceneStatus            = substr($payload,32, 2);

                        //     if ( $sceneStatus != "00" ) {
                        //         parserLog("debug", '  Status error Store Scene Response.');
                        //         return;
                        //     }

                        //     $groupID          = substr($payload,34, 4);
                        //     $sceneId          = substr($payload,36, 2);

                        //     parserLog("debug", '  store scene response confirmation (decoded but not processed) Please refresh with a -Get Scene Membership- : group: '.$groupID.' - scene id:'.$sceneId );
                        //     return;
                        // }

                        // Get Scene Membership Response
                        else if ( $cmd == "06" ) {
                            $sceneStatus            = substr($payload,32, 2);
                            if ( $sceneStatus == "85" ) {  // Invalid Field
                                $sceneRemainingCapacity = substr($payload,34, 2);
                                $groupID                = substr($payload,36, 4);

                                parserLog2("debug", $srcAddr, "  scene: scene capa:".$sceneRemainingCapacity.' - group: '.$groupID );

                                $abeille = Abeille::byLogicalId($dest."/".$srcAddr,'Abeille');
                                $sceneStored = json_decode( $abeille->getConfiguration('sceneJson','{}') , True );
                                $sceneStored["sceneRemainingCapacity"]        = $sceneRemainingCapacity;
                                unset( $sceneStored["GroupeScene"][$groupID] );

                                $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                                $abeille->save();

                                parserLog2("debug", $srcAddr, '  '.json_encode($sceneStored) );

                                // $sceneStored = json_decode( Abeille::byLogicalId($dest."/".$srcAddr,'Abeille')->getConfiguration('sceneJson','{}') , True );
                                // parserLog("debug", $dest.', Type=8002/Data indication ---------------------> '.json_encode($sceneStored) );
                                return;
                            }
                            if ( $sceneStatus != "00" ) {
                                parserLog2("debug", $srcAddr, '  Status error on scene info.');
                                return;
                            }

                            $sceneRemainingCapacity = substr($payload,34, 2);
                            $groupID                = substr($payload,38, 2).substr($payload,36, 2);
                            $sceneCount             = substr($payload,40, 2);
                            $sceneId = "";
                            for ($i = 0; $i < hexdec($sceneCount); $i++) {
                                $sceneId .= '-'.substr($payload,42+$i*2, 2);
                            }

                            parserLog2("debug", $srcAddr, "  scene capa:".$sceneRemainingCapacity.' - group: '.$groupID.' - scene count:'.$sceneCount.' - scene id:'.$sceneId );

                            $sceneStored["sceneRemainingCapacity"]        = $sceneRemainingCapacity;
                            $sceneStored["sceneCount"]                    = $sceneCount;
                            $sceneStored["GroupeScene"][$groupID]["sceneId"]             = $sceneId;
                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();

                            parserLog2("debug", $srcAddr,  '  '.json_encode($sceneStored) );
                        }

                        // Ikea specific ?
                        else if (($cmd == "07") && ($manufCode == '117C')) {
                            // Ikea specific
                            $value = substr($pl, 0, 2);

                            parserLog2("debug", $srcAddr, '  Ikea 5 buttons remote'
                                            .': Cmd='.$cmd
                                            .', Value='.$value
                                            );

                            // $attrReportN[] = [
                            //     array( "name" => $clustId.'-'.$srcEp.'-0000', "value" => $value ),
                            // ];
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0005-cmd07',
                                'value' => $value,
                            );
                        }
                    } // End $clustId == "0005"

                    // 0006/On/Off cluster specific
                    else if ($clustId == "0006") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $cmdInt = hexdec($cmd);
                        if ((($cmdInt >= 0) && ($cmdInt <= 2)) || // Off=00, On=01, Toggle=02
                            (($cmdInt >= 0x40) && ($cmdInt <= 0x42))) {
                            /* Forwarding to Abeille */
                            // Tcharp38: The 'Click-Middle' is OBSOLETE. Can't define EP so the source of this "click".
                            //           Moreover no sense since there may have no link with "middle". It's is just a OnOff cmd FROM a device to Zigate.
                            $attrReportN[] = array(
                                'name' => 'Click-Middle', // OBSOLETE: Do not use !!
                                'value' => $cmd,
                            );

                            // Tcharp38: New way of handling this event (OnOff cmd coming from a device)
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0006-cmd'.$cmd,
                                'value' => 1, // Currently fake value. Not required for Off-00/On-01/Toggle-02 cmds
                            );
                            // Tcharp38: TODO: Value should return payload when there is (cmds 40/41/42) but must be decoded by 8002 instead to get it.

                            // parserLog('debug', "  Handled by decode8095");
                            // return;
                        } else if ($cmd == "FD") {
                            // parserLog("debug", "  Tuya 0006 specific cmd FD", "8002");
                            $attrReportN = tuyaDecode0006CmdFD($srcEp, $srcAddr, $msg);
                        }
                    } // End $clustId == "0006"

                    // 0008/Level control cluster specific
                    else if ($clustId == "0008") {
                        if (($cmd == "00") || ($cmd == "04")) { // Move to Level (without/with On/Off)
                            $level = substr($pl, 0, 2);
                            $transition = AbeilleTools::reverseHex(substr($pl, 2, 4));
                            $optMask = substr($pl, 6, 2);
                            $optOverride = substr($pl, 8, 2);
                            $m = "  Move to level";
                            if ($cmd == "04")
                                $m .= " with On/Off";
                            $m .= ": level=".$level.", transition=".$transition.", optMask=".$optMask.", optOverride=".$optOverride;
                            parserLog2('debug', $srcAddr, $m);
                        } else if (($cmd == "01") || ($cmd == "05")) { // Move (without/with On/Off)
                            $mode = substr($pl, 0, 2);
                            $rate = substr($pl, 2, 2);
                            $optMask = substr($pl, 4, 2);
                            $optOverride = substr($pl, 6, 2);
                            $m = "  Move";
                            if ($cmd == "05")
                                $m .= " with On/Off";
                            $m .= ": mode=".$mode.", rate=".$rate.", optMask=".$optMask.", optOverride=".$optOverride;
                            parserLog2('debug', $srcAddr, $m);
                        } else if (($cmd == "02") || ($cmd == "06")) { // Step (without/with On/Off)
                            $mode = substr($pl, 0, 2);
                            $size = substr($pl, 2, 2);
                            $transition = AbeilleTools::reverseHex(substr($pl, 4, 4));
                            $optMask = substr($pl, 8, 2);
                            $optOverride = substr($pl, 10, 2);
                            $m = "  Step";
                            if ($cmd == "06")
                                $m .= " with On/Off";
                            $m .= ": mode=".$mode.", size=".$size.", transition=".$transition.", optMask=".$optMask.", optOverride=".$optOverride;
                            parserLog2('debug', $srcAddr, $m);
                        } else if (($cmd == "03") || ($cmd == "07")) { // Stop (without/with On/Off)
                            $optMask = substr($pl, 0, 2);
                            $optOverride = substr($pl, 2, 2);
                            $m = "  Stop";
                            if ($cmd == "07")
                                $m .= " with On/Off";
                            $m .= ": optMask=".$optMask.", optOverride=".$optOverride;
                            parserLog2('debug', $srcAddr, $m);
                        } else {
                            parserLog2('debug', $srcAddr, "  Unsupported cluster 0008 specific cmd ".$cmd);
                            return;
                        }

                        // Legacy code support. To be removed at some point.
                        $attrReportN[] = array(
                            'name' => 'Up-Down', // OBSOLETE: Do not use !!
                            'value' => $cmd,
                        );

                        // Tcharp38: New way of handling this event (Level cluster cmd coming from a device)
                        $attrReportN[] = array(
                            'name' => $srcEp.'-0008-cmd'.$cmd,
                            'value' => 1, // Equivalent to a click. No special value
                        );
                        // Tcharp38: Where is the data associated to cmd ? May need to decode that with 8002 instead.
                    } // End clustId == "0008"

                    // 0019/OTA cluster specific
                    else if ($clustId == "0019") {
                        if ($cmd == "01") { // Query Next Image Request
                            $fieldControl = substr($pl, 0, 2);
                            $manufCode = AbeilleTools::reverseHex(substr($pl, 2, 4));
                            $imgType = AbeilleTools::reverseHex(substr($pl, 6, 4));
                            $curFileVers = AbeilleTools::reverseHex(substr($pl, 10, 8));
                            $hwVers = AbeilleTools::reverseHex(substr($pl, 18, 4));

                            parserLog2('debug', $srcAddr, "  fieldCtrl=".$fieldControl.", manufCode=".$manufCode.", imgType=".$imgType.", fileVers=".$curFileVers.", hwVers=".$hwVers);
                            if (!isset($GLOBALS['ota_fw']) || !isset($GLOBALS['ota_fw'][$manufCode])) {
                                parserLog2('debug', $srcAddr, "  NO fw update available for this manufacturer.");
                                // Respond 'no image' to device => Handled by Zigate server
                                return;
                            }
                            if (!isset($GLOBALS['ota_fw'][$manufCode][$imgType])) {
                                parserLog2('debug', $srcAddr, "  NO fw update available for this image type.");
                                // Respond 'no image' to device => Handled by Zigate server
                                return;
                            }
                            $fw = $GLOBALS['ota_fw'][$manufCode][$imgType];
                            if (hexdec($curFileVers) >= hexdec($fw['fileVersion'])) {
                                parserLog2('debug', $srcAddr, "  Found compliant FW but same version or older.");
                                // Respond 'no image' to device => Handled by Zigate server
                                return;
                            }
                            // Responding to device: image found
                            $imgVers = $fw['fileVersion'];
                            $imgSize = $fw['fileSize'];
                            parserLog2('debug', $srcAddr, "  FW version {$imgVers} available. Response handled by Zigate server.");
                            // msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/cmd-0019", 'ep='.$srcEp.'&cmd=02&status=00&manufCode='.$manufCode.'&imgType='.$imgType.'&imgVersion='.$imgVers.'&imgSize='.$imgSize);
                        } else if ($cmd == "03") { // Image Block Request
                            $fieldControl = substr($msg, 0, 2);
                            $manufCode = AbeilleTools::reverseHex(substr($pl, 2, 4));
                            $imgType = AbeilleTools::reverseHex(substr($pl, 6, 4));
                            $fileVersion = AbeilleTools::reverseHex(substr($pl, 10, 8));
                            $fileOffset = AbeilleTools::reverseHex(substr($pl, 18, 8));
                            $maxDataSize = AbeilleTools::reverseHex(substr($pl, 26, 2));

                            msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/otaImageBlockResponse", 'ep='.$srcEp.'&sqn='.$sqn.'&cmd=05&manufCode='.$manufCode.'&imgType='.$imgType.'&imgOffset='.$fileOffset.'&maxData='.$maxDataSize);

                            if (!isset($eq['zigbee']['imageType']) || ($eq['zigbee']['imageType'] != $imgType)) {
                                // TODO: May need to support multiple image types
                                $eq['zigbee']['imageType'] = $imgType;
                                $msg = array(
                                    'type' => 'deviceUpdates',
                                    'net' => $dest,
                                    'addr' => $srcAddr,
                                    'ep' => $srcEp,
                                    'updates' => array(
                                        "imageType" => $imgType
                                    )
                                );
                                msgToMainD($msg);
                            }

                            $m = "  fieldCtrl=".$fieldControl.", manufCode=".$manufCode.", imgType=".$imgType.", fileVers=".$fileVersion.", fileOffset=".$fileOffset.", maxData=".$maxDataSize;
                            parserLog2('debug', $srcAddr, $m);
                        } else if ($cmd == "06") { // Upgrade end request
                            $status = substr($pl, 0, 2);
                            $manufCode = AbeilleTools::reverseHex(substr($pl, 2, 4));
                            $imgType = AbeilleTools::reverseHex(substr($pl, 6, 4));
                            $fileVersion = AbeilleTools::reverseHex(substr($pl, 10, 8));

                            $m = '  OTA upgrade end request'
                                .', Status='.$status
                                .', ManufCode='.$manufCode
                                .', ImgType='.$imgType
                                .', FileVers='.$fileVersion;
                            parserLog2('debug', $srcAddr, $m);

                            // msgToCmd(PRIO_NORM, "Cmd".$dest."/".$addr."/otaUpgradeEndResponse", 'ep='.$srcEp.'&cmd=02&status=00&manufCode='.$manufCode.'&imgType='.$imgType.'&imgVersion='.$imgVers.'&imgSize='.$imgSize);
                            $eqLogic = Abeille::byLogicalId("{$dest}/{$srcAddr}", 'Abeille');
                            $eqHName = $eqLogic->getHumanName();
                            switch ($status) {
                            case "00":
                                message::add("Abeille", "{$eqHName}: Mise-à-jour du firmware terminée avec succès");
                                // OTA update completed. Need to reread some infos
                                msgToCmd(PRIO_HIGH, "Cmd".$dest."/".$srcAddr."/readAttribute", "ep=".$srcEp."&clustId=0000&attrId=0006,4000"); // Read DateCode + SWBuildID
                                msgToCmd(PRIO_HIGH, "Cmd".$dest."/".$srcAddr."/getNodeDescriptor", "");
                                break;
                            case "95":
                                message::add("Abeille", "{$eqHName}: Transfert du firmware annulé par l'équipement");
                                break;
                            case "96":
                                message::add("Abeille", "{$eqHName}: Transfert du firmware terminé mais invalide");
                                break;
                            case "99":
                                message::add("Abeille", "{$eqHName}: Transfert du firmware terminé mais d'autres images sont requises pour la mise-à-jour");
                                break;
                            }
                        }
                    } // End clustId == "0019"

                    // 0020/Poll control cluster specific
                    else if ($clustId == "0020") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        if ($cmd == "00") {
                            parserLog2('debug', $srcAddr, '  Check-in cmd');
                            msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/cmd-0020", 'ep='.$srcEp.'&cmd=00');
                        } else {
                            parserLog2('debug', $srcAddr, '  Unsupported Poll control cluster cmd '.$cmd);
                        }
                    } // End '$clustId == "0020"'

                    // 0300/Color control cluster specific
                    else if ($clustId == "0300") {
                        // Tcharp38: Covering all 0300 commands
                        parserLog2("debug", $srcAddr, "  msg=".$msg, "8002");
                        $attrReportN[] = array(
                            'name' => $srcEp.'-0300-cmd'.$cmd,
                            'value' => $msg, // Rest of command data to be decoded if required
                        );
                    }

                    // 0500/IAS cluster specific
                    else if ($clustId == "0500") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        if ($cmd == "00") { // Zone status change notification
                            // Not handled here
                            $zoneStatus = AbeilleTools::reverseHex(substr($pl, 0, 4));
                            $extStatus = substr($pl, 4, 2);
                            $zoneId = substr($pl, 6, 2);
                            $delay = AbeilleTools::reverseHex(substr($pl, 8, 4));
                            $m = '  Zone status change notification: ZoneStatus='.$zoneStatus.', ExtStatus='.$extStatus.', ZoneId='.$zoneId.', Delay='.$delay;

                            // Legacy: Sending 0500-#EP#-0000 with zoneStatus as value
                            // To be removed at some point
                            $attrReportN[] = array(
                                'name' => '0500-'.$srcEp.'-0000',
                                'value' => $zoneStatus,
                            );
                            // New message format '#EP#-0500-cmd00' with $zoneStatus as value
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0500-cmd00',
                                'value' => $zoneStatus,
                            );
                        } else if ($cmd == "01") { // Zone enroll request
                            $zoneType = AbeilleTools::reverseHex(substr($pl, 0, 4));
                            $manufCode = AbeilleTools::reverseHex(substr($pl, 4, 4));
                            $m = '  Zone enroll request: ZoneType='.$zoneType.', ManufCode='.$manufCode.' => Answering ZoneId=12';
                            // TODO: Where to get Zone ID from ? Defaulting to '12' for now.
                            msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/cmd-0500", 'ep='.$srcEp.'&cmd=00&zoneId=12');
                            // $attrReportN[] = array(
                            //     'name' => $srcEp.'-0500-cmd01',
                            //     'value' => $zoneType.'-'.$manufCode,
                            // );
                        } else {
                            parserLog2("debug", $srcAddr, "  Ignored 0500/IAS_Zone cluster specific command ".$cmd, "8002");
                            return;
                        }

                        parserLog2('debug', $srcAddr, $m);
                        // $toMon[] = $m;
                    } // End '$clustId == "0500"'

                    // 0501/IAS ACE specific
                    else if ($clustId == "0501") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        if (($cmd == "00") && ($dir == 1)) { // Server to client: Arm Response
                            $armNotif = substr($pl, 0, 2);

                            $notifs = array(
                                "00" => "All Zones Disarmed",
                                "01" => "Only Day/Home Zones Armed",
                                "02" => "Only Night/Sleep Zones Armed",
                                "03" => "All Zones Armed",
                                "04" => "Invalid Arm/Disarm Code",
                                "05" => "Not ready to arm",
                                "06" => "Already disarmed",
                            );
                            $m = "  Arm notification: ".$notifs[$armNotif];
                        } else if (($cmd == "00") && ($dir == 0)) { // Client to server: Arm cmd
                            $armMode = substr($pl, 0, 2);
                            $armCodeSize = hexdec(substr($pl, 2, 2));
                            $armCode = pack("H*", substr($pl, 4, $armCodeSize * 2));
                            $zoneId = hexdec(substr($pl, 4 + $armCodeSize * 2, 2));
                            $armModeT = array(
                                "00" => "Disarm",
                                "01" => "Arm Day/Home Zones Only",
                                "02" => "Arm Night/Sleep Zones Only",
                                "03" => "Arm All Zones",
                            );
                            $m = "  Arm: Mode=".$armMode."/".$armModeT[$armMode].", Code=".$armCode.", ZoneId=".$zoneId;
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0501-cmd00C-val',
                                'value' => $armMode,
                            );
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0501-cmd00C-str',
                                'value' => $armModeT[$armMode],
                            );
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0501-cmd00C-code',
                                'value' => $armCode,
                            );
                        } else if (($cmd == "02") && ($dir == 0)) { // Client to server: Emergency
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0501-cmd02C',
                                'value' => 'EMERGENCY',
                            );
                        } else if (($cmd == "03") && ($dir == 0)) { // Client to server: Fire
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0501-cmd03C',
                                'value' => 'FIRE',
                            );
                        } else if (($cmd == "04") && ($dir == 0)) { // Client to server: Panic
                            $attrReportN[] = array(
                                'name' => $srcEp.'-0501-cmd04C',
                                'value' => 'PANIC',
                            );
                        } else if (($cmd == "07") && ($dir == 0)) { // Client to server: Get Panel Status
                            $m = "  Get Panel Status Response command";
                            // Generate a 'Get Panel Status Response command'
                            msgToCmd(PRIO_NORM, "Cmd".$dest."/".$srcAddr."/cmd-0501", 'ep='.$srcEp.'&cmd=05');
                        } else {
                            parserLog2("debug", $srcAddr, "  Ignored 0501/IAS_ACE cluster specific command ".$cmd, "8002");
                            return;
                        }
                        parserLog2('debug', $srcAddr, $m);
                        // $toMon[] = $m;
                    } // End '$clustId == "0501"'

                    // 1000/Touch link commissioning cluster specific
                    else if ($clustId == "1000") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        if ($dir && ($cmd == "41")) { // Get group identifiers response
                            $total = substr($msg, 0, 2);
                            $startIdx = substr($msg, 2, 2);
                            $count = substr($msg, 4, 2);
                            $msg = substr($msg, 6);
                            parserLog2('debug', $srcAddr, '  Get group identifiers response: Total='.$total.', StartIdx='.$startIdx.', Count='.$count);
                            for ($i = 0; $i < $count; $i++) {
                                $groupId = AbeilleTools::reverseHex(substr($msg, 0, 4));
                                $groupType = substr($msg, 4, 2);
                                $msg = substr($msg, 6);
                                parserLog2('debug', $srcAddr, '  - Group='.$groupId.', Type='.$groupType);
                            }
                        } else if ($dir && ($cmd == "42")) { // Get endpoint list response
                            $total = substr($msg, 0, 2);
                            $startIdx = substr($msg, 2, 2);
                            $count = substr($msg, 4, 2);
                            $msg = substr($msg, 6);
                            parserLog2('debug', $srcAddr, '  Get endpoint list response: Total='.$total.', StartIdx='.$startIdx.', Count='.$count);
                            for ($i = 0; $i < $count; $i++) {
                                $netAddr2 = AbeilleTools::reverseHex(substr($msg, 0, 4));
                                $epId2 = substr($msg, 4, 2);
                                $profId2 = AbeilleTools::reverseHex(substr($msg, 6, 4));
                                $devId2 = AbeilleTools::reverseHex(substr($msg, 10, 4));
                                $version2 = substr($msg, 12, 2);
                                $msg = substr($msg, 14);
                                parserLog2('debug', $srcAddr, '  - Addr='.$netAddr2.', EP='.$epId2.', ProfId='.$profId2.', DevId='.$decId2);
                            }
                        } else {
                            parserLog2("debug", $srcAddr, "  Ignored 1000/Touchlink cluster specific command ".$cmd, "8002");
                            return;
                        }
                    } // End '$clustId == "1000"'

                    // Cluster EF00 is used by Tuya.
                    else if ($clustId == "EF00") {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        $attrReportN = tuyaDecodeEF00Cmd($dest, $srcAddr, $srcEp, $cmd, $msg);
                    } // End $clustId == "EF00"

                    else {
                        // Duplicated message ?
                        if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
                            return;

                        // Checking if command must be treated as special private case

                        /* Generic format for private clusters/commands reminder
                        "private": {
                            "EF00": { // CLUSTID
                                "type": "tuya",
                                "05": { // DP
                                    "function": "rcvValue",
                                    "info": "01-measuredValue"
                                },
                            },
                            "ED00": { // CLUSTID
                                "type": "tuya-zosung"
                            },
                            "0000-FF01": { // CLUSTID-ATTRID
                                "type": "xiaomi",
                                "01-21": {
                                    "func": "numberDiv",
                                    "div": 1000,
                                    "info": "0001-01-0020"
                                }
                            },
                            "FC00-00": { // CLUSTID-CMDID
                                "type": "generic",
                                "function": "philipsDecodeCmdFC00"
                            }
                        }
                        "fromDevice": { // OBSOLETE !! Previous naming
                            "ED00": {
                                "type": "tuya-zosung"
                            },
                            "0000-FF01": { // CLUSTID-ATTRID
                                "type": "xiaomi",
                                "01-21": {
                                    "func": "numberDiv",
                                    "div": 1000,
                                    "info": "0001-01-0020"
                                }
                            },
                            "EF00": {
                                "type": "tuya",
                                "05": { // DP
                                    "function": "rcvValue",
                                    "info": "01-measuredValue"
                                },
                            }
                        } */
                        // if (isset($eq['private'])) {
                        //     foreach ($eq['private'] as $key => $p2) {
                        //         $lenKey = strlen($key);
                        //         if (($lenKey == 4) && ($clustId != $key)) // 'CCCC' (clustId) case
                        //             continue;
                        //         // if (($lenFd1 == 9) && ($clustId.'-'.$attrId != $fd1)) // 'CCCC-AAAA' (clustId-attrId) case
                        //         //     continue;

                        //         $supportType = $p2['type']; // "tuya", "tuya-zosung", "xiaomi"
                        //         break;
                        //     }
                        // }
                        // if (isset($supportType)) {
                        //     if ($supportType == 'tuya')
                        //         $attrReportN = tuyaDecodeEF00Cmd($dest, $srcAddr, $srcEp, $cmd, $pl);
                        //     else if ($supportType == 'tuya-zosung')
                        //         $attrReportN = tuyaDecodeZosungCmd($dest, $srcAddr, $srcEp, $cmd, $pl);
                        //     else
                        //         parserLog2("error", $srcAddr, "  Cluster specific command ".$clustId."-".$cmd.": unsupported type ".$supportType);
                        // } else {
                        //     parserLog2("debug", $srcAddr, "  Unsupported cluster specific command ".$clustId."-".$cmd, "8002");
                        //     $attrReportN[] = array(
                        //         'name' => 'inf_'.$srcEp.'-'.$clustId.'-cmd'.$cmd,
                        //         'value' => $pl,
                        //     );
                        // }
                        if (isset($eq['private']) && isset($eq['private'][$clustId.'-'.$cmd])) {
                            $p = $eq['private'][$clustId.'-'.$cmd];
                            $supportType = $p['type']; // "tuya", "tuya-zosung", "xiaomi", "generic"
                            if ($supportType == 'tuya')
                                $attrReportN = tuyaDecodeEF00Cmd($dest, $srcAddr, $srcEp, $cmd, $pl);
                            else if ($supportType == 'tuya-zosung')
                                $attrReportN = tuyaDecodeZosungCmd($dest, $srcAddr, $srcEp, $cmd, $pl);
                            else if ($supportType == 'generic') {
                                if (!isset($p['function'])) {
                                    parserLog2("error", $srcAddr, "  Cluster specific command ".$clustId."-".$cmd.": Undefined private function");
                                } else {
                                    $fct = $p['function'];
                                    if (!function_exists($fct)) {
                                        parserLog2("error", $srcAddr, "  Cluster specific command ".$clustId."-".$cmd.": Unknown private function '$fct'");
                                    } else {
                                        $attrReportN = $fct($dest, $srcAddr, $srcEp, $clustId, $cmd, $pl);
                                    }
                                }
                            } else
                                parserLog2("error", $srcAddr, "  Cluster specific command ".$clustId."-".$cmd.": Unsupported type '$supportType'");
                        } else {
                            parserLog2("debug", $srcAddr, "  Cluster '$clustId' specific command '$cmd' handled as default logic id '$srcEp-$clustId-cmd$cmd'");
                            $attrReportN[] = array(
                                'name' => $srcEp.'-'.$clustId.'-cmd'.$cmd,
                                'value' => (strlen($pl) > 0) ? $pl : "1",
                            );
                        }
                    }
                } // End cluster specific commands
            }

            // Any device key info updates ?
            if (count($devUpdates) != 0)
                $this->deviceUpdates($dest, $srcAddr, $srcEp, $devUpdates);

            // Something to report to main daemon ?
            if (count($attrReportN) > 0) {
                $msg = array(
                    // 'src' => 'parser',
                    'type' => 'attributesReportN',
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $srcEp,
                    'clustId' => $clustId,
                    'attributes' => $attrReportN,
                    'time' => time(),
                    'lqi' => $lqi
                );
                msgToMainD($msg);
            }
            if (count($readAttributesResponseN) > 0) {
                $msg = array(
                    // 'src' => 'parser',
                    'type' => 'readAttributesResponseN',
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $srcEp,
                    'clustId' => $clustId,
                    'attributes' => $readAttributesResponseN,
                    'time' => time(),
                    'lqi' => $lqi
                );
                msgToMainD($msg);
            }
            if (count($toAbeille) > 0) {
                foreach ($toAbeille as $msg)
                    msgToMainD($msg);
            }
            // If nothing to report, at least informing device is alive to prevent timeout
            if ((count($attrReportN) == 0) && (count($readAttributesResponseN) == 0) && (count($toAbeille) == 0)) {
                $msg = array(
                    'type' => 'deviceAlive',
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'time' => time(),
                    'lqi' => $lqi
                );
                msgToMainD($msg);
            }

            // Something to report to client ?
            if (isset($toCli))
                $this->msgToClient($toCli);

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            // Monitor if requested (OBSOLETE way !! Use parserLog2() instead)
            // if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr)) {
            //     foreach ($toMon as $monMsg)
            //         monMsgFromZigate($monMsg); // Send message to monitor
            // }
            // Flush monitoring messages (if any) from parserLog2()
            foreach ($GLOBALS['toMon'] as $monMsg)
                monMsgFromZigate($monMsg); // Send message to monitor
            $GLOBALS['toMon'] = []; // Clear monitoring queue
        } // End decode8002()

        /* 8009/Network State Reponse */
        function decode8009($dest, $payload, $lqi) {
            // <Short Address: uint16_t>
            // <Extended Address: uint64_t>
            // <PAN ID: uint16_t>
            // <Ext PAN ID: uint64_t>
            // <Channel: u int8_t>
            $addr       = substr($payload, 0, 4);
            $extAddr    = substr($payload, 4, 16);
            $panId      = substr($payload, 20, 4);
            $extPanId   = substr($payload, 24,16);
            $chan       = hexdec(substr($payload, 40, 2));

            $msgDecoded = '8009/Network state response, Addr='.$addr.', ExtAddr='.$extAddr.', PANId='.$panId.', ExtPANId='.$extPanId.', Chan='.$chan;
            parserLog2('debug', $addr, $dest.', Type='.$msgDecoded, "8009");

            $this->whoTalked[] = $dest.'/'.$addr;

            // Monitor if requested
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $addr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            // Zigate IEEE local storage
            $zgId = substr($dest, 7);
            $GLOBALS['zigate'.$zgId]['ieee'] = $extAddr;
            $GLOBALS['zigate'.$zgId]['extPanId'] = $extPanId;

            /* If still required, checking USB port unexpected switch */
            // Tcharp38: Handled by Abeille.class
            // $confIeee = str_replace('Abeille', 'ab::zgIeeeAddr', $dest); // AbeilleX => ab::zgIeeeAddrX
            // $confIeeeOk = str_replace('Abeille', 'ab::zgIeeeAddrOk', $dest); // AbeilleX => ab::zgIeeeAddrOkX
            // if (config::byKey($confIeeeOk, 'Abeille', 0) == 0) {
            //     if (config::byKey($confIeee, 'Abeille', 'none', 1) == "none") {
            //         config::save($confIeee, $extAddr, 'Abeille');
            //         config::save($confIeeeOk, 1, 'Abeille');
            //     } else if (config::byKey($confIeee, 'Abeille', 'none', 1) == $extAddr) {
            //         config::save($confIeeeOk, 1, 'Abeille');
            //     } else {
            //         config::save($confIeeeOk, -1, 'Abeille');
            //         message::add("Abeille", "Mauvais port détecté pour zigate ".$zgId.". Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
            //         return;
            //     }
            // }
            // Tcharp38: Zigate IEEE stored in 2 many locations. Need to optimize
            // $eqLogic = Abeille::byLogicalId($dest.'/'.$addr, 'Abeille');
            // $ieee2 = $eqLogic->getConfiguration('IEEE', '');
            // if ($ieee2 == "") {
            //     $eqLogic->setConfiguration('IEEE', $extAddr);
            //     $eqLogic->save();
            // }

            $msg = array(
                // 'src' => 'parser',
                'type' => 'networkState',
                'net' => $dest,
                'addr' => $addr, // Should be 0000
                'ieee' => $extAddr,
                'panId' => $panId,
                'extPanId' => $extPanId,
                'chan' => $chan,
                'time' => time()
            );
            msgToMainD($msg);
        }

        /* Zigate FW version */
        function decode8010($dest, $payload, $lqi) {
            // <Major version number: uint16_t>
            // <Installer version number: uint16_t>
            $major = substr($payload, 0, 4);
            $minor = substr($payload, 4, 4);

            parserLog2('debug', '0000', $dest.', Type=8010/Version, Appli='.$major.', SDK='.$minor, "8010");

            $zgId = substr($dest, 7);
            $GLOBALS['zigate'.$zgId]['fwVersionMaj'] = $major;
            $GLOBALS['zigate'.$zgId]['fwVersionMin'] = $minor;

            // FW version required by AbeilleCmd for flow control decision.
            $msg = array (
                'type'      => "8010",
                'net'       => $dest,
                'major'     => $major,
                'minor'     => $minor,
            );
            msgToCmdAck($msg);

            $msg = array(
                // 'src' => 'parser',
                'type' => 'zigateVersion',
                'net' => $dest,
                'major' => $major,
                'minor' => $minor,
                'time' => time()
            );
            msgToMainD($msg);
        }

        /**
         * ACK DATA (since FW 3.1b) = ZPS_EVENT_APS_DATA_CONFIRM Note: NACK = 8702
         *
         * @param $dest     Network (AbeilleX)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing
         */
        function decode8011($dest, $payload, $lqi) {
            // <Status: uint8_t>
            // <Destination address: uint16_t>
            // <Dest Endpoint : uint8_t>
            // <Cluster ID : uint16_t>
            $status     = substr($payload, 0, 2);
            $dstAddr    = substr($payload, 2, 4);
            $dstEp      = substr($payload, 6, 2);
            $clustId    = substr($payload, 8, 4);
            if (strlen($payload) > 12)
                $sqnAps = substr($payload, 12, 2);
            else
                $sqnAps = '';

            // Tcharp38: 'clustId' seems completely wrong.

            $msgDecoded = '8011/APS data ACK, Status='.$status.'/'.zbGetAPSStatus($status).', Addr='.$dstAddr.', EP='.$dstEp.', ClustId='.$clustId;
            if ($sqnAps != '')
                $msgDecoded .= ', SQNAPS='.$sqnAps;
            parserLog2('debug', $dstAddr, $dest.', Type='.$msgDecoded, "8011");

            // Sending msg to cmd for flow control
            $toAbeille = array (
                'type'      => "8011",
                'net'       => $dest,
                'status'    => $status,
                'addr'      => $dstAddr,
                'sqnAps'    => $sqnAps,
            );
            msgToCmdAck($toAbeille);

            // Monitor if required
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $dstAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor
        }

        /* 8012/
           Confirms that a data packet sent by the local node has been successfully passed down the stack to the MAC layer
           and has made its first hop towards its destination (an acknowledgment has been received from the next hop node) */
        function decode8012($net, $payload, $lqi) {
            // <Status: uint8_t>
            // <Src Endpoint: uint8_t>
            // <Dest Endpoint : uint8_t>
            // <Dest Addr mode: uint8_t>
            // <Destination address: uint16_t> OR <Destination IEEE address: uint64_t>
            // <APS Sequence number: uint8_t>
            // See https://github.com/fairecasoimeme/ZiGate/issues/350
            $status = substr($payload, 0, 2);
            $srcEp = substr($payload, 2, 2);
            $dstEp = substr($payload, 4, 2);
            $dstMode = substr($payload, 6, 2);
            // Tcharp38: Lack of infos from Zigate site. Extracted from Domoticz plugin.
            if (in_array($dstMode, ["01", "02", "07"])) {
                $dstAddr    = substr($payload, 8, 4);
                $sqnAps     = substr($payload,12, 2);
                $nPdu       = substr($payload,14, 2);
                $aPdu       = substr($payload,16, 2);
            } else if ($dstMode == "03") { // IEEE
                $dstAddr    = substr($payload, 8, 16);
                $sqnAps     = substr($payload,24, 2);
                $nPdu       = substr($payload,26, 2);
                $aPdu       = substr($payload,28, 2);
            }

            $zgId = substr($net, 7); // AbeilleX => X
            $GLOBALS['zigate'.$zgId]['nPdu'] = $nPdu;
            $GLOBALS['zigate'.$zgId]['aPdu'] = $aPdu;

            // Log
            $msgDecoded = '8012/APS data confirm, Status='.$status.', Addr='.$dstAddr.', SQNAPS='.$sqnAps.', NPDU='.$nPdu.', APDU='.$aPdu;
            parserLog2('debug', $dstAddr, $net.', Type='.$msgDecoded, "8012");

            // Sending msg to cmd for flow control => Useful to update NPDU/APDU
            $msg = array (
                'type'      => "8012",
                'net'       => $net,
                // 'status'    => $status, // Unused so far
                // 'addr'      => $dstAddr, // Unused so far
                // 'sqnAps'    => $sqnAps, // Unused so far
                'nPDU'      => $nPdu,
                'aPDU'      => $aPdu,
            );
            msgToCmdAck($msg);

            // Monitor if required
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $dstAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor
        }

        /**
         * 8014/“Permit join” status decode function
         *
         * @param $dest     Zigbee network (ex: Abeille1)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing
         */
        function decode8014($dest, $payload, $lqi) {
            // “Permit join” status
            // response Msg Type=0x8014
            // 0 - Off 1 - On
            //<Status: bool_t>
            // Envoi Status

            $status = substr($payload, 0, 2);
            $zgId = substr($dest, 7);

            // Local status storage
            $GLOBALS['zigate'.$zgId]['permitJoin'] = $status;

            parserLog2('debug', '0000', $dest.', Type=8014/Permit join status response, PermitJoinStatus='.$status);
            if ($status == "01")
                parserLog2('info', '0000', '  Zigate'.$zgId.': en mode INCLUSION', "8014");
            else
                parserLog2('info', '0000', '  Zigate'.$zgId.': mode inclusion inactif', "8014");

            $msg = array(
                // 'src' => 'parser',
                'type' => 'permitJoin',
                'net' => $dest,
                'status' => $status,
                'time' => time()
            );
            msgToMainD($msg);
        }

        /* Get devices list response */
        // Tcharp38: Useless
        // function decode8015($dest, $payload, $lqi)
        // {
        //     // <device list – data each entry is 13 bytes>
        //     // <ID: uint8_t>
        //     // <Short address: uint16_t>
        //     // <IEEE address: uint64_t>
        //     // <Power source: bool_t> 0 – battery 1- AC power
        //     // <LinkQuality : uint8_t> 1-255

        //     // Id Short IEEE             Power  LQ
        //     // 00 ffe1  00158d0001d5c421 00     a6
        //     // 01 c4bf  00158d0001215781 00     aa
        //     // 02 4f34  00158d00016d8d4f 00     b5
        //     // 03 304a  00158d0001a66ca3 00     a4
        //     // 04 cc0D  00158d0001d6c177 00     b3
        //     // 05 3c58  00158d00019f9199 00     9a
        //     // 06 7c3b  000B57fffe2c82e9 00     bb
        //     // 07 7c54  00158d000183afeb 01     c3
        //     // 08 3db8  00158d000183af7b 01     c5
        //     // 32 553c  000B57fffe3025ad 01     9f
        //     // 00 -> Pourquoi 00 ?

        //     parserLog('debug', $dest.', Type=8015/Get devices list response, Payload='.$payload);

        //     $nb = (strlen($payload) - 2) / 26;
        //     parserLog('debug','  Nombre d\'abeilles: '.$nb);

        //     for ($i = 0; $i < $nb; $i++) {

        //         $srcAddr = substr($payload, $i * 26 + 2, 4);

        //         // Envoie IEEE
        //         $dataAddr = substr($payload, $i * 26 + 6, 16);
        //         $this->msgToAbeille($dest."/".$srcAddr, "IEEE", "Addr", $dataAddr);

        //         // Envoie Power Source
        //         $dataPower = substr($payload, $i * 26 + 22, 2);
        //         $this->msgToAbeille($dest."/".$srcAddr, "Power", "Source", $dataPower);

        //         // Envoie Link Quality
        //         $dataLink = hexdec(substr($payload, $i * 26 + 24, 2));
        //         $this->msgToAbeille($dest."/".$srcAddr, "Link", "Quality", $dataLink);

        //         parserLog('debug', '  i='.$i.': '
        //                         .'ID='.substr($payload, $i * 26 + 0, 2)
        //                         .', ShortAddr='.$srcAddr
        //                         .', ExtAddr='.$dataAddr
        //                         .', PowerSource (0:battery - 1:AC)='.$dataPower
        //                         .', LinkQuality='.$dataLink   );
        //     }
        // }

        /* 8017/Get Time Server Response (FW >= 3.0f) */
        function decode8017($dest, $payload, $lqi) {
            // <Timestamp UTC: uint32_t> from 2000-01-01 00:00:00
            $timestamp = substr($payload, 0, 8);
            parserLog2('debug', '0000', $dest.', Type=8017/Get time server response, Timestamp='.hexdec($timestamp), "8017");

            // Note: updating timestamp ref from 2000 to 1970
            $data = date(DATE_RFC2822, hexdec($timestamp) + mktime(0, 0, 0, 1, 1, 2000));
            $msg = array(
                // 'src' => 'parser',
                'type' => 'zigateTime',
                'net' => $dest,
                'timeServer' => $data,
                'time' => time()
            );
            msgToMainD($msg);
        }

        /* 8024/Network joined/formed */
        function decode8024($dest, $payload, $lqi) {
            // https://github.com/fairecasoimeme/ZiGate/issues/74

            // Formed Msg Type = 0x8024
            // Node->Host  Network Joined / Formed

            // <status: uint8_t>
            //      0 = Joined existing network
            //      1 = Formed new network
            //      128 – 244 = Failed (ZigBee event codes)
            // <short address: uint16_t>
            // <extended address:uint64_t>
            // <channel: uint8_t>

            /* Decode */
            $status = substr($payload, 0, 2);
            if ($status == "00") { $data = "Joined existing network"; }
            if ($status == "01") { $data = "Formed new network"; }
            if ($status == "04") { $data = "Network (already) formed"; }
            if ($status  > "04") { $data = "Failed (ZigBee event codes): ".substr($payload, 0, 2); }
            $dataShort = substr($payload, 2, 4);
            $dataIEEE = substr($payload, 6, 16);
            $dataNetwork = hexdec(substr($payload, 22, 2));

            /* Log */
            parserLog2('debug', '0000', $dest.', Type=8024/Network joined-formed, Status=\''.$data.'\', Addr='.$dataShort.', ExtAddr='.$dataIEEE.', Chan='.$dataNetwork, "8024");

            // Zigate IEEE local storage
            $zgId = substr($dest, 7);
            $GLOBALS['zigate'.$zgId]['ieee'] = $dataIEEE;

            /* If still required, checking USB port unexpected switch */
            // Tcharp38: Handled by Abeille.class
            // $confIeee = str_replace('Abeille', 'ab::zgIeeeAddr', $dest); // AbeilleX => ab::zgIeeeAddrX
            // $confIeeeOk = str_replace('Abeille', 'ab::zgIeeeAddrOk', $dest); // AbeilleX => ab::zgIeeeAddrOkX
            // if (config::byKey($confIeeeOk, 'Abeille', 0) == 0) {
            //     if (config::byKey($confIeee, 'Abeille', 'none', 1) == "none") {
            //         config::save($confIeee, $dataIEEE, 'Abeille');
            //         config::save($confIeeeOk, 1, 'Abeille');
            //     } else if (config::byKey($confIeee, 'Abeille', 'none', 1) == $dataIEEE) {
            //         config::save($confIeeeOk, 1, 'Abeille');
            //     } else {
            //         config::save($confIeeeOk, -1, 'Abeille');
            //         $zgId = substr($dest, 7); // AbeilleX => X
            //         message::add("Abeille", "Mauvais port détecté pour zigate ".$zgId.". Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
            //         return;
            //     }
            // }

            $msg = array(
                // 'src' => 'parser',
                'type' => 'networkStarted',
                'net' => $dest,
                'status' => $status,
                'statusTxt' => $data,
                'addr' => $dataShort, // Should be always 0000
                'ieee' => $dataIEEE, // Zigate IEEE
                'chan' => $dataNetwork,
                'time' => time()
            );
            msgToMainD($msg);
        }

        /* 8035/PDM event code. Since FW 3.1b */
        function decode8035($dest, $payload, $lqi) {
            $PDMEvtCode = substr($payload, 0, 2); // PDM event code (PDM_eSystemEventCode): <uint8_t>
            $RecId = substr($payload, 2, 8); // Record id : <uint32_t>

            parserLog2('debug', '0000', $dest.', Type=8035/PDM event code'
                             .', Code=x'.$PDMEvtCode
                             .', RecId='.$RecId
                             .' => '.zgGetPDMEvent($PDMEvtCode), "8035");
        }

        // function decode8040($dest, $payload, $lqi) {
        //     // Network address response

        //     // <Sequence number: uin8_t>
        //     // <status: uint8_t>
        //     // <IEEE address: uint64_t>
        //     // <short address: uint16_t>
        //     // <number of associated devices: uint8_t>
        //     // <start index: uint8_t>
        //     // <device list – data each entry is uint16_t>
        //     $Addr = substr($payload,20, 4);

        //     $msgDecoded='8040/Network address response'
        //        .', SQN='                                     .substr($payload, 0, 2)
        //        .', Status='                                  .substr($payload, 2, 2)
        //        .', ExtAddr='                                 .substr($payload, 4,16)
        //        .', Addr='                               .$Addr
        //        .', NumberOfAssociatedDevices='               .substr($payload,24, 2)
        //        .', StartIndex='                              .substr($payload,26, 2);
        //     parserLog('debug', $dest.', Type='.$msgDecoded, "8040");

        //     if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $Addr))
        //         monMsgFromZigate($msgDecoded); // Send message to monitor

        //     if ( substr($payload, 2, 2) != "00" ) {
        //         parserLog('debug', '  Don\'t use this data there is an error');
        //     } else {
        //         for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
        //             parserLog('debug', '  AssociatedDev='.substr($payload, (28 + $i), 4));
        //         }
        //     }
        // }

        /**
         * 8048/Leave indication
         *
         * This method process a Zigbeee message coming from a device indicating Leaving
         * Note: message compliant with RAW mode.
         *
         * @param $dest     Zigbee network (ex: Abeille1)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8048($dest, $payload, $lqi) {
            /* Decode */
            $ieee = substr($payload, 0, 16);
            $rejoinStatus = substr($payload, 16, 2);

            /* Log */
            $msgDecoded = '8048/Leave indication, ExtAddr='.$ieee.', RejoinStatus='.$rejoinStatus;
            parserLog2('debug', $ieee, $dest.', Type='.$msgDecoded, "8048");

            /* Config ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                // 'src' => 'parser',
                'type' => 'leaveIndication',
                'net' => $dest,
                'ieee' => $ieee,
                'rejoin' => $rejoinStatus,
                'time' => time(),
                'lqi' => $lqi
            );
            msgToMainD($msg);

            // Monitor if requested
            if (isset($GLOBALS["dbgMonitorAddrExt"]) && !strcasecmp($GLOBALS["dbgMonitorAddrExt"], $ieee))
                monMsgFromZigate($msgDecoded); // Send message to monitor
        }

        // https://github.com/fairecasoimeme/ZiGate/issues/6
        // Button   Pres-stype  Response  command       attr
        // down     click       0x8085    0x02          None
        // down     hold        0x8085    0x01          None
        // down     release     0x8085    0x03          None
        // up       click       0x8085    0x06          None
        // up       hold        0x8085    0x05          None
        // up       release     0x8085    0x07          None
        // middle   click       0x8095    0x02          None
        // left     click       0x80A7    0x07          direction: 1
        // left     hold        0x80A7    0x08          direction: 1    => can t get that one
        // right    click       0x80A7    0x07          direction: 0
        // right    hold        0x80A7    0x08          direction: 0    => can t get that one
        // left/right release   0x80A7    0x09          None            => can t get that one
        //
        // down = brightness down, up = brightness up,
        // middle = Power button,
        // left and right = when brightness up is up left is left and right is right.
        // Holding down power button for ~10 sec will result multiple commands sent, but it wont send any hold command only release.
        // Remote won't tell which button was released left or right, but it will be same button that was last hold.
        // Remote is unable to send other button commands at least when left or right is hold down.

        // /* Level cluster command coming from a device (broadcast or unicast to Zigate) */
        // function decode8085($dest, $payload, $lqi) {
        //     // <Sequence number: uin8_t>    -> 2
        //     // <endpoint: uint8_t>          -> 2
        //     // <Cluster id: uint16_t>       -> 4
        //     // <address_mode: uint8_t>      -> 2
        //     // <addr: uint16_t>             -> 4
        //     // <cmd: uint8>                 -> 2
        //     //  2: 'click', 1: 'hold', 3: 'release'

        //     $ep = substr($payload, 2, 2);
        //     $clustId = substr($payload, 4, 4);
        //     $srcAddr = substr($payload, 10, 4); // Assuming short addr mode
        //     $cmd = substr($payload, 14, 2);

        //     $decoded = '8085/Level update'
        //         .', SQN='.substr($payload, 0, 2)
        //         .', EP='.$ep
        //         .', ClustId='.$clustId
        //         .', AddrMode='.substr($payload, 8, 2)
        //         .', SrcAddr='.$srcAddr
        //         .', Cmd='.$cmd;
        //     parserLog('debug', $dest.', Type='.$decoded);

        //     // Monitor if required
        //     if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr)) {
        //         monMsgFromZigate($decoded); // Send message to monitor
        //     }

        //     $this->whoTalked[] = $dest.'/'.$srcAddr;

        //     // Legacy code support. To be removed at some point.
        //     $attributes = [];
        //     $attributes[] = array(
        //         'name' => 'Up-Down', // OBSOLETE: Do not use !!
        //         'value' => $cmd,
        //     );

        //     // Tcharp38: New way of handling this event (Level cluster cmd coming from a device)
        //     $attributes[] = array(
        //         'name' => $ep.'-0008-cmd'.$cmd,
        //         'value' => 1, // Equivalent to a click. No special value
        //     );
        //     // Tcharp38: Where is the data associated to cmd ? May need to decode that with 8002 instead.

        //     $msg = array(
        //         // 'src' => 'parser',
        //         'type' => 'attributesReportN',
        //         'net' => $dest,
        //         'addr' => $srcAddr,
        //         'ep' => $ep,
        //         'clustId' => $clustId,
        //         'attributes' => $attributes,
        //         'time' => time(),
        //         'lqi' => $lqi
        //     );
        //     msgToMainD($msg);
        // }

        // /* OnOff cluster command coming from a device (broadcast or unicast to Zigate) */
        // function decode8095($dest, $payload, $lqi) {
        //     // <Sequence number: uin8_t>
        //     // <endpoint: uint8_t>
        //     // <Cluster id: uint16_t>
        //     // <address_mode: uint8_t>
        //     // <SrcAddr: uint16_t>
        //     // <status: uint8>
        //     $sqn = substr($payload, 0, 2);
        //     $ep = substr($payload, 2, 2);
        //     $clustId = substr($payload, 4, 4);
        //     $addrMode = substr($payload, 8, 2);
        //     $srcAddr = substr($payload, 10, 4);
        //     $status = substr($payload, 14, 2);

        //     // Log
        //     $decoded = '8095/OnOff update'
        //         .', SQN='.$sqn
        //         .', EP='.$ep
        //         .', ClustId='.$clustId
        //         .', AddrMode='.$addrMode
        //         .', Addr='.$srcAddr
        //         .', Status='.$status;
        //     parserLog('debug', $dest.', Type='.$decoded);

        //     // Can it be duplicated ?
        //     // Duplicated message ?
        //     // if ($this->isDuplicated($dest, $srcAddr, $fcf, $sqn))
        //     //     return;

        //     // $this->whoTalked[] = $dest.'/'.$srcAddr;
        //     if ($this->deviceUpdates($dest, $srcAddr, ''))
        //         return; // Unknown device

        //     // Monitor if required
        //     if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
        //         monMsgFromZigate($decoded); // Send message to monitor

        //     /* Forwarding to Abeille */
        //     $attributes = array();
        //     // $this->msgToAbeille("{$dest}/{$srcAddr}", "Click", "Middle", $status);
        //     // Tcharp38: The 'Click-Middle' must be avoided. Can't define EP so the source of this "click".
        //     //           Moreover no sense since there may have no link with "middle". It's is just a OnOff cmd FROM a device to Zigate.
        //     $attr = array(
        //         'name' => 'Click-Middle', // OBSOLETE: Do not use !!
        //         'value' => $status,
        //     );
        //     $attributes[] = $attr;

        //     // Tcharp38: New way of handling this event (OnOff cmd coming from a device)
        //     $attr = array(
        //         'name' => $ep.'-0006-cmd'.$status,
        //         'value' => 1, // Currently fake value. Not required for Off-00/On-01/Toggle-02 cmds
        //     );
        //     $attributes[] = $attr;
        //     // Tcharp38: TODO: Value should return payload when there is (cmds 40/41/42) but must be decoded by 8002 instead to get it.
        //     // Tcharp38: Note: Cmd FD (seen as Tuya specific cluster 0006 cmd) may be returned too with recent FW.

        //     $msg = array(
        //         // 'src' => 'parser',
        //         'type' => 'attributesReportN',
        //         'net' => $dest,
        //         'addr' => $srcAddr,
        //         'ep' => $ep,
        //         'clustId' => $clustId,
        //         'attributes' => $attributes,
        //         'time' => time(),
        //         'lqi' => $lqi
        //     );
        //     msgToMainD($msg);
        // }

        // //----------------------------------------------------------------------------------------------------------------
        // ##TODO
        // #reponse scene
        // #80a0-80a6
        // function decode80A0($dest, $payload, $lqi) {
        //     // <sequence number: uint8_t>                           -> 2
        //     // <endpoint : uint8_t>                                 -> 2
        //     // <cluster id: uint16_t>                               -> 4
        //     // <status: uint8_t>                                    -> 2

        //     // <group ID: uint16_t>                                 -> 4
        //     // <scene ID: uint8_t>                                  -> 2
        //     // <transition time: uint16_t>                          -> 4

        //     // <scene name length: uint8_t>                         -> 2
        //     // <scene name max length: uint8_t>                     -> 2
        //     // <scene name  data: data each element is uint8_t>     -> 2

        //     // <extensions length: uint16_t>                        -> 4
        //     // <extensions max length: uint16_t>                    -> 4
        //     // <extensions data: data each element is uint8_t>      -> 2
        //     // <Src Addr: uint16_t> (added only from 3.0f version)

        //     parserLog('debug', $dest.', Type=80A0/Scene View'
        //                     .', SQN='                           .substr($payload, 0, 2)
        //                     .', EndPoint='                      .substr($payload, 2, 2)
        //                     .', ClusterId='                     .substr($payload, 4, 4)
        //                     .', Status='                        .substr($payload, 8, 2)

        //                     .', GroupID='                       .substr($payload,10, 4)
        //                     .', SceneID='                       .substr($payload,14, 2)
        //                     .', transition time: '              .substr($payload,16, 4)

        //                     .', scene name lenght: '            .substr($payload,20, 2)  // Osram Plug repond 0 pour lenght et rien apres.
        //                     .', scene name max lenght: '        .substr($payload,22, 2)
        //                     .', scene name : '                  .substr($payload,24, 2)

        //                     .', scene extensions lenght: '      .substr($payload,26, 4)
        //                     .', scene extensions max lenght: '  .substr($payload,30, 4)
        //                     .', scene extensions : '            .substr($payload,34, 2) );
        // }

        // function decode80A3($dest, $payload, $lqi) {
        //     // <sequence number: uint8_t>   -> 2
        //     // <endpoint : uint8_t>         -> 2
        //     // <cluster id: uint16_t>       -> 4
        //     // <status: uint8_t>            -> 2
        //     // <group ID: uint16_t>         -> 4
        //     // <Src Addr: uint16_t> (added only from 3.0f version)

        //     parserLog('debug', $dest.', Type=80A3/Remove All Scene'
        //                     .', SQN='          .substr($payload, 0, 2)
        //                     .', EndPoint='     .substr($payload, 2, 2)
        //                     .', ClusterId='    .substr($payload, 4, 4)
        //                     .', Status='       .substr($payload, 8, 2)
        //                     .', group ID='     .substr($payload,10, 4)
        //                     .', source='       .substr($payload,14, 4)  );
        // }

        // function decode80A4($dest, $payload, $lqi) {
        //     // <sequence number: uint8_t>   -> 2
        //     // <endpoint : uint8_t>         -> 2
        //     // <cluster id: uint16_t>       -> 4
        //     // <status: uint8_t>            -> 2
        //     // <group ID: uint16_t>         -> 4
        //     // <scene ID: uint8_t>          -> 2
        //     // <Src Addr: uint16_t> (added only from 3.0f version)

        //     parserLog('debug', $dest.', Type=80A4/Store Scene Response'
        //                     .', SQN='          .substr($payload, 0, 2)
        //                     .', EndPoint='     .substr($payload, 2, 2)
        //                     .', ClusterId='    .substr($payload, 4, 4)
        //                     .', Status='       .substr($payload, 8, 2)
        //                     .', GroupID='      .substr($payload,10, 4)
        //                     .', SceneID='      .substr($payload,14, 2)
        //                     .', Source='       .substr($payload,16, 4)  );
        // }

        function decode80A6($dest, $payload, $lqi) {
            // parserLog('debug', ';Type: 80A6: raw data: '.$payload );

            // Cas du message retour lors d un storeScene sur une ampoule Hue
            if ( strlen($payload)==18  ) {
                // <sequence number: uint8_t>               -> 2
                // <endpoint : uint8_t>                     -> 2
                // <cluster id: uint16_t>                   -> 4
                // <status: uint8_t>                        -> 2

                // <group ID: uint16_t>                     -> 4
                // sceneId: uint8_t                       ->2

                parserLog('debug', $dest.', Type=80A6/Scene Membership'
                                .', SQN='          .substr($payload, 0, 2)      // 1
                                .', EndPoint='     .substr($payload, 2, 2)      // 1
                                .', ClusterId='    .substr($payload, 4, 4)      // 1
                                .', Status='       .substr($payload, 8, 2)      //
                                 //.'; capacity: '     .substr($payload,10, 2)
                                .', GroupID='     .substr($payload,10, 4)
                                .', SceneID='     .substr($payload,14, 2)  );
            }
            // Cas du message retour lors d un getSceneMemberShip
            else {
                // <sequence number: uint8_t>               -> 2
                // <endpoint : uint8_t>                     -> 2
                // <cluster id: uint16_t>                   -> 4
                // <status: uint8_t>                        -> 2
                // <capacity: uint8_t>                      -> 2
                // <group ID: uint16_t>                     -> 4
                // <scene count: uint8_t>                   -> 2
                // <scene list: data each element uint8_t>  -> 2
                // <Src Addr: uint16_t> (added only from 3.0f version)

                $seqNumber  = substr($payload, 0, 2);
                $endpoint   = substr($payload, 2, 2);
                $clustId  = substr($payload, 4, 4);
                $status     = substr($payload, 8, 2);
                $capacity   = substr($payload,10, 2);
                $groupID    = substr($payload,12, 4);
                $sceneCount = substr($payload,16, 2);
                $source     = substr($payload,18+$sceneCount*2, 4);

                if ($status!=0) {
                    parserLog('debug', $dest.', Type=80A6/Scene Membership => Status != 0'
                                    .', SQN='          .substr($payload, 0, 2)      // 1
                                    .', EndPoint='     .substr($payload, 2, 2)      // 1
                                    .', source='       .$source
                                    .', ClusterId='    .substr($payload, 4, 4)      // 1
                                    .', Status='       .substr($payload, 8, 2)      //
                                    .', capacity='     .substr($payload,10, 2)
                                    .', GroupID='     .substr($payload,10, 4)
                                    .', SceneID='     .substr($payload,14, 2)  );
                    return;
                }

                $sceneCount = hexdec( $sceneCount );
                $sceneId="";
                for ($i=0;$i<$sceneCount;$i++)
                {
                    // parserLog('debug', 'scene '.$i.' scene: '  .substr($payload,18+$i*2, 2));
                    $sceneId .= '-'.substr($payload,18+$sceneCount*2, 2);
                }

                // Envoie Group-Membership (pas possible car il me manque l address short.
                // $srcAddr = substr($payload, 8, 4);

                $clustId = "Scene";
                $attrId = "Membership";
                if ( $sceneId == "" ) { $data = $groupID."-none"; } else { $data = $groupID.$sceneId; }

                parserLog('debug', $dest.', Type=80A6/Scene Membership'
                                .', SQN='          .$seqNumber
                                .', EndPoint='     .$endpoint
                                .', ClusterId='    .$clustId
                                .', Status='       .$status
                                .', Capacity='     .$capacity
                                .', Source='       .$source
                                .', GroupID='      .$groupID
                                .', SceneID='      .$sceneId
                                .', Group-Scenes='.$data);

                // Je ne peux pas envoyer, je ne sais pas qui a repondu pour tester je mets l adresse en fixe d une ampoule
                $clustId = "Scene";
                $attrId = "Membership";
                // $this->msgToAbeille($dest."/".$source, $clustId, $attrId, $data);
                $attrReportN = [
                    array( "name" => $clustId.'-'.$attrId, "value" => $data ),
                ];
                $toAbeille = array(
                    // 'src' => 'parser',
                    'type' => 'attributesReportN',
                    'net' => $dest,
                    'addr' => $source,
                    'ep' => $endpoint,
                    'clustId' => $clustId,
                    'attributes' => $attrReportN,
                    'time' => time(),
                    'lqi' => $lqi
                );
                msgToMainD($toAbeille);
            }
        } // End decode80A6()

        // Telecommande Ikea
        // https://github.com/fairecasoimeme/ZiGate/issues/6
        // https://github.com/fairecasoimeme/ZiGate/issues/64
        // Button   Pres-stype  Response  command       attr
        // down     click       0x8085    0x02          None
        // down     hold        0x8085    0x01          None
        // down     release     0x8085    0x03          None
        // up       click       0x8085    0x06          None
        // up       hold        0x8085    0x05          None
        // up       release     0x8085    0x07          None
        // middle   click       0x8095    0x02          None
        // left     click       0x80A7    0x07          direction: 1
        // left     hold        0x80A7    0x08          direction: 1    => can t get that one
        // right    click       0x80A7    0x07          direction: 0
        // right    hold        0x80A7    0x08          direction: 0    => can t get that one
        // left/right release   0x80A7    0x09          None            => can t get that one
        //
        // down = brightness down, up = brightness up,
        // middle = Power button,
        // left and right = when brightness up is up left is left and right is right.
        // Holding down power button for ~10 sec will result multiple commands sent, but it wont send any hold command only release.
        // Remote won't tell which button was released left or right, but it will be same button that was last hold.
        // Remote is unable to send other button commands at least when left or right is hold down.

        function decode80A7($dest, $payload, $lqi) {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <addr: uint16_t>             -> 4
            // <cmd: uint8>                 -> 2
            // <direction: uint8>           -> 2
            // uint8 u8Attr1;
            // uint8 u8Attr2;
            // uint8 u8Attr3;
            // source int16

            // directions = {0: 'right', 1: 'left', 2: 'middle'}
            // {7: 'click', 8: 'hold', 9: 'release'}

            $seqNumber      = substr($payload, 0, 2);
            $endpoint       = substr($payload, 2, 2);
            $clustId      = substr($payload, 4, 4);
            $cmd            = substr($payload, 8, 2);
            $direction      = substr($payload,10, 2);
            $attr1          = substr($payload,12, 2);
            $attr2          = substr($payload,14, 2);
            $attr3          = substr($payload,16, 2);
            $source         = substr($payload,18, 4);

            parserLog('debug', $dest.', Type=80A7/Remote button pressed (LEFT/RIGHT) (Processed->$this->decoded but not sent to MQTT)'
                            .', SQN='          .$seqNumber
                            .', EndPoint='     .$endpoint
                            .', ClusterId='    .$clustId
                            .', cmd='          .$cmd
                            .', direction='    .$direction
                            .', u8Attr1='      .$attr1
                            .', u8Attr2='      .$attr2
                            .', u8Attr3='      .$attr3
                            .', Source='       .$source );

            // $clustId = "80A7";
            // $attrId = "Cmd";
            // $data = $cmd;
            // $this->msgToAbeille($dest."/".$source, $clustId, $attrId, $data);

            // $clustId = "80A7";
            // $attrId = "Direction";
            // $data = $direction;
            // $this->msgToAbeille($dest."/".$source, $clustId, $attrId, $data);

            $attrReportN = [
                array( "name" => '80A7-Cmd', "value" => $cmd ),
                array( "name" => '80A7-Direction', "value" => $direction ),
            ];
            $toAbeille = array(
                // 'src' => 'parser',
                'type' => 'attributesReportN',
                'net' => $dest,
                'addr' => $source,
                'ep' => $endpoint,
                'clustId' => $clustId,
                'attributes' => $attrReportN,
                'time' => time(),
                'lqi' => $lqi
            );
            msgToMainD($toAbeille);

            $this->whoTalked[] = $dest.'/'.$source;
        } // End decode80A7()

        /**
         * 0x8701/Route Discovery Confirm -  Warning: potential swap between statuses.
         *
         * @param $dest     Zigbee network (ex: Abeille1)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Does return anything as all action are triggered by sending messages in queues
         */
        // Tcharp38: What it is useful for ?
        function decode8701($dest, $payload, $lqi) {
            // NWK Code Table Chap 10.2.3 from JN-UG-3113
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            global $allErrorCode;

            $nwkStatus = substr($payload, 0, 2);
            $status = substr($payload, 2, 2);
            $addr = substr($payload, 4, 4);

            $msg = '8701/Route discovery confirm'
                //    .', MACStatus='.$status.' ('.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
                   .', MACStatus='.$status.'/'.$allErrorCode[$status][0]
                //    .', NwkStatus='.$nwkStatus.' ('.$allErrorCode[$nwkStatus][0].'->'.$allErrorCode[$nwkStatus][1].')'
                   .', NwkStatus='.$nwkStatus.'/'.$allErrorCode[$nwkStatus][0]
                   .', Addr='.$addr;

            parserLog('debug', $dest.', Type='.$msg, "8701");

            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $addr))
                monMsgFromZigate($msg); // Send message to monitor
        }

        /**
         * 8702/APS data confirm fail
         *
         * @param $dest     Zigbee network (ex: Abeille1)
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return Does return anything as all action are triggered by sending messages in queues
         */
        function decode8702($dest, $payload, $lqi) {
            global $allErrorCode;

            // <status: uint8_t>
            // <src endpoint: uint8_t>
            // <dst endpoint: uint8_t>
            // <dst address mode: uint8_t>
            // <destination address: uint64_t>
            // <seq number: uint8_t>
            $status     = substr($payload, 0, 2);
            $srcEp      = substr($payload, 2, 2);
            $dstEp      = substr($payload, 4, 2);
            $dstMode    = substr($payload, 6, 2);
            // Tcharp38: Lack of infos from Zigate site. Extracted from Domoticz plugin.
            if (in_array($dstMode, ["01", "02", "07"])) {
                $dstAddr    = substr($payload, 8, 4);
                $sqnAps     = substr($payload,12, 2);
                $nPdu       = substr($payload,14, 2);
                $aPdu       = substr($payload,16, 2);
            } else if ($dstMode == "03") { // IEEE
                $dstAddr    = substr($payload, 8, 16);
                $sqnAps     = substr($payload,24, 2);
                $nPdu       = substr($payload,26, 2);
                $aPdu       = substr($payload,28, 2);
            }
            $msgDecoded = '8702/APS data confirm fail'
               .', Status='.$status.'/'.$allErrorCode[$status][0]
               .', SrcEP='.$srcEp
               .', DstEP='.$dstEp
               .', AddrMode='.$dstMode
               .', Addr='.$dstAddr
               .', SQNAPS='.$sqnAps
               .', NPDU='.$nPdu.', APDU='.$aPdu;

            // Log
            parserLog('debug', $dest.', Type='.$msgDecoded, "8702");

            // Sending msg to cmd for flow control
            // No longer used on cmd side
            // $msg = array (
            //     'type'      => "8702",
            //     'net'       => $dest,
            //     'status'    => $status,
            //     'addr'      => $dstAddr,
            //     'sqnAps'    => $sqnAps,
            //     'nPDU'      => $nPdu,
            //     'aPDU'      => $aPdu,
            // );
            // msgToCmdAck($msg);

            // Monitor if requested
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $dstAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor
        }

        function decode8806($dest, $payload, $lqi) {
            // Command 0x0807 Get Tx Power doesn't need any parameters.
            // If command is handled successfully response will be first status(0x8000) with success status and after that Get Tx Power Response(0x8807).
            // 0x8807 has only single parameter which is uint8 power. If 0x0807 fails then response is going to be only status(0x8000) with status 1.
            // Standard power modules of JN516X(Except JN5169) modules have only 4 possible power levels (Table 5 JN-UG-3024 v2.6). These levels are based on some kind of hardware registry so there is no way to change them in firmware. In ZiGate's case this means:
            // Set/get tx value    Mapped value (dBM)
            // 0 to 31              0
            // 32 to 39             -32
            // 40 to 51             -20
            // 52 to 63             -9

            // <Power: uint8_t>
            $power = substr($payload, 0, 2);

            parserLog('debug', $dest.', Type=8806/Set TX power response, Power='.$power);

            $msg = array(
                // 'src' => 'parser',
                'type' => 'zigatePower',
                'net' => $dest,
                'power' => $power,
                'time' => time(),
            );
            msgToMainD($msg);
        }

        function decode8807($dest, $payload, $lqi) {
            // Command 0x0807 Get Tx Power doesn't need any parameters.
            // If command is handled successfully response will be first status(0x8000) with success status and after that Get Tx Power Response(0x8807).
            // 0x8807 has only single parameter which is uint8 power. If 0x0807 fails then response is going to be only status(0x8000) with status 1.
            // Standard power modules of JN516X(Except JN5169) modules have only 4 possible power levels (Table 5 JN-UG-3024 v2.6). These levels are based on some kind of hardware registry so there is no way to change them in firmware. In ZiGate's case this means:
            // Set/get tx value    Mapped value (dBM)
            // 0 to 31              0
            // 32 to 39             -32
            // 40 to 51             -20
            // 52 to 63             -9

            // <Power: uint8_t>
            $power = substr($payload, 0, 2);

            parserLog('debug', $dest.', Type=8807/Get TX power, Power='.$power);

            $msg = array(
                // 'src' => 'parser',
                'type' => 'zigatePower',
                'net' => $dest,
                'power' => $power,
                'time' => time(),
            );
            msgToMainD($msg);
        }

        // PDM dump response (Abeille's firmware only)
        function decodeAB01($net, $payload, $lqi) {
            $id = substr($payload, 0, 4);
            $status = substr($payload, 4, 2); // 00=OK, 01=does not exist, 02=Found but truncated
            $first = hexdec(substr($payload, 6, 1));
            $last = hexdec(substr($payload, 7, 1));
            $size = substr($payload, 8, 4);
            $data = substr($payload, 12);

            parserLog('debug', $net.', Type=AB01/PDM dump response'
                .', Id='.$id
                .', Status='.$status
                .', First/Last='.$first.'/'.$last
                .', Size='.$size
                .', Data='.$data);

            $zgId = substr($net, 7); // AbeilleX => X
            if (!isset($GLOBALS['zigate'.$zgId]))
                $GLOBALS['zigate'.$zgId] = [];
            if ($first)
                $GLOBALS['zigate'.$zgId]['pdms'] = []; // Clear previous content
            $GLOBALS['zigate'.$zgId]['pdms'][$id] = array(
                'status' => $status,
                'size' => $size,
                'data' => $data
            );

            if ($last) {
                // Dump to file 'tmp/AbeillePdm-AbeilleX.json'
                $table = [];
                $table['signature'] = "Abeille PDM tables";
                $table['net'] = $net;
                $table['collectTime'] = time();
                $table['fwVersion'] = $GLOBALS['zigate'.$zgId]['fwVersionMaj'].'-'.$GLOBALS['zigate'.$zgId]['fwVersionMin'];
                $table['pdms'] = $GLOBALS['zigate'.$zgId]['pdms'];
                $json = json_encode($table);
                file_put_contents(__DIR__."/../../tmp/AbeillePdm-".$net.".json", $json);
            }
        }

        // PDM restore response (Abeille's firmware only)
        function decodeAB03($net, $payload, $lqi) {
            $id = substr($payload, 0, 4);
            $status = substr($payload, 4, 2); // ?

            parserLog('debug', $net.', Type=AB03/PDM restore response'
                .', Id='.$id
                .', Status='.$status);

            // Sending msg to cmd for flow control
            $msg = array (
                'type'      => "AB03",
                'net'       => $net,
                'id'        => $id,
                'status'    => $status,
            );
            msgToCmdAck($msg);
        }

        /* 9999/Extended error */
        function decode9999($net, $payload, $lqi) {
            /* FW >= 3.1e
               Extended Status: uint8_t */
            $extStatus = substr($payload, 0, 2);

            $zgId = substr($net, 7); // AbeilleX => X
            $extra = '';
            if (isset($GLOBALS['zigate'.$zgId]) && isset($GLOBALS['zigate'.$zgId]['nPdu']))
                $extra = ', NPDU='.$GLOBALS['zigate'.$zgId]['nPdu'];
            if (isset($GLOBALS['zigate'.$zgId]) && isset($GLOBALS['zigate'.$zgId]['aPdu']))
                $extra .= ', APDU='.$GLOBALS['zigate'.$zgId]['aPdu'];

            $decoded = '9999/Extended error'.', ExtStatus='.$extStatus.$extra;
            parserLog('debug', $net.', Type='.$decoded);
        }

        // /**
        //  * WHile processing AbeilleParser can schedule action by adding action in the queue like for exemple:
        //  * $this->actionQueue[] = array( 'when'=>time()+5, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$ieee );
        //  *
        //  * @param $this->actionQueue
        //  * @return none
        //  */
        // function processActionQueue() {
        //     if ( !($this->actionQueue) ) return;
        //     if ( count($this->actionQueue) < 1 ) return;

        //     foreach ( $this->actionQueue as $key=>$action ) {
        //         if ( $action['when'] < time()) {
        //             if (!method_exists($this, $action['what'])) {
        //                 parserLog('debug', "processActionQueue(): Unknown action '".json_encode($action)."'", 'processActionQueue');
        //                 continue;
        //             }
        //             parserLog('debug', "processActionQueue(): action '".json_encode($action)."'", 'processActionQueue');
        //             $fct = $action['what'];
        //             if ( isset($action['parm0'])) {
        //                 $this->$fct($action['parm0'], $action['parm1'], $action['parm2'], $action['parm3']);
        //             } else {
        //                 $this->$fct($action['addr']);
        //             }
        //             unset($this->actionQueue[$key]);
        //         }
        //     }
        // }

        /**
         * With device on battery we have to wait for them to wake up before sending them command:
         * $this->wakeUpQueue[] = array( 'which'=>logicalId, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$ieee );
         *
         * @param logicalId
         * @param $this->wakeUpQueue
         * @return none
         */
        // Tcharp38: No longer in use right now.
        // function processWakeUpQueue() {
        //     if ( !($this->wakeUpQueue)) {
        //         unset($this->whoTalked);
        //         return;
        //     }
        //     if ( count($this->wakeUpQueue)<1 ) {
        //         unset($this->whoTalked);
        //         return;
        //     }
        //     if ( !($this->whoTalked) ) return;
        //     if ( count($this->whoTalked) < 1 ) return;

        //     parserLog('debug', 'processWakeUpQueue(): ------------------------------>');

        //     foreach( $this->whoTalked as $keyWho=>$who ) {
        //         parserLog('debug', 'processWakeUpQueue(): '.$who.' talked');
        //         foreach ( $this->wakeUpQueue as $keyWakeUp=>$action ) {
        //             if ( $action['which'] == $who ) {
        //                 if ( method_exists($this, $action['what'])) {
        //                     parserLog('debug', 'processWakeUpQueue(): action: '.json_encode($action), 'processWakeUpQueue');
        //                     $fct = $action['what'];
        //                     if ( isset($action['parm0'])) {
        //                         $this->$fct($action['parm0'],$action['parm1'],$action['parm2'],$action['parm3']);
        //                     } else {
        //                         $this->$fct($action['addr']);
        //                     }
        //                     unset($this->wakeUpQueue[$keyWakeUp]);
        //                 }
        //             }
        //             unset($this->whoTalked[$keyWho]);
        //         }
        //     }

        //     parserLog('debug', 'processWakeUpQueue(): <------------------------------');
        // }

        // /* Called on any receipt from a device meaning it is awake, at least for a very short time */
        // function deviceIsAwake($net, $addr) {
        //     if (!isset($this->pendingMsg))
        //         return;
        //     if (!isset($this->pendingMsg[$net]))
        //         return;
        //     if (!isset($this->pendingMsg[$net][$addr]))
        //         return;
        // }
    } // class AbeilleParser
?>
