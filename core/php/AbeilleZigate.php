<?php

    /*
     * Zigate standalone PHP library.
     */

    $curLogLevel = 0;
    $logFile = ''; // Absolut path
    require_once __DIR__.'/../../resources/AbeilleDeamon/lib/AbeilleTools.php';
    require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    require_once 'AbeilleLog.php'; // logMessage()

    /* Library setup.
       If 'lFile' is not absolut, default Jeedom path is added. */
    /* TODO: To be revisited. No longer required to configure log */
    function zgSetConf($lFile = '') {
        // global $curLogLevel, $logFile;
        // $curLogLevel = AbeilleTools::getPluginLogLevel('Abeille');
        // if (substr($lFile, 0, 1) != "/") // Not absolut path ?
        //     $logFile = __DIR__.'/../../../../log/'.$lFile;
        // else
        //     $logFile = $lFile;
    }

    /* Log function.
       '\n' is automatically added at end of line.
       WARNING: A call to 'zgSetConf()' is expected once to allow logs. */
    // function zgLog($logLevel, $msg)
    // {
    //     global $logFile, $curLogLevel;

    //     if ($logFile == '')
    //         return; // Can't log. Config not done
    //     if (AbeilleTools::getNumberFromLevel($logLevel) > $curLogLevel)
    //         return; // Nothing to do

    //     $logLevel = strtolower(trim($logLevel));
    //     if ($logLevel == "warning")
    //         $logLevel = "warn";
    //     /* Note: sprintf("%-5.5s", $logLevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
    //     file_put_contents($logFile, '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] '.$msg."\n", FILE_APPEND);
    // }

    /* Write message 'zgMsg' (string) to 'zgF' file desc.
       Returns: 0=OK, -1=ERROR */
    function zgWrite($zgF, $zgMsg)
    {
        logMessage('debug', "zgWrite(".$zgMsg.")");
        if ($zgF == FALSE) {
            logMessage('error', "zgWrite() END: fopen ERROR");
            return -1;
        }
        $frame = zgMsgToFrame($zgMsg);
        $status = fwrite($zgF, pack("H*", $frame));
        fflush($zgF);
        if ($status == FALSE) {
            logMessage('error', "zgWrite() END: fwrite ERROR");
            return -1;
        }
        // logMessage("zgWrite() END");
        return 0;
    }

    /* Write message 'zgMsg' (string) to 'zgF' file desc.
       Returns: 0=OK, -1=ERROR */
    function zgWrite2($zgMsg) {
        logMessage('debug', "zgWrite2(".$zgMsg.")");

        $frame = zgMsgToFrame($zgMsg);
        $msg = Array(
            "type" => "msg",
            "msg" => $frame
        );

        $queue = msg_get_queue(queueKeyAssistToCmd);
        if (msg_send($queue, 1, $msg, TRUE, false) == FALSE) {
            logMessage("error", "Could not send msg to 'queueKeyAssistToCmd': msg=".json_encode($msg));
            return -1;
        }

        return 0;
    }

    /* Read frame, extract & transcode message.
       Returns: 0=OK, -1=ERROR */
    function zgRead($zgF, &$zgMsg) {
        logMessage('debug', "zgRead()");
        if ($zgF == FALSE) {
            logMessage('error', "zgRead() ERROR: bad desc for reading");
            return -1;
        }
        $decode = false;
        $zgMsg = "";
        while (true) {
            $c = fread($zgF, 01);
            $c = strtoupper(bin2hex($c));

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
        }
        logMessage('debug', '  Read='.$zgMsg);
        return 0;
    }

    /* Read frame, extract & transcode message.
       Returns: 0=OK, -1=ERROR */
    function zgRead2(&$zgMsg) {
        logMessage('debug', "zgRead2()");

        $queue = msg_get_queue(queueKeyParserToAssist);
        $max_msg_size = 2048;
        $msg_type = NULL;
        $zgMsg = "";
        while (1) {
            if (msg_receive($queue, 0, $msg_type, $max_msg_size, $zgMsg, TRUE, MSG_IPC_NOWAIT) == TRUE)
                break;
            usleep(100000); // Sleep 100ms
        }

        logMessage('debug', '  Read='.$zgMsg);
        return 0;
    }

    /* Encode given 'msg' and returns zigate frame */
    function zgMsgToFrame($msg)
    {
        $msgout = "";
        $msgsize = strlen($msg);
        for ($i = 0; $i < $msgsize; $i += 2) {
            $byte = substr($msg, $i, 2);
            if (hexdec($byte) < 0x10)
                $msgout .= sprintf("02%02X", (hexdec($byte) ^ 0x10));
            else
                $msgout .= $byte;
        }
        return "01".$msgout."03";
    }

    /* Compose message following zigate protocol.
       Minimum 1 arg = 'msgType'
       Any payload to be added as next args. */
    function zgComposeMsg($msgType)
    {
        /* TODO: Ensure msgType = 4 char string */

        $nbOfArgs = func_num_args();
        $args = func_get_args();
        $payload = "";
        for ($i = 1; $i < $nbOfArgs; $i++) {
            $payload .= $args[$i];
        }
        $payloadLen = strlen($payload) / 2;

        /* Computing checksum (msgType xor length xor payload) */
        $crc = 0;
        for ($i = 0; $i < strlen($msgType); $i += 2)
            $crc = $crc ^ hexdec(substr($msgType, $i, 2));
        $plLen = sprintf("%04X", $payloadLen);
        for ($i = 0; $i < strlen($plLen); $i += 2)
            $crc = $crc ^ hexdec(substr($plLen, $i, 2));
        for ($i = 0; $i < strlen($payload); $i += 2)
            $crc = $crc ^ hexdec(substr($payload, $i, 2));

        $msg = "";
        $msg .= $msgType; // Message type, 2 bytes
        $msg .= sprintf("%04X", $payloadLen); // Payload length, 2 bytes
        $msg .= sprintf("%02X", $crc); // Checksum, 1 byte
        $msg .= $payload; // Payload
        // logMessage('debug', 'msg='.$msg);

        return $msg;
    }

    /*
     * Zigate commands (exclusive access required)
     */

    /* Send "Get Version" to zgPort to get FW version.
       Returns: 0=OK, -1=ERROR */
    function zgGetVersion($zgPort, &$version)
    {
        logMessage('debug', "zgGetVersion()");

        $version = 0;
        $zgF = fopen($zgPort, "w+"); // Zigate input/output
        if ($zgF == FALSE) {
            logMessage("error", "zgGetVersion(): ERREUR d'accès à la Zigate sur port ".$zgPort);
            return -1;
        }

        logMessage('debug', 'Interrogation de la Zigate sur port '.$zgPort);
        $zgMsg = zgComposeMsg("0010");
        $status = zgWrite($zgF, $zgMsg); // Sending "Get Version" command
        $zgMsg = "";
        if ($status == 0) {
            $status = zgRead($zgF, $zgMsg); // Expecting 8000 'status' frame
        }
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8000") {
                logMessage('debug', 'Mauvaise réponse. 8000 attendu.');
                $status = -1;
            }
        }
        if ($status == 0)
            $status = zgRead($zgF, $zgMsg); // Expecting 8010 'Version list' frame
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8010") {
                logMessage('debug', 'Mauvaise réponse. 8010 attendu.');
                $status = -1;
            } else {
                $version = substr($zgMsg, 14, 4);
                logMessage('info', 'FW version '.$version);
            }
        }

        fclose($zgF); // Close file desc
        return $status;
    }

    /* Send "Get Devices List" (cmd 0x0015) to zgPort to get list of known devices.
       'zgDevices' is an array of known devices.
       Each device is himself an array with the following keys, with addr & ieee UPPER case
           ['id', 'addr', 'ieee', 'power, 'link']
       Returns: 0=OK, -1=ERROR */
    /* TODO: Remove this function. 0015 command seems to not be the one described */
    function zgGetDevicesList($zgPort, &$zgDevices)
    {
        logMessage('debug', "zgGetDevicesList(zgPort=".$zgPort.")");
        $zgF = fopen($zgPort, "w+"); // Zigate input/output
        if ($zgF == FALSE) {
            logMessage("error", "zgGetDevicesList(): ERREUR d'accès à la Zigate sur port " . $zgPort);
            return -1;
        }

        $zgDevices = array();
        $status = 0;

        $zgMsg = zgComposeMsg("0015");
        $status = zgWrite($zgF, $zgMsg); // Sending "Get Devices List" command
        if ($status == 0) {
            $status = zgRead($zgF, $zgMsg); // Expecting 8000 'status' frame
        }
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8000") {
                logMessage('error', 'Mauvaise réponse. 8000 attendu.');
                $status = -1;
            }
        }

        if ($status == 0) {
            /* Get Devices List response
                <device list – data each entry is 13 bytes>
                    <ID: uint8_t>
                    <Short address: uint16_t>
                    <IEEE address: uint64_t>
                    <Power source: bool_t> 0 – battery 1- AC power
                    <LinkQuality : uint8_t> 1-255 */
            $status = zgRead($zgF, $zgMsg); // Expecting 8015 frame
            if ($status == 0) {
                $zgMsgType = substr($zgMsg, 0, 4);
                if ($zgMsgType != "8015") {
                    logMessage('error', 'Mauvaise réponse. 8015 attendu.');
                    $status = -1;
                } else {
                    $plSize = (strlen($zgMsg) / 2) - 10; // Payload size (nBytes - 10)
                    $nbOfDev = $plSize / 13; // Number of devices
                    for ($i = 4 + 4 + 2, $devNb = 0; $devNb < $nbOfDev; $devNb++) {
                        $dev = array();
                        $dev['id'] = substr($zgMsg, $i, 2); $i += 2;
                        $dev['addr'] = strtoupper(substr($zgMsg, $i, 4)); $i += 4;
                        $dev['ieee'] = strtoupper(substr($zgMsg, $i, 16)); $i += 16;
                        $dev['power'] = substr($zgMsg, $i, 2); $i += 2;
                        $dev['link'] = substr($zgMsg, $i, 2); $i += 2;
                        // logMessage('debug', 'id='.$dev['id'].', addr='.$dev['addr'].', ieee='.$dev['ieee']);
                        $zgDevices[] = $dev;
                    }
                    /* Note: There should be 1 more byte for RSSI */
                }
            }
        }

        fclose($zgF); // Close file desc
        return $status;
    }

    /* Send "Remove Device" (cmd 0x0026) to 'zgPort'.
       WARNING: Zigate must be available for exclusive access !
       Returns: 0=OK, -1=ERROR */
    /* TODO: Remove this function. Not functional */
    function zgRemoveDevice($zgPort, $devAddr, $devIEEE)
    {
        logMessage('debug', "zgRemoveDevice(zgPort=".$zgPort.")");
        $zgF = fopen($zgPort, "w+"); // Zigate input/output
        if ($zgF == FALSE) {
            logMessage("error", "zgRemoveDevice(): ERREUR d'accès à la Zigate sur port ".$zgPort);
            return -1;
        }

        $zgDevices = array();
        $status = 0;

        // $zgMsg = zgComposeMsg("0026", $devAddr, $devIEEE);
        /* 0x004C	Leave Request
            <extended address: uint64_t>
            <Rejoin: uint8_t>
            <Remove Children: uint8_t>
            Rejoin:
                0 = Do not rejoin
                1 = Rejoin
            Remove Children:
                0 = Leave, do not remove children
                1 = Leave, removing children
           Return: Expecting status, then "leave indication/0x8048"
         */
        $zgMsg = zgComposeMsg("004C", $devIEEE, "00", "00");
        $status = zgWrite($zgF, $zgMsg);
        if ($status == 0)
            $status = zgRead($zgF, $zgMsg); // Expecting 8000 'status' frame
        if ($status == 0) {
            $zgMsgType = substr($zgMsg, 0, 4);
            if ($zgMsgType != "8000") {
                logMessage('error', 'Mauvaise réponse de la cmde 004C. 8000 attendu.');
                $status = -1;
            } else {
                $zgStatus = substr($zgMsg, 10, 2);
                if ($zgStatus != "00") {
                    logMessage('error', 'Cmde 004C en erreur: status='.$zgStatus);
                    $status = -1;
                }
            }
        }

        if ($status == 0) {
            /* Expecting now 'Leave Indication' message
            0x8048	Leave indication
                <extended address: uint64_t>
                <rejoin status: uint8_t> */
            $status = zgRead($zgF, $zgMsg); // Expecting 8048 frame
            if ($status == 0) {
                $zgMsgType = substr($zgMsg, 0, 4);
                if ($zgMsgType != "8048") {
                    logMessage('error', 'Mauvaise réponse. 8048 attendu.');
                    $status = -1;
                } else {
                }
            }
        }

        fclose($zgF); // Close file desc
        return $status;
    }
?>
