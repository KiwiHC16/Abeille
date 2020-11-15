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

    function logToFile($logFile = '', $logLevel = 'NONE', $msg = "")
    {
        if (AbeilleTools::getNumberFromLevel($logLevel) > AbeilleTools::getPluginLogLevel('Abeille'))
            return; // Nothing to do

        $logDir = __DIR__.'/../../../../log/';
        /* TODO: How to align logLevel width for better visual aspect ? */
        file_put_contents($logDir.$logFile, '['.date('Y-m-d H:i:s').']['.$logLevel.'] '.$msg."\n", FILE_APPEND);
    }

try {

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/../class/Abeille.class.php';
    require_once __DIR__.'/../class/AbeilleZigate.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/lib/Tools.php'; // deamonlogFilter()

    include_file('core', 'authentification', 'php');
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }

    ajax::init();

    if (init('action') == 'checkSocat') {
        $cmd = '/bin/bash '.__DIR__.'/../../resources/checkSocat.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success(json_encode($status));
    }

    if (init('action') == 'installSocat') {
        $cmd = '/bin/bash '.__DIR__.'/../../resources/installSocat.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success(json_encode($status));
    }

    if (init('action') == 'checkWiringPi') {
        $cmd = '/bin/bash '.__DIR__.'/../../resources/checkWiringPi.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success(json_encode($status));
    }

    if (init('action') == 'installWiringPi') {
        $cmd = '/bin/bash '.__DIR__.'/../../resources/installWiringPi.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success();
    }

    if (init('action') == 'checkTTY') {
        $zgPort = init('zgport');
        $zgType = init('zgtype');
        logToFile('AbeilleConfig.log', 'info', 'Test de communication avec la Zigate; type='.$zgType.', port='.$zgPort);

        logToFile('AbeilleConfig.log', 'debug', 'Arret des démons');
        abeille::deamon_stop(); // Stopping daemon

        /* Checks port exists and is not already used */
        $cmdToExec = "checkTTY.sh ".$zgPort." ".$zgType;
        $cmd = '/bin/bash '.__DIR__.'/../../resources/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        // $status = 0;

        /* Read Zigate FW version */
        $version = 0; // FW version
        if ($status == 0) {
            zg_SetConf('AbeilleConfig.log');
            $status = zg_GetVersion($zgPort, $version);
        }

        logToFile('AbeilleConfig.log', 'debug', 'Redémarrage des démons');
        abeille::deamon_start(); // Restarting daemon

        ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
    }

    if (init('action') == 'installTTY') {
        $cmd = '/bin/bash '.__DIR__.'/../../resources/installTTY.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success();
    }

    /* Update PiZigate FW but check parameters first, prior to shutdown daemon */
    if (init('action') == 'updateFirmwarePiZiGate') {
        $zgFwFile = init('fwfile');
        $zgPort = init('zgport');

        logToFile('Abeille', 'debug', 'Démarrage updateFirmware(' . $zgFwFile . ', ' . $zgPort . ')');

        logToFile('AbeilleConfig.log', 'info', 'Vérification des paramètres');
        $cmdToExec = "updateFirmware.sh " . $zgFwFile . " " . $zgPort . " -check";
        $cmd = '/bin/bash '.__DIR__.'/../../resources/'.$cmdToExec.' >> '.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);

        $version = 0; // FW version
        if ($status == 0) {
            logToFile('AbeilleConfig.log', 'info', 'Arret des démons');
            abeille::deamon_stop(); // Stopping daemon

            /* Updating FW and reset Zigate */
            logToFile('AbeilleConfig.log', 'info', 'Programming');
            $cmdToExec = "updateFirmware.sh " . $zgFwFile . " " . $zgPort;
            $cmd = '/bin/bash ' . __DIR__ . '/../../resources/'.$cmdToExec.' >> ' . log::getPathToLog('AbeilleConfig.log') . ' 2>&1';
            exec($cmd, $out, $status);

            /* Reading FW version */
            if ($status == 0) {
                $status = zg_GetVersion($zgPort, $version);
            }

            logToFile('AbeilleConfig.log', 'info', 'Redémarrage des démons');
            abeille::deamon_start(); // Restarting daemon
        }

        ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
    }

    if (init('action') == 'resetPiZiGate') {
        $cmd = '/bin/bash '.__DIR__.'/../../resources/resetPiZigate.sh >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd, $out, $status);
        ajax::success();
    }

   /* Devloper mode: Switch GIT branch */
    if (init('action') == 'switchBranch') {
        $branch = init('branch');
        $updateOnly = init('updateOnly');

        $status = 0;

        /* Creating temp dir */
        $tmp = __DIR__.'/../../tmp';
        $doneFile = $tmp.'/switchBranch.done';
        if (file_exists($tmp) == FALSE)
            mkdir($tmp);
        else if (file_exists($doneFile))
            unlink($doneFile); // Removing 'switchBranch.done' file

        /* Creating a copy of 'switchBranch.sh' in 'tmp' */
        $cmd = 'cd '.__DIR__.'/../../resources/; sudo cp -p switchBranch.sh ../tmp/switchBranch.sh >> '.log::getPathToLog('AbeilleConfig.log').' 2>&1';
        exec($cmd);

        $cmdToExec = "switchBranch.sh ".$branch." ".$updateOnly;
        $cmd = 'nohup '.__DIR__.'/../../tmp/'.$cmdToExec.' >>'.log::getPathToLog('AbeilleConfig.log').' 2>&1 &';
        exec($cmd);

        ajax::success(json_encode(array('status' => $status)));
    }

    /* Developer featuer: Remove equipment(s) listed by id in 'eqList', from Jeedom DB.
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
    throw new Exception('Aucune methode correspondante');
    /********** Catch exeption ************/
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
