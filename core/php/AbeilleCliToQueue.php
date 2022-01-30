<?php

    /* This code purpose is perform actions triggered by client:
        - Send a message to a specific queue
            action=sendMsg
            queueId=XXX
            msg=YYY
        - Or perform a specific action
            action=reconfigure&eqId=XX
     */

    /* Tcharp38 note:
       This file should smoothly replace 'Network/TestSVG/xmlhttpMQTTSend.php'
       but also 'AbeilleFormAction.php' (more work there)
     */

    require_once __DIR__.'/../config/Abeille.config.php'; // dbgFile + queues

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
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/AbeilleLog.php'; // logDebug()

    if (isset($_GET['action']))
        $action = $_GET['action'];
    else
        $action = "sendMsg";

    if (isset($dbgTcharp38)) logDebug("CliToQueue: action=".$action);

    if ($action == "sendMsg") {
        // Default target queue = '$abQueues['xmlToAbeille']['id']'
        if (isset($_GET['queueId']))
            $queueId = $_GET['queueId'];
        else
            $queueId = $abQueues['xmlToAbeille']['id'];
        $queue = msg_get_queue($queueId);
        if ($queue === false) {
            if (isset($dbgTcharp38)) logDebug("CliToQueue: ERROR: Invalid queue ID ".$queueId);
            return;
        }

        if (in_array($queueId, [$abQueues['xmlToAbeille']['id'], $abQueues['xToCmd']['id']])) {
            $topic =  $_GET['topic'];
            $topic = str_replace('_', '/', $topic);
            $payload = $_GET['payload'];
            $payload = str_replace('_', '&', $payload);
            if (isset($dbgTcharp38)) logDebug("CliToQueue: topic=".$topic.", payload=".$payload);

            $msg = array(
                'topic' => $topic,
                'payload' => $payload,
            );

            if (msg_send($queue, 1, $msg, true, false)) {
                echo "(fichier xmlhttpMQQTSend) added to queue: ".json_encode($msg);
                // print_r(msg_stat_queue($queue));
            } else {
                echo "debug","(fichier xmlhttpMQQTSend) could not add message to queue";
            }
        } else {
            if (!isset($_GET['msg'])) {
                if (isset($dbgTcharp38)) logDebug("CliToQueue: ERROR: 'msg' is undefined");
                return;
            }
            $msgString = $_GET['msg'];
            if (isset($dbgTcharp38)) logDebug("CliToQueue: msg=".$msgString);
            $msgArr = explode('_', $msgString);
            $m = array();
            foreach ($msgArr as $idx => $value) {
                // logDebug("CliToQueue LA: ".$value);
                $a = explode(':', $value);
                $m[$a[0]] = $a[1];
            }
            if (isset($dbgTcharp38)) logDebug("CliToQueue: ".json_encode($m));
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
        $jsonId = $eqLogic->getConfiguration('ab::jsonId', '');
        $jsonLocation = $eqLogic->getConfiguration('ab::jsonLocation', '');
        if ($jsonId == '') {
            if (isset($dbgTcharp38)) logDebug("CliToQueue: ERROR: jsonId empty");
            return; // ERROR
        }

        $sig = $eqLogic->getConfiguration('ab::signature');
        if ($sig) {
            $modelId = $sig['modelId'];
            $manufId = $sig['manufId'];
        } else {
            $modelId = "";
            $manufId = "";
        }
        if (($jsonId == "defaultUnknown") && ($modelId != "")) {
            $jsonId2 = "";
            $jsonLoc2 = "";
            $localP = __DIR__."/../../core/config/devices/";
            $abeilleP = __DIR__."/../../core/config/devices/";
            if (file_exists($localP.$modelId."_".$manufId."/".$modelId."_".$manufId.".json")) {
                $jsonId2 = $modelId."_".$manufId;
                $jsonLoc2 = "local";
            } else if (file_exists($abeilleP.$modelId."_".$manufId."/".$modelId."_".$manufId.".json")) {
                $jsonId2 = $modelId."_".$manufId;
                $jsonLoc2 = "Abeille";
            } else if (file_exists($localP.$modelId."/".$modelId.".json")) {
                $jsonId2 = $modelId;
                $jsonLoc2 = "local";
            } else if (file_exists($abeilleP.$modelId."/".$modelId.".json")) {
                $jsonId2 = $modelId;
                $jsonLoc2 = "Abeille";
            }
            if (($jsonId2 != $jsonId) || ($jsonLoc2 != $jsonLocation)) {
                if (isset($dbgTcharp38)) logDebug("CliToQueue: New jsonId: ".$jsonId2."/".$jsonLoc2);
                $eqLogic->setConfiguration('ab::jsonId', $jsonId2);
                $eqLogic->setConfiguration('ab::jsonLocation', $jsonLoc2);
                $eqLogic->save();
                $jsonId = $jsonId2;
                $jsonLocation = $jsonLoc2;
            }
        }

        $eqConfig = AbeilleTools::getDeviceConfig($jsonId, $jsonLocation);
        if ($eqConfig === false) {
            if (isset($dbgTcharp38)) logDebug("CliToQueue: ERROR: No device config");
            return; // ERROR
        }
        $mainEP = $eqLogic->getConfiguration('mainEP', '');
        $ieee = $eqLogic->getConfiguration('IEEE', '');
        list($eqNet, $eqAddr) = explode("/", $eqLogic->getLogicalId());
        $zgId = substr($eqNet, 7); // AbeilleX => X
        $zigate = eqLogic::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
        $zgIeee = $zigate->getConfiguration('IEEE', '');

        $cmds = $eqConfig['commands'];
        if (isset($dbgTcharp38)) logDebug("CliToQueue: cmds=".json_encode($cmds));
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

            $queue = msg_get_queue($abQueues['xToCmd']['id']);
            $msg = array(
                'topic' => $topic,
                'payload' => $request,
            );
// logDebug("msg=".json_encode($msg));
        if (isset($dbgTcharp38)) logDebug("CliToQueue: msg_send(): ".json_encode($msg));
        if (msg_send($queue, 1, $msg, true, false) == false) {
                if (isset($dbgTcharp38)) logDebug("CliToQueue: ERROR: msg_send()");
            }
        }

        if ($action == "reinit") {
            $queue = msg_get_queue($abQueues['xmlToAbeille']['id']);
            $msg = array(
                'topic' => "CmdCreate".$eqNet."/".$eqAddr."/resetFromJson",
                'payload' => '',
            );
            if (isset($dbgTcharp38)) logDebug("reinit msg to Abeille: ".json_encode($msg));
            msg_send($queue, 1, $msg, true, false);
        }
    } // End $action == "reconfigure"
?>
