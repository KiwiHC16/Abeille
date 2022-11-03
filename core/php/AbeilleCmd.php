<?php
    /*
     * AbeilleCmd daemon (send messages to zigate)
     *
     * - collect messages
     * - format them properly, and send them to Zigate
     * - check cmd ACK (8000/8012/8702 zigate answers)
     */

    include_once __DIR__.'/../config/Abeille.config.php';

    /* Developers options */
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/AbeilleLog.php';
    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../class/AbeilleCmdQueue.class.php';
    include_once __DIR__.'/AbeilleOTA.php';
    include_once __DIR__.'/AbeilleCmd-Tuya.php';

    logSetConf("AbeilleCmd.log", true);
    logMessage('info', '>>> Démarrage d\'AbeilleCmd');

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleCmd.php debug
    //check already running

    $abeilleConfig = AbeilleTools::getParameters();
    $running = AbeilleTools::getRunningDaemons();
    $daemons = AbeilleTools::diffExpectedRunningDaemons($abeilleConfig, $running);
    logMessage('debug', 'Daemons status: '.json_encode($daemons));
    if ($daemons["cmd"] > 1){
        logMessage('error', 'Le démon est déja lancé ! '.json_encode($daemons));
        exit(3);
    }

    declare(ticks = 1);
    pcntl_signal(SIGTERM, 'signalHandler', false);
    function signalHandler($signal) {
        logMessage('info', '<<< Arret du démon AbeilleCmd');
        exit;
    }

    /* Any device to monitor ?
       It is indicated by 'ab::monitorId' key in Jeedom 'config' table. */
    $monId = $abeilleConfig['ab::monitorId'];
    if ($monId !== false) {
        $eqLogic = eqLogic::byId($monId);
        if (!is_object($eqLogic)) {
            logMessage('debug', 'Bad ID to monitor: '.$monId);
        } else {
            list($net, $addr) = explode( "/", $eqLogic->getLogicalId());
            $ieee = $eqLogic->getConfiguration('IEEE', '');
            $dbgMonitorAddr = $addr;
            $dbgMonitorAddrExt = $ieee;
            logMessage("debug", "Device to monitor: ".$eqLogic->getHumanName().', '.$addr.'-'.$ieee);
            include_once __DIR__.'/AbeilleMonitor.php'; // Tracing monitor for debug purposes
        }
    }

    // Reading available OTA firmwares
    otaReadFirmwares();

    // $queueCtrlToCmd = msg_get_queue($abQueues["ctrlToCmd"]["id"]);
    // $queueCtrlToCmdMax = $abQueues["ctrlToCmd"]["max"];

    function cmdLog($loglevel = 'NONE', $message = "", $isEnable = 1) {
        if ($isEnable == 0)
            return;
        logMessage($loglevel, $message);
    }

    try {
        $AbeilleCmdQueue = new AbeilleCmdQueue($argv[1]);

        // Display useful infos before starting
        $AbeilleCmdQueue->displayStatus();
        $last = time();

        // $fromAssistQueue = msg_get_queue(queueKeyAssistToCmd);
        // $rerouteNet = ""; // Rerouted network if defined (ex: 'Abeille1')

        while (true) {
            // Treat Zigate statuses (0x8000 cmd) coming from parser
            $AbeilleCmdQueue->processAcks();

            // Treat pending commands for zigate
            $AbeilleCmdQueue->processCmdQueues();

            // Check zigate ACK timeout
            $AbeilleCmdQueue->zigateAckCheck();

            /* Checking if there is any control message for Cmd */
            // if (msg_receive($queueCtrlToCmd, 0, $msgType, $queueCtrlToCmdMax, $jsonMsg, false, MSG_IPC_NOWAIT, $errorCode) == true) {
            //     logMessage('debug', "queueCtrlToCmd=".$jsonMsg);
            //     $msg = json_decode($jsonMsg, true);
            //     if ($msg['type'] == 'readOtaFirmwares') {
            //         otaReadFirmwares(); // Reread available firmwares
            //     }
            // } else if ($errorCode != 42) { // 42 = No message
            //     logMessage('debug', '  msg_receive(queueCtrlToCmd) ERROR '.$errorCode);
            // }

            // Check 'xToCmd' queue
            $AbeilleCmdQueue->collectAllOtherMessages();

            // Check tempo queue
            // TODO: This should be a separate thread to not disturb cmd to Zigate process.
            $AbeilleCmdQueue->execTempoCmdAbeille();

            /* Display queues status every 30sec */
            if ((time() - $last) > 30) {
                $AbeilleCmdQueue->displayStatus();
                $last = time();
            }

            // Libère le CPU
            time_nanosleep(0, 10000000); // 1/100s
        }
    }
    catch (Exception $e) {
        // Tcharp38: Can we reach this ?
        logMessage('debug', 'error: '. json_encode($e->getMessage()));
    }

    // Tcharp38: Can we reach this ?
    unset($AbeilleCmdQueue);
    logMessage('info', '<<< Fin du démon \'AbeilleCmd\'');
?>
