<?php

    require_once __DIR__.'/../config/Abeille.config.php'; // Queues

    /* Developpers mode */
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/AbeilleLog.php'; // logDebug()

    logDebug("AbeilleQueueToCli starting");

    $queueId = queueKeyParserToCli;
    $queue = msg_get_queue($queueId);

    // while (msg_receive($queue, 0, $msgType, 256, $jsonMsg, false)) {
    //     logDebug("msg=".$jsonMsg);
    //     echo $jsonMsg;
    // };
    if (msg_receive($queue, 0, $msgType, 1024, $jsonMsg, false, 0, $errCode) == false) {
        logDebug("AbeilleQueueToCli: ERROR=".$errCode." => ".posix_strerror($errCode));
        // Note: err 7 => message size bigger than given size
    } else {
        logDebug("AbeilleQueueToCli: msg=".$jsonMsg);
        echo $jsonMsg;
    }

    logDebug("AbeilleQueueToCli exiting");
?>
