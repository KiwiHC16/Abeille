<?php
    class AbeilleCmd extends cmd {
        /**
         * updateField()
         * Used to replace #xxx# fields with real value
         *
         * @param request to be modified
         * @return request modified
         */
        public static function updateField($dest, $cmd, $request, $_options=NULL) {
            $request2 = $request;
            $eqLogic = $cmd->getEqLogic();

            if (strpos($request2, "#addrGroup#") > 0) {
                $request2 = str_replace("#addrGroup#", $eqLogic->getConfiguration("Groupe"), $request2);
                // logMessage('debug', 'request - addGroup : '.$request2);
            }

            if (strpos($request2, "#GroupeEP") > 0) {
                $id = substr($request2, strpos($request2, "#GroupeEP") + strlen("#GroupeEP"), 1);
                $request2 = str_replace("#GroupeEP".$id."#", $eqLogic->getConfiguration("GroupeEP".$id), $request2);
                // logMessage('debug', 'request - GroupEP : '.$id.' - '.$request2);
                // $request .+ "TEST";
            }

            if (strpos($request2, '#onTime#') > 0) {
                $onTimeHex = sprintf("%04s", dechex($eqLogic->getConfiguration("onTime") * 10));
                $request2 = str_replace("#onTime#", $onTimeHex, $request2);
            }
            // logMessage('debug', 'request - onTime: '.$request2);

            if (strpos($request2, '#addrIEEE#') > 0) {
                $commandIEEE = $eqLogic->getConfiguration("IEEE",'none');
                if (strlen($commandIEEE)!=16) {
                    // logMessage('debug', 'Adresse IEEE de l equipement '.$cmd->getEqLogic()->getHumanName().' inconnue');
                    return true;
                }
                $request2 = str_replace('#addrIEEE#', $commandIEEE, $request2);
            }
            // logMessage('debug', 'request - addrIEEE: '.$request);

            if (strpos($request2, '#ZiGateIEEE#') > 0) {
                // Logical Id ruche de la forme: Abeille1/0000
                $rucheIEEE = Abeille::byLogicalId($dest.'/0000', 'Abeille')->getConfiguration("IEEE",'none');
                // logMessage('debug', 'Adresse IEEE de la ruche '.$rucheIEEE);
                if (strlen($rucheIEEE) != 16) {
                    // logMessage('debug', 'Adresse IEEE de la ruche '.$cmd->getEqLogic()->getHumanName().' inconnue');
                    // return true;
                } else
                    $request2 = str_replace('#ZiGateIEEE#', $rucheIEEE, $request2);
            }
            // logMessage('debug', 'request - ZigateIEEE: '.$request2);

            // Request with multi inputs, input from a Info command.
            // Todo: At this stage process only one cmd info, could need multi info command in the futur
            // log::add( 'Abeille', 'debug', 'CmdInfo: Start analysis: '.$request );
            // #cmdInfo_xxxxxxx_#
            if (preg_match('`#cmdInfo_(.*)_#`m', $request2, $m)) {
                // log::add( 'Abeille', 'debug', 'CmdInfo: found it: '.json_encode($m) );
                $cmdInfo = $eqLogic->getCmd('info', $m[1]);
                // log::add( 'Abeille', 'debug', 'CmdInfo: '.$cmdInfo->getName() );
                $request2 = str_replace("#cmdInfo_".$m[1]."_#", $cmdInfo->execCmd(), $request2);
            }
            // log::add( 'Abeille', 'debug', 'CmdInfo: End analysis' );

            if (isset($_options)) {
                switch ($cmd->getSubType()) {
                case 'slider':
                    $sliderVal = $_options['slider'];
                    $sliderVal = trim($sliderVal); // Remove potential space seen at end of slider value
                    $cmdTopic = $cmd->getConfiguration('topic', '');
                    if ($cmdTopic == "writeAttribute") {
                        // New way of handling #slider#
                        $request2 = str_replace('#slider#', '#slider'.$sliderVal.'#', $request2);
                    } else
                        $request2 = str_replace('#slider#', $sliderVal, $request2);
                    break;
                case 'color':
                    $request2 = str_replace('#color#', $_options['color'], $request2);
                    break;
                case 'message':
                    $request2 = str_replace('#title#', $_options['title'], $request2);
                    $request2 = str_replace('#message#', $_options['message'], $request2);
                    break;
                }
            }
            // logMessage('debug', 'request - options: '.$request2);

            $request2 = str_replace('\\', '', jeedom::evaluateExpression($request2));
            // logMessage('debug', 'request - eval: '.$request2);

            $request2 = cmd::cmdToValue($request2);
            // logMessage('debug', 'request - cmdToVal: '.$request2);

            if ($request2 != $request) {
                logMessage('debug', "  updateField(): Updated '".$request."'");
                logMessage('debug', "  updateField(): To '".$request2."'");
                $request = $request2;
            }
            return $request;
        }

        /* Cmd execution.
           Tcharp38: why is it part of AbeilleCmd ? Shouldn't be in Abeille.class.php ?
           KiwiHC16: It's part of Jeedom Structure. You have a class for Eq and a Class for Cmd. AbeilleCmd is child of Cmd Class, Abeille is child of eqLogic class.
        */
        public function execute($_options = null) {
            global $abQueues;

            $eqLogic = $this->getEqLogic();
            logSetConf("AbeilleCmd.log", true); // Mandatory since called from 'Abeille.class.php'
            // logMessage('debug', "-- execute(eqName='".$eqLogic->getName()."' name='".$this->getName()."' type=".$this->getType().', options='.json_encode($_options).')');
            logMessage('debug', "-- execute(".$this->getHumanName().", type=".$this->getType().', options='.json_encode($_options).')');

            // TODO: A revoir, je ne sais plus ce qu'est ce truc.
            // cmdId : 12676 est le level d une ampoule
            // la cmdId 12680 a pour value 12676
            // Donc apres avoir fait un setLevel (12680) qui change le Level (12676), la cmdId setLvele est appelée avec le parametre: "cmdIdUpdated":"12676"
            // On le voit dans le log avec:
            // [2020-01-30 03:39:22][debug] : execute ->action<- function with options ->{"cmdIdUpdated":"12676"}<-
            if (isset($_options['cmdIdUpdated'])) {
                logMessage('debug', '-- _options[cmdIdUpdated] received so stop here, don t process: '.json_encode($_options['cmdIdUpdated']));
                return;
            }

            if ($this->getType() == 'action') {

                $eqLogicId = $eqLogic->getLogicalId();
                list($dest, $addr) = explode("/", $eqLogicId);
                if ($dest == '' || $addr == '') {
                    logMessage('error', $eqLogic->getHumanName().': DB corrompue. Cmde annulée.');
                    return;
                }

                // Value update if 'valueOffset' is defined.
                // Reminder: 'valueOffset' is equivalent to 'calculValueOffset' for action cmd.
                // Ex: "valueOffset": "#value#*10"
                // Ex: "valueOffset": "#value#*#logicid0102-01-F003#/100"
                $vo = $this->getConfiguration('ab::valueOffset', null);
                if ($vo !== null) {
                    if (isset($_options['slider'])) {
                        $vo = str_replace('#value#', $_options['slider'], $vo); // Replace #value# by slider value
                        $lop = strpos($vo, '#logicid'); // Any #logicid....# variable ?
                        if ($lop != false) {
                            // logMessage('debug', "-- execute(): logicId at pos ".$lop);
                            $logicId = substr($vo, $lop + 8);
                            $lop = strpos($logicId, '#');
                            $logicId = substr($logicId, 0, $lop);
                            // logMessage('debug', "-- execute(): logicId=".$logicId);
                            $cmdLogic = $eqLogic->getCmd('info', $logicId);
                            if (!is_object($cmdLogic)) {
                                message::add("Abeille", $eqLogic->getHumanName().": Commande '".$logicId."' inconnue");
                            } else {
                                $cmdVal = $cmdLogic->execCmd();
                                logMessage('debug', "-- execute(): cmd logicId='".$logicId."', val=".$cmdVal);
                                $vo = str_replace('#logicid'.$logicId.'#', $cmdVal, $vo); // Replace #logicid...#
                            }
                        }
                        $newValue = jeedom::evaluateExpression($vo); // Compute final formula
                        logMessage('debug', "-- execute(): 'valueOffset' applied: ".$_options['slider']." => ".$newValue);
                        $_options['slider'] = $newValue;
                    }
                }

                // -------------------------------------------------------------------------
                // Process topic
                // Needed for Telecommande: "topic":"CmdAbeille\/#addrGroup#\/OnOffGroup"
                if (strpos($this->getConfiguration('topic'), "CmdAbeille") === 0) {
                    $topic = str_replace("Abeille", $dest, $this->getConfiguration('topic'));
                } else {
                    $topic = "Cmd".$eqLogicId."/".$this->getConfiguration('topic');
                }
                // Tcharp38: What must be replaced in 'topic' ?
                $topic = $this->updateField($dest, $this, $topic, $_options);

                // -------------------------------------------------------------------------
                // Process Request
                $request = $this->updateField($dest, $this, $this->getConfiguration('request', '1'), $_options);

                // -------------------------------------------------------------------------
                $msg = array();
                $msg['topic'] = $topic;
                $msg['payload'] = $request;
                $msgJson = json_encode($msg);

                if (strpos($topic, "CmdCreate") === 0) {
                    $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
                    if (msg_send($queueXToAbeille, 1, $msgJson, false, false)) {
                        logMessage('debug', '-- execute(): CmdCreate: Msg sent: '.$msgJson);
                    } else {
                        logMessage('debug', '-- execute(): ERROR: CmdCreate: Could not send Msg');
                    }
                } else {
                    $queue = msg_get_queue($abQueues['xToCmd']['id']);
                    if (msg_send($queue, PRIO_NORM, $msgJson, false, false)) {
                        logMessage('debug', '-- execute(): Msg sent: '.$msgJson);
                    } else {
                        logMessage('debug', '-- execute(): ERROR: Could not send Msg');
                    }
                }

                // Mise a jour de la commande info associée, necessaire pour les commande actions qui recupere des parametres des commandes infos.
                if ($this->getCmdValue()) {
                    logMessage('debug', '-- execute(): will process cmdAction with cmd Info Ref if exist: '.$this->getCmdValue()->getName());
                    // TODO: je suppose qu il n'y a qu une commande info associée
                    $cmdInfo = $this->getCmdValue();
                    if ($cmdInfo) {
                        if (isset($_options['slider'])) {
                            logMessage('debug', '-- execute(): cmdAction with cmd Info Ref: '.$this->getCmdValue()->getName().' with value slider: '.$_options['slider']);
                            $cmdInfo->event($_options['slider']);
                        }
                    }
                }

                // An action cmd can trig another action cmd with 'trigOut'
                $to = $this->getConfiguration('ab::trigOut', null);
                if ($to !== null) {
                    // $trigOffset = $cmdLogic->getConfiguration('ab::trigOutOffset');
                    // Abeille::trigCommand($eqLogic, $cmdLogic->execCmd(), $trigLogicId, $trigOffset);
                    $trigCmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $to);
                    if ($trigCmd) {
                        log::add('Abeille', 'debug', "  SHOULD BE TRIGGERED='".$to."'");
                        // TODO
                        // log::add('Abeille', 'debug', "  Triggering cmd '".$to."'");
                        // $eqLogic->checkAndUpdateCmd($trigCmd, $trigValue);
                    }
                }
            } // End type==action

            return true;
        } // End public function execute()
    } // End class AbeilleCmd extends cmd
?>
