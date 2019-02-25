<?php
    
    
    /***
     * AbeilleMQTTCCmd subscribe to Abeille topic and receive message sent by AbeilleParser.
     *
     *
     *
     */
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__)."/../../core/class/Abeille.class.php";
    
    require_once("lib/Tools.php");
    include("CmdToAbeille.php");  // contient processCmd()
    
    include(dirname(__FILE__).'/includes/config.php');
    include(dirname(__FILE__).'/includes/function.php');
    
    
    function deamonlog($loglevel = 'NONE', $message = "")
    {
        Tools::deamonlog($loglevel,'AbeilleMQTTCmd',$message);
    }
    
    function addTempoCmdAbeille($topic, $msg) {
        // TempoCmdAbeille/Ruche/getVersion&time=123 -> msg
        
        list($topic, $param) = explode('&', $topic);
        $topic = str_replace( 'Tempo', '', $topic);
        
        list($timeTitle, $time) = explode('=', $param);
        
        $GLOBALS["mqttMessageQueue"][] = array( $time, $topic, $msg );
        deamonlog('debug', 'addTempoCmdAbeille - mqttMessageQueue: '.json_encode($GLOBALS["mqttMessageQueue"]) );
        
        return;
    }
    
    function execTempoCmdAbeille($client,$qos) {
        if ( count($GLOBALS["mqttMessageQueue"])<1 ) {
            return;
        }
        // deamonlog('debug', 'execTempoCmdAbeille - mqttMessageQueue - debut: '.json_encode($GLOBALS["mqttMessageQueue"]) );
        $now=time();
        foreach ($GLOBALS["mqttMessageQueue"] as $key => $mqttMessage) {
            // deamonlog('debug', 'execTempoCmdAbeille - mqttMessageQueue - 0: '.$mqttMessage[0] );
            if ($mqttMessage[0]<$now) {
                $client->publish( $mqttMessage[1], $mqttMessage[2], $qos);
                unset($GLOBALS["mqttMessageQueue"][$key]);
            }
        }
        // deamonlog('debug', 'execTempoCmdAbeille - mqttMessageQueue - fin: '.json_encode($GLOBALS["mqttMessageQueue"]) );
        
        return;
    }
    
    function procmsg($topic, $msg)
    {
        global $dest;
        global $client;
        global $qos;
        
        //$msg =  preg_replace("/[^A-Za-z0-9&=]/",'',$msg);
        
        $test = explode('/', $topic);
        if ( sizeof( $test ) !=3 ) {
            return ;
        }
        
        list($type, $address, $action) = explode('/', $topic);
        // deamonlog('debug', 'Type: '.$type.' Address: '.$address.' avec Action: '.$action);
        
        if ($type == "TempoCmdAbeille") {
            addTempoCmdAbeille( $topic, $msg);
        }
        
        if ($type != "CmdAbeille") {
            // deamonlog('warning','Msg Received: Topic: {'.$topic.'} => '.$msg.' mais je ne sais pas quoi en faire, no action.');
            return;
        }
        
        deamonlog('info', '-----' );
        deamonlog('info', 'Msg Received: Topic: {'.$topic.'} => '.$msg);
        
        deamonlog('debug', 'Type: '.$type.' Address: '.$address.' avec Action: '.$action);
        
        // if ($type == "Abeille") { return; }
        
        // Je traite que les CmdAbeille/..../....
        // Jai les CmdAbeille/Ruche et les CmdAbeille/shortAdress que je dois gérer un peu differement les uns des autres.
        
        if ($address != "Ruche") {
            switch ($action) {
                    //----------------------------------------------------------------------------
                case "managementNetworkUpdateRequest":
                    $Command = array(
                                     "managementNetworkUpdateRequest" => "1",
                                     "address" => $address,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "Annonce":
                    if ($msg == "Default") {
                        deamonlog('info', 'Preparation de la commande annonce pour default');
                        $Command = array(
                                         "ReadAttributeRequest" => "1",
                                         "address" => $address,
                                         "clusterId" => "0000",
                                         "attributeId" => "0005",
                                         "EP"=>"01",
                                         );
                    }
                    if ($msg == "Hue") {
                        deamonlog('info', 'Preparation de la commande annonce pour Hue');
                        $Command = array(
                                         "ReadAttributeRequestHue" => "1",
                                         "address" => $address,
                                         "clusterId" => "0000",
                                         "attributeId" => "0005",
                                         "EP"=>"0B",
                                         );
                    }
                    if ($msg == "OSRAM") {
                        deamonlog('info', 'Preparation de la commande annonce pour OSRAM');
                        $Command = array(
                                         "ReadAttributeRequestOSRAM" => "1",
                                         "address" => $address,
                                         "clusterId" => "0000",
                                         "attributeId" => "0005",
                                         "EP"=>"03",
                                         );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "AnnonceProfalux":
                    if ($msg == "Default") {
                        deamonlog('info', 'Preparation de la commande annonce pour default');
                        $Command = array(
                                         "ReadAttributeRequest" => "1",
                                         "address" => $address,
                                         "clusterId" => "0000",
                                         "attributeId" => "0010",
                                         "EP"=>"03",
                                         );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff":
                    $convertOnOff = array(
                                          "On"      => "01",
                                          "Off"     => "00",
                                          "Toggle"  => "02",
                                          );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "02",
                                         "address" => $address,
                                         "destinationEndpoint" => $parameters['EP'],
                                         "action" => $convertOnOff[$parameters['Action']],
                                         );
                    }
                    else {
                        
                        $actionId = $convertOnOff[$msg];
                        
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "02",
                                         "address" => $address,
                                         "destinationEndpoint" => "01",
                                         "action" => $actionId,
                                         );
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff2":
                    if ($msg == "On") {
                        $actionId = "01";
                    }
                    if ($msg == "Off") {
                        $actionId = "00";
                    }
                    if ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                                     "onoff" => "1",
                                     "addressMode" => "02",
                                     "address" => $address,
                                     "destinationEndpoint" => "02",
                                     "action" => $actionId,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOff3":
                    if ($msg == "On") {
                        $actionId = "01";
                    }
                    if ($msg == "Off") {
                        $actionId = "00";
                    }
                    if ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                                     "onoff" => "1",
                                     "addressMode" => "02",
                                     "address" => $address,
                                     "destinationEndpoint" => "03",
                                     "action" => $actionId,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffHue":
                    if ($msg == "On") {
                        $actionId = "01";
                    }
                    if ($msg == "Off") {
                        $actionId = "00";
                    }
                    if ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                                     "onoff" => "1",
                                     "addressMode" => "02",
                                     "address" => $address,
                                     "destinationEndpoint" => "0B",
                                     "action" => $actionId,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffOSRAM":
                    if ($msg == "On") {
                        $actionId = "01";
                    }
                    if ($msg == "Off") {
                        $actionId = "00";
                    }
                    if ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                                     "onoff" => "1",
                                     "addressMode" => "02",
                                     "address" => $address,
                                     "destinationEndpoint" => "03",
                                     "action" => $actionId,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "UpGroup":
                    $Command = array(
                                     "UpGroup" => "1",
                                     "addressMode" => "01",
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
                                     "address" => $address,
                                     "destinationEndpoint" => "01", // Set but not send on radio
                                     "step" => $msg,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "OnOffGroup":
                    if ($msg == "On") {
                        $actionId = "01";
                    }
                    if ($msg == "Off") {
                        $actionId = "00";
                    }
                    if ($msg == "Toggle") {
                        $actionId = "02";
                    }
                    $Command = array(
                                     "onoff" => "1",
                                     "addressMode" => "01",
                                     "address" => $address,
                                     "destinationEndpoint" => "01", // Set but not send on radio
                                     "action" => $actionId,
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequest":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    // $keywords = preg_split("/[=&]+/", $msg);
                    deamonlog('debug', 'Msg Received: '.$msg);
                    
                    // Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
                    $Command = array(
                                     "WriteAttributeRequest" => "1",
                                     "address" => $address,
                                     // "Proprio" => $keywords[1],
                                     "Proprio" => $parameters['Proprio'],
                                     // "clusterId" => $keywords[3],
                                     "clusterId" => $parameters['clusterId'],
                                     // "attributeId" => $keywords[5],
                                     "attributeId" => $parameters['attributeId'],
                                     // "attributeType" => $keywords[7],
                                     "attributeType" => $parameters['attributeType'],
                                     // "value" => $keywords[9],
                                     "value" => $parameters['value'],
                                     "attributeType" => $parameters['attributeType'],
                                     
                                     );
                    deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "WriteAttributeRequestVibration":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    // $keywords = preg_split("/[=&]+/", $msg);
                    deamonlog('debug', 'Msg Received: '.$msg);
                    
                    // Proprio=115f&clusterId=0500&attributeId=fff1&attributeType=23&value=03010000&repeat=1
                    $Command = array(
                                     "WriteAttributeRequestVibration" => "1",
                                     "address" => $address,
                                     // "Proprio" => $keywords[1],
                                     "Proprio" => $parameters['Proprio'],
                                     // "clusterId" => $keywords[3],
                                     "clusterId" => $parameters['clusterId'],
                                     // "attributeId" => $keywords[5],
                                     "attributeId" => $parameters['attributeId'],
                                     // "attributeType" => $keywords[7],
                                     "attributeType" => $parameters['attributeType'],
                                     // "value" => $keywords[9],
                                     "value" => $parameters['value'],
                                     "repeat" => $parameters['repeat'],
                                     
                                     );
                    deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "ReadAttributeRequest":
                    $keywords = preg_split("/[=&]+/", $msg);
                    if (count($keywords) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    deamonlog('debug', 'AbeilleMQTTCmd: Msg received: '.json_encode($msg).' from NE');
                    $Command = array(
                                     "ReadAttributeRequest" => "1",
                                     "address"      => $address,
                                     "clusterId"    => $parameters['clusterId'],   // Don't change the speeling here but in the template
                                     "attributeId"  => $parameters['attributeId'],
                                     "EP"           => $parameters['EP'],
                                     "Proprio"      => $parameters['Proprio'],
                                     );
                    deamonlog('debug', 'AbeilleMQTTCmd: Msg analysed: '.json_encode($Command).' from NE');
                    break;
                    //----------------------------------------------------------------------------
                case "ReadAttributeRequestHue":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "ReadAttributeRequestHue" => "1",
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
                                     "address" => $address,
                                     "clusterId" => $keywords[1],
                                     "attributeId" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevel":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                        $Command = array(
                                         "setLevel"             => "1",
                                         "addressMode"          => "02",
                                         "address"              => $address,
                                         "destinationEndpoint"  => $parameters['EP'],
                                         "Level"                => intval($parameters['Level'] * 255 / 100),
                                         "duration"             => $parameters['duration'],
                                         );
                    }
                    else {
                        
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
                                         "address"              => $address,
                                         "destinationEndpoint"  => $parameters['EP'],
                                         "Level"                => $parameters['Level'],
                                         "duration"             => $parameters['duration'],
                                         );
                    }
                    else {
                        
                    }
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelOSRAM":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevel" => "1",
                                     "addressMode" => "02",
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
                    
                    $a = -0.8571429;
                    $b = 1.8571429;
                    $c = 0;
                    
                    $keywords = preg_split("/[=&]+/", $msg);
                    
                    // $level255 = intval($keywords[1] * 255 / 100);
                    // deamonlog('debug', 'level255: '.$level255);
                    
                    $levelSlider = $keywords[1];                // Valeur entre 0 et 100
                    // deamonlog('debug', 'level Slider: '.$levelSlider);
                    
                    $levelSliderPourcent = $levelSlider/100;    // Valeur entre 0 et 1
                    
                    // $level = min( max( round( $level255 * $level255 * a + $level255 * $b + $c ), 0), 255);
                    $levelPourcent = $a*$levelSliderPourcent*$levelSliderPourcent+$b*$levelSliderPourcent+c;
                    $level = $levelPourcent * 255;
                    $level = min( max( round( $level), 0), 255);
                    
                    deamonlog('debug', 'level Slider: '.$levelSlider.' level calcule: '.$levelPourcent.' level envoye: '.$level);
                    
                    $Command = array(
                                     "setLevel" => "1",
                                     "addressMode" => "02",
                                     "address" => $address,
                                     "destinationEndpoint" => "01",
                                     "Level" => $level,
                                     "duration" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setLevelStop":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "setLevelStop" => "1",
                                     "addressMode" => "02",
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
                                         "address"              => $address,
                                         "X"                    => $parameters['X'],
                                         "Y"                    => $parameters['Y'],
                                         "destinationEndPoint"  => $parameters['EP'],
                                         );
                    }
                    else {
                        
                    }
                    break;

                    //----------------------------------------------------------------------------
                case "setColourRGB":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    // Si message vient de Abeille alors le parametre est: RRVVBB
                    // Si le message vient de Homebridge: {"color":"#00FF11"}, j'extrais la partie interessante.
                    
                    
                    $rouge = hexdec(substr($parameters['color'],0,2));
                    $vert  = hexdec(substr($parameters['color'],2,2));
                    $bleu  = hexdec(substr($parameters['color'],4,2));
                    
                    deamonlog( 'debug', "msg: ".$msg." rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    $client->publish('Abeille/'.$address.'/colorRouge', $rouge*100/255,         $qos);
                    $client->publish('Abeille/'.$address.'/colorVert',  $vert*100/255,          $qos);
                    $client->publish('Abeille/'.$address.'/colorBleu',  $bleu*100/255,          $qos);
                    $client->publish('Abeille/'.$address.'/ColourRGB',  $parameters['color'],   $qos);
                    
                    $Command = array(
                                     "setColourRGB" => "1",
                                     "address" => $address,
                                     "R" => $rouge,
                                     "G" => $vert,
                                     "B" => $bleu,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setRouge":
                    $abeille = Abeille::byLogicalId('Abeille/'.$address,'Abeille');
                    
                    $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                    $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                    $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                    deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    if ( $rouge=="" ) { $rouge = 1;   }
                    if ( $vert=="" )  { $vert = 1;    }
                    if ( $bleu=="" )  { $bleu = 1;    }
                    deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    $client->publish('Abeille/'.$address.'/colorRouge', $msg, $qos);
                    
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $Command = array(
                                     "setColourRGB" => "1",
                                     "address" => $address,
                                     "R" => $parameters['color']/100*255,
                                     "G" => $vert/100*255,
                                     "B" => $bleu/100*255,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setVert":
                    $abeille = Abeille::byLogicalId('Abeille/'.$address,'Abeille');
                    
                    $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                    $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                    $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                    deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    if ( $rouge=="" ) { $rouge = 1;   }
                    if ( $vert=="" )  { $vert = 1;    }
                    if ( $bleu=="" )  { $bleu = 1;    }
                    deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    $client->publish('Abeille/'.$address.'/colorVert', $msg, $qos);
                    
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $Command = array(
                                     "setColourRGB" => "1",
                                     "address" => $address,
                                     "R" => $rouge/100*255,
                                     "G" => $parameters['color']/100*255,
                                     "B" => $bleu/100*255,
                                     "destinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setBleu":
                    $abeille = Abeille::byLogicalId('Abeille/'.$address,'Abeille');
                    
                    $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                    $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                    $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                    deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    if ( $rouge=="" ) { $rouge = 1;   }
                    if ( $vert=="" )  { $vert = 1;    }
                    if ( $bleu=="" )  { $bleu = 1;    }
                    deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );
                    
                    $client->publish('Abeille/'.$address.'/colorBleu', $msg, $qos);
                    
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $Command = array(
                                     "setColourRGB" => "1",
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
                    deamonlog( 'debug', 'msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $temperatureK = $parameters['slider'];
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureK );
                    $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = dechex( $temperatureConsigne );
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                    $Command = array(
                                     "setTemperature"       => "1",
                                     "addressMode"          => "02",
                                     "address"              => $address,
                                     "temperature"          => $temperatureConsigne,
                                     "destinationEndPoint"  => $parameters['EP'],
                                     );
                    
                    $client->publish('Abeille/'.$address.'/Temperature-Light', $temperatureK,         $qos);
                    break;
                    //----------------------------------------------------------------------------
                case "setTemperatureGroup":
                    // T°K   Hex sent  Dec eq
                    // 2200     01C6       454
                    // 2700     0172       370
                    // 4000     00FA       250
                    // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                    deamonlog( 'debug', 'msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $temperatureK = $parameters['slider'];
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureK );
                    $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = dechex( $temperatureConsigne );
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                    $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                    deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                    $Command = array(
                                     "setTemperature"       => "1",
                                     "addressMode"          => "01",
                                     "address"              => $address,
                                     "temperature"          => $temperatureConsigne,
                                     "destinationEndPoint"  => $parameters['EP'],
                                     );
                    
                    $client->publish('Abeille/'.$address.'/Temperature-Light', $temperatureK,         $qos);
                    break;
                    //----------------------------------------------------------------------------
                case "sceneGroupRecall":
                    // a revoir completement
                    deamonlog( 'debug', 'sceneGroupRecall msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $Command = array(
                                     "sceneGroupRecall"         => "1",
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
                                     "address" => $keywords[1],
                                     "StartIndex" => $keywords[3],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "IEEE_Address_request":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "IEEE_Address_request" => "1",
                                     "address" => $address,
                                     "shortAddress" => $keywords[1],
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "identifySend":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    $Command = array(
                                     "identifySend" => "1",
                                     "address" => $address,
                                     "duration" => $parameters['duration'], // $keywords[1]
                                     "DestinationEndPoint" => $parameters['EP'],
                                     );
                    break;
                case "identifySendHue":
                    $keywords = preg_split("/[=&]+/", $msg);
                    $Command = array(
                                     "identifySend" => "1",
                                     "address" => $address,
                                     "duration" => "0010", // $keywords[1]
                                     "DestinationEndPoint" => "0B",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "getGroupMembership":
                    $Command = array(
                                     "getGroupMembership" => "1",
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
                                     "address"                  => $address,
                                     "targetExtendedAddress"    => $parameters['targetExtendedAddress'],
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "clusterID"                => $parameters['ClusterId'],
                                     "destinationAddress"       => $parameters['reportToAddress'],
                                     "destinationEndpoint"      => "01",
                                     );
                    break;
                    //----------------------------------------------------------------------------
                case "setReport":
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $Command = array(
                                     "setReport"                => "1",
                                     "address"                  => $address,
                                     "targetEndpoint"           => $parameters['targetEndpoint'],
                                     "ClusterId"                => $parameters['ClusterId'],
                                     "AttributeType"            => $parameters['AttributeType'],
                                     "AttributeId"              => $parameters['AttributeId'],
                                     "MaxInterval"              => str_pad(dechex($parameters['MaxInterval']),4,0,STR_PAD_LEFT),
                                     );
                    break;
                    //----------------------------------------------------------------------------
                default:
                    deamonlog('warning', 'AbeilleCommand unknown: '.$action );
                    break;
            } // switch
        } // if $address != "Ruche"
        else { // $address == "Ruche"
            $done = 0;
            
            // Crée les variables dans la chaine et associe la valeur.
            $fields = preg_split("/[=&]+/", $msg);
            if (count($fields) > 1) {
                $parameters = proper_parse_str( $msg );
            }
            
            switch ($action) {
                case "ReadAttributeRequest":
                    $Command = array(
                                     "ReadAttributeRequest" => "1",
                                     "address"      => $parameters['address'],
                                     "clusterId"    => $parameters['clusterId'],
                                     "attributeId"  => $parameters['attributId'],
                                     "Proprio"      => $parameters['Proprio'],
                                     );
                    
                    deamonlog('debug', 'Msg Received: '.$msg.' from Ruche');
                    $done = 1;
                    break;
                    
                case "bindShort":
                    $Command = array(
                                     "bindShort"                => "1",
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
                    $Command = array(
                                     "setReport"                => "1",
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
                                     "address"                  => $parameters['address'],
                                     "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                     "groupID"                  => $parameters['groupID'],
                                     "sceneID"                  => $parameters['sceneID'],
                                     );
                    $done = 1;
                    break;
                    
                case "sceneGroupRecall":
                    deamonlog( 'debug', 'sceneGroupRecall msg: ' . $msg );
                    $fields = preg_split("/[=&]+/", $msg);
                    if (count($fields) > 1) {
                        $parameters = proper_parse_str( $msg );
                    }
                    
                    $Command = array(
                                     "sceneGroupRecall"         => "1",
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
                                     "addScene"                => "1",
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
                    $Command = array($action => $msg);
                } // Si une command type get http param1=value1&param2=value2
                if (count($keywords) == 2) {
                    deamonlog('debug', '2 arguments command');
                    $Command = array(
                                     $action => $action,
                                     $keywords[0] => $keywords[1],
                                     );
                }
                if (count($keywords) == 4) {
                    deamonlog('debug', '4 arguments command');
                    $Command = array(
                                     $action => $action,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     );
                }
                if (count($keywords) == 6) {
                    deamonlog('debug', '6 arguments command');
                    $Command = array(
                                     $action => $action,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     );
                }
                if (count($keywords) == 8) {
                    deamonlog('debug', '8 arguments command');
                    $Command = array(
                                     $action => $action,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     $keywords[6] => $keywords[7],
                                     );
                }
                if (count($keywords) == 10) {
                    deamonlog('debug', '10 arguments command');
                    $Command = array(
                                     $action => $action,
                                     $keywords[0] => $keywords[1],
                                     $keywords[2] => $keywords[3],
                                     $keywords[4] => $keywords[5],
                                     $keywords[6] => $keywords[7],
                                     $keywords[8] => $keywords[9],
                                     );
                }
                if (count($keywords) == 12) {
                    deamonlog('debug', '12 arguments command');
                    $Command = array(
                                     $action => $action,
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
        
        // print_r( $Command );
        $toPrint = "";
        foreach ( $Command as $commandItem => $commandValue) { $toPrint = $toPrint . $commandItem."-".$commandValue."-"; }
        
        deamonlog('debug','processCmd call with Command parameters: '.$toPrint);
        
        processCmd($dest, $Command, $GLOBALS['requestedlevel']);
        
        
        return;
    }
    
    // ***********************************************************************************************
    // MQTT
    // ***********************************************************************************************
    function connect($r, $message)
    {
        log::add('AbeilleMQTTCmd', 'info', 'Mosquitto: Connexion à Mosquitto avec code ' . $r . ' ' . $message);
        // config::save('state', '1', 'Abeille');
    }
    
    function disconnect($r)
    {
        log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Déconnexion de Mosquitto avec code ' . $r);
        // config::save('state', '0', 'Abeille');
    }
    
    function subscribe()
    {
        log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Subscribe to topics');
    }
    
    function logmq($code, $str)
    {
        // if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
        log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Log level: ' . $code . ' Message: ' . $str);
        // }
    }
    
    function message($message)
    {
        // var_dump( $message );
        procmsg( $message->topic, $message->payload );
    }
    
    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    //                      1          2           3       4          5       6
    //$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;
    
    $dest = $argv[1];
    $server = $argv[2];     // change if necessary
    $port = $argv[3];                     // change if necessary
    $username = $argv[4];                   // set your username
    $password = $argv[5];                   // set your password
    $client_id = "AbeilleMQTTCmd"; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos = $argv[6];
    $requestedlevel = $argv[7];
    $requestedlevel = '' ? 'none' : $argv[7];
    
    $mqttMessageQueue = array(); // Table with mqtt command to execute later on.
    $parameters_info = Abeille::getParameters();
    
    if ($dest == 'none') {
        $dest = $resourcePath.'/COM';
        deamonlog('info', 'main: debug for com file: '.$dest);
        exec(system::getCmdSudo().'touch '.$dest.'; chmod 777 '.$dest.' > /dev/null 2>&1');
    }
    
    deamonlog( 'info', 'Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel );
    
    
    deamonlog( 'debug', 'Create a MQTT Client');
    
    // https://github.com/mgdm/Mosquitto-PHP
    // http://mosquitto-php.readthedocs.io/en/latest/client.html
    $client = new Mosquitto\Client($client_id);
    
    // var_dump( $client );
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
    $client->onConnect('connect');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
    $client->onDisconnect('disconnect');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
    $client->onSubscribe('subscribe');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
    $client->onMessage('message');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onLog
    $client->onLog('logmq');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
    $client->setWill('/jeedom', "Client AbeilleMQTTCmd died :-(", $parameters_info['AbeilleQos'], 0);
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
    $client->setReconnectDelay(1, 120, 1);
    
    // var_dump( $client );
    
    try {
        deamonlog('info', 'try part');
        
        $client->setCredentials( $username, $password );
        $client->connect( $server, $port, 60 );
        $client->subscribe( $parameters_info['AbeilleTopic'], $qos ); // !auto: Subscribe to root topic
        
        deamonlog( 'debug', 'Subscribed to topic: '.$parameters_info['AbeilleTopic'] );
        
        // 1 to use loopForever et 0 to use while loop
        if (0) {
            // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
            deamonlog( 'debug', 'Let loop for ever' );
            $client->loopForever();
        }
        else {
            while (true) {
                // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                $client->loop(0);
                execTempoCmdAbeille($client,$qos);
                time_nanosleep( 0, 10000000 ); // 1/100s
            }
        }
        
        $client->disconnect();
        unset($client);
        
    } catch (Exception $e) {
        log::add('Abeille', 'error', $e->getMessage());
    }
    
    deamonlog('info', 'Fin du script');
    
    
    ?>
