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
    include_once "AbeilleLog.php"; // Log library

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

        // Zigbee endpoints list defined ?
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        logMessage('debug', '  ab::zigbee='.json_encode($zigbee));
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
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getNodeDescriptor");
            return;
        } else
            msgToCli("step", "Manuf code", "ok");

        // Checking Zigbee endpoints
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (!isset($ep2['servClusters'])) {
                msgToCli("step", "EP${epId2} server clusters list");
                logMessage('debug', '  Requesting simple descriptor for EP '.$epId2);
                msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getSimpleDescriptor", "ep=".$epId2);
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
                if (isset($ep2['modelId']) && ($ep2['modelId'] == '') && !isset($ep2['location'])) { // Location is useful for Profalux 1st gen
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '0010';
                    $missingTxt .= 'location';
                }
                if (!isset($ep2['dateCode'])) { // DateCode
                    if ($missing != '') {
                        $missing .= ',';
                        $missingTxt .= '/';
                    }
                    $missing .= '0006';
                    $missingTxt .= 'DateCode';
                }
                if (!isset($ep2['swBuildId'])) { // SWBuildID
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
                    msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/readAttribute", "ep=".$epId2."&clustId=0000&attrId=".$missing);
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
                    msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getGroupMembership", "ep=".$epId2);
                    return; // To reduce requests on first missing groups membership
                }
            }
            msgToCli("step", "EP${epId2} server cluster 0004 infos", "ok");
        }

        // Zigbee main signature correct ?
        // Should reflect the signature of the first EP supporting cluster 0000
        $zbSig = $eqLogic->getConfiguration('ab::signature', []);
        logMessage('debug', '  ab::signature='.json_encode($zbSig));
        $zbNewSig = [];
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (strpos($ep2['servClusters'], '0000') === false)
                continue; // No basic cluster for this EP

            if (!isset($ep2['manufId']) || !isset($ep2['modelId'])) {
                logMessage('debug', '  ERROR: Missing model or manuf for EP '.$epId2);
                return;
            }

            // model or manuf are now either known or unsupported
            if (!isset($zbNewSig['manufId']) && ($ep2['manufId'] != ''))
                $zbNewSig['manufId'] = $ep2['manufId'];
            if (!isset($zbNewSig['modelId']) && ($ep2['modelId'] != ''))
                $zbNewSig['modelId'] = $ep2['modelId'];

            // Profalux specific case: using 'location' to get identifier
            if (!isset($zbNewSig['modelId']) && isset($ep2['location']) && ($ep2['location'] != '')) {
                logMessage('debug', "  Profalux case: Using 'location' info '".$ep2['location']."'");
                $zbNewSig['modelId'] = $ep2['location'];
                $zbNewSig['manufId'] = '';
            }
        }
        if ($zbNewSig != $zbSig) {
            $eqLogic->setConfiguration('ab::signature', $zbNewSig);
            $eqLogic->save();
            logMessage('debug', "  ab::signature updated to ".json_encode($zbNewSig));
            $zbSig = $zbNewSig;
        }
        if (!isset($zbSig['modelId'])) {
            msgToCli("step", "Zigbee signature");
            logMessage('debug', "  Device ERROR: Invalid main Zigbee signature: ".json_encode($zbSig));
            return;
        }
        msgToCli("step", "Zigbee signature", "ok");

        // Is model correct ?
        // ab::eqModel = array(
        //     'sig' => model signature
        //     'id' => model file name
        //     'location' => model file source
        //     'type' => model type
        //     'forcedByUser' => true|false
        //     'manuf' => EQ manufacturer name
        //     'model' => EQ model nam
        //     'type' => EQ model type
        // )
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
        logMessage('debug', '  ab::eqModel='.json_encode($eqModel));
        $eqModelChanged = false;
        $modelContent = [];
        if (!isset($eqModel['modelName']) || ($eqModel['modelName'] == '')) {
            msgToCli("step", "Model file name");
            $modelContent = AbeilleTools::findModel($zbSig['modelId'], $zbSig['manufId']);
            if ($modelContent !== false) {
                $eqModel['modelSig'] = $modelContent['modelSig'];
                $eqModel['modelName'] = $modelContent['jsonId'];
                $eqModel['modelSource'] = $modelContent['location'];
                $eqModel['forcedByUser'] = false;
                $eqModel['manuf'] = $modelContent['manuf'];
                $eqModel['model'] = $modelContent['model'];
                $eqModel['type'] = $modelContent['type'];
                $eqModelChanged = true;
            }
        }
        if (isset($eqModel['modelName']) && ($eqModel['modelName'] != ''))
            msgToCli("step", "Model file name", "ok");
        else
            return;
        if (!isset($eqModel['modelSig']) || ($eqModel['modelSig'] == '')) {
            msgToCli("step", "Model signature");
            logMessage('debug', "  Missing model sig.");
            $modelContent = AbeilleTools::findModel($zbSig['modelId'], $zbSig['manufId']);
            if ($modelContent !== false) {
                $eqModel['modelSig'] = $modelContent['modelSig'];
                $eqModelChanged = true;
            }
        }
        if (isset($eqModel['modelSig']) && ($eqModel['modelSig'] != ''))
            msgToCli("step", "Model signature", "ok");
        else
            return;

        // Equipment infos
        if (!isset($eqModel['manuf']) || ($eqModel['manuf'] == '') ||
            !isset($eqModel['model']) || ($eqModel['model'] == '') ||
            !isset($eqModel['type']) || ($eqModel['type'] == '')) {
            msgToCli("step", "Equipment infos");
            $model = AbeilleTools::getDeviceModel($eqModel['modelSig'], $eqModel['modelName'], $eqModel['modelSource']);
            logMessage('debug', "model=".json_encode($model));
            $eqModel['manuf'] = isset($model['manufacturer']) ? $model['manufacturer']: '';
            $eqModel['model'] = isset($model['model']) ? $model['model']: '';
            $eqModel['type'] = isset($model['type']) ? $model['type']: '';
            $eqModelChanged = true;
        }
        if (($eqModel['manuf'] != '') && ($eqModel['model'] != '') && ($eqModel['type'] != ''))
            msgToCli("step", "Equipment infos", "ok");

        if ($eqModelChanged) {
            $eqLogic->setConfiguration('ab::eqModel', $eqModel);
            $eqLogic->save();
            logMessage('debug', "  ab::eqModel updated to ".json_encode($eqModel));
        }

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
