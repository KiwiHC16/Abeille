
<?php
    // Ikea specific parser functions
    // Included by 'AbeilleParser.php'

    function ikeaDecodeCmd($net, $addr, $ep, $clustId, $cmdId, $pl) {

        $attrReportN = [];
        if ($cmdId == '07') {

            $value = substr($pl, 0, 2);
            parserLog2("debug", $srcAddr, "  Ikea private: Cluster 0005, Cmd 07, Value=$value");

            $attrReportN[] = array(
                'name' => "$ep-0005-cmd07",
                'value' => $value,
            );
        } else
            parserLog2("debug", $srcAddr, "  ikeaDecodeCmd(): Unsupported cmd '$clustId-$cmdId'");

        return $attrReportN;
    }
?>
