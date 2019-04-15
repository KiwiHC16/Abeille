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
    
    require_once dirname(__FILE__) . "/../../../core/php/core.inc.php";
    require_once("../resources/AbeilleDeamon/lib/Tools.php");
    require_once("../resources/AbeilleDeamon/includes/config.php");
    require_once("../resources/AbeilleDeamon/includes/fifo.php");
    require_once("../resources/AbeilleDeamon/includes/function.php");
    
    
    
    function benLog($message = "")
    {
        global $debugBen;
        if ($debugBen && strlen($message) > 0) echo $message . "\n";
    }
    
    // Definition MQTT
    
    function connect($r, $message)
    {
    }
    
    function disconnect($r)
    {
    }
    
    function subscribe()
    {
    }
    
    function logmq($code, $str)
    {
        
    }
    
    // ---------------------------------------------------------------------------------------------------------------------------
    function message($message)
    {
        global $qos;
        global $abeilleParameters;
        
        echo "Message: \n";
        var_dump( $message );
        echo "\n";
        echo "\n";
        
        $NE_All_local = &$GLOBALS['NE_All_BuildFromLQI'];
        $knownNE_local = &$GLOBALS['knownNE_FromAbeille'];
        
        // On gere la root de mqtt
        if ( $abeilleParameters["AbeilleTopic"] != "#" ) {
            if ( strpos( "_".$message->topic, substr($message->topic,0,-1)) != 1 ) {
                log::add('Abeille', 'debug', "Message receive but is not for me, wrong delivery !!!");
                return;
            }
            // On enleve AbeilleTopic
            echo "lets remove topic root\n";
            $message->topic = substr( $message->topic, strlen($abeilleParameters["AbeilleTopic"])-1 );
        }
        
        echo "Message Topic: ".$message->topic."\n";
        
        if (strpos( "_".$message->topic, "LQI") != 1) {
            echo "LQI not\n";
            return;
        }
        
        // Crée les variables dans la chaine et associe la valeur.
        $parameters = proper_parse_str($message->payload);
        
        // Si je recois des message vide c'est que je suis à la fin de la table et je demande l arret de l envoie des requetes LQI
        // et je dis que le NE a ete interrogé
        if ($parameters['BitmapOfAttributes'] == "") {
            $GLOBALS['NE_continue'] = 0;
            // check si le NE existe deja, si oui je le marque fait, sinon je l ajoute à la liste pour le prochain passage
            if (isset($NE_All_local[$GLOBALS['NE']])) {
                $NE_All_local[$GLOBALS['NE']] = array("LQI_Done" => 0);
            } else {
                $NE_All_local[$GLOBALS['NE']] = array("LQI_Done" => 1);
            }
            
            return;
        }
        
        $parameters['NE'] = $GLOBALS['NE'];
        $parameters['NE_Name'] = ($parameters['NE'] == '0000') ? 'Ruche' : $knownNE_local[$parameters['NE']];
        if (strlen($parameters['NE_Name']) == 0) {
            $parameters['NE_Name'] = "Inconnu-" . $parameters['IEEE_Address'];
        }
        
        $topicArray = explode("/", $message->topic);
        $parameters['Voisine'] = $topicArray[1];
        $parameters['Voisine_Name'] = $knownNE_local[$parameters['Voisine']]; // array_search($topicArray[1], $knownNE_local); //$knownNE_local[$parameters['Voisine']];
        
        // echo "Voisine: " . $parameters['Voisine'] . " Voisine Name: " . $parameters['Voisine_Name'] . "\n";
        
        // Decode Bitmap Attribut
        // Bit map of attributes Described below: uint8_t
        // bit 0-1 Device Type (0-Coordinator 1-Router 2-End Device)    => Process
        // bit 2-3 Permit Join status (1- On 0-Off)                     => Skip no need for the time being
        // bit 4-5 Relationship (0-Parent 1-Child 2-Sibling)            => Process
        // bit 6-7 Rx On When Idle status (1-On 0-Off)                  => Process
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x00) {
            $parameters['Type'] = "Coordinator";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x01) {
            $parameters['Type'] = "Router";
            if (isset($NE_All_local[$parameters['Voisine']])) { // deja dans la list donc on ne fait rien
            } else {
                $NE_All_local[$parameters['Voisine']] = array("LQI_Done" => 0);
            }
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x02) {
            $parameters['Type'] = "End Device";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00000011) == 0x03) {
            $parameters['Type'] = "Unknown";
        }
        
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x00) {
            $parameters['Relationship'] = "Parent";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x10) {
            $parameters['Relationship'] = "Child";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x20) {
            $parameters['Relationship'] = "Sibling";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b00110000) == 0x30) {
            $parameters['Relationship'] = "Unknown";
        }
        
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0x00) {
            $parameters['Rx'] = "Rx-Off";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0x40) {
            $parameters['Rx'] = "Rx-On";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0x80) {
            $parameters['Rx'] = "Rx-Unknown";
        }
        if ((hexdec($parameters['BitmapOfAttributes']) & 0b11000000) == 0xC0) {
            $parameters['Rx'] = "Rx-Unknown";
        }
        
        $parameters['LinkQualityDec'] = hexdec($parameters['LinkQuality']);
        
        // print_r( $parameters );
        
        $GLOBALS['LQI'][] = $parameters;
        
        // Envoie de l'adresse IEEE a Abeille pour completer les objets.
        // e.g. Abeille/d45e/IEEE-Addr
        $mqtt = $GLOBALS['client'];
        $mqtt->publish(substr($abeilleParameters['AbeilleTopic'],0,-1)."Abeille/" . $parameters['Voisine'] . "/IEEE-Addr", $parameters['IEEE_Address'], $qos);
        
    }
    
    
    /*
     + * Send a mosquitto message to jeedom
     + *
     + * Ask NE at address to provide LQI from its table at index index
     + */
    function mqqtPublishLQI($mqtt, $destAddr, $index, $qos = 0)
    {
        global $abeilleParameters;
        $mqtt->publish(substr($abeilleParameters['AbeilleTopic'],0,-1)."CmdAbeille/Ruche/Management_LQI_request", "address=" . $destAddr . "&StartIndex=" . $index, $qos);
        
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
        return 'Cluster ID: ' . $cluster . '-' . $GLOBALS['clusterTab']["0x" . $cluster];
    }
    
    function collectInformation($client, $NE)
    {
        $indexTable = 0;
        
        while ($GLOBALS['NE_continue']) {
            // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
            mqqtPublishLQI($client, $NE, sprintf("%'.02x", $indexTable), $qos = 0);
            
            $indexTable++;
            // if ($indexTable > count($GLOBALS['knownNE'])+10) {
            // Pour l instant je met une valeur en dure. 30 voisines max.
            if ($indexTable > 30) {
                $GLOBALS['NE_continue'] = 0;
            }
            // usleep(100);
            $client->loop(5000);
            sleep(1);
            
        }
        // On vide les derniers messages qui trainent
        sleep(1);
        $client->loop(5000);
        sleep(1);
        $client->loop(5000);
        sleep(1);
        $client->loop(5000);
        
    }
    
    /*--------------------------------------------------------------------------------------------------*/
    /* Main
     /*--------------------------------------------------------------------------------------------------*/
    
    $debugBen = 1;
    $abeilleParameters = Abeille::getParameters();
    
    $serial         = $abeilleParameters["AbeilleSerialPort"];
    $server         = $abeilleParameters["AbeilleAddress"];             // change if necessary
    $port           = $abeilleParameters["AbeillePort"];                // change if necessary
    $username       = $abeilleParameters["AbeilleUser"];                // set your username
    $password       = $abeilleParameters["AbeillePass"];                // set your password
    $client_id      = "AbeilleLQI";                                     // make sure this is unique for connecting to sever - you could use uniqid()
    $qos            = $abeilleParameters["AbeilleQos"];
    $requestedlevel = "debug";
    
    benLog('Start Main');
    
    var_dump( $abeilleParameters );
    
    
    $DataFile = "AbeilleLQI_MapData.json";
    $FileLock = $DataFile . ".lock";
    $nbwritten = 0;
    
    if (file_exists($FileLock)) {
        $content = file_get_contents($FileLock);
        benLog($FileLock . ' content: ' . $content);
        if (strpos("_".$content, "done") != 1) {
            echo 'Oops, une collecte est déja en cours... Veuillez attendre la fin de l\'opération';
            benLog('debug', 'Une collecte est probablement en cours, fichier lock present, exit.');
            exit;
        }
    }
    
    $nbwritten = file_put_contents($FileLock, "init");
    if ($nbwritten<1) {
        unlink($FileLock);
        echo 'Oops, je ne peux pas écrire sur ' . $FileLock;
        exit;
    }
    
    // On recupere les infos d'Abeille
    $knownNE_FromAbeille = array();
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        $name = $eqLogic->getName();
        $shortAddress = str_replace("Abeille/", "", $eqLogic->getLogicalId());
        $shortAddress = ($name == 'Ruche') ? "0000" : $shortAddress;
        // $knownNE_FromAbeille[$name] = $shortAddress;
        $knownNE_FromAbeille[$shortAddress] = $name;
    }
    
    benLog("NE connus pas Abeille");
    if ($debugBen) {
        var_dump($knownNE_FromAbeille);
        echo "----------------------------------\n";
    }
    
    
    // $clusterTab = Tools::getJSonConfigFiles("zigateClusters.json");
    
    $LQI = array();
    
    // echo "DEBUT: ".date(DATE_RFC2822)."<br>";
    //lqiLog('debug', '---------: definition et connection a mosquitto');
    
    // https://github.com/mgdm/Mosquitto-PHP
    // http://mosquitto-php.readthedocs.io/en/latest/client.html
    
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
    
    
    $client->setCredentials( $username, $password );
    
    //lqiLog('debug', 'Connect to MQTT');
    $client->connect( $server, $port, 60 );
    
    sleep(2);
    
    echo "Sub to: ->".substr($abeilleParameters['AbeilleTopic'],0,-1)."LQI/#"."<-\n";
    $client->subscribe( substr($abeilleParameters['AbeilleTopic'],0,-1)."LQI/#", $qos ); // !auto: Subscribe to root topic
    
    // Let's start with the Coordinator
    if ($debugBen) { echo "---------: Let s start with the Coordinator\n"; }
    //lqiLog('debug', '---------: Let s start with the Coordinator');
    $NE_All_BuildFromLQI = array();
    $NE_All_BuildFromLQI["0000"] = array("LQI_Done" => 0);
    
    //exists in knownNE
    //collectInformation($client, $NE);
    // $NE_All[$NE]['LQI_Done'] = 1;
    
    // Let's start at least with 0000
    $NE_All_continue = 1;   // Controle le while sur la liste des NE
    $NE_continue = 1;       // controle la boucle sur l interrogation de la table des voisines d un NE particulier
    
    // Let's start the loop to collect all LQI
    while ($NE_All_continue) {
        
        // Par defaut je ne continu pas. Si je trouve au moins un NE je continue, je ferai donc une boucle à vide à la fin.
        $NE_All_continue = 0;
        
        // Let's continue with Routers found
        // foreach ($knownNE as $name => $neAddress) {
        foreach ($NE_All_BuildFromLQI as $currentNeAddress => $currentNeStatus) {
            benLog("=============================================================");
            benLog("Start Loop");
            
            //-----------------------------------------------------------------------------
            // Estimation du travail restant et info dans le fichier lock
            $total = count($NE_All_BuildFromLQI);
            $done = 0;
            foreach ($NE_All_BuildFromLQI as $neAddressProgress => $neStatusProgress) {
                if ($neStatusProgress['LQI_Done'] == 1) {
                    $done++;
                }
            }
            benLog("AbeilleLQI main: " . $done . " of " . $total);
            
            //-----------------------------------------------------------------------------
            
            // Variable globale qui me permet de savoir quel NE on est en cours d'interrogation car dans le message de retour je n'ai pas cette info.
            $NE = $currentNeAddress;
            
            $name = $knownNE_FromAbeille[$currentNeAddress];
            if (strlen($name) == 0) {
                $name = "Inconnu-" . $currentNeAddress;
            }
            
            $nbwritten = file_put_contents($FileLock, $done . " of " . $total . ' (' . $name . ' - ' . $currentNeAddress . ' - ' . $currentNeStatus['LQI_Done'] . ')');
            if ($nbwritten<1) {
                unlink($FileLock);
                echo 'Oops, je ne peux pas écrire sur ' . $FileLock;
                exit;
            }
            
            benLog('AbeilleLQI main: Interrogation de ' . $name . ' - ' . $currentNeAddress );
            if ($debugBen) var_dump($NE_All_BuildFromLQI);
            
            if ($currentNeStatus['LQI_Done'] == 0) {
                // echo "Let s do\n";
                // $NE = $neAddress;
                $NE_All_continue = 1;
                $NE_continue = 1;
                benLog('AbeilleLQI main: Interrogation de ' . $name . ' - ' . $currentNeAddress  . " Je lance la collecte");
                sleep(5);
                collectInformation($client, $currentNeAddress);
                $NE_All_BuildFromLQI[$NE]['LQI_Done'] = 1;
            } else {
                // echo "Already done\n";
            }
        }
        
    }
    
    $client->disconnect();
    unset($client);
    
    //announce end of processing
    //file_put_contents($FileLock, "done");
    file_put_contents($FileLock, "done - ".date('l jS \of F Y h:i:s A'));
    
    // encode array to json
    $json = json_encode(array('data' => $LQI));
    
    //write json to file
    if (file_put_contents($DataFile, $json)) {
        echo "JSON file created successfully...\n";
    }
    else {
        unlink($DataFile);
        echo "Oops! Error creating json file...\n";
    }
    
    // Formating pour la doc asciidoc
    if (0) {
        // echo "<table>\n";
        // echo "<tr><td>NE</td><td>Voisine</td><td>Relation</td><td>Profondeur</td><td>LQI</td></tr>\n";
        echo "|NE|Voisine|Relation|Profondeur|LQI\n";
        
        foreach ($LQI as $key => $voisine) {
            // echo "<tr>";
            // echo "<td>".$voisine['NE']."</td><td>".$voisine['Voisine']."</td><td>".$voisine['Relationship']."</td><td>".$voisine['Depth']."</td><td>".$voisine['LinkQualityDec']."</td>";
            
            echo "|" . $voisine['NE'] . "|" . $voisine['NE_Name'] . "|" . $voisine['Voisine'] . "|" . "|" . $voisine['Voisine_Name'] . "|" . $voisine['Relationship'] . "|" . $voisine['Depth'] . "|" . $voisine['LinkQualityDec'] . "\n";
            
            // echo "</tr>\n";
        }
        // echo "</table>\n";
    }
    
    // print_r( $NE_All );
    // print_r( $voisine );
    // print_r( $LQI );
    
    ?>
