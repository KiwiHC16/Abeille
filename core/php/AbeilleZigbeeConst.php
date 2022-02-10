
<?php
    /*
     * Zigbee constants from official specs.
     * Mainly Zigbee Cluster Library spec (ZCL).
     */

    /* Returns Zigbee APS status from code. */
    function zbGetAPSStatus($status)
    {
        $status = strtoupper($status);

        /* Zigbee APS statuses */
        $statusesTable = array (
            "00" => "Success",
            "A0" => "ASDU_TOO_LONG",
            "A1" => "DEFRAG_DEFERRED",
            "A2" => "DEFRAG_UNSUPPORTED",
            "A3" => "ILLEGAL_REQUEST",
            "A4" => "INVALID_BINDING",
            "A5" => "INVALID_GROUP",
            "A6" => "INVALID_PARAMETER",
            "A7" => "NO_ACK",
            "A8" => "NO_BOUND_DEVICE",
            "A9" => "NO_SHORT_ADDRESS",
            "AA" => "NOT_SUPPORTED",
            "AB" => "SECURED_LINK_KEY",
            "AC" => "SECURED_NWK_KEY",
            "AD" => "SECURITY_FAIL",
            "AE" => "TABLE_FULL",
            "AF" => "UNSECURED",
            "B0" => "UNSUPPORTED_ATTRIBUTE",
        );

        /* ZCL statuses */
        if (array_key_exists($status, $statusesTable))
            return $statusesTable[$status];
        return "Unknown-".$status;
    }

    /* Returns Zigbee ZCL status from code. */
    function zbGetZCLStatus($status)
    {
        $status = strtolower($status);

        /* ZCL statuses */
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
        return "Unknown-".$status;
    }

    // Clusters definition
    //  'name' = Cluster name
    //  'attributes' = Array. Server attributes
    //      'name' = String. Attribute name
    //      'access' = String. Access type Read, Write, rePortable
    //      'dataType' = Number.
    //  'commands' = Array. commands received
    // All hex are UPPER case
    $GLOBALS['zbClusters'] = array(
        "0000" => array(
            "name" => "Basic",
            "attributes" => array(
                "0000" => array( "name" => "ZCLVersion", "access" => "R" ),
                "0001" => array( "name" => "ApplicationVersion", "access" => "R" ),
                "0002" => array( "name" => "StackVersion", "access" => "R" ),
                "0003" => array( "name" => "HWVersion", "access" => "R" ),
                "0004" => array( "name" => "ManufacturerName", "access" => "R" ),
                "0005" => array( "name" => "ModelIdentifier", "access" => "R" ),
                "0006" => array( "name" => "DateCode", "access" => "R" ),
                "0007" => array( "name" => "PowerSource", "access" => "R" ),
                "0010" => array( "name" => "LocationDescription", "access" => "RW" ),
                "0011" => array( "name" => "PhysicalEnvironment", "access" => "RW" ),
                "0012" => array( "name" => "DeviceEnabled", "access" => "RW" ),
                "0013" => array( "name" => "AlarmMask", "access" => "RW" ),
                "0014" => array( "name" => "DisableLocalConfig", "access" => "RW" ),
                "4000" => array( "name" => "SWBuildID", "access" => "R" ),
            ),
            "commands" => array(
                "00" => array( "name" => "Reset to factory defaults" ),
            ),
        ),
        "0001" => array(
            "name" => "Power configuration",
            "attributes" => array(
                "0000" => array( "name" => "MainsVoltage", "access" => "R" ),
                "0020" => array( "name" => "BatteryVoltage", "access" => "R" ),
                "0021" => array( "name" => "BatteryPercentageRemaining", "access" => "R" ),
            ),
        ),
        "0002" => array(
            "name" => "Device temperature config",
        ),
        "0003" => array(
            "name" => "Identify",
            "attributes" => array(
                "0000" => array( "name" => "IdentifyTime", "access" => "RW" ),
            ),
            "commands" => array(
                "00" => array( "name" => "Identify" ),
                "01" => array( "name" => "IdentifyQuery" ),
                "40" => array( "name" => "TriggerEffect" ),
            ),
        ),
        "0004" => array(
            "name" => "Groups",
            "attributes" => array(
                "0000" => array( "name" => "NameSupport", "access" => "R" ),
            ),
            "commands" => array(
                "00" => array( "name" => "AddGroup" ),
                "01" => array( "name" => "ViewGroup" ),
                "02" => array( "name" => "GetGroupMembership" ),
                "03" => array( "name" => "RemoveGroup" ),
                "04" => array( "name" => "RemoveAllGroups" ),
                "05" => array( "name" => "AddGroupIfIdent" ),
            ),
        ),
        "0005" => array(
            "name" => "Scenes",
            "attributes" => array(
                "0000" => array( "name" => "SceneCount", "access" => "R" ),
                "0001" => array( "name" => "CurrentScene", "access" => "R" ),
                "0002" => array( "name" => "CurrentGroup", "access" => "R" ),
                "0003" => array( "name" => "SceneValid", "access" => "R" ),
                "0004" => array( "name" => "NameSupport", "access" => "R" ),
                "0005" => array( "name" => "LastConfiguredBy", "access" => "R" ),
            ),
            "commands" => array(
                "00" => array( "name" => "AddScene" ),
                "01" => array( "name" => "ViewScene" ),
                "02" => array( "name" => "RemoveScene" ),
                "03" => array( "name" => "RemoveAllScenes" ),
                "04" => array( "name" => "StoreScene" ),
                "05" => array( "name" => "RecallScene" ),
                "06" => array( "name" => "GetSceneMembership" ),
                "40" => array( "name" => "EnhancedAddScene" ),
                "41" => array( "name" => "EnhancedViewScene" ),
                "42" => array( "name" => "CopyScene" ),
            ),
        ),
        "0006" => array(
            "name" => "On/Off",
            "attributes" => array(
                "0000" => array( "name" => "OnOff", "access" => "R", "dataType" => 0x10 ),
                "4000" => array( "name" => "GlobalSceneControl", "access" => "R", "dataType" => 0x10 ),
                "4001" => array( "name" => "OnTime", "access" => "RW", "dataType" => 0x21 ),
                "4002" => array( "name" => "OffWaitTime", "access" => "RW", "dataType" => 0x21 ),
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
        "0007" => array(
            "name" => "On/Off switch config",
            "attributes" => array(
                "0000" => array( "name" => "SwitchType", "access" => "R" ),
                "0010" => array( "name" => "SwitchActions", "access" => "RW" ),
            ),
        ),
        "0008" => array(
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
                // "07" => array( "name" => "Stop" ), // Another "stop" (0x07) ?
            ),
        ),
        "0009" => array(
            "name" => "Alarm",
            "attributes" => array(
                "0000" => array( "name" => "AlarmCount", "access" => "R" ),
            ),
            "commands" => array(
                "00" => array( "name" => "ResetAlarm" ),
                "01" => array( "name" => "ResetAllAlarms" ),
                "02" => array( "name" => "GetAlarm" ),
                "03" => array( "name" => "ResetAlarmLog" ),
            ),
        ),
        "000A" => array(
            "name" => "Time",
            "attributes" => array(
                "0000" => array( "name" => "Time", "access" => "RW" ),
                "0001" => array( "name" => "TimeStatus", "access" => "RW" ),
                "0002" => array( "name" => "TimeZone", "access" => "RW" ),
                "0003" => array( "name" => "DstStart", "access" => "RW" ),
                "0004" => array( "name" => "DstEnd", "access" => "RW" ),
                "0005" => array( "name" => "DstShift", "access" => "RW" ),
                "0006" => array( "name" => "StandardTime", "access" => "R" ),
                "0007" => array( "name" => "LocalTime", "access" => "R" ),
                "0008" => array( "name" => "LastSetTime", "access" => "R" ),
                "0009" => array( "name" => "ValidUntilTime", "access" => "RW" ),
            ),
        ),
        "000C" => array(
            "name" => "Analog Input",
            "attributes" => array(
                "0051" => array( "name" => "OutOfService", "access" => "RW", "dataType" => 0x10 ), // Type bool
                "0055" => array( "name" => "PresentValue", "access" => "RW", "dataType" => 0x39 ), // 0x39 = Single precision
                "006F" => array( "name" => "StatusFlags", "access" => "R", "dataType" => 0x18 ), // Type map8
            ),
        ),
        "0012" => array(
            "name" => "Multistate Input",
            "attributes" => array(
                "PresentValue" => array( "name" => "PresentValue", "access" => "RW", "dataType" => 0x21 ), // uint16
            ),
        ),
        "0014" => array(
            "name" => "Multistate Value",
            "attributes" => array(
                "000E" => array( "name" => "StateText", "access" => "RW" ),
                "001C" => array( "name" => "Description", "access" => "RW" ),
                "004A" => array( "name" => "NumberOfStates", "access" => "RW" ),
                "0051" => array( "name" => "OutOfService", "access" => "RW" ),
                "0055" => array( "name" => "PresentValue", "access" => "RW" ),
                "0057" => array( "name" => "PriorityArray", "access" => "RW" ),
                "0067" => array( "name" => "Reliability", "access" => "RW" ),
                "0068" => array( "name" => "RelinquishDefault", "access" => "RW" ),
                "006F" => array( "name" => "StatusFlags", "access" => "R" ),
                "0100" => array( "name" => "ApplicationType", "access" => "R" ),
            ),
        ),
        "0015" => array(
            "name" => "Commissioning",
        ),
        "0019" => array( // OTA
            "name" => "OTA upgrade",
            "attributes" => array(
                "0000" => array( "name" => "UpgradeServerID", "access" => "R" ),
                "0001" => array( "name" => "FileOffset", "access" => "R" ),
                "0002" => array( "name" => "CurrentFileVersion", "access" => "R" ),
                "0003" => array( "name" => "CurrentZigBeeStackVersion", "access" => "R" ),
                "0004" => array( "name" => "DownloadedFileVersion", "access" => "R" ),
                "0005" => array( "name" => "DownloadedZigBeeStackVersion", "access" => "R" ),
                "0006" => array( "name" => "ImageUpgradeStatus", "access" => "R" ),
                "0007" => array( "name" => "Manufacturer ID", "access" => "R" ),
                "0008" => array( "name" => "Image Type ID", "access" => "R" ),
                "0009" => array( "name" => "MinimumBlockPeriod", "access" => "R" ),
                "000A" => array( "name" => "Image Stamp", "access" => "R" ),
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
        "0020" => array(
            "name" => "Poll control",
            "attributes" => array(
                "0000" => array( "name" => "CheckInInterval", "access" => "RW" ),
                "0001" => array( "name" => "LongPollInterval", "access" => "R" ),
                "0002" => array( "name" => "ShortPollInterval", "access" => "R" ),
                "0003" => array( "name" => "FastPollTimeout", "access" => "RW" ),
                "0004" => array( "name" => "CheckInIntervalMin", "access" => "R" ),
                "0005" => array( "name" => "LongPollIntervalMin", "access" => "R" ),
                "0006" => array( "name" => "FastPollTimeoutMax", "access" => "R" ),
            ),
            "commands" => array(
                "00" => array( "name" => "CheckIn" ),
            ),
        ),
        "0021" => array(
            "name" => "Green power proxy",
        ),
        "0100" => array(
            "name" => "Shade Configuration",
            "attributes" => array(
                "0000" => array( "name" => "PhysicalClosedLimit", "access" => "R" ),
                "0001" => array( "name" => "MotorStepSize", "access" => "R" ),
                "0002" => array( "name" => "Status", "access" => "RW" ),
                "0010" => array( "name" => "ClosedLimit", "access" => "RW" ),
                "0011" => array( "name" => "Mode", "access" => "RW" ),
            ),
        ),
        "0101" => array(
            "name" => "Door Lock",
            "attributes" => array(
                "0000" => array( "name" => "LockState", "access" => "R" ),
                "0001" => array( "name" => "LockType", "access" => "R" ),
                "0002" => array( "name" => "ActuatorEnabled", "access" => "R" ),
                "0003" => array( "name" => "DoorState", "access" => "R" ),
                "0004" => array( "name" => "DoorOpenEvents", "access" => "RW" ),
                "0005" => array( "name" => "DoorClosedEvents", "access" => "RW" ),
                "0006" => array( "name" => "OpenPeriod", "access" => "RW" ),
                // To be completed
            ),
        ),
        "0102" => array(
            "name" => "Window Covering",
            "attributes" => array(
                // Information attributes
                "0000" => array( "name" => "WindowCoveringType", "access" => "R" ),
                "0001" => array( "name" => "PhysClosedLimitLift", "access" => "R" ),
                "0002" => array( "name" => "PhysClosedLimitTilt", "access" => "R" ),
                "0003" => array( "name" => "CurPosLift", "access" => "R" ),
                "0004" => array( "name" => "CurPosTilt", "access" => "R" ),
                "0005" => array( "name" => "NbOfActuationsLift", "access" => "R" ),
                "0006" => array( "name" => "NbOfActuationsTilt", "access" => "R" ),
                "0007" => array( "name" => "ConfigStatus", "access" => "R" ),
                "0008" => array( "name" => "CurPosLiftPercent", "access" => "R" ),
                "0009" => array( "name" => "CurPosTiltPercent", "access" => "R" ),
                // Settings attributes
                "0010" => array( "name" => "InstalledOpenLimitLift", "access" => "R" ),
                "0011" => array( "name" => "InstalledClosedLimitLift", "access" => "R" ),
                "0012" => array( "name" => "InstalledOpenLimitTilt", "access" => "R" ),
                "0013" => array( "name" => "InstalledClosedLimitTilt", "access" => "R" ),
                "0014" => array( "name" => "VelocityLift", "access" => "RW" ),
                "0015" => array( "name" => "AccelTimeLift", "access" => "RW" ),
                "0016" => array( "name" => "DecelTimeLift", "access" => "RW" ),
                "0017" => array( "name" => "Mode", "access" => "RW" ),
                "0018" => array( "name" => "IntermSetpointsLift", "access" => "RW" ),
                "0019" => array( "name" => "IntermSetpointsTilt", "access" => "RW" ),
            ),
            "commands" => array(
                "00" => array( "name" => "UpOpen" ),
                "01" => array( "name" => "DownClose" ),
                "02" => array( "name" => "Stop" ),
                "04" => array( "name" => "GotoLiftVal" ),
                "05" => array( "name" => "GotoLiftPercent" ),
                "07" => array( "name" => "GotoTiltVal" ),
                "08" => array( "name" => "GotoTiltPercent" ),
            ),
        ),
        "0201" => array(
            "name" => "Thermostat",
            "attributes" => array(
                "0000" => array( "name" => "LocalTemperature", "access" => "RP", "dataType" => 0x29 ),
                "0002" => array( "name" => "Occupancy", "access" => "R", "dataType" => 0x18 ),
                "0012" => array( "name" => "OccupiedHeatingSetpoint", "access" => "RW", "dataType" => 0x29 ),
                "0014" => array( "name" => "UnoccupiedHeatingSetpoint", "access" => "RW", "dataType" => 0x29 ),
            ),
        ),
        "0202" => array(
            "name" => "Fan control",
        ),
        "0204" => array(
            "name" => "Thermostat user interface",
        ),
        "0300" => array(
            "name" => "Color control",
            "attributes" => array(
                "0000" => array( "name" => "CurrentHue", "access" => "R", "dataType" => 0x20 ), // uint8
                "0001" => array( "name" => "CurrentSaturation", "access" => "R", "dataType" => 0x20 ), // uint8
                "0003" => array( "name" => "CurrentX", "access" => "R", "dataType" => 0x21 ), // uint16
                "0004" => array( "name" => "CurrentY", "access" => "R", "dataType" => 0x21 ), // uint16
                "0007" => array( "name" => "ColorTemperatureMireds", "access" => "R", "dataType" => 0x21 ), // uint16
                "0008" => array( "name" => "ColorMode", "access" => "R", "dataType" => 0x30 ), // enum8
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
            "attributes" => array(
                "0000" => array( "name" => "MeasuredValue", "access" => "R" ),
            ),
        ),
        "0401" => array(
            "name" => "Illuminance Level Sensing",
        ),
        "0405" => array(
            "name" => "Relative Humidity",
            "attributes" => array(
                // Relative Humidity Measurement Information
                "0000" => array( "name" => "MeasuredValue", "access" => "R" ),
                "0001" => array( "name" => "MinMeasuredValue", "access" => "R" ),
                "0002" => array( "name" => "MaxMeasuredValue", "access" => "R" ),
                "0003" => array( "name" => "Tolerance", "access" => "R" ),
            ),
        ),
        "0406" => array(
            "name" => "Occupancy Sensing",
            "attributes" => array(
                "0000" => array( "name" => "Occupancy", "access" => "R", "dataType" => 0x18 ), // map8
                "0001" => array( "name" => "OccupancySensorType", "access" => "R", "dataType" => 0x30 ), // enum8
            ),
        ),
        "0500" => array(
            "name" => "IAS Zone",
            "attributes" => array(
                "0000" => array( "name" => "ZoneState", "access" => "R" ),
                "0001" => array( "name" => "ZoneType", "access" => "R" ),
                "0002" => array( "name" => "ZoneStatus", "access" => "R" ),
            ),
            // Commands received
            // Commands generated
            "commandsG" => array(
                "00" => array( "name" => "Zone Status Change Notification" ),
                "01" => array( "name" => "Zone Enroll Request" ),
            ),
        ),
        "0501" => array(
            "name" => "IAS ACE",
        ),
        "0502" => array(
            "name" => "IAS WD",
        ),
        "0702" => array(
            "name" => "Metering (Smart Energy)",
            "attributes" => array(
                // Reading information attribute set
                "0000" => array( "name" => "CurrentSummationDelivered", "access" => "R" ),
                "0001" => array( "name" => "CurrentSummationReceived", "access" => "R" ),
                "0002" => array( "name" => "CurrentMaxDemandDelivered", "access" => "R" ),
                "0003" => array( "name" => "CurrentMaxDemandReceived", "access" => "R" ),
                "0006" => array( "name" => "PowerFactor", "access" => "R" ),

                // Meter status attribute set
                "0200" => array( "name" => "Status", "access" => "R" ),

                // Formatting set
                "0300" => array( "name" => "UnitofMeasure", "access" => "R" ),
                "0301" => array( "name" => "Multiplier", "access" => "R" ),
                "0302" => array( "name" => "Divisor", "access" => "R" ),
                "0303" => array( "name" => "SummationFormatting", "access" => "R" ),
                "0306" => array( "name" => "MeteringDeviceType", "access" => "R" ),
            ),
            // Commands received: none
            // Commands generated
            // "commands" => array(
                // "00" => array( "name" => "Get Profile" ),
                // "01" => array( "name" => "Request Mirror" ),
                // "02" => array( "name" => "Mirror Removed" ),
                // "03" => array( "name" => "Request Fast Poll Mode" ),
            // ),
        ),
        "0B04" => array( // Electrical measurement cluster
            "name" => "Electrical measurement",
            "attributes" => array(
                "0000" => array( "name" => "Measurement Type", "access" => "R" ),

                "0300" => array( "name" => "AC Frequency", "access" => "R" ),

                "0505" => array( "name" => "RMS Voltage", "access" => "R", "dataType" => 0x21 ),
                "0508" => array( "name" => "RMS Current", "access" => "R", "dataType" => 0x21 ),
                "050B" => array( "name" => "Active Power", "access" => "R", "dataType" => 0x29 ),

                "0602" => array( "name" => "AC Current Multiplier", "access" => "R" ),
                "0603" => array( "name" => "AC Current Divisor", "access" => "R" ),
                "0604" => array( "name" => "AC Power Multiplier", "access" => "R" ),
                "0605" => array( "name" => "AC Power Divisor", "access" => "R" ),
            ),
            // "commands" => array(
            //     // "cmd1" => array( "name" => "GetProfile" ),
            //     // "cmd2" => array( "name" => "RequestMirrorResponse" ),
            //     // "cmd3" => array( "name" => "MirrorRemoved" ),
            //     // "cmd4" => array( "name" => "RequestFastPollMode" ),
            // ),
        ),
        "1000" => array(
            "name" => "Touchlink",
            "commands" => array(
                "00" => array( "name" => "Scan Request" ),
                "02" => array( "name" => "Dev Info Req" ),
                "06" => array( "name" => "Identify Req" ),
                "07" => array( "name" => "Reset To Factory Req" ),
                "10" => array( "name" => "Network Start Req" ),
                "12" => array( "name" => "Network Join Router Req" ),
                "14" => array( "name" => "Network Join End Device Req" ),
                "16" => array( "name" => "Network Update Req" ),
                "41" => array( "name" => "Get Group Id Req" ),
                "42" => array( "name" => "Get EP List Req" ),
            ),
        ),
    );

    /* Returns cluster name from its ID */
    function zbGetZCLClusterName($clustId) {
        global $zbClusters;
        $clustId = strtoupper($clustId);
        if (!array_key_exists($clustId, $zbClusters))
            return "Unknown-".$clustId; // Unknown cluster
        return $zbClusters[$clustId]['name'];
    }

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
        return "Unknown-".$id;
    }

    /* Based on ZCL spec.
       Returns cluster specific command from $clustId-$cmdId or false if unknown */
    function zbGetZCLClusterCmd($clustId, $cmdId)
    {
        global $zbClusters;

        $clustId = strtoupper($clustId);
        $cmdId = strtoupper($cmdId);

        if (!array_key_exists($clustId, $zbClusters))
            return false; // Unknown cluster
        if (!isset($zbClusters[$clustId]['commands']))
            return false; // No commands defined
        if (!array_key_exists($cmdId, $zbClusters[$clustId]['commands']))
            return false; // Unknown command
        return $zbClusters[$clustId]['commands'][$cmdId];
    }

    /* Based on ZCL spec.
       Returns cluster specific command name from $clustId-$cmdId. */
    function zbGetZCLClusterCmdName($clustId, $cmdId) {
        $cmd = zbGetZCLClusterCmd($clustId, $cmdId);
        if ($cmd === false)
            return "Unknown-".$clustId."-".$cmdId;
        return $cmd['name'];
    }

    /* Based on ZCL spec.
       Returns attribute infos for $clustId-$attrId or false if unknown */
    function zbGetZCLAttribute($clustId, $attrId) {
        global $zbClusters;

        $clustId = strtoupper($clustId);
        $attrId = strtoupper($attrId);

        if (!array_key_exists($clustId, $zbClusters))
            return false; // Unknown cluster
        if (!isset($zbClusters[$clustId]['attributes']))
            return false; // No attributes defined
        if (!array_key_exists($attrId, $zbClusters[$clustId]['attributes']))
            return false; // Unknown attribute
        return $zbClusters[$clustId]['attributes'][$attrId];
    }

    /* Based on ZCL spec.
       Returns cluster specific command name from $clustId-$cmdId. */
    function zbGetZCLAttributeName($clustId, $attrId) {
        $attr = zbGetZCLAttribute($clustId, $attrId);
        if ($attr === false)
            return "Unknown-".$clustId."-".$attrId;
        return $attr['name'];
    }
?>
