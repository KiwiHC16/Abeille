<?php

include_once __DIR__.'/AbeilleCmdProcess.class.php';

class AbeilleCmdPrepare extends AbeilleCmdProcess {

    function procmsg( $message ) {

        $this->deamonlog("debug", "  L2 - procmsg(".json_encode($message).")");

        $topic      = $message->topic;
        $msg        = $message->payload;
        $priority   = $message->priority;

        $this->deamonlog("debug", "  L2 - procmsg(".json_encode($topic)." , ".json_encode($msg)." , ".json_encode($priority).")");

        if (sizeof(explode('/', $topic)) != 3) {
            $this->deamonlog("error", "procmsg(). Mauvais format de message reçu.");
            return ;
        }

        list ($type, $address, $action) = explode('/', $topic);

        if (preg_match("(^TempoCmd)", $type)) {
            $this->deamonlog("debug", "  Ajoutons le message a queue Tempo.", $this->debug['procmsg2']);
            $this->addTempoCmdAbeille($topic, $msg, $priority);
            return;
        }

        if (!preg_match("(^Cmd)", $type)) {
            $this->deamonlog('warning', '  Msg Received: Type: {'.$type.'} <> Cmdxxxxx donc ce n est pas pour moi, no action.');
            return;
        }

        $dest = str_replace( 'Cmd', '',  $type );

        $this->deamonlog("debug", '  Msg Received: Topic: {'.$topic.'} => '.$msg, $this->debug['procmsg3']);
        $this->deamonlog("debug", '  (ln: '.__LINE__.') - Type: '.$type.' Address: '.$address.' avec Action: '.$action, $this->debug['procmsg3']);

        // Jai les CmdAbeille/Ruche et les CmdAbeille/shortAdress que je dois gérer un peu differement les uns des autres.

        if ($address != "Ruche") {
            $this->deamonlog("debug", '  Address != Ruche', $this->debug['procmsg3']);

            $convertOnOff = array(
                "On"      => "01",
                "Off"     => "00",
                "Toggle"  => "02",
            );

            switch ($action) {
                    //----------------------------------------------------------------------------
                case "managementNetworkUpdateRequest":
                    $Command = array(
                        "managementNetworkUpdateRequest" => "1",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $address,
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "Mgmt_Rtg_req":
                    $Command = array(
                        "Mgmt_Rtg_req" => "1",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $address,
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "AnnonceManufacturer":
                        if (strlen($msg) == 2) {
                            // $this->deamonlog('info', 'Preparation de la commande annonce pour EP');
                            $Command = array(
                                "ReadAttributeRequest" => "1",
                                "priority" => $priority,
                                "dest" => $dest,
                                "address" => $address,
                                "clusterId" => "0000",
                                "attributeId" => "0004",
                                "EP" => $msg,
                            );
                        }
                    break;
                    //----------------------------------------------------------------------------
                case "Annonce":
                    if (strlen($msg) == 2) {
                        // $this->deamonlog('info', 'Preparation de la commande annonce pour EP');
                        $Command = array(
                            "ReadAttributeRequest" => "1",
                            "priority" => $priority,
                            "dest" => $dest,
                            "address" => $address,
                            "clusterId" => "0000",
                            "attributeId" => "0005",
                            "EP" => $msg,
                        );
                    }
                    else {
                        if ($msg == "Default") {
                            // $this->deamonlog('info', 'Preparation de la commande annonce pour default');
                            $Command = array(
                                "ReadAttributeRequest" => "1",
                                "priority" => $priority,
                                "dest" => $dest,
                                "address" => $address,
                                "clusterId" => "0000",
                                "attributeId" => "0005",
                                "EP" => "01",
                            );
                        }
                        if ($msg == "Hue") {
                            // $this->deamonlog('info', 'Preparation de la commande annonce pour Hue');
                            $Command = array(
                                "ReadAttributeRequestHue" => "1",
                                "priority" => $priority,
                                "dest" => $dest,
                                "address" => $address,
                                "clusterId" => "0000",
                                "attributeId" => "0005",
                                "EP" => "0B",
                            );
                        }
                        if ($msg == "OSRAM") {
                            // $this->deamonlog('info', 'Preparation de la commande annonce pour OSRAM');
                            $Command = array(
                                "ReadAttributeRequestOSRAM" => "1",
                                "priority" => $priority,
                                "dest" => $dest,
                                "address" => $address,
                                "clusterId" => "0000",
                                "attributeId" => "0005",
                                "EP" => "03",
                            );
                        }
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "AnnonceProfalux":
                    if ($msg == "Default") {
                        $this->deamonlog('info', 'Preparation de la commande annonce pour Profalux');
                        $Command = array(
                            "ReadAttributeRequest" => "1",
                            "priority" => $priority,
                            "dest" => $dest,
                            "address" => $address,
                            "clusterId" => "0000",
                            "attributeId" => "0010",
                            "EP" => "03",
                        );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffRaw":
                    if ($this->debug['procmsg3']) {
                        $this->deamonlog("debug", '  OnOffRaw with dest: '.$dest);
                    }
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                            "onoffraw"              => "1",
                            "dest"                  => $dest,
                            "priority"              => $priority,
                            "addressMode"           => "02",
                            "address"               => $address,
                            "destinationEndpoint"   => $parameters['EP'],
                            "action"                => $convertOnOff[$parameters['Action']],
                        );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff":
                    if ($this->debug['procmsg3']) {
                        $this->deamonlog("debug", '  OnOff with dest: '.$dest);
                    }
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                            "onoff"                 => "1",
                            "dest"                  => $dest,
                            "priority"              => $priority,
                            "addressMode"           => "02",
                            "address"               => $address,
                            "destinationEndpoint"   => $parameters['EP'],
                            "action"                => $convertOnOff[$parameters['Action']],
                        );
                    }
                    else {
                        $Command = array(
                            "onoff"                 => "1",
                            "dest"                  => $dest,
                            "priority"              => $priority,
                            "addressMode"           => "02",
                            "address"               => $address,
                            "destinationEndpoint"   => "01",
                            "action"                => $convertOnOff[$msg],
                        );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff2":
                    $Command = array(
                        "onoff"                 => "1",
                        "dest"                  => $dest,
                        "priority"              => $priority,
                        "addressMode"           => "02",
                        "address"               => $address,
                        "destinationEndpoint"   => "02",
                        "action"                => $convertOnOff[$msg],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff3":
                case "OnOffOSRAM":
                    $Command = array(
                        "onoff"                 => "1",
                        "dest"                  => $dest,
                        "priority"              => $priority,
                        "addressMode"           => "02",
                        "address"               => $address,
                        "destinationEndpoint"   => "03",
                        "action"                => $convertOnOff[$msg],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff4":
                    $Command = array(
                        "onoff"                 => "1",
                        "dest"                  => $dest,
                        "priority"              => $priority,
                        "addressMode"           => "02",
                        "address"               => $address,
                        "destinationEndpoint"   => "04",
                        "action"                => $convertOnOff[$msg],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffHue":
                    $Command = array(
                        "onoff"                 => "1",
                        "dest"                  => $dest,
                        "priority"              => $priority,
                        "addressMode"           => "02",
                        "address"               => $address,
                        "destinationEndpoint"   => "0B",
                        "action"                => $convertOnOff[$msg],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "commandLegrand":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                        "commandLegrand" => "1",
                        "addressMode" => "02",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $address,
                        "destinationEndpoint" => $parameters['EP'],
                        "Mode" => $parameters['Mode'],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "UpGroup":
                    $Command = array(
                        "UpGroup" => "1",
                        "addressMode" => "01",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $address,
                        "destinationEndpoint" => "01", // Set but not send on radio
                        "step" => $msg,
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "DownGroup":
                    $Command = array(
                        "DownGroup" => "1",
                        "addressMode" => "01",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $address,
                        "destinationEndpoint" => "01", // Set but not send on radio
                        "step" => $msg,
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffGroup":
                    if ($msg == "On") {
                        $actionId = "01";
                    } elseif ($msg == "Off") {
                        $actionId = "00";
                    } elseif ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                        "onoff"                 => "1",
                        "addressMode"           => "01",
                        "priority"              => $priority,
                        "dest"                  => $dest,
                        "address"               => $address,
                        "destinationEndpoint"   => "01", // Set but not send on radio
                        "action"                => $actionId,
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffGroupBroadcast":
                    if ($msg == "On") {
                        $actionId = "01";
                    } elseif ($msg == "Off") {
                        $actionId = "00";
                    } elseif ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                        "onoff"                 => "1",
                        "addressMode"           => "04",
                        "priority"              => $priority,
                        "dest"                  => $dest,
                        "address"               => $address,
                        "destinationEndpoint"   => "01", // Set but not send on radio
                        "action"                => $actionId,
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffGroupTimed":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str($msg);
                    }

                    if ($parameters['action'] == "On") {
                        $actionId = "01";
                    } elseif ($parameters['action'] == "Off") {
                        $actionId = "00";
                    }

                    $Command = array(
                                    "OnOffTimed"           => "1",
                                    "addressMode"          => "01",
                                    "priority"             => $priority,
                                    "dest"                 => $dest,
                                    "address"              => $address,
                                    "destinationEndpoint"  => "01", // Set but not send on radio
                                    "action"               => $actionId,
                                    "onTime"               => $parameters['onTime'],
                                    "offWaitTime"          => $parameters['offWaitTime'],
                                );
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequest":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', '  Msg Received: '.$msg);

                    // Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
                    $Command = array(
                                    "WriteAttributeRequest" => "1",
                                    "priority"          => $priority,
                                    "dest"              => $dest,
                                    "address"           => $address,

                                    "Proprio"           => $parameters['Proprio'],
                                    "clusterId"         => $parameters['clusterId'],
                                    "attributeId"       => $parameters['attributeId'],
                                    "attributeType"     => $parameters['attributeType'],
                                    "value"             => $parameters['value'],
                                );
                    $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestVibration":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', '  Msg Received: '.$msg);

                    $Command = array(
                                     "WriteAttributeRequestVibration" => "1",
                                     "priority"         => $priority,
                                     "dest"             => $dest,
                                     "address"          => $address,

                                     "Proprio"          => $parameters['Proprio'],
                                     "clusterId"        => $parameters['clusterId'],
                                     "attributeId"      => $parameters['attributeId'],
                                     "attributeType"    => $parameters['attributeType'],
                                     "value"            => $parameters['value'],
                                     "repeat"           => $parameters['repeat'],

                                     );
                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestHostFlag":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', '  Msg Received: '.$msg);

                    $consigne = $parameters['value'];
                    $consigneHex = $consigne[4].$consigne[5].$consigne[2].$consigne[3].$consigne[0].$consigne[1];

                    $Command = array(
                                     "WriteAttributeRequest" => "1",
                                     "priority"         => $priority,
                                     "dest"             => $dest,
                                     "address"          => $address,

                                     "Proprio"          => $parameters['Proprio'],
                                     "clusterId"        => $parameters['clusterId'],
                                     "attributeId"      => $parameters['attributeId'],
                                     "attributeType"    => $parameters['attributeType'],
                                     "value"            => $consigneHex,
                                     );
                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestTemperatureSpiritConsigne":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', '  Msg Received: '.$msg);

                    $consigne = sprintf( "%04X", $parameters['value']*100 );
                    $consigneHex = $consigne[2].$consigne[3].$consigne[0].$consigne[1];

                    $Command = array(
                                     "WriteAttributeRequest"    => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "Proprio"                  => $parameters['Proprio'],
                                     "clusterId"                => $parameters['clusterId'],
                                     "attributeId"              => $parameters['attributeId'],
                                     "attributeType"            => $parameters['attributeType'],
                                     "value"                    => $consigneHex,

                                     );
                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestValveSpiritConsigne":
                case "WriteAttributeRequestTrvSpiritMode":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', '  Msg Received: '.$msg);

                    $consigne = sprintf( "%02X", $parameters['value'] );
                    $consigneHex = $consigne;

                    $Command = array(
                                     "WriteAttributeRequest" => "1",
                                     "priority"         => $priority,
                                     "dest"             => $dest,
                                     "address"          => $address,
                                     "Proprio"          => $parameters['Proprio'],
                                     "clusterId"        => $parameters['clusterId'],
                                     "attributeId"      => $parameters['attributeId'],
                                     "attributeType"    => $parameters['attributeType'],
                                     "value"            => $consigneHex,
                                     );
                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestGeneric":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', ' Msg Received: '.$msg);

                    // Par defaut
                    $valuePrepared = $parameters['value'];

                    // Example: set Temperature Danfoss Radiator Head
                    if ( $parameters['attributeType'] = '29' )  {
                        $valuePrepared = sprintf( "%04X", $parameters['value']*100 );
                        $valuePrepared = $valuePrepared[2] . $valuePrepared[3] . $valuePrepared[0] . $valuePrepared[1] ;
                    }

                    if ( $parameters['attributeType'] = '30' )  {
                        $valuePrepared = sprintf( "%02X", $parameters['value'] );
                    }

                    $Command = array(
                                        "WriteAttributeRequestGeneric" => "1",
                                        "priority"         => $priority,
                                        "dest"             => $dest,
                                        "address"          => $address,
                                        "EP"               => $parameters['EP'],
                                        "Proprio"          => $parameters['Proprio'],
                                        "clusterId"        => $parameters['clusterId'],
                                        "attributeId"      => $parameters['attributeId'],
                                        "attributeType"    => $parameters['attributeType'],
                                        "value"            => $valuePrepared,
                                        );
                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestActivateDimmer":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $this->deamonlog('debug', ' Msg Received: '.$msg);

                    $Command = array(
                                     "WriteAttributeRequestActivateDimmer" => "1",
                                     "priority"         => $priority,
                                     "dest"             => $dest,
                                     "address"          => $address,
                                     "clusterId"        => $parameters['clusterId'],
                                     "attributeId"      => $parameters['attributeId'],
                                     "attributeType"    => $parameters['attributeType'],
                                     "value"            => $parameters['value'],
                                     );
                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "ReadAttributeRequest":
                    $keywords = preg_split("/[=&]+/", $msg);
                    if (count($keywords) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    $this->deamonlog('debug', '  Msg received: '.json_encode($msg).' from NE');
                    if ( !isset($parameters['Proprio']) ) { $parameters['Proprio'] = "0000"; }
                    $Command = array(
                                     "ReadAttributeRequest"     => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "clusterId"                => $parameters['clusterId'],   // Don't change the speeling here but in the template
                                     "attributeId"              => $parameters['attributeId'],
                                     "EP"                       => $parameters['EP'],
                                     "Proprio"                  => $parameters['Proprio'],
                                     );
                    $this->deamonlog('debug', '  Msg analysed: '.json_encode($Command).' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "ReadAttributeRequestHue":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "ReadAttributeRequestHue" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "clusterId" => $keywords[1],
                                     "attributeId" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "ReadAttributeRequestOSRAM":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "ReadAttributeRequestOSRAM" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "clusterId" => $keywords[1],
                                     "attributeId" => $keywords[3],
                                     );
                    break;
                       //----------------------------------------------------------------------------
                case "ReadAttributeRequestMulti":
                    $keywords = preg_split("/[=&]+/", $msg);
                    if (count($keywords) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    $this->deamonlog('debug', '  Msg received: '.json_encode($msg).' from NE');
                    if ( !isset($parameters['Proprio']) ) { $parameters['Proprio'] = "0000"; }
                    $Command = array(
                                     "ReadAttributeRequestMulti"     => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "EP"                       => $parameters['EP'],
                                     "clusterId"                => $parameters['clusterId'],   // Don't change the speeling here but in the template
                                     "Proprio"                  => $parameters['Proprio'],
                                     "attributeId"              => $parameters['attributeId'],
                                     );
                    $this->deamonlog('debug', '  Msg analysed: '.json_encode($Command).' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "setLevel":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                                        "setLevel"             => "1",
                                        "addressMode"          => "02",
                                        "priority" => $priority,
                                        "dest" => $dest,
                                        "address"              => $address,
                                        "destinationEndpoint"  => $parameters['EP'],
                                        "Level"                => intval($parameters['Level'] * 255 / 100),
                                        "duration"             => $parameters['duration'],
                                    );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelRaw":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                                         "setLevel"             => "1",
                                         "addressMode"          => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"              => $address,
                                         "destinationEndpoint"  => $parameters['EP'],
                                         "Level"                => $parameters['Level'],
                                         "duration"             => $parameters['duration'],
                                         );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelOSRAM":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevel" => "1",
                                     "addressMode" => "02",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "destinationEndpoint" => "03",
                                     "Level" => intval($keywords[1] * 255 / 100),
                                     "duration" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelVolet":
                    // Pour un get level (level de 0 à 255):
                    // a=0.00081872
                    // b=0.2171167
                    // c=-8.60201639
                    // level = level * level * a + level * b + c

                    // $a = -0.8571429;
                    // $b = 1.8571429;
                    // $c = 0;

                    $eqLogic = eqLogic::byLogicalId( $dest."/".$address, "Abeille" );
                    $a = $eqLogic->getConfiguration( 'paramA', 0);
                    $b = $eqLogic->getConfiguration( 'paramB', 1);
                    $c = $eqLogic->getConfiguration( 'paramC', 0);

                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    // $level255 = intval($keywords[1] * 255 / 100);
                    // $this->deamonlog('debug', 'level255: '.$level255);

                    $levelSlider = $parameters['Level'];                // Valeur entre 0 et 100
                    // $this->deamonlog('debug', 'level Slider: '.$levelSlider);

                    $levelSliderPourcent = $levelSlider/100;    // Valeur entre 0 et 1

                    // $level = min( max( round( $level255 * $level255 * a + $level255 * $b + $c ), 0), 255);
                    $levelPourcent = $a * $levelSliderPourcent * $levelSliderPourcent + $b * $levelSliderPourcent + $c;
                    $level = $levelPourcent * 255;
                    $level = min(max(round($level), 0), 255);

                    $this->deamonlog('debug', '  level Slider: '.$levelSlider.' level calcule: '.$levelPourcent.' level envoye: '.$level);

                    $Command = array(
                        "setLevel" => "1",
                        "addressMode" => "02",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $address,
                        "destinationEndpoint" => "01",
                        "Level" => $level,
                        "duration" => $parameters['duration'],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "moveToLiftAndTiltBSO":

                     $fields = preg_split("/[=&]+/", $msg);
                     if (count($fields) > 1) {
                         $parameters = proper_parse_str( $msg );
                     }

                     $Command = array(
                                      "moveToLiftAndTiltBSO" => "1",
                                      "addressMode" => "02",
                                      "priority" => $priority,
                                      "dest" => $dest,
                                      "address" => $address,
                                      "destinationEndpoint" => "01",
                                      "lift"        => $parameters['lift'],
                                      "inclinaison" => $parameters['inclinaison'],        // Valeur entre 0 et 90
                                      "duration"    => $parameters['duration'],              // FFFF to have max speed of tilt
                                      );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelStop":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevelStop" => "1",
                                     "addressMode" => "02",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "sourceEndpoint" => "01",
                                     "destinationEndpoint" => "01",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelStopHue":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevelStop" => "1",
                                     "addressMode" => "02",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "sourceEndpoint" => "01",
                                     "destinationEndpoint" => "0B",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelHue":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevel" => "1",
                                     "addressMode" => "02",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "destinationEndpoint" => "0B",
                                     "Level" => intval($keywords[1] * 255 / 100),
                                     "duration" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelGroup":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevel" => "1",
                                     "addressMode" => "01",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "destinationEndpoint" => "01",
                                     "Level" => intval($keywords[1] * 255 / 100),
                                     "duration" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setColour":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                                         "setColour"            => "1",
                                         "addressMode"          => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"              => $address,
                                         "X"                    => $parameters['X'],
                                         "Y"                    => $parameters['Y'],
                                         "destinationEndPoint"  => $parameters['EP'],
                                         "duration"             => $parameters['duration'],
                                         );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "setColourGroup":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                            $Command = array(
                                             "setColour"            => "1",
                                             "addressMode"          => "01",
                                             "priority"             => $priority,
                                             "dest"                 => $dest,
                                             "address"              => $address,
                                             "X"                    => $parameters['X'],
                                             "Y"                    => $parameters['Y'],
                                             "destinationEndPoint"  => "01", // not needed as group
                                             );
                        }
                        break;
                    //----------------------------------------------------------------------------
                case "setColourRGB":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        if (strlen($parameters['color']) == 7) {
                            $parameters['color'] = substr($parameters['color'], 1);
                        }
                    }

                    // Si message vient de Abeille alors le parametre est: RRVVBB
                    // Si le message vient de Homebridge: {"color":"#00FF11"}, j'extrais la partie interessante.


                    $rouge = hexdec(substr($parameters['color'],0,2));
                    $vert  = hexdec(substr($parameters['color'],2,2));
                    $bleu  = hexdec(substr($parameters['color'],4,2));

                    $this->deamonlog( 'debug', "  msg: ".$msg." rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/colorRouge', $rouge*100/255      );
                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/colorVert',  $vert*100/255       );
                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/colorBleu',  $bleu*100/255       );
                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/ColourRGB',  $parameters['color']);

                    $Command = array(
                                     "setColourRGB" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "R" => $rouge,
                                     "G" => $vert,
                                     "B" => $bleu,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setRouge":
                    $abeille = Abeille::byLogicalId( $dest.'/'.$address,'Abeille');

                    $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                    $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                    $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                    $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                    if ( $rouge=="" ) { $rouge = 1;   }
                    if ( $vert=="" )  { $vert = 1;    }
                    if ( $bleu=="" )  { $bleu = 1;    }
                    $this->deamonlog('debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu);

                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/colorRouge', $msg );

                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "setColourRGB" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "R" => $parameters['color']/100*255,
                                     "G" => $vert/100*255,
                                     "B" => $bleu/100*255,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setVert":
                    $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                    $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                    $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                    $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                    $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                    if ( $rouge=="" ) { $rouge = 1;   }
                    if ( $vert=="" )  { $vert = 1;    }
                    if ( $bleu=="" )  { $bleu = 1;    }
                    $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/colorVert', $msg );

                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "setColourRGB" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "R" => $rouge/100*255,
                                     "G" => $parameters['color']/100*255,
                                     "B" => $bleu/100*255,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setBleu":
                    $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                    $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                    $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                    $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                    $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                    if ( $rouge=="" ) { $rouge = 1;   }
                    if ( $vert=="" )  { $vert = 1;    }
                    if ( $bleu=="" )  { $bleu = 1;    }
                    $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                    $this->publishMosquittoAbeile( queueKeyCmdToAbeille, $dest.'/'.$address.'/colorBleu', $msg );

                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "setColourRGB" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "R" => $rouge/100*255,
                                     "G" => $vert/100*255,
                                     "B" => $parameters['color']/100*255,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setColourHue":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setColour" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "X" => $keywords[1],
                                     "Y" => $keywords[3],
                                     "destinationEndPoint" => "0B",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setColourOSRAM":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setColour" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "X" => $keywords[1],
                                     "Y" => $keywords[3],
                                     "destinationEndPoint" => "03",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setTemperature":
                    // T°K   Hex sent  Dec eq
                    // 2200	 01C6	   454
                    // 2700	 0172	   370
                    // 4000	 00FA	   250
                    // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                    $this->deamonlog( 'debug', '  msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $temperatureK = $parameters['slider'];
                    $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureK );
                    $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                    $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = dechex( $temperatureConsigne );
                    $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                    $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                    $Command = array(
                                     "setTemperature"       => "1",
                                     "addressMode"          => "02",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address"              => $address,
                                     "temperature"          => $temperatureConsigne,
                                     "destinationEndPoint"  => $parameters['EP'],
                                     );

                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/Temperature-Light', $temperatureK );
                    break;
                    //----------------------------------------------------------------------------
                case "setTemperatureGroup":
                    // T°K   Hex sent  Dec eq
                    // 2200     01C6       454
                    // 2700     0172       370
                    // 4000     00FA       250
                    // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                    $this->deamonlog( 'debug', '  msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $temperatureK = $parameters['slider'];
                    $this->deamonlog( 'debug', ' temperatureConsigne: ' . $temperatureK );
                    $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                    $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = dechex( $temperatureConsigne );
                    $this->deamonlog( 'debug', ' temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                    $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                    $Command = array(
                                     "setTemperature"       => "1",
                                     "addressMode"          => "01",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address"              => $address,
                                     "temperature"          => $temperatureConsigne,
                                     "destinationEndPoint"  => $parameters['EP'],
                                     );

                    $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/Temperature-Light', $temperatureK );
                    break;
                    //----------------------------------------------------------------------------
                case "sceneGroupRecall":
                    // a revoir completement
                    $this->deamonlog( 'debug', '  sceneGroupRecall msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "sceneGroupRecall"         => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     // "address"                  => $parameters['groupID'],   // Ici c est l adresse du group.

                                     // "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     // "DestinationEndPoint"      => "ff",
                                     // "groupID"                  => $parameters['groupID'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  =>  $parameters['sceneID'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "Management_LQI_request":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                        "Management_LQI_request" => "1",
                        "priority" => $priority,
                        "dest" => $dest,
                        "address" => $keywords[1],
                        "StartIndex" => $keywords[3],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "IEEE_Address_request":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                        "IEEE_Address_request"      => "1",
                        "priority"                  => $priority,
                        "dest"                      => $dest,
                        "address"                   => $address,
                        "shortAddress"              => $keywords[1],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "identifySend": // identifySend KIWI1
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    $Command = array(
                                     "identifySend" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "duration" => $parameters['duration'],
                                     "DestinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "identifySendHue": // identifySendHue KIWI2
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "identifySend" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     "duration" => "0010", // $keywords[1]
                                     "DestinationEndPoint" => "0B",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "getGroupMembership":
                    $Command = array(
                                     "getGroupMembership" => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address" => $address,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "bindShort":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    $Command = array(
                                     "bindShort"                => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "targetExtendedAddress"    => $parameters['targetExtendedAddress'],
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "clusterID"                => $parameters['ClusterId'],
                                     "destinationAddress"       => $parameters['reportToAddress'],
                                     "destinationEndpoint"      => "01",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setReportSpirit":
                case "setReport":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "setReport"                => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "ClusterId"                => $parameters['ClusterId'],
                                     "AttributeId"              => $parameters['AttributeId'],
                                     );
                    if (isset($parameters["AttributeDirection"]))      { $Command['AttributeDirection']    = $parameters['AttributeDirection']; }

                    if (isset($parameters["AttributeType"]))           { $Command['AttributeType']         = $parameters['AttributeType']; }
                    if (isset($parameters["MinInterval"]))             { $Command['MinInterval']           = str_pad(dechex($parameters['MinInterval']),   4,0,STR_PAD_LEFT); }
                    if (isset($parameters["MaxInterval"]))             { $Command['MaxInterval']           = str_pad(dechex($parameters['MaxInterval']),   4,0,STR_PAD_LEFT); }
                    if (isset($parameters["Change"]))                  { $Command['Change']                = str_pad(dechex($parameters['Change']),        2,0,STR_PAD_LEFT); }

                    if (isset($parameters["Timeout"]))                 { $Command['Timeout']               = str_pad(dechex($parameters['Timeout']),       4,0,STR_PAD_LEFT); }
                    break;
                    //----------------------------------------------------------------------------
                case "setReportRaw":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "setReportRaw"             => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "ClusterId"                => $parameters['ClusterId'],
                                     "AttributeId"              => $parameters['AttributeId'],
                                     );
                    if (isset($parameters["AttributeDirection"]))      { $Command['AttributeDirection']    = $parameters['AttributeDirection']; }

                    if (isset($parameters["AttributeType"]))           { $Command['AttributeType']         = $parameters['AttributeType']; }
                    if (isset($parameters["MinInterval"]))             { $Command['MinInterval']           = str_pad(dechex($parameters['MinInterval']),   4,0,STR_PAD_LEFT); }
                    if (isset($parameters["MaxInterval"]))             { $Command['MaxInterval']           = str_pad(dechex($parameters['MaxInterval']),   4,0,STR_PAD_LEFT); }
                    if (isset($parameters["Change"]))                  { $Command['Change']                = str_pad(dechex($parameters['Change']),        2,0,STR_PAD_LEFT); }

                    if (isset($parameters["Timeout"]))                 { $Command['Timeout']               = str_pad(dechex($parameters['Timeout']),       4,0,STR_PAD_LEFT); }
                    break;
                    //----------------------------------------------------------------------------
                case "WindowsCovering":
                    $fields = preg_split("/[=&]+/", $msg);
                      if (count($fields) > 1) {
                          $parameters = proper_parse_str( $msg );
                      }

                    $Command = array(
                                     "WindowsCovering"          => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address"                  => $address,
                                     "clusterCommand"           => $parameters['clusterCommand'],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "WindowsCoveringLevel":
                    $fields = preg_split("/[=&]+/", $msg);
                      if (count($fields) > 1) {
                          $parameters = proper_parse_str( $msg );
                      }

                    $Command = array(
                                     "WindowsCoveringLevel"     => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $address,
                                     "clusterCommand"           => $parameters['clusterCommand'],
                                     "liftValue"                => sprintf("%02s",dechex($parameters['liftValue'])),
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "WindowsCoveringGroup":
                    $fields = preg_split("/[=&]+/", $msg);
                      if (count($fields) > 1) {
                          $parameters = proper_parse_str( $msg );
                      }

                    $Command = array(
                                     "WindowsCoveringGroup"     => "1",
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     "address"                  => $address,
                                     "clusterCommand"           => $parameters['clusterCommand'],
                    );
                    break;
                    //----------------------------------------------------------------------------
                case "writeAttributeRequestIAS_WD":
                    $fields = preg_split("/[=&]+/", $msg);
                      if (count($fields) > 1) {
                          $parameters = proper_parse_str( $msg );
                      }

                    $Command = array(
                                     "writeAttributeRequestIAS_WD"     => "1",
                                     "priority"                        => $priority,
                                     "dest"                            => $dest,
                                     "address"                         => $address,
                                     "mode"                            => $parameters['mode'],
                                     "duration"                        => $parameters['duration'],
                    );
                    break;

                    //----------------------------------------------------------------------------
                case "DiscoverAttributesCommand":
                    $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                    $Command = array(
                                        "DiscoverAttributesCommand"     => "1",
                                        "priority"                      => $priority,
                                        "dest"                          => $dest,
                                        "address"                       => $address,
                                        "EP"                            => $parameters['EP'],
                                        "clusterId"                     => $parameters['clusterId'],
                                        "startAttributeId"              => $parameters['startAttributeId'],
                                        "maxAttributeId"                => $parameters['maxAttributeId'],
                    );
                    break;
    
                        //----------------------------------------------------------------------------

                default:
                    // $this->deamonlog('warning', '  AbeilleCommand unknown: '.$action );
                    break;
            } // switch
        } // if $address != "Ruche"
        else { // $address == "Ruche"
            $done = 0;
            // $this->deamonlog("debug", 'procmsg fct - Pour La Ruche - (Ln: '.__LINE__.')' );
            // Crée les variables dans la chaine et associe la valeur.
            $fields = preg_split("/[=&]+/", $msg);
            if (count($fields) > 1) {
                $parameters = proper_parse_str( $msg );
            }

            switch ($action) {
                case "ReadAttributeRequest":
                    $Command = array(
                                     "ReadAttributeRequest" => "1",
                                     "priority"     => $priority,
                                     "dest"         => $dest,
                                     "address"      => $parameters['address'],
                                     "clusterId"    => $parameters['clusterId'],
                                     "attributeId"  => $parameters['attributId'],
                                     "Proprio"      => $parameters['Proprio'],
                                     "EP"           => $parameters['EP'],
                                     );

                    $this->deamonlog('debug', '  Msg Received: '.$msg.' from Ruche');
                    $done = 1;
                    break;

                case "bindShort":
                    $Command = array(
                                     "bindShort"                => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "targetExtendedAddress"    => $parameters['targetExtendedAddress'],
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "clusterID"                => $parameters['ClusterId'],
                                     "destinationAddress"       => $parameters['reportToAddress'],
                                     "destinationEndpoint"      => "01",
                                     );
                    $done = 1;
                    break;

                case "setReport":
                    if ( !isset($parameters['targetEndpoint']) )    { $parameters['targetEndpoint'] = "01"; }
                    if ( !isset($parameters['MaxInterval']) )       { $parameters['MaxInterval']    = "0"; }
                    $Command = array(
                                     "setReport"                => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "ClusterId"                => $parameters['ClusterId'],
                                     "AttributeType"            => $parameters['AttributeType'],
                                     "AttributeId"              => $parameters['AttributeId'],
                                     "MaxInterval"              => str_pad(dechex($parameters['MaxInterval']),4,0,STR_PAD_LEFT),
                                     );
                    $done = 1;
                    break;

                case "getGroupMembership":
                    if ( $parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
                    if ( strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "getGroupMembership"       => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     );
                    $done = 1;
                    break;

                case "addGroup":
                    if ( $parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
                    if ( strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "addGroup"                 => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupAddress"             => $parameters['groupAddress'],
                                     );
                    $done = 1;
                    break;

                case "removeGroup":
                    if ( $parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
                    if ( strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "removeGroup"              => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupAddress"             => $parameters['groupAddress'],
                                     );
                    $done = 1;
                    break;

                    // Scene -----------------------------------------------------------------------------------------------

                case "viewScene":
                    if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "viewScene"                => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  => $parameters['sceneID'],
                                     );
                    $done = 1;
                    break;

                case "storeScene":
                    if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "storeScene"               => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  => $parameters['sceneID'],
                                     );
                    $done = 1;
                    break;

                case "recallScene":
                    if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "recallScene"              => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  => $parameters['sceneID'],
                                     );
                    $done = 1;
                    break;

                case "sceneGroupRecall":
                    $this->deamonlog( 'debug', '  sceneGroupRecall msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }

                    $Command = array(
                                     "sceneGroupRecall"         => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     // "address"                  => $parameters['groupID'],   // Ici c est l adresse du group.

                                     // "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     // "DestinationEndPoint"      => "ff",
                                     // "groupID"                  => $parameters['groupID'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  =>  $parameters['sceneID'],
                                     );
                    $done = 1;
                    break;

                case "addScene":
                    if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "addScene"                 => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  => $parameters['sceneID'],
                                     "sceneName"                => $parameters['sceneName'],
                                     );
                    $done = 1;
                    break;

                case "getSceneMembership":
                    if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "getSceneMembership"       => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     );
                    $done = 1;
                    break;

                case "removeSceneAll":
                    if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                    $Command = array(
                                     "removeSceneAll"           => "1",
                                     "priority"                 => $priority,
                                     "dest"                     => $dest,
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     );
                    $done = 1;
                    break;
            } // switch action

            //  -----------------------------------------------------------------------------------------------
            if ( !$done ) {
                $keywords = preg_split("/[=&]+/", $msg);

                // Si une string simple
                if (count($keywords) == 1) {
                    $this->deamonlog('debug', '  L2 - 1 argument command');
                    $Command = array(
                                     $action => $msg,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     );
                } // Si une command type get http param1=value1&param2=value2
                if (count($keywords) == 2) {
                    $this->deamonlog('debug', '  L2 - 2 arguments command');
                    $Command = array(
                                     $action => $action,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     $keywords[0] => $keywords[1],
                                     );
                }
                if (count($keywords) == 4) {
                    $this->deamonlog('debug', '  L2 - 4 arguments command');
                    $Command = array(
                                     $action => $action,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     );
                }
                if (count($keywords) == 6) {
                    $this->deamonlog('debug', '  L2 - 6 arguments command');
                    $Command = array(
                                     $action => $action,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     );
                }
                if (count($keywords) == 8) {
                    // $this->deamonlog('debug', '  L2 - 8 arguments command');
                    $Command = array(
                                     $action => $action,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     $keywords[6] => $keywords[7],
                                     );
                }
                if (count($keywords) == 10) {
                    // $this->deamonlog('debug', '  L2 - 10 arguments command');
                    $Command = array(
                                     $action => $action,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     $keywords[6] => $keywords[7],
                                     $keywords[8] => $keywords[9],
                                     );
                }
                if (count($keywords) == 12) {
                    // $this->deamonlog('debug', '  L2 - 12 arguments command');
                    $Command = array(
                                     $action => $action,
                                     "priority" => $priority,
                                     "dest" => $dest,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     $keywords[6] => $keywords[7],
                                     $keywords[8] => $keywords[9],
                                     $keywords[10] => $keywords[11],
                                     );
                }

            }
        }

        /*---------------------------------------------------------*/

        if (!isset($Command)) {
            $this->deamonlog('debug', '  WARNING: Unknown command ! (topic='.$topic.')', 1);
        } else {
            $this->deamonlog('debug', '  calling processCmd with Command parameters: '.json_encode($Command), $this->debug['procmsg']);
            $this->processCmd( $Command );
        }

        return;
    }
}

?>
