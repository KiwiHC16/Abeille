<?php

    /*
     * AbeilleSocat (daemon)
     *
     * - Using 'socat'
     * - Convert TCP connection to 'tty' like port for later use by 'AbeileSerialRead'
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.php";
    if (file_exists($dbgFile)) {
        include_once $dbgFile;
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

    logSetConf(); // Log to STDOUT until log name fully known (need Zigate number)
    logMessage('info', 'Démarrage d\'AbeilleSocat');
    if ($argc < 1) { // Currently expecting <ZigatePort> <LogLevel> <ip:port>
        logMessage('error', 'Port série manquant pour lancement AbeilleSocat => Arret du démon.');
        exit(1);
    }

    $serial = $argv[1];
    $requestedlevel=$argv[2];
    $requestedlevel=''?'none':$argv[2];
    $ip=''?'192.168.4.1':$argv[3];
    // $clusterTab= AbeilleTools::getJSonConfigFiles('zigateClusters.json'); // Unused

     /* Checking parameters */
     if (!preg_match("(^/dev/zigate)", $serial)) {
        logMessage('info', 'ERREUR inattendue: Le port '.$serial.' ne correspond pas à une Zigate WIFI. Je n\'aurais pas du être lancé.');
        exit(2);
    }

    /* Now we can extract network number to properly identify log file. */
    $abeilleNb = (int)substr($serial, 11); // '/dev/zigateX' => Zigate number X (ex: 1)
    logSetConf("AbeilleSocat".$abeilleNb.".log"); // Log to file with line nb check

    $nohup 	= "/usr/bin/nohup";
    $sudo   = "/usr/bin/sudo";
    $socat 	= "/usr/bin/socat";
    // $parameters = "pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
    // $parameters = "pty,raw,echo=0,link=".$WifiLink." tcp:".$ip;
    $parameters = "pty,raw,echo=0,link=".$serial." tcp:".$ip;
    logMessage('info','Attention certain systemes acceptent l option rawer pour socat et pas d autres. Modifiez la commande en fonction de votre systeme en mettant rawer ou raw dans le fichier AbeilleSocat.php ligne 40');

    $nohup  = "/usr/bin/nohup";
    $sudo   = "/usr/bin/sudo";
    $socat  = "/usr/bin/socat";
    // $parameters = "pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
    // $parameters = "pty,raw,echo=0,link=".$WifiLink." tcp:".$ip;
    // $parameters = "pty,raw,echo=0,link=".$serial." tcp:".$ip;
    $parameters = "-d -d pty,raw,echo=0,link=".$serial." tcp:".$ip;
    /* TODO: How to check 'raw' option support ? */
    logMessage('info', 'Attention ! Certain systèmes acceptent soit l\'option \'raw\' soit \'rawer\' pour socat. Modifiez la ligne "$parameter=" du fichier AbeilleSocat.php pour mettre la bonne option.');

    logMessage('info','Starting reading port '.$serial.' with log level '.$requestedlevel);

    // Boucle sur le lancement de socat et des que socat est lancé bloque la boucle pendant l'execution.
    // while (1) {

        /* Printing socat infos for debug */
        // $cmd = $sudo." ".$socat." -V";
        // $cmd = $cmd . ' >/var/www/html/log/AbeilleSOCATTOOL';
        // $cmd = $cmd . ' 2>&1';

        logMessage('Info','Creation de la connection wifi.');
    //
        // $cmd = "socat -d -d -x pty,raw,echo=0,link=/tmp/zigate tcp:192.168.4.8:9999";
        // $cmd = "socat pty,raw,echo=0,link=/tmp/zigate tcp:192.168.4.8:9999";
    //
        // $cmd = "socat -d -d -x pty,raw,echo=0,link=".$WifiLink." tcp:192.168.4.8:9999";
        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":9999";

        //$cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":23"; // jeedomzwave
        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":9999"; // abeille
        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip.":23";

        /* TODO: socat output might not be UTF8.
           Can we use iconv -f ISO-8859-1 -t UTF-8//TRANSLIT input.file -o out.file ? */

        // $cmd = "socat pty,rawer,echo=0,link=".$WifiLink." tcp:".$ip;
        $cmd = $sudo." ".$socat." ".$parameters;
        // $cmd = $cmd . ' 2>&1 &';
        // $cmd = $cmd . ' >>/var/www/html/log/AbeilleSOCATTOOL';
        $cmd = $cmd . ' 2>&1';
        logMessage('Info', 'Command: '.$cmd);

        exec( $cmd );
        //logMessage('Info','Arret de Socat on relance dans 1 minute.');
       // sleep(60);
    //}

    logMessage('info', 'Arret du démon \'AbeilleSocat\'');
?>
