
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

    function xiaomiDecodeFunction($addr, $valueHex, $value, $m, $map, &$attrReportN = null) {
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
        parserLog2('debug', $addr, $m);

        // if ($toMon !== null)
        //     $toMon[] = $m;
        if ($attrReportN !== null)
            $attrReportN[] = array( "name" => $map['info'], "value" => $value2 );
    }

    // Decode Xiaomi tags
    // Based on https://github.com/dresden-elektronik/deconz-rest-plugin/wiki/Xiaomi-manufacturer-specific-clusters%2C-attributes-and-attribute-reporting
    // Could be cluster 0000, attr FF01, type 42 (character string)
    // Could be cluster FCC0, attr 00F7, type 41 (octet string)
    function xiaomiDecodeTagsOld($net, $addr, $clustId, $attrId, $pl, &$attrReportN = null) {
        $eq = &getDevice($net, $addr); // By ref
        // parserLog('debug', 'eq='.json_encode($eq));
        if (!isset($eq['xiaomi']) || !isset($eq['xiaomi']['fromDevice'][$clustId.'-'.$attrId])) {
            parserLog2('debug', $addr, "    No defined Xiaomi mapping");
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
                parserLog2('debug', $addr, '    Tag='.$tagId.', Type='.$typeId.'/'.$type['short'].' SIZE 0');
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
                xiaomiDecodeFunction($addr, $valueHex, $value, $m, $mapping[$idx], $attrReportN);
            else {
                $m .= ' => '.$value.' (ignored)';
                parserLog2('debug', $addr, $m);
                // $toMon[] = $m;
            }
        }
    }
    function xiaomiDecodeTags($net, $addr, $clustId, $attrId, $pl, &$attrReportN = null) {
        $eq = &getDevice($net, $addr); // By ref
        // parserLog('debug', 'eq='.json_encode($eq));
        if (!isset($eq['private']) || !isset($eq['private'][$clustId.'-'.$attrId])) {
            parserLog2('debug', $addr, "    No defined Xiaomi mapping");
            // return;
            $mapping = [];
        } else
            $mapping = $eq['private'][$clustId.'-'.$attrId];

        $l = strlen($pl);
        if ($attrReportN !== null)
            $attrReportN = [];
        for ($i = 0; $i < $l; ) {
            $tagId = substr($pl, $i + 0, 2);
            $typeId = substr($pl, $i + 2, 2);

            $type = zbGetDataType($typeId);
            $size = $type['size'];
            if ($size == 0) {
                parserLog2('debug', $addr, '    Tag='.$tagId.', Type='.$typeId.'/'.$type['short'].' SIZE 0');
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
                xiaomiDecodeFunction($addr, $valueHex, $value, $m, $mapping[$idx], $attrReportN);
            else {
                $m .= ' => '.$value.' (ignored)';
                parserLog2('debug', $addr, $m);
                // $toMon[] = $m;
            }
        }
    }

    // OBSOLETE: Handle several attributes in a private way
    // Modified to treat 1 attribute only
    function xiaomiReportAttributeOld($net, $addr, $clustId, $pl, &$attrReportN = null) {
        $eq = &getDevice($net, $addr); // By ref
        // $l = strlen($pl);
        // for ( ; $l > 0; ) {
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
                    parserLog2('debug', $addr, "  WARNING: attrSize is unknown for type ".$attrType);
                }
            }
            $attrData = substr($pl, 0, $attrSize * 2); // Raw attribute data

            $pl = substr($pl, $attrSize * 2); // Point on next attribute
            $l = strlen($pl);

            //
            // Flexible decoding according to 'xiaomi' model's section
            //
            // Section format:
            // - Attribute
            // - Attribute including tags
            // - Attribute type 4C/struct
            //      "struct": 1, => indicates 4C/structure
            //      "01-21": { => <idx>-<type>
            //          "func": "numberDiv",
            //          "div": 1000,
            //          "info": "0001-01-0020",
            //          "comment": "Battery volt"
            //      }

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
                            "struct": 1,
                            "01-21": {
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

                    $m = '  AttrId='.$attrId
                        .', AttrType='.$attrType
                        .', ValueHex='.$valueHex.' => '.$value.' ==> '.$fromDev['info'].'='.$value;
                    parserLog2('debug', $addr, $m);
                    // $toMon[] = $m;

                    $attrReportN[] = array(
                        'name' => $fromDev['info'],
                        'value' => $value
                    );
                } else if (!isset($fromDev['struct'])) { // Idx format 'TA-TY', TA=tagId, TY=typeId
                    // 'CLUSTER-ATTRIB' + 'TAG-TYPE' syntax
                    $m = '  AttrId='.$attrId
                        .', AttrType='.$attrType;
                    parserLog2('debug', $addr, $m);
                    // $toMon[] = $m;

                    xiaomiDecodeTagsOld($net, $addr, $clustId, $attrId, $attrData, $attrReportN);
                } else { // CLUSTER-ATTRIB for type 4C/struct
                    $m = '  AttrId='.$attrId
                        .', AttrType='.$attrType;
                    parserLog2('debug', $addr, $m);
                    // $toMon[] = $m;

                    // Note: 2B count already skipped
                    // 4C/struct format reminder
                    //      xxxx = 2 Bytes for count (ignored)
                    //      t1 d1 = Type 1 (1 B) followed by data 1 (size depends on t1)
                    //      t2 d2 = Type 2 (1 B) followed by data 2 (size depends on t2)
                    //      ...
                    $subIdx = 0;
                    while (strlen($attrData) > 0) {
                        parserLog2('debug', $addr, '  attrData='.$attrData);

                        $subType = substr($attrData, 0, 2);
                        $subData = substr($attrData, 2); // Skipping type (1B)
                        $value = AbeilleParser::decodeDataType($subData, $subType, true, 0, $subSize, $valueHex);
                        $m = '    Idx='.$subIdx.', SubType='.$subType.', ValueHex='.$valueHex;
                        $idx = sprintf("%02X", $subIdx);
                        $idx .= '-'.$subType;
                        if (isset($fromDev[$idx]))
                            xiaomiDecodeFunction($valueHex, $value, $m, $fromDev[$idx], $attrReportN);
                        else {
                            $m .= ' => '.$value.' (ignored)';
                            parserLog2('debug', $addr, $m);
                            // $toMon[] = $m;
                        }

                        $attrData = substr($attrData, 2 + ($subSize * 2)); // Skipping sub-type + sub-data
                        $subIdx++;
                    }
                }
                // continue;
            } else {
                $m = "  UNHANDLED ".$clustId."-".$attrId."-".$attrType.": ".$attrData;
                parserLog2('debug', $addr, $m);
                if (($attrType == "41") || ($attrType == "42")) // Even if unhandled, displaying debug infos
                    xiaomiDecodeTagsOld($net, $addr, $clustId, $attrId, $attrData, $attrReportN);
            }
        // }
    }

    // Handle 1 attribute in a private way
    function xiaomiReportAttribute($net, $addr, $clustId, $attr, &$attrReportN = null) {
        $eq = &getDevice($net, $addr); // By ref

        $attrId = $attr['id'];
        $attrType = $attr['dataType'];
        $attrData = $attr['valueHex']; // Raw attribute data

        //
        // Flexible decoding according to 'xiaomi' model's section
        //
        // Section format:
        // - Attribute
        // - Attribute including tags
        // - Attribute type 4C/struct
        //      "struct": 1, => indicates 4C/structure
        //      "01-21": { => <idx>-<type>
        //          "func": "numberDiv",
        //          "div": 1000,
        //          "info": "0001-01-0020",
        //          "comment": "Battery volt"
        //      }

        /* "private": {
                "FCC0-0112": {
                    "type": "xiaomi",
                    "info": "0400-01-0000"
                },
                "FCC0-00F7": {
                    "type": "xiaomi",
                    "01-21": {
                        "func": "numberDiv",
                        "div": 1000,
                        "info": "0001-01-0020",
                        "comment": "Battery volt"
                    }
                },
                "FCC0-00F7": {
                    "type": "xiaomi",
                    "struct": 1,
                    "01-21": {
                        "func": "numberDiv",
                        "div": 1000,
                        "info": "0001-01-0020",
                        "comment": "Battery volt"
                    }
                }
            } */

        if (!isset($eq['private']) || !isset($eq['private'][$clustId.'-'.$attrId])) {
            parserLog2('debug', $addr, "  UNHANDLED ".$clustId."-".$attrId."-".$attrType.": ".$attrData);
            if (($attrType == "41") || ($attrType == "42")) // Even if unhandled, displaying debug infos
                xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN);
            return;
        }

        $private = $eq['private'][$clustId.'-'.$attrId];
        if (isset($private['info'])) {
            // 'CLUSTER-ATTRIB' + 'info' syntax
            $value = AbeilleParser::decodeDataType($pl2, $attrType, true, 0, $attrSize, $valueHex);

            $m = '  AttrId='.$attrId
                .', AttrType='.$attrType
                .', ValueHex='.$valueHex.' => '.$value.' ==> '.$fromDev['info'].'='.$value;
            parserLog2('debug', $addr, $m);

            $attrReportN[] = array(
                'name' => $private['info'],
                'value' => $value
            );
        } else if (!isset($private['struct'])) { // Idx format 'TA-TY', TA=tagId, TY=typeId
            // 'CLUSTER-ATTRIB' + 'TAG-TYPE' syntax
            $m = '  AttrId='.$attrId
                .', AttrType='.$attrType;
            parserLog2('debug', $addr, $m);
            // $toMon[] = $m;

            xiaomiDecodeTags($net, $addr, $clustId, $attrId, $attrData, $attrReportN);
        } else { // CLUSTER-ATTRIB for type 4C/struct
            $m = '  AttrId='.$attrId
                .', AttrType='.$attrType;
            parserLog2('debug', $addr, $m);

            // Note: 2B count already skipped
            // 4C/struct format reminder
            //      xxxx = 2 Bytes for count (ignored)
            //      t1 d1 = Type 1 (1 B) followed by data 1 (size depends on t1)
            //      t2 d2 = Type 2 (1 B) followed by data 2 (size depends on t2)
            //      ...
            $subIdx = 0;
            while (strlen($attrData) > 0) {
                parserLog2('debug', $addr, '  attrData='.$attrData);

                $subType = substr($attrData, 0, 2);
                $subData = substr($attrData, 2); // Skipping type (1B)
                $value = AbeilleParser::decodeDataType($subData, $subType, true, 0, $subSize, $valueHex);
                $m = '    Idx='.$subIdx.', SubType='.$subType.', ValueHex='.$valueHex;
                $idx = sprintf("%02X", $subIdx);
                $idx .= '-'.$subType;
                if (isset($private[$idx]))
                    xiaomiDecodeFunction($valueHex, $value, $m, $private[$idx], $attrReportN);
                else {
                    $m .= ' => '.$value.' (ignored)';
                    parserLog2('debug', $addr, $m);
                }

                $attrData = substr($attrData, 2 + ($subSize * 2)); // Skipping sub-type + sub-data
                $subIdx++;
            }
        }
    }
?>
