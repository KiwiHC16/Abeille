<?php
// Classes Heritage
// AbeilleCmd.php -> AbeilleCmdQueue(class) -> AbeilleCmdQueueFct(class) -> debug(class) -> AbeilleTools(class)
    
    include_once __DIR__.'/AbeilleCmdQueueFct.class.php';

    class AbeilleCmdQueue extends AbeilleCmdQueueFct {

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
        public $tempoMessageQueue;

        public $cmdQueue;                         // When a cmd is to be sent to the zigate we store it first, then try to send it if the cmdAck is low. Flow Control.
        public $timeLastAck = array();            // When I got the last Ack from Zigate
        public $timeLastAckTimeOut = array();     // x s secondes dans retour de la zigate, je considere qu'elle est ok de nouveau pour ne pas rester bloqué.
        public $maxRetry = maxRetryDefault;       // Abeille will try to send the message max x times

        public $zigateNb;
        public $zigateAvailable = array();        // Si on pense la zigate dispo ou non.

        function __construct($debugLevel) {
            /* Configuring log library to use 'logMessage()' */
            logSetConf("AbeilleCmd.log");

            $this->deamonlog("debug", "AbeilleCmdQueue constructor start", $this->debug["AbeilleCmdClass"]);
            $this->deamonlog("debug", "Recuperation des queues de messages", $this->debug["AbeilleCmdClass"]);

            $this->queueKeyAbeilleToCmd           = msg_get_queue(queueKeyAbeilleToCmd);
            $this->queueKeyParserToCmd            = msg_get_queue(queueKeyParserToCmd);
            $this->queueKeyCmdToCmd               = msg_get_queue(queueKeyCmdToCmd);
            $this->queueKeyCmdToAbeille           = msg_get_queue(queueKeyCmdToAbeille);
            $this->queueKeyLQIToCmd               = msg_get_queue(queueKeyLQIToCmd);
            $this->queueKeyXmlToCmd               = msg_get_queue(queueKeyXmlToCmd);
            $this->queueKeyFormToCmd              = msg_get_queue(queueKeyFormToCmd);
            $this->queueKeyParserToCmdSemaphore   = msg_get_queue(queueKeyParserToCmdSemaphore);

            $this->tempoMessageQueue               = array();

            $this->zigateNb = config::byKey('zigateNb', 'Abeille', '1', 1);
            for ($i = 1; $i <= $this->zigateNb; $i++) {
                $this->zigateAvailable[$i] = 1;
                $this->timeLastAck[$i] = 0;
                $this->timeLastAckTimeOut[$i] = 0;
            }

            $this->deamonlog("debug", "AbeilleCmdQueue constructor end", $this->debug["AbeilleCmdClass"]);
        }

        public function publishMosquitto( $queueKeyId, $priority, $topic, $payload ) {

            $queue = msg_get_queue($queueKeyId);

            $msgAbeille = new MsgAbeille;
            $msgAbeille->message['topic']   = $topic;
            $msgAbeille->message['payload'] = $payload;

            if (msg_send($queue, $priority, $msgAbeille, true, false)) {
                $this->deamonlog('debug', '(fct publishMosquitto) mesage: '.json_encode($msgAbeille).' added to queue : '.$queueKeyId, $this->debug['tempo']);
            } else {
                $this->deamonlog('debug', '(fct publishMosquitto) could not add message '.json_encode($msgAbeille).' to queue : '.$queueKeyId, $this->debug['tempo']);
            }
        }

        public function publishMosquittoAbeille( $queueKeyId, $topic, $payload ) {

             $queue = msg_get_queue($queueKeyId);

             $msgAbeille = new MsgAbeille;

             $msgAbeille->message['topic']   = $topic;
             $msgAbeille->message['payload'] = $payload;

             if (msg_send($queue, $msgAbeille, true, false)) {
                 $this->deamonlog('debug', '(fct publishMosquittoAbeille) mesage: '.json_encode($msgAbeille).' added to queue : '.$queueKeyId, $this->debug['tempo']);
             } else {
                 $this->deamonlog('debug', '(fct publishMosquittoAbeille) could not add message '.json_encode($msgAbeille).' to queue : '.$queueKeyId, $this->debug['tempo']);
             }
        }

        public function addTempoCmdAbeille($topic, $msg, $priority) {
            // TempoCmdAbeille1/Ruche/getVersion&time=123 -> msg

            list($topic, $param) = explode('&', $topic);
            $topic = str_replace( 'Tempo', '', $topic);

            list($timeTitle, $time) = explode('=', $param);

            $this->tempoMessageQueue[] = array( 'time'=>$time, 'priority'=>$priority, 'topic'=>$topic, 'msg'=>$msg );
            $this->deamonlog('debug', 'addTempoCmdAbeille - tempoMessageQueue: '.json_encode($this->tempoMessageQueue), $this->debug['tempo']);
            if (count($this->tempoMessageQueue) > 50 ) {
                $this->deamonlog('info', 'Il y a plus de 50 messages dans le queue tempo.' );
            }
        }

        public function execTempoCmdAbeille() {

            if (count($this->tempoMessageQueue) < 1) {
                return;
            }

            $now=time();
            foreach ($this->tempoMessageQueue as $key => $mqttMessage) {
                // deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue - 0: '.$mqttMessage[0] );
                if ($mqttMessage['time']<$now) {
                    $this->publishMosquitto( queueKeyCmdToCmd, $mqttMessage['priority'], $mqttMessage['topic'], $mqttMessage['msg']  );
                    $this->deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue - one less: -> '.json_encode($this->tempoMessageQueue[$key]), $this->debug['tempo']);
                    unset($this->tempoMessageQueue[$key]);
                    $this->deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue : '.json_encode($this->tempoMessageQueue), $this->debug['tempo']);
                }
            }
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

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);

            if ( isset($Command['repeat']) ) {
                if ( $Command['repeat']>1 ) {
                    for ($x = 2; $x <= $Command['repeat']; $x++) {
                        sleep(5);
                        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        function setParam2($dest,$address,$clusterId,$attributeId,$destinationEndPoint,$Param, $dataType, $proprio) {
            $this->deamonlog('debug', "  command setParam2");
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
            $this->deamonlog('debug', "data2: ".$data2 );
            $this->deamonlog('debug', "length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            // $this->deamonlog('debug', "data: ".$data );
            // $this->deamonlog('debug', "lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        function setParam3($dest,$Command) {
            // Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15

            $this->deamonlog('debug', "command setParam3");

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

            $this->deamonlog('debug', "data2: ".$data2 . " length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug', "data: ".$data );
            // $this->deamonlog('debug', "lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // Needed for fc01 of Legrand Dimmer
        // clusterId=fc01&attributeId=0000&attributeType=09&value=0101
        function setParam4($dest,$Command) {

            $this->deamonlog('debug', "command setParam4");

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

            $this->deamonlog('debug', "data2: ".$data2 . " length data2: ".$dataLength );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug', "data: ".$data );
            // $this->deamonlog('debug', "lenth data: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
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

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
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

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        function getChecksum( $msgtype, $length, $datas) {
            $temp = 0;
            $temp ^= hexdec($msgtype[0].$msgtype[1]) ;
            $temp ^= hexdec($msgtype[2].$msgtype[3]) ;
            $temp ^= hexdec($length[0].$length[1]) ;
            $temp ^= hexdec($length[2].$length[3]);

            for ($i=0;$i<=(strlen($datas)-2);$i+=2) {
                $temp ^= hexdec($datas[$i].$datas[$i+1]);
            }

            $this->deamonlog('debug',"getChecksum fct - msgtype: " . $msgtype . " length: " . $length . " datas: " . $datas . " strlen data: " . strlen($datas) . " checksum calculated: " . sprintf("%02X",$temp), $this->debug["Checksum"]);

            return sprintf("%02X",$temp);
        }

        function transcode($datas) {
            $this->deamonlog('debug','transcode fct - transcode data: '.$datas, $this->debug['transcode']);
            $mess="";

            if (strlen($datas)%2 !=0) return -1;

            for ($i=0;$i<(strlen($datas));$i+=2)
            {
                $byte = $datas[$i].$datas[$i+1];

                if (hexdec($byte)>=hexdec(10)) {
                    $mess.=$byte;
                } else {
                    $mess.="02".sprintf("%02X",(hexdec($byte) ^ 0x10));
                }
            }

            return $mess;
        }

        function sendCmd($priority, $dest, $cmd, $len, $datas='', $shortAddr="") {
            $this->deamonlog("debug", "sendCmd(".json_encode($dest).", cmd=".json_encode($cmd).", priority=".json_encode($priority).")", $this->debug['sendCmd']);

            $i = str_replace( 'Abeille', '', $dest );
            if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N', 1) == 'N' ) {
                $this->deamonlog("debug", "  Je ne traite pas cette commande car la zigate est desactivee." );
                return;
            }
            $this->deamonlog("debug", "  i: ".$i." key: ".config::byKey('AbeilleIEEE_Ok'.$i, 'Abeille', '-1', 1), $this->debug['sendCmd']);
            if (config::byKey('AbeilleIEEE_Ok'.$i, 'Abeille', '-1', 1) == '-1') {
                $this->deamonlog("debug", "  Je ne traite pas cette commande car la zigate ne semble pas etre sur le bon port tty." );
                return;
            }

            if ($dest == "none") {
                $this->deamonlog("debug", "  Je ne mets pas la commande dans la queue car la dest est none", $this->debug['sendCmd']);
                return; // on ne process pas les commande pour les zigate qui n existe pas.
            }

            if (is_null($priority)) {
                $this->deamonlog("debug", "  priority is null, rejecting the command", $this->debug['sendCmd']);
                return;
            }

            if ($priority < priorityMin) {
                $this->deamonlog("debug", "  priority out of range (rejecting the command): ".$priority, $this->debug['sendCmd']);
                return;
            }

            // A chaque retry la priority increase d'un.
            if ($priority > (priorityMax+priorityMax)) {
                $this->deamonlog("debug", "  priority out of range (rejecting the command): ".$priority, $this->debug['sendCmd']);
                return;
            }

            // received = time when the commande was added to the queue
            // time = when the commande was ssend to the zigate last time
            // retry = nombre de tentative restante
            // priority = priority du message

            if (($i > 0) && ($i <= maxNbOfZigate)) {
                $this->cmdQueue[$i][] = array(
                    'received' => microtime(true),
                    'time' => 0,
                    'retry' => $this->maxRetry,
                    'priority' => $priority,
                    'dest' => $dest,
                    'cmd' => $cmd,
                    'len' => $len,
                    'datas' => $datas,
                    'shortAddr' => $shortAddr
                );

                $this->deamonlog("debug", "  Je mets la commande dans la queue: ".$i." - Nb Cmd:".count($this->cmdQueue[$i])." -> ".json_encode($this->cmdQueue[$i]), $this->debug['sendCmd2']);
                if (count($this->cmdQueue[$i]) > 50) {
                    $this->deamonlog('info', '  Il y a plus de 50 messages dans le queue de la zigate: '.$i);
                }
            } else {
                $this->deamonlog("debug", "  Je recois un message pour une queue qui n est pas valide: ->".$i."<-", $this->debug['sendCmd']);
            }
        }

        function writeToDest($f, $dest, $cmd, $len, $datas) {
            fwrite($f,pack("H*","01"));
            fwrite($f,pack("H*",$this->transcode($cmd))); //MSG TYPE
            fwrite($f,pack("H*",$this->transcode($len))); //LENGTH
            if (!empty($datas)) {
                fwrite($f,pack("H*",$this->transcode($this->getChecksum($cmd,$len,$datas)))); //checksum
                fwrite($f,pack("H*",$this->transcode($datas))); //datas
            } else {
                fwrite($f,pack("H*",$this->transcode($this->getChecksum($cmd,$len,"00")))); //checksum
            }
            fwrite($f,pack("H*","03"));
        }

        function sendCmdToZigate($dest, $cmd, $len, $datas) {
            // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
            // SVP ne pas enlever ce code c est tres utile pour le debug et verifier les commandes envoyées sur le port serie.
            if (0) {
                $f = fopen("/var/www/html/log/toto","w");
                $this->writeToDest( $f, $dest, $cmd, $len, $datas);
                fclose($f);
            }

            $this->deamonlog('debug','sendCmdToZigate(Dest='.$dest.', cmd='.$cmd.', len='.$len.', datas='.$datas.")", $this->debug['sendCmd']);

            $i = str_replace( 'Abeille', '', $dest );
            $destSerial = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '1', 1);

            if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') == 'Y' ) {
                $this->deamonlog("debug", "  Envoi de la commande a la zigate: ".$destSerial.'-'.$cmd.'-'.$len.'-'.$datas, $this->debug['sendCmdToZigate']);
                $f = fopen( $destSerial,"w");
                $this->writeToDest($f, $destSerial, $cmd, $len, $datas);
                fclose($f);
            }
            else {
                $this->deamonlog("debug", "  Pas d envoi de la commande a la zigate (zigate inactive): ".$destSerial.'-'.$cmd.'-'.$len.'-'.$datas, $this->debug['sendCmdToZigate']);
            }
        }

        function afficheStatQueue() {
            $texteLog = "";
            if (isset($this->tempoMessageQueue)) {
                $texteLog .= "tempoMessageQueue: ".count( $this->tempoMessageQueue )." - ";
            }
            for ($i = 1; $i <= $this->zigateNb; $i++) {
                if (isset($this->cmdQueue[$i])) {
                    $texteLog .= "cmdQueue: ".$i." nb message: ".count( $this->cmdQueue[$i] )." - ";
                }
            }
            $this->deamonlog("debug", $texteLog );
        }

        function processCmdQueueToZigate() {

            for ($i = 1; $i <= $this->zigateNb; $i++) {
                if (!isset($this->cmdQueue[$i])) continue;                                     // si la queue n existe pas je passe mon chemin
                if (count($this->cmdQueue[$i]) < 1) continue;                                  // si la queue est vide je passe mon chemin
                if ($this->zigateAvailable[$i] == 0) continue;                                 // Si la zigate n est pas considéré dispo je passe mon chemin

                $this->deamonlog("debug", "processCmdQueueToZigate()", $this->debug['sendCmdAck2']);
                $this->deamonlog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate a envoyer.", $this->debug['sendCmdAck']);
                $this->deamonlog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate a envoyer: ".json_encode($this->cmdQueue[$i]), $this->debug['sendCmdAck2']);

                $this->zigateAvailable[$i] = 0;    // Je considere la zigate pas dispo car je lui envoie une commande
                $this->timeLastAck[$i] = time();

                $cmd = array_shift($this->cmdQueue[$i]);    // Je recupere la premiere commande
                $this->sendCmdToZigate( $cmd['dest'], $cmd['cmd'], $cmd['len'], $cmd['datas'] );    // J'envoie la premiere commande récupérée
                $cmd['retry']--;                        // Je reduis le nombre de retry restant
                $cmd['priority']++;                     // Je reduis la priorité
                $cmd['time'] = time();                    // Je mets l'heure a jour

                // Le nombre de retry n'est pas épuisé donc je remet la commande dans la queue
                if ($cmd['retry'] > 0) {
                    array_unshift($this->cmdQueue[$i], $cmd);  // Je remets la commande dans la queue avec l heure, prio++ et un retry -1
                } else {
                    $this->deamonlog("debug", "  La commande n a plus de retry, on la drop: ".json_encode($cmd), $this->debug['sendCmdAck2']);
                }

                $this->deamonlog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres envoie commande: ".json_encode($this->cmdQueue[$i]), $this->debug['sendCmdAck2']);
                // $this->deamonlog("debug", "--------------------", $this->debug['sendCmdAck2']);
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

            $this->deamonlog("debug", "procmsg(".json_encode($message).")");
            // $this->deamonlog("debug", "----------", $this->debug['procmsg2']);

            $topic      = $message->topic;
            $msg        = $message->payload;
            $priority   = $message->priority;

            if (sizeof(explode('/', $topic)) != 3) {
                $this->deamonlog("error", "procmsg(). Mauvais format de message reçu.");
                return ;
            }

            list ($type, $address, $action) = explode('/', $topic);

            if (preg_match("(^TempoCmd)", $type)) {
                $this->deamonlog("debug", "  Ajoutons le message a queue Tempo.", $this->debug['procmsg2']);
                $this->addTempoCmdAbeille($topic, $msg, $priority);
                return;
            }

            if (!preg_match("(^Cmd)", $type)) {
                $this->deamonlog('warning', '  Msg Received: Type: {'.$type.'} <> Cmdxxxxx donc ce n est pas pour moi, no action.');
                return;
            }

            $dest = str_replace( 'Cmd', '',  $type );

            $this->deamonlog("debug", '  Msg Received: Topic: {'.$topic.'} => '.$msg, $this->debug['procmsg3']);
            $this->deamonlog("debug", '  (ln: '.__LINE__.') - Type: '.$type.' Address: '.$address.' avec Action: '.$action, $this->debug['procmsg3']);

            // Jai les CmdAbeille/Ruche et les CmdAbeille/shortAdress que je dois gérer un peu differement les uns des autres.

            if ($address != "Ruche") {
                $this->deamonlog("debug", '  Address != Ruche', $this->debug['procmsg3']);
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
                        if (strlen($msg) == 2) {
                            // $this->deamonlog('info', 'Preparation de la commande annonce pour EP');
                            $Command = array(
                                "ReadAttributeRequest" => "1",
                                "priority" => $priority,
                                "dest" => $dest,
                                "address" => $address,
                                "clusterId" => "0000",
                                "attributeId" => "0005",
                                "EP" => $msg,
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
                                    "EP" => "01",
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
                                    "EP" => "0B",
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
                                    "EP" => "03",
                                );
                            }
                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "AnnonceProfalux":
                        if ($msg == "Default") {
                            $this->deamonlog('info', 'Preparation de la commande annonce pour Profalux');
                            $Command = array(
                                "ReadAttributeRequest" => "1",
                                "priority" => $priority,
                                "dest" => $dest,
                                "address" => $address,
                                "clusterId" => "0000",
                                "attributeId" => "0010",
                                "EP" => "03",
                            );
                        }
                        break;
                        //----------------------------------------------------------------------------
                    case "OnOff":
                        if ($this->debug['procmsg3']) {
                            $this->deamonlog("debug", '  OnOff with dest: '.$dest);
                        }
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
                                "addressMode" => "02",
                                "priority" => $priority,
                                "dest" => $dest,
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
                        } elseif ($msg == "Off") {
                            $actionId = "00";
                        } elseif ($msg == "Toggle") {
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
                    case "OnOffOSRAM":
                        if ($msg == "On") {
                            $actionId = "01";
                        } elseif ($msg == "Off") {
                            $actionId = "00";
                        } elseif ($msg == "Toggle") {
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
                        } elseif ($msg == "Off") {
                            $actionId = "00";
                        } elseif ($msg == "Toggle") {
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
                    case "commandLegrand":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

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
                        } elseif ($msg == "Off") {
                            $actionId = "00";
                        } elseif ($msg == "Toggle") {
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
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str($msg);
                        }

                        if ($parameters['action'] == "On") {
                            $actionId = "01";
                        } elseif ($parameters['action'] == "Off") {
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
                        $this->deamonlog('debug', '  Msg Received: '.$msg);

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
                        $this->deamonlog('debug', '  Msg Received: '.$msg);

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
                        $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestHostFlag":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', '  Msg Received: '.$msg);

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
                        $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestTemperatureSpiritConsigne":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', '  Msg Received: '.$msg);

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
                        $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestValveSpiritConsigne":
                    case "WriteAttributeRequestTrvSpiritMode":
                    $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', '  Msg Received: '.$msg);

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
                        $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "WriteAttributeRequestActivateDimmer":
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        // $keywords = preg_split("/[=&]+/", $msg);
                        $this->deamonlog('debug', ' Msg Received: '.$msg);

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
                        $this->deamonlog('debug', '  Msg Received: '.$msg.' from NE');
                        break;
                        //----------------------------------------------------------------------------
                    case "ReadAttributeRequest":
                        $keywords = preg_split("/[=&]+/", $msg);
                        if (count($keywords) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }
                        $this->deamonlog('debug', '  Msg received: '.json_encode($msg).' from NE');
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
                        $this->deamonlog('debug', '  Msg analysed: '.json_encode($Command).' from NE');
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
                        $levelPourcent = $a * $levelSliderPourcent * $levelSliderPourcent + $b * $levelSliderPourcent + $c;
                        $level = $levelPourcent * 255;
                        $level = min(max(round($level), 0), 255);

                        $this->deamonlog('debug', '  level Slider: '.$levelSlider.' level calcule: '.$levelPourcent.' level envoye: '.$level);

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
                         
                         $fields = preg_split("/[=&]+/", $msg);
                         if (count($fields) > 1) {
                             $parameters = proper_parse_str( $msg );
                         }

                         $Command = array(
                                          "moveToLiftAndTiltBSO" => "1",
                                          "addressMode" => "02",
                                          "priority" => $priority,
                                          "dest" => $dest,
                                          "address" => $address,
                                          "destinationEndpoint" => "01",
                                          "inclinaison" => $parameters['Inclinaison'],        // Valeur entre 0 et 90
                                          "duration" => $parameters['duration'],              // FFFF to have max speed of tilt
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

                        $this->deamonlog( 'debug', "  msg: ".$msg." rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

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
                        $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        if ( $rouge=="" ) { $rouge = 1;   }
                        if ( $vert=="" )  { $vert = 1;    }
                        if ( $bleu=="" )  { $bleu = 1;    }
                        $this->deamonlog('debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu);

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
                        $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        if ( $rouge=="" ) { $rouge = 1;   }
                        if ( $vert=="" )  { $vert = 1;    }
                        if ( $bleu=="" )  { $bleu = 1;    }
                        $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

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
                        $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

                        if ( $rouge=="" ) { $rouge = 1;   }
                        if ( $vert=="" )  { $vert = 1;    }
                        if ( $bleu=="" )  { $bleu = 1;    }
                        $this->deamonlog( 'debug', "  rouge: ".$rouge." vert: ".$vert." bleu: ".$bleu );

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
                        $this->deamonlog( 'debug', '  msg: ' . $msg );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $temperatureK = $parameters['slider'];
                        $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureK );
                        $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                        $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = dechex( $temperatureConsigne );
                        $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                        $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
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
                        $this->deamonlog( 'debug', '  msg: ' . $msg );
                        $fields = preg_split("/[=&]+/", $msg);
                        if (count($fields) > 1) {
                            $parameters = proper_parse_str( $msg );
                        }

                        $temperatureK = $parameters['slider'];
                        $this->deamonlog( 'debug', ' temperatureConsigne: ' . $temperatureK );
                        $temperatureConsigne = intval(-0.113333333 * $temperatureK + 703.3333333);
                        $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = dechex( $temperatureConsigne );
                        $this->deamonlog( 'debug', ' temperatureConsigne: ' . $temperatureConsigne );
                        $temperatureConsigne = str_pad( $temperatureConsigne, 4, "0", STR_PAD_LEFT) ;
                        $this->deamonlog( 'debug', '  temperatureConsigne: ' . $temperatureConsigne );
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
                        $this->deamonlog( 'debug', '  sceneGroupRecall msg: ' . $msg );
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
                    case "setReportSpirit":
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
                        $this->deamonlog('warning', '  AbeilleCommand unknown: '.$action );
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

                        $this->deamonlog('debug', '  Msg Received: '.$msg.' from Ruche');
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
                        $this->deamonlog( 'debug', '  sceneGroupRecall msg: ' . $msg );
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

            $this->deamonlog('debug','  calling processCmd with Command parameters: '.json_encode($Command), $this->debug['procmsg']);
            $this->processCmd( $Command );

            return;
        }

        /* Treat Zigate statuses (0x8000 cmd) coming from parser */
        function traiteLesAckRecus() {
            $msg_type = NULL;
            $max_msg_size = 512;
            if (!msg_receive($this->queueKeyParserToCmdSemaphore, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT)) {
                return;
            }

            $i = str_replace('Abeille', '', $msg['dest']);

            $this->deamonlog("debug", "traiteLesAckRecus())", $this->debug['traiteLesAckRecus']);

            if ($this->debug['traiteLesAckRecus']) {
                if (isset($this->statusText[$msg['status']])) {
                    $this->deamonlog("debug", "  Message 8000 status recu: ".$msg['status']." -> ".$this->statusText[$msg['status']] . " cmdAck: " . json_encode($msg) . " alors que j ai " . count($this->cmdQueue[$i]) . " message(s) en attente: ");
                } else {
                    $this->deamonlog("debug", "  Message 8000 status recu: ".$msg['status']."->Code Inconnu cmdAck: ".json_encode($msg) . " alors que j ai ".count($this->cmdQueue[$i])." message(s) en attente: ".json_encode($this->cmdQueue[$i]));
                }
            }

            // [2019-10-31 13:17:37][AbeilleCmd][debug]Message 8000 status recu, cmdAck: {"type":"8000","status":"00","SQN":"b2","PacketType":"00fa"}
            // type: 8000 : message status en retour d'une commande envoyée à la zigate
            // status: 00 : Ok commande bien recue par la zigate / 15: ???
            // SQN semble s'incrementer à chaque commande
            // PacketType semble est la commande envoyée ici 00fa est une commande store (windows...)

            // $cmd = array_slice( $this->cmdQueue, 0, 1 ); // je recupere une copie du premier élément de la queue

            // if ( ($msg['status'] == "00") || ($msg['status'] == "01") || ($msg['status'] == "05") || ($cmd[0]['retry'] <= 0 ) ) {
            // ou si retry tombe à 0
            if (in_array($msg['status'], ['00', '01', '05'])) {
                // Je vire la commande si elle est bonne
                // ou si elle est incorrecte
                // ou si conf alors que stack demarrée
                /* TODO: How can we be sure ACK is for last cmd ? */
                array_shift( $this->cmdQueue[$i] ); // Je vire la commande
                $this->zigateAvailable[$i] = 1;      // Je dis que la Zigate est dispo
                $this->timeLastAck[$i] = 0;

                // Je tri la queue pour preparer la prochaine commande
                $this->deamonlog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres drop commande: ".json_encode($this->cmdQueue[$i]), $this->debug['traiteLesAckRecus']);

                // J'en profite pour ordonner la queue pour traiter les priorités
                // https://www.php.net/manual/en/function.array-multisort.php
                if (count($this->cmdQueue[$i]) > 1) {
                    $retry      = array_column( $this->cmdQueue[$i],'retry'   );
                    $prio       = array_column( $this->cmdQueue[$i],'priority');
                    $received   = array_column( $this->cmdQueue[$i],'received');
                    array_multisort( $retry, SORT_DESC, $prio, SORT_ASC, $received, SORT_ASC, $this->cmdQueue[$i] );
                }

                $this->deamonlog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres tri : ".json_encode($this->cmdQueue[$i]), $this->debug['traiteLesAckRecus']);
            } else {
                $this->zigateAvailable[$i] = 0;      // Je dis que la Zigate n est pas dispo
                $this->timeLastAck[$i] = time();    // Je garde la date de ce mauvais Ack
            }

            $this->deamonlog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres reception de ce Ack", $this->debug['traiteLesAckRecus']);
            // $this->deamonlog("debug", "traiteLesAckRecus fct - *************", $this->debug['traiteLesAckRecus']);
        }

        function timeOutSurLesAck() {
            for ($i = 1; $i <= $this->zigateNb; $i++) {
                // La zigate est dispo donc on ne regarde pas les timeout OU TimeOut deja arrivé et pas de Ack depuis
                if ($this->zigateAvailable[$i] == 1 || $this->timeLastAck[$i] == 0) {
                    continue;
                }
                $now = time();
                $delta = $now-$this->timeLastAck[$i];
                if ($delta > $this->timeLastAckTimeOut[$i]) {
                    $this->deamonlog("debug", "Je n'ai pas de Ack (Status) depuis ".$delta." secondes avec now = ".$now." et timeLastAck = ".$this->timeLastAck[$i] . " donc je considère la zigate dispo.....", $this->debug['sendCmdAck']);
                    $this->zigateAvailable[$i] = 1;
                    $this->timeLastAck[$i] = 0;
                }
            }
        }

        /* Collect & treat messages for cmd */
        function recupereTousLesMessagesVenantDesAutresThreads() {
            $msg_type = NULL; // Unused ?
            $msg = NULL;
            $max_msg_size = 512;
            $message = new MsgAbeille();

            $listQueue = array(
                $this->queueKeyAbeilleToCmd,
                $this->queueKeyParserToCmd,
                $this->queueKeyCmdToCmd,
                $this->queueKeyLQIToCmd,
                $this->queueKeyXmlToCmd,
                $this->queueKeyFormToCmd,
            );

            // Recupere tous les messages venant des autres threads, les analyse et converti et met dans la queue cmdQueue
            foreach ($listQueue as $queue) {
                if (msg_receive( $queue, 0, $msg_priority, $max_msg_size, $msg, true, MSG_IPC_NOWAIT)) {
                    $this->deamonlog("debug", "Message from ".$this->getQueueName($queue).": ".$msg->message['topic']." -> ".$msg->message['payload'], $this->debug['AbeilleCmdClass']);
                    $message->topic = $msg->message['topic'];
                    $message->payload = $msg->message['payload'];
                    $message->priority = $msg_priority;
                    $this->procmsg($message);
                    $msg_type = NULL;
                    $msg = NULL;
                } else {
                    // $this->deamonlog("debug", "Queue: ".$this->getQueueName($queue)." Pas de message");
                }
            }
        }
    }
?>
