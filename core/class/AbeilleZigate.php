<?php

    /*
     * Zigate standalone PHP functions.
     */

    /* Errors reporting: uncomment below lines for debug */
    // error_reporting(E_ALL);
    // ini_set('error_log', '/var/www/html/log/AbeillePHP');
    // ini_set('log_errors', 'On');

    /* Log to JavaScript console for debug purposes */
    // function zg_Debug($DebugMsg)
    // {
        // $f = fopen("/var/www/html/log/AbeillePiZigate", "a");
        // fwrite($f, $DebugMsg);
        // fclose($f);
    // }

    /* Write 'zgMsg' string to 'zgF' file desc.
       Returns: 0=OK, -1=ERROR */
    function zg_WriteMsgString($zgF, $zgMsg)
    {
        // zg_Debug("zg_WriteMsgString(" . $zgMsg . ")\n");
        if ($zgF == FALSE) {
            // zg_Debug("zg_WriteMsgString() END: fopen ERROR\n");
            return -1;
        }
        $status = fwrite($zgF, pack("H*", $zgMsg));
        fflush($zgF);
        if ($status == FALSE) {
            // zg_Debug("zg_WriteMsgString() END: fwrite ERROR\n");
            return -1;
        }
        // zg_Debug("zg_WriteMsgString() END\n");
        return 0;
    }

    /* Read & decode Zigate frame
       Returns: 0=OK, -1=ERROR */
    function zg_ReadMsgString($zgF, &$zgMsg)
    {
        // zg_Debug("zg_ReadMsgString()\n");
        if ($zgF == FALSE) {
            // zg_Debug("zg_ReadMsgString() ERROR: bad desc for reading\n");
            return -1;
        }
        $decode = false;
        $zgMsg = "";
        while (true) {
            $c = fread($zgF, 01);
            $c = bin2hex($c);

            // zg_Debug("  Got " . $c . "\n");
            if ($c == "01") { // Start of frame ?
                $zgMsg = "";
            } else if ($c == "03") { // End of frame ?
                break;
            } else if ($c == "02") {
                $decode = true; // Next char must be decoded
            } else {
                if ($decode) {
                    $zgMsg .= sprintf("%02X", (hexdec($c) ^ 0x10));
                    $decode = false;
                } else {
                    $zgMsg .= $c;
                }
            }
            // zg_Debug("  zgMsg=" . $zgMsg . "\n");
        }
        // zg_Debug("zg_ReadMsgString() END: zgMsg=" . $zgMsg . "\n");
        return 0;
    }

    /* Send "Get Version" to zgPort to get FW version.
       Returns: 0=OK, -1=ERROR */
    function zg_GetVersion($zgPort, &$version)
    {
        // zg_Debug("zg_GetVersion()\n");
        $version = 0;
        $zgF = fopen($zgPort, "w+"); // Zigate input/output
        if ($zgF == FALSE) {
            // zg_Debug("zg_GetVersion(): ERREUR d'accès à la Zigate sur port " . $zgPort . "\n");
            return -1;
        }

        /* TODO: Log file should be configurable to keep this lib standalone */
        log::add('AbeillePiZigate', 'debug', 'Interrogation de la Zigate sur port ' . $zgPort);
        $status = zg_WriteMsgString($zgF, "01021010021002101003"); // Sending "Get Version" command
        $zgMsg = "";
        if ($status == 0) {
            $status = zg_ReadMsgString($zgF, $zgMsg); // Expecting 8000 'status' frame
        }
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8000") {
                // zg_Debug("zg_GetVersion(): Was expecting 8000. Got " . $zgMsgType . "\n");
                log::add('AbeillePiZigate', 'debug', 'Mauvaise réponse. 8000 attendu.');
                $status = -1;
            }
        }
        if ($status == 0)
            $status = zg_ReadMsgString($zgF, $zgMsg); // Expecting 8010 'Version list' frame
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8010") {
                // zg_Debug("zg_GetVersion(): Was expecting 8010. Got " . $zgMsgType . "\n");
                log::add('AbeillePiZigate', 'debug', 'Mauvaise réponse. 8010 attendu.');
                $status = -1;
            } else {
                $version = substr($zgMsg, 14, 4);
                log::add('AbeillePiZigate', 'debug', 'FW version ' . $version);
            }
        }
        return $status;
    }
?>
