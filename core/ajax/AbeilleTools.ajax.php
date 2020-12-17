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
    $dbgFile = __DIR__."/../../tmp/debug.php";
    if (file_exists($dbgFile)) {
        include_once $dbgFile;
        $dbgDeveloperMode = TRUE;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    // $pluginRoot = __DIR__.'/../..'; // Plugin root (ex: /var/www/html/plugins/Abeille)

    /* Log feature for debug purposes */
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
                $error = "Le fichier '".$file."' n'existe pas.";
            }
            if ($status == 0) {
                $mtime = filemtime($path);
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'mtime'=> $mtime)));
        }

        /* Get temp file last modification time.
           'file' is relative to Jeedom temp directory.
           Returns: status=0 if found, -1 else */
        if (init('action') == 'getTmpFileModificationTime') {
            $file = init('file');

            $path = jeedom::getTmpFolder("Abeille").'/'.$file;
            $status = 0;
            $error = "";
            $mtime = 0;

            if ($file == "") {
                $status = -1;
                $error = "Nom du fichier manquant.";
            }
            if (!file_exists($path)) {
                $status = -1;
                $error = "Le fichier '".$file."' n'existe pas.";
            }
            if ($status == 0) {
                clearstatcache(TRUE, $path);
                $mtime = filemtime($path);
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'mtime'=> $mtime)));
        }

        /* Retrieve file content from Jeedom temp directory.
           'file' is relative to Jeedom official temp dir.
           WARNING: Only 'text' content is supported right now.
           Returns: status=0 if found, -1 else */
        if (init('action') == 'getTmpFile') {
            $file = init('file');
            // TODO: If required, add mode=string/array

            $path = jeedom::getTmpFolder("Abeille").'/'.$file;
            $status = 0;
            $error = "";
            $content = "";

            if (!file_exists($path)) {
                $status = -1;
                $error = "Le fichier '".$file."' n'existe pas.";
            }
            if ($status == 0) {
                // $content = file($path); // $content = array, 1 element per line
                $content = file_get_contents($path); // $content is string
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'content' => $content)));
        }

        /* Create compressed file with all logs.
           Result is 'AbeilleLogs-YYMMDD.<ext>' in Jeedom temp directory.
           Returns: status=0 if found, -1 else */
        if (init('action') == 'createLogsZipFile') {
            $status = 0;
            $error = "";

            $tmpDir = jeedom::getTmpFolder("Abeille");
            $logsDir = $tmpDir."/AbeilleLogs";
            if (file_exists($logsDir)) {
                $cmd = "sudo rm -f ".$logsDir."/*";
                exec($cmd, $out, $status);
            } else {
                if (!file_exists($logsDir))
                    mkdir($logsDir);
            }

            /* Copie all logs to 'AbeilleLogs' & remove previous compressed file. */
            $jlogsDir = __DIR__."/../../../../log"; // Jeedom logs dir
            $cmd = "cd ".$jlogsDir."; sudo cp Abeille* ".$logsDir;
            $cmd .= "; sudo cp ".$tmpDir."/*.log ".$logsDir;
            // $cmd .= "; sudo rm -f tmp/AbeilleLogs.*";
            exec($cmd, $out, $status);

            /* Searching for available compression tool */
            // TODO: Tcharp38. Select tool according to what's available.
            $tool = "gzip";
            // $tool = "bzip2";

            $now = new DateTime;
            $zipFile = "AbeilleLogs-".$now->format('ymd'); // 'AbeilleLogs-YYMMDD'
            if ($tool == "gzip") {
                /* gzip
                -c => Write output on standard output
                -r => Travel the directory structure recursively
                */
                $zipFile .= ".tar.gz";
                $cmd = "cd ".$logsDir."; sudo tar cvf - * | gzip -c >../".$zipFile."; cd ..; rm -rf AbeilleLogs";
            }
            if ($tool == "bzip2") {
                /* bzip2
                -c => Compress or decompress to standard output
                -z => Compress
                -q => Quiet
                */
                $zipFile .= ".bz2";
                $cmd = "cd ".$logsDir."; sudo tar cvf - * | bzip2 -zqc >../".$zipFile."; cd ..; rm -rf AbeilleLogs";
            }

            exec($cmd, $out, $status);
            if ($status != 0)
                $error = "Erreur '".$cmd."'";

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'zipFile' => $zipFile)));
        }

        /* Remove given file from Jeedom temp directory.
           'file' is relative to Jeedom official temp dir.
           Returns: status=0 if ok, 1 =not found, -1=other error */
        if (init('action') == 'delTmpFile') {
            $file = init('file');

            $path = jeedom::getTmpFolder("Abeille").'/'.$file;
            $status = 0;
            $error = "";

            if (!file_exists($path)) {
                $status = 1;
                $error = "Le fichier '".$file."' n'existe pas.";
            }
            if ($status == 0) {
                if (unlink($path) == FALSE) {
                    $status = -1;
                    $error = "Impossible de détruire '".$file."'.";
                }
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
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
