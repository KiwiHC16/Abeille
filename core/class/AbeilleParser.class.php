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

            $this->queueKeyParserToAbeille      = msg_get_queue(queueKeyParserToAbeille);
            $this->queueKeyParserToAbeille2     = msg_get_queue(queueKeyParserToAbeille2);
            $this->queueKeyParserToCmd          = msg_get_queue(queueKeyParserToCmd);
            $this->queueKeyParserToCmdSemaphore = msg_get_queue(queueKeyParserToCmdSemaphore);
            $this->queueKeyParserToLQI          = msg_get_queue(queueKeyParserToLQI);

            /* Monitor updates */
            if (isset($GLOBALS["dbgMonitorAddr"])) {
                /* Extracting IEEE address from '<short>-<ieee>' format. */
                $GLOBALS["dbgMonitorAddrExt"] = substr($GLOBALS["dbgMonitorAddr"], 5);
            }
        }

        // $SrcAddr = dest / shortaddr
        // Tcharp38: This function is obsolete. It is smoothly replaced by msgToAbeille2() with new msg format
        function msgToAbeille($SrcAddr, $ClusterId, $AttributId, $data)
        {
            // dest / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = true;

            $msgAbeille->message = array(
                                    'topic' => $SrcAddr."/".$ClusterId."-".$AttributId,
                                    'payload' => $data,
                                     );
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == false) {
                parserLog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                parserLog("error", "  Message=".json_encode($msgAbeille));
            }

            $msgAbeille->message = array('topic' => $SrcAddr."/Time-TimeStamp", 'payload' => time());
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == false) {
                parserLog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                parserLog("error", "  Message=".json_encode($msgAbeille));
            }

            $msgAbeille->message = array('topic' => $SrcAddr."/Time-Time", 'payload' => date("Y-m-d H:i:s"));
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == false) {
                parserLog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                parserLog("error", "  Message=".json_encode($msgAbeille));
            }
        }

        // Tcharp38: This function is obsolete is smoothly replaced by msgToAbeille2() with new msg format
        function msgToAbeilleFct($SrcAddr, $fct, $data)
        {
            // $SrcAddr = dest / shortaddr
            // dest / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $msgAbeille->message = array( 'topic' => $SrcAddr."/".$fct, 'payload' => $data, );

            $errorcode = 0;
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false, $errorcode)) {
                // parserLog("debug","(fct msgToAbeilleFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
            }
            else {
                parserLog("debug", "(fct msgToAbeilleFct) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
            }
        }

        // Tcharp38: This function is obsolete is smoothly replaced by msgToAbeille2() with new msg format
        function msgToAbeilleCmdFct( $fct, $data)
        {
             // Abeille / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $msgAbeille->message = array( 'topic' => $fct, 'payload' => $data, );

            $errorcode = 0;
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false, $errorcode)) {
                // parserLog("debug","(fct msgToAbeilleFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                parserLog("debug","(fct msgToAbeilleFct) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
            }
        }

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

            $msgAbeille = new MsgAbeille;
            $msgAbeille->message = array( 'topic' => $topic, 'payload' => $payload );

            $errorcode = 0;
            if (msg_send($this->queueKeyParserToCmd, 1, $msgAbeille, true, false, $errorcode) == false) {
                parserLog("debug", "msgToCmd() ERROR: Can't write to 'queueKeyParserToCmd', error=".$errorcode);
            }
        }

        /*
        function msgToAbeilleLQI( $Addr, $Index, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => "LQI/".$Addr."/".$Index, 'payload' => $data, $errorcode);

            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // parserLog("debug","(fct msgToAbeilleLQI ParserToAbeille) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                parserLog("debug","(fct msgToAbeilleLQI ParserToAbeille) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
            }

            if (msg_send( $this->queueKeyParserToLQI, 1, $msgAbeille, true, false)) {
                // parserLog("debug","(fct msgToAbeilleLQI ParserToLQI) added to queue (queueKeyParserToLQI): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                parserLog("debug","(fct msgToAbeilleLQI ParserToLQI) could not add message to queue (queueKeyParserToLQI) with error code : ".$errorcode);
            }
        } */

        /* Send message to 'AbeilleLQI'.
           Returns: 0=OK, -1=ERROR */
        function msgToLQICollector($SrcAddr, $NTableEntries, $NTableListCount, $StartIndex, $NList)
        {
            $msg = array(
                'Type' => '804E',
                'SrcAddr' => $SrcAddr,
                'TableEntries' => $NTableEntries,
                'TableListCount' => $NTableListCount,
                'StartIndex' => $StartIndex,
                'List' => $NList
            );

            if (msg_send($this->queueKeyParserToLQI, 1, json_encode($msg), false, false, $error_code) == TRUE) {
                return 0;
            }

            parserLog("error", "msgToLQICollector(): Impossible d'envoyer le msg vers AbeilleLQI (err ".$error_code.")");
            return -1;
        }

        /* Send msg to client if queue exists.
           Purpose is to push infos to client side page when they arrive (ex: device discovery).
         */
        function msgToClient($msg) {
            $queueKeyParserToCli = msg_get_queue(queueKeyParserToCli);
            if ($queueKeyParserToCli === false)
                return; // No queue

            $errorcode = 0;
            if (msg_send($queueKeyParserToCli, 1, json_encode($msg), false, false, $errorcode) == false) {
                parserLog("debug", "msgToClient(): ERROR ".$errorcode);
            } else
                parserLog("debug", "msgToClient(): Sent ".json_encode($msg));
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
                    if (isset($GLOBALS['supportedEqList'][$identifier])) {
                        $jsonName = $identifier;
                        parserLog('debug', "  EQ is supported with '".$identifier."' identifier");
                    } else if (isset($GLOBALS['customEqList'][$identifier])) {
                        $jsonName = $identifier;
                        $jsonLocation = "local";
                        parserLog('debug', "  EQ is supported as user/custom with '".$identifier."' identifier");
                    }
                }
                if ($jsonName == '') {
                    $identifier = $eq['modelIdentifier'];
                    if (isset($GLOBALS['supportedEqList'][$identifier])) {
                        $jsonName = $identifier;
                        parserLog('debug', "  EQ is supported with '".$identifier."' identifier");
                    } else if (isset($GLOBALS['customEqList'][$identifier])) {
                        $jsonName = $identifier;
                        $jsonLocation = "local";
                        parserLog('debug', "  EQ is supported as user/custom with '".$identifier."' identifier");
                    }
                }
            } else {
                /* Search by location */
                $identifier = $eq['location'];
                if (isset($GLOBALS['supportedEqList'][$identifier])) {
                    $jsonName = $identifier;
                    parserLog('debug', "  EQ is supported with '".$identifier."' location identifier");
                } else if (isset($GLOBALS['customEqList'][$identifier])) {
                    $jsonName = $identifier;
                    $jsonLocation = "local";
                    parserLog('debug', "  EQ is supported as user/custom with '".$identifier."' location identifier");
                }
            }

            if ($jsonName == '') {
                $eq['jsonId'] = "defaultUnknown";
                parserLog('debug', "  EQ is UNsupported. 'defaultUnknown' config will be used");
                return false;
            }

            // Read JSON to get list of commands to execute
            $eqConfig = AbeilleTools::getJsonConfig($jsonName, $jsonLocation);
            $eq['jsonId'] = $jsonName;
            $eq['config'] = $eqConfig[$jsonName];
            return true;
        }

        /* Cached EQ infos reminder:
            $GLOBALS['eqList'][<network>][<addr>] = array(
                'ieee' => $ieee,
                'capa' => '', // MAC capa from dev announce
                'status' => 'identifying', // identifying, configuring, discovering, idle
                'since' => time(),
                'epList' => '', // List of end points
                'epFirst' => '', // First end point (usually 01)
                'manufacturer' => null (undef)/false (unsupported)/'xx'
                'modelIdentifier' => null (undef)/false (unsupported)/'xx'
                'location' => null (undef)/false (unsupported)/'xx'
                'jsonId' => '', // JSON identifier
            );
            identifying: req EP list + manufacturer + modelId + location
                         Note: Special case for Xiaomi which may not support "Active EP request".
            configuring: execute cmds with 'execAtCreation' flag
            discovering: for unknown EQ
            idle: all actions ended
        */

        /* Called on device announce during inclusion phase.
           This is the first step of identification. */
        function deviceAnnounce($net, $addr, $ieee, $capa) {
            if (!isset($GLOBALS['eqList']))
                $GLOBALS['eqList'] = [];
            if (!isset($GLOBALS['eqList'][$net]))
                $GLOBALS['eqList'][$net] = [];

            if (isset($GLOBALS['eqList'][$net][$addr])) {
                $eq = &$GLOBALS['eqList'][$net][$addr];
                if ($eq['ieee'] != $ieee) {
                    parserLog('debug', '  ERROR: There is a different EQ (ieee='.$eq['ieee'].') for addr '.$addr);
                    return;
                }
                parserLog('debug', '  EQ already known: Status='.$eq['status'].', since='.$eq['since'].', time='.time());

                /* Note: Assuming 4sec max per phase */
                if (($eq['since'] + 4) > time()) {
                    if ($eq['status'] == 'identifying')
                        parserLog('debug', '  Device identification already ongoing');
                    else if ($eq['status'] == 'discovering')
                        parserLog('debug', '  Device discovering already ongoing');
                    return;
                }
                /* Identification to restart */
            } else {
                /* Looking for eq by its ieee to update addr which may have changed */
                foreach ($GLOBALS['eqList'][$net] as $oldaddr => $eq) {
                    if ($eq['ieee'] != $ieee)
                        continue;

                    $GLOBALS['eqList'][$net][$addr] = $eq;
                    unset($GLOBALS['eqList'][$net][$oldaddr]);
                    parserLog('debug', '  EQ already known: Addr updated from '.$oldaddr.' to '.$addr);
                    break;
                }
                if (!isset($GLOBALS['eqList'][$net][$addr])) {
                    /* It's an unknown eq */
                    parserLog('debug', '  EQ new to parser');
                    $GLOBALS['eqList'][$net][$addr] = array(
                        'ieee' => $ieee,
                        'capa' => $capa,
                        'status' => '', // identifying, discovering, configuring
                        'since' => '',
                        'epList' => '',
                        'epFirst' => '',
                        'manufacturer' => null, // null=undef, false=unsupported, else 'value'
                        'modelIdentifier' => null, // null=undef, false=unsupported, else 'value'
                        'location' => null, // null=undef, false=unsupported, else 'value'
                        'jsonId' => '',
                    );
                    $eq = &$GLOBALS['eqList'][$net][$addr];
                }
            }

            if ($eq['epList'] == '') {
                /* Special trick for Xiaomi for which some devices (at least v1) do not answer to "Active EP request".
                   Old sensor even not answer to manufacturer request. */
                $xiaomi = (substr($ieee, 0, 9) == "00158D000") ? true : false;
                if ($xiaomi) {
                    parserLog('debug', '  Xiaomi specific identification.');
                    $eq['manufacturer'] = 'LUMI';
                    $eq['epList'] = "01";
                    $eq['epFirst'] = "01";
                    if (!isset($eq['modelIdentifier'])) {
                        parserLog('debug', '  Requesting modelIdentifier from EP 01');
                        $this->msgToCmd("Cmd".$net."/0000/getName", "address=".$addr.'&destinationEndPoint=01');
                    }
                } else {
                    parserLog('debug', '  Requesting active end points list');
                    $this->msgToCmd("Cmd".$net."/0000/ActiveEndPoint", "address=".$addr);
                }
                $eq['status'] = 'identifying';
                $eq['since'] = time();
            } else {
                /* 'epList' is already known => trigger next step */
                $eq['status'] = 'identifying';
                $eq['since'] = time();
                $this->updateEq($net, $addr, 'epList', $eq['epList']);
            }
        }

        /* 2nd step of identification phase.
           Wait for enough info to identify device (manuf, modelId, location) */
        function updateEq($net, $addr, $updType, $value) {
            if (!isset($GLOBALS['eqList']))
                return; // No dev announce before
            if (!isset($GLOBALS['eqList'][$net]))
                return; // No dev announce before
            if (!isset($GLOBALS['eqList'][$net][$addr]))
                return; // No dev announce before

            $eq = &$GLOBALS['eqList'][$net][$addr]; // By ref
            $v = ($value === false) ? 'false' : $value;
            parserLog('debug', "  updateEq('".$updType."', '".$v."'), status=".$eq['status']);

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
                if (!isset($eq['manufacturer'])) {
                    parserLog('debug', '  Requesting manufacturer from EP '.$eq['epFirst']);
                    $this->msgToCmd("Cmd".$net."/0000/getManufacturerName", "address=".$addr.'&destinationEndPoint='.$eq['epFirst']);
                }
                if (!isset($eq['modelIdentifier'])) {
                    parserLog('debug', '  Requesting modelIdentifier from EP '.$eq['epFirst']);
                    $this->msgToCmd("Cmd".$net."/0000/getName", "address=".$addr.'&destinationEndPoint='.$eq['epFirst']);
                }
                if (!isset($eq['location'])) {
                    parserLog('debug', '  Requesting location from EP '.$eq['epFirst']);
                    $this->msgToCmd("Cmd".$net."/0000/getLocation", "address=".$addr.'&destinationEndPoint='.$eq['epFirst']);
                }
            }

            /* Enough infos to try to identify device ?
               Identification process reminder
                - if modelId is supported
                    - search for JSON with 'modelId_manuf' then 'modelId'
                    - if found => configure
                    - if not found => discover
                - else if location is supported
                    - search for JSON with 'modelId_manuf' then 'modelId'
                    - if found => configure
                    - if not found => discover
                - else => discover */
            $nextstep = '';
            if (!isset($eq['modelIdentifier']))
                return; // Need value or false (unsupported)
            if ($eq['modelIdentifier'] !== false) {
                if (!isset($eq['manufacturer']))
                    return; // Need value or false (unsupported)
                /* Manufacturer & modelId attributes returned */
                if ($this->findJsonConfig($eq, 'modelIdentifier'))
                    $nextstep = 'configure';
                else
                    $nextstep = 'discover';
            } else if (!isset($eq['location'])) {
                return; // Need value or false (unsupported)
            } else if ($eq['location'] !== false) {
                /* ModelId UNsupported. Trying with 'location' */
                if ($this->findJsonConfig($eq, 'location'))
                    $nextstep = 'configure';
                else
                    $nextstep = 'discover';
            } else {
                $nextstep = 'discover';
            }

            if ($nextstep == 'configure')
                $this->configureEQ($net, $addr);
            else if ($nextstep == 'discover')
                $this->discoverEQ($net, $addr);
        } // End updateEQ()

        /* Go thru EQ commands and execute all those marked 'execAtCreation' */
        function configureEQ($net, $addr) {
            parserLog('debug', "  configureEQ(".$net.", ".$addr.")");

            $eq = &$GLOBALS['eqList'][$net][$addr];
            $eq['status'] = 'configuring';
            $cmds = $eq['config']['Commandes'];

parserLog('debug', "  cmds=".json_encode($cmds));
            foreach ($cmds as $cmd) {
                $c = $cmd['configuration'];
                if (!isset($c['execAtCreation']))
                    continue;
                parserLog('debug', "    exec cmd ".$cmd['name']);
                $topic = $c['topic'];
                // $topic = AbeilleCmd::updateField($dest, $cmd, $topic);
                $request = $c['request'];
                // $request = AbeilleCmd::updateField($dest, $cmd, $request);
                // TODO: #EP# defaulted to first EP but should be
                //       defined in cmd use if different target EP
                $request = str_replace('#EP#', $eq['epFirst'], $request);
                $request = str_replace('#addrIEEE#', $eq['ieee'], $request);
                $zgNb = substr($net, 7); // 'AbeilleX' => 'X'
                $request = str_replace('#ZiGateIEEE#', $GLOBALS['zigate'.$zgNb]['ieee'], $request);
parserLog('debug', '      request='.$request);
                $this->msgToCmd("Cmd".$net."/".$addr."/".$topic, $request);
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
                'capa' => $eq['capa'],
                'time' => time()
            );
            $this->msgToAbeille2($msg);

            // TODO: Tcharp38: 'idle' state might be too early since execAtCreation commands might not be completed yet
            $eq['status'] = 'idle';
        }

        /* Unknown EQ. Attempting to discover it. */
        function discoverEQ($net, $addr) {
            parserLog('debug', "  discoverEQ()");

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
        public function volt2pourcent( $voltage ) {
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

        /* New generic function to convert hex string to uintX/intX */
        function convHexToNumber($hexValue, $zbType) {
            $num = hexdec($hexValue);
            switch ($zbType) {
            case "29": // int16
                if ($num > 0x7fff) // is negative
                $num -= 0x10000;
                break;
            default:
                parserLog('debug', "convHexToNumber(): Unsupported type ".$zbType);
                return 0;
            }
            return $num;
        }

        /* Convert hex string to proper data type.
           'hexString' = input hexa string
           'reorder' = true if input is raw string, else false
           'dataSize' is size of value in Bytes
           'hexValue' is the extracted & reordered hex string value
           Returns value according to type. */
        function decodeDataType($hexString, $dataType, $reorder, &$dataSize, &$hexValue) {
            // Compute value size according to data type
            switch ($dataType) {
            case "20": // Uint8
            case "28": // Int8
                $dataSize = 1;
                break;
            case "21": // Uint16
            case "29": // Int16
                $dataSize = 2;
                break;
            case "22": // Uint24
            case "2A": // Int24
                $dataSize = 3;
                break;
            case "23":  // Uint32
            case "2B": // Int32
                $dataSize = 4;
                break;
            case "24":  // Uint40
            case "2C": // Int40
                $dataSize = 5;
                break;
            case "25":  // Uint48
            case "2D": // Int48
                $dataSize = 6;
                break;
            case "26":  // Uint56
            case "2E": // Int56
                $dataSize = 7;
                break;
            case "27":  // Uint64
            case "2F": // Int64
                $dataSize = 8;
                break;
            default:
                parserLog('debug', "  decodeDataType(): Unsupported type ".$dataType);
                $dataSize = 0;
                $hexValue = '';
                return '';
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
parserLog('debug', "  decodeDataType(): size=".$dataSize.", hexString=".$hexString." => hs=".$hs);
            $hexValue = $hs;

            // Computing value
            switch ($dataType) {
            case "20": // Uint8
            case "21": // Uint16
            case "22": // Uint24
            case "23": // Uint32
            case "24": // Uint40
            case "25": // Uint48
            case "26": // Uint56
            case "27": // Uint64
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
            default:
                parserLog('debug', "  decodeDataType(): Unsupported type ".$dataType);
                return '';
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
         * @param datas         Message sent by the zigate
         *
         * @return Status       0=OK, -1=ERROR
         */
        function protocolDatas($dest, $datas) {
            // Reminder: message format received from Zigate.
            // 01/start & 03/end are already removed.
            //   00-03 : Msg Type (2 bytes)
            //   04-07 : Length (2 bytes) => optional payload + LQI
            //   08-09 : crc (1 byte)
            //   10... : Optional data / payload
            //   Last  : LQI (1 byte)

            $crctmp = 0;

            $length = strlen($datas);
            if ($length < 10) {
                ParserLog('error', $dest.", Message TOO SHORT (len=".$length.")");
                return -1; // Too short. Min=MsgType+Len+Crc
            }

            //type de message
            $type = $datas[0].$datas[1].$datas[2].$datas[3];
            $crctmp = $crctmp ^ hexdec($datas[0].$datas[1]) ^ hexdec($datas[2].$datas[3]);

            // Taille message
            // see github: AbeilleParser Erreur CRC #1562
            $ln = $datas[4].$datas[5].$datas[6].$datas[7];
            $ln = hexdec($ln);
            if ( $ln > 150 ) {
                parserLog('error', $dest.", Message TOO LONG (len=".$length.") => ignored");
                return 0;
            }
            $crctmp = $crctmp ^ hexdec($datas[4].$datas[5]) ^ hexdec($datas[6].$datas[7]);

            //acquisition du CRC
            $crc = strtolower($datas[8].$datas[9]);

            /* Payload.
               Payload size is 'Length' - 1 (excluding LQI) but CRC takes LQI into account.
               See https://github.com/fairecasoimeme/ZiGate/issues/325# */
            $payloadSize = ($length - 12) / 2; // Real payload size in Bytes. Removing MsgType+Len+Crc+LQI
            if ($payloadSize != ($ln - 1))
                parserLog('debug', 'WARNING: Length ('.$ln.') != real payload + LQI size ('.$payloadSize.')');
            $payload = substr($datas, 10, $payloadSize * 2);
            // parserLog('debug', 'type='.$type.', payload='.$payload);
            for ($i = 0; $i < $ln; $i++) {
                // $payload .= $datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)];
                $crctmp = $crctmp ^ hexdec($datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)]);
            }

            // LQI
            $quality = $datas[10 + ($i * 2) - 2].$datas[10 + ($i * 2) - 1];
            $quality = hexdec( $quality );

            //verification du CRC
            if (hexdec($crc) != $crctmp) {
                parserLog('error', 'ERREUR CRC: calc=0x'.dechex($crctmp).', att=0x'.$crc.'. Message ignoré: '.substr($datas, 0, 12).'...'.substr($datas, -2, 2));
                parserLog('debug', 'Mess ignoré='.$datas);
                return -1;
            }

            //Traitement PAYLOAD
            $param1 = "";
            // if (($type == "8003") || ($type == "8043")) $param1 = $clusterTab; // Tcharp38: no longer used
            // if ($type == "804E") $param1 = $LQI; // Tcharp38: no longer used
            // if ($type == "8102") $param1 = $quality; // Tcharp38: always transmitted now

            $fct = "decode".$type;
            // parserLog('debug','Calling function: '.$fct);

            //  if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none' ) == $ExtendedAddress ) {
            //               config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 1,   'Abeille');

            $commandAcceptedUntilZigateIdentified = array( "decode0300", "decode0208", "decode8009", "decode8024", "decode8000" );

            /* To be sure there is no port changes, 'AbeilleIEEE_Ok' is set to 0 on daemon start.
               Should be updated by 8009 response */
            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 'Abeille', '0', 1 ) == 0 ) {
                if (!in_array($fct, $commandAcceptedUntilZigateIdentified)) {
                    parserLog('debug', $dest.', AbeilleIEEE_Ok==0 => msg '.$type." ignored");
                    return 0;
                }
            }

            if ( method_exists($this, $fct)) {
                $this->$fct($dest, $payload, $quality);
            }
            else {
                parserLog('debug', $dest.', Type='.$type.'/'.zgGetMsgByType($type).', ignoré (non supporté).');
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
         * @return          Does return anything as all action are triggered by sending messages in queues
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

            /* Monitor if required */
            if (isset($GLOBALS["dbgMonitorAddrExt"]) && !strcasecmp($GLOBALS["dbgMonitorAddrExt"], $IEEE)) {
                monMsgFromZigate($msgDecoded); // Send message to monitor
                monAddrHasChanged($Addr, $IEEE); // Short address has changed
                $GLOBALS["dbgMonitorAddr"] = $Addr;
            }

            $this->whoTalked[] = $dest.'/'.$Addr;

            $zgNb = substr($dest, 7);
            if (isset($GLOBALS['zigate'.$zgNb]['permitJoin']) && ($GLOBALS['zigate'.$zgNb]['permitJoin'] == "01")) {
                $this->deviceAnnounce($dest, $Addr, $IEEE, $MACCapa);
            } else {
                parserLog('debug', '  Not in inclusion mode => device announce ignored');
                return;
            }

            // Tcharp38: Why ? $this->msgToAbeilleFct($dest."/"."Ruche", "enable", $IEEE); <- KiwiHC16: un equipement peut exister dans Abeille eet etre disabled donc on le reactive ici.
            // $this->msgToAbeilleFct($dest."/".$Addr, "enable", $IEEE);

            // Envoie de la IEEE a Jeedom qui le processera dans la cmd de l objet si celui ci existe deja dans Abeille, sinon sera drop
            // $this->msgToAbeille($dest."/".$Addr, "IEEE", "Addr", $IEEE);

            // Rafraichi le champ Ruche, JoinLeave (on garde un historique)
            // $this->msgToAbeille($dest."/0000", "joinLeave", "IEEE", "Annonce->".$IEEE);

            // $this->msgToAbeille($dest."/"."$Addr", "MACCapa", "MACCapa", $MACCapa);

            // Si 02 = Rejoin alors on doit le connaitre on ne va pas faire de recherche
            // if ($Rejoin == "02") return;

            // Tcharp38: How to handle the following ?
            //           This currently may lead to cmd bottleneck with bad 8000 status.

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

            $SrcAddr    = substr($payload,  2,  4);
            $EPS        = substr($payload,  6,  2);
            $ClusterId  = "0006-".$EPS;
            $AttributId = "0000";
            $data       = substr($payload, 30,  2);

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

            $msgDecoded = "0100/?";
            parserLog('debug', $dest.', Type='.$msgDecoded);
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
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

            $this->msgToCmd( "Cmd".$dest."/0000/PDM", "req=E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE");
        }

        function decode0208($dest, $payload, $lqi)
        {
            // "0208 0003 19001000"
            // E_SL_MSG_PDM_EXISTENCE_REQUEST = 0x0208

            $id = substr( $payload, 0  , 4);

            parserLog('debug', $dest.', Type=0208/E_SL_MSG_PDM_EXISTENCE_REQUEST : PDM Exist for id : '.$id.' ?');

            $this->msgToCmd( "Cmd".$dest."/0000/PDM", "req=E_SL_MSG_PDM_EXISTENCE_RESPONSE&recordId=".$id);
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
            // $SrcAddr    = "Ruche";
            // $ClusterId  = "Zigate";
            // $AttributId = "8000";
            // $data       = $this->displayStatus($status);
            // $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

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

        /* Called from decode8002() to decode a "Read Attribute Status Record" */
        function decode8002_ReadAttrStatusRecord($hexString, &$size) {
            /* Attributes status record format:
                Attr id = 2B
                Status = 1B
                Attr data type = 1B
                Attr = <according to data type> */
            $attr = array(
                'id' => substr($hexString, 2, 2).substr($hexString, 0, 2),
                'status' => substr($hexString, 4, 2),
                'dataType' => '',
                'value' => null
            );
            $size = 6;
            if ($attr['status'] != '00')
                return;
            $attr['dataType'] = substr($hexString, 6, 2);
            $attr['value'] = $this->decodeDataType($hexString, $attr['dataType'], true, $dataSize, $hexValue);
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

            // Decode first based on https://zigate.fr/documentation/commandes-zigate/
            $status                 = substr($payload, 0, 2);
            $profile                = substr($payload, 2, 4);
            $cluster                = substr($payload, 6, 4);
            $srcEndPoint            = substr($payload,10, 2);
            $destEndPoint           = substr($payload,12, 2);
            $sourceAddressMode      = substr($payload,14, 2);
            $srcAddress             = substr($payload,16, 4);
            $destinationAddressMode = substr($payload,20, 2);
            $dstAddress             = substr($payload,22, 4);
            // $payload              // Will decode later depending on the message
            $pl = substr($payload, 26); // Keeping payload only

            $this->whoTalked[] = $dest.'/'.$srcAddress;

            $msgDecoded = '8002/Data indication'
                            .', Status='.$status
                            .', ProfId='.$profile
                            .', ClustId='.$cluster
                            .", SrcEP=".$srcEndPoint
                            .", DestEP=".$destEndPoint
                            .", SrcAddrMode=".$sourceAddressMode
                            .", SrcAddr=".$srcAddress
                            .", DestAddrMode=".$destinationAddressMode
                            .", DestAddr=".$dstAddress;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8002");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $srcAddress, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            // Routing Table Response
            if (($profile == "0000") && ($cluster == "8032")) {

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

                    parserLog('debug', '  address='.$addressDest.' status='.$statusDecoded.'('.$statusRouting.') Next Hop='.$nextHop, "8002");

                    if ((base_convert( $statusRouting, 16, 2) &  7) == "00" ) {
                        $routingTable[] = array( $addressDest => $nextHop );
                    }
                }

                // if ( $srcAddress == "Ruche" ) return; // Verrue car si j interroge l alarme Heiman, je ne vois pas a tous les coups la reponse sur la radio et le message recu par Abeille vient d'abeille !!!

                $abeille = Abeille::byLogicalId( $dest.'/'.$srcAddress, 'Abeille');
                if ( $abeille ) {
                    $abeille->setConfiguration('routingTable', json_encode($routingTable) );
                    $abeille->save();
                }
                else {
                    parserLog('debug', '  abeille not found !!!', "8002");
                }

                return;
            }

            // Tcharp38: profId=104 & clustId=0001 does not mean it is power report. It could be
            //   plenty of other "response" (ex: discover attribut response)
            // // Cluster 0x0001 Power
            // if (($profile == "0104") && ($cluster == "0001")) {
            //     // Managed/Processed by Ziagte which send a 8102 message: sort of duplication of same message on 8000 et 8002, so not decoding it here, otherwise duplication.
            //     parserLog("debug", "  Duplication of 8102 and 8000 => dropped", "8002");
            //     return;
            // }

            // Cluster 0x0004 Groups
            if (($profile == "0104") && ($cluster == "0004")) {
                // Managed/Processed by Ziagte which send a 8062 message: sort of duplication of same message on 8000 et 8002, so not decoding it here, otherwise duplication.
                parserLog("debug", "  Duplication of 8102 and 8000 => dropped", "8002");
                return;
            }

            //  Cluster 0005 Scene (exemple: Boutons lateraux de la telecommande -)
            if (($profile == "0104") && ($cluster == "0005")) {

                $abeille = Abeille::byLogicalId($dest."/".$srcAddress,'Abeille');
                $sceneStored = json_decode( $abeille->getConfiguration('sceneJson','{}') , True );

                $frameCtrlField = substr($payload,26, 2);

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

                        $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$srcEndPoint, '0000', $value );
                        return;
                    }
                }

                if ( $frameCtrlField=='18' ) { // Default Resp: 1 / Direction: 1 / Manu Specific: 0 / Cluster Specific: 00
                    $SQN                    = substr($payload,28, 2);
                    $cmd                    = substr($payload,30, 2);
                    parserLog("debug", '  cmd :'.$cmd );

                    if ($cmd=="01") { // Read Attribut
                        $attribut           = substr($payload,34, 2).substr($payload,32, 2);
                        $status             = substr($payload,36, 2);
                        if ( $status != "00" ) {
                            parserLog("debug", '  Attribut Read Status error.');
                            return;
                        }
                        if ( $attribut == "0000" ) {
                            $dataType   = substr($payload,38, 2);
                            $sceneCount = substr($payload,40, 2);
                            $sceneStored["sceneCount"]           = $sceneCount-1; // On ZigLight need to remove one
                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();

                            parserLog("debug", '  '.json_encode($sceneStored) );

                            return;
                        }

                        if ( $attribut == "0001" ) {
                            $dataType   = substr($payload,38, 2);
                            $sceneCurrent = substr($payload,40, 2);
                            $sceneStored["sceneCurrent"]           = $sceneCurrent;
                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();

                            parserLog("debug", '  '.json_encode($sceneStored) );

                            return;
                        }

                        if ( $attribut == "0002" ) {
                            $dataType       = substr($payload,38, 2);
                            $groupCurrent   = substr($payload,40, 4);
                            $sceneStored["groupCurrent"]           = $groupCurrent;
                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();

                            parserLog("debug", '  '.json_encode($sceneStored) );

                            return;
                        }

                        if ( $attribut == "0003" ) {
                            $dataType   = substr($payload,38, 2);
                            $sceneActive = substr($payload,40, 2);
                            $sceneStored["sceneActive"]           = $sceneActive;
                            $abeille->setConfiguration('sceneJson', json_encode($sceneStored));
                            $abeille->save();

                            parserLog("debug", '  '.json_encode($sceneStored) );

                            return;
                        }

                        parserLog("debug", '  Attribut inconnu :'.$attribut );
                    }
                }

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

                            // $sceneStored = json_decode( Abeille::byLogicalId($dest."/".$srcAddress,'Abeille')->getConfiguration('sceneJson','{}') , True );
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

            // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
            if (($profile == "0104") && ($cluster == "0006")) {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd != "FD" ) return;
                $value                  = substr($payload,32, 2);

                parserLog('debug',  '  Interrupteur sur pile TS0043 bouton'
                                .', frameCtrlField='.$frameCtrlField
                                .', SQN='.$SQN
                                .', cmd='.$cmd
                                .', value='.$value,
                                 "8002"
                                 );

                $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$srcEndPoint, '0000', $value );
                return;
            }

            //
            if (($profile == "0104") && ($cluster == "0008")) {

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

                $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$srcEndPoint, '0000', $value );
                return;
            }

            if (($profile == "0104") && ($cluster == "000A")) {
                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2);

                if ($cmd == '00') {
                    $attributTime                  = substr($payload,34, 2).substr($payload,32, 2);
                    $attributTimeZone              = substr($payload,38, 2).substr($payload,36, 2);
                    if ( isset($this->debug["8002"])) {
                        parserLog('debug', '  Time Request - (decoded but not processed) '
                                       .', frameCtrlField='.$frameCtrlField
                                       .', SQN='.$SQN
                                       .', cmd='.$cmd
                                       .', attributTime='.$attributTime
                                       .', attributTimeZone='.$attributTimeZone
                                        );
                                    }

                    // Here we should reply to the device with the time. I though this Time Cluster was implemented in the zigate....
                    return;
                }
            }

            if (($profile == "0104") && ($cluster == "0204")) {
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

                    $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, $value );
                    return;
                }
            }

            // Tcharp38: profId=104 & clustId=402 does not mean it is temp report. It could be
            // plenty of other "response" (ex: discover attribut response)
            // // Cluster 0x0402 Temperature
            // if (($profile == "0104") && ($cluster == "0402")) {
            //     // Managed/Processed by Ziagte which send a 8102 message: sort of duplication of same message on 8000 et 8002, so not decoding it here, otherwise duplication.
            //     parserLog("debug",  "  Message duplication => dropped");
            //     return;
            // }

            // Remontée puissance prise TS0121 Issue: #1288
            if (($profile == "0104") && ($cluster == "0702")) {
                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd == "0A" ) $cmd = "0A - report attribut";
                $attribute              = substr($payload,34, 2).substr($payload,32, 2);
                $dataType               = substr($payload,36, 2);

                // Remontée puissance prise TS0121 Issue: #1288
                if (($attribute == '0000') && ($dataType==25)) {
                    // '25' => array( 'Uint48', 6 ), // Unsigned 48-bit int
                    $value = substr($payload,48, 2).substr($payload,46, 2).substr($payload,44, 2).substr($payload,42, 2).substr($payload,40, 2).substr($payload,38, 2);
                    parserLog('debug', '  Remontée puissance prise TS0121 '
                                   .', frameCtrlField='.$frameCtrlField
                                   .', SQN='.$SQN
                                   .', cmd='.$cmd
                                   .', attribute='.$attribute
                                   .', dataType='.$dataType
                                   .', value='.$value.' - '.hexdec($value),
                                    "8002"
                                    );

                    $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value));
                    return;
                }
            }

            // Remontée puissance module Legrand 20AX / prise Blitzwolf BW-SHP13 #1231
            // Electrical measurement cluster
            if (($profile == "0104") && ($cluster == "0B04")) {

                $frameCtrlField = substr($payload, 26, 2);
                $SQN = substr($payload, 28, 2);
                $cmd = substr($payload, 30, 2);
                if ($cmd == "01") $cmdTxt = "01/Read attributes response";
                else if ($cmd == "0A") $cmdTxt = "0A/Report attribut";
                parserLog('debug', '  FCF='.$frameCtrlField
                   .', SQN='.$SQN
                   .', cmd='.$cmdTxt);

                if ($cmd == '01') {

                    $attributs = substr($payload,32);
                    parserLog('debug', '  Attributs received: '.$attributs, "8002");

                    while (strlen($attributs) > 0) {

                        $attribute  = substr($attributs, 2, 2).substr($attributs, 0, 2);
                        $status     = substr($attributs, 4, 2);
                        if ($status != '00') {
                            parserLog('debug', '  Attribut analysis: '.$attribute.'-'.$status." => Ignored (status != 0)", "8002");
                            $attributs = substr($attributs, 6);
                            continue;
                        }

                        $dataType = substr($attributs, 6, 2);
                        $hexValue = '';
                        $dataSize = 0;
                        $realValue = $this->decodeDataType(substr($attributs, 8), $dataType, true, $dataSize, $hexValue);
                        $attributs = substr($attributs, 8 + ($dataSize * 2));

                        // $listType = array("21", "29");
                        // if ( in_array( $dataType, $listType)) {
                        //     $valueRevHex = substr($attributs, 8, 4);
                        //     $value = $valueRevHex[2].$valueRevHex[3].$valueRevHex[0].$valueRevHex[1];
                        //     $attributs = substr($attributs,12);
                        // }

                        $msg = array(
                            'src' => 'parser',
                            'type' => 'attributReport',
                            'net' => $dest,
                            'addr' => $srcAddress,
                            'ep' => $srcEndPoint,
                            'name' => $cluster.'-'.$srcEndPoint.'-'.$attribute,
                            'value' => false, // False = unsupported
                            'time' => time(),
                            'lqi' => $lqi
                        );

                        // example: Remontée V prise Blitzwolf BW-SHP13
                        if ($attribute == '0505') {
                            // '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int

                            parserLog('debug', '  RMS Voltage'
                               .', attrib='.$attribute
                               .', dataType='.$dataType
                               .', value='.$hexValue.' => '.$realValue,
                                "8002");

                            // $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, $realValue);
                            $msg['value'] = $realValue;
                            $this->msgToAbeille2($msg);
                        }

                        // example: Remontée A prise Blitzwolf BW-SHP13
                        else if ($attribute == '0508') {
                            // '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int

                            parserLog('debug', '  RMS Current'
                               .', attrib='.$attribute
                               .', dataType='.$dataType
                               .', value='.$hexValue.' => '.$realValue,
                                "8002"
                                );

                            // $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, $realValue);
                            $msg['value'] = $realValue;
                            $this->msgToAbeille2($msg);
                        }

                        // example: Remontée puissance prise Blitzwolf BW-SHP13
                        else if ($attribute == '050B') {
                            // '29' => array( 'Int16', 2 ), // Signed 16-bit int

                            parserLog('debug', '  Active Power'
                               .', attrib='.$attribute
                               .', dataType='.$dataType
                               .', value='.$hexValue.' => '.$realValue,
                                "8002"
                                );

                            // $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, $realValue);
                            $msg['value'] = $realValue;
                            $this->msgToAbeille2($msg);
                        }

                        else {
                            parserLog('debug', '  Attribut analysis: '.$attribute.'-'.$status.'-'.$dataType.'-'.$hexValue , "8002");
                        }
                    }
                    return;
                }

                // exemple: emontée puissance module Legrand 20AX
                if (($cmd == '0A')) {
                    $attribute = substr($payload,34, 2).substr($payload,32, 2);
                    $dataType  = substr($payload,36, 2);
                    if (($attribute == '050B') && ($dataType == '29')) {
                        // '29' => array( 'Int16', 2 ), // Signed 16-bit int
                        $value = substr($payload,40, 2).substr($payload,38, 2);

                        parserLog('debug', '  ActivePower'
                           .', attrib='.$attribute
                           .', dataType='.$dataType
                           .', value='.$value.' - '.hexdec($value),
                            "8002");

                        $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value));
                        return;
                    }
                }
            }

            // Remontée etat relai module Legrand 20AX
            // 80020019F4000104 FC41 010102D2B9020000180B0A000030000100100084
            if (($profile == "0104") && ($cluster == "FC41")) {

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

                $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value));

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

                    $this->msgToAbeille($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value));
                }

                return;
            }

            // Prise Xiaomi
            if (($profile == "0104") && ($cluster == "FCC0")) {
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

                                    $this->msgToAbeille($dest."/".$srcAddress, '0006', '01-0000',     $FCC0["Etat SW 1 Binaire"]["valueConverted"],  $lqi);    // On Off Etat
                                    $this->msgToAbeille($dest."/".$srcAddress, '0402', '01-0000',     $FCC0["Device Temperature"]["valueConverted"], $lqi);    // Device Temperature
                                    $this->msgToAbeille($dest."/".$srcAddress, '000C', '15-0055',     $FCC0["Puissance"]["valueConverted"],          $lqi);    // Puissance
                                    $this->msgToAbeille($dest."/".$srcAddress, 'tbd',  '--conso--',   $FCC0["Consommation"]["valueConverted"],       $lqi);    // Consumption
                                    $this->msgToAbeille($dest."/".$srcAddress, 'tbd',  '--volt--',    $FCC0["Voltage"]["valueConverted"],            $lqi);    // Voltage
                                    $this->msgToAbeille($dest."/".$srcAddress, 'tbd',  '--current--', $FCC0["Current"]["valueConverted"],            $lqi);    // Current
                                }
                            }
                        }
                    }
                }
            }

            /* Decoding ZCL header */
            $FCF = substr($payload, 26, 2); // Frame Control Field
            $manufSpecific = hexdec($FCF) & (1 << 2);
            if ($manufSpecific) {
                /* 16bits for manuf specific code */
                $SQN = substr($payload, 32, 2); // Sequence Number
                $Cmd = substr($payload, 34, 2); // Command
                $msg = substr($payload, 34);
            } else {
                $SQN = substr($payload, 28, 2); // Sequence Number
                $Cmd = substr($payload, 30, 2); // Command
                $msg = substr($payload, 32);
            }
            parserLog('debug', "  FCF=".$FCF.", SQN=".$SQN.", cmd=".$Cmd.", msg=".$msg);

            /* 'Cmd' reminder
                0x01 Read Attributes Response
                0x04 Write Attributes Response
                0x05 Write Attributes No Response
                0x07 Configure Reporting Response
                0x09 Read Reporting Configuration Response
                0x0a Report attributes
                0x0d Discover Attributes Response
            */
            if ($Cmd == "01") { // Read Attributes Response
                parserLog('debug', "  Read Attributes Response");

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

                // $l = strlen($msg);
                // for ($i = 0; $i < $l;) {
                //     $size = 0;
                //     $attr = $this->decode8002_ReadAttrStatusRecord($msg, $size);
                //     $attributes[] = $attr;
                //     $i += $size;
                // }
            } else if ($Cmd == "04") { // Write Attributes Response
                parserLog('debug', "  Write Attributes Response");
            } else if ($Cmd == "07") { // Configure Reporting Response
                parserLog('debug', "  Configure Reporting Response");
            } else if ($Cmd == "0A") { // Report attributes
                parserLog('debug', "  Report attributes");
                // Currently treated by decode8100_8102()
            } else if ($Cmd == "0D") { // Discover Attributes Response
                parserLog('debug', "  Discover Attributes Response");
                $completed = substr($msg, 2);
                $msg = substr($msg, 2); // Skipping 'completed' status

                parserLog('debug', "  msg=".$msg);
                $attributes = [];
                // 'Attributes' => []; // Attributes
                //      $attr['Id']
                //      $attr['DataType']
                $l = strlen($msg);
                for ($i = 0; $i < $l;) {
                    $attr = array(
                        'Id' => substr($msg, $i + 2, 2).substr($msg, $i, 2),
                        'DataType' => substr($msg, $i + 4, 2)
                    );
                    $attributes[] = $attr;
                    $i += 6;
                }
                $m = '';
                foreach ($attributes as $attr) {
                    if ($m != '')
                        $m .= '/';
                    $m .= $attr['Id'];
                }
                parserLog('debug', '  Clust '.$cluster.': '.$m);
                $this->discoverLog('- Clust '.$cluster.': '.$m);
                return;
            }

            parserLog("debug", "  Ignored, payload=".$pl, "8002");
        }

        // Tcharp38: No longer required.
        // https://github.com/fairecasoimeme/ZiGate/issues/295
        // function decode8003($dest, $payload, $ln, $lqi, $clusterTab) {
        //     // <source endpoint: uint8_t t>
        //     // <profile ID: uint16_t>
        //     // <cluster list: data each entry is uint16_t>

        //     $SrcEndpoint = substr($payload, 0, 2);
        //     $profileID   = substr($payload, 2, 4);

        //     $len = (strlen($payload)-2-4-2)/4;
        //     for ($i = 0; $i < $len; $i++) {
        //         $clustId = substr($payload, 6 + ($i * 4), 4);
        //         parserLog('debug', $dest.', Type=8003/Clusters list, SrcEP='.$SrcEndpoint.', ProfId='.$profileID.', ClustId='.$clustId.' - '.zgGetCluster($clustId), "8003");
        //     }
        // }

        // Tcharp38: No longer required.
        // https://github.com/fairecasoimeme/ZiGate/issues/295
        // function decode8004($dest, $payload, $lqi) {
        //     // <source endpoint: uint8_t>
        //     // <profile ID: uint16_t>
        //     // <cluster ID: uint16_t>
        //     // <attribute list: data each entry is uint16_t>

        //     $SrcEndpoint = substr($payload, 0, 2);
        //     $profileID   = substr($payload, 2, 4);
        //     $clusterID   = substr($payload, 6, 4);

        //     $len = (strlen($payload)-2-4-4-2)/4;
        //     for ($i = 0; $i < $len; $i++) {
        //         parserLog('debug', $dest.', Type=8004/Liste des Attributs de l’objet, SrcEP='.$SrcEndpoint.', ProfileID='.$profileID.', ClustId='.$clusterID.', Attribute='.substr($payload, (10 + ($i*4) ), 4) );
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

        //     $SrcEndpoint = substr($payload, 0, 2);
        //     $profileID   = substr($payload, 2, 4);
        //     $clusterID   = substr($payload, 6, 4);

        //     $len = (strlen($payload)-2-4-4-2)/2;
        //     for ($i = 0; $i < $len; $i++) {
        //         parserLog('debug', $dest.', Type=8005/Liste des commandes de l’objet, SrcEP='.$SrcEndpoint.', ProfID='.$profileID.', ClustId='.$clusterID.', Commandes='.substr($payload, (10 + ($i*2) ), 2) );
        //     }
        // }

        /* Network State Reponse */
        function decode8009($dest, $payload, $lqi)
        {
            // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; (Network State response)(Processed->MQTT)'); }

            // <Short Address: uint16_t>
            // <Extended Address: uint64_t>
            // <PAN ID: uint16_t>
            // <Ext PAN ID: uint64_t>
            // <Channel: u int8_t>
            $ShortAddress       = substr($payload, 0, 4);
            $ExtendedAddress    = substr($payload, 4,16);
            $PAN_ID             = substr($payload,20, 4);
            $Ext_PAN_ID         = substr($payload,24,16);
            $Channel            = hexdec(substr($payload,40, 2));

            $this->whoTalked[] = $dest.'/'.$ShortAddress;

            $msgDecoded = '8009/Network state response, Addr='.$ShortAddress.', ExtAddr='.$ExtendedAddress.', PANId='.$PAN_ID.', ExtPANId='.$Ext_PAN_ID.', Chan='.$Channel;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8009");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $ShortAddress, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            // Local storage for speed optimization
            $zgNb = substr($dest, 7);
            $GLOBALS['zigate'.$zgNb]['ieee'] = $ExtendedAddress;

            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none', 1 ) == "none" ) {
                config::save( str_replace('Abeille', 'AbeilleIEEE', $dest), $ExtendedAddress,   'Abeille');
            }
            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none', 1 ) == $ExtendedAddress ) {
                config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 1,   'Abeille');
            }
            else {
                config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), -1,   'Abeille');
                message::add("Abeille", "La zigate ".$dest." detectee ne semble pas etre la bonne (IEEE qui remonte: ".$ExtendedAddress." alors que j ai en memoire: ".config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none', 1 )."). Je la bloque pour ne pas créer de soucis.", "Verifiez que les zigates sont bien sur le bon port tty par example suite  a un reboot..", 'Abeille/Demon');
                return;
            }

            // Envoie Short Address
            $data = $ShortAddress;
            $this->msgToAbeille($dest."/0000", "Short", "Addr", $data);
            // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; ZiGate Short Address: '.$ShortAddress); }

            // Envoie Extended Address
            $data = $ExtendedAddress;
            $this->msgToAbeille($dest."/0000", "IEEE", "Addr", $data);
            // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; IEEE Address: '.$ExtendedAddress); }

            // Envoie PAN ID
            $data = $PAN_ID;
            $this->msgToAbeille($dest."/0000", "PAN", "ID", $data);
            // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; PAN ID: '.$PAN_ID); }

            // Envoie Ext PAN ID
            $data = $Ext_PAN_ID;
            $this->msgToAbeille($dest."/0000", "Ext_PAN", "ID", $data);
            // if ($this->debug['8009']) { parserLog('debug', $dest.', Type=8009; Ext_PAN_ID: '.$Ext_PAN_ID); }

            // Envoie Channel
            $data = $Channel;
            $this->msgToAbeille($dest."/0000", "Network", "Channel", $data);
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
            $DestEndPoint   = substr($payload, 6, 2);
            $ClustID        = substr($payload, 8, 4);

            $msgDecoded = '8011/APS data ACK, Status='.$Status.', Addr='.$DestAddr.', EP='.$DestEndPoint.', ClustId='.$ClustID;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8011");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $DestAddr, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            if ($Status=="00") {
                if ( Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' )) {
                    $eq = Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' ) ;
                    parserLog('debug', '  Found: '.$eq->getHumanName()." set APS_ACK to 1", "8011");
                    $eq->setStatus('APS_ACK', '1');
                    parserLog('debug', '  APS_ACK: '.$eq->getStatus('APS_ACK'), "8011");
                }
            } else {
                if ( Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' )) {
                    $eq = Abeille::byLogicalId( $dest.'/'.$DestAddr, 'Abeille' ) ;
                    parserLog('debug', '  ACK failed: '.$eq->getHumanName().". APS_ACK set to 0", "8011");
                    $eq->setStatus('APS_ACK', '0');
                    parserLog('debug', '  APS_ACK: '.$eq->getStatus('APS_ACK'), "8011");
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
            $zgNb = substr($dest, 7);

            parserLog('debug', $dest.', Type=8014/Permit join status response, PermitJoinStatus='.$Status);
            if ($Status == "01")
                parserLog('info', '  Zigate'.$zgNb.': en mode INCLUSION', "8014");
            else
                parserLog('info', '  Zigate'.$zgNb.': mode inclusion inactif', "8014");

            $GLOBALS['zigate'.$zgNb]['permitJoin'] = $Status;

            $this->msgToAbeille($dest."/0000", "permitJoin", "Status", $Status);
        }

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

            parserLog('debug', $dest.', Type=8015/Abeille List: Payload='.$payload);

            $nb = (strlen($payload) - 2) / 26;
            parserLog('debug','  Nombre d\'abeilles: '.$nb);

            for ($i = 0; $i < $nb; $i++) {

                $SrcAddr = substr($payload, $i * 26 + 2, 4);

                // Envoie IEEE
                $dataAddr = substr($payload, $i * 26 + 6, 16);
                $this->msgToAbeille($dest."/".$SrcAddr, "IEEE", "Addr", $dataAddr);

                // Envoie Power Source
                $dataPower = substr($payload, $i * 26 + 22, 2);
                $this->msgToAbeille($dest."/".$SrcAddr, "Power", "Source", $dataPower);

                // Envoie Link Quality
                $dataLink = hexdec(substr($payload, $i * 26 + 24, 2));
                $this->msgToAbeille($dest."/".$SrcAddr, "Link", "Quality", $dataLink);

                parserLog('debug', '  i='.$i.': '
                                .'ID='.substr($payload, $i * 26 + 0, 2)
                                .', ShortAddr='.$SrcAddr
                                .', ExtAddr='.$dataAddr
                                .', PowerSource (0:battery - 1:AC)='.$dataPower
                                .', LinkQuality='.$dataLink   );
            }
        }

        /* 8017/Get Time Server Response (FW >= 3.0f) */
        function decode8017($dest, $payload, $lqi)
        {
            // <Timestamp UTC: uint32_t> from 2000-01-01 00:00:00
            $Timestamp = substr($payload, 0, 8);
            parserLog('debug', $dest.', Type=8017/Get time server response, Timestamp='.hexdec($Timestamp), "8017");

            // Note: updating timestamp ref from 2000 to 1970
            $data = date(DATE_RFC2822, hexdec($Timestamp) + mktime(0, 0, 0, 1, 1, 2000));
            $this->msgToAbeille($dest."/0000", "ZiGate", "Time", $data);
        }

        /* Network joined/formed */
        function decode8024($dest, $payload, $lqi)
        {
            // https://github.com/fairecasoimeme/ZiGate/issues/74

            // Formed Msg Type = 0x8024
            // Node->Host  Network Joined / Formed

            // <status: uint8_t>
            // <short address: uint16_t>
            // <extended address:uint64_t>
            // <channel: uint8_t>

            // Status:
            // 0 = Joined existing network
            // 1 = Formed new network
            // 128 – 244 = Failed (ZigBee event codes)

            // Envoi Status
            if( substr($payload, 0, 2) == "00" ) { $data = "Joined existing network"; }
            if( substr($payload, 0, 2) == "01" ) { $data = "Formed new network"; }
            if( substr($payload, 0, 2) == "04" ) { $data = "Network (already) formed"; }
            if( substr($payload, 0, 2)  > "04" ) { $data = "Failed (ZigBee event codes): ".substr($payload, 0, 2); }
            $dataShort = substr($payload, 2, 4);
            $dataIEEE = substr($payload, 6, 16);
            $dataNetwork = hexdec(substr($payload, 22, 2));

            parserLog('debug', $dest.', Type=8024/Network joined-formed, Status=\''.$data.'\', Addr='.$dataShort.', ExtAddr='.$dataIEEE.', Chan='.$dataNetwork, "8024");

            $this->msgToAbeille($dest."/0000", "Network", "Status", $data);
            $this->msgToAbeille($dest."/0000", "Short", "Addr", $dataShort);
            $this->msgToAbeille($dest."/0000", "IEEE", "Addr", $dataIEEE);
            $this->msgToAbeille($dest."/0000", "Network", "Channel", $dataNetwork);
        }

        function decode8030($dest, $payload, $lqi)
        {
            // Firmware V3.1a: Add fields for 0x8030, 0x8031 Both responses now include source endpoint, addressmode and short address. https://github.com/fairecasoimeme/ZiGate/issues/122
            // <Sequence number: uint8_t>
            // <status: uint8_t>

            parserLog('debug', $dest.', Type=8030/Bind response (decoded but Not Processed - Just send time update and status to Network-Bind in Ruche)'
                            .', SQN=0x'.substr($payload, 0, 2)
                            .', Status=0x'.substr($payload, 2, 2), "8030");

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
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $Addr, 4))
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
            $SrcAddr = substr($payload,20, 4);

            $msgDecoded = '8041/IEEE address response'
                            .', SQN='                                     .substr($payload, 0, 2)
                            .', Status='                                  .substr($payload, 2, 2)
                            .', ExtAddr='                                 .substr($payload, 4,16)
                            .', Addr='                               .substr($payload,20, 4)
                            .', NbOfAssociatedDevices='               .substr($payload,24, 2)
                            .', StartIndex='                              .substr($payload,26, 2);
            parserLog('debug', $dest.', Type='.$msgDecoded, "8041");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            if ( substr($payload, 2, 2)!= "00" ) {
                parserLog('debug', '  Don t use this data there is an error, comme info not known');
            }

            for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
                parserLog('debug', '  AssociatedDev='    .substr($payload, (28 + $i), 4) );
            }

            $data = substr($payload, 4,16);

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

            $this->msgToAbeille($dest."/".$SrcAddr, "IEEE", "Addr", $data);
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
            // <InClusterCount: uint8_t >   -> 2
            // <In cluster list: data each entry is uint16_t> -> 4
            // <OutClusterCount: uint8_t>   -> 2
            // <Out cluster list: data each entry is uint16_t> -> 4
            // Bit fields: Device version: 4 bits (bits 0-4) Reserved: 4 bits (bits4-7)

            $SQN        = substr($payload, 0, 2);
            $Status     = substr($payload, 2, 2);
            $SrcAddr    = substr($payload, 4, 4);
            $Len        = substr($payload, 8, 2);
            $EPoint     = substr($payload,10, 2);
            $profile    = substr($payload,12, 4);
            $deviceId   = substr($payload,16, 4);
            $InClusterCount = substr($payload,22, 2); // Number of input clusters

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

            $msgDecoded = '8043/Simple descriptor response'
                            .', SQN='         .$SQN
                            .', Status='      .$Status
                            .', Addr='        .$SrcAddr
                            .', Length='      .$Len
                            .', EP='          .$EPoint
                            .', ProfId='     .$profile.'/'.zgGetProfile($profile)
                            .', DevId='       .$deviceId.'/'.zgGetDevice($profile, $deviceId)
                            .', BitField='    .substr($payload,20, 2);
            parserLog('debug', $dest.', Type='.$msgDecoded, "8043");

            /* Send to monitor if required */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($msgDecoded);

            $discovering = $this->discoveringState($dest, $SrcAddr);
            if ($discovering) $this->discoverLog($msgDecoded);
            if ($Status == "83") {
                parserLog('debug', '  EP is NOT active', "8043");
                if ($discovering) $this->discoverLog('- EP is NOT active');
                return;
            }
            if ($Status != "00") {
                parserLog('debug', '  Unknown status '.$Status, "8043");
                if ($discovering) $this->discoverLog('- Unknown status '.$Status);
                return;
            }

            // Envoie l info a Abeille: Tcharp38: What for ?
            $data = zgGetDevice($profile, $deviceId);
            $this->msgToAbeille($dest."/".$SrcAddr, "SimpleDesc", "DeviceDescription", $data);

            // Decode le message dans les logs
            parserLog('debug','  InClusterCount='.$InClusterCount, "8043");
            if ($discovering) $this->discoverLog('- InClusterCount='.$InClusterCount);
            for ($i = 0; $i < (intval(substr($payload, 22, 2)) * 4); $i += 4) {
                $clustId = substr($payload, (24 + $i), 4);
                parserLog('debug', '  InCluster='.$clustId.' - '.zgGetCluster($clustId), "8043");
                if ($discovering) {
                    $this->discoverLog('- InCluster='.$clustId.' - '.zgGetCluster($clustId));
                    // parserLog('debug', '  Requesting supported attributs list for EP '.$ep.', clust '.$clustId);
                    // Tcharp38: Some devices may not support discover attribut command and return a "default response" with status 82 (unsupported general command)
                    // Tcharp38: Some devices do not respond at all (ex: Sonoff SNBZ02)
                    $this->msgToCmd("Cmd".$dest."/0000/DiscoverAttributesCommand", "address=".$SrcAddr.'&EP='.$EPoint.'&clusterId='.$clustId.'&direction=00&startAttributeId=0000&maxAttributeId=FF');
                }
            }
            parserLog('debug','  OutClusterCount='.substr($payload,24+$i, 2), "8043");
            if ($discovering) $this->discoverLog('- OutClusterCount='.substr($payload,24+$i, 2));
            for ($j = 0; $j < (intval(substr($payload, 24+$i, 2)) * 4); $j += 4) {
                parserLog('debug', '  OutCluster='.substr($payload, (24 + $i +2 +$j), 4).' - '.zgGetCluster(substr($payload, (24 + $i +2 +$j), 4)), "8043");
                if ($discovering) $this->discoverLog('- OutCluster='.substr($payload, (24 + $i +2 +$j), 4).' - '.zgGetCluster(substr($payload, (24 + $i +2 +$j), 4)));
            }

            $data = 'zigbee'.zgGetDevice($profile, $deviceId);
            if ( strlen( $data) > 1 ) {
                $this->msgToAbeille($dest."/".$SrcAddr, "SimpleDesc-".$EPoint, "DeviceDescription", $data);
            }
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
            $SrcAddr        = substr($payload, 4, 4);
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
               .', Addr='.$SrcAddr
               .', EPCount='.$EndPointCount
               .', EPList='.$endPointList;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8045");

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

            /* Monitor is required */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            /* Send to client */
            $toCli = array(
                'src' => 'parser',
                'type' => 'activeEndpoints',
                'net' => $dest,
                'addr' => $SrcAddr,
                'epList' => $endPointList
            );
            $this->msgToClient($toCli);

            if ($status != "00") {
                parserLog('debug', '  Status != 0 => ignoring');
                return;
            }

            /* Update equipement key infos */
            $this->updateEq($dest, $SrcAddr, 'epList', $endPointList);

            // parserLog('debug', '  Asking details for EP '.$EP.' [Modelisation]' );
            // $this->msgToCmd("Cmd".$dest."/0000/getManufacturerName", "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            // $this->msgToCmd("Cmd".$dest."/0000/getName", "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            // $this->msgToCmd("Cmd".$dest."/0000/getLocation", "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            // $this->msgToCmd("TempoCmd".$dest."/0000/SimpleDescriptorRequest&time=".(time() + 4), "address=".$SrcAddr.'&endPoint='.           $EP );

            // $this->actionQueue[] = array('when' => time() + 8, 'what' => 'configureNE', 'addr'=>$dest.'/'.$SrcAddr);
            // $this->actionQueue[] = array('when' => time() + 11, 'what' => 'getNE', 'addr'=>$dest.'/'.$SrcAddr);
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
            $IEEE = substr($payload, 0, 16);
            $RejoinStatus = substr($payload, 16, 2);

            $msgDecoded = '8048/Leave indication, ExtAddr='.$IEEE.', RejoinStatus='.$RejoinStatus;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8048");
            if (isset($GLOBALS["dbgMonitorAddrExt"]) && !strcasecmp($GLOBALS["dbgMonitorAddrExt"], $IEEE))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            /*
            $cmds = Cmd::byLogicalId('IEEE-Addr');
            foreach( $cmds as $cmd ) {
                if ( $cmd->execCmd() == $IEEE ) {
                    $abeille = $cmd->getEqLogic();
                    $name = $abeille->getName();
                }
            }
             */

            /* Config ongoing. Informing Abeille for EQ creation/update */
            $msg = array(
                'src' => 'parser',
                'type' => 'leaveIndication',
                'net' => $dest,
                'ieee' => $IEEE,
                'time' => time()
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
            $Channels = "";
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($payload, (22 + ($i * 2)), 2); // hexa value
                if ($i != 0)
                    $Channels .= ';';
                $Channels .= hexdec($Chan);
            }

            parserLog('debug', '  Channels='.$Channels.' address='.$addr);
            */

            $Channel = 11; // Could need to be adapted if we change the list of channel requested, at this time all of them.
            $results = array();
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($payload, (22 + ($i * 2)), 2); // hexa value
                $results[$Channel] = hexdec($Chan);
                $Channel++;
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
            $SrcAddr = substr($payload, 10 + ($NTableListCount * 42), 4); // 21 bytes per neighbour entry

            $decoded = '804E/Management LQI response'
                .', SQN='               .$SQN
                .', Status='            .$Status
                .', NTableEntries='     .$NTableEntries
                .', NTableListCount='   .$NTableListCount
                .', StartIndex='        .$StartIndex
                .', SrcAddr='           .$SrcAddr;
            parserLog('debug', $dest.', Type='.$decoded);

            /* Monitor if required */
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($decoded); // Send message to monitor

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

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

            $this->msgToLQICollector($SrcAddr, $NTableEntries, $NTableListCount, $StartIndex, $NList);
            // Tcharp38 TODO: lastComm can be updated for $SrcAddr only
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

            parserLog('debug', $dest.', Type=8060/Add a group response (ignoré)'
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
            $SrcAddr = substr($payload, 12 + ($groupCount * 4), 4);

            $decoded = '8062/Group membership'
               .', SQN='.$sqn
               .', EP='.$ep
               .', ClustId='.$clustId
               .', Capacity='.$capa
               .', GroupCount='.$groupCount
               .', Addr='.$SrcAddr;
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

            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4)) {
                monMsgFromZigate($decoded); // Send message to monitor
                monMsgFromZigate("  Groups: ".$groups); // Send message to monitor
            }

            $this->msgToAbeille($dest."/".$SrcAddr, "Group", "Membership", $groups);
        }

        function decode8063($dest, $payload, $lqi)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <Group id: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            parserLog('debug', $dest.', Type=8063/Remove a group response (ignoré)'
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

        // function decode8084($dest, $payload, $lqi) {
            // J ai eu un crash car le soft cherchait cette fonction mais elle n'est pas documentée...
            // parserLog('debug', $dest.', Type=8084/? (ignoré)');
        // }

        function decode8085($dest, $payload, $lqi)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <addr: uint16_t>             -> 4
            // <cmd: uint8>                 -> 2

            // 2: 'click', 1: 'hold', 3: 'release'

            parserLog('debug', $dest.', Type=8085/Remote button pressed (ClickHoldRelease) a group response)'
                            .', SQN='           .substr($payload, 0, 2)
                            .', EndPoint='      .substr($payload, 2, 2)
                            .', clusterId='     .substr($payload, 4, 4)
                            .', address_mode='  .substr($payload, 8, 2)
                            .', SrcAddr='   .substr($payload,10, 4)
                            .', Cmd='           .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Up";
            $AttributId     = "Down";
            $data           = substr($payload,14, 2);

            $this->whoTalked[] = $dest.'/'.$source;

            $this->msgToAbeille($dest.'/'.$source, $ClusterId, $AttributId, $data);
        }

        function decode8095($dest, $payload, $lqi)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <SrcAddr: uint16_t>          -> 4
            // <status: uint8>              -> 2

            parserLog('debug', $dest.', Type=8095/Remote button pressed (ONOFF_UPDATE) a group response)'
                            .', SQN='          .substr($payload, 0, 2)
                            .', EndPoint='     .substr($payload, 2, 2)
                            .', ClusterId='    .substr($payload, 4, 4)
                            .', Addr Mode='    .substr($payload, 8, 2)
                            .', SrcAddr='      .substr($payload,10, 4)
                            .', Status='       .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Click";
            $AttributId     = "Middle";
            $data           = substr($payload,14, 2);

            $this->whoTalked[] = $dest.'/'.$source;

            $this->msgToAbeille($dest.'/'.$source, $ClusterId, $AttributId, $data);
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

            parserLog('debug', $dest.', Type=80A0/Scene View (ignoré)'
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

            parserLog('debug', $dest.', Type=80A3/Remove All Scene (ignoré)'
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

            parserLog('debug', $dest.', Type=80A4/Store Scene Response (ignoré)'
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

                parserLog('debug', $dest.', Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT)'
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
                $clusterId  = substr($payload, 4, 4);
                $status     = substr($payload, 8, 2);
                $capacity   = substr($payload,10, 2);
                $groupID    = substr($payload,12, 4);
                $sceneCount = substr($payload,16, 2);
                $source     = substr($payload,18+$sceneCount*2, 4);

                if ($status!=0) {
                    parserLog('debug', $dest.', Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT) => Status NOT null'
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
                // $SrcAddr = substr($payload, 8, 4);

                $ClusterId = "Scene";
                $AttributId = "Membership";
                if ( $sceneId == "" ) { $data = $groupID."-none"; } else { $data = $groupID.$sceneId; }

                parserLog('debug', $dest.', Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT)'
                                .', SQN='          .$seqNumber
                                .', EndPoint='     .$endpoint
                                .', ClusterId='    .$clusterId
                                .', Status='       .$status
                                .', Capacity='     .$capacity
                                .', Source='       .$source
                                .', GroupID='      .$groupID
                                .', SceneID='      .$sceneId
                                .', Group-Scenes='.$data);

                // Je ne peux pas envoyer, je ne sais pas qui a repondu pour tester je mets l adresse en fixe d une ampoule
                $ClusterId = "Scene";
                $AttributId = "Membership";
                $this->msgToAbeille($dest."/".$source, $ClusterId, $AttributId, $data);
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
            $clusterId      = substr($payload, 4, 4);
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
                            .', ClusterId='    .$clusterId
                            .', cmd='          .$cmd
                            .', direction='    .$direction
                            .', u8Attr1='      .$attr1
                            .', u8Attr2='      .$attr2
                            .', u8Attr3='      .$attr3
                            .', Source='       .$source );

            $clusterId = "80A7";
            $AttributId = "Cmd";
            $data = $cmd;
            $this->msgToAbeille($dest."/".$source, $clusterId, $AttributId, $data);

            $clusterId = "80A7";
            $AttributId = "Direction";
            $data = $direction;
            $this->msgToAbeille($dest."/".$source, $clusterId, $AttributId, $data);
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
            $SQN                = substr($payload,  0, 2);
            $SrcAddr            = substr($payload,  2, 4);
            $EPoint             = substr($payload,  6, 2);
            $ClusterId          = substr($payload,  8, 4);
            $AttributId         = substr($payload, 12, 4);
            $AttributStatus     = substr($payload, 16, 2);
            $dataType           = substr($payload, 18, 2);
            $AttributSize       = substr($payload, 20, 4);
            $Attribut           = substr($payload, 24, hexdec($AttributSize) * 2);

            if ($type == "8100")
                $msg = '8100/Read individual attribute response';
            else
                $msg = '8102/Attribut report';
            $msg .= ', SQN='            .$SQN
                    .', Addr='          .$SrcAddr
                    .', EP='            .$EPoint
                    .', ClustId='       .$ClusterId
                    .', AttrId='        .$AttributId
                    .', AttrStatus='    .$AttributStatus
                    .', AttrDataType='  .$dataType
                    .', AttrSize='      .$AttributSize;

            parserLog('debug', $dest.', Type='.$msg, $type);
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($msg); // Send message to monitor

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

            if ($ClusterId == "0005") {
                parserLog('debug', '  Processed in 8002 => dropped');
                return;
            }

            /* Params: SrcAddr, ClustId, AttrId, Data */
            $this->msgToAbeille($dest."/".$SrcAddr, 'Link', 'Quality', $lqi);

            if ($AttributStatus == '86') {
                parserLog('debug', '  Status 86 => Unsupported attribute type ', $type);
                if ($ClusterId == "0000") {
                    switch ($AttributId) {
                    case "0004":
                        $this->updateEq($dest, $SrcAddr, 'manufacturer', false);
                        break;
                    case "0005":
                        $this->updateEq($dest, $SrcAddr, 'modelIdentifier', false);
                        break;
                    case "0010":
                        $this->updateEq($dest, $SrcAddr, 'location', false);
                        break;
                    default:
                        break;
                    }
                }

                /* Forwarding unsupported atttribute to Abeille */
                $msg = array(
                    'src' => 'parser',
                    'type' => 'attributReport',
                    'net' => $dest,
                    'addr' => $SrcAddr,
                    'ep' => $EPoint,
                    'name' => $ClusterId.'-'.$EPoint.'-'.$AttributId,
                    'value' => false, // False = unsupported
                    'time' => time(),
                    'lqi' => $lqi
                );
                $this->msgToAbeille2($msg);

                return;
            }
            if ($AttributStatus != '00') {
                parserLog('debug', '  Status != 0 => Ignored', $type);
                return;
            }

            /* Treating message in the following order
               - by clustId
               - then attribId.
               If not treated there, then next steps will be
               - direct conversion according to attrib type and return as it is
               - renaining custom cases that should be moved in first place
            */
            $data = null; // Data to push to Abeille
            if ($ClusterId == "0000") { // Basic cluster
                    // 0004: ManufacturerName
                    // 0005: ModelIdentifier
                    // 0010: Location => Used for Profalux

                if (($AttributId=="0004") || ($AttributId=="0005") || ($AttributId=="0010")) {
                    // Assuming $dataType == "42"

                    $trimmedValue = pack('H*', $Attribut);
                    $trimmedValue = str_replace(' ', '', $trimmedValue); //remove all space in names for easier filename handling
                    $trimmedValue = str_replace("\0", '', $trimmedValue); // On enleve les 0x00 comme par exemple le nom des equipements Legrand

                    if ($AttributId == "0004") { // 0x0004 ManufacturerName string
                        parserLog('debug', "  ManufacturerName='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."'");
                        $data = $trimmedValue;

                        $this->updateEq($dest, $SrcAddr, 'manufacturer', $trimmedValue);
                    } else if ($AttributId == "0005") { // 0x0005 ModelIdentifier string
                        $trimmedValue = $this->cleanModelId($trimmedValue);

                        parserLog('debug', "  ModelIdentifier='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."'");
                        $data = $trimmedValue;

                        $this->updateEq($dest, $SrcAddr, 'modelIdentifier', $trimmedValue);
                    } else if ($AttributId == "0010") { // Location
                        parserLog('debug', "  LocationDescription='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."'");
                        $data = $trimmedValue;

                        $this->updateEq($dest, $SrcAddr, 'location', $trimmedValue);
                    }
                }

                // Xiaomi lumi.sensor_86sw1 (Wall 1 Switch sur batterie)
                elseif (($AttributId == "FF01") && ($AttributSize == "001B")) {
                    parserLog("debug","  Xiaomi proprietary (Wall 1 Switch, Gaz Sensor)" );
                    // Dans le cas du Gaz Sensor, il n'y a pas de batterie alors le decodage est probablement faux.

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat = substr($payload, 80, 2);

                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent( $voltage ).', Etat=' .$etat);

                    $this->msgToAbeille($dest."/".$SrcAddr, '0006',     '01-0000', $etat, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                    return; // Nothing more to publish
                }

                // Xiaomi door sensor V2
                elseif (($AttributId == "FF01") && ($AttributSize == "001D")) {
                    // Assuming $dataType == "42"

                    // $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $voltage = hexdec(substr($Attribut, 2 * 2 + 2, 2).substr($Attribut, 2 * 2, 2));
                    // $etat           = substr($payload, 80, 2);
                    $etat = substr($Attribut, 80 - 24, 2);

                    parserLog('debug', '  Xiaomi proprietary (Door Sensor): Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', State='.$etat);

                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage));
                    $this->msgToAbeille($dest."/".$SrcAddr, '0006', '01-0000', $etat, $lqi);
                    return; // Nothing more to publish
                }

                // Xiaomi capteur temperature rond V1 / lumi.sensor_86sw2 (Wall 2 Switches sur batterie)
                elseif (($AttributId == "FF01") && ($AttributSize == "001F")) {
                    parserLog("debug","  Xiaomi proprietary (Capteur Temperature Rond/Wall 2 Switch)");

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity = hexdec( substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2) );

                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent( $voltage ).', Temp='.$temperature.', Humidity='.$humidity );

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0402', '01-0000', $temperature, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0405', '01-0000', $humidity, $lqi);
                    return; // Nothing more to publish
                }

                // Xiaomi capteur Presence V2
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102; (Attribut Report)(Processed->MQTT); SQN: 01; Src Addr : a2e1; End Point : 01; Cluster ID : 0000; Attr ID : ff01; Attr Status : 00; Attr Data Type : 42; Attr Size : 0021; Data byte list : 0121e50B0328150421a80105213300062400000000000A2100006410000B212900
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
                elseif (($AttributId == 'FF01') && ($AttributSize == "0021")) {
                    // Assuming $dataType == "42"
                    parserLog('debug', '  Xiaomi proprietary (Capteur Presence V2)');

                    $voltage = hexdec(substr($payload, 28+2, 2).substr($payload, 28, 2));
                    $lux = hexdec(substr($payload, 86+2, 2).substr($payload, 86, 2));
                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent( $voltage ).', Lux='.$lux);

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0400', '01-0000', $lux, $lqi); // Luminosite
                    return;
                }

                // Xiaomi temp/humidity/pressure square sensor
                elseif (($AttributId == 'FF01') && ($AttributSize == "0025")) {
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

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId, '$this->decoded as Volt-Temperature-Humidity', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent($voltage), $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0402', '01-0000', $temperature, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0405', '01-0000', $humidity, $lqi);
                    return;
                }

                // Xiaomi Wall Plug (Kiwi: ZNCZ02LM, rvitch: )
                elseif (($AttributId == "FF01") && (($AttributSize == "0031") || ($AttributSize == "002B"))) {
                    $logMessage = "";
                    $logMessage .= "  Xiaomi proprietary (Wall Plug)";

                    $onOff = hexdec(substr($payload, 24 + 2 * 2, 2));

                    $puissance = unpack('f', pack('H*', substr($payload, 24 + 8 * 2, 8)));
                    $puissanceValue = $puissance[1];

                    $conso = unpack('f', pack('H*', substr($payload, 24 + 14 * 2, 8)));
                    $consoValue = $conso[1];

                    $logMessage .= '  OnOff='.$onOff.', Puissance='.$puissanceValue.', Consommation='.$consoValue;
                    parserLog('debug', $logMessage);

                    // $this->msgToAbeille($SrcAddr,$ClusterId,$AttributId,'$this->decoded as OnOff-Puissance-Conso', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0006',  '-01-0000',        $onOff,             $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'tbd',   '--puissance--',   $puissanceValue,    $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'tbd',   '--conso--',       $consoValue,        $lqi);
                    return;
                }

                // Xiaomi Presence Infrarouge IR V1 / Bouton V1 Rond
                elseif (($AttributId == "FF02")) {
                    // Assuming $dataType == "42"
                    parserLog("debug","  Xiaomi proprietary (IR/button/door V1)" );

                    $voltage = hexdec(substr($payload, 24 +  8, 2).substr($payload, 24 + 6, 2));

                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent($voltage));

                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                    return;
                }

            } // End cluster 0000

            else if ($ClusterId == "0001") { // Power configuration cluster
                if ($AttributId == "0020") { // BatteryVoltage
                    $batteryVoltage = substr($Attribut, 0, 2);
                    $volt = hexdec($batteryVoltage) / 10;
                    parserLog('debug', '  BatteryVoltage='.$batteryVoltage.' => '.$volt.'V');
                }

                else if ($AttributId == "0021") { // BatteryPercentageRemaining
                    $BatteryPercent = substr($Attribut, 0, 2);
                    $percent = hexdec($BatteryPercent) / 2;
                    parserLog('debug', '  BatteryPercent='.$BatteryPercent.' => '.$percent.'%');
                }
            } // End cluster 0001/power configuration

            else if ($ClusterId == "0006") { // On/Off cluster
                if ($AttributId == "0000") { // OnOff
                    $OnOff = substr($Attribut, 0, 2);
                    parserLog('debug', '  OnOff='.$OnOff);
                }
            } // End cluster 0006/onoff

            else if ($ClusterId == "0008") { // Level control cluster
                if ($AttributId == "0000") { // CurrentLevel
                    $CurrentLevel = substr($Attribut, 0, 2);
                    parserLog('debug', '  CurrentLevel='.$CurrentLevel);
                }
            } // End cluster 0006/onoff

            else if ($ClusterId == "000C") { // Analog input cluster
                if ($AttributId == "0055") {
                    // assuming $dataType == "39"

                    if ($EPoint=="01") {
                        // Remontée puissance (instantannée) relay double switch 1
                        // On va envoyer ca sur la meme variable que le champ ff01
                        $hexNumber = substr($payload, 24, 8);
                        $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                        $bin = pack('H*', $hexNumberOrder );
                        $data = unpack("f", $bin )[1];

                        $puissanceValue = $data;
                        // $this->msgToAbeille( $SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $lqi);

                        // Relay Double
                        $this->msgToAbeille($dest."/".$SrcAddr, '000C',     '01-0055',    $puissanceValue, $lqi);
                    }
                    if (($EPoint=="02") || ($EPoint=="15")) {
                        // Remontée puissance (instantannée) de la prise xiaomi et relay double switch 2
                        // On va envoyer ca sur la meme variable que le champ ff01
                        $hexNumber = substr($payload, 24, 8);
                        $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                        $bin = pack('H*', $hexNumberOrder );
                        $data = unpack("f", $bin )[1];

                        $puissanceValue = $data;
                        // Relay Double - Prise Xiaomi
                        $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId,     $EPoint.'-'.$AttributId,    $puissanceValue, $lqi);
                    }
                    if ($EPoint=="03") {
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

            else if ($ClusterId == "0400") { // Illuminance Measurement cluster
                if ($AttributId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4);
                    $illuminance = pow(10, (hexdec($MeasuredValue) - 1) / 10000);
                    // TODO Tcharp38: Check if correct formula and what returned to Abeille
                    parserLog('debug', '  Illuminance, MeasuredValue='.$MeasuredValue.' => '.$illuminance.'Lx');
                }
            } // End cluster 0400

            else if ($ClusterId == "0402") { // Temperature Measurement cluster
                if ($AttributId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4); // int16
                    $temp = $this->decodeDataType($MeasuredValue, $dataType, false, $dataSize, $hexString) / 100;
                    parserLog('debug', '  Temp, MeasuredValue='.$MeasuredValue.' => '.$temp.'C');
                }
            } // End cluster 0402

            else if ($ClusterId == "0403") { // Pressure Measurement cluster
                if ($AttributId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4); // int16, MeasuredValue = 10 x Pressure
                    $pressure = $this->decodeDataType($Attribut, $dataType, false, $dataSize, $hexString) / 10;
                    parserLog('debug', '  Pressure, MeasuredValue='.$MeasuredValue.' => '.$pressure.'kPa');
                }
            } // End cluster 0403

            else if ($ClusterId == "0405") { // Relative Humidity cluster
                if ($AttributId == "0000") { // MeasuredValue
                    $MeasuredValue = substr($Attribut, 0, 4);
                    $humidity = hexdec($MeasuredValue) / 100;
                    parserLog('debug', '  Humidity, MeasuredValue='.$MeasuredValue.' => '.$humidity.'%');
                }
            } // End cluster 0405

            else if ($ClusterId == "0406") { // Occupancy Sensing cluster
                if ($AttributId == "0000") { // Occupancy
                    $Occupancy = substr($Attribut, 0, 2);
                    // Bit 0 specifies the sensed occupancy as follows: 1 = occupied, 0 = unoccupied.
                    parserLog('debug', '  Occupancy='.$Occupancy);
                }
            } // End cluster 0406

            /* If $data is set it means message already treated before */
            if (isset($data)) {
                $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId, $data);
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
                // $data = hexdec(substr($payload, 24, 2));
                $data = $this->decodeDataType(substr($payload, 24), $dataType, false, $dataSize, $hexString);
            }

            // else if ($dataType == "21") {
            //     $data = hexdec(substr($payload, 24, 4));
            // }
            // Utilisé pour remonter la pression par capteur Xiaomi Carré.
            // Octet 8 bits man pack ne prend pas le 8 bits, il prend à partir de 16 bits.

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

            // else if ($dataType == "39") {
            //     if (($ClusterId=="000C") && ($AttributId=="0055")  ) {
            //         if ($EPoint=="01") {
            //             // Remontée puissance (instantannée) relay double switch 1
            //             // On va envoyer ca sur la meme variable que le champ ff01
            //             $hexNumber = substr($payload, 24, 8);
            //             $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
            //             $bin = pack('H*', $hexNumberOrder );
            //             $data = unpack("f", $bin )[1];

            //             $puissanceValue = $data;
            //             // $this->msgToAbeille( $SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $lqi);

            //             // Relay Double
            //             $this->msgToAbeille($dest."/".$SrcAddr, '000C',     '01-0055',    $puissanceValue,    $lqi);
            //         }
            //         if (($EPoint=="02") || ($EPoint=="15")) {
            //             // Remontée puissance (instantannée) de la prise xiaomi et relay double switch 2
            //             // On va envoyer ca sur la meme variable que le champ ff01
            //             $hexNumber = substr($payload, 24, 8);
            //             $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
            //             $bin = pack('H*', $hexNumberOrder );
            //             $data = unpack("f", $bin )[1];

            //             $puissanceValue = $data;
            //             // Relay Double - Prise Xiaomi
            //             $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId,     $EPoint.'-'.$AttributId,    $puissanceValue,    $lqi);
            //         }
            //         if ($EPoint=="03") {
            //             // Example Cube Xiaomi
            //             // Sniffer dit Single Precision Floating Point
            //             // b9 1e 38 c2 -> -46,03
            //             // $data = hexdec(substr($payload, 24, 4));
            //             // $data = unpack("s", pack("s", hexdec(substr($payload, 24, 4))))[1];
            //             $hexNumber = substr($payload, 24, 8);
            //             $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
            //             $bin = pack('H*', $hexNumberOrder );
            //             $data = unpack("f", $bin )[1];
            //         }
            //     }
            // }

            else if ($dataType == "42") {
                // ------------------------------------------------------- Xiaomi ----------------------------------------------------------
                // Xiaomi Bouton V2 Carré
                // elseif (($AttributId == "FF01") && ($AttributSize == "001A")) {
                if (($AttributId == "FF01") && ($AttributSize == "001A")) {
                    parserLog("debug", "  Champ proprietaire Xiaomi (Bouton carré)" );

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    parserLog('debug', '  Voltage='.$voltage.' Voltage%='.$this->volt2pourcent( $voltage ));

                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                }

                // // Xiaomi capteur Presence V2
                // // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102; (Attribut Report)(Processed->MQTT); SQN: 01; Src Addr : a2e1; End Point : 01; Cluster ID : 0000; Attr ID : ff01; Attr Status : 00; Attr Data Type : 42; Attr Size : 0021; Data byte list : 0121e50B0328150421a80105213300062400000000000A2100006410000B212900
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

                // elseif (($AttributId == 'FF01') && ($AttributSize == "0021")) {
                //     parserLog('debug', '  Champ proprietaire Xiaomi (Capteur Presence V2)');

                //     $voltage        = hexdec(substr($payload, 28+2, 2).substr($payload, 28, 2));
                //     $lux            = hexdec(substr($payload, 86+2, 2).substr($payload, 86, 2));
                //     parserLog('debug', '  Volt=' .$voltage.', Volt%='.$this->volt2pourcent( $voltage ).', Lux='.$lux);

                //     $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity', $lqi);
                //     $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                //     $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                //     $this->msgToAbeille($dest."/".$SrcAddr, '0400', '01-0000', $lux, $lqi); // Luminosite

                //     // $this->msgToAbeille( $SrcAddr, '0402', '0000', $temperature,      $lqi);
                //     // $this->msgToAbeille( $SrcAddr, '0405', '0000', $humidity,         $lqi);
                //     // $this->msgToAbeille( $SrcAddr, '0403', '0010', $pression / 10,    $lqi);
                //     // $this->msgToAbeille( $SrcAddr, '0403', '0000', $pression / 100,   $lqi);
                // }

                // Xiaomi capteur Inondation
                elseif (($AttributId == 'FF01') && ($AttributSize == "0022")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Capteur d\'inondation)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat = substr($payload, 88, 2);
                    parserLog('debug', '  Volt='.$voltage.', Volt%='.$this->volt2pourcent( $voltage ).', Etat='.$etat);

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0402', '0000', $temperature,      $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0405', '0000', $humidity,         $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0403', '0010', $pression / 10,    $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0403', '0000', $pression / 100,   $lqi);
                }

                // Xiaomi bouton Aqara Wireless Switch V3 #712 (https://github.com/KiwiHC16/Abeille/issues/712)
                elseif (($AttributId == 'FF01') && ($AttributSize == "0026")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Bouton Aqara Wireless Switch V3)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Volt=' .$voltage.', Volt%='.$this->volt2pourcent( $voltage ));

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                }

                // Xiaomi Smoke Sensor
                elseif (($AttributId == 'FF01') && ($AttributSize == "0028")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Sensor Smoke)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent( $voltage ));

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                }

                // Xiaomi Cube
                // Xiaomi capteur Inondation
                elseif (($AttributId == 'FF01') && ($AttributSize == "002A")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Cube)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);

                    // $this->msgToAbeille( $SrcAddr, '0402', '0000', $temperature,      $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0405', '0000', $humidity,         $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0403', '0010', $pression / 10,    $lqi);
                    // $this->msgToAbeille( $SrcAddr, '0403', '0000', $pression / 100,   $lqi);
                }

                // Xiaomi Vibration
                elseif (($AttributId == 'FF01') && ($AttributSize == "002E")) {
                    parserLog('debug', '  Champ proprietaire Xiaomi (Vibration)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent( $voltage ));

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity', $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                }

                // Xiaomi Double Relay (ref ?)
                elseif (($AttributId == "FF01") && ($AttributSize == "0044")) {
                    $FF01 = $this->decodeFF01(substr($payload, 24, strlen($payload) - 24 - 2));
                    parserLog('debug', "  Champ proprietaire Xiaomi (Relais double)");
                    parserLog('debug', "  ".json_encode($FF01));

                    $this->msgToAbeille($dest."/".$SrcAddr, '0006', '01-0000',   $FF01["Etat SW 1 Binaire"]["valueConverted"], $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '0006', '02-0000',   $FF01["Etat SW 2 Binaire"]["valueConverted"], $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, '000C', '01-0055',   $FF01["Puissance"]["valueConverted"],         $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'tbd',  '--conso--', $FF01["Consommation"]["valueConverted"],      $lqi);
                }

                // Xiaomi Capteur Presence
                // Je ne vois pas ce message pour ce cateur et sur appui lateral il n envoie rien
                // Je mets un Attribut Size a XX en attendant. Le code et la il reste juste a trouver la taille de l attribut si il est envoyé.
                elseif (($AttributId == "FF01") && ($AttributSize == "00XX")) {
                    parserLog("debug","  Champ proprietaire Xiaomi (Bouton Carre)" );

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    parserLog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $lqi);
                    $this->msgToAbeille($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $lqi);
                }

                // ne traite pas les FF01 inconnus
                elseif ($AttributId == "FF01") {
                    parserLog("debug", "  Ignored. Unknown attribut FF01");
                    return;
                }

                // ------------------------------------------------------- Philips ----------------------------------------------------------
                // Bouton Telecommande Philips Hue RWL021
                elseif (($ClusterId == "FC00")) {

                    $buttonEventTexte = array (
                                               '00' => 'appui',
                                               '01' => 'appui maintenu',
                                               '02' => 'relâche sur appui court',
                                               '03' => 'relâche sur appui long',
                                               );
                    // parserLog("debug","  Champ proprietaire Philips Hue, decodons le et envoyons a Abeille les informations ->".pack('H*', substr($payload, 24+2, (strlen($payload) - 24 - 2)) )."<-" );
                    $button = $AttributId;
                    $buttonEvent = substr($payload, 24+2, 2 );
                    $buttonDuree = hexdec(substr($payload, 24+6, 2 ));
                    parserLog("debug", "  Champ proprietaire Philips Hue: Bouton=".$button.", Event=".$buttonEvent.", EventText=".$buttonEventTexte[$buttonEvent]." et duree: ".$buttonDuree);

                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId."-Event", $buttonEvent);
                    $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId."-Duree", $buttonDuree);
                }

                // ------------------------------------------------------- Tous les autres cas ----------------------------------------------------------
                else {
                    $data = pack('H*', $Attribut);
                }
            }

            /* Note: If $data is not set, then nothing to send to Abeille. This might be because data type is unsupported */
            else if (!isset($data)) {
                parserLog('debug', "  WARNING: Unsupported data type ".$dataType);
            }

            if (isset($data)) {
                $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId, $data);
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

            //.'; Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.'
            parserLog('debug', $dest.', Type=8101/Default response'
                            .', SQN='.$sqn
                            .', EP='.substr($payload, 2, 2)
                            .', ClustId='.$clustId.'/'.zgGetCluster($clustId)
                            .', Cmd='.substr($payload, 8, 2)
                            .', Status='.$status);
            if ($status != "00")
                parserLog('debug', '  Status '.status.' => '.zgGetZCLStatus($status));
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
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $addr, 4))
                monMsgFromZigate($msg); // Send message to monitor

            // Tcharp38: what for ? $attrId does not exist in all cases so what to report ?
            // $data = date("Y-m-d H:i:s")." Attribut: ".$attrId." Status (00: Ok, <>0: Error): ".$status;
            // $this->msgToAbeille($dest."/0000", "Network", "Report", $data);
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
            $ClusterId  = substr($payload, 4, 4);
            $SrcAddr    = substr($payload,10, 4); // Assuming short mode

            $msgDecoded = '8401/IAS zone status change notification'
               .', SQN='.substr($payload, 0, 2)
               .', EP='.$EP
               .', ClustId='.$ClusterId
               .', SrcAddrMode='.substr($payload, 8, 2)
               .', SrcAddr='.$SrcAddr
               .', ZoneStatus='.substr($payload,14, 4)
               .', ExtStatus='.substr($payload,18, 2)
               .', ZoneId='.substr($payload,20, 2)
               .', Delay='.substr($payload,22, 4);
            parserLog('debug', $dest.', Type='.$msgDecoded);
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $SrcAddr, 4))
                monMsgFromZigate($msg); // Send message to monitor

            $this->whoTalked[] = $dest.'/'.$SrcAddr;

            // On transmettre l info sur Cluster 0500 et Cmd: 0000 (Jusqu'a present on etait sur ClusterId-AttributeId, ici ClusterId-CommandId)
            $AttributId = "0000";
            $data       = substr($payload,14, 4);
            $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $EP.'-'.$AttributId, $data);
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
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $Addr, 4))
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
            $SrcEP      = substr($payload, 2, 2);
            $DestEP     = substr($payload, 4, 2);
            $DestMode   = substr($payload, 6, 2);
            $DestAddr   = substr($payload, 8, 4);
            $SQN        = substr($payload,12, 2);

            $msgDecoded = '8702/APS data confirm fail'
                //.', Status='.$status.'/'.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
               .', Status='.$status.'/'.$allErrorCode[$status][0]
               .', SrcEP='.$SrcEP
               .', DestEP='.$DestEP
               .', AddrMode='.$DestMode
               .', Addr='.$DestAddr
               .', SQN='.$SQN;
            parserLog('debug', $dest.', Type='.$msgDecoded, "8702");
            if (isset($GLOBALS["dbgMonitorAddr"]) && !strncasecmp($GLOBALS["dbgMonitorAddr"], $DestAddr, 4))
                monMsgFromZigate($msgDecoded); // Send message to monitor

            // // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            // $SrcAddr    = "Ruche";
            // $ClusterId  = "Zigate";
            // $AttributId = "8702";
            // $data       = substr($payload, 8, 4);

            // // if ( Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' ) ) $name = Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' )->getHumanName(true);
            // // message::add("Abeille","L'équipement ".$name." (".$data.") ne peut être joint." );

            // $this->msgToAbeille($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

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
