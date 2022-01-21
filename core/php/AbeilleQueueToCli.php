<?php

    require_once __DIR__.'/../config/Abeille.config.php'; // Queues

    /* Developers mode */
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/AbeilleLog.php'; // logDebug()

    logDebug("AbeilleQueueToCli starting");

    $queueId = $abQueues["parserToCli"]['id'];
    $msgMax = $abQueues["parserToCli"]['max'];
    $queue = msg_get_queue($queueId);

    // while (msg_receive($queue, 0, $msgType, 256, $msgJson, false)) {
    //     logDebug("msg=".$msgJson);
    //     echo $msgJson;
    // };
    if (msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, 0, $errCode) == false) {
        // Note: err 7 => message size bigger than given size
        if ($errCode == 7) {
            msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, MSG_NOERROR);
            logDebug("QueueToCli: ERROR=MSG TOO BIG => IGNORED");
        } else
            logDebug("QueueToCli: ERROR=".$errCode." => ".posix_strerror($errCode));
    } else {
        logDebug("QueueToCli: msg=".$msgJson);
        echo $msgJson;
    }

    logDebug("AbeilleQueueToCli exiting");
?>
