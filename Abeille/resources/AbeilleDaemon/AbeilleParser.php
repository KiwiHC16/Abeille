<?php
    include("includes/config.php");
    include("includes/fifo.php");
    
    include("lib/phpMQTT.php");
    
    function mqqtPublish( $mqtt, $SrcAddr, $ClusterId, $AttributId, $data ) {
        // Abeille / short addr / Cluster ID - Attr ID -> data
        if ($mqtt->connect(true, NULL, "jeedom", "jeedom")) {
            $mqtt->publish( "Abeille/" .$SrcAddr . "/" . $ClusterId . "-" . $AttributId,    $data,                  0 );
            $mqtt->publish( "Abeille/" .$SrcAddr . "/" . "Time"     . "-" . "TimeStamp",    time(),                 0 );
            $mqtt->publish( "Abeille/" .$SrcAddr . "/" . "Time"     . "-" . "Time",         date("Y-m-d H:i:s"),    0 );
            $mqtt->close();
        } else {
            echo "Time out!\n";
        }
    }
    
    function mqqtPublishAnnounce( $mqtt, $SrcAddr, $data ) {
        // Abeille / short addr / Annonce -> data
        if ($mqtt->connect(true, NULL, "jeedom", "jeedom")) {
            $mqtt->publish( "CmdAbeille/" .$SrcAddr . "/Annonce",    $data, 0 );
            $mqtt->close();
        } else {
            echo "Time out!\n";
        }
    }
    
    function hex2str($hex) {
        $str = '';
        for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
        return $str;
    }
    
    function displayClusterId($cluster)
    {
        $clusterTab["0000"]= " (General: Basic)";
        $clusterTab["0001"]= " (General: Power Config)";
        $clusterTab["0002"]= " (General: Temperature Config)";
        $clusterTab["0003"]= " (General: Identify)";
        $clusterTab["0004"]= " (General: Groups)";
        $clusterTab["0005"]= " (General: Scenes)";
        $clusterTab["0006"]= " (General: On/Off)";
        $clusterTab["0007"]= " (General: On/Off Config)";
        $clusterTab["0008"]= " (General: Level Control)";
        $clusterTab["0009"]= " (General: Alarms)";
        $clusterTab["000A"]= " (General: Time)";
        $clusterTab["000F"]= " (General: Binary Input Basic)";
        $clusterTab["0020"]= " (General: Poll Control)";
        $clusterTab["0019"]= " (General: OTA)";
        $clusterTab["0101"]= " (General: Door Lock";
        $clusterTab["0201"]= " (HVAC: Thermostat)";
        $clusterTab["0202"]= " (HVAC: Fan Control)";
        $clusterTab["0300"]= " (Lighting: Color Control)";
        $clusterTab["0400"]= " (Measurement: Illuminance)";
        $clusterTab["0402"]= " (Measurement: Temperature)";
        $clusterTab["0406"]= " (Measurement: Occupancy Sensing)";
        $clusterTab["0500"]= " (Security & Safety: IAS Zone)";
        $clusterTab["0702"]= " (Smart Energy: Metering)";
        $clusterTab["0B05"]= " (Misc: Diagnostics)";
        $clusterTab["1000"]= " (ZLL: Commissioning)";
        
        echo "  Cluster ID: " .$cluster;
        echo $clusterTab[$cluster]."\n";
        
    }
    
    function displayStatus($status)
    {
        switch ($status)
        {
            case "00": { echo "(Success)"."\n";                 } break;
            case "01": { echo "(Incorrect Parameters)"."\n";    } break;
            case "02": { echo "(Unhandled Command)"."\n";       } break;
            case "03": { echo "(Command Failed)"."\n";          } break;
            case "04": { echo "(Busy)"."\n";                    } break;
            case "05": { echo "(Stack Already Started)"."\n";   } break;
            default:   { echo "(ZigBee Error Code)"."\n";       } break;
        }
    }
    
    function protocolDatas($datas,$mqtt)
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
        echo "\n--------------\n";
        echo "protocolDatas\n";
        
        //Pourquoi 12, je ne sais pas.
        if ($length>=12)
        {
            echo "message > 12 char\n";
            $crctmp = 0;
            //type de message
            $type=$datas[0].$datas[1].$datas[2].$datas[3];
            $crctmp= $crctmp ^ hexdec($datas[0].$datas[1]) ^ hexdec($datas[2].$datas[3]);
            //taille message
            $ln=$datas[4].$datas[5].$datas[6].$datas[7];
            $crctmp= $crctmp ^ hexdec($datas[4].$datas[5]) ^ hexdec($datas[6].$datas[7]);
            //acquisition du CRC
            $crc=$datas[8].$datas[9];
            //payload
            $payload="";
            for($i=0;$i<hexdec($ln);$i++)
            {
                $payload.=$datas[10+($i*2)].$datas[10+(($i*2)+1)];
                $crctmp= $crctmp ^ hexdec($datas[10+($i*2)].$datas[10+(($i*2)+1)]);
            }
            $quality = $datas[10+($i*2)-2].$datas[10+($i*2)-1];
            
            $payloadLength = strlen($payload)-2;
            
            //verification du CRC
            //if ($crc == dechex($crctmp))
            if ( 1)
            {
                echo "Type: ".$type."\n";
                //Traitement PAYLOAD
                switch ($type)
                {
                        
                    case "004d" :
                        echo "\ntype: 004d";
                        echo "(Device announce)(Processed->MQTT)\n";
                        echo "Src Addr : ".substr($payload,0,4)."\n";
                        echo "IEEE : ".substr($payload,4,8)."\n";
                        echo "MAC capa : ".substr($payload,12,2)."\n";
                        echo "Quality : ".$quality;
                        $SrcAddr = substr($payload,0,4);
                        $data = "Annonce";
                        mqqtPublishAnnounce( $mqtt, $SrcAddr, $data );
                        break;
                    
                    case "8000" :
                        echo "\ntype: 8000\n";
                        echo " (Status)(Not processed)\n";
                        echo " Length: ".hexdec($ln)."\n";
                        echo " Status: ".substr($payload,0,2)."-"; displayStatus(substr($payload,0,2));
                        echo " SQN: ".substr($payload,2,2)."\n";
                        /*
                        if (hexdec(substr($payload,0,2)) > 2)
                        {
                            echo  "  Message: ";
                            echo hex2str(substr($payload,8,strlen($payload)-2))."\n";
                        }
                        */
                        break;
                        
                    case "8001" :
                        echo "\ntype: 8001";
                        echo " (Log)(Not processed*************************************************************)";
                        echo  "\n";
                        echo "  Level: 0x".substr($payload,0,2);
                        echo "\n"	;
                        echo  "  Message: ";
                        echo hex2str(substr($payload,2,strlen($payload)-2))."\n";
                        break;
                        
                    case "8010" :
                        echo "(Version)(Not processed)\n";
                        echo "Application : ".hexdec(substr($payload,0,4))."\n";
                        echo "SDK : ".hexdec(substr($payload,4,4))."\n";
                        break;
                        
                    case "8043" :
                        echo "\ntype: 8043";
                        echo "(Simple Descriptor Response)(Not processed)\n";
                        echo "SQN : ".substr($payload,0,2)."\n";
                        echo "Status : ".substr($payload,2,2)."\n";
                        echo "Short Address : ".substr($payload,4,4)."\n";
                        echo "Length : ".substr($payload,8,2)."\n";
                        if (intval(substr($payload,8,2))>0)
                        {
                            echo "Endpoint : ".substr($payload,10,2)."\n";
                            
                            //PAS FINI
                        }
                        break;
                        
                    case "8045" :
                        echo "\ntype: 8045";
                        echo "(Active Endpoints Response)(Not processed)\n";
                        echo "SQN : ".substr($payload,0,2)."\n";
                        echo "Status : ".substr($payload,2,2)."\n";
                        echo "Short Address : ".substr($payload,4,4)."\n";
                        echo "Endpoint Count : ".substr($payload,8,2)."\n";
                        echo "Endpoint List :" ."\n";
                        for ($i = 0; $i < (intval(substr($payload,8,2)) *2); $i+=2)
                        {
                            echo "Endpoint : ".substr($payload,(8+$i),2)."\n";
                        }
                        break;

                    case "8048":
                        echo "(Leave Indication)\n";
                        echo "extended addr : ".substr($payload,0,16)."\n";
                        echo "rejoin status : ".substr($payload,16,2)."\n";
                        break;
                        
                    case "8100": // "Type: 0x8100 (Read Attrib Response)"
                        // 8100 000D0C0Cb32801000600000010000101
                        echo "\ntype: 8100\n";
                        echo "Type: 0x8100 (Read Attrib Response)(Processed->MQTT)\n";
                        echo "SQN: : ".substr($payload,0,2)."\n";
                        echo "Src Addr: : ".substr($payload,2,4)."\n";
                        echo "EnPt: ".substr($payload,6,2)."\n";
                        echo "Cluster Id: ".substr($payload,8,4)."\n";
                        echo "Attribut Id: ".substr($payload,12,4)."\n";
                        echo "Attribute Status: ".substr($payload,16,2)."\n";
                        echo "Attribute data type: ".substr($payload,18,2)."\n";
                        $dataType = substr($payload,18,2);
                        // IKEA OnOff state reply data type: 10
                        // IKEA Manufecturer name data type: 42
                        echo "Syze of Attribute: ".substr($payload,20,4)."\n";
                        echo "Data byte list (one octet pour l instant): ".substr($payload,24,2)."\n";
                        
                        // short addr / Cluster ID / Attr ID -> data
                        $SrcAddr = substr($payload,2,4);
                        $ClusterId = substr($payload,8,4);
                        $AttributId = substr($payload,12,4);
                        
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
                        if ( $dataType=="10" )
                            {
                            $data = hexdec(substr($payload,24,2));
                            }
                        if ( $dataType=="20" )
                        {
                            $data = hexdec(substr($payload,24,2));
                        }
                        if ( $dataType=="42" )
                            {
                               $data = hex2bin(substr($payload,24,(strlen($payload)-24)));
                            }
                        echo "Data byte: ".$data."\n";
                        
                        mqqtPublish( $mqtt, $SrcAddr, $ClusterId, $AttributId, $data );

                        break;
                        
                    case "8101" :
                        echo "\ntype: 8101";
                        echo "(Default Response)(Not processed)\n";
                        echo "Le probleme c est qu on ne sait pas qui envoie le message, on a pas la source, sinon il faut faire un mapping avec SQN, ce que je ne veux pas faire.\n";
                        echo "SQN : ".substr($payload,0,2)."\n";
                        echo "EndPoint : ".substr($payload,2,2)."\n";
                        displayClusterId(substr($payload,4,4));
                        echo "Command : ".substr($payload,8,2)."\n";
                        echo "Status : ".substr($payload,10,2)."\n";
                        break;
                    
                    case "8102" :
                        echo "\ntype: 8102\n";
                        echo "[".date("Y-m-d H:i:s")."]\n";
                        echo "(Attribute Report)(Processed->MQTT)\n";
                        
                        //<Sequence number: uint8_t>
                        //<Src address : uint16_t>
                        //<Endpoint: uint8_t>
                        //<Cluster id: uint16_t>
                        //<Attribute Enum: uint16_t>
                        //<Attribute status: uint8_t>
                        //<Attribute data type: uint8_t>
                        //<Size Of the attributes in bytes: uint16_t>
                        //<Data byte list : stream of uint8_t>
                        echo "SQN: "            .substr($payload,0,2)."\n";
                        echo "Src Addr : "      .substr($payload,2,4)."\n";     $SrcAddr = substr($payload,2,4);
                        echo "End Point : "     .substr($payload,6,2)."\n";
                        echo "Cluster ID : "    .substr($payload,8,4)."\n";     $ClusterId = substr($payload,8,4);
                        echo "Attr ID : "       .substr($payload,12,4)."\n";    $AttributId = substr($payload,12,4);
                        echo "Attr Status : "   .substr($payload,16,2)."\n";
                        echo "Attr Data Type : ".substr($payload,18,2)."\n";    $dataType = substr($payload,18,2);
                        echo "Attr Size : "     .substr($payload,20,4)."\n";    $AttributSize = substr($payload,20,4);
                        echo "Data byte list : ".substr($payload,24,(strlen($payload)-24-2))."\n";
                        
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
                        if ( $dataType=="10" )
                        {
                            $data = hexdec(substr($payload,24,2));
                        }
                        if ( $dataType=="18" )
                        {
                            $data = substr($payload,24,2);
                        }
                        if ( $dataType=="20" )
                        {
                            $data = hexdec(substr($payload,24,2));
                        }
                        if ( $dataType=="21" )
                        {
                            $data = hexdec(substr($payload,24,4));
                        }
                        if ( $dataType=="28" )
                        {
                            $data = hexdec(substr($payload,24,2));
                        }
                        if ( $dataType=="29" )
                        {
                            $data = hexdec(substr($payload,24,4));
                        }
                        if ( $dataType=="42" )
                        {
                            if ( ($AttributId=="ff01") && ($AttributSize=="001f") )  // Xiaomi capteur temperature rond
                            {
                                echo "Champ proprietaire Xiaomi, doit etre decodé (Capteur Temperature Rond)\n";
                                $voltage        = hexdec(substr($payload,24+ 2*2+2,2).substr($payload,24+ 2*2,2))/1000;
                                $temperature    = hexdec(substr($payload,24+21*2+2,2).substr($payload,24+21*2,2));
                                $humidity       = hexdec(substr($payload,24+25*2+2,2).substr($payload,24+25*2,2));
                                
                                echo "Voltage: "        .$voltage."\n";
                                echo "Temperature: "    .$temperature."\n";
                                echo "Humidity: "       .$humidity."\n";
                                
                                mqqtPublish( $mqtt, $SrcAddr, $ClusterId,      $AttributId,    "Decoded as Volt-Temperature-Humidity"       );
                                mqqtPublish( $mqtt, $SrcAddr, "Batterie",      "Volt",         $voltage        );
                                mqqtPublish( $mqtt, $SrcAddr, "0402",          "0000",       $temperature    );
                                mqqtPublish( $mqtt, $SrcAddr, "0405",          "0000",       $humidity       );
                                
                            }
                            elseif ( ($AttributId=="ff01") && ($AttributSize=="0025") )  // Xiaomi capteur temperature carré
                            {
                                echo "Champ proprietaire Xiaomi, doit etre decodé (Capteur Temperature Carré)\n";
                                $voltage        = hexdec(substr($payload,24+ 2*2+2,2).substr($payload,24+ 2*2,2))/1000;
                                $temperature    = hexdec(substr($payload,24+21*2+2,2).substr($payload,24+21*2,2));
                                $humidity       = hexdec(substr($payload,24+25*2+2,2).substr($payload,24+25*2,2));
                                $pression       = hexdec(substr($payload,24+29*2+6,2).substr($payload,24+29*2+4,2).substr($payload,24+29*2+2,2).substr($payload,24+29*2,2));
                                
                                echo "Voltage: "        .$voltage."\n";
                                echo "Temperature: "    .$temperature."\n";
                                echo "Humidity: "       .$humidity."\n";
                                echo "Pression: "       .$pression."\n";
                                
                                mqqtPublish( $mqtt, $SrcAddr, $ClusterId,      $AttributId,    "Decoded as Volt-Temperature-Humidity"       );
                                mqqtPublish( $mqtt, $SrcAddr, "Batterie",      "Volt",         $voltage             );
                                mqqtPublish( $mqtt, $SrcAddr, "0402",          "0000",         $temperature         );
                                mqqtPublish( $mqtt, $SrcAddr, "0405",          "0000",         $humidity            );
                                mqqtPublish( $mqtt, $SrcAddr, "0403",          "0010",         $pression            );
                                mqqtPublish( $mqtt, $SrcAddr, "0403",          "0000",         intval($pression)    );
                            }
                            elseif ( ($AttributId=="ff01") && ($AttributSize=="0031") )  // Xiaomi Wall Plug
                            {
                                echo "Champ proprietaire Xiaomi, doit etre decodé (Wall Plug)\n";
                                $onOff        = hexdec(substr($payload,24+ 2*2,2));
                                echo "Puissance: ".    substr($payload,24+ 8*2,8)."\n";
                                $puissance    = unpack("f",pack('H*',substr($payload,24+ 8*2,8))); $puissanceValue = $puissance[1];
                                echo "Conso: ".        substr($payload,24+14*2,8)."\n";
                                $conso        = unpack("f",pack('H*',substr($payload,24+14*2,8))); $consoValue = $conso[1];
                                
                                echo "OnOff: "          .$onOff     ."\n";
                                echo "Puissance: "      .$puissanceValue ."\n";
                                echo "Consommation: "   .$consoValue     ."\n";
                                
                                mqqtPublish( $mqtt, $SrcAddr, $ClusterId,          $AttributId,    "Decoded as OnOff-Puissance-Conso"       );
                                mqqtPublish( $mqtt, $SrcAddr, "0006",          "0000",         $onOff           );
                                mqqtPublish( $mqtt, $SrcAddr, "tbd",          "--puissance--",       $puissanceValue       );
                                mqqtPublish( $mqtt, $SrcAddr, "tbd",          "--conso--",       $consoValue           );
                            }
                            else
                            {
                            $data = hex2bin(substr($payload,24,(strlen($payload)-24-2))); // -2 est une difference entre ZiGate et NXP Controlleur.
                            }
                        }
                        
                        if ( isset($data) )
                        {
                            echo "Data byte: ".$data."\n";
                            mqqtPublish( $mqtt, $SrcAddr, $ClusterId,          $AttributId,    $data );

                        }

                        break;
     
                    case "8701" :
                        echo "(Router Discovery Confirm)(Not processed)\n";
                        echo "Status : ".substr($payload,0,2)."\n";
                        echo "Nwk Status : ".substr($payload,2,2)."\n";
                        break;
                        
                    case "8702" :
                        echo "\ntype: 8702";
                        echo "(APS Data Confirm Fail)(Not processed)\n";
                        echo "Status : ".substr($payload,0,2)."\n";
                        echo "Source Endpoint : ".substr($payload,2,2)."\n";
                        echo "Destination Endpoint : ".substr($payload,4,2)."\n";
                        echo "Destination Mode : ".substr($payload,6,2)."\n";
                        echo "Destination Address : ".substr($payload,8,4)."\n";
                        echo "SQN: : ".substr($payload,12,2)."\n";
                        break;
                        
                    default:
                        
                        break;
                        
                }
                
            }else{
                $tab=-2;
            }
            
            
        }else{
            $tab=-1;
        }
        
        return $tab;
    }
    
    if (!file_exists(COM))
    { echo "Fichier ".COM." n existe pas\n";
        exit(1);
    }
    
    $fifoIN = new fifo( $in, 'r' );
    
    $server = "127.0.0.1";     // change if necessary
    $port = 1883;                     // change if necessary
    $username = "jeedom";                   // set your username
    $password = "jeedom";                   // set your password
    $client_id = "zigate"; // make sure this is unique for connecting to sever - you could use uniqid()
    $mqtt = new phpMQTT($server, $port, $client_id);
    
    while (true)
    {
        //traitement de chaque trame;
        $data= $fifoIN->read();
        echo protocolDatas($data,$mqtt);
        usleep(1);
        
    }
    
    ?>
