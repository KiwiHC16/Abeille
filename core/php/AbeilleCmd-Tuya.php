<?php
    // Tuya specific commands (to device).
    // Included by 'AbeilleCmd.php'

    $tuyaTransId = 0; // Transaction ID: 0 to 255

    function tuyaGenSqn() {
        global $tuyaTransId;
        $tuyaTransId++;
        if ($tuyaTransId > 255)
            $tuyaTransId = 0;
        $tId = sprintf("%04X", $tuyaTransId);
        return $tId;
    }

    function tuyaCheckRequiredParams($required, $abCmd) {
        $cmd = $abCmd['cmd'];
        foreach ($required as $req) {
            if (!isset($abCmd[$req])) {
                cmdLog('error', "    ERROR: Undefined '".$req."' for '".$cmd."'");
                return false;
            }
        }
        return true;
    }

    // Types reminder
    // Type	    TypeId  LengthInBytes	Description
    // raw	    0x00	N	            Corresponds to raw datapoint (module pass-through)
    // bool	    0x01	1	            value range: 0x00/0x01
    // value	0x02	4	            corresponds to int type, big end representation
    // string	0x03	N	            corresponds to a specific string
    // enum	    0x04	1	            Enumeration type, range 0-255
    // bitmap	0x05	1/2/4	        Large end representation for lengths greater than 1 byte

    // Tuya generic command (ex: 'setSetpoint') to data point mapping.
    // Note: There is a default dpId per cmd. Can be customized from device model ("params": "dpId=XX")
    // Note: For backward compatibility, DO NOT modify these commands. Create a new one if for ex dpType is different.
    function tuyaCmd2Dp($abCmd) {
        $cmd = $abCmd['cmd'];
        $data = $abCmd['data'];
        switch ($cmd) {

        //
        // Generic commands
        //

        case "setBool":
        case "setEnum":
            $required = ['dpId', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;

            $dpId = $abCmd['dpId'];
            if ($cmd == 'setBool')
                $dpType = "01"; // 1B, Bool
            else
                $dpType = "04"; // 1B, Enum
            $dpData = sprintf("%02X", $abCmd['data']);
            break;
        case "setValue":
            $required = ['dpId', 'data']; // mult & div are optional
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;

            $dpId = $abCmd['dpId'];
            $mult = isset($abCmd['mult']) ? $abCmd['mult'] : 1;
            $div = isset($abCmd['div']) ? $abCmd['div'] : 1;
            $dpType = "02"; // 4B, Value
            $dpData = sprintf("%08X", $abCmd['data'] * $mult / $div);
            break;

        //
        // The following are OBSOLETE commands
        //

        case "setSetpoint": // Validated on TV02 thermostat
            $dpId = (isset($abCmd['dpId']) ? $abCmd['dpId']: "10");
            $dpType = "02"; // 4B value
            $dpData = sprintf("%08X", $data * 10);
            break;
        case "setThermostat-Mode": // Validated on TV02 thermostat
            $dpId = (isset($abCmd['dpId']) ? $abCmd['dpId']: "02");
            $dpType = "04"; // 1B, Enum
            $dpData = sprintf("%02X", $data);
            break;
        case "setOnOff": // WORK ONGOING !! Validated on TV02 thermostat
            $dpId = (isset($abCmd['dpId']) ? $abCmd['dpId']: "73");
            $dpType = "01"; // 1B, Bool
            $dpData = sprintf("%02X", $data);
            break;
        // for Saswell irrigation valve
        case "setOpenClose":
            $dpId = (isset($abCmd['dpId']) ? $abCmd['dpId']: "01");
            $dpType = "01"; // 1B, Bool
            $dpData = sprintf("%02X", $data);
            break;
        case "setValueMult": // OBSOLETE: Use setValue with 'mult' set
            $required = ['dpId', 'mult', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;

            $dpId = $abCmd['dpId'];
            $mult = $abCmd['mult'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $abCmd['data'] * $mult);
            break;
        case "setValueDiv": // OBSOLETE: Use setValue with 'div' set
            $required = ['dpId', 'div', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;

            $dpId = $abCmd['dpId'];
            $div = $abCmd['div'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $abCmd['data'] / $div);
            break;
        // Send percent data in 0-1000 range with input in 0-100 range
        case "setPercent1000": // OBSOLETE: Use setValue with 'mult'=10
            $required = ['dpId', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;

            $dpId = $abCmd['dpId'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $abCmd['data'] * 10);
            break;

        default:
            cmdLog('debug', "    ERROR: Unsupported Tuya cmd '".$cmd."'");
            return false;
        }

        $dp = array(
            'id' => $dpId,
            'type' => $dpType,
            // 'dataLen' => $dpLen,
            'data' => $dpData
        );
        return $dp;
    }

    // Returns Tuya specific transaction ID (2 bytes)
    function tuyaZosungSeq() {
        global $tuyaSeq;
        $tuyaSeq++;
        // if ($tuyaSeq > 255)
        //     $tuyaSeq = 0;
        if ($tuyaSeq > 9) // For unknown reason, seq above 9 breaks the transfer
            $tuyaSeq = 0;
        $seq = sprintf("%04X", $tuyaSeq);
        return $seq;
    }

    require_once __DIR__.'/../class/AbeilleCmdProcess.class.php';

    // Compute CRC for given message
    // Use cases: ED00 cluster support (Moes universal remote)
    function tuyaZosungCrc($message) {
        $crc = 0;
        $len = strlen($message) / 2;
        for($i = 0; $i < $len; $i++) {
            $c = substr($message, $i * 2, 2);
            // cmdLog('debug', "  c=${c}, crc=".dechex($crc));
            $crc += hexdec($c);
            $crc %= 0x100;
        }
        // cmdLog('debug', "  crc=".dechex($crc));
        return sprintf("%02X", $crc);
    }

    // Use cases: ED00 cluster support (Moes universal remote)
    // Cmd 00: 'data'=IR code to send (base64 URL encoded format)
    // Cmd 03: 'data'=JSON encoded {'seq' => hex string, 'pos' => binary, 'maxLen' => binary}
    function tuyaZosung($net, $addr, $ep, $command) {
        $cmd = $command['cmd'];
        $data = $command['data'];
        cmdLog2('debug', $addr, "  tuyaZosung(Net=${net}, Addr=${addr}, EP=${ep}, Cmd=${cmd})");

        if ($cmd == '00') { // Send IR code
            // Data is base64 URL encoded
            $dataB64 = AbeilleTools::base64url2base64($data);
            cmdLog2('debug', $addr, "  Send IR code '${dataB64}'");
            $irMsg = array(
                'key_num' => 1,
                'delay' => 300,
                'key1' => array (
                    'num' => 1,
                    'freq' => 38000,
                    'type' => 1,
                    'key_code' => $dataB64,
                ),
            );
            $irMsgJson = json_encode($irMsg, JSON_UNESCAPED_SLASHES);
            cmdLog2('debug', $addr, '  TEMPORARY: irMsgJson='.$irMsgJson);
            $message = bin2hex($irMsgJson);
            cmdLog2('debug', $addr, '  TEMPORARY: message='.$message);
            $seq = tuyaZosungSeq(); // For unknown reason, seq above 9 breaks the transfer

            // Saving message to send
            if (!isset($GLOBALS['zosung']))
                $GLOBALS['zosung'] = [];
            $GLOBALS['zosung'][$seq] = array(
                'message' => $message
            );

            // Cmd ED00-00 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'length', type: DataType.uint32},
            // {name: 'unk1', type: DataType.uint32},
            // {name: 'unk2', type: DataType.uint16},
            // {name: 'unk3', type: DataType.uint8},
            // {name: 'cmd', type: DataType.uint8},
            // {name: 'unk4', type: DataType.uint16},

            // $seq = tuyaGenSqn(); // Already generated
            $length = sprintf("%08X", strlen($message) / 2);
            $unk1 = '00000000';
            $unk2 = 'e004';
            $unk3 = '01';
            $cmd = '02';
            $unk4 = '0000';
            cmdLog2('debug', $addr, "  Cmd ED00-00: Seq=${seq}, Len=${length}");

            $seq = AbeilleTools::reverseHex($seq);
            $len = AbeilleTools::reverseHex($length);
            $unk1 = AbeilleTools::reverseHex($unk1);
            $unk2 = AbeilleTools::reverseHex($unk2);
            $unk4 = AbeilleTools::reverseHex($unk4);
            $data = $seq.$len.$unk1.$unk2.$unk3.$cmd.$unk4;

            $header = array(
                'net' => $net,
                'addr' => $addr,
                'ep' => $ep,
                'clustId' => 'ED00',
                'clustSpecific' => true, // Cluster specific frame
                'cmd' => '00'
            );
            AbeilleCmdProcess::sendRawMessage($header, $data);
        } else if ($cmd == '03') {
            $params = json_decode($data, true);
            $seq = $params['seq']; // Hex string
            $pos = $params['pos']; // Integer
            $maxLen = $params['maxLen']; // Integer
            cmdLog2('debug', $addr, "  Cmd ED00-03: Seq=${seq}, Pos=d${pos}, MaxLen=d${maxLen}");

            if (!isset($GLOBALS['zosung']) || !isset($GLOBALS['zosung'][$seq])) {
                cmdLog2('debug', $addr, "  WARNING: No message defined for seq ${seq}");
                return;
            }

            $message = $GLOBALS['zosung'][$seq]['message'];
            cmdLog2('debug', $addr, "  TEMPORARY: message=".$message);
            $msgRemain = substr($message, $pos * 2);
            cmdLog2('debug', $addr, "  TEMPORARY: msgRemain=".$msgRemain);
            $msgSize = strlen($msgRemain) / 2;
            if ($msgSize == 0) {
                cmdLog2('debug', $addr, "  WARNING: All datas already sent");
                return;
            }
            if ($msgSize > 0x32) { // Note: 0x32 taken from zigbee-herdsman-converters, zosung.ts
                $msgSize = 0x32;
                $msgPart = substr($msgRemain, 0, $msgSize * 2); // Truncate to maxLen
            } else
                $msgPart = $msgRemain;
            $msgSize = sprintf("%02X", $msgSize);
            $msgPartCrc = tuyaZosungCrc($msgPart);
            cmdLog2('debug', $addr, "  MsgSize=${msgSize}, MsgPart=${msgPart}, MsgPartCrc=${msgPartCrc}");

            // Cmd 03 reminder
            // {name: 'zero', type: DataType.uint8},
            // {name: 'seq', type: DataType.uint16},
            // {name: 'position', type: DataType.uint32},
            // {name: 'msgpart', type: DataType.octetStr},
            // {name: 'msgpartcrc', type: DataType.uint8},
            $seq = sprintf("%04X", $seq);
            $pos = sprintf("%08X", $pos);
            $seqR = AbeilleTools::reverseHex($seq);
            $posR = AbeilleTools::reverseHex($pos);
            $data = '00'.$seqR.$posR.$msgSize.$msgPart.$msgPartCrc;

            $header = array(
                'net' => $net,
                'addr' => $addr,
                'ep' => $ep,
                'clustId' => 'ED00',
                'clustSpecific' => true, // Cluster specific frame
                'cmd' => '03'
            );
            AbeilleCmdProcess::sendRawMessage($header, $data);
        }
    }
?>
