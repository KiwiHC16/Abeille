<?php
    // Tuya specific commands (to device) functions.
    // Included by 'AbeilleCmd.php'

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
        // for Saswell
        case "setOpenClose":
            $dpId = (isset($abCmd['dpId']) ? $abCmd['dpId']: "01");
            $dpType = "01";
            $dpData = sprintf("%02X", $data);
            break;
        default:
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

?>
