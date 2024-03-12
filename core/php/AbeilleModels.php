<?php
    /* Generic library to deal with models */

    require_once __DIR__ . '/../config/Abeille.config.php'; // dbgFile constant + queues
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__ . '/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../php/AbeilleLog.php'; // logMessage(), logDebug()

    /* Get list of supported devices ($src="Abeille"), or user/custom ones ($src="local")
    Returns: false if error
        or associative array $modelsList[$modelSig+$modelVariant] = array(
            'modelSig' => model signature (the Zigbee signature that leads to this model)
            'modelName' => model file name WITHOUT '.json' extension
            'modelSource' => model file location ('Abeille' or 'local')
            'modelPath' => OPTIONAL: Path relative to 'modelSource' (default='<modelName>/<modelName>.json')
            'manufacturer' => equipment manufacturer
            'model' => equipment model
            'type' => equipment short description
            'icon' => equipment associated icon
        ) */
    function getModelsList($src = "Abeille") {
        $devicesList = [];

        if (($src == "Abeille") || ($src == ""))
            $rootDir = modelsDir;
        else if ($src == "local")
            $rootDir = modelsLocalDir;
        else {
            log::add('Abeille', 'error', "  getModelsList(): Emplacement JSON '".$src."' invalide");
            return false;
        }

        $dh = opendir($rootDir);
        if ($dh === false) {
            log::add('Abeille', 'error', '  getModelsList(): opendir('.$rootDir.')');
            return false;
        }
        while (($dirEntry = readdir($dh)) !== false) {
            /* Ignoring some entries */
            if (in_array($dirEntry, array(".", "..")))
                continue;
            $fullPath = $rootDir.$dirEntry;
            if (!is_dir($fullPath))
                continue;

            /* Supporting multiple variant of a model (rare case)
               <modelName>/<modelName>.json
               <modelName>/<modelName>-<variantX>.json (can't be auto-detected)
               <modelName>/<modelName>-<variantY>.json (can't be auto-detected) */
            $dh2 = opendir($fullPath);
            $deLen = strlen($dirEntry);
            while (($dirEntry2 = readdir($dh2)) !== false) {
                if (in_array($dirEntry2, array(".", "..")))
                    continue;
                if (pathinfo($dirEntry2, PATHINFO_EXTENSION) != "json")
                    continue;

                // Model and its optional variants is named '<modelName>[-<variant>].json'
                logDebug("${dirEntry} => dirEntry2='${dirEntry2}'");
                if (substr($dirEntry2, 0, $deLen) != $dirEntry)
                    continue; // Discovery or other file but not a model

                $dirEntry2 = substr($dirEntry2, 0, -5); // Removing trailing '.json'
                $fullPath2 = $rootDir.$dirEntry.'/'.$dirEntry2.'.json';
                if (!is_file($fullPath2)) {
                    logMessage("error", "getModelsList(): Erreur interne '${fullPath2}'");
                    continue;
                }

                $dev = array(
                    'modelSig' => $dirEntry,
                    'modelName' => $dirEntry2,
                    'modelSource' => $src
                );
                if ($dirEntry2 != $dirEntry)
                    $dev['modelPath'] = $dirEntry.'/'.$dirEntry2.'.json'; // It's a variant

                $content = file_get_contents($fullPath2);
                $devMod = json_decode($content, true);
                $devMod = $devMod[$dirEntry2]; // Removing top key

                $dev['manufacturer'] = isset($devMod['manufacturer']) ? $devMod['manufacturer'] : '';
                $dev['model'] = isset($devMod['model']) ? $devMod['model']: '';
                $dev['type'] = isset($devMod['type']) ? $devMod['type'] : '';
                $dev['icon'] = isset($devMod['configuration']['icon']) ? $devMod['configuration']['icon'] : '';
                $devicesList[$dirEntry2] = $dev;

                /* Any other equipments using the same model ? */
                if (isset($devMod['alternateIds'])) {
                    $ai = $devMod['alternateIds'];
                    /* Reminder:
                        "alternateIds": {
                            "alternateSigX": {
                                "manufacturer": "manufX", // Optional
                                "model": "modelX", // Optional
                                "type": "typeX" // Optional
                                "icon": "iconX" // Optional
                            },
                            "alternateSigY": {},
                            "alternateSigZ": {}
                        } */
                    foreach ($ai as $aId => $aIdVal) {
                        log::add('Abeille', 'debug', "  getModelsList(): Alternate ID '".$aId."' for '".$dirEntry2."'");
                        $devA = $dev; // modelName, modelSource & modelPath do not change
                        $devA['modelSig'] = $aId;

                        // manufacturer, model, type or icon can be overload
                        if (isset($aIdVal['manufacturer']))
                            $devA['manufacturer'] = $aIdVal['manufacturer'];
                        if (isset($aIdVal['model']))
                            $devA['model'] = $aIdVal['model'];
                        if (isset($aIdVal['type']))
                            $devA['type'] = $aIdVal['type'];
                        if (isset($aIdVal['icon']))
                            $devA['icon'] = $aIdVal['icon'];
                        $devicesList[$aId] = $devA;
                    }
                }
            }
            closedir($dh2);
        }
        closedir($dh);

        // Default sorting... by alphabetic order of manufacturers
        // TODO

        return $devicesList;
    } // End getModelsList()

    /*
     * Read device model
     * - src: 'Abeille' or 'local'
     * - modelPath: Relative path (modelName/modelName.json unless it is a variant)
     * - modelName: JSON file name without extension
     * - modelSig: Model signature (!= modelName if alternate signature)
     * - mode: 0/default=load commands too, 1=split cmd call & file
     * Return: device associative array WITHOUT top level key (modelSig) or false if error.
     */
    function getDeviceModel($src, $modelPath, $modelName, $modelSig='', $mode=0) {
        log::add('Abeille', 'debug', "  getDeviceModel(${src}, '${modelPath}', ${modelName}, ${modelSig}, mode=${mode})");

        // $dbg = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        // log::add('Abeille', 'debug', "BACKTRACE: ".json_encode($dbg, JSON_UNESCAPED_SLASHES));

        if ($modelPath == '') {
            log::add('Abeille', 'error', "  getDeviceModel(): 'modelPath' vide !");
            return false;
        }

        if (($src == 'Abeille') || ($src == ''))
            $modelPathFull = modelsDir.$modelPath;
        else
            $modelPathFull = modelsLocalDir.$modelPath;
        // log::add('Abeille', 'debug', '  modelPathFull='.$modelPathFull);
        if (!is_file($modelPathFull)) {
            log::add('Abeille', 'error', "  getDeviceModel(): Modèle d'équipement '${modelName}' inconnu.");
            return false;
        }

        $jsonContent = file_get_contents($modelPathFull);
        if ($jsonContent === false) {
            log::add('Abeille', 'error', "  getDeviceModel(): Le modèle d'équipement '${modelName}' est introuvable.");
            return false;
        }
        $device = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            log::add('Abeille', 'error', "  Le modèle JSON '${modelName}' est corrompu.");
            log::add('Abeille', 'debug', '  getDeviceModel(): content='.$jsonContent);
            return false;
        }

        $device = $device[$modelName]; // Removing top key
        $device['modelSource'] = $src; // Official device or local one ?
        $device['modelName'] = $modelName;
        $device['modelSig'] = ($modelSig != '') ? $modelSig : $modelName;

        /* Alternate signature support */
        if ($device['modelSig'] != $modelName) {
            /* Reminder:
                "alternateIds": {
                    "alternateSigX": {
                        "manufacturer": "manufX", // Optional
                        "model": "modelX", // Optional
                        "type": "typeX" // Optional
                        "icon": "iconX" // Optional
                    }
                } */
            if (!isset($device['alternateIds'][$modelSig])) {
                // Internal error
                log::add('Abeille', 'error', "getDeviceModel(): Unexpected alternate sig '${modelSig}'");
            } else {
                $alt = $device['alternateIds'][$modelSig];
                // manufacturer, model, type or icon overload
                if (isset($alt['manufacturer']))
                    $device['manufacturer'] = $alt['manufacturer'];
                if (isset($alt['model']))
                    $device['model'] = $alt['model'];
                if (isset($alt['type']))
                    $device['type'] = $alt['type'];
                if (isset($alt['icon']))
                    $device['configuration']['icon'] = $alt['icon'];
                if (isset($alt['batteryType']))
                    $device['configuration']['batteryType'] = $alt['batteryType'];
                unset($device['alternateIds']); // Cleanup
            }
        }

        // Removing all comments
        removeModelComments($device);
        if (isset($device['private']))
            removeModelComments($device['private']);

        // Variables section
        // "variables": {
        //     "groupEP1": "1001",
        //     "groupEP3": "3003",
        //     "groupEP4": "4004"
        // }
        if (isset($device['variables'])) { // Convert keys to upper case
            $variables = [];
            foreach($device['variables'] as $vKey => $vVal) {
                $vKey = strtoupper($vKey);
                $variables[$vKey] = $vVal;
            }
            $device['variables'] = $variables;
        }

        if (isset($device['commands'])) {
            if ($mode == 0) {
                $deviceCmds = array();
                $jsonCmds = $device['commands'];
                unset($device['commands']);
                foreach ($jsonCmds as $cmd1 => $cmd2) {
                    /* New command JSON format: "jeedom_cmd_name": { "use": "json_cmd_name", "params": "xxx"... } */
                    $cmdFName = $cmd2['use']; // File name without '.json'
                    $newCmd = getCommandModel($modelName, $cmdFName, $cmd1);
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
                    if (isset($cmd2['value']))
                        $newCmd[$cmd1]['value'] = $cmd2['value'];
                    if (isset($cmd2['disableTitle'])) // Disable title part of a subType 'message'
                        $newCmd[$cmd1]['disableTitle'] = $cmd2['disableTitle'];

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

                    // All overloads done. Let's check if any remaining variables
                    // #EP# => replaced by ['configuration']['mainEP']
                    // $newCmdTxt = json_encode($newCmd);
                    // $mainEP = $device['configuration']['mainEP'];
                    // $newCmdTxt = str_ireplace('#EP#', $mainEP, $newCmdTxt);
                    // // Temp '#GROUPEPx#' support
                    // for ($g = 1; $g <= 8; $g++) {
                    //     if (isset($device['configuration']["groupEP".$g])) {
                    //         // Case insensitive #xxx# replacement
                    //         $gVal = $device['configuration']["groupEP".$g];
                    //         $newCmdTxt = str_ireplace('#GROUPEP'.$g.'#', $gVal, $newCmdTxt);
                    //     }
                    // }
                    // $newCmd = json_decode($newCmdTxt, true);

                    // Finally variables are NOT replaced on model load but on execution. This allows user to change them thru advanced tab.
                    // $newCmdTxt = json_encode($newCmd);
                    // $offset = 0;
                    // while (true) {
                    //     $start = strpos($newCmdTxt, "#", $offset); // Start
                    //     if ($start === false)
                    //         break;
                    //     $len = strpos(substr($newCmdTxt, $start + 1), "#"); // Length
                    //     if ($len === false) {
                    //         log::add('Abeille', 'error', "getDeviceModel(): No closing dash (#) for cmd '${cmdJName}'");
                    //         break;
                    //     }
                    //     $len += 2;
                    //     // echo "S=".$start.", L=".$len."\n";
                    //     $var = substr($newCmdTxt, $start, $len);
                    //     $varUp = strtoupper($var);

                    //     if ($var == "#EP#") {
                    //         $mainEP = $device['configuration']['mainEP'];
                    //         $newCmdTxt = str_ireplace('#EP#', $mainEP, $newCmdTxt);
                    //     } else if (isset($device['variables']) && isset($device['variables'][$varUp])) {
                    //         $newCmdTxt = str_ireplace($var, $device['variables'][$varUp], $newCmdTxt);
                    //     }
                    //     // Note: Some vars are replaced just before command is executed

                    //     $offset = $start + $len;
                    // }
                    // $newCmd = json_decode($newCmdTxt, true);

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
                    $c = getCommandModel($modelName, $cFName, $cJName);
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
                        $newCmd = getCommandModel($modelName, $cmdFName, $cmd1);
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
     * Read given command JSON model.
     *  'cmdFName' = command file name without '.json'
     *  'newJCmdName' = cmd name to replace (coming from 'use')
     * Returns: array() or false if not found.
     */
    function getCommandModel($modelName, $cmdFName, $newJCmdName = '') {
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
        removeModelComments($cmd[$cmdJName]);
        // foreach ($cmd[$cmdJName] as $cmdKey => $cmdVal) {
        //     if (substr($cmdKey, 0, 7) != "comment")
        //         continue;
        //     unset($cmd[$cmdJName][$cmdKey]);
        // }

        return $cmd;
    }

    // Remove all 'commentX' from the top level of given 'tree'
    function removeModelComments(&$tree) {
        foreach ($tree as $key => $val) {
            if (substr($key, 0, 7) != "comment")
                continue;

            // log::add('Abeille', 'debug', "REMOVING '${key}' '${val}'");
            unset($tree[$key]);
        }
    }

    // Attempt to find corresponding model based on given zigbee signature.
    // Returns: associative array('modelSig', 'modelName, 'modelSource') or false
    function identifyModel($zbModelId, $zbManufId) {

        $identifier1 = $zbModelId.'_'.$zbManufId;
        $identifier2 = $zbModelId;

        // Search by <zbModelId>_<zbManufId>, starting from local models list
        $localModels = getModelsList('local');
        foreach ($localModels as $modelSig => $model) {
            if ($modelSig == $identifier1) {
                $identifier = $identifier1;
                break;
            }
        }
        if (!isset($identifier)) {
            // Search by <zbModelId>_<zbManufId>, starting from offical models list
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
?>
