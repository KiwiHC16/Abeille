<?php

    /*
     * Targets for AJAX's requests related to exchanges with zigate
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.json";
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

        require_once __DIR__.'/../config/Abeille.config.php';

        /* Send a order to zigate thru 'AbeilleCmd'.
           Returns: 0=OK, -1=ERROR */
        if (init('action') == 'sendMsgToCmd') {
            $topic = init('topic');
            $payload = init('payload');
            // logToFile("action=sendMsgToCmd: topic='".$topic."', payload='".$payload."'");

            $status = 0;
            $error = "";

            $msg = array();
            $msg['topic']   = $topic;
            $msg['payload'] = $payload;

            $queue = msg_get_queue($abQueues['xToCmd']['id']);
            if (msg_send($queue, 1, $msg, true, false) == false) {
                $error = "Could not send msg to 'xToCmd': msg=".json_encode($msg);
                $status = -1;
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Send a message to parser, from EQ assistant.
           TODO: There is probably a way to create a generic function.
           Returns: 0=OK, -1=ERROR */
        if (init('action') == 'sendMsgToParser') {
            $type = init('type');
            $network = init('network');
            logToFile("action=sendMsgToParser: type='".$type."', network='".$network."'");

            $status = 0;
            $error = "";

            $queue = msg_get_queue($abQueues['assistToParser']['id']);
            $msg = Array(
                "type" => $type,
                "network" => $network
            );

            if (msg_send($queue, 1, $msg, true, false) == false) {
                $error = "Could not send msg to 'assistToParser': msg=".json_encode($msg);
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
