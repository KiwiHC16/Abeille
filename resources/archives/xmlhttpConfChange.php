<?php
    
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    
    // echo "Begin - ";
    // echo 'debug: Envoi du message ->'.$_GET['payload'].'<- vers ->'.$_GET['topic'].'<-';
    
    $parameters_info = Abeille::getParameters();
    
    Abeille::CmdAffichage($_GET['topic'],$_GET['payload']);

    // echo "Ending - ";
    
    ?>
