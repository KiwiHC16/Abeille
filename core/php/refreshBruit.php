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

    function sendToCmd($queueId, $priority, $topic, $payload)
    {
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

        if (msg_send($queue, $priority, $msg, true, false, $errCode)) {
            // log::add('Abeille', 'debug', "refreshBruit():(): Envoyé '".json_encode($msg)."' vers queue ".$queueId);
        } else
            log::add('Abeille', 'warning', "refreshBruit(): Impossible d'envoyer '".json_encode($msg)."' vers 'xToCmd'");
    }

    $eqLogics = Abeille::byType('Abeille');
    $queueId = $abQueues["xToCmd"]['id'];

    foreach ($eqLogics as $eqLogic) {
        if ( ($device == $eqLogic->getLogicalId()) || ($device == "All") ) {
            list($dest, $address) = explode('/', $eqLogic->getLogicalId());
            if ( strlen($address) == 4 ) {
                sendToCmd($queueId, PRIO_NORM, "Cmd".$dest."/".$address."/managementNetworkUpdateRequest", "");
                sleep(5);
            }
        }
    }
?>
