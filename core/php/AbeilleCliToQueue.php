<?php

    /* This code purpose is to send from client page a message to Abeille or AbeilleCmd
       thru corresponding queue */

    /* Tcharp38 note:
       This file should smoothly replace 'Network/TestSVG/xmlhttpMQTTSend.php'
       but also 'AbeilleFormAction.php' (more work there)
     */

    require_once __DIR__.'/../../core/config/Abeille.config.php'; // Queues
    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/AbeilleLog.php'; // logDebug()

    // Default target queue = 'queueKeyXmlToAbeille'
    if (isset($_GET['queueId']))
        $queueId = $_GET['queueId'];
    else
        $queueId = queueKeyXmlToAbeille;
    $queue = msg_get_queue($queueId);
    // $queueKeyXmlToCmd           = msg_get_queue(queueKeyXmlToCmd);

    if (($queueId == queueKeyXmlToAbeille) || ($queueId == queueKeyXmlToCmd)) {
        $topic =  $_GET['topic'];
        $topic = str_replace('_', '/', $topic);
        $payload = $_GET['payload'];
        $payload = str_replace('_', '&', $payload);
        logDebug("CliToQueue: topic=".$topic.", payload=".$payload);

        Class MsgAbeille {
            public $message = array(
                                    'topic' => 'Coucou',
                                    'payload' => 'me voici',
                                    );
        }

        $msgAbeille = new MsgAbeille;
        $msgAbeille->message = array(
                                     'topic' => $topic,
                                     'payload' => $payload,
                                     );

        if (msg_send($queue, 1, $msgAbeille, true, false)) {
            echo "(fichier xmlhttpMQQTSend) added to queue: ".json_encode($msgAbeille);
            // print_r(msg_stat_queue($queue));
        } else {
            echo "debug","(fichier xmlhttpMQQTSend) could not add message to queue";
        }
    } else {
        $msgString = $_GET['msg'];
        logDebug("CliToQueue: ".$msgString);
        $msgArr = explode('_', $msgString);
        $m = array();
        foreach ($msgArr as $idx => $value) {
            // logDebug("CliToQueue LA: ".$value);
            $a = explode(':', $value);
            $m[$a[0]] = $a[1];
        }
        logDebug("CliToQueue: ".json_encode($m));
        msg_send($queue, 1, json_encode($m), false, false);
    }
?>
