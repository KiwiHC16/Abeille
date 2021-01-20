<?php

    include_once __DIR__.'/../../core/class/AbeilleMsg.php';

    /*
     * AbeilleParserClass
     *
     * - Read data from FIFO file (FIFO populated by AbeilleSerialRead)
     * - translate them into a understandable message,
     * - then publish them to Abeille
     *
     * @param argv
     *      argv contient le niveau de log. typical call: /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDeamon/lib/../AbeilleParser.php debug
     *  @return
     *
     */
    class AbeilleParser  {
        public $queueKeyParserToAbeille = null;
        public $queueKeyParserToCmd = null;

        public $debug = array(
                              "AbeilleParserClass"      => 0,  // Mise en place des class

                              "8000"                    => 1, // Status
                              "8002"                    => 1, // Unknown messages to Zigate. Mode Hybrid.
                              "8009"                    => 1, // Get Network Status
                              "8010"                    => 1, // Zigate Version
                              "8011"                    => 1, // ACK DATA
                              "8014"                    => 1, // Permit Join
                              "8024"                    => 1, // Network joined-forme
                              "8043"                    => 1, // Simple descriptor response
                              "8045"                    => 1, //
                              "8048"                    => 1, //
                              "8701"                    => 1, //

                              "cleanUpNE"               => 1,
                              "configureNE"             => 1,
                              "getNE"                   => 1,
                              "processActionQueue"      => 1,
                              "processAnnonce"          => 1,
                              "processAnnonceStageChg"  => 1,

                              );

        // ZigBee Cluster Library - Document 075123r02ZB - Page 79 - Table 2.15
        // Data Type -> Description, # octets
        public $zbDataTypes = array(
                                 '00' => array( 'Null', 0 ),
                                 // 01-07: reserved
                                 '08' => array( 'General Data', 1),
                                 '09' => array( 'General Data', 2),
                                 '0A' => array( 'General Data', 3),
                                 '0B' => array( 'General Data', 4),
                                 '0C' => array( 'General Data', 5),
                                 '0D' => array( 'General Data', 6),
                                 '0E' => array( 'General Data', 7),
                                 '0F' => array( 'General Data', 8),

                                 '10' => array( 'Bool', 1 ), // Boolean
                                 // 0x11-0x17 Reserved
                                 '18' => array( 'Bitmap', 1 ),
                                 '19' => array( 'Bitmap', 2 ),
                                 '1A' => array( 'Bitmap', 3 ),
                                 '1B' => array( 'Bitmap', 4 ),
                                 '1C' => array( 'Bitmap', 5 ),
                                 '1D' => array( 'Bitmap', 6 ),
                                 '1E' => array( 'Bitmap', 7 ),
                                 '1F' => array( 'Bitmap', 8 ),

                                 '20' => array( 'Uint8',  1 ), // Unsigned 8-bit int
                                 '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int
                                 '22' => array( 'Uint24', 3 ), // Unsigned 24-bit int
                                 '23' => array( 'Uint32', 4 ), // Unsigned 32-bit int
                                 '24' => array( 'Uint40', 5 ), // Unsigned 40-bit int
                                 '25' => array( 'Uint48', 6 ), // Unsigned 48-bit int
                                 '26' => array( 'Uint56', 7 ), // Unsigned 56-bit int
                                 '27' => array( 'Uint64', 8 ), // Unsigned 64-bit int

                                 '28' => array( 'Int8',  1 ), // Signed 8-bit int
                                 '29' => array( 'Int16', 2 ), // Signed 16-bit int
                                 '2A' => array( 'Int24', 3 ), // Signed 24-bit int
                                 '2B' => array( 'Int32', 4 ), // Signed 32-bit int
                                 '2C' => array( 'Int40', 5 ), // Signed 40-bit int
                                 '2D' => array( 'Int48', 6 ), // Signed 48-bit int
                                 '2E' => array( 'Int56', 7 ), // Signed 56-bit int
                                 '2F' => array( 'Int64', 8 ), // Signed 64-bit int

                                 '30' => array( 'Enumeration', 1 ),
                                 '31' => array( 'Enumeration', 2 ),
                                 // 0x32-0x37 Reserved

                                 '38' => array( 'SemiPrecision',   2 ),
                                 '39' => array( 'Single', 4 ), // Single precision
                                 '3A' => array( 'DoublePrecision', 8 ),
                                 // 0x3b-0x3f
                                 // 0x40 Reserved
                                 '41' => array( 'String - Octet string',             'Defined in first octet' ),
                                 '42' => array( 'String - Charactere string',        'Defined in first octet' ),
                                 '43' => array( 'String - Long octet string',        'Defined in first two octets' ),
                                 '44' => array( 'String - Long charactere string',   'Defined in first two octets' ),
                                 // 0x45-0x47 Reserved
                                 '48' => array( 'Ordered sequence - Array',          '2+sum of lengths of contents' ),
                                 // 0x49-0x4b Reserved
                                 '4C' => array( 'Ordered sequence - Structure',      '2+sum of lengths of contents' ),
                                 // 0x4d-0x4f Reserved
                                 '50' => array( 'Collection - Set',      'Sum of lengths of contents' ),
                                 '51' => array( 'Collection - Bag',      'Sum of lengths of contents' ),
                                 //0x52-0x57 Reserved
                                 // 0x58-0xdf Reserved
                                 'E0' => array( 'Time - Time of day', 4 ),
                                 'E1' => array( 'Time - Date', 4 ),
                                 'E2' => array( 'Time - UTCTime', 4 ),
                                 // 0xe3 - 0xe7 Reserved
                                 'E8' => array( 'Identifier - Cluster ID', 2 ),
                                 'E9' => array( 'Identifier - Attribute ID', 2 ),
                                 'EA' => array( 'Identifier - BACnet OID', 4 ),
                                 // 0xeb-0xef Reserved
                                 'F0' => array( 'Miscellaneous - IEEE address', 8 ),
                                 'F1' => array( 'Miscellaneous - 128 bit security key', 16 ),
                                 // 0xF2-0xFe Reserved
                                 'FF' => array( 'Unknown', 0 ),
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

            /* Configuring log library to use 'logMessage()' */
            logSetConf("AbeilleParser.log");

            if ($this->debug["AbeilleParserClass"]) $this->deamonlog("debug", "AbeilleParser constructor");
            $this->parameters_info = AbeilleTools::getParameters();

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

        function mqqtPublishFct($SrcAddr, $fct, $data)
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

        /*
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
        } */

        /* Send message to 'AbeilleLQI'.
           Returns: 0=OK, -1=ERROR */
        function msgToLQICollector($SrcAddr, $NTableEntries, $NTableListCount, $StartIndex, $NList)
        {
            $msg = array(
                'Type' => '804E',
                'SrcAddr' => $SrcAddr,
                'TableEntries' => $NTableEntries,
                'TableListCount' => $NTableListCount,
                'StartIndex' => $StartIndex,
                'List' => $NList
            );

            if (msg_send($this->queueKeyParserToLQI, 1, json_encode($msg), FALSE, FALSE, $error_code) == TRUE) {
                return 0;
            }

            $this->deamonlog("error", "msgToLQICollector(): Impossible d'envoyer le msg vers AbeilleLQI (err ".$error_code.")");
            return -1;
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

        // Fonction dupliquée dans Abeille.
        public function volt2pourcent( $voltage ) {
            $max = 3.135;
            $min = 2.8;
            if ( $voltage/1000 > $max ) {
                $this->deamonlog( 'debug', 'Voltage remonte par le device a plus de '.$max.'V. Je retourne 100%.' );
                return 100;
            }
            if ( $voltage/1000 < $min ) {
                $this->deamonlog( 'debug', 'Voltage remonte par le device a moins de '.$min.'V. Je retourne 0%.' );
                return 0;
            }
            return round(100-((($max-($voltage/1000))/($max-$min))*100));
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
                '03' => "Device Temperature", // Based on #1344
                '05' => "tbd2",             // Type Associé 21 donc 16bitUint
                '07' => "tbd3",             // Type associé 27 donc 64bitUint
                '08' => "tbd4",             // ??
                '09' => "tbd5",             // ??
                '0b' => "tbd0b",            // Type associé 20 donc 8bitUint
                '64' => "Etat SW 1 Binaire", // Based on Aqara Double Relay (mais j ai aussi un 64 pour la temperature (Temp carré V2), Etat On/Off Prise Xiaomi
                '65' => "Etat SW 2 Binaire", // Based on Aqara Double Relay (mais j ai aussi un 65 pour Humidity Temp carré V2)
                '66' => "Pression",          // Based on Temperature Capteur V2
                '6E' => "Etat SW 1 Analog",  // Based on Aqara Double Relay
                '6F' => "Etat SW 2 Analog",  // Based on Aqara Double Relay
                '94' => "tbd6",             // Type associé ??
                '95' => "Consommation",     // Based on Prise Xiaomi
                '96' => "Voltage",          // Based on #1344
                '97' => "Current",          // Based on #1344
                '98' => "Puissance",        // Based on Aqara Double Relay nad #1344
                '9A' => "tbd9A",            // Type associé 20 donc une donnée 8bitUint
                '9B' => "tbd11",            // Type associé 10 donc une donnée binaire
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

         /**
         * protocolDatas: Treat messages received from AbeilleSerialRead, check CRC, and if Ok execute proper decode function.
         *
         * @param dest          Zigate sending the message
         * @param datas         Message sent by the zigate
         * @param clusterTab    Table of the Cluster definition
         * @param LQI           Output: LQI from the message received
         *
         * @return Status       0=OK, -1=ERROR
         */
        function protocolDatas($dest, $datas, $clusterTab, &$LQI) {
            // Reminder: message format received from Zigate.
            // 01/start & 03/end are already removed.
            //   00-03 : Msg Type (2 bytes)
            //   04-07 : Length (2 bytes) => optional payload + LQI
            //   08-09 : crc (1 byte)
            //   10... : Optional data / payload
            //   Last  : LQI (1 byte)

            $crctmp = 0;

            $length = strlen($datas);
            if ($length < 10) { return -1; } // Too short. Min=MsgType+Len+Crc

            //type de message
            $type = $datas[0].$datas[1].$datas[2].$datas[3];
            $crctmp = $crctmp ^ hexdec($datas[0].$datas[1]) ^ hexdec($datas[2].$datas[3]);

            // Taille message
            // see github: AbeilleParser Erreur CRC #1562
            $ln = $datas[4].$datas[5].$datas[6].$datas[7];
            $ln = hexdec($ln);
            if ( $ln > 150 ) {
                $this->deamonlog('error', 'Le message recu est beaucoup trop long. On ne le process pas.');
                return 0;
            }
            $crctmp = $crctmp ^ hexdec($datas[4].$datas[5]) ^ hexdec($datas[6].$datas[7]);

            //acquisition du CRC
            $crc = strtolower($datas[8].$datas[9]);

            /* Payload.
               Payload size is 'Length' - 1 (excluding LQI) but CRC takes LQI into account.
               See https://github.com/fairecasoimeme/ZiGate/issues/325# */
            $payloadSize = ($length - 12) / 2; // Real payload size in Bytes. Removing MsgType+Len+Crc+LQI
            if ($payloadSize != ($ln - 1))
                $this->deamonlog('debug', 'WARNING: Length ('.$ln.') != real payload + LQI size ('.$payloadSize.')');
            $payload = substr($datas, 10, $payloadSize * 2);
            // $this->deamonlog('debug', 'type='.$type.', payload='.$payload);
            for ($i = 0; $i < $ln; $i++) {
                // $payload .= $datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)];
                $crctmp = $crctmp ^ hexdec($datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)]);
            }

            // LQI
            $quality = $datas[10 + ($i * 2) - 2].$datas[10 + ($i * 2) - 1];
            $quality = hexdec( $quality );

            //verification du CRC
            if (hexdec($crc) != $crctmp) {
                $this->deamonlog('error', 'ERREUR CRC: calc=0x'.dechex($crctmp).', att=0x'.$crc.'. Message ignoré: '.substr($datas, 0, 12).'...'.substr($datas, -2, 2));
                $this->deamonlog('debug', 'Mess ignoré='.$datas);
                return -1;
            }

            //Traitement PAYLOAD
            $param1 = "";
            if (($type == "8003") || ($type == "8043")) $param1 = $clusterTab;
            // if ($type == "804E") $param1 = $LQI; // Tcharp38: no longer used
            if ($type == "8102") $param1 = $quality;

            $fct = "decode".$type;
            // $this->deamonlog('debug','Calling function: '.$fct);

            //  if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE', $dest), 'Abeille', 'none' ) == $ExtendedAddress ) {
            //               config::save( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 1,   'Abeille');

            $commandAcceptedUntilZigateIdentified = array( "decode0300", "decode0208", "decode8009", "decode8024", "decode8000" );

            // On vérifie que l on est sur la bonne zigate.
            if ( config::byKey( str_replace('Abeille', 'AbeilleIEEE_Ok', $dest), 'Abeille', '0', 1 ) == 0 ) {
                if ( !in_array($fct, $commandAcceptedUntilZigateIdentified) ) {
                    return 0;
                }
            }

            if ( method_exists($this, $fct) ) {
                $this->$fct($dest, $payload, $ln, 0, $param1); }
            else {
                $msgName = zgGetMsgByType($type);
                $this->deamonlog('debug', $dest.', Type='.$type.'/'.$msgName.', ignoré (non supporté).');
            }

            return 0;
        }

        /*--------------------------------------------------------------------------------------------------*/
        /* $this->decode functions
         /*--------------------------------------------------------------------------------------------------*/

        /**
         * 004D/Device announce
         * This method process a Zigbeee annonce message coming from a device
         *  Will first decode it.
         *  Send information to Abeille to update Jeedom
         *  And start the device identification by requesting EP and IEEE to the device.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ? Probably not needed anymore. Historical param from mosquitto broker needs
         * @param $dummy    ?
         *
         * @return          Does return anything as all action are triggered by sending messages in queues
         */
        function decode004d($dest, $payload, $ln, $qos, $dummy)
        {
            /* < short address: uint16_t>
               < IEEE address: uint64_t>
               < MAC capability: uint8_t> MAC capability
                    Bit 0 - Alternate PAN Coordinator    => 1 no
                    Bit 1 - Device Type                  => 2 yes
                    Bit 2 - Power source                 => 4 yes
                    Bit 3 - Receiver On when Idle        => 8 yes
                    Bit 4 - Reserved                     => 16 no
                    Bit 5 - Reserved                     => 32 no
                    Bit 6 - Security capability          => 64 no
                    Bit 7 - Allocate Address             => 128 no
               <Rejoin information : uint8_t> (from v3.1b) => OPTIONAL !!
                    If len(payload) == 22 ==> No Join Flag
                    If len(payload) == 24 ==> Join Flag
                    When receiving a Device Annoucement the Rejoin Flag can give us some information
                    0x00 The device was not on the network.
                         Most-likely it has been reset, and all Unbind, Bind , Report, must be redone
                    0x01 The device was on the Network, but change its route
                         the device was not reset
                    0x02, 0x03 The device was on the network and coming back.
                         Here we can assumed the device was not reset. */
            /* See https://github.com/fairecasoimeme/ZiGate/issues/325# */

            $Addr       = substr($payload, 0, 4);
            $IEEE       = substr($payload, 4, 16);
            $MACCapa    = substr($payload, 20, 2);
            if (strlen($payload) > 22)
                $Rejoin = substr($payload, 22, 2);
            else
                $Rejoin = "";

            $msgDecoded = '004d/Device announce'.', Addr='.$Addr.', ExtAddr='.$IEEE.', MACCapa='.$MACCapa;
            if ($Rejoin != "") $msgDecoded .= ', Rejoin='.$Rejoin;
            $msgDecoded .= ', [Modelisation]';
            $this->deamonlog('debug', $dest.', Type='.$msgDecoded);

            // Envoie de la IEEE a Jeedom qui le processera dans la cmd de l objet si celui ci existe deja, sinon sera drop
            // $this->mqqtPublish($dest."/".$Addr, "IEEE", "Addr", $IEEE);

            // Tcharp38: Why ? $this->mqqtPublishFct($dest."/"."Ruche", "enable", $IEEE);
            $this->mqqtPublishFct($dest."/".$Addr, "enable", $IEEE);

            // Rafraichi le champ Ruche, JoinLeave (on garde un historique)
            $this->mqqtPublish($dest."/"."Ruche", "joinLeave", "IEEE", "Annonce->".$IEEE);

            // Si 02 = Rejoin alors on doit le connaitre on ne va pas faire de recherche
            if ($Rejoin == "02") return;

            if ( config::byKey( 'blocageTraitementAnnonce', 'Abeille', 'Non', 1 ) == "Oui" ) return;

            if ( Abeille::checkInclusionStatus( $dest ) != "01" ) return;

            // If this IEEE is already in Abeille we stop the process of creation in Abeille, but we send IEEE and Addr to update Addr if needed.
            if (Abeille::getEqFromIEEE($IEEE)) {
                $this->actionQueue[] = array( 'when'=>time()+5, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$IEEE );
                return;
            }

            $agressif = config::byKey( 'agressifTraitementAnnonce', 'Abeille', '4', 1 );

            for ($i = 0; $i < $agressif; $i++) {
                $this->mqqtPublishFctToCmd("TempoCmd".$dest."/Ruche/ActiveEndPoint&time=".(time()+($i*2)), "address=".$Addr );
                $this->actionQueue[] = array( 'when'=>time()+($i*2)+5, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$Addr, 'parm1'=>"IEEE",    'parm2'=>"Addr",    'parm3'=>$IEEE );
                $this->actionQueue[] = array( 'when'=>time()+($i*2)+5, 'what'=>'mqqtPublish', 'parm0'=>$dest."/".$Addr, 'parm1'=>"MACCapa", 'parm2'=>"MACCapa", 'parm3'=>$MACCapa );
            }
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

            $msgDecoded = "0100/?";
            $this->deamonlog('debug', $dest.', Type='.$msgDecoded);

            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        // PDM Management

        function decode0302($dest, $payload, $ln, $qos, $dummy)
        {
            // E_SL_MSG_PDM_LOADED = 0x0302
            // https://zigate.fr/documentation/deplacer-le-pdm-de-la-zigate/
            $this->deamonlog('debug', $dest.', Type=0302/E_SL_MSG_PDM_LOADED');
        }

        function decode0300($dest, $payload, $ln, $qos, $dummy)
        {
            // "0300 0001DCDE"
            // E_SL_MSG_PDM_HOST_AVAILABLE = 0x0300
            $this->deamonlog('debug', $dest.', Type=0300/E_SL_MSG_PDM_HOST_AVAILABLE : PDM Host Available ?');

            $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/PDM", "req=E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE");
        }

        function decode0208($dest, $payload, $ln, $qos, $dummy)
        {
            // "0208 0003 19001000"
            // E_SL_MSG_PDM_EXISTENCE_REQUEST = 0x0208

            $id = substr( $payload, 0  , 4);

            $this->deamonlog('debug', $dest.', Type=0208/E_SL_MSG_PDM_EXISTENCE_REQUEST : PDM Exist for id : '.$id.' ?');

            $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/PDM", "req=E_SL_MSG_PDM_EXISTENCE_RESPONSE&recordId=".$id);
        }

        // Zigate Status
        function decode8000($dest, $payload, $ln, $qos, $dummy)
        {
            $status     = substr($payload, 0, 2);
            $SQN        = substr($payload, 2, 2);
            $PacketType = substr($payload, 4, 4);

            if ($this->debug['8000']) { // Tcharp38: Should not be disabled. Might be useful to see error in user logs. Voir flag a prendre en compte quand en mode dev et a ne pas prendre si pas mode dev. cf issue en cours.
                $this->deamonlog('debug', $dest.', Type=8000/Status'
                                 . ', Status='.$status.'/'.zgGet8000Status($status)
                                 . ', SQN='.$SQN
                                 . ', PacketType='.$PacketType);
            }

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            // $SrcAddr    = "Ruche";
            // $ClusterId  = "Zigate";
            // $AttributId = "8000";
            // $data       = $this->displayStatus($status);
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

            if ($PacketType == "0002") {
                if ( $status == "00" ) {
                    message::add("Abeille","Le mode de fonctionnement de la zigate a bien été modifié.","" );
                }
                else {
                    message::add("Abeille","Durant la demande de modification du mode de fonctionnement de la zigate, une erreur a été détectée.","" );
                }
            }
        }

        /**
         * Data indication
         *
         * This method process a Zigbeee message coming from a device which is unknown from zigate, so Abeille as to deal with it.
         *  Will first decode it.
         *  Take action base on message contain
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ?
         * @param $dummy    ?
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8002($dest, $payload, $ln, $qos, $dummy) {
            // ZigBee Specification: 2.4.4.3.3   Mgmt_Rtg_rsp

            // 3 bits (status) + 1 bit memory constrained concentrator + 1 bit many-to-one + 1 bit Route Record required + 2 bit reserved
            // Il faudrait faire un decodage bit a bit mais pour l instant je prends les plus courant et on verra si besoin.
            $statusDecode = array(
                                  0x00 => "Active",
                                  0x01 => "Dicovery_Underway",
                                  0x02 => "Discovery_Failed",
                                  0x03 => "Inactive",
                                  0x04 => "Validation_Underway", // Got that if interrogate the Zigate
                                  0x05 => "Reserved",
                                  0x06 => "Reserved",
                                  0x07 => "Reserved",
                                  0x10 => " + Many To One", // 0x10 -> 1 0 000 bin -> Active + no constrain + Many To One + no route required
                                  );

            // Decode first based on https://zigate.fr/documentation/commandes-zigate/
            $status                 = substr($payload, 0, 2);
            $profile                = substr($payload, 2, 4);
            $cluster                = substr($payload, 6, 4);
            $srcEndPoint            = substr($payload,10, 2);
            $destEndPoint           = substr($payload,12, 2);
            $sourceAddressMode      = substr($payload,14, 2);
            $srcAddress             = substr($payload,16, 4); if ( $srcAddress == "0000" ) $srcAddress = "Ruche";
            $destinationAddressMode = substr($payload,20, 2);
            $dstAddress             = substr($payload,22, 4); if ( $dstAddress == "0000" ) $dstAddress = "Ruche";
            // $payload              // Will decode later depending on the message

            $baseLog = "status: ".$status." profile:".$profile." cluster:".$cluster." srcEndPoint:".$srcEndPoint." destEndPoint:".$destEndPoint." sourceAddressMode:".$sourceAddressMode." srcAddress:".$srcAddress." destinationAddressMode:".$destinationAddressMode." dstAddress:".$dstAddress;

            // Routing Table Response
            if (($profile == "0000") && ($cluster == "8032")) {

                $SQN                    = substr($payload,26, 2);
                $status                 = substr($payload,28, 2);
                $tableSize              = hexdec(substr($payload,30, 2));
                $index                  = hexdec(substr($payload,32, 2));
                $tableCount             = hexdec(substr($payload,34, 2));

                if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Routing Table Response'
                                . $baseLog
                                . ', SQN='.$SQN
                                . ', status='.$status
                                . ', tableSize='.$tableSize
                                . ', index='.$index
                                . ', tableCount='.$tableCount
                                );

                $routingTable = array();

                for ($i = $index; $i < $index+$tableSize; $i++) {

                    $addressDest=substr($payload,36+($i*10), 4);

                    $statusRouting = substr($payload,36+($i*10)+4,2);
                    $statusDecoded = $statusDecode[ base_convert( $statusRouting, 16, 2) &  7 ];
                    if (base_convert($statusRouting, 16, 10)>=0x10) $statusDecoded .= $statusDecode[ base_convert($statusRouting, 16, 2) & 0x10 ];

                    $nextHop=substr($payload,36+($i*10)+4+2,4);

                    $this->deamonlog('debug', '    address='.$addressDest.' status='.$statusDecoded.'('.$statusRouting.') Next Hop='.$nextHop );

                    if ( (base_convert( $statusRouting, 16, 2) &  7) == "00" ) {
                        $routingTable[] = array( $addressDest => $nextHop );
                    }
                }

                // if ( $srcAddress == "Ruche" ) return; // Verrue car si j interroge l alarme Heiman, je ne vois pas a tous les coups la reponse sur la radio et le message recu par Abeille vient d'abeille !!!

                $abeille = Abeille::byLogicalId( $dest.'/'.$srcAddress, 'Abeille');
                if ( $abeille ) {
                    $abeille->setConfiguration('routingTable', json_encode($routingTable) );
                    $abeille->save();
                }
                else {
                    $this->deamonlog('debug', '    abeille not found !!!');
                }

                return;
            }
            
            // Boutons lateraux de la telecommande
            if ( ($profile == "0104") && ($cluster == "0005") ) {
                $frameCtrlField = substr($payload,26, 2);
                if ( $frameCtrlField=='05' ) {
                    $Manufacturer   = substr($payload,30, 2).substr($payload,28, 2);
                    if ( $Manufacturer=='117C' ) {

                        $SQN                    = substr($payload,32, 2);
                        $cmd                    = substr($payload,34, 2); 
                        if ( $cmd != "07" ) { 
                            if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Message can t be decoded. Looks like Telecommande Ikea Ronde but not completely.');
                            return;
                        }
                        $remainingData          = substr($payload,36, 8);
                        $value                  = substr($payload,36, 2);

                        if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Telecommande Ikea Ronde'
                                        . $baseLog
                                        . ', frameCtrlField='.$frameCtrlField
                                        . ', Manufacturer='.$Manufacturer
                                        . ', SQN='.$SQN
                                        . ', cmd='.$cmd
                                        . ', value='.$value
                                        );

                        $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$srcEndPoint, '0000', $value );
                        return;    
                    }
                }
            }

            // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
            if ( ($profile == "0104") && ($cluster == "0006") ) {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd != "FD" ) return;
                $value                  = substr($payload,32, 2);

                if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Interrupteur sur pile TS0043 bouton'
                                 . $baseLog
                                 . ', frameCtrlField='.$frameCtrlField
                                 . ', SQN='.$SQN
                                 . ', cmd='.$cmd
                                 . ', value='.$value
                                 );

                $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$srcEndPoint, '0000', $value );
                return;
            }

            //
            if ( ($profile == "0104") && ($cluster == "0008") ) {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd != "FD" ) return;
                $value                  = substr($payload,32, 2);

                if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - (decoded but not processed) '
                                . $baseLog
                                . ', frameCtrlField='.$frameCtrlField
                                 . ', SQN='.$SQN
                                 . ', cmd='.$cmd
                                 . ', value='.$value
                                );

                $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$srcEndPoint, '0000', $value );
                return;
            }

            if ( ($profile == "0104") && ($cluster == "000A") ) {
                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2);

                if ($cmd == '00') {
                    $attributTime                  = substr($payload,34, 2) . substr($payload,32, 2);
                    $attributTimeZone              = substr($payload,38, 2) . substr($payload,36, 2);
                    if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Time Request - (decoded but not processed) '
                                    . $baseLog
                                    . ', frameCtrlField='.$frameCtrlField
                                    . ', SQN='.$SQN
                                    . ', cmd='.$cmd
                                    . ', attributTime='.$attributTime
                                    . ', attributTimeZone='.$attributTimeZone
                                    );

                    // Here we should reply to the device with the time. I though this Time Cluster was implemented in the zigate....
                    return;
                }
            }

            // Remontée puissance prise TS0121 Issue: #1288
            if ( ($profile == "0104") && ($cluster == "0702") ) {
                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd == "0A" ) $cmd = "0A - report attribut";
                $attribute              = substr($payload,34, 2).substr($payload,32, 2);
                $dataType               = substr($payload,36, 2);

                // Remontée puissance prise TS0121 Issue: #1288
                if ( ($attribute == '0000') && ($dataType==25) ) {
                    // '25' => array( 'Uint48', 6 ), // Unsigned 48-bit int
                    $value = substr($payload,48, 2).substr($payload,46, 2).substr($payload,44, 2).substr($payload,42, 2).substr($payload,40, 2).substr($payload,38, 2);
                    if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée puissance prise TS0121 '
                                    . $baseLog
                                    . ', frameCtrlField='.$frameCtrlField
                                    . ', SQN='.$SQN
                                    . ', cmd='.$cmd
                                    . ', attribute='.$attribute
                                    . ', dataType='.$dataType
                                    . ', value='.$value.' - '.hexdec($value)
                                    );

                    $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );
                    return;
                }
            }

            // Remontée puissance module Legrand 20AX / prise Blitzwolf BW-SHP13 #1231
            if ( ($profile == "0104") && ($cluster == "0B04") ) {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2);

                if ( $cmd == "01" ) $cmdTxt = "01 - read attribut response ";
                if ( $cmd == "0A" ) $cmdTxt = "0A - report attribut ";


                if ( $cmd == '01') {
                    $attribute  = substr($payload,34, 2).substr($payload,32, 2);
                    $status     = substr($payload,36, 2);
                    $dataType   = substr($payload,38, 2);

                    if ($status!='00') {
                        if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - le status est erroné ne process pas la commande');
                        return;
                    }

                    // example: Remontée V prise Blitzwolf BW-SHP13
                    if (($attribute == '0505') && ($dataType == '21') ) {
                        // '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int
                        $value = substr($payload,42, 2).substr($payload,40, 2);

                        if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée V Blitzwolf '
                            . $baseLog
                            . ', frameCtrlField='.$frameCtrlField
                            . ', SQN='.$SQN
                            . ', cmd='.$cmdTxt
                            . ', attribute='.$attribute
                            . ', dataType='.$dataType
                            . ', value='.$value.' - '.hexdec($value)
                            );

                        $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );

                        return;
                    }

                    // example: Remontée A prise Blitzwolf BW-SHP13
                    if (($attribute == '0508') && ($dataType == '21') ) {
                        // '21' => array( 'Uint16', 2 ), // Unsigned 16-bit int
                        $value = substr($payload,42, 2).substr($payload,40, 2);

                        if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée A Blitzwolf '
                            . $baseLog
                            . ', frameCtrlField='.$frameCtrlField
                            . ', SQN='.$SQN
                            . ', cmd='.$cmdTxt
                            . ', attribute='.$attribute
                            . ', dataType='.$dataType
                            . ', value='.$value.' - '.hexdec($value)
                            );

                        $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );

                        return;
                    }

                    // example: Remontée puissance prise Blitzwolf BW-SHP13
                    if (($attribute == '050B') && ($dataType == '29') ) {
                        // '29' => array( 'Int16', 2 ), // Signed 16-bit int
                        $value = substr($payload,42, 2).substr($payload,40, 2);

                        if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée puissance Legrand/Blitzwolf '
                            . $baseLog
                            . ', frameCtrlField='.$frameCtrlField
                            . ', SQN='.$SQN
                            . ', cmd='.$cmdTxt
                            . ', attribute='.$attribute
                            . ', dataType='.$dataType
                            . ', value='.$value.' - '.hexdec($value)
                            );

                        $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );

                        return;
                    }
                }

                // exemple: emontée puissance module Legrand 20AX
                if ( ($cmd == '0A') ) {
                    $attribute = substr($payload,34, 2).substr($payload,32, 2);
                    $dataType  = substr($payload,36, 2);
                    if ( ($attribute == '050B') && ($dataType == '29') ) {
                        // '29' => array( 'Int16', 2 ), // Signed 16-bit int
                        $value = substr($payload,40, 2).substr($payload,38, 2);

                        if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée puissance Legrand/Blitzwolf(?) '
                            . $baseLog
                            . ', frameCtrlField='.$frameCtrlField
                            . ', SQN='.$SQN
                            . ', cmd='.$cmd
                            . ', attribute='.$attribute
                            . ', dataType='.$dataType
                            . ', value='.$value.' - '.hexdec($value)
                            );

                        $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );

                        return;
                    }
                }
            }

            // Remontée etat relai module Legrand 20AX
            // 80020019F4000104 FC41 010102D2B9020000180B0A000030000100100084
            if ( ($profile == "0104") && ($cluster == "FC41") ) {

                $frameCtrlField         = substr($payload,26, 2);
                $SQN                    = substr($payload,28, 2);
                $cmd                    = substr($payload,30, 2); if ( $cmd == "0a" ) $cmd = "0a - report attribut";

                $attribute              = substr($payload,34, 2).substr($payload,32, 2);
                $dataType               = substr($payload,36, 2);
                $value                  = substr($payload,38, 2);

                if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée etat relai module Legrand 20AX '
                                    . $baseLog
                                    . ', frameCtrlField='.$frameCtrlField
                                    . ', SQN='.$SQN
                                    . ', cmd='.$cmd
                                    . ', attribute='.$attribute
                                    . ', dataType='.$dataType
                                    . ', value='.$value.' - '.hexdec($value)
                                    );

                $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );

                // if ($this->debug["8002"]) $this->deamonlog('debug', 'lenght: '.strlen($payload) );
                if ( strlen($payload)>42 ) {
                    $attribute              = substr($payload,42, 2).substr($payload,40, 2);
                    $dataType               = substr($payload,44, 2);
                    $value                  = substr($payload,46, 2);

                    if ($this->debug["8002"]) $this->deamonlog('debug', $dest.', Type=8002/Data indication - Remontée etat relai module Legrand 20AX '
                                    . $baseLog
                                    . ', frameCtrlField='.$frameCtrlField
                                    . ', SQN='.$SQN
                                    . ', cmd='.$cmd
                                    . ', attribute='.$attribute
                                    . ', dataType='.$dataType
                                    . ', value='.$value.' - '.hexdec($value)
                                    );

                    $this->mqqtPublish($dest."/".$srcAddress, $cluster.'-'.$destEndPoint, $attribute, hexdec($value) );
                }

                return;
            }

            // Prise Xiaomi
            if ( ($profile == "0104") && ($cluster == "FCC0") ) {
                $FCF            = substr($payload,26, 2);
                if ( $FCF=='1C' ) {
                    $Manufacturer   = substr($payload,30, 2).substr($payload,28, 2);
                    if ( $Manufacturer=='115F' ) {
                        $SQN            = substr($payload,32, 2);
                        $Cmd            = substr($payload,34, 2);
                        if ( $Cmd=='0A') {
                            $Attribut   = substr($payload,38, 2).substr($payload,36, 2);
                            if ( $Attribut=='00F7' ) {
                                $dataType = substr($payload,40, 2);
                                if ( $dataType == "41" ) {
                                    $dataLength = hexdec(substr($payload,42, 2));
                                    // Je suppose que je suis avec un message Xiaomi Prise que je decode comme les champs FF01
                                    $FCC0 = $this->decodeFF01(substr($payload, 44, $dataLength));
                                    $this->deamonlog('debug', "  Champ proprietaire Xiaomi (Prise)");
                                    $this->deamonlog('debug', "  ".json_encode($FCC0));

                                    $this->mqqtPublish($dest."/".$srcAddress, '0402', '01-0000',     $FCC0["Device Temperature"]["valueConverted"], $qos);    // Device Temperature
                                    $this->mqqtPublish($dest."/".$srcAddress, '0006', '01-0000',     $FCC0["Etat SW 1 Binaire"]["valueConverted"],  $qos);    // On Off Etat
                                    $this->mqqtPublish($dest."/".$srcAddress, 'tbd',  '--conso--',   $FCC0["Consommation"]["valueConverted"],       $qos);    // Consumption
                                    $this->mqqtPublish($dest."/".$srcAddress, 'tbd',  '--volt--',    $FCC0["Voltage"]["valueConverted"],            $qos);    // Voltage
                                    $this->mqqtPublish($dest."/".$srcAddress, 'tbd',  '--current--', $FCC0["Current"]["valueConverted"],            $qos);    // Current
                                    $this->mqqtPublish($dest."/".$srcAddress, '000C', '15-0055',     $FCC0["Puissance"]["valueConverted"],          $qos);    // Puissance
                                }
                            }
                        }
                    }
                }
            }

            if ($this->debug["8002"]) $this->deamonlog("debug",$dest.", Type=8002 (decoded but not processed - message unknown): ".$baseLog);
        }

        function decode8003($dest, $payload, $ln, $qos, $clusterTab) {
            // <source endpoint: uint8_t t>
            // <profile ID: uint16_t>
            // <cluster list: data each entry is uint16_t>

            $SrcEndpoint = substr($payload, 0, 2);
            $profileID   = substr($payload, 2, 4);

            $len = (strlen($payload)-2-4-2)/4;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug', $dest.', Type=8003/Liste des clusters de l’objet, SrcEP='.$SrcEndpoint.', ProfileID='.$profileID.', Cluster='.substr($payload, (6 + ($i*4) ), 4). ' - ' . $clusterTab['0x'.substr($payload, (6 + ($i*4) ), 4)]);
            }
        }

        function decode8004($dest, $payload, $ln, $qos, $dummy) {
            // <source endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            // <attribute list: data each entry is uint16_t>

            $SrcEndpoint = substr($payload, 0, 2);
            $profileID   = substr($payload, 2, 4);
            $clusterID   = substr($payload, 6, 4);

            $len = (strlen($payload)-2-4-4-2)/4;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug', $dest.', Type=8004/Liste des Attributs de l’objet, SrcEP='.$SrcEndpoint.', ProfileID='.$profileID.', ClustId='.$clusterID.', Attribute='.substr($payload, (10 + ($i*4) ), 4) );
            }
        }

        function decode8005($dest, $payload, $ln, $qos, $dummy) {
            // $this->deamonlog('debug',';type: 8005: (Liste des commandes de l’objet)(Not Processed)' );

            // <source endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            //<command ID list:data each entry is uint8_t>

            $SrcEndpoint = substr($payload, 0, 2);
            $profileID   = substr($payload, 2, 4);
            $clusterID   = substr($payload, 6, 4);

            $len = (strlen($payload)-2-4-4-2)/2;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug', $dest.', Type=8005/Liste des commandes de l’objet, SrcEP='.$SrcEndpoint.', ProfID='.$profileID.', ClustId='.$clusterID.', Commandes='.substr($payload, (10 + ($i*2) ), 2) );
            }
        }

        /* Network State Reponse */
        function decode8009($dest, $payload, $ln, $qos, $dummy)
        {
            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; (Network State response)(Processed->MQTT)'); }

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

            $msgDecoded = '8009/Network state response, ShortAddr='.$ShortAddress.', ExtAddr='.$ExtendedAddress.', PANId='.$PAN_ID.', ExtPANId='.$Ext_PAN_ID.', Channel='.$Channel;
            if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type='.$msgDecoded); }

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
            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; ZiGate Short Address: '.$ShortAddress); }

            // Envoie Extended Address
            $SrcAddr = "Ruche";
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $data = $ExtendedAddress;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; IEEE Address: '.$ExtendedAddress); }

            // Envoie PAN ID
            $SrcAddr = "Ruche";
            $ClusterId = "PAN";
            $AttributId = "ID";
            $data = $PAN_ID;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; PAN ID: '.$PAN_ID); }

            // Envoie Ext PAN ID
            $SrcAddr = "Ruche";
            $ClusterId = "Ext_PAN";
            $AttributId = "ID";
            $data = $Ext_PAN_ID;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; Ext_PAN_ID: '.$Ext_PAN_ID); }

            // Envoie Channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Channel";
            $data = $Channel;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; Channel: '.$Channel); }

            // if ($this->debug['8009']) { $this->deamonlog('debug', $dest.', Type=8009; ; Level=0x'.substr($payload, 0, 2)); }
        }

        /* Version */
        function decode8010($dest, $payload, $ln, $qos, $dummy)
        {
            /*
            <Major version number: uint16_t>
            <Installer version number: uint16_t>
            */

            if ($this->debug['8010']) {
                $this->deamonlog('debug', $dest.', Type=8010/Version, Appli='.hexdec(substr($payload, 0, 4)) . ', SDK='.substr($payload, 4, 4));
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
            // if ($this->debug['8010']) { $this->deamonlog('debug', $dest.', Type=8010; '.$AttributId.': '.$data.' qos:'.$qos); }
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        /**
         * ACK DATA (since FW 3.1b) = ZPS_EVENT_APS_DATA_CONFIRM Note: NACK = 8702
         *
         * This method process a Zigbeee message coming from a zigate for Ack APS messages
         *  Will first decode it.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ?
         * @param $dummy    ?
         *
         * @return          Nothing
         */
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

            if ($this->debug['8011']) $this->deamonlog('debug', $dest.', Type=8011/APS data ACK, Status='.$Status.', DestAddr='.$DestAddr.', DestEP='.$DestEndPoint.', ClustId='.$ClustID);
        }

        /**
         * “Permit join” status
         *
         * This method process a Zigbeee message coming from a zigate findicating the Join Permit Status
         * Will first decode it.
         * Send the info to Abeille to update ruche command
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ?
         * @param $dummy    ?
         *
         * @return          Nothing
         */
        function decode8014($dest, $payload, $ln, $qos, $dummy)
        {
            // “Permit join” status
            // response Msg Type=0x8014
            // 0 - Off 1 - On
            //<Status: bool_t>
            // Envoi Status

            $data = substr($payload, 0, 2);

            if ($this->debug['8011']) $this->deamonlog('debug', $dest.', Type=8014/Permit join status response: PermitJoinStatus='.$data);
            if ($data == "01")
            if ($this->debug['8011']) $this->deamonlog('info', 'Zigate'.substr($dest, 7, 1).' en mode INCLUSION');
            else
            if ($this->debug['8011']) $this->deamonlog('info', 'Zigate'.substr($dest, 7, 1).': FIN du mode inclusion');

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

            $this->deamonlog('debug', $dest.', Type=8015/Abeille List: Payload='.$payload);

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

                $this->deamonlog('debug', '  i='.$i.': '
                                 . 'ID='.substr($payload, $i * 26 + 0, 2)
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
            $this->deamonlog('debug', $dest.', Type=8017/Get Time server Response: Timestamp='.hexdec($Timestamp) );

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
            if( substr($payload, 0, 2)  > "04" ) { $data = "Failed (ZigBee event codes): ".substr($payload, 0, 2); }
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

            if ($this->debug['8024']) {$this->deamonlog('debug', $dest.', Type=8024/Network joined-formed, Status=\''.$data.'\', ShortAddr='.$dataShort.', ExtAddr='.$dataIEEE.', Chan='.$dataNetwork);}
        }

        function decode8030($dest, $payload, $ln, $qos, $dummy)
        {
            // Firmware V3.1a: Add fields for 0x8030, 0x8031 Both responses now include source endpoint, addressmode and short address. https://github.com/fairecasoimeme/ZiGate/issues/122
            // <Sequence number: uint8_t>
            // <status: uint8_t>

            $this->deamonlog('debug', $dest.', Type=8030/Bind response (decoded but Not Processed - Just send time update and status to Network-Bind in Ruche)'
                             . ', SQN=0x'.substr($payload, 0, 2)
                             . ', Status=0x'.substr($payload, 2, 2)  );

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Bind";
            $data = date("Y-m-d H:i:s")." Status (00: Ok, <>0: Error): ".substr($payload, 2, 2);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        /* 8035/PDM event code. Since FW 3.1b */
        function decode8035($dest, $payload, $ln, $qos, $dummy)
        {
            $PDMEvtCode = substr($payload, 0, 2); // <PDM event code: uint8_t>
            $RecId = substr($payload, 2, 8); // <record id : uint32_t>

            $this->deamonlog('debug', $dest.', Type=8035/PDM event code'
                             .', PDMEvtCode=x'.$PDMEvtCode
                             .', RecId='.$RecId
                             .' => '.zgGetPDMEvent($PDMEvtCode));
        }

        function decode8040($dest, $payload, $ln, $qos, $dummy)
        {
            // Firmware V3.1a: Add SrcAddr to 0x8040 command (MANAGEMENT_LQI_REQUEST) https://github.com/fairecasoimeme/ZiGate/issues/198

            // Network address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>

            $this->deamonlog('debug', $dest.', Type=8040/Network address response'
                             . ', SQN='                                     .substr($payload, 0, 2)
                             . ', Status='                                  .substr($payload, 2, 2)
                             . ', ExtAddr='                                 .substr($payload, 4,16)
                             . ', ShortAddr='                               .substr($payload,20, 4)
                             . ', NumberOfAssociatedDevices='               .substr($payload,24, 2)
                             . ', StartIndex='                              .substr($payload,26, 2) );

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

            $this->deamonlog('debug', $dest.', Type=8041/IEEE Address response'
                             . ', SQN='                                     .substr($payload, 0, 2)
                             . ', Status='                                  .substr($payload, 2, 2)
                             . ', ExtAddr='                                 .substr($payload, 4,16)
                             . ', ShortAddr='                               .substr($payload,20, 4)
                             . ', NumberOfAssociatedDevices='               .substr($payload,24, 2)
                             . ', StartIndex='                              .substr($payload,26, 2) );

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

        /**
         * Simple descriptor response
         *
         * This method process a Zigbeee message coming from a device indicating it s simple description
         *  Will first decode it.
         *  And send to Abeille only the Type of Equipement. Could be used if the model don't existe based on the name.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ?
         * @param $dummy    ?
         *
         * @return          Nothing as actions are requested in the execution
         */
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

            $SQN        = substr($payload, 0, 2);
            $Status     = substr($payload, 2, 2);
            $SrcAddr    = substr($payload, 4, 4);
            $Len        = substr($payload, 8, 2);
            $EPoint     = substr($payload,10, 2);
            $profile    = substr($payload,12, 4);
            $deviceId   = substr($payload,16, 4);
            $InClusterCount = substr($payload,22, 2); // Number of input clusters

            if ($this->debug['8043']) $this->deamonlog('debug', $dest.', Type=8043/Simple descriptor response'
                             . ', SQN='         .$SQN
                             . ', Status='      .$Status
                             . ', Addr='        .$SrcAddr
                             . ', Length='      .$Len
                             . ', EP='          .$EPoint
                             . ', Profile='     .$profile.'/'.zgGetProfile($profile)
                             . ', DevId='       .$deviceId.'/'.zgGetDevice($profile, $deviceId)
                             . ', BitField='    .substr($payload,20, 2))
                             . ', [Modelisation]'
                             ;

            if ($Status=="00") {
                // Envoie l info a Abeille
                $ClusterId = "SimpleDesc";
                $AttributId = "DeviceDescription";
                $data = zgGetDevice($profile, $deviceId);
                $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

                // Decode le message dans les logs
                if ($this->debug['8043']) $this->deamonlog('debug','  [Modelisation] InClusterCount='.$InClusterCount);
                for ($i = 0; $i < (intval(substr($payload, 22, 2)) * 4); $i += 4) {
                    if ($this->debug['8043']) $this->deamonlog('debug', '  [Modelisation] InCluster='.substr($payload, (24 + $i), 4).' - '.zgGetCluster(substr($payload, (24 + $i), 4)));
                }
                if ($this->debug['8043']) $this->deamonlog('debug','  [Modelisation] OutClusterCount='.substr($payload,24+$i, 2));
                for ($j = 0; $j < (intval(substr($payload, 24+$i, 2)) * 4); $j += 4) {
                    if ($this->debug['8043']) $this->deamonlog('debug', '  [Modelisation] OutCluster='.substr($payload, (24 + $i +2 +$j), 4).' - '.zgGetCluster(substr($payload, (24 + $i +2 +$j), 4)));
                }

                $data = 'zigbee'.zgGetDevice($profile, $deviceId);
                if ( strlen( $data) > 1 ) {
                    $this->mqqtPublish($dest."/".$SrcAddr, "SimpleDesc-".$EPoint, "DeviceDescription", $data);
                }
            }
            elseif ($Status=="83") {
                if ($this->debug['8043']) $this->deamonlog('debug', '  [Modelisation] Simple Descriptor Not Active');
            }
            else {
                if ($this->debug['8043']) $this->deamonlog('debug', '  [Modelisation] Simple Descriptor status inconnu.');
            }
        }

        /**
         * Active Endpoints Response
         *
         * This method process a Zigbeee message coming from a device indicating existing EP
         *  Will first decode it.
         *  Continue device identification by requesting Manufacturer, Name, Location, simpleDescriptor to the device.
         *  Then request the configuration of the device and even more infos.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ?
         * @param $dummy    ?
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8045($dest, $payload, $ln, $qos, $dummy)
        {
            $SrcAddr = substr($payload, 4, 4);
            $EP = substr($payload, 10, 2);

            $endPointList = "";
            for ($i = 0; $i < (intval(substr($payload, 8, 2)) * 2); $i += 2) {
                // $this->deamonlog('debug','Endpoint : '    .substr($payload, (10 + $i), 2));
                $endPointList = $endPointList . '; '.substr($payload, (10 + $i), 2) ;
            }

            if ($this->debug['8045']) $this->deamonlog('debug', $dest.', Type=8045/Active endpoints response'
                             . ', SQN='             .substr($payload, 0, 2)
                             . ', Status='          .substr($payload, 2, 2)
                             . ', ShortAddr='       .substr($payload, 4, 4)
                             . ', EndPointCount='   .substr($payload, 8, 2)
                             . ', EndPointList='    .$endPointList
                             . ', [Modelisation]'
                            );

            $this->mqqtPublishFctToCmd(                     "Cmd".$dest."/Ruche/getManufacturerName",                         "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            $this->mqqtPublishFctToCmd(                     "Cmd".$dest."/Ruche/getName",                                     "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            if ($EP="0B" ) $this->mqqtPublishFctToCmd(      "Cmd".$dest."/Ruche/getLocation",                                 "address=".$SrcAddr.'&destinationEndPoint='.$EP );
            $this->mqqtPublishFctToCmd(                "TempoCmd".$dest."/Ruche/SimpleDescriptorRequest&time=".(time()+4),    "address=".$SrcAddr.'&endPoint='.           $EP );

            $this->actionQueue[] = array( 'when'=>time()+ 8, 'what'=>'configureNE', 'addr'=>$dest.'/'.$SrcAddr );
            $this->actionQueue[] = array( 'when'=>time()+11, 'what'=>'getNE',       'addr'=>$dest.'/'.$SrcAddr );
        }

        /**
         * 8048/Leave indication
         *
         * This method process a Zigbeee message coming from a device indicating Leaving
         *  Will first decode it.
         *  Continue device identification by requesting Manufacturer, Name, Location, simpleDescriptor to the device.
         *  Then request the configuration of the device and even more infos.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ?
         * @param $dummy    ?
         *
         * @return          Nothing as actions are requested in the execution
         */
        function decode8048($dest, $payload, $ln, $qos, $dummy)
        {
            $IEEE = substr($payload, 0, 16);
            $RejoinStatus = substr($payload, 16, 2);

            $msgDecoded = '8048/Leave indication, ExtAddr='.$IEEE.', RejoinStatus='.$RejoinStatus;
            if ($this->debug['8048']) $this->deamonlog('debug', $dest.', Type='.$msgDecoded);

            /*
            $cmds = Cmd::byLogicalId('IEEE-Addr');
            foreach( $cmds as $cmd ) {
                if ( $cmd->execCmd() == $IEEE ) {
                    $abeille = $cmd->getEqLogic();
                    $name = $abeille->getName();
                }
            }
             */

            $SrcAddr = "Ruche";
            $ClusterId = "joinLeave";
            $AttributId = "IEEE";
            $data = "Leave->".$IEEE."->".$RejoinStatus;
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            $SrcAddr = "Ruche";
            $fct = "disable";
            $extendedAddr = $IEEE;
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
                $this->deamonlog('debug', $dest.', Type=804A/Management network update response, Status Error ('.$Status.') can not process the message.');
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

            $this->deamonlog('debug', $dest.', Type=804A/Management Network Update Response (Processed): addr='.$addr
                             . ', SQN=0x'.$SQN
                             . ', Status='.$Status
                             . ', TotalTransmission='.$TotalTransmission
                             . ', TransmFailures='.$TransmFailures
                             . ', ScannedChannels=0x'.$ScannedChannels
                             . ', ScannedChannelsCount=0x'.$ScannedChannelsCount
                             . ', Channels='.json_encode($results)
                             );
        }

        /* 804E/Management LQI response */
        function decode804E($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uint8_t>
            // <status: uint8_t>
            // <Neighbour Table Entries : uint8_t>
            // <Neighbour Table List Count : uint8_t>
            // <Start Index : uint8_t>
            // <List of elements described below :> (empty if 'Neighbour Table list count' is 0)
            //      NWK Address : uint16_t
            //      Extended PAN ID : uint64_t
            //      IEEE Address : uint64_t
            //      Depth : uint_t
            //      Link Quality : uint8_t
            //      Bit map of attributes Described below: uint8_t
            //          bit 0-1 Device Type (0-Coordinator 1-Router 2-End Device)
            //          bit 2-3 Permit Join status (1- On 0-Off)
            //          bit 4-5 Relationship (0-Parent 1-Child 2-Sibling)
            //          bit 6-7 Rx On When Idle status (1-On 0-Off)
            // <Src Address : uint16_t> (only since v3.1a)
            // Note: It looks like there is an LQI byte at end of frame

            $SQN = substr($payload, 0, 2);
            $Status = substr($payload, 2, 2);
            $NTableEntries = substr($payload, 4, 2);
            $NTableListCount = substr($payload, 6, 2);
            $StartIndex = substr($payload, 8, 2);
            $SrcAddr = substr($payload, 10 + ($NTableListCount * 42), 4); // 21 bytes per neighbour entry

            $decoded = '804E/Management LQI response'
                .', SQN='                       .$SQN
                .', Status='                    .$Status
                .', NeighbourTableEntries='     .$NTableEntries
                .', NeighbourTableListCount='   .$NTableListCount
                .', StartIndex='                .$StartIndex
                .', SrcAddr='                   .$SrcAddr;
            $this->deamonlog('debug', $dest.', Type='.$decoded);

            if ($Status != "00") {
                $this->deamonlog('debug', "  Status != 00 => abandon du decode");
                return;
            }

            $j = 10; // Neighbours list starts at char 10
            $NList = []; // List of neighbours
            for ($i = 0; $i < hexdec($NTableListCount); $j += 42, $i++) {
                $N = array(
                    "Addr"     => substr($payload, $j + 0, 4),
                    "ExtPANId" => substr($payload, $j + 4, 16),
                    "ExtAddr"  => substr($payload, $j + 20, 16),
                    "Depth"    => substr($payload, $j + 36, 2),
                    "LQI"      => substr($payload, $j + 38, 2),
                    "BitMap"   => substr($payload, $j + 40, 2)
                );
                $NList[] = $N; // Add to neighbours list
                $this->deamonlog('debug', '  NAddr='.$N['Addr']
                    .', NExtPANId='.$N['ExtPANId']
                    .', NExtAddr='.$N['ExtAddr']
                    .', NDepth='.$N['Depth']
                    .', NLQI='.$N['LQI']
                    .', NBitMap='.$N['BitMap'].' => '.zgGet804EBitMap($N['BitMap']));

                // On regarde si on connait NWK Address dans Abeille, sinon on va l'interroger pour essayer de le récupérer dans Abeille.
                // Ca ne va marcher que pour les équipements en eveil.
                // Cmdxxxx/Ruche/getName address=bbf5&destinationEndPoint=0B
                if (($N['Addr'] != "0000") && !Abeille::byLogicalId($dest.'/'.$N['Addr'], 'Abeille')) {
                    $this->deamonlog('debug', '  NeighbourAddr='.$N['Addr']." n'est pas dans Jeedom. Essayons de l'interroger. Si en sommeil une intervention utilisateur sera necessaire.");

                    $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/getName", "address=".$N['Addr']."&destinationEndPoint=01" );
                    $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/getName", "address=".$N['Addr']."&destinationEndPoint=03" );
                    $this->mqqtPublishFctToCmd( "Cmd".$dest."/Ruche/getName", "address=".$N['Addr']."&destinationEndPoint=0B" );
                }

                $this->mqqtPublishCmdFct( $dest."/".$N['Addr']."/IEEE-Addr", $N['ExtAddr']);
            }

            $this->msgToLQICollector($SrcAddr, $NTableEntries, $NTableListCount, $StartIndex, $NList);
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

            $this->deamonlog('debug', $dest.', Type=8060/Add a group response (ignoré)'
                             . ', SQN='           .substr($payload, 0, 2)
                             . ', EndPoint='      .substr($payload, 2, 2)
                             . ', ClusterId='     .substr($payload, 4, 4)
                             . ', Status='        .substr($payload, 8, 2)
                             . ', GroupID='       .substr($payload,10, 4)
                             . ', SrcAddr='       .substr($payload,14, 4) );
        }

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
                $this->deamonlog('debug', $dest.', Type=8062: group '.$i.'(addr:'.(12+$i*4).'): '  .substr($payload,12+$i*4, 4));
                $groupsId .= '-' . substr($payload,12+$i*4, 4);
            }
            $this->deamonlog('debug', $dest.', Type=8062;Groups: ->'.$groupsId."<-");

            // $this->deamonlog('debug', ';Level=0x'.substr($payload, strlen($payload)-2, 2));

            // Envoie Group-Membership
            $SrcAddr = substr($payload, 12+$groupCount*4, 4);
            if ($SrcAddr == "0000" ) { $SrcAddr = "Ruche"; }
            $ClusterId = "Group";
            $AttributId = "Membership";
            if ( $groupsId == "" ) { $data = "none"; } else { $data = $groupsId; }

            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);

            $this->deamonlog('debug', $dest.', Type=8062/Group Membership'
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

            $this->deamonlog('debug', $dest.', Type=8063/Remove a group response (ignoré)'
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
            // $this->deamonlog('debug', $dest.', Type=8084/? (ignoré)');
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

            $this->deamonlog('debug', $dest.', Type=8085/Remote button pressed (ClickHoldRelease) a group response)'
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
            // <SrcAddr: uint16_t>          -> 4
            // <status: uint8>              -> 2

            $this->deamonlog('debug', $dest.', Type=8095/Remote button pressed (ONOFF_UPDATE) a group response)'
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', Addr Mode='    .substr($payload, 8, 2)
                             . ', SrcAddr='      .substr($payload,10, 4)
                             . ', Status='       .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Click";
            $AttributId     = "Middle";
            $data           = substr($payload,14, 2);

            $this->mqqtPublish($dest.'/'.$source, $ClusterId, $AttributId, $data);
        }

        //----------------------------------------------------------------------------------------------------------------
        ##TODO
        #reponse scene
        #80a0-80a6
        function decode80A0($dest, $payload, $ln, $qos, $dummy)
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

            $this->deamonlog('debug', $dest.', Type=80A0/Scene View (ignoré)'
                             . ', SQN='                           .substr($payload, 0, 2)
                             . ', EndPoint='                      .substr($payload, 2, 2)
                             . ', ClusterId='                     .substr($payload, 4, 4)
                             . ', Status='                        .substr($payload, 8, 2)

                             . ', GroupID='                       .substr($payload,10, 4)
                             . ', SceneID='                       .substr($payload,14, 2)
                             . ', transition time: '              .substr($payload,16, 4)

                             . ', scene name lenght: '            .substr($payload,20, 2)  // Osram Plug repond 0 pour lenght et rien apres.
                             . ', scene name max lenght: '        .substr($payload,22, 2)
                             . ', scene name : '                  .substr($payload,24, 2)

                             . ', scene extensions lenght: '      .substr($payload,26, 4)
                             . ', scene extensions max lenght: '  .substr($payload,30, 4)
                             . ', scene extensions : '            .substr($payload,34, 2) );
        }

        function decode80A3($dest, $payload, $ln, $qos, $dummy)
        {
            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', $dest.', Type=80A3/Remove All Scene (ignoré)'
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', Status='       .substr($payload, 8, 2)
                             . ', group ID='     .substr($payload,10, 4)
                             . ', source='       .substr($payload,14, 4)  );
        }

        function decode80A4($dest, $payload, $ln, $qos, $dummy)
        {
            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <scene ID: uint8_t>          -> 2
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', $dest.', Type=80A4/Store Scene Response (ignoré)'
                             . ', SQN='          .substr($payload, 0, 2)
                             . ', EndPoint='     .substr($payload, 2, 2)
                             . ', ClusterId='    .substr($payload, 4, 4)
                             . ', Status='       .substr($payload, 8, 2)
                             . ', GroupID='      .substr($payload,10, 4)
                             . ', SceneID='      .substr($payload,14, 2)
                             . ', Source='       .substr($payload,16, 4)  );
        }

        function decode80A6($dest, $payload, $ln, $qos, $dummy)
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

                $this->deamonlog('debug', $dest.', Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT)'
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
                    $this->deamonlog('debug', $dest.', Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT) => Status NOT null'
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

                $this->deamonlog('debug', $dest.', Type=80A6/Scene Membership (Processed->$this->decoded but not sent to MQTT)'
                                 . ', SQN='          .$seqNumber
                                 . ', EndPoint='     .$endpoint
                                 . ', ClusterId='    .$clusterId
                                 . ', Status='       .$status
                                 . ', Capacity='     .$capacity
                                 . ', Source='       .$source
                                 . ', GroupID='      .$groupID
                                 . ', SceneID='      .$sceneId
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

        function decode80A7($dest, $payload, $ln, $qos, $dummy)
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

            $this->deamonlog('debug', $dest.', Type=80A7/Remote button pressed (LEFT/RIGHT) (Processed->$this->decoded but not sent to MQTT)'
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

        /* Common function for 8100 & 8102 messages */
        function decode8100_8102($type, $dest, $payload, $ln, $qos, $quality)
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
            $SQN                = substr($payload,  0, 2);
            $SrcAddr            = substr($payload,  2, 4);
            $EPoint             = substr($payload,  6, 2);
            $ClusterId          = substr($payload,  8, 4);
            $AttributId         = substr($payload, 12, 4);
            $AttributStatus     = substr($payload, 16, 2);
            $dataType           = substr($payload, 18, 2);
            $AttributSize       = substr($payload, 20, 4);
            $Attribut           = substr($payload, 24, hexdec($AttributSize) * 2);

            /* Params: SrcAddr, ClustId, AttrId, Data */
            $this->mqqtPublish($dest."/".$SrcAddr, 'Link', 'Quality', $quality);

            if ($type == "8100")
                $msg = $dest.', 8100/Read individual attribute response';
            else
                $msg = $dest.', 8102/Attribut report';

            $msg .= ', SQN='            .$SQN
                    .', Addr='          .$SrcAddr
                    .', EP='            .$EPoint
                    .', ClustId='       .$ClusterId
                    .', AttrId='        .$AttributId
                    .', AttrStatus='    .$AttributStatus
                    .', AttrDataType='  .$dataType
                    .', AttrSize='      .$AttributSize;

            if ($AttributStatus!='00') { 
                $msg .= " -> erreur, attribut status not null, probablement tentative de lecture d un parametre non disponible. ";
                $this->deamonlog('debug', $msg );
                return; 
            }

            $this->deamonlog('debug', $dest.', Type='.$msg);

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
                if ( ($ClusterId=="000C") && ($AttributId=="0055")  ) {
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
                        // Relay Double - Prise Xiaomi
                        $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId,     $EPoint.'-'.$AttributId,    $puissanceValue,    $qos);
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
                // 0005: ModelIdentifier
                // 0010: Piece (nom utilisé pour Profalux)
                if (($ClusterId=="0000") && (($AttributId=="0004") || ($AttributId=="0005") || ($AttributId=="0010"))) {
                    $msg .= ', DataByteList='.pack('H*', $Attribut);
                    $msg .= ', [Modelisation]';

                    if ($AttributId=="0004") { // 0x0004 ManufacturerName string
                        $trimmedValue = pack('H*', $Attribut);
                        $trimmedValue = str_replace(' ', '', $trimmedValue); //remove all space in names for easier filename handling
                        $trimmedValue = str_replace("\0", '', $trimmedValue); // On enleve les 0x00 comme par exemple le nom des equipements Legrand

                        if (strlen($trimmedValue )>2)
                            $this->ManufacturerNameTable[$dest.'/'.$SrcAddr] = array ( 'time'=> time(), 'ManufacturerName'=>$trimmedValue );

                        $this->deamonlog('debug', "  ManufacturerName='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."', ".json_encode($this->ManufacturerNameTable).', [Modelisation]');

                        return;
                    }
                    if ( ($AttributId=="0005") || ($AttributId=="0010") ) { // 0x0005 ModelIdentifier string
                        $trimmedValue = pack('H*', $Attribut);
                        $trimmedValue = str_replace(' ', '', $trimmedValue); //remove all space in names for easier filename handling
                        $trimmedValue = str_replace("\0", '', $trimmedValue); // On enleve les 0x00 comme par exemple le nom des equipements Legrand

                        ///@TODO: needManufacturer : C est un verrue qu'il faudrait retirer. Depuis le debut seul le nom est utilisé et maintenant on a des conflit de nom du fait de produits differents s annonceant sous le meme nom. Donc on utilise Manufactuerer_ModelId. Mais il faudrait reprendre tous les modeles. D ou cette verrue.
                        $needManufacturer = array('TS0043','TS0115','TS0121');
                        if (in_array($trimmedValue,$needManufacturer)) {
                            if (isset($this->ManufacturerNameTable[$dest.'/'.$SrcAddr])) {
                                if ( $this->ManufacturerNameTable[$dest.'/'.$SrcAddr]['time'] +10 > time() ) {
                                    $trimmedValue .= '_'.$this->ManufacturerNameTable[$dest.'/'.$SrcAddr]['ManufacturerName'];
                                    unset($this->ManufacturerNameTable[$dest.'/'.$SrcAddr]);
                                }
                                else {
                                    unset($this->ManufacturerNameTable[$dest.'/'.$SrcAddr]);
                                    return;
                                }
                            }
                            else {
                                return;
                            }
                        }

                        $data = $trimmedValue;
                        // Tcharp38: To be revisited for ManufacturerNameTable[] which appears to be empty
                        // $this->deamonlog('debug', "  ModelIdentifier='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."', ".json_encode($this->ManufacturerNameTable).', [Modelisation]');
                        $this->deamonlog('debug', "  ModelIdentifier='".pack('H*', $Attribut)."', trimmed='".$trimmedValue."', [Modelisation]");
                    }
                }

                // ------------------------------------------------------- Xiaomi ----------------------------------------------------------
                // Xiaomi Bouton V2 Carré
                elseif (($AttributId == "FF01") && ($AttributSize == "001A")) {
                    $this->deamonlog("debug", "  Champ proprietaire Xiaomi (Bouton carré)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  Voltage='.$voltage.' Voltage%='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage, $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $qos);
                }

                // Xiaomi lumi.sensor_86sw1 (Wall 1 Switch sur batterie)
                elseif (($AttributId == "FF01") && ($AttributSize == "001B")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Wall 1 Switch, Gaz Sensor)" );
                    // Dans le cas du Gaz Sensor, il n'y a pas de batterie alors le decodage est probablement faux.

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat           = substr($payload, 80, 2);

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent( $voltage ).', Etat=' .$etat);

                    $this->mqqtPublish($dest."/".$SrcAddr, '0006',     '01-0000', $etat,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Door Sensor V2
                elseif (($AttributId == "FF01") && ($AttributSize == "001D")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Door Sensor)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat           = substr($payload, 80, 2);

                    $this->deamonlog('debug', '  DoorV2Voltage='   .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', DoorV2Etat='      .$etat);

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,  $qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ));
                    $this->mqqtPublish($dest."/".$SrcAddr, '0006', '01-0000', $etat,  $qos);
                }

                // Xiaomi capteur temperature rond V1 / lumi.sensor_86sw2 (Wall 2 Switches sur batterie)
                elseif (($AttributId == "FF01") && ($AttributSize == "001F")) {
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

                elseif (($AttributId == 'FF01') && ($AttributSize == "0021")) {
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
                elseif (($AttributId == 'FF01') && ($AttributSize == "0022")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Capteur d\'inondation)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                    $etat = substr($payload, 88, 2);

                    $this->deamonlog('debug', '  Voltage='.$voltage.', Pourcent='.$this->volt2pourcent( $voltage ).', Etat='.$etat);
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
                elseif (($AttributId == 'FF01') && ($AttributSize == "0025")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Capteur de température carré)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  Voltage='.$voltage.', Voltage%='.$this->volt2pourcent( $voltage ).', Temperature='.$temperature.', Humidity='.$humidity.', Pression='.$pression);
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
                elseif (($AttributId == 'FF01') && ($AttributSize == "0026")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Bouton Aqara Wireless Switch V3)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Smoke Sensor
                elseif (($AttributId == 'FF01') && ($AttributSize == "0028")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Sensor Smoke)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Cube
                // Xiaomi capteur Inondation
                elseif (($AttributId == 'FF01') && ($AttributSize == "002A")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Cube)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));
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
                elseif (($AttributId == 'FF01') && ($AttributSize == "002E")) {
                    $this->deamonlog('debug', '  Champ proprietaire Xiaomi (Vibration)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Voltage%='.$this->volt2pourcent( $voltage ));
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Wall Plug (Kiwi: ZNCZ02LM, rvitch: )
                elseif (($AttributId == "FF01") && (($AttributSize == "0031") || ($AttributSize == "002B") )) {
                    $logMessage = "";
                    // $this->deamonlog('debug', $dest.', Type=8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall Plug)');
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
                elseif (($AttributId == "FF01") && ($AttributSize == "0044")) {
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
                elseif (($AttributId == "FF01") && ($AttributSize == "00XX")) {
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (Bouton Carre)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // ne traite pas les FF01 inconnus
                elseif ($AttributId == "FF01") {
                    $this->deamonlog("debug", "Champ FF01 non traite car inconnu." );
                    return;
                }

                // Xiaomi Presence Infrarouge IR V1 / Bouton V1 Rond
                elseif (($AttributId == "FF02")) {
                    // Non decodé a ce stade
                    // $this->deamonlog("debug", "Champ 0xFF02 non $this->decode a ce stade");
                    $this->deamonlog("debug","  Champ proprietaire Xiaomi (IR V1)" );

                    $voltage        = hexdec(substr($payload, 24 +  8, 2).substr($payload, 24 + 6, 2));

                    $this->deamonlog('debug', '  Voltage=' .$voltage.', Pourcent='.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish($dest."/".$SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // ------------------------------------------------------- Philips ----------------------------------------------------------
                // Bouton Telecommande Philips Hue RWL021
                elseif (($ClusterId == "FC00")) {

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
                    $data = pack('H*', $Attribut );
                }
            }

            if (isset($data)) {
                if ( strpos($data, "sensor_86sw2")>2 ) { $data="lumi.sensor_86sw2"; } // Verrue: getName = lumi.sensor_86sw2Un avec probablement des caractere cachés alors que lorsqu'il envoie son nom spontanement c'est lumi.sensor_86sw2 ou l inverse, je ne sais plus
                $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId."-".$EPoint, $AttributId, $data);
            }
        }

        /* 0x8100/Read individual Attribute Response */
        function decode8100($dest, $payload, $ln, $qos, $dummy)
        {
            $this->decode8100_8102("8100", $dest, $payload, $ln, $qos, $dummy);
        }

        function decode8101($dest, $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', $dest.', Type=8101/Default Response (ignoré)'
                             . '; Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.'
                             . ', SQN='.substr($payload, 0, 2)
                             . ', EndPoint='.substr($payload, 2, 2)
                             . ', '. $this->displayClusterId(substr($payload, 4, 4))
                             . ', Command='.substr($payload, 8, 2)
                             . ', Status='.substr($payload, 10, 2)  );
        }

        /* Attribute report */
        function decode8102($dest, $payload, $ln, $qos, $quality)
        {
            $this->decode8100_8102("8102", $dest, $payload, $ln, $qos, $quality);
        }

        function decode8110($dest, $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', $dest.', Type=8110/Write Attribute Response (Decoded but not processed yet)'
                        //    . ': Dest='.$dest
                        //    . ', Level=0x'.substr($payload, 0, 2)
                        //    . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))
                         );
        }

        function decode8120($dest, $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uint8_t>
            // <Src address : uint16_t>
            // <Endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <Attribute Enum: uint16_t> (add in v3.0f)
            // <Status: uint8_t>
            $msg = '8120/Configure Reporting response'
                . ', SQN='     .substr($payload, 0, 2)
                . ', Addr='    .substr($payload, 2, 4)
                . ', EP='      .substr($payload, 6, 2)
                . ', ClustId=' .substr($payload, 8, 4)
                . ', Attr='    .substr($payload,12, 4)
                . ', Status='  .substr($payload,16, 2);
            $this->deamonlog('debug', $dest.', Type='.$msg);

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Report";
            $data = date("Y-m-d H:i:s")." Attribut: ".substr($payload,12, 4)." Status (00: Ok, <>0: Error): ".substr($payload,16, 2);
            $this->mqqtPublish($dest."/".$SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8140($dest, $payload, $ln, $qos, $dummy)
        {
            // Some changes in this message so read: https://github.com/fairecasoimeme/ZiGate/pull/90
            $this->deamonlog('debug', $dest.', Type=8140/Configure Reporting response (Decoded but not processed yet)'
                            // . ': Dest='.$dest
                            // . ', Level=0x'.substr($payload, 0, 2)
                            // . ', Message='.$this->hex2str(substr($payload, 2, strlen($payload) - 2))
                             );
        }

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

            $this->deamonlog('debug', $dest.', Type=8401/IAS Zone status change notification'
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


        /**
         * 0x8701/Router Discovery Confirm -  Warning: potential swap between statuses.
         * This method process ????
         *  Will first decode it.
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ? Probably not needed anymore. Historical param from mosquitto broker needs
         * @param $dummy    ?
         *
         * @return          Does return anything as all action are triggered by sending messages in queues
         */
        function decode8701($dest, $payload, $ln, $qos, $dummy)
        {
            // NWK Code Table Chap 10.2.3 from JN-UG-3113
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            global $allErrorCode;

            $nwkStatus = substr($payload, 0, 2);
            $status = substr($payload, 2, 2);
            $Addr = substr($payload, 4, 4);

            $msg = '8701/Route discovery confirm'
                   .', MACStatus='.$status.' ('.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
                   .', NwkStatus='.$nwkStatus.' ('.$allErrorCode[$nwkStatus][0].'->'.$allErrorCode[$nwkStatus][1].')'
                   .', Addr='.$Addr;
            if ( $this->debug['8701'] ) $this->deamonlog('debug', $dest.', Type='.$msg);
        }

        /**
         * 8702/APS data confirm fail
         * This method process ????
         *  Will first decode it.
         *  Send the info to Ruche
         *
         * @param $dest     Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         * @param $payload  Parameter sent by the device in the zigbee message
         * @param $ln       ?
         * @param $qos      ? Probably not needed anymore. Historical param from mosquitto broker needs
         * @param $dummy    ?
         *
         * @return          Does return anything as all action are triggered by sending messages in queues
         */
        function decode8702($dest, $payload, $ln, $qos, $dummy)
        {
            global $allErrorCode;

            // <status: uint8_t>
            // <src endpoint: uint8_t>
            // <dst endpoint: uint8_t>
            // <dst address mode: uint8_t>
            // <destination address: uint64_t>
            // <seq number: uint8_t>
            $status = substr($payload, 0, 2);

            if ( $this->debug['8701'] ) $this->deamonlog('debug', $dest.', Type=8702/APS Data Confirm Fail'
                             . ', Status='.$status.' ('.$allErrorCode[$status][0].'->'.$allErrorCode[$status][1].')'
                             . ', SrcEP='.substr($payload, 2, 2)
                             . ', DestEP='.substr($payload, 4, 2)
                             . ', DestMode='.substr($payload, 6, 2)
                             . ', DestAddr='.substr($payload, 8, 4)
                             . ', SQN='.substr($payload, 12, 2)   );

            // type; 8702; (APS Data Confirm Fail)(decoded but Not Processed); Status : d4; Source Endpoint : 01; Destination Endpoint : 03; Destination Mode : 02; Destination Address : c3cd; SQN: : 00

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "8702";
            $data       = substr($payload, 8, 4);

            // if ( Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' ) ) $name = Abeille::byLogicalId( $dest.'/'.$data, 'Abeille' )->getHumanName(true);
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

            $this->deamonlog('debug', $dest.', Type=8806/Set TX power answer'
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

            $this->deamonlog('debug', $dest.', Type=8807/Get TX power'
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

        /**
         * getNE
         * This method send all command needed to the NE to get its state.
         *
         * @param $short    Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         *
         * @return          Doesn't return anything as all action are triggered by sending messages in queues
         */
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
                            if ( $this->debug['getNE'] ) $this->deamonlog('debug', 'Type=fct; getNE cmd: '.$getState);
                            $cmd->execCmd();
                        }
                    }
                }
            }
        }

        public static function execAtCreationCmdForOneNE($address) {
            $cmds = AbeilleCmd::searchConfigurationEqLogic( Abeille::byLogicalId( $address,'Abeille')->getId(), 'execAtCreation', 'action' );
            foreach ( $cmds as $key => $cmd ) {
                self::deamonlog('debug', 'execAtCreationCmdForOneNE: '.$cmd->getName().' - '.$cmd->getConfiguration('execAtCreation').' - '.$cmd->getConfiguration('execAtCreationDelay') );
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInclusion, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".(time()+$cmd->getConfiguration('PollingOnCmdChangeDelay')), $cmd->getConfiguration('request') );
            }
        }

        /**
         * configureNE
         * This method send all command needed to the NE to configure it.
         *
         * @param $short    Complete address of the device in Abeille. Which is also thee logicalId. Format is AbeilleX/YYYY - X being the Zigate Number - YYYY being zigbee short address.
         *
         * @return          Doesn't return anything as all action are triggered by sending messages in queues
         */
        function configureNE( $short ) {
            if ( $this->debug['configureNE'] ) $this->deamonlog('debug', 'Type=fct; ===> Configure NE Start');

            $commandeConfiguration = array( 'BindShortToZigateBatterie',            'setReportBatterie', 'spiritSetReportBatterie',
                                           'BindToZigateEtat',                      'setReportEtat',
                                           'BindToZigateLevel',                     'setReportLevel',
                                           'BindToZigateButton',
                                           'spiritTemperatureBindShortToZigate',    'spiritTemperatureSetReport',
                                           'BindToZigateIlluminance',               'setReportIlluminance', 'setReportIlluminanceXiaomi',
                                           'BindToZigateOccupancy',                 'setReportOccupancy',
                                           'BindToZigateTemperature',               'setReportTemperature',
                                           'BindToZigatePuissanceLegrand',          'setReportPuissanceLegrand',
                                           'BindShortToSmokeHeiman',                'setReportSmokeHeiman',
                                           'LivoloSwitchTrick1',                    'LivoloSwitchTrick2', 'LivoloSwitchTrick3', 'LivoloSwitchTrick4',
                                           );

            $abeille = Abeille::byLogicalId( $short,'Abeille');

            if ( $abeille) {

                ///@TODO: retirer ce bout de code et garder uniquement: execAtCreationCmdForOneNE
                // Initial mode of configuration, l ideal serait de retirer ce bout de code et garder uniquement: execAtCreationCmdForOneNE
                $arr = array(1, 2);
                foreach ($arr as &$value) {
                    foreach ( $commandeConfiguration as $config ) {
                        $cmd = $abeille->getCmd('action', $config);
                        if ( $cmd ) {
                            if ( $this->debug['configureNE'] ) $this->deamonlog('debug', 'Type=fct; ===> Configure NE cmd: '.$config);
                            $cmd->execCmd();
                        }
                        else {
                            if ( $this->debug['configureNE'] ) $this->deamonlog('debug', 'Type=fct; ===> Configure NE '.$config.': Cmd not found, probably not an issue, probably should not do it');
                        }
                    }
                }

                // New mode of configuration based on definition in the template
                self::execAtCreationCmdForOneNE($short);
            }

            if ( $this->debug['configureNE'] ) $this->deamonlog('debug', 'Type=fct; ===> Configure NE End');
        }

        function processActionQueue() {
            if ( !($this->actionQueue) ) return;
            if ( count($this->actionQueue) < 1 ) return;

            foreach ( $this->actionQueue as $key=>$action ) {
                if ( $action['when'] < time() ) {
                    if ( method_exists($this, $action['what']) ) {
                        if ( $this->debug['processActionQueue'] ) { $this->deamonlog('debug', 'processActionQueue(): action: '.json_encode($action)); }
                        $fct = $action['what'];
                        if ( isset($action['parm0']) ) {
                            $this->$fct($action['parm0'],$action['parm1'],$action['parm2'],$action['parm3']);
                        } else {
                            $this->$fct($action['addr']);
                        }
                        unset($this->actionQueue[$key]);
                    }
                }
            }
        }

        /**
         * fonction proxy pour rediriger le log vers le logger.
         *
         * @param string $level
         * @param string $message
         */
        public function deamonlog(string $level, string $message)
        {
            logMessage($level,$message);
        }
    } // class AbeilleParser
?>
