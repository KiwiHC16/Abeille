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

    /* Errors reporting: uncomment below lines for debug */
    error_reporting(E_ALL);
    ini_set('error_log', '/var/www/html/log/AbeillePHP');
    ini_set('log_errors', 'On');

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
        // require_once __DIR__.'/../class/Abeille.class.php';
        // require_once __DIR__.'/../class/AbeilleZigate.php';
        include_once __DIR__.'/../../resources/AbeilleDeamon/lib/Tools.php'; // deamonlogFilter()

        include_file('core', 'authentification', 'php');
        if (!isConnect('admin')) {
            throw new Exception('401 Unauthorized');
        }

        ajax::init();

        /* Check if a file exists.
           'path' is relative to plugin root dir (/var/www/html/plugins/Abeille).
           Returns: status=0 if found, -1 else */
        // if (init('action') == 'fileExists') {
        //     $path = init('path');
        //     $path = __DIR__.'/../../'.$path;
        //     if (file_exists($path))
        //         $status = 0;
        //     else
        //         $status = -1; // Not found    
        //     ajax::success(json_encode(array('status' => $status)));
        // }

        /* Get file last modification time.
           'path' is relative to plugin root dir (/var/www/html/plugins/Abeille).
           Returns: status=0 if found, -1 else */
        if (init('action') == 'getFileModificationTime') {
            $file = init('path');
            $path = __DIR__.'/../../'.$file;

            $status = 0;
            $error = "";
            $mtime = 0;

            if (!file_exists($path)) {
                $status = -1;
                $error = "Le fichier ".$file." n'existe pas.";
            }
            if ($status == 0) {
                $mtime = filemtime($path);
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'mtime'=> $mtime)));
        }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
           Instead 'success' callback is used. This means that
           - take care of error code returned
           - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'AbeilleTools.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
