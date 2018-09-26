<?php
    
    
    /***
     * AbeilleMQTTCCmd subscribe to Abeille topic and receive message sent by AbeilleParser.
     *
     *
     *
     */
    
    $lib_phpMQTT = 0;
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    
    if ( $lib_phpMQTT ) {  include("lib/phpMQTT.php"); }
    require_once("lib/Tools.php");
    include("CmdToAbeille.php");  // contient processCmd()
    
    include(dirname(__FILE__).'/includes/config.php');
    include(dirname(__FILE__).'/includes/function.php');
    
    
    function deamonlog($loglevel = 'NONE', $message = "")
    {
        Tools::deamonlog($loglevel,'AbeilleMQTTCmd',$message);
    }
    
    
    function procmsg($topic, $msg)
    {
        global $dest;
        
        $msg =  preg_replace("/[^A-Za-z0-9&=]/",'',$msg);
        
        $test = explode('/', $topic);
        if ( sizeof( $test ) !=3 ) {
            return ;
        }
        
        list($type, $address, $action) = explode('/', $topic);
        
        if ($type != "CmdAbeille") {
            // deamonlog('warning','Msg Received: Topic: {'.$topic.'} => '.$msg.' mais je ne sais pas quoi en faire, no action.');
            return;
        }
        
        deamonlog('info', 'Msg Received: Topic: {'.$topic.'} => '.$msg);
        
        deamonlog('debug', 'Type: '.$type.' Address: '.$address.' avec Action: '.$action);
        
        // if ($type == "Abeille") { return; }
        
        // Je traite que les CmdAbeille/..../....
        // Jai les CmdAbeille/Ruche et les CmdAbeille/shortAdress que je dois gérer un peu differement les uns des autres.
        
        
        
        if ($address != "Ruche") {
            //----------------------------------------------------------------------------
            if ($action == "Annonce") {
                if ($msg == "Default") {
                    deamonlog('info', 'Preparation de la commande annonce pour default');
                    $Command = array(
                                     "ReadAttributeRequest" => "1",
                                     "address" => $address,
                                     "clusterId" => "0000",
                                     "attributeId" => "0005",
                                     );
                }
                if ($msg == "Hue") {
                    deamonlog('info', 'Preparation de la commande annonce pour Hue');
                    $Command = array(
                                     "ReadAttributeRequestHue" => "1",
                                     "address" => $address,
                                     "clusterId" => "0000",
                                     "attributeId" => "0005",
                                     );
                }
                if ($msg == "OSRAM") {
                    deamonlog('info', 'Preparation de la commande annonce pour OSRAM');
                    $Command = array(
                                     "ReadAttributeRequestOSRAM" => "1",
                                     "address" => $address,
                                     "clusterId" => "0000",
                                     "attributeId" => "0005",
                                     );
                }
                //----------------------------------------------------------------------------
            } elseif ($action == "AnnonceProfalux") {
                if ($msg == "Default") {
                    deamonlog('info', 'Preparation de la commande annonce pour default');
                    $Command = array(
                                     "ReadAttributeRequest" => "1",
                                     "address" => $address,
                                     "clusterId" => "0000",
                                     "attributeId" => "0010",
                                     );
                }
                //----------------------------------------------------------------------------
            } elseif ($action == "OnOff") {
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
                                 "destinationEndpoint" => "01",
                                 "action" => $actionId,
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "OnOff2") {
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
                //----------------------------------------------------------------------------
            } elseif ($action == "OnOff3") {
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
                //----------------------------------------------------------------------------
            } elseif ($action == "OnOffHue") {
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
                //----------------------------------------------------------------------------
            } elseif ($action == "OnOffOSRAM") {
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
                //----------------------------------------------------------------------------
            } elseif ($action == "UpGroup") {
                $Command = array(
                                 "UpGroup" => "1",
                                 "addressMode" => "01",
                                 "address" => $address,
                                 "destinationEndpoint" => "01", // Set but not send on radio
                                 "step" => $msg,
                                 );
                
                //----------------------------------------------------------------------------
            } elseif ($action == "DownGroup") {
                $Command = array(
                                 "DownGroup" => "1",
                                 "addressMode" => "01",
                                 "address" => $address,
                                 "destinationEndpoint" => "01", // Set but not send on radio
                                 "step" => $msg,
                                 );
                
                //----------------------------------------------------------------------------
            } elseif ($action == "OnOffGroup") {
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
                
                //----------------------------------------------------------------------------
            } elseif ($action == "WriteAttributeRequest") {
                $keywords = preg_split("/[=&]+/", $msg);
                deamonlog('debug', 'Msg Received: '.$msg);
                
                // Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
                $Command = array(
                                 "WriteAttributeRequest" => "1",
                                 "address" => $address,
                                 "Proprio" => $keywords[1],
                                 "clusterId" => $keywords[3],
                                 "attributeId" => $keywords[5],
                                 "attributeType" => $keywords[7],
                                 "value" => $keywords[9],
                                 );
                deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                
               //----------------------------------------------------------------------------
            } elseif ($action == "ReadAttributeRequest") {
                $keywords = preg_split("/[=&]+/", $msg);
                deamonlog('debug', 'Msg Received: '.$msg);
                
                
                $Command = array(
                                 "ReadAttributeRequest" => "1",
                                 "address" => $address,
                                 "clusterId" => $keywords[1],
                                 "attributeId" => $keywords[3],
                                 );
                deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                
                //----------------------------------------------------------------------------
            } elseif ($action == "ReadAttributeRequestHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "ReadAttributeRequestHue" => "1",
                                 "address" => $address,
                                 "clusterId" => $keywords[1],
                                 "attributeId" => $keywords[3],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "ReadAttributeRequestOSRAM") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "ReadAttributeRequestOSRAM" => "1",
                                 "address" => $address,
                                 "clusterId" => $keywords[1],
                                 "attributeId" => $keywords[3],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevel") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevel" => "1",
                                 "addressMode" => "02",
                                 "address" => $address,
                                 "destinationEndpoint" => "01",
                                 "Level" => intval($keywords[1] * 255 / 100),
                                 "duration" => $keywords[3],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevelOSRAM") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevel" => "1",
                                 "addressMode" => "02",
                                 "address" => $address,
                                 "destinationEndpoint" => "03",
                                 "Level" => intval($keywords[1] * 255 / 100),
                                 "duration" => $keywords[3],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevelVolet") {
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
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevelStop") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevelStop" => "1",
                                 "addressMode" => "02",
                                 "address" => $address,
                                 "sourceEndpoint" => "01",
                                 "destinationEndpoint" => "01",
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevelStopHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevelStop" => "1",
                                 "addressMode" => "02",
                                 "address" => $address,
                                 "sourceEndpoint" => "01",
                                 "destinationEndpoint" => "0B",
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevelHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevel" => "1",
                                 "addressMode" => "02",
                                 "address" => $address,
                                 "destinationEndpoint" => "0B",
                                 "Level" => intval($keywords[1] * 255 / 100),
                                 "duration" => $keywords[3],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setLevelGroup") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevel" => "1",
                                 "addressMode" => "01",
                                 "address" => $address,
                                 "destinationEndpoint" => "01",
                                 "Level" => intval($keywords[1] * 255 / 100),
                                 "duration" => $keywords[3],
                                 );
                
                //----------------------------------------------------------------------------
            } elseif ($action == "setColour") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setColour" => "1",
                                 "address" => $address,
                                 "X" => $keywords[1],
                                 "Y" => $keywords[3],
                                 "destinationEndPoint" => "01",
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setColourHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setColour" => "1",
                                 "address" => $address,
                                 "X" => $keywords[1],
                                 "Y" => $keywords[3],
                                 "destinationEndPoint" => "0B",
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setColourOSRAM") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setColour" => "1",
                                 "address" => $address,
                                 "X" => $keywords[1],
                                 "Y" => $keywords[3],
                                 "destinationEndPoint" => "03",
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setTemperature") {
                // T°K   Hex sent  Dec eq
                // 2200	 01C6	   454
                // 2700	 0172	   370
                // 4000	 00FA	   250
                // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                
                $temperatureConsigne = $msg;
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $temperatureConsigne = intval(-0.113333333 * $temperatureConsigne + 703.3333333);
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $temperatureConsigne = dechex( $temperatureConsigne );
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $Command = array(
                                 "setTemperature" => "1",
                                 "address" => $address,
                                 "temperature" => $temperatureConsigne,
                                 "destinationEndPoint" => "01",
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "setTemperatureSlider") {
                
                // T°K   Hex sent  Dec eq
                // 2200	 01C6	   454
                // 2700	 0172	   370
                // 4000	 00FA	   250
                // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                // Et slider va de 0 a 100 : disons 0 pour 2200K et 100 pour 4000K
                // Ce qui nous donne une equation T = (4000-2200)/100* $msg + 2200
                
                $temperatureConsigne = (4000-2200)/100 * $msg + 2200;
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                // Ensuite on calcule comme pour la fonction setTemperature
                $temperatureConsigne = intval(-0.113333333 * $temperatureConsigne + 703.3333333);
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $temperatureConsigne = dechex( $temperatureConsigne );
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                $Command = array(
                                 "setTemperature" => "1",
                                 "address" => $address,
                                 "temperature" => $temperatureConsigne,
                                 "destinationEndPoint" => "01",
                                 );
                
                //----------------------------------------------------------------------------
            } elseif ($action == "sceneGroupRecall") {
                
                $Command = array(
                                 "sceneGroupRecall"         => "1",
                                 "address"                  => $address,   // Ici c est l adresse du group.
                                 
                                 // "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "DestinationEndPoint"      => "ff",
                                 // "groupID"                  => $parameters['groupID'],
                                 "groupID"                  => $address,
                                 "sceneID"                  => $msg,
                                 );
                
                //----------------------------------------------------------------------------
            } elseif ($action == "Management_LQI_request") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "Management_LQI_request" => "1",
                                 "address" => $keywords[1],
                                 "StartIndex" => $keywords[3],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "IEEE_Address_request") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "IEEE_Address_request" => "1",
                                 "address" => $address,
                                 "shortAddress" => $keywords[1],
                                 // "requestType" => $keywords[3],
                                 // "startIndex" => $keywords[5],
                                 );
                //----------------------------------------------------------------------------
            } elseif ($action == "identifySend") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "identifySend" => "1",
                                 "address" => $address,
                                 "duration" => "0010", // $keywords[1]
                                 "DestinationEndPoint" => "01",
                                 );
            } elseif ($action == "identifySendHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "identifySend" => "1",
                                 "address" => $address,
                                 "duration" => "0010", // $keywords[1]
                                 "DestinationEndPoint" => "0B",
                                 );
            } /* at ruche level pour l instant */
            elseif ($action == "getGroupMembership") {
                $Command = array(
                                 "getGroupMembership" => "1",
                                 "address" => $address,
                                 );
            } /* elseif ($action == "") {
               $keywords = preg_split("/[=&]+/", $msg);
               $Command = array(
               "setLevel" => "1",
               "address" => $address,
               "clusterId" => "0008",
               "Level" => intval($keywords[1] * 255 / 100),
               "duration" => $keywords[3],
               );
               */
            elseif ($action == "bindShort") {
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
            }
            
            elseif ($action == "setReport") {
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
            }
            
            else {
                deamonlog('warning', 'AbeilleCommand unknown: '.$action );
            }
        }
        
        
        else {
            /*---------------------------------------------------------*/
            // if ($address == "Ruche") {
            $done = 0;
            
            // Crée les variables dans la chaine et associe la valeur.
            $fields = preg_split("/[=&]+/", $msg);
            if (count($fields) > 1) {
                $parameters = proper_parse_str( $msg );
            }
            
            if ($action == "ReadAttributeRequest") {
                $keywords = preg_split("/[=&]+/", $msg);
                deamonlog('debug', 'Msg Received: '.$msg);
                
                // Payload: address=7191&clusterId=0006&attributId=0000
                $Command = array(
                                 "ReadAttributeRequest" => "1",
                                 "address" => $keywords[1],
                                 "clusterId" => $keywords[3],
                                 "attributeId" => $keywords[5],
                                 );
                $address = $keywords[1];
                deamonlog('debug', 'Msg Received: '.$msg.' from Ruche');
                $done = 1;
            }
            
            if ($action == "bindShort") {
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
                
            }
            
            if ($action == "setReport") {
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
                
            }
            
            if ($action == "getGroupMembership") {
                if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "getGroupMembership"       => "1",
                                 "address"                  => $parameters['address'],
                                 "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 );
                $done = 1;
                
            }
            
            // Scene -----------------------------------------------------------------------------------------------
            
            if ($action == "viewScene") {
                
                if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "viewScene"                => "1",
                                 "address"                  => $parameters['address'],
                                 "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "groupID"                  => $parameters['groupID'],
                                 "sceneID"                  => $parameters['sceneID'],
                                 );
                $done = 1;
                
            }
            
            if ($action == "storeScene") {
                
                if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "storeScene"               => "1",
                                 "address"                  => $parameters['address'],
                                 "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "groupID"                  => $parameters['groupID'],
                                 "sceneID"                  => $parameters['sceneID'],
                                 );
                $done = 1;
                
            }
            
            if ($action == "recallScene") {
                
                if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "recallScene"              => "1",
                                 "address"                  => $parameters['address'],
                                 "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "groupID"                  => $parameters['groupID'],
                                 "sceneID"                  => $parameters['sceneID'],
                                 );
                $done = 1;
                
            }
            
            if ($action == "sceneGroupRecall") {
                
                // if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "sceneGroupRecall"         => "1",
                                 "address"                  => $parameters['addressGroup'],   // Ici c est l adresse du group.
                                 
                                 // "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "DestinationEndPoint"      => "ff",
                                 // "groupID"                  => $parameters['groupID'],
                                 "groupID"                  => $parameters['addressGroup'],
                                 "sceneID"                  => $parameters['sceneID'],
                                 );
                $done = 1;
                
            }
            
            if ($action == "addScene") {
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
                
            }
            
            if ($action == "getSceneMembership") {
                if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "getSceneMembership"       => "1",
                                 "address"                  => $parameters['address'],
                                 "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "groupID"                  => $parameters['groupID'],
                                 );
                $done = 1;
                
            }
            
            if ($action == "removeSceneAll") {
                if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                $Command = array(
                                 "removeSceneAll"           => "1",
                                 "address"                  => $parameters['address'],
                                 "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                 "groupID"                  => $parameters['groupID'],
                                 );
                $done = 1;
                
            }
            
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
        
        deamonlog('debug','processCmd call with: '.$toPrint);
        
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
    
    $parameters_info = Abeille::getParameters();
    
    if ($dest == 'none') {
        $dest = $resourcePath.'/COM';
        deamonlog('info', 'main: debug for com file: '.$dest);
        exec(system::getCmdSudo().'touch '.$dest.'; chmod 777 '.$dest.' > /dev/null 2>&1');
    }
    
    deamonlog( 'info', 'Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel );
    
    if ($GLOBALS['lib_phpMQTT']) {
        $mqtt = new phpMQTT($server, $port, $client_id);
        
        if (!$mqtt->connect(true, null, $username, $password)) {
            deamonlog( 'debug', 'Can t connect to mosquitto' );
            exit(1);
        }
        
        $topics['CmdAbeille/#'] = array("qos" => $qos, "function" => "procmsg");
        
        $mqtt->subscribe($topics, $qos);
        
        while ($mqtt->proc()) {
        }
        
        $mqtt->close();
        
    }
    else {
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
            if (1) {
                // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
                deamonlog( 'debug', 'Let loop for ever' );
                $client->loopForever();
            } else {
                while (true) {
                    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                    $client->loop();
                    //usleep(100);
                }
            }
            
            $client->disconnect();
            unset($client);
            
        } catch (Exception $e) {
            log::add('Abeille', 'error', $e->getMessage());
        }
    }
    
    
    
    deamonlog('info', 'Fin du script');
    
    
    ?>
