<?php

    /*
     * Zigate standalone PHP library.
     * - Log done thru AbeilleLog lib and therefore to output configured
     *   from calling function.
     */

    $curLogLevel = 0;
    $logFile = ''; // Absolut path
    require_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    require_once 'AbeilleLog.php'; // logMessage()

    /* Library setup.
       If 'lFile' is not absolut, default Jeedom path is added. */
    /* TODO: To be revisited. No longer required to configure log */
    function zgSetConf($lFile = '') {
    }

    /* Write message 'zgMsg' (string) to 'zgF' file desc.
       Returns: 0=OK, -1=ERROR */
    function zgWrite($zgF, $zgMsg)
    {
        logMessage('debug', "zgWrite(".$zgMsg.")");
        if ($zgF == false) {
            logMessage('error', "zgWrite() END: fopen ERROR");
            return -1;
        }
        $frame = zgMsgToFrame($zgMsg);
        $status = fwrite($zgF, pack("H*", $frame));
        fflush($zgF);
        if ($status == false) {
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
        $msgJson = json_encode($msg);

        $queue = msg_get_queue(queueKeyAssistToCmd);
        if (msg_send($queue, 1, $msgJson, false, false) == false) {
            logMessage("error", "Could not send msg to 'queueKeyAssistToCmd': msg=".$msgJson);
            return -1;
        }

        return 0;
    }

    /* Read frame, extract & transcode message.
       Returns: 0=OK, -1=ERROR */
    function zgRead($zgF, &$zgMsg) {
        logMessage('debug', "zgRead()");
        if ($zgF == false) {
            logMessage('error', "zgRead() ERROR: bad desc for reading");
            return -1;
        }
        $decode = false;
        $zgMsg = "";
        $step = "WAITSTART";
        while (true) {
            $c = fread($zgF, 01);
            $c = strtoupper(bin2hex($c));

            if ($step == "WAITSTART") {
                if ($c !== "01") // Start of frame ?
                    continue;
                $zgMsg = "";
                $step = "WAITEND";
            } else { // WAITEND
                if ($c == "03") // End of frame ?
                    break;
                if ($c == "02") {
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
        }
        logMessage('debug', '  Read='.$zgMsg);
        return 0;
    }

    /* Read zigate msg from parser thru queue.
       Returns: 0=OK, -1=ERROR (timeout) */
    function zgRead2(&$zgMsg, &$timeout) {
        logMessage('debug', "zgRead2()");

        $queue = msg_get_queue(queueKeyParserToAssist);
        $max_msg_size = 2048;
        $msg_type = NULL;
        $zgMsg = "";
        while ($timeout > 0) {
            if (msg_receive($queue, 0, $msg_type, $max_msg_size, $zgMsg, false, MSG_IPC_NOWAIT) == TRUE) {
                $zgMsg = json_decode($zgMsg);
                logMessage('debug', '  Read='.$zgMsg);
                return 0;
            }
            usleep(100000); // Sleep 100ms
            $timeout -= 100;
        }

        logMessage('debug', '  Time out !');
        return -1;
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
        if ($zgF == false) {
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
                $major = substr($zgMsg, 10, 4);
                $minor = substr($zgMsg, 14, 4);
                $version = $major.'-'.$minor;
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
        if ($zgF == false) {
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
        if ($zgF == false) {
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

    /* Get list of active end points.
       Send "Active Endpoint request" (cmd 0x0045) to zgPort and read 8045 answer.
       WARNING: Zigate exclusive access required !
       Args: zgPort = Zigate port (ex: '/dev/ttyS1'), eqAddr = EQ short addr
       Returns: 0=OK, -1=ERROR */
    function zgGetEPList($zgNb, $eqAddr, &$EPList) {
        logMessage('debug', "zgGetEPList(zgNb=".$zgNb.", addr=".$eqAddr.")");

        zgReroute($zgNb, 0);

        $zgMsg = zgComposeMsg("0045", $eqAddr);
        $status = zgWrite2($zgMsg);
        $timeout = 2000; // 2s
        while ($status == 0) {
            $status = zgRead2($zgMsg, $timeout); // Expecting 8000 'status' frame
            if ($status != 0)
                break; // Error (timeout)
            $msgType = substr($zgMsg, 0, 4);
            $packetType = substr($zgMsg, 14, 4);
            if (($msgType == "8000") && ($packetType == "0045"))
                break;
        }
        while ($status == 0) {
            $timeout = 2000; // 2s
            $status = zgRead2($zgMsg, $timeout); // Expecting 8045
            if ($status != 0)
                break;

            if (substr($zgMsg, 0, 4) != "8045")
                continue;
            $zgMsg = substr($zgMsg, 10); // Skipping type/len/crc

            // <Sequence number: uint8_t>
            // <status: uint8_t>
            // <Address: uint16_t>
            // <endpoint count: uint8_t>
            // <active endpoint list: each data element of the type uint8_t>
            $EPCount = hexdec(substr($zgMsg, 8, 2));
            $List = [];
            for ($i = 0; $i < ($EPCount * 2); $i += 2) {
                $List[] = hexdec(substr($zgMsg, 10 + $i, 2));
            }
            $EPList = array(
                'SQN' => substr($zgMsg, 0, 2),
                'Status' => substr($zgMsg, 2, 2),
                'Addr' => substr($zgMsg, 4, 4),
                'EPCount' => $EPCount,
                'EPList' => $List
            );
            break;
        }

        zgReroute($zgNb, 1);
        return $status;
    }

    /* Get single descriptor response.
       Send "Single Desc Request" (cmd 0x0043) to zgPort and read 8043 answer.
       WARNING: Zigate exclusive access required !
       Note: Returns ERR if no answer from EQ within 2s (timeout).
       Args: zgPort = Zigate port (ex: '/dev/ttyS1'), eqAddr = EQ short addr,
             eqEP = End Point number
       Output: $resp
       Returns: 0=OK, -1=ERROR */
    function zgGetSingleDescResp($zgNb, $eqAddr, $eqEP, &$resp) {
        logMessage('debug', "zgGetSingleDescResp(zgNb=".$zgNb.", addr=".$eqAddr.", EP=".$eqEP.")");

        zgReroute($zgNb, 0);

        $zgMsg = zgComposeMsg("0043", $eqAddr, sprintf("%02X", $eqEP));
        $status = zgWrite2($zgMsg);
        $timeout = 2000; // 2s
        while ($status == 0) {
            $status = zgRead2($zgMsg, $timeout); // Expecting 8000 'status' frame
            if ($status != 0)
                break; // Error (timeout)
            $msgType = substr($zgMsg, 0, 4);
            $packetType = substr($zgMsg, 14, 4);
            if (($msgType == "8000") && ($packetType == "0043"))
                break;
        }
        while ($status == 0) {
            $timeout = 2000; // 2s
            $status = zgRead2($zgMsg, $timeout); // Expecting 8043
            if ($status != 0)
                break;

            if (substr($zgMsg, 0, 4) != "8043")
                continue;
            $zgMsg = substr($zgMsg, 10); // Skipping type/len/crc

            // 8043 message reminder
            // <Sequence number: uint8_t>
            // <status: uint8_t>
            // <nwkAddress: uint16_t>
            // <length: uint8_t>
            // <endpoint: uint8_t>
            // <profile: uint16_t>
            // <device id: uint16_t>
            // <bit fields: uint8_t >
            //      Device version: 4 bits (bits 0-4)
            //      Reserved: 4 bits (bits4-7)
            // <InClusterCount: uint8_t >
            // <In cluster list: data each entry is uint16_t>
            // <OutClusterCount: uint8_t>
            // <Out cluster list: data each entry is uint16_t>
            $InClustCount = hexdec(substr($zgMsg, 22, 2));
            $InClustList = [];
            for ($i = 0; $i < ($InClustCount * 4); $i += 4) {
                $InClustList[] = substr($zgMsg, (24 + $i), 4);
            }
            $OutClustCount = hexdec(substr($zgMsg, 24 + $i, 2));
            $OutClustList = [];
            for ($j = 0; $j < ($OutClustCount * 4); $j += 4) {
                $OutClustList[] = substr($zgMsg, (24 + $i + 2 + $j), 4);
            }
            $resp = array(
                'SQN' => substr($zgMsg, 0, 2),
                'Status' => substr($zgMsg, 2, 2),
                'Addr' => substr($zgMsg, 4, 4),
                'Len' => substr($zgMsg, 8, 2),
                'EP' => hexdec(substr($zgMsg, 10, 2)),
                'ProfId' => substr($zgMsg, 12, 4),
                'DevId' => substr($zgMsg, 16, 4),
                'BitF' => substr($zgMsg, 20, 2),
                'InClustCount' => $InClustCount,
                'InClustList' => $InClustList,
                'OutClustCount' => $OutClustCount,
                'OutClustList' => $OutClustList,
            );
            break;
        }

        zgReroute($zgNb, 1);
        return $status;
    }

    /* Get attribut discovery response.
       Send "Attribut Discovery Request" (cmd 0x0140) to zgPort and read 8140 answer.
       WARNING: Zigate exclusive access required !
       WARNING: Requires FW >= 31d in HYBRID mode !
       Note: Returns ERR if no answer from EQ within 2s (timeout).
       Args: zgPort = Zigate port (ex: '/dev/ttyS1'), eqAddr = EQ short addr,
             eqEP = End Point number, clustId
       Output: $resp
       Returns: 0=OK, -1=ERROR */
    function zgGetAttrDiscResp($zgNb, $eqAddr, $eqEP, $clustId, &$resp) {
        logMessage('debug', "zgGetAttrDiscResp(zgNb=".$zgNb.", addr=".$eqAddr.", EP=".$eqEP.", clustId=".$clustId.")");

        zgReroute($zgNb, 0);

        // 0140 message reminder:
        // <address mode: uint8_t>	Status
        // <target short address: uint16_t>	Attribute Discovery response
        // <source endpoint: uint8_t>
        // <destination endpoint: uint8_t>
        // <Cluster id: uint16_t>
        // <Attribute id : uint16_t>
        // <direction: uint8_t>
        //      0 – from server to client
        //      1 – from client to server
        // <manufacturer specific: uint8_t>
        //      1 – Yes
        //      0 – No
        // <manufacturer id: uint16_t>
        // <Max number of identifiers: uint8_t>
        $zgMsg = zgComposeMsg("0140", "02", $eqAddr, "01", sprintf("%02X", $eqEP), $clustId."0000"."00"."00"."0000"."FF");
        $status = zgWrite2($zgMsg);
        $timeout = 2000; // 2s
        while ($status == 0) {
            $status = zgRead2($zgMsg, $timeout); // Expecting 8000 'status' frame
            if ($status != 0)
                break; // Error (timeout)
            $msgType = substr($zgMsg, 0, 4);
            $packetType = substr($zgMsg, 14, 4);
            if (($msgType == "8000") && ($packetType == "0140"))
                break;
        }

        while ($status == 0) {
            $timeout = 2000; // 2s
            $status = zgRead2($zgMsg, $timeout);
            if ($status != 0)
                break;

            if (substr($zgMsg, 0, 4) != "8002")
                continue;
            $zgMsg = substr($zgMsg, 10); // Skipping type/len/crc

            // // 8140 message reminder:
            // // <complete: uint8_t>
            // // <attribute type: uint8_t>
            // // <attribute id: uint16_t>
            // // <Src Addr: uint16_t> (added only from 3.0f version)
            // // <Src EndPoint: uint8_t> (added only from 3.0f version)
            // // <Cluster id: uint16_t> (added only from 3.0f version)
            // $resp = array(
            //     'Completed' => hexdec(substr($zgMsg, 0, 2)),
            //     'AttrType' => substr($zgMsg, 2, 2),
            //     'AttrId' => substr($zgMsg, 4, 4),
            //     'Addr' => substr($zgMsg, 8, 4),
            //     'EP' => hexdec(substr($zgMsg, 12, 2)),
            //     'ClustId' => substr($zgMsg, 14, 4),
            // );

            /* Decoding message */
            $resp = decode8002($zgMsg);
            break;
        }

        zgReroute($zgNb, 1);
        return $status;
    }


    /* Decode 8002 message.
       Returns: array() */
    function decode8002($zgMsg) {
        logMessage('debug', "decode8002(): zgMsg=".$zgMsg);
        /* 8002 message reminder (if hybrid mode) */
        // <status: uint8_t>
        // <Profile ID: uint16_t>
        // <cluster ID: uint16_t>
        // <source endpoint: uint8_t>
        // <destination endpoint: uint8_t>
        // <source address mode: uint8_t>
        // <source address: uint16_t or uint64_t>
        // <destination address mode: uint8_t>
        // <destination address: uint16_t or uint64_t>
        // <payload : data each element is uint8_t>
        $profId = substr($zgMsg, 2, 4);
        $clustId = substr($zgMsg, 6, 4);
        $resp = array(
            'Status' => substr($zgMsg, 0, 2),
            'ProfId' => $profId,
            'ClustId' => $clustId,
            'SrcEP' => substr($zgMsg, 10, 2),
            'DestEP' => substr($zgMsg, 12, 2),
            'AddrMode' => substr($zgMsg, 14, 2),
            'Addr' => substr($zgMsg, 16, 4), // Assuming "short" addr mode (2)
            'DestAddrMode' => substr($zgMsg, 20, 2),
            'DestAddr' => substr($zgMsg, 22, 4), // Assuming "short" addr mode (2)

            'LQI' => substr($zgMsg, -2, 2)
        );

        /* Decoding ZCL header */
        $resp['FCF'] = substr($zgMsg, 26, 2); // Frame Control Field
        $manufSpecific = hexdec($resp['FCF']) & (1 << 2);
        if ($manufSpecific) {
            /* 16bits for manuf specific code */
            $resp['SQN'] = substr($zgMsg, 32, 2); // Sequence Number
            $resp['Cmd'] = substr($zgMsg, 34, 2); // Command
            $zgMsg = substr($zgMsg, 34, -2);
        } else {
            $resp['SQN'] = substr($zgMsg, 28, 2); // Sequence Number
            $resp['Cmd'] = substr($zgMsg, 30, 2); // Command
            $zgMsg = substr($zgMsg, 32, -2);
        }

        /*
        0x01 Read Attributes Response
        0x04 Write Attributes Response
        0x05 Write Attributes No Response
        0x07 Configure Reporting Response
        0x09 Read Reporting Configuration Response
        0x0d Discover Attributes Response
        */
        logMessage('debug', "decode8002(): cmd=".$resp['Cmd']);

        if ($resp['Cmd'] == "01") { // Read Attributes Response
            $attributes = [];
            // 'Attributes' => []; // Attributes
            //      $attr['Id']
            //      $attr['Status']
            //      $attr['DataType']
            //      $attr['Data']
            $l = strlen($zgMsg);
            $unknownType = false;
            for ($i = 0; $i < $l;) {
                $attrId = substr($zgMsg, $i + 2, 2).substr($zgMsg, $i, 2);
                $attrStatus = substr($zgMsg, $i + 4, 2);
                $i += 6;

                /* Note: Status=0x86 means unsupported attribute */
                if ($attrStatus != "00") {
                    $attr = array(
                        'Id' => $attrId,
                        'Status' => $attrStatus
                    );
                    $attributes[] = $attr;
                    continue;
                }

                $attr = array(
                    'Id' => $attrId,
                    'Status' => $attrStatus,
                    'DataType' => substr($zgMsg, $i, 2),
                );
                $i += 2;
                switch ($attr['DataType']) {
                case "10": // Boolean
                case "18": // 8bit bitmap
                case "20": // 8bit unsigned int
                case "30": // 8bit enum
                    $attr['Data'] = substr($zgMsg, $i, 2);
                    $i += 2;
                    break;
                case "21": // 16bit unsigned int
                    $attr['Data'] = substr($zgMsg, $i, 2);
                    $i += 4;
                    break;
                case "42": // String
                    $len = hexdec(substr($zgMsg, $i, 2)) * 2;
                    $attr['Data'] = pack('H*', substr($zgMsg, $i + 2, $len));
                    $i += 2 + $len;
                    break;
                default:
                    $unknownType = TRUE;
                    logMessage("WARNING", "Unknown attribute type ".$attr['DataType'].". Rest of decode IGNORED !");
                    break;
                }
                if ($unknownType)
                    break;
                $attributes[] = $attr;
            }
            $resp['Attributes'] = $attributes;
        } else if ($resp['Cmd'] == "0D") { // Discover Attributes Response
            logMessage('debug', "decode8002(): Discover Attributes Response");
            $completed = substr($zgMsg, 2);
            $zgMsg = substr($zgMsg, 2); // Skipping 'completed' status

            $attributes = [];
            // 'Attributes' => []; // Attributes
            //      $attr['Id']
            //      $attr['DataType']
            $l = strlen($zgMsg);
            for ($i = 0; $i < $l;) {
                $attr = array(
                    'Id' => substr($zgMsg, $i + 2, 2).substr($zgMsg, $i, 2),
                    'DataType' => substr($zgMsg, $i + 4, 2)
                );
                $attributes[] = $attr;
                $i += 6;
            }
            $resp['Attributes'] = $attributes;
        }

        return $resp;
    }

    /* Decode 8002 message for "Read Attribute Response" case.
       Returns: array() */
    function decode8002_ReadAttributeResponse($zgMsg) {
        logMessage('debug', "decode8002_ReadAttributeResponse zgMsg=".$zgMsg);
                /* 8002 message reminder (if hybrid mode) */
                // <status: uint8_t>
                // <Profile ID: uint16_t>
                // <cluster ID: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <source address mode: uint8_t>
                // <source address: uint16_t or uint64_t>
                // <destination address mode: uint8_t>
                // <destination address: uint16_t or uint64_t>
                // <payload : data each element is uint8_t>
                $profId = substr($zgMsg, 2, 4);
                $clustId = substr($zgMsg, 6, 4);
                $resp = array(
                    'Status' => substr($zgMsg, 0, 2),
                    'ProfId' => $profId,
                    'ClustId' => $clustId,
                    'SrcEP' => substr($zgMsg, 10, 2),
                    'DestEP' => substr($zgMsg, 12, 2),
                    'AddrMode' => substr($zgMsg, 14, 2),
                    'Addr' => substr($zgMsg, 16, 4), // Assuming "short" addr mode (2)
                    'DestAddrMode' => substr($zgMsg, 20, 2),
                    'DestAddr' => substr($zgMsg, 22, 4), // Assuming "short" addr mode (2)

                    /* Read Attribute Response specific */
                    'FCF' => substr($zgMsg, 26, 2), // Frame Control Field
                    'SQN' => substr($zgMsg, 28, 2), // Sequence Number
                    'Cmd' => substr($zgMsg, 30, 2), // Command
                    // 'Attributes' => []; // Attributes
                    //      $attr['Id']
                    //      $attr['Status']
                    //      $attr['DataType']
                    //      $attr['Data']
                    'LQI' => substr($zgMsg, -2, 2)
                );
                $zgMsg = substr($zgMsg, 32, -2);
                $attributes = [];
                $l = strlen($zgMsg);
                $unknownType = false;
                for ($i = 0; $i < $l;) {
                    $attrId = substr($zgMsg, $i + 2, 2).substr($zgMsg, $i, 2);
                    $attrStatus = substr($zgMsg, $i + 4, 2);
                    $i += 6;

                    /* Note: Status=0x86 means unsupported attribute */
                    if ($attrStatus != "00") {
                        $attr = array(
                            'Id' => $attrId,
                            'Status' => $attrStatus
                        );
                        $attributes[] = $attr;
                        continue;
                    }

                    $attr = array(
                        'Id' => $attrId,
                        'Status' => $attrStatus,
                        'DataType' => substr($zgMsg, $i, 2),
                    );
                    $i += 2;
                    switch ($attr['DataType']) {
                    case "10": // Boolean
                    case "18": // 8bit bitmap
                    case "20": // 8bit unsigned int
                    case "30": // 8bit enum
                        $attr['Data'] = substr($zgMsg, $i, 2);
                        $i += 2;
                        break;
                    case "21": // 16bit unsigned int
                        $attr['Data'] = substr($zgMsg, $i, 2);
                        $i += 4;
                        break;
                    case "42": // String
                        $len = hexdec(substr($zgMsg, $i, 2)) * 2;
                        $attr['Data'] = pack('H*', substr($zgMsg, $i + 2, $len));
                        $i += 2 + $len;
                        break;
                    default:
                        $unknownType = TRUE;
                        logMessage("WARNING", "Unknown attribute type ".$attr['DataType']);
                        break;
                    }
                    if ($unknownType)
                        break;
                    $attributes[] = $attr;
                }
                $resp['Attributes'] = $attributes;

                return $resp;
            }

    /* Attempt to detect main supported attributs for given EP/Cluster.
       Note: This function goes thru AbeilleCmd & AbeilleParser.
       Note: Returns ERR if no answer from EQ within 2s (timeout).
       Args: zgNb = Zigate number, eqAddr = EQ short addr,
             eqEP = End Point number, clustId
       Output: $resp=8002 response message
       Returns: 0=OK, -1=ERROR */
    function zgDetectAttributs($zgNb, $eqAddr, $eqEP, $clustId, &$resp) {
        logMessage('debug', "zgDetectAttributs(zgNb=".$zgNb.", addr=".$eqAddr.", EP=".$eqEP.", clustId=".$clustId.")");

        zgReroute($zgNb, 0);

        // "0100/Read Attribute request" message reminder:
        // <address mode: uint8_t>
        // <target short address: uint16_t>
        // <source endpoint: uint8_t>
        // <destination endpoint: uint8_t>
        // <Cluster id: uint16_t>
        // <direction: uint8_t>
        //     0 – from server to client
        //     1 – from client to server
        // <manufacturer specific: uint8_t>
        //     0 – No 1 – Yes
        // <manufacturer id: uint16_t>
        // <number of attributes: uint8_t>
        // <attributes list: data list of uint16_t each>
        $attrList = [];
        if ($clustId == "0000") { // Basic
            $attrList[] = "0000"; // ZCLVersion
            $attrList[] = "0004"; // ManufacturerName
            $attrList[] = "0005"; // ModelIdentifier
            $attrList[] = "0007"; // PowerSource
            $attrList[] = "0010"; // LocationDescription
            $attrList[] = "0012"; // DeviceEnabled
        } else if ($clustId == "0003") { // Identify
            $attrList[] = "0000"; // IdentifyTime
        } else if ($clustId == "0004") { // Groups
            $attrList[] = "0000"; // NameSupport
        } else if ($clustId == "0005") { // Scenes
            $attrList[] = "0000"; // SceneCount
            $attrList[] = "0001"; // CurrentScene
            $attrList[] = "0002"; // CurrentGroup
            $attrList[] = "0003"; // SceneValid
            $attrList[] = "0004"; // NameSupport
            $attrList[] = "0005"; // LastConfiguredBy
        } else if ($clustId == "0006") { // On/off
            $attrList[] = "0000"; // OnOff
            $attrList[] = "4000"; // GlobalSceneControl
            $attrList[] = "4001"; // OnTime
            $attrList[] = "4002"; // OffWaitTime
        } else if ($clustId == "0007") { // On/off switch config
            $attrList[] = "0000"; // SwitchType
            $attrList[] = "0010"; // SwitchActions
        } else if ($clustId == "0008") { // Level control
            $attrList[] = "0000"; // CurrentLevel
            $attrList[] = "0001"; // RemainingTime
            $attrList[] = "0010"; // OnOffTransitionTime
            $attrList[] = "0011"; // OnLevel
            $attrList[] = "0012"; // OnTransitionTime
            $attrList[] = "0013"; // OffTransitionTime
            $attrList[] = "0014"; // DefaultMoveRate
        } else if ($clustId == "0009") { // Alarm
            $attrList[] = "0000"; // AlarmCount
        } else if ($clustId == "000A") { // Time
            $attrList[] = "0000"; // Time
            $attrList[] = "0001"; // TimeStatus
            $attrList[] = "0002"; // TimeZone
            $attrList[] = "0003"; // DstStart
            $attrList[] = "0004"; // DstEnd
            $attrList[] = "0005"; // DstShift
            $attrList[] = "0006"; // StandardTime
            $attrList[] = "0007"; // LocalTime
            $attrList[] = "0008"; // LastSetTime
            $attrList[] = "0009"; // ValidUntilTime
        } else if ($clustId == "0014") { // Multistate Value
            $attrList[] = "000E"; // StateText
            $attrList[] = "001C"; // Description
            $attrList[] = "004A"; // NumberOfStates
            $attrList[] = "0051"; // OutOfService
            $attrList[] = "0055"; // PresentValue
            $attrList[] = "0057"; // PriorityArray
            $attrList[] = "0067"; // Reliability
            $attrList[] = "0068"; // RelinquishDefault
            $attrList[] = "006F"; // StatusFlags
            $attrList[] = "0100"; // ApplicationType
        } else if ($clustId == "0015") { // Commissioning
        } else if ($clustId == "0020") { // Poll control
            $attrList[] = "0000"; // Check-inInterval
            $attrList[] = "0001"; // LongPoll Interval
            $attrList[] = "0002"; // ShortPollInterval
            $attrList[] = "0003"; // FastPollTimeout
            $attrList[] = "0004"; // Check-inIntervalMin
            $attrList[] = "0005"; // LongPollIntervalMin
            $attrList[] = "0006"; // FastPollTimeoutMax
        } else if ($clustId == "0100") { // Shade Configuration
            $attrList[] = "0000"; // PhysicalClosedLimit
            $attrList[] = "0001"; // MotorStepSize
            $attrList[] = "0002"; // Status
            $attrList[] = "0010"; // ClosedLimit
            $attrList[] = "0011"; // Mode
        } else if ($clustId == "0102") { // Window covering
            /* Set 0x00 => Window Covering Information */
            $attrList[] = "0000"; // WindowCoveringType
            $attrList[] = "0001"; // PhysicalClosedLimit – Lift
            $attrList[] = "0002"; // PhysicalClosedLimit – Tilt
            $attrList[] = "0003"; // CurrentPosition – Lift
            $attrList[] = "0004"; // Current Position – Tilt
            $attrList[] = "0005"; // Number of Actuations – Lift
            $attrList[] = "0006"; // Number of Actuations – Tilt
            $attrList[] = "0007"; // Config/Status
            $attrList[] = "0008"; // Current Position Lift Percentage
            $attrList[] = "0009"; // Current Position Tilt Percentage
            /* Set 0x01 => Window Covering Settings */
            $attrList[] = "0010"; // WindowCoveringType
            $attrList[] = "0011"; // PhysicalClosedLimit – Lift
            $attrList[] = "0012"; // PhysicalClosedLimit – Tilt
            $attrList[] = "0013"; // CurrentPosition – Lift
            $attrList[] = "0014"; // Current Position – Tilt
            $attrList[] = "0015"; // Number of Actuations – Lift
            $attrList[] = "0016"; // Number of Actuations – Tilt
            $attrList[] = "0017"; // Config/Status
            $attrList[] = "0018"; // Current Position Lift Percentage
            $attrList[] = "0019"; // Current Position Tilt Percentage
        } else if ($clustId == "1000") { // Touchlink commissioning
            // No attributes in this cluster
        }
        $attrCount = sizeof($attrList);
        if ($attrCount == 0) {
            $resp = array(
                'Attributes' => [], // Attributes
            );
            return 0;
        }
        $attr = implode($attrList);

        $zgMsg = zgComposeMsg("0100", "02", $eqAddr, "01", sprintf("%02X", $eqEP), $clustId, "00000000", sprintf("%02X", $attrCount), $attr);
        $status = zgWrite2($zgMsg);
        $timeout = 2000; // 2s
        while ($status == 0) {
            $status = zgRead2($zgMsg, $timeout); // Expecting 8000 'status' frame
            if ($status != 0)
                break; // Error (timeout)
            $msgType = substr($zgMsg, 0, 4);
            $packetType = substr($zgMsg, 14, 4);
            if (($msgType == "8000") && ($packetType == "0100"))
                break;
        }

        while ($status == 0) {
            $timeout = 2000; // 2s
            $status = zgRead2($zgMsg, $timeout); // Expecting 8002
            if ($status != 0)
                break;

            if (substr($zgMsg, 0, 4) != "8002")
                continue;
            $zgMsg = substr($zgMsg, 10); // Skipping type/len/crc

            /* 8002 message reminder (if hybrid mode) */
            // <status: uint8_t>
            // <Profile ID: uint16_t>
            // <cluster ID: uint16_t>
            // ...
            // $profId = substr($zgMsg, 2, 4);
            // $clustId = substr($zgMsg, 6, 4);
            if (substr($zgMsg, 6, 4) != $clustId)
                continue; // Not the expected response

            /* Decoding message */
            $resp = decode8002_ReadAttributeResponse($zgMsg);
            break;
        }

        zgReroute($zgNb, 1);
        return $status;
    }

    /* Request AbeilleCmd & AbeilleParser to (stop) reroute messages
       for given network */
    function zgReroute($zgNb, $stop = 0) {
        logMessage('debug', "zgReroute(zgNb=".$zgNb.", stop=".$stop.")");

        $msg = Array(
            "type" => ($stop == 1 ? "reroutestop" : "reroute"),
            "network" => "Abeille".$zgNb
        );
        $msgJson = json_encode($msg);

        $status = 0;
        $parserQueue = msg_get_queue($abQueues["assistToParser"]["id"]);
        $cmdQueue = msg_get_queue($abQueues["assistToCmd"]["id"]);
        if (($parserQueue == false) || ($cmdQueue == false)) {
            logMessage("error", "msg_get_queue() ERROR");
            $status = -1;
        }
        if ($status == 0) {
            if (msg_send($parserQueue, 1, $msgJson, false, false) == false) {
                logMessage("error", "Could not send msg to 'assistToParser': msg=".$msgJson);
                $status = -1;
            }
        }
        if ($status == 0) {
            if (msg_send($cmdQueue, 1, $msgJson, false, false) == false) {
                logMessage("error", "Could not send msg to 'assistToCmd': msg=".$msgJson);
                $status = -1;
            }
        }
        return $status;
    }

    /* Send a "Read Attribute Request", wait for 8002 answer, and decode it.
       WARNING: Zigate exclusive access required !
       Note: Returns ERR if no answer from EQ within 2s (timeout).
       Args: zgPort = Zigate port (ex: '/dev/ttyS1'), eqAddr = EQ short addr,
             eqEP = End Point number, clustId
       Output: $resp
       Returns: 0=OK, -1=ERROR */
    function zgReadAttributeResponse($zgNb, $eqAddr, $eqEP, $clustId, $attrId, &$resp) {
        logMessage('debug', "zgReadAttributeResponse(zgNb=".$zgNb.", addr=".$eqAddr.", EP=".$eqEP.", clustId=".$clustId.", attrId=".$attrId.")");

        zgReroute($zgNb, 0);

        // "0100/Read Attribute request" message reminder:
        // <address mode: uint8_t>
        // <target short address: uint16_t>
        // <source endpoint: uint8_t>
        // <destination endpoint: uint8_t>
        // <Cluster id: uint16_t>
        // <direction: uint8_t>
        //     0 – from server to client
        //     1 – from client to server
        // <manufacturer specific: uint8_t>
        //     0 – No 1 – Yes
        // <manufacturer id: uint16_t>
        // <number of attributes: uint8_t>
        // <attributes list: data list of uint16_t each>
        $zgMsg = zgComposeMsg("0100", "02", $eqAddr, "01", sprintf("%02X", $eqEP), $clustId, "0000000001", $attrId);
        $status = zgWrite2($zgMsg);
        $timeout = 2000; // 2s
        while ($status == 0) {
            $status = zgRead2($zgMsg, $timeout); // Expecting 8000 'status' frame
            if ($status != 0)
                break; // Error (timeout)
            $msgType = substr($zgMsg, 0, 4);
            $packetType = substr($zgMsg, 14, 4);
            if (($msgType == "8000") && ($packetType == "0100"))
                break;
        }
        while ($status == 0) {
            $timeout = 2000; // 2s
            $status = zgRead2($zgMsg, $timeout); // Expecting 8002
            if ($status != 0)
                break; // Timeout

            if (substr($zgMsg, 0, 4) != "8002")
                continue;
            $zgMsg = substr($zgMsg, 10); // Skipping type/len/crc

            /* 8002 message reminder (if hybrid mode) */
            // <status: uint8_t>
            // <Profile ID: uint16_t>
            // <cluster ID: uint16_t>
            // ...
            // $profId = substr($zgMsg, 2, 4);
            if (substr($zgMsg, 6, 4) != $clustId)
                continue; // Not the expected response

            /* Decoding message */
            $resp = decode8002_ReadAttributeResponse($zgMsg);
            break;
        }

        zgReroute($zgNb, 1);
        return $status;
    }
?>
