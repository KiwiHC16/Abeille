<?php
    
    
    /***
     *
     * AbeilleMQTTCCmdTimer subscribe to Abeille Timer topic .
     *
     *
     *
     */
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__).'/Abeille.class.php';
    require_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';
    include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
    include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/function.php';
    
    class debug extends Tools {
        function deamonlog($loglevel = 'NONE', $message = "") {
            if ($this->debug["cli"] ) {
                echo "[".date("Y-m-d H:i:s").'][AbeilleMQTTCmdTimer][DEBUG.BEN] '.$message."\n";
            }
            else {
                $this->deamonlogFilter($loglevel,'Abeille', 'AbeilleMQTTCmd',$message);
            }
        }
    }

    class AbeilleTimer extends debug {
        public $debug = array(  "cli"                 => 1, // commande line mode or jeedom
                              "AbeilleTimerClass" => 1,
                              "procmsg" => 0, );
        public $parameters_info;
        public $Timers = array();
        public $lastWidgetUpdate;
        public $lastCmdUpdate;
        public $RefreshWidgetRate;
        public $RefreshCmdRate;
        public $queueKeyAbeilleToTimer;
        public $queueKeyTimerToAbeille;
        
        function __construct($client_id) {
            global $argv;
            
            if ($this->debug["AbeilleTimerClass"]) $this->deamonlog("debug", "AbeilleTimer constructor");
            $this->parameters_info = Abeille::getParameters();
            
            $this->requestedlevel = $argv[7];
            $this->requestedlevel = '' ? 'none' : $argv[7];
            $GLOBALS['requestedlevel'] = $this->requestedlevel ;
            
            $this->RefreshWidgetRate = 5; // s
            $this->RefreshCmdRate = 1; // s
            $this->lastWidgetUpdate = time();
            $this->lastCmdUpdate = time();
            
            // parent::__construct($client_id, $this->parameters_info["AbeilleUser"], $this->parameters_info["AbeillePass"], $this->parameters_info["AbeilleAddress"], $this->parameters_info["AbeillePort"], $this->parameters_info["AbeilleTopic"], $this->parameters_info["AbeilleQos"], $this->debug["AbeilleMQTTCmdClass"] );
            
        }
        
        function publishTimer( $SrcAddr, $ClusterId, $AttributId, $data ) {
            // Abeille / short addr / Cluster ID - Attr ID -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message['topic'] = "Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId;
            $msgAbeille->message['payload'] = $data;
            
            if (msg_send( $this->queueKeyTimerToAbeille, 1, $msgAbeille, true, false)) {
                log::add('Abeille', 'Debug', '(publishTimer) Msg sent'.json_encode($msgAbeille));
            }
            else {
                log::add('Abeille', 'Debug', '(publishTimer) Could not send Msg');
            }
            
            
        }
        
        function execCommandeTimer( $commande, $options ) {
            // echo "Commande: " . $commande . "\n";
            try { // on fait cmd::byString pour trouver une commande mais si elle n'est pas trouvée ca genere une exception et le execCmd n'est pas executé.
                if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'execCommandeTimer fct - looking for cmd');
                if ( $this->debug['procmsg'] ) $this->deamonlog('debug', json_encode(cmd::humanReadableToCmd($commande)));
                if ( str_replace('#', '', cmd::humanReadableToCmd($commande)) > 0 ) {
                    $cmd = cmd::byString( $commande );
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'execCommandeTimer fct - exec cmd found:'.json_encode($cmd));
                    $cmd->execCmd( $options );
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'execCommandeTimer fct - exec cmd done');
                }
                else {
                    $this->deamonlog('debug', "I can t find this command: ".$commande);
                }
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
        
        function checkExparies() {
            
            foreach ( $this->Timers as $address => $Timer ) {
                
                if ( $Timer['T3'] <= time() ) {
                    // echo $address . " expired\n";
                    
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
                    if ( (time()-$this->lastWidgetUpdate) > $this->RefreshWidgetRate ){
                        $this->publishTimer( $address, "Var", "Duration", $Timer['T3']-time() );
                    }
                    
                    if ( (time()-$this->lastCmdUpdate) > $this->RefreshCmdRate ) {
                        $now = time();
                        if ( $now < $Timer['T0'] )                            { $phase = '->T0';    $RampUpDown = 0; }
                        if ( ($Timer['T0'] <= $now) && ($now<$Timer['T1']) )  { $phase = 'T0->T1';  $RampUpDown = ($now-$Timer['T0'])/($Timer['T1']-$Timer['T0'])*100; }
                        if ( ($Timer['T1'] <= $now) && ($now<$Timer['T2']) )  { $phase = 'T1->T2';  $RampUpDown = 100; }
                        if ( ($Timer['T2'] <= $now) && ($now<$Timer['T3']) )  { $phase = 'T2->T3';  $RampUpDown = 100-($now-$Timer['T2'])/($Timer['T3']-$Timer['T2'])*100; }
                        if ( $Timer['T3'] < $now )                            { $phase = 'T3->';    $RampUpDown = 0; }
                        
                        $this->publishTimer( $address, "Var", "RampUpDown", $RampUpDown );
                        
                        // Si nous sommes dans une phase de ramp on enoie la commande, si on change de phase on envoi le derniere commande ramp pour etre sur d etre a 100% ou 0%
                        if ( ($phase == 'T0->T1') || ($phase == 'T2->T3') || ($phase != $Timer['state']) ) {
                            if ( isset($Timer["actionRamp"]) ) {
                                if ( strlen($Timer["actionRamp"]) > 1 ) {
                                    $options['slider'] = $RampUpDown;
                                    $this->execCommandeTimer( $Timer["actionRamp"], $options );
                                }
                            }
                            if ( isset($Timer["scenarioRamp"]) ) {
                                execScenarioTimer( $Timer["scenarioRamp"], $options );
                            }
                            $this->Timers[$address]['state'] = $phase;
                        }
                        
                    }
                }
                
            }
            
            if ( (time()-$this->lastWidgetUpdate) > $this->RefreshWidgetRate ){ $this->lastWidgetUpdate = time(); }
            if ( (time()-$this->lastCmdUpdate) > $this->RefreshCmdRate ){ $this->lastCmdUpdate = time(); }
            
        }
        
        function procmsg($message) {
            
            // if ( $this->debug['procmsg'] ) $this->deamonlog("debug", "----------");
            // if ( $this->debug['procmsg'] ) $this->deamonlog("debug", "procmsg fct - topic: ". $message->topic . " len: " . strlen($this->parameters_info["AbeilleTopic"]) );
            
            $parameters_info = Abeille::getParameters();
            
            $this->deamonlog("debug", "Topic: ->".$message->topic."<- Value ->".$message->payload."<-");
            
            $topic = $message->topic;
            $msg = $message->payload;
            
            $test = explode('/', $topic);
            if ( sizeof( $test ) !=3 ) {
                $this->deamonlog("debug", "procmsg fct - Le format du message n est pas bon je ne le traite pas !!!");
                return ;
            }
            
            // Process only CmdTimer messages.
            list($type, $address, $action) = explode('/', $topic);
            if ($type != "CmdTimer") {
                if ( $this->debug['procmsg'] ) $this->deamonlog('warning','procmsg fct - Msg Received: Topic: {'.$topic.'} => '.$msg.' mais ce n est pas pour moi, no action.');
                return;
            }
            
            if ( $this->debug['procmsg'] ) $this->deamonlog('info', 'AbeilleMQTTCmdimer - procmsg fct - ;Msg Received: Topic: {'.$topic.'} => '.$msg);
            // if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'AbeilleMQTTCmdimer - procmsg fct - Type: '.$type.' Address: '.$address.' avec Action: '.$action);
            
            // Crée les variables dans la chaine et associe la valeur.
            $parameters = proper_parse_str( $msg );
            // if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'AbeilleMQTTCmdimer - procmsg fct - Parametres: '.json_encode($parameters));
            
            //----------------------------------------------------------------------------
            // actionStart=#put_the_cmd_here#&durationSeconde=300&RampUp=10&RampDown=10&actionRamp=#put_the_cmd_here#
            // T0 = Start
            // T1 = Start + RampUp
            // T2 = Start + RampUp + Duration
            // T3 = Start + RampUp + Duration + RampDown
            
            // Action Start est à T0
            // Action Stop est à T3
            
            if ($action == "TimerStart") {
                if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - TimeStart');
                // $keywords = preg_split("/[=&]+/", $msg);
                if ( isset($parameters["durationSeconde"]) ) {
                    
                    // Passe l etat à 1.
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - passe a 1');
                    $this->publishTimer( $address, "0006", "0000", "1" );
                    
                    // On verifie les temps
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - check time');
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
                    
                    // Necessaire par exemple pour la commande qui envoie un sms
                    if ( isset($parameters["message"]) ) {
                        $options['message'] = $parameters["message"];
                    }
                    
                    // On crée un Timer avec ses parametres
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - create timer');
                    $this->Timers[$address] = $parameters;
                    
                    // On calcule les points/temps de passages
                    $now = time();
                    $this->Timers[$address]['T0'] = $now;
                    $this->Timers[$address]['T1'] = $now + $parameters['RampUp'];
                    $this->Timers[$address]['T2'] = $now + $parameters['RampUp'] + $parameters['durationSeconde'];
                    $this->Timers[$address]['T3'] = $now + $parameters['RampUp'] + $parameters['durationSeconde'] + $parameters['RampDown'];
                    $this->Timers[$address]['state'] = 'T0->T1';
                    
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - sent time info to abeille');
                    $this->publishTimer( $address, "Var", "ExpiryTime", date("Y-m-d H:i:s", $this->Timers[$address]['T3']) );
                    $this->publishTimer( $address, "Var", "Duration", $this->Timers[$address]['T3']-time() );
                    
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - manage action');
                    if ( isset($parameters["actionStart"]) ) {
                        if ( strlen($parameters["actionStart"])<1 ) $this->deamonlog('debug', "Pas de commande start definie.");
                        elseif (  $parameters["actionStart"] == "#put_the_cmd_here#" ) $this->deamonlog('debug', "Valeur par defaut, Pas de commande start definie.");
                        else {
                            if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - exec action');
                            $this->execCommandeTimer( $parameters["actionStart"], $options );
                        }
                    }
                    
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - manage senario');
                    if ( isset($parameters["scenarioStart"]) ) {
                        $this->execScenarioTimer( $parameters["scenarioStart"] );
                    }
                    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - start all done');
                }
                
                //----------------------------------------------------------------------------
                // actionCancel=#put_the_cmd_here#
            } elseif ($action == "TimerCancel") {
                if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - TimerCancel');
                // $keywords = preg_split("/[=&]+/", $msg);
                $this->publishTimer($address, "0006", "0000", "0");
                $this->publishTimer($address, "Var", "ExpiryTime", "-");
                $this->publishTimer($address, "Var", "Duration", "-");
                
                // Necessaire par exemple pour la commande qui envoie un sms
                if ( isset($parameters["message"]) ) {
                    $options['message'] = $parameters["message"];
                }
                
                if ( isset($parameters["actionCancel"]) ) {
                    if ( strlen($parameters["actionCancel"])<1 ) $this->deamonlog('debug', "Pas de commande cancel definie.");
                    elseif ( $parameters["actionCancel"] == "#put_the_cmd_here#" ) $this->deamonlog('debug', "Valeur par defaut, Pas de commande cancel definie.");
                    else $this->execCommandeTimer( $parameters["actionCancel"], $options );
                }
                
                if ( $parameters["scenarioCancel"] ) {
                    $this->execScenarioTimer( $parameters["scenarioCancel"] );
                }
                
                unset( $this->Timers[$address] );
                
                //----------------------------------------------------------------------------
                // actionStop=#put_the_cmd_here#
            } elseif ($action == "TimerStop") {
                $this->deamonlog('debug', 'TimerStop');
                // $keywords = preg_split("/[=&]+/", $msg);
                
                $this->publishTimer($address, "0006", "0000", "-");
                $this->publishTimer($address, "Var", "ExpiryTime", "-");
                $this->publishTimer($address, "Var", "Duration", "-");
                $this->publishTimer($address, "Var", "RampUpDown", "0");
                
                // Necessaire par exemple pour la commande qui envoie un sms
                if ( isset($parameters["message"]) ) {
                    $options['message'] = $parameters["message"];
                }
                
                if ( isset($parameters["actionStop"]) ) {
                    if ( strlen($parameters["actionStop"])<1 ) $this->deamonlog('debug', "Pas de commande stop definie.");
                    elseif ( $parameters["actionStop"] == "#put_the_cmd_here#" ) $this->deamonlog('debug', "Valeur par defaut, Pas de commande stop definie.");
                    else $this->execCommandeTimer( $parameters["actionStop"], $options );
                }
                
                if ( isset($parameters["scenarioStop"]) ) {
                    $this->execScenarioTimer( $parameters["scenarioStop"] );
                }
                
                unset( $this->Timers[$address] );
                
                
                //----------------------------------------------------------------------------
            }
            else {
                $this->deamonlog('debug', 'Command unknown, so not processed.');
            }
            
            //----------------------------------------------------------------------------
            
        }
        
    }
    
    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    //                      1          2           3       4          5       6
    //$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;
    
    $AbeilleTimer = new AbeilleTimer("AbeilleTimer");
    
    try {
        
        if ( $AbeilleTimer->debug['AbeilleTimerClass'] ) $AbeilleTimer->deamonlog("debug", json_encode( $AbeilleTimer ) );

        $AbeilleTimer->deamonlog("debug", "Let s start" );
        
        $AbeilleTimer->queueKeyAbeilleToTimer = msg_get_queue(queueKeyAbeilleToTimer);
        $AbeilleTimer->queueKeyTimerToAbeille = msg_get_queue(queueKeyTimerToAbeille);
        
        $msg_type = NULL;
        $msg = NULL;
        $max_msg_size = 512;
        
        while ( true ) {
            if (msg_receive( $AbeilleTimer->queueKeyAbeilleToTimer, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT)) {
                $AbeilleTimer->deamonlog("debug", "Message pulled from queue for 124: ".$msg->message['topic']." -> ".$msg->message['payload']);
                $message->topic = $msg->message['topic'];
                $message->payload = $msg->message['payload'];
                $AbeilleTimer->procmsg($message);
                $msg_type = NULL;
                $msg = NULL;
            }
            // $AbeilleTimer->deamonlog("debug", "coucou" );
            $AbeilleTimer->checkExparies();
            time_nanosleep( 0, 10000000 ); // 1/100s
        }
    }
    catch (Exception $e) {
        $AbeilleTimer->deamonlog('debug', 'error', $e->getMessage());
        $AbeilleTimer->deamonlog('info', 'Fin du script');
    }
    
    
    unset($AbeilleTimer);
    
    ?>
