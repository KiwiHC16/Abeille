<?php
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';

    include("includes/config.php");
    include("includes/fifo.php");

    function _exec($cmd, &$out = null)
    {
        $desc = array(
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $proc = proc_open($cmd, $desc, $pipes);

        $ret = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $retVal = proc_close($proc);

        if (func_num_args() == 2) {
            $out = array($ret, $err);
        }

        return $retVal;
    }

    /* -------------------------------------------------------------------- */

    $serial = $argv[1];

    log::add('AbeilleSerialRead', 'debug', 'AbeilleSerial: Demarrage de AbeilleSerial');

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        log::add('AbeilleMQTTC', 'debug', 'AbeilleMQTTC main: debug for com file: '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.'chmod 777 '.$serial.' > /dev/null 2>&1');
    }


    if (!file_exists($serial)) {
        log::add('AbeilleSerialRead', 'error', 'AbeilleSerial: Error: Fichier '.$serial.' n existe pas');
        exit(1);
    }

    log::add('AbeilleSerialRead', 'info', 'AbeilleSerial: Serial port used: '.$serial);

    $fifoIN = new fifo($in, 'w+');

    _exec("stty -F ".$serial." sane", $out);
    // echo "Setup ttyUSB default configuration, resultat: \n";
    // print_r( $out );

    _exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb raw", $out);
    // echo "Setup ttyUSB configuration, resultat: \n";
    // print_r( $out );

    $f = fopen($serial, "r");

    // print_r( $f ); echo "\n";

    $transcodage = false;
    $trame = "";
    $test = "";

    while (true) {
        if (!file_exists($serial)) {
            log::add('AbeilleSerialRead', 'debug', 'AbeilleSerial: CRITICAL Fichier '.$serial." n existe pas");
            exit(1);
        }

        $car = fread($f, 01);

        $car = bin2hex($car);
        if ($car == "01") {
            $trame = "";
        } else {
            if ($car == "03") {
                // echo date("Y-m-d H:i:s")." -> ".$trame."\n";
                log::add('AbeilleSerialRead', 'debug', 'AbeilleSerial: '.date("Y-m-d H:i:s")." -> ".$trame);
                $fifoIN->write($trame."\n");
            } else {
                if ($car == "02") {
                    $transcodage = true;
                } else {
                    if ($transcodage) {
                        $trame .= sprintf("%02X", (hexdec($car) ^ 0x10));

                    } else {

                        $trame .= $car;
                    }
                    $transcodage = false;
                }
            }
        }

    }

    fclose($f);
    $fifoIN->close();

    log::add('AbeilleSerialRead', 'error', 'AbeilleSerial: Fin script AbeilleSerial');

?>
