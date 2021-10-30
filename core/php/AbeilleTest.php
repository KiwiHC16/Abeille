<?php
/*
 This php code allow to inject messages in the system while it's running for tests purposes
 php AbeilleTest.php testNumber
*/

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../../core/config/Abeille.config.php';
    include_once __DIR__.'/AbeilleLog.php';
    include_once __DIR__.'/../class/Abeille.class.php';
    include_once __DIR__.'/../class/AbeilleParser.class.php';
    include_once __DIR__.'/../class/AbeilleCmdQueue.class.php';

    $test = $argv[1];
    echo "Running test: ".$test."\n";

    // "Send a command in binary format directly to AbeilleCMd to be written on serie port\n"
    if ( (000<=$test) && ($test<100) ) {
        echo "To execute this test Abeille Daemon has to be stopped.\n";
        $cmdQueue = new AbeilleCmdQueue('debug');

        if ($test==1) {
            echo "Send a command in binary format directly to AbeilleCMd to be written on serie port\n";
            $cmdQueue->sendCmdToZigate( 'Abeille1', '0092', '0006', "0283E4010102" );
        }

        if ($test==2) {
            echo "ON / OFF with no effects: Toggle avec command zigate 0530 idem as test == 1\n";

            $addressMode            = "02";                // 01 pour groupe, 02 pour NE
            $targetShortAddress     = $argv[2];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = "01";
            $profile                = "0104";
            $cluster                = "0006";
            $securityMode           = "02";
            $radius                 = "30";
            // $dataLength = "16";

            $FrameControlField      = "11";
            $SQN                    = "00";
            $cmd                    = "02"; // Toggle

            $data2 = $FrameControlField . $SQN . $cmd;
            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $cluster . $profile . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $cmdQueue->sendCmdToZigate( 'Abeille1', '0530', $lenth, $data );
        }

        if ($test==3) {
            echo "Windows Covering test for Store Ikea: Up\n";

            $addressMode            = "02";                // 01 pour groupe, 02 pour NE
            $targetShortAddress     = $argv[2];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = "01";
            $profile                = "0104";
            $cluster                = "0102";
            $securityMode           = "02";
            $radius                 = "30";
            // $dataLength = "16";

            $FrameControlField      = "11";
            $SQN                    = "00";
            $cmd                    = "00"; // 00: Up, 01: Down, 02: Stop, 04: Go to lift value (not supported), 05: Got to lift pourcentage.

            $data2 = $FrameControlField . $SQN . $cmd;
            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $cluster . $profile . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $cmdQueue->sendCmdToZigate( 'Abeille1', '0530', $lenth, $data );

        }

        if ($test==4) {
            echo "Windows Covering test for Store Ikea: lift to %\n";

            $addressMode            = "02";                // 01 pour groupe, 02 pour NE
            $targetShortAddress     = $argv[2];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = "01";
            $profile                = "0104";
            $cluster                = "0102";
            $securityMode           = "02";
            $radius                 = "30";
            // $dataLength = "16";

            $FrameControlField      = "11";
            $SQN                    = "00";
            $cmd                    = "05"; // 00: Up, 01: Down, 02: Stop, 04: Go to lift value (not supported), 05: Got to lift pourcentage.
            $liftValue              = $argv[3];

            $data2 = $FrameControlField . $SQN . $cmd . $liftValue . $liftValue;
            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $cluster . $profile . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $cmdQueue->sendCmdToZigate( 'Abeille1', '0530', $lenth, $data );

        }
    }

    // Send a L2 Command to the queue to be processed
    if ( (100<=$test) && ($test<200) ) {
        echo "To execute this test Abeille Daemon has to run.\n";

        $msgAbeille = new MsgAbeille;
        $msgAbeille->priority = 1;

        if ($test==100) {
            echo "Test pour BSO\n";
            echo "php AbeilleTest.php 100 adrese lift inclinaison duree\n";
            echo "adresse: 4 digit\n";
            echo "lift: 2 digit\n";
            echo "inclinaison: 2 digit\n";
            echo "duree: 4 digit, FFFF pour vitesse max\n";

            $msgAbeille->message['topic'] = 'CmdAbeille1/'.$argv[2].'/moveToLiftAndTiltBSO';
            $msgAbeille->message['payload'] = 'EP=01&lift='.$argv[3].'&inclinaison='.$argv[4].'&duration='.$argv[5];
        }

        if ($test==108) {
            echo "On Off Test\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/'.$argv[2].'/OnOff';
            $msgAbeille->message['payload'] = 'Action=Toggle&EP='.$argv[3];
        }

        if ($test==109) {
            echo "On Off Test\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/'.$argv[2].'/OnOff';
            $msgAbeille->message['payload'] = 'Toggle';
        }

        if ($test==110) {
            echo "Reset Ruche\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/0000/reset';
            $msgAbeille->message['payload'] = 'reset';
        }

        if ($test==111) {
            echo "Used to Test PDM messages and dev the PDM feature\n";
            $msgAbeille->message['topic'] = 'CmdAbeille1/0000/PDM';
            $msgAbeille->message['payload'] = 'E_SL_MSG_PDM_HOST_AVAILABLE';
        }

        if ($test==112) {
            echo "Test envoie Cmd With Tempo\n";
            $msgAbeille->message['topic'] =  'Tempo'.'CmdAbeille1/'.$argv[2].'/readAttribute' . '&time=' . (time() + 3);
            $msgAbeille->message['payload'] = 'ep=01&clustId=0B04&attrId=050B';
        }

        // All Cluster 0000 - attribut 0 to 7
        if ($test==113) {
            echo "Test envoie Cmd to get ZCL version\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0000';
        }

        if ($test==114) {
            echo "Test envoie Cmd to get Application version\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0001';
        }

        if ($test==115) {
            echo "Test envoie Cmd to get Stack version\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0002';
        }

        if ($test==116) {
            echo "Test envoie Cmd to get HW version\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0003';
        }

        if ($test==117) {
            echo "Test envoie Cmd to get ManufacturerName\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0004';
        }

        if ($test==118) {
            echo "Test envoie Cmd to get ModelIdentifier\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0005';
        }

        if ($test==119) {
            echo "Test envoie Cmd to get DateCode\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0006';
        }

        if ($test==120) {
            echo "Test envoie Cmd to get PowerSource\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0000&attrId=0007';
        }

        if ($test==121) {
            echo "Test envoie Cmd to get batterie voltage\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0001&attrId=0021';
        }

        if ($test==122) {
            echo "Test envoie Cmd to bind a cluster\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/bindShort';
            $msgAbeille->message['payload'] = 'targetExtendedAddress=00158D0001FFD6E9&targetEndpoint=01&ClusterId=0102&reportToAddress=00158D0001B22E24';
        }

        if ($test==123) {
            echo "Test envoie Cmd to setReport an attribut\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/setReport';
            $msgAbeille->message['payload'] = 'targetEndpoint=01&ClusterId=0102&AttributeId=0008&AttributeType=20';
        }

        if ($test==124) {
            echo "Test envoie Cmd to DiscoverAttributesCommand\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/discoverAttributes';
            $msgAbeille->message['payload'] = 'ep=01&clustId='.$argv[3].'&startAttrId=0000&maxAttrId=FF';
        }

        if ($test==125) {
            echo "Test envoie Cmd to Discover IEEE\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/IEEE_Address_request';
            $msgAbeille->message['payload'] = 'shortAddress='.$argv[2];
        }

        if ($test==126) {
            echo "Test recuperation parametre Scene Count\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0005&attrId=0000';
        }

        if ($test==127) {
            echo "Test recuperation parametre Scene Current\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0005&attrId=0001';
        }

        if ($test==128) {
            echo "Test recuperation parametre Group Current for scene\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0005&attrId=0002';
        }

        if ($test==129) {
            echo "Test recuperation parametre scene active\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0005&attrId=0003';
        }

        if ($test==130) {
            echo "Remove Group Tele Innr EP 1, 3-8\n";
            $msgAbeille->message['topic']   = 'CmdAbeille1/0000/removeGroup';
            $msgAbeille->message['payload'] = 'address='.$argv[2].'&DestinationEndPoint='.$argv[3].'&groupAddress='.$argv[4];
        }

        if ($test==131) {
            echo "Read white spectre color of an Ikea bulb\n";
            $msgAbeille->message['topic']   = 'CmdAbeille2/'.$argv[2].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=01&clustId=0300&attrId=0007';
        }

        if ($test==132) {
            echo "Set Time to Zigate\n";
            $msgAbeille->message['topic']   = 'CmdAbeille' . $argv[2] . '/0000/setZgTimeServer';
            $msgAbeille->message['payload'] = '';
        }

        if ($test==133) {
            echo "Get Time from Zigate\n";
            $msgAbeille->message['topic']   = 'CmdAbeille' . $argv[2] . '/0000/getZgTimeServer';
            $msgAbeille->message['payload'] = '';
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
        switch($test) {
            case 200:
                echo "Send a Systeme Message to the Ruche to be used by a scenario by the user\n";
                // public static function publishMosquitto($queueId, $priority, $topic, $payload)
                Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille1/0000/SystemMessage", "Le message");
                break;
            case 201:
                echo "Send a Systeme Message to the Ruche1 and clean it after 5 sec\n";
                AbeilleTools::sendMessageToRuche("daemonTest1","this is test message from zigate1");
                sleep( 5);
                AbeilleTools::sendMessageToRuche("daemonTest1","");
                break;
            case 202:
                echo "Send a Systeme Message to the Ruche3 and clean it after 5 sec\n";
                AbeilleTools::sendMessageToRuche("daemonTest3","this is test message from zigate3");
                sleep( 5);
                AbeilleTools::sendMessageToRuche("daemonTest3","");
                break;
            case 203:
                echo "Send a Systeme Message to the Ruche1 and 2 and clean them after 5 sec\n";
                AbeilleTools::sendMessageToRuche("daemonTest1","this is test message from zigate1");
                AbeilleTools::sendMessageToRuche("daemonTest2","this is test message from zigate2");
                sleep( 5);
                AbeilleTools::clearSystemMessage($parameters);
                break;
            case 204:
                echo "Send a Systeme Message to the Ruche1 and 2 and clean zigate 1 after 5 sec\n";
                AbeilleTools::sendMessageToRuche("daemonTest1","this is test message from zigate1");
                AbeilleTools::sendMessageToRuche("daemonTest2","this is test message from zigate2");
                sleep( 5);
                AbeilleTools::clearSystemMessage($parameters,'1');
                break;
            case 205:
                echo "Send a Systeme Message to the Ruche to create an eq based on a template\n";
                Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille1/FFFF/0000-01-0005", "BSO");
                break;
        }
    }

    // Test Methode/Function
    if ( (300<=$test) && ($test<400) ) {
        switch ($test) {
            case 300:
                echo "Test Cron function\n";
                Abeille::cron();
            break;
            case 301:
                echo "Test Cron15 function\n";
                Abeille::cron15();
            break;
            case 302:
                echo "Test CronHourly function\n";
                Abeille::cronHourly();
            break;
            case 303:
                echo "Test CronDaily function\n";
                Abeille::cronDaily();
            break;
            case 310:
                echo "Test getIEEE\n";
                var_dump(Abeille::getIEEE('Abeille1/B529'));
            break;
            case 311:
                echo "Test getEqFromIEEE\n";
                var_dump(Abeille::getEqFromIEEE('842E14FFFE1396F5'));
            break;
            case 320:
                echo "Test AbeilleParser::execAtCreationCmdForOneNE(logicalId)\n";
                AbeilleParser::execAtCreationCmdForOneNE($argv[2]);
            break;
            case 330:
                echo "Decodage trame prise Xiaomi a l adressse: 4AAE\n";
                $trame = "8002005495000104FCC00101024AAE0200001C5F11E40AF700413D64100103281A9839FE542C429539F187773E963900200F459739A8223C43052101009A20100821160107270000000000000000092100040B20009B100199";
                $abeilleParser = new AbeilleParser;
                $abeilleParser->protocolDatas("Abeille1", $trame);
            break;
            case 331:
                echo "Test daemon monitoring\n";

                echo "Parameters:\n";
                // $parameters = Abeille::getParameters();
                $json = '{"parametersCheck":"ok","parametersCheck_message":"","AbeilleParentId":"125","AbeilleType1":"USB","AbeilleSerialPort1":"\/dev\/ttyUSB3","IpWifiZigate1":"192.168.4.1:23","AbeilleActiver1":"Y","AbeilleType2":"WIFI","AbeilleSerialPort2":"\/dev\/zigate2","IpWifiZigate2":"192.168.4.106:23","AbeilleActiver2":"Y","AbeilleType3":"USB","AbeilleSerialPort3":"\/dev\/ttyUSB2","IpWifiZigate3":"192.168.4.119:23","AbeilleActiver3":"Y","AbeilleType4":"WIFI","AbeilleSerialPort4":"\/dev\/zigate4","IpWifiZigate4":"192.168.4.107:23","AbeilleActiver4":"Y"}';
                $parameters = json_decode($json,1);
                // var_dump($parameters);
                // echo "Parameters: ".json_encode($parameters)."\n";
                // echo "Nb zigate: ".$parameters['zigateNb']."\n";

                // $running = AbeilleTools::getRunningDaemons();
                // Kiwi Pro Simu
                $running[] = "845 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSocat.php /dev/zigate2 debug 192.168.4.106:23";
                $running[] = "847 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSocat.php /dev/zigate4 debug 192.168.4.107:23";
                $running[] = "873 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille1 /dev/ttyUSB3 debug";
                $running[] = "878 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille2 /dev/zigate2 debug";
                $running[] = "883 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille3 /dev/ttyUSB2 debug";
                $running[] = "892 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille4 /dev/zigate4 debug";
                $running[] = "896 /usr/bin/php /var/www/html/plugins/Abeille/core/class/AbeilleParser.php debug";
                $running[] = "899 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../../core/class/AbeilleCmd.php debug";

                echo "Running: ".json_encode($running)."\n";
                echo "\n";

                $diff = AbeilleTools::diffExpectedRunningDaemons($parameters, $running);
                echo "Diff: ".json_encode($diff)."\n";
                echo "\n";

            break;
            }
    }

    // Test core function of Jeedom
    if ( (400<=$test) && ($test<500) ) {
        switch ($test) {
            case 400:
                echo "Cmd ByValue: retourne les commandes qui ont value ou #value# dans le champ value.\n";
                $newVal = 53;
                $cmdAction = cmd::byId(23897);
                echo "Cmd Action : ".$cmdAction->getName()."\n";
                $cmdInfo = $cmdAction->getCmdValue();
                echo "Cmd Info associée : ".$cmdInfo->getName()." - ".$cmdInfo->execCmd()."\n";
                $cmdInfo->event($newVal);
                echo "Cmd Info associée : ".$cmdInfo->getName()." - ".$cmdInfo->execCmd()."\n";
            break;
        }
    }

    // Test DIY
    if ( (500<=$test) && ($test<600) ) {

        if ($test==500) {
            echo "Test envoie Cmd to get ZCL version\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep=08&clustId=0000&attrId=0000';
        }

        if ($test==504) {
            echo "Test envoie Cmd to get ManufacturerName\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep='.$argv[4].'&clustId=0000&attrId=0004';
        }

        if ($test==505) {
            echo "Test envoie Cmd to get ModelIdentifier\n";
            $msgAbeille->message['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msgAbeille->message['payload'] = 'ep='.$argv[4].'&clustId=0000&attrId=0005';
        }

        // php AbeilleTest.php 510 2 F0C0 08
        // Led Red DIO6: toglle et visiblement un PWM to control level.
        if ($test==510) {
            echo "On Off Test\n";
            $msgAbeille->message['topic'] = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/OnOff';
            $msgAbeille->message['payload'] = 'Action=Toggle&EP='.$argv[4];
        }

        // Control level Led Rouge
        // Minimum puissance
        // php AbeilleTest.php 511 2 F0C0 08 02
        // Toute puissance
        // php AbeilleTest.php 511 2 F0C0 08 99
        if ($test==511) {
            echo "Level Test\n";
            $msgAbeille->message['topic'] = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/setLevel';
            $msgAbeille->message['payload'] = 'EP='.$argv[4].'&Level='.$argv[5].'&duration=10';
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

    echo "End of the test.\n";
?>
