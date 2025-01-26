<?php
    /*
     * AbeilleMain => Main daemon
     */

    include_once __DIR__.'/../config/Abeille.config.php';

    /* Developers debug features */
    if (file_exists(dbgFile)) {
        // include_once dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/AbeilleLog.php';
    // include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../class/AbeilleTools.class.php';
    // include_once __DIR__.'/AbeilleCmd.class.php';
    // include_once __DIR__.'/../../plugin_info/install.php'; // updateConfigDB()
    // include_once __DIR__.'/../php/AbeilleModels.php'; // library to deal with models => getModelsList()

    /**
     * RefreshCmd
     * Execute all cmd to update cmd info (e.g: after a long stop of Abeille to  get all data)
     *
     * @param   none
     *
     * @return  Does not return anything as all action are triggered by sending messages in queues
     */
    function refreshCmd() {
        global $abQueues;

        logMessage('debug', 'refreshCmd: start');
        $i=15;
        foreach (AbeilleCmd::searchConfiguration('RefreshData', 'Abeille') as $key => $cmd) {
            if ($cmd->getConfiguration('RefreshData',0)) {
                logMessage('debug', 'refreshCmd: '.$cmd->getHumanName().' ('.$cmd->getEqlogic()->getLogicalId().')' );
                // $cmd->execute(); le process ne sont pas tous demarrer donc on met une tempo.
                // $topic = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getLogicalId();
                $topic = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getConfiguration('topic');
                $request = $cmd->getConfiguration('request');
                // publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$topic."&time=".(time()+$i), $request);
                msgToCmd("TempoCmd".$topic."&time=".(time()+$i), $request);
                $i++;
            }
        }
        logMessage('debug', 'refreshCmd: end');
    }

    // Zigate Jeedom equipment creation/update. Called on daemon startup or new beehive creation.
    function createRuche($dest) {
        $gtwId = substr($dest, 7); // AbeilleX => X

        // $config = AbeilleTools::getConfig();
        $config = $GLOBALS['config']; // Present as global since main daemon
        $eqLogic = eqLogic::byLogicalId($dest."/0000", 'Abeille');
        if (!is_object($eqLogic)) {
            message::add("Abeille", "Création de l'équipement 'Ruche' en cours. Rafraichissez votre dashboard dans qq secondes.", '');
            logMessage('info', 'Ruche: Création de '.$dest."/0000");
            $eqLogic = new Abeille();
            //id
            $eqLogic->setName("Ruche-".$dest);
            $eqLogic->setLogicalId($dest."/0000");
            if ($config['ab::defaultParent'] > 0) {
                $eqLogic->setObject_id($config['ab::defaultParent']);
            } else {
                $eqLogic->setObject_id(jeeObject::rootObject()->getId());
            }
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setIsVisible("0");
            $eqLogic->setConfiguration('ab::icon', "Ruche");
            $eqLogic->setTimeout(5); // timeout en minutes
            $eqLogic->setIsEnable(1);
        } else {
            // TODO: If already exist, should we update commands if required ?
            logMessage('debug', "createRuche(): '".$eqLogic->getLogicalId()."' already exists");
        }

        $eqLogic->setConfiguration('mainEP', '01');

        // For future.. if required
        // // Zigate is a bridge: adding 'ab::bridge' array
        // $bridge = array(
        //     'type' => 'zigate',
        //     'model' => $config['ab::gtwSubType'.$gtwId],
        // );
        // $eqLogic->setConfiguration('ab::bridge', $bridge);

        // Zigate JSON model infos
        $eqModelInfos = array(
            'modelSig' => 'rucheCommand',
            'modelName' => 'rucheCommand', // Equipment model id
            'modelSource' => 'Abeille', // Equipment model location
            'type' => 'Zigate',
        );
        $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

        // Note: initializing 'groups' support. Simple descriptor response does not show cluster 0004 for EP01 (see https://github.com/fairecasoimeme/ZiGate/issues/409)
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        if (!isset($zigbee['groups']))
            $zigbee['groups'] = [];
        if (!isset($zigbee['groups']['01']))
            $zigbee['groups']['01'] = '';
        $zigbee['mainsPowered'] = 1;
        $zigbee['rxOnWhenIdle'] = 1;
        $eqLogic->setConfiguration('ab::zigbee', $zigbee);

        $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $eqLogic->save();

        $rucheCommandList = AbeilleTools::getJSonConfigFiles('rucheCommand.json', 'Abeille');

        // // Only needed for debug and dev so by default it's not done.
        // if (0) {
        //     $i = 100;

        //     //Load all commandes from defined objects (except ruche), and create them hidden in Ruche to allow debug and research.
        //     $items = AbeilleTools::getDeviceNameFromJson('Abeille');

        //     foreach ($items as $item) {
        //         $AbeilleObjetDefinition = AbeilleTools::getJSonConfigFilebyDevices(AbeilleTools::getTrimmedValueForJsonFiles($item), 'Abeille');
        //         // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
        //         foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
        //             $rucheCommandList[$objetId] = array(
        //                 "name" => $objetId,
        //                 "order" => $i++,
        //                 "isVisible" => "0",
        //                 "isHistorized" => "0",
        //                 "Type" => "action",
        //                 "subType" => "other",
        //                 "configuration" => array(
        //                     "topic" => "CmdCreate/".$objetId."/0000-0005",
        //                     "request" => $objetId,
        //                     "visibilityCategory" => "additionalCommand",
        //                     "visibiltyTemplate" => "0"
        //                 ),
        //             );
        //         }
        //     }
        //     // print_r($rucheCommandList);
        // }

        // Removing obsolete commands by their logical ID (unique)
        $cmds = Cmd::byEqLogicId($eqLogic->getId());
        foreach ($cmds as $cmdLogic) {
            $found = false;
            $cmdName = $cmdLogic->getName();
            $cmdLogicId = $cmdLogic->getLogicalId();
            foreach ($rucheCommandList as $cmdLogicId2 => $mCmd) {
                if ($cmdLogicId == $cmdLogicId2) {
                    $found = true;
                    break; // Listed in JSON
                }
            }
            if ($found == false) {
                logMessage('debug', "  Removing cmd '".$cmdName."' => '".$cmdLogicId."'");
                $cmdLogic->remove(); // No longer required
            }
        }

        // Creating/updating beehive commands
        $order = 0;
        foreach ($rucheCommandList as $cmdLogicId => $mCmd) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdLogicId);
            if (!$cmdLogic) {
                $cmdJName = $mCmd["name"]; // Jeedom cmd name
                logMessage('debug', "  Adding cmd '".$cmdJName."' => '".$cmdLogicId."'");
                $cmdLogic = new AbeilleCmd();
                $cmdLogic->setEqLogic_id($eqLogic->getId());
                $cmdLogic->setEqType('Abeille');
                $cmdLogic->setLogicalId($cmdLogicId);
                $cmdLogic->setName($cmdJName);
                $newCmd = true;
            } else {
                $cmdJName = $cmdLogic->getName();
                logMessage('debug', "  Updating cmd '".$cmdJName."' => '".$cmdLogicId."'");
                $newCmd = false;
            }

            $cmdLogic->setOrder($order++); // New or update

            if ($mCmd["Type"] == "action") {
                // $cmdLogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd);
                $cmdLogic->setConfiguration('topic', $cmdLogicId);

                // Tcharp38: work in progress. Adding support for linked commands
                // Note: Error if info cmd is not registered BEFORE action cmd.
                // if (isset($mCmd["value"])) {
                //     // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                //     logMessage('debug', 'Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$mCmd["value"]);

                //     $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $mCmd["value"]);
                //     $cmdLogic->setValue($cmdPointeur_Value->getId());
                // }
            } else {
                // $cmdLogic->setConfiguration('topic', $nodeid.'/'.$cmd);
                $cmdLogic->setConfiguration('topic', $cmdLogicId);
            }
            // if ($mCmd["Type"] == "action") {  // not needed as mosquitto is not used anymore
            //    $cmdLogic->setConfiguration('retain', '0');
            // }
            if (isset($mCmd["configuration"])) {
                foreach ($mCmd["configuration"] as $confKey => $confValue) {
                    $cmdLogic->setConfiguration($confKey, $confValue);
                }
            }
            $cmdLogic->setType($mCmd["Type"]);
            $cmdLogic->setSubType($mCmd["subType"]);

            // Todo only if new command
            if ($newCmd) {
                if (isset($mCmd["isHistorized"])) $cmdLogic->setIsHistorized($mCmd["isHistorized"]);
                if (isset($mCmd["template"])) $cmdLogic->setTemplate('dashboard', $mCmd["template"]);
                if (isset($mCmd["template"])) $cmdLogic->setTemplate('mobile', $mCmd["template"]);
                if (isset($mCmd["invertBinary"])) $cmdLogic->setDisplay('invertBinary', '0');
                if (isset($mCmd["isVisible"])) $cmdLogic->setIsVisible($mCmd["isVisible"]);
                if (isset($mCmd["display"])) {
                    foreach ($mCmd["display"] as $confKey => $confValue) {
                        // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                        $cmdLogic->setDisplay($confKey, $confValue);
                    }
                }
            }

            // Whatever existing or new beehive, it is key to reset the following points
            if ($cmdLogicId == 'FW-Version')
                // $cmdLogic->setValue('----'); // Indicate FW version is invalid
                $cmdLogic->setCache('value', '---------'); // Indicate FW version is invalid

            $cmdLogic->save();
        }
    } // End createRuche()

    // EZSP gateway Jeedom equipment creation/update. Called on daemon startup or new beehive creation.
    function createEzspGateway($net) {
        $gtwId = substr($net, 7); // AbeilleX => X

        // $config = AbeilleTools::getConfig();
        $config = $GLOBALS['config']; // Present as global since main daemon
        $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
        if (!is_object($eqLogic)) {
            message::add("Abeille", "Création de l'équipement 'EZSP' en cours. Rafraichissez votre dashboard dans qq secondes.", '');
            logMessage('info', 'Ruche: Création de '.$net."/0000");
            $eqLogic = new Abeille();
            //id
            $eqLogic->setName("EzspGtw-".$net);
            $eqLogic->setLogicalId($net."/0000");
            if ($config['ab::defaultParent'] > 0) {
                $eqLogic->setObject_id($config['ab::defaultParent']);
            } else {
                $eqLogic->setObject_id(jeeObject::rootObject()->getId());
            }
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setIsVisible("0"); // No need on dashboard
            $eqLogic->setConfiguration('ab::icon', "Ruche");
            $eqLogic->setTimeout(5); // timeout en minutes
            $eqLogic->setIsEnable(1);
        } else {
            // TODO: If already exist, should we update commands if required ?
            logMessage('debug', "createEzspGateway(): '".$eqLogic->getLogicalId()."' already exists");
        }

        // $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $eqLogic->save();
    } // End createEzspGateway()

    // Create a basic Jeedom device
    function newJeedomDevice($net, $addr, $ieee) {
        logMessage('debug', '  newJeedomDevice('.$net.', addr='.$addr.')');

        $logicalId = $net.'/'.$addr;
        $eqLogic = new Abeille();
        $eqLogic->setEqType_name('Abeille');
        $eqLogic->setName("newDevice-".$addr); // Temp name to have it non empty
        $eqLogic->save(); // Save to force Jeedom to assign an ID

        $eqName = $net."-".$eqLogic->getId(); // Default name (ex: 'Abeille1-12')
        $eqLogic->setName($eqName);
        $eqLogic->setLogicalId($logicalId);
        // $config = AbeilleTools::getConfig();
        $config = $GLOBALS['config']; // Present as global since main daemon
        $eqLogic->setObject_id($config['ab::defaultParent']);
        $eqLogic->setConfiguration('IEEE', $ieee);
        $eqLogic->setIsVisible(0); // Hidden by default
        $eqLogic->setIsEnable(1);
        $eqLogic->save();

        // Inform cmd that new device has been created
        $msg = array(
            'type' => "eqUpdated",
            'id' => $eqLogic->getId()
        );
        msgToCmd2($msg);

    } // End newJeedomDevice()

    function message($topic, $payload) {
        // KiwiHC16: Please leave this line log::add commented otherwise too many messages in log Abeille
        // and keep the 3 lines below which print all messages except Time-Time, Time-TimeStamp and Link-Quality that we get for every message.
        // Divide by 3 the log quantity and ease the log reading
        // logMessage('debug', "message(topic='".$topic."', payload='".$payload."')");

        $topicArray = explode("/", $topic);
        if (sizeof($topicArray) != 3) {
            logMessage('debug', "ERROR: Invalid message: topic=".$topic);
            return;
        }

        // $config = AbeilleTools::getConfig();
        $config = $GLOBALS['config']; // Present as global since main daemon

        // if (!preg_match("(Time|Link-Quality)", $topic)) {
        //    logMessage('debug', "fct message Topic: ->".$topic."<- Value ->".$payload."<-");
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // demande de creation de ruche au cas ou elle n'est pas deja crée....
        // La ruche est aussi un objet Abeille
        if ($topic == "CmdRuche/0000/CreateRuche") {
            // logMessage('debug', "Topic: ->".$topic."<- Value ->".$payload."<-");
            createRuche($payload);
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // On ne prend en compte que les message Abeille|Ruche|CmdCreate/#/#
        // CmdCreate -> pour la creation des objets depuis la ruche par exemple pour tester les modeles
        if (!preg_match("(^Abeille|^Ruche|^CmdCreate|^ttyUSB|^zigate|^monitZigate)", $topic)) {
            // logMessage('debug', 'message: this is not a '.$Filter.' message: topic: '.$topic.' message: '.$payload);
            return;
        }

        /*----------------------------------------------------------------------------------------------------------------------------------------------*/
        // Analyse du message recu
        // [CmdAbeille:Abeille] / Address / Cluster-Parameter
        // [CmdAbeille:Abeille] / $addr / $cmdId => $value
        // $nodeId = [CmdAbeille:Abeille] / $addr

        list($Filter, $addr, $cmdId) = explode("/", $topic);
        // logMessage('debug', "message(): Filter=".$Filter.", addr=".$addr.", cmdId=".$cmdId);
        if (preg_match("(^CmdCreate)", $topic)) {
            $Filter = str_replace("CmdCreate", "", $Filter);
        }
        $net = $Filter; // Network (ex: 'Abeille1')
        $dest = $Filter;

        // log all messages except the one related to Time, which overload the log
        // if (!in_array($cmdId, array("Time-Time", "Time-TimeStamp", "Link-Quality"))) {
        if (!in_array($cmdId, array("Time-Time", "Link-Quality"))) {
            logMessage('debug', "message(topic='".$topic."', payload='".$payload."')");
        }

        $nodeid = $net.'/'.$addr;
        $value = $payload;
        $type = 'topic';         // type = topic car pas json

        // Le capteur de temperature rond V1 xiaomi envoie spontanement son nom: ->lumi.sensor_ht<- mais envoie ->lumi.sens<- sur un getName
        // Tcharp38: To be removed. This cleanup is now done directly in parser on modelIdentifier receive
        // if ($cmdId == "0000-01-0005") {
        //     if ($value == "lumi.sens") {
        //         $value = "lumi.sensor_ht";
        //         message::add("Abeille", "lumi.sensor_ht case tracking: ".json_encode($message), '');
        //     }
        //     if ($value == "lumi.sensor_swit") $value = "lumi.sensor_switch.aq3";
        //     if ($value == "TRADFRI Signal Repeater") $value = "TRADFRI signal repeater";
        // }

        /* Request to create virtual remote control */
        if ($cmdId == "createRemote") {
            logMessage('debug', 'message(): createRemote');

            /* Let's compute RC number */
            $eqLogics = eqLogic::byType('Abeille');
            $max = 1;
            foreach ($eqLogics as $key => $eqLogic) {
                list($net2, $addr2) = explode("/", $eqLogic->getLogicalId());
                if ($net2 != $net)
                    continue; // Wrong network
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
                $modelName2 = $eqModel ? $eqModel['modelName'] : '';
                if ($modelName2 != "remotecontrol")
                    continue; // Not a remote
                if ($addr2 == '')
                    continue; // No addr for remote on '210607-STABLE-1' leading to 1 remote only per zigate.
                $addr2 = substr($addr2, 2); // Removing 'rc'
                if (hexdec($addr2) >= $max)
                    $max = hexdec($addr2) + 1;
            }

            /* Remote control short addr = 'rcXX' */
            $rcAddr = sprintf("rc%02X", $max);
            $dev = array(
                'net' => $dest,
                'addr' => $rcAddr,
                'modelSource' => 'Abeille',
                'modelName' => 'remotecontrol',
                'modelSig' => 'remotecontrol',
            );
            createDevice("update", $dev);

            return;
        }

        // /* Request to update device from JSON. Useful to avoid reinclusion */
        // // No longer be used
        // if ($cmdId == "updateFromJson") {
        //     logMessage('debug', 'message(): updateFromJson, '.$net.'/'.$addr);

        //     $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
        //     if (!is_object($eqLogic)) {
        //         logMessage('debug', '  ERROR: Unknown device');
        //         return;
        //     }

        //     // createDevice("update", $dest, $addr);
        //     $dev = array(
        //         'net' => $dest,
        //         'addr' => $addr,
        //     );
        //     createDevice("update", $dev);

        //     return;
        // }

        /* Request to update or reset device from JSON. Useful to avoid reinclusion */
        if (($cmdId == "updateFromModel") || ($cmdId == "resetFromModel")) {
            if ($cmdId == 'updateFromModel')
                $action = 'update';
            else
                $action = 'reset';
            logMessage('debug', 'message(): '.$cmdId.', '.$net.'/'.$addr);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('error', '  '.$cmdId.': Equipement inconnu: '.$net.'/'.$addr);
                return;
            }

            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            $modelSource = isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille';
            $modelName = isset($eqModel['modelName']) ? $eqModel['modelName'] : '';
            $modelPath = isset($eqModel['modelPath']) ? $eqModel['modelPath'] : '';
            $modelForced = isset($eqModel['modelForced']) ? $eqModel['modelForced'] : false;
            $modelSig = isset($eqModel['modelSig']) ? $eqModel['modelSig'] : $modelName;
            $modelName1 = $modelName;

            // Checking if model is defaultUnknown and there is now a real model for it (see #2211).
            // Also rechecking if model is still the correct one (ex: TS011F => TS011F__TZ3000_2putqrmw)
            // $eqSig = $eqLogic->getConfiguration('ab::signature', []);
            // if (($eqSig != []) && ($eqSig['modelId'] != "") && !$modelForced) {
            //     // Any user or official model ?
            //     // $modelInfos = findModel($eqSig['modelId'], $eqSig['manufId']);
            //     $modelInfos = identifyModel($eqSig['modelId'], $eqSig['manufId']);
            //     if ($modelInfos !== false) {
            //         $modelSig = $modelInfos['modelSig'];
            //         $modelName = $modelInfos['modelName'];
            //         $modelSource = $modelInfos['modelSource'];
            //         $eqHName = $eqLogic->getHumanName();
            //     }
            // }
            if (($modelName == '') || (($modelName == "defaultUnknown") && !$modelForced)) {
                // Any better model ?
                $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                if (isset($zigbee['modelId']) && ($zigbee['modelId'] != "")) {
                    $modelInfos = identifyModel($zigbee['modelId'], $zigbee['manufId']);
                    if ($modelInfos !== false) {
                        $modelSource = $modelInfos['modelSource'];
                        $modelName = $modelInfos['modelName'];
                        $modelPath = isset($modelInfos['modelPath']) ? $modelInfos['modelPath'] : '';
                        $modelSig = $modelInfos['modelSig'];
                        $eqHName = $eqLogic->getHumanName();
                        message::add("Abeille", $eqHName.": Mise-à-jour à partir du modèle '{$modelName}'", '');
                    }
                }
            }

            if ($modelName == '') {
                message::add("Abeille", $eqHName.": Mise-à-jour impossible. Modele non identifié => Réparer", '');
                return;
            }

            // Was using a deleted local model ?
            if ($modelSource == "local") {
                $fullPath = __DIR__."/../config/devices_local/{$modelName}/{$modelName}.json";
                if (!file_exists($fullPath)) {
                    $fullPath = __DIR__."/../config/devices/{$modelName}/{$modelName}.json";
                    if (file_exists($fullPath)) {
                        logMessage('debug', "Local model not found => using official one instead");
                        $modelSource = "Abeille";
                    } else {
                        logMessage('debug', "Local model not found but NO official one found.");
                        return;
                    }
                }
            }

            $dev = array(
                'net' => $dest,
                'addr' => $addr,
                'ieee' => $eqLogic->getConfiguration('IEEE'),
                'modelSource' => $modelSource, // Model file location
                'modelName' => $modelName, // Model file name
                'modelSig' => $modelSig, // Model signature
            );
            if ($modelPath != '')
                $dev['modelPath'] = $modelPath;
            createDevice($action, $dev);

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Cherche l objet par sa ref short Address et la commande
        $eqLogic = eqLogic::byLogicalId($nodeid, 'Abeille');
        if (is_object($eqLogic)) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdId);
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si equipement et cmd existe alors on met la valeur a jour
        if (is_object($eqLogic) && is_object($cmdLogic)) {
            /* Traitement particulier pour la remontée de nom qui est utilisé pour les ping des routeurs */
            // if (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) {
            // if (preg_match("/^0000-[0-9A-F]*-*0005/", $cmdId) || preg_match("/^0000-[0-9A-F]*-*0010/", $cmdId)) {
            // else
            // if ($cmdId == "Time-TimeStamp") {
            //     // logMessage('debug', "  Updating 'online' status for '".$dest."/".$addr."'");
            //     $cmdLogicOnline = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'online');
            //     $eqLogic->checkAndUpdateCmd($cmdLogicOnline, 1);
            // }

            // Traitement particulier pour rejeter certaines valeurs
            // exemple: le Xiaomi Wall Switch 2 Bouton envoie un On et un Off dans le même message donc Abeille recoit un ON/OFF consecutif et
            // ne sais pas vraiment le gérer donc ici on rejete le Off et on met un retour d'etat dans la commande Jeedom
            if ($cmdLogic->getConfiguration('AbeilleRejectValue', -9999.99) == $value) {
                logMessage('debug', 'Rejet de la valeur: '.$cmdLogic->getConfiguration('AbeilleRejectValue', -9999.99).' - '.$value);

                return;
            }

            $eqLogic->checkAndUpdateCmd($cmdLogic, $value);

            // infoCmdUpdate($eqLogic, $cmdLogic, $value);

            // // Polling to trigger based on this info cmd change: e.g. state moved to On, getPower value.
            // $cmds = AbeilleCmd::searchConfigurationEqLogic($eqLogic->getId(), 'PollingOnCmdChange', 'action');
            // foreach ($cmds as $key => $cmd) {
            //     if ($cmd->getConfiguration('PollingOnCmdChange') == $cmdId) {
            //         logMessage('debug', 'Cmd action execution: '.$cmd->getName());
            //         // $cmd->execute(); si j'envoie la demande immediatement le device n a pas le temps de refaire ses mesures et repond avec les valeurs d avant levenement
            //         // Je vais attendre qq secondes aveant de faire la demande
            //         // publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000" );
            //         publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$cmd->getLogicalId()."&time=".(time() + $cmd->getConfiguration('PollingOnCmdChangeDelay')), $cmd->getConfiguration('request'));
            //     }
            // }
            return;
        }

        // if (is_object($eqLogic) && !is_object($cmdLogic)) {
        //     logMessage('debug', "  L'objet '".$nodeid."' existe mais pas la cmde '".$cmdId."' => message ignoré");
        //     return;
        // }

        logMessage('debug', "  WARNING: Unexpected or no longer supported message.");
        return;
    } // End message()

    /* Deal with messages coming from parser or cmd processes.
       Note: this is the new way to handle messages from parser, replacing progressively 'message()' */
    function msgFromParser($msg) {
        global $abQueues;

        if (isset($msg['net']))
            $net = $msg['net'];
        if (isset($msg['addr']))
            $addr = $msg['addr'];
        if (isset($msg['ep']))
            $ep = $msg['ep'];
        else
            $ep = '';

        /* Log level has changed */
        if ($msg['type'] == "logLevelChanged") {
            logMessage('debug', "msgFromParser(): log level changed to ".$msg['level']);
            logLevelChanged($msg['level']);
            return;
        } // End 'logLevelChanged'

        /* Request to update EQ from given model (ex: user force model) */
        if ($msg['type'] == "setForcedModel") {
            logMessage('debug', "msgFromParser(): setForcedModel: ".json_encode($msg, JSON_UNESCAPED_SLASHES));

            $dev = array(
                'net' => $net,
                'addr' => $addr,
                'modelSource' => $msg['modelSource'], // Model file location
                'modelName' => $msg['modelName'], // Model name (modelX[-variant]) WITHOUT '.json'
                // 'modelPath' => $msg['modelPath'], // Optional: Model file path (modelX/modelX[-variant].json)
                'modelForced' => true,
                'modelSig' => $msg['modelSig'],
            );
            if (isset($msg['modelPath']))
                $dev['modelPath'] = $msg['modelPath'];
            createDevice("reset", $dev); // Changing to another model => force reset

            return;
        } // End 'setForcedModel'

        /* Request to update EQ from originally identified model (ex: user removed forced model) */
        if ($msg['type'] == "removeForcedModel") {
            logMessage('debug', "msgFromParser(): removeForcedModel: ".json_encode($msg, JSON_UNESCAPED_SLASHES));

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('error', "removeForcedModel '{$net}/{$addr}': Equipement inconnu.");
                return;
            }
            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            if (!isset($zigbee['modelId']) || !isset($zigbee['manufId'])) {
                logMessage('warning', "removeForcedModel '{$net}/{$addr}': Identifiant Zigbee incomplet => réparer");
                logMessage('debug', "zigbee=".json_encode($zigbee, JSON_UNESCAPED_SLASHES));
                return;
            }
            $model = identifyModel($zigbee['modelId'], $zigbee['manufId']);
            if ($model !== false) {
                $dev = array(
                    'net' => $net,
                    'addr' => $addr,
                    'modelSource' => $model['modelSource'], // Model file location
                    'modelName' => $model['modelName'], // Model name (modelX[-variant]) WITHOUT '.json'
                    // 'modelPath' => $msg['modelPath'], // Optional: Model file path (modelX/modelX[-variant].json)
                    'modelForced' => false,
                    'modelSig' => $model['modelSig'], // Model signature for devices supported thru alternate IDs
                );
                if (isset($model['modelPath']))
                    $dev['modelPath'] = $model['modelPath'];
                createDevice("reset", $dev); // Changing to another model => force reset
            }

            return;
        } // End 'removeForcedModel'

        if ($msg['type'] == "updateFromModel") {
            logMessage('debug', "msgFromParser(): updateFromModel: ".json_encode($msg, JSON_UNESCAPED_SLASHES));
            $dev = array(
                'net' => $net,
                'addr' => $addr,
                'ieee' => $ieee,
                'modelName' => $msg['modelName'],
                'modelSource' => $msg['modelSource'],
            );
            createDevice("update", $dev);

            return;
        } // End 'updateFromModel'

        /* Parser has found a new device. Basic Jeedom entry to be created. */
        if ($msg['type'] == "newDevice") {
            $ieee = $msg['ieee'];
            logMessage('debug', "msgFromParser(): New device: ".$net.'/'.$addr.", ieee=".$ieee);

            newJeedomDevice($net, $addr, $ieee);

            return;
        } // End 'newDevice'

        /* Transmit status has changed. */
        if ($msg['type'] == "eqTxStatusUpdate") {
            logMessage('debug', "msgFromParser(): TX status update: ".$net.'/'.$addr.", status=".$msg['txStatus']);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (is_object($eqLogic)) {
                $eqLogic->setStatus('ab::txAck', $msg['txStatus']); // ab::txAck == 'ok' or 'noack'
                $eqLogic->save();
            } else {
                logMessage('error', "msgFromParser(eqTxStatusUpdate): Equipement inconnu: ".$net.'/'.$addr);
            }

            return;
        } // End 'eqTxStatusUpdate'

        /* Parser has found device infos to update. */
        if ($msg['type'] == "deviceUpdates") {
            logMessage('debug', "msgFromParser(): '{$net}/{$addr}/{$ep}' device updates, ".json_encode($msg['updates'], JSON_UNESCAPED_SLASHES));

            $eqChanged = false;
            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                if (!isset($msg['updates']['ieee'])) {
                    logMessage('debug', "  ERROR: Unknown '{$net}/{$addr}' device and no IEEE update.");
                    return;
                }
                $ieee = $msg['updates']['ieee'];
                $all = eqLogic::byType('Abeille');
                foreach ($all as $eqLogic) {
                    $ieee2 = $eqLogic->getConfiguration('IEEE', '');
                    if ($ieee2 != $ieee)
                        continue;

                    $eqLogicId2 = $eqLogic->getLogicalId(); // Ex: 'Abeille1/xxxx'
                    list($net2, $addr2) = explode( "/", $eqLogicId2);
                    $eqLogic->setLogicalId($net.'/'.$addr);
                    if ($net != $net2)
                        logMessage('debug', '  '.$eqLogic->getHumanName().": Migrated from '{$net2}/{$addr2}' to '{$net}/{$addr}'");
                    else
                        logMessage('debug', '  '.$eqLogic->getHumanName().": Addr updated from {$addr2} to {$addr}");
                    $eqLogic->setIsEnable(1);
                    $eqChanged = true;
                    // $informCmd = true;
                    break;
                }
            }
            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            $zigbeeChanged = false;
            foreach ($msg['updates'] as $updKey => $updVal) {
                $jsonUpdVal = json_encode($updVal, JSON_UNESCAPED_SLASHES);
                if ($updKey == 'ieee')
                    continue; // Already treated
                else if ($updKey == 'logicalType') { // Node descriptor/logical type
                    $zigbee['logicalType'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[logicalType]' updated to {$updVal}");
                } else if ($updKey == 'macCapa') {
                    $zigbee['macCapa'] = $updVal;
                    $mc = hexdec($zigbee['macCapa']);
                    $zigbee['mainsPowered'] = ($mc >> 2) & 0b1; // 1=mains-powered
                    $zigbee['rxOnWhenIdle'] = ($mc >> 3) & 0b1;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[macCapa]' updated to ".$updVal);
                } else if ($updKey == 'rxOnWhenIdle') {
                    $zigbee['rxOnWhenIdle'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[rxOnWhenIdle]' updated to ".$updVal);
                } else if ($updKey == 'endPoints') {
                    $zigbee['endPoints'] = $updVal;
                    // Cleanup old bugs
                    foreach ($zigbee['endPoints'] as $epId2) {
                        if (($epId2 == "") || ($epId2 == "00"))
                            unset($zigbee['endPoints'][$epId2]);
                    }
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints]' updated to {$jsonUpdVal}");
                } else if ($updKey == 'profId') {
                    $zigbee['endPoints'][$ep]['profId'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][profId]' updated to {$jsonUpdVal}");
                } else if ($updKey == 'devId') {
                    $zigbee['endPoints'][$ep]['devId'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][devId]' updated to {$jsonUpdVal}");
                } else if ($updKey == 'servClusters') {
                    $zigbee['endPoints'][$ep]['servClusters'] = $updVal;
                    if (strpos($updVal, '0004') !== false)
                        $zigbee['groups'][$ep] = '';
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][servClusters]' updated to {$jsonUpdVal}");
                } else if ($updKey == 'manufId') {
                    if (!isset($zigbee['endPoints'][$ep]['manufId'])) {
                        $zigbee['endPoints'][$ep]['manufId'] = $updVal;
                        $zigbeeChanged = true;
                        logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][manufId]' updated to '{$updVal}'");
                    }
                } else if ($updKey == 'modelId') {
                    if (!isset($zigbee['endPoints'][$ep]['modelId'])) {
                        $zigbee['endPoints'][$ep]['modelId'] = $updVal;
                        $zigbeeChanged = true;
                        logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][modelId]' updated to '{$updVal}'");
                    }
                } else if ($updKey == 'location') {
                    if (!isset($zigbee['endPoints'][$ep]['location'])) {
                        $zigbee['endPoints'][$ep]['location'] = $updVal;
                        $zigbeeChanged = true;
                        logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][location]' updated to '{$updVal}'");
                    }
                } else if ($updKey == 'manufCode') {
                    $zigbee['manufCode'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[manufCode]' updated to ".$updVal);
                } else if ($updKey == 'dateCode') { // Clust/attr = 0000-0006
                    $zigbee['endPoints'][$ep]['dateCode'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][dateCode]' updated to '{$updVal}'");
                } else if ($updKey == 'swBuildId') { // Clust/attr = 0000-4000
                    $zigbee['endPoints'][$ep]['swBuildId'] = $updVal;
                    $zigbeeChanged = true;
                    logMessage('debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints][{$ep}][swBuildId]' updated to '{$updVal}'");
                }
            }
            if ($zigbeeChanged) {
                $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                $eqChanged = true;
            }
            if ($eqChanged) {
                $eqLogic->save();
            // if (isset($informCmd)) {
                // Inform cmd that EQ config has changed
                $msg = array(
                    'type' => "eqUpdated",
                    'id' => $eqLogic->getId()
                );
                msgToCmd2($msg);
            }

            return;
        } // End 'deviceUpdates'

        /* Parser has received a "device announce" and has identified (or not) the device. */
        if ($msg['type'] == "eqAnnounce") {
            /* Msg reminder
                $msg = array(
                    'src' => 'parser',
                    'type' => 'eqAnnounce',
                    'net' => $net,
                    'addr' => $addr,
                    'ieee' => $eq['ieee'],
                    'ep' => $eq['epFirst'],
                    'modelId' =>
                    'manufId' =>
                    'modelName' => $eq['modelName'], // JSON identifier
                    'modelSource' => '', // 'Abeille' or 'local'
                    'macCapa' => $eq['macCapa'],
                    'time' => time()
                ); */

            $logicalId = $net.'/'.$addr;
            $modelName = $msg['modelName'];
            $modelSource = $msg['modelSource']; // 'Abeille' or 'local'
            logMessage('debug', "msgFromParser(): Eq announce received for ".$net.'/'.$addr.", jsonId='".$modelName."'".", jsonLoc='".$modelSource."'");

            $ieee = $msg['ieee'];

            $eqLogic = eqLogic::byLogicalId($logicalId, 'Abeille');
            if (!is_object($eqLogic)) {
                /* Unknown device with net/addr logicalId.
                   Probably due to addr change on 'dev announce'. Looking for EQ based on its IEEE address */
                $all = eqLogic::byType('Abeille');
                foreach ($all as $key => $eqLogic) {
                    $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/xxxx'
                    list($net2, $addr2) = explode( "/", $eqLogicId);
                    if ($net2 != $net)
                        continue; // Not on expected network
                    if ((substr($addr2, 0, 2) == "rc") || ($addr2 == ""))
                        continue; // Virtual remote control

                    $ieee2 = $eqLogic->getConfiguration('IEEE', '');
                    if ($ieee2 == '') {
                        logMessage('debug', "  Eq announce: WARNING. No IEEE addr in '".$eqLogicId."' config.");
                        continue; // No registered IEEE
                    }
                    if ($ieee2 != $ieee)
                        continue; // Not the right equipment

                    $eqLogic->setLogicalId($logicalId); // Updating logical ID
                    $eqLogic->save();
                    logMessage('debug', "  Eq announce: Eq found with old addr ".$addr2.". Update done.");
                    break; // No need to go thru other equipments
                }
            }

            /* Create or update device from JSON.
               Note: ep = first End Point */
            $dev = array(
                'net' => $net,
                'addr' => $addr,
                'ieee' => $ieee,
                'modelId' => $msg['modelId'],
                'manufId' => $msg['manufId'],
                'modelSource' => $modelSource,
                'modelName' => $modelName,
                'macCapa' => $msg['macCapa']
            );
            createDevice("update", $dev);

            $eqLogic = eqLogic::byLogicalId($logicalId, 'Abeille');
            $eqId = $eqLogic->getId();

            // /* MAC capa from 004D/Device announce message */
            // $mc = hexdec($msg['macCapa']);
            // $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            // $zigbee['macCapa'] = $msg['macCapa'];
            // $rxOnWhenIdle = ($mc >> 3) & 0b1;
            // $mainsPowered = ($mc >> 2) & 0b1;
            // if ($mainsPowered) // 1=mains-powererd
            //     $zigbee['mainsPowered'] = 1;
            // else
            //     $zigbee['mainsPowered'] = 0;
            // if ($rxOnWhenIdle) // 1=Receiver enabled when idle
            //     $zigbee['rxOnWhenIdle'] = 1;
            // else
            //     $zigbee['rxOnWhenIdle'] = 0;
            // $eqLogic->setConfiguration('ab::zigbee', $zigbee);
            // $eqLogic->save();

            updateTimestamp($eqLogic, $msg['time']);

            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "Short-Addr");
            if (is_object($cmdLogic))
                $ret = $eqLogic->checkAndUpdateCmd($cmdLogic, $addr);

            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "IEEE-Addr");
            if (is_object($cmdLogic))
                $eqLogic->checkAndUpdateCmd($cmdLogic, $ieee);

            $mc = hexdec($msg['macCapa']);
            $mainsPowered = ($mc >> 2) & 0b1;
            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "Mains-Powered");
            if (!is_object($cmdLogic))
                $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "Power-Source"); // Old name support
            if (is_object($cmdLogic))
                $eqLogic->checkAndUpdateCmd($cmdLogic, $mainsPowered);

            return;
        } // End 'eqAnnounce'

        /* Parser has found that a device has changed network. */
        if ($msg['type'] == "eqMigrated") {
            // Msg reminder:
            // 'type' => 'eqMigrated',
            // 'net' => $net,
            // 'addr' => $addr,
            // 'srcNet' => $oldNet,
            // 'srcAddr' => $oldAddr,

            $oldLogicId = $msg['srcNet'].'/'.$msg['srcAddr'];
            logMessage('debug', "msgFromParser(): Device migration from ".$oldLogicId." to ".$net."/".$addr);

            $eqLogic = eqLogic::byLogicalId($oldLogicId, 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: ".$oldLogicId." is unknown");
                return;
            }

            // Moving eq to new network
            $eqLogic->setLogicalId($net.'/'.$addr);
            $eqLogic->setIsEnable(1);
            $eqLogic->save();
            message::add("Abeille", $eqLogic->getHumanName().": a migré vers le réseau ".$net, '');

            // Inform cmd that EQ config has changed
            $msg = array(
                'type' => "eqUpdated",
                'id' => $eqLogic->getId()
            );
            msgToCmd2($msg);

            return;
        } // End 'eqMigrated'

        /* Parser has received a "leave indication" */
        if ($msg['type'] == "leaveIndication") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'leaveIndication',
                'net' => $dest,
                'ieee' => $IEEE,
                'rejoin' => $RejoinStatus,
                'time' => time(),
                'lqi' => $lqi
            */

            $ieee = $msg['ieee'];
            logMessage('debug', "msgFromParser(): Leave indication for ".$net."/".$ieee.", rejoin=".$msg['rejoin']);

            /* Look for corresponding equipment (identified via its IEEE addr) */
            $all = eqLogic::byType('Abeille');
            $eqLogic = null;
            foreach ($all as $key => $eqLogic2) {
                $eqLogicId2 = $eqLogic2->getLogicalId(); // Ex: 'Abeille1/xxxx'
                list($net2, $addr2) = explode( "/", $eqLogicId2);
                if ($net2 != $net)
                    continue; // Not on expected network
                if ((substr($addr2, 0, 2) == "rc") || ($addr2 == ""))
                    continue; // Virtual remote control

                $ieee2 = $eqLogic2->getConfiguration('IEEE', '');
                if ($ieee2 == '') {
                    logMessage('debug', "msgFromParser(): WARNING. No IEEE addr in '".$eqLogicId2."' config.");
                    $cmd = $eqLogic2->getCmd('info', 'IEEE-Addr');
                    if (is_object($cmd)) {
                        $ieee2 = $cmd->execCmd();
                        if ($ieee2 != $ieee)
                            continue; // Still no registered IEEE
                        // Tcharp38: It should not be possible that IEEE be in cmd but not in configuration. No sense
                        //           since IEEE always provided with device announce.
                        $eqLogic2->setConfiguration('IEEE', $ieee2);
                        $eqLogic2->save();
                        logMessage('debug', "msgFromParser(): Missing IEEE addr corrected");
                    }
                }
                if ($ieee2 != $ieee)
                    continue; // Not the right equipment

                $eqLogic = $eqLogic2;
                break; // No need to go thru other equipments
            }

            if (isset($eqLogic)) {
                $eqLogic->setIsEnable(0);

                /* Display message only if NOT in include mode */
                if (checkInclusionStatus($net) !== 1)
                    message::add("Abeille", $eqLogic->getHumanName().": A quitté le réseau => désactivé.", '');

                $eqLogic->save();
                $eqLogic->refresh();
                logMessage('debug', '  '.$eqLogic->getHumanName().' ('.$eqLogic->getLogicalId().') has left the network => DISABLED');

                updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            } else
                logMessage('debug', 'msgFromParser(): WARNING: Device with IEEE '.$ieee.' NOT found in Jeedom');

            return;
        } // End 'leaveIndication'

        // Grouped attributes reporting (by Jeedom logical name)
        // Grouped read attribute responses (by Jeedom logical name)
        if (($msg['type'] == "attributesReportN") || ($msg['type'] == "readAttributesResponseN")) {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'attributesReportN'/'readAttributesResponseN',
                'net' => $net,
                'addr' => $addr,
                'ep' => 'xx', // End point hex string
                'clustId' => $clustId,
                'attributes' => [],
                    'name' => 'xxx', // Attribut name = cmd logical ID (ex: 'clustId-ep-attrId')
                    'value' => false/xx, // false = unsupported
                'time' => time(),
                'lqi' => $lqi
            */

            if ($msg['type'] == "attributesReportN")
                logMessage('debug', "msgFromParser(): Attributes report by name from '{$net}/{$addr}/{$ep}'");
            else
                logMessage('debug', "msgFromParser(): Read attributes response by name from '{$net}/{$addr}/{$ep}'");

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  Unknown device '{$net}/{$addr}'");
                return; // Unknown device
            }

            foreach ($msg['attributes'] as $attr) {
                $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $attr['name']);
                if (!is_object($cmdLogic)) {
                    logMessage('debug', "  Unknown Jeedom command logicId='".$attr['name']."'");
                } else {
                    $cmdName = $cmdLogic->getName();
                    $unit = $cmdLogic->getUnite();
                    if ($unit === null)
                        $unit = '';
                    // WARNING: value might not be the final one if 'calculValueOffset' is used
                    $cvo = $cmdLogic->getConfiguration('calculValueOffset', '');
                    if ($cvo == '')
                        logMessage('debug', "  '".$cmdName."' (".$attr['name'].") => ".$attr['value']." ".$unit);
                    else
                        logMessage('debug', "  '".$cmdName."' (".$attr['name'].") => ".$attr['value']." (calculValueOffset=".$cvo.")");
logMessage('debug', "  checkAndUpdateCmd(), attr['value']=".json_encode($attr['value']));
                    $eqLogic->checkAndUpdateCmd($cmdLogic, $attr['value']);

                    // Checking if battery info, only if registered command
logMessage('debug', "  checkIfBatteryInfo(), attr=".json_encode($attr));
                    checkIfBatteryInfo($eqLogic, $attr['name'], $attr['value']);

                    // Check if any action cmd must be executed triggered by this update
logMessage('debug', "  infoCmdUpdate()");
                    infoCmdUpdate($eqLogic, $cmdLogic, $attr['value']);
                }
            }

            updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'attributesReportN' or 'readAttributesResponseN'

        if ($msg['type'] == "deviceAlive") {
            /* $msg reminder
                'type' => 'deviceAlive',
                'net' => $net,
                'addr' => $addr,
                'time' => time(),
                'lqi' => $lqi
            */
            logMessage('debug', "msgFromParser(): Device '{$net}/{$addr}' is ALIVE");
            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  Unknown device '{$net}/{$addr}'");
                return; // Unknown device
            }
            updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        }

        /* Zigate version (8010 response) */
        if ($msg['type'] == "zigateVersion") {
            /* Reminder
            $msg = array(
                'src' => 'parser',
                'type' => 'zigateVersion',
                'net' => $dest,
                'major' => $major,
                'minor' => $minor,
                'time' => time()
            ); */

            logMessage('debug', "msgFromParser(): ".$net.", Zigate version ".$msg['major']."-".$msg['minor']);
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: No zigate for network ".$net);
                return;
            }

            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'FW-Version');
            if ($cmdLogic) {
                $savedVersion = $cmdLogic->execCmd(); // MMMM-mmmm
                $version = $msg['major'].'-'.$msg['minor'];
                if ($savedVersion != $version)
                    logMessage('debug', '  FW saved version: '.$savedVersion);
                if ($savedVersion == '---------') {
                    $gtwId = substr($net, 7);
                    // Zigate mode is now HYBRID in all cases. Abeille is no longer able to deal with older firmwares
                    if ($msg['major'] == 'AB01') { // Abeille's FW for Zigate v1
                        // logMessage('debug', '  FW version AB01 => Configuring zigate '.$gtwId.' in hybrid mode');
                        // publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$gtwId."/0000/zgSetMode", "mode=hybrid");
                    } else {
                        if (hexdec($msg['minor']) >= 0x031D) {
                            // logMessage('debug', '  FW version >= 3.1D => Configuring zigate '.$gtwId.' in hybrid mode');
                            // publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$gtwId."/0000/zgSetMode", "mode=hybrid");
                        } else {
                            // logMessage('debug', '  Old FW. Configuring zigate '.$gtwId.' in normal mode');
                            // publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$gtwId."/0000/zgSetMode", "mode=normal");
                        }
                        // TODO: Different msg according to v1 or v2
                        if (hexdec($msg['minor']) < 0x0321) {
                            if (hexdec($msg['minor']) < 0x031E)
                                message::add('Abeille', 'Attention: La zigate '.$gtwId.' fonctionne avec un trop vieux FW incompatible avec Abeille. Merci de faire une mise-à-jour en 3.21 ou supérieur.');
                            else
                                message::add('Abeille', "Il est nécessaire de mettre à jour votre Zigate avec la version '3.23'.");
                        }
                    }
                }
                $eqLogic->checkAndUpdateCmd($cmdLogic, $version);
            }

            updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'zigateVersion'

        /* Zigate time (8017 response) */
        if ($msg['type'] == "zigateTime") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'zigateTime',
                'net' => $dest,
                'timeServer' => $data,
                'time' => time()
             */

            logMessage('debug', "msgFromParser(): ".$net.", Zigate timeServer ".$msg['time']);
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'ZiGate-Time');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['timeServer']);

            updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'zigateTime'

        /* Zigate TX power (8806/8807 responses) */
        if ($msg['type'] == "zigatePower") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'zigatePower',
                'net' => $dest,
                'power' => $power,
                'time' => time()
             */

            logMessage('debug', "msgFromParser(): ".$net.", Zigate power ".$msg['power']);
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'ZiGate-Power');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['power']);

            updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'zigatePower'

        /* Network state (8009 response) */
        if ($msg['type'] == "networkState") {
            /* Reminder
            $msg = array(
                'src' => 'parser',
                'type' => 'networkState',
                'net' => $dest,
                'addr' => $ShortAddress,
                'ieee' => $ExtendedAddress,
                'panId' => $PAN_ID,
                'extPanId' => $Ext_PAN_ID,
                'chan' => $Channel,
                'time' => time()
            ); */

            logMessage('debug', "msgFromParser(): ".$net.", network state, ieee=".$msg['ieee'].", chan=".$msg['chan']);

            checkZgIeee($net, $msg['ieee']);

            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            // $ieee = $eqLogic->getConfiguration('IEEE', '');
            // if (($ieee != '') && ($ieee != $msg['ieee'])) {
            //     logMessage('debug', "  ERROR: IEEE mistmatch, got ".$msg['ieee']." while expecting ".$ieee);
            //     return;
            // }
            $curIeee = $eqLogic->getConfiguration('IEEE', '');
            if ($curIeee != $msg['ieee']) {
                $eqLogic->setConfiguration('IEEE', $msg['ieee']);
                $eqLogic->save();
            }

            $eqId = $eqLogic->getId();
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'PAN-ID');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['panId']);
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Ext_PAN-ID');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['extPanId']);
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Network-Channel');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['chan']);

            updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'networkState'

        /* Network started (8024 response) */
        if ($msg['type'] == "networkStarted") {
            /* Reminder
            $msg = array(
                'src' => 'parser',
                'type' => 'networkStarted',
                'net' => $dest,
                'status' => $status,
                'statusTxt' => $data,
                'addr' => $dataShort, // Should be always 0000
                'ieee' => $dataIEEE, // Zigate IEEE
                'chan' => $dataNetwork,
                'time' => time()
            ); */

            logMessage('debug', "msgFromParser(): ".$net.", network started, ieee=".$msg['ieee'].", chan=".$msg['chan']);

            checkZgIeee($net, $msg['ieee']);

            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $ieee = $eqLogic->getConfiguration('IEEE', '');
            if (($ieee != '') && ($ieee != $msg['ieee'])) {
                logMessage('debug', "  ERROR: IEEE mistmatch, got ".$msg['ieee']." while expecting ".$ieee);
                return;
            }
            $eqId = $eqLogic->getId();
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Network-Status');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['statusTxt']);
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Network-Channel');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['chan']);

            updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'networkStarted'

        /* Permit join status (8014 response) */
        if ($msg['type'] == "permitJoin") {
            /* Reminder
                'src' => 'parser',
                'type' => 'permitJoin',
                'net' => $dest,
                'status' => $Status,
                'time' => time()
            ); */

            logMessage('debug', "msgFromParser(): ".$net.", permit join, status=".$msg['status']);
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $eqId = $eqLogic->getId();
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'permitJoin-Status');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['status']);
            updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'permitJoin'

        /* Bind response (8030 response) */
        if ($msg['type'] == "bindResponse") {
            // 'src' => 'parser',
            // 'type' => 'bindResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'status' => $status,
            // 'time' => time(),
            // 'lqi' => $lqi,
            logMessage('debug', "msgFromParser(): ".$net."/".$addr.", Bind response, status=".$msg['status']);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if ($eqLogic)
                updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);

            return;
        } // End 'bindResponse'

        /* IEEE address response (8041 response) */
        if ($msg['type'] == "ieeeAddrResponse") {
            // 'type' => 'ieeeAddrResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ieee' => $ieee,
            // 'time' => time(),
            // 'lqi' => $lqi,
            logMessage('debug', "msgFromParser(): ".$net."/".$addr.", IEEE addr response, ieee=".$msg['ieee']);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!$eqLogic) {
                logMessage('debug', "  WARNING: Unknown device");
                return;
            }
            $curIeee = $eqLogic->getConfiguration('IEEE', '');
            if ($curIeee == '') {
                $eqLogic->setConfiguration('IEEE', $msg['ieee']);
                $eqLogic->save();
                $eqLogic->refresh();
                logMessage('debug', "  Device IEEE updated.");
            } else if ($curIeee != $msg['ieee']) {
                logMessage('debug', "  WARNING: Device has a different IEEE => UNEXPECTED !!");
            }

            updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'ieeeAddrResponse'

        /* AddGroup/removeGroup/getGroupMembership responses */
        if (($msg['type'] == "addGroupResponse") || ($msg['type'] == "removeGroupResponse") || ($msg['type'] == "getGroupMembershipResponse")) {
            // // 'src' => 'parser',
            // 'type' => 'addGroupResponse'/'removeGroupResponse'/'getGroupMembershipResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ep' => $srcEp,
            // 'groups' => $grp
            // 'time' => time(),
            // 'lqi' => $lqi
            logMessage('debug', "msgFromParser(): ".$net."/".$addr."/".$ep.", ".$msg['type'].", groups=".$msg['groups']);
            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                logMessage('debug', "  Unknown device '".$net."/".$addr."'");
                return; // Unknown device
            }

            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            $newZigbee = $zigbee;
            if (!isset($newZigbee['groups']))
                $newZigbee['groups'] = [];
            if (!isset($newZigbee['groups'][$ep]))
                $newZigbee['groups'][$ep] = '';
            $groups = $newZigbee['groups'][$ep];
            if ($msg['type'] == 'getGroupMembershipResponse') {
                $groups = $msg['groups'];
            } else if ($msg['type'] == 'addGroupResponse') {
                if ($groups == '')
                    $groups = $msg['groups'];
                else
                    $groups .= '/'.$msg['groups'];
            } else { // removeGroupResponse
                $groupsArr = explode("/", $groups);
                foreach ($groupsArr as $gIdx => $g) {
                    if ($g == $msg['groups'])
                        unset($groupsArr[$gIdx]);
                }
                $groups = implode("/", $groupsArr);
            }
            $newZigbee['groups'][$ep] = $groups;
            if ($newZigbee != $zigbee) {
                $eqLogic->setConfiguration('ab::zigbee', $newZigbee);
                $eqLogic->save();
            }

            updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'addGroupResponse'/'removeGroupResponse'/'getGroupMembershipResponse'

        logMessage('debug', "msgFromParser(): Ignored msg ".json_encode($msg));
    } // End msgFromParser()

    /* Create or update Jeedom device based on its JSON model.
       Called in the following cases
       - On 'eqAnnounce' message from parser (device announce) => action = 'update'
       - To create/update a virtual 'remotecontrol' => action = 'update'
       - To update from JSON (identical to re-inclusion) => action = 'update'
       - To force a different model than the one auto-detected => action = 'update'
     */
    function createDevice($action, $dev) {
        logMessage('debug', '  createDevice('.$action.', dev='.json_encode($dev, JSON_UNESCAPED_SLASHES));

        /* $action reminder
              'update' => create or update device (device announce/update)
              'reset' => create or reset device from model (user request)
           $dev reminder
                $dev = array(
                    'net' =>
                    'addr' =>
                    'modelSource' => 'Abeille', // Model file location ('Abeille' or 'local')
                    'modelName' => 'remotecontrol', // Model file name
                    'modelSig' => 'remotecontrol', // Model signature
                );
         */

        if (!isset($dev['net']) || !isset($dev['addr'])) {
            logMessage('error', "  createDevice(): 'net' et/ou 'addr' non renseigné");
            return;
        }
        $net = $dev['net'];
        $addr = $dev['addr'];
        $eqLogicId = "{$net}/{$addr}";
        $eqLogic = eqLogic::byLogicalId($eqLogicId, 'Abeille');

        // Do we have all informations about model to use ?
        if (isset($dev['modelName'])) {
            if (isset($dev['modelSource']))
                $modelSource = $dev['modelSource'];
            $modelName = $dev['modelName'];
            if (isset($dev['modelPath']))
                $modelPath = $dev['modelPath'];
            $modelForced = isset($dev['modelForced']) ? $dev['modelForced']: false;

            if (isset($dev['modelSig']))
                $modelSig = $dev['modelSig'];
        }
        if ((!isset($modelName) || !isset($modelSig)) && is_object($eqLogic)) {
            // Missing modelName and/or modelSig => already stored in ab::eqModel ?
            $jEqModel = $eqLogic->getConfiguration('ab::eqModel', []); // Eq model from Jeedom DB
            if (!isset($modelName)) {
                $modelSource = isset($jEqModel['modelSource']) ? $jEqModel['modelSource']: '';
                $modelName = isset($jEqModel['modelName']) ? $jEqModel['modelName']: '';
                if (isset($jEqModel['modelPath']))
                    $modelPath = $jEqModel['modelPath'];
                $modelForced = isset($jEqModel['modelForced']) ? $jEqModel['modelForced']: false;
            }
            if (!isset($modelSig) && isset($jEqModel['modelSig']))
                $modelSig = $jEqModel['modelSig'];
        }
        if (!isset($modelName)) {
            logMessage('error', "  createDevice({$net}/{$addr}): 'modelName' non renseigné");
            return;
        }

        // Ok, 'modelName' is defined
        if ($modelSource == '')
            $modelSource = 'Abeille';
        if (!isset($modelPath))
            $modelPath = "{$modelName}/{$modelName}.json";
        if (!isset($modelSig))
            $modelSig = $modelName;

        if (($modelSource != '') && ($modelPath != '')) {
            // $model = AbeilleTools::getDeviceModel($modelSig, $modelName, $modelSource);
            $model = getDeviceModel($modelSource, $modelPath, $modelName, $modelSig);
            if ($model === false) {
                logMessage('debug', '  ERRRRRRRRR');
                return;
            }

            logMessage('debug', '  Model='.json_encode($model, JSON_UNESCAPED_SLASHES));
            $eqType = $model['type'];
        }

        if (!is_object($eqLogic)) {
            $newEq = true;

            // if ($action != 'create') {
            //     logMessage('debug', '  ERROR: Action='.$action.' but device '.$eqLogicId.' does not exist');
            //     return;
            // }

            // $action == 'create'
            logMessage('debug', '  New device '.$eqLogicId);
            if ($modelName != "defaultUnknown")
                message::add("Abeille", "Nouvel équipement identifié (".$eqType."). Création en cours. Rafraîchissez votre dashboard dans qq secondes.", '');
            else
                message::add("Abeille", "Nouvel équipement détecté mais non supporté. Création en cours avec la config par défaut (".$modelName."). Rafraîchissez votre dashboard dans qq secondes.", '');

            $eqLogic = new Abeille();
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setName("newDevice-".$dev['addr']); // Temp name to have it non empty
            $eqLogic->save(); // Save to force Jeedom to assign an ID

            $eqId = $eqLogic->getId();
            $eqName = $eqType." - ".$eqId; // Default name (ex: '<eqType> - 12')
            $eqLogic->setName($eqName);
            $eqLogic->setLogicalId($eqLogicId);
            // $config = AbeilleTools::getConfig();
            $config = $GLOBALS['config']; // Present as global since main daemon
            $eqLogic->setObject_id($config['ab::defaultParent']);
            if (isset($dev['ieee'])) $eqLogic->setConfiguration('IEEE', $dev['ieee']); // No IEEE for virtual remote
        } else {
            $newEq = false;

            $eqHName = $eqLogic->getHumanName(); // Jeedom hierarchical name
            logMessage('debug', '  Already existing device '.$eqLogicId.' => '.$eqHName);

            // Kept for safety but should already be assigned in 'special case' block
            $jEqModel = $eqLogic->getConfiguration('ab::eqModel', []); // Eq model from Jeedom DB
            $curEqModel = isset($jEqModel['modelName']) ? $jEqModel['modelName'] : ''; // Current JSON model
            $ieee = $eqLogic->getConfiguration('IEEE'); // IEEE from Jeedom DB
            $eqId = $eqLogic->getId();

            if ($curEqModel == '') { // Jeedom eq exists but init not completed
                $eqName = $eqType." - ".$eqId; // Default name (ex: '<eqType> - 12')
                $eqLogic->setName($eqName);
                message::add("Abeille", $eqHName.": Nouvel équipement identifié.", '');
                $action = 'reset';
            } else if (($curEqModel == 'defaultUnknown') && ($modelName != 'defaultUnknown')) {
                message::add("Abeille", $eqHName.": S'est réannoncé => Mise-à-jour du modèle par défaut vers '".$eqType."'", '');
                $action = 'reset'; // Update from defaultUnknown = reset to new model
            }
            // else if ($action == "update")
            //     message::add("Abeille", $eqHName.": Mise-à-jour à partir de son modèle (source=".$modelSource.")");
            else if ($action == "reset")
                message::add("Abeille", $eqHName.": Réinitialisation à partir de '".$modelName."' (source=".$modelSource.")");
            else { // action = create
                /* Tcharp38: Following https://github.com/KiwiHC16/Abeille/issues/2132#, device re-announce is just ignored here
                    to not generate plenty messages, unless device was disabled.
                    Other reasons to generate message ?
                */
                if ($eqLogic->getIsEnable() != 1)
                    message::add("Abeille", $eqHName.": S'est réannoncé => Mise-à-jour à partir de son modèle (source=".$modelSource.")");
            }
        }

        if ($modelSource == "local") {
            $fullPath = __DIR__."/../config/devices/{$modelName}/{$modelName}.json";
            if (file_exists($fullPath))
                message::add("Abeille", $eqHName.": Attention ! Modèle local (devices_local) utilisé alors qu'un modèle officiel existe.", '');
        }

        /* Whatever creation or update, common steps follows */
        $modelConf = $model["configuration"];
        logMessage('debug', '  modelConfig='.json_encode($modelConf));

        /* mainEP: Used to define default end point to target, when undefined in command itself (use of '#EP#'). */
        if (isset($modelConf['mainEP'])) {
            $mainEP = $modelConf['mainEP'];
        } else {
            logMessage('debug', '  WARNING: Undefined mainEP => defaulting to 01');
            $mainEP = "01";
        }
        $eqLogic->setConfiguration('mainEP', $mainEP);

        // OBSOLETE: Moved to 'ab::zigbee'
        // if (isset($dev['modelId'])) {
        //     $sig = array(
        //         'modelId' => $dev['modelId'],
        //         'manufId' => $dev['manufId'],
        //     );
        //     $eqLogic->setConfiguration('ab::signature', $sig);
        // }

        // Icon updated if no-longer-exists/reset/undefined/defaultUnknown
        $curIcon = $eqLogic->getConfiguration('ab::icon', '');
        if ($curIcon != '') {
            $iconPath = __DIR__.'/../../images/node_'.$curIcon.'.png';
            $iconExists = file_exists($iconPath);
        } else {
            $iconPath = '';
            $iconExists = false;
        }
        // logMessage('debug', 'LA iconExists='.$iconExists.', path='.$iconPath);
        if (!$iconExists || ($action == 'reset') || ($curIcon == '') || ($curIcon == 'defaultUnknown')) {
            if (isset($modelConf["icon"]))
                $icon = $modelConf["icon"];
            else
                $icon = '';
            $eqLogic->setConfiguration('ab::icon', $icon);
        }

        // Update only if new device (missing info) or reinit
        $curTimeout = $eqLogic->getTimeout(null);
        if (($action == 'reset') || ($curTimeout === null)) {
            if (isset($model["timeout"]))
                $eqLogic->setTimeout($model["timeout"]);
            else
                $eqLogic->setTimeout(null);
        }
        $curCats = $eqLogic->getCategory();
        if (($action == 'reset') || (count($curCats) == 0)) {
            if (isset($model["category"])) {
                $categories = $model["category"];
                $allCat = ["heating", "security", "energy", "light", "opening", "automatism", "multimedia", "default"];
                foreach ($allCat as $cat) { // Clear all
                    $eqLogic->setCategory($cat, "0");
                }
                foreach ($categories as $key => $value) {
                    $eqLogic->setCategory($key, $value);
                }
            }
            // TODO: If no category defined, default value to be set
        }

        // Update only if new device or reinit
        if (($action == 'reset') || $newEq) {
            // isVisible: Reseted when leaving network (ex: reset). Must be set when rejoin unless defined in model.
            if (isset($model["isVisible"]))
                $eqLogic->setIsVisible($model["isVisible"]);
            else
                $eqLogic->setIsVisible(1);
        }

        // Tcharp38: Seems no longer used
        // $lastCommTimeout = (array_key_exists("lastCommunicationTimeOut", $modelConf) ? $modelConf["lastCommunicationTimeOut"] : '-1');
        // $eqLogic->setConfiguration('lastCommunicationTimeOut', $lastCommTimeout);

        if (isset($modelConf['batteryType']))
            $eqLogic->setConfiguration('battery_type', $modelConf['batteryType']);
        else
            $eqLogic->setConfiguration('battery_type', null);

        if (isset($modelConf['paramType']))
            $eqLogic->setConfiguration('paramType', $modelConf['paramType']);

        // OBSOLETE: Replaced by use of 'variables' section
        // if (isset($modelConf['Groupe'])) { // Tcharp38: What for ? Telecommande Innr - KiwiHC16: on doit pouvoir simplifier ce code. Mais comme c etait la premiere version j ai fait detaillé.
        //     $eqLogic->setConfiguration('Groupe', $modelConf['Groupe']);
        // }

        // #GROUPEPx# variables now stored as generic vars in 'variables' section and replacement already done by getDeviceModel()
        // Temporary support for 'groupEPx' (to replace #GROUPEPx#)
        // Constant used to define remote control group per EP
        // for ($g = 1; $g <= 8; $g++) {
        //     if (isset($modelConf['groupEP'.$g]))
        //         $eqLogic->setConfiguration('groupEP'.$g, $modelConf['groupEP'.$g]);
        //     else
        //         $eqLogic->setConfiguration('groupEP'.$g, null);
        // }

        if (isset($modelConf['onTime'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('onTime', $modelConf['onTime']);
        }
        if (isset($modelConf['poll']))
            $eqLogic->setConfiguration('poll', $modelConf['poll']);
        else
            $eqLogic->setConfiguration('poll', null);

        // Tuya specific infos: OBSOLETE ! Replaced by 'private' + 'EF00' + 'type=tuya'
        // if (isset($model['tuyaEF00']))
        //     $eqLogic->setConfiguration('ab::tuyaEF00', $model['tuyaEF00']);
        // else
        //     $eqLogic->setConfiguration('ab::tuyaEF00', null);

        // Xiaomi specific infos: OBSOLETE soon. Replaced by 'fromDevice'
        // if (isset($model['xiaomi']))
        //     $eqLogic->setConfiguration('ab::xiaomi', $model['xiaomi']);
        // else
        //     $eqLogic->setConfiguration('ab::xiaomi', null);

        // Zigbee & customization from model
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        if (isset($model['customization'])) {
            $eqLogic->setConfiguration('ab::customization', $model['customization']);
            if (isset($model['customization']['macCapa'])) {
                $zigbee['macCapa'] = $model['customization']['macCapa'];
                logMessage('debug', "  'macCapa' forced to ".$zigbee['macCapa']);
            }
        } else {
            $eqLogic->setConfiguration('ab::customization', null);
            if (isset($dev['macCapa'])) {
                $zigbee['macCapa'] = $dev['macCapa'];
            }
        }
        if (isset($zigbee['macCapa']) && ($zigbee['macCapa'] != '')) {
            $mc = hexdec($zigbee['macCapa']);
            $zigbee['mainsPowered'] = ($mc >> 2) & 0b1; // 1=mains-powered
            $zigbee['rxOnWhenIdle'] = ($mc >> 3) & 0b1; // 1=Receiver enabled when idle
        }
        if (isset($dev['modelId'])) { // Was previously stored in 'ab::signature'
            $zigbee['modelId'] = $dev['modelId'];
            $zigbee['manufId'] = $dev['manufId'];
        }
        $eqLogic->setConfiguration('ab::zigbee', $zigbee);

        // JSON model infos => 'ab::eqModel'
        $eqModelInfos = array(
            // Model infos
            'modelSource' => $modelSource, // Equipment model file location
            'modelName' => $modelName, // Equipment model file name
            // 'modelPath' => $modelPath, // OPTIONAL: Model path if variant (<modelName>/<modelName>[-variantX].json)
            'modelForced' => $modelForced,

            'modelSig' => $modelSig, // Equipment model signature (!= modelName if alternate ID or forced model)

            // Equipment infos
            'manuf' => isset($model['manufacturer']) ? $model['manufacturer'] : '',
            'model' => isset($model['model']) ? $model['model'] : '',
            'type' => $model['type'],

            // 'lastUpdate' => time(), // Store last update from model. // Tcharp38: created for Abeille but not used
            // 'variables' // Optional
            // 'private' // Optional
        );
        if ($modelPath != "{$modelName}/{$modelName}.json")
            $eqModelInfos['modelPath'] = $modelPath;
        if (isset($model['private'])) // Private cluster or command specific infos
            $eqModelInfos['private'] = $model['private'];
        // else if (isset($model['fromDevice'])) // OBSOLETE soon => replaced by 'private'
        //     $eqModelInfos['fromDevice'] = $model['fromDevice'];
        if (isset($model['variables'])) // Optional variables
            $eqModelInfos['variables'] = $model['variables'];
        $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

        // generic_type
        if (isset($model['genericType']))
            $eqLogic->setGenericType($model['genericType']);
        else
            $eqLogic->setGenericType(null);

        $eqLogic->setIsEnable(1);
        $eqLogic->save();

        /* During commands creation #EP# must be replaced by proper endpoint.
           If not already done, using default (mainEP) value */
        if (isset($model['commands'])) {
            $modelCmds = $model['commands'];
            $modelCmds2 = json_encode($modelCmds, JSON_UNESCAPED_SLASHES);
            if (strstr($modelCmds2, '#EP#') !== false) {
                if ($mainEP == "") {
                    message::add("Abeille", "'mainEP' est requis mais n'est pas défini dans '".$modelName.".json'", '');
                    $mainEP = "01";
                }

                logMessage('debug', '  mainEP='.$mainEP);
                $modelCmds2 = str_ireplace('#EP#', $mainEP, $modelCmds2);
                $modelCmds = json_decode($modelCmds2, true);
                logMessage('debug', '  Updated commands='.json_encode($modelCmds));
            }
        }

        /* Creating list of current Jeedom commands.
           jeedomCmds[jCmdId] = array(
                'name' =>
                'logicalId' =>
                'topic' =>
                'request' =>
                'obsolete' =>
           )
           Note: DO NOT split commands by info/action. It's key to be sure that both name & logicalId are UNIQUE */
        $jCmds = Cmd::byEqLogicId($eqId);
        $jeedomCmds = []; // List of current Jeedom commands
        foreach ($jCmds as $cmdLogic) {
            $cmdType = $cmdLogic->getType();
            $cmdName = $cmdLogic->getName(); // == jCmdName (Jeedom cmd name)
            $cmdLogicId = $cmdLogic->getLogicalId('');
            $cmdId = $cmdLogic->getId();
            $cmdTopic = $cmdLogic->getConfiguration('topic', '');
            $cmdReq = $cmdLogic->getConfiguration('request', '');
            if ($cmdType == 'info')
                logMessage('debug', "  Jeedom ".$cmdType.": name='".$cmdName."' ('".$cmdLogicId."'), id=".$cmdId);
            else
                logMessage('debug', "  Jeedom ".$cmdType.": name='".$cmdName."' ('".$cmdLogicId."'), id=".$cmdId.", topic='".$cmdTopic."', req='".$cmdReq."'");
            $c = array(
                'name' => $cmdName,
                'logicalId' => $cmdLogicId,
                'topic' => $cmdTopic, // action only
                'request' => $cmdReq, // action only
                'obsolete' => (strpos($cmdLogicId, '::') === false) ? true : false // User cmd must not be removed
            );
            $jeedomCmds[$cmdId] = $c;
        }

        // Creating or updating commands based on model content.
        // Tcharp38: WARNING: Faced an issue with a command whose logicalId changed (SWBuildID, logicId=0000-01-4000 newLogicId=0000-03-4000)
        // How to handle such case ?
        $order = 0;
        foreach ($modelCmds as $mCmdName => $mCmd) {
            // Initial checks
            if (!isset($mCmd["type"])) {
                logMessage('error', "{{La commande suivante n'a pas de type défini}}: '{$mCmdName}'");
                continue;
            }
            $mCmdType = $mCmd["type"];
            if ($mCmdType == 'info') {
                if (!isset($mCmd["logicalId"]) || ($mCmd["logicalId"] == '')) {
                    logMessage('error', "{{La commande suivante n'a pas de 'logicalId' défini}}: '{$mCmdName}'");
                    continue;
                }
            } else if ($mCmdType == 'action') {
                // Any checks ?
            } else {
                logMessage('error', "{{La commande suivante a un type invalide}}: '{$mCmdName}'");
                continue;
            }

            if ($mCmdType == "action") {
                $mCmdTopic = isset($mCmd["configuration"]['topic']) ? $mCmd["configuration"]['topic'] : ''; // Abeille command name
                $mCmdReq = isset($mCmd["configuration"]['request']) ? $mCmd["configuration"]['request'] : ''; // Abeille command parameters
            }
            $mCmdLogicId = isset($mCmd["logicalId"]) ? $mCmd["logicalId"] : '';

            /* Looking for corresponding cmd in Jeedom.
               New or existing cmd ?
               Note that 'info' cmds are uniq thanks to their logicalId. Not the case so far for 'action' which may lead
               to cmd deleted and recreated if cmd name has changed, and therefore lead to orpheline cmd if deleted one
               was used somewhere in Jeedom.
             */
            // $cmdLogic = null;
            $cmdId = null;

            // Search by logical ID
            logMessage('debug', "  Searching by logical ID: '".$mCmdLogicId."'");
            foreach ($jeedomCmds as $jCmdId => $jCmd) {
                // Note: Cmd logical ID & names have to be unique
                // if (($jCmd['logicalId'] != $mCmdLogicId) && ($jCmd['name'] != $mCmdName))
                //     continue;
                if ($jCmd['logicalId'] != $mCmdLogicId)
                    continue;
                $cmdId = $jCmdId;
                $jeedomCmds[$jCmdId]['obsolete'] = false;
                break; // Found
            }

            // Search by name if still not found
            if ($cmdId === null) {
                logMessage('debug', "  Searching by name: '".$mCmdName."'");
                foreach ($jeedomCmds as $jCmdId => $jCmd) {
                    if (($jCmd['name'] != '') && ($jCmd['name'] != $mCmdName))
                        continue;
                    $cmdId = $jCmdId;
                    $jeedomCmds[$jCmdId]['obsolete'] = false;
                    break; // Found
                }
            }

            // Search by topic/request if still not found & 'action'
            // DISABLED !! Does not work when adding new commands (then no name nor logicId match) but same topic/request
            // if (($cmdId === null) && ($mCmdType == 'action')) {
            //     $mTopic = $mCmd["configuration"]['topic'];
            //     $mRequest = $mCmd["configuration"]['request'];
            //     logMessage('debug', "  Searching by topic/request='".$mTopic."/".$mRequest."'");
            //     foreach ($jeedomCmds as $jCmdId => $jCmd) {
            //         if ($jCmd['topic'] != $mTopic)
            //             continue;
            //         if ($jCmd['request'] != $mRequest)
            //             continue;
            //         $cmdId = $jCmdId;
            //         $jeedomCmds[$jCmdId]['obsolete'] = false;
            //         break;
            //     }
            // }

            if ($cmdId === null) { // Not found => new command
                $newCmd = true;
                if ($mCmdType == 'info')
                    logMessage('debug', "  Adding ".$mCmdType." '".$mCmdName."' (".$mCmdLogicId.")");
                else
                    logMessage('debug', "  Adding ".$mCmdType." '".$mCmdName."' (".$mCmdLogicId."), topic='".$mCmdTopic."', request='".$mCmdReq."'");
                $cmdLogic = new cmd();
                $cmdLogic->setEqLogic_id($eqId);
                $cmdLogic->setEqType('Abeille');
            } else {
                $newCmd = false;
                logMessage('debug', '  found: id='.$cmdId);
                $cmdLogic = cmd::byId($cmdId);
                $jCmdName = $cmdLogic->getName();
                $jCmdLogicId = $cmdLogic->getLogicalId();
                if ($mCmdType == 'info')
                    logMessage('debug', "  Updating ".$mCmdType." '".$jCmdName."' (".$jCmdLogicId.")");
                else {
                    logMessage('debug', "  Updating ".$mCmdType." '".$jCmdName."' (".$jCmdLogicId.") => logicId='".$mCmdLogicId."', topic='".$mCmdTopic."', request='".$mCmdReq."'");
                    $jeedomCmds[$cmdId]['topic'] = $mCmdTopic;
                    $jeedomCmds[$cmdId]['request'] = $mCmdReq;
                }
            }

            $cmdLogic->setType($mCmdType); // 'info' or 'action': Always updated in case type change for same name
            $cmdLogic->setSubType($mCmd["subType"]);
            $cmdLogic->setOrder($order++);
            $cmdLogic->setLogicalId($mCmdLogicId);
            if ($cmdId !== null)
                $jeedomCmds[$cmdId]['logicalId'] = $mCmdLogicId;

            // Updates only if reset or new command
            if (($action == 'reset') || $newCmd) {
                if (isset($mCmd["unit"]))
                    $cmdLogic->setUnite($mCmd["unit"]);
                else
                    $cmdLogic->setUnite(null); // Clear unit

                $cmdLogic->setName($mCmdName);

                if (isset($mCmd["isHistorized"]))
                    $cmdLogic->setIsHistorized($mCmd["isHistorized"]);
                else
                    $cmdLogic->setIsHistorized(0);

                if (isset($mCmd["isVisible"]))
                    $cmdLogic->setIsVisible($mCmd["isVisible"]);
                else
                    $cmdLogic->setIsVisible(0);
            }

            // Updates only if new command or reinit or missing entry
            $curGenericType = $cmdLogic->getGeneric_type();
            if ($curGenericType === null)
                $curGenericType = '';
            if (($action == 'reset') || $newCmd || ($curGenericType == '')) {
                if (isset($mCmd["genericType"]))
                    $cmdLogic->setGeneric_type($mCmd["genericType"]);
                else
                    $cmdLogic->setGeneric_type(null); // Clear generic type
            }
            $curDashbTemplate = $cmdLogic->getTemplate('dashboard', '');
            // logMessage('debug', '  curDashbTemplate='.$curDashbTemplate);
            if (($action == 'reset') || $newCmd || ($curDashbTemplate == '')) {
                if (isset($mCmd["template"]) && ($mCmd["template"] != "")) {
                    logMessage('debug', '  Set dashboard template='.$mCmd["template"]);
                    $cmdLogic->setTemplate('dashboard', $mCmd["template"]);
                }
            }
            $curMobTemplate = $cmdLogic->getTemplate('mobile', '');
            if (($action == 'reset') || $newCmd || ($curMobTemplate == '')) {
                if (isset($mCmd["template"]) && ($mCmd["template"] != "")) {
                    $cmdLogic->setTemplate('mobile', $mCmd["template"]);
                }
            }

            if ($mCmdType == "info") { // info cmd
            } else { // action cmd
                if (isset($mCmd["value"])) {
                    // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                    logMessage('debug', '  Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$mCmd["value"]);

                    $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $mCmd["value"]);
                    if ($cmdPointeur_Value)
                        $cmdLogic->setValue($cmdPointeur_Value->getId());
                }
            }

            /* Updating command 'configuration' fields.
               In case of update, some fields may no longer be required ($unusedConfKeys).
               They are removed if not defined in JSON model. */
            $toRemove = ['visibilityCategory', 'historizeRound', 'execAtCreation', 'execAtCreationDelay', 'topic', 'Polling', 'RefreshData', 'listValue'];
            array_push($toRemove, 'ab::trigOut', 'PollingOnCmdChange', 'PollingOnCmdChangeDelay', 'ab::notStandard');
            array_push($toRemove, 'ab::valueOffset', 'ab::repeat');
            $rmOnlyIfReset = $toRemove;
            array_push($rmOnlyIfReset, 'minValue', 'maxValue', 'calculValueOffset', 'repeatEventManagement');
            // Abeille specific keys must be renamed when taken from model (ex: trigOut => ab::trigOut)
            $toRename = ['trigOut', 'notStandard', 'valueOffset', 'repeat'];
            if (isset($mCmd["configuration"])) {
                $mCmdConf = $mCmd["configuration"];

                foreach ($mCmdConf as $confKey => $confValue) {
                    // Trick for conversion 'key' => 'ab::key' for Abeille specifics
                    // Note: this is currently not applied to all Abeille specific fields.
                    if (in_array($confKey, $toRename))
                        $confKey = "ab::".$confKey;

                    $cmdLogic->setConfiguration($confKey, $confValue);

                    // $confKey is used => no cleanup required
                    if ($action == 'reset') {
                        $keyIdx = array_search($confKey, $rmOnlyIfReset);
                        unset($rmOnlyIfReset[$keyIdx]);
                    } else {
                        $keyIdx = array_search($confKey, $toRemove);
                        unset($toRemove[$keyIdx]);
                    }
                }
            }

            /* Removing any obsolete 'configuration' fields (those remaining in 'unusedConfKeys') */
            if ($action == 'reset')
                $toRm = $rmOnlyIfReset;
            else
                $toRm = $toRemove;
            foreach ($toRm as $confKey) {
                // If key is defined but set to null, no way to detect this. So force remove all the time.
                // if ($cmdLogic->getConfiguration($confKey) == null)
                //     continue;
                // logMessage('debug', '  Removing obsolete configuration key: '.$confKey);
                $cmdLogic->setConfiguration($confKey, null); // Removing config entry
            }

            // On conserve l info du template pour la visibility
            // Tcharp38: What for ? Not found where it is used
            // if (isset($mCmd["isVisible"]))
            //     $cmdLogic->setConfiguration("visibiltyTemplate", $mCmd["isVisible"]);

            // Display stuff is updated only if new eq or new cmd to not overwrite user changes
            if (($action == 'reset') || $newCmd) {
                if (isset($mCmd["invertBinary"]))
                    $cmdLogic->setDisplay('invertBinary', $mCmd["invertBinary"]);
                else
                    $cmdLogic->setDisplay('invertBinary', null);

                if (isset($mCmd["nextLine"])) {
                    if ($mCmd["nextLine"] == "after")
                        $cmdLogic->setDisplay('forceReturnLineAfter', 1);
                    else
                        $cmdLogic->setDisplay('forceReturnLineBefore', 1);
                } else {
                    $cmdLogic->setDisplay('forceReturnLineAfter', null);
                    $cmdLogic->setDisplay('forceReturnLineBefore', null);
                }
            }
            if (isset($mCmd["disableTitle"])) // Disable title part of a 'message' action cmd
                $cmdLogic->setDisplay('title_disable', $mCmd["disableTitle"]);
            else
                $cmdLogic->setDisplay('title_disable', null);

            $cmdLogic->save();
        }

        // Removing obsolete cmds
        foreach ($jeedomCmds as $jCmdId => $jCmd) {
            if ($jCmd['obsolete'] == false)
                continue;

            logMessage('debug', "  Removing '".$jCmd['name']."' (".$jCmd['logicalId'].")");
            $cmdLogic = cmd::byId($jCmdId);
            $cmdLogic->remove();
        }

        // $eqLogic->refreshWidget(); // Refresh equipment display ? Required ? Useful ?

        // Inform cmd & parser that EQ config has changed
        $msg = array(
            'type' => "eqUpdated",
            'id' => $eqId,
        );
        msgToCmd2($msg);
        msgToParser($msg);
    } // End createDevice()

    /* Returns inclusion status: 1=include mode, 0=normal, -1=ERROR */
    function checkInclusionStatus($net) {
        $eqLogic = eqLogic::byLogicalId($net.'/0000', 'Abeille');
        if (!is_object($eqLogic) || ($eqLogic->getIsEnable() != 1))
            return -1;

        $cmdJoinStatus = $eqLogic->getCmd('info', 'permitJoin-Status');
        if (!is_object($cmdJoinStatus))
            return -1;

        $incStatus = $cmdJoinStatus->execCmd();
        // logMessage('debug', "incStatus=".$incStatus);
        if (($incStatus === 0) || ($incStatus === 1))
            return $incStatus;
        return -1;
    }

    /* Update all infos related to last communication time & LQI of given device.
       This is based on timestamp of last communication received from device itself. */
    function updateTimestamp($eqLogic, $timestamp, $lqi = null) {
        $eqLogicId = $eqLogic->getLogicalId();
        $eqId = $eqLogic->getId();

        // logMessage('debug', "  updateTimestamp(): Updating last comm. time for '".$eqLogicId."'");

        // Updating directly eqLogic/setStatus/'lastCommunication' & 'timeout' with real timestamp
        $eqLogic->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));

        /* Tcharp38 note:
           The cases hereafter could be removed. Using 'lastCommunication' allows to no longer
           use these 3 specific & redondant commands. To be discussed. */

        // $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-TimeStamp");
        // if (!is_object($cmdLogic))
        //     logMessage('debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-TimeStamp'");
        // else
        //     $eqLogic->checkAndUpdateCmd($cmdLogic, $timestamp);

        $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-Time");
        if (!is_object($cmdLogic))
            logMessage('debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-Time'");
        else
            $eqLogic->checkAndUpdateCmd($cmdLogic, date("Y-m-d H:i:s", $timestamp));

        $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'online');
        if (is_object($cmdLogic))
        //     logMessage('debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'online'");
        // else
            $eqLogic->checkAndUpdateCmd($cmdLogic, 1);

        list($net, $addr) = explode("/", $eqLogicId);
        if ($addr != "0000") { // Not a gateway
            if ($lqi !== null) {
                $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Link-Quality');
                if (!is_object($cmdLogic))
                    logMessage('debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Link-Quality'");
                else
                    $eqLogic->checkAndUpdateCmd($cmdLogic, $lqi);
            }

            // Updating corresponding Zigate alive status too
            $zigate = eqLogic::byLogicalId($net.'/0000', 'Abeille');
            $zigate->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));
            // Warning: lastCommunication update is not transmitted to client as not an info cmd
        }
    }

    function msgToParser($msg) {
        // global $abQueues;
        // $queue = msg_get_queue($abQueues['xToParser']['id']);
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

        global $queueXToParser;
        msg_send($queueXToParser, 1, $msgJson, false, false);
        logMessage('debug', "  Msg to Parser: ".$msgJson);
    }

    function msgToCmd($topic, $payload) {
        $msg = array();
        $msg['priority'] = PRIO_NORM;
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

        global $queueXToCmd;
        msg_send($queueXToCmd, 1, $msgJson, false, false);
        logMessage('debug', "  Msg to Cmd: ".$msgJson);
    }

    function msgToCmd2($msg) {
        // global $abQueues;
        // $queue = msg_get_queue($abQueues['xToCmd']['id']);
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

        global $queueXToCmd;
        msg_send($queueXToCmd, 1, $msgJson, false, false);
        logMessage('debug', "  Msg to Cmd: ".$msgJson);
    }

    // function publishMosquitto($queueId, $priority, $topic, $payload) {
    //     static $queueStatus = []; // "ok" or "error"

    //     $queue = msg_get_queue($queueId);
    //     if ($queue === false) {
    //         logMessage('error', "publishMosquitto(): La queue ".$queueId." n'existe pas. Message ignoré.");
    //         return;
    //     }
    //     if (($stat = msg_stat_queue($queue)) == false) {
    //         return; // Something wrong
    //     }

    //     /* To avoid plenty errors, checking if someone really reads the queue.
    //        If not, do nothing but a message to user first time.
    //        Note: Assuming potential pb if more than 50 pending messages. */
    //     $pendMsg = $stat['msg_qnum']; // Pending messages
    //     if ($pendMsg > 50) {
    //         if (file_exists("/proc/") && !file_exists("/proc/".$stat['msg_lrpid'])) {
    //             /* Receiver process seems down */
    //             if (isset($queueStatus[$queueId]) && ($queueStatus[$queueId] == "error"))
    //                 return; // Queue already marked "in error"
    //             message::add("Abeille", "Alerte ! Démon arrété ou planté. (Re)démarrage nécessaire.", '');
    //             $queueStatus[$queueId] = "error";
    //             return;
    //         }
    //     }

    //     $msg = array();
    //     $msg['priority'] = $priority;
    //     $msg['topic'] = $topic;
    //     $msg['payload'] = $payload;
    //     $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

    //     if (msg_send($queue, 1, $msgJson, false, false, $error_code)) {
    //         logMessage('debug', "  publishMosquitto(): Sent '".$msgJson."' to queue ".$queueId);
    //         $queueStatus[$queueId] = "ok"; // Status ok
    //     } else
    //         logMessage('warning', "publishMosquitto(): Impossible d'envoyer '".$msgJson."' vers queue ".$queueId);
    // } // End publishMosquitto()

    function checkZgIeee($net, $ieee) {
        $gtwId = substr($net, 7);
        $keyIeee = str_replace('Abeille', 'ab::zgIeeeAddr', $net); // AbeilleX => ab::zgIeeeAddrX
        $keyIeeeOk = str_replace('Abeille', 'ab::zgIeeeAddrOk', $net); // AbeilleX => ab::zgIeeeAddrOkX
        if (config::byKey($keyIeeeOk, 'Abeille', 0) == 0) {
            $ieeeConf = config::byKey($keyIeee, 'Abeille', '');
            if ($ieeeConf == "") {
                config::save($keyIeee, $ieee, 'Abeille');
                config::save($keyIeeeOk, 1, 'Abeille');
            } else if ($ieeeConf == $ieee) {
                config::save($keyIeeeOk, 1, 'Abeille');
            } else {
                config::save($keyIeeeOk, -1, 'Abeille');
                message::add("Abeille", "Attention: La zigate ".$gtwId." semble nouvelle ou il y a eu échange de ports. Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
            }
        }
    }

    // Check if received attribute is a battery information
    function checkIfBatteryInfo($eqLogic, $attrName, $attrVal) {
        if (($attrName == "Battery-Percent") || ($attrName == "Batterie-Pourcent")) {  // Obsolete
            $attrVal = round($attrVal, 0);
            logMessage('debug', "  Battery % reporting: ".$attrName.", val=".$attrVal);
            $eqLogic->setStatus('battery', $attrVal);
            $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        }  else if (preg_match("/^0001-[0-9A-F]*-0021/", $attrName)) {
            $attrVal = round($attrVal, 0);
            logMessage('debug', "  Battery % reporting: ".$attrName.", val=".$attrVal);
            $eqLogic->setStatus('battery', $attrVal);
            $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        }
    }

    /* Called on info cmd update (attribute report or attribute read) to see if any action cmd must be executed */
    function infoCmdUpdate($eqLogic, $cmdLogic, $value) {

        // Trig another command ('ab::trigOut' eqLogic config) ?
        // Syntax reminder
        // "trigOut": {
        //     "01-smokeAlarm": {
        //         "comment": "On receive we trig <EP>-smokeAlarm with extracted boolean/bit0 value",
        //         "valueOffset": "#value#&1"
        //     },
        //     "01-tamperAlarm": {
        //         "comment": "Bit 2 is tamper",
        //         "valueOffset": "(#value#>>2)&1"
        //     }
        // }
        $toList = $cmdLogic->getConfiguration('ab::trigOut', []);
        foreach ($toList as $trigLogicId => $to) {
            if (isset($to['valueOffset']))
                $trigOffset = $to['valueOffset'];
            else
                $trigOffset = '';
            trigCommand($eqLogic, $cmdLogic->execCmd(), $trigLogicId, $trigOffset);
        }
        // if ($trigLogicId) {
        //     $trigOffset = $cmdLogic->getConfiguration('ab::trigOutOffset');
        //     Abeille::trigCommand($eqLogic, $cmdLogic->execCmd(), $trigLogicId, $trigOffset);
        // }

        // Trig another command (PollingOnCmdChange keyword) ?
        global $abQueues;
        $cmds = cmd::searchConfigurationEqLogic($eqLogic->getId(), 'PollingOnCmdChange', 'action');
        $cmdLogicId = $cmdLogic->getLogicalId();
        foreach ($cmds as $cmd) {
            if ($cmd->getConfiguration('PollingOnCmdChange', '') != $cmdLogicId)
                continue;
            $delay = $cmd->getConfiguration('PollingOnCmdChangeDelay', '');
            $cmdName = $cmd->getName();
            $cmdLogicId = $cmd->getLogicalId();
            if ($delay != 0) {
                logMessage('debug', "  Triggering '{$cmdName}' ({$cmdLogicId}) with delay ".$delay);
                // publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".(time() + $delay), $cmd->getConfiguration('request'));
                msgToCmd("TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".(time() + $delay), $cmd->getConfiguration('request'));
            } else {
                logMessage('debug', "  Triggering '{$cmdName}' ({$cmdLogicId})");
                // publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".time(), $cmd->getConfiguration('request'));
                msgToCmd("TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".time(), $cmd->getConfiguration('request'));
            }
        }
    }

    /* Trig another command defined by 'trigLogicId'.
       The 'newValue' is computed with 'trigOffset' if required then applied to 'trigLogicId' */
    function trigCommand($eqLogic, $value, $trigLogicId, $trigOffset = '') {
        $trigCmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $trigLogicId);
        if (!is_object($trigCmd)) {
            logMessage('debug', "  trigCommand(): Unknown Jeedom command logicId='{$trigLogicId}'");
            return;
        }

        logMessage('debug', "  trigCommand(Val={$value}, TrigOffset='{$trigOffset}')");
        if ($trigOffset != '') {
            $vsPos = stripos($trigOffset, '#valueswitch-'); // Any #valueswitch-....# variable ?
            if ($vsPos !== false) {
                $vs = substr($trigOffset, $vsPos + 13);
                $vsPos2 = strpos($vs, '#');
                $varName = substr($vs, 0, $vsPos2);
                logMessage('debug', "  'valueswitch' detected: VarName='{$varName}'");

                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                $varUp = strtoupper($varName);
                if (!isset($eqModel['variables']) || !isset($eqModel['variables'][$varUp])) {
                    $eqHName = $eqLogic->getHumanName();
                    message::add("Abeille", "{$eqHName}: La variable '{$varUp}' n'est pas définie");
                    return;
                }
                $var = $eqModel['variables'][$varUp];
                logMessage('debug', "  Var=".json_encode($var, JSON_UNESCAPED_SLASHES));
                $varType = gettype($var);
                logMessage('debug', "  varType={$varType}");
                if ($varType == "array") {
                    // Variable is an array so keys are string. If value is int => convert to hex string.
                    logMessage('debug', "  valueType=".gettype($value));
                    if (gettype($value) != "string") {
                        $value2 = strval($value);
                        logMessage('debug', "  value2={$value2}");
                        $newValue = $var[$value2];
                    } else
                        $newValue = $var[$value];
                } else
                    $newValue = $var;
                logMessage('debug', "  newValue=".json_encode($newValue, JSON_UNESCAPED_SLASHES));
                $trigValue = jeedom::evaluateExpression(str_ireplace("#valueswitch-{$varName}#", $newValue, $trigOffset));
            } else
                $trigValue = jeedom::evaluateExpression(str_ireplace('#value#', $value, $trigOffset));
        } else
            $trigValue = $value;

        $trigName = $trigCmd->getName();
        logMessage('debug', "  Triggering cmd '{$trigName}' ({$trigLogicId}) with Val='{$trigValue}'");
        $eqLogic->checkAndUpdateCmd($trigCmd, $trigValue);

        // Is the triggered command a battery percent reporting ?
        if (preg_match("/^0001-[0-9A-F]*-0021/", $trigLogicId)) {
            $trigValue = round($trigValue, 0);
            logMessage('debug', "  Battery % reporting: {$trigLogicId}, Val={$trigValue}");
            $eqLogic->setStatus('battery', $trigValue);
            $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        }
    }

    logSetConf("AbeilleMainD.log", true);
    logMessage('info', ">>> Démarrage d'AbeilleMainD");

    $config = AbeilleTools::getConfig();
    $running = AbeilleTools::getRunningDaemons();
    $daemons = AbeilleTools::diffExpectedRunningDaemons($config, $running);
    logMessage('debug', 'Daemons status: '.json_encode($daemons));
    if ($daemons["main"] > 1){
        logMessage('error', "Un démon 'main' est déja lancé ! ".json_encode($daemons));
        exit(3);
    }

    declare(ticks = 1);
    pcntl_signal(SIGTERM, 'signalHandler', false);
    function signalHandler($signal) {
        logMessage('info', '<<< SIGTERM => Arret du démon AbeilleMainD');
        exit(0);
    }

    /* Main daemon starting.
        This means that other daemons have started too. Abeille can communicate with them */

    $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
    $queueXToAbeilleMax = $abQueues["xToAbeille"]["max"];
    $queueXToParser = msg_get_queue($abQueues['xToParser']['id']);
    $queueXToCmd = msg_get_queue($abQueues['xToCmd']['id']);

    // Create/update 'gateway' equipments
    $GLOBALS['config'] = $config;
    for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
        if ($config['ab::gtwPort'.$gtwId] == 'none')
            continue; // Port undefined

        if ($config['ab::gtwEnabled'.$gtwId] == 'Y') {
            // Create/update beehive equipment on Jeedom side
            // Note: This will reset 'FW-Version' to '---------' to mark FW version invalid.
            if ($config['ab::gtwType'.$gtwId] == "zigate")
                createRuche("Abeille{$gtwId}");
            else
                createEzspGateway("Abeille{$gtwId}");
        } else {
            // Gateway disabled. Ensure equipment is disabled too
            $eqLogic = eqLogic::byLogicalId("Abeille{$gtwId}/0000", 'Abeille');
            if (is_object($eqLogic) && ($eqLogic->getIsEnable() != 0)) {
                $eqLogic->setIsEnable(0);
                $eqLogic->save();
            }
        }
    }

    try {
        // Essaye de recuperer les etats des equipements
        // Tcharp38: Moved from deamon_start()
        refreshCmd();

        // $abQueues = $GLOBALS['abQueues'];
        // while(true) {
        //     $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
        //     if ($queueXToAbeille !== false)
        //         break;

        //     logMessage('debug', 'msg_get_queue(xToAbeille) ERROR');
        //     usleep(500000); // Sleep 500ms
        // }
        // $queueXToAbeilleMax = $abQueues["xToAbeille"]["max"];

        // https: github.com/torvalds/linux/blob/master/include/uapi/asm-generic/errno.h
        // const int EINVAL = 22;
        // const int ENOMSG = 42; /* No message of desired type */

        // Blocking queue read
        logMessage('debug', 'Infinite listening to queueXToAbeille');
        while (true) {
            logMessage('debug', 'msg_receive, msg_qnum='.msg_stat_queue($queueXToAbeille)["msg_qnum"]);
            if (@msg_receive($queueXToAbeille, 0, $rxMsgType, $queueXToAbeilleMax, $msgJson, false, 0, $errCode) == false) {
                if ($errCode == 7) {
                    msg_receive($queueXToAbeille, 0, $rxMsgType, $queueXToAbeilleMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                    logMessage('error', "Message (xToAbeille) trop grand ignoré: ".$msgJson);
                    continue; // Continue without sleeping
                }

                logMessage('debug', 'msg_receive(xToAbeille) erreur '.$errCode);
                usleep(500000); // Sleep 500ms
                continue;
            }

            $msg = json_decode($msgJson, true);
            if (isset($msg['topic']))
                message($msg['topic'], $msg['payload']);
            else
                msgFromParser($msg);
        }
    } catch (Exception $e) {
        logMessage('error', 'Exception '.$e->getMessage());
    }

    logMessage('debug', '<<< Main daemon exiting');
?>
