<?php
    include_once __DIR__.'/AbeilleDebug.class.php';
    include_once __DIR__.'/../php/AbeilleZigbeeConst.php'; // Attribute type

    class AbeilleCmdProcess extends AbeilleDebug {

        /* Check if required param are set in 'Command' and are not empty.
           Returns: true if ok, else false */
        function checkRequiredParams($required, $Command) {
            $paramError = false;
            foreach ($required as $param) {
                if (isset($Command['cmdParams'][$param])) {
                    if (gettype($Command['cmdParams'][$param]) != "string")
                        continue; // No other check on non string types
                    if ($Command['cmdParams'][$param] != '')
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

        /* Generic RAW cmd function for Zigate (using 0530 msg).
        Expecting header as follows
            $header = array(
                'net' => $net, // Mandatory
                'addr' => $addr, // Mandatory
                'ep' => $ep, // Mandatory
                'clustId' => 'yyyy', // Mandatory
                'clustSpecific' => true, // Optional: true=cluster specific frame, false=general frame (default)
                'manufCode' => 'xxxx', // Optional: hex string for manuf code if manuf specific
                'cmd' => '00' // Mandatory
            );
        */
        public static function sendRawMessage($header, $data) {
            $required = ['net', 'addr', 'ep', 'clustId', 'cmd'];
            foreach ($required as $param) {
                if (!isset($header[$param])) {
                    cmdLog('error', "    sendRawMessage(): Paramètre '".$param."' manquant.");
                    return false;
                }
            }

            $zgCmd      = "0530";
            $addrMode   = "02";
            $addr       = $header['addr'];
            $srcEp      = "01";
            $dstEp      = $header['ep'];
            $profId     = "0104";
            $clustId    = $header['clustId'];
            $secMode    = "02"; // ???
            $radius     = "30"; // ??

            $net            = $header['net'];
            $clustSpecific  = isset($header['clustSpecific']) ? $header['clustSpecific']: false; // Default = general frame
            $manufCode      = isset($header['manufCode']) ? $header['manufCode']: '';
            $cmd            = $header['cmd'];

            $hParams = array(
                'clustSpecific' => $clustSpecific,
                'manufCode' => $manufCode,
                'cmdId' => $cmd
            );
            $zclHeader = AbeilleCmdProcess::genZclHeader($hParams);

            $data2 = $zclHeader.$data;
            $dataLen2 = sprintf("%02X", strlen($data2) / 2);

            $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
            $data = $data1.$data2;

            AbeilleCmdQueue::addCmdToQueue2(PRIO_NORM, $net, $zgCmd, $data, $addr, $addrMode);
        }

        // Generate an APS SQN, auto-incremented
        // OBSOLETE: Use genZclHeader() instead.
        public static function genSqn() {
            // Tcharp38: SQN should be treated per zigate
            $sqn = sprintf("%02X", $GLOBALS['lastSqn']);
            if ($GLOBALS['lastSqn'] == 255)
                $GLOBALS['lastSqn'] = 1; // Skipping value 0 (=zigate cmd not transmitted ota)
            else
                $GLOBALS['lastSqn']++;
            return $sqn;
        }

        // Generate ZCL header
        // hParams = array(
        //     'clustSpecific' => true, // Optional: Frame type: false=general (default), true=cluster specific
        //     'manufCode' => '', // Optional: Note: if != '' it enabled 'manufSpecific' flag
        //     'toCli' => true, // Optional: Direction: false=default=client2server, true=server2client
        //     'disableDefaultRsp' => false, // Optional (default=true)
        //     'zclSqn' => 'xx', // Optional
        //     'cmdId' => 'xx' // Mandatory
        // )
        public static function genZclHeader($hParams) {
            // cmdLog('debug', '  genZclHeader(): hParams='.json_encode($hParams));

            $clustSpecific = isset($hParams['clustSpecific']) ? $hParams['clustSpecific'] : false;
            $manufCode = isset($hParams['manufCode']) ? $hParams['manufCode'] : '';
            $toCli = isset($hParams['toCli']) ? $hParams['toCli'] : false;
            $disableDefaultRsp = isset($hParams['disableDefaultRsp']) ? $hParams['disableDefaultRsp'] : true;
            $cmdId = isset($hParams['cmdId']) ? $hParams['cmdId'] : '';
            $zclSqn = isset($hParams['zclSqn']) ? $hParams['zclSqn'] : AbeilleCmdProcess::genSqn();

            $frameType = $clustSpecific ? 1 : 0;
            if ($frameType == 0)
                $zhTxt = "General";
            else
                $zhTxt = "Cluster-specific";
            if ($manufCode == '') {
                $manufSpecific = 0;
                $manufCode = '';
            } else {
                $manufSpecific = 1;
                $manufCode = AbeilleTools::reverseHex($manufCode);
                $zhTxt .= "/ManufCode=${manufCode}";
            }
            $toCli = $toCli ? 1 : 0;
            if ($toCli)
                $zhTxt .= "/Serv->Cli";
            else
                $zhTxt .= "/Cli->Serv";
            $disableDefaultRsp = $disableDefaultRsp ? 1 : 0;

            $fcf = ($disableDefaultRsp << 4) | ($toCli << 3) | ($manufSpecific << 2) | $frameType;
            // cmdLog('debug', '  genZclHeader(): fcf='.$fcf);
            $fcf = sprintf("%02X", $fcf);

            $zclHeader = $fcf.$manufCode.$zclSqn.$cmdId;
            cmdLog('debug', '  zclHeader: '.$zhTxt.', SQN='.$zclSqn.', CmdId='.$cmdId);
            return $zclHeader;
        }

        /**
         * Converts signed decimal to hex (Two's complement)
         *
         * @param $value int, signed
         *
         * @return string, upper case hex value, both bytes padded left with zeros
         */
        function signed2hex($value, $size) {
            $packed = pack('i', $value);
            $hex='';
            for ($i=0; $i < $size; $i++){
                $hex .= strtoupper( str_pad( dechex(ord($packed[$i])) , 2, '0', STR_PAD_LEFT) );
            }
            $tmp = str_split($hex, 2);
            $out = implode('', array_reverse($tmp));
            return $out;
        }

        // Called to convert '#sliderXX#' or '#selectXX#' to 'XX'
        // 'XX' is always a decimal value
        // function sliderToHex($sliderVal, $type) {
        //     $strDecVal = substr($sliderVal, 7, -1); // Extracting decimal value
        //     $signed = false;
        //     switch ($type) {
        //     case '20': // uint8
        //     case '30': // enum8
        //         $size = 1;
        //         break;
        //     case '21': // uint16
        //     case '31': // enum16
        //         $size = 2;
        //         break;
        //     case '22': // uint24
        //         $size = 3;
        //         break;
        //     case '23': // uint32
        //         $size = 3;
        //         break;
        //     case '28': // int8
        //         $size = 1;
        //         $signed = true;
        //         break;
        //     case '29': // int16
        //         $size = 2;
        //         $signed = true;
        //         break;
        //     case '2A': // int24
        //         $size = 3;
        //         $signed = true;
        //         break;
        //     case '2B': // int32
        //         $size = 4;
        //         $signed = true;
        //         break;
        //     default:
        //         cmdLog('error', "sliderToHex(): strDecVal=".$strDecVal.", UNSUPPORTED type=".$type);
        //         return false;
        //     }
        //     if ($signed)
        //         $strHexVal = $this->signed2hex($strDecVal, $size);
        //     else {
        //         $val = intval($strDecVal);
        //         $size = $size * 2; // 1 Byte = 2 hex char
        //         $format = "%0".$size."X";
        //         // cmdLog('debug', "  val=".strval($val).", size=".strval($size)." => format=".$format);
        //         $strHexVal = sprintf($format, $val);
        //     }

        //     cmdLog('debug', "  sliderToHex(): strDecVal=".$strDecVal." => ".$strHexVal);
        //     return $strHexVal;
        // }

        /* Format attribute value.
           Return hex string formatted value according to its type.
           WARNING: Returned hex string must still be reversed if >= 2B */
        function formatAttribute($valIn, $type) {
            // cmdLog('debug', "formatAttribute(".$valIn.", ".$type.")");
            // Note: Slider/select conversion to be done in execute() function
            if (substr($valIn, 0, 7) == "#slider") {
                // $valIn = $this->sliderToHex($valIn, $type);
                $valIn = substr($valIn, 7, -1); // Extracting decimal value
            } else if (substr($valIn, 0, 7) == "#select") {
                // $valIn = $this->sliderToHex($valIn, $type);
                $valIn = substr($valIn, 7, -1); // Extracting decimal value
            } else if (substr($valIn, 0, 2) == "0x") {
                $valIn = hexdec($valIn);
            }
            if ($valIn === false)
                return false;

            $valOut = '';
            switch ($type) {
            case 'bool':
            case '10':
            case '18': // map8
            case '20': // uint8
            case '30': // enum8
                $valOut = sprintf("%02X", $valIn);
                break;
            case 'uint16':
            case '21':
            case '31': // enum16
                $valOut = sprintf("%04X", $valIn);
                break;
            case 'uint24':
            case '22':
                $valOut = sprintf("%06X", $valIn);
                break;
            case 'uint32':
            case '23':
                $valOut = sprintf("%08X", $valIn);
                break;
            case 'uint48':
            case '25':
                $valOut = sprintf("%012X", $valIn);
                break;
            case '28': // int8
                $valOut = $this->signed2Hex($valIn, 1);
                break;
            case '29': // int16
            case 'int16':
                $valOut = $this->signed2Hex($valIn, 2);
                break;
            case '2A': // int24
                $valOut = $this->signed2Hex($valIn, 3);
                break;
            case '2B': // int32
                $valOut = $this->signed2Hex($valIn, 4);
                break;
            case '39': // Single precision
                $valOut = strrev(unpack('h*', pack('f', $valIn))[1]);
                break;
            case '42': // string
                $len = sprintf("%02X", strlen($valIn));
                $valOut = $len.bin2hex($valIn);
                // cmdLog('debug', "len=".$len.", valOut=".$valOut);
                break;
            default:
                cmdLog('error', "  formatAttribute(${valIn}, type=${type}) => Type non supporté");
                $valOut = '12'; // Fake value
            }

            cmdLog('debug', "  formatAttribute(${valIn}, type=${type}) => valOut=${valOut}");
            return $valOut;
        }

        // Ne semble pas fonctionner et me fait planté la ZiGate, idem ques etParam()
        // Tcharp38: See https://github.com/KiwiHC16/Abeille/issues/2143#
        function ReportParamXiaomi($dest, $Command) {
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
            $address                = $Command['cmdParams']['address'];
            $srcEp         = "01";
            $dstEp    = "01";
            $clusterId              = $Command['cmdParams']['clusterId'];
            $direction              = "00";
            $manufacturerSpecific   = "01";
            $proprio                = $Command['cmdParams']['Proprio'];
            $numberOfAttributes     = "01";
            $attributeId            = $Command['cmdParams']['attributeId'];
            $attributeType          = $Command['cmdParams']['attributeType'];
            $value                  = $Command['cmdParams']['value'];

            $data = $addrMode.$address.$srcEp.$dstEp.$clusterId.$direction.$manufacturerSpecific.$proprio.$numberOfAttributes.$attributeId.$attributeType.$value;

            // $length = sprintf("%04s", dechex(strlen($data) / 2));
            // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
            $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);

            if (isset($Command['cmdParams']['repeat'])) {
                if ($Command['cmdParams']['repeat']>1 ) {
                    for ($x = 2; $x <= $Command['cmdParams']['repeat']; $x++) {
                        sleep(5);
                        // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                        $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                    }
                }
            }
        }

        /**
         * setParam3: send the commande to zigate to send a write attribute on zigbee network with a proprio field
         *
         * @param dest
         * @param Command with following info: Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
         *
         * @return none
         */
        function setParam3($dest, $Command) {
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

            $addrMode   = "02";
            $addr       = $Command['cmdParams']['address'];
            $srcEp      = "01";

            if (isset($Command['cmdParams']['destinationEndpoint'])) {
                if ($Command['cmdParams']['destinationEndpoint']>1 ) {
                    $dstEp = $Command['cmdParams']['destinationEndpoint'];
                }
                else {
                    $dstEp = "01";
                }
            }
            else {
                $dstEp = "01";
            }

            $profId                  = "0104";
            $clustId                  = $Command['cmdParams']['clusterId'];

            $secMode               = "02"; // ???
            $radius                     = "30";
            // $dataLength <- define later

            $frameControlAPS            = "40";   // APS Control Field
            // If Ack Request 0x40 If no Ack then 0x00
            // Avec 0x40 j'ai un default response

            $frameControlZCL            = "14";   // ZCL Control Field
            // Disable Default Response + Manufacturer Specific

            $frameControl = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $Proprio                    = $Command['cmdParams']['Proprio'][2].$Command['cmdParams']['Proprio'][3].$Command['cmdParams']['Proprio'][0].$Command['cmdParams']['Proprio'][1];

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId                = $Command['cmdParams']['attributeId'][2].$Command['cmdParams']['attributeId'][3].$Command['cmdParams']['attributeId'][0].$Command['cmdParams']['attributeId'][1];

            $dataType                   = $Command['cmdParams']['attributeType'];

            $attributValue              = $Command['cmdParams']['value'];

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
        function setParam4($dest, $Command) {

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
            $addr = $Command['cmdParams']['address'];
            $srcEp             = "01";
            if ($Command['cmdParams']['destinationEndpoint']>1 ) { $dstEp = $Command['cmdParams']['destinationEndpoint']; } else { $dstEp = "01"; } // $dstEp; // "01";

            $profId                  = "0104";
            $clustId                  = $Command['cmdParams']['clusterId'];

            $secMode               = "02"; // ???
            $radius                     = "30";
            // $dataLength <- calculated later

            $frameControlAPS            = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

            $frameControlZCL            = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific

            $frameControl               = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId                = $Command['cmdParams']['attributeId'][2].$Command['cmdParams']['attributeId'][3].$Command['cmdParams']['attributeId'][0].$Command['cmdParams']['attributeId'][1];

            $dataType                   = $Command['cmdParams']['attributeType'];
            $attributValue              = $Command['cmdParams']['value'];

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
        function setParamGeneric($dest, $Command) {

            cmdLog('debug', "  command setParamGeneric", $this->debug['processCmd']);

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
            $addr = $Command['cmdParams']['address'];
            $srcEp     = "01";
            if ($Command['cmdParams']['ep']>1 ) { $dstEp = $Command['cmdParams']['ep']; } else { $dstEp = "01"; } // $dstEp; // "01";

            $profId          = "0104";
            $clustId          = $Command['cmdParams']['clusterId'];

            $secMode       = "02"; // ???
            $radius             = "30";
            // $dataLength <- calculated later

            $frameControlAPS    = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

            if ($Command['cmdParams']['Proprio'] == '' ) {
                $frameControlZCL    = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific
            }
            else {
                $frameControlZCL    = "14";   // ZCL Control Field - Disable Default Response + Manufacturer Specific
            }
            $Proprio = $Command['cmdParams']['Proprio'];

            $frameControl       = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId        = $Command['cmdParams']['attributeId'][2].$Command['cmdParams']['attributeId'][3].$Command['cmdParams']['attributeId'][0].$Command['cmdParams']['attributeId'][1];

            $dataType           = $Command['cmdParams']['attributeType'];
            $attributValue      = $Command['cmdParams']['value'];

            $data2 = $frameControl.$Proprio.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

            $data = $data1.$data2;

            // $length = sprintf("%04s", dechex(strlen($data) / 2));
            // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
            $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
        }

        // Generate a 'Read attribute request'
        function readAttribute($priority, $dest, $addr, $dstEp, $clustId, $attribId, $manufId = '', $repeat = 0) {
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

            $zgCmd = "0100"; // Cmd 100 request AckAPS based on ZigBee traces.

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
                    cmdLog('error', "  readAttribute(): Format attribut (".$attrId.") incorrect => ignoré.");
                    continue;
                }
                $attribList .=  $attrId;
                $nbAttr++;
            }
            cmdLog('debug', "  readAttribute: ClustId=${clustId}, AttrList=${attribList}");
            $nbOfAttrib = sprintf("%02X", $nbAttr);
            $data = $addrMode.$addr.$srcEp.$dstEp.$clustId.$dir.$manufSpecific.$manufId.$nbOfAttrib.$attribList;

            $this->addCmdToQueue2($priority, $dest, $zgCmd, $data, $addr, $addrMode, $repeat);
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
        //     $addr     = $Command['cmdParams']['address'];
        //     $srcEp         = "01";
        //     $dstEp    = $Command['cmdParams']['ep'];
        //     $clustId              = $Command['cmdParams']['clusterId'];
        //     $profId              = "0104";
        //     $secMode           = "02";
        //     $radius                 = "1E";

        //     $zclControlField        = "10";
        //     $transactionSequence    = "01";
        //     $cmdId                  = "00";

        //     $attributs = "";
        //     $attributList           = explode(',',$Command['cmdParams']['attributeId']);

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
        //     // $address = $Command['cmdParams']['address'];
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
        //     // $address = $Command['cmdParams']['address'];
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
                cmdLog('debug', "  processCmd() ERROR: Command not set", $this->debug['processCmd']);
                return;
            }
            if (!isset($Command['dest'])) {
                cmdLog("debug", "  processCmd() ERROR: No dest defined, stop here");
                return;
            }

            cmdLog("debug", "  processCmd(".json_encode($Command).")", $this->debug['processCmd']);

            // $priority   = $this->reviewPriority($Command);
            // if ($priority==-1) {
            //     cmdLog("debug", "    L1 - processCmd - can t define priority, stop here");
            //     return;
            // }
            $dest       = $Command['dest'];

            //---- PDM ------------------------------------------------------------------
            // if (isset($Command['PDM'])) {

            //     if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE") {
            //         $cmd = "8300";

            //         $PDM_E_STATUS_OK = "00";
            //         $data = $PDM_E_STATUS_OK;

            //         $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
            //     }

            //     if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_EXISTENCE_RESPONSE") {
            //         $cmd = "8208";

            //         $recordId = $Command['recordId'];

            //         $recordExist = "00";

            //         if ($recordExist == "00" ) {
            //             $size = '0000';
            //             $persistedData = "";
            //         }

            //         $data = $recordId.$recordExist.$size;

            //         $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
            //     }
            //     return;
            // }

            // abeilleList abeilleListAll
            if ($Command['name'] == 'abeilleList') {

                cmdLog('debug', "  Get Abeilles List", $this->debug['processCmd']);
                // $this->addCmdToQueue($priority,$dest,"0015","0000","");
                $this->addCmdToQueue2(PRIO_NORM, $dest, "0015");
                return;
            }

            //----------------------------------------------------------------------
            // Bind
            // Title => 000B57fffe3025ad (IEEE de l ampoule)
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006
            if ($Command['name'] == 'bind') {

                cmdLog('debug', "  command bind", $this->debug['processCmd']);
                $cmd = "0030";

                // <target extended address: uint64_t>                                  -> 16
                // <target endpoint: uint8_t>                                           -> 2
                // <cluster ID: uint16_t>                                               -> 4
                // <destination address mode: uint8_t>                                  -> 2
                // <destination address:uint16_t or uint64_t>                           -> 4 / 16 => 0000 for Zigate
                // <destination endpoint (value ignored for group address): uint8_t>    -> 2

                // $targetExtendedAddress  = "000B57fffe3025ad";
                $targetExtendedAddress  = $Command['cmdParams']['address'];
                //
                if (isset($Command['cmdParams']['targetEndpoint'])) {
                    $targetEndpoint         = $Command['cmdParams']['targetEndpoint'];
                }
                else {
                    $targetEndpoint         = "01";
                }

                // $clustId              = "0006";
                $clustId              = $Command['cmdParams']['clusterId'];
                // $destinationAddressMode = "02";
                $destinationAddressMode = "03";

                // $destinationAddress     = "0000";
                // $destinationAddress     = "00158D0001B22E24";
                $destinationAddress     = $Command['cmdParams']['reportToAddress'];

                $dstEp    = "01";

                $data = $targetExtendedAddress.$targetEndpoint.$clustId.$destinationAddressMode.$destinationAddress.$dstEp;
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            // BindToGroup
            // Pour Telecommande RC110
            // Tcharp38: Why the need of a 0530 based function ?
            if ($Command['name'] == 'BindToGroup') {

                cmdLog('debug', "  command BindToGroup", $this->debug['processCmd']);

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

                $addrMode       = "02";
                $addr           = $Command['cmdParams']['address'];
                $srcEpBind      = "00";
                $dstEpBind      = "00";
                $profIdBind     = "0000";
                $clustIdBind    = "0021";
                $secMode        = "02";
                $radius         = "30";

                $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

                $targetExtendedAddress      = AbeilleTools::reverseHex($Command['cmdParams']['targetExtendedAddress']);
                $targetEndpoint             = $Command['cmdParams']['targetEndpoint'];
                $clustId                  = AbeilleTools::reverseHex($Command['cmdParams']['clusterId']);
                $destinationAddressMode     = "01";
                $reportToGroup              = AbeilleTools::reverseHex($Command['cmdParams']['reportToGroup']);

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
            //     cmdLog('debug', "  command bind short", $this->debug['processCmd']);
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
            //     $addr         = $Command['cmdParams']['address'];
            //     $srcEpBind         = "00";
            //     $dstEpBind    = "00";
            //     $profIdBind              = "0000";
            //     $clustIdBind              = "0021";
            //     $secMode               = "02";
            //     $radius                     = "30";
            //     $dataLength                 = "16";

            //     $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

            //     if (strlen($Command['cmdParams']['targetExtendedAddress']) < 2 ) {
            //         cmdLog('debug', "  command bind short: param targetExtendedAddress is empty. Can t do. So return", $this->debug['processCmd']);
            //         return;
            //     }
            //     $targetExtendedAddress = AbeilleTools::reverseHex($Command['cmdParams']['targetExtendedAddress']);

            //     $targetEndpoint = $Command['cmdParams']['targetEndpoint'];

            //     $clustId = AbeilleTools::reverseHex($Command['cmdParams']['clusterId']);

            //     $destinationAddressMode = "03";
            //     if (strlen($Command['destinationAddress']) < 2 ) {
            //         cmdLog('debug', "  command bind short: param destinationAddress is empty. Can t do. So return", $this->debug['processCmd']);
            //         return;
            //     }
            //     $destinationAddress = AbeilleTools::reverseHex($Command['destinationAddress']);

            //     $dstEp = $Command['cmdParams']['destinationEndpoint'];

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
            if ($Command['name'] == 'setReport') { // Tcharp38: OBSOLETE. Use 'configureReporting' instead.

                cmdLog('debug', "  WARNING: OBSOLETE: command setReport", $this->debug['processCmd']);
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
                $addr     = $Command['cmdParams']['address'];
                if (isset( $Command['cmdParams']['sourceEndpoint'] )) { $srcEp = $Command['cmdParams']['sourceEndpoint']; } else { $srcEp = "01"; }
                if (isset( $Command['cmdParams']['targetEndpoint'] )) { $targetEndpoint = $Command['cmdParams']['targetEndpoint']; } else { $targetEndpoint = "01"; }
                $ClusterId              = $Command['cmdParams']['clusterId'];
                $direction              = "00";                     //
                $manufacturerSpecific   = "00";                     //
                $manufacturerId         = "0000";                   //
                $numberOfAttributes     = "01";                     // One element at a time

                if (isset( $Command['cmdParams']['AttributeDirection'] )) { $AttributeDirection = $Command['cmdParams']['AttributeDirection']; } else { $AttributeDirection = "00"; } // See if below

                $AttributeId            = $Command['cmdParams']['attributeId'];    // "0000"

                if ($AttributeDirection == "00" ) {
                    if (isset($Command['cmdParams']['attributeType'])) {$AttributeType = $Command['cmdParams']['attributeType']; }
                    else {
                        cmdLog('error', "  set Report with an AttributeType not defines for equipment: ". $addr." attribut: ".$AttributeId." can t process" , $this->debug['processCmd']);
                        return;
                    }
                    if (isset($Command['cmdParams']['minInterval']))   { $MinInterval  = $Command['cmdParams']['minInterval']; } else { $MinInterval    = "0000"; }
                    if (isset($Command['cmdParams']['maxInterval']))   { $MaxInterval  = $Command['cmdParams']['maxInterval']; } else { $MaxInterval    = "0000"; }
                    if (isset($Command['cmdParams']['Change']))        { $Change       = $Command['cmdParams']['Change']; }      else { $Change         = "00"; }
                    $Timeout = "ABCD";
                }
                else if ($AttributeDirection == "01" ) {
                    $AttributeType          = "12";     // Put crappy info to see if they are passed to zigbee by zigate but it's not.
                    $MinInterval            = "3456";   // Need it to respect cmd 0120 format.
                    $MaxInterval            = "7890";
                    $Change                 = "12";
                    if (isset($Command['cmdParams']['Timeout']))   { $Timeout      = $Command['cmdParams']['Timeout']; }     else { $Timeout        = "0000"; }
                }
                else {
                    cmdLog('error', "  set Report with an AttributeDirection (".$AttributeDirection.") not valid for equipment: ". $addr." attribut: ".$AttributeId." can t process", $this->debug['processCmd']);
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
            if ($Command['name'] == 'setReportRaw') { // Tcharp38: OBSOLETE. Use 'configureReporting' instead.

                cmdLog('debug', "  WARNING: OBSOLETE: command setReportRaw", $this->debug['processCmd']);

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
                $addr     = $Command['cmdParams']['address'];
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
            if ($Command['name'] == 'commissioningGroupAPS') {

                cmdLog('debug', "  commissioningGroupAPS", $this->debug['processCmd']);

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
                $addr     = $Command['cmdParams']['address'];
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
                $groupId                = AbeilleTools::reverseHex($Command['cmdParams']['groupId']);
                $groupType              = "00";

                $data2 = $zclControlField.$transactionSequence.$cmdId.$total.$startIndex.$count.$groupId.$groupType;
                $dataLength = sprintf("%02X", strlen($data2) / 2);
                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                $data = $data1.$data2;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // CmdAbeille/0000/commissioningGroupAPSLegrand -> address=a048&groupId=AA00
            // Commission group for Legrand Telecommande On/Off still interrupteur Issue #1290
            if ($Command['name'] == 'commissioningGroupAPSLegrand') {

                cmdLog('debug', "  commissioningGroupAPSLegrand", $this->debug['processCmd']);

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
                $addr     = $Command['cmdParams']['address'];
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
                $groupId                = AbeilleTools::reverseHex($Command['cmdParams']['groupId']);

                $data2 = $zclControlField.$Manufacturer.$transactionSequence.$cmdId.$groupId ;

                $dataLength = sprintf( "%02X", strlen($data2) / 2);

                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

                cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEp."-".$dstEp."-".$clustId."-".$profId."-".$secMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                cmdLog('debug', "  Data2: ".$zclControlField."-".$Manufacturer."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            if ($Command['name'] == 'viewScene') {

                $cmd = "00A0";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['cmdParams']['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['cmdParams']['destinationEndpoint']; // -> 2

                $groupID = $Command['cmdParams']['groupId'];
                $sceneID = $Command['cmdParams']['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'storeScene') {

                $cmd = "00A4";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['cmdParams']['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['cmdParams']['destinationEndpoint']; // -> 2

                $groupID = $Command['cmdParams']['groupId'];
                $sceneID = $Command['cmdParams']['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'recallScene') {

                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode   = "02";                                    // Short Address -> 2
                $address    = $Command['cmdParams']['address'];                         // -> 4
                $srcEp      = "01";                                 // -> 2
                $dstEp      = $Command['cmdParams']['destinationEndpoint']; // -> 2

                $groupID    = $Command['cmdParams']['groupId'];
                $sceneID    = $Command['cmdParams']['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'sceneGroupRecall') {

                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode   = "01";                  // Group Address -> 1, Short Address -> 2
                $address    = $Command['cmdParams']['groupId'];   // -> 4
                $srcEp      = "01";                  // -> 2
                $dstEp      = "02";                  // -> 2

                $groupID    = $Command['cmdParams']['groupId'];
                $sceneID    = $Command['cmdParams']['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'addScene') {

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

                $addrMode   = "02";
                $address    = $Command['cmdParams']['address'];
                $srcEp      = "01";
                $dstEp      = $Command['cmdParams']['destinationEndpoint'];

                $groupID    = $Command['cmdParams']['groupId'];
                $sceneID    = $Command['cmdParams']['sceneID'];

                $transitionTime         = "0001";

                $sceneNameLength        = sprintf("%02s", (strlen( $Command['cmdParams']['sceneName'] )/2));      // $Command['sceneNameLength'];
                $sceneNameMaxLength     = sprintf("%02s", (strlen( $Command['cmdParams']['sceneName'] )/2));      // $Command['sceneNameMaxLength'];
                $sceneNameData          = $Command['cmdParams']['sceneName'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID.$transitionTime.$sceneNameLength.$sceneNameMaxLength.$sceneNameData ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'getSceneMembership') {

                $cmd = "00A6";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['cmdParams']['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['cmdParams']['destinationEndpoint']; // -> 2

                $groupID = $Command['cmdParams']['groupId'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'removeScene') {

                $cmd = "00A2";

                //0x00A2
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['cmdParams']['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['cmdParams']['destinationEndpoint']; // -> 2

                $groupID = $Command['cmdParams']['groupId'];
                $sceneID = $Command['cmdParams']['sceneID'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID.$sceneID ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'removeSceneAll') {

                $cmd = "00A3";

                //0x00A3
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addrMode = "02";                                    // Short Address -> 2
                $address = $Command['cmdParams']['address'];                         // -> 4
                $srcEp = "01";                                 // -> 2
                $dstEp = $Command['cmdParams']['destinationEndpoint']; // -> 2

                $groupID = $Command['cmdParams']['groupId'];

                $data = $addrMode.$address.$srcEp.$dstEp.$groupID ;

                // $length = sprintf("%04s", dechex(strlen($data) / 2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'sceneLeftIkea') {

                cmdLog('debug', "  Specific Command to simulate Ikea Telecommand < and >");

                // Msg Type = 0x0530
                $cmd = "0530";

                $addrMode = "01"; // 01 pour groupe
                $addr = $Command['cmdParams']['address'];
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

            if ($Command['name'] == 'WindowsCoveringLevel') {

                $cmd = '0530';

                $addrMode            = "02";                // 01 pour groupe, 02 pour NE
                $addr     = $Command['cmdParams']['address'];
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
            if ($Command['name'] == 'WindowsCoveringGroup') {

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
                $address        = $Command['cmdParams']['address'];
                $srcEp          = "01";
                $detEP          = "01";
                $clusterCommand = $Command['cmdParams']['clusterCommand'];

                $data = $addrMode.$address.$srcEp.$detEP.$clusterCommand;

                // $length = sprintf("%04s", dechex(strlen($data )/2));
                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // Don't know how to make it works
            if ($Command['name'] == 'touchLinkFactoryResetTarget') {

                if ($Command['touchLinkFactoryResetTarget']=="DO")
                {
                    // $this->addCmdToQueue($priority,$priority,$dest,"00D2","0000");
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "00D2");
                }
                return;
            }

            // OBSOLETE !! Replaced by cmd-Private + fct=profaluxSetTiltLift
            if ($Command['name'] == 'moveToLiftAndTiltBSO') {

                cmdLog('debug', "  command moveToLiftAndTiltBSO", $this->debug['processCmd']);

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

                $addrMode               = $Command['cmdParams']['addressMode'];
                $addr                   = $Command['cmdParams']['address'];
                $srcEp                  = "01";
                $dstEp                  = "01";
                $profId                 = "0104";
                $clustId                = "0008";
                $secMode                = "02";
                $radius                 = "1E";

                $zclControlField        = "05";
                $ManfufacturerCode      = "1011"; // inverted
                $transactionSequence    = "01";
                $cmdId                  = "10";  // Cmd Proprio Profalux
                $option                 = "03";  // Je ne touche que le Tilt
                // $Lift                   = "00";  // Not used / between 1 and 254 (see CurrentLevel attribute)
                $Lift                   = sprintf( "%02s",dechex($Command['cmdParams']['lift']));
                // $Tilt                   = "2D";  // 2D move to 45deg / between 0 and 90 (See CurrentOrentation attribute)
                $Tilt                   = sprintf( "%02s",dechex($Command['cmdParams']['inclinaison']));  // 2D move to 45deg / between 0 and 90 (See CurrentOrentation attribute)
                // $transitionTime         = "FFFF"; // FFFF ask the Tilt to move as fast as possible
                $transitionTime         = sprintf( "%04s",dechex($Command['cmdParams']['duration']));

                $data2 = $zclControlField.$ManfufacturerCode.$transactionSequence.$cmdId.$option.$Lift.$Tilt.$transitionTime ;
                $dataLength = sprintf("%02X", strlen($data2) / 2);
                $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;

                // cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEp."-".$dstEp."-".$clustId."-".$profId."-".$secMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                // cmdLog('debug', "  Data2: ".$zclControlField."-".$ManfufacturerCode."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                return;
            }

            // // setLevelStop => Obsolete: Use 'cmd-0008' + 'cmd=07' instead
            // if (isset($Command['setLevelStop']) && isset($Command['cmdParams']['address']) && isset($Command['cmdParams']['addressMode']) && isset($Command['cmdParams']['sourceEndpoint']) && isset($Command['cmdParams']['destinationEndpoint']))
            // {
            //     // <address mode: uint8_t>
            //     // <target short address: uint16_t>
            //     // <source endpoint: uint8_t>
            //     // <destination endpoint: uint8_t>

            //     $cmd = "0084"; // Stop with OnOff = Cluster 0008, cmd 07
            //     $addrMode            = $Command['cmdParams']['addressMode'];
            //     $address                = $Command['cmdParams']['address'];
            //     $srcEp         = $Command['cmdParams']['sourceEndpoint'];
            //     $dstEp    = $Command['cmdParams']['destinationEndpoint'];

            //     $data = $addrMode.$address.$srcEp.$dstEp ;

            //     // $length = sprintf("%04s", dechex(strlen($data) / 2));
            //     // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
            //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
            //     return;
            // }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ($Command['name'] == 'WriteAttributeRequest') {

                cmdLog('debug', "  WARNING: Use of OBSOLETE 'WriteAttributeRequest' command");
                $this->setParam3( $dest, $Command );
                return;
            }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ($Command['name'] == 'WriteAttributeRequestGeneric') {

                cmdLog('debug', "  WARNING: Use of OBSOLETE 'WriteAttributeRequestGeneric' command");
                $this->setParamGeneric( $dest, $Command );
                return;
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ($Command['name'] == 'WriteAttributeRequestVibration') {

                cmdLog('debug', "  WARNING: Use of OBSOLETE 'WriteAttributeRequestVibration' command");
                // Tcharp38: WHere is this code ??? $this->setParamXiaomi($dest, $Command);
                // cmdLog('debug', "ERROR: WriteAttributeRequestVibration() CAN'T be executed. Missing setParamXiaomi()", $this->debug['processCmd']);
                $this->ReportParamXiaomi($dest, $Command);
                return;
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ($Command['name'] == 'WriteAttributeRequestActivateDimmer') {

                cmdLog('debug', "  WARNING: Use of OBSOLETE 'WriteAttributeRequestActivateDimmer' command");
                $this->setParam4( $dest, $Command );
                return;
            }

            // Tcharp38 note: This is badly named. It is not a write attribute but
            // most probably a cluster 0502 cmd 00
            // OBSOLETE => Use 'cmd-0502' with 'cmd=00' instead
            if ($Command['name'] == 'writeAttributeRequestIAS_WD') {

                    cmdLog('debug', "  command writeAttributeRequestIAS_WD", $this->debug['processCmd']);
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
                    $addr = $Command['cmdParams']['address'];
                    $srcEp = "01";
                    $dstEp = "01";
                    $direction = "01";
                    $manufacturerSpecific = "00";
                    $manufacturerId = "0000";
                    $warningMode = "04";
                    if ($Command['cmdParams']['mode'] == "Flash" )      $warningMode = "04";        // 14, 24, 34: semble faire le meme son meme si la doc indique: Burglar, Fire, Emergency / 04: que le flash
                    if ($Command['cmdParams']['mode'] == "Sound" )      $warningMode = "10";
                    if ($Command['cmdParams']['mode'] == "FlashSound" ) $warningMode = "14";
                    $warningDuration = "000A"; // en seconde
                    if ($Command['cmdParams']['duration'] > 0 )         $warningDuration = sprintf("%04s", dechex($Command['cmdParams']['duration']));
                    // $strobeDutyCycle = "01";
                    // $strobeLevel = "F0";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$direction.$manufacturerSpecific.$manufacturerId.$warningMode.$warningDuration; //.$strobeDutyCycle.$strobeLevel;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                    return;
                }

            // Add Group APS
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed

            if ($Command['name'] == 'addGroupAPS') {

                cmdLog('debug', "  command add group with APS", $this->debug['processCmd']);
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
                $addr = $Command['cmdParams']['address'];
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

                cmdLog('debug', "  Data1: ".$addrMode."-".$addr."-".$srcEpBind."-".$dstEpBind."-".$clustIdBind."-".$profIdBind."-".$secMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) , $this->debug['processCmd']);
                cmdLog('debug', "  Data2: ".$dummy.$dummy1.$cmdAddGroup.$groupId.$length." len: ".(strlen($data2)/2) , $this->debug['processCmd']);

                $data = $data1.$data2;
                // cmdLog('debug', "Data: ".$data." len: ".(strlen($data)/2));

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $addr);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr);
                return;
            }

            // Replace Equipement
            if ($Command['name'] == 'replaceEquipement') {

                cmdLog('debug', "  Replace an Equipment", $this->debug['processCmd']);

                $old = $Command['old'];
                $new = $Command['new'];

                cmdLog('debug', "  Update eqLogic table for new object", $this->debug['processCmd']);
                $sql =          "UPDATE `eqLogic` SET ";
                $sql = $sql.  "name = 'Abeille-".$new."-New' , logicalId = '".$new."', configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql.  "WHERE  eqType_name = 'Abeille' AND logicalId = '".$old."' AND configuration LIKE '%".$old."%'";
                cmdLog('debug',"sql: ".$sql, $this->debug['processCmd']);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

                cmdLog('debug', "  Update cmd table for new object", $this->debug['processCmd']);
                $sql =          "UPDATE `cmd` SET ";
                $sql = $sql.  "configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql.  "WHERE  eqType = 'Abeille' AND configuration LIKE '%".$old."%' ";
                cmdLog('debug', "  sql: ".$sql, $this->debug['processCmd']);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
                return;
            }

            //
            if ($Command['name'] == 'UpGroup') {

                cmdLog('debug', '  UpGroup for: '.$Command['cmdParams']['address'], $this->debug['processCmd']);
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
                if (isset ( $Command['cmdParams']['addressMode'] )) { $addrMode = $Command['cmdParams']['addressMode']; } else { $addrMode = "02"; }

                $address = $Command['cmdParams']['address'];
                $srcEp = "01";
                if (isset ( $Command['cmdParams']['destinationEndpoint'] )) { $dstEp = $Command['cmdParams']['destinationEndpoint'];} else { $dstEp = "01"; };
                $onoff = "00";
                $stepMode = "00"; // 00 : Up, 01 : Down
                $stepSize = $Command['cmdParams']['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                return;
            }

            if ($Command['name'] == 'DownGroup') {

                cmdLog('debug', '  DownGroup for: '.$Command['cmdParams']['address'], $this->debug['processCmd']);
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
                if (isset ( $Command['cmdParams']['addressMode'] )) { $addrMode = $Command['cmdParams']['addressMode']; } else { $addrMode = "02"; }

                $address = $Command['cmdParams']['address'];
                $srcEp = "01";
                if (isset ( $Command['cmdParams']['destinationEndpoint'] )) { $dstEp = $Command['cmdParams']['destinationEndpoint'];} else { $dstEp = "01"; };
                $onoff = "00";
                $stepMode = "01"; // 00 : Up, 01 : Down
                $stepSize = $Command['cmdParams']['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $addrMode.$address.$srcEp.$dstEp.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                return;
            }

            // On / Off Timed Send
            if ($Command['name'] == 'OnOffTimed') {

                cmdLog('debug', '  OnOff for: '.$Command['cmdParams']['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['cmdParams']['action'].' - '.$Command['cmdParams']['onTime'].' - '.$Command['ffWaitTime'], $this->debug['processCmd']);
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

                $zgCmd          = "0093";
                $addrMode       = $Command['cmdParams']['addressMode'];
                $address        = $Command['cmdParams']['address'];
                $srcEp          = "01";
                $dstEp          = $Command['cmdParams']['destinationEndpoint'];
                $action         = $Command['cmdParams']['action'];
                $onTime         = $Command['cmdParams']['onTime'];
                $offWaitTime    = $Command['cmdParams']['offWaitTime'];

                $data = $addrMode.$address.$srcEp.$dstEp.$action.$onTime.$offWaitTime;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $address, $addrMode);

                // if ($addrMode == "02" ) {
                //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000" );
                //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000" );

                //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+$Command['cmdParams']['onTime']), "ep=".$dstEp."&clustId=0006&attrId=0000" );
                //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+$Command['cmdParams']['onTime']), "ep=".$dstEp."&clustId=0008&attrId=0000" );
                // }
                return;
            }

            // Take RGB (0-255) convert to X, Y and send the color
            if ($Command['name'] == 'setColourRGB') {

                // The reverse transformation
                // https://en.wikipedia.org/wiki/SRGB

                $R=$Command['cmdParams']['R'];
                $G=$Command['cmdParams']['G'];
                $B=$Command['cmdParams']['B'];

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
                $address = $Command['cmdParams']['address'];
                $srcEp = "01";
                $dstEp = $Command['cmdParams']['destinationEndpoint'];
                $colourX = str_pad( dechex($x), 4, "0", STR_PAD_LEFT);
                $colourY = str_pad( dechex($y), 4, "0", STR_PAD_LEFT);
                $duration = "0001";

                cmdLog( 'debug', "  colourX: ".$colourX." colourY: ".$colourY, $this->debug['processCmd'] );

                $data = $addrMode.$address.$srcEp.$dstEp.$colourX.$colourY.$duration ;

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            if ($Command['name'] == 'MgtLeave') {

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

                $address        = $Command['cmdParams']['address'];
                $IEEE           = $Command['cmdParams']['IEEE'];
                if (isset($Command['cmdParams']['Rejoin'])) $Rejoin = $Command['cmdParams']['Rejoin']; else $Rejoin = "00";
                if (isset($Command['cmdParams']['RemoveChildren']))  $RemoveChildren = $Command['cmdParams']['RemoveChildren']; else $RemoveChildren = "01";

                $data = $address.$IEEE.$Rejoin.$RemoveChildren;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                // $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                return;
            }

            // if (isset($Command['Remove']) && isset($Command['cmdParams']['address']) && isset($Command['cmdParams']['IEEE']))
            // https://github.com/KiwiHC16/Abeille/issues/332
            if ($Command['name'] == 'Remove') {

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

                // $address        = $Command['cmdParams']['address'];
                $address        = $Command['cmdParams']['ParentAddressIEEE'];
                $IEEE           = $Command['cmdParams']['ChildAddressIEEE'];

                $data = $address.$IEEE ;

                $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                return;
            }

            /* Tcharp38: New way of checking commands.
               'name' field contains command name. */
            if (isset($Command['name'])) {
                $cmdName = $Command['name'];
                // cmdLog('debug', '  '.$cmdName.' cmd', $this->debug['processCmd']);

                /* Note: commands are described in the following order:
                   - Zigate specific commands
                   - Zigbee standard commands
                   - Zigbee cluster library (ZCL) global commands
                   - Zigbee cluster library (ZCL) cluster specific commands
                 */

                /*
                 * Zigate specific commands
                 */

                // Zigate specific command: Set mode (raw, hybrid, normal)
                if ($cmdName == 'zgSetMode') {
                    $mode = $Command['cmdParams']['mode'];
                    if ($mode == "raw") {
                        $modeVal = "01";
                    } else if ($mode == "hybrid") {
                        $modeVal = "02";
                    } else // Normal
                        $modeVal = "00";
                    cmdLog('debug', "  Setting mode ${modeVal}/${mode}");
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0002", $modeVal);
                    return;
                }

                // Zigate specific command: Set certfication (CE, FCC)
                else if ($cmdName == 'zgSetCertification') {
                    $required = ['certif'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0019";
                    $certif = $Command['cmdParams']['certif'];
                    if ($certif == "CE")
                        $data = "01";
                    else if ($certif == "FCC")
                        $data = "02";
                    else {
                        cmdLog('error', '  zgSetCertification: Certification invalide ! ('.$certif.')');
                        return;
                    }

                    cmdLog('debug', "  zgSetCertification: Certif=${data}/${certif}");
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data);
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'zgSetPermitMode') {
                    $required = ['mode'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $mode = $Command['cmdParams']['mode'];
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

                // Zigate specific command
                else if ($cmdName == 'zgGetNetworkStatus') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0009");
                    return;
                }

                // Zigate specific command: Requests FW version
                else if ($cmdName == 'zgGetVersion') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0010");
                    return;
                }

                // Zigate specific command: Reset zigate (SW reset)
                else if ($cmdName == 'zgSoftReset') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0011");
                    return;
                }

                // Zigate specific command: Set Time server (v3.0f)
                else if ($cmdName == 'zgSetTimeServer') {
                    if (!isset($Command['cmdParams']['time'])) {
                        $zgRef = mktime(0, 0, 0, 1, 1, 2000); // 2000-01-01 00:00:00
                        $Command['cmdParams']['time'] = time() - $zgRef;
                    }
                    cmdLog('debug', "  zgSetTimeServer, time=".$Command['cmdParams']['time'], $this->debug['processCmd']);

                    /* Cmd 0016 reminder
                    payload = <timestamp UTC: uint32_t> from 2000-01-01 00:00:00
                    WARNING: PHP time() is based on 1st of jan 1970 and NOT 2000 !! */
                    $cmd = "0016";
                    $data = sprintf("%08s", dechex($Command['cmdParams']['time']));
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'zgGetTimeServer') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0017");
                    return;
                }

                // https://github.com/fairecasoimeme/ZiGate/issues/145
                // Added cmd 0807 Get Tx Power #175
                // PHY_PIB_TX_POWER_DEF (default - 0x80)
                // PHY_PIB_TX_POWER_MIN (minimum - 0)
                // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
                else if ($cmdName == 'zgGetTxPower') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0807");
                    return;
                }

                // Zigate specific command: Set TX power
                // Mandatory params: 'txPower' (2B hexa string)
                else if ($cmdName == 'zgSetTxPower') {
                    $required = ['txPower'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0806";
                    $data = $Command['cmdParams']['txPower'];

                    cmdLog('debug', "  zgSetTxPower: txPower=${data}");
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data);
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'zgStartNetwork') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0024");
                    return;
                }

                // Zigate specific command: Start Network Scan
                // Unsupported cmd on Zigate v1 at least (FW 3.21)
                // else if ($cmdName == 'zgStartNetworkScan') {
                //     $this->addCmdToQueue2(PRIO_NORM, $dest, "0025");
                //     return;
                // }

                // Zigate specific command: Set LED, ON/1 or OFF/0
                else if ($cmdName == 'zgSetLed') {
                    if ($Command['cmdParams']['value'] == 1)
                        $value = "01";
                    else
                        $value = "00";

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0018", $value);
                    return;
                }

                // Zigate specific command: Set channel mask
                // Mandatory params: 'mask' (hexa string)
                else if ($cmdName == 'zgSetChannelMask') {
                    $required = ['mask'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $mask = $Command['cmdParams']['mask'];
                    if (!ctype_xdigit($mask)) {
                        cmdLog('error', '  Invalid channel mask. Not hexa ! ('.$mask.')');
                        return;
                    }
                    $mask = str_pad($mask, 8, '0', STR_PAD_LEFT); // Add any missing zeros

                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0021", $mask);
                    return;
                }

                // Zigate specific command: Set extended PAN-ID
                // Mandatory params: 'extPanId'
                else if ($cmdName == 'zgSetExtendedPanId') {
                    $required = ['extPanId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0020";
                    $extPanId = $Command['cmdParams']['extPanId'];
                    cmdLog('debug', "  zgSetExtendedPanId: extPanId=${extPanId}");

                    /* Note: This is NOT FUNCTIONAL.
                       Zigate always answers 04/busy. Probably because network started automatically */

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $extPanId, "0000");
                    return;
                }

                // Zigate specific command: Erase PDM
                else if ($cmdName == 'zgErasePdm') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "0012");
                    return;
                }

                // Zigate specific command: Dump/save PDM
                // WARNING: Only with Abeille's firmwares (ABxx-yyyy)
                else if ($cmdName == 'zgDumpPdm') {
                    $this->addCmdToQueue2(PRIO_NORM, $dest, "AB00", "");
                    return;
                }

                // Zigate specific command: Restore PDM
                // WARNING: Only with Abeille's firmwares (ABxx-yyyy)
                // Mandatory params: 'file' (JSON file path relative to Abeille's root)
                else if ($cmdName == 'zgRestorePdm') {
                    $required = ['file'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $file = $Command['cmdParams']['file'];
                    $path = __DIR__."/../../".$file;
                    if (!file_exists($path)) {
                        cmdLog('error', "  Le fichier suivant n'existe pas: '".$file."'");
                        return;
                    }
                    $contentTxt = file_get_contents($path);
                    cmdLog('debug', "  contentTxt=".$contentTxt);
                    $content = json_decode($contentTxt, true);
                    if (!isset($content['signature']) || ($content['signature'] != 'Abeille PDM tables')) {
                        cmdLog('error', "  Fichier invalide: '".$file."'");
                        return;
                    }

                    foreach ($content['pdms'] as $pdmId => $pdm) {
                        if ($pdm['status'] != '00')
                            continue;
                        $pl = $pdmId.$pdm['size'].$pdm['data'];
                        $this->addCmdToQueue2(PRIO_NORM, $dest, "AB02", $pl);
                    }
                    return;
                }

                /*
                 * Zigbee standard commands
                 */

                // Zigbee command: Mgmt_Lqi_req
                // Mandatory: Expecting 2 parameters: 'addr' & 'startIndex'
                else if ($cmdName == 'getNeighborTable') {
                    $required = ['addr', 'startIndex'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "004E";

                    // <target short address: uint16_t>
                    // <Start Index: uint8_t>

                    $addr = $Command['cmdParams']['addr'];
                    $startIndex = $Command['cmdParams']['startIndex'];

                    $data = $addr.$startIndex ;

                    // Note: 004E seems to be ACKed command.
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, "02");
                    return;
                }

                // Zigbee command: Mgmt_Rtg_req
                // Request routing table. To Zigbee router or coordinator.
                // Mandatory params: 'addr'
                // Optional params: 'startIdx' (hex byte, default='00')
                else if ($cmdName == 'getRoutingTable') {
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // Msg Type = 0x0530
                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "00";
                    $dstEp      = "00";
                    $profId     = "0000";
                    $clustId    = "0032";
                    $secMode    = "28";
                    $radius     = "30";

                    $sqn        = $this->genSqn();
                    $startIdx   = isset($Command['cmdParams']['startIdx']) ? $Command['cmdParams']['startIdx'] : "00";

                    $data2 = $sqn.$startIdx;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'getRoutingTable'

                // Zigbee command: Get binding table (Mgmt_Bind_req)
                // Mandatory params: 'addr'
                // Optional params: none
                else if ($cmdName == 'getBindingTable') {
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // Msg Type = 0x0530
                    $zgCmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>

                    $addrMode   = "02"; // Short addr mode
                    $addr       = $Command['cmdParams']['addr'];
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

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                }

                // Zigbee command: Bind to device or bind to group.
                // Bind, thru command 0030 => generates 'Bind_req' / cluster 0021
                // Mandatory params: addr, ep, clustId, destAddr
                // Optional params: destEp required if destAddr = device ieee addr
                else if ($cmdName == 'bind0030') {

                    $required = ['addr', 'ep', 'clustId', 'destAddr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    // If 'destAddr' == IEEE then need 'destEp' too.
                    if ((strlen($Command['cmdParams']['destAddr']) == 16) && !isset($Command['cmdParams']['destEp'])) {
                        cmdLog('error', "  bind0030: Missing 'destEp'");
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
                    $addr = $Command['cmdParams']['addr'];
                    if (strlen($addr) != 16) {
                        cmdLog('error', "  bind0030: Invalid addr length (".$addr.")");
                        return;
                    }
                    $ep = $Command['cmdParams']['ep'];
                    $clustId = $Command['cmdParams']['clustId'];

                    // Dest
                    // $dstAddr: 01=16bit group addr, destEp ignored
                    // $dstAddr: 03=64bit ext addr, destEp required
                    $dstAddr = $Command['cmdParams']['destAddr'];
                    $dstEp = isset($Command['cmdParams']['destEp']) ? $Command['cmdParams']['destEp'] : "00"; // destEp ignored if group address
                    if (strlen($dstAddr) == 4) {
                        $dstAddrMode = "01";
                        $dstTxt = "group ".$dstAddr;
                    } else if (strlen($dstAddr) == 16) {
                        $dstAddrMode = "03";
                        $dstTxt = "device ".$dstAddr."/EP-".$dstEp;
                    } else {
                        cmdLog('error', "  bind0030: Invalid dest addr length (".$dstAddr.")");
                        return;
                    }
                    cmdLog('debug', '  bind0030: '.$addr.'/EP-'.$ep.'/Clust-'.$clustId.' to '.$dstTxt);
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
                    if ((strlen($Command['cmdParams']['destAddr']) == 16) && !isset($Command['cmdParams']['destEp'])) {
                        cmdLog('error', "  unbind0031: Missing 'destEp'");
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
                    $addr = $Command['cmdParams']['addr'];
                    if (strlen($addr) != 16) {
                        cmdLog('error', "  unbind0031: Invalid addr length (".$addr.")");
                        return;
                    }
                    $ep = $Command['cmdParams']['ep'];
                    $clustId = $Command['cmdParams']['clustId'];

                    // Dest
                    // $dstAddr: 01=16bit group addr, destEp ignored
                    // $dstAddr: 03=64bit ext addr, destEp required
                    $dstAddr = $Command['cmdParams']['destAddr'];
                    if (strlen($dstAddr) == 4) {
                        $dstAddrMode = "01";
                        $dstEp = '';
                        $dstTxt = 'group '.$dstAddr;
                    } else if (strlen($dstAddr) == 16) {
                        $dstAddrMode = "03";
                        $dstEp = $Command['cmdParams']['destEp'];
                        $dstTxt = 'device '.$dstAddr.'/EP-'.$dstEp;
                    } else {
                        cmdLog('error', "  unbind0031: Invalid dest addr length (".$dstAddr.")");
                        return;
                    }
                    cmdLog('debug', '  unbind0031: '.$addr.'/EP-'.$ep.'/Clust-'.$clustId.' to '.$dstTxt);
                    $data = $addr.$ep.$clustId.$dstAddrMode.$dstAddr.$dstEp;

                    // Note: Unbind is sent with ACK request
                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, "02");
                    return;
                } // End $cmdName == 'unbind0031'

                // Zigbee command: IEEE address request (IEEE_addr_req)
                // Mandatory: 'addr'
                // Optional: 'reqType' (default=00/single dev resp), 'priority'
                else if ($cmdName == 'getIeeeAddress') { // IEEE_addr_req => IEEE_addr_rsp
                    /* Checking that mandatory infos are there */
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0041";

                    // <target short address: uint16_t>
                    // <short address: uint16_t>
                    // <request type: uint8_t>: Request Type: 0 = Single 1 = Extended
                    // <start index: uint8_t>

                    $priority       = (isset($Command['priority']) ? $Command['priority'] : PRIO_NORM);
                    // See https://github.com/fairecasoimeme/ZiGate/issues/386#
                    // Both address must be the same.
                    $addr           = $Command['cmdParams']['addr'];
                    $shortAddr      = $Command['cmdParams']['addr'];
                    $reqType        = isset($Command['cmdParams']['reqType']) ? $Command['cmdParams']['reqType'] : "00"; // 00=single device response, 01=extended
                    $startIndex     = "00";

                    cmdLog('debug', "  getIeeeAddress: addr=${addr}, type=${reqType}");
                    $data = $addr.$shortAddr.$reqType.$startIndex ;

                    $this->addCmdToQueue2($priority, $dest, $zgCmd, $data, $addr);
                    return;
                }

                // Zigbee command: Network address request (NWK_addr_req)
                // Mandatory: 'ieee'
                // Optional: 'reqType' (00=single=default, 01=extended)
                else if ($cmdName == 'getNwkAddress') {
                    $required = ['ieee'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0040";

                    // <target short address: uint16_t> // Tcharp38: What for ??
                    // <extended address:uint64_t>
                    // <request type: uint8_t> 0 = Single Request 1 = Extended Request
                    // <start index: uint8_t>

                    $addr = '0000'; // What for ?
                    $ieee = $Command['cmdParams']['ieee'];
                    $reqType = isset($Command['cmdParams']['reqType']) ? $Command['cmdParams']['reqType'] : "00";
                    $startIndex = "00";

                    $data = $addr.$ieee.$reqType.$startIndex ;

                    cmdLog('debug', "  getNwkAddress: IEEE=${ieee}, ReqType=${reqType}");

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr);
                    return;
                }

                // Zigbee command: Active endpoints request (Active_EP_req)
                // Mandatory params: 'addr'
                // Optional: 'priority'
                else if ($cmdName == 'getActiveEndpoints') {
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0045";

                    // <target short address: uint16_t>

                    $prio = (isset($Command['priority']) ? $Command['priority'] : PRIO_NORM);
                    $addr = $Command['cmdParams']['addr'];
                    $addrMode = "02"; // Short addr with ACK

                    cmdLog('debug', "  getActiveEndpoints: Prio=${prio}, Addr=${addr}");
                    $data = $addr;

                    $this->addCmdToQueue2($prio, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                }

                // Zigbee command: Simple descriptor request (Simple_Desc_req)
                // Mandatory: 'addr' + 'ep'
                // Optional: 'priority'
                else if ($cmdName == 'getSimpleDescriptor') {
                    $required = ['addr', 'ep'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <target short address: uint16_t>
                    // <endpoint: uint8_t>

                    $zgCmd = "0043";
                    $prio = (isset($Command['priority']) ? $Command['priority'] : PRIO_NORM);
                    $addr = sprintf("%04X", hexdec($Command['cmdParams']['addr']));
                    $addrMode = "02"; // Short addr with ACK
                    $ep = sprintf("%02X", hexdec($Command['cmdParams']['ep']));

                    cmdLog('debug', "  getSimpleDescriptor: Prio=${prio}, Addr=${addr}, EP=${ep}");
                    $data = $addr.$ep;

                    $this->addCmdToQueue2($prio, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                }

                // Zigbee command: Node descriptor request (Node_Desc_req)
                else if ($cmdName == 'getNodeDescriptor') {
                    $required = ['addr'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    // <target short address: uint16_t>

                    $zgCmd = "0042";
                    $addr = sprintf("%04X", hexdec($Command['cmdParams']['addr']));

                    $data = $addr;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr);
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

                    $ieee = $Command['cmdParams']['IEEE'];
                    if (isset($Command['cmdParams']['Rejoin']))
                        $rejoin = $Command['cmdParams']['Rejoin'];
                    else
                        $rejoin = "00";
                    if (isset($Command['cmdParams']['RemoveChildren']))
                        $RemoveChildren = $Command['cmdParams']['RemoveChildren'];
                    else
                        $RemoveChildren = "01";

                    $data = $ieee.$rejoin.$RemoveChildren;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data);
                    return;
                }

                // Zigbee command: Management Network Update request (Mgmt_NWK_Update_req, cluster 0038)
                // Mandatory params: 'addr'
                // Optional params:
                // - 'scanChan' (hex string, default=All/'07FFF800')
                // - 'scanDuration' (hex string, default='01', 'FE' for channel change)
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

                    $addr               = $Command['cmdParams']['addr'];
                    $scanChan           = isset($Command['cmdParams']['scanChan']) ? hexdec($Command['cmdParams']['scanChan']) : 0x7FFF800;
                    $scanChan           = sprintf("%08X", $scanChan);
                    $scanDuration       = isset($Command['cmdParams']['scanDuration']) ? $Command['cmdParams']['scanDuration'] : "01";
                    $scanDuration       = strtoupper($scanDuration);
                    if ($scanDuration == "FE") // Channel change request
                        $scanCount      = "00";
                    else
                        $scanCount      = "01";
                    $networkUpdateId    = "01";
                    $networkManagerAddr = "0000"; // Useful only if scanDuration==FF

                    cmdLog('debug', "  mgmtNetworkUpdateReq: addr=".$addr.", scanChan=".$scanChan.", scanDuration=".$scanDuration.', scanCount='.$scanCount);
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

                    $manufId = isset($Command['cmdParams']['manufId']) ? $Command['cmdParams']['manufId'] : '';
                    $repeat = isset($Command['repeat']) ? $Command['repeat']: 0;
                    $this->readAttribute(PRIO_NORM, $dest, $Command['cmdParams']['addr'], $Command['cmdParams']['ep'], $Command['cmdParams']['clustId'], $Command['cmdParams']['attrId'], $manufId, $repeat);
                    return;
                }

                // ZCL global: Generic 'read attribute request' function based on 0530 zigate msg
                // Mandatory params: 'addr', 'ep', 'clustId', 'attrId'
                // Optional : 'priority', 'manufCode'
                else if ($cmdName == 'readAttribute2') {
                    /* Checking that mandatory infos are there */
                    $required = ['ep', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

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
                    $repeat   = isset($Command['repeat']) ? $Command['repeat'] : 0;

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $clustId    = $Command['cmdParams']['clustId'];
                    $profId     = "0104";
                    $secMode    = "02"; // ???
                    $radius     = "30"; // ???

                    /* ZCL header */
                    $hParams = array(
                        'manufCode' => isset($Command['cmdParams']['manufCode']) ? $Command['cmdParams']['manufCode'] : '',
                        'cmdId' => '00', // Read Attributes
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    cmdLog('debug', "  readAttribute2: AttrId=".$Command['cmdParams']['attrId']);

                    $list = explode(',', $Command['cmdParams']['attrId']);
                    $attrIdList = '';
                    foreach ($list as $attrId) {
                        if (strlen($attrId) != 4) {
                            cmdLog('error', "  readAttribute2(): Format attribut (${attrId}) incorrect => ignoré.");
                            continue;
                        }
                        $attrIdList .= AbeilleTools::reverseHex($attrId);
                    }

                    $data2 = $zclHeader.$attrIdList;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;
                    $this->addCmdToQueue2($priority, $dest, "0530", $data, $addr, $addrMode, $repeat);
                    return;
                } // End 'readAttribute2'

                // Generic 'write attribute request' function based on 0110 Zigate msg.
                // ZCL global: writeAttribute command
                // Mandatory: ep, clustId, attrId & attrVal
                // Optional : attrType, dir (default=00), manufId
                else if ($cmdName == 'writeAttribute') {
                    /* Checking that mandatory infos are there */
                    $required = ['ep', 'clustId', 'attrId', 'attrVal'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (!isset($Command['cmdParams']['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['cmdParams']['clustId'], $Command['cmdParams']['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            cmdLog('error', "  writeAttribute: 'attrType' manquant");
                            return;
                        }
                        $attrType = sprintf("%02X", $attr['dataType']);
                    } else
                        $attrType = $Command['cmdParams']['attrType'];

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
                    $repeat   = isset($Command['repeat']) ? $Command['repeat'] : 0;

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $clustId    = $Command['cmdParams']['clustId'];
                    $dir        = (isset($Command['cmdParams']['dir']) ? $Command['cmdParams']['dir'] : "00"); // 00 = to server side, 01 = to client site
                    if (isset($Command['cmdParams']['manufId']) && ($Command['cmdParams']['manufId'] != "0000")) {
                        $manufSpecific  = "01";
                        $manufCode      = $Command['cmdParams']['manufId'];
                    } else {
                        $manufSpecific  = "00";
                        $manufCode      = "0000";
                    }
                    $nbOfAttributes = "01";
                    $attrVal = $this->formatAttribute($Command['cmdParams']['attrVal'], $attrType);
                    if ($attrVal === false)
                        return;
                    $attrId = $Command['cmdParams']['attrId'];
                    $attrList = $attrId.$attrType.$attrVal;

                    cmdLog('debug', "  writeAttribute: Dir=${dir}, ManufCode=${manufCode}, AttrId=${attrId}, AttrType=${attrType}, AttrVal=${attrVal}");
                    $data = $addrMode.$addr.$srcEp.$dstEp.$clustId.$dir.$manufSpecific.$manufCode.$nbOfAttributes.$attrList;

                    $this->addCmdToQueue2($priority, $dest, "0110", $data, $addr, $addrMode, $repeat);
                    return;
                } // cmdName == 'writeAttribute'

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
                    if (!isset($Command['cmdParams']['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['cmdParams']['clustId'], $Command['cmdParams']['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            cmdLog('debug', "  command writeAttribute0530 ERROR: Missing 'attrType'");
                            return;
                        }
                        $Command['cmdParams']['attrType'] = sprintf("%02X", $attr['dataType']);
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
                    $repeat   = isset($Command['repeat']) ? $Command['repeat'] : 0;

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $clustId    = $Command['cmdParams']['clustId'];
                    $profId     = "0104";
                    $secMode    = "02"; // ???
                    $radius     = "30";
                    // $dataLength <- calculated later

                    // ZCL header
                    $dir        = hexdec(isset($Command['cmdParams']['dir']) ? $Command['cmdParams']['dir'] : "00");
                    $fcf        = sprintf("%02X", ($dir << 3) | 00);
                    $sqn        = isset($Command['cmdParams']['sqn']) ? $Command['cmdParams']['sqn'] : $this->genSqn();
                    $cmd        = "02"; // Write Attributes
                    $zclHeader  = $fcf.$sqn.$cmd;

                    // Write attribute record
                    $attrId     = AbeilleTools::reverseHex($Command['cmdParams']['attrId']);
                    $dataType   = $Command['cmdParams']['attrType'];
                    $attrVal    = AbeilleTools::reverseHex($Command['cmdParams']['attrVal']);

                    cmdLog('debug', "  writeAttribute0530: Dir=${dir}, AttrType=".$Command['cmdParams']['attrType'].", AttrVal=${attrVal}");
                    $data2 = $zclHeader.$attrId.$dataType.$attrVal;

                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2($priority, $dest, "0530", $data, $addr, $addrMode, $repeat);
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

                    // TODO: Might be required to generate proper cmd allowing to enable default response.
                    //       Some devices may not answer at all if disabled. To be confirmed.

                    $cmd            = "0140";
                    $addrMode       = "02";
                    $addr           = $Command['cmdParams']['addr'];
                    $srcEp          = "01";
                    $dstEp          = sprintf("%02X", hexdec($Command['cmdParams']['ep']));
                    $clustId        = sprintf("%04X", hexdec($Command['cmdParams']['clustId']));
                    $attrId         = isset($Command['cmdParams']['startAttrId']) ? $Command['cmdParams']['startAttrId']: "0000";
                    if (!isset($Command['cmdParams']['dir']))
                        $dir        = '00'; // Default: to server side
                    else
                        $dir        = $Command['cmdParams']['dir']; // '00' = server cluster atttrib, '01' = client cluster attrib
                    $manufSpec      = "00"; //  1 – Yes	 0 – No
                    $manufId        = "0000";
                    $maxAttrId      = isset($Command['cmdParams']['maxAttrId']) ? $Command['cmdParams']['maxAttrId'] : "FF";
                    cmdLog('debug', '  discoverAttributes: dir='.$dir.', startAttrId='.$attrId.", maxAttr=".$maxAttrId, $this->debug['processCmd']);

                    $data = $addrMode.$addr.$srcEp.$dstEp.$clustId.$attrId.$dir.$manufSpec.$manufId.$maxAttrId;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                }

                // ZCL global: Discover commands received
                // Mandatory params: 'addr', 'ep', 'clustId'
                // Optional params: 'startId' (default=00), 'max' (default=FF), 'dir' (00=toServer=default, 01=toClient)
                else if ($cmdName == 'discoverCommandsReceived') {
                    $required = ['addr', 'ep', 'clustId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = $Command['cmdParams']['clustId'];
                    $secMode    = "02";
                    $radius     = "1E";
                    $dir        = isset($Command['cmdParams']['dir']) ? $Command['cmdParams']['dir'] : '00'; // 00 = to server side, 01 = to client site

                    /* ZCL header */
                    $hParams = array(
                        'cmdId' => '11', // Discover Commands Received
                        'toCli' => ($dir == "00") ? false : true,
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    $startId    = isset($Command['cmdParams']['startId']) ? $Command['cmdParams']['startId'] : "00";
                    $max        = isset($Command['cmdParams']['max']) ? $Command['cmdParams']['max'] : "FF";
                    cmdLog('debug', "  discoverCommandsReceived: startId=${startId}, max=${max}");
                    $data2 = $zclHeader.$startId.$max;

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = $Command['cmdParams']['clustId'];
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    // $fcf        = "10"; // Frame Control Field
                    // $sqn        = $this->genSqn();
                    // $cmdId      = "13"; // Discover Commands Generated
                    $hParams = array(
                        'cmdId' => '13', // Discover Commands Generated
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    $startId    = isset($Command['cmdParams']['startId']) ? $Command['cmdParams']['startId'] : "00";
                    $max        = isset($Command['cmdParams']['max']) ? $Command['cmdParams']['max'] : "FF";
                    // $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $data2 = $zclHeader.$startId.$max;

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
                    $addr           = $Command['cmdParams']['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['cmdParams']['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['cmdParams']['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    $fcf            = "10"; // Frame Control Field
                    $sqn            = $this->genSqn();
                    $cmdId          = "01"; // Read Attributes Response
                    $data2 = $fcf.$sqn.$cmdId;

                    // if (!isset($Command['cmdParams']['attrId']) || !isset($Command['cmdParams']['status']) || !isset($Command['cmdParams']['attrType'])) {}
                    //     cmdLog('debug', "  ERROR: Missing '".$param."'");
                    //     return;
                    // }
                    $attrId = AbeilleTools::reverseHex($Command['cmdParams']['attrId']);
                    $status = $Command['cmdParams']['status'];
                    $attrType = $Command['cmdParams']['attrType'];
                    $attrVal = AbeilleTools::reverseHex($Command['cmdParams']['attrVal']);
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
                    $addr           = $Command['cmdParams']['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['cmdParams']['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['cmdParams']['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    // $fcf            = "10"; // Frame Control Field
                    // $sqn            = $this->genSqn();
                    // $cmdId          = "15"; // Discover Attributes Extended
                    $hParams = array(
                        'cmdId' => '15', // Discover Attributes Extended
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    $startId        = isset($Command['cmdParams']['startId']) ? $Command['cmdParams']['startId'] : "0000";
                    $max            = isset($Command['cmdParams']['max']) ? $Command['cmdParams']['max'] : "FF";
                    // $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $data2 = $zclHeader.$startId.$max;

                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End cmdName == 'discoverAttributesExt'

                // ZCL global: Configure reporting command (OBSOLETE => use 'configureReporting2')
                // Mandatory parameters: addr, clustId, attrId
                // Optional parameters: attrType, minInterval, maxInterval, changeVal, manufId
                else if ($cmdName == 'configureReporting') {
                    /* Mandatory infos: addr, clustId, attrId. 'attrType' can be auto-detected */
                    $required = ['addr', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (!isset($Command['cmdParams']['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['cmdParams']['clustId'], $Command['cmdParams']['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            cmdLog('debug', "  command configureReporting ERROR: Missing 'attrType'");
                            return;
                        }
                        $Command['cmdParams']['attrType'] = sprintf("%02X", $attr['dataType']);
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
                    $addr           = $Command['cmdParams']['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['cmdParams']['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['cmdParams']['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    if (isset($Command['cmdParams']['manufId'])) {
                        $manufId = $Command['cmdParams']['manufId'];
                        $fcf = "14"; // Frame Control Field
                    } else {
                        $manufId = '';
                        $fcf = "10"; // Frame Control Field
                    }
                    $sqn            = $this->genSqn();
                    $cmdId          = "06";

                    /* Attribute Reporting Configuration Record */
                    $dir                    = "00";
                    $attrId                 = AbeilleTools::reverseHex($Command['cmdParams']['attrId']);
                    $attrType               = $Command['cmdParams']['attrType'];
                    $minInterval            = isset($Command['cmdParams']['minInterval']) ? $Command['cmdParams']['minInterval'] : "0000";
                    $maxInterval            = isset($Command['cmdParams']['maxInterval']) ? $Command['cmdParams']['maxInterval'] : "0000";
                    $changeVal              = ''; // Reportable change.
                    if (isset($Command['cmdParams']['changeVal'])) {
                        $changeVal = $Command['cmdParams']['changeVal'];
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
                        //     cmdLog('debug', "  ERROR: Unsupported attrType ".$attrType, $this->debug['processCmd']);
                        //     $changeVal = "01";
                        // }
                    }
                    // $change = AbeilleTools::reverseHex($changeVal); // Reportable change.
                    // $timeout = "0000";

                    // TODO: changeVal should be set to some default, in the proper format

                    cmdLog('debug', "  configureReporting: manufId=".$manufId.", attrType='".$attrType."', min='".$minInterval."', max='".$maxInterval."', changeVal='".$changeVal."'", $this->debug['processCmd']);
                    cmdLog('debug', "  WARNING: OBSOLETE function");
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

                // ZCL global: Configure reporting command (2)
                // Mandatory parameters: addr, ep, clustId, attrId
                // Optional params: manufCode (2B hex), dir (1B hex, default=00)
                // Mandatory extra params if dir=00: attrType (1B hex), minInterval (number), maxInterval (number), changeVal (number)
                // Mandatory extra params if dir=01: timeout (number)
                else if ($cmdName == 'configureReporting2') {
                    /* Mandatory infos: addr, clustId, attrId. 'attrType' can be auto-detected */
                    $required = ['addr', 'ep', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $dir = isset($Command['cmdParams']['dir']) ? $Command['cmdParams']['dir'] : '00';

                    if ($dir == '00') {
                        // attrType is optional because can be guessed thanks to clustId/attrId
                        $attrType = isset($Command['cmdParams']['attrType']) ? $Command['cmdParams']['attrType'] : '';
                        if ($attrType == '') {
                            /* Attempting to find attribute type according to its id */
                            $attr = zbGetZCLAttribute($Command['cmdParams']['clustId'], $Command['cmdParams']['attrId']);
                            if (($attr === false) || !isset($attr['dataType'])) {
                                cmdLog('error', "  command configureReporting2 ERROR: Missing 'attrType'");
                                return;
                            }
                            $attrType = sprintf("%02X", $attr['dataType']);
                        }
                    }

                    // $required0 = ['minInterval', 'maxInterval', 'changeVal']; // + 'attrType' tested before
                    // $required1 = ['timeout'];
                    // if ($dir == '00')
                    //     $required = $required0;
                    // else
                    //     $required = $required1;
                    // if (!$this->checkRequiredParams($required, $Command))
                    //     return;

                    $zgCmd = "0530";

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
                    $addr           = $Command['cmdParams']['addr'];
                    $srcEp          = "01";
                    $dstEp          = $Command['cmdParams']['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['cmdParams']['clustId'];
                    $secMode        = "02";
                    $radius         = "1E";
                    $attrId         = $Command['cmdParams']['attrId'];

                    /* ZCL header */
                    $hParams = array(
                        'manufCode' => isset($Command['cmdParams']['manufCode']) ? $Command['cmdParams']['manufCode'] : '',
                        'cmdId' => '06', // Configure reporting
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    /* Attribute Reporting Configuration Record */
                    if ($dir == '00') {
                        $minInterval = isset($Command['cmdParams']['minInterval']) ? $Command['cmdParams']['minInterval'] : 0;
                        $maxInterval = isset($Command['cmdParams']['maxInterval']) ? $Command['cmdParams']['maxInterval'] : 0;
                        $changeVal   = isset($Command['cmdParams']['changeVal']) ? $Command['cmdParams']['changeVal'] : 1;

                        if ($maxInterval == 0xffff) {
                            cmdLog('debug', "  configureReporting2: Disable reporting");
                            // 'changeVal' must 0 for that case
                            $changeVal = 0;
                        } else if (($maxInterval == 0) && ($minInterval != 0xffff)) {
                            cmdLog('debug', "  configureReporting2: No periodic reporting");
                            if ($changeVal == 0) {
                                cmdLog('error', "  configureReporting2: 'changeVal' ne peut etre 0");
                                return;
                            }
                        } else if (($maxInterval == 0) && ($minInterval == 0xffff)) {
                            cmdLog('debug', "  configureReporting2: Revert to default reporting");
                            // 'changeVal' must 0 for that case
                            $changeVal = 0;
                        } else {
                            if ($maxInterval < $minInterval) {
                                cmdLog('error', "  configureReporting2: maxInterval < minInterval");
                                return;
                            }
                        }

                        $minInterval = $this->formatAttribute($minInterval, "uint16");
                        $maxInterval = $this->formatAttribute($maxInterval, "uint16");

                        // 'changeVal' must be omitted if 'discrete' data types
                        $dt = hexdec($attrType);
                        if ((($dt >= 0x08) && ($dt <= 0x1f)) || (($dt >= 0x30) && ($dt <= 0x31)) || ($dt >= 0xe8)) // Discrete types
                            $changeVal = '';
                        else
                            $changeVal = $this->formatAttribute($changeVal, $attrType);

                        cmdLog('debug', "  configureReporting2: AttrId=${attrId}, AttrType=${attrType}, Min=${minInterval}, Max=${maxInterval}, ChangeVal=${changeVal}");
                        $attrId = AbeilleTools::reverseHex($attrId);
                        $minInterval = AbeilleTools::reverseHex($minInterval);
                        $maxInterval = AbeilleTools::reverseHex($maxInterval);
                        $changeVal = AbeilleTools::reverseHex($changeVal); // Reverse if > 1B

                        $data2 = $zclHeader.'00'.$attrId.$attrType.$minInterval.$maxInterval.$changeVal;
                    } else {
                        $timeout = $Command['cmdParams']['Timeout'];
                        $timeout = sprintf("%04X", $timeout);
                        $timeout = AbeilleTools::reverseHex($timeout);

                        $data2 = $zclHeader.'01'.$attrId.$timeout;
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End 'configureReporting2'

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $clustId    = $Command['cmdParams']['clustId'];
                    $dir        = "00"; // 00=attribute is reported, 01=attribute is received
                    $nbOfAttr   = "01";
                    $manufSpecific = "00";
                    $manufId    = "0000";
                    $attrDir    = "00";
                    $attrId     = $Command['cmdParams']['attrId'];

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0000';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = "00"; // Reset to Factory Defaults

                    $data2      = $fcf.$sqn.$cmdId;
                    $dataLen2   = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // Cluster 0000, $cmdName == 'cmd-0000'

                // ZCL cluster 0003 specific: Identify command
                // Mandatory params: 'address' (hex 2B)  & 'EP' (hex 1B)
                // Optional: 'duration' (hex 2B, default=0010)
                else if ($cmdName == 'identifySend') {
                    $required = ['addr', 'EP']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0070";
                    // Msg Type = 0x0070
                    // Identify Send

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <time: uint16_t> Time: Seconds

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $duration   = (isset($Command['cmdParams']['duration']) && ($Command['cmdParams']['duration'] != '')) ? $Command['cmdParams']['duration'] : '0010';

                    cmdLog('debug', '  identifySend: ep='.$dstEp.', duration='.$duration);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$duration;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0003/identifySend

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
                    $zgCmd      = "0060";
                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $group      = $Command['cmdParams']['group'];

                    cmdLog('debug', '  addGroup: Ep='.$dstEp.', Group='.$group);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$group;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0004, $cmdName == 'addGroup'

                // ZCL cluster 0004 specific: removeGroup, sent to server
                // Mandatory params: 'addr', 'ep', & 'group'
                else if ($cmdName == 'removeGroup') {
                    $required = ['addr', 'ep', 'group']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0063";
                    // <address mode: uint8_t>
                    //<target short address: uint16_t>
                    //<source endpoint: uint8_t>
                    //<destination endpoint: uint8_t>
                    //<group address: uint16_t>
                    $addrMode = "02";
                    $addr = $Command['cmdParams']['addr'];
                    $srcEp = "01";
                    $dstEp = $Command['cmdParams']['ep'] ;
                    $group = $Command['cmdParams']['group'];

                    cmdLog('debug', '  removeGroup: Ep='.$dstEp.', Group='.$group);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$group;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'removeGroup'

                // ZCL cluster 0004 specific: getGroupMembership, sent to server
                // Mandatory params: 'addr', 'ep'
                else if ($cmdName == 'getGroupMembership') {
                    $required = ['addr', 'ep']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0062";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <group count: uint8_t>
                    // <group list:data>

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $ep         = $Command['cmdParams']['ep'];
                    $groupCount = "00";
                    $groupList  = "";

                    /* Correcting EP size if required (ex "1" => "01") */
                    if (strlen($ep) != 2) {
                        $EP = hexdec($ep);
                        $ep = sprintf("%02X", (hexdec($EP)));
                    }

                    $data = $addrMode.$addr.$srcEp.$ep.$groupCount.$groupList;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0004, $cmdName == 'getGroupMembership'

                // ZCL cluster 0006/On off specific.
                // Mandatory params: addr, ep & cmd (00=off, 01=on, 02=toggle)
                // Mandatory params: addrGroup & cmd if addrMode=01/group
                // Mandatory params: cmd if addrMode=04/broadcast
                else if ($cmdName == 'cmd-0006') {
                    $required1 = ['addr', 'ep', 'cmd']; // Mandatory infos for dev addr
                    $required2 = ['addrGroup', 'cmd']; // Mandatory infos for group addr
                    $required3 = ['cmd']; // Mandatory infos for broadcast
                    $addrMode   = isset($Command['cmdParams']['addrMode']) ? $Command['cmdParams']['addrMode'] : "02"; // 01=Group, 02=device (default), 04=broadcast
                    if ($addrMode == '01') {
                        $required = $required2; // Group command
                    } else if ($addrMode == '04') {
                        $required = $required3; // Broadcast command
                    } else {
                        $required = $required1;
                    }
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmdId = $Command['cmdParams']['cmd'];

                    if (($cmdId == '00') || ($cmdId == '01') || ($cmdId == '02')) { // Off, on, or toggle
                        $zgCmd      = "0092";

                        $addr       = ($addrMode == '02') ? $Command['cmdParams']['addr'] : (($addrMode == '01') ? $Command['addrGroup'] : 'DEAD');
                        $srcEp      = "01";
                        $dstEp      = ($addrMode == '02') ? $Command['cmdParams']['ep'] : '01';
                        $cmdId      = $Command['cmdParams']['cmd'];

                        cmdLog('debug', "  cmd-0006: addrMode=${addrMode}, addr=${addr}, cmd=${cmdId}");
                        $data = $addrMode.$addr.$srcEp.$dstEp.$cmdId;

                        $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    } else {
                        cmdLog('error', "  cmd-0006: Unsupported cluster 0006 specific command ".$cmdId);
                    }

                    return;
                } // End $cmdName == 'cmd-0006'

                // ZCL cluster 0004 specific: removeAllGroups, sent to server
                // Mandatory params: 'addr', 'ep'
                else if ($cmdName == 'removeAllGroups') {
                    $required = ['addr', 'ep']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0064";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <group count: uint8_t>
                    // <group list:data>

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $ep         = $Command['cmdParams']['ep'];
                    $groupCount = "00";
                    $groupList  = "";

                    /* Correcting EP size if required (ex "1" => "01") */
                    if (strlen($ep) != 2) {
                        $EP = hexdec($ep);
                        $ep = sprintf("%02X", hexdec($EP));
                    }

                    $data = $addrMode.$addr.$srcEp.$ep;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End cluster 0004, $cmdName == 'removeAllGroups'

                // ZCL cluster 0008/Level control specific: (received) commands
                // Mandatory params: addr, EP, Level (in dec, %), duration (dec)
                // Optional params: duration (default=0001)
                else if ($cmdName == 'setLevel') {
                    $required = ['addr', 'level']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (($Command['cmdParams']['Level'] < 0) || ($Command['cmdParams']['level'] > 100)) {
                        cmdLog('error', "  setLevel: 'Level' en dehors de la plage 0->100");
                        return;
                    }
                    $zgCmd = "0081"; // Move to level with/without on/off

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <onoff : uint8_t>
                    // <Level: uint8_t >
                    // <Transition Time: uint16_t>

                    if (isset($Command['cmdParams']['addressMode'])) $addrMode = $Command['cmdParams']['addressMode']; else $addrMode = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $onOff      = "01";
                    $l          = intval($Command['cmdParams']['Level'] * 255 / 100);
                    $level      = sprintf("%02X", $l);
                    $duration   = isset($Command['cmdParams']['duration']) ? sprintf("%04X", $Command['cmdParams']['duration']) : "0001";
                    cmdLog('debug', "  setLevel: onOff=".$onOff.", level=".$level.", duration=".$duration);

                    $data = $addrMode.$addr.$srcEp.$dstEp.$onOff.$level.$duration;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $zgCmd, $data, $addr, $addrMode);

                    // if ($addrMode == "02") {
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000");
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000");

                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2+$Command['cmdParams']['duration']), "ep=".$dstEp."&clustId=0006&attrId=0000");
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3+$Command['cmdParams']['duration']), "ep=".$dstEp."&clustId=0008&attrId=0000");
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

                    $zgCmd = "0081"; // Move to level with/without on/off

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <onoff : uint8_t>
                    // <Level: uint8_t >
                    // <Transition Time: uint16_t>

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $onoff      = "01";
                    $level      = sprintf("%02X", $Command['cmdParams']['Level']);
                    $duration   = isset($Command['cmdParams']['duration']) ? sprintf("%04X", $Command['cmdParams']['duration']) : "0001";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$onoff.$level.$duration;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $zgCmd, $data, $addr, $addrMode);

                    // if ($addrMode == "02") {
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2), "ep=".$dstEp."&clustId=0006&attrId=0000");
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3), "ep=".$dstEp."&clustId=0008&attrId=0000");

                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+2+$Command['cmdParams']['duration']), "ep=".$dstEp."&clustId=0006&attrId=0000");
                    //     $this->publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+3+$Command['cmdParams']['duration']), "ep=".$dstEp."&clustId=0008&attrId=0000");
                    // }
                    return;
                }

                // ZCL cluster 0008/Level control specific.
                // Mandatory params: addr, ep & cmd ('00', '04, '07')
                //   + 'level' for cmd '00' or '04'
                // Optional params: 'duration' for cmd '00' or '04'
                else if ($cmdName == 'cmd-0008') {
                    $required1 = ['addr', 'ep', 'cmd']; // Mandatory infos
                    $required2 = ['addr', 'ep', 'cmd', 'level']; // Mandatory infos for 00 & 04
                    if (isset($Command['cmdParams']['cmd']) && ($Command['cmdParams']['cmd'] == "07"))
                        $required = $required1;
                    else
                        $required = $required2;
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $cmdId = $Command['cmdParams']['cmd'];

                    // TO BE COMPLETED
                    if (($cmdId == '00') || ($cmdId == '04')) { // Move to Level without (00) or with On/Off (04)
                        $zgCmd      = "0081";
                        $addrMode   = "02"; // Assuming short addr
                        $addr       = $Command['cmdParams']['addr'];
                        $srcEp      = "01";
                        $dstEp      = $Command['cmdParams']['ep'];
                        if ($cmdId == '00')
                            $onOff = "00";
                        else
                            $onOff = "01";
                        $level      = sprintf("%02X", intval($Command['cmdParams']['level']));
                        $duration   = isset($Command['cmdParams']['duration']) ? sprintf("%04X", $Command['cmdParams']['duration']) : "0001";
                        cmdLog('debug', '  cmd-0008: onOff='.$onOff.', level='.$level.', duration='.$duration);

                        $data       = $addrMode.$addr.$srcEp.$dstEp.$onOff.$level.$duration;

                        $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    } else if ($cmdId == '07') { // Stop with OnOff
                        $zgCmd      = "0084"; // Stop with OnOff = Cluster 0008, cmd 07
                        $addrMode   = "02"; // Assuming short addr
                        $addr       = $Command['cmdParams']['addr'];
                        $srcEp      = "01";
                        $dstEp      = $Command['cmdParams']['ep'];

                        $data       = $addrMode.$addr.$srcEp.$dstEp;

                        $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    } else {
                        cmdLog('error', "  cmd-0008: Unsupported cluster 0008 command ".$cmdId);
                    }

                    return;
                } // End $cmdName == 'cmd-0008'

                // ZCL cluster 0019 specific: Inform Zigate that there is a valid OTA image
                else if ($cmdName == 'otaLoadImage') {
                    $required = ['manufCode', 'imgType']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $manufCode = $Command['cmdParams']['manufCode'];
                    $imgType = $Command['cmdParams']['imgType'];
                    if (!isset($GLOBALS['ota_fw']) || !isset($GLOBALS['ota_fw'][$manufCode]) || !isset($GLOBALS['ota_fw'][$manufCode][$imgType])) {
                        cmdLog('debug', "  ERROR: No such FW", $this->debug['processCmd']);
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

                    $manufCode = $Command['cmdParams']['manufCode'];
                    $imgType = $Command['cmdParams']['imgType'];
                    if (!isset($GLOBALS['ota_fw']) || !isset($GLOBALS['ota_fw'][$manufCode]) || !isset($GLOBALS['ota_fw'][$manufCode][$imgType])) {
                        return;
                    }
                    $fw = $GLOBALS['ota_fw'][$manufCode][$imgType];

                    $addrMode = "02";
                    $addr = $Command['cmdParams']['addr'];
                    $srcEp = "01"; // Zigate is source
                    $dstEp = $Command['cmdParams']['ep'];
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
                    $manufCode = $Command['cmdParams']['manufCode'];
                    $imgType = $Command['cmdParams']['imgType'];
                    $imgOffset = $Command['cmdParams']['imgOffset'];
                    $maxData = $Command['cmdParams']['maxData'];

                    if (!isset($GLOBALS['ota_fw']) || !isset($GLOBALS['ota_fw'][$manufCode]) || !isset($GLOBALS['ota_fw'][$manufCode][$imgType])) {
                        cmdLog('debug', "  otaImageBlockResponse WARNING: ManufCode=${manufCode}, ImgType=${imgType} => NO FW. Request ignored");
                        return;
                    }
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
                    cmdLog('debug', "  otaImageBlockResponse: Offset=${realOffset}, ImgSize=${imgSize}");
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
                    $addr = $Command['cmdParams']['addr'];
                    $srcEp = "01";
                    $dstEp = $Command['cmdParams']['ep'];
                    $sqn = $Command['cmdParams']['sqn'] ? $Command['cmdParams']['sqn'] : $this->genSqn();
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
                        $eqHName = $eqLogic->getHumanName();
                        message::add("Abeille", $eqHName.": Mise-à-jour du firmware démarrée", "");
                    }
                    return;
                }

                // ZCL cluster 0019 specific: Inform device to apply new image
                else if ($cmdName == 'otaUpgradeEndResponse') {
                    // WORK ONGOING: really required ??
                    cmdLog('debug', "  ERROR: otaUpgradeEndResponse NOT IMPLEMENTED", $this->debug['processCmd']);
                    return;
                }

                // ZCL cluster 0019 specific: (received) commands
                else if ($cmdName == 'cmd-0019') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0019';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "19"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = $Command['cmdParams']['cmd'];

                    $data2 = $fcf.$sqn.$cmdId;

                    // Image notify handled by otaImageNotify (zigate 0500)
                    // if ($cmdId == "00") { // Image Notify
                    //     $required = ['manufCode', 'imgType']; // Mandatory infos
                    //     if (!$this->checkRequiredParams($required, $Command))
                    //         return;
                    //     $plType = "02"; // queryJitter + manufCode + imgType
                    //     $queryJitter = "32"; // 0x01 – 0x64
                    //     $manufCode = AbeilleTools::reverseHex($Command['cmdParams']['manufCode']);
                    //     $imageType = AbeilleTools::reverseHex($Command['cmdParams']['imgType']);
                    //     $data2 .= $plType.$queryJitter.$manufCode.$imageType;
                    // } else

                    // Query next image response handled by Zigate server
                    // if ($cmdId == "02") { // Query Next Image Response
                    //     $required = ['status', 'manufCode', 'imgType', 'imgVersion', 'imgSize']; // Mandatory infos
                    //     if (!$this->checkRequiredParams($required, $Command))
                    //         return;
                    //     $status = $Command['cmdParams']['status'];
                    //     if ($status != '00')
                    //         $data2 = $fcf.$sqn.$cmdId.$status;
                    //     else {
                    //         $manufCode = AbeilleTools::reverseHex($Command['cmdParams']['manufCode']);
                    //         $imageType = AbeilleTools::reverseHex($Command['cmdParams']['imgType']);
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
                    //     $manufCode = $Command['cmdParams']['manufCode'];
                    //     $imgType = $Command['cmdParams']['imgType'];
                    //     $imgOffset = $Command['cmdParams']['imgOffset'];
                    //     $maxData = $Command['cmdParams']['maxData'];

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
                    //     cmdLog('debug', "  Reading data from real offset ".$realOffset, $this->debug['processCmd']);
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
                        cmdLog('debug', "  ERROR: Unsupported cmdId ".$cmdId, $this->debug['processCmd']);
                        return;
                    }

                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr);
                    return;
                }

                // ZCL cluster 0020 (Poll control) specific client commands
                else if ($cmdName == 'cmd-0020') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0020';
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "19"; // Frame Control Field
                    $sqn        = $this->genSqn();
                    $cmdId      = $Command['cmdParams']['cmd'];

                    $data2 = $fcf.$sqn.$cmdId;

                    // Cmds reminder:
                    // 0x00 Check-in Response M
                    // 0x01 Fast Poll Stop M
                    // 0x02 Set Long Poll Interval O
                    // 0x03 Set Short Poll Interval O
                    if ($cmdId == "00") { // Check-in Response
                        $data2 .= '00'.'0000';
                    } else {
                        cmdLog('error', "  cmd-0020: Unsupported cmdId ".$cmdId, $this->debug['processCmd']);
                        return;
                    }

                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr);
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
                //     $address        = $Command['cmdParams']['address'];
                //     $srcEp          = "01";
                //     $detEP          = "01";
                //     $clusterCommand = $Command['cmdParams']['clusterCommand'];

                //     $data = $addrMode.$address.$srcEp.$detEP.$clusterCommand;

                //     $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $address);
                //     return;
                // } // End $cmdName == 'WindowsCovering'

                // ZCL cluster 0102/Window covering specific cmds
                // Mandatory params: addr, ep, cmd
                //      + value (décimal) for cmd '04'/'05'/'07'/'08'
                // Optional params: none
                else if ($cmdName == 'cmd-0102') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    $cmdId = $Command['cmdParams']['cmd'];
                    if ($cmdId == '04' || $cmdId == '05' || $cmdId == '07' || $cmdId == '08')
                        if (!isset($Command['cmdParams']['value'])) {
                            cmdLog('error', "  cmd-0102: Champ 'value' non renseigné");
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

                    $zgCmd        = "00FA";

                    $addrMode   = "02"; // 01 pour groupe, 02 pour NE
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $extra      = '';
                    $value      = (isset($Command['cmdParams']['value']) ? (int)$Command['cmdParams']['value'] : 0);
                    if ($cmdId == "04" || $cmdId == "07")
                        $extra = sprintf("%04X", $value); // uint16
                    else if ($cmdId == "05" || $cmdId == "08")
                        $extra = sprintf("%02X", $value); // uint8

                    cmdLog('debug', "  cmd-0102: CmdId=${cmdId}, Extra=${extra}");
                    $data = $addrMode.$addr.$srcEp.$dstEp.$cmdId.$extra;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-0102'

                // ZCL cluster 0201 specific commands
                // Mandatory params: addr, ep, cmd
                //    and amount for cmd '00'
                // Optional params: mode (default=00/heat) for cmd '00'
                else if ($cmdName == 'cmd-0201') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0201';
                    $secMode    = "02";
                    $radius     = "1E";
                    $cmdId      = $Command['cmdParams']['cmd'];

                    /* ZCL header */
                    // Dir = 0 = to server
                    // $fcf        = "11"; // Frame Control Field
                    // $sqn        = $this->genSqn();
                    $hParams = array(
                        'clustSpecific' => true,
                        'cmdId' => $cmdId
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    if ($cmdId == "00") { // Client->server: Setpoint Raise/Lower
                        // Mode values:
                        // 0x00 Heat (adjust Heat Setpoint)
                        // 0x01 Cool (adjust Cool Setpoint)
                        // 0x02 Both (adjust Heat Setpoint and Cool Setpoint)
                        $mode = isset($Command['cmdParams']['mode']) ? $Command['cmdParams']['mode'] : "00";
                        if (($mode != '00') && ($mode != '01') && ($mode != '02')) {
                            cmdLog('error', "  cmd-0201: SetPoint raise/lower: Mode invalide: '${mode}'");
                            return;
                        }
                        // Amount = signed 8-bit int, value for increase or decrease in steps of 0.1°C.
                        $amount = $Command['cmdParams']['amount'];
                        cmdLog('debug', "  cmd-0201: Cmd=00, Mode=${mode}, Amount=${amount}");
                        $data2 = $zclHeader.$mode.$amount;
                    } else {
                        cmdLog('error', "  cmd-0201: Commande ".$cmdId." non supportée");
                        return;
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
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

                    $zgCmd        = "00B7"; // Move to color
                    if (isset($Command['cmdParams']['addressMode'])) $addrMode = $Command['cmdParams']['addressMode']; else $addrMode = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    if (isset($Command['cmdParams']['ep'])) $dstEp = $Command['cmdParams']['ep']; else $dstEp = "01";
                    $colourX    = $Command['cmdParams']['X'];
                    $colourY    = $Command['cmdParams']['Y'];
                    if (isset($Command['cmdParams']['duration']) && $Command['cmdParams']['duration']>0)
                        $duration = sprintf("%04s", dechex($Command['cmdParams']['duration']));
                    else
                        $duration = "0001";

                    $data = $addrMode.$addr.$srcEp.$dstEp.$colourX.$colourY.$duration;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $zgCmd, $data, $addr, $addrMode);
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

                    $zgCmd = "00C0"; // 00C0=Move to colour temperature

                    if (isset($Command['cmdParams']['addressMode'])) $addrMode = $Command['cmdParams']['addressMode']; else $addrMode = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    if (isset($Command['cmdParams']['ep'])) $dstEp = $Command['cmdParams']['ep']; else $dstEp = "01";
                    // Color temp K = 1,000,000 / ColorTempMireds,
                    // where ColorTempMireds is in the range 1 to 65279 mireds inclusive,
                    // giving a color temp range from 1,000,000 kelvins to 15.32 kelvins.
                    $tempK = intval($Command['cmdParams']['slider']);
                    if ($tempK == 0)
                        $tempMireds = "0000";
                    else {
                        if ($tempK < 15.32)
                            $tempK = 15.32;
                        else if ($tempK > 1000000)
                            $tempK = 1000000;
                        $tempMireds  = sprintf("%04X", 1000000 / $tempK);
                    }
                    $transition = "0001"; // Transition time

                    cmdLog('debug', '  setTemperature: TempK='.$tempK.' => Using tempMireds='.$tempMireds.', transition='.$transition);
                    $data = $addrMode.$addr.$srcEp.$dstEp.$tempMireds.$transition;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $zgCmd, $data, $addr, $addrMode);

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

                    $zgCmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0500'; // IAS Zone
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();

                    $cmdId      = $Command['cmdParams']['cmd'];
                    if ($cmdId == "00") { // Zone enroll response
                        $data2 = $fcf.$sqn.$cmdId.'00'.$Command['cmdParams']['zoneId'];
                    } else {
                        cmdLog('error', "  Unsupported cmdId ".$cmdId);
                        return;
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
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

                    $zgCmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0501'; // IAS ACE
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();

                    $cmdId      = $Command['cmdParams']['cmd'];
                    if ($cmdId == "05") { // Get Panel Status Response
                        $panelStatus = "00";
                        $secRemaining = "00";
                        $audibleNotif = "00";
                        $alarmStatus = "00";
                        $data2 = $fcf.$sqn.$cmdId.$panelStatus.$secRemaining.$audibleNotif.$alarmStatus;
                    } else {
                        cmdLog('error', "  Unsupported cmdId ".$cmdId);
                        return;
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
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

                    $zgCmd        = "0530";
                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '0502'; // IAS WD
                    $secMode    = "02";
                    $radius     = "1E";

                    /* ZCL header */
                    $fcf        = "11"; // Frame Control Field
                    $sqn        = $this->genSqn();

                    // Use cases: #2242, #2550
                    $cmdId      = $Command['cmdParams']['cmd'];
                    if ($cmdId == "00") { // Start warning
                        $mode = isset($Command['cmdParams']['mode']) ? $Command['cmdParams']['mode'] : 'emergency'; // Warning mode: Emergency
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
                        if (isset($Command['cmdParams']['strobe']))
                            $strobe = ($Command['cmdParams']['strobe'] == 'on') ? 1 : 0;
                        else {
                            if ($mode == 0) // Warning mode == stop
                                $strobe = 0; // Strobe OFF
                            else
                                $strobe = 1; // Strobe ON
                        }
                        $sirenl = isset($Command['cmdParams']['sirenl']) ? $Command['cmdParams']['sirenl'] : 'high'; // Siren level
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
                        $duration = isset($Command['cmdParams']['duration']) ? $Command['cmdParams']['duration'] : 10; // Default=10sec

                        cmdLog('debug', "  Start warning: Using mode=".$mode.", strobe=".$strobe.", sirenl=".$sirenl.", duration=".$duration);
                        $map8 = ($mode << 4) | ($strobe << 2) | $sirenl;
                        $map8 = sprintf("%02X", $map8); // Convert to hex string
                        // cmdLog('debug', "   map8=".$map8);
                        $duration = sprintf("%04X", $duration); // Convert to hex string
                        $duration = AbeilleTools::reverseHex($duration);
                        $data2 = $fcf.$sqn.$cmdId.$map8.$duration."05"."03";
                    } else {
                        cmdLog('error', "  Unsupported cluster 0502 command ".$cmdId);
                        return;
                    }
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-0502'

                // ZCL cluster 1000 specific: (received) commands
                // Mandatory:
                // Optional: 'dir' (00=toServer=default, 01=toClient)
                //   Other optional parameters depending on 'cmd' & 'dir'.
                else if ($cmdName == 'cmd-1000') {
                    $required = ['addr', 'ep', 'cmd']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = '1000';
                    $secMode    = "02";
                    $radius     = "1E";
                    $dir        = (isset($Command['cmdParams']['dir']) ? $Command['cmdParams']['dir'] : "00"); // 00 = to server side, 01 = to client site
                    $cmdId      = $Command['cmdParams']['cmd'];

                    /* ZCL header */
                    // $fcf        = "11"; // Frame Control Field
                    // $sqn        = $this->genSqn();
                    $hParams = array(
                        'clustSpecific' => true,
                        'toCli' => ($dir == "00") ? false : true,
                        'cmdId' => $cmdId,
                    );
                    $zclHeader = $this->genZclHeader($hParams);

                    if ($dir == "00") { // To server
                        // 0x00 Scan request M 13.3.2.2.1
                        // 0x02 Device information request M 13.3.2.2.2
                        // 0x06 Identify request M 13.3.2.2.3
                        // 0x07 Reset to factory new request M 13.3.2.2.4
                        // 0x10 Network start request M 13.3.2.2.5
                        // 0x12 Network join router request M 13.3.2.2.6
                        // 0x14 Network join end device request M 13.3.2.2.7
                        // 0x16 Network update request M 13.3.2.2.8
                        // 0x41 Get group identifiers request O* 13.3.2.2.9
                        // 0x42 Get endpoint list request O
                        if ($cmdId == "41") { // Get Group Identifiers Request Command
                            $startIdx = isset($Command['cmdParams']['startIdx']) ? $Command['cmdParams']['startIdx'] : "00";
                            $data2 = $zclHeader.$startIdx;
                        } else if ($cmdId == "42") { // Get endpoint list request
                            $startIdx = isset($Command['cmdParams']['startIdx']) ? $Command['cmdParams']['startIdx'] : "00";
                            $data2 = $zclHeader.$startIdx;
                        } else {
                            cmdLog('debug', "  ERROR: Unsupported cluster 1000 command ${cmdId} to SERVER");
                            return;
                        }
                    } else { // To client
                        // 0x01 Scan response Mandatory
                        // 0x03 Device information response Mandatory
                        // 0x11 Network start response Mandatory
                        // 0x13 Network join router response Mandatory
                        // 0x15 Network join end device response Mandatory
                        // 0x40 Endpoint information Optional
                        // 0x41 Get group identifiers response Mandatory if get group identifiers request command is generated; otherwise Optional
                        // 0x42 Get endpoint list response
                        if ($cmdId == "41") { // Get Group Identifiers Response
                            $total = isset($Command['cmdParams']['total']) ? $Command['cmdParams']['total'] : "00";
                            $startIdx = isset($Command['cmdParams']['startIdx']) ? $Command['cmdParams']['startIdx'] : "00";
                            $count = isset($Command['cmdParams']['count']) ? $Command['cmdParams']['count'] : "01";
                            if (!isset($Command['cmdParams']['group'])) {
                                cmdLog('error', "  Missing 'group' for cmd 1000-${cmdId} to CLIENT");
                                return;
                            }
                            $group = $Command['cmdParams']['group'];
                            cmdLog('debug', "  cmd-1000-41 to client: total=${total}, startIdx=${startIdx}, count=${count}, group=${group}");
                            $data2 = $zclHeader.$total.$startIdx.$count.$group.'00';
                        } else {
                            cmdLog('debug', "  ERROR: Unsupported cluster 1000 command ${cmdId} to CLIENT");
                            return;
                        }
                    }

                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                }

                // ZCL cluster EF00 specific: e.g. Curtains motor Tuya https://github.com/KiwiHC16/Abeille/issues/2304
                else if ($cmdName == 'cmd-EF00') {
                    $required = ['addr', 'ep', 'cmd', 'param']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd = "0530";

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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
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
                    $cmdTuya        = $Command['cmdParams']['cmd'];
                    if ($cmdTuya==GotoLevel) $type = DataType_VALUE; else $type = DataType_ENUM;
                    $function         = "00";
                    if ($type==DataType_VALUE) $len = "04"; else $len="01";
                    if ($type==DataType_VALUE) $param = sprintf("%08s", dechex($Command['param'])); else $param = $Command['param'];  // Up=00, Stop: 01, Down: 02

                    $data2 = $fcf.$sqn.$cmdId.$status.$counterTuya.$cmdTuya.$type.$function.$len.$param;
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(priorityUserCmd, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End cmdName == 'cmd-EF00'

                // Tuya private cluster EF00
                // TODO: Should be replaced by 'cmd-Private'
                // Mandatory params: 'addr', 'ep', 'cmd', and 'data'
                //      cmd/00: setBool/setValue/setValueMult/setValueDiv/setPercent1000
                //      cmd/25: internetStatus
                // Optional params: 'dpId', 'tuyaSqn' (4B hex string)
                else if ($cmdName == 'cmd-tuyaEF00') {
                    $required = ['addr', 'ep', 'cmd', 'data']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd      = "0530";

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = 'EF00';
                    $secMode    = "02";
                    $radius     = "1E";

                    // Tuya fields
                    // Command sent to device and its format fully depends on device himself.
                    if ($Command['cmdParams']['cmd'] == 'internetStatus') {
                        $hParams = array(
                            'clustSpecific' => true,
                            'manufCode' => $Command['cmdParams']['manufCode'],
                            'cmdId' => '25' // Response to internet status request
                        );
                        $zclHeader = $this->genZclHeader($hParams);

                        $tSqn       = isset($Command['cmdParams']['tuyaSqn']) ? $Command['cmdParams']['tuyaSqn'] : tuyaGenSqn(); // Tuya transaction ID
                        $tData      = $Command['cmdParams']['data']; // Supposed to be 00 (NOT connected), 01 (connected) or 02 (timeout)
                        $tLen       = sprintf("%04X", strlen($tData) / 2);

                        cmdLog2('debug', $addr, '  internetStatus: tSqn='.$tSqn.', tLen='.$tLen.', tData='.$tData);
                        $data2 = $zclHeader.$tSqn.$tLen.$tData;
                    } else {
                        // ZCL header
                        $fcf        = "11"; // Frame Control Field
                        $sqn        = $this->genSqn();
                        $cmdId      = "00"; // TY_DATA_REQUEST, 0x00, The gateway sends data to the Zigbee module.
                        // cmdLog2('debug', $addr, '  BEN: Command : '.json_encode($Command) );
                        $dp = tuyaCmd2Dp($Command);
                        // cmdLog2('debug', $addr, '  BEN: dp : '.json_encode($dp) );
                        if ($dp === false) {
                            return;
                        }
                        $dpType = $dp['type'];
                        $dpData = $dp['data'];
                        $dpLen = strlen($dpData) / 2;
                        if (($dpType == "02") && ($dpLen > 4)) {
                            cmdLog2('error', $addr, '  Wrong dpData size (max=4B for type 02)');
                            return;
                        }

                        $tSqn = isset($Command['cmdParams']['tuyaSqn']) ? $Command['cmdParams']['tuyaSqn'] : tuyaGenSqn(); // Tuya transaction ID
                        $dpId = $dp['id'];
                        $dpLen = sprintf("%04X", $dpLen);

                        // cmdLog2('debug', $addr, '  BEN: '.$Command['cmdParams']['cmd'].': tSqn='.$tSqn.', dpId='.$dpId.', dpType='.$dpType.', dpData='.$dpData);
                        $data2 = $fcf.$sqn.$cmdId.$tSqn.$dpId.$dpType.$dpLen.$dpData;
                    }
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End $cmdName == 'cmd-tuyaEF00'

                // Generic way to support private cluster/cmd
                // Mandatory params: 'addr', 'ep', 'fct'
                // Optional: anything. The command is passed to function as it is.
                else if ($cmdName == 'cmd-Private') {
                    $required = ['addr', 'ep', 'fct']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $addr       = $Command['cmdParams']['addr'];
                    $ep         = $Command['cmdParams']['ep'];
                    $fctName    = $Command['cmdParams']['fct'];
                    if (function_exists($fctName)) {
                        $fctName($dest, $addr, $ep, $Command);
                    } else if (method_exists($this, $fctName)) {
                        $this->$fctName($dest, $addr, $ep, $Command);
                    } else {
                        cmdLog2('error', $addr, "  Commande privée: Fonction '${fctName}' inconnue");
                    }
                    return;
                } // End 'cmd-Private'

                // Legrand private cluster FC41.
                // TODO: Should be replaced by 'cmd-Private'
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
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    if ($Command['cmdParams']['ep']>1 ) { $dstEp = $Command['cmdParams']['ep']; } else { $dstEp = "01"; } // $dstEp; // "01";
                    $profId     = "0104";
                    $clustId    = "FC41";
                    $secMode    = "02"; // ???
                    $radius     = "30";

                    $fcf        = "15";   // APS Control Field, see doc for details
                    $manufId    = "2110"; // Reversed 1021
                    $sqn        = $this->genSqn();
                    $command    = "00";

                    // $data = "00"; // 00 = Off, 02 = Auto, 03 = On.
                    $data = $Command['cmdParams']['mode'];
                    $data2 = $fcf.$manufId.$sqn.$command.$data;
                    $dataLength = sprintf("%02X", strlen($data2) / 2);
                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLength;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $cmd, $data, $addr, $addrMode);
                    return;
                } // End 'commandLegrand'

                // Generic cluster specific command
                // Mandatory params: 'addr', 'ep', 'clustId' (2B hex), 'cmd' (1B hex), and 'data' (hex)
                // Optional params: 'manufCode'
                else if ($cmdName == 'cmd-Generic') {
                    $required = ['addr', 'ep', 'clustId', 'cmd', 'data']; // Mandatory infos
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $zgCmd      = "0530";

                    $addrMode   = "02";
                    $addr       = $Command['cmdParams']['addr'];
                    $srcEp      = "01";
                    $dstEp      = $Command['cmdParams']['ep'];
                    $profId     = "0104";
                    $clustId    = $Command['cmdParams']['clustId'];
                    $secMode    = "02";
                    $radius     = "1E";

                    $hParams = array(
                        'clustSpecific' => true,
                        'manufCode' => isset($Command['cmdParams']['manufCode']) ? $Command['cmdParams']['manufCode'] : '',
                        'cmdId' => $Command['cmdParams']['cmd']
                    );
                    $zclHeader = $this->genZclHeader($hParams);
                    $data = $Command['cmdParams']['data'];

                    cmdLog('debug', "  genericCmd: ep=${dstEp}, clustId=${clustId}, zclHeader=${zclHeader}, data=${data}");
                    $data2 = $zclHeader.$data;
                    $dataLen2 = sprintf("%02X", strlen($data2) / 2);

                    $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
                    $data = $data1.$data2;

                    $this->addCmdToQueue2(PRIO_NORM, $dest, $zgCmd, $data, $addr, $addrMode);
                    return;
                } // End 'cmd-Generic'

                // else {
                //     cmdLog('debug', "  ERROR: Unexpected command '".$cmdName."'");
                //     return;
                // }
            }

            cmdLog('error', "  Commande inattendue: '".json_encode($Command)."'");
        }
    }
?>
