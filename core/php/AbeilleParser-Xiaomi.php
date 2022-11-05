
<?php
    // Xiaomi specific parser functions
    // Included by 'AbeilleParser.php'

    // WORK ONGOING. Not all functions migrated their yet.

    // Decode Xiaomi tags
    // Based on https://github.com/dresden-elektronik/deconz-rest-plugin/wiki/Xiaomi-manufacturer-specific-clusters%2C-attributes-and-attribute-reporting
    function xiaomiDecodeTags($pl) {
        $tagsList = array(
            "01-21" => array( "desc" => "Battery-Volt" ),
            "03-28" => array( "desc" => "Device-Temp" ),
            "0A-21" => array( "desc" => "Parent-Addr" ),
            "0B-21" => array( "desc" => "Light-Level" ),
            "64-10" => array( "desc" => "OnOff" ), // Or Open/closed
            "64-29" => array( "desc" => "Temperature" ),
            "65-21" => array( "desc" => "Humidity" ),
            "66-2B" => array( "desc" => "Pressure" ),
            "95-39" => array( "desc" => "Current" ), // Or Consumption
            "96-39" => array( "desc" => "Voltage" ),
            "97-39" => array( "desc" => "Consumption" ),
            "98-39" => array( "desc" => "Power" ),
        );

        $l = strlen($pl);
        for ($i = 0; $i < $l; ) {
            $tagId = substr($pl, $i + 0, 2);
            $typeId = substr($pl, $i + 2, 2);

            $type = zbGetDataType($typeId);
            $size = $type['size'];
            if ($size == 0) {
                parserLog('debug', '  Tag='.$tagId.', Type='.$typeId.'/'.$type['short'].' SIZE 0');
                break;
            }
            $data = substr($pl, $i + 4, $size * 2);
            if ($size > 1)
                $data = AbeilleTools::reverseHex($data);
            $i += 4 + ($size * 2);

            $idx = strtoupper($tagId.'-'.$typeId);
            if (isset($tagsList[$idx]))
                $tagName = '/'.$tagsList[$idx]['desc'];
            else
                $tagName = '';
            parserLog('debug', '  Tag='.$tagId.$tagName.', Type='.$typeId.'/'.$type['short'].', Data='.$data);
        }
    }
?>
