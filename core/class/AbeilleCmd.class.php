<?php

    class AbeilleCmd extends cmd
    {
        /**
         * updateField
         * 
         * used to replace #xxx# fields with real value
         * 
         * @param request to be modified
         * 
         * @return request modified
         */
        public function updateField($dest,$cmd,$request,$_options=NULL) {
            /* ------------------------------ */
            // // Je fais les remplacement dans les parametres (ex: addGroup pour telecommande Ikea 5 btn)
            if (strpos($request, "#addrGroup#") > 0) {
                $request = str_replace("#addrGroup#", $cmd->getEqLogic()->getConfiguration("Groupe"), $request);
                logMessage('debug', 'request - addGroup : ' . $request);
            }
            

            if (strpos($request, "#GroupeEP") > 0) {
                $id = substr($request,strpos($request, "#GroupeEP")+strlen("#GroupeEP"),1);
                $request = str_replace("#GroupeEP".$id."#", $cmd->getEqLogic()->getConfiguration("GroupeEP".$id), $request);
                logMessage('debug', 'request - GroupEP : ' . $id . ' - ' . $request);
                $request .+ "TEST";
            }

            if (strpos($request, '#onTime#') > 0) {
                $onTimeHex = sprintf("%04s", dechex($cmd->getEqLogic()->getConfiguration("onTime") * 10));
                $request = str_replace("#onTime#", $onTimeHex, $request);
            }
            // logMessage('debug', 'request - onTime: ' . $request);

            if (strpos($request, '#addrIEEE#') > 0) {
                $commandIEEE = $cmd->getEqLogic()->getConfiguration("IEEE",'none');
                if (strlen($commandIEEE)!=16) {
                    logMessage('debug', 'Adresse IEEE de l equipement ' . $cmd->getEqLogic()->getHumanName() . ' inconnue');
                    return true;
                }
                $request = str_replace('#addrIEEE#', $commandIEEE, $request);
            }
            // logMessage('debug', 'request - addrIEEE: ' . $request);

            if (strpos($request, '#ZiGateIEEE#') > 0) {
                // Logical Id ruche de la forme: Abeille1/Ruche
                $rucheIEEE = Abeille::byLogicalId($dest . '/Ruche', 'Abeille')->getConfiguration("IEEE",'none');
                logMessage('debug', 'Adresse IEEE de la ruche ' . $rucheIEEE);
                if (strlen($rucheIEEE)!=16) {
                    logMessage('debug', 'Adresse IEEE de la ruche ' . $cmd->getEqLogic()->getHumanName() . ' inconnue');
                    return true;
                }
                $request = str_replace('#ZiGateIEEE#', $rucheIEEE, $request);
            }
            // logMessage('debug', 'request - ZigateIEEE: ' . $request);

            // Request with multi inputs, input from a Info command.
            // Todo: At this stage process only one cmd info, could need multi info command in the futur 
            // log::add( 'Abeille', 'debug', 'CmdInfo: Start analysis: '.$request );
            // #cmdInfo_xxxxxxx_#
            if (preg_match('`#cmdInfo_(.*)_#`m', $request, $m)) {
                // log::add( 'Abeille', 'debug', 'CmdInfo: found it: '.json_encode($m) );
                $cmdInfo = $this->getEqLogic()->getCmd('info',$m[1]);
                // log::add( 'Abeille', 'debug', 'CmdInfo: '.$cmdInfo->getName() );
                $request= str_replace("#cmdInfo_".$m[1]."_#",$cmdInfo->execCmd(),$request);
            }
            // log::add( 'Abeille', 'debug', 'CmdInfo: End analysis' );

            if (isset($_options)) {
                switch ($cmd->getSubType()) {
                    case 'slider':
                        $request = str_replace('#slider#', $_options['slider'], $request);
                        break;
                    case 'color':
                        $request = str_replace('#color#', $_options['color'], $request);
                        break;
                    case 'message':
                        $request = str_replace('#title#', $_options['title'], $request);
                        $request = str_replace('#message#', $_options['message'], $request);
                        break;
                }
            }
            // logMessage('debug', 'request - options: ' . $request);

            $request = str_replace('\\', '', jeedom::evaluateExpression($request));
            // logMessage('debug', 'request - eval: ' . $request);

            $request = cmd::cmdToValue($request);
            // logMessage('debug', 'request - cmdToVal: ' . $request);

            return $request;
        }

        public function execute($_options = null)
        {
            log::add( 'Abeille', 'debug', '------------');

            logMessage('debug',           'execute(eqName='.$this->getEqLogic()->getName().' name='.$this->getName().' type='.$this->getType().', options='.json_encode($_options).')');
            log::add( 'Abeille', 'debug', 'execute(eqName='.$this->getEqLogic()->getName().' name='.$this->getName().' type='.$this->getType().', options='.json_encode($_options).')');

            // cmdId : 12676 est le level d une ampoule
            // la cmdId 12680 a pour value 12676
            // Donc apres avoir fait un setLevel (12680) qui change le Level (12676), la cmdId setLvele est appelée avec le parametre: "cmdIdUpdated":"12676"
            // On le voit dans le log avec:
            // [2020-01-30 03:39:22][debug] : execute ->action<- function with options ->{"cmdIdUpdated":"12676"}<-
            if (isset($_options['cmdIdUpdated'])) {
                log::add( 'Abeille', 'debug', '_options[cmdIdUpdated] received so stop here, don t process: '.json_encode($_options['cmdIdUpdated']));
                return;
            }

            if ($this->getType() == 'action') {

                list($dest,$addr) = explode("/", $this->getEqLogic()->getLogicalId());

                // Needed for Telecommande
                // "topic":"CmdAbeille\/Ruche\/sceneGroupRecall"
                // "topic":"CmdAbeille\/#addrGroup#\/OnOffGroup"
                // il faut recuperer la zigate controlant ce groupe pour cette telecommande et l addGroup du groupe vient des param
                if (strpos($this->getConfiguration('topic'), "CmdAbeille") === 0) {
                    $topic = str_replace("Abeille", $dest, $this->getConfiguration('topic'));
                }
                else {
                    $topic = "Cmd" . $this->getEqLogic()->getLogicalId() . "/" . $this->getConfiguration('topic');
                }

                logMessage('debug', 'topic: ' . $topic);

                /* ------------------------------ */
                // Je fais les remplacement dans la commande (ex: addGroup pour telecommande Ikea 5 btn)
                $topic = $this->updateField($dest,$this,$topic,$_options);
                logMessage('debug', 'topic updated: ' . $topic);
                log::add( 'Abeille', 'debug', 'topic: ' . $topic);

                // -------------------------------------------------------------------------
                // Process Request
                $request = $this->getConfiguration('request', '1');
                logMessage('debug', 'request: ' . $request);
                $request = $this->updateField($dest,$this,$request,$_options);
                logMessage('debug', 'request updated: ' . $request);
                log::add( 'Abeille', 'debug', 'request: ' . $request);

                // -------------------------------------------------------------------------
                $msgAbeille = new MsgAbeille;

                $msgAbeille->message['topic'] = $topic;
                $msgAbeille->message['payload'] = $request;

                logMessage('debug', 'topic: ' . $topic . ' request: ' . $request);

                if (strpos($topic, "CmdCreate") === 0) {
                    $queueKeyAbeilleToAbeille = msg_get_queue(queueKeyAbeilleToAbeille);
                    if (msg_send($queueKeyAbeilleToAbeille, 1, $msgAbeille, true, false)) {
                        logMessage('debug', '(CmdCreate) Msg sent: ' . json_encode($msgAbeille));
                    } else {
                        logMessage('debug', '(CmdCreate) Could not send Msg');
                    }
                } else {
                    $queueKeyAbeilleToCmd = msg_get_queue(queueKeyAbeilleToCmd);
                    if (msg_send($queueKeyAbeilleToCmd, priorityUserCmd, $msgAbeille, true, false)) {
                        logMessage('debug', '(All) Msg sent: ' . json_encode($msgAbeille));
                    } else {
                        logMessage('debug', '(All) Could not send Msg');
                    }
                }

                // Mise a jour de la commande info associée
                log::add( 'Abeille', 'debug', 'execute() - will process cmdAction with cmd Info Ref if exist: '.$this->getCmdValue()->getName());
                if ($this->getCmdValue()) {
                    // TODO: je suppose qu il n'y a qu une commande info associée
                    $cmdInfo = $this->getCmdValue();
                    if ($cmdInfo) {
                        if (isset($_options['slider'])) {
                            log::add( 'Abeille', 'debug', 'execute() - cmdAction with cmd Info Ref: '.$this->getCmdValue()->getName() . ' with value slider: '.$_options['slider']);
                            $cmdInfo->event($_options['slider']);
                        }
                    }
                }



            } // End type==action

            return true;
        } // End public function execute()
    } // End class AbeilleCmd extends cmd
?>
