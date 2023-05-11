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

    function repairDevice($eqId, $eqLogic) {
        logMessage('debug', 'repairDevice('.$eqId.')');

        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        // IEEE defined ?
        $ieee = $eqLogic->getConfiguration('IEEE', '');
        if ($ieee == '') {
            logMessage('debug', '  Requesting IEEE');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getIeeeAddress");
            return;
        }

        // Zigbee endpoints list defined ?
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        logMessage('debug', '  ab::zigbee='.json_encode($zigbee));
        if (!isset($zigbee['endPoints'])) {
            logMessage('debug', '  Requesting active endpoints list');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getActiveEndpoints");
            return;
        }

        // Zigbee manufCode defined ?
        if (!isset($zigbee['manufCode'])) {
            logMessage('debug', '  Requesting node descriptor');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getNodeDescriptor");
            return;
        }

        // Checking Zigbee endpoints
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (!isset($ep2['servClusters'])) {
                logMessage('debug', '  Requesting simple descriptor for EP '.$epId2);
                msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getSimpleDescriptor", "ep=".$epId2);
                return; // To reduce requests on first missing descriptor
            }

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
                if ($missing != '') {
                    logMessage('debug', '  Requesting '.$missingTxt.' from EP '.$epId2);
                    msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/readAttribute", "ep=".$epId2."&clustId=0000&attrId=".$missing);
                    return; // Reducing requests on first missing stuff
                }
            }

            if (strpos($ep2['servClusters'], '0004') !== false) {
                if (isset($ep2['groups']))
                    logMessage('debug', '  Groups='.json_encode($ep2['groups']));
                if (!isset($zigbee['groups']) || !isset($zigbee['groups'][$epId2])) {
                    logMessage('debug', '  Requesting groups membership for EP '.$epId2);
                    msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getGroupMembership", "ep=".$epId2);
                    return; // To reduce requests on first missing groups membership
                }
            }
        }

        // Zigbee main signature correct ?
        // Should reflect the signature of the first EP supporting cluster 0000
        $sig = $eqLogic->getConfiguration('ab::signature', []);
        logMessage('debug', '  ab::signature='.json_encode($sig));
        $newSig = [];
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (strpos($ep2['servClusters'], '0000') === false)
                continue; // No basic cluster for this EP

            if (!isset($ep2['manufId']) || !isset($ep2['modelId'])) {
                logMessage('debug', '  Missing model or manuf for EP '.$epId2);
                return;
            }

            // model or manuf are now either known or unsupported
            if (!isset($newSig['manufId']) && ($ep2['manufId'] != ''))
                $newSig['manufId'] = $ep2['manufId'];
            if (!isset($newSig['modelId']) && ($ep2['modelId'] != ''))
                $newSig['modelId'] = $ep2['modelId'];

            // Profalux specific case: using 'location' to get identifier
            if (!isset($newSig['modelId']) && isset($ep2['location']) && ($ep2['location'] != '')) {
                logMessage('debug', "  Profalux case: Using 'location'=".$ep2['location']);
                $newSig['modelId'] = $ep2['location'];
                $newSig['manufId'] = '';
            }
        }
        if ($newSig != $sig) {
            $eqLogic->setConfiguration('ab::signature', $newSig);
            $eqLogic->save();
            logMessage('debug', "  ab::signature updated to ".json_encode($newSig));
            $sig = $newSig;
        }
        if (!isset($sig['modelId'])) {
            logMessage('debug', "  Device ERROR: Invalid main Zigbee signature: ".json_encode($sig));
            return;
        }

        // Is model correct ?
        // ab::eqModel = array(
        //     'id' =>
        //     'location' =>
        //     'type' =>
        // )
        $model = $eqLogic->getConfiguration('ab::eqModel', []);
        logMessage('debug', '  ab::eqModel='.json_encode($model));
        if (!isset($model['id']) || ($model['id'] == 'defaultUnknown')) {
            logMessage('debug', "  Model is 'defaultUnknown' or undefined.");
            $m = AbeilleTools::findModel($sig['modelId'], $sig['manufId']);
            if ($m !== false) {
                $model['id'] = $m['jsonId'];
                $model['location'] = $m['location'];
                $model['type'] = $m['type'];
                $eqLogic->setConfiguration('ab::eqModel', $model);
                $eqLogic->save();
                logMessage('debug', "  ab::eqModel updated to ".json_encode($model));
            } else if (!isset($model['id'])) {
                $model['id'] = 'defaultUnknown';
                $model['location'] = 'Abeille';
                $model['type'] = 'Unknown device';
                $eqLogic->setConfiguration('ab::eqModel', $model);
                $eqLogic->save();
                logMessage('debug', "  ab::eqModel updated to ".json_encode($model));
            }
        }

        logMessage('debug', '  Device OK');
    }

    /*--------------------------------------------------------------------------------------------------*/
    /* Main
    /*--------------------------------------------------------------------------------------------------*/

    logSetConf(jeedom::getTmpFolder("Abeille")."/AbeilleRepair.log", true);
    logMessage("", ">>> AbeilleRepair starting");

    if (isset($_GET['eqId'])) // Equipment ID passed as URL ?
        $eqId = $_GET['eqId'];
    else
        exit(0); // Unsupported case so far

    $queueLQIToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);
    if (isset($eqId)) {
        $eqLogic = Abeille::byId($eqId);
        repairDevice($eqId, $eqLogic);
    }

    logMessage("", "<<< AbeilleRepair exiting.");
?>
