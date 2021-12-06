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

    function updateDevice($devName, $fullPath, $dev) {

        $devUpdated = false;

        if (!isset($dev[$devName]['type'])) {
            if (isset($dev[$devName]['nameJeedom'])) {
                $dev[$devName]['type'] = $dev[$devName]['nameJeedom'];
                unset($dev[$devName]['nameJeedom']);
                $devUpdated = true;
                echo "  'nameJeedom' renamed to 'type'.\n";
            }
        }

        if (!isset($dev[$devName]['category'])) {
            if (isset($dev[$devName]['Categorie'])) {
                $dev[$devName]['category'] = $dev[$devName]['Categorie'];
                unset($dev[$devName]['Categorie']);
                $devUpdated = true;
                echo "  'Categorie' renamed to 'category'.\n";
            }
        }

        if (!isset($dev[$devName]['configuration'])) {
            newDevError($devName, "ERROR", "No configuration defined");
        } else {
            $config = $dev[$devName]['configuration'];

            if (!isset($config['icon'])) {
                if (isset($config['icone'])) {
                    $dev[$devName]['configuration']['icon'] = $dev[$devName]['configuration']['icone'];
                    unset($dev[$devName]['configuration']['icone']);
                    $devUpdated = true;
                    echo "  'icone' renamed to 'icon'.\n";
                }
            }

            if (!isset($config['batteryType'])) {
                if (isset($config['battery_type'])) {
                    $dev[$devName]['configuration']['batteryType'] = $dev[$devName]['configuration']['battery_type'];
                    unset($dev[$devName]['configuration']['battery_type']);
                    $devUpdated = true;
                    echo "  'battery_type' renamed to 'batteryType'.\n";
                }
            }

            if (!isset($config['mainEP']))
                newDevError($devName, "ERROR", "No 'configuration:mainEP' defined");
            else if ($config['mainEP'] == '#EP#') {
                $dev[$devName]['configuration']['mainEP'] = "01";
                $devUpdated = true;
                echo "  'mainEP' updated from '#EP#' to '01'.\n";
            }

            if (isset($config['uniqId'])) {
                unset($dev[$devName]['configuration']['uniqId']);
                $devUpdated = true;
                echo "  Removed 'uniqId'.\n";
            }
        }

        if (!isset($dev[$devName]['commands'])) {
            if (isset($dev[$devName]['Commandes'])) {
                $dev[$devName]['commands'] = $dev[$devName]['Commandes'];
                unset($dev[$devName]['Commandes']);
                $devUpdated = true;
                echo "  'Commandes' renamed to 'commands'.\n";
            }
        }
        if (isset($dev[$devName]['commands'])) {
            $commands = $dev[$devName]['commands'];
            $commands2 = [];
            foreach ($commands as $key => $value) {
                if (substr($key, 0, 7) == "include") {
                    $cmdFName = $value;
                    $oldSyntax = true;
                } else {
                    // New syntax: "<jCmdName>": { "use": "<fileName>" }
                    $cmdFName = $value['use'];
                    $oldSyntax = false;
                }

                /* include ToggleEpxx => Toggle use zbCmd-0006-Toggle */
                // if (substr($cmdFName, 0, 8) == "ToggleEp") {
                //     $epId = substr($cmdFName, 8);
                //     $dev[$devName]['commands']['Toggle '.$epId]['use'] = "zbCmd-0006-Toggle";
                //     $dev[$devName]['commands']['Toggle '.$epId]['params'] = "ep=".$epId;
                //     $dev[$devName]['commands']['Toggle '.$epId]['nextLine'] = "after";
                //     if ($oldSyntax)
                //         unset($dev[$devName]['commands'][$key]);
                //     $devUpdated = true;
                //     echo "  Cmd '".$cmdFName."' replaced by 'zbCmd-0006-Toggle'.\n";
                //     continue;
                // }
                // if (($cmdFName == "getManufacturerName") && $oldSyntax) {
                //     unset($dev[$devName]['commands'][$key]);
                //     $devUpdated = true;
                //     echo "  Cmd '".$cmdFName."' REMOVED.\n";
                // }
                if (($cmdFName == "temperature") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0402-MeasuredValue",
                        "isVisible" => 1,
                        "isHistorized" => 1
                    );
                    $commands2["Temperature"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "getEtat") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbReadAttribute",
                        "params"=> "clustId=0006&attrId=0000"
                    );
                    $commands2["Get-Status"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Off") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbCmd-0006-Off",
                        "isVisible" => 1,
                    );
                    $commands2["Off"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "On") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbCmd-0006-On",
                        "isVisible" => 1,
                    );
                    $commands2["On"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "BindToPowerConfig") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbBindToZigate",
                        "params" => "clustId=0001",
                        "execAtCreation" => "Yes",
                        "execAtCreationDelay" => 10
                    );
                    $commands2["Bind-0001-ToZigate"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "BindShortToSmokeHeiman") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbBindToZigate",
                        "params" => "clustId=0500",
                        "execAtCreation" => "Yes",
                        "execAtCreationDelay" => 9
                    );
                    $commands2["Bind-0500-ToZigate"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "BindShortToZigateBatterie") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbBindToZigate",
                        "params" => "clustId=0001",
                        "execAtCreation" => "Yes",
                        "execAtCreationDelay" => 9
                    );
                    $commands2["Bind-0001-ToZigate"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "levelLight") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zb-0008-CurrentLevel",
                        "isVisible" => 1,
                    );
                    $commands2["CurrentLevel-0008"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "getLevel") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zbReadAttribute",
                        "params" => "clustId=0008&attrId=0000"
                    );
                    $commands2["Get-CurrentLevel"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "colorX") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0300-CurrentX",
                        "isVisible" => 1,
                    );
                    $commands2["CurrentX"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "colorY") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0300-CurrentY",
                        "isVisible" => 1,
                    );
                    $commands2["CurrentY"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "BasicApplicationVersion") && $oldSyntax) {
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' REMOVED.\n";
                } else if (($cmdFName == "getColorX") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zbReadAttribute",
                        "params" => "clustId=0300&attrId=0003"
                    );
                    $commands2["Get-ColorX"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                // } else if (($cmdFName == "temperatureLight") && $oldSyntax) {
                //     $cmdArr = Array(
                //         "use" => "zbCmd-0300-MoveToColorTemp",
                //         "isVisible" => 1,
                //     );
                //     $commands2["Set Temperature"] = $cmdArr;
                //     $devUpdated = true;
                //     echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "6000K") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zbCmd-0300-MoveToColorTemp",
                        "params" => "slider=6000",
                        "isVisible" => 1,
                    );
                    $commands2["Set 6000K"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Vert") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zbCmd-0300-MoveToColor",
                        "params" => "X=147A&Y=D709",
                        "isVisible" => 1,
                    );
                    $commands2["Set Green"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Blanc") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zbCmd-0300-MoveToColor",
                        "params" => "X=6000&Y=6000",
                        "isVisible" => 1,
                    );
                    $commands2["Set White"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Rouge") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zbCmd-0300-MoveToColor",
                        "params" => "X=AE13&Y=51EB",
                        "isVisible" => 1,
                    );
                    $commands2["Set Red"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else {
                    $commands2[$key] = $value;
                }
            }
            $dev[$devName]['commands'] = $commands2;
        }

        if (isset($dev[$devName]['Comment'])) {
            $dev[$devName]['comment'] = $dev[$devName]['Comment'];
            unset($dev[$devName]['Comment']);
            $devUpdated = true;
            echo "  'Comment' renamed to 'comment'.\n";
        }

        if ($devUpdated) {
            $text = json_encode($dev, JSON_PRETTY_PRINT);
            file_put_contents($fullPath, $text);
        }
    }

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
        if (isset($cmd2['configuration']['execAtCreationDelay'])) {
            if (gettype($cmd2['configuration']['execAtCreationDelay']) == "string") {
                $cmd2['configuration']['execAtCreationDelay'] = (int)$cmd2['configuration']['execAtCreationDelay'];
                $cmdUpdated = true;
                echo "  'execAtCreationDelay' type changed from string to integer.\n";
            }
        }

        if ($cmdUpdated) {
            $newCmd = array();
            $newCmd[$fileName] = $cmd2;
            $text = json_encode($newCmd, JSON_PRETTY_PRINT);
            file_put_contents($fullPath, $text);
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

    buildDevicesList();
    buildCommandsList();

    echo "\nUpdating devices if required ...\n";
    foreach ($devicesList as $entry => $fullPath) {
        echo "- ".$entry.".json\n";
        $jsonContent = file_get_contents($fullPath);
        $content = json_decode($jsonContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            newDevError($entry, 'ERROR', 'Corrupted JSON file');
            continue;
        }
        updateDevice($entry, $fullPath, $content);
    }

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
