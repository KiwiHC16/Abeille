<?php
    /* Update JSON files to be aligned with latest Abeille code */

    define('devicesDir', __DIR__.'/../core/config/devices');
    define('commandsDir', __DIR__.'/../core/config/commands');

    $devicesList = [];
    $commandsList = [];
    $missingCmds = 0;
    $devErrors = []; // Errors/warnings found in devices
    $cmdErrors = []; // Errors/warnings found in commands

    /* Register a new device error/warning */
    // function newDevError($file, $type, $msg) {
    //     echo "  ".$type.": ".$msg."\n";

    //     global $devErrors;
    //     $e = array(
    //         "file" => $file,
    //         "type" => $type,
    //         "msg" => $msg
    //     );
    //     $devErrors[] = $e;
    // }

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

    // function checkDevice($devName, $dev) {
    //     global $missingCmds;
    //     global $commandsList;

    //     if (!isset($dev[$devName])) {
    //         newDevError($devName, "ERROR", "Corruped JSON. Expecting '".$devName."' top key");
    //         return;
    //     }
    //     // echo "dev=".json_encode($dev)."\n";

    //     if (!isset($dev[$devName]['configuration'])) {
    //         newDevError($devName, "WARNING", "No configuration defined");
    //     } else {
    //         $config = $dev[$devName]['configuration'];
    //         if (isset($config['icone'])) {
    //             $icon = "images/node_".$config['icone'].".png";
    //             if (!file_exists($icon)) {
    //                 newDevError($devName, "ERROR", "Missing icon '".$icon."'");
    //             }
    //         }
    //     }

    //     if (!isset($dev[$devName]['Commandes'])) {
    //         newDevError($devName, "WARNING", "No commands defined");
    //         return;
    //     }
    //     $cmds = $dev[$devName]['Commandes'];
    //     // echo "cmds=".json_encode($cmds)."\n";
    //     foreach ($cmds as $key => $value) {
    //         $path = commandsDir."/".$value.".json";
    //         if (!file_exists($path)) {
    //             newDevError($devName, "ERROR", "Unknown command ".$value);
    //             $missingCmds++;
    //         } else {
    //             $commandsList[$value] = $path;
    //         }
    //     }
    // }

    function updateCommand($fileName, $fullPath, $cmd) {
        global $commandsList;

        foreach ($cmd as $cmdKey => $cmd2) {
            break;
        }

        if (isset($cmd2['type']))
            $type = $cmd2['type'];
        else if (isset($cmd2['Type'])) // Old name support
            $type = $cmd2['Type'];
        else {
            newCmdError($fileName, "ERROR", "Command type is undefined.");
            return;
        }

        $cmdUpdated = false;

        if (isset($cmd2['order'])) {
            unset($cmd2['order']);
            $cmdUpdated = true;
            echo "  Removed 'order'.\n";
        }
        if (isset($cmd2['Type'])) {
            $cmd2['type'] = $cmd2['Type'];
            unset($cmd2['Type']);
            $cmdUpdated = true;
            echo "  Renamed 'Type' to 'type'.\n";
        }

        if (!isset($cmd2['configuration'])) {
            newCmdError($fileName, "ERROR", "Missing 'configuration' section");
            return;
        }

        /* For info cmds, logicalId added = previous configuration:topic */
        if (($type == "info") && !isset($cmd2['logicalId'])) {
            if (!isset($cmd2['configuration']['topic'])) {
                newCmdError($fileName, "ERROR", "Missing 'logicalId' field for info cmd");
                return;
            }
            $cmd2['logicalId'] = $cmd2['configuration']['topic'];
            unset($cmd2['configuration']['topic']);
            $cmdUpdated = true;
            echo "  Moved 'configuration:topic' to 'logicalId'.\n";
        }
       if (($type == "action") && !isset($cmd2['configuration']['topic'])) {
            newCmdError($fileName, "ERROR", "Missing 'configuration:topic' field for action cmd");
        }
        if (isset($cmd2['configuration']['uniqId'])) {
            unset($cmd2['configuration']['uniqId']);
            $cmdUpdated = true;
            echo "  Removed 'configuration:uniqId'.\n";
        }

        if ($cmdUpdated) {
            $newCmd = array();
            $newCmd[$fileName] = $cmd2;
            $text = json_encode($newCmd, JSON_PRETTY_PRINT);
            file_put_contents($fullPath, $text);
        }
    }

    // function buildDevicesList() {
    //     echo "Building devices list ...\n";
    //     global $devicesList;
    //     $devicesList = [];
    //     $dh = opendir(devicesDir);
    //     while (($dirEntry = readdir($dh)) !== false) {
    //          /* Ignoring some entries */
    //          if (in_array($dirEntry, array(".", "..", "LISEZMOI.txt", "README.txt")))
    //             continue;

    //         $fullPath = devicesDir.'/'.$dirEntry.'/'.$dirEntry.".json";
    //         if (!file_exists($fullPath)) {
    //             echo "- ".$dirEntry.": path access ERROR\n";
    //             echo "  ".$fullPath."\n";
    //             continue;
    //         }

    //         $devicesList[$dirEntry] = $fullPath;
    //     }
    // }

    function buildCommandsList() {
        echo "Building commands list\n";
        global $commandsList;
        $commandsList = [];
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
            $commandsList[$dirEntry] = $fullPath;
        }
        echo "= Ok";
    }

    buildCommandsList();

    echo "\nUpdating commands if required ...\n";
    foreach ($commandsList as $entry => $fullPath) {
        echo "- ".$entry.".json\n";
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newCmdError($entry, 'ERROR', 'Corrupted JSON file');
            continue;
        }
        updateCommand($entry, $fullPath, $content);
    }
?>
