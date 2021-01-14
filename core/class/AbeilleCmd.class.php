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
                
                // CmdCreate n est dans aucun template donc ne semble pas necessaire dans execute()
                // if (strpos($this->getConfiguration('topic'), "CmdCreate") === 0) {
                //         $topic = $this->getConfiguration('topic');
                // }
                
                log::add('Abeille', 'Debug', 'topic: ' . $topic);

                /* ------------------------------ */
                // Je fais les remplacement dans la commande (ex: addGroup pour telecommande Ikea 5 btn)
                if (strpos($topic, "#addrGroup#") > 0) {
                    $topic = str_replace("#addrGroup#", $this->getEqLogic()->getConfiguration("Groupe"), $topic);
                }

                // -------------------------------------------------------------------------
                // Process Request
                $request = $this->getConfiguration('request', '1');
                // request: c'est le payload dans la page de configuration pour une commande
                // C est les parametres de la commande pour la zigate
                log::add('Abeille', 'Debug', 'request: ' . $request);

                /* ------------------------------ */
                // // Je fais les remplacement dans les parametres (ex: addGroup pour telecommande Ikea 5 btn)
                if (strpos($request, "#addrGroup#") > 0) {
                    $request = str_replace("#addrGroup#", $this->getEqLogic()->getConfiguration("Groupe"), $request);
                }

                if (strpos($request, '#onTime#') > 0) {
                    $onTimeHex = sprintf("%04s", dechex($this->getEqLogic()->getConfiguration("onTime") * 10));
                    $request = str_replace("#onTime#", $onTimeHex, $request);
                }

                if (strpos($request, '#addrIEEE#') > 0) {
                    $commandIEEE = $this->getEqLogic()->getConfiguration("IEEE",'none');
                    if (strlen($commandIEEE)!=16) {
                        log::add('Abeille', 'Debug', 'Adresse IEEE de l equipement ' . $this->getEqLogic()->getHumanName() . ' inconnue');
                        return true;
                    }
                    $request = str_replace('#addrIEEE#', $commandIEEE, $request);
                }

                if (strpos($request, '#ZiGateIEEE#') > 0) {
                    // Logical Id ruche de la forme: Abeille1/Ruche
                    $rucheIEEE = Abeille::byLogicalId($dest . '/Ruche', 'Abeille')->getConfiguration("IEEE",'none');
                    log::add('Abeille', 'Debug', 'Adresse IEEE de la ruche ' . $rucheIEEE);
                    if (strlen($rucheIEEE)!=16) {
                        log::add('Abeille', 'Debug', 'Adresse IEEE de la ruche ' . $this->getEqLogic()->getHumanName() . ' inconnue');
                        return true;
                    }
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
