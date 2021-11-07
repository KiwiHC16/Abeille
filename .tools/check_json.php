<?php
    /* Check JSON files */

    define('devicesDir', __DIR__.'/../core/config/devices');
    define('commandsDir', __DIR__.'/../core/config/commands');

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
        if (!isset($dev[$devName]['type'])) {
            if (isset($dev[$devName]['nameJeedom']))
                $error = newDevError($devName, "ERROR", "'nameJeedom' is obsolete. Use 'type' instead");
            else
                $error = newDevError($devName, "ERROR", "No equipment 'type' defined");
        }

        if (!isset($dev[$devName]['category'])) {
            if (isset($dev[$devName]['Categorie']))
                $error = newDevError($devName, "ERROR", "'Categorie' is obsolete. Use 'category' instead");
            else
                $error = newDevError($devName, "ERROR", "No 'category' defined");
        } else {
            $allCats = $dev[$devName]['category'];
            $allowed = ['heating', 'security', 'energy', 'light', 'opening', 'automatism', 'multimedia', 'other'];
            foreach($allCats as $cat => $catEn) {
                if (in_array($cat, $allowed))
                    continue;
                    $error = newDevError($devName, "ERROR", "Unexpected '".$cat."' category.");
                }
        }

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
                if (!in_array($fieldName, $supportedKeys))
                    $error = newDevError($devName, "ERROR", "Invalid '".$fieldName."' configuration field");
            }
        }

        $unusedCmds = &$GLOBALS['unusedCmds'];

        if (isset($dev[$devName]['commands']))
            $commands = $dev[$devName]['commands'];
        else if (isset($dev[$devName]['Commandes']))
            $commands = $dev[$devName]['Commandes'];
        if (!isset($commands)) {
            $error = newDevError($devName, "WARNING", "No commands defined");
        } else {
            // echo "commands=".json_encode($commands)."\n";
            foreach ($commands as $key => $value) {
                if (substr($key, 0, 7) == "include") {
                    $cmdFName = $value;
                    $newSyntax = false;
                } else {
                    // New syntax: "<jCmdName>": { "use": "<fileName>" }
                    $cmdFName = $value['use'];
                    $newSyntax = true;
                }
                $path = commandsDir."/".$cmdFName.".json";
                if (!file_exists($path)) {
                    $error = newDevError($devName, "ERROR", "Unknown command JSON ".$cmdFName.".json");
                    $missingCmds++;
                }

                if ($newSyntax) {
                    $supportedKeys = ['use', 'params', 'isVisible', 'execAtCreation', 'execAtCreationDelay', 'nextLine', 'template', 'subType', 'unit'];
                    foreach ($value as $key2 => $value2) {
                        if (!in_array($key2, $supportedKeys))
                            $error = newDevError($devName, "ERROR", "Invalid '".$key2."' key for '".$key."' Jeedom command");
                    }
                }

                /* Updating list of unused commands */
                $i = array_search($cmdFName, $unusedCmds, true);
                if ($i !== false)
                    unset($unusedCmds[$i]); // $cmdFName is used
            }

            if ($error)
                step('E');
            else
                step('.');
        }

        /* Checking supported keywords */
        $supportedKeys = ['type', 'manufacturer', 'zbManufacturer', 'model', 'timeout', 'category', 'configuration', 'commands', 'isVisible', 'alternateIds'];
        foreach ($dev[$devName] as $key => $value) {
            if (in_array($key, $supportedKeys))
                continue;
            if (substr($key, 0, 7) == "comment")
                continue;
            newDevError($devName, "ERROR", "Invalid key '".$key."'");
        }
    }

    function checkCommand($cmdName, $cmd) {
        global $commandsList;

        if (!isset($cmd[$cmdName])) {
            newCmdError($cmdName, "ERROR", "Expecting '".$cmdName."' top key");
            return;
        }

        $c = $cmd[$cmdName];
        if (!isset($c['type'])) {
            newCmdError($cmdName, "ERROR", "Missing 'type' filed (info or action)");
            return;
        }
        $type = $c['type'];
        if (($type == "action") && ($type == "info")) {
            newCmdError($cmdName, "ERROR", "Invalid 'type' value ".$type." ('info' or 'action' allowed)");
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
        }
    }

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
            $unusedCmds[] = $dirEntry;
        }
    }

    function getCommandConfig($cmdFName, $newJCmdName = '') {
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

        return $cmd;
    }

    /* Check use of commands */
    function checkDevicecommands($devName, $fullPath) {
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

        $jsonCmds = $device['commands'];
        $error = false;
// echo "jsonCmds=".json_encode($jsonCmds)."\n";
        foreach ($jsonCmds as $cmd1 => $cmd2) {
            if (substr($cmd1, 0, 7) == "include") {
                /* Old command JSON commands syntax: "includeX": "json_cmd_name" */
                $newCmd = getCommandConfig($cmd2);
                if ($newCmd === false)
                    continue; // Cmd does not exist.
                $cmdJName = $cmd2;
                $newCmdText = json_encode($newCmd);
            } else {
                /* New command JSON format: "jeedom_cmd_name": { "use": "json_cmd_name", "params": "xxx"... } */
                $cmdFName = $cmd2['use']; // File name without '.json'
                $newCmd = getCommandConfig($cmdFName, $cmd1);
                if ($newCmd === false)
                    continue; // Cmd does not exist.
                $cmdJName = $cmd1;
                $newCmdText = json_encode($newCmd);

                if (isset($cmd2['params'])) {
                    // Overwritting default settings with 'params' content
                    $params = explode('&', $cmd2['params']); // ep=01&clustId=0000 => ep=01, clustId=0000
                    foreach ($params as $p) {
                        list($pName, $pVal) = explode("=", $p);
                        $pName = strtoupper($pName);
                        $newCmdText = str_replace('#'.$pName.'#', $pVal, $newCmdText);
                    }
                    $newCmd = json_decode($newCmdText, true);
                }
            }

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
// break;
        }
        if ($error)
            step('E');
        else
            step('.');
    }

    /* TODO: If JSON name not given on cmd line, parsing all
       devices & commands */
    buildDevicesList();
    buildAllCommandsList();

    // echo "devl=".json_encode($devicesList)."\n";
    echo "Checking devices syntax\n- ";
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

    echo "\n\nDevices errors summary\n";
    if (sizeof($devErrors) != 0 ) {
        $f = "";
        foreach ($devErrors as $e) {
            if ($f != $e['file']) {
                echo "- ".$e['file']."\n";
                $f = $e['file'];
            }
            echo "  ".$e['type'].": ".$e['msg']."\n";
        }
    } else
        echo "= None\n";

    echo "\nCommands errors summary\n";
    if (sizeof($cmdErrors) != 0 ) {
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

    echo "\nList of unused/obsolete commands\n";
    foreach ($unusedCmds as $idx => $cmdName) {
        echo "- ".$cmdName.".json\n";
    }
?>
