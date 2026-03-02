<?php
    /*
     * Display content of AbeillePdm-AbeilleX.json
     * Tcharp38
     */

    /* JSON is given thru cmd line */
    $pdmPath = "";
    for ($i = 1; $i < $argc; $i++) {
        $pdmPath = $argv[$i];
    }

    $jsonContent = file_get_contents($pdmPath);
    $content = json_decode($jsonContent, true);
    $pdms = $content['pdms'];
    foreach ($pdms as $pdmId => $pdm) {
        $size = $pdm['size'];
        $data = $pdm['data'];
        echo "$pdmId: $size $data\n";

        $len = strlen($data);
        if ($pdmId == "F101") { // Child table
            if ($len % 40) {
                echo "  ERROR: Unexpected size. Not a multiple of 20B\n";
            }
            $nbEntries = $len / 40;
            echo "  Child table: $nbEntries entries\n";
            for ($i = 0; $i < $len; ) {
                // zps_tsNwkSlistNode sNode;  /**< Single linked list node */
                // uint16 u16Lookup;         /**< Extended address */
                // uint16 u16NwkAddr;         /**< Network address */
                // uint8  u8TxFailed;         /**< Transmit failed count */
                // uint8  u8LinkQuality;      /**< Link Quality indication */
                // uint8  u8Age;    /**< Router age (in link status periods) */
                // uint8 u8ZedTimeoutindex;    /* index into the timeout const table */
                // int8    i8TXPower;          /** < TX Power in dBm last used for this device */
                // uint8   u8MacID;            /** < Mac ID for this device */
                // uint8 au8Field[2];                $i += 8; // Skip 4 Bytes
                // + 2B to align on 20 Bytes total.. why ??

                $i += 8; // Skip sNode/4 Bytes
                $i += 4; // Skip u16Lookup/2 Bytes

                $nwkAddr = substr($data, $i, 4);
                $i += 4; // Skip u16NwkAddr/2 Bytes

                $i += 2; // Skip u8TxFailed/1 Byte
                $i += 2; // Skip u8LinkQuality/1 Byte

                $age = substr($data, $i, 2);
                $i += 2; // Skip u8Age/1 Byte

                $i += 2; // Skip u8ZedTimeoutindex/1 Byte
                $i += 2; // Skip i8TXPower/1 Byte
                $i += 2; // Skip u8MacID/1 Byte
                $i += 4; // Skip au8Field/2 Bytes
                $i += 8; // Skip 2 additional bytes

                echo "  Addr=$nwkAddr, Age=$age\n";
            }
        } else if ($pdmId == "F102") { // SHORT_ADDRESS_MAP
            if ($len % 4) { // Each entry is 16-bits/2Bytes
                echo "  ERROR: Unexpected size. Not a multiple of 2B\n";
            }
            $nbEntries = $len / 4;
            echo "  SHORT_ADDRESS_MAP: $nbEntries entries\n";
            for ($i = 0; $i < $len; ) {
                $nwkAddr = substr($data, $i, 4);
                echo "  Addr=$nwkAddr\n";
                $i += 4; // Skip u16NwkAddr/2 Bytes
            }
        } else if ($pdmId == "F103") { // NWK_ADDRESS_MAP
            if ($len % 16) { // Each entry is 64-bits/8Bytes
                echo "  ERROR: Unexpected size. Not a multiple of 8B\n";
            }
            $nbEntries = $len / 16;
            echo "  NWK_ADDRESS_MAP: $nbEntries entries\n";
            for ($i = 0; $i < $len; ) {
                $ieeeAddr = substr($data, $i, 16);
                echo "  Addr=$ieeeAddr\n";
                $i += 16; // Skip ieeeAddr/8 Bytes
            }
        }
    }
?>
