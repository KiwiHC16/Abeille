<?php

    /***
     * AbeilleParser
     *
     * pop data from FIFO file and translate them into a understandable message,
     * then publish them to mosquitto
     *
     */

    // Annonce -> populate NE-> get EP -> getName -> getLocation -> unset NE

    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__).'/Abeille.class.php';
    require_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';
    include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
    include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/function.php';
    include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/fifo.php';

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
                                         ),
                         );

    class debug {
        function deamonlog($loglevel = 'NONE', $message = "")
        {
            if ($this->debug["cli"] ) {
                echo "[".date("Y-m-d H:i:s").'][AbeilleParser][DEBUG.BEN] '.$message."\n";
            }
            else {
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
                              "8000"                    => 0, // Status
                              "8009"                    => 0, // Get Network Status
                              "8010"                    => 0,
                              "processAnnonce"          => 1,
                              "processAnnonceStageChg"  => 1,
                              "cleanUpNE"               => 0,
                              "Serial"                  => 1, );

        // ZigBee Cluster Library - Document 075123r02ZB - Page 79 - Table 2.15
        // Data Type -> Description, # octets
        public $dataType = array(
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

                                 '10' => array( 'Logical', 1 ),
                                 // 0x11-0x17 Reserved
                                 '18' => array( 'Bitmap', 1 ),
                                 '19' => array( 'Bitmap', 2 ),
                                 '1a' => array( 'Bitmap', 3 ),
                                 '1b' => array( 'Bitmap', 4 ),
                                 '1c' => array( 'Bitmap', 5 ),
                                 '1d' => array( 'Bitmap', 6 ),
                                 '1e' => array( 'Bitmap', 7 ),
                                 '1f' => array( 'Bitmap', 8 ),

                                 '20' => array( 'Unsigned integer', 1 ),
                                 '21' => array( 'Unsigned integer', 2 ),
                                 '22' => array( 'Unsigned integer', 3 ),
                                 '23' => array( 'Unsigned integer', 4 ),
                                 '24' => array( 'Unsigned integer', 5 ),
                                 '25' => array( 'Unsigned integer', 6 ),
                                 '26' => array( 'Unsigned integer', 7 ),
                                 '27' => array( 'Unsigned integer', 8 ),

                                 '28' => array( 'Signed integer', 1 ),
                                 '29' => array( 'Signed integer', 2 ),
                                 '2a' => array( 'Signed integer', 3 ),
                                 '2b' => array( 'Signed integer', 4 ),
                                 '2c' => array( 'Signed integer', 5 ),
                                 '2d' => array( 'Signed integer', 6 ),
                                 '2e' => array( 'Signed integer', 7 ),
                                 '2f' => array( 'Signed integer', 8 ),

                                 '30' => array( 'Enumeration', 1 ),
                                 '31' => array( 'Enumeration', 2 ),
                                 // 0x32-0x37 Reserved

                                 '38' => array( 'SemiPrecision',   2 ),
                                 '39' => array( 'SinglePrecision', 4 ),
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
                                 'e8' => array( 'Identifier - Attribute ID', 2 ),
                                 'e8' => array( 'Identifier - BACnet OID', 4 ),
                                 // 0xeb-0xef Reserved
                                 'f0' => array( 'Miscellaneous - IEEE address', 8 ),
                                 'f1' => array( 'Miscellaneous - 128 bit security key', 16 ),
                                 // 0xF2-0xFe Reserved
                                 'ff' => array( 'Unknown', 0 ),

        );

        public $parameters_info;

        function __construct() {
            global $argv;

            if ($this->debug["AbeilleParserClass"]) $this->deamonlog("debug", "AbeilleParser constructor");
            $this->parameters_info = Abeille::getParameters();

            // $this->requestedlevel = $argv[7];
            // $this->requestedlevel = '' ? 'none' : $argv[7];
            $this->requestedlevel = "debug";
            
            $this->queueKeyParserToAbeille = msg_get_queue(queueKeyParserToAbeille);
            $this->queueKeyParserToCmd = msg_get_queue(queueKeyParserToCmd);
            $this->queueKeyParserToLQI = msg_get_queue(queueKeyParserToLQI);
        }
        
        function mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => "Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId,
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublish) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublish) could not add message to queue (queueKeyParserToAbeille)");
            }
            
            $msgAbeille->message = array(
                                         'topic' => "Abeille/".$SrcAddr."/Time-TimeStamp",
                                         'payload' => time(),
                                         );
            
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false)) {
                // $this->deamonlog("debug","(fct mqqtPublish) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublish) could not add message to queue (queueKeyParserToAbeille)");
            }
            
            $msgAbeille->message = array(
                                         'topic' => "Abeille/".$SrcAddr."/Time-Time",
                                         'payload' => date("Y-m-d H:i:s"),
                                         );
            
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false)) {
                // $this->deamonlog("debug","(fct mqqtPublish) added to queue (queueKeyParserToAbeille): .json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublish) could not add message to queue (queueKeyParserToAbeille)");
            }
            
            
        }

        function mqqtPublishFct( $SrcAddr, $fct, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => "Abeille/".$SrcAddr."/".$fct,
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishFct) could not add message to queue (queueKeyParserToAbeille)");
            }
            
        }
        
        function mqqtPublishFctToCmd( $fct, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => $fct,
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToCmd, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishFctToCmd) added to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishFctToCmd) could not add message to queue (queueKeyParserToCmd)");
            }
            
        }
        
        function mqqtPublishCmdFct( $fct, $data)
        {
             // Abeille / short addr / Cluster ID - Attr ID -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => $fct,
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishFct) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishFct) could not add message to queue (queueKeyParserToAbeille)");
            }
            
        }

        function mqqtPublishLQI( $Addr, $Index, $data)
        {
            // Abeille / short addr / Cluster ID - Attr ID -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => "LQI/".$Addr."/".$Index,
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToAbeille, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishLQI ParserToAbeille) added to queue (queueKeyParserToAbeille): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishLQI ParserToAbeille) could not add message to queue (queueKeyParserToAbeille)");
            }
            
            if (msg_send( $this->queueKeyParserToLQI, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishLQI ParserToLQI) added to queue (queueKeyParserToLQI): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishLQI ParserToLQI) could not add message to queue (queueKeyParserToLQI)");
            }
            
        }

        function mqqtPublishAnnounce( $SrcAddr, $data)
        {
            // Abeille / short addr / Annonce -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => "CmdAbeille/".$SrcAddr."/Annonce",
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToCmd, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishAnnounce) added to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishAnnounce) could not add message to queue (queueKeyParserToCmd)");
            }
        }

        function mqqtPublishAnnounceProfalux( $SrcAddr, $data)
        {
            // Abeille / short addr / Annonce -> data
            
            $msgAbeille = new MsgAbeille;
            
            $msgAbeille->message = array(
                                         'topic' => "CmdAbeille/".$SrcAddr."/AnnonceProfalux",
                                         'payload' => $data,
                                         );
            
            if (msg_send( $this->queueKeyParserToCmd, 1, $msgAbeille, true, false)) {
                $this->deamonlog("debug","(fct mqqtPublishAnnounceProfalux) added to queue (queueKeyParserToCmd): ".json_encode($msgAbeille));
                // print_r(msg_stat_queue($queue));
            }
            else {
                $this->deamonlog("debug","(fct mqqtPublishAnnounceProfalux) could not add message to queue (queueKeyParserToCmd)");
            }
        }

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
            return 'Cluster ID: '.$cluster.'-'.$GLOBALS['clusterTab']["0x".$cluster] ;
        }

        function Logical( $value ) {
          return hexdec( $value );
        }

        function SinglePrecision( $value ) {
          return unpack('f', pack('H*', $value ))[1];
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
            '97' => "tbdç",
            '98' => "Puissance",        // Based on Aqara Double Relay
            '9b' => "tbd11",
          );

          return $IdName[$id];

        }

        function decodeFF01( $data ) {
          $continue = 1;
          $fields = array();
          if ( strlen( $data ) < 2 ) return $fields;
          while ($continue) {
            $id = $data[0].$data[1];
            $type = $data[2].$data[3];
            $len = $this->dataType[$type][1]*2;
            $value = substr($data, 4, $len );
            $fct = $this->dataType[$type][0];
            if ( method_exists($this, $fct) ) { $valueConverted = $this->$fct($value); } else { $valueConverted = ""; }

            $fields[$this->getFF01IdName( $id )] = array(
              'id' => $id,
              'type' => $type,
              'converter' => $this->dataType[$type][0],
              'value' => $value,
              'valueConverted' => $valueConverted,
            );
            $data = substr( $data, 4+$len );
            if ( strlen($data)<2 ) $continue = 0;
          }
          return $fields;
        }

        function displayStatus($status) {
            $return = "";
            switch ($status) {
                case "00":
                {
                    $return = "00-(Success)";
                }
                    break;
                case "01":
                {
                    $return = "01-(Incorrect Parameters)";
                }
                    break;
                case "02":
                {
                    $return = "02-(Unhandled Command)";
                }
                    break;
                case "03":
                {
                    $return = "03-(Command Failed)";
                }
                    break;
                case "04":
                {
                    $return = "04-(Busy (Node is carrying out a lengthy operation and is currently unable to handle the incoming command) )";
                }
                    break;
                case "05":
                {
                    $return = "05-(Stack Already Started (no new configuration accepted) )";
                }
                    break;
                default:
                {
                    $return = "(ZigBee Error Code unknown): ".$status;
                }
                    break;
            }

            return $return;
        }

        function protocolDatas($datas,  $qos, $clusterTab, &$LQI) {
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

            //$this->deamonlog('info', '-------------- '.date("Y-m-d H:i:s").': protocolData size('.$length.') message > 12 char: '.$datas);

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
            $quality = $datas[10 + ($i * 2) - 2].$datas[10 + ($i * 2) - 1];
            $quality = hexdec( $quality );

            $payloadLength = strlen($payload) - 2;

            //verification du CRC
            if (hexdec($crc) != $crctmp) {
                $this->deamonlog('error',';CRC is not as expected ('.$crctmp.') is '.$crc.' ');
            }

            //Traitement PAYLOAD
            $param1 = "";
            if ( ($type == "8003") || ($type == "8043")) $param1 = $clusterTab;
            if ($type == "804e") $param1=$LQI;
            if ($type == "8102") $param1=$quality;

            $fct = "decode".$type;
            // $this->deamonlog('debug','Calling function: '.$fct);
            if ( method_exists($this, $fct) ) {
                $this->$fct($payload, $ln, $qos, $param1); }
            else {
                $this->deamonlog('debug','Call to a function which doesn t exist: '.$fct);
            }

            return $tab;
        }

        /*--------------------------------------------------------------------------------------------------*/
        /* $this->decode functions
         /*--------------------------------------------------------------------------------------------------*/

        // Device announce
        function decode004d( $payload, $ln, $qos, $dummy)
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
            $test = 2 + 4 + 8;

            $this->deamonlog('debug',';Type; 004d; (Device announce)(Processed->MQTT)'
                             . '; Src Addr : '.substr($payload, 0, 4)
                             . '; IEEE : '.substr($payload, 4, 16)
                             . '; MAC capa : '.substr($payload, 20, 2)   );

            $SrcAddr    = substr($payload,  0,  4);
            $IEEE       = substr($payload,  4, 16);
            $capability = substr($payload, 20,  2);

            // Envoie de la IEEE a Jeedom qui le processera dans la cmd de l objet si celui ci existe deja, sinon sera drop
            $this->mqqtPublish( $SrcAddr, "IEEE", "Addr", $IEEE);

            $this->mqqtPublishFct( "Ruche", "enable", $IEEE);

            // Rafraichi le champ Ruche, JoinLeave (on garde un historique)
            $this->mqqtPublish( "Ruche", "joinLeave", "IEEE", "Annonce->".$IEEE);

            $GLOBALS['NE'][$SrcAddr]['IEEE']                    = $IEEE;
            $GLOBALS['NE'][$SrcAddr]['capa']                    = $capability;
            $GLOBALS['NE'][$SrcAddr]['timeAnnonceReceived']     = time();
            $GLOBALS['NE'][$SrcAddr]['state']                   = 'annonceReceived';

        }
        // Zigate Status
        function decode8000( $payload, $ln, $qos, $dummy)
        {
            $status     = substr($payload, 0, 2);
            $SQN        = substr($payload, 2, 2);
            $PacketType = substr($payload, 4, 4);

            if ($this->debug['8000']) {
                $this->deamonlog('debug',';type; 8000; (Status)(Not Processed)'
                                 . '; Length: '.hexdec($ln)
                                 . '; Status: '.$this->displayStatus($status)
                                 . '; SQN: '.$SQN
                                 . '; PacketType: '.$PacketType  );

                if ( $SQN==0 ) { $this->deamonlog('debug',';type; 8000; SQN: 0 for messages which are not transmitted over the air.'); }
            }

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "8000";
            $data       = $this->displayStatus($status);

            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8001( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8001; (Log)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8002( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8002; (Data indication)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8003( $payload, $ln, $qos, $clusterTab)
        {
            // <source endpoint: uint8_t t>
            // <profile ID: uint16_t>
            // <cluster list: data each entry is uint16_t>

            $sourceEndPoint = substr($payload, 0, 2);
            $profileID      = substr($payload, 2, 4);

            $len = (strlen($payload)-2-4-2)/4;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug',';type; 8003; (Liste des clusters de l’objet)(Not Processed - Just $this->decoded); sourceEndPoint: '.$sourceEndPoint.'; profileID: '.$profileID.'; cluster: '.substr($payload, (6 + ($i*4) ), 4). ' - ' . $clusterTab['0x'.substr($payload, (6 + ($i*4) ), 4)]);
            }
        }

        function decode8004( $payload, $ln, $qos, $dummy)
        {
            // <source endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            // <attribute list: data each entry is uint16_t>

            $sourceEndPoint = substr($payload, 0, 2);
            $profileID      = substr($payload, 2, 4);
            $clusterID      = substr($payload, 6, 4);

            $len = (strlen($payload)-2-4-4-2)/4;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug',';type; 8004; (Liste des Attributs de l’objet)(Not Processed - Just $this->decoded); sourceEndPoint: '.$sourceEndPoint.'; profileID: '.$profileID.'; clusterID: '.$clusterID.'; attribute: '.substr($payload, (10 + ($i*4) ), 4) );
            }
        }

        function decode8005( $payload, $ln, $qos, $dummy)
        {
            // $this->deamonlog('debug',';type: 8005: (Liste des commandes de l’objet)(Not Processed)' );

            // <source endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            //<command ID list:data each entry is uint8_t>

            $sourceEndPoint = substr($payload, 0, 2);
            $profileID      = substr($payload, 2, 4);
            $clusterID      = substr($payload, 6, 4);

            $len = (strlen($payload)-2-4-4-2)/2;
            for ($i = 0; $i < $len; $i++) {
                $this->deamonlog('debug',';type; 8005; (Liste des commandes de l’objet)(Not Processed - Just $this->decoded); sourceEndPoint: '.$sourceEndPoint.'; profileID: '.$profileID.'; clusterID: '.$clusterID.'; commandes: '.substr($payload, (10 + ($i*2) ), 2) );
            }
        }

        function decode8006( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8006; (Non “Factory new” Restart)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8007( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8007; (“Factory New” Restart)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8008( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8008; (“Function inconnue pas dans la doc")(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8009( $payload, $ln, $qos, $dummy)
        {
            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; (Network State response)(Processed->MQTT)'); }

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

            // Envoie Short Address
            $SrcAddr = "Ruche";
            $ClusterId = "Short";
            $AttributId = "Addr";
            $data = $ShortAddress;
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; ZiGate Short Address: '.$ShortAddress); }

            // Envoie Extended Address
            $SrcAddr = "Ruche";
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $data = $ExtendedAddress;
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; IEEE Address: '.$ExtendedAddress); }

            // Envoie PAN ID
            $SrcAddr = "Ruche";
            $ClusterId = "PAN";
            $AttributId = "ID";
            $data = $PAN_ID;
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; PAN ID: '.$PAN_ID); }

            // Envoie Ext PAN ID
            $SrcAddr = "Ruche";
            $ClusterId = "Ext_PAN";
            $AttributId = "ID";
            $data = $Ext_PAN_ID;
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; Ext_PAN_ID: '.$Ext_PAN_ID); }

            // Envoie Channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Channel";
            $data = $Channel;
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; Channel: '.$Channel); }

            if ($this->debug['8009']) { $this->deamonlog('debug',';type; 8009; ; Level: 0x'.substr($payload, 0, 2)); }
        }

        function decode8010( $payload, $ln, $qos, $dummy)
        {
            if ($this->debug['8010']) {
                $this->deamonlog('debug',';type; 8010; (Version)(Processed->MQTT)'
                                 . '; Application : '.hexdec(substr($payload, 0, 4))
                                 . '; SDK : '.hexdec(substr($payload, 4, 4)));
            }
            $SrcAddr = "Ruche";
            $ClusterId = "SW";
            $AttributId = "Application";
            $data = substr($payload, 0, 4);
            if ($this->debug['8010']) { $this->deamonlog("debug", ';type; 8010; '.$AttributId.": ".$data." qos:".$qos); }
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

            $SrcAddr = "Ruche";
            $ClusterId = "SW";
            $AttributId = "SDK";
            $data = substr($payload, 4, 4);
            if ($this->debug['8010']) { $this->deamonlog('debug',';type; 8010; '.$AttributId.': '.$data.' qos:'.$qos); }
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8014( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8014; ( “Permit join” status response)(Processed->MQTT)'
                             . '; Permit Join Status: '.substr($payload, 0, 2));

            // “Permit join” status
            // response Msg Type=0x8014

            // 0 - Off 1 - On
            //<Status: bool_t>
            // Envoi Status

            $SrcAddr = "Ruche";
            $ClusterId = "permitJoin";
            $AttributId = "Status";

            $data = substr($payload, 0, 2);

            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

        }

        function decode8015( $payload, $ln, $qos, $dummy)
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

            $this->deamonlog('debug',';type; 8015; (Abeille List)(Processed->MQTT) Payload: '.$payload);

            $nb = (strlen($payload) - 2) / 26;
            $this->deamonlog('debug','Nombre d\'abeilles: '.$nb);

            for ($i = 0; $i < $nb; $i++) {

                $SrcAddr = substr($payload, $i * 26 + 2, 4);

                // Envoie IEEE
                $ClusterId = "IEEE";
                $AttributId = "Addr";
                $dataAddr = substr($payload, $i * 26 + 6, 16);
                $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $dataAddr);

                // Envoie Power Source
                $ClusterId = "Power";
                $AttributId = "Source";
                $dataPower = substr($payload, $i * 26 + 22, 2);
                $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $dataPower);

                // Envoie Link Quality
                $ClusterId = "Link";
                $AttributId = "Quality";
                $dataLink = hexdec(substr($payload, $i * 26 + 24, 2));
                $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $dataLink);

                $this->deamonlog('debug','type: 8015 (Abeille List) Abeille i: '.$i
                                 . '; ID : '.substr($payload, $i * 26 + 0, 2)
                                 . '; Short Addr : '.$SrcAddr
                                 . '; IEEE Addr: '.$dataAddr
                                 . '; Power Source (0:battery - 1:AC): '.$dataPower
                                 . '; Link Quality: '.$dataLink   );
            }
        }

        function decode8017( $payload, $ln, $qos, $dummy)
        {
            // Get Time server Response (v3.0f)
            // <Timestamp UTC: uint32_t> from 2000-01-01 00:00:00
            $Timestamp = substr($payload, 0, 8);
            $this->deamonlog('debug','type; 8017; (Get Time server Response); Timestamp: '.hexdec($Timestamp) );

            $SrcAddr = "Ruche";
            $ClusterId = "ZiGate";
            $AttributId = "Time";
            $data = date( DATE_RFC2822, hexdec($Timestamp) );
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

        }

        function decode8024( $payload, $ln, $qos, $dummy)
        {
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
            if( substr($payload, 0, 2) == "04" ) { $data = "Network already formed, not starting it again."; }
            if( substr($payload, 0, 2) > "04" ) { $data = "Failed (ZigBee event codes): ".substr($payload, 0, 2); }
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

            // Envoie Short Address
            $SrcAddr = "Ruche";
            $ClusterId = "Short";
            $AttributId = "Addr";
            $dataShort = substr($payload, 2, 4);
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $dataShort);

            // Envoie IEEE Address
            $SrcAddr = "Ruche";
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $dataIEEE = substr($payload, 6,16);
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $dataIEEE);

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Channel";
            $dataNetwork = hexdec( substr($payload,22, 2) );
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $dataNetwork);

            $this->deamonlog('debug',';type; 8024; ( Network joined / formed )(Processed->MQTT); Satus; '.$data.'; short addr : '.$dataShort.'; extended address : '.$dataIEEE.';Channel : '.$dataNetwork);

        }

        function decode8028( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8028; (Authenticate response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode802B( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 802B; (	User Descriptor Notify)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode802C( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 802C; (User Descriptor Response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8030( $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uint8_t>
            // <status: uint8_t>

            $this->deamonlog('debug',';type; 8030; (Bind response)(decoded but Not Processed - Just send time update and status to Network-Bind in Ruche)'
                             . '; SQN: 0x'.substr($payload, 0, 2)
                             . '; Status: 0x'.substr($payload, 2, 2)  );

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Bind";
            $data = date("Y-m-d H:i:s")." Status (00: Ok, <>0: Error): ".substr($payload, 2, 2);
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

        }

        function decode8031( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8031; (unBind response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8034( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8034; (Complex Descriptor response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8040( $payload, $ln, $qos, $dummy)
        {
            // Network Address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>

            $this->deamonlog('debug',';type; 8040; (IEEE Address response)(decoded but Not Processed)'
                             . '; SQN : '                                    .substr($payload, 0, 2)
                             . '; Status : '                                 .substr($payload, 2, 2)
                             . '; IEEE address : '                           .substr($payload, 4,16)
                             . '; short address : '                          .substr($payload,20, 4)
                             . '; number of associated devices : '           .substr($payload,24, 2)
                             . '; start index : '                            .substr($payload,26, 2) );

            if ( substr($payload, 2, 2)!= "00" ) {
                $this->deamonlog('debug',';type; 8040; Don t use this data there is an error, comme info not known');
            }

            for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
                $this->deamonlog('debug',';type; 8040;associated devices: '    .substr($payload, (28 + $i), 4) );
            }

        }

        function decode8041( $payload, $ln, $qos, $dummy)
        {
            // IEEE Address response

            // <Sequence number: uin8_t>
            // <status: uint8_t>
            // <IEEE address: uint64_t>
            // <short address: uint16_t>
            // <number of associated devices: uint8_t>
            // <start index: uint8_t>
            // <device list – data each entry is uint16_t>

            $this->deamonlog('debug',';type; 8041; (IEEE Address response)(decoded but Not Processed)'
                             . '; SQN : '                                    .substr($payload, 0, 2)
                             . '; Status : '                                 .substr($payload, 2, 2)
                             . '; IEEE address : '                           .substr($payload, 4,16)
                             . '; short address : '                          .substr($payload,20, 4)
                             . '; number of associated devices : '           .substr($payload,24, 2)
                             . '; start index : '                            .substr($payload,26, 2) );

            if ( substr($payload, 2, 2)!= "00" ) {
                $this->deamonlog('debug',';type; 8041; Don t use this data there is an error, comme info not known');
            }

            for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
                $this->deamonlog('debug',';type; 8041;associated devices: '    .substr($payload, (28 + $i), 4) );
            }
        }

        function decode8042( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8042; (Node Descriptor response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8043( $payload, $ln, $qos, $clusterTab)
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

            $this->deamonlog('debug',';type; 8043; (Simple Descriptor Response)(Not Processed)'
                             . '; SQN : '             .substr($payload, 0, 2)
                             . '; Status : '          .substr($payload, 2, 2)
                             . '; Short Address : '   .substr($payload, 4, 4)
                             . '; Length : '          .substr($payload, 8, 2)
                             . '; endpoint : '        .substr($payload,10, 2)
                             . '; profile : '         .substr($payload,12, 4) . ' (' . $profileTable[substr($payload,12, 4)] . ')'
                             . '; deviceId : '        .substr($payload,16, 4) . ' (' . $deviceInfo[substr($payload,12, 4)][substr($payload,16, 4)] .')'
                             . '; bitField : '        .substr($payload,20, 2)
                             . '; InClusterCount : '  .substr($payload,22, 2)   );

            $SrcAddr    = substr($payload, 4, 4);
            $EPoint     = substr($payload,10, 2);
            $profile    = substr($payload,12, 4);
            $deviceId   = substr($payload,16, 4);

            for ($i = 0; $i < (intval(substr($payload, 22, 2)) * 4); $i += 4) {
                $this->deamonlog('debug','In cluster: '    .substr($payload, (24 + $i), 4). ' - ' . $clusterTab['0x'.substr($payload, (24 + $i), 4)]);
            }
            $this->deamonlog('debug','OutClusterCount : '  .substr($payload,24+$i, 2));
            for ($j = 0; $j < (intval(substr($payload, 24+$i, 2)) * 4); $j += 4) {
                $this->deamonlog('debug','Out cluster: '    .substr($payload, (24 + $i +2 +$j), 4) . ' - ' . $clusterTab['0x'.substr($payload, (24 + $i +2 +$j), 4)]);
            }

            $data = 'zigbee'.$deviceInfo[$profile][$deviceId];
            if ( strlen( $data) > 1 ) {
                $this->mqqtPublish( $SrcAddr, "SimpleDesc-".$EPoint, "DeviceDescription", $data);
                // if ( isset($GLOBALS['NE'][$SrcAddr]) ) { $GLOBALS['NE'][$SrcAddr]['deviceId']=$deviceInfo[$profile][$deviceId]; }
                $GLOBALS['NE'][$SrcAddr]['deviceId']=$data;
            }

        }

        function decode8044( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug',';type; 8044; (N	Power Descriptor response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        // Active Endpoints Response
        function decode8045( $payload, $ln, $qos, $dummy)
        {
            $SrcAddr = substr($payload, 4, 4);
            $EP = substr($payload, 10, 2);

            $endPointList = "";
            for ($i = 0; $i < (intval(substr($payload, 8, 2)) * 2); $i += 2) {
                // $this->deamonlog('debug','Endpoint : '    .substr($payload, (10 + $i), 2));
                $endPointList = $endPointList . '; '.substr($payload, (10 + $i), 2) ;
            }

            $this->deamonlog('debug',';type; 8045; (Active Endpoints Response)'
                             . '; SQN : '             .substr($payload, 0, 2)
                             . '; Status : '          .substr($payload, 2, 2)
                             . '; Short Address : '   .substr($payload, 4, 4)
                             . '; Endpoint Count : '  .substr($payload, 8, 2)
                             . '; Endpoint List :'    .$endPointList             );

            $GLOBALS['NE'][$SrcAddr]['state']='EndPoint';
            $GLOBALS['NE'][$SrcAddr]['EP']=$EP;

        }

        function decode8046( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', 'Type; 8046; (Match Descriptor response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2)));
        }

        function decode8047( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', 'Type; 8047; (Management Leave response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2)));
        }

        function decode8048( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', ';Type; 8048; (Leave Indication)(Processed->Draft-MQTT)'
                             . '; extended addr : '.substr($payload, 0, 16)
                             . '; rejoin status : '.substr($payload, 16, 2)    );

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

            $data = "Leave->".$name."->".substr($payload, 0, 16)."->".substr($payload, 16, 2);
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

            $SrcAddr = "Ruche";
            $fct = "disable";
            $extendedAddr = substr($payload, 0, 16);
            $this->mqqtPublishFct( $SrcAddr, $fct, $extendedAddr);

        }

        function decode804A( $payload, $ln, $qos, $dummy)
        {
            // app_general_events_handler.c
            // E_SL_MSG_MANAGEMENT_NETWORK_UPDATE_RESPONSE
            $this->deamonlog('debug', 'Type; 804A: (Management Network Update response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))  );
        }

        function decode804B( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', 'Type; 804B; (	System Server Discovery response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))  );
        }


        function decode804E( $payload, $ln, $qos, &$LQI)
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

            // Le paquet contient 2 LQI mais je ne vais en lire qu'un à la fois pour simplifier le code

            $this->deamonlog('debug', ';Type; 804E; (Management LQI response)(decoded but Not Processed)'
                             . '; SQN: '                          .substr($payload, 0, 2)
                             . '; status: '                       .substr($payload, 2, 2)
                             . '; Neighbour Table Entries: '      .substr($payload, 4, 2)
                             . '; Neighbour Table List Count: '   .substr($payload, 6, 2)
                             . '; Start Index: '                  .substr($payload, 8, 2)
                             . '; NWK Address: '                  .substr($payload, 10, 4)
                             . '; Extended PAN ID: '              .substr($payload, 14,16)
                             . '; IEEE Address: '                 .substr($payload, 30,16)
                             . '; Depth: '                 .hexdec(substr($payload, 46, 2))
                             . '; Link Quality: '          .hexdec(substr($payload, 48, 2))
                             . '; Bit map of attributes: '        .substr($payload, 50, 2)   );

            $srcAddress         = 'Not Available Yet Due To ZiGate';
            $index              = substr($payload, 8, 2);
            $NeighbourAddr      = substr($payload, 10, 4);
            $lqi                = hexdec(substr($payload, 48, 2));
            $Depth              = hexdec(substr($payload, 46, 2));
            $bitMapOfAttributes = substr($payload, 50, 2); // to be $this->decoded
            $LQI[$srcAddress]=array($Neighbour=>array('LQI'=>$lqi, 'depth'=>$Depth, 'tree'=>$bitMapOfAttributes, ));

            $data =
            "NeighbourTableEntries="       .substr($payload, 4, 2)
            ."&Index="                      .substr($payload, 8, 2)
            ."&ExtendedPanId="              .substr($payload,14,16)
            ."&IEEE_Address="               .substr($payload,30,16)
            ."&Depth="                      .substr($payload,46, 2)
            ."&LinkQuality="                .substr($payload,48, 2)
            ."&BitmapOfAttributes="         .substr($payload,50, 2);

            // $this->deamonlog('debug', ';Level: 0x'.substr($payload, 0, 2));
            // $this->deamonlog('debug', 'Message: ');
            // $this->deamonlog('debug',$this->hex2str(substr($payload, 2, strlen($payload) - 2)));

            //function $this->mqqtPublishLQI( $Addr, $Index, $data, $qos = 0)
            $this->mqqtPublishLQI( $NeighbourAddr, $index, $data);

            if ( strlen($NeighbourAddr) !=4 ) { return; }

            // On regarde si on connait NWK Address dans Abeille, sinon on va l'interroger pour essayer de le récupérer dans Abeille.
            // Ca ne va marcher que pour les équipements en eveil.
            // CmdAbeille/Ruche/getName address=bbf5&destinationEndPoint=0B
            if ( !Abeille::byLogicalId( 'Abeille/'.$NeighbourAddr, 'Abeille') ) {
                $this->deamonlog('debug', ';Type; 804E; (Management LQI response)(decoded but Not Processed): trouvé '.$NeighbourAddr.' qui n est pas dans Jeedom, essayons de l interroger, si en sommail une intervention utilisateur sera necessaire.');

                /*
                $this->client->publish(substr($this->parameters_info['AbeilleTopic'],0,-1)."CmdAbeille/Ruche/getName",    "address=".$NeighbourAddr."&destinationEndPoint=01", $this->parameters_info['AbeilleQos']);
                $this->client->publish(substr($this->parameters_info['AbeilleTopic'],0,-1)."CmdAbeille/Ruche/getName",    "address=".$NeighbourAddr."&destinationEndPoint=03", $this->parameters_info['AbeilleQos']);
                $this->client->publish(substr($this->parameters_info['AbeilleTopic'],0,-1)."CmdAbeille/Ruche/getName",    "address=".$NeighbourAddr."&destinationEndPoint=0B", $this->parameters_info['AbeilleQos']);
                 */
                $this->mqqtPublishCmdFct( "CmdAbeille/Ruche/getName", "address=".$NeighbourAddr."&destinationEndPoint=01" );
                $this->mqqtPublishCmdFct( "CmdAbeille/Ruche/getName", "address=".$NeighbourAddr."&destinationEndPoint=03" );
                $this->mqqtPublishCmdFct( "CmdAbeille/Ruche/getName", "address=".$NeighbourAddr."&destinationEndPoint=0B" );
            }

        }

        //----------------------------------------------------------------------------------------------------------------
        function decode8060( $payload, $ln, $qos, $dummy)
        {
            // Answer format changed: https://github.com/fairecasoimeme/ZiGate/pull/97
            // Bizard je ne vois pas la nouvelle ligne dans le maaster zigate alors qu elle est dans GitHub

            // <Sequence number:  uint8_t>
            // <endpoint:         uint8_t>
            // <Cluster id:       uint16_t>
            // <status:           uint8_t>  (added only from 3.0f version)
            // <Group id :        uint16_t> (added only from 3.0f version)
            // <Src Addr:         uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type; 8060; (Add a group response)(decoded but Not Processed)'
                             . '; SQN: '           .substr($payload, 0, 2)
                             . '; endPoint: '      .substr($payload, 2, 2)
                             . '; clusterId: '     .substr($payload, 4, 4)
                             . '; status: '        .substr($payload, 8, 2)
                             . '; GroupID: '       .substr($payload,10, 4)
                             . '; srcAddr: '       .substr($payload,14, 4) );
        }

        //----------------------------------------------------------------------------------------------------------------
        function decode8061( $payload, $ln, $qos, $dummy)
        {

        }

        // Get Group Membership response
        function decode8062( $payload, $ln, $qos, $dummy)
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
                $this->deamonlog('debug', ';Type; 8062;group '.$i.'(addr:'.(12+$i*4).'): '  .substr($payload,12+$i*4, 4));
                $groupsId .= '-' . substr($payload,12+$i*4, 4);
            }
            $this->deamonlog('debug', ';Type; 8062;Groups: ->'.$groupsId."<-");

            // $this->deamonlog('debug', ';Level: 0x'.substr($payload, strlen($payload)-2, 2));

            // Envoie Group-Membership
            $SrcAddr = substr($payload, 12+$groupCount*4, 4);
            if ($SrcAddr == "0000" ) { $SrcAddr = "Ruche"; }
            $ClusterId = "Group";
            $AttributId = "Membership";
            if ( $groupsId == "" ) { $data = "none"; } else { $data = $groupsId; }

            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);

            $this->deamonlog('debug', ';Type; 8062; (Group Memebership)(Processed->MQTT)'
                             . '; SQN: '          .substr($payload, 0, 2)
                             . '; endPoint: '     .substr($payload, 2, 2)
                             . '; clusterId: '    .substr($payload, 4, 4)
                             . '; capacity: '     .substr($payload, 8, 2)
                             . '; group count: '  .substr($payload,10, 2)
                             . '; groups: '       .$data
                             . '; source: '       .$SrcAddr );

        }

        function decode8063( $payload, $ln, $qos, $dummy)
        {

            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <Group id: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)

            $this->deamonlog('debug', 'Type; 8063; (Remove a group response)(decoded but Not Processed)'
                             . '; SQN: '          .substr($payload, 0, 2)
                             . '; endPoint: '     .substr($payload, 2, 2)
                             . '; clusterId: '    .substr($payload, 4, 4)
                             . '; statusId: '     .substr($payload, 8, 2)
                             . '; groupId: '      .substr($payload,10, 4)
                             . '; sourceId: '     .substr($payload,14, 4) );
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

        function decode8084( $payload, $ln, $qos, $dummy) {
            // J ai eu un crash car le soft cherchait cette fonction mais elle n'est pas documentée...
        }

        function decode8085( $payload, $ln, $qos, $dummy)
        {

            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <addr: uint16_t>             -> 4
            // <cmd: uint8>                 -> 2

            // 2: 'click', 1: 'hold', 3: 'release'

            $this->deamonlog('debug', ';Type; 8085; (Remote button pressed (ClickHoldRelease) a group response)(decoded but Not Processed)'
                             . '; SQN: '           .substr($payload, 0, 2)
                             . '; endPoint: '      .substr($payload, 2, 2)
                             . '; clusterId: '     .substr($payload, 4, 4)
                             . '; address_mode: '  .substr($payload, 8, 2)
                             . '; source addr: '   .substr($payload,10, 4)
                             . '; cmd: '           .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Up";
            $AttributId     = "Down";
            $data           = substr($payload,14, 2);

            $this->mqqtPublish( $source, $ClusterId, $AttributId, $data);
        }


        function decode8095( $payload, $ln, $qos, $dummy)
        {

            // <Sequence number: uin8_t>    -> 2
            // <endpoint: uint8_t>          -> 2
            // <Cluster id: uint16_t>       -> 4
            // <address_mode: uint8_t>      -> 2
            // <source addr: uint16_t>      -> 4
            // <cmd: uint8>                 -> 2

            $this->deamonlog('debug', ';Type; 8095; (Remote button pressed (ONOFF_UPDATE) a group response)(decoded but Not Processed)'
                             . '; SQN: '          .substr($payload, 0, 2)
                             . '; endPoint: '     .substr($payload, 2, 2)
                             . '; clusterId: '    .substr($payload, 4, 4)
                             . '; statusId: '     .substr($payload, 8, 2)
                             . '; sourceAddr: '   .substr($payload,10, 4)
                             . '; cmd: '          .substr($payload,14, 2) );

            $source         = substr($payload,10, 4);
            $ClusterId      = "Click";
            $AttributId     = "Middle";
            $data           = substr($payload,14, 2);

            $this->mqqtPublish( $source, $ClusterId, $AttributId, $data);
        }
        //----------------------------------------------------------------------------------------------------------------
        ##TODO
        #reponse scene
        #80a0-80a6
        function decode80a0( $payload, $ln, $qos, $dummy)
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


            $this->deamonlog('debug', ';Type; 80A0 (Scene View)(decoded but not Processed)'
                             . '; SQN: '                           .substr($payload, 0, 2)
                             . '; endPoint: '                      .substr($payload, 2, 2)
                             . '; clusterId: '                     .substr($payload, 4, 4)
                             . '; status: '                        .substr($payload, 8, 2)

                             . '; group ID: '                      .substr($payload,10, 4)
                             . '; scene ID: '                      .substr($payload,14, 2)
                             . '; transition time: '               .substr($payload,16, 4)

                             . '; scene name lenght: '             .substr($payload,20, 2)  // Osram Plug repond 0 pour lenght et rien apres.
                             . '; scene name max lenght: '         .substr($payload,22, 2)
                             . '; scene name : '                   .substr($payload,24, 2)

                             . '; scene extensions lenght: '       .substr($payload,26, 4)
                             . '; scene extensions max lenght: '   .substr($payload,30, 4)
                             . '; scene extensions : '             .substr($payload,34, 2) );

        }

        function decode80a1( $payload, $ln, $qos, $dummy)
        {

        }

        function decode80a2( $payload, $ln, $qos, $dummy)
        {

        }

        function decode80a3( $payload, $ln, $qos, $dummy)
        {

            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <Src Addr: uint16_t> (added only from 3.0f version)


            $this->deamonlog('debug', ';Type: 80A3; (Remove All Scene)(decoded but not Processed)'
                             . '; SQN: '          .substr($payload, 0, 2)
                             . '; endPoint: '     .substr($payload, 2, 2)
                             . '; clusterId: '    .substr($payload, 4, 4)
                             . '; status: '       .substr($payload, 8, 2)
                             . '; group ID: '     .substr($payload,10, 4)
                             . '; source: '       .substr($payload,14, 4)  );

        }

        function decode80a4( $payload, $ln, $qos, $dummy)
        {

            // <sequence number: uint8_t>   -> 2
            // <endpoint : uint8_t>         -> 2
            // <cluster id: uint16_t>       -> 4
            // <status: uint8_t>            -> 2
            // <group ID: uint16_t>         -> 4
            // <scene ID: uint8_t>          -> 2
            // <Src Addr: uint16_t> (added only from 3.0f version)


            $this->deamonlog('debug', ';Type; 80A3; (Store Scene Response)(decoded but not Processed)'
                             . '; SQN: '          .substr($payload, 0, 2)
                             . '; endPoint: '     .substr($payload, 2, 2)
                             . '; clusterId: '    .substr($payload, 4, 4)
                             . '; status: '       .substr($payload, 8, 2)
                             . '; group ID: '     .substr($payload,10, 4)
                             . '; scene ID: '     .substr($payload,14, 2)
                             . '; source: '       .substr($payload,16, 4)  );

        }

        function decode80a6( $payload, $ln, $qos, $dummy)
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



                $this->deamonlog('debug', ';Type; 80A6; (Scene Membership)(Processed->$this->decoded but not sent to MQTT)'
                                 . '; SQN: '          .substr($payload, 0, 2)      // 1
                                 . '; endPoint: '     .substr($payload, 2, 2)      // 1
                                 . '; clusterId: '    .substr($payload, 4, 4)      // 1
                                 . '; status: '       .substr($payload, 8, 2)      //
                                 // . '; capacity: '     .substr($payload,10, 2)
                                 . '; group ID: '     .substr($payload,10, 4)
                                 . '; scene ID: '     .substr($payload,14, 2)  );
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
                    $this->deamonlog('debug', ';Type; 80A6; (Scene Membership)(Processed->$this->decoded but not sent to MQTT) => Status NOT null'
                                     . '; SQN: '          .substr($payload, 0, 2)      // 1
                                     . '; endPoint: '     .substr($payload, 2, 2)      // 1
                                     . '; source: '       .$source
                                     . '; clusterId: '    .substr($payload, 4, 4)      // 1
                                     . '; status: '       .substr($payload, 8, 2)      //
                                     . '; capacity: '     .substr($payload,10, 2)
                                     . '; group ID: '     .substr($payload,10, 4)
                                     . '; scene ID: '     .substr($payload,14, 2)  );
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

                $this->deamonlog('debug', ';Type; 80A6; (Scene Membership)(Processed->$this->decoded but not sent to MQTT)'
                                 . '; SQN: '          .$seqNumber
                                 . '; endPoint: '     .$endpoint
                                 . '; clusterId: '    .$clusterId
                                 . '; status: '       .$status
                                 . '; capacity: '     .$capacity
                                 . '; source: '       .$source
                                 . '; group ID: '     .$groupID
                                 . '; scene ID: '     .$sceneId
                                 . '; Group-Scenes: ->' . $data . "<-" );

                // Je ne peux pas envoyer, je ne sais pas qui a repondu pour tester je mets l adresse en fixe d une ampoule
                $ClusterId = "Scene";
                $AttributId = "Membership";
                $this->mqqtPublish( $source, $ClusterId, $AttributId, $data);

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

        function decode80a7( $payload, $ln, $qos, $dummy)
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


            $this->deamonlog('debug', ';Type; 80a7; (Remote button pressed (LEFT/RIGHT))(Processed->$this->decoded but not sent to MQTT)'
                             . '; SQN: '          .$seqNumber
                             . '; endPoint: '     .$endpoint
                             . '; clusterId: '    .$clusterId
                             . '; cmd: '          .$cmd
                             . '; direction: '    .$direction
                             . '; u8Attr1: '      .$attr1
                             . '; u8Attr2: '      .$attr2
                             . '; u8Attr3: '      .$attr3
                             . '; source: '       .$source );

            $clusterId = "80A7";
            $AttributId = "Cmd";
            $data = $cmd;
            $this->mqqtPublish( $source, $clusterId, $AttributId, $data);

            $clusterId = "80A7";
            $AttributId = "Direction";
            $data = $direction;
            $this->mqqtPublish( $source, $clusterId, $AttributId, $data);

        }
        //----------------------------------------------------------------------------------------------------------------


        #Reponse Attributs
        #8100-8140

        function decode8100( $payload, $ln, $qos, $dummy)
        {
            // "Type: 0x8100 (Read Attrib Response)"
            // 8100 000D0C0Cb32801000600000010000101
            $this->deamonlog('debug', 'Type; 0x8100; (Read Attrib Response)(Processed->MQTT)'
                             . '; SQN: '.substr($payload, 0, 2)
                             . '; Src Addr: '.substr($payload, 2, 4)
                             . '; EnPt: '.substr($payload, 6, 2)
                             . '; Cluster Id: '.substr($payload, 8, 4)
                             . '; Attribut Id: '.substr($payload, 12, 4)
                             . '; Attribute Status: '.substr($payload, 16, 2)
                             . '; Attribute data type: '.substr($payload, 18, 2) );

            $dataType = substr($payload, 18, 2);
            // IKEA OnOff state reply data type: 10
            // IKEA Manufecturer name data type: 42
            /*
             $this->deamonlog('Syze of Attribute: '.substr($payload, 20, 4));
             $this->deamonlog('Data byte list (one octet pour l instant): '.substr($payload, 24, 2));
             */
            $this->deamonlog('debug', 'Syze of Attribute: '.substr($payload, 20, 4));
            $this->deamonlog('debug', 'Data byte list (one octet pour l instant): '.substr($payload, 24, 2));

            // short addr / Cluster ID / EP / Attr ID -> data
            $SrcAddr    = substr($payload, 2, 4);
            $ClusterId  = substr($payload, 8, 4);
            $EP         = substr($payload, 6, 2);
            $AttributId = substr($payload, 12, 4);

            // valeur hexadécimale	- type -> function
            // 0x00	Null
            // 0x10	boolean                 -> hexdec
            // 0x18	8-bit bitmap
            // 0x20	uint8	unsigned char   -> hexdec
            // 0x21	uint16
            // 0x22	uint32
            // 0x25	uint48
            // 0x28	int8
            // 0x29	int16
            // 0x2a	int32
            // 0x30	Enumeration : 8bit
            // 0x42	string                  -> hex2bin
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
            $this->deamonlog('debug','Type; 0x8100;Data byte: '.$data);

            $this->mqqtPublish( $SrcAddr, $ClusterId, $EP.'-'.$AttributId, $data);
        }

        function decode8101( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', ';Type; 8101; (Default Response)(Not Processed)'
                             . '; Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.'
                             . '; SQN : '.substr($payload, 0, 2)
                             . '; EndPoint : '.substr($payload, 2, 2)
                             . '; '. $this->displayClusterId(substr($payload, 4, 4))
                             . '; Command : '.substr($payload, 8, 2)
                             . '; Status : '.substr($payload, 10, 2)  );
        }

        function decode8102( $payload, $ln, $qos, $quality)
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
            $ClusterId          = substr($payload, 8, 4);
            $EPoint             = substr($payload, 6, 2);
            $AttributId         = substr($payload,12, 4);
            $AttributStatus     = substr($payload,16, 2);
            $dataType           = substr($payload,18, 2);
            $AttributSize       = substr($payload,20, 4);

            $this->mqqtPublish( $SrcAddr, 'Link', 'Quality', $quality);


            // 0005: ModelIdentifier
            // 0010: Piece (nom utilisé pour Profalux)
            if ( ($ClusterId=="0000") && ( ($AttributId=="0005") || ($AttributId=="0010") ) ) {
                $this->deamonlog('debug', ';Type; 8102; (Attribut Report)(Processed->MQTT)'
                                 . '; SQN: '              .$SQN
                                 . '; Src Addr : '        .$SrcAddr
                                 . '; End Point : '       .$EPoint
                                 . '; Cluster ID : '      .$ClusterId
                                 . '; Attr ID : '         .$AttributId
                                 . '; Attr Status : '     .$AttributStatus
                                 . '; Attr Data Type : '  .$dataType
                                 . '; Attr Size : '       .$AttributSize
                                 . '; Data byte list : ->'  .pack('H*', substr($payload, 24, (strlen($payload) - 24 - 2)) ).'<-' );
            }
            else {
                $this->deamonlog('debug', ';Type; 8102; (Attribut Report)(Processed->MQTT)'
                                 . '; SQN: '              .$SQN
                                 . '; Src Addr : '        .$SrcAddr
                                 . '; End Point : '       .$EPoint
                                 . '; Cluster ID : '      .$ClusterId
                                 . '; Attr ID : '         .$AttributId
                                 . '; Attr Status : '     .$AttributStatus
                                 . '; Attr Data Type : '  .$dataType
                                 . '; Attr Size : '       .$AttributSize
                                 . '; Data byte list : '  .substr($payload, 24, (strlen($payload) - 24 - 2))  );
            }


            // valeur hexadécimale	- type -> function
            // 0x00	Null
            // 0x10	boolean                 -> hexdec
            // 0x18	8-bit bitmap
            // 0x20	uint8	unsigned char   -> hexdec
            // 0x21	uint16                  -> hexdec
            // 0x22	uint32
            // 0x24 ???
            // 0x25	uint48
            // 0x28	int8                    -> hexdec(2)
            // 0x29	int16                   -> unpack("s", pack("s", hexdec(
            // 0x2a	int32                   -> unpack("l", pack("l", hexdec(
            // 0x2b ????32
            // 0x30	Enumeration : 8bit
            // 0x42	string                  -> hex2bin

            if ($dataType == "10") {
                $data = hexdec(substr($payload, 24, 2));
            }

            if ($dataType == "18") {
                $data = substr($payload, 24, 2);
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
                        $this->mqqtPublish( $SrcAddr, '000C',     '01-0055',    $puissanceValue,    $qos);
                    }
                    if ($EPoint=="02") {
                    // Remontée puissance (instantannée) de la prise xiaomi et relay double switch 2
                    // On va envoyer ca sur la meme variable que le champ ff01
                    $hexNumber = substr($payload, 24, 8);
                    $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
                    $bin = pack('H*', $hexNumberOrder );
                    $data = unpack("f", $bin )[1];

                    $puissanceValue = $data;
                    $this->mqqtPublish( $SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $qos);

                    // Relay Double
                    $this->mqqtPublish( $SrcAddr, '000C',     '02-0055',    $puissanceValue,    $qos);
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
                    $this->deamonlog("debug",";Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Bouton Carre)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', $SrcAddr.': Voltage: '.$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage, $qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ), $qos);

                }

                // Xiaomi lumi.sensor_86sw1 (Wall 1 Switch sur batterie)
                elseif (($AttributId == "ff01") && ($AttributSize == "001b")) {
                    $this->deamonlog("debug",";Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall 1 Switch, Gaz Sensor)" );
                    // Dans le cas du Gaz Sensor, il n'y a pas de batterie alors le decodage est probablement faux.

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat           = substr($payload, 80, 2);

                    $this->deamonlog('debug', $SrcAddr.': Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ).' Etat: '         .$etat);

                    $this->mqqtPublish( $SrcAddr, '0006',     '01-0000', $etat,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                }

                // Xiaomi Door Sensor V2
                elseif (($AttributId == "ff01") && ($AttributSize == "001d")) {
                    $this->deamonlog("debug",";Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Door Sensor)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $etat           = substr($payload, 80, 2);

                    $this->deamonlog('debug', $SrcAddr.': Door V2 Voltage: '   .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ).' Door V2 Etat: '      .$etat);

                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,  $qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ));
                    $this->mqqtPublish( $SrcAddr, '0006', '01-0000', $etat,  $qos);
                }

                // Xiaomi capteur temperature rond V1 / lumi.sensor_86sw2 (Wall 2 Switches sur batterie)
                elseif (($AttributId == "ff01") && ($AttributSize == "001f")) {
                    $this->deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Temperature Rond/Wall 2 Switch)');

                    $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity = hexdec( substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2) );

                    $this->deamonlog('debug', ';Type; 8102; Address:'.$SrcAddr.'; Voltage: '.$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ).'; Temperature: '.$temperature.'; Humidity: '.$humidity );
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos );
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                    $this->mqqtPublish( $SrcAddr, '0402', '01-0000', $temperature,$qos);
                    $this->mqqtPublish( $SrcAddr, '0405', '01-0000', $humidity,$qos);

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
                    $this->deamonlog('debug',';Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Presence V2)');

                    $voltage        = hexdec(substr($payload, 28+2, 2).substr($payload, 28, 2));
                    $lux            = hexdec(substr($payload, 86+2, 2).substr($payload, 86, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', ';Type; 8102;'.$SrcAddr.': Voltage; '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ).' Lux; '          .$lux);
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);
                    $this->mqqtPublish( $SrcAddr, '0400', '01-0000', $lux,$qos); // Luminosite

                    // $this->mqqtPublish( $SrcAddr, '0402', '0000', $temperature,      $qos);
                    // $this->mqqtPublish( $SrcAddr, '0405', '0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);

                }

                // Xiaomi capteur Inondation
                elseif (($AttributId == 'ff01') && ($AttributSize == "0022")) {
                    $this->deamonlog('debug',';Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Inondation)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                    $etat = substr($payload, 88, 2);

                    $this->deamonlog('debug', ';Type; 8102;'.$SrcAddr.': Inondation Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ).' Inondation Etat: '      .$etat);
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                    // $this->mqqtPublish( $SrcAddr, '0402', '0000', $temperature,      $qos);
                    // $this->mqqtPublish( $SrcAddr, '0405', '0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);

                }

                // Xiaomi capteur temperature carré V2
                elseif (($AttributId == 'ff01') && ($AttributSize == "0025")) {
                    $this->deamonlog('debug',';Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Temperature Carré)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', ';Type; 8102; Address:'.$SrcAddr.'; ff01/25: Voltage: '.$voltage.'; Pourcent: '.$this->volt2pourcent( $voltage ).'; ff01/25: Temperature: '.$temperature.'; ff01/25: Humidity: '.$humidity.'; ff01/25: Pression: '.$pression);
                    // $this->deamonlog('debug', 'ff01/25: Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'ff01/25: Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'ff01/25: Pression: '     .$pression);

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                    $this->mqqtPublish( $SrcAddr, '0402', '01-0000', $temperature,      $qos);
                    $this->mqqtPublish( $SrcAddr, '0405', '01-0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);

                }

                // Xiaomi Smoke Sensor
                elseif (($AttributId == 'ff01') && ($AttributSize == "0028")) {
                    $this->deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Sensor Smoke)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', ';Type; 8102;'.$SrcAddr.': Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                }

                // Xiaomi Cube
                // Xiaomi capteur Inondation
                elseif (($AttributId == 'ff01') && ($AttributSize == "002a")) {
                    $this->deamonlog('debug',';Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Cube)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', ';Type; 8102;'.$SrcAddr.'Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ));
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                    // $this->mqqtPublish( $SrcAddr, '0402', '0000', $temperature,      $qos);
                    // $this->mqqtPublish( $SrcAddr, '0405', '0000', $humidity,         $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                    // $this->mqqtPublish( $SrcAddr, '0403', '0000', $pression / 100,   $qos);

                }

                // Xiaomi Vibration
                elseif (($AttributId == 'ff01') && ($AttributSize == "002e")) {
                    $this->deamonlog('debug',';Type; 8102; Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Vibration)');

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                    // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                    // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                    // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));

                    $this->deamonlog('debug', ';Type; 8102; '.$SrcAddr.': Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ));
                    // $this->deamonlog('debug', 'Temperature: '  .$temperature);
                    // $this->deamonlog('debug', 'Humidity: '     .$humidity);
                    // $this->deamonlog('debug', 'Pression: '     .$pression);

                    $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId,'$this->decoded as Volt-Temperature-Humidity',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                }

                // Xiaomi Wall Plug (Kiwi: ZNCZ02LM, rvitch: )
                elseif (($AttributId == "ff01") && (($AttributSize == "0031") || ($AttributSize == "002b") )) {
                    $logMessage = "";
                    // $this->deamonlog('debug', ';Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall Plug)');
                    $logMessage .= ";Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall Plug)";

                    $onOff = hexdec(substr($payload, 24 + 2 * 2, 2));

                    $puissance = unpack('f', pack('H*', substr($payload, 24 + 8 * 2, 8)));
                    $puissanceValue = $puissance[1];

                    $conso = unpack('f', pack('H*', substr($payload, 24 + 14 * 2, 8)));
                    $consoValue = $conso[1];

                    // $this->mqqtPublish($SrcAddr,$ClusterId,$AttributId,'$this->decoded as OnOff-Puissance-Conso',$qos);
                    $this->mqqtPublish( $SrcAddr, 'Xiaomi',  '0006-00-0000',     $onOff,             $qos);
                    $this->mqqtPublish( $SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $qos);
                    $this->mqqtPublish( $SrcAddr, 'tbd',     '--conso--',        $consoValue,        $qos);

                    $logMessage .= ';OnOff: '.$onOff.';Puissance: '.$puissanceValue.';Consommation: '.$consoValue;
                    $this->deamonlog('debug', $logMessage);
                }

                // Xiaomi Double Relay (Kiwi:  )
                elseif ( ($AttributId == "ff01") && ($AttributSize == "0044") ) {
                    $FF01 = $this->decodeFF01(substr($payload, 24));

                    $this->mqqtPublish( $SrcAddr, '0006',  '01-0000',       $FF01["Etat SW 1 Binaire"]["valueConverted"], $qos);
                    $this->mqqtPublish( $SrcAddr, '0006',  '02-0000',       $FF01["Etat SW 2 Binaire"]["valueConverted"], $qos);
                    $this->mqqtPublish( $SrcAddr, '000C',  '01-0055',       $FF01["Puissance"]["valueConverted"],         $qos);
                    $this->mqqtPublish( $SrcAddr, 'tbd',   '--conso--',     $FF01["Consommation"]["valueConverted"],      $qos);

                    $this->deamonlog('debug', ";Type; 8102; Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Relay Double):".json_encode($FF01));
                }

                // Xiaomi Capteur Presence
                // Je ne vois pas ce message pour ce cateur et sur appui lateral il n envoie rien
                // Je mets un Attribut Size a XX en attendant. Le code et la il reste juste a trouver la taille de l attribut si il est envoyé.
                elseif (($AttributId == "ff01") && ($AttributSize == "00XX")) {
                    $this->deamonlog("debug",";Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Bouton Carre)" );

                    $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));

                    $this->deamonlog('debug', ';Type; 8102;'.$SrcAddr.': Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

                }

                // Xiaomi Presence Infrarouge IR V1 / Bouton V1 Rond
                elseif (($AttributId == "ff02")) {
                    // Non decodé a ce stade
                    // $this->deamonlog("debug", "Champ 0xFF02 non $this->decode a ce stade");
                    $this->deamonlog("debug",";Type; 8102;Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (IR V1)" );

                    $voltage        = hexdec(substr($payload, 24 +  8, 2).substr($payload, 24 + 6, 2));

                    $this->deamonlog('debug', $SrcAddr.': Voltage: '      .$voltage.' Pourcent: '.$this->volt2pourcent( $voltage ));

                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                    $this->mqqtPublish( $SrcAddr, 'Batterie', 'Pourcent', $this->volt2pourcent( $voltage ),$qos);

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
                    // $this->deamonlog("debug",";Type; 8102; Champ proprietaire Philips Hue, decodons le et envoyons a Abeille les informations ->".pack('H*', substr($payload, 24+2, (strlen($payload) - 24 - 2)) )."<-" );
                    $button = $AttributId;
                    $buttonEvent = substr($payload, 24+2, 2 );
                    $buttonDuree = hexdec(substr($payload, 24+6, 2 ));
                    $this->deamonlog("debug",";Type; 8102; Champ proprietaire Philips Hue, decodons le et envoyons a Abeille les informations, Bouton: ".$button." Event: ".$buttonEvent." Event Texte: ".$buttonEventTexte[$buttonEvent]." et duree: ".$buttonDuree);

                    $this->mqqtPublish( $SrcAddr, $ClusterId."-".$EPoint, $AttributId."-Event", $buttonEvent);
                    $this->mqqtPublish( $SrcAddr, $ClusterId."-".$EPoint, $AttributId."-Duree", $buttonDuree);

                }
                // ------------------------------------------------------- Tous les autres cas ----------------------------------------------------------
                else {
                    $data = hex2bin(substr($payload, 24, (strlen($payload) - 24 - 2))); // -2 est une difference entre ZiGate et NXP Controlleur pour le LQI.
                }
            }

            if (isset($data)) {
                if ( strpos($data, "sensor_86sw2")>2 ) { $data="lumi.sensor_86sw2"; } // Verrue: getName = lumi.sensor_86sw2Un avec probablement des caractere cachés alors que lorsqu'il envoie son nom spontanement c'est lumi.sensor_86sw2 ou l inverse, je ne sais plus
                $this->mqqtPublish( $SrcAddr, $ClusterId."-".$EPoint, $AttributId, $data);
            }

            // Si nous recevons le modelIdentifer ou le location en phase d'annonce d un equipement, nous envoyons aussi le short address et IEEE
            if ( isset($GLOBALS['NE'][$SrcAddr]) ) {
                if ( $GLOBALS['NE'][$SrcAddr]['action']=="ActiveEndPointReceived->modelIdentifier") {
                    if ( ($ClusterId=="0000") && ( $AttributId=="0005" ) && ( strlen($data)>1 ) ) {
                        $GLOBALS['NE'][$SrcAddr]['state'] = "modelIdentifier";
                        $GLOBALS['NE'][$SrcAddr]['modelIdentifier']=$data;
                    }
                }
                if ($GLOBALS['NE'][$SrcAddr]['action']=="modelIdentifierReceived->location") {
                    if ( ($ClusterId=="0000") && ( $AttributId=="0010" ) && ( strlen($data)>1 ) ) {
                        $GLOBALS['NE'][$SrcAddr]['state'] = "modelIdentifier";
                        $GLOBALS['NE'][$SrcAddr]['location']=$data;
                    }
                }
            }
        }

        function decode8110( $payload, $ln, $qos, $dummy)
        {
            $this->deamonlog('debug', ';Type; 8110; (	Write Attribute Response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        function decode8120( $payload, $ln, $qos, $dummy)
        {
            // <Sequence number: uint8_t>
            // <Src address : uint16_t>
            // <Endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <Attribute Enum: uint16_t> (add in v3.0f)
            // <Status: uint8_t>

            $this->deamonlog('debug', ';type; 8120; (Configure Reporting response)(decoded but not Processed)'
                             . '; SQN: '              .substr($payload, 0, 2)
                             . '; Source address: '   .substr($payload, 2, 4)
                             . '; EndPoint: '         .substr($payload, 6, 2)
                             . '; Cluster Id: '       .substr($payload, 8, 4)
                             . '; Attribute: '        .substr($payload,12, 4)
                             . '; Status: '           .substr($payload,16, 2)  );

            // Envoie channel
            $SrcAddr = "Ruche";
            $ClusterId = "Network";
            $AttributId = "Report";
            $data = date("Y-m-d H:i:s")." Attribut: ".substr($payload,12, 4)." Status (00: Ok, <>0: Error): ".substr($payload,16, 2);
            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
        }

        function decode8140( $payload, $ln, $qos, $dummy)
        {
            // Some changes in this message so read: https://github.com/fairecasoimeme/ZiGate/pull/90
            $this->deamonlog('debug', 'Type; 8140; (Configure Reporting response)(Not Processed)'
                             . '; (Not processed*************************************************************)'
                             . '; Level: 0x'.substr($payload, 0, 2)
                             . '; Message: '.$this->hex2str(substr($payload, 2, strlen($payload) - 2))   );
        }

        // Codé sur la base des messages Xiaomi Inondation
        function decode8401( $payload, $ln, $qos, $dummy)
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

            $this->deamonlog('debug', ';Type; 8401; (IAS Zone status change notification )(Processed)'
                             . '; SQN: '               .substr($payload, 0, 2)
                             . '; endpoint: '          .substr($payload, 2, 2)
                             . '; cluster id: '        .substr($payload, 4, 4)
                             . '; src address mode: '  .substr($payload, 8, 2)
                             . '; src address: '       .substr($payload,10, 4)
                             . '; zone status: '       .substr($payload,14, 4)
                             . '; extended status: '   .substr($payload,18, 2)
                             . '; zone id: '           .substr($payload,20, 2)
                             . '; delay: '             .substr($payload,22, 4)  );

            $SrcAddr    = substr($payload,10, 4);
            $ClusterId  = substr($payload, 4, 4);
            $EP         = substr($payload, 2, 2);
            $AttributId = "0000";
            $data       = substr($payload,14, 4);

            // On transmettre l info sur Cluster 0500 et Cmd: 0000 (Jusqu'a present on etait sur ClusterId-AttributeId, ici ClusterId-CommandId)
            $this->mqqtPublish( $SrcAddr, $ClusterId, $EP.'-'.$AttributId, $data);

        }


        function decode8701( $payload, $ln, $qos, $dummy)
        {
            // NWK Code Table Chap 10.2.3 from JN-UG-3113
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            
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
            
            $nwkCode = array (
            "00" => array( "ZPS_NWK_ENUM_SUCCESS", "Success", ),
            "c1" => array( "ZPS_NWK_ENUM_INVALID_PARAMETER", "An invalid or out-of-range parameter has been passed", ),
            "c2" => array( "ZPS_NWK_ENUM_INVALID_REQUEST", "Request cannot be processed", ),
            "c3" => array( "ZPS_NWK_ENUM_NOT_PERMITTED", "NLME-JOIN.request not permitted", ),
            "c4" => array( "ZPS_NWK_ENUM_STARTUP_FAILURE", "NLME-NETWORK-FORMATION.request failed", ),
            "c5" => array( "ZPS_NWK_ENUM_ALREADY_PRESENT", "NLME-DIRECT-JOIN.request failure - device already present", ),
            "c6" => array( "ZPS_NWK_ENUM_SYNC_FAILURE", "NLME-SYNC.request has failed", ),
            "c7" => array( "ZPS_NWK_ENUM_NEIGHBOR_TABLE_FULL", "NLME-DIRECT-JOIN.request failure - no space in Router table", ),
            "c8" => array( "ZPS_NWK_ENUM_UNKNOWN_DEVICE", "NLME-LEAVE.request failure - device not in Neighbour table", ),
            "c9" => array( "ZPS_NWK_ENUM_UNSUPPORTED_ATTRIBUTE", "NLME-GET/SET.request unknown attribute identifier", ),
            "ca" => array( "ZPS_NWK_ENUM_NO_NETWORKS", "NLME-JOIN.request detected no networks", ),
            "cb" => array( "ZPS_NWK_ENUM_RESERVED_1", "Reserved", ),
            "cc" => array( "ZPS_NWK_ENUM_MAX_FRM_CTR", "Security processing has failed on outgoing frame due to maximum frame counter", ),
            "cd" => array( "ZPS_NWK_ENUM_NO_KEY", "Security processing has failed on outgoing frame due to no key", ),
            "ce" => array( "ZPS_NWK_ENUM_BAD_CCM_OUTPUT", "Security processing has failed on outgoing frame due CCM", ),
            "cf" => array( "ZPS_NWK_ENUM_NO_ROUTING_CAPACITY", "Attempt at route discovery has failed due to lack of table space", ),
            "d0" => array( "ZPS_NWK_ENUM_ROUTE_DISCOVERY_FAILED", "Attempt at route discovery has failed due to any reason except lack of table space (KiwiHC16: exemple no body response to a route request to a specific short address = no route known to this address, NE removed ? NE out of cobverage ?)", ),
            "d1" => array( "ZPS_NWK_ENUM_ROUTE_ERROR", "NLDE-DATA.request has failed due to routing failure on sending device", ),
            "d2" => array( "ZPS_NWK_ENUM_BT_TABLE_FULL", "Broadcast or broadcast-mode multicast has failed as there is no room in BTT", ),
            "d3" => array( "ZPS_NWK_ENUM_FRAME_NOT_BUFFERED", "Unicast  mode  multi-cast  frame  was  discarded pending route discovery", ),
                              );
            
            // $status = substr($payload, 0, 2);
            // $nwkStatus = substr($payload, 2, 2);
            // D apres https://github.com/fairecasoimeme/ZiGate/issues/92 il est fort possible que les deux status soient inversés
            $nwkStatus = substr($payload, 0, 2);
            $status = substr($payload, 2, 2);
            
            
            $this->deamonlog('debug', ';type; 8701; (Route Discovery Confirm)(decoded but Not Processed)'
                             . '; MAC Status: '.$status.' ('.$macCode[$status][0].'->'.$macCode[$status][1].')'
                             . '; Nwk Status: '.$nwkStatus.' ('.$nwkCode[$nwkStatus][0].'->'.$nwkCode[$nwkStatus][1].')'  );
        }

        function decode8702( $payload, $ln, $qos, $dummy)
        {
            // APS Code Table Chap 10.2.2 from JN-UG-3113
            // Cette table ne semble pas etre la bonne car je recois un d4 comme status
            // AbeilleParser 2019-04-26 11:58:06[debug];type; 8702; (APS Data Confirm Fail); Status : d4 (->); Source Endpoint : 01; Destination Endpoint : 0B; Destination Mode : 02; Destination Address : bbf5; SQN: : 00
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

            $status = substr($payload, 0, 2);
            
            $this->deamonlog('debug', ';type; 8702; (APS Data Confirm Fail)'
                             . '; Status : '.$status.' ('.$apsCode[$status][1].'->'.$apsCode[$status][2].')'
                             . '; Source Endpoint : '.substr($payload, 2, 2)
                             . '; Destination Endpoint : '.substr($payload, 4, 2)
                             . '; Destination Mode : '.substr($payload, 6, 2)
                             . '; Destination Address : '.substr($payload, 8, 4)
                             . '; SQN: : '.substr($payload, 12, 2)   );

            // type; 8702; (APS Data Confirm Fail)(decoded but Not Processed); Status : d4; Source Endpoint : 01; Destination Endpoint : 03; Destination Mode : 02; Destination Address : c3cd; SQN: : 00

            // On envoie un message MQTT vers la ruche pour le processer dans Abeille
            $SrcAddr    = "Ruche";
            $ClusterId  = "Zigate";
            $AttributId = "8702";
            $data       = substr($payload, 8, 4);

            if ( Abeille::byLogicalId( 'Abeille/'.$data, 'Abeille' ) ) $name = Abeille::byLogicalId( 'Abeille/'.$data, 'Abeille' )->getHumanName(true);
            // message::add("Abeille","L'équipement ".$name." (".$data.") ne peut être joint." );

            $this->mqqtPublish( $SrcAddr, $ClusterId, $AttributId, $data);
        }

        // ***********************************************************************************************
        // Gestion des annonces
        // ***********************************************************************************************

        function getNE( $short )
        {
            $getStates = array( 'getEtat', 'getLevel', 'getColorX', 'getColorY', 'getManufacturerName', 'getSWBuild', 'get Battery'  );

            $abeille = Abeille::byLogicalId('Abeille/'.$short,'Abeille');

            if ( $abeille ) {
                $arr = array(1, 2);
                foreach ($arr as &$value) {
                    foreach ( $getStates as $getState ) {
                        $cmd = $abeille->getCmd('action', $getState);
                        if ( $cmd ) {
                            $this->deamonlog('debug',';Type; fct; getNE cmd: '.$getState);
                            $cmd->execCmd();
                            // sleep(0.5);
                        }
                    }
                }
            }

            $GLOBALS['NE'][$short]['state']='currentState';
        }

        function configureNE( $short )
        {
            $this->deamonlog('debug',';Type; fct; ===> Configure NE Start');

            $commandeConfiguration = array( 'BindShortToZigateBatterie',            'setReportBatterie', 'spiritSetReportBatterie',
                                           'BindToZigateEtat',                     'setReportEtat',
                                           'BindToZigateLevel',                    'setReportLevel',
                                           'BindToZigateButton',
                                           'spiritTemperatureBindShortToZigate',   'spiritTemperatureSetReport',
                                           'BindToZigateIlluminance',              'setReportIlluminance',
                                           'BindToZigateOccupancy',                'setReportOccupancy',
                                           'BindToZigateTemperature',              'setReportTemperature', );

            $abeille = Abeille::byLogicalId('Abeille/'.$short,'Abeille');

            if ( $abeille) {
                $arr = array(1, 2);
                foreach ($arr as &$value) {
                    foreach ( $commandeConfiguration as $config ) {
                        $cmd = $abeille->getCmd('action', $config);
                        if ( $cmd ) {
                            $this->deamonlog('debug',';Type; fct; ===> Configure NE cmd: '.$config);
                            $cmd->execCmd();
                            //sleep(0.5);
                        }
                        else {
                            $this->deamonlog('debug',';Type; fct; ===> Configure NE '.$config.': Cmd not found, probably not an issue, probably should not do it');
                        }
                    }
                }
            }

            $GLOBALS['NE'][$short]['state']='configuration';

            $this->deamonlog('debug',';Type; fct; ===> Configure NE End');
        }

        function processAnnonce( $NE )
        {
            // Etat successifs
            // annonceReceived
            // ActiveEndPoint
            // modelIdentifier || location: meme etat
            // configuration: bind, setReport
            // currentState : get etat: etat, level
            // done

            // Transition
            // none
            // annonceReceived->ActiveEndPoint
            // ActiveEndPointReceived->modelIdentifier
            // modelIdentifierReceived->location
            // location->configuration
            // configuration->currentState
            // done

            if ( count($GLOBALS['NE'])<1 ) { return; }

            if ( $this->debig['processAnnonce'] ) { $this->deamonlog('debug',';Type; fct; processAnnonce, NE: '.json_encode($GLOBALS['NE'])); }

            foreach ( $NE as $short=>$infos ) {
                switch ($infos['state']) {

                    case 'annonceReceived':
                        if (!isset($NE[$short]['action'])) {
                            if ( (($infos['timeAnnonceReceived'])+1) < time() ) { // on attend 1s apres l annonce pour envoyer nos demandes car l equipement fait son appairage.
                                if ( $this->debug['processAnnonceStageChg'] ) { $this->deamonlog('debug',';Type; fct; processAnnonceStageChg, NE: '.json_encode($GLOBALS['NE'])); }
                                $this->deamonlog('debug',';Type; fct; processAnnonceStageChg ; ===> Demande le EP de l equipement');
                                $this->mqqtPublishFctToCmd("CmdAbeille/Ruche/ActiveEndPoint",                       "address=".$short );
                                $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/ActiveEndPoint&time=".(time()+2), "address=".$short );
                                $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/ActiveEndPoint&time=".(time()+4), "address=".$short );
                                $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/ActiveEndPoint&time=".(time()+6), "address=".$short );
                                $GLOBALS['NE'][$short]['action']="annonceReceived->ActiveEndPoint";
                            }
                        }
                        break;

                    case 'EndPoint':
                        if ( $NE[$short]['action'] == "annonceReceived->ActiveEndPoint" ) {
                            if ( $this->debug['processAnnonceStageChg'] ) { $this->deamonlog('debug',';Type; fct; processAnnonceStageChg, NE: '.json_encode($GLOBALS['NE'])); }
                            $this->deamonlog('debug',';Type; fct; processAnnonceStageChg ; ===> Demande le nom de l equipement');
                            $this->mqqtPublishFctToCmd("CmdAbeille/Ruche/getName",                  "address=".$short.'&destinationEndPoint='.$NE[$short]['EP'] );
                            $this->mqqtPublishFctToCmd("CmdAbeille/Ruche/getLocation",              "address=".$short.'&destinationEndPoint='.$NE[$short]['EP'] );

                            $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/getName&time=".(time()+2),                  "address=".$short.'&destinationEndPoint='.$NE[$short]['EP'] );
                            $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/getLocation&time=".(time()+2),              "address=".$short.'&destinationEndPoint='.$NE[$short]['EP'] );

                            // TempoCmdAbeille/Ruche/getVersion&time=123 -> msg=Version
                            $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/SimpleDescriptorRequest&time=".(time()+4), "address=".$short.'&endPoint='.           $NE[$short]['EP'] );
                            $this->mqqtPublishFctToCmd("TempoCmdAbeille/Ruche/SimpleDescriptorRequest&time=".(time()+6), "address=".$short.'&endPoint='.           $NE[$short]['EP'] );
                            $GLOBALS['NE'][$short]['action']="ActiveEndPointReceived->modelIdentifier";
                        }
                        break;

                    case 'modelIdentifier':
                        if ( $NE[$short]['action'] == "ActiveEndPointReceived->modelIdentifier" ) {
                            if ( $this->debug['processAnnonceStageChg'] ) {
                                $this->deamonlog('debug',';Type; fct; processAnnonceStageChg, NE: '.json_encode($GLOBALS['NE']));
                            }
                            $this->deamonlog('debug',';Type; fct; processAnnonceStageChg ; ===> Configure NE');
                            $GLOBALS['NE'][$short]['action']="modelIdentifierReceived->configuration";
                            $this->mqqtPublish( $short, "IEEE", "Addr", $infos['IEEE']);
                            $this->mqqtPublish( $short, "Short", "Addr", $short);
                            sleep(5); // time for the object to be created before configuring
                            $this->configureNE($short);
                        }
                        break;


                    case 'configuration':
                        if ( $NE[$short]['action'] == "modelIdentifierReceived->configuration" ) {
                            if ( $this->debug['processAnnonceStageChg'] ) { $this->deamonlog('debug',';Type; fct; processAnnonceStageChg, NE: '.json_encode($GLOBALS['NE'])); }
                            $this->deamonlog('debug',';Type; fct; processAnnonceStageChg ; ===> Demande Current State Equipement');
                            $GLOBALS['NE'][$short]['action']="configuration->currentState";
                            $this->getNE($short);
                        }
                        break;

                    case 'currentState':
                        if ( $NE[$short]['action'] == "configuration->currentState" ) {
                            if ( $this->debug['processAnnonceStageChg'] ) { $this->deamonlog('debug',';Type; fct; processAnnonceStageChg, NE: '.json_encode($GLOBALS['NE'])); }
                            $GLOBALS['NE'][$short]['state']="done";
                            $GLOBALS['NE'][$short]['action']="done";
                        }
                        break;

                    case 'done':
                        break;

                    default:
                        $this->deamonlog('debug',';Type; fct; processAnnonce, Switch default: WARNING should not exist for ->'.$short.'<- with state ->'.$infos['state'].'<-');
                }
            }

        }

        function cleanUpNE($NE)
        {
            if ( count($GLOBALS['NE'])<1 ) { return; }
            if ( $this->debug['cleanUpNE'] ) { $this->deamonlog('debug',';Type; fct; cleanUpNE begin, NE: '.json_encode($GLOBALS['NE'])); }
            foreach ( $NE as $short=>$infos ) {
                if ( $this->debug['cleanUpNE'] ) { $this->deamonlog('debug',';Type; fct; cleanUpNE time: '.($infos['timeAnnonceReceived']+60).' - ' . time() ); }
                if ( ((($infos['timeAnnonceReceived'])+60) < time()) || ( ($GLOBALS['NE'][$short]['state']=="done")&&($GLOBALS['NE'][$short]['action']=="done") ) ) {
                    if ( $this->debug['cleanUpNE'] ) { $this->deamonlog('debug',';Type; fct; cleanUpNE unset: '.$short); }
                    $this->mqqtPublish( $short, "IEEE", "Addr", $infos['IEEE']);
                    $this->mqqtPublish( $short, "Short", "Addr", $short);
                    $this->mqqtPublish( $short, "Power", "Source", ((base_convert($infos['capa'],16,2)&0x04)>>2));
                    unset( $GLOBALS['NE'][$short] );
                }
            }
            if ( $this->debug['cleanUpNE'] ) { $this->deamonlog('debug',';Type; fct; cleanUpNE end, NE: '.json_encode($GLOBALS['NE'])); }
        }

    }
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
        
        
        while (true) {
            
            if (msg_receive( $queueKeySerieToParser, 0, $msg_type, $max_msg_size, $data, false, MSG_IPC_NOWAIT)) {
                $AbeilleParser->deamonlog( 'debug', "Message pulled from queue queueKeySerieToParser: ".json_encode($data) );
                $msg_type = NULL;
                $msg = NULL;
            }
            
            $AbeilleParser->protocolDatas( $data, 0, $clusterTab, $LQI );
            
            $AbeilleParser->processAnnonce($NE);
            $AbeilleParser->cleanUpNE($NE);
            
            time_nanosleep( 0, 10000000 ); // 1/100s
        }
        
        
        unset($AbeilleParser);
        
    }
    catch (Exception $e) {
        $AbeilleParser->deamonlog( 'debug', 'error: '. json_encode($e->getMessage()));
    }
    
    $AbeilleParser->deamonlog('info', 'Fin du script');
    ?>
