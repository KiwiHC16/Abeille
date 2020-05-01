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
 * AJAX's requests targets for Abeille
 */

try {

    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__) . '/../class/Abeille.class.php';
    require_once dirname(__FILE__) . '/../class/AbeilleZigate.php';

    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }

    ajax::init();

    if (init('action') == 'syncconfAbeille') {
        abeille::syncconfAbeille(false);
        ajax::success();
    }

    if (init('action') == 'updateConfigAbeille') {
        abeille::updateConfigAbeille(false);
        ajax::success();
    }

    if (init('action') == 'installSocat') {
        abeille::installSocat(false);
        ajax::success();
    }

    if (init('action') == 'checkWiringPi') {
        $status = abeille::checkWiringPi(false);
        ajax::success(json_encode($status));
    }

    if (init('action') == 'installWiringPi') {
        abeille::installWiringPi(false);
        ajax::success();
    }

    if (init('action') == 'checkTTY') {
        $zgPort = init('zgport');

        log::remove('AbeillePiZigate');
        /* TODO: Log old issue. Why the following message never gets out ? */
        log::add('AbeillePiZigate', 'debug', 'Arret du démon');
        abeille::deamon_stop(); // Stopping daemon

        /* Checks port exists and is not already used */
        $cmdToExec = "checkTTY.sh " . $zgPort;
        $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/' . $cmdToExec . ' >>' . log::getPathToLog('AbeillePiZigate') . ' 2>&1';
        exec($cmd, $out, $status);
        // $status = 0;

        /* Read Zigate FW version */
        $version = 0; // FW version
        if ($status == 0) {
            $status = zg_GetVersion($zgPort, $version);
        }

        log::add('AbeillePiZigate', 'debug', 'Redémarrage du démon');
        abeille::deamon_start(); // Restarting daemon

        ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
    }

    if (init('action') == 'installTTY') {
        abeille::installTTY(false);
        ajax::success();
    }

    /* Update PiZigate FW but check parameters first, prior to shutdown daemon */
    if (init('action') == 'updateFirmwarePiZiGate') {
        // abeille::updateFirmwarePiZiGate(false, init('fwfile'), init('zgport'));
        $zgFwFile = init('fwfile');
        $zgPort = init('zgport');

        log::add('Abeille', 'debug', 'Démarrage updateFirmware(' . $zgFwFile . ', ' . $zgPort . ')');

        log::remove('AbeillePiZigate');
        log::add('AbeillePiZigate', 'info', 'Vérification des paramètres');
        $cmdToExec = "updateFirmware.sh " . $zgFwFile . " " . $zgPort . " -check";
        $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/'.$cmdToExec.' >> ' . log::getPathToLog('AbeillePiZigate') . ' 2>&1';
        exec($cmd, $out, $status);
        // if ($status != 0)
            // return $status; // Something wrong with parameters

        $version = 0; // FW version
        if ($status == 0) {
            log::add('AbeillePiZigate', 'info', 'Arret du démon');
            abeille::deamon_stop(); // Stopping daemon

            /* Updating FW and reset Zigate */
            log::add('AbeillePiZigate', 'info', 'Programming');
            $cmdToExec = "updateFirmware.sh " . $zgFwFile . " " . $zgPort;
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/'.$cmdToExec.' >> ' . log::getPathToLog('AbeillePiZigate') . ' 2>&1';
            exec($cmd, $out, $status);

            /* Reading FW version */
            if ($status == 0) {
                $status = zg_GetVersion($zgPort, $version);
            }

            log::add('AbeillePiZigate', 'info', 'Redémarrage du démon');
            abeille::deamon_start(); // Restarting daemon
        }

        ajax::success(json_encode(array('status' => $status, 'fw' => $version)));
    }

    if (init('action') == 'resetPiZiGate') {
        abeille::resetPiZiGate(false);
        ajax::success();
    }

    throw new Exception('Aucune methode correspondante');
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
