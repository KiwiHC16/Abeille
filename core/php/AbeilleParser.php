<?php

    /*
     * AbeilleParser
     *
     * - Pop data from zigates (msg_receive)
     * - translate them into a understandable message
     * - then publish them (msg_send)
     */

    include_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers options */
    if (file_exists(dbgFile)) {
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["dbgParserLog"])) {
            /* Convert array to associative one */
            $arr = $dbgConfig["dbgParserLog"];
            $dbgParserLog = [];
            foreach ($arr as $idx => $value) {
                $dbgParserLog[$value] = 1;
            }
        }
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../class/AbeilleTools.class.php';
    include_once __DIR__.'/AbeilleLog.php'; // Abeille log features
    include_once __DIR__.'/AbeilleZigateConst.php'; // Zigate constants
    include_once __DIR__.'/AbeilleZigbeeConst.php'; // Zigbee constants
    include_once __DIR__.'/../class/AbeilleCmd.class.php';
    include_once __DIR__.'/../class/AbeilleParser.class.php';
    include_once __DIR__.'/../class/Abeille.class.php';
    include_once __DIR__.'/AbeilleOTA.php';
    include_once __DIR__.'/AbeilleParser-Tuya.php'; // Tuya specific functions
    include_once __DIR__.'/AbeilleParser-Xiaomi.php'; // Xiaomi specific functions

    // Needed for decode8701 and decode8702
    // Voir https://github.com/fairecasoimeme/ZiGate/issues/161
    // APS Code Table Chap 10.2.2 from JN-UG-3113
    // Cette table ne semble pas etre la bonne car je recois un d4 comme status
    // AbeilleParser 2019-04-26 11:58:06[debug];type; 8702; (APS Data Confirm Fail); Status : d4 (->); Source Endpoint : 01; Destination Endpoint : 0B; Destination Mode : 02; Destination Address : bbf5; SQN: : 00
    // As per JN-UG-3113 v1.5
    // Event We can find all of status / error code here : https://www.nxp.com/docs/en/user-guide/JN-UG-3113.pdf page 437 from https://github.com/fairecasoimeme/ZiGate/issues/47

    $event = array(
                   "00" => array( "ZPS_EVENT_NONE","" ),
                   "01" => array( "ZPS_EVENT_APS_DATA_INDICATION","" ),
                   "02" => array( "ZPS_EVENT_APS_DATA_CONFIRM","" ),
                   "03" => array( "ZPS_EVENT_APS_DATA_ACK","" ),
                   "04" => array( "ZPS_EVENT_NWK_STARTED","" ),
                   "05" => array( "ZPS_EVENT_NWK_JOINED_AS_ROUTER","" ),
                   "06" => array( "ZPS_EVENT_NWK_JOINED_AS_ENDDEVICE","" ),
                   "07" => array( "ZPS_EVENT_NWK_FAILED_TO_START","" ),
                   "08" => array( "ZPS_EVENT_NWK_FAILED_TO_JOIN","" ),
                   "09" => array( "ZPS_EVENT_NWK_NEW_NODE_HAS_JOINED","" ),
                   "0A" => array( "ZPS_EVENT_NWK_DISCOVERY_COMPLETE","" ),
                   "0B" => array( "ZPS_EVENT_NWK_LEAVE_INDICATION","" ),
                   "0C" => array( "ZPS_EVENT_NWK_LEAVE_CONFIRM","" ),
                   "0D" => array( "ZPS_EVENT_NWK_STATUS_INDICATION","" ),
                   "0E" => array( "ZPS_EVENT_NWK_ROUTE_DISCOVERY_CONFIRM","" ),
                   "0F" => array( "ZPS_EVENT_NWK_POLL_CONFIRM","" ),
                   "10" => array( "ZPS_EVENT_NWK_ED_SCAN","" ),
                   "11" => array( "ZPS_EVENT_ZDO_BIND","" ),
                   "12" => array( "ZPS_EVENT_ZDO_UNBIND","" ),
                   "13" => array( "ZPS_EVENT_ZDO_LINK_KEY","" ),
                   "14" => array( "ZPS_EVENT_BIND_REQUEST_SERVER","" ),
                   "15" => array( "ZPS_EVENT_ERROR","" ),
                   "16" => array( "ZPS_EVENT_APS_INTERPAN_DATA_INDICATION","" ),
                   "17" => array( "ZPS_EVENT_APS_INTERPAN_DATA_CONFIRM","" ),
                   "18" => array( "ZPS_EVENT_APS_ZGP_DATA_INDICATION","" ),
                   "19" => array( "ZPS_EVENT_APS_ZGP_DATA_CONFIRM","" ),
                   "1A" => array( "ZPS_EVENT_TC_STATUS","" ),
                   "1B" => array( "ZPS_EVENT_NWK_DUTYCYCLE_INDICATION","" ),
                   "1C" => array( "ZPS_EVENT_NWK_FAILED_TO_SELECT_AUX_CHANNEL","" ),
                   "1D" => array( "ZPS_EVENT_NWK_ROUTE_RECORD_INDICATION","" ),
                   "1E" => array( "ZPS_EVENT_NWK_FC_OVERFLOW_INDICATION","" ),
                   "1F" => array( "ZPS_ZCP_EVENT_FAILURE","" ),
                   );

    $zdpCode = array(
                     "80" => array( "ZPS_APL_ZDP_E_INV_REQUESTTYPE", "The supplied request type was invalid.", ),
                     "81" => array( "ZPS_APL_ZDP_E_DEVICE_NOT_FOUND", "The requested device did  not exist on a device following a child descriptor request to a parent.", ),
                     "82" => array( "ZPS_APL_ZDP_E_INVALID_EP", "The supplied endpoint was equal to 0x00 or between 0xF1 and 0xFF.", ),
                     "83" => array( "ZPS_APL_ZDP_E_NOT_ACTIVE", "The requested endpoint is not described by a Simple descriptor.", ),
                     "84" => array( "ZPS_APL_ZDP_E_NOT_SUPPORTED", "The requested optional feature is not supported on the target device.", ),
                     "85" => array( "ZPS_APL_ZDP_E_TIMEOUT", "A timeout has occurred with the requested operation.", ),
                     "86" => array( "ZPS_APL_ZDP_E_NO_MATCH", "The End Device bind request was unsuccessful due to a failure to match any suitable clusters.", ),
                     "88" => array( "ZPS_APL_ZDP_E_NO_ENTRY", "The unbind request was unsuccessful due to the Coordinator or source device not having an entry in its binding table to unbind.", ),
                     "89" => array( "ZPS_APL_ZDP_E_NO_DESCRIPTOR", "A child descriptor was not available following a discov ery request to a parent.", ),
                     "8A" => array( "ZPS_APL_ZDP_E_INSUFFICIENT_SPACE", "The device does not have storage space to support the requested operation.", ),
                     "8B" => array( "ZPS_APL_ZDP_E_NOT_PERMITTED", "The device is not in the proper state to support the requested operation.", ),
                     "8C" => array( "ZPS_APL_ZDP_E_TABLE_FULL", "The device does not have table space to support the operation.", ),
                     "8D" => array( "ZPS_APL_ZDP_E_NOT_AUTHORIZED", "The permissions configuration table on the target indicates that the request is not authorised from this device.", ),
                     );

    $apsCode = array(
                     "A0" => array( "ZPS_APL_APS_E_ASDU_TOO_LONG", "A transmit request failed since the ASDU is too large and fragmentation is not supported.", ),
                     "A1" => array( "ZPS_APL_APS_E_DEFRAG_DEFERRED", "A received fragmented frame could not be defragmented at the current time.", ),
                     "A2" => array( "ZPS_APL_APS_E_DEFRAG_UNSUPPORTED", "A received fragmented frame could not be defragmented since the device does not support fragmentation.", ),
                     "A3" => array( "ZPS_APL_APS_E_ILLEGAL_REQUEST", "A parameter value was out of range.", ),
                     "A4" => array( "ZPS_APL_APS_E_INVALID_BINDING", "An APSME-UNBIND.request failed due to the requested binding link not existing in the binding table.", ),
                     "A5" => array( "ZPS_APL_APS_E_INVALID_GROUP", "An APSME-REMOVE-GROUP.request has been issued with a group identifier that does not appear in the group table.", ),
                     "A6" => array( "ZPS_APL_APS_E_INVALID_PARAMETER", "A parameter value was invalid or out of range.", ),
                     "A7" => array( "ZPS_APL_APS_E_NO_ACK", "An APSDE-DATA.request requesting acknowledged transmission failed due to no acknowledgement being received.", ),
                     "A8" => array( "ZPS_APL_APS_E_NO_BOUND_DEVICE", "An APSDE-DATA.request with a destination addressing mode set to 0x00 failed due to there being no devices bound to this device.", ),
                     "A9" => array( "ZPS_APL_APS_E_NO_SHORT_ADDRESS", "An APSDE-DATA.request with a destination addressing mode set to 0x03 failed due to no corresponding short address found in the address map table.", ),
                     "AA" => array( "ZPS_APL_APS_E_NOT_SUPPORTED", "An APSDE-DATA.request with a destination addressing mode set to 0x00 failed due to a binding table not being supported on the device.", ),
                     "AB" => array( "ZPS_APL_APS_E_SECURED_LINK_KEY", "An ASDU was received that was secured using a link key.", ),
                     "AC" => array( "ZPS_APL_APS_E_SECURED_NWK_KEY", "An ASDU was received that was secured using a network key.", ),
                     "AD" => array( "ZPS_APL_APS_E_SECURITY_FAIL", "An APSDE-DATA.request requesting security has resulted in an error during the corresponding security processing.", ),
                     "AE" => array( "ZPS_APL_APS_E_TABLE_FULL", "An APSME-BIND.request or APSME.ADDGROUP.request issued when the binding or group tables, respectively, were full.", ),
                     "AF" => array( "ZPS_APL_APS_E_UNSECURED", "An ASDU was received without any security.", ),
                     "B0" => array( "ZPS_APL_APS_E_UNSUPPORTED_ATTRIBUTE ", " An APSME-GET.request or APSMESET. request has been issued with an unknown attribute identifier.", ),
                     );

    $nwkCode = array(
                     "00" => array( "ZPS_NWK_ENUM_SUCCESS", "Success"),
                     "C1" => array( "ZPS_NWK_ENUM_INVALID_PARAMETER", "An invalid or out-of-range parameter has been passed"),
                     "C2" => array( "ZPS_NWK_ENUM_INVALID_REQUEST", "Request cannot be processed"),
                     "C3" => array( "ZPS_NWK_ENUM_NOT_PERMITTED", "NLME-JOIN.request not permitted"),
                     "C4" => array( "ZPS_NWK_ENUM_STARTUP_FAILURE", "NLME-NETWORK-FORMATION.request failed"),
                     "C5" => array( "ZPS_NWK_ENUM_ALREADY_PRESENT", "NLME-DIRECT-JOIN.request failure - device already present"),
                     "C6" => array( "ZPS_NWK_ENUM_SYNC_FAILURE", "NLME-SYNC.request has failed"),
                     "C7" => array( "ZPS_NWK_ENUM_NEIGHBOR_TABLE_FULL", "NLME-DIRECT-JOIN.request failure - no space in Router table"),
                     "C8" => array( "ZPS_NWK_ENUM_UNKNOWN_DEVICE", "NLME-LEAVE.request failure - device not in Neighbour table"),
                     "C9" => array( "ZPS_NWK_ENUM_UNSUPPORTED_ATTRIBUTE", "NLME-GET/SET.request unknown attribute identifier"),
                     "Ca" => array( "ZPS_NWK_ENUM_NO_NETWORKS", "NLME-JOIN.request detected no networks"),
                     "CB" => array( "ZPS_NWK_ENUM_RESERVED_1", "Reserved"),
                     "CC" => array( "ZPS_NWK_ENUM_MAX_FRM_CTR", "Security processing has failed on outgoing frame due to maximum frame counter"),
                     "CD" => array( "ZPS_NWK_ENUM_NO_KEY", "Security processing has failed on outgoing frame due to no key"),
                     "CE" => array( "ZPS_NWK_ENUM_BAD_CCM_OUTPUT", "Security processing has failed on outgoing frame due CCM"),
                     "CF" => array( "ZPS_NWK_ENUM_NO_ROUTING_CAPACITY", "Attempt at route discovery has failed due to lack of table space"),
                     "D0" => array( "ZPS_NWK_ENUM_ROUTE_DISCOVERY_FAILED", "Attempt at route discovery has failed due to any reason except lack of table space"),
                     "D1" => array( "ZPS_NWK_ENUM_ROUTE_ERROR", "NLDE-DATA.request has failed due to routing failure on sending device"),
                     "D2" => array( "ZPS_NWK_ENUM_BT_TABLE_FULL", "Broadcast or broadcast-mode multicast has failed as there is no room in BTT"),
                     "D3" => array( "ZPS_NWK_ENUM_FRAME_NOT_BUFFERED", "Unicast mode multi-cast frame was discarded pending route discovery"),
                     "D4" => array( "ZPS_NWK_ENUM_FRAME_IS_BUFFERED", "Unicast frame does not have a route available but it is buffered for automatic resend. / https://github.com/fairecasoimeme/ZiGate/issues/207"),
                     );

    $macCode = array(
                     "00" => array( "MAC_ENUM_SUCCESS", "Success", ),
                     "E0" => array( "MAC_ENUM_BEACON_LOSS", "Beacon loss after synchronisation request", ),
                     "E1" => array( "MAC_ENUM_CHANNEL_ACCESS_FAILURE", "CSMA/CA channel access failure", ),
                     "E2" => array( "MAC_ENUM_DENIED", "GTS request denied", ),
                     "E3" => array( "MAC_ENUM_DISABLE_TRX_FAILURE", "Could not disable transmit or receive", ),
                     "E4" => array( "MAC_ENUM_FAILED_SECURITY_CHECK", "Incoming frame failed security check", ),
                     "E5" => array( "MAC_ENUM_FRAME_TOO_LONG", "Frame too long, after security processing, to be sent", ),
                     "E6" => array( "MAC_ENUM_INVALID_GTS", "GTS transmission failed", ),
                     "E7" => array( "MAC_ENUM_INVALID_HANDLE", "Purge request failed to find entry in queue", ),
                     "E8" => array( "MAC_ENUM_INVALID_PARAMETER", "Out-of-range parameter in function", ),
                     "E9" => array( "MAC_ENUM_NO_ACK", "No acknowledgement received when expected", ),
                     "EA" => array( "MAC_ENUM_NO_BEACON", "Scan failed to find any beacons", ),
                     "EB" => array( "MAC_ENUM_NO_DATA", "No response data after a data request", ),
                     "EC" => array( "MAC_ENUM_NO_SHORT_ADDRESS", "No allocated network (short) address for operation", ),
                     "ED" => array( "MAC_ENUM_OUT_OF_CAP", "Receiver-enable request could not be executed, as CAP finished", ),
                     "EE" => array( "MAC_ENUM_PAN_ID_CONFLICT", "PAN ID conflict has been detected", ),
                     "EF" => array( "MAC_ENUM_REALIGNMENT", "Co-ordinator realignment has been received", ),
                     "F0" => array( "MAC_ENUM_TRANSACTION_EXPIRED", "Pending transaction has expired and data discarded", ),
                     "F1" => array( "MAC_ENUM_TRANSACTION_OVERFLOW", "No capacity to store transaction", ),
                     "F2" => array( "MAC_ENUM_TX_ACTIVE", "Receiver-enable request could not be executed, as in transmit state", ),
                     "F3" => array( "MAC_ENUM_UNAVAILABLE_KEY", "Appropriate key is not available in ACL", ),
                     "F4" => array( "MAC_ENUM_UNSUPPORTED_ATTRIBUTE", "PIB Set/Get on unsupported attribute", ),
                     );

    $allErrorCode = $event + $zdpCode + $apsCode + $nwkCode + $macCode;
    $toMon = []; // Monitoring messages from device to monitor only (filled by parserLog2())

    /* Parser log function.
       If 'dev mode' & 'debug' level: message can be filtered according to "debug.json" */
    function parserLog($level, $msg, $type = '') {
        if (($type != '') && ($level == "debug")) {
            global $dbgParserLog;
            if (isset($dbgParserLog) && isset($dbgParserLog[$type]))
                return; // Log disabled for this type
        }
        logMessage($level, $msg);
    }

    /* Parser log & monitoring function.
       If 'dev mode' & 'debug' level: message can be filtered according to "debug.json"
       Note: Regarding monitoring messages they have to be flushed at some point.
     */
    function parserLog2($level, $addr, $msg, $type = '') {
        if (($type != '') && ($level == "debug")) {
            global $dbgParserLog;
            if (isset($dbgParserLog) && isset($dbgParserLog[$type]))
                return; // Log disabled for this type
        }
        logMessage($level, $msg);

        if (isset($GLOBALS["dbgMonitorAddr"]) && !strcasecmp($GLOBALS["dbgMonitorAddr"], $addr))
            $GLOBALS['toMon'][] = $msg;
    }

    /* New function to send msg to Abeille.
        Msg format is now flexible and can transport a bunch of infos coming from zigbee event instead of splitting them
        into several messages to Abeille. */
    function msgToAbeille2($msg) {
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
        global $queueXToAbeille;
        // Note: '@' to suppress PHP warning message.
        if (@msg_send($queueXToAbeille, 1, $msgJson, false, false, $errCode) == false) {
            parserLog("debug", "msgToAbeille2() ERROR ${errCode}/".AbeilleTools::getMsgSendErr($errCode));
        }
    }

    /* Send message to 'AbeilleCmd' thru 'xToCmd' queue */
    function msgToCmd($prio, $topic, $payload = '') {
        $msg = array(
            'priority' => $prio,
            'topic' => $topic,
            'payload' => $payload
        );
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

        global $queueXToCmd;
        // Note: '@' to suppress PHP warning message.
        if (@msg_send($queueXToCmd, 1, $msgJson, false, false, $errCode) == false) {
            parserLog("debug", "  msgToCmd(queueXToCmd) ERROR ${errCode}/".AbeilleTools::getMsgSendErr($errCode));
        }
    }

    function msgToCmdAck($msg) {
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
        global $queueParserToCmdAck;
        // Note: '@' to suppress PHP warning message.
        if (@msg_send($queueParserToCmdAck, 1, $msgJson, false, false, $errCode) == false) {
            parserLog("debug", "  msgToCmd(queueParserToCmdAck) ERROR ${errCode}/".AbeilleTools::getMsgSendErr($errCode));
        }
    }

    // Inform Jeedom to create a new device
    function addNewJeedomDevice($net, $addr, $ieee) {
        $msg = array(
            'type' => 'newDevice',
            'net' => $net,
            'addr' => $addr,
            'ieee' => $ieee,
        );
        msgToAbeille2($msg);
    }

    // Create a new device in internal devices list => $GLOBALS['devices'][$net][$addr]
    function newDevice($net, $addr, $ieee = null) {
        // This is a new device
        $zigbee = array(
            'ieee' => $ieee,
            'macCapa' => '',
            'rxOnWhenIdle' => null,
            'endPoints' => null,
        );
        $eqModel = array(
            'modelName' => '', // JSON file name without extension
            'modelSource' => '', // ''/'Abeille' or 'local'
            'modelForced' => false
        );
        $GLOBALS['devices'][$net][$addr] = array(
            // 'ieee' => $ieee,
            // 'macCapa' => '',
            // 'rxOnWhenIdle' => null,
            'zigbee' => $zigbee,

            'rejoin' => '', // Rejoin info from device announce
            'status' => 'identifying', // identifying, configuring, discovering, idle
            'time' => time(),
            'mainEp' => '',
            'manufId' => null, // Zigbee manufacturer: null(undef)/false(unsupported)/'xx'
            'modelId' => null, // Zigmee model: null(undef)/false(unsupported)/'xx'
            'location' => null, // Zigbee location: null(undef)/false(unsupported)/'xx'
            // 'modelName' => '', // Model file name WITHOUT '.json'
            // 'modelSource' => '', // Model source ('Abeille' or 'local')
            // 'modelForced' => false, // Model forced by user if 'true'
            'eqModel' => $eqModel,
            // Optional 'private'
            // Optional 'notStandard-0400-0000'
        );

        // Informing Abeille to create a new (but empty) device.
        if ($ieee) {
            parserLog('debug', '  '.$addr.'/'.$ieee.' is a new device.');
            addNewJeedomDevice($net, $addr, $ieee);
        } else {
            // Still not enough infos to create device on Jeedom side
            parserLog('debug', '  '.$addr.' is a new device but missing IEEE. Requesting ...');
            msgToCmd(PRIO_HIGH, "Cmd".$net."/".$addr."/getIeeeAddress");
        }
    } // End newDevice()

    /* Check if device is already known to parser.
        If not, add entry with given net/addr/ieee.
        Returns: device entry by reference */
    function &getDevice($net, $addr, $ieee = null, &$new = false) {
        if (!isset($GLOBALS['devices'][$net]))
            $GLOBALS['devices'][$net] = [];

        if (isset($GLOBALS['devices'][$net][$addr])) {
            if (($GLOBALS['devices'][$net][$addr]['zigbee']['ieee'] === null) && ($ieee !== null)) {
                $GLOBALS['devices'][$net][$addr]['zigbee']['ieee'] = $ieee;
                addNewJeedomDevice($net, $addr, $ieee);
            }
            return $GLOBALS['devices'][$net][$addr];
        }

        // Not found. If IEEE is given let's check if short addr has changed or if equipment has migrated from another network.
        if ($ieee) {
            foreach ($GLOBALS['devices'][$net] as $oldAddr => $eq) {
                if (!isset($eq['zigbee']['ieee']) || ($eq['zigbee']['ieee'] !== $ieee))
                    continue;

                $GLOBALS['devices'][$net][$addr] = $eq;
                unset($GLOBALS['devices'][$net][$oldAddr]);
                parserLog('debug', "  EQ already known: Addr updated from ${oldAddr} to ${addr}");

                // Removing any cmd pending message for old address since device would no longer answer
                $msg = array(
                    'type' => 'shortAddrChange',
                    'oldNet' => $net,
                    'newNet' => $net,
                    'oldAddr' => $oldAddr,
                    'newAddr' => $addr
                );
                msgToCmdAck($msg);

                // Informing Abeille about short addr change
                $msg = array(
                    'type' => 'deviceUpdates',
                    'net' => $net,
                    'addr' => $addr,
                    'updates' => array(
                        'ieee' => $ieee
                    ),
                );
                msgToAbeille2($msg);

                return $GLOBALS['devices'][$net][$addr];
            }

            // Still not found. Checking if was in a different network but need IEEE for that.
            foreach ($GLOBALS['devices'] as $oldNet => $oldAddr) {
                if ($oldNet == $net)
                    continue; // This network has already been checked

                foreach ($GLOBALS['devices'][$oldNet] as $oldAddr => $eq) {
                    if ($eq['zigbee']['ieee'] !== $ieee)
                        continue;

                    $GLOBALS['devices'][$net][$addr] = $eq; // net & addr update
                    unset($GLOBALS['devices'][$oldNet][$oldAddr]);
                    parserLog('debug', '  EQ already known on network '.$oldNet.' with addr '.$oldAddr.' => migrated');

                    // Removing any cmd pending message for old net/address since device would no longer answer
                    $msg = array(
                        'type' => 'shortAddrChange',
                        'oldNet' => $oldNet,
                        'newNet' => $net,
                        'oldAddr' => $oldAddr,
                        'newAddr' => $addr
                    );
                    msgToCmdAck($msg);

                    // Informing Abeille to migrate Jeedom part to proper network.
                    $msg = array(
                        'type' => 'eqMigrated',
                        'net' => $net,
                        'addr' => $addr,
                        'srcNet' => $oldNet,
                        'srcAddr' => $oldAddr,
                    );
                    msgToAbeille2($msg);

                    return $GLOBALS['devices'][$net][$addr];
                }
            }
        }

        // This is a new device
        newDevice($net, $addr, $ieee);
        $new = true; // This is a new device
        return $GLOBALS['devices'][$net][$addr];
    } // End getDevice()

    // Reread Jeedom useful infos on eqLogic DB update
    // Note: A delay is required prior to this if DB has to be updated (createDevice() in Abeille.class)
    function updateDeviceFromDB($eqId) {
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            parserLog('error', "updateDeviceFromDB(${eqId}): Equipement inconnu");
            return;
        }
        $eqLogicId = $eqLogic->getLogicalId();
        list($net, $addr) = explode("/", $eqLogicId);

        if (!isset($GLOBALS['devices'][$net]))
            $GLOBALS['devices'][$net] = [];
        if (!isset($GLOBALS['devices'][$net][$addr]))
            $GLOBALS['devices'][$net][$addr] = [];
        $eq = &$GLOBALS['devices'][$net][$addr];

        $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
        // $eq['modelName'] = isset($eqModel['modelName']) ? $eqModel['modelName'] : '';
        // $eq['modelSource'] = isset($eqModel['modelSource']) ? $eqModel['modelSource'] : 'Abeille';
        // $eq['modelForced'] = isset($eqModel['modelForced']) ? $eqModel['modelForced'] : false;
        // if (isset($eqModel['modelPath'])) // Forced model variant case
        //     $eq['modelPath'] = $eqModel['modelPath'];
        if (!isset($eqModel['modelSource']))
            $eqModel['modelSource'] = ''; // Default
        if (!isset($eqModel['modelPath']))
            $eqModel['modelPath'] = ''; // Default
        if (isset($eqModel['private'])) {
            $eq['private'] = $eqModel['private'];
            parserLog('debug', "  'private' updated to ".json_encode($eq['private'], JSON_UNESCAPED_SLASHES));
        } else if (isset($GLOBALS['devices'][$net][$addr]['private']))
            unset($GLOBALS['devices'][$net][$addr]['private']);

        // $fromDevice = $eqLogic->getConfiguration('ab::fromDevice', null); // OBSOLETE soon. Replaced by 'private'
        // if ($fromDevice !== null) { // OBSOLETE soon. Replaced by 'private'
        //     $eq['fromDevice'] = $fromDevice;
        //     parserLog('debug', "  'fromDevice' updated to ".json_encode($eq['fromDevice']));
        // } else if (isset($eq['fromDevice']))
        //     unset($GLOBALS['devices'][$net][$addr]['fromDevice']);
        // $eq['tuyaEF00'] = $eqLogic->getConfiguration('ab::tuyaEF00', null); // OBSOLETE soon. Replaced by 'private'
        // parserLog('debug', "  'tuyaEF00' updated to ".json_encode($eq['tuyaEF00'])); // OBSOLETE soon. Replaced by 'private'
        // $eq['xiaomi'] = $eqLogic->getConfiguration('ab::xiaomi', null); // OBSOLETE soon. Replaced by 'private'
        // parserLog('debug', "  'xiaomi' updated to ".json_encode($eq['xiaomi'])); // OBSOLETE soon. Replaced by 'private'

        $eq['customization'] = $eqLogic->getConfiguration('ab::customization', null);
        parserLog('debug', "  'customization' updated to ".json_encode($eq['customization']));

        // TO BE COMPLETED with any other useful info for parser
    }

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleParser.php /dev/ttyUSB0 127.0.0.1 1883 jeedom jeedom 0 debug
    logSetConf("AbeilleParser.log", true);
    logMessage("info", ">>> Démarrage d'AbeilleParser");

    // Check if already running
    $config = AbeilleTools::getConfig();
    $running = AbeilleTools::getRunningDaemons();
    $daemons= AbeilleTools::diffExpectedRunningDaemons($config, $running);
    logMessage('debug', 'Daemons: '.json_encode($daemons));
    if ($daemons["parser"] > 1) {
        logMessage('error', "Le démon 'AbeilleParser' est déja lancé !");
        exit(3);
    }

    declare(ticks = 1);
    pcntl_signal(SIGTERM, 'signalHandler', false);
    function signalHandler($signal) {
        logMessage('info', '<<< Arret du démon AbeilleParser');
        exit;
    }

    // Inits
    // $queueParserToAbeille2 = msg_get_queue($abQueues["parserToAbeille2"]["id"]);
    $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
    $queueXToCmd = msg_get_queue($abQueues["xToCmd"]["id"]);
    $queueParserToCmdAck = msg_get_queue($abQueues["parserToCmdAck"]["id"]);

    /* Any device to monitor ?
       It is indicated by 'ab::monitorId' key in Jeedom 'config' table. */
    $GLOBALS['toMon'] = [];
    // $monId = config::byKey('ab::monitorId', 'Abeille', false);
    $monId = $config['ab::monitorId'];
    if ($monId !== false) {
        $eqLogic = eqLogic::byId($monId);
        if (!is_object($eqLogic)) {
            logMessage('debug', 'Bad ID for device to monitor: '.$monId);
        } else {
            list($net, $addr) = explode( "/", $eqLogic->getLogicalId());
            $ieee = $eqLogic->getConfiguration('IEEE', '');
            $dbgMonitorAddr = $addr;
            $dbgMonitorAddrExt = $ieee;
            logMessage("debug", "Device to monitor: ".$eqLogic->getHumanName().', '.$addr.'-'.$ieee);
            include_once __DIR__.'/AbeilleMonitor.php'; // Tracing monitor for debug purposes
        }
    }

    // In case parser only is restarted, let's get zigates IEEE if known */
    for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
        if ($config['ab::gtwEnabled'.$gtwId] != 'Y')
            continue; // Disabled
        if ($config['ab::gtwType'.$gtwId] != 'zigate')
            continue; // Not a Zigate network

        // $ieeeOk = config::byKey('ab::zgIeeeAddrOk'.$zgId, 'Abeille', 0);
        $ieeeOk = $config['ab::zgIeeeAddrOk'.$gtwId];
        if ($ieeeOk == 1) { // IEEE addr already verified & valid
            // $extAddr = config::byKey('ab::zgIeeeAddr'.$zgId, 'Abeille', "1212121212121212");
            $extAddr = $config['ab::zgIeeeAddr'.$gtwId];
            $GLOBALS['zigate'.$gtwId]['ieee'] = $extAddr;
            logMessage("debug", "Zigate ".$gtwId." already verified IEEE: ".$extAddr);
        }
        $GLOBALS['zigate'.$gtwId]['ieeeStatus'] = $ieeeOk;
    }

    // Reading available OTA firmwares
    otaReadFirmwares();

    // Work-around for https://github.com/fairecasoimeme/ZiGatev2/issues/36#
    $last8002DevAnnounce = 'xxxx'; //

    try {
        $AbeilleParser = new AbeilleParser("AbeilleParser");

        $queueXToParser = msg_get_queue($abQueues["xToParser"]["id"]);
        $queueXToParserMax = $abQueues["xToParser"]["max"];

        /* Init list of supported & user/custom devices */
        // $GLOBALS['supportedEqList'] = AbeilleTools::getDevicesList("Abeille");
        $GLOBALS['supportedEqList'] = getModelsList("Abeille");
        // $GLOBALS['customEqList'] = AbeilleTools::getDevicesList("local");
        $GLOBALS['customEqList'] = getModelsList("local");
        logMessage('debug', 'customEqList='.json_encode( $GLOBALS['customEqList']));

        /* Init known devices list */
        $eqLogics = eqLogic::byType('Abeille');
        $GLOBALS['devices'] = [];
        foreach ($eqLogics as $eqLogic) {
            $eqLogicId = $eqLogic->getLogicalId();
            list($net, $addr) = explode("/", $eqLogicId);
            $gtwId = substr($net, 7); // 'AbeilleX' => 'X'
            if ($gtwId == '')
                continue; // Incorrect case

            if ($config['ab::gtwType'.$gtwId] != 'zigate')
                continue; // Not a Zigate network

            // $sig = $eqLogic->getConfiguration('ab::signature', '');
            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            $jsonId = isset($eqModel['modelName']) ? $eqModel['modelName'] : '';
            if ($jsonId != '')
                $status = 'idle';
            else
                $status = 'identifying';
            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            $zigbee['ieee'] = $eqLogic->getConfiguration('IEEE', null);
            $eq = array(
                // 'ieee' => $eqLogic->getConfiguration('IEEE', null),
                // 'macCapa' => isset($zigbee['macCapa']) ? $zigbee['macCapa'] : '',
                // 'rxOnWhenIdle' => isset($zigbee['rxOnWhenIdle']) ? $zigbee['rxOnWhenIdle'] : null,
                // 'endPoints' => isset($zigbee['endPoints']) ? $zigbee['endPoints'] : null, // null(undef)
                'zigbee' => $zigbee,
                'manufCode' => isset($zigbee['manufCode']) ? $zigbee['manufCode'] : null,
                'rejoin' => '', // Rejoin info from device announce
                'status' => $status, // identifying, configuring, discovering, idle
                'time' => time(),
                'mainEp' => '',
                // Cluster 0000 infos
                'manufId' => null, // Zigbee manufacturer: null(undef)/false(unsupported)/'xx'
                'modelId' => null, // Zigmee model: null(undef)/false(unsupported)/'xx'
                'location' => null, // Zigbee location: null(undef)/false(unsupported)/'xx'
                'dateCode' => null,
                'swBuildId' => null,
                // Abeille's model infos
                // 'modelName' => $jsonId,
                // 'modelSource' => '',
                // 'modelForced' => isset($eqModel['modelForced']) ? $eqModel['modelForced'] : false,
                'eqModel' => $eqModel,
                'customization' => $eqLogic->getConfiguration('ab::customization', null),
                //'private' => // Optional: Set if 'private' section exists in model
                // Optional 'notStandard-0400-0000'
            );
            if (isset($eqModel['private']))
                $eq['private'] = $eqModel['private'];
            // else if (isset($eqModel['fromDevice'])) // OBSOLETE soon => replaced by 'private'
            //     $eq['fromDevice'] = $eqModel['fromDevice']; // OBSOLETE soon => replaced by 'private'

            // Checking for '0400-0000' not standard attribute
            $cmds = Cmd::byEqLogicId($eqLogic->getId(), 'info');
            foreach ($cmds as $cmdLogic) {
                $notStandard = $cmdLogic->getConfiguration('ab::notStandard', 0);
                if ($notStandard != 0) {
                    if (!isset($eq['notStandard']))
                        $eq['notStandard'] = [];
                    $cmdLogicId = $cmdLogic->getLogicalId();
                    $eq['notStandard'][$cmdLogicId] = 1; // Only 'notStandard' supported case
                    logMessage('debug', "'".$eqLogicId."' has non standard ".$cmdLogicId." attribute");
                }
            }
            // if (($jsonId != '') && ($jsonId != 'rucheCommand')) {
            //     $devModel = AbeilleTools::getDeviceModel($jsonId);
            //     if (isset($devModel['commands'])) {
            //         // parserLog('debug', 'ZOB='.json_encode($devModel['commands']));
            //         foreach ($devModel['commands'] as $c) {
            //             if ($c['type'] != 'info')
            //                 continue;
            //             // parserLog('debug', $eqLogicId.': c='.json_encode($c));
            //             if (!isset($c['configuration']) || !isset($c['configuration']['notStandard']))
            //                 continue;
            //             $eq['notStandard-0400-0000'] = 1; // Only 'notStandard' supported case
            //             logMessage('debug', "'".$eqLogicId."' has non standard 0400-0000 attribute");
            //         }
            //     }
            // }

            $GLOBALS['devices'][$net][$addr] = $eq;
        }

        logMessage('debug', 'Reading messages queues');
        while (true) {

            // Wait (block) for any input messages
            // - Those coming from AbeilleSerialRead
            // - Other control infos
            while (msg_receive($queueXToParser, 0, $msgType, $queueXToParserMax, $msgJson, false, 0, $errCode)) { // Blocking read
                $msg = json_decode($msgJson, true);
                if ($msg === null) {
                    logMessage('debug', 'ERROR: json_decode(): msgJson='.$msgJson);
                    time_nanosleep(0, 10000000); // 1/100s
                    continue;
                }

                if ($msg['type'] == "rcvChanStatus") {
                    logMessage('debug', $msg['net'].' RX channel status '.$msg['status']);
                    // Message from AbeilleSerialReadX
                    // Sending msg to cmd indicating receive channel status
                    msgToCmdAck($msg);
                } else if ($msg['type'] == "serialRead") {
                    // Message from AbeilleSerialReadX
                    $AbeilleParser->protocolDatas($msg['net'], $msg['msg']);
                } else if ($msg['type'] == "logLevelChanged") {
                    logLevelChanged($msg['level']);
                } else {
                    // Ctrl message
                    if ($msg['type'] == 'sendToCli') {
                        $GLOBALS['sendToCli']['net'] = $msg['net'];
                        $GLOBALS['sendToCli']['addr'] = $msg['addr'];
                        $GLOBALS['sendToCli']['ieee'] = $msg['ieee'];
                    } else if ($msg['type'] == 'readOtaFirmwares') {
                        otaReadFirmwares(); // Reread available firmwares
                    } else if ($msg['type'] == 'eqRemoved') {
                        logMessage('debug', 'Some equipments removed from Jeedom');
                        // Some equipments removed from Jeedom => phantoms if still in network
                        // $msg['net'] = Abeille network (AbeilleX)
                        // $msg['devices'] = Eq addr separated by ','
                        $net = $msg['net'];
                        $arr = explode(',', $msg['devices']);
                        foreach ($arr as $idx => $addr) {
                            if (!isset($GLOBALS['devices'][$net])) {
                                logMessage('debug', "  ERROR: Unknown network ".$net);
                                continue;
                            }
                            if (!isset($GLOBALS['devices'][$net][$addr])) {
                                logMessage('debug', "  ERROR: Unknown device ".$net."/".$addr);
                                continue;
                            }
                            $ieee = $GLOBALS['devices'][$net][$addr]['ieee'];
                            unset($GLOBALS['devices'][$net][$addr]);
                            logMessage('debug', "  Device ${net}/${addr} (ieee=${ieee}) removed from Jeedom");
                        }
                    } else if ($msg['type'] == 'eqUpdated') {
                        // Note: On model reload/reset, changes may impact parsing (ex: Tuya).
                        $eqId = $msg['id'];
                        logMessage('debug', 'EQ id '.$eqId.' updated. Need to read Jeedom DB.');
                        updateDeviceFromDB($eqId);
                    } else
                        logMessage('error', 'ERROR: Unexpected msg: '.$msgJson);
                }
            }
            if ($errCode == 7) { // Too big
                logMessage('error', '  msg_receive(queueXToParser) ERROR: msg TOO BIG ignored.');
                logMessage('debug', "  msg=${msgJson}");
                msg_receive($queueXToParser, 0, $msgType, $queueXToParserMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR); // Purge
            } else if ($errCode != 42) { // 42 = No message
                logMessage('debug', '  msg_receive(queueXToParser) ERROR '.$errCode);
                time_nanosleep(0, 10000000); // 1/100s
            }

            // Check if we have any action scheduled and waiting to be processed
            // $AbeilleParser->processActionQueue();

            // Check if we have any command waiting for the device to wake up
            // $AbeilleParser->processWakeUpQueue();
        }

        unset($AbeilleParser);
    }
    catch (Exception $e) {
        // Tcharp38: Can we reach this ?
        logMessage('debug', 'error: '.json_encode($e->getMessage()));
    }

    logMessage('info', '<<< AbeilleParser: arret du démon'); // Tcharp38: Can we reach this ?
?>
