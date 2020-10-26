<?php
    /*
     * AbeilleCmd
     *
     * subscribe to Abeille topic and receive message sent by AbeilleParser.
     *
     */

    /* Developers debug features */
    $dbgFile = __DIR__."/../../tmp/debug.php";
    if (file_exists($dbgFile)) {
        include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', '/var/www/html/log/AbeillePHP');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/function.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/fifo.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/lib/Tools.php';
    include_once __DIR__.'/AbeilleMsg.php';

    class debug extends AbeilleTools {
        function deamonlog($loglevel = 'NONE', $message = "")
        {
            if ( $this->debug["cli"] ) {
                echo "[".date("Y-m-d H:i:s").'][AbeilleCmd][DEBUG.KIWI] '.$message."\n";
            }
            else {
                $this->deamonlogFilter($loglevel, 'Abeille', 'AbeilleCmd', $message);
            }
        }
    }

    class AbeilleCmdQueue extends debug {

        public $statusText = array(
                            "00" => "Success",
                            "01" => "Incorrect parameters",
                            "02" => "Unhandled command",
                            "03" => "Command failed",
                            "04" => "Busy (Node is carrying out a lengthy operation and is currently unable to handle the incoming command)",
                            "05" => "Stack already started (no new configuration accepted)",

                            "15" => "ZPS_EVENT_ERROR Indicates that an error has occurred on the local node. The nature of the error is reported through the structure ZPS_tsAfErrorEvent - see Section 7.2.2.17. JN-UG-3113 v1.5 -> En gros pas de place pour traiter le message",

                            "80" => "Code inconnu",
                            "A6" => "Code inconnu",
                            "C2" => "Code inconnu",
                            );

        public $queueKeyAbeilleToCmd;
        public $queueKeyParserToCmd;
        public $queueKeyCmdToCmd;
        public $queueKeyCmdToAbeille;
        public $queueKeyLQIToCmd;
        public $queueKeyXmlToCmd;
        public $queueKeyFormToCmd;
        public $queueKeyParserToCmdSemaphore;

        public $zigateNb;
        public $zigateAvailable = array();

        function __construct($debug) {
            if ($debug) $this->deamonlog("debug", "AbeilleCmdQueue constructor start");

            if ($debug) $this->deamonlog("debug", "Recuperation des queues de messages");
            $this->queueKeyAbeilleToCmd           = msg_get_queue(queueKeyAbeilleToCmd);
            $this->queueKeyParserToCmd            = msg_get_queue(queueKeyParserToCmd);
            $this->queueKeyCmdToCmd               = msg_get_queue(queueKeyCmdToCmd);
            $this->queueKeyCmdToAbeille           = msg_get_queue(queueKeyCmdToAbeille);
            $this->queueKeyLQIToCmd               = msg_get_queue(queueKeyLQIToCmd);
            $this->queueKeyXmlToCmd               = msg_get_queue(queueKeyXmlToCmd);
            $this->queueKeyFormToCmd              = msg_get_queue(queueKeyFormToCmd);
            $this->queueKeyParserToCmdSemaphore   = msg_get_queue(queueKeyParserToCmdSemaphore);

            $this->tempoMessageQueue               = array();

            if ($debug) $this->deamonlog("debug", "AbeilleCmdQueue constructor end");
        }

        public function publishMosquitto( $queueKeyId, $priority, $topic, $payload ) {

            $queue = msg_get_queue($queueKeyId);

            $msgAbeille = new MsgAbeille;

            $msgAbeille->message['topic']   = $topic;
            $msgAbeille->message['payload'] = $payload;

            if (msg_send($queue, $priority, $msgAbeille, true, false)) {
                if ( $this->debug['tempo'] ) $this->deamonlog('debug', '(fct publishMosquitto) mesage: '.json_encode($msgAbeille).' added to queue : '.$queueKeyId );
            }
            else {
                if ( $this->debug['tempo'] ) $this->deamonlog('debug', '(fct publishMosquitto) could not add message '.json_encode($msgAbeille).' to queue : '.$queueKeyId );
            }
        }

        public function publishMosquittoAbeille( $queueKeyId, $topic, $payload ) {

             $queue = msg_get_queue($queueKeyId);

             $msgAbeille = new MsgAbeille;

             $msgAbeille->message['topic']   = $topic;
             $msgAbeille->message['payload'] = $payload;

             if (msg_send($queue, $msgAbeille, true, false)) {
                 if ( $this->debug['tempo'] ) $this->deamonlog('debug', '(fct publishMosquittoAbeille) mesage: '.json_encode($msgAbeille).' added to queue : '.$queueKeyId );
             }
             else {
                 if ( $this->debug['tempo'] ) $this->deamonlog('debug', '(fct publishMosquittoAbeille) could not add message '.json_encode($msgAbeille).' to queue : '.$queueKeyId );
             }
         }

        public function addTempoCmdAbeille($topic, $msg, $priority) {
            // TempoCmdAbeille1/Ruche/getVersion&time=123 -> msg

            list($topic, $param) = explode('&', $topic);
            $topic = str_replace( 'Tempo', '', $topic);

            list($timeTitle, $time) = explode('=', $param);

            $this->tempoMessageQueue[] = array( 'time'=>$time, 'priority'=>$priority, 'topic'=>$topic, 'msg'=>$msg );
            if ( $this->debug['tempo'] ) $this->deamonlog('debug', 'addTempoCmdAbeille - tempoMessageQueue: '.json_encode($this->tempoMessageQueue) );
            if ( count($this->tempoMessageQueue) > 50 ) $this->deamonlog('info', 'Il y a plus de 50 messages dans le queue tempo.' );

            return;
        }

        public function execTempoCmdAbeille() {

            if ( count($this->tempoMessageQueue)<1 ) {
                return;
            }

            $now=time();
            foreach ($this->tempoMessageQueue as $key => $mqttMessage) {
                // deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue - 0: '.$mqttMessage[0] );
                if ($mqttMessage['time']<$now) {
                    $this->publishMosquitto( queueKeyCmdToCmd, $mqttMessage['priority'], $mqttMessage['topic'], $mqttMessage['msg']  );
                    if ( $this->debug['tempo'] ) $this->deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue - one less: -> '.json_encode($this->tempoMessageQueue[$key]) );
                    unset($this->tempoMessageQueue[$key]);
                    if ( $this->debug['tempo'] ) $this->deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue : '.json_encode($this->tempoMessageQueue) );
                }
            }

            return;
        }
    }

    class AbeilleCmdL2 extends AbeilleCmdQueue {
        public $debug = array( "cli"                    => 0, // commande line mode or jeedom
                              "Checksum"                => 0, // Debug checksum calculation
                              "tempo"                   => 0, // Debug tempo queue
                              "procmsg"                 => 0, // Debug fct procmsg
                              "procmsg1"                => 1, // Debug fct procmsg avec un seul msg
                              "procmsg2"                => 1, // Debug fct procmsg avec un seul msg
                              "procmsg3"                => 0, // Debug fct procmsg avec un seul msg
                              "processCmd"              => 1, // Debug fct processCmd
                              "sendCmd"                 => 1, // Debug fct sendCmd
                              "sendCmd2"                => 0, // Debug fct sendCmd
                              "cmdQueue"                => 0, // Debug cmdQueue
                              "sendCmdAck"              => 1, // Debug fct sendCmdAck
                              "sendCmdAck2"             => 0, // Debug fct sendCmdAck
                              "transcode"               => 0, // Debug transcode fct
                              "AbeilleCmdClass"         => 1, // Mise en place des class
                              "sendCmdToZigate"         => 1, // Mise en place des class
                              "traiteLesAckRecus"       => 0, // Nouvelle Gestion des Ack
                              "processCmdQueueToZigate" => 1,
                              );

        public $cmdQueue;                         // When a cmd is to be sent to the zigate we store it first, then try to send it if the cmdAck is low. Flow Control.
        public $zigateAvailable = array();        // Si on pense la zigate dispo ou non.
        public $timeLastAck = array();            // When I got the last Ack from Zigate
        public $timeLastAckTimeOut = array();     // x s secondes dans retour de la zigate, je considere qu'elle est ok de nouveau pour ne pas rester bloqué.
        public $maxRetry = maxRetryDefault;       // Abeille will try to send the message max x times

        public $zigateNb;

        public $requestedlevel;

        function __construct($debugLevel) {

            if ($this->debug["AbeilleCmdClass"]) $this->deamonlog("debug", "AbeilleCmd constructor start");

            $this->requestedlevel = $debugLevel;

            $this->zigateNb = config::byKey('zigateNb', 'Abeille', '1', 1);

            parent::__construct( $this->debug["AbeilleCmdClass"] );

            for ( $i=1; $i<=$this->zigateNb; $i++ ) {
                $this->zigateAvailable[$i] = 1;
                $this->timeLastAck[$i] = 0;
                $this->timeLastAckTimeOut[$i] = 0;
            }

            if ($this->debug["AbeilleCmdClass"]) $this->deamonlog("debug", "AbeilleCmd constructor end");
        }

        // Ne semble pas fonctionner et me fait planté la ZiGate, idem ques etParam()
        function setParamXiaomi($dest,$Command) {
            // Write Attribute request
            // Msg Type = 0x0110

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <direction: uint8_t>
            // <manufacturer specific: uint8_t>
            // <manufacturer id: uint16_t>
            // <number of attributes: uint8_t>
            // <attributes list: data list of uint16_t  each>
            //      Direction:
            //          0 - from server to client
            //          1 - from client to server
            //      Manufacturer specific :
            //          1 – Yes
            //          0 – No

            $priority = $Command['priority'];

            $cmd                    = "0110";

            $addressMode            = "02"; // Short Address -> 2
            $address                = $Command['address'];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = "01";
            $clusterId              = $Command['clusterId'];
            $direction              = "00";
            $manufacturerSpecific   = "01";
            $proprio                = $Command['Proprio'];
            $numberOfAttributes     = "01";
            $attributeId            = $Command['attributeId'];
            $attributeType          = $Command['attributeType'];
            $value                  = $Command['value'];

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $clusterId . $direction . $manufacturerSpecific . $proprio . $numberOfAttributes . $attributeId . $attributeType . $value;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );

            if ( isset($Command['repeat']) ) {
                if ( $Command['repeat']>1 ) {
                    for ($x = 2; $x <= $Command['repeat']; $x++) {
                        sleep(5);
                        $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
                    }
                }
            }
        }

        // J'ai un probleme avec la command 0110, je ne parviens pas à l utiliser. Prendre setParam2 en atttendant.
        function setParam($dest,$address,$clusterId,$attributeId,$destinationEndPoint,$Param) {
            /*
             <address mode: uint8_t>
             <target short address: uint16_t>
             <source endpoint: uint8_t>
             <destination endpoint: uint8_t>
             <Cluster id: uint16_t>
             <direction: uint8_t>
             <manufacturer specific: uint8_t>
             <manufacturer id: uint16_t>
             <number of attributes: uint8_t>
             <attributes list: data list of uint16_t  each>
             Direction:
             0 - from server to client
             1 - from client to server
             */

            $priority = $Command['priority'];

            $cmd = "0110";
            $lenth = "000E";

            $addressMode = "02";
            // $address = $Command['address'];
            $sourceEndpoint = "01";
            // $destinationEndpoint = "01";
            //$ClusterId = "0006";
            $Direction = "01";
            $manufacturerSpecific = "00";
            $manufacturerId = "0000";
            $numberOfAttributes = "01";
            // $attributesList = "0000";
            $attributesList = $attributeId;
            $attributesList = "Salon1         ";

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndPoint . $clusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
            // $this->deamonlog('debug','data: '.$data);
            // $this->deamonlog('debug','len data: '.strlen($data));
            //echo "Read Attribute command data: ".$data."\n";

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        function setParam2($dest,$address,$clusterId,$attributeId,$destinationEndPoint,$Param, $dataType, $proprio) {
            $this->deamonlog('debug',"command setParam2");
            // Msg Type = 0x0530

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)
            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode = "02";
            $targetShortAddress = $address;
            $sourceEndpoint = "01";
            $destinationEndpoint = $destinationEndPoint; // "01";
            $profileID = "0104"; // "0000";
            $clusterID = $clusterId; // "0021";
            $securityMode = "02"; // ???
            $radius = "30";
            // $dataLength = "16";

            $frameControl = "00";
            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute = "02";

            $attributeId = $attributeId[2].$attributeId[3].$attributeId[0].$attributeId[1]; // $attributeId;
            // $dataType = "42"; // string
            // $Param = "53616C6F6E31202020202020202020";
            // $Param = "Salon2         ";
            $lengthAttribut = sprintf("%02s",dechex(strlen( $Param ))); // "0F";
            $attributValue = ""; for ($i=0; $i < strlen($Param); $i++) { $attributValue .= sprintf("%02s",dechex(ord($Param[$i]))); }
            // $attributValue = $Param; // "53616C6F6E31202020202020202020"; //$Param;

            $data2 = $frameControl . $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $lengthAttribut . $attributValue;

            // $dataLength = "16";
            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
            $this->deamonlog('debug',"data2: ".$data2 );
            $this->deamonlog('debug',"length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            // $this->deamonlog('debug',"data: ".$data );
            // $this->deamonlog('debug',"lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );

        }

        function setParam3($dest,$Command) {
            // Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15

            $this->deamonlog('debug',"command setParam3");

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpoint = "01";

            if ( isset($Command['destinationEndpoint']) ) {
                    if ( $Command['destinationEndpoint']>1 ) {
                $destinationEndpoint = $Command['destinationEndpoint'];
            }
            else {
                $destinationEndpoint = "01";
            } // $destinationEndPoint; // "01";
            }
                else {
                $destinationEndpoint = "01";
            }

            $profileID = "0104";
            $clusterID = $Command['clusterId'];

            $securityMode = "02"; // ???
            $radius = "30";
            // $dataLength = define later

            $frameControlAPS = "40";   // APS Control Field
            // If Ack Request 0x40 If no Ack then 0x00
            // Avec 0x40 j'ai un default response

            $frameControlZCL = "14";   // ZCL Control Field
            // Disable Default Response + Manufacturer Specific

            $frameControl = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $Proprio = $Command['Proprio'][2].$Command['Proprio'][3].$Command['Proprio'][0].$Command['Proprio'][1];

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute = "02";

            // $attributeId = $attributeId[2].$attributeId[3].$attributeId[0].$attributeId[1]; // $attributeId;
            $attributeId = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            // $dataType = "42"; // string
            $dataType = $Command['attributeType'];

            // $Param = $Command['value'];
            // $lengthAttribut = sprintf("%02s",dechex(strlen( $Param )));
            // $attributValue = ""; for ($i=0; $i < strlen($Param); $i++) { $attributValue .= sprintf("%02s",dechex(ord($Param[$i]))); }
            $attributValue = $Command['value'];

            // $data2 = $frameControl . $Proprio. $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $lengthAttribut . $attributValue;
            $data2 = $frameControl . $Proprio. $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $attributValue;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $this->deamonlog('debug',"data2: ".$data2 . " length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug',"data: ".$data );
            // $this->deamonlog('debug',"lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );

        }

        // Needed for fc01 of Legrand Dimmer
        // clusterId=fc01&attributeId=0000&attributeType=09&value=0101
        function setParam4($dest,$Command) {

            $this->deamonlog('debug',"command setParam4");

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpoint = "01";
            if ( $Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

            $profileID = "0104";
            $clusterID = $Command['clusterId'];

            $securityMode = "02"; // ???
            $radius = "30";
            // $dataLength = define later

            $frameControlAPS = "40";   // APS Control Field
            // If Ack Request 0x40 If no Ack then 0x00
            // Avec 0x40 j'ai un default response

            $frameControlZCL = "10";   // ZCL Control Field
            // Disable Default Response + Not Manufacturer Specific

            $frameControl = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute = "02";

            $attributeId = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType = $Command['attributeType'];
            $attributValue = $Command['value'];

            // $data2 = $frameControl . $Proprio. $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $attributValue;
            $data2 = $frameControl . $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $attributValue;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $this->deamonlog('debug',"data2: ".$data2 . " length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug',"data: ".$data );
            // $this->deamonlog('debug',"lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );

        }

        // Needed for fc41 of Legrand Contacteur
        function commandLegrand($dest,$Command) {

            // $this->deamonlog('debug',"commandLegrand()");

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>


            $addressMode = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpoint = "01";
            if ( $Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

            $profileID = "0104";
            $clusterID = "FC41";

            $securityMode = "02"; // ???
            $radius = "30";
            // $dataLength = define later

            // ---------------------------

            $frameControlAPS = "15";   // APS Control Field, see doc for details

            $manufacturerCode = "2110";
            $transqactionSequenceNumber = "1A"; // to be reviewed
            $command = "00";

            // $data = "00"; // 00 = Off, 02 = Auto, 03 = On.
            $data = $Command['Mode'];

            $data2 = $frameControlAPS . $manufacturerCode . $transqactionSequenceNumber . $command . $data;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            // $this->deamonlog('debug',"data2: ".$data2 . " length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug',"data: ".$data );
            // $this->deamonlog('debug',"lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        function getParam($priority,$dest,$address,$clusterId,$attributeId,$destinationEndPoint,$Proprio) {
            /*
             <address mode: uint8_t>
             <target short address: uint16_t>
             <source endpoint: uint8_t>
             <destination endpoint: uint8_t>
             <Cluster id: uint16_t>
             <direction: uint8_t>
             <manufacturer specific: uint8_t>
             <manufacturer id: uint16_t>
             <number of attributes: uint8_t>
             <attributes list: data list of uint16_t  each>
             Direction:
             0 - from server to client
             1 - from client to server
             Manufacturer specific :
             0 – No
             1 – Yes

             8 16 8 8 16 8 8 16 8 16 -> 2 4 2 2 4 2 2 4 2 4 -> 28/2d -> 14d -> 0x0E

             19:07:11.771 -> 01 02 11 02 10 02 10 02 1E 91 02 12 B3 28 02 11 02 11 02 10 02 16 02 10 02 10 02 10 02 10 02 11 02 10 02 10 03
             00:15:32.115 -> 01 02 11 02 10 02 10 02 1E 91 02 12 B3 28 02 11 02 11 02 10 02 16 02 10 02 10 02 10 02 10 02 11 02 10 02 10 03
             00:15:32.221 <- 01 80 00 00 04 86 00 03 01 00 03
             00:20:29.130 <- 01 80 00 00 04 80 00 05 01 00 03
             80 00 00 04 84 00 01 01 00
             00:15:32.248 <- 01 81 00 00 0D 02 03 B3 28 01 00 06 00 00 00 10 00 01 00 03
             00:20:29.156 <- 01 81 00 00 0D 05 05 B3 28 01 00 06 00 00 00 10 00 01 01 03
             81 00 00 0D 00 01 b3 28 01 00 06 00 00 00 10 00 01 00
             81 00 00 0D 06 06 b3 28 01 00 06 00 00 00 10 00 01 01

             01: start
             02 11 02 10: Msg Type: 02 11 => 01 02 10 => 00 ==> 0100 (read attribute request)
             02 10 02 1E: Length: 00 0E
             91: Chrksum
             02 12 B3 28 02 11 02 11 02 10 02 16 02 10 02 10 02 10 02 10 02 11 02 10 02 10: data
             02 12: address mode: 02
             B3 28: Short address: B328
             02 11: Source EndPoint: 01
             02 11: Dest EndPoint: 01
             02 10 02 16: Cluster Id: 00 06 (General On/Off)
             02 10: Direction: 0 (from server to client)
             02 10: manufacturer specific: 00 (No)
             02 10 02 10 : manufacturer id: 00 00
             02 11: number of attributes: 01
             02 10 02 10: attributes list: data list of uint16_t  each: 00 00

             */
            // $param = "02B3280101000600000000010000";
            // echo $param;
            // $this->sendCmd($priority, $dest, "0100", "000E", $param );
            // $this->sendCmd($priority, $dest, "0100", "000E", "02B3280101000600000000010000");

            $cmd = "0100";
            // $lenth = "000E";
            $addressMode = "02";
            // $address = $Command['address'];
            $sourceEndpoint = "01";
            // $destinationEndpoint = "01";
            //$ClusterId = "0006";
            $ClusterId = $clusterId;
            $Direction = "00";
            if ( (strlen($Proprio)<1) || ($Proprio="0000") ) {
                $manufacturerSpecific = "00";
                $manufacturerId = "0000";
            }
            else {
                $manufacturerSpecific = "01";
                $manufacturerId = $Proprio;
            }
            $numberOfAttributes = "01";
            $attributesList = $attributeId;
            //      02              B328        01              01                      0006            00          00                      0000            01                      0000
            //      02              faec        01              01                      0500           00           01                      115f            01                      fff1
            $data = $addressMode . $address . $sourceEndpoint . $destinationEndPoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug','data: '.$data.' length: '.$lenth);

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        // getParamHue: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
        function getParamHue($priority,$dest,$address,$clusterId,$attributeId) {
            $this->deamonlog('debug','getParamHue');

            $priority = $Command['priority'];

            $cmd = "0100";
            $lenth = "000E";
            $addressMode = "02";
            // $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "0B";
            //$ClusterId = "0006";
            $ClusterId = $clusterId;
            $Direction = "00";
            $manufacturerSpecific = "00";
            $manufacturerId = "0000";
            $numberOfAttributes = "01";
            // $attributesList = "0000";
            $attributesList = $attributeId;

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
            // $this->deamonlog('debug','len data: '.strlen($data));
            //echo "Read Attribute command data: ".$data."\n";

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        // getParamOSRAM: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
        function getParamOSRAM($priority,$dest,$address,$clusterId,$attributeId) {
            $this->deamonlog('debug','getParamOSRAM');

            $priority = $Command['priority'];

            $cmd = "0100";
            $lenth = "000E";
            $addressMode = "02";
            // $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "03";
            //$ClusterId = "0006";
            $ClusterId = $clusterId;
            $Direction = "00";
            $manufacturerSpecific = "00";
            $manufacturerId = "0000";
            $numberOfAttributes = "01";
            // $attributesList = "0000";
            $attributesList = $attributeId;

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
            // $this->deamonlog('debug','len data: '.strlen($data));
            //echo "Read Attribute command data: ".$data."\n";

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        function getChecksum( $msgtype, $length, $datas) {
            $temp = 0;
            $temp ^= hexdec($msgtype[0].$msgtype[1]) ;
            $temp ^= hexdec($msgtype[2].$msgtype[3]) ;
            $temp ^= hexdec($length[0].$length[1]) ;
            $temp ^= hexdec($length[2].$length[3]);

            for ($i=0;$i<=(strlen($datas)-2);$i+=2)
            {
                $temp ^= hexdec($datas[$i].$datas[$i+1]);
            }

            if ($this->debug["Checksum"]) $this->deamonlog('debug',"getChecksum fct - msgtype: " . $msgtype . " length: " . $length . " datas: " . $datas . " strlen data: " . strlen($datas) . " checksum calculated: " . sprintf("%02X",$temp) );

            return sprintf("%02X",$temp);
        }

        function transcode($datas) {
            if ( $this->debug['transcode'] ) $this->deamonlog('debug','transcode fct - transcode data: '.$datas);
            $mess="";

            if (strlen($datas)%2 !=0) return -1;

            for ($i=0;$i<(strlen($datas));$i+=2)
            {
                $byte = $datas[$i].$datas[$i+1];

                if (hexdec($byte)>=hexdec(10))
                {
                    $mess.=$byte;

                }else{
                    $mess.="02".sprintf("%02X",(hexdec($byte) ^ 0x10));
                }
            }
            return $mess;
        }

        function sendCmd($priority, $dest, $cmd, $len, $datas='') {
            if ( $this->debug['sendCmd'] ) { $this->deamonlog("debug", "sendCmd fct - dest: " . json_encode($dest) . " cmd: ".json_encode($cmd). " priority: ".json_encode($priority) ); }

            $i = str_replace( 'Abeille', '', $dest );
            if ( config::byKey('AbeilleActiver'.$i, 'Abeille', 'N', 1) == 'N' ) {
                $this->deamonlog("debug", "sendCmd fct - Je ne traite pas cette commande car la zigate est desactivee." );
                return;
            }
            if ( $this->debug['sendCmd2'] ) { $this->deamonlog("debug", "sendCmd fct - i: ".$i." key: ".config::byKey('AbeilleIEEE_Ok'.$i, 'Abeille', '-1', 1)); }
            if ( config::byKey('AbeilleIEEE_Ok'.$i, 'Abeille', '-1', 1) == '-1' ) {
                $this->deamonlog("debug", "sendCmd fct - Je ne traite pas cette commande car la zigate ne semble pas etre sur le bon port tty." );
                return;
            }

            if ( $dest == "none" ) {
                if ( $this->debug['sendCmd'] ) { $this->deamonlog("debug", "sendCmd fct - Je ne mets pas la commande dans la queue car la dest est none" ); }
                return; // on ne process pas les commande pour les zigate qui n existe pas.
            }

            if ( is_null($priority) ) {
                if ( $this->debug['sendCmd'] ) { $this->deamonlog("debug", "sendCmd fct - priority is null, rejecting the command" ); }
                return;
            }

            if ( $priority < priorityMin ) {
                if ( $this->debug['sendCmd'] ) { $this->deamonlog("debug", "sendCmd fct - priority out of range (rejecting the command): ".$priority ); }
                return;
            }

            // A chaque retry la priority increase d'un.
            if ( $priority > (priorityMax+priorityMax) ) {
                if ( $this->debug['sendCmd'] ) { $this->deamonlog("debug", "sendCmd fct - priority out of range (rejecting the command): ".$priority ); }
                return;
            }

            // received = time when the commande was added to the queue
            // time = when the commande was ssend to the zigate last time
            // retry = nombre de tentative restante
            // priority = priority du message

            if ( ($i>0) && ($i<=maxNbOfZigate) ) {
                $this->cmdQueue[$i][] = array( 'received'=>microtime(true), 'time'=>0, 'retry'=>$this->maxRetry, 'priority'=>$priority, 'dest'=>$dest, 'cmd'=>$cmd, 'len'=>$len, 'datas'=>$datas );
                if ( $this->debug['sendCmd2'] ) { $this->deamonlog("debug", "sendCmd fct - Je mets la commande dans la queue: ".$i." - Nb Cmd:".count($this->cmdQueue[$i])." -> ".json_encode($this->cmdQueue[$i]) ); }
                if ( count($this->cmdQueue[$i]) > 50 ) $this->deamonlog('info', 'Il y a plus de 50 messages dans le queue de la zigate: '.$i );
            }
            else {
                if ( $this->debug['sendCmd'] ) { $this->deamonlog("debug", "sendCmd fct - Je recois un message pour une queue qui n est pas valide: ->".$i."<-"); }
            }
        }

        function writeToDest( $f, $dest, $cmd, $len, $datas) {
            fwrite($f,pack("H*","01"));
            fwrite($f,pack("H*",$this->transcode($cmd))); //MSG TYPE
            fwrite($f,pack("H*",$this->transcode($len))); //LENGTH
            if (!empty($datas))
            {
                fwrite($f,pack("H*",$this->transcode($this->getChecksum($cmd,$len,$datas)))); //checksum
                fwrite($f,pack("H*",$this->transcode($datas))); //datas
            }else{
                fwrite($f,pack("H*",$this->transcode($this->getChecksum($cmd,$len,"00")))); //checksum
            }
            fwrite($f,pack("H*","03"));
        }

        function sendCmdToZigate( $dest, $cmd, $len, $datas) {
            // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
            // SVP ne pas enlever ce code c est tres utile pour le debug et verifier les commandes envoyées sur le port serie.

            // if ( $this->debug['sendCmd'] ) $this->deamonlog('debug','sendCmd fct - Dest:'.$dest.' cmd:'.$cmd.' len:'.$len.' datas:'.$datas);
            if (0) {
                $f=fopen("/var/www/html/log/toto","w");
                $this->writeToDest( $f, $dest, $cmd, $len, $datas);
                fclose($f);
            }

            $i = str_replace( 'Abeille', '', $dest );
            $destSerial = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '1', 1);

            if ( config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') == 'Y' ) {
                if ( $this->debug['sendCmdToZigate'] ) { $this->deamonlog("debug", " =================> Envoi de la commande a la zigate: ".$destSerial.'-'.$cmd.'-'.$len.'-'.$datas); }
                $f=fopen( $destSerial,"w");
                $this->writeToDest( $f, $destSerial, $cmd, $len, $datas);
                fclose($f);
            }
            else {
                if ( $this->debug['sendCmdToZigate'] ) { $this->deamonlog("debug", " =================> Pas d envoi de la commande a la zigate (zigate inactive): ".$destSerial.'-'.$cmd.'-'.$len.'-'.$datas); }
            }
        }

        function afficheStatQueue() {
            $texteLog = "";
            if ( isset( $this->tempoMessageQueue ) ) {
                $texteLog .= "tempoMessageQueue: ".count( $this->tempoMessageQueue )." - ";
            }
            for ( $i=1; $i<=$this->zigateNb; $i++) {
                if ( isset( $this->cmdQueue[$i] ) ) {
                    $texteLog .= "cmdQueue: ".$i." nb message: ".count( $this->cmdQueue[$i] )." - ";
                }
            }
            $this->deamonlog("debug", $texteLog );
        }

        function processCmdQueueToZigate() {

            for ( $i=1; $i<=$this->zigateNb; $i++) {
                // $this->deamonlog("debug", "processCmdQueueToZigate fct - start avec la queue : ".$i);
                if ( !isset( $this->cmdQueue[$i]) )     continue;                                     // si la queue n existe pas je passe mon chemin
                if ( count( $this->cmdQueue[$i] ) < 1 ) continue;                                     // si la queue est vide je passe mon chemin
                if ( $this->zigateAvailable[$i] == 0 )   continue;                                     // Si la zigate n est pas considéré dispo je passe mon chemin
                // if ( $this->debug['processCmdQueueToZigate'] ) { $this->deamonlog("debug", "processCmdQueueToZigate fct - start y a des truc a faire dans la queue : ".$i); }
                if ( $this->debug['sendCmdAck2'] ) { $this->deamonlog("debug", "--------------------"); }
                if ( $this->debug['sendCmdAck'] ) { $this->deamonlog("debug", "J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate a envoyer." ); }
                if ( $this->debug['sendCmdAck2'] ) { $this->deamonlog("debug", "J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate a envoyer: ".json_encode($this->cmdQueue[$i]) ); }

                $this->zigateAvailable[$i] = 0;    // Je considere la zigate pas dispo car je lui envoie une commande
                $this->timeLastAck[$i] = time();

                $cmd = array_shift($this->cmdQueue[$i]);    // Je recupere la premiere commande
                $this->sendCmdToZigate( $cmd['dest'], $cmd['cmd'], $cmd['len'], $cmd['datas'] );    // J'envoie la premiere commande récupérée
                $cmd['retry']--;                        // Je reduis le nombre de retry restant
                $cmd['priority']++;                     // Je reduis la priorité car
                $cmd['time']=time();                    // Je mets l'heure a jour

                // Le nombre de retry n'est pas épuisé donc je remet la commande dans la queue
                if ($cmd['retry']>0) {
                    array_unshift( $this->cmdQueue[$i], $cmd);  // Je remets la commande dans la queue avec l heure, prio++ et un retry -1
                }
                else {
                    if ( $this->debug['sendCmdAck2'] ) { $this->deamonlog("info", "La commande n a plus de retry, on la drop: ".json_encode($cmd)); }
                }

                if ( $this->debug['sendCmdAck2'] ) { $this->deamonlog("debug", "J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres envoie commande: ".json_encode($this->cmdQueue[$i]) ); }

                if ( $this->debug['sendCmdAck2'] ) { $this->deamonlog("debug", "--------------------"); }
            }
        }

        function processCmd( $Command ) {

            if ( $this->debug['processCmd'] ) $this->deamonlog("debug", "processCmd fct - begin processCmd function, Command: ".json_encode($Command) );

            if (!isset($Command)) {
                if ( $this->debug['processCmd'] ) $this->deamonlog('debug',"processCmd fct - processCmd Command not set return");
                return;
            }

            if ( isset($Command['priority']) ) {
                if ( isset($Command['address']) ) {
                    $NE = Abeille::byLogicalId( $Command['dest'].'/'.$Command['address'], 'Abeille' );
                    if ( $NE ) {
                        if ( $NE->getIsEnable() ) {
                            if ( ( time() - strtotime($NE->getStatus('lastCommunication')) ) > (60*$NE->getTimeout() ) ) {
                                $this->deamonlog('debug',"processCmd fct - NE en Time Out alors je mets la priorite au minimum.");
                                $priority = priorityLostNE;
                            }
                            else {
                                $priority = $Command['priority'];
                            }
                        }
                        else {
                            $this->deamonlog('debug',"processCmd fct - NE desactive, je ne fais rien.");
                            return;
                        }
                    }
                    else {
                        $this->deamonlog('debug',"processCmd fct - NE introuvable, probablement une annonce, j envoie la commande.");
                        $priority = $Command['priority'];
                    }
                }
                else {
                    $priority = $Command['priority'];
                }
            }
            else {
                $this->deamonlog('debug',"processCmd fct - priority not defined !!!");
                $priority = priorityInterrogation;
            }

            $dest = $Command['dest'];
            
            // En gros 0 normal, 1 RAW mode, 2 Mode hybride
            if ( isset($Command['setModeHybride']) )
            {
                if ($Command['setModeHybride']=="normal")
                {
                    if ( $this->debug['processCmd'] ) $this->deamonlog('debug',"processCmd fct - Set Mode Hybride");
                    $this->sendCmd($priority,$dest,"0002","0001","00");
                }
            
                if ($Command['setModeHybride']=="RAW")
                {
                    if ( $this->debug['processCmd'] ) $this->deamonlog('debug',"processCmd fct - Set Mode Hybride");
                    $this->sendCmd($priority,$dest,"0002","0001","01");
                }
      
                if ($Command['setModeHybride']=="hybride")
                {
                    if ( $this->debug['processCmd'] ) $this->deamonlog('debug',"processCmd fct - Set Mode Hybride");
                    $this->sendCmd($priority,$dest,"0002","0001","02");
                }
            }

            if ( isset($Command['getVersion']) )
            {
                if ($Command['getVersion']=="Version")
                {
                    if ( $this->debug['processCmd'] ) $this->deamonlog('debug',"processCmd fct - Get Version");
                    $this->sendCmd($priority,$dest,"0010","0000","");
                }
            }

            if ( isset($Command['reset']) )
            {
                if ($Command['reset']=="reset")
                {
                    //    16:56:56.300 -> 01 02 10 11 02 10 02 10 11 03
                    // 01 start
                    // 02 10 11: 00 11: Reset
                    // 02 10 02 10 : 00 00: Length
                    // 11: crc
                    // 03: Stop
                    $this->sendCmd($priority,$dest,"0011","0000","");
                }
            }

            if ( isset($Command['ErasePersistentData']) )
            {
                if ($Command['ErasePersistentData']=="ErasePersistentData")
                {
                    $this->sendCmd($priority,$dest,"0012","0000","");
                }
            }

            // Resets (“Factory New”) the Control Bridge but persists the frame counters.
            if ( isset($Command['FactoryNewReset']) )
            {
                if ($Command['FactoryNewReset']=="FactoryNewReset")
                {
                    $this->sendCmd($priority,$dest,"0013","0000","");
                }
            }

            // abeilleList abeilleListAll
            if ( isset($Command['abeilleList']) )
            {

                if ($Command['abeilleList']=="abeilleListAll")
                {
                    $this->deamonlog('debug',"Get Abeilles List");
                    //echo "Get Abeilles List\n";
                    $this->sendCmd($priority,$dest,"0015","0000","");
                }
            }
            //----------------------------------------------------------------------
            // Set Time server (v3.0f)
            if ( isset($Command['setTimeServer']) )
            {
                if (!isset($Command['time']) ) {
                    $Command['time'] = time();
                }
                $this->deamonlog('debug',"setTimeServer");
                $cmd = "0016";
                $data = sprintf("%08s",dechex($Command['time']));

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }

            if ( isset($Command['getTimeServer'])  )
            {
                $this->deamonlog('debug',"getTimeServer");
                $cmd = "0017";
                $data = "";

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
            //----------------------------------------------------------------------
            if ( isset($Command['setOnZigateLed'])  )
            {
                $this->deamonlog('debug',"setOnZigateLed");
                $cmd = "0018";
                $data = "01";

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }

            if ( isset($Command['setOffZigateLed'])  )
            {
                $this->deamonlog('debug',"setOffZigateLed");
                $cmd = "0018";
                $data = "00";

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
            //----------------------------------------------------------------------
            if ( isset($Command['setCertificationCE'])  )
            {
                $this->deamonlog('debug',"setCertificationCE");
                $cmd = "0019";
                $data = "01";

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }

            if ( isset($Command['setCertificationFCC'])  )
            {
                $this->deamonlog('debug',"setCertificationFCC");
                $cmd = "0019";
                $data = "02";

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
            //----------------------------------------------------------------------
            // https://github.com/fairecasoimeme/ZiGate/issues/145
            // PHY_PIB_TX_POWER_DEF (default - 0x80)
            // PHY_PIB_TX_POWER_MIN (minimum - 0)
            // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
            if ( isset($Command['TxPower'])  )
            {
                $this->deamonlog('debug',"TxPower");
                $cmd = "0806";
                $data = $Command['TxPower'];
                if ( $data < 10 ) $data = '0'.$data;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
            //----------------------------------------------------------------------
              // https://github.com/fairecasoimeme/ZiGate/issues/145
              // Added cmd 0807 Get Tx Power #175
              // PHY_PIB_TX_POWER_DEF (default - 0x80)
              // PHY_PIB_TX_POWER_MIN (minimum - 0)
              // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
              if ( isset($Command['GetTxPower'])  )
              {
                  $this->deamonlog('debug',"GetTxPower");
                  $cmd = "0807";
                  $data = "";

                  $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                  $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
              }
            //----------------------------------------------------------------------
            if ( isset($Command['setChannelMask'])  )
            {
                $this->deamonlog('debug',"setChannelMask");
                $cmd = "0021";
                $data = $Command['setChannelMask'];

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
            //----------------------------------------------------------------------
            if ( isset($Command['setExtendedPANID'])  )
            {
                $this->deamonlog('debug',"setExtendedPANID");
                $cmd = "0020";
                $data = $Command['setExtendedPANID'];

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
            //----------------------------------------------------------------------
            if ( isset($Command["startNetwork"]) )
            {
                if ($Command['startNetwork']=="StartNetwork")
                {
                    $this->sendCmd($priority,$dest,"0024","0000","");
                }
            }

            if ( isset($Command["getNetworkStatus"]) )
            {
                if ($Command['getNetworkStatus']=="getNetworkStatus")
                {
                    $this->sendCmd($priority,$dest,"0009","0000","");
                }
            }

            if ( isset($Command['SetPermit']) )
            {
                if ($Command['SetPermit']=="Inclusion")
                {
                    $cmd = "0049";
                    $lenth = "0004";
                    $data = "FFFCFE00";
                    // <target short address: uint16_t>
                    // <interval: uint8_t>
                    // <TCsignificance: uint8_t>

                    // Target address: May be address of gateway node or broadcast (0xfffc)
                    // Interval:
                    // 0 = Disable Joining 1 – 254 = Time in seconds to allow joins 255 = Allow all joins
                    // TCsignificance:
                    // 0 = No change in authentication 1 = Authentication policy as spec

                    // 09:08:29.156 -> 01 02 10 49 02 10 02 14 50 FF FC 1E 02 10 03
                    // 01 : Start
                    // 02 10 49: 00 49: Permit Joining request Msg Type = 0x0049
                    // 02 10 02 14: Length:
                    // 50: Chrksum
                    // FF FC:<target short address: uint16_t>
                    // 1E: <interval: uint8_t>
                    // 02 10: <TCsignificance: uint8_t> 00

                    // 09:08:29.193 <- 01 80 00 00 04 F4 00 39 00 49 03
                    $this->sendCmd($priority,$dest,$cmd,$lenth,$data); //1E = 30 secondes

                    // $CommandAdditionelle['permitJoin'] = "permitJoin";
                    // $CommandAdditionelle['permitJoin'] = "Status";
                    // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "Cmd".$dest."/Ruche/permitJoin", "Status" );
                }
                if ($Command['SetPermit']=="InclusionStop")
                {
                    $cmd = "0049";
                    $lenth = "0004";
                    $data = "FFFC0000";
                    // <target short address: uint16_t>
                    // <interval: uint8_t>
                    // <TCsignificance: uint8_t>

                    // Target address: May be address of gateway node or broadcast (0xfffc)
                    // Interval:
                    // 0 = Disable Joining 1 – 254 = Time in seconds to allow joins 255 = Allow all joins
                    // TCsignificance:
                    // 0 = No change in authentication 1 = Authentication policy as spec

                    // 09:08:29.156 -> 01 02 10 49 02 10 02 14 50 FF FC 1E 02 10 03
                    // 01 : Start
                    // 02 10 49: 00 49: Permit Joining request Msg Type = 0x0049
                    // 02 10 02 14: Length:
                    // 50: Chrksum
                    // FF FC:<target short address: uint16_t>
                    // 1E: <interval: uint8_t>
                    // 02 10: <TCsignificance: uint8_t> 00

                    // 09:08:29.193 <- 01 80 00 00 04 F4 00 39 00 49 03
                    $this->sendCmd($priority,$dest,$cmd,$lenth,$data); //1E = 30 secondes

                    // $CommandAdditionelle['permitJoin'] = "permitJoin";
                    // $CommandAdditionelle['permitJoin'] = "Status";
                    // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "Cmd".$dest."/Ruche/permitJoin", "Status" );
                }
            }

            if ( isset($Command['permitJoin']) )
            {
                if ($Command['permitJoin']=="Status")
                {
                    // “Permit join” status on the target
                    // Msg Type =  0x0014

                    $cmd = "0014";
                    $lenth = "0000";
                    $data = "";

                    $this->sendCmd($priority,$dest,$cmd,$lenth,$data); //1E = 30 secondes
                }
            }

            //----------------------------------------------------------------------
            // Management Network Update request
            // ZPS_eAplZdpMgmtNwkUpdateRequest - APP_eZdpMgmtNetworkUpdateReq - E_SL_MSG_MANAGEMENT_NETWORK_UPDATE_REQUEST
            if ( isset($Command['managementNetworkUpdateRequest']) && isset($Command['address']) )
            {
                // Msg Type =  0x004A

                // <target short address: uint16_t>
                // <channel mask: uint32_t>
                // <scan duration: uint8_t>
                // <scan count: uint8_t> -> Si valeur a 5 alors l ampoule envoit 5 messages avec les mesures
                // <network update ID: uint8_t>
                // <network manager short address: uint16_t>
                //
                // Channel Mask: Mask of channels to scan
                // Scan Duration: 0 – 0xFF Multiple of superframe duration.
                // Scan count: Scan repeats 0 – 5
                // Network Update ID: 0 – 0xFF Transaction ID for scan

                $cmd = "004A";

                $shortAddress               = $Command['address'];
                $channelMask                = "07FFF800";
                $scanDuration               = "01";
                $scanCount                  = "01";
                $networkUpdateId            = "01";
                $networkManagerShortAddress = "0000";

                $data = $shortAddress . $channelMask . $scanDuration . $scanCount . $networkUpdateId .$networkManagerShortAddress;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }

            //----------------------------------------------------------------------
            // 2.4.3.3.3   Mgmt_Rtg_req
            //
            if ( isset($Command['Mgmt_Rtg_req']) && isset($Command['address']) )
            {
                $this->deamonlog('debug',"command Mgmt_Rtg_req");
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)
                // <data: auint8_t>
                // APS Part <= data
                //

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "00";
                $destinationEndpoint    = "00";
                $profileID              = "0000";
                $clusterID              = "0032";
                $securityMode           = "28";
                $radius                 = "30";
                // $dataLength             = "16";

                $SQN = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.
                $startIndex = "00";

                $data2 = $SQN . $startIndex;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
                $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

                $this->deamonlog('debug',"Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength );
                $this->deamonlog('debug',"Data2: ".$SQN."-".$startIndex );

                $data = $data1 . $data2;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            //----------------------------------------------------------------------
            // Bind
            // Title => 000B57fffe3025ad (IEEE de l ampoule)
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006
            if ( isset($Command['bind']) )
            {
                $this->deamonlog('debug',"command bind");
                // Msg Type = 0x0030
                $cmd = "0030";

                // <target extended address: uint64_t>                                  -> 16
                // <target endpoint: uint8_t>                                           -> 2
                // <cluster ID: uint16_t>                                               -> 4
                // <destination address mode: uint8_t>                                  -> 2
                // <destination address:uint16_t or uint64_t>                           -> 4 / 16 => 0000 for Zigate
                // <destination endpoint (value ignored for group address): uint8_t>    -> 2

                // $targetExtendedAddress  = "000B57fffe3025ad";
                $targetExtendedAddress  = $Command['address'];
                //
                if ( isset($Command['targetEndpoint']) ) {
                    $targetEndpoint         = $Command['targetEndpoint'];
                }
                else {
                    $targetEndpoint         = "01";
                }

                // $clusterID              = "0006";
                $clusterID              = $Command['ClusterId'];
                // $destinationAddressMode = "02";
                $destinationAddressMode = "03";

                // $destinationAddress     = "0000";
                // $destinationAddress     = "00158D0001B22E24";
                $destinationAddress     = $Command['reportToAddress'];

                $destinationEndpoint    = "01";
                //  16 + 2 + 4 + 2 + 4 + 2 = 30/2 => 15 => F
                // $lenth = "000F";
                //  16 + 2 + 4 + 2 + 16 + 2 = 42/2 => 21 => 15
                $lenth = "0015";

                $data =  $targetExtendedAddress . $targetEndpoint . $clusterID . $destinationAddressMode . $destinationAddress . $destinationEndpoint;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // Bind Short
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed
            if ( isset($Command['bindShort']) )
            {
                $this->deamonlog('debug',"command bind short");
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                                            -> 1
                // <target extended address: uint64_t>                                  -> 8
                // <target endpoint: uint8_t>                                           -> 1
                // <cluster ID: uint16_t>                                               -> 2
                // <destination address mode: uint8_t>                                  -> 1
                // <destination address:uint16_t or uint64_t>                           -> 8
                // <destination endpoint (value ignored for group address): uint8_t>    -> 1
                // => 34 -> 0x22

                $addressMode = "02";
                $targetShortAddress = $Command['address'];
                $sourceEndpointBind = "00";
                $destinationEndpointBind = "00";
                $profileIDBind = "0000";
                $clusterIDBind = "0021";
                $securityMode = "02";
                $radius = "30";
                $dataLength = "16";

                $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

                // $targetExtendedAddress = "1d1369feff9ffd90";
                $targetExtendedAddress = reverse_hex($Command['targetExtendedAddress']);
                // $targetEndpoint = "01";
                $targetEndpoint = $Command['targetEndpoint'];
                // $clusterID = "0600";  // 0006 but need to be inverted
                $clusterID = reverse_hex($Command['clusterID']);
                $destinationAddressMode = "03";
                // $destinationAddressMode = $Command['destinationAddressMode'];
                // $destinationAddress = "221b9a01008d1500";
                $destinationAddress = reverse_hex($Command['destinationAddress']);
                // $destinationEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndpoint'];

                // $targetExtendedAddress  = "000B57fffe3025ad";
                // $targetExtendedAddress  = $Command['address'];
                //
                //// if ( isset($Command['targetEndpoint']) ) {
                ////    $targetEndpoint         = $Command['targetEndpoint'];
                ////}
                ////else {
                ////    $targetEndpoint         = "01";
                ////}

                // $clusterID              = "0006";
                // // $clusterIDBind             = $Command['ClusterId'];
                // $destinationAddressMode = "02";
                // // $destinationAddressMode = "03";

                // $destinationAddress     = "0000";
                // $destinationAddress     = "00158D0001B22E24";
                // // $destinationAddress     = $Command['reportToAddress'];

                ////$destinationEndpoint    = "01";

                $lenth = "0022";

                // $data =  $targetExtendedAddress . $targetEndpoint . $clusterID . $destinationAddressMode . $destinationAddress . $destinationEndpoint;
                // $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $profileIDBind . $clusterIDBind . $securityMode . $radius . $dataLength;
                $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $clusterIDBind . $profileIDBind . $securityMode . $radius . $dataLength;
                $data2 = $dummy . $targetExtendedAddress . $targetEndpoint . $clusterID  . $destinationAddressMode . $destinationAddress . $destinationEndpoint;

                $this->deamonlog('debug',"Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) );
                $this->deamonlog('debug',"Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$destinationAddress."-".$destinationEndpoint." len: ".(strlen($data2)/2) );

                $data = $data1 . $data2;
                // $this->deamonlog('debug',"Data: ".$data." len: ".(strlen($data)/2) );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // setReport
            // Title => setReport
            // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
            if ( isset($Command['setReport']) )
            {
                $this->deamonlog('debug',"command setReport");
                // Configure Reporting request
                // Msg Type = 0x0120

                $cmd = "0120";

                // <address mode: uint8_t>              -> 2
                // <target short address: uint16_t>     -> 4
                // <source endpoint: uint8_t>           -> 2
                // <destination endpoint: uint8_t>      -> 2
                // <Cluster id: uint16_t>               -> 4
                // <direction: uint8_t>                 -> 2
                // <manufacturer specific: uint8_t>     -> 2
                // <manufacturer id: uint16_t>          -> 4
                // <number of attributes: uint8_t>      -> 2
                // <attributes list: data list of uint16_t  each>
                //      Attribute direction : uint8_t   -> 2
                //      Attribute type : uint8_t        -> 2
                //      Attribute id : uint16_t         -> 4
                //      Min interval : uint16_t         -> 4
                //      Max interval : uint16_t         -> 4
                //      Timeout : uint16_t              -> 4
                //      Change : uint8_t                -> 2

                $addressMode            = "02";                     // 01 = short
                $targetShortAddress     = $Command['address'];
                // $sourceEndpoint         = "01";
                if ( isset( $Command['sourceEndpoint'] ) ) {
                    if ( hexdec($Command['sourceEndpoint'])>1 ) {
                        $sourceEndpoint = $Command['sourceEndpoint'];
                    }
                    else {
                        $sourceEndpoint = "01";
                    }
                }
                else {
                    $sourceEndpoint = "01";
                }
                // $targetEndpoint    = "01";
                if ( hexdec($Command['targetEndpoint'])>1 ) { $targetEndpoint = $Command['targetEndpoint']; } else { $targetEndpoint = "01"; }
                $ClusterId              = $Command['ClusterId'];
                $direction              = "00";                     // To Server / To Client
                $manufacturerSpecific   = "00";                     // Tx Server / Rx Client
                $manufacturerId         = "0000";                   // ?
                $numberOfAttributes     = "01";                     // One element at a time
                $AttributeDirection     = "00";                     // ?

                // E_ZCL_BOOL            = 0x10,                                    -> Etat Ampoule Ikea
                // E_ZCL_UINT8           = 0x20,              // Unsigned 8 bit     -> Level Ampoule Ikea
                // cf chap 7.1.3 of JN-UG-3113 v1.2
                $AttributeType          = $Command['AttributeType'];

                $AttributeId            = $Command['AttributeId'];    // "0000"
                //$AttributeId            = "0000";

                // $MinInterval            = "0000";
                if ( isset($Command['MinInterval']) ) { $MinInterval = $Command['MinInterval']; } else { $MinInterval = "0000"; }
                if ( isset($Command['MaxInterval']) ) { $MaxInterval = $Command['MaxInterval']; } else { $MaxInterval = "0000"; }
                $Timeout                = "0000";
                $Change                 = "00";

                //  2 + 4 + 2 + 2 + 4 + 2 + 2 + 4 + 2    + 2 + 2 + 4 + 4 + 4 + 4 + 2 = 46/2 => 23 => 17
                $lenth = "0017";

                $data =  $addressMode . $targetShortAddress . $sourceEndpoint . $targetEndpoint . $ClusterId . $direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $AttributeDirection . $AttributeType . $AttributeId . $MinInterval . $MaxInterval . $Timeout . $Change ;

                // $this->deamonlog('debug',"Data: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$targetEndpoint."-".$ClusterId."-".$direction."-".$manufacturerSpecific."-".$manufacturerId."-".$numberOfAttributes."-".$AttributeDirection."-".$AttributeType."-".$AttributeId."-".$MinInterval."-".$MaxInterval."-".$Timeout."-".$Change);

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // CmdAbeille/Ruche/commissioningGroupAPS -> address=a048&groupId=AA00
            // Commission group for Ikea Telecommande On/Off still interrupteur
            if ( isset($Command['commissioningGroupAPS']) )
            {
                $this->deamonlog('debug',"commissioningGroupAPS");

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id: Get Group Id Response
                // 01 Total
                // 00 Start Index
                // 01 Count
                // 00 Group Type
                // 001B Group Id

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profileID              = "0104";
                $clusterID              = "1000";
                $securityMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "19";
                $transactionSequence    = "01";
                $cmdId                  = "41";
                $total                  = "01";
                $startIndex             = "00";
                $count                  = "01";
                $groupId                = reverse_hex($Command['groupId']);
                $groupType              = "00";

                $data2 = $zclControlField . $transactionSequence . $cmdId . $total . $startIndex . $count . $groupId . $groupType;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

                $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

                $this->deamonlog('debug',"Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) );
                $this->deamonlog('debug',"Data2: ".$zclControlField."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) );

                $data = $data1 . $data2;
                // $this->deamonlog('debug',"Data: ".$data." len: ".sprintf("%04s",dechex(strlen( $data )/2)) );

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['getGroupMembership']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) )
            {
                $cmd = "0062";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group count: uint8_t>
                // <group list:data>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2
                $groupCount = "00";                                     // -> 2
                $groupList = "";                                        // ? Not mentionned in the ZWGUI -> 0
                //  2 + 4 + 2 + 2 + 2 + 0 = 12/2 => 6
                $lenth = "0006";

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupCount . $groupList ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['viewScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
            {
                $cmd = "00A0";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['storeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
            {
                $cmd = "00A4";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['recallScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
            {
                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['sceneGroupRecall']) && isset($Command['groupID']) && isset($Command['sceneID']) )
            {
                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "01";                                    // Group Address -> 1, Short Address -> 2
                $address = $Command['groupID'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = "02"; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['addScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) && isset($Command['sceneName']) )
            {
                $cmd = "00A1";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>
                // <transition time: uint16_t>
                // <scene name length: uint8_t>
                // <scene name max length: uint8_t>
                // <scene name data: data each element is uint8_t>

                $addressMode            = "02";
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['DestinationEndPoint'];

                $groupID                = $Command['groupID'];
                $sceneID                = $Command['sceneID'];

                $transitionTime         = "0001";

                $sceneNameLength        = sprintf("%02s", (strlen( $Command['sceneName'] )/2) );      // $Command['sceneNameLength'];
                $sceneNameMaxLength     = sprintf("%02s", (strlen( $Command['sceneName'] )/2) );      // $Command['sceneNameMaxLength'];
                $sceneNameData          = $Command['sceneName'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID . $transitionTime . $sceneNameLength . $sceneNameMaxLength . $sceneNameData ;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['getSceneMembership']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) )
            {
                $cmd = "00A6";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID ;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['removeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
            {
                $cmd = "00A2";

                //0x00A2
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID ;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['removeSceneAll']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) )
            {
                $cmd = "00A3";

                //0x00A3
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID ;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['sceneLeftIkea']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) )
            {
                $this->deamonlog('debug',"Specific Command to simulate Ikea Telecommand < and >");

                // Msg Type = 0x0530
                $cmd = "0530";

                $addressMode = "01"; // 01 pour groupe
                $targetShortAddress = $Command['address'];
                $sourceEndpointBind = "01";
                $destinationEndpointBind = "01";
                $profileIDBind = "0104";
                $clusterIDBind = "0005";
                $securityMode = "02";
                $radius = "30";
                // $dataLength = "16";

                $FrameControlField = "05";  // 1
                $manu = "7C11";
                $SQN = "00";
                $cmdIkea = "07";
                $cmdIkeaParams = "00010D00";

                $data2 = $FrameControlField . $manu . $SQN . $cmdIkea . $cmdIkeaParams;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $clusterIDBind . $profileIDBind . $securityMode . $radius . $dataLength;

                $this->deamonlog('debug',"Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(dechex(strlen($data1)/2)) );
                $this->deamonlog('debug',"Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$destinationAddress."-".$destinationEndpoint." len: ".(dechex(strlen($data2)/2)) );

                $data = $data1 . $data2;
                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                // $this->deamonlog('debug',"Data: ".$data." len: ".(dechex(strlen($data)/2)) );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
            if ( isset($Command['WindowsCovering']) && isset($Command['address']) && isset($Command['clusterCommand']) )
            {
                // 0x00FA    Windows covering (v3.0f only)
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <cluster command : uint8_t>
                // 0 = Up/Open
                // 1 = Down/Close
                // 2 = Stop
                // 4 = Go To Lift Value (extra cmd : Value in cm)
                // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                // 7 = Go To Tilt Value (extra cmd : Value in cm)
                // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)
                // <extra command : uint8_t or uint16_t >

                $cmd = "00FA";

                $addressMode    = "02"; // 01 pour groupe, 02 pour NE
                $address        = $Command['address'];
                $srcEP          = "01";
                $detEP          = "01";
                $clusterCommand = $Command['clusterCommand'];

                $data = $addressMode . $address . $srcEP . $detEP . $clusterCommand;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                // $this->deamonlog('debug',"Data: ".$data." len: ".(dechex(strlen($data)/2)) );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
            if ( isset($Command['WindowsCoveringGroup']) && isset($Command['address']) && isset($Command['clusterCommand']) )
            {
                // 0x00FA    Windows covering (v3.0f only)
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <cluster command : uint8_t>
                // 0 = Up/Open
                // 1 = Down/Close
                // 2 = Stop
                // 4 = Go To Lift Value (extra cmd : Value in cm)
                // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                // 7 = Go To Tilt Value (extra cmd : Value in cm)
                // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)
                // <extra command : uint8_t or uint16_t >

                $cmd = "00FA";

                $addressMode    = "04"; // 01 pour groupe, 02 pour NE, 03 pour , 04 pour broadcast
                $address        = $Command['address'];
                $srcEP          = "01";
                $detEP          = "01";
                $clusterCommand = $Command['clusterCommand'];

                $data = $addressMode . $address . $srcEP . $detEP . $clusterCommand;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                // $this->deamonlog('debug',"Data: ".$data." len: ".(dechex(strlen($data)/2)) );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['ActiveEndPoint']) )
            {
                $cmd = "0045";

                // <target short address: uint16_t>

                $address = $Command['address']; // -> 4

                //  4 = 4/2 => 2
                $lenth = "0002";

                $data = $address;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['SimpleDescriptorRequest']) )
            {
                $cmd = "0043";

                // <target short address: uint16_t>
                // <endpoint: uint8_t>

                $address = $Command['address']; // -> 4
                $endpoint = $Command['endPoint']; // -> 2

                //  4 + 2 = 6/2 => 3
                $lenth = "0003";

                $data = $address . $endpoint ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            //----------------------------------------------------------------------------
            if ( isset($Command['Network_Address_request']) )
            {
                $cmd = "0040";

                // <target short address: uint16_t> -> 4
                // <extended address:uint64_t>      -> 16
                // <request type: uint8_t>          -> 2
                // <start index: uint8_t>           -> 2
                // Request Type:
                // 0 = Single Request 1 = Extended Request
                // -> 24 / 2 = 12 => 0x0C

                $address = $Command['address'];
                $IeeeAddress = $Command['IEEEAddress'];
                $requestType = "01";
                $startIndex = "00";

                $data = $address . $IeeeAddress . $requestType . $startIndex ;
                $lenth = "000C"; // A verifier

                $this->deamonlog('debug','Network_Address_request: '.$data . ' - ' . $lenth  );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['IEEE_Address_request']) )
            {
                $cmd = "0041";

                // <target short address: uint16_t> -> 4
                // <short address: uint16_t>        -> 4
                // <request type: uint8_t>          -> 2
                // <start index: uint8_t>           -> 2
                // Request Type: 0 = Single 1 = Extended

                $address = $Command['address'];
                $shortAddress = $Command['shortAddress'];
                $requestType = "01";
                $startIndex = "00";

                $data = $address . $shortAddress . $requestType . $startIndex ;
                // $lenth = strlen($data)/2;
                $lenth = "0006";

                $this->deamonlog('debug','IEEE_Address_request: '.$data . ' - ' . $lenth  );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['Management_LQI_request']) )
            {
                $cmd = "004E";

                // <target short address: uint16_t>
                // <Start Index: uint8_t>

                $address = $Command['address'];     // -> 4
                $startIndex = $Command['StartIndex']; // -> 2

                //  4 + 2 = 6/2 => 3
                $lenth = "0003";

                $data = $address . $startIndex ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['identifySend']) && isset($Command['address']) && isset($Command['duration']) && isset($Command['DestinationEndPoint']) )
            {
                $cmd = "0070";
                // Msg Type = 0x0070
                // Identify Send

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <time: uint16_t> Time: Seconds

                //                 Start  Type         Length           Short       Addr
                // 17:29:31.398 -> 01     02 10 70     02 10 02 17      10 02    12 6E    1B 02 11 02 11 02 10 10 03
                // 01: Start
                // 02 10 70: 00 70 - Msg Type Identify Send
                // 02 10 02 17 => Length -> 7
                // 10 02 => Mode 2 -> Short
                //

                // 17:29:31.461 <- 01 80 00 00 05 FE 00 0B 00 70 00 03
                // 17:29:31.523 <- 01 81 01 00 07 F5 0B 01 00 03 00 00 7B 03

                $addressMode = "02"; // Short Address -> 2
                $address = $Command['address']; // -> 4
                $sourceEndpoint = "01"; // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2
                $time = $Command['duration']; // -> 4
                //  2 + 4 + 2 + 2 + 4 = 14/2 => 7
                $lenth = "0007";
                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $time ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // Don't know how to make it works
            if ( isset($Command['touchLinkFactoryResetTarget']) )
            {
                if ($Command['touchLinkFactoryResetTarget']=="DO")
                {
                    $this->sendCmd($priority,$priority,$dest,"00D2","0000","");
                }
            }

            // setLevel on one object
            if ( isset($Command['setLevel']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['Level']) && isset($Command['duration']) )
            {
                $cmd = "0081";
                // 11:53:06.479 -> 01 02 10 81 02 10 02 19 C6 02 12 83 DF 02 11 02 11 02 11 AA 02 10 BB 03
                //                 01 02 10 81 02 10 02 19 d7 02 12 83 df 02 11 02 11 02 11 02 11 02 11 03
                //
                // 02 83 DF 01 01 01 ff 00 BB
                //
                // 01: Start
                // 02 10 81: Msg Type: 00 81 -> Move To Level
                // 02 10 02 19: Lenght
                // C6: CRC
                // 02 12: <address mode: uint8_t> : 02
                // 83 DF: <target short address: uint16_t> 83 DF (Lampe Z Ikea)
                // 02 11: <source endpoint: uint8_t>: 01
                // 02 11: <destination endpoint: uint8_t>: 01
                // 02 11: <onoff : uint8_t>: 01
                // AA: <Level: uint8_t > AA Value I put to identify easely: Level to reach
                // 02 10 BB: <Transition Time: uint16_t>: 00BB Value I put to identify easely: Transition duration
                $addressMode = $Command['addressMode'];
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndpoint'];
                $onoff = "01";
                if ( $Command['Level']<16 )
                {
                    $level = "0".dechex($Command['Level']);
                    // $this->deamonlog('debug',"setLevel: ".$Command['Level']."-".$level);
                }
                else
                {
                    $level = dechex($Command['Level']);
                    // $this->deamonlog('debug',"setLevel: ".$Command['Level']."-".$level);
                }

                // $duration = "00" . $Command['duration'];
                if ( $Command['duration']<16 )
                {
                    $duration = "0".dechex($Command['duration']); // echo "duration: ".$Command['duration']."-".$duration."-\n";
                }
                else
                {
                    $duration = dechex($Command['duration']); // echo "duration: ".$Command['duration']."-".$duration."-\n";
                }
                $duration = "00" . $duration;

                // 11:53:06.543 <- 01 80 00 00 04 53 00 56 00 81 03
                // 11:53:06.645 <- 01 81 01 00 06 DD 56 01 00 08 04 00 03
                // 8 16 8 8 8 8 16
                // 2  4 2 2 2 2  4 = 18/2d => 9d => 0x09
                $lenth = "0009";

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $onoff . $level . $duration ;
                // echo "data: " . $data . "\n";

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );

                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );

                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2+$Command['duration']), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3+$Command['duration']), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );
            }

            if ( isset($Command['moveToLiftAndTiltBSO']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['inclinaison']) && isset($Command['duration']) )
            {
                $this->deamonlog('debug',"command moveToLiftAndTiltBSO");

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id: Get Group Id Response
                // 01 Total
                // 00 Start Index
                // 01 Count
                // 00 Group Type
                // 001B Group Id

                $addressMode            = $Command['addressMode'];
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profileID              = "0104";
                $clusterID              = "0008";
                $securityMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "11";
                $transactionSequence    = "01";
                $cmdId                  = "10";  // Cmd Proprio Profalux
                $option                 = "02";  // Je ne touche que le Tilt
                $Lift                   = "00";  // Not used
                $Tilt                   = "2D";  // 2D move to 45deg
                $transitionTime         = "FFFF";
                // $startIndex             = "00";
                // $count                  = "01";
                // $groupId                = reverse_hex($Command['groupId']);
                // $groupType              = "00";

                $data2 = $zclControlField . $transactionSequence . $cmdId . $option . $Lift . $Tilt . $transitionTime ;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

                $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

                $this->deamonlog('debug',"Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) );
                $this->deamonlog('debug',"Data2: ".$zclControlField."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) );

                $data = $data1 . $data2;
                // $this->deamonlog('debug',"Data: ".$data." len: ".sprintf("%04s",dechex(strlen( $data )/2)) );

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // setLevelStop
            if ( isset($Command['setLevelStop']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['sourceEndpoint']) && isset($Command['destinationEndpoint']) )
            {
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>

                $cmd = "0084";
                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = $Command['sourceEndpoint'];
                $destinationEndpoint    = $Command['destinationEndpoint'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint ;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ( (isset($Command['WriteAttributeRequest'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']) )
            {
                $this->setParam3( $dest, $Command );
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ( (isset($Command['WriteAttributeRequestVibration'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']) )
            {
                $this->setParamXiaomi( $dest, $Command );
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ( (isset($Command['WriteAttributeRequestActivateDimmer'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']) )
            {
                $this->setParam4( $dest, $Command );
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004

            if ( (isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']) && isset($Command['Proprio']) )
            {
                $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], $Command['EP'], $Command['Proprio'] );
            }
            else
            {
                if ( (isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']) )
                {
                    $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], $Command['EP'], "0000" );
                }
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            if ( (isset($Command['ReadAttributeRequestHue'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) )
            {
                // echo "ReadAttributeRequest pour address: " . $Command['address'] . "\n";
                // if ( $Command['ReadAttributeRequest']==1 )
                //{
                // $this->getParamHue( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "0B" );
                $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "0B", "0000" );
                //}
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            if ( (isset($Command['ReadAttributeRequestOSRAM'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) )
            {
                // echo "ReadAttributeRequest pour address: " . $Command['address'] . "\n";
                // if ( $Command['ReadAttributeRequest']==1 )
                //{
                // getParamOSRAM( $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "01" );
                $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "03", "0000" );
                //}
            }

            if ( isset($Command['writeAttributeRequestIAS_WD']) ) {
                // Parameters: EP=#EP&mode=Flash&duration=#slider#

                    $this->deamonlog('debug',"command writeAttributeRequestIAS_WD");
                    // Msg Type = 0x0111

                    $priority = $Command['priority'];

                    $cmd = "0111";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <direction: uint8_t>
                    // <manufacturer specific: uint8_t>
                    // <manufacturer id: uint16_t>
                    // <Warning Mode: uint8_t>
                    // <Warning Duration: uint16_t>
                    // <Strobe duty cycle : uint8_t>
                    // <Strobe level : uint8_t>

                    $addressMode = "02";
                    $targetShortAddress = $Command['address'];
                    $sourceEndpoint = "01";
                    $destinationEndpoint = "01";
                    $direction = "01";
                    $manufacturerSpecific = "00";
                    $manufacturerId = "0000";
                    $warningMode = "04";
                    if ( $Command['mode'] == "Flash" )      $warningMode = "04";        // 14, 24, 34: semble faire le meme son meme si la doc indique: Burglar, Fire, Emergency / 04: que le flash
                    if ( $Command['mode'] == "Sound" )      $warningMode = "10";
                    if ( $Command['mode'] == "FlashSound" ) $warningMode = "14";
                    $warningDuration = "000A"; // en seconde
                    if ( $Command['duration'] > 0 )         $warningDuration = sprintf("%04s", dechex($Command['duration']) );
                    // $strobeDutyCycle = "01";
                    // $strobeLevel = "F0";

                    $data = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $direction . $manufacturerSpecific . $manufacturerId . $warningMode . $warningDuration; // . $strobeDutyCycle . $strobeLevel;

                    $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                    $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['addGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']) )
            {
                $this->deamonlog('debug',"Add a group to a device");
                //echo "Add a group to an IKEA bulb\n";

                // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
                // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
                // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
                // Add Group
                // Message Description
                // Msg Type = 0x0060 Command ID = 0x00
                $cmd = "0060";
                $lenth = "0007";
                // <address mode: uint8_t>
                //<target short address: uint16_t>
                //<source endpoint: uint8_t>
                //<destination endpoint: uint8_t>
                //<group address: uint16_t>
                $addressMode = "02";
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['DestinationEndPoint'];
                $groupAddress = $Command['groupAddress'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupAddress ;
                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // Add Group APS
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed

            if ( isset($Command['addGroupAPS'])  )
            {
                $this->deamonlog('debug',"command add group with APS");
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (05 -> 0x05)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                      -> 1
                // <cmdAddGroup>                                  -> 1
                // <group>                                        -> 2
                // <length>                                       -> 1

                // => 16 -> 0x10

                $addressMode = "02";
                $targetShortAddress = $Command['address'];
                $sourceEndpointBind = "01";
                $destinationEndpointBind = "01";
                $profileIDBind = "0104";
                $clusterIDBind = "0004";
                $securityMode = "02";
                $radius = "30";
                $dataLength = "06";

                $dummy = "01";  // I don't know why I need this but if I don't put it then I'm missing some data
                $dummy1 = "00";  // Dummy

                $cmdAddGroup = "00";
                $groupId = "AAAA";
                $length = "00";

                $lenth = "0011";

                // $data =  $targetExtendedAddress . $targetEndpoint . $clusterID . $destinationAddressMode . $destinationAddress . $destinationEndpoint;
                // $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $profileIDBind . $clusterIDBind . $securityMode . $radius . $dataLength;
                $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $clusterIDBind . $profileIDBind . $securityMode . $radius . $dataLength;
                $data2 = $dummy . $dummy1 . $cmdAddGroup . $groupId . $length;

                $this->deamonlog('debug',"Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) );
                $this->deamonlog('debug',"Data2: ".$dummy . $dummy1 . $cmdAddGroup . $groupId . $length." len: ".(strlen($data2)/2) );

                $data = $data1 . $data2;
                // $this->deamonlog('debug',"Data: ".$data." len: ".(strlen($data)/2) );

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['removeGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']) )
            {
                $this->deamonlog('debug',"Remove a group to a device");
                //echo "Remove a group to an IKEA bulb\n";

                // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
                // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
                // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
                // Add Group
                // Message Description
                // Msg Type = 0x0060 Command ID = 0x00
                $cmd = "0063";
                $lenth = "0007";
                // <address mode: uint8_t>
                //<target short address: uint16_t>
                //<source endpoint: uint8_t>
                //<destination endpoint: uint8_t>
                //<group address: uint16_t>
                $addressMode = "02";
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['DestinationEndPoint'] ;
                $groupAddress = $Command['groupAddress'];

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupAddress ;
                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // Replace Equipement
            if ( isset($Command['replaceEquipement']) && isset($Command['old']) && isset($Command['new']) )
            {
                $this->deamonlog('debug',"Replace an Equipment");

                $old = $Command['old'];
                $new = $Command['new'];

                $this->deamonlog('debug',"Update eqLogic table for new object");
                $sql =          "update `eqLogic` SET ";
                $sql = $sql .   "name = 'Abeille-".$new."-New' , logicalId = '".$new."', configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql .   "where  eqType_name = 'Abeille' and logicalId = '".$old."' and configuration like '%".$old."%'";
                $this->deamonlog('debug',"sql: ".$sql);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

                $this->deamonlog('debug',"Update cmd table for new object");
                $sql =          "update `cmd` SET ";
                $sql = $sql .   "configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql .   "where  eqType = 'Abeille' and configuration like '%".$old."%' ";
                $this->deamonlog('debug',"sql: ".$sql);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
            }

            //
            if ( isset($Command['UpGroup']) && isset($Command['address']) && isset($Command['step']) )
            {
                $this->deamonlog('debug','UpOnOffGroup for: '.$Command['address']);
                // <address mode: uint8_t>          -> 2
                // <target short address: uint16_t> -> 4
                // <source endpoint: uint8_t>       -> 2
                // <destination endpoint: uint8_t>  -> 2
                // <onoff: uint8_t>                 -> 2
                // <step mode: uint8_t >            -> 2
                // <step size: uint8_t>             -> 2
                // <Transition Time: uint16_t>      -> 4
                // -> 20/2 =10 => 0A

                $cmd = "0082";
                $lenth = "000A";
                if ( isset ( $Command['addressMode'] ) ) { $addressMode = $Command['addressMode']; } else { $addressMode = "02"; }

                $address = $Command['address'];
                $sourceEndpoint = "01";
                if ( isset ( $Command['destinationEndpoint'] ) ) { $destinationEndpoint = $Command['destinationEndpoint'];} else { $destinationEndpoint = "01"; };
                $onoff = "00";
                $stepMode = "00"; // 00 : Up, 01 : Down
                $stepSize = $Command['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                $this->sendCmd($priority, $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$stepMode.$stepSize.$TransitionTime );
            }

            if ( isset($Command['DownGroup']) && isset($Command['address']) && isset($Command['step']) )
            {
                $this->deamonlog('debug','UpOnOffGroup for: '.$Command['address']);
                // <address mode: uint8_t>          -> 2
                // <target short address: uint16_t> -> 4
                // <source endpoint: uint8_t>       -> 2
                // <destination endpoint: uint8_t>  -> 2
                // <onoff: uint8_t>                 -> 2
                // <step mode: uint8_t >            -> 2
                // <step size: uint8_t>             -> 2
                // <Transition Time: uint16_t>      -> 4
                // -> 20/2 =10 => 0A

                $cmd = "0082";
                $lenth = "000A";
                if ( isset ( $Command['addressMode'] ) ) { $addressMode = $Command['addressMode']; } else { $addressMode = "02"; }

                $address = $Command['address'];
                $sourceEndpoint = "01";
                if ( isset ( $Command['destinationEndpoint'] ) ) { $destinationEndpoint = $Command['destinationEndpoint'];} else { $destinationEndpoint = "01"; };
                $onoff = "00";
                $stepMode = "01"; // 00 : Up, 01 : Down
                $stepSize = $Command['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                $this->sendCmd($priority, $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$stepMode.$stepSize.$TransitionTime );
            }

            // ON / OFF with no effects
            if ( isset($Command['onoff']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) )
            {
                if ( $this->debug['processCmd'] ) $this->deamonlog('debug','processCmd fct - fct OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action']);
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <command ID: uint8_t>
                    // Command Id
                    // 0 - Off
                    // 1 - On
                    // 2 - Toggle

                $cmd = "0092";
                $lenth = "0006";
                $addressMode = $Command['addressMode'];
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndpoint'];
                $action = $Command['action'];

                $this->sendCmd($priority, $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action );

                if ( $addressMode != "01" ) {
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );
                    }
            }

            // On / Off Timed Send
            if ( isset($Command['OnOffTimed']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) && isset($Command['onTime']) && isset($Command['offWaitTime']) )
            {
                if ( $this->debug['processCmd'] ) $this->deamonlog('debug','OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'].' - '.$Command['onTime'].' - '.$Command['ffWaitTime'] );
                // <address mode: uint8_t>    Status
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <onoff: uint8_t>
                // <on time: uint16_t>
                // <off time: uint16_t>
                    // On / Off:
                    // 0 = Off
                    // 1 = On
                    // Time: Seconds

                $cmd = "0093";
                // $lenth = "0006";
                $addressMode = $Command['addressMode'];
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndpoint'];
                $action = $Command['action'];
                $onTime = $Command['onTime'];
                $offWaitTime = $Command['offWaitTime'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action.$onTime.$offWaitTime;
                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );

                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );

                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+$Command['onTime']), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+$Command['onTime']), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );
            }

            // Move to Colour
            if ( isset($Command['setColour']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['X']) && isset($Command['Y'])  && isset($Command['destinationEndPoint']) )
            {
                // <address mode: uint8_t>              2
                // <target short address: uint16_t>     4
                // <source endpoint: uint8_t>           2
                // <destination endpoint: uint8_t>      2
                // <colour X: uint16_t>                 4
                // <colour Y: uint16_t>                 4
                // <transition time: uint16_t >         4

                $cmd = "00B7";
                // 8+16+8+8+16+16+16 = 88 /8 = 11 => 0x0B
                $lenth = "000B";

                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['destinationEndPoint'];
                $colourX                = $Command['X'];
                $colourY                = $Command['Y'];
                $duration               = "0001";

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $colourX . $colourY . $duration ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // Take RGB (0-255) convert to X, Y and send the color
            if ( isset($Command['setColourRGB']) )
            {
                // The reverse transformation
                // https://en.wikipedia.org/wiki/SRGB

                $R=$Command['R'];
                $G=$Command['G'];
                $B=$Command['B'];

                $a = 0.055;

                // are in the range 0 to 1. (A range of 0 to 255 can simply be divided by 255.0).
                $Rsrgb = $R / 255;
                $Gsrgb = $G / 255;
                $Bsrgb = $B / 255;

                if ( $Rsrgb <= 0.04045 ) { $Rlin = $Rsrgb/12.92; } else { $Rlin = pow( ($Rsrgb+$a)/(1+$a), 2.4); }
                if ( $Gsrgb <= 0.04045 ) { $Glin = $Gsrgb/12.92; } else { $Glin = pow( ($Gsrgb+$a)/(1+$a), 2.4); }
                if ( $Bsrgb <= 0.04045 ) { $Blin = $Bsrgb/12.92; } else { $Blin = pow( ($Bsrgb+$a)/(1+$a), 2.4); }

                $X = 0.4124 * $Rlin + 0.3576 * $Glin + 0.1805 *$Blin;
                $Y = 0.2126 * $Rlin + 0.7152 * $Glin + 0.0722 *$Blin;
                $Z = 0.0193 * $Rlin + 0.1192 * $Glin + 0.9505 *$Blin;

                if ( ($X + $Y + $Z)!=0 ) {
                    $x = $X / ( $X + $Y + $Z );
                    $y = $Y / ( $X + $Y + $Z );
                }
                else {
                    echo "Can t do the convertion.";
                }

                $x = $x*255*255;
                $y = $y*255*255;

                // Meme commande que la commande du dessus
                $cmd = "00B7";
                // 8+16+8+8+16+16+16 = 88 /8 = 11 => 0x0B
                $lenth = "000B";

                $addressMode = "02";
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndPoint'];
                $colourX = str_pad( dechex($x), 4, "0", STR_PAD_LEFT);
                $colourY = str_pad( dechex($y), 4, "0", STR_PAD_LEFT);
                $duration = "0001";

                $this->deamonlog( 'debug', "colourX: ".$colourX." colourY: ".$colourY );

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $colourX . $colourY . $duration ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // Move to Colour Temperature
            if ( isset($Command['setTemperature']) && isset($Command['address']) && isset($Command['temperature']) && isset($Command['destinationEndPoint']) )
            {
                // <address mode: uint8_t>              2
                // <target short address: uint16_t>     4
                // <source endpoint: uint8_t>           2
                // <destination endpoint: uint8_t>      2
                // <colour temperature: uint16_t>       4
                // <transition time: uint16_t>          4

                $cmd = "00C0";
                // 2+4+2+2+4+4 = 18 /2 = 9 => 0x09
                $lenth = "0009";

                $addressMode = $Command['addressMode'];
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndPoint'];
                $temperature = $Command['temperature'];
                $duration = "0001";

                $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $temperature . $duration ;

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['getName']) && isset($Command['address']) )
            {
                // $this->deamonlog('debug','Get Name from: '.$Command['address']);
                //echo "Get Name from: ".$Command['address']."\n";
                if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
                $this->getParam( $priority, $dest, $Command['address'], "0000", "0005", $Command['destinationEndPoint'], "0000" );
            }

            if ( isset($Command['getLocation']) && isset($Command['address']) )
            {
                //echo "Get Name from: ".$Command['address']."\n";
                if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
                // $this->deamonlog('debug','Get Location from: '.$Command['address'].'->'.$Command['destinationEndPoint'].'<-');
                $this->getParam( $priority, $dest, $Command['address'], "0000", "0010", $Command['destinationEndPoint'], "0000" );
            }

            if ( isset($Command['setLocation']) && isset($Command['address']) )
            {
                // $this->deamonlog('debug','Set Location of: '.$Command['address']);
                if ( $Command['location'] == "" ) { $Command['location'] = "Not Def"; }
                if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }

                $this->setParam2( $dest, $Command['address'], "0000", "0010",$Command['destinationEndPoint'],$Command['location'], "42" );
            }

            if ( isset($Command['MgtLeave']) && isset($Command['address']) && isset($Command['IEEE']) )
            {
                // $this->deamonlog('debug','Leave for: '.$Command['address']." - ".$Command['IEEE']);
                $cmd = "0047";
                //$lenth = "";

                // <target short address: uint16_t>
                // <extended address: uint64_t>
                // <Rejoin: uint8_t>
                // <Remove Children: uint8_t>
                //  Rejoin,
                //      0 = Do not rejoin
                //      1 = Rejoin
                //  Remove Children
                //      0 = Leave, removing children
                //      1 = Leave, do not remove children

                $address        = $Command['address'];
                $IEEE           = $Command['IEEE'];
                $Rejoin         = "00";
                $RemoveChildren = "01";

                $data = $address . $IEEE . $Rejoin . $RemoveChildren;
                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            // if ( isset($Command['Remove']) && isset($Command['address']) && isset($Command['IEEE']) )
            // https://github.com/KiwiHC16/Abeille/issues/332
            if ( isset($Command['Remove']) && isset($Command['IEEE']) )
            {
                // $this->deamonlog('debug','Remove for: '.$Command['address']." - ".$Command['IEEE']);
                $this->deamonlog('debug','Remove for: '.$Command['IEEE']);
                $cmd = "0026";

                // Doc is probably not up to date, need to provide IEEE twice
                // Tested and works in case of a NE in direct to coordinator
                // To be tested if message is routed.
                // <target short address: uint16_t>
                // <extended address: uint64_t>

                // $address        = $Command['address'];
                $address        = $Command['IEEE'];
                $IEEE           = $Command['IEEE'];

                $data = $address . $IEEE ;
                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
            }

            if ( isset($Command['commandLegrand']) )
            {
                $this->commandLegrand($dest,$Command);
            }
        }

        function getQueueName($queue){
            /**
             * return queue name from queueId
             *
             * has to be implemented in each classe that use msg_get_queue
             *
             * @param $queueId
             * @return string queue name
             */
            $queueTopic="Not Found";
            switch($queue){
                case $this->queueKeyAbeilleToCmd:
                    $queueTopic="queueKeyAbeilleToCmd";
                    break;
                case $this->queueKeyParserToCmd:
                    $queueTopic="queueKeyParserToCmd";
                    break;
                case $this->queueKeyCmdToCmd:
                    $queueTopic="queueKeyCmdToCmd";
                    break;
                case $this->queueKeyCmdToAbeille:
                    $queueTopic="queueKeyCmdToAbeille";
                    break;
                case $this->queueKeyLQIToCmd:
                    $queueTopic="queueKeyLQIToCmd";
                    break;
                case $this->queueKeyXmlToCmd:
                    $queueTopic="queueKeyXmlToCmd";
                    break;
                case $this->queueKeyFormToCmd:
                    $queueTopic="queueKeyFormToCmd";
                    break;
                case $this->queueKeyParserToCmdSemaphore:
                    $queueTopic="queueKeyParserToCmdSemaphore";
                    break;
            }
            return $queueTopic;
        }

        function procmsg( $message ) {

            if ( $this->debug['procmsg2'] ) $this->deamonlog("debug", "----------");
            $this->deamonlog("info", "procmsg fct - message: ". json_encode($message) );

            $topic      = $message->topic;
            $msg        = $message->payload;
            $priority   = $message->priority;

            $test = explode('/', $topic);
            if ( sizeof( $test ) !=3 ) {
                $this->deamonlog("debug", "Le format du message n est pas bon je ne le traite pas !!!");
                return ;
            }

            list($type, $address, $action) = explode('/', $topic);

            if (preg_match("(^TempoCmd)", $type)) {
                if ( $this->debug['procmsg2'] ) $this->deamonlog("debug", "procmsg fct - topic: Ajoutons le message a queue Tempo.");
                $this->addTempoCmdAbeille( $topic, $msg, $priority);
                return;
            }

            if (!preg_match("(^Cmd)", $type)) {
                if ( $this->debug['procmsg2'] ) $this->deamonlog('warning','procmsg fct - Msg Received: Type: {'.$type.'} <> Cmdxxxxx donc ce n est pas pour moi, no action.');
                return;
            }

            $dest = str_replace( 'Cmd', '',  $type );

            if ( $this->debug['procmsg3'] ) $this->deamonlog("debug", 'procmsg fct - Msg Received: Topic: {'.$topic.'} => '.$msg);

            if ( $this->debug['procmsg3'] ) $this->deamonlog("debug", 'procmsg fct - (ln: '.__LINE__.') - Type: '.$type.' Address: '.$address.' avec Action: '.$action);

            // Jai les CmdAbeille/Ruche et les CmdAbeille/shortAdress que je dois gérer un peu differement les uns des autres.

            if ($address != "Ruche") {
                if ( $this->debug['procmsg3'] ) $this->deamonlog("debug", 'procmsg fct - Address != Ruche');
                switch ($action) {
                        //----------------------------------------------------------------------------
                    case "managementNetworkUpdateRequest":
                        $Command = array(
                                         "managementNetworkUpdateRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "Mgmt_Rtg_req":
                        $Command = array(
                                         "Mgmt_Rtg_req" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "Annonce":
                        if ( strlen($msg) == 2 ) {
                            // $this->deamonlog('info', 'Preparation de la commande annonce pour EP');
                            $Command = array(
                                             "ReadAttributeRequest" => "1",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "address" => $address,
                                             "clusterId" => "0000",
                                             "attributeId" => "0005",
                                             "EP"=>$msg,
                                             );
                        }
                        else {
                            if ($msg == "Default") {
                                // $this->deamonlog('info', 'Preparation de la commande annonce pour default');
                                $Command = array(
                                                 "ReadAttributeRequest" => "1",
                                                 "priority" => $priority,
                                                 "dest" => $dest,
                                                 "address" => $address,
                                                 "clusterId" => "0000",
                                                 "attributeId" => "0005",
                                                 "EP"=>"01",
                                                 );
                            }
                            if ($msg == "Hue") {
                                // $this->deamonlog('info', 'Preparation de la commande annonce pour Hue');
                                $Command = array(
                                                 "ReadAttributeRequestHue" => "1",
                                                 "priority" => $priority,
                                                 "dest" => $dest,
                                                 "address" => $address,
                                                 "clusterId" => "0000",
                                                 "attributeId" => "0005",
                                                 "EP"=>"0B",
                                                 );
                            }
                            if ($msg == "OSRAM") {
                                // $this->deamonlog('info', 'Preparation de la commande annonce pour OSRAM');
                                $Command = array(
                                                 "ReadAttributeRequestOSRAM" => "1",
                                                 "priority" => $priority,
                                                 "dest" => $dest,
                                                 "address" => $address,
                                                 "clusterId" => "0000",
                                                 "attributeId" => "0005",
                                                 "EP"=>"03",
                                                 );
                            }
                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "AnnonceProfalux":
                        if ($msg == "Default") {
                            $this->deamonlog('info', 'Preparation de la commande annonce pour default');
                            $Command = array(
                                             "ReadAttributeRequest" => "1",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "address" => $address,
                                             "clusterId" => "0000",
                                             "attributeId" => "0010",
                                             "EP"=>"03",
                                             );
                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOff":
                        if ( $this->debug['procmsg3'] ) $this->deamonlog("debug", 'procmsg fct - OnOff with dest: '.$dest);
                        $convertOnOff = array(
                                              "On"      => "01",
                                              "Off"     => "00",
                                              "Toggle"  => "02",
                                              );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                            $Command = array(
                                             "onoff" => "1",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "addressMode" => "02",
                                             "address" => $address,
                                             "destinationEndpoint" => $parameters['EP'],
                                             "action" => $convertOnOff[$parameters['Action']],
                                             );
                        }
                        else {

                            $actionId = $convertOnOff[$msg];

                            $Command = array(
                                             "onoff" => "1",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "addressMode" => "02",
                                             "address" => $address,
                                             "destinationEndpoint" => "01",
                                             "action" => $actionId,
                                             );
                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOff2":
                        if ($msg == "On") {
                            $actionId = "01";
                        }
                        if ($msg == "Off") {
                            $actionId = "00";
                        }
                        if ($msg == "Toggle") {
                            $actionId = "02";
                        }
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "02",
                                         "action" => $actionId,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOff3":
                        if ($msg == "On") {
                            $actionId = "01";
                        }
                        if ($msg == "Off") {
                            $actionId = "00";
                        }
                        if ($msg == "Toggle") {
                            $actionId = "02";
                        }
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "03",
                                         "action" => $actionId,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOffHue":
                        if ($msg == "On") {
                            $actionId = "01";
                        }
                        if ($msg == "Off") {
                            $actionId = "00";
                        }
                        if ($msg == "Toggle") {
                            $actionId = "02";
                        }
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "0B",
                                         "action" => $actionId,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOffOSRAM":
                        if ($msg == "On") {
                            $actionId = "01";
                        }
                        if ($msg == "Off") {
                            $actionId = "00";
                        }
                        if ($msg == "Toggle") {
                            $actionId = "02";
                        }
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "03",
                                         "action" => $actionId,
                                         );
                        break;
                            //----------------------------------------------------------------------------
                    case "commandLegrand":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) $parameters = proper_parse_str( $msg );

                        $Command = array(
                                         "commandLegrand" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => $parameters['EP'],
                                         "Mode" => $parameters['Mode'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "UpGroup":
                        $Command = array(
                                         "UpGroup" => "1",
                                         "addressMode" => "01",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "01", // Set but not send on radio
                                         "step" => $msg,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "DownGroup":
                        $Command = array(
                                         "DownGroup" => "1",
                                         "addressMode" => "01",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "01", // Set but not send on radio
                                         "step" => $msg,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOffGroup":
                        if ($msg == "On") {
                            $actionId = "01";
                        }
                        if ($msg == "Off") {
                            $actionId = "00";
                        }
                        if ($msg == "Toggle") {
                            $actionId = "02";
                        }
                        $Command = array(
                                         "onoff" => "1",
                                         "addressMode" => "01",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "01", // Set but not send on radio
                                         "action" => $actionId,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                        case "OnOffGroupTimed":
                            $fields = preg_split("/[=&]+/", $msg);
                            if (count($fields) > 1) $parameters = proper_parse_str( $msg );

                            if ($parameters['action'] == "On") {
                                $actionId = "01";
                            }
                            if ($parameters['action'] == "Off") {
                                $actionId = "00";
                            }
                            $Command = array(
                                             "OnOffTimed"           => "1",
                                             "addressMode"          => "01",
                                             "priority"             => $priority,
                                             "dest"                 => $dest,
                                             "address"              => $address,
                                             "destinationEndpoint"  => "01", // Set but not send on radio
                                             "action"               => $actionId,
                                             "onTime"               => $parameters['onTime'],
                                             "offWaitTime"          => $parameters['offWaitTime'],
                                             );
                            break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequest":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        // Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
                        $Command = array(
                                         "WriteAttributeRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "Proprio" => $parameters['Proprio'],
                                         // "clusterId" => $keywords[3],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $parameters['value'],
                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestVibration":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        // Proprio=115f&clusterId=0500&attributeId=fff1&attributeType=23&value=03010000&repeat=1
                        $Command = array(
                                         "WriteAttributeRequestVibration" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "Proprio" => $parameters['Proprio'],
                                         // "clusterId" => $keywords[3],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $parameters['value'],
                                         "repeat" => $parameters['repeat'],

                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestHostFlag":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        // $consigne = sprintf( "%06X", $parameters['value'] );
                        $consigne = $parameters['value'];
                        $consigneHex = $consigne[4].$consigne[5].$consigne[2].$consigne[3].$consigne[0].$consigne[1];

                        $Command = array(
                                         "WriteAttributeRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "Proprio" => $parameters['Proprio'],
                                         // "clusterId" => $keywords[3],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $consigneHex,
                                         // "repeat" => $parameters['repeat'],

                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestTemperatureSpiritConsigne":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        $consigne = sprintf( "%04X", $parameters['value']*100 );
                        $consigneHex = $consigne[2].$consigne[3].$consigne[0].$consigne[1];

                        $Command = array(
                                         "WriteAttributeRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "Proprio" => $parameters['Proprio'],
                                         // "clusterId" => $keywords[3],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $consigneHex,
                                         // "repeat" => $parameters['repeat'],

                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestValveSpiritConsigne":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        $consigne = sprintf( "%02X", $parameters['value'] );
                        $consigneHex = $consigne; // $consigne[2].$consigne[3].$consigne[0].$consigne[1];

                        $Command = array(
                                         "WriteAttributeRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "Proprio" => $parameters['Proprio'],
                                         // "clusterId" => $keywords[3],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $consigneHex,
                                         // "repeat" => $parameters['repeat'],

                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestTrvSpiritMode":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        $consigne = sprintf( "%02X", $parameters['value'] );
                        $consigneHex = $consigne; // $consigne[2].$consigne[3].$consigne[0].$consigne[1];

                        $Command = array(
                                         "WriteAttributeRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "Proprio" => $parameters['Proprio'],
                                         // "clusterId" => $keywords[3],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $consigneHex,
                                         // "repeat" => $parameters['repeat'],

                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestActivateDimmer":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', 'Msg Received: '.$msg);

                        $Command = array(
                                         "WriteAttributeRequestActivateDimmer" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         // "Proprio" => $keywords[1],
                                         "clusterId" => $parameters['clusterId'],
                                         // "attributeId" => $keywords[5],
                                         "attributeId" => $parameters['attributeId'],
                                         // "attributeType" => $keywords[7],
                                         "attributeType" => $parameters['attributeType'],
                                         // "value" => $keywords[9],
                                         "value" => $parameters['value'],
                                         );
                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "ReadAttributeRequest":
                        $keywords = preg_split("/[=&]+/", $msg);
                        if (count($keywords) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        $this->deamonlog('debug', 'AbeilleCmd: Msg received: '.json_encode($msg).' from NE');
                        if ( !isset($parameters['Proprio']) ) { $parameters['Proprio'] = "0000"; }
                        $Command = array(
                                         "ReadAttributeRequest" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"      => $address,
                                         "clusterId"    => $parameters['clusterId'],   // Don't change the speeling here but in the template
                                         "attributeId"  => $parameters['attributeId'],
                                         "EP"           => $parameters['EP'],
                                         "Proprio"      => $parameters['Proprio'],
                                         );
                        $this->deamonlog('debug', 'AbeilleCmd: Msg analysed: '.json_encode($Command).' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "ReadAttributeRequestHue":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "ReadAttributeRequestHue" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "clusterId" => $keywords[1],
                                         "attributeId" => $keywords[3],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "ReadAttributeRequestOSRAM":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "ReadAttributeRequestOSRAM" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "clusterId" => $keywords[1],
                                         "attributeId" => $keywords[3],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevel":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                            $Command = array(
                                             "setLevel"             => "1",
                                             "addressMode"          => "02",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "address"              => $address,
                                             "destinationEndpoint"  => $parameters['EP'],
                                             "Level"                => intval($parameters['Level'] * 255 / 100),
                                             "duration"             => $parameters['duration'],
                                             );
                        }
                        else {

                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelRaw":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                            $Command = array(
                                             "setLevel"             => "1",
                                             "addressMode"          => "02",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "address"              => $address,
                                             "destinationEndpoint"  => $parameters['EP'],
                                             "Level"                => $parameters['Level'],
                                             "duration"             => $parameters['duration'],
                                             );
                        }
                        else {

                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelOSRAM":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setLevel" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "03",
                                         "Level" => intval($keywords[1] * 255 / 100),
                                         "duration" => $keywords[3],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelVolet":
                        // Pour un get level (level de 0 à 255):
                        // a=0.00081872
                        // b=0.2171167
                        // c=-8.60201639
                        // level = level * level * a + level * b + c

                        // $a = -0.8571429;
                        // $b = 1.8571429;
                        // $c = 0;

                        $eqLogic = eqLogic::byLogicalId( $dest."/".$address, "Abeille" );
                        $a = $eqLogic->getConfiguration( 'paramA', 0);
                        $b = $eqLogic->getConfiguration( 'paramB', 1);
                        $c = $eqLogic->getConfiguration( 'paramC', 0);

                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        // $level255 = intval($keywords[1] * 255 / 100);
                        // $this->deamonlog('debug', 'level255: '.$level255);

                        $levelSlider = $parameters['Level'];                // Valeur entre 0 et 100
                        // $this->deamonlog('debug', 'level Slider: '.$levelSlider);

                        $levelSliderPourcent = $levelSlider/100;    // Valeur entre 0 et 1

                        // $level = min( max( round( $level255 * $level255 * a + $level255 * $b + $c ), 0), 255);
                        $levelPourcent = $a*$levelSliderPourcent*$levelSliderPourcent+$b*$levelSliderPourcent+$c;
                        $level = $levelPourcent * 255;
                        $level = min( max( round( $level), 0), 255);

                        $this->deamonlog('debug', 'level Slider: '.$levelSlider.' level calcule: '.$levelPourcent.' level envoye: '.$level);

                        $Command = array(
                                         "setLevel" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "01",
                                         "Level" => $level,
                                         "duration" => $parameters['duration'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "moveToLiftAndTiltBSO":
                                      // Pour un get level (level de 0 à 255):
                         // a=0.00081872
                         // b=0.2171167
                         // c=-8.60201639
                         // level = level * level * a + level * b + c

                         $a = -0.8571429;
                         $b = 1.8571429;
                         $c = 0;

                         $fields = preg_split("/[=&]+/", $msg);
                         if (count($fields) > 1) {
                             $parameters = proper_parse_str( $msg );
                         }

                         // $level255 = intval($keywords[1] * 255 / 100);
                         // $this->deamonlog('debug', 'level255: '.$level255);

                         $inclinaisonSlider = $parameters['Inclinaison'];                // Valeur entre 0 et 100
                         // $this->deamonlog('debug', 'level Slider: '.$levelSlider);

                         $inclinaisonSliderPourcent = $inclinaisonSlider/100;    // Valeur entre 0 et 1

                         // $level = min( max( round( $level255 * $level255 * a + $level255 * $b + $c ), 0), 255);
                         $inclinaisonPourcent = $a*$inclinaisonSliderPourcent*$inclinaisonSliderPourcent+$b*$inclinaisonSliderPourcent+$c;
                         $inclinaison = $inclinaisonPourcent * 255;
                         $inclinaison = min( max( round( $inclinaison), 0), 255);

                         $this->deamonlog('debug', 'inclinaison Slider: '.$inclinaisonSlider.' inclinaison calcule: '.$inclinaisonPourcent.' inclinaison envoye: '.$inclinaison);

                         $Command = array(
                                          "moveToLiftAndTiltBSO" => "1",
                                          "addressMode" => "02",
                                          "priority" => $priority,
                                          "dest" => $dest,
                                          "address" => $address,
                                          "destinationEndpoint" => "01",
                                          "inclinaison" => $inclinaison,
                                          "duration" => $parameters['duration'],
                                          );
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelStop":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setLevelStop" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "sourceEndpoint" => "01",
                                         "destinationEndpoint" => "01",
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelStopHue":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setLevelStop" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "sourceEndpoint" => "01",
                                         "destinationEndpoint" => "0B",
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelHue":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setLevel" => "1",
                                         "addressMode" => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "0B",
                                         "Level" => intval($keywords[1] * 255 / 100),
                                         "duration" => $keywords[3],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setLevelGroup":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setLevel" => "1",
                                         "addressMode" => "01",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "destinationEndpoint" => "01",
                                         "Level" => intval($keywords[1] * 255 / 100),
                                         "duration" => $keywords[3],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setColour":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                            $Command = array(
                                             "setColour"            => "1",
                                             "addressMode"          => "02",
                                             "priority" => $priority,
                                             "dest" => $dest,
                                             "address"              => $address,
                                             "X"                    => $parameters['X'],
                                             "Y"                    => $parameters['Y'],
                                             "destinationEndPoint"  => $parameters['EP'],
                                             );
                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "setColourGroup":
                            $fields = preg_split("/[=&]+/", $msg);
                            if (count($fields) > 1) {
                                $parameters = proper_parse_str( $msg );
                                $Command = array(
                                                 "setColour"            => "1",
                                                 "addressMode"          => "01",
                                                 "priority"             => $priority,
                                                 "dest"                 => $dest,
                                                 "address"              => $address,
                                                 "X"                    => $parameters['X'],
                                                 "Y"                    => $parameters['Y'],
                                                 "destinationEndPoint"  => "01", // not needed as group
                                                 );
                            }
                            break;
                        //----------------------------------------------------------------------------
                    case "setColourRGB":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                            if (strlen($parameters['color']) == 7) {
                                $parameters['color'] = substr($parameters['color'], 1);
                            }
                        }

                        // Si message vient de Abeille alors le parametre est: RRVVBB
                        // Si le message vient de Homebridge: {"color":"#00FF11"}, j'extrais la partie interessante.


                        $rouge = hexdec(substr($parameters['color'],0,2));
                        $vert  = hexdec(substr($parameters['color'],2,2));
                        $bleu  = hexdec(substr($parameters['color'],4,2));

                        $this->deamonlog( 'debug', "msg: ".$msg." rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/colorRouge', $rouge*100/255      );
                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/colorVert',  $vert*100/255       );
                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/colorBleu',  $bleu*100/255       );
                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, 'Abeille/'.$address.'/ColourRGB',  $parameters['color']);

                        $Command = array(
                                         "setColourRGB" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "R" => $rouge,
                                         "G" => $vert,
                                         "B" => $bleu,
                                         "destinationEndPoint" => $parameters['EP'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setRouge":
                        $abeille = Abeille::byLogicalId( $dest.'/'.$address,'Abeille');

                        $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                        $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                        $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                        $this->deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        if ( $rouge=="" ) { $rouge = 1;   }
                        if ( $vert=="" )  { $vert = 1;    }
                        if ( $bleu=="" )  { $bleu = 1;    }
                        $this->deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/colorRouge', $msg );

                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "setColourRGB" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "R" => $parameters['color']/100*255,
                                         "G" => $vert/100*255,
                                         "B" => $bleu/100*255,
                                         "destinationEndPoint" => $parameters['EP'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setVert":
                        $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                        $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                        $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                        $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                        $this->deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        if ( $rouge=="" ) { $rouge = 1;   }
                        if ( $vert=="" )  { $vert = 1;    }
                        if ( $bleu=="" )  { $bleu = 1;    }
                        $this->deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/colorVert', $msg );

                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "setColourRGB" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "R" => $rouge/100*255,
                                         "G" => $parameters['color']/100*255,
                                         "B" => $bleu/100*255,
                                         "destinationEndPoint" => $parameters['EP'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setBleu":
                        $abeille = Abeille::byLogicalId($dest.'/'.$address,'Abeille');

                        $rouge  = $abeille->getCmd('info', 'colorRouge')->execCmd();
                        $vert   = $abeille->getCmd('info', 'colorVert')->execCmd();
                        $bleu   = $abeille->getCmd('info', 'colorBleu')->execCmd();
                        $this->deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        if ( $rouge=="" ) { $rouge = 1;   }
                        if ( $vert=="" )  { $vert = 1;    }
                        if ( $bleu=="" )  { $bleu = 1;    }
                        $this->deamonlog( 'debug', "rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        $this->publishMosquittoAbeile( queueKeyCmdToAbeille, $dest.'/'.$address.'/colorBleu', $msg );

                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "setColourRGB" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "R" => $rouge/100*255,
                                         "G" => $vert/100*255,
                                         "B" => $parameters['color']/100*255,
                                         "destinationEndPoint" => $parameters['EP'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setColourHue":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setColour" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "X" => $keywords[1],
                                         "Y" => $keywords[3],
                                         "destinationEndPoint" => "0B",
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setColourOSRAM":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "setColour" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "X" => $keywords[1],
                                         "Y" => $keywords[3],
                                         "destinationEndPoint" => "03",
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setTemperature":
                        // T°K   Hex sent  Dec eq
                        // 2200	 01C6	   454
                        // 2700	 0172	   370
                        // 4000	 00FA	   250
                        // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                        $this->deamonlog( 'debug', 'msg: ' . $msg );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $temperatureK = $parameters['slider'];
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureK );
                        $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = dechex( $temperatureConsigne );
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                        $Command = array(
                                         "setTemperature"       => "1",
                                         "addressMode"          => "02",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"              => $address,
                                         "temperature"          => $temperatureConsigne,
                                         "destinationEndPoint"  => $parameters['EP'],
                                         );

                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/Temperature-Light', $temperatureK );
                        break;
                        //----------------------------------------------------------------------------
                    case "setTemperatureGroup":
                        // T°K   Hex sent  Dec eq
                        // 2200     01C6       454
                        // 2700     0172       370
                        // 4000     00FA       250
                        // De ces nombres on calcule l'equation: Y = -0,113333333 * X + 703,3333333
                        $this->deamonlog( 'debug', 'msg: ' . $msg );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $temperatureK = $parameters['slider'];
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureK );
                        $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = dechex( $temperatureConsigne );
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                        $this->deamonlog( 'debug', 'temperatureConsigne: ' . $temperatureConsigne );
                        $Command = array(
                                         "setTemperature"       => "1",
                                         "addressMode"          => "01",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"              => $address,
                                         "temperature"          => $temperatureConsigne,
                                         "destinationEndPoint"  => $parameters['EP'],
                                         );

                        $this->publishMosquittoAbeille( queueKeyCmdToAbeille, $dest.'/'.$address.'/Temperature-Light', $temperatureK );
                        break;
                        //----------------------------------------------------------------------------
                    case "sceneGroupRecall":
                        // a revoir completement
                        $this->deamonlog( 'debug', 'sceneGroupRecall msg: ' . $msg );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "sceneGroupRecall"         => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         // "address"                  => $parameters['groupID'],   // Ici c est l adresse du group.

                                         // "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         // "DestinationEndPoint"      => "ff",
                                         // "groupID"                  => $parameters['groupID'],
                                         "groupID"                  => $parameters['groupID'],
                                         "sceneID"                  =>  $parameters['sceneID'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "Management_LQI_request":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "Management_LQI_request" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $keywords[1],
                                         "StartIndex" => $keywords[3],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "IEEE_Address_request":
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "IEEE_Address_request" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "shortAddress" => $keywords[1],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "identifySend": // identifySend KIWI1
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        $Command = array(
                                         "identifySend" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "duration" => $parameters['duration'],
                                         "DestinationEndPoint" => $parameters['EP'],
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "identifySendHue": // identifySendHue KIWI2
                        $keywords = preg_split("/[=&]+/", $msg);
                        $Command = array(
                                         "identifySend" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         "duration" => "0010", // $keywords[1]
                                         "DestinationEndPoint" => "0B",
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "getGroupMembership":
                        $Command = array(
                                         "getGroupMembership" => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address" => $address,
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "bindShort":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        $Command = array(
                                         "bindShort"                => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $address,
                                         "targetExtendedAddress"    => $parameters['targetExtendedAddress'],
                                         "targetEndpoint"           => $parameters['targetEndpoint'],
                                         "clusterID"                => $parameters['ClusterId'],
                                         "destinationAddress"       => $parameters['reportToAddress'],
                                         "destinationEndpoint"      => "01",
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setReport":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "setReport"                => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"                  => $address,
                                         "targetEndpoint"           => $parameters['targetEndpoint'],
                                         "ClusterId"                => $parameters['ClusterId'],
                                         "AttributeType"            => $parameters['AttributeType'],
                                         "AttributeId"              => $parameters['AttributeId'],
                                         "MinInterval"              => str_pad(dechex($parameters['MinInterval']),4,0,STR_PAD_LEFT),
                                         "MaxInterval"              => str_pad(dechex($parameters['MaxInterval']),4,0,STR_PAD_LEFT),
                                         );
                        break;
                        //----------------------------------------------------------------------------
                    case "setReportSpirit":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "setReport"                => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"                  => $address,
                                         "targetEndpoint"           => $parameters['targetEndpoint'],
                                         "ClusterId"                => $parameters['ClusterId'],
                                         "AttributeType"            => $parameters['AttributeType'],
                                         "AttributeId"              => $parameters['AttributeId'],
                                         "MinInterval"              => str_pad(dechex($parameters['MinInterval']),4,0,STR_PAD_LEFT),
                                         "MaxInterval"              => str_pad(dechex($parameters['MaxInterval']),4,0,STR_PAD_LEFT),
                                         );
                        break;

                        //----------------------------------------------------------------------------
                    case "WindowsCovering":
                        $fields = preg_split("/[=&]+/", $msg);
                          if (count($fields) > 1) {
                              $parameters = proper_parse_str( $msg );
                          }

                        $Command = array(
                                         "WindowsCovering"          => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"                  => $address,
                                         "clusterCommand"           => $parameters['clusterCommand'],
                        );
                        break;
                        //----------------------------------------------------------------------------
                        case "WindowsCoveringGroup":
                        $fields = preg_split("/[=&]+/", $msg);
                          if (count($fields) > 1) {
                              $parameters = proper_parse_str( $msg );
                          }

                        $Command = array(
                                         "WindowsCoveringGroup"     => "1",
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         "address"                  => $address,
                                         "clusterCommand"           => $parameters['clusterCommand'],
                        );
                        break;
                        //----------------------------------------------------------------------------
                        case "writeAttributeRequestIAS_WD":
                        $fields = preg_split("/[=&]+/", $msg);
                          if (count($fields) > 1) {
                              $parameters = proper_parse_str( $msg );
                          }

                        $Command = array(
                                         "writeAttributeRequestIAS_WD"     => "1",
                                         "priority"                        => $priority,
                                         "dest"                            => $dest,
                                         "address"                         => $address,
                                         "mode"                            => $parameters['mode'],
                                         "duration"                        => $parameters['duration'],
                        );
                        break;

                        //----------------------------------------------------------------------------

                    default:
                        $this->deamonlog('warning', 'AbeilleCommand unknown: '.$action );
                        break;
                } // switch
            } // if $address != "Ruche"
            else { // $address == "Ruche"
                $done = 0;
                // $this->deamonlog("debug", 'procmsg fct - Pour La Ruche - (Ln: '.__LINE__.')' );
                // Crée les variables dans la chaine et associe la valeur.
                $fields = preg_split("/[=&]+/", $msg);
                if (count($fields) > 1) {
                    $parameters = proper_parse_str( $msg );
                }

                switch ($action) {
                    case "ReadAttributeRequest":
                        $Command = array(
                                         "ReadAttributeRequest" => "1",
                                         "priority"     => $priority,
                                         "dest"         => $dest,
                                         "address"      => $parameters['address'],
                                         "clusterId"    => $parameters['clusterId'],
                                         "attributeId"  => $parameters['attributId'],
                                         "Proprio"      => $parameters['Proprio'],
                                         "EP"           => $parameters['EP'],
                                         );

                        $this->deamonlog('debug', 'Msg Received: '.$msg.' from Ruche');
                        $done = 1;
                        break;

                    case "bindShort":
                        $Command = array(
                                         "bindShort"                => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "targetExtendedAddress"    => $parameters['targetExtendedAddress'],
                                         "targetEndpoint"           => $parameters['targetEndpoint'],
                                         "clusterID"                => $parameters['ClusterId'],
                                         "destinationAddress"       => $parameters['reportToAddress'],
                                         "destinationEndpoint"      => "01",
                                         );
                        $done = 1;
                        break;

                    case "setReport":
                        if ( !isset($parameters['targetEndpoint']) )    { $parameters['targetEndpoint'] = "01"; }
                        if ( !isset($parameters['MaxInterval']) )       { $parameters['MaxInterval']    = "0"; }
                        $Command = array(
                                         "setReport"                => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "targetEndpoint"           => $parameters['targetEndpoint'],
                                         "ClusterId"                => $parameters['ClusterId'],
                                         "AttributeType"            => $parameters['AttributeType'],
                                         "AttributeId"              => $parameters['AttributeId'],
                                         "MaxInterval"              => str_pad(dechex($parameters['MaxInterval']),4,0,STR_PAD_LEFT),
                                         );
                        $done = 1;
                        break;

                    case "getGroupMembership":
                        if ( $parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
                        if ( strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "getGroupMembership"       => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         );
                        $done = 1;
                        break;

                    case "addGroup":
                        if ( $parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
                        if ( strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "addGroup"                 => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupAddress"             => $parameters['groupAddress'],
                                         );
                        $done = 1;
                        break;

                    case "removeGroup":
                        if ( $parameters['address']=="Ruche" ) { $parameters['address'] = "0000"; }
                        if ( strlen($parameters['DestinationEndPoint'])<2 ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "removeGroup"              => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupAddress"             => $parameters['groupAddress'],
                                         );
                        $done = 1;
                        break;

                        // Scene -----------------------------------------------------------------------------------------------

                    case "viewScene":
                        if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "viewScene"                => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupID"                  => $parameters['groupID'],
                                         "sceneID"                  => $parameters['sceneID'],
                                         );
                        $done = 1;
                        break;

                    case "storeScene":
                        if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "storeScene"               => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupID"                  => $parameters['groupID'],
                                         "sceneID"                  => $parameters['sceneID'],
                                         );
                        $done = 1;
                        break;

                    case "recallScene":
                        if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "recallScene"              => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupID"                  => $parameters['groupID'],
                                         "sceneID"                  => $parameters['sceneID'],
                                         );
                        $done = 1;
                        break;

                    case "sceneGroupRecall":
                        $this->deamonlog( 'debug', 'sceneGroupRecall msg: ' . $msg );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $Command = array(
                                         "sceneGroupRecall"         => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         // "address"                  => $parameters['groupID'],   // Ici c est l adresse du group.

                                         // "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         // "DestinationEndPoint"      => "ff",
                                         // "groupID"                  => $parameters['groupID'],
                                         "groupID"                  => $parameters['groupID'],
                                         "sceneID"                  =>  $parameters['sceneID'],
                                         );
                        $done = 1;
                        break;

                    case "addScene":
                        if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "addScene"                 => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupID"                  => $parameters['groupID'],
                                         "sceneID"                  => $parameters['sceneID'],
                                         "sceneName"                => $parameters['sceneName'],
                                         );
                        $done = 1;
                        break;

                    case "getSceneMembership":
                        if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "getSceneMembership"       => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupID"                  => $parameters['groupID'],
                                         );
                        $done = 1;
                        break;

                    case "removeSceneAll":
                        if ( !isset($parameters['DestinationEndPoint']) ) { $parameters['DestinationEndPoint'] = "01"; }
                        $Command = array(
                                         "removeSceneAll"           => "1",
                                         "priority"                 => $priority,
                                         "dest"                     => $dest,
                                         "address"                  => $parameters['address'],
                                         "DestinationEndPoint"      => $parameters['DestinationEndPoint'],
                                         "groupID"                  => $parameters['groupID'],
                                         );
                        $done = 1;
                        break;
                } // switch action

                //  -----------------------------------------------------------------------------------------------
                if ( !$done ) {
                    $keywords = preg_split("/[=&]+/", $msg);

                    // Si une string simple
                    if (count($keywords) == 1) {
                        $Command = array(
                                         $action => $msg,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         );
                    } // Si une command type get http param1=value1&param2=value2
                    if (count($keywords) == 2) {
                        // $this->deamonlog('debug', '2 arguments command');
                        $Command = array(
                                         $action => $action,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         $keywords[0] => $keywords[1],
                                         );
                    }
                    if (count($keywords) == 4) {
                        // $this->deamonlog('debug', '4 arguments command');
                        $Command = array(
                                         $action => $action,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         );
                    }
                    if (count($keywords) == 6) {
                        // $this->deamonlog('debug', '6 arguments command');
                        $Command = array(
                                         $action => $action,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         $keywords[4] => $keywords[5],
                                         );
                    }
                    if (count($keywords) == 8) {
                        // $this->deamonlog('debug', '8 arguments command');
                        $Command = array(
                                         $action => $action,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         $keywords[4] => $keywords[5],
                                         $keywords[6] => $keywords[7],
                                         );
                    }
                    if (count($keywords) == 10) {
                        // $this->deamonlog('debug', '10 arguments command');
                        $Command = array(
                                         $action => $action,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         $keywords[4] => $keywords[5],
                                         $keywords[6] => $keywords[7],
                                         $keywords[8] => $keywords[9],
                                         );
                    }
                    if (count($keywords) == 12) {
                        // $this->deamonlog('debug', '12 arguments command');
                        $Command = array(
                                         $action => $action,
                                         "priority" => $priority,
                                         "dest" => $dest,
                                         $keywords[0] => $keywords[1],
                                         $keywords[2] => $keywords[3],
                                         $keywords[4] => $keywords[5],
                                         $keywords[6] => $keywords[7],
                                         $keywords[8] => $keywords[9],
                                         $keywords[10] => $keywords[11],
                                         );
                    }

                }
            }

            /*---------------------------------------------------------*/

            if ( $this->debug['procmsg'] ) $this->deamonlog('debug','procmsg fct - calling processCmd with Command parameters: '.json_encode($Command));
            $this->processCmd( $Command );

            return;
        }

        /* Treat Zigate statuses (0x8000 cmd) coming from parser */
        function traiteLesAckRecus() {

            $msg_type = NULL;
            $msg = NULL;
            $max_msg_size = 512;

            if ( !msg_receive($this->queueKeyParserToCmdSemaphore, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT) ) return; // Si pas de message je passe mon chemin

            $i = str_replace( 'Abeille', '', $msg['dest'] );

            if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "traiteLesAckRecus fct - *************" );
            if (isset($this->statusText[$msg['status']])) {
                // if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "Message 8000 status recu: ".$msg['status']."->".$this->statusText[$msg['status']]." cmdAck: ".json_encode($msg) . " alors que j ai ".count($this->cmdQueue)." message(s) en attente: ".json_encode($this->cmdQueue));
                if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "traiteLesAckRecus fct - Message 8000 status recu: ".$msg['status']." -> ".$this->statusText[$msg['status']]." cmdAck: ".json_encode($msg) . " alors que j ai ".count($this->cmdQueue[$i])." message(s) en attente: " );
            }
            else {
                if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "traiteLesAckRecus fct - Message 8000 status recu: ".$msg['status']."->Code Inconnu cmdAck: ".json_encode($msg) . " alors que j ai ".count($this->cmdQueue[$i])." message(s) en attente: ".json_encode($this->cmdQueue[$i]));
            }

            // [2019-10-31 13:17:37][AbeilleCmd][debug]Message 8000 status recu, cmdAck: {"type":"8000","status":"00","SQN":"b2","PacketType":"00fa"}
            // type: 8000 : message status en retour d'une commande envoyée à la zigate
            // status: 00 : Ok commande bien recue par la zigate / 15: ???
            // SQN semble s'incrementer à chaque commande
            // PacketType semble est la commande envoyée ici 00fa est une commande store (windows...)

            // $cmd = array_slice( $this->cmdQueue, 0, 1 ); // je recupere une copie du premier élément de la queue

            // if ( ($msg['status'] == "00") || ($msg['status'] == "01") || ($msg['status'] == "05") || ($cmd[0]['retry'] <= 0 ) ) {
            // ou si retry tombe à 0
            if ( ($msg['status'] == "00") || ($msg['status'] == "01") || ($msg['status'] == "05") ) {
                // Je vire la commande si elle est bonne
                // ou si elle est incorrecte
                // ou si conf alors que stack demarrée
                array_shift( $this->cmdQueue[$i] ); // Je vire la commande
                $this->zigateAvailable[$i] = 1;      // Je dis que la Zigate est dispo
                $this->timeLastAck[$i] = 0;

                // Je tri la queue pour preparer la prochaine commande
                if ( $this->debug['traiteLesAckRecus'] ) { $this->deamonlog("debug", "traiteLesAckRecus fct - J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres drop commande: ".json_encode($this->cmdQueue[$i]) ); }


                 // J'en profite pour ordonner la queue pour traiter les priorités
                 // https://www.php.net/manual/en/function.array-multisort.php
                 if ( count($this->cmdQueue[$i]) > 1 ) {
                     $retry      = array_column( $this->cmdQueue[$i],'retry'   );
                     $prio       = array_column( $this->cmdQueue[$i],'priority');
                     $received   = array_column( $this->cmdQueue[$i],'received');
                     array_multisort( $retry, SORT_DESC, $prio, SORT_ASC, $received, SORT_ASC, $this->cmdQueue[$i] );
                 }

                 if ( $this->debug['traiteLesAckRecus'] ) { $this->deamonlog("debug", "traiteLesAckRecus fct - J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres tri          : ".json_encode($this->cmdQueue[$i]) ); }
            }
            else {
                $this->zigateAvailable[$i] = 0;      // Je dis que la Zigate n est pas dispo
                $this->timeLastAck[$i] = time();    // Je garde la date de ce mauvais Ack
            }
            // if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "J'ai ".count($this->cmdQueue)." commande(s) pour la zigate apres reception de ce Ack: ".json_encode($this->cmdQueue) );
            if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "traiteLesAckRecus fct - J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres reception de ce Ack" );
            if ( $this->debug['traiteLesAckRecus'] ) $this->deamonlog("debug", "traiteLesAckRecus fct - *************" );
        }

        function timeOutSurLesAck() {
            for ($i=1; $i<=$this->zigateNb; $i++) {
                if ( $this->zigateAvailable[$i] == 1 ) continue;    // La zigate est dispo donc on ne regarde pas les timeout
                if ( $this->timeLastAck[$i] == 0 ) continue;        // TimeOut deja arrivé et pas de Ack depuis
                $now = time();
                $delta = $now-$this->timeLastAck[$i];
                if ( $delta > $this->timeLastAckTimeOut[$i] ) {
                    if ( $this->debug['sendCmdAck'] ) {$this->deamonlog("debug", "Je n'ai pas de Ack (Status) depuis ".$delta." secondes avec now = ".$now." et timeLastAck = ".$this->timeLastAck[$i] . " donc je considère la zigate dispo....."); }
                    $this->zigateAvailable[$i] = 1;
                    $this->timeLastAck[$i] = 0;
                }
            }
        }

        /* Collect & treat messages for cmd */
        function recupereTousLesMessagesVenantDesAutresThreads() {
            $msg_type = NULL;
            $msg = NULL;
            $max_msg_size = 512;
            $message= new MsgAbeille();

            $listQueue = array(
                               $this->queueKeyAbeilleToCmd,
                               $this->queueKeyParserToCmd,
                               $this->queueKeyCmdToCmd,
                               $this->queueKeyLQIToCmd,
                               $this->queueKeyXmlToCmd,
                               $this->queueKeyFormToCmd,
                               );

            // Recupere tous les messages venant des autres threads, les analyse et converti et met dans la queue cmdQueue
            foreach ( $listQueue as $queue) {
                if (msg_receive( $queue, 0, $msg_priority, $max_msg_size, $msg, true, MSG_IPC_NOWAIT)) {
                    if ( $this->debug['AbeilleCmdClass'] ) { $this->deamonlog("debug", "Message pulled from queue ".$this->getQueueName($queue).": ".$msg->message['topic']." -> ".$msg->message['payload']);}
                    $message->topic = $msg->message['topic'];
                    $message->payload = $msg->message['payload'];
                    $message->priority = $msg_priority;
                    $this->procmsg($message);
                    $msg_type = NULL;
                    $msg = NULL;
                }
                else {
                    // $this->deamonlog("debug", "Queue: ".$this->getQueueName($queue)." Pas de message");
                }
            }
        }
    }

    // ***********************************************************************************************
    // MAIN
    // ***********************************************************************************************
    // exemple d appel
    // php AbeilleCmd.php debug

    try {
        echo "Let s start\n";

        $last = 0;

        $AbeilleCmdL2 = new AbeilleCmdL2($argv[1]);
        // echo "AbeilleCmd construit\n";

        while ( true ) {
            /* Treat Zigate statuses (0x8000 cmd) coming from parser */
            $AbeilleCmdL2->traiteLesAckRecus();

            $AbeilleCmdL2->timeOutSurLesAck();

            // Traite toutes les commandes zigate en attente
            $AbeilleCmdL2->processCmdQueueToZigate();

            $AbeilleCmdL2->recupereTousLesMessagesVenantDesAutresThreads();

            // Recuperes tous les messages en attente sur timer
            $AbeilleCmdL2->execTempoCmdAbeille();

            /* Display queues status every 30sec */
            if ( (time() - $last) > 30 ) {
                $AbeilleCmdL2->afficheStatQueue();
                $last = time();
            }

            // Libère le CPU
            time_nanosleep(0, 10000000); // 1/100s
        }

    }
    catch (Exception $e) {
        $AbeilleCmdL2->deamonlog( 'debug', 'error: '. json_encode($e->getMessage()));
        $AbeilleCmdL2->deamonlog( 'info', 'Fin du script');
    }

    $AbeilleCmdL2->deamonlog( 'info', 'Fin du démon \'AbeilleCmd\'');
    unset($AbeilleCmdL2);
?>
