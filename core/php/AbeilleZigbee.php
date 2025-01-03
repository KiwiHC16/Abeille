
<?php
    /*
     * Zigbee specific functions.
     * Mainly Zigbee Cluster Library spec (ZCL).
     */

    /* Format attribute value.
        Return hex string formatted value according to its type.
        WARNING: Returned hex string must still be reversed if >= 2B */
    function zbFormatData($valIn, $type) {
        // logMessage('debug', "zbFormatData(".$valIn.", ".$type.")");
        // Note: Slider/select conversion to be done in execute() function
        if (substr($valIn, 0, 7) == "#slider") {
            $valIn = substr($valIn, 7, -1); // Extracting decimal value
        } else if (substr($valIn, 0, 7) == "#select") {
            $valIn = substr($valIn, 7, -1); // Extracting decimal value
        } else if (substr($valIn, 0, 2) == "0x") {
            $valIn = hexdec($valIn);
        }
        if ($valIn === false)
            return false;

        $valOut = '';
        switch ($type) {
        case 'bool':
        case '10':
        case '18': // map8
        case '20': // uint8
        case '30': // enum8
            $valOut = sprintf("%02X", $valIn);
            break;
        case 'uint16':
        case '21':
        case '31': // enum16
            $valOut = sprintf("%04X", $valIn);
            break;
        case 'uint24':
        case '22':
            $valOut = sprintf("%06X", $valIn);
            break;
        case 'uint32':
        case '23':
            $valOut = sprintf("%08X", $valIn);
            break;
        case 'uint48':
        case '25':
            $valOut = sprintf("%012X", $valIn);
            break;
        case '28': // int8
            $valOut = $this->signed2Hex($valIn, 1);
            break;
        case '29': // int16
        case 'int16':
            $valOut = $this->signed2Hex($valIn, 2);
            break;
        case '2A': // int24
            $valOut = $this->signed2Hex($valIn, 3);
            break;
        case '2B': // int32
        case 'int32':
            $valOut = $this->signed2Hex($valIn, 4);
            break;
        case '39': // Single precision
            $valOut = strrev(unpack('h*', pack('f', $valIn))[1]);
            break;
        case '42': // string
            $len = sprintf("%02X", strlen($valIn));
            $valOut = $len.bin2hex($valIn);
            // logMessage('debug', "len=".$len.", valOut=".$valOut);
            break;
        default:
            logMessage('error', "  zbFormatData($valIn, type=$type) => Type non supporté");
            $valOut = '12'; // Fake value
        }

        logMessage('debug', "  zbFormatData($valIn, type=$type) => valOut=$valOut");
        return $valOut;
    }
?>
