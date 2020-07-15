<?php

    /*
     * AbeilleSocat (daemon)
     *
     * - Using 'socat'
     * - Convert TCP connection to 'tty' like port for later use by 'AbeileSerialRead'
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
    $sudo   = "/usr/bin/sudo";
    $socat 	= "/usr/bin/socat";
    // $parameters = "pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
    // $parameters = "pty,raw,echo=0,link=".$WifiLink." tcp:".$ip;
    $parameters = "pty,raw,echo=0,link=".$serial." tcp:".$ip;
    deamonlog('info','Attention certain systemes acceptent l option rawer pour socat et pas d autres. Modifiez la commande en fonction de votre systeme en mettant rawer ou raw dans le fichier AbeilleSocat.php ligne 40');


    if (!preg_match("(^/dev/zigate)", $serial)) {
    // if ( $serial != $WifiLink ) {
        deamonlog('info','Pas de connection wifi donc je ne fais rien, et je n aurais pas du être lancé.');
        exit(1);
    }

    deamonlog('info','Starting reading port '.$serial.' with log level '.$requestedlevel);

    // Boucle sur le lancement de socat et des que socat est lancé bloque la boucle pendant l'execution.
    // while (1) {

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

        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
        $cmd = $sudo." ".$socat." ".$parameters ;
        deamonlog('Info','Command: '.$cmd);
        // $cmd = $cmd . ' 2>&1 &';
        $cmd = $cmd . ' 2>&1';

        exec( $cmd );
        //deamonlog('Info','Arret de Socat on relance dans 1 minute.');
       // sleep(60);
    //}

    deamonlog('info','Fin script AbeilleSocat');

    ?>
