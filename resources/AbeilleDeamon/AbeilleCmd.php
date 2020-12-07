<?php
    /*
     * démon AbeilleCmd
     *
     * subscribe to Abeille topic and receive message sent by AbeilleParser.
     *
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.php";
    if (file_exists($dbgFile)) {
        include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/includes/config.php';
    include_once __DIR__.'/includes/function.php';
    include_once __DIR__.'/includes/fifo.php';

    include_once __DIR__.'/../../core/class/AbeilleMsg.php';
    include_once __DIR__.'/../../core/php/AbeilleLog.php';

    include_once __DIR__.'/../../core/class/AbeilleCmdQueue.class.php';

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleCmd.php debug

    try {
        $AbeilleCmdQueue = new AbeilleCmdQueue($argv[1]);
        logMessage("info", "Démarrage du démon 'AbeilleCmd'");

        $last = 0;
        while (true) {
            /* Treat Zigate statuses (0x8000 cmd) coming from parser */
            $AbeilleCmdQueue->traiteLesAckRecus();

            $AbeilleCmdQueue->timeOutSurLesAck();

            // Traite toutes les commandes zigate en attente
            $AbeilleCmdQueue->processCmdQueueToZigate();

            $AbeilleCmdQueue->recupereTousLesMessagesVenantDesAutresThreads();

            // Recuperes tous les messages en attente sur timer
            $AbeilleCmdQueue->execTempoCmdAbeille();

            /* Display queues status every 30sec */
            if ((time() - $last) > 30 ) {
                $AbeilleCmdQueue->afficheStatQueue();
                $last = time();
            }

            // Libère le CPU
            time_nanosleep(0, 10000000); // 1/100s
        }
    }
    catch (Exception $e) {
        $AbeilleCmdQueue->deamonlog( 'debug', 'error: '. json_encode($e->getMessage()));
        $AbeilleCmdQueue->deamonlog( 'info', 'Fin du script');
    }

    $AbeilleCmdQueue->deamonlog( 'info', 'Fin du démon \'AbeilleCmd\'');
    unset($AbeilleCmdQueue);
?>
