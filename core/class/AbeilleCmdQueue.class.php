<?php
    // Classes Heritage
    // AbeilleCmd.php -> AbeilleCmdQueue(class) -> AbeilleCmdPrepare(class) -> AbeilleCmdProcess(class) -> debug(class) -> AbeilleTools(class)
    // AbeilleCmd.php: process pour l envoie des messages à la zigate
    // AbeilleCmdQueue: gere les queues d'envoie des messqges
    // AbeilleCmdPrepare: Prend la demande utilisateur et fait le mapping avec la commnande zigate
    // AbeilleCmdProcess: Encode la demande utilisateur en binaire pour la zigate
    // debug: Permet de definir les fonctions que l on veut dans les logs
    // Tools: Caisse a outils de fonctions.

    include_once __DIR__.'/AbeilleCmdPrepare.class.php';

    class AbeilleCmdQueue extends AbeilleCmdPrepare {

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

        function __construct($debugLevel='debug') {
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
            $this->deamonlog("debug", "      sendCmd(".json_encode($dest).", cmd=".json_encode($cmd).", data=".json_encode($datas).", len=".json_encode($len).", priority=".json_encode($priority).")", $this->debug['sendCmd']);

            $i = str_replace( 'Abeille', '', $dest );
            if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N', 1) == 'N' ) {
                $this->deamonlog("debug", "      Je ne traite pas cette commande car la zigate est desactivee." );
                return;
            }
            $this->deamonlog("debug", "  i: ".$i." key: ".config::byKey('AbeilleIEEE_Ok'.$i, 'Abeille', '-1', 1), $this->debug['sendCmd']);
            if (config::byKey('AbeilleIEEE_Ok'.$i, 'Abeille', '-1', 1) == '-1') {
                $this->deamonlog("debug", "      Je ne traite pas cette commande car la zigate ne semble pas etre sur le bon port tty." );
                return;
            }

            if ($dest == "none") {
                $this->deamonlog("debug", "      Je ne mets pas la commande dans la queue car la dest est none", $this->debug['sendCmd']);
                return; // on ne process pas les commande pour les zigate qui n existe pas.
            }

            if (is_null($priority)) {
                $this->deamonlog("debug", "      priority is null, rejecting the command", $this->debug['sendCmd']);
                return;
            }

            if ($priority < priorityMin) {
                $this->deamonlog("debug", "      priority out of range (rejecting the command): ".$priority, $this->debug['sendCmd']);
                return;
            }

            // A chaque retry la priority increase d'un.
            if ($priority > (priorityMax+priorityMax)) {
                $this->deamonlog("debug", "      priority out of range (rejecting the command): ".$priority, $this->debug['sendCmd']);
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

                $this->deamonlog("debug", "      Je mets la commande dans la queue: ".$i." - Nb Cmd:".count($this->cmdQueue[$i])." -> ".json_encode($this->cmdQueue[$i]), $this->debug['sendCmd2']);
                if (count($this->cmdQueue[$i]) > 50) {
                    $this->deamonlog('info', '      Il y a plus de 50 messages dans le queue de la zigate: '.$i);
                }
            } else {
                $this->deamonlog("debug", "      Je recois un message pour une queue qui n est pas valide: ->".$i."<-", $this->debug['sendCmd']);
            }
        }

        function writeToDest($f, $dest, $cmd, $len, $datas)
        {
            //
            if (get_resource_type($f)) {
                fwrite($f, pack("H*", "01"));
                fwrite($f, pack("H*", $this->transcode($cmd))); //MSG TYPE
                fwrite($f, pack("H*", $this->transcode($len))); //LENGTH
                if (!empty($datas)) {
                    fwrite($f, pack("H*", $this->transcode($this->getChecksum($cmd, $len, $datas)))); //checksum
                    fwrite($f, pack("H*", $this->transcode($datas))); //datas
                } else {
                    fwrite($f, pack("H*", $this->transcode($this->getChecksum($cmd, $len, "00")))); //checksum
                }
                fwrite($f, pack("H*", "03"));
            }
            else
            {
                $this->deamonlog("debug", "      $dest resource non accessible: $cmd non écrit");
                $this->deamonlog("error", "      $dest resource non accessible: $cmd non écrit");
            }
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
