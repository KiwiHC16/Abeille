<?php

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/function.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/fifo.php';

    include_once __DIR__.'/AbeilleLog.php';

    include_once __DIR__.'/../class/AbeilleCmdQueue.class.php';

    $objet = new AbeilleCmdQueue('debug');
    $message = new object();
    $message->priority = 1;

    if (0) {
        $objet->sendCmdToZigate( 'Abeille1', '0092', '0006', "0283E4010102" );
    }

    if (1) {
        if (0) {
            $message->topic = 'CmdAbeille1/25c9/moveToLiftAndTiltBSO';
            $message->payload = 'EP=01&Inclinaison=60&duration=FFFF';
        }  
        
        if (0) {
            $message->topic = 'CmdAbeille1/83E4/OnOff';
            $message->payload = 'Action=Toggle&EP=01';
        }

        if (0) {
            $message->topic = 'CmdAbeille1/83E4/OnOff';
            $message->payload = 'Action=Toggle&EP=01';
        }

        if (0) {
            $message->topic = 'CmdAbeille1/Ruche/reset';
            $message->payload = 'reset';
        }
        
        if (1) {

            $message->topic = 'CmdAbeille1/Ruche/PDM';
            $message->payload = 'E_SL_MSG_PDM_HOST_AVAILABLE';
            $objet->procmsg($message);
            $objet->processCmdQueueToZigate();


        }

        $objet->procmsg($message);
        $objet->processCmdQueueToZigate();
    }
?>
