<?php

    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

    // Il faut plusieures queues entre les process, on ne peut pas avoir un pot pourri pour tous comme avec Mosquitto.
    // 1: Abeille
    // 2: AbeilleParser -> Parser
    // 3: AbeilleMQTTCmd -> Cmd
    // 4: AbeilleTimer  -> Timer
    // 5: AbeilleLQI -> LQI
    // 6: xmlhttpMQTTSend -> xml

    // 221: means AbeilleParser to(2) Abeille
    define('queueKeyAbeilleToAbeille',  121);
    define('queueKeyAbeilleToCmd',      123);
    define('queueKeyAbeilleToTimer',    124);
    define('queueKeyParserToAbeille',   221);
    define('queueKeyParserToCmd',       223);
    define('queueKeyParserToLQI',       225);
    define('queueKeyCmdToAbeille',      321);
    define('queueKeyCmdToCmd',          323);
    define('queueKeyTimerToAbeille',    421);
    define('queueKeyLQIToAbeille',      521);
    define('queueKeyLQIToCmd',          523);
    define('queueKeyXmlToAbeille',      621);
    define('queueKeyXmlToCmd',          623);

    Class MsgAbeille {
        public $message = array(
                                'topic' => 'Coucou',
                                'payload' => 'me voici',
                                );
    }

    echo "Begin - ";
    echo 'debug: Envoi du message ->'.$_GET['payload'].'<- vers ->'.$_GET['topic'].'<-';

    $queueKeyXmlToAbeille       = msg_get_queue(queueKeyXmlToAbeille);
    $queueKeyXmlToCmd           = msg_get_queue(queueKeyXmlToCmd);
    /*
    $parameters_info = Abeille::getParameters();

    $publish = new Mosquitto\Client();

    $publish->setCredentials( $parameters_info['AbeilleUser'], $parameters_info['AbeillePass'] );

    $publish->connect( $parameters_info['AbeilleAddress'], $parameters_info['AbeillePort'], 60 );

    $publish->publish( substr($parameters_info["AbeilleTopic"],0,-1).str_replace('_','/',$_GET['topic']), $_GET['payload'], $parameters_info["AbeilleQos"], 0);
    for ($i = 0; $i < 100; $i++) {
        // Loop around to permit the library to do its work
        $publish->loop(1);
    }
    $publish->disconnect();
    unset($publish);
*/

    $msgAbeille = new MsgAbeille;

    $msgAbeille->message = array(
                                 'topic' => str_replace('_','/',$_GET['topic']),
                                 'payload' => $_GET['payload'],
                                 );

    //topic=CmdAbeille_Ruche_SetPermit&payload=Inclusion
    /*
    $msgAbeille->message = array(
                                 'topic' => 'CmdAbeille/0000/SetPermit',
                                 'payload' => 'Inclusion',
                                 );
    */


    if (msg_send( $queueKeyXmlToAbeille, 1, $msgAbeille, true, false)) {
        echo "(fichier xmlhttpMQQTSend) added to queue: ".json_encode($msgAbeille);
        // print_r(msg_stat_queue($queue));
    }
    else {
        echo "debug","(fichier xmlhttpMQQTSend) could not add message to queue";
    }

    if (msg_send( $queueKeyXmlToCmd, 1, $msgAbeille, true, false)) {
        echo "(fichier xmlhttpMQQTSend) added to queue: ".json_encode($msgAbeille);
        // print_r(msg_stat_queue($queue));
    }
    else {
        echo "debug","(fichier xmlhttpMQQTSend) could not add message to queue";
    }

    echo "Ending - ";

    ?>
