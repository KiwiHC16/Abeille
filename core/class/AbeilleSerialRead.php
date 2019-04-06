<?php


    /***
     * AbeilleSerialRead
     *
     * Get information from selected port (/dev/ttyUSB0 pour TTL ou socat pour WIFI), transcode data from binary to hex.
     * and write it to FIFO file.
     *
     */


require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';

include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
include dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/fifo.php';

    function deamonlog($loglevel='NONE',$message=""){
        Tools::deamonlogFilter($loglevel,'AbeilleSerialRead',$message);
    }

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
    $requestedlevel=$argv[2];
    $requestedlevel=''?'none':$argv[2];
    $clusterTab= Tools::getJSonConfigFiles('zigateClusters.json');

    deamonlog('info','Starting reading port '.$serial.' and transcoding to '.$in.' with log level '.$requestedlevel);

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        deamonlog('info', 'Main: com file (experiment): '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.'; chmod 777 '.$serial.' > /dev/null 2>&1');
    }

    if (!file_exists($serial)) {
        deamonlog('error','Error: Fichier '.$serial.' n existe pas');
        exit(1);
    }

    $fifoIN = new fifo($in, 0777, "w" );
    deamonlog('info','Starting with pipe file (to send info to AbeilleParser): '.$in);

    _exec("stty -F ".$serial." sane", $out);
    _exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw", $out);

    $f = fopen($serial, "r");

    // print_r( $f ); echo "\n";

    $transcodage = false;
    $trame = "";
    $test = "";

    while (true) {
        if (!file_exists($serial)) {
            deamonlog('error','CRITICAL Fichier '.$serial.' n existe pas');
            exit(1);
        }

        $car = fread($f, 01);

        $car = bin2hex($car);
        if ($car == "01") {
            $trame = "";
        } else {
            if ($car == "03") {
                deamonlog('debug',date("Y-m-d H:i:s").' -> '.$trame);
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

    deamonlog('error','Fin script AbeilleSerial');

?>
