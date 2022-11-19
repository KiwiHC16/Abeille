<?php
    // Tuya specific commands (to device) functions.
    // Included by 'AbeilleCmd.php'

    $tuyaTransId = 0; // Transaction ID: 0 to 255

    function tuyaCheckRequiredParams($required, $abCmd) {
        $cmd = $abCmd['cmd'];
        foreach ($required as $req) {
            if (!isset($abCmd[$req])) {
                cmdLog('debug', "    ERROR: Undefined '".$req."' for '".$cmd."'");
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
            $dpType = "01";
            $dpData = sprintf("%02X", $data);
            break;

        // Generic commands

        // Send boolean data
        case "setBool":
            $required = ['dpId', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;
            $dpId = $abCmd['dpId'];
            $dpType = "01"; // Bool
            $dpData = sprintf("%02X", $abCmd['data']);
            break;
        case "setValue":
            $required = ['dpId', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;
            $dpId = $abCmd['dpId'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $abCmd['data']);
            break;
        case "setValueMult":
            $required = ['dpId', 'mult', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;
            $dpId = $abCmd['dpId'];
            $mult = $abCmd['mult'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $abCmd['data'] * $mult);
            break;
        case "setValueDiv":
            $required = ['dpId', 'div', 'data'];
            if (!tuyaCheckRequiredParams($required, $abCmd))
                return false;
            $dpId = $abCmd['dpId'];
            $div = $abCmd['div'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $abCmd['data'] / $div);
            break;
        // Send percent data in 0-1000 range with input in 0-100 range
        case "setPercent1000":
            if (!isset($abCmd['dpId'])) {
                cmdLog('debug', "    ERROR: Undefined dpId for '".$cmd."'");
                return false;
            }
            $dpId = $abCmd['dpId'];
            $dpType = "02"; // Value
            $dpData = sprintf("%08X", $data * 10);
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

    // Returns Tuya specific transaction ID
    function tuyaGetTransId() {
        global $tuyaTransId;
        $tuyaTransId++;
        if ($tuyaTransId > 255)
            $tuyaTransId = 0;
        $tId = sprintf("%02X", $tuyaTransId);
        return $tId;
    }
?>
