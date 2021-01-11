<?php

    /*
     * Targets for AJAX's requests related to exchanges with zigate
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.php";
    if (file_exists($dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect()) {
        ajax::error(json_encode(array('status' => -1, 'error' => "Non connecté ou session expirée.")));
    }

    /* Log function for debug purposes */
    function logToFile($msg = "")
    {
        $logFile = "AbeilleDebug.log";
        $logDir = __DIR__.'/../../../../log/';
        file_put_contents($logDir.$logFile, '['.date('Y-m-d H:i:s').'] '.$msg."\n", FILE_APPEND);
    }

    try {
        ajax::init();

        require_once __DIR__.'/../class/AbeilleMsg.php';
        require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';

        /* Send a order to zigate thru 'AbeilleCmd'.
           Returns: 0=OK, -1=ERROR */
        if (init('action') == 'sendMsgToCmd') {
            $topic = init('topic');
            $payload = init('payload');
            // logToFile("action=sendMsgToCmd: topic='".$topic."', payload='".$payload."'");

            $status = 0;
            $error = "";

            $queueKeyFormToCmd = msg_get_queue(queueKeyFormToCmd);
            $msgAbeille = new MsgAbeille;
            $msgAbeille->message['topic']   = $topic;
            $msgAbeille->message['payload'] = $payload;

            if (msg_send($queueKeyFormToCmd, 1, $msgAbeille, true, false) == FALSE) {
                $error = "Could not send msg to 'queueKeyFormToCmd': msg=".json_encode($msgAbeille);
                $status = -1;
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
           Instead 'success' callback is used. This means that
           - take care of error code returned
           - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'AbeilleZigate.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
