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
        public $maxRetry = maxRetryDefault; // Abeille will try to send the message max x times
        public $zigates = array(); // All enabled zigates
        // public $statCmd = array();

        /**
         * Return number of cmd in the queue
         */
        public function checkCmdToSendInTheQueue($priority) {
            if (is_array($this->zigates[$this->zgId]['cmdQueue'][$priority]) )
                return count($this->zigates[$this->zgId]['cmdQueue'][$priority]);
            else
                return 0;
        }

        public function addNewCmdToQueue($priority, $newCmd) {
            // if ($priority == PRIO_HIGH) {
            //     $this->zigates[$zgId]['cmdQueueHigh'][] = $newCmd;
            //     cmdLog("debug", "      \->Added cmd to Zigate".$zgId." HIGH priority queue. QueueSize=".$queueSize, $this->debug['addCmdToQueue2']);
            //     if (count($this->zigates[$zgId]['cmdQueueHigh']) > 50) {
            //         cmdLog('debug', '      WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue');
            //     }
            // }
            // else {
            //     $this->zigates[$zgId]['cmdQueue'][] = $newCmd;
            //     cmdLog("debug", "      \->Added cmd to Zigate".$zgId." normal priority queue. QueueSize=".$queueSize, $this->debug['addCmdToQueue2']);
            //     if (count($this->zigates[$zgId]['cmdQueue']) > 50) {
            //         cmdLog('debug', '      WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue');
            //     }
            // }

            $this->zigates[$this->zgId]['cmdQueue'][$priority][] = $newCmd;
            if (count($this->zigates[$this->zgId]['cmdQueue'][$priority]) > 50) {
                cmdLog('debug', '      WARNING: More than 50 pending messages in zigate'.$this->zgId.' cmd queue: '.$priority);
            }
        }

        public function removeFirstCmdFromQueue($priority) {
            if ($this->checkCmdToSendInTheQueue($priority))
                array_shift($this->zigates[$this->zgId]['cmdQueue'][$priority]);
            else
                cmdLog("debug", __FUNCTION__." Trying to remove a cmd in an empty queue.");
        }

        public function zgGetQueueFirstMessage($priority) {
            return $this->zigates[$this->zgId]['cmdQueue'][$priority][0];
        }

        public function zgGetQueue($priority) {
            return $this->zigates[$this->zgId]['cmdQueue'][$priority];
        }

        public function zgTagSentQueueFirstMessage($priority) {
            $this->zigates[$this->zgId]['cmdQueue'][$priority][0]['status'] = "SENT";
            $this->zigates[$this->zgId]['cmdQueue'][$priority][0]['sentTime'] = time();
            $this->zigates[$this->zgId]['cmdQueue'][$priority][0]['try']--;
        }

        public function zgChangeStatusSentQueueFirstMessage($priority, $status) {
            $this->zigates[$this->zgId]['cmdQueue'][$priority][0]['status'] = $status;
        }

        public function zgChangesqnApsSentQueueFirstMessage($priority, $sqnAps) {
            $this->zigates[$this->zgId]['cmdQueue'][$priority][0]['sqnAps'] = $sqnAps;
        }

        public function zgGetEnable() {
            return $this->zigates[$this->zgId]['enabled'];
        }

        public function zgGetAvailable() {
            return $this->zigates[$this->zgId]['available'];
        }

        public function zgSetNotAvailable() {
            $this->zigates[$this->zgId]['available'] = 0;
        }

        public function zgSetAvailable() {
            $this->zigates[$this->zgId]['available'] = 1;
        }

        public function zgSetSentPri($queuePri) {
            $this->zigates[$this->zgId]['sentPri'] = $queuePri;
        }

        public function zgGetSentPri() {
            return $this->zigates[$this->zgId]['sentPri'];
        }

        public function setFwVersion($zgId, $hw, $fw) {
            $this->zigates[$zgId]['hw'] = $hw;
            $this->zigates[$zgId]['fw'] = $fw;
        }

        public function zgGetHw() {
            return $this->zigates[$this->zgId]['hw'];
        }

        public function zgGetFw() {
            return $this->zigates[$this->zgId]['fw'];
        }

        public function zgSetnPDU($hw) {
            $this->zigates[$this->zgId]['nPDU'] = $hw;
        }

        public function zgGetnPDU() {
            return $this->zigates[$this->zgId]['nPDU'];
        }

        public function zgSetaPDU($hw) {
            $this->zigates[$this->zgId]['aPDU'] = $hw;
        }

        public function zgGetaPDU() {
            return $this->zigates[$this->zgId]['aPDU'];
        }

        public function initNewZigateDefault($zgId) {
            $zg = array();
            // cmdLog("debug", __FUNCTION__." Enabled: ".config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N'));
            if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') == 'Y') {
                $zg['enabled'] = 1;
            } else {
                $zg['enabled'] = 0;
            }
            $zg['port'] = config::byKey('AbeilleSerialPort'.$zgId, 'Abeille', '');
            if ($zg['port'] == '') {
                $zg['enabled'] = 0;
                cmdLog('error', 'initNewZigateDefault: port non défini');
            }
            $zg['ieeeOk'] = config::byKey('AbeilleIEEE_Ok'.$zgId, 'Abeille', '-1');
            // cmdLog("debug", __FUNCTION__." Enabled: ".$zg['enabled']);
            $zg['available'] = 1;           // By default we consider the Zigate available to receive commands
            $zg['hw'] = 0;                  // HW version: 1=v1, 2=v2
            $zg['fw'] = 0;                  // FW minor version (ex 0x321)
            $zg['nPDU'] = 0;                // Last NDPU
            $zg['aPDU'] = 0;                // Last APDU
            $zg['cmdQueue'] = array();      // Array of queues. One queue per priority from priorityMin to priorityMax.
            foreach ( range( priorityMin, priorityMax) as $prio ) {
                $zg['cmdQueue'][$prio] = array();
            }
            $zg['sentPri'] = 0;             // Priority for last sent cmd for following 8000 ack

            return $zg;
        }

        function __construct($debugLevel='debug') {
            // cmdLog("debug", "AbeilleCmdQueue constructor start", $this->debug["AbeilleCmdClass"]);
            // cmdLog("debug", "Recuperation des queues de messages", $this->debug["AbeilleCmdClass"]);

            $abQueues = $GLOBALS['abQueues'];
            $this->queueParserToCmdAck      = msg_get_queue($abQueues["parserToCmdAck"]["id"]);
            $this->queueParserToCmdAckMax   = $abQueues["parserToCmdAck"]["max"];
            $this->queueXToCmd              = msg_get_queue($abQueues["xToCmd"]["id"]);

            $this->tempoMessageQueue = array();

            $this->zigates = array();
            cmdLog("debug", "AbeilleCmdQueue constructor");
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                $this->zigates[$zgId] = $this->initNewZigateDefault($zgId);
                cmdLog("debug", "  zg".$zgId.'='.json_encode($this->zigates[$zgId]), $this->debug["AbeilleCmdClass"] );
            }

            // Used to know which Zigate we are working on right now. Sort of global variable. Would have been better to have queue objects and not queue array. Perhaps in the futur could improve the code...
            $this->zgId = 1;

            $this->displayStatus();

            $this->lastSqn = 0;

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
            cmdLog('debug', '      incStatCmd(): '.json_encode($this->statCmd) );
        }
        */

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
            cmdLog("debug", "    addCmdToQueue2(Pri=".$priority.", Net=".$net.", Cmd=".$cmd.", Payload=".$payload.", Addr=".$addr." AddrMode=".$addrMode.")");

            $zgId = substr($net, 7);
            $this->zgId = $zgId;

            // Checking min parameters
            if (!$this->zgGetEnable()) {
                cmdLog("debug", "      Zigate disabled. Ignoring command.");
                return;
            }
            // if (config::byKey('AbeilleIEEE_Ok'.$this->zgId, 'Abeille', '-1', 1) == '-1') {
            if ($this->zigates[$zgId]['ieeeOk'] == '-1') {
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

            if (($addrMode == "02") || ($addrMode == "03"))
                $ackAps = 1;
            else
                $ackAps = 0;
            $newCmd = array(
                // 'priority'  => $priority,
                'dest'      => $net,
                'addr'      => $addr, // For monitoring purposes
                'cmd'       => $cmd,
                'datas'     => $payload,
                'status'    => '', // '', 'SENT', '8000', '8012' or '8702', '8011'
                'try'       => $this->maxRetry + 1, // Number of retries if failed
                'sentTime'  => 0, // For lost cmds timeout
                'sqn'    => '', // Internal SQN
                'sqnAps'    => '', // Network SQN
                'ackAps'    => $ackAps, // 1 if ACK, 0 else
            );
            // Tcharp38: This is for nPDU/aPDU regulation. To be revisited later.
            // if ($addrMode && ($this->zgGetHw()) && ($this->zgGetFw() >= 0x31e))
            //     $newCmd['addrMode'] = $addrMode; // For flow control if v1 & FW >= 3.1e

            $this->addNewCmdToQueue($priority,$newCmd);
        } // End addCmdToQueue2()

        /**
         * Find cmd corresponding to SQNAPS
         * Returns: Found matching cmd, or null
         *  'lastSent' is true if cmd is the last sent one for this zigate.
         */
        function getCmd($zgId, $sqnAps, &$lastSent = false) {
            $zg = $this->zigates[$zgId];

            foreach (range(priorityMin, priorityMax) as $prio) {
                foreach ($zg['cmdQueue'][$prio] as $cmdIdx => $cmd) {
                    if ($cmd['sqnAps'] == $sqnAps) {
                        // Is it the last sent cmd ?
                        if (($cmdIdx == 0) && ($prio == $this->zigates[$zgId]['sentPri']))
                            $lastSent = true;
                        return $this->zigates[$zgId]['cmdQueue'][$prio][$cmdIdx];
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
        function sendCmdToZigate($dest, $cmd, $datas) {
            // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
            // SVP ne pas enlever ce code c est tres utile pour le debug et verifier les commandes envoyées sur le port serie.
            if (0) {
                $f = fopen("/var/www/html/log/toto","w");
                $this->writeToDest($f, $dest, $cmd, $datas);
                fclose($f);
            }

            cmdLog('debug', '  sendCmdToZigate(Dest='.$dest.', cmd='.$cmd.', datas='.$datas.")", $this->debug['sendCmdToZigate']);

            $zgId = substr($dest, 7);
            $this->zgId = $zgId;
            // $destSerial = config::byKey('AbeilleSerialPort'.$this->zgId, 'Abeille', '1', 1);
            $destSerial = $this->zigates[$zgId]['port'];

            // Test should not be needed as we already tested in addCmdToQueue2
            if (!$this->zgGetEnable()) {
                cmdLog("debug", "  Zigate ".$this->zgId." (".$destSerial.") disabled => ignoring cmd ".$cmd.'-'.$datas);
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
            // cmdLog("debug", __FUNCTION__." AbeilleCmdQueue constructor, zigates:".json_encode($this->zigates), $this->debug["AbeilleCmdClass"] );
            $zgTxt = '';
            foreach ($this->zigates as $zgId => $zg) {
                $available = $zg['available'] ? "yes" : "NO";
                $zgTxt .= "/".$available;
            }
            cmdLog("debug", "  Zg (avail): ".$zgTxt);


            $queuesTxt = "";
            if (isset($this->tempoMessageQueue)) {
                $queuesTxt .= "tempo=".count( $this->tempoMessageQueue );
            }

            foreach ($this->zigates as $zgId => $zg) {
                foreach ( range( priorityMin, priorityMax) as $prio ) {
                    $queuesTxt .= ", Queue[".$zgId."][".$prio."]=".count($zg['cmdQueue'][$prio]);

                    if ($zgTxt != "")
                        $zgTxt .= ", ";
                    $zgTxt .= "zg".$zgId."=on";
                }
            }

            cmdLog("debug", "Status, queues : ".$queuesTxt);

        }

        function processCmdQueues() {
            // cmdLog("debug", __FUNCTION__." Begin");
            // cmdLog('debug', '  processCmdQueues() zigates='.json_encode($this->zigates));

            foreach ($this->zigates as $zgId => $zg) {
                // cmdLog("debug", __FUNCTION__." zgId: ".$zgId);
                $this->zgId = $zgId;

                if (!$this->zgGetEnable())      continue; // Disabled
                if (!$this->zgGetAvailable())   continue;  // Not free

                foreach( range( priorityMin, priorityMax) as $priority ) {
                    if ($this->checkCmdToSendInTheQueue($priority)) {
                        cmdLog('debug', "processCmdQueues()");

                        $this->zgSetNotAvailable();     // Zigate no longer free
                        $this->zgSetSentPri($priority); // Keep the last queue used to send a cmd to this Zigate

                        $cmd = $this->zgGetQueueFirstMessage($priority); // Takes first cmd
                        cmdLog('debug', "  cmd=".json_encode($cmd));
                        if ($cmd['status'] != '') {
                            cmdLog('debug', "  WARNING: Unexpected cmd status '".$cmd['status']."'");
                        }

                        $this->sendCmdToZigate($cmd['dest'], $cmd['cmd'], $cmd['datas']);

                        $this->zgTagSentQueueFirstMessage($priority);

                        if (isset($GLOBALS["dbgMonitorAddr"]) && ($cmd['addr'] != "") && ($GLOBALS["dbgMonitorAddr"] != "") && !strncasecmp($cmd['addr'], $GLOBALS["dbgMonitorAddr"], 4))
                            monMsgToZigate($cmd['addr'], $cmd['cmd'].'-'.$cmd['datas']); // Monitor this addr ?
                    }
                }

                // cmdLog('debug', "             processCmdQueues() - available: ".$this->zgGetAvailable());

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
                //             cmdLog('debug', 'processCmdQueues(): APDU>2 => send delayed');
                //             continue; // Will retry later
                //         }
                //         if ($this->zigateNPDU[$zgId] > 7) {
                //             cmdLog('debug', 'processCmdQueues(): NPDU>7 => send delayed');
                //             continue; // Will retry later
                //         }
                //     }
                // }

                // Ok let's get cmd and send it to Zigate
                // $cmd = array_shift($queue); // Get first cmd

                // $this->zigateAvailable[$zgId] = 0; // Zigate no longer free
                // $this->zigateSentPri[$zgId] = $queuePri;

            }

            // cmdLog('debug', '  processCmdQueues() zigates='.json_encode($this->zigates));
            // cmdLog("debug", __FUNCTION__." End");
        }

        // Process 8000, 8012 or 8702 messages
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
                        logMessage('debug', 'processAcks() ERROR: msg TOO BIG ignored: '.$msgJson);
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

                // cmdLog("debug", "  LA=".json_encode($this->zigates));
                //$zg = &$this->zigates[$zgId];
                // cmdLog("debug", "processAcks(): type=".$msg['type'], $this->debug['processAcks']);

                // Tcharp38 TODO: This msg should be not passed thru this queue.
                if ($msg['type'] == "8010") {
                    cmdLog("debug", "  8010 msg: FwVersion=".$msg['major']."-".$msg['minor']);
                    if ($msg['major'] == '0005')
                        $this->setFwVersion($zgId, 2);
                    else
                        $this->setFwVersion($zgId, 1, hexdec($msg['minor']));
                    continue;
                }

                // TODO: To be revisited. Ex: 8000 then 801x ignored
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
                    $this->zgSetnPDU(hexdec($nPDU));
                } else
                    $nPDU = "?";

                if (isset($msg['aPDU'])) {
                    $aPDU = $msg['aPDU'];
                    $this->zgSetaPDU(hexdec($aPDU));
                } else
                    $aPDU = "?";

                /* ACK or no ACK ?
                   In all cases expecting 8000 as first last sent cmd ack.
                   If ackAps is 1 (addrMode 02 or 03), zigate is released after
                     - 8702 => device did not ack (got 8000 then 8702)
                     - 8011 (following 8012) => device acknowledged cmd (got 8000, 8012, then 8011)
                   If ackAps is 0, zigate is released after 8000 msg
                 */

                unset($removeCmd); // Unset in case set in previous msg
                if ($msg['type'] == "8000") {

                    // Checking sent cmd vs received ack misalignment
                    if ( !$this->checkCmdToSendInTheQueue($this->zgGetSentPri()) ) {
                        // cmdLog("debug", $m." => ignored as queue is empty");
                        continue;
                    }

                    $cmd = $this->zgGetQueueFirstMessage($this->zgGetSentPri());

                    // Checking sent cmd vs received ack misalignment
                    if ($msg['packetType'] != $cmd['cmd']) {
                        cmdLog("debug", "  8000 => ignored as packets are different: packetType: ".$msg['packetType']." cmd: ".$cmd['cmd']);
                        continue;
                    }

                    // Storing SQN APS. This is key to identify cmd linked to msg. Might not be last sent except for 8000.
                    $pri = $this->zgGetSentPri();
                    $this->zigates[$zgId]['cmdQueue'][$pri][0]['sqn'] = $msg['sqn']; // Internal SQN
                    $this->zigates[$zgId]['cmdQueue'][$pri][0]['sqnAps'] = $sqnAps; // Network SQN

                    if ($msg['status'] == "00") {

                        if ($cmd['ackAps']) continue; // On this command I m waiting for APS ACK.
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
                    else if (in_array($msg['status'], ['01', '02', '03', '05'])) {
                        // Status is: bad param, unhandled, failed (?), stack already started
                        // $cmd['status'] = '8000';
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

                    // If ACK is requested but failed, removing cmd or it will lead to cmd timeout.
                    // Note: This is done only if cmd == last sent.
                    if ($cmd['ackAps'] && $lastSent)
                        $removeCmd = true;
                }

                else if ($msg['type'] == "8702") {
// cmdLog('debug', '  zigates='.json_encode($this->zigates));
                    $cmd = $this->getCmd($zgId, $msg['sqnAps'], $lastSent);
                    if ($cmd == null) {
                        cmdLog('debug', '  Corresponding cmd not found.');
                        // Note: This can appear for cmds not sent from zigate, but received by zigate (ex: request cluster 000A/time from device)
                        continue;
                    }

                    // If ACK is requested but failed, removing cmd or it will lead to cmd timeout.
                    // Note: This is done only if cmd == last sent.
                    if ($cmd['ackAps'] && $lastSent)
                        $removeCmd = true;
                }

                // Removing last sent cmd
                if (isset($removeCmd)) {
                    if ($removeCmd) {
                        // cmdLog('debug', '                 queue before='.json_encode($this->zgGetQueue($this->zgGetSentPri())));

                        // array_shift($this->zigates[$zgId]['cmdQueueHigh']); // Removing cmd
                        cmdLog('debug', '  Removing cmd from queue');
                        $this->removeFirstCmdFromQueue($this->zgGetSentPri());

                        // cmdLog('debug', '                 queue after='.json_encode($this->zgGetQueue($this->zgGetSentPri())));
                        $this->zgSetAvailable(); // Zigate is free again
                    }
                }
            }

            // cmdLog("debug", "  ".count($this->cmdQueue[$zgId])." remaining pending commands", $this->debug['processAcks']);
            // cmdLog("debug", "  LA23=".json_encode($this->zigates));
            // cmdLog("debug", __FUNCTION__." End");

        } // End processAcks()

        // Check zigate status which may be blocked by unacked sent cmd
        function zigateAckCheck() {
            foreach ($this->zigates as $zgId => $zg) {

                $this->zgId = $zgId;

                if (!$this->zgGetEnable())
                    continue; // This zigate is disabled/unconfigured
                if ($this->zgGetAvailable())
                    continue; // Zigate is ON & available => nothing to check

                // $zg = &$this->zigates[$zgId];
                // if ($zg['sentPri'] == PRIO_HIGH) {
                //     $queue = $zg['cmdQueueHigh'];
                // } else {
                //     $queue = $zg['cmdQueue'];
                // }

                $cmd = $this->zgGetQueueFirstMessage($this->zgGetSentPri());
                if ($cmd['sentTime'] + 2 > time())
                    continue; // 2sec timeout not reached yet

                cmdLog("debug", "zigateAckCheck(): WARNING: Zigate".$zgId." cmd ".$cmd['cmd']." TIMEOUT (SQN=".$cmd['sqn'].", SQNAPS=".$cmd['sqnAps'].") => Considering zigate available.");
                $this->zgSetAvailable();

                $this->removeFirstCmdFromQueue($this->zgGetSentPri()); // Removing blocked cmd
                // array_shift($this->zigates[$zgId]['cmdQueueHigh']);


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

        /* Collect & treat other messages for AbeilleCmd */
        function collectAllOtherMessages() {
            $queue = $this->queueXToCmd;
            $msg = NULL;
            $msgMax = 512;
            if (msg_receive($queue, 0, $msgType, $msgMax, $msg, true, MSG_IPC_NOWAIT, $errCode) == false) {
                if ($errCode == 7) {
                    msg_receive($queue, 0, $msgType, $msgMax, $msg, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                    logMessage('debug', 'collectAllOtherMessages() ERROR: msg TOO BIG ignored.');
                } else if ($errCode != 42) // 42 = No message
                    logMessage("error", "collectAllOtherMessages() ERROR ".$errCode." on queue 'xToCmd'");
                return;
            }

            cmdLog("debug", "Msg from 'xToCmd': ".$msg['topic']." => ".$msg['payload'], $this->debug['AbeilleCmdClass']);
            $topic = $msg['topic'];
            $payload = $msg['payload'];
            // $message->priority = $msg_priority;
            $this->prepareCmd($topic, $payload);
        }
    }
?>