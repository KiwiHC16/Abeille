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
        if (!isset($dev[$devName]['Commandes'])) {
            newDevError($devName, "WARNING", "No commands defined");
            return;
        }
        $cmds = $dev[$devName]['Commandes'];
        // echo "cmds=".json_encode($cmds)."\n";
        foreach ($cmds as $key => $value) {
            $path = commandsDir."/".$value.".json";
            if (!file_exists($path)) {
                newDevError($devName, "ERROR", "Unknown command ".$value);
                $missingCmds++;
            } else {
                $commandsList[$value] = $path;
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
        if (!isset($c['configuration'])) {
            newCmdError($cmdName, "ERROR", "Missing 'configuration' section");
        } else {
            if (!isset($c['configuration']['topic'])) {
                newCmdError($cmdName, "ERROR", "Missing 'configuration:topic' field");
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
