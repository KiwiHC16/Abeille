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
    if (isset($dbgMonitorAddr) && ($dbgMonitorAddr != ""))
        include_once __DIR__.'/../php/AbeilleMonitor.php'; // Tracing monitor for debug purposes

    class AbeilleCmdQueue extends AbeilleCmdPrepare {

        // public $statusText = array(
        //     "00" => "Success",
        //     "01" => "Incorrect parameters",
        //     "02" => "Unhandled command",
        //     "03" => "Command failed",
        //     "04" => "Busy (Node is carrying out a lengthy operation and is currently unable to handle the incoming command)",
        //     "05" => "Stack already started (no new configuration accepted)",
        //     "15" => "ZPS_EVENT_ERROR Indicates that an error has occurred on the local node. The nature of the error is reported through the structure ZPS_tsAfErrorEvent - see Section 7.2.2.17. JN-UG-3113 v1.5 -> En gros pas de place pour traiter le message",
        // );

        // public $queueKeyAbeilleToCmd;
        // public $queueParserToCmd;
        public $queueParserToCmdMax;
        // public $$abQueues["xToCmd"];
        // public $queueKeyCmdToAbeille;
        // public $queueKeyLQIToCmd;
        // public $queueKeyXmlToCmd;
        // public $queueKeyFormToCmd;
        public $queueParserToCmdAck;
        public $queueParserToCmdAckMax;
        public $tempoMessageQueue;

        // public $cmdQueue = array(); // When a cmd is to be sent to the zigate we store it first, then try to send it if the cmdAck is low. Flow Control.
        // public $cmdQueueHigh = array(); // High prority commands
        // public $timeLastAck = array();            // When I got the last Ack from Zigate
        // public $timeLastAckTimeOut = array();     // x s secondes dans retour de la zigate, je considere qu'elle est ok de nouveau pour ne pas rester bloqué.
        public $maxRetry = maxRetryDefault; // Abeille will try to send the message max x times

        // public $zigateEnabled = array(); // 1=Zigate is enabled
        // public $zigateAvailable = array(); // 1=ready to received new cmd
        // public $zigateSentPri = array(); // Priority of last sent cmd
        // public $zigateNPDU = array(); // Last NPDU
        // public $zigateAPDU = array(); // Last APDU
        // public $zigateFw = array(); // FW minor version (ex: 0x321)
        // public $zigateHw = array(); // HW version: 1=v1, 2=v2

        public $zigates = array(); // All enabled zigates
        // public $statCmd = array();

        function __construct($debugLevel='debug') {
            cmdLog("debug", "AbeilleCmdQueue constructor start", $this->debug["AbeilleCmdClass"]);
            // cmdLog("debug", "Recuperation des queues de messages", $this->debug["AbeilleCmdClass"]);

            $abQueues = $GLOBALS['abQueues'];
            // $this->queueKeyAbeilleToCmd     = msg_get_queue(queueKeyAbeilleToCmd);
            // $this->queueParserToCmd         = msg_get_queue($abQueues["parserToCmd"]["id"]);
            // $this->queueParserToCmdMax      = $abQueues["parserToCmd"]["max"];
            // $this->$abQueues["xToCmd"]         = msg_get_queue($abQueues["xToCmd"]);
            // $this->queueKeyCmdToAbeille     = msg_get_queue(queueKeyCmdToAbeille);
            // $this->queueKeyLQIToCmd         = msg_get_queue($abQueues["LQIToCmd"]["id"]);
            // $this->queueKeyXmlToCmd         = msg_get_queue(queueKeyXmlToCmd);
            // $this->queueKeyFormToCmd        = msg_get_queue(queueKeyFormToCmd);
            $this->queueParserToCmdAck      = msg_get_queue($abQueues["parserToCmdAck"]["id"]);
            $this->queueParserToCmdAckMax   = $abQueues["parserToCmdAck"]["max"];
            $this->queueXToCmd              = msg_get_queue($abQueues["xToCmd"]["id"]);

            $this->tempoMessageQueue = array();

            // for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            //     $this->zigateEnabled[$zgId] = 0;
            //     if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') != 'Y')
            //         continue; // This Zigate is not enabled
            //     /* Tcharp38: This leads to problems when creating new beehive.
            //        Currently daemons should be restarted AFTER beehive created in Jeedom.
            //        Since not the case, AbeilleCmd consider new beehive disabled.
            //      */
            //     // $zigate = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            //     // if (!is_object($zigate))
            //     //     continue; // Probably deleted on Jeedom side.
            //     // if (!$zigate->getIsEnable())
            //     //     continue; // Zigate disabled

            //     $this->zigateEnabled[$zgId] = 1;
            //     $this->zigateAvailable[$zgId] = 1;
            //     // $this->timeLastAck[$zgId] = 0;
            //     // $this->timeLastAckTimeOut[$zgId] = 0;
            //     $this->zigateFw[$zgId] = 0;
            //     $this->zigateHw[$zgId] = 0;
            // }

            // Tcharp38: Ongoing. New way to define zigates status
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') != 'Y')
                    continue; // This Zigate is not enabled

                $zg = array();
                $zg['id'] = $zgId;
                $zg['enabled'] = 1;
                $zg['available'] = 1;
                $zg['hw'] = 0; // 1=v1, 2=v2
                $zg['fw'] = 0; // FW minor version (ex 0x321)
                $zg['nPDU'] = 0; // Last NDPU
                $zg['aPDU'] = 0; // Last APDU
                $zg['cmdQueue'] = array();
                $zg['cmdQueueHigh'] = array();
                $zg['sentPri'] = 0;
                $this->zigates[$zgId] = $zg;
            }

            cmdLog("debug", "AbeilleCmdQueue constructor end", $this->debug["AbeilleCmdClass"]);
        }

        // public function incStatCmd( $cmd ) {
        //     if ( isset($this->statCmd[$cmd]) ) {
        //         $this->statCmd[$cmd]++;
        //     }
        //     else {
        //         $this->statCmd[$cmd]=1;
        //     }
        //     cmdLog('debug', '      incStatCmd(): '.json_encode($this->statCmd) );
        // }

        public function publishMosquitto($queueId, $priority, $topic, $payload) {

            $queue = msg_get_queue($queueId);

            $msg = array();
            $msg['topic']   = $topic;
            $msg['payload'] = $payload;

            if (msg_send($queue, $priority, $msg, true, false)) {
                cmdLog('debug', '(fct publishMosquitto) mesage: '.json_encode($msg).' added to queue : '.$queueId, $this->debug['tempo']);
            } else {
                cmdLog('debug', '(fct publishMosquitto) could not add message '.json_encode($msg).' to queue : '.$queueId, $this->debug['tempo']);
            }
        }

        public function msgToAbeille($topic, $payload) {
            global $abQueues;
            $queueId = $abQueues['cmdToAbeille']['id'];
            $queue = msg_get_queue($queueId);

            $msg = array();
            $msg['topic']   = $topic;
            $msg['payload'] = $payload;

            if (msg_send($queue, 1, $msg, true, false)) {
                cmdLog('debug', 'msgToAbeille() mesage: '.json_encode($msg).' added to queue : '.$queueId, $this->debug['tempo']);
            } else {
                cmdLog('debug', 'msgToAbeille() could not add message '.json_encode($msg).' to queue : '.$queueId, $this->debug['tempo']);
            }
        }

        public function addTempoCmdAbeille($topic, $msg, $priority) {
            list($topic, $param) = explode('&', $topic);
            $topic = str_replace( 'Tempo', '', $topic);

            list($timeTitle, $time) = explode('=', $param);

            $this->tempoMessageQueue[] = array(
                'time' => $time,
                'priority' => $priority,
                'topic' => $topic,
                'msg' => $msg
            );
            cmdLog('debug', 'addTempoCmdAbeille - tempoMessageQueue: '.json_encode($this->tempoMessageQueue), $this->debug['tempo']);
            if (count($this->tempoMessageQueue) > 50 ) {
                cmdLog('info', 'Il y a plus de 50 messages dans le queue tempo.' );
            }
        }

        public function execTempoCmdAbeille() {
            global $abQueues;

            if (count($this->tempoMessageQueue) < 1) {
                return;
            }

            $now = time();
            foreach ($this->tempoMessageQueue as $key => $mqttMessage) {
                // deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue - 0: '.$mqttMessage[0] );
                if ($mqttMessage['time']<$now) {
                    $this->publishMosquitto($abQueues['xToCmd']['id'], $mqttMessage['priority'], $mqttMessage['topic'], $mqttMessage['msg']);
                    cmdLog('debug', 'execTempoCmdAbeille - tempoMessageQueue - one less: -> '.json_encode($this->tempoMessageQueue[$key]), $this->debug['tempo']);
                    unset($this->tempoMessageQueue[$key]);
                    cmdLog('debug', 'execTempoCmdAbeille - tempoMessageQueue : '.json_encode($this->tempoMessageQueue), $this->debug['tempo']);
                }
            }
        }

        function getChecksum($msgtype, $length, $datas) {
            $temp = 0;
            $temp ^= hexdec($msgtype[0].$msgtype[1]) ;
            $temp ^= hexdec($msgtype[2].$msgtype[3]) ;
            $temp ^= hexdec($length[0].$length[1]) ;
            $temp ^= hexdec($length[2].$length[3]);

            for ($i=0;$i<=(strlen($datas)-2);$i+=2) {
                $temp ^= hexdec($datas[$i].$datas[$i+1]);
            }

            cmdLog('debug',"getChecksum fct - msgtype: " . $msgtype . " length: " . $length . " datas: " . $datas . " strlen data: " . strlen($datas) . " checksum calculated: " . sprintf("%02X",$temp), $this->debug["Checksum"]);

            return sprintf("%02X",$temp);
        }

        function transcode($datas) {
            cmdLog('debug','transcode fct - transcode data: '.$datas, $this->debug['transcode']);
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

        /**
         * addCmdToQueue()
         *
         * put in the queue the cmd to be put on the serial link to the zigate
         *
         * @param priority  priority of the command in the queue
         * @param dest      zigate to address the command
         * @param cmd       cmd in hex format as per zigate API
         * @param len       len of the cmd
         * @param data      data of the cmd
         * @param addr ???
         *
         * @return  none
         */
        function addCmdToQueue2($priority = PRIO_NORM, $net = '', $cmd = '', $payload = '', $addr = '', $addrMode = null) {
            cmdLog("debug", "    addCmdToQueue2(pri=".$priority.", net=".$net.", cmd=".$cmd.", payload=".$payload.", addr=".$addr.")");

            $zgId = substr($net, 7);
            $zg = &$this->zigates[$zgId];

            // Checking min parameters
            if ($zg['enabled'] == 0) {
                cmdLog("debug", "      Zigate disabled. Ignoring command.");
                return;
            }
            if (config::byKey('AbeilleIEEE_Ok'.$zgId, 'Abeille', '-1', 1) == '-1') {
                cmdLog("debug", "      Zigate on wrong port. Ignoring command.");
                return;
            }
            if (!ctype_xdigit($cmd)) {
                cmdLog('error', '      ERROR: Invalid cmd. Not hexa ! ('.$cmd.')');
                return;
            }
            if (($payload != '') && !ctype_xdigit($payload)) {
                cmdLog('error', '      ERROR: Invalid payload. Not hexa ! ('.$payload.')');
                return;
            }

            // Ok. Checking payload length
            $len = strlen($payload);
            if ($len % 2) {
                cmdLog("debug", "      ERROR: Odd payload length => cmd IGNORED");
                return;
            }

            // $this->incStatCmd($cmd);

            $newCmd = array(
                // 'priority'  => $priority,
                'dest'      => $net,
                'addr'      => $addr, // For monitoring purposes
                'cmd'       => $cmd,
                'datas'     => $payload,
                'status'    => '', // '', 'SENT', '8000', '8012' or '8702', '8011'
                'try'       => $this->maxRetry + 1, // Number of retries if failed
                'sentTime'  => 0, // For lost cmds timeout
                'sqnAps'    => ''
            );
            if ($addrMode && ($zg['hw'] == 1) && ($zg['fw'] >= 0x31e))
                $newCmd['addrMode'] = $addrMode; // For flow control if v1 & FW >= 3.1e
            if ($priority == PRIO_HIGH)
                $queue = &$zg['cmdQueueHigh'];
            else
                $queue = &$zg['cmdQueue'];
            $queue[] = $newCmd;
            $queueSize = count($queue);
            if ($priority == PRIO_HIGH)
                cmdLog("debug", "      Added cmd to Zigate".$zgId." HIGH priority queue. QueueSize=".$queueSize, $this->debug['addCmdToQueue2']);
            else
                cmdLog("debug", "      Added cmd to Zigate".$zgId." normal priority queue. QueueSize=".$queueSize, $this->debug['addCmdToQueue2']);
            if ($queueSize > 50) {
                cmdLog('debug', '      WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue');
            }
        } // End addCmdToQueue2()

        // Find cmd corresponding to SQNAPS
        // Returns: array('status'=true/false, 'cmd'=cmd by ref)
        function getCmd($sqnAps, $zg) {
            $cmd = null;
            foreach ($zg['cmdQueueHigh'] as $idx => $cmd2) {
                if ($cmd2['sqnAps'] == $sqnAps) {
                    $cmd = &$cmd2;
                    break;
                }
            }
            if ($cmd == null) {
                foreach ($zg['cmdQueue'] as $idx => $cmd2) {
                    if ($cmd2['sqnAps'] == $sqnAps) {
                        $cmd = &$cmd2;
                        break;
                    }
                }
            }
            $ret = array(
                'status' => ($cmd != null) ? true : false,
                'cmd' => $cmd
            );
            return $ret;
        }

        function writeToDest($f, $port, $cmd, $datas) {
            $len = sprintf("%04X", strlen($datas) / 2);
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
                cmdLog("error", "    Port '$port' non accessible. Commande '$cmd' non écrite.");
            }
        }

        /**
         * sendCmdToZigate()
         *
         * connect to zigate and pass the commande on serial link
         *
         * @param dest  Zigbee network (ex: 'Abeille1')
         * @param cmd   zigate commande as per zigate API
         * @param data  data for the cmd
         *
         * @return none
         */
        function sendCmdToZigate($dest, $cmd, $datas) {
            // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
            // SVP ne pas enlever ce code c est tres utile pour le debug et verifier les commandes envoyées sur le port serie.
            if (0) {
                $f = fopen("/var/www/html/log/toto","w");
                $this->writeToDest($f, $dest, $cmd, $datas);
                fclose($f);
            }

            cmdLog('debug','sendCmdToZigate(Dest='.$dest.', cmd='.$cmd.', datas='.$datas.")", $this->debug['sendCmdToZigate']);

            $i = substr($dest, 7);
            $destSerial = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '1', 1);

            if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y') {
                cmdLog("debug", "  Zigate ".$i." (".$destSerial.") disabled => ignoring cmd ".$cmd.'-'.$datas);
                return;
            }

            // Note: Using file_exists() to avoid PHP warning when port issue.
            if (!file_exists($destSerial) || (($f = fopen($destSerial, "w")) == false)) {
                cmdLog("error", "  Port '$destSerial' non accessible. Commande '$cmd' non écrite.");
                return;
            }

            // cmdLog("debug", "  Writing to port ".$destSerial.': '.$cmd.'-'.$len.'-'.$datas, $this->debug['sendCmdToZigate']);
            $this->writeToDest($f, $destSerial, $cmd, $datas);
            fclose($f);
        }

        /* Display queues & zigate status.
           Called every 30sec. */
        function displayStatus() {
            $queuesTxt = "";
            $zgTxt = '';
            if (isset($this->tempoMessageQueue)) {
                $queuesTxt .= "tempo=".count( $this->tempoMessageQueue );
            }
            // for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            //     if (isset($this->cmdQueueHigh[$zgId]))
            //         $queuesTxt .= ", high[".$zgId."]=".count($this->cmdQueueHigh[$zgId]);
            //     if (isset($this->cmdQueue[$zgId]))
            //         $queuesTxt .= ", norm[".$zgId."]=".count($this->cmdQueue[$zgId]);
            // }
            foreach ($this->zigates as $zgId => $zg) {
                $queuesTxt .= ", high[".$zgId."]=".count($zg['cmdQueueHigh']);
                $queuesTxt .= ", norm[".$zgId."]=".count($zg['cmdQueue']);

                if ($zgTxt != "")
                    $zgTxt .= ", ";
                $zgTxt .= "zg".$zgId."=on";
                $available = $zg['available'] ? "yes" : "NO";
                $zgTxt .= "/".$available;
            }

            // for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            //     if ($zgTxt != "")
            //         $zgTxt .= ", ";
            //     $enabled = $this->zigateEnabled[$zgId] ? "on" : "OFF";
            //     $zgTxt .= "zg".$zgId."=".$enabled;
            //     if ($this->zigateEnabled[$zgId]) {
            //         $available = $this->zigateAvailable[$zgId] ? "yes" : "NO";
            //         $zgTxt .= "/".$available;
            //     }
            // }

            cmdLog("debug", "Status, queues : ".$queuesTxt);
            cmdLog("debug", "  Zg (on/avail): ".$zgTxt);
        }

        // function processZigateCmdQueues() {
        //     for ($i = 1; $i <= maxNbOfZigate; $i++) {
        //         if (!isset($this->cmdQueue[$i])) continue;      // si la queue n existe pas je passe mon chemin
        //         if (count($this->cmdQueue[$i]) < 1) continue;   // si la queue est vide je passe mon chemin
        //         if ($this->zigateAvailable[$i] == 0) continue;  // Si la zigate n est pas considéré dispo je passe mon chemin

        //         $pending = count($this->cmdQueue[$i]);
        //         cmdLog("debug", "processZigateCmdQueues(): zigate ".$i.", pending=".$pending, $this->debug['processZigateCmdQueues']);
        //         // cmdLog("debug", "  cmd=".json_encode($this->cmdQueue[$i]), $this->debug['processZigateCmdQueues']);

        //         $this->zigateAvailable[$i] = 0; // Je considere la zigate pas dispo car je lui envoie une commande
        //         $this->timeLastAck[$i] = time();

        //         $cmd = array_shift($this->cmdQueue[$i]);    // Je recupere la premiere commande
        //         $this->sendCmdToZigate($cmd['dest'], $cmd['cmd'], $cmd['len'], $cmd['datas']);    // J'envoie la premiere commande récupérée
        //         $cmd['retry']--;                        // Je reduis le nombre de retry restant
        //         $cmd['priority']++;                     // Je reduis la priorité
        //         $cmd['time'] = time();                    // Je mets l'heure a jour
        //         if (isset($GLOBALS["dbgMonitorAddr"]) && ($cmd['addr'] != "") && ($GLOBALS["dbgMonitorAddr"] != "") && !strncasecmp($cmd['addr'], $GLOBALS["dbgMonitorAddr"], 4))
        //             monMsgToZigate($cmd['addr'], $cmd['cmd'].'-'.$cmd['len'].'-'.$cmd['datas']); // Monitor this addr ?

        //         // Le nombre de retry n'est pas épuisé donc je remet la commande dans la queue
        //         if ($cmd['retry'] > 0) {
        //             array_unshift($this->cmdQueue[$i], $cmd);  // Je remets la commande dans la queue avec l heure, prio++ et un retry -1
        //         } else {
        //             cmdLog("debug", "  La commande n a plus de retry, on la drop: ".json_encode($cmd), 1);
        //         }

        //         cmdLog("debug", "  J'ai ".count($this->cmdQueue[$i])." commande(s) pour la zigate apres envoie commande: ".json_encode($this->cmdQueue[$i]), $this->debug['processZigateCmdQueues']);
        //         // cmdLog("debug", "--------------------", $this->debug['sendCmdAck']);
        //     }
        // }

        function processZigateCmdQueues() {
            // cmdLog("debug", "processZigateCmdQueues()");
// cmdLog('debug', '  zigates='.json_encode($this->zigates));
            foreach ($this->zigates as $zgId => $zg) {
                if ($zg['enabled'] == 0) continue; // Disabled
                if ($zg['available'] == 0) continue;  // Not free

                $zg = &$this->zigates[$zgId];
// cmdLog('debug', 'cmdQueue12='.json_encode($zg['cmdQueue']));

                // Anything to send for this zigate ?
                if (count($zg['cmdQueueHigh']) > 0) {
                    $queue = &$zg['cmdQueueHigh'];
                    $queuePri = PRIO_HIGH;
                } else if (count($zg['cmdQueue']) > 0) {
                    $queue = &$zg['cmdQueue'];
                    $queuePri = PRIO_NORM;
                } else
                    continue; // Nothing to send. Moving to next zigate.

                /* Additional flow control: to avoid zigate internal saturation.
                   If HW v1
                     If FW >= 3.1e, using NPDU/APDU regulation.
                     If FW <  3.1e, regulation based on max cmd per sec.
                   If HW v2
                     No flow control. Regulation based on max cmd per sec.
                 */
                // if ($this->zigateHw[$zgId] == 1) {
                //     if ($this->zigateFw[$zgId] >= 0x31E) {
                //         if ($this)
                //         if ($this->zigateAPDU[$zgId] > 2) {
                //             cmdLog('debug', 'processZigateCmdQueues(): APDU>2 => send delayed');
                //             continue; // Will retry later
                //         }
                //         if ($this->zigateNPDU[$zgId] > 7) {
                //             cmdLog('debug', 'processZigateCmdQueues(): NPDU>7 => send delayed');
                //             continue; // Will retry later
                //         }
                //     }
                // }

                // Ok let's get cmd and send it to Zigate
                // $cmd = array_shift($queue); // Get first cmd

                // $this->zigateAvailable[$zgId] = 0; // Zigate no longer free
                // $this->zigateSentPri[$zgId] = $queuePri;
                $zg['available'] = 0; // Zigate no longer free
                $zg['sentPri'] = $queuePri;

                $cmd = &$queue[0]; // Taking first cmd
                if ($cmd['status'] != '') {
cmdLog('debug', "  WARNING: Unexpected cmd status '".$cmd['status']."'");
cmdLog('debug', "  cmd=".json_encode($cmd));
                } else
                cmdLog('debug', "  cmd=".json_encode($cmd));
                $this->sendCmdToZigate($cmd['dest'], $cmd['cmd'], $cmd['datas']);

                $cmd['status'] = "SENT";
                $cmd['sentTime'] = time();
                $cmd['try']--;

                if (isset($GLOBALS["dbgMonitorAddr"]) && ($cmd['addr'] != "") && ($GLOBALS["dbgMonitorAddr"] != "") && !strncasecmp($cmd['addr'], $GLOBALS["dbgMonitorAddr"], 4))
                    monMsgToZigate($cmd['addr'], $cmd['cmd'].'-'.$cmd['datas']); // Monitor this addr ?
            }
        }

        /* Treat Zigate statuses (0x8000 cmd) coming from parser */
        // function processZigateAcks() {
        //     while (true) {
        //         $msg_type = NULL;
        //         $msgMax = $this->queueParserToCmdAckMax;
        //         if (msg_receive($this->queueParserToCmdAck, 0, $msg_type, $msgMax, $msg, false, MSG_IPC_NOWAIT, $errCode) == false) {
        //             if ($errCode != 42) // 42 = No message
        //                 logMessage("debug", "processZigateAcks() ERROR ".$errCode);
        //             return;
        //         }

        //         $msg = json_decode($msg, true);
        //         $zgId = str_replace('Abeille', '', $msg['dest']);
        //         cmdLog("debug", "processZigateAcks())", $this->debug['processZigateAcks']);

        //         if ($this->debug['processZigateAcks']) {
        //             if (isset($this->statusText[$msg['status']])) {
        //                 cmdLog("debug", "  Message 8000 status recu: ".$msg['status']." -> ".$this->statusText[$msg['status']] . " cmdAck: " . json_encode($msg) . " alors que j ai " . count($this->cmdQueue[$zgId]) . " message(s) en attente: ");
        //             } else {
        //                 cmdLog("debug", "  Message 8000 status recu: ".$msg['status']."->Code Inconnu cmdAck: ".json_encode($msg) . " alors que j ai ".count($this->cmdQueue[$zgId])." message(s) en attente: ".json_encode($this->cmdQueue[$i]));
        //             }
        //         }

        //         if (in_array($msg['status'], ['00', '01', '05'])) {
        //             // Je vire la commande si elle est bonne
        //             // ou si elle est incorrecte
        //             // ou si conf alors que stack demarrée
        //             /* TODO: How can we be sure ACK is for last cmd ? <- KiwiHC16: Aucune à ma connaissance, faille depuis le debut de mon point de vue.*/
        //             array_shift( $this->cmdQueue[$zgId] ); // Je vire la commande
        //             $this->zigateAvailable[$zgId] = 1;      // Je dis que la Zigate est dispo
        //             $this->timeLastAck[$zgId] = 0;

        //             // Je tri la queue pour preparer la prochaine commande
        //             cmdLog("debug", "  J'ai ".count($this->cmdQueue[$zgId])." commande(s) pour la zigate apres drop commande: ".json_encode($this->cmdQueue[$zgId]), $this->debug['processZigateAcks']);

        //             // J'en profite pour ordonner la queue pour traiter les priorités
        //             // https://www.php.net/manual/en/function.array-multisort.php
        //             if (count($this->cmdQueue[$zgId]) > 1) {
        //                 $retry      = array_column( $this->cmdQueue[$zgId],'retry'   );
        //                 $prio       = array_column($this->cmdQueue[$zgId], 'priority');
        //                 $received   = array_column( $this->cmdQueue[$zgId],'received');
        //                 array_multisort($retry, SORT_DESC, $prio, SORT_ASC, $received, SORT_ASC, $this->cmdQueue[$zgId]);
        //             }

        //             cmdLog("debug", "  J'ai ".count($this->cmdQueue[$zgId])." commande(s) pour la zigate apres tri : ".json_encode($this->cmdQueue[$zgId]), $this->debug['processZigateAcks']);
        //         } else {
        //             $this->zigateAvailable[$zgId] = 0;      // Je dis que la Zigate n est pas dispo
        //             $this->timeLastAck[$zgId] = time();    // Je garde la date de ce mauvais Ack
        //         }
        //     }

        //     cmdLog("debug", "  ".count($this->cmdQueue[$zgId])." remaining pending commands", $this->debug['processZigateAcks']);
        // }

        // Process 8000, 8012 or 8702 messages
        function processZigateAcks() {
            // cmdLog("debug", "processZigateAcks()");
            while (true) {
                $msgMax = $this->queueParserToCmdAckMax;
                if (msg_receive($this->queueParserToCmdAck, 0, $msgType, $msgMax, $msg, false, MSG_IPC_NOWAIT, $errCode) == false) {
                    if ($errCode == 7) {
                        msg_receive($this->queueParserToCmdAck, 0, $msgType, $msgMax, $msg, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        logMessage('debug', 'processZigateAcks() ERROR: msg TOO BIG ignored: '.json_encode($msg));
                    } else if ($errCode != 42) // 42 = No message
                        cmdLog("debug", "processZigateAcks() ERROR ".$errCode);
                    break;
                }

                $msg = json_decode($msg, true);
                $zgId = substr($msg['net'], 7);
// cmdLog("debug", "  LA=".json_encode($this->zigates));
                $zg = &$this->zigates[$zgId];
                // cmdLog("debug", "processZigateAcks(): type=".$msg['type'], $this->debug['processZigateAcks']);

                // Tcharp38 TODO: This msg should be not passed thru this queue.
                if ($msg['type'] == "8010") {
                    cmdLog("debug", "8010 msg: FwVersion=".$msg['major']."-".$msg['minor']);
                    if ($msg['major'] == '0005')
                        $zg['hw'] = 2;
                    else
                        $zg['hw'] = 1;
                    $zg['fw'] = hexdec($msg['minor']);
                    continue;
                }

                // TODO: To be revisited. Ex: 8000 then 8010 ignored
                // if (!count($zg['cmdQueue']) && !count($zg['cmdQueueHigh'])) {
                //     cmdLog("debug", $msg['type']." msg but empty cmd queues => ignored");
                //     continue;
                // }

                if (isset($msg['sqnAps']))
                    $sqnAps = $msg['sqnAps'];
                else
                    $sqnAps = "?";
                if (isset($msg['nPDU'])) {
                    $nPDU = $msg['nPDU'];
                    $zg['nPDU'] = hexdec($nPDU);
                } else
                    $nPDU = "?";
                if (isset($msg['aPDU'])) {
                    $aPDU = $msg['aPDU'];
                    $zg['aPDU'] = hexdec($aPDU);
                } else
                    $aPDU = "?";

                unset($removeCmd); // Unset in case set in previous msg
                if ($msg['type'] == "8000") {
                    $m = "Msg from parser: 8000: ".$msg['net'].", Status=".$msg['status'].", SQN=".$msg['sqn'].", SQNAPS=".$msg['sqnAps'].", PackType=".$msg['packetType'].", NPDU=".$nPDU.", APDU=".$aPDU;

                    if ($zg['sentPri'] == PRIO_HIGH)
                        $queue = &$zg['cmdQueueHigh'];
                    else
                        $queue = &$zg['cmdQueue'];
                    $queueSize = count($queue);
                    if ($queueSize > 0)
                        $cmd = &$queue[0];

                    // Checking sent cmd vs received ack misalignment
                    if (($queueSize == 0) || ($msg['packetType'] != $cmd['cmd'])) {
                        cmdLog("debug", $m." => ignored");
                        continue;
                    }

                    cmdLog("debug", $m);
                    if ($msg['status'] == "00") {
                        // Status is: success
                        $cmd['status'] = '8000';
                        if ($sqnAps == '00')
                            $removeCmd = true; // Cmd not transmitted on air => no ack to expect
                        else
                            $cmd['sqnAps'] = $sqnAps;
                    } else if (in_array($msg['status'], ['01', '02', '03', '05'])) {
                        // Status is: bad param, unhandled, failed (?), stack already started
                        // $cmd['status'] = '8000';
                        $removeCmd = true;
                    } else {
                        // Something failed
                        if ($cmd['try'] == 0) {
                            cmdLog("debug", "  WARNING: Something failed and too many retries.");
                            $removeCmd = true;
                        } else {
                            cmdLog("debug", "  WARNING: Something failed. Cmd will be retried ".$cmd['try']." time(s) max.");
                        }
                    }
                } else {
                    // 8011, 8012 or 8702
                    $c = $this->getCmd($msg['sqnAps'], $zg); // Return is array
                    if ($c['status'] == false)
                        $cmd = null;
                    else
                        $cmd = $c['cmd'];

                    if ($msg['type'] == "8011") {
                        $m = "Msg from parser: 8011: ".$msg['net'].", Status=".$msg['status'].", Addr=".$msg['addr'].", SQNAPS=".$msg['sqnAps'];
                        if ($cmd == null) {
                            cmdLog("debug", $m." => ignored");
                            continue;
                        }
                        cmdLog("debug", $m);
                        $cmd['status'] = '8011';
                    } else if ($msg['type'] == "8012") {
                        $m = "Msg from parser: 8012: ".$msg['net'].", Status=".$msg['status'].", Addr=".$msg['addr'].", SQNAPS=".$msg['sqnAps'].", NPDU=".$nPDU.", APDU=".$aPDU;
                        if ($cmd == null) {
                            cmdLog("debug", $m." => ignored");
                            continue;
                        }
                        cmdLog("debug", $m);
                        $cmd['status'] = '8012';
                    } else if ($msg['type'] == "8702") {
                        $m = "Msg from parser: 8702: ".$msg['net'].", Status=".$msg['status'].", Addr=".$msg['addr'].", SQNAPS=".$msg['sqnAps'].", NPDU=".$nPDU.", APDU=".$aPDU;
                        if ($cmd == null) {
                            cmdLog("debug", $m." => ignored");
                            continue;
                        }
                        cmdLog("debug", $m);
                        $cmd['status'] = '8702';
                    } else {
                        cmdLog("debug", $type." msg: WARNING. What's that ???");
                        continue;
                    }
                }

                /* Should we remove cmd from queue and free zigate ?
                   If cmd with ACK (addrMode 02 or 03)
                    Expecting 8012 or 8702 after 8000
                   If cmd without ACK
                    ?
                 */
                if (!isset($removeCmd)) {
                    $removeCmd = false;
                    if (isset($cmd['addrMode'])) { // FW >= 3.1e
                        if (($cmd['addrMode'] == "02") || ($cmd['addrMode'] == "03")) {
                            // Wait for 8012 or 8702 after 8000
                            if (($cmd['status'] == '8012') || ($cmd['status'] == '8702')) {
                                $removeCmd = true;
                            }
                        } else if ($cmd['status'] == '8000') {
                            $removeCmd = true;
                        }
                    } else {
                        // Default => 8000 = final step
                        if ($cmd['status'] == '8000') {
                            $removeCmd = true;
                        }
                    }
                }

                // Apply
                if ($removeCmd) {
cmdLog('debug', '  queue before='.json_encode($zg['cmdQueue']));
                    if ($zg['sentPri'] == PRIO_HIGH)
                        array_shift($zg['cmdQueueHigh']); // Removing cmd
                    else
                        array_shift($zg['cmdQueue']); // Removing cmd
cmdLog('debug', '  queue after='.json_encode($zg['cmdQueue']));
                    $zg['available'] = 1; // Zigate is free again
                }
            }

            // cmdLog("debug", "  ".count($this->cmdQueue[$zgId])." remaining pending commands", $this->debug['processZigateAcks']);
// cmdLog("debug", "  LA23=".json_encode($this->zigates));
        } // End processZigateAcks()

        // Check zigate status which may be blocked by unacked sent cmd
        function zigateAckCheck() {
            foreach ($this->zigates as $zgId => $zg) {
                if ($zg['enabled'] == 0)
                    continue; // This zigate is disabled/unconfigured
                if ($zg['available'] == 1)
                    continue; // Zigate is ON & available => nothing to check

                $zg = &$this->zigates[$zgId];
                if ($zg['sentPri'] == PRIO_HIGH) {
                    $queue = &$zg['cmdQueueHigh'];
                } else {
                    $queue = &$zg['cmdQueue'];
                }
                $cmd = $queue[0];
                if ($cmd['sentTime'] + 2 > time())
                    continue; // 2sec timeout not reached yet

                cmdLog("debug", "zigateAckCheck(): WARNING: Zigate".$zgId." cmd ".$cmd['cmd']." TIMEOUT (SQNAPS=".$cmd['sqnAps'].") => Considering zigate available.");
                $zg['available'] = 1;
                array_shift($queue); // Removing blocked cmd

                // if ($this->timeLastAck[$zgId] == 0)
                //     continue;

                // $now = time();
                // $delta = $now - $this->timeLastAck[$zgId];
                // if ($delta > $this->timeLastAckTimeOut[$zgId]) {
                //     cmdLog("debug", "zigateAckCheck(): WARNING: NO Zigate".$zgId." ACK since ".$delta." sec. Considering zigate available.");
                //     $this->zigateAvailable[$zgId] = 1;
                //     $this->timeLastAck[$zgId] = 0;
                // }
            }
        }

        // function getQueueName($queue){
        //     /**
        //      * return queue name from queueId
        //      *
        //      * has to be implemented in each classe that use msg_get_queue
        //      *
        //      * @param $queueId
        //      * @return string queue name
        //      */
        //     $queueTopic="Not Found";
        //     switch($queue){
                // case $this->queueKeyAbeilleToCmd:
                //     $queueTopic="queueKeyAbeilleToCmd";
                //     break;
                // case $this->queueParserToCmd:
                //     $queueTopic="queueParserToCmd";
                //     break;
                // case $this->$abQueues["xToCmd"]:
                //     $queueTopic="$abQueues["xToCmd"]";
                //     break;
                // case $this->queueKeyCmdToAbeille:
                //     $queueTopic="queueKeyCmdToAbeille";
                //     break;
                // case $this->queueKeyLQIToCmd:
                //     $queueTopic="queueKeyLQIToCmd";
                //     break;
                // case $this->queueKeyXmlToCmd:
                //     $queueTopic="queueKeyXmlToCmd";
                //     break;
                // case $this->queueKeyFormToCmd:
                //     $queueTopic="queueKeyFormToCmd";
                //     break;
        //         case $this->queueParserToCmdAck:
        //             $queueTopic="queueParserToCmdAck";
        //             break;
        //     }
        //     return $queueTopic;
        // }

        /* Collect & treat other messages for AbeilleCmd */
        function collectAllOtherMessages() {
            $listQueue = array(
                $this->queueXToCmd,
                // $this->queueParserToCmd,
                // Tcharp38: TODO: Merge all following queues in 1 (cmds for zigate)
                // $this->queueKeyAbeilleToCmd,
                // $this->$abQueues["xToCmd"],
                // $this->queueKeyLQIToCmd,
                // $this->queueKeyXmlToCmd,
                // $this->queueKeyFormToCmd,
            );

            // Recupere tous les messages venant des autres threads, les analyse et converti et met dans la queue cmdQueue
            foreach ($listQueue as $queue) {
                $msg = NULL;
                // if ($queue == $this->queueParserToCmd)
                //     $msgMax = $this->queueParserToCmdMax;
                // else
                    $msgMax = 512;
                if (msg_receive($queue, 0, $msgType, $msgMax, $msg, true, MSG_IPC_NOWAIT, $errCode) == false) {
                    if ($errCode == 7) {
                        msg_receive($queue, 0, $msgType, $msgMax, $msg, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        logMessage('debug', 'collectAllOtherMessages() ERROR: msg TOO BIG ignored.');
                    } else if ($errCode != 42) // 42 = No message
                        logMessage("error", "collectAllOtherMessages() ERROR ".$errCode." on queue 'xToCmd'");
                    continue; // Moving to next queue
                }

                cmdLog("debug", "Msg from 'xToCmd': ".$msg['topic']." => ".$msg['payload'], $this->debug['AbeilleCmdClass']);
                $topic = $msg['topic'];
                $payload = $msg['payload'];
                // $message->priority = $msg_priority;
                $this->prepareCmd($topic, $payload);
            }
        }
    }
?>
