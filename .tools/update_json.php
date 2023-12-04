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
        } // End 'category'

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
            } else {
                $icon = $config['icon'];
                // Rename some icons
                $iconsToRename = array(
                    'eTRV0100' => 'Danfoss-Ally-Thermostat',
                    'IkeaTradfriBulbE14WSOpal400lm' => 'Ikea-BulbE14-Globe',
                    'ProfaluxLigthModule' => 'Profalux-LigthModule',
                    'Ikea-BulbE14CandleWhite' => 'Ikea-BulbE14-Candle',
                    'TRADFRIbulbE14Wopch400lm' => 'Ikea-BulbE14-Candle',
                    'TRADFRIbulbE14WS470lm' => 'Ikea-BulbE14-Candle',
                    'TRADFRIbulbE27WSopal1000lm' => 'Ikea-BulbE27',
                    'TRADFRIbulbE27WW806lm' => 'Ikea-BulbE27',
                );
                if (isset($iconsToRename[$icon])) {
                    $newName = $iconsToRename[$icon];
                    $dev[$devName]['configuration']['icon'] = $newName;
                    echo "  'icon' renamed from '${icon}' to '${newName}'.\n";
                    $devUpdated = true;
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
                // New syntax only: "<cmdJName>": { "use": "<fileName>", "param": "xxx" ... }
                $cmdJName = $key;
                $cmdFName = $value['use'];
                $cmd = $value;
                $oldSyntax = false;

                if (preg_match('/^zb-[a-zA-Z0-9]{4}-/', $cmdFName)) {
                    $new = "inf_zbAttr-".substr($cmdFName, 3);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' RENAMED.\n";
                }

                // act_zbCmdG-XXXX-YYYY => act_zbCmdC-XXXX-YYYY
                else if (preg_match('/^act_zbCmdG-[a-zA-Z0-9]{4}-/', $cmdFName)) {
                    $new = "act_zbCmdC-".substr($cmdFName, 11);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' RENAMED.\n";
                }

                // zbCmdR-XXXX-YYYY => inf_zbCmdR-XXXX-YYYY
                else if (preg_match('/^zbCmdR-[a-zA-Z0-9]{4}-/', $cmdFName)) {
                    $new = "inf_zbCmdR-".substr($cmdFName, 7);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' RENAMED.\n";
                }

                // attr-XXXX => inf_XXXX
                else if (preg_match('/^attr-[a-zA-Z0-9]*/', $cmdFName)) {
                    $new = "inf_".substr($cmdFName, 5);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' RENAMED.\n";
                }

                // 'inf_zbCmdR-XXXX-Yyyyy' => 'inf_zbCmdC-XXXX-Yyyyy'
                else if (preg_match('/^inf_zbCmdR-[a-zA-Z0-9]*/', $cmdFName)) {
                    $new = "inf_zbCmdC-".substr($cmdFName, 11);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' RENAMED.\n";
                }

                // 'inf_Xxxxx' => 'inf_xxxxxx'
                else if (preg_match('/^inf_[A-Z]{1}/', $cmdFName)) {
                    $new = "inf_";
                    $new .= strtolower(substr($cmdFName, 4, 1));
                    $new .= substr($cmdFName, 5);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '${cmdFName}' renamed to '${new}'.\n";
                }

                // zbReadAttribute => act_zbReadAttribute
                else if (in_array($cmdFName, array('zbReadAttribute', 'zbWriteAttribute', 'zbConfigureReporting', 'zbBindToZigate'))) {
                    $new = "act_zb".substr($cmdFName, 2);
                    $commands2[$key] = $value;
                    $commands2[$key]["use"] = $new;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' RENAMED.\n";
                }

                // // Group-Membership => inf_zbAttr-0004-Group-Membership
                // else if (in_array($cmdFName, array('zbReadAttribute', 'zbWriteAttribute', 'zbConfigureReporting', 'zbBindToZigate'))) {
                //     $new = "act_zb".substr($cmdFName, 2);
                //     $commands2[$key] = $value;
                //     $commands2[$key]["use"] = $new;
                //     $devUpdated = true;
                //     echo "  Cmd '".$cmdFName."' RENAMED.\n";
                // }

                // Cluster 0001 updates
                else if (($cmdFName == "BindToPowerConfig") && $oldSyntax) {
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
                else if ($cmdFName == "Identify") {
                    $cmdArr = Array(
                        "use" => "act_zbCmdC-Identify",
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
                } else if ($cmdFName == "Group-Membership") {
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' REMOVED.\n";
                }

                // Cluster 0006 updates
                else if (($cmdFName == "act_zbConfigureReporting") &&
                        ($cmd['params'] ==  "clustId=0006&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "clustId=0006&attrType=10&attrId=0000";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
                } else if (($cmdFName == "act_zbConfigureReporting") &&
                    ($cmd['params'] ==  "ep=01&clustId=0006&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "ep=01&clustId=0006&attrId=0000&attrType=10";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
                } else if (($cmdFName == "act_zbConfigureReporting") &&
                    ($cmd['params'] ==  "ep=02&clustId=0006&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "ep=02&clustId=0006&attrId=0000&attrType=10";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
                } else if (($cmdFName == "act_zbConfigureReporting") &&
                    ($cmd['params'] ==  "ep=03&clustId=0006&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "ep=03&clustId=0006&attrId=0000&attrType=10";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
                } else if (($cmdFName == "act_zbConfigureReporting") &&
                    ($cmd['params'] ==  "ep=04&clustId=0006&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "ep=04&clustId=0006&attrId=0000&attrType=10";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
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
                }

                // Cluster 0008 updates
                else if (($cmdFName == "act_zbConfigureReporting") &&
                    ($cmd['params'] ==  "clustId=0008&attrType=20&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "clustId=0008&attrId=0000&attrType=20";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
                } else if (($cmdFName == "act_zbConfigureReporting") &&
                    ($cmd['params'] ==  "clustId=0008&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000&changeVal=")) {
                    $cmd['use'] = "act_zbConfigureReporting2";
                    $cmd['params'] =  "clustId=0008&attrId=0000&attrType=20";
                    $commands2[$cmdJName] = $cmd;
                    $devUpdated = true;
                    echo "  Cmd '${cmdJName}' UPDATED.\n";
                } else if (($cmdFName == "setReportLevel") && $oldSyntax) {
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
                } else if (($cmdFName == "setLevelVoletDown") && $oldSyntax) {
                    $commands2["Down"] = Array(
                        "use" => "zbCmd-0008-DownClose",
                        // "params" => "clustId=0008&attrId=0000",
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ($cmdFName == "setLevel") {
                    $commands2["Set Level"] = Array(
                        "use" => "act_setLevel-Light",
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
                } else if ($cmdFName == "temperatureLight") {
                    $commands2["Color temp"] = Array(
                        "use" => "inf_zbAttr-0300-ColorTemperatureMireds",
                        "minValue" => "2000", // What for ?
                        "maxValue" => "6500", // What for ?
                        "calculValueOffset" => "intval(1000000\/#value#)",
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ($cmdFName == "temperatureLight1") {
                    $commands2["Color temp"] = Array(
                        "use" => "inf_zbAttr-0300-ColorTemperatureMireds",
                        "minValue" => "2700", // What for ?
                        "maxValue" => "5000", // What for ?
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ($cmdFName == "temperatureLight2") {
                    $commands2["Color temp"] = Array(
                        "use" => "inf_zbAttr-0300-ColorTemperatureMireds",
                        "minValue" => "2000", // What for ?
                        "maxValue" => "6500", // What for ?
                        "isVisible" => 1,
                    );
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                } else if ($cmdFName == "temperatureLightV2") {
                    $commands2["Color temp"] = Array(
                        "use" => "inf_zbAttr-0300-ColorTemperatureMireds",
                        "historizeRound" => "0",
                        "isVisible" => 1,
                    );
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

                // Misc updates
                // use click => use inf_click
                else if ($value['use'] == "click") {
                    $value['use'] = "inf_click";
                    $commands2[$key] = $value;
                    $devUpdated = true;
                    echo "  Cmd '".$cmdFName."' UPDATED.\n";
                }

                else {
                    $commands2[$cmdJName] = $cmd;
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

        // xiaomi/fromDevice => private + type=xiaomi
        if (isset($dev[$devName]['xiaomi'])) {
            if (isset($dev[$devName]['xiaomi']['fromDevice'])) {
                $dev[$devName]['private'] = [];
                foreach ($dev[$devName]['xiaomi']['fromDevice'] as $pKey => $pVal) {
                    $pVal['type'] = "xiaomi";
                    $dev[$devName]['private'][$pKey] = $pVal;
                }
            }
            unset($dev[$devName]['xiaomi']);
            $devUpdated = true;
            echo "  'xiaomi' updated to 'private'.\n";
        }

        if ($devUpdated) {
            $text = json_encode($dev, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($fullPath, $text);
        }
    }

    function updateCommand($fileName, $fullPath, $cmd) {
        global $commandsList;

        // foreach ($cmd as $cmdKey => $cmd2) {
        //     break;
        // }

        if (isset($cmd2['type']))
            $type = $cmd2['type'];
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
        // if (isset($cmd2['Type'])) {
        //     $cmd2['type'] = $cmd2['Type'];
        //     unset($cmd2['Type']);
        //     $cmdUpdated = true;
        //     echo "  Renamed 'Type' to 'type'.\n";
        // }
        // if (isset($cmd2['unite'])) {
        //     $cmd2['unit'] = $cmd2['unite'];
        //     unset($cmd2['unite']);
        //     $cmdUpdated = true;
        //     echo "  Renamed 'unite' to 'unit'.\n";
        // }
        // if (isset($cmd2['generic_type'])) {
        //     $cmd2['genericType'] = $cmd2['generic_type'];
        //     unset($cmd2['generic_type']);
        //     $cmdUpdated = true;
        //     echo "  Renamed 'generic_type' to 'genericType'.\n";
        // }
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
            $text = json_encode($newCmd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

    // Ex: zb-xxxx-yyyy => inf_zbAttr-xxxx-yyyy
    function renameCmds() {
        echo "Renaming commands\n";
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

            // if (!preg_match('/^zb-[a-zA-Z0-9]{4}-/', $dirEntry))
            //     continue;
            // $new = "inf_zbAttr-".substr($dirEntry, 3);

            // if (!preg_match('/^zbCmd-[a-zA-Z0-9]{4}-/', $dirEntry))
            //     continue;
            // $new = "act_zbCmdG-".substr($dirEntry, 6);

            // attr-XXXX => inf_XXXX
            // if (!preg_match('/^attr-[a-zA-Z0-9]*/', $dirEntry))
            //     continue;
            // $new = "inf_".substr($dirEntry, 5);

            // if (!in_array($dirEntry, array('zbReadAttribute', 'zbWriteAttribute', 'zbConfigureReporting', 'zbBindToZigate')))
            //     continue;
            // $new = "act_zb".substr($dirEntry, 2);

            if (preg_match('/^act_zbCmdG-[a-zA-Z0-9]{4}-/', $dirEntry))
                $new = "act_zbCmdC-".substr($dirEntry, 11);
            else if (preg_match('/^inf_zbCmdR-[a-zA-Z0-9]{4}-/', $dirEntry))
                $new = "inf_zbCmdC-".substr($dirEntry, 11);
            else
                continue;

            $jsonContent = file_get_contents($fullPath);
            $content = json_decode($jsonContent, true);
            $content[$new] = $content[$dirEntry];
            unset($content[$dirEntry]);
            $jsonContent = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $fullPath2 = commandsDir.'/'.$new.'.json';
            file_put_contents($fullPath2, $jsonContent);
            unlink($fullPath);
            echo "- ".$dirEntry." renamed to ".$new."\n";
        }
        echo "= Ok";
    }

    renameCmds(); // Ex: zb-xxxx-yyyy => inf_zbAttr-xxxx-yyyy

    // exit(12);

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
