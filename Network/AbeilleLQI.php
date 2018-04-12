<?php
    
    /***
     * LQI
     *
     * Send LQI request to the network
     * Process 804E answer messages
     * to draw LQI (Link Quality Indicator)
     * to draw NE Hierarchy
     *
     */
    
    
    require_once dirname(__FILE__)."/../../../core/php/core.inc.php";
    require_once("../resources/AbeilleDeamon/lib/Tools.php");
    require_once("../resources/AbeilleDeamon/includes/config.php");
    require_once("../resources/AbeilleDeamon/includes/fifo.php");
    require_once("../resources/AbeilleDeamon/includes/function.php");
    // require_once("../resources/AbeilleDeamon/lib/phpMQTT.php");
    
    function deamonlog($loglevel='NONE',$message=""){
        Tools::deamonlog($loglevel,'AbeilleLQI',$message);
        // echo $message . "\n";
    }
    
    // Definition MQTT
    
    function connect($r, $message)
    {
        deamonlog('debug', 'Connexion à Mosquitto avec code ' . $r . ' ' . $message);
        // config::save('state', '1', 'Abeille');
    }
    
    function disconnect($r)
    {
        deamonlog('debug', 'Déconnexion de Mosquitto avec code ' . $r);
        config::save('state', '0', 'Abeille');
    }
    
    function subscribe()
    {
        deamonlog('debug', 'Subscribe to topics');
    }
    
    function logmq($code, $str)
    {
        if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
            // Example of messages in log if deamonlog uncommented
            // AbeilleLQI 2018-04-12 12:39:15[DEBUG]16 : Client LQI_Connection sending PUBLISH (d0, q0, r0, m3, 'CmdAbeille/Ruche/Management_LQI_request', ... (26 bytes))
            // AbeilleLQI 2018-04-12 12:39:17[DEBUG]16 : Client LQI_Connection received PUBLISH (d0, q0, r0, m0, 'LQI/df33/00', ... (139 bytes))
            // deamonlog('debug', $code . ' : ' . $str);
        }
    }
    
    // ---------------------------------------------------------------------------------------------------------------------------
    function message($message)
    {

        // deamonlog('debug', '--- process a new message -----------------------');
        deamonlog('debug', $message->topic . ' => ' . $message->payload );

        
        // Crée les variables dans la chaine et associe la valeur.
        $parameters = proper_parse_str( $message->payload );
        
        $parameters['NE'] = $GLOBALS['NE'];
        
        $topicArray = explode("/", $message->topic);
        $parameters['Voisine'] = $topicArray[1];
        
        // Decode Bitmap Attribut
        // Bit map of attributes Described below: uint8_t
        // bit 0-1 Device Type (0-Coordinator 1-Router 2-End Device)    => Process
        // bit 2-3 Permit Join status (1- On 0-Off)                     => Skip no need for the time being
        // bit 4-5 Relationship (0-Parent 1-Child 2-Sibling)            => Process
        // bit 6-7 Rx On When Idle status (1-On 0-Off)                  => Process
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x00 ) { $parameters['Type'] = "Coordinator"; }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x01 ) { $parameters['Type'] = "Router";      }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x02 ) { $parameters['Type'] = "End Device";  }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x03 ) { $parameters['Type'] = "Unknown";     }

        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x00 ) { $parameters['Relationship'] = "Parent";  }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x10 ) { $parameters['Relationship'] = "Child";   }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x20 ) { $parameters['Relationship'] = "Sibling"; }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x30 ) { $parameters['Relationship'] = "Unknown"; }
        
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0x00 ) { $parameters['Rx'] = "Rx-Off";      }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0x40 ) { $parameters['Rx'] = "Rx-On";       }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0x80 ) { $parameters['Rx'] = "Rx-Unknown";  }
        if ( (hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0xC0 ) { $parameters['Rx'] = "Rx-Unknown";  }
        
        $parameters['LinkQualityDec'] = hexdec($parameters['LinkQuality']);
        
        // print_r( $parameters );
        
        $GLOBALS['LQI'][] = $parameters;
        
    }
    
    
    /*
     + * Send a mosquitto message to jeedom
     + *
     + * Ask NE at address to provide LQI from its table at index index
     + */
    function mqqtPublishLQI($mqtt, $destAddr, $index, $qos = 0)
    {
        //if ($mqtt->connect(true, null, $GLOBALS['username'], $GLOBALS['password'])) {
        $mqtt->publish("CmdAbeille/Ruche/Management_LQI_request", "address=".$destAddr."&StartIndex=".$index, $qos);
        //    $mqtt->close();
        //} else {
        //    deamonlog('WARNING', 'Time out!');
        //}
    }
    
    
    function hex2str($hex)
    {
        $str = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        
        return $str;
    }
    
    function displayClusterId($cluster)
    {
        return 'Cluster ID: '.$cluster.'-'.$GLOBALS['clusterTab']["0x".$cluster] ;
    }
    
    
    /*--------------------------------------------------------------------------------------------------*/
    /* Main
     /*--------------------------------------------------------------------------------------------------*/
    
    
    //                      1          2           3       4          5       6
    //$paramdeamon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;
    
    if ( isset($argv[1])){
        $serial = $argv[1];
        $server = $argv[2];     // change if necessary
        $port = $argv[3];                     // change if necessary
        $username = $argv[4];                   // set your username
        $password = $argv[5];                   // set your password
        $client_id = 'AbeilleLQI'; // make sure this is unique for connecting to sever - you could use uniqid()
        $qos = $argv[6];
        $requestedlevel = $argv[7];
        $requestedlevel = '' ? 'none' : $argv[7];
    } else{
        $server = "127.0.0.1";     // change if necessary
        $port = "1883";                     // change if necessary
        $username = "jeedom";                   // set your username
        $password = "jeedom";                   // set your password
        $client_id = 'AbeilleLQI'; // make sure this is unique for connecting to sever - you could use uniqid()
        $qos = 0;
        $requestedlevel = "debug";
    }
    
    $clusterTab = Tools::getJSonConfigFiles("zigateClusters.json");
    $LQI = array();
    
    deamonlog('debug', 'Start Main');
    
    // https://github.com/mgdm/Mosquitto-PHP
    // http://mosquitto-php.readthedocs.io/en/latest/client.html
    // $client = new Mosquitto\Client($parameters_info['AbeilleConId']);
    $client = new Mosquitto\Client("LQI_Connection");
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
    $client->onConnect('connect');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
    $client->onDisconnect('disconnect');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
    $client->onSubscribe('subscribe');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
    $client->onMessage('message');
    
    $client->onLog('logmq');
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
    $client->setWill('/LQI', "Client died :-(", $qos, 0);
    
    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
    $client->setReconnectDelay(1, 120, 1);
    
    
    $client->setCredentials(
                            // $parameters_info['AbeilleUser'],
                            // $parameters_info['AbeillePass']
                            $username,
                            $password
                            );
    
    deamonlog('debug', 'Connect to MQTT' );
    $client->connect(
                     // $parameters_info['AbeilleAddress'],
                     // $parameters_info['AbeillePort'],
                     $server,
                     $port,
                     60
                     );
    
    sleep(2);
    
    deamonlog('debug', 'Subscribe to topic ' . "LQI/#" );
    $client->subscribe(
                       // $parameters_info['AbeilleTopic'],
                       // $parameters_info['AbeilleQos']
                       "LQI/#",
                       $qos
                       ); // !auto: Subscribe to root topic
    
    // $mqtt = new phpMQTT($server, $port, $client_id);
    // mqqtPublishLQI($mqtt, "d45e", "00", $qos = 0);
    
    // deamonlog('debug', 'Request data');
    // mqqtPublishLQI($client, "d45e", "00", $qos = 0);
    
    // topic : LQI/2389/02 (LQI/Neigbour Addr, Table Index)
    // NeighbourTableListCount=01&ExtendedPanId=28d07615bb019209&IEEE_Address=00158d0001b7b2a2&Depth=02&LinkQuality=ff&BitmapOfAttributes=12
    // deamonlog('debug', 'Starting parsing mqtt broker with log level '.$requestedlevel.' on '.$username.':'.$password.'@'.$server.':'.$port.' qos='.$qos );
    // $client->loop();
    // deamonlog('debug', 'Sleep 2');
    // sleep(2);
    
    // mqqtPublishLQI($client, "d45e", "01", $qos = 0);
    // sleep(2);
    // $client->loop();
    
    // mqqtPublishLQI($client, "d45e", "02", $qos = 0);
    // sleep(2);
    // $client->loop();
    
    
    
    $continue = 1;
    $indexTable = 0;
    $tableSize = 0x09;
    $NE = "0000";
    
    // 1 to use loopForever et 0 to use while loop
    if ( 1 ) {
        
        if ( 0 ) {
            // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
            $client->loopForever();
        }
        else {
            while ($continue) {
                // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                $client->loop();
                // mqqtPublishLQI($client, "d45e", "0".$indexTable, $qos = 0);
                if ( $indexTable < $tableSize ) {
                    deamonlog('debug', 'Publish: 0000 - '.sprintf("%'.02x", $indexTable));
                    mqqtPublishLQI($client, $NE, sprintf("%'.02x", $indexTable), $qos = 0);
                }
                $indexTable++;
                // usleep(100);
                sleep(2);
                if ( $indexTable > $tableSize+5 ) { $continue = 0; }
            }
        }
    }
    
    $client->disconnect();
    unset($client);
    
    
    echo "LQI: \n";
    // print_r( $LQI );
    
    // Nice presentation
    foreach ( $LQI as $key => $voisine ) {
        echo $voisine['NE']."->".$voisine['Voisine']." ".$voisine['Relationship']." ".$voisine['LinkQualityDec']."\n";
    }
    
    
    // deamonlog('debug', 'sortie du loop');
    
    ?>
