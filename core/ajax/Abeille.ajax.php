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
    require_once __DIR__.'/../config/Abeille.config.php'; // dbgFile constant
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    function logToFile($logFile = '', $logLevel = '', $msg = "")
    {
        if ($logLevel != '') {
            if (AbeilleTools::getNumberFromLevel($logLevel) > AbeilleTools::getPluginLogLevel('Abeille'))
                return; // Nothing to do
            /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
            $pref = '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] ';
        } else
            $pref = '['.date('Y-m-d H:i:s').'] ';

        $logDir = __DIR__.'/../../../../log/';
        /* TODO: How to align logLevel width for better visual aspect ? */
        file_put_contents($logDir.$logFile, $pref.$msg."\n", FILE_APPEND);
    }

try {

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/../class/Abeille.class.php';
    require_once __DIR__.'/../php/AbeilleZigate.php';
    include_once __DIR__.'/../class/AbeilleTools.class.php'; // deamonlogFilter()
    require_once __DIR__.'/../php/AbeilleInstall.php'; // checkIntegrity()
    include_once __DIR__.'/../php/AbeilleLog.php'; // logDebug()

    include_file('core', 'authentification', 'php');
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }

    ajax::init();

    // logDebug('Abeille.ajax.php: action='.init('action'));

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

    if (init('action') == 'installSocat') {
        $cmd = '/bin/bash '.__DIR__.'/../scripts/installSocat.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success(json_encode($status));
    }

    if (init('action') == 'checkWiringPi') {
        $prefix = logGetPrefix(""); // Get log prefix
        $cmd = '/bin/bash '.__DIR__."/../scripts/checkWiringPi.sh | sed -e 's/^/".$prefix."/' >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success(json_encode($status));
    }

    if (init('action') == 'installWiringPi') {
        $cmd = '/bin/bash '.__DIR__.'/../scripts/installWiringPi.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success();
    }

    if (init('action') == 'checkTTY') {
        $zgPort = init('zgport');
        $zgType = init('zgtype');

        logSetConf('AbeilleConfig.log', true);
        logMessage('info', 'Test de communication avec la Zigate; type='.$zgType.', port='.$zgPort);

        logMessage('debug', 'Arret des démons');
        abeille::deamon_stop(); // Stopping daemon

        /* Checks port exists and is not already used */
        $prefix = logGetPrefix(""); // Get log prefix
        $cmdToExec = "checkTTY.sh ".$zgPort." ".$zgType.' "'.$prefix.'"';
        $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec." >>".log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);

        /* Read Zigate FW version */
        $version = 0; // FW version
        if ($status == 0) {
            $status = zgGetVersion($zgPort, $version);
        }

        logMessage('debug', 'Redémarrage des démons');
        abeille::deamon_start(); // Restarting daemon

        ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
    }

    if (init('action') == 'installTTY') {
        $cmd = '/bin/bash '.__DIR__.'/../scripts/installTTY.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success();
    }

    /* Update FW but check parameters first, prior to shutdown daemon */
    if (init('action') == 'updateFirmware') {
        $zgType = init('zgtype'); // "PI" or "DIN"
        $zgPort = init('zgport');
        $zgFwFile = init('fwfile');

        logSetConf('AbeilleConfig.log', true);
        logMessage('debug', 'Démarrage updateFirmware('.$zgType.', '.$zgFwFile.', '.$zgPort.')');

        if ($zgType == "PI")
            $script = "updateFirmware.sh";
        else
            $script = "updateFirmwareDIN.sh";

        logMessage('debug', 'Vérification des paramètres');
        $cmdToExec = $script." check ".$zgPort;
        $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);

        $version = 0; // FW version
        if ($status == 0) {
            logMessage('info', 'Arret des démons');
            abeille::deamon_stop(); // Stopping daemon

            /* Updating FW and reset Zigate */
            $cmdToExec = $script." flash ".$zgPort." ".$zgFwFile;
            $cmd = '/bin/bash '.__DIR__.'/../scripts/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
            exec($cmd, $out, $status);

            /* Reading FW version */
            // Tcharp38 note: removed this
            // When restarting Zigate, several messages exchanges before being able to ask for version
            // To be revisited
            // if ($status == 0) {
            //     $status = zgGetVersion($zgPort, $version);
            // }

            logMessage('info', 'Redémarrage des démons');
            abeille::deamon_start(); // Restarting daemon
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
        $tmp = __DIR__.'/../../tmp';
        $doneFile = $tmp.'/switchBranch.done';
        if (file_exists($tmp) == FALSE)
            mkdir($tmp);
        else if (file_exists($doneFile))
            unlink($doneFile); // Removing 'switchBranch.done' file

        /* Creating a copy of 'switchBranch.sh' in 'tmp' */
        $cmd = 'cd '.__DIR__.'/../scripts/; sudo cp -p switchBranch.sh ../../tmp/switchBranch.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd);

        logMessage('debug', 'Arret des démons');
        abeille::deamon_stop(); // Stopping daemon

        $cmdToExec = "switchBranch.sh ".$branch.' "'.$prefix.'"';
        $cmd = 'nohup /bin/bash '.__DIR__.'/../../tmp/'.$cmdToExec." >>".log::getPathToLog('AbeilleConfig.log').' 2>&1 &';
        exec($cmd);

        /* Note: Returning immediately but switch not completed yet. Anyway server side code
           might be completely different after switch */
        ajax::success(json_encode(array('status' => $status)));
    }

    /* Developer feature: Remove equipment(s) listed by id in 'eqList', from Jeedom DB.
       Zigate is untouched.
       Returns: status=0/-1, errors=<error message(s)> */
    if (init('action') == 'removeEqJeedom') {
        $eqList = init('eqList');

        $status = 0;
        $errors = ""; // Error messages
        foreach ($eqList as $eqId) {
            /* Collecting required infos */
            $eqLogic = eqLogic::byId($eqId);
            if (!is_object($eqLogic)) {
                throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__).' '.$eqId);
            }

            /* Removing device from Jeedom DB */
            $eqLogic->remove();
        }

        ajax::success(json_encode(array('status' => $status, 'errors' => $errors)));
    }

        /* Remove equipment(s) from zigbee listed by id in 'eqIdList'.
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'removeEqZigbee') {
            $eqIdList = init('eqIdList');

            $status = 0;
            $errors = ""; // Error messages
            $missingParentIEEE = FALSE;
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
                $queueKeyFormToCmd = msg_get_queue(queueKeyFormToCmd);
                $msg = array();
                $msg['topic']   = 'CmdAbeille'.$zgId.'/0000/LeaveRequest';
                $msg['payload'] = "IEEE=".$eqIEEE;
                if (msg_send($queueKeyFormToCmd, 1, $msg, true, false) == FALSE) {
                    $errors = "Could not send msg to 'queueKeyFormToCmd': msg=".json_encode($msg);
                    $status = -1;
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

            $curMonId = config::byKey('monitor', 'Abeille', false);
            if ($eqId !== $curMonId) {
                /* Saving ID of device to monitor */
                config::save('monitor', $eqId, 'Abeille');

                /* Need to start AbeilleMonitor if not already running
                and restart cmd & parser.
                WARNING: If cron is not running, any (re)start should be avoided. */
                $conf = AbeilleTools::getParameters();
                AbeilleTools::restartDaemons($conf, "AbeilleMonitor AbeilleParser AbeilleCmd");
            }

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
