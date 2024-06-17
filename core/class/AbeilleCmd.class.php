<?php
    class AbeilleCmd extends cmd {
        /**
         * updateField()
         * Used to replace #xxx# fields with real value
         *
         * @param request to be modified
         * @return request modified
         */
        public static function updateField($net, $cmd, $request, $_options=NULL) {
            $request2 = $request;
            $eqLogic = $cmd->getEqLogic();

            if (strpos($request2, "#addrGroup#") > 0) {
                $request2 = str_replace("#addrGroup#", $eqLogic->getConfiguration("Groupe"), $request2);
                // logMessage('debug', 'request - addGroup : '.$request2);
            }

            // Tcharp38: #GroupeEPx# removed. Now supported thru 'variables' section
            // if (strpos($request2, "#GroupeEP") > 0) {
            //     $id = substr($request2, strpos($request2, "#GroupeEP") + strlen("#GroupeEP"), 1);
            //     $request2 = str_replace("#GroupeEP".$id."#", $eqLogic->getConfiguration("GroupeEP".$id), $request2);
            //     // logMessage('debug', 'request - GroupEP : '.$id.' - '.$request2);
            //     // $request .+ "TEST";
            // }

            if (strpos($request2, '#onTime#') > 0) {
                $onTimeHex = sprintf("%04s", dechex($eqLogic->getConfiguration("onTime") * 10));
                $request2 = str_replace("#onTime#", $onTimeHex, $request2);
            }
            // logMessage('debug', 'request - onTime: '.$request2);

            if (stripos($request2, '#addrIEEE#') !== false)  {
                $commandIEEE = $eqLogic->getConfiguration("IEEE", '');
                if ($commandIEEE != '')
                    $request2 = str_ireplace('#addrIEEE#', $commandIEEE, $request2);
            }
            if (stripos($request2, '#IEEE#') !== false)  {
                $commandIEEE = $eqLogic->getConfiguration("IEEE", '');
                if ($commandIEEE != '')
                    $request2 = str_ireplace('#IEEE#', $commandIEEE, $request2);
            }
            // logMessage('debug', 'request - addrIEEE: '.$request);

            if (stripos($request2, '#ZiGateIEEE#') !== false) {
                // Logical Id ruche de la forme: Abeille1/0000
                $rucheIEEE = Abeille::byLogicalId($net.'/0000', 'Abeille')->getConfiguration("IEEE", '');
                // logMessage('debug', 'Adresse IEEE de la ruche '.$rucheIEEE);
                if ($rucheIEEE != '')
                    $request2 = str_ireplace('#ZiGateIEEE#', $rucheIEEE, $request2);
            }
            // logMessage('debug', 'request - ZigateIEEE: '.$request2);

            // Request with multi inputs, input from a Info command.
            // Todo: At this stage process only one cmd info, could need multi info command in the futur
            // #cmdInfo_xxxxxxx_#
            // Ex: lift=#cmdInfo_Level_# => lift=value from 'Level' info cmd
            // OBSOLETE !! Better to use #logicidxxxx# since the cmd could be renamed by user
            if (preg_match('`#cmdInfo_(.*)_#`m', $request2, $m)) {
                log::add( 'Abeille', 'debug', '  updateField(): Found cmd info: '.json_encode($m, JSON_UNESCAPED_SLASHES));
                $cmdInfo = $eqLogic->getCmd('info', $m[1]);
                if (!is_object($cmdInfo)) {
                    log::add( 'Abeille', 'error', $eqLogic->getHumanName().": Modèle invalide. La commande info '".$m[1]."' n'existe pas.");
                } else {
                    // log::add( 'Abeille', 'debug', 'CmdInfo: '.$cmdInfo->getName() );
                    $request2 = str_replace("#cmdInfo_".$m[1]."_#", $cmdInfo->execCmd(), $request2);
                }
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
                case 'select':
                    $sliderVal = $_options['select'];
                    $sliderVal = trim($sliderVal); // Remove potential space seen at end of slider value
                    $cmdTopic = $cmd->getConfiguration('topic', '');
                    if ($cmdTopic == "writeAttribute") {
                        // New way of handling #slider#
                        $request2 = str_replace('#select#', '#select'.$sliderVal.'#', $request2);
                    } else
                        $request2 = str_replace('#select#', $sliderVal, $request2);
                    break;
                case 'color':
                    $request2 = str_replace('#color#', $_options['color'], $request2);
                    break;
                case 'message':
                    if (isset($_options['title']))
                        $request2 = str_replace('#title#', $_options['title'], $request2);
                    if (isset($_options['message']))
                        $request2 = str_replace('#message#', $_options['message'], $request2);
                    break;
                default: // 'other'
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

        /* Jeedom cmd execution.
           This is a Jeedom required function. Called to execute an 'action' command.
        */
        public function execute($_options = null) {
            logSetConf("AbeilleCmd.log", true); // Mandatory since called from 'Abeille.class.php'
            $cmdType = $this->getType();
            $cmdHName = $this->getHumanName();
            logMessage('debug', "-- execute(${cmdHName}, type=${cmdType}, options=".json_encode($_options).')');

            if ($cmdType != 'action') {
                logMessage('error', "-- Unexpected info command => ignored");
                return;
            }

            /* If cmd sub type is not 'other', there is a value associated to cmd which depends on sub type
               'select' => #select#
               'slider' => #slider#
               'message' => #title# (optional) + #message#
               'color' => #color#

               Value can be overloaded or updated thanks to 'valueOffset' for cases above except 'other'
             */

            // TODO: A revoir, je ne sais plus ce qu'est ce truc.
            // cmdId : 12676 est le level d une ampoule
            // la cmdId 12680 a pour value 12676
            // Donc apres avoir fait un setLevel (12680) qui change le Level (12676), la cmdId setLvele est appelée avec le parametre: "cmdIdUpdated":"12676"
            // On le voit dans le log avec:
            // [2020-01-30 03:39:22][debug] : execute ->action<- function with options ->{"cmdIdUpdated":"12676"}<-
            // Tcharp38: 'cmdIdUpdated' not found elsewhere in Abeille nor in Jeedom core.
            // if (isset($_options['cmdIdUpdated'])) {
            //     logMessage('debug', '-- _options[cmdIdUpdated] received so stop here, don t process: '.json_encode($_options['cmdIdUpdated']));
            //     return;
            // }

            $eqLogic = $this->getEqLogic();
            $eqLogicId = $eqLogic->getLogicalId();
            list($net, $addr) = explode("/", $eqLogicId);
            if ($net == '' || $addr == '') {
                logMessage('error', $eqLogic->getHumanName().': DB corrompue. Cmde annulée.');
                return;
            }

            // User command support
            // This is recognized with the following logical ID syntax 'logicalId::parameters'
            // Ex: sendCode::message=B4gRiBEtAo8G4AED4AsB4BsfQAFAJ-APAcAbQAfgBwMH1rmIEYgRLQLgAxfgCwHgGx9AAUAn4A8BwBtAB-AHA-B8hwIGLQI
            $cmdLogicId = $this->getLogicalId();
            if (($pos = strpos($cmdLogicId, "::")) !== false) {
                $cmdLogicId2 = substr($cmdLogicId, 0, $pos);
                $cmdParams = substr($cmdLogicId, $pos + 2);
                logMessage('debug', "-- User cmd: logicId=${cmdLogicId2}, params=${cmdParams}");
                $cmdLogic = $eqLogic->getCmd('action', $cmdLogicId2);
                if (!is_object($cmdLogic)) {
                    logMessage('error', $this->getHumanName().": Cmde '${cmdLogicId2}' inconnue.");
                    return;
                }
                $params = explode('=', $cmdParams);
                logMessage('debug', "-- params=".json_encode($params));
                $request = $cmdLogic->getConfiguration('request', '');
                $len = count($params);
                for ($i = 0; $i < $len; ) {
                    $pKey = $params[$i++];
                    $pVal = $params[$i++];
                    logMessage('debug', "-- pKey=${pKey}, pVal=${pVal}");
                    $request = str_ireplace("#${pKey}#", $pVal, $request);
                }
                logMessage('debug', "-- request=${request}");
            } else {
                $cmdLogic = $this;
                $request = $cmdLogic->getConfiguration('request', '');
            }

            // Value update if 'valueOffset' is defined.
            // Reminder: 'valueOffset' is equivalent to 'calculValueOffset' for action cmd.
            // Ex: "valueOffset": "#value#*10"
            // Ex: "valueOffset": "#value#*#logicid0102-01-F003#/100"
            // Ex: "valueOffset": "#valueformat-%02X#" (equiv 'sprintf("%02X", #value#)')
            $vo = $cmdLogic->getConfiguration('ab::valueOffset', "");
            if ($vo != "") {
                logMessage('debug', "-- 'ab::valueOffset'=${vo}");
                // Replacing '#logicid...#
                $lop = strpos($vo, '#logicid'); // Any #logicid....# variable ?
                if ($lop !== false) {
                    // logMessage('debug', "-- execute(): logicId at pos ".$lop);
                    $logicId = substr($vo, $lop + 8);
                    $lop = strpos($logicId, '#');
                    $logicId = substr($logicId, 0, $lop);
                    // logMessage('debug', "-- execute(): logicId=".$logicId);
                    $cmdLogic2 = $eqLogic->getCmd('info', $logicId);
                    if (!is_object($cmdLogic2)) {
                        message::add("Abeille", "${cmdHName}: Commande '${logicId}' inconnue");
                    } else {
                        $cmdVal = $cmdLogic2->execCmd();
                        logMessage('debug', "-- Cmd logicId='".$logicId."', val=".$cmdVal);
                        $vo = str_replace('#logicid'.$logicId.'#', $cmdVal, $vo); // Replace #logicid...#
                    }
                }

                if (isset($_options['slider'])) {
                    $vo = str_replace('#value#', $_options['slider'], $vo); // Replace #value# by slider value
                    // $lop = strpos($vo, '#logicid'); // Any #logicid....# variable ?
                    // if ($lop != false) {
                    //     // logMessage('debug', "-- execute(): logicId at pos ".$lop);
                    //     $logicId = substr($vo, $lop + 8);
                    //     $lop = strpos($logicId, '#');
                    //     $logicId = substr($logicId, 0, $lop);
                    //     // logMessage('debug', "-- execute(): logicId=".$logicId);
                    //     $cmdLogic2 = $eqLogic->getCmd('info', $logicId);
                    //     if (!is_object($cmdLogic2)) {
                    //         message::add("Abeille", $eqLogic->getHumanName().": Commande '".$logicId."' inconnue");
                    //     } else {
                    //         $cmdVal = $cmdLogic2->execCmd();
                    //         logMessage('debug', "-- Cmd logicId='".$logicId."', val=".$cmdVal);
                    //         $vo = str_replace('#logicid'.$logicId.'#', $cmdVal, $vo); // Replace #logicid...#
                    //     }
                    // }
                    $newValue = jeedom::evaluateExpression($vo); // Compute final formula
                    logMessage('debug', "-- 'valueOffset' applied: ".$_options['slider']." => ".$newValue);
                    $_options['slider'] = $newValue;
                } else if (strpos($request, '#value#') !== false) {
                    // #value# must be replaced by valueOffset result
                    // $lop = strpos($vo, '#logicid'); // Any #logicid....# variable ?
                    // if ($lop != false) {
                    //     // logMessage('debug', "-- execute(): logicId at pos ".$lop);
                    //     $logicId = substr($vo, $lop + 8);
                    //     $lop = strpos($logicId, '#');
                    //     $logicId = substr($logicId, 0, $lop);
                    //     // logMessage('debug', "-- execute(): logicId=".$logicId);
                    //     $cmdLogic2 = $eqLogic->getCmd('info', $logicId);
                    //     if (!is_object($cmdLogic2)) {
                    //         message::add("Abeille", $eqLogic->getHumanName().": Commande '".$logicId."' inconnue");
                    //     } else {
                    //         $cmdVal = $cmdLogic2->execCmd();
                    //         logMessage('debug', "-- Cmd logicId='".$logicId."' => val=".$cmdVal);
                    //         $vo = str_replace('#logicid'.$logicId.'#', $cmdVal, $vo); // Replace #logicid...#
                    //     }
                    // }
                    $newValue = jeedom::evaluateExpression($vo); // Compute final formula
                    $request = str_replace('#value#', $newValue, $request);
                    logMessage('debug', "-- 'valueOffset' applied: newValue=${newValue}");
                }

                if (isset($_options['slider']))
                    $value = $_options['slider'];
                else if (isset($_options['select']))
                    $value = $_options['select'];

                // #valueformat-FMT# ?
                $vFmtPos = stripos($vo, '#valueformat-'); // Any #valueformat-FMT# variable ?
                if ($vFmtPos !== false) {
                    $fmt = substr($vo, $vFmtPos + 13); // 'FMT#'
                    $end = strpos($fmt, '#'); // '#' position
                    $fmt = substr($fmt, 0, $end); // 'FMT'
                    $newValue = sprintf($fmt, $value);
                    $request = str_ireplace("#valueformat-${fmt}#", $newValue, $request);
                    logMessage('debug', "-- 'valueformat': Replaced '${value}' by '${newValue}'");
                }
            }

            // -------------------------------------------------------------------------
            // Process topic
            // Needed for Telecommande: "topic":"CmdAbeille\/#addrGroup#\/OnOffGroup"
            if (strpos($cmdLogic->getConfiguration('topic'), "CmdAbeille") === 0) {
                $topic = str_replace("Abeille", $net, $cmdLogic->getConfiguration('topic'));
            } else {
                $topic = "Cmd".$eqLogicId."/".$cmdLogic->getConfiguration('topic');
            }

            /* 'topic' replacement examples
             "topic": "CmdAbeille/#addrGroup#/setColourGroup" => must replace #ADDRGROUP#
             "topic": "CmdAbeille/#GROUPEP4#/OnOffGroup"  => must replace #GROUPEPx#
             "topic": "CmdAbeille/#addrGroup#/setTemperatureGroup
            */
            $topic = $cmdLogic->updateField($net, $cmdLogic, $topic, $_options);

            /* 'request' replacement examples
             "request": "Level=#slider#&duration=01"
             "request": "targetEndpoint=#EP#&ClusterId=0300&AttributeId=0007&AttributeType=21"
             "request": "EP=#EP#&color=#color#"
             "request": "clusterId=0201&attributeId=4000&EP=01&Proprio=1037&attributeType=30&value=#message#"
             "request": "groupID=#addrGroup#&sceneID=01"
             "request": "targetExtendedAddress=#addrIEEE#&targetEndpoint=07&clusterID=0008&reportToGroup=#GROUPEP7#"
             "request": "ep=#EP#&clustId=#CLUSTID#&attrId=#ATTRID#&attrVal=#select#"
             "request": "addr=#IEEE#&ep=#EP#&clustId=#CLUSTID#&destAddr=#ZigateIEEE#&destEp=01"
            */
            if (stripos($request, "#valueoffset#") !== false) {
                if ($vo == "") {
                    // message::add("Abeille", "${cmdHName}: 'valueOffset' manquant");
                    logMessage('error', "${cmdHName}: 'valueOffset' manquant");
                    return;
                }
                /* Note: could not make it work with jeedom::evaluateExpression()
                [2024-06-16 22:05:20] -- Cmd logicId='0102-01-0017', val=0
                [2024-06-16 22:05:20] -- '#valueoffset#' => newValue='0|(1<<1)'
                [2024-06-16 22:05:20] -- '#valueoffset#' => request='ep=01&clustId=0102&attrId=0017&attrVal=0|(1<<1)&attrType=18'
                */
                $exp = "\$newVal = ${vo};";
                eval($exp);
                logMessage('debug', "-- '#valueoffset#' => newVal='${newVal}'");
                $request = str_ireplace("#valueoffset#", $newVal, $request);
                logMessage('debug', "-- '#valueoffset#' => request='${request}'");
            }
            $request = $cmdLogic->updateField($net, $cmdLogic, $request, $_options);

            $msg = array();
            $msg['topic'] = $topic;
            $msg['payload'] = $request;
            $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
            global $abQueues;

            if (strpos($topic, "CmdCreate") === 0) {
                $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
                if (msg_send($queueXToAbeille, 1, $msgJson, false, false)) {
                    logMessage('debug', '-- CmdCreate: Msg sent: '.$msgJson);
                } else {
                    logMessage('debug', '-- ERROR: CmdCreate: Could not send Msg');
                }
            } else {
                $queue = msg_get_queue($abQueues['xToCmd']['id']);
                if (msg_send($queue, PRIO_NORM, $msgJson, false, false)) {
                    logMessage('debug', '-- Msg sent: '.$msgJson);
                } else {
                    logMessage('debug', '-- ERROR: Could not send Msg');
                }
            }

            // Mise a jour de la commande info associée, necessaire pour les commande actions qui recupere des parametres des commandes infos.
            // Tcharp38: DISABLED !! This is NOT the reality. Cmd info should reflect the real device status and therefore info
            // update should either be reporting (best case) or polling
            // if ($cmdLogic->getCmdValue()) {
            //     logMessage('debug', '-- Will process cmdAction with cmd Info Ref if exist: '.$cmdLogic->getCmdValue()->getName());
            //     // TODO: je suppose qu il n'y a qu une commande info associée
            //     $cmdInfo = $cmdLogic->getCmdValue();
            //     if ($cmdInfo) {
            //         if (isset($_options['slider'])) {
            //             logMessage('debug', '-- cmdAction with cmd Info Ref: '.$cmdLogic->getCmdValue()->getName().' with value slider: '.$_options['slider']);
            //             $cmdInfo->event($_options['slider']);
            //         }
            //     }
            // }

            // An action cmd can trig another action cmd with 'trigOut'
            // Syntax reminder
            // "trigOut": {
            //     "01-smokeAlarm": {
            //         "comment": "On receive we trig <EP>-smokeAlarm with extracted boolean/bit0 value",
            //         "valueOffset": "#value#&1"
            //     },
            //     "01-tamperAlarm": {
            //         "comment": "Bit 2 is tamper",
            //         "valueOffset": "(#value#>>2)&1"
            //     }
            // }
            $toList = $cmdLogic->getConfiguration('ab::trigOut', []);
            foreach ($toList as $toLogicId => $to) {
                // if (isset($to['valueOffset']))
                //     $toOffset = $to['valueOffset'];
                // else
                //     $toOffset = '';
                $trigCmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $toLogicId);
                if ($trigCmd) {
                    logMessage('debug', "-- Triggering '${toLogicId}'");
                    $trigCmd->execute();
                }
            }
            // $to = $cmdLogic->getConfiguration('ab::trigOut', null);
            // if ($to !== null) {
            //     $trigCmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $to);
            //     if ($trigCmd) {
            //         logMessage('debug', "-- Triggering '".$to."'");
            //         $trigCmd->execute();
            //     }
            // }

            return;
        } // End public function execute()
    } // End class AbeilleCmd extends cmd
?>
