<?php
    /*
     * Abeille installation & update library.
     * Common functions to deal with pre or post installation.
     */

    $GLOBALS["md5File"] = __DIR__."/../../plugin_info/Abeille.md5";
    include_once "AbeilleLog.php"; // logDebug()

    /**
     * Function pour executer une commande shell et mettre le resultat dans le log
     * @param    commande shell a executer
     * @return  none
     */
    function Abeille_pre_update_log($cmd, $comment, $log, $echo) {
        exec( $cmd, $output);
        if ($log) log::add('Abeille', 'debug', $cmd.' -> '.json_encode($output));
        if ($echo) {
            echo $comment.' -> <br>';
            foreach ($output as $line) {
                echo "....".$line."<br>\n";
            }
            echo "<br>";
        }
    }

    /**
     * Function call before doing the update of the plugin from the Market
     * Fonction exécutée automatiquement avant la mise à jour du plugin
     * https://github.com/jeedom/plugin-template/blob/master/plugin_info/pre_install.php
     *
     * @param       none
     * @return      nothing
     */
    function Abeille_pre_update_analysis($log, $echo) {

        log::add('Abeille', 'debug', 'Launch of Abeille_pre_update_analysis()');

        $tmpDir = jeedom::getTmpFolder('Abeille');
        if ($echo) echo '{{Repertoire Temporaire}}: '.$tmpDir.'<br><br>';
        if ($log) log::add('Abeille', 'debug', '{{Repertoire Temporaire}}: '.$tmpDir );

        Abeille_pre_update_log('df -h', '{{Espaces disques}}:', $log, $echo);
        Abeille_pre_update_log("df  --output=avail -BM ".jeedom::getTmpFolder(), '{{Espace disponible}}:', $log, $echo);
        Abeille_pre_update_log("du -sh /var/www/html/plugins/Abeille", '{{Taille Abeille}}:', $log, $echo);

        exec ( 'df  --output=avail -BM '.$tmpDir, $output );
        $tmpSpaceAvailable = str_replace( 'M', '', $output[1] );
        unset( $output );

        // It is possible to get the size of the zip file downloaded by jeedom by: wget https://github.com/KiwiHC16/Abeille/archive/stable.zip -O /dev/null
        // The command wget --spider doesn't give the size on github.
        // So we have to download the complete file to know its size, so it's not a good solution.
        // Let's use the current size of the install plugin to have an idea.
        exec ( 'du -sh /var/www/html/plugins/Abeille', $output );
        list($AbeilleSize, $folder) = explode('M',$output[0]);
        unset( $output );

        if ( $tmpSpaceAvailable < $AbeilleSize*3 ) {
            if ($echo) echo '<span style="color:orange">{{L espace disponible est trop faible pour faire une mise a jour.}}<br>';
            if ($log) log::add('Abeille', 'debug', 'L espace disponible est trop faible pour faire une mise a jour.');
        } else {
            if ($echo) echo '<span style="color:green">{{Pas de soucis détecté.}}<br>';
            if ($log) log::add('Abeille', 'debug', 'Pas de soucis détecté.' );
        }

        log::add('Abeille', 'debug', 'End of Abeille_pre_update()');
    }

    /**
     * Check if valid 'Abeille.md5' is present and up-to-date.
     *
     * @param   none
     * @return 0=OK, -1=No MD5 file, -2=out-dated
     */
    function validMd5Exists() {
        global $md5File;
        if (!file_exists($md5File))
            return -1;

        /* To ensure it is up to date, checking version against md5 file header.
           MD5 file must be generated after Abeille's version update.
           # Auto-generated Abeille's MD5 file. DO NOT MODIFY !
           # VERSION="dev_tcharp38, 2021-01-29 12:51:55" */
        $file = fopen($md5File, "r");
        $line = fgets($file); // Should be a comment
        $line = trim(fgets($file)); // Should be Abeille's version
        fclose($file);
        $arr = explode("=", $line);
        /* arr[1] should be "dev_tcharp38, 2021-01-29 12:51:55" */
        $md5Version = str_replace("\"", "", $arr[1]);

        $file = fopen(__DIR__."/../../plugin_info/Abeille.version", "r");
        $line = fgets($file); // Should be a comment
        $abeilleVersion = trim(fgets($file));
        fclose($file);

        if ($md5Version != $abeilleVersion) {
            // logDebug("'Abeille.md5' is out-dated: abeilleVersion=".$abeilleVersion.", md5version=".$md5Version);
            return -2; // Out dated
        }

        return 0;
    }

    /**
     * Check plugin code integrity if 'Abeille.md5' is present and up-to-date.
     * Messages logged in 'integrityCheck.log', then 'AbeilleConfig.log' if error.
     * @param   none
     * @return 0=OK, -1=no MD5 file, -2=checksum error)
     */
    function checkIntegrity() {
        global $md5File;
        if (!file_exists($md5File))
            return -1;

        $logFile = __DIR__."/../../../../log/AbeilleConfig.log";
        $tmpDir = jeedom::getTmpFolder('Abeille');
        $tmpFile = $tmpDir."/integrityCheck.log";
        logSetConf($logFile, true); // Init AbeilleLog lib.
        logMessage("info", "Test d'intégrité d'Abeille");
        /* Note: Using 2 logs to be able to catch md5sum error if something bad found */
        $cmd = "cd ".__DIR__."/../..; md5sum -c --quiet plugin_info/Abeille.md5 >".$tmpFile." 2>&1";
        exec($cmd, $out, $status);
        // logDebug("out=".json_encode($out));
        if ($status != 0) {
            $prefix = logGetPrefix("info")."- "; // Get log prefix
            $cmd = "cat ".$tmpFile." | grep -v WARNING | sed -e 's/^/".$prefix."/' >>".$logFile;
            exec($cmd, $out, $status);
            logMessage("info", "= ERREUR: Les fichiers ci-dessus sont corrompus.");
            logMessage("info", "= L'installation s'est mal déroulée, peut-être par manque de place.");
            return -2;
        }
        logMessage("debug", "= Ok, tout semble correct.");
        return 0;
    }

    /**
     * Remove no longer needed files & repositories.
     * This is normally only required just after plugin update.
     * Messages logged in "AbeilleConfig.log".
     * @param   none
     * @return 0=OK, -1=ERROR (or no Abeille.md5)
     */
    function doPostUpdateCleanup() {
        global $md5File;
        if (!file_exists($md5File))
            return -1;

        $logFile = __DIR__."/../../../../log/AbeilleConfig.log";
        logSetConf($logFile, true); // Init AbeilleLog lib.
        logMessage("info", "Nettoyage des fichiers obsolètes...");

        /* Creating list of valid files */
        $refFiles = [];
        if ($file = fopen($md5File, "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                if (substr($line, 0, 1) == '#')
                    continue;
                if (strlen($line) == 0)
                    continue;
                $f = trim(substr($line, 34)); // Removing CRC + ' *' + EOL
                $refFiles[] = $f;
            }
            fclose($file);
        }

        /* Going thru all files to see if should be removed or not */
        define("pluginRoot", __DIR__."/../../");
        function cleanDir($dir, $refFiles) {
            // logDebug("cleanDir(".$dir.")");
            if ($dh = opendir(pluginRoot.$dir)) {
                while (($entry = readdir($dh)) !== false) {
                    // logDebug("entry=".$entry);

                    if (($entry == ".") || ($entry == ".."))
                        continue;
                    if (substr($entry, 0, 1) == ".")
                        continue; // Ignoring '.xxx' hidden files

                    if ($dir.$entry == "tmp")
                        continue; // Ignoring 'tmp' dir
                    if ($dir.$entry == "core/config/devices_local")
                        continue; // Ignoring local/user devices

                    if (is_dir(pluginRoot.$dir.$entry)) {
                        cleanDir($dir.$entry."/", $refFiles);
                    } else {
                        if (in_array($dir.$entry, $refFiles)) {
                            // logDebug($dir.$entry." found in refFiles");
                            continue;
                        }
                        exec("sudo rm -f ".pluginRoot.$dir.$entry, $out, $status);
                        if ($status == 0)
                            logMessage("info", "- '".$dir.$entry."' SUPPRIME");
                        else
                            logMessage("info", "- '".$dir.$entry."': ERREUR. Impossible de supprimer");
                    }
                }
                closedir($dh);
            }
        }
        cleanDir("", $refFiles); // Starting at plugin root
        logMessage("info", "= Nettoyage terminé");

        return 0;
    }

    // Abeille_pre_update();
?>
