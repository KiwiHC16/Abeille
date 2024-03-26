<?php

    /*
     * LQI collector to draw nodes table & network graph
     * Called once a day by cron, or on user request from "Network" pages.
     *
     * Starting from zigate (coordinator), send LQI request to get neighbour table
     * - Send request thru AbeilleCmd (004E cmd)
     * - Get response from AbeilleParser (804E cmd)
     * - Identify each neighbour
     * - If neighbor is router, added to list for interrogation
     */

    include_once("../../core/config/Abeille.config.php");

    /* Developers debug features */
    if (file_exists(dbgFile)) {
        // include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__."/../../../../core/php/core.inc.php";
    include_once "AbeilleLog.php"; // Log library: logDebug(), logMessage()
    include_once "AbeilleModels.php"; // Models library

    /* Send msg to 'AbeilleCmd'
       Returns: 0=OK, -1=ERROR (fatal since queue issue) */
    function msgToCmd($prio, $topic, $payload = '') {
        $msg = array();
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg);
        logMessage("", "  msgToCmd: ".$msgJson);

        global $queueLQIToCmd;
        if (msg_send($queueLQIToCmd, priorityInterrogation, $msgJson, false, false) == false) {
            logMessage('error', "msgToCmd: Unable to send message to AbeilleCmd");
            return false;
        }
        return true;
    }

    /* Send message to client side
       'type' => 'step' or ?
    */
    function msgToCli($type, $value, $value2 = '') {
        if ($type == "step")
            $msgToCli = array('type' => $type, 'name' => $value, 'status' => $value2);
        else
            $msgToCli = array('type' => 'ERROR');
        global $messages;
        $messages[] = $msgToCli;
        // echo json_encode($msgToCli);
    }

    function saveEqConfig($eqLogic, $key, $value) {
        $eqLogic->getConfiguration($key, $value);
        $eqLogic->save();
        // TODO: Need to inform cmd & parser of change
    }

    function repairDevice($eqId, $eqLogic) {
        logMessage('debug', 'repairDevice('.$eqId.')');

        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        // IEEE defined ?
        $ieee = $eqLogic->getConfiguration('IEEE', '');
        if ($ieee == '') {
            msgToCli("step", "IEEE");
            logMessage('debug', '  Requesting IEEE');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getIeeeAddress");
            return;
        } else
            msgToCli("step", "IEEE", "ok");

        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        logMessage('debug', '  ab::zigbee='.json_encode($zigbee, JSON_UNESCAPED_SLASHES));
        $saveEqZigbee = false;

        // Zigbee endpoints list defined ?
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) { // Checking current EP list
            if (($epId2 == '') || ($epId2 == '00')) {
                logMessage('debug', "  Removing zigbee['endPoints'][${epId2}]");
                unset($zigbee['endPoints'][$epId2]); // Removing unexpected content
                $saveEqZigbee = true;
            }
        }
        if ($saveEqZigbee)
            saveEqConfig($eqLogic, 'ab::zigbee', $zigbee);
        if (!isset($zigbee['endPoints'])) {
            msgToCli("step", "Active end points");
            logMessage('debug', '  Requesting active endpoints list');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getActiveEndpoints");
            return;
        } else
            msgToCli("step", "Active end points", "ok");

        // Zigbee manufCode defined ?
        if (!isset($zigbee['manufCode'])) {
            msgToCli("step", "Manuf code");
            logMessage('debug', '  Requesting node descriptor');
            msgToCmd(PRIO_HIGH, "Cmd${net}/${addr}/getNodeDescriptor");
            return;
        } else
            msgToCli("step", "Manuf code", "ok");

        // Checking Zigbee endpoints
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (!isset($ep2['servClusters'])) {
                msgToCli("step", "EP${epId2} server clusters list");
                logMessage('debug', '  Requesting simple descriptor for EP '.$epId2);
                msgToCmd(PRIO_HIGH, "Cmd${net}/${addr}/getSimpleDescriptor", "ep=".$epId2);
                return; // To reduce requests on first missing descriptor
            }
            msgToCli("step", "EP${epId2} server clusters list", "ok");

            if (strpos($ep2['servClusters'], '0000') !== false) {
                // Cluster 0000 is supported
                $missing = '';
                $missingTxt = '';
                if (!isset($ep2['manufId'])) {
                    $missing = '0004';
                    $missingTxt = 'manufId';
                }
                if (!isset($ep2['modelId'])) {
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '0005';
                    $missingTxt .= 'modelId';
                }
                // Profalux specific: Need 'location' to identify device
                // Note: IEEE address is Profalux (20918A) or sometimes Ember corp (00:0D:6F)
                if (isset($ep2['modelId']) && ($ep2['modelId'] == false) && !isset($ep2['location'])) { // Location is useful for Profalux 1st gen
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '0010';
                    $missingTxt .= 'location';
                }
                if (!isset($ep2['dateCode']) || ($ep2['dateCode'] === null)) { // DateCode undefined
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '0006';
                    $missingTxt .= 'DateCode';
                }
                if (!isset($ep2['swBuildId']) || ($ep2['swBuildId'] === null)) { // SWBuildID undefined
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '4000';
                    $missingTxt .= 'SWBuildID';
                }
                if ($missing != '') {
                    msgToCli("step", "EP${epId2} server cluster 0000 infos");
                    logMessage('debug', '  Requesting '.$missingTxt.' from EP '.$epId2);
                    msgToCmd(PRIO_HIGH, "Cmd${net}/${addr}/readAttribute", "ep=${epId2}&clustId=0000&attrId=".$missing);

                    // WARNING: At least a device (TS0001, _TZ3000_tqlv4ug4) is not responding if multiple attributes at the same time (ex: 0004,0005,0006,4000)
                    //          No solution so far. This will prevent to have the repair phase completed.
                    // $missing = "0004,0005,0006";
                    // $missing = "0004,0005";
                    // msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/readAttribute2", "ep=".$epId2."&clustId=0000&attrId=".$missing);
                    return; // Reducing requests on first missing stuff
                }
            }
            msgToCli("step", "EP${epId2} server cluster 0000 infos", "ok");

            if (strpos($ep2['servClusters'], '0004') !== false) {
                // Cluster 0004 is supported
                if (isset($ep2['groups']))
                    logMessage('debug', '  Groups='.json_encode($ep2['groups']));
                if (!isset($zigbee['groups']) || !isset($zigbee['groups'][$epId2])) {
                    msgToCli("step", "EP${epId2} server cluster 0004 infos");
                    logMessage('debug', '  Requesting groups membership for EP '.$epId2);
                    msgToCmd(PRIO_HIGH, "Cmd${net}/${addr}/getGroupMembership", "ep=".$epId2);
                    return; // To reduce requests on first missing groups membership
                }
            }
            msgToCli("step", "EP${epId2} server cluster 0004 infos", "ok");
        }

        $error = false;
        $saveEqZigbee = false;
        $saveEq = false;

        // Zigbee main signature correct ?
        // Should reflect the signature of the first EP supporting cluster 0000
        if (!$error) {
            foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
                if (strpos($ep2['servClusters'], '0000') === false)
                    continue; // No basic cluster for this EP

                // Reminder: modelId & manufId could be empty, specifically for Profalux 1st gen
                if (!isset($ep2['manufId']) || !isset($ep2['modelId'])) {
                    logMessage('debug', "  ERROR: Missing modelId or manufId for EP ${epId2}");
                    // return;
                    $error = true;
                    break;
                }

                // Main modelId or manufId are now either known or unsupported
                if (!isset($zigbee['manufId']) && ($ep2['manufId'] != '')) {
                    $zigbee['manufId'] = $ep2['manufId'];
                    logMessage('debug', "  zigbee['manufId'] updated to ".$zigbee['manufId']);
                    $saveEqZigbee = true;
                }
                if (!isset($zigbee['modelId']) && ($ep2['modelId'] != '')) {
                    $zigbee['modelId'] = $ep2['modelId'];
                    logMessage('debug', "  zigbee['modelId'] updated to ".$zigbee['modelId']);
                    $saveEqZigbee = true;
                }

                // Profalux specific case: using 'location' to get identifier
                if ((!isset($zigbee['modelId']) || ($zigbee['modelId'] == ""))
                    && isset($ep2['location']) && ($ep2['location'] != '')) {
                    logMessage('debug', "  Profalux case: Using 'location' info '".$ep2['location']."'");
                    $zigbee['modelId'] = $ep2['location'];
                    $zigbee['manufId'] = '';
                    $saveEqZigbee = true;
                }
            }
        }

        if (!$error) {
            if (!isset($zigbee['modelId']) || ($zigbee['modelId'] == "")) {
                msgToCli("step", "Zigbee signature");
                logMessage('debug', "  Device ERROR: Invalid main Zigbee signature: ".json_encode($zigbee, JSON_UNESCAPED_SLASHES));
                $error = true;
                // return;
            } else
                msgToCli("step", "Zigbee signature", "ok");
        }

        $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
        logMessage('debug', '  ab::eqModel='.json_encode($eqModel, JSON_UNESCAPED_SLASHES));
        $saveEqModel = false;

        // Is model identification correct ?
        // ab::eqModel = array(
        //     'modelSig' => model signature
        //     'modelSource' => OPTIONAL: model file source (default=Abeille)
        //     'modelName' => model file name WITHOUT '.json'
        //     'modelPath' => OPTIONAL: required if variant (modelX/modelX-variantY.json)
        //     'modelForced' => OPTIONAL: true | false (default)
        //     'manuf' => EQ manufacturer name
        //     'model' => EQ model nam
        //     'type' => EQ model type
        // )
        if (!$error) {
            if (!isset($eqModel['modelName']) || ($eqModel['modelName'] == '') || ($eqModel['modelName'] == 'defaultUnknown')) {
                msgToCli("step", "Model file name");
                $modelContent = identifyModel($zigbee['modelId'], $zigbee['manufId']);
                if ($modelContent !== false) {
                    $eqModel['modelSource'] = $modelContent['modelSource'];
                    $eqModel['modelName'] = $modelContent['modelName'];
                    if (isset($modelContent['modelPath']))
                        $eqModel['modelPath'] = $modelContent['modelPath'];
                    if (isset($eqModel['modelForced']))
                        unset($eqModel['modelForced']); // In case it was previously forced but unknown

                    $eqModel['modelSig'] = $modelContent['modelSig'];
                    $saveEqModel = true;
                } else {
                    // If returns false but modelName=='defaultUnknown', stay as it is
                    // TODO: Should switch to 'defaultUnknown' if not recognized
                }
            }
            if (isset($eqModel['modelName']) && ($eqModel['modelName'] != ''))
                msgToCli("step", "Model file name", "ok");
            else
                $error = true;
        }

        if (!$error) {
            if (!isset($eqModel['modelSig']) || ($eqModel['modelSig'] == '')) {
                msgToCli("step", "Model signature");
                logMessage('debug', "  Missing model sig.");
                $modelContent = identifyModel($zigbee['modelId'], $zigbee['manufId']);
                if ($modelContent !== false) {
                    $eqModel['modelSig'] = $modelContent['modelSig'];
                    $saveEqModel = true;
                } else {
                    logMessage('debug', "  OOPS: Unexpected case ! 'modelSig' undefined and model not identified.");
                }
            } else {
                // Checks that signature is the expected one
                $modelContent = identifyModel($zigbee['modelId'], $zigbee['manufId']);
                if ($modelContent !== false) {
                    if ($eqModel['modelSig'] != $modelContent['modelSig']) {
                        logMessage('debug', "  'modelSig' updated from '".$eqModel['modelSig']."' to '".$modelContent['modelSig']."'");
                        $eqModel['modelSig'] = $modelContent['modelSig'];
                        $saveEqModel = true;
                    }
                } else {
                    logMessage('debug', "  OOPS: Unexpected case ! 'modelSig' can't be checked since model not identified.");
                }
            }
            if (isset($eqModel['modelSig']) && ($eqModel['modelSig'] != ''))
                msgToCli("step", "Model signature", "ok");
            else
                $error = true;
        }

        // Compute optional 'modelPath'
        if (isset($eqModel['modelPath']))
            $modelPath = $eqModel['modelPath'];
        else
            $modelPath = $eqModel['modelName']."/".$eqModel['modelName'].".json";

        // Equipment infos
        if (!$error) {
            if ((!isset($eqModel['manuf']) || ($eqModel['manuf'] == '') ||
                !isset($eqModel['model']) || ($eqModel['model'] == '') ||
                !isset($eqModel['type']) || ($eqModel['type'] == ''))) {
                msgToCli("step", "Equipment infos");
                $model = getDeviceModel($eqModel['modelSource'], $modelPath, $eqModel['modelName'], $eqModel['modelSig']);
                logMessage('debug', "model=".json_encode($model));
                $eqModel['manuf'] = isset($model['manufacturer']) ? $model['manufacturer']: '';
                $eqModel['model'] = isset($model['model']) ? $model['model']: '';
                $eqModel['type'] = isset($model['type']) ? $model['type']: '';
                $saveEqModel = true;
            }
            if (($eqModel['manuf'] != '') && ($eqModel['model'] != '') && ($eqModel['type'] != ''))
                msgToCli("step", "Equipment infos", "ok");
        }

        // Checking icon
        if (!$error) {
            $icon = $eqLogic->getConfiguration('ab::icon', '');
            if (($icon == '') || ($icon == "defaultUnknown")) {
                logMessage('debug', "Invalid icon '${icon}'");
                msgToCli("step", "Icon");
                if (!isset($model))
                    $model = getDeviceModel($eqModel['modelSource'], $modelPath, $eqModel['modelName'], $eqModel['modelSig']);
                if (isset($model['configuration']['icon'])) {
                    $eqLogic->setConfiguration('ab::icon', $model['configuration']['icon']);
                    logMessage('debug', "  ab::icon updated to '".$model['configuration']['icon']."'");
                    $saveEq = true;
                }
            }
            $icon = $eqLogic->getConfiguration('ab::icon', '');
            if (($icon != '') && ($icon != "defaultUnknown"))
                msgToCli("step", "Icon", "ok");
        }

        // Updating eqLogic if required
        if ($saveEqZigbee) {
            $eqLogic->setConfiguration('ab::zigbee', $zigbee);
            logMessage('debug', "  ab::zigbee updated to ".json_encode($zigbee, JSON_UNESCAPED_SLASHES));
            $saveEq = true;
        }
        if ($saveEqModel) {
            $eqLogic->setConfiguration('ab::eqModel', $eqModel);
            logMessage('debug', "  ab::eqModel updated to ".json_encode($eqModel, JSON_UNESCAPED_SLASHES));
            $saveEq = true;
        }
        if ($saveEq)
            $eqLogic->save();

        if ($error)
            logMessage('debug', '  Device status error');
        else
            logMessage('debug', '  Device OK');
    }

    /*--------------------------------------------------------------------------------------------------*/
    /* Main
    /*--------------------------------------------------------------------------------------------------*/

    logSetConf(jeedom::getTmpFolder("Abeille")."/AbeilleRepair.log", true);
    logMessage("", ">>> AbeilleRepair starting");

    if (!isset($_POST['eqId'])) {
        $msgToCli = array('type' => 'error', 'errMsg' => 'Missing equipment ID');
        echo json_encode($msgToCli);
        logMessage("", "<<< AbeilleRepair exiting on error.");
        exit(1);
    }

    $eqId = $_POST['eqId'];
    $eqLogic = Abeille::byId($eqId);
    if (!is_object($eqLogic)) {
        msgToCli("error", "Unknown device ID ${eqId}");
        exit(2);
    }

    $queueLQIToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);

    $messages = [];
    repairDevice($eqId, $eqLogic);
    echo json_encode($messages);

    logMessage("", "<<< AbeilleRepair exiting.");
?>
