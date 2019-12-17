<?php
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__).'/../../core/class/Abeille.class.php';
    
    // $parameters_info = Abeille::getParameters();
    if (0) {
    $_POST = array (
                    "eqSelected-1604"=>"on",
                    "eqSelected-1590"=>"on",
                    "submitButton"=>"Get Group",
                    "group"=>"",
                    "groupIdScene1"=>"",
                    "groupIdScene2"=>"",
                    "sceneID"=>"",
                    "channelMask"=>"",
                    "extendedPanId"=>"",
                    "TxPowerValue"=>"",
                    "Largeur"=>"",
                    "Hauteur"=>"",
    );
    }
    
    function sendMessageFromFormToCmd( $topic, $payload ) {
        
        $queueKeyFormToCmd   = msg_get_queue(queueKeyFormToCmd);
        $msgAbeille = new MsgAbeille;
        
        $msgAbeille->message['topic']   = $topic;
        $msgAbeille->message['payload'] = $payload;
        
        if (msg_send($queueKeyFormToCmd, 1, $msgAbeille, true, false)) {
            echo "added to queue (".queueKeyFormToCmd."): ".json_encode($msgAbeille)."<br>\n";
        }
        else {
            echo "could not add message to queue id: ".$queueKeyId."<br>\n";
        }
    }
    
    function getInfosFromNe( $item, $value, $client ) {
      $deviceId = substr( $item, strpos($item,"-")+1 );
      echo "deviceId: ".substr( $item, strpos($item,"-")+1 )."<br>";
      $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
      $address = substr($device->getLogicalId(),8);
      echo "address: ".$address."<br>\n";
      $EP = $device->getConfiguration('mainEP');
      echo "EP: ".$EP."<br>\n";

      // Get Name
      sendMessageFromFormToCmd('CmdAbeille/Ruche/ActiveEndPoint',           'address='.$address                             );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/SimpleDescriptorRequest',  'address='.$address.'&endPoint='.$EP            );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/IEEE_Address_request',     'address='.$address                             );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/getName',                  'address='.$address.'&destinationEndPoint='.$EP );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/getLocation',              'address='.$address.'&destinationEndPoint='.$EP );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership',       'address='.$address.'&DestinationEndPoint='.$EP );
      // sendMessageFromFormToCmd('CmdAbeille/Ruche/getSceneMembership',   'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$grouID, 0);
      // sendMessageFromFormToCmd('CmdAbeille/Ruche/ReadAttributeRequest', 'address='.$address.'&DestinationEndPoint='.$EP'.'&ClusterId='.$clusterId'.'&attributId='.$attributId'.'&Proprio='.$proprio', 0);

    }

    /*
    // ***********************************************************************************************
    // MQTT
    // ***********************************************************************************************
    function connect($r, $message)
    {
        // log::add('AbeilleMQTTCmd', 'info', 'Mosquitto: Connexion à Mosquitto avec code ' . $r . ' ' . $message);
        // config::save('state', '1', 'Abeille');
    }

    function disconnect($r)
    {
        // log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Déconnexion de Mosquitto avec code ' . $r);
        // config::save('state', '0', 'Abeille');
    }

    function subscribe()
    {
        // log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Subscribe to topics');
    }

    function logmq($code, $str)
    {
        // if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
        // log::add('AbeilleMQTTCmd', 'debug', 'Mosquitto: Log level: ' . $code . ' Message: ' . $str);
        // }
    }

    function message($message)
    {
        // var_dump( $message );
    }
    // https://github.com/mgdm/Mosquitto-PHP
    // http://mosquitto-php.readthedocs.io/en/latest/client.html
    $client = new Mosquitto\Client();

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
    $client->onConnect('connect');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
    $client->onDisconnect('disconnect');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
    $client->onSubscribe('subscribe');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
    $client->onMessage('message');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onLog
    $client->onLog('logmq');

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
    $client->setWill('/jeedom', "Client AbeilleFormAction died !!!", $parameters_info['AbeilleQos'], 0);

    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
    $client->setReconnectDelay(1, 120, 1);
*/
    try {
/*
        $client->setCredentials( "jeedom", "jeedom" );
        $client->connect( "localhost", 1883, 60 );
        $client->subscribe( "#", 0 ); // !auto: Subscribe to root topic
*/
        echo "_POST: ".json_encode( $_POST )."<br>\n";
        echo "Group: ".$_POST['groupID'].$_POST['groupIdScene1'].$_POST['groupIdScene2']."<br>\n";
        echo "Action: ".$_POST['submitButton']."<br>\n";

        switch ($_POST['submitButton']) {

            // Group
            case 'Add Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/addGroup',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        sleep(1);
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership', 'address='.$address.'&DestinationEndPoint='.$EP );
                        sleep(1);
                    }
                }
                break;
                
            case 'Set Group Remote':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/commissioningGroupAPS',           'address='.$address.'&groupId='.$_POST['group'] );
                    }
                }
                break;
                
            case 'Remove Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/removeGroup',        'address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        sleep(1);
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership', 'address='.$address.'&DestinationEndPoint='.$EP );
                        sleep(1);
                    }
                }
                break;
            case 'Get Group':
                
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos( $item, "eqSelected") === 0 ) {
                        echo "Id: ->".substr( $item, strpos($item,"-")+1 )."<-<br>\n";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        echo "Address: ".$address."<br>";
                        $EP = $device->getConfiguration('mainEP');
                        echo "Id: ".$EP."<br>";
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership', 'address='.$address.'&DestinationEndPoint='.$EP );
                    }
                }
                
                break;

            // Scene
            case 'View Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/viewScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );

                    }
                }
                break;

            case 'Store Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/storeScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );

                    }
                }
                break;

            case 'Recall Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/recallScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                break;

            case 'scene Group Recall':
                if (0) {
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/sceneGroupRecall',       'groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                }
                else {
                    sendMessageFromFormToCmd('CmdAbeille/Ruche/sceneGroupRecall',       'groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                }
                break;

            case 'Add Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/addScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'].'&sceneName=aa' );
                    }
                }
                break;

            case 'Remove Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/removeScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                break;

            case 'Get Scene Membership':
                echo "Get Scene Membership<br>";
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getSceneMembership',     'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'] );
                    }
                }
                break;

            case 'Remove All Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/removeSceneAll',         'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'] );
                    }
                }
                break;

            // Template
            case 'Apply Template':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        $deviceId = substr( $item, strpos($item,"-")+1 );
                        // echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        // $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        // $address = substr($device->getLogicalId(),8);
                        // $EP = $device->getConfiguration('mainEP');
                        // sendMessageFromFormToCmd('CmdAbeille/Ruche/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        abeille::updateConfigAbeille( $deviceId );
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;

            case 'Get Infos from NE':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        // $deviceId = substr( $item, strpos($item,"-")+1 );
                        // echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        // $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        // $address = substr($device->getLogicalId(),8);
                        // $EP = $device->getConfiguration('mainEP');
                        // sendMessageFromFormToCmd('CmdAbeille/Ruche/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        getInfosFromNe( $item, $Value, $client );
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;
                
            case 'TxPower Z1':
                echo "TxPower request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort']).'/Ruche/TxPower', $_POST['TxPowerValue'] );
                break;
                
            case 'TxPower Z2':
                echo "TxPower request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort2']).'/Ruche/TxPower', $_POST['TxPowerValue'] );
                break;
                
            case 'TxPower Z3':
                echo "TxPower request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort3']).'/Ruche/TxPower', $_POST['TxPowerValue'] );
                break;
                
            case 'TxPower Z4':
                echo "TxPower request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort4']).'/Ruche/TxPower', $_POST['TxPowerValue'] );
                break;
                
            case 'TxPower Z5':
                echo "TxPower request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort5']).'/Ruche/TxPower', $_POST['TxPowerValue'] );
                break;
                
            case 'Set Channel Mask Z1':
                echo "Set Channel Mask request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort']).'/Ruche/setChannelMask', $_POST['channelMask'] );
                break;
                
            case 'Set Channel Mask Z2':
                echo "Set Channel Mask request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort2']).'/Ruche/setChannelMask', $_POST['channelMask'] );
                break;
                
            case 'Set Channel Mask Z3':
                echo "Set Channel Mask request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort3']).'/Ruche/setChannelMask', $_POST['channelMask'] );
                break;
                
            case 'Set Channel Mask Z4':
                echo "Set Channel Mask request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort4']).'/Ruche/setChannelMask', $_POST['channelMask'] );
                break;
                
            case 'Set Channel Mask Z5':
                echo "Set Channel Mask request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort5']).'/Ruche/setChannelMask', $_POST['channelMask'] );
                break;
                
            case 'Set Extended PANID Z1':
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort']).'/Ruche/setExtendedPANID', $_POST['extendedPanId'] );
                break;
                
            case 'Set Extended PANID Z2':
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort2']).'/Ruche/setExtendedPANID', $_POST['extendedPanId'] );
                break;
                
            case 'Set Extended PANID Z3':
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort3']).'/Ruche/setExtendedPANID', $_POST['extendedPanId'] );
                break;
                
            case 'Set Extended PANID Z4':
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort4']).'/Ruche/setExtendedPANID', $_POST['extendedPanId'] );
                break;
                
            case 'Set Extended PANID Z5':
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('Cmd'.basename(Abeille::getParameters()['AbeilleSerialPort5']).'/Ruche/setExtendedPANID', $_POST['extendedPanId'] );
                break;
        }

        /*
        $client->loop();
        sleep(1);
        $client->loop();
        
        $client->disconnect();
        unset($client);
*/
        
    } catch (Exception $e) {
        echo '<br>error: '.$e->getMessage();
    }
    echo "<br>Fin";
    sleep(1);
    header ("location:/index.php?v=d&m=Abeille&p=Abeille");

    ?>
