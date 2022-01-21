<?php

    require_once __DIR__.'/../../core/config/Abeille.config.php'; // Queues
    require_once __DIR__.'/../../../../core/php/core.inc.php';

    echo "Begin - ";
    echo 'debug: Envoi du message ->'.$_GET['payload'].'<- vers ->'.$_GET['topic'].'<-';

    $queueKeyXmlToAbeille       = msg_get_queue(queueKeyXmlToAbeille);
    // $queueKeyXmlToCmd           = msg_get_queue(queueKeyXmlToCmd);
    $queueKeyXmlToCmd       = msg_get_queue($abQueues['xToCmd']['id']);
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

    $msg = array(
        'topic' => str_replace('_','/',$_GET['topic']),
        'payload' => $_GET['payload'],
    );

    if (msg_send( $queueKeyXmlToAbeille, 1, $msg, true, false)) {
        echo "(fichier xmlhttpMQQTSend) added to queue: ".json_encode($msg);
        // print_r(msg_stat_queue($queue));
    }
    else {
        echo "debug","(fichier xmlhttpMQQTSend) could not add message to queue";
    }

    if (msg_send($queueKeyXmlToCmd, 1, $msg, true, false)) {
        echo "(fichier xmlhttpMQQTSend) added to queue: ".json_encode($msg);
        // print_r(msg_stat_queue($queue));
    }
    else {
        echo "debug","(fichier xmlhttpMQQTSend) could not add message to queue";
    }

    echo "Ending - ";
?>
