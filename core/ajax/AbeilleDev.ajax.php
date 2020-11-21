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
    $dbgFile = __DIR__."/../../tmp/debug.php";
    if (file_exists($dbgFile)) {
        include_once $dbgFile;
        $dbgDeveloperMode = TRUE;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

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
        include_once __DIR__.'/../../resources/AbeilleDeamon/lib/AbeilleTools.php'; // deamonlogFilter()

        include_file('core', 'authentification', 'php');
        if (!isConnect('admin')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }

        ajax::init();

        /* Monitor equipment with given ID.
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'monitor') {
            $eqId = init('eqId');

            $eqLogic = eqLogic::byId($eqId);
            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/8362'
            list($net, $eqAddr) = explode( "/", $eqLogicId);

            /* Collecting IEEE address */
            $eqAddrExt = $eqLogic->getConfiguration('IEEE', 'none');
            if ($eqAddrExt == "none")
                $eqAddrExt = "xxxxxxxxxxxxxxxx";

            $status = 0;
            $error = "";

            if (!is_writable($dbgFile)) {
                $error = "'debug.php' n'est pas accessible en écriture.";
                logToFile('Abeille', 'error', $error);
                $status = -1;
            }

            /* Read & update 'debug.php' */
            /* Note: In the future, this monitor feature may be accessible to end users.
               The addr to monitor will therefore no longer be in debug.php */
            $tmp = __DIR__."/../../tmp";
            if (!file_exists($tmp))
                mkdir($tmp);
            $dbgFileTmp = $tmp."/debug.php.tmp";
            $fo = fopen($dbgFileTmp, "w");
            $daemonsMustRestart = FALSE;
            $fi = file_get_contents($dbgFile);
            $rows = explode("\n", $fi);
            $entryFound = FALSE; // True is "dbgMonitorAddr" entry found
            foreach($rows as $row => $line) {
                if (strstr($line, '$dbgMonitorAddr')) {
                    /* TODO: Check if addr is different */
                    fprintf($fo, "    \$dbgMonitorAddr = \"%s-%s\";\n", $eqAddr, $eqAddrExt);
                    $daemonsMustRestart = TRUE;
                    $entryFound = TRUE;
                    continue;
                }
                if (strstr($line, '?>')) {
                    if ($entryFound == FALSE) {
                        fprintf($fo, "    \$dbgMonitorAddr = \"%s-%s\";\n", $eqAddr, $eqAddrExt);
                        $daemonsMustRestart = TRUE;
                    }
                }
                fprintf($fo, "%s\n", $line);
            }
            fclose($fo);
            chmod($dbgFileTmp, 0666); // Allow read & write
            unlink($dbgFile); // Delete
            rename($dbgFileTmp, $dbgFile); // Move new file from tmp

            if (($status == 0) && $daemonsMustRestart) {
                logToFile('Abeille', 'info', 'Adresse à surveiller = '.$eqAddr.'. Redémarrage des démons.');
                abeille::deamon_start(); // Restarting daemons (start is performing stop anyway)
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Create developer config file ("tmp/debug.php")
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'createDevConfig') {
            $status = 0;
            $error = "";

            $tmpDir = __DIR__."/../../tmp";
            if (file_exists($tmpDir) == FALSE)
                mkdir($tmpDir);

            file_put_contents($dbgFile, "<?php\n");
            file_put_contents($dbgFile, "   // Auto-generated developers config file.\n", FILE_APPEND);
            file_put_contents($dbgFile, "   // ".date('Y-m-d H:i:s')." \n\n", FILE_APPEND);
            // file_put_contents($dbgFile, "   \$dbgAbeillePHP = TRUE;\n", FILE_APPEND);
            file_put_contents($dbgFile, "   \$dbgMonitorAddr = \"\";\n", FILE_APPEND);
            file_put_contents($dbgFile, "?>\n", FILE_APPEND);

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
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

        /* Read developer config file (tmp/debug.php)
           Returns: status=0/-1, errors=<error message(s)>, config=array <devconfig> */
        if (init('action') == 'readDevConfig') {
            $status = 0;
            $error = "";
            $devConfig = array();

            if (file_exists($dbgFile) == FALSE)
                ajax::success(json_encode(array('status' => $status, 'error' => $error, 'config' => $devConfig)));

            $fi = file_get_contents($dbgFile);
            $rows = explode("\n", $fi);
            foreach($rows as $row => $line) {
                trim($line);
                if ($line == "")
                    continue; // Empty line
                if (substr($line, 0, 2) == "//")
                    continue; // Single line comment

                if (substr($line, 0, 14) == "\$dbgAbeillePHP") {
                    $l = explode("=", $line);
                    $a = trim($l[1]); // $a='FALSE;' or 'TRUE;'
                    if (substr($a, 0, 4) == "TRUE")
                        $devConfig['dbgAbeillePHP'] = TRUE;
                    else
                        $devConfig['dbgAbeillePHP'] = FALSE;
                    continue;
                }
                if (substr($line, 0, 15) == "\$dbgMonitorAddr") {
                    $l = explode("=", $line);
                    $a = trim($l[1]); // $a='"xxxx-xxxxxxxxxxxxxxxx"; // comments'
                    $end = strpos($a, ";"); // Where is end of line code ?
                    $devConfig['dbgMonitorAddr'] = substr($a, 0, $end);
                    continue;
                }
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'config' => $devConfig)));
        }

        /* Update developer config file ("tmp/debug.php")
           Returns: status=0/-1, errors=<error message(s)> */
        if (init('action') == 'updateDevConfig') {
            $devConfig = init('devConfig'); // Expecting array
            $status = 0;
            $error = "";

            /* Read & update 'debug.php' */
            $tmpDir = __DIR__."/../../tmp";
            if (!file_exists($tmpDir))
                mkdir($tmpDir);

            $dbgFileTmp = $tmpDir."/debug.php.tmp";
            $fo = fopen($dbgFileTmp, "w");
            $fi = file_get_contents($dbgFile);
            $rows = explode("\n", $fi);
            foreach($rows as $row => $line) {
                if (strstr($line, '$dbgAbeillePHP')) {
                    if (!isset($devConfig["dbgAbeillePHP"]))
                        fprintf($fo, "%s\n", $line); // Not set so don't touch
                    else {
                        fprintf($fo, "    \$dbgAbeillePHP = %s;\n", ($devConfig["dbgAbeillePHP"] == "true" ? "TRUE" : "FALSE"));
                    }
                    continue;
                }
                if (strstr($line, '$dbgMonitorAddr')) {
                    if (!isset($devConfig["dbgMonitorAddr"]))
                        fprintf($fo, "%s\n", $line); // Not set so don't touch
                    else
                        fprintf($fo, "    \$dbgMonitorAddr = \"%s\";\n", $devConfig["dbgMonitorAddr"]);
                    continue;
                }

                fprintf($fo, "%s\n", $line);
                if (strstr($line, '?>'))
                    break;
            }
            fclose($fo);
            chmod($dbgFileTmp, 0666); // Allow read & write
            unlink($dbgFile); // Delete
            rename($dbgFileTmp, $dbgFile); // Move new file from tmp

            sleep(2); // A small delay before returning to allow change detection
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
