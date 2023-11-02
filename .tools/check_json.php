<?php
    /* Check JSON files */

    define('devicesDir', __DIR__.'/../core/config/devices');
    define('commandsDir', __DIR__.'/../core/config/commands');

    // eqLogic categories
    // {"heating":"1","security":"0","energy":"0","light":"0","opening":"0","automatism":"0","multimedia":"0","default":"0"}
    $eqCategories = ['heating', 'security', 'energy', 'light', 'opening', 'automatism', 'multimedia', 'default'];

    // Cmd 'action' & 'info' subtypes
    $actSubTypes = ['other', 'slider', 'message', 'color', 'select'];
    $infSubTypes = ['numeric', 'binary', 'string'];

    $devicesList = [];
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
            "type" => $type,
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
    function checkDevice($devName, $dev) {
        global $missingCmds;
        global $commandsList;

        if (!isset($dev[$devName])) {
            newDevError($devName, "ERROR", "Corruped JSON. Expecting '".$devName."' top key");
            step('E');
            return;
        }
        // echo "dev=".json_encode($dev)."\n";

        $error = false;

        // Checking 'type'
        if (!isset($dev[$devName]['type'])) {
            $error = newDevError($devName, "ERROR", "No equipment 'type' defined");
        }

        // Checking 'genericType'
        if (isset($dev[$devName]['genericType'])) {
            $genType = $dev[$devName]['genericType'];
            $validGenTypes = ['Other', 'Battery', 'Camera', 'Heating', 'Electricity', 'Environment', 'Generic', 'Light', 'Mode', 'Multimedia', 'Weather', 'Opening', 'Outlet', 'Robot', 'Security', 'Thermostat', 'Fan', 'Shutter'];
            if (!in_array($genType, $validGenTypes))
                $error = newDevError($devName, "ERROR", "Invalid 'genericType' defined: ".$genType);
        }

        // Checking 'category'
        if (!isset($dev[$devName]['category'])) {
            $error = newDevError($devName, "ERROR", "No 'category' defined");
        } else {
            $allCats = $dev[$devName]['category'];
            // $allowed = ['heating', 'security', 'energy', 'light', 'opening', 'automatism', 'multimedia', 'other'];
            global $eqCategories;
            foreach($allCats as $cat => $catEn) {
                if (in_array($cat, $eqCategories))
                    continue;
                $error = newDevError($devName, "ERROR", "Unexpected '".$cat."' category.");
            }
        }

        // Checking 'configuration'
        if (!isset($dev[$devName]['configuration'])) {
            $error = newDevError($devName, "WARNING", "No configuration defined");
        } else {
            $config = $dev[$devName]['configuration'];

            /* Checking icon */
            $icon = "";
            if (!isset($config['icon'])) {
                if (isset($config['icone'])) {
                    $error = newDevError($devName, "ERROR", "'icone' is obsolete. Use 'icon' instead.");
                    $icon = $config['icone'];
                }
            } else
                $icon = $config['icon'];
            if ($icon == "")
                $error = newDevError($devName, "ERROR", "No 'icon' defined.");
            else {
                $icon = "images/node_".$icon.".png";
                if (!file_exists($icon)) {
                    $error = newDevError($devName, "ERROR", "Missing icon '".$icon."' file.");
                }
            }

            if (!isset($config['mainEP']))
                $error = newDevError($devName, "ERROR", "Missing 'configuration:mainEP'");
            else if (!ctype_xdigit($config['mainEP']))
                $error = newDevError($devName, "ERROR", "'configuration:mainEP' should be hexa string. #EP# not allowed.");

            /* Checking 'configuration' fields validity */
            $supportedKeys = ['icon', 'mainEP', 'trig', 'trigOffset', 'batteryType', 'poll', 'lastCommunicationTimeOut', 'paramType'];
            foreach ($config as $fieldName => $fieldValue) {
                if (substr($fieldName, 0, 7) == "groupEP")
                    continue; // Allowed
                if (!in_array($fieldName, $supportedKeys))
                    $error = newDevError($devName, "ERROR", "Invalid '".$fieldName."' configuration field");
            }
        }

        // Checking 'commands'
        $unusedCmds = &$GLOBALS['unusedCmds'];
        if (!isset($dev[$devName]['commands'])) {
            $error = newDevError($devName, "WARNING", "No commands defined");
        } else {
            $commands = $dev[$devName]['commands'];
            // echo "commands=".json_encode($commands)."\n";
            $logicIds = [];
            foreach ($commands as $cmdJName => $cmd) {
                // New syntax: "<cmdJName>": { "use": "<fileName>" }
                $cmdModel = $cmd['use'];
                $path = commandsDir."/".$cmdModel.".json";
                if (!file_exists($path)) {
                    $error = newDevError($devName, "ERROR", "Unknown command JSON ".$cmdModel.".json");
                    $missingCmds++;
                }

                /* Updating list of unused commands models */
                $i = array_search($cmdModel, $unusedCmds, true);
                if ($i !== false)
                    unset($unusedCmds[$i]); // $cmdModel is used

                // List of supported command keys
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
                        //         $error = newDevError($devName, "ERROR", "Invalid '".$key2."' cmd key value for '".$key."' Jeedom command");
                        // }
                        continue;
                    }
                    if (substr($key2, 0, 7) == "comment")
                        continue;
                    $error = newDevError($devName, "ERROR", "Invalid '".$key2."' cmd key for '".$cmdJName."' Jeedom command");
                }
            }

            if ($error)
                step('E');
            else
                step('.');
        }

        /* Tuya specific checks */
        // TODO: To be completed => OBSOLETE soon. Will be replaced by 'fromDevice'
        if (isset($dev[$devName]['tuyaEF00'])) {
            foreach ($dev[$devName]['tuyaEF00'] as $key => $value) {
                if ($key == 'fromDevice') {
                    foreach ($dev[$devName]['tuyaEF00']['fromDevice'] as $key2 => $value2) {
                        if (!isset($value2['function'])) {
                            $error = newDevError($devName, "ERROR", "Missing 'function' for tuyaEF00/fromDevice");
                            continue;
                        }
                        $func = $value2['function'];
                        $supportedFunc = ['rcvValue', 'rcvValueDiv', 'rcvValueMult', 'rcvValue0Is1'];
                        if (!in_array($func, $supportedFunc)) {
                            $error = newDevError($devName, "ERROR", "Invalid function '".$func."' for tuyaEF00/fromDevice");
                            continue;
                        }
                        if ($func == 'rcvValueDiv') {
                            if (!isset($value2['div'])) {
                                $error = newDevError($devName, "ERROR", "Missing 'div' for DP '".$key2."' in tuyaEF00/fromDevice");
                                continue;
                            }
                        }
                    }
                    continue;
                }
                newDevError($devName, "ERROR", "Invalid Tuya key '".$key."'");
            }
        }

        /* Xiaomi specific checks */
        // TODO: To be completed => OBSOLETE soon. Will be replaced by 'fromDevice'
        if (isset($dev[$devName]['xiaomi'])) {
        }

        /* Custom cluster/attribute specific checks */
        // TODO: To be completed
        // WORK ONGOING !!!
        /* Generic format for private clusters/commands reminder
        "fromDevice": {
            "ED00": {
                "type": "tuya-zosung"
            },
            "0000-FF01": { // CLUSTID-ATTRID
                "type": "xiaomi",
                "01-21": {
                    "func": "numberDiv",
                    "div": 1000,
                    "info": "0001-01-0020"
                }
            },
            "EF00": {
                "type": "tuya",
                "05": { // DP
                    "function": "rcvValue",
                    "info": "01-measuredValue"
                },
            }
        } */
        if (isset($dev[$devName]['fromDevice'])) {
        }

        /* Checking top level supported keywords */
        $supportedKeys = ['type', 'manufacturer', 'zbManufacturer', 'model', 'timeout', 'category', 'configuration', 'commands', 'isVisible', 'alternateIds', 'tuyaEF00', 'customization', 'xiaomi'];
        array_push($supportedKeys, 'genericType', 'fromDevice');
        foreach ($dev[$devName] as $key => $value) {
            if (in_array($key, $supportedKeys))
                continue;
            if (substr($key, 0, 7) == "comment")
                continue;
            newDevError($devName, "ERROR", "Invalid device key '".$key."'");
        }
    }

    // Check command model
    function checkCommand($cmdName, $cmd) {
        global $commandsList;

        if (!isset($cmd[$cmdName])) {
            newCmdError($cmdName, "ERROR", "Expecting '".$cmdName."' top key");
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
    } // End checkCommand()

    function buildDevicesList() {
        echo "Building devices list ...\n";
        global $devicesList;
        $devicesList = [];
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

            $devicesList[$dirEntry] = $fullPath;
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

    /* Check use of commands */
    function checkDeviceCommands($devName, $fullPath) {
        $jsonContent = file_get_contents($fullPath);
        if ($jsonContent === false) {
            step('E');
            return;
        }
        $device = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            step('E');
            return;
        }

        $device = $device[$devName]; // Removing top key
        if (!isset($device['commands'])) {
            step('.');
            return;
        }

        $cmds = $device['commands'];
        $error = false;
        $logicIds = [];
        // echo "cmds=".json_encode($cmds)."\n";
        foreach ($cmds as $cmdJName => $devCmd) {
            /* New command JSON format:
                "jeedom_cmd_name": {
                    "use": "json_cmd_name",
                    "params": "xxx"...
                } */
            $cmdFName = $devCmd['use']; // File name without '.json'
            $newCmd = getCommandModel($cmdFName, $cmdJName);
            if ($newCmd === false)
                continue; // Cmd does not exist.
            $newCmd = $newCmd[$cmdJName]; // Remove top key

            // Checking Jeedom cmd name
            if (strpos($cmdJName, "/") !== false) {
                newDevError($devName, "ERROR", "Invalid Jeedom cmd name '".$cmdJName."' ('/' is fordidden)");
                $error = true;
            }

            // Overload from device model
            // echo "devCmd=".json_encode($devCmd)."\n";
            if (isset($devCmd['logicalId']))
                $newCmd['logicalId'] = $devCmd['logicalId'];

            $newCmdText = json_encode($newCmd);
            // echo "newCmdText=".$newCmdText."\n";
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

            // Checking all remaining #var# cases
            //  #EP# => replaced at during pairing
            //  #select# => dynamic variable, replaced at run time
            // echo "newCmdText=".$newCmdText."\n";
            while (true) {
                $start = strpos($newCmdText, "#"); // Start
                if ($start === false)
                    break;

                $len = strpos(substr($newCmdText, $start + 1), "#"); // Length
                if ($len === false) {
                    newDevError($devName, "ERROR", "No closing dash (#) for cmd '".$cmdJName."'");
                    $error = true;
                    break;
                }
                $len += 2;
                // echo "S=".$start.", L=".$len."\n";
                $var = substr($newCmdText, $start, $len);

                if ($var == "#EP#") {
                    if (!isset($device['configuration']['mainEP'])) {
                        newDevError($devName, "ERROR", "'#EP#' found but NO 'mainEP'");
                        $error = true;
                    }
                } else if ($var == "#select#") {
                    if (!isset($devCmd['listValue']) && !isset($newCmd['listValue'])) {
                        newDevError($devName, "ERROR", "Undefined 'listValue' for '#select#'");
                        $error = true;
                    }
                } else {
                    $allowed = ['#value#', '#slider#', '#title#', '#message#', '#color#', '#onTime#', '#IEEE#', '#addrIEEE#', '#ZigateIEEE#', '#ZiGateIEEE#', '#addrGroup#'];
                    // Tcharp38 note: don't know purpose of slider/title/message/color/onTime/GroupeEPx
                    if (!in_array($var, $allowed)) {
                        if (substr($var, 0, 9) != "#GroupeEP") {
                            newDevError($devName, "ERROR", "Missing '".$var."' variable data for cmd '".$cmdJName."'");
                            $error = true;
                        }
                    }
                }

                $newCmdText = substr($newCmdText, $start + $len);
            }

            // Checking uniqness of logicalId
            $logicId = isset($newCmd['logicalId']) ? $newCmd['logicalId']: '';
            if ($logicId == '')
                $error = newDevError($devName, "ERROR", "Undefined logical ID for '".$cmdJName."' cmd (model ".$cmdFName.")");
            else if (in_array($logicId, $logicIds))
                $error = newDevError($devName, "ERROR", "Duplicated logical ID '".$logicId."' (cmd ".$cmdJName.")");
            else
                $logicIds[] = $logicId;
        }
        if ($error)
            step('E');
        else
            step('.');
    }

    /* TODO: If JSON name not given on cmd line, parsing all
       devices & commands */
    for ($i = 1; $i < $argc; $i++) {
        $modName = $argv[$i];
        $fullPath = devicesDir.'/'.$modName;
        if (!is_dir($fullPath)) {
            echo "- ".$modName.": path access ERROR\n";
            echo "  ".$fullPath."\n";
            exit;
        }

        $fullPath = devicesDir.'/'.$modName.'/'.$modName.".json";
        if (!file_exists($fullPath)) {
            echo "- ".$modName.": path access ERROR\n";
            echo "  ".$fullPath."\n";
            exit;
        }

        $devicesList[$modName] = $fullPath;
        break;
    }
    if (count($devicesList) == 0) {
        buildDevicesList();
        buildAllCommandsList();
    }

    // echo "devl=".json_encode($devicesList)."\n";
    echo "Checking devices models syntax\n- ";
    $idx = 2;
    foreach ($devicesList as $devName => $fullPath) {
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newDevError($devName, 'ERROR', 'Corrupted JSON file');
            step('E');
        } else
            checkDevice($devName, $content);
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
        checkCommand($entry, $content);
    }

    echo "\nChecking command variables\n- ";
    $idx = 2;
    foreach ($devicesList as $devName => $fullPath) {
        checkDeviceCommands($devName, $fullPath);
    }

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
