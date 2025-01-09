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
    include_once __DIR__.'/AbeilleZigbee.php'; // Zigbee specific functions

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
        logMessage('debug', "  updateDeviceFromDB({$eqId})");

        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            logMessage('debug', "  ERROR: updateDeviceFromDB(): Equipment ID {$eqId} does not exist");
            return;
        }

        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        if (!isset($GLOBALS['devices'][$net]))
            $GLOBALS['devices'][$net] = [];

        $ieee = $eqLogic->getConfiguration('IEEE', '');
        if (!isset($GLOBALS['devices'][$net][$addr]) && ($ieee == '')) {
            logMessage('debug', "  ERROR: updateDeviceFromDB(): Unknown addr '{$net}/{$addr}' and IEEE is undefined");
            return;
        }

        // Checking if known by AbeilleCmd
        $found = false;
        if (isset($GLOBALS['devices'][$net][$addr]))
            $found = true;
        if ($found == false) {
            // Unknown: Address may have changed following new 'device announce'
            foreach ($GLOBALS['devices'][$net] as $addr2 => $eq2) {
                if ($eq2['zigbee']['ieee'] == $ieee) {
                    $found = true;
                    $GLOBALS['devices'][$net][$addr] = $GLOBALS['devices'][$net][$addr2];
                    unset($GLOBALS['devices'][$net][$addr2]);
                    logMessage('debug', "  Device ID {$eqId} address changed from '{$net}/{$addr2}' to '{$net}/{$addr}'.");
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
                    if ($eq2['zigbee']['ieee'] == $ieee) {
                        $found = true;
                        $GLOBALS['devices'][$net][$addr] = $GLOBALS['devices'][$net2][$addr2];
                        unset($GLOBALS['devices'][$net2][$addr2]);
                        logMessage('debug', "  Device ID {$eqId} migrated from '{$net2}/{$addr2}' to '{$net}/{$addr}'.");
                        break;
                    }
                }
            }
        }

        // Whatever found or new... updating infos used by cmd process
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        $zigbee['ieee'] = $ieee;
        $zigbee['txStatus'] = $eqLogic->getStatus('ab::txAck', 'ok'); // Transmit status: 'ok' or 'noack'
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
        $eq = array(
            // 'ieee' => $ieee,
            // 'rxOnWhenIdle' => (isset($zigbee['rxOnWhenIdle']) && ($zigbee['rxOnWhenIdle'] == 1)) ? true : false
            // 'txStatus' => $eqLogic->getStatus('ab::txAck', 'ok'), // Transmit status: 'ok' or 'noack'
            'zigbee' => $zigbee,

            // 'jsonId' => isset($eqModel['modelName']) ? $eqModel['modelName'] : '',
            // 'jsonLocation' => isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille',
            // 'modelPath' => isset($eqModel['modelPath']) ? $eqModel['modelPath'] : "<modName>/<modName>.json",
            'eqModel' => $eqModel
        );
        if (isset($eq['eqModel']['modelName']) && ($eq['eqModel']['modelName'] != '')) {
            // if (isset($eqModel['modelPath']))
            //     $eq['modelPath'] = $eqModel['modelPath'];
            // else
            //     $eq['modelPath'] = $eq['eqModel']['modelName']."/".$eq['eqModel']['modelName'].".json";
            if (!isset($eq['eqModel']['modelPath']))
                $eq['eqModel']['modelPath'] = "";

            // Read JSON to get list of commands to execute
            $model = getDeviceModel($eq['eqModel']['modelSource'], $eq['eqModel']['modelPath'], $eq['eqModel']['modelName']);
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

        global $queueXToCmd;
        // Note: '@' to suppress PHP warning message.
        if (@msg_send($queueXToCmd, 1, $msgJson, false, false, $errCode) == false) {
            cmdLog("debug", "  msgToCmd(xToCmd) ERROR {$errCode}/".AbeilleTools::getMsgSendErr($errCode));
        }
    }

    /* Send msg to Abeille ('xToAbeille' queue) */
    function msgToAbeille($msg) {

        global $queueXToAbeille;
        // Note: '@' to suppress PHP warning message.
        if (@msg_send($queueXToAbeille, 1, json_encode($msg), false, false, $errCode) == false) {
            cmdLog("debug", "  msgToAbeille() ERROR {$errCode}/".AbeilleTools::getMsgSendErr($errCode));
        }
    }

    // Configure Zigate
    // Called to configure Zigate when receive channel is already opened to not loose responses
    function configureZigate($zgId) {
        cmdLog('debug', "configureZigate({$zgId})");

        $gtwType = isset($config['ab::gtwType'.$zgId]) ? $config['ab::gtwType'.$zgId] : 'zigate';
        if ($gtwType != 'zigate') {
            cmdLog('error', "  Gateway {$zgId} is NOT a Zigate");
            return;
        }

        // msgToCmd("CmdAbeille".$zgId."/0000/zgSoftReset", "");
        AbeilleCmdQueue::pushZigateCmd($zgId, PRIO_HIGH, "0011", "", "0000", null, 0);
        // Following cmds delayed by 1sec to wait for chip reset

        // Extended PAN ID change must be done BEFORE starting network
        // Note: STILL NOT FUNCTIONAL. Zigate always says "BUSY" !!! Network seems to be automatically started after soft reset even without 0024/Start cmd.
        // Note: Experiement was done for Livolo TI0001 case with channel 26 and specific EPANID
        // msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetExtendedPanId&tempo=".(time()+1), "extPanId=21758D1900481200");
        // AbeilleCmdQueue::pushZigateCmd($zgId, PRIO_HIGH, "0020", "21758D1900481200", "0000", null);

        global $config;
        $chan = isset($config['ab::gtwChan'.$zgId]) ? $config['ab::gtwChan'.$zgId] : 0; // Default = auto (all channels)
        if ($chan == 0)
            $mask = 0x7fff800; // All channels = auto
        else
            $mask = 1 << $chan;
        $mask = sprintf("%08X", $mask);
        cmdLog('debug', "  Settings chan ".$chan." (mask=".$mask.") for zigate ".$zgId);
        // msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetChannelMask&tempo=".(time()+1), "mask=".$mask);
        AbeilleCmdQueue::pushZigateCmd($zgId, PRIO_HIGH, "0021", $mask, "0000", null, 0);

        // msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetTimeServer&tempo=".(time()+1), "");
        $zgRef = mktime(0, 0, 0, 1, 1, 2000); // 2000-01-01 00:00:00
        $zgTime = time() - $zgRef;
        $data = sprintf("%08s", dechex($zgTime));
        AbeilleCmdQueue::pushZigateCmd($zgId, PRIO_HIGH, "0016", $data, "0000", null, 0);

        if (isset($config['ab::forceZigateHybridMode']) && ($config['ab::forceZigateHybridMode'] == "Y")) {
            $mode = "hybrid";
            $mode2 = "02";
        } else {
            $mode = "raw";
            $mode2 = "01";
        }
        cmdLog('debug', "  Configuring Zigate {$zgId} in {$mode} mode");
        // msgToCmd("TempoCmdAbeille".$zgId."/0000/zgSetMode&tempo=".(time()+1), "mode={$mode}");
        AbeilleCmdQueue::pushZigateCmd($zgId, PRIO_HIGH, "0002", $mode2, "0000", null, 0);

        // msgToCmd("TempoCmdAbeille".$zgId."/0000/zgStartNetwork&tempo=".(time()+10), "");
        AbeilleCmdQueue::pushZigateCmd($zgId, PRIO_HIGH, "0024", "", "0000", null, 0);

        msgToCmd("TempoCmdAbeille".$zgId."/0000/zgGetVersion&tempo=".(time()+10), "");
    }

    // Configure device
    // Returns: true=ok, false=error
    function configureDevice($msg) {
        $net = $msg['net'];
        $addr = $msg['addr'];
        cmdLog('debug', "  configureDevice({$net}, {$addr})");

        if (!isset($GLOBALS['devices'][$net][$addr])) {
            cmdLog('debug', "  configureDevice() ERROR: Unknown device");
            return false; // Unexpected request
        }

        $eq = $GLOBALS['devices'][$net][$addr];
        cmdLog('debug', "    eq=".json_encode($eq, JSON_UNESCAPED_SLASHES));

        // If modelSource/modelPath & modelName are passed, using it to get up-to-date cmds
        // - eqLogic might to be updated yet
        // - model may be different
        // - and configuration requires quick reaction during device announce
        if (isset($msg['modelSource']) && isset($msg['modelPath']) && isset($msg['modelName'])) {
            $model = getDeviceModel($msg['modelSource'], $msg['modelPath'], $msg['modelName']);
            if ($model !== false) {
                $eq['mainEp'] = isset($model['mainEP']) ? $model['mainEP'] : "01";
                $eq['commands'] = isset($model['commands']) ? $model['commands'] : [];
                if (isset($model['variables']))
                    $eq['variables'] = $model['variables'];
            }
        }

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

            $request = str_ireplace('#addrIEEE#', $eq['zigbee']['ieee'], $request);
            $request = str_ireplace('#IEEE#', $eq['zigbee']['ieee'], $request);
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
                        // log::add('Abeille', 'error', "getDeviceModel(): No closing dash (#) for cmd '{$cmdJName}'");
                        cmdLog('error', "      No closing dash (#)");
                        break;
                    }
                    $varLen = $varEnd - $varStart + 1;
                    $var = substr($request, $varStart, $varLen); // $var='#xxx#'
                    $varUp = strtoupper(substr($var, 1, -1)); // '#var#' => 'VAR' (no #)
                    cmdLog('debug', "      Start={$varStart}, End={$varEnd} => Size={$varLen}, var={$var}, varUp={$varUp}");
                    if (isset($eq['variables'][$varUp])) {
                        $varNew = $eq['variables'][$varUp];
                        cmdLog('debug', "      Replacing '{$var}' by '{$varNew}");
                        $request = str_ireplace($var, $varNew, $request);
                        $offset = $varStart + strlen($varNew);
                    } else
                        $offset = $varStart + $varLen;
                }
            }

            cmdLog('debug', '      Topic='.$topic.", Request='".$request."'");
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

    // Remove all pending messages for given zgId + addr or ieee.
    // Useful for ex when addr changed on device announce.
    function clearPending($zgId, $addr, $ieee) {
        cmdLog("debug", "  clearPending({$zgId}, {$addr}, {$ieee})");

        foreach ($GLOBALS['zigates'][$zgId]['cmdQueue'] as $pri => $q) {
            $count = count($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri]);
            cmdLog("debug", "  Pri={$pri}, Count=$count, Q=".json_encode($q));
            for ($cmdIdx = 0; $cmdIdx < $count; ) {
                $cmd = $GLOBALS['zigates'][$zgId]['cmdQueue'][$pri][$cmdIdx];
                if ($cmd['status'] != '') {
                    $cmdIdx++; // Already sent. Will be removed by ACK/NO-ACK
                    continue;
                }

                if (((strlen($cmd['addr']) == 4) && ($cmd['addr'] == $addr)) ||
                    ((strlen($cmd['addr']) == 16) && ($cmd['addr'] == $ieee))) {
                    array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri], $cmdIdx, 1);
                    // Note: cmd @cmdIdx is the next cmd after array_splice
                    cmdLog("debug", "  Removed Pri={$pri}/Idx={$cmdIdx}");
                    $count--;
                } else
                    $cmdIdx++;
            }

            // $count = count($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri]);
            // cmdLog("debug", "  Pri={$pri}, Count=$count, Q-AFTER=".json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri]));
        }

        // Cleaning tempo queue too
        global $tempoMessageQueue;
        $count = count($tempoMessageQueue);
        cmdLog("debug", "  Tempo count=$count BEFORE=".json_encode($tempoMessageQueue));
        for ($cmdIdx = 0; $cmdIdx < $count; ) {
            $cmd = $tempoMessageQueue[$cmdIdx];
            cmdLog("debug", "  cmdIdx={$cmdIdx}, cmd=".json_encode($cmd));
            /* Examples
               "topic":"CmdAbeille1/7D80/bind0030", "params":"addr=00124B002242C5C5&ep=01&clustId=0402&destAddr=00158D0001ED3365&destEp=01"
               "topic":"CmdAbeille1/7D80/configureReporting2","params":"ep=01&clustId=0402&attrType=29&attrId=0000&minInterval=600&maxInterval=900"
            */
            $paramsAddr = strstr($cmd['params'], "addr=");
            if ($paramsAddr === false) {
                // Address to be extracted from topic
                list($tmp, $cmdAddr, $tmp2) = explode("/", $cmd['topic']);
            } else {
                $paramsAddr = substr($paramsAddr, 5); // Skip 'addr='
                $cmdAddr = explode('&', $paramsAddr)[0];
            }
            // cmdLog("debug", "  cmdAddr={$cmdAddr}");
            if (((strlen($cmdAddr) == 4) && ($cmdAddr == $addr)) ||
                ((strlen($cmdAddr) == 16) && ($cmdAddr == $ieee))) {
                array_splice($tempoMessageQueue, $cmdIdx, 1);
                cmdLog("debug", "  Removed Tempo/Idx={$cmdIdx}");
                $count--;
            } else
                $cmdIdx++;
        }
        // $count = count($tempoMessageQueue);
        // cmdLog("debug", "  Tempo count=$count AFTER=".json_encode($tempoMessageQueue));
    }

    logSetConf("AbeilleCmd.log", true);
    logMessage('info', ">>> Démarrage d'AbeilleCmd");

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************

    $config = AbeilleTools::getConfig();
    $running = AbeilleTools::getRunningDaemons();
    $daemons = AbeilleTools::diffExpectedRunningDaemons($config, $running);
    logMessage('debug', 'Daemons status: '.json_encode($daemons));
    if ($daemons["cmd"] > 1){
        logMessage('error', "Un démon 'cmd' est déja lancé ! ".json_encode($daemons));
        exit(3);
    }

    declare(ticks = 1);
    pcntl_signal(SIGTERM, 'signalHandler', false);
    function signalHandler($signal) {
        logMessage('info', '<<< SIGTERM => Arret du démon AbeilleCmd');
        exit(0);
    }

    // Creating queues
    $queueXToAbeille        = msg_get_queue($abQueues["xToAbeille"]["id"]);
    if ($queueXToAbeille === false)
        logMessage("error", "msg_get_queue(xToAbeille) ERROR");
    $queueXToCmd            = msg_get_queue($abQueues["xToCmd"]["id"]);
    if ($queueXToCmd === false)
        logMessage("error", "msg_get_queue(xToCmd) ERROR");
    $queueParserToCmdAck    = msg_get_queue($abQueues["parserToCmdAck"]["id"]);
    if ($queueParserToCmdAck === false)
        logMessage("error", "msg_get_queue(ParserToCmdAck) ERROR");
    if (($queueXToAbeille === false) || ($queueXToCmd === false) || ($queueParserToCmdAck === false)) {
        logMessage('info', '<<< Pb de création de queues => Arret du démon AbeilleCmd');
        exit(4);
    }
    $queueXToCmdMax         = $abQueues["xToCmd"]["max"];
    $queueParserToCmdAckMax = $abQueues["parserToCmdAck"]["max"];
    $tempoMessageQueue = []; // Delayed commands to Zigate

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

    /* Init known devices list:
       $GLOBALS['devices'][net][addr]
          zigbee => []
              ieee => from device config
          mainEp => from model
          eqModel => []
            modelName =>
            modelSource =>
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

            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            $zigbee['ieee'] = $eqLogic->getConfiguration('IEEE', '');
            $zigbee['txStatus'] = $eqLogic->getStatus('ab::txAck', 'ok'); // Transmit status: 'ok' or 'noack'

            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            // cmdLog('debug', "    eqModel for '$eqLogicId'=".json_encode($eqModel, JSON_UNESCAPED_SLASHES));

            $eq = array(
                // 'ieee' => $eqLogic->getConfiguration('IEEE', ''),
                // 'rxOnWhenIdle' => (isset($zigbee['rxOnWhenIdle']) && ($zigbee['rxOnWhenIdle'] == 1)) ? true : false
                // 'txStatus' => $eqLogic->getStatus('ab::txAck', 'ok'), // Transmit status: 'ok' or 'noack'
                'zigbee' => $zigbee,

                // 'jsonId' => isset($eqModel['modelName']) ? $eqModel['modelName'] : '',
                // 'jsonLocation' => isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille',
                // 'modelPath' => isset($eqModel['modelPath']) ? $eqModel['modelPath'] : "<modName>/<modName>.json",
                'eqModel' => $eqModel,
                // 'variables' // Optional
            );
            if (isset($eq['eqModel']['modelName']) && ($eq['eqModel']['modelName'] != '')) {
                // if (isset($eqModel['modelPath']))
                //     $eq['modelPath'] = $eqModel['modelPath'];
                // else
                //     $eq['modelPath'] = $eq['eqModel']['modelName']."/".$eq['eqModel']['modelName'].".json";
                if (!isset($eq['eqModel']['modelPath']))
                    $eq['eqModel']['modelPath'] = "";

                // Read JSON to get list of commands to execute
                $model = getDeviceModel($eq['eqModel']['modelSource'], $eq['eqModel']['modelPath'], $eq['eqModel']['modelName']);
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

            // Check zigates status => Now part of processCmdQueues()
            // $AbeilleCmdQueue->checkZigatesStatus();

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

            // Free CPU time
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
