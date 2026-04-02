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

    define('MAX_INFLIGHT', 3); // Max number of in-flight commands per Zigate
    define('QUARANTINE_DURATION', 900); // 15 minutes quarantine for noack devices
    if (isset($dbgMonitorAddr) && ($dbgMonitorAddr != ""))
        include_once __DIR__.'/../php/AbeilleMonitor.php'; // Tracing monitor for debug purposes

    class AbeilleCmdQueue extends AbeilleCmdPrepare {

        // public $queueParserToCmdMax;
        // public $queueParserToCmdAck;
        // public $queueParserToCmdAckMax;
        // public $tempoMessageQueue;

        function __construct($debugLevel='debug') {
            // cmdLog("debug", "AbeilleCmdQueue constructor start", $this->debug["AbeilleCmdClass"]);
            // cmdLog("debug", "Recuperation des queues de messages", $this->debug["AbeilleCmdClass"]);

            // $abQueues = $GLOBALS['abQueues'];
            // $this->queueParserToCmdAck      = msg_get_queue($abQueues["parserToCmdAck"]["id"]);
            // $this->queueParserToCmdAckMax   = $abQueues["parserToCmdAck"]["max"];
            // $this->queueXToCmd              = msg_get_queue($abQueues["xToCmd"]["id"]);
            // $this->queueXToCmdMax           = $abQueues["xToCmd"]["max"];

            // $this->tempoMessageQueue = array();

            // $GLOBALS['zigates'] = array();
            // cmdLog("debug", "AbeilleCmdQueue constructor");
            // for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            //     $GLOBALS['zigates'][$zgId] = $this->initNewZigateDefault($zgId);
            //     cmdLog("debug", "  zg".$zgId.'='.json_encode($GLOBALS['zigates'][$zgId]), $this->debug["AbeilleCmdClass"] );
            // }

            // Used to know which Zigate we are working on right now. Sort of global variable. Would have been better to have queue objects and not queue array. Perhaps in the futur could improve the code...
            // $this->zgId = 1;

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

        public function msgToAbeille($topic, $payload) {

            $msg = array();
            $msg['topic']   = $topic;
            $msg['payload'] = $payload;
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

            global $queueXToAbeille;
            if (msg_send($queueXToAbeille, 1, $msgJson, false, false, $errCode) == false) {
                cmdLog('debug', "msgToAbeille() ERROR {$errCode}");
            }
        }

        // Tempo queue: filling process
        public function addTempoCmdAbeille($topic, $msg, $priority) {
            list($topic, $param) = explode('&', $topic);
            $topic = str_replace( 'Tempo', '', $topic);

            list($timeTitle, $timeStr) = explode('=', $param);

            global $tempoMessageQueue;
            // cmdLog('debug', 'addTempoCmdAbeille(): Queue BEFORE='.json_encode($tempoMessageQueue));
            $tempoMessageQueue[] = array(
                'time' => intval($timeStr),
                'priority' => $priority,
                'topic' => $topic,
                'params' => $msg
            );
            // cmdLog('debug', 'addTempoCmdAbeille(): Queue AFTER='.json_encode($tempoMessageQueue));
            $count = count($tempoMessageQueue);
            cmdLog('debug', "  Added msg to 'Tempo' queue. Count=$count");

            // Check
            if (count($tempoMessageQueue) > 50) {
                cmdLog('info', 'Il y a plus de 50 messages dans le queue tempo.');
            }
        }

        // Tempo queue: reading process
        public function execTempoCmdAbeille() {

            global $tempoMessageQueue;
            $count = count($tempoMessageQueue);
            if ($count == 0)
                return;

            // cmdLog('debug', "execTempoCmdAbeille(), Count=$count, Queue=".json_encode($tempoMessageQueue));
            $now = time();
            // foreach ($tempoMessageQueue as $msgIdx => $msg) {
            //     if ($msg['time'] > $now)
            //         continue;

            //     $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
            //     cmdLog("debug", "  Queuing Tempo cmd for execution: idx=$msgIdx, msg=$msgJson");
            //     cmdLog('debug', "execTempoCmdAbeille() BEFORE - Count=$count, tempoMessageQueue=".json_encode($tempoMessageQueue));
            //     $this->sendToCmd($msg['priority'], $msg['topic'], $msg['params']);
            //     array_splice($tempoMessageQueue, $msgIdx, 1);
            //     $count = count($tempoMessageQueue);
            //     cmdLog('debug', "execTempoCmdAbeille() AFTER - Count=$count, tempoMessageQueue=".json_encode($tempoMessageQueue));
            // }

            for ($msgIdx = 0; $msgIdx < $count; ) {
                $msg = $tempoMessageQueue[$msgIdx];

                // Memory leak fix: remove expired commands (older than 5 minutes)
                // This prevents unbounded queue growth if commands are never dispatched
                if ($msg['time'] + 300 < $now) {
                    cmdLog("debug", "  Removing expired tempo cmd (>5min old): idx=$msgIdx, topic=".$msg['topic']);
                    array_splice($tempoMessageQueue, $msgIdx, 1);
                    $count = count($tempoMessageQueue);
                    continue;
                }

                if ($msg['time'] > $now) {
                    $msgIdx++;
                    continue;
                }

                $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
                cmdLog("debug", "  Queuing Tempo cmd for execution: idx=$msgIdx, msg=$msgJson");
                $this->sendToCmd($msg['priority'], $msg['topic'], $msg['params']);
                array_splice($tempoMessageQueue, $msgIdx, 1);
                $count = count($tempoMessageQueue);
            }
        }

        public function sendToCmd($priority, $topic, $payload) {
            // global $abQueues;
            // $queue = msg_get_queue($abQueues['xToCmd']['id']);

            $msg = array(
                'priority' => $priority,
                'topic' => $topic,
                'payload' => $payload,
            );
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

            global $queueXToCmd;
            if (msg_send($queueXToCmd, 1, $msgJson, false, false) == false) {
                cmdLog('debug', '  sendToCmd() ERROR: Could not add message '.$msgJson.' to queue xToCmd');
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
        public static function pushZigateCmd($zgId, $pri, $zgCmd, $payload, $repeat, $addr, $addrMode, $begin = false) {
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
                'status'    => '', // '', 'SENT'
                'try'       => maxRetryDefault + 1, // Number of send attempts
                'sentTime'  => 0, // For lost cmds timeout
                // Timeout: Zigate has 7s internal timeout when ACK
                'timeout'   => $ackAps ? 8 : 4, // Cmd expiration time
                'sqn'       => '', // Zigate SQN
                'sqnAps'    => '', // Network SQN
                'ackAps'    => $ackAps, // True if ACK, false else => OBSOLETE. Replaced by 'waitFor'
                'waitFor'   => $ackAps ? "ACK": "8000",
                'repeat'    => $repeat, // Repeat cmd until ACKed
            );

            // Abeille PDM restore cmd can be slow
            if ($zgCmd == "AB02") {
                $newCmd['timeout'] = 120; // TODO: Should be relative to data size
                $newCmd['waitFor'] = "AB03";
            }

            // LQI request to Zigate will not generate any ACK
            else if (($zgCmd == "004E") && ($addr == "0000"))
                $newCmd['waitFor'] = "8000";

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
        public static function addCmdToQueue2($priority, $net, $zgCmd, $payload = '', $addr = '', $addrMode = null, $repeat = 0) {
            cmdLog("debug", "    addCmdToQueue2(Pri={$priority}, Net={$net}, ZgCmd={$zgCmd}, Payload={$payload}, Addr={$addr}, AddrMode={$addrMode}, Repeat={$repeat})");

            $zgId = substr($net, 7);
            // $this->zgId = $zgId;

            // Checking min parameters
            if (!isset($GLOBALS['zigates'][$zgId])) {
                cmdLog("debug", "    No Zigate {$zgId} => cmd IGNORED");
                return;
            }
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
                cmdLog("error", "    Commande Zigate '{$zgCmd}' ignorée: Taille données impaire");
                return;
            }

            // Troughput optimisation: Ignoring if same cmd/payload is already in the pipe
            // TODO: May need to take care about 'toggle' case.
            // TODO: Is it worth removing old pendind cmd and adding new one ? Might allow to keep latest cmds order.
            foreach (range(priorityMax, priorityMin) as $prio2) {
                $pendingCmds = $GLOBALS['zigates'][$zgId]['cmdQueue'][$prio2];
                foreach($pendingCmds as $pendCmd) {
                    if ($pendCmd['cmd'] != $zgCmd)
                        continue;
                    if ($pendCmd['datas'] == $payload) {
                        cmdLog('debug', "    Same cmd already pending with priority {$prio2} => ignoring");
                        return;
                    }
                }
            }

            // Quarantine check: if device is quarantined, drop command entirely
            if ($addr != '') {
                $dev = &getDevice($net, $addr);
                if (isset($dev['quarantineUntil']) && ($dev['quarantineUntil'] > time())) {
                    $remaining = $dev['quarantineUntil'] - time();
                    cmdLog('debug', "    Device {$addr} is QUARANTINED ({$remaining}s remaining) => cmd DROPPED");
                    return;
                }
                // Clear expired quarantine
                if (isset($dev['quarantineUntil']) && ($dev['quarantineUntil'] <= time())) {
                    unset($dev['quarantineUntil']);
                }

                // If device TX status is 'NO ACK', moving to low priority queue
                if ($priority != PRIO_LOW) {
                    if (isset($dev['zigbee']['txStatus']) && ($dev['zigbee']['txStatus'] == 'noack')) {
                        cmdLog('debug', "    Device is NO-ACK: Moving cmd to low priority queue");
                        $priority = PRIO_LOW;
                    }
                }
            }

            // $this->incStatCmd($cmd);

            AbeilleCmdQueue::pushZigateCmd($zgId, $priority, $zgCmd, $payload, $repeat, $addr, $addrMode);

            // TEST
            // if ($addr == '85B1') {
            //     logMessage("debug", "LA4 cmdQueue=".json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'], JSON_UNESCAPED_SLASHES));
            // }
            // END TEST

            // Display statistics
            $queuesTxt = '';
            foreach (range(priorityMax, priorityMin) as $prio) {
                if ($queuesTxt != '')
                    $queuesTxt .= ', ';
                $queuesTxt .= "Pri{$prio}=".count($GLOBALS['zigates'][$zgId]['cmdQueue'][$prio]);
            }
            cmdLog('debug', '    Zg '.$zgId.' queues: '.$queuesTxt);
            if (count($GLOBALS['zigates'][$zgId]['cmdQueue'][$priority]) > 50) {
                cmdLog('debug', '    WARNING: More than 50 pending messages in zigate'.$zgId.' cmd queue: '.$priority);
            }
        } // End addCmdToQueue2()

        /**
         * Remove a sent command from queue and adjust sentSlots indices.
         */
        function removeSentCmd($zgId, $pri, $idx, $slotKey) {
            cmdLog('debug', "  removeSentCmd(zgId={$zgId}, Pri={$pri}, Idx={$idx}, Slot={$slotKey})");
            array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$pri], $idx, 1);
            array_splice($GLOBALS['zigates'][$zgId]['sentSlots'], $slotKey, 1);
            // Adjust remaining slot indices that pointed into the same priority queue
            foreach ($GLOBALS['zigates'][$zgId]['sentSlots'] as &$sl) {
                if ($sl['pri'] == $pri && $sl['idx'] > $idx)
                    $sl['idx']--;
            }
            unset($sl);
        }

        /**
         * Find cmd (by ref) corresponding to SQNAPS
         * Returns: Found matching cmd, or empty array
         */
        function &getCmd($zgId, $sqnAps) {
            foreach (range(priorityMax, priorityMin) as $prio) {
                foreach ($GLOBALS['zigates'][$zgId]['cmdQueue'][$prio] as $cmdIdx => $cmd) {
                    if ($cmd['sqnAps'] == $sqnAps) {
                        return $GLOBALS['zigates'][$zgId]['cmdQueue'][$prio][$cmdIdx];
                    }
                }
            }

            static $error = [];
            return $error;
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

        // /**
        //  * sendCmdToZigate()
        //  *
        //  * connect to zigate and pass the commande on serial link
        //  *
        //  * @param dest  Zigbee network (ex: 'Abeille1')
        //  * @param cmd   zigate commande as per zigate API
        //  * @param data  data for the cmd
        //  *
        //  * @return none
        //  */
        // function sendCmdToZigate($dest, $addr, $cmd, $datas) {
        //     // Ecrit dans un fichier toto pour avoir le hex envoyés pour analyse ou envoie les hex sur le bus serie.
        //     // SVP ne pas enlever ce code c est tres utile pour le debug et verifier les commandes envoyées sur le port serie.
        //     // if (0) {
        //     //     $f = fopen("/var/www/html/log/toto","w");
        //     //     $this->writeToDest($f, $dest, $cmd, $datas);
        //     //     fclose($f);
        //     // }

        //     cmdLog('debug', '  sendCmdToZigate(Dest='.$dest.', addr='.$addr.', cmd='.$cmd.', datas='.$datas.")", $this->debug['sendCmdToZigate']);

        //     $zgId = substr($dest, 7);
        //     // $this->zgId = $zgId;
        //     $destSerial = $GLOBALS['zigates'][$zgId]['port'];

        //     // Test should not be needed as we already tested in addCmdToQueue2
        //     if (!$GLOBALS['zigates'][$zgId]['enabled']) {
        //         cmdLog("debug", "  Zigate ".$zgId." (".$destSerial.") disabled => ignoring cmd ".$cmd.'-'.$datas);
        //         return;
        //     }

        //     // Note: Using file_exists() to avoid PHP warning when port issue.
        //     if (!file_exists($destSerial) || (($f = fopen($destSerial, "w")) == false)) {
        //         // cmdLog("error", "  Port '$destSerial' non accessible. Commande '$cmd' non écrite.");
        //         cmdLog("debug", "  '$destSerial' port not accessible => '$cmd' cmd ignored.");
        //         return;
        //     }

        //     // cmdLog("debug", "  Writing to port ".$destSerial.': '.$cmd.'-'.$len.'-'.$datas, $this->debug['sendCmdToZigate']);
        //     $this->writeToDest($f, $destSerial, $cmd, $datas);
        //     fclose($f);
        // }

        /**
         * Display queues & zigate status.
         * Called every 30sec.
         */
        function displayStatus() {
            // cmdLog("debug", __FUNCTION__." AbeilleCmdQueue constructor, zigates:".json_encode($GLOBALS['zigates']), $this->debug["AbeilleCmdClass"] );
            // foreach ($GLOBALS['zigates'] as $zgId => $zg) {
            foreach ($GLOBALS['zigates'] as $zgId => $zg) {
                $inFlight = isset($zg['sentSlots']) ? count($zg['sentSlots']) : 0;
                $avail = $inFlight == 0 ? "idle" : "BUSY({$inFlight}/" . MAX_INFLIGHT . ")";

                $queuesTxt = '';
                foreach (range(priorityMax, priorityMin) as $prio) {
                    if ($queuesTxt != '')
                        $queuesTxt .= ', ';
                    $queuesTxt .= "Pri{$prio}=".count($GLOBALS['zigates'][$zgId]['cmdQueue'][$prio]);
                }

                cmdLog("debug", "Zg{$zgId} status: {$avail}, ".$queuesTxt);
            }

            global $tempoMessageQueue;
            if (isset($tempoMessageQueue))
                $tempoCount = count($tempoMessageQueue);
            else
                $tempoCount = 0;
            cmdLog("debug", "Tempo status: count=".$tempoCount);
        }

        // Check if any pending cmd to be sent to Zigate
        function processCmdQueues() {
            // cmdLog("debug", __FUNCTION__." Begin");
            // cmdLog('debug', '  processCmdQueues() zigates='.json_encode($GLOBALS['zigates']));

            foreach ($GLOBALS['zigates'] as $zgId => $zg) {
                // cmdLog("debug", __FUNCTION__." zgId=".$zgId.", zg=".json_encode($zg));

                if (!$zg['enabled'])
                    continue; // Disabled

                // Check timeouts on in-flight commands in sentSlots (reverse loop for safe splice)
                $now = time();
                for ($s = count($zg['sentSlots']) - 1; $s >= 0; $s--) {
                    $slot = $zg['sentSlots'][$s];
                    if (!isset($zg['cmdQueue'][$slot['pri']][$slot['idx']])) {
                        // Orphan slot - remove it
                        array_splice($GLOBALS['zigates'][$zgId]['sentSlots'], $s, 1);
                        continue;
                    }
                    $cmd = $zg['cmdQueue'][$slot['pri']][$slot['idx']];
                    if ($cmd['status'] == '' || $cmd['sentTime'] + $cmd['timeout'] > $now)
                        continue; // Not sent yet or timeout not reached

                    cmdLog("debug", "WARNING: processCmdQueues(): Zigate".$zgId." cmd ".$cmd['cmd']." ".$cmd['timeout']."s TIMEOUT (SQN=".$cmd['sqn'].", SQNAPS=".$cmd['sqnAps'].") => slot freed");
                    $this->removeSentCmd($zgId, $slot['pri'], $slot['idx'], $s);
                }

                // Also cleanup SENT commands waiting for 8011 that have timed out (no longer in sentSlots)
                foreach (range(priorityMax, priorityMin) as $cleanPri) {
                    for ($ci = count($GLOBALS['zigates'][$zgId]['cmdQueue'][$cleanPri]) - 1; $ci >= 0; $ci--) {
                        $cmd = $GLOBALS['zigates'][$zgId]['cmdQueue'][$cleanPri][$ci];
                        if ($cmd['status'] != 'SENT' || $cmd['sqnAps'] == '')
                            continue; // Not a sent-and-waiting-for-8011 command
                        if ($cmd['sentTime'] + $cmd['timeout'] <= $now) {
                            cmdLog("debug", "WARNING: processCmdQueues(): Zigate".$zgId." cmd ".$cmd['cmd']." to ".$cmd['addr']." 8011 TIMEOUT (SQNAPS=".$cmd['sqnAps'].") => removed from queue");
                            array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$cleanPri], $ci, 1);
                            // Adjust sentSlot indices
                            foreach ($GLOBALS['zigates'][$zgId]['sentSlots'] as &$sl) {
                                if ($sl['pri'] == $cleanPri && $sl['idx'] > $ci)
                                    $sl['idx']--;
                            }
                            unset($sl);
                        }
                    }
                }

                // Check if we can send more commands
                if (count($GLOBALS['zigates'][$zgId]['sentSlots']) >= MAX_INFLIGHT) {
                    continue; // All in-flight slots occupied
                }
                if (isset($zg['availTime']) && ($zg['availTime'] > microtime(true))) {
                    continue; // Zigate temporarily unavailable (soft reset, retry delay)
                }
                unset($GLOBALS['zigates'][$zgId]['availTime']);

                // Throughput regulation
                // $zg['tp_time'] gives time (in us) when Zigate can be considered available again.
                $mt = microtime(true);
                if (isset($zg['tp_time']) && ($zg['tp_time'] > $mt))
                    $regulation = "Throughput";
                // NDPU regulation (NDPU too high leads to extended error due to lack of resources)
                else if ($zg['nPDU'] > 7)
                    $regulation = "NDPU";
                else
                    $regulation = '';

                // Collect addresses that already have an in-flight command
                $inFlightAddrs = [];
                foreach (range(priorityMax, priorityMin) as $p) {
                    foreach ($GLOBALS["zigates"][$zgId]["cmdQueue"][$p] as $c) {
                        if ($c['status'] == 'SENT' && $c['addr'] != '')
                            $inFlightAddrs[$c['addr']] = true;
                    }
                }

                // Looking for a cmd to send in HIGH to LOW priority order
                unset($sendIdx);
                foreach (range(priorityMax, priorityMin) as $priority) {
                    $count = count($zg['cmdQueue'][$priority]);
                    if ($count == 0)
                        continue; // Queue empty

                    // Find first unsent command in this priority queue
                    foreach ($zg['cmdQueue'][$priority] as $cmdIdx => $cmd) {
                        if ($cmd['status'] != '')
                            continue; // Already SENT, waiting for ACK - skip

                        // Per-device limit: max 1 in-flight per equipment
                        if ($cmd['addr'] != '' && isset($inFlightAddrs[$cmd['addr']])) {
                            continue; // This device already has a command in-flight
                        }

                        if ($cmd['zgOnly'] || ($regulation == '')) {
                            $sendIdx = $cmdIdx;
                            break;
                        }
                        // Regulation active, this cmd is blocked. Look for zgOnly or another device
                        cmdLog('debug', "processCmdQueues(): ZgId={$zgId}, Pri={$priority}/Idx={$cmdIdx}, Cmd=".$cmd['cmd']." => '{$regulation}' regulation");
                        foreach ($zg['cmdQueue'][$priority] as $cmdIdx2 => $cmd2) {
                            if ($cmdIdx2 <= $cmdIdx)
                                continue;
                            if ($cmd2['status'] != '')
                                continue;
                            if (!$cmd2['zgOnly'])
                                continue;
                            $sendIdx = $cmdIdx2;
                            break;
                        }
                        break; // Stop searching this priority
                    }

                    if (isset($sendIdx))
                        break;
                } // End priorities loop

                // Finally something to send for this Zigate ?
                if (isset($sendIdx)) {
                    cmdLog('debug', "processCmdQueues(): ZgId={$zgId}, Pri={$priority}/Idx={$sendIdx}, NPDU=".$zg['nPDU'].", APDU=".$zg['aPDU']);

                    $cmd = $zg['cmdQueue'][$priority][$sendIdx];

                    // $this->sendCmdToZigate($cmd['dest'], $cmd['addr'], $cmd['cmd'], $cmd['datas']);
                    cmdLog('debug', '  Sending: Dest='.$cmd['dest'].', addr='.$cmd['addr'].', zgCmd='.$cmd['cmd'].', datas='.$cmd['datas']);
                    $destSerial = $GLOBALS['zigates'][$zgId]['port'];
                    // Note: Using file_exists() to avoid PHP warning when port issue.
                    if (!file_exists($destSerial) || (($f = fopen($destSerial, "w")) == false)) {
                        cmdLog("debug", "  '$destSerial' port not accessible => '$cmd' cmd ignored.");
                        continue; // Move to next Zigate
                    }
                    $this->writeToDest($f, $destSerial, $cmd['cmd'], $cmd['datas']);
                    fclose($f);

                    // cmdLog('debug', "  CmdBEFORE=".json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][$sendIdx]));
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][$sendIdx]['status'] = "SENT";
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][$sendIdx]['sentTime'] = time();
                    $GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][$sendIdx]['try']--;
                    // cmdLog('debug', "  CmdAFTER=".json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'][$priority][$sendIdx]));

                    $GLOBALS['zigates'][$zgId]['sentSlots'][] = [
                        'pri' => $priority,
                        'idx' => $sendIdx,
                    ]; // Track in-flight command
                    // $GLOBALS['zigates'][$zgId]['tp_time'] = microtime(true) + 0.1; // Next avail time in 100ms
                    if ($cmd['cmd'] == "0011") // Special case: soft reset
                        $GLOBALS['zigates'][$zgId]['availTime'] = microtime(true) + 1.0; // Zg will be free in 1sec
                    else
                        $GLOBALS['zigates'][$zgId]['tp_time'] = microtime(true) + 0.1; // Next avail time in 100ms

                    if (isset($GLOBALS["dbgMonitorAddr"]) && ($cmd['addr'] != "") && ($GLOBALS["dbgMonitorAddr"] != "") && !strncasecmp($cmd['addr'], $GLOBALS["dbgMonitorAddr"], 4))
                        monMsgToZigate($cmd['addr'], $cmd['cmd'].'-'.$cmd['datas']); // Monitor this addr ?

                    // cmdLog('debug', "  Zigate=".json_encode($GLOBALS['zigates'][$zgId]));
                }
            } // End zigates loop
        }

        // Process high priority queue from parser (8000, 8011, 8012 or 8702 messages)
        function processAcksQueue() {

            // cmdLog("debug", __FUNCTION__." Begin");

            global $queueParserToCmdAck, $queueParserToCmdAckMax;
            while (true) {
                $msgMax = $queueParserToCmdAckMax;
                if (msg_receive($queueParserToCmdAck, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == false) {
                    if ($errCode == 7) {
                        logMessage('error', 'processAcks() ERROR: msg TOO BIG ignored: '.$msgJson);
                        msg_receive($queueParserToCmdAck, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        continue;
                    } else if ($errCode != 42) // 42 = No message
                        cmdLog("debug", "processAcks() ERROR: msg_receive() err ".$errCode);
                    return;
                }

                cmdLog("debug", 'processAcks(): msg='.$msgJson);
                $msg = json_decode($msgJson, true);

                // Clear all pending messages for net/addr device.
                // This is useful when short addr changed, due to (multiple) device announce,
                // or device migrated to another network.
                if ($msg['type'] == "shortAddrChange") {
                    $oldNet = $msg['oldNet'];
                    $newNet = $msg['newNet'];
                    $oldAddr = $msg['oldAddr'];
                    $newAddr = $msg['newAddr'];
                    $ieee = $msg['ieee'];
                    cmdLog("debug", "  shortAddrChange: {$oldNet}/{$oldAddr} to {$newNet}/{$newAddr} (ieee=$ieee)");

                    // Remove any pending messages to be sent to old address
                    $zgId = substr($msg['oldNet'], 7);
                    clearPending($zgId, $oldAddr, $ieee);

                    // Update local infos
                    if (isset($GLOBALS['devices'][$oldNet]) && isset($GLOBALS['devices'][$oldNet][$oldAddr])) {
                        $GLOBALS['devices'][$newNet][$newAddr] = $GLOBALS['devices'][$oldNet][$oldAddr];
                        unset($GLOBALS['devices'][$oldNet][$oldAddr]);
                    }

                    // If address was monitored.. let's update too
                    if (isset($GLOBALS["dbgMonitorAddr"]) && ($GLOBALS["dbgMonitorAddr"] == $oldAddr))
                        $GLOBALS["dbgMonitorAddr"] = $newAddr;
                    continue;
                } // End type=='shortAddrChange'

                if ($msg['type'] == "clearPending") {
                    // Remove any pending messages to be sent to old address
                    $zgId = substr($msg['net'], 7);
                    clearPending($zgId, $msg['addr'], $msg['ieee']);
                    continue;
                } // End type=='clearPending'

                $zgId = substr($msg['net'], 7);
                // $this->zgId = $zgId;

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

                // Tcharp38 TODO: This msg should be not passed thru this priority queue.
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

                // Serial to parser receive channel is UP
                if ($msg['type'] == "rcvChanStatus") {
                    configureZigate($zgId); // Configure Zigate
                    continue;
                }

                // PDM restore response (Abeille's ABxx-yyyy specific FW)
                if ($msg['type'] == "AB03") {
                    cmdLog("debug", "  AB03 msg: ID=".$msg['id'].", Status=".$msg['status']);

                    $removeCmd = true;
                }

                /* ACK or no ACK ?
                   In all cases expecting 8000 as first last sent cmd ack.
                   If ackAps is true (addrMode 02 or 03), zigate is released after
                     - 8702 => Buffered. Does not mean anything. May finish sucessfully or not.
                     - 8012 => Message received by next hope.
                     - 8011 (following 8012) => device acknowledged (or not) cmd (got 8000, 8012, then 8011)
                   If ackAps is false, zigate is released after 8000 msg
                 */

                else if ($msg['type'] == "8000") {

                    // 8000 arrives in FIFO order - match to sentSlots[0]
                    if (empty($GLOBALS['zigates'][$zgId]['sentSlots'])) {
                        cmdLog("warning", "  Unexpected 8000 msg. No sent slots.");
                        continue;
                    }
                    $slot = $GLOBALS['zigates'][$zgId]['sentSlots'][0]; // FIFO front
                    $sentPri = $slot['pri'];
                    $sentIdx = $slot['idx'];

                    if (!isset($GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri][$sentIdx])) {
                        cmdLog("warning", "  8000: slot points to missing cmd (Pri={$sentPri}, Idx={$sentIdx})");
                        array_splice($GLOBALS['zigates'][$zgId]['sentSlots'], 0, 1);
                        continue;
                    }

                    cmdLog("debug", "  TEMP: cmds=".json_encode($GLOBALS['zigates'][$zgId]['cmdQueue'], JSON_UNESCAPED_SLASHES));
                    $cmd = &$GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri][$sentIdx];

                    // Checking sent cmd vs received ack misalignment
                    if ($msg['packetType'] != $cmd['cmd']) {
                        cmdLog("debug", "  8000 => ignored as packets are different: packetType=".$msg['packetType']." zgCmd=".$cmd['cmd']);
                        continue;
                    }

                    // Storing SQN APS. This is key to identify cmd linked to msg.
                    $cmd['sqn'] = $msg['sqn']; // Internal SQN
                    $cmd['sqnAps'] = $sqnAps; // Network SQN

                    $msgStatus = $msg['status'];
                    if ($msgStatus == "00") {

                        if ($cmd['waitFor'] == "ACK") {
                            // Zigate accepted the cmd. Free the in-flight slot
                            // but keep the cmd in cmdQueue (waiting for 8011 matched by sqnAps)
                            array_splice($GLOBALS['zigates'][$zgId]['sentSlots'], 0, 1);
                            continue;
                        }
                        if ($cmd['waitFor'] != "8000")
                            continue; // Need to wait for something else

                        $removeCmd = true;
                    }
                    else if (in_array($msgStatus, ['01', '02', '03', '05', '06', '14', '15'])) {
                        cmdLog("debug", "  WARNING: Zigate cmd failed (err {$msgStatus})");
                        $removeCmd = true;
                    }
                    else {
                        if ($cmd['try'] == 0) {
                            cmdLog("debug", "  WARNING: Cmd ".$cmd['cmd']." to ".$cmd['addr']." failed and too many retries.");
                            $removeCmd = true;
                        }
                        else {
                            cmdLog("debug", "  WARNING: Cmd ".$cmd['cmd']." failed (err {$msgStatus}). Will be retried ".$cmd['try']." time(s) max.");
                            $GLOBALS['zigates'][$zgId]['availTime'] = microtime(true) + 1.0;
                        }
                    }

                    if (isset($removeCmd) && $removeCmd) {
                        $this->removeSentCmd($zgId, $sentPri, $sentIdx, 0);
                        unset($removeCmd);
                    }
                }

                else if ($msg['type'] == "8011") {
                    $cmd = &$this->getCmd($zgId, $msg['sqnAps']); // By ref
                    if ($cmd === []) {
                        cmdLog('debug', '  Corresponding cmd not found.');
                        continue;
                    }
                    cmdLog('debug', "  cmd=".json_encode($cmd, JSON_UNESCAPED_SLASHES));

                    if ($cmd['waitFor'] == "ACK") {
                        $removeCmd = true;

                        $net = $msg['net'];
                        $addr = $msg['addr'];
                        $eq = &getDevice($net, $addr); // By ref
                        if ($eq === []) {
                            cmdLog('debug', "  WARNING: Unknown device: Net={$net} Addr={$addr}");
                        } else {
                            cmdLog('debug', "  eq=".json_encode($eq, JSON_UNESCAPED_SLASHES));
                            $newTxStatus = '';
                            if ($msg['status'] == '00') { // OK
                                if ($eq['zigbee']['txStatus'] !== 'ok')
                                    $newTxStatus = 'ok';
                                // Clear quarantine if device responds
                                if (isset($eq['quarantineUntil'])) {
                                    unset($eq['quarantineUntil']);
                                    cmdLog("info", "  Device {$addr} removed from quarantine (responded OK)");
                                }
                            } else { // NO ACK
                                if (isset($eq['zigbee']['rxOnWhenIdle']) && $eq['zigbee']['rxOnWhenIdle'] && ($eq['zigbee']['txStatus'] !== 'noack'))
                                    $newTxStatus = 'noack';

                                if (isset($cmd['repeat'])) {
                                    $repeat = $cmd['repeat'];
                                    if ($repeat != 0) {
                                        cmdLog("debug", "  Cmd ".$cmd['cmd']." failed (no ACK) but will be repeated.");
                                        $cmd['repeat'] -= 1;
                                        $cmd['status'] = ''; // Not sent
                                        $removeCmd = false; // Keep the cmd to be repeated
                                    } else {
                                        cmdLog("debug", "  Cmd ".$cmd['cmd']." failed (no ACK) even if Repeat={$repeat}. rxOnWhenIdle=".($eq['zigbee']['rxOnWhenIdle'] ?? 'unset'));
                                        // Quarantine: device unreachable after all retries
                                        if (isset($eq['zigbee']['rxOnWhenIdle']) && $eq['zigbee']['rxOnWhenIdle']) {
                                            $eq['quarantineUntil'] = time() + QUARANTINE_DURATION;
                                            cmdLog("info", "  Device {$addr} QUARANTINED for ".QUARANTINE_DURATION."s (no ACK after all retries)");
                                            // Persist quarantine to Jeedom DB
                                            $qMsg = array(
                                                'type' => 'eqTxStatusUpdate',
                                                'net' => $net,
                                                'addr' => $addr,
                                                'txStatus' => 'noack',
                                                'quarantineUntil' => $eq['quarantineUntil']
                                            );
                                            msgToAbeille($qMsg);
                                            // Drop all pending commands to this device from all queues
                                            for ($qp = priorityMax; $qp <= priorityMin; $qp++) {
                                                for ($qi = count($GLOBALS['zigates'][$zgId]['cmdQueue'][$qp]) - 1; $qi >= 0; $qi--) {
                                                    $qc = $GLOBALS['zigates'][$zgId]['cmdQueue'][$qp][$qi];
                                                    if ($qc['addr'] == $addr && $qc['status'] == '') {
                                                        cmdLog('debug', "  Dropping queued cmd to quarantined device {$addr}");
                                                        array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$qp], $qi, 1);
                                                        // Adjust sentSlot indices
                                                        foreach ($GLOBALS['zigates'][$zgId]['sentSlots'] as &$slAdj) {
                                                            if ($slAdj['pri'] == $qp && $slAdj['idx'] > $qi)
                                                                $slAdj['idx']--;
                                                        }
                                                        unset($slAdj);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($newTxStatus != '') {
                                $eq['zigbee']['txStatus'] = $newTxStatus;
                                $msg = array(
                                    'type' => 'eqTxStatusUpdate',
                                    'net' => $net,
                                    'addr' => $addr,
                                    'txStatus' => $newTxStatus,
                                    'quarantineUntil' => isset($eq['quarantineUntil']) ? $eq['quarantineUntil'] : 0
                                );
                                msgToAbeille($msg);
                                cmdLog2('debug', $addr, "  {$net}-{$addr} status changed to '{$newTxStatus}'");
                            }
                        }

                        // Remove the cmd from cmdQueue (slot was already freed by 8000 handler)
                        if ($removeCmd) {
                            $ackSqnAps = $cmd['sqnAps'];
                            // Find cmd in cmdQueue by sqnAps and remove it
                            $cmdRemoved = false;
                            foreach (range(priorityMax, priorityMin) as $p) {
                                foreach ($GLOBALS['zigates'][$zgId]['cmdQueue'][$p] as $ci => $c) {
                                    if (isset($c['sqnAps']) && $c['sqnAps'] == $ackSqnAps) {
                                        // Also check if there's still a sentSlot pointing to this cmd
                                        $slotFound = false;
                                        foreach ($GLOBALS['zigates'][$zgId]['sentSlots'] as $sk => $sl) {
                                            if ($sl['pri'] == $p && $sl['idx'] == $ci) {
                                                // Slot still exists (edge case) - use removeSentCmd
                                                $this->removeSentCmd($zgId, $p, $ci, $sk);
                                                $slotFound = true;
                                                break;
                                            }
                                        }
                                        if (!$slotFound) {
                                            // Normal case: slot already freed by 8000, just remove cmd
                                            array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$p], $ci, 1);
                                            // Adjust any sentSlot indices in same priority
                                            foreach ($GLOBALS['zigates'][$zgId]['sentSlots'] as &$sl2) {
                                                if ($sl2['pri'] == $p && $sl2['idx'] > $ci)
                                                    $sl2['idx']--;
                                            }
                                            unset($sl2);
                                        }
                                        $cmdRemoved = true;
                                        break 2;
                                    }
                                }
                            }
                            if (!$cmdRemoved)
                                cmdLog('debug', "  8011: Could not find cmd with sqnAps={$ackSqnAps} to remove");
                            unset($removeCmd);
                        }
                    }
                }

                else if ($msg['type'] == "8012") {
                    // 8012 msg used to get NPDU/APDU status updates only
                    continue;
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

                // Removal is now handled in each 8000/8011 handler via removeSentCmd()
            }

            // cmdLog("debug", "  ".count($this->cmdQueue[$zgId])." remaining pending commands", $this->debug['processAcks']);
            // cmdLog("debug", "  LA23=".json_encode($GLOBALS['zigates']));
            // cmdLog("debug", __FUNCTION__." End");

        } // End processAcks()

        // OBSOLETE: Part of processCmdQueues()
        // Check zigate status which may be blocked by unacked sent cmd
        // function checkZigatesStatus() {
        //     foreach ($GLOBALS['zigates'] as $zgId => $zg) {

        //         if (!$zg['enabled'])
        //             continue; // This zigate is disabled/unconfigured
        //         if ($zg['available'])
        //             continue; // Zigate is ON & available => nothing to check

        //         $sentPri = $zg['sentPri'];
        //         $sentIdx = $zg['sentIdx'];
        //         $cmd = $zg['cmdQueue'][$sentPri][$sentIdx];

        //         if ($cmd['status'] == '')
        //             continue; // Not sent yet

        //         $timeout = $cmd['timeout'];
        //         if ($cmd['sentTime'] + $timeout > time())
        //             continue; // Timeout not reached yet

        //         cmdLog("debug", "WARNING: checkZigatesStatus(): Zigate".$zgId." cmd ".$cmd['cmd']." {$timeout}s TIMEOUT (SQN=".$cmd['sqn'].", SQNAPS=".$cmd['sqnAps'].") => Considering zigate available.");
        //         // array_shift($GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri]);
        //         array_splice($GLOBALS['zigates'][$zgId]['cmdQueue'][$sentPri], $sentIdx, 1);
        //         $GLOBALS['zigates'][$zgId]['available'] = 1;
        //         unset($GLOBALS['zigates'][$zgId]['availTime']); // In case still present
        //     }
        // } // End checkZigatesStatus()

        /* Collect & treat other messages from 'xToCmd' queue. */
        function processXToCmdQueue() {
            global $queueXToCmd, $queueXToCmdMax;
            $msgMax = $queueXToCmdMax;
            if (msg_receive($queueXToCmd, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode) == false) {
                if ($errCode == 7) {
                    logMessage('error', 'processXToCmdQueue() ERROR: msg TOO BIG ignored.');
                    logMessage('debug', "  msg={$msgJson}");
                    msg_receive($queue, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                } else if ($errCode != 42) { // 42 = No message
                    logMessage("error", "processXToCmdQueue() ERROR ".$errCode." reading queue 'xToCmd'");
                    usleep(500000); // Small delay after error
                }
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
                    configureDevice($msg); // Configure device (execAtCreation)
                } else if ($msg['type'] == 'logLevelChanged') {
                    logLevelChanged($msg['level']);
                } else
                    cmdLog("error", "AbeilleCmd: Message inattendu: ".$msgJson);
            } else {
                // $prio = isset($msg['priority']) ? $msg['priority']: PRIO_NORM;
                // $topic = $msg['topic'];
                // $payload = $msg['payload'];
                // $this->prepareCmd($prio, $topic, $payload);
                $this->prepareCmd($msg);
            }
        }
    }
?>