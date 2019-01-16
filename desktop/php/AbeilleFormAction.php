<?php
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__)."/../../core/class/Abeille.class.php";
    
    $parameters_info = Abeille::getParameters();
    
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
    $client = new Mosquitto\Client($client_id);
    
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
    $client->setWill('/jeedom', "Client AbeilleFormAction finished the activities", $parameters_info['AbeilleQos'], 0);
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
    $client->setReconnectDelay(1, 120, 1);
    
    try {
        
        $client->setCredentials( "jeedom", "jeedom" );
        $client->connect( "localhost", 1883, 60 );
        $client->subscribe( "#", 0 ); // !auto: Subscribe to root topic
        
        echo "Group: ".$_POST['group']."<br>";
        echo "Action: ".$_POST['submitButton']."<br>";
        
        switch ($_POST['submitButton']) {
            
            // Group
            case 'Add Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        $client->publish('CmdAbeille/Ruche/addGroup',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'], 0);
                        sleep(1);
                        $client->publish('CmdAbeille/Ruche/getGroupMembership', 'address='.$address.'&DestinationEndPoint='.$EP, 0);
                        sleep(1);
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
                        $client->publish('CmdAbeille/Ruche/removeGroup',        'address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'], 0);
                        sleep(1);
                        $client->publish('CmdAbeille/Ruche/getGroupMembership', 'address='.$address.'&DestinationEndPoint='.$EP, 0);
                        sleep(1);
                    }
                }
                break;
            case 'Get Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        $client->publish('CmdAbeille/Ruche/getGroupMembership', 'address='.$address.'&DestinationEndPoint='.$EP, 0);
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
                        $client->publish('CmdAbeille/Ruche/viewScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'], 0);
                        
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
                        $client->publish('CmdAbeille/Ruche/storeScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'], 0);
                        
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
                        $client->publish('CmdAbeille/Ruche/recallScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'], 0);
                    }
                }
                break;
                
            case 'scene Group Recall':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        $client->publish('CmdAbeille/Ruche/sceneGroupRecall',              'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'], 0);
                    }
                }
                break;
                
            case 'Add Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        $client->publish('CmdAbeille/Ruche/addScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'].'&sceneName=aa', 0);
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
                        $client->publish('CmdAbeille/Ruche/removeScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'], 0);
                    }
                }
                break;
                
            case 'Get Scene Membership':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        $address = substr($device->getLogicalId(),8);
                        $EP = $device->getConfiguration('mainEP');
                        $client->publish('CmdAbeille/Ruche/getSceneMembership',     'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'], 0);
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
                        $client->publish('CmdAbeille/Ruche/removeSceneAll',         'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'], 0);
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
                        // $client->publish('CmdAbeille/Ruche/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'], 0);
                        abeille::updateConfigAbeille( $deviceId );
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;
        }
        
        $client->loop();
        
        $client->disconnect();
        unset($client);
        
    } catch (Exception $e) {
        echo '<br>error: '.$e->getMessage();
    }
    echo "<br>Fin";
    sleep(3);
    header ("location:/index.php?v=d&m=Abeille&p=Abeille");
    
    ?>
