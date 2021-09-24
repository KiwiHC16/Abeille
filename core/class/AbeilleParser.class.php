<?php

    include_once __DIR__.'/../../core/class/AbeilleMsg.php';

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
        public $queueKeyParserToAbeille = null; // Old communication path to Abeille
        public $queueKeyParserToAbeille2 = null; // New communication path to Abeille
        public $queueKeyParserToCmd = null;
        public $parameters_info;
        public $actionQueue; // queue of action to be done in Parser like config des NE ou get info des NE
        public $wakeUpQueue; // queue of command to be sent when the device wakes up.
        public $whoTalked;   // store the source of messages to see if any message are waiting for them in wakeUpQueue

        // ZigBee Cluster Library - Document 075123r02ZB - Page 79 - Table 2.15
        // Data Type -> Description, # octets
        public $zbDataTypes = array(
                                 '00' => array( 'Null', 0 ),
                                 // 01-07: reserved
                                 '08' => array( 'General Data', 1),
                                 '09' => array( 'General Data', 2),
                                 '0A' => array( 'General Data', 3),
                                 '0B' => array( 'General Data', 4),
                                 '0C' => array( 'General Data', 5),
                                 '0D' => array( 'General Data', 6),
                                 '0E' => array( 'General Data', 7),
                                 '0F' => array( 'General Data', 8),

                                 '10' => array( 'Bool', 1 ), // Boolean
                                 // 0x11-0x17 Reserved
                                 '18' => array( 'Bitmap', 1 ),
                                 '19' => array( 'Bitmap', 2 ),
                                 '1A' => array( 'Bitmap', 3 ),
                                 '1B' => array( 'Bitmap', 4 ),
                                 '1C' => array( 'Bitmap', 5 ),
                                 '1D' => array( 'Bitmap', 6 ),
                                 '1E' => array( 'Bitmap', 7 ),
                                 '1F' => array( 'Bitmap', 8 ),

                                 '20' => array( 'Uint8',  1 ), // Unsigned 8-bit int
                                 '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int
                                 '22' => array( 'Uint24', 3 ), // Unsigned 24-bit int
                                 '23' => array( 'Uint32', 4 ), // Unsigned 32-bit int
                                 '24' => array( 'Uint40', 5 ), // Unsigned 40-bit int
                                 '25' => array( 'Uint48', 6 ), // Unsigned 48-bit int
                                 '26' => array( 'Uint56', 7 ), // Unsigned 56-bit int
                                 '27' => array( 'Uint64', 8 ), // Unsigned 64-bit int

                                 '28' => array( 'Int8',  1 ), // Signed 8-bit int
                                 '29' => array( 'Int16', 2 ), // Signed 16-bit int
                                 '2A' => array( 'Int24', 3 ), // Signed 24-bit int
                                 '2B' => array( 'Int32', 4 ), // Signed 32-bit int
                                 '2C' => array( 'Int40', 5 ), // Signed 40-bit int
                                 '2D' => array( 'Int48', 6 ), // Signed 48-bit int
                                 '2E' => array( 'Int56', 7 ), // Signed 56-bit int
                                 '2F' => array( 'Int64', 8 ), // Signed 64-bit int

                                 '30' => array( 'Enumeration', 1 ),
                                 '31' => array( 'Enumeration', 2 ),
                                 // 0x32-0x37 Reserved

                                 '38' => array( 'SemiPrecision',   2 ),
                                 '39' => array( 'Single', 4 ), // Single precision
                                 '3A' => array( 'DoublePrecision', 8 ),
                                 // 0x3b-0x3f
                                 // 0x40 Reserved
                                 '41' => array( 'String - Octet string',             'Defined in first octet' ),
                                 '42' => array( 'String - Charactere string',        'Defined in first octet' ),
                                 '43' => array( 'String - Long octet string',        'Defined in first two octets' ),
                                 '44' => array( 'String - Long charactere string',   'Defined in first two octets' ),
                                 // 0x45-0x47 Reserved
                                 '48' => array( 'Ordered sequence - Array',          '2+sum of lengths of contents' ),
                                 // 0x49-0x4b Reserved
                                 '4C' => array( 'Ordered sequence - Structure',      '2+sum of lengths of contents' ),
                                 // 0x4d-0x4f Reserved
                                 '50' => array( 'Collection - Set',      'Sum of lengths of contents' ),
                                 '51' => array( 'Collection - Bag',      'Sum of lengths of contents' ),
                                 //0x52-0x57 Reserved
                                 // 0x58-0xdf Reserved
                                 'E0' => array( 'Time - Time of day', 4 ),
                                 'E1' => array( 'Time - Date', 4 ),
                                 'E2' => array( 'Time - UTCTime', 4 ),
                                 // 0xe3 - 0xe7 Reserved
                                 'E8' => array( 'Identifier - Cluster ID', 2 ),
                                 'E9' => array( 'Identifier - Attribute ID', 2 ),
                                 'EA' => array( 'Identifier - BACnet OID', 4 ),
                                 // 0xeb-0xef Reserved
                                 'F0' => array( 'Miscellaneous - IEEE address', 8 ),
                                 'F1' => array( 'Miscellaneous - 128 bit security key', 16 ),
                                 // 0xF2-0xFe Reserved
                                 'FF' => array( 'Unknown', 0 ),
        );

        /* Returns ZigBee data type or array('?'.$type.'?', 0) if unknown */
        function getZbDataType($type)
        {
            if (array_key_exists($type, $this->zbDataTypes))
                return $this->zbDataTypes[$type];
            return array('?'.$type.'?', 0);
        }

        function __construct() {
            global $argv;

            /* Configuring log library to use 'logMessage()' */
            logSetConf("AbeilleParser.log", true);

            parserLog("debug", "AbeilleParser constructor", "AbeilleParserClass");
            $this->parameters_info = AbeilleTools::getParameters();

                        // $this->requestedlevel = $argv[7];
            $this->requestedlevel = '' ? 'none' : $argv[1];
            $GLOBALS['requestedlevel'] = $this->requestedlevel ;

            $abQueues = $GLOBALS['abQueues'];
            $this->queueKeyParserToAbeille      = msg_get_queue(queueKeyParserToAbeille);
            $this->queueKeyParserToAbeille2     = msg_get_queue(queueKeyParserToAbeille2);
            $this->queueKeyParserToCmd          = msg_get_queue(queueKeyParserToCmd);
            $this->queueKeyParserToCmdSemaphore = msg_get_queue(queueKeyParserToCmdSemaphore);
            $this->queueParserToLQI             = msg_get_queue($abQueues["parserToLQI"]["id"]);
            $this->queueParserToLQIMax          = $abQueues["parserToLQI"]["max"];
        }

        // $srcAddr = dest / shortaddr
        // Tcharp38: This function is obsolete. It is smoothly replaced by msgToAbeille2() with new msg format
        function msgToAbeille($srcAddr, $clustId, $attrId, $data)
        {
            // dest / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = true;

            $msgAbeille->message = array(
                                    'topic' => $srcAddr."/".$clustId."-".$attrId,
                                    'payload' => $data,
                                     );
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == false) {
                parserLog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                parserLog("error", "  Message=".json_encode($msgAbeille));
            }

            $msgAbeille->message = array('topic' => $srcAddr."/Time-TimeStamp", 'payload' => time());
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == false) {
                parserLog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                parserLog("error", "  Message=".json_encode($msgAbeille));
            }

            $msgAbeille->message = array('topic' => $srcAddr."/Time-Time", 'payload' => date("Y-m-d H:i:s"));
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == false) {
                parserLog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                parserLog("error", "  Message=".json_encode($msgAbeille));
            }
        }

        // Tcharp38: This function is obsolete is smoothly replaced by msgToAbeille2() with new msg format
        // function msgToAbeilleFct($srcAddr, $fct, $data)
        // {
        //     // $srcAddr = dest / shortaddr
        //     // dest / short addr / Cluster ID - Attr ID -> data

        //     $msgAbeille = new MsgAbeille;
        //     $msgAbeille->message = array( 'topic' => $srcAddr."/".$fct, 'payload' => $data, );

        //     $errorcode = 0;
        //     if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false, $errorcode)) {
        //         // parserLog("debug","(fct msgToAbeilleFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
        //     }
        //     else {
        //         parserLog("debug", "(fct msgToAbeilleFct) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
        //     }
        // }

        // Tcharp38: This function is obsolete is smoothly replaced by msgToAbeille2() with new msg format
        // function msgToAbeilleCmdFct( $fct, $data)
        // {
        //      // Abeille / short addr / Cluster ID - Attr ID -> data

        //     $msgAbeille = new MsgAbeille;
        //     $msgAbeille->message = array( 'topic' => $fct, 'payload' => $data, );

        //     $errorcode = 0;
        //     if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false, $errorcode)) {
        //         // parserLog("debug","(fct msgToAbeilleCmdFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
        //         // print_r(msg_stat_queue($queue));
        //     }
        //     else {
        //         parserLog("debug","(fct msgToAbeilleCmdFct) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
        //     }
        // }

        /* New function to send msg to Abeille.
           Msg format is now flexible and can transport a bunch of infos coming from zigbee event instead of splitting them
           into several messages to Abeille. */
        function msgToAbeille2($msg) {
            $errorcode = 0;
            if (msg_send($this->queueKeyParserToAbeille2, 1, json_encode($msg), false, false, $errorcode) == false) {
                parserLog("debug", "msgToAbeille2(): ERROR ".$errorcode);
            }
        }

        /* Send message to 'AbeilleCmd' thru 'queueKeyParserToCmd' */
        function msgToCmd($topic, $payload)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data
            //parserLog("debug", "msgToCmd(): topic=".$topic.", payload=".$payload);

            $msgAbeille = new MsgAbeille;
            $msgAbeille->message = array( 'topic' => $topic, 'payload' => $payload );

            $errorcode = 0;
            if (msg_send($this->queueKeyParserToCmd, 1, $msgAbeille, true, false, $errorcode) == false) {
                parserLog("debug", "msgToCmd() ERROR: Can't write to 'queueKeyParserToCmd', error=".$errorcode);
            }
        }

        /* Send message to 'AbeilleLQI'.
           Returns: true=ok, false=ERROR */
        function msgToLQICollector($srcAddr, $NTableEntries, $NTableListCount, $StartIndex, $NList)
        {
            $msg = array(
                'type' => '804E',
                'srcAddr' => $srcAddr,
                'tableEntries' => $NTableEntries,
                'tableListCount' => $NTableListCount,
                'startIndex' => $StartIndex,
                'list' => $NList
            );

            /* Message size control. If too big it would block queue forever */
            $msgJson = json_encode($msg);
            $size = strlen($msgJson);
            $max = $this->queueParserToLQIMax;
            if ($size > $max) {
                parserLog("error", "msgToLQICollector(): Message trop gros ignoré (taille=".$size.", max=".$max.")");
                return false;
            }
            if (msg_send($this->queueParserToLQI, 1, $msgJson, false, false, $errorCode) == true)
                return true;

            parserLog("error", "msgToLQICollector(): Impossible d'envoyer le msg vers AbeilleLQI (err ".$errorCode.")");
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
            if ($status['msg_qnum'] >= 2) {
                parserLog('debug', '  msgToClient(): Pending messages in queue => doing nothing');
                return;
            }

            $jsonMsg = json_encode($msg);
            $size = strlen($jsonMsg);
            $max = $abQueues['parserToCli']['max'];
            if ($size > $max) {
                parserLog("error", "msgToClient(): Message trop gros ignoré (taille=".$size.", max=".$max.")");
                return false;
            }
            if (msg_send($queue, 1, $jsonMsg, false, false, $errorcode) == false) {
                parserLog("debug", "  msgToClient(): ERROR ".$errorcode);
            } else
                parserLog("debug", "  msgToClient(): Sent ".json_encode($msg));
        }

        /* Check if eq is part of supported or user/custom devices names.
           Returns: true is supported, else false */
        function findJsonConfig(&$eq, $by='modelIdentifier') {
            $ma = ($eq['manufacturer'] === false) ? 'false' : $eq['manufacturer'];
            $mo = ($eq['modelIdentifier'] === false) ? 'false' : $eq['modelIdentifier'];
            $lo = ($eq['location'] === false) ? 'false' : $eq['location'];
            parserLog('debug', "  findJsonConfig(), manuf='".$ma."', model='".$mo."', loc='".$lo."'");

            /* Looking for corresponding JSON if supported device.
               - Look with '<modelId>_<manufacturer>' identifier
               - If not found, look with '<modelId>' identifier
               - And if still not found, use 'defaultUnknown'
             */
            $jsonName = '';
            $jsonLocation = "Abeille"; // Default location
            if ($by == 'modelIdentifier') {
                /* Search by modelId and manufacturer */
                if (($eq['manufacturer'] !== false) && ($eq['manufacturer'] != '')) {
                    $identifier = $eq['modelIdentifier'].'_'.$eq['manufacturer'];
                     if (isset($GLOBALS['customEqList'][$identifier])) {
                        $jsonName = $identifier;
                        $jsonLocation = "local";
                        parserLog('debug', "  EQ is supported as user/custom config with '".$identifier."' identifier");
                    } else if (isset($GLOBALS['supportedEqList'][$identifier])) {
                        $jsonName = $identifier;
                        parserLog('debug', "  EQ is supported with '".$identifier."' identifier");
                    }
                }
                if ($jsonName == '') {
                    $identifier = $eq['modelIdentifier'];
                     if (isset($GLOBALS['customEqList'][$identifier])) {
                        $jsonName = $identifier;
                        $jsonLocation = "local";
                        parserLog('debug', "  EQ is supported as user/custom config with '".$identifier."' identifier");
                    } else if (isset($GLOBALS['supportedEqList'][$identifier])) {
                        $jsonName = $identifier;
                        parserLog('debug', "  EQ is supported with '".$identifier."' identifier");
                    }
                }
            } else {
                /* Search by location */
                $identifier = $eq['location'];
                 if (isset($GLOBALS['customEqList'][$identifier])) {
                    $jsonName = $identifier;
                    $jsonLocation = "local";
                    parserLog('debug', "  EQ is supported as user/custom config with '".$identifier."' location identifier");
                } else if (isset($GLOBALS['supportedEqList'][$identifier])) {
                    $jsonName = $identifier;
                    parserLog('debug', "  EQ is supported with '".$identifier."' location identifier");
                }
            }

            if ($jsonName == '') {
                $eq['jsonId'] = "defaultUnknown";
                $eq['jsonLocation'] = "Abeille";
                parserLog('debug', "  EQ is UNsupported. 'defaultUnknown' config will be used");
                return false;
            }

            // Read JSON to get list of commands to execute
            $eqConfig = AbeilleTools::getDeviceConfig($jsonName, $jsonLocation);
            $eq['jsonId'] = $jsonName;
            $eq['jsonLocation'] = $jsonLocation;
            $eq['config'] = $eqConfig;
            return true;
        }

        /* Cached EQ infos reminder:
            $GLOBALS['eqList'][<network>][<addr>] = array(
                'ieee' => $ieee,
                'capa' => '', // MAC capa from dev announce
                'rejoin' => '', // Rejoin info from device announce
                'status' => 'identifying', // identifying, configuring, discovering, idle
                'time' => time(),
                'epList' => '', // List of end points
                'epFirst' => '', // First end point (usually 01)
                'manufacturer' => null (undef)/false (unsupported)/'xx'
                'modelIdentifier' => null (undef)/false (unsupported)/'xx'
                'location' => null (undef)/false (unsupported)/'xx'
                'jsonId' => '', // JSON identifier
                'jsonLocation' => '', // JSON location ("Abeille"=default, or "local")
            );
            identifying: req EP list + manufacturer + modelId + location
                         Note: Special case for Xiaomi which may not support "Active EP request".
            configuring: execute cmds with 'execAtCreation' flag
            discovering: for unknown EQ
            idle: all actions ended
        */

        /* Called on device announce. */
        function deviceAnnounce($net, $addr, $ieee, $capa, $rejoin) {
            if (!isset($GLOBALS['eqList']))
                $GLOBALS['eqList'] = [];
            if (!isset($GLOBALS['eqList'][$net]))
                $GLOBALS['eqList'][$net] = [];

            /* If no device with 'addr', may be due to short addr change */
            if (!isset($GLOBALS['eqList'][$net][$addr])) {
                /* Looking for eq by its ieee to update addr which may have changed */
                foreach ($GLOBALS['eqList'][$net] as $oldaddr => $eq) {
                    if ($eq['ieee'] != $ieee)
                        continue;

                    $GLOBALS['eqList'][$net][$addr] = $eq;
                    unset($GLOBALS['eqList'][$net][$oldaddr]);
                    parserLog('debug', '  EQ already known: Addr updated from '.$oldaddr.' to '.$addr);
                    break;
                }
            }

            if (isset($GLOBALS['eqList'][$net][$addr])) {
                $eq = &$GLOBALS['eqList'][$net][$addr]; // By ref
                if ($eq['ieee'] != $ieee) {
                    parserLog('debug', '  ERROR: There is a different EQ (ieee='.$eq['ieee'].') for addr '.$addr);
                    return;
                }
                parserLog('debug', '  EQ already known: Status='.$eq['status'].', since='.$eq['time'].', time='.time());

                /* Checking if it's not a too fast consecutive device announce.
                   Note: Assuming 4sec max per phase */
                if (($eq['time'] + 4) > time()) {
                    if ($eq['status'] == 'identifying')
                        parserLog('debug', '  Device identification already ongoing');
                    else if ($eq['status'] == 'discovering')
                        parserLog('debug', '  Device discovering already ongoing');
                    return; // Last step is not older than 4sec
                }
            } else {
                /* It's an unknown eq */
                parserLog('debug', '  EQ new to parser');
                $GLOBALS['eqList'][$net][$addr] = array(
                    'ieee' => $ieee,
                    'capa' => $capa,
                    'rejoin' => $rejoin,
                    'status' => '', // identifying, discovering, configuring
                    'time' => '',
                    'epList' => '',
                    'epFirst' => '',
                    'manufacturer' => null, // null=undef, false=unsupported, else 'value'
                    'modelIdentifier' => null, // null=undef, false=unsupported, else 'value'
                    'location' => null, // null=undef, false=unsupported, else 'value'
                    'jsonId' => '',
                    'jsonLocation' => ''
                );
                $eq = &$GLOBALS['eqList'][$net][$addr]; // By ref
            }

            /* Starting identification phase */
            $eq['status'] = 'identifying';
            $eq['time'] = time();

            if ($eq['epList'] != '') {
                /* 'epList' is already known => trig next step */
                $this->deviceUpdate($net, $addr, 'epList', $eq['epList']);
                return;
            }

            /* Special trick for Xiaomi for which some devices (at least v1) do not answer to "Active EP request".
                Old sensor even not answer to manufacturer request. */
            $xiaomi = (substr($ieee, 0, 9) == "00158D000") ? true : false;
            if ($xiaomi) {
                parserLog('debug', '  Xiaomi specific identification.');
                $eq['manufacturer'] = 'LUMI';
                $eq['epList'] = "01";
                $eq['epFirst'] = "01";
                $this->deviceUpdate($net, $addr, 'epList', $eq['epList']);
                return;
            }

            /* Default identification: need EP list */
            parserLog('debug', '  Requesting active end points list');
            $this->msgToCmd("Cmd".$net."/0000/ActiveEndPoint", "address=".$addr);
        }

        /* 2nd step of identification phase.
           Wait for enough info to identify device (manuf, modelId, location) */
        function deviceUpdate($net, $addr, $updType, $value) {
            if (!isset($GLOBALS['eqList']))
                return; // No dev announce before
            if (!isset($GLOBALS['eqList'][$net]))
                return; // No dev announce before
            if (!isset($GLOBALS['eqList'][$net][$addr]))
                return; // No dev announce before

            $eq = &$GLOBALS['eqList'][$net][$addr]; // By ref
            $v = ($value === false) ? 'false' : $value;
            parserLog('debug', "  deviceUpdate('".$updType."', '".$v."'), status=".$eq['status']);

            /* Updating entry */
            $eq[$updType] = $value;
            if ($updType == 'epList') {
                $eqArr = explode('/', $value);
                $eq['epFirst'] = $eqArr[0];
            }

            /* If not in 'identifying' phase, no more to do */
            if ($eq['status'] != 'identifying')
                return;

            if ($updType == 'epList') {
                /* Any other info missing to identify device ? */
                if (!isset($eq['manufacturer'])) {
                    parserLog('debug', '  Requesting manufacturer from EP '.$eq['epFirst']);
                    // $this->msgToCmd("Cmd".$net."/0000/getManufacturerName", "address=".$addr.'&destinationEndPoint='.$eq['epFirst']);
                    $this->msgToCmd("Cmd".$net."/".$addr."/readAttribute", "ep=".$eq['epFirst']."&clustId=0000&attrId=0004");
                }
                if (!isset($eq['modelIdentifier'])) {
                    parserLog('debug', '  Requesting modelIdentifier from EP '.$eq['epFirst']);
                    // $this->msgToCmd("Cmd".$net."/0000/getName", "address=".$addr.'&destinationEndPoint='.$eq['epFirst']);
                    $this->msgToCmd("Cmd".$net."/".$addr."/readAttribute", "ep=".$eq['epFirst']."&clustId=0000&attrId=0005");
                }
                if (!isset($eq['location'])) {
                    parserLog('debug', '  Requesting location from EP '.$eq['epFirst']);
                    // $this->msgToCmd("Cmd".$net."/0000/getLocation", "address=".$addr.'&destinationEndPoint='.$eq['epFirst']);
                    $this->msgToCmd("Cmd".$net."/".$addr."/readAttribute", "ep=".$eq['epFirst']."&clustId=0000&attrId=0010");
                }
            }

            /* Enough infos to try to identify device ?
               Identification process reminder
                - if modelId is supported
                    - search for JSON with 'modelId_manuf' then 'modelId'
                    - if found => configure
                    - if not found => discover
                - else if location is supported
                    - search for JSON with 'location'
                    - if found => configure
                    - if not found => discover */
            if (!isset($eq['modelIdentifier']))
                return; // Need value or false (unsupported)
            if ($eq['modelIdentifier'] !== false) {
                if (!isset($eq['manufacturer']))
                    return; // Need value or false (unsupported)
                /* Manufacturer & modelId attributes returned */
                $this->findJsonConfig($eq, 'modelIdentifier');
            } else if (!isset($eq['location'])) {
                return; // Need value or false (unsupported)
            } else if ($eq['location'] !== false) {
                /* ModelId UNsupported. Trying with 'location' */
                $this->findJsonConfig($eq, 'location');
            } else {
                parserLog('debug', "  WARNING: Unidentified device ! Can't do anything.");
                return;
            }

            /* Device is identified and 'jsonId' indicates how to support it. */
            // Tcharp38: If new dev announce of already known device, should we reconfigure it anyway ?
            if ($eq['jsonId'] != 'defaultUnknown')
                $this->deviceConfigure($net, $addr);
            else
                $this->deviceDiscover($net, $addr);
        } // End deviceUpdate()

        /* Go thru EQ commands and execute all those marked 'execAtCreation' */
        function deviceConfigure($net, $addr) {
            parserLog('debug', "  deviceConfigure(".$net.", ".$addr.")");

            $eq = &$GLOBALS['eqList'][$net][$addr];
            $eq['status'] = 'configuring';

            $cmds = $eq['config']['commands'];
            parserLog('debug', "    cmds=".json_encode($cmds));
            foreach ($cmds as $cmdJName => $cmd) {
                if (!isset($cmd['configuration']))
                    continue; // No 'configuration' section then no 'execAtCreation'
                $c = $cmd['configuration'];
                if (!isset($c['execAtCreation']))
                    continue;
                if (isset($c['execAtCreationDelay']))
                    $delay = $c['execAtCreationDelay'];
                else
                    $delay = 0;
                parserLog('debug', "    exec cmd ".$cmdJName." with delay ".$delay);
                $topic = $c['topic'];
                $request = $c['request'];
                // TODO: #EP# defaulted to first EP but should be
                //       defined in cmd use if different target EP
                $request = str_replace('#EP#', $eq['epFirst'], $request);
                $request = str_replace('#addrIEEE#', $eq['ieee'], $request);
                $zgId = substr($net, 7); // 'AbeilleX' => 'X'
                $request = str_replace('#ZiGateIEEE#', $GLOBALS['zigate'.$zgId]['ieee'], $request);
parserLog('debug', '      topic='.$topic.', request='.$request);
        //         // Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInclusion, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$topic."&time=".(time()+$cmd->getConfiguration('execAtCreationDelay')), $request );
                if ($delay == 0)
                    $this->msgToCmd("Cmd".$net."/".$addr."/".$topic, $request);
                else {
                    $delay = time() + $delay;
                    $this->msgToCmd("TempoCmd".$net."/".$addr."/".$topic.'&time='.$delay, $request);
                }
            }

            /* Config ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                'src' => 'parser',
                'type' => 'eqAnnounce',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $eq['ieee'],
                'ep' => $eq['epFirst'],
                'jsonId' => $eq['jsonId'],
                'jsonLocation' => $eq['jsonLocation'], // "Abeille" or "local"
                'capa' => $eq['capa'],
                'time' => time()
            );
            $this->msgToAbeille2($msg);

            // TODO: Tcharp38: 'idle' state might be too early since execAtCreation commands might not be completed yet
            $eq['status'] = 'idle';
        }

        /* Unknown EQ. Attempting to discover it. */
        function deviceDiscover($net, $addr) {
            parserLog('debug', "  deviceDiscover()");

            $eq = &$GLOBALS['eqList'][$net][$addr];
            $eq['status'] = 'discovering';

            $this->discoverLog('***');
            $this->discoverLog('*** New equipement discovery');
            $this->discoverLog('***');
            $this->discoverLog('Network: '.$net);
            $this->discoverLog('IEEE: '.$eq['ieee']);
            $this->discoverLog('EP list: '.$eq['epList']);
            $m = ($eq['manufacturer'] === false) ? "-unsupported-" : "'".$eq['manufacturer']."'";
            $this->discoverLog('Manufacturer: '.$m);
            $m = ($eq['modelIdentifier'] === false) ? "-unsupported-" : "'".$eq['modelIdentifier']."'";
            $this->discoverLog('ModelId: '.$m);
            $l = ($eq['location'] === false) ? "-unsupported-" : "'".$eq['location']."'";
            $this->discoverLog('Location: '.$l);
            $this->discoverLog('MAC capa: '.$eq['capa']);

            /* EQ is unsupported. Need to interrogate it to find main supported functions */
            $epArr = explode('/', $eq['epList']);
            foreach ($epArr as $ep) {
                parserLog('debug', '  Requesting simple descriptor for EP '.$ep);
                $this->msgToCmd("Cmd".$net."/0000/SimpleDescriptorRequest", "address=".$addr.'&endPoint='.$ep);
            }

            /* Discover ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                'src' => 'parser',
                'type' => 'eqAnnounce',
                'net' => $net,
                'addr' => $addr,
                'ieee' => $eq['ieee'],
                'ep' => $eq['epFirst'],
                'jsonId' => $eq['jsonId'],
                'jsonLocation' => $eq['jsonLocation'], // "Abeille" or "local"
                'capa' => $eq['capa'],
                'time' => time()
            );
            $this->msgToAbeille2($msg);
        }

        /* Returns true if net/addr EQ is in "discovering" state, else false */
        function discoveringState($net, $addr) {
            if (!isset($GLOBALS['eqList']))
                return false;
            if (!isset($GLOBALS['eqList'][$net]))
                return false;
            if (!isset($GLOBALS['eqList'][$net][$addr]))
                return false;
            $eq = $GLOBALS['eqList'][$net][$addr];
            if ($eq['status'] != 'discovering')
                return false;
            return true;
        }

        /* Append msg to 'AbeilleDiscover.log' */
        function discoverLog($msg) {
            $logPath = jeedom::getTmpFolder("Abeille")."/AbeilleDiscover.log";
            file_put_contents($logPath, $msg."\n", FILE_APPEND);
        }

        /* Clean modelIdentifier, removing some unwanted chars */
        function cleanModelId($modelId) {
            // Le capteur de temperature rond V1 xiaomi envoie spontanement son nom: ->lumi.sensor_ht<- mais envoie ->lumi.sens<- sur un getName
            if ($modelId == "lumi.sens") $modelId = "lumi.sensor_ht";

            if ($modelId == "TRADFRI Signal Repeater") $modelId = "TRADFRI signal repeater";

            if ($modelId == "lumi.sensor_swit") $modelId = "lumi.sensor_switch.aq3";

            // Work-around: getName = lumi.sensor_86sw2Un avec probablement des caractere cachés alors que lorsqu'il envoie son nom spontanement c'est lumi.sensor_86sw2 ou l inverse, je ne sais plus
            if (strpos($modelId, "sensor_86sw2") > 2) { $modelId="lumi.sensor_86sw2"; }

            if (!strncasecmp($modelId, "lumi.", 5))
                $modelId = substr($modelId, 5); // Remove leading "lumi." case insensitive

            //remove all space in names for easier filename handling
            $modelId = str_replace(' ', '', $modelId);

            // On enleve le / comme par exemple le nom des equipements Legrand
            $modelId = str_replace('/', '', $modelId);

            // On enleve le * Ampoules GU10 Philips #1778
            $modelId = str_replace('*', '', $modelId);

            // On enleve les 0x00 comme par exemple le nom des equipements Legrand
            $modelId = str_replace("\0", '', $modelId);

            return $modelId;
        }

        function procmsg($topic, $payload)
        {
            // AbeilleParser traite les messages venant du port serie mais rien venant de MQTT, pas de besoin.
        }

        function hex2str($hex)   {
            $str = '';
            for ($i = 0; $i < strlen($hex); $i += 2) {
                $str .= chr(hexdec(substr($hex, $i, 2)));
            }

            return $str;
        }

        // Fonction dupliquée dans Abeille.
        public function volt2pourcent($voltage) {
            $max = 3.135;
            $min = 2.8;
            if ( $voltage/1000 > $max ) {
                parserLog('debug', '  Voltage remonte par le device a plus de '.$max.'V. Je retourne 100%.' );
                return 100;
            }
            if ( $voltage/1000 < $min ) {
                parserLog('debug', '  Voltage remonte par le device a moins de '.$min.'V. Je retourne 0%.' );
                return 0;
            }
            return round(100-((($max-($voltage/1000))/($max-$min))*100));
        }

        /* Zigbee type 0x10 to string */
        function convBoolToString($value) {
            if (hexdec($value) == 0)
                return "0";
            return "1"; // Any value != 0 means TRUE
        }

        /* Zigbee type 0x39 to string */
        function convSingleToString($value) {
          return unpack('f', pack('H*', $value ))[1];
        }

        function convUint8ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint16ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint24ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint32ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint40ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint48ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint56ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint64ToString($value) {
            return base_convert($value, 16, 10);
        }

        /* Zigbee types 0x28..0x2f to string */
        function convInt8ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7f) // is negative
                $num -= 0x100;
            return sprintf("%d", $num);
        }
        function convInt16ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7fff) // is negative
                $num -= 0x10000;
            return sprintf("%d", $num);
        }
        function convInt24ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7fffff) // is negative
                $num -= 0x1000000;
            return sprintf("%d", $num);
        }
        function convInt32ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7fffffff) // is negative
                $num -= 0x100000000;
            $conv = sprintf("%d", $num);
            // parserLog('debug', 'convInt32ToString value='.$value.', conv='.$conv );
            return $conv;
        }

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

        /* Convert hex string to proper data type.
           'hexString' = input data (hexa string format)
           'reorder' = true if input is raw string, else false
           'dataSize' is size of value in Bytes
           'dataRaw' is the extracted & reordered hex string value
           Returns value according to type or false if error. */
        function decodeDataType($hexString, $dataType, $reorder, &$dataSize, &$dataRaw) {
            // Compute value size according to data type
            switch ($dataType) {
            case "10": // Boolean
            case "18": // 8-bit bitmap
            case "20": // Uint8
            case "28": // Int8
                $dataSize = 1;
                break;
            case "21": // Uint16
            case "29": // Int16
                $dataSize = 2;
                break;
            case "0A": // Discrete: 24-bit data
            case "22": // Uint24
            case "2A": // Int24
                $dataSize = 3;
                break;
            case "23": // Uint32
            case "2B": // Int32
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
                $dataSize = 8;
                break;
            case "41": // String discrete: octstr
            case "42": // String discrete: string
                $dataSize = hexdec(substr($hexString, 0, 2));
                $hexString = substr($hexString, 2);
                break;
            default:
                parserLog('debug', "  decodeDataType() ERROR: Unsupported type ".$dataType);
                return false;
            }

            // Checking size
            $l = strlen($hexString);
            if ($l < (2 * $dataSize)) {
                parserLog('debug', "  decodeDataType() ERROR: Data too short (malformed packet ?)");
                return false;
            }

            // Reordering raw bytes
            if ($reorder) {
                // 'hs' is now reduced to proper size
                if ($dataSize == 1)
                    $hs = substr($hexString, 0, 2);
                else {
                    $hs = '';
                    for ($i = 0; $i < ($dataSize * 2); $i += 4) {
                        $hs .= substr($hexString, $i + 2, 2).substr($hexString, $i, 2);
                    }
                }
            } else
                $hs = substr($hexString, 0, $dataSize * 2);
// parserLog('debug', "  decodeDataType(): size=".$dataSize.", hexString=".$hexString." => hs=".$hs);
            $dataRaw = $hs;

            // Computing value
            switch ($dataType) {
            case "10": // Boolean
            case "18": // 8-bit bitmap
            case "20": // Uint8
            case "21": // Uint16
            case "22": // Uint24
            case "23": // Uint32
            case "24": // Uint40
            case "25": // Uint48
            case "26": // Uint56
            case "27": // Uint64
            case "E0": // Time analog: ToD
            case "E1": // Time analog: Date
            case "E2": // Time analog: UTC
                $value = hexdec($hs);
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
            case "41": // String discrete: octstr
            case "42": // String discrete: string
                $value = $hs;
                break;
            default:
                parserLog('debug', "  decodeDataType() ERROR: Unsupported type ".$dataType);
                return false;
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

        /* Decode FF01 attribut payload */
        function decodeFF01($data) {
            $fields = array();
            $dataLen = strlen($data);
            while ($dataLen != 0) {
                if ($dataLen < 4) {
                    parserLog('debug', 'decodeFF01(): Longueur incorrecte ('.$dataLen.'). Analyse FF01 interrompue.');
                    break;
                }

                $id = $data[0].$data[1];
                $type = $data[2].$data[3];
                $dt_arr = $this->getZbDataType($type);
                $len = $dt_arr[1] * 2;
                if ($len == 0) {
                    /* If length is unknown we can't continue since we don't known where is next good position */
                    parserLog('warning', 'decodeFF01(): Type de données '.$type.' non supporté. Analyse FF01 interrompue.');
                    break;
                }
                $value = substr($data, 4, $len );
                $fct = 'conv'.$dt_arr[0].'ToString';
                if (method_exists($this, $fct)) {
                    $valueConverted = $this->$fct($value);
                    // parserLog('debug', 'decodeFF01(): Conversion du type '.$type.' (val='.$value.')');
                } else {
                    parserLog('debug', 'decodeFF01(): Conversion du type '.$type.' non supporté');
                    $valueConverted = "";
                }

                $fields[$this->getFF01IdName($id)] = array(
                    'id' => $id,
                    'type' => $type,
                    'typename' => $dt_arr[0],
                    'value' => $value,
                    'valueConverted' => $valueConverted,
                );
                $data = substr($data, 4 + $len);
                $dataLen = strlen($data);
            }
            return $fields;
        }

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
               'AbeilleIEEE_Ok' is set to 0 on daemon start when interrogation is not done yet.
               Should be updated by 8009 or 8024 responses */
            $commandAcceptedUntilZigateIdentified = array("0208", "0300", "8000", "8009", "8024");
            $confIeeeOk = str_replace('Abeille', 'AbeilleIEEE_Ok', $dest); // AbeilleX => AbeilleIEEE_OkX
            $confIeeeOkVal = config::byKey($confIeeeOk, 'Abeille', '0');
            if ($confIeeeOkVal == -1) {
                parserLog('debug', $dest.', AbeilleIEEE_Ok=='.$confIeeeOkVal.' => msg '.$type." ignored. Port switch ??");
                return 0;
            } else if ($confIeeeOkVal == 0) {
                $zgId = substr($dest, 7); // AbeilleX => X
                $this->msgToCmd("CmdAbeille".$zgId."/0000/getNetworkStatus", "getNetworkStatus");

                if (!in_array($type, $commandAcceptedUntilZigateIdentified)) {
                    parserLog('debug', $dest.', AbeilleIEEE_Ok=='.$confIeeeOkVal.' => msg '.$type." ignored. Waiting 8009 or 8024.");
                    return 0;
                }
            }

            $fct = "decode".$type;
            if (method_exists($this, $fct)) {
                $this->$fct($dest, $payload, $lqi);
            } else {
                parserLog('debug', $dest.', Type='.$type.'/'.zgGetMsgByType($type).', ignored (unsupported).');
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
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return Nothing
         */
        function decode004d($dest, $payload, $lqi)
        {
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

            $Addr       = substr($payload, 0, 4);
            $IEEE       = substr($payload, 4, 16);
            $MACCapa    = substr($payload, 20, 2);
            if (strlen($payload) > 22)
                $Rejoin = substr($payload, 22, 2);
            else
                $Rejoin = "";

            $msgDecoded = '004d/Device announce'.', Addr='.$Addr.', ExtAddr='.$IEEE.', MACCapa='.$MACCapa;
            if ($Rejoin != "") $msgDecoded .= ', Rejoin='.$Rejoin;
            parserLog('debug', $dest.', Type='.$msgDecoded);

            /* Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddrExt"]) && !strcasecmp($GLOBALS["dbgMonitorAddrExt"], $IEEE)) {
                monMsgFromZigate($msgDecoded); // Send message to monitor
                monAddrHasChanged($Addr, $IEEE); // Short address has changed
                $GLOBALS["dbgMonitorAddr"] = $Addr;
            }

            $this->whoTalked[] = $dest.'/'.$Addr;

            /* Send to client if required (EQ page opened) */
            $toCli = array(
                'src' => 'parser',
                'type' => 'deviceAnnounce',
                'net' => $dest,
                'addr' => $Addr,
                'ieee' => $IEEE
            );
            $this->msgToClient($toCli);

            $zgId = substr($dest, 7);
            if (isset($GLOBALS['zigate'.$zgId]['permitJoin']) && ($GLOBALS['zigate'.$zgId]['permitJoin'] == "01")) {
                $this->deviceAnnounce($dest, $Addr, $IEEE, $MACCapa, $Rejoin);
            } else {
                // if (config::byKey('blocageTraitementAnnonce', 'Abeille', 'Oui', 1) == "Oui") {
                //     parserLog('debug', '  Not in inclusion mode => device announce ignored');
                //     return;
                // }
                if (!isset($GLOBALS['eqList'][$dest]) || !isset($GLOBALS['eqList'][$dest][$Addr]))
                    parserLog('debug', '  Not in inclusion mode but trying to identify unknown device anyway.');
                else
                    parserLog('debug', '  Not in inclusion mode and got a device announce for already known device.');
                $this->deviceAnnounce($dest, $Addr, $IEEE, $MACCapa, $Rejoin);
            }

            // $this->msgToAbeilleFct($dest."/".$Addr, "enable", $IEEE);

            // Envoie de la IEEE a Jeedom qui le processera dans la cmd de l objet si celui ci existe deja dans Abeille, sinon sera drop
            // $this->msgToAbeille($dest."/".$Addr, "IEEE", "Addr", $IEEE);

            // Rafraichi le champ Ruche, JoinLeave (on garde un historique)
            // $this->msgToAbeille($dest."/0000", "joinLeave", "IEEE", "Annonce->".$IEEE);

            // $this->msgToAbeille($dest."/"."$Addr", "MACCapa", "MACCapa", $MACCapa);

            // Si 02 = Rejoin alors on doit le connaitre on ne va pas faire de recherche
            // if ($Rejoin == "02") return;

            // Tcharp38: Purpose of the following was to try to identify device even if not in include mode.
            //           Note that this currently may lead to cmd bottleneck with bad 8000 status.

            // if (config::byKey('blocageTraitementAnnonce', 'Abeille', 'Non', 1) == "Oui")
            //     return;

            // if ( Abeille::checkInclusionStatus( $dest ) != "01" ) return;

            // // If this IEEE is already in Abeille we stop the process of creation in Abeille, but we send IEEE and Addr to update Addr if needed.
            // if (Abeille::getEqFromIEEE($IEEE)) {
            //     $this->actionQueue[] = array( 'when'=>time()+5, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$IEEE );
            //     return;
            // }

            // $agressif = config::byKey( 'agressifTraitementAnnonce', 'Abeille', '4', 1 );
            // for ($i = 0; $i < $agressif; $i++) {
            //     $this->msgToCmd("TempoCmd".$dest."/0000/ActiveEndPoint&time=".(time()+($i*2)), "address=".$Addr );
            //     $this->actionQueue[] = array( 'when'=>time()+($i*2)+5, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$IEEE );
            //     $this->actionQueue[] = array( 'when'=>time()+($i*2)+5, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"MACCapa", 'parm2'=>"MACCapa", 'parm3'=>$MACCapa );
            // }
        }

        /* Fonction specifique pour le retour d'etat de l interrupteur Livolo. */
        function decode0100($dest, $payload, $lqi)
        {
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

            /* Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            $clustId  = "0006-".$EPS;
            $attrId = "0000";
            $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, $data);
        }

        // PDM Management

        function decode0302($dest, $payload, $lqi)
        {
            // E_SL_MSG_PDM_LOADED = 0x0302
            // https://zigate.fr/documentation/deplacer-le-pdm-de-la-zigate/
            parserLog('debug', $dest.', Type=0302/E_SL_MSG_PDM_LOADED');
        }

        function decode0300($dest, $payload, $lqi)
        {
            // "0300 0001DCDE"
            // E_SL_MSG_PDM_HOST_AVAILABLE = 0x0300
            parserLog('debug', $dest.', Type=0300/E_SL_MSG_PDM_HOST_AVAILABLE : PDM Host Available ?');

            $this->msgToCmd("Cmd".$dest."/0000/PDM", "req=E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE");
        }

        function decode0208($dest, $payload, $lqi)
        {
            // "0208 0003 19001000"
            // E_SL_MSG_PDM_EXISTENCE_REQUEST = 0x0208

            $id = substr( $payload, 0  , 4);

            parserLog('debug', $dest.', Type=0208/E_SL_MSG_PDM_EXISTENCE_REQUEST : PDM Exist for id : '.$id.' ?');

            $this->msgToCmd("Cmd".$dest."/0000/PDM", "req=E_SL_MSG_PDM_EXISTENCE_RESPONSE&recordId=".$id);
        }

        // Zigate Status
        function decode8000($dest, $payload, $lqi)
        {
            $status     = substr($payload, 0, 2);
            $SQN        = substr($payload, 2, 2);
            $PacketType = substr($payload, 4, 4);

            $msgDecoded = '8000/Status'
                .', Status='.$status.'/'.zgGet8000Status($status)
                .', SQN='.$SQN
                .', PacketType='.$PacketType;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8000");

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            // $srcAddr    = "Ruche";
            // $clustId  = "Zigate";
            // $attrId = "8000";
            // $data       = $this->displayStatus($status);
            // $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, $data);

            $msgAbeille = array ('dest'         => $dest,
                                 'type'         => "8000",
                                 'status'       => $status,
                                 'SQN'          => $SQN,
                                 'PacketType'   => $PacketType , // The value of the initiating command request.
                                 );

            // Envoie du message 8000 (Status) pour AbeilleMQTTCmd pour la gestion du flux de commandes vers la zigate
            if (msg_send( $this->queueKeyParserToCmdSemaphore, 1, $msgAbeille, true, false)) {
                // parserLog("debug","(fct msgToAbeille) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille), "8000");
            } else {
                parserLog("debug", "  Could not add message to 'queueKeyParserToCmd' queue: ".json_encode($msgAbeille), "8000");
            }

            if ($PacketType == "0002") {
                if ( $status == "00" ) {
                    parserLog("debug","  Le mode de fonctionnement de la zigate a bien été modifié.");
                    // message::add("Abeille", "Le mode de fonctionnement de la zigate a bien été modifié.","" );
                } else {
                    parserLog("debug", "  Durant la demande de modification du mode de fonctionnement de la zigate, une erreur a été détectée.");
                    message::add("Abeille", "Durant la demande de modification du mode de fonctionnement de la zigate, une erreur a été détectée.","" );
                }
            }
        }

        /* Called from decode8002() to decode a "Read Attribute Status Record"
           Returns: false if error */
        function decode8002_ReadAttrStatusRecord($hexString, &$size) {
            /* Attributes status record format:
                Attr id = 2B
                Status = 1B
                Attr data type = 1B (if status == 0)
                Attr = <according to data type> (if status == 0) */
            $l = strlen($hexString);
            if ($l < 6) {
                parserLog('debug', "  decode8002_ReadAttrStatusRecord() ERROR: Unexpected record size ".$l);
                return false;
            }
            $attr = array(
                'id' => substr($hexString, 2, 2).substr($hexString, 0, 2),
                'status' => substr($hexString, 4, 2),
                'dataType' => '',
                'valueHex' => '', // Extracted hex value
                'value' => null // Real value
            );
            $size = 6;
            if ($attr['status'] != '00')
                return $attr;
            $attr['dataType'] = substr($hexString, 6, 2);
            $hexString = substr($hexString, 8);
            $attr['value'] = $this->decodeDataType($hexString, $attr['dataType'], true, $dataSize, $valueHex);
            if ($attr['value'] === false)
                return false;
            $attr['valueHex'] = $valueHex;
            $size = 6 + 2 + (2 * $dataSize);
            return $attr;
        }

        /* Called from decode8002() to decode a "Report attribute"
           Returns: false if error */
        function decode8002_ReportAttribute($hexString, &$size) {
            /* Attributes status record format:
                Attr id = 2B
                Attr data type = 1B
                Attr = <according to data type> */
            $l = strlen($hexString);
            if ($l < 8) { // 4 + 2 + 2 at least
                parserLog('debug', "  decode8002_ReportAttribute() ERROR: Unexpected record size ".$l);
                return false;
            }
            $attr = array(
                'id' => substr($hexString, 2, 2).substr($hexString, 0, 2),
                'dataType' => substr($hexString, 4, 2),
                'value' => null
            );
            $hexString = substr($hexString, 6);
            $attr['value'] = $this->decodeDataType($hexString, $attr['dataType'], true, $dataSize, $valueHex);
            if ($attr['value'] === false)
                return false;
            $attr['valueHex'] = $valueHex;
            $size = 6 + (2 * $dataSize);
            return $attr;
        }

        /**
         * Data indication
         *
         * This method process a Zigbeee message coming from a device which is unknown from zigate, so Abeille as to deal with it.
         *  Will first decode it.
         *  Take action base on message contain
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8002($dest, $payload, $lqi) {

            // Decode first based on https://zigate.fr/documentation/commandes-zigate/
            $status         = substr($payload, 0, 2);
            $profId         = substr($payload, 2, 4);
            $cluster        = substr($payload, 6, 4);
            $srcEp          = substr($payload,10, 2);
            $destEp         = substr($payload,12, 2);
            $srcAddrMode    = substr($payload,14, 2);
            $srcAddr        = substr($payload,16, 4);
            $destAddrMode   = substr($payload,20, 2);
            $dstAddress     = substr($payload,22, 4);
            $pl = substr($payload, 26); // Keeping remaining payload

            /* Log */
            $msgDecoded = '8002/Data indication'
                            .', Status='.$status
                            .', ProfId='.$profId
                            .', ClustId='.$cluster
                            .", SrcEP=".$srcEp
                            .", DestEP=".$destEp
                            .", SrcAddrMode=".$srcAddrMode
                            .", SrcAddr=".$srcAddr
                            .", DestAddrMode=".$destAddrMode
                            .", DestAddr=".$dstAddress;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8002");

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            /* Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            /* Profile 0000 */
            if ($profId == "0000") {
                // Routing Table Response (Mgmt_Rtg_rsp)
                if ($cluster == "8032") {

                    // ZigBee Specification: 2.4.4.3.3   Mgmt_Rtg_rsp
                    // 3 bits (status) + 1 bit memory constrained concentrator + 1 bit many-to-one + 1 bit Route Record required + 2 bit reserved
                    // Il faudrait faire un decodage bit a bit mais pour l instant je prends les plus courant et on verra si besoin.
                    $statusDecode = array(
                        0x00 => "Active",
                        0x01 => "Dicovery_Underway",
                        0x02 => "Discovery_Failed",
                        0x03 => "Inactive",
                        0x04 => "Validation_Underway", // Got that if interrogate the Zigate
                        0x05 => "Reserved",
                        0x06 => "Reserved",
                        0x07 => "Reserved",
                        0x10 => " + Many To One", // 0x10 -> 1 0 000 bin -> Active + no constrain + Many To One + no route required
                        );

                    $SQN                    = substr($payload,26, 2);
                    $status                 = substr($payload,28, 2);
                    $tableSize              = hexdec(substr($payload,30, 2));
                    $index                  = hexdec(substr($payload,32, 2));
                    $tableCount             = hexdec(substr($payload,34, 2));

                    parserLog('debug', '  Routing table response'
                            .', SQN='.$SQN
                            .', Status='.$status
                            .', tableSize='.$tableSize
                            .', index='.$index
                            .', tableCount='.$tableCount,
                                "8002"
                                );

                    $routingTable = array();

                    for ($i = $index; $i < $index+$tableSize; $i++) {

                        $addressDest=substr($payload,36+($i*10), 4);

                        $statusRouting = substr($payload,36+($i*10)+4,2);
                        $statusDecoded = $statusDecode[ base_convert( $statusRouting, 16, 2) &  7 ];
                        if (base_convert($statusRouting, 16, 10)>=0x10) $statusDecoded .= $statusDecode[ base_convert($statusRouting, 16, 2) & 0x10 ];

                        $nextHop=substr($payload,36+($i*10)+4+2,4);

                        parserLog('debug', '  Addr='.$addressDest.', Status='.$statusRouting.'/'.$statusDecoded.', Next Hop='.$nextHop, "8002");

                        if ((base_convert( $statusRouting, 16, 2) &  7) == "00" ) {
                            $routingTable[] = array( $addressDest => $nextHop );
                        }
                    }

                    // if ( $srcAddr == "Ruche" ) return; // Verrue car si j interroge l alarme Heiman, je ne vois pas a tous les coups la reponse sur la radio et le message recu par Abeille vient d'abeille !!!

                    $abeille = Abeille::byLogicalId($dest.'/'.$srcAddr, 'Abeille');
                    if ( $abeille ) {
                        $abeille->setConfiguration('routingTable', json_encode($routingTable) );
                        $abeille->save();
                    }
                    else {
                        parserLog('debug', '  abeille not found !!!', "8002");
                    }

                    return;
                }

                // Binding Table Response (Mgmt_Bind_rsp)
                if ($cluster == "8033") {

                    /* Parser exemple
                    Abeille1, Type=8002/Data indication, Status=00, ProfId=0000, ClustId=8033, SrcEP=00, DestEP=00, SrcAddrMode=02, SrcAddr=9007, DestAddrMode=02, DestAddr=0000
                        Binding table response, SQN=12, Status=00, tableSize=2, index=0, tableCount=2
                        04CF8CDF3C77164B, 01, 0004 => EP01 @00158D0001ED3365
                        04CF8CDF3C77164B, 01, 0100 => EP01 @00158D0001ED3365 */

                    $sqn        = substr($pl, 0, 2);
                    $status     = substr($pl, 2, 2);
                    $tableSize  = hexdec(substr($pl, 4, 2));
                    $index      = hexdec(substr($pl, 6, 2));
                    $tableCount = hexdec(substr($pl, 8, 2));

                    parserLog('debug', '  Binding table response'
                            .', SQN='.$sqn
                            .', Status='.$status
                            .', tableSize='.$tableSize
                            .', index='.$index
                            .', tableCount='.$tableCount, "8002");

                    $pl = substr($pl, 10);
                    for ($i = 0; $i < $tableCount; $i++) {
                        $srcIeee = AbeilleTools::reverseHex(substr($pl, 0, 16));
                        $srcEp  = substr($pl, 16, 2);
                        $clustId = AbeilleTools::reverseHex(substr($pl, 18, 4));
                        $destAddrMode = substr($pl, 22, 2);
                        if ($destAddrMode == "01") {
                            // 16-bit group address for DstAddr and DstEndpoint not present
                            $destAddr = AbeilleTools::reverseHex(substr($pl, 24, 4));
                            parserLog('debug', '  '.$srcIeee.', EP'.$srcEp.', Clust '.$clustId.' => group '.$destAddr);
                            $pl = substr($pl, 28);
                        } else if ($destAddrMode == "03") {
                            // 64-bit extended address for DstAddr and DstEndp present
                            $destIeee = AbeilleTools::reverseHex(substr($pl, 24, 16));
                            $destEP  = substr($pl, 40, 2);
                            parserLog('debug', '  '.$srcIeee.', EP'.$srcEp.', Clust '.$clustId.' => '.$destIeee.', EP'.$destEP);
                            $pl = substr($pl, 42);
                        } else {
                            parserLog('debug', '  ERROR: Unexpected destAddrMode '.$destAddrMode);
                            return;
                        }
                    }
                    return;
                }

                switch ($cluster) {
                case "8031":
                    parserLog('debug', '  Handled by decode804E');
                    break;
                default:
                    parserLog('debug', '  Unsupported/ignored profile 0000 message');
                }
                return;
            }

            /*
             * Code hereafter is covering ZCL compliant messages.
             * Profiles: 0104/ZHA
             */

            if ($profId !== "0104") {
                parserLog('debug', '  Unsupported/ignored profile '.$profId.' message');
                return;
            }

            //  Cluster 0005 Scene (exemple: Boutons lateraux de la telecommande -)
            if ($cluster == "0005") {
                $frameCtrlField = substr($payload, 26, 2);
                parserLog("debug", '  Cluster 0005: FCF='.$frameCtrlField);

                // Tcharp38: WARNING: There is probably something wrong there.
                // There are cases where 0005 message is neither supported by this part nor by 8100_8102 decode.
                // Example:
                // [2021-08-30 16:44:31] Abeille1, Type=8002/Data indication, Status=00, ProfId=0104, ClustId=0005, SrcEP=01, DestEP=01, SrcAddrMode=02, SrcAddr=7C4F, DestAddrMode=02, DestAddr=0000
                // [2021-08-30 16:44:31]   FCF=08, SQN=7C, cmd=01/Read Attributes Response
                // [2021-08-30 16:44:31]   Handled by decode8100_8102
                // [2021-08-30 16:44:31] Abeille1, Type=8100/Read individual attribute response, SQN=7C, Addr=7C4F, EP=01, ClustId=0005, AttrId=0001, AttrStatus=00, AttrDataType=20, AttrSize=0001
                // [2021-08-30 16:44:31]   Processed in 8002 => dropped

                $abeille = Abeille::byLogicalId($dest."/".$srcAddr,'Abeille');
                $sceneStored = json_decode( $abeille->getConfiguration('sceneJson','{}') , True );

                if ( $frameCtrlField=='05' ) {
                    $Manufacturer   = substr($payload,30, 2).substr($payload,28, 2);
                    if ( $Manufacturer=='117C' ) {

                        $SQN                    = substr($payload,32, 2);
                        $cmd                    = substr($payload,34, 2);
                        if ( $cmd != "07" ) {
                            parserLog("debug", '  Message can t be decoded. Looks like Telecommande Ikea Ronde but not completely.');
                            return;
                        }
                        $remainingData          = substr($payload,36, 8);
                        $value                  = substr($payload,36, 2);

                        parserLog("debug", '  Telecommande Ikea Ronde'
                                       .', frameCtrlField='.$frameCtrlField
                                       .', Manufacturer='.$Manufacturer
                                       .', SQN='.$SQN
                                       .', cmd='.$cmd
                                       .', value='.$value
                                        );

                        $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$srcEp, '0000', $value);
                        return;
                    }
                }

                // Tcharp38: Moved this part in ZCL global commands decode with questions.
                // if ( $frameCtrlField=='18' ) { // Default Resp: 1 / Direction: 1 / Manu Specific: 0 / Cluster Specific: 00
                //     $SQN                    = substr($payload,28, 2);
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

                if ( $frameCtrlField=='19' ) { // Default Resp: 1 / Direction: 1 / Manu Specific: 0 / Cluster Specific: 01
                    $SQN                    = substr($payload,28, 2);
                    $cmd                    = substr($payload,30, 2);

                    // Add Scene Response
                    if ( $cmd == "00" ) {
                        $sceneStatus            = substr($payload,32, 2);

                        if ( $sceneStatus != "00" ) {
                            parserLog("debug", '  Status error dd Scene Response.');
                            return;
                        }

                        $groupID          = substr($payload,34, 4);
                        $sceneId          = substr($payload,36, 2);

                        parserLog("debug", '  Add Scene Response confirmation (decoded but not processed) Please refresh with a -Get Scene Membership- : group: '.$groupID.' - scene id:'.$sceneId );
                        return;
                    }

                    // View Scene Response
                    elseif ( $cmd == "01" ) {
                        $sceneStatus            = substr($payload,32, 2);
                        if ( $sceneStatus != "00" ) {
                            parserLog("debug", '  Status error on scene info.');
                            return;
                        }
                        $groupID            = substr($payload,34, 4);
                        $sceneId            = substr($payload,38, 2);
                        $transitionTime     = substr($payload,40, 4);
                        $Length             = substr($payload,44, 2);
                        $statusRouting      = "";
                        $extensionSet       = ""; // 06:00:01:01:08:00:01:fe:00:03:04:13:ae:eb:51 <- to be investigated

                        parserLog("debug", '  View Scene Response: '.$groupID.' - '.$sceneId.' ...');
                        return;
                    }

                    // Remove scene response
                    elseif ( $cmd == "02" ) {
                        $sceneStatus            = substr($payload,32, 2);
                        if ( $sceneStatus != "00" ) {
                            parserLog("debug", '  Status error on scene info.');
                            return;
                        }
                        $groupID            = substr($payload,34, 4);
                        $sceneId            = substr($payload,38, 2);

                        parserLog("debug", '  Scene: '.$sceneId.' du groupe: '.$groupID.' a ete supprime.');
                        return;
                    }

                    // Remove All Scene Response
                    elseif ( $cmd == "03" ) {
                        $sceneStatus            = substr($payload,32, 2);
                        if ( $sceneStatus != "00" ) {
                            parserLog("debug", '  Status error Remove All Scene Response.');
                            return;
                        }
                        $groupID                = substr($payload,36, 2).substr($payload,34, 2);

                        unset($sceneStored["sceneRemainingCapacity"]);
                        unset($sceneStored["sceneCount"]);
                        unset($sceneStored["GroupeScene"][$groupID]);
                        $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        $abeille->save();
                    }

                    // Store Scene Response
                    elseif ( $cmd == "04" ) {
                        $sceneStatus            = substr($payload,32, 2);

                        if ( $sceneStatus != "00" ) {
                            parserLog("debug", '  Status error Store Scene Response.');
                            return;
                        }

                        $groupID          = substr($payload,34, 4);
                        $sceneId          = substr($payload,36, 2);

                        parserLog("debug", '  store scene response confirmation (decoded but not processed) Please refresh with a -Get Scene Membership- : group: '.$groupID.' - scene id:'.$sceneId );
                        return;
                    }

                    // Get Scene Membership Response
                    elseif ( $cmd == "06" ) {
                        $sceneStatus            = substr($payload,32, 2);
                        if ( $sceneStatus == "85" ) {  // Invalid Field
                            $sceneRemainingCapacity = substr($payload,34, 2);
                            $groupID                = substr($payload,36, 4);

                            parserLog("debug", "  scene: scene capa:".$sceneRemainingCapacity.' - group: '.$groupID );

                            $sceneStored["sceneRemainingCapacity"]        = $sceneRemainingCapacity;
                            unset( $sceneStored["GroupeScene"][$groupID] );

                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();

                            parserLog("debug", '  '.json_encode($sceneStored) );

                            // $sceneStored = json_decode( Abeille::byLogicalId($dest."/".$srcAddr,'Abeille')->getConfiguration('sceneJson','{}') , True );
                            // parserLog("debug", $dest.', Type=8002/Data indication ---------------------> '.json_encode($sceneStored) );
                            return;
                        }
                        if ( $sceneStatus != "00" ) {
                            parserLog("debug", '  Status error on scene info.');
                            return;
                        }

                        $sceneRemainingCapacity = substr($payload,34, 2);
                        $groupID                = substr($payload,38, 2).substr($payload,36, 2);
                        $sceneCount             = substr($payload,40, 2);
                        $sceneId = "";
                        for ($i = 0; $i < hexdec($sceneCount); $i++) {
                            $sceneId .= '-'.substr($payload,42+$i*2, 2);
                        }

                        parserLog("debug", "  scene capa:".$sceneRemainingCapacity.' - group: '.$groupID.' - scene count:'.$sceneCount.' - scene id:'.$sceneId );

                        $sceneStored["sceneRemainingCapacity"]        = $sceneRemainingCapacity;
                        $sceneStored["sceneCount"]                    = $sceneCount;
                        $sceneStored["GroupeScene"][$groupID]["sceneId"]             = $sceneId;
                        $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        $abeille->save();

                        parserLog("debug",  '  '.json_encode($sceneStored) );

                        return;
                    }

                    else {
                        parserLog("debug",  '  Message can t be decoded. Cmd unknown.');
                        return;
                    }
                }
            }

            $cmd = substr($payload, 30, 2);

            // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
            // if (($cluster == "0006") && ($cmd == "FD")) {

            //     $frameCtrlField         = substr($payload,26, 2);
            //     $SQN                    = substr($payload,28, 2);
            //     $cmd                    = substr($payload,30, 2); if ( $cmd != "FD" ) return;
            //     $value                  = substr($payload,32, 2);

            //     parserLog('debug',  '  Interrupteur sur pile TS0043 bouton'
            //                     .', frameCtrlField='.$frameCtrlField
            //                     .', SQN='.$SQN
            //                     .', cmd='.$cmd
            //                     .', value='.$value,
            //                      "8002"
            //                      );

            //     $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$srcEp, '0000', $value);
            //     return;
            // }

            // Tcharp38: What is cmd 'FD' ??
            if (($cluster == "0008") && ($cmd == "FD")) {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd != "FD" ) return;
                $value                  = substr($payload,32, 2);

                parserLog('debug', '  '
                               .', frameCtrlField='.$frameCtrlField
                                .', SQN='.$SQN
                                .', cmd='.$cmd
                                .', value='.$value,
                                 "8002"
                                );

                $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$srcEp, '0000', $value);
                return;
            }

            // Tcharp38: Do nothing right now. To be added in ZCL standard decode
            // if ($cluster == "000A") {
            //     $frameCtrlField         = substr($payload,26, 2);
            //     $SQN                    = substr($payload,28, 2);
            //     $cmd                    = substr($payload,30, 2);

            //     if ($cmd == '00') {
            //         $attributTime                  = substr($payload,34, 2).substr($payload,32, 2);
            //         $attributTimeZone              = substr($payload,38, 2).substr($payload,36, 2);
            //         if ( isset($this->debug["8002"])) {
            //             parserLog('debug', '  Time Request - (decoded but not processed) '
            //                            .', frameCtrlField='.$frameCtrlField
            //                            .', SQN='.$SQN
            //                            .', cmd='.$cmd
            //                            .', attributTime='.$attributTime
            //                            .', attributTimeZone='.$attributTimeZone
            //                             );
            //                         }

            //         // Here we should reply to the device with the time. I though this Time Cluster was implemented in the zigate....
            //         return;
            //     }
            // }

            if ($cluster == "0204") {
                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2);

                if ($cmd == '01') { // Read Response
                    $attribute                 = substr($payload,34, 2).substr($payload,32, 2);
                    $status                    = substr($payload,36, 2);
                    $attributeType              = substr($payload,38, 2);
                    $value                     = substr($payload,40, 2);

                    if ( !$status ) {
                        parserLog('debug', '  Status not null - not processing. ');
                        return;
                    }

                    parserLog('debug', '  Time Request - (decoded but not processed) '
                                   .', frameCtrlField='.$frameCtrlField
                                   .', SQN='.$SQN
                                   .', cmd='.$cmd
                                   .', attribute='.$attribute
                                   .', status='.$status
                                   .', attributeType='.$attributeType
                                   .', value='.$value
                                    );

                    $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$destEp, $attribute, $value);
                    return;
                }
            }

            // Tcharp38: No longer required. This case is handled by decode8102
            // [2021-09-13 11:42:10] Abeille1, Type=8002/Data indication, Status=00, ProfId=0104, ClustId=0702, SrcEP=01, DestEP=01, SrcAddrMode=02, SrcAddr=4E85, DestAddrMode=02, DestAddr=0000
            // [2021-09-13 11:42:10]   Remontée puissance prise TS0121 , frameCtrlField=08, SQN=1E, cmd=0A - report attribut, attribute=0000, dataType=25, value=00000000007D - 125
            // [2021-09-13 11:42:10] Abeille1, Type=8102/Attribute report, SQN=1E, Addr=4E85, EP=01, ClustId=0702, AttrId=0000, AttrStatus=00, AttrDataType=25, AttrSize=0006
            // // Remontée puissance prise TS0121 Issue: #1288
            // if ($cluster == "0702") {
            //     $frameCtrlField         = substr($payload,26, 2);
            //     $SQN                    = substr($payload,28, 2);
            //     $cmd                    = substr($payload,30, 2); if ( $cmd == "0A" ) $cmd = "0A - report attribut";
            //     $attribute              = substr($payload,34, 2).substr($payload,32, 2);
            //     $dataType               = substr($payload,36, 2);

            //     // Remontée puissance prise TS0121 Issue: #1288
            //     if (($attribute == '0000') && ($dataType==25)) {
            //         // '25' => array( 'Uint48', 6 ), // Unsigned 48-bit int
            //         $value = substr($payload,48, 2).substr($payload,46, 2).substr($payload,44, 2).substr($payload,42, 2).substr($payload,40, 2).substr($payload,38, 2);
            //         parserLog('debug', '  Remontée puissance prise TS0121 '
            //                        .', frameCtrlField='.$frameCtrlField
            //                        .', SQN='.$SQN
            //                        .', cmd='.$cmd
            //                        .', attribute='.$attribute
            //                        .', dataType='.$dataType
            //                        .', value='.$value.' - '.hexdec($value),
            //                         "8002"
            //                         );

            //         $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$destEp, $attribute, hexdec($value));
            //         return;
            //     }
            // }

            // Electrical measurement cluster
            // Info: Used for power reporting on Legrand 20AX / prise Blitzwolf BW-SHP13 #1231
            // if ($cluster == "0B04") {

            //     $frameCtrlField = substr($payload, 26, 2);
            //     $SQN = substr($payload, 28, 2);
            //     $cmd = substr($payload, 30, 2);
            //     parserLog('debug', '  FCF='.$frameCtrlField
            //        .', SQN='.$SQN
            //        .', cmd='.$cmd.'/'.zbGetZCLGlobalCmdName($cmd));

                // if ($cmd == '01') {
                //     $attributs = substr($payload, 32);
                //     // parserLog('debug', '  Attributs received: '.$attributs, "8002");

                //     while (strlen($attributs) > 0) {

                //         $attribute  = substr($attributs, 2, 2).substr($attributs, 0, 2);
                //         $status     = substr($attributs, 4, 2);
                //         if ($status != '00') {
                //             parserLog('debug', '  Attribut analysis: '.$attribute.'-'.$status." => Ignored (status != 0)", "8002");
                //             $attributs = substr($attributs, 6);
                //             continue;
                //         }

                //         $dataType = substr($attributs, 6, 2);
                //         $hexValue = '';
                //         $dataSize = 0;
                //         $realValue = $this->decodeDataType(substr($attributs, 8), $dataType, true, $dataSize, $hexValue);
                //         $attributs = substr($attributs, 8 + ($dataSize * 2));

                //         $attrName = "?";
                //         $msg = array(
                //             'src' => 'parser',
                //             'type' => 'attributeReport',
                //             'net' => $dest,
                //             'addr' => $srcAddr,
                //             'ep' => $srcEp,
                //             'name' => $cluster.'-'.$srcEp.'-'.$attribute,
                //             'value' => $realValue, // False = unsupported
                //             'time' => time(),
                //             'lqi' => $lqi
                //         );

                //         if ($attribute == '0505') {
                //             $attrName = "RMS Voltage";
                //         } else if ($attribute == '0508') {
                //             $attrName = "RMS Current";
                //         } else if ($attribute == '050B') {
                //             $attrName = "Active Power";
                //         }

                //         parserLog('debug', '  '.$attrName
                //             .', attrId='.$attribute
                //             .', attrType='.$dataType
                //             .', value='.$hexValue.' => '.$realValue,
                //             "8002"
                //         );

                //         $this->msgToAbeille2($msg);

                //         /* Send to client if required (ex: EQ page opened) */
                //         $toCli = array(
                //             'src' => 'parser',
                //             'type' => 'attributeReport',
                //             'net' => $dest,
                //             'addr' => $srcAddr,
                //             'ep' => $srcEp,
                //             'clustId' => $cluster,
                //             'attrId' => $attribute,
                //             'status' => $status,
                //             'value' => $realValue
                //         );
                //         $this->msgToClient($toCli);
                //     }
                //     return;
                // } // End cmd==01

                // // exemple: emontée puissance module Legrand 20AX
                // if ($cmd == '0A') {
                //     $attribute = substr($payload,34, 2).substr($payload,32, 2);
                //     $dataType  = substr($payload,36, 2);
                //     if (($attribute == '050B') && ($dataType == '29')) {
                //         // '29' => array( 'Int16', 2 ), // Signed 16-bit int
                //         $value = substr($payload,40, 2).substr($payload,38, 2);

                //         parserLog('debug', '  ActivePower'
                //            .', attrib='.$attribute
                //            .', dataType='.$dataType
                //            .', value='.$value.' - '.hexdec($value),
                //             "8002");

                //         $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$destEp, $attribute, hexdec($value));
                //         return;
                //     }
                // }
            // }

            // Remontée etat relai module Legrand 20AX
            // 80020019F4000104 FC41 010102D2B9020000180B0A000030000100100084
            if ($cluster == "FC41") {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd == "0a" ) $cmd = "0a - report attribut";

                $attribute              = substr($payload,34, 2).substr($payload,32, 2);
                $dataType               = substr($payload,36, 2);
                $value                  = substr($payload,38, 2);

                parserLog('debug', '  Remontée etat relai module Legrand 20AX '
                                   .', frameCtrlField='.$frameCtrlField
                                   .', SQN='.$SQN
                                   .', cmd='.$cmd
                                   .', attribute='.$attribute
                                   .', dataType='.$dataType
                                   .', value='.$value.' - '.hexdec($value),
                                    "8002"
                                    );

                $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$destEp, $attribute, hexdec($value));

                // if ($this->debug["8002"]) $this->deamonlog('debug', 'lenght: '.strlen($payload) );
                if ( strlen($payload)>42 ) {
                    $attribute              = substr($payload,42, 2).substr($payload,40, 2);
                    $dataType               = substr($payload,44, 2);
                    $value                  = substr($payload,46, 2);

                    parserLog('debug', '  Remontée etat relai module Legrand 20AX '
                                   .', frameCtrlField='.$frameCtrlField
                                   .', SQN='.$SQN
                                   .', cmd='.$cmd
                                   .', attribute='.$attribute
                                   .', dataType='.$dataType
                                   .', value='.$value.' - '.hexdec($value),
                                    "8002"
                                    );

                    $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$destEp, $attribute, hexdec($value));
                }

                return;
            }

            // Prise Xiaomi
            if ($cluster == "FCC0") {
                $FCF = substr($payload,26, 2);
                if ( $FCF=='1C' ) {
                    $Manufacturer   = substr($payload,30, 2).substr($payload,28, 2);
                    if ( $Manufacturer=='115F' ) {
                        $SQN = substr($payload,32, 2);
                        $Cmd = substr($payload,34, 2);
                        if ( $Cmd=='0A') {
                            $Attribut   = substr($payload,38, 2).substr($payload,36, 2);
                            if ( $Attribut=='00F7' ) {
                                $dataType = substr($payload,40, 2);
                                if ( $dataType == "41" ) { // 0x41 Octet stream
                                    $dataLength = hexdec(substr($payload,42, 2));
                                    // Je suppose que je suis avec un message Xiaomi Prise que je decode comme les champs FF01
                                    // parserLog('debug', "  Champ proprietaire FCCO Xiaomi (Prise)");
                                    // parserLog('debug', "        dataLength: ".$dataLength);
                                    $FCC0 = $this->decodeFF01(substr($payload, 44, $dataLength*2));
                                    // parserLog('debug', "  ".json_encode($FCC0));

                                    $this->msgToAbeille($dest."/".$srcAddr, '0006', '01-0000',     $FCC0["Etat SW 1 Binaire"]["valueConverted"]);    // On Off Etat
                                    $this->msgToAbeille($dest."/".$srcAddr, '0402', '01-0000',     $FCC0["Device Temperature"]["valueConverted"]);    // Device Temperature
                                    $this->msgToAbeille($dest."/".$srcAddr, '000C', '15-0055',     $FCC0["Puissance"]["valueConverted"]);    // Puissance
                                    $this->msgToAbeille($dest."/".$srcAddr, 'tbd',  '--conso--',   $FCC0["Consommation"]["valueConverted"]);    // Consumption
                                    $this->msgToAbeille($dest."/".$srcAddr, 'tbd',  '--volt--',    $FCC0["Voltage"]["valueConverted"]);    // Voltage
                                    $this->msgToAbeille($dest."/".$srcAddr, 'tbd',  '--current--', $FCC0["Current"]["valueConverted"]);    // Current
                                }
                            }
                        }
                    }
                }
            }

            /* WARNING:
               If execution reached here it is assumed that message is "Zigbee cluster library" compliant
               and NOT treated above.
               Compliant profiles: 0104 (ZHA)
             */

            /* Decoding ZCL header */
            $fcf = substr($payload, 26, 2); // Frame Control Field
            $frameType = hexdec($fcf) & 3; // Bits 0 & 1: 00=global, 01=cluster specific
            $manufSpecific = (hexdec($fcf) >> 2) & 1;
            $dir = (hexdec($fcf) >> 3) & 1;
            if ($frameType == 0)
                $fcfTxt = "Global";
            else
                $fcfTxt = "Cluster-specific";
            if ($dir)
                $fcfTxt .= "/Serv->Cli";
            else
                $fcfTxt .= "/Cli->Serv";
            if ($manufSpecific) {
                /* 16bits for manuf specific code */
                $SQN = substr($payload, 32, 2); // Sequence Number
                $cmd = substr($payload, 34, 2); // Command
                $msg = substr($payload, 36);
            } else {
                $SQN = substr($payload, 28, 2); // Sequence Number
                $cmd = substr($payload, 30, 2); // Command
                $msg = substr($payload, 32);
            }

            /*
             * General ZCL command
             */

            if ($frameType == 0) { // General command
                parserLog('debug', "  FCF=".$fcf."/".$fcfTxt.", SQN=".$SQN.", cmd=".$cmd.'/'.zbGetZCLGlobalCmdName($cmd));

                /* General 'Cmd' reminder
                    0x01 Read Attributes Response
                    0x04 Write Attributes Response
                    0x05 Write Attributes No Response
                    0x07 Configure Reporting Response
                    0x09 Read Reporting Configuration Response
                    0x0a Report attributes
                    0x0b Default Response
                    0x0d Discover Attributes Response
                    0x12 Discover Commands Received Response
                    0x16 Discover Attributes Extended Response
                */
                if ($cmd == "01") { // Read Attributes Response
                    // Some clusters are directly handled by 8100/8102 decode
                    $acceptedCmd01 = ['0005', '0020', '0B04']; // Clusters handled here
                    if (!in_array($cluster, $acceptedCmd01)) {
                        parserLog('debug', "  Handled by decode8100_8102");
                        return;
                    }

                    /* Monitor if requested */
                    if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                        monMsgFromZigate("8002/Read attributes response"); // Send message to monitor

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

                    $l = strlen($msg);
                    $attributes = [];
                    for ($i = 0; $i < $l;) {
                        $size = 0;
                        $attr = $this->decode8002_ReadAttrStatusRecord(substr($msg, $i), $size);
                        if ($attr === false)
                            break; // Stop decode there

                        $attrName = zbGetZCLAttributeName($cluster, $attr['id']);
                        parserLog('debug', '  AttrId='.$attr['id'].'/'.$attrName
                            .', Status='.$attr['status']
                            .', AttrType='.$attr['dataType']
                            .', Value='.$attr['valueHex'].' => '.$attr['value'],
                            "8002"
                        );
                        $attrId = $attr['id'];
                        unset($attr['id']); // Remove 'id' from object for optimization
                        $attributes[$attrId] = $attr;
                        $i += $size;
                    }
                    if (sizeof($attributes) == 0)
                        return;

                    // Reporting grouped attributes to Abeille
                    $msgTo = array(
                        'src' => 'parser',
                        'type' => 'readAttributesResponse',
                        'net' => $dest,
                        'addr' => $srcAddr,
                        'ep' => $srcEp,
                        'clustId' => $cluster,
                        'attributes' => $attributes,
                        'time' => time(),
                        'lqi' => $lqi
                    );
                    $this->msgToAbeille2($msgTo);

                    /* Send to client page if required (ex: EQ page opened) */
                    $this->msgToClient($msgTo);

                    /* Tcharp38: Cluster 0005 specific case.
                       Why is it handled here in Parser ?? Moreover why in decode8002 since supported by decode8100 ? */
                    if ($cluster == "0005") {
                        $abeille = Abeille::byLogicalId($dest."/".$srcAddr,'Abeille');
                        $sceneStored = json_decode($abeille->getConfiguration('sceneJson', '{}'), true);
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
                        $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                        $abeille->save();
                        parserLog("debug", '  '.json_encode($sceneStored));
                        return;
                    }

                    return;
                } // End '$cmd == "01"'

                else if ($cmd == "07") { // Configure Reporting Response
                    parserLog('debug', "  Handled by decode8120");
                    return;
                }

                else if ($cmd == "09") { // Read Reporting Configuration Response
                    $status = substr($msg, 0, 2);
                    $dir = substr($msg, 2, 2);
                    $attrId = substr($msg, 6, 2).substr($msg, 4, 2);
                    if ($status == "00") {
                        $attrType = substr($msg, 8, 2);
                        $minInterval = substr($msg, 10, 4);
                        $maxInterval = substr($msg, 14, 4);
                        // Reportable change => Variable size
                        // Timeout period => 2B
                        // TO BE COMPLETED
                        parserLog('debug', '  Status='.$status.'/'.zbGetZCLStatus($status).', Dir='.$dir.', AttrId='.$attrId
                            .', AttrType='.$attrType.', minInterval='.$minInterval.', maxInterval='.$maxInterval);
                    } else {
                        // $msg = substr($msg, 8);
                        parserLog('debug', '  Status='.$status.'/'.zbGetZCLStatus($status).', Dir='.$dir.', AttrId='.$attrId);
                    }
                    return;
                }

                else if ($cmd == "0A") { // Report attributes
                    // Some clusters are directly handled by 8100/8102 decode
                    $acceptedCmd0A = ['0300', '050B']; // Clusters handled here
                    if (!in_array($cluster, $acceptedCmd0A)) {
                        parserLog('debug', "  Handled by decode8100_8102");
                        return;
                    }

                    /* Monitor if requested */
                    if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                        monMsgFromZigate("8002/Report attributes response"); // Send message to monitor

                    $l = strlen($msg);
                    $attributes = [];
                    for ($i = 0; $i < $l;) {
                        $attr = $this->decode8002_ReportAttribute(substr($msg, $i), $size);
                        if ($attr === false)
                            break;

                        $attrName = zbGetZCLAttributeName($cluster, $attr['id']);
                        parserLog('debug', '  AttrId='.$attr['id'].'/'.$attrName
                            .', attrType='.$attr['dataType']
                            .', value='.$attr['value'],
                            "8002"
                        );
                        $attrId = $attr['id'];
                        unset($attr['id']); // Remove 'id' from object for optimization
                        $attributes[$attrId] = $attr;
                        $i += $size;
                    }
                    if (sizeof($attributes) == 0)
                        return;

                    // Reporting grouped attributes to Abeille
                    $toAbeille = array(
                        'src' => 'parser',
                        'type' => 'reportAttributes',
                        'net' => $dest,
                        'addr' => $srcAddr,
                        'ep' => $srcEp,
                        'clustId' => $cluster,
                        'attributes' => $attributes,
                        'time' => time(),
                        'lqi' => $lqi,
                    );
                    $this->msgToAbeille2($toAbeille);

                    return;
                }

                else if ($cmd == "0B") { // Default Response
                    // Tcharp38 note: Decoded here because 8101 message does not contain source address
                    $cmdId = substr($msg, 0, 2);
                    $status = substr($msg, 2, 2);

                    parserLog('debug', '  Cmd='.$cmdId
                        .', Status='.$status.' => '.zbGetZCLStatus($status));

                    /* Send to client if connection opened */
                    $toCli = array(
                        'src' => 'parser',
                        'type' => 'defaultResponse',
                        'net' => $dest,
                        'addr' => $srcAddr,
                        'ep' => $srcEp,
                        'clustId' => $cluster,
                        'cmd' => $cmdId,
                        'status' => $status
                    );
                    $this->msgToClient($toCli);
                    return;
                }

                else if ($cmd == "0D") { // Discover Attributes Response
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
                    parserLog('debug', '  Clust '.$cluster.': '.$m);
                    $this->discoverLog('- Clust '.$cluster.': '.$m);

                    /* Send to client if required (EQ page opened) */
                    $toCli = array(
                        'src' => 'parser',
                        'type' => 'discoverAttributesResponse',
                        'net' => $dest,
                        'addr' => $srcAddr,
                        'ep' => $srcEp,
                        'clustId' => $cluster,
                        'dir' => (hexdec($fcf) >> 3) & 1,
                        'attributes' => $attributes
                    );
                    $this->msgToClient($toCli);
                    return;
                }

                else if ($cmd == "12") { // Discover Commands Received Response
                    $completed = substr($msg, 2);
                    $msg = substr($msg, 2); // Skipping 'completed' status
                    $commands = [];
                    $l = strlen($msg);
                    for ($i = 0; $i < $l;) {
                        $commands[] = substr($msg, $i, 2);
                        $i += 2;
                    }
                    parserLog('debug', '  Supported received commands: '.implode("/", $commands));

                    /* Send to client if required (ex: EQ page opened) */
                    $toCli = array(
                        'src' => 'parser',
                        'type' => 'commandsReceived',
                        'net' => $dest,
                        'addr' => $srcAddr,
                        'ep' => $srcEp,
                        'clustId' => $cluster,
                        'commands' => $commands
                    );
                    $this->msgToClient($toCli);
                    return;
                }

                else if ($cmd == "16") { // Discover Attributes Extended Response
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
                    parserLog('debug', '  Clust '.$cluster.': '.$m);
                    $this->discoverLog('- Clust '.$cluster.': '.$m);

                    /* Send to client if required (ex: EQ page opened) */
                    $toCli = array(
                        'src' => 'parser',
                        'type' => 'discoverAttributesExtendedResponse',
                        'net' => $dest,
                        'addr' => $srcAddr,
                        'ep' => $srcEp,
                        'clustId' => $cluster,
                        'dir' => (hexdec($fcf) >> 3) & 1,
                        'attributes' => $attributes
                    );
                    $this->msgToClient($toCli);
                    return;
                }

                parserLog("debug", "  Ignored general command", "8002");
                return;
            }

            /*
             * Cluster specific command
             */

            parserLog('debug', "  FCF=".$fcf."/".$fcfTxt.", SQN=".$SQN.", cmd=".$cmd.'/'.zbGetZCLClusterCmdName($cluster, $cmd));

            // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
            // Tuya 1,2,3,4 buttons switch
            if (($cluster == "0006") && ($cmd == "FD")) {

                $value = substr($payload, 32, 2);
                if ($value == "00")
                    $click = 'single';
                else if ($value == "01")
                    $click = 'double';
                else if ($value == "02")
                    $click = 'long';
                else {
                    parserLog('debug',  '  Tuya 0006-FD specific command'
                        .', value='.$value.' => UNSUPPORTED', "8002");
                    return;
                }

                parserLog('debug',  '  Tuya 0006-FD specific command'
                                .', value='.$value.' => click='.$click, "8002");

                // Generating an event on 'EP-click' Jeedom cmd (ex: '01-click' = 'single')
                $this->msgToAbeille($dest."/".$srcAddr, $srcEp, "click", $click);

                // Legacy code to be revisited
                // TODO: Replace commands '0006-EP-0000' to 'EP-click' in JSON
                $this->msgToAbeille($dest."/".$srcAddr, $cluster.'-'.$srcEp, '0000', $value);

                return;
            }
            if ($cluster == "0006") {
                if (($cmd == "00") || ($cmd == 01)) {
                    parserLog('debug', "  Handled by decode8095");
                    return;
                }
            }

            parserLog("debug", "  Ignored cluster specific command ".$cluster."-".$cmd, "8002");
        }

        // Tcharp38: No longer required.
        // https://github.com/fairecasoimeme/ZiGate/issues/295
        // function decode8003($dest, $payload, $ln, $lqi, $clusterTab) {
        //     // <source endpoint: uint8_t t>
        //     // <profile ID: uint16_t>
        //     // <cluster list: data each entry is uint16_t>

        //     $srcEp = substr($payload, 0, 2);
        //     $$profId   = substr($payload, 2, 4);

        //     $len = (strlen($payload)-2-4-2)/4;
        //     for ($i = 0; $i < $len; $i++) {
        //         $clustId = substr($payload, 6 + ($i * 4), 4);
        //         parserLog('debug', $dest.', Type=8003/Clusters list, SrcEP='.$srcEp.', ProfId='.$$profId.', ClustId='.$clustId.' - '.zgGetCluster($clustId), "8003");
        //     }
        // }

        // Tcharp38: No longer required.
        // https://github.com/fairecasoimeme/ZiGate/issues/295
        // function decode8004($dest, $payload, $lqi) {
        //     // <source endpoint: uint8_t>
        //     // <profile ID: uint16_t>
        //     // <cluster ID: uint16_t>
        //     // <attribute list: data each entry is uint16_t>

        //     $srcEp = substr($payload, 0, 2);
        //     $$profId   = substr($payload, 2, 4);
        //     $clustId   = substr($payload, 6, 4);

        //     $len = (strlen($payload)-2-4-4-2)/4;
        //     for ($i = 0; $i < $len; $i++) {
        //         parserLog('debug', $dest.', Type=8004/Liste des Attributs de l’objet, SrcEP='.$srcEp.', ProfileID='.$$profId.', ClustId='.$clustId.', Attribute='.substr($payload, (10 + ($i*4) ), 4) );
        //     }
        // }

        // Tcharp38: No longer required.
        // https://github.com/fairecasoimeme/ZiGate/issues/295
        // function decode8005($dest, $payload, $lqi) {
        //     // parserLog('debug',';type: 8005: (Liste des commandes de l’objet)(Not Processed)' );

        //     // <source endpoint: uint8_t>
        //     // <profile ID: uint16_t>
        //     // <cluster ID: uint16_t>
        //     //<command ID list:data each entry is uint8_t>

        //     $srcEp = substr($payload, 0, 2);
        //     $$profId   = substr($payload, 2, 4);
        //     $clustId   = substr($payload, 6, 4);

        //     $len = (strlen($payload)-2-4-4-2)/2;
        //     for ($i = 0; $i < $len; $i++) {
        //         parserLog('debug', $dest.', Type=8005/Liste des commandes de l’objet, SrcEP='.$srcEp.', ProfID='.$$profId.', ClustId='.$clustId.', Commandes='.substr($payload, (10 + ($i*2) ), 2) );
        //     }
        // }

        /* 8009/Network State Reponse */
        function decode8009($dest, $payload, $lqi)
        {
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
            parserLog('debug', $dest.', Type='.$msgDecoded, "8009");

            $this->whoTalked[] = $dest.'/'.$addr;

            /* Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $addr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            // Zigate IEEE local storage
            $zgId = substr($dest, 7);
            $GLOBALS['zigate'.$zgId]['ieee'] = $extAddr;

            /* If still required, checking USB port unexpected switch */
            $confIeee = str_replace('Abeille', 'AbeilleIEEE', $dest); // AbeilleX => AbeilleIEEEX
            $confIeeeOk = str_replace('Abeille', 'AbeilleIEEE_Ok', $dest); // AbeilleX => AbeilleIEEE_OkX
            if (config::byKey($confIeeeOk, 'Abeille', 0) == 0) {
                if (config::byKey($confIeee, 'Abeille', 'none', 1) == "none") {
                    config::save($confIeee, $extAddr, 'Abeille');
                    config::save($confIeeeOk, 1, 'Abeille');
                } else if (config::byKey($confIeee, 'Abeille', 'none', 1) == $extAddr) {
                    config::save($confIeeeOk, 1, 'Abeille');
                } else {
                    config::save($confIeeeOk, -1, 'Abeille');
                    message::add("Abeille", "Mauvais port détecté pour zigate ".$zgId.". Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
                    return;
                }
            }

            $msg = array(
                'src' => 'parser',
                'type' => 'networkState',
                'net' => $dest,
                'addr' => $addr, // Should be 0000
                'ieee' => $extAddr,
                'panId' => $panId,
                'extPanId' => $extPanId,
                'chan' => $chan,
                'time' => time()
            );
            $this->msgToAbeille2($msg);

            // // Envoie Short Address
            // $data = $addr;
            // $this->msgToAbeille($dest."/0000", "Short", "Addr", $data);
            // // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; ZiGate Short Address: '.$addr); }

            // // Envoie Extended Address
            // $data = $extAddr;
            // $this->msgToAbeille($dest."/0000", "IEEE", "Addr", $data);
            // // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; IEEE Address: '.$extAddr); }

            // // Envoie PAN ID
            // $data = $panId;
            // $this->msgToAbeille($dest."/0000", "PAN", "ID", $data);
            // // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; PAN ID: '.$panId); }

            // // Envoie Ext PAN ID
            // $data = $extPanId;
            // $this->msgToAbeille($dest."/0000", "Ext_PAN", "ID", $data);
            // // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; Ext_PAN_ID: '.$extPanId); }

            // // Envoie Channel
            // $data = $chan;
            // $this->msgToAbeille($dest."/0000", "Network", "Channel", $data);
        }

        /* Zigate FW version */
        function decode8010($dest, $payload, $lqi)
        {
            /*
            <Major version number: uint16_t>
            <Installer version number: uint16_t>
            */
            $major = substr($payload, 0, 4);
            $minor = substr($payload, 4, 4);

            parserLog('debug', $dest.', Type=8010/Version, Appli='.$major.', SDK='.$minor, "8010");

            // $this->msgToAbeille($dest."/0000", "SW", "Application", $major);
            // $this->msgToAbeille($dest."/0000", "SW", "SDK", $minor);
            $msg = array(
                'src' => 'parser',
                'type' => 'zigateVersion',
                'net' => $dest,
                'major' => $major,
                'minor' => $minor,
                'time' => time()
            );
            $this->msgToAbeille2($msg);
        }

        /**
         * ACK DATA (since FW 3.1b) = ZPS_EVENT_APS_DATA_CONFIRM Note: NACK = 8702
         *
         * This method process a Zigbeee message coming from a zigate for Ack APS messages
         *  Will first decode it.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing
         */
        function decode8011($dest, $payload, $lqi)
        {
            /*
            <Status: uint8_t>
            <Destination address: uint16_t>
            <Dest Endpoint : uint8_t>
            <Cluster ID : uint16_t>
            */
            $Status         = substr($payload, 0, 2);
            $DestAddr       = substr($payload, 2, 4);
            $destEp   = substr($payload, 6, 2);
            $ClustID        = substr($payload, 8, 4);

            $msgDecoded = '8011/APS data ACK, Status='.$Status.', Addr='.$DestAddr.', EP='.$destEp.', ClustId='.$ClustID;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8011");

            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $DestAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            if ($Status=="00") {
                if ( Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' )) {
                    $eq = Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' ) ;
                    parserLog('debug', '  Found: '.$eq->getHumanName()." set APS_ACK to 1", "8011");
                    $eq->setStatus('APS_ACK', '1');
                    // parserLog('debug', '  APS_ACK: '.$eq->getStatus('APS_ACK'), "8011");
                }
            } else {
                if ( Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' )) {
                    $eq = Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' ) ;
                    parserLog('debug', '  ACK failed: '.$eq->getHumanName().". APS_ACK set to 0", "8011");
                    $eq->setStatus('APS_ACK', '0');
                    // parserLog('debug', '  APS_ACK: '.$eq->getStatus('APS_ACK'), "8011");
                }
            }
        }

        /* 8012/
           Confirms that a data packet sent by the local node has been successfully passed down the stack to the MAC layer
           and has made its first hop towards its destination (an acknowledgment has been received from the next hop node) */
        function decode8012($dest, $payload, $lqi)
        {
            // See https://github.com/fairecasoimeme/ZiGate/issues/350
            $msgDecoded = '8012/ZPS_EVENT_APS_DATA_CONFIRM';
            parserLog('debug', $dest.', Type='.$msgDecoded, "8012");
        }

        /**
         * “Permit join” status
         *
         * This method process a Zigbeee message coming from a zigate findicating the Join Permit Status
         * Will first decode it.
         * Send the info to Abeille to update ruche command
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing
         */
        function decode8014($dest, $payload, $lqi)
        {
            // “Permit join” status
            // response Msg Type=0x8014
            // 0 - Off 1 - On
            //<Status: bool_t>
            // Envoi Status

            $Status = substr($payload, 0, 2);
            $zgId = substr($dest, 7);

            // Local status storage
            $GLOBALS['zigate'.$zgId]['permitJoin'] = $Status;

            parserLog('debug', $dest.', Type=8014/Permit join status response, PermitJoinStatus='.$Status);
            if ($Status == "01")
                parserLog('info', '  Zigate'.$zgId.': en mode INCLUSION', "8014");
            else
                parserLog('info', '  Zigate'.$zgId.': mode inclusion inactif', "8014");

            // $this->msgToAbeille($dest."/0000", "permitJoin", "Status", $Status);
            $msg = array(
                'src' => 'parser',
                'type' => 'permitJoin',
                'net' => $dest,
                'status' => $Status,
                'time' => time()
            );
            $this->msgToAbeille2($msg);
        }

        /* Get devices list response */
        function decode8015($dest, $payload, $lqi)
        {
            // <device list – data each entry is 13 bytes>
            // <ID: uint8_t>
            // <Short address: uint16_t>
            // <IEEE address: uint64_t>
            // <Power source: bool_t> 0 – battery 1- AC power
            // <LinkQuality : uint8_t> 1-255

            // Id Short IEEE             Power  LQ
            // 00 ffe1  00158d0001d5c421 00     a6
            // 01 c4bf  00158d0001215781 00     aa
            // 02 4f34  00158d00016d8d4f 00     b5
            // 03 304a  00158d0001a66ca3 00     a4
            // 04 cc0D  00158d0001d6c177 00     b3
            // 05 3c58  00158d00019f9199 00     9a
            // 06 7c3b  000B57fffe2c82e9 00     bb
            // 07 7c54  00158d000183afeb 01     c3
            // 08 3db8  00158d000183af7b 01     c5
            // 32 553c  000B57fffe3025ad 01     9f
            // 00 -> Pourquoi 00 ?

            parserLog('debug', $dest.', Type=8015/Get devices list response, Payload='.$payload);

            $nb = (strlen($payload) - 2) / 26;
            parserLog('debug','  Nombre d\'abeilles: '.$nb);

            for ($i = 0; $i < $nb; $i++) {

                $srcAddr = substr($payload, $i * 26 + 2, 4);

                // Envoie IEEE
                $dataAddr = substr($payload, $i * 26 + 6, 16);
                $this->msgToAbeille($dest."/".$srcAddr, "IEEE", "Addr", $dataAddr);

                // Envoie Power Source
                $dataPower = substr($payload, $i * 26 + 22, 2);
                $this->msgToAbeille($dest."/".$srcAddr, "Power", "Source", $dataPower);

                // Envoie Link Quality
                $dataLink = hexdec(substr($payload, $i * 26 + 24, 2));
                $this->msgToAbeille($dest."/".$srcAddr, "Link", "Quality", $dataLink);

                parserLog('debug', '  i='.$i.': '
                                .'ID='.substr($payload, $i * 26 + 0, 2)
                                .', ShortAddr='.$srcAddr
                                .', ExtAddr='.$dataAddr
                                .', PowerSource (0:battery - 1:AC)='.$dataPower
                                .', LinkQuality='.$dataLink   );
            }
        }

        /* 8017/Get Time Server Response (FW >= 3.0f) */
        function decode8017($dest, $payload, $lqi)
        {
            // <Timestamp UTC: uint32_t> from 2000-01-01 00:00:00
            $timestamp = substr($payload, 0, 8);
            parserLog('debug', $dest.', Type=8017/Get time server response, Timestamp='.hexdec($timestamp), "8017");

            // Note: updating timestamp ref from 2000 to 1970
            $data = date(DATE_RFC2822, hexdec($timestamp) + mktime(0, 0, 0, 1, 1, 2000));
            // $this->msgToAbeille($dest."/0000", "ZiGate", "Time", $data);
            $msg = array(
                'src' => 'parser',
                'type' => 'zigateTime',
                'net' => $dest,
                'timeServer' => $data,
                'time' => time()
            );
            $this->msgToAbeille2($msg);
        }

        /* Network joined/formed */
        function decode8024($dest, $payload, $lqi)
        {
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
            parserLog('debug', $dest.', Type=8024/Network joined-formed, Status=\''.$data.'\', Addr='.$dataShort.', ExtAddr='.$dataIEEE.', Chan='.$dataNetwork, "8024");

            // Zigate IEEE local storage
            $zgId = substr($dest, 7);
            $GLOBALS['zigate'.$zgId]['ieee'] = $dataIEEE;

            /* If still required, checking USB port unexpected switch */
            $confIeee = str_replace('Abeille', 'AbeilleIEEE', $dest); // AbeilleX => AbeilleIEEEX
            $confIeeeOk = str_replace('Abeille', 'AbeilleIEEE_Ok', $dest); // AbeilleX => AbeilleIEEE_OkX
            if (config::byKey($confIeeeOk, 'Abeille', 0) == 0) {
                if (config::byKey($confIeee, 'Abeille', 'none', 1) == "none") {
                    config::save($confIeee, $dataIEEE, 'Abeille');
                    config::save($confIeeeOk, 1, 'Abeille');
                } else if (config::byKey($confIeee, 'Abeille', 'none', 1) == $dataIEEE) {
                    config::save($confIeeeOk, 1, 'Abeille');
                } else {
                    config::save($confIeeeOk, -1, 'Abeille');
                    $zgId = substr($dest, 7); // AbeilleX => X
                    message::add("Abeille", "Mauvais port détecté pour zigate ".$zgId.". Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
                    return;
                }
            }

            // Envoi Status
            // $this->msgToAbeille($dest."/0000", "Network", "Status", $data);
            // $this->msgToAbeille($dest."/0000", "Short", "Addr", $dataShort);
            // $this->msgToAbeille($dest."/0000", "IEEE", "Addr", $dataIEEE);
            // $this->msgToAbeille($dest."/0000", "Network", "Channel", $dataNetwork);
            $msg = array(
                'src' => 'parser',
                'type' => 'networkStarted',
                'net' => $dest,
                'status' => $status,
                'statusTxt' => $data,
                'addr' => $dataShort, // Should be always 0000
                'ieee' => $dataIEEE, // Zigate IEEE
                'chan' => $dataNetwork,
                'time' => time()
            );
            $this->msgToAbeille2($msg);
        }

        // 8030/Bind response
        function decode8030($dest, $payload, $lqi)
        {
            // See https://github.com/fairecasoimeme/ZiGate/issues/122
            // <Sequence number: uint8_t>
            // <status: uint8_t>
            // <Src address mode: uint8_t> (only from v3.1a)
            // <Src Address : uint16_t> (only from v3.1a)

            parserLog('debug', $dest.', Type=8030/Bind response'
                            .', SQN='.substr($payload, 0, 2)
                            .', Status='.substr($payload, 2, 2)
                            .', SrcAddrMode='.substr($payload, 4, 2)
                            .', SrcAddr='.substr($payload, 6, 4), "8030");

            $data = date("Y-m-d H:i:s")." Status (00: Ok, <>0: Error): ".substr($payload, 2, 2);
            $this->msgToAbeille($dest."/0000", "Network", "Bind", $data);
        }

        /* 8035/PDM event code. Since FW 3.1b */
        function decode8035($dest, $payload, $lqi)
        {
            $PDMEvtCode = substr($payload, 0, 2); // <PDM event code: uint8_t>
            $RecId = substr($payload, 2, 8); // <record id : uint32_t>

            parserLog('debug', $dest.', Type=8035/PDM event code'
                             .', PDMEvtCode=x'.$PDMEvtCode
                             .', RecId='.$RecId
                             .' => '.zgGetPDMEvent($PDMEvtCode), "8035");
        }

        function decode8040($dest, $payload, $lqi)
        {
            // Firmware V3.1a: Add SrcAddr to 0x8040 command (MANAGEMENT_LQI_REQUEST) https://github.com/fairecasoimeme/ZiGate/issues/198

            // Network address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>
            $Addr = substr($payload,20, 4);

            $msgDecoded='8040/Network address response'
               .', SQN='                                     .substr($payload, 0, 2)
               .', Status='                                  .substr($payload, 2, 2)
               .', ExtAddr='                                 .substr($payload, 4,16)
               .', Addr='                               .$Addr
               .', NumberOfAssociatedDevices='               .substr($payload,24, 2)
               .', StartIndex='                              .substr($payload,26, 2);
            parserLog('debug', $dest.', Type='.$msgDecoded, "8040");

            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $Addr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            if ( substr($payload, 2, 2) != "00" ) {
                parserLog('debug', '  Don\'t use this data there is an error');
            } else {
                for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
                    parserLog('debug', '  AssociatedDev='.substr($payload, (28 + $i), 4));
                }
            }
        }

        function decode8041($dest, $payload, $lqi)
        {
            // IEEE Address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>
            $sqn = substr($payload, 0, 2);
            $status = substr($payload, 2, 2);
            $ieee = substr($payload, 4, 16);
            $addr = substr($payload, 20, 4);
            $nbDevices = substr($payload, 24, 2);
            $startIdx = substr($payload, 26, 2);

            $msgDecoded = '8041/IEEE address response'
                            .', SQN='.$sqn
                            .', Status='.$status
                            .', ExtAddr='.$ieee
                            .', Addr='.$addr
                            .', NbOfAssociatedDevices='.$nbDevices
                            .', StartIndex='.$startIdx;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8041");

            $this->whoTalked[] = $dest.'/'.$addr;

            /* Monitor if required */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $addr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            if ($status != "00") {
                parserLog('debug', '  Status='.$status.' => Unknown error');
                return;
            }

            for ($i = 0; $i < (intval($nbDevices) * 4); $i += 4) {
                parserLog('debug', '  AssociatedDev='.substr($payload, (28 + $i), 4));
            }

            $this->msgToAbeille($dest."/".$addr, "IEEE", "Addr", $ieee);
        }

        /**
         * Simple descriptor response
         *
         * This method process a Zigbeee message coming from a device indicating it s simple description
         *  Will first decode it.
         *  And send to Abeille only the Type of Equipement. Could be used if the model don't existe based on the name.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8043($dest, $payload, $lqi)
        {
            // <Sequence number: uint8_t>   -> 2
            // <status: uint8_t>            -> 2
            // <nwkAddress: uint16_t>       -> 4
            // <length: uint8_t>            -> 2
            // <endpoint: uint8_t>          -> 2
            // <profile: uint16_t>          -> 4
            // <device id: uint16_t>        -> 4
            // <bit fields: uint8_t >       -> 2
            //      Bit fields: Device version: 4 bits (bits 0-4) Reserved: 4 bits (bits4-7)
            // <InClusterCount: uint8_t >   -> 2
            // <In cluster list: data each entry is uint16_t> -> 4
            // <OutClusterCount: uint8_t>   -> 2
            // <Out cluster list: data each entry is uint16_t> -> 4

            $SQN        = substr($payload, 0, 2);
            $Status     = substr($payload, 2, 2);
            $srcAddr    = substr($payload, 4, 4);
            $Len        = substr($payload, 8, 2);
            $ep         = substr($payload,10, 2);
            $profId    = substr($payload,12, 4);
            $deviceId   = substr($payload,16, 4);

            /* Log */
            $msgDecoded = '8043/Simple descriptor response'
                            .', SQN='         .$SQN
                            .', Status='      .$Status
                            .', Addr='        .$srcAddr
                            .', Length='      .$Len
                            .', EP='          .$ep
                            .', ProfId='     .$profId.'/'.zgGetProfile($profId)
                            .', DevId='       .$deviceId.'/'.zgGetDevice($profId, $deviceId)
                            .', BitField='    .substr($payload,20, 2);
            parserLog('debug', $dest.', Type='.$msgDecoded, "8043");

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            /* Send to Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msgDecoded);

            $discovering = $this->discoveringState($dest, $srcAddr);
            if ($discovering) $this->discoverLog($msgDecoded);

            if ($Status != "00") {
                if ($Status == "83")
                    $statusMsg = 'EP is NOT active';
                else
                    $statusMsg = 'Unknown status '.$Status;
                parserLog('debug', '  '.$statusMsg, "8043");
                if ($discovering) $this->discoverLog('- '.$statusMsg);
                return;
            }

            /* Continue msg decoding if status == 00 */
            $InClustCount = hexdec(substr($payload, 22, 2)); // Number of server clusters
            $InClustList = [];
            for ($i = 0; $i < ($InClustCount * 4); $i += 4) {
                $InClustList[] = substr($payload, (24 + $i), 4);
            }
            $OutClustCount = hexdec(substr($payload, 24 + $i, 2));
            $OutClustList = [];
            for ($j = 0; $j < ($OutClustCount * 4); $j += 4) {
                $OutClustList[] = substr($payload, (24 + $i + 2 + $j), 4);
            }

            // Envoie l info a Abeille: Tcharp38: What for ?
            // $data = zgGetDevice($profId, $deviceId);
            // $this->msgToAbeille($dest."/".$srcAddr, "SimpleDesc", "DeviceDescription", $data);

            /* Log */
            parserLog('debug','  InClusterCount='.$InClustCount, "8043");
            $inputClusters = "";
            for ($i = 0; $i < $InClustCount; $i++) {
                $clustId = $InClustList[$i];
                if ($i != 0)
                    $inputClusters .= "/";
                $inputClusters .= $clustId;
                parserLog('debug', '  InCluster='.$clustId.' => '.zgGetCluster($clustId), "8043");
            }
            parserLog('debug','  OutClusterCount='.substr($payload,24+$i, 2), "8043");
            $outputClusters = "";
            for ($i = 0; $i < $OutClustCount; $i++) {
                $clustId = $OutClustList[$i];
                if ($i != 0)
                    $outputClusters .= "/";
                $outputClusters .= $clustId;
                parserLog('debug', '  OutCluster='.$clustId.' => '.zgGetCluster($clustId), "8043");
            }

            /* If discovering phase => log to 'AbeilleDiscover.log' & attempt to discover attributes */
            if ($discovering) {
                $this->discoverLog('- InClusterCount='.$InClustCount);
                for ($i = 0; $i < $InClustCount; $i++) {
                    $clustId = $InClustList[$i];
                    $this->discoverLog('- InCluster='.$clustId.' => '.zgGetCluster($clustId));
                    // parserLog('debug', '  Requesting supported attributs list for EP '.$ep.', clust '.$clustId);
                    // Tcharp38: Some devices may not support discover attribut command and return a "default response" with status 82 (unsupported general command)
                    // Tcharp38: Some devices do not respond at all (ex: Sonoff SNBZ02)
                    $this->msgToCmd("Cmd".$dest."/".$srcAddr."/discoverAttributes", "ep=".$ep.'&clustId='.$clustId.'&dir=00&startAttrId=0000&maxAttrId=FF');
                }
                $this->discoverLog('- OutClusterCount='.$OutClustCount);
                for ($i = 0; $i < $OutClustCount; $i++) {
                    $clustId = $OutClustList[$i];
                    $this->discoverLog('- OutCluster='.$clustId.' => '.zgGetCluster($clustId));
                }
            }

            /* Send to client if required (EQ page opened) */
            $toCli = array(
                'src' => 'parser',
                'type' => 'simpleDesc',
                'net' => $dest,
                'addr' => $srcAddr,
                'ep' => $ep,
                'inClustList' => $inputClusters, // Format: 'xxxx/yyyy/zzzz'
                'outClustList' => $outputClusters // Format: 'xxxx/yyyy/zzzz'
            );
            $this->msgToClient($toCli);

            // Tcharp38: Send to Abeille. What for ?
            // $data = 'zigbee'.zgGetDevice($profId, $deviceId);
            // if ( strlen( $data) > 1 ) {
            //     $this->msgToAbeille($dest."/".$srcAddr, "SimpleDesc-".$ep, "DeviceDescription", $data);
            // }
        }

        /**
         * Active Endpoints Response
         *
         * This method process a Zigbeee message coming from a device indicating existing EP
         *  Will first decode it.
         *  Continue device identification by requesting Manufacturer, Name, Location, simpleDescriptor to the device.
         *  Then request the configuration of the device and even more infos.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8045($dest, $payload, $lqi)
        {
            $SQN            = substr($payload, 0, 2);
            $status         = substr($payload, 2, 2);
            $srcAddr        = substr($payload, 4, 4);
            $EndPointCount  = substr($payload, 8, 2);
            $endPointList = "";
            for ($i = 0; $i < (intval($EndPointCount) * 2); $i += 2) {
                if ($i != 0)
                    $endPointList .= "/";
                $endPointList .= substr($payload, (10 + $i), 2);
                if ($i == 0) {
                    $EP = substr($payload, (10 + $i), 2);
                }
            }

            $msgDecoded = '8045/Active endpoints response'
               .', SQN='.$SQN
               .', Status='.$status
               .', Addr='.$srcAddr
               .', EPCount='.$EndPointCount
               .', EPList='.$endPointList;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8045");

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            /* Monitor is required */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            /* Send to client */
            $toCli = array(
                'src' => 'parser',
                'type' => 'activeEndpoints',
                'net' => $dest,
                'addr' => $srcAddr,
                'epList' => $endPointList
            );
            $this->msgToClient($toCli);

            if ($status != "00") {
                parserLog('debug', '  Status != 0 => ignoring');
                return;
            }

            /* Update equipement key infos */
            $this->deviceUpdate($dest, $srcAddr, 'epList', $endPointList);

            // parserLog('debug', '  Asking details for EP '.$EP.' [Modelisation]' );
            // $this->msgToCmd("Cmd".$dest."/0000/getManufacturerName", "address=".$srcAddr.'&destinationEndPoint='.$EP );
            // $this->msgToCmd("Cmd".$dest."/0000/getName", "address=".$srcAddr.'&destinationEndPoint='.$EP );
            // $this->msgToCmd("Cmd".$dest."/0000/getLocation", "address=".$srcAddr.'&destinationEndPoint='.$EP );
            // $this->msgToCmd("TempoCmd".$dest."/0000/SimpleDescriptorRequest&time=".(time() + 4), "address=".$srcAddr.'&endPoint='.           $EP );

            // $this->actionQueue[] = array('when' => time() + 8, 'what' => 'configureNE', 'addr'=>$dest.'/'.$srcAddr);
            // $this->actionQueue[] = array('when' => time() + 11, 'what' => 'getNE', 'addr'=>$dest.'/'.$srcAddr);
        }

        /**
         * 8048/Leave indication
         *
         * This method process a Zigbeee message coming from a device indicating Leaving
         *  Will first decode it.
         *  Continue device identification by requesting Manufacturer, Name, Location, simpleDescriptor to the device.
         *  Then request the configuration of the device and even more infos.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8048($dest, $payload, $lqi)
        {
            /* Decode */
            $IEEE = substr($payload, 0, 16);
            $RejoinStatus = substr($payload, 16, 2);

            /* Log */
            $msgDecoded = '8048/Leave indication, ExtAddr='.$IEEE.', RejoinStatus='.$RejoinStatus;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8048");

            /* Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddrExt"]) && !strcasecmp($GLOBALS["dbgMonitorAddrExt"], $IEEE))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            /* Config ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                'src' => 'parser',
                'type' => 'leaveIndication',
                'net' => $dest,
                'ieee' => $IEEE,
                'rejoin' => $RejoinStatus,
                'time' => time(),
                'lqi' => $lqi
            );
            $this->msgToAbeille2($msg);

            // $data = "Leave->".$IEEE."->".$RejoinStatus;
            // $this->msgToAbeille($dest."/0000", "joinLeave", "IEEE", $data);

            // $this->msgToAbeilleFct($dest."/0000", "disable", $IEEE);
        }

        /* 804A = Management Network Update Response */
        function decode804A($dest, $payload, $lqi)
        {
            /* Note: Source address added in FW V3.1a */
            /* <Sequence number: uint8_t>
               <status: uint8_t>
               <total transmission: uint16_t>
               <transmission failures: uint16_t>
               <scanned channels: uint32_t >
               <scanned channel list count: uint8_t>
               <channel list: list each element is uint8_t>
               <Src Address : uint16_t> (only from v3.1a) */

            // app_general_events_handler.c
            // E_SL_MSG_MANAGEMENT_NETWORK_UPDATE_RESPONSE

            $SQN = substr($payload, 0, 2);
            $Status = substr($payload, 2, 2);
            $TotalTransmission = substr($payload, 4, 4);
            $TransmFailures = substr($payload, 8, 4);
            $ScannedChannels = substr($payload, 12, 8);
            $ScannedChannelsCount = substr($payload, 20, 2);

            $msgDecoded = '804A/Management network update response'
               .', SQN='.$SQN
               .', Status='.$Status
               .', TotTx='.$TotalTransmission
               .', TxFailures='.$TransmFailures
               .', ScannedChan='.$ScannedChannels
               .', ScannedChanCount='.$ScannedChannelsCount;
            parserLog('debug', $dest.', Type='.$msgDecoded, "804A");

            if ($Status!="00") {
                parserLog('debug', '  Status Error ('.$Status.') can not process the message.', "804A");
                return;
            }

            /*
            $chans = "";
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($payload, (22 + ($i * 2)), 2); // hexa value
                if ($i != 0)
                    $chans .= ';';
                $chans .= hexdec($Chan);
            }

            parserLog('debug', '  Channels='.$chans.' address='.$addr);
            */

            $chan = 11; // Could need to be adapted if we change the list of channel requested, at this time all of them.
            $results = array();
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($payload, (22 + ($i * 2)), 2); // hexa value
                $results[$chan] = hexdec($Chan);
                $chan++;
            }
            $addr = substr($payload, (22 + ($i * 2)), 4);

            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list( $eqDest, $eqAddr ) = explode("/", $eqLogic->getLogicalId());
                if (($dest==$eqDest) &&($addr==$eqAddr)) {
                    // parserLog('debug', '  Storing the information');
                    $eqLogic->setConfiguration('totalTransmission', $TotalTransmission);
                    $eqLogic->setConfiguration('transmissionFailures', $TransmFailures);
                    $eqLogic->setConfiguration('localZigbeeChannelPower', $results);
                    $eqLogic->save();
                }
            }

            parserLog('debug', '  Channels='.json_encode($results), "804A");
        }

        /* 804E/Management LQI response */
        function decode804E($dest, $payload, $lqi)
        {
            // <Sequence number: uint8_t>
            // <status: uint8_t>
            // <Neighbour Table Entries : uint8_t>
            // <Neighbour Table List Count : uint8_t>
            // <Start Index : uint8_t>
            // <List of elements described below :> (empty if 'Neighbour Table list count' is 0)
            //      NWK Address : uint16_t
            //      Extended PAN ID : uint64_t
            //      IEEE Address : uint64_t
            //      Depth : uint_t
            //      Link Quality : uint8_t
            //      Bit map of attributes Described below: uint8_t
            //          bit 0-1 Device Type (0-Coordinator 1-Router 2-End Device)
            //          bit 2-3 Permit Join status (1- On 0-Off)
            //          bit 4-5 Relationship (0-Parent 1-Child 2-Sibling)
            //          bit 6-7 Rx On When Idle status (1-On 0-Off)
            // <Src Address : uint16_t> (only since v3.1a)

            $SQN = substr($payload, 0, 2);
            $Status = substr($payload, 2, 2);
            $NTableEntries = substr($payload, 4, 2);
            $NTableListCount = substr($payload, 6, 2);
            $StartIndex = substr($payload, 8, 2);
            $srcAddr = substr($payload, 10 + (hexdec($NTableListCount) * 42), 4); // 21 bytes per neighbour entry

            $decoded = '804E/Management LQI response'
                .', SQN='               .$SQN
                .', Status='            .$Status
                .', NTableEntries='     .$NTableEntries
                .', NTableListCount='   .$NTableListCount
                .', StartIndex='        .$StartIndex
                .', SrcAddr='           .$srcAddr;
            parserLog('debug', $dest.', Type='.$decoded);

            /* Monitor if requested */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($decoded); // Send message to monitor

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            if ($Status != "00") {
                parserLog('debug', "  Status != 00 => abandon du decode");
                return;
            }

            $j = 10; // Neighbours list starts at char 10
            $NList = []; // List of neighbours
            for ($i = 0; $i < hexdec($NTableListCount); $j += 42, $i++) {
                $N = array(
                    "Addr"     => substr($payload, $j + 0, 4),
                    "ExtPANId" => substr($payload, $j + 4, 16),
                    "ExtAddr"  => substr($payload, $j + 20, 16),
                    "Depth"    => substr($payload, $j + 36, 2),
                    "LQI"      => substr($payload, $j + 38, 2),
                    "BitMap"   => substr($payload, $j + 40, 2)
                );
                $NList[] = $N; // Add to neighbours list
                parserLog('debug', '  NAddr='.$N['Addr']
                    .', NExtPANId='.$N['ExtPANId']
                    .', NExtAddr='.$N['ExtAddr']
                    .', NDepth='.$N['Depth']
                    .', NLQI='.$N['LQI']
                    .', NBitMap='.$N['BitMap'].' => '.zgGet804EBitMap($N['BitMap']));

                /* If equipment is unknown, may try to interrogate it.
                   Note: this is blocked by default. Unknown equipement should join only during inclusion phase.
                   Note: this could not work for battery powered eq since they will not listen & reply.
                   Cmdxxxx/Ruche/getName address=bbf5&destinationEndPoint=0B */
                if (($N['Addr'] != "0000") && !Abeille::byLogicalId($dest.'/'.$N['Addr'], 'Abeille')) {
                    if (config::byKey('blocageRecuperationEquipement', 'Abeille', 'Oui', 1) == "Oui") {
                        parserLog('debug', '  Eq addr '.$N['Addr']." is unknown.");
                    } else {
                        parserLog('debug', '  Eq addr '.$N['Addr']." is unknown. Trying to interrogate.");

                        $this->msgToCmd("Cmd".$dest."/0000/getName", "address=".$N['Addr']."&destinationEndPoint=01");
                        $this->msgToCmd("Cmd".$dest."/0000/getName", "address=".$N['Addr']."&destinationEndPoint=03");
                        $this->msgToCmd("Cmd".$dest."/0000/getName", "address=".$N['Addr']."&destinationEndPoint=0B");
                    }
                }

                /* Tcharp38: Commented for 2 reasons
                   1/ this leads to 'lastCommunication' updated for $N['Addr'] while NO message received from him, so just wrong.
                   2/ such code is just there to work-around a missed short addr change. */
                // $this->msgToAbeilleCmdFct($dest."/".$N['Addr']."/IEEE-Addr", $N['ExtAddr']);
            }

            $this->msgToLQICollector($srcAddr, $NTableEntries, $NTableListCount, $StartIndex, $NList);
            // Tcharp38 TODO: lastComm can be updated for $srcAddr only
        }

        //----------------------------------------------------------------------------------------------------------------
        function decode8060($dest, $payload, $lqi)
        {
            // Answer format changed: https://github.com/fairecasoimeme/ZiGate/pull/97
            // Bizard je ne vois pas la nouvelle ligne dans le maaster zigate alors qu elle est dans GitHub

            // <Sequence number:  uint8_t>
            // <endpoint:         uint8_t>
            // <Cluster id:       uint16_t>
            // <status:           uint8_t>  (added only from 3.0f version)
            // <Group id :        uint16_t> (added only from 3.0f version)
            // <Src Addr:         uint16_t> (added only from 3.0f version)

            parserLog('debug', $dest.', Type=8060/Add a group response'
                            .', SQN='           .substr($payload, 0, 2)
                            .', EndPoint='      .substr($payload, 2, 2)
                            .', ClusterId='     .substr($payload, 4, 4)
                            .', Status='        .substr($payload, 8, 2)
                            .', GroupID='       .substr($payload,10, 4)
                            .', SrcAddr='       .substr($payload,14, 4) );
        }

        // Get Group Membership response
        function decode8062($dest, $payload, $lqi)
        {
            // <Sequence number: uint8_t>                               -> 2
            // <endpoint: uint8_t>                                      -> 2
            // <Cluster id: uint16_t>                                   -> 4
            // <Src Addr: uint16_t> (added only from 3.0d version)      -> 4 ????
            // <capacity: uint8_t>                                      -> 2
            // <Group count: uint8_t>                                   -> 2
            // <List of Group id: list each data item uint16_t>         -> 4x
            // <Src Addr: uint16_t> (added only from 3.0f version) new due to a change impacting many command but here already available above.

            $sqn = substr($payload, 0, 2);
            $ep = substr($payload, 2, 2);
            $clustId = substr($payload, 4, 4);
            $capa = substr($payload, 8, 2);
            $groupCount = substr($payload,10, 2);
            $srcAddr = substr($payload, 12 + ($groupCount * 4), 4);

            $decoded = '8062/Group membership'
               .', SQN='.$sqn
               .', EP='.$ep
               .', ClustId='.$clustId
               .', Capacity='.$capa
               .', GroupCount='.$groupCount
               .', Addr='.$srcAddr;

            parserLog('debug', $dest.', Type='.$decoded);

            $groups = "";
            for ($i = 0; $i < hexdec($groupCount); $i++) {
                if ($i != 0)
                    $groups .= '/';
                $groups .= substr($payload, 12 + ($i * 4), 4);
            }
            if ($groups == "")
                $groups = "none";
            parserLog('debug', "  Groups: ".$groups);

            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr)) {
                monMsgFromZigate($decoded); // Send message to monitor
                monMsgFromZigate("  Groups: ".$groups); // Send message to monitor
            }

            $this->msgToAbeille($dest."/".$srcAddr, "Group", "Membership", $groups);
        }

        function decode8063($dest, $payload, $lqi)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <Group id: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            parserLog('debug', $dest.', Type=8063/Remove a group response'
                            .', SQN='          .substr($payload, 0, 2)
                            .', EndPoint='     .substr($payload, 2, 2)
                            .', clusterId='    .substr($payload, 4, 4)
                            .', statusId='     .substr($payload, 8, 2)
                            .', groupId='      .substr($payload,10, 4)
                            .', sourceId='     .substr($payload,14, 4) );
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

        function decode8085($dest, $payload, $lqi)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <addr: uint16_t>             -> 4
            // <cmd: uint8>                 -> 2
            //  2: 'click', 1: 'hold', 3: 'release'

            $srcAddr = substr($payload, 10, 4);
            $cmd = substr($payload, 14, 2);

            parserLog('debug', $dest.', Type=8085/Remote button pressed (ClickHoldRelease) a group response)'
                            .', SQN='.substr($payload, 0, 2)
                            .', EP='.substr($payload, 2, 2)
                            .', ClustId='.substr($payload, 4, 4)
                            .', AddrMode='.substr($payload, 8, 2)
                            .', SrcAddr='.$srcAddr
                            .', Cmd='.$cmd);

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            $this->msgToAbeille($dest.'/'.$srcAddr, "Up", "Down", $cmd);
        }

        function decode8095($dest, $payload, $lqi)
        {
            // <Sequence number: uin8_t>
            // <endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <address_mode: uint8_t>
            // <SrcAddr: uint16_t>
            // <status: uint8>
            $ep = substr($payload, 2, 2);
            $clustId = substr($payload, 4, 4);
            $addrMode = substr($payload, 8, 2);
            $srcAddr = substr($payload, 10, 4);
            $status = substr($payload, 14, 2);

            parserLog('debug', $dest.', Type=8095/OnOff update'
                .', SQN='.substr($payload, 0, 2)
                .', EP='.$ep
                .', ClustId='.$clustId
                .', AddrMode='.$addrMode
                .', Addr='.$srcAddr
                .', Status='.$status);

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            /* Forwarding to Abeille */
            // $this->msgToAbeille($dest.'/'.$srcAddr, "Click", "Middle", $status);
            // Tcharp38: The 'Click-Middle' must be avoided. Can't define EP so the source of this "click".
            //           Moreover no sense since there may have no link with "middle". It's is just a OnOff cmd FROM a device to Zigate.
            $msg = array(
                'src' => 'parser',
                'type' => 'attributeReport',
                'net' => $dest,
                'addr' => $srcAddr,
                'ep' => $ep,
                'name' => 'Click-Middle',
                'value' => $status,
                'time' => time(),
                'lqi' => $lqi
            );
            $this->msgToAbeille2($msg);

            // Tcharp38: New way of handling this event (OnOff cmd coming from a device)
            $msg['name'] = $ep.'-cmd-onoff';
            $this->msgToAbeille2($msg);
        }

        //----------------------------------------------------------------------------------------------------------------
        ##TODO
        #reponse scene
        #80a0-80a6
        function decode80A0($dest, $payload, $lqi)
        {
            // <sequence number: uint8_t>                           -> 2
            // <endpoint : uint8_t>                                 -> 2
            // <cluster id: uint16_t>                               -> 4
            // <status: uint8_t>                                    -> 2

            // <group ID: uint16_t>                                 -> 4
            // <scene ID: uint8_t>                                  -> 2
            // <transition time: uint16_t>                          -> 4

            // <scene name length: uint8_t>                         -> 2
            // <scene name max length: uint8_t>                     -> 2
            // <scene name  data: data each element is uint8_t>     -> 2

            // <extensions length: uint16_t>                        -> 4
            // <extensions max length: uint16_t>                    -> 4
            // <extensions data: data each element is uint8_t>      -> 2
            // <Src Addr: uint16_t> (added only from 3.0f version)

            parserLog('debug', $dest.', Type=80A0/Scene View'
                            .', SQN='                           .substr($payload, 0, 2)
                            .', EndPoint='                      .substr($payload, 2, 2)
                            .', ClusterId='                     .substr($payload, 4, 4)
                            .', Status='                        .substr($payload, 8, 2)

                            .', GroupID='                       .substr($payload,10, 4)
                            .', SceneID='                       .substr($payload,14, 2)
                            .', transition time: '              .substr($payload,16, 4)

                            .', scene name lenght: '            .substr($payload,20, 2)  // Osram Plug repond 0 pour lenght et rien apres.
                            .', scene name max lenght: '        .substr($payload,22, 2)
                            .', scene name : '                  .substr($payload,24, 2)

                            .', scene extensions lenght: '      .substr($payload,26, 4)
                            .', scene extensions max lenght: '  .substr($payload,30, 4)
                            .', scene extensions : '            .substr($payload,34, 2) );
        }

        function decode80A3($dest, $payload, $lqi)
        {
            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            parserLog('debug', $dest.', Type=80A3/Remove All Scene'
                            .', SQN='          .substr($payload, 0, 2)
                            .', EndPoint='     .substr($payload, 2, 2)
                            .', ClusterId='    .substr($payload, 4, 4)
                            .', Status='       .substr($payload, 8, 2)
                            .', group ID='     .substr($payload,10, 4)
                            .', source='       .substr($payload,14, 4)  );
        }

        function decode80A4($dest, $payload, $lqi)
        {
            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <scene ID: uint8_t>          -> 2
            // <Src Addr: uint16_t> (added only from 3.0f version)

            parserLog('debug', $dest.', Type=80A4/Store Scene Response'
                            .', SQN='          .substr($payload, 0, 2)
                            .', EndPoint='     .substr($payload, 2, 2)
                            .', ClusterId='    .substr($payload, 4, 4)
                            .', Status='       .substr($payload, 8, 2)
                            .', GroupID='      .substr($payload,10, 4)
                            .', SceneID='      .substr($payload,14, 2)
                            .', Source='       .substr($payload,16, 4)  );
        }

        function decode80A6($dest, $payload, $lqi)
        {
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
                $this->msgToAbeille($dest."/".$source, $clustId, $attrId, $data);
            }
        }

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

        function decode80A7($dest, $payload, $lqi)
        {
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

            $this->whoTalked[] = $dest.'/'.$source;

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

            $clustId = "80A7";
            $attrId = "Cmd";
            $data = $cmd;
            $this->msgToAbeille($dest."/".$source, $clustId, $attrId, $data);

            $clustId = "80A7";
            $attrId = "Direction";
            $data = $direction;
            $this->msgToAbeille($dest."/".$source, $clustId, $attrId, $data);
        }

        /* Common function for 8100 & 8102 messages */
        function decode8100_8102($type, $dest, $payload, $lqi)
        {
            //<Sequence number: uint8_t>
            //<Src address : uint16_t>
            //<Endpoint: uint8_t>
            //<Cluster id: uint16_t>
            //<Attribute Enum: uint16_t>
            //<Attribute status: uint8_t>
            //<Attribute data type: uint8_t>
            //<Size Of the attributes in bytes: uint16_t>
            //<Data byte list : stream of uint8_t>
            $SQN        = substr($payload,  0, 2);
            $srcAddr    = substr($payload,  2, 4);
            $ep         = substr($payload,  6, 2);
            $clustId    = substr($payload,  8, 4);
            $attrId     = substr($payload, 12, 4);
            $attrStatus = substr($payload, 16, 2);
            $dataType   = substr($payload, 18, 2);
            $attrSize   = substr($payload, 20, 4);
            $Attribut   = substr($payload, 24, hexdec($attrSize) * 2);

            if ($type == "8100")
                $msg = '8100/Read individual attribute response';
            else
                $msg = '8102/Attribute report';
            $msg .= ', SQN='            .$SQN
                    .', Addr='          .$srcAddr
                    .', EP='            .$ep
                    .', ClustId='       .$clustId
                    .', AttrId='        .$attrId
                    .', AttrStatus='    .$attrStatus
                    .', AttrDataType='  .$dataType
                    .', AttrSize='      .$attrSize;

            parserLog('debug', $dest.', Type='.$msg, $type);
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msg); // Send message to monitor

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            if ($clustId == "0005") {
                parserLog('debug', '  Handled by decode8002');
                return;
            }

            /* Params: SrcAddr, ClustId, AttrId, Data */
            $this->msgToAbeille($dest."/".$srcAddr, 'Link', 'Quality', $lqi);

            if ($attrStatus == '86') {
                parserLog('debug', '  Status 86 => Unsupported attribute type ', $type);
                if ($clustId == "0000") {
                    switch ($attrId) {
                    case "0004":
                        $this->deviceUpdate($dest, $srcAddr, 'manufacturer', false);
                        break;
                    case "0005":
                        $this->deviceUpdate($dest, $srcAddr, 'modelIdentifier', false);
                        break;
                    case "0010":
                        $this->deviceUpdate($dest, $srcAddr, 'location', false);
                        break;
                    default:
                        break;
                    }
                }

                /* Forwarding unsupported atttribute to Abeille */
                $msg = array(
                    'src' => 'parser',
                    'type' => 'attributeReport',
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'name' => $clustId.'-'.$ep.'-'.$attrId,
                    'value' => false, // False = unsupported
                    'time' => time(),
                    'lqi' => $lqi
                );
                $this->msgToAbeille2($msg);

                /* Send to client if connection opened */
                $toCli = array(
                    'src' => 'parser',
                    'type' => 'attributeReport', // 8100 or 8102
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'clustId' => $clustId,
                    'attrId' => $attrId,
                    'status' => "86"
                );
                $this->msgToClient($toCli);

                return;
            }
            if ($attrStatus != '00') {
                parserLog('debug', '  Status != 0 => Ignored', $type);
                return;
            }

            /* Treating message in the following order
               - by clustId
               - then attribId.
               If not treated there, then next steps will be
               - renaining custom cases that should be moved in first place
               - and finally direct conversion according to attrib type and return as it is
            */
            $data = null; // Data to push to Abeille
            if ($clustId == "0000") { // Basic cluster
                    // 0004: ManufacturerName
                    // 0005: ModelIdentifier
                    // 0010: Location => Used for Profalux

                if (($attrId=="0004") || ($attrId=="0005") || ($attrId=="0010")) {
                    // Assuming $dataType == "42"

                    $trimmedValue = pack('H*', $Attribut);
                    $trimmedValue = str_replace(' ', '', $trimmedValue); //remove all space in names for easier filename handling
                    $trimmedValue = str_replace("\0", '', $trimmedValue); // On enleve les 0x00 comme par exemple le nom des equipements Legrand

                    if ($attrId == "0004") { // 0x0004 ManufacturerName string
                        parserLog('debug', "  ManufacturerName='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."'");
                        $data = $trimmedValue;

                        $this->deviceUpdate($dest, $srcAddr, 'manufacturer', $trimmedValue);
                    } else if ($attrId == "0005") { // 0x0005 ModelIdentifier string
                        $trimmedValue = $this->cleanModelId($trimmedValue);

                        parserLog('debug', "  ModelIdentifier='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."'");
                        $data = $trimmedValue;

                        $this->deviceUpdate($dest, $srcAddr, 'modelIdentifier', $trimmedValue);
                    } else if ($attrId == "0010") { // Location
                        parserLog('debug', "  LocationDescription='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."'");
                        $data = $trimmedValue;

                        $this->deviceUpdate($dest, $srcAddr, 'location', $trimmedValue);
                    }
                }

                // Xiaomi lumi.sensor_86sw1 (Wall 1 Switch sur batterie)
                elseif (($attrId == "FF01") && ($attrSize == "001B")) {
                    parserLog("debug","  Xiaomi proprietary (Wall 1 Switch, Gaz Sensor)" );
                    // Dans le cas du Gaz Sensor, il n'y a pas de batterie alors le decodage est probablement faux.

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat = substr($payload, 80, 2);

                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent($voltage).', Etat=' .$etat);

                    $this->msgToAbeille($dest."/".$srcAddr, '0006',     '01-0000', $etat);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    return; // Nothing more to publish
                }

                // Xiaomi door sensor V2
                elseif (($attrId == "FF01") && ($attrSize == "001D")) {
                    // Assuming $dataType == "42"

                    // $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $voltage = hexdec(substr($Attribut, 2 * 2 + 2, 2).substr($Attribut, 2 * 2, 2));
                    // $etat           = substr($payload, 80, 2);
                    $etat = substr($Attribut, 80 - 24, 2);

                    parserLog('debug', '  Xiaomi proprietary (Door Sensor): Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', State='.$etat);

                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    $this->msgToAbeille($dest."/".$srcAddr, '0006', '01-0000', $etat);
                    return; // Nothing more to publish
                }

                // Xiaomi capteur temperature rond V1 / lumi.sensor_86sw2 (Wall 2 Switches sur batterie)
                elseif (($attrId == "FF01") && ($attrSize == "001F")) {
                    parserLog("debug","  Xiaomi proprietary (Capteur Temperature Rond/Wall 2 Switch)");

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity = hexdec( substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2) );

                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', Temp='.$temperature.', Humidity='.$humidity );

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId,'$this->decoded as Volt-Temperature-Humidity');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    $this->msgToAbeille($dest."/".$srcAddr, '0402', '01-0000', $temperature);
                    $this->msgToAbeille($dest."/".$srcAddr, '0405', '01-0000', $humidity);
                    return; // Nothing more to publish
                }

                // Xiaomi capteur Presence V2
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102; (Attribute report)(Processed->MQTT); SQN: 01; Src Addr : a2e1; End Point : 01; Cluster ID : 0000; Attr ID : ff01; Attr Status : 00; Attr Data Type : 42; Attr Size : 0021; Data byte list : 0121e50B0328150421a80105213300062400000000000A2100006410000B212900
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Presence V2)
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102;Voltage; 3045
                // 01 21 e50B param 1 - uint16 - be5 (3.045V) /24
                // 03 28 15                                   /32
                // 04 21 a801                                 /38
                // 05 21 3300                                 /46
                // 06 24 0000000000                           /54
                // 0A 21 0000 - Param 0xA 10dec - uint16 - 0x0 0dec /68
                // 64 10 00 - parm 0x64 100dec - Boolean - 0      (Presence ?)  /76
                // 0B 21 2900 - Param 0xB 11dec - uint16 - 0x0029 (41dec Lux ?) /82
                elseif (($attrId == 'FF01') && ($attrSize == "0021")) {
                    // Assuming $dataType == "42"
                    parserLog('debug', '  Xiaomi proprietary (Capteur Presence V2)');

                    $voltage = hexdec(substr($payload, 28+2, 2).substr($payload, 28, 2));
                    $lux = hexdec(substr($payload, 86+2, 2).substr($payload, 86, 2));
                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', Lux='.$lux);

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, '$this->decoded as Volt-Temperature-Humidity');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    $this->msgToAbeille($dest."/".$srcAddr, '0400', '01-0000', $lux); // Luminosite
                    return;
                }

                // Xiaomi capteur Inondation
                elseif (($attrId == 'FF01') && ($attrSize == "0022")) {
                    // Assuming DataType=42
                    parserLog('debug', '  Xiaomi proprietary (Capteur d\'inondation)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat = substr($payload, 88, 2);
                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', Etat='.$etat);

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, '$this->decoded as Volt-Temperature-Humidity');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    return;
                }

                // Xiaomi temp/humidity/pressure square sensor
                elseif (($attrId == 'FF01') && ($attrSize == "0025")) {
                    // Assuming $dataType == "42"

                    parserLog('debug', '  Xiaomi proprietary (Temp square sensor)');

                    $voltage        = hexdec(substr($Attribut, 2 * 2 + 2, 2).substr($Attribut, 2 * 2, 2));
                    $temperature    = unpack("s", pack("s", hexdec(substr($Attribut, 21 * 2 + 2, 2).substr($Attribut, 21 * 2, 2))))[1];
                    $humidity       = hexdec(substr($Attribut, 25 * 2 + 2, 2).substr($Attribut, 25 * 2, 2));
                    $pression       = hexdec(substr($Attribut, 29 * 2 + 6, 2).substr($Attribut, 29 * 2 + 4, 2).substr($Attribut, 29 * 2 + 2, 2).substr($Attribut, 29 * 2, 2));

                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', Temp='.$temperature.', Humidity='.$humidity.', Pressure='.$pression);
                    // parserLog('debug', 'ff01/25: Temperature: '  .$temperature);
                    // parserLog('debug', 'ff01/25: Humidity: '     .$humidity);
                    // parserLog('debug', 'ff01/25: Pression: '     .$pression);

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, '$this->decoded as Volt-Temperature-Humidity');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    $this->msgToAbeille($dest."/".$srcAddr, '0402', '01-0000', $temperature);
                    $this->msgToAbeille($dest."/".$srcAddr, '0405', '01-0000', $humidity);
                    return;
                }

                // Xiaomi Smoke Sensor
                elseif (($attrId == 'FF01') && ($attrSize == "0028")) {
                    parserLog('debug', '  Xiaomi proprietary (Smoke Sensor)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, '$this->decoded as Volt');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    return;
                }

                // Xiaomi Vibration
                elseif (($attrId == 'FF01') && ($attrSize == "002E")) {
                    // Assuming $dataType == "42"
                    parserLog('debug', '   Xiaomi proprietary (Vibration)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, '$this->decoded as Volt-Temperature-Humidity');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    return;
                }

                // Xiaomi Wall Plug (Kiwi: ZNCZ02LM, rvitch: )
                elseif (($attrId == "FF01") && (($attrSize == "0031") || ($attrSize == "002B"))) {
                    parserLog('debug', "  Xiaomi proprietary (Wall Plug)");

                    $onOff = hexdec(substr($payload, 24 + 2 * 2, 2));

                    $puissance = unpack('f', pack('H*', substr($payload, 24 + 8 * 2, 8)));
                    $puissanceValue = $puissance[1];

                    $conso = unpack('f', pack('H*', substr($payload, 24 + 14 * 2, 8)));
                    $consoValue = $conso[1];

                    parserLog('debug', '  OnOff='.$onOff.', Puissance='.$puissanceValue.', Consommation='.$consoValue);

                    // $this->msgToAbeille($srcAddr,$clustId,$attrId,'$this->decoded as OnOff-Puissance-Conso');
                    $this->msgToAbeille($dest."/".$srcAddr, '0006',  '-01-0000',        $onOff);
                    $this->msgToAbeille($dest."/".$srcAddr, 'tbd',   '--puissance--',   $puissanceValue);
                    $this->msgToAbeille($dest."/".$srcAddr, 'tbd',   '--conso--',       $consoValue);
                    return;
                }

                // Xiaomi Double Relay (ref ?)
                elseif (($attrId == "FF01") && ($attrSize == "0044")) {
                    $FF01 = $this->decodeFF01(substr($payload, 24, strlen($payload) - 24 - 2));
                    parserLog('debug', "  Xiaomi proprietary (Double relay)");
                    parserLog('debug', "  ".json_encode($FF01));

                    $this->msgToAbeille($dest."/".$srcAddr, '0006', '01-0000',   $FF01["Etat SW 1 Binaire"]["valueConverted"]);
                    $this->msgToAbeille($dest."/".$srcAddr, '0006', '02-0000',   $FF01["Etat SW 2 Binaire"]["valueConverted"]);
                    $this->msgToAbeille($dest."/".$srcAddr, '000C', '01-0055',   $FF01["Puissance"]["valueConverted"]);
                    $this->msgToAbeille($dest."/".$srcAddr, 'tbd',  '--conso--', $FF01["Consommation"]["valueConverted"]);
                    return;
                }

                // Xiaomi Presence Infrarouge IR V1 / Bouton V1 Rond
                elseif (($attrId == "FF02")) {
                    // Assuming $dataType == "42"
                    parserLog("debug","  Xiaomi proprietary (IR/button/door V1)" );

                    $voltage = hexdec(substr($payload, 24 +  8, 2).substr($payload, 24 + 6, 2));

                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    return;
                }
            } // End cluster 0000

            else if ($clustId == "0001") { // Power configuration cluster
                if ($attrId == "0020") { // BatteryVoltage
                    $batteryVoltage = substr($Attribut, 0, 2);
                    $volt = hexdec($batteryVoltage) / 10;
                    parserLog('debug', '  BatteryVoltage='.$batteryVoltage.' => '.$volt.'V');
                }

                else if ($attrId == "0021") { // BatteryPercentageRemaining
                    $BatteryPercent = substr($Attribut, 0, 2);
                    $percent = hexdec($BatteryPercent) / 2;
                    parserLog('debug', '  BatteryPercent='.$BatteryPercent.' => '.$percent.'%');
                    $data = $percent;
                }
            } // End cluster 0001/power configuration

            else if ($clustId == "0006") { // On/Off cluster
                if ($attrId == "0000") { // OnOff
                    $OnOff = substr($Attribut, 0, 2);
                    parserLog('debug', '  OnOff='.$OnOff);
                }
            } // End cluster 0006/onoff

            else if ($clustId == "0008") { // Level control cluster
                if ($attrId == "0000") { // CurrentLevel
                    $CurrentLevel = substr($Attribut, 0, 2);
                    parserLog('debug', '  CurrentLevel='.$CurrentLevel);
                }
            } // End cluster 0006/onoff

            else if ($clustId == "000C") { // Analog input cluster
                if ($attrId == "0055") {
                    // assuming $dataType == "39"

                    if ($ep=="01") {
                        // Remontée puissance (instantannée) relay double switch 1
                        // On va envoyer ca sur la meme variable que le champ ff01
                        $hexNumber = substr($payload, 24, 8);
                        $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                        $bin = pack('H*', $hexNumberOrder );
                        $data = unpack("f", $bin )[1];

                        $puissanceValue = $data;
                        // $this->msgToAbeille( $srcAddr, 'tbd',     '--puissance--',    $puissanceValue);

                        // Relay Double
                        $this->msgToAbeille($dest."/".$srcAddr, '000C',     '01-0055',    $puissanceValue);
                    }
                    if (($ep=="02") || ($ep=="15")) {
                        // Remontée puissance (instantannée) de la prise xiaomi et relay double switch 2
                        // On va envoyer ca sur la meme variable que le champ ff01
                        $hexNumber = substr($payload, 24, 8);
                        $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                        $bin = pack('H*', $hexNumberOrder );
                        $data = unpack("f", $bin )[1];

                        $puissanceValue = $data;
                        // Relay Double - Prise Xiaomi
                        $this->msgToAbeille($dest."/".$srcAddr, $clustId, $ep.'-'.$attrId, $puissanceValue);
                    }
                    if ($ep=="03") {
                        // Example Cube Xiaomi
                        // Sniffer dit Single Precision Floating Point
                        // b9 1e 38 c2 -> -46,03
                        // $data = hexdec(substr($payload, 24, 4));
                        // $data = unpack("s", pack("s", hexdec(substr($payload, 24, 4))))[1];
                        $hexNumber = substr($payload, 24, 8);
                        $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                        $bin = pack('H*', $hexNumberOrder );
                        $data = unpack("f", $bin )[1];
                    }
                }
            } // End cluster 000C

            else if ($clustId == "0400") { // Illuminance Measurement cluster
                if ($attrId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4);
                    $illuminance = pow(10, (hexdec($MeasuredValue) - 1) / 10000);
                    // TODO Tcharp38: Check if correct formula and what returned to Abeille
                    parserLog('debug', '  Illuminance, MeasuredValue='.$MeasuredValue.' => '.$illuminance.'Lx');
                }
            } // End cluster 0400

            else if ($clustId == "0402") { // Temperature Measurement cluster
                if ($attrId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4); // int16
                    $temp = $this->decodeDataType($MeasuredValue, $dataType, false, $dataSize, $hexString) / 100;
                    parserLog('debug', '  Temp, MeasuredValue='.$MeasuredValue.' => '.$temp.'C');
                }
            } // End cluster 0402

            else if ($clustId == "0403") { // Pressure Measurement cluster
                if ($attrId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4); // int16, MeasuredValue = 10 x Pressure
                    $pressure = $this->decodeDataType($Attribut, $dataType, false, $dataSize, $hexString) / 10;
                    parserLog('debug', '  Pressure, MeasuredValue='.$MeasuredValue.' => '.$pressure.'kPa');
                }
            } // End cluster 0403

            else if ($clustId == "0405") { // Relative Humidity cluster
                if ($attrId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4);
                    $humidity = hexdec($MeasuredValue) / 100;
                    parserLog('debug', '  Humidity, MeasuredValue='.$MeasuredValue.' => '.$humidity.'%');
                }
            } // End cluster 0405

            else if ($clustId == "0406") { // Occupancy Sensing cluster
                if ($attrId == "0000") { // Occupancy
                    $Occupancy = substr($Attribut, 0, 2);
                    // Bit 0 specifies the sensed occupancy as follows: 1 = occupied, 0 = unoccupied.
                    parserLog('debug', '  Occupancy='.$Occupancy);
                }
            } // End cluster 0406

            /* If $data is set it means message already treated before */
            if (isset($data)) {
                /* Send to Abeille */
                $this->msgToAbeille($dest."/".$srcAddr, $clustId."-".$ep, $attrId, $data);

                /* Send to client page if connection opened */
                $toCli = array(
                    'src' => 'parser',
                    'type' => 'attributeReport', // 8100 or 8102
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'clustId' => $clustId,
                    'attrId' => $attrId,
                    'status' => "00",
                    'value' => $data
                );
                $this->msgToClient($toCli);
                return;
            }

            /* Tcharp38
               Code hereafter to be migrated above to be sorted by clustId/attribId but need to be sure of clustId first.
               Only remaining code should be dataType to value conversion for "standard" values that do not require
               extra compute (value received is the real value and for ex no div required).
             */

            // 0x00 Null
            // 0x10 boolean                 -> hexdec
            // 0x18 8-bit bitmap
            // 0x20 Unsigned 8-bit  integer uint8
            // 0x21 Unsigned 16-bit integer uint16
            // 0x22 Unsigned 24-bit integer uint24
            // 0x23 Unsigned 32-bit integer uint32
            // 0x24 Unsigned 40-bit integer uint40
            // 0x25 Unsigned 48-bit integer uint48
            // 0x26 Unsigned 56-bit integer uint56
            // 0x27 Unsigned 64-bit integer uint64
            // 0x28 Signed 8-bit  integer int8
            // 0x29 Signed 16-bit integer int16
            // 0x2a Signed 24-bit integer int24
            // 0x2b Signed 32-bit integer int32
            // 0x2c Signed 40-bit integer int40
            // 0x2d Signed 48-bit integer int48
            // 0x2e Signed 56-bit integer int56
            // 0x2f Signed 64-bit integer int64
            // 0x30 Enumeration : 8bit
            // 0x42 string                  -> hex2bin

            if ($dataType == "10") {
                $data = hexdec(substr($payload, 24, 2));
            }

            else if ($dataType == "18") {
                $data = substr($payload, 24, 2);
            }

            // Exemple Heiman Smoke Sensor Attribut 0002 sur cluster 0500
            else if ($dataType == "19") {
                $data = substr($payload, 24, 4);
            }

            // Tcharp38: uintX decode is now supported by decodeDataType()
            else if ((hexdec($dataType) >= 0x20) && (hexdec($dataType) <= 0x27)) {
                $data = $this->decodeDataType(substr($payload, 24), $dataType, false, $dataSize, $hexString);
            }

            else if ($dataType == "28") {
                // $data = hexdec(substr($payload, 24, 2));
                $in = substr($payload, 24, 2);
                if ( hexdec($in)>127 ) { $raw = "FF".$in ; } else  { $raw = "00".$in; }

                $data = unpack("s", pack("s", hexdec($raw)))[1];
            }

            // Example Temperature d un Xiaomi Carre
            // Sniffer dit Signed 16bit integer
            else if ($dataType == "29") { // int16
                $data = unpack("s", pack("s", hexdec(substr($Attribut, 0, 4))))[1];
            }

            else if ($dataType == "30") {
                // $data = hexdec(substr($payload, 24, 4));
                $data = substr($payload, 24, 4);
            }

            else if ($dataType == "42") {
                // ------------------------------------------------------- Xiaomi ----------------------------------------------------------
                // Xiaomi Bouton V2 Carré
                // elseif (($attrId == "FF01") && ($attrSize == "001A")) {
                if (($attrId == "FF01") && ($attrSize == "001A")) {
                    parserLog("debug", "  Champ proprietaire Xiaomi (Bouton carré)" );

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    parserLog('debug', '  Voltage='.$voltage.' Voltage%='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                }

                // // Xiaomi capteur Presence V2
                // // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102; (Attribute report)(Processed->MQTT); SQN: 01; Src Addr : a2e1; End Point : 01; Cluster ID : 0000; Attr ID : ff01; Attr Status : 00; Attr Data Type : 42; Attr Size : 0021; Data byte list : 0121e50B0328150421a80105213300062400000000000A2100006410000B212900
                // // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Presence V2)
                // // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102;Voltage; 3045
                // // 01 21 e50B param 1 - uint16 - be5 (3.045V) /24
                // // 03 28 15                                   /32
                // // 04 21 a801                                 /38
                // // 05 21 3300                                 /46
                // // 06 24 0000000000                           /54
                // // 0A 21 0000 - Param 0xA 10dec - uint16 - 0x0 0dec /68
                // // 64 10 00 - parm 0x64 100dec - Boolean - 0      (Presence ?)  /76
                // // 0B 21 2900 - Param 0xB 11dec - uint16 - 0x0029 (41dec Lux ?) /82

                // elseif (($attrId == 'FF01') && ($attrSize == "0021")) {
                //     parserLog('debug', '  Champ proprietaire Xiaomi (Capteur Presence V2)');

                //     $voltage        = hexdec(substr($payload, 28+2, 2).substr($payload, 28, 2));
                //     $lux            = hexdec(substr($payload, 86+2, 2).substr($payload, 86, 2));
                //     parserLog('debug', '  Volt=' .$voltage.', Volt%='.$this->volt2pourcent($voltage).', Lux='.$lux);

                //     $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId,'$this->decoded as Volt-Temperature-Humidity');
                //     $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                //     $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                //     $this->msgToAbeille($dest."/".$srcAddr, '0400', '01-0000', $lux); // Luminosite

                //     // $this->msgToAbeille( $srcAddr, '0402', '0000', $temperature);
                //     // $this->msgToAbeille( $srcAddr, '0405', '0000', $humidity);
                //     // $this->msgToAbeille( $srcAddr, '0403', '0010', $pression / 10);
                //     // $this->msgToAbeille( $srcAddr, '0403', '0000', $pression / 100);
                // }

                // Xiaomi bouton Aqara Wireless Switch V3 #712 (https://github.com/KiwiHC16/Abeille/issues/712)
                elseif (($attrId == 'FF01') && ($attrSize == "0026")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Bouton Aqara Wireless Switch V3)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Volt=' .$voltage.', Volt%='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId,'$this->decoded as Volt');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                }

                // Xiaomi Cube
                // Xiaomi capteur Inondation
                elseif (($attrId == 'FF01') && ($attrSize == "002A")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Cube)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId,'$this->decoded as Volt-Temperature-Humidity');
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));

                    // $this->msgToAbeille( $srcAddr, '0402', '0000', $temperature);
                    // $this->msgToAbeille( $srcAddr, '0405', '0000', $humidity);
                    // $this->msgToAbeille( $srcAddr, '0403', '0010', $pression / 10);
                    // $this->msgToAbeille( $srcAddr, '0403', '0000', $pression / 100);
                }

                // Xiaomi Capteur Presence
                // Je ne vois pas ce message pour ce cateur et sur appui lateral il n envoie rien
                // Je mets un Attribut Size a XX en attendant. Le code et la il reste juste a trouver la taille de l attribut si il est envoyé.
                elseif (($attrId == "FF01") && ($attrSize == "00XX")) {
                    parserLog("debug","  Champ proprietaire Xiaomi (Bouton Carre)" );

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$srcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                }

                // ne traite pas les FF01 inconnus
                elseif ($attrId == "FF01") {
                    parserLog("debug", "  Ignored. Unknown attribut FF01");
                    return;
                }

                // ------------------------------------------------------- Philips ----------------------------------------------------------
                // Bouton Telecommande Philips Hue RWL021
                elseif (($clustId == "FC00")) {

                    $buttonEventTexte = array (
                                               '00' => 'appui',
                                               '01' => 'appui maintenu',
                                               '02' => 'relâche sur appui court',
                                               '03' => 'relâche sur appui long',
                                               );
                    // parserLog("debug","  Champ proprietaire Philips Hue, decodons le et envoyons a Abeille les informations ->".pack('H*', substr($payload, 24+2, (strlen($payload) - 24 - 2)) )."<-" );
                    $button = $attrId;
                    $buttonEvent = substr($payload, 24 + 2, 2);
                    $buttonDuree = hexdec(substr($payload, 24 + 6, 2));
                    parserLog("debug", "  Champ proprietaire Philips Hue: Bouton=".$button.", Event=".$buttonEvent.", EventText=".$buttonEventTexte[$buttonEvent]." et duree: ".$buttonDuree);

                    $this->msgToAbeille($dest."/".$srcAddr, $clustId."-".$ep, $attrId."-Event", $buttonEvent);
                    $this->msgToAbeille($dest."/".$srcAddr, $clustId."-".$ep, $attrId."-Duree", $buttonDuree);
                }

                // ------------------------------------------------------- Tous les autres cas ----------------------------------------------------------
                else {
                    $data = pack('H*', $Attribut);
                }
            }

            else if ($dataType == "48") { // Array
                // Tcharp38: Don't know how to handle it.
                parserLog('debug', "  WARNING: Don't know how to decode 'array' data type.");
                $data = "00"; // Fake value
            }

            /* Note: If $data is not set, then nothing to send to Abeille. This might be because data type is unsupported */
            else if (!isset($data)) {
                $data = $this->decodeDataType(substr($payload, 24), $dataType, false, $dataSize, $hexString);
            }

            if (isset($data)) {
                // $this->msgToAbeille($dest."/".$srcAddr, $clustId."-".$ep, $attrId, $data);
                /* Forwarding unsupported atttribute to Abeille */
                $toAbeille = array(
                    'src' => 'parser',
                    'type' => 'attributeReport',
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'name' => $clustId.'-'.$ep.'-'.$attrId,
                    'value' => $data,
                    'time' => time(),
                    'lqi' => $lqi
                );
                $this->msgToAbeille2($toAbeille);

                /* Send to client if connection opened */
                $toCli = array(
                    'src' => 'parser',
                    'type' => 'attributeReport', // 8100 or 8102
                    'net' => $dest,
                    'addr' => $srcAddr,
                    'ep' => $ep,
                    'clustId' => $clustId,
                    'attrId' => $attrId,
                    'status' => "00",
                    'value' => $data
                );
                $this->msgToClient($toCli);
            }
        }

        /* 0x8100/Read individual Attribute Response */
        function decode8100($dest, $payload, $lqi)
        {
            $this->decode8100_8102("8100", $dest, $payload, $lqi);
        }

        /* Default response */
        function decode8101($dest, $payload, $lqi)
        {
            $sqn = substr($payload, 0, 2);
            $clustId = substr($payload, 4, 4);
            $status = substr($payload, 10, 2);

            // Tcharp38: Decoding it inside 8002 to get source address.
            parserLog('debug', $dest.', Type=8101/Default response'
                            .', SQN='.$sqn
                            .', EP='.substr($payload, 2, 2)
                            .', ClustId='.$clustId.'/'.zgGetCluster($clustId)
                            .', Cmd='.substr($payload, 8, 2)
                            .', Status='.$status
                            .' => Handled by decode8002');
        }

        /* Attribute report */
        function decode8102($dest, $payload, $lqi)
        {
            $this->decode8100_8102("8102", $dest, $payload, $lqi);
        }

        function decode8110($dest, $payload, $lqi)
        {
            parserLog('debug', $dest.', Type=8110/Write attribute response'
                        //   .': Dest='.$dest
                        //   .', Level=0x'.substr($payload, 0, 2)
                        //   .', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))
                         );
        }

        function decode8120($dest, $payload, $lqi)
        {
            // <Sequence number: uint8_t>
            // <Src address : uint16_t>
            // <Endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // WARNING: Only if payload size > 7: <Attribute Enum: uint16_t> (add in v3.0f)
            // <Status: uint8_t>
            $payloadLen = strlen($payload) / 2; // Size in bytes

            $sqn = substr($payload, 0, 2);
            $addr = substr($payload, 2, 4);
            $ep = substr($payload, 6, 2);
            $clustId = substr($payload, 8, 4);
            if ($payloadLen == 7) {
                $status = substr($payload, 12, 2);
            } else {
                $attrId = substr($payload, 12, 4);
                $status = substr($payload, 16, 2);
            }

            if ($payloadLen == 7) { // E_ZCL_CBET_REPORT_ATTRIBUTES_CONFIGURE_RESPONSE
                $msg = '8120/Configure reporting response'
               .', SQN='.$sqn
               .', Addr='.$addr
               .', EP='.$ep
               .', ClustId='.$clustId
               .', Status='.$status;
            } else {
                $msg = '8120/Individual configure reporting response'
               .', SQN='.$sqn
               .', Addr='.$addr
               .', EP='.$ep
               .', ClustId='.$clustId
               .', AttrId='.$attrId
               .', Status='.$status;
            }
            parserLog('debug', $dest.', Type='.$msg);
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $addr))
                monMsgFromZigate($msg); // Send message to monitor

            // Tcharp38: what for ? $attrId does not exist in all cases so what to report ?
            // $data = date("Y-m-d H:i:s")." Attribut: ".$attrId." Status (00: Ok, <>0: Error): ".$status;
            // $this->msgToAbeille($dest."/0000", "Network", "Report", $data);
        }

        // 8122/Read Reporting Configuration
        function decode8122($dest, $payload, $lqi)
        {
            // <Sequence number: uint8_t>
            // <Src address : uint16_t>
            // <Endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <Status: uint8_t>
            // Attribute type : uint8_t
            // Attribute id : uint16_t
            // Min interval : uint16_t
            // Max interval : uint16_t
            $sqn = substr($payload, 0, 2);
            $srcAddr = substr($payload, 2, 4);
            $ep = substr($payload, 6, 2);
            $clustId = substr($payload, 8, 4);
            $status = substr($payload, 12, 2);
            if ($status == "00") {
                $attrType = substr($payload, 14, 2);
                $attrId = substr($payload, 16, 4);
                // Tcharp38: min & max seem inverted compared to NXP spec
                $maxInterval = hexdec(substr($payload, 20, 4));
                $minInterval = hexdec(substr($payload, 24, 4));
            }

            $decoded = '8122/Read reporting config'
                .', SQN='.$sqn
                .', Addr='.$srcAddr
                .', EP='.$ep
                .', ClustId='.$clustId
                .', Status='.$status.'/'.zbGetZCLStatus($status);
            parserLog('debug', $dest.', Type='.$decoded);
            if ($status == "00")
                parserLog('debug', '  AttrType='.$attrType.', AttrId='.$attrId.', MinInterval='.$minInterval.', MaxInterval='.$maxInterval);
        }

        function decode8140($dest, $payload, $lqi)
        {
            // Some changes in this message so read: https://github.com/fairecasoimeme/ZiGate/pull/90
            // https://zigate.fr/documentation/commandes-zigate/
            // Obj-> ZiGate	0x8140	Attribute Discovery response
            // <complete: uint8_t>
            // <attribute type: uint8_t>
            // <attribute id: uint16_t>
            // <Src Addr: uint16_t> (added only from 3.0f version)
            // <Src EndPoint: uint8_t> (added only from 3.0f version)
            // <Cluster id: uint16_t> (added only from 3.0f version)

            // Abeille Serial Example
            //                                     Cmd  Len  CRC Co Type Attr   Addr EP Clus LQI
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 01  01 20   0000   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 00  01 20   0001   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 03  01 20   0002   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 02  01 20   0003   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 67  01 42   0004   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 66  01 42   0005   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 65  01 42   0006   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 16  01 30   0007   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 73  01 42   0010   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 23  01 42   4000   1B53 01 0000 A2"
            // [2021-01-27 17:48:06][debug] Reçu: "8140 000A 02  01 21   FFFD   1B53 01 0000 A2"

            // Payload
            // Co Type Attr Addr EP Clus
            // 01 20   0000 1B53 01 0000
            // Co = complete

            $completed  = substr( $payload, 0, 2);
            $type       = substr( $payload, 2, 2);
            $Attr       = substr( $payload, 4, 4);
            $Addr       = substr( $payload, 8, 4);
            $EP         = substr( $payload,12, 2);
            $Cluster    = substr( $payload,14, 4);

            $msgDecoded = '8140/Attribute discovery response'
               .', Completed='.$completed
               .', AttrType='.$type
               .', AttrId='.$Attr
               .', EP='.$EP
               .', ClustId='.$Cluster;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8140");
            /* Tcharp38: Treated by decode8002() */
            parserLog('debug', '  Handled by decode8002', "8140");
        }

        // Codé sur la base des messages Xiaomi Inondation
        function decode8401($dest, $payload, $lqi)
        {
            // <sequence number: uint8_t>
            // <endpoint : uint8_t>
            // <cluster id: uint16_t>
            // <src address mode: uint8_t>
            // <src address: uint64_t  or uint16_t based on address mode>
            // <zone status: uint16_t>
            // <extended status: uint8_t>
            // <zone id : uint8_t>
            // <delay: data each element uint16_t>

            $EP         = substr($payload, 2, 2);
            $clustId  = substr($payload, 4, 4);
            $srcAddr    = substr($payload,10, 4); // Assuming short mode

            $msgDecoded = '8401/IAS zone status change notification'
               .', SQN='.substr($payload, 0, 2)
               .', EP='.$EP
               .', ClustId='.$clustId
               .', SrcAddrMode='.substr($payload, 8, 2)
               .', SrcAddr='.$srcAddr
               .', ZoneStatus='.substr($payload,14, 4)
               .', ExtStatus='.substr($payload,18, 2)
               .', ZoneId='.substr($payload,20, 2)
               .', Delay='.substr($payload,22, 4);
            parserLog('debug', $dest.', Type='.$msgDecoded);
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $srcAddr))
                monMsgFromZigate($msg); // Send message to monitor

            $this->whoTalked[] = $dest.'/'.$srcAddr;

            // On transmettre l info sur Cluster 0500 et Cmd: 0000 (Jusqu'a present on etait sur ClusterId-AttributeId, ici ClusterId-CommandId)
            $attrId = "0000";
            $data       = substr($payload,14, 4);
            $this->msgToAbeille($dest."/".$srcAddr, $clustId, $EP.'-'.$attrId, $data);
        }

        /**
         * 0x8701/Router Discovery Confirm -  Warning: potential swap between statuses.
         * This method process ????
         *  Will first decode it.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Does return anything as all action are triggered by sending messages in queues
         */
        function decode8701($dest, $payload, $lqi)
        {
            // NWK Code Table Chap 10.2.3 from JN-UG-3113
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            global $allErrorCode;

            $nwkStatus = substr($payload, 0, 2);
            $status = substr($payload, 2, 2);
            $Addr = substr($payload, 4, 4);

            $msg = '8701/Route discovery confirm'
                   .', MACStatus='.$status.' ('.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
                   .', NwkStatus='.$nwkStatus.' ('.$allErrorCode[$nwkStatus][0].'->'.$allErrorCode[$nwkStatus][1].')'
                   .', Addr='.$Addr;
            parserLog('debug', $dest.', Type='.$msg, "8701");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $Addr))
                monMsgFromZigate($msg); // Send message to monitor
        }

        /**
         * 8702/APS data confirm fail
         * This method process ????
         *  Will first decode it.
         *  Send the info to Ruche
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $lqi
         *
         * @return          Does return anything as all action are triggered by sending messages in queues
         */
        function decode8702($dest, $payload, $lqi)
        {
            global $allErrorCode;

            // <status: uint8_t>
            // <src endpoint: uint8_t>
            // <dst endpoint: uint8_t>
            // <dst address mode: uint8_t>
            // <destination address: uint64_t>
            // <seq number: uint8_t>
            $status     = substr($payload, 0, 2);
            $srcEp      = substr($payload, 2, 2);
            $DestEP     = substr($payload, 4, 2);
            $DestMode   = substr($payload, 6, 2);
            $DestAddr   = substr($payload, 8, 4);
            $SQN        = substr($payload,12, 2);

            $msgDecoded = '8702/APS data confirm fail'
                //.', Status='.$status.'/'.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
               .', Status='.$status.'/'.$allErrorCode[$status][0]
               .', SrcEP='.$srcEp
               .', DestEP='.$DestEP
               .', AddrMode='.$DestMode
               .', Addr='.$DestAddr
               .', SQN='.$SQN;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8702");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $DestAddr))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            // // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            // $srcAddr    = "Ruche";
            // $clustId  = "Zigate";
            // $attrId = "8702";
            // $data       = substr($payload, 8, 4);

            // // if ( Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' ) ) $name = Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' )->getHumanName(true);
            // // message::add("Abeille","L'équipement ".$name." (".$data.") ne peut être joint." );

            // $this->msgToAbeille($dest."/".$srcAddr, $clustId, $attrId, $data);

            if ( Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' )) {
                $eq = Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' );
                parserLog('debug', '  NO ACK for '.$eq->getHumanName().". APS_ACK set to 0", "8702");
                $eq->setStatus('APS_ACK', '0');
                // parserLog('debug', $dest.', Type=8702/APS Data Confirm Fail status: '.$eq->getStatus('APS_ACK'), "8702");
            }
        }

        function decode8806($dest, $payload, $lqi)
        {
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

            parserLog('debug', $dest.', Type=8806/Set TX power answer'
                            .', Power='               .$payload
                             );
            $data       = substr($payload, 0, 2);

            // On transmettre l info sur la ruche
            $this->msgToAbeille($dest."/0000", "Zigate", "Power", $data);
        }

        function decode8807($dest, $payload, $lqi)
        {
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

            parserLog('debug', $dest.', Type=8807/Get TX power'
                            .', Power='.$power
                        );

            $this->msgToAbeille($dest."/0000", "Zigate", "Power", $power);
        }

        /* Extended error */
        function decode9999($dest, $payload, $lqi)
        {
            /* FW >= 3.1e
               Extended Status: uint8_t */
            $ExtStatus = substr($payload, 0, 2);

            $decoded = '9999/Extended error'
                .', ExtStatus='.$ExtStatus;
            parserLog('debug', $dest.', Type='.$decoded);
        }

        // ***********************************************************************************************
        // Gestion des annonces
        // ***********************************************************************************************

        // /**
        //  * getNE()
        //  * This method send all command needed to the NE to get its state.
        //  *
        //  * @param $short    Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
        //  * @return Doesn't return anything as all action are triggered by sending messages in queues
        //  */
        // function getNE($logicId)
        // {
        //     list($dest, $addr) = explode("/", $logicId);
        //     $getStates = array('getEtat', 'getLevel', 'getColorX', 'getColorY', 'getManufacturerName', 'getSWBuild', 'get Battery');

        //     $eqLogic = Abeille::byLogicalId($logicId, 'Abeille');
        //     if ( $eqLogic ) {
        //         $arr = array(1, 2);
        //         foreach ($arr as &$value) {
        //             foreach ($getStates as $getState) {
        //                 $cmd = $eqLogic->getCmd('action', $getState);
        //                 if ( $cmd ) {
        //                     parserLog('debug', 'getNE('.$logicId.'): '.$getState, "getNE");
        //                     // $cmd->execCmd();
        //                     logMessage('debug', "  cmdLogicId=".$cmd->getLogicalId());
        //                     $topic = $cmd->getConfiguration('topic');
        //                     $topic = AbeilleCmd::updateField($dest, $cmd, $topic);
        //                     $request = $cmd->getConfiguration('request');
        //                     $request = AbeilleCmd::updateField($dest, $cmd, $request);
        //                     logMessage('debug', "  topic=".$topic.", request=".$request);
        //                     $this->msgToCmd("Cmd".$dest."/".$addr."/".$topic, $request);
        //                 }
        //             }
        //         }
        //     }
        // }

        // /**
        //  * execAtCreationCmdForOneNE()
        //  * - Execute all commands with 'execAtCreation' flag set
        //  *
        //  * @param logicalId of the device
        //  * @return none
        //  */
        // function execAtCreationCmdForOneNE($logicalId) {
        //     parserLog('debug', 'execAtCreationCmdForOneNE('.$logicalId.')');
        //     $eqLogic = Abeille::byLogicalId($logicalId,'Abeille');
        //     if (!is_object($eqLogic)) {
        //         logMessage('debug', "  Unkown EQ '".$logicalId."'");
        //         return;
        //     }
        //     list($dest, $addr) = explode("/", $logicalId);
        //     logMessage('debug', "  dest=".$dest.", addr=".$addr);
        //     // echo $dest.' - '.$addr."\n";
        //     $cmds = AbeilleCmd::searchConfigurationEqLogic($eqLogic->getId(), 'execAtCreation', 'action');
        //     foreach ( $cmds as $key => $cmd ) {
        //         // $topic = $cmd->getLogicalId();
        //         logMessage('debug', "  cmdLogicId=".$cmd->getLogicalId());
        //         $topic = $cmd->getConfiguration('topic');
        //         $topic = AbeilleCmd::updateField($dest, $cmd, $topic);
        //         $request = $cmd->getConfiguration('request');
        //         $request = AbeilleCmd::updateField($dest, $cmd, $request);
        //         // Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInclusion, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$topic."&time=".(time()+$cmd->getConfiguration('execAtCreationDelay')), $request );
        //         logMessage('debug', "  topic=".$topic.", request=".$request);
        //         $this->msgToCmd("Cmd".$dest."/".$addr."/".$topic, $request);
        //     }
        // }

        // /**
        //  * configureNE
        //  * This method send all command needed to the NE to configure it.
        //  *
        //  * @param $short    Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
        //  *
        //  * @return          Doesn't return anything as all action are triggered by sending messages in queues
        //  */
        // function configureNE($eqLogicId) {
        //     // parserLog('debug', 'configureNE('.$eqLogicId.')');
        //     self::execAtCreationCmdForOneNE($eqLogicId);
        // }

        /**
         * WHile processing AbeilleParser can schedule action by adding action in the queue like for exemple:
         * $this->actionQueue[] = array( 'when'=>time()+5, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$IEEE );
         *
         * @param $this->actionQueue
         * @return none
         */
        function processActionQueue() {
            if ( !($this->actionQueue) ) return;
            if ( count($this->actionQueue) < 1 ) return;

            foreach ( $this->actionQueue as $key=>$action ) {
                if ( $action['when'] < time()) {
                    if (!method_exists($this, $action['what'])) {
                        parserLog('debug', "processActionQueue(): Unknown action '".json_encode($action)."'", 'processActionQueue');
                        continue;
                    }
                    parserLog('debug', "processActionQueue(): action '".json_encode($action)."'", 'processActionQueue');
                    $fct = $action['what'];
                    if ( isset($action['parm0'])) {
                        $this->$fct($action['parm0'], $action['parm1'], $action['parm2'], $action['parm3']);
                    } else {
                        $this->$fct($action['addr']);
                    }
                    unset($this->actionQueue[$key]);
                }
            }
        }

        /**
         * With device on battery we have to wait for them to wake up before sending them command:
         * $this->wakeUpQueue[] = array( 'which'=>logicalId, 'what'=>'msgToAbeille', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$IEEE );
         *
         * @param logicalId
         * @param $this->wakeUpQueue
         * @return none
         */
        function processWakeUpQueue() {
            if ( !($this->wakeUpQueue)) {
                unset($this->whoTalked);
                return;
            }
            if ( count($this->wakeUpQueue)<1 ) {
                unset($this->whoTalked);
                return;
            }
            if ( !($this->whoTalked) ) return;
            if ( count($this->whoTalked) < 1 ) return;

            parserLog('debug', 'processWakeUpQueue(): ------------------------------>');

            foreach( $this->whoTalked as $keyWho=>$who ) {
                parserLog('debug', 'processWakeUpQueue(): '.$who.' talked');
                foreach ( $this->wakeUpQueue as $keyWakeUp=>$action ) {
                    if ( $action['which'] == $who ) {
                        if ( method_exists($this, $action['what'])) {
                            parserLog('debug', 'processWakeUpQueue(): action: '.json_encode($action), 'processWakeUpQueue');
                            $fct = $action['what'];
                            if ( isset($action['parm0'])) {
                                $this->$fct($action['parm0'],$action['parm1'],$action['parm2'],$action['parm3']);
                            } else {
                                $this->$fct($action['addr']);
                            }
                            unset($this->wakeUpQueue[$keyWakeUp]);
                        }
                    }
                    unset($this->whoTalked[$keyWho]);
                }
            }

            parserLog('debug', 'processWakeUpQueue(): <------------------------------');
        }

    } // class AbeilleParser
?>
