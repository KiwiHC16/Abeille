
<?php
    // Xiaomi specific parser functions
    // Included by 'AbeilleParser.php'

    // Model syntax reminder for Xiaomi
    // "xiaomi": {
    //     "fromDevice": {
    //         "<clust>-<attr>": {
    //             "XX-YY": { "func": "number", "info": "info_cmd_logic_id" }
    //             "XX-Y2": { "func": "numberDiv", "div": 100, "info": "info_cmd_logic_id" }
    //         }
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
    // Example
    // "xiaomi": {
    //     "fromDevice": {
    //         "0000-FF02": {
    //             "01-21": {
    //                 "func": "numberDiv",
    //                 "div": 1000,
    //                 "info": "0001-01-0020",
    //                 "comment": "Battery-Volt"
    //             }
    //         }
    //     }
    // }

    function xiaomiDecodeFunction($valueHex, $value, $m, $map, &$attrReportN = null, &$toMon = null) {
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

        $m .= ' => '.$value.$mapTxt;
        parserLog('debug', $m);

        if ($toMon !== null)
            $toMon[] = $m;
        if ($attrReportN !== null)
            $attrReportN[] = array( "name" => $map['info'], "value" => $value2 );
    }

    // Decode Xiaomi tags
    // Based on https://github.com/dresden-elektronik/deconz-rest-plugin/wiki/Xiaomi-manufacturer-specific-clusters%2C-attributes-and-attribute-reporting
    // Could be cluster 0000, attr FF01, type 42 (character string)
    // Could be cluster FCC0, attr 00F7, type 41 (octet string)
    function xiaomiDecodeTags($net, $addr, $clustId, $attrId, $pl, &$attrReportN = null, &$toMon = null) {
        $eq = &getDevice($net, $addr); // By ref
        // parserLog('debug', 'eq='.json_encode($eq));
        if (!isset($eq['xiaomi']) || !isset($eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId])) {
            parserLog('debug', "    No defined Xiaomi mapping");
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
                parserLog('debug', '    Tag='.$tagId.', Type='.$typeId.'/'.$type['short'].' SIZE 0');
                break;
            }
            $valueHex = substr($pl, $i + 4, $size * 2);
            $i += 4 + ($size * 2);
            if ($size > 1)
                $valueHex = AbeilleTools::reverseHex($valueHex);
            $value = AbeilleParser::decodeDataType($valueHex, $typeId, false, 0, $dataSize, $valueHex);

            $m = '    Tag='.$tagId.', Type='.$typeId.'/'.$type['short'];

            $idx = strtoupper($tagId.'-'.$typeId);
            if (isset($mapping[$idx]))
                xiaomiDecodeFunction($valueHex, $value, $m, $mapping[$idx], $attrReportN, $toMon);
            else {
                $m .= ' => '.$value.' (ignored)';
                parserLog('debug', $m);
                $toMon[] = $m;
            }
        }
    }

    function xiaomiReportAttributes($net, $addr, $clustId, $pl, &$attrReportN = null, &$toMon = null) {
        $eq = &getDevice($net, $addr); // By ref
        $l = strlen($pl);
        for ( ; $l > 0; ) {
            $attrId = substr($pl, 2, 2).substr($pl, 0, 2);
            $attrType = substr($pl, 4, 2);
            $pl = substr($pl, 6); // Skipping attr ID + attr type
            $pl2 = $pl;

            // Computing attribute size for legacy decodes
            if (($attrType == "41") || ($attrType == "42")) {
                $attrSize = hexdec(substr($pl, 0, 2));
                $pl = substr($pl, 2); // Skipping 1B size
            } else if ($attrType == "4C") { // Struct
                $pl = substr($pl, 4); // Skipping 2B count
                $attrSize = strlen($pl) / 2; // WARNING: This is wrong. Then can't support consecutive attributes
            } else {
                $type = zbGetDataType($attrType);
                $attrSize = $type['size'];
                if ($attrSize == 0) {
                    $attrSize = strlen($pl) / 2;
                    parserLog('debug', "  WARNING: attrSize is unknown for type ".$attrType);
                }
            }
            $attrData = substr($pl, 0, $attrSize * 2); // Raw attribute data

            $pl = substr($pl, $attrSize * 2); // Point on next attribute
            $l = strlen($pl);

            //
            // Flexible decoding according to 'xiaomi' model's section
            // - Attribute
            // - Attribute including tags
            // - Attribute type struct 4C
            /* "xiaomi": {
                   "fromDevice": {
                        "FCC0-0112": {
                            "info": "0400-01-0000"
                        },
                        "FCC0-00F7": {
                            "01-21": {
                                "func": "numberDiv",
                                "div": 1000,
                                "info": "0001-01-0020",
                                "comment": "Battery volt"
                            }
                        },
                        "FCC0-00F7": {
                            "10": {
                                "func": "numberDiv",
                                "div": 1000,
                                "info": "0001-01-0020",
                                "comment": "Battery volt"
                            }
                        }
                    }
                } */

            if (isset($eq['xiaomi']) && isset($eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId])) {
                $fromDev = $eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId];
                if (isset($fromDev['info'])) {
                    // 'CLUSTER-ATTRIB' + 'info' syntax
                    $value = AbeilleParser::decodeDataType($pl2, $attrType, true, 0, $attrSize, $valueHex);
                    $attrReportN[] = array(
                        'name' => $fromDev['info'],
                        'value' => $value
                    );

                    $m = '  AttrId='.$attrId
                        .', AttrType='.$attrType
                        .', ValueHex='.$valueHex.' => '.$value.' ==> '.$fromDev['info'].'='.$value;
                    parserLog('debug', $m);
                    $toMon[] = $m;
                } else if (strlen(array_key_first($fromDev)) == 5) { // Idx format 'TA-TY', TA=tagId, TY=typeId
                    // 'CLUSTER-ATTRIB' + 'TAG-TYPE' syntax
                    $m = '  AttrId='.$attrId
                        .', AttrType='.$attrType;
                    parserLog('debug', $m);
                    $toMon[] = $m;

                    xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN, $toMon);
                } else { // CLUSTER-ATTRIB for type 4C/struct
                    $m = '  AttrId='.$attrId
                        .', AttrType='.$attrType;
                    parserLog('debug', $m);
                    $toMon[] = $m;

                    // Note: 4B count already skipped
                    while (strlen($attrData) > 0) {
                        $t = substr($attrData, 2);
                        $attrData = substr($attrData, 2); // Skipping type
                        $m = '    SubType='.$t.', ValueHex='.$valueHex;
                        $value = AbeilleParser::decodeDataType($attrData, $t, true, 0, $s, $valueHex);
                        if (isset($fromDev[$t]))
                            xiaomiDecodeFunction($valueHex, $value, $m, $fromDev[$t], $attrReportN, $toMon);
                        else {
                            $m .= ' => '.$value.' (ignored)';
                            parserLog('debug', $m);
                            $toMon[] = $m;
                        }

                        $attrData = substr($attrData, $s * 2); // Skipping data
                    }
                }
                continue;
            }

            $m = "  UNHANDLED ".$clustId."-".$attrId."-".$attrType.": ".$attrData;
            parserLog('debug', $m);
            $toMon[] = $m;
            if (($attrType == "41") || ($attrType == "42")) // Even if unhandled, displaying debug infos
                xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN, $toMon);
        }
    }
?>
