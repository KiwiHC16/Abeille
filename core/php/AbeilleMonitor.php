<?php

    /*
     * AbeilleMonitor
     * This is a tracing daemon designed to track messages sent to and received
     * from a specific device, in order to track what happened, specifically
     * when at some point the device appears to be in time out.
     *
     * The system is based on a standalone daemon 'AbeilleMonitor'
     * - that collect both messages coming from AbeilleParser and AbeilleCmd
     * - check if short addr is the one to be monitored
     * - if matches, store messages in log file, in timestamp order
     */

    include_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers debug features */
    // define("dbgFile", __DIR__."/../../tmp/debug.json");
    if (file_exists(dbgFile)) {
        $dbgConfig = json_decode(file_get_contents(dbgFile), TRUE);
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/AbeilleLog.php';

    /* Called from AbeilleParser to add a message to monitor.
       'addr' is either short address if 2 bytes, or IEEE address if 8 bytes.
       'logSetConf()' must be called first from parser. */
    function monMsgFromZigate($msgDecoded)
    {
        // logMessage("debug", "monMsgFromZigate('".$addr."', '".$msgDecoded."')");

        /* Check queue */
        if (!msg_queue_exists(queueKeyParserToMon))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'x2mon', 'msg' => $msgDecoded);

        /* Write to fifo */
        $queue = msg_get_queue(queueKeyParserToMon);
        msg_send($queue, 1, $msg);
    }

    /* Called from AbeilleParser when short address has changed (device announce) */
    function monAddrHasChanged($addr, $ieee)
    {
        /* Check queue */
        if (!msg_queue_exists(queueKeyParserToMon))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'newaddr', 'addr' => $addr, 'ieee' => $ieee);

        /* Write to fifo */
        $queue = msg_get_queue(queueKeyParserToMon);
        msg_send($queue, 1, $msg);
    }

    /* Called from AbeilleCmd to add a message to monitor.
       'logSetConf()' must be called first from 'AbeilleCmd'. */
    function monMsgToZigate($addr, $msgDecoded)
    {
        // logMessage("debug", "monMsgToZigate('".$addr."', '".$msgDecoded."')");

        /* $addr currently unused. Filtered at upper level */
        // $shortAddr = strtolower($shortAddr); // Convert lower case
        // global $dbgMonitorAddr;
        // if ($shortAddr != strtolower($dbgMonitorAddr))
            // return;

        /* Check queue */
        if (!msg_queue_exists(queueKeyCmdToMon))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'x2mon', 'msg' => $msgDecoded);

        /* Write to fifo */
        $queue = msg_get_queue(queueKeyCmdToMon);
        msg_send($queue, 1, $msg);
    }

    /* Called to inform 'AbeilleCmd' to change monitoring address. */
    function monMsgToCmd($addr)  {
        /* Check queue */
        if (!msg_queue_exists(queueKeyMonToCmd))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'newaddr', 'addr' => $addr);

        /* Write to fifo */
        $queue = msg_get_queue(queueKeyMonToCmd);
        msg_send($queue, 1, $msg);
    }

    /* Shutdown function */
    function monShutdown($sig, $sigInfos) {
        pcntl_signal($sig, SIG_IGN);
        logMessage("info", "<<< Arret du démon de monitoring");
        exit(0);
    }

    /* Called on SIGUSR1 signal to reload dev config file. */
    // function monReload($sig, $sigInfos) {
    //     // pcntl_signal($sig, SIG_IGN);
    //     if (file_exists(dbgFile)) {
    //         logMessage("debug", "Relecture de la config developpeur.");
    //         $dbgConfig = json_decode(file_get_contents(dbgFile), TRUE);
    //         if (isset($dbgConfig["dbgMonitorAddr"])) {
    //             logMessage("info", "Adresse à surveiller: ".$dbgConfig["dbgMonitorAddr"]);
    //         } else
    //             logMessage("info", "Aucune adresse à surveiller pour l'instant.");
    //     } else
    //         logMessage("debug", "Pas de config dev a recharger.");
    // }

    /* Run AbeilleMonitor daemon.
       Address to monitor is currently extracted from developper config file. */
    function monRun()
    {
        /* Configuring 'AbeilleLog' library */
        logSetConf("AbeilleMonitor.log", TRUE);
        logMessage("info", ">>> Démarrage du démon de monitoring");

        declare(ticks = 1);
        if (pcntl_signal(SIGTERM, "monShutdown", FALSE) != TRUE)
            logMessage("error", "Erreur pcntl_signal()");
        // if (pcntl_signal(SIGUSR1, "monReload", FALSE) != TRUE)
        //     logMessage("error", "Erreur pcntl_signal()");

        $dbgConfig = $GLOBALS["dbgConfig"];
        if (isset($dbgConfig["dbgMonitorAddr"])) {
            logMessage("info", "Adresse à surveiller: ".$dbgConfig["dbgMonitorAddr"]);
        } else
            logMessage("info", "Aucune adresse à surveiller pour l'instant.");

        /* Main
           Create queues and check them regularly
         */
        try {
            $queueCmdToMon = msg_get_queue(queueKeyCmdToMon); // Messages sent to monitored device
            $queueParserToMon = msg_get_queue(queueKeyParserToMon); // Messages received from monitored device
            if (!is_resource($queueCmdToMon) || !is_resource($queueParserToMon)) {
                logMessage("error", "ERR: Problème de création des queues. Arret du démon.");
                return;
            }

            $max_msg_size = 512;

            while (true) {
                /* Check if one of the queues is not empty */
                /* Select oldest message then log it and remove it from queue */

                $statCmdQueue = msg_stat_queue($queueCmdToMon);
                $statParserQueue = msg_stat_queue($queueParserToMon);
                if (($statCmdQueue['msg_qnum'] == 0) && ($statParserQueue['msg_qnum'] == 0)) {
                    time_nanosleep( 0, 10000000 ); // 1/100s
                    continue;
                }

                $msgType = NULL;
                $msg = NULL;
                $msgSent = TRUE; // Message direction: assuming sent
                if (($statCmdQueue['msg_qnum'] != 0) && ($statParserQueue['msg_qnum'] != 0)) {
                    /* Select queue with oldest message */
                    if ($statCmdQueue['msg_stime'] < $statParserQueue['msg_stime'])
                        msg_receive($queueCmdToMon, 0, $msgType, $max_msg_size, $msg, TRUE, MSG_IPC_NOWAIT);
                    else {
                        msg_receive($queueParserToMon, 0, $msgType, $max_msg_size, $msg, TRUE, MSG_IPC_NOWAIT);
                        $msgSent = FALSE;
                    }
                } else if ($statCmdQueue['msg_qnum'] != 0)
                    msg_receive($queueCmdToMon, 0, $msgType, $max_msg_size, $msg, TRUE, MSG_IPC_NOWAIT);
                else {
                    msg_receive($queueParserToMon, 0, $msgType, $max_msg_size, $msg, TRUE, MSG_IPC_NOWAIT);
                    $msgSent = FALSE;
                }

                if ($msg['type'] == 'x2mon') {
                    /* Log message */
                    if ($msgSent == TRUE)
                        logMessage("debug", "=> ".$msg['msg']);
                    else
                        logMessage("debug", "<= ".$msg['msg']);
                } else {
                    /* Should be 'newaddr' msg type */
                    logMessage("debug", "<= Nouvelle adresse courte pour ".$msg['ieee'].": ".$msg['addr']);

                    /* Informing 'AbeilleCmd' that addr has changed.
                       Note: 2 ways to inform AbeilleCmd, either thru a dedicated queue (current case) or restarting it */
                    monMsgToCmd($msg['addr']);

                    /* Updating 'debug.json' content */
                    if (file_exists($dbgFile))
                        $devConfig = json_decode(file_get_contents($dbgFile), TRUE);
                    else
                        $devConfig = array();
                    $devConfig["dbgMonitorAddr"] = $msg['addr'];
                    file_put_contents($dbgFile, json_encode($devConfig));
                    chmod($dbgFile, 0666); // Allow read & write
                }
            }
        }

        catch (Exception $e) {
            // $AbeilleParser->deamonlog( 'debug', 'error: '. json_encode($e->getMessage()));
            logMessage("error", "ERR: Exception ".json_encode($e->getMessage()));
        }

        logMessage("info", "<<< Arret du démon de monitoring.");
        exit(12);
    }
?>
