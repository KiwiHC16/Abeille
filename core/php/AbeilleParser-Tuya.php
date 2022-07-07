
<?php
    // Tuya specific parser functions
    // Included by 'AbeilleParser.php'

    // Decode cluster 0006 received cmd FD
    // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
    // Tuya 1,2,3,4 buttons switch
    function tuyaDecode0006CmdFD($ep, $msg) {
        $attributesN = [];

        $value = substr($msg, 0, 2);
        if ($value == "00")
            $click = 'single';
        else if ($value == "01")
            $click = 'double';
        else if ($value == "02")
            $click = 'long';
        else {
            parserLog('debug',  '  Tuya 0006-FD specific command'
                .', value='.$value.' => UNSUPPORTED', "8002");
            return $attributesN;
        }

        parserLog('debug',  '  Tuya 0006-FD specific command'
                        .', value='.$value.' => click='.$click, "8002");

        // Generating an event thru '#EP#-click' Jeedom cmd (ex: '01-click' = 'single')
        $attr = array(
            'name' => $ep.'-click',
            'value' => $click,
        );
        $attributesN[] = $attr;

        // Legacy code to be removed at some point
        // TODO: Replace commands '0006-#EP#-0000' to '#EP#-click' in JSON
        $attr = array(
            'name' => '0006-'.$ep.'-0000',
            'value' => $value,
        );
        $attributesN[] = $attr;

        return $attributesN;
    }

    // Cluster EF00
    // See: https://github.com/zigbeefordomoticz/wiki/blob/master/en-eng/Technical/Tuya-0xEF00.md
    // See: https://www.zigbee2mqtt.io/advanced/support-new-devices/02_support_new_tuya_devices.html#_2-adding-your-device
    // See: https://developer.tuya.com/en/docs/iot-device-dev/tuya-zigbee-universal-docking-access-standard?id=K9ik6zvofpzql

    function tuyaGetDp($msg) {
        $tDpId = substr($msg, 0, 2);
        $tDataType = substr($msg, 2, 2);
        $tFunc = substr($msg, 4, 2); // What for ?
        $tLen = substr($msg, 6, 2);
        $tData = substr($msg, 8, hexdec($tLen) * 2);
        $m = "Dp=".$tDpId.", DpType=".$tDataType.", Func=".$tFunc.", DpLen=".$tLen;

        $dp = array(
            'id' => $tDpId,
            'type' => $tDataType,
            'dataLen' => $tLen,
            'data' => $tData,
            'm' => $m
        );
        return $dp;
    }

    // Types reminder
    // Type	    TypeId  LengthInBytes	Description
    // raw	    0x00	N	            Corresponds to raw datapoint (module pass-through)
    // bool	    0x01	1	            value range: 0x00/0x01
    // value	0x02	4	            corresponds to int type, big end representation
    // string	0x03	N	            corresponds to a specific string
    // enum	    0x04	1	            Enumeration type, range 0-255
    // bitmap	0x05	1/2/4	        Large end representation for lengths greater than 1 byte

    // Receive a datapoint, map it to a specific function an decode it.
    // Mapping is defined "per device" directly in its model (tuyaEF00/fromDevice).
    function tuyaDecodeDp($ep, $dp, $mapping, &$toMon) {
        $dpId = $dp['id'];
        if (!isset($mapping[$dpId])) {
            parserLog("debug", "  ".$dp['m'].": Unrecognized DP (data=".$dp['data'].")", "8002");
            return false;
        }

        /* New syntax
            "tuyaEF00": {
                "fromDevice": {
                    "01": { "function": "rcvValueDiv", "info": "0006-01-0000", "div": 1 },
                    "02": { "function": "rcvValue", "info": "0008-01-0000" },
                }
            }
           Old syntax
            "tuyaEF00": {
                "fromDevice": {
                    "01": "rcvOnOff",
                    "02": "rcvLevel"
                }
            }
         */


        $info = "undefined-info";
        $div = 1; // For optional value division
        $mult = 1; // For optional value multiplication
        if (isset($mapping[$dpId]['function'])) {
            $func = $mapping[$dpId]['function'];
            if (isset($mapping[$dpId]['info']))
                $info = $mapping[$dpId]['info'];
            if (isset($mapping[$dpId]['div']))
                $div = $mapping[$dpId]['div'];
            if (isset($mapping[$dpId]['mult']))
                $mult = $mapping[$dpId]['mult'];
        } else
            $func = $mapping[$dpId];

        // TV02 thermostat (TS0601, _TZE200_hue3yfsn) $mapping exemple: array(
        //     "02" => "rcvThermostat-Mode",
        //     "08" => "rcvThermostat-WindowDetectionStatus",
        //     "10" => "rcvThermostat-Setpoint",
        //     "18" => "rcvThermostat-LocalTemp",
        //     "23" => "rcvBattery-Percent"
        // );
        switch ($func) {
        case "rcvThermostat-Mode": // Mode (Checked on TV02)
            // 00=Auto, 01=Manual, 03=Holliday
            $mode = $dp['data'];
            if ($mode == "00")
                $mode = "auto";
            else if ($mode == "01")
                $mode = "manual";
            else if ($mode == "03")
                $mode = "holliday";
            else
                $mode = "?";
            $logMsg = "  ".$dp['m']." => Mode = ".$dp['data']."/".$mode;
            parserLog("debug", $logMsg, "8002");
            $toMon[] = $logMsg; // For monitor
            $attributeN = array(
                'name' => $ep.'-mode',
                'value' => $mode,
            );
            break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValue", "info": "01-windowDetectionStatus" }
        // case "rcvThermostat-WindowDetectionStatus": // Window detection status (Checked on TV02)
        //     parserLog("debug", "  ".$dp['m']." => Window detection status = ".$dp['data'], "8002");
        //     $attributeN = array(
        //         'name' => $ep.'-windowDetectionStatus',
        //         'value' => hexdec($dp['data']),
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValueDiv", "info": "01-setPoint", "div": 10 }
        // case "rcvThermostat-Setpoint": // Set point (Checked on TV02)
        //     $setPoint = hexdec($dp['data']) / 10;
        //     parserLog("debug", "  ".$dp['m']." => Set point = ".$setPoint." C", "8002");
        //     $attributeN = array(
        //         'name' => $ep.'-setpoint',
        //         'value' => $setPoint,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValueMult", "info": "0402-01-0000", "mult": 10 }
        // case "rcvThermostat-LocalTemp": // Received local temp (Checked on TV02)
        //     $temp = hexdec($dp['data']) / 10;
        //     $tempReport = $temp * 100; // Divided by 100 due to model
        //     parserLog("debug", "  ".$dp['m']." => Temp = ".$temp." C", "8002");
        //     $attributeN = array(
        //         'name' => '0402-'.$ep.'-0000',
        //         'value' => $tempReport,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValue", "info": "0001-01-0021" }
        // case "rcvBattery-Percent": // Received battery percent (Checked on TV02)
        //     $val = hexdec($dp['data']);
        //     parserLog("debug", "  ".$dp['m']." => Battery-percent = ".$val." %", "8002");
        //     $attributeN = array(
        //         'name' => '0001-'.$ep.'-0021',
        //         'value' => $val,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValue", "info": "0006-01-0000" }
        // case "rcvOnline-Status": // WORK ONGOING !!! Received ON/OFF status (Checked on TV02)
        //     $val = hexdec($dp['data']);
        //     $st = ($val == 1) ? "ON" : "OFF";
        //     parserLog("debug", "  ".$dp['m']." => On/Off=".$val."/".$st, "8002");
        //     $attributeN = array(
        //         'name' => '0006-'.$ep.'-0000',
        //         'value' => $val,
        //     );
        //     break;
        // Smart Air Box (TS0601, _TZE200_yvx5lh6k) $mapping exemple: array(
        //     "02" => "rcvSmartAir-CO2",
        //     "12" => "rcvSmartAir-Temperature",
        //     "13" => "rcvSmartAir-Humidity",
        //     "15" => "rcvSmartAir-VOC",
        //     "16" => "rcvSmartAir-CH20",
        // );
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValue", "info": "01-CO2_ppm" }
        // case "rcvSmartAir-CO2": // CO2 (verified on Smart Air sensor)
        //     $val = hexdec($dp['data']);
        //     parserLog("debug", "  ".$dp['m']." => CO2=".$val." ppm", "8002");
        //     $attributeN = array(
        //         'name' => $ep.'-CO2_ppm',
        //         'value' => $val,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValueMult", "info": "0402-01-0000", "mult": 10 }
        // case "rcvSmartAir-Temperature": // Temp (verified on Smart Air sensor)
        //     $val = hexdec($dp['data']);
        //     $vReport = $val * 10; // Divided by 100 due to model
        //     $val = $val / 10;
        //     parserLog("debug", "  ".$dp['m']." => Temp=".$val." C", "8002");
        //     $attributeN = array(
        //         'name' => '0402-'.$ep.'-0000',
        //         'value' => $vReport,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValueMult", "info": "0405-01-0000", "mult": 10 }
        // case "rcvSmartAir-Humidity": // Humidity (verified on Smart Air sensor)
        //     $val = hexdec($dp['data']);
        //     $vReport = $val * 10; // Divided by 100 due to model
        //     $val = $val / 10;
        //     parserLog("debug", "  ".$dp['m']." => Humidity=".$val." %", "8002");
        //     $attributeN = array(
        //         'name' => '0405-'.$ep.'-0000',
        //         'value' => $vReport,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValueDiv", "info": "01-VOC_ppm", "div": 10 }
        // case "rcvSmartAir-VOC": // VOC (verified on Smart Air sensor)
        //     $val = hexdec($dp['data']) / 10;
        //     parserLog("debug", "  ".$dp['m']." => VOC=".$val." ppm", "8002");
        //     $attributeN = array(
        //         'name' => $ep.'-VOC_ppm',
        //         'value' => $val,
        //     );
        //     break;
        // Obsolete. Use generic function instead
        // "02": { "function": "rcvValue", "info": "01-CH20_ppm" }
        // case "rcvSmartAir-CH20": // Formaldéhyde µg/m3 (Méthanal / CH2O_ppm) (verified on Smart Air sensor)
        //     $val = hexdec($dp['data']);
        //     parserLog("debug", "  ".$dp['m']." => CH2O=".$val." ppm", "8002");
        //     $attributeN = array(
        //         'name' => $ep.'-CH20_ppm',
        //         'value' => $val,
        //     );
        //     break;
        // Obsolete. Replaced by generic function 'rcvValue0Is1'
        // case "rcvSmokeAlarm": // Smoke status: value to bool
        //     $val = hexdec($dp['data']);
        //     if ($val == 0)
        //         $val = 1;
        //     else
        //         $val = 0;
        //     parserLog("debug", "  ".$dp['m']." => Smoke alarm=".$val, "8002");
        //     $attributeN = array(
        //         'name' => $ep.'-smokeAlarm',
        //         'value' => $val,
        //     );
        //     break;

        // Solenoid valve Saswell SAS980SWT
        case "rcvValve-Status":
            $val = hexdec($dp['data']) % 2;
            $st = ($val == 1) ? "ON" : "OFF";
            parserLog("debug", "  ".$dp['m']." => On/Off=".$val."/".$st, "8002");
            $attributeN = array(
                'name' => '0006-'.$ep.'-0000',
                'value' => $val,
            );
            break;
        // case "rcvValve-TimeLeft":
        //     $val = hexdec($dp['data']);
        //     parserLog("debug", "  ".$dp['m']." => Time left=".$val, "8002");
        //     $attributeN = array(
        //         'name' => $ep."-timeLeft",
        //         'value' => $val,
        //     );
        //     break;
        // case "rcvValve-LastOpenDuration":
        //     $val = hexdec($dp['data']);
        //     parserLog("debug", "  ".$dp['m']." => Last open duration=".$val, "8002");
        //     $attributeN = array(
        //         'name' => $ep."-lastOpenDuration",
        //         'value' => $val,
        //     );
        //     break;
        // case "rcvValve-MeasuredValue":
        //     $val = hexdec($dp['data']);
        //     parserLog("debug", "  ".$dp['m']." => Measured value=".$val, "8002");
        //     $attributeN = array(
        //         'name' => $ep."-measuredValue",
        //         'value' => $val,
        //     );
        //     break;

        // Generic functions

        // Use exemple:  "02": { "function": "rcvValue", "info": "0008-01-0000" },
        case "rcvValue": // Value sent as Jeedom info
            $val = hexdec($dp['data']);
            $logMsg = "  ".$dp['m']." => Info=".$info.", Val=".$val;
            $attributeN = array(
                'name' => $info,
                'value' => $val,
            );
            break;
        // Use exemple:  "02": { "function": "rcvValueDiv", "info": "01-setPoint", "dic": 10 },
        case "rcvValueDiv": // Divided value sent as Jeedom info
            $val = hexdec($dp['data']);
            $val = $val / $div;
            $logMsg = "  ".$dp['m']." => Info=".$info.", Div=".$div." => Val=".$val;
            $attributeN = array(
                'name' => $info,
                'value' => $val,
            );
            break;
        // "02": { "function": "rcvValueMult", "info": "0402-01-0000", "mult": 10 }
        case "rcvValueMult": // Multiplied value sent as Jeedom info
            $val = hexdec($dp['data']);
            $val = $val * $mult;
            $logMsg = "  ".$dp['m']." => Info=".$info.", Mult=".$mult." => Val=".$val;
            $attributeN = array(
                'name' => $info,
                'value' => $val,
            );
            break;
        case "rcvValue0Is1": // val==0 => 1, then sent as Jeedom info
            $val = hexdec($dp['data']);
            if ($val == 0)
                $val = 1;
            else
                $val = 0;
            $logMsg = "  ".$dp['m']." => Info=".$info.", Val=".$val;
            $attributeN = array(
                'name' => $info,
                'value' => $val,
            );
            break;

        default:
            parserLog("error", "  Unknown Tuya function '".$func."' for dpId=".$dpId);
            $attributeN = false;
            break;
        }

        if (isset($logMsg)) {
            parserLog("debug", $logMsg, "8002");
            $toMon[] = $logMsg;
        }
        return $attributeN;
    }

    // Decode cluster EF00 received cmd 24 => Time synchronization
    function tuyaDecodeEF00Cmd24($ep, $msg) {
        // def send_timesynchronisation(self, NwkId, srcEp, ClusterID, dstNWKID, dstEP, serial_number):

        //     # Request: cmd: 0x24  Data: 0x0008
        //     # 0008 600d8029 600d8e39
        //     # Request: cmd: 0x24 Data: 0x0053
        //     # 0053 60e9ba1f  60e9d63f
        //     if NwkId not in self.ListOfDevices:
        //         return
        //     sqn = get_and_inc_SQN(self, NwkId)

        //     field1 = "0d"
        //     field2 = "80"
        //     field3 = "29"

        //     EPOCTime = datetime(1970, 1, 1)
        //     now = datetime.utcnow()
        //     UTCTime_in_sec = int((now - EPOCTime).total_seconds())
        //     LOCALtime_in_sec = int((utc_to_local(now) - EPOCTime).total_seconds())

        //     utctime = "%08x" % UTCTime_in_sec
        //     localtime = "%08x" % LOCALtime_in_sec
        //     self.log.logging(
        //         "Tuya",
        //         "Debug",
        //         "send_timesynchronisation - %s/%s UTC: %s Local: %s" % (NwkId, srcEp, UTCTime_in_sec, LOCALtime_in_sec),
        //     )

        //     payload = "11" + sqn + "24" + serial_number + utctime + localtime
        //     raw_APS_request(self, NwkId, srcEp, "ef00", "0104", payload, zigate_ep=ZIGATE_EP, ackIsDisabled=False)
        //     self.log.logging("Tuya", "Debug", "send_timesynchronisation - %s/%s " % (NwkId, srcEp))
    }

    function tuyaDecodeEF00Cmd($net, $addr, $ep, $cmdId, $msg, &$toMon) {
        $tCmds = array(
            "00" => array("name" => "TY_DATA_REQUEST", "desc" => "Gateway-side data request"),
            "01" => array("name" => "TY_DATA_RESPONE", "desc" => "Reply to MCU-side data request"),
            "02" => array("name" => "TY_DATA_REPORT", "desc" => "MCU-side data active upload (bidirectional)"),
            "03" => array("name" => "TY_DATA_QUERY", "desc" => "GW send, trigger MCU side to report all current information, no zcl payload"),
            "10" => array("name" => "TUYA_MCU_VERSION_REQ", "desc" => "Gw->Zigbee gateway query MCU version"),
            "11" => array("name" => "TUYA_MCU_VERSION_RSP", "desc" => "Zigbee->Gw MCU return version or actively report version"),
            "12" => array("name" => "TUYA_MCU_OTA_NOTIFY", "desc" => "Gw->Zigbee gateway notifies MCU of upgrade"),
            "13" => array("name" => "TUYA_OTA_BLOCK_DATA_REQ", "desc" => "Zigbee->Gw requests an upgrade package for the MCU"),
            "14" => array("name" => "TUYA_OTA_BLOCK_DATA_RSP", "desc" => "Gw->Zigbee gateway returns the requested upgrade package"),
            "15" => array("name" => "TUYA_MCU_OTA_RESULT", "desc" => "Zigbee->Gw returns the upgrade result for the mcu"),
            "24" => array("name" => "TUYA_MCU_SYNC_TIME", "desc" => "Time synchronization (bidirectional)"),
        );
        if (($cmdId != "01") && ($cmdId != "02")) {
            if (isset($tCmds[$cmdId]))
                $cmdName = $tCmds[$cmdId]['name'];
            else
                $cmdName = $cmdId."/?";
            parserLog("debug", "  Unsupported Tuya cmd ".$cmdName." => ignored", "8002");
            return [];
        }

        $eq = &getDevice($net, $addr); // By ref
        // parserLog('debug', 'eq='.json_encode($eq));
        if (!isset($eq['tuyaEF00']) || !isset($eq['tuyaEF00']['fromDevice'])) {
            parserLog('debug', "  No defined Tuya mapping => ignoring (msg=".$msg.")");
            return [];
        }
        $mapping = $eq['tuyaEF00']['fromDevice'];
        // parserLog('debug', '  Tuya mapping='.json_encode($mapping));

        $attributesN = [];
        if (($cmdId == "01") || ($cmdId == "02")) {
            $tSqn = substr($msg, 0, 4); // uint16
            $msg = substr($msg, 4); // Skip tSqn
            parserLog("debug", "  Tuya EF00 specific cmd ".$cmdId." (tSQN=".$tSqn.")", "8002");
            while (strlen($msg) != 0) {
                $dp = tuyaGetDp($msg);

                $a = tuyaDecodeDp($ep, $dp, $mapping, $toMon);
                if ($a !== false)
                    $attributesN[] = $a;
                else { // Unknown DP
                    if (!isset($eq['tuyaEF00']['unknown']) || !isset($eq['tuyaEF00'][$dp['id']]))
                        $eq['tuyaEF00']['unknown'][$dp['id']] = $dp;
                }

                // Move to next DP
                $s = 8 + (hexdec($dp['dataLen']) * 2);
                $msg = substr($msg, $s);
            }
        }
// TODO: How to store unknown DP in discovery.json ?
// Should not store "on the fly" as file accesses are time consuming

        return $attributesN;
    }
?>
