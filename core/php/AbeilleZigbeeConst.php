
<?php
    /*
     * Zigbee constants from official specs
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
    $zbClusters = array(
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
            )
        ),
        "0008" => array( // Level control cluster
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
            )
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
   ?>
