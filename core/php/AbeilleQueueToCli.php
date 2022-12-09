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

    logDebug(">>> AbeilleQueueToCli starting");

    $queueId = $abQueues["parserToCli"]['id'];
    $msgMax = $abQueues["parserToCli"]['max'];
    $queue = msg_get_queue($queueId);

    // Reading queue and concatening messages until there are no more
    $flags = 0; // We block only on first message
    $messages = "{";
    $msgIdx = 0;
    while (true) {
        if (msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, $flags, $errCode) == false) {
            // Note: err 7 => message size bigger than given size
            if ($errCode == 7) {
                msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, MSG_NOERROR);
                logDebug("QueueToCli: ERROR=MSG TOO BIG => IGNORED");
                continue;
            } else if ($errCode == 42)
                break; // No more messages

            logDebug("QueueToCli: ERROR=".$errCode." => ".posix_strerror($errCode));
            break;
        }

        logDebug("QueueToCli: msg=".$msgJson);
        // echo $msgJson;
        if ($msgIdx != 0)
            $messages .= ",";
        $messages .= '"'.$msgIdx.'": '.$msgJson;
        // logDebug("QueueToCli: LA=".$messages);
        $msgIdx++;
        $flags = MSG_IPC_NOWAIT;

        // break;
    }
    $messages .= "}";

    if ($messages != "{}") {
        // logDebug("QueueToCli: messages=".$messages);
        echo $messages;
    }

    logDebug("<<< AbeilleQueueToCli exiting");
?>
