<?php
    /* This file is part of Jeedom.
    *
    * Jeedom is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * Jeedom is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
    */

    include_once __DIR__.'/../config/Abeille.config.php';

    /* Developers debug features */
    if (file_exists(dbgFile)) {
        // include_once dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../../resources/AbeilleDeamon/includes/function.php';
    include_once __DIR__.'/AbeilleTools.class.php';
    include_once __DIR__.'/AbeilleMsg.php';
    include_once __DIR__.'/AbeilleCmd.class.php';
    include_once __DIR__.'/../../plugin_info/install.php'; // updateConfigDB()

class Abeille extends eqLogic
{
    /**
     * migrateBetweenZigates()
     *
     * @param beeId: bee Id to be moved
     * @param zigateY: zigate de destination
     *
     * @return none
     *
     * https://github.com/KiwiHC16/Abeille/issues/1771
     *
     * 1/ Changement logical Id
     * 2/ Remove zigbee reseau 1 zigbee
     * 3/ inclusion normale sur le reseau 2 zigbee
     *
     * Faire un bouton qui fait les etapes 1/ et 2 puis demander à l'utilisateur de faire l'étape 3
     *
     */
    public static function migrateBetweenZigates($beeId, $zigateY)
    {
        $bee = Abeille::byId($beeId);
        if (!is_object($bee)) {
            log::add('Abeille', 'debug', 'Erreur je ne trouve pas l abeille, je ne peux faire l operation.');
            return;
        }

        $IEEE = $bee->getConfiguration('IEEE', 'none');
        if ( $IEEE=='none' ) {
            log::add('Abeille', 'debug', 'L Abeille na pas d adresse IEEE connue, je ne peux faire l operation.');
        }

        if ($zigateY > config::byKey('zigateNb', 'Abeille', '0', 1)) {
            log::add('Abeille', 'debug', 'Cette Zigate n existe pas: '.$zigateY.', je ne peux faire l operation.');
            return;
        }

        list($destBee, $shortBee) = explode('/', $bee->getLogicalId());

        $newDestBee = "Abeille".$zigateY;

        // 1/ Changement logical Id
        $bee->setLogicalId($newDestBee.'/'.$shortBee);
        $bee->save();

        // 2/ Remove zigbee reseau 1 zigbee
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$newDestBee."/0000/Remove", "ParentAddressIEEE=".$IEEE."&ChildAddressIEEE=".$IEEE );

        // 3/ inclusion normale sur le reseau 2 zigbee
        message::add("Abeille", "Je viens de préparer la migration de ".$bee->getHumanName(). ". Veuillez faire maintenant son inclusion dans la zigate: ".$zigateY);
    }

    /**
     * replaceGhost()
     *
     * @param ghostId: Id of the bee to be removed
     * @param realId: Id of the bee which will replace the ghost and receive all informations
     *
     * https://github.com/KiwiHC16/Abeille/issues/1055
     */
    public static function replaceGhost($ghostId, $realId)
    {
        log::add('Abeille', 'debug', 'Lancement de la procedure de remplacement de '.$ghostId.' par '.$realId );
        $ghost = Abeille::byId($ghostId);
        if (!is_object($ghost)) {
            log::add('Abeille', 'debug', 'Erreur je ne trouve pas l abeille ghost.');
            return;
        }
        $real = Abeille::byId($realId);
        if (!is_object($real)) {
            log::add('Abeille', 'debug', 'Erreur je ne trouve pas l abeille réelle.');
            return;
        }

        list($destGhost, $shortGhost) = explode('/', $ghost->getLogicalId());
        list($destReal, $shortReal) = explode('/', $real->getLogicalId());

        // Remove NE from ZigateX
        $IEEE = $ghost->getConfiguration('IEEE', 'none');
        if ( $IEEE=='none' ) {
            log::add('Abeille', 'debug', 'Le ghost n a pas d adresse IEEE connue, je ne peux le retirer de la zigate.');
        }
        else {
            log::add('Abeille', 'debug', 'Je retire '.$ghost->getName().' de la zigate.');
            self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$destGhost."/0000/Remove", "ParentAddressIEEE=".$IEEE."&ChildAddressIEEE=".$IEEE );
        }

        log::add('Abeille', 'debug', 'Transfer des informations de l equipment.' );

        log::add('Abeille', 'debug', 'Nom.' );
        $real->setName($ghost->getName().'_real');

        log::add('Abeille', 'debug', 'Parent.' );
        $real->setObject($ghost->getObject());

        // log::add('Abeille', 'debug', 'Categories.' );
        // $real->setCategory($ghost->getCategory());

        log::add('Abeille', 'debug', 'Notes.' );
        $real->setConfiguration( 'note', $real->getConfiguration('note', '').$ghost->getConfiguration('note', '') );

        log::add('Abeille', 'debug', 'positionX.' );
        $real->setConfiguration( 'positionX', $ghost->getConfiguration('positionX', '') );

        log::add('Abeille', 'debug', 'positionY.' );
        $real->setConfiguration( 'positionY', $ghost->getConfiguration('positionY', '') );

        log::add('Abeille', 'debug', 'positionZ.' );
        $real->setConfiguration( 'positionZ', $ghost->getConfiguration('positionZ', '') );

        log::add('Abeille', 'debug', 'Visibilité.' );
        $real->setIsVisible($ghost->getIsVisible());

        log::add('Abeille', 'debug', 'Enalbed/No.' );
        $real->setIsEnable($ghost->getIsEnable());


        if ( $ghost->getConfiguration('uniqId', '') == $real->getConfiguration('uniqId', '') ) {
            log::add('Abeille', 'debug', 'Transfer du TimeOut car les equipements partagent le meme modele.' );
            $real->setTimeout($ghost->getTimeout());
        }
        else {
            log::add('Abeille', 'debug', 'Pas de transfer du TimeOut car les equipements ne partagent pas le meme modele.' );
        }

        // Parcours toutes les commandes pour recuperer les historiques, remplacer dans jeedom les instances de #ghost-cmd# par #real-cmd#
        log::add('Abeille', 'debug', 'Transfer des commandes une a une.' );
        foreach ($ghost->getCmd() as $numGhost => $ghostCmd) {
            foreach ($real->getCmd() as $numReal => $realCmd) {
                if ($ghostCmd->getLogicalId() == $realCmd->getLogicalId()) {
                    if ($ghostCmd->getSubType() == $realCmd->getSubType()) {
                        if ($ghostCmd->getType() == 'info' && $realCmd->getType() == 'info') {
                            if ($ghostCmd->getIsHistorized() == 1) {
                                // -- migrer l historique des commandes AbeilleY/YYYY vers AbeilleX/XXXX, les instances des commandes dans scenario et autres
                                // history::copyHistoryToCmd('#17009#', '#17251#');
                                // echo 'Copy history from '.$ghostCmd->getName().' to '.$realCmd->getName()."\n";
                                log::add('Abeille', 'debug', 'Transfer de l historique pour la commande: '.$ghostCmd->getName() );
                                history::copyHistoryToCmd($ghostCmd->getId(), $realCmd->getId());
                            }
                        }
                        // -- migrer les instances des commandes dans scenario et autres
                        log::add('Abeille', 'debug', 'Transfer des instance de la commande: '.$ghostCmd->getName().' dans jeedom.' );
                        jeedom::replaceTag(array('#'.$ghostCmd->getId().'#' => '#'.$realCmd->getId().'#'));
                    }
                }
            }
        }

        // -- supprimer l Abeille ghost
        log::add('Abeille', 'debug', 'Suppression de l eq ghost.' );
        $ghost->remove();

        // Sauvegarde
        log::add('Abeille', 'debug', 'Sauvegarde du nouvel eq.' );
        $real->save();

        return;
    }

    // Fonction dupliquée dans AbeilleParser.
    public static function volt2pourcent($voltage)
    {
        $max = 3.135;
        $min = 2.8;
        if ($voltage / 1000 > $max) {
            log::add('Abeille', 'debug', 'Voltage remonte par le device a plus de '.$max.'V. Je retourne 100%.');
            return 100;
        }
        if ($voltage / 1000 < $min) {
            log::add('Abeille', 'debug', 'Voltage remonte par le device a moins de '.$min.'V. Je retourne 0%.');
            return 0;
        }
        return round(100 - ((($max - ($voltage / 1000)) / ($max - $min)) * 100));
    }

    /**
     * Function return data for santé page
     *
     * @param none
     *
     * @return test   title/decription of the test
     * @return result test result
     * @return advice comment by question mark icone
     * @return state  if the test was successful or not
     */
    public static function health()
    {
        $return = array();
        $result = '';

        for ($i = 1; $i <= config::byKey('zigateNb', 'Abeille', '1', 1); $i++) {
            if ( config::byKey('AbeilleActiver'.$i, 'Abeille', '1', 1) ) {
                $result .= config::byKey('AbeilleSerialPort'.$i, 'Abeille', '', 1);
            }
        }

        $return[] = array(
            'test' => 'Ports: ',             // title of the line
            'result' => $result,             // Text which be printed in the line
            'advice' => 'Ports utilisés',    // Text printed when mouse is on question mark icone
            'state' => true,                // Status du plugin: true line will be green, false line will be red.
        );

        return $return;
    }

    /**
     * Looking for missing IEEE addresses
     * Will fetch information from zigbee network to get all missing IEEE
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function tryToGetIEEE()
    {
        log::add('Abeille', 'debug', 'Recherche des adresses IEEE manquantes.');
        $tryToGetIEEEArray = array();
        $eqLogics = Abeille::byType('Abeille');

        foreach ($eqLogics as $key => $eqLogic) {
            if ($eqLogic->getIsEnable() != 1) {
                log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' désactivé => ignoré');
                continue; // Eq disabled => ignored
            }
            if ($eqLogic->getTimeout() == 1) {
                log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' en timeout => ignoré');
                continue; // Eq in timeout => ignored
            }
            if ($eqLogic->getStatus('lastCommunication') == '') {
                log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' n\'a jamais communiqué => ignoré');
                continue; // Eq in timeout => ignored
            }

            if (strlen($eqLogic->getConfiguration('IEEE', 'none')) == 16) {
                continue; // J'ai une adresse IEEE dans la conf donc je passe mon chemin
            }

            $commandIEEE = $eqLogic->getCmd('info', 'IEEE-Addr');
            if ($commandIEEE == null) {
                log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' sans cmd \'IEEE-Addr\' => ignoré');
                continue; // No cmd to retrieve IEEE address. Normal ?
            }

            if (strlen($commandIEEE->execCmd()) == 16) {
                $eqLogic->setConfiguration('IEEE', $commandIEEE->execCmd()); // Si je suis a cette ligne c est que je n ai pas IEEE dans conf mais dans cmd alors je mets dans conf.
                $eqLogic->save();
                $eqLogic->refresh();
                continue; // J'ai une adresse IEEE dans la commande donc je passe mon chemin
            }

            $tryToGetIEEEArray[] = $key;
        }

        // Prend x abeilles au hasard dans cette liste d'abeille a interroger.
        $eqLogicIds = array_rand($tryToGetIEEEArray, 2);

        // Pour ces x Abeilles lance l interrogation
        foreach ($eqLogicIds as $eqLogicId) {
            echo "Start Loop: ".$eqLogicId."\n";
            // echo "Start Loop Detail: ";
            $eqLogicX = $eqLogics[$tryToGetIEEEArray[$eqLogicId]];
            // var_dump($eqLogic);
            $commandIEEE_X = $eqLogicX->getCmd('info', 'IEEE-Addr');
            if ($commandIEEE_X) {
                $addrIEEE_X = $commandIEEE_X->execCmd();
                if (strlen($addrIEEE_X) < 2) {
                    list($dest, $NE) = explode('/', $eqLogicX->getLogicalId());
                    if (strlen($NE) == 4) {
                        if ($eqLogicX->getIsEnable()) {
                            log::add('Abeille', 'debug', 'Demarrage tryToGetIEEE for '.$NE);
                            echo 'Demarrage tryToGetIEEE for '.$NE."\n";
                            $cmd = "/usr/bin/nohup php ".__DIR__."/../php/AbeilleInterrogate.php ".$dest." ".$NE." >/dev/null 2>&1 &";
                            // echo "Cmd: ".$cmd."\n";
                            exec($cmd, $out, $status);
                        } else echo "Je n essaye pas car Abeille inactive.\n";
                    } else echo "Je n ai pas recuperé l adresse courte !!!\n";
                } else echo "IEEE superieure à deux carateres !!! :".$addrIEEE_X."\n";
            } else echo "commandIEEE n existe pas !!!!\n";
        }
    }

    public static function updateConfigAbeille($abeilleIdFilter = false)
    {

    }

    /**
     * pollingCmd
     * Collect all cmd with Polling define and execute it
     *
     * @param   $period One of the crons: cron, cron15, cronHourly....
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function pollingCmd($period)
    {
        foreach (AbeilleCmd::searchConfiguration('Polling', 'Abeille') as $key => $cmd) {
            if ($cmd->getConfiguration('Polling') == $period) {
                $cmd->execute();
            }
        }
    }

    /**
     * RefreshCmd
     * Execute all cmd to update cmd info (e.g: after a long stop of Abeille to  get all data)
     *
     * @param   none
     *
     * @return  Does not return anything as all action are triggered by sending messages in queues
     */
    public static function refreshCmd()
    {
        log::add('Abeille', 'debug', 'refreshCmd: start');
        $i=15;
        foreach (AbeilleCmd::searchConfiguration('RefreshData', 'Abeille') as $key => $cmd) {
            if ($cmd->getConfiguration('RefreshData',0)) {
                log::add('Abeille', 'debug', 'refreshCmd: '.$cmd->getHumanName().' ('.$cmd->getEqlogic()->getLogicalId().')' );
                // $cmd->execute(); le process ne sont pas tous demarrer donc on met une tempo.
                $topic   = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getLogicalId();
                $request = $cmd->getConfiguration('request');
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$topic."&time=".(time()+$i), $request );
                $i++;
            }
        }
        log::add('Abeille', 'debug', 'refreshCmd: end');
    }

    /**
     * getIEEE
     * get IEEE from the eqLogic
     *
     * @param   $address    logicalId of the eqLogic
     *
     * @return              Does not return anything as all action are triggered by sending messages in queues
     */
    public static function getIEEE($address)
    {
        if (strlen(self::byLogicalId($address, 'Abeille')->getConfiguration('IEEE', 'none')) == 16) {
            return self::byLogicalId($address, 'Abeille')->getConfiguration('IEEE', 'none');
        } else {
            return AbeilleCmd::byEqLogicIdAndLogicalId(self::byLogicalId($address, 'Abeille')->getId(), 'IEEE-Addr')->execCmd();
        }
    }

    /**
     * getEqFromIEEE
     * get eqLogic from IEEE
     *
     * @param   $IEEE    IEEE of the device
     *
     * @return           eq with this IEEE or Null if not found
     */
    public static function getEqFromIEEE($IEEE)
    {
        foreach (self::searchConfiguration('IEEE', 'Abeille') as $eq) {
            if ($eq->getConfiguration('IEEE') == $IEEE) {
                return $eq;
            }
        }
        return null;
    }

    /**
     * cronDaily
     * Called by Jeedom every days.
     * Refresh LQI
     * Poll Cmd cronDaily
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cronDaily()
    {
        log::add('Abeille', 'debug', 'Starting cronDaily ------------------------------------------------------------------------------------------------------------------------');

        // Refresh LQI once a day to get IEEE in prevision of futur changes, to get network topo as fresh as possible in json
        log::add('Abeille', 'debug', 'cronD: Lancement de l\'analyse réseau (AbeilleLQI.php)');
        $ROOT = __DIR__."/../php";
        $cmd = "cd ".$ROOT."; nohup /usr/bin/php AbeilleLQI.php 1>/dev/null 2>/dev/null &";
        log::add('Abeille', 'debug', 'cronD: cmd=\''.$cmd.'\'');
        exec($cmd);

        // Poll Cmd
        self::pollingCmd("cronDaily");
    }

    /**
     * cronHourly
     * Called by Jeedom every 60 minutes.
     * Refresh Ampoule Ikea Bind et set Report
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cronHourly()
    {
        log::add('Abeille', 'debug', 'Starting cronHourly ------------------------------------------------------------------------------------------------------------------------');
        log::add('Abeille', 'debug', 'Check Zigate Presence');

        $param = AbeilleTools::getParameters();
if (0) {
        //--------------------------------------------------------
        // Refresh Ampoule Ikea Bind et set Report
        log::add('Abeille', 'debug', 'Refresh Ampoule Ikea Bind et set Report');

        $eqLogics = Abeille::byType('Abeille');
        $i = 0;
        foreach ($eqLogics as $eqLogic) {
            // Filtre sur Ikea
            if (strpos("_".$eqLogic->getConfiguration("icone"), "IkeaTradfriBulb") > 0) {
                list($dest, $addr) = explode("/", $eqLogic->getLogicalId());
                $i = $i + 1;

                // Recupere IEEE de la Ruche/ZiGate
                $ZiGateIEEE = self::getIEEE($dest.'/0000');

                // Recupere IEEE de l Abeille
                $addrIEEE = self::getIEEE($dest.'/'.$addr);

                log::add('Abeille', 'debug', 'Refresh bind and report for Ikea Bulb: '.$addr);
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/0000/bindShort&time=".(time() + (($i * 33) + 1)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0006&reportToAddress=".$ZiGateIEEE);
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/0000/bindShort&time=".(time() + (($i * 33) + 2)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0008&reportToAddress=".$ZiGateIEEE);
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/0000/setReport&time=".(time() + (($i * 33) + 3)), "address=".$addr."&ClusterId=0006&AttributeId=0000&AttributeType=10");
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/0000/setReport&time=".(time() + (($i * 33) + 4)), "address=".$addr."&ClusterId=0008&AttributeId=0000&AttributeType=20");
            }
        }
        if (($i * 33) > (3600)) {
            message::add("Abeille", "Danger il y a trop de message a envoyer dans le cron 1 heure.", "Contactez KiwiHC16 sur le Forum.");
        }
    }
        //--------------------------------------------------------
        // Poll Cmd
        self::pollingCmd("cronHourly");

        log::add('Abeille', 'debug', 'Ending cronHourly ------------------------------------------------------------------------------------------------------------------------');
    }

    /**
     * cron30
     * Called by Jeedom every 30 minutes.
     * pollingCmd
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron30() {
        // Poll Cmd
        self::pollingCmd("cron30");
    }

    /**
     * cron15
     * Called by Jeedom every 15 minutes.
     * Will send a message Annonce to equipement to refresh TimeOut status
     * Will execute all action cmd needed to refresh some info command
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron15()
    {
        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron15: Main daemon stopped => cron15 canceled');
            return;
        }

        log::add('Abeille', 'debug', 'cron15: Starting --------------------------------');

        /* Look every 15 minutes if the kernel driver is not in error */
        log::add('Abeille', 'debug', 'cron15: Check USB driver potential crash');
        $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
        $output = array();
        exec(system::getCmdSudo().$cmd, $output);
        $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
        if ($usbZigateStatus != '0') {
            message::add("Abeille", "ERREUR: le pilote pl2303 semble en erreur, impossible de communiquer avec la zigate.", "Il faut débrancher/rebrancher la zigate et relancer le démon.");
            // log::add('Abeille', 'debug', 'cron15: Fin --------------------------------');
        }

        log::add('Abeille', 'debug', 'cron15: Interrogating devices silent for more than 15mins.');
        $config = AbeilleTools::getParameters();
        $i = 0;
        for ($zgNb = 1; $zgNb <= $config['zigateNb']; $zgNb++) {
            $zigate = Abeille::byLogicalId('Abeille'.$zgNb.'/0000', 'Abeille');
            if (!is_object($zigate))
                continue; // Probably deleted on Jeedom side.
            if (!$zigate->getIsEnable())
                continue; // Zigate disabled
            if ($config['AbeilleActiver'.$zgNb] != 'Y')
                continue; // Zigate disabled.

            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list($dest, $addr) = explode("/", $eqLogic->getLogicalId());
                if ($dest != 'Abeille'.$zgNb)
                    continue; // Not on current network
                if (!$eqLogic->getIsEnable())
                    continue; // Equipment disabled

                /* Special case: should ignore virtual remote */
                if ($eqLogic->getConfiguration("modeleJson") == "remotecontrol")
                    continue; // Abeille virtual remote

                $eqName = $eqLogic->getname();

                /* Checking if received some news in the last 15mins */
                $cmd = $eqLogic->getCmd('info', 'Time-TimeStamp');
                if (!is_object($cmd)) { // Cmd not found
                    log::add('Abeille', 'warning', "cron15: Commande 'Time-TimeStamp' manquante pour ".$eqName);
                    continue; // No sense to interrogate EQ if Time-TimeStamp does not exists
                }
                $lastComm = $cmd->execCmd();
                if (!is_numeric($lastComm)) { // Does it avoid PHP warning ?
                    // No comm from EQ yet.
                    $daemonsStart = config::byKey('lastDeamonLaunchTime', 'Abeille', '');
                    if ($daemonsStart == '')
                        continue; // Daemons not started yet
                    $lastComm = strtotime($daemonsStart);
                }
                if ((time() - $lastComm) <= (15 * 60))
                    continue; // Alive within last 15mins. No need to interrogate.

                /* No news in the last 15mins. Need to interrogate this eq */
                $mainEP = $eqLogic->getConfiguration("mainEP");
                if (strlen($mainEP) <= 1) {
                    log::add('Abeille', 'warning', "cron15: 'End Point' principal manquant pour ".$eqName);
                    continue;
                }
                $poll = 0;
                if ($eqLogic->getConfiguration("RxOnWhenIdle", 'none') == 1)
                    $poll = 1;

                if (strlen($eqLogic->getConfiguration("battery_type", '')) == 0)
                    $poll += 10;

                if ($eqLogic->getConfiguration("poll", 'none') == "15")
                    $poll += 100;

                if ($poll > 0) {
                    log::add('Abeille', 'debug', "cron15: Interrogating '".$eqName."' (addr ".$addr.", poll-reason=".$poll.")");
                    Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), $mainEP);
                    $i++;
                }
            }
        }
        if (($i * 23) > (15 * 60)) { // A msg every 23sec must fit in 15mins.
            message::add("Abeille", "Danger ! Il y a trop de messages à envoyer dans le cron 15 minutes.", "Contacter KiwiHC15 sur le forum");
        }

        // Execute Action Cmd to refresh Info command
        // self::pollingCmd("cron15");

        log::add('Abeille', 'debug', 'cron15:Terminé --------------------------------');
        return;
    }

    /**
     * cron10
     * Called by Jeedom every 10 minutes.
     * pollingCmd
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron10() {
        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron10: Main daemon stopped => cron10 canceled');
            return;
        }

        // Poll Cmd
        self::pollingCmd("cron10");
    }

    /**
     * cron5
     * Called by Jeedom every 5 minutes.
     * pollingCmd
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron5() {
        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron5: Main daemon stopped => cron5 canceled');
            return;
        }

        // Poll Cmd
        self::pollingCmd("cron5");
    }

    /**
     * cron1
     * - Called by Jeedom every 1 minutes.
     * - Check (& restart if required) daemons status
     * - Check queues status
     * - Poll WIFI Zigate to keep esplink open
     * Polling 1 minute sur etat et level
     * Refresh health information
     * Refresh inclusion state
     * Exec Cmd action which are needed to refresh cmd info
     *
     * @param none
     * @return nothing
     */
    public static function cron()
    {
        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron1: Main daemon stopped => cron1 canceled');
            return;
        }

        // log::add( 'Abeille', 'debug', 'cron1: Start ------------------------------------------------------------------------------------------------------------------------' );
        $param = AbeilleTools::getParameters();

        $running = AbeilleTools::getRunningDaemons();
        $status = AbeilleTools::checkAllDaemons($param, $running);

        /* For debug purposes, display 'PID/daemonShortName' */
        $status = AbeilleTools::getRunningDaemons2();
        $daemons = "";
        foreach ($status['daemons'] as $daemon) {
            if ($daemons != "")
                $daemons .= ", ";
            $daemons .= $daemon['pid'].'/'.$daemon['shortName'];
        }
        log::add('Abeille', 'debug', 'cron1: '.$daemons);

        // /* Store infos in "shared mem" to share with other functions */
        // $shm = shm_attach(12, 200, 0600);
        // if ($shm === false) {
        //     log::add('Abeille', 'debug', "cron1: ERROR shm_attach()");
        // } else {
        //     // Note: 13 = '$status['running']' ID, 1 bit per running daemon
        //     shm_put_var($shm, 13, $status['running']);
        //     shm_detach($shm);
        // }

        // Check ipcs situation pour detecter des soucis eventuels
        // Moved from deamon_info()
        if (msg_stat_queue(msg_get_queue(queueKeyAbeilleToAbeille))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyAbeilleToAbeille');
        if (msg_stat_queue(msg_get_queue(queueKeyAbeilleToCmd))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyAbeilleToCmd');
        if (msg_stat_queue(msg_get_queue(queueKeyParserToAbeille))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyParserToAbeille');
        if (msg_stat_queue(msg_get_queue(queueKeyParserToCmd))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyParserToCmd');
        if (msg_stat_queue(msg_get_queue(queueKeyParserToLQI))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyParserToLQI');
        if (msg_stat_queue(msg_get_queue(queueKeyCmdToAbeille))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyCmdToAbeille');
        if (msg_stat_queue(msg_get_queue(queueKeyCmdToCmd))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyCmdToCmd');
        if (msg_stat_queue(msg_get_queue(queueKeyLQIToAbeille))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyLQIToAbeille');
        if (msg_stat_queue(msg_get_queue(queueKeyLQIToCmd))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyLQIToCmd');
        if (msg_stat_queue(msg_get_queue(queueKeyXmlToAbeille))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyXmlToAbeille');
        if (msg_stat_queue(msg_get_queue(queueKeyXmlToCmd))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyXmlToCmd');
        if (msg_stat_queue(msg_get_queue(queueKeyFormToCmd))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyFormToCmd');
        if (msg_stat_queue(msg_get_queue(queueKeySerialToParser))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeySerialToParser');
        if (msg_stat_queue(msg_get_queue(queueKeyParserToCmdSemaphore))["msg_qnum"] > 100) log::add('Abeille', 'info', 'deamon_info(): --------- ipcs queue too full: queueKeyParserToCmdSemaphore');

        // https://github.com/jeelabs/esp-link
        // The ESP-Link connections on port 23 and 2323 have a 5 minute inactivity timeout.
        // so I need to create a minimum of traffic, so pull zigate every minutes
        for ($i = 1; $i <= $param['zigateNb']; $i++) {
            if ($param['AbeilleActiver'.$i] != 'Y')
                continue; // Zigate disabled
            if ($param['AbeilleSerialPort'.$i] == "none")
                continue; // Serial port undefined
            // TODO Tcharp38: Currently leads to PI zigate timeout. No sense since still alive.
            // if ($param['AbeilleType'.$i] != "WIFI")
            //     continue; // Not a WIFI zigate. No polling required

            Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille".$i."/0000/getVersion&time=".(time() + 20), "Version");
            // beille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille".$i."/0000/getNetworkStatus&time=".(time() + 24), "getNetworkStatus");
        }

        $eqLogics = self::byType('Abeille');

        /* Refresh status for equipements which require 1min polling */
        $i = 0;
        foreach ($eqLogics as $eqLogic) {
            if (!$eqLogic->getIsEnable())
                continue; // Equipment disabled
            if ($eqLogic->getConfiguration("poll") != "1")
                continue; // No 1min polling requirement

            list($dest, $address) = explode("/", $eqLogic->getLogicalId());
            if (strlen($address) != 4)
                continue; // Bad address, needed for virtual device

            log::add('Abeille', 'debug', 'cron1: GetEtat/GetLevel, addr='.$address);
            $mainEP = $eqLogic->getConfiguration('mainEP');
            Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$mainEP."&clusterId=0006&attributeId=0000");
            Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$mainEP."&clusterId=0008&attributeId=0000");
            $i++;
        }
        if (($i * 3) > 60) {
            message::add("Abeille", "Danger ! Il y a trop de messages à envoyer dans le cron 1 minute.", "Contacter KiwiHC15 sur le forum.");
        }

        // Poll Cmd
        self::pollingCmd("cron");

        /**
         * Refresh health information
         */
        // log::add('Abeille', 'debug', '----------- Refresh health information');
        //$eqLogics = self::byType('Abeille');

        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getTimeout() > 0) {
                if (strtotime($eqLogic->getStatus('lastCommunication')) > 0) {
                    $last = strtotime($eqLogic->getStatus('lastCommunication'));
                } else {
                    $last = 0;
                }
                // Alerte sur TimeOut Defini
                if (($last + (60 * $eqLogic->getTimeout())) > time()) {
                    // Ok
                    $eqLogic->setStatus('state', 'ok');
                    $eqLogic->setStatus('timeout', 0);
                } else {
                    // NOK
                    $eqLogic->setStatus('state', 'Time Out Last Communication');
                    $eqLogic->setStatus('timeout', 1);
                }
                // ===============================================================================================================================
                // log::add( 'Abeille', 'debug', 'Name: '.$eqLogic->getName().' lastCommunication: '.$eqLogic->getStatus( "lastCommunication" ).' timeout value: '.$eqLogic->getTimeout().' timeout status: '.$eqLogic->getStatus( 'timeout' ).' state: '.$eqLogic->getStatus('state'));
            } else {
                $eqLogic->setStatus('state', '-');
                $eqLogic->setStatus('timeout', 0);
            }
        }

        // Si Inclusion status est à 1 on demande un Refresh de l information
        // Je regarde si j ai deux zigate en inclusion et si oui je genere une alarme.
        $count = array();
        for ($i = 1; $i <= $param['zigateNb']; $i++) {
            if (self::checkInclusionStatus("Abeille".$i) == 1) {
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/permitJoin", "Status");
                $count[] = $i;
            }
        }
        if (count($count) > 1) message::add("Abeille", "Danger vous avez plusieurs Zigate en mode inclusion: ".json_encode($count).". L equipement peut se joindre a l un ou l autre resau zigbee.", "Vérifier sur quel reseau se joint l equipement.");

        // log::add( 'Abeille', 'debug', 'cron1: Fin ------------------------------------------------------------------------------------------------------------------------' );
    }

    /**
     * Jeedom required function: report plugin & config status
     * @param none
     * @return array with state, launchable, launchable_message
     */
    public static function deamon_info()
    {
        /* Notes:
           Since Abeille has its own way to restart missing daemons, reporting only
           cron status as global Abeille status to avoid conflict between
           - Jeedom asking daemons restart (automatic management)
           - Abeille internal daemons restart mecanism */

        /* Init with valid status */
        $status = array(
            'state' => 'ok',  // Assuming cron is running
            'launchable' => 'ok',  // Assuming config ok
            'launchable_message' => ""
        );

        /* Checking there is no error getting parameters and daemon can be started. */
        // TODO: Tcharp38. Can it be optimized ?. Each deamon_info() call leads to mysql DB interrogation.
        $config = AbeilleTools::getParameters();
        if ($config['parametersCheck'] != "ok") {
            $status['launchable'] = $config['parametersCheck'];
            // Tcharp38: Where is reported 'launchable_message' ?
            $status['launchable_message'] = $config['parametersCheck_message'];
            log::add('Abeille', 'warning', 'deamon_info(): Config zigate invalide');
        }

        /* Checking main cron = main Abeille's daemon */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            $status['state'] = "nok";
            log::add('Abeille', 'warning', 'deamon_info(): Main daemon is not runnning.');
        }

        log::add('Abeille', 'debug', 'deamon_info(): '.json_encode($status));
        return $status;
    }

    /* This function is used before starting daemons to
        - run some cleanup
        - update the config database if changes needed
        Note: incorrect naming 'deamon' instead of 'daemon' due to Jeedom mistake. */
    public static function deamon_start_cleanup()
    {
        // log::add('Abeille', 'debug', 'deamon_start_cleanup(): Démarrage');

        message::removeAll('Abeille');

        // Remove temporary files
        for ($i = 1; $i <= config::byKey('zigateNb', 'Abeille', '1', 1); $i++) {
            $lockFile = jeedom::getTmpFolder('Abeille').'/AbeilleLQI_MapDataAbeille'.$i.'.json.lock';
            if (file_exists($lockFile)) {
                unlink($lockFile);
                log::add('Abeille', 'debug', 'deamon_start_cleanup(): Removed '.$lockFile);
            }
        }

        // Desactive les Zigate pour eviter de discuter avec une zigate sur le mauvais port
        // AbeilleIEEE_Ok = -1 si la Zigate detectée n est pas la bonne
        //     "          =  0 pour demarrer
        //     "          =  1 Si la zigate detectée est la bonne
        for ($i = 1; $i <= config::byKey('zigateNb', 'Abeille', '1', 1); $i++) {
            config::save("AbeilleIEEE_Ok".$i, 0, 'Abeille');
        }

        /* Checking configuration DB version.
               If standard user, this should be done by installation process (install.php).
               If user based on GIT, this is a work-around */
        $dbVersion = config::byKey('DbVersion', 'Abeille', '');
        $dbVersionLast = 20201122;
        if (($dbVersion == '') || (intval($dbVersion) < $dbVersionLast)) {
            log::add('Abeille', 'debug', 'deamon_start_cleanup(): DB config v'.$dbVersion.' < v'.$dbVersionLast.' => Mise-à-jour');
            updateConfigDB();
        }

        // log::add('Abeille', 'debug', 'deamon_start_cleanup(): Terminé');
        return;
    }

    /* Jeedom required function.
       Starts all daemons.
       Note: incorrect naming 'deamon' instead of 'daemon' due to Jeedom mistake. */
    public static function deamon_start($_debug = false)
    {
        log::add('Abeille', 'debug', 'deamon_start(): Démarrage');

        /* Some checks before starting daemons
               - Are dependancies ok ?
               - does Abeille cron exist ? */
        if (self::dependancy_info()['state'] != 'ok') {
            message::add("Abeille", "Tentative de demarrage alors qu il y a un soucis avec les dependances", "Avez vous installée les dépendances.");
            log::add('Abeille', 'debug', "Tentative de demarrage alors qu il y a un soucis avec les dependances");
            return false;
        }
        if (!is_object(cron::byClassAndFunction('Abeille', 'deamon'))) {
            log::add('Abeille', 'error', 'deamon_start(): Tache cron introuvable');
            message::add("Abeille", "deamon_start(): Tache cron introuvable", "Est ce un bug dans Abeille ?");
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }

        /* Stop all, in case not already the case */
        self::deamon_stop();

        /* Cleanup */
        self::deamon_start_cleanup();

        $param = AbeilleTools::getParameters();

        /* Checking config */
        // TODO Tcharp38: Should be done during deamon_info() and report proper 'launchable'
        for ($zgNb = 1; $zgNb <= $param['zigateNb']; $zgNb++) {
            if ($param['AbeilleActiver'.$zgNb] != 'Y')
                continue; // Disabled

            /* This zigate is enabled. Checking other parameters */
            $error = "";
            $sp = $param['AbeilleSerialPort'.$zgNb];
            if (($sp == 'none') || ($sp == "")) {
                $error = "Port série de la zigate ".$zgNb." INVALIDE";
            }
            if ($error == "") {
                if ($param['AbeilleType'.$zgNb] == "WIFI") {
                    $wifiAddr = $param['IpWifiZigate'.$zgNb];
                    if (($wifiAddr == 'none') || ($wifiAddr == "")) {
                        $error = "Adresse Wifi de la zigate ".$zgNb." INVALIDE";
                    }
                }
            }
            if ($error != "") {
                $param['AbeilleActiver'.$zgNb] = 'N';
                config::save('AbeilleActiver'.$zgNb, 'N', 'Abeille');
                log::add('Abeille', 'error', $error." ! Zigate désactivée.");
            }
        }

        /* Configuring GPIO for PiZigate if one active found.
            PiZigate reminder (using 'WiringPi'):
            - port 0 = RESET
            - port 2 = FLASH
            - Production mode: FLASH=1, RESET=0 then 1 */
        for ($i = 1; $i <= $param['zigateNb']; $i++) {
            if (($param['AbeilleSerialPort'.$i] == 'none') or ($param['AbeilleActiver'.$i] != 'Y'))
                continue; // Undefined or disabled
            if ($param['AbeilleType'.$i] == "PI") {
                AbeilleTools::checkGpio(); // Found an active PI Zigate, needed once
                break;
            }
        }

        // /* Shared mem access */
        // $shm = shm_attach(12, 200, 0600);
        // if ($shm === false) {
        //     log::add('Abeille', 'debug', "deamon_start(): ERROR shm_attach()");
        // } else {
        //     // Note: 13 = '$status['running']' ID, 1 bit per running daemon
        //     shm_put_var($shm, 13, 0); // Clear 'running' status
        //     // shm_detach($shm);
        // }

        /* Starting all required daemons */
        AbeilleTools::startDaemons($param);

        // Starting main daemon; this will start to treat received messages
        cron::byClassAndFunction('Abeille', 'deamon')->run();

        /* Waiting for background daemons to be up & running.
           If not, the return of first commands sent to zigate might be lost.
           This was sometimes the case for 0009 cmd which is key to 'enable' msg receive on parser side. */
        $expected = 0; // 1 bit per expected serial read daemon
        for ($zgNb = 1; $zgNb <= $param['zigateNb']; $zgNb++) {
            if (($param['AbeilleSerialPort'.$zgNb] == 'none') or ($param['AbeilleActiver'.$zgNb] != 'Y'))
                continue; // Undefined or disabled
            $expected |= constant("daemonSerialRead".$zgNb);
            if ($param['AbeilleType'.$zgNb] == 'WIFI')
                $expected |= constant("daemonSocat".$zgNb);
        }
        $timeout = 10;
        for ($t = 0; $t < $timeout; $t++) {
            $runArr = AbeilleTools::getRunningDaemons2();
            if (($runArr['running'] & $expected) == $expected)
                break;
            sleep(1);
        }
        if ($t == $timeout)
            log::add('Abeille', 'debug', 'deamon_start(): ERROR, still some missing daemons after timeout');

        // Tcharp38: Moved to main daemon (deamon())
        // // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
        // for ($i = 1; $i <= $param['zigateNb']; $i++) {
        //     if (($param['AbeilleSerialPort'.$i] == 'none') or ($param['AbeilleActiver'.$i] != 'Y'))
        //         continue; // Undefined or disabled

        //     // log::add('Abeille', 'debug', 'deamon_start(): ***** creation de ruche '.$i.' (Abeille): '.basename($param['AbeilleSerialPort'.$i]));
        //     Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/0000/CreateRuche", "Abeille".$i);

        //     // log::add('Abeille', 'debug', 'deamon_start(): ***** Demarrage du réseau Zigbee '.$i.' ********');
        //     Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/startNetwork", "StartNetwork");
        //     // log::add('Abeille', 'debug', 'deamon_start(): ***** Set Time réseau Zigbee '.$i.' ********');
        //     Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/setTimeServer", "");
        //     /* Get network state to get Zigate IEEE asap and confirm no port change */
        //     Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/getNetworkStatus", "getNetworkStatus");

        //     // Set the mode of the zigate, important from 3.1D.
        //     $version = "";
        //     $ruche = Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille');
        //     if ($ruche) {
        //         $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($ruche->getId(), 'SW-SDK');
        //         if ($cmdlogic) {
        //             $version = $cmdlogic->execCmd();
        //         }
        //     }
        //     if ($version == '031D') {
        //         log::add('Abeille', 'debug', 'deamon_start(): Configuring zigate '. $i.' in hybrid mode');
        //         // message::add("Abeille", "Demande de fonctionnement de la zigate en mode hybride (firmware >= 3.1D).");
        //         Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/setModeHybride", "hybride");
        //     } else {
        //         log::add('Abeille', 'debug', 'deamon_start(): Configuring zigate '.$i.' in normal mode');
        //         // message::add("Abeille", "Demande de fonctionnement de la zigate en mode normal (firmware < 3.1D).");
        //         Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/setModeHybride", "normal");
        //     }
        // }

        // Tcharp38: Moved to main daemon (deamon())
        // Essaye de recuperer les etats des equipements
        // self::refreshCmd();

        log::add('Abeille', 'debug', 'deamon_start(): Terminé');
        return true;
    }

    // /**
    //  * Not used ?
    //  *
    //  * @param $Abeille
    //  * @return string
    //  */
    // public static function mapAbeillePort($Abeille)
    // {
    //     $param = AbeilleTools::getParameters();

    //     for ($i = 1; $i <= $param['zigateNb']; $i++) {
    //         if ($Abeille == "Abeille".$i) return basename($param['AbeilleSerialPort'.$i]);
    //     }
    // }

    // /**
    //  * Not used ?
    //  *
    //  * @param $port
    //  * @return string
    //  */
    // public static function mapPortAbeille($port)
    // {
    //     $param = AbeilleTools::getParameters();

    //     for ($i = 1; $i <= $param['zigateNb']; $i++) {
    //         if ($port == $param['AbeilleSerialPort'.$i]) return "Abeille".$i;
    //     }
    // }

    /* Stopping all daemons and removing queues */
    public static function deamon_stop()
    {
        log::add('Abeille', 'debug', 'deamon_stop(): Démarrage');

        /* Stopping cron */
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (!is_object($cron))
            log::add('Abeille', 'error', 'deamon_stop(): Tache cron introuvable');
        else if ($cron->running()) {
            log::add('Abeille', 'debug', 'deamon_stop(): Arret du cron');
            $cron->halt();
while ($cron->running()) {
    usleep(500000);
    log::add('Abeille', 'debug', 'deamon_stop(): cron STILL running');
}
        } else
            log::add('Abeille', 'debug', 'deamon_stop(): cron déja arrété');

        /* Stopping all 'Abeille' daemons */
        AbeilleTools::stopDaemons();

        /* Removing all queues */
        // Tcharp38: Any way to include allQueues from config ?
        // include_once __DIR__.'/../config/Abeille.config.php';
        $allQueues = array(
            queueKeyAbeilleToAbeille, queueKeyAbeilleToCmd, queueKeyParserToAbeille, queueKeyParserToAbeille2, queueKeyCmdToAbeille,
            queueKeyCmdToMon, queueKeyParserToMon, queueKeyMonToCmd,
            queueKeyAssistToParser, queueKeyParserToAssist, queueKeyAssistToCmd,
            queueKeyParserToLQI, queueKeyLQIToAbeille, queueKeyLQIToCmd,
            queueKeyXmlToAbeille, queueKeyXmlToCmd, queueKeyFormToCmd, queueKeyParserToCli,
            queueKeyParserToCmd, queueKeyCmdToCmd, queueKeySerialToParser, queueKeyParserToCmdSemaphore, queueKeyCtrlToParser
        );
        foreach ($allQueues as $queueId) {
            $queue = msg_get_queue($queueId);
            if ($queue != false)
                msg_remove_queue($queue);
        }

        log::add('Abeille', 'debug', 'deamon_stop(): Terminé');
    }

    public static function dependancy_info()
    {
        log::add('Abeille', 'warning', '-------------------------------------> dependancy_info()');

        // Called by js dans plugin.class.js(getDependancyInfo) -> plugin.ajax.php(dependancy_info())
        // $dependancy_info['state'] pour affichage
        // state = [ok / nok / in_progress (progression/duration)] / state
        // il n ' y plus de dépendance hotmis pour la zigate wifi (socat) qui est installé par un script a part.
        $debug_dependancy_info = 1;

        $return = array();
        $return['state'] = 'ok';
        $return['progress_file'] = jeedom::getTmpFolder('Abeille').'/dependance';

        // Check package socat
        // Tcharp38: Wrong. This dependancy is required only if wifi zigate. Should not impact those using USB or PI
        $cmd = "command -v socat";
        exec($cmd, $output_dpkg, $return_var);
        if ($return_var == 1) {
            message::add("Abeille", "Le package socat est nécéssaire pour l'utilisation de la zigate Wifi. Si vous avez la zigate usb, vous pouvez ignorer ce message");
            log::add('Abeille', 'warning', 'Le package socat est nécéssaire pour l\'utilisation de la zigate Wifi.');
        }

        if ($debug_dependancy_info) log::add('Abeille', 'debug', 'dependancy_info: '.json_encode($return));

        return $return;
    }

    /* Called from Jeedom */
    public static function dependancy_install()
    {
        log::add('Abeille', 'debug', 'Installation des dépendances: IN');
        message::add("Abeille", "Installation des dépendances en cours.", "N'oubliez pas de lire la documentation: https://kiwihc16.github.io/AbeilleDoc");
        log::remove(__CLASS__.'_update');
        $result = [
            'script' => __DIR__.'/../scripts/installDependencies.sh '.jeedom::getTmpFolder('Abeille').'/dependance',
            'log' => log::getPathToLog(__CLASS__.'_update')
        ];
        log::add('Abeille', 'debug', 'Installation des dépendances: OUT: '.implode($result, ' X '));

        return $result;
    }

    /* This is Abeille's main daemon, directly controlled by Jeedom itself. */
    public static function deamon()
    {
        log::add('Abeille', 'debug', 'deamon(): Main daemon starting');

        /* Main daemon starting.
           This means that other daemons have started too. Abeille can communicate with them */

        // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
        // Tcharp38: Moved from deamon_start()
        $param = AbeilleTools::getParameters();
        for ($i = 1; $i <= $param['zigateNb']; $i++) {
            if (($param['AbeilleSerialPort'.$i] == 'none') or ($param['AbeilleActiver'.$i] != 'Y'))
                continue; // Undefined or disabled

            // log::add('Abeille', 'debug', 'deamon(): ***** creation de ruche '.$i.' (Abeille): '.basename($param['AbeilleSerialPort'.$i]));
            Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/0000/CreateRuche", "Abeille".$i);

            // log::add('Abeille', 'debug', 'deamon(): ***** Demarrage du réseau Zigbee '.$i.' ********');
            Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/startNetwork", "StartNetwork");
            // log::add('Abeille', 'debug', 'deamon(): ***** Set Time réseau Zigbee '.$i.' ********');
            Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/setTimeServer", "");
            /* Get network state to get Zigate IEEE asap and confirm no port change */
            Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/getNetworkStatus", "getNetworkStatus");

            // Set the mode of the zigate, important from 3.1D.
            $version = "";
            $ruche = Abeille::byLogicalId('Abeille'.$i.'/0000', 'Abeille');
            if ($ruche) {
                $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($ruche->getId(), 'SW-SDK');
                if ($cmdlogic) {
                    $version = $cmdlogic->execCmd();
                }
            }
            if (($version == '031D') || ($version == '031E')) {
                log::add('Abeille', 'debug', 'deamon(): Configuring zigate '.$i.' in hybrid mode');
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/setModeHybride", "hybride");
            } else {
                log::add('Abeille', 'debug', 'deamon(): Configuring zigate '.$i.' in normal mode');
                Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille".$i."/0000/setModeHybride", "normal");
            }
        }

        // Essaye de recuperer les etats des equipements
        // Tcharp38: Moved from deamon_start()
        self::refreshCmd();

        try {
            $queueKeyAbeilleToAbeille = msg_get_queue(queueKeyAbeilleToAbeille);
            $queueKeyParserToAbeille = msg_get_queue(queueKeyParserToAbeille);
            $queueKeyParserToAbeille2 = msg_get_queue(queueKeyParserToAbeille2);
            $queueKeyCmdToAbeille = msg_get_queue(queueKeyCmdToAbeille);
            $queueKeyXmlToAbeille = msg_get_queue(queueKeyXmlToAbeille);

            $msg_type = NULL;
            $msg = NULL;
            $max_msg_size = 512;
            $message = new MsgAbeille;

            // https: github.com/torvalds/linux/blob/master/include/uapi/asm-generic/errno.h
            // #define    ENOMSG        42    /* No message of desired type */
            $errorcodeMsg = 0;

            while (true) {
                if (msg_receive($queueKeyAbeilleToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                    $message->topic = $msg->message['topic'];
                    $message->payload = $msg->message['payload'];
                    self::message($message);
                    $msg_type = NULL;
                    $msg = NULL;
                }
                if (($errorcodeMsg != 42) and ($errorcodeMsg != 0)) {
                    log::add('Abeille', 'error', 'deamon(): msg_receive queueKeyAbeilleToAbeille error '.$errorcodeMsg);
                }

                /* New path parser to Abeille */
                if (msg_receive($queueKeyParserToAbeille2, 0, $msg_type, $max_msg_size, $msg_json, false, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                    self::msgFromParser(json_decode($msg_json, true));
                    $msg_type = NULL;
                    $msg = NULL;
                }
                if (($errorcodeMsg != 42) and ($errorcodeMsg != 0)) {
                    log::add('Abeille', 'debug', 'deamon(): msg_receive queueKeyParserToAbeille2 error '.$errorcodeMsg);
                }

                /* Legacy path parser to Abeille. To be progressively removed */
                if (msg_receive($queueKeyParserToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                    $message->topic = $msg->message['topic'];
                    $message->payload = $msg->message['payload'];
                    self::message($message);
                    $msg_type = NULL;
                    $msg = NULL;
                }
                if (($errorcodeMsg != 42) and ($errorcodeMsg != 0)) {
                    log::add('Abeille', 'debug', 'deamon(): msg_receive queueKeyParserToAbeille error '.$errorcodeMsg);
                }

                if (msg_receive($queueKeyCmdToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                    $message->topic = $msg->message['topic'];
                    $message->payload = $msg->message['payload'];
                    self::message($message);
                    $msg_type = NULL;
                    $msg = NULL;
                }
                if (($errorcodeMsg != 42) and ($errorcodeMsg != 0)) {
                    log::add('Abeille', 'debug', 'deamon(): msg_receive queueKeyCmdToAbeille error '.$errorcodeMsg);
                }

                if (msg_receive($queueKeyXmlToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                    $message->topic = $msg->message['topic'];
                    $message->payload = $msg->message['payload'];
                    self::message($message);
                    $msg_type = NULL;
                    $msg = NULL;
                }
                if (($errorcodeMsg != 42) and ($errorcodeMsg != 0)) {
                    log::add('Abeille', 'debug', 'deamon(): msg_receive queueKeyXmlToAbeille error '.$errorcodeMsg);
                }

                time_nanosleep(0, 10000000); // 1/100s
            }

        } catch (Exception $e) {
            log::add('Abeille', 'error', 'deamon(): Exception '.$e->getMessage());
        }
        log::add('Abeille', 'debug', 'deamon(): Main daemon stoppped');
    }

    // public static function checkParameters() {
    //     // return 1 si Ok, 0 si erreur
    //     $param = Abeille::getParameters();

    //     if ( !isset($param['zigateNb']) ) { return 0; }
    //     if ( $param['zigateNb'] < 1 ) { return 0; }
    //     if ( $param['zigateNb'] > 9 ) { return 0; }

    //     // Testons la validité de la configuration
    //     $atLeastOneZigateActiveWithOnePortDefined = 0;
    //     for ( $i=1; $i<=$param['zigateNb']; $i++ ) {
    //         if ($return['AbeilleActiver'.$i ]=='Y') {
    //             if ($return['AbeilleSerialPort'.$i]!='none') {
    //                 $atLeastOneZigateActiveWithOnePortDefined++;
    //             }
    //         }
    //     }
    //     if ( $atLeastOneZigateActiveWithOnePortDefined <= 0 ) {
    //         log::add('Abeille','debug','checkParameters: aucun serialPort n est pas défini/actif.');
    //         message::add('Abeille','Warning: Aucun port série n est pas défini/Actif dans la configuration.' );
    //         return 0;
    //     }

    //     // Vérifions l existence des ports
    //     for ( $i=1; $i<=$param['zigateNb']; $i++ ) {
    //         if ($return['AbeilleActiver'.$i ]=='Y') {
    //             if ($return['AbeilleSerialPort'.$i] != 'none') {
    //                 if (@!file_exists($return['AbeilleSerialPort'.$i])) {
    //                     log::add('Abeille','debug','checkParameters: Le port série n existe pas: '.$return['AbeilleSerialPort'.$i]);
    //                     message::add('Abeille','Warning: Le port série n existe pas: '.$return['AbeilleSerialPort'.$i],'' );
    //                     $return['parametersCheck']="nok";
    //                     $return['parametersCheck_message'] = __('Le port série '.$return['AbeilleSerialPort'.$i].' n existe pas (zigate déconnectée ?)', __FILE__);
    //                     return 0;
    //                 } else {
    //                     if (substr(decoct(fileperms($return['AbeilleSerialPort'.$i])), -4) != "0777") {
    //                         exec(system::getCmdSudo().'chmod 777 '.$return['AbeilleSerialPort'.$i].' > /dev/null 2>&1');
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     return 1;
    // }

    public static function postSave()
    {
        /* Tcharp38: Strange. postSave() called when starting daemons.
           No sense to re-start main daemon from their then */
        // log::add('Abeille', 'debug', 'postSave()');
        // $cron = cron::byClassAndFunction('Abeille', 'deamon');
        // if (is_object($cron) && !$cron->running()) {
        //     $cron->run();
        // }
        // log::add('Abeille', 'debug', 'deamon_postSave: OUT');
    }

    public static function fetchShortFromIEEE($IEEE, $checkShort)
    {
        // Return:
        // 0 : Short Address is aligned with the one received
        // Short : Short Address is NOT aligned with the one received
        // -1 : Error Nothing found

        // $lookForIEEE = "000B57fffe490C2a";
        // $checkShort = "2006";
        // log::add('Abeille', 'debug', 'KIWI: start function fetchShortFromIEEE');
        $abeilles = Abeille::byType('Abeille');

        foreach ($abeilles as $abeille) {

            if (strlen($abeille->getConfiguration('IEEE', 'none')) == 16) {
                $IEEE_abeille = $abeille->getConfiguration('IEEE', 'none');
            } else {
                $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr');
                if (is_object($cmdIEEE)) {
                    $IEEE_abeille = $cmdIEEE->execCmd();
                    if (strlen($IEEE_abeille) == 16) {
                        $abeille->setConfiguration('IEEE', $IEEE_abeille); // si j ai l IEEE dans la cmd et pas dans le conf, je transfer, retro compatibility
                        $abeille->save();
                        $abeille->refresh();
                    }
                }
            }

            if ($IEEE_abeille == $IEEE) {

                $cmdShort = $abeille->getCmd('Info', 'Short-Addr');
                if ($cmdShort) {
                    if ($cmdShort->execCmd() == $checkShort) {
                        // echo "Success ";
                        // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return 0');
                        return 0;
                    } else {
                        // echo "Pas success du tout ";
                        // La cmd short n est pas forcement à jour alors on va essayer avec le nodeId.
                        // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return Short: '.$cmdShort->execCmd() );
                        // return $cmdShort->execCmd();
                        // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return Short: '.substr($abeille->getlogicalId(),-4) );
                        return substr($abeille->getlogicalId(), -4);
                    }

                    return $return;
                }
            }
        }

        // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return -1');
        return -1;
    }

    /* Returns inclusion status: 1=include mode, 0=normal, -1=ERROR */
    public static function checkInclusionStatus($dest)
    {
        // Return: Inclusion status or -1 if error
        $ruche = Abeille::byLogicalId($dest.'/0000', 'Abeille');

        if ($ruche) {
            // echo "Join status collection\n";
            $cmdJoinStatus = $ruche->getCmd('Info', 'permitJoin-Status');
            if ($cmdJoinStatus) {
                return $cmdJoinStatus->execCmd();
            }
        }

        return -1;
    }

    public static function CmdAffichage($affichageType, $Visibility = "na")
    {
        // $affichageType could be:
        //  affichageNetwork
        //  affichageTime
        //  affichageCmdAdd
        // $Visibilty command could be
        // Y
        // N
        // toggle
        // na

        if ($Visibility == "na") {
            return;
        }

        $parameters_info = AbeilleTools::getParameters();

        $convert = array(
            "affichageNetwork" => "Network",
            "affichageTime" => "Time",
            "affichageCmdAdd" => "additionalCommand"
        );

        log::add('Abeille', 'debug', 'Entering CmdAffichage with affichageType: '.$affichageType.' - Visibility: '.$Visibility);
        echo 'Entering CmdAffichage with affichageType: '.$affichageType.' - Visibility: '.$Visibility;

        switch ($Visibility) {
            case 'Y':
                break;
            case 'N':
                break;
            case 'toggle':
                if ($parameters_info[$affichageType] == 'Y') {
                    $Visibility = 'N';
                } else {
                    $Visibility = 'Y';
                }
                break;
        }
        config::save($affichageType, $Visibility, 'Abeille');

        $abeilles = self::byType('Abeille');
        foreach ($abeilles as $key => $abeille) {
            $cmds = $abeille->getCmd();
            foreach ($cmds as $keyCmd => $cmd) {
                if ($cmd->getConfiguration("visibilityCategory") == $convert[$affichageType]) {
                    switch ($Visibility) {
                        case 'Y':
                            $cmd->setIsVisible(1);
                            break;
                        case 'N':
                            $cmd->setIsVisible(0);
                            break;
                    }
                }
                $cmd->save();
            }
            $abeille->save();
            $abeille->refresh();
        }

        log::add('Abeille', 'debug', 'Leaving CmdAffichage');
        return;
    }

    public static function interrogateUnknowNE( $dest, $addr ) {
        if ( config::byKey( 'blocageRecuperationEquipement', 'Abeille', 'Oui', 1 ) == 'Oui' ) {
            log::add('Abeille', 'debug', "L equipement ".$dest."/".$addr." n existe pas dans Jeedom, je ne cherche pas a le recupérer, param: blocage recuperation equipment.");
            return;
        }

        if ($addr == "0000") return;

        log::add('Abeille', 'debug', "L equipement ".$dest."/".$addr." n existe pas dans Jeedom, j'essaye d interroger l equipement pour le créer.");

        // EP 01
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceManufacturer", "01");
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/Annonce", "Default");
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceProfalux", "Default");

        // EP 0B
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceManufacturer", "0B");
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/Annonce", "Hue");

        // EP 03
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceManufacturer", "03");
        self::publishMosquitto(queueKeyAbeilleToCmd, priorityNeWokeUp, "Cmd".$dest."/".$addr."/Annonce", "OSRAM");

        return;
    }

    public static function message($message)
    {
        // KiwiHC16: Please leave this line log::add commented otherwise too many messages in log Abeille
        // and keep the 3 lines below which print all messages except Time-Time, Time-TimeStamp and Link-Quality that we get for every message.
        // Divide by 3 the log quantity and ease the log reading
        // log::add('Abeille', 'debug', "message(topic='".$message->topic."', payload='".$message->payload."')");

        $topicArray = explode("/", $message->topic);
        if (sizeof($topicArray) != 3) {
            log::add('Abeille', 'debug', "ERROR: Invalid message: topic=".$message->topic);
            return;
        }

        $parameters_info = AbeilleTools::getParameters();

        // if (!preg_match("(Time|Link-Quality)", $message->topic)) {
        //    log::add('Abeille', 'debug', "fct message Topic: ->".$message->topic."<- Value ->".$message->payload."<-");
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // demande de creation de ruche au cas ou elle n'est pas deja crée....
        // La ruche est aussi un objet Abeille
        if ($message->topic == "CmdRuche/0000/CreateRuche") {
            // log::add('Abeille', 'debug', "Topic: ->".$message->topic."<- Value ->".$message->payload."<-");
            self::createRuche($message);
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // On ne prend en compte que les message Abeille|Ruche|CmdCreate/#/#
        // CmdCreate -> pour la creation des objets depuis la ruche par exemple pour tester les modeles
        if (!preg_match("(^Abeille|^Ruche|^CmdCreate|^ttyUSB|^zigate|^monitZigate)", $message->topic)) {
            // log::add('Abeille', 'debug', 'message: this is not a '.$Filter.' message: topic: '.$message->topic.' message: '.$message->payload);
            return;
        }

        /*----------------------------------------------------------------------------------------------------------------------------------------------*/
        // Analyse du message recu
        // [CmdAbeille:Abeille] / Address / Cluster-Parameter
        // [CmdAbeille:Abeille] / $addr / $cmdId => $value
        // $nodeId = [CmdAbeille:Abeille] / $addr

        list($Filter, $addr, $cmdId) = explode("/", $message->topic);
        // log::add('Abeille', 'debug', "message(): Filter=".$Filter.", addr=".$addr.", cmdId=".$cmdId);
        if (preg_match("(^CmdCreate)", $message->topic)) {
            $Filter = str_replace("CmdCreate", "", $Filter);
        }
        $net = $Filter; // Network (ex: 'Abeille1')
        $dest = $Filter;

        // log all messages except the one related to Time, which overload the log
        if (!in_array($cmdId, array("Time-Time", "Time-TimeStamp", "Link-Quality"))) {
            log::add('Abeille', 'debug', "message(topic='".$message->topic."', payload='".$message->payload."')");
        }

        $nodeid = $net.'/'.$addr;
        $value = $message->payload;
        $type = 'topic';         // type = topic car pas json

        // Le capteur de temperature rond V1 xiaomi envoie spontanement son nom: ->lumi.sensor_ht<- mais envoie ->lumi.sens<- sur un getName
        // Tcharp38: To be removed. This cleanup is now done directly in parser on modelIdentifier receive
        if ($cmdId == "0000-01-0005") {
            if ($value == "lumi.sens") {
                $value = "lumi.sensor_ht";
                message::add("Abeille", "lumi.sensor_ht case tracking: ".json_encode($message), '');
            }
            if ($value == "lumi.sensor_swit") $value = "lumi.sensor_switch.aq3";
            if ($value == "TRADFRI Signal Repeater") $value = "TRADFRI signal repeater";
        }

        // Tcharp38: No longer required here. Treated by msgFromParser()
        //           'enable is part of 'eqAnnounce' while 'disable' is part of 'leaveIndication' msg.
        // /* Treat "enable" (device announce) or "disable" (leave indication) cmds. */
        // if (($cmdId == "enable") || ($cmdId == "disable")) {
        //     log::add('Abeille', 'debug', 'message(): '.$cmdId.': net='.$Filter.', IEEE='.$value);

        //     /* Look for corresponding equipment (identified via its IEEE addr) */
        //     $allBees = self::byType('Abeille');
        //     $missingIEEE = array(); // List of eq with missing IEEE
        //     foreach ($allBees as $key=>$bee) {
        //         $beeLogicId = $bee->getLogicalId(); // Ex: 'Abeille1/xxxx'
        //         list($net, $oldAddr) = explode( "/", $beeLogicId);
        //         if ($net != $Filter) // TODO: $Filter = 'AbeilleX' ??
        //             continue; // Not on expected network

        //         $ieee = $bee->getConfiguration('IEEE', 'none');
        //         if ($ieee == 'none') {
        //             $missingIEEE[] = $bee;
        //             continue; // No registered IEEE
        //         }
        //         if ($ieee != $value)
        //             continue; // Not the right equipment

        //         $matchingBee = $bee;
        //         break; // No need to go thru other equipments
        //     }

        //     /* If eq not found, might be due to missing IEEE. Let's check */
        //     if (!isset($matchingBee) && (sizeof($missingIEEE) != 0)) {
        //         log::add('Abeille', 'debug', 'message(): '.$cmdId.': Vérification des adresses IEEE manquantes');
        //         foreach ($missingIEEE as $bee) {
        //             $cmd = $bee->getCmd('info', 'IEEE-Addr');
        //             if ($cmd->execCmd() != $value)
        //                 continue; // Still not the correct eq

        //             $matchingBee = $bee;
        //             break; // No need to go thru other equipments
        //         }
        //     }

        //     if (isset($matchingBee)) {
        //         if ($cmdId == "enable") {
        //             $bee->setIsEnable(1);
        //             /* Updating logical ID since short address changed with device announce */
        //             $oldLogicId = $bee->getLogicalId();
        //             $nodeid = $Filter.'/'.$addr;
        //             $bee->setLogicalId($nodeid);
        //             log::add('Abeille', 'debug', 'message(): disable: Mise-à-jour '.$oldLogicId.' => '.$nodeid);
        //             // message::add("Abeille", "'".$bee->getHumanName()."' a rejoint le réseau.", '');
        //         } else {
        //             $bee->setIsEnable(0);
        //             /* Display message only if NOT in include mode */
        //             if (self::checkInclusionStatus($dest) != 1)
        //                 message::add("Abeille", "'".$bee->getHumanName()."' a quitté le réseau => désactivé.", '');
        //         }
        //         $bee->save();
        //         $bee->refresh();
        //     } else
        //         log::add('Abeille', 'debug', 'message(): '.$cmdId.': Eq '.$Filter.'-'.$value.' pas trouvé dans Jeedom.');

        //     return;
        // }

        /* Request to create virtual remote control */
        if ($cmdId == "createRemote") {
            log::add('Abeille', 'debug', 'message(): createRemote');

            /* Let's compute RC number */
            $eqLogics = Abeille::byType('Abeille');
            $max = 1;
            foreach ($eqLogics as $key => $eqLogic) {
                list($net2, $addr2) = explode("/", $eqLogic->getLogicalId());
                if ($net2 != $net)
                    continue; // Wrong network
                $jsonName2 = $eqLogic->getConfiguration('modeleJson');
                if ($jsonName2 != "remotecontrol")
                    continue; // Not a remote
                if ($addr2 == '')
                    continue; // No addr for remote on '210607-STABLE-1' leading to 1 remote only per zigate.
                $addr2 = substr($addr2, 2); // Removing 'rc'
                if (hexdec($addr2) >= $max)
                    $max = hexdec($addr2) + 1;
            }

            /* Remote control short addr = 'rcXX' */
            $rcAddr = sprintf("rc%02X", $max);
            Abeille::createDevice($dest, $rcAddr, '', '01', 'remotecontrol');

            return;
        }

        /* Request to update device from JSON. Useful to avoid reinclusion */
        if ($cmdId == "updateFromJson") {
            log::add('Abeille', 'debug', 'message(): updateFromJson, '.$net.'/'.$addr);

            $eqLogic = Abeille::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', '  ERROR: Unknown device');
                return;
            }

            $jsonName = $eqLogic->getConfiguration('modeleJson');
            $ieee = $eqLogic->getConfiguration('IEEE');
            $mainEP = $eqLogic->getConfiguration('mainEP');
            Abeille::createDevice($dest, $addr, $ieee, $mainEP, $jsonName, "Mise-à-jour de '".$eqLogic->getName()."' à partir de son fichier JSON");

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Cherche l objet par sa ref short Address et la commande
        $elogic = self::byLogicalId($nodeid, 'Abeille');
        if (is_object($elogic)) {
            $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmdId);
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        /* Tcharp38: Case hereafter is no longer used.
           Device creation is now triggered by Parser 'eqAnnounce' msg when device is identified.
           Treated by 'msgFromParser()' */
        // Si l objet n existe pas et je recoie son nom => je créé l objet.
        // if (!is_object($elogic)
        //     && (preg_match("/^0000-[0-9A-Fa-f]*-*0005/", $cmdId)
        //         || preg_match("/^0000-[0-9A-Fa-f]*-*0010/", $cmdId)
        //         || preg_match("/^SimpleDesc-[0-9A-Fa-f]*-*DeviceDescription/", $cmdId)
        //     )
        // ) {
        //     $trimmedValue = $value;
        //     log::add('Abeille', 'debug', 'Recherche objet: '.$value.' dans les objets connus');

        //     /* Remove leading "lumi." from name as all xiaomi devices who start with this prefix. */
        //     if (!strncasecmp($trimmedValue, "lumi.", 5))
        //         $trimmedValue = substr($trimmedValue, 5); // Remove leading "lumi." case insensitive

        //     //remove all space in names for easier filename handling
        //     $trimmedValue = str_replace(' ', '', $trimmedValue);

        //     // On enleve le / comme par exemple le nom des equipements Legrand
        //     $trimmedValue = str_replace('/', '', $trimmedValue);

        //     // On enleve le * Ampoules GU10 Philips #1778
        //     $trimmedValue = str_replace('*', '', $trimmedValue);

        //     // On enleve les 0x00 comme par exemple le nom des equipements Legrand
        //     $trimmedValue = str_replace("\0", '', $trimmedValue);

        //     log::add('Abeille', 'debug', 'value:'.$value.' / trimmed value: ->'.$trimmedValue.'<-');
        //     $AbeilleObjetDefinition = AbeilleTools::getDeviceConfig($trimmedValue);
        //     log::add('Abeille', 'debug', 'Template initial: '.json_encode($AbeilleObjetDefinition));

        //     // On recupere le EP
        //     // $EP = substr($cmdId,5,2);
        //     $EP = explode('-', $cmdId)[1];
        //     log::add('Abeille', 'debug', 'EP: '.$EP);
        //     $AbeilleObjetDefinitionJson = json_encode($AbeilleObjetDefinition);
        //     $AbeilleObjetDefinitionJson = str_replace('#EP#', $EP, $AbeilleObjetDefinitionJson);
        //     $AbeilleObjetDefinition = json_decode($AbeilleObjetDefinitionJson, true);
        //     log::add('Abeille', 'debug', 'Template mis a jour avec EP: '.json_encode($AbeilleObjetDefinition));

        //     if (array_key_exists($trimmedValue, $AbeilleObjetDefinition)) {
        //         $jsonName = $trimmedValue;
        //     }
        //     if (array_key_exists('defaultUnknown', $AbeilleObjetDefinition)) {
        //         $jsonName = 'defaultUnknown';
        //     }

        //     /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        //     // Creation de l objet Abeille
        //     // Exemple pour les objets créés par les commandes Ruche.
        //     if (strlen($addr) != 4) {
        //         $index = rand(1000, 9999);
        //         $addr = $addr."-".$index;
        //         $nodeid = $nodeid."-".$index;
        //     }

        //     $elogic = new Abeille();
        //     $elogic->setEqType_name('Abeille');
        //     $elogic->setName("nouveau-".$addr); // Temp name to have it non empty
        //     $elogic->save(); // Save to force Jeedom to assign an ID

        //     $name = $Filter."-".$elogic->getId();
        //     message::add("Abeille", "Nouvel équipement détecté: ".$name.". Création en cours. Rafraîchissez votre dashboard dans qq secondes.", '');
        //     $elogic->setName($name);
        //     $elogic->setLogicalId($nodeid);
        //     $elogic->setObject_id($parameters_info['AbeilleParentId']);
        //     $objetDefSpecific = $AbeilleObjetDefinition[$jsonName];
        //     $objetConfiguration = $objetDefSpecific["configuration"];
        //     log::add('Abeille', 'debug', 'Template configuration: '.json_encode($objetConfiguration));
        //     $elogic->setConfiguration('modeleJson', $trimmedValue);
        //     // $elogic->setConfiguration('topic', $nodeid); // not needed as the info is in logicalId.
        //     $elogic->setConfiguration('type', $type);
        //     $elogic->setConfiguration('uniqId', $objetConfiguration["uniqId"]);
        //     $elogic->setConfiguration('icone', $objetConfiguration["icone"]);
        //     $elogic->setConfiguration('mainEP', $objetConfiguration["mainEP"]);
        //     $lastCommTimeout = (array_key_exists("lastCommunicationTimeOut", $objetConfiguration) ? $objetConfiguration["lastCommunicationTimeOut"] : '-1');
        //     $elogic->setConfiguration('lastCommunicationTimeOut', $lastCommTimeout);
        //     $elogic->setConfiguration('type', $type);

        //     if (isset($objetConfiguration['battery_type'])) {
        //         $elogic->setConfiguration('battery_type', $objetConfiguration['battery_type']);
        //     }
        //     if (isset($objetConfiguration['paramType']))
        //         $elogic->setConfiguration('paramType', $objetConfiguration['paramType']);
        //     if (isset($objetConfiguration['Groupe'])) { // Tcharp38: What for ? Telecommande Innr - KiwiHC16: on doit pouvoir simplifier ce code. Mais comme c etait la premiere version j ai fait detaillé.
        //         $elogic->setConfiguration('Groupe', $objetConfiguration['Groupe']);
        //     }
        //     if (isset($objetConfiguration['Groupe'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('Groupe', $objetConfiguration['Groupe']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP1'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP1', $objetConfiguration['GroupeEP1']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP3'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP3', $objetConfiguration['GroupeEP3']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP4'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP4', $objetConfiguration['GroupeEP4']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP5'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP5', $objetConfiguration['GroupeEP5']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP6'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP6', $objetConfiguration['GroupeEP6']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP7'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP7', $objetConfiguration['GroupeEP7']);
        //     }
        //     if (isset($objetConfiguration['GroupeEP8'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('GroupeEP8', $objetConfiguration['GroupeEP8']);
        //     }
        //     if (isset($objetConfiguration['onTime'])) { // Tcharp38: What for ?
        //         $elogic->setConfiguration('onTime', $objetConfiguration['onTime']);
        //     }
        //     if (isset($objetConfiguration['Zigate'])) {
        //         $elogic->setConfiguration('Zigate', $objetConfiguration['Zigate']);
        //     }
        //     if (isset($objetConfiguration['protocol'])) {
        //         $elogic->setConfiguration('protocol', $objetConfiguration['protocol']);
        //     }
        //     if (isset($objetConfiguration['poll'])) {
        //         $elogic->setConfiguration('poll', $objetConfiguration['poll']);
        //     }
        // if (isset($objetDefSpecific["isVisible"]))
        //     $elogic->setIsVisible($objetDefSpecific["isVisible"]);
        // else
        //     $elogic->setIsVisible(1);

        //     // eqReal_id
        //     $elogic->setIsEnable("1");
        //     // status
        //     // timeout
        //     $elogic->setTimeout($objetDefSpecific["timeout"]);

        //     $elogic->setCategory(array_keys($objetDefSpecific["Categorie"])[0], $objetDefSpecific["Categorie"][array_keys($objetDefSpecific["Categorie"])[0]]);
        //     // display
        //     // order
        //     // comment

        //     //log::add('Abeille', 'info', 'Saving device '.$nodeid);
        //     //$elogic->save();
        //     $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        //     $elogic->save();

        //     // Creation des commandes pour l objet Abeille juste créé.
        //     if (isset($GLOBALS['debugKIWI']) && $GLOBALS['debugKIWI']) {
        //         echo "On va creer les commandes.\n";
        //         print_r($objetDefSpecific['Commandes']);
        //     }

        //     foreach ($objetDefSpecific['Commandes'] as $cmd => $cmdValueDefaut) {
        //         log::add('Abeille', 'info', "Eq ".$name.": Ajout de la commande '".$cmdValueDefaut["name"]."' => '".$cmd."'");
        //         // 'Creation de la commande: '.$nodeid.'/'.$cmd.' suivant model de l objet pour l objet: '.$name

        //         $cmdlogic = new AbeilleCmd();
        //         // id
        //         $cmdlogic->setEqLogic_id($elogic->getId());
        //         $cmdlogic->setEqType('Abeille');
        //         $cmdlogic->setLogicalId($cmd);
        //         if (isset($cmdValueDefaut["order"]))
        //             $cmdlogic->setOrder($cmdValueDefaut["order"]);
        //         $cmdlogic->setName($cmdValueDefaut["name"]);
        //         // value

        //         if ($cmdValueDefaut["Type"] == "info") {
        //             // $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd);
        //             $cmdlogic->setConfiguration('topic', $cmd);
        //         }
        //         if ($cmdValueDefaut["Type"] == "action") {
        //             // $cmdlogic->setConfiguration('retain', '0'); // not needed anymore, was used for mosquitto

        //             if (isset($cmdValueDefaut["value"])) {
        //                 // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
        //                 log::add('Abeille', 'debug', 'Define cmd info pour cmd action: '.$elogic->getHumanName()." - ".$cmdValueDefaut["value"]);

        //                 $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $elogic->getName(), $cmdValueDefaut["value"]);
        //                 $cmdlogic->setValue($cmdPointeur_Value->getId());
        //             }
        //         }

        //         // La boucle est pour info et pour action
        //         foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
        //             // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
        //             // $cmdlogic->setConfiguration($confKey, str_replace('#addr#', $addr, $confValue)); // Ce n'est plus necessaire car l adresse est maintenant dans le logicalId
        //             $cmdlogic->setConfiguration($confKey, $confValue);

        //             // Ne pas effacer, en cours de dev.
        //             // $cmdlogic->setConfiguration($confKey, str_replace('#addrIEEE#',     '#addrIEEE#',   $confValue));
        //             // $cmdlogic->setConfiguration($confKey, str_replace('#ZiGateIEEE#',   '#ZiGateIEEE#', $confValue));
        //         }
        //         // On conserve l info du template pour la visibility
        //         $cmdlogic->setConfiguration("visibiltyTemplate", $cmdValueDefaut["isVisible"]);

        //         // template
        //         if (isset($cmdValueDefaut["template"])) {
        //             $cmdlogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
        //             $cmdlogic->setTemplate('mobile', $cmdValueDefaut["template"]);
        //         }
        //         $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
        //         $cmdlogic->setType($cmdValueDefaut["Type"]);
        //         $cmdlogic->setSubType($cmdValueDefaut["subType"]);
        //         if (array_key_exists("generic_type", $cmdValueDefaut))
        //             $cmdlogic->setGeneric_type($cmdValueDefaut["generic_type"]);
        //         // unite
        //         if (isset($cmdValueDefaut["unite"])) {
        //             $cmdlogic->setUnite($cmdValueDefaut["unite"]);
        //         }

        //         if (isset($cmdValueDefaut["invertBinary"])) {
        //             $cmdlogic->setDisplay('invertBinary', $cmdValueDefaut["invertBinary"]);
        //         }
        //         // La boucle est pour info et pour action
        //         // isVisible
        //         $parameters_info = AbeilleTools::getParameters();
        //         $isVisible = $cmdValueDefaut["isVisible"];

        //         if (array_key_exists("display", $cmdValueDefaut))
        //             foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
        //                 // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
        //                 $cmdlogic->setDisplay($confKey, $confValue);
        //             }

        //         $cmdlogic->setIsVisible($isVisible);

        //         // html
        //         // alert

        //         $cmdlogic->save();

        //         // $elogic->checkAndUpdateCmd( $cmdlogic, $cmdValueDefaut["value"] );

        //         if ($cmdlogic->getName() == "Short-Addr") {
        //             $elogic->checkAndUpdateCmd($cmdlogic, $addr);
        //         }
        //     }

        //     // On defini le nom de l objet
        //     $elogic->checkAndUpdateCmd($cmdId, $value);

        //     return;
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        /* If unknown eq and IEEE received, looking for eq with same IEEE to update logicalName & topic */
        // e.g. Short address change (Si l adresse a changé, on ne peut pas trouver l objet par son nodeId)
        if (!is_object($elogic) && ($cmdId == "IEEE-Addr")) {
            log::add('Abeille', 'debug', 'message(), !objet & IEEE: Recherche de l\'equipement correspondant');
            // 0 : Short Address is aligned with the one received
            // Short : Short Address is NOT aligned with the one received
            // -1 : Error Nothing found
            $ShortFound = Abeille::fetchShortFromIEEE($value, $addr);
            log::add('Abeille', 'debug', 'message(), !objet & IEEE: Trouvé='.$ShortFound);
            if ((strlen($ShortFound) == 4) && ($addr != "0000")) {

                $elogic = self::byLogicalId($dest."/".$ShortFound, 'Abeille');
                if (!is_object($elogic)) {
                    log::add('Abeille', 'debug', 'message(), !objet & IEEE: L\'équipement ne semble pas sur la bonne zigate. Abeille ne fait rien automatiquement. L\'utilisateur doit résoudre la situation.');
                    return;
                }

                // log::add('Abeille', 'debug', "message(), !objet & IEEE: Adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - ".$elogic->getName().", on fait la mise a jour automatique");
                log::add('Abeille', 'debug', "message(), !objet & IEEE: $value correspond à '".$elogic->getHumanName()."'. Mise-à-jour de l'adresse courte $ShortFound vers $addr.");
                // Comme c est automatique des que le retour d experience sera suffisant, on n alerte pas l utilisateur. Il n a pas besoin de savoir
                // message::add("Abeille", "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - ".$elogic->getName().", on fait la mise a jour automatique", '');
                message::add("Abeille", "Nouvelle adresse '".$addr."' pour '".$elogic->getHumanName()."'. Mise à jour automatique.");

                // Si on trouve l adresse dans le nom, on remplace par la nouvelle adresse
                // log::add('Abeille', 'debug', "!objet&IEEE --> IEEE-Addr; Ancien nom: ".$elogic->getName().", nouveau nom: ".str_replace($ShortFound, $addr, $elogic->getName()));
                // $elogic->setName(str_replace($ShortFound, $addr, $elogic->getName()));

                $elogic->setLogicalId($dest."/".$addr);
                $elogic->setConfiguration('topic', $dest."/".$addr);
                $elogic->save();

                // Il faut aussi mettre a jour la commande short address
                Abeille::publishMosquitto(queueKeyAbeilleToAbeille, priorityInterrogation, $dest."/".$addr."/Short-Addr", $addr);
            } else {
                log::add('Abeille', 'debug', 'message(), !objet & IEEE: Je n ai pas trouvé d Abeille qui corresponde.');
                self::interrogateUnknowNE( $dest, $addr );
            }
            // log::add('Abeille', 'debug', '!objet&IEEE --> fin du traitement');
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande => je drop la cmd but I try to get the device into Abeille
        // e.g. un Equipement envoie des infos, mais l objet n existe pas dans Jeedom
        if (!is_object($elogic)) {

            // Je ne fais les demandes que si les commandes ne sont pas Time-Time, Time-Stamp et Link-Quality
            if (!preg_match("(Time|Link-Quality)", $message->topic)) {

                if (!Abeille::checkInclusionStatus($dest)) {
                    log::add('Abeille', 'info', 'Des informations remontent pour un equipement inconnu d Abeille avec pour adresse '.$addr.' et pour la commande '.$cmdId );
                }

                self::interrogateUnknowNE( $dest, $addr );
            }

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet exist et on recoie une IEEE
        // e.g. Un NE renvoie son annonce
        if (is_object($elogic) && ($cmdId == "IEEE-Addr")) {

            // Je rejete les valeur null (arrive avec les equipement xiaomi qui envoie leur nom spontanement alors que l IEEE n est pas recue.
            if (strlen($value) < 2) {
                log::add('Abeille', 'debug', 'IEEE-Addr; =>'.$value.'<= ; IEEE non valable pour un equipement, valeur rejetée: '.$addr.": IEEE =>".$value."<=");
                return;
            }

            // Je ne sais pas pourquoi des fois on recoit des IEEE null
            if ($value == "0000000000000000") {
                log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';IEEE recue est null, je ne fais rien.');
                return;
            }

            // ffffffffffffffff remonte avec les mesures LQI si nouveau equipements.
            if ($value == "FFFFFFFFFFFFFFFF") {
                log::add('Abeille', 'debug', 'IEEE-Addr; =>'.$value.'<= ; IEEE non valable pour un equipement, valeur rejetée: '.$addr.": IEEE =>".$value."<=");
                return;
            }

            // Update IEEE cmd
            if (!is_object($cmdlogic)) {
                log::add('Abeille', 'debug', 'IEEE-Addr commande n existe pas');
                return;
            }

            // $IEEE = $cmdlogic->execCmd();
            $IEEE = $elogic->getConfiguration('IEEE','None');
            if ( ($IEEE!=$value) && (strlen($IEEE)==16) ) {
                log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';Alerte changement de l adresse IEEE pour un equipement !!! '.$addr.": ".$IEEE." =>".$value."<=");
                message::add("Abeille", "Alerte changement de l adresse IEEE pour un equipement !!! ( $addr : $IEEE =>$value<= )", '');
            }

            $elogic->checkAndUpdateCmd($cmdlogic, $value);
            $elogic->setConfiguration('IEEE', $value);
            $elogic->save();
            $elogic->refresh();

            log::add('Abeille', 'debug', '  IEEE-Addr cmd and eq updated: '.$elogic->getName().' - '.$elogic->getConfiguration('IEEE', 'Unknown') );

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Tcharp38: No longer required. MACCap is collected during device announce and passed thru "eqAnnounce" msg (treated by msgFromParser())
        // // Si l objet exist et on recoie une MACCapa
        // // e.g. Un NE renvoie son annonce
        // if (is_object($elogic) && ($cmdId == "MACCapa-MACCapa")) {

        //     log::add('Abeille', 'debug', 'MACCapa-MACCapa: '.$value.' saving into eqlogic');

        //     $mc = hexdec($value);
        //     $rxOnWhenIdle = ($mc >> 3) & 0b1;
        //     $powerSource = ($mc >> 2) & 0b1;

        //     $elogic->setConfiguration('MACCapa', $value);
        //     if ($powerSource) // 1=mains-powererd
        //         $elogic->setConfiguration('AC_Power', 1);
        //     else
        //         $elogic->setConfiguration('AC_Power', 0);
        //     if ($rxOnWhenIdle) // 1=Receiver enabled when idle
        //         $elogic->setConfiguration('RxOnWhenIdle', 1);
        //     else
        //         $elogic->setConfiguration('RxOnWhenIdle', 0);
        //     $elogic->save();
        //     $elogic->refresh();

        //     return;
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si equipement et cmd existe alors on met la valeur a jour
        if (is_object($elogic) && is_object($cmdlogic)) {
            /* Traitement particulier pour les batteries */
            if ($cmdId == "Batterie-Volt") {
                /* Volt en milli V. Max a 3,1V Min a 2,7V, stockage en % batterie */
                // Tcharp38: To be removed. Should not be systematic. Device may report percent too */
                $elogic->setStatus('battery', self::volt2pourcent($value));
                $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }
            else if (($cmdId == "Battery-Percent") || ($cmdId == "Batterie-Pourcent")) {
                log::add('Abeille', 'debug', "  Battery % reporting: ".$cmdId.", val=".$value);
                $elogic->setStatus('battery', $value);
                $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }
            // Tcharp38: Correct % value directly reported by parser according to zigbee spec (raw value / 2)
            // if ($cmdId == "0001-01-0021") {
            //     /* en % batterie example Ikea Remote */
            //     // 10.10.2.1   BatteryPercentageRemaining Attribute Specifies the remaining battery life as a half integer percentage of the full battery capacity (e.g.
            //     // 34.5%, 45%, 68.5%, 90%) with a range between zero and 100%, with 0x00 = 0%, 0x64 = 50%, and 0xC8 = 100%. This is particularly suited for devices with
            //     // rechargeable batteries. The value 0xff indicates an invalid or unknown reading. This attribute SHALL be configurable for attribute reporting.
            //     // C8 is 200, so value/200*100
            //     $elogic->setStatus('battery', $value / 2);
            //     $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            // }
            else if (preg_match("/^0001-[0-9A-F]*-0021/", $cmdId)) {
                log::add('Abeille', 'debug', "  Battery % reporting: ".$cmdId.", val=".$value);
                $elogic->setStatus('battery', $value);
                $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }

            /* Traitement particulier pour la remontée de nom qui est utilisé pour les ping des routeurs */
            // if (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) {
            // if (preg_match("/^0000-[0-9A-F]*-*0005/", $cmdId) || preg_match("/^0000-[0-9A-F]*-*0010/", $cmdId)) {
            else if ($cmdId == "Time-TimeStamp") {
                log::add('Abeille', 'debug', "  Updating 'online' status for '".$dest."/".$addr."'");
                $cmdlogicOnline = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), 'online');
                $elogic->checkAndUpdateCmd($cmdlogicOnline, 1);
            }

            // Traitement particulier pour rejeter certaines valeurs
            // exemple: le Xiaomi Wall Switch 2 Bouton envoie un On et un Off dans le même message donc Abeille recoit un ON/OFF consecutif et
            // ne sais pas vraiment le gérer donc ici on rejete le Off et on met un retour d'etat dans la commande Jeedom
            if ($cmdlogic->getConfiguration('AbeilleRejectValue', -9999.99) == $value) {
                log::add('Abeille', 'debug', 'Rejet de la valeur: '.$cmdlogic->getConfiguration('AbeilleRejectValue', -9999.99).' - '.$value);

                return;
            }

            $elogic->checkAndUpdateCmd($cmdlogic, $value);

            // Polling to trigger based on this info cmd change: e.g. state moved to On, getPower value.
            $cmds = AbeilleCmd::searchConfigurationEqLogic($elogic->getId(), 'PollingOnCmdChange', 'action');
            foreach ($cmds as $key => $cmd) {
                if ($cmd->getConfiguration('PollingOnCmdChange') == $cmdId) {
                    log::add('Abeille', 'debug', 'Cmd action execution: '.$cmd->getName());
                    // $cmd->execute(); si j'envoie la demande immediatement le device n a pas le temps de refaire ses mesures et repond avec les valeurs d avant levenement
                    // Je vais attendre qq secondes aveant de faire la demande
                    // Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000" );
                    Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$cmd->getLogicalId()."&time=".(time() + $cmd->getConfiguration('PollingOnCmdChangeDelay')), $cmd->getConfiguration('request'));
                }
            }

            /* 'trig' defined in command allows to trig another command on new value receipt.
               Syntax: 'trig': 'trig-cmd-logicalId' */
            $trigLogicId = $cmdlogic->getConfiguration('ab::trig');
            if ($trigLogicId) {
                $newvalue = $cmdlogic->execCmd(); // Value might be updated with a "calculValueOffset" rule
                $trigCmd = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), $trigLogicId);
                if ($trigCmd)
                    $elogic->checkAndUpdateCmd($trigCmd, $newvalue);

                if (preg_match("/^0001-[0-9A-F]*-0021/", $trigLogicId)) {
                    log::add('Abeille', 'debug', "  Battery % reporting: ".$trigLogicId.", val=".$newvalue);
                    $elogic->setStatus('battery', $newvalue);
                    $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                }
            }
            return;
        }

        if (is_object($elogic) && !is_object($cmdlogic)) {
            log::add('Abeille', 'debug', "  L'objet '".$nodeid."' existe mais pas la cmde '".$cmdId."' => message ignoré");
            return;
        }

        log::add('Abeille', 'debug', "  WARNING: Unexpected state at end of message().");
        return;
    } // End message()

    /* Deal with messages coming from parser.
       Note: this is the new way to handle messages from parser, replacing progressively 'message()' */
    public static function msgFromParser($msg) {
        $net = $msg['net'];
        if (isset($msg['addr']))
            $addr = $msg['addr'];
        if (isset($msg['ep']))
            $ep = $msg['ep'];

        /* Parser has received a "device announce" and has identified (or not) the device. */
        if ($msg['type'] == "eqAnnounce") {
            /* Msg reminder
                $msg = array(
                    'src' => 'parser',
                    'type' => 'eqAnnounce',
                    'net' => $net,
                    'addr' => $addr,
                    'ieee' => $eq['ieee'],
                    'ep' => $eq['epFirst'],
                    'jsonId' => $eq['jsonId'],
                    'capa' => $eq['capa'],
                    'time' => time()
                ); */

            $logicalId = $net.'/'.$addr;
            $jsonName = $msg['jsonId'];
            log::add('Abeille', 'debug', "msgFromParser(): Eq announce received for ".$net.'/'.$addr.", zbId='".$jsonName."'");

            $ieee = $msg['ieee'];

            $eqLogic = self::byLogicalId($logicalId, 'Abeille');
            if (!is_object($eqLogic)) {
                /* Unknown device with net/addr logicalId.
                   Probably due to addr change on 'dev announce'. Looking for EQ based on its IEEE address */
                $all = self::byType('Abeille');
                foreach ($all as $key => $eqLogic) {
                    $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/xxxx'
                    list($net2, $addr2) = explode( "/", $eqLogicId);
                    if ($net2 != $net)
                        continue; // Not on expected network
                    if ((substr($addr2, 0, 2) == "rc") || ($addr2 == ""))
                        continue; // Virtual remote control

                    $ieee2 = $eqLogic->getConfiguration('IEEE', '');
                    if ($ieee2 == '') {
                        log::add('Abeille', 'debug', "msgFromParser(): WARNING. No IEEE addr in '".$eqLogicId."' config.");
                        continue; // No registered IEEE
                    }
                    if ($ieee2 != $ieee)
                        continue; // Not the right equipment

                    $eqLogic->setLogicalId($logicalId); // Updating logical ID
                    $eqLogic->save();
                    log::add('Abeille', 'debug', "msgFromParser(): Eq found with old addr ".$addr2.". Update done.");
                    break; // No need to go thru other equipments
                }
            }

            /* Create or update device from JSON.
               Note: ep = first End Point */
            Abeille::createDevice($net, $addr, $ieee, $ep, $jsonName);

            if (!is_object($eqLogic))
                $eqLogic = self::byLogicalId($logicalId, 'Abeille');

            /* MAC capa */
            $mc = hexdec($msg['capa']);
            $rxOnWhenIdle = ($mc >> 3) & 0b1;
            $powerSource = ($mc >> 2) & 0b1;
            $eqLogic->setConfiguration('MACCapa', $msg['capa']);
            if ($powerSource) // 1=mains-powererd
                $eqLogic->setConfiguration('AC_Power', 1);
            else
                $eqLogic->setConfiguration('AC_Power', 0);
            if ($rxOnWhenIdle) // 1=Receiver enabled when idle
                $eqLogic->setConfiguration('RxOnWhenIdle', 1);
            else
                $eqLogic->setConfiguration('RxOnWhenIdle', 0);
            $eqLogic->save();

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            $cmdlogic = AbeilleCmd::byEqLogicIdCmdName($eqLogic->getId(), "Short-Addr");
            if (!is_object($cmdlogic))
                $eqLogic->checkAndUpdateCmd($cmdlogic, $addr);
            $cmdlogic = AbeilleCmd::byEqLogicIdCmdName($eqLogic->getId(), "IEEE-Addr");
            if (!is_object($cmdlogic))
                $eqLogic->checkAndUpdateCmd($cmdlogic, $ieee);

            return;
        } // End 'eqAnnounce'

        /* Parser has received a "leave indication" */
        if ($msg['type'] == "leaveIndication") {
            /* Msg reminder
                $msg = array(
                    'src' => 'parser',
                    'type' => 'leaveIndication',
                    'net' => $dest,
                    'ieee' => $IEEE,
                    'rejoin' => $RejoinStatus,
                    'time' => time()
                );
            */

            $ieee = $msg['ieee'];
            log::add('Abeille', 'debug', "msgFromParser(): Leave indication for IEEE ".$ieee.", rejoin=".$msg['rejoin']);

            /* Look for corresponding equipment (identified via its IEEE addr) */
            $all = self::byType('Abeille');
            $eqLogic = null;
            foreach ($all as $key => $eqLogic2) {
                $eqLogicId2 = $eqLogic2->getLogicalId(); // Ex: 'Abeille1/xxxx'
                list($net2, $addr2) = explode( "/", $eqLogicId2);
                if ($net2 != $net)
                    continue; // Not on expected network
                if ((substr($addr2, 0, 2) == "rc") || ($addr2 == ""))
                    continue; // Virtual remote control

                $ieee2 = $eqLogic2->getConfiguration('IEEE', '');
                if ($ieee2 == '') {
                    log::add('Abeille', 'debug', "msgFromParser(): WARNING. No IEEE addr in '".$eqLogicId2."' config.");
                    $cmd = $eqLogic2->getCmd('info', 'IEEE-Addr');
                    if (is_object($cmd)) {
                        $ieee2 = $cmd->execCmd();
                        if ($ieee2 != $ieee)
                            continue; // Still no registered IEEE
                        // Tcharp38: It should not be possible that IEEE be in cmd but not in configuration. No sense
                        //           since IEEE always provided with device announce.
                        $eqLogic2->setConfiguration('IEEE', $ieee2);
                        $eqLogic2->save();
                        log::add('Abeille', 'debug', "msgFromParser(): Missing IEEE addr corrected");
                    }
                }
                if ($ieee2 != $ieee)
                    continue; // Not the right equipment

                $eqLogic = $eqLogic2;
                break; // No need to go thru other equipments
            }

            if (isset($eqLogic)) {
                $eqLogic->setIsEnable(0);
                /* Display message only if NOT in include mode */
                if (self::checkInclusionStatus($net) != 1)
                    message::add("Abeille", "'".$eqLogic->getHumanName()."' a quitté le réseau => désactivé.", '');
                $eqLogic->save();
                $eqLogic->refresh();

                Abeille::updateTimestamp($eqLogic, $msg['time']);
            } else
                log::add('Abeille', 'debug', 'msgFromParser(): WARNING: Device with IEEE '.$ieee.' NOT found in Jeedom');

            return;
        } // End 'leaveIndication'

        /* Attribut report (8100 & 8102 responses) */
        if ($msg['type'] == "attributeReport") {
            /* Reminder
                $msg = array(
                    'src' => 'parser',
                    'type' => 'attributeReport',
                    'net' => $net,
                    'addr' => $addr,
                    'ep' => 'xx', // End point hex string
                    'name' => 'xxx', // Attribut name = cmd logical ID
                    'value' => false, // False = unsupported
                    'time' => time(),
                    'lqi' => $lqi
                ); */

            log::add('Abeille', 'debug', "msgFromParser(): Attribut report from '".$net."/".$addr."/".$ep."': attr='".$msg['name']."', val='".$msg['value']."'");
            $eqLogic = self::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  Unknown device");
                return; // Unknown device
            }

            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $msg['name']);
            if (!is_object($cmdLogic)) {
                log::add('Abeille', 'debug', "  Unknown command");
                return; // Unknown command
            }
            $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['value']);

            Abeille::updateTimestamp($eqLogic, $msg['time']);
            return;
        } // End 'attributeReport'

        /* Zigate version (8010 response) */
        if ($msg['type'] == "zigateVersion") {
            /* Reminder
            $msg = array(
                'src' => 'parser',
                'type' => 'zigateVersion',
                'net' => $dest,
                'major' => $major,
                'minor' => $minor,
                'time' => time()
            ); */

            log::add('Abeille', 'debug', "msgFromParser(): ".$net.", Zigate version ".$msg['major']."-".$msg['minor']);
            $eqLogic = self::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'SW-Application');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['major']);
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'SW-SDK');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['minor']);
            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        }

        /* Network state (8009 response) */
        if ($msg['type'] == "networkState") {
            /* Reminder
            $msg = array(
                'src' => 'parser',
                'type' => 'networkState',
                'net' => $dest,
                'addr' => $ShortAddress,
                'ieee' => $ExtendedAddress,
                'panId' => $PAN_ID,
                'extPanId' => $Ext_PAN_ID,
                'chan' => $Channel,
                'time' => time()
            ); */

            log::add('Abeille', 'debug', "msgFromParser(): ".$net.", network state, ieee=".$msg['ieee'].", chan=".$msg['chan']);
            $eqLogic = self::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $ieee = $eqLogic->getConfiguration('IEEE', 'none');
            if ($ieee != $msg['ieee']) {
                log::add('Abeille', 'debug', "  ERROR: IEEE mistmatch, got ".$msg['ieee']." while expecting ".$ieee);
                return;
            }
            $eqId = $eqLogic->getId();
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'PAN-ID');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['panId']);
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Ext_PAN-ID');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['extPanId']);
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Network-Channel');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['chan']);

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        }

        /* Permit join status (8014 response) */
        if ($msg['type'] == "permitJoin") {
            /* Reminder
                'src' => 'parser',
                'type' => 'permitJoin',
                'net' => $dest,
                'status' => $Status,
                'time' => time()
            ); */

            log::add('Abeille', 'debug', "msgFromParser(): ".$net.", permit join, status=".$msg['status']);
            $eqLogic = self::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $eqId = $eqLogic->getId();
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'permitJoin-Status');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['status']);
            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        }

        log::add('Abeille', 'debug', "msgFromParser(): WARNING: Unsupported msg");
        log::add('Abeille', 'debug', "msgFromParser(): ".json_encode($msg));
    } // End msgFromParser()

    public static function publishMosquitto($queueId, $priority, $topic, $payload)
    {
        static $queueStatus = []; // "ok" or "error"

        $queue = msg_get_queue($queueId);
        if ($queue == false) {
            // log::add('Abeille', 'error', "publishMosquitto(): La queue ".$queueId." n'existe pas. Message ignoré.");
            return;
        }
        if (($stat = msg_stat_queue($queue)) == false) {
            return; // Something wrong
        }

        /* To avoid plenty errors, checking if someone really reads the queue.
           If not, do nothing but a message to user first time.
           Note: Assuming potential pb if more than 50 pending messages. */
        $pendMsg = $stat['msg_qnum']; // Pending messages
        if ($pendMsg > 50) {
            if (file_exists("/proc/") && !file_exists("/proc/".$stat['msg_lrpid'])) {
                /* Receiver process seems down */
                if (isset($queueStatus[$queueId]) && ($queueStatus[$queueId] == "error"))
                    return; // Queue already marked "in error"
                message::add("Abeille", "Alerte ! Démon arrété ou planté. (Re)démarrage nécessaire.", '');
                $queueStatus[$queueId] = "error";
                return;
            }
        }

        // $parameters_info = AbeilleTools::getParameters();

        $msgAbeille = new MsgAbeille;
        $msgAbeille->message['topic'] = $topic;
        $msgAbeille->message['payload'] = $payload;

        if (msg_send($queue, $priority, $msgAbeille, true, false, $error_code)) {
            log::add('Abeille', 'debug', "publishMosquitto(): Envoyé '".json_encode($msgAbeille->message)."' vers queue ".$queueId);
            $queueStatus[$queueId] = "ok"; // Status ok
        } else
            log::add('Abeille', 'warning', "publishMosquitto(): Impossible d'envoyer '".json_encode($msgAbeille->message)."' vers queue ".$queueId);
    } // End publishMosquitto()

    public static function createRuche($message = null)
    {
        $dest = $message->payload;
        $elogic = self::byLogicalId($dest."/0000", 'Abeille');
        if (is_object($elogic)) {
            log::add('Abeille', 'debug', 'message: createRuche: objet: '.$elogic->getLogicalId().' existe deja');
            return;
        }
        // Creation de la ruche
        log::add('Abeille', 'info', 'objet ruche : creation par model de '.$dest."/0000");

        /*
            $cmdId = end($topicArray);
            $key = count($topicArray) - 1;
            unset($topicArray[$key]);
            $addr = end($topicArray);
            // nodeid est le topic sans le dernier champ
            $nodeid = implode($topicArray, '/');
            */

        message::add("Abeille", "Création de l objet Ruche en cours, dans quelques secondes rafraichissez votre dashboard pour le voir.", '');
        $parameters_info = AbeilleTools::getParameters();
        $elogic = new Abeille();
        //id
        $elogic->setName("Ruche-".$dest);
        $elogic->setLogicalId($dest."/0000");
        if ($parameters_info['AbeilleParentId'] > 0) {
            $elogic->setObject_id($parameters_info['AbeilleParentId']);
        } else {
            $elogic->setObject_id(jeeObject::rootObject()->getId());
        }
        $elogic->setEqType_name('Abeille');
        $elogic->setConfiguration('topic', $dest."/0000");
        $elogic->setConfiguration('type', 'topic');
        $elogic->setConfiguration('lastCommunicationTimeOut', '-1');
        $elogic->setIsVisible("1");
        $elogic->setConfiguration('icone', "Ruche");
        // eqReal_id
        $elogic->setIsEnable("1");
        // status
        $elogic->setTimeout(5); // timeout en minutes
        // $elogic->setCategory();
        // display
        // order
        // comment

        //log::add('Abeille', 'info', 'Saving device '.$nodeid);
        //$elogic->save();
        $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $elogic->save();

        $rucheCommandList = AbeilleTools::getJSonConfigFiles('rucheCommand.json', 'Abeille');

        // Only needed for debug and dev so by default it's not done.
        if (0) {
            $i = 100;

            //Load all commandes from defined objects (except ruche), and create them hidden in Ruche to allow debug and research.
            $items = AbeilleTools::getDeviceNameFromJson('Abeille');

            foreach ($items as $item) {
                $AbeilleObjetDefinition = AbeilleTools::getJSonConfigFilebyDevices(AbeilleTools::getTrimmedValueForJsonFiles($item), 'Abeille');
                // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
                foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
                    $rucheCommandList[$objetId] = array(
                        "name" => $objetId,
                        "order" => $i++,
                        "isVisible" => "0",
                        "isHistorized" => "0",
                        "Type" => "action",
                        "subType" => "other",
                        "configuration" => array("topic" => "CmdCreate/".$objetId."/0000-0005", "request" => $objetId, "visibilityCategory" => "additionalCommand", "visibiltyTemplate" => "0"),
                    );
                }
            }
            // print_r($rucheCommandList);
        }

        //Create ruche object and commands
        foreach ($rucheCommandList as $cmd => $cmdValueDefaut) {
            $nomObjet = "Ruche";
            log::add(
                'Abeille',
                'info',
                // 'Creation de la command: '.$nodeid.'/'.$cmd.' suivant model de l objet: '.$nomObjet
                'Creation de la command: '.$cmd.' suivant model de l objet: '.$nomObjet
            );
            $cmdlogic = new AbeilleCmd();
            // id
            $cmdlogic->setEqLogic_id($elogic->getId());
            $cmdlogic->setEqType('Abeille');
            $cmdlogic->setLogicalId($cmd);
            $cmdlogic->setOrder($cmdValueDefaut["order"]);
            $cmdlogic->setName($cmdValueDefaut["name"]);
            if ($cmdValueDefaut["Type"] == "action") {
                // $cmdlogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd);
                $cmdlogic->setConfiguration('topic', $cmd);

                // Tcharp38: work in progress. Adding support for linked commands
                // Note: Error if info cmd is not registered BEFORE action cmd.
                // if (isset($cmdValueDefaut["value"])) {
                //     // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                //     log::add('Abeille', 'debug', 'Define cmd info pour cmd action: '.$elogic->getHumanName()." - ".$cmdValueDefaut["value"]);

                //     $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $elogic->getName(), $cmdValueDefaut["value"]);
                //     $cmdlogic->setValue($cmdPointeur_Value->getId());
                // }
            } else {
                // $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd);
                $cmdlogic->setConfiguration('topic', $cmd);
            }
            // if ($cmdValueDefaut["Type"] == "action") {  // not needed as mosquitto is not used anymore
            //    $cmdlogic->setConfiguration('retain', '0');
            // }
            if (isset($cmdValueDefaut["configuration"])) {
                foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                    $cmdlogic->setConfiguration($confKey, $confValue);
                }
            }
            // template
            if (isset($cmdValueDefaut["template"])) $cmdlogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
            if (isset($cmdValueDefaut["template"])) $cmdlogic->setTemplate('mobile', $cmdValueDefaut["template"]);
            if (isset($cmdValueDefaut["isHistorized"])) $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
            $cmdlogic->setType($cmdValueDefaut["Type"]);
            $cmdlogic->setSubType($cmdValueDefaut["subType"]);
            // unite
            if (isset($cmdValueDefaut["invertBinary"])) $cmdlogic->setDisplay('invertBinary', '0');
            // La boucle est pour info et pour action
            if (isset($cmdValueDefaut["display"])) {
                foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                    // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                    $cmdlogic->setDisplay($confKey, $confValue);
                }
            }
            if (isset($cmdValueDefaut["isVisible"])) $cmdlogic->setIsVisible($cmdValueDefaut["isVisible"]);
            // value
            // html
            // alert

            $cmdlogic->save();
            // $elogic->checkAndUpdateCmd($cmdId, $cmdValueDefaut["value"]);
        }
    } // End createRuche()

    /* Create or update Jeedom device based on its JSON config.
       This is also used to create Abeille's specific device like "remotecontrol". */
    public static function createDevice($net, $addr, $ieee, $mainEP, $jsonName, $userMsg = '') {

        $logicalId = $net.'/'.$addr;
        $abeilleConfig = AbeilleTools::getParameters();
        $deviceConfig = AbeilleTools::getDeviceConfig($jsonName);
        // $deviceConfig = $deviceConfig[$jsonName]; // Removing top key

        $eqType = $deviceConfig['nameJeedom'];

        $elogic = self::byLogicalId($net."/".$addr, 'Abeille');
        if (!is_object($elogic)) {
            $newEq = true;
            log::add('Abeille', 'debug', 'createDevice(): New device '.$net.'/'.$addr);
            if ($jsonName != "defaultUnknown")
                message::add("Abeille", "Nouvel équipement identifié (".$eqType."). Création en cours. Rafraîchissez votre dashboard dans qq secondes.", '');
            else
                message::add("Abeille", "Nouvel équipement détecté mais non reconnu. Création en cours avec la config par défaut (".$jsonName."). Rafraîchissez votre dashboard dans qq secondes.", '');

            $elogic = new Abeille();
            $elogic->setEqType_name('Abeille');
            $elogic->setName("newDevice-".$addr); // Temp name to have it non empty
            $elogic->save(); // Save to force Jeedom to assign an ID

            $eqName = $net."-".$elogic->getId(); // Default name (ex: 'Abeille1-12')
            $elogic->setName($eqName);
            $elogic->setLogicalId($logicalId);
            $elogic->setObject_id($abeilleConfig['AbeilleParentId']);
        } else {
            $newEq = false;
            log::add('Abeille', 'debug', 'createDevice(): Already existing device '.$net.'/'.$addr);
            $eqName = $elogic->getName();
            $eqPath = $elogic->getHumanName(); // Jeedom hierarchical name
            $eqCurJsonId = $elogic->getConfiguration('modeleJson'); // Current JSON ID
            if (($eqCurJsonId == 'defaultUnknown') && ($jsonName != 'defaultUnknown'))
                message::add("Abeille", "'".$eqPath."' s'est réannoncé. Mise-à-jour de la config par défaut vers '".$eqType."'", '');
            else if ($userMsg == '')
                message::add("Abeille", "'".$eqPath."' s'est réannoncé. Mise-à-jour en cours.", '');
            else
                message::add("Abeille", $userMsg, '');
        }

        /* Whatever creation or update, common steps follows */
        $objetConfiguration = $deviceConfig["configuration"];

        /* mainEP:
           Was used to defined main End Point on which we could read model/manuf/location */
        if ($mainEP == "#EP#")
            $mainEP = "01"; // Defaulting to 01

        // Replacing all remaining '#EP#' with 'mainEP' value
        // Note that this is possible only with old cmd JSON format (include XXX)
        log::add('Abeille', 'debug', 'createDevice(): mainEP='.$mainEP);
        $deviceConfigJson = json_encode($deviceConfig);
        $deviceConfigJson = str_replace('#EP#', $mainEP, $deviceConfigJson);
        $deviceConfig = json_decode($deviceConfigJson, true);
        log::add('Abeille', 'debug', 'createDevice(): Updated EQ config='.json_encode($deviceConfig));

        // $objetDefSpecific = $deviceConfig[$jsonName];
        log::add('Abeille', 'debug', 'Template config='.json_encode($objetConfiguration));
        $elogic->setConfiguration('modeleJson', $jsonName);
        $elogic->setConfiguration('type', 'topic'); // ??, type = topic car pas json. Tcharp38: what for ?

        if (isset($objetConfiguration["icon"]))
            $icon = $objetConfiguration["icon"];
        else if (isset($objetConfiguration["icone"])) // Old naming support
            $icon = $objetConfiguration["icone"];
        else
            $icon = '';
        $elogic->setConfiguration('icone', $icon);

        $elogic->setConfiguration('mainEP', $objetConfiguration["mainEP"]);
        $lastCommTimeout = (array_key_exists("lastCommunicationTimeOut", $objetConfiguration) ? $objetConfiguration["lastCommunicationTimeOut"] : '-1');
        $elogic->setConfiguration('lastCommunicationTimeOut', $lastCommTimeout);
        $elogic->setConfiguration('IEEE', $ieee);

        if (isset($objetConfiguration['batteryType']))
            $elogic->setConfiguration('battery_type', $objetConfiguration['batteryType']);
        else if (isset($objetConfiguration['battery_type'])) // Old name support
            $elogic->setConfiguration('battery_type', $objetConfiguration['battery_type']);

        if (isset($objetConfiguration['paramType']))
            $elogic->setConfiguration('paramType', $objetConfiguration['paramType']);
        if (isset($objetConfiguration['Groupe'])) { // Tcharp38: What for ? Telecommande Innr - KiwiHC16: on doit pouvoir simplifier ce code. Mais comme c etait la premiere version j ai fait detaillé.
            $elogic->setConfiguration('Groupe', $objetConfiguration['Groupe']);
        }
        if (isset($objetConfiguration['GroupeEP1'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP1', $objetConfiguration['GroupeEP1']);
        }
        if (isset($objetConfiguration['GroupeEP3'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP3', $objetConfiguration['GroupeEP3']);
        }
        if (isset($objetConfiguration['GroupeEP4'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP4', $objetConfiguration['GroupeEP4']);
        }
        if (isset($objetConfiguration['GroupeEP5'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP5', $objetConfiguration['GroupeEP5']);
        }
        if (isset($objetConfiguration['GroupeEP6'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP6', $objetConfiguration['GroupeEP6']);
        }
        if (isset($objetConfiguration['GroupeEP7'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP7', $objetConfiguration['GroupeEP7']);
        }
        if (isset($objetConfiguration['GroupeEP8'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('GroupeEP8', $objetConfiguration['GroupeEP8']);
        }
        if (isset($objetConfiguration['onTime'])) { // Tcharp38: What for ?
            $elogic->setConfiguration('onTime', $objetConfiguration['onTime']);
        }
        if (isset($objetConfiguration['Zigate'])) {
            $elogic->setConfiguration('Zigate', $objetConfiguration['Zigate']);
        }
        if (isset($objetConfiguration['protocol'])) {
            $elogic->setConfiguration('protocol', $objetConfiguration['protocol']);
        }
        if (isset($objetConfiguration['poll'])) {
            $elogic->setConfiguration('poll', $objetConfiguration['poll']);
        }

        if (isset($deviceConfig["isVisible"]))
            $elogic->setIsVisible($deviceConfig["isVisible"]);
        else
            $elogic->setIsVisible(1);
        $elogic->setIsEnable(1);
        if (isset($deviceConfig["timeout"]))
            $elogic->setTimeout($deviceConfig["timeout"]);

        if (isset($deviceConfig["category"]))
            $categories = $deviceConfig["category"];
        else if (isset($deviceConfig["Categorie"])) // Old name support
            $categories = $deviceConfig["Categorie"];
        // $elogic->setCategory(array_keys($deviceConfig["Categorie"])[0], $deviceConfig["Categorie"][array_keys($objetDefSpecific["Categorie"])[0]]);
        $allCat = ["heating","security","energy","light","opening","automatism","multimedia","default"];
        foreach ($allCat as $cat) { // Clear all
            $elogic->setCategory($cat, "0");
        }
        foreach ($categories as $key => $value) {
            $elogic->setCategory($key, $value);
        }

        $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $elogic->save();

        if (isset($deviceConfig['commands']))
            $jsonCmds = $deviceConfig['commands'];
        else if (isset($v['Commandes'])) // Old name support
            $jsonCmds = $deviceConfig['Commandes'];

        /* Removing obsolete commands, not listed in JSON.
           Might be needed for ex if device was previously 'defaultUnknown'. */
        $cmds = Cmd::byEqLogicId($elogic->getId());
        foreach ($cmds as $cmdLogic) {
            $found = false;
            $cmdName = $cmdLogic->getName();
            foreach ($jsonCmds as $cmd => $cmdValueDefaut) {
                if ($cmdName == $cmdValueDefaut["name"]) {
                    $found = true;
                    break; // Listed in JSON
                }
            }
            if ($found == false) {
                log::add('Abeille', 'debug', 'createDevice(): '.$eqName.", removing cmd '".$cmdName."'");
                $cmdLogic->remove(); // No longer required
            }
        }

        /* Creating or updating commands. */
        $order = 0;
        foreach ($jsonCmds as $cmdKey => $cmdValueDefaut) {
            if ($cmdValueDefaut["type"] == "info")
                $type = "info";
            else if ($cmdValueDefaut["type"] == "action")
                $type = "action";
            else {
                log::add('Abeille', 'error', "La commande '".$cmdJName."' (fichier ".$cmdKey.".json) n'a pas de type défini => ignorée");
                break;
            }

            // $cmdJName = $cmdValueDefaut["name"]; // Jeedom command name
            $cmdJName = $cmdKey; // Jeedom command name
            if ($type == "info")
                $cmdAName = $cmdValueDefaut["logicalId"]; // Abeille command name
            else
                $cmdAName = $cmdValueDefaut["configuration"]['topic']; // Abeille command name
            if (isset($cmdValueDefaut["configuration"]['request']))
                $cmdAParams = $cmdValueDefaut["configuration"]['request']; // Abeille command params
            else
                $cmdAParams = '';

            /* New or existing cmd ? */
            $cmdlogic = AbeilleCmd::byEqLogicIdCmdName($elogic->getId(), $cmdJName);
            if (!is_object($cmdlogic)) {
                $newCmd = true;
                log::add('Abeille', 'debug', 'createDevice(): '.$eqName.", adding cmd '".$cmdJName."' => '".$cmdAName."', '".$cmdAParams."'");
                $cmdlogic = new AbeilleCmd();
            } else {
                $newCmd = false;
                log::add('Abeille', 'debug', 'createDevice(): '.$eqName.", updating cmd '".$cmdJName."' => '".$cmdAName."', '".$cmdAParams."'");
            }

            $cmdlogic->setEqLogic_id($elogic->getId());
            $cmdlogic->setEqType('Abeille');
            // Tcharp38: Cmds now created in order of declarations in device JSON.
            // Does not make sense to be defined in cmd itself since can be reused by different device.
            // if (isset($cmdValueDefaut["order"]))
            //     $cmdlogic->setOrder($cmdValueDefaut["order"]);
            $cmdlogic->setOrder($order++);
            $cmdlogic->setName($cmdJName);

            if (isset($cmdValueDefaut["logicalId"])) // Mandatory for info cmds
                $cmdlogic->setLogicalId($cmdValueDefaut["logicalId"]);
            else
                $cmdlogic->setLogicalId($cmdKey);

            if ($type == "info") { // info cmd
            } else { // action cmd
                if (isset($cmdValueDefaut["value"])) {
                    // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                    log::add('Abeille', 'debug', 'createDevice(): Define cmd info pour cmd action: '.$elogic->getHumanName()." - ".$cmdValueDefaut["value"]);

                    $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $elogic->getName(), $cmdValueDefaut["value"]);
                    $cmdlogic->setValue($cmdPointeur_Value->getId());
                }
            }

            if (isset($cmdValueDefaut["trig"]))
                $cmdlogic->setConfiguration('ab::trig', $cmdValueDefaut["trig"]);
            else
                $cmdlogic->setConfiguration('ab::trig', null); // Removing config entry

            /* Updating 'configuration' fields of eqLogic from JSON.
               In case of update, some fields may no longer be required ($unusedConfKey).
               They are removed if not updated from JSON. */
            $unusedConfKey = ['visibilityCategory', 'minValue', 'maxValue', 'historizeRound', 'calculValueOffset', 'execAtCreation', 'execAtCreationDelay', 'uniqId', 'repeatEventManagement', 'topic'];
            foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                // $cmdlogic->setConfiguration($confKey, str_replace('#addr#', $addr, $confValue)); // Ce n'est plus necessaire car l adresse est maintenant dans le logicalId

                // Ne pas effacer, en cours de dev.
                // $cmdlogic->setConfiguration($confKey, str_replace('#addrIEEE#',     '#addrIEEE#',   $confValue));
                // $cmdlogic->setConfiguration($confKey, str_replace('#ZiGateIEEE#',   '#ZiGateIEEE#', $confValue));

                $cmdlogic->setConfiguration($confKey, $confValue);
                foreach ($unusedConfKey as $uk => $uv) {
                    if ($uv != $confKey)
                        continue;
                    unset($unusedConfKey[$uk]);
                }
            }

            /* Removing any obsolete configuration field */
            foreach ($unusedConfKey as $confKey) {
                // Tcharp38: Is it the proper way to know if entry exists ?
                if ($cmdlogic->getConfiguration($confKey) == null)
                    continue;
                // log::add('Abeille', 'debug', '  Removing obsolete configuration entry: '.$confKey);
                $cmdlogic->setConfiguration($confKey, null); // Removing config entry
            }

            // On conserve l info du template pour la visibility
            // Tcharp38: What for ? Not found where it is used
            if (isset($cmdValueDefaut["isVisible"]))
                $cmdlogic->setConfiguration("visibiltyTemplate", $cmdValueDefaut["isVisible"]);

            /* Command widget: can be defaulted with 'template'
               Updating only if new command to not overwrite user changes (see issue #2075) */
            if ($newCmd) {
                // Don't touch anything if defined empty in JSON
                if (isset($cmdValueDefaut["template"]) && ($cmdValueDefaut["template"] != "")) {
                    $cmdlogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
                    $cmdlogic->setTemplate('mobile', $cmdValueDefaut["template"]);
                }
            }

            $cmdlogic->setType($cmdValueDefaut["type"]);
            $cmdlogic->setSubType($cmdValueDefaut["subType"]);
            if (array_key_exists("generic_type", $cmdValueDefaut))
                $cmdlogic->setGeneric_type($cmdValueDefaut["generic_type"]);

            if (isset($cmdValueDefaut["unite"]))
                $cmdlogic->setUnite($cmdValueDefaut["unite"]);

            if (isset($cmdValueDefaut["isHistorized"]))
                $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
            else
                $cmdlogic->setIsHistorized(0);

            if (isset($cmdValueDefaut["isVisible"]))
                $cmdlogic->setIsVisible($cmdValueDefaut["isVisible"]);
            else
                $cmdlogic->setIsVisible(0);

            // TODO: Update all JSON to move "invertBinary" into "display" section
            if (isset($cmdValueDefaut["invertBinary"])) {
                $cmdlogic->setDisplay('invertBinary', $cmdValueDefaut["invertBinary"]);
            }
            if (array_key_exists("display", $cmdValueDefaut))
                foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                    $cmdlogic->setDisplay($confKey, $confValue);
                }
            $cmdlogic->save();
        }
    } // End createDevice()

    /* Update all infos related to last communication time of given device.
       This is based on timestamp of last communication received from device itself. */
    public static function updateTimestamp($eqLogic, $timestamp) {
        $eqLogicId = $eqLogic->getLogicalId();
        $eqId = $eqLogic->getId();

        log::add('Abeille', 'debug', "updateTimestamp(): Updating last comm. time for '".$eqLogicId."'");

        // Updating directly eqLogic/setStatus/'lastCommunication' & 'timeout' with real timestamp
        $eqLogic->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));

        /* Tcharp38 note:
           The cases hereafter could be removed. Using 'lastCommunication' allows to no longer
           use these 3 specific & redondant commands. To be discussed. */

        $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-TimeStamp");
        if (!is_object($cmdlogic))
            log::add('Abeille', 'debug', 'updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-TimeStamp'");
        else
            $eqLogic->checkAndUpdateCmd($cmdlogic, $timestamp);

        $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-Time");
        if (!is_object($cmdlogic))
            log::add('Abeille', 'debug', 'updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-Time'");
        else
            $eqLogic->checkAndUpdateCmd($cmdlogic, date("Y-m-d H:i:s", $timestamp));

        $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'online');
        if (!is_object($cmdlogic))
            log::add('Abeille', 'debug', 'updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'online'");
        else
            $eqLogic->checkAndUpdateCmd($cmdlogic, 1);
    }
}

