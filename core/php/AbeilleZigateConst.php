
<?php
    /*
     * Zigate constants
     */

    /* Returns Zigate message name based on given '$msgType' */
    function zgGetMsgByType($msgType)
    {
        /* Type and name of zigate messages (mainly those currently unsupported) */
        $zgMessages = array(
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
?>
