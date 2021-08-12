<?php
    /* Check JSON files */

    define('devicesDir', __DIR__.'/../core/config/devices');
    define('commandsDir', __DIR__.'/../core/config/commands');

    $devicesList = [];
    $commandsList = [];
    $missingCmds = 0;
    $devErrors = []; // Errors/warnings found in devices
    $cmdErrors = []; // Errors/warnings found in commands

    /* Register a new device error/warning */
    function newDevError($file, $type, $msg) {
        echo "  ".$type.": ".$msg."\n";

        global $devErrors;
        $e = array(
            "file" => $file,
            "type" => $type,
            "msg" => $msg
        );
        $devErrors[] = $e;
    }

    /* Register a new command error/warning */
    function newCmdError($file, $type, $msg) {
        echo "  ".$type.": ".$msg."\n";

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
            return;
        }
        // echo "dev=".json_encode($dev)."\n";

        if (!isset($dev[$devName]['type'])) {
            if (isset($dev[$devName]['nameJeedom']))
                newDevError($devName, "ERROR", "'nameJeedom' is obsolete. Use 'type' instead");
            else
                newDevError($devName, "ERROR", "No equipment 'type' defined");
        }

        if (!isset($dev[$devName]['category'])) {
            if (isset($dev[$devName]['Categorie']))
                newDevError($devName, "ERROR", "'Categorie' is obsolete. Use 'category' instead");
            else
                newDevError($devName, "ERROR", "No 'category' defined");
        } else {
            $allCats = $dev[$devName]['category'];
            $allowed = ['heating', 'security', 'energy', 'light', 'opening', 'automatism', 'multimedia', 'other'];
            foreach($allCats as $cat => $catEn) {
                if (in_array($cat, $allowed))
                    continue;
                    newDevError($devName, "ERROR", "Unexpected '".$cat."' category.");
                }
        }

        if (!isset($dev[$devName]['configuration'])) {
            newDevError($devName, "WARNING", "No configuration defined");
        } else {
            $config = $dev[$devName]['configuration'];

            /* Checking icon */
            $icon = "";
            if (!isset($config['icon'])) {
                if (isset($config['icone'])) {
                    newDevError($devName, "ERROR", "'icone' is obsolete. Use 'icon' instead.");
                    $icon = $config['icone'];
                }
            } else
                $icon = $config['icon'];
            if ($icon == "")
                newDevError($devName, "ERROR", "No 'icon' defined.");
            else {
                $icon = "images/node_".$icon.".png";
                if (!file_exists($icon)) {
                    newDevError($devName, "ERROR", "Missing icon '".$icon."' file.");
                }
            }

            if (!isset($config['mainEP']))
                newDevError($devName, "ERROR", "Missing 'configuration:mainEP'");
            else if (!ctype_xdigit($config['mainEP']))
                newDevError($devName, "ERROR", "'configuration:mainEP' should be hexa string. #EP# not allowed.");
        }

        if (isset($dev[$devName]['commands']))
            $commands = $dev[$devName]['commands'];
        else if (isset($dev[$devName]['Commandes']))
            $commands = $dev[$devName]['Commandes'];
        if (!isset($commands)) {
            newDevError($devName, "WARNING", "No commands defined");
        } else {
            // echo "commands=".json_encode($commands)."\n";
            foreach ($commands as $key => $value) {
                if (substr($key, 0, 7) == "include") {
                    $cmdFName = $value;
                } else {
                    // 'use'
                    $cmdFName = $value['use'];
                }
                $path = commandsDir."/".$cmdFName.".json";
                if (!file_exists($path)) {
                    newDevError($devName, "ERROR", "Unknown command JSON ".$cmdFName.".json");
                    $missingCmds++;
                } else {
                    $commandsList[$value] = $path;
                }
            }
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

        if (!isset($c['configuration'])) {
            newCmdError($cmdName, "ERROR", "Missing 'configuration' section");
            return;
        }

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

    function buildDevicesList() {
        echo "Building devices list ...\n";
        global $devicesList;
        $devicesList = [];
        $dh = opendir(devicesDir);
        while (($dirEntry = readdir($dh)) !== false) {
             /* Ignoring some entries */
             if (in_array($dirEntry, array(".", "..", "LISEZMOI.txt", "README.txt")))
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

    // function buildCommandsList() {
    //     echo "Building commands list ...\n";
    //     global $commandsList;
    //     $commandsList = [];
    //     $dh = opendir(commandsDir);
    //     while (($dirEntry = readdir($dh)) !== false) {
    //          /* Ignoring some entries */
    //          if (in_array($dirEntry, array(".", "..")))
    //             continue;

    //         $fullPath = commandsDir.'/'.$dirEntry;
    //         if (!file_exists($fullPath)) {
    //             echo "- ".$dirEntry.": path access ERROR\n";
    //             echo "  ".$fullPath."\n";
    //             continue;
    //         }

    //         $commandsList[$dirEntry] = $fullPath;
    //     }
    // }

    /* If JSON name not given on cmd line, parsing all
       devices & commands */
    buildDevicesList();
    // buildCommandsList();

    // echo "devl=".json_encode($devicesList)."\n";
    echo "Checking devices ...\n";
    foreach ($devicesList as $entry => $fullPath) {
        echo "- ".$entry."\n";
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newDevError($entry, 'ERROR', 'Corrupted JSON file');
            continue;
        }
        checkDevice($entry, $content);
    }

    echo "\nChecking used commands ...\n";
    foreach ($commandsList as $entry => $fullPath) {
        echo "- ".$entry."\n";
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newCmdError($entry, 'ERROR', 'Corrupted JSON file');
            continue;
        }
        checkCommand($entry, $content);
    }

    echo "\n";
    if (sizeof($devErrors) != 0 ) {
        echo "Devices errors summary\n";
        $f = "";
        foreach ($devErrors as $e) {
            if ($f != $e['file']) {
                echo "- ".$e['file']."\n";
                $f = $e['file'];
            }
            echo "  ".$e['type'].": ".$e['msg']."\n";
        }
    } else
        echo "= Devices ok\n";

    if (sizeof($cmdErrors) != 0 ) {
        echo "Commands errors summary\n";
        $f = "";
        foreach ($cmdErrors as $e) {
            if ($f != $e['file']) {
                echo "- ".$e['file']."\n";
                $f = $e['file'];
            }
            echo "  ".$e['type'].": ".$e['msg']."\n";
        }
    } else
        echo "= Commands ok\n";
?>
