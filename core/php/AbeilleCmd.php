<?php
    /*
     * AbeilleCmd daemon
     *
     * subscribe to Abeille topic and receive message sent by AbeilleParser.
     *
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        $dbgConfig = json_decode(file_get_contents($dbgFile), true);
        if (isset($dbgConfig["dbgMonitorAddr"])) { // Monitor params
            $dbgMonitorAddr = $dbgConfig["dbgMonitorAddr"];
        }
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../config/Abeille.config.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/function.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/fifo.php';
    include_once __DIR__.'/../class/AbeilleMsg.php';
    include_once __DIR__.'/AbeilleLog.php';
    include_once __DIR__.'/../class/AbeilleCmdQueue.class.php';

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleCmd.php debug
    //check already running
    logSetConf("AbeilleCmd.log", true);
    logMessage('info', '>>> AbeilleCmd starting');
    $parameters = AbeilleTools::getParameters();
    $running = AbeilleTools::getRunningDaemons();
    $daemons= AbeilleTools::diffExpectedRunningDaemons($parameters,$running);
    logMessage('debug', 'Daemons status: '.json_encode($daemons));
    if ($daemons["cmd"] > 1){
        logMessage('error', 'Le démon est déja lancé ! '.json_encode($daemons));
        exit(3);
    }

    try {
        $AbeilleCmdQueue = new AbeilleCmdQueue($argv[1]);

        $last = 0;
        $fromAssistQueue = msg_get_queue(queueKeyAssistToCmd);
        $rerouteNet = ""; // Rerouted network if defined (ex: 'Abeille1')
        while (true) {
            /* Treat Zigate statuses (0x8000 cmd) coming from parser */
            $AbeilleCmdQueue->traiteLesAckRecus();

            $AbeilleCmdQueue->timeOutSurLesAck();

            // Traite toutes les commandes zigate en attente
            $AbeilleCmdQueue->processCmdQueueToZigate();

            /* Performing msg rerouting from 'EQ assistant' */
            $max_msg_size = 2048;
            $msg_type = NULL;
            if (msg_receive($fromAssistQueue, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT) == true) {
                logMessage('debug', "Received=".json_encode($msg));
                if ($msg['type'] == 'reroute') {
                    $rerouteNet = $msg['network'];
                    /* TODO: Tcharp38: Can be optimized */
                    $zgNb = str_replace('Abeille', '', $rerouteNet);
                    $AbeilleCmdQueue->zigateAvailable[$zgNb] = 0;
                    logMessage('debug', "'".$rerouteNet."' messages must be rerouted");
                } else if ($msg['type'] == 'reroutestop') {
                    logMessage('debug', "Stopping '".$rerouteNet."' msg rerouting.");
                    $rerouteNet = "";
                    /* TODO: Tcharp38: Can be optimized */
                    $zgNb = str_replace('Abeille', '', $rerouteNet);
                    $AbeilleCmdQueue->zigateAvailable[$zgNb] = 1;
                } else if ($msg['type'] == 'msg') {
                    logMessage('debug', $rerouteNet.", rerouting: ".$msg['msg']);
                    /* TODO: Tcharp38: Can be optimized */
                    $zgNb = str_replace('Abeille', '', $rerouteNet);
                    if (config::byKey('AbeilleActiver'.$zgNb, 'Abeille', 'N') == 'Y' ) {
                        $sp = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '1', 1);
                        $f = fopen($sp, "w");
                        fwrite($f, pack("H*", $msg['msg']));
                        fclose($f);
                    }
                }
            }

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
        logMessage('debug', 'error: '. json_encode($e->getMessage()));
        logMessage('info', '<<< Fin du démon \'AbeilleCmd\'');
    }

    unset($AbeilleCmdQueue);
    logMessage('info', '<<< Fin du démon \'AbeilleCmd\'');
?>
