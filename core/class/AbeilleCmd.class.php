<?php

    class AbeilleCmd extends cmd
    {
        /**
         * updateField()
         * Used to replace #xxx# fields with real value
         *
         * @param request to be modified
         * @return request modified
         */
        public static function updateField($dest, $cmd, $request, $_options=NULL) {
            $request2 = $request;

            if (strpos($request2, "#addrGroup#") > 0) {
                $request2 = str_replace("#addrGroup#", $cmd->getEqLogic()->getConfiguration("Groupe"), $request2);
                // logMessage('debug', 'request - addGroup : ' . $request2);
            }

            if (strpos($request2, "#GroupeEP") > 0) {
                $id = substr($request2, strpos($request2, "#GroupeEP") + strlen("#GroupeEP"), 1);
                $request2 = str_replace("#GroupeEP".$id."#", $cmd->getEqLogic()->getConfiguration("GroupeEP".$id), $request2);
                // logMessage('debug', 'request - GroupEP : ' . $id . ' - ' . $request2);
                // $request .+ "TEST";
            }

            if (strpos($request2, '#onTime#') > 0) {
                $onTimeHex = sprintf("%04s", dechex($cmd->getEqLogic()->getConfiguration("onTime") * 10));
                $request2 = str_replace("#onTime#", $onTimeHex, $request2);
            }
            // logMessage('debug', 'request - onTime: ' . $request2);

            if (strpos($request2, '#addrIEEE#') > 0) {
                $commandIEEE = $cmd->getEqLogic()->getConfiguration("IEEE",'none');
                if (strlen($commandIEEE)!=16) {
                    // logMessage('debug', 'Adresse IEEE de l equipement ' . $cmd->getEqLogic()->getHumanName() . ' inconnue');
                    return true;
                }
                $request2 = str_replace('#addrIEEE#', $commandIEEE, $request2);
            }
            // logMessage('debug', 'request - addrIEEE: ' . $request);

            if (strpos($request2, '#ZiGateIEEE#') > 0) {
                // Logical Id ruche de la forme: Abeille1/Ruche
                $rucheIEEE = Abeille::byLogicalId($dest . '/Ruche', 'Abeille')->getConfiguration("IEEE",'none');
                // logMessage('debug', 'Adresse IEEE de la ruche ' . $rucheIEEE);
                if (strlen($rucheIEEE) != 16) {
                    // logMessage('debug', 'Adresse IEEE de la ruche ' . $cmd->getEqLogic()->getHumanName() . ' inconnue');
                    // return true;
                } else
                    $request2 = str_replace('#ZiGateIEEE#', $rucheIEEE, $request2);
            }
            // logMessage('debug', 'request - ZigateIEEE: ' . $request2);

            // Request with multi inputs, input from a Info command.
            // Todo: At this stage process only one cmd info, could need multi info command in the futur
            // log::add( 'Abeille', 'debug', 'CmdInfo: Start analysis: '.$request );
            // #cmdInfo_xxxxxxx_#
            if (preg_match('`#cmdInfo_(.*)_#`m', $request2, $m)) {
                // log::add( 'Abeille', 'debug', 'CmdInfo: found it: '.json_encode($m) );
                $cmdInfo = $this->getEqLogic()->getCmd('info', $m[1]);
                // log::add( 'Abeille', 'debug', 'CmdInfo: '.$cmdInfo->getName() );
                $request2 = str_replace("#cmdInfo_".$m[1]."_#", $cmdInfo->execCmd(), $request2);
            }
            // log::add( 'Abeille', 'debug', 'CmdInfo: End analysis' );

            if (isset($_options)) {
                switch ($cmd->getSubType()) {
                    case 'slider':
                        $request2 = str_replace('#slider#', $_options['slider'], $request2);
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
            // logMessage('debug', 'request - options: ' . $request2);

            $request2 = str_replace('\\', '', jeedom::evaluateExpression($request2));
            // logMessage('debug', 'request - eval: ' . $request2);

            $request2 = cmd::cmdToValue($request2);
            // logMessage('debug', 'request - cmdToVal: ' . $request2);

            if ($request2 != $request) {
                logMessage('debug', 'updateField()');
                logMessage('debug', "  Updated '".$request."'");
                logMessage('debug', "  To '".$request2."'");
                $request = $request2;
            }
            return $request;
        }

        public function execute($_options = null)
        {
            logSetConf("AbeilleCmd.log"); // Mandatory since called from 'Abeille.class.php'
            logMessage('debug', '');
            logMessage('debug', '------ execute(eqName='.$this->getEqLogic()->getName().' name='.$this->getName().' type='.$this->getType().', options='.json_encode($_options).')');

            // TODO: A revoir, je ne sais plus ce qu'est ce truc.
            // cmdId : 12676 est le level d une ampoule
            // la cmdId 12680 a pour value 12676
            // Donc apres avoir fait un setLevel (12680) qui change le Level (12676), la cmdId setLvele est appelée avec le parametre: "cmdIdUpdated":"12676"
            // On le voit dans le log avec:
            // [2020-01-30 03:39:22][debug] : execute ->action<- function with options ->{"cmdIdUpdated":"12676"}<-
            if (isset($_options['cmdIdUpdated'])) {
                logMessage('debug', '------ _options[cmdIdUpdated] received so stop here, don t process: '.json_encode($_options['cmdIdUpdated']));
                return;
            }

            if ($this->getType() == 'action') {

                list($dest,$addr) = explode("/", $this->getEqLogic()->getLogicalId());

                // -------------------------------------------------------------------------
                // Process topic
                // Needed for Telecommande: "topic":"CmdAbeille\/#addrGroup#\/OnOffGroup"
                if (strpos($this->getConfiguration('topic'), "CmdAbeille") === 0) {
                    $topic = str_replace("Abeille", $dest, $this->getConfiguration('topic'));
                }
                else {
                    $topic = "Cmd" . $this->getEqLogic()->getLogicalId() . "/" . $this->getConfiguration('topic');
                }
                $topic = $this->updateField($dest,$this,$topic,$_options);

                // -------------------------------------------------------------------------
                // Process Request
                $request = $this->updateField($dest,$this,$this->getConfiguration('request', '1'),$_options);

                // -------------------------------------------------------------------------
                $msgAbeille = new MsgAbeille;
                $msgAbeille->message['topic'] = $topic;
                $msgAbeille->message['payload'] = $request;

                if (strpos($topic, "CmdCreate") === 0) {
                    $queueKeyAbeilleToAbeille = msg_get_queue(queueKeyAbeilleToAbeille);
                    if (msg_send($queueKeyAbeilleToAbeille, 1, $msgAbeille, true, false)) {
                        logMessage('debug', '------ (CmdCreate) Msg sent: ' . json_encode($msgAbeille));
                    } else {
                        logMessage('debug', '------ (CmdCreate) Could not send Msg');
                    }
                } else {
                    $queueKeyAbeilleToCmd = msg_get_queue(queueKeyAbeilleToCmd);
                    if (msg_send($queueKeyAbeilleToCmd, priorityUserCmd, $msgAbeille, true, false)) {
                        logMessage('debug', '------ execute(): Msg sent: ' . json_encode($msgAbeille));
                    } else {
                        logMessage('debug', '------ execute(): Could not send Msg');
                    }
                }

                // Mise a jour de la commande info associée, necessaire pour les commande actions qui recupere des parametres des commandes infos.
                if ($this->getCmdValue()) {
                    logMessage('debug', '------ execute(): will process cmdAction with cmd Info Ref if exist: '.$this->getCmdValue()->getName());
                    // TODO: je suppose qu il n'y a qu une commande info associée
                    $cmdInfo = $this->getCmdValue();
                    if ($cmdInfo) {
                        if (isset($_options['slider'])) {
                            logMessage('debug', '------ execute(): cmdAction with cmd Info Ref: '.$this->getCmdValue()->getName() . ' with value slider: '.$_options['slider']);
                            $cmdInfo->event($_options['slider']);
                        }
                    }
                }
            } // End type==action

            return true;
        } // End public function execute()
    } // End class AbeilleCmd extends cmd
?>
