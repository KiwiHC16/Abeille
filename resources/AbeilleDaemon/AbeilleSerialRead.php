<?php
    include("includes/config.php");
    include("includes/fifo.php");
    
    function _exec($cmd, &$out = null)
    {
        $desc = array(
                      1 => array("pipe", "w"),
                      2 => array("pipe", "w")
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
    
    if (!file_exists($serial))
    { echo "Error: Fichier ".$serial." n existe pas\n";
        exit(1);
    }
    

    
    echo "Serial port used: ".$serial."\n";
    
    $fifoIN = new fifo( $in, 'w+' );
    
    _exec("stty -F ".$serial." sane",$out);
    echo "Setup ttyUSB default configuration, resultat: \n";
    print_r( $out );
    
    _exec("stty -F ".$serial." speed 115200 cs8 -parenb -cstopb raw",$out);
    echo "Setup ttyUSB configuration, resultat: \n";
    print_r( $out );
    
    $f = fopen($serial, "r");
    
    print_r( $f ); echo "\n";
    
    $transcodage=false;
    $trame="";
    $test="";
    
    while (true)
    {
    if (!file_exists($serial))
    { echo "Fichier ".$serial." n existe pas\n";
        exit(1);
    }
    
        $car=fread($f,01);
        
        $car=bin2hex($car);
        if ($car=="01")
        {
            $trame="";
        }
        else if ($car=="03")
        {
            echo date("Y-m-d H:i:s")." -> ".$trame."\n";
            $fifoIN->write($trame."\n");
        }else if($car=="02")
        {
            $transcodage=true;
        }else{
            if ($transcodage)
            {
                $trame.= sprintf("%02X",(hexdec($car) ^ 0x10));
                
            }else{
                
                $trame.=$car;
            }
            $transcodage=false;
        }
        
    }
    
    fclose($f);
    $fifoIN->close();
    
    ?>
