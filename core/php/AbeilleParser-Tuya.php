
<?php
    // Tuya specific parser functions
    // Included by 'AbeilleParser.php'

    // Decode cluster 0006 received cmd FD
    // Interrupteur sur pile TS0043 3 boutons sensitifs/capacitifs
    // Tuya 1,2,3,4 buttons switch
    function tuyaDecode0006CmdFD($srcEp, $msg) {
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
            'name' => $srcEp.'-click',
            'value' => $click,
        );
        $attributesN[] = $attr;

        // Legacy code to be removed at some point
        // TODO: Replace commands '0006-#EP#-0000' to '#EP#-click' in JSON
        $attr = array(
            'name' => '0006-'.$srcEp.'-0000',
            'value' => $value,
        );
        $attributesN[] = $attr;

        return $attributesN;
    }

    // Cluster EF00
    // See https://github.com/zigbeefordomoticz/wiki/blob/master/en-eng/Technical/Tuya-0xEF00.md
    // See: https://www.zigbee2mqtt.io/advanced/support-new-devices/02_support_new_tuya_devices.html#_2-adding-your-device

    // Decode cluster EF00 received cmd 01
    function tuyaDecodeEF00Cmd01($srcEp, $msg) {
        $attributesN = [];

        parserLog("debug", "  msg=".$msg, "8002");
        $tStatus = substr($msg, 0, 2); // uint8
        $tTId = substr($msg, 2, 2); // uint8
        $l = strlen($msg);
        for ($i = 4; $i < $l;) {
            $tDataPoint = substr($msg, $i, 2);
            $tDataType = substr($msg, $i + 2, 2);
            $tFunc = substr($msg, $i + 4, 2);
            $tLen = substr($msg, $i + 6, 2);
            parserLog("debug", "  Dp=".$tDataPoint.", DpType=".$tDataType.", Func=".$tFunc.", DpLen=".$tLen, "8002");

            $i += 8 + (hexdec($tLen) * 2);
        }

        return $attributesN;
    } // End tuyaDecodeEF00Cmd01()

    // Decode cluster EF00 received cmd 02
    /* Memo
        Format:
        sqn/uint16, dpList
            dpList = dataPoint/uint8, dataType/uint8, func/uint8, dataLen/uint8, data
        Opened points:
        - Is a 'dataPoint' valid for all Tuya devices ? (ex: dp 12 is always temp reporting ?)
        - What is the purpose of 'function' ?
        */
    function tuyaDecodeEF00Cmd02($srcEp, $msg) {
        $attributesN = [];

        parserLog("debug", "  msg=".$msg, "8002");
        $tSqn = substr($msg, 0, 4); // uint16
        $l = strlen($msg);
        for ($i = 4; $i < $l;) {
            $tDataPoint = substr($msg, $i, 2);
            $tDataType = substr($msg, $i + 2, 2);
            $tFunc = substr($msg, $i + 4, 2);
            $tLen = substr($msg, $i + 6, 2);
            parserLog("debug", "  Dp=".$tDataPoint.", DpType=".$tDataType.", Func=".$tFunc.", DpLen=".$tLen, "8002");
            $tData = substr($msg, $i + 8, hexdec($tLen) * 2);
            // Tcharp38: WARNING: The following decode seems to be specific to Smart Air sensor. To be revisited
            switch($tDataPoint) {
            case "02":
                $val = hexdec($tData);
                parserLog("debug", "  CO2=".$val." ppm", "8002");
                $attributesN[] = array(
                    'name' => $srcEp.'-CO2_ppm',
                    'value' => $val,
                );
                break;
            case "12": // Temp
                $val = hexdec($tData);
                $vReport = $val * 10; // Divided by 100 due to model
                $val = $val / 10;
                parserLog("debug", "  Temp=".$val." C", "8002");
                $attributesN[] = array(
                    'name' => '0402-'.$srcEp.'-0000',
                    'value' => $vReport,
                );
                break;
            case "13": // Humidity
                $val = hexdec($tData);
                $vReport = $val * 10; // Divided by 100 due to model
                $val = $val / 10;
                parserLog("debug", "  Humidity=".$val." %", "8002");
                $attributesN[] = array(
                    'name' => '0405-'.$srcEp.'-0000',
                    'value' => $vReport,
                );
                break;
            case "15": // VOC
                $val = hexdec($tData) / 10;
                parserLog("debug", "  VOC=".$val." ppm", "8002");
                $attributesN[] = array(
                    'name' => $srcEp.'-VOC_ppm',
                    'value' => $val,
                );
                break;
            case "16": // Formaldéhyde µg/m3 (Méthanal / CH2O_ppm)
                $val = hexdec($tData);
                parserLog("debug", "  VOC=".$val." ppm", "8002");
                $attributesN[] = array(
                    'name' => $srcEp.'-CH20_ppm',
                    'value' => $val,
                );
                break;
            default:
                parserLog('debug', '  Unsupported DP '.$tDataPoint);
                break;
            }
            $i += 8 + (hexdec($tLen) * 2);
        }

        return $attributesN;
    }

?>
