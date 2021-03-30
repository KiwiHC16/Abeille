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
     * Targets for AJAX's requests for developer features
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        $dbgConfig = json_decode(file_get_contents($dbgFile), TRUE);
        $dbgDeveloperMode = TRUE;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    /* Write/create developer config.
       '$devConfig' = associative array
       Returns: 0=OK, -1=ERROR */
    function writeDevConfig($devConfig)
    {
        global $dbgFile;

        $tmp = __DIR__."/../../tmp";
        if (!file_exists($tmp))
            mkdir($tmp);
        if (file_exists($dbgFile) && !is_writable($dbgFile)) {
            logMessage('error', "'tmp/debug.json' n'est pas accessible en écriture.");
            return -1;
        }

        file_put_contents($dbgFile, json_encode($devConfig));
        chmod($dbgFile, 0666); // Allow read & write
        return 0;
    }

    try {
        require_once __DIR__.'/../../../../core/php/core.inc.php';
        include_once __DIR__.'/../../resources/AbeilleDeamon/lib/AbeilleTools.php'; // deamonlogFilter()
        include_once __DIR__.'/../php/AbeilleLog.php'; // logMessage(), logDebug()

        include_file('core', 'authentification', 'php');
        if (!isConnect('admin')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }

        ajax::init();

        /* Monitor equipment with given ID.
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'monitor') {
            $eqId = init('eqId');

            logSetConf("AbeilleMonitor.log", TRUE);
            // logMessage("debug", "AbeilleDev.ajax: action==monitor, eqId=".$eqId);

            $eqLogic = eqLogic::byId($eqId);
            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/8362'
            list($eqNet, $eqAddr) = explode( "/", $eqLogicId);

            /* Collecting IEEE address */
            $eqAddrExt = $eqLogic->getConfiguration('IEEE', '');
            if ($eqAddrExt == "")
                $eqAddrExt = "xxxxxxxxxxxxxxxx";

            $status = 0;
            $error = "";

            if (file_exists($dbgFile))
                $devConfig = json_decode(file_get_contents($dbgFile), TRUE);
            else
                $devConfig = array();
// logDebug("BEFORE devConfig=".json_encode($devConfig));
            $devConfig["dbgMonitorAddr"] = $eqAddr."-".$eqAddrExt;
// logDebug("AFTER devConfig=".json_encode($devConfig));
            if (writeDevConfig($devConfig) != 0) {
                logMessage("error", "Erreur writeDevConfig()");
                ajax::success(json_encode(array('status' => -1, 'error' => "Erreur writeDevConfig()")));
            }

            /* Need to start AbeilleMonitor if not already running
               and restart cmd & parser.
               WARNING: If cron is not running, any (re)start should be avoided. */
            $conf = AbeilleTools::getParameters();
            AbeilleTools::restartDaemons($conf, "AbeilleMonitor AbeilleParser AbeilleCmd");

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Create/write developer config file ("tmp/debug.json")
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'writeDevConfig') {
            $devConfig = init(devConfig); // JSON string
            $status = 0;
            $error = "";

            if ($devConfig == "")
                $devConfig = "{}";

            writeDevConfig(json_decode($devConfig));

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Read developer config file (tmp/debug.json)
           Returns: status=0/-1, errors=<error message(s)>, config=JSON string for developer config */
        if (init('action') == 'readDevConfig') {
            $status = 0;
            $error = "";
            $devConfig = ""; // JSON string

            if (file_exists($dbgFile) == FALSE)
                ajax::success(json_encode(array('status' => -1, 'error' => "Not in dev mode", 'config' => $devConfig)));

            $devConfig = file_get_contents($dbgFile);
            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'config' => $devConfig)));
        }

        if (init('action') == 'deleteDevConfig') {
            $status = 0;
            $error = "";

            if (file_exists($dbgFile) == FALSE)
                ajax::success(json_encode(array('status' => $status, 'error' => $error)));

            if (unlink($dbgFile) == FALSE) {
                $status = -1;
                $error = "Impossible de détruire le fichier de config.";
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Developer feature: Change timeout for equipment(s) listed by id in 'eqList'.
           Returns: status=0/-1, error=<error message(s)> */
        if (init('action') == 'setEqTimeout') {
            $eqList = init('eqList');
            $timeout = init('timeout');

            $status = 0;
            $error = ""; // Error messages
            foreach ($eqList as $eqId) {
                /* Collecting required infos */
                $eqLogic = eqLogic::byId($eqId);
                if (!is_object($eqLogic)) {
                    throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__).' '.$eqId);
                }

                /* Updating timeout */
                $eqLogic->setTimeout($timeout);
                $eqLogic->save();
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
           Instead 'success' callback is used. This means that
           - take care of error code returned
           - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'AbeilleDev.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
