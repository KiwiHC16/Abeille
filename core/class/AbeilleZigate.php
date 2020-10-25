<?php

    /*
     * Zigate standalone PHP library.
     */

    /* Errors reporting: uncomment below lines for debug */
    // error_reporting(E_ALL);
    // ini_set('error_log', '/var/www/html/log/AbeillePHP');
    // ini_set('log_errors', 'On');

    $curLogLevel = 0;
    $logFile = '';

    /* Library config for logs */
    function zg_SetConf($lFile = '')
    {
        global $curLogLevel, $logFile;
        $curLogLevel = AbeilleTools::getPluginLogLevel('Abeille');
        $logFile = $lFile;
    }

    /* Log function.
       '\n' is automatically added at end of line.
       WARNING: A call to 'zg_SetConf()' is expected once to allow logs. */
    function zg_Log($logLevel, $msg)
    {
        global $logFile, $curLogLevel;

        if ($logFile == '')
            return; // Can't log. Config not done
        if (AbeilleTools::getNumberFromLevel($logLevel) > $curLogLevel)
            return; // Nothing to do

        $logDir = __DIR__.'/../../../../log/';
        /* TODO: How to align logLevel width for better visual aspect ? */
        file_put_contents($logDir.$logFile, '['.date('Y-m-d H:i:s').']['.$logLevel.'] '.$msg."\n", FILE_APPEND);
    }

    /* Write 'zgMsg' string to 'zgF' file desc.
       Returns: 0=OK, -1=ERROR */
    function zg_WriteMsgString($zgF, $zgMsg)
    {
        // zg_Log("zg_WriteMsgString(" . $zgMsg . ")");
        if ($zgF == FALSE) {
            // zg_Log("zg_WriteMsgString() END: fopen ERROR");
            return -1;
        }
        $status = fwrite($zgF, pack("H*", $zgMsg));
        fflush($zgF);
        if ($status == FALSE) {
            // zg_Log("zg_WriteMsgString() END: fwrite ERROR");
            return -1;
        }
        // zg_Log("zg_WriteMsgString() END");
        return 0;
    }

    /* Read & decode Zigate frame
       Returns: 0=OK, -1=ERROR */
    function zg_ReadMsgString($zgF, &$zgMsg)
    {
        // zg_Log("zg_ReadMsgString()");
        if ($zgF == FALSE) {
            // zg_Log("zg_ReadMsgString() ERROR: bad desc for reading");
            return -1;
        }
        $decode = false;
        $zgMsg = "";
        while (true) {
            $c = fread($zgF, 01);
            $c = strtoupper(bin2hex($c));

            // zg_Log("  Got " . $c . "");
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
            // zg_Log("  zgMsg=" . $zgMsg . "");
        }
        // zg_Log("zg_ReadMsgString() END: zgMsg=" . $zgMsg . "");
        return 0;
    }

    /* Send "Get Version" to zgPort to get FW version.
       Returns: 0=OK, -1=ERROR */
    function zg_GetVersion($zgPort, &$version)
    {
        // zg_Log("zg_GetVersion()");
        $version = 0;
        $zgF = fopen($zgPort, "w+"); // Zigate input/output
        if ($zgF == FALSE) {
            zg_Log("error", "zg_GetVersion(): ERREUR d'accès à la Zigate sur port " . $zgPort);
            return -1;
        }

        zg_Log('debug', 'Interrogation de la Zigate sur port '.$zgPort);
        $status = zg_WriteMsgString($zgF, "01021010021002101003"); // Sending "Get Version" command
        $zgMsg = "";
        if ($status == 0) {
            $status = zg_ReadMsgString($zgF, $zgMsg); // Expecting 8000 'status' frame
        }
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8000") {
                zg_Log('debug', 'Mauvaise réponse. 8000 attendu.');
                $status = -1;
            }
        }
        if ($status == 0)
            $status = zg_ReadMsgString($zgF, $zgMsg); // Expecting 8010 'Version list' frame
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8010") {
                zg_Log('debug', 'Mauvaise réponse. 8010 attendu.');
                $status = -1;
            } else {
                $version = substr($zgMsg, 14, 4);
                zg_Log('debug', 'FW version '.$version);
            }
        }
        return $status;
    }
?>
