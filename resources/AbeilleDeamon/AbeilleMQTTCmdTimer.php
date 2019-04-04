<?php


/***
*
* AbeilleMQTTCCmdTimer subscribe to Abeille Timer topic .
*
*
*
*/

require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__)."/../../core/class/Abeille.class.php";
require_once dirname(__FILE__).("/lib/Tools.php");
include(dirname(__FILE__).'/includes/config.php');
include(dirname(__FILE__).'/includes/function.php');

function connect($r, $message) {
  log::add('AbeilleMQTTCmd', 'info', 'Mosquitto: Connexion à Mosquitto avec code ' . $r . ' ' . $message);
  // config::save('state', '1', 'Abeille');
}

function disconnect($r) {
  log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Déconnexion de Mosquitto avec code ' . $r);
  // config::save('state', '0', 'Abeille');
}

function subscribe() {
  log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Subscribe to topics');
}

function logmq($code, $str) {
  // if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
  log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Log level: ' . $code . ' Message: ' . $str);
  // }
}

function message($message) {
  global $AbeilleTimer;
  // var_dump( $message );
  $AbeilleTimer->procmsg( $message->topic, $message->payload );
}

class debug extends Tools {
  function deamonlog($loglevel = 'NONE', $message = "") {
    if ($this->debug["cli"] ) {
      echo $message."\n";
    }
    else {
      $this->deamonlogFilter($loglevel,'AbeilleMQTTCmd',$message);
    }
  }
}

class MosquittoAbeille extends debug {
  public $client;

  function __construct($client_id, $username, $password, $server, $port, $topicRoot, $qos, $debug) {
    if ($debug) $this->deamonlog("debug", "MosquittoAbeille constructor");

    // https://github.com/mgdm/Mosquitto-PHP
    // http://mosquitto-php.readthedocs.io/en/latest/client.html
    $this->client = new Mosquitto\Client($client_id);

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
    $this->client->onConnect('connect');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
    $this->client->onDisconnect('disconnect');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
    $this->client->onSubscribe('subscribe');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
    $this->client->onMessage('message');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onLog
    $this->client->onLog('logmq');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
    $this->client->setWill('/jeedom', "Client ".$client_id." died :-(", $qos, 0);

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
    $this->client->setReconnectDelay(1, 120, 1);

    $this->client->setCredentials( $username, $password );
    $this->client->connect( $server, $port, 60 );
    $this->client->publish( "/jeedom", "Client ".$client_id." is joining", $this->qos );
    $this->client->subscribe( $topicRoot, $qos ); // !auto: Subscribe to root topic

    if ($debug) $this->deamonlog( 'debug', 'Subscribed to topic: '.$topicRoot );
  }
}

class AbeilleTimer extends MosquittoAbeille {
  public $debug = array(  "cli"                 => 1, // commande line mode or jeedom
  "AbeilleTimerClass" => 1,
  "procmsg" => 1, );
  public $parameters_info;
  public $Timers = array();

  public $client;
  public $RefreshWidgetRate;
  public $lastWidgetUpdate;
  public $RefreshCmdRate;
  public $lastCmdUpdate;

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

    parent::__construct($client_id, $this->parameters_info["AbeilleUser"], $this->parameters_info["AbeillePass"], $this->parameters_info["AbeilleAddress"], $this->parameters_info["AbeillePort"], $this->parameters_info["AbeilleTopic"], $this->parameters_info["AbeilleQos"], $this->debug["AbeilleMQTTCmdClass"] );

  }

  function mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data ) {
    // Abeille / short addr / Cluster ID - Attr ID -> data

    $this->client->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId,    $data              );
    $this->client->publish("Abeille/".$SrcAddr."/Time-TimeStamp",                 time()             );
    $this->client->publish("Abeille/".$SrcAddr."/Time-Time",                      date("Y-m-d H:i:s"));

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

  function checkExparies() {
    global $Timers;
    global $mqtt;
    global $RefreshWidgetRate;
    global $lastWidgetUpdate;
    global $RefreshCmdRate;
    global $lastCmdUpdate;
    global $qos;

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
            $this->client->mqqtPublish( $address, "Var", "Duration", $Timer['T3']-time() );
            // print_r( $Timer ); echo "\n";
          }

          if ( (time()-$lastCmdUpdate) > $RefreshCmdRate ) {
            $now = time();
            if ( $now < $Timer['T0'] ) { $phase = '->T0'; $RampUpDown = 0; }
            if ( ($Timer['T0'] <= $now) && ($now<$Timer['T1']) ) { $phase = 'T0->T1'; $RampUpDown = ($now-$Timer['T0'])/($Timer['T1']-$Timer['T0'])*100; }
            if ( ($Timer['T1'] <= $now) && ($now<$Timer['T2']) ) { $phase = 'T1->T2'; $RampUpDown = 100; }
            if ( ($Timer['T2'] <= $now) && ($now<$Timer['T3']) ) { $phase = 'T2->T3'; $RampUpDown = 100-($now-$Timer['T2'])/($Timer['T3']-$Timer['T2'])*100; }
            if ( $Timer['T3'] < $now ) { $phase = 'T3->';$RampUpDown = 0; }

            $this->client->mqqtPublish( $address, "Var", "RampUpDown", $RampUpDown );

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

  function procmsg($topic, $msg) {
    if ( $this->debug['procmsg'] ) $this->deamonlog("debug", "----------");
    if ( $this->debug['procmsg'] ) $this->deamonlog("debug", "procmsg fct - topic: ". $topic . " len: " . strlen($this->parameters_info["AbeilleTopic"]) );
    if ( substr($topic, 0, strlen($this->parameters_info["AbeilleTopic"])-2) != substr($this->parameters_info["AbeilleTopic"],0, strlen($this->parameters_info["AbeilleTopic"])-2 ) ) {
      if ( $this->debug['procmsg'] ) $this->deamonlog("debug", "procmsg fct - Message receive but is not for me, wrong delivery !!!");
      return;
    }

    // On enleve AbeilleTopic
    $topic = substr( $topic, strlen($this->parameters_info["AbeilleTopic"])-1 );

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

    if ( $this->debug['procmsg'] ) $this->deamonlog('info', 'procmsg fct - ;Msg Received: Topic: {'.$topic.'} => '.$msg);

    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - Type: '.$type.' Address: '.$address.' avec Action: '.$action);

    // Crée les variables dans la chaine et associe la valeur.
    $parameters = proper_parse_str( $msg );
    if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - Parametres: '.json_encode($parameters));

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
        $this->client->mqqtPublish( $address, "0006", "0000", "1" );

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
        $this->Timers[$address] = $parameters;

        // On calcule les points/temps de passages
        $now = time();
        $Timers[$address]['T0'] = $now;
        $Timers[$address]['T1'] = $now + $parameters['RampUp'];
        $Timers[$address]['T2'] = $now + $parameters['RampUp'] + $parameters['durationSeconde'];
        $Timers[$address]['T3'] = $now + $parameters['RampUp'] + $parameters['durationSeconde'] + $parameters['RampDown'];
        $Timers[$address]['state'] = 'T0->T1';

        // print_r( $Timers );

        $this->client->mqqtPublish( $address, "Var", "ExpiryTime", date("Y-m-d H:i:s", $Timers[$address]['T3']) );
        $this->client->mqqtPublish( $address, "Var", "Duration", $Timers[$address]['T3']-time() );
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
      if ( $this->debug['procmsg'] ) $this->deamonlog('debug', 'procmsg fct - TimerCancel');
      // $keywords = preg_split("/[=&]+/", $msg);
      $this->client->mqqtPublish($address, "0006", "0000", "0");
      $this->client->mqqtPublish($address, "Var", "ExpiryTime", "-");
      $this->client->mqqtPublish($address, "Var", "Duration", "-");
      $Timers[$address] = -1;

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

      $this->client->mqqtPublish($address, "0006", "0000", "-");
      $this->client->mqqtPublish($address, "Var", "ExpiryTime", "-");
      $this->client->mqqtPublish($address, "Var", "Duration", "-");
      $this->client->mqqtPublish($address, "Var", "RampUpDown", "0");

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

}

// ***********************************************************************************************
// MAIN
// ***********************************************************************************************
//                      1          2           3       4          5       6
//$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;

try {
  $AbeilleTimer = new AbeilleTimer("AbeilleTimer");

  if ( $AbeilleTimer->debug['AbeilleTimerClass'] ) $AbeilleTimer->deamonlog("debug", json_encode( $AbeilleTimer ) );

  while (true) {
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
    $AbeilleTimer->client->loop(0);
    $AbeilleTimer->checkExparies();
    time_nanosleep( 0, 10000000 ); // 1/100s
  }

  $AbeilleTimer->Client->disconnect();

}
catch (Exception $e) {
  $AbeilleTimer->deamonlog('debug', 'error', $e->getMessage());
  $AbeilleTimer->deamonlog('info', 'Fin du script');
}


  unset($AbeilleTimer);

?>
