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

    // Log if proper log level
    function cmdLog($level, $message = "", $isEnable = 1) {
        if ($isEnable == 0)
            return;
        logMessage($level, $message);
    }

    // Log if proper log level
    // & monitor if proper address
    function cmdLog2($level, $addr, $message) {
        logMessage($level, $message);

        if (isset($GLOBALS["dbgMonitorAddr"]) && ($GLOBALS["dbgMonitorAddr"] != "") && ($addr == $GLOBALS["dbgMonitorAddr"]))
            monMsgToZigate($addr, $message);
    }

    /* Get device infos.
        Returns: device entry by reference or false */
    function &getDevice($net, $addr) {
        if (!isset($GLOBALS['devices'][$net]))
            return false;
        if (!isset($GLOBALS['devices'][$net][$addr]))
            return false;

        return $GLOBALS['devices'][$net][$addr];
    }

    // Reread Jeedom useful infos on eqLogic DB update
    // Note: A delay is required prior to this if DB has to be updated (createDevice() in Abeille.class)
    function updateDeviceFromDB($eqId) {
        $eqLogic = eqLogic::byId($eqId);
        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        $ieee = $eqLogic->getConfiguration('IEEE', '');
        if (!isset($GLOBALS['devices'][$net][$addr]) && ($ieee == '')) {
            logMessage('debug', "  updateDeviceFromDB() WARNING: Unknown addr ${addr} and IEEE is undefined");
            return;
        }
        if (!isset($GLOBALS['devices'][$net][$addr])) {
            // Address may have changed following new device announce
            foreach ($GLOBALS['devices'][$net] as $addr2 => $eq2) {
                if ($eq2['ieee'] == $ieee) {
                    $GLOBALS['devices'][$net][$addr] = $GLOBALS['devices'][$net][$addr2];
                    unset($GLOBALS['devices'][$net][$addr2]);
                    logMessage('debug', "  Device ID ${eqId} address changed from ${addr2} to ${addr}.");
                }
            }
        }

        // $GLOBALS['devices'][$net][$addr]['tuyaEF00'] = $eqLogic->getConfiguration('ab::tuyaEF00', null);
        // parserLog('debug', "  'tuyaEF00' updated to ".json_encode($GLOBALS['devices'][$net][$addr]['tuyaEF00']));
        // $GLOBALS['devices'][$net][$addr]['xiaomi'] = $eqLogic->getConfiguration('ab::xiaomi', null);
        // parserLog('debug', "  'xiaomi' updated to ".json_encode($GLOBALS['devices'][$net][$addr]['xiaomi']));
        // $GLOBALS['devices'][$net][$addr]['customization'] = $eqLogic->getConfiguration('ab::customization', null);
        // parserLog('debug', "  'customization' updated to ".json_encode($GLOBALS['devices'][$net][$addr]['customization']));
        // TO BE COMPLETED if any other key info
    }

    /* Send msg to 'xToCmd' queue. */
    function msgToCmd($msg) {
        global $abQueues;

        $queue = msg_get_queue($abQueues["xToCmd"]["id"]);
        if (msg_send($queue, 1, json_encode($msg), false, false, $errCode) == false) {
            cmdLog("debug", "msgToCmd(): ERROR ".$errCode);
        }
    }

    /* Send msg to Abeille ('xToAbeille' queue) */
    function msgToAbeille($msg) {
        global $abQueues;

        $queue = msg_get_queue($abQueues["xToAbeille"]["id"]);
        if (msg_send($queue, 1, json_encode($msg), false, false, $errCode) == false) {
            cmdLog("debug", "msgToAbeille(): ERROR ".$errCode);
        }
    }

    // Configure device
    // Returns: true=ok, false=error
    // WORK ONGOING: Not used yet. Currently same function in AbeilleParser.class.php.
    function configureDevice($net, $addr) {
        cmdLog('debug', "  configureDevice(".$net.", ".$addr.")");

        if (!isset($GLOBALS['devices'][$net][$addr])) {
            cmdLog('debug', "  configureDevice() ERROR: Unknown device");
            return false; // Unexpected request
        }

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
    logMessage('info', ">>> {{Démarrage d'AbeilleCmd}}");

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************

    $config = AbeilleTools::getConfig();
    $running = AbeilleTools::getRunningDaemons();
    $daemons = AbeilleTools::diffExpectedRunningDaemons($config, $running);
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
    $monId = $config['ab::monitorId'];
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
          ieee => '' or IEEE address
          enabled => true or false
          ieeeOk => true or false
          port => '' or serial port (ex: '/dev/ttyS1')
          available => true or false
          hw => 1=v1, 2=v2/+, 0=undefined
          fw =>
          nPDU =>
          aPDU =>
          cmdQueue => array or array, cmdQueue[prio] = []
          sentPri => Priority of last sent cmd
     */
    $GLOBALS['devices'] = [];
    $GLOBALS['zigates'] = [];
    $GLOBALS['lastSqn'] = 1;
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);
        $zgId = substr($net, 7); // 'AbeilleX' => 'X'

        if ($addr == "0000") { // Zigate ?
            if (!isset($GLOBALS['zigates'][$zgId]))
                $GLOBALS['zigates'][$zgId] = [];

            $GLOBALS['zigates'][$zgId]['ieee'] = $eqLogic->getConfiguration('IEEE', '');
            $GLOBALS['zigates'][$zgId]['enabled'] = ($config['ab::zgEnabled'.$zgId] == "Y") ? true : false;
            $GLOBALS['zigates'][$zgId]['ieeeOk'] = ($config['ab::zgIeeeAddrOk'.$zgId] == 1) ? true : false;
            $GLOBALS['zigates'][$zgId]['port'] = $config['ab::zgPort'.$zgId];
            $GLOBALS['zigates'][$zgId]['available'] = true; // By default we consider the Zigate available to receive commands
            $GLOBALS['zigates'][$zgId]['hw'] = 0;           // HW version: 1=v1, 2=v2
            $GLOBALS['zigates'][$zgId]['fw'] = 0;           // FW minor version (ex 0x321)
            $GLOBALS['zigates'][$zgId]['nPDU'] = 0;         // Last NDPU
            $GLOBALS['zigates'][$zgId]['aPDU'] = 0;         // Last APDU
            $GLOBALS['zigates'][$zgId]['cmdQueue'] = [];    // Array of queues. One queue per priority from priorityMin to priorityMax.
            foreach(range(priorityMin, priorityMax) as $prio) {
                $GLOBALS['zigates'][$zgId]['cmdQueue'][$prio] = [];
            }
            $GLOBALS['zigates'][$zgId]['sentPri'] = 0;      // Priority for last sent cmd for following 8000 ack
        } else {
            if (!isset($GLOBALS['devices'][$net]))
                $GLOBALS['devices'][$net] = [];

            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            $eq = array(
                'ieee' => $eqLogic->getConfiguration('IEEE', ''),
                'txStatus' => $eqLogic->getStatus('ab::txAck', 'ok'), // Transmit status: 'ok' or 'noack'
                'jsonId' => isset($eqModel['id']) ? $eqModel['id'] : '',
                'jsonLocation' => isset($eqModel['location']) ? $eqModel['location'] : 'Abeille',
            );
            if ($eq['jsonId'] != '') {
                // Read JSON to get list of commands to execute
                $model = AbeilleTools::getDeviceModel($eq['jsonId'], $eq['jsonLocation']);
                if ($model !== false) {
                    $eq['mainEp'] = isset($model['mainEP']) ? $model['mainEP'] : "01";
                    $eq['commands'] = isset($model['commands']) ? $model['commands'] : [];
                }
            }

            $GLOBALS['devices'][$net][$addr] = $eq;
        }
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

            // Check zigates status
            $AbeilleCmdQueue->checkZigatesStatus();

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
