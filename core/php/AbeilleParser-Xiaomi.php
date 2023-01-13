
<?php
    // Xiaomi specific parser functions
    // Included by 'AbeilleParser.php'

    // WORK ONGOING. Not all functions migrated their yet.

    // Model syntaxe reminder for Xiaomi
    // "xiaomi": {
    //     "fromDevice": {
    //         "XX-YY": { "func": "number", "info": "info_cmd_logic_id" }
    //         "XX-Y2": { "func": "numberDiv", "div": 100, "info": "info_cmd_logic_id" }
    //     }
    // }
    // where 'XX'=tag value, 'YY'=data type
    //       'func' (function to apply on value) can be 'raw', 'number', 'numberDiv', 'numberMult', 'toPercent'
    //              raw: received hexa value transmitted as it is
    //              number: resulting number transmitted as it is
    //              numberDiv: resulting number is divided by 'div' before Jeedom update
    //              numberMult: resulting number is multiplied by 'mult' before Jeedom update
    //              toPercent: resulting number is converted to percentage (with 'min' & 'max') before Jeedom update
    //              divAndPercent: resulting number is divided ('div') then converted to percentage (with 'min' & 'max') before Jeedom update

    // Decode Xiaomi tags
    // Based on https://github.com/dresden-elektronik/deconz-rest-plugin/wiki/Xiaomi-manufacturer-specific-clusters%2C-attributes-and-attribute-reporting
    // Could be cluster 0000, attr FF01, type 42 (character string)
    // Could be cluster FCC0, attr 00F7, type 41 (octet string)
    function xiaomiDecodeTags($net, $addr, $clustId, $attrId, $pl, &$attrReportN = null, &$toMon = null) {
        // $tagsList = array(
        //     "01-21" => array( "desc" => "Battery-Volt" ),
        //     "03-28" => array( "desc" => "Device-Temp" ),
        //     "0A-21" => array( "desc" => "Parent-Addr" ),
        //     "0B-21" => array( "desc" => "Light-Level" ),
        //     "64-10" => array( "desc" => "OnOff" ), // Or Open/closed
        //     "64-29" => array( "desc" => "Temperature" ),
        //     "65-21" => array( "desc" => "Humidity" ),
        //     "66-2B" => array( "desc" => "Pressure" ),
        //     "95-39" => array( "desc" => "Current" ), // Or Consumption
        //     "96-39" => array( "desc" => "Voltage" ),
        //     "97-39" => array( "desc" => "Consumption" ),
        //     "98-39" => array( "desc" => "Power" ),
        // );

        $eq = &getDevice($net, $addr); // By ref
        // parserLog('debug', 'eq='.json_encode($eq));
        if (!isset($eq['xiaomi']) || !isset($eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId])) {
            parserLog('debug', "  No defined Xiaomi mapping");
            // return;
            $mapping = [];
        } else
            $mapping = $eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId];

        $l = strlen($pl);
        if ($attrReportN !== null)
            $attrReportN = [];
        for ($i = 0; $i < $l; ) {
            $tagId = substr($pl, $i + 0, 2);
            $typeId = substr($pl, $i + 2, 2);

            $type = zbGetDataType($typeId);
            $size = $type['size'];
            if ($size == 0) {
                parserLog('debug', '  Tag='.$tagId.', Type='.$typeId.'/'.$type['short'].' SIZE 0');
                break;
            }
            $valueHex = substr($pl, $i + 4, $size * 2);
            $i += 4 + ($size * 2);
            if ($size > 1)
                $valueHex = AbeilleTools::reverseHex($valueHex);
            $value = AbeilleParser::decodeDataType($valueHex, $typeId, false, 0, $dataSize, $valueHex);

            $idx = strtoupper($tagId.'-'.$typeId);
            // if (isset($tagsList[$idx]))
            //     $tagName = '/'.$tagsList[$idx]['desc'];
            // else
            //     $tagName = '';
            // parserLog('debug', '  Tag='.$tagId.$tagName.', Type='.$typeId.'/'.$type['short'].', Value='.$valueHex.' => '.$value);
            if (isset($mapping[$idx])) {
                $map = $mapping[$idx];
                $value2 = $value;
                $mapTxt = ' ==> ';

                $func = (isset($map['func']) ? $map['func'] : "raw");
                if ($func == "raw") {
                    $value2 = $valueHex;
                } else if ($func == "numberDiv") {
                    $div = $map['div'];
                    $value2 = $value / $div;
                    $mapTxt .= 'div='.$div.', ';
                } else if ($func == "numberMult") {
                    $mult = $map['mult'];
                    $value2 = $value * $mult;
                    $mapTxt .= 'mult='.$mult.', ';
                } else if ($func == "toPercent") {
                    $min = (isset($map['min']) ? $map['min'] : 2.85);
                    $max = (isset($map['max']) ? $map['max'] : 3.0);
                    if ($value > $max)
                        $value2 = $max;
                    else if ($value < $min)
                        $value2 = $min;
                    $value2 = ($value2 - $min) / ($max - $min);
                    $value2 *= 100;
                    $mapTxt .= 'min='.$min.', max='.$max.', ';
                } else if ($func == "divAndPercent") {
                    $div = (isset($map['div']) ? $map['div'] : 1);
                    $value2 /= $div;
                    $min = (isset($map['min']) ? $map['min'] : 2.85);
                    $max = (isset($map['max']) ? $map['max'] : 3.0);
                    if ($value2 > $max)
                        $value2 = $max;
                    else if ($value2 < $min)
                        $value2 = $min;
                    $value2 = ($value2 - $min) / ($max - $min);
                    $value2 *= 100;
                    $mapTxt .= 'div='.$div.', min='.$min.', max='.$max.', ';
                }
                $mapTxt .= $map['info'];
                $mapTxt .= '='.$value2;

                if ($attrReportN !== null)
                    $attrReportN[] = array( "name" => $map['info'], "value" => $value2 );
            } else
                $mapTxt = ' (ignored)';
            $m = '  Tag='.$tagId.', Type='.$typeId.'/'.$type['short'].', ValueHex='.$valueHex.' => '.$value.$mapTxt;
            parserLog('debug', $m);
            if ($toMon !== null)
                $toMon[] = $m;

        }
    }

    function xiaomiReportAttributes($net, $addr, $clustId, $pl, &$attrReportN = null, &$toMon = null) {
        $l = strlen($pl);
        for ( ; $l > 0; ) {
            $attrId = substr($pl, 2, 2).substr($pl, 0, 2);
            $attrType = substr($pl, 4, 2);
            $pl = substr($pl, 6);

            // Computing attribute size
            if (($attrType == "41") || ($attrType == "42")) {
                $attrSize = hexdec(substr($pl, 0, 2));
                $pl = substr($pl, 2); // Skip 1B size
            } else {
                $type = zbGetDataType($attrType);
                $attrSize = $type['size'];
                if ($attrSize == 0) {
                    $attrSize = strlen($pl) / 2;
                    parserLog('debug', "  WARNING: attrSize is unknown for type ".$attrType);
                }
            }
            $attrData = substr($pl, 0, $attrSize * 2); // Attribute data
            $pl = substr($pl, $attrSize * 2); // Point on next attribute
            $l = strlen($pl);

            //
            // Legacy decoding
            //

            // Xiaomi door sensor V2
            if (($attrId == "FF01") && ($attrSize == 29)) {
                // Assuming $dataType == "42"

                parserLog('debug', '  Xiaomi proprietary (Door Sensor)');
                $attrReportN = [];
                xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN, $toMon);

                // Previous code. For info only
                // // $voltage        = hexdec(substr($payload, 24 + 2 * 2 + 2, 2).substr($payload, 24 + 2 * 2, 2));
                // $voltage = hexdec(substr($Attribut, 2 * 2 + 2, 2).substr($Attribut, 2 * 2, 2));
                // // $etat           = substr($payload, 80, 2);
                // $etat = substr($Attribut, 80 - 24, 2);
                // parserLog('debug', '  Xiaomi proprietary (Door Sensor): Volt='.$voltage.', Volt%='.$this->volt2pourcent($voltage).', State='.$etat);
                // $attributesReportN = [
                //     // array( "name" => "0001-01-0020", "value" => $voltage  / 1000 ),
                //     array( "name" => "0001-01-0021", "value" => $this->volt2pourcent($voltage) ),
                //     array( "name" => "0006-01-0000", "value" => $etat ),
                // ];
                continue;
            }

            // Xiaomi leak sensor
            // TO BE COMPLETED !!
            else if (($attrId == 'FF01') && ($attrSize == 34 /* "0022" */)) {
                // Assuming DataType=42
                parserLog('debug', '  Xiaomi proprietary (Water leak sensor)');

                // For info until activation
                $unused = [];
                xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $unused, $toMon);
                // For info until activation

                // $voltage = hexdec(substr($pl2, 24 + 2 * 2 + 2, 2).substr($pl2, 24 + 2 * 2, 2));
                // $etat = substr($pl2, 88, 2);
                // parserLog('debug', '  Volt='.$voltage.', Etat='.$etat);
                // $attrReportN = [
                //     // array( "name" => "0001-01-0020", "value" => $voltage  / 1000 ),
                //     array( "name" => "0001-01-0021", "value" => $this->volt2pourcent($voltage) ),
                // ];
                continue;
            }

            // Xiaomi temp/humidity/pressure square sensor
            else if (($attrId == 'FF01') && ($attrSize == 37)) {
                // Assuming $dataType == "42"

                parserLog('debug', '  Xiaomi proprietary (Temp square sensor)');
                $attrReportN = [];
                xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN, $toMon);

                // Previous code. For info only
                // $voltage        = hexdec(substr($pl2, 2 * 2 + 2, 2).substr($pl2, 2 * 2, 2));
                // $temperature    = unpack("s", pack("s", hexdec(substr($pl2, 21 * 2 + 2, 2).substr($pl2, 21 * 2, 2))))[1];
                // $humidity       = hexdec(substr($pl2, 25 * 2 + 2, 2).substr($pl2, 25 * 2, 2));
                // $pression       = hexdec(substr($pl2, 29 * 2 + 6, 2).substr($pl2, 29 * 2 + 4, 2).substr($pl2, 29 * 2 + 2, 2).substr($pl2, 29 * 2, 2));
                // parserLog('debug', '  Legacy: Volt='.$voltage.', Temp='.$temperature.', Humidity='.$humidity.', Pressure='.$pression);
                // $attributesReportN = [
                //     // array( "name" => "0001-01-0020", "value" => $voltage  / 1000 ),
                //     array( "name" => "0001-01-0021", "value" => $this->volt2pourcent($voltage) ),
                //     array( "name" => '0402-01-0000', "value" => $temperature / 100 ),
                //     array( "name" => '0405-01-0000', "value" => $humidity / 100 ),
                // ];
                continue;
            }

            //
            // New decoding
            //

            $eq = &getDevice($net, $addr); // By ref
            if (isset($eq['xiaomi']) && isset($eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId])) { // Xiaomi specific without manufCode
                xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN, $toMon);
            } else {
                parserLog('debug', "  UNHANDLED ".$clustId."-".$attrId."-".$attrType.": ".$attrData);
            }

            // break; // Currently supporting only 1 attribut.
            // $pl = substr($pl, $attrSize * 2);
            // $l = strlen($pl);
        }
    }
?>
