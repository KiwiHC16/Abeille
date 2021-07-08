<?php

    /* This code purpose is perform actions triggered by client:
        - Send a message to a specific queue
            action=sendMsg&queueId=XXX&...
        - Or perform a specific action
            action=reconfigure&eqId=XX
     */

    /* Tcharp38 note:
       This file should smoothly replace 'Network/TestSVG/xmlhttpMQTTSend.php'
       but also 'AbeilleFormAction.php' (more work there)
     */

    require_once __DIR__.'/../../core/config/Abeille.config.php'; // Queues
    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/AbeilleLog.php'; // logDebug()

    if (isset($_GET['action']))
        $action = $_GET['action'];
    else
        $action = "sendMsg";

    if ($action == "sendMsg") {
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

        return;
    } // End $action == "sendMsg"

    /* Request to reconfigure device.
       This is done by executing action cmds with 'execAtCreation' flag set. */
    if ($action == "reconfigure") {
        $eqId = $_GET['eqId'];
// logDebug("reconfigure: eqId=".$eqId);
        $eqLogic = eqLogic::byId($eqId);
        $jsonName = $eqLogic->getConfiguration('modeleJson', '');
        if ($jsonName == '')
            return; // ERROR
        $jsonLocation = "Abeille";
        $eqConfig = AbeilleTools::getDeviceConfig($jsonName, $jsonLocation);
        $mainEP = $eqLogic->getConfiguration('mainEP', '');
        $ieee = $eqLogic->getConfiguration('IEEE', '');
        list($eqNet, $eqAddr) = explode("/", $eqLogic->getLogicalId());
        $zgNb = substr($eqNet, 7); // AbeilleX => X
        $zigate = eqLogic::byLogicalId('Abeille'.$zgNb.'/0000', 'Abeille');
        $zgIeee = $zigate->getConfiguration('IEEE', '');

        $cmds = $eqConfig['commands'];
        foreach ($cmds as $cmdJName => $cmd) {
            $c = $cmd['configuration'];
            if (!isset($c['execAtCreation']))
                continue;

            $topic = 'Cmd'.$eqNet.'/'.$eqAddr.'/'.$c['topic'];
            $request = $c['request'];

            // TODO: #EP# defaulted to first EP but should be
            //       defined in cmd use if different target EP
            $request = str_replace('#EP#', $mainEP, $request);

            $request = str_replace('#addrIEEE#', $ieee, $request);
            $request = str_replace('#ZiGateIEEE#', $zgIeee, $request);

            $queue = msg_get_queue(queueKeyXmlToCmd);
            $msg = new MsgAbeille;
            $msg->message = array(
                                'topic' => $topic,
                                'payload' => $request,
                                );
// logDebug("msg=".json_encode($msg));
            msg_send($queue, 1, $msg, true, false);
        }
    } // End $action == "reconfigure"
?>
