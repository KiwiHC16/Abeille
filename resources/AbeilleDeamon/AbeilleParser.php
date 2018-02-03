<?php

    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    
    include("includes/config.php");
    include("includes/fifo.php");
    include("lib/phpMQTT.php");
    include("lib/Tools.php");

    $clusterTab= Tools::getJSonConfigFiles('zigateClusters.json');

    function getNumberFromLeve($loglevel){
        if (strcasecmp($loglevel, "NONE")==0){$iloglevel=0;}
        if (strcasecmp($loglevel,"ERROR")==0){$iloglevel=1;}
        if (strcasecmp($loglevel,"WARNING")==0){$iloglevel=2;}
        if (strcasecmp($loglevel,"INFO")==0){$iloglevel=3;}
        if (strcasecmp($loglevel,"DEBUG")==0){$iloglevel=4;}
        return $iloglevel;
    }

    /***
     * if loglevel is lower/equal than the app requested level then message is written
     *
     * @param string $loglevel
     * @param string $message
     */
    function deamonlog($loglevel='NONE',$message =''){
        if (strlen($message)>=1  &&  getNumberFromLeve($loglevel) <= getNumberFromLeve($GLOBALS["requestedlevel"]) ) {
            fwrite(STDOUT, 'AbeilleParser: '.date("Y-m-d H:i:s").' '.$message . PHP_EOL); ;
        }
    }

    function mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data)
    {
        // Abeille / short addr / Cluster ID - Attr ID -> data
        if ($mqtt->connect(true, NULL, "jeedom", "jeedom")) {
            $mqtt->publish( "Abeille/" .$SrcAddr . "/" . $ClusterId . "-" . $AttributId,    $data,                  0 );
            $mqtt->publish( "Abeille/" .$SrcAddr . "/" . "Time"     . "-" . "TimeStamp",    time(),                 0 );
            $mqtt->publish( "Abeille/" .$SrcAddr . "/" . "Time"     . "-" . "Time",         date("Y-m-d H:i:s"),    0 );
            $mqtt->close();
        } else {
            deamonlog('WARNING','Time out!');
        }
    }

    function mqqtPublishAnnounce($mqtt, $SrcAddr, $data)
    {
        // Abeille / short addr / Annonce -> data
        if ($mqtt->connect(true, NULL, "jeedom", "jeedom")) {
            $mqtt->publish( "CmdAbeille/" .$SrcAddr . "/Annonce",    $data, 0 );
            $mqtt->close();
        } else {
            deamonlog('Time out!');
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
        deamonlog('debug','Cluster ID: '.$cluster.'- '.$GLOBALS['clusterTab'][$cluster]);
    }

    function displayStatus($status)
    {
        switch ($status)
        {
            case "00": { deamonlog('debug','00-(Success)');                 } break;
            case "01": { deamonlog('debug','01-(Incorrect Parameters)');    } break;
            case "02": { deamonlog('debug','02-(Unhandled Command)');       } break;
            case "03": { deamonlog('debug','03-(Command Failed)');          } break;
            case "04": { deamonlog('debug','04-(Busy)');                    } break;
            case "05": { deamonlog('debug','05-(Stack Already Started)');   } break;
            default:   { deamonlog('debug','(ZigBee Error Code)');       } break;
        }
    }

    function protocolDatas($datas, $mqtt)
        // datas: trame complete recue sur le port serie sans le start ni le stop.
        // 01: 01 Start
        // 02-03: Msg Type
        // 04-05: Length
        // 06: crc
        // 07-: Data / Payload
        // xx: 03 Stop
    {
        $tab="";
        $length=strlen($datas);
        deamonlog('info','-------------- '.date("Y-m-d H:i:s").': protocolData' );

        //Pourquoi 12, je ne sais pas.
        if ($length>=12)
        {
            deamonlog('debug','message > 12 char');
            $crctmp = 0;
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
            if (1) {
                deamonlog('debug','Type: '.$type.' quality: '.$quality);
                //Traitement PAYLOAD
                switch ($type) {

                    case "004d" :
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
                        
                        deamonlog('debug','Src Addr : '.substr($payload,0,4));
                        deamonlog('debug','IEEE : '.substr($payload,4,16));
                        deamonlog('debug','MAC capa : '.substr($payload,20,2));
                        deamonlog('debug','Quality : '.$quality);
                        $SrcAddr = substr($payload,0,4);
                        $IEEE = substr($payload,4,16);
                        $capability = substr($payload,20,2);
                        
                        // Envoie de la IEEE a Jeedom
                        mqqtPublish($mqtt, $SrcAddr, "IEEE", "Addr", $IEEE);

                        // Si routeur alors demande son nom (permet de declancher la creation des objets pour ampoules IKEA
                        if ((hexdec($capability) & $test) == 14) {
                            $data = 'Annonce';
                            mqqtPublishAnnounce($mqtt, $SrcAddr, $data);
                        }
                        break;

                    case "8000" :
                        deamonlog('debug','type: 8000 (Status)(Not Processed)');
                        deamonlog('debug','Length: '.hexdec($ln));
                        deamonlog('debug','Status: '.substr($payload, 0, 2).'-'.
                        displayStatus(substr($payload, 0, 2)));
                        deamonlog('debug','SQN: '.substr($payload, 2, 2));

                        break;

                    case "8001" :
                        deamonlog('debug','type: 8001: (Log)(Not Processed)' );
                        deamonlog('debug',' (Not processed*************************************************************)');
                        deamonlog('debug','  Level: 0x'.substr($payload,0,2));
                        deamonlog('debug','Message: ');
                        deamonlog(hex2str(substr($payload,2,strlen($payload)-2)));
                        break;

                    case "8010" :
                        deamonlog('debug','type: 8010 (Version)(Processed->MQTT)');
                        deamonlog('debug','Application : ".hexdec(substr($payload,0,4))');
                        deamonlog('debug','SDK : ".hexdec(substr($payload,4,4))');
                        $SrcAddr = "Ruche";
                        $ClusterId = "SW";
                        $AttributId = "Application";
                        $data = substr($payload, 0, 4);
                        deamonlog('debug',$AttributId.': '.$data);
                        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data);

                        $SrcAddr = "Ruche";
                        $ClusterId = "SW";
                        $AttributId = "SDK";
                        $data = substr($payload, 4, 4);
                        deamonlog('debug',$AttributId.': '.$data);
                        mqqtPublish($mqtt, $SrcAddr, $ClusterId, $AttributId, $data);
                        break;

                    case "8015" :
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
                        
                        deamonlog('debug','type: 8015 (Abeille List)(Processed->MQTT) Payload: ".$payload');
                        
                        $nb = (strlen( $payload )-2)/26;
                        deamonlog('debug','Nombre d\'abeilles: ".$nb');
                        
                        for ($i=0;$i<$nb;$i++)
                        {
                            deamonlog('debug','Abeille i: '.$i);
                            deamonlog('debug','ID : '                            .substr($payload,$i*26+ 0, 2));
                            deamonlog('debug','Short Addr : '                    .substr($payload,$i*26+ 2, 4));
                            deamonlog('debug','IEEE Addr: '                      .substr($payload,$i*26+ 6,16));
                            deamonlog('debug','Power Source (0:battery - 1:AC): '.substr($payload,$i*26+22, 2));
                            deamonlog('debug','Link Quality: '                   .hexdec(substr($payload,$i*26+24, 2)));

                            $SrcAddr = substr($payload,$i*26+ 2, 4);
                            
                            // Envoie IEEE
                            $ClusterId = "IEEE";
                            $AttributId="Addr";
                            $data =substr($payload,$i*26+ 6,16);
                            mqqtPublish( $mqtt, $SrcAddr, $ClusterId, $AttributId, $data );
                            
                            // Envoie Power Source
                            $ClusterId = "Power";
                            $AttributId="Source";
                            $data =substr($payload,$i*26+22, 2);
                            mqqtPublish( $mqtt, $SrcAddr, $ClusterId, $AttributId, $data );
                            
                            // Envoie Link Quaity
                            $ClusterId = "Link";
                            $AttributId="Quality";
                            $data = hexdec(substr($payload,$i*26+24, 2));
                            mqqtPublish( $mqtt, $SrcAddr, $ClusterId, $AttributId, $data );
                            
                        }

                        break;

                    case "8043" :

                        deamonlog('debug','type: 8043 (Simple Descriptor Response)(Not Processed)');
                        deamonlog('debug','SQN : '.substr($payload, 0, 2));
                        deamonlog('debug','Status : '.substr($payload, 2, 2));
                        deamonlog('debug','Short Address : '.substr($payload, 4, 4));
                        deamonlog('debug','Length : '.substr($payload, 8, 2));
                        if (intval(substr($payload,8,2))>0)
                        {
                            deamonlog('debug','Endpoint : ".substr($payload,10,2)');
                            
                            //PAS FINI
                        }
                        break;

                    case "8045" :
                        
                        deamonlog('debug','type: 8045 (Active Endpoints Response)(Not Processed)');
                        deamonlog('debug','SQN : '.substr($payload, 0, 2));
                        deamonlog('debug','Status : '.substr($payload, 2, 2));
                        deamonlog('debug','Short Address : '.substr($payload, 4, 4));
                        deamonlog('debug','Endpoint Count : '.substr($payload, 8, 2));
                        deamonlog('debug','Endpoint List :');
                        for ($i = 0; $i < (intval(substr($payload,8,2)) *2); $i+=2)
                        {
                            deamonlog('debug','Endpoint : ".substr($payload,(8+$i),2)');
                        }
                        break;

                    case "8048":
                        
                        deamonlog('debug',' type: 8048 (Leave Indication)(Not Processed)');
                        deamonlog('debug','extended addr : '.substr($payload, 0, 16));
                        deamonlog('debug','rejoin status : '.substr($payload, 16, 2));
                        
                        break;

                    case "8100": // "Type: 0x8100 (Read Attrib Response)"
                        // 8100 000D0C0Cb32801000600000010000101
                        /*
                        deamonlog('Type: 0x8100 (Read Attrib Response)(Processed->MQTT)');
                        deamonlog('SQN: : '.substr($payload, 0, 2));
                        deamonlog('Src Addr: : '.substr($payload, 2, 4));
                        deamonlog('EnPt: '.substr($payload, 6, 2));
                        deamonlog('Cluster Id: '.substr($payload, 8, 4));
                        deamonlog('Attribut Id: '.substr($payload, 12, 4));
                        deamonlog('Attribute Status: '.substr($payload, 16, 2));
                        deamonlog('Attribute data type: '.substr($payload, 18, 2));
                        */
                        deamonlog('debug','type: 8100');
                        deamonlog('debug','Type: 0x8100 (Read Attrib Response)(Processed->MQTT)');
                        deamonlog('debug','SQN: : ".substr($payload,0,2)');
                        deamonlog('debug','Src Addr: : ".substr($payload,2,4)');
                        deamonlog('debug','EnPt: ".substr($payload,6,2)');
                        deamonlog('debug','Cluster Id: ".substr($payload,8,4)');
                        deamonlog('debug','Attribut Id: ".substr($payload,12,4)');
                        deamonlog('debug','Attribute Status: ".substr($payload,16,2)');
                        deamonlog('debug','Attribute data type: ".substr($payload,18,2)');
                        $dataType = substr($payload,18,2);
                        // IKEA OnOff state reply data type: 10
                        // IKEA Manufecturer name data type: 42
                        /*
                        deamonlog('Syze of Attribute: '.substr($payload, 20, 4));
                        deamonlog('Data byte list (one octet pour l instant): '.substr($payload, 24, 2));
                        */
                        deamonlog('debug','Syze of Attribute: ".substr($payload,20,4)');
                        deamonlog('debug','Data byte list (one octet pour l instant): ".substr($payload,24,2)');
                        
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
                        deamonlog('Data byte: ".$data');
                        
                        mqqtPublish( $mqtt, $SrcAddr, $ClusterId, $AttributId, $data );
                        
                        break;

                    case "8101" :

                        deamonlog('debug',' type: 8101 (Default Response)(Not Processed)');
                        deamonlog('debug','Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.');
                        deamonlog('debug','SQN : '.substr($payload, 0, 2));
                        deamonlog('debug','EndPoint : '.substr($payload, 2, 2));
                        displayClusterId(substr($payload, 4, 4));
                        deamonlog('debug','Command : '.substr($payload, 8, 2));
                        deamonlog('debug','Status : '.substr($payload, 10, 2));
                        break;

                    case "8102" :

                        deamonlog('debug',' type: 8102 (Attribut Report)(Processed->MQTT)');
                        deamonlog('debug','['.date('Y-m-d H:i:s').']');

                        //<Sequence number: uint8_t>
                        //<Src address : uint16_t>
                        //<Endpoint: uint8_t>
                        //<Cluster id: uint16_t>
                        //<Attribute Enum: uint16_t>
                        //<Attribute status: uint8_t>
                        //<Attribute data type: uint8_t>
                        //<Size Of the attributes in bytes: uint16_t>
                        //<Data byte list : stream of uint8_t>
                        $SQN=substr($payload,0,2);
                        $SrcAddr = substr($payload,2,4);
                        deamonlog('debug','SQN: '            .$SQN);
                        deamonlog('debug','Src Addr : '      .$SrcAddr);
                        $ClusterId = substr($payload,8,4);
                        $EPoint=substr($payload,6,2);
                        $AttributId = substr($payload,12,4);
                        $AttributStatus=substr($payload,16,2);
                        $dataType = substr($payload,18,2);
                        $AttributSize = substr($payload,20,4);
                        deamonlog('debug','End Point : '     .$EPoint);
                        deamonlog('debug','Cluster ID : '.$ClusterId);
                        deamonlog('debug','Attr ID : '       .$AttributId);
                        deamonlog('debug','Attr Status : '   .$AttributStatus);
                        deamonlog('debug','Attr Data Type : '.$dataType);
                        deamonlog('debug','Attr Size : '     .$AttributSize);
                        deamonlog('debug','Data byte list : '.substr($payload,24,(strlen($payload)-24-2)));
                        
                        // valeur hexadécimale	- type -> function
                        // 0x00	Null
                        // 0x10	boolean                 -> hexdec
                        // 0x18	8-bit bitmap
                        // 0x20	uint8	unsigned char   -> hexdec
                        // 0x21	uint16                  -> hexdec
                        // 0x22	uint32
                        // 0x25	uint48
                        // 0x28	int8                    -> hexdec(2)
                        // 0x29	int16                   -> hexdec
                        // 0x2a	int32
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
                        if ($dataType == "28") {
                            $data = hexdec(substr($payload, 24, 2));
                        }
                        if ($dataType == "29") {
                            $data = hexdec(substr($payload, 24, 4));
                        }
                        if ($dataType == "42") {

                            // Xiaomi capteur temperature rond
                            if ( ($AttributId=="ff01") && ($AttributSize=="001f") )
                            {
                                deamonlog('debug','Champ proprietaire Xiaomi, doit etre decodé (Capteur Temperature Rond)');
                                $voltage        = hexdec(substr($payload,24+ 2*2+2,2).substr($payload,24+ 2*2,2));
                                $temperature    = hexdec(substr($payload,24+21*2+2,2).substr($payload,24+21*2,2));
                                $humidity       = hexdec(substr($payload,24+25*2+2,2).substr($payload,24+25*2,2));
                                
                                deamonlog('debug','Voltage: "        .$voltage');
                                deamonlog('debug','Temperature: "    .$temperature');
                                deamonlog('debug','Humidity: "       .$humidity');
                                
                                mqqtPublish( $mqtt, $SrcAddr, $ClusterId,      $AttributId,    "Decoded as Volt-Temperature-Humidity"       );
                                mqqtPublish( $mqtt, $SrcAddr, "Batterie",      "Volt",         $voltage        );
                                
                                // Value decoded and value reported look aligned so I merge them.
                                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0402-0000",       $temperature    );
                                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0405-0000",       $humidity       );
                                
                                mqqtPublish( $mqtt, $SrcAddr, "0402",          "0000",       $temperature    );
                                mqqtPublish( $mqtt, $SrcAddr, "0405",          "0000",       $humidity       );
                                
                            }
                            
                            // Xiaomi capteur temperature carré
                            elseif ( ($AttributId=="ff01") && ($AttributSize=="0025") )
                            {
                                deamonlog('debug','Champ proprietaire Xiaomi, doit etre decodé (Capteur Temperature Carré)');
                                $voltage        = hexdec(substr($payload,24+ 2*2+2,2).substr($payload,24+ 2*2,2));
                                $temperature    = hexdec(substr($payload,24+21*2+2,2).substr($payload,24+21*2,2));
                                $humidity       = hexdec(substr($payload,24+25*2+2,2).substr($payload,24+25*2,2));
                                $pression       = hexdec(substr($payload,24+29*2+6,2).substr($payload,24+29*2+4,2).substr($payload,24+29*2+2,2).substr($payload,24+29*2,2));
                                
                                deamonlog('debug','Voltage: '        .$voltage);
                                deamonlog('debug','Temperature: '    .$temperature);
                                deamonlog('debug','Humidity: '       .$humidity);
                                deamonlog('debug','Pression: '       .$pression);
                                
                                mqqtPublish( $mqtt, $SrcAddr, $ClusterId,      $AttributId,    "Decoded as Volt-Temperature-Humidity"       );
                                mqqtPublish( $mqtt, $SrcAddr, "Batterie",      "Volt",         $voltage );
                                
                                // Value decoded and value reported look aligned so I merge them.
                                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0402-0000",         $temperature         );
                                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0405-0000",         $humidity            );
                                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0403-0010",         $pression/10        );
                                // mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0403-0000",         $pression/100         );
                                
                                mqqtPublish( $mqtt, $SrcAddr, "0402",          "0000",         $temperature         );
                                mqqtPublish( $mqtt, $SrcAddr, "0405",          "0000",         $humidity            );
                                mqqtPublish( $mqtt, $SrcAddr, "0403",          "0010",         $pression/10        );
                                mqqtPublish( $mqtt, $SrcAddr, "0403",          "0000",         $pression/100         );
                            }
                            
                            // Xiaomi Door Sensor
                            elseif ( ($AttributId=="ff01") && ($AttributSize=="0031") ) {
                                deamonlog('debug','Le door sensor envoie un paquet proprietaire 0x115F qu il va fallair traiter, ne suis pa sure de la longueur car je ne peux pas tester....');
                            }
                            
                            // Xiaomi Wall Plug
                            elseif ( ($AttributId=="ff01") && ($AttributSize=="0031") )
                            {
                                deamonlog('debug','Champ proprietaire Xiaomi, doit etre decodé (Wall Plug)');
                                $onOff        = hexdec(substr($payload,24+ 2*2,2));
                                deamonlog('debug','Puissance: '.    substr($payload,24+ 8*2,8));
                                $puissance    = unpack("f",pack('H*',substr($payload,24+ 8*2,8))); $puissanceValue = $puissance[1];
                                deamonlog('debug','Conso: '.        substr($payload,24+14*2,8));
                                $conso        = unpack("f",pack('H*',substr($payload,24+14*2,8))); $consoValue = $conso[1];
                                
                                deamonlog('debug','OnOff: '          .$onOff);
                                deamonlog('debug','Puissance: '      .$puissanceValue);
                                deamonlog('debug','Consommation: '   .$consoValue);
                                
                                mqqtPublish( $mqtt, $SrcAddr, $ClusterId,          $AttributId,    "Decoded as OnOff-Puissance-Conso"       );
                                mqqtPublish( $mqtt, $SrcAddr, "Xiaomi",          "0006-0000",         $onOff           );
                                mqqtPublish( $mqtt, $SrcAddr, "tbd",          "--puissance--",       $puissanceValue       );
                                mqqtPublish( $mqtt, $SrcAddr, "tbd",          "--conso--",       $consoValue           );
                            }
                            
                            // Xiaomi Presence Infrarouge
                            elseif ( ($AttributId=="ff02") )
                            {
                                // Non decodé a ce stade
                                deamonlog('debug','Champ 0xFF02 non decode a ce stade');
                            }
                            
                            else
                            {
                                $data = hex2bin(substr($payload,24,(strlen($payload)-24-2))); // -2 est une difference entre ZiGate et NXP Controlleur.
                            }
                        }
                        
                        if ( isset($data) )
                        {
                            deamonlog('debug','Data byte: ".$data');
                            mqqtPublish( $mqtt, $SrcAddr, $ClusterId,          $AttributId,    $data );
                            
                        }

                        break;

                    case "8701" :
                        deamonlog('debug',' type: 8701 (Route Discovery Confirm)(Not Processed)');
                        deamonlog('debug','Status : '.substr($payload,0,2));
                        deamonlog('debug','Nwk Status : '.substr($payload,2,2));
                        break;

                    case "8702" :
                        deamonlog('debug','type: 8701: (APS Data Confirm Fail)(Not Processed)' );
                        deamonlog('debug','Status : '.substr($payload,0,2));
                        deamonlog('debug','Source Endpoint : '.substr($payload,2,2));
                        deamonlog('debug','Destination Endpoint : '.substr($payload,4,2));
                        deamonlog('debug','Destination Mode : '.substr($payload,6,2));
                        deamonlog('debug','Destination Address : '.substr($payload,8,4));
                        deamonlog('debug','SQN: : '.substr($payload,12,2));
                        break;

                    default:

                        break;

                }

            } else {
                $tab = -2;
            }


        } else {
            $tab = -1;
        }

        return $tab;
    }

    //                      1          2           3       4          5       6
    //$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;


    $serial = $argv[1];
    $server = $argv[2];     // change if necessary
    $port = $argv[3];                     // change if necessary
    $username = $argv[4];                   // set your username
    $password =  $argv[5];                   // set your password
    $client_id = "AbeilleParser"; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos=$argv[6];
    $requestedlevel=$argv[7];
    $requestedlevel=''?'none':$argv[7];
    $mqtt = new phpMQTT($server, $port, $client_id);
    $fifoIN = new fifo($in, 'r');
    //$zigateCluster= Tools::getJSonConfigFiles($GLOBALS['zigateJsonFileCluster']);
    $clusterTab= Tools::getJSonConfigFiles('zigateClusters.json');

    deamonlog('info','Starting reading port '.$serial.' with log level '.$requestedlevel.' on server='.$server.':'.$port.' username='.$username.' pass='.$password.' qos='.$qos);

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        deamonlog('debug','main: debug for com file: '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.'chmod 777 '.$serial.' > /dev/null 2>&1');
    }


    if (!file_exists($serial)) {
        deamonlog('error','ERROR, fichier '.$serial.' n existe pas');
        exit(1);
    }

    while (true) {
        if (!file_exists($serial)) {
            deamonlog('error','Erreur, fichier '.$serial.' n existe pas');
            exit(1);
        }
        //traitement de chaque trame;
        $data = $fifoIN->read();
        protocolDatas($data, $mqtt);
        usleep(1);

    }

    deamonlog('warning','sortie du loop');

?>
