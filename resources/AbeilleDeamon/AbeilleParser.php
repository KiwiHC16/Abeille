<?php
    
    /***
     * AbeilleParser
     *
     * pop data from FIFO file and translate them into a understandable message,
     * then publish them to mosquitto
     *
     */
    
    
    require_once dirname(__FILE__)."/../../../../core/php/core.inc.php";
    require_once("lib/Tools.php");
    include("includes/config.php");
    include("includes/fifo.php");
    include("lib/phpMQTT.php");
    
    
    $clusterTab = Tools::getJSonConfigFiles("zigateClusters.json");

    // print_r( $clusterTab );
    
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
        deamonlog("debug","mqttPublish with Qos: ".$qos);
        if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
            $mqtt->publish("Abeille/".$SrcAddr."/".$ClusterId."-".$AttributId, $data, $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-TimeStamp", time(), $qos);
            $mqtt->publish("Abeille/".$SrcAddr."/Time-Time", date("Y-m-d H:i:s"), $qos);
            $mqtt->close();
        } else {
            deamonlog('WARNING', 'Time out!');
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
        deamonlog("debug", "mqttPublishAnnonce : Qos: ".$qos);
        if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
            $mqtt->publish("CmdAbeille/".$SrcAddr."/Annonce", $data, $qos);
            $mqtt->close();
        } else {
            deamonlog('error','Time out!');
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
        deamonlog('debug','Cluster ID: '.$cluster.'-'.$GLOBALS['clusterTab']["0x".$cluster]);
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
                $return = "(ZigBee Error Code)";
            }
                break;
        }
        
        return $return;
    }
    
    function protocolDatas($datas, $mqtt, $qos, $clusterTab)
    {
        // datas: trame complete recue sur le port serie sans le start ni le stop.
        // 01: 01 Start
        // 02-03: Msg Type
        // 04-05: Length
        // 06: crc
        // 07-: Data / Payload
        // xx: 03 Stop
        
        $tab = "";
        $crctmp = 0;

        $length = strlen($datas);
        // Message trop court pour etre un vrai message
        if ($length < 12) { return -1; }
        
        deamonlog('info', '-------------- '.date("Y-m-d H:i:s").': protocolData size('.$length.') message > 12 char');
        
        //type de message
        $type = $datas[0].$datas[1].$datas[2].$datas[3];
        $crctmp = $crctmp ^ hexdec($datas[0].$datas[1]) ^ hexdec($datas[2].$datas[3]);
        
        //taille message
        $ln = $datas[4].$datas[5].$datas[6].$datas[7];
        $crctmp = $crctmp ^ hexdec($datas[4].$datas[5]) ^ hexdec($datas[6].$datas[7]);
        
        //acquisition du CRC
        $crc = $datas[8].$datas[9];
        //payload
        $payload = "";
        for ($i = 0; $i < hexdec($ln); $i++) {
            $payload .= $datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)];
            $crctmp = $crctmp ^ hexdec($datas[10 + ($i * 2)].$datas[10 + (($i * 2) + 1)]);
        }
        $quality = $datas[10 + ($i * 2) - 2].$datas[10 + ($i * 2) - 1];
        
        $payloadLength = strlen($payload) - 2;
        
        //verification du CRC
        //if ($crc == dechex($crctmp))
        // if ($crc != dechex($crctmp)) { return -2; }
        if ($crc != dechex($crctmp)) {
            deamonlog('error','CRC is not as expected ('.dechex($crctmp).') is '.$crc.' ');
        }
        
        deamonlog('debug','Type: '.$type.' quality: '.$quality);
        
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
                
            case "804E" :
                decode804E($mqtt, $payload, $ln, $qos);
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
                decode8102($mqtt, $payload, $ln, $qos);
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
        deamonlog('debug','type: 004d (Device announce)(Processed->MQTT)');
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
        
        deamonlog('debug','Src Addr : '.substr($payload, 0, 4));
        deamonlog('debug','IEEE : '.substr($payload, 4, 16));
        deamonlog('debug','MAC capa : '.substr($payload, 20, 2));
        $SrcAddr = substr($payload, 0, 4);
        $IEEE = substr($payload, 4, 16);
        $capability = substr($payload, 20, 2);
        
        // Envoie de la IEEE a Jeedom
        mqqtPublish($mqtt, $SrcAddr, "IEEE", "Addr", $IEEE, $qos);
        
        // Si routeur alors demande son nom (permet de declancher la creation des objets pour ampoules IKEA
        if ((hexdec($capability) & $test) == 14) {
            $data = 'Annonce';
            mqqtPublishAnnounce($mqtt, $SrcAddr, $data,$qos);
        }
    }
    
    function decode8000($mqtt, $payload, $ln, $qos)
    {
        $status = substr($payload, 0, 2);
        $SQN = substr($payload, 2, 2);
        deamonlog('debug','type: 8000 (Status)(Not Processed)');
        deamonlog('debug','Length: '.hexdec($ln));
        deamonlog('debug','Status: '.displayStatus($status));
        deamonlog('debug','SQN: '.$SQN);
    }
    
    function decode8001($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8001: (Log)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8002($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8002: (Data indication)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8003($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8003: (Liste des clusters de l’objet)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8004($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8004: (Liste des attributs de l’objet)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    function decode8005($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8005: (Liste des commandes de l’objet)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    function decode8006($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8006: (Non “Factory new” Restart)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    function decode8007($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8008: (“Factory New” Restart)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8009($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8009: (Data indication)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8010($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8010 (Version)(Processed->MQTT)');
        deamonlog('debug','Application : '.hexdec(substr($payload, 0, 4)));
        deamonlog('debug','SDK : '.hexdec(substr($payload, 4, 4)));
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
        deamonlog('debug','type: 8014: ( “Permit join” status response)(Processed->MQTT)');
        deamonlog('debug','Permit Join Status: '.substr($payload, 0, 2));
        
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
        
        deamonlog('debug','Type: 8015 (Abeille List)(Processed->MQTT) Payload: '.$payload);
        
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
            
            deamonlog('debug','Abeille i: '.$i);
            deamonlog('debug','ID : '.substr($payload, $i * 26 + 0, 2));
            deamonlog('debug','Short Addr : '.$SrcAddr);
            deamonlog('debug','IEEE Addr: '.$dataAddr);
            deamonlog('debug','Power Source (0:battery - 1:AC): '.$dataPower);
            deamonlog('debug','Link Quality: '.$dataLink);
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
        
        deamonlog('debug','Type: 8024: ( Network joined / formed )(Processed->MQTT)');
        deamonlog('debug','Satus : '               .$data);
        deamonlog('debug','short addr : '          .$dataShort);
        deamonlog('debug','extended address : '    .$dataIEEE);
        deamonlog('debug','Channel : '             .$dataNetwork);
        
    }
    
    function decode8028($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 8028: (Authenticate response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode802B($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 802B: (	User Descriptor Notify)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode802C($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 802C: (User Descriptor Response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8030($mqtt, $payload, $ln, $qos)
    {
        // <Sequence number: uint8_t>
        // <status: uint8_t>
        
        deamonlog('debug','Type: 8030: (	Bind response)(Decoded but Not Processed)');
        deamonlog('debug','SQN: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Status: 0x'.substr($payload, 2, 2));
        
    }
    
    function decode8031($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 8031: (unBind response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8034($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 8034: (Complex Descriptor response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    
    function decode8040($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 8040: (Network Address response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8041($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 8041: (IEEE Address response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8042($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','Type: 8042: (Node Descriptor response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
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
        
        deamonlog('debug','Type: 8043 (Simple Descriptor Response)(Not Processed)');
        deamonlog('debug','SQN : '             .substr($payload, 0, 2));
        deamonlog('debug','Status : '          .substr($payload, 2, 2));
        deamonlog('debug','Short Address : '   .substr($payload, 4, 4));
        deamonlog('debug','Length : '          .substr($payload, 8, 2));
        deamonlog('debug','endpoint : '        .substr($payload,10, 2));
        deamonlog('debug','profile : '         .substr($payload,12, 4));
        deamonlog('debug','deviceId : '        .substr($payload,16, 4));
        deamonlog('debug','bitField : '        .substr($payload,20, 2));
        deamonlog('debug','InClusterCount : '  .substr($payload,22, 2));
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
        deamonlog('debug','Type: 8044: (N	Power Descriptor response)(Not Processed)');
        deamonlog('debug',' (Not processed*************************************************************)');
        deamonlog('debug','  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug','Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8045($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug','type: 8045 (Active Endpoints Response)(Not Processed)');
        deamonlog('debug','SQN : '             .substr($payload, 0, 2));
        deamonlog('debug','Status : '          .substr($payload, 2, 2));
        deamonlog('debug','Short Address : '   .substr($payload, 4, 4));
        deamonlog('debug','Endpoint Count : '  .substr($payload, 8, 2));
        deamonlog('debug','Endpoint List :');
        for ($i = 0; $i < (intval(substr($payload, 8, 2)) * 2); $i += 2) {
            deamonlog('debug','Endpoint : '    .substr($payload, (10 + $i), 2));
        }
    }
    
    function decode8046($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8046: (Match Descriptor response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8047($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8047: (Management Leave response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8048($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', ' type: 8048 (Leave Indication)(Processed->Draft-MQTT)');
        deamonlog('debug', 'extended addr : '.substr($payload, 0, 16));
        deamonlog('debug', 'rejoin status : '.substr($payload, 16, 2));
        
        $SrcAddr = "Ruche";
        $ClusterId = "joinLeave";
        $AttributId = "IEEE";
        $data = "Leave->".substr($payload, 0, 16)."->".substr($payload, 16, 2);
        
        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
        
    }
    
    function decode804A($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 804A: (Management Network Update response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    
    function decode804B($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 804B: (	System Server Discovery response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    
    function decode804E($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 804E: (Management LQI response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    ##TODO
    ##Reponse groupe
    ##8060-8063
    function decode8060($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8060: (Add a group response)(Decoded but Not Processed)');
        // <Sequence number: uint8_t>
        // <endpoint: uint8_t>
        // <Cluster id: uint16_t>
        deamonlog('debug', 'SQN: '          .substr($payload, 0, 2));
        deamonlog('debug', 'endPoint: '     .substr($payload, 2, 2));
        deamonlog('debug', 'clusterId: '    .substr($payload, 4, 4));
    }
    
    
    
    function decode8062($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8062: (Group Memebership)(Decoded but Not Processed)');
        // <Sequence number: uint8_t>   -> 2
        // <endpoint: uint8_t>          -> 2
        // <Cluster id: uint16_t>       -> 4
        // <capacity: uint8_t>          -> 2
        // <Group count: uint8_t>       -> 2
        // <List of Group id: list each data item uint16_t>
        deamonlog('debug', 'payload length: '          .strlen($payload) );
        $groupSize = strlen($payload)-12-2; // 2 last are RSSI
        deamonlog('debug', 'group part of the payload length: '          .$groupSize );
        
        
        deamonlog('debug', 'SQN: '          .substr($payload, 0, 2));
        deamonlog('debug', 'endPoint: '     .substr($payload, 2, 2));
        deamonlog('debug', 'clusterId: '    .substr($payload, 4, 4));
        deamonlog('debug', 'capacity: '     .substr($payload, 8, 2));
        deamonlog('debug', 'group count: '  .substr($payload,10, 2));
        $groupCount = hexdec( substr($payload,10, 2) );
        for ($i=0;$i<$groupCount;$i++)
        {
            deamonlog('debug', 'group '.$i.'(addr:'.(12+$i*4).'): '  .substr($payload,12+$i*4, 4));
        }
        
        deamonlog('debug', 'Level: 0x'.substr($payload, strlen($payload)-2, 2));
        
    }
    
    function decode8063($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8063: (Remove a group response)(Decoded but Not Processed)');
        // <Sequence number: uin8_t>    -> 2
        // <endpoint: uint8_t>          -> 2
        // <Cluster id: uint16_t>       -> 4
        // <status: uint8_t>            -> 2
        // <Group id: uint16_t>         -> 4
        
        deamonlog('debug', 'SQN: '          .substr($payload, 0, 2));
        deamonlog('debug', 'endPoint: '     .substr($payload, 2, 2));
        deamonlog('debug', 'clusterId: '    .substr($payload, 4, 4));
        deamonlog('debug', 'statusId: '     .substr($payload, 8, 2));
        deamonlog('debug', 'groupId: '      .substr($payload,10, 4));
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
        deamonlog('debug', 'Type: 0x8100 (Read Attrib Response)(Processed->MQTT)');
        deamonlog('debug', 'SQN: '.substr($payload, 0, 2));
        deamonlog('debug', 'Src Addr: '.substr($payload, 2, 4));
        deamonlog('debug', 'EnPt: '.substr($payload, 6, 2));
        deamonlog('debug', 'Cluster Id: '.substr($payload, 8, 4));
        deamonlog('debug', 'Attribut Id: '.substr($payload, 12, 4));
        deamonlog('debug', 'Attribute Status: '.substr($payload, 16, 2));
        deamonlog('debug', 'Attribute data type: '.substr($payload, 18, 2));
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
        deamonlog('debug', ' Type: 8101 (Default Response)(Not Processed)');
        deamonlog(
                  'debug',
                  'Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.'
                  );
        deamonlog('debug', 'SQN : '.substr($payload, 0, 2));
        deamonlog('debug', 'EndPoint : '.substr($payload, 2, 2));
        displayClusterId(substr($payload, 4, 4));
        deamonlog('debug', 'Command : '.substr($payload, 8, 2));
        deamonlog('debug', 'Status : '.substr($payload, 10, 2));
    }
    
    function decode8102($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', ' Type: 8102 (Attribut Report)(Processed->MQTT)');
        deamonlog('debug', '['.date('Y-m-d H:i:s').']');
        
        //<Sequence number: uint8_t>
        //<Src address : uint16_t>
        //<Endpoint: uint8_t>
        //<Cluster id: uint16_t>
        //<Attribute Enum: uint16_t>
        //<Attribute status: uint8_t>
        //<Attribute data type: uint8_t>
        //<Size Of the attributes in bytes: uint16_t>
        //<Data byte list : stream of uint8_t>
        $SQN = substr($payload, 0, 2);
        $SrcAddr = substr($payload, 2, 4);
        deamonlog('debug', 'SQN: '.$SQN);
        deamonlog('debug', 'Src Addr : '.$SrcAddr);
        $ClusterId = substr($payload, 8, 4);
        $EPoint = substr($payload, 6, 2);
        $AttributId = substr($payload, 12, 4);
        $AttributStatus = substr($payload, 16, 2);
        $dataType = substr($payload, 18, 2);
        $AttributSize = substr($payload, 20, 4);
        deamonlog('debug', 'End Point : '.$EPoint);
        deamonlog('debug', 'Cluster ID : '.$ClusterId);
        deamonlog('debug', 'Attr ID : '.$AttributId);
        deamonlog('debug', 'Attr Status : '.$AttributStatus);
        deamonlog('debug', 'Attr Data Type : '.$dataType);
        deamonlog('debug', 'Attr Size : '.$AttributSize);
        deamonlog('debug', 'Data byte list : '.substr($payload, 24, (strlen($payload) - 24 - 2)));
        
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
            
            $data = hexdec(substr($payload, 24, 2));
            if ( $data>127 ) {
                deamonlog(
                          'debug',
                          'Warning, dataType 28, decodage avec hexdec mais ne fonctionne pas sur des nombres negatifs, si voyez cette ligne avec des nombres negatifs, il faut un correctif, pas le temps de le faire maintenant'
                          );
            }
        }
        // Example Temperature d un Xiaomi Carre
        // Sniffer dit Signed 16bit integer
        if ($dataType == "29") {
            // $data = hexdec(substr($payload, 24, 4));
            $data = unpack("s", pack("s", hexdec(substr($payload, 24, 4))))[1];
        }
        if ($dataType == "42") {
            
            // Xiaomi capteur temperature rond
            if (($AttributId == "ff01") && ($AttributSize == "001f")) {
                deamonlog(
                          'debug',
                          'Champ proprietaire Xiaomi, doit etre decodé (Capteur Temperature Rond)'
                          );
                $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                // $temperature = hexdec(substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2));
                $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                $humidity = hexdec(
                                   substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2)
                                   );
                
                deamonlog('debug', 'Voltage: '.$voltage);
                deamonlog('debug', 'Temperature: '.$temperature);
                deamonlog('debug', 'Humidity: '.$humidity);
                
                mqqtPublish(
                            $mqtt,
                            $SrcAddr,
                            $ClusterId,
                            $AttributId,
                            'Decoded as Volt-Temperature-Humidity',$qos
                            );
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                
                // Value decoded and value reported look aligned so I merge them.
                // mqqtPublish( $mqtt, $SrcAddr, 'Xiaomi',          '0402-0000',       $temperature    );
                // mqqtPublish( $mqtt, $SrcAddr, 'Xiaomi',          '0405-0000',       $humidity       );
                
                mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,$qos);
                mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,$qos);
                
            } // Xiaomi capteur temperature carré
            elseif (($AttributId == 'ff01') && ($AttributSize == '0025')) {
                deamonlog(
                          'debug',
                          'Champ proprietaire Xiaomi, doit etre decodé (Capteur Temperature Carré)'
                          );
                $voltage = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                // $temperature = hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) );
                $temperature = unpack("s", pack("s", hexdec( substr($payload, 24 + 21 * 2 + 2, 2).substr($payload, 24 + 21 * 2, 2) )))[1];
                
                $humidity = hexdec(
                                   substr($payload, 24 + 25 * 2 + 2, 2).substr($payload, 24 + 25 * 2, 2)
                                   );
                $pression = hexdec(
                                   substr($payload, 24 + 29 * 2 + 6, 2).substr($payload, 24 + 29 * 2 + 4, 2).substr(
                                                                                                                    $payload,
                                                                                                                    24 + 29 * 2 + 2,
                                                                                                                    2
                                                                                                                    ).substr($payload, 24 + 29 * 2, 2)
                                   );
                
                deamonlog('debug', 'Voltage: '.$voltage);
                deamonlog('debug', 'Temperature: '.$temperature);
                deamonlog('debug', 'Humidity: '.$humidity);
                deamonlog('debug', 'Pression: '.$pression);
                
                mqqtPublish(
                            $mqtt,
                            $SrcAddr,
                            $ClusterId,
                            $AttributId,
                            'Decoded as Volt-Temperature-Humidity',$qos
                            );
                mqqtPublish($mqtt, $SrcAddr, 'Batterie', 'Volt', $voltage,$qos);
                
                // Value decoded and value reported look aligned so I merge them.
                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0402-0000",         $temperature         );
                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0405-0000",         $humidity            );
                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0403-0010",         $pression/10        );
                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0403-0000",         $pression/100         );
                
                mqqtPublish($mqtt, $SrcAddr, '0402', '0000', $temperature,$qos);
                mqqtPublish($mqtt, $SrcAddr, '0405', '0000', $humidity,$qos);
                mqqtPublish($mqtt, $SrcAddr, '0403', '0010', $pression / 10,$qos);
                mqqtPublish($mqtt, $SrcAddr, '0403', '0000', $pression / 100,$qos);
            } // Xiaomi Door Sensor
            elseif (($AttributId == "ff01") && ($AttributSize == "0031")) {
                deamonlog(
                          "debug",
                          "Le door sensor envoie un paquet proprietaire 0x115F qu il va fallair traiter, ne suis pa sure de la longueur car je ne peux pas tester...."
                          );
            } // Xiaomi Wall Plug
            elseif (($AttributId == "ff01") && ($AttributSize == "0031")) {
                deamonlog('debug', 'Champ proprietaire Xiaomi, doit etre decodé (Wall Plug)');
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
                mqqtPublish($mqtt, $SrcAddr, 'Xiaomi', '0006-0000', $onOff,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'tbd', '--puissance--', $puissanceValue,$qos);
                mqqtPublish($mqtt, $SrcAddr, 'tbd', '--conso--', $consoValue,$qos);
            } // Xiaomi Presence Infrarouge
            elseif (($AttributId == "ff02")) {
                // Non decodé a ce stade
                deamonlog("debug", "Champ 0xFF02 non decode a ce stade");
            } else {
                $data = hex2bin(
                                substr($payload, 24, (strlen($payload) - 24 - 2))
                                ); // -2 est une difference entre ZiGate et NXP Controlleur.
            }
        }
        
        if (isset($data)) {
            deamonlog('debug', 'Data byte: '.$data);
            mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data, $qos);
            
        }
    }
    
    function decode8110($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8110: (	Write Attribute Response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8120($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8120: (Configure Reporting response)(Decoded but not Processed)');
        
        // <Sequence number: uint8_t>
        // <Src address : uint16_t>
        // <Endpoint: uint8_t>
        // <Cluster id: uint16_t>
        // <Status: uint8_t>
        deamonlog('debug', 'SQN: '              .substr($payload, 0, 2));
        deamonlog('debug', 'Source address: '   .substr($payload, 2, 4));
        deamonlog('debug', 'EndPoint: '         .substr($payload, 6, 2));
        deamonlog('debug', 'Cluster Id: '       .substr($payload, 8, 4));
        deamonlog('debug', 'Status: '           .substr($payload,12, 2));
        
    }
    
    function decode8140($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'Type: 8140: (Configure Reporting response)(Not Processed)');
        deamonlog('debug', ' (Not processed*************************************************************)');
        deamonlog('debug', '  Level: 0x'.substr($payload, 0, 2));
        deamonlog('debug', 'Message: ');
        deamonlog('debug',hex2str(substr($payload, 2, strlen($payload) - 2)));
    }
    
    function decode8701($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', ' type: 8701 (Route Discovery Confirm)(Not Processed)');
        deamonlog('debug', 'Status : '.substr($payload, 0, 2));
        deamonlog('debug', 'Nwk Status : '.substr($payload, 2, 2));
    }
    
    function decode8702($mqtt, $payload, $ln, $qos)
    {
        deamonlog('debug', 'type: 8701: (APS Data Confirm Fail)(Not Processed)');
        deamonlog('debug', 'Status : '.substr($payload, 0, 2));
        deamonlog('debug', 'Source Endpoint : '.substr($payload, 2, 2));
        deamonlog('debug', 'Destination Endpoint : '.substr($payload, 4, 2));
        deamonlog('debug', 'Destination Mode : '.substr($payload, 6, 2));
        deamonlog('debug', 'Destination Address : '.substr($payload, 8, 4));
        deamonlog('debug', 'SQN: : '.substr($payload, 12, 2));
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
    $mqtt = new phpMQTT($server, $port, $client_id);
    $fifoIN = new fifo( $in, 0777, "r" );
    //$zigateCluster= Tools::getJSonConfigFiles($GLOBALS['zigateJsonFileCluster']);
    $clusterTab = Tools::getJSonConfigFiles("zigateClusters.json");
    
    
    deamonlog(
              'info',
              'Starting parsing from '.$in.' to mqtt broker with log level '.$requestedlevel.' on '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos
              );
    
    if (!file_exists($in)) {
        deamonlog('error', 'ERROR, fichier '.$in.' n existe pas');
        exit(1);
    }
    
    while (true) {
        if (!file_exists($in)) {
            deamonlog('error', 'Erreur, fichier '.$in.' n existe pas');
            exit(1);
        }
        //traitement de chaque trame;
        $data = $fifoIN->read();
        protocolDatas( $data, $mqtt, $qos, $clusterTab);
        usleep(1);
        
    }
    
    deamonlog('warning', 'sortie du loop');
    
    ?>
