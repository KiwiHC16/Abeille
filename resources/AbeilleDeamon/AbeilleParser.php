<?php

    /*
     * AbeilleParser
     *
     * - Pop data from FIFO file
     * - translate them into a understandable message,
     * - then publish them to mosquitto
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        $dbgConfig = json_decode(file_get_contents($dbgFile), TRUE);
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

    // Annonce -> populate NE-> get EP -> getName -> getLocation -> unset NE

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/includes/config.php';
    include_once __DIR__.'/includes/function.php';
    include_once __DIR__.'/includes/fifo.php';
    include_once __DIR__.'/lib/AbeilleTools.php';
    include_once __DIR__.'/../../core/php/AbeilleLog.php'; // Abeille log features
    include_once __DIR__.'/../../core/php/AbeilleZigateConst.php'; // Zigate constants
    include_once __DIR__.'/../../core/class/AbeilleParser.class.php'; // AbeilleParserClass

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



    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleParser.php /dev/ttyUSB0 127.0.0.1 1883 jeedom jeedom 0 debug
    //check already running
    logSetConf("AbeilleParser.log");
    logMessage("info", "Démarrage d'AbeilleParser");
    $parameters = AbeilleTools::getParameters();
    $running = AbeilleTools::getRunningDaemons();
    $daemons= AbeilleTools::diffExpectedRunningDaemons($parameters,$running);
    logMessage('debug', 'Daemons status: '.json_encode($daemons));
    #Two at least expected,the original and this one
    if ($daemons["parser"] > 1){
        logMessage('error', 'Le daemon est déja lancé! '.json_encode($daemons));
        exit(3);
    }

    try {
        // On crée l objet AbeilleParser
        $AbeilleParser = new AbeilleParser("AbeilleParser");

        $NE = array(); // Ne doit exister que le temps de la creation de l objet. On collecte les info du message annonce et on envoie les info a jeedom et apres on vide la tableau.
        $LQI = array();
        $clusterTab = AbeilleTools::getJSonConfigFiles("zigateClusters.json");

        $queueKeySerieToParser   = msg_get_queue(queueKeySerieToParser);
        $max_msg_size = 2048;
        $msg_type = NULL;

        while (true) {

            if (msg_receive( $queueKeySerieToParser, 0, $msg_type, $max_msg_size, $dataJson, false, MSG_IPC_NOWAIT)) {
                // $AbeilleParser->deamonlog( 'debug', 'Message pulled from queue queueKeySerieToParser: '.$dataJson );
                $data = json_decode( $dataJson );
                $AbeilleParser->protocolDatas( $data->dest, $data->trame, $clusterTab, $LQI);
            }
            // $AbeilleParser->processAnnonce($NE);
            // $AbeilleParser->cleanUpNE($NE);
            $AbeilleParser->processActionQueue();

            time_nanosleep( 0, 10000000 ); // 1/100s
        }

        unset($AbeilleParser);
    }
    catch (Exception $e) {
        logMessage('debug', 'error: '.json_encode($e->getMessage()));
    }

    logMessage('info', 'AbeilleParser: arret du démon');
?>
