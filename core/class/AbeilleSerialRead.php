<?php

    /*
     * AbeilleSerialReadX
     *
     * - Read Zigate messages from selected port (/dev/ttyXX for USB & Pi, or thru socat for WIFI)
     * - Transcode data from binary to hex.
     * - and write it to FIFO file.
     *
     * Usage:
     * /usr/bin/php /var/www/html/plugins/Abeille/core/class/AbeilleSerialRead.php <AbeilleX> <ZigatePort> <DebugLevel>
     *
     */

    /* Developpers debug features */
    $dbgFile = dirname(__FILE__)."/../../debug.php";
    if (file_exists($dbgFile))
        include_once $dbgFile;

    /* Errors reporting: enabled if 'dbgAbeillePHP' is TRUE */
    if (isset($dbgAbeillePHP) && ($dbgAbeillePHP == TRUE)) {
        error_reporting(E_ALL);
        ini_set('error_log', '/var/www/html/log/AbeillePHP');
        ini_set('log_errors', 'On');
    }

    include_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/function.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/fifo.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';

    function daemonlog($loglevel='NONE', $message=""){
        Tools::deamonlogFilter($loglevel, 'Abeille', 'AbeilleSerialRead', $message);
    }

    // function _exec($cmd, &$out = null)
    // {
        // $desc = array(
            // 1 => array("pipe", "w"),
            // 2 => array("pipe", "w"),
        // );

        // $proc = proc_open($cmd, $desc, $pipes);

        // $ret = stream_get_contents($pipes[1]);
        // $err = stream_get_contents($pipes[2]);

        // fclose($pipes[1]);
        // fclose($pipes[2]);

        // $retVal = proc_close($proc);

        // if (func_num_args() == 2) {
            // $out = array($ret, $err);
        // }

        // return $retVal;
    // }

    /* -------------------------------------------------------------------- */

    daemonlog('info', 'Démarrage d\'AbeilleSerialRead sur port '.$argv[2]);

    /* Checking parameters */
    if ($argc < 3) { // Currently expecting <cmdname> <AbeilleX> <ZigatePort>
        daemonlog('error', 'Argument(s) manquant(s)');
        exit(1);
    }
    if (substr($argv[1], 0, 7) != "Abeille") {
        daemonlog('error', 'Argument 1 incorrect (devrait être \'AbeilleX\')');
        exit(2);
    }

    $abeille        = $argv[1]; // Zigate name (ex: 'Abeille1')
    $serial         = $argv[2]; // Zigate port (ex: '/dev/ttyUSB0')
    $requestedlevel = $argv[3]; // Currently unused
    $abeilleNb = (int)substr($arg1, 7); // Zigate number (ex: 1)

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        daemonlog('info', 'Main: com file (experiment): '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.' > /dev/null 2>&1');
    }
    if (!file_exists($serial)) {
        daemonlog('error', 'Le port '.$serial.' n\'existe pas ! Arret du démon');
        exit(3);
    }

    // $clusterTab= Tools::getJSonConfigFiles('zigateClusters.json'); // Unused

    daemonlog('info', 'Queue: \'queueKeySerieToParser\'');
    $queueKeySerieToParser    = msg_get_queue(queueKeySerieToParser);
    // $queueKeySerieToParserSem = sem_get(queueKeySerieToParser); // Unused

    exec(system::getCmdSudo().' chmod 777 '.$serial.' > /dev/null 2>&1');
    exec("stty -F ".$serial." sane", $out);
    exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw", $out);

    // Si le lien tombe a l ouverture de $serial c est peut etre par ce que le serveur n'est pas dispo.
    // Il semblerai que le lien pts soit créé même si la liaison n'est pas établie.
    $f = fopen($serial, "r");
    if ($f == FALSE) {
        daemonlog('error', 'Impossible d\'ouvrir le port '.$serial.' en lecture. Arret du démon AbeilleSerialRead'.$abeilleNb);
        exit(4);
    }
    stream_set_blocking($f, TRUE); // Should be blocking read but is it default ?

    $transcode = false;
    $trame = ""; // Transcoded message from Zigate
    $step = "WAITSTART";
    $ecrc = 0; // Expected CRC
    $ccrc = 0; // Calculated CRC
    $byteIdx = 0; // Byte number

    /* Protocol reminder:
       00   : 01 = start
       01-02: Msg Type
       03-04: Length
       05   : crc
       xx   : payload
       last : 03 = stop

       CRC = 0x00 XOR MSG-TYPE XOR LENGTH XOR PAYLOAD
     */

    while (true) {
        $byte = fread($f, 01);

        $byte = bin2hex($byte);

        if ($step == "WAITSTART") {
            /* Waiting for "01" start byte.
               Bytes outside 01..03 markers are unexpected. */
            if ($byte != "01") {
                $trame .= $byte; // Unexpected outside 01..03 markers => error
            } else {
                /* "01" start found */
                if ($trame != "")
                    daemonlog('error', 'Trame en dehors marqueurs: '.json_encode($trame));
                $trame = "";
                $step = "WAITEND";
                $byteIdx = 1; // Next byte is index 1
                $ccrc = 0;
            }
        } else {
            /* Waiting for "03" end byte */
            if ($byte == "03") {
                if ($ccrc != $ecrc)
                    daemonlog('error', 'ERREUR CRC: calc=0x'.dechex($ccrc).', att=0x'.dechex($ecrc).', mess='.substr($trame, 0, 12).'...'.substr($trame, -2, 2));

                $trameToSend = array( 'dest'=>$abeille, 'trame'=>$trame );
                if (msg_send( $queueKeySerieToParser, 1, json_encode($trameToSend), false, false) == FALSE) {
                    daemonlog('error', 'ERREUR de transmission: '.json_encode($trame));
                } else {
                    daemonlog('debug', 'Reçu: '.json_encode($trame));
                }
                $trame = ""; // Already transmitted or displayed
                $step = "WAITSTART";
            } else {
                if ($byte == "02") {
                    $transcode = true; // Next char to be transcoded
                } else {
                    if ($transcode) {
                        $byte = sprintf("%02x", (hexdec($byte) ^ 0x10));
                        $transcode = false;
                    }
                    $trame .= $byte;
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
    daemonlog('info', 'Fin du démon AbeilleSerialRead'.$abeilleNb);
?>
