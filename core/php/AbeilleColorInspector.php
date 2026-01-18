<?php

    /*
     * Analysis of how the device supports the color cluster
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
    }

    function msgToCli2($type, $value, $value2 = '') {
        $messages = [];
        if ($type == "step")
            $messages[] = array('type' => $type, 'name' => $value, 'status' => $value2);
        else
            $messages[] = array('type' => 'ERROR');
        echo json_encode($messages);
    }

    function saveEqConfig($eqLogic, $key, $value) {
        $eqLogic->getConfiguration($key, $value);
        $eqLogic->save();
        // TODO: Need to inform cmd & parser of change
    }

    function inspectDevice($eqId, $eqLogic) {
        logMessage('debug', 'inspectDevice('.$eqId.')');

        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        // TODO: How to react if no zigbee ? Repair ?
        if ($zigbee == []) {
            msgToCli("step", "No 'zigbee' info => Repair required.");
            return;
        }

        // Color cluster supported ?
        $colorSupportedEpId = false; // Color cluster supported on EP, or false
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (!isset($ep2['servClusters'])) {
                msgToCli("step", "No 'zigbee/$epId2/servClusters' info => Repair required.");
                return;
            }
            // servClusters => Supported server clusters separated by '/': ex '0000/0003/FFFF/0006'
            if (strpos($ep2['servClusters'], '0300') === false) {
                logMessage('debug', "  Color cluster NOT supported on EP $epId2");
                continue;
            }
            $colorSupportedEpId = $epId2;
        }
        if ($colorSupportedEpId === false) {
            msgToCli("step", "No color cluster support for this device.");
            return;
        }

        // Next steps ??
        // Supported server attributes ? => Where to store them if this process is recalled multiple times ?

        // Try moveToColor (X/Y)
        // Auto report ? or read back ? => 0300-EP-0007
        // Same question after configure reporting done
        // Same question after bind done

        // Try set color temp (moveToColorTemp)
        // Auto report ? or read back ? => 0300-EP-0007
        // Same question after configure reporting done
    }

    /*--------------------------------------------------------------------------------------------------*/
    /* Main
    /*--------------------------------------------------------------------------------------------------*/

    logSetConf(jeedom::getTmpFolder("Abeille")."/AbeilleColorInspector.log", true);
    logMessage("", ">>> AbeilleColorInspector starting");

    // if (!isset($_POST['eqId'])) {
    //     $msgToCli = array('type' => 'error', 'errMsg' => 'Missing equipment ID');
    //     echo json_encode($msgToCli);
    //     logMessage("", "<<< AbeilleColorInspector exiting on error.");
    //     exit(1);
    // }

    // $eqId = $_POST['eqId'];
    // $eqLogic = Abeille::byId($eqId);
    // if (!is_object($eqLogic)) {
    //     msgToCli("error", "Unknown device ID ${eqId}");
    //     exit(2);
    // }

    // $queueLQIToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);

    // $messages = [];
    // inspectDevice($eqId, $eqLogic);
    // echo json_encode($messages);

    if (!isset($_POST['eqId'])) {
        $msgToCli = array('type' => 'error', 'errMsg' => 'Missing equipment ID');
        echo json_encode($msgToCli);
        logMessage("", "<<< AbeilleColorInspector exiting on error.");
        exit(1);
    }

    $eqId = $_POST['eqId'];
    $eqLogic = Abeille::byId($eqId);
    if (!is_object($eqLogic)) {
        msgToCli("error", "Unknown device ID ${eqId}");
        exit(2);
    }

    $queueLQIToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);

    $eqLogicId = $eqLogic->getLogicalId();
    list($net, $addr) = explode("/", $eqLogicId);
    $exitCI = false;
    while (!$exitCI) {
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        if ($zigbee == []) { // TODO: How to react if no zigbee ? Repair ?
            msgToCli2("step", "No 'zigbee' info => Repair required.");
            break;
        }

        // Color cluster supported ?
        $colorSupportedEpId = false; // Color cluster supported on EP, or false
        foreach ($zigbee['endPoints'] as $epId2 => $ep2) {
            if (!isset($ep2['servClusters'])) {
                msgToCli2("step", "No 'zigbee/$epId2/servClusters' info => Repair required.");
                $exitCI = true;
                break;
            }
            // servClusters => Supported server clusters separated by '/': ex '0000/0003/FFFF/0006'
            if (strpos($ep2['servClusters'], '0300') === false) {
                logMessage('debug', "  Color cluster NOT supported on EP $epId2");
                continue;
            }
            $colorSupportedEpId = $epId2;
        }
        if ($colorSupportedEpId === false) {
            msgToCli2("step", "No color cluster support for this device.");
            $exitCI = true;
            break;
        }

        // Next steps ??
        // Supported server attributes ? => Where to store them if this process is recalled multiple times ?

        // Try moveToColor (X/Y)
        // Auto report ? or read back ? => 0300-EP-0007
        // Same question after configure reporting done
        // Same question after bind done

        // Try set color temp (moveToColorTemp)
        // Auto report ? or read back ? => 0300-EP-0007
        // Same question after configure reporting done
    }

    logMessage("", "<<< AbeilleColorInspector exiting.");
?>
