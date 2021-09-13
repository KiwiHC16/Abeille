
<?php
    /*
     * Zigbee constants from official specs
     * Mainly Zigbee Cluster Library spec (ZCL).
     */

    /* Returns Zigbee ZCL status from code. */
    function zbGetZCLStatus($status)
    {
        $status = strtolower($status);

        /* List of known devices per profile */
        $statusesTable = array (
            "00" => "Success",
            "01" => "Operation failed",
            "7e" => "Not authorized",
            "7f" => "Reserved field non zero",
            "80" => "Malformed command",
            "81" => "Unsupported cluster command",
            "82" => "Unsupported general command",
            "83" => "Unsupported manuf cluster command",
            "84" => "Unsupported manuf general command",
            "85" => "Invalid field",
            "86" => "Unsupported attribute",
            "87" => "Out of range error, or set to a reserved value",
            "88" => "Write to read only attribute",
            "89" => "Insufficient space",
            "8a" => "Duplicate entry",
            "8b" => "Not found",
            "8c" => "Unreportable attribute",
            "8d" => "Invalid data type",
            "8e" => "Invalid selector",
            "8f" => "Write only attribute",
            "90" => "Inconsistent startup state",
            "91" => "Defined out of band",
            "92" => "Inconsistent supplied value",
            "93" => "Action denied",
            "94" => "Timeout",
            "95" => "Process abort",
            "96" => "Invalid OTA image",
            "97" => "Waiting for data",
            "98" => "No OTA image available",
            "99" => "More OTA image required",
            "9a" => "Command being processed",
            "c0" => "Hardware failure",
            "c1" => "Software failure",
            "c2" => "Calibration error",
            "c3" => "Unsupported cluster"
        );

        if (array_key_exists($status, $statusesTable))
            return $statusesTable[$status];
        return "Unknown status ".$status;
    }

    // Clusters definition
    // Attributes on server side only
    // Commands received only
    // All hex are UPPER case
    // Note about names: Attributes are usually without any space (as ZCL spec) but cmds contain spaces
    $GLOBALS['zbClusters'] = array(
        "0000" => array(
            "name" => "Basic",
        ),
        "0001" => array(
            "name" => "Power configuration",
        ),
        "0002" => array(
            "name" => "Device temperature config",
        ),
        "0003" => array(
            "name" => "Identify",
        ),
        "0006" => array( // On/Off cluster
            "name" => "On/Off",
            "attributes" => array(
                "0000" => array( "name" => "OnOff", "access" => "R" ),
                "4000" => array( "name" => "GlobalSceneControl", "access" => "R" ),
                "4001" => array( "name" => "OnTime", "access" => "RW" ),
                "4002" => array( "name" => "OffWaitTime", "access" => "RW" ),
            ),
            "commands" => array(
                "00" => array( "name" => "Off" ),
                "01" => array( "name" => "On" ),
                "02" => array( "name" => "Toggle" ),
                "40" => array( "name" => "Off With Effect" ),
                "41" => array( "name" => "Off With Recall Global Scene" ),
                "42" => array( "name" => "Off With Timed Off" ),
            ),
        ),
        "0008" => array( // Level control cluster
            "name" => "Level control",
            "attributes" => array(
                "0000" => array( "name" => "CurrentLevel", "access" => "R" ),
                "0001" => array( "name" => "RemainingTime", "access" => "R" ),
                "0010" => array( "name" => "OnOffTransitionTime", "access" => "RW" ),
                "0011" => array( "name" => "OnLevel", "access" => "RW" ),
                "0012" => array( "name" => "OnTransitionTime", "access" => "RW" ),
                "0013" => array( "name" => "OffTransitionTime", "access" => "RW" ),
                "0014" => array( "name" => "DefaultMoveRate", "access" => "RW" ),
            ),
            "commands" => array(
                "00" => array( "name" => "Move To Level" ),
                "01" => array( "name" => "Move" ),
                "02" => array( "name" => "Step" ),
                "03" => array( "name" => "Stop" ),
                "04" => array( "name" => "Move To Level With OnOff" ),
                "05" => array( "name" => "Move With OnOff" ),
                "06" => array( "name" => "Step With OnOff" ),
                // "07" : { "name" : "Stop" ), // Another "stop" (0x07) ?
            ),
        ),
        "0015" => array(
            "name" => "Commissioning",
        ),
        "0019" => array( // OTA
            "name" => "OTA upgrade",
            "attributes" => array(
                // TO BE COMPLETED
            ),
            "commands" => array(
                "00" => array( "name" => "Image Notify" ),
                "01" => array( "name" => "Query Next Image Request" ),
                "02" => array( "name" => "Query Next Image Response" ),
                "03" => array( "name" => "Image Block Request" ),
                "04" => array( "name" => "Image Page Request" ),
                "05" => array( "name" => "Image Block Response" ),
                "06" => array( "name" => "Upgrade End Request" ),
                "07" => array( "name" => "Upgrade End Response" ),
                "08" => array( "name" => "Query Device Specific File Request" ),
                "09" => array( "name" => "Query Device Specific File Response" ),
            ),
        ),
        "0100" => array(
            "name" => "Shade Configuration",
        ),
        "0101" => array(
            "name" => "Door Lock",
        ),
        "0102" => array(
            "name" => "Window Covering",
        ),
        "0300" => array( // On/Off cluster
            "name" => "Color control",
            "attributes" => array(
            ),
            "commands" => array(
                "00" => array( "name" => "Move To Hue" ),
                "01" => array( "name" => "Move Hue" ),
                "02" => array( "name" => "Step Hue" ),
                "03" => array( "name" => "Move To Saturation" ),
                "04" => array( "name" => "Move Saturation" ),
                "05" => array( "name" => "Step Saturation" ),
                "06" => array( "name" => "Move To Hue And Saturation" ),
                "07" => array( "name" => "Move To Color" ),
                "08" => array( "name" => "Move Color" ),
                "09" => array( "name" => "Step Color" ),
                "0A" => array( "name" => "Move To Color Temperature" ),
                "40" => array( "name" => "Enhanced Move To Hue" ),
                "41" => array( "name" => "Enhanced Move Hue" ),
                "42" => array( "name" => "Enhanced Step Hue" ),
                "43" => array( "name" => "Enhanced Move To Hue And Saturation" ),
                "44" => array( "name" => "Color Loop Set" ),
                "47" => array( "name" => "Stop Move Step" ),
                "4B" => array( "name" => "Move Color Temperature" ),
                "4C" => array( "name" => "Step Color Temperature" ),
            ),
        ),
        "0301" => array(
            "name" => "Ballast Configuration",
        ),
        "0400" => array(
            "name" => "Illuminance Measurement",
        ),
        "0401" => array(
            "name" => "Illuminance Level Sensing",
        ),
        "0500" => array(
            "name" => "IAS Zone",
        ),
        "0501" => array(
            "name" => "IAS ACE",
        ),
        "0502" => array(
            "name" => "IAS WD",
        ),
        "0702" => array( // Metering (Smart Energy) cluster
            "name" => "Metering (Smart Energy)",
            "attributes" => array(
                "0000" => array( "name" => "CurrentSummationDelivered", "access" => "R" ),
                "0001" => array( "name" => "CurrentSummationReceived", "access" => "R" ),
                "0002" => array( "name" => "CurrentMaxDemandDelivered", "access" => "R" ),
                "0003" => array( "name" => "CurrentMaxDemandReceived", "access" => "R" ),
            ),
            // "commands" => array(
                // "cmd1" => array( "name" => "GetProfile" ),
                // "cmd2" => array( "name" => "RequestMirrorResponse" ),
                // "cmd3" => array( "name" => "MirrorRemoved" ),
                // "cmd4" => array( "name" => "RequestFastPollMode" ),
            // ),
        ),
        "0B04" => array( // Electrical measurement cluster
            "name" => "Electrical measurement",
            "attributes" => array(
                "0000" => array( "name" => "Measurement Type", "access" => "R" ),
                "0300" => array( "name" => "AC Frequency", "access" => "R" ),
                "0505" => array( "name" => "RMS Voltage", "access" => "R" ),
                "0508" => array( "name" => "RMS Current", "access" => "R" ),
                "050B" => array( "name" => "Active Power", "access" => "R" ),
                "0602" => array( "name" => "AC Current Multiplier", "access" => "R" ),
                "0603" => array( "name" => "AC Current Divisor", "access" => "R" ),
                "0604" => array( "name" => "AC Power Multiplier", "access" => "R" ),
                "0605" => array( "name" => "AC Power Divisor", "access" => "R" ),
            ),
            "commands" => array(
                // "cmd1" => array( "name" => "GetProfile" ),
                // "cmd2" => array( "name" => "RequestMirrorResponse" ),
                // "cmd3" => array( "name" => "MirrorRemoved" ),
                // "cmd4" => array( "name" => "RequestFastPollMode" ),
            ),
        ),
        "1000" => array(
            "name" => "Touchlink",
        ),
    );

    /* Returns Zigbee ZCL global command name from id. */
    function zbGetZCLGlobalCmdName($cmdId)
    {
        $id = strtolower($cmdId);

        /* List of known ZCL commands */
        $cmdsTable = array (
            "00" => "Read Attributes",
            "01" => "Read Attributes Response",
            "02" => "Write Attributes",
            "03" => "Write Attributes Undivided",
            "04" => "Write Attributes Response",
            "05" => "Write Attributes No Response",
            "06" => "Configure Reporting",
            "07" => "Configure Reporting Response",
            "08" => "Read Reporting Configuration",
            "09" => "Read Reporting Configuration Response",
            "0a" => "Report attributes",
            "0b" => "Default Response",
            "0c" => "Discover Attributes",
            "0d" => "Discover Attributes Response",
            "0e" => "Read Attributes Structured",
            "0f" => "Write Attributes Structured",
            "10" => "Write Attributes Structured response",
            "11" => "Discover Commands Received",
            "12" => "Discover Commands Received Response",
            "13" => "Discover Commands Generated",
            "14" => "Discover Commands Generated Response",
            "15" => "Discover Attributes Extended",
            "16" => "Discover Attributes Extended Response"
        );

        if (array_key_exists($id, $cmdsTable))
            return $cmdsTable[$id];
        return "Unknown cmd ".$id;
    }

    /* Based on ZCL spec.
       Returns cluster specific command from $clustId-$cmdId or false if unknown */
    function zbGetZCLClusterCmd($clustId, $cmdId)
    {
        global $zbClusters;

        $clustId = strtoupper($clustId);
        $cmdId = strtoupper($cmdId);

        if (!array_key_exists($clustId, $zbClusters))
            return false;
        if (!array_key_exists($cmdId, $zbClusters[$clustId]['commands']))
            return false;
        return $zbClusters[$clustId]['commands'][$cmdId];
    }

    /* Based on ZCL spec.
       Returns cluster specific command name from $clustId-$cmdId. */
    function zbGetZCLClusterCmdName($clustId, $cmdId) {
        $cmd = zbGetZCLClusterCmd($clustId, $cmdId);
        if ($cmd === false)
            return "Unknown cmd ".$clustId."-".$cmdId;
        return $cmd['name'];
    }

    /* Based on ZCL spec.
       Returns attribute infos for $clustId-$attrId or false if unknown */
    function zbGetZCLAttribute($clustId, $attrId) {
        global $zbClusters;

        $clustId = strtoupper($clustId);
        $attrId = strtoupper($attrId);

        if (!array_key_exists($clustId, $zbClusters))
            return false;
        if (!array_key_exists($attrId, $zbClusters[$clustId]['attributes']))
            return false;
        return $zbClusters[$clustId]['attributes'][$attrId];
    }

    /* Based on ZCL spec.
       Returns cluster specific command name from $clustId-$cmdId. */
    function zbGetZCLAttributeName($clustId, $attrId) {
        $attr = zbGetZCLAttribute($clustId, $attrId);
        if ($attr === false)
            return "Unknown attr ".$clustId."-".$attrId;
        return $attr['name'];
    }
?>
