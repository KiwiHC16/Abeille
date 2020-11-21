
<?php
    /*
     * Zigate constants
     */

    /* Returns Zigate message name based on given '$msgType' */
    function zgGetMsgByType($msgType)
    {
        /* Type and name of zigate messages */
        $zgMessages = array(
            "0200" => "Save record request",
            "0201" => "Load record request",
            "0202" => "Delete all records",
            "0300" => "Host PDM available request",
            "0302" => "PDM loaded",

            "8001" => "Log message",
            "8002" => "Data indication",
            "8006" => "Non “Factory new” Restart",
            "8007" => "“Factory New” Restart",
            "8009" => "Network state response",
            "8028" => "Authenticate response",
            "802B" => "User descriptor notify",
            "802C" => "User descriptor response",
            "8031" => "Unbind response",
            "8034" => "Complex descriptor response",
            "8035" => "PDM event code",
            "8042" => "Node descriptor response",
            "8044" => "Power descriptor response",
            "8046" => "Match descriptor response",
            "8047" => "Management leave response",
            "804B" => "System server discovery response",
            "8061" => "View group response",
            "80A1" => "Add scene response",
            "80A2" => "Remove scene response",
            "8110" => "Write attribute response",
            "8140" => "Configure reporting response",
            "8200" => "Save record request respose",
            "8201" => "Load record response",
            "8300" => "Host PDM available response",
            "8401" => "Zone status change notification",
            "8531" => "Complex descriptor response",
        );

        if (array_key_exists($msgType, $zgMessages))
            return $zgMessages[$msgType];
        return "Type ".$msgType." inconnu";
    }


    /* Returns Zigate PDM event desc based on given '$code' */
    function zgGetPDMEvent($code)
    {
        /* PDM event codes & desc.
           Returned by command 0x8035 */
       $zgPDMEvents = array(
            "00" => "WEAR_COUNT_TRIGGER_VALUE_REACHED",
            "01" => "DESCRIPTOR_SAVE_FAILED",
            "02" => "PDM_NOT_ENOUGH_SPACE",
            "03" => "LARGEST_RECORD_FULL_SAVE_NO_LONGER_POSSIBLE",
            "04" => "SEGMENT_DATA_CHECKSUM_FAIL",
            "05" => "SEGMENT_SAVE_OK",
            "06" => "EEPROM_SEGMENT_HEADER_REPAIRED",
            "07" => "SYSTEM_INTERNAL_BUFFER_WEAR_COUNT_SWAP",
            "08" => "SYSTEM_DUPLICATE_FILE_SEGMENT_DETECTED",
            "09" => "SYSTEM_ERROR",
            "0A" => "SEGMENT_PREWRITE",
            "0B" => "SEGMENT_POSTWRITE",
            "0C" => "SEQUENCE_DUPLICATE_DETECTED",
            "0D" => "SEQUENCE_VERIFY_FAIL",
            "0E" => "PDM_SMART_SAVE",
            "0F" => "PDM_FULL_SAVE"
        );

        if (array_key_exists($code, $zgPDMEvents))
            return $zgPDMEvents[$code];
        return "Code PDM ".$code." inconnu";
    }

    /* Returns zigbee profile name based on given 'profId' */
    function zgGetProfile($profId)
    {
        /* List of known zigbee profiles */
        $profilesTable = array (
            '0104'=>'ZigBee Home Automation (ZHA)',
            'C05E'=>'Zigbee Link Layer (ZLL)',
        );

        if (array_key_exists($profId, $profilesTable))
            return $profilesTable[$profId];
        return "Profil ".$profId." inconnu";
    }

    /* Returns zigbee device name based on given 'profId/devId' couple. */
    function zgGetDevice($profId, $devId)
    {
        $profId = strtoupper($profId);
        $devId = strtoupper($devId);

        /* List of known devices per profile */
        $devicesTable = array (
            /* ZHA */
            '0104' => array(
                '0000'=>'On/Off Switch',
                '0001'=>'Level Control Switch',
                '0002'=>'On/Off Output',
                '0003'=>'Level Controllable Output',
                '0004'=>'Scene Selector',
                '0005'=>'Configuration Tool',
                '0006'=>'Remote Control',
                '0007'=>'Combined Interface',
                '0008'=>'Range Extender',
                '0009'=>'Mains Power Outlet',
                '000A'=>'Door Lock',
                '000B'=>'Door Lock Controller',
                '000C'=>'Simple Sensor',
                '000D'=>'Consumption Awareness Device',

                '0050'=>'Home Gateway',
                '0051'=>'Smart Plug',
                '0052'=>'White Goods',
                '0053'=>'Meter Interface',

                // Lighting
                '0100'=>'On/Off Light',
                '0101'=>'Dimmable Light',
                '0102'=>'Color Dimmable Light',
                '0103'=>'On/Off Light Switch',
                '0104'=>'Dimmer Switch',
                '0105'=>'Color Dimmer Switch',
                '0106'=>'Light Sensor',
                '0107'=>'Occupency Sensor',

                // Legrand
                '010A'=>'Legrand xxxx',

                // Closures
                '0200'=>'Shade',
                '0201'=>'Shade Controller',
                '0202'=>'Window Covering Device',
                '0203'=>'Window Covering Controller',

                // HVAC
                '0300'=>'Heating/Cooling Unit',
                '0301'=>'Thermostat',
                '0302'=>'Temperature Sensor',
                '0303'=>'Pump',
                '0304'=>'Pump Controller',
                '0305'=>'Pressure Sensor',
                '0306'=>'Flow Sensor',
                '0307'=>'Mini Split AC',

                // Intruder Alarm Systems
                '0400'=>'IAS Control and Indicating Equipment',
                '0401'=>'IAs Ancillary Equipment',
                '0402'=>'IAS Zone',
                '0403'=>'IAS Warning Device',

                // From Xiaomi
                '5F01'=>'Xiaomi Temperature',

                // From OSRAM investigation
                '0810'=>'OSRAM Switch',

                // From Legrand investigation
                '0B04'=>'Electrical Measurement', // Attribut Id: 0x50B - Active Power
            ),
            /* ZLL */
            'C05E' => array(
                // Lighting devices
                '0000'=>'On/Off light',
                '0010'=>'On/Off plug-in unit',
                '0100'=>'Dimmable light',
                '010A'=>'Proprio Prise Ikea',   // Pas dans le standard mais remonté par prise Ikea
                '0110'=>'Dimmable plug-in unit',
                '0200'=>'Color light',
                '0210'=>'Extended color light',
                '0220'=>'Color temperature light',
                // Conroller devices
                '0800'=>'Color controller',
                '0810'=>'Color scene controller',
                '0820'=>'Non-color controller',
                '0830'=>'Non-color scene controller',
                '0840'=>'Control bridge',
                '0850'=>'On/Off sensor',
            ),
        );

        if (!isset($devicesTable[$profId]))
            return "Profil ".$profId." inconnu";
        if (!isset($devicesTable[$profId][$devId]))
            return "Device ".$devId." inconnu";
        return $devicesTable[$profId][$devId];
    }

    /* Returns zigbee cluster name based on given 'clustId'. */
    function zgGetCluster($clustId)
    {
        $clustId = strtoupper($clustId);

        /* List of known devices per profile */
        $clustersTable = array (
            "0000" => "General: Basic",
            "0001" => "General: Power Config",
            "0002" => "General: Temperature Config",
            "0003" => "General: Identify",
            "0004" => "General: Groups",
            "0005" => "General: Scenes",
            "0006" => "General: On/Off",
            "0007" => "General: On/Off Config",
            "0008" => "General: Level Control",
            "0009" => "General: Alarms",
            "000A" => "General: Time",
            "000B" => "General: RSSI Location",
            "000C" => "General: Analog Input (Basic)",
            "000D" => "General: Analog Output (Basic)",
            "000E" => "General: Analog Value (Basic)",
            "000F" => "General: Binary Input (Basic)",
            "0010" => "General: Binary Output (Basic)",
            "0011" => "General: Binary Value (Basic)",
            "0012" => "General: Multistate Input (Basic)",
            "0013" => "General: Multistate Output (Basic)",
            "0014" => "General: Multistate Value (Basic)",
            "0015" => "General: Commissioning",
            "0019" => "General: Inconnu par abeille",
            "0020" => "General: Poll Control",
            "0019" => "General: OTA",
            "0100" => "Closures: Shade Configuration",
            "0101" => "General: Door Lock",
            "0200" => "Pump Configuration and Control",
            "0201" => "HVAC: Thermostat",
            "0202" => "HVAC: Fan Control",
            "0203" => "HVAC: Dehumidification Control",
            "0204" => "HVAC: Thermostat User Interface Configuration",
            "0300" => "Lighting: Color Control",
            "0301" => "Lighting: Ballast Configuration",
            "0400" => "Measurement: Illuminance",
            "0401" => "Measurement: Illuminance level sensing",
            "0402" => "Measurement: Temperature",
            "0403" => "Measurement: Pression atmosphérique",
            "0404" => "Measurement: Flow Measurement",
            "0405" => "Measurement: Humidity",
            "0406" => "Measurement: Occupancy Sensing",
            "0500" => "Security & Safety: IAS Zone (IAS_ZONE_CLUSTER)",
            "0501" => "Security & Safety: IAS ACE (IAS_ACE_CLUSTER)",
            "0502" => "Security & Safety: IAS WD",
            "0702" => "Smart Energy: Metering",
            "0B05" => "Misc: Diagnostics",
            "0B04" => "Legrand private",
            "0B05" => "Diagnostics (DIAGNOSTICS_CLUSTER)",
            "1000" => "ZLL: Commissioning",
            "FC01" => "Legrand private",
            "FC41" => "Legrand private",
            "FF01" => "Xiaomi private",
            "FF02" => "Xiaomi private",
            "FFFF" => "Xiaomi private"
        );
        if (array_key_exists($clustId, $clustersTable))
            return $clustersTable[$clustId];
        return "Cluster ".$clustId." inconnu";
    }
?>
