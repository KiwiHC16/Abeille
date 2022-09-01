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

    // Dummy function to avoid to find the right one which is not needed for those tests
    function cmdLog($loglevel = 'NONE', $message = "", $isEnable = 1) {
        echo $message."\n";
    }

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

        if ($test==5) {
            echo "Curtain test Up\n";

            $Command['addr']="727C";
            $Command['ep']="01";

            $cmd = "0530";

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <profile ID: uint16_t>
            // <cluster ID: uint16_t>
            // <security mode: uint8_t>
            // <radius: uint8_t>
            // <data length: uint8_t>

            //  ZCL Control Field
            //  ZCL SQN
            //  Command Id
            //  ....

            $addrMode   = "02";
            $addr       = $Command['addr'];
            $srcEp      = "01";
            $dstEp      = $Command['ep'];
            $profId     = "0104";
            $clustId    = 'EF00';
            $secMode    = "02";
            $radius     = "1E";

            /* ZCL header */
            $fcf        = "11"; // Frame Control Field
            $sqn        = "23";
            $cmdId      = "00";

            $field1         = "00";
            $counterTuya    = "01"; // Set to 1, in traces increasse all the time. Not sure if mandatory to increase.
            $field3         = "01";
            $field4         = "04";
            $field5         = "00";
            $field6         = "01";
            $cmdIdTuya      = "01"; // $Command['cmd'];

            $data2 = $fcf.$sqn.$cmdId.$field1.$counterTuya.$field3.$field4.$field5.$field6.$cmdIdTuya;
            $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

            $data1 = $addrMode.$addr.$srcEp.$dstEp.$clustId.$profId.$secMode.$radius.$dataLen2;
            $data = $data1.$data2;
            // $lenth = sprintf("%04x", strlen($data) / 2);

            echo $data."\n";
            $cmdQueue->sendCmdToZigate( 'Abeille1', $cmd, $data );

        }
    }

    // Send a L2 Command to the queue to be processed
    if ( (100<=$test) && ($test<200) ) {
        echo "To execute this test Abeille Daemon has to run.\n";

        $msg = array();
        // $msgAbeille->priority = 1;

        if ($test==100) {
            echo "Test pour BSO\n";
            echo "php AbeilleTest.php 100 adrese lift inclinaison duree\n";
            echo "adresse: 4 digit\n";
            echo "lift: 2 digit\n";
            echo "inclinaison: 2 digit\n";
            echo "duree: 4 digit, FFFF pour vitesse max\n";

            $msg['topic'] = 'CmdAbeille1/'.$argv[2].'/moveToLiftAndTiltBSO';
            $msg['payload'] = 'EP=01&lift='.$argv[3].'&inclinaison='.$argv[4].'&duration='.$argv[5];
        }

        if ($test==108) {
            echo "On Off Test\n";
            $msg['topic'] = 'CmdAbeille1/'.$argv[2].'/OnOff';
            $msg['payload'] = 'Action=Toggle&EP='.$argv[3];
        }

        if ($test==109) {
            echo "On Off Test\n";
            $msg['topic'] = 'CmdAbeille1/'.$argv[2].'/OnOff';
            $msg['payload'] = 'Toggle';
        }

        if ($test==110) {
            echo "Reset Ruche\n";
            $msg['topic'] = 'CmdAbeille1/0000/reset';
            $msg['payload'] = 'reset';
        }

        if ($test==111) {
            echo "Used to Test PDM messages and dev the PDM feature\n";
            $msg['topic'] = 'CmdAbeille1/0000/PDM';
            $msg['payload'] = 'E_SL_MSG_PDM_HOST_AVAILABLE';
        }

        if ($test==112) {
            echo "Test envoie Cmd With Tempo\n";
            $msg['topic'] =  'Tempo'.'CmdAbeille1/'.$argv[2].'/readAttribute' . '&time=' . (time() + 3);
            $msg['payload'] = 'ep=01&clustId=0B04&attrId=050B';
        }

        // All Cluster 0000 - attribut 0 to 7
        if ($test==113) {
            echo "Test envoie Cmd to get ZCL version\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0000';
        }

        if ($test==114) {
            echo "Test envoie Cmd to get Application version\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0001';
        }

        if ($test==115) {
            echo "Test envoie Cmd to get Stack version\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0002';
        }

        if ($test==116) {
            echo "Test envoie Cmd to get HW version\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0003';
        }

        if ($test==117) {
            echo "Test envoie Cmd to get ManufacturerName\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0004';
        }

        if ($test==118) {
            echo "Test envoie Cmd to get ModelIdentifier\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0005';
        }

        if ($test==119) {
            echo "Test envoie Cmd to get DateCode\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0006';
        }

        if ($test==120) {
            echo "Test envoie Cmd to get PowerSource\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0000&attrId=0007';
        }

        if ($test==121) {
            echo "Test envoie Cmd to get batterie voltage\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0001&attrId=0021';
        }

        if ($test==122) {
            echo "Test envoie Cmd to bind a cluster\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/bindShort';
            $msg['payload'] = 'targetExtendedAddress=00158D0001FFD6E9&targetEndpoint=01&ClusterId=0102&reportToAddress=00158D0001B22E24';
        }

        if ($test==123) {
            echo "Test envoie Cmd to setReport an attribut\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/setReport';
            $msg['payload'] = 'targetEndpoint=01&ClusterId=0102&AttributeId=0008&AttributeType=20';
        }

        if ($test==124) {
            echo "Test envoie Cmd to DiscoverAttributesCommand\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/discoverAttributes';
            $msg['payload'] = 'ep=01&clustId='.$argv[3].'&startAttrId=0000&maxAttrId=FF';
        }

        if ($test==125) {
            echo "Test envoie Cmd to Discover IEEE\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/getIeeeAddress';
            $msg['payload'] = '';
        }

        if ($test==126) {
            echo "Test recuperation parametre Scene Count\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0005&attrId=0000';
        }

        if ($test==127) {
            echo "Test recuperation parametre Scene Current\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0005&attrId=0001';
        }

        if ($test==128) {
            echo "Test recuperation parametre Group Current for scene\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0005&attrId=0002';
        }

        if ($test==129) {
            echo "Test recuperation parametre scene active\n";
            $msg['topic']   = 'CmdAbeille1/'.$argv[2].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0005&attrId=0003';
        }

        if ($test==130) {
            echo "Remove Group Tele Innr EP 1, 3-8\n";
            $msg['topic']   = 'CmdAbeille1/0000/removeGroup';
            $msg['payload'] = 'address='.$argv[2].'&DestinationEndPoint='.$argv[3].'&groupAddress='.$argv[4];
        }

        if ($test==131) {
            echo "Read white spectre color of an Ikea bulb\n";
            $msg['topic']   = 'CmdAbeille2/'.$argv[2].'/readAttribute';
            $msg['payload'] = 'ep=01&clustId=0300&attrId=0007';
        }

        if ($test==132) {
            echo "Set Time to Zigate\n";
            $msg['topic']   = 'CmdAbeille' . $argv[2] . '/0000/setZgTimeServer';
            $msg['payload'] = '';
        }

        if ($test==133) {
            echo "Get Time from Zigate\n";
            $msg['topic']   = 'CmdAbeille' . $argv[2] . '/0000/getZgTimeServer';
            $msg['payload'] = '';
        }

        // Send the command to the queue for processing
        $queueKeyAbeilleToCmd = msg_get_queue(queueKeyAbeilleToCmd);
        if ( $queueKeyAbeilleToCmd ) {
            if (msg_send($queueKeyAbeilleToCmd, priorityUserCmd, json_encode($msg), false, false)) {
                echo "Msg sent: " . json_encode($msg) . "\n";
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
                Abeille::publishMosquitto($abQueues["xToAbeille"]["id"], PRIO_NORM, "Abeille1/0000/SystemMessage", "Le message");
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
                Abeille::publishMosquitto($abQueues["xToAbeille"]["id"], PRIO_NORM, "Abeille1/FFFF/0000-01-0005", "BSO");
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
                $json = '{"parametersCheck":"ok","parametersCheck_message":"","ab::defaultParent":"125","ab::zgType1":"USB","ab::zgPort1":"\/dev\/ttyUSB3","ab::zgIpAddr1":"192.168.4.1:23","ab::zgEnabled1":"Y","ab::zgType2":"WIFI","ab::zgPort2":"\/dev\/zigate2","ab::zgIpAddr2":"192.168.4.106:23","ab::zgEnabled2":"Y","ab::zgType3":"USB","ab::zgPort3":"\/dev\/ttyUSB2","ab::zgIpAddr3":"192.168.4.119:23","ab::zgEnabled3":"Y","ab::zgType4":"WIFI","ab::zgPort4":"\/dev\/zigate4","ab::zgIpAddr4":"192.168.4.107:23","ab::zgEnabled4":"Y"}';
                $parameters = json_decode($json,1);
                // var_dump($parameters);
                // echo "Parameters: ".json_encode($parameters)."\n";
                // echo "Nb zigate: ".$parameters['zigateNb']."\n";

                // $running = AbeilleTools::getRunningDaemons();
                // Kiwi Pro Simu
                $running[] = "845 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSocat.php /tmp/zigateWifi2 debug 192.168.4.106:23";
                $running[] = "847 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSocat.php /tmp/zigateWifi4 debug 192.168.4.107:23";
                $running[] = "873 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille1 /dev/ttyUSB3 debug";
                $running[] = "878 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille2 /tmp/zigateWifi2 debug";
                $running[] = "883 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille3 /dev/ttyUSB2 debug";
                $running[] = "892 /usr/bin/php /var/www/html/plugins/Abeille/core/class/../php/AbeilleSerialRead.php Abeille4 /tmp/zigateWifi4 debug";
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
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep=08&clustId=0000&attrId=0000';
        }

        if ($test==504) {
            echo "Test envoie Cmd to get ManufacturerName\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep='.$argv[4].'&clustId=0000&attrId=0004';
        }

        if ($test==505) {
            echo "Test envoie Cmd to get ModelIdentifier\n";
            $msg['topic']   = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/readAttribute';
            $msg['payload'] = 'ep='.$argv[4].'&clustId=0000&attrId=0005';
        }

        // php AbeilleTest.php 510 2 F0C0 08
        // Led Red DIO6: toglle et visiblement un PWM to control level.
        if ($test==510) {
            echo "On Off Test\n";
            $msg['topic'] = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/OnOff';
            $msg['payload'] = 'Action=Toggle&EP='.$argv[4];
        }

        // Control level Led Rouge
        // Minimum puissance
        // php AbeilleTest.php 511 2 F0C0 08 02
        // Toute puissance
        // php AbeilleTest.php 511 2 F0C0 08 99
        if ($test==511) {
            echo "Level Test\n";
            $msg['topic'] = 'CmdAbeille'.$argv[2].'/'.$argv[3].'/setLevel';
            $msg['payload'] = 'EP='.$argv[4].'&Level='.$argv[5].'&duration=10';
        }

        // Send the command to the queue for processing
        $queueKeyAbeilleToCmd = msg_get_queue(queueKeyAbeilleToCmd);
        if ( $queueKeyAbeilleToCmd ) {
            if (msg_send($queueKeyAbeilleToCmd, priorityUserCmd, json_encode($msg), false, false)) {
                echo "Msg sent: " . json_encode($msg) . "\n";
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
