<?php
    include_once __DIR__.'/AbeilleDebug.class.php';
    include_once __DIR__.'/../php/AbeilleZigbeeConst.php'; // Attribute type

    class AbeilleCmdProcess extends AbeilleDebug {

        /* Check if required param are set in 'Command' and are not empty.
           Returns: true if ok, else false */
        function checkRequiredParams($required, $Command) {
            $paramError = false;
            foreach ($required as $idx => $param) {
                if (isset($Command[$param])) {
                    if (gettype($Command[$param]) != "string")
                        continue; // No other check on non string types
                    if ($Command[$param] != '')
                        continue; // String not empty => ok
                    cmdLog('error', "Cmd '".$Command['name']."': Paramètre '".$param."' vide !");
                } else {
                    cmdLog('error', "Cmd '".$Command['name']."': Paramètre '".$param."' manquant.");
                }
                $paramError = true;
            }
            if ($paramError)
                return false;
            return true;
        }

        // Generate an APS SQN, auto-incremented
        function genSqn() {
            $sqn = sprintf("%02X", $this->lastSqn);
            if ($this->lastSqn == 255)
                $this->lastSqn = 0;
            else
                $this->lastSqn++;
            return $sqn;
        }

        /**
         * Converts signed decimal to hex (Two's complement)
         *
         * @param $value int, signed
         *
         * @param $reverseEndianness bool, if true reverses the byte order (see machine dependency)
         *
         * @return string, upper case hex value, both bytes padded left with zeros
         */
        function signed2hex($value, $size, $reverseEndianness = true) {
            $packed = pack('i', $value);
            $hex='';
            for ($i=0; $i < $size; $i++){
                $hex .= strtoupper( str_pad( dechex(ord($packed[$i])) , 2, '0', STR_PAD_LEFT) );
            }
            $tmp = str_split($hex, 2);
            $out = implode('', ($reverseEndianness ? array_reverse($tmp) : $tmp));
            return $out;
        }

        // Called to convert '#sliderXX#' or '#selectXX#'
        // 'XX' is always a decimal value
        function sliderToHex($sliderVal, $type) {
            $strDecVal = substr($sliderVal, 7, -1); // Extracting decimal value
            $signed = false;
            switch ($type) {
            case '20': // uint8
            case '30': // enum8
                $size = 1;
                break;
            case '21': // uint16
            case '31': // enum16
                $size = 2;
                break;
            case '22': // uint24
                $size = 3;
                break;
            case '23': // uint32
                $size = 3;
                break;
            case '28': // int8
                $size = 1;
                $signed = true;
                break;
            case '29': // int16
                $size = 2;
                $signed = true;
                break;
            case '2A': // int24
                $size = 3;
                $signed = true;
                break;
            case '2B': // int32
                $size = 4;
                $signed = true;
                break;
            default:
                cmdLog('error', "sliderToHex(): strDecVal=".$strDecVal.", UNSUPPORTED type=".$type);
                return false;
            }
            if ($signed)
                $strHexVal = $this->signed2hex($strDecVal, $size);
            else {
                $val = intval($strDecVal);
                $size = $size * 2; // 1 Byte = 2 hex char
                $format = "%0".$size."X";
                // cmdLog('debug', "    val=".strval($val).", size=".strval($size)." => format=".$format);
                $strHexVal = sprintf($format, $val);
            }

            cmdLog('debug', "    sliderToHex(): strDecVal=".$strDecVal." => ".$strHexVal);
            return $strHexVal;
        }

        /* Format attribute value.
           Return hex string formatted value according to its type */
        function formatAttribute($valIn, $type) {
            // cmdLog('debug', "formatAttribute(".$valIn.", ".$type.")");
            $valIn2 = $valIn;
            if (substr($valIn, 0, 7) == "#slider") {
                $valIn2 = $this->sliderToHex($valIn, $type);
            } else if (substr($valIn, 0, 7) == "#select") {
                $valIn2 = $this->sliderToHex($valIn, $type);
            }
            if ($valIn2 === false)
                return false;

            $valOut = '';
            switch ($type) {
            case '42': // string
                $len = sprintf("%02X", strlen($valIn2));
                $valOut = $len.bin2hex($valIn2);
                // cmdLog('debug', "len=".$len.", valOut=".$valOut);
                break;
            default:
                $valOut = $valIn2;
            }

            cmdLog('debug', "    formatAttribute(".$valIn.", type=".$type.") => valOut=".$valOut);
            return $valOut;
        }

        // Ne semble pas fonctionner et me fait planté la ZiGate, idem ques etParam()
        // Tcharp38: See https://github.com/KiwiHC16/Abeille/issues/2143#
        function ReportParamXiaomi($dest,$Command) {
            // Write Attribute request
            // Msg Type = 0x0110

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <direction: uint8_t>
            // <manufacturer specific: uint8_t>
            // <manufacturer id: uint16_t>
            // <number of attributes: uint8_t>
            // <attributes list: data list of uint16_t  each>
            //      Direction:
            //          0 - from server to client
            //          1 - from client to server
            //      Manufacturer specific :
            //          1 – Yes
            //          0 – No

            $priority = $Command['priority'];

            $cmd                    = "0110";

            $addrMode            = "02"; // Short Address -> 2
            $address                = $Command['address'];
            $srcEp         = "01";
            $dstEp    = "01";
            $clusterId              = $Command['clusterId'];
            $direction              = "00";
            $manufacturerSpecific   = "01";
            $proprio                = $Command['Proprio'];
            $numberOfAttributes     = "01";
            $attributeId            = $Command['attributeId'];
            $attributeType          = $Command['attributeType'];
            $value                  = $Command['value'];

            $data = $addrMode.$address.$srcEp.$dstEp.$clusterId.$direction.$manufacturerSpecific.$proprio.$numberOfAttributes.$attributeId.$attributeType.$value;

            // $length = sprintf("%04s", dechex(strlen($data) / 2));
            // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
            $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);

            if (isset($Command['repeat'])) {
                if ($Command['repeat']>1 ) {
                    for ($x = 2; $x <= $Command['repeat']; $x++) {
                        sleep(5);
                        // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                        $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                    }
                }
            }
        }

        // Tcharp38: Seems no longer used
        // J'ai un probleme avec la command 0110, je ne parviens pas à l utiliser. Prendre setParam2 en atttendant.
        // function setParam($dest,$address,$clusterId,$attributeId,$dstEp,$Param) {
        //     /*
        //         <address mode: uint8_t>
        //         <target short address: uint16_t>
        //         <source endpoint: uint8_t>
        //         <destination endpoint: uint8_t>
        //         <Cluster id: uint16_t>
        //         <direction: uint8_t>
        //         <manufacturer specific: uint8_t>
        //         <manufacturer id: uint16_t>
        //         <number of attributes: uint8_t>
        //         <attributes list: data list of uint16_t  each>
        //         Direction:
        //         0 - from server to client
        //         1 - from client to server
        //         */

        //     $priority               = $Command['priority'];

        //     $cmd                    = "0110";
        //     $length                  = "000E";

        //     $addrMode            = "02";
        //     // $address             = $Command['address'];
        //     $srcEp         = "01";
        //     // $dstEp = "01";
        //     //$ClusterId            = "0006";
        //     $Direction              = "01";
        //     $manufacturerSpecific   = "00";
        //     $manufacturerId         = "0000";
        //     $numberOfAttributes     = "01";
        //     $attributesList         = $attributeId;
        //     $attributesList         = "Salon1         ";

        //     $data = $addrMode.$address.$srcEp.$dstEp.$clusterId.$Direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$attributesList;

        //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
        //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
        // }

        // Tcharp38: No longer used
        // /**
        //  * setParam2: send the commande to zigate to send a write attribute on zigbee network
        //  *
        //  * @param dest
        //  * @param address
        //  * @param clusterId
        //  * @param attributeId
        //  * @param destinationEndPoint
        //  * @param Param
        //  * @param dataType
        //  *
        //  * @return none
        //  */
        // function setParam2( $dest, $address, $clusterId, $attributeId, $dstEp, $Param, $dataType) {
        //     cmdLog('debug', "  command setParam2", $this->debug['processCmd']);

        //     $priority = $Command['priority'];

        //     $cmd = "0530";

        //     // <address mode: uint8_t>              -> 1
        //     // <target short address: uint16_t>     -> 2
        //     // <source endpoint: uint8_t>           -> 1
        //     // <destination endpoint: uint8_t>      -> 1

        //     // <profile ID: uint16_t>               -> 2
        //     // <cluster ID: uint16_t>               -> 2

        //     // <security mode: uint8_t>             -> 1
        //     // <radius: uint8_t>                    -> 1
        //     // <data length: uint8_t>               -> 1  (22 -> 0x16)
        //     // <data: auint8_t>
        //     // APS Part <= data
        //     // dummy 00 to align mesages                                            -> 1
        //     // <target extended address: uint64_t>                                  -> 8
        //     // <target endpoint: uint8_t>                                           -> 1
        //     // <cluster ID: uint16_t>                                               -> 2
        //     // <destination address mode: uint8_t>                                  -> 1
        //     // <destination address:uint16_t or uint64_t>                           -> 8
        //     // <destination endpoint (value ignored for group address): uint8_t>    -> 1
        //     // => 34 -> 0x22

        //     $addrMode            = "02";
        //     $addr     = $address;
        //     $srcEp         = "01";
        //     $dstEp    = $dstEp;
        //     $profId              = "0104";
        //     $clustId              = $clusterId;
        //     $secMode           = "02"; // ???
        //     $radius                 = "30";
        //     // $dataLength <- calculated later

        //     $frameControl               = "00";
        //     $transqactionSequenceNumber = "1A"; // to be reviewed
        //     $commandWriteAttribute      = "02";

        //     $attributeId = $attributeId[2].$attributeId[3].$attributeId[0].$attributeId[1]; // $attributeId;

        //     $lengthAttribut = sprintf("%02s",dechex(strlen( $Param ))); // "0F";
        //     $attributValue = ""; for ($i=0; $i < strlen($Param); $i++) { $attributValue .= sprintf("%02s",dechex(ord($Param[$i]))); }

        //     $data2 = $frameControl.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$lengthAttribut.$attributValue;

        //     $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

        //     $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

        //     $data = $data1.$data2;

        //     // $length = sprintf("%04s", dechex(strlen($data) / 2));
        //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
        //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
        // }

        /**
         * setParam3: send the commande to zigate to send a write attribute on zigbee network with a proprio field
         *
         * @param dest
         * @param Command with following info: Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
         *
         * @return none
         */
        function setParam3($dest,$Command) {
            cmdLog('debug', "command setParam3", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addrMode                = "02";
            $addr         = $Command['address'];
            $srcEp             = "01";

            if (isset($Command['destinationEndpoint'])) {
                if ($Command['destinationEndpoint']>1 ) {
                    $dstEp = $Command['destinationEndpoint'];
                }
                else {
                    $dstEp = "01";
                }
            }
            else {
                $dstEp = "01";
            }

            $profId                  = "0104";
            $clustId                  = $Command['clusterId'];

            $secMode               = "02"; // ???
            $radius                     = "30";
            // $dataLength <- define later

            $frameControlAPS            = "40";   // APS Control Field
            // If Ack Request 0x40 If no Ack then 0x00
            // Avec 0x40 j'ai un default response

            $frameControlZCL            = "14";   // ZCL Control Field
            // Disable Default Response + Manufacturer Specific

            $frameControl = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $Proprio                    = $Command['Proprio'][2].$Command['Proprio'][3].$Command['Proprio'][0].$Command['Proprio'][1];

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId                = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType                   = $Command['attributeType'];

            $attributValue              = $Command['value'];

            $data2                      = $frameControl.$Proprio.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength                 = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1                      = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

            $data                       = $data1.$data2;

            // $length                      = sprintf("%04s",dechex(strlen($data) / 2));
            // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
            $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
        }

        /**
         * setParam4: send the commande to zigate to send a write attribute on zigbee network without a proprio field
         *
         * @param dest
         * @param Command with following info: clusterId=fc01&attributeId=0000&attributeType=09&value=0101
         *
         * @return none
         */
        function setParam4($dest,$Command) {

            cmdLog('debug', "command setParam4", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addrMode                = "02";
            $addr = $Command['address'];
            $srcEp             = "01";
            if ($Command['destinationEndpoint']>1 ) { $dstEp = $Command['destinationEndpoint']; } else { $dstEp = "01"; } // $dstEp; // "01";

            $profId                  = "0104";
            $clustId                  = $Command['clusterId'];

            $secMode               = "02"; // ???
            $radius                     = "30";
            // $dataLength <- calculated later

            $frameControlAPS            = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

            $frameControlZCL            = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific

            $frameControl               = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId                = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType                   = $Command['attributeType'];
            $attributValue              = $Command['value'];

            $data2                      = $frameControl.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength                 = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1                      = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

            $data                       = $data1.$data2;

            // $length                      = sprintf("%04s",dechex(strlen($data) / 2));
            // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
            $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
        }

        /**
         * setParamGeneric: send the commande to zigate to send a write attribute on zigbee network
         * This setParam try to be generic because there are too many setParam function above
         * Target: On long term would replace all of them.
         *
         * @param dest
         * @param Command with following info: clusterId=fc01&attributeId=0000&attributeType=09&value=0101
         *
         * @return none
         */
        function setParamGeneric($dest,$Command) {

            cmdLog('debug', "command setParam4", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addrMode        = "02";
            $addr = $Command['address'];
            $srcEp     = "01";
            if ($Command['destinationEndpoint']>1 ) { $dstEp = $Command['destinationEndpoint']; } else { $dstEp = "01"; } // $dstEp; // "01";

            $profId          = "0104";
            $clustId          = $Command['clusterId'];

            $secMode       = "02"; // ???
            $radius             = "30";
            // $dataLength <- calculated later

            $frameControlAPS    = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

            if ($Command['Proprio'] == '' ) {
                $frameControlZCL    = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific
            }
            else {
                $frameControlZCL    = "14";   // ZCL Control Field - Disable Default Response + Manufacturer Specific
            }
            $Proprio = $Command['Proprio'];

            $frameControl       = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId        = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType           = $Command['attributeType'];
            $attributValue      = $Command['value'];

            $data2 = $frameControl.$Proprio.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

            $data = $data1.$data2;

            // $length = sprintf("%04s", dechex(strlen($data) / 2));
            // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
            $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
        }

        // Generate a 'Read attribute request'
        function readAttribute($priority, $dest, $addr, $dstEp, $clustId, $attribId, $manufId = '') {
            /*
                <address mode: uint8_t>
                <target short address: uint16_t>
                <source endpoint: uint8_t>
                <destination endpoint: uint8_t>
                <Cluster id: uint16_t>
                <direction: uint8_t>
                    0 - from server to client
                    1 - from client to server
                <manufacturer specific: uint8_t>
                    0 – No
                    1 – Yes
                <manufacturer id: uint16_t>
                <number of attributes: uint8_t>
                <attributes list: data list of uint16_t  each>
            */

            $cmd = "0100"; // Cmd 100 request AckAPS based on ZigBee traces.

            $addrMode = "02";
            // TODO: Check 'addr' size
            $srcEp = "01";
            // TODO: Check 'dstEp' size
            // TODO: Check 'clustId' size
            $dir = "00";

            if (($manufId == "") || ($manufId == "0000")) {
                $manufSpecific = "00";
                $manufId = "0000";
            } else {
                $manufSpecific = "01";
                // TODO: check manufId size
            }

            /* Supporting multi-attributes (ex: '0505,0508,050B')
               Tcharp38 note: This way is not recommended as Zigate team is unable to tell the max number
                    of attributs for the request to be "functional". Seems ok up to 4. */
            $list = explode(',', $attribId);
            $attribList = "";
            $nbAttr = 0;
            foreach ($list as $attrId) {
                if (strlen($attrId) != 4) {
                    cmdLog('error', "readAttribute(): Format attribut (".$attrId.") incorrect => ignoré.");
                    continue;
                }
                $attribList .=  $attrId;
                $nbAttr++;
            }
            $nbOfAttrib = sprintf("%02X", $nbAttr);
            $data = $addrMode.$addr.$srcEp.$dstEp.$clustId.$dir.$manufSpecific.$manufId.$nbOfAttrib.$attribList;

            $this->addCmdToQueue2($priority, $dest, $cmd, $data, $addr, $addrMode);
        }

        /**
         * getParamMulti
         *
         * Read Attribut from a specific cluster. Can read multiple attribut in one message.
         * Doesn't ask for APS ACk, for default Response ZCL to reduce traffic on net
         *
         * @param Command including priority, dest, address, EP, clusterId, Proprio, attributId
         * proprio not implemented yet
         *
         *
         * @return none,
         */
        // function getParamMulti($Command) {
        //     cmdLog('debug', " command getParamMulti", $this->debug['processCmd']);

        //     $cmd = "0530";

        //     // <address mode: uint8_t>              -> 1
        //     // <target short address: uint16_t>     -> 2
        //     // <source endpoint: uint8_t>           -> 1
        //     // <destination endpoint: uint8_t>      -> 1

        //     // <profile ID: uint16_t>               -> 2
        //     // <cluster ID: uint16_t>               -> 2

        //     // <security mode: uint8_t>             -> 1
        //     // <radius: uint8_t>                    -> 1
        //     // <data length: uint8_t>               -> 1
        //     //                                                                                12 -> 0x0C
        //     // <data: auint8_t>
        //     // ZCL Control Field
        //     // ZCL SQN
        //     // Commad Id
        //     // ....

        //     $priority               = $Command['priority'];
        //     $dest                   = $Command['dest'];

        //     $addrMode            = "02";
        //     $addr     = $Command['address'];
        //     $srcEp         = "01";
        //     $dstEp    = $Command['EP'];
        //     $clustId              = $Command['clusterId'];
        //     $profId              = "0104";
        //     $secMode           = "02";
        //     $radius                 = "1E";

        //     $zclControlField        = "10";
        //     $transactionSequence    = "01";
        //     $cmdId                  = "00";

        //     $attributs = "";
        //     $attributList           = explode(',',$Command['attributeId']);

        //     foreach ($attributList as $attribut) {
        //         $attributs .= AbeilleTools::reverseHex(str_pad( $attribut, 4, "0", STR_PAD_LEFT));
        //     }

        //     $sep = "";
        //     $data2 = $zclControlField.$sep.$transactionSequence.$sep.  $cmdId.$sep.$attributs;
        //     $sep = "-";
        //     $data2Txt = $zclControlField.$sep.$transactionSequence.$sep.  $cmdId.$sep.$attributs;

        //     $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

        //     $sep = "";
        //     $data1 = $addrMode.$sep.$addr.$sep.$srcEp.$sep.$dstEp.$sep.$clustId.$sep.$profId.$sep.$secMode.$sep.$radius.$sep.$dataLength;
        //     $sep = "-";
        //     $data1Txt = $addrMode.$sep.$addr.$sep.$srcEp.$sep.$dstEp.$sep.$clustId.$sep.$profId.$sep.$secMode.$sep.$radius.$sep.$dataLength;

        //     $data = $data1.$data2;
        //     // $length = sprintf("%04s", dechex(strlen($data) / 2));
        //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
        //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
        // }

        // Tcharp38: Seems no longer used
        // getParamHue: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
        // function getParamHue($priority,$dest,$address,$clusterId,$attributeId) {
        //     cmdLog('debug','getParamHue', $this->debug['processCmd']);

        //     $priority = $Command['priority'];

        //     $cmd = "0100";
        //     $length = "000E";
        //     $addrMode = "02";
        //     // $address = $Command['address'];
        //     $srcEp = "01";
        //     $dstEp = "0B";
        //     //$ClusterId = "0006";
        //     $ClusterId = $clusterId;
        //     $Direction = "00";
        //     $manufacturerSpecific = "00";
        //     $manufacturerId = "0000";
        //     $numberOfAttributes = "01";
        //     // $attributesList = "0000";
        //     $attributesList = $attributeId;

        //     $data = $addrMode.$address.$srcEp.$dstEp.$ClusterId.$Direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$attributesList;

        //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
        //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
        // }

        // Tcharp38: Seems no longer used
        // getParamOSRAM: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
        // function getParamOSRAM($priority,$dest,$address,$clusterId,$attributeId) {
        //     cmdLog('debug','getParamOSRAM', $this->debug['processCmd']);

        //     $priority = $Command['priority'];

        //     $cmd = "0100";
        //     $length = "000E";
        //     $addrMode = "02";
        //     // $address = $Command['address'];
        //     $srcEp = "01";
        //     $dstEp = "03";
        //     //$ClusterId = "0006";
        //     $ClusterId = $clusterId;
        //     $Direction = "00";
        //     $manufacturerSpecific = "00";
        //     $manufacturerId = "0000";
        //     $numberOfAttributes = "01";
        //     // $attributesList = "0000";
        //     $attributesList = $attributeId;

        //     $data = $addrMode.$address.$srcEp.$dstEp.$ClusterId.$Direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$attributesList;

        //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
        //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
        // }

        /**
         * reviewPriority()
         *
         * See if we need to change the priority reaquested for a message
         *
         * @param Command
         * @return priority re-evaluated
         *
         */
        // function reviewPriority($Command) {
        //     if (isset($Command['priority'])) {
        //         // TODO: Eq Address and Group Address can't be distingueshed here. Probability to have a group address = eq address is low but exist.
        //         if (isset($Command['address'])) {
        //             if ($NE = Abeille::byLogicalId($Command['dest'].'/'.$Command['address'], 'Abeille')) {
        //                 if ($NE->getIsEnable()) {
        //                     if (( time() - strtotime($NE->getStatus('lastCommunication'))) < (60*$NE->getTimeout()) ) {
        //                         if ($NE->getStatus('APS_ACK', '1') == '1') {
        //                             return $Command['priority'];
        //                         }
        //                         else {
        //                             cmdLog('debug', "    NE n a pas repondu lors de precedente commande alors je mets la priorite au minimum.");
        //                             return priorityLostNE;
        //                         }
        //                     }
        //                     else {
        //                         cmdLog('debug', "    NE en Time Out alors je mets la priorite au minimum.");
        //                         return priorityLostNE;
        //                     }
        //                 }
        //                 else {
        //                     /* Tcharp38: Preventing cmd to be sent if EQ is disabled is not good here.
        //                     If EQ was disabled but now under pairing process (dev announce)
        //                     this prevents interrogation of EQ and therefore reinclusion.
        //                     This check should be done at source not here. At least don't filter
        //                     requests from parser. */
        //                     // cmdLog('debug', "    NE desactive, je n envoie pas de commande.");
        //                     // return -1;
        //                     return $Command['priority'];
        //                 }
        //             }
        //             else {
        //                 cmdLog('debug', "    NE n existe pas dans Abeille, une annonce/une commande de groupe, je ne touche pas à la priorite.");
        //                 return $Command['priority'];
        //             }
        //         }
        //         else {
        //             return $Command['priority'];
        //         }
        //     }
        //     else {
        //         cmdLog('debug', "    priority not defined !!!");
        //         return priorityInterrogation;
        //     }
        // }

        /**
         * processCmd()
         *
         * Convert an Abeille internal command to proper zigate format
         *
         * @param Command
         * @return None
         */
        function processCmd($Command) {
            global $abQueues;

            // Initial checks
            if (!isset($Command)) {
                cmdLog('debug', "    processCmd() ERROR: Command not set", $this->debug['processCmd']);
                return;
            }
            if (!isset($Command['dest'])) {
                cmdLog("debug", "    processCmd() ERROR: No dest defined, stop here");
                return;
            }

            cmdLog("debug", "    processCmd(".json_encode($Command).")", $this->debug['processCmd']);

            // $priority   = $this->reviewPriority($Command);
            // if ($priority==-1) {
            //     cmdLog("debug", "    L1 - processCmd - can t define priority, stop here");
            //     return;
            // }
            $dest       = $Command['dest'];

            //---- PDM ------------------------------------------------------------------
            if (isset($Command['PDM'])) {

                if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE") {
                    $cmd = "8300";

                    $PDM_E_STATUS_OK = "00";
                    $data = $PDM_E_STATUS_OK;

                    // $length = sprintf("%04s", dechex(strlen($data) / 2));
                    // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                }

                if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_EXISTENCE_RESPONSE") {
                    $cmd = "8208";

                    $recordId = $Command['recordId'];

                    $recordExist = "00";

                    if ($recordExist == "00" ) {
                        $size = '0000';
                        $persistedData = "";
                    }

                    $data = $recordId.$recordExist.$size;

                    // $length = sprintf("%04s", dechex(strlen($data) / 2));
                    // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                }
                return;
            }

            // abeilleList abeilleListAll
            if (isset($Command['abeilleList'])) {
                cmdLog('debug', "    Get Abeilles List", $this->debug['processCmd']);
                // $this->addCmdToQueue($priority,$dest,"0015","0000","");
                $this->addCmdToQueue2(PRIO_NORM, $dest, "0015");
                return;
            }

            if (isset($Command['setCertificationCE'])) {
                cmdLog('debug', "    setCertificationCE", $this->debug['processCmd']);
                $cmd = "0019";
                $data = "01";

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            if (isset($Command['setCertificationFCC'])) {
                cmdLog('debug', "    setCertificationFCC", $this->debug['processCmd']);
                $cmd = "0019";
                $data = "02";

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            //----------------------------------------------------------------------
            // https://github.com/fairecasoimeme/ZiGate/issues/145
            // PHY_PIB_TX_POWER_DEF (default - 0x80)
            // PHY_PIB_TX_POWER_MIN (minimum - 0)
            // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
            if (isset($Command['TxPower'])  ) {
                cmdLog('debug', "    TxPower", $this->debug['processCmd']);
                $cmd = "0806";
                $data = $Command['TxPower'];
                if ($data < 10 ) $data = '0'.$data;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            if (isset($Command['setExtendedPANID'])) {
                cmdLog('debug', "    setExtendedPANID", $this->debug['processCmd']);
                $cmd = "0020";
                $data = $Command['setExtendedPANID'];

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            if (isset($Command["getNetworkStatus"])) {
                $priority = isset($Command['priority']) ? $Command['priority']: PRIO_NORM;
                $this->addCmdToQueue2($priority, $dest, "0009");
                return;
            }

            //----------------------------------------------------------------------
            // Bind
            // Title => 000B57fffe3025ad (IEEE de l ampoule)
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006
            if (isset($Command['bind'])) // Tcharp38: OBSOLETE !! Use "bind0030" instead
            {
                cmdLog('debug', "    command bind", $this->debug['processCmd']);
                // Msg Type = 0x0030
                $cmd = "0030";

                // <target extended address: uint64_t>                                  -> 16
                // <target endpoint: uint8_t>                                           -> 2
                // <cluster ID: uint16_t>                                               -> 4
                // <destination address mode: uint8_t>                                  -> 2
                // <destination address:uint16_t or uint64_t>                           -> 4 / 16 => 0000 for Zigate
                // <destination endpoint (value ignored for group address): uint8_t>    -> 2

                // $targetExtendedAddress  = "000B57fffe3025ad";
                $targetExtendedAddress  = $Command['address'];
                //
                if (isset($Command['targetEndpoint'])) {
                    $targetEndpoint         = $Command['targetEndpoint'];
                }
                else {
                    $targetEndpoint         = "01";
                }

                // $clustId              = "0006";
                $clustId              = $Command['ClusterId'];
                // $destinationAddressMode = "02";
                $destinationAddressMode = "03";

                // $destinationAddress     = "0000";
                // $destinationAddress     = "00158D0001B22E24";
                $destinationAddress     = $Command['reportToAddress'];

                $dstEp    = "01";

                $data = $targetExtendedAddress.$targetEndpoint.$clustId.$destinationAddressMode.$destinationAddress.$dstEp;
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            // BindToGroup
            // Pour Telecommande RC110
            // Tcharp38: Why the need of a 0530 based function ?
            if (isset($Command['BindToGroup']))
            {
                cmdLog('debug', "    command BindToGroup", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                                            -> 1
                // <target extended address: uint64_t>                                  -> 8
                // <target endpoint: uint8_t>                                           -> 1
                // <cluster ID: uint16_t>                                               -> 2
                // <destination address mode: uint8_t>                                  -> 1
                // <destination address:uint16_t or uint64_t>                           -> 8
                // <destination endpoint (value ignored for group address): uint8_t>    -> 1
                // => 34 -> 0x22

                $addrMode                = "02";
                $addr         = $Command['address'];
                $srcEpBind         = "00";
                $dstEpBind    = "00";
                $profIdBind              = "0000";
                $clustIdBind              = "0021";
                $secMode               = "02";
                $radius                     = "30";
                // $dataLength                 = "16";

                $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

                $targetExtendedAddress      = AbeilleTools::reverseHex($Command['targetExtendedAddress']);
                $targetEndpoint             = $Command['targetEndpoint'];
                $clustId                  = AbeilleTools::reverseHex($Command['clusterID']);
                $destinationAddressMode     = "01";
                $reportToGroup              = AbeilleTools::reverseHex($Command['reportToGroup']);

                $data2 = $dummy.$targetExtendedAddress.$targetEndpoint.$clustId .$destinationAddressMode.$reportToGroup;
                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addrMode.$addr.$srcEpBind.$dstEpBind.$clustIdBind.$profIdBind.$secMode.$radius.$dataLength;

                cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEpBind."-".$dstEpBind."-".$clustIdBind."-".$profIdBind."-".$secMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2), $this->debug['processCmd'] );
                cmdLog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clustId."-".$destinationAddressMode."-".$reportToGroup." len: ".(strlen($data2)/2), $this->debug['processCmd'] );

                $data = $data1.$data2;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // Bind Short
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed
            // Tcharp38: Why the need of a 0530 based function ?
            // if (isset($Command['bindShort'])) {
            //     cmdLog('debug', "    command bind short", $this->debug['processCmd']);
            //     // Msg Type = 0x0530
            //     $cmd = "0530";

            //     // <address mode: uint8_t>              -> 1
            //     // <target short address: uint16_t>     -> 2
            //     // <source endpoint: uint8_t>           -> 1
            //     // <destination endpoint: uint8_t>      -> 1

            //     // <profile ID: uint16_t>               -> 2
            //     // <cluster ID: uint16_t>               -> 2

            //     // <security mode: uint8_t>             -> 1
            //     // <radius: uint8_t>                    -> 1
            //     // <data length: uint8_t>               -> 1  (22 -> 0x16)
            //     // <data: auint8_t>
            //     // APS Part <= data
            //     // dummy 00 to align mesages                                            -> 1
            //     // <target extended address: uint64_t>                                  -> 8
            //     // <target endpoint: uint8_t>                                           -> 1
            //     // <cluster ID: uint16_t>                                               -> 2
            //     // <destination address mode: uint8_t>                                  -> 1
            //     // <destination address:uint16_t or uint64_t>                           -> 8
            //     // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            //     // => 34 -> 0x22

            //     $addrMode                = "02";
            //     $addr         = $Command['address'];
            //     $srcEpBind         = "00";
            //     $dstEpBind    = "00";
            //     $profIdBind              = "0000";
            //     $clustIdBind              = "0021";
            //     $secMode               = "02";
            //     $radius                     = "30";
            //     $dataLength                 = "16";

            //     $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

            //     if (strlen($Command['targetExtendedAddress']) < 2 ) {
            //         cmdLog('debug', "  command bind short: param targetExtendedAddress is empty. Can t do. So return", $this->debug['processCmd']);
            //         return;
            //     }
            //     $targetExtendedAddress = AbeilleTools::reverseHex($Command['targetExtendedAddress']);

            //     $targetEndpoint = $Command['targetEndpoint'];

            //     $clustId = AbeilleTools::reverseHex($Command['clusterID']);

            //     $destinationAddressMode = "03";
            //     if (strlen($Command['destinationAddress']) < 2 ) {
            //         cmdLog('debug', "  command bind short: param destinationAddress is empty. Can t do. So return", $this->debug['processCmd']);
            //         return;
            //     }
            //     $destinationAddress = AbeilleTools::reverseHex($Command['destinationAddress']);

            //     $dstEp = $Command['destinationEndpoint'];

            //     $length = "0022";

            //     $data1 = $addrMode.$addr.$srcEpBind.$dstEpBind.$clustIdBind.$profIdBind.$secMode.$radius.$dataLength;
            //     $data2 = $dummy.$targetExtendedAddress.$targetEndpoint.$clustId .$destinationAddressMode.$destinationAddress.$dstEp;

            //     cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEpBind."-".$dstEpBind."-".$clustIdBind."-".$profIdBind."-".$secMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2), $this->debug['processCmd'] );
            //     cmdLog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clustId."-".$destinationAddressMode."-".$destinationAddress."-".$dstEp." len: ".(strlen($data2)/2), $this->debug['processCmd'] );

            //     $data = $data1.$data2;

            //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
            //     return;
            // }

            // setReport
            // Title => setReport
            // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
            if (isset($Command['setReport'])) // Tcharp38: OBSOLETE. Use 'configureReporting' instead.
            {
                cmdLog('debug', "    command setReport", $this->debug['processCmd']);
                // Configure Reporting request
                // Msg Type = 0x0120

                $cmd = "0120";

                // <address mode: uint8_t>              -> 2
                // <target short address: uint16_t>     -> 4
                // <source endpoint: uint8_t>           -> 2
                // <destination endpoint: uint8_t>      -> 2
                // <Cluster id: uint16_t>               -> 4
                // <direction: uint8_t>                 -> 2
                // <manufacturer specific: uint8_t>     -> 2
                // <manufacturer id: uint16_t>          -> 4
                // <number of attributes: uint8_t>      -> 2
                // <attributes list: data list of uint16_t  each>
                //      Attribute direction : uint8_t   -> 2
                //      Attribute type : uint8_t        -> 2
                //      Attribute id : uint16_t         -> 4
                //      Min interval : uint16_t         -> 4
                //      Max interval : uint16_t         -> 4
                //      Timeout : uint16_t              -> 4
                //      Change : uint8_t                -> 2

                $addrMode            = "02";
                $addr     = $Command['address'];
                if (isset( $Command['sourceEndpoint'] )) { $srcEp = $Command['sourceEndpoint']; } else { $srcEp = "01"; }
                if (isset( $Command['targetEndpoint'] )) { $targetEndpoint = $Command['targetEndpoint']; } else { $targetEndpoint = "01"; }
                $ClusterId              = $Command['ClusterId'];
                $direction              = "00";                     //
                $manufacturerSpecific   = "00";                     //
                $manufacturerId         = "0000";                   //
                $numberOfAttributes     = "01";                     // One element at a time

                if (isset( $Command['AttributeDirection'] )) { $AttributeDirection = $Command['AttributeDirection']; } else { $AttributeDirection = "00"; } // See if below

                $AttributeId            = $Command['AttributeId'];    // "0000"

                if ($AttributeDirection == "00" ) {
                    if (isset($Command['AttributeType'])) {$AttributeType = $Command['AttributeType']; }
                    else {
                        cmdLog('error', "set Report with an AttributeType not defines for equipment: ". $addr." attribut: ".$AttributeId." can t process" , $this->debug['processCmd']);
                        return;
                    }
                    if (isset($Command['MinInterval']))   { $MinInterval  = $Command['MinInterval']; } else { $MinInterval    = "0000"; }
                    if (isset($Command['MaxInterval']))   { $MaxInterval  = $Command['MaxInterval']; } else { $MaxInterval    = "0000"; }
                    if (isset($Command['Change']))        { $Change       = $Command['Change']; }      else { $Change         = "00"; }
                    $Timeout = "ABCD";
                }
                else if ($AttributeDirection == "01" ) {
                    $AttributeType          = "12";     // Put crappy info to see if they are passed to zigbee by zigate but it's not.
                    $MinInterval            = "3456";   // Need it to respect cmd 0120 format.
                    $MaxInterval            = "7890";
                    $Change                 = "12";
                    if (isset($Command['Timeout']))   { $Timeout      = $Command['Timeout']; }     else { $Timeout        = "0000"; }
                }
                else {
                    cmdLog('error', "set Report with an AttributeDirection (".$AttributeDirection.") not valid for equipment: ". $addr." attribut: ".$AttributeId." can t process", $this->debug['processCmd']);
                    return;
                }

                $data =  $addrMode.$addr.$srcEp.$targetEndpoint.$ClusterId.$direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$AttributeDirection.$AttributeType.$AttributeId.$MinInterval.$MaxInterval.$Timeout.$Change ;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // setReportRaw
            // Title => setReportRaw
            // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
            // For the time being hard coded to run tests but should replace setReport due to a bug on Timeout of command 0120. See my notes.
            if (isset($Command['setReportRaw'])) { // Tcharp38: OBSOLETE. Use 'configureReporting' instead.
                cmdLog('debug', "   command setReportRaw", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // ZCL Control Field
                // ZCL SQN
                // Commad Id
                // ....

                $addrMode            = "02";
                $addr     = $Command['address'];
                $srcEp         = "01";
                $dstEp    = "01";
                $profId              = "0104";
                $clustId              = "0B04";
                $secMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "10";
                $transactionSequence    = "01";
                $cmdId                  = "06";
                $directionReport        = "00";

                // $attributeType          = "29";      // Puissance
                $attributeType          = "21";         // A

                // $attribut               = "0B05";    // Puissance
                $attribut               = "0805";       // A

                $MinInterval            = "0500";
                $MaxInterval            = "2C01";
                $change                 = "0100";

                $data2 = $zclControlField.$transactionSequence.$cmdId.$directionReport.$attribut.$attributeType.$MinInterval.$MaxInterval.$change;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

                cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEp."-".$dstEp."-".$clustId."-".$profId."-".$secMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                cmdLog('debug', "  Data2: ".$zclControlField."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // CmdAbeille/0000/commissioningGroupAPS -> address=a048&groupId=AA00
            // Commission group for Ikea Telecommande On/Off still interrupteur
            if (isset($Command['commissioningGroupAPS']))
            {
                cmdLog('debug', "    commissioningGroupAPS", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id: Get Group Id Response
                // 01 Total
                // 00 Start Index
                // 01 Count
                // 00 Group Type
                // 001B Group Id

                $addrMode            = "02";
                $addr     = $Command['address'];
                $srcEp         = "01";
                $dstEp    = "01";
                $profId              = "0104";
                $clustId              = "1000";
                $secMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "19"; // Cluster specific + server to client
                $transactionSequence    = "01";
                $cmdId                  = "41";

                $total                  = "01";
                $startIndex             = "00";
                $count                  = "01";
                $groupId                = AbeilleTools::reverseHex($Command['groupId']);
                $groupType              = "00";

                $data2 = $zclControlField.$transactionSequence.$cmdId.$total.$startIndex.$count.$groupId.$groupType;
                $dataLength = sprintf( "%02s", dechex(strlen($data2) / 2));
                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                $data = $data1.$data2;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // CmdAbeille/0000/commissioningGroupAPSLegrand -> address=a048&groupId=AA00
            // Commission group for Legrand Telecommande On/Off still interrupteur Issue #1290
            if (isset($Command['commissioningGroupAPSLegrand']))
            {
                cmdLog('debug', "    commissioningGroupAPSLegrand", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id: Get Group Id Response
                // 01 Total
                // 00 Start Index
                // 01 Count
                // 00 Group Type
                // 001B Group Id

                $addrMode            = "02";
                $addr     = $Command['address'];
                $srcEp         = "01";
                $dstEp    = "01";
                $profId              = "0104";
                $clustId              = "FC01";
                $secMode           = "02";
                $radius                 = "14";

                $zclControlField        = "1D";
                $Manufacturer           = AbeilleTools::reverseHex("1021");
                $transactionSequence    = "01";
                $cmdId                  = "08";
                $groupId                = AbeilleTools::reverseHex($Command['groupId']);

                $data2 = $zclControlField.$Manufacturer.$transactionSequence.$cmdId.$groupId ;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

                cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEp."-".$dstEp."-".$clustId."-".$profId."-".$secMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                cmdLog('debug', "  Data2: ".$zclControlField."-".$Manufacturer."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data );
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            if (isset($Command['viewScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A0";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['storeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A4";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['recallScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode                    = "02";                                    // Short Address -> 2
                $address                        = $Command['address'];                         // -> 4
                $srcEp                 = "01";                                 // -> 2
                $dstEp            = $Command['DestinationEndPoint']; // -> 2

                $groupID                        = $Command['groupID'];
                $sceneID                        = $Command['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['sceneGroupRecall']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode                    = "01";                  // Group Address -> 1, Short Address -> 2
                $address                        = $Command['groupID'];   // -> 4
                $srcEp                 = "01";                  // -> 2
                $dstEp            = "02";                  // -> 2

                $groupID                        = $Command['groupID'];
                $sceneID                        = $Command['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['addScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) && isset($Command['sceneName']))
            {
                $cmd = "00A1";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>
                // <transition time: uint16_t>
                // <scene name length: uint8_t>
                // <scene name max length: uint8_t>
                // <scene name data: data each element is uint8_t>

                $addrMode            = "02";
                $address                = $Command['address'];
                $srcEp         = "01";
                $dstEp    = $Command['DestinationEndPoint'];

                $groupID                = $Command['groupID'];
                $sceneID                = $Command['sceneID'];

                $transitionTime         = "0001";

                $sceneNameLength        = sprintf("%02s", (strlen( $Command['sceneName'] )/2));      // $Command['sceneNameLength'];
                $sceneNameMaxLength     = sprintf("%02s", (strlen( $Command['sceneName'] )/2));      // $Command['sceneNameMaxLength'];
                $sceneNameData          = $Command['sceneName'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID.$transitionTime.$sceneNameLength.$sceneNameMaxLength.$sceneNameData ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['getSceneMembership']) && isset($Command['address']) && isset($Command['DestinationEndPoint']))
            {
                $cmd = "00A6";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['removeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A2";

                //0x00A2
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['removeSceneAll']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']))
            {
                $cmd = "00A3";

                //0x00A3
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['sceneLeftIkea']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']))
            {
                cmdLog('debug', "  Specific Command to simulate Ikea Telecommand < and >");

                // Msg Type = 0x0530
                $cmd = "0530";

                $addrMode = "01"; // 01 pour groupe
                $addr = $Command['address'];
                $srcEpBind = "01";
                $dstEpBind = "01";
                $profIdBind = "0104";
                $clustIdBind = "0005";
                $secMode = "02";
                $radius = "30";
                // $dataLength = "16";

                $FrameControlField = "05";  // 1
                $manu = "7C11";
                $SQN = "00";
                $cmdIkea = "07";
                $cmdIkeaParams = "00010D00";

                $data2 = $FrameControlField.$manu.$SQN.$cmdIkea.$cmdIkeaParams;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

                $data1 = $addrMode.$addr.$srcEpBind.$dstEpBind.$clustIdBind.$profIdBind.$secMode.$radius.$dataLength;

                cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEpBind."-".$dstEpBind."-".$clustIdBind."-".$profIdBind."-".$secMode."-".$radius."-".$dataLength." len: ".(dechex(strlen($data1)/2)), $this->debug['processCmd'] );
                cmdLog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clustId."-".$destinationAddressMode."-".$destinationAddress."-".$dstEp." len: ".(dechex(strlen($data2)/2)), $this->debug['processCmd'] );

                $data = $data1.$data2;
                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            if (isset($Command['WindowsCoveringLevel']) && isset($Command['address']) && isset($Command['clusterCommand']))
            {
                // echo "Windows Covering test for Store Ikea: lift to %\n";

                $cmd = '0530';

                $addrMode            = "02";                // 01 pour groupe, 02 pour NE
                $addr     = $Command['address'];
                $srcEp         = "01";
                $dstEp    = "01";
                $profile                = "0104";
                $cluster                = "0102";
                $secMode           = "02";
                $radius                 = "30";
                // $dataLength = "16";

                $FrameControlField      = "11";
                $SQN                    = "00";
                $cmdLift                = "05"; // 00: Up, 01: Down, 02: Stop, 04: Go to lift value (not supported), 05: Got to lift pourcentage.
                $liftValue              = $Command['liftValue'];

                $data2 = $FrameControlField.$SQN.$cmdLift.$liftValue.$liftValue;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

                $data1 = $addrMode.$addr.$srcEp.$dstEp.$cluster.$profile.$secMode.$radius.$dataLength;

                $data = $data1.$data2;
                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
            if (isset($Command['WindowsCoveringGroup']) && isset($Command['address']) && isset($Command['clusterCommand']))
            {
                // 0x00FA    Windows covering (v3.0f only)
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <cluster command : uint8_t>
                // 0 = Up/Open
                // 1 = Down/Close
                // 2 = Stop
                // 4 = Go To Lift Value (extra cmd : Value in cm)
                // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                // 7 = Go To Tilt Value (extra cmd : Value in cm)
                // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)
                // <extra command : uint8_t or uint16_t >

                $cmd = "00FA";

                $addrMode    = "04"; // 01 pour groupe, 02 pour NE, 03 pour , 04 pour broadcast
                $address        = $Command['address'];
                $srcEp          = "01";
                $detEP          = "01";
                $clusterCommand = $Command['clusterCommand'];

                $data = $addrMode.$address.$srcEp.$detEP.$clusterCommand;

                // $length = sprintf("%04s", dechex(strlen($data )/2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // /* Expected format:
            // net/0000 ActiveEndpointRequest address=<addr>*/
            // if (isset($Command['ActiveEndPoint'])) // OBSOLETE: Use getActiveEndpoints instead
            // {
            //     $cmd = "0045";

            //     // <target short address: uint16_t>

            //     $address = $Command['address']; // -> 4

            //     //  4 = 4/2 => 2
            //     // $length = "0002";

            //     $data = $address;

            //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
            //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
            //     return;
            // }

            //----------------------------------------------------------------------------
            if (isset($Command['Network_Address_request']))
            {
                $cmd = "0040";

                // <target short address: uint16_t> -> 4
                // <extended address:uint64_t>      -> 16
                // <request type: uint8_t>          -> 2
                // <start index: uint8_t>           -> 2
                // Request Type:
                // 0 = Single Request 1 = Extended Request
                // -> 24 / 2 = 12 => 0x0C

                $address = $Command['address'];
                $IeeeAddress = $Command['IEEEAddress'];
                $requestType = "01";
                $startIndex = "00";

                $data = $address.$IeeeAddress.$requestType.$startIndex ;
                // $length = "000C"; // A verifier

                cmdLog('debug', '    Network_Address_request: '.$data.' - '.$length, $this->debug['processCmd']  );

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if (isset($Command['identifySend']) && isset($Command['address']) && isset($Command['duration']) && isset($Command['DestinationEndPoint']))
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

                $addrMode = "02"; // Short Address -> 2
                $address = $Command['address']; // -> 4
                $srcEp = "01"; // -> 2
                $dstEp = $Command['DestinationEndPoint']; // -> 2
                $time = $Command['duration']; // -> 4
                //  2 + 4 + 2 + 2 + 4 = 14/2 => 7
                // $length = "0007";
                $data = $addrMode.$address.$srcEp.$dstEp.$time ;

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // Don't know how to make it works
            if (isset($Command['touchLinkFactoryResetTarget']))
            {
                if ($Command['touchLinkFactoryResetTarget']=="DO")
                {
                    // $this->addCmdToQueue($priority,$priority,$dest,"00D2","0000");
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "00D2");
                }
                return;
            }

            if (isset($Command['moveToLiftAndTiltBSO']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['inclinaison']) && isset($Command['duration']))
            {
                cmdLog('debug', "    command moveToLiftAndTiltBSO", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id
                // etc

                $addrMode            = $Command['addressMode'];
                $addr     = $Command['address'];
                $srcEp         = "01";
                $dstEp    = "01";
                $profId              = "0104";
                $clustId              = "0008";
                $secMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "05";
                $ManfufacturerCode      = "1011"; // inverted
                $transactionSequence    = "01";
                $cmdId                  = "10";  // Cmd Proprio Profalux
                $option                 = "03";  // Je ne touche que le Tilt
                // $Lift                   = "00";  // Not used / between 1 and 254 (see CurrentLevel attribute)
                $Lift                   = sprintf( "%02s",dechex($Command['lift']));
                // $Tilt                   = "2D";  // 2D move to 45deg / between 0 and 90 (See CurrentOrentation attribute)
                $Tilt                   = sprintf( "%02s",dechex($Command['inclinaison']));  // 2D move to 45deg / between 0 and 90 (See CurrentOrentation attribute)
                // $transitionTime         = "FFFF"; // FFFF ask the Tilt to move as fast as possible
                $transitionTime         = sprintf( "%04s",dechex($Command['duration']));

                $data2 = $zclControlField.$ManfufacturerCode.$transactionSequence.$cmdId.$option.$Lift.$Tilt.$transitionTime ;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

                cmdLog('debug', "    Data1: ".$addrMode."-".$addr."-".$srcEp."-".$dstEp."-".$clustId."-".$profId."-".$secMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                cmdLog('debug', "    Data2: ".$zclControlField."-".$ManfufacturerCode."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // // setLevelStop => Obsolete: Use 'cmd-0008' + 'cmd=07' instead
            // if (isset($Command['setLevelStop']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['sourceEndpoint']) && isset($Command['destinationEndpoint']))
            // {
            //     // <address mode: uint8_t>
            //     // <target short address: uint16_t>
            //     // <source endpoint: uint8_t>
            //     // <destination endpoint: uint8_t>

            //     $cmd = "0084"; // Stop with OnOff = Cluster 0008, cmd 07
            //     $addrMode            = $Command['addressMode'];
            //     $address                = $Command['address'];
            //     $srcEp         = $Command['sourceEndpoint'];
            //     $dstEp    = $Command['destinationEndpoint'];

            //     $data = $addrMode.$address.$srcEp.$dstEp ;

            //     // $length = sprintf("%04s", dechex(strlen($data) / 2));
            //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
            //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
            //     return;
            // }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequest'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']))
            {
                $this->setParam3( $dest, $Command );
                return;
            }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequestGeneric'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['attributeType']) && isset($Command['value']))
            {
                $this->setParamGeneric( $dest, $Command );
                return;
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequestVibration'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']))
            {
                // Tcharp38: WHere is this code ??? $this->setParamXiaomi($dest, $Command);
                // cmdLog('debug', "ERROR: WriteAttributeRequestVibration() CAN'T be executed. Missing setParamXiaomi()", $this->debug['processCmd']);
                $this->ReportParamXiaomi($dest, $Command);
                return;
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequestActivateDimmer'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']))
            {
                $this->setParam4( $dest, $Command );
                return;
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            // if (isset($Command['ReadAttributeRequest'])) {
            //     if (isset($Command['address']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']) && isset($Command['Proprio']))
            //         $this->readAttribute(PRIO_NORM, $dest, $Command['address'], $Command['EP'], $Command['clusterId'], $Command['attributeId'], $Command['Proprio'] );
            //     else if ((isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']))
            //         $this->readAttribute(PRIO_NORM, $dest, $Command['address'], $Command['EP'], $Command['clusterId'], $Command['attributeId']);
            //     return;
            // }

            // if (isset($Command['readAttributeRequest'])) {
            //     $this->readAttribute(PRIO_NORM, $dest, $Command['addr'], $Command['ep'], $Command['clustId'], $Command['attrId']);
            //     return;
            // }

            // ReadAttributeRequestMulti ------------------------------------------------------------------------------------
            // if ((isset($Command['ReadAttributeRequestMulti'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']))
            // {
            //     $this->getParamMulti( $Command );
            //     return;
            // }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            // if ((isset($Command['ReadAttributeRequestHue'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']))
            // {
            //     // echo "ReadAttributeRequest pour address: ".$Command['address']."\n";
            //     // if ($Command['ReadAttributeRequest']==1 )
            //     //{
            //     // $this->getParamHue( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "0B" );
            //     $this->readAttribute($priority, $dest, $Command['address'], "0B", $Command['clusterId'], $Command['attributeId']);
            //     //}
            //     return;
            // }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            // if ((isset($Command['ReadAttributeRequestOSRAM'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']))
            // {
            //     // echo "ReadAttributeRequest pour address: ".$Command['address']."\n";
            //     // if ($Command['ReadAttributeRequest']==1 )
            //     //{
            //     // getParamOSRAM( $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "01" );
            //     $this->readAttribute($priority, $dest, $Command['address'], "03", $Command['clusterId'], $Command['attributeId']);
            //     //}
            //     return;
            // }

            // Tcharp38 note: This is badly named. It is not a write attribute but
            // most probably a cluster 0502 cmd 00
            // OBSOLETE => Use 'cmd-0502' with 'cmd=00' instead
            if (isset($Command['writeAttributeRequestIAS_WD'])) {
                // Parameters: EP=#EP&mode=Flash&duration=#slider#

                    cmdLog('debug', "    command writeAttributeRequestIAS_WD", $this->debug['processCmd']);
                    // Msg Type = 0x0111

                    $priority = $Command['priority'];

                    $cmd = "0111";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <direction: uint8_t>
                    // <manufacturer specific: uint8_t>
                    // <manufacturer id: uint16_t>
                    // <Warning Mode: uint8_t>
                    // <Warning Duration: uint16_t>
                    // <Strobe duty cycle : uint8_t>
                    // <Strobe level : uint8_t>

                    $addrMode = "02";
                    $addr = $Command['address'];
                    $srcEp = "01";
                    $dstEp = "01";
                    $direction = "01";
                    $manufacturerSpecific = "00";
                    $manufacturerId = "0000";
                    $warningMode = "04";
                    if ($Command['mode'] == "Flash" )      $warningMode = "04";        // 14, 24, 34: semble faire le meme son meme si la doc indique: Burglar, Fire, Emergency / 04: que le flash
                    if ($Command['mode'] == "Sound" )      $warningMode = "10";
                    if ($Command['mode'] == "FlashSound" ) $warningMode = "14";
                    $warningDuration = "000A"; // en seconde
                    if ($Command['duration'] > 0 )         $warningDuration = sprintf("%04s", dechex($Command['duration']));
                    // $strobeDutyCycle = "01";
                    // $strobeLevel = "F0";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$direction.$manufacturerSpecific.$manufacturerId.$warningMode.$warningDuration; //.$strobeDutyCycle.$strobeLevel;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

            // Add Group APS
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed

            if (isset($Command['addGroupAPS'])  )
            {
                cmdLog('debug', "    command add group with APS", $this->debug['processCmd']);
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (05 -> 0x05)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                      -> 1
                // <cmdAddGroup>                                  -> 1
                // <group>                                        -> 2
                // <length>                                       -> 1

                // => 16 -> 0x10

                $addrMode = "02";
                $addr = $Command['address'];
                $srcEpBind = "01";
                $dstEpBind = "01";
                $profIdBind = "0104";
                $clustIdBind = "0004";
                $secMode = "02";
                $radius = "30";
                $dataLength = "06";

                $dummy = "01";  // I don't know why I need this but if I don't put it then I'm missing some data
                $dummy1 = "00";  // Dummy

                $cmdAddGroup = "00";
                $groupId = "AAAA";
                $length = "00";

                $length = "0011";

                // $data =  $targetExtendedAddress.$targetEndpoint.$clustId.$destinationAddressMode.$destinationAddress.$dstEp;
                // $data1 = $addrMode.$addr.$srcEpBind.$dstEpBind.$profIdBind.$clustIdBind.$secMode.$radius.$dataLength;
                $data1 = $addrMode.$addr.$srcEpBind.$dstEpBind.$clustIdBind.$profIdBind.$secMode.$radius.$dataLength;
                $data2 = $dummy.$dummy1.$cmdAddGroup.$groupId.$length;

                cmdLog('debug', "    Data1: ".$addrMode."-".$addr."-".$srcEpBind."-".$dstEpBind."-".$clustIdBind."-".$profIdBind."-".$secMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) , $this->debug['processCmd']);
                cmdLog('debug', "    Data2: ".$dummy.$dummy1.$cmdAddGroup.$groupId.$length." len: ".(strlen($data2)/2) , $this->debug['processCmd']);

                $data = $data1.$data2;
                // cmdLog('debug', "Data: ".$data." len: ".(strlen($data)/2));

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            if (isset($Command['removeGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']))
            {
                cmdLog('debug', "    Remove a group to a device", $this->debug['processCmd']);
                //echo "Remove a group to an IKEA bulb\n";

                // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
                // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
                // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
                // Add Group
                // Message Description
                // Msg Type = 0x0060 Command ID = 0x00
                $cmd = "0063";
                $length = "0007";
                // <address mode: uint8_t>
                //<target short address: uint16_t>
                //<source endpoint: uint8_t>
                //<destination endpoint: uint8_t>
                //<group address: uint16_t>
                $addrMode = "02";
                $address = $Command['address'];
                $srcEp = "01";
                $dstEp = $Command['DestinationEndPoint'] ;
                $groupAddress = $Command['groupAddress'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupAddress ;
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // Replace Equipement
            if (isset($Command['replaceEquipement']) && isset($Command['old']) && isset($Command['new']))
            {
                cmdLog('debug', "    Replace an Equipment", $this->debug['processCmd']);

                $old = $Command['old'];
                $new = $Command['new'];

                cmdLog('debug',"    Update eqLogic table for new object", $this->debug['processCmd']);
                $sql =          "UPDATE `eqLogic` SET ";
                $sql = $sql.  "name = 'Abeille-".$new."-New' , logicalId = '".$new."', configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql.  "WHERE  eqType_name = 'Abeille' AND logicalId = '".$old."' AND configuration LIKE '%".$old."%'";
                cmdLog('debug',"sql: ".$sql, $this->debug['processCmd']);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

                cmdLog('debug',"    Update cmd table for new object", $this->debug['processCmd']);
                $sql =          "UPDATE `cmd` SET ";
                $sql = $sql.  "configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql.  "WHERE  eqType = 'Abeille' AND configuration LIKE '%".$old."%' ";
                cmdLog('debug',"    sql: ".$sql, $this->debug['processCmd']);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
                return;
            }

            //
            if (isset($Command['UpGroup']) && isset($Command['address']) && isset($Command['step']))
            {
                cmdLog('debug','    UpGroup for: '.$Command['address'], $this->debug['processCmd']);
                // <address mode: uint8_t>          -> 2
                // <target short address: uint16_t> -> 4
                // <source endpoint: uint8_t>       -> 2
                // <destination endpoint: uint8_t>  -> 2
                // <onoff: uint8_t>                 -> 2
                // <step mode: uint8_t >            -> 2
                // <step size: uint8_t>             -> 2
                // <Transition Time: uint16_t>      -> 4
                // -> 20/2 =10 => 0A

                $cmd = "0082";
                $length = "000A";
                if (isset ( $Command['addressMode'] )) { $addrMode = $Command['addressMode']; } else { $addrMode = "02"; }

                $address = $Command['address'];
                $srcEp = "01";
                if (isset ( $Command['destinationEndpoint'] )) { $dstEp = $Command['destinationEndpoint'];} else { $dstEp = "01"; };
                $onoff = "00";
                $stepMode = "00"; // 00 : Up, 01 : Down
                $stepSize = $Command['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                return;
            }

            if (isset($Command['DownGroup']) && isset($Command['address']) && isset($Command['step']))
            {
                cmdLog('debug','    DownGroup for: '.$Command['address'], $this->debug['processCmd']);
                // <address mode: uint8_t>          -> 2
                // <target short address: uint16_t> -> 4
                // <source endpoint: uint8_t>       -> 2
                // <destination endpoint: uint8_t>  -> 2
                // <onoff: uint8_t>                 -> 2
                // <step mode: uint8_t >            -> 2
                // <step size: uint8_t>             -> 2
                // <Transition Time: uint16_t>      -> 4
                // -> 20/2 =10 => 0A

                $cmd = "0082";
                $length = "000A";
                if (isset ( $Command['addressMode'] )) { $addrMode = $Command['addressMode']; } else { $addrMode = "02"; }

                $address = $Command['address'];
                $srcEp = "01";
                if (isset ( $Command['destinationEndpoint'] )) { $dstEp = $Command['destinationEndpoint'];} else { $dstEp = "01"; };
                $onoff = "00";
                $stepMode = "01"; // 00 : Up, 01 : Down
                $stepSize = $Command['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                return;
            }

            // ON / OFF with no effects
            if (isset($Command['onoff']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']))
            {
                cmdLog('debug','    OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'], $this->debug['processCmd']);
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <command ID: uint8_t>
                    // Command Id
                    // 0 - Off
                    // 1 - On
                    // 2 - Toggle

                $cmd                    = "0092";

                $addrMode               = $Command['addressMode']; // 01: Group, 02: device
                $address                = $Command['address'];
                $srcEp                  = "01";
                $dstEp                  = $Command['destinationEndpoint'];
                $action                 = $Command['action'];

                $data = $addrMode.$address.$srcEp.$dstEp.$action;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $priority               = $Command['priority'];

                $this->addCmdToQueue2($priority, $dest, $cmd, $data, $address, $addrMode);

                if ($addrMode == "02" ) {
                    $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000" );
                }
                return;
            }

            // ON / OFF with no effects RAW with no APS ACK
            // Not used as some eq have a strange behavior if the APS ACK is not set (e.g. Xiaomi Plug / should probably test again / bug from the eq ?)
            if (isset($Command['onoffraw']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']))
            {
                cmdLog('debug', "    command setParam4", $this->debug['processCmd']);

                $dest       = $Command['dest'];
                $priority   = $Command['priority'];

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)

                // <data: auint8_t> APS Part <= data

                $addrMode        = $Command['addressMode'];
                $addr = $Command['address'];
                $srcEp     = "01";
                if ($Command['destinationEndpoint']>1 ) { $dstEp = $Command['destinationEndpoint']; } else { $dstEp = "01"; } // $dstEp; // "01";

                $profId          = "0104";
                $clustId          = "0006"; // $Command['clusterId'];

                $secMode       = "02"; // ???
                $radius             = "30";
                // $dataLength <- calculated later

                $frameControl               = "11"; // Ici dans cette commande c est ZCL qu'on control
                $transqactionSequenceNumber = "1A"; // to be reviewed
                $commandWriteAttribute      = $Command['action'];

                $data2 = $frameControl.$transqactionSequenceNumber.$commandWriteAttribute;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                $data = $data1.$data2;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);

                if ($addrMode == "02" ) {
                    $this->publishMosquitto( $abQueues["xToCmd"]['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/ReadAttributeRequestMulti&time=".(time()+2), "EP=".$dstEp."&clusterId=0006&attributeId=0000" );
                }
                return;
            }

            // On / Off Timed Send
            if (isset($Command['OnOffTimed']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) && isset($Command['onTime']) && isset($Command['offWaitTime']))
            {
                cmdLog('debug','    OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'].' - '.$Command['onTime'].' - '.$Command['ffWaitTime'], $this->debug['processCmd']);
                // <address mode: uint8_t>    Status
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <onoff: uint8_t>
                // <on time: uint16_t>
                // <off time: uint16_t>
                    // On / Off:
                    // 0 = Off
                    // 1 = On
                    // Time: Seconds

                $cmd = "0093";
                // $length = "0006";
                $addrMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $srcEp         = "01";
                $dstEp    = $Command['destinationEndpoint'];
                $action                 = $Command['action'];
                $onTime                 = $Command['onTime'];
                $offWaitTime            = $Command['offWaitTime'];

                $data = $addrMode.$address.$srcEp.$dstEp.$action.$onTime.$offWaitTime;
                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);

                if ($addrMode == "02" ) {
                    $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000" );

                    $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+$Command['onTime']), "ep=".$dstEp."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+$Command['onTime']), "ep=".$dstEp."&clustId=0008&attrId=0000" );
                }
                return;
            }

            // Take RGB (0-255) convert to X, Y and send the color
            if (isset($Command['setColourRGB']))
            {
                // The reverse transformation
                // https://en.wikipedia.org/wiki/SRGB

                $R=$Command['R'];
                $G=$Command['G'];
                $B=$Command['B'];

                $a = 0.055;

                // are in the range 0 to 1. (A range of 0 to 255 can simply be divided by 255.0).
                $Rsrgb = $R / 255;
                $Gsrgb = $G / 255;
                $Bsrgb = $B / 255;

                if ($Rsrgb <= 0.04045 ) { $Rlin = $Rsrgb/12.92; } else { $Rlin = pow( ($Rsrgb+$a)/(1+$a), 2.4); }
                if ($Gsrgb <= 0.04045 ) { $Glin = $Gsrgb/12.92; } else { $Glin = pow( ($Gsrgb+$a)/(1+$a), 2.4); }
                if ($Bsrgb <= 0.04045 ) { $Blin = $Bsrgb/12.92; } else { $Blin = pow( ($Bsrgb+$a)/(1+$a), 2.4); }

                $X = 0.4124 * $Rlin + 0.3576 * $Glin + 0.1805 *$Blin;
                $Y = 0.2126 * $Rlin + 0.7152 * $Glin + 0.0722 *$Blin;
                $Z = 0.0193 * $Rlin + 0.1192 * $Glin + 0.9505 *$Blin;

                if (($X + $Y + $Z)!=0 ) {
                    $x = $X / ( $X + $Y + $Z );
                    $y = $Y / ( $X + $Y + $Z );
                }
                else {
                    echo "Can t do the convertion.";
                }

                $x = $x*255*255;
                $y = $y*255*255;

                // Meme commande que la commande du dessus
                $cmd = "00B7";
                // 8+16+8+8+16+16+16 = 88 /8 = 11 => 0x0B
                $length = "000B";

                $addrMode = "02";
                $address = $Command['address'];
                $srcEp = "01";
                $dstEp = $Command['destinationEndPoint'];
                $colourX = str_pad( dechex($x), 4, "0", STR_PAD_LEFT);
                $colourY = str_pad( dechex($y), 4, "0", STR_PAD_LEFT);
                $duration = "0001";

                cmdLog( 'debug', "    colourX: ".$colourX." colourY: ".$colourY, $this->debug['processCmd'] );

                $data = $addrMode.$address.$srcEp.$dstEp.$colourX.$colourY.$duration ;

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // if (isset($Command['getManufacturerName']) && isset($Command['address']))
            // {
            //     if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
            //     $this->readAttribute(PRIO_NORM, $dest, $Command['address'], $Command['destinationEndPoint'], "0000", "0004");
            //     return;
            // }

            // if (isset($Command['getName']) && isset($Command['address']))
            // {
            //     if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
            //     $this->readAttribute(PRIO_NORM, $dest, $Command['address'], $Command['destinationEndPoint'], "0000", "0005");
            //     return;
            // }

            // if (isset($Command['getLocation']) && isset($Command['address']))
            // {
            //     if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
            //     $this->readAttribute(PRIO_NORM, $dest, $Command['address'], $Command['destinationEndPoint'], "0000", "0010");
            //     return;
            // }

            // Tcharp38: Seems no longer used
            // if (isset($Command['setLocation']) && isset($Command['address']))
            // {
            //     if ($Command['location'] == "" ) { $Command['location'] = "Not Def"; }
            //     if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }

            //     $this->setParam2( $dest, $Command['address'], "0000", "0010",$Command['destinationEndPoint'],$Command['location'], "42" );
            //     return;
            // }

            if (isset($Command['MgtLeave']) && isset($Command['address']) && isset($Command['IEEE']))
            {
                // Zigbee specification
                // 2.4.3.3.5   Mgmt_Leave_req
                // (ClusterID=0x0034)
                //2.4.3.3.5.1     When Generated The Mgmt_Leave_req is generated from a Local Device requesting that a Remote Device leave the network
                // or to request that another device leave the network. The Mgmt_Leave_req is generated by a management application which directs
                // the request to a Remote Device where the NLME-LEAVE.request is to be executed using the parameter supplied by Mgmt_Leave_req.

                $cmd = "0047";

                // <target short address: uint16_t>
                // <extended address: uint64_t>
                // <Rejoin: uint8_t>
                // <Remove Children: uint8_t>
                //  Rejoin,
                //      0 = Do not rejoin
                //      1 = Rejoin
                //  Remove Children
                //      0 = Leave, removing children
                //      1 = Leave, do not remove children

                $address        = $Command['address'];
                $IEEE           = $Command['IEEE'];
                if (isset($Command['Rejoin'])) $Rejoin = $Command['Rejoin']; else $Rejoin = "00";
                if (isset($Command['RemoveChildren']))  $RemoveChildren = $Command['RemoveChildren']; else $RemoveChildren = "01";

                $data = $address.$IEEE.$Rejoin.$RemoveChildren;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // if (isset($Command['Remove']) && isset($Command['address']) && isset($Command['IEEE']))
            // https://github.com/KiwiHC16/Abeille/issues/332
            if (isset($Command['Remove']) && isset($Command['ParentAddressIEEE']) && isset($Command['ChildAddressIEEE']))
            {
                // <target short address: uint64_t>
                // <extended address: uint64_t>
                $cmd = "0026";

                // Doc is probably not up to date, need to provide IEEE twice
                // Tested and works in case of a NE in direct to coordinator
                // To be tested if message is routed.
                // <target short address: uint16_t>
                // <extended address: uint64_t>

                // Zigbee specification
                // Chapter 4 Security Services Specification
                // 4.4 APS Layer Security
                // 4.4.5 Remove Device Services
                // The APSME provides services that allow a device (for example, a Trust Center) to inform another device (for example, a router) that one of its children should be removed from the network.
                // The ZDO of a device (for example, a Trust Center) shall issue this primitive when it wants to request that a parent device (for example, a router) remove one of its children from the network. For example, a Trust Center can use this primitive to remove a child device that fails to authenticate properly.
                // APSME-REMOVE-DEVICE.request { ParentAddress IEEE, ChildAddress IEEE}

                // $address        = $Command['address'];
                $address        = $Command['ParentAddressIEEE'];
                $IEEE           = $Command['ChildAddressIEEE'];

                $data = $address.$IEEE ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            /* Tcharp38: New way of checking commands.
               'name' field contains command name. */
            if (isset($Command['name'])) {
                $cmdName = $Command['name'];
                // cmdLog('debug', '    '.$cmdName.' cmd', $this->debug['processCmd']);

                /* Note: commands are described in the following order:
                   - Zigate specific commands
                   - Zigbee standard commands
                   - Zigbee cluster library (ZCL) global commands
                   - Zigbee cluster library (ZCL) cluster specific commands
                 */

                /*
                 * Zigate specific commands
                 */

                // Zigate specific command
                if ($cmdName == 'setZgMode') {
                    $mode = $Command['mode'];
                    if ($mode == "raw") {
                        $modeVal = "01";
                    } else if ($mode == "hybrid") {
                        $modeVal = "02";
                    } else // Normal
                        $modeVal = "00";
                    cmdLog('debug',"    Setting mode ".$mode."/".$modeVal);
                    // $this->addCmdToQueue($priority, $dest, "0002", "0001", $modeVal);
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0002", $modeVal);
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'setZgPermitMode') {
                    $mode = $Command['mode'];
                    if ($mode == "start") {
                        $cmd = "0049";
                        $length = "0004";
                        $data = "FFFCFE00";
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
                        // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data); //1E = 30 secondes
                        $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);

                        // $CommandAdditionelle['permitJoin'] = "permitJoin";
                        // $CommandAdditionelle['permitJoin'] = "Status";
                        // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "Cmd".$dest."/0000/permitJoin", "Status" );
                    } elseif ($mode == "stop") {
                        $cmd = "0049";
                        $length = "0004";
                        $data = "FFFC0000";
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
                        // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data); //1E = 30 secondes
                        $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);

                        // $CommandAdditionelle['permitJoin'] = "permitJoin";
                        // $CommandAdditionelle['permitJoin'] = "Status";
                        // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "Cmd".$dest."/0000/permitJoin", "Status");
                    }
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'permitJoin') {
                    //  && $Command['permitJoin']=="Status") {
                    // cmdLog("debug", "    permitJoin-Status");
                    // “Permit join” status on the target
                    // Msg Type =  0x0014

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0014");
                    return;
                }

                // Zigate specific command: Requests FW version
                else if (($cmdName == 'getZgVersion') || ($cmdName == 'getVersion')) {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0010");
                    return;
                }

                // Zigate specific command: Reset zigate
                else if (($cmdName == 'resetZg') || ($cmdName == 'resetZigate')) {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0011");
                    return;
                }

                // Zigate specific command: Set Time server (v3.0f)
                else if (($cmdName == 'setZgTimeServer') || ($cmdName == 'setTimeServer')) {
                    if (!isset($Command['time'])) {
                        $zgRef = mktime(0, 0, 0, 1, 1, 2000); // 2000-01-01 00:00:00
                        $Command['time'] = time() - $zgRef;
                    }
                    cmdLog('debug', "    setZgTimeServer, time=".$Command['time'], $this->debug['processCmd']);

                    /* Cmd 0016 reminder
                    payload = <timestamp UTC: uint32_t> from 2000-01-01 00:00:00
                    WARNING: PHP time() is based on 1st of jan 1970 and NOT 2000 !! */
                    $cmd = "0016";
                    $data = sprintf("%08s", dechex($Command['time']));
                    // $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                    return;
                }

                // Zigate specific command
                else if (($cmdName == 'getZgTimeServer') || ($cmdName == 'getTimeServer')) {
                    // $data = "";
                    // $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0017");
                    return;
                }

                // https://github.com/fairecasoimeme/ZiGate/issues/145
                // Added cmd 0807 Get Tx Power #175
                // PHY_PIB_TX_POWER_DEF (default - 0x80)
                // PHY_PIB_TX_POWER_MIN (minimum - 0)
                // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
                else if ($cmdName == 'getZgTxPower') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0807");
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'startZgNetwork') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0024");
                    return;
                }

                // Zigate specific command: Start Network Scan
                // Unsupported cmd on Zigate v1 at least (FW 3.21)
                // else if ($cmdName == 'startZgNetworkScan') {
                //     $this->addCmdToQueue2(PRIO_NORM, $dest, "0025");
                //     return;
                // }

                // Zigate specific command: Erase PDM
                else if (($cmdName == 'eraseZgPDM') || ($cmdName == 'ErasePersistentData')) {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0012");
                    return;
                }

                // Zigate specific command: Set LED, ON/1 or OFF/0
                else if ($cmdName == 'setZgLed') {
                    if ($Command['value'] == 1)
                        $value = "01";
                    else
                        $value = "00";

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0018", $value);
                    return;
                }

                // Zigate specific command: Set channel mask
                // Mandatory params: 'mask' (hexa string)
                else if ($cmdName == 'setZgChannelMask') {
                    $required = ['mask'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $mask = $Command['mask'];
                    if (!ctype_xdigit($mask)) {
                        cmdLog('error', '    Invalid channel mask. Not hexa ! ('.$mask.')');
                        return;
                    }
                    $mask = str_pad($mask, 8, '0', STR_PAD_LEFT); // Add any missing zeros

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0021", $mask);
                    return;
                }

                /*
                 * Zigbee standard commands
                 */

                // Zigbee command: Mgmt_Lqi_req
                else if ($cmdName == 'getNeighborTable') {
                    /* Expecting 2 parameters: 'addr' & 'startIndex' */
                    $required = ['addr', 'startIndex'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "004E";

                    // <target short address: uint16_t>
                    // <Start Index: uint8_t>

                    $addr = $Command['addr'];
                    $startIndex = $Command['startIndex'];

                    $data = $addr.$startIndex ;

                    // Note: 004E seems to be ACKed command.
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, "02");
                    return;
                }

                // Zigbee command: Mgmt_Rtg_req
                // Request routing table. To Zigbee router or coordinator.
                // Mandatory params: 'addr'
                // Optional params: 'startIdx' (hex byte, default='00')
                else if ($cmdName == 'getRoutingTable') {
                    /* Checking that mandatory infos are there */
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // Msg Type = 0x0530
                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>

                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>

                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>
                    // <data: auint8_t>

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "00";
                    $dstEp      = "00";
                    $profId     = "0000";
                    $clustId    = "0032";
                    $secMode    = "28";
                    $radius     = "30";

                    $sqn        = $this->genSqn();
                    $startIdx   = isset($Command['startIdx']) ? $Command['startIdx'] : "00";

                    $data2 = $sqn.$startIdx;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                } // End $cmdName == 'getRoutingTable'

                // Zigbee command: Get binding table (Mgmt_Bind_req)
                // Mandatory params: 'address'
                // Optional params: none
                else if ($cmdName == 'getBindingTable') {
                    $required = ['address'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // Msg Type = 0x0530
                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>

                    $addrMode   = "02"; // Short addr mode
                    $addr       = $Command['address'];
                    $srcEp      = "00";
                    $dstEp      = "00";
                    $profId     = "0000";
                    $clustId    = "0033"; // Mgmt_Bind_req
                    $secMode    = "28";
                    $radius     = "30";

                    $sqn        = $this->genSqn();
                    $startIndex = "00";

                    $data2 = $sqn.$startIndex;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                // Zigbee command: Bind to device or bind to group.
                // Bind, thru command 0030 => generates 'Bind_req' / cluster 0021
                // Mandatory params: addr, clustId, attrType, attrId
                // Optional params: destEp required if destAddr = device ieee addr
                else if ($cmdName == 'bind0030') {

                    $required = ['addr', 'ep', 'clustId', 'destAddr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    // If 'destAddr' == IEEE then need 'destEp' too.
                    if ((strlen($Command['destAddr']) == 16) && !isset($Command['destEp'])) {
                        cmdLog('error', "    bind0030: Missing 'destEp'");
                        return;
                    }

                    $cmd = "0030";

                    // <target extended address: uint64_t>
                    // <target endpoint: uint8_t>
                    // <cluster ID: uint16_t>
                    // <destination address mode: uint8_t>
                    // <destination address:uint16_t or uint64_t>
                    // <destination endpoint (value ignored for group address): uint8_t>

                    // Source
                    $addr = $Command['addr'];
                    if (strlen($addr) != 16) {
                        cmdLog('error', "    bind0030: Invalid addr length (".$addr.")");
                        return;
                    }
                    $ep = $Command['ep'];
                    $clustId = $Command['clustId'];

                    // Dest
                    // $dstAddr: 01=16bit group addr, destEp ignored
                    // $dstAddr: 03=64bit ext addr, destEp required
                    $dstAddr = $Command['destAddr'];
                    $dstEp = isset($Command['destEp']) ? $Command['destEp'] : "00"; // destEp ignored if group address
                    if (strlen($dstAddr) == 4) {
                        $dstAddrMode = "01";
                        $dstTxt = "group ".$dstAddr;
                    } else if (strlen($dstAddr) == 16) {
                        $dstAddrMode = "03";
                        $dstTxt = "device ".$dstAddr."/EP-".$dstEp;
                    } else {
                        cmdLog('error', "    bind0030: Invalid dest addr length (".$dstAddr.")");
                        return;
                    }
                    cmdLog('debug', '    bind0030: '.$addr.'/EP-'.$ep.'/Clust-'.$clustId.' to '.$dstTxt);
                    $data = $addr.$ep.$clustId.$dstAddrMode.$dstAddr.$dstEp;

                    // Note: Bind is sent with ACK request
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, "02");
                    return;
                } // End $cmdName == 'bind0030'

                // Zigbee command: Unbind
                // Unbind, thru command 0031 => generates 'Unbind_req' / cluster 0022
                // Mandatory params: addr, clustId, attrType, attrId
                // Optional params: destEp required if destAddr = device ieee addr
                else if ($cmdName == 'unbind0031') {

                    $required = ['addr', 'ep', 'clustId', 'destAddr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    // If 'destAddr' == IEEE then need 'destEp' too.
                    if ((strlen($Command['destAddr']) == 16) && !isset($Command['destEp'])) {
                        cmdLog('error', "    unbind0031: Missing 'destEp'");
                        return;
                    }

                    $cmd = "0031";

                    // <target extended address: uint64_t>
                    // <target endpoint: uint8_t>
                    // <cluster ID: uint16_t>
                    // <destination address mode: uint8_t>
                    // <destination address:uint16_t or uint64_t>
                    // <destination endpoint (value ignored for group address): uint8_t>

                    // Source
                    $addr = $Command['addr'];
                    if (strlen($addr) != 16) {
                        cmdLog('error', "    unbind0031: Invalid addr length (".$addr.")");
                        return;
                    }
                    $ep = $Command['ep'];
                    $clustId = $Command['clustId'];

                    // Dest
                    // $dstAddr: 01=16bit group addr, destEp ignored
                    // $dstAddr: 03=64bit ext addr, destEp required
                    $dstAddr = $Command['destAddr'];
                    if (strlen($dstAddr) == 4) {
                        $dstAddrMode = "01";
                        $dstEp = '';
                        $dstTxt = 'group '.$dstAddr;
                    } else if (strlen($dstAddr) == 16) {
                        $dstAddrMode = "03";
                        $dstEp = $Command['destEp'];
                        $dstTxt = 'device '.$dstAddr.'/EP-'.$dstEp;
                    } else {
                        cmdLog('error', "    unbind0031: Invalid dest addr length (".$dstAddr.")");
                        return;
                    }
                    cmdLog('debug', '    unbind0031: '.$addr.'/EP-'.$ep.'/Clust-'.$clustId.' to '.$dstTxt);
                    $data = $addr.$ep.$clustId.$dstAddrMode.$dstAddr.$dstEp;

                    // Note: Unbind is sent with ACK request
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, "02");
                    return;
                } // End $cmdName == 'unbind0031'

                // Zigbee command: IEEE address request (IEEE_addr_req)
                else if ($cmdName == 'getIeeeAddress') { // IEEE_addr_req + IEEE_addr_rsp
                    /* Checking that mandatory infos are there */
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0041";

                    // <target short address: uint16_t>
                    // <short address: uint16_t>
                    // <request type: uint8_t>: Request Type: 0 = Single 1 = Extended
                    // <start index: uint8_t>

                    $priority       = (isset($Command['priority']) ? $Command['priority'] : PRIO_NORM);
                    // See https://github.com/fairecasoimeme/ZiGate/issues/386#
                    // Both address must be the same.
                    $address        = $Command['addr'];
                    $shortAddress   = $Command['addr'];
                    $requestType    = "00"; // 00=single device response
                    $startIndex     = "00";

                    $data = $address.$shortAddress.$requestType.$startIndex ;

                    $this->addCmdToQueue2($priority, $dest, $cmd, $data, $address);
                    return;
                }

                // Zigbee command: Active endpoints request (Active_EP_req)
                // Mandatory params: 'addr'
                else if ($cmdName == 'getActiveEndpoints') {
                    /* Checking that mandatory infos are there */
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0045";

                    // <target short address: uint16_t>

                    $priority = (isset($Command['priority']) ? $Command['priority'] : PRIO_NORM);
                    $addr = $Command['addr'];
                    $addrMode = "02"; // Short addr with ACK

                    $data = $addr;

                    $this->addCmdToQueue2($priority, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // Zigbee command: Simple descriptor request (Simple_Desc_req)
                else if ($cmdName == 'getSimpleDescriptor') {
                    $required = ['addr', 'ep'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <target short address: uint16_t>
                    // <endpoint: uint8_t>

                    $cmd = "0043";
                    $addr = sprintf("%04X", hexdec($Command['addr']));
                    $ep = sprintf("%02X", hexdec($Command['ep']));

                    $data = $addr.$ep;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                // Zigbee command: Node descriptor request (Node_Desc_req)
                else if ($cmdName == 'getNodeDescriptor') {
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <target short address: uint16_t>

                    $cmd = "0042";
                    $addr = sprintf("%04X", hexdec($Command['addr']));

                    $data = $addr;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                // Zigbee command: Leave request
                // Mandatory params: 'IEEE'
                // Optional params: 'Rejoin' & 'RemoveChildren'
                else if ($cmdName == 'LeaveRequest') {
                    $required = ['IEEE'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "004C";

                    // <extended address: uint64_t>
                    // <Rejoin: uint8_t>
                    // <Remove Children: uint8_t>
                    //  Rejoin,
                    //      0 = Do not rejoin
                    //      1 = Rejoin
                    //  Remove Children
                    //      0 = Leave, removing children
                    //      1 = Leave, do not remove children

                    $ieee = $Command['IEEE'];
                    if (isset($Command['Rejoin']))
                        $rejoin = $Command['Rejoin'];
                    else
                        $rejoin = "00";
                    if (isset($Command['RemoveChildren']))
                        $RemoveChildren = $Command['RemoveChildren'];
                    else
                        $RemoveChildren = "01";

                    $data = $ieee.$rejoin.$RemoveChildren;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                    return;
                }

                // Zigbee command: Management Network Update request (Mgmt_NWK_Update_req, cluster 0038)
                // Mandatory params: 'addr'
                // Optional params: 'scanChan' (hex string, default='07FFF800'), 'scanDuration' (hex string, default='01')
                else if ($cmdName == 'mgmtNetworkUpdateReq') {
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // Msg Type =  0x004A

                    // <target short address: uint16_t>
                    // <channel mask: uint32_t>
                    // <scan duration: uint8_t>
                    // <scan count: uint8_t> -> Si valeur a 5 alors l ampoule envoit 5 messages avec les mesures
                    // <network update ID: uint8_t>
                    // <network manager short address: uint16_t>
                    //
                    // Channel Mask: Mask of channels to scan
                    // Scan Duration: 0 – 0xFF Multiple of superframe duration.
                    // Scan count: Scan repeats 0 – 5
                    // Network Update ID: 0 – 0xFF Transaction ID for scan

                    $cmd                = "004A";

                    $addr               = $Command['addr'];
                    $scanChan           = isset($Command['scanChan']) ? hexdec($Command['scanChan']) : 0x7FFF800;
                    $scanChan           = sprintf("%08X", $scanChan);
                    $scanDuration       = isset($Command['scanDuration']) ? $Command['scanDuration'] : "01";
                    $scanDuration       = strtoupper($scanDuration);
                    if ($scanDuration == "FE") // Channel change request
                        $scanCount      = "00";
                    else
                        $scanCount      = "01";
                    $networkUpdateId    = "01";
                    $networkManagerAddr = "0000"; // Useful only if scanDuration==FF

                    cmdLog('debug', "    Using addr=".$addr.", scanChan=".$scanChan.", scanDuration=".$scanDuration.', scanCount='.$scanCount);
                    $data = $addr.$scanChan.$scanDuration.$scanCount.$networkUpdateId.$networkManagerAddr;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                /*
                 * ZCL general commands
                 */

                // ZCL global: readAttribute command
                // Mandatory params: 'addr', 'ep', 'clustId', 'attrId'
                // Optional params: 'manufId'
                else if ($cmdName == 'readAttribute') {
                    $required = ['addr', 'ep', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    if (isset($Command['manufId']))
                        $manufId = $Command['manufId'];
                    else
                        $manufId = '';
                    $this->readAttribute(PRIO_NORM, $dest, $Command['addr'], $Command['ep'], $Command['clustId'], $Command['attrId'], $manufId);
                    return;
                }

                // Generic 'write attribute request' function based on 0110 Zigate msg.
                // ZCL global: writeAttribute command
                // Mandatory: ep, clustId, attrId & attrVal
                // Optional : attrType, dir (default=00), manufId
                else if ($cmdName == 'writeAttribute') {
                    /* Checking that mandatory infos are there */
                    $required = ['ep', 'clustId', 'attrId', 'attrVal'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (!isset($Command['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['clustId'], $Command['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            cmdLog('error', "writeAttribute: 'attrType' manquant");
                            return;
                        }
                        $attrType = sprintf("%02X", $attr['dataType']);
                    } else
                        $attrType = $Command['attrType'];


                    // If attrVal is coming from slider, it must be converted to proper data type
                    // $attrVal = $Command['attrVal'];
                    // if (substr($attrVal, 0, 7) == "#slider") {
                    //     $attrVal = $this->sliderToHex($attrVal, $Command['attrType']);
                    //     if ($attrVal === false)
                    //         return;
                    // }

                    /* Cmd 0110 reminder:
                        <address mode: uint8_t>	Data Indication
                        <target short address: uint16_t>
                        <source endpoint: uint8_t>
                        <destination endpoint: uint8_t>
                        <Cluster id: uint16_t>
                        <direction: uint8_t>
                        <manufacturer specific: uint8_t>
                        <manufacturer id: uint16_t>
                        <number of attributes: uint8_t>
                        <attributes list: data list of uint16_t each>
                            Attribute ID : uint16_t
                            Attribute Type : uint8_t
                            Attribute Data : byte[32]
                    */

                    $priority   = isset($Command['priority']) ? $Command['priority'] : PRIO_NORM;

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $clustId    = $Command['clustId'];
                    $dir        = (isset($Command['dir']) ? $Command['dir'] : "00"); // 00 = to server side, 01 = to client site
                    if (isset($Command['manufId']) && ($Command['manufId'] != "0000")) {
                        $manufSpecific  = "01";
                        $manufCode      = $Command['manufId'];
                    } else {
                        $manufSpecific  = "00";
                        $manufCode      = "0000";
                    }
                    $nbOfAttributes = "01";
                    $attrVal    = $this->formatAttribute($Command['attrVal'], $attrType);
                    if ($attrVal === false)
                        return;
                    $attrList   = $Command['attrId'].$attrType.$attrVal;

                    cmdLog('debug', "    Using dir=".$dir.", manufId=".$manufCode.", attrType=".$attrType.", attrVal=".$attrVal, $this->debug['processCmd']);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$clustId.$dir.$manufSpecific.$manufCode.$nbOfAttributes.$attrList;

                    $this->addCmdToQueue2($priority, $dest, "0110", $data, $addr, $addrMode);
                    return;
                }

                // ZCL global: Generic 'write attribute request' function based on 0530 zigate msg
                // Tcharp38 note: not clear yet why it can be required but 0110 is still a nightmare. Doc is weak and too
                //   far from a clean spec like ZCL one. So easier to follow ZCL spec.
                // Mandatory: ep, clustId, attrId & attrVal
                // Optional : priority, attrType, dir (default=00), sqn
                else if ($cmdName == 'writeAttribute0530') {
                    /* Checking that mandatory infos are there */
                    $required = ['ep', 'clustId', 'attrId', 'attrVal'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (!isset($Command['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['clustId'], $Command['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            cmdLog('debug', "    command writeAttribute0530 ERROR: Missing 'attrType'");
                            return;
                        }
                        $Command['attrType'] = sprintf("%02X", $attr['dataType']);
                    }

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <cluster ID: uint16_t>
                    // <profile ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // ZCL header
                        // Frame control
                        // Manuf code
                        // SQN
                        // Command ID
                    // Write attribute record 1
                        // Attribute id
                        // Attribute data type
                        // Attribute data
                    // ...
                    // Write attribute record X

                    $priority   = isset($Command['priority']) ? $Command['priority'] : PRIO_NORM;

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $clustId    = $Command['clustId'];
                    $profId     = "0104";
                    $secMode    = "02"; // ???
                    $radius     = "30";
                    // $dataLength <- calculated later

                    // ZCL header
                    $dir        = hexdec(isset($Command['dir']) ? $Command['dir'] : "00");
                    $fcf        = sprintf("%02X", ($dir << 3) | 00);
                    $sqn        = isset($Command['sqn']) ? $Command['sqn'] : $this->genSqn();
                    $cmd        = "02"; // Write Attributes
                    $zclHeader  = $fcf.$sqn.$cmd;

                    // Write attribute record
                    $attrId     = AbeilleTools::reverseHex($Command['attrId']);
                    $dataType   = $Command['attrType'];
                    $attrVal    = AbeilleTools::reverseHex($Command['attrVal']);

                    cmdLog('debug', "    Using dir=".$dir.", attrType=".$Command['attrType'].", attrVal=".$attrVal, $this->debug['processCmd']);
                    $data2 = $zclHeader.$attrId.$dataType.$attrVal;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$profId.$clustId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2($priority, $dest, "0530", $data, $addr, $addrMode);
                    return;
                }

                // ZCL global: discoverAttributes command
                // Mandatory params: addr, ep, clustId
                // Optional params : dir, startAttrId (default=0000), maxAttrId (default=FF)
                else if ($cmdName == 'discoverAttributes') {
                    $required = ['addr', 'ep', 'clustId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <Cluster id: uint16_t>
                    // <Attribute id : uint16_t>
                    // <direction: uint8_t>
                    //      Direction:
                    //      0 – from server to client
                    //      1 – from client to server
                    // <manufacturer specific: uint8_t>
                    //      Manufacturer specific :
                    //      1 – Yes
                    //      0 – No
                    // <manufacturer id: uint16_t>
                    // <Max number of identifiers: uint8_t>

                    $cmd            = "0140";
                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $dstEp          = sprintf("%02X", hexdec($Command['ep']));
                    $clustId        = sprintf("%04X", hexdec($Command['clustId']));
                    $attrId         = isset($Command['startAttrId']) ? $Command['startAttrId']: "0000";
                    if (!isset($Command['dir']))
                        $dir        = '00'; // Default: to server side
                    else
                        $dir        = $Command['dir']; // '00' = server cluster atttrib, '01' = client cluster attrib
                    $manufSpec      = "00"; //  1 – Yes	 0 – No
                    $manufId        = "0000";
                    $maxAttrId      = isset($Command['maxAttrId']) ? $Command['maxAttrId'] : "FF";
                    cmdLog('debug','    Using dir='.$dir.', startAttrId='.$attrId.", maxAttr=".$maxAttrId, $this->debug['processCmd']);

                    $data = $addrMode.$addr.$srcEp.$dstEp.$clustId.$attrId.$dir.$manufSpec.$manufId.$maxAttrId;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // ZCL global: Discover commands received
                // Mandatory params: 'addr', 'ep', 'clustId'
                // Optional params: 'startId' (default=00), 'max' (default=FF)
                else if ($cmdName == 'discoverCommandsReceived') {
                    $required = ['addr', 'ep', 'clustId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = $Command['clustId'];
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "10"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = "11"; // Discover Commands Received

                    $startId    = isset($Command['startId']) ? $Command['startId'] : "00";
                    $max        = isset($Command['max']) ? $Command['max'] : "FF";

                    $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End 'discoverCommandsReceived'

                // ZCL global: Discover commands generated
                // Mandatory params: 'addr', 'ep', 'clustId'
                // Optional params: 'startId' (default=00), 'max' (default=FF)
                else if ($cmdName == 'discoverCommandsGenerated') {
                    $required = ['addr', 'ep', 'clustId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = $Command['clustId'];
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "10"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = "13"; // Discover Commands Generated

                    $startId    = isset($Command['startId']) ? $Command['startId'] : "00";
                    $max        = isset($Command['max']) ? $Command['max'] : "FF";

                    $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End 'discoverCommandsGenerated'

                // ZCL global: Read Attributes Response
                // Mandatory params: 'addr', 'ep', 'clustId', 'attrId', 'status', 'attrType', 'attrVal'
                else if ($cmdName == 'sendReadAttributesResponse') {
                    $required = ['addr', 'ep', 'clustId', 'attrId', 'status', 'attrType', 'attrVal'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    $fcf            = "10"; // Frame Control Field
                    $sqn            = $this->genSqn();
                    $cmdId          = "01"; // Read Attributes Response
                    $data2 = $fcf.$sqn.$cmdId;

                    // if (!isset($Command['attrId']) || !isset($Command['status']) || !isset($Command['attrType'])) {}
                    //     cmdLog('debug', "    ERROR: Missing '".$param."'");
                    //     return;
                    // }
                    $attrId = AbeilleTools::reverseHex($Command['attrId']);
                    $status = $Command['status'];
                    $attrType = $Command['attrType'];
                    $attrVal = AbeilleTools::reverseHex($Command['attrVal']);
                    $data2 = $data2.$attrId.$status.$attrType.$attrVal;

                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // ZCL global: Discover Attributes Extended
                // Mandatory params: 'addr', 'ep', 'clustId'
                // Optional params: startId (default=0000), max (default=FF)
                else if ($cmdName == 'discoverAttributesExt') {
                    /* Mandatory infos: addr, ep, clustId */
                    $required = ['addr', 'ep', 'clustId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $cmd            = "0530";
                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    $fcf            = "10"; // Frame Control Field
                    $sqn            = $this->genSqn();
                    $cmdId          = "15"; // Discover Attributes Extended

                    $startId        = isset($Command['startId']) ? $Command['startId'] : "0000";
                    $max            = isset($Command['max']) ? $Command['max'] : "FF";

                    $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // ZCL global: Configure reporting command
                // Mandatory parameters: addr, clustId, attrId
                // Optional parameters: attrType, minInterval, maxInterval, changeVal, manufId
                // Tcharp38: Why don't we use 0120 zigate command instead of 0530 ??
                else if ($cmdName == 'configureReporting') {
                    /* Mandatory infos: addr, clustId, attrId. 'attrType' can be auto-detected */
                    $required = ['addr', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (!isset($Command['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['clustId'], $Command['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            cmdLog('debug', "    command configureReporting ERROR: Missing 'attrType'");
                            return;
                        }
                        $Command['attrType'] = sprintf("%02X", $attr['dataType']);
                    }

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    if (isset($Command['manufId'])) {
                        $manufId = $Command['manufId'];
                        $fcf = "14"; // Frame Control Field
                    } else {
                        $manufId = '';
                        $fcf = "10"; // Frame Control Field
                    }
                    $sqn            = $this->genSqn();
                    $cmdId          = "06";

                    /* Attribute Reporting Configuration Record */
                    $dir                    = "00";
                    $attrId                 = AbeilleTools::reverseHex($Command['attrId']);
                    $attrType               = $Command['attrType'];
                    $minInterval            = isset($Command['minInterval']) ? $Command['minInterval'] : "0000";
                    $maxInterval            = isset($Command['maxInterval']) ? $Command['maxInterval'] : "0000";
                    $changeVal              = ''; // Reportable change.
                    if (isset($Command['changeVal'])) {
                        $changeVal = $Command['changeVal'];
                    } else {
                        // switch ($attrType) {
                        // case "21": // Uint16
                        //     $changeVal = "0001";
                        //     break;
                        // case "10": // Boolean
                        // case "20": // Uint8
                        //     $changeVal = "01";
                        //     break;

                        // // Tcharp38: TO BE COMPLETED ! changeVal size depends on attribute type
                        // default:
                        //     cmdLog('debug', "    ERROR: Unsupported attrType ".$attrType, $this->debug['processCmd']);
                        //     $changeVal = "01";
                        // }
                    }
                    // $change = AbeilleTools::reverseHex($changeVal); // Reportable change.
                    // $timeout = "0000";

                    // TODO: changeVal should be set to some default, in the proper format

                    cmdLog('debug', "    configureReporting: manufId=".$manufId.", attrType='".$attrType."', min='".$minInterval."', max='".$maxInterval."', changeVal='".$changeVal."'", $this->debug['processCmd']);
                    $manufId = AbeilleTools::reverseHex($manufId);
                    $minInterval = AbeilleTools::reverseHex($minInterval);
                    $maxInterval = AbeilleTools::reverseHex($maxInterval);
                    $changeVal = AbeilleTools::reverseHex($changeVal);
                    $data2 = $fcf.$manufId.$sqn.$cmdId.$dir.$attrId.$attrType.$minInterval.$maxInterval.$changeVal;
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End 'configureReporting'

                // ZCL global: Read Reporting Configration command
                // Mandatory params: 'addr', 'ep', 'clustId', 'attrId'
                else if ($cmdName == 'readReportingConfig') {
                    $required = ['addr', 'ep', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <Cluster id: uint16_t>
                    // <direction: uint8_t>
                    // <number of attributes: uint8_t>
                    // <manufacturer specific: uint8_t>
                    // <manufacturer id: uint16_t>
                    // Attribute direction : uint8_t
                    // Attribute id : uint16_t

                    $cmd        = "0122";
                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $clustId    = $Command['clustId'];
                    $dir        = "00"; // 00=attribute is reported, 01=attribute is received
                    $nbOfAttr   = "01";
                    $manufSpecific = "00";
                    $manufId    = "0000";
                    $attrDir    = "00";
                    $attrId     = $Command['attrId'];

                    $data =  $addrMode.$addr.$srcEp.$dstEp.$clustId.$dir.$nbOfAttr.$manufSpecific.$manufId.$attrDir.$attrId;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // Edn 'readReportingConfig'

                /*
                 * ZCL cluster specific commands
                 */

                // ZCL cluster 0000 specific: (received) commands
                else if ($cmdName == 'cmd-0000') {
                    $required = ['addr', 'ep']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0000';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = "00"; // Reset to Factory Defaults

                    $data2 = $fcf.$sqn.$cmdId;
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // Cluster 0000, $cmdName == 'cmd-0000'

                // ZCL cluster 0004 specific: addGroup, sent to server
                // Mandatory params: 'addr', 'ep', & 'group'
                else if ($cmdName == 'addGroup') {
                    $required = ['addr', 'ep', 'group']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    //<address mode: uint8_t>
                    //<target short address: uint16_t>
                    //<source endpoint: uint8_t>
                    //<destination endpoint: uint8_t>
                    //<group address: uint16_t>
                    $cmd        = "0060";
                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $group      = $Command['group'];

                    cmdLog('debug', '  addGroup: ep='.$dstEp.', group='.$group);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$group;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0004, $cmdName == 'addGroup'

                // ZCL cluster 0004 specific: getGroupMembership, sent to server
                // Mandatory params: 'addr', 'ep'
                else if ($cmdName == 'getGroupMembership') {
                    $required = ['addr', 'ep']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0062";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <group count: uint8_t>
                    // <group list:data>

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $ep         = $Command['ep'];
                    $groupCount = "00";
                    $groupList  = "";

                    /* Correcting EP size if required (ex "1" => "01") */
                    if (strlen($ep) != 2) {
                        $EP = hexdec($ep);
                        $ep = sprintf("%02X", (hexdec($EP)));
                    }

                    $data = $addrMode.$addr.$srcEp.$ep.$groupCount.$groupList;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0004, $cmdName == 'getGroupMembership'

                // ZCL cluster 0004 specific: removeAllGroups, sent to server
                // Mandatory params: 'addr', 'ep'
                else if ($cmdName == 'removeAllGroups') {
                    $required = ['addr', 'ep']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0064";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <group count: uint8_t>
                    // <group list:data>

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $ep         = $Command['ep'];
                    $groupCount = "00";
                    $groupList  = "";

                    /* Correcting EP size if required (ex "1" => "01") */
                    if (strlen($ep) != 2) {
                        $EP = hexdec($ep);
                        $ep = sprintf("%02X", hexdec($EP));
                    }

                    $data = $addrMode.$addr.$srcEp.$ep;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0004, $cmdName == 'removeAllGroups'

                // ZCL cluster 0008/Level control specific: (received) commands
                // Mandatory params: addr, EP, Level (in dec, %), duration (dec)
                // Optional params: duration (default=0001)
                else if ($cmdName == 'setLevel') {
                    $required = ['addr', 'Level']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (($Command['Level'] < 0) || ($Command['Level'] > 100)) {
                        cmdLog('debug', '  ERROR: Level outside 0->100 range');
                        return;
                    }
                    $cmd = "0081"; // Move to level with/without on/off

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <onoff : uint8_t>
                    // <Level: uint8_t >
                    // <Transition Time: uint16_t>

                    if (isset($Command['addressMode'])) $addrMode = $Command['addressMode']; else $addrMode = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['EP'];
                    $onoff      = "01";
                    $l = intval($Command['Level'] * 255 / 100);
                    $level      = sprintf("%02X", $l);
                    $duration   = isset($Command['duration']) ? sprintf("%04X", $Command['duration']) : "0001";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$onoff.$level.$duration;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $cmd, $data, $addr, $addrMode);

                    // if ($addrMode == "02") {
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000");
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000");

                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2+$Command['duration']), "ep=".$dstEp."&clustId=0006&attrId=0000");
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3+$Command['duration']), "ep=".$dstEp."&clustId=0008&attrId=0000");
                    // }
                    return;
                }

                // ZCL cluster 0008/Level control specific: (received) commands
                // setLevelRaw = same as setLevel but 'Level' is raw data, not %
                // Mandatory params: addr, EP, Level (in dec, %), duration (dec)
                // Optional params: duration (default=0001)
                // OBSOLETE !!! Use 'cmd-0008' instead with 'cmd=00' or '04'
                else if ($cmdName == 'setLevelRaw') {
                    $required = ['addr', 'EP', 'Level']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0081"; // Move to level with/without on/off

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <onoff : uint8_t>
                    // <Level: uint8_t >
                    // <Transition Time: uint16_t>

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['EP'];
                    $onoff      = "01";
                    $level      = sprintf("%02X", $Command['Level']);
                    $duration   = isset($Command['duration']) ? sprintf("%04X", $Command['duration']) : "0001";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$onoff.$level.$duration;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $cmd, $data, $addr, $addrMode);

                    if ($addrMode == "02") {
                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000");
                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000");

                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2+$Command['duration']), "ep=".$dstEp."&clustId=0006&attrId=0000");
                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3+$Command['duration']), "ep=".$dstEp."&clustId=0008&attrId=0000");
                    }
                    return;
                }

                // ZCL cluster 0008/Level control specific.
                // Mandatory params: addr, ep & cmd ('00', '04, '07')
                //   + 'level' for cmd '00' or '04'
                // Optional params: 'duration' for cmd '00' or '04'
                else if ($cmdName == 'cmd-0008') {
                    $required1 = ['addr', 'ep', 'cmd']; // Mandatory infos
                    $required2 = ['addr', 'ep', 'cmd', 'level']; // Mandatory infos for 00 & 04
                    if (isset($Command['cmd']) && ($Command['cmd'] == "07"))
                        $required = $required1;
                    else
                        $required = $required2;
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmdId = $Command['cmd'];

                    // TO BE COMPLETED
                    if (($cmdId == '00') || ($cmdId == '04')) { // Move to Level without (00) or with On/Off (04)
                        $cmd        = "0081";
                        $addrMode   = "02"; // Assuming short addr
                        $addr       = $Command['addr'];
                        $srcEp      = "01";
                        $dstEp      = $Command['ep'];
                        if ($cmdId == '00')
                            $onOff = "00";
                        else
                            $onOff = "01";
                        $level      = sprintf("%02X", intval($Command['level']));
                        $duration   = isset($Command['duration']) ? sprintf("%04X", $Command['duration']) : "0001";
                        cmdLog('debug', '    Using onOff='.$onOff.', level='.$level.', duration='.$duration);

                        $data       = $addrMode.$addr.$srcEp.$dstEp.$onOff.$level.$duration;

                        $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    } else if ($cmdId == '07') { // Stop with OnOff
                        $cmd        = "0084"; // Stop with OnOff = Cluster 0008, cmd 07
                        $addrMode   = "02"; // Assuming short addr
                        $addr       = $Command['addr'];
                        $srcEp      = "01";
                        $dstEp      = $Command['ep'];

                        $data       = $addrMode.$addr.$srcEp.$dstEp;

                        $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    } else {
                        cmdLog('error', "  Unsupported cluster 0008 command ".$cmdId);
                    }

                    return;
                } // End $cmdName == 'cmd-0008'

                // ZCL cluster 0019 specific: Inform Zigate that there is a valid OTA image
                else if ($cmdName == 'otaLoadImage') {
                    $required = ['manufCode', 'imgType']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $manufCode = $Command['manufCode'];
                    $imgType = $Command['imgType'];
                    if (!isset($GLOBALS['ota_fw']) || !isset($GLOBALS['ota_fw'][$manufCode]) || !isset($GLOBALS['ota_fw'][$manufCode][$imgType])) {
                        cmdLog('debug', "    ERROR: No such FW", $this->debug['processCmd']);
                        return;
                    }
                    $fw = $GLOBALS['ota_fw'][$manufCode][$imgType];
                    $addr = "0000"; // Target is zigate

                    // addMode.addr.fwHeader
                    $fwHeader = otaAArrayToHString($fw['header']);
                    $data = "02"."0000".$fwHeader;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0500", $data, $addr);
                    return;
                }

                // ZCL cluster 0019 specific: Inform device that there is a valid OTA image
                else if ($cmdName == 'otaImageNotify') {
                    $required = ['addr', 'ep', 'manufCode', 'imgType']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $manufCode = $Command['manufCode'];
                    $imgType = $Command['imgType'];
                    if (!isset($GLOBALS['ota_fw']) || !isset($GLOBALS['ota_fw'][$manufCode]) || !isset($GLOBALS['ota_fw'][$manufCode][$imgType])) {
                        return;
                    }
                    $fw = $GLOBALS['ota_fw'][$manufCode][$imgType];

                    $addrMode = "02";
                    $addr = $Command['addr'];
                    $srcEp = "01"; // Zigate is source
                    $dstEp = $Command['ep'];
                    $status = "00";
                    $imgVersion = $fw['fileVersion'];
                    $queryJitter = "64"; // x64 = 100

                    $data = $addrMode.$addr.$srcEp.$dstEp.$status.$imgVersion.$imgType.$manufCode.$queryJitter;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0505", $data, $addr);
                    return;
                }

                // ZCL cluster 0019 specific: Image block response (called on 8501 request)
                else if ($cmdName == 'otaImageBlockResponse') {
                    $required = ['addr', 'ep', 'manufCode', 'imgType', 'imgOffset', 'maxData']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    $manufCode = $Command['manufCode'];
                    $imgType = $Command['imgType'];
                    $imgOffset = $Command['imgOffset'];
                    $maxData = $Command['maxData'];

                    $fw = $GLOBALS['ota_fw'][$manufCode][$imgType];
                    $imgVers = $fw['fileVersion'];
                    $imgSize = $fw['fileSize'];
                    if (!isset($fw['fileHandle']))
                        $fw['fileHandle'] = fopen(__DIR__.'/../../'.otaDir.'/'.$fw['fileName'], 'rb');
                    $fh = $fw['fileHandle'];
                    // TODO: If max > remaining ?
                    $dataSize = $maxData;
                    // if (hexdec($dataSize) > 48)
                    //     $dataSize = "30"; // Required ?
                    $realOffset = $fw['startIdx'] + hexdec($imgOffset);
                    cmdLog('debug', "    Reading data from real offset ".$realOffset, $this->debug['processCmd']);
                    fseek($fh, $realOffset, SEEK_SET);
                    $data = fread($fh, hexdec($dataSize));
                    $data = strtoupper(bin2hex($data));

                    // Doc from Doudz/zigate
                    // data = struct.pack('!BHBBBBLLHHB{}B'.format(data_size), request['address_mode'], self.__addr(request['addr']),
                    //     source_endpoint, request['endpoint'], request['sequence'], ota_status,
                    //     request['file_offset'], self._ota['image']['header']['image_version'],
                    //     self._ota['image']['header']['image_type'],
                    //     self._ota['image']['header']['manufacturer_code'],
                    //     data_size, *ota_data_to_send)
                    $addrMode = "02";
                    $addr = $Command['addr'];
                    $srcEp = "01";
                    $dstEp = $Command['ep'];
                    $sqn = $Command['sqn'] ? $Command['sqn'] : $this->genSqn();
                    $status = "00";
                    // $imgOffset
                    // $imgVers
                    // $imgType
                    // $manufCode
                    // $dataSize
                    // $data

                    $data2 = $addrMode.$addr.$srcEp.$dstEp.$sqn.$status;
                    $data2 .= $imgOffset.$imgVers.$imgType.$manufCode.$dataSize.$data;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0502", $data2, $addr);

                    if ($imgOffset == "00000000") {
                        $eqLogic = Abeille::byLogicalId($dest.'/'.$addr, 'Abeille');
                        $eqPath = $eqLogic->getHumanName();
                        message::add("Abeille", $eqPath.": Mise-à-jour du firmware démarrée", "");
                    }
                    return;
                }

                // ZCL cluster 0019 specific: Inform device to apply new image
                else if ($cmdName == 'otaUpgradeEndResponse') {
                    // WORK ONGOING: really required ??
                    cmdLog('debug', "    ERROR: otaUpgradeEndResponse NOT IMPLEMENTED", $this->debug['processCmd']);
                    return;
                }

                // ZCL cluster 0019 specific: (received) commands
                else if ($cmdName == 'cmd-0019') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0019';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "19"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = $Command['cmd'];

                    $data2 = $fcf.$sqn.$cmdId;

                    // Image notify handled by otaImageNotify (zigate 0500)
                    // if ($cmdId == "00") { // Image Notify
                    //     $required = ['manufCode', 'imgType']; // Mandatory infos
                    //     if (!$this->checkRequiredParams($required, $Command))
                    //         return;
                    //     $plType = "02"; // queryJitter + manufCode + imgType
                    //     $queryJitter = "32"; // 0x01 – 0x64
                    //     $manufCode = AbeilleTools::reverseHex($Command['manufCode']);
                    //     $imageType = AbeilleTools::reverseHex($Command['imgType']);
                    //     $data2 .= $plType.$queryJitter.$manufCode.$imageType;
                    // } else

                    // Query next image response handled by Zigate server
                    // if ($cmdId == "02") { // Query Next Image Response
                    //     $required = ['status', 'manufCode', 'imgType', 'imgVersion', 'imgSize']; // Mandatory infos
                    //     if (!$this->checkRequiredParams($required, $Command))
                    //         return;
                    //     $status = $Command['status'];
                    //     if ($status != '00')
                    //         $data2 = $fcf.$sqn.$cmdId.$status;
                    //     else {
                    //         $manufCode = AbeilleTools::reverseHex($Command['manufCode']);
                    //         $imageType = AbeilleTools::reverseHex($Command['imgType']);
                    //         $fileVersion = AbeilleTools::reverseHex($Command['imgVersion']);
                    //         $imageSize = AbeilleTools::reverseHex($Command['imgSize']);
                    //         $data2 .= $status.$manufCode.$imageType.$fileVersion.$imageSize;
                    //     }
                    // } else

                    // Image block response handled by 'otaImageBlockResponse' zigate msg "0502"
                    // if ($cmdId == "05") { // Image Block Response
                    //     $required = ['manufCode', 'imgType', 'imgOffset', 'maxData']; // Mandatory infos
                    //     if (!$this->checkRequiredParams($required, $Command))
                    //         return;
                    //     $manufCode = $Command['manufCode'];
                    //     $imgType = $Command['imgType'];
                    //     $imgOffset = $Command['imgOffset'];
                    //     $maxData = $Command['maxData'];

                    //     $fw = $GLOBALS['ota_fw'][$manufCode][$imgType];
                    //     $imgVers = $fw['fileVersion'];
                    //     $imgSize = $fw['fileSize'];
                    //     if (!isset($fw['fileHandle']))
                    //         $fw['fileHandle'] = fopen(__DIR__.'/../../'.otaDir.'/'.$fw['fileName'], 'rb');
                    //     $fh = $fw['fileHandle'];
                    //     // TODO: If max > remaining ?
                    //     $dataSize = $maxData;
                    //     // if (hexdec($dataSize) > 48)
                    //     //     $dataSize = "30"; // Required ?
                    //     $realOffset = $fw['startIdx'] + hexdec($imgOffset);
                    //     cmdLog('debug', "    Reading data from real offset ".$realOffset, $this->debug['processCmd']);
                    //     fseek($fh, $realOffset, SEEK_SET);
                    //     $data = fread($fh, hexdec($dataSize));
                    //     $data = strtoupper(bin2hex($data));

                    //     $manufCode = AbeilleTools::reverseHex($manufCode);
                    //     $imgType = AbeilleTools::reverseHex($imgType);
                    //     $fileVersion = AbeilleTools::reverseHex($imgVers);
                    //     $imgOffset = AbeilleTools::reverseHex($imgOffset);
                    //     $data = AbeilleTools::reverseHex($data);
                    //     $data2 .= "00".$manufCode.$imgType.$fileVersion.$imgOffset.$dataSize.$data;

                    //     if ($imgOffset == "00000000") {
                    //         $eqLogic = Abeille::byLogicalId($dest.'/'.$addr, 'Abeille');
                    //         $eqPath = $eqLogic->getHumanName();
                    //         message::add("Abeille", $eqPath.": Mise-à-jour du firmware démarrée", "");
                    //     }
                    // } else

                    {
                        cmdLog('debug', "    ERROR: Unsupported cmdId ".$cmdId, $this->debug['processCmd']);
                        return;
                    }

                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                // ZCL cluster 0020 (Poll control) specific client commands
                else if ($cmdName == 'cmd-0020') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0020';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "19"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = $Command['cmd'];

                    $data2 = $fcf.$sqn.$cmdId;

                    // Cmds reminder:
                    // 0x00 Check-in Response M
                    // 0x01 Fast Poll Stop M
                    // 0x02 Set Long Poll Interval O
                    // 0x03 Set Short Poll Interval O
                    if ($cmdId == "00") { // Check-in Response
                        $data2 .= '00'.'0000';
                    } else {
                        cmdLog('debug', "    ERROR: Unsupported cmdId ".$cmdId, $this->debug['processCmd']);
                        return;
                    }

                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                // // ZCL cluster 0102 specific
                // // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
                // else if ($cmdName == 'WindowsCovering') { // OBSOLETE !! Use cmd-0102 instead
                //     $required = ['address', 'clusterCommand']; // Mandatory infos
                //     if (!$this->checkRequiredParams($required, $Command))
                //         return;

                //     // 0x00FA    Windows covering (v3.0f only)
                //     // <address mode: uint8_t>
                //     // <target short address: uint16_t>
                //     // <source endpoint: uint8_t>
                //     // <destination endpoint: uint8_t>
                //     // <cluster command : uint8_t>
                //     // 0 = Up/Open
                //     // 1 = Down/Close
                //     // 2 = Stop
                //     // 4 = Go To Lift Value (extra cmd : Value in cm)
                //     // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                //     // 7 = Go To Tilt Value (extra cmd : Value in cm)
                //     // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)
                //     // <extra command : uint8_t or uint16_t >

                //     $cmd = "00FA";

                //     $addrMode       = "02"; // 01 pour groupe, 02 pour NE
                //     $address        = $Command['address'];
                //     $srcEp          = "01";
                //     $detEP          = "01";
                //     $clusterCommand = $Command['clusterCommand'];

                //     $data = $addrMode.$address.$srcEp.$detEP.$clusterCommand;

                //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                //     return;
                // } // End $cmdName == 'WindowsCovering'

                // ZCL cluster 0102/Window covering specific cmds
                // Mandatory params: addr, ep, cmd
                //      + value (décimal) for cmd '04'/'05'/'06'/'07'
                // Optional params: none
                else if ($cmdName == 'cmd-0102') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    $cmdId = $Command['cmd'];
                    if ($cmdId == '04' || $cmdId == '05' || $cmdId == '06' || $cmdId == '07')
                        if (!isset($Command['value'])) {
                            cmdLog('error', "cmd-0102: Champ 'value' non renseigné");
                            return;
                        }

                    // cmdId reminder:
                    // 0 = Up/Open
                    // 1 = Down/Close
                    // 2 = Stop
                    // 4 = Go To Lift Value (extra cmd : Value in cm)
                    // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                    // 7 = Go To Tilt Value (extra cmd : Value in cm)
                    // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)

                    $cmd        = "00FA";

                    $addrMode   = "02"; // 01 pour groupe, 02 pour NE
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $extra      = '';
                    $value      = (isset($Command['value']) ? (int)$Command['value'] : 0);
                    if ($cmdId == "04" || $cmdId == "06")
                        $extra = sprintf("%04X", $value); // uint16
                    else if ($cmdId == "05" || $cmdId == "07")
                        $extra = sprintf("%02X", $value); // uint8

                    cmdLog('debug', '    Using cmdId='.$cmdId.', extra='.$extra);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$cmdId.$extra;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                } // End $cmdName == 'cmd-0102'

                // ZCL cluster 0201 specific: (received) commands
                // PRELIM: Work ongoing !!! Not tested yet.
                // Mandatory params: addr, ep, cmd
                //    and amount for cmd '00'
                // Optional params: mode (default=00/heat) for cmd '00'
                else if ($cmdName == 'cmd-0201') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0201';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    // Dir = 0 = to server
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = $Command['cmd'];

                    if ($cmdId == "00") { // Setpoint Raise/Lower
                        // 0x00 Heat (adjust Heat Setpoint)
                        // 0x01 Cool (adjust Cool Setpoint)
                        // 0x02 Both (adjust Heat Setpoint and Cool Setpoint)
                        $mode = isset($Command['mode']) ? $Command['mode'] : "00";
                        // amount = signed 8-bit int, value for increase or decrease in steps of 0.1°C.
                        $amount = $Command['amount'];
                        $data2 = $fcf.$sqn.$cmdId.$mode.$amount;
                    } else {
                        cmdLog('debug', "    ERROR: Unsupported cluster 0201 command ".$cmdId);
                        return;
                    }
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                } // End 'cmd-0201'

                // ZCL cluster 0300/color control: Move to Colour
                else if ($cmdName == 'setColour') {
                    $required = ['addr', 'X', 'Y']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <colour X: uint16_t>
                    // <colour Y: uint16_t>
                    // <transition time: uint16_t >

                    $cmd        = "00B7"; // Move to color
                    if (isset($Command['addressMode'])) $addrMode = $Command['addressMode']; else $addrMode = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    if (isset($Command['EP'])) $dstEp = $Command['EP']; else $dstEp = "01";
                    $colourX    = $Command['X'];
                    $colourY    = $Command['Y'];
                    if (isset($Command['duration']) && $Command['duration']>0)
                        $duration = sprintf("%04s", dechex($Command['duration']));
                    else
                        $duration = "0001";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$colourX.$colourY.$duration;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // ZCL cluster 0300/color control: Move to Colour Temperature
                // Mandatory params: addr, EP, slider (temp in K)
                // Note: slider=0 is specific value to force tempMireds=0000
                else if ($cmdName == 'setTemperature') {
                    $required = ['addr', 'slider']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <target short address: uint16_t>     4
                    // <source endpoint: uint8_t>           2
                    // <destination endpoint: uint8_t>      2
                    // <colour temperature: uint16_t>       4
                    // <transition time: uint16_t>          4

                    $cmd = "00C0"; // 00C0=Move to colour temperature

                    if (isset($Command['addressMode'])) $addrMode = $Command['addressMode']; else $addrMode = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    if (isset($Command['EP'])) $dstEp = $Command['EP']; else $dstEp = "01";
                    // Color temp K = 1,000,000 / ColorTempMireds,
                    // where ColorTempMireds is in the range 1 to 65279 mireds inclusive,
                    // giving a color temp range from 1,000,000 kelvins to 15.32 kelvins.
                    $tempK = $Command['slider'];
                    if ($tempK == 0)
                        $tempMireds = "0000";
                    else {
                        if ($tempK < 15.32)
                            $tempK = 15.32;
                        else if ($tempK > 1000000)
                            $tempK = 1000000;
                        $tempMireds  = sprintf("%04X", intval(1000000/$tempK));
                    }
                    $transition = "0001"; // Transition time

                    cmdLog('debug', '    TempK='.$tempK.' => Using tempMireds='.$tempMireds.', transition='.$transition);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$tempMireds.$transition;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $cmd, $data, $addr, $addrMode);

                    if ($addrMode == "02") {
                        $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0300&attrId=0007" );
                    }
                    return;
                }

                // ZCL cluster 0500/IAS Zone commands
                // Mantatory params: 'addr', 'ep', 'cmd' (00 or 01)
                // Optional params for cmd 00/zone enroll response:
                else if ($cmdName == 'cmd-0500') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $cmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0500'; // IAS Zone
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();

                    $cmdId      = $Command['cmd'];
                    if ($cmdId == "00") { // Zone enroll response
                        $data2 = $fcf.$sqn.$cmdId.'00'.$Command['zoneId'];
                    } else {
                        cmdLog('error', "    Unsupported cmdId ".$cmdId);
                        return;
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-0500'

                // ZCL cluster 0501/IAS ACE commands
                // Mantatory params: 'addr', 'ep', 'cmd' (05)
                // Optional params: ?
                else if ($cmdName == 'cmd-0501') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $cmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0501'; // IAS ACE
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();

                    $cmdId      = $Command['cmd'];
                    if ($cmdId == "05") { // Get Panel Status Response
                        $panelStatus = "00";
                        $secRemaining = "00";
                        $audibleNotif = "00";
                        $alarmStatus = "00";
                        $data2 = $fcf.$sqn.$cmdId.$panelStatus.$secRemaining.$audibleNotif.$alarmStatus;
                    } else {
                        cmdLog('error', "    Unsupported cmdId ".$cmdId);
                        return;
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-0501'

                // ZCL cluster 0502/IAS Warning Device commands
                // Mantatory params: 'addr', 'ep', 'cmd' (00 or 01)
                // Optional params for cmd 00:
                //      'mode' (string, 'stop'/'burglar'/'fire'/'emergency' (default)/'policepanic'/'firepanic'/'emergencypanic')
                //      'strobe' (string, 'on'/'off', default=depends on 'mode')
                //      'duration' (number, in sec, default=10sec)
                //      'sirenl' (siren level: 'low', 'medium', 'high' (default), 'veryhigh'). Note: '0' to '3' accepted too.
                else if ($cmdName == 'cmd-0502') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $cmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '0502'; // IAS WD
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();

                    // Use cases: #2242, #2550
                    $cmdId      = $Command['cmd'];
                    if ($cmdId == "00") { // Start warning
                        $mode = isset($Command['mode']) ? $Command['mode'] : 'emergency'; // Warning mode: Emergency
                        $mode = strtolower($mode);
                        switch ($mode) {
                        case 'stop':
                            $mode = 0; break;
                        case 'burglar':
                            $mode = 1; break;
                        case 'fire':
                            $mode = 2; break;
                        case 'policepanic':
                            $mode = 4; break;
                        case 'firepanic':
                            $mode = 5; break;
                        case 'emergencypanic':
                            $mode = 6; break;
                        case 'emergency':
                        default:
                            $mode = 3; break;
                        }
                        if (isset($Command['strobe']))
                            $strobe = ($Command['strobe'] == 'on') ? 1 : 0;
                        else {
                            if ($mode == 0) // Warning mode == stop
                                $strobe = 0; // Strobe OFF
                            else
                                $strobe = 1; // Strobe ON
                        }
                        $sirenl = isset($Command['sirenl']) ? $Command['sirenl'] : 'high'; // Siren level
                        $sirenl = strtolower($sirenl);
                        switch ($sirenl) {
                        case 'low':
                        case '0':
                            $sirenl = 0;
                            break;
                        case 'medium':
                        case '1':
                            $sirenl = 1;
                            break;
                        case 'veryhigh':
                        case '3':
                            $sirenl = 3;
                            break;
                        case 'high':
                        case '2':
                        default:
                            $sirenl = 2;
                            break;
                        }
                        $duration = isset($Command['duration']) ? $Command['duration'] : 10; // Default=10sec

                        cmdLog('debug', "    Start warning: Using mode=".$mode.", strobe=".$strobe.", sirenl=".$sirenl.", duration=".$duration);
                        $map8 = ($mode << 4) | ($strobe << 2) | $sirenl;
                        $map8 = sprintf("%02X", $map8); // Convert to hex string
                        // cmdLog('debug', "   map8=".$map8);
                        $duration = sprintf("%04X", $duration); // Convert to hex string
                        $duration = AbeilleTools::reverseHex($duration);
                        $data2 = $fcf.$sqn.$cmdId.$map8.$duration."05"."03";
                    } else {
                        cmdLog('error', "    Unsupported cluster 0502 command ".$cmdId);
                        return;
                    }
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-0502'

                // ZCL cluster 1000 specific: (received) commands
                else if ($cmdName == 'cmd-1000') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = '1000';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = $Command['cmd'];

                    if ($cmdId == "41") { // Get Group Identifiers Request Command
                        $startIdx = isset($Command['startIdx']) ? $Command['startIdx'] : "00";
                        $data2 = $fcf.$sqn.$cmdId.$startIdx;
                    } else if ($cmdId == "42") { // Get endpoint list request
                        $startIdx = isset($Command['startIdx']) ? $Command['startIdx'] : "00";
                        $data2 = $fcf.$sqn.$cmdId.$startIdx;
                    } else {
                        cmdLog('debug', "    ERROR: Unsupported cluster 1000 command ".$cmdId);
                        return;
                    }
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

                // ZCL cluster EF00 specific: e.g. Curtains motor Tuya https://github.com/KiwiHC16/Abeille/issues/2304
                else if ($cmdName == 'cmd-EF00') {
                    $required = ['addr', 'ep', 'cmd', 'param']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    // Data Point
                    define('OpenCloseStop',   "01");
                    define('GotoLevel',       "02");
                    define('ForwardBackward', "05");

                    // Data Type
                    define('DataType_VALUE',  "02");
                    define('DataType_ENUM',   "04");

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = 'EF00';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = "00";

                    $status         = "00";
                    $counterTuya    = "33"; // Set to 1, in traces increasse all the time. Not sure if mandatory to increase.
                    $cmdTuya        = $Command['cmd'];
                    if ($cmdTuya==GotoLevel) $type = DataType_VALUE; else $type = DataType_ENUM;
                    $function         = "00";
                    if ($type==DataType_VALUE) $len = "04"; else $len="01";
                    if ($type==DataType_VALUE) $param = sprintf("%08s", dechex($Command['param'])); else $param = $Command['param'];  // Up=00, Stop: 01, Down: 02

                    $data2 = $fcf.$sqn.$cmdId.$status.$counterTuya.$cmdTuya.$type.$function.$len.$param;
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // Tuya private cluster EF00
                // Mandatory params: 'addr', 'ep', 'cmd', and 'data'
                // Optional params: 'dpId'
                else if ($cmdName == 'cmd-tuyaEF00') {
                    $required = ['addr', 'ep', 'cmd', 'data']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmd        = "0530";

                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['ep'];
                    $profId     = "0104";
                    $clustId    = 'EF00';
                    $secMode    = "02";
                    $radius     = "1E";

                    // ZCL header
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = "00";

                    // Tuya fields
                    // Command sent to device and its format fully depends on device himself.
                    $dp = tuyaCmd2Dp($Command);
                    if ($dp === false) {
                        return;
                    }

                    $transId = tuyaGetTransId(); // Transaction ID
                    $dpId = $dp['id'];
                    $dpType = $dp['type'];
                    $dpData = $dp['data'];
                    cmdLog('debug', '    Using transId='.$transId.', dpId='.$dpId.', dpType='.$dpType.', dpData='.$dpData);
                    $len = sprintf("%02X", strlen($dpData) / 2);
                    if (($dpType == "02") && ($len > 4)) {
                        cmdLog('error', '    Wrong dpData size (max=4B for type 02)');
                        return;
                    }
                    $data2 = $fcf.$sqn.$cmdId."00".$transId.$dpId.$dpType."00".$len.$dpData;
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-tuyaEF00'

                // Legrand private cluster FC41.
                // Mandatory params: 'addr', 'Mode'
                // Optional params: 'priority', 'EP'
                else if ($cmdName == 'commandLegrand') {
                    $required = ['addr', 'Mode']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // Memo message 0530
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <cluster ID: uint16_t>
                    // <profile ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>
                    // <data: auint8_t>

                    // $priority = $Command['priority'];
                    $cmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['addr'];
                    $srcEp      = "01";
                    if ($Command['EP']>1 ) { $dstEp = $Command['EP']; } else { $dstEp = "01"; } // $dstEp; // "01";
                    $profId     = "0104";
                    $clustId    = "FC41";
                    $secMode    = "02"; // ???
                    $radius     = "30";

                    $fcf        = "15";   // APS Control Field, see doc for details
                    $manufId    = "2110"; // Reversed 1021
                    $sqn        = $this->genSqn();
                    $command    = "00";

                    // $data = "00"; // 00 = Off, 02 = Auto, 03 = On.
                    $data = $Command['Mode'];
                    $data2 = $fcf.$manufId.$sqn.$command.$data;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End 'commandLegrand'

                // else {
                //     cmdLog('debug', "    ERROR: Unexpected command '".$cmdName."'");
                //     return;
                // }
            }

            cmdLog('error', "    Commande inattendue: '".json_encode($Command)."'");
        }
    }
?>
