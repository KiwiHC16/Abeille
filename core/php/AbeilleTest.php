<?php
/*
 This php code allow to inkect messages in the system while it's running for tests purposes
 Set the "if condition" to run the test you want.
*/

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/function.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/fifo.php';

    include_once __DIR__.'/AbeilleLog.php';

    include_once __DIR__.'/../class/Abeille.class.php';
    include_once __DIR__.'/../class/AbeilleCmdQueue.class.php';

    $test = $argv[1];
    echo "Running test: ".$test."\n";
    
    if ( (000<=$test) && ($test<100) ) {
        echo "To execute this test Abeille Daemon has to be stopped.\n";
        $cmdQueue = new AbeilleCmdQueue('debug');
        
        if ($test==1) {
            echo "Send a command in binary format directly to AbeilleCMd to be written on serie port\n";
            $cmdQueue->sendCmdToZigate( 'Abeille1', '0092', '0006', "0283E4010102" );
        }
    }

    // Send a L2 Command to the queue to be processed 
    if ( (100<=$test) && ($test<200) ) {
        echo "To execute this test Abeille Daemon has to run.\n";

        $msgAbeille = new MsgAbeille;
        $msgAbeille->priority = 1;

        if ($test==100) {
            echo "Test pour BSO\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/3EFE/moveToLiftAndTiltBSO';
            $msgAbeille->message['payload'] = 'EP=01&Inclinaison=60&duration=FFFF';
        }  
        
        if ($test==101) {
            echo "On Off Test\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/3EFE/OnOff';
            $msgAbeille->message['payload'] = 'Action=Toggle&EP=01';
        }

        if ($test==102) {
            echo "Reset Ruche\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/Ruche/reset';
            $msgAbeille->message['payload'] = 'reset';
        }
        
        if ($test==103) {
            echo "Used to Test PDM messages and dev the PDM feature\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/Ruche/PDM';
            $msgAbeille->message['payload'] = 'E_SL_MSG_PDM_HOST_AVAILABLE';
        }

        // Send the command to the queue for processing
        $queueKeyAbeilleToCmd = msg_get_queue(queueKeyAbeilleToCmd);
        if ( $queueKeyAbeilleToCmd ) {
            if (msg_send($queueKeyAbeilleToCmd, priorityUserCmd, $msgAbeille, true, false)) {
                echo "Msg sent: " . json_encode($msgAbeille) . "\n";
            } else {
                echo "Could not send Msg\n";
            }
        }
        else {
            echo "Can t connect to the queue !\n";
        }
        

    }

    // Send a message to Abeille's queue
    if ( (200<=$test) && ($test<300) ) {
        if ($test==200) {
            echo "Send a Systeme Message to the Ruche to be used by a scenario by the user\n";
            // public static function publishMosquitto($queueId, $priority, $topic, $payload)
            Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille1/Ruche/SystemMessage", "Le message");
        }

        if ($test==201) {
            echo "Send a Systeme Message to the SonOff Inter to tesst Batterie level info command\n";
            // public static function publishMosquitto($queueId, $priority, $topic, $payload)
            Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille1/9CD5/0001-01-0021", "37");
        }
        
    }

    // Test Methode/Function
    if ( (300<=$test) && ($test<400) ) {
        if ($test==300) {
            echo "Test Cron function\n";
            Abeille::cron();
        }
        if ($test==301) {
            echo "Test Cron15 function\n";
            Abeille::cron15();
        }
        if ($test==302) {
            echo "Test CronHourly function\n";
            Abeille::cronHourly();
        }
        if ($test==303) {
            echo "Test CronDaily function\n";
            Abeille::cronDaily();
        }

        if ($test==310) {
            echo "Test getIEEE\n";
            var_dump( Abeille::getIEEE('Abeille1/B529') );
        }
        if ($test==311) {
            echo "Test getEqFromIEEE\n";
            var_dump( Abeille::getEqFromIEEE('842E14FFFE1396F5') );
        }

        if ($test==320) {
            echo "Test AbeilleParser::execAtCreationCmdForOneNE\n";
                $address = "Abeille1/7147";
                $cmds = AbeilleCmd::searchConfigurationEqLogic( Abeille::byLogicalId( $address,'Abeille')->getId(), 'execAtCreation', 'action' );
                foreach ( $cmds as $key => $cmd ) {
                    // self::deamonlog('debug', 'execAtCreationCmdForOneNE: '.$cmd->getName().' - '.$cmd->getConfiguration('execAtCreation').' - '.$cmd->getConfiguration('execAtCreationDelay') );
                    // Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInclusion, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".(time()+$cmd->getConfiguration('PollingOnCmdChangeDelay')), $cmd->getConfiguration('request') );
                    echo 'execAtCreationCmdForOneNE: '.$cmd->getName().' - '.$cmd->getConfiguration('execAtCreation').' - '.$cmd->getConfiguration('execAtCreationDelay')."\n";
                }
            
        }

    }

    echo "End of the test.\n";
?>
