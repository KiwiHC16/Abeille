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

        public $queueParserToCmdMax;
        public $queueParserToCmdAck;
        public $queueParserToCmdAckMax;
        public $tempoMessageQueue;
        // public $maxRetry = maxRetryDefault; // Abeille will try to send the message max x times
        // public $zigates = array(); // All enabled zigates
        // public $statCmd = array();

        /**
         * Return number of cmd in the queue
         */
        public function checkCmdToSendInTheQueue($priority) {
            // if (is_array($GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority]) )
            //     return count($GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority]);
            // else
            //     return 0;
            if (is_array($GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority]) )
                return count($GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority]);
            else
                return 0;
        }

        // public function addNewCmdToQueue($priority, $newCmd) {
        //     // if ($priority == PRIO_HIGH) {
        //     //     $GLOBALS['zigates'][$zgId]['cmdQueueHigh'][] = $newCmd;
        //     //     cmdLog("debug", "    \->Added cmd to Zigate".$zgId." HIGH priority queue. QueueSize=".$queueSize, $this->debug['addCmdToQueue2']);
        //     //     if (count($GLOBALS['zigates'][$zgId]['cmdQueueHigh']) > 50) {
        //     //         cmdLog('debug', '    WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue');
        //     //     }
        //     // }
        //     // else {
        //     //     $GLOBALS['zigates'][$zgId]['cmdQueue'][] = $newCmd;
        //     //     cmdLog("debug", "    \->Added cmd to Zigate".$zgId." normal priority queue. QueueSize=".$queueSize, $this->debug['addCmdToQueue2']);
        //     //     if (count($GLOBALS['zigates'][$zgId]['cmdQueue']) > 50) {
        //     //         cmdLog('debug', '    WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue');
        //     //     }
        //     // }

        //     $GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority][] = $newCmd;
        //     if (count($GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority]) > 50) {
        //         cmdLog('debug', '    WARNING: More than 50 pending messages in zigate'.$this->zgId.' cmd queue: '.$priority);
        //     }
        // }

        // public function removeFirstCmdFromQueue($priority) {
        //     if ($this->checkCmdToSendInTheQueue($priority))
        //         array_shift($GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority]);
        //     else
        //         cmdLog("debug", __FUNCTION__." Trying to remove a cmd in an empty queue.");
        // }

        // public function zgGetQueue($priority) {
        //     return $GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority];
        // }

        // public function zgChangeStatusSentQueueFirstMessage($priority, $status) {
        //     // $GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority][0]['status'] = $status;
        //     $GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority][0]['status'] = $status;
        // }

        // public function zgChangesqnApsSentQueueFirstMessage($priority, $sqnAps) {
        //     // $GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority][0]['sqnAps'] = $sqnAps;
        //     $GLOBALS['zigates'][$this->zgId]['cmdQueue'][$priority][0]['sqnAps'] = $sqnAps;
        // }

        // public function zgGetSentPri() {
        //     return $GLOBALS['zigates'][$this->zgId]['sentPri'];
        // }

        // public function setFwVersion($zgId, $hw, $fw) {
        //     $GLOBALS['zigates'][$zgId]['hw'] = $hw;
        //     $GLOBALS['zigates'][$zgId]['fw'] = $fw;
        // }

        // public function zgGetHw() {
        //     return $GLOBALS['zigates'][$this->zgId]['hw'];
        // }

        // public function zgGetFw() {
        //     return $GLOBALS['zigates'][$this->zgId]['fw'];
        // }

        // public function initNewZigateDefault($zgId) {
        //     $zg = array();
            // cmdLog("debug", __FUNCTION__." Enabled: ".config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N'));
            // if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') == 'Y') {
            //     $zg['enabled'] = 1;
            // } else {
            //     $zg['enabled'] = 0;
            // }
            // $zg['port'] = config::byKey('ab::zgPort'.$zgId, 'Abeille', '');
            // if ($zg['port'] == '') {
            //     $zg['enabled'] = 0;
            //     cmdLog('error', 'initNewZigateDefault: port non défini');
            // }
            // $zg['ieeeOk'] = config::byKey('ab::zgIeeeAddrOk'.$zgId, 'Abeille', '-1');
            // cmdLog("debug", __FUNCTION__." Enabled: ".$zg['enabled']);
            // $zg['available'] = 1;           // By default we consider the Zigate available to receive commands
            // $zg['hw'] = 0;                  // HW version: 1=v1, 2=v2
            // $zg['fw'] = 0;                  // FW minor version (ex 0x321)
            // $zg['nPDU'] = 0;                // Last NDPU
            // $zg['aPDU'] = 0;                // Last APDU
            // $zg['cmdQueue'] = array();      // Array of queues. One queue per priority from priorityMin to priorityMax.
            // foreach(range(priorityMin, priorityMax) as $prio) {
            //     $zg['cmdQueue'][$prio] = array();
            // }
            // $zg['sentPri'] = 0;             // Priority for last sent cmd for following 8000 ack

        //     return $zg;
        // }

        function __construct($debugLevel='debug') {
            // cmdLog("debug", "AbeilleCmdQueue constructor start", $this->debug["AbeilleCmdClass"]);
            // cmdLog("debug", "Recuperation des queues de messages", $this->debug["AbeilleCmdClass"]);

            $abQueues = $GLOBALS['abQueues'];
            $this->queueParserToCmdAck      = msg_get_queue($abQueues["parserToCmdAck"]["id"]);
            $this->queueParserToCmdAckMax   = $abQueues["parserToCmdAck"]["max"];
            $this->queueXToCmd              = msg_get_queue($abQueues["xToCmd"]["id"]);
            $this->queueXToCmdMax           = $abQueues["xToCmd"]["max"];

            $this->tempoMessageQueue = array();

            // $GLOBALS['zigates'] = array();
            // cmdLog("debug", "AbeilleCmdQueue constructor");
            // for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            //     $GLOBALS['zigates'][$zgId] = $this->initNewZigateDefault($zgId);
            //     cmdLog("debug", "  zg".$zgId.'='.json_encode($GLOBALS['zigates'][$zgId]), $this->debug["AbeilleCmdClass"] );
            // }

            // Used to know which Zigate we are working on right now. Sort of global variable. Would have been better to have queue objects and not queue array. Perhaps in the futur could improve the code...
            $this->zgId = 1;

            $this->displayStatus();

            // $this->lastSqn = 0; // Moved as global var

            // cmdLog("debug", "AbeilleCmdQueue constructor end", $this->debug["AbeilleCmdClass"]);
        }

        /*
        public function incStatCmd( $cmd ) {
            if ( isset($this->statCmd[$cmd]) ) {
                $this->statCmd[$cmd]++;
            }
            else {
                $this->statCmd[$cmd]=1;
            }
            cmdLog('debug', '    incStatCmd(): '.json_encode($this->statCmd) );
        }
        */

        public function publishMosquitto($queueId, $priority, $topic, $payload) {

            $queue = msg_get_queue($queueId);

            $msg = array();
            $msg['topic']   = $topic;
            $msg['payload'] = $payload;
            $msgJson = json_encode($msg);

            if (msg_send($queue, $priority, $msgJson, false, false)) {
                cmdLog('debug', '(fct publishMosquitto) mesage: '.$msgJson.' added to queue : '.$queueId, $this->debug['tempo']);
            } else {
                cmdLog('debug', '(fct publishMosquitto) could not add message '.$msgJson.' to queue : '.$queueId, $this->debug['tempo']);
            }
        }

        public function msgToAbeille($topic, $payload) {
            global $abQueues;
            $queueId = $abQueues['xToAbeille']['id'];
            $queue = msg_get_queue($queueId);

            $msg = array();
            $msg['topic']   = $topic;
            $msg['payload'] = $payload;
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

            if (msg_send($queue, 1, $msgJson, false, false)) {
                cmdLog('debug', 'msgToAbeille() mesage: '.$msgJson.' added to queue : '.$queueId, $this->debug['tempo']);
            } else {
                cmdLog('debug', 'msgToAbeille() could not add message '.$msgJson.' to queue : '.$queueId, $this->debug['tempo']);
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
            if (count($this->tempoMessageQueue) > 50) {
                cmdLog('info', 'Il y a plus de 50 messages dans le queue tempo.');
            }
        }

        public function execTempoCmdAbeille() {
            global $abQueues;

            if (count($this->tempoMessageQueue) == 0)
                return;

            $now = time();
            foreach ($this->tempoMessageQueue as $key => $mqttMessage) {
                // deamonlog('debug', 'execTempoCmdAbeille - tempoMessageQueue - 0: '.$mqttMessage[0] );
                if ($mqttMessage['time'] > $now)
                    continue;

                $this->publishMosquitto($abQueues['xToCmd']['id'], $mqttMessage['priority'], $mqttMessage['topic'], $mqttMessage['msg']);
                cmdLog('debug', 'execTempoCmdAbeille(): tempoMessageQueue='.json_encode($this->tempoMessageQueue[$key]), $this->debug['tempo']);
                unset($this->tempoMessageQueue[$key]);
                // cmdLog('debug', 'execTempoCmdAbeille - tempoMessageQueue : '.json_encode($this->tempoMessageQueue), $this->debug['tempo']);
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

            // cmdLog('debug',"getChecksum fct - msgtype: " . $msgtype . " length: " . $length . " datas: " . $datas . " strlen data: " . strlen($datas) . " checksum calculated: " . sprintf("%02X",$temp), $this->debug["Checksum"]);

            return sprintf("%02X",$temp);
        }

        function transcode($datas) {
            // cmdLog('debug','transcode fct - transcode data: '.$datas, $this->debug['transcode']);
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

        // Push a new zigate cmd to be sent
        // If 'begin' is set to true, cmd is added in front of queue to be the first one
        public static function pushZigateCmd($zgId, $pri, $zgCmd, $payload, $addr, $addrMode, $begin = false) {
            if (($addrMode == "02") || ($addrMode == "03"))
                $ackAps = true;
            else
                $ackAps = false;
            $newCmd = array(
                // 'priority'  => $pri,
                'dest'      => 'Abeille'.$zgId,
                'addr'      => $addr, // For monitoring purposes
                'cmd'       => $zgCmd, // Zigate message type
                'datas'     => $payload,
                'zgOnly'    => zgIsZigateOnly($zgCmd), // Msg for Zigate only if true, not to be transmitted
                'status'    => '', // '', 'SENT', '8000', '8012' or '8702', '8011'
                'try'       => maxRetryDefault + 1, // Number of retries if failed
                'sentTime'  => 0, // For lost cmds timeout
                // Timeout: Zigate has 7s internal timeout when ACK
                'timeout'   => $ackAps ? 8 : 4, // Cmd expiration time
                'sqn'       => '', // Zigate SQN
                'sqnAps'    => '', // Network SQN
                'ackAps'    => $ackAps, // True if ACK, false else
                'waitFor'   => $ackAps ? "ACK": "8000",
            );
            // Abeille PDM restore cmd can be slow
            if ($zgCmd == "AB02") {
                $newCmd['timeout'] = 120; // TODO: Should be relative to data size
                $newCmd['waitFor'] = "AB03";
            }
            if ($begin) {
                // cmdLog('debug', 'LA='.json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri]));
                array_unshift($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri], $newCmd);
                // cmdLog('debug', 'LA2='.json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri]));
            } else
                $GLOBALS['zigates'][$zgId]['cmdQueue'][$pri][] = $newCmd;
        }

        /**
         * addCmdToQueue()
         *
         * put in the queue the cmd to be put on the serial link to the zigate
         *
         * @param priority  priority of the command in the queue
         * @param net       Network (ex: 'Abeille2')
         * @param zgCmd     Zigate cmd in hex format as per zigate API
         * @param payload   Cmd payload
         * @param addr      Device short addr
         * @param addrMode  Addressing mode (ACK/no ACK)
         *
         * @return  none
         */
        public static function addCmdToQueue2($priority, $net, $zgCmd, $payload = '', $addr = '', $addrMode = null) {
            cmdLog("debug", "    addCmdToQueue2(Pri=${priority}, Net=${net}, ZgCmd=${zgCmd}, Payload=${payload}, Addr=${addr}, AddrMode=${addrMode})");

            $zgId = substr($net, 7);
            // $this->zgId = $zgId;

            // Checking min parameters
            if (!$GLOBALS['zigates'][$zgId]['enabled']) {
                cmdLog("debug", "    Zigate disabled => cmd IGNORED");
                return;
            }
            // Temp disabled. 'ieeeOk' should be updated from parser when it is ok. Not the case here.
            // if ($GLOBALS['zigates'][$zgId]['ieeeOk'] == '-1') {
            //     cmdLog("debug", "    Zigate on wrong port => cmd IGNORED");
            //     return;
            // }
            if (!ctype_xdigit($zgCmd)) {
                cmdLog('error', '    Commande Zigate invalide: Pas en hexa ! ('.$zgCmd.')');
                return;
            }
            if (($payload != '') && !ctype_xdigit($payload)) {
                cmdLog('error', '    Invalid payload. Not hexa ! ('.$payload.')');
                return;
            }
            $len = strlen($payload);
            if ($len % 2) { // Checking payload length
                cmdLog("error", "    Commande Zigate '${zgCmd}' ignorée: Taille données impaire");
                return;
            }

            // Overflow optimisation
            // Ignoring if same cmd/payload already in the pipe
            // TODO: May need to take care about 'toggle' case.
            // $pendingCmds = $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority];
            $pendingCmds = $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority];
            foreach($pendingCmds as $pendCmd) {
                if ($pendCmd['cmd'] != $zgCmd)
                    continue;
                if ($pendCmd['datas'] == $payload) {
                    cmdLog('debug', "    Same cmd already pending with priority ${priority} => ignoring");
                    return;
                }
            }

            // $this->incStatCmd($cmd);

            AbeilleCmdQueue::pushZigateCmd($zgId, $priority, $zgCmd, $payload, $addr, $addrMode);

            // Display statistics
            $queuesTxt = '';
            foreach (range(priorityMax, priorityMin) as $prio) {
                if ($queuesTxt != '')
                    $queuesTxt .= ', ';
                $queuesTxt .= "[".$prio."]=".count($GLOBALS['zigates'][$zgId]['cmdQueue'][$prio]);
            }
            cmdLog('debug', '    Zg '.$zgId.' queues: '.$queuesTxt);
            if (count($GLOBALS['zigates'][$zgId]['cmdQueue'][$priority]) > 50) {
                cmdLog('debug', '    WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue: '.$priority);
            }
        } // End addCmdToQueue2()

        /**
         * Find cmd corresponding to SQNAPS
         * Returns: Found matching cmd, or null
         *  'lastSent' is true if cmd is the last sent one for this zigate.
         */
        function getCmd($zgId, $sqnAps, &$lastSent = false) {
            $zg = $GLOBALS['zigates'][$zgId];

            foreach (range(priorityMax, priorityMin) as $prio) {
                foreach ($zg['cmdQueue'][$prio] as $cmdIdx => $cmd) {
                    if ($cmd['sqnAps'] == $sqnAps) {
                        // Is it the last sent cmd ?
                        if (($cmdIdx == 0) && ($prio == $GLOBALS['zigates'][$zgId]['sentPri']))
                            $lastSent = true;
                        return $GLOBALS['zigates'][$zgId]['cmdQueue'][$prio][$cmdIdx];
                    }
                }
            }

            return null;
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
        function sendCmdToZigate($dest, $addr, $cmd, $datas) {
            // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
            // SVP ne pas enlever ce code c est tres utile pour le debug et verifier les commandes envoyées sur le port serie.
            // if (0) {
            //     $f = fopen("/var/www/html/log/toto","w");
            //     $this->writeToDest($f, $dest, $cmd, $datas);
            //     fclose($f);
            // }

            cmdLog('debug', '  sendCmdToZigate(Dest='.$dest.', addr='.$addr.', cmd='.$cmd.', datas='.$datas.")", $this->debug['sendCmdToZigate']);

            $zgId = substr($dest, 7);
            $this->zgId = $zgId;
            $destSerial = $GLOBALS['zigates'][$zgId]['port'];

            // Test should not be needed as we already tested in addCmdToQueue2
            if (!$GLOBALS['zigates'][$zgId]['enabled']) {
                cmdLog("debug", "  Zigate ".$zgId." (".$destSerial.") disabled => ignoring cmd ".$cmd.'-'.$datas);
                return;
            }

            // Note: Using file_exists() to avoid PHP warning when port issue.
            if (!file_exists($destSerial) || (($f = fopen($destSerial, "w")) == false)) {
                // cmdLog("error", "  Port '$destSerial' non accessible. Commande '$cmd' non écrite.");
                cmdLog("debug", "  '$destSerial' port not accessible => '$cmd' cmd ignored.");
                return;
            }

            // cmdLog("debug", "  Writing to port ".$destSerial.': '.$cmd.'-'.$len.'-'.$datas, $this->debug['sendCmdToZigate']);
            $this->writeToDest($f, $destSerial, $cmd, $datas);
            fclose($f);
        }

        /**
         * Display queues & zigate status.
         * Called every 30sec.
         */
        function displayStatus() {
            // cmdLog("debug", __FUNCTION__." AbeilleCmdQueue constructor, zigates:".json_encode($GLOBALS['zigates']), $this->debug["AbeilleCmdClass"] );
            $zgTxt = '';
            // foreach ($GLOBALS['zigates'] as $zgId => $zg) {
            foreach ($GLOBALS['zigates'] as $zgId => $zg) {
                $available = $zg['available'] ? "yes" : "NO";
                $zgTxt .= "/".$available;
            }
            cmdLog("debug", "  Zg (avail): ".$zgTxt);


            $queuesTxt = "";
            if (isset($this->tempoMessageQueue)) {
                $queuesTxt .= "tempo=".count( $this->tempoMessageQueue );
            }

            foreach ($GLOBALS['zigates'] as $zgId => $zg) {
                foreach (range(priorityMin, priorityMax) as $prio) {
                    $queuesTxt .= ", Queue[".$zgId."][".$prio."]=".count($zg['cmdQueue'][$prio]);

                    if ($zgTxt != "")
                        $zgTxt .= ", ";
                    $zgTxt .= "zg".$zgId."=on";
                }
            }

            cmdLog("debug", "Status, queues : ".$queuesTxt);
        }

        // Check if any pending cmd to be sent to Zigate
        function processCmdQueues() {
            // cmdLog("debug", __FUNCTION__." Begin");
            // cmdLog('debug', '  processCmdQueues() zigates='.json_encode($GLOBALS['zigates']));

            // foreach ($GLOBALS['zigates'] as $zgId => $zg) {
            foreach ($GLOBALS['zigates'] as $zgId => $zg) {
                // cmdLog("debug", __FUNCTION__." zgId=".$zgId.", zg=".json_encode($zg));

                if (!$zg['enabled']) continue; // Disabled
                if (!$zg['available']) continue;  // Already treating a command

                $this->zgId = $zgId;
                foreach (range(priorityMax, priorityMin) as $priority) {
                    $count = count($zg['cmdQueue'][$priority]);
                    if ($count == 0)
                        continue; // Queue empty

                    // There is something to send

                    // Throughput limitation
                    // $zg['tp_time'] gives time (in us) when Zigate can be considered available again.
                    $mt = microtime(true);
                    if (isset($zg['tp_time']) && ($zg['tp_time'] > $mt)) {
                        cmdLog('debug', "processCmdQueues(): ZgId=${zgId} => Throughput limitation");
                        break; // This zigate is not yet available
                    }

                    cmdLog('debug', "processCmdQueues(): ZgId=${zgId}, Pri=${priority}, NPDU=".$zg['nPDU'].", APDU=".$zg['aPDU']);

                    $cmd = $zg['cmdQueue'][$priority][0]; // Takes first cmd
                    cmdLog('debug', "  cmd=".json_encode($cmd));
                    if ($cmd['status'] != '') {
                        cmdLog('debug', "  WARNING: Unexpected cmd status '".$cmd['status']."'");
                    }

                    // NDPU limitation (NDPU too high leads to extended error due to lack of resources)
                    // if (!$cmd['zgOnly']) { // Not a cmd for Zigate only
                    //     if ($zg['nPDU'] > 7) {
                    //         cmdLog('debug', "  NDPU limitation (NPDU=".$zg['nPDU'].")");
                    //         break; // This zigate is not yet available
                    //     }
                    // }

                    /* Additional flow control with nPDU/aPDU regulation to avoid zigate internal saturation.
                        This must not prevent zigate internal commands (ex: read version).
                        If HW v1
                            If FW >= 3.1e, using NPDU/APDU regulation.
                            If FW <  3.1e, regulation based on max cmd per sec => Throughput limitation
                        If HW v2
                            No flow control. Regulation based on max cmd per sec => Throughput limitation
                    */
                    // if (($zg['hw'] == 1) && !$cmd['zgOnly']) { // Not a cmd for Zigate only
                    //     // cmdLog('debug', "processCmdQueues(): nPDU/aPDU regulation to be checked: ".json_encode($zg));
                    //     if (($zg['nPDU'] > 7) || ($zg['aPDU'] > 2)) {
                    //         cmdLog('debug', '  NPDU/APDU regulation for Zigate  '.$zgId.' (NPDU='.$zg['nPDU'].', APDU='.$zg['aPDU'].')');

                    //         // Adding a "read version" cmd as FIRST CMD to force NDPU/APDU update
                    //         $this->pushZigateCmd($zgId, $priority, "0010", "", "0000", "00", true);
                    //         $cmd = $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][0]; // Takes first cmd
                    //     }
                    // }

                    $GLOBALS['zigates'][$zgId]['available'] = 0; // Zigate no longer free
                    $GLOBALS['zigates'][$zgId]['sentPri'] = $priority; // Keep the last queue used to send a cmd to this Zigate

                    $this->sendCmdToZigate($cmd['dest'], $cmd['addr'], $cmd['cmd'], $cmd['datas']);

                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][0]['status'] = "SENT";
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][0]['sentTime'] = time();
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][0]['try']--;
                    $GLOBALS['zigates'][$zgId]['tp_time'] = microtime(true) + 0.1; // Next avail time in 100ms

                    if (isset($GLOBALS["dbgMonitorAddr"]) && ($cmd['addr'] != "") && ($GLOBALS["dbgMonitorAddr"] != "") && !strncasecmp($cmd['addr'], $GLOBALS["dbgMonitorAddr"], 4))
                        monMsgToZigate($cmd['addr'], $cmd['cmd'].'-'.$cmd['datas']); // Monitor this addr ?

                    break; // This zigate is no longer idle so do not check other priorities now.
                }
            }

            // cmdLog('debug', '  processCmdQueues() zigates='.json_encode($GLOBALS['zigates']));
            // cmdLog("debug", __FUNCTION__." End");
        }

        // Process 8000, 8011, 8012 or 8702 messages
        /**
         * Loop to read all messages and quit when no more message (break loop)
         */
        function processAcks() {

            // cmdLog("debug", __FUNCTION__." Begin");

            while (true) {
                $msgMax = $this->queueParserToCmdAckMax;
                if (msg_receive($this->queueParserToCmdAck, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == false) {
                    if ($errCode == 7) {
                        msg_receive($this->queueParserToCmdAck, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        logMessage('error', 'processAcks() ERROR: msg TOO BIG ignored: '.$msgJson);
                        continue;
                    } else if ($errCode != 42) // 42 = No message
                        cmdLog("debug", "processAcks() ERROR: msg_receive() err ".$errCode);

                    // cmdLog("debug", __FUNCTION__." End due to return as no more messages in the queue.");
                    return;
                }

                cmdLog("debug", 'processAcks(): msg='.$msgJson);
                $msg = json_decode($msgJson, true);
                $zgId = substr($msg['net'], 7);
                $this->zgId = $zgId;

                if (isset($msg['sqnAps']))
                    $sqnAps = $msg['sqnAps'];
                else
                    $sqnAps = "?";

                if (isset($msg['nPDU'])) {
                    $nPDU = $msg['nPDU'];
                    $GLOBALS['zigates'][$zgId]['nPDU'] = hexdec($nPDU);
                } else
                    $nPDU = "?";

                if (isset($msg['aPDU'])) {
                    $aPDU = $msg['aPDU'];
                    $GLOBALS['zigates'][$zgId]['aPDU'] = hexdec($aPDU);
                } else
                    $aPDU = "?";

                unset($removeCmd); // Unset in case set in previous msg
                $sentPri = $GLOBALS['zigates'][$zgId]['sentPri'];

                // cmdLog("debug", "  LA=".json_encode($GLOBALS['zigates']));
                //$zg = &$GLOBALS['zigates'][$zgId];
                // cmdLog("debug", "processAcks(): type=".$msg['type'], $this->debug['processAcks']);

                // Tcharp38 TODO: This msg should be not passed thru this queue.
                if ($msg['type'] == "8010") {
                    cmdLog("debug", "  8010 msg: FwVersion=".$msg['major']."-".$msg['minor']);
                    if ($msg['major'] == '0005')
                        $hw = 2; // Zigate v2
                    else
                        $hw = 1;
                    $GLOBALS['zigates'][$zgId]['hw'] = $hw;
                    $GLOBALS['zigates'][$zgId]['fw'] = hexdec($msg['minor']);
                    continue;
                }

                // PDM restore response (Abeille's ABxx-yyyy specific FW)
                if ($msg['type'] == "AB03") {
                    cmdLog("debug", "  AB03 msg: ID=".$msg['id'].", Status=".$msg['status']);

                    $removeCmd = true;
                }

                // TODO: To be revisited. Ex: 8000 then 801x ignored
                // if (!count($zg['cmdQueue']) && !count($zg['cmdQueueHigh'])) {
                //     cmdLog("debug", $msg['type']." msg but empty cmd queues => ignored");
                //     continue;
                // }

                /* ACK or no ACK ?
                   In all cases expecting 8000 as first last sent cmd ack.
                   If ackAps is true (addrMode 02 or 03), zigate is released after
                     - 8702 => Buffered. Does not mean anything. May finish sucessfully or not.
                     - 8012 => Message received by next hope.
                     - 8011 (following 8012) => device acknowledged (or not) cmd (got 8000, 8012, then 8011)
                   If ackAps is false, zigate is released after 8000 msg
                 */

                else if ($msg['type'] == "8000") {

                    // Checking sent cmd vs received ack misalignment
                    if ( !$this->checkCmdToSendInTheQueue($sentPri) ) {
                        // cmdLog("debug", $m." => ignored as queue is empty");
                        continue;
                    }

                    $cmd = $GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri][0];

                    // Checking sent cmd vs received ack misalignment
                    if ($msg['packetType'] != $cmd['cmd']) {
                        cmdLog("debug", "  8000 => ignored as packets are different: packetType=".$msg['packetType']." cmd=".$cmd['cmd']);
                        continue;
                    }

                    // Storing SQN APS. This is key to identify cmd linked to msg. Might not be last sent except for 8000.
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri][0]['sqn'] = $msg['sqn']; // Internal SQN
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri][0]['sqnAps'] = $sqnAps; // Network SQN

                    $msgStatus = $msg['status'];
                    if ($msgStatus == "00") {

                        if ($cmd['waitFor'] == "ACK")
                            continue; // On this command I m waiting for APS ACK.
                        if ($cmd['waitFor'] != "8000")
                            continue; // Need to wait for something else

                        // if ($cmd['ackAps']) continue; // On this command I m waiting for APS ACK.
                        $removeCmd = true;
                        // Status is: success
                        // $this->zgChangeStatusSentQueueFirstMessage($this->zgGetSentPri(), '8000');
                        // $cmd['status'] = '8000';
                        // if ($sqnAps == '00')
                        //     $removeCmd = true; // Cmd not transmitted on air => no ack to expect
                        // else {
                        //     $this->zgChangesqnApsSentQueueFirstMessage($this->zgGetSentPri(), $sqnAps);
                        //     // $cmd['sqnAps'] = $sqnAps;
                        //     $removeCmd = true;
                        // }
                    }
                    else if (in_array($msgStatus, ['01', '02', '03', '05', '06', '14'])) {
                        // Status is: bad param, unhandled, failed (?), stack already started
                        // Status 06 = Unknown EP ? (Zigate v2)
                        // 14/E_ZCL_ERR_ZBUFFER_FAIL: Msg too big
                        // $cmd['status'] = '8000';
                        cmdLog("debug", "  WARNING: Zigate cmd failed (status=${msgStatus})");
                        $removeCmd = true;
                    }
                    else {
                        // Something failed
                        if ($cmd['try'] == 0) {
                            cmdLog("debug", "  WARNING: Something failed and too many retries.");
                            $removeCmd = true;
                        }
                        else {
                            cmdLog("debug", "  WARNING: Something failed. Cmd will be retried ".$cmd['try']." time(s) max.");
                        }
                    }
                }

                else if ($msg['type'] == "8011") {
                    $cmd = $this->getCmd($zgId, $msg['sqnAps'], $lastSent);
                    if ($cmd == null) {
                        cmdLog('debug', '  Corresponding cmd not found.');
                        continue;
                    }
                    cmdLog('debug', "  cmd=".json_encode($cmd));

                    // If ACK is requested but failed, removing cmd or it will lead to cmd timeout.
                    // Note: This is done only if cmd == last sent.
                    // if ($cmd['ackAps'] && $lastSent)
                    //     $removeCmd = true;
                    if ($lastSent && ($cmd['waitFor'] == "ACK")) {
                        $removeCmd = true;

                        $net = $msg['net'];
                        $addr = $msg['addr'];
                        $eq = &getDevice($net, $addr); // By ref
                        if ($eq === []) {
                            cmdLog('debug', "  WARNING: Unknown device: Net=${net} Addr=${addr}");
                        } else {
                            cmdLog('debug', "  eq=".json_encode($eq));
                            // Note: TX status makes sense only if device is always listening (rxOnWhenIdle=TRUE)
                            //       For other devices any interrogation without waking up device may lead to NO-ACK which is normal
                            if (isset($eq['rxOnWhenIdle']) && $eq['rxOnWhenIdle']) {
                                $newTxStatus = '';
                                if ($msg['status'] == '00') { // Ok ?
                                    if ($eq['txStatus'] !== 'ok')
                                        $newTxStatus = 'ok';
                                } else { // NO ACK ?
                                    if ($eq['txStatus'] !== 'noack')
                                        $newTxStatus = 'noack';
                                }
                                if ($newTxStatus != '') {
                                    $eq['txStatus'] = $newTxStatus;
                                    $msg = array(
                                        // 'src' => 'cmd',
                                        'type' => 'eqTxStatusUpdate',
                                        'net' => $net,
                                        'addr' => $addr,
                                        'txStatus' => $newTxStatus // 'ok', or 'noack'
                                    );
                                    msgToAbeille($msg);
                                    cmdLog2('debug', $addr, "  ${net}-${addr} status changed to '${newTxStatus}'");
                                }
                            }
                        }
                    }
                }

                else if ($msg['type'] == "8012") {
                    // 8012 msg used to get NPDU/APDU status updates only
                    return;
                }

                /* Tcharp38: 8702 now ignored. Just means message buffered but does not
                   mean failed. It may terminate with a 8011/ACK or 8011/NO_ACK
                   Ex:
                   [2022-11-11 00:15:48] Abeille1, Type=8000/Status, Status=00/Success, SQN=14, PacketType=004E, Sent=02, SQNAPS=2E, NPDU=02, APDU=01
                   [2022-11-11 00:15:48] Abeille1, Type=8702/APS data confirm fail, Status=D4/ZPS_NWK_ENUM_FRAME_IS_BUFFERED, SrcEP=00, DstEP=00, AddrMode=02, Addr=6903, SQNAPS=2E, NPDU=02, APDU=01
                   [2022-11-11 00:15:49] Abeille1, Type=8011/APS data ACK, Status=00/Success, Addr=6903, EP=00, ClustId=0031, SQNAPS=2E
                */
                // else if ($msg['type'] == "8702") {
                //     $cmd = $this->getCmd($zgId, $msg['sqnAps'], $lastSent);
                //     if ($cmd == null) {
                //         cmdLog('debug', '  Corresponding cmd not found.');
                //         // Note: This can appear for cmds not sent from zigate, but received by zigate (ex: request cluster 000A/time from device)
                //         continue;
                //     }

                //     // If ACK is requested but failed, removing cmd or it will lead to cmd timeout.
                //     // Note: This is done only if cmd == last sent.
                //     if ($cmd['ackAps'] && $lastSent)
                //         $removeCmd = true;
                // }

                // Removing last sent cmd
                if (isset($removeCmd) && $removeCmd) {
                    // cmdLog('debug', '               queue before='.json_encode($this->zgGetQueue($this->zgGetSentPri())));

                    cmdLog('debug', '  Removing cmd from queue');
                    // $this->removeFirstCmdFromQueue($sentPri);
                    array_shift($GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri]);

                    // cmdLog('debug', '               queue after='.json_encode($this->zgGetQueue($this->zgGetSentPri())));
                    $GLOBALS['zigates'][$zgId]['available'] = 1; // Zigate is free again
                }
            }

            // cmdLog("debug", "  ".count($this->cmdQueue[$zgId])." remaining pending commands", $this->debug['processAcks']);
            // cmdLog("debug", "  LA23=".json_encode($GLOBALS['zigates']));
            // cmdLog("debug", __FUNCTION__." End");

        } // End processAcks()

        // Check zigate status which may be blocked by unacked sent cmd
        function checkZigatesStatus() {
            foreach ($GLOBALS['zigates'] as $zgId => $zg) {

                if (!$zg['enabled'])
                    continue; // This zigate is disabled/unconfigured
                if ($zg['available'])
                    continue; // Zigate is ON & available => nothing to check

                $sentPri = $zg['sentPri'];
                $cmd = $zg['cmdQueue'][$sentPri][0];
                if ($cmd['status'] == '')
                    continue; // Not sent yet
                // Timeout: At least 4sec to let Zigate do its job but is that enough ?
                // if ($cmd['ackAps'])
                //     $timeout = 8; // Zigate has 7s internal timeout when ACK
                // else
                //     $timeout = 4;
                $timeout = $cmd['timeout'];
                if ($cmd['sentTime'] + $timeout > time())
                    continue; // Timeout not reached yet

                cmdLog("debug", "WARNING: checkZigatesStatus(): Zigate".$zgId." cmd ".$cmd['cmd']." ${timeout}s TIMEOUT (SQN=".$cmd['sqn'].", SQNAPS=".$cmd['sqnAps'].") => Considering zigate available.");
                // $this->zgId = $zgId;
                // $this->removeFirstCmdFromQueue($sentPri); // Removing blocked cmd
                array_shift($GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri]);
                $GLOBALS['zigates'][$zgId]['available'] = 1;
            }
        } // End checkZigatesStatus()

        /* Collect & treat other messages from 'xToCmd' queue. */
        function processXToCmdQueue() {
            $queue = $this->queueXToCmd;
            $msgMax = $this->queueXToCmdMax;
            if (msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == false) {
                if ($errCode == 7) {
                    msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                    logMessage('debug', 'processXToCmdQueue() ERROR: msg TOO BIG ignored.');
                } else if ($errCode != 42) // 42 = No message
                    logMessage("error", "processXToCmdQueue() ERROR ".$errCode." on queue 'xToCmd'");
                return;
            }

            cmdLog("debug", "Msg from 'xToCmd': ".$msgJson);
            $msg = json_decode($msgJson, true);
            if (isset($msg['type'])) {
                if ($msg['type'] == 'readOtaFirmwares') {
                    otaReadFirmwares(); // Reread available firmwares
                } else if ($msg['type'] == 'eqUpdated') {
                    updateDeviceFromDB($msg['id']); // Update device info from eqLogic
                } else if ($msg['type'] == 'configureDevice') {
                    configureDevice($msg['net'], $msg['addr']); // Configure device (execAtCreation)
                } else
                    cmdLog("error", "AbeilleCmd: Message inattendu: ".$msgJson);
            } else {
                $prio = isset($msg['priority']) ? $msg['priority']: PRIO_NORM;
                $topic = $msg['topic'];
                $payload = $msg['payload'];
                // cmdLog("debug", "Msg from 'xToCmd': Pri=".$prio.", topic='".$topic."', payload='".$payload."'", $this->debug['AbeilleCmdClass']);
                $this->prepareCmd($prio, $topic, $payload);
            }
        }
    }
?>