<?php
    
    
    /***
     * AbeilleMQTTCCmd subscribe to Abeille topic and receive message sent by AbeilleParser.
     *
     *
     *
     */
    
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';

    require_once("lib/Tools.php");
    include("CmdToAbeille.php");  // contient processCmd()
    include("lib/phpMQTT.php");
    include (dirname(__FILE__).'/includes/config.php');

function deamonlog($loglevel='NONE',$message=""){
    Tools::deamonlog($loglevel,'AbeilleMQTTCCmd',$message);
}
    
    function procmsg($topic, $msg)
    {
        global $dest;
        
        deamonlog('info','Msg Received: Topic: {'.$topic.'} => '.$msg);
        
        list($type, $address, $action) = explode('/', $topic);
        
        deamonlog('debug','Type: '.$type.' Address: '.$address.' avec Action: '.$action);
        
        if ($type == "CmdAbeille") {
            //----------------------------------------------------------------------------
            if ($action == "Annonce") {
                $Command = array(
                                 "ReadAttributeRequest" => "1",
                                 "address" => $address,
                                 "clusterId" => "0000",
                                 "attributeId" => "0005",
                                 );
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
                                 "destinationEndpoint"=>"01",
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
                                 "destinationEndpoint"=>"0B",
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
                                 "destinationEndpoint"=>"03",
                                 "action" => $actionId,
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
                                     "destinationEndpoint"=>"01", // Set but not send on radio
                                     "action"=>$actionId,
                                     );
            //----------------------------------------------------------------------------
            } elseif ($action == "ReadAttributeRequest") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "ReadAttributeRequest" => "1",
                                 "address" => $address,
                                 "clusterId" => $keywords[1],
                                 "attributeId" => $keywords[3],
                                 );
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
                                 "destinationEndpoint"=>"01",
                                 "Level" => intval($keywords[1] * 255 / 100),
                                 "duration" => $keywords[3],
                                 );
            //----------------------------------------------------------------------------
            } elseif ($action == "setLevelHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "setLevel" => "1",
                                 "addressMode" => "02",
                                 "address" => $address,
                                 "destinationEndpoint"=>"0B",
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
                                 "destinationEndpoint"=>"01",
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
            }
            
            
            elseif ($action == "identifySend") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "identifySend" => "1",
                                 "address" => $address,
                                 "duration"=> "0010", // $keywords[1]
                                 "DestinationEndPoint" => "01",
                                 );
            }
            elseif ($action == "identifySendHue") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                                 "identifySend" => "1",
                                 "address" => $address,
                                 "duration"=> "0010", // $keywords[1]
                                 "DestinationEndPoint" => "0B",
                                 );
            }
            
            
            

            
            /* at ruche level pour l instant
            elseif ($action == "getGroupMembership") {
                $Command = array(
                                 "getGroupMembership" => "1",
                                 "address" => $address
                                 );
            }
            */
             
            /* elseif ($action == "") {
             $keywords = preg_split("/[=&]+/", $msg);
             $Command = array(
             "setLevel" => "1",
             "address" => $address,
             "clusterId" => "0008",
             "Level" => intval($keywords[1] * 255 / 100),
             "duration" => $keywords[3],
             );
             */
            
            else {
                if ( $address != "Ruche"){
                    deamonlog('warning','AbeilleCommand unknown: '.$action.' not even for ruche');
                }
            }
            
            
            /*---------------------------------------------------------*/
            if ($address == "Ruche") {
                $keywords = preg_split("/[=&]+/", $msg);
                
                // Si une string simple
                if (count($keywords) == 1) {
                    $Command = array($action => $msg);
                } // Si une command type get http param1=value1&param2=value2
                else {
                    if (count($keywords) == 2) {
                        deamonlog('debug','2 arguments command');
                        $Command = array(
                                         $action => $action,
                                         $keywords[0] => $keywords[1]
                                         );
                    }
                    if (count($keywords) == 4) {
                        deamonlog('debug','4 arguments command');
                        $Command = array(
                                         $action => $action,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         );
                    }
                    if (count($keywords) == 6) {
                        deamonlog('debug','6 arguments command');
                        $Command = array(
                                         $action => $action,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         $keywords[4] => $keywords[5],
                                         );
                    }
                }
            }
            
            
            /*---------------------------------------------------------*/
            
            // print_r( $Command );
            processCmd($dest, $Command,$GLOBALS['requestedlevel']);
        } else {
            deamonlog('warning', 'Msg Received: Topic: {'.$topic.'} =>'.$msg.'mais je ne sais pas quoi en faire, no action.');
        }
    }
    
    // MAIN
    //                      1          2           3       4          5       6
    //$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;
    
    $dest = $argv[1];
    $server = $argv[2];     // change if necessary
    $port = $argv[3];                     // change if necessary
    $username = $argv[4];                   // set your username
    $password =  $argv[5];                   // set your password
    $client_id = "AbeilleMQTTCmd"; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos=$argv[6];
    $mqtt = new phpMQTT($server, $port, $client_id);
    $requestedlevel=$argv[7];
    $requestedlevel=''?'none':$argv[7];
    
    if ($dest=='none'){
        $dest=$resourcePath.'/COM';
        deamonlog('info', 'main: debug for com file: '.$dest);
        exec(system::getCmdSudo().'touch '.$dest.'chmod 777 '.$dest.' > /dev/null 2>&1');
    }
    
    deamonlog('info','Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel);
    
    
    if (!$mqtt->connect(true, null, $username, $password)) {
        exit(1);
    }
    
    $topics['CmdAbeille/#'] = array("qos" => $qos, "function" => "procmsg");
    
    $mqtt->subscribe($topics, $qos);
    
    while ($mqtt->proc()) {
    }
    
    $mqtt->close();
    
    deamonlog('info','Fin du script');
    
    
    ?>
