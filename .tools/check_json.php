<?php
    /* Check JSON files */

    define('devicesDir', __DIR__.'/../core/config/devices');
    define('commandsDir', __DIR__.'/../core/config/commands');
    require_once __DIR__.'/../core/php/AbeilleZigbeeConst.php'; // Zigbee constants

    // eqLogic categories
    // {"heating":"1","security":"0","energy":"0","light":"0","opening":"0","automatism":"0","multimedia":"0","default":"0"}
    $eqCategories = ['heating', 'security', 'energy', 'light', 'opening', 'automatism', 'multimedia', 'default'];

    // eqLogic generic types
    $eqGenTypes = ['Other', 'Battery', 'Camera', 'Heating', 'Electricity', 'Environment', 'Generic', 'Light', 'Mode', 'Multimedia', 'Weather', 'Opening', 'Outlet', 'Robot', 'Security', 'Thermostat', 'Fan', 'Shutter'];

    // Cmd 'action' & 'info' subtypes
    $actSubTypes = ['other', 'slider', 'message', 'color', 'select'];
    $infSubTypes = ['numeric', 'binary', 'string'];

    $devModList = [];
    $commandsList = [];
    $allCommandsList = []; // All available commands
    $missingCmds = 0;
    $devErrors = []; // Errors/warnings found in devices
    $cmdErrors = []; // Errors/warnings found in commands
    $unusedCmds = []; // List of unused cmds (obsolete)
    $idx = 0;

    function step($char) {
        global $idx;
        echo $char;
        $idx++;
        if ($idx ==78) {
            echo "\n  ";
            $idx = 2;
        }
    }

    /* Register a new device error/warning and returns 'true' */
    function newDevError($file, $type, $msg) {
        // echo "  ".$type.": ".$msg."\n";

        global $devErrors;
        $e = array(
            "file" => $file,
            "type" => $type, // 'ERROR', 'WARNING'
            "msg" => $msg
        );
        $devErrors[] = $e;
        return true;
    }

    /* Register a new command error/warning */
    function newCmdError($file, $type, $msg) {
        // echo "  ".$type.": ".$msg."\n";

        global $cmdErrors;
        $e = array(
            "file" => $file,
            "type" => $type,
            "msg" => $msg
        );
        $cmdErrors[] = $e;
    }

    // Check cmd 'subType'. Returns true if ok, else false
    function cmdSubTypeIsOk($type, $subType) {
        if ($type == "action") {
            global $actSubTypes;
            // $actSubTypes = ['other', 'slider', 'message', 'color', 'select'];
            if (!in_array($subType, $actSubTypes))
                return false;
        } else {
            global $infSubTypes;
            if (!in_array($subType, $infSubTypes))
                return false;
        }
        return true;
    }

    // Check device model
    function checkDeviceModel($devModName, $dev) {
        global $missingCmds;
        global $commandsList;
        global $eqGenTypes;

        if (!isset($dev[$devModName])) {
            newDevError($devModName, "ERROR", "Corruped JSON. Expecting '".$devModName."' top key");
            step('E');
            return;
        }
        // echo "dev=".json_encode($dev)."\n";

        $error = false;
        $devModel = $dev[$devModName]; // Removing top key

        // Checking 'type'
        if (!isset($devModel['type'])) {
            $error = newDevError($devModName, "ERROR", "No equipment 'type' defined");
        }

        // Checking 'genericType'
        if (isset($devModel['genericType'])) {
            $genType = $devModel['genericType'];
            if (!in_array($genType, $eqGenTypes))
                $error = newDevError($devModName, "ERROR", "Invalid 'genericType' defined: ".$genType);
        } /* else
            $error = newDevError($devModName, "WARNING", "No equipment genericType' defined"); */

        // Checking 'category'
        if (!isset($devModel['category'])) {
            $error = newDevError($devModName, "ERROR", "No 'category' defined");
        } else {
            $allCats = $devModel['category'];
            // $allowed = ['heating', 'security', 'energy', 'light', 'opening', 'automatism', 'multimedia', 'other'];
            global $eqCategories;
            foreach($allCats as $cat => $catEn) {
                if (in_array($cat, $eqCategories))
                    continue;
                $error = newDevError($devModName, "ERROR", "Unexpected '".$cat."' category.");
            }
        }

        // Checking 'configuration'
        if (!isset($devModel['configuration'])) {
            $error = newDevError($devModName, "WARNING", "No configuration defined");
        } else {
            $config = $devModel['configuration'];

            /* Checking icon */
            $icon = "";
            if (!isset($config['icon'])) {
                if (isset($config['icone'])) {
                    $error = newDevError($devModName, "ERROR", "'icone' is obsolete. Use 'icon' instead.");
                    $icon = $config['icone'];
                }
            } else
                $icon = $config['icon'];
            if ($icon == "")
                $error = newDevError($devModName, "ERROR", "No 'icon' defined.");
            else {
                $icon = "images/node_".$icon.".png";
                if (!file_exists($icon)) {
                    $error = newDevError($devModName, "ERROR", "Missing icon '".$icon."' file.");
                }
            }

            if (!isset($config['mainEP']))
                $error = newDevError($devModName, "ERROR", "Missing 'configuration:mainEP'");
            else if (!ctype_xdigit($config['mainEP']))
                $error = newDevError($devModName, "ERROR", "'configuration:mainEP' should be hexa string. #EP# not allowed.");

            /* Checking 'configuration' fields validity */
            $supportedKeys = ['icon', 'mainEP', 'trig', 'trigOffset', 'batteryType', 'poll', 'lastCommunicationTimeOut', 'paramType'];
            foreach ($config as $fieldName => $fieldValue) {
                if (substr($fieldName, 0, 7) == "groupEP")
                    continue; // Allowed
                if (!in_array($fieldName, $supportedKeys))
                    $error = newDevError($devModName, "ERROR", "Invalid '".$fieldName."' configuration field");
            }
        }

        // Variables section
        // "variables": {
        //     "groupEP1": "1001",
        //     "groupEP3": "3003",
        //     "groupEP4": "4004"
        // }
        if (isset($devModel['variables'])) { // Convert keys to upper case
            $variables = [];
            foreach($devModel['variables'] as $vKey => $vVal) {
                $vKey = strtoupper($vKey);
                $variables[$vKey] = $vVal;
            }
            $devModel['variables'] = $variables;
        }

        // Checking 'commands'
        $unusedCmds = &$GLOBALS['unusedCmds'];
        $logicIds = [];
        if (!isset($devModel['commands'])) {
            $error = newDevError($devModName, "WARNING", "No commands defined");
        } else {
            $commands = $devModel['commands'];
            // echo "commands=".json_encode($commands)."\n";
            $logicIds = [];
            foreach ($commands as $cmdJName => $cmd) {
                // New syntax: "<cmdJName>": { "use": "<fileName>", "params": "X=1&Y=2" }
                $cmdModName = $cmd['use'];
                $cmdModPath = commandsDir."/".$cmdModName.".json";
                if (!file_exists($cmdModPath)) {
                    $error = newDevError($devModName, "ERROR", "Unknown command JSON ".$cmdModName.".json");
                    $missingCmds++;
                }

                /* Updating list of unused commands models */
                $i = array_search($cmdModName, $unusedCmds, true);
                if ($i !== false)
                    unset($unusedCmds[$i]); // $cmdModName is used

                // Check command keys
                $validCmdKeys = ['use', 'params', 'isVisible', 'isHistorized', 'execAtCreation', 'execAtCreationDelay', 'nextLine', 'template', 'subType', 'unit', 'minValue', 'maxValue', 'genericType', 'logicalId', 'invertBinary', 'historizeRound', 'calculValueOffset'];
                array_push($validCmdKeys, 'repeatEventManagement', 'listValue');
                array_push($validCmdKeys, 'returnStateTime', 'returnStateValue', 'Polling');
                array_push($validCmdKeys, 'trigOut', 'trigOutOffset', 'notStandard', 'valueOffset');
                array_push($validCmdKeys, 'value');
                foreach ($cmd as $key2 => $value2) {
                    if (in_array($key2, $validCmdKeys)) {
                        // if ($key2 == 'subType') {
                        //     // TODO: How to know cmd type ?
                        //     if (!cmdSubTypeIsOk($type, $value2))
                        //         $error = newDevError($devModName, "ERROR", "Invalid '".$key2."' cmd key value for '".$key."' Jeedom command");
                        // }
                        continue;
                    }
                    if (substr($key2, 0, 7) == "comment")
                        continue;
                    $error = newDevError($devModName, "ERROR", "Invalid '".$key2."' cmd key for '".$cmdJName."' Jeedom command");
                }

                // act_zbConfigureReporting2 ... checking attribute type vs attribute
                if ($cmd['use'] == "act_zbConfigureReporting2") {
                    $params = $cmd['params'];
                    $pArr = explode('&', $params);
                    $clustId = '';
                    $attrId = '';
                    $attrType = '';
                    foreach ($pArr as $p) {
                        $pArr2 = explode('=', $p); // pX=vX to array of pX & vX
                        if ($pArr2[0] == 'clustId')
                            $clustId = $pArr2[1];
                        else if ($pArr2[0] == 'attrId')
                            $attrId = $pArr2[1];
                        else if ($pArr2[0] == 'attrType')
                            $attrType = $pArr2[1];
                    }
                    $attr = zbGetZCLAttribute($clustId, $attrId);
                    if ($attr !== false) {
                        $correctAttrType = sprintf("%02X", $attr['dataType']);
                        if ($attrType != $correctAttrType) {
                            $error = newDevError($devModName, "ERROR", "Cmd '${cmdJName}': Invalid attribute type '${attrType}' for clust ${clustId} attr ${attrId}");
                        }
                    }
                }

                // Now loading cmd model
                checkCmd($devModName, $devModel, $cmdJName, $cmdModPath, $logicIds);
            }

            if ($error)
                step('E');
            else
                step('.');
        }

        /* OBSOLETE SOON: Tuya specific checks */
        // TODO: To be completed => OBSOLETE soon. Will be replaced by 'fromDevice'
        // if (isset($dev[$devModName]['tuyaEF00'])) {
        //     foreach ($dev[$devModName]['tuyaEF00'] as $key => $value) {
        //         if ($key == 'fromDevice') {
        //             foreach ($dev[$devModName]['tuyaEF00']['fromDevice'] as $key2 => $value2) {
        //                 if (!isset($value2['function'])) {
        //                     $error = newDevError($devModName, "ERROR", "Missing 'function' for tuyaEF00/fromDevice");
        //                     continue;
        //                 }
        //                 $func = $value2['function'];
        //                 $supportedFunc = ['rcvValue', 'rcvValueDiv', 'rcvValueMult', 'rcvValue0Is1'];
        //                 if (!in_array($func, $supportedFunc)) {
        //                     $error = newDevError($devModName, "ERROR", "Invalid function '".$func."' for tuyaEF00/fromDevice");
        //                     continue;
        //                 }
        //                 if ($func == 'rcvValueDiv') {
        //                     if (!isset($value2['div'])) {
        //                         $error = newDevError($devModName, "ERROR", "Missing 'div' for DP '".$key2."' in tuyaEF00/fromDevice");
        //                         continue;
        //                     }
        //                 }
        //             }
        //             continue;
        //         }
        //         newDevError($devModName, "ERROR", "Invalid Tuya key '".$key."'");
        //     }
        // }

        /* Custom cluster/attribute specific checks */
        // TODO: To be completed
        /* Generic format for private clusters/commands reminder
        "private": {
            "ED00": {
                "type": "tuya-zosung"
            },
            "EF00": {
                "type": "tuya",
                "05": { // DP
                    "function": "rcvValue",
                    "info": "01-measuredValue"
                },
            },
            "0000-FF01": { // CLUSTID-ATTRID Xiaomi with tag/type decode
                "type": "xiaomi",
                "01-21": { // Tag-type
                    "func": "numberDiv",
                    "div": 1000,
                    "info": "0001-01-0020"
                }
            },
            "FCC0-0112": { // CLUSTID-ATTRID Xiaomi
                "type": "xiaomi",
                "info": "01-illumAndPresence",
                "comment": "Illuminance + motion detection"
            },
            "FCC0-00F7": { // CLUSTID-ATTRID for struct/4C
                "type": "xiaomi",
                "struct": 1, // Indicates to handle attr FCC0-00F7 as a 4C/structure
                "01-21": { // <type>-<data>
                    "func": "numberDiv",
                    "div": 1000,
                    "info": "0001-01-0020",
                    "comment": "Battery volt"
                }
            }
        } */
        if (isset($dev[$devModName]['private'])) {
            $private = $dev[$devModName]['private'];
            foreach ($private as $pKey => $pVal) {
                if (!isset($pVal['type'])) {
                    newDevError($devModName, "ERROR", "Private support: Missing 'type'");
                    continue;
                }

                $pKeyLen = strlen($pKey);
                $pType = $pVal['type'];

                if ($pType == "xiaomi") {
                    if ($pKeyLen != 9) { // Expecting CLUSTID-ATTRID
                        newDevError($devModName, "ERROR", "Xiaomi private support: Invalid key '${pKey}'");
                        continue;
                    }

                    if (isset($pVal['struct'])) {
                        // 4C/struct case
                    } else if (isset($pVal['info'])) {
                        // info + function case
                    } else {
                        // tag-type case
                        foreach ($private[$pKey] as $tt => $tt2) {
                            // echo "LA tt=${tt}, tt2=".json_encode($tt2)."\n";
                            if (substr($tt, 0, 7) == "comment")
                                continue;
                            if ($tt == "type") {
                                if ($tt2 != "xiaomi")
                                    newDevError($devModName, "ERROR", "Xiaomi private support: 'type' should be 'xiaomi' for '".$tt."'");
                                continue;
                            }
                            $ttLen = strlen($tt);
                            if ($ttLen != 5) { // Expecting 'tag-type' (TA-TY)
                                newDevError($devModName, "ERROR", "Xiaomi private support: Unexpected entry '".$tt."'");
                                continue;
                            }
                            $func = $tt2['func'];
                            if (($func == 'numberDiv') && !isset($tt2['div']))
                                newDevError($devModName, "ERROR", "Xiaomi private support: Missing 'div' for '".$tt."'");
                            else if (($func == 'numberMult') && !isset($tt2['mult']))
                                newDevError($devModName, "ERROR", "Xiaomi private support: Missing 'mult' for '".$tt."'");
                        }
                    }
                }

                /* Tuya DP case
                    "EF00": {
                        "type": "tuya",
                        "05": { // DP
                            "function": "rcvValue",
                            "info": "01-measuredValue"
                        },
                    },
                 */
                else if ($pType == "tuya") {
                    if ($pKeyLen != 4) { // Expecting CLUSTID
                        newDevError($devModName, "ERROR", "Tuya DP private support: Invalid key '${pKey}'");
                        continue;
                    }

                    foreach ($private[$pKey] as $dpId => $dpVal) {
                        if ($dpId == "type")
                            continue;

                        if (!isset($dpVal['function'])) {
                            $error = newDevError($devModName, "ERROR", "Missing 'function' for private/${pKey}");
                            continue;
                        }
                        $func = $dpVal['function'];
                        $supportedFunc = ['rcvValue', 'rcvValueDiv', 'rcvValueMult', 'rcvValue0Is1'];
                        if (!in_array($func, $supportedFunc)) {
                            $error = newDevError($devModName, "ERROR", "Invalid function '${func}' for private/${pKey} DP ${dpId}");
                            continue;
                        }
                        if ($func == 'rcvValueDiv') {
                            if (!isset($dpVal['div'])) {
                                $error = newDevError($devModName, "ERROR", "Missing 'div' for DP '${dpId}' in private/${pKey}");
                                continue;
                            }
                        }
                    }
                }

                /* Tuya Zosung case (ex: TS1201/IR remote controls)
                    "private": {
                        "ED00": {
                            "type": "tuya-zosung"
                        }
                    }
                 */
                else if ($pType == "tuya-zosung") {
                    // Currently no other key than 'type'
                }
            }
        }

        /* Checking device model top level keys */
        $validDevKeys = ['type', 'manufacturer', 'zbManufacturer', 'model', 'timeout', 'category', 'configuration', 'commands', 'isVisible', 'alternateIds', 'customization'];
        array_push($validDevKeys, 'genericType', 'private', 'variables');
        foreach ($dev[$devModName] as $key => $value) {
            if (in_array($key, $validDevKeys))
                continue;
            if (substr($key, 0, 7) == "comment")
                continue;
            newDevError($devModName, "ERROR", "Invalid device top key '".$key."'");
        }
    } // End checkDeviceModel()

    // Check command model
    function checkCommandModel($cmdName, $cmd) {
        global $commandsList;

        if (!isset($cmd[$cmdName])) {
            newCmdError($cmdName, "ERROR", "Expecting '".$cmdName."' command top key");
            return;
        }

        $c = $cmd[$cmdName];
        if (!isset($c['type'])) {
            newCmdError($cmdName, "ERROR", "Missing 'type' field (info or action)");
            return;
        }

        // Checking 'type'
        $type = $c['type'];
        if (($type == "action") && ($type == "info")) {
            newCmdError($cmdName, "ERROR", "Invalid 'type' value ".$type." ('info' or 'action' allowed)");
            return;
        }

        // Checking 'subType'
        $subType = $c['subType'];
        if (!cmdSubTypeIsOk($type, $subType)) {
            newCmdError($cmdName, "ERROR", "Invalid cmd 'subType' value '".$subType."'");
            return;
        }

        if (!isset($c['configuration'])) {
            if ($type == "action") {
                newCmdError($cmdName, "ERROR", "Missing 'configuration' section for 'action' command");
                return;
            }
        } else {
            // 'configuration' section exists
            $conf = $c['configuration'];
            if ($type == "info") {
                if (!isset($c['logicalId']))
                    newCmdError($cmdName, "ERROR", "Missing 'logicalId' field for info command");
            } else {
                if (!isset($conf['topic']))
                    newCmdError($cmdName, "ERROR", "Missing 'configuration:topic' field");
            }
            if (isset($conf['execAtCreationDelay']) && (gettype($conf['execAtCreationDelay']) == "string")) {
                newCmdError($cmdName, "ERROR", "'execAtCreationDelay' must be number and NOT string");
            }

            /* Checking supported 'configuration' keywords */
            $supportedConfKeys = ['topic', 'request', 'historizeRound', 'execAtCreation', 'execAtCreationDelay', 'trigOut', 'trigOutOffset', 'repeatEventManagement', 'visibilityCategory'];
            array_push($supportedConfKeys, 'minValue', 'maxValue', 'calculValueOffset', 'valueOffset', 'AbeilleRejectValue', 'Polling', 'PollingOnCmdChange', 'PollingOnCmdChangeDelay', 'visibiltyTemplate');
            array_push($supportedConfKeys, 'returnStateTime', 'returnStateValue');
            foreach ($conf as $key => $value) {
                if (in_array($key, $supportedConfKeys))
                    continue;
                if (substr($key, 0, 7) == "comment")
                    continue;
                    newCmdError($cmdName, "ERROR", "Invalid command configuration key '".$key."'");
            }
        }

        /* Checking supported top level keywords */
        $supportedKeys = ['type', 'subType', 'logicalId', 'configuration', 'name', 'genericType', 'template', 'isVisible', 'isHistorized', 'invertBinary', 'unit', 'display', 'nextLine', 'value'];
        array_push($supportedKeys, 'disableTitle');
        foreach ($cmd[$cmdName] as $key => $value) {
            if (in_array($key, $supportedKeys))
                continue;
            if (substr($key, 0, 7) == "comment")
                continue;
                newCmdError($cmdName, "ERROR", "Invalid command top key '".$key."'");
        }
    } // End checkCommandModel()

    function buildDevModelsList() {
        echo "Building devices models list ...\n";
        global $devModList;
        $devModList = [];
        $dh = opendir(devicesDir);
        while (($dirEntry = readdir($dh)) !== false) {
             /* Ignoring some entries */
             if (in_array($dirEntry, array(".", "..")))
                continue;
            $fullPath = devicesDir.'/'.$dirEntry;
            if (!is_dir($fullPath))
                continue;

            $fullPath = devicesDir.'/'.$dirEntry.'/'.$dirEntry.".json";
            if (!file_exists($fullPath)) {
                echo "- ".$dirEntry.": path access ERROR\n";
                echo "  ".$fullPath."\n";
                continue;
            }

            $devModList[$dirEntry] = $fullPath;
        }
    }

    function buildAllCommandsList() {
        echo "Building all commands list ...\n";
        global $allCommandsList, $unusedCmds;
        $allCommandsList = [];
        $dh = opendir(commandsDir);
        while (($dirEntry = readdir($dh)) !== false) {
            /* Ignoring some entries */
            if (in_array($dirEntry, array(".", "..")))
                continue;
            if (pathinfo($dirEntry, PATHINFO_EXTENSION) != "json")
                continue;

            $fullPath = commandsDir.'/'.$dirEntry;
            if (!file_exists($fullPath)) {
                echo "- ".$dirEntry.": path access ERROR\n";
                echo "  ".$fullPath."\n";
                continue;
            }

            $dirEntry = substr($dirEntry, 0, -5); // Removing file extension
            $allCommandsList[$dirEntry] = $fullPath;
            // defaultCmds = Commands not in models but added automatically on creation
            $defaultCmds = ['inf_addr-Short', 'inf_addr-Ieee', 'inf_linkQuality', 'inf_online', 'inf_time-String', 'inf_time-Timestamp'];
            if (!in_array($dirEntry, $defaultCmds))
                $unusedCmds[] = $dirEntry;
        }
    }

    function getCommandModel($cmdFName, $newJCmdName = '') {
        $fullPath = commandsDir.'/'.$cmdFName.'.json';
        if (!file_exists($fullPath)) {
            return false;
        }

        $jsonContent = file_get_contents($fullPath);
        $cmd = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
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

        // Removing all comments to not treat them
        foreach ($cmd[$cmdJName] as $key => $data) {
            if (substr($key, 0, 7) == "comment")
                unset($cmd[$cmdJName][$key]);
            if ($key == "configuration") {
                foreach ($cmd[$cmdJName]['configuration'] as $key2 => $data2) {
                    if (substr($key2, 0, 7) == "comment")
                        unset($cmd[$cmdJName]['configuration'][$key2]);
                }
            }
        }

        return $cmd;
    }

    // /* Check use of commands */
    // function checkDeviceCommands($devModName, $fullPath) {
    //     $jsonContent = file_get_contents($fullPath);
    //     if ($jsonContent === false) {
    //         step('E');
    //         return;
    //     }
    //     $device = json_decode($jsonContent, true);
    //     if (json_last_error() != JSON_ERROR_NONE) {
    //         step('E');
    //         return;
    //     }

    //     $device = $device[$devModName]; // Removing top key

    //     if (!isset($device['commands'])) {
    //         step('.');
    //         return;
    //     }

    //     $cmds = $device['commands'];
    //     $error = false;
    //     $logicIds = [];
    //     // echo "cmds=".json_encode($cmds)."\n";
    //     foreach ($cmds as $cmdJName => $devCmd) {
    //         /* New command JSON format:
    //             "jeedom_cmd_name": {
    //                 "use": "json_cmd_name",
    //                 "params": "xxx"...
    //             } */
    //         $cmdFName = $devCmd['use']; // File name without '.json'
    //         echo "cmdFName=".json_encode($cmdFName)."\n";

    //         $newCmd = getCommandModel($cmdFName, $cmdJName);
    //         if ($newCmd === false)
    //             continue; // Cmd does not exist.
    //         $newCmd = $newCmd[$cmdJName]; // Remove top key

    //         // Checking Jeedom cmd name
    //         if (strpos($cmdJName, "/") !== false) {
    //             newDevError($devModName, "ERROR", "Invalid Jeedom cmd name '".$cmdJName."' ('/' is fordidden)");
    //             $error = true;
    //         }

    //         // Overload from device model
    //         // echo "devCmd=".json_encode($devCmd)."\n";
    //         if (isset($devCmd['logicalId']))
    //             $newCmd['logicalId'] = $devCmd['logicalId'];

    //         $newCmdText = json_encode($newCmd);
    //         echo "  BEFORE newCmdText=".json_encode($newCmdText)."\n";
    //         // echo "newCmdText=".$newCmdText."\n";
    //         if (isset($devCmd['params']) && (trim($devCmd['params']) != '')) {
    //             // Overwritting default settings with 'params' content
    //             $params = explode('&', $devCmd['params']); // ep=01&clustId=0000 => ep=01, clustId=0000
    //             foreach ($params as $p) {
    //                 list($pName, $pVal) = explode("=", $p);
    //                 // $pName = strtoupper($pName);
    //                 $newCmdText = str_ireplace('#'.$pName.'#', $pVal, $newCmdText);
    //             }
    //             $newCmd = json_decode($newCmdText, true);
    //         }
    //         echo "  Posst params newCmdText=".json_encode($newCmdText)."\n";

    //         // Checking all remaining #var# cases
    //         //  #EP# => replaced at Jeedom EQ creation (pairing)
    //         //  #select# => dynamic variable, replaced at run time
    //         //  #title# + #message# => dynamic variables, replaced at run time
    //         // echo "newCmdText=".$newCmdText."\n";
    //         while (true) {
    //             $start = strpos($newCmdText, "#"); // Start
    //             if ($start === false)
    //                 break;

    //             $len = strpos(substr($newCmdText, $start + 1), "#"); // Length
    //             if ($len === false) {
    //                 $error = newDevError($devModName, "ERROR", "No closing dash (#) for cmd '".$cmdJName."'");
    //                 break;
    //             }
    //             $len += 2;
    //             // echo "S=".$start.", L=".$len."\n";
    //             $var = substr($newCmdText, $start, $len);

    //             if ($var == "#EP#") {
    //                 if (!isset($device['configuration']['mainEP'])) {
    //                     $error = newDevError($devModName, "ERROR", "'#EP#' found but NO 'mainEP'");
    //                 }
    //             } else if ($var == "#select#") {
    //                 if (!isset($devCmd['listValue']) && !isset($newCmd['listValue'])) {
    //                     $error = newDevError($devModName, "ERROR", "Undefined 'listValue' for '#select#'");
    //                 }
    //             } else {
    //                 $allowed = ['#value#', '#slider#', '#title#', '#message#', '#color#', '#onTime#', '#IEEE#', '#addrIEEE#', '#ZigateIEEE#', '#ZiGateIEEE#', '#addrGroup#'];
    //                 // Tcharp38 note: don't know purpose of slider/title/message/color/onTime/GroupeEPx
    //                 if (!in_array($var, $allowed)) {
    //                     if (substr($var, 0, 9) != "#GroupeEP") {
    //                         $error = newDevError($devModName, "ERROR", "Missing '".$var."' variable data for cmd '".$cmdJName."'");
    //                     }
    //                 }
    //             }

    //             $newCmdText = substr($newCmdText, $start + $len);
    //         }
    //         echo "newCmdText=".json_encode($newCmdText)."\n";

    //         // Checking uniqness of logicalId
    //         $logicId = isset($newCmd['logicalId']) ? $newCmd['logicalId']: '';
    //         if ($logicId == '')
    //             $error = newDevError($devModName, "ERROR", "Undefined logical ID for '".$cmdJName."' cmd (model ".$cmdFName.")");
    //         else if (in_array($logicId, $logicIds))
    //             $error = newDevError($devModName, "ERROR", "Duplicated logical ID '".$logicId."' (cmd ".$cmdJName.")");
    //         else
    //             $logicIds[] = $logicId;
    //     }
    //     if ($error)
    //         step('E');
    //     else
    //         step('.');
    // }

    /* Check given command
       - devModName = Device model name (without '.json' ext)
       - devModel = Device full model (without top key)
       - cmdJName = Jeedom cmd name
       - logicIds = Array of cmd logic IDs
       - devCmd = Device model part of command
       - cmd = Command model */
    function checkCmd($devModName, $devModel, $cmdJName, $cmdModPath, &$logicIds) {

        $devCmd = $devModel['commands'][$cmdJName];
        $cmdModName = $devCmd['use'];

        $cmdModJson = file_get_contents($cmdModPath);
        $cmdMod = json_decode($cmdModJson, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newCmdError($cmdModName, 'ERROR', 'Corrupted JSON file');
            return;
        }
        $cmdMod = $cmdMod[$cmdModName]; // Removing top key
        $cmd = $cmdMod;
        // TODO: Cmd model should be overloaded by eq model customizations prior to check

        // echo "checkCmd devCmd=".json_encode($devCmd)."\n";
        // echo "  cmd=".json_encode($cmd)."\n";
        $error = false;

        // Checking Jeedom cmd name
        if (strpos($cmdJName, "/") !== false) {
            newDevError($devModName, "ERROR", "Invalid Jeedom cmd name '".$cmdJName."' ('/' is fordidden)");
            $error = true;
        }

        $newCmd = $cmd;

        // Removing any comments that may disturb analysis
        foreach($newCmd as $newCmdK => $newCmdV) {
            if (substr($newCmdK, 0, 7) == "comment")
                unset($newCmd[$newCmdK]);
        }
        if (isset($newCmd['configuration'])) {
            foreach($newCmd['configuration'] as $newCmdK => $newCmdV) {
                if (substr($newCmdK, 0, 7) == "comment")
                    unset($newCmd['configuration'][$newCmdK]);
            }
        }

        // Overload cmd model from device model customizations
        // echo "devCmd=".json_encode($devCmd)."\n";
        if (isset($devCmd['logicalId']))
            $newCmd['logicalId'] = $devCmd['logicalId'];
        if (isset($devCmd['listValue']))
            $newCmd['listValue'] = $devCmd['listValue'];
        if (isset($devCmd['subType']))
            $newCmd['subType'] = $devCmd['subType'];

        $newCmdText = json_encode($newCmd, JSON_UNESCAPED_SLASHES);
        // echo "  BEFORE newCmdText=".$newCmdText."\n";

        // Overload from 'params'
        if (isset($devCmd['params']) && (trim($devCmd['params']) != '')) {
            // Overwritting default settings with 'params' content
            $params = explode('&', $devCmd['params']); // ep=01&clustId=0000 => ep=01, clustId=0000
            foreach ($params as $p) {
                list($pName, $pVal) = explode("=", $p);
                // $pName = strtoupper($pName);
                $newCmdText = str_ireplace('#'.$pName.'#', $pVal, $newCmdText);
            }
            $newCmd = json_decode($newCmdText, true);
        }
        // echo "  POST params newCmdText=".$newCmdText."\n";

        // Checking all remaining #var# cases
        //  #EP# => replaced at Jeedom EQ creation (pairing)
        //  #select# => dynamic variable, replaced at run time
        //  #title# + #message# => dynamic variables, replaced at run time
        // echo "newCmdText=".$newCmdText."\n";
        while (true) {
            $start = strpos($newCmdText, "#"); // Start
            if ($start === false)
                break;

            $len = strpos(substr($newCmdText, $start + 1), "#"); // Length
            if ($len === false) {
                $error = newDevError($devModName, "ERROR", "No closing dash (#) for cmd '".$cmdJName."'");
                break;
            }
            $len += 2;
            // echo "S=".$start.", L=".$len."\n";
            $var = substr($newCmdText, $start, $len);
            $varUp = strtoupper($var);
            $varOk = false;
            // echo "  Var to identify=".$var."\n"; // Ex: '#message#'

            $validVars = ['#value#', '#slider#', '#title#', '#message#', '#color#', '#select#']; // Jeedom variables
            array_push($validVars, '#onTime#', '#IEEE#', '#addrIEEE#', '#ZigateIEEE#', '#ZiGateIEEE#', '#addrGroup#');
            if (in_array($var, $validVars)) {
                $varOk = true;
                if ($var == "#select#") {
                    if (!isset($devCmd['listValue']) && !isset($newCmd['listValue']))
                        $error = newDevError($devModName, "ERROR", "Undefined 'listValue' for '#select#'");
                }
            }
            if (!$varOk && ($var == "#EP#")) {
                $varOk = true;
                if (!isset($devModel['configuration']['mainEP']))
                    $error = newDevError($devModName, "ERROR", "'#EP#' found but NO 'mainEP'");
            }
            if (!$varOk && isset($devModel['variables'])) {
                // echo "  variables=".json_encode($devModel['variables'])."\n";
                // Removing leading & trailing '#'
                $v2 = substr($varUp, 1, -1);
                if (isset($devModel['variables'][$v2]))
                    $varOk = true;
            }
            if (!$varOk) {
                $error = newDevError($devModName, "ERROR", "Missing '${var}' variable data for cmd '${cmdJName}'");
            }

            $newCmdText = substr($newCmdText, $start + $len);
            // echo "  POST var newCmdText=".$newCmdText."\n";
        }

        // Checking uniqness of logicalId
        $logicId = isset($newCmd['logicalId']) ? $newCmd['logicalId']: '';
        if ($logicId == '')
            $error = newDevError($devModName, "ERROR", "Undefined logical ID for '".$cmdJName."' cmd (model ".$cmdFName.")");
        else if (in_array($logicId, $logicIds))
            $error = newDevError($devModName, "ERROR", "Duplicated logical ID '".$logicId."' (cmd ".$cmdJName.")");
        else
            $logicIds[] = $logicId;

        // If 'listValue', 'subType' should be 'select'
        if ((isset($newCmd["listValue"]) || isset($devCmd["listValue"])) && ($newCmd["subType"] != "select")) {
            $error = newDevError($devModName, "ERROR", "Wrong sub-type for 'listValue' in cmd '${cmdJName}'");
        }

        if ($error)
            step('E');
        else
            step('.');
    }

    /* If JSON name not given on cmd line, parsing all
       devices & commands */
    for ($i = 1; $i < $argc; $i++) {
        $modName = $argv[$i];
        // $fullPath = devicesDir.'/'.$modName;
        // if (!is_dir($fullPath)) {
        //     echo "- ".$modName.": path access ERROR\n";
        //     echo "  ".$fullPath."\n";
        //     exit;
        // }

        $fullPath = devicesDir.'/'.$modName.'/'.$modName.".json";
        if (!file_exists($fullPath)) {
            echo "- ".$modName.": path access ERROR\n";
            echo "  ".$fullPath."\n";
            exit;
        }

        $devModList[$modName] = $fullPath;
        break;
    }
    if (count($devModList) == 0) {
        buildDevModelsList();
        buildAllCommandsList();
    }

    // echo "devl=".json_encode($devModList)."\n";
    echo "Checking devices models syntax\n- ";
    $idx = 2;
    foreach ($devModList as $devModName => $fullPath) {
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newDevError($devModName, 'ERROR', 'Corrupted JSON file');
            step('E');
        } else
            checkDeviceModel($devModName, $content);
    }

    // TODO: Should be USED cmds only
    echo "\nChecking ALL commands syntax\n- ";
    $idx = 2;
    foreach ($allCommandsList as $entry => $fullPath) {
        step('.');
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newCmdError($entry, 'ERROR', 'Corrupted JSON file');
            continue;
        }
        checkCommandModel($entry, $content);
    }

    // echo "\nChecking command variables\n- ";
    // $idx = 2;
    // foreach ($devModList as $devModName => $fullPath) {
    //     checkDeviceCommands($devModName, $fullPath);
    // }

    $nbErrors = sizeof($cmdErrors);
    echo "\n\nCommands errors summary (".$nbErrors." errors)\n";
    if ($nbErrors != 0 ) {
        $f = "";
        foreach ($cmdErrors as $e) {
            if ($f != $e['file']) {
                echo "- ".$e['file']."\n";
                $f = $e['file'];
            }
            echo "  ".$e['type'].": ".$e['msg']."\n";
        }
    } else
        echo "= None\n";

    $nbErrors = sizeof($devErrors);
    echo "\nDevices errors summary (".$nbErrors." errors)\n";
    if ($nbErrors != 0 ) {
        $f = "";
        foreach ($devErrors as $e) {
            if ($f != $e['file']) {
                echo "- ".$e['file']."\n";
                $f = $e['file'];
            }
            echo "  ".$e['type'].": ".$e['msg']."\n";
        }
        echo "= ".$nbErrors." errors\n";
    } else
        echo "= None\n";

    echo "\nList of unused/obsolete commands\n";
    foreach ($unusedCmds as $idx => $cmdName) {
        echo "- ".$cmdName.".json\n";
    }
?>
