<?php

    /*
     * AbeilleSerialReadX
     *
     * - Read Zigate messages from selected port (ex: /dev/ttyXX)
     * - Transcode data from binary to hex (note: ALL HEX are converted UPPERCASE)
     * - and send message to parser thru queue.
     *
     * Usage:
     * /usr/bin/php /var/www/html/plugins/Abeille/core/php/AbeilleSerialRead.php <AbeilleX> <ZigatePort> <DebugLevel>
     *
     */

    include_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers mode ? */
    if (file_exists(dbgFile)) {
        // include_once dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/AbeilleLog.php';
    include_once __DIR__.'/../class/AbeilleTools.class.php';

    /* Tcharp38: Ouahhh. How can it handle multi-zigate ? Who is
       dealing with concurrent msg_send() on the same queue ? */
    function msgToParser($msgToSend) {
        global $queueSerialToParser, $queueMax;
        $jsonMsg = json_encode($msgToSend);
        if (msg_send($queueSerialToParser, 1, $jsonMsg, false, false, $errorCode) == false) {
            logMessage('debug', 'msg_send(queueSerialToParser): ERROR '.$errorCode);
            logMessage('debug', '  msg='.json_encode($msgToSend));
            return false;
        }
        return true;
    }

    logSetConf('', true); // Log to STDOUT until log name fully known (need Zigate number)
    logMessage('info', '>>> Démarrage d\'AbeilleSerialRead sur port '.$argv[2]);

    /* Checking parameters */
    if ($argc < 3) { // Currently expecting <cmdname> <AbeilleX> <ZigatePort>
        logMessage('error', 'Argument(s) manquant(s)');
        exit(1);
    }
    if (substr($argv[1], 0, 7) != "Abeille") {
        logMessage('error', 'Argument 1 incorrect (devrait être \'AbeilleX\')');
        exit(2);
    }

    $net            = $argv[1]; // Network name (ex: 'Abeille1')
    $serial         = $argv[2]; // Zigate port (ex: '/dev/ttyUSB0')
    $requestedlevel = $argv[3]; // Currently unused
    $zgId = (int)substr($net, 7); // Zigate number (ex: 1)
    logSetConf("AbeilleSerialRead".$zgId.".log", true); // Log to file with line nb check

    // Check if already running
    $config = AbeilleTools::getParameters();
    $running = AbeilleTools::getRunningDaemons();
    $daemons= AbeilleTools::diffExpectedRunningDaemons($config, $running);
    logMessage('debug', 'Daemons='.json_encode($daemons));
    if ($daemons["serialRead".$zgId] > 1) {
        logMessage('error', 'Un démon AbeilleSerialRead'.$zgId.' est déja lancé.');
        exit(4);
    }

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        logMessage('info', 'Main: com file (experiment): '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.' > /dev/null 2>&1');
    }

    $firstFrame = true; // To indicate that first frame might be corrupted

    // Wait for port to be available, configure it then open it
    function waitPort($serial) {
        global $firstFrame;

        while (true) {
            // Wait for port
            while (true) {
                if (file_exists($serial))
                    break;
                usleep(500000); // Sleep 500ms
            }

            // Port exists. Let's try to configure
            exec(system::getCmdSudo().' chmod 666 '.$serial.' >/dev/null 2>&1');
            exec("stty -F ".$serial." sane >/dev/null 2>&1", $out, $status);
            if ($status == 0)
                exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw >/dev/null 2>&1", $out, $status);
            if ($status != 0) {
                // Could not configure it properly
                $err = implode("/", $out);
                logMessage('debug', 'stty error: '.$err);
                continue;
            }

            // Config done. Opening
            $f = fopen($serial, "r");
            if ($f !== false) {
                logMessage('debug', $serial.' port opened');
                stream_set_blocking($f, true); // Should be blocking read but is it default ?
                $firstFrame = true; // First frame might be corrupted
                return $f;
            }
            sleep(1);
        }
    }

    // TODO Tcharp38: May make sense to wait for port to be ready
    // to cover socat > serialread case if socat starts later.
    // if (!file_exists($serial)) {
    //     logMessage('error', 'Le port '.$serial.' n\'existe pas ! Arret du démon');
    //     exit(3);
    // }
    $f = waitPort($serial);

    // function shutdown($sig, $sigInfos) {
    //     pcntl_signal($sig, SIG_IGN);

    //     logMessage("info", "<<< Arret d'AbeilleSerialRead".$zgId);
    //     exit(0);
    // }

    // declare(ticks = 1);
    // if (pcntl_signal(SIGTERM, "shutdown", false) != true)
    //     logMessage("error", "Erreur pcntl_signal()");

    // $queueSerialToParser = msg_get_queue(queueSerialToParser);
    $queueSerialToParser = msg_get_queue($abQueues["serialToParser"]["id"]);
    $queueMax = $abQueues["serialToParser"]["max"];

    // exec(system::getCmdSudo().' chmod 777 '.$serial.' >/dev/null 2>&1');
    // exec("stty -F ".$serial." sane", $out, $status);
    // if ($status != 0) {
    //     logMessage('debug', 'ERR stty -F '.$serial.' sane');
    // }
    // exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw", $out, $status);
    // if ($status != 0) {
    //     logMessage('debug', 'ERR stty -F '.$serial.' speed 115200 cs8 -parenb -cstopb -echo raw');
    // }
    // while (true) {
    //     try {
    //         exec(system::getCmdSudo().' chmod 666 '.$serial.' >/dev/null 2>&1');
    //         exec("stty -F ".$serial." sane >/dev/null 2>&1", $out, $status);
    //         if ($status == 0)
    //             exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw >/dev/null 2>&1", $out, $status);
    //         if ($status == 0)
    //             break;
    //         $err = implode("/", $out);
    //         logMessage('debug', 'stty error: '.$err);
    //     } catch ( Exception $e ) {
    //         logMessage('debug', 'stty err');
    //     }
    //     sleep(1);
    // }

    // $f = fopen($serial, "r");
    // if ($f == false) {
    //     logMessage('error', 'Impossible d\'ouvrir le port '.$serial.' en lecture. Arret du démon AbeilleSerialRead'.$zgId);
    //     exec('sudo lsof -Fcn '.$serial, $out);
    //     logMessage('debug', 'sudo lsof -Fcn '.$serial.' => \''.implode(",", $out).'\'');
    //     exit(4);
    // }
    // while (true) {
    //     try {
    //         $f = fopen($serial, "r");
    //         if ($f !== false)
    //             break;
    //     } catch ( Exception $e ) {
    //         logMessage('debug', 'fopen err');
    //     }
    //     sleep(1);
    // }
    // logMessage('debug', $serial.' port opened');
    // stream_set_blocking($f, true); // Should be blocking read but is it default ?

    /* Inform others that i'm ready to process zigate messages */
    $msgToSend = array(
        'src' => 'serialread',
        'net' => $net,
        'type' => 'status',
        'status' => 'ready',
    );
    msgToParser($msgToSend);

    $transcode = false;
    $frame = ""; // Transcoded message from Zigate
    $step = "WAITSTART";
    $ecrc = 0; // Expected CRC
    $ccrc = 0; // Calculated CRC
    $byteIdx = 0; // Byte number

    /* Protocol reminder:
       00    : 01 = start
       01-02 : Msg Type
       03-04 : Length => Payload size + 1 byte for LQI
       05    : crc
       xx    : payload
       last-1: LQI
       last  : 03 = stop

       CRC = 0x00 XOR MSG-TYPE XOR LENGTH XOR PAYLOAD XOR LQI
     */

    while (true) {
        /* Check if port still there.
           Key for connection with Socat */
        if (!file_exists($serial)) {
            fclose($f);
            logMessage('debug', 'ERROR: Serial port '.$serial.' disappeared !');
            $f = waitPort($serial);
            // logMessage('debug', $serial.' port is back');
        }

        $byte = fread($f, 01);

        $byte = strtoupper(bin2hex($byte));

        if ($step == "WAITSTART") {
            /* Waiting for "01" start byte.
               Bytes outside 01..03 markers are unexpected. */
            if ($byte != "01") {
                $frame .= $byte; // Unexpected outside 01..03 markers => error
            } else {
                /* "01" start found */
                if (($frame != "") && !$firstFrame)
                    logMessage('debug', 'ERROR: Frame outside 01/02 markers: '.json_encode($frame));
                $frame = "";
                $firstFrame = false;
                $step = "WAITEND";
                $byteIdx = 1; // Next byte is index 1
                $ccrc = 0;
            }
        } else {
            /* Waiting for "03" end byte */
            if ($byte == "03") {
                logMessage('debug', 'Got '.json_encode($frame));

                if ($ccrc != $ecrc)
                    logMessage('debug', 'ERROR: CRC: calc=0x'.dechex($ccrc).', att=0x'.dechex($ecrc).', mess='.substr($frame, 0, 12).'...'.substr($frame, -2, 2));

                $msgToSend = array(
                    'src' => 'serialread',
                    'net' => $net,
                    'type' => 'zigatemessage',
                    'msg' => $frame
                );
                msgToParser($msgToSend);
                $frame = ""; // Already transmitted or displayed
                $step = "WAITSTART";
            } else {
                if ($byte == "02") {
                    $transcode = true; // Next char to be transcoded
                } else {
                    if ($transcode) {
                        $byte = sprintf("%02X", (hexdec($byte) ^ 0x10));
                        $transcode = false;
                    }
                    $frame .= $byte;
                    if ($byteIdx == 5)
                        $ecrc = hexdec($byte); // Byte 5 is expected CRC
                    else
                        $ccrc = $ccrc ^ hexdec($byte);
                    $byteIdx++;
                }
            }
        }
    }

    fclose($f);
    logMessage('info', '<<< Fin du démon AbeilleSerialRead'.$zgId);
?>
