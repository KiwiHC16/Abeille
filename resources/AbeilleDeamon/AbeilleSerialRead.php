<?php
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';

    include("includes/config.php");
    include("includes/fifo.php");
    include("lib/Tools.php");


  function getNumberFromLeve($loglevel){
        if (strcasecmp($loglevel, "NONE")==0){$iloglevel=0;}
        if (strcasecmp($loglevel,"ERROR")==0){$iloglevel=1;}
        if (strcasecmp($loglevel,"WARNING")==0){$iloglevel=2;}
        if (strcasecmp($loglevel,"INFO")==0){$iloglevel=3;}
        if (strcasecmp($loglevel,"DEBUG")==0){$iloglevel=4;}
        return $iloglevel;
    }

    /***
     * if loglevel is lower/equal than the app requested level then message is written
     *
     * @param string $loglevel
     * @param string $message
     */
    function deamonlog($loglevel='NONE',$message =''){
        if (strlen($message)>=1  &&  getNumberFromLeve($loglevel) <= getNumberFromLeve($GLOBALS["requestedlevel"]) ) {
            fwrite(STDOUT, 'AbeilleSerialRead: '.date("Y-m-d H:i:s").' '.$message . PHP_EOL); ;
        }
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

    deamonlog('info','Starting reading port '.$serial.' with log level '.$requestedlevel);

    if ($serial == 'none') {
        $serial = $resourcePath.'/COM';
        deamonlog('info', 'Main: debug for com file: '.$serial);
        exec(system::getCmdSudo().'touch '.$serial.'chmod 777 '.$serial.' > /dev/null 2>&1');
    }


    if (!file_exists($serial)) {
        deamonlog('error','AbeilleSerialRead: Error: Fichier '.$serial.' n existe pas');
        exit(1);
    }


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
