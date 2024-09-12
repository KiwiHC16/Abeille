<?php
    require_once __DIR__.'/../php/AbeilleLog.php';
    require_once __DIR__.'/../config/Abeille.config.php';
    require_once __DIR__.'/../php/AbeilleModels.php'; // getModelsList()

    define('devicesDir', __DIR__.'/../config/devices/'); // Abeille's supported devices
    define('devicesLocalDir', __DIR__.'/../config/devices_local/'); // Unsupported/user devices
    define('cmdsDir', __DIR__.'/../config/commands/'); // Abeille's supported commands
    define('imagesDir', __DIR__.'/../../images/'); // Abeille's supported icons

    class AbeilleTools
    {
        const configDir = __DIR__.'/../config/';

        /**
         * Get Abeille log level.
         *
         * @param: None
         * @return: int: 4=debug, 3=info, 2=warning, 1=error, 0=none
         */
        public static function getLogLevel() {
            // var_dump( config::getLogLevelPlugin()["log::level::Abeille"] );
            // si debug:  {"100":"1","200":"0","300":"0","400":"0","1000":"0","default":"0"}
            // si info:   {"100":"0","200":"1","300":"0","400":"0","1000":"0","default":"0"}
            // si warning:{"100":"0","200":"0","300":"1","400":"0","1000":"0","default":"0"}
            // si error:  {"100":"0","200":"0","300":"0","400":"1","1000":"0","default":"0"}
            // si aucun:  {"100":"0","200":"0","300":"0","400":"0","1000":"1","default":"0"}
            // si defaut: {"100":"0","200":"0","300":"0","400":"0","1000":"0","default":"1"}
            $logLevelPluginJson = config::getLogLevelPlugin()["log::level::Abeille"];
            if ($logLevelPluginJson['100']) return 4;
            else if ($logLevelPluginJson['200']) return 3;
            else if ($logLevelPluginJson['300']) return 2;
            else if ($logLevelPluginJson['400']) return 1;
            else if ($logLevelPluginJson['1000']) return 0;
            else if ($logLevelPluginJson['default']) return 1; // This one is set to 1 but should be found from conf
            else return 1; // Default = error
        }

        /**
         * Convert log level string to number to compare more easily.
         *
         * @param $loglevel
         * @return int
         */
        public static function getNumberFromLevel($loglevel) {
            $niveau = array(
                "NONE" => 0,
                "ERROR" => 1,
                "WARNING" => 2,
                "INFO" => 3,
                "DEBUG" => 4
            );

            $upperString = strtoupper(trim($loglevel));
            if (array_search($upperString, $niveau, false)) {
                return $niveau[$upperString];
            }
            #if logLevel is not found, then no log is allowed
            return "0";
        }

        /***
         * if loglevel is lower/equal than the app requested level then message is written
         *
         * @param string $log level of the message
         * @param string plugin Name
         * @param string logger name = script qui envoie le message
         * @param string le message lui meme
         * @param string $message
         */
        public static function deamonlogFilter($loglevel = 'NONE', $pluginName, $loggerName = 'Tools', $message = '') {
            if (strlen($message) < 1) return;
            if (self::getNumberFromLevel($loglevel) <= self::getLogLevel($pluginName)) {
                $loglevel = strtolower(trim($loglevel));
                if ($loglevel == "warning")
                    $loglevel = "warn";
                /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
                fwrite(STDOUT, '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $loglevel).'] '.$message.PHP_EOL);
            }
        }

        /* Clean 'devices_local'.
           Returns: true=ok, false=error */
        public static function cleanDevices() {
            $rootDir = devicesLocalDir;
            $dh = opendir($rootDir);
            if ($dh === false) {
                log::add('Abeille', 'error', 'cleanDevices(): opendir('.$rootDir.')');
                return false;
            }
            while (($dirEntry = readdir($dh)) !== false) {
                /* Ignoring some entries */
                if (in_array($dirEntry, array(".", "..")))
                    continue;
                $fullPath = $rootDir.$dirEntry;
                if (!is_dir($fullPath))
                    continue;

                // Any files in directory ?
                $empty = true; // Assuming empty
                $dh2 = opendir($fullPath);
                while (($dirEntry2 = readdir($dh2)) !== false) {
                    if (in_array($dirEntry2, array(".", "..")))
                        continue;
                    $empty = false;
                    break;
                }
                closedir($dh2);

                if ($empty) {
                    log::add('Abeille', 'debug', "cleanDevices(): Removing empty '".$dirEntry."'");
                    rmdir($fullPath);
                }

            }
            closedir($dh);

            return true;
        } // End cleanDevices()

        /* Get list of supported commands (found in core/config/commands).
        Returns: Indexed array; $commandsList[] = $fileName, or false if error */
        public static function getCommandsList() {
            $commandsList = [];

            $rootDir = cmdsDir;
            $dh = opendir($rootDir);
            if ($dh === false) {
                log::add('Abeille', 'error', 'getCommandsList(): opendir('.$rootDir.') error');
                return false;
            }
            while (($entry = readdir($dh)) !== false) {
                /* Ignoring non json files */
                if (in_array($entry, array(".", "..")))
                    continue;
                if (pathinfo($entry, PATHINFO_EXTENSION) != "json")
                    continue;

                $fullPath = $rootDir.$entry;
                if (!file_exists($fullPath)) {
                    log::add('Abeille', 'debug', 'getCommandsList(): path access error '.$fullPath);
                    continue;
                }

                $commandsList[] = substr($entry, 0, -5); // Removing ".json"
            }
            closedir($dh);
            sort($commandsList);

            return $commandsList;
        }

        /* Get list of icons (images/node_xxx.png).
        Returns: Array of icon names ('node_' & '.png' removed), or false if error */
        public static function getImagesList() {
            $imagesList = [];

            $rootDir = imagesDir;
            $dh = opendir($rootDir);
            if ($dh === false) {
                log::add('Abeille', 'error', 'getImagesList(): opendir('.$rootDir.') error');
                return false;
            }
            while (($entry = readdir($dh)) !== false) {
                /* Ignoring non json files */
                if (in_array($entry, array(".", "..")))
                    continue;
                if (pathinfo($entry, PATHINFO_EXTENSION) != "png")
                    continue;
                if (substr($entry, 0, 5) != "node_")
                    continue;

                $fullPath = $rootDir.$entry;
                if (!file_exists($fullPath)) {
                    log::add('Abeille', 'debug', 'getCommandsList(): path access error '.$fullPath);
                    continue;
                }

                $image = substr($entry, 0, -4); // Removing ".png"
                $image = substr($image, 5); // Removing 'node_'
                $imagesList[] = $image;
            }
            closedir($dh);
            sort($imagesList);

            return $imagesList;
        }

        /* Returns path for device JSON config file for given 'device'.
        If device is not found, returns false. */
        public static function getDevicePath($device) {
            /* Reminder:
            config/devices_local => user/custom devices not supported yet
            config/devices => devices officially supported by Abeille
            */

            /* Is that an unsupported or user device ? */
            $modelPath = devicesLocalDir.$device.'/'.$device.'.json';
            if (file_exists($modelPath)) {
                log::add('Abeille', 'debug', '  getDevicePath('.$device.') => '.$modelPath);
                return $modelPath;
            }

            /* Is that a supported device ? */
            $modelPath = devicesDir.$device.'/'.$device.'.json';
            if (file_exists($modelPath)) {
                log::add('Abeille', 'debug', '  getDevicePath('.$device.') => '.$modelPath);
                return $modelPath;
            }

            log::add('Abeille', 'debug', '  getDevicePath('.$device.') => NOT found');
            return false; // Not found
        }

        // Remove all 'commentX' from the top level of given 'tree'
        // OBSOLETE: Use 'removeModelComments() from AbeilleModels.php
        public static function removeComments(&$tree) {
            foreach ($tree as $key => $val) {
                if (substr($key, 0, 7) != "comment")
                    continue;

                // log::add('Abeille', 'debug', "REMOVING '{$key}' '{$val}'");
                unset($tree[$key]);
            }
        }

        /**
         * Read given command JSON model.
         *  'cmdFName' = command file name without '.json'
         *  'newJCmdName' = cmd name to replace (coming from 'use')
         * Returns: array() or false if not found.
         */
        // OBSOLETE: Use 'getCommandModel() from AbeilleModels.php
        public static function getCommandModel($modelName, $cmdFName, $newJCmdName = '') {
            $fullPath = cmdsDir.$cmdFName.'.json';
            if (!file_exists($fullPath)) {
                log::add('Abeille', 'error', "Modèle '".$modelName."': Le fichier de commande '".$cmdFName.".json' n'existe pas.");
                return false;
            }

            $jsonContent = file_get_contents($fullPath);
            $cmd = json_decode($jsonContent, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                log::add('Abeille', 'error', "Modèle '".$modelName."': Fichier de commande '".$cmdFName.".json' corrompu.");
                return false;
            }

            /* Replacing top key from fileName to jeedomCmdName if different */
            if ($newJCmdName != '')
                $cmdJName = $newJCmdName;
            else
                $cmdJName = $cmd[$cmdFName]['name'];
            if ($cmdJName != $cmdFName) {
                $cmd[$cmdJName] = $cmd[$cmdFName];
                unset($cmd[$cmdFName]);
            }

            // Removing all comments
            self::removeComments($cmd[$cmdJName]);
            // foreach ($cmd[$cmdJName] as $cmdKey => $cmdVal) {
            //     if (substr($cmdKey, 0, 7) != "comment")
            //         continue;
            //     unset($cmd[$cmdJName][$cmdKey]);
            // }

            return $cmd;
        }

        // Attempt to find corresponding model based on given zigbee signature.
        // Returns: associative array('modelSig', 'modelName, 'modelSource') or false
        // OBSOLETE: Use identifyModel() from AbeilleModels.php lib
        public static function findModel($zbModelId, $zbManufId) {
            log::add('Abeille', 'debug', "  OBSOLETE findModel({$zbModelId}, {$zbManufId})");

            $identifier1 = $zbModelId.'_'.$zbManufId;
            $identifier2 = $zbModelId;

            // Search by <zbModelId>_<zbManufId>, starting from local models list
            // $localModels = AbeilleTools::getDevicesList('local');
            $localModels = getModelsList('local');
            foreach ($localModels as $modelSig => $model) {
                if ($modelSig == $identifier1) {
                    $identifier = $identifier1;
                    break;
                }
            }
            if (!isset($identifier)) {
                // Search by <zbModelId>_<zbManufId>, starting from offical models list
                // $officialModels = AbeilleTools::getDevicesList('Abeille');
                $officialModels = getModelsList('Abeille');
                foreach ($officialModels as $modelSig => $model) {
                    if ($modelSig == $identifier1) {
                        $identifier = $identifier1;
                        break;
                    }
                }
            }
            if (!isset($identifier)) {
                // Search by <zbModelId> in local models
                foreach ($localModels as $modelSig => $model) {
                    if ($modelSig == $identifier2) {
                        $identifier = $identifier2;
                        break;
                    }
                }
            }
            if (!isset($identifier)) {
                // Search by <zbModelId> in offical models
                foreach ($officialModels as $modelSig => $model) {
                    if ($modelSig == $identifier2) {
                        $identifier = $identifier2;
                        break;
                    }
                }
            }
            if (!isset($identifier))
                return false; // No model found

            return $model;
        }

        /**
         * return the config list from a file located in core/config directory
         *
         * @param null $jsonFile
         * @return mixed|void
         */
        public static function getJSonConfigFiles($jsonFile = null) {
            $configDir = self::configDir;

            // self::deamonlog("debug", "Tools: loading file ".$jsonFile." in ".$configDir);
            $confFile = $configDir.$jsonFile;

            //file exists ?
            if (!is_file($confFile)) {
                log::add('Abeille', 'error', $confFile.' not found.');
                return;
            }
            // is valid json
            $content = file_get_contents($confFile);
            if (!is_json($content)) {
                log::add('Abeille', 'error', $confFile.' is not a valid json.');
                return;
            }

            $json = json_decode($content, true);

            // self::deamonlogFilter( "DEBUG", 'Abeille', 'Tools', "AbeilleTools: nb line ".strlen($content) );

            return $json;
        }

        /**
         * Get device config with device name located in core/config/devices/object.json
         *
         * @param null $device
         * @param Abeille logger name
         * @return bool|mixed|void
         */
        public static function getJSonConfigFilebyDevices($device = 'none', $logger = 'Abeille') {
            $modelPath = AbeilleTools::getDevicePath($device);
            // log::add('Abeille', 'debug', 'getJSonConfigFilebyDevices: devicefilename'.$modelPath);
            if ($modelPath === false) {
                // log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: file not found devicefilename'.$modelPath);
                return;
            }

            $content = file_get_contents($modelPath);

            $deviceJson = json_decode($content, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename content is not a json: '.$content);
                return;
            }

            // log::add('Abeille', 'debug', 'getJSonConfigFilebyDevices: '.$device.' json found Tools: nb line '.strlen($content));
            return $deviceJson;
        }

        /**
         * Return filename ready to be search in devices directories
         *
         * @param string $filename
         * @return mixed|string*
         */
        public static function getTrimmedValueForJsonFiles($filename = "") {
            //remove lumi. from name as all xiaomi devices have a lumi. name
            //remove all space in names for easier filename handling
            $trimmed = strlen($filename) > 1 ? str_replace(' ', '', str_replace('lumi.', '', $filename)) : "";
            return $trimmed;
        }

        /*
        * Scan config/devices directory to load devices name
        *
        * @param string $logger
        * @return array of json devices name
        */
        public static function getDeviceNameFromJson($logger = 'Abeille') {
            $return = array();
            // $dirCount = 0;
            // $dirExcluded = 0;
            // $fileMissing = 0;
            // $fileIllisible = 0;
            // $fileCount = 0;

            if (file_exists(devicesDir) == false) {
                log::add('Abeille', 'error', "Problème d'installation. Le chemin '...core/config/devices' n'existe pas.");
                return $return;
            }

            $dh = opendir(devicesDir);
            while (($dirEntry = readdir($dh)) !== false) {
                // $dirCount++;

                $fullPath = devicesDir.$dirEntry;
                if (!is_dir($fullPath))
                    continue;
                if (in_array($dirEntry, array(".", ".."))) {
                    // $dirExcluded++;
                    continue;
                }

                $fullPath = devicesDir.$dirEntry.DIRECTORY_SEPARATOR.$dirEntry.".json";
                if (!file_exists($fullPath)) {
                    // log::add('Abeille', 'warning', "Fichier introuvable: ".$fullPath);
                    // $fileMissing++;
                    // Probably an empty directory
                    continue;
                }

                try {
                    $jsonContent = file_get_contents($fullPath);
                } catch (Exception $e) {
                    log::add('Abeille', 'error', 'Impossible de lire le contenu du fichier '.$fullPath);
                    // $fileIllisible++;
                    continue;
                }

                $jdec = json_decode($jsonContent, true);
                if ($jdec == null) {
                    log::add('Abeille', 'error', 'Fichier corrompu: '.$fullPath);
                    // $fileIllisible++;
                    continue;
                }
                $returnOneMore = array_keys($jdec)[0];

                $return[] = $returnOneMore;
                // $fileCount++;
            }

            // log::add('Abeille', 'debug', "Nb repertoire template parcourus: ".$dirCount." dont ". $dirExcluded." exclus soit ".($dirCount-$dirExcluded).". ".$fileCount." fichiers template ajoutés à la liste, ".$fileMissing++." introuvables et ".$fileIllisible." templates illisibles." );
            return $return;
        }

        /**
         * Read 'config' DB for plugin 'Abeille' and returns an array.
         *
         * @return array
         */
        // public static function getParameters() { // OBSOLETE: Use getConfig() instead
        //     return AbeilleTools::getConfig();
        // }

        public static function getConfig() {
            $config = array();

            // Tcharp38: Should not be there
            $config['parametersCheck'] = 'ok'; // Ces deux variables permettent d'indiquer la validité des données.
            $config['parametersCheck_message'] = "";

            for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
                $config['ab::gtwType'.$gtwId] = config::byKey('ab::gtwType'.$gtwId, 'Abeille', 'zigate', 1);
                $config['ab::gtwSubType'.$gtwId] = config::byKey('ab::gtwSubType'.$gtwId, 'Abeille', '', 1);
                $config['ab::gtwPort'.$gtwId] = config::byKey('ab::gtwPort'.$gtwId, 'Abeille', '', 1);
                $config['ab::gtwIpAddr'.$gtwId] = config::byKey('ab::gtwIpAddr'.$gtwId, 'Abeille', '', 1);
                $config['ab::gtwEnabled'.$gtwId] = config::byKey('ab::gtwEnabled'.$gtwId, 'Abeille', 'N', 1);
                $config['ab::zgIeeeAddrOk'.$gtwId] = config::byKey('ab::zgIeeeAddrOk'.$gtwId, 'Abeille', 0);
                $config['ab::zgIeeeAddr'.$gtwId] = config::byKey('ab::zgIeeeAddr'.$gtwId, 'Abeille', '');
                $config['ab::gtwChan'.$gtwId] = config::byKey('ab::gtwChan'.$gtwId, 'Abeille', 0); // 0=auto
            }
            $config['ab::preventUsbPowerCycle'] = config::byKey('ab::preventUsbPowerCycle', 'Abeille', 'N');
            $config['ab::forceZigateHybridMode'] = config::byKey('ab::forceZigateHybridMode', 'Abeille', 'N');
            $config['ab::monitorId'] = config::byKey('ab::monitorId', 'Abeille', false);
            $config['ab::defaultParent'] = config::byKey('ab::defaultParent', 'Abeille', '1', 1);

            return $config;
        }

        /**
         * return missing daemon comparing active zigate and running daemons
         *
         * @param array $parameters
         * @param $running
         *
         * @return string of comma separated missing daemon
         */
        public static function getMissingDaemons(array $parameters, $running): string {
            $found = self::diffExpectedRunningDaemons($parameters, $running);
            $missing = "";
            foreach ($found as $daemon => $value) {
                if ($value == 0) {
                    if ($missing != "")
                        $missing .= ", ";
                    $missing .= $daemon;
                    // AbeilleTools::sendMessageToRuche($daemon, "processus manquant: $daemon");
                }
            }
            if (strlen($missing) > 0) {
                log::add('Abeille', 'debug', '  '.__CLASS__.':'.__FUNCTION__."(): Missing=".$missing);
                return $missing;
            } else {
                return "";
            }
        }

        /**
         * Check if any missing daemon and restart missing ones.
         *
         * @param $config
         * @return array
         */
        public static function checkAllDaemons2($config) {
            log::add('Abeille', 'debug', __FUNCTION__.'()');

            //remove all message before each check
            // AbeilleTools::clearSystemMessage($config,'all');

            /* status = array(
                'state' = 'ok'
                'running' = [],
                    running[daemonShortName] = array (
                        'pid'
                        'cmd'
                    )
             */
            $status = array(
                'state' => "ok",
                'running' => [],
            );

            // Expected daemons
            $nbGateways = 0;
            $nbZigates = 0;
            $expected = [];
            for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
                if ($config['ab::gtwEnabled'.$gtwId] == "N")
                    continue;

                $nbGateways++;
                $gtwType = $config['ab::gtwType'.$gtwId];
                $gtwSubType = $config['ab::gtwSubType'.$gtwId];
                if ($gtwType == "zigate") {
                    $nbZigates++;

                    $expected[] = 'SerialRead'.$gtwId;

                    // If type 'WIFI', socat daemon required too
                    if ($gtwSubType == "WIFI")
                        $expected[] = 'Socat'.$gtwId;
                } else { // gtwType == "ezsp"

                }
            }
            if ($nbGateways == 0) {
                log::add('Abeille', 'debug', '  NO active gateway');
                return $status;
            }

            if ($nbZigates != 0) {
                $expected[] = 'Parser';
                $expected[] = 'Cmd';
            }
            log::add('Abeille', 'debug', '  expected='.json_encode($expected, JSON_UNESCAPED_SLASHES));

            // Running daemons
            $running = AbeilleTools::getRunningDaemons2();
            log::add('Abeille', 'debug', '  running='.json_encode($running, JSON_UNESCAPED_SLASHES));

            // Restart missing ones
            $restart = '';
            foreach ($expected as $daemonName) {
                if (isset($running['daemons'][$daemonName])) {
                    // This daemon is running
                } else {
                    if ($restart != '')
                        $restart .= ' ';
                    $restart .= $daemonName;
                }
            }
            if ($restart != '')
                AbeilleTools::restartDaemons($config, $restart);

            $status['running'] = $running;
            // log::add('Abeille', 'debug', '  status='.json_encode($status));
            log::add('Abeille', 'debug', "checkAllDaemons2() => ".$status['state']);
            return $status;
        }

        /**
         * Get running processes for Abeille plugin
         *
         * @return array
         */
        public static function getRunningDaemons(): array {
            exec("pgrep -a php | awk '/Abeille(Parser|SerialRead|Cmd|Socat|Ezsp).php /'", $running);
            // TODO: Ezsp is not php but python3
            return $running;
        }

        /**
         * Get Abeille's running processes
         *
         * @return array of array
         */
        public static function getRunningDaemons2(): array {
            $cmd1 = "pgrep -a php | grep Abeille";
            exec($cmd1, $running); // Get all Abeille daemons
            /* 'pgrep -a php | grep Abeille' example:
                6333 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSocat.php /tmp/zigateWifi2 debug 192.168.0.102:9999 */
            $cmd2 = "pgrep -a socat | grep ".wifiLink;
            exec($cmd2, $running2); // Get zigate specifc 'socat' processes if any
            /*  'pgrep -a socat' example:
                16343 socat -d -d pty,raw,echo=0,link=/tmp/zigateWifi2 tcp:192.168.0.101:80 */
            $cmd3 = "pgrep -a python3 | grep Abeille";
            exec($cmd3, $running3); // Get all Abeille python3 daemons
            $processes = array_merge($running, $running2, $running3);

            $daemons = [];
            $runBits = 0;
            foreach ($processes as $line) {
                $lineArr = explode(" ", $line);
                if (strstr($line, "AbeilleCmd") != false) {
                    $shortName = "Cmd";
                    $runBits |= daemonCmd;
                } else if (strstr($line, "AbeilleParser") !== false) {
                    $shortName = "Parser";
                    $runBits |= daemonParser;
                } else if (strstr($line, "AbeilleMonitor") !== false) {
                    $shortName = "Monitor";
                    $runBits |= daemonMonitor;
                } else if (strstr($line, "AbeilleSerialRead") !== false) {
                    $net = $lineArr[3]; // Ex 'Abeille1'
                    $zgId = substr($net, 7);
                    $shortName = "SerialRead".$zgId;
                    $runBits |= constant("daemonSerialRead".$zgId);
                } else if (strstr($line, "AbeilleSocat") !== false) {
                    $net = $lineArr[3]; // Ex '/tmp/zigateWifiX'
                    $zgId = substr($net, strlen(wifiLink));
                    $shortName = "Socat".$zgId;
                    $runBits |= constant("daemonSocat".$zgId);
                } else if (strstr($line, "socat") !== false) {
                    $pos = strpos($line, wifiLink);
                    // TO BE COMPLETED if required
                    $zgId = substr($line, $pos + strlen(wifiLink), 1);
                    $shortName = "socat".$zgId;
                } else if (strstr($line, "AbeilleEzsp") !== false) {
                    $pos = strpos($line, "--gtwid=");
                    $gtwId = substr($line, $pos + 8, 1); // 'xxx --gtwid=Y' => Y
                    $shortName = "ezsp".$gtwId;
                    // $runBits |= constant("daemonSocat".$zgId);
                } else
                    $shortName = "Unknown";
                $d = array(
                    'pid' => $lineArr[0],
                    'cmd' => substr($line, strpos($line, " ")),
                );
                $daemons[$shortName] = $d;
            }
            $return = array(
                'runningNb' => sizeof($daemons), // Nb of running daemons
                'runBits' => $runBits, // 1 bit per running daemon
                'daemons' => $daemons, // Detail on each daemon
            );
            return $return;
        }

        /**
         * return an array with expected daemons and theirs status
         *
         * @param $parameters
         * @param $running
         * @return array
         */
        public
        static function diffExpectedRunningDaemons($config, $running): array
        {
            $found['cmd'] = 0;
            $found['parser'] = 0;
            $nbProcessExpected = 2; // parser and cmd

            log::add('Abeille', 'debug', "config=".json_encode($config));
            log::add('Abeille', 'debug', "running=".json_encode($running));

            // Count number of processes we should have based on configuration, init $found['process x'] to 0.
            for ($n = 1; $n <= maxGateways; $n++) {
                if ($config['ab::gtwEnabled'.$n] != "Y")
                    continue;

                if ($config['ab::gtwType'.$n] == 'zigate') {
                    $found['serialRead'.$n] = 0;
                    $nbProcessExpected++;

                    if ($config['ab::gtwSubType'.$n] == "WIFI") {
                        $found['socat'.$n] = 0;
                        $nbProcessExpected++;
                    }
                }
            }
            $found['expected'] = $nbProcessExpected;

            // Search processes runnning and increase $found['process x'] each time we find one.
            foreach ($running as $line) {
                $match = [];
                if (preg_match('/AbeilleSerialRead.php Abeille([0-9]) /', $line, $match)) {
                    $abeille = $match[1];
                    if (stristr($line, "abeilleserialread.php"))
                        $found['serialRead'.$abeille]++;
                }
                elseif (preg_match('/AbeilleSocat.php \/tmp\/zigateWifi([0-9]) /', $line, $match)) {
                    $abeille = $match[1];
                    if (stristr($line, 'abeillesocat.php')) {
                        $found['socat'.$abeille]++;
                    }
                }
                else {
                    if (stristr($line, "abeilleparser.php"))
                        $found['parser']++;
                    if (stristr($line, "abeillecmd.php"))
                        $found['cmd']++;
                }
            }

            //log::add('Abeille', 'debug', __CLASS__.':'.__FUNCTION__.':'.__LINE__.': nbExpected: '.$nbProcessExpected.', found: '.json_encode($found));

            return $found;
        }

        /**
         * Stop given daemons list or all daemons.
         * Ex: $daemons = "AbeilleMonitor AbeilleCmd AbeilleParser"
         * @param string $daemons Deamons list to stop, space separated. Empty string = ALL
         */
        public static function stopDaemons($toStop = "") {
            log::add('Abeille', 'debug', "  stopDaemons($toStop)");
            /* 'pgrep -a php | grep Abeille' example:
                6333 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSocat.php /tmp/zigateWifi2 debug 192.168.0.102:9999
                'pgrep -a socat' example:
                16343 socat -d -d pty,raw,echo=0,link=/tmp/zigateWifi2 tcp:192.168.0.101:80 */

            $cmd1 = $cmd2 = "";
            if ($toStop != "") {
                $running2 = self::getRunningDaemons2();
                if ($running2['runningNb'] == 0) {
                    log::add('Abeille', 'debug', '  stopDaemons(): No active daemons');
                    return true;
                }
                $toStopArr = explode(" ", $toStop);
                $running = [];
                $running['daemons'] = [];
                foreach($running2['daemons'] as $daemonName => $daemon) {
                    $found = false;
                    foreach($toStopArr as $daemonToStop) {
                        if (strstr($daemon['cmd'], $daemonToStop) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        $running['daemons'][$daemonName] = $daemon;
                    }
                }
                $running['runningNb'] = sizeof($running['daemons']);
                $running['runBits'] = 0; // TODO if required

                // $daemonsArr = explode(" ", $toStop);
                // $grep = "";
                // $grep2 = ""; // Grep pattern for 'socat'
                // foreach ($daemonsArr as $daemon) {
                //     if ($grep != "")
                //         $grep .= "\|";
                //     $grep .= $daemon;
                //     if (substr($daemon, 0, 12) == "AbeilleSocat") {
                //         $zgId = substr($daemon, 12);
                //         if ($grep2 != "")
                //             $grep2 .= "\|";
                //         $grep2 .= "zigate".$zgId;
                //     }
                // }
                // $cmd1 = "pgrep -a php | grep '".$grep."'";
                // exec($cmd1, $running);
                // if ($grep2 != "") {
                //     $cmd2 = "pgrep -a socat | grep '".$grep."'";
                //     exec($cmd2, $running2); // Get zigate specifc 'socat' processes if any
                //     $running = array_merge($running, $running2);
                // }
            } else {
                // Stopping all Abeille's daemons
                $running = self::getRunningDaemons2();

                // $cmd1 = "pgrep -a php | grep Abeille";
                // exec($cmd1, $running); // Get all Abeille daemons
                // $cmd2 = "pgrep -a socat | grep ".wifiLink;
                // exec($cmd2, $running2); // Get zigate specifc 'socat' processes if any
                // $running = array_merge($running, $running2);
            }
            log::add('Abeille', 'debug', '  stopDaemons(): running='.json_encode($running, JSON_UNESCAPED_SLASHES));

            /* Reminder:
                $running = array(
                    'runningNb' => $runningNb, // Nb of running daemons
                    'runBits' => $runBits, // 1 bit per running daemon
                    'daemons' => $daemons, // Detail on each daemon
                );
            */

            $nbOfDaemons = $running['runningNb'];
            if ($nbOfDaemons == 0) {
                log::add('Abeille', 'debug', '  stopDaemons(): No active daemons');
                return true;
            }

            log::add('Abeille', 'debug', '  stopDaemons(): Stopping '.$nbOfDaemons.' daemons');
            $allPids = '';
            $allPids2 = '';
            // for ($i = 0; $i < $nbOfDaemons; $i++) {
            //     // log::add('Abeille', 'debug', 'deamon_stopDaemonsstop(): running[i]='.$running[$i]);
            //     $arr = explode(" ", $running[$i]);
            //     $allPids .= ' '.$arr[0];
            // }
            foreach($running['daemons'] as $daemon) {
                $allPids .= ' '.$daemon['pid'];
                if (strstr($daemon['cmd'], "AbeilleSerialRead") !== false)
                    $allPids2 .= ' '.$daemon['pid']; // AbeilleSerialRead needs a 2nd kill
            }
            $cmd = "sudo kill -s TERM".$allPids;
            /* Note: Serial read has special behavior. Can't be killed with simple 'kill -s TERM' and when 'kill -s KILL'
                port still used but by 'apache2 -k ...'.
                Using double 'kill -s TERM' with delay seems to solve this pb. */
            if ($allPids2 != '')
                $cmd .= "; sleep 0.5; sudo kill -s TERM".$allPids2;
            log::add('Abeille', 'debug', '  stopDaemons(): '.$cmd);
            exec($cmd);

            /* Waiting until timeout that all daemons be ended */
            for ($t = 0; ($nbOfDaemons != 0) && ($t < daemonStopTimeout); $t+=500) {
                usleep(500000); // Sleep 500ms
                // $running = array(); // Clear previous result.
                // $running2 = array(); // Clear previous result.
                // exec($cmd1, $running);
                // if ($cmd2 != "") {
                //     exec($cmd2, $running2); // Get zigate specifc 'socat' processes if any
                //     $running = array_merge($running, $running2);
                // }
                // $nbOfDaemons = sizeof($running);
                $running = self::getRunningDaemons2();
                $nbOfDaemons = $running['runningNb'];
            }
            if ($nbOfDaemons != 0) {
                log::add('Abeille', 'debug', '  stopDaemons(): '.$nbOfDaemons.' daemons still active after '.daemonStopTimeout.' ms');
                log::add('Abeille', 'debug', '  stopDaemons(): '.json_encode($running, JSON_UNESCAPED_SLASHES));
                // for ($i = 0; $i < $nbOfDaemons; $i++) {
                //     $arr = explode(" ", $running[$i]);
                //     $pid = $arr[0];
                //     exec("sudo kill -s KILL ".$pid);
                // }
                $allPids = '';
                foreach ($running['daemons'] as $daemon) {
                    $allPids .= ' '.$daemon['pid'];
                }
                exec("sudo kill -s KILL".$allPids);
            }

            return true;
        }

        /**
         * Start daemons.
         * Ex: $daemons = "AbeilleMonitor AbeilleCmd AbeilleParser"
         * @param string $daemons Deamons list to start, space separated. Empty string = ALL
         */
        public static function startDaemons($config, $daemons = "") {
            if ($daemons == "") {
                /* Note: starting input daemons first to not loose any returned
                value/status as opposed to cmd being started first */
                $nbZigates = 0;
                for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
                    if ($config['ab::gtwEnabled'.$gtwId] != "Y")
                        continue; // Gateway disabled

                    $gtwType = $config['ab::gtwType'.$gtwId];
                    $gtwSubType = $config['ab::gtwSubType'.$gtwId];

                    if ($daemons != "")
                        $daemons .= " ";
                    if ($gtwType == 'zigate') {
                        $nbZigates++;

                        if ($gtwSubType == "WIFI")
                            $daemons .= "AbeilleSocat".$gtwId;

                        // Adding serial read
                        if ($daemons != "")
                            $daemons .= " ";
                        $daemons .= "AbeilleSerialRead".$gtwId;
                    } else { // ezsp
                        $daemons .= "AbeilleEzsp".$gtwId;
                    }
                }
                if ($nbZigates > 0)
                    $daemons .= " AbeilleParser AbeilleCmd";

                if ($daemons == "") {
                    message::add("Abeille", "Aucune passerelle active. Veuillez corriger via la page de config.");
                    return false;
                }

                /* Starting 'AbeilleMonitor' daemon too if required */
                if ($config['ab::monitorId'] !== false)
                    $daemons .= " AbeilleMonitor";
            }

            log::add('Abeille', 'debug', "  startDaemons(): ".$daemons);
            $daemonsArr = explode(" ", $daemons);
            foreach ($daemonsArr as $daemon) {
                $cmd = self::getStartCommand($config, $daemon);
                if ($cmd == "")
                    log::add('Abeille', 'debug', "  startDaemons(): ERROR, empty cmd for '".$daemon."'");
                else
                    exec($cmd.' &');
            }
            return true;
        }

        /**
         * (Re)start given daemons list.
         * Ex: $daemons = "AbeilleMonitor AbeilleCmd AbeilleParser"
         * @param string $daemons Deamons list to restart, space separated
         */
        public static function restartDaemons($config, $daemons = "") {

            log::add('Abeille', 'debug', "  restartDaemons(daemons={$daemons})");
            if (AbeilleTools::stopDaemons($daemons) == false)
                return false; // Error

            AbeilleTools::startDaemons($config, $daemons);

            return true; // ok
        }

        /**
         * Returns cmd to start given daemon
         * @param $config
         * @param $daemonFile (ex: 'cmd', 'parser', serialread1'...)
         * @return String
         */
        public static function getStartCommand($config, $daemonFile): string {
            $nohup = "/usr/bin/nohup";
            $php = "/usr/bin/php";
            $gtwId = (preg_match('/[0-9]/', $daemonFile, $matches) != true ? "" : $matches[0]);
            $logLevel = log::convertLogLevel(log::getLogLevel('Abeille'));
            $logsDir = logsDir;
            $tmpDir = jeedom::getTmpFolder("Abeille");

            unset($matches);
            $daemonFile = (preg_match('/[a-zA-Z]*/', $daemonFile, $matches) != true ? "" : strtolower($matches[0]));

            switch ($daemonFile) {
            // Zigate specific daemons
            case 'cmd':
            case 'abeillecmd':
                $daemonPhp = "AbeilleCmd.php";
                $logCmd = " >>{$logsDir}AbeilleCmd.log 2>&1";
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$logLevel.$logCmd;
                break;
            case 'parser':
            case 'abeilleparser':
                $daemonPhp = "AbeilleParser.php";
                $daemonLog = " >>{$logsDir}AbeilleParser.log 2>&1";
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$logLevel.$daemonLog;
                break;
            case 'serialread':
            case 'abeilleserialread':
                $daemonPhp = "AbeilleSerialRead.php";
                $daemonParams = 'Abeille'.$gtwId.' '.$config['ab::gtwPort'.$gtwId].' ';
                $daemonLog = $logLevel." >>{$tmpDir}/AbeilleSerialRead".$gtwId.".log 2>&1";
                exec(system::getCmdSudo().'chmod 777 '.$config['ab::gtwPort'.$gtwId].' > /dev/null 2>&1');
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$daemonParams.$daemonLog;
                break;
            case 'socat':
            case 'abeillesocat':
                $daemonPhp = "AbeilleSocat.php";
                $daemonParams = $config['ab::gtwPort'.$gtwId].' '.$config['ab::gtwIpAddr'.$gtwId].' '.$logLevel;
                $daemonLog = " >>{$logsDir}AbeilleSocat".$gtwId.'.log 2>&1';
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$daemonParams.$daemonLog;
                break;
            // EmberZnet/EZSP daemon
            case 'ezsp':
            case 'abeilleezsp':
            case 'abeilleezspd':
                $daemon = "AbeilleEzspD.py";
                $daemonParams = "--gtwid={$gtwId} --gtwport=".$config['ab::gtwPort'.$gtwId];
                $daemonLog = " >>{$logsDir}AbeilleEzsp".$gtwId.'.log 2>&1';
                $cmd = $nohup." python3 ".corePythonDir.$daemon." ".$daemonParams.$daemonLog;
                break;
            // General monitoring daemon
            case 'monitor':
            case 'abeillemonitor':
                $log = " >>".log::getPathToLog("AbeilleMonitor.log")." 2>&1";
                $cmd = $php." -r \"require '".corePhpDir."AbeilleMonitor.php'; monRun();\"".$log;
                break;
            default:
                $cmd = "";
            }
            return $cmd;
        }

        /**
         * send a message to a ruche according to the splitted daemonnameX
         * where daemonname and zigateNbr are extracted.
         *
         * @param $daemon
         */
        public static function sendMessageToRuche($daemon, $message = "") {
            global $abQueues;

            $daemonName = (preg_match('/[a-zA-Z]*/', $daemon, $matches) != true ? $daemon : $matches[0]);
            unset($matches);
            $zigateNbr = (preg_match('/[0-9]/', $daemon, $matches) != true ? "1" : $matches[0]);
            $messageToSend = ($message == "") ? "" : "$daemonName: $message";
            // log::add('Abeille', 'debug', "Process Monitoring: " .__CLASS__.':'.__FUNCTION__.':'.__LINE__.' sending '.$messageToSend.' to zigate '.$zigateNbr);
            if ( strlen($messageToSend) > 2 ) {
            Abeille::publishMosquitto($abQueues["xToAbeille"]["id"], PRIO_NORM,
                "Abeille$zigateNbr/0000/SystemMessage", $messageToSend);
            }
        }

        /**
         * clean messages displayed by all zigates if no parameters or all
         *
         * @param array $parameters jeedom Abeille's config
         * @param string $which number, from 1 to 9
         */
        public static function clearSystemMessage($parameters, $which = 'all') {
            if ($which == 'all') {
                for ($n = 1; $n <= maxGateways; $n++) {
                    if ($parameters['ab::gtwEnabled'.$n] == "Y") {
                        // log::add('Abeille', 'debug', "Process Monitoring: ".__CLASS__.':'.__FUNCTION__.':'.__LINE__.' clearing zigate '.$n);
                        AbeilleTools::sendMessageToRuche("daemon$n", "");
                    }
                }
            } else {
                // log::add('Abeille', 'debug', "Process Monitoring: ".__CLASS__.':'.__FUNCTION__.':'.__LINE__.' clearing zigate '.$which);
                AbeilleTools::sendMessageToRuche("daemon$which", "");
            }
        }

        // Unused
        // public static function checkRequired($type, $zigateNumber) {
        //     if ($type == 'PI')
        //         self::setPIGpio();
        //     else if ($type == 'WIFI')
        //         self::checkWifi($zigateNumber);
        // }

        /**
         * @param $zigateNumber
         */
        public static function checkWifi($zigateNumber) {
            exec("command -v socat", $out, $ret);
            if ($ret != 0) {
                log::add('Abeille', 'error', 'socat n\'est  pas installé. zigate Wifi inutilisable. Installer socat depuis la partie wifi zigate de la page de configuration');
                log::add('Abeille', 'debug', 'socat => '.implode(', ', $out));
                message::add("Abeille", "Erreur, socat n\'est  pas installé. zigate Wifi inutilisable. Installer socat depuis la partie wifi zigate de la page de configuration");

            } else {
                log::add('Abeille', 'debug', __CLASS__.'::'.__FUNCTION__.' L:'.__LINE__.'Zigate Wifi active trouvée, socat trouvé');
            }
        }

        /**
         * A PI zigate is found. Configuring GPIO for its use.
         * Returns: true if ok, false if error
         */
        public static function setPIGpio() {
            /* Configuring GPIO for PiZigate if one active found.
                PiZigate reminder (using 'WiringPi' (default) or 'PiGpio'):
                - port 0 = RESET
                - port 2 = FLASH
                - Production mode: FLASH=1, RESET=0 then 1 */
            $gpioLib = config::byKey('ab::defaultGpioLib', 'Abeille', 'WiringPi', 1);
            log::add('Abeille', 'debug', "  setPIGpio(): gpioLib='$gpioLib'");
            if ($gpioLib == "WiringPi") {
                exec("command gpio -v", $out, $ret);
                if ($ret != 0) {
                    log::add('Abeille', 'error', 'WiringPi semble mal installé.');
                    log::add('Abeille', 'debug', 'setPIGpio(): command gpio -v => '.json_encode($out));
                    return false;
                }

                exec("gpio mode 0 out; gpio mode 2 out; gpio write 2 1; gpio write 0 0; sleep 0.2; gpio write 0 1 &");
                return true;
            } else if ($gpioLib == "PiGpio") {
                exec("python3 ".__DIR__."/../scripts/resetPiZigate.py");
                return true;
            }

            log::add('Abeille', 'error', "Librairie GPIO $gpioLib invalide pour la PiZigate.");
            message::add('Abeille', "Librairie GPIO $gpioLib invalide pour la PiZigate.");
        }

        /**
         * Returns true is Abeille's cron (main daemon) is running.
         *
         * @return true if running, else false
         */
        public static function isAbeilleCronRunning() {
            $cron = cron::byClassAndFunction('Abeille', 'deamon');
            if (!is_object($cron))
                return false;
            if ($cron->running() == false)
                return false;
            return true;
        }

        /**
         * simple log function, extended in AbeilleDebug
         *
         * @param string $loglevel
         * @param string $message
         */
        function deamonlog($loglevel = 'NONE', $message = "") {
            log::add('Abeille', $loglevel, $message);
        }

        // Inverse l ordre des des octets.
        public static function reverseHex($a) {
            $reverse = "";
            for ($i = strlen($a) - 2; $i >= 0; $i -= 2) {
                $reverse .= $a[$i].$a[$i+1];
            }
            return $reverse;
        }

        // Base64 URL encode function
        // Returns false if error, or base64 URL compliant string
        public static function base64url_encode($data) {
            // First of all you should encode $data to Base64 string
            $b64 = base64_encode($data);

            // Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do
            if ($b64 === false) {
                return false;
            }

            // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
            $url = strtr($b64, '+/', '-_');

            // Remove padding character from the end of line and return the Base64URL result
            return rtrim($url, '=');
        }

        // Base64 URL to base64
        public static function base64url2base64($data) {
            // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
            $b64 = strtr($data, '-_', '+/');

            // Adding padding if required
            $b64 = str_pad($b64, strlen($b64) % 4, '=', STR_PAD_RIGHT);

            return $b64;
        }

        // Returns 'msg_send' error description
        public static function getMsgSendErr($errCode) {
            $errDesc = array(
                '11' => 'Queue full',
                '13' => 'Permission denied',
                '22' => 'Invalid argument' // EINVAL
            );
            if (isset($errDesc[$errCode]))
                return $errDesc[$errCode];
            else
                return "?";
        }
    }
?>
