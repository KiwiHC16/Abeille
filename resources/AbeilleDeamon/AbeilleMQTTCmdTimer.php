<?php
    
    
    /***
     *
     * AbeilleMQTTCCmdTimer subscribe to Abeille Timer topic .
     *
     *
     *
     */
    
    $lib_phpMQTT = 0;
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    
    require_once("lib/Tools.php");
    // include("CmdToAbeille.php");  // contient processCmd()
    if ( $lib_phpMQTT ) {  include("lib/phpMQTT.php"); }
    include(dirname(__FILE__).'/includes/config.php');
    include(dirname(__FILE__).'/includes/function.php');
    
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
        if ( $GLOBAL['lib_phpMQTT'] ) {
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
        else {
            $mqtt->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId,    $data,               $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-TimeStamp",                 time(),              $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-Time",                      date("Y-m-d H:i:s"), $qos);
        }
    }
    
    
    function execCommandeTimer( $commande, $options ) {
        // echo "Commande: " . $commande . "\n";
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
        global $RefreshWidgetRate;
        global $lastWidgetUpdate;
        global $RefreshCmdRate;
        global $lastCmdUpdate;
        
        foreach ( $Timers as $address => $Timer ) {
            if ( $Timer != -1 ) {
                if ( $Timer['T3'] <= time() ) {
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
                    if ( (time()-$lastWidgetUpdate) > $RefreshWidgetRate ){
                        mqqtPublish($mqtt, $address, "Var", "Duration", $Timer['T3']-time(), $qos);
                        // print_r( $Timer ); echo "\n";
                    }
                    
                    if ( (time()-$lastCmdUpdate) > $RefreshCmdRate ) {
                        $now = time();
                        if ( $now < $Timer['T0'] ) { $phase = '->T0'; $RampUpDown = 0; }
                        if ( ($Timer['T0'] <= $now) && ($now<$Timer['T1']) ) { $phase = 'T0->T1'; $RampUpDown = ($now-$Timer['T0'])/($Timer['T1']-$Timer['T0'])*100; }
                        if ( ($Timer['T1'] <= $now) && ($now<$Timer['T2']) ) { $phase = 'T1->T2'; $RampUpDown = 100; }
                        if ( ($Timer['T2'] <= $now) && ($now<$Timer['T3']) ) { $phase = 'T2->T3'; $RampUpDown = 100-($now-$Timer['T2'])/($Timer['T3']-$Timer['T2'])*100; }
                        if ( $Timer['T3'] < $now ) { $phase = 'T3->';$RampUpDown = 0; }
                        
                        mqqtPublish($mqtt, $address, "Var", "RampUpDown", $RampUpDown, $qos);
                        
                        // Si nous sommes dans une phase de ramp on enoie la commande, si on change de phase on envoi le derniere commande ramp pour etre sur d etre a 100% ou 0%
                        if ( ($phase == 'T0->T1') || ($phase == 'T2->T3') || ($phase != $Timer['state']) ) {
                            if ( isset($Timer["actionRamp"]) ) {
                                $options['slider'] = $RampUpDown;
                                execCommandeTimer( $Timer["actionRamp"], $options );
                            }
                            if ( isset($Timer["scenarioRamp"]) ) {
                                execScenarioTimer( $Timer["scenarioRamp"], $options );
                            }
                            $Timers[$address]['state'] = $phase;
                        }
                        
                    }
                }
            }
        }
        
        if ( (time()-$lastWidgetUpdate) > $RefreshWidgetRate ){ $lastWidgetUpdate = time(); }
        if ( (time()-$lastCmdUpdate) > $RefreshCmdRate ){ $lastCmdUpdate = time(); }

    }
    

    
    function procmsg($topic, $msg)
    {
        global $dest;
        global $mqtt;
        global $Timers;
        
        // Process only CmdTimer messages.
        if ($type != "CmdTimer") { return ; }
        
        deamonlog('info', ';Msg Received: Topic: {'.$topic.'} => '.$msg);
        
        list($type, $address, $action) = explode('/', $topic);
        
        deamonlog('debug', 'Type: '.$type.' Address: '.$address.' avec Action: '.$action);
        
        // Crée les variables dans la chaine et associe la valeur.
        $parameters = proper_parse_str( $msg );
        // print_r( $parameters );
        
        //----------------------------------------------------------------------------
        // actionStart=#put_the_cmd_here#&durationSeconde=300&RampUp=10&RampDown=10&actionRamp=#put_the_cmd_here#
        // T0 = Start
        // T1 = Start + RampUp
        // T2 = Start + RampUp + Duration
        // T3 = Start + RampUp + Duration + RampDown
        
        // Action Start est à T0
        // Action Stop est à T3
        
        if ($action == "TimerStart") {
            deamonlog('debug', 'TimeStart');
            // $keywords = preg_split("/[=&]+/", $msg);
            if ( isset($parameters["durationSeconde"]) ) {
                
                mqqtPublish($mqtt, $address, "0006", "0000", "1", $qos);
                
                if ( isset($parameters["messageStart"]) ) {
                    $options['message'] = $parameters["messageStart"];
                }
                
                // On verifie les temps
                if ( isset($parameters['RampUp']) ) {
                    if ( $parameters['RampUp'] < 1 ) { $parameters['RampUp'] = 1; }
                }
                else { $parameters['RampUp'] = 1; }
                
                if ( isset($parameters['durationSeconde']) ) {
                    if ( $parameters['durationSeconde'] < 1 ) { $parameters['durationSeconde'] = 1; }
                }
                else { $parameters['durationSeconde'] = 1; }
                
                if ( isset($parameters['RampDown']) ) {
                    if ( $parameters['RampDown'] < 1 ) { $parameters['RampDown'] = 1; }
                }
                else { $parameters['RampDown'] = 1; }
                
                // On crée un Timer avec ses parametres
                $Timers[$address] = $parameters;
                
                // On calcule les points/temps de passages
                $now = time();
                $Timers[$address]['T0'] = $now;
                $Timers[$address]['T1'] = $now + $parameters['RampUp'];
                $Timers[$address]['T2'] = $now + $parameters['RampUp'] + $parameters['durationSeconde'];
                $Timers[$address]['T3'] = $now + $parameters['RampUp'] + $parameters['durationSeconde'] + $parameters['RampDown'];
                $Timers[$address]['state'] = 'T0->T1';
                
                // print_r( $Timers );
                
                mqqtPublish($mqtt, $address, "Var", "ExpiryTime", date("Y-m-d H:i:s", $Timers[$address]['T3']), $qos);
                mqqtPublish($mqtt, $address, "Var", "Duration", $Timers[$address]['T3']-time(), $qos);
                // print_r($Timers);
                if ( isset($parameters["actionStart"]) ) {
                    if (  $parameters["actionStart"] != "#put_the_cmd_here#" ) {
                        execCommandeTimer( $parameters["actionStart"], $options );
                    }
                    else { deamonlog('debug', "commande not set for TimerStart"); }
                }
                elseif ( isset($parameters["scenarioStart"]) ) {
                    execScenarioTimer( $parameters["scenarioStart"] );
                }
                else { deamonlog('debug', "Type de commande inconnue, vérifiez les parametres."); }
            }
            
            //----------------------------------------------------------------------------
            // actionCancel=#put_the_cmd_here#
        } elseif ($action == "TimerCancel") {
            deamonlog('debug', 'TimerCancel');
            // $keywords = preg_split("/[=&]+/", $msg);
            mqqtPublish($mqtt, $address, "0006", "0000", "0", $qos);
            mqqtPublish($mqtt, $address, "Var", "ExpiryTime", "-", $qos);
            mqqtPublish($mqtt, $address, "Var", "Duration", "-", $qos);
            // mqqtPublish($mqtt, $address, "Var", "RampUpDown", "0", $qos); // Cancel, je laisse en l etat, stop je mets a 0
            $Timers[$address] = -1;
            // print_r($Timers);
            
            if ( isset($parameters["message"]) ) {
                $options['message'] = $parameters["message"];
            }
            
            if ( isset($parameters["actionCancel"]) ) {
                if ( $parameters["actionCancel"] != "#put_the_cmd_here#" ) {
                    execCommandeTimer( $parameters["actionCancel"], $options );
                    
                }
                else { deamonlog('debug', "commande not set for TimerCancel"); }
            }
            elseif ( $parameters["scenarioCancel"] ) {
                execScenarioTimer( $parameters["scenarioCancel"] );
            }
            else { deamonlog('debug', "Type de commande inconnue, vérifiez les parametres."); }
            
            unset( $Timers[$address] );
            
            //----------------------------------------------------------------------------
            // actionStop=#put_the_cmd_here#
        } elseif ($action == "TimerStop") {
            deamonlog('debug', 'TimerStop');
            // $keywords = preg_split("/[=&]+/", $msg);
            
            mqqtPublish($mqtt, $address, "0006", "0000", "-", $qos);
            mqqtPublish($mqtt, $address, "Var", "ExpiryTime", "-", $qos);
            mqqtPublish($mqtt, $address, "Var", "Duration", "-", $qos);
            mqqtPublish($mqtt, $address, "Var", "RampUpDown", "0", $qos);
            
            // $Timers[$address] = -1;
            // print_r($Timers);
            
            // Necessaire par exemple pour la commande qui envoie un sms
            if ( isset($parameters["message"]) ) {
                $options['message'] = $parameters["message"];
            }
            
            if ( isset($parameters["actionStop"]) ) {
                if ( $parameters["actionStop"] != "#put_the_cmd_here#" ) {
                    execCommandeTimer( $parameters["actionStop"], $options );
                }
                else { deamonlog('debug',"commande not set for TimerStop"); }
            }
            elseif ( isset($parameters["scenarioStop"]) ) {
                execScenarioTimer( $parameters["scenarioStop"] );
            }
            else { deamonlog('debug', "Type de commande inconnue, vérifiez les parametres."); }
            
            unset( $Timers[$address] );
            
            
            //----------------------------------------------------------------------------
        } else {
            deamonlog('debug', 'Command unknown, so not processed.');
        }
        
        //----------------------------------------------------------------------------
        
        
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
    $mqtt_id = "AbeilleMQTTCmdTimer"; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos = $argv[6];
    $requestedlevel = $argv[7];
    $requestedlevel = '' ? 'none' : $argv[7];
    
    
    $RefreshWidgetRate = 5; // s
    $RefreshCmdRate = 1; // s
    $lastWidgetUpdate = time();
    $lastCmdUpdate = time();
    
    $Timers = array();
    
    $parameters_info = Abeille::getParameters();
    
    deamonlog('info', 'Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel );
    echo 'Processing MQTT message from '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos.' with log level '.$requestedlevel."\n" ;
    
    if ($GLOBAL['lib_phpMQTT']) {
        $mqtt = new phpMQTT($server, $port, $mqtt_id);
        
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
    }
    else {
        deamonlog( 'debug', 'Create a MQTT Client');
        
        // https://github.com/mgdm/Mosquitto-PHP
        // http://mosquitto-php.readthedocs.io/en/latest/client.html
        $mqtt = new Mosquitto\Client($mqtt_id);
        
        // var_dump( $mqtt );
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
        $mqtt->onConnect('connect');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
        $mqtt->onDisconnect('disconnect');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
        $mqtt->onSubscribe('subscribe');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
        $mqtt->onMessage('message');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onLog
        $mqtt->onLog('logmq');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
        $mqtt->setWill('/jeedom', "Client AbeilleMQTTCmd died :-(", $qos, 0);
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
        $mqtt->setReconnectDelay(1, 120, 1);
        
        // var_dump( $mqtt );
        
        try {
            deamonlog('info', 'try part');
            
            $mqtt->setCredentials( $username, $password );
            $mqtt->connect( $server, $port, 60 );
            $mqtt->subscribe( $parameters_info['AbeilleTopic'], $qos ); // !auto: Subscribe to root topic
            
            deamonlog( 'debug', 'Subscribed to topic: '.$parameters_info['AbeilleTopic'] );
            
            // 1 to use loopForever et 0 to use while loop
            if (0) {
                // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
                deamonlog( 'debug', 'Let loop for ever' );
                $mqtt->loopForever();
            } else {
                while (true) {
                    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                    $mqtt->loop();
                    //usleep(100);
                    sleep(0.1);
                    checkExparies();
                }
            }
            
            $mqtt->disconnect();
            unset($mqtt);
            
        } catch (Exception $e) {
            log::add('Abeille', 'error', $e->getMessage());
        }

    }
    
    deamonlog('info', 'Fin du script');
    
    
    ?>
