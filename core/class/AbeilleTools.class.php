<?php
    require_once __DIR__.'/../php/AbeilleLog.php';
    require_once __DIR__.'/../config/Abeille.config.php';

    define('corePhpDir', __DIR__.'/../php/');
    define('devicesDir', __DIR__.'/../config/devices/'); // Abeille's supported devices
    define('devicesLocalDir', __DIR__.'/../config/devices_local/'); // Unsupported/user devices
    define('cmdsDir', __DIR__.'/../config/commands/'); // Abeille's supported commands
    define('imagesDir', __DIR__.'/../../images/'); // Abeille's supported icons

    class AbeilleTools
    {
        const configDir = __DIR__.'/../config/';
        const logDir = __DIR__."/../../../../log/";

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

        /* Get list of supported devices ($from="Abeille"), or user/custom ones ($from="local")
           Returns: Associative array; $devicesList[$identifier] = array(), or false if error */
        public static function getDevicesList($from = "Abeille") {
            $devicesList = [];

            if ($from == "Abeille")
                $rootDir = devicesDir;
            else if ($from == "local")
                $rootDir = devicesLocalDir;
            else {
                log::add('Abeille', 'error', "getDevicesList(): Emplacement JSON '".$from."' invalide");
                return false;
            }

            $dh = opendir($rootDir);
            if ($dh === false) {
                log::add('Abeille', 'error', 'getDevicesList(): opendir('.$rootDir.')');
                return false;
            }
            while (($dirEntry = readdir($dh)) !== false) {
                /* Ignoring some entries */
                if (in_array($dirEntry, array(".", "..")))
                    continue;
                $fullPath = $rootDir.$dirEntry;
                if (!is_dir($fullPath))
                    continue;

                $fullPath = $rootDir.$dirEntry.'/'.$dirEntry.".json";
                if (!file_exists($fullPath))
                    continue; // No local JSON model. Maybe just an auto-discovery ?

                $dev = array(
                    'jsonId' => $dirEntry,
                    'location' => $from
                );

                /* Check if config is compliant with other device identification */
                $content = file_get_contents($fullPath);
                $devConf = json_decode($content, true);
                $devConf = $devConf[$dirEntry]; // Removing top key
                $dev['manufacturer'] = isset($devConf['manufacturer']) ? $devConf['manufacturer'] : '';
                $dev['model'] = isset($devConf['model']) ? $devConf['model']: '';
                $dev['type'] = $devConf['type'];
                $dev['icon'] = $devConf['configuration']['icon'];
                $devicesList[$dirEntry] = $dev;

                if (isset($devConf['alternateIds'])) {
                    $ai = $devConf['alternateIds'];
                    /* Reminder:
                       "alternateIds": {
                          "sigX": {
                            "manufacturer": "manufX", // Optional
                            "model": "modelX", // Optional
                            "type": "typeX" // Optional
                            "icon": "iconX" // Optional
                          }
                       } */
                    foreach ($ai as $aId => $aIdVal) {
                        log::add('Abeille', 'debug', "getDevicesList(): Alternate ID '".$aId."' for '".$dirEntry."'");
                        $dev = array(
                            'jsonId' => $dirEntry,
                            'location' => $from
                        );
                        // manufacturer, model, type or icon overload
                        if (isset($aIdVal['manufacturer']))
                            $dev['manufacturer'] = $aIdVal['manufacturer'];
                        if (isset($aIdVal['model']))
                            $dev['model'] = $aIdVal['model'];
                        if (isset($aIdVal['type']))
                            $dev['type'] = $aIdVal['type'];
                        if (isset($aIdVal['icon']))
                            $dev['icon'] = $aIdVal['icon'];
                        $devicesList[$aId] = $dev;
                    }
                }
            }
            closedir($dh);

            return $devicesList;
        } // End getDevicesList()

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

        /**
         * Read given command JSON model.
         *  'cmdFName' = command file name without '.json'
         *  'newJCmdName' = cmd name to replace (coming from 'use')
         * Returns: array() or false if not found.
         */
        public static function getCommandModel($cmdFName, $newJCmdName = '') {
            $fullPath = cmdsDir.$cmdFName.'.json';
            if (!file_exists($fullPath)) {
                log::add('Abeille', 'error', "Le fichier de commande '".$cmdFName.".json' n'existe pas.");
                return false;
            }

            $jsonContent = file_get_contents($fullPath);
            $cmd = json_decode($jsonContent, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                log::add('Abeille', 'error', "Fichier de commande '".$cmdFName.".json' corrompu.");
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
            foreach ($cmd[$cmdJName] as $cmdKey => $cmdVal) {
                if (substr($cmdKey, 0, 7) != "comment")
                    continue;
                unset($cmd[$cmdJName][$cmdKey]);
            }

            return $cmd;
        }

        /*
         * Read given device configuration from JSON file and associated commands.
         * 'modelName': JSON file name without extension
         * 'from': JSON file location (default=Abeille, or 'local')
         * 'mode': 0/default=load commands too, 1=split cmd call & file
         * Return: device associative array without top level key (jsonId) or false if error.
         */
        public static function getDeviceModel($modelName, $from="Abeille", $mode=0) {
            // log::add('Abeille', 'debug', 'getDeviceModel start, modelName='.$modelName.", from=".$from);

            if ($modelName == '') {
                log::add('Abeille', 'error', "getDeviceModel(): 'modelName' vide !");
                return false;
            }

            if (($from == 'Abeille') || ($from == ''))
                $modelPath = devicesDir.$modelName.'/'.$modelName.'.json';
            else
                $modelPath = devicesLocalDir.$modelName.'/'.$modelName.'.json';
            // log::add('Abeille', 'debug', '  modelPath='.$modelPath);
            if (!is_file($modelPath)) {
                // log::add('Abeille', 'error', 'Modèle \''.$modelName.'\' inconnu. Utilisation du modèle par défaut.');
                // $modelName = 'defaultUnknown';
                // $modelPath = devicesDir.$modelName.'/'.$modelName.'.json';
                log::add('Abeille', 'error', 'getDeviceModel(): Modèle \''.$modelName.'\' inconnu.');
                return false;
            }

            $jsonContent = file_get_contents($modelPath);
            $device = json_decode($jsonContent, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                log::add('Abeille', 'error', 'Le modèle JSON \''.$modelName.'\' est corrompu.');
                log::add('Abeille', 'debug', 'getDeviceModel(): content='.$jsonContent);
                return false;
            }

            $device = $device[$modelName]; // Removing top key
            $device['jsonId'] = $modelName;
            $device['jsonLocation'] = $from; // Official device or local one ?

            if (isset($device['commands'])) {
                if ($mode == 0) {
                    $deviceCmds = array();
                    $jsonCmds = $device['commands'];
                    unset($device['commands']);
                    foreach ($jsonCmds as $cmd1 => $cmd2) {
                        /* New command JSON format: "jeedom_cmd_name": { "use": "json_cmd_name", "params": "xxx"... } */
                        $cmdFName = $cmd2['use']; // File name without '.json'
                        $newCmd = self::getCommandModel($cmdFName, $cmd1);
                        if ($newCmd === false)
                            continue; // Cmd does not exist.

                        if (isset($cmd2['params'])) {
                            // log::add('Abeille', 'debug', 'params='.json_encode($cmd2['params']));
                            // log::add('Abeille', 'debug', 'newCmd BEFORE='.json_encode($newCmd));

                            // Overwritting default settings with 'params' content
                            // TODO: This should be done on 'configuration/request' only ??
                            $paramsArr = explode('&', $cmd2['params']); // ep=01&clustId=0000 => ep=01, clustId=0000
                            $text = json_encode($newCmd);
                            foreach ($paramsArr as $p) {
                                list($pName, $pVal) = explode("=", $p);
                                // Case insensitive #xxx# replacement
                                $text = str_ireplace('#'.$pName.'#', $pVal, $text);
                            }
                            $newCmd = json_decode($text, true);

                            // Adding new (optional) parameters
                            if (isset($newCmd[$cmd1]['configuration']['request'])) {
                                $request = $newCmd[$cmd1]['configuration']['request'];
                                // log::add('Abeille', 'debug', 'request BEFORE='.json_encode($request));
                                $requestArr = explode('&', $request); // ep=01&clustId=0000 => ep=01, clustId=0000
                                foreach ($paramsArr as $p) {
                                    list($pName, $pVal) = explode("=", $p);
                                    $found = false;
                                    foreach ($requestArr as $r) {
                                        list($rName, $rVal) = explode("=", $r);
                                        if ($rName == $pName) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if ($found == false)
                                        $request .= "&".$p; // Adding optional param
                                }
                                $newCmd[$cmd1]['configuration']['request'] = $request;
                                // log::add('Abeille', 'debug', 'request AFTER='.json_encode($newCmd[$cmd1]['configuration']['request']));
                            }

                            // log::add('Abeille', 'debug', 'newCmd AFTER='.json_encode($newCmd));
                        }

                        if (isset($cmd2['isVisible'])) {
                            $value = $cmd2['isVisible'];
                            if ($value === "yes")
                                $value = 1;
                            else if ($value === "no")
                                $value = 0;
                            $newCmd[$cmd1]['isVisible'] = $value;
                        }
                        if (isset($cmd2['nextLine']))
                            $newCmd[$cmd1]['nextLine'] = $cmd2['nextLine'];
                        // {
                        //     $value = $cmd2['nextLine'];
                        //     if ($value === "after")
                        //         $newCmd[$cmd1]['display']['forceReturnLineAfter'] = 1;
                        //     else if ($value === "before")
                        //         $newCmd[$cmd1]['display']['forceReturnLineBefore'] = 1;
                        // }
                        if (isset($cmd2['template']))
                            $newCmd[$cmd1]['template'] = $cmd2['template'];
                        if (isset($cmd2['subType']))
                            $newCmd[$cmd1]['subType'] = $cmd2['subType'];
                        if (isset($cmd2['unit']))
                            $newCmd[$cmd1]['unit'] = $cmd2['unit'];
                        if (isset($cmd2['isHistorized']))
                            $newCmd[$cmd1]['isHistorized'] = $cmd2['isHistorized'];
                        if (isset($cmd2['genericType']))
                            $newCmd[$cmd1]['genericType'] = $cmd2['genericType'];
                        if (isset($cmd2['logicalId']))
                            $newCmd[$cmd1]['logicalId'] = $cmd2['logicalId'];
                        if (isset($cmd2['invertBinary']))
                            $newCmd[$cmd1]['invertBinary'] = $cmd2['invertBinary'];

                        if (isset($cmd2['execAtCreation']))
                            $newCmd[$cmd1]['configuration']['execAtCreation'] = $cmd2['execAtCreation'];
                        if (isset($cmd2['execAtCreationDelay']))
                            $newCmd[$cmd1]['configuration']['execAtCreationDelay'] = $cmd2['execAtCreationDelay'];
                        if (isset($cmd2['minValue']))
                            $newCmd[$cmd1]['configuration']['minValue'] = $cmd2['minValue'];
                        if (isset($cmd2['maxValue']))
                            $newCmd[$cmd1]['configuration']['maxValue'] = $cmd2['maxValue'];
                        if (isset($cmd2['trigOut']))
                            $newCmd[$cmd1]['configuration']['trigOut'] = $cmd2['trigOut'];
                        if (isset($cmd2['trigOutOffset']))
                            $newCmd[$cmd1]['configuration']['trigOutOffset'] = $cmd2['trigOutOffset'];
                        if (isset($cmd2['historizeRound']))
                            $newCmd[$cmd1]['configuration']['historizeRound'] = $cmd2['historizeRound'];
                        if (isset($cmd2['calculValueOffset']))
                            $newCmd[$cmd1]['configuration']['calculValueOffset'] = $cmd2['calculValueOffset'];
                        if (isset($cmd2['repeatEventManagement']))
                            $newCmd[$cmd1]['configuration']['repeatEventManagement'] = $cmd2['repeatEventManagement'];
                        if (isset($cmd2['returnStateTime']))
                            $newCmd[$cmd1]['configuration']['returnStateTime'] = $cmd2['returnStateTime'];
                        if (isset($cmd2['returnStateValue']))
                            $newCmd[$cmd1]['configuration']['returnStateValue'] = $cmd2['returnStateValue'];
                        if (isset($cmd2['notStandard']))
                            $newCmd[$cmd1]['configuration']['notStandard'] = $cmd2['notStandard'];
                        if (isset($cmd2['valueOffset']))
                            $newCmd[$cmd1]['configuration']['valueOffset'] = $cmd2['valueOffset'];
                        if (isset($cmd2['listValue']))
                            $newCmd[$cmd1]['configuration']['listValue'] = $cmd2['listValue'];
                        if (isset($cmd2['Polling']))
                            $newCmd[$cmd1]['configuration']['Polling'] = $cmd2['Polling'];

                        // All overloads done. Let's check if any remaining variable
                        // #EP# => replaced by ['configuration']['mainEP']
                        $newCmdTxt = json_encode($newCmd);
                        $mainEP = $device['configuration']['mainEP'];
                        $newCmdTxt = str_ireplace('#EP#', $mainEP, $newCmdTxt);
                        // Temp '#GROUPEPx#' support
                        for ($g = 1; $g <= 8; $g++) {
                            if (isset($device['configuration']["groupEP".$g])) {
                                // Case insensitive #xxx# replacement
                                $gVal = $device['configuration']["groupEP".$g];
                                $newCmdTxt = str_ireplace('#GROUPEP'.$g.'#', $gVal, $newCmdTxt);
                            }
                        }
                        $newCmd = json_decode($newCmdTxt, true);

                        // log::add('Abeille', 'debug', 'getDeviceModel(): newCmd='.json_encode($newCmd));
                        $deviceCmds += $newCmd;
                    }

                    // Adding base commands
                    $baseCmds = array(
                        'inf_addr-Short' => 'Short-Addr',
                        'inf_addr-Ieee' => 'IEEE-Addr',
                        'inf_linkQuality' => 'Link Quality',
                        'inf_online' => 'Online',
                        'inf_time-String' => 'Time-Time',
                        'inf_time-Timestamp' => 'Time-TimeStamp'
                    );
                    foreach ($baseCmds as $cFName => $cJName) {
                        $c = self::getCommandModel($cFName, $cJName);
                        if ($c !== false)
                            $deviceCmds += $c;
                    }

                    $device['commands'] = $deviceCmds;
                } else if ($mode == 1) {
                    $jsonCmds = $device['commands'];
                    foreach ($jsonCmds as $cmd1 => $cmd2) {
                        // if (substr($cmd1, 0, 7) == "include") {
                        //     /* Old command JSON format: "includeX": "json_cmd_name" */
                        //     $cmdFName = $cmd2;
                        //     $newCmd = self::getCommandModel($cmdFName);
                        //     if ($newCmd === false)
                        //         continue; // Cmd does not exist.
                        // } else {
                            /* New command JSON format: "jeedom_cmd_name": { "use": "json_cmd_name", "params": "xxx"... } */
                            $cmdFName = $cmd2['use']; // File name without '.json'
                            $newCmd = self::getCommandModel($cmdFName, $cmd1);
                            if ($newCmd === false)
                                continue; // Cmd does not exist.
                        // }
                        $cmdsFiles[$cmdFName] = $newCmd;
                    }
                } else if ($mode == 2) {
                    /* Do not load commands in this mode */
                }
            } // End isset($device['commands'])

            // log::add('Abeille', 'debug', 'getDeviceModel end');
            return $device;
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
        public static function getParameters() {
            $config = array();

            // Tcharp38: Should not be there
            $config['parametersCheck'] = 'ok'; // Ces deux variables permettent d'indiquer la validité des données.
            $config['parametersCheck_message'] = "";

            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                $config['ab::zgType'.$zgId] = config::byKey('ab::zgType'.$zgId, 'Abeille', '', 1);
                $config['ab::zgPort'.$zgId] = config::byKey('ab::zgPort'.$zgId, 'Abeille', '', 1);
                $config['ab::zgIpAddr'.$zgId] = config::byKey('ab::zgIpAddr'.$zgId, 'Abeille', '', 1);
                $config['ab::zgEnabled'.$zgId] = config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N', 1);
                $config['ab::zgIeeeAddrOk'.$zgId] = config::byKey('ab::zgIeeeAddrOk'.$zgId, 'Abeille', 0);
                $config['ab::zgIeeeAddr'.$zgId] = config::byKey('ab::zgIeeeAddr'.$zgId, 'Abeille', '');
                $chan = config::byKey('ab::zgChan'.$zgId, 'Abeille', 'niet');
                if ($chan != 'niet')
                    $config['ab::zgChan'.$zgId] = $chan;
            }
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
         * @param $parameters
         * @param $running
         * @return mixed
         */
        // public static function checkAllDaemons($parameters, $running)
        // {
        //     //remove all message before each check
        //     AbeilleTools::clearSystemMessage($parameters,'all');

        //     $status['state'] = "ok";
        //     if (AbeilleTools::isMissingDaemons($parameters, $running) == true) {
        //         $status['state'] = "nok";
        //         AbeilleTools::restartMissingDaemons($parameters, $running);

        //         //After restart daemons, if still are missing, then no hope.
        //         if (AbeilleTools::isMissingDaemons($parameters, $running) == false) {
        //             $status['state'] = "ok"; // Finally missing ones restarted properly
        //         } else {
        //             /* There are still some missing daemons */
        //             $daemons = AbeilleTools::getMissingDaemons($parameters, $running);
        //             if (strlen($daemons) == 0) {
        //                 /* Ouahhh.. not normal */
        //                 AbeilleTools::clearSystemMessage($parameters, 'all');
        //                 $status['state'] = "ok";
        //             } else {
        //                 log::add('Abeille', 'debug', '  '.__CLASS__.':'.__FUNCTION__.'(): Missing daemons '.$daemons);
        //                 // AbeilleTools::sendMessageToRuche($daemons, 'Démons manquants: '.$daemons);
        //             }
        //         }
        //     }

        //     log::add('Abeille', 'debug', "checkAllDaemons() => ".$status['state']);
        //     return $status;
        // }

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
            $nbZigates = 0;
            $expected = [];
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                if ($config['ab::zgEnabled'.$zgId] == "N")
                    continue;

                $nbZigates++;
                $expected[] = 'SerialRead'.$zgId;

                // If type 'WIFI', socat daemon required too
                if ($config['ab::zgType'.$zgId] == "WIFI") {
                    $expected[] = 'Socat'.$zgId;
                }
            }
            if ($nbZigates == 0) {
                log::add('Abeille', 'debug', '  NO active zigate');
                return $status;
            }

            $expected[] = 'Parser';
            $expected[] = 'Cmd';
            log::add('Abeille', 'debug', '  expected='.json_encode($expected));

            // Running daemons
            $running = AbeilleTools::getRunningDaemons2();
            log::add('Abeille', 'debug', '  running='.json_encode($running));

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
            exec("pgrep -a php | awk '/Abeille(Parser|SerialRead|Cmd|Socat).php /'", $running);
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
            $processes = array_merge($running, $running2);

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
                    $zgId = substr($line, $pos + strlen(wifiLink), 1); // WARNING: Currently limited to 1 to 9. Zigate 10 missing
                    $shortName = "socat".$zgId;
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
        static function diffExpectedRunningDaemons($parameters, $running): array
        {
            $nbProcessExpected = 2; // parser and cmd

            $activedZigate = 0;
            $found['cmd'] = 0;
            $found['parser'] = 0;

            // Count number of processes we should have based on configuration, init $found['process x'] to 0.
            for ($n = 1; $n <= maxNbOfZigate; $n++) {
                if ($parameters['ab::zgEnabled'.$n] == "Y") {
                    $activedZigate++;

                    // e.g. "ab::zgType2":"WIFI", "ab::zgPort2":"/tmp/zigateWifi2"
                    // SerialRead + Socat
                    // if (stristr($parameters['ab::zgPort'.$n], '/tmp/zigateWifi')) {
                    if ($parameters['ab::zgType'.$n] == "WIFI") {
                        $nbProcessExpected += 2;
                        $found['serialRead'.$n] = 0;
                        $found['socat'.$n] = 0;
                    }

                    // e.g. "ab::zgType1":"USB", "ab::zgPort1":"/dev/ttyUSB3"
                    // SerialRead
                    if (preg_match("(tty|monit)", $parameters['ab::zgPort'.$n])) {
                        $nbProcessExpected++;
                        $found['serialRead'.$n] = 0;
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
         * @param $isCron   is cron daemon for this plugin started
         * @param $parameters (parametersCheck, parametersCheck_message, ab::defaultParent,
         * ab::zgType,ab::zgPortX, ab::zgIpAddrX, ab::zgEnabled
         * @param $running
         * return true if any missing daemons
         */
        // public
        // static function isMissingDaemons($parameters, $running): bool
        // {
        //     //no cron, no start requested
        //     // if (AbeilleTools::isAbeilleCronRunning() == false) {
        //     //     return false;
        //     // }

        //     $found = self::diffExpectedRunningDaemons($parameters, $running);
        //     $nbProcessExpected = $found['expected'];
        //     array_pop($found);
        //     $result = $nbProcessExpected - array_sum($found);
        //     // log::add('Abeille', 'debug', "Process Monitoring: ".__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '.($result == 0 ? 'false' : 'true').',   found: '.json_encode($found));
        //     //log::add('Abeille', 'Info', 'Tools:isMissingDaemons:'.($result==1?"Yes":"No"));
        //     if ($result > 1) {
        //         log::add('Abeille', 'Warning', 'Abeille: il manque au moins un processus pour gérer la zigate');
        //         // message::add("Abeille", "Danger,  il manque au moins un processus pour gérer la zigate");
        //     }

        //     return $result > 0;
        // }

        /**
         * @param array $parameters
         * @param $running
         */
        // public
        // static function restartMissingDaemons(array $parameters, $running)
        // {
        //     $lastLaunch = config::byKey('lastDeamonLaunchTime', 'Abeille', '');
        //     $found = self::diffExpectedRunningDaemons($parameters, $running);
        //     array_splice($found, -1, 1);
        //     //get socat first
        //     arsort($found);
        //     $found = array_reverse($found);
        //     // log::add('Abeille', 'debug', "Process Monitoring: ".__CLASS__.':'.__FUNCTION__.':'.__LINE__.' : lastLaunch: '.$lastLaunch.', found:'.json_encode($found));

        //     // $missing = "";
        //     foreach ($found as $daemon => $value) {
        //         if ($value == 0) {
        //             // AbeilleTools::sendMessageToRuche($daemon,'relance de '.$daemon);
        //             log::add('Abeille', 'debug', "Restarting ".$daemon);
        //             // $missing .= ", $daemon";
        //             $cmd = self::getStartCommand($parameters, $daemon);
        //             // log::add('Abeille', 'info', "Process Monitoring: ".__CLASS__.':'.__FUNCTION__.':'.__LINE__.': restarting Abeille'.$daemon. '/'. $value);
        //             // log::add('Abeille', 'debug', "Process Monitoring: ".__CLASS__.':'.__FUNCTION__.':'.__LINE__ .': restarting  XXXXX  Abeille XXXXX'.$daemon.': '.$cmd);
        //             exec($cmd.' &');
        //         }

        //     }
        //     // log::add('Abeille', 'debug', "Process Monitoring: ".__CLASS__.':'.__FUNCTION__.':'.__LINE__.': missing daemons:'.$missing);
        // }

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
            log::add('Abeille', 'debug', '  stopDaemons(): running='.json_encode($running));

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
                log::add('Abeille', 'debug', '  stopDaemons(): '.json_encode($running));
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
                for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                    if ($config['ab::zgEnabled'.$zgId] != "Y")
                        continue; // Zigate disabled

                    if ($config['ab::zgType'.$zgId] == "WIFI") {
                        if ($daemons != "")
                            $daemons .= " ";
                        $daemons .= "AbeilleSocat".$zgId;
                    }
                    if ($daemons != "")
                        $daemons .= " ";
                    $daemons .= "AbeilleSerialRead".$zgId;
                }
                if ($daemons == "") {
                    message::add("Abeille", "Aucune zigate active. Veuillez corriger.");
                    return false;
                }
                $daemons .= " AbeilleParser AbeilleCmd";

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
            $nb = (preg_match('/[0-9]/', $daemonFile, $matches) != true ? "" : $matches[0]);
            $logLevel = log::convertLogLevel(log::getLogLevel('Abeille'));
            $logDir = self::logDir;

            unset($matches);
            $daemonFile = (preg_match('/[a-zA-Z]*/', $daemonFile, $matches) != true ? "" : strtolower($matches[0]));

            switch ($daemonFile) {
            case 'cmd':
            case 'abeillecmd':
                $daemonPhp = "AbeilleCmd.php";
                $logCmd = " >>".$logDir."AbeilleCmd.log 2>&1";
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$logLevel.$logCmd;
                break;
            case 'parser':
            case 'abeilleparser':
                $daemonPhp = "AbeilleParser.php";
                $daemonLog = " >>".$logDir."AbeilleParser.log 2>&1";
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$logLevel.$daemonLog;
                break;
            case 'serialread':
            case 'abeilleserialread':
                $daemonPhp = "AbeilleSerialRead.php";
                $daemonParams = 'Abeille'.$nb.' '.$config['ab::zgPort'.$nb].' ';
                $daemonLog = $logLevel." >>".$logDir."AbeilleSerialRead".$nb.".log 2>&1";
                exec(system::getCmdSudo().'chmod 777 '.$config['ab::zgPort'.$nb].' > /dev/null 2>&1');
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$daemonParams.$daemonLog;
                break;
            case 'socat':
            case 'abeillesocat':
                $daemonPhp = "AbeilleSocat.php";
                $daemonParams = $config['ab::zgPort'.$nb].' '.$logLevel.' '.$config['ab::zgIpAddr'.$nb];
                $daemonLog = " >>".$logDir."AbeilleSocat".$nb.'.log 2>&1';
                $cmd = $nohup." ".$php." ".corePhpDir.$daemonPhp." ".$daemonParams.$daemonLog;
                break;
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
                for ($n = 1; $n <= maxNbOfZigate; $n++) {
                    if ($parameters['ab::zgEnabled'.$n] == "Y") {
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
            if (config::byKey('ab::defaultGpioLib', 'Abeille', 'WiringPi', 1)=="WiringPi") {
                exec("command gpio -v", $out, $ret);
                if ($ret != 0) {
                    log::add('Abeille', 'error', 'WiringPi semble mal installé.');
                    log::add('Abeille', 'debug', 'setPIGpio(): command gpio -v => '.json_encode($out));
                    return false;
                }

                /* Configuring GPIO for PiZigate if one active found.
                    PiZigate reminder (using 'WiringPi'):
                    - port 0 = RESET
                    - port 2 = FLASH
                    - Production mode: FLASH=1, RESET=0 then 1 */
                log::add('Abeille', 'debug', 'AbeilleTools:setPIGpio(): Active PiZigate found => configuring GPIOs');
                exec("gpio mode 0 out; gpio mode 2 out; gpio write 2 1; gpio write 0 0; sleep 0.2; gpio write 0 1 &");
                return true;
            }
            if (config::byKey('ab::defaultGpioLib', 'Abeille', 'WiringPi', 1)=="PiGpio") {
                exec("python /var/www/html/plugins/Abeille/core/scripts/resetPiZigate.py");
                return true;
            }

            log::add('Abeille', 'error', 'Pas de librairie GPIO definie pour la PiZigate.');
            message::add('Abeille', 'Pas de librairie GPIO definie pour la PiZigate.');
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
    }
?>
