<?php
    // Simulation de la command getVersion envoyée sur le port serie
    // pour vérifier qu'elle est bien générée.
    
    include("libSerial.php");       // Contient _exec
    include("CmdToAbeille.php");    // contient processCmd()
    
    function checkStty( $tty )
    {
        print_r( $tty );
        echo "\n";
        $settings = explode(";",$tty);
        print_r( $settings );
        echo "\n";
    }
    
    $binarySendToZigateForGetVersion = "01021010021002101003";
    $isTty = 0;
    
    if ( isset($argv[1]) )
    {
        $dest =$argv[1];
        if ( strpos( $dest, "tty" )>0 )
        {
            $isTty = 1;
            if (!is_writable($dest)) { echo "ERROR critique can t write on the file\n"; return; }
            echo "tty: ".$dest."\n";
            $cmd = "stty -F ".$dest." sane";
            echo "cmd: ".$cmd."\n";
            _exec($cmd,$out);
            
            $cmd = "stty -F ".$dest." speed 115200 cs8 -parenb -cstopb raw";
            echo "cmd: ".$cmd."\n";
            _exec($cmd,$out);
            // echo "Setup ttyUSB configuration, resultat: \n";
            // print_r( $out );
            
            // Verif stty
            $cmd = "stty -a -F ".$dest;
            _exec($cmd,$out);
            echo "Verif stty, resultat: \n";
            checkStty( $out[0] );
            
            
        }
    }
    else {
        $dest = "testDataGeneratedToZigate";
    }
    $Command['getVersion']="Version";
    
    // print_r( $dest );
    // print_r( $Comman );
    
    processCmd( $dest, $Command );
    
    if ( $isTty==0 )
    {
        $cmd = "xxd -u -p ".$dest;
        exec( $cmd, $output );
        
        // print_r( $output );
        
        if ( $output[0] == $binarySendToZigateForGetVersion )
        { echo "Ok\n"; }
        else
        { echo "NOk\n"; }
    }
    
    // $cmd = "rm ".$dest;
    // exec( $cmd, $output );
    
    
    
    ?>
