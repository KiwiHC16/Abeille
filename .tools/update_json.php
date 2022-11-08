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

        if (isset($dev[$devName]['alternateIds'])) {
            $ai = $dev[$devName]['alternateIds'];
            if (gettype($ai) == "string") {
                $sArr = explode(",", $ai);
                $ai = [];
                foreach ($sArr as $aId) {
                    $ai[$aId] = [];
                }
                $dev[$devName]['alternateIds'] = $ai;
                $devUpdated = true;
                echo "  'alternateIds' syntax updated.\n";
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

            if (isset($config['lastCommunicationTimeOut'])) {
                unset($dev[$devName]['configuration']['lastCommunicationTimeOut']);
                $devUpdated = true;
                echo "  Removed 'lastCommunicationTimeOut'.\n";
            }
        } // End 'configuration'

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

                // Cluster 0001 updates
                if (($cmdFName == "BindToPowerConfig") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbBindToZigate",
                        "params" => "clustId=0001",
                        "execAtCreation" => "Yes",
                        "execAtCreationDelay" => 10
                    );
                    $commands2["Bind-0001-ToZigate"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "getBatteryVolt") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbReadAttribute",
                        "params" => "clustId=0001&attrId=0020",
                    );
                    $commands2["Get Battery-Volt"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "getBattery") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbReadAttribute",
                        "params" => "clustId=0001&attrId=0021",
                    );
                    $commands2["Get Battery-Percent"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Batterie-Volt") && $oldSyntax) {
                    $commands2["Battery-Volt"] = Array(
                        "use"=> "zb-0001-BatteryVolt"
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Batterie-Volt-Konke") && $oldSyntax) {
                    $commands2["Battery-Volt"] = Array(
                        "use"=> "zb-0001-BatteryVolt"
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Batterie-Pourcent") && $oldSyntax) {
                    $commands2["Battery-Percent"] = Array(
                        "use"=> "zb-0001-BatteryPercent"
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "Batterie-Hue") && $oldSyntax) {
                    $commands2["Battery-Percent"] = Array(
                        "use"=> "zb-0001-BatteryPercent"
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                // Cluster 0003/Identify updates
                else if (($cmdFName == "Identify") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "Identify",
                        // "params" => "ep=03",
                        // "subType" => "numeric",
                        // "template" => "door",
                        // "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["Identify"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                // Cluster 0004/Groups updates
                else if (($cmdFName == "Group-Membership") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "Group-Membership",
                        // "params" => "ep=03",
                        // "subType" => "numeric",
                        // "template" => "door",
                        // "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["Groups"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                // Cluster 0006 updates
                else if (($cmdFName == "etat") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        // "params" => "ep=03",
                        // "subType" => "numeric",
                        // "template" => "door",
                        // "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["etat"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatDoor") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        // "params" => "ep=03",
                        // "subType" => "numeric",
                        "template" => "door",
                        "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["etat"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatSW1") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=04",
                        "subType" => "binary",
                        "template" => "badge",
                        "invertBinary" => 1,
                        "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["etat switch 1"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatSW2") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=05",
                        "subType" => "binary",
                        "template" => "badge",
                        "invertBinary" => 1,
                        "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["etat switch 2"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatSW3") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=06",
                        "subType" => "binary",
                        "template" => "badge",
                        "invertBinary" => 1,
                        "genericType" => "OPENING",
                        "isVisible" => 1
                    );
                    $commands2["etat switch 3"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatSwitch") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        // "params" => "ep=06",
                        "subType" => "binary",
                        "template" => "badge",
                        "invertBinary" => 1,
                        "genericType" => "SWITCH_STATE",
                        "isVisible" => 1
                    );
                    $commands2["etat"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatSwitchKonke") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        // "params" => "ep=06",
                        "subType" => "binary",
                        "template" => "badge",
                        // "invertBinary" => 1,
                        "genericType" => "SWITCH_STATE",
                        "isVisible" => 1
                    );
                    $commands2["etat"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ((substr($cmdFName, 0, 10) == "etatCharge") && $oldSyntax) {
                    $x = hexdec(substr($cmdFName, 10));
                    $ep = sprintf("%02X", $x + 1);
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=".$ep,
                        // "subType" => "numeric",
                        // "template" => "door",
                        "genericType" => "LIGHT_STATE",
                        "isVisible" => 1
                    );
                    $commands2["etat charge ".$x] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "etatLight") && $oldSyntax) {
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        // "params" => "ep=".$ep,
                        // "subType" => "numeric",
                        // "template" => "door",
                        // "genericType" => "LIGHT_STATE",
                        "isVisible" => 1
                    );
                    $commands2["etat"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ((substr($cmdFName, 0, 9) == "etatInter") && $oldSyntax) {
                    $x = hexdec(substr($cmdFName, 9));
                    $ep = sprintf("%02X", $x + 1);
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=".$ep,
                        "subType" => "numeric",
                        // "template" => "door",
                        // "genericType" => "LIGHT_STATE",
                        "isVisible" => 1
                    );
                    $commands2["etat inter ".$x] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ((($cmdFName == "etatEp01out") || ($cmdFName == "etatEp02out") || ($cmdFName == "etatEp03out") || ($cmdFName == "etatEp04out")
                || ($cmdFName == "etatEp05out") || ($cmdFName == "etatEp06out") || ($cmdFName == "etatEp07out") || ($cmdFName == "etatEp08out"))
                        && $oldSyntax) {
                    $ep = hexdec(substr($cmdFName, 6, 2));
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=".$ep,
                        "subType" => "binary",
                        // "template" => "door",
                        "genericType" => "SWITCH_STATE",
                        "isVisible" => 1
                    );
                    $commands2["Digital Output ".$ep] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ((($cmdFName == "etatEp01in") || ($cmdFName == "etatEp02in") || ($cmdFName == "etatEp03in") || ($cmdFName == "etatEp04in")
                || ($cmdFName == "etatEp05in") || ($cmdFName == "etatEp06in") || ($cmdFName == "etatEp07in") || ($cmdFName == "etatEp08in"))
                        && $oldSyntax) {
                    $ep = hexdec(substr($cmdFName, 6, 2));
                    $cmdArr = Array(
                        "use" => "zb-0006-OnOff",
                        "params" => "ep=".$ep,
                        "subType" => "binary",
                        // "template" => "door",
                        "genericType" => "SWITCH_STATE",
                        "isVisible" => 1
                    );
                    $commands2["Digital Input ".$ep] = $cmdArr;
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
                }

                // Cluster 0008 updates
                else if (($cmdFName == "setReportLevel") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbConfigureReporting",
                        "params" => "clustId=0008&attrType=20&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=",
                        "execAtCreation" => "Yes",
                        "execAtCreationDelay" => 11
                    );
                    $commands2["SetReporting-0008-0000"] = $cmdArr;
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
                } else if (($cmdFName == "levelVoletStop") && $oldSyntax) {
                    $commands2["Stop"] = Array(
                        "use" => "zbCmdG-0008-StopWithOnOff",
                        // "params" => "clustId=0008&attrId=0000",
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "setLevelVoletUp") && $oldSyntax) {
                    $commands2["Up"] = Array(
                        "use" => "zbCmd-0008-UpOpen",
                        // "params" => "clustId=0008&attrId=0000",
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "setLevelVoletDown") && $oldSyntax) {
                    $commands2["Down"] = Array(
                        "use" => "zbCmd-0008-DownClose",
                        // "params" => "clustId=0008&attrId=0000",
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                // Cluster 0102 updates
                else if (($cmdFName == "WindowsCoveringStop") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbCmd-0102-Stop",
                        // "params" => "clustId=0008&attrType=20&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=",
                        // "execAtCreation" => "Yes",
                        // "execAtCreationDelay" => 11
                    );
                    $commands2["Stop"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "WindowsCoveringDown") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbCmd-0102-DownClose",
                        // "params" => "clustId=0008&attrType=20&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=",
                        // "execAtCreation" => "Yes",
                        // "execAtCreationDelay" => 11
                    );
                    $commands2["Down"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "WindowsCoveringUp") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbCmd-0102-UpOpen",
                        // "params" => "clustId=0008&attrType=20&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=",
                        // "execAtCreation" => "Yes",
                        // "execAtCreationDelay" => 11
                    );
                    $commands2["Up"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                // Cluster 0300 updates
                else if (($cmdFName == "colorX") && $oldSyntax) {
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
                }

                // Cluster 0500 updates
                else if (($cmdFName == "BindShortToSmokeHeiman") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "zbBindToZigate",
                        "params" => "clustId=0500",
                        "execAtCreation" => "Yes",
                        "execAtCreationDelay" => 9
                    );
                    $commands2["Bind-0500-ToZigate"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                // Cluster 0B04 updates
                else if (($cmdFName == "getPlugA") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "poll-0B04-0508",
                    );
                    $commands2["Poll 0B04-0508"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "getPlugPower") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "poll-0B04-050B",
                    );
                    $commands2["Poll 0B04-050B"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if (($cmdFName == "getPlugV") && $oldSyntax) {
                    $cmdArr = Array(
                        "use"=> "poll-0B04-0505",
                    );
                    $commands2["Poll 0B04-0505"] = $cmdArr;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                else {
                    $commands2[$key] = $value;
                }
            }
            $dev[$devName]['commands'] = $commands2;
        } // End 'commands'

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
        if (isset($cmd2['unite'])) {
            $cmd2['unit'] = $cmd2['unite'];
            unset($cmd2['unite']);
            $cmdUpdated = true;
            echo "  Renamed 'unite' to 'unit'.\n";
        }
        if (isset($cmd2['generic_type'])) {
            $cmd2['genericType'] = $cmd2['generic_type'];
            unset($cmd2['generic_type']);
            $cmdUpdated = true;
            echo "  Renamed 'generic_type' to 'genericType'.\n";
        }
        if (isset($cmd2['Comment'])) {
            $cmd2['comment'] = $cmd2['Comment'];
            unset($cmd2['Comment']);
            $cmdUpdated = true;
            echo "  Renamed 'Comment' to 'comment'.\n";
        }

        if (!isset($cmd2['configuration'])) {
            newCmdError($fileName, "ERROR", "Missing 'configuration' section");
            // return;
        } else {
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
        }

        if (isset($cmd2['display'])) {
            if (isset($cmd2['display']['forceReturnLineAfter'])) {
                $val = $cmd2['display']['forceReturnLineAfter'];
                if ($val == 1 || $val == "1") {
                    $cmd2['nextLine'] = 'after';
                    unset($cmd2['display']['forceReturnLineAfter']);
                    $cmdUpdated = true;
                    echo "  'forceReturnLineAfter' replaced by 'nextLine'.\n";
                }
            }
            if (isset($cmd2['display']) && (count($cmd2['display']) == 0)) {
                unset($cmd2['display']);
                $cmdUpdated = true;
                echo "  Empty 'display' section removed.\n";
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
