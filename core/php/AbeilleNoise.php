<?php

    /***
     *
     *
     */

    include_once("../config/Abeille.config.php");
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__."/../../../../core/php/core.inc.php";
    // include_once("../class/AbeilleTools.class.php");

    if (!isset($_GET['device']))
        exit;
    $device = $_GET['device'];

    function sendToCmd($queueId, $priority, $topic, $payload) {
        $queue = msg_get_queue($queueId);
        if ($queue == false) {
            // log::add('Abeille', 'error', "publishMosquitto(): La queue ".$queueId." n'existe pas. Message ignoré.");
            return;
        }
        if (($stat = msg_stat_queue($queue)) == false) {
            return; // Something wrong
        }

        $msg = array();
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg);

        if (msg_send($queue, $priority, $msgJson, false, false, $errCode)) {
            // log::add('Abeille', 'debug', "refreshNoise():(): Envoyé '".$msgJson."' vers queue ".$queueId);
        } else
            log::add('Abeille', 'warning', "refreshNoise(): Impossible d'envoyer '".$msgJson."' vers 'xToCmd'");
    }

    $eqLogics = Abeille::byType('Abeille');
    $queueId = $abQueues["xToCmd"]['id'];

    if ($device == "All") {
        foreach ($eqLogics as $eqLogic) {
            list($net, $addr) = explode('/', $eqLogic->getLogicalId());
            log::add('Abeille', 'debug', "refreshNoise(): '${net}/${addr}'");
            sendToCmd($queueId, PRIO_NORM, "Cmd".$net."/".$addr."/mgmtNetworkUpdateReq", "");
        }
        sleep(5);
    } else {
        $eqLogic = eqLogic::byLogicalId($device, 'Abeille');
        if (!is_object($eqLogic)) {
            log::add('Abeille', 'error', "refreshNoise(): Invalid device '${device}'");
            exit(2);
        }

        list($net, $addr) = explode('/', $eqLogic->getLogicalId());
        log::add('Abeille', 'debug', "refreshNoise(): '${device}'");
        sendToCmd($queueId, PRIO_NORM, "Cmd".$net."/".$addr."/mgmtNetworkUpdateReq", "");
        sleep(5);
    }
?>
