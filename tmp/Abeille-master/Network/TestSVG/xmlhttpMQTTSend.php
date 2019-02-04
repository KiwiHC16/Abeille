<?php
    
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    
    echo "Begin - ";
    echo 'debug: Envoi du message ->'.$_GET['payload'].'<- vers ->'.$_GET['topic'].'<-';
    
    $parameters_info = Abeille::getParameters();
    
    $publish = new Mosquitto\Client();
    
    $publish->setCredentials( $parameters_info['AbeilleUser'], $parameters_info['AbeillePass'] );
    
    $publish->connect( $parameters_info['AbeilleAddress'], $parameters_info['AbeillePort'], 60 );
    $publish->publish( str_replace('_','/',$_GET['topic']), $_GET['payload'], 0, 0);
    for ($i = 0; $i < 100; $i++) {
        // Loop around to permit the library to do its work
        $publish->loop(1);
    }
    $publish->disconnect();
    unset($publish);
    
    echo "Ending - ";
    
    ?>
