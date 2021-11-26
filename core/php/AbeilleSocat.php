<?php

    /*
     * AbeilleSocat (daemon)
     *
     * - Using 'socat'
     * - Convert TCP connection to 'tty' like port for later use by 'AbeilleSerialRead'
     */

    include_once __DIR__.'/../config/Abeille.config.php';

    /* Developpers mode ? */
    if (file_exists(dbgFile)) {
        // include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/AbeilleLog.php';
    include_once __DIR__.'/../class/AbeilleTools.class.php';

    logSetConf('', true); // Log to STDOUT until log name fully known (need Zigate number)
    logMessage('info', '>>> Démarrage d\'AbeilleSocat');
    if ($argc < 1) { // Currently expecting <ZigatePort> <LogLevel> <ip:port>
        logMessage('error', 'Port série manquant pour lancement AbeilleSocat => Arret du démon.');
        exit(1);
    }

    $serial = $argv[1];
    $requestedlevel = $argv[2];
    $requestedlevel = '' ? 'none' : $argv[2];
    $ip = '' ? '192.168.4.1' : $argv[3];
    $ipArr = explode(':', $ip);
    if (count($ipArr) > 1) {
        $ip = $ipArr[0];
        $port = $ipArr[1];
    } else // No port => defaulting to 9999 for Wifi zigate
        $port = "9999";

    /* Checking parameters */
    if (!preg_match("(^".wifiLink.")", $serial)) {
        logMessage('info', 'ERREUR: Le port '.$serial.' ne correspond pas à une Zigate WIFI.');
        exit(2);
    }

     /* Now we can extract network number to properly identify log file. */
    $zgId = (int)substr($serial, strlen(wifiLink)); // '/tmp/zigateWifiX' => Zigate number X (ex: 1)
    logSetConf("AbeilleSocat".$zgId.".log", true); // Log to file with line nb check

    //check already running
    $parameters = AbeilleTools::getParameters();
    $running = AbeilleTools::getRunningDaemons();
    $daemons= AbeilleTools::diffExpectedRunningDaemons($parameters,$running);
    logMessage('debug', 'Daemons: '.json_encode($daemons));
    #Two at least expected,the original and this one
    if ($daemons["socat".$zgId] > 1) {
        logMessage('error', 'Le démon est déja lancé ! '.json_encode($daemons));
        exit(3);
    }

    $sudo   = "/usr/bin/sudo";
    // $socat  = "/usr/bin/socat";
    // $parameters = "pty,rawer,echo=0,link=".wifiLink." tcp:".$ip;
    // $parameters = "pty,raw,echo=0,link=".wifiLink." tcp:".$ip;
    // $parameters = "pty,raw,echo=0,link=".$serial." tcp:".$ip;
    $parameters = "-d -d pty,raw,echo=0,link=".$serial." tcp:".$ip.":".$port;
    /* TODO: How to check 'raw' option support ?
       Note: raw is obsolete. rawer should be the default. To be tested. */
    logMessage('info', 'Attention ! Certain systèmes acceptent soit l\'option \'raw\' soit \'rawer\' pour socat. Modifiez la ligne "$parameter=" du fichier AbeilleSocat.php pour mettre la bonne option.');

    logMessage('debug', 'Creating connection from '.$ip.':'.$port.' to '.$serial);
    //
    // $cmd = "socat -d -d -x pty,raw,echo=0,link=/tmp/zigate tcp:192.168.4.8:9999";
    // $cmd = "socat pty,raw,echo=0,link=/tmp/zigate tcp:192.168.4.8:9999";
    //
    // $cmd = "socat -d -d -x pty,raw,echo=0,link=".wifiLink." tcp:192.168.4.8:9999";
    // $cmd = "socat pty,rawer,echo=0,link=".wifiLink." tcp:".$ip.":9999";

    //$cmd = "socat pty,rawer,echo=0,link=".wifiLink." tcp:".$ip.":23"; // jeedomzwave
    // $cmd = "socat pty,rawer,echo=0,link=".wifiLink." tcp:".$ip.":9999"; // abeille
    // $cmd = "socat pty,rawer,echo=0,link=".wifiLink." tcp:".$ip.":23";

    /* TODO: socat output might not be UTF8.
        Can we use iconv -f ISO-8859-1 -t UTF-8//TRANSLIT input.file -o out.file ? */

    // $cmd = "socat pty,rawer,echo=0,link=".wifiLink." tcp:".$ip;
    // $cmd = $sudo." ".$socat." ".$parameters;
    $cmd = $sudo." socat ".$parameters;
    // $cmd = $cmd . ' 2>&1 &';
    // $cmd = $cmd . ' >>/var/www/html/log/AbeilleSOCATTOOL';
    // $cmd = $cmd.' 2>&1';

    logMessage('debug', 'cmd='.$cmd);

    exec($cmd, $output, $result_code);
    if ($result_code != 0)
        logMessage('debug', 'Socat exited with ERROR '.$result_code);

    logMessage('info', '<<< Arret du démon \'AbeilleSocat\'');
?>
