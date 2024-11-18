
<?php
    // Philips specific parser functions
    // Included by 'AbeilleParser.php'

    // Model syntax reminder for Philips
    // "private": {
    //     "<clust>-<cmd>": { // Ex: 'FC00-00'
    //         "type": "generic",
    //         "function": "philipsDecodeCmdFC00"
    //     }
    // }

    // Example
    // "private": {
    //     "FC00-00": {
    //         "type": "generic",
    //         "function": "philipsDecodeCmdFC00"
    //     }
    // }

    function philipsDecodeCmdFC00($net, $addr, $ep, $clustId, $cmdId, $pl) {

        // $buttonEventTxt = array (
        //     '00' => 'Short press',
        //     '01' => 'Long press',
        //     '02' => 'Release short press',
        //     '03' => 'Release long press',
        // );
        // $attrId = substr($pl, 2, 2).substr($pl, 0, 2);
        // $buttonEvent = substr($pl, 0 + 2, 2);
        // $buttonDuree = hexdec(substr($pl, 0 + 6, 2));
        // parserLog2("debug", $addr, "  Philips Hue proprietary: Button=".$button.", Event=".$buttonEvent." (".$buttonEventTxt[$buttonEvent]."), duration=".$buttonDuree);

        // $attrReportN = [
        //     array( "name" => $clustId."-".$ep."-".$attrId."-Event", "value" => $buttonEvent ),
        //     array( "name" => $clustId."-".$ep."-".$attrId."-Duree", "value" => $buttonDuree ),
        // ];

        // Infos from "zigbee-herdsman"
        // commandsResponse: {
        //     hueNotification: {
        //         ID: 0,
        //         parameters: [
        //             {name: 'button', type: DataType.UINT8},
        //             {name: 'unknown1', type: DataType.UINT24},
        //             {name: 'type', type: DataType.UINT8},
        //             {name: 'unknown2', type: DataType.UINT8},
        //             {name: 'time', type: DataType.UINT8},
        //             {name: 'unknown2', type: DataType.UINT8},
        //         ],
        //     },
        // },

        $buttonTypeTxt = array (
            '00' => 'short-press',
            '01' => 'long-press',
            '02' => 'short-release',
            '03' => 'long-release',
        );

        $button = substr($pl, 0, 2);
        // $unknown1 = substr($pl, 2, 3);
        $type = substr($pl, 5, 2);
        // $unknown2 = substr($pl, 7, 2);
        $time = substr($pl, 9, 2);
        $typeTxt = isset($buttonTypeTxt[$type]) ? $buttonTypeTxt[$type] : "?";
        parserLog2("debug", $addr, "  Philips private: Cluster=$clustId, Cmd=$cmd, Button=$button, Type=$type/$typeTxt, Time=$time");

        $attrReportN = [];
        $attrReportN[] = Array( "name" => "$philipsFC00-$cmdId-$button", "value" => $typeTxt );

        // OBSOLETE SUPPORT
        // $attrReportN[] = Array( "name" => $clustId."-".$ep."-".$attrId."-Event", "value" => $buttonEvent );
        // $attrReportN[] = Array( "name" => $clustId."-".$ep."-".$attrId."-Duree", "value" => $buttonDuree );

        return $attrReportN;
    }
?>
