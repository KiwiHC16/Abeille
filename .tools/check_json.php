<?php
    /* Check JSON files */

    define('devicesDir', __DIR__.'/../core/config/devices');
    $devicesList = [];

    function checkDevice() {

    }

    function checkCommand() {

    }

    /* If JSON name not given on cmd line, parsing all
       devices & commands */
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

            $devicesList[] = $fullPath;
        }
    }

    buildDevicesList();

    // echo "devl=".json_encode($devicesList)."\n";
    echo "Checking devices\n";
    $errors = 0;
    foreach ($devicesList as $fullPath) {
        echo "- ".$fullPath."\n";
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            echo "= Corrupted JSON file\n";
            $errors++;
        }
    }
    if ($errors != 0)
        echo "= WARNING: Some corrupted devices found\n";
    else
        echo "= Devices ok\n";
?>
