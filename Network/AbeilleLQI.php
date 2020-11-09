<?php
    
    /*
     * LQI
     *
     * Send LQI request to the network
     * Process 804E answer messages
     * to draw LQI (Link Quality Indicator)
     * to draw NE Hierarchy
     *
     */
    
    include_once __DIR__."/../../../core/php/core.inc.php";
    include_once("../resources/AbeilleDeamon/lib/Tools.php");
    include_once("../resources/AbeilleDeamon/includes/config.php");
    include_once("../resources/AbeilleDeamon/includes/fifo.php");
    include_once("../resources/AbeilleDeamon/includes/function.php");
    
    function KiwiLog($message = "")
    {
        global $debugKiwi;
        if ($debugKiwi && strlen($message) > 0) echo $message . "<br>\n";
    }
    
    // ---------------------------------------------------------------------------------------------------------------------------
    function message() {
        global $LQI;
        global $abeilleParameters;
        global $queueKeyParserToLQI;
        global $NE_All_BuildFromLQI;
        global $knownNE_FromAbeille;
        global $knownObject_FromAbeille;
        global $NE;
        global $NE_continue;
        
        // KiwiLog("Check if message");
        
        $max_msg_size = 512;
        
        if (msg_receive( $queueKeyParserToLQI, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT)) {
            $message = new stdClass();
            $message->topic = $msg->message['topic'];
            $message->payload = $msg->message['payload'];
        }
        else {
            return;
        }
        
        KiwiLog("Got Message (Ln: ".__LINE__."): ".json_encode( $message ) );
         
        if (strpos( "_".$message->topic, "LQI") != 1) {
            echo "LQI not\n";
            return;
        }
        
        // Crée les variables dans la chaine et associe la valeur.
        $parameters = proper_parse_str($message->payload);
        unset($parameters['srcAddress']); // remove this info that looks strange and I don t understand it so create confusion.
        
        // Si je recois des message vide c'est que je suis à la fin de la table et je demande l arret de l envoie des requetes LQI et je dis que le NE a ete interrogé
        if ($parameters['BitmapOfAttributes'] == "") {
            $NE_continue = 0;
            $NE_All_BuildFromLQI[$NE] = array("LQI_Scan_Done" => 1);
            return;
        }
        
        $parameters['NE'] = $NE;
        $parameters['NE_Name'] = $knownNE_FromAbeille[$NE];
        $parameters['NE_Objet'] = $knownObject_FromAbeille[$NE];
        
        list( $dest, $addr ) = explode( '/', $NE );

        if (strlen($parameters['NE_Name']) == 0) {
            $parameters['NE_Name'] = "Inconnu-" . $parameters['IEEE_Address'];
            $parameters['NE_Objet'] = "Inconnu";
        }
        
        list( $lqi, $voisineAddr, $i ) = explode("/", $message->topic);
        if ( $voisineAddr == "0000" ) $voisineAddr = "Ruche";
        $parameters['Voisine'] = $dest . "/" . $voisineAddr;
        if ( isset($knownNE_FromAbeille[$parameters['Voisine']]) ) {
            $parameters['Voisine_Name'] = $knownNE_FromAbeille[$parameters['Voisine']];
            $parameters['Voisine_Objet'] = $knownObject_FromAbeille[$parameters['Voisine']];
        }
        else {
            $parameters['Voisine_Name'] = $parameters['Voisine'];
            $parameters['Voisine_Objet'] = "Inconnu";
        }

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
            if (isset($NE_All_BuildFromLQI[$parameters['Voisine']])) { // deja dans la list donc on ne fait rien
            } else {
                $NE_All_BuildFromLQI[$parameters['Voisine']] = array("LQI_Scan_Done" => 0);
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
        
        $LQI[] = $parameters;
    }
    
    function mqqtPublishLQI( $dest, $addr, $index ) {
        global $queueKeyLQIToCmd;
        
        $msgAbeille = new MsgAbeille;
        
        $msgAbeille->message['topic'] = "Cmd".$dest."/Ruche/Management_LQI_request";
        $msgAbeille->message['payload'] = "address=" . $addr . "&StartIndex=" . $index;
        
        KiwiLog("publishLQI: ".json_encode($msgAbeille));
        
        if (msg_send( $queueKeyLQIToCmd, priorityInterrogation, $msgAbeille, true, false)) {
            log::add('Abeille', 'debug', '(AbeilleLQI - mqqtPublishLQI) Msg sent: '.json_encode($msgAbeille));
        }
        else {
            log::add('Abeille', 'debug', '(AbeilleLQI - mqqtPublishLQI) Could not send Msg');
        }
    }
    
    function hex2str($hex) {
        $str = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        
        return $str;
    }
    
    function displayClusterId($cluster) {
        return 'Cluster ID: ' . $cluster . '-' . $GLOBALS['clusterTab']["0x" . $cluster];
    }
    
    function collectInformation( $serial, $addr ) {
        $indexTable = 0;
        
        while ($GLOBALS['NE_continue']) {

            mqqtPublishLQI( $serial, $addr, sprintf("%'.02x", $indexTable) );
            
            $indexTable++;
            // if ($indexTable > count($GLOBALS['knownNE'])+10) {
            // Pour l instant je met une valeur en dure. 30 voisines max.
            if ($indexTable > 30) {
                $GLOBALS['NE_continue'] = 0;
            }

            for ($i = 1; $i <= 3; $i++) {
                message();
                sleep(1);
            }
            
        }
        // On vide les derniers messages qui trainent
        for ($i = 1; $i <= 3; $i++) {
            message();
            sleep(1);
        }
    }
    
    /*--------------------------------------------------------------------------------------------------*/
    /* Main
     /*--------------------------------------------------------------------------------------------------*/
    // Bouton GetLQI(x)
    // refreshNetworkCache refreshCache(x) dans desktop/modal/network.php -> desktop/js/network.js -> updateZigBeeJsonCache(x) -> AbeilleLQI.php?zigate=(x)
    // Pour tester en shell, declarer $_GET['zigate']=(x) en decommentant la ligne suivante et faire un php AbeilleLQI.php
    
    $debugKiwi = 0;
    $debugKiwiCli = 0; // if called form shell
    if ( $debugKiwiCli ) $_GET['zigate']=1;
    
    $LQI                        = array();
    $knownNE_FromAbeille        = array();
    $knownObject_FromAbeille    = array();
    $NE_All_BuildFromLQI        = array();
    
    $abeilleParameters = Abeille::getParameters();
    
    KiwiLog('Start Main');
    
    /* Note: depending on the way 'AbeilleLQI' is launched, arguments are not
       collected in the same way.
       URL => use $_GET[]
       Cmd line/shell => use $argv[] */
    if (!isset($_GET['zigate'])) {
        /* Zigate number passed as arg ? (called from shell/cron case) */
        if (!isset($argv[1])) {
            KiwiLog("Paramètre zigate manquant.");
            return;
        }
        $zgNb = $argv[1];
    } else
        $zgNb = $_GET['zigate'];
    if (($zgNb < 1) or ($zgNb > maxNbOfZigate)) {
        KiwiLog("Mauvaise valeur de zigate !!!!");
        return;
    }
    
    $serial = "Abeille".$zgNb;
    
    $queueKeyLQIToCmd    = msg_get_queue( queueKeyLQIToCmd );
    $queueKeyParserToLQI = msg_get_queue( queueKeyParserToLQI );
    
    $dir = "tmp/";
    $DataFile = $dir."AbeilleLQI_MapData".$serial.".json";
    $FileLock = $DataFile . ".lock";
    $nbwritten = 0;
    
    if (file_exists($FileLock)) {
        $content = file_get_contents($FileLock);
        KiwiLog($FileLock . ' content: ' . $content);
        if (strpos("_".$content, "done") != 1) {
            echo 'Oops, une collecte est déja en cours... Veuillez attendre la fin de l\'opération';
            KiwiLog('debug', 'Une collecte est probablement en cours, fichier lock present, exit.');
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
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        $knownNE_FromAbeille[$eqLogic->getLogicalId()] = $eqLogic->getName();
        $knownObject_FromAbeille[$eqLogic->getLogicalId()] = $eqLogic->getObject()->getName();
    }
    if ( $debugKiwiCli ) { echo json_encode($knownObject_FromAbeille)."\n"; }
    
    // Let's start at least with Ruche
    $NE_All_BuildFromLQI[$serial."/Ruche"] = array("LQI_Scan_Done" => 0);
    
    KiwiLog( "NE connus pas Abeille: ".json_encode($knownNE_FromAbeille) );
    KiwiLog( "NE to scan: ".json_encode($NE_All_BuildFromLQI) );
    
    KiwiLog( "DEBUT: ".date(DATE_RFC2822)."<br>");

    $NE_All_continue = 1;   // Controle le while sur la liste des NE
    $NE_continue = 1;       // controle la boucle sur l interrogation de la table des voisines d un NE particulier
    
    // Let's start the loop to collect all LQI
    while ($NE_All_continue) {
        
        // Par defaut je ne continu pas. Si je trouve au moins un NE je continue, je ferai donc une boucle à vide à la fin.
        $NE_All_continue = 0;
        
        // Let's continue with Routers found
        // foreach ($knownNE as $name => $neAddress) {
        foreach ($NE_All_BuildFromLQI as $currentNeAddress => $currentNeStatus) {
            KiwiLog("=============================================================");
            KiwiLog("Start Loop with : ".$currentNeAddress);
            
            list( $serial, $addr ) = explode( '/', $currentNeAddress) ;
            if ( $addr == "Ruche" ) { $addr = "0000"; }
            
            //-----------------------------------------------------------------------------
            // Estimation du travail restant et info dans le fichier lock
            $total = count($NE_All_BuildFromLQI);
            $done = 0;
            foreach ($NE_All_BuildFromLQI as $neAddressProgress => $neStatusProgress) {
                if ($neStatusProgress['LQI_Scan_Done'] == 1) {
                    $done++;
                }
            }
            KiwiLog("AbeilleLQI main: " . $done . " of " . $total);
            
            //-----------------------------------------------------------------------------
            // Variable globale qui me permet de savoir quel NE on est en cours d'interrogation car dans le message de retour je n'ai pas cette info.
            $NE = $currentNeAddress;
            
            $name = $knownNE_FromAbeille[$currentNeAddress];
            if (strlen($name) == 0) {
                $name = "Inconnu-" . $currentNeAddress;
            }
            
            $nbwritten = file_put_contents($FileLock, $done . " of " . $total . ' (' . $name . ' - ' . $currentNeAddress . ')');
            if ($nbwritten<1) {
                unlink($FileLock);
                echo 'Oops, je ne peux pas écrire sur ' . $FileLock;
                exit;
            }
            
            KiwiLog( "All info collected so far: ".json_encode($NE_All_BuildFromLQI) );
            
            if ($currentNeStatus['LQI_Scan_Done'] == 0) {
                $NE_All_continue = 1;
                $NE_continue = 1;
                KiwiLog('AbeilleLQI main: Interrogation de ' . $name . ' - ' . $serial . ' - ' . $addr  . " -> Je lance la collecte");
                collectInformation( $serial, $addr );
                $NE_All_BuildFromLQI[$NE]['LQI_Scan_Done'] = 1;
                sleep(5);
            } else {
                // echo "Already done\n";
            }
        }
    }
        
    //announce end of processing
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
    
    // print_r( $NE_All );
    // print_r( $voisine );
    // print_r( $LQI );
?>
