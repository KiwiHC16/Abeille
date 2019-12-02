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
        Tools::deamonlogFilter($loglevel, 'Abeille', 'AbeilleSocat', $message);
        echo $message."\n";
    }
    
    
    /* -------------------------------------------------------------------- */
    
    
    $serial = $argv[1];
    $requestedlevel=$argv[2];
    $requestedlevel=''?'none':$argv[2];
    $ip=''?'192.168.4.1':$argv[3];
    $clusterTab= Tools::getJSonConfigFiles('zigateClusters.json');
    
    $nohup 	= "/usr/bin/nohup";
    $socat 	= "/usr/bin/socat";
    // $parameters = "pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
    $parameters = "pty,raw,echo=0,link=".$WifiLink." tcp:".$ip;
    
    if ( $serial != $WifiLink ) {
        deamonlog('info','Pas de connection wifi donc je ne fais rien, et je n aurais pas du être lancé.');
        exit(1);
    }
    
    deamonlog('info','Starting reading port '.$serial.' with log level '.$requestedlevel);
    
    while (1) {
        
        deamonlog('Info','Creation de la connection wifi.');
	//
        // $cmd = "socat -d -d -x pty,raw,echo=0,link=/tmp/zigate tcp:192.168.4.8:9999";
        // $cmd = "socat pty,raw,echo=0,link=/tmp/zigate tcp:192.168.4.8:9999";
	//
        // $cmd = "socat -d -d -x pty,raw,echo=0,link=".$WifiLink." tcp:192.168.4.8:9999";
        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":9999";
        
        //$cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":23"; // jeedomzwave
        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":9999"; // abeille
        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":23";
        
        $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
        $cmd = $socat." ".$parameters ;
        deamonlog('Info','Command: '.$cmd);
        // $cmd = $cmd . ' 2>&1 &';
        $cmd = $cmd . ' 2>&1';
        
        exec( $cmd );
        deamonlog('Info','Arret de Socat on relance dans 1 minute.');
        sleep(60);
    }
    
    deamonlog('info','Fin script AbeilleSocat');
    
    ?>
