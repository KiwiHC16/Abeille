<?php

class AbeilleCmd extends cmd
{
    public function execute($_options = null)
    {

        log::add('Abeille', 'Debug', 'execute ->' . $this->getType() . '<- function with options ->' . json_encode($_options) . '<-');

        // cmdId : 12676 est le level d une ampoule
        // la cmdId 12680 a pour value 12676
        // Donc apres avoir fait un setLevel (12680) qui change le Level (12676), la cmdId setLvele est appelée avec le parametre: "cmdIdUpdated":"12676"
        // On le voit dans le log avec:
        // [2020-01-30 03:39:22][DEBUG] : execute ->action<- function with options ->{"cmdIdUpdated":"12676"}<-
        if (isset($_options['cmdIdUpdated'])) return;

        switch ($this->getType()) {
            case 'action' :

                /* ------------------------------ */
                // Topic est l arborescence MQTT: La fonction Zigate

                $Abeilles = new Abeille();

                $NE_Id = $this->getEqLogic_id();
                $NE = $Abeilles->byId($NE_Id);

                if (strpos("_" . $this->getConfiguration('topic'), "CmdAbeille") == 1) {
                    $topic = $this->getConfiguration('topic');
                } else {
                    if (strpos("_" . $this->getConfiguration('topic'), "CmdCreate") == 1) {
                        $topic = $this->getConfiguration('topic');
                    } else {
                        $topic = "Cmd" . $NE->getConfiguration('topic') . "/" . $this->getConfiguration('topic');
                    }
                }

                if (strpos("_" . $this->getConfiguration('topic'), "CmdAbeille") == 1) {
                // if ( $NE->getConfiguration('Zigate') > 1 ) {
                //     $topic = str_replace( "CmdAbeille", "CmdAbeille".$NE->getConfiguration("Zigate"), $topic );
                // }
                    $topicNEArray = explode("/", $NE->getConfiguration("topic"));
                    $destNE = str_replace("Abeille", "", $topicNEArray[0]);
                    $topic = str_replace("CmdAbeille", "CmdAbeille" . $destNE, $topic);
                }

                log::add('Abeille', 'Debug', 'topic: ' . $topic);

                $topicArray = explode("/", $topic);
                $dest = substr($topicArray[0], 3);

                /* ------------------------------ */
                // Je fais les remplacement dans la commande (ex: addGroup pour telecommande Ikea 5 btn)
                if (strpos($topic, "#addrGroup#") > 0) {
                    $topic = str_replace("#addrGroup#", $NE->getConfiguration("Groupe"), $topic);
                }

                // -------------------------------------------------------------------------
                // Process Request
                $request = $this->getConfiguration('request', '1');
                // request: c'est le payload dans la page de configuration pour une commande
                // C est les parametres de la commande pour la zigate
                log::add('Abeille', 'Debug', 'request: ' . $request);

                /* ------------------------------ */
                // Je fais les remplacement dans la commande (ex: addGroup pour telecommande Ikea 5 btn)
                if (strpos($request, "#addrGroup#") > 0) {
                    $request = str_replace("#addrGroup#", $NE->getConfiguration("Groupe"), $request);
                }

                /* ------------------------------ */
                // Je fais les remplacement dans les parametres
                if (strpos($request, '#onTime#') > 0) {
                    $onTimeHex = sprintf("%04s", dechex($NE->getConfiguration("onTime") * 10));
                    $request = str_replace("#onTime#", $onTimeHex, $request);
                }

                if (strpos($request, '#addrIEEE#') > 0) {
                    $ruche = new Abeille();
                    $command = new AbeilleCmd();

                    // Recupere IEEE de la Ruche/ZiGate
                    $rucheId = $ruche->byLogicalId($dest . '/Ruche', 'Abeille')->getId();
                    log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);

                    if (strlen($ruche->byLogicalId($dest . '/Ruche', 'Abeille')->getConfiguration('IEEE', 'none')) == 16) {
                        $rucheIEEE = $ruche->byLogicalId($dest . '/Ruche', 'Abeille')->getConfiguration('IEEE', 'none');
                    } else {
                        $rucheIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
                    }
                    log::add('Abeille', 'debug', 'IEEE pour  Ruche: ' . $rucheIEEE);

                    $currentCommandId = $this->getId();
                    $currentObjectId = $this->getEqLogic_id();
                    log::add('Abeille', 'debug', 'Id pour current abeille: ' . $currentObjectId);

                    // ne semble pas rendre la main si l'objet n'a pas de champ "IEEE-Addr"
                    $commandIEEE = $command->byEqLogicIdAndLogicalId($currentObjectId, 'IEEE-Addr')->execCmd();

                    // print_r( $command->execCmd() );
                    log::add('Abeille', 'debug', 'IEEE pour current abeille: ' . $commandIEEE);

                    // $elogic->byLogicalId( 'Abeille/b528', 'Abeille' );
                    // print_r( $objet->byLogicalId( 'Abeille/b528', 'Abeille' )->getId() );
                    // echo "\n";
                    // print_r( $command->byEqLogicIdAndLogicalId( $objetId, "IEEE-Addr" )->getLastValue() );

                    $request = str_replace('#addrIEEE#', $commandIEEE, $request);
                    $request = str_replace('#ZiGateIEEE#', $rucheIEEE, $request);
                }

                switch ($this->getSubType()) {
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

                $request = str_replace('\\', '', jeedom::evaluateExpression($request));
                $request = cmd::cmdToValue($request);

                $msgAbeille = new MsgAbeille;

                $msgAbeille->message['topic'] = $topic;
                $msgAbeille->message['payload'] = $request;

                log::add('Abeille', 'Debug', 'topic: ' . $topic . ' request: ' . $request);

                if (strpos($topic, "CmdCreate") === 0) {
                    $queueKeyAbeilleToAbeille = msg_get_queue(queueKeyAbeilleToAbeille);
                    if (msg_send($queueKeyAbeilleToAbeille, 1, $msgAbeille, true, false)) {
                        log::add('Abeille', 'debug', '(CmdCreate) Msg sent: ' . json_encode($msgAbeille));
                    } else {
                        log::add('Abeille', 'debug', '(CmdCreate) Could not send Msg');
                    }
                } else {
                    $queueKeyAbeilleToCmd = msg_get_queue(queueKeyAbeilleToCmd);
                    if (msg_send($queueKeyAbeilleToCmd, priorityUserCmd, $msgAbeille, true, false)) {
                        log::add('Abeille', 'debug', '(All) Msg sent: ' . json_encode($msgAbeille));
                    } else {
                        log::add('Abeille', 'debug', '(All) Could not send Msg');
                    }
                }
        }

        return true;
    }
}

?>
