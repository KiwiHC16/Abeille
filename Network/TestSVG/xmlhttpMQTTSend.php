<?php

    require_once __DIR__.'/../../core/config/Abeille.config.php'; // Queues
    require_once __DIR__.'/../../../../core/php/core.inc.php';

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
