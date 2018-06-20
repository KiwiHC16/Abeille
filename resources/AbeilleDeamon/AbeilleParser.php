<?php
    
    /***
     * AbeilleParser
     *
     * pop data from FIFO file and translate them into a understandable message,
     * then publish them to mosquitto
     *
     */
    
    $lib_phpMQTT = 0;
    
    require_once dirname(__FILE__)."/../../../../core/php/core.inc.php";
    require_once("lib/Tools.php");
    require_once("includes/config.php");
    require_once("includes/fifo.php");
    
    if ( $lib_phpMQTT ) {  include("lib/phpMQTT.php"); }
    
    function deamonlog($loglevel='NONE',$message=""){
        Tools::deamonlog($loglevel,'AbeilleParser',$message);
    }
    
    /*
     + * Send a mosquitto message to jeedom
     + *
     + * @param $mqtt
     + * @param $SrcAddr
     + * @param $ClusterId
     + * @param $AttributId
     + * @param $data
     + * @param int $qos
     + */
    function mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos = 0)
    {
        // Abeille / short addr / Cluster ID - Attr ID -> data
        // deamonlog("debug","mqttPublish with Qos: ".$qos);
        if ( $GLOBAL['lib_phpMQTT'] ) {
            if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
                $mqtt->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId, $data, $qos);
                $mqtt->publish("Abeille/".$SrcAddr."/Time-TimeStamp", time(), $qos);
                $mqtt->publish("Abeille/".$SrcAddr."/Time-Time", date("Y-m-d H:i:s"), $qos);
                $mqtt->close();
            } else {
                deamonlog('WARNING', 'Time out!');
            }
        }
        else {
            $mqtt->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId,    $data,               $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-TimeStamp",                 time(),              $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-Time",                      date("Y-m-d H:i:s"), $qos);
        }
    }
    
    function mqqtPublishLQI($mqtt, $Addr, $Index, $data, $qos = 0)
    {
        // Abeille / short addr / Cluster ID - Attr ID -> data
        // deamonlog("debug","mqttPublish with Qos: ".$qos);
        if ( $GLOBAL['lib_phpMQTT'] ) {
            if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
                // $mqtt->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId, $data, $qos);
                // $mqtt->publish("Abeille/".$SrcAddr."/Time-TimeStamp", time(), $qos);
                // $mqtt->publish("Abeille/".$SrcAddr."/Time-Time", date("Y-m-d H:i:s"), $qos);
                
                $mqtt->publish("LQI/".$Addr."/".$Index, $data, $qos);
                
                $mqtt->close();
            } else {
                deamonlog('WARNING', 'Time out!');
            }
        }
        else {
            $mqtt->publish("LQI/".$Addr."/".$Index, $data, $qos);
        }
    }
    
    /**
     * send an announce to a device
     *
     * @param $mqtt
     * @param $SrcAddr
     * @param $data
     * @param int $qos
     */
    function mqqtPublishAnnounce($mqtt, $SrcAddr, $data, $qos = 0)
    {
        // Abeille / short addr / Annonce -> data
        // deamonlog("debug", "function mqttPublishAnnonce pour addr: ".$SrcAddr." et endPoint: " .$data);
        if ( $GLOBAL['lib_phpMQTT'] ) {
            if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
                $mqtt->publish("CmdAbeille/".$SrcAddr."/Annonce", $data, $qos);
                $mqtt->close();
            } else {
                deamonlog('error','Time out!');
            }
        }
        else {
            $mqtt->publish("CmdAbeille/".$SrcAddr."/Annonce", $data, $qos);
        }
    }
    
    function mqqtPublishAnnounceProfalux($mqtt, $SrcAddr, $data, $qos = 0)
    {
        // Abeille / short addr / Annonce -> data
        // deamonlog("debug", "function mqttPublishAnnonce pour addr: ".$SrcAddr." et endPoint: " .$data);
        if ( $GLOBAL['lib_phpMQTT'] ) {
            if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
                $mqtt->publish("CmdAbeille/".$SrcAddr."/AnnonceProfalux", $data, $qos);
                $mqtt->close();
            } else {
                deamonlog('error','Time out!');
            }
        }
        else {
            $mqtt->publish("CmdAbeille/".$SrcAddr."/AnnonceProfalux", $data, $qos);
        }
    }
    
    function hex2str($hex)
    {
        $str = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        
        return $str;
    }
    
    function displayClusterId($cluster)
    {
        return 'Cluster ID: '.$cluster.'-'.$GLOBALS['clusterTab']["0x".$cluster] ;
    }
    
    function displayStatus($status)
    {
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
                $return = "04-(Busy)";
            }
                break;
            case "05":
            {
                $return = "05-(Stack Already Started)";
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
    
    function protocolDatas($datas, $mqtt, $qos, $clusterTab, &$LQI)
    {
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
        
        // deamonlog('info', '-------------- '.date("Y-m-d H:i:s").': protocolData size('.$length.') message > 12 char');
        
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
            deamonlog('error',';CRC is not as expected ('.$crctmp.') is '.$crc.' ');
        }
        
        // deamonlog('debug',';type: '.$type.' quality: '.$quality);
        
        //Traitement PAYLOAD
        switch ($type) {
                #Device Announce
            case "004d" :
                decode004d($mqtt, $payload, $qos);
                break;
                #Reponses
            case "8000" :
                decode8000($mqtt, $payload, $ln, $qos);
                break;
                
            case "8001" :
                decode8001($mqtt, $payload, $ln, $qos);
                break;
                
            case "8002" :
                decode8002($mqtt, $payload, $ln, $qos);
                break;
                
            case "8003" :
                decode8003($mqtt, $payload, $ln, $qos);
                break;
                
            case "8004" :
                decode8004($mqtt, $payload, $ln, $qos);
                break;
                
            case "8005" :
                decode8005($mqtt, $payload, $ln, $qos);
                break;
                
            case "8006" :
                decode8006($mqtt, $payload, $ln, $qos);
                break;
                
            case "8007" :
                decode8007($mqtt, $payload, $ln, $qos);
                break;
                
            case "8008" :
                decode8008($mqtt, $payload, $ln, $qos);
                break;
                
            case "8009" :
                decode8009($mqtt, $payload, $ln, $qos);
                break;
                
                
            case "8010" :
                decode8010($mqtt, $payload, $ln, $qos);
                break;
                
            case "8014" :
                decode8014($mqtt, $payload, $ln, $qos);
                break;
                
            case "8015" :
                decode8015($mqtt, $payload, $ln, $qos);
                break;
                
            case "8024" :
                decode8024($mqtt, $payload, $ln, $qos);
                break;
                
            case "8028" :
                decode8028($mqtt, $payload, $ln, $qos);
                break;
                
            case "802B" :
                decode802B($mqtt, $payload, $ln, $qos);
                break;
                
            case "802C" :
                decode802C($mqtt, $payload, $ln, $qos);
                break;
                
            case "8030" :
                decode8030($mqtt, $payload, $ln, $qos);
                break;
                
            case "8031" :
                decode8031($mqtt, $payload, $ln, $qos);
                break;
                
            case "8034" :
                decode8034($mqtt, $payload, $ln, $qos);
                break;
                
            case "8040" :
                decode8040($mqtt, $payload, $ln, $qos);
                break;
                
            case "8041" :
                decode8041($mqtt, $payload, $ln, $qos);
                break;
                
            case "8042" :
                decode8042($mqtt, $payload, $ln, $qos);
                break;
                
            case "8043" :
                decode8043($mqtt, $payload, $ln, $qos, $clusterTab);
                break;
                
            case "8044" :
                decode8044($mqtt, $payload, $ln, $qos);
                break;
                
            case "8045" :
                decode8045($mqtt, $payload, $ln, $qos);
                break;
                
            case "8046" :
                decode8044($mqtt, $payload, $ln, $qos);
                break;
                
            case "8047" :
                decode8044($mqtt, $payload, $ln, $qos);
                break;
                
            case "8048":
                decode8048($mqtt, $payload, $ln, $qos);
                break;
                
            case "804A" :
                decode804A($mqtt, $payload, $ln, $qos);
                break;
                
            case "804B" :
                decode804B($mqtt, $payload, $ln, $qos);
                break;
                
            case "804e" :
                decode804E($mqtt, $payload, $ln, $qos, $LQI);
                break;
                ##Reponse groupe
                ##8060-8063
            case "8060" :
                decode8060($mqtt, $payload, $ln, $qos);
                break;
                
            case "8062" :
                decode8062($mqtt, $payload, $ln, $qos);
                break;
                
            case "8063" :
                decode8063($mqtt, $payload, $ln, $qos);
                break;
                
                #reponse scene
                #80a0-80a6
                
                #Reponse Attributs
                #8100-8140
            case "8100":
                decode8100($mqtt, $payload, $ln, $qos);
                break;
                
            case "8101" :
                decode8101($mqtt, $payload, $ln, $qos);
                break;
                
            case "8102" :
                decode8102($mqtt, $payload, $ln, $qos, $quality);
                break;
                
            case "8110" :
                decode8110($mqtt, $payload, $ln, $qos);
                break;
                
            case "8120" :
                decode8120($mqtt, $payload, $ln, $qos);
                break;
                
            case "8140" :
                decode8140($mqtt, $payload, $ln, $qos);
                break;
                
                # IAS Zone Status Change notification
            case "8401" :
                decode8401($mqtt, $payload, $ln, $qos);
                break;
                
                #Route discover
            case "8701" :
                decode8701($mqtt, $payload, $ln, $qos);
                break;
                #Reponse APS
            case "8702" :
                decode8702($mqtt, $payload, $ln, $qos);
                break;
                
                
            default:
                break;
                
        }
        
        return $tab;
    }
    
    /*--------------------------------------------------------------------------------------------------*/
    /* Decode functions
     /*--------------------------------------------------------------------------------------------------*/
    
    function decode004d($mqtt, $payload, $qos)
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
        
        deamonlog('debug',';type: 004d (Device announce)(Processed->MQTT)'
                  . '; Src Addr : '.substr($payload, 0, 4)
                  . '; IEEE : '.substr($payload, 4, 16)
                  . '; MAC capa : '.substr($payload, 20, 2)   );
        
        $SrcAddr = substr($payload, 0, 4);
        $IEEE = substr($payload, 4, 16);
        $capability = substr($payload, 20, 2);
        
        // Envoie de la IEEE a Jeedom
        mqqtPublish($mqtt, $SrcAddr, "IEEE", "Addr", $IEEE, $qos);
        
        // Si routeur alors demande son nom (permet de declencher la creation des objets pour ampoules IKEA
        if ((hexdec($capability) & $test) == 14) {
            deamonlog('debug','Je demande a l equipement d annoncer son nom');
            
            // Pour les ampoules IKEA
            deamonlog('debug','Je demande a l equipement de type generique');
            $data = 'Default'; // destinationEndPoint
            mqqtPublishAnnounce($mqtt, $SrcAddr, $data, $qos);
            
            sleep(2);
            
            // Pour les ampoules Hue
            deamonlog('debug','Je demande a l equipement de type Hue');
            $data = 'Hue'; // destinationEndPoint
            mqqtPublishAnnounce($mqtt, $SrcAddr, $data, $qos);
            
            sleep(2);
            
            // Pour les ampoules OSRAM
            deamonlog('debug','Je demande a l equipement de type OSRAM');
            $data = 'OSRAM'; // destinationEndPoint
            mqqtPublishAnnounce($mqtt, $SrcAddr, $data, $qos);
            
            sleep(2);
            
            // Pour les volets ProFalux
            deamonlog('debug','Je demande a l equipement de type ProFalux');
            $data = 'Default'; // destinationEndPoint
            mqqtPublishAnnounceProfalux($mqtt, $SrcAddr, $data, $qos);
            
        }
        else{
            deamonlog('debug','Je ne demande pas a l equipement d annoncer son nom car ce n est pas un routeur (il n ecoute peut etre pas).');
        }
    }
    
    function decode8000($mqtt, $payload, $ln, $qos)
    {
        $status = substr($payload, 0, 2);
        $SQN = substr($payload, 2, 2);
        
        deamonlog('debug',';type: 8000 (Status)(Not Processed)'
                  . '; Length: '.hexdec($ln)
                  . '; Status: '.displayStatus($status)
                  . '; SQN: '.$SQN );
        
        if ( $SQN==0 ) { deamonlog('debug','SQN: 0 for messages which are not transmitted over the air.'); }
    }
    
    function decode8001($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8001: (Log)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8002($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8002: (Data indication)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8003($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8003: (Liste des clusters de l’objet)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8004($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8004: (Liste des attributs de l’objet)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    function decode8005($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8005: (Liste des commandes de l’objet)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    function decode8006($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8006: (Non “Factory new” Restart)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    function decode8007($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8007: (“Factory New” Restart)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    function decode8008($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8008: (“Function inconnue pas dans la doc")(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8009($mqtt, $payload, $ln, $qos)
    {
        
        deamonlog('debug',';type: 8009: (Network State response (Firm v3.0d))(Processed->MQTT)');
        
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
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        deamonlog('debug','ZiGate Short Address: '.$ShortAddress);
        
        // Envoie Extended Address
        $SrcAddr = "Ruche";
        $ClusterId = "IEEE";
        $AttributId = "Addr";
        $data = $ExtendedAddress;
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        deamonlog('debug','IEEE Address: '.$ExtendedAddress);
        
        // Envoie PAN ID
        $SrcAddr = "Ruche";
        $ClusterId = "PAN";
        $AttributId = "ID";
        $data = $PAN_ID;
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        deamonlog('debug','PAN ID: '.$PAN_ID);
        
        // Envoie Ext PAN ID
        $SrcAddr = "Ruche";
        $ClusterId = "Ext_PAN";
        $AttributId = "ID";
        $data = $Ext_PAN_ID;
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        deamonlog('debug','Ext_PAN_ID: '.$Ext_PAN_ID);
        
        // Envoie Channel
        $SrcAddr = "Ruche";
        $ClusterId = "Network";
        $AttributId = "Channel";
        $data = $Channel;
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        deamonlog('debug','Channel: '.$Channel);
        
        deamonlog('debug','Level: 0x'.substr($payload, 0, 2));
        // deamonlog('debug','Message: ');
        // deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8010($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8010 (Version)(Processed->MQTT)'
                  . '; Application : '.hexdec(substr($payload, 0, 4))
                  . '; SDK : '.hexdec(substr($payload, 4, 4)));
        $SrcAddr = "Ruche";
        $ClusterId = "SW";
        $AttributId = "Application";
        $data = substr($payload, 0, 4);
        deamonlog("debug", $AttributId.": ".$data." qos:".$qos);
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
        $SrcAddr = "Ruche";
        $ClusterId = "SW";
        $AttributId = "SDK";
        $data = substr($payload, 4, 4);
        deamonlog('debug',$AttributId.': '.$data.' qos:'.$qos);
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
    }
    
    function decode8014($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8014: ( “Permit join” status response)(Processed->MQTT)'
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
        
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
    }
    
    function decode8015($mqtt, $payload, $ln, $qos)
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
        
        deamonlog('debug',';type: 8015 (Abeille List)(Processed->MQTT) Payload: '.$payload);
        
        $nb = (strlen($payload) - 2) / 26;
        deamonlog('debug','Nombre d\'abeilles: '.$nb);
        
        for ($i = 0; $i < $nb; $i++) {
            
            $SrcAddr = substr($payload, $i * 26 + 2, 4);
            
            // Envoie IEEE
            $ClusterId = "IEEE";
            $AttributId = "Addr";
            $dataAddr = substr($payload, $i * 26 + 6, 16);
            mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $dataAddr, $qos);
            
            // Envoie Power Source
            $ClusterId = "Power";
            $AttributId = "Source";
            $dataPower = substr($payload, $i * 26 + 22, 2);
            mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $dataPower, $qos);
            
            // Envoie Link Quality
            $ClusterId = "Link";
            $AttributId = "Quality";
            $dataLink = hexdec(substr($payload, $i * 26 + 24, 2));
            mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $dataLink, $qos);
            
            deamonlog('debug','Abeille i: '.$i
                      . '; ID : '.substr($payload, $i * 26 + 0, 2)
                      . '; Short Addr : '.$SrcAddr
                      . '; IEEE Addr: '.$dataAddr
                      . '; Power Source (0:battery - 1:AC): '.$dataPower
                      . '; Link Quality: '.$dataLink   );
        }
    }
    
    function decode8024($mqtt, $payload, $ln, $qos)
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
        if( substr($payload, 0, 2) > "01" ) { $data = "Failed (ZigBee event codes): ".substr($payload, 0, 2); }
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
        // Envoie Short Address
        $SrcAddr = "Ruche";
        $ClusterId = "Short";
        $AttributId = "Addr";
        $dataShort = substr($payload, 2, 4);
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $dataShort, $qos);
        
        // Envoie IEEE Address
        $SrcAddr = "Ruche";
        $ClusterId = "IEEE";
        $AttributId = "Addr";
        $dataIEEE = substr($payload, 6,16);
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $dataIEEE, $qos);
        
        // Envoie channel
        $SrcAddr = "Ruche";
        $ClusterId = "Network";
        $AttributId = "Channel";
        $dataNetwork = hexdec( substr($payload,22, 2) );
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $dataNetwork, $qos);
        
        deamonlog('debug',';type: 8024: ( Network joined / formed )(Processed->MQTT)');
        deamonlog('debug','Satus : '               .$data);
        deamonlog('debug','short addr : '          .$dataShort);
        deamonlog('debug','extended address : '    .$dataIEEE);
        deamonlog('debug','Channel : '             .$dataNetwork);
        
    }
    
    function decode8028($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8028: (Authenticate response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode802B($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 802B: (	User Descriptor Notify)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode802C($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 802C: (User Descriptor Response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8030($mqtt, $payload, $ln, $qos)
    {
        // <Sequence number: uint8_t>
        // <status: uint8_t>
        
        deamonlog('debug',';type: 8030: (Bind response)(Decoded but Not Processed - Just send time update and status to Network-Bind in Ruche)'
                  . '; SQN: 0x'.substr($payload, 0, 2)
                  . '; Status: 0x'.substr($payload, 2, 2)  );
        
        // Envoie channel
        $SrcAddr = "Ruche";
        $ClusterId = "Network";
        $AttributId = "Bind";
        $data = date("Y-m-d H:i:s")." Status (00: Ok, <>0: Error): ".substr($payload, 2, 2);
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
    }
    
    function decode8031($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8031: (unBind response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8034($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8034: (Complex Descriptor response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    
    function decode8040($mqtt, $payload, $ln, $qos)
    {
        // Network Address response
        
        // <Sequence number: uin8_t>
        // <status: uint8_t>
        // <IEEE address: uint64_t>
        // <short address: uint16_t>
        // <number of associated devices: uint8_t>
        // <start index: uint8_t>
        // <device list – data each entry is uint16_t>
        
        
        // deamonlog('debug',';type: 8040: (Network Address response)(Decoded but Not Processed)'
        //          . '; (Not processed*************************************************************)'
        //           . '; Level: 0x'.substr($payload, 0, 2)
        //          . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
        
        deamonlog('debug',';type: 8041: (IEEE Address response)(Decoded but Not Processed)'
                  . '; SQN : '                                    .substr($payload, 0, 2)
                  . '; Status : '                                 .substr($payload, 2, 2)
                  . '; IEEE address : '                           .substr($payload, 4,16)
                  . '; short address : '                          .substr($payload,20, 4)
                  . '; number of associated devices : '           .substr($payload,24, 2)
                  . '; start index : '                            .substr($payload,26, 2)
                  );
        
        if ( substr($payload, 2, 2)!= "00" ) {
            deamonlog('debug',';type: 8041: Don t use this data there is an error, comme info not known');
        }
        
        for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
            deamonlog('debug','associated devices: '    .substr($payload, (28 + $i), 4) );
        }
        
    }
    
    function decode8041($mqtt, $payload, $ln, $qos)
    {
        // IEEE Address response
        
        // <Sequence number: uin8_t>
        // <status: uint8_t>
        // <IEEE address: uint64_t>
        // <short address: uint16_t>
        // <number of associated devices: uint8_t>
        // <start index: uint8_t>
        // <device list – data each entry is uint16_t>
        
        // deamonlog('debug',';type: 8041: (IEEE Address response)(Decoded but Not Processed)'
        //          . '; (Not processed*************************************************************)'
        //          . '; Level: 0x'.substr($payload, 0, 2)
        //          . '; Message: '.substr($payload, 2, strlen($payload) - 2)   );
        
        deamonlog('debug',';type: 8041: (IEEE Address response)(Decoded but Not Processed)'
                  . '; SQN : '                                    .substr($payload, 0, 2)
                  . '; Status : '                                 .substr($payload, 2, 2)
                  . '; IEEE address : '                           .substr($payload, 4,16)
                  . '; short address : '                          .substr($payload,20, 4)
                  . '; number of associated devices : '           .substr($payload,24, 2)
                  . '; start index : '                            .substr($payload,26, 2)
                  );
        
        if ( substr($payload, 2, 2)!= "00" ) {
            deamonlog('debug',';type: 8041: Don t use this data there is an error, comme info not known');
        }
        
        for ($i = 0; $i < (intval(substr($payload,24, 2)) * 4); $i += 4) {
            deamonlog('debug','associated devices: '    .substr($payload, (28 + $i), 4) );
        }
    }
    
    function decode8042($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8042: (Node Descriptor response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8043($mqtt, $payload, $ln, $qos, $clusterTab)
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
        
        deamonlog('debug',';type: 8043 (Simple Descriptor Response)(Not Processed)'
                  . '; SQN : '             .substr($payload, 0, 2)
                  . '; Status : '          .substr($payload, 2, 2)
                  . '; Short Address : '   .substr($payload, 4, 4)
                  . '; Length : '          .substr($payload, 8, 2)
                  . '; endpoint : '        .substr($payload,10, 2)
                  . '; profile : '         .substr($payload,12, 4)
                  . '; deviceId : '        .substr($payload,16, 4)
                  . '; bitField : '        .substr($payload,20, 2)
                  . '; InClusterCount : '  .substr($payload,22, 2)   );
        
        for ($i = 0; $i < (intval(substr($payload, 22, 2)) * 4); $i += 4) {
            deamonlog('debug','In cluster: '    .substr($payload, (24 + $i), 4). ' - ' . $clusterTab['0x'.substr($payload, (24 + $i), 4)]);
        }
        deamonlog('debug','OutClusterCount : '  .substr($payload,24+$i, 2));
        for ($j = 0; $j < (intval(substr($payload, 24+$i, 2)) * 4); $j += 4) {
            deamonlog('debug','Out cluster: '    .substr($payload, (24 + $i +2 +$j), 4) . ' - ' . $clusterTab['0x'.substr($payload, (24 + $i +2 +$j), 4)]);
        }
        
    }
    
    function decode8044($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug',';type: 8044: (N	Power Descriptor response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8045($mqtt, $payload, $ln, $qos)
    {
        $endPointList = "";
        for ($i = 0; $i < (intval(substr($payload, 8, 2)) * 2); $i += 2) {
            // deamonlog('debug','Endpoint : '    .substr($payload, (10 + $i), 2));
            $endPointList = $endPointList . '; Endpoint : '.substr($payload, (10 + $i), 2) ;
        }
        
        deamonlog('debug',';type: 8045 (Active Endpoints Response)(Not Processed)'
                  . '; SQN : '             .substr($payload, 0, 2)
                  . '; Status : '          .substr($payload, 2, 2)
                  . '; Short Address : '   .substr($payload, 4, 4)
                  . '; Endpoint Count : '  .substr($payload, 8, 2)
                  . '; Endpoint List :'    .$endPointList             );
        
    }
    
    function decode8046($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8046: (Match Descriptor response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8047($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8047: (Management Leave response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8048($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', ' type: 8048 (Leave Indication)(Processed->Draft-MQTT)'
                  . '; extended addr : '.substr($payload, 0, 16)
                  . '; rejoin status : '.substr($payload, 16, 2)    );
        
        $SrcAddr = "Ruche";
        $ClusterId = "joinLeave";
        $AttributId = "IEEE";
        $data = "Leave->".substr($payload, 0, 16)."->".substr($payload, 16, 2);
        
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
    }
    
    function decode804A($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 804A: (Management Network Update response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))  );
    }
    
    
    function decode804B($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 804B: (	System Server Discovery response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))  );
    }
    
    
    function decode804E($mqtt, $payload, $ln, $qos, &$LQI)
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
        
        deamonlog('debug', ';Type: 804E: (Management LQI response)(Decoded but Not Processed)'
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
        $bitMapOfAttributes = substr($payload, 50, 2); // to be decoded
        $LQI[$srcAddress]=array($Neighbour=>array('LQI'=>$lqi, 'depth'=>$Depth, 'tree'=>$bitMapOfAttributes, ));
        
        $data =
        "NeighbourTableEntries="       .substr($payload, 4, 2)
        ."&Index="                      .substr($payload, 8, 2)
        ."&ExtendedPanId="              .substr($payload,14,16)
        ."&IEEE_Address="               .substr($payload,30,16)
        ."&Depth="                      .substr($payload,46, 2)
        ."&LinkQuality="                .substr($payload,48, 2)
        ."&BitmapOfAttributes="         .substr($payload,50, 2);
        
        // deamonlog('debug', 'Level: 0x'.substr($payload, 0, 2));
        // deamonlog('debug', 'Message: ');
        // deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
        
        //function mqqtPublishLQI($mqtt, $Addr, $Index, $data, $qos = 0)
        mqqtPublishLQI($mqtt, $NeighbourAddr, $index, $data, $qos);
    }
    
    ##TODO
    ##Reponse groupe
    ##8060-8063
    function decode8060($mqtt, $payload, $ln, $qos)
    {
        
        // <Sequence number: uint8_t>
        // <endpoint: uint8_t>
        // <Cluster id: uint16_t>
        
        deamonlog('debug', 'Type: 8060: (Add a group response)(Decoded but Not Processed)'
                  . '; SQN: '          .substr($payload, 0, 2)
                  . '; endPoint: '     .substr($payload, 2, 2)
                  . '; clusterId: '    .substr($payload, 4, 4) );
    }
    
    
    
    function decode8062($mqtt, $payload, $ln, $qos)
    {
        
        // <Sequence number: uint8_t>                               -> 2
        // <endpoint: uint8_t>                                      -> 2
        // <Cluster id: uint16_t>                                   -> 4
        // <Src Addr: uint16_t> (added only from 3.0d version)      -> 4
        // <capacity: uint8_t>                                      -> 2
        // <Group count: uint8_t>                                   -> 2
        // <List of Group id: list each data item uint16_t>         -> 4x
        
        // . '; SQN: '          .payload length: '          .strlen($payload) );
        // . '; SQN: '          .group part of the payload length: '          .$groupSize
        
        $groupSize = strlen($payload)-12-2; // 2 last are RSSI
        
        deamonlog('debug', 'Type: 8062: (Group Memebership)(Processed->MQTT)'
                  . '; SQN: '          .substr($payload, 0, 2)
                  . '; endPoint: '     .substr($payload, 2, 2)
                  . '; clusterId: '    .substr($payload, 4, 4)
                  . '; Address: '      .substr($payload, 8, 4)
                  . '; capacity: '     .substr($payload,12, 2)
                  . '; group count: '  .substr($payload,14, 2)  );
        
        $groupCount = hexdec( substr($payload,14, 2) );
        $groupsId="";
        for ($i=0;$i<$groupCount;$i++)
        {
            deamonlog('debug', 'group '.$i.'(addr:'.(16+$i*4).'): '  .substr($payload,16+$i*4, 4));
            $groupsId .= '-' . substr($payload,16+$i*4, 4);
        }
        deamonlog('debug', 'Groups: '.$groupsId);
        
        deamonlog('debug', 'Level: 0x'.substr($payload, strlen($payload)-2, 2));
        
        // Envoie Group-Membership
        $SrcAddr = substr($payload, 8, 4);
        $ClusterId = "Group";
        $AttributId = "Membership";
        $data = $groupsId;
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
    }
    
    function decode8063($mqtt, $payload, $ln, $qos)
    {
        
        // <Sequence number: uin8_t>    -> 2
        // <endpoint: uint8_t>          -> 2
        // <Cluster id: uint16_t>       -> 4
        // <status: uint8_t>            -> 2
        // <Group id: uint16_t>         -> 4
        
        deamonlog('debug', 'Type: 8063: (Remove a group response)(Decoded but Not Processed)'
                  . '; SQN: '          .substr($payload, 0, 2)
                  . '; endPoint: '     .substr($payload, 2, 2)
                  . '; clusterId: '    .substr($payload, 4, 4)
                  . '; statusId: '     .substr($payload, 8, 2)
                  . '; groupId: '      .substr($payload,10, 4) );
    }
    
    
    
    ##TODO
    #reponse scene
    #80a0-80a6
    
    #Reponse Attributs
    #8100-8140
    
    function decode8100($mqtt, $payload, $ln, $qos)
    {
        // "Type: 0x8100 (Read Attrib Response)"
        // 8100 000D0C0Cb32801000600000010000101
        deamonlog('debug', 'Type: 0x8100 (Read Attrib Response)(Processed->MQTT)'
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
         deamonlog('Syze of Attribute: '.substr($payload, 20, 4));
         deamonlog('Data byte list (one octet pour l instant): '.substr($payload, 24, 2));
         */
        deamonlog('debug', 'Syze of Attribute: '.substr($payload, 20, 4));
        deamonlog('debug', 'Data byte list (one octet pour l instant): '.substr($payload, 24, 2));
        
        // short addr / Cluster ID / Attr ID -> data
        $SrcAddr = substr($payload, 2, 4);
        $ClusterId = substr($payload, 8, 4);
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
        deamonlog('debug','Data byte: '.$data);
        
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
    }
    
    function decode8101($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', ';Type: 8101 (Default Response)(Not Processed)'
                  . '; Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.'
                  . '; SQN : '.substr($payload, 0, 2)
                  . '; EndPoint : '.substr($payload, 2, 2)
                  . '; '. displayClusterId(substr($payload, 4, 4))
                  . '; Command : '.substr($payload, 8, 2)
                  . '; Status : '.substr($payload, 10, 2)  );
    }
    
    function decode8102($mqtt, $payload, $ln, $qos, $quality)
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
        
        mqqtPublish($mqtt, $SrcAddr, 'Link', 'Quality', $quality, $qos);
        
        
        // 0005: ModelIdentifier
        // 0010: Piece (nom utilisé pour Profalux)
        if ( ($ClusterId=="0000") && ( ($AttributId=="0005") || ($AttributId=="0010") ) ) {
            deamonlog('debug', ';Type: 8102 (Attribut Report)(Processed->MQTT)'
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
            deamonlog('debug', ';Type: 8102 (Attribut Report)(Processed->MQTT)'
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
        // 0x25	uint48
        // 0x28	int8                    -> hexdec(2)
        // 0x29	int16                   -> unpack("s", pack("s", hexdec(
        // 0x2a	int32                   -> unpack("l", pack("l", hexdec(
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
        
        // Example Cube Xiaomi
        // Sniffer dit Single Precision Floating Point
        // b9 1e 38 c2 -> -46,03
        if ($dataType == "39") {
            // $data = hexdec(substr($payload, 24, 4));
            // $data = unpack("s", pack("s", hexdec(substr($payload, 24, 4))))[1];
            $hexNumber = substr($payload, 24, 8);
            $hexNumberOrder = $hexNumber[6].$hexNumber[7].$hexNumber[4].$hexNumber[5].$hexNumber[2].$hexNumber[3].$hexNumber[0].$hexNumber[1];
            $bin = pack('H*', $hexNumberOrder );
            $data = unpack("f", $bin )[1];
        }
        
        if ($dataType == "42") {
            
            // Xiaomi Bouton Carré
            if (($AttributId == "ff01") && ($AttributSize == "001a")) {
                deamonlog("debug","Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Bouton Carre)" );
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
            }
            
            // Xiaomi lumi.sensor_86sw1 (Wall 1 Switch sur batterie)
            elseif (($AttributId == "ff01") && ($AttributSize == "001b")) {
                deamonlog("debug","Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall 1 Switch)" );
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
            }
            
            // Xiaomi Door Sensor
            elseif (($AttributId == "ff01") && ($AttributSize == "001d")) {
                deamonlog("debug","Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Door Sensor)" );
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
            }
            
            // Xiaomi capteur temperature rond / lumi.sensor_86sw2 (Wall 2 Switches sur batterie)
            elseif (($AttributId == "ff01") && ($AttributSize == "001f")) {
                deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Temperature Rond/Wall 2 Switch)');
                
                $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                $humidity = hexdec( substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2) );
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                // deamonlog('debug', 'Temperature: '  .$temperature);
                // deamonlog('debug', 'Humidity: '     .$humidity);
                
                mqqtPublish($mqtt, $SrcAddr, $ClusterId,$AttributId,'Decoded as Volt-Temperature-Humidity',$qos );
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
                // mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,$qos);
                // mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,$qos);
                
            }
            
            // Xiaomi capteur Presence V2
            elseif (($AttributId == 'ff01') && ($AttributSize == "0021")) {
                deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Presence V2)');
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                // deamonlog('debug', 'Temperature: '  .$temperature);
                // deamonlog('debug', 'Humidity: '     .$humidity);
                // deamonlog('debug', 'Pression: '     .$pression);
                
                mqqtPublish($mqtt,$SrcAddr,$ClusterId, $AttributId,'Decoded as Volt-Temperature-Humidity',$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
                // mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,      $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,         $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                
            }
            
            // Xiaomi capteur Inondation
            elseif (($AttributId == 'ff01') && ($AttributSize == "0022")) {
                deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Inondation)');
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                // deamonlog('debug', 'Temperature: '  .$temperature);
                // deamonlog('debug', 'Humidity: '     .$humidity);
                // deamonlog('debug', 'Pression: '     .$pression);
                
                mqqtPublish($mqtt,$SrcAddr,$ClusterId, $AttributId,'Decoded as Volt-Temperature-Humidity',$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
                // mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,      $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,         $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                
            }
            
            // Xiaomi capteur temperature carré
            elseif (($AttributId == 'ff01') && ($AttributSize == "0025")) {
                deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Capteur Temperature Carré)');
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                
                deamonlog('debug', 'ff01/25: Voltage: '      .$voltage);
                deamonlog('debug', 'ff01/25: Temperature: '  .$temperature);
                deamonlog('debug', 'ff01/25: Humidity: '     .$humidity);
                deamonlog('debug', 'ff01/25: Pression: '     .$pression);
                
                mqqtPublish($mqtt,$SrcAddr,$ClusterId, $AttributId,'Decoded as Volt-Temperature-Humidity',$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
                mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,      $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,         $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                
            }
            
            // Xiaomi Smoke Sensor
            elseif (($AttributId == 'ff01') && ($AttributSize == "0028")) {
                deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Sensor Smoke)');
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                
                mqqtPublish($mqtt,$SrcAddr,$ClusterId, $AttributId,'Decoded as Volt',$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
            }
            
            // Xiaomi Cube
            // Xiaomi capteur Inondation
            elseif (($AttributId == 'ff01') && ($AttributSize == "002a")) {
                deamonlog('debug','Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Cube)');
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                // $temperature    = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                // $humidity       = hexdec(substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2));
                // $pression       = hexdec(substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr($payload,24 + 29 * 2 + 2,2).substr($payload, 24 + 29 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                // deamonlog('debug', 'Temperature: '  .$temperature);
                // deamonlog('debug', 'Humidity: '     .$humidity);
                // deamonlog('debug', 'Pression: '     .$pression);
                
                mqqtPublish($mqtt,$SrcAddr,$ClusterId, $AttributId,'Decoded as Volt-Temperature-Humidity',$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
                // mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,      $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,         $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0010', $pression / 10,    $qos);
                // mqqtPublish($mqtt, $SrcAddr, '0403', '0000', $pression / 100,   $qos);
                
            }
            
            // Xiaomi Wall Plug
            elseif (($AttributId == "ff01") && ($AttributSize == "0031")) {
                deamonlog('debug', 'Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Wall Plug)');
                $onOff = hexdec(substr($payload, 24 + 2 * 2, 2));
                deamonlog('debug', 'Puissance: '.substr($payload, 24 + 8 * 2, 8));
                $puissance = unpack('f', pack('H*', substr($payload, 24 + 8 * 2, 8)));
                $puissanceValue = $puissance[1];
                deamonlog('debug', 'Conso: '.substr($payload, 24 + 14 * 2, 8));
                $conso = unpack('f', pack('H*', substr($payload, 24 + 14 * 2, 8)));
                $consoValue = $conso[1];
                
                deamonlog('debug', 'OnOff: '.$onOff);
                deamonlog('debug', 'Puissance: '.$puissanceValue);
                deamonlog('debug', 'Consommation: '.$consoValue);
                
                mqqtPublish(
                            $mqtt,
                            $SrcAddr,
                            $ClusterId,
                            $AttributId,
                            'Decoded as OnOff-Puissance-Conso',$qos
                            );
                mqqtPublish($mqtt, $SrcAddr, 'Xiaomi',  '0006-0000',        $onOff,             $qos);
                mqqtPublish($mqtt, $SrcAddr, 'tbd',     '--puissance--',    $puissanceValue,    $qos);
                mqqtPublish($mqtt, $SrcAddr, 'tbd',     '--conso--',        $consoValue,        $qos);
            }
            
            
            // Xiaomi Capteur Presence
            // Je ne vois pas ce message pour ce cateur et sur appui lateral il n envoie rien
            // Je mets un Attribut Size a XX en attendant. Le code et la il reste juste a trouver la taille de l attribut si il est envoyé.
            elseif (($AttributId == "ff01") && ($AttributSize == "00XX")) {
                deamonlog("debug","Champ proprietaire Xiaomi, decodons le et envoyons a Abeille les informations (Bouton Carre)" );
                
                $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                
                deamonlog('debug', 'Voltage: '      .$voltage);
                
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Pourcent', (100-(((3.1-($voltage/1000))/(3.1-2.8))*100)),$qos);
                
            }
            
            // Xiaomi Presence Infrarouge
            elseif (($AttributId == "ff02")) {
                // Non decodé a ce stade
                deamonlog("debug", "Champ 0xFF02 non decode a ce stade");
            } else {
                $data = hex2bin(substr($payload, 24, (strlen($payload) - 24 - 2))); // -2 est une difference entre ZiGate et NXP Controlleur.
            }
        }
        
        if (isset($data)) {
            if ( hexdec($EPoint) < 2 ) {
                // deamonlog('debug', 'Data byte: '.$data);
                mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
            }
            else {
                // Ceci est necessaire pour les Ep Src du Xiaomi Wall Plug
                // Mais du coup il  faut changer les modeles de toutes les NE qui on un EP diff de 01.
                mqqtPublish($mqtt, $SrcAddr, $ClusterId."-".$EPoint, $AttributId, $data, $qos);
            }
        }
    }
    
    function decode8110($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8110: (	Write Attribute Response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    function decode8120($mqtt, $payload, $ln, $qos)
    {
        // <Sequence number: uint8_t>
        // <Src address : uint16_t>
        // <Endpoint: uint8_t>
        // <Cluster id: uint16_t>
        // <Status: uint8_t>
        
        deamonlog('debug', 'Type: 8120: (Configure Reporting response)(Decoded but not Processed)'
                  . '; SQN: '              .substr($payload, 0, 2)
                  . '; Source address: '   .substr($payload, 2, 4)
                  . '; EndPoint: '         .substr($payload, 6, 2)
                  . '; Cluster Id: '       .substr($payload, 8, 4)
                  . '; Status: '           .substr($payload,12, 2)  );
        
        // Envoie channel
        $SrcAddr = "Ruche";
        $ClusterId = "Network";
        $AttributId = "Report";
        $data = date("Y-m-d H:i:s")." Status (00: Ok, <>0: Error): ".substr($payload,12, 2);
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
    }
    
    function decode8140($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8140: (Configure Reporting response)(Not Processed)'
                  . '; (Not processed*************************************************************)'
                  . '; Level: 0x'.substr($payload, 0, 2)
                  . '; Message: '.hex2str(substr($payload, 2, strlen($payload) - 2))   );
    }
    
    // Codé sur la base des messages Xiaomi Inondation
    function decode8401($mqtt, $payload, $ln, $qos)
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
        
        deamonlog('debug', ';Type: 8401: (IAS Zone status change notification )(Processed)'
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
        $AttributId = "0000";
        $data       = substr($payload,14, 4);
        
        // On transmettre l info sur Cluster 0500 et Cmd: 0000 (Jusqu'a present on etait sur ClusterId-AttributeId, ici ClusterId-CommandId)
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
    }
    
    
    function decode8701($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', ' type: 8701 (Route Discovery Confirm)(Decoded but Not Processed)'
                  . '; Status : '.substr($payload, 0, 2)
                  . '; Nwk Status : '.substr($payload, 2, 2)  );
    }
    
    function decode8702($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'type: 8701: (APS Data Confirm Fail)(Decoded but Not Processed)'
                  . '; Status : '.substr($payload, 0, 2)
                  . '; Source Endpoint : '.substr($payload, 2, 2)
                  . '; Destination Endpoint : '.substr($payload, 4, 2)
                  . '; Destination Mode : '.substr($payload, 6, 2)
                  . '; Destination Address : '.substr($payload, 8, 4)
                  . '; SQN: : '.substr($payload, 12, 2)   );
    }
    
    // ***********************************************************************************************
    // MQTT
    // ***********************************************************************************************
    function connect($r, $message)
    {
        log::add('AbeilleParser', 'info', 'Mosquitto: Connexion à Mosquitto avec code ' . $r . ' ' . $message);
        // config::save('state', '1', 'Abeille');
    }
    
    function disconnect($r)
    {
        log::add('AbeilleParser', 'debug', 'Mosquitto: Déconnexion de Mosquitto avec code ' . $r);
        // config::save('state', '0', 'Abeille');
    }
    
    function subscribe()
    {
        log::add('AbeilleParser', 'debug', 'Mosquitto: Subscribe to topics');
    }
    
    function logmq($code, $str)
    {
        // if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
        log::add('AbeilleParser', 'debug', 'Mosquitto: Log level: ' . $code . ' Message: ' . $str);
        // }
    }
    
    function message($message)
    {
        // var_dump( $message );
        procmsg( $message->topic, $message->payload );
    }
    
    /*--------------------------------------------------------------------------------------------------*/
    /* Main
     /*--------------------------------------------------------------------------------------------------*/
    
    
    //                      1          2           3       4          5       6
    //$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;
    
    
    $serial = $argv[1];
    $server = $argv[2];     // change if necessary
    $port = $argv[3];                     // change if necessary
    $username = $argv[4];                   // set your username
    $password = $argv[5];                   // set your password
    $client_id = 'AbeilleParser'; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos = $argv[6];
    $requestedlevel = $argv[7];
    $requestedlevel = '' ? 'none' : $argv[7];
    
    $LQI = array();
    
    deamonlog('info', 'Starting parsing from '.$in.' to mqtt broker with log level '.$requestedlevel.' on '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos );
    
    $fifoIN = new fifo( $in, 0777, "r" );
    
    deamonlog('info', 'Starting parsing from '.$in.' to mqtt broker with log level '.$requestedlevel.' on '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos );
    
    if (!file_exists($in)) {
        deamonlog('error', 'ERROR, fichier '.$in.' n existe pas');
        exit(1);
    }
    
    $clusterTab = Tools::getJSonConfigFiles("zigateClusters.json");
    
    if ($GLOBAL['lib_phpMQTT']) {
        $mqtt = new phpMQTT($server, $port, $client_id);
        while (true) {
            if (!file_exists($in)) {
                deamonlog('error', 'Erreur, fichier '.$in.' n existe pas');
                exit(1);
            }
            //traitement de chaque trame;
            $data = $fifoIN->read();
            protocolDatas( $data, $mqtt, $qos, $clusterTab, $LQI );
            usleep(1);
            
        }
    }
    else {
        deamonlog( 'debug', 'Create a MQTT Client');
        
        // https://github.com/mgdm/Mosquitto-PHP
        // http://mosquitto-php.readthedocs.io/en/latest/client.html
        $mqtt = new Mosquitto\Client($client_id);
        
        // var_dump( $mqtt );
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
        $mqtt->onConnect('connect');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
        $mqtt->onDisconnect('disconnect');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
        $mqtt->onSubscribe('subscribe');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
        $mqtt->onMessage('message');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onLog
        $mqtt->onLog('logmq');
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
        $mqtt->setWill('/jeedom', "Client AbeilleMQTTCmd died :-(", $qos, 0);
        
        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
        $mqtt->setReconnectDelay(1, 120, 1);
        
        // var_dump( $mqtt );
        
        try {
            deamonlog('info', 'try part');
            
            $mqtt->setCredentials( $username, $password );
            $mqtt->connect( $server, $port, 60 );
            // $mqtt->subscribe( $parameters_info['AbeilleTopic'], $qos ); // !auto: Subscribe to root topic
            
            deamonlog( 'debug', 'Subscribed to topic: '.$parameters_info['AbeilleTopic'] );
            
            // 1 to use loopForever et 0 to use while loop
            if (0) {
                // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
                deamonlog( 'debug', 'Let loop for ever' );
                $mqtt->loopForever();
            } else {
                while (true) {
                    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                    $mqtt->loop();
                    //usleep(100);
                    if (!file_exists($in)) {
                        deamonlog('error', 'Erreur, fichier '.$in.' n existe pas');
                        exit(1);
                    }
                    //traitement de chaque trame;
                    $data = $fifoIN->read();
                    protocolDatas( $data, $mqtt, $qos, $clusterTab, $LQI );
                    usleep(1);
                }
            }
            
            $mqtt->disconnect();
            unset($mqtt);
            
        } catch (Exception $e) {
            log::add('Abeille', 'error', $e->getMessage());
        }
        
    }
    
    
    
    
    
    
    
    
    
    deamonlog('warning', 'sortie du loop');
    
    ?>
