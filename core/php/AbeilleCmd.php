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
    include_once __DIR__.'/AbeilleZigateConst.php';

    function cmdLog($loglevel = 'NONE', $message = "", $isEnable = 1) {
        if ($isEnable == 0)
            return;
        logMessage($loglevel, $message);
    }

    // Reread Jeedom useful infos on eqLogic DB update
    // Note: A delay is required prior to this if DB has to be updated (createDevice() in Abeille.class)
    function updateDeviceFromDB($eqId) {
        $eqLogic = eqLogic::byId($eqId);
        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        // $GLOBALS['eqList'][$net][$addr]['tuyaEF00'] = $eqLogic->getConfiguration('ab::tuyaEF00', null);
        // parserLog('debug', "  'tuyaEF00' updated to ".json_encode($GLOBALS['eqList'][$net][$addr]['tuyaEF00']));
        // $GLOBALS['eqList'][$net][$addr]['xiaomi'] = $eqLogic->getConfiguration('ab::xiaomi', null);
        // parserLog('debug', "  'xiaomi' updated to ".json_encode($GLOBALS['eqList'][$net][$addr]['xiaomi']));
        // $GLOBALS['eqList'][$net][$addr]['customization'] = $eqLogic->getConfiguration('ab::customization', null);
        // parserLog('debug', "  'customization' updated to ".json_encode($GLOBALS['eqList'][$net][$addr]['customization']));
        // TO BE COMPLETED if any other key info
    }

    /* Send msg to 'xToCmd' queue. */
    function msgToCmd($msg) {
        global $abQueues;

        $queue = msg_get_queue($abQueues["xToCmd"]["id"]);
        if (msg_send($queue, 1, json_encode($msg), false, false, $errCode) == false) {
            parserLog("debug", "msgToCmd(): ERROR ".$errCode);
        }
    }

        // Configure device
    // Returns: true=ok, false=error
    // WORK ONGOING: Not used yet. Currently same function in AbeilleParser.class.php.
    function configureDevice($net, $addr) {
        cmdLog('debug', "  configureDevice(".$net.", ".$addr.")");

        $eq = $GLOBALS['devices'][$net][$addr];
        if (!isset($eq['commands'])) {
            cmdLog('debug', "    No cmds in JSON model.");
            return true;
        }

        $cmds = $eq['commands'];
        cmdLog('debug', "    cmds=".json_encode($cmds, JSON_UNESCAPED_SLASHES));
        foreach ($cmds as $cmdJName => $cmd) {
            if (!isset($cmd['configuration']))
                continue; // No 'configuration' section then no 'execAtCreation'

            $c = $cmd['configuration'];
            if (!isset($c['execAtCreation']))
                continue;

            if (isset($c['execAtCreationDelay']))
                $delay = $c['execAtCreationDelay'];
            else
                $delay = 0;
            cmdLog('debug', "    exec cmd '".$cmdJName."' with delay ".$delay);
            $topic = $c['topic'];
            $request = $c['request'];
            // TODO: #EP# defaulted to first EP but should be
            //       defined in cmd use if different target EP
            // $request = str_ireplace('#EP#', $eq['epFirst'], $request);
            $request = str_ireplace('#addrIEEE#', $eq['ieee'], $request);
            $request = str_ireplace('#IEEE#', $eq['ieee'], $request);
            $request = str_ireplace('#EP#', $eq['mainEp'], $request);
            $zgId = substr($net, 7); // 'AbeilleX' => 'X'
            $request = str_ireplace('#ZiGateIEEE#', $GLOBALS['zigates'][$zgId]['ieee'], $request);
            cmdLog('debug', '      topic='.$topic.", request='".$request."'");
            if ($delay == 0)
                $topic = "Cmd".$net."/".$addr."/".$topic;
            else {
                $delay = time() + $delay;
                $topic = "TempoCmd".$net."/".$addr."/".$topic.'&time='.$delay;
            }
            $msg = array(
                'topic' => $topic,
                'payload' => $request
            );
            msgToCmd($msg);
        }

        return true;
    } // End configureDevice()

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

    /* Init known devices list:
       $GLOBALS['devices'][net][addr]
          ieee => from device config
          mainEp => from model
          jsonId =>
          jsonLocation =>
          commands => from model
       $GLOBALS['zigates'][zgId]
          ieee =>
     */
    $GLOBALS['devices'] = [];
    $GLOBALS['zigates'] = [];
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);
        $zgId = substr($net, 7); // 'AbeilleX' => 'X'

        if ($addr == "0000") { // Zigate ?
            if (!isset($GLOBALS['zigates'][$zgId]))
                $GLOBALS['zigates'][$zgId] = [];

            $GLOBALS['zigates'][$zgId]['ieee'] = $eqLogic->getConfiguration('IEEE', '');
            continue;
        }

        if (!isset($GLOBALS['devices'][$net]))
            $GLOBALS['devices'][$net] = [];

        $eq = [];
        $eq['ieee'] = $eqLogic->getConfiguration('IEEE', '');
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
        $eq['jsonId'] = isset($eqModel['id']) ? $eqModel['id'] : '';
        $eq['jsonLocation'] = isset($eqModel['location']) ? $eqModel['location'] : 'Abeille';
        // Read JSON to get list of commands to execute
        $model = AbeilleTools::getDeviceModel($eq['jsonId'], $eq['jsonLocation']);
        if ($model !== false) {
            $eq['mainEp'] = isset($model['mainEP']) ? $model['mainEP'] : "01";
            $eq['commands'] = isset($model['commands']) ? $model['commands'] : [];
        }

        $GLOBALS['devices'][$net][$addr] = $eq;
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

            // Check 'xToCmd' queue
            $AbeilleCmdQueue->processXToCmdQueue();

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
