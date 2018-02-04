<?php
    include("includes/config.php");


    /****
     * cmdToAbeille called by AbeilleMQTTc to process message command
     * transform hex to binary and write to serial port.
     */

     /**
     * @param $msgtype
     * @param $length
     * @param $datas
     * @return string
     */
    function getChecksum($msgtype,$length,$datas)
    {
        if (1)
        {
            deamonlog('debug',"getChecksum()");
            deamonlog('debug', "msgtype: " . $msgtype );
            deamonlog('debug', "length: " . $length  );
            deamonlog('debug', "datas: " . $datas );
            /*echo "getChecksum()\n";
            echo "msgtype: " . $msgtype . "\n";
            echo "length: " . $length . "\n";
            echo "datas: " . $datas ."\n";
            */
        }
        
        $temp = 0;
        
        $temp ^= hexdec($msgtype[0].$msgtype[1]) ;
        $temp ^= hexdec($msgtype[2].$msgtype[3]) ;
        $temp ^= hexdec($length[0].$length[1]) ;
        $temp ^= hexdec($length[2].$length[3]);
        deamonlog('debug','len data: '.strlen($datas));
        //echo "len data: ".strlen($datas)."\n";
        
        for ($i=0;$i<=(strlen($datas));$i+=2)
        {
            // echo "i: ".$i."\n";
            $temp ^= hexdec($datas[$i].$datas[$i+1]);
        }
        
        
        return sprintf("%02X",$temp);
    }
    
    function transcode($datas)
    {
        $mess="";
        if (strlen($datas)%2 !=0)
        {
            return -1;
        }
        for ($i=0;$i<(strlen($datas));$i+=2)
        {
            $byte = $datas[$i].$datas[$i+1];
            
            if (hexdec($byte)>=hexdec(10))
            {
                $mess.=$byte;
                
            }else{
                $mess.="02".sprintf("%02X",(hexdec($byte) ^ 0x10));
            }
        }
        return $mess;
    }
    
    function getParam($dest,$address,$clusterId,$attributeId)
    {
        /*
         <address mode: uint8_t>
         <target short address: uint16_t>
         <source endpoint: uint8_t>
         <destination endpoint: uint8_t>
         <Cluster id: uint16_t>
         <direction: uint8_t>
         <manufacturer specific: uint8_t>
         <manufacturer id: uint16_t>
         <number of attributes: uint8_t>
         <attributes list: data list of uint16_t  each>
         Direction:
         0 - from server to client
         1 - from client to server
         Manufacturer specific :
         0 – No
         1 – Yes
         
         8 16 8 8 16 8 8 16 8 16 -> 2 4 2 2 4 2 2 4 2 4 -> 28/2d -> 14d -> 0x0E
         
         19:07:11.771 -> 01 02 11 02 10 02 10 02 1E 91 02 12 B3 28 02 11 02 11 02 10 02 16 02 10 02 10 02 10 02 10 02 11 02 10 02 10 03
         00:15:32.115 -> 01 02 11 02 10 02 10 02 1E 91 02 12 B3 28 02 11 02 11 02 10 02 16 02 10 02 10 02 10 02 10 02 11 02 10 02 10 03
         00:15:32.221 <- 01 80 00 00 04 86 00 03 01 00 03
         00:20:29.130 <- 01 80 00 00 04 80 00 05 01 00 03
         80 00 00 04 84 00 01 01 00
         00:15:32.248 <- 01 81 00 00 0D 02 03 B3 28 01 00 06 00 00 00 10 00 01 00 03
         00:20:29.156 <- 01 81 00 00 0D 05 05 B3 28 01 00 06 00 00 00 10 00 01 01 03
         81 00 00 0D 00 01 b3 28 01 00 06 00 00 00 10 00 01 00
         81 00 00 0D 06 06 b3 28 01 00 06 00 00 00 10 00 01 01
         
         01: start
         02 11 02 10: Msg Type: 02 11 => 01 02 10 => 00 ==> 0100 (read attribute request)
         02 10 02 1E: Length: 00 0E
         91: Chrksum
         02 12 B3 28 02 11 02 11 02 10 02 16 02 10 02 10 02 10 02 10 02 11 02 10 02 10: data
         02 12: address mode: 02
         B3 28: Short address: B328
         02 11: Source EndPoint: 01
         02 11: Dest EndPoint: 01
         02 10 02 16: Cluster Id: 00 06 (General On/Off)
         02 10: Direction: 0 (from server to client)
         02 10: manufacturer specific: 00 (No)
         02 10 02 10 : manufacturer id: 00 00
         02 11: number of attributes: 01
         02 10 02 10: attributes list: data list of uint16_t  each: 00 00
         
         */
        // $param = "02B3280101000600000000010000";
        // echo $param;
        // sendCmd( $dest, "0100", "000E", $param );
        // sendCmd( $dest, "0100", "000E", "02B3280101000600000000010000");
        $cmd = "0100";
        $lenth = "000E";
        $addressMode = "02";
        // $address = $Command['address'];
        $sourceEndpoint = "01";
        $destinationEndpoint = "01";
        //$ClusterId = "0006";
        $ClusterId = $clusterId;
        $Direction = "00";
        $manufacturerSpecific = "00";
        $manufacturerId = "0000";
        $numberOfAttributes = "01";
        // $attributesList = "0000";
        $attributesList = $attributeId;
        
        $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
        deamonlog('debug','len data: '.strlen($data));
        //echo "Read Attribute command data: ".$data."\n";
        
        sendCmd( $dest, $cmd, $lenth, $data );
    }
    
    
    function sendCmd( $dest, $cmd,$len,$datas)
    {
        // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
        $f=fopen($dest,"w");
        
        fwrite($f,pack("H*","01"));
        fwrite($f,pack("H*",transcode($cmd))); //MSG TYPE
        fwrite($f,pack("H*",transcode($len))); //LENGTH
        if (!empty($datas))
        {
            fwrite($f,pack("H*",getChecksum($cmd,$len,$datas))); //checksum
            fwrite($f,pack("H*",transcode($datas))); //datas
        }else{
            fwrite($f,pack("H*",getChecksum($cmd,$len,"00"))); //checksum
        }
        fwrite($f,pack("H*","03"));
        
        fclose($f);
    }


    /**
     * Main function called by AbeilleMQTTCmd to process command and send it in binzry to /dev/ttyUSB0
     *
     * @param $dest destination for binary command
     * @param $Command command to send
     * @param $loglevel write info to log
     */
    function processCmd( $dest, $Command,$_requestedlevel )
    // Dest: destination to send data in normal situation to /dev/ttyUSB0 or toto for debugging for example.
    // please keep command definition order by command Id, easier to match with documentation 1216
    {

        $GLOBALS['requestedlevel']=$_requestedlevel;

        if (!isset($Command)) return;
        
        // print_r( $Command );
        
        if ( isset($Command['getVersion']) )
        {
            
            if ($Command['getVersion']=="Version")
            {
                deamonlog('debug',"Get Version");
                sendCmd($dest,"0010","0000","");
            }
        }
        
        if ( isset($Command['reset']) )
        {
            if ($Command['reset']=="reset")
            {
                //    16:56:56.300 -> 01 02 10 11 02 10 02 10 11 03
                // 01 start
                // 02 10 11: 00 11: Reset
                // 02 10 02 10 : 00 00: Length
                // 11: crc
                // 03: Stop
                sendCmd($dest,"0011","0000","");
            }
        }

        // abeilleList abeilleListAll
        if ( isset($Command['abeilleList']) )
        {
            
            if ($Command['abeilleList']=="abeilleListAll")
            {
                deamonlog('debug',"Get Abeilles List");
                //echo "Get Abeilles List\n";
                sendCmd($dest,"0015","0000","");
            }
        }
        
      
        if ( isset($Command["startNetwork"]) )
        {
            if ($Command['startNetwork']=="StartNetwork")
            {
                sendCmd($dest,"0024","0000","");
            }
        }
        
        if ( isset($Command['SetPermit']) )
        {
            if ($Command['SetPermit']=="Inclusion")
            {
                
                
                // <target short address: uint16_t>
                // <interval: uint8_t>
                // <TCsignificance: uint8_t>
                
                // Target address: May be address of gateway node or broadcast (0xfffc)
                // Interval:
                // 0 = Disable Joining 1 – 254 = Time in seconds to allow joins 255 = Allow all joins
                // TCsignificance:
                // 0 = No change in authentication 1 = Authentication policy as spec
                
                // 09:08:29.156 -> 01 02 10 49 02 10 02 14 50 FF FC 1E 02 10 03
                // 01 : Start
                // 02 10 49: 00 49: Permit Joining request Msg Type = 0x0049
                // 02 10 02 14: Length:
                // 50: Chrksum
                // FF FC:<target short address: uint16_t>
                // 1E: <interval: uint8_t>
                // 02 10: <TCsignificance: uint8_t> 00
                
                // 09:08:29.193 <- 01 80 00 00 04 F4 00 39 00 49 03
                sendCmd($dest,"0049","0004","FFFCFE00"); //1E = 30 secondes
                
            }
        }
        
        if ( isset($Command['identifySend']) )
        {
            if (isset($Command['duration']))
            {
                $cmd = "0070";
                // Msg Type = 0x0070
                // Identify Send
                
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <time: uint16_t> Time: Seconds
                
                //                 Start  Type         Length           Short       Addr
                // 17:29:31.398 -> 01     02 10 70     02 10 02 17      10 02    12 6E    1B 02 11 02 11 02 10 10 03
                // 01: Start
                // 02 10 70: 00 70 - Msg Type Identify Send
                // 02 10 02 17 => Length -> 7
                // 10 02 => Mode 2 -> Short
                //
                
                // 17:29:31.461 <- 01 80 00 00 05 FE 00 0B 00 70 00 03
                // 17:29:31.523 <- 01 81 01 00 07 F5 0B 01 00 03 00 00 7B 03
                
                $addressMode = "02"; // Short Address -> 2
                $address = $Command['address']; // -> 4
                $sourceEndpoint = "01"; // -> 2
                $destinationEndpoint = "01"; // -> 2
                $time = $Command['duration']; // -> 4
                //  2 + 4 + 2 + 2 + 4 = 14/2 => 7
                $lenth = "0007";
                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $time ;
                
                sendCmd( $dest, $cmd, $lenth, $data );
                
            }
        }

        
        /*
         // Don't know how to make it works
         if ( isset($Command['touchLinkFactoryResetTarget']) )
        {
            if ($Command['touchLinkFactoryResetTarget']=="DO")
            {
                sendCmd($dest,"00D2","0000","");
            }
        }
        */
        
        // setLevel on one object
        if ( isset($Command['setLevel']) && isset($Command['address']) && isset($Command['clusterId']) && isset($Command['Level']) && isset($Command['duration']) )
        {
            $cmd = "0081";
            // 11:53:06.479 -> 01 02 10 81 02 10 02 19 C6 02 12 83 DF 02 11 02 11 02 11 AA 02 10 BB 03
            //                 01 02 10 81 02 10 02 19 d7 02 12 83 df 02 11 02 11 02 11 02 11 02 11 03
            //
            // 02 83 DF 01 01 01 ff 00 BB
            //
            // 01: Start
            // 02 10 81: Msg Type: 00 81 -> Move To Level
            // 02 10 02 19: Lenght
            // C6: CRC
            // 02 12: <address mode: uint8_t> : 02
            // 83 DF: <target short address: uint16_t> 83 DF (Lampe Z Ikea)
            // 02 11: <source endpoint: uint8_t>: 01
            // 02 11: <destination endpoint: uint8_t>: 01
            // 02 11: <onoff : uint8_t>: 01
            // AA: <Level: uint8_t > AA Value I put to identify easely: Level to reach
            // 02 10 BB: <Transition Time: uint16_t>: 00BB Value I put to identify easely: Transition duration
            $addressMode = "02";
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "01";
            $onoff = "01";
            if ( $Command['Level']<16 )
            {
                $level = "0".dechex($Command['Level']); // echo "setLevel: ".$Command['Level']."-".$level."-\n";
            }
            else
            {
                $level = dechex($Command['Level']); // echo "setLevel: ".$Command['Level']."-".$level."-\n";
            }
            
            // $duration = "00" . $Command['duration'];
            if ( $Command['duration']<16 )
            {
                $duration = "0".dechex($Command['duration']); // echo "duration: ".$Command['duration']."-".$duration."-\n";
            }
            else
            {
                $duration = dechex($Command['duration']); // echo "duration: ".$Command['duration']."-".$duration."-\n";
            }
            $duration = "00" . $duration;
            
            // 11:53:06.543 <- 01 80 00 00 04 53 00 56 00 81 03
            // 11:53:06.645 <- 01 81 01 00 06 DD 56 01 00 08 04 00 03
            // 8 16 8 8 8 8 16
            // 2  4 2 2 2 2  4 = 18/2d => 9d => 0x09
            $lenth = "0009";
            
            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $onoff . $level . $duration ;
            // echo "data: " . $data . "\n";
            
            sendCmd( $dest, $cmd, $lenth, $data );
            
            // getParam($dest,$address, $Command['clusterId'] );
            sleep(1);
            getParam($dest,$address, $Command['clusterId'], "0000" );
            getParam($dest,$address, $Command['clusterId'], "0000" );
            
            
        }
        
        // ReadAttributeRequest ------------------------------------------------------------------------------------
        // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
        if ( (isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) )
        {
            // echo "ReadAttributeRequest pour address: " . $Command['address'] . "\n";
            // if ( $Command['ReadAttributeRequest']==1 )
            //{
                getParam( $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'] );
            //}
        }
        
        if ( isset($Command['addGroup']) && isset($Command['address']) && isset($Command['groupAddress']) )
        {
            deamonlog('debug',"Add a group to an IKEA bulb");
            //echo "Add a group to an IKEA bulb\n";
            
            // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
            // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
            // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
            // Add Group
            // Message Description
            // Msg Type = 0x0060 Command ID = 0x00
            $cmd = "0060";
            $lenth = "0007";
            // <address mode: uint8_t>
            //<target short address: uint16_t>
            //<source endpoint: uint8_t>
            //<destination endpoint: uint8_t>
            //<group address: uint16_t>
            $addressMode = "02";
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "01";
            $groupAddress = $Command['groupAddress'];
            
            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupAddress ;
            sendCmd( $dest, $cmd, $lenth, $data );
        }
        
        if ( isset($Command['removeGroup']) && isset($Command['address']) && isset($Command['groupAddress']) )
        {
            deamonlog('debug',"Remove a group to an IKEA bulb");
            //echo "Remove a group to an IKEA bulb\n";
            
            // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
            // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
            // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
            // Add Group
            // Message Description
            // Msg Type = 0x0060 Command ID = 0x00
            $cmd = "0063";
            $lenth = "0007";
            // <address mode: uint8_t>
            //<target short address: uint16_t>
            //<source endpoint: uint8_t>
            //<destination endpoint: uint8_t>
            //<group address: uint16_t>
            $addressMode = "02";
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "01";
            $groupAddress = $Command['groupAddress'];
            
            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupAddress ;
            sendCmd( $dest, $cmd, $lenth, $data );
        }
        
        // ON / OFF one object
        if ( isset($Command['onoff']) && isset($Command['address']) && isset($Command['action']) && isset($Command['clusterId']) )
        {
            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <command ID: uint8_t>
            // Command Id
            // 0 - Off
            // 1 - On
            // 2 - Toggle
            
            $cmd = "0092";
            $lenth = "0006";
            $addressMode = "02";
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "01";
            $action = $Command['action'];
            
            sendCmd( $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action );
            $attribute = "0000";
            getParam($dest,$address, $Command['clusterId'], $attribute);
        }
        
        // Group of Objects ON/ OFF
        if ( isset($Command['groupOnOff']) && isset($Command['addressOnOff']) && isset($Command['action']) )
        {
            // 17:42:13.758 -> 01 02 10 92 02 10 02 16 CD 02 11 C2 98 02 11 02 11 02 12 03
            //                 01 02 10 92 02 10 02 16 cc 02 11 c2 98 02 11 02
            // 01: start
            // 02 10 92: 00 92 -> On/Off with no effect
            // 02 10 02 16: length
            // CD: crc
            // 02 11: <address mode: uint8_t>: 01 (Group et 02=Short)
            // C2 98: <target short address: uint16_t>: C2 98
            // 02 11: <source endpoint: uint8_t> 1
            // 02 11: <destination endpoint: uint8_t> 1
            // 02 12: <command ID: uint8_t>: 2 toggle
            // Command Id
            // 0 - Off
            // 1 - On
            // 2 - Toggle
            
            $cmd = "0092";
            $lenth = "0006";
            
            $addressMode = "01";
            $address = $Command['addressOnOff'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "01";
            $commandID = $Command['action'];
            
            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $commandID ;
            
            sendCmd( $dest, $cmd, $lenth, $data );
            
        }
        
        
        if ( isset($Command['getName']) && isset($Command['address']) )
        {
            deamonlog('debug','Get Name from: '.$Command['address']);
            //echo "Get Name from: ".$Command['address']."\n";
            getParam( $dest, $Command['address'], "0000", "0005" );
        }
    }
    
    ?>
