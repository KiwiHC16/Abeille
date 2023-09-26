
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
                        .', ValueHex='.$value.' => Click='.$click, "8002");

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
        $m = "Dp=".$tDpId.", Type=".$tDataType.", Func=".$tFunc.", Len=".$tLen.", ValueHex=".$tData;

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
            $m = "  ".$dp['m'].": Unrecognized DP !";
            parserLog("debug", $m, "8002");
            $toMon[] = $m;
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
            // This case should no longer be required. Obsolete !
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
            $attributeN = array(
                'name' => $ep.'-mode',
                'value' => $mode,
            );
            break;

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

        // Generic functions

        // Use exemple:  "02": { "function": "rcvValue", "info": "0008-01-0000" },
        case "rcvValue": // Value sent as Jeedom info
            $val = hexdec($dp['data']);
            $logMsg = "  ".$dp['m']." => 'rcvValue' => '".$info."'=".$val;
            $attributeN = array(
                'name' => $info,
                'value' => $val,
            );
            break;
        // Use exemple:  "02": { "function": "rcvValueDiv", "info": "01-setPoint", "dic": 10 },
        case "rcvValueDiv": // Divided value sent as Jeedom info
            $val = hexdec($dp['data']);
            $val = $val / $div;
            $logMsg = "  ".$dp['m']." => 'rcvValueDiv', Div=".$div." => '".$info."'=".$val;
            $attributeN = array(
                'name' => $info,
                'value' => $val,
            );
            break;
        // "02": { "function": "rcvValueMult", "info": "0402-01-0000", "mult": 10 }
        case "rcvValueMult": // Multiplied value sent as Jeedom info
            $val = hexdec($dp['data']);
            $val = $val * $mult;
            $logMsg = "  ".$dp['m']." => 'rcvValueMult', Mult=".$mult." => '".$info."'=".$val;
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
            $logMsg = "  ".$dp['m']." => 'rcvValue0Is1' => '".$info."'=".$val;
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
            "25" => array("name" => "TUYA_INTERNET_STATUS", "desc" => "MCU request gateway connection status"),
        );

        $attributesN = [];
        $eq = &getDevice($net, $addr); // By ref
        if (($cmdId == "01") || ($cmdId == "02")) {
            // parserLog('debug', 'eq='.json_encode($eq));
            if (!isset($eq['tuyaEF00']) || !isset($eq['tuyaEF00']['fromDevice'])) {
                parserLog('debug', "  No defined Tuya mapping => ignoring (msg=".$msg.")");
                return [];
            }
            $mapping = $eq['tuyaEF00']['fromDevice'];
            // parserLog('debug', '  Tuya mapping='.json_encode($mapping));

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
        } else if ($cmdId == "06") { // TY_DATA_SEARCH
            parserLog('debug', "  TY_DATA_SEARCH: ".$msg);
            // status = MsgPayload[6:8]  # uint8
            // transid = MsgPayload[8:10]  # uint8
            // dp = int(MsgPayload[10:12], 16)
            // datatype = int(MsgPayload[12:14], 16)
            // fn = MsgPayload[14:16]
            // len_data = MsgPayload[16:18]
            // data = MsgPayload[18:]
            $tDpId = substr($msg, 0, 2);
            $tDataType = substr($msg, 2, 2);
            $tFunc = substr($msg, 4, 2); // What for ?
            $tLen = substr($msg, 6, 2);
            $tData = substr($msg, 8, hexdec($tLen) * 2);
            $m = "Dp=".$tDpId.", Type=".$tDataType.", Func=".$tFunc.", Len=".$tLen.", ValueHex=".$tData;
            parserLog('debug', "  TY_DATA_SEARCH: ".$m);
        } else if ($cmdId == "11") { // TUYA_MCU_VERSION_RSP
            parserLog('debug', "  TUYA_MCU_VERSION_RSP: ".$msg);
        } else if ($cmdId == "25") { // TUYA_INTERNET_STATUS
            parserLog('debug', "  Internet access status request => Answering 'connected'");
            $tSqn = substr($msg, 0, 4); // uint16
            $manufCode = isset($eq['manufCode']) ? '&manufCode='.$eq['manufCode'] : '';
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-tuyaEF00", "ep=".$ep."&cmd=internetStatus".$manufCode."&tuyaSqn=".$tSqn."&data=01");
        } else {
            if (isset($tCmds[$cmdId]))
                $cmdName = $tCmds[$cmdId]['name'];
            else
                $cmdName = $cmdId."/?";
            parserLog("debug", "  Unsupported Tuya cmd ".$cmdName." => ignored", "8002");
        }

// TODO: How to store unknown DP in discovery.json ?
// Should not store "on the fly" as file accesses are time consuming

        return $attributesN;
    }

    // Use cases: E004+ED00 clusters support (Moes universal remote)
    function tuyaDecodeZosungCmd($net, $addr, $ep, $cmdId, $pl, &$toMon) {
        $attrReportN = [];
        // Assuming cluster ED00
        if ($cmdId == "00") {
            // {name: 'seq', type: DataType.uint16},
            // {name: 'length', type: DataType.uint32},
            // {name: 'unk1', type: DataType.uint32},
            // {name: 'unk2', type: DataType.uint16},
            // {name: 'unk3', type: DataType.uint8},
            // {name: 'cmd', type: DataType.uint8},
            // {name: 'unk4', type: DataType.uint16},
            $seq = substr($pl, 0, 4);
            $len = substr($pl, 4, 8);
            $unk1 = substr($pl, 12, 8);
            $unk2 = substr($pl, 20, 4);
            $unk3 = substr($pl, 24, 2);
            $cmd = substr($pl, 26, 2);
            $unk4 = substr($pl, 28, 4);

            $seqR = AbeilleTools::reverseHex($seq);
            $lenR = AbeilleTools::reverseHex($len);
            parserLog("debug", "  Tuya-Zosung cmd ED00-${cmdId}: Seq=${seqR}, Len=${lenR}, Cmd=${cmd}");

            $data = '00'.$seq.$len.$unk1.$unk2.$unk3.$cmd.$unk4;
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=01&data=${data}");
            //meta.logger.debug(`"IR-Message-Code00" response sent.`);
            //Note: This last msg could generate err 14/E_ZCL_ERR_ZBUFFER_FAIL if too big. Size reduced by using hexdec().

            // Cmd 02 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'position', type: DataType.uint32},
            // {name: 'maxlen', type: DataType.uint8},
            $data = $seq.'00000000'.'38';
            // $time = time() + 2; // 2sec later
            // msgToCmd(PRIO_NORM, "TempoCmd".$net."/".$addr."/cmd-Generic&time=${time}", "ep=".$ep."&clustId=ED00&cmd=02&data=${data}");
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=02&data=${data}");
            // meta.logger.debug(`"IR-Message-Code00" transfer started.`);

            $GLOBALS['zosung'] = [];
            $GLOBALS['zosung'][$seqR] = array(
                'expSize' => $lenR, // Expected size
                'data' => []
            );
        } else if ($cmdId == "03") {
            // Cmd 03 reminder
            // {name: 'zero', type: DataType.uint8},
            // {name: 'seq', type: DataType.uint16},
            // {name: 'position', type: DataType.uint32},
            // {name: 'msgpart', type: DataType.octetStr},
            // {name: 'msgpartcrc', type: DataType.uint8},
            $zero = substr($pl, 0, 2);
            $seq = substr($pl, 2, 4);
            $pos = substr($pl, 6, 8);
            $msgPart = substr($pl, 14, -2); // Rest is msgpart + msgpartcrc
            $msgSize = strlen($msgPart) / 2;
            $crc = substr($pl, -2);

            $seqR = AbeilleTools::reverseHex($seq);
            $posR = AbeilleTools::reverseHex($pos);
            parserLog("debug", "  Tuya-Zosung cmd ED00-${cmdId}: Seq=${seqR}, Pos=${posR}, MsgSize=d${msgSize}, CRC=${crc}");

            if (!isset($GLOBALS['zosung']) || !isset($GLOBALS['zosung'][$seqR])) {
                parserLog("debug", "  Unexpected message => Ignored");
                return $attrReportN;
            }

            // TODO
            $GLOBALS['zosung'][$seqR]['data'] = $msgPart;

            $expSize = $GLOBALS['zosung'][$seqR]['expSize'];
            if (($posR + $msgSize) < $expSize) {
                // Need more datas
                $pos = sprintf("%08X", hexdec($posR) + $msgSize);
                $data = $seq.$pos.'38';
                msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=02&data=${data}");
            } else {
                // All data received
                $data = '00'.$seq.'0000';
                msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=04&data=${data}");
            }
        } else if ($cmdId == "05") {
            // Cmd 05 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'zero', type: DataType.uint16},
            $seq = substr($pl, 0, 4);
            $zero = substr($pl, 4, 4);
            $seqR = AbeilleTools::reverseHex($seq);

            if (!isset($GLOBALS['zosung']) || !isset($GLOBALS['zosung'][$seqR])) {
                parserLog("debug", "  Unexpected message (seq ${seqR}) => Ignored");
                return $attrReportN;
            }

            $data = bin2hex("{'study':1}");
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=E004&cmd=00&data=${data}");

            // Report msgpart as "IR learned"
            $attrReportN[] = array(
                'name' => "01-learned-code",
                'value' => $GLOBALS['zosung'][$seqR]['data'],
            );
            unset($GLOBALS['zosung'][$seqR]);
        } else {
            parserLog("debug", "  Unsupported Tuya-Zosung cmd ".$cmdId." => ignored");
        }

        return $attrReportN;
    }
?>
