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

    /* Developers options */
    if (file_exists(dbgFile)) {
        // $dbgConfig = json_decode(file_get_contents(dbgFile), true);

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
        global $abQueues;

        // logMessage("debug", "monMsgFromZigate('".$addr."', '".$msgDecoded."')");

        /* Check queue */
        if (!msg_queue_exists($abQueues['parserToMon']['id']))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'x2mon', 'msg' => $msgDecoded);

        /* Write to fifo */
        $queue = msg_get_queue($abQueues['parserToMon']['id']);
        msg_send($queue, 1, json_encode($msg), false);
    }

    /* Called from AbeilleParser when short address has changed (device announce) */
    function monAddrHasChanged($addr, $ieee)
    {
        global $abQueues;

        /* Check queue */
        if (!msg_queue_exists($abQueues['parserToMon']['id']))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'newaddr', 'addr' => $addr, 'ieee' => $ieee);

        /* Write to fifo */
        $queue = msg_get_queue($abQueues['parserToMon']['id']);
        msg_send($queue, 1, json_encode($msg), false);
    }

    /* Called from AbeilleCmd to add a message to monitor.
       'logSetConf()' must be called first from 'AbeilleCmd'. */
    function monMsgToZigate($addr, $msgDecoded) {
        global $abQueues;

        // logMessage("debug", "monMsgToZigate('".$addr."', '".$msgDecoded."')");

        /* $addr currently unused. Filtered at upper level */
        // $shortAddr = strtolower($shortAddr); // Convert lower case
        // global $dbgMonitorAddr;
        // if ($shortAddr != strtolower($dbgMonitorAddr))
            // return;

        /* Check queue */
        if (!msg_queue_exists($abQueues['cmdToMon']['id']))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'x2mon', 'msg' => $msgDecoded);

        /* Write to fifo */
        $queue = msg_get_queue($abQueues['cmdToMon']['id']);
        msg_send($queue, 1, json_encode($msg), false);
    }

    /* Called to inform 'AbeilleCmd' to change monitoring address. */
    function monMsgToCmd($addr)  {
        global $abQueues;

        /* Check queue */
        if (!msg_queue_exists($abQueues['monToCmd']['id']))
            return; // Ignored

        /* Compose FIFO message */
        $msg = array('type' => 'newaddr', 'addr' => $addr);

        /* Write to fifo */
        $queue = msg_get_queue($abQueues['monToCmd']['id']);
        msg_send($queue, 1, json_encode($msg), false);
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
    //         $dbgConfig = json_decode(file_get_contents(dbgFile), true);
    //         if (isset($dbgConfig["dbgMonitorAddr"])) {
    //             logMessage("info", "Adresse à surveiller: ".$dbgConfig["dbgMonitorAddr"]);
    //         } else
    //             logMessage("info", "Aucune adresse à surveiller pour l'instant.");
    //     } else
    //         logMessage("debug", "Pas de config dev a recharger.");
    // }

    /* Run AbeilleMonitor daemon.
       Address to monitor is currently extracted from developper config file. */
    function monRun() {
        global $abQueues;

        /* Configuring 'AbeilleLog' library */
        logSetConf("AbeilleMonitor.log", true);
        logMessage("info", ">>> Démarrage du démon de monitoring");

        declare(ticks = 1);
        if (pcntl_signal(SIGTERM, "monShutdown", false) != true)
            logMessage("error", "Erreur pcntl_signal()");
        // if (pcntl_signal(SIGUSR1, "monReload", false) != true)
        //     logMessage("error", "Erreur pcntl_signal()");

        $GLOBALS["monId"] = config::byKey('ab::monitorId', 'Abeille', false);
        $monId = $GLOBALS["monId"];
        if ($monId !== false) {
            $eqLogic = eqLogic::byId($monId);
            if (!is_object($eqLogic)) {
                logMessage('debug', 'ID '.$monId.' no longer valid. Disabling monitoring');
                config::save('ab::monitorId', false, 'Abeille');
                logMessage("info", "Aucun équipement à surveiller pour l'instant.");
            } else {
                list($net, $addr) = explode( "/", $eqLogic->getLogicalId());
                $ieee = $eqLogic->getConfiguration('IEEE', '');
                logMessage("info", "Equipement à surveiller: ".$eqLogic->getHumanName().', '.$addr.'-'.$ieee);
            }
        } else
            logMessage("info", "Aucun équipement à surveiller pour l'instant.");

        /* Main
           Create queues and check them regularly
         */
        try {
            $queueCmdToMon = msg_get_queue($abQueues['cmdToMon']['id']); // Messages sent to monitored device
            $queueParserToMon = msg_get_queue($abQueues['parserToMon']['id']); // Messages received from monitored device
            if (!is_resource($queueCmdToMon) || !is_resource($queueParserToMon)) {
                logMessage("error", "ERR: Problème de création des queues. Arret du démon.");
                return;
            }

            $msgMaxSize = 512;

            while (true) {
                time_nanosleep(0, 10000000); // 10ms

                /* Check if one of the queues is not empty */
                /* Select oldest message then log it and remove it from queue */

                $statCmdQueue = msg_stat_queue($queueCmdToMon);
                $statParserQueue = msg_stat_queue($queueParserToMon);
                if (($statCmdQueue === false) || ($statParserQueue === false)) {
                    logMessage("debug", "msg_stat_queue() FAILED");
                    continue;
                }
                if (($statCmdQueue['msg_qnum'] == 0) && ($statParserQueue['msg_qnum'] == 0)) {
                    continue;
                }

                $msgType = NULL; // Unused
                $msgFromCmd = true; // Message direction: assuming sent so coming from 'AbeilleCmd'
                if (($statCmdQueue['msg_qnum'] != 0) && ($statParserQueue['msg_qnum'] != 0)) { // Message in both queues ?
                    /* Select queue with oldest message */
                    if ($statCmdQueue['msg_stime'] > $statParserQueue['msg_stime']) {
                        $msgFromCmd = false;
                    }
                // } else if ($statCmdQueue['msg_qnum'] != 0)
                //     $queue = $queueCmdToMon;
                } else {
                    $msgFromCmd = false;
                }

                if ($msgFromCmd)
                    $queue = $queueCmdToMon;
                else
                    $queue = $queueParserToMon;
                $status = msg_receive($queue, 0, $msgType, $msgMaxSize, $msgJson, false, MSG_IPC_NOWAIT, $errCode);
                if ($status === false) {
                    logMessage("debug", "msg_received() FAILED: ErrCode=${$errCode}");
                    continue;
                }
                if ($msgJson == '') {
                    logMessage("debug", "EMPTY message received: Status=${status}, ErrCode=${$errCode}");
                    continue;
                }

                $msg = json_decode($msgJson, true);
                if ($msg['type'] == 'x2mon') {
                    /* Log message */
                    if ($msgFromCmd == true)
                        logMessage("debug", "=> ".$msg['msg']);
                    else
                        logMessage("debug", "<= ".$msg['msg']);
                } else {
                    /* Should be 'newaddr' msg type */
logMessage("debug", "TEMPORARY: msgJson=${msgJson}");
                    logMessage("debug", "<= New short addr for ".$msg['ieee'].": ".$msg['addr']);

                    /* Informing 'AbeilleCmd' that addr has changed.
                       Note: 2 ways to inform AbeilleCmd, either thru a dedicated queue (current case) or restarting it */
                    monMsgToCmd($msg['addr']);

                    // /* Updating 'debug.json' content */
                    // if (file_exists($dbgFile))
                    //     $devConfig = json_decode(file_get_contents($dbgFile), true);
                    // else
                    //     $devConfig = array();
                    // $devConfig["dbgMonitorAddr"] = $msg['addr'];
                    // file_put_contents($dbgFile, json_encode($devConfig));
                    // chmod($dbgFile, 0666); // Allow read & write
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
