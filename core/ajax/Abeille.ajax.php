<?php

    /* This file is part of Plugin abeille for jeedom.
     *
     * Plugin abeille for jeedom is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * Plugin abeille for jeedom is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with Plugin abeille for jeedom. If not, see <http://www.gnu.org/licenses/>.
     */

    /*
     * Targets for AJAX's requests
     */

    /* Developers debug features */
    require_once __DIR__.'/../config/Abeille.config.php'; // dbgFile constant + queues
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    function logToFile($logFile = '', $logLevel = '', $msg = "") {
        if ($logLevel != '') {
            if (AbeilleTools::getNumberFromLevel($logLevel) > AbeilleTools::getLogLevel())
                return; // Nothing to do
            /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
            $pref = '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] ';
        } else
            $pref = '['.date('Y-m-d H:i:s').'] ';

        $logDir = __DIR__.'/../../../../log/';
        /* TODO: How to align logLevel width for better visual aspect ? */
        file_put_contents($logDir.$logFile, $pref.$msg."\n", FILE_APPEND);
    }

    /* Format then send msg to AbeilleCmd.
       Returns: true=ok, false=error */
    function sendToCmd($topic, $payload = '') {
        global $abQueues;

        $queueId = $abQueues["xToCmd"]['id'];
        $queue = msg_get_queue($queueId);

        $msg = array();
        $msg['topic']   = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg);

        if (msg_send($queue, 1, $msgJson, false, false) == false) {
            return false;
        }
        return true;
    }

    function sendToX($queueName, $msg) {
        global $abQueues;

        $queueId = $abQueues[$queueName]['id'];
        $queue = msg_get_queue($queueId);
        $msgJson = json_encode($msg);

        if (msg_send($queue, 1, $msgJson, false, false) == false) {
            return false;
        }
        return true;
    }

    // stop=true (default) => Disable daemons auto-start and stop them
    function pauseDaemons($stop = true, $dStatus = []) {
        if ($stop) {
            $dStatus['autoMode'] = config::byKey('deamonAutoMode', 'Abeille', 0);
            if ($dStatus['autoMode'] == 1) {
                logMessage('debug', 'Disabling daemons auto-startup');
                config::save('deamonAutoMode', 0, 'Abeille');
            }

            $daemons = AbeilleTools::getRunningDaemons2();
            /* 'daemons' reminder
            $daemons = array(
                'runningNb' => sizeof($daemons), // Nb of running daemons
                'runBits' => $runBits, // 1 bit per running daemon
                'daemons' => $daemons, // Detail on each daemon
            ); */
            $dStatus['running'] = ($daemons['runningNb'] > 0) ? true: false;
            Abeille::deamon_stop();
        } else {
            if ($dStatus['running']) // Daemons were running
                Abeille::deamon_start(); // Restarting daemons
            if ($dStatus['autoMode'] == 1) {
                logMessage('debug', 'Reenabling daemons auto-startup');
                config::save('deamonAutoMode', 1, 'Abeille');
            }
        }
        return $dStatus;
    }

    try {

        require_once __DIR__.'/../../../../core/php/core.inc.php';
        require_once __DIR__.'/../class/Abeille.class.php';
        require_once __DIR__.'/../php/AbeilleZigate.php';
        include_once __DIR__.'/../class/AbeilleTools.class.php'; // deamonlogFilter()/getRunningDaemons2()
        require_once __DIR__.'/../php/AbeilleInstall.php'; // checkIntegrity()
        include_once __DIR__.'/../php/AbeilleLog.php'; // logDebug()
        include_once __DIR__.'/../php/AbeilleGit.php'; // gitFetchAll()

        include_file('core', 'authentification', 'php');
        if (!isConnect('admin')) {
            throw new Exception('401 Unauthorized');
        }

        ajax::init();

        logDebug('Abeille.ajax.php: action='.init('action'));

        /* For Wifi Zigate
        - check 'Addr:Port' via ping
        - check socat installation
        */
        if (init('action') == 'checkWifi') {
            $zgPort = init('zgport'); // Addr:Port
            $zgSSP = init('ssp'); // Socat serial port

            /* TODO: Log old issue. Why the following message never gets out ? */
            logToFile('AbeilleConfig.log', 'debug', 'Arret des démons');
            abeille::deamon_stop(); // Stopping daemons

            /* Checks addr is responding to ping and socat is installed. */
            $cmdToExec = "checkWifi.sh ".$zgPort;
            $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);
            // $status = 0;

            /* TODO */
            /* Need 'AbeilleSocat' daemon to interrogate Wifi zigate */
            // $nohup = "/usr/bin/nohup";
            // $php = "/usr/bin/php";
            // $dir = __DIR__."/../class/";
            // log::add('AbeilleConfig.log', 'debug', 'Démarrage d\'un démon socat temporaire');
            // $params = $zgSSP.' '.log::convertLogLevel(log::getLogLevel('Abeille')).' '.$zgPort;
            // $log = " >>".log::getPathToLog('AbeilleConfig.log')." 2>&1";
            // $cmd = $nohup." ".$php." ".$dir."AbeilleSocat.php"." ".$params.$log;
            // exec("echo ".$cmd." >>".log::getPathToLog('AbeilleTOTO'));
            // log::add('AbeilleConfig.log', 'debug', '  cmd='.$cmd);
            // exec($cmd.' &');

            /* Read Zigate FW version */
            // $version = 0; // FW version
            // if ($status == 0) {
                // zg_SetLog("AbeilleConfig");
                // $status = zgGetVersion($zgSSP, $version);
            // }

            logToFile('AbeilleConfig.log', 'debug', 'Redémarrage des démons');
            abeille::deamon_start(); // Restarting daemons

            ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
        }

        if (init('action') == 'checkSocat') {
            $prefix = logGetPrefix(""); // Get log prefix
            $cmd = '/bin/bash '.__DIR__."/../scripts/checkSocat.sh | sed -e 's/^/".$prefix."/' >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);
            ajax::success(json_encode($status));
        }

        if (init('action') == 'checkWiringPi') {
            $prefix = logGetPrefix(""); // Get log prefix
            $cmd = '/bin/bash '.__DIR__."/../scripts/checkWiringPi.sh | sed -e 's/^/".$prefix."/' >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);
            ajax::success(json_encode($status));
        }

        // OBSOLETE: installWiringPi => installPackage + package=WiringPi
        // if (init('action') == 'installWiringPi') {
        //     $cmd = '/bin/bash '.__DIR__.'/../scripts/installWiringPi.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        //     exec($cmd, $out, $status);
        //     ajax::success();
        // }

        // OBSOLETE: installPiGpio => installPackage + package=PiGpio
        // if (init('action') == 'installPiGpio') {
        //     $cmd = '/bin/bash '.__DIR__.'/../scripts/installPiGpio.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        //     exec($cmd, $out, $status);
        //     ajax::success();
        // }

        // OBSOLETE: installSocat => installPackage + package=socat
        // if (init('action') == 'installSocat') {
        //     $cmd = '/bin/bash '.__DIR__.'/../scripts/installSocat.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        //     exec($cmd, $out, $status);
        //     ajax::success(json_encode($status));
        // }

        // Package installation
        // Supported packages: WiringPi, PiGpio, socat
        if (init('action') == 'installPackage') {
            $package = init('package');

            $cmd = '/bin/bash '.__DIR__."/../scripts/installPackage.sh ${package} >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);
            ajax::success();
        }

        if (init('action') == 'checkPiGpio') {
            $prefix = logGetPrefix(""); // Get log prefix
            $cmd = 'python3 '.__DIR__."/../scripts/checkPiGpio.py | sed -e 's/^/".$prefix."/' >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);
            ajax::success(json_encode($status));
        }

        if (init('action') == 'checkTTY') {
            $zgPort = init('zgport');
            $zgType = init('zgtype');
            $zgGpioLib = init("zgGpioLib");

            logSetConf('AbeilleConfig.log', true);
            logMessage('info', 'Test de communication avec la Zigate; type='.$zgType.', port='.$zgPort.', GpioLib='.$zgGpioLib);

            $dStatus = pauseDaemons(true); // Stop daemons if running

            /* Checks port exists and is not already used */
            $prefix = logGetPrefix(""); // Get log prefix
            $cmdToExec = "checkTTY.sh ".$zgPort." ".$zgType." ".$zgGpioLib.' "'.$prefix.'"';
            $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec." >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);

            /* Read Zigate FW version */
            $version = 0; // FW version
            if ($status == 0) {
                $status = zgGetVersion($zgPort, $version);
            }

            pauseDaemons(false, $dStatus); // Restart daemons if were running

            ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
        }

        if (init('action') == 'installTTY') {
            $cmd = '/bin/bash '.__DIR__.'/../scripts/installTTY.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);
            ajax::success();
        }

        /* Update FW but check parameters first, prior to shutdown daemon */
        if (init('action') == 'updateFirmware') {
            $zgType = init('zgType'); // "PI"/"PIv2" or "DIN"
            $zgPort = init('zgPort');
            $zgGpioLib = init('zgGpioLib');
            $zgFwFile = init('fwFile');
            $erasePdm = init('erasePdm');
            $zgId = init('zgId');

            logSetConf('AbeilleConfig.log', true);
            logMessage('debug', "updateFirmware(${zgPort}, ${zgType}, ${zgGpioLib}, ${zgFwFile})");

            // if (($zgType == "PI") || ($zgType == "PIv2"))
                $script = "updateZigate.sh";
            // else
            //     $script = "updateFirmwareDIN.sh";

            logMessage('debug', 'Checking parameters');
            $cmdToExec = $script." check ${zgPort} ${zgType} ${zgGpioLib} ${zgFwFile}";
            $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);

            $version = 0; // FW version
            if ($status == 0) {
                $dStatus = pauseDaemons(true); // Stop daemons if running

                /* Updating FW and reset Zigate */
                $cmdToExec = $script." flash ${zgPort} ${zgType} ${zgGpioLib} ${zgFwFile}";
                $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
                exec($cmd, $out, $status);

                /* Reading FW version */
                // Tcharp38 note: removed this
                // When restarting Zigate, several messages exchanges before being able to ask for version
                // To be revisited
                // if ($status == 0) {
                //     $status = zgGetVersion($zgPort, $version);
                // }

                pauseDaemons(false, $dStatus); // Restart daemons if were running

                if (($status == 0) && $erasePdm) {
                    logMessage('info', 'Effacement de la PDM');
                    if (sendToCmd('TempoCmdAbeille'.$zgId.'/0000/zgErasePdm&time='.(time()+2), '') == false) {
                        $status = -1;
                        $error = "Can't send erase PDM request";
                    }
                }
            }

            ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
        }

        if (init('action') == 'resetPiZigate') {
            $prefix = logGetPrefix(""); // Get log prefix
            $cmd = "/bin/bash ".__DIR__."/../scripts/resetPiZigate.sh | sed -e 's/^/".$prefix."/' >>".log::getPathToLog('AbeilleConfig.log')." 2>&1";
            exec($cmd, $out, $status);
            ajax::success();
        }

        /* Devloper mode: Switch GIT branch */
        if (init('action') == 'switchBranch') {
            $branch = init('branch');
            $updateOnly = init('updateOnly'); // TODO: No longer required

            logSetConf('AbeilleConfig.log', true);

            $status = 0;
            $prefix = logGetPrefix(""); // Get log prefix

            /* Creating temp dir */
            $tmpDir = jeedom::getTmpFolder("Abeille");
            $doneFile = $tmpDir.'/switchBranch.done';
            if (file_exists($tmpDir) == false)
                mkdir($tmpDir);
            else if (file_exists($doneFile))
                unlink($doneFile); // Removing 'switchBranch.done' file

            /* Creating a copy of 'switchBranch.sh' in 'tmp' */
            $cmd = 'cd '.__DIR__.'/../scripts/; sudo cp -p switchBranch.sh ../../tmp/switchBranch.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd);

            gitFetchAll(0);

            $dStatus = pauseDaemons(true); // Stop daemons if running

            $cmdToExec = "switchBranch.sh ".$branch.' "'.$prefix.'"';
            // $cmd = 'nohup /bin/bash '.__DIR__.'/../../tmp/'.$cmdToExec." >>".log::getPathToLog('AbeilleConfig.log').' 2>&1 &';
            $cmd = '/bin/bash '.__DIR__.'/../../tmp/'.$cmdToExec." >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd);

            // TODO: There is something to be revisited there
            // switch done in backbround but daemons are restarted BEFORE switch ended.
            // TODO: Wait for switch end ?

            pauseDaemons(false, $dStatus); // Restart daemons if were running

            /* Note: Returning immediately but switch not completed yet. Anyway server side code
            might be completely different after switch */
            ajax::success(json_encode(array('status' => $status)));
        }

        // /* Developer feature: Remove equipment(s) listed by id in 'eqList', from Jeedom DB.
        // Zigate is untouched.
        // Returns: status=0/-1, errors=<error message(s)> */
        // if (init('action') == 'removeEqJeedom') {
        //     $eqList = init('eqList');

        //     $status = 0;
        //     $errors = ""; // Error messages
        //     foreach ($eqList as $eqId) {
        //         /* Collecting required infos */
        //         $eqLogic = eqLogic::byId($eqId);
        //         if (!is_object($eqLogic)) {
        //             throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__).' '.$eqId);
        //         }

        //         /* Removing device from Jeedom DB */
        //         $eqLogic->remove();
        //     }

        //     ajax::success(json_encode(array('status' => $status, 'errors' => $errors)));
        // }

        /* Remove equipment(s) from zigbee listed by id in 'eqIdList'.
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'removeEqZigbee') {
            $eqIdList = init('eqIdList');

            $status = 0;
            $errors = ""; // Error messages
            $missingParentIEEE = false;
            foreach ($eqIdList as $eqId) {
                /* Collecting required infos (zgId, parentIEEE & deviceIEEE) */
                $eqLogic = eqLogic::byId($eqId);
                if (!is_object($eqLogic)) {
                    throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__).' '.$eqId);
                }
                $eqLogicId = $eqLogic->getLogicalid();
                $eqName = $eqLogic->getName();
                list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
                $zgId = substr($eqNet, 7); // Extracting zigate id from network name (AbeilleX)
                $eqIEEE = $eqLogic->getConfiguration('IEEE', '');
                $parentIEEE = $eqLogic->getConfiguration('parentIEEE', '');
                // Tcharp38: parentIEEE not required with 004C command
                // if (($eqIEEE == "") || ($parentIEEE == "")) {
                if (($eqIEEE == "")) {
                        /* Can't do it. Missing info */
                    $status = -1;
                    if ($eqIEEE == "")
                        $errors .= "L'équipement '".$eqName."' n'a pas d'adresse IEEE\n";
                    else {
                        $errors .= "L'équipement '".$eqName."' n'a pas l'adresse IEEE de son parent\n";
                        $missingParentIEEE = true;
                    }
                    continue;
                }

                /* Sending msg to 'AbeilleCmd' */
                if (sendToCmd('CmdAbeille'.$zgId.'/0000/LeaveRequest', "IEEE=".$eqIEEE) == false) {
                    $status = -1;
                    $errors = "Impossible d'envoyer la demande de quitter le réseau";
                }
            }

            if ($missingParentIEEE)
                $errors .= "Forcer l'interrogation du réseau pour fixer le problème.";
            ajax::success(json_encode(array('status' => $status, 'errors' => $errors)));
        }

        /* Check plugin integrity. This assumes that "Abeille.md5" is present and up-to-date.
           Returns: status; 0=OK, -1=no MD5, -2=checksum error */
        if (init('action') == 'checkIntegrity') {
            $status = checkIntegrity();
            $error = "";
            if ($status == -1)
                $error = "No MD5 file";
            else
                $error = "Checksum error";

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Cleanup plugin repository based on "Abeille.md5" listed files.
           Returns: status; 0=OK, -1=ERROR */
        if (init('action') == 'doPostUpdateCleanup') {
            if (validMd5Exists() != 0) {
                ajax::success(json_encode(array('status' => -1, 'error' => "No valid MD5 file")));
            }
            $status = doPostUpdateCleanup();
            $error = "";

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Monitor equipment with given ID.
        Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'monitor') {
            $eqId = init('eqId');

            logSetConf("AbeilleMonitor.log", true);
            // logMessage("debug", "AbeilleDev.ajax: action==monitor, eqId=".$eqId);

            $status = 0;
            $error = "";

            $curMonId = config::byKey('ab::monitorId', 'Abeille', false);
            if ($eqId !== $curMonId) {
                /* Saving ID of device to monitor */
                config::save('ab::monitorId', $eqId, 'Abeille');

                /* Need to start AbeilleMonitor if not already running
                and restart cmd & parser.
                WARNING: If cron is not running, any (re)start should be avoided. */
                $conf = AbeilleTools::getConfig();
                AbeilleTools::restartDaemons($conf, "AbeilleMonitor AbeilleParser AbeilleCmd");
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Migrate device to another one. */
        // * https://github.com/KiwiHC16/Abeille/issues/1771
        // *
        // * 1/ Changement logical Id
        // * 2/ Remove zigbee reseau 1 zigbee
        // * 3/ inclusion normale sur le reseau 2 zigbee
        // *
        // * Faire un bouton qui fait les etapes 1/ et 2 puis demander à l'utilisateur de faire l'étape 3
        if (init('action') == 'migrate') {
            $eqId = init('eqId');
            $dstZgId = init('dstZgId');

            $status = 0;
            $error = "";

            $eqLogic = Abeille::byId($eqId);
            if (!is_object($eqLogic)) {
                $status = -1;
                $error = "Unknown eqId ".$eqId;
            } else {
                $ieee = $eqLogic->getConfiguration('IEEE', 'none');
                if ($ieee == 'none') {
                    $status = -1;
                    $error = "Device ".$eqId." DOES NOT have IEEE address";
                } else {
                    list($net, $addr) = explode('/', $eqLogic->getLogicalId());
                    $zgId = substr($net, 7); // AbeilleX => X

                    // $newNet = "Abeille".$dstZgId;

                    // Moving eq to new network
                    // $eqLogic->setLogicalId($newNet.'/'.$addr);
                    // $eqLogic->save();

                    // Excluding device from current network
                    // self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$newDestBee."/0000/Remove", "ParentAddressIEEE=".$IEEE."&ChildAddressIEEE=".$IEEE );
                    /* Sending msg to 'AbeilleCmd' */
                    $topic   = 'CmdAbeille'.$dstZgId.'/0000/zgSetPermitMode';
                    $payload = "mode=start";
                    if (sendToCmd($topic, $payload) == false) {
                        $errors = "Could not send msg to 'xToCmd': topic=".$topic;
                        $status = -1;
                    }
                    if ($status == 0) {
                        $topic   = 'CmdAbeille'.$zgId.'/0000/LeaveRequest';
                        $payload = "IEEE=".$ieee."&Rejoin=00";
                        if (sendToCmd($topic, $payload) == false) {
                            $errors = "Could not send msg to 'xToCmd': topic=".$topic;
                            $status = -1;
                        }
                    }

                    // 3/ inclusion normale sur le reseau 2 zigbee
                    // message::add("Abeille", "Je viens de préparer la migration de ".$eqLogic->getHumanName(). ". Veuillez faire maintenant son inclusion dans la zigate ".$dstZgId);
                }
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /*  */
        if (init('action') == 'acceptNewZigate') {
            $zgId = init('zgId');

            $status = 0;
            $error = "";

            config::remove("ab::zgIeeeAddrOk".$zgId, 'Abeille');
            config::remove("ab::zgIeeeAddr".$zgId, 'Abeille');

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /**
         * replaceEq()
         *
         * @param deadEqId: Id of the bee to be removed
         * @param newEqId: Id of the bee which will replace the ghost and receive all informations
         *
         * https://github.com/KiwiHC16/Abeille/issues/1055
         */
        if (init('action') == 'replaceEq') {
            $deadEqId = init('deadEqId');
            $newEqId = init('newEqId');

            $status = 0;
            $error = "";

            $deadEq = Abeille::byId($deadEqId);
            if (!is_object($deadEq)) {
                $error = "Equipement HS inconnu (id ".$deadId.")";
                $status = -1;
            }
            if ($status == 0) {
                $newEq = Abeille::byId($newEqId);
                if (!is_object($newEq)) {
                    $error = "Nouvel equipement inconnu (id ".$newId.")";
                    $status = -1;
                }
            }

            if ($status == 0) {
                list($deadNet, $deadAddr) = explode('/', $deadEq->getLogicalId());
                list($newNet, $newAddr) = explode('/', $newEq->getLogicalId());

                $oldIeee = $deadEq->getConfiguration('IEEE', '');
                if ($oldIeee != '') {
                    log::add('Abeille', 'debug', 'Je retire '.$deadEq->getName().' de la zigate.');
                    sendToCmd("Cmd".$deadNet."/0000/Remove", "ParentAddressIEEE=".$oldIeee."&ChildAddressIEEE=".$oldIeee);
                }

                // Collecting ieee & short addr from new device to assign them to old one
                $newIeee = $newEq->getConfiguration('IEEE', '');
                $deadEq->setConfiguration('IEEE', $newIeee);
                $deadEq->setLogicalId($newEq->getLogicalId());

                $newEq->remove();
                $deadEq->save();
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        // Get 'eqLogic' content for given 'eqId'
        if (init('action') == 'getEq') {
            $eqId = init('eqId');

            $status = 0;
            $error = "";
            $eq = [];

            $eqLogic = eqLogic::byId($eqId);
            if (!is_object($eqLogic)) {
                $error = "Equipement inconnu (id ".$eqId.")";
                $status = -1;
            } else {
                $eq['id'] = $eqId;
                $eq['name'] = $eqLogic->getName();
                $eq['logicId'] = $eqLogic->getLogicalId();
                list($eqNet, $eqAddr) = explode("/", $eq['logicId']);
                $eq['zgId'] = substr($eqNet, 7);
                $eq['zgType'] = config::byKey('ab::gtwSubType'.$eq['zgId'], 'Abeille', '', 1);
                $eq['zgChan'] = config::byKey('ab::gtwChan'.$eq['zgId'], 'Abeille', 0, 1); // 0=auto
                $eq['addr'] = $eqAddr;
                $eq['ieee'] = $eqLogic->getConfiguration('IEEE', '');
                $eq['defaultEp'] = $eqLogic->getConfiguration('mainEP', '');
                $eq['batteryType'] = $eqLogic->getConfiguration('battery_type', '');

                // OBSOLETE: Moved to 'ab::zigbee'
                // $sig = $eqLogic->getConfiguration('ab::signature', []);
                // $eq['zbModel'] = isset($sig['modelId']) ? $sig['modelId'] : '';
                // $eq['zbManuf'] = isset($sig['manufId']) ? $sig['manufId'] : '';

                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                // logDebug('  eqModel='.json_encode($eqModel));
                // $eq['modelSig'] = isset($eqModel['modelSig']) ? $eqModel['modelSig'] : ''; // TO BE REMOVED
                // $eq['modelName'] = isset($eqModel['modelName']) ? $eqModel['modelName'] : ''; // TO BE REMOVED
                // $eq['modelSource'] = isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille'; // TO BE REMOVED
                // $eq['modelType'] = isset($eqModel['type']) ? $eqModel['type'] : ''; // TO BE REMOVED
                // $eq['modelForced'] = isset($eqModel['modelForced']) ? $eqModel['modelForced'] : false; // TO BE REMOVED
                $eq['model'] = array(
                    // Model infos
                    'modelSig' => isset($eqModel['modelSig']) ? $eqModel['modelSig'] : '',
                    'modelName' => isset($eqModel['modelName']) ? $eqModel['modelName'] : '',
                    'modelSource' => isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille',
                    'modelForced' => isset($eqModel['modelForced']) ? $eqModel['modelForced'] : false,

                    // Equipment infos
                    'manuf' => isset($eqModel['manuf']) ? $eqModel['manuf'] : '',
                    'model' => isset($eqModel['model']) ? $eqModel['model'] : '',
                    'type' => isset($eqModel['type']) ? $eqModel['type'] : '',
                    // 'variables' => optional
                );
                if (isset($eqModel['variables']))
                    $eq['model']['variables'] = $eqModel['variables'];

                $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                $eq['zigbee'] = $zigbee;
                $eq['zbModel'] = isset($zigbee['modelId']) ? $zigbee['modelId'] : '';
                $eq['zbManuf'] = isset($zigbee['manufId']) ? $zigbee['manufId'] : '';

                $eq['paramType'] = $eqLogic->getConfiguration('paramType', "");

                $jCmds = Cmd::byEqLogicId($eqId);
                $eq['cmds'] = [];
                foreach ($jCmds as $cmdLogic) {
                    $cmdId = $cmdLogic->getId();
                    $cmdLogicId = $cmdLogic->getLogicalId('');
                    if ($cmdLogic->getType() == 'info')
                        $cmdVal = $cmdLogic->execCmd();
                    else
                        $cmdVal = '';
                    $eq['cmds'][$cmdLogicId] = array(
                        'id' => $cmdId,
                        'val' => $cmdVal
                    );
                }
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'eq' => $eq)));
        }

        // Get equipments list
        if (init('action') == 'getEqList') {
            $gtwId = init('gtwId', 0); // Default == all networks

            $status = 0;
            $error = "";
            $eqList = [];

            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $eqLogicId = $eqLogic->getLogicalId();
                list($eqNet, $eqAddr) = explode("/", $eqLogicId);
                if ($gtwId != 0) {
                    $i = substr($eqNet, 7); // AbeilleX => X
                    if ($i != $gtwId)
                        continue; // Not under the requested network
                }

                // Trying to identify zigbee device type
                $zbType = "";
                $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                if (isset($zigbee['logicalType'])) {
                    switch($zigbee['logicalType']) {
                    case 0: $zbType = "Coordinator"; break;
                    case 1: $zbType = "Router"; break;
                    case 2: $zbType = "End Device"; break;
                    default: break;
                    }
                } else if (isset($zigbee['macCapa'])) {
                    $mc = hexdec($zigbee['macCapa']);
                    $devType = ($mc >> 1) & 0b1;
                    if ($devType) {
                        if ($eqAddr == "0000")
                            $zbType = "Coordinator";
                        else
                        $zbType = "Router";
                    } else
                        $zbType = "End Device";
                }

                $eqParent = $eqLogic->getObject();
                if (!is_object($eqParent))
                    $objName = "";
                else
                    $objName = $eqParent->getName();

                $settings = $eqLogic->getConfiguration('ab::settings', []);

                $eq = array(
                    'net' => $eqNet,
                    'addr' => $eqAddr,
                    'name' => $eqLogic->getName(),
                    'id' => $eqLogic->getId(),
                    'logicId' => $eqLogicId,
                    'objectName' => $objName,
                    'enabled' => $eqLogic->getIsEnable(),
                    'zbType' => $zbType,
                    'icon' => $eqLogic->getConfiguration('ab::icon', 'defaultUnknown'),
                    'x' => isset($settings['physLocationX']) ? $settings['physLocationX'] : 0,
                    'y' => isset($settings['physLocationY']) ? $settings['physLocationY'] : 0,
                    'z' => isset($settings['physLocationZ']) ? $settings['physLocationZ'] : 0, // Level
                );
                $eqList[$eqLogicId] = $eq;
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'eqList' => $eqList)));
        }

        // Retrieves all informations suitable to fill health page
        if (init('action') == 'getHealthDatas') {
            $status = 0;
            $error = "";
            $eq = [];

            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $eqLogicId = $eqLogic->getLogicalId();
                list($eqNet, $eqAddr) = explode("/", $eqLogicId);
                if (($eqNet == "") || ($eqAddr == ""))
                    continue; // Invalid

                $e = [];
                $e['jId'] = $eqLogic->getId();
                $e['link'] = $eqLogic->getLinkToConfiguration();
                $e['hName'] = $eqLogic->getHumanName(true);
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                $e['type'] = isset($eqModel['type']) ? $eqModel['type'] : '?';
                $e['ieee'] = $eqLogic->getConfiguration('IEEE', '');
                $e['isEnabled'] = $eqLogic->getIsEnable();
                $e['timeout'] = $eqLogic->getStatus('timeout');
                $e['lastComm'] = $eqLogic->getStatus('lastCommunication');
                if (substr($eqAddr, 0, 2) == "rc") // Remote control ?
                    $e['since'] = '-';
                else
                    $e['since'] = floor((time() - strtotime($e['lastComm'])) / 3600);
                $e['txAck'] = $eqLogic->getStatus('ab::txAck', 'ok');

                // Last LQI
                if ($eqAddr == "0000")
                    $lastLqi = "-";
                else {
                    $lqiCmd = $eqLogic->getCmd('info', 'Link-Quality');
                    if (is_object($lqiCmd))
                        $lastLqi = $lqiCmd->execCmd();
                    else
                        $lastLqi = "?";
                }
                $e['lastLqi'] = $lastLqi;

                // Last battery status
                $bat = $eqLogic->getStatus('battery', '');
                if ($bat == '')
                    $bat = '-';
                else
                    $bat = $bat.'%';
                $e['lastBat'] = $bat;

                // Gateway
                $gtwId = substr($eqNet, 7); // AbeilleX => X
                $gtw = eqLogic::byLogicalId('Abeille'.$gtwId.'/0000', 'Abeille');
                $e['gtwTimeout'] = $gtw->getStatus('timeout');
                $e['gtwType'] = config::byKey('ab::gtwType'.$gtwId, 'Abeille', 'zigate');

                if (!isset($eq[$eqNet]))
                    $eq[$eqNet] = [];
                if (!isset($eq[$eqNet][$eqAddr]))
                    $eq[$eqNet][$eqAddr] = $e;
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'equipments' => $eq)));
        } // End getHealthDatas

        // Read 'ab::settings' content
        // Params: eqId = Equipment Jeedom ID
        if (init('action') == 'getSettings') {
            $status = 0;
            $error = "";

            $eqId = init('eqId');
            $eqLogic = Abeille::byId($eqId);
            $settings = [];
            if (!is_object($eqLogic)) {
                $error = "Invalid device ID ".$eqId;
                $status = -1;
            } else
                $settings = $eqLogic->getConfiguration('ab::settings', []);

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'settings' => $settings)));
        }

        // TODO: OBSOLETE !!! Use 'saveEqConfig' instead, more generic
        // Change eqLogic 'ab::settings' content
        // Param: eqId = Equipment Jeedom ID
        // Param: settings = associative array of settings to change
        if (init('action') == 'saveSettings') {
            $status = 0;
            $error = "";

            $eqId = init('eqId');
            $newSettings = init('settings');
            $newSettings = json_decode($newSettings, true);

            $eqLogic = Abeille::byId($eqId);
            if (!is_object($eqLogic)) {
                $error = "Invalid device ID ".$eqId;
                $status = -1;
            } else {
                $settings = $eqLogic->getConfiguration('ab::settings', []);
                foreach ($newSettings as $setKey => $setVal) {
                    $settings[$setKey] = $setVal;
                    logDebug('  settings['.$setKey.']='.$setVal);
                }
                $eqLogic->setConfiguration('ab::settings', $settings);
                $eqLogic->save();
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        // Change eqLogic 'configuration' content
        // Param: eqId = Equipment Jeedom ID
        // Param: eqConfKey = eqLogic->configuration field to update (ex: 'ab::eqModel', 'ab::settings')
        // Param: eqConfVal = value for eqLogic->configuration->eqConfKey (note: currently only ASSOCIATIVE ARRAY)
        if (init('action') == 'saveEqConfig') {
            $status = 0;
            $error = "";

            $eqId = init('eqId');
            $eqConfKey = init('eqConfKey'); // ex: 'ab::eqModel', 'ab::settings'
            $eqConfVal = init('eqConfVal');
            $newArr = json_decode($eqConfVal, true); // currently only ASSOCIATIVE ARRAY

            $eqLogic = Abeille::byId($eqId);
            if (!is_object($eqLogic)) {
                $error = "Invalid device ID ".$eqId;
                $status = -1;
            } else {
                $curArr = $eqLogic->getConfiguration($eqConfKey, []);
                foreach ($newArr as $setKey => $setVal) {
                    $curArr[$setKey] = $setVal;
                    logDebug("  ${eqConfKey}[${setKey}]=".json_encode($setVal, JSON_UNESCAPED_SLASHES));
                }
                $eqLogic->setConfiguration($eqConfKey, $curArr);
                $eqLogic->save();
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        // Change 'config' DB content based on given keys
        if (init('action') == 'saveConfig') {
            $status = 0;
            $error = "";

            $config = init('config');
            logDebug('config='.$config);

            $config = json_decode($config, true);
            foreach ($config as $key => $value) {
                config::save($key, $value, 'Abeille');
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        // Returns 'config' DB content
        if (init('action') == 'getConfig') {
            $status = 0;
            $error = "";

            $config = AbeilleTools::getConfig();
            $configJson = json_encode($config);
            logDebug('configJson='.$configJson);

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'config' => $configJson)));
        }

        // Restart Abeille's daemons
        if (init('action') == 'restartDaemons') {
            $status = 0;
            $error = "";

            $config = AbeilleTools::getConfig();
            AbeilleTools::restartDaemons($config, "");

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        // Return Abeille's running daemons
        if (init('action') == 'getRunningDaemons') {
            $status = 0;
            $error = "";

            $daemons = AbeilleTools::getRunningDaemons2();
            /* 'daemons' reminder
            $daemons = array(
                'runningNb' => sizeof($daemons), // Nb of running daemons
                'runBits' => $runBits, // 1 bit per running daemon
                'daemons' => $daemons, // Detail on each daemon
            ); */

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'daemons' => $daemons)));
        }

        // Log level may have changed. Informing daemons
        if (init('action') == 'logLevelChanged') {
            $jLevel = init('level'); // Jeedom level (100, 200, 400...)
            $aLevel = logJtoA($jLevel); // Convert to Abeille level (0 to 4)

            $status = 0;
            $error = "";

            $msg = array(
                'type' => 'logLevelChanged',
                'level' => $aLevel
            );
            sendToX('xToParser', $msg);
            sendToX('xToCmd', $msg);
            sendToX('xToAbeille', $msg);

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
            Instead 'success' callback is used. This means that
            - take care of error code returned
            - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'Abeille.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
