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

    /* Developers mode & PHP errors */
    if (file_exists(dbgFile)) {
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["defines"])) {
            $arr = $dbgConfig["defines"];
            foreach ($arr as $idx => $value) {
                if ($value == "Tcharp38")
                    $dbgTcharp38 = true;
            }
        }
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/AbeilleLog.php'; // logDebug()

    if (isset($_GET['action']))
        $action = $_GET['action'];
    else
        $action = "sendMsg";

    if ($dbgTcharp38) logDebug("CliToQueue: action=".$action);

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
            if ($dbgTcharp38) logDebug("CliToQueue: topic=".$topic.", payload=".$payload);

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
            if ($dbgTcharp38) logDebug("CliToQueue: ".$msgString);
            $msgArr = explode('_', $msgString);
            $m = array();
            foreach ($msgArr as $idx => $value) {
                // logDebug("CliToQueue LA: ".$value);
                $a = explode(':', $value);
                $m[$a[0]] = $a[1];
            }
            if ($dbgTcharp38) logDebug("CliToQueue: ".json_encode($m));
            msg_send($queue, 1, json_encode($m), false, false);
        }

        return;
    } // End $action == "sendMsg"

    /* Request to reconfigure device.
       This is done by executing action cmds with 'execAtCreation' flag set. */
    /* Tcharp38: TODO: Better to put 'reconfigure' functionality inside AbeilleCmd and remove it
       from here & parser too. */
    if (($action == "reconfigure") || ($action == "reinit")) {
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
        $zgId = substr($eqNet, 7); // AbeilleX => X
        $zigate = eqLogic::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
        $zgIeee = $zigate->getConfiguration('IEEE', '');

        $cmds = $eqConfig['commands'];
        foreach ($cmds as $cmdJName => $cmd) {
            if (!isset($cmd['configuration']))
                continue;
            $c = $cmd['configuration'];
            if (!isset($c['execAtCreation']))
                continue;

            $topic = 'Cmd'.$eqNet.'/'.$eqAddr.'/'.$c['topic'];
            $request = $c['request'];

            // TODO: #EP# defaulted to first EP but should be
            //       defined in cmd use if different target EP
            $request = str_ireplace('#ep#', $mainEP, $request); // Case insensitive

            $request = str_ireplace('#ieee#', $ieee, $request); // Case insensitive
            $request = str_ireplace('#addrIeee#', $ieee, $request); // Case insensitive

            $request = str_ireplace('#zigateIeee#', $zgIeee, $request); // Case insensitive

            $queue = msg_get_queue(queueKeyXmlToCmd);
            $msg = new MsgAbeille;
            $msg->message = array(
                                'topic' => $topic,
                                'payload' => $request,
                                );
// logDebug("msg=".json_encode($msg));
            msg_send($queue, 1, $msg, true, false);
        }

        if ($action == "reinit") {
            $queue = msg_get_queue(queueKeyXmlToAbeille);
            $msg = new MsgAbeille;
            $msg->message = array(
                                'topic' => "CmdCreate".$eqNet."/".$eqAddr."/resetFromJson",
                                'payload' => '',
                                );
            if ($dbgTcharp38) logDebug("reinit msg to Abeille: ".json_encode($msg));
            msg_send($queue, 1, $msg, true, false);
        }
    } // End $action == "reconfigure"
?>
