<?php
    
    
    /***
     *
     * AbeilleMQTTCCmdTimer subscribe to Abeille Timer topic .
     *
     *
     *
     */
    
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    
    require_once("lib/Tools.php");
    // include("CmdToAbeille.php");  // contient processCmd()
    include("lib/phpMQTT.php");
    include(dirname(__FILE__).'/includes/config.php');
    
    // Function
    function deamonlog($loglevel = 'NONE', $message = "")
    {
        Tools::deamonlog($loglevel,'AbeilleMQTTCmdTimer',$message);
    }
    
    /*
     + * Send a mosquitto message to jeedom
     + *
     + * @param $mqtt
     + * @param $SrcAddr
     + * @param $ClusterId
     + * @param $AttributId
     + * @param $data
     + * @param int $qos
     + */
    function mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos = 0)
    {
        // Abeille / short addr / Cluster ID - Attr ID -> data
        // deamonlog("debug","mqttPublish with Qos: ".$qos);
        if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
            $mqtt->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId, $data, $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-TimeStamp", time(), $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-Time", date("Y-m-d H:i:s"), $qos);
            $mqtt->close();
        } else {
            deamonlog('WARNING', 'Time out!');
            echo "\n\nWARNING', Time out!\n\n";
        }
    }
  
    function execCommandeTimer( $commande, $options ) {
        echo "Commande: " . $commande . "\n";
        try { // on fait cmd::byString pour trouver une commande mais si elle n'est pas trouvée ca genere une exception et le execCmd n'est pas executé.
            $cmd = cmd::byString( $commande );
            $cmd->execCmd( $options );
        } catch (Exception $e) {
            if ($debug) echo 'Exception reçue car la commande n est pas trouvee: ',  $e->getMessage(), "\n";
        }
    }
    
    function execScenarioTimer( $scenarioId, $options ) {
        echo "Scenario: " . $scenarioId . "\n";
        try { // on fait cmd::byString pour trouver une commande mais si elle n'est pas trouvée ca genere une exception et le execCmd n'est pas executé.
            $scenario = scenario::byId( $scenarioId );
            $scenario->execute();  // execute( $_trigger = '', $_message = '' )
        } catch (Exception $e) {
            if ($debug) echo 'Exception reçue car le scenario n est pas trouve: ',  $e->getMessage(), "\n";
        }
    }
    
    function checkExparies () {
        global $Timers;
        global $mqtt;
        global $refreshRate;
        global $lastUpdate;
        
        foreach ( $Timers as $address => $Timer ) {
            if ( $Timer != -1 ) {
                if ( $Timer <= time() ) {
                    echo $address . " expired\n";
                    
                    $eqLogicToFind = eqLogic::byLogicalId( "Abeille/".$address, "Abeille" );
                    // print_r( $eqLogicToFind ); echo "\n";
                    $eqLogicToFindId = $eqLogicToFind->getId();
                    // print_r( $eqLogicToFindId ); echo "\n";
                    $commandToFind = cmd::byEqLogicIdCmdName( $eqLogicToFindId, "Stop");
                    // print_r( $commandToFind ); echo "\n";
                    $commandToFind->execCmd();
                    
                }
                else {
                    // echo $address . " running \n";
                    if ( (time()-$lastUpdate) > $refreshRate ){
                        mqqtPublish($mqtt, $address, "Var", "Duration", $Timer-time(), $qos);
                    }
                }
            }
        }
        
        if ( (time()-$lastUpdate) > $refreshRate ){ $lastUpdate = time(); }

    }
    

    
    function procmsg($topic, $msg)
    {
        global $dest;
        global $mqtt;
        global $Timers;
        
        deamonlog('info', 'Msg Received: Topic: {'.$topic.'} => '.$msg);
        
        list($type, $address, $action) = explode('/', $topic);
        
        deamonlog('debug', 'Type: '.$type.' Address: '.$address.' avec Action: '.$action);
        
        if ($type == "CmdTimer") {
            //----------------------------------------------------------------------------
            // actionStart=#put_the_cmd_here#&durationSeconde=300
            if ($action == "TimerStart") {
                deamonlog('debug', 'TimeStart');
                $keywords = preg_split("/[=&]+/", $msg);
                if ( $keywords[2] == "durationSeconde" ) {
                    mqqtPublish($mqtt, $address, "0006", "0000", "1", $qos);
                    $Timers[$address] = time()+$keywords[3];
                    mqqtPublish($mqtt, $address, "Var", "ExpiryTime", date("Y-m-d H:i:s", $Timers[$address]), $qos);
                    mqqtPublish($mqtt, $address, "Var", "Duration", $Timers[$address]-time(), $qos);
                    // print_r($Timers);
                    if ( $keywords[0] == "actionStart" ) {
                        if ( $keywords[1] != "#put_the_cmd_here#" ) {
                            execCommandeTimer( $keywords[1] );
                            }
                        else { deamonlog('debug', "commande not set for TimerStart"); }
                    }
                    elseif ( $keywords[0] == "scenarioStart" ) {
                            execScenarioTimer( $keywords[1] );
                    }
                    else { deamonlog('debug', "Type de commande inconnue, vérifiez les parametres."); }
                }
                
                //----------------------------------------------------------------------------
                // actionCancel=#put_the_cmd_here#
            } elseif ($action == "TimerCancel") {
                deamonlog('debug', 'TimerCancel');
                $keywords = preg_split("/[=&]+/", $msg);
                mqqtPublish($mqtt, $address, "0006", "0000", "0", $qos);
                mqqtPublish($mqtt, $address, "Var", "ExpiryTime", "-", $qos);
                mqqtPublish($mqtt, $address, "Var", "Duration", "-", $qos);
                $Timers[$address] = -1;
                // print_r($Timers);
                
                if ( $keywords[0] == "actionCancel" ) {
                    if ( $keywords[1] != "#put_the_cmd_here#" ) {
                        execCommandeTimer( $keywords[1] );
                        
                    }
                    else { deamonlog('debug', "commande not set for TimerCancel"); }
                }
                elseif ( $keywords[0] == "scenarioCancel" ) {
                    execScenarioTimer( $keywords[1] );
                }
                else { deamonlog('debug', "Type de commande inconnue, vérifiez les parametres."); }
                
                //----------------------------------------------------------------------------
                // actionStop=#put_the_cmd_here#
            } elseif ($action == "TimerStop") {
                deamonlog('debug', 'TimerStop');
                $keywords = preg_split("/[=&]+/", $msg);
                
                mqqtPublish($mqtt, $address, "0006", "0000", "-", $qos);
                mqqtPublish($mqtt, $address, "Var", "ExpiryTime", "-", $qos);
                mqqtPublish($mqtt, $address, "Var", "Duration", "-", $qos);
                
                $Timers[$address] = -1;
                // print_r($Timers);
                
                // Necessaire par exemple pour la commande qui envoie un sms
                if ( $keywords[2] == "message" ) {
                    $options['message'] = $keywords[3];
                }
                
                if ( $keywords[0] == "actionStop" ) {
                    if ( $keywords[1] != "#put_the_cmd_here#" ) {
                        execCommandeTimer( $keywords[1], $options );
                    }
                    else { deamonlog('debug',"commande not set for TimerStop"); }
                }
                elseif ( $keywords[0] == "scenarioStop" ) {
                        execScenarioTimer( $keywords[1] );
                    }
                else { deamonlog('debug', "Type de commande inconnue, vérifiez les parametres."); }
                
                
                //----------------------------------------------------------------------------
            } else {
                deamonlog('debug', 'Command unknown, so not processed.');
            }
            
            //----------------------------------------------------------------------------
            
        }
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
    $client_id = "AbeilleMQTTCmdTimer"; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos = $argv[6];
    $requestedlevel = $argv[7];
    $requestedlevel = '' ? 'none' : $argv[7];
    $mqtt = new phpMQTT($server, $port, $client_id);
    
    $refreshRate = 5; // s
    $lastUpdate = time();
    
    $Timers = array();
    
    deamonlog(
              'info',
              'Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel
              );
    echo 'Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel."\n" ;
    
    if (!$mqtt->connect(true, null, $username, $password)) {
        exit(1);
    }
    
    $topics['CmdTimer/#'] = array("qos" => $qos, "function" => "procmsg");
    
    $mqtt->subscribe($topics, $qos);
    
    
    while ($mqtt->proc()) {
        sleep(0.1);
        checkExparies();
    }
     
    
    $mqtt->close();
    
    deamonlog('info', 'Fin du script');
    
    
    ?>
