<?php


    /***
     * AbeilleSerialRead
     *
     * Get information from selected port (/dev/ttyUSB0 pour TTL ou socat pour WIFI), transcode data from binary to hex.
     * and write it to FIFO file.
     *
     * /usr/bin/php /var/www/html/plugins/Abeille/core/class/../../core/class/AbeilleSerialRead.php /tmp/zigate debug
     *
     */


    include_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/function.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/fifo.php';
    
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';
    
    function deamonlog($loglevel='NONE',$message=""){
        Tools::deamonlogFilter($loglevel,'Abeille', 'AbeilleSerialRead', $message);
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

    $abeille        = $argv[1];
    $serial         = $argv[2];
    $requestedlevel = $argv[3];
    
    $clusterTab= Tools::getJSonConfigFiles('zigateClusters.json');

    $queueKeySerieToParser    = msg_get_queue(queueKeySerieToParser);
    $queueKeySerieToParserSem = sem_get(queueKeySerieToParser);
    
    deamonlog('info','Starting reading port '.$serial.' and transcoding to queueKeySerieToParser with log level: '.$requestedlevel);

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        deamonlog('info', 'Main: com file (experiment): '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.'; chmod 777 '.$serial.' > /dev/null 2>&1');
    }

    exec(system::getCmdSudo().' chmod 777 '.$serial.' > /dev/null 2>&1');
    
    if (!file_exists($serial)) {
        deamonlog('error','Error: Fichier '.$serial.' n existe pas');
        exit(1);
    }

    exec("stty -F ".$serial." sane", $out);
    exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb -echo raw", $out);
    
    // Si le lien tombe a l ouverture de $serial c est peut etre par ce que le serveur n'est pas dispo.
    // Il semblerai que le lien pts soit créé même si la liaison n'est pas établie.
    $f = fopen($serial, "r");
    
    $transcodage = false;
    $trame = "";
    $test = "";
    
    while (true) {
        if (!file_exists($serial)) {
            deamonlog('error','CRITICAL Fichier '.$serial.' n existe pas');
            fclose($f);
            exit(1);
        }

        $car = fread($f, 01);

        $car = bin2hex($car);
        if ($car == "01") {
            $trame = "";
        } else {
            if ($car == "03") {
                deamonlog('debug',date("Y-m-d H:i:s").' -> '.$trame);
                $trameToSend = array( 'dest'=>$abeille, 'trame'=>$trame );
                // $trameToSend = basename($serial).'|'.$trame ;
                //sem_acquire( $queueKeySerieToParserSem );
                if (msg_send( $queueKeySerieToParser, 1, json_encode($trameToSend), false, false)) {
                    deamonlog('info', 'Msg sent queueKeySerieToParser ('.queueKeySerieToParser.'): '.json_encode($trame));
                }
                else {
                    deamonlog('error', 'Msg sent queueKeySerieToParser ('.queueKeySerieToParser.'): Could not send Msg');
                }
                // sem_release( $queueKeySerieToParserSem );
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

    deamonlog('error','Fin script AbeilleSerial');

?>
