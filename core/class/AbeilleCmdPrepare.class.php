<?php

    include_once __DIR__.'/AbeilleCmdProcess.class.php';

    class AbeilleCmdPrepare extends AbeilleCmdProcess {
        // function found at https://secure.php.net/manual/fr/function.parse-str.php
        // which translate a X=x&Y=y&Z=z en array X=>x Y=>y Z=>z
        function proper_parse_str($str) {
            # result array
            $arr = array();

            # split on outer delimiter
            $pairs = explode('&', $str);

            # loop through each pair
            foreach ($pairs as $i) {
                # split into name and value
                list($name,$value) = explode('=', $i, 2);

                # if name already exists
                if(isset($arr[$name])) {
                    # stick multiple values into an array
                    if(is_array($arr[$name]) ) {
                        $arr[$name][] = $value;
                    }
                    else {
                        $arr[$name] = array($arr[$name], $value);
                    }
                }
                # otherwise, simply stick it in a scalar
                else {
                    $arr[$name] = $value;
                }
            }

            # return result array
            return $arr;
        }

        /**
         * prepareCmd()
         *
         * process commands received in the queue to be send to zigate
         * Do the mapping (L2: Level 1) with basic command and send it to processCmd() for processing at cmd level (L1: Level 1)
         *
         * @param message   message in the queue which include the cmd and data to be sent
         *
         * @return none
         *
         */
        // function prepareCmd($priority, $topic, $payload, $phpunit=0) {
        function prepareCmd($cmdMsg, $phpunit=0) {

            $priority = isset($cmdMsg['priority']) ? $cmdMsg['priority']: PRIO_NORM;
            $topic = $cmdMsg['topic'];
            $payload = $cmdMsg['payload'];


            // cmdLog("debug", "  prepareCmd(".$priority.", ".$topic.", ".$payload.")", $this->debug['prepareCmd']);

            $msg        = $payload;

            // Expecting 3 infos: <type>/<addr>/<action>
            if (sizeof(explode('/', $topic)) != 3) {
                cmdLog("debug", "    WARNING: Bad topic format (".$topic.").");
                return ;
            }
            list ($type, $address, $action) = explode('/', $topic);

            if (preg_match("(^TempoCmd)", $type)) {
                cmdLog("debug", "    Ajoutons le message a queue Tempo.", $this->debug['prepareCmd2']);
                $this->addTempoCmdAbeille($topic, $msg, $priority);
                return;
            }

            if (!preg_match("(^Cmd)", $type)) {
                cmdLog('warning', '    Msg Received: Type: {'.$type.'} <> Cmdxxxxx donc ce n est pas pour moi, no action.');
                return;
            }

            $dest = str_replace('Cmd', '',  $type); // Remove 'Cmd' prefix

            cmdLog("debug", '    Msg Received: Topic: {'.$topic.'} => '.$msg, $this->debug['prepareCmd3']);
            cmdLog("debug", '    (ln: '.__LINE__.') - Type: '.$type.' Address: '.$address.' avec Action: '.$action, $this->debug['prepareCmd3']);

            $convertOnOff = array(
                "On"      => "01",
                "Off"     => "00",
                "Toggle"  => "02",
            );

            $fields = preg_split("/[=&]+/", $msg);
            if (count($fields) > 1) {
                $parameters = $this->proper_parse_str($msg);
            }

            switch ($action) {

            /*
             * Zigbee ZDO commands
             */

            case "BindToGroup": // OBSOLETE: Use 'bind0030' instead
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $Command = array(
                                    "name" => "BindToGroup",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $address,
                                        "targetExtendedAddress"    => $parameters['targetExtendedAddress'],
                                        "targetEndpoint"           => $parameters['targetEndpoint'],
                                        "clusterID"                => $parameters['clusterID'],
                                        "reportToGroup"            => $parameters['reportToGroup'],
                                        "destinationEndpoint"      => "01",
                                    )
                                );
                break;

            /*
             * Zigbee ZCL commands
             */

            // case "ReadAttributeRequest": // OBSOLETE: Use 'readAttribute' instead
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     if (count($keywords) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }
            //     if (!isset($parameters['Proprio']) ) { $parameters['Proprio'] = "0000"; }
            //     $Command = array(
            //                         "ReadAttributeRequest"     => "1",
            //                         "priority"                 => $priority,
            //                         "dest"                     => $dest,
            //                         "address"                  => $address,
            //                         "clusterId"                => $parameters['clusterId'],   // Don't change the speeling here but in the template
            //                         "attributeId"              => $parameters['attributeId'],
            //                         "EP"                       => $parameters['EP'],
            //                         "Proprio"                  => $parameters['Proprio'],
            //                         );
            //     break;

            // case "readAttributeRequest": // OBSOLETE: Use 'readAttribute' instead
            //         $keywords = preg_split("/[=&]+/", $msg);
            //     if (count($keywords) > 1) {
            //         $params = $this->proper_parse_str($msg);
            //     }
            //     if (!isset($params['ep']) || !isset($params['clustId']) || !isset($params['attrId'])) {
            //         cmdLog('debug', '  readAttributeRequest ERROR: missing minimal infos');
            //         return;
            //     }
            //     $Command = array(
            //                     "readAttributeRequest"      => "1",
            //                     "priority"                  => $priority,
            //                     "dest"                      => $dest,
            //                     "addr"                      => $address,
            //                     "ep"                        => $params['ep'],
            //                     "clustId"                   => $params['clustId'],
            //                     "attrId"                    => $params['attrId']
            //                     );
            //     break;

            // case "ReadAttributeRequestHue": // OBSOLETE: Use 'readAttribute' instead
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     $Command = array(
            //                         "ReadAttributeRequestHue" => "1",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address" => $address,
            //                         "clusterId" => $keywords[1],
            //                         "attributeId" => $keywords[3],
            //                         );
            //     break;

            // case "ReadAttributeRequestOSRAM": // OBSOLETE: Use 'readAttribute' instead
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     $Command = array(
            //                         "ReadAttributeRequestOSRAM" => "1",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address" => $address,
            //                         "clusterId" => $keywords[1],
            //                         "attributeId" => $keywords[3],
            //                         );
            //     break;

            // case "ReadAttributeRequestMulti":
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     if (count($keywords) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }
            //     if (!isset($parameters['Proprio']) ) { $parameters['Proprio'] = "0000"; }
            //     $Command = array(
            //                         "ReadAttributeRequestMulti"     => "1",
            //                         "priority"                 => $priority,
            //                         "dest"                     => $dest,
            //                         "address"                  => $address,
            //                         "EP"                       => $parameters['EP'],
            //                         "clusterId"                => $parameters['clusterId'],   // Don't change the speeling here but in the template
            //                         "Proprio"                  => $parameters['Proprio'],
            //                         "attributeId"              => $parameters['attributeId'],
            //                         );
            //     break;

            // case "setReportSpirit": // OBSOLETE: Use 'configureReporting' instead
            case "setReport": // OBSOLETE: Use 'configureReporting2' instead
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }

                if (!isset($parameters['targetEndpoint']) )
                    $parameters['targetEndpoint'] = "01";
                if (!isset($parameters['MinInterval']) )
                    $parameters['MinInterval'] = "0000";
                else
                    $parameters['MinInterval'] = str_pad(dechex($parameters['MinInterval']), 4, 0, STR_PAD_LEFT);

                $Command = array(
                                "name" => "setReport",
                                "priority"          => $priority,
                                "dest"              => $dest,
                                "cmdParams" => array(
                                    "address"           => $address,
                                    "targetEndpoint"    => $parameters['targetEndpoint'],
                                    "clusterId"         => $parameters['ClusterId'],
                                    "attributeId"       => $parameters['AttributeId'],
                                    "minInterval"       => $parameters['MinInterval']
                                )
                            );
                if (isset($parameters["AttributeDirection"]))      { $Command['cmdParams']['attributeDirection']    = $parameters['AttributeDirection']; }
                if (isset($parameters["AttributeType"]))           { $Command['cmdParams']['attributeType']         = $parameters['AttributeType']; }
                if (isset($parameters["MaxInterval"]))             { $Command['cmdParams']['maxInterval']           = str_pad(dechex($parameters['MaxInterval']),   4,0,STR_PAD_LEFT); }
                if (isset($parameters["Change"]))                  { $Command['cmdParams']['change']                = str_pad(dechex($parameters['Change']),        2,0,STR_PAD_LEFT); }
                if (isset($parameters["Timeout"]))                 { $Command['cmdParams']['timeout']               = str_pad(dechex($parameters['Timeout']),       4,0,STR_PAD_LEFT); }
                break;

            // case "setReportRaw": // OBSOLETE: Use 'configureReporting2' instead
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }

            //     $Command = array(
            //                         "setReportRaw"             => "1",
            //                         "priority"                 => $priority,
            //                         "dest"                     => $dest,
            //                         "address"                  => $address,
            //                         "targetEndpoint"           => $parameters['targetEndpoint'],
            //                         "ClusterId"                => $parameters['ClusterId'],
            //                         "AttributeId"              => $parameters['AttributeId'],
            //                         );
            //     if (isset($parameters["AttributeDirection"]))      { $Command['AttributeDirection']    = $parameters['AttributeDirection']; }
            //     if (isset($parameters["AttributeType"]))           { $Command['AttributeType']         = $parameters['AttributeType']; }
            //     if (isset($parameters["MinInterval"]))             { $Command['MinInterval']           = str_pad(dechex($parameters['MinInterval']),   4,0,STR_PAD_LEFT); }
            //     if (isset($parameters["MaxInterval"]))             { $Command['MaxInterval']           = str_pad(dechex($parameters['MaxInterval']),   4,0,STR_PAD_LEFT); }
            //     if (isset($parameters["Change"]))                  { $Command['Change']                = str_pad(dechex($parameters['Change']),        2,0,STR_PAD_LEFT); }
            //     if (isset($parameters["Timeout"]))                 { $Command['Timeout']               = str_pad(dechex($parameters['Timeout']),       4,0,STR_PAD_LEFT); }
            //     break;

            /*
             * Unsorted yet
             */

            // Tcharp38: Should no longer be required
            // case "AnnonceManufacturer":
            //     if (strlen($msg) == 2) {
            //         $Command = array(
            //             "ReadAttributeRequest" => "1",
            //             "priority" => $priority,
            //             "dest" => $dest,
            //             "address" => $address,
            //             "clusterId" => "0000",
            //             "attributeId" => "0004",
            //             "EP" => $msg,
            //         );
            //     }
            //     break;
            // Tcharp38: Should no longer required
            // case "Annonce":
            //     if (strlen($msg) == 2) {
            //         $Command = array(
            //             "ReadAttributeRequest" => "1",
            //             "priority" => $priority,
            //             "dest" => $dest,
            //             "address" => $address,
            //             "clusterId" => "0000",
            //             "attributeId" => "0005",
            //             "EP" => $msg,
            //         );
            //     } else {
            //         if ($msg == "Default") {
            //             $Command = array(
            //                 "ReadAttributeRequest" => "1",
            //                 "priority" => $priority,
            //                 "dest" => $dest,
            //                 "address" => $address,
            //                 "clusterId" => "0000",
            //                 "attributeId" => "0005",
            //                 "EP" => "01",
            //             );
            //         }
            //         if ($msg == "Hue") {
            //             $Command = array(
            //                 "ReadAttributeRequestHue" => "1",
            //                 "priority" => $priority,
            //                 "dest" => $dest,
            //                 "address" => $address,
            //                 "clusterId" => "0000",
            //                 "attributeId" => "0005",
            //                 "EP" => "0B",
            //             );
            //         }
            //         if ($msg == "OSRAM") {
            //             $Command = array(
            //                 "ReadAttributeRequestOSRAM" => "1",
            //                 "priority" => $priority,
            //                 "dest" => $dest,
            //                 "address" => $address,
            //                 "clusterId" => "0000",
            //                 "attributeId" => "0005",
            //                 "EP" => "03",
            //             );
            //         }
            //     }
            //     break;
            // Tcharp38: No longer required
            // case "AnnonceProfalux":
            //     if ($msg == "Default") {
            //         $Command = array(
            //             "ReadAttributeRequest" => "1",
            //             "priority" => $priority,
            //             "dest" => $dest,
            //             "address" => $address,
            //             "clusterId" => "0000",
            //             "attributeId" => "0010",
            //             "EP" => "03",
            //         );
            //     }
            //     break;
            // case "OnOffRaw":
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //         $Command = array(
            //             "onoffraw"              => "1",
            //             "dest"                  => $dest,
            //             "priority"              => $priority,
            //             "addressMode"           => "02",
            //             "address"               => $address,
            //             "destinationEndpoint"   => $parameters['EP'],
            //             "action"                => $convertOnOff[$parameters['Action']],
            //         );
            //     }
            //     break;
            // case "OnOff": // OBSOLETE: Replaced by 'cmd-0006'

            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //         $Command = array(
            //             "onoff"                 => "1",
            //             "dest"                  => $dest,
            //             "priority"              => priorityUserCmd,
            //             "addressMode"           => "02",
            //             "address"               => $address,
            //             "destinationEndpoint"   => $parameters['EP'],
            //             "action"                => $convertOnOff[$parameters['Action']]
            //         );
            //     }
            //     else {
            //         $Command = array(
            //             "onoff"                 => "1",
            //             "dest"                  => $dest,
            //             "priority"              => priorityUserCmd,
            //             "addressMode"           => "02",
            //             "address"               => $address,
            //             "destinationEndpoint"   => "01",
            //             "action"                => $convertOnOff[$msg]
            //         );
            //     }
            //     break;
            // case "OnOff2":
            //     $Command = array(
            //         "onoff"                 => "1",
            //         "dest"                  => $dest,
            //         "priority"              => $priority,
            //         "addressMode"           => "02",
            //         "address"               => $address,
            //         "destinationEndpoint"   => "02",
            //         "action"                => $convertOnOff[$msg],
            //     );
            //     break;
            // case "OnOff3":
            // case "OnOffOSRAM":
            //     $Command = array(
            //         "onoff"                 => "1",
            //         "dest"                  => $dest,
            //         "priority"              => $priority,
            //         "addressMode"           => "02",
            //         "address"               => $address,
            //         "destinationEndpoint"   => "03",
            //         "action"                => $convertOnOff[$msg],
            //     );
            //     break;
            // case "OnOff4":
            //     $Command = array(
            //         "onoff"                 => "1",
            //         "dest"                  => $dest,
            //         "priority"              => $priority,
            //         "addressMode"           => "02",
            //         "address"               => $address,
            //         "destinationEndpoint"   => "04",
            //         "action"                => $convertOnOff[$msg],
            //     );
            //     break;
            // case "OnOffHue":
            //     $Command = array(
            //         "onoff"                 => "1",
            //         "dest"                  => $dest,
            //         "priority"              => $priority,
            //         "addressMode"           => "02",
            //         "address"               => $address,
            //         "destinationEndpoint"   => "0B",
            //         "action"                => $convertOnOff[$msg],
            //     );
            //     break;
            // case "commandLegrand":
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }

            //     $Command = array(
            //         "commandLegrand" => "1",
            //         "addressMode" => "02",
            //         "priority" => $priority,
            //         "dest" => $dest,
            //         "address" => $address,
            //         "destinationEndpoint" => $parameters['EP'],
            //         "Mode" => $parameters['Mode'],
            //     );
            //     break;
            case "UpGroup":
                $Command = array(
                    "name" => "UpGroup",
                    "priority" => $priority,
                    "dest" => $dest,
                    "cmdParams" => array(
                        "addressMode" => "01",
                        "address" => $address,
                        "destinationEndpoint" => "01", // Set but not send on radio
                        "step" => $msg,
                    )
                );
                break;
            case "DownGroup":
                $Command = array(
                    "name" => "DownGroup",
                    "priority" => $priority,
                    "dest" => $dest,
                    "cmdParams" => array(
                        "addressMode" => "01",
                        "address" => $address,
                        "destinationEndpoint" => "01", // Set but not send on radio
                        "step" => $msg,
                    )
                );
                break;
            // case "OnOffGroup": // Obsolete. Replaced by 'cmd-0006'
            //     if ($msg == "On") {
            //         $actionId = "01";
            //     } elseif ($msg == "Off") {
            //         $actionId = "00";
            //     } elseif ($msg == "Toggle") {
            //         $actionId = "02";
            //     }
            //     $Command = array(
            //         "onoff"                 => "1",
            //         "addressMode"           => "01",
            //         "priority"              => $priority,
            //         "dest"                  => $dest,
            //         "address"               => $address,
            //         "destinationEndpoint"   => "01", // Set but not send on radio
            //         "action"                => $actionId,
            //     );
            //     break;
            // case "OnOffGroupBroadcast": // Obsolete. Replaced by 'cmd-0006'
            //     if ($msg == "On") {
            //         $actionId = "01";
            //     } elseif ($msg == "Off") {
            //         $actionId = "00";
            //     } elseif ($msg == "Toggle") {
            //         $actionId = "02";
            //     }
            //     $Command = array(
            //         "onoff"                 => "1",
            //         "addressMode"           => "04",
            //         "priority"              => $priority,
            //         "dest"                  => $dest,
            //         "address"               => $address,
            //         "destinationEndpoint"   => "01", // Set but not send on radio
            //         "action"                => $actionId,
            //     );
            //     break;
            case "OnOffGroupTimed":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }

                if ($parameters['action'] == "On") {
                    $actionId = "01";
                } elseif ($parameters['action'] == "Off") {
                    $actionId = "00";
                }

                $Command = array(
                                "name" => "OnOffTimed",
                                "priority"             => $priority,
                                "dest"                 => $dest,
                                "cmdParams" => array(
                                    "address"              => $address,
                                    "addressMode"          => "01",
                                    "destinationEndpoint"  => "01", // Set but not send on radio
                                    "action"               => $actionId,
                                    "onTime"               => $parameters['onTime'],
                                    "offWaitTime"          => $parameters['offWaitTime'],
                                )
                            );
                break;
            case "WriteAttributeRequest":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $Command = array(
                                "name" => "WriteAttributeRequest",
                                "priority"          => $priority,
                                "dest"              => $dest,
                                "cmdParams" => array(
                                    "address"           => $address,
                                    "Proprio"           => $parameters['Proprio'],
                                    "clusterId"         => $parameters['clusterId'],
                                    "attributeId"       => $parameters['attributeId'],
                                    "attributeType"     => $parameters['attributeType'],
                                    "value"             => $parameters['value'],
                                )
                            );
                break;
            case "WriteAttributeRequestVibration":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $Command = array(
                                    "name" => "WriteAttributeRequestVibration",
                                    "priority"         => $priority,
                                    "dest"             => $dest,
                                    "cmdParams" => array(
                                        "address"          => $address,
                                        "Proprio"          => $parameters['Proprio'],
                                        "clusterId"        => $parameters['clusterId'],
                                        "attributeId"      => $parameters['attributeId'],
                                        "attributeType"    => $parameters['attributeType'],
                                        "value"            => $parameters['value'],
                                        "repeat"           => $parameters['repeat'],
                                    )
                                );
                break;
            case "WriteAttributeRequestHostFlag":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $consigne = $parameters['value'];
                $consigneHex = $consigne[4].$consigne[5].$consigne[2].$consigne[3].$consigne[0].$consigne[1];
                $Command = array(
                                    "name" => "WriteAttributeRequest",
                                    "priority"         => $priority,
                                    "dest"             => $dest,
                                    "cmdParams" => array(
                                        "address"          => $address,
                                        "Proprio"          => $parameters['Proprio'],
                                        "clusterId"        => $parameters['clusterId'],
                                        "attributeId"      => $parameters['attributeId'],
                                        "attributeType"    => $parameters['attributeType'],
                                        "value"            => $consigneHex,
                                    )
                                );
                break;
            case "WriteAttributeRequestTemperatureSpiritConsigne":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $consigne = sprintf("%04X", $parameters['value']*100 );
                $consigneHex = $consigne[2].$consigne[3].$consigne[0].$consigne[1];
                $Command = array(
                                    "name" => "WriteAttributeRequest",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $address,
                                        "Proprio"                  => $parameters['Proprio'],
                                        "clusterId"                => $parameters['clusterId'],
                                        "attributeId"              => $parameters['attributeId'],
                                        "attributeType"            => $parameters['attributeType'],
                                        "value"                    => $consigneHex,
                                    )
                                );
                break;
            case "WriteAttributeRequestValveSpiritConsigne":
            case "WriteAttributeRequestTrvSpiritMode":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $consigne = sprintf("%02X", $parameters['value'] );
                $consigneHex = $consigne;
                $Command = array(
                                    "name" => "WriteAttributeRequest",
                                    "priority"         => $priority,
                                    "dest"             => $dest,
                                    "cmdParams" => array(
                                        "address"          => $address,
                                        "Proprio"          => $parameters['Proprio'],
                                        "clusterId"        => $parameters['clusterId'],
                                        "attributeId"      => $parameters['attributeId'],
                                        "attributeType"    => $parameters['attributeType'],
                                        "value"            => $consigneHex,
                                    )
                                );
                break;
            case "WriteAttributeRequestGeneric":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $valuePrepared = $parameters['value'];
                // Example: set Temperature Danfoss Radiator Head
                if ($parameters['attributeType'] == '29')  {
                    $valuePrepared = sprintf("%04X", $parameters['value'] * 100);
                    $valuePrepared = $valuePrepared[2] . $valuePrepared[3] . $valuePrepared[0] . $valuePrepared[1];
                } else if ($parameters['attributeType'] == '30')  {
                    $valuePrepared = sprintf("%02X", $parameters['value']);
                } else {
                    cmdLog('debug', '  WriteAttributeRequestGeneric ERROR: unsupported attribute type '.$parameters['attributeType']);
                    return;
                }
                $Command = array(
                                    "name" => "WriteAttributeRequestGeneric",
                                    "priority"         => $priority,
                                    "dest"             => $dest,
                                    "cmdParams" => array(
                                        "address"          => $address,
                                        "EP"               => $parameters['EP'],
                                        "Proprio"          => $parameters['Proprio'],
                                        "clusterId"        => $parameters['clusterId'],
                                        "attributeId"      => $parameters['attributeId'],
                                        "attributeType"    => $parameters['attributeType'],
                                        "value"            => $valuePrepared,
                                    )
                                );
                break;
            case "WriteAttributeRequestActivateDimmer":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $Command = array(
                                    "name" => "WriteAttributeRequestActivateDimmer",
                                    "priority"         => $priority,
                                    "dest"             => $dest,
                                    "cmdParams" => array(
                                        "address"          => $address,
                                        "clusterId"        => $parameters['clusterId'],
                                        "attributeId"      => $parameters['attributeId'],
                                        "attributeType"    => $parameters['attributeType'],
                                        "value"            => $parameters['value'],
                                    )
                                );
                break;

            case "setLevelRaw":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                    $Command = array(
                        "name"      => "setLevelRaw",
                        "priority"  => $priority,
                        "dest"      => $dest,
                        "cmdParams" => array(
                            "addr"      => $address,
                            "EP"        => $parameters['EP'],
                            "Level"     => $parameters['Level'],
                            "duration"  => $parameters['duration'],
                        )
                    );
                }
                break;
            case "setLevelOSRAM":
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                    "name" => "setLevel",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "addr" => $address,
                                        "ep" => "03",
                                        // "Level" => intval($keywords[1] * 255 / 100),
                                        "level" => $keywords[1],
                                        "duration" => $keywords[3],
                                    )
                                );
                break;
            case "setLevelVolet":
                $eqLogic = eqLogic::byLogicalId($dest."/".$address, "Abeille" );
                $a = $eqLogic->getConfiguration('paramA', 0);
                $b = $eqLogic->getConfiguration('paramB', 1);
                $c = $eqLogic->getConfiguration('paramC', 0);

                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $levelSlider = $parameters['Level'];                // Valeur entre 0 et 100
                $levelSliderPourcent = $levelSlider/100;            // Valeur entre 0 et 1
                $levelPourcent = $a * $levelSliderPourcent * $levelSliderPourcent + $b * $levelSliderPourcent + $c;
                $level = $levelPourcent * 255;
                $level = min(max(round($level), 0), 255);
                // $Command = array(
                //     "name" => "setLevelRaw",
                //     "priority" => $priority,
                //     "dest" => $dest,
                //     "addr" => $address,
                //     "EP" => "01",
                //     "Level" => $level,
                //     "duration" => $parameters['duration'],
                // );
                $Command = array(
                    "name" => "cmd-0008",
                    "priority" => $priority,
                    "dest" => $dest,
                    "cmdParams" => array(
                        "addr" => $address,
                        "ep" => "01",
                        "cmd" => "04", // Move to Level with On/Off
                        "level" => $level,
                        "duration" => $parameters['duration'],
                    )
                );
                break;
            case "moveToLiftAndTiltBSO":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = $this->proper_parse_str($msg);
                    }
                    $Command = array(
                                    "name" => "moveToLiftAndTiltBSO",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "addressMode" => "02",
                                        "address" => $address,
                                        "destinationEndpoint" => "01",
                                        "lift"        => $parameters['lift'],
                                        "inclinaison" => $parameters['inclinaison'],        // Valeur entre 0 et 90
                                        "duration"    => $parameters['duration'],              // FFFF to have max speed of tilt
                                    )
                                );
                break;
            // Obsolete: Use 'cmd-0008' + 'cmd=07'
            // case "setLevelStop":
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     $Command = array(
            //                         "setLevelStop" => "1",
            //                         "addressMode" => "02",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address" => $address,
            //                         "sourceEndpoint" => "01",
            //                         "destinationEndpoint" => "01",
            //                         );
            //     break;
            // Seems unused. Anyway, use 'cmd-0008' + 'cmd=07' instead.
            // case "setLevelStopHue":
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     $Command = array(
            //                         "setLevelStop" => "1",
            //                         "addressMode" => "02",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address" => $address,
            //                         "sourceEndpoint" => "01",
            //                         "destinationEndpoint" => "0B",
            //                         );
            //     break;
            case "setLevelHue":
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                    "name" => "setLevel",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "addr" => $address,
                                        "ep" => "0B",
                                        // "Level" => intval($keywords[1] * 255 / 100),
                                        "level" => $keywords[1],
                                        "duration" => $keywords[3],
                                    )
                                );
                break;
            case "setLevelGroup":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $Command = array(
                                    "name"                 => "setLevel",
                                    "priority"             => priorityUserCmd,
                                    "dest"                 => $dest,
                                    "cmdParams" => array(
                                        "addrMode"  => "01",
                                        "addr"      => $address,
                                        "ep"        => "01",
                                        "level"     => intval($parameters['Level']),
                                        "duration"  => $parameters['duration'],
                                    )
                                );
                break;
            // case "setColour":
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //         $Command = array(
            //                             "setColour"            => "1",
            //                             "addressMode"          => "02",
            //                             "priority" => $priority,
            //                             "dest" => $dest,
            //                             "address"              => $address,
            //                             "X"                    => $parameters['X'],
            //                             "Y"                    => $parameters['Y'],
            //                             "destinationEndPoint"  => $parameters['EP'],
            //                             "duration"             => $parameters['duration'],
            //                             );
            //     }
            //     break;
            case "setColourGroup":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = $this->proper_parse_str($msg);
                        $Command = array(
                                            "name"                 => "setColour",
                                            "priority"             => priorityUserCmd,
                                            "dest"                 => $dest,
                                            "cmdParams" => array(
                                                "addr"                 => $address,
                                                "addressMode"          => "01",
                                                "X"                    => $parameters['X'],
                                                "Y"                    => $parameters['Y'],
                                                "destinationEndPoint"  => "01", // not needed as group
                                            )
                                        );
                    }
                    break;
            case "setColourRGB":
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                    if (strlen($parameters['color']) == 7) {
                        $parameters['color'] = substr($parameters['color'], 1);
                    }
                }

                // Si message vient de Abeille alors le parametre est: RRVVBB
                // Si le message vient de Homebridge: {"color":"#00FF11"}, j'extrais la partie interessante.
                $rouge = hexdec(substr($parameters['color'],0,2));
                $vert  = hexdec(substr($parameters['color'],2,2));
                $bleu  = hexdec(substr($parameters['color'],4,2));

                $this->msgToAbeille('Abeille/'.$address.'/colorRouge', $rouge*100/255      );
                $this->msgToAbeille('Abeille/'.$address.'/colorVert',  $vert*100/255       );
                $this->msgToAbeille('Abeille/'.$address.'/colorBleu',  $bleu*100/255       );
                $this->msgToAbeille('Abeille/'.$address.'/ColourRGB',  $parameters['color']);

                $Command = array(
                                    "name" => "setColourRGB",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "address" => $address,
                                        "R" => $rouge,
                                        "G" => $vert,
                                        "B" => $bleu,
                                        "destinationEndPoint" => $parameters['EP'],
                                    )
                                );
                break;
            case "setRouge":
                $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();

                if ($rouge=="" ) { $rouge = 1;   }
                if ($vert=="" )  { $vert = 1;    }
                if ($bleu=="" )  { $bleu = 1;    }

                $this->msgToAbeille($dest.'/'.$address.'/colorRouge', $msg );

                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }

                $Command = array(
                                    "name" => "setColourRGB",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "address" => $address,
                                        "R" => $parameters['color']/100*255,
                                        "G" => $vert/100*255,
                                        "B" => $bleu/100*255,
                                        "destinationEndPoint" => $parameters['EP'],
                                    )
                                );
                break;
            case "setVert":
                $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();

                if ($rouge=="" ) { $rouge = 1;   }
                if ($vert=="" )  { $vert = 1;    }
                if ($bleu=="" )  { $bleu = 1;    }

                $this->msgToAbeille($dest.'/'.$address.'/colorVert', $msg );

                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }

                $Command = array(
                    "name" => "setColourRGB",
                    "priority" => $priority,
                    "dest" => $dest,
                    "cmdParams" => array(
                        "address" => $address,
                        "R" => $rouge/100*255,
                        "G" => $parameters['color']/100*255,
                        "B" => $bleu/100*255,
                        "destinationEndPoint" => $parameters['EP'],
                    )
                );
                break;
            case "setBleu":
                $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();

                if ($rouge=="" ) { $rouge = 1;   }
                if ($vert=="" )  { $vert = 1;    }
                if ($bleu=="" )  { $bleu = 1;    }

                $this->msgToAbeille($dest.'/'.$address.'/colorBleu', $msg);

                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }

                $Command = array(
                    "name" => "setColourRGB",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "address" => $address,
                                        "R" => $rouge/100*255,
                                        "G" => $vert/100*255,
                                        "B" => $parameters['color']/100*255,
                                        "destinationEndPoint" => $parameters['EP'],
                                    )
                                );
                break;
            case "setColourHue":
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                    "name" => "setColour",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "address" => $address,
                                        "X" => $keywords[1],
                                        "Y" => $keywords[3],
                                        "destinationEndPoint" => "0B",
                                    )
                                );
                break;
            case "setColourOSRAM":
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                    "name" => "setColour",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "address" => $address,
                                        "X" => $keywords[1],
                                        "Y" => $keywords[3],
                                        "destinationEndPoint" => "03",
                                    )
                                );
                break;
            // case "setTemperature":
            //     // T°K   Hex sent  Dec eq
            //     // 2200	 01C6	   454
            //     // 2700	 0172	   370
            //     // 4000	 00FA	   250
            //     // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }
            //     $Command = array(
            //                         "setTemperature"       => "1",
            //                         "addressMode"          => "02",
            //                         "priority"             => $priority,
            //                         "dest"                 => $dest,
            //                         "address"              => $address,
            //                         "temperature"          => sprintf("%04s", dechex(intval(1000000/$parameters['slider'])) ),
            //                         "destinationEndPoint"  => $parameters['EP'],
            //                         );
            //     break;
            case "setTemperatureGroup":
                // T°K   Hex sent  Dec eq
                // 2200     01C6       454
                // 2700     0172       370
                // 4000     00FA       250
                // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }
                $Command = array(
                                    "name"                 => "setTemperature",
                                    "priority"             => $priority,
                                    "dest"                 => $dest,
                                    "cmdParams" => array(
                                        "addressMode"          => "01",
                                        "addr"                 => $address,
                                        "slider"               => sprintf("%04s", dechex(intval(1000000/$parameters['slider'])) ),
                                        "EP"                   => $parameters['EP'],
                                    )
                                );
                break;


            case "writeAttributeRequestIAS_WD":
                $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = $this->proper_parse_str($msg);
                    }

                $Command = array(
                                    "name" => "writeAttributeRequestIAS_WD",
                                    "priority"                        => $priority,
                                    "dest"                            => $dest,
                                    "cmdParams" => array(
                                        "address"                         => $address,
                                        "mode"                            => $parameters['mode'],
                                        "duration"                        => $parameters['duration'],
                                    )
                );
                break;

            /*
             * Cluster 0001/Identify support
             */

            // case "identifySend":
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }
            //     $Command = array(
            //                         "identifySend" => "1",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address" => $address,
            //                         "duration" => $parameters['duration'],
            //                         "DestinationEndPoint" => $parameters['EP'],
            //                         );
            //     break;
            // case "identifySendHue":
            //     $keywords = preg_split("/[=&]+/", $msg);
            //     $Command = array(
            //                         "identifySend" => "1",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address" => $address,
            //                         "duration" => "0010",
            //                         "DestinationEndPoint" => "0B",
            //                         );
            //     break;

            /*
             * Cluster 0004/Groups support
             */

            // case "removeGroup":
            //     // if ($parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
            //     if (strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
            //     $Command = array(
            //                         "removeGroup"              => "1",
            //                         "priority"                 => $priority,
            //                         "dest"                     => $dest,
            //                         "address"                  => $parameters['address'],
            //                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
            //                         "groupAddress"             => $parameters['groupAddress'],
            //                         );
            //     break;

            /*
             * Cluster 0005/Scenes support
             */

            case "viewScene":
                if (!isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                    "name" => "viewScene",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $parameters['address'],
                                        "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                        "groupID"                  => $parameters['groupID'],
                                        "sceneID"                  => $parameters['sceneID'],
                                    )
                                );
                break;

            case "storeScene":
                if (!isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                    "name" => "storeScene",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $parameters['address'],
                                        "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                        "groupID"                  => $parameters['groupID'],
                                        "sceneID"                  => $parameters['sceneID'],
                                    )
                                );
                break;

            case "recallScene":
                if (!isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                    "name" => "recallScene",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "address"                  => $parameters['address'],
                                    "cmdParams" => array(
                                        "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                        "groupID"                  => $parameters['groupID'],
                                        "sceneID"                  => $parameters['sceneID'],
                                    )
                                );
                break;

            case "sceneGroupRecall":
                cmdLog('debug', '  sceneGroupRecall msg: ' . $msg );
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = $this->proper_parse_str($msg);
                }

                $Command = array(
                                    "name" => "sceneGroupRecall",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "groupID"                  => $parameters['groupID'],
                                        "sceneID"                  =>  $parameters['sceneID'],
                                    )
                                );
                break;

            // case "sceneGroupRecall":
            //     cmdLog('debug', '  sceneGroupRecall msg: ' . $msg );
            //     $fields = preg_split("/[=&]+/", $msg);
            //     if (count($fields) > 1) {
            //         $parameters = $this->proper_parse_str($msg);
            //     }

            //     $Command = array(
            //                         "sceneGroupRecall"         => "1",
            //                         "priority"                 => $priority,
            //                         "dest"                     => $dest,
            //                         "groupID"                  => $parameters['groupID'],
            //                         "sceneID"                  =>  $parameters['sceneID'],
            //                         );
            //     break;

            case "addScene":
                if (!isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                    "name" => "addScene",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $parameters['address'],
                                        "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                        "groupID"                  => $parameters['groupID'],
                                        "sceneID"                  => $parameters['sceneID'],
                                        "sceneName"                => $parameters['sceneName'],
                                    )
                                );
                break;

            case "getSceneMembership":
                if (!isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                    "name" => "getSceneMembership",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $parameters['address'],
                                        "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                        "groupID"                  => $parameters['groupID'],
                                    )
                                );
                break;

            case "removeSceneAll":
                if (!isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                    "name" => "removeSceneAll",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $parameters['address'],
                                        "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                        "groupID"                  => $parameters['groupID'],
                                    )
                                );
                break;

            /*
             * Cluster 0102/Window covering support
             */

            // case "WindowsCovering":
            //     $fields = preg_split("/[=&]+/", $msg);
            //         if (count($fields) > 1) {
            //             $parameters = $this->proper_parse_str($msg);
            //         }

            //     $Command = array(
            //                         "WindowsCovering"          => "1",
            //                         "priority" => $priority,
            //                         "dest" => $dest,
            //                         "address"                  => $address,
            //                         "clusterCommand"           => $parameters['clusterCommand'],
            //     );
            //     break;

            case "WindowsCoveringLevel":
                $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = $this->proper_parse_str($msg);
                    }

                $Command = array(
                                    "name" => "WindowsCoveringLevel",
                                    "priority"                 => $priority,
                                    "dest"                     => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $address,
                                        "clusterCommand"           => $parameters['clusterCommand'],
                                        "liftValue"                => sprintf("%02s",dechex($parameters['liftValue'])),
                                    )
                );
                break;

            case "WindowsCoveringGroup":
                $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = $this->proper_parse_str($msg);
                    }

                $Command = array(
                                    "name" => "WindowsCoveringGroup",
                                    "priority" => $priority,
                                    "dest" => $dest,
                                    "cmdParams" => array(
                                        "address"                  => $address,
                                        "clusterCommand"           => $parameters['clusterCommand'],
                                    )
                );
                break;

            default:
                // cmdLog("debug", '  No prepare function. Forwarding cmd to AbeilleCmdProcess.');
                // cmdLog("debug", '  msg='.json_encode($msg));

                /* Tcharp38 notes:
                   This part forward directly message to 'AbeilleCmdProcess'.
                   Should be the default to later remove this 'AbeilleCmdPrepare' step.
                   Incoming messages have 2 parts:
                     - Target device (ex: CmdAbeille1/FE70/discoverAttributesExt) => address + command
                     - Command params (ex: ep=01&clustId=0000&start=00)
                   During this conversion process a key point is 'addr'. Device addr is taken if 'addr' is not already
                   part of parameters.
                 */

                 list ($type, $address, $action) = explode('/', $topic);
                 $net = str_replace('Cmd', '',  $type); // Remove 'Cmd' prefix

                 $Command = array(
                    // $action => $action, // Tcharp38: Legacy. Replaced by 'name' field. To be removed at some point.
                    "name" => $action,
                    "cmdParams" => [],
                    "priority" => isset($cmdMsg['priority']) ? $cmdMsg['priority']: PRIO_NORM,
                    "repeat" => isset($cmdMsg['repeat']) ? $cmdMsg['repeat']: 0,
                    "dest" => $net,
                );

                // Splitting payload by '&' then by '='
                $addrFound = false;
                if ($payload != "") {
                    $couples = preg_split("/[&]+/", $payload);
                    // cmdLog("debug", '  couples='.json_encode($couples));
                    for($i = 0; $i < count($couples); $i++) {
                        $keywords = preg_split("/[=]+/", $couples[$i]);
                        // cmdLog("debug", '  keywords='.json_encode($keywords));
                        if (isset($keywords[1]))
                            $Command['cmdParams'][$keywords[0]] = $keywords[1];
                        else
                            $Command['cmdParams'][$keywords[0]] = 1; // Special case. Ex: CmdAbeille1/0000/SetPermit -> Inclusion
                        if ($keywords[0] == "addr")
                            $addrFound = true;
                    }
                }
                if ($addrFound == false)
                    $Command['cmdParams']['addr'] = $address;

                break;
            } // switch action

            if (!isset($Command)) {
                cmdLog('debug', '  ERROR: Unknown command ! (topic='.$topic.')');
            } else {
                // cmdLog('debug', '  L2 - calling processCmd with Command parameters: '.json_encode($Command), $this->debug['prepareCmd']);
                if ($phpunit) return $Command;
                $this->processCmd($Command);
            }

            return;
        }
    }
?>