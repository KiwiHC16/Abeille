
<?php
    // Tuya specific parser functions
    // Included by 'AbeilleParser.php'

    // Decode cluster 0006 received cmd FD
    // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
    // Tuya 1,2,3,4 buttons switch
    function tuyaDecode0006CmdFD($ep, $addr, $msg) {
        $attributesN = [];

        $value = substr($msg, 0, 2);
        if ($value == "00")
            $click = 'single';
        else if ($value == "01")
            $click = 'double';
        else if ($value == "02")
            $click = 'long';
        else {
            parserLog2('debug',  $addr, '  Tuya 0006-FD specific command'
                .', value='.$value.' => UNSUPPORTED', "8002");
            return $attributesN;
        }

        parserLog2('debug',  $addr, '  Tuya 0006-FD specific command'
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

    // Data types reminder
    // Type	    TypeId  LengthInBytes	Description
    // raw	    0x00	N	            Corresponds to raw datapoint (module pass-through)
    // bool	    0x01	1	            value range: 0x00/0x01
    // value	0x02	4	            corresponds to int type, big end representation
    // string	0x03	N	            corresponds to a specific string
    // enum	    0x04	1	            Enumeration type, range 0-255
    // bitmap	0x05	1/2/4	        Large end representation for lengths greater than 1 byte
    function tuyaGetDpDataType($tData) {
        $types = array(
            "00" => "raw",
            "01" => "bool",
            "02" => "value",
            "03" => "string",
            "04" => "enum",
            "05" => "bitmap"
        );

        $tData = strtoupper($tData); // Upper string just in case
        if (isset($types[$tData]))
            return($types[$tData]);
        return "?";
    }

    function tuyaGetDp($msg) {
        $tDpId = substr($msg, 0, 2);
        $tDataType = substr($msg, 2, 2);
        // $tFunc = substr($msg, 4, 2); // What for ?
        // $tLen = substr($msg, 6, 2);
        $tLen = substr($msg, 4, 4);
        $tData = substr($msg, 8, hexdec($tLen) * 2);
        $tDataTypeTxt = tuyaGetDpDataType($tDataType);
        $m = "Dp={$tDpId}, Type={$tDataType}/{$tDataTypeTxt}, Len={$tLen}, ValueHex=".$tData;

        $dp = array(
            'id' => $tDpId,
            'type' => $tDataType,
            'dataLen' => $tLen,
            'data' => $tData,
            'm' => $m
        );
        return $dp;
    }


    // Receive a datapoint, map it to a specific function an decode it.
    // Mapping is defined "per device" directly in its model (private + type="tuya").
    function tuyaDecodeDp($addr, $ep, $dp, $mapping) {
        $dpId = $dp['id'];
        if (!isset($mapping[$dpId])) {
            parserLog2("debug", $addr, "  ".$dp['m'].": Unrecognized DP !", "8002");
            return false;
        }

        /* New syntax
            "private": {
                "EF00": {
                    "type": "tuya",
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
            else if ($mode == "02")
                $mode = "off";
            else if ($mode == "03")
                $mode = "holliday";
            else
                $mode = "?";
            $logMsg = "  ".$dp['m']." => Mode = ".$dp['data']."/".$mode;
            parserLog2("debug", $addr, $logMsg, "8002");
            $attributeN = array(
                'name' => $ep.'-mode',
                'value' => $mode,
            );
            break;

        // Solenoid valve Saswell SAS980SWT
        case "rcvValve-Status":
            $val = hexdec($dp['data']) % 2;
            $st = ($val == 1) ? "ON" : "OFF";
            parserLog2("debug", $addr, "  ".$dp['m']." => On/Off=".$val."/".$st, "8002");
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
            parserLog2("error", $addr, "  Unknown Tuya function '".$func."' for dpId=".$dpId);
            $attributeN = false;
            break;
        }

        if (isset($logMsg)) {
            parserLog2("debug", $addr, $logMsg, "8002");
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

    function tuyaDecodeEF00Cmd($net, $addr, $ep, $cmdId, $msg) {
        $tCmds = array(
            "00" => array("name" => "TY_DATA_REQUEST", "desc" => "Gateway-side data request"),
            "01" => array("name" => "TY_DATA_RESPONE", "desc" => "Reply to MCU-side data request"),
            "02" => array("name" => "TY_DATA_REPORT", "desc" => "MCU-side data active upload (bidirectional)"),
            "03" => array("name" => "TY_DATA_QUERY", "desc" => "GW send, trigger MCU side to report all current information, no zcl payload"),
            "06" => array("name" => "TY_DATA_SEARCH", "desc" => "?"),
            "10" => array("name" => "TUYA_MCU_VERSION_REQ", "desc" => "Gw->Zigbee gateway query MCU version"),
            "11" => array("name" => "TUYA_MCU_VERSION_RSP", "desc" => "Zigbee->Gw MCU return version or actively report version"),
            "12" => array("name" => "TUYA_MCU_OTA_NOTIFY", "desc" => "Gw->Zigbee gateway notifies MCU of upgrade"),
            "13" => array("name" => "TUYA_OTA_BLOCK_DATA_REQ", "desc" => "Zigbee->Gw requests an upgrade package for the MCU"),
            "14" => array("name" => "TUYA_OTA_BLOCK_DATA_RSP", "desc" => "Gw->Zigbee gateway returns the requested upgrade package"),
            "15" => array("name" => "TUYA_MCU_OTA_RESULT", "desc" => "Zigbee->Gw returns the upgrade result for the mcu"),
            "24" => array("name" => "TUYA_MCU_SYNC_TIME", "desc" => "Time synchronization (bidirectional)"),
            "25" => array("name" => "TUYA_INTERNET_STATUS", "desc" => "MCU request gateway connection status"),
        );

        // parserLog2("debug", $addr, "  BEN Tuya EF00 specific cmd (tuyaDecodeEF00Cmd) ".$cmdId." - ".json_encode($msg), "8002");

        $attributesN = [];
        $eq = &getDevice($net, $addr); // By ref

        // parserLog2("debug", $addr, "  BEN Tuya EF00 specific cmd (tuyaDecodeEF00Cmd) ".$cmdId." - ".json_encode($eq), "8002");

        if (($cmdId == "01") || ($cmdId == "02")) {
            // parserLog('debug', 'eq='.json_encode($eq));
            // if (!isset($eq['tuyaEF00']) || !isset($eq['tuyaEF00']['fromDevice'])) {
            // TODO: EF00 might not be always the cluster in Tuya DP mode
            if (!isset($eq['private']) || !isset($eq['private']['EF00'])) {
                parserLog2('debug', $addr, "  No defined Tuya mapping => ignoring (msg=".$msg.")");
                return [];
            }
            // $mapping = $eq['tuyaEF00']['fromDevice'];
            $mapping = $eq['private']['EF00'];
            // parserLog('debug', '  Tuya mapping='.json_encode($mapping));

            $tSqn = substr($msg, 0, 4); // uint16
            $msg = substr($msg, 4); // Skip tSqn
            parserLog2("debug", $addr, "  Tuya EF00 specific cmd ".$cmdId." (tSQN=".$tSqn.")", "8002");
            while (strlen($msg) != 0) {
                $dp = tuyaGetDp($msg);

                $a = tuyaDecodeDp($addr, $ep, $dp, $mapping);
                if ($a !== false)
                    $attributesN[] = $a;
                else { // Unknown DP
                    // if (!isset($eq['tuyaEF00']['unknown']) || !isset($eq['tuyaEF00'][$dp['id']]))
                    //     $eq['tuyaEF00']['unknown'][$dp['id']] = $dp;
                    // TODO: Is that the right converstion to 'private' section ?
                    $eq['private']['EF00']['unknown'][$dp['id']] = $dp;
                }

                // Move to next DP
                $s = 8 + (hexdec($dp['dataLen']) * 2);
                $msg = substr($msg, $s);
            }
        } else if ($cmdId == "06") { // TY_DATA_SEARCH
            parserLog2('debug', $addr, "  TY_DATA_SEARCH: ".$msg);
            $tSeq = substr($msg, 0, 4);
            $msg = substr($msg, 4); // Skip tSqn
            $dp = tuyaGetDp($msg);
            // $mapping = $eq['tuyaEF00']['fromDevice'];
            $mapping = $eq['private']['EF00'];
            $a = tuyaDecodeDp($addr, $ep, $dp, $mapping);
            if ($a !== false)
                $attributesN[] = $a;
            // $tSeq = substr($msg, 0, 4);
            // $tDpId = substr($msg, 4, 2);
            // $tDataType = substr($msg, 6, 2);
            // $tLen = substr($msg, 8, 4);
            // $tData = substr($msg, 12, hexdec($tLen) * 2);
            // $m = "Seq={$tSeq}, Dp={$tDpId}, Type={$tDataType}, Len={$tLen}, ValueHex=".$tData;
            // parserLog('debug', "  TY_DATA_SEARCH: ".$m);
        } else if ($cmdId == "11") { // TUYA_MCU_VERSION_RSP
            parserLog2('debug', $addr, "  TUYA_MCU_VERSION_RSP: ".$msg);
        } else if ($cmdId == "25") { // TUYA_INTERNET_STATUS
            parserLog2('debug', $addr, "  Internet access status request => Answering 'connected'");
            $tSqn = substr($msg, 0, 4); // uint16
            $manufCode = isset($eq['manufCode']) ? '&manufCode='.$eq['manufCode'] : '';
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-tuyaEF00", "ep=".$ep."&cmd=internetStatus".$manufCode."&tuyaSqn=".$tSqn."&data=01");
        } else {
            if (isset($tCmds[$cmdId]))
                $cmdName = $tCmds[$cmdId]['name'];
            else
                $cmdName = $cmdId."/?";
            parserLog2("debug", $addr, "  Unsupported Tuya cmd ".$cmdName." => ignored", "8002");
        }

// TODO: How to store unknown DP in discovery.json ?
// Should not store "on the fly" as file accesses are time consuming

        return $attributesN;
    }

    // Compute CRC for given message
    // Use cases: ED00 cluster support (Moes universal remote)
    function tuyaZosungCrc($message) {
        $crc = 0;
        $len = strlen($message) / 2;
        for($i = 0; $i < $len; $i++) {
            $c = substr($message, $i * 2, 2);
            // cmdLog('debug', "  c={$c}, crc=".dechex($crc));
            $crc += hexdec($c);
            $crc %= 0x100;
        }
        // cmdLog('debug', "  crc=".dechex($crc));
        return sprintf("%02X", $crc);
    }

    // Use cases: E004+ED00 clusters support (Moes universal remote)
    function tuyaDecodeZosungCmd($net, $addr, $ep, $cmdId, $pl) {
        $attrReportN = [];
        // Assuming cluster ED00
        if ($cmdId == "00") {
            // Cmd 00 reminder
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
            parserLog2("debug", $addr, "  Tuya-Zosung cmd ED00-00: Seq={$seqR}, Len={$lenR}, Cmd={$cmd} => Replying with ED00-01 & 02.");

            $data = '00'.$seq.$len.$unk1.$unk2.$unk3.$cmd.$unk4;
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=01&data={$data}");
            //meta.logger.debug(`"IR-Message-Code00" response sent.`);
            //Note: This last msg could generate err 14/E_ZCL_ERR_ZBUFFER_FAIL if too big. Size reduced by using hexdec().

            // Cmd 02 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'position', type: DataType.uint32},
            // {name: 'maxlen', type: DataType.uint8},
            $data = $seq.'00000000'.'38';
            // $time = time() + 2; // 2sec later
            // msgToCmd(PRIO_NORM, "TempoCmd".$net."/".$addr."/cmd-Generic&time={$time}", "ep=".$ep."&clustId=ED00&cmd=02&data={$data}");
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=02&data={$data}");
            // meta.logger.debug(`"IR-Message-Code00" transfer started.`);

            $GLOBALS['zosung'] = [];
            $GLOBALS['zosung'][$seqR] = array(
                'expected' => hexdec($lenR), // Expected size
                'received' => 0, // Received size
                'data' => ''
            );
        } else if ($cmdId == "01") {
            // Cmd 01 reminder
            // {name: 'zero', type: DataType.uint8},
            // {name: 'seq', type: DataType.uint16},
            // {name: 'length', type: DataType.uint32},
            // {name: 'unk1', type: DataType.uint32},
            // {name: 'unk2', type: DataType.uint16},
            // {name: 'unk3', type: DataType.uint8},
            // {name: 'cmd', type: DataType.uint8},
            // {name: 'unk4', type: DataType.uint16},
            $seq = substr($pl, 2, 4);
            $len = substr($pl, 6, 8);
            $unk1 = substr($pl, 14, 8);
            $unk2 = substr($pl, 22, 4);
            $unk3 = substr($pl, 26, 2);
            $cmd = substr($pl, 28, 2);
            $unk4 = substr($pl, 30, 4);

            $seqR = AbeilleTools::reverseHex($seq);
            $lenR = AbeilleTools::reverseHex($len);
            parserLog2("debug", $addr, "  Tuya-Zosung cmd ED00-01: Seq={$seqR}, Len={$lenR}, Cmd={$cmd}");
        } else if ($cmdId == "02") {
            // Cmd 02 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'position', type: DataType.uint32},
            // {name: 'maxlen', type: DataType.uint8},
            $seqR = AbeilleTools::reverseHex(substr($pl, 0, 4));
            $positionR = AbeilleTools::reverseHex(substr($pl, 4, 8));
            $maxLen = substr($pl, 12, 2);
            parserLog2("debug", $addr, "  Tuya-Zosung cmd ED00-02: Seq={$seqR}, Pos={$positionR}, MaxLen={$maxLen}");

            parserLog2("debug", $addr, "  Replying with cmd ED00-03");
            $data = array(
                'seq' => $seqR,
                'pos' => hexdec($positionR),
                'maxLen' => hexdec($maxLen)
            );
            $dataJson = json_encode($data, JSON_UNESCAPED_SLASHES);
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Private", "ep=".$ep."&fct=tuyaZosung&cmd=03&message={$dataJson}");
        } else if ($cmdId == "03") {
            // Cmd 03 reminder
            // {name: 'zero', type: DataType.uint8},
            // {name: 'seq', type: DataType.uint16},
            // {name: 'position', type: DataType.uint32},
            // {name: 'msgpart', type: DataType.octetStr},
            // {name: 'msgpartcrc', type: DataType.uint8},
            $seq = substr($pl, 2, 4);
            $pos = substr($pl, 6, 8);
            $msgSize = hexdec(substr($pl, 14, 2));
            $msgPart = substr($pl, 16, -2); // Rest is msgpart + msgpartcrc
            $msgPartSize = strlen($msgPart) / 2; // For control purposes
            if ($msgSize != $msgPartSize)
                parserLog2("debug", $addr, "  WARNING: msgSize ({$msgSize}) != len msgPart ({$msgPartSize})");
            $crc = substr($pl, -2);

            $seqR = AbeilleTools::reverseHex($seq);
            $posR = AbeilleTools::reverseHex($pos);
            parserLog2("debug", $addr, "  Tuya-Zosung cmd ED00-03: Seq={$seqR}, Pos={$posR}, MsgSize=d{$msgSize}, CRC={$crc}");
            $cCrc = tuyaZosungCrc($msgPart);
            parserLog2("debug", $addr, "  MsgSize={$msgSize}, MsgPart={$msgPart}, computedCRC={$cCrc}");
            if ($cCrc != $crc)
                parserLog2("debug", $addr, "  WARNING: Computed CRC ({$cCrc}) != CRC ({$crc})");

            if (!isset($GLOBALS['zosung']) || !isset($GLOBALS['zosung'][$seqR])) {
                parserLog2("debug", $addr, "  Unexpected message => Ignored");
                return $attrReportN;
            }

            $GLOBALS['zosung'][$seqR]['data'] .= $msgPart; // Append to end
            $GLOBALS['zosung'][$seqR]['received'] += $msgSize;

            $recSize = $GLOBALS['zosung'][$seqR]['received'];
            $expSize = $GLOBALS['zosung'][$seqR]['expected'];
            parserLog2("debug", $addr, "  rcvSize=d{$recSize}, expSize=d{$expSize}, posR={$posR}");
            if ($recSize < $expSize) {
                // Need more datas
                parserLog2("debug", $addr, "  Replying with cmd ED00-02: Need more datas");

                $posR = sprintf("%08X", hexdec($posR) + $msgSize);
                $pos = AbeilleTools::reverseHex($posR);
                $data = $seq.$pos.'38';
                msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=02&data={$data}");
            } else {
                // All data received
                parserLog2("debug", $addr, "  Replying with cmd ED00-04: All datas received");

                $data = '00'.$seq.'0000';
                msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=04&data={$data}");
            }
        } else if ($cmdId == "04") {
            // Cmd 04 reminder
            // {name: 'zero0', type: DataType.uint8},
            // {name: 'seq', type: DataType.uint16},
            // {name: 'zero1', type: DataType.uint16},
            $seq = substr($pl, 2, 4);
            $seqR = AbeilleTools::reverseHex($seq);
            parserLog2("debug", $addr, "  Tuya-Zosung cmd ED00-04: Seq={$seqR} => Replying with cmd ED00-05");

            // Cmd 05 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'zero', type: DataType.uint16},
            $data = $seq.'0000';
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=ED00&cmd=05&data={$data}");
        } else if ($cmdId == "05") {
            // Cmd 05 reminder
            // {name: 'seq', type: DataType.uint16},
            // {name: 'zero', type: DataType.uint16},
            $seq = substr($pl, 0, 4);
            $seqR = AbeilleTools::reverseHex($seq);
            parserLog2("debug", $addr, "  Tuya-Zosung cmd ED00-05: Seq={$seqR}");

            if (!isset($GLOBALS['zosung']) || !isset($GLOBALS['zosung'][$seqR])) {
                parserLog2("debug", $addr, "  Unexpected message (seq {$seqR}) => Ignored");
                return $attrReportN;
            }

            $data = bin2hex("{'study':1}");
            msgToCmd(PRIO_NORM, "Cmd".$net."/".$addr."/cmd-Generic", "ep=".$ep."&clustId=E004&cmd=00&data={$data}");

            // Report msgpart as "IR learned"
            $dataBA = hex2bin($GLOBALS['zosung'][$seqR]['data']);
            $dataB64URL = AbeilleTools::base64url_encode($dataBA);

            $attrReportN[] = array(
                'name' => "learnedCode",
                'value' => $dataB64URL,
            );
            unset($GLOBALS['zosung'][$seqR]);
        } else {
            parserLog2("debug", $addr, "  Unsupported Tuya-Zosung cmd ".$cmdId." => ignored");
        }

        return $attrReportN;
    }
?>
