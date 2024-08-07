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
    include_once __DIR__.'/AbeilleCmd-Tuya.php'; // Tuya specific commands
    include_once __DIR__.'/AbeilleCmd-Profalux.php'; // Profalux specific commands
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
        Returns: device entry by reference or [] if error */
    function &getDevice($net, $addr) {
        static $error = [];
        if (!isset($GLOBALS['devices'][$net]))
            return $error;
        if (!isset($GLOBALS['devices'][$net][$addr]))
            return $error;

        return $GLOBALS['devices'][$net][$addr];
    }

    // Reread Jeedom useful infos on eqLogic DB update
    // Note: A delay is required prior to this if DB has to be updated (createDevice() in Abeille.class)
    function updateDeviceFromDB($eqId) {
        logMessage('debug', "  updateDeviceFromDB(${eqId})");

        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            logMessage('debug', "  ERROR: updateDeviceFromDB(): Equipment ID ${eqId} does not exist");
            return;
        }

        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        if (!isset($GLOBALS['devices'][$net]))
            $GLOBALS['devices'][$net] = [];

        $ieee = $eqLogic->getConfiguration('IEEE', '');
        if (!isset($GLOBALS['devices'][$net][$addr]) && ($ieee == '')) {
            logMessage('debug', "  ERROR: updateDeviceFromDB(): Unknown addr '${net}/${addr}' and IEEE is undefined");
            return;
        }

        // Checking if known by AbeilleCmd
        $found = false;
        if (isset($GLOBALS['devices'][$net][$addr]))
            $found = true;
        if ($found == false) {
            // Unknown: Address may have changed following new 'device announce'
            foreach ($GLOBALS['devices'][$net] as $addr2 => $eq2) {
                if ($eq2['ieee'] == $ieee) {
                    $found = true;
                    $GLOBALS['devices'][$net][$addr] = $GLOBALS['devices'][$net][$addr2];
                    unset($GLOBALS['devices'][$net][$addr2]);
                    logMessage('debug', "  Device ID ${eqId} address changed from '${net}/${addr2}' to '${net}/${addr}'.");
                    break;
                }
            }
        }
        if ($found == false) {
            // Unknown: Equipment may have been migrated from another network
            foreach ($GLOBALS['devices'] as $net2 => $devices2) { // Go thru all networks
                if ($net2 == $net)
                    continue; // This network has already been checked
                foreach ($GLOBALS['devices'][$net2] as $addr2 => $eq2) {
                    if ($eq2['ieee'] == $ieee) {
                        $found = true;
                        $GLOBALS['devices'][$net][$addr] = $GLOBALS['devices'][$net2][$addr2];
                        unset($GLOBALS['devices'][$net2][$addr2]);
                        logMessage('debug', "  Device ID ${eqId} migrated from '${net2}/${addr2}' to '${net}/${addr}'.");
                        break;
                    }
                }
            }
        }

        // Whatever found or new... updating infos used by cmd process
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        $eq = array(
            'ieee' => $ieee,
            'txStatus' => $eqLogic->getStatus('ab::txAck', 'ok'), // Transmit status: 'ok' or 'noack'
            'jsonLocation' => isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille',
            'jsonId' => isset($eqModel['modelName']) ? $eqModel['modelName'] : '',
            // 'modelPath' => isset($eqModel['modelPath']) ? $eqModel['modelPath'] : "<modName>/<modName>.json",
            'rxOnWhenIdle' => (isset($zigbee['rxOnWhenIdle']) && ($zigbee['rxOnWhenIdle'] == 1)) ? true : false
        );
        if ($eq['jsonId'] != '') {
            if (isset($eqModel['modelPath']))
                $eq['modelPath'] = $eqModel['modelPath'];
            else
                $eq['modelPath'] = $eq['jsonId']."/".$eq['jsonId'].".json";

            // Read JSON to get list of commands to execute
            $model = getDeviceModel($eq['jsonLocation'], $eq['modelPath'], $eq['jsonId']);
            if ($model !== false) {
                $eq['mainEp'] = isset($model['mainEP']) ? $model['mainEP'] : "01";
                $eq['commands'] = isset($model['commands']) ? $model['commands'] : [];
            }
        }
        if (isset($eqModel['variables']))
            $eq['variables'] = $eqModel['variables'];

        $GLOBALS['devices'][$net][$addr] = $eq;
    }

    /* Send msg to 'xToCmd' queue. */
    function msgToCmd($topic, $payload = '') {
        $msg = array(
            'topic' => $topic,
            'payload' => $payload
        );
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

        global $abQueues;
        $queue = msg_get_queue($abQueues["xToCmd"]["id"]);
        if (msg_send($queue, 1, $msgJson, false, false, $errCode) == false) {
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

    // Configure Zigate
    // Called to configure Zigate when receive channel is already opened to not loose responses
    function configureZigate($zgId) {
        cmdLog('debug', "configureZigate(${zgId})");

        msgToCmd("CmdAbeille".$zgId."/0000/zgSoftReset", "");
        // Cmds delayed by 1sec to wait for chip reset

        global $config;
        if (isset($config['ab::gtwChan'.$zgId])) {
            $chan = $config['ab::gtwChan'.$zgId];
            if ($chan == 0)
                $mask = 0x7fff800; // All channels = auto
            else
                $mask = 1 << $chan;
            $mask = sprintf("%08X", $mask);
            cmdLog('debug', "  Settings chan ".$chan." (mask=".$mask.") for zigate ".$zgId);
            msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetChannelMask&tempo=".(time()+1), "mask=".$mask);
        }
        msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetTimeServer&tempo=".(time()+1), "");

        if (isset($config['ab::forceZigateHybridMode']) && ($config['ab::forceZigateHybridMode'] == "Y")) {
            $mode = "hybrid";
        } else
            $mode = "raw";
        cmdLog('debug', "  Configuring Zigate ${zgId} in ${mode} mode");
        msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetMode&tempo=".(time()+1), "mode=${mode}");

        msgToCmd("TempoCmdAbeille".$zgId."/0000/zgStartNetwork&tempo=".(time()+1), "");

        msgToCmd("TempoCmdAbeille".$zgId."/0000/zgGetVersion&tempo=".(time()+1), "");
    }

    // Configure device
    // Returns: true=ok, false=error
    function configureDevice($net, $addr) {
        cmdLog('debug', "  configureDevice(${net}, ${addr})");

        if (!isset($GLOBALS['devices'][$net][$addr])) {
            cmdLog('debug', "  configureDevice() ERROR: Unknown device");
            return false; // Unexpected request
        }

        $eq = $GLOBALS['devices'][$net][$addr];
        cmdLog('debug', "    eq=".json_encode($eq, JSON_UNESCAPED_SLASHES));

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
            cmdLog('debug', "    Exec cmd '".$cmdJName."' with delay ".$delay);
            $topic = $c['topic'];
            $request = $c['request'];

            $request = str_ireplace('#addrIEEE#', $eq['ieee'], $request);
            $request = str_ireplace('#IEEE#', $eq['ieee'], $request);
            $request = str_ireplace('#EP#', $eq['mainEp'], $request);
            $zgId = substr($net, 7); // 'AbeilleX' => 'X'
            $request = str_ireplace('#ZiGateIEEE#', $GLOBALS['zigates'][$zgId]['ieee'], $request);

            // Final replacement of remaining variables (#var#) in 'request'
            // Use case: Variables 'groupEPx'
            if (isset($eq['variables'])) {
                $offset = 0;
                while(true) {
                    $varStart = strpos($request, "#", $offset); // Start
                    if ($varStart === false)
                        break;

                    if ($offset == 0)
                        cmdLog('debug', "      Final variables replacement with 'variables' section.");

                    $varEnd = strpos($request, "#", $varStart + 1); // End
                    if ($varEnd === false) {
                        // log::add('Abeille', 'error', "getDeviceModel(): No closing dash (#) for cmd '${cmdJName}'");
                        cmdLog('error', "      No closing dash (#)");
                        break;
                    }
                    $varLen = $varEnd - $varStart + 1;
                    $var = substr($request, $varStart, $varLen); // $var='#xxx#'
                    $varUp = strtoupper(substr($var, 1, -1)); // '#var#' => 'VAR' (no #)
                    cmdLog('debug', "      Start=${varStart}, End=${varEnd} => Size=${varLen}, var=${var}, varUp=${varUp}");
                    if (isset($eq['variables'][$varUp])) {
                        $varNew = $eq['variables'][$varUp];
                        cmdLog('debug', "      Replacing '${var}' by '${varNew}");
                        $request = str_ireplace($var, $varNew, $request);
                        $offset = $varStart + strlen($varNew);
                    } else
                        $offset = $varStart + $varLen;
                }
            }

            cmdLog('debug', '    topic='.$topic.", request='".$request."'");
            if ($delay == 0)
                $topic = "Cmd".$net."/".$addr."/".$topic;
            else {
                $delay = time() + $delay;
                $topic = "TempoCmd".$net."/".$addr."/".$topic.'&time='.$delay;
            }
            msgToCmd($topic, $request);
        }

        return true;
    } // End configureDevice()

    // Remove all pending messages for given zgId/addr.
    // Useful for ex when addr changed on device announce.
    function clearPending($zgId, $addr) {
        cmdLog("debug", "  clearPending(${zgId}, ${addr})");
        foreach ($GLOBALS['zigates'][$zgId]['cmdQueue'] as $pri => $q) {
            cmdLog("debug", "  pri=${pri}, q=".json_encode($q));
            $count = count($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri]);
            for ($cmdIdx = 0; $cmdIdx < $count; ) {
                $cmd = $GLOBALS['zigates'][$zgId]['cmdQueue'][$pri][$cmdIdx];
                if ($cmd['addr'] == $addr) {
                    array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri], $cmdIdx, 1);
                    // Note: cmd @cmdIdx is the next cmd after array_splice
                    cmdLog("debug", "  Removed Pri/Idx=${pri}/${cmdIdx}");
                    $count--;
                    continue;
                }
                $cmdIdx++;
            }
        }
    }

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
        $gtwId = substr($net, 7); // 'AbeilleX' => 'X'
        if ($gtwId == '')
            continue; // Incorrect case

        if ($config['ab::gtwType'.$gtwId] != 'zigate')
            continue; // Not a Zigate network

        if ($addr == "0000") { // Gateway ?
            if (!isset($GLOBALS['zigates'][$gtwId]))
                $GLOBALS['zigates'][$gtwId] = [];

            $GLOBALS['zigates'][$gtwId]['ieee'] = $eqLogic->getConfiguration('IEEE', '');
            $GLOBALS['zigates'][$gtwId]['enabled'] = ($config['ab::gtwEnabled'.$gtwId] == "Y") ? true : false;
            $GLOBALS['zigates'][$gtwId]['ieeeOk'] = ($config['ab::zgIeeeAddrOk'.$gtwId] == 1) ? true : false;
            $GLOBALS['zigates'][$gtwId]['port'] = $config['ab::gtwPort'.$gtwId];
            $GLOBALS['zigates'][$gtwId]['available'] = true; // By default we consider the Zigate available to receive commands
            $GLOBALS['zigates'][$gtwId]['status'] = 'waitParser'; // 'waitParser', 'ok'
            $GLOBALS['zigates'][$gtwId]['hw'] = 0;           // HW version: 1=v1, 2=v2
            $GLOBALS['zigates'][$gtwId]['fw'] = 0;           // FW minor version (ex 0x321)
            $GLOBALS['zigates'][$gtwId]['nPDU'] = 0;         // Last NDPU
            $GLOBALS['zigates'][$gtwId]['aPDU'] = 0;         // Last APDU
            $GLOBALS['zigates'][$gtwId]['cmdQueue'] = [];    // Array of queues. One queue per priority from priorityMin to priorityMax.
            foreach(range(priorityMin, priorityMax) as $prio) {
                $GLOBALS['zigates'][$gtwId]['cmdQueue'][$prio] = [];
            }
            $GLOBALS['zigates'][$gtwId]['sentPri'] = 0;      // Last sent cmd priority
            $GLOBALS['zigates'][$gtwId]['sentIdx'] = 0;      // Last send cmd index
        } else {
            // Is the gateway a Zigate ?

            if (!isset($GLOBALS['devices'][$net]))
                $GLOBALS['devices'][$net] = [];

            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            // cmdLog('debug', "    eqModel for '$eqLogicId'=".json_encode($eqModel, JSON_UNESCAPED_SLASHES));
            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);

            $eq = array(
                'ieee' => $eqLogic->getConfiguration('IEEE', ''),
                'txStatus' => $eqLogic->getStatus('ab::txAck', 'ok'), // Transmit status: 'ok' or 'noack'
                'jsonId' => isset($eqModel['modelName']) ? $eqModel['modelName'] : '',
                'jsonLocation' => isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille',
                // 'modelPath' => isset($eqModel['modelPath']) ? $eqModel['modelPath'] : "<modName>/<modName>.json",
                'rxOnWhenIdle' => (isset($zigbee['rxOnWhenIdle']) && ($zigbee['rxOnWhenIdle'] == 1)) ? true : false
                // 'variables' // Optional
            );
            if ($eq['jsonId'] != '') {
                if (isset($eqModel['modelPath']))
                    $eq['modelPath'] = $eqModel['modelPath'];
                else
                    $eq['modelPath'] = $eq['jsonId']."/".$eq['jsonId'].".json";

                // Read JSON to get list of commands to execute
                $model = getDeviceModel($eq['jsonLocation'], $eq['modelPath'], $eq['jsonId']);
                if ($model !== false) {
                    $eq['mainEp'] = isset($model['mainEP']) ? $model['mainEP'] : "01";
                    $eq['commands'] = isset($model['commands']) ? $model['commands'] : [];
                }
            }
            if (isset($eqModel['variables'])) {
                $eq['variables'] = $eqModel['variables'];
                // cmdLog('debug', "    EQ with variables=".json_encode($eq, JSON_UNESCAPED_SLASHES));
            }

            cmdLog('debug', "    eq=".json_encode($eq, JSON_UNESCAPED_SLASHES));
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
            // Treat Zigate key infos (ex 0x8000 cmd) coming from parser
            $AbeilleCmdQueue->processAcksQueue();

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
