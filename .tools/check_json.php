<?php
    /* Check JSON files */

    define('devicesDir', __DIR__.'/../core/config/devices');
    define('commandsDir', __DIR__.'/../core/config/commands');

    $devicesList = [];
    $commandsList = [];
    $missingCmds = 0;

    function checkDevice($devName, $dev) {
        global $missingCmds;

        if (!isset($dev[$devName])) {
            echo "  ERROR: Corruped JSON. Missing ".$devName." key\n";
            return;
        }
        // echo "dev=".json_encode($dev)."\n";
        $cmds = $dev[$devName]['Commandes'];
        // echo "cmds=".json_encode($cmds)."\n";
        foreach ($cmds as $key => $value) {
            $path = commandsDir."/".$value.".json";
            if (!file_exists($path)) {
                echo "  ERROR: Unknown command ".$value."\n";
                $missingCmds++;
            }
        }
    }

    function checkCommand() {

    }

    function buildDevicesList() {
        echo "Building devices list ...\n";
        global $devicesList;
        $devicesList = [];
        $dh = opendir(devicesDir);
        while (($dirEntry = readdir($dh)) !== false) {
             /* Ignoring some entries */
             if (in_array($dirEntry, array(".", "..", "listeCompatibilite.php")))
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

    function buildCommandsList() {
        echo "Building commands list ...\n";
        global $commandsList;
        $commandsList = [];
        $dh = opendir(commandsDir);
        while (($dirEntry = readdir($dh)) !== false) {
             /* Ignoring some entries */
             if (in_array($dirEntry, array(".", "..")))
                continue;

            $fullPath = commandsDir.'/'.$dirEntry;
            if (!file_exists($fullPath)) {
                echo "- ".$dirEntry.": path access ERROR\n";
                echo "  ".$fullPath."\n";
                continue;
            }

            $commandsList[$dirEntry] = $fullPath;
        }
    }

    /* If JSON name not given on cmd line, parsing all
       devices & commands */
    buildDevicesList();
    buildCommandsList();

    // echo "devl=".json_encode($devicesList)."\n";
    echo "Checking devices\n";
    $errors = 0;
    foreach ($devicesList as $entry => $fullPath) {
        echo "- ".$entry."\n";
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            echo "  ERROR: Corrupted JSON file\n";
            $errors++;
        }
        checkDevice($entry, $content);
    }
    if ($errors != 0)
        echo "= WARNING: Some corrupted devices found\n";
    if ($missingCmds != 0)
        echo "= WARNING: Some missing commands found\n";
    if (($errors == 0) && ($missingCmds == 0))
        echo "= Devices ok\n";
?>
