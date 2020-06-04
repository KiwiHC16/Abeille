<?php

    /*
     * AbeilleParser
     *
     * - Pop data from FIFO file
     * - translate them into a understandable message,
     * - then publish them to mosquitto
     */

    // Annonce -> populate NE-> get EP -> getName -> getLocation -> unset NE

    include_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/function.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/fifo.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';

    /* Errors reporting: uncomment below lines for debug */
    // error_reporting(E_ALL);
    // ini_set('error_log', '/var/www/html/log/AbeillePHP');
    // ini_set('log_errors', 'On');

    $profileTable = array (
                           'c05e'=>'ZLL Application Profile',
                           '0104'=>'ZigBee Home Automation (ZHA)',
                           );

    $deviceInfo = array (
                         'c05e' => array(
                                         // Lighting devices
                                         '0000'=>'On/Off light',
                                         '0010'=>'On/Off plug-in unit',
                                         '0100'=>'Dimmable light',
                                         '010A'=>'Proprio Prise Ikea',   // Pas dans le standard mais remonté par prise Ikea
                                         '0110'=>'Dimmable plug-in unit',
                                         '0200'=>'Color light',
                                         '0210'=>'Extended color light',
                                         '0220'=>'Color temperature light',
                                         // Conroller devices
                                         '0800'=>'Color controller',
                                         '0810'=>'Color scene controller',
                                         '0820'=>'Non-color controller',
                                         '0830'=>'Non-color scene controller',
                                         '0840'=>'Control bridge',
                                         '0850'=>'On/Off sensor',
                                         ),
                         '0104' => array(
                                         '0000'=>'On/Off Switch',
                                         '0001'=>'Level Control Switch',
                                         '0002'=>'On/Off Output',
                                         '0003'=>'Level Controllable Output',
                                         '0004'=>'Scene Selector',
                                         '0005'=>'Configuration Tool',
                                         '0006'=>'Remote Control',
                                         '0007'=>'Combined Interface',
                                         '0008'=>'Range Extender',
                                         '0009'=>'Mains Power Outlet',
                                         '000A'=>'Door Lock',
                                         '000B'=>'Door Lock Controller',
                                         '000C'=>'Simple Sensor',
                                         '000D'=>'Consumption Awareness Device',

                                         '0050'=>'Home Gateway',
                                         '0051'=>'Smart Plug',
                                         '0052'=>'White Goods',
                                         '0053'=>'Meter Interface',

                                         // Lighting
                                         '0100'=>'On/Off Light',
                                         '0101'=>'Dimmable Light',
                                         '0102'=>'Color Dimmable Light',
                                         '0103'=>'On/Off Light Switch',
                                         '0104'=>'Dimmer Switch',
                                         '0105'=>'Color Dimmer Switch',
                                         '0106'=>'Light Sensor',
                                         '0107'=>'Occupency Sensor',

                                         // Closures
                                         '0200'=>'Shade',
                                         '0201'=>'Shade Controller',
                                         '0202'=>'Window Covering Device',
                                         '0203'=>'Window Covering Controller',

                                         // HVAC
                                         '0300'=>'Heating/Cooling Unit',
                                         '0301'=>'Thermostat',
                                         '0302'=>'Temperature Sensor',
                                         '0303'=>'Pump',
                                         '0304'=>'Pump Controller',
                                         '0305'=>'Pressure Sensor',
                                         '0306'=>'Flow Sensor',
                                         '0307'=>'Mini Split AC',

                                         // Intruder Alarm Systems
                                         '0400'=>'IAS Control and Indicating Equipment',
                                         '0401'=>'IAs Ancillary Equipment',
                                         '0402'=>'IAS Zone',
                                         '0403'=>'IAS Warning Device',

                                         // From OSRAM investigation
                                         '0810'=>'OSRAM Switch',

                                         // From Legrand investigation
                                         '0B04'=>'Electrical Measurement', // Attribut Id: 0x50B - Active Power

                                         ),
                         );

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
                     "81" => array( "Z P S _ A P L _ Z D P _ E _ D E V I C E _ N O T _ F O U N D", "T h e   r e q u e s t e d   d e v i c e   d i d  not exist on a device following a child descriptor request to a parent.", ),
                     "82" => array( "ZPS_APL_ZDP_E_INVALID_EP", "The supplied endpoint was equal to 0x00 or between 0xF1 and 0xFF.", ),
                     "83" => array( "ZPS_APL_ZDP_E_NOT_ACTIVE", "The requested endpoint is not described by a Simple descriptor.", ),
                     "84" => array( "ZPS_APL_ZDP_E_NOT_SUPPORTED", "The requested optional feature is not supported on the target device.", ),
                     "85" => array( "ZPS_APL_ZDP_E_TIMEOUT", "A timeout has occurred with the requested operation.", ),
                     "86" => array( "ZPS_APL_ZDP_E_NO_MATCH", "The End Device bind request was unsuccessful due to a failure to match any suitable clusters.", ),
                     "88" => array( "ZPS_APL_ZDP_E_NO_ENTRY", "The unbind request was unsuccessful due to the Coordinator or source device not having an entry in its binding table to unbind.", ),
                     "89" => array( "ZPS_APL_ZDP_E_NO_DESCRIPTOR", "A child descriptor was not available following a discov ery request to a parent.", ),
                     "8a" => array( "ZPS_APL_ZDP_E_INSUFFICIENT_SPACE", "The device does not have storage space to support the requested operation.", ),
                     "8b" => array( "ZPS_APL_ZDP_E_NOT_PERMITTED", "The device is not in the proper state to support the requested operation.", ),
                     "8c" => array( "ZPS_APL_ZDP_E_TABLE_FULL", "The device does not have table space to support the operation.", ),
                     "8d" => array( "ZPS_APL_ZDP_E_NOT_AUTHORIZED", "The permissions configuration table on the target indicates that the request is not authorised from this device.", ),
                     );

    $apsCode = array(
                     "a0" => array( "ZPS_APL_APS_E_ASDU_TOO_LONG", "A transmit request failed since the ASDU is too large and fragmentation is not supported.", ),
                     "a1" => array( "ZPS_APL_APS_E_DEFRAG_DEFERRED", "A received fragmented frame could not be defragmented at the current time.", ),
                     "a2" => array( "ZPS_APL_APS_E_DEFRAG_UNSUPPORTED", "A received fragmented frame could not be defragmented since the device does not support fragmentation.", ),
                     "a3" => array( "ZPS_APL_APS_E_ILLEGAL_REQUEST", "A parameter value was out of range.", ),
                     "a4" => array( "ZPS_APL_APS_E_INVALID_BINDING", "An APSME-UNBIND.request failed due to the requested binding link not existing in the binding table.", ),
                     "a5" => array( "ZPS_APL_APS_E_INVALID_GROUP", "An APSME-REMOVE-GROUP.request has been issued with a group identifier that does not appear in the group table.", ),
                     "a6" => array( "ZPS_APL_APS_E_INVALID_PARAMETER", "A parameter value was invalid or out of range.", ),
                     "a7" => array( "ZPS_APL_APS_E_NO_ACK", "An APSDE-DATA.request requesting acknowledged transmission failed due to no acknowledgement being received.", ),
                     "a8" => array( "ZPS_APL_APS_E_NO_BOUND_DEVICE", "An APSDE-DATA.request with a destination addressing mode set to 0x00 failed due to there being no devices bound to this device.", ),
                     "a9" => array( "ZPS_APL_APS_E_NO_SHORT_ADDRESS", "An APSDE-DATA.request with a destination addressing mode set to 0x03 failed due to no corresponding short address found in the address map table.", ),
                     "aa" => array( "ZPS_APL_APS_E_NOT_SUPPORTED", "An APSDE-DATA.request with a destination addressing mode set to 0x00 failed due to a binding table not being supported on the device.", ),
                     "ab" => array( "ZPS_APL_APS_E_SECURED_LINK_KEY", "An ASDU was received that was secured using a link key.", ),
                     "ac" => array( "ZPS_APL_APS_E_SECURED_NWK_KEY", "An ASDU was received that was secured using a network key.", ),
                     "ad" => array( "ZPS_APL_APS_E_SECURITY_FAIL", "An APSDE-DATA.request requesting security has resulted in an error during the corresponding security processing.", ),
                     "ae" => array( "ZPS_APL_APS_E_TABLE_FULL", "An APSME-BIND.request or APSME.ADDGROUP.request issued when the binding or group tables, respectively, were full.", ),
                     "af" => array( "ZPS_APL_APS_E_UNSECURED", "An ASDU was received without any security.", ),
                     "b0" => array( "ZPS_APL_APS_E_UNSUPPORTED_ATTRIBUTE ", " An APSME-GET.request or APSMESET. request has been issued with an unknown attribute identifier.", ),
                     );

    $nwkCode = array(
                     "00" => array( "ZPS_NWK_ENUM_SUCCESS", "Success"),
                     "c1" => array( "ZPS_NWK_ENUM_INVALID_PARAMETER", "An invalid or out-of-range parameter has been passed"),
                     "c2" => array( "ZPS_NWK_ENUM_INVALID_REQUEST", "Request cannot be processed"),
                     "c3" => array( "ZPS_NWK_ENUM_NOT_PERMITTED", "NLME-JOIN.request not permitted"),
                     "c4" => array( "ZPS_NWK_ENUM_STARTUP_FAILURE", "NLME-NETWORK-FORMATION.request failed"),
                     "c5" => array( "ZPS_NWK_ENUM_ALREADY_PRESENT", "NLME-DIRECT-JOIN.request failure - device already present"),
                     "c6" => array( "ZPS_NWK_ENUM_SYNC_FAILURE", "NLME-SYNC.request has failed"),
                     "c7" => array( "ZPS_NWK_ENUM_NEIGHBOR_TABLE_FULL", "NLME-DIRECT-JOIN.request failure - no space in Router table"),
                     "c8" => array( "ZPS_NWK_ENUM_UNKNOWN_DEVICE", "NLME-LEAVE.request failure - device not in Neighbour table"),
                     "c9" => array( "ZPS_NWK_ENUM_UNSUPPORTED_ATTRIBUTE", "NLME-GET/SET.request unknown attribute identifier"),
                     "ca" => array( "ZPS_NWK_ENUM_NO_NETWORKS", "NLME-JOIN.request detected no networks"),
                     "cb" => array( "ZPS_NWK_ENUM_RESERVED_1", "Reserved"),
                     "cc" => array( "ZPS_NWK_ENUM_MAX_FRM_CTR", "Security processing has failed on outgoing frame due to maximum frame counter"),
                     "cd" => array( "ZPS_NWK_ENUM_NO_KEY", "Security processing has failed on outgoing frame due to no key"),
                     "ce" => array( "ZPS_NWK_ENUM_BAD_CCM_OUTPUT", "Security processing has failed on outgoing frame due CCM"),
                     "cf" => array( "ZPS_NWK_ENUM_NO_ROUTING_CAPACITY", "Attempt at route discovery has failed due to lack of table space"),
                     "d0" => array( "ZPS_NWK_ENUM_ROUTE_DISCOVERY_FAILED", "Attempt at route discovery has failed due to any reason except lack of table space"),
                     "d1" => array( "ZPS_NWK_ENUM_ROUTE_ERROR", "NLDE-DATA.request has failed due to routing failure on sending device"),
                     "d2" => array( "ZPS_NWK_ENUM_BT_TABLE_FULL", "Broadcast or broadcast-mode multicast has failed as there is no room in BTT"),
                     "d3" => array( "ZPS_NWK_ENUM_FRAME_NOT_BUFFERED", "Unicast mode multi-cast frame was discarded pending route discovery"),
                     "d4" => array( "ZPS_NWK_ENUM_FRAME_IS_BUFFERED", "Unicast frame does not have a route available but it is buffered for automatic resend. / https://github.com/fairecasoimeme/ZiGate/issues/207"),
                     );

    $macCode = array(
                     "00" => array( "MAC_ENUM_SUCCESS", "Success", ),
                     "e0" => array( "MAC_ENUM_BEACON_LOSS", "Beacon loss after synchronisation request", ),
                     "e1" => array( "MAC_ENUM_CHANNEL_ACCESS_FAILURE", "CSMA/CA channel access failure", ),
                     "e2" => array( "MAC_ENUM_DENIED", "GTS request denied", ),
                     "e3" => array( "MAC_ENUM_DISABLE_TRX_FAILURE", "Could not disable transmit or receive", ),
                     "e4" => array( "MAC_ENUM_FAILED_SECURITY_CHECK", "Incoming frame failed security check", ),
                     "e5" => array( "MAC_ENUM_FRAME_TOO_LONG", "Frame too long, after security processing, to be sent", ),
                     "e6" => array( "MAC_ENUM_INVALID_GTS", "GTS transmission failed", ),
                     "e7" => array( "MAC_ENUM_INVALID_HANDLE", "Purge request failed to find entry in queue", ),
                     "e8" => array( "MAC_ENUM_INVALID_PARAMETER", "Out-of-range parameter in function", ),
                     "e9" => array( "MAC_ENUM_NO_ACK", "No acknowledgement received when expected", ),
                     "ea" => array( "MAC_ENUM_NO_BEACON", "Scan failed to find any beacons", ),
                     "eb" => array( "MAC_ENUM_NO_DATA", "No response data after a data request", ),
                     "ec" => array( "MAC_ENUM_NO_SHORT_ADDRESS", "No allocated network (short) address for operation", ),
                     "ed" => array( "MAC_ENUM_OUT_OF_CAP", "Receiver-enable request could not be executed, as CAP finished", ),
                     "ee" => array( "MAC_ENUM_PAN_ID_CONFLICT", "PAN ID conflict has been detected", ),
                     "ef" => array( "MAC_ENUM_REALIGNMENT", "Co-ordinator realignment has been received", ),
                     "f0" => array( "MAC_ENUM_TRANSACTION_EXPIRED", "Pending transaction has expired and data discarded", ),
                     "f1" => array( "MAC_ENUM_TRANSACTION_OVERFLOW", "No capacity to store transaction", ),
                     "f2" => array( "MAC_ENUM_TX_ACTIVE", "Receiver-enable request could not be executed, as in transmit state", ),
                     "f3" => array( "MAC_ENUM_UNAVAILABLE_KEY", "Appropriate key is not available in ACL", ),
                     "f4" => array( "MAC_ENUM_UNSUPPORTED_ATTRIBUTE", "PIB Set/Get on unsupported attribute", ),
                     );

    /* Type and name of zigate messages (mainly those currently unsupported) */
    $zigateMessages = array(
        "8001" => "Log message",
        "8002" => "Data indication",
        "8006" => "Non “Factory new” Restart",
        "8007" => "“Factory New” Restart",
        "8008" => "\“Function inconnue pas dans la doc\"",
        "8009" => "Network State Response",
        "8028" => "Authenticate response",
        "802B" => "User Descriptor Notify",
        "802C" => "User Descriptor Response",
        "8031" => "Unbind response",
        "8034" => "Complex Descriptor response",
        "8035" => "PDM event code",
        "8042" => "Node Descriptor response",
        "8044" => "Power Descriptor response",
        "8046" => "Match Descriptor response",
        "8047" => "Management Leave response",
        "804B" => "System Server Discovery response",
        "8061" => "View Group response",
        "80A1" => "Add Scene response",
        "80A2" => "Remove Scene response",
        "8110" => "Write Attribute Response",
        "8140" => "Configure Reporting response",
        "8401" => "Zone status change notification",
        "8531" => "Complex Descriptor response",
    );

    /* Returns Zigate message name based on given '$msgType' */
    function getZigateMsgByType($msgType)
    {
        global $zigateMessages;

        if (array_key_exists($msgType, $zigateMessages))
            return $zigateMessages[$msgType];
        return "Message inconnu";
    }

    $allErrorCode = $event + $zdpCode + $apsCode + $nwkCode + $macCode;

    class debug {
        function deamonlog($loglevel = 'NONE', $message = "")
        {
            if ($this->debug["cli"] ) {
                echo "[".date("Y-m-d H:i:s").'][AbeilleParser][DEBUG.BEN] '.$message."\n";
            }
            else {
                /* TODO: How to align loglevel width for better visual aspect ? */
                Tools::deamonlogFilter( $loglevel, 'Abeille', 'AbeilleParser', $message );
            }
        }
    }

    class AbeilleParser extends debug {
        public $queueKeyParserToAbeille = null;
        public $queueKeyParserToCmd = null;

        public $debug = array(
                              "cli"                     => 0, // commande line mode or jeedom
                              "AbeilleParserClass"      => 0,  // Mise en place des class
                              "8000"                    => 1, // Status
                              "8009"                    => 1, // Get Network Status
                              "8010"                    => 1,
                              "processAnnonce"          => 1,
                              "processAnnonceStageChg"  => 1,
                              "cleanUpNE"               => 1,
                              "Serial"                  => 1,
                              "processActionQueue"      => 1,
                              );

        // ZigBee Cluster Library - Document 075123r02ZB - Page 79 - Table 2.15
        // Data Type -> Description, # octets
        public $zbDataTypes = array(
                                 '00' => array( 'Null', 0 ),
                                 // 01-07: reserved
                                 '08' => array( 'General Data', 1),
                                 '09' => array( 'General Data', 2),
                                 '0a' => array( 'General Data', 3),
                                 '0b' => array( 'General Data', 4),
                                 '0c' => array( 'General Data', 5),
                                 '0d' => array( 'General Data', 6),
                                 '0e' => array( 'General Data', 7),
                                 '0f' => array( 'General Data', 8),

                                 '10' => array( 'Bool', 1 ), // Boolean
                                 // 0x11-0x17 Reserved
                                 '18' => array( 'Bitmap', 1 ),
                                 '19' => array( 'Bitmap', 2 ),
                                 '1a' => array( 'Bitmap', 3 ),
                                 '1b' => array( 'Bitmap', 4 ),
                                 '1c' => array( 'Bitmap', 5 ),
                                 '1d' => array( 'Bitmap', 6 ),
                                 '1e' => array( 'Bitmap', 7 ),
                                 '1f' => array( 'Bitmap', 8 ),

                                 '20' => array( 'Uint8',  1 ), // Unsigned 8-bit int
                                 '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int
                                 '22' => array( 'Uint24', 3 ), // Unsigned 24-bit int
                                 '23' => array( 'Uint32', 4 ), // Unsigned 32-bit int
                                 '24' => array( 'Uint40', 5 ), // Unsigned 40-bit int
                                 '25' => array( 'Uint48', 6 ), // Unsigned 48-bit int
                                 '26' => array( 'Uint56', 7 ), // Unsigned 56-bit int
                                 '27' => array( 'Uint64', 8 ), // Unsigned 64-bit int

                                 '28' => array( 'Int8', 1 ), // Signed 8-bit int
                                 '29' => array( 'Int16', 2 ), // Signed 16-bit int
                                 '2a' => array( 'Int24', 3 ), // Signed 24-bit int
                                 '2b' => array( 'Int32', 4 ), // Signed 32-bit int
                                 '2c' => array( 'Int40', 5 ), // Signed 40-bit int
                                 '2d' => array( 'Int48', 6 ), // Signed 48-bit int
                                 '2e' => array( 'Int56', 7 ), // Signed 56-bit int
                                 '2f' => array( 'Int64', 8 ), // Signed 64-bit int

                                 '30' => array( 'Enumeration', 1 ),
                                 '31' => array( 'Enumeration', 2 ),
                                 // 0x32-0x37 Reserved

                                 '38' => array( 'SemiPrecision',   2 ),
                                 '39' => array( 'Single', 4 ), // Single precision
                                 '3a' => array( 'DoublePrecision', 8 ),
                                 // 0x3b-0x3f
                                 // 0x40 Reserved
                                 '41' => array( 'String - Octet string',             'Defined in first octet' ),
                                 '42' => array( 'String - Charactere string',        'Defined in first octet' ),
                                 '43' => array( 'String - Long octet string',        'Defined in first two octets' ),
                                 '44' => array( 'String - Long charactere string',   'Defined in first two octets' ),
                                 // 0x45-0x47 Reserved
                                 '48' => array( 'Ordered sequence - Array',          '2+sum of lengths of contents' ),
                                 // 0x49-0x4b Reserved
                                 '4c' => array( 'Ordered sequence - Structure',      '2+sum of lengths of contents' ),
                                 // 0x4d-0x4f Reserved
                                 '50' => array( 'Collection - Set',      'Sum of lengths of contents' ),
                                 '51' => array( 'Collection - Bag',      'Sum of lengths of contents' ),
                                 //0x52-0x57 Reserved
                                 // 0x58-0xdf Reserved
                                 'e0' => array( 'Time - Time of day', 4 ),
                                 'e1' => array( 'Time - Date', 4 ),
                                 'e2' => array( 'Time - UTCTime', 4 ),
                                 // 0xe3 - 0xe7 Reserved
                                 'e8' => array( 'Identifier - Cluster ID', 2 ),
                                 'e9' => array( 'Identifier - Attribute ID', 2 ),
                                 'ea' => array( 'Identifier - BACnet OID', 4 ),
                                 // 0xeb-0xef Reserved
                                 'f0' => array( 'Miscellaneous - IEEE address', 8 ),
                                 'f1' => array( 'Miscellaneous - 128 bit security key', 16 ),
                                 // 0xF2-0xFe Reserved
                                 'ff' => array( 'Unknown', 0 ),
        );

        /* Returns ZigBee data type or array('?'.$type.'?', 0) if unknown */
        function getZbDataType($type)
        {
            if (array_key_exists($type, $this->zbDataTypes))
                return $this->zbDataTypes[$type];
            return array('?'.$type.'?', 0);
        }

        public $parameters_info;
        public $actionQueue; // queue of action to be done in Parser like config des NE ou get info des NE

        function __construct() {
            global $argv;

            if ($this->debug["AbeilleParserClass"]) $this->deamonlog("debug", "AbeilleParser constructor");
            $this->parameters_info = Abeille::getParameters();

                        // $this->requestedlevel = $argv[7];
            $this->requestedlevel = '' ? 'none' : $argv[1];
            $GLOBALS['requestedlevel'] = $this->requestedlevel ;

            $this->queueKeyParserToAbeille      = msg_get_queue(queueKeyParserToAbeille);
            $this->queueKeyParserToCmd          = msg_get_queue(queueKeyParserToCmd);
            $this->queueKeyParserToCmdSemaphore = msg_get_queue(queueKeyParserToCmdSemaphore);
            $this->queueKeyParserToLQI          = msg_get_queue(queueKeyParserToLQI);
        }

        // $SrcAddr = dest / shortaddr
        function mqqtPublish($SrcAddr, $ClusterId, $AttributId, $data)
        {
            // dest / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = true;

            $msgAbeille->message = array(
                                    'topic' => $SrcAddr."/".$ClusterId."-".$AttributId,
                                    'payload' => $data,
                                     );
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == FALSE) {
                $this->deamonlog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                $this->deamonlog("error", "  Message=".json_encode($msgAbeille));
            }

            $msgAbeille->message = array('topic' => $SrcAddr."/Time-TimeStamp", 'payload' => time());
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == FALSE) {
                $this->deamonlog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                $this->deamonlog("error", "  Message=".json_encode($msgAbeille));
            }

            $msgAbeille->message = array('topic' => $SrcAddr."/Time-Time", 'payload' => date("Y-m-d H:i:s"));
            if (msg_send($this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode) == FALSE) {
                $this->deamonlog("error", "msg_send() ERREUR ".$errorcode.". Impossible d'envoyer le message sur la queue 'queueKeyParserToAbeille'");
                $this->deamonlog("error", "  Message=".json_encode($msgAbeille));
            }
        }

        function mqqtPublishFct( $SrcAddr, $fct, $data)
        {
            // $SrcAddr = dest / shortaddr
            // dest / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => $SrcAddr."/".$fct, 'payload' => $data, );

            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // $this->deamonlog("debug","(fct mqqtPublishFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishFct) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
            }
        }

        function mqqtPublishFctToCmd( $fct, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => $fct, 'payload' => $data, );

            if (msg_send( $this->queueKeyParserToCmd, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // $this->deamonlog("debug","(fct mqqtPublishFctToCmd) added to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishFctToCmd) could not add message to queue (queueKeyParserToCmd) with error code : ".$errorcode);
            }
        }

        function mqqtPublishCmdFct( $fct, $data)
        {
             // Abeille / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => $fct, 'payload' => $data, );

            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // $this->deamonlog("debug","(fct mqqtPublishFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishFct) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
            }
        }

        function mqqtPublishLQI( $Addr, $Index, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => "LQI/".$Addr."/".$Index, 'payload' => $data, $errorcode);

            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // $this->deamonlog("debug","(fct mqqtPublishLQI ParserToAbeille) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishLQI ParserToAbeille) could not add message to queue (queueKeyParserToAbeille) with error code : ".$errorcode);
            }

            if (msg_send( $this->queueKeyParserToLQI, 1, $msgAbeille, true, false)) {
                // $this->deamonlog("debug","(fct mqqtPublishLQI ParserToLQI) added to queue (queueKeyParserToLQI): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishLQI ParserToLQI) could not add message to queue (queueKeyParserToLQI) with error code : ".$errorcode);
            }
        }

        /*
        function mqqtPublishAnnounce( $SrcAddr, $data)
        {
            // Abeille / short addr / Annonce -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => "Cmd".$SrcAddr."/Annonce", 'payload' => $data, );

            if (msg_send( $this->queueKeyParserToCmd, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // $this->deamonlog("debug","(fct mqqtPublishAnnounce) added to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishAnnounce) could not add message to queue (queueKeyParserToCmd) with error code : ".$errorcode);
            }
        }

        function mqqtPublishAnnounceProfalux( $SrcAddr, $data)
        {
            // Abeille / short addr / Annonce -> data

            $msgAbeille = new MsgAbeille;
            $errorcode = 0;
            $blocking = false;

            $msgAbeille->message = array( 'topic' => "Cmd".$SrcAddr."/AnnonceProfalux", 'payload' => $data, );

            if (msg_send( $this->queueKeyParserToCmd, 1, $msgAbeille, true, $blocking, $errorcode)) {
                // $this->deamonlog("debug","(fct mqqtPublishAnnounceProfalux) added to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishAnnounceProfalux) could not add message to queue (queueKeyParserToCmd) with error code : ".$errorcode);
            }
        }
         */

        function procmsg($topic, $payload)
        {
            // AbeilleParser traite les messages venant du port serie mais rien venant de MQTT, pas de besoin.
        }

        function hex2str($hex)   {
            $str = '';
            for ($i = 0; $i < strlen($hex); $i += 2) {
                $str .= chr(hexdec(substr($hex, $i, 2)));
            }

            return $str;
        }

        public function volt2pourcent( $voltage ) {
            if ( $voltage/1000 > 3.135 ) {
                $this->deamonlog( 'error', 'Voltage a plus de 3.135V. Je retourne 100% mais il y a qq chose qui cloche.' );
                return 100;
            }
            if ( $voltage/1000 < 2.8 ) {
                $this->deamonlog( 'error', 'Voltage a moins de 2.8V. Je retourne 0% mais il y a qq chose qui cloche.' );
                return 0;
            }
            return round(100-(((3.135-($voltage/1000))/(3.135-2.8))*100));
        }

        function displayClusterId($cluster) {
            return 'ClusterId='.$cluster.'-'.$GLOBALS['clusterTab']["0x".$cluster] ;
        }

        /* Zigbee type 0x10 to string */
        function convBoolToString($value) {
            if (hexdec($value) == 0)
                return "0";
            return "1"; // Any value != 0 means TRUE
        }

        /* Zigbee type 0x39 to string */
        function convSingleToString($value) {
          return unpack('f', pack('H*', $value ))[1];
        }

        function convUint8ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint16ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint24ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint32ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint40ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint48ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint56ToString($value) {
            return base_convert($value, 16, 10);
        }
        function convUint64ToString($value) {
            return base_convert($value, 16, 10);
        }

        /* Zigbee types 0x28..0x2f to string */
        function convInt8ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7f) // is negative
                $num -= 0x100;
            return sprintf("%d", $num);
        }
        function convInt16ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7fff) // is negative
                $num -= 0x10000;
            return sprintf("%d", $num);
        }
        function convInt24ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7fffff) // is negative
                $num -= 0x1000000;
            return sprintf("%d", $num);
        }
        function convInt32ToString($value) {
            $num = hexdec($value);
            if ($num > 0x7fffffff) // is negative
                $num -= 0x100000000;
            $conv = sprintf("%d", $num);
            // $this->deamonlog( 'debug', 'convInt32ToString value='.$value.', conv='.$conv );
            return $conv;
        }

        function getFF01IdName($id) {
            $IdName = array(
                '01' => "Volt",             // Based on Xiaomi Bouton V2 Carré
                '03' => "tbd1",
                '05' => "tbd2",
                '07' => "tbd3",
                '08' => "tbd4",
                '09' => "tbd5",
                '64' => "Etat SW 1 Binaire", // Based on Aqara Double Relay (mais j ai aussi un 64 pour la temperature (Temp carré V2)
                '65' => "Etat SW 2 Binaire", // Based on Aqara Double Relay (mais j ai aussi un 65 pour Humidity Temp carré V2)
                '66' => "Pression",          // Based on Temperature Capteur V2
                '6e' => "Etat SW 1 Analog",  // Based on Aqara Double Relay
                '6f' => "Etat SW 2 Analog",  // Based on Aqara Double Relay
                '94' => "tbd6",
                '95' => "Consommation",     // Based on Prise Xiaomi
                '96' => "tbd8",
                '97' => "tbd9",
                '98' => "Puissance",        // Based on Aqara Double Relay
                '9b' => "tbd11",
            );
            if (array_key_exists($id, $IdName))
                return $IdName[$id];
            return '?'.$id.'?';
        }

        /* Decode FF01 attribut payload */
        function decodeFF01($data) {
            $fields = array();
            $dataLen = strlen($data);
            while ($dataLen != 0) {
                if ($dataLen < 4) {
                    $this->deamonlog('debug', 'decodeFF01(): Longueur incorrecte ('.$dataLen.'). Analyse FF01 interrompue.');
                    break;
                }

                $id = $data[0].$data[1];
                $type = $data[2].$data[3];
                $dt_arr = $this->getZbDataType($type);
                $len = $dt_arr[1] * 2;
                if ($len == 0) {
                    /* If length is unknown we can't continue since we don't known where is next good position */
                    $this->deamonlog('warning', 'decodeFF01(): Type de données '.$type.' non supporté. Analyse FF01 interrompue.');
                    break;
                }
                $value = substr($data, 4, $len );
                $fct = 'conv'.$dt_arr[0].'ToString';
                if (method_exists($this, $fct)) {
                    $valueConverted = $this->$fct($value);
                    // $this->deamonlog('debug', 'decodeFF01(): Conversion du type '.$type.' (val='.$value.')');
                } else {
                    $this->deamonlog('debug', 'decodeFF01(): Conversion du type '.$type.' non supporté');
                    $valueConverted = "";
                }

                $fields[$this->getFF01IdName($id)] = array(
                    'id' => $id,
                    'type' => $type,
                    'typename' => $dt_arr[0],
                    'value' => $value,
                    'valueConverted' => $valueConverted,
                );
                $data = substr($data, 4 + $len);
                $dataLen = strlen($data);
            }
            return $fields;
        }

        function displayStatus($status) {
            $return = "";
            switch ($status) {
            case "00":
                $return = "00-(Success)";
                break;
            case "01":
                $return = "01-(Incorrect Parameters)";
                break;
            case "02":
                $return = "02-(Unhandled Command)";
                break;
            case "03":
                $return = "03-(Command Failed)";
                break;
            case "04":
                $return = "04-(Busy (Node is carrying out a lengthy operation and is currently unable to handle the incoming command) )";
                break;
            case "05":
                $return = "05-(Stack Already Started (no new configuration accepted) )";
                break;
            default:
                $return = $status."-(ZigBee Error Code unknown)";
                break;
            }

            return $return;
        }

        function protocolDatas($dest, $datas, $qos, $clusterTab, &$LQI) {
            // datas: trame complete recue sur le port serie sans le start ni le stop.
            // 01: 01 Start
            // 02-03: Msg Type
            // 04-05: Length
            // 06: crc
            // 07-: Data / Payload
            // Last 8 bit is Link quality (modif zigate)
            // xx: 03 Stop

            $tab = "";
            $crctmp = 0;

            $length = strlen($datas);
            // Message trop court pour etre un vrai message
            if ($length < 12) { return -1; }

            //$this->deamonlog('debug','protocolDatas: '.$datas);
            //$this->deamonlog('debug', ' Data ('.$length.'>12 char): '.$datas);

            //type de message
            $type = $datas[0].$datas[1].$datas[2].$datas[3];
            $crctmp = $crctmp ^ hexdec($datas[0].$datas[1]) ^ hexdec($datas[2].$datas[3]);

            //taille message
            $ln = $datas[4].$datas[5].$datas[6].$datas[7];
            $crctmp = $crctmp ^ hexdec($datas[4].$datas[5]) ^ hexdec($datas[6].$datas[7]);

            //acquisition du CRC
            $crc = strtolower($datas[8].$datas[9]);

            //payload
            $payload = "";
            for ($i = 0; $i < hexdec($ln); $i++) {
                $payload .= $datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)];
                $crctmp = $crctmp ^ hexdec($datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)]);
            }

            // RSSI
            $quality = $datas[10 + ($i * 2) - 2].$datas[10 + ($i * 2) - 1];
            $quality = hexdec( $quality );

            // $payloadLength = strlen($payload) - 2;

            //verification du CRC
            if (hexdec($crc) != $crctmp) {
                $this->deamonlog('error', 'ERREUR CRC: calc=0x'.dechex($crctmp).', att=0x'.$crc.'. Message ignoré: '.substr($datas, 0, 12).'...'.substr($datas, -2, 2));
                $this->deamonlog('debug', 'Mess ignoré='.$datas);
                return -1;
            }

            //Traitement PAYLOAD
            $param1 = "";
            if (($type == "8003") || ($type == "8043")) $param1 = $clusterTab;
            if ($type == "804e") $param1=$LQI;
            if ($type == "8102") $param1=$quality;

            $fct = "decode".$type;
            // $this->deamonlog('debug','Calling function: '.$fct);

            //  if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none' ) == $ExtendedAddress ) {
            //               config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 1,   'Abeille');

            $commandAcceptedUntilZigateIdentified = array( "decode8009", "decode8024", "decode8000" );

            // On vérifie que l on est sur la bonne zigate.
            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 'Abeille', '0', 1 ) == 0 ) {
                if ( !in_array($fct, $commandAcceptedUntilZigateIdentified) ) {
                    return;
                }
            }

            if ( method_exists($this, $fct) ) {
                $this->$fct($dest, $payload, $ln, $qos, $param1); }
            else {
                $msgName = getZigateMsgByType($type);
                $this->deamonlog('debug', 'Message \''.$type.'/'.$msgName.'\' ignoré.');
            }

            return $tab;
        }

        /*--------------------------------------------------------------------------------------------------*/
        /* $this->decode functions
         /*--------------------------------------------------------------------------------------------------*/

        // Device announce
        function decode004d($dest, $payload, $ln, $qos, $dummy)
        {
            // < short address: uint16_t>
            // < IEEE address: uint64_t>
            // < MAC capability: uint8_t> MAC capability
            // Bit 0 - Alternate PAN Coordinator    => 1 no
            // Bit 1 - Device Type                  => 2 yes
            // Bit 2 - Power source                 => 4 yes
            // Bit 3 - Receiver On when Idle        => 8 yes
            // Bit 4 - Reserved                     => 16 no
            // Bit 5 - Reserved                     => 32 no
            // Bit 6 - Security capability          => 64 no
            // Bit 7 - Allocate Address             => 128 no
            // Pour le Rejoin, à la fin de la trame, j'ai rajouté une information pour savoir si c'est un JOIN classique ou REJOIN c'est un uint8 - JOIN =0 REJOIN= 2 (mail du 22/11/2019 17:11)
            $test = 2 + 4 + 8;

            $this->deamonlog('debug', 'Type=004d/Device announce'
                             . ': Dest='.$dest
                             . ', SrcAddr='  .substr($payload,  0, 4)
                             . ', IEEE='      .substr($payload,  4, 16)
                             . ', MACCapa='  .substr($payload, 20, 2)
                             . ', Rejoin='    .substr($payload, 22, 2)
                             );

            $SrcAddr    = substr($payload,  0,  4);
            $IEEE       = substr($payload,  4, 16);
            $capability = substr($payload, 20,  2);

            // Envoie de la IEEE a Jeedom qui le processera dans la cmd de l objet si celui ci existe deja, sinon sera drop
            $this->mqqtPublish($dest."/".$SrcAddr, "IEEE", "Addr", $IEEE);

            $this->mqqtPublishFct( $dest."/"."Ruche", "enable", $IEEE);

            // Rafraichi le champ Ruche, JoinLeave (on garde un historique)
            $this->mqqtPublish($dest."/"."Ruche", "joinLeave", "IEEE", "Annonce->".$IEEE);

            $this->mqqtPublishFctToCmd(     "Cmd".$dest."/Ruche/ActiveEndPoint",                  "address=".$SrcAddr );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/ActiveEndPoint&time=".(time()+2), "address=".$SrcAddr );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/ActiveEndPoint&time=".(time()+4), "address=".$SrcAddr );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/ActiveEndPoint&time=".(time()+6), "address=".$SrcAddr );

            $this->actionQueue[] = array( 'when'=>time()+1, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$SrcAddr, 'parm1'=>"IEEE", 'parm2'=>"Addr", 'parm3'=>$IEEE );
            $this->actionQueue[] = array( 'when'=>time()+2, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$SrcAddr, 'parm1'=>"IEEE", 'parm2'=>"Addr", 'parm3'=>$IEEE );
            $this->actionQueue[] = array( 'when'=>time()+4, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$SrcAddr, 'parm1'=>"IEEE", 'parm2'=>"Addr", 'parm3'=>$IEEE );
            $this->actionQueue[] = array( 'when'=>time()+6, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$SrcAddr, 'parm1'=>"IEEE", 'parm2'=>"Addr", 'parm3'=>$IEEE );
        }

        /* Fonction specifique pour le retour d'etat de l interrupteur Livolo. */
        function decode0100($dest, $payload, $ln, $qos, $dummy)
        {
            // obj -> ZiGate            0x0100
            // Read Attribute request
            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
                // <Cluster id: uint16_t> : missing
            // <direction: uint8_t>
            // <manufacturer specific: uint8_t>
            // <manufacturer id: uint16_t>
            // <number of attributes: uint8_t>
            // <attributes list: data list of uint16_t each>
            // Direction:
            //  0 – from server to client
            //  1 – from client to server
            //  Manufacturer specific :
            //  0 – No 1 – Yes
            // Probleme sur format c.f. mail avec Fred.

// Cmd         AddrMode   Addr EPS EPD Dir Spe Id   #  #1 #2 #3 #4 #5 Value
// 0100 0011bb 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 00    8a -> 0&0 (0)
// 0100 0011bd 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 01    8d -> 1&0 (1)
// 0100 0011b9 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 02    8a -> 0&1 (2)
// 0100 0011bf 02         c7b8 06  08  01  01  15d2 06 a8 38 00 12 11 03    8d -> 1&1 (3)
//             0          2    6   8   10                                   30

            $SrcAddr    = substr($payload,  2,  4);
            $EPS        = substr($payload,  6,  2);
            $ClusterId  = "0006-".$EPS;
            $AttributId = "0000";
            $data       = substr($payload, 30,  2);

            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        // Zigate Status
        function decode8000($dest, $payload, $ln, $qos, $dummy)
        {
            $status     = substr($payload, 0, 2);
            $SQN        = substr($payload, 2, 2);
            $PacketType = substr($payload, 4, 4);

            if ($this->debug['8000']) {
                $this->deamonlog('debug', 'Type=8000/Status'
                                 . ': Dest='.$dest
                                 . ', Length='.hexdec($ln)
                                 . ', Status='.$this->displayStatus($status)
                                 . ', SQN='.$SQN
                                 . ', PacketType='.$PacketType  );

                // if ( $SQN==0 ) { $this->deamonlog('debug', 'Type=8000; SQN: 0 for messages which are not transmitted over the air.'); }
            }

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "8000";
            $data       = $this->displayStatus($status);

            // $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            $msgAbeille = array ('dest'         => $dest,
                                 'type'         => "8000",
                                 'status'       => $status,
                                 'SQN'          => $SQN,
                                 'PacketType'   => $PacketType , // The value of the initiating command request.
                                 );

            // Envoie du message 8000 (Status) pour AbeilleMQTTCmd pour la gestion du flux de commandes vers la zigate
            if (msg_send( $this->queueKeyParserToCmdSemaphore, 1, $msgAbeille, true, false)) {
                // $this->deamonlog("debug","(fct mqqtPublish) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct decode8000) could not add message to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
            }
        }

        // function decode8001($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8001/Log (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        function decode8002($dest, $payload, $ln, $qos, $dummy) {
            $frameControlField      = substr($payload, 2, 2);
            $destEndPoint           = substr($payload, 4, 2);
            $cluster                = substr($payload, 6, 4);
            $profile                = substr($payload,10, 4);
            $dummy1                 = substr($payload,14, 2);
            $address                = substr($payload,16, 4);
            $dummy2                 = substr($payload,20, 6);
            $SQN                    = substr($payload,26, 2);
            $status                 = substr($payload,28, 2);
            $tableSize              = hexdec(substr($payload,30, 2));
            $index                  = hexdec(substr($payload,32, 2));
            $tableCount             = hexdec(substr($payload,34, 2));
            
            $this->deamonlog('debug', 'Type=8002/Data indication (ignoré)'
                             . ': Dest='.$dest
                             . ', tableSize='.$tableSize
                             . ', index='.$index
                             . ', tableCount='.$tableCount
                             );
            
            for ($i = $index; $i < $index+$tableCount; $i++) {
                $this->deamonlog('debug', '    address='.substr($payload,36+($i*10), 4).' status='.substr($payload,36+($i*10)+4,2).' Next Hop='.substr($payload,36+($i*10)+4+2,4));
            }
        }

        function decode8003($dest, $payload, $ln, $qos, $clusterTab)
        {
            // <source endpoint: uint8_t t>
            // <profile ID: uint16_t>
            // <cluster list: data each entry is uint16_t>

            $SrcEndpoint = substr($payload, 0, 2);
            $profileID      = substr($payload, 2, 4);

            $len = (strlen($payload)-2-4-2)/4;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug', 'Type=8003/Liste des clusters de l’objet (Not Processed - Just $this->decoded): Dest='.$dest.', SrcEndPoint='.$SrcEndpoint.', ProfileID='.$profileID.', Cluster='.substr($payload, (6 + ($i*4) ), 4). ' - ' . $clusterTab['0x'.substr($payload, (6 + ($i*4) ), 4)]);
            }
        }

        function decode8004($dest, $payload, $ln, $qos, $dummy)
        {
            // <source endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            // <attribute list: data each entry is uint16_t>

            $SrcEndpoint = substr($payload, 0, 2);
            $profileID      = substr($payload, 2, 4);
            $clusterID      = substr($payload, 6, 4);

            $len = (strlen($payload)-2-4-4-2)/4;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug', 'Type=8004/Liste des Attributs de l’objet (Not Processed - Just $this->decoded): Dest='.$dest.', SrcEndPoint='.$SrcEndpoint.', ProfileID='.$profileID.', ClusterID='.$clusterID.', Attribute='.substr($payload, (10 + ($i*4) ), 4) );
            }
        }

        function decode8005($dest, $payload, $ln, $qos, $dummy)
        {
            // $this->deamonlog('debug',';type: 8005: (Liste des commandes de l’objet)(Not Processed)' );

            // <source endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            //<command ID list:data each entry is uint8_t>

            $SrcEndpoint = substr($payload, 0, 2);
            $profileID      = substr($payload, 2, 4);
            $clusterID      = substr($payload, 6, 4);

            $len = (strlen($payload)-2-4-4-2)/2;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug', 'Type=8005/Liste des commandes de l’objet (Not Processed - Just $this->decoded): Dest='.$dest.', SrcEndpoint='.$SrcEndpoint.', ProfileID='.$profileID.', ClusterID='.$clusterID.', Commandes='.substr($payload, (10 + ($i*2) ), 2) );
            }
        }

        // function decode8006($dest, $payload, $ln, $qos, $dummy)
        // {
            // Firmware 3.1a,  Fix Rearranged teNODE_STATES to logical in all cases https://github.com/fairecasoimeme/ZiGate/issues/101

            // $this->deamonlog('debug', 'Type=8006/Non “Factory new” Restart (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // function decode8007($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8007/“Factory New” Restart (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // function decode8008($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8008/“Function inconnue pas dans la doc" (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        /* Network State Reponse */
        function decode8009($dest, $payload, $ln, $qos, $dummy)
        {
            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; (Network State response)(Processed->MQTT)'); }

            // <Short Address: uint16_t>
            // <Extended Address: uint64_t>
            // <PAN ID: uint16_t>
            // <Ext PAN ID: uint64_t>
            // <Channel: u int8_t>
            $ShortAddress       = substr($payload, 0, 4);
            $ExtendedAddress    = substr($payload, 4,16);
            $PAN_ID             = substr($payload,20, 4);
            $Ext_PAN_ID         = substr($payload,24,16);
            $Channel            = hexdec(substr($payload,40, 2));

            if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009/Network State Response: Dest='.$dest.', ShortAddr='.$ShortAddress.', ExtAddr='.$ExtendedAddress.', PANId='.$PAN_ID.', ExtPANId='.$Ext_PAN_ID.', Channel='.$Channel); }

            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none', 1 ) == "none" ) {
                config::save( str_replace('Abeille', 'AbeilleIEEE', $dest), $ExtendedAddress,   'Abeille');
            }
            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none', 1 ) == $ExtendedAddress ) {
                config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 1,   'Abeille');
            }
            else {
                config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), -1,   'Abeille');
                message::add("Abeille", "La zigate ".$dest." detectee ne semble pas etre la bonne (IEEE qui remonte: ".$ExtendedAddress." alors que j ai en memoire: ".config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none', 1 )."). Je la bloque pour ne pas créer de soucis.", "Verifiez que les zigates sont bien sur le bon port tty par example suite  a un reboot..", 'Abeille/Demon');
                return;
            }

            // Envoie Short Address
            $SrcAddr = "Ruche";
            $ClusterId = "Short";
            $AttributId = "Addr";
            $data = $ShortAddress;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; ZiGate Short Address: '.$ShortAddress); }

            // Envoie Extended Address
            $SrcAddr = "Ruche";
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $data = $ExtendedAddress;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; IEEE Address: '.$ExtendedAddress); }

            // Envoie PAN ID
            $SrcAddr = "Ruche";
            $ClusterId = "PAN";
            $AttributId = "ID";
            $data = $PAN_ID;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; PAN ID: '.$PAN_ID); }

            // Envoie Ext PAN ID
            $SrcAddr = "Ruche";
            $ClusterId = "Ext_PAN";
            $AttributId = "ID";
            $data = $Ext_PAN_ID;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; Ext_PAN_ID: '.$Ext_PAN_ID); }

            // Envoie Channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Channel";
            $data = $Channel;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; Channel: '.$Channel); }

            // if ($this->debug['8009']) { $this->deamonlog('debug', 'Type=8009; ; Level=0x'.substr($payload, 0, 2)); }
        }

        /* Version */
        function decode8010($dest, $payload, $ln, $qos, $dummy)
        {
            /*
            <Major version number: uint16_t>
            <Installer version number: uint16_t>
            */

            if ($this->debug['8010']) {
                $this->deamonlog('debug', 'Type=8010/Version: Appli='.hexdec(substr($payload, 0, 4)) . ', SDK='.substr($payload, 4, 4));
            }
            $SrcAddr = "Ruche";
            $ClusterId = "SW";
            $AttributId = "Application";
            $data = substr($payload, 0, 4);
            // if ($this->debug['8010']) { $this->deamonlog("debug", 'Type=8010; '.$AttributId.": ".$data." qos:".$qos); }
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            $SrcAddr = "Ruche";
            $ClusterId = "SW";
            $AttributId = "SDK";
            $data = substr($payload, 4, 4);
            // if ($this->debug['8010']) { $this->deamonlog('debug', 'Type=8010; '.$AttributId.': '.$data.' qos:'.$qos); }
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        /* ACK DATA (since FW 3.1b) */
        function decode8011($dest, $payload, $ln, $qos, $dummy)
        {
            /*
            <Status: uint8_t>
            <Destination address: uint16_t>
            <Dest Endpoint : uint8_t>
            <Cluster ID : uint16_t>
            */
            $Status = substr($payload, 0, 2);
            $DestAddr = substr($payload, 2, 4);
            $DestEndPoint = substr($payload, 6, 2);
            $ClustID = substr($payload, 8, 4);

            $this->deamonlog('debug', 'Type=8011/APS_DATA_ACK (ignoré): Status='.$Status.', DestAddr='.$DestAddr.', DestEndPoint='.$DestEndPoint.', ClustID='.$ClustID);
        }

        function decode8014($dest, $payload, $ln, $qos, $dummy)
        {
            // “Permit join” status
            // response Msg Type=0x8014
            // 0 - Off 1 - On
            //<Status: bool_t>
            // Envoi Status

            $data = substr($payload, 0, 2);

            $this->deamonlog('debug', 'Type=8014/Permit join status response: PermitJoinStatus='.$data);
            if ($data == "01")
                $this->deamonlog('info', 'Zigate'.substr($dest, 7, 1).' en mode INCLUSION');
            else
                $this->deamonlog('info', 'Zigate'.substr($dest, 7, 1).': FIN du mode inclusion');

            $SrcAddr = "Ruche";
            $ClusterId = "permitJoin";
            $AttributId = "Status";
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8015($dest, $payload, $ln, $qos, $dummy)
        {
            // <device list – data each entry is 13 bytes>
            // <ID: uint8_t>
            // <Short address: uint16_t>
            // <IEEE address: uint64_t>
            // <Power source: bool_t> 0 – battery 1- AC power
            // <LinkQuality : uint8_t> 1-255

            // Id Short IEEE             Power  LQ
            // 00 ffe1  00158d0001d5c421 00     a6
            // 01 c4bf  00158d0001215781 00     aa
            // 02 4f34  00158d00016d8d4f 00     b5
            // 03 304a  00158d0001a66ca3 00     a4
            // 04 cc0D  00158d0001d6c177 00     b3
            // 05 3c58  00158d00019f9199 00     9a
            // 06 7c3b  000B57fffe2c82e9 00     bb
            // 07 7c54  00158d000183afeb 01     c3
            // 08 3db8  00158d000183af7b 01     c5
            // 32 553c  000B57fffe3025ad 01     9f
            // 00 -> Pourquoi 00 ?

            $this->deamonlog('debug', 'Type=8015/Abeille List: Payload='.$payload);

            $nb = (strlen($payload) - 2) / 26;
            $this->deamonlog('debug','  Nombre d\'abeilles: '.$nb);

            for ($i = 0; $i < $nb; $i++) {

                $SrcAddr = substr($payload, $i * 26 + 2, 4);

                // Envoie IEEE
                $ClusterId = "IEEE";
                $AttributId = "Addr";
                $dataAddr = substr($payload, $i * 26 + 6, 16);
                $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $dataAddr);

                // Envoie Power Source
                $ClusterId = "Power";
                $AttributId = "Source";
                $dataPower = substr($payload, $i * 26 + 22, 2);
                $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $dataPower);

                // Envoie Link Quality
                $ClusterId = "Link";
                $AttributId = "Quality";
                $dataLink = hexdec(substr($payload, $i * 26 + 24, 2));
                $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $dataLink);

                $this->deamonlog('debug', '  i='.$i
                                 . ': Dest='.$dest
                                 . ', ID='.substr($payload, $i * 26 + 0, 2)
                                 . ', ShortAddr='.$SrcAddr
                                 . ', ExtAddr='.$dataAddr
                                 . ', PowerSource (0:battery - 1:AC)='.$dataPower
                                 . ', LinkQuality='.$dataLink   );
            }
        }

        function decode8017($dest, $payload, $ln, $qos, $dummy)
        {
            // Get Time server Response (v3.0f)
            // <Timestamp UTC: uint32_t> from 2000-01-01 00:00:00
            $Timestamp = substr($payload, 0, 8);
            $this->deamonlog('debug', 'Type=8017/Get Time server Response: Timestamp='.hexdec($Timestamp) );

            $SrcAddr = "Ruche";
            $ClusterId = "ZiGate";
            $AttributId = "Time";
            $data = date( DATE_RFC2822, hexdec($Timestamp) );
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        /* Network joined/formed */
        function decode8024($dest, $payload, $ln, $qos, $dummy)
        {
            // https://github.com/fairecasoimeme/ZiGate/issues/74

            // Formed Msg Type = 0x8024
            // Node->Host  Network Joined / Formed

            // <status: uint8_t>
            // <short address: uint16_t>
            // <extended address:uint64_t>
            // <channel: uint8_t>

            // Status:
            // 0 = Joined existing network
            // 1 = Formed new network
            // 128 – 244 = Failed (ZigBee event codes)

            // Envoi Status
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Status";
            if( substr($payload, 0, 2) == "00" ) { $data = "Joined existing network"; }
            if( substr($payload, 0, 2) == "01" ) { $data = "Formed new network"; }
            if( substr($payload, 0, 2) == "04" ) { $data = "Network (already) formed"; }
            if( substr($payload, 0, 2) > "04" ) { $data = "Failed (ZigBee event codes): ".substr($payload, 0, 2); }
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            // Envoie Short Address
            $SrcAddr = "Ruche";
            $ClusterId = "Short";
            $AttributId = "Addr";
            $dataShort = substr($payload, 2, 4);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $dataShort);

            // Envoie IEEE Address
            $SrcAddr = "Ruche";
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $dataIEEE = substr($payload, 6,16);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $dataIEEE);

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Channel";
            $dataNetwork = hexdec( substr($payload,22, 2) );
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $dataNetwork);

            $this->deamonlog('debug', 'Type=8024/Network joined-formed: Dest='.$dest.', Status=\''.$data.'\', ShortAddr='.$dataShort.', ExtAddr='.$dataIEEE.', Channel='.$dataNetwork);
        }

        // function decode8028($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8028/Authenticate response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // function decode802B($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=802B/User Descriptor Notify (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // function decode802C($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=802C/User Descriptor Response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        function decode8030($dest, $payload, $ln, $qos, $dummy)
        {
            // Firmware V3.1a: Add fields for 0x8030, 0x8031 Both responses now include source endpoint, addressmode and short address. https://github.com/fairecasoimeme/ZiGate/issues/122
            // <Sequence number: uint8_t>
            // <status: uint8_t>

            $this->deamonlog('debug', 'Type=8030/Bind response (decoded but Not Processed - Just send time update and status to Network-Bind in Ruche)'
                             . ': Dest='.$dest
                             . ', SQN=0x'.substr($payload, 0, 2)
                             . ', Status=0x'.substr($payload, 2, 2)  );

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Bind";
            $data = date("Y-m-d H:i:s")." Status (00: Ok, <>0: Error): ".substr($payload, 2, 2);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        // function decode8031($dest, $payload, $ln, $qos, $dummy)
        // {
            // Firmware V3.1a: Add fields for 0x8030, 0x8031 Both responses now include source endpoint, addressmode and short address. https://github.com/fairecasoimeme/ZiGate/issues/122

            // $this->deamonlog('debug', 'Type=8031/unBind response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // function decode8034($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8034/Complex Descriptor response (ignoré)'
                             // . ', Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        function decode8040($dest, $payload, $ln, $qos, $dummy)
        {
            // Firmware V3.1a: Add SrcAddr to 0x8040 command (MANAGEMENT_LQI_REQUEST) https://github.com/fairecasoimeme/ZiGate/issues/198

            // Network Address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>

            $this->deamonlog('debug', 'Type=8040/Network Address response (decoded but Not Processed)'
                             . ': Dest='.$dest
                             . ', SQN='                                    .substr($payload, 0, 2)
                             . ', Status='                                 .substr($payload, 2, 2)
                             . ', ExtAddr='                           .substr($payload, 4,16)
                             . ', ShortAddr='                          .substr($payload,20, 4)
                             . ', NumberOfAssociatedDevices='           .substr($payload,24, 2)
                             . ', StartIndex='                            .substr($payload,26, 2) );

            if ( substr($payload, 2, 2)!= "00" ) {
                $this->deamonlog('debug', '  Type=8040: Don t use this data there is an error, comme info not known');
            }

            for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
                $this->deamonlog('debug', '  AssociatedDev='.substr($payload, (28 + $i), 4) );
            }
        }

        function decode8041($dest, $payload, $ln, $qos, $dummy)
        {
            // IEEE Address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>

            $this->deamonlog('debug', 'Type=8041/IEEE Address response'
                             . ': Dest='.$dest
                             . ', SQN='                                    .substr($payload, 0, 2)
                             . ', Status='                                 .substr($payload, 2, 2)
                             . ', ExtAddr='                           .substr($payload, 4,16)
                             . ', ShortAddr='                          .substr($payload,20, 4)
                             . ', NumberOfAssociatedDevices='           .substr($payload,24, 2)
                             . ', StartIndex='                            .substr($payload,26, 2) );

            if ( substr($payload, 2, 2)!= "00" ) {
                $this->deamonlog('debug', '  Don t use this data there is an error, comme info not known');
            }

            for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
                $this->deamonlog('debug', '  AssociatedDev='    .substr($payload, (28 + $i), 4) );
            }

            $SrcAddr = substr($payload,20, 4);
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $data = substr($payload, 4,16);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        // function decode8042($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8042/Node Descriptor response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        function decode8043($dest, $payload, $ln, $qos, $clusterTab)
        {
            // <Sequence number: uint8_t>   -> 2
            // <status: uint8_t>            -> 2
            // <nwkAddress: uint16_t>       -> 4
            // <length: uint8_t>            -> 2
            // <endpoint: uint8_t>          -> 2
            // <profile: uint16_t>          -> 4
            // <device id: uint16_t>        -> 4
            // <bit fields: uint8_t >       -> 2
            // <InClusterCount: uint8_t >   -> 2
            // <In cluster list: data each entry is uint16_t> -> 4
            // <OutClusterCount: uint8_t>   -> 2
            // <Out cluster list: data each entry is uint16_t> -> 4
            // Bit fields: Device version: 4 bits (bits 0-4) Reserved: 4 bits (bits4-7)

            global $profileTable;
            global $deviceInfo;

            $SrcAddr    = substr($payload, 4, 4);
            $EPoint     = substr($payload,10, 2);
            $profile    = substr($payload,12, 4);
            $deviceId   = substr($payload,16, 4);
            $InClusterCount = substr($payload,22, 2); // Number of input clusters

            $this->deamonlog('debug', 'Type=8043/Simple Descriptor Response'
                             . ': Dest='.$dest
                             . ', SQN='             .substr($payload, 0, 2)
                             . ', Status='          .substr($payload, 2, 2)
                             . ', ShortAddr='   .substr($payload, 4, 4)
                             . ', Length='          .substr($payload, 8, 2)
                             . ', EndPoint='        .substr($payload,10, 2)
                             . ', Profile='         .substr($payload,12, 4) . ' (' . $profileTable[substr($payload,12, 4)] . ')'
                             . ', DeviceId='        .substr($payload,16, 4) . ' (' . $deviceInfo[substr($payload,12, 4)][substr($payload,16, 4)] .')'
                             . ', BitField='        .substr($payload,20, 2));

            $this->deamonlog('debug','  InClusterCount='.$InClusterCount);
            for ($i = 0; $i < (intval(substr($payload, 22, 2)) * 4); $i += 4) {
                $this->deamonlog('debug', '  InCluster='.substr($payload, (24 + $i), 4). ' - ' . $clusterTab['0x'.substr($payload, (24 + $i), 4)]);
            }
            $this->deamonlog('debug','  OutClusterCount='.substr($payload,24+$i, 2));
            for ($j = 0; $j < (intval(substr($payload, 24+$i, 2)) * 4); $j += 4) {
                $this->deamonlog('debug', '  OutCluster='.substr($payload, (24 + $i +2 +$j), 4) . ' - ' . $clusterTab['0x'.substr($payload, (24 + $i +2 +$j), 4)]);
            }

            $data = 'zigbee'.$deviceInfo[$profile][$deviceId];
            if ( strlen( $data) > 1 ) {
                $this->mqqtPublish($dest."/".$SrcAddr, "SimpleDesc-".$EPoint, "DeviceDescription", $data);
            }
        }

        // function decode8044($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8044/Power Descriptor response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // Active Endpoints Response
        function decode8045($dest, $payload, $ln, $qos, $dummy)
        {
            $SrcAddr = substr($payload, 4, 4);
            $EP = substr($payload, 10, 2);

            $endPointList = "";
            for ($i = 0; $i < (intval(substr($payload, 8, 2)) * 2); $i += 2) {
                // $this->deamonlog('debug','Endpoint : '    .substr($payload, (10 + $i), 2));
                $endPointList = $endPointList . '; '.substr($payload, (10 + $i), 2) ;
            }

            $this->deamonlog('debug', 'Type=8045/Active Endpoints Response'
                             . ': Dest='.$dest
                             . ', SQN='             .substr($payload, 0, 2)
                             . ', Status='          .substr($payload, 2, 2)
                             . ', ShortAddr='   .substr($payload, 4, 4)
                             . ', EndPointCount='  .substr($payload, 8, 2)
                             . ', EndPointList='    .$endPointList             );

            $this->mqqtPublishFctToCmd(     "Cmd".$dest."/Ruche/getName",                                     "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            $this->mqqtPublishFctToCmd(     "Cmd".$dest."/Ruche/getLocation",                                 "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/getName&time=".(time()+2),                    "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/getLocation&time=".(time()+2),                "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/SimpleDescriptorRequest&time=".(time()+4),    "address=".$SrcAddr.'&endPoint='.           $EP );
            $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/SimpleDescriptorRequest&time=".(time()+6),    "address=".$SrcAddr.'&endPoint='.           $EP );

            $this->actionQueue[] = array( 'when'=>time()+ 8, 'what'=>'configureNE', 'addr'=>$dest.'/'.$SrcAddr );
            $this->actionQueue[] = array( 'when'=>time()+11, 'what'=>'getNE',       'addr'=>$dest.'/'.$SrcAddr );

        }

        // function decode8046($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8046/Match Descriptor response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2)));
        // }

        // function decode8047($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8047/Management Leave response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2)));
        // }

        function decode8048($dest, $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', 'Type=8048/Leave Indication'
                             . ': Dest='.$dest
                             . ', ExtendedAddr='.substr($payload, 0, 16)
                             . ', RejoinStatus='.substr($payload, 16, 2)    );

            $SrcAddr = "Ruche";
            $ClusterId = "joinLeave";
            $AttributId = "IEEE";

            $IEEE = substr($payload, 0, 16);
            $cmds = Cmd::byLogicalId('IEEE-Addr');
            foreach( $cmds as $cmd ) {
                if ( $cmd->execCmd() == $IEEE ) {
                    $abeille = $cmd->getEqLogic();
                    $name = $abeille->getName();
                }
            }

            $data = "Leave->".substr($payload, 0, 16)."->".substr($payload, 16, 2);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            $SrcAddr = "Ruche";
            $fct = "disable";
            $extendedAddr = substr($payload, 0, 16);
            $this->mqqtPublishFct( $dest."/".$SrcAddr, $fct, $extendedAddr);
        }

        /* 804A = Management Network Update Response */
        function decode804A($dest, $payload, $ln, $qos, $dummy)
        {
            /* Note: Source address added in FW V3.1a */
            /* <Sequence number: uint8_t>
               <status: uint8_t>
               <total transmission: uint16_t>
               <transmission failures: uint16_t>
               <scanned channels: uint32_t >
               <scanned channel list count: uint8_t>
               <channel list: list each element is uint8_t>
               <Src Address : uint16_t> (only from v3.1a) */

            // app_general_events_handler.c
            // E_SL_MSG_MANAGEMENT_NETWORK_UPDATE_RESPONSE
            
            $SQN=substr($payload, 0, 2);
            $Status=substr($payload, 2, 2);
            
            if ($Status!="00") {
                $this->deamonlog('debug', 'Type=804A/Management Network Update Response (Processed): Status Error ('.$Status.') can not process the message.');
                return;
            }
            
            $TotalTransmission = substr($payload, 4, 4);
            $TransmFailures = substr($payload, 8, 4);
            
            $ScannedChannels = substr($payload, 12, 8);
            $ScannedChannelsCount = substr($payload, 20, 2);
            
            /*
            $Channels = "";
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($payload, (22 + ($i * 2)), 2); // hexa value
                if ($i != 0)
                    $Channels .= ';';
                $Channels .= hexdec($Chan);
            }
            
            $this->deamonlog('debug', '  Channels='.$Channels.' address='.$addr);
            */
            
            $Channel = 11; // Could need to be adapted if we change the list of channel requested, at this time all of them.
            $results = array();
            for ($i = 0; $i < (intval($ScannedChannelsCount, 16)); $i += 1) {
                $Chan = substr($payload, (22 + ($i * 2)), 2); // hexa value
                $results[$Channel] = hexdec($Chan);
                $Channel++;
            }
            $addr = substr($payload, (22 + ($i * 2)), 4);
            
            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list( $eqDest, $eqAddr ) = explode("/", $eqLogic->getLogicalId());
                if ( ($dest==$eqDest) &&($addr==$eqAddr) ) {
                    // $this->deamonlog('debug', '  Storing the information');
                    $eqLogic->setConfiguration('totalTransmission', $TotalTransmission);
                    $eqLogic->setConfiguration('transmissionFailures', $TransmFailures);
                    $eqLogic->setConfiguration('localZigbeeChannelPower', $results);
                    $eqLogic->save();
                }
            }
            
            $this->deamonlog('debug', 'Type=804A/Management Network Update Response (Processed): Dest='.$dest.' addr='.$addr
                             . ', SQN=0x'.$SQN
                             . ', Status='.$Status
                             . ', TotalTransmission='.$TotalTransmission
                             . ', TransmFailures='.$TransmFailures
                             . ', ScannedChannels=0x'.$ScannedChannels
                             . ', ScannedChannelsCount=0x'.$ScannedChannelsCount
                             . ', Channels='.json_encode($results)
                             );
                                 
        }

        // function decode804B($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=804B/System Server Discovery response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))  );
        // }

        function decode804E($dest, $payload, $ln, $qos, &$LQI)
        {
            // <Sequence number: uint8_t>
            // <status: uint8_t>
            // <Neighbour Table Entries : uint8_t>
            // <Neighbour Table List Count : uint8_t>
            // <Start Index : uint8_t>
            // <List of Entries elements described below :>
            // Note: If Neighbour Table list count is 0, there are no elements in the list.
            //  NWK Address : uint16_t
            //  Extended PAN ID : uint64_t
            //  IEEE Address : uint64_t
            //  Depth : uint_t
            //  Link Quality : uint8_t
            //  Bit map of attributes Described below: uint8_t
            //  bit 0-1 Device Type
            //  (0-Coordinator 1-Router 2-End Device)
            //  bit 2-3 Permit Join status
            //  (1- On 0-Off)
            //  bit 4-5 Relationship
            //  (0-Parent 1-Child 2-Sibling)
            //  bit 6-7 Rx On When Idle status
            //  (1-On 0-Off)
            // <Src Address : uint16_t> ( only from v3.1a) => Cette valeur est tres surprenante. Ne semble pas valide ou je ne la comprend pas.

            // Le paquet contient 2 LQI mais je ne vais en lire qu'un à la fois pour simplifier le code

            $this->deamonlog('debug', 'Type=804E/Management LQI response'
                              . ': Dest='.$dest
                             . ', SQN='                          .substr($payload, 0, 2)
                             . ', Status='                       .substr($payload, 2, 2)
                             . ', NeighbourTableEntries='      .substr($payload, 4, 2)
                             . ', NeighbourTableListCount='   .substr($payload, 6, 2)
                             . ', StartIndex='                  .substr($payload, 8, 2)
                             . ', NWKAddr='                  .substr($payload,10, 4)
                             . ', ExtPANId='              .substr($payload,14,16)
                             . ', ExtAddr='                 .substr($payload,30,16)
                             . ', Depth='                 .hexdec(substr($payload,46, 2))
                             . ', LinkQuality='          .hexdec(substr($payload,48, 2))
                             . ', BitMapOfAttributes='        .substr($payload,50, 2)
                             . ', SrcAddr='                  .substr($payload,52, 4) );

            $srcAddress           = substr($payload, 52, 4);
            $index                = substr($payload, 8, 2);
            $NeighbourAddr        = substr($payload, 10, 4);
            $NeighbourIEEEAddress = substr($payload, 30,16);
            $lqi                  = hexdec(substr($payload, 48, 2));
            $Depth                = hexdec(substr($payload, 46, 2));
            $bitMapOfAttributes   = substr($payload, 50, 2); // to be $this->decoded
            // $LQI[$srcAddress]     = array($Neighbour=>array('LQI'=>$lqi, 'depth'=>$Depth, 'tree'=>$bitMapOfAttributes, ));

            $data =
            "srcAddress="                   .substr($payload,52, 4)
            ."&NeighbourTableEntries="      .substr($payload, 4, 2)
            ."&Index="                      .substr($payload, 8, 2)
            ."&ExtendedPanId="              .substr($payload,14,16)
            ."&IEEE_Address="               .substr($payload,30,16)
            ."&Depth="                      .substr($payload,46, 2)
            ."&LinkQuality="                .substr($payload,48, 2)
            ."&BitmapOfAttributes="         .substr($payload,50, 2);

            // $this->deamonlog('debug', ';Level=0x'.substr($payload, 0, 2));
            // $this->deamonlog('debug', 'Message=');
            // $this->deamonlog('debug',$this->hex2str(substr($payload, 2, strlen($payload) - 2)));

            //function $this->mqqtPublishLQI( $Addr, $Index, $data, $qos = 0)
            $this->mqqtPublishLQI( $NeighbourAddr, $index, $data);

            if ( strlen($NeighbourAddr) !=4 ) { return; }

            // On regarde si on connait NWK Address dans Abeille, sinon on va l'interroger pour essayer de le récupérer dans Abeille.
            // Ca ne va marcher que pour les équipements en eveil.
            // Cmdxxxx/Ruche/getName address=bbf5&destinationEndPoint=0B
            if ( !Abeille::byLogicalId( $dest.'/'.$NeighbourAddr, 'Abeille') ) {
                $this->deamonlog('debug', 'Type=804E/Management LQI response: NeighbourAddr='.$NeighbourAddr.' qui n est pas dans Jeedom, essayons de l interroger, si en sommail une intervention utilisateur sera necessaire.');

                $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/getName", "address=".$NeighbourAddr."&destinationEndPoint=01" );
                $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/getName", "address=".$NeighbourAddr."&destinationEndPoint=03" );
                $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/getName", "address=".$NeighbourAddr."&destinationEndPoint=0B" );
            }

            $this->mqqtPublishCmdFct( $dest."/".$NeighbourAddr."/IEEE-Addr", $NeighbourIEEEAddress);
                 // Abeille / short addr / Cluster ID - Attr ID -> data
        }

        //----------------------------------------------------------------------------------------------------------------
        function decode8060($dest, $payload, $ln, $qos, $dummy)
        {
            // Answer format changed: https://github.com/fairecasoimeme/ZiGate/pull/97
            // Bizard je ne vois pas la nouvelle ligne dans le maaster zigate alors qu elle est dans GitHub

            // <Sequence number:  uint8_t>
            // <endpoint:         uint8_t>
            // <Cluster id:       uint16_t>
            // <status:           uint8_t>  (added only from 3.0f version)
            // <Group id :        uint16_t> (added only from 3.0f version)
            // <Src Addr:         uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type=8060/Add a group response (ignoré)'
                             . ': Dest='.$dest
                             . ', SQN='           .substr($payload, 0, 2)
                             . ', EndPoint='      .substr($payload, 2, 2)
                             . ', ClusterId='     .substr($payload, 4, 4)
                             . ', Status='        .substr($payload, 8, 2)
                             . ', GroupID='       .substr($payload,10, 4)
                             . ', SrcAddr='       .substr($payload,14, 4) );
        }

        //----------------------------------------------------------------------------------------------------------------
        // function decode8061($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8061/? (ignoré)');
        // }

        // Get Group Membership response
        function decode8062($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uint8_t>                               -> 2
            // <endpoint: uint8_t>                                      -> 2
            // <Cluster id: uint16_t>                                   -> 4
            // <Src Addr: uint16_t> (added only from 3.0d version)      -> 4
            // <capacity: uint8_t>                                      -> 2
            // <Group count: uint8_t>                                   -> 2
            // <List of Group id: list each data item uint16_t>         -> 4x
            // <Src Addr: uint16_t> (added only from 3.0f version) new due to a change impacting many command but here already available above.

            $groupCount = hexdec( substr($payload,10, 2) );
            $groupsId="";
            for ($i=0;$i<$groupCount;$i++)
            {
                $this->deamonlog('debug', 'Type=8062;group '.$i.'(addr:'.(12+$i*4).'): '  .substr($payload,12+$i*4, 4));
                $groupsId .= '-' . substr($payload,12+$i*4, 4);
            }
            $this->deamonlog('debug', 'Type=8062;Groups: ->'.$groupsId."<-");

            // $this->deamonlog('debug', ';Level=0x'.substr($payload, strlen($payload)-2, 2));

            // Envoie Group-Membership
            $SrcAddr = substr($payload, 12+$groupCount*4, 4);
            if ($SrcAddr == "0000" ) { $SrcAddr = "Ruche"; }
            $ClusterId = "Group";
            $AttributId = "Membership";
            if ( $groupsId == "" ) { $data = "none"; } else { $data = $groupsId; }

            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            $this->deamonlog('debug', 'Type=8062/Group Membership'
                             . ': Dest='.$dest
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', Capacity='     .substr($payload, 8, 2)
                             . ', Group count='  .substr($payload,10, 2)
                             . ', Groups='       .$data
                             . ', Source='       .$SrcAddr );
        }

        function decode8063($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <Group id: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type=8063/Remove a group response (ignoré)'
                             . ': Dest='.$dest
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', clusterId='    .substr($payload, 4, 4)
                             . ', statusId='     .substr($payload, 8, 2)
                             . ', groupId='      .substr($payload,10, 4)
                             . ', sourceId='     .substr($payload,14, 4) );
        }

        // https://github.com/fairecasoimeme/ZiGate/issues/6
        // Button   Pres-stype  Response  command       attr
        // down     click       0x8085    0x02          None
        // down     hold        0x8085    0x01          None
        // down     release     0x8085    0x03          None
        // up       click       0x8085    0x06          None
        // up       hold        0x8085    0x05          None
        // up       release     0x8085    0x07          None
        // middle   click       0x8095    0x02          None
        // left     click       0x80A7    0x07          direction: 1
        // left     hold        0x80A7    0x08          direction: 1    => can t get that one
        // right    click       0x80A7    0x07          direction: 0
        // right    hold        0x80A7    0x08          direction: 0    => can t get that one
        // left/right release   0x80A7    0x09          None            => can t get that one
        //
        // down = brightness down, up = brightness up,
        // middle = Power button,
        // left and right = when brightness up is up left is left and right is right.
        // Holding down power button for ~10 sec will result multiple commands sent, but it wont send any hold command only release.
        // Remote won't tell which button was released left or right, but it will be same button that was last hold.
        // Remote is unable to send other button commands at least when left or right is hold down.

        // function decode8084($dest, $payload, $ln, $qos, $dummy) {
            // J ai eu un crash car le soft cherchait cette fonction mais elle n'est pas documentée...
            // $this->deamonlog('debug', 'Type=8084/? (ignoré)');
        // }

        function decode8085($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <addr: uint16_t>             -> 4
            // <cmd: uint8>                 -> 2

            // 2: 'click', 1: 'hold', 3: 'release'

            $this->deamonlog('debug', 'Type=8085/Remote button pressed (ClickHoldRelease) a group response)'
                             . ': dest='          .$dest
                             . ', SQN='           .substr($payload, 0, 2)
                             . ', EndPoint='      .substr($payload, 2, 2)
                             . ', clusterId='     .substr($payload, 4, 4)
                             . ', address_mode='  .substr($payload, 8, 2)
                             . ', SrcAddr='   .substr($payload,10, 4)
                             . ', Cmd='           .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Up";
            $AttributId     = "Down";
            $data           = substr($payload,14, 2);

            $this->mqqtPublish($dest.'/'.$source, $ClusterId, $AttributId, $data);
        }

        function decode8095($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <SrcAddr: uint16_t>      -> 4
            // <cmd: uint8>                 -> 2

            $this->deamonlog('debug', 'Type=8095/Remote button pressed (ONOFF_UPDATE) a group response)'
                             . ': dest='         .$dest
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', StatusId='     .substr($payload, 8, 2)
                             . ', SrcAddr='   .substr($payload,10, 4)
                             . ', Cmd='          .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Click";
            $AttributId     = "Middle";
            $data           = substr($payload,14, 2);

            $this->mqqtPublish($dest .'/'.$source, $ClusterId, $AttributId, $data);
        }

        //----------------------------------------------------------------------------------------------------------------
        ##TODO
        #reponse scene
        #80a0-80a6
        function decode80a0($dest, $payload, $ln, $qos, $dummy)
        {
            // <sequence number: uint8_t>                           -> 2
            // <endpoint : uint8_t>                                 -> 2
            // <cluster id: uint16_t>                               -> 4
            // <status: uint8_t>                                    -> 2

            // <group ID: uint16_t>                                 -> 4
            // <scene ID: uint8_t>                                  -> 2
            // <transition time: uint16_t>                          -> 4

            // <scene name length: uint8_t>                         -> 2
            // <scene name max length: uint8_t>                     -> 2
            // <scene name  data: data each element is uint8_t>     -> 2

            // <extensions length: uint16_t>                        -> 4
            // <extensions max length: uint16_t>                    -> 4
            // <extensions data: data each element is uint8_t>      -> 2
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type=80A0/Scene View (ignoré)'
                             . ': Dest='.$dest
                             . ', SQN='                           .substr($payload, 0, 2)
                             . ', EndPoint='                      .substr($payload, 2, 2)
                             . ', ClusterId='                     .substr($payload, 4, 4)
                             . ', Status='                        .substr($payload, 8, 2)

                             . ', GroupID='                      .substr($payload,10, 4)
                             . ', SceneID='                      .substr($payload,14, 2)
                             . ', transition time: '               .substr($payload,16, 4)

                             . ', scene name lenght: '             .substr($payload,20, 2)  // Osram Plug repond 0 pour lenght et rien apres.
                             . ', scene name max lenght: '         .substr($payload,22, 2)
                             . ', scene name : '                   .substr($payload,24, 2)

                             . ', scene extensions lenght: '       .substr($payload,26, 4)
                             . ', scene extensions max lenght: '   .substr($payload,30, 4)
                             . ', scene extensions : '             .substr($payload,34, 2) );
        }

        // function decode80a1($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=80a1/? (ignoré)');
        // }

        // function decode80a2($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=80a2/? (ignoré)');
        // }

        function decode80a3($dest, $payload, $ln, $qos, $dummy)
        {
            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type=80A3/Remove All Scene (ignoré)'
                             . ': Dest='.$dest
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', Status='       .substr($payload, 8, 2)
                             . ', group ID='     .substr($payload,10, 4)
                             . ', source='       .substr($payload,14, 4)  );
        }

        function decode80a4($dest, $payload, $ln, $qos, $dummy)
        {
            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <scene ID: uint8_t>          -> 2
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type=80A3/Store Scene Response (ignoré)'
                             . ': Dest='.$dest
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', Status='       .substr($payload, 8, 2)
                             . ', GroupID='     .substr($payload,10, 4)
                             . ', SceneID='     .substr($payload,14, 2)
                             . ', Source='       .substr($payload,16, 4)  );
        }

        function decode80a6($dest, $payload, $ln, $qos, $dummy)
        {
            // $this->deamonlog('debug', ';Type: 80A6: raw data: '.$payload );

            // Cas du message retour lors d un storeScene sur une ampoule Hue
            if ( strlen($payload)==18  ) {
                // <sequence number: uint8_t>               -> 2
                // <endpoint : uint8_t>                     -> 2
                // <cluster id: uint16_t>                   -> 4
                // <status: uint8_t>                        -> 2

                // <group ID: uint16_t>                     -> 4
                // sceneId: uint8_t                       ->2

                $this->deamonlog('debug', 'Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT)'
                                 . ': Dest='.$dest
                                 . ', SQN='          .substr($payload, 0, 2)      // 1
                                 . ', EndPoint='     .substr($payload, 2, 2)      // 1
                                 . ', ClusterId='    .substr($payload, 4, 4)      // 1
                                 . ', Status='       .substr($payload, 8, 2)      //
                                 // . '; capacity: '     .substr($payload,10, 2)
                                 . ', GroupID='     .substr($payload,10, 4)
                                 . ', SceneID='     .substr($payload,14, 2)  );
            }
            // Cas du message retour lors d un getSceneMemberShip
            else {
                // <sequence number: uint8_t>               -> 2
                // <endpoint : uint8_t>                     -> 2
                // <cluster id: uint16_t>                   -> 4
                // <status: uint8_t>                        -> 2
                // <capacity: uint8_t>                      -> 2
                // <group ID: uint16_t>                     -> 4
                // <scene count: uint8_t>                   -> 2
                // <scene list: data each element uint8_t>  -> 2
                // <Src Addr: uint16_t> (added only from 3.0f version)

                $seqNumber  = substr($payload, 0, 2);
                $endpoint   = substr($payload, 2, 2);
                $clusterId  = substr($payload, 4, 4);
                $status     = substr($payload, 8, 2);
                $capacity   = substr($payload,10, 2);
                $groupID    = substr($payload,12, 4);
                $sceneCount = substr($payload,16, 2);
                $source     = substr($payload,18+$sceneCount*2, 4);

                if ($status!=0) {
                    $this->deamonlog('debug', 'Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT) => Status NOT null'
                                     . ': Dest='.$dest
                                     . ', SQN='          .substr($payload, 0, 2)      // 1
                                     . ', EndPoint='     .substr($payload, 2, 2)      // 1
                                     . ', source='       .$source
                                     . ', ClusterId='    .substr($payload, 4, 4)      // 1
                                     . ', Status='       .substr($payload, 8, 2)      //
                                     . ', capacity='     .substr($payload,10, 2)
                                     . ', GroupID='     .substr($payload,10, 4)
                                     . ', SceneID='     .substr($payload,14, 2)  );
                    return;
                }

                $sceneCount = hexdec( $sceneCount );
                $sceneId="";
                for ($i=0;$i<$sceneCount;$i++)
                {
                    // $this->deamonlog('debug', 'scene '.$i.' scene: '  .substr($payload,18+$i*2, 2));
                    $sceneId .= '-' . substr($payload,18+$sceneCount*2, 2);
                }

                // Envoie Group-Membership (pas possible car il me manque l address short.
                // $SrcAddr = substr($payload, 8, 4);

                $ClusterId = "Scene";
                $AttributId = "Membership";
                if ( $sceneId == "" ) { $data = $groupID."-none"; } else { $data = $groupID . $sceneId; }

                $this->deamonlog('debug', 'Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT)'
                                 . ': Dest='.$dest
                                 . ', SQN='          .$seqNumber
                                 . ', EndPoint='     .$endpoint
                                 . ', ClusterId='    .$clusterId
                                 . ', Status='       .$status
                                 . ', Capacity='     .$capacity
                                 . ', Source='       .$source
                                 . ', GroupID='     .$groupID
                                 . ', SceneID='     .$sceneId
                                 . ', Group-Scenes=' . $data);

                // Je ne peux pas envoyer, je ne sais pas qui a repondu pour tester je mets l adresse en fixe d une ampoule
                $ClusterId = "Scene";
                $AttributId = "Membership";
                $this->mqqtPublish($dest."/".$source, $ClusterId, $AttributId, $data);
            }
        }

        // Telecommande Ikea
        // https://github.com/fairecasoimeme/ZiGate/issues/6
        // https://github.com/fairecasoimeme/ZiGate/issues/64
        // Button   Pres-stype  Response  command       attr
        // down     click       0x8085    0x02          None
        // down     hold        0x8085    0x01          None
        // down     release     0x8085    0x03          None
        // up       click       0x8085    0x06          None
        // up       hold        0x8085    0x05          None
        // up       release     0x8085    0x07          None
        // middle   click       0x8095    0x02          None
        // left     click       0x80A7    0x07          direction: 1
        // left     hold        0x80A7    0x08          direction: 1    => can t get that one
        // right    click       0x80A7    0x07          direction: 0
        // right    hold        0x80A7    0x08          direction: 0    => can t get that one
        // left/right release   0x80A7    0x09          None            => can t get that one
        //
        // down = brightness down, up = brightness up,
        // middle = Power button,
        // left and right = when brightness up is up left is left and right is right.
        // Holding down power button for ~10 sec will result multiple commands sent, but it wont send any hold command only release.
        // Remote won't tell which button was released left or right, but it will be same button that was last hold.
        // Remote is unable to send other button commands at least when left or right is hold down.

        function decode80a7($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <addr: uint16_t>             -> 4
            // <cmd: uint8>                 -> 2
            // <direction: uint8>           -> 2
            // uint8 u8Attr1;
            // uint8 u8Attr2;
            // uint8 u8Attr3;
            // source int16

            // directions = {0: 'right', 1: 'left', 2: 'middle'}
            // {7: 'click', 8: 'hold', 9: 'release'}

            $seqNumber      = substr($payload, 0, 2);
            $endpoint       = substr($payload, 2, 2);
            $clusterId      = substr($payload, 4, 4);
            $cmd            = substr($payload, 8, 2);
            $direction      = substr($payload,10, 2);
            $attr1          = substr($payload,12, 2);
            $attr2          = substr($payload,14, 2);
            $attr3          = substr($payload,16, 2);
            $source         = substr($payload,18, 4);

            $this->deamonlog('debug', 'Type=80a7/Remote button pressed (LEFT/RIGHT) (Processed->$this->decoded but not sent to MQTT)'
                             . ': Dest='.$dest
                             . ', SQN='          .$seqNumber
                             . ', EndPoint='     .$endpoint
                             . ', ClusterId='    .$clusterId
                             . ', cmd='          .$cmd
                             . ', direction='    .$direction
                             . ', u8Attr1='      .$attr1
                             . ', u8Attr2='      .$attr2
                             . ', u8Attr3='      .$attr3
                             . ', Source='       .$source );

            $clusterId = "80A7";
            $AttributId = "Cmd";
            $data = $cmd;
            $this->mqqtPublish($dest."/".$source, $clusterId, $AttributId, $data);

            $clusterId = "80A7";
            $AttributId = "Direction";
            $data = $direction;
            $this->mqqtPublish($dest."/".$source, $clusterId, $AttributId, $data);
        }
        //----------------------------------------------------------------------------------------------------------------

        #Reponse Attributs
        #8100-8140

        function decode8100($dest, $payload, $ln, $qos, $dummy)
        {
            // "Type: 0x8100 (Read Attrib Response)"
            // 8100 000D0C0Cb32801000600000010000101
            $this->deamonlog('debug', 'Type=0x8100; (Read Attrib Response)(Processed->MQTT)'
                             . ': Dest='.$dest
                             . ', SQN='.substr($payload, 0, 2)
                             . ', SrcAddr='.substr($payload, 2, 4)
                             . ', EnPt='.substr($payload, 6, 2)
                             . ', ClusterId='.substr($payload, 8, 4)
                             . ', AttrId='.substr($payload, 12, 4)
                             . ', AttrStatus='.substr($payload, 16, 2)
                             . ', AttrDataType='.substr($payload, 18, 2) );

            $dataType = substr($payload, 18, 2);
            // IKEA OnOff state reply data type: 10
            // IKEA Manufecturer name data type: 42
            /*
             $this->deamonlog('Syze of Attribute: '.substr($payload, 20, 4));
             $this->deamonlog('Data byte list (one octet pour l instant): '.substr($payload, 24, 2));
             */
            $this->deamonlog('debug', '  Size of Attribute='.substr($payload, 20, 4));
            $this->deamonlog('debug', '  Data byte list (one octet pour l instant)='.substr($payload, 24, 2));

            // short addr / Cluster ID / EP / Attr ID -> data
            $SrcAddr    = substr($payload, 2, 4);
            $ClusterId  = substr($payload, 8, 4);
            $EP         = substr($payload, 6, 2);
            $AttributId = substr($payload, 12, 4);

            // valeur hexadécimale  - type -> function
            // 0x00 Null
            // 0x10 boolean                 -> hexdec
            // 0x18 8-bit bitmap
            // 0x20 uint8   unsigned char   -> hexdec
            // 0x21 uint16
            // 0x22 uint32
            // 0x25 uint48
            // 0x28 int8
            // 0x29 int16
            // 0x2a int32
            // 0x30 Enumeration : 8bit
            // 0x42 string                  -> hex2bin
            if ($dataType == "10") {
                $data = hexdec(substr($payload, 24, 2));
            }
            if ($dataType == "20") {
                $data = hexdec(substr($payload, 24, 2));
            }
            if ($dataType == "42") {
                $data = hex2bin(substr($payload, 24, (strlen($payload) - 24)));
            }
            //deamonlog('Data byte: '.$data);
            $this->deamonlog('debug', '  Data byte='.$data);

            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $EP.'-'.$AttributId, $data);
        }

        function decode8101($dest, $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', 'Type=8101/Default Response (ignoré)'
                             . '; Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.'
                             . ': Dest='.$dest
                             . ', SQN='.substr($payload, 0, 2)
                             . ', EndPoint='.substr($payload, 2, 2)
                             . ', '. $this->displayClusterId(substr($payload, 4, 4))
                             . ', Command='.substr($payload, 8, 2)
                             . ', Status='.substr($payload, 10, 2)  );
        }

        /* Attribute report */
        function decode8102($dest, $payload, $ln, $qos, $quality)
        {
            //<Sequence number: uint8_t>
            //<Src address : uint16_t>
            //<Endpoint: uint8_t>
            //<Cluster id: uint16_t>
            //<Attribute Enum: uint16_t>
            //<Attribute status: uint8_t>
            //<Attribute data type: uint8_t>
            //<Size Of the attributes in bytes: uint16_t>
            //<Data byte list : stream of uint8_t>
            $SQN                = substr($payload, 0, 2);
            $SrcAddr            = substr($payload, 2, 4);
            $EPoint             = substr($payload, 6, 2);
            $ClusterId          = substr($payload, 8, 4);
            $AttributId         = substr($payload,12, 4);
            $AttributStatus     = substr($payload,16, 2);
            $dataType           = substr($payload,18, 2);
            $AttributSize       = substr($payload,20, 4);

            /* Params: SrcAddr, ClustId, AttrId, Data */
            $this->mqqtPublish($dest."/".$SrcAddr, 'Link', 'Quality', $quality);

            // 0005: ModelIdentifier
            // 0010: Piece (nom utilisé pour Profalux)
            if ( ($ClusterId=="0000") && ( ($AttributId=="0005") || ($AttributId=="0010") ) ) {
                $this->deamonlog('debug', 'Type=8102/Attribut Report: Dest='.$dest
                                 . ', SQN='             .$SQN
                                 . ', SrcAddr='         .$SrcAddr
                                 . ', EndPoint='        .$EPoint
                                 . ', ClusterID='       .$ClusterId
                                 . ', AttrID='          .$AttributId
                                 . ', AttrStatus='      .$AttributStatus
                                 . ', AttrDataType='    .$dataType
                                 . ', AttrSize='        .$AttributSize
                                 . ', DataByteList='    .pack('H*', substr($payload, 24, (strlen($payload) - 24 - 2)) ));
            }
            else {
                $this->deamonlog('debug', 'Type=8102/Attribut Report: Dest='.$dest
                                 . ', SQN='             .$SQN
                                 . ', SrcAddr='         .$SrcAddr
                                 . ', EndPoint='        .$EPoint
                                 . ', ClusterID='       .$ClusterId
                                 . ', AttrID='          .$AttributId
                                 . ', AttrStatus='      .$AttributStatus
                                 . ', AttrDataType='    .$dataType
                                 . ', AttrSize='        .$AttributSize
                                 . ', DataByteList='    .substr($payload, 24, (strlen($payload) - 24 - 2)));
            }

            // valeur hexadécimale  - type -> function
            // 0x00 Null
            // 0x10 boolean                 -> hexdec
            // 0x18 8-bit bitmap
            // 0x20 uint8   unsigned char   -> hexdec
            // 0x21 uint16                  -> hexdec
            // 0x22 uint32
            // 0x24 ???
            // 0x25 uint48
            // 0x28 int8                    -> hexdec(2)
            // 0x29 int16                   -> unpack("s", pack("s", hexdec(
            // 0x2a int32                   -> unpack("l", pack("l", hexdec(
            // 0x2b ????32
            // 0x30 Enumeration : 8bit
            // 0x42 string                  -> hex2bin

            if ($dataType == "10") {
                $data = hexdec(substr($payload, 24, 2));
            }

            if ($dataType == "18") {
                $data = substr($payload, 24, 2);
            }

            // Exemple Heiman Smoke Sensor Attribut 0002 sur cluster 0500
            if ($dataType == "19") {
                $data = substr($payload, 24, 4);
            }

            if ($dataType == "20") {
                $data = hexdec(substr($payload, 24, 2));
            }

            if ($dataType == "21") {
                $data = hexdec(substr($payload, 24, 4));
            }
            // Utilisé pour remonter la pression par capteur Xiaomi Carré.
            // Octet 8 bits man pack ne prend pas le 8 bits, il prend à partir de 16 bits.

            if ($dataType == "28") {
                // $data = hexdec(substr($payload, 24, 2));
                $in = substr($payload, 24, 2);
                if ( hexdec($in)>127 ) { $raw = "FF".$in ; } else  { $raw = "00".$in; }

                $data = unpack("s", pack("s", hexdec($raw)))[1];
            }

            // Example Temperature d un Xiaomi Carre
            // Sniffer dit Signed 16bit integer
            if ($dataType == "29") {
                // $data = hexdec(substr($payload, 24, 4));
                $data = unpack("s", pack("s", hexdec(substr($payload, 24, 4))))[1];
            }

            if ($dataType == "39") {
                if ( ($ClusterId=="000C") && ($AttributId="0055")  ) {
                    if ($EPoint=="01") {
                        // Remontée puissance (instantannée) relay double switch 1
                        // On va envoyer ca sur la meme variable que le champ ff01
                        $hexNumber = substr($payload, 24, 8);
                        $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                        $bin = pack('H*', $hexNumberOrder );
                        $data = unpack("f", $bin )[1];

                        $puissanceValue = $data;
                        // $this->mqqtPublish( $SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $qos);

                        // Relay Double
                        $this->mqqtPublish($dest."/".$SrcAddr, '000C',     '01-0055',    $puissanceValue,    $qos);
                    }
                    if ( ($EPoint=="02") || ($EPoint=="15")) {
                    // Remontée puissance (instantannée) de la prise xiaomi et relay double switch 2
                    // On va envoyer ca sur la meme variable que le champ ff01
                    $hexNumber = substr($payload, 24, 8);
                    $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                    $bin = pack('H*', $hexNumberOrder );
                    $data = unpack("f", $bin )[1];

                    $puissanceValue = $data;
                    $this->mqqtPublish($dest."/".$SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $qos);

                    // Relay Double
                    $this->mqqtPublish($dest."/".$SrcAddr, '000C',     '02-0055',    $puissanceValue,    $qos);
                    }
                } else {
                    // Example Cube Xiaomi
                    // Sniffer dit Single Precision Floating Point
                    // b9 1e 38 c2 -> -46,03

                    // $data = hexdec(substr($payload, 24, 4));
                    // $data = unpack("s", pack("s", hexdec(substr($payload, 24, 4))))[1];
                    $hexNumber = substr($payload, 24, 8);
                    $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                    $bin = pack('H*', $hexNumberOrder );
                    $data = unpack("f", $bin )[1];
                }
            }

            if ($dataType == "42") {
                // ------------------------------------------------------- Xiaomi ----------------------------------------------------------
                // Xiaomi Bouton V2 Carré
                if (($AttributId == "ff01") && ($AttributSize == "001a")) {
                    $this->deamonlog("debug", "  Champ proprietaire Xiaomi (Bouton Carre)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage='.$voltage.' Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $qos);
                }

                // Xiaomi lumi.sensor_86sw1 (Wall 1 Switch sur batterie)
                elseif (($AttributId == "ff01") && ($AttributSize == "001b")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Wall 1 Switch, Gaz Sensor)" );
                    // Dans le cas du Gaz Sensor, il n'y a pas de batterie alors le decodage est probablement faux.

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat           = substr($payload, 80, 2);

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', Etat=' .$etat);

                    $this->mqqtPublish($dest."/".$SrcAddr, '0006',     '01-0000', $etat,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Door Sensor V2
                elseif (($AttributId == "ff01") && ($AttributSize == "001d")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Door Sensor)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat           = substr($payload, 80, 2);

                    $this->deamonlog('debug', '  DoorV2Voltage='   .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', DoorV2Etat='      .$etat);

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,  $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ));
                    $this->mqqtPublish($dest."/".$SrcAddr, '0006', '01-0000', $etat,  $qos);
                }

                // Xiaomi capteur temperature rond V1 / lumi.sensor_86sw2 (Wall 2 Switches sur batterie)
                elseif (($AttributId == "ff01") && ($AttributSize == "001f")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Capteur Temperature Rond/Wall 2 Switch): ");

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity = hexdec( substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2) );

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage='.$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', Temperature='.$temperature.', Humidity='.$humidity );
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos );
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0402', '01-0000', $temperature,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0405', '01-0000', $humidity,$qos);
                }

                // Xiaomi capteur Presence V2
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102; (Attribut Report)(Processed->MQTT); SQN: 01; Src Addr : a2e1; End Point : 01; Cluster ID : 0000; Attr ID : ff01; Attr Status : 00; Attr Data Type : 42; Attr Size : 0021; Data byte list : 0121e50B0328150421a80105213300062400000000000A2100006410000B212900
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Presence V2)
                // AbeilleParser 2019-01-30 22:51:11[DEBUG];Type; 8102;Voltage; 3045
                // 01 21 e50B param 1 - uint16 - be5 (3.045V) /24
                // 03 28 15                                   /32
                // 04 21 a801                                 /38
                // 05 21 3300                                 /46
                // 06 24 0000000000                           /54
                // 0A 21 0000 - Param 0xA 10dec - uint16 - 0x0 0dec /68
                // 64 10 00 - parm 0x64 100dec - Boolean - 0      (Presence ?)  /76
                // 0B 21 2900 - Param 0xB 11dec - uint16 - 0x0029 (41dec Lux ?) /82

                elseif (($AttributId == 'ff01') && ($AttributSize == "0021")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Capteur Presence V2)');

                    $voltage        = hexdec(substr($payload, 28+2, 2).substr($payload, 28, 2));
                    $lux            = hexdec(substr($payload, 86+2, 2).substr($payload, 86, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', Lux='.$lux);
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0400', '01-0000', $lux,$qos); // Luminosite

                    // $this->mqqtPublish( $SrcAddr, '0402', '0000', $temperature,      $qos);
                    // $this->mqqtPublish( $SrcAddr, '0405', '0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                }

                // Xiaomi capteur Inondation
                elseif (($AttributId == 'ff01') && ($AttributSize == "0022")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Capteur Inondation)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                    $etat = substr($payload, 88, 2);

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Inondation Voltage='      .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', Inondation Etat='  .$etat);
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                    // $this->mqqtPublish( $SrcAddr, '0402', '0000', $temperature,      $qos);
                    // $this->mqqtPublish( $SrcAddr, '0405', '0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                }

                // Xiaomi capteur temperature carré V2
                elseif (($AttributId == 'ff01') && ($AttributSize == "0025")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Capteur température carré)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  Voltage='.$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', Temperature='.$temperature.', Humidity='.$humidity.', Pression='.$pression);
                    // $this->deamonlog('debug', 'ff01/25: Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'ff01/25: Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'ff01/25: Pression: '     .$pression);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0402', '01-0000', $temperature,      $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0405', '01-0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                }

                // Xiaomi bouton Aqara Wireless Switch V3 #712 (https://github.com/KiwiHC16/Abeille/issues/712)
                elseif (($AttributId == 'ff01') && ($AttributSize == "0026")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Bouton Aqara Wireless Switch V3)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Smoke Sensor
                elseif (($AttributId == 'ff01') && ($AttributSize == "0028")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Sensor Smoke)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Cube
                // Xiaomi capteur Inondation
                elseif (($AttributId == 'ff01') && ($AttributSize == "002a")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Cube)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                    // $this->mqqtPublish( $SrcAddr, '0402', '0000', $temperature,      $qos);
                    // $this->mqqtPublish( $SrcAddr, '0405', '0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                }

                // Xiaomi Vibration
                elseif (($AttributId == 'ff01') && ($AttributSize == "002e")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Vibration)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Wall Plug (Kiwi: ZNCZ02LM, rvitch: )
                elseif (($AttributId == "ff01") && (($AttributSize == "0031") || ($AttributSize == "002b") )) {
                    $logMessage = "";
                    // $this->deamonlog('debug', 'Type=8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall Plug)');
                    $logMessage .= "  Champ proprietaire Xiaomi (Wall Plug)";

                    $onOff = hexdec(substr($payload, 24 + 2 * 2, 2));

                    $puissance = unpack('f', pack('H*', substr($payload, 24 + 8 * 2, 8)));
                    $puissanceValue = $puissance[1];

                    $conso = unpack('f', pack('H*', substr($payload, 24 + 14 * 2, 8)));
                    $consoValue = $conso[1];

                    // $this->mqqtPublish($SrcAddr,$ClusterId,$AttributId,'$this->decoded as OnOff-Puissance-Conso',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0006',  '-01-0000',        $onOff,             $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'tbd',   '--puissance--',   $puissanceValue,    $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'tbd',   '--conso--',       $consoValue,        $qos);

                    $logMessage .= '  OnOff='.$onOff.', Puissance='.$puissanceValue.', Consommation='.$consoValue;
                    $this->deamonlog('debug', $logMessage);
                }

                // Xiaomi Double Relay (ref ?)
                elseif (($AttributId == "ff01") && ($AttributSize == "0044")) {
                    $FF01 = $this->decodeFF01(substr($payload, 24, strlen($payload) - 24 - 2));
                    $this->deamonlog('debug', "  Champ proprietaire Xiaomi (Relais double)");
                    $this->deamonlog('debug', "  ".json_encode($FF01));

                    $this->mqqtPublish($dest."/".$SrcAddr, '0006', '01-0000',   $FF01["Etat SW 1 Binaire"]["valueConverted"], $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '0006', '02-0000',   $FF01["Etat SW 2 Binaire"]["valueConverted"], $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, '000C', '01-0055',   $FF01["Puissance"]["valueConverted"],         $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'tbd',  '--conso--', $FF01["Consommation"]["valueConverted"],      $qos);
                }

                // Xiaomi Capteur Presence
                // Je ne vois pas ce message pour ce cateur et sur appui lateral il n envoie rien
                // Je mets un Attribut Size a XX en attendant. Le code et la il reste juste a trouver la taille de l attribut si il est envoyé.
                elseif (($AttributId == "ff01") && ($AttributSize == "00XX")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Bouton Carre)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Presence Infrarouge IR V1 / Bouton V1 Rond
                elseif (($AttributId == "ff02")) {
                    // Non decodé a ce stade
                    // $this->deamonlog("debug", "Champ 0xFF02 non $this->decode a ce stade");
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (IR V1)" );

                    $voltage        = hexdec(substr($payload, 24 +  8, 2).substr($payload, 24 + 6, 2));

                    $this->deamonlog('debug', '  SrcAddr='.$SrcAddr.', Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // ------------------------------------------------------- Philips ----------------------------------------------------------
                // Bouton Telecommande Philips Hue RWL021
                elseif (($ClusterId == "fc00")) {

                    $buttonEventTexte = array (
                                               '00' => 'appui',
                                               '01' => 'appui maintenu',
                                               '02' => 'relâche sur appui court',
                                               '03' => 'relâche sur appui long',
                                               );
                    // $this->deamonlog("debug","  Champ proprietaire Philips Hue, decodons le et envoyons a Abeille les informations ->".pack('H*', substr($payload, 24+2, (strlen($payload) - 24 - 2)) )."<-" );
                    $button = $AttributId;
                    $buttonEvent = substr($payload, 24+2, 2 );
                    $buttonDuree = hexdec(substr($payload, 24+6, 2 ));
                    $this->deamonlog("debug", "  Champ proprietaire Philips Hue: Bouton=".$button.", Event=".$buttonEvent.", EventText=".$buttonEventTexte[$buttonEvent]." et duree: ".$buttonDuree);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId."-Event", $buttonEvent);
                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId."-Duree", $buttonDuree);
                }

                // ------------------------------------------------------- Tous les autres cas ----------------------------------------------------------
                else {
                    $data = hex2bin(substr($payload, 24, (strlen($payload) - 24 - 2))); // -2 est une difference entre ZiGate et NXP Controlleur pour le LQI.
                }
            }

            if (isset($data)) {
                if ( strpos($data, "sensor_86sw2")>2 ) { $data="lumi.sensor_86sw2"; } // Verrue: getName = lumi.sensor_86sw2Un avec probablement des caractere cachés alors que lorsqu'il envoie son nom spontanement c'est lumi.sensor_86sw2 ou l inverse, je ne sais plus
                $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId, $data);
            }
        }

        // function decode8110($dest, $payload, $ln, $qos, $dummy)
        // {
            // $this->deamonlog('debug', 'Type=8110/Write Attribute Response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        function decode8120($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uint8_t>
            // <Src address : uint16_t>
            // <Endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <Attribute Enum: uint16_t> (add in v3.0f)
            // <Status: uint8_t>

            $this->deamonlog('debug', 'Type=8120/Configure Reporting response (decoded but not Processed)'
                             . ': Dest='.$dest
                             . ', SQN='              .substr($payload, 0, 2)
                             . ', SrcAddr='   .substr($payload, 2, 4)
                             . ', EndPoint='         .substr($payload, 6, 2)
                             . ', ClusterId='       .substr($payload, 8, 4)
                             . ', Attribute='        .substr($payload,12, 4)
                             . ', Status='           .substr($payload,16, 2)  );

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Report";
            $data = date("Y-m-d H:i:s")." Attribut: ".substr($payload,12, 4)." Status (00: Ok, <>0: Error): ".substr($payload,16, 2);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        // function decode8140($dest, $payload, $ln, $qos, $dummy)
        // {
            // Some changes in this message so read: https://github.com/fairecasoimeme/ZiGate/pull/90
            // $this->deamonlog('debug', 'Type=8140/Configure Reporting response (ignoré)'
                             // . ': Dest='.$dest
                             // . ', Level=0x'.substr($payload, 0, 2)
                             // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        // }

        // Codé sur la base des messages Xiaomi Inondation
        function decode8401($dest, $payload, $ln, $qos, $dummy)
        {
            // <sequence number: uint8_t>
            // <endpoint : uint8_t>
            // <cluster id: uint16_t>
            // <src address mode: uint8_t>
            // <src address: uint64_t  or uint16_t based on address mode>
            // <zone status: uint16_t>
            // <extended status: uint8_t>
            // <zone id : uint8_t>
            // <delay: data each element uint16_t>

            $this->deamonlog('debug', 'Type=8401/IAS Zone status change notification'
                             . ': Dest='.$dest
                             . ', SQN='               .substr($payload, 0, 2)
                             . ', EndPoint='          .substr($payload, 2, 2)
                             . ', ClusterId='        .substr($payload, 4, 4)
                             . ', SrcAddrMode='  .substr($payload, 8, 2)
                             . ', SrcAddr='       .substr($payload,10, 4)
                             . ', ZoneStatus='       .substr($payload,14, 4)
                             . ', ExtStatus='   .substr($payload,18, 2)
                             . ', ZoneId='           .substr($payload,20, 2)
                             . ', Delay='             .substr($payload,22, 4)  );

            $SrcAddr    = substr($payload,10, 4);
            $ClusterId  = substr($payload, 4, 4);
            $EP         = substr($payload, 2, 2);
            $AttributId = "0000";
            $data       = substr($payload,14, 4);

            // On transmettre l info sur Cluster 0500 et Cmd: 0000 (Jusqu'a present on etait sur ClusterId-AttributeId, ici ClusterId-CommandId)
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $EP.'-'.$AttributId, $data);
        }

        function decode8701($dest, $payload, $ln, $qos, $dummy)
        {
            // NWK Code Table Chap 10.2.3 from JN-UG-3113
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            global $allErrorCode;

            // $status = substr($payload, 0, 2);
            // $nwkStatus = substr($payload, 2, 2);
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            $nwkStatus = substr($payload, 0, 2);
            $status = substr($payload, 2, 2);

            $this->deamonlog('debug', 'Type=8701/Route Discovery Confirm (decoded but Not Processed)'
                             . ': Dest='.$dest
                             . ', MACStatus='.$status.' ('.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
                             . ', NwkStatus='.$nwkStatus.' ('.$allErrorCode[$nwkStatus][0].'->'.$allErrorCode[$nwkStatus][1].')'  );
        }

        function decode8702($dest, $payload, $ln, $qos, $dummy)
        {
            global $allErrorCode;

            $status = substr($payload, 0, 2);

            $this->deamonlog('debug', 'Type=8702/APS Data Confirm Fail'
                             . ': Dest='.$dest
                             . ', Status='.$status.' ('.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
                             . ', SrcEndpoint='.substr($payload, 2, 2)
                             . ', DestEndpoint='.substr($payload, 4, 2)
                             . ', DestMode='.substr($payload, 6, 2)
                             . ', DestAddr='.substr($payload, 8, 4)
                             . ', SQN='.substr($payload, 12, 2)   );

            // type; 8702; (APS Data Confirm Fail)(decoded but Not Processed); Status : d4; Source Endpoint : 01; Destination Endpoint : 03; Destination Mode : 02; Destination Address : c3cd; SQN: : 00

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "8702";
            $data       = substr($payload, 8, 4);

            if ( Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' ) ) $name = Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' )->getHumanName(true);
            // message::add("Abeille","L'équipement ".$name." (".$data.") ne peut être joint." );

            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8806($dest, $payload, $ln, $qos, $dummy)
        {
            // Command 0x0807 Get Tx Power doesn't need any parameters.
            // If command is handled successfully response will be first status(0x8000) with success status and after that Get Tx Power Response(0x8807).
            // 0x8807 has only single parameter which is uint8 power. If 0x0807 fails then response is going to be only status(0x8000) with status 1.
            // Standard power modules of JN516X(Except JN5169) modules have only 4 possible power levels (Table 5 JN-UG-3024 v2.6). These levels are based on some kind of hardware registry so there is no way to change them in firmware. In ZiGate's case this means:
            // Set/get tx value    Mapped value (dBM)
            // 0 to 31              0
            // 32 to 39             -32
            // 40 to 51             -20
            // 52 to 63             -9

            // <Power: uint8_t>

            $this->deamonlog('debug', 'Type=8806/Set TX Power Answer'
                             . ': Dest='.$dest
                             . ', Power='               .$payload
                             );
            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "Power";
            $data       = substr($payload, 0, 2);

            // On transmettre l info sur la ruche
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8807($dest, $payload, $ln, $qos, $dummy)
        {
            // Command 0x0807 Get Tx Power doesn't need any parameters.
            // If command is handled successfully response will be first status(0x8000) with success status and after that Get Tx Power Response(0x8807).
            // 0x8807 has only single parameter which is uint8 power. If 0x0807 fails then response is going to be only status(0x8000) with status 1.
            // Standard power modules of JN516X(Except JN5169) modules have only 4 possible power levels (Table 5 JN-UG-3024 v2.6). These levels are based on some kind of hardware registry so there is no way to change them in firmware. In ZiGate's case this means:
            // Set/get tx value    Mapped value (dBM)
            // 0 to 31              0
            // 32 to 39             -32
            // 40 to 51             -20
            // 52 to 63             -9

            // <Power: uint8_t>

            $this->deamonlog('debug', 'Type=8807/Get Tx Power'
                             . ': Dest='.$dest
                             . ', Power='               .$payload
                             );

            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "Power";
            $data       = substr($payload, 0, 2);

            // On transmettre l info sur la ruche
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        // ***********************************************************************************************
        // Gestion des annonces
        // ***********************************************************************************************

        function getNE( $short )
        {
            $getStates = array( 'getEtat', 'getLevel', 'getColorX', 'getColorY', 'getManufacturerName', 'getSWBuild', 'get Battery'  );

            $abeille = Abeille::byLogicalId( $short,'Abeille');

            if ( $abeille ) {
                $arr = array(1, 2);
                foreach ($arr as &$value) {
                    foreach ( $getStates as $getState ) {
                        $cmd = $abeille->getCmd('action', $getState);
                        if ( $cmd ) {
                            $this->deamonlog('debug', 'Type=fct; getNE cmd: '.$getState);
                            $cmd->execCmd();
                            // sleep(0.5);
                        }
                    }
                }
            }
        }

        function configureNE( $short )
        {
            $this->deamonlog('debug', 'Type=fct; ===> Configure NE Start');

            $commandeConfiguration = array( 'BindShortToZigateBatterie',            'setReportBatterie', 'spiritSetReportBatterie',
                                           'BindToZigateEtat',                      'setReportEtat',
                                           'BindToZigateLevel',                     'setReportLevel',
                                           'BindToZigateButton',
                                           'spiritTemperatureBindShortToZigate',    'spiritTemperatureSetReport',
                                           'BindToZigateIlluminance',               'setReportIlluminance',
                                           'BindToZigateOccupancy',                 'setReportOccupancy',
                                           'BindToZigateTemperature',               'setReportTemperature',
                                           'BindToZigatePuissanceLegrand',          'setReportPuissanceLegrand',
                                           'BindShortToSmokeHeiman',                'setReportSmokeHeiman',
                                           'LivoloSwitchTrick1',                    'LivoloSwitchTrick2', 'LivoloSwitchTrick3', 'LivoloSwitchTrick4',
                                           );

            $abeille = Abeille::byLogicalId( $short,'Abeille');

            if ( $abeille) {
                $arr = array(1, 2);
                foreach ($arr as &$value) {
                    foreach ( $commandeConfiguration as $config ) {
                        $cmd = $abeille->getCmd('action', $config);
                        if ( $cmd ) {
                            $this->deamonlog('debug', 'Type=fct; ===> Configure NE cmd: '.$config);
                            $cmd->execCmd();
                            //sleep(0.5);
                        }
                        else {
                            $this->deamonlog('debug', 'Type=fct; ===> Configure NE '.$config.': Cmd not found, probably not an issue, probably should not do it');
                        }
                    }
                }
            }

            $this->deamonlog('debug', 'Type=fct; ===> Configure NE End');
        }

        function processActionQueue() {
            if ( !($this->actionQueue) ) return;
            if ( count($this->actionQueue) < 1 ) return;

            foreach ( $this->actionQueue as $key=>$action ) {
                if ( $action['when'] < time() ) {
                    if ( method_exists($this, $action['what']) ) {
                        if ( $this->debug['processActionQueue'] ) { $this->deamonlog('debug', 'Type=fct; processActionQueue, action: '.json_encode($action)); }
                        $fct = $action['what'];
                        if ( isset($action['parm0']) ) {
                            $this->$fct($action['parm0'],$action['parm1'],$action['parm2'],$action['parm3']);
                        }
                        else {
                            $this->$fct($action['addr']);
                        }
                        unset($this->actionQueue[$key]);
                    }
                }
            }
        }

    } // class AbeilleParser

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleParser.php /dev/ttyUSB0 127.0.0.1 1883 jeedom jeedom 0 debug

    try {
        // On crée l objet AbeilleParser
        $AbeilleParser = new AbeilleParser("AbeilleParser");
        $NE = array(); // Ne doit exister que le temps de la creation de l objet. On collecte les info du message annonce et on envoie les info a jeedom et apres on vide la tableau.
        $LQI = array();
        $clusterTab = Tools::getJSonConfigFiles("zigateClusters.json");

        $queueKeySerieToParser   = msg_get_queue(queueKeySerieToParser);
        $max_msg_size = 512;
        $msg_type = NULL;

        while (true) {

            if (msg_receive( $queueKeySerieToParser, 0, $msg_type, $max_msg_size, $dataJson, false, MSG_IPC_NOWAIT)) {
                // $AbeilleParser->deamonlog( 'debug', "Message pulled from queue queueKeySerieToParser: ".json_encode($data) );

                $data = json_decode( $dataJson );
                $AbeilleParser->protocolDatas( $data->dest, $data->trame, 0, $clusterTab, $LQI );
            }
            // $AbeilleParser->processAnnonce($NE);
            // $AbeilleParser->cleanUpNE($NE);
            $AbeilleParser->processActionQueue();

            time_nanosleep( 0, 10000000 ); // 1/100s
        }

        unset($AbeilleParser);

    }
    catch (Exception $e) {
        $AbeilleParser->deamonlog( 'debug', 'error: '. json_encode($e->getMessage()));
    }

    $AbeilleParser->deamonlog('info', 'Fin du script');
?>
