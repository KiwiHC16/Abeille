<?php

    /*
     * AbeilleSerialReadX
     *
     * - Read Zigate messages from selected port (/dev/ttyXX)
     * - Transcode data from binary to hex (note: ALL HEX are converted UPPERCASE)
     * - and send message to parser thru queue.
     *
     * Usage:
     * /usr/bin/php /var/www/html/plugins/Abeille/core/php/AbeilleSerialRead.php <AbeilleX> <ZigatePort> <DebugLevel>
     *
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        // include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/includes/config.php';
    include_once __DIR__.'/includes/function.php';
    include_once __DIR__.'/includes/fifo.php';
    // include_once __DIR__.'/../../resources/AbeilleDeamon/lib/AbeilleTools.php';
    include_once __DIR__.'/../../core/php/AbeilleLog.php';

    logSetConf('', TRUE); // Log to STDOUT until log name fully known (need Zigate number)
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

    $abeille        = $argv[1]; // Zigate name (ex: 'Abeille1')
    $serial         = $argv[2]; // Zigate port (ex: '/dev/ttyUSB0')
    $requestedlevel = $argv[3]; // Currently unused
    $abeilleNb = (int)substr($abeille, -1); // Zigate number (ex: 1)
    logSetConf("AbeilleSerialRead".$abeilleNb.".log", TRUE); // Log to file with line nb check

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        logMessage('info', 'Main: com file (experiment): '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.' > /dev/null 2>&1');
    }
    if (!file_exists($serial)) {
        logMessage('error', 'Le port '.$serial.' n\'existe pas ! Arret du démon');
        exit(3);
    }

    // function shutdown($sig, $sigInfos) {
    //     pcntl_signal($sig, SIG_IGN);

    //     logMessage("info", "<<< Arret d'AbeilleSerialRead".$abeilleNb);
    //     exit(0);
    // }

    // declare(ticks = 1);
    // if (pcntl_signal(SIGTERM, "shutdown", FALSE) != TRUE)
    //     logMessage("error", "Erreur pcntl_signal()");

    //check already running
    $parameters = AbeilleTools::getParameters();
    $running = AbeilleTools::getRunningDaemons();
    $daemons= AbeilleTools::diffExpectedRunningDaemons($parameters,$running);
    logMessage('debug', 'Daemons='.json_encode($daemons));
    #Two at least expected,the original and this one
    if ($daemons["serialRead".$abeilleNb] > 1){
        logMessage('error', 'Le daemon est déja lancé! '.json_encode($daemons));
        exit(3);
    }

    $queueKeySerieToParser = msg_get_queue(queueKeySerieToParser);

    exec(system::getCmdSudo().' chmod 777 '.$serial.' >/dev/null 2>&1');
    exec("stty -F ".$serial." sane", $out, $status);
    if ($status != 0) {
        logMessage('debug', 'ERR stty -F '.$serial.' sane');
    }
    exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw", $out, $status);
    if ($status != 0) {
        logMessage('debug', 'ERR stty -F '.$serial.' speed 115200 cs8 -parenb -cstopb -echo raw');
    }

    // Si le lien tombe a l ouverture de $serial c est peut etre par ce que le serveur n'est pas dispo.
    // Il semblerai que le lien pts soit créé même si la liaison n'est pas établie.
    $f = fopen($serial, "r");
    if ($f == FALSE) {
        logMessage('error', 'Impossible d\'ouvrir le port '.$serial.' en lecture. Arret du démon AbeilleSerialRead'.$abeilleNb);
        exec('sudo lsof -Fcn '.$serial, $out);
        logMessage('debug', 'sudo lsof -Fcn => \''.implode(",", $out).'\'');
        exit(4);
    }
    stream_set_blocking($f, TRUE); // Should be blocking read but is it default ?

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
            logMessage('error', 'Le port '.$serial.' a disparu !');
            break;
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
                if ($frame != "")
                    logMessage('error', 'Trame en dehors marqueurs: '.json_encode($frame));
                $frame = "";
                $step = "WAITEND";
                $byteIdx = 1; // Next byte is index 1
                $ccrc = 0;
            }
        } else {
            /* Waiting for "03" end byte */
            if ($byte == "03") {
                if ($ccrc != $ecrc)
                    logMessage('error', 'ERREUR CRC: calc=0x'.dechex($ccrc).', att=0x'.dechex($ecrc).', mess='.substr($frame, 0, 12).'...'.substr($frame, -2, 2));

                $msgToSend = array( 'dest'=>$abeille, 'trame'=>$frame );
                if (msg_send( $queueKeySerieToParser, 1, json_encode($msgToSend), false, false) == FALSE) {
                    logMessage('error', 'ERREUR de transmission: '.json_encode($frame));
                } else {
                    logMessage('debug', 'Reçu: '.json_encode($frame));
                }
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
    logMessage('info', 'Fin du démon AbeilleSerialRead'.$abeilleNb);
?>
