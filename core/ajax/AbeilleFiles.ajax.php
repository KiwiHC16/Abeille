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
     * AJAX targets to manipulate files
     */

    require_once __DIR__.'/../config/Abeille.config.php';

    /* Developers debug features */
    if (file_exists(dbgFile)) {
        // include_once $dbgFile;
        $dbgDeveloperMode = TRUE;

        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect()) {
        ajax::error(json_encode(array('status' => -1, 'error' => "Non connecté ou session expirée.")));
    }

    include_once __DIR__.'/../php/AbeilleLog.php'; // logDebug()

    try {

        include_once __DIR__.'/../class/AbeilleTools.class.php'; // deamonlogFilter()

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

        /* Retrieve file content relative to Abeille plugin directory.
           'file' is relative to Abeille dir.
           WARNING: Only 'text' content is supported right now.
           Returns: status=0 if found, -1 else */
        if (init('action') == 'getFile') {
            $file = init('file');
            // TODO: If required, add mode=string/array

            $path = __DIR__.'/../../'.$file;
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
            // Note: always rm to avoid conflict with AbeilleLogs file instead of dir.
            if (file_exists($logsDir)) {
                $cmd = "sudo rm -rf ".$logsDir;
                exec($cmd, $out, $status);
            }
            mkdir($logsDir);

            // Create up-to-date key infos log
            exec('php '.__DIR__.'/../php/AbeilleSupportKeyInfos.php');

            /* Copie all logs to 'AbeilleLogs' & remove previous compressed file. */
            $jlogsDir = __DIR__."/../../../../log"; // Jeedom logs dir
            $cmd = "cd ".$jlogsDir."; sudo cp Abeille* ".$logsDir;
            $cmd .= "; sudo cp http.error event ".$logsDir;
            // $cmd .= "; sudo cp update ".$logsDir;
            $cmd .= "; sudo cp ".$tmpDir."/*.log ".$logsDir;
            // $cmd .= "; sudo rm -f tmp/AbeilleLogs.*";
            exec($cmd, $out, $status);

            // Special case for 'update': filtering out 'http://' or 'https://'
            if ($fileIn = fopen($jlogsDir."/update", "r")) {
                $fileOut = fopen($logsDir.'/update', 'w');
                while(!feof($fileIn)) {
                    $line = fgets($fileIn);
                    $pos = strpos($line, "http");
                    if ($pos !== false) {
                        if (substr($line, $pos, 8) == "https://")
                            $pos += 8;
                        else
                            $pos += 7;
                        $start = substr($line, 0, $pos);
                        fwrite($fileOut, $start." FILTERED\n");
                    } else
                        fwrite($fileOut, $line);
                }
                fclose($fileIn);
            }

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
                $cmd = "cd ".$logsDir."; sudo tar cf - * | gzip -c >../".$zipFile."; cd ..; rm -rf AbeilleLogs";
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

        /* Remove given 'file' relative to Abeille plugin directory.
           Returns: status=0 if ok, 1 =not found, -1=other error */
        if (init('action') == 'delFile') {
            $file = init('file');

            $path = __DIR__."/../../".$file;
            $status = 0;
            $error = "";
            logDebug("action=delFile, file=".$file.", path=".$path);

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

        /* Clear file content.
           'file' = file name.
           'location' = 'JEEDOM-LOG' (default, default log dir) or 'JEEDOM-TMP' (temp Abeille log dir)
           Returns: status=0 if ok, 1=not found, -1=other error */
        if (init('action') == 'clearFile') {
            $file = init('file');
            $location = init('location');

            if (($location == '') || ($location == 'JEEDOM-LOG'))
                $path = __DIR__."/../../../../log/".$file;
            else if ($location == "JEEDOM-TMP")
                $path = jeedom::getTmpFolder("Abeille").'/'.$file;
            else {
                ajax::error(json_encode(array('status' => -1, 'error' => "clearFile: Invalid 'location'")));
            }

            logDebug("action=clearFile, path=".$path);
            $status = 0;
            $error = "";

            if (!file_exists($path)) {
                $status = 1;
                $error = "Le fichier '".$file."' n'existe pas.";
            }
            if ($status == 0) {
                com_shell::execute(system::getCmdSudo().'chmod 664 '.$path.'>/dev/null 2>&1; cat /dev/null >'.$path);
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* Check if a file exists.
           'path' is relative to plugin root dir (/var/www/html/plugins/Abeille).
           Returns: status=0 if found, -1 else */
        if (init('action') == 'fileExists') {
            $path = init('path');
            $path = __DIR__.'/../../'.$path;
            if (file_exists($path))
                $status = 0;
            else
                $status = -1; // Not found
            ajax::success(json_encode(array('status' => $status)));
        }

        /* Write given text content to file.
           'path' is relative to plugin root dir (/var/www/html/plugins/Abeille).
           Returns: status=0 if Ok, -1 else */
        if (init('action') == 'writeFile') {
            $path = init('path');
            $content = init('content');
//logDebug("action=writeFile, path=".$path.", content=".$content);

            $path = __DIR__.'/../../'.$path;
            $status = 0;
            if (file_put_contents($path, $content) === false)
                $status = -1;

            ajax::success(json_encode(array('status' => $status)));
        }

        /* Retrieve device configuration reading device JSON & associated commands JSON.
           'jsonId' is JSON device name without extension.
           Returns: status=0 if found, -1 else */
        if (init('action') == 'readDeviceConfig') {
            $jsonId = init('jsonId');
            $jsonLocation = init('jsonLocation');
            $mode = init('mode');

            $status = 0;
            $error = "";
            $content = "";

            if ($jsonLocation == "Abeille")
                $fullPath = __DIR__.'/../config/devices/'.$jsonId.'/'.$jsonId.'.json';
            else
                $fullPath = __DIR__.'/../config/devices_local/'.$jsonId.'/'.$jsonId.'.json';
            if (!file_exists($fullPath)) {
                $status = -1;
                $error = "Le fichier '".$jsonId."' n'existe pas dans '".$jsonLocation."'";
            } else {
                $devModel = AbeilleTools::getDeviceModel('', $jsonId, $jsonLocation, $mode);
                if ($devModel === false) {
                    $status = -1;
                    $error = "Le modèle '".$jsonId."' n'existe pas dans '".$jsonLocation."'";
                } else
                    $content = json_encode($devModel);
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'content' => $content)));
        }

        /* Write device configuration.
           'jsonId' is JSON device name without extension.
           'jsonLocation' is 'Abeille' or 'local'.
           'devConfig' is JSON device config.
           Returns: status: 0=ok, -1=error */
        if (init('action') == 'writeDeviceConfig') {
logDebug("writeDeviceConfig");
            $jsonId = init('jsonId');
            $jsonLocation = init('jsonLocation'); // 'Abeille' or 'local'
            $devConfig = init('devConfig');
logDebug("jsonId=".$jsonId);
logDebug("jsonLocation=".$jsonLocation);

            if ($jsonLocation == "Abeille")
                $jsonDir = __DIR__.'/../config/devices/'.$jsonId;
            else
                $jsonDir = __DIR__.'/../config/devices_local/'.$jsonId;

            $status = 0;
            $error = "";

            if (!file_exists($jsonDir)) {
                if (mkdir($jsonDir) == false) {
                    $status = -1;
                    $error = "Can't create dir '".$jsonDir;
                }
            }
            if ($status == 0) {
                $fullPath = $jsonDir.'/'.$jsonId.'.json';
                $json = json_encode($devConfig, JSON_PRETTY_PRINT);
logDebug("json=".$json);
                file_put_contents($fullPath, $json);
            }

            ajax::success(json_encode(array('status' => $status, 'error' => $error)));
        }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
           Instead 'success' callback is used. This means that
           - take care of error code returned
           - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'AbeilleFiles.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
