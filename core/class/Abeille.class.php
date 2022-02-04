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
    include_once __DIR__.'/AbeilleTools.class.php';
    include_once __DIR__.'/AbeilleCmd.class.php';
    include_once __DIR__.'/../../plugin_info/install.php'; // updateConfigDB()
    include_once __DIR__.'/../php/AbeilleLog.php'; // logGetPluginLevel()

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

        if ($zigateY > $GLOBALS['maxNbOfZigate']) {
            log::add('Abeille', 'debug', 'Cette Zigate n existe pas: '.$zigateY.', je ne peux faire l operation.');
            return;
        }

        list($destBee, $shortBee) = explode('/', $bee->getLogicalId());

        $newDestBee = "Abeille".$zigateY;

        // 1/ Changement logical Id
        $bee->setLogicalId($newDestBee.'/'.$shortBee);
        $bee->save();

        // 2/ Remove zigbee reseau 1 zigbee
        self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$newDestBee."/0000/Remove", "ParentAddressIEEE=".$IEEE."&ChildAddressIEEE=".$IEEE );

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
            self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$destGhost."/0000/Remove", "ParentAddressIEEE=".$IEEE."&ChildAddressIEEE=".$IEEE );
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
     * @return advice comment by question mark icon
     * @return state  if the test was successful or not
     */
    public static function health()
    {
        $return = array();
        $result = '';

        for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
            if ( config::byKey('AbeilleActiver'.$i, 'Abeille', '1', 1) ) {
                $result .= config::byKey('AbeilleSerialPort'.$i, 'Abeille', '', 1);
            }
        }

        $return[] = array(
            'test' => 'Ports: ',             // title of the line
            'result' => $result,             // Text which be printed in the line
            'advice' => 'Ports utilisés',    // Text printed when mouse is on question mark icon
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
    // Tcharp38: No longer used
    // public static function tryToGetIEEE()
    // {
    //     log::add('Abeille', 'debug', 'Recherche des adresses IEEE manquantes.');
    //     $tryToGetIEEEArray = array();
    //     $eqLogics = Abeille::byType('Abeille');

    //     foreach ($eqLogics as $key => $eqLogic) {
    //         if ($eqLogic->getIsEnable() != 1) {
    //             log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' désactivé => ignoré');
    //             continue; // Eq disabled => ignored
    //         }
    //         if ($eqLogic->getTimeout() == 1) {
    //             log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' en timeout => ignoré');
    //             continue; // Eq in timeout => ignored
    //         }
    //         if ($eqLogic->getStatus('lastCommunication') == '') {
    //             log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' n\'a jamais communiqué => ignoré');
    //             continue; // Eq in timeout => ignored
    //         }

    //         if (strlen($eqLogic->getConfiguration('IEEE', 'none')) == 16) {
    //             continue; // J'ai une adresse IEEE dans la conf donc je passe mon chemin
    //         }

    //         $commandIEEE = $eqLogic->getCmd('info', 'IEEE-Addr');
    //         if ($commandIEEE == null) {
    //             log::add('Abeille', 'debug', '  Eq \''.$eqLogic->getLogicalId().'\' sans cmd \'IEEE-Addr\' => ignoré');
    //             continue; // No cmd to retrieve IEEE address. Normal ?
    //         }

    //         if (strlen($commandIEEE->execCmd()) == 16) {
    //             $eqLogic->setConfiguration('IEEE', $commandIEEE->execCmd()); // Si je suis a cette ligne c est que je n ai pas IEEE dans conf mais dans cmd alors je mets dans conf.
    //             $eqLogic->save();
    //             $eqLogic->refresh();
    //             continue; // J'ai une adresse IEEE dans la commande donc je passe mon chemin
    //         }

    //         $tryToGetIEEEArray[] = $key;
    //     }

    //     // Prend x abeilles au hasard dans cette liste d'abeille a interroger.
    //     $eqLogicIds = array_rand($tryToGetIEEEArray, 2);

    //     // Pour ces x Abeilles lance l interrogation
    //     foreach ($eqLogicIds as $eqLogicId) {
    //         echo "Start Loop: ".$eqLogicId."\n";
    //         // echo "Start Loop Detail: ";
    //         $eqLogicX = $eqLogics[$tryToGetIEEEArray[$eqLogicId]];
    //         // var_dump($eqLogic);
    //         $commandIEEE_X = $eqLogicX->getCmd('info', 'IEEE-Addr');
    //         if ($commandIEEE_X) {
    //             $addrIEEE_X = $commandIEEE_X->execCmd();
    //             if (strlen($addrIEEE_X) < 2) {
    //                 list($dest, $NE) = explode('/', $eqLogicX->getLogicalId());
    //                 if (strlen($NE) == 4) {
    //                     if ($eqLogicX->getIsEnable()) {
    //                         log::add('Abeille', 'debug', 'Demarrage tryToGetIEEE for '.$NE);
    //                         echo 'Demarrage tryToGetIEEE for '.$NE."\n";
    //                         $cmd = "/usr/bin/nohup php ".__DIR__."/../php/AbeilleInterrogate.php ".$dest." ".$NE." >/dev/null 2>&1 &";
    //                         // echo "Cmd: ".$cmd."\n";
    //                         exec($cmd, $out, $status);
    //                     } else echo "Je n essaye pas car Abeille inactive.\n";
    //                 } else echo "Je n ai pas recuperé l adresse courte !!!\n";
    //             } else echo "IEEE superieure à deux carateres !!! :".$addrIEEE_X."\n";
    //         } else echo "commandIEEE n existe pas !!!!\n";
    //     }
    // }

    public static function updateConfigAbeille($abeilleIdFilter = false)
    {

    }

    /**
     * executePollCmds
     * Execute commands with "Polling" flag according to given "period".
     *
     * @param $period One of the crons: cron, cron15, cronHourly....
     *
     * @return Does not return anything as all action are triggered by sending messages in queues
     */
    public static function executePollCmds($period)
    {
        $cmds = cmd::searchConfiguration('Polling', 'Abeille');
        foreach ($cmds as $cmd) {
            if ($cmd->getConfiguration('Polling') != $period)
                continue;
            $eqLogic = $cmd->getEqLogic();
            $eqLogicId = $eqLogic->getHumanName();
            log::add('Abeille', 'debug', "executePollCmds(".$period."), '".$eqLogicId."', cmdLogicId='".$cmd->getLogicalId()."'");
            $cmd->execute();
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
        global $abQueues;

        log::add('Abeille', 'debug', 'refreshCmd: start');
        $i=15;
        foreach (AbeilleCmd::searchConfiguration('RefreshData', 'Abeille') as $key => $cmd) {
            if ($cmd->getConfiguration('RefreshData',0)) {
                log::add('Abeille', 'debug', 'refreshCmd: '.$cmd->getHumanName().' ('.$cmd->getEqlogic()->getLogicalId().')' );
                // $cmd->execute(); le process ne sont pas tous demarrer donc on met une tempo.
                // $topic = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getLogicalId();
                $topic = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getConfiguration('topic');
                $request = $cmd->getConfiguration('request');
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$topic."&time=".(time()+$i), $request);
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

        $preventLQIRequest = config::byKey('preventLQIRequest', 'Abeille', 'no');
        if ($preventLQIRequest == "yes") {
            log::add('Abeille', 'debug', 'cronD: LQI request (AbeilleLQI.php) prevented on user request.');
        } else {
            // Refresh LQI once a day to get IEEE in prevision of futur changes, to get network topo as fresh as possible in json
            log::add('Abeille', 'debug', 'cronD: Starting LQI request (AbeilleLQI.php)');
            $ROOT = __DIR__."/../php";
            $cmd = "cd ".$ROOT."; nohup /usr/bin/php AbeilleLQI.php 1>/dev/null 2>/dev/null &";
            log::add('Abeille', 'debug', 'cronD: cmd=\''.$cmd.'\'');
            exec($cmd);
        }

        // Poll Cmd
        self::executePollCmds("cronDaily");
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

        $config = AbeilleTools::getParameters();
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
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/bindShort&time=".(time() + (($i * 33) + 1)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0006&reportToAddress=".$ZiGateIEEE);
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/bindShort&time=".(time() + (($i * 33) + 2)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0008&reportToAddress=".$ZiGateIEEE);
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/setReport&time=".(time() + (($i * 33) + 3)), "address=".$addr."&ClusterId=0006&AttributeId=0000&AttributeType=10");
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/setReport&time=".(time() + (($i * 33) + 4)), "address=".$addr."&ClusterId=0008&AttributeId=0000&AttributeType=20");
            }
        }
        if (($i * 33) > (3600)) {
            message::add("Abeille", "Danger il y a trop de message a envoyer dans le cron 1 heure.", "Contactez KiwiHC16 sur le Forum.");
        }
    }
        //--------------------------------------------------------
        // Poll Cmd
        self::executePollCmds("cronHourly");

        log::add('Abeille', 'debug', 'Ending cronHourly ------------------------------------------------------------------------------------------------------------------------');
    }

    /**
     * cron30
     * Called by Jeedom every 30 minutes.
     * executePollCmds
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron30() {
        // Poll Cmd
        self::executePollCmds("cron30");
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
        global $abQueues;

        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron15(): Main daemon stopped => cron15 canceled');
            return;
        }

        log::add('Abeille', 'debug', 'cron15(): Starting --------------------------------');

        /* Look every 15 minutes if the kernel driver is not in error */
        log::add('Abeille', 'debug', 'cron15(): Check USB driver potential crash');
        $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
        $output = array();
        exec(system::getCmdSudo().$cmd, $output);
        $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
        if ($usbZigateStatus != '0') {
            message::add("Abeille", "ERREUR: le pilote pl2303 semble en erreur, impossible de communiquer avec la zigate.", "Il faut débrancher/rebrancher la zigate et relancer le démon.");
            // log::add('Abeille', 'debug', 'cron15(): Fin --------------------------------');
        }

        log::add('Abeille', 'debug', 'cron15(): Interrogating devices silent for more than 15mins.');
        $config = AbeilleTools::getParameters();
        $i = 0;
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            $zigate = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            if (!is_object($zigate))
                continue; // Does not exist on Jeedom side.
            if (!$zigate->getIsEnable())
                continue; // Zigate disabled
            if ($config['AbeilleActiver'.$zgId] != 'Y')
                continue; // Zigate disabled.

            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list($dest, $addr) = explode("/", $eqLogic->getLogicalId());
                if ($dest != 'Abeille'.$zgId)
                    continue; // Not on current network
                if (!$eqLogic->getIsEnable())
                    continue; // Equipment disabled

                /* Special case: should ignore virtual remote */
                if ($eqLogic->getConfiguration("ab::jsonId") == "remotecontrol")
                    continue; // Abeille virtual remote

                $eqName = $eqLogic->getname();

                /* Checking if received some news in the last 15mins */
                $cmd = $eqLogic->getCmd('info', 'Time-TimeStamp');
                if (!is_object($cmd)) { // Cmd not found
                    log::add('Abeille', 'warning', "cron15(): Commande 'Time-TimeStamp' manquante pour ".$eqName);
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
                    log::add('Abeille', 'warning', "cron15(): 'End Point' principal manquant pour ".$eqName);
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
                    log::add('Abeille', 'debug', "cron15(): Interrogating '".$eqName."' (addr ".$addr.", poll-reason=".$poll.")");
                    // Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), $mainEP);
                    // Reading ZCLVersion attribute which should always be supported
                    Abeille::publishMosquitto($abQueues['xToCmd']['id'], PRIO_NORM, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+($i*23)), "ep=".$mainEP."&clustId=0000&attrId=0000");
                    $i++;
                }
            }
        }
        if (($i * 23) > (15 * 60)) { // A msg every 23sec must fit in 15mins.
            message::add("Abeille", "Danger ! Il y a trop de messages à envoyer dans le cron 15 minutes.", "Contacter KiwiHC15 sur le forum");
        }

        // Execute Action Cmd to refresh Info command
        // self::executePollCmds("cron15");

        log::add('Abeille', 'debug', 'cron15():Terminé --------------------------------');
        return;
    } // End cron15()

    /**
     * cron10
     * Called by Jeedom every 10 minutes.
     * executePollCmds
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
        self::executePollCmds("cron10");
    } // End cron10()

    /**
     * cron5
     * Called by Jeedom every 5 minutes.
     * executePollCmds
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
        self::executePollCmds("cron5");
    } // End cron5()

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
            log::add('Abeille', 'debug', 'cron(): Main daemon stopped => cron1 canceled');
            return;
        }

        // log::add( 'Abeille', 'debug', 'cron(): Start ------------------------------------------------------------------------------------------------------------------------' );
        $config = AbeilleTools::getParameters();

        $running = AbeilleTools::getRunningDaemons();
        $status = AbeilleTools::checkAllDaemons($config, $running);

        /* For debug purposes, display 'PID/daemonShortName' */
        $status = AbeilleTools::getRunningDaemons2();
        $daemons = "";
        foreach ($status['daemons'] as $daemon) {
            if ($daemons != "")
                $daemons .= ", ";
            $daemons .= $daemon['pid'].'/'.$daemon['shortName'];
        }
        log::add('Abeille', 'debug', 'cron(): Daemons: '.$daemons);

        // Checking queues status to log any potential issue.
        // Moved from deamon_info()
        $abQueues = $GLOBALS['abQueues'];
        foreach ($abQueues as $queueName => $queueDesc) {
            $queueId = $queueDesc['id'];
            $queue = msg_get_queue($queueId);
            if ($queue === false) {
                log::add('Abeille', 'info', "cron(): ERREUR: Pb d'accès à la queue '".$queueName."' (id ".$queueId.")");
                continue;
            }
            if (msg_stat_queue($queue)["msg_qnum"] > 100)
                log::add('Abeille', 'info', "cron(): ERREUR: La queue '".$queueName."' (id ".$queueId.") contient plus de 100 messages.");
        }

        // https://github.com/jeelabs/esp-link
        // The ESP-Link connections on port 23 and 2323 have a 5 minute inactivity timeout.
        // so I need to create a minimum of traffic, so pull zigate every minutes
        for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
            if ($config['AbeilleActiver'.$i] != 'Y')
                continue; // Zigate disabled
            if ($config['AbeilleSerialPort'.$i] == "none")
                continue; // Serial port undefined
            // TODO Tcharp38: Currently leads to PI zigate timeout. No sense since still alive.
            // if ($config['AbeilleType'.$i] != "WIFI")
            //     continue; // Not a WIFI zigate. No polling required

            // TODO: Better to read time to correct it if required, instead of version that rarely changes
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmdAbeille".$i."/0000/getZgVersion&time=".(time() + 20), "");
            // beille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmdAbeille".$i."/0000/getNetworkStatus&time=".(time() + 24), "getNetworkStatus");
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

            log::add('Abeille', 'debug', 'cron(): GetEtat/GetLevel, addr='.$address);
            $mainEP = $eqLogic->getConfiguration('mainEP');
            // Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$mainEP."&clusterId=0006&attributeId=0000");
            // Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$mainEP."&clusterId=0008&attributeId=0000");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+($i*3)), "ep=".$mainEP."&clustId=0006&attrId=0000");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+($i*3)), "ep=".$mainEP."&clustId=0008&attrId=0000");
            $i++;
        }
        if (($i * 3) > 60) {
            message::add("Abeille", "Danger ! Il y a trop de messages à envoyer dans le cron 1 minute.", "Contacter KiwiHC16 sur le forum.");
        }

        // Poll Cmd
        self::executePollCmds("cron");

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
        } // End cron()

        // Si Inclusion status est à 1 on demande un Refresh de l information
        // Je regarde si j ai deux zigate en inclusion et si oui je genere une alarme.
        $count = array();
        for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
            if (self::checkInclusionStatus("Abeille".$i) == 1) {
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$i."/0000/permitJoin", "Status");
                $count[] = $i;
            }
        }
        if (count($count) > 1) message::add("Abeille", "Danger vous avez plusieurs Zigate en mode inclusion: ".json_encode($count).". L equipement peut se joindre a l un ou l autre resau zigbee.", "Vérifier sur quel reseau se joint l equipement.");

        // log::add( 'Abeille', 'debug', 'cron(): Fin ------------------------------------------------------------------------------------------------------------------------' );
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
        for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
            $lockFile = jeedom::getTmpFolder('Abeille').'/AbeilleLQI_MapDataAbeille'.$i.'.json.lock';
            if (file_exists($lockFile)) {
                unlink($lockFile);
                log::add('Abeille', 'debug', 'deamon_start_cleanup(): Removed '.$lockFile);
            }
        }

        // Clear zigate IEEE status to detect any port switch.
        // AbeilleIEEE_Ok=-1: Zigate IEEE is NOT the expected one (port switch ?)
        //     "         = 0: IEEE check to be done
        //     "         = 1: Zigate on the right port
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            config::save("AbeilleIEEE_Ok".$zgId, 0, 'Abeille');
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

        $config = AbeilleTools::getParameters();

        /* Checking config */
        // TODO Tcharp38: Should be done during deamon_info() and report proper 'launchable'
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if ($config['AbeilleActiver'.$zgId] != 'Y')
                continue; // Disabled

            /* This zigate is enabled. Checking other parameters */
            $error = "";
            $sp = $config['AbeilleSerialPort'.$zgId];
            if (($sp == 'none') || ($sp == "")) {
                $error = "Port série de la zigate ".$zgId." INVALIDE";
            }
            if ($error == "") {
                if ($config['AbeilleType'.$zgId] == "WIFI") {
                    $wifiAddr = $config['IpWifiZigate'.$zgId];
                    if (($wifiAddr == 'none') || ($wifiAddr == "")) {
                        $error = "Adresse Wifi de la zigate ".$zgId." INVALIDE";
                    }
                }
            }
            if ($error != "") {
                $config['AbeilleActiver'.$zgId] = 'N';
                config::save('AbeilleActiver'.$zgId, 'N', 'Abeille');
                log::add('Abeille', 'error', $error." ! Zigate désactivée.");
            }
        }

        /* Configuring GPIO for PiZigate if one active found.
            PiZigate reminder (using 'WiringPi'):
            - port 0 = RESET
            - port 2 = FLASH
            - Production mode: FLASH=1, RESET=0 then 1 */
        for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
            if (($config['AbeilleSerialPort'.$i] == 'none') or ($config['AbeilleActiver'.$i] != 'Y'))
                continue; // Undefined or disabled
            if ($config['AbeilleType'.$i] == "PI") {
                AbeilleTools::checkGpio(); // Found an active PI Zigate, needed once
                break;
            }
        }

        /* Starting all required daemons */
        if (AbeilleTools::startDaemons($config) == false) {
            // Probably no active Zigate. Startup cancelled.
            return;
        }

        /* Waiting for background daemons to be up & running.
           If not, the return of first commands sent to zigate might be lost.
           This was sometimes the case for 0009 cmd which is key to 'enable' msg receive on parser side. */
        // TODO Tcharp38: Note: This should not longer be required as the parser itself do the request on startup
        $expected = 0; // 1 bit per expected serial read daemon
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if (($config['AbeilleSerialPort'.$zgId] == 'none') or ($config['AbeilleActiver'.$zgId] != 'Y'))
                continue; // Undefined or disabled
            $expected |= constant("daemonSerialRead".$zgId);
            if ($config['AbeilleType'.$zgId] == 'WIFI')
                $expected |= constant("daemonSocat".$zgId);
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

        // Starting main daemon; this will start to treat received messages
        cron::byClassAndFunction('Abeille', 'deamon')->run();

        // Tcharp38: Moved to main daemon (deamon())
        // // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
        // for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
        //     if (($config['AbeilleSerialPort'.$i] == 'none') or ($config['AbeilleActiver'.$i] != 'Y'))
        //         continue; // Undefined or disabled

        //     // log::add('Abeille', 'debug', 'deamon_start(): ***** creation de ruche '.$i.' (Abeille): '.basename($config['AbeilleSerialPort'.$i]));
        //     Abeille::publishMosquitto($abQueues["abeilleToAbeille"]["id"], priorityInterrogation, "CmdRuche/0000/CreateRuche", "Abeille".$i);

        //     // log::add('Abeille', 'debug', 'deamon_start(): ***** Demarrage du réseau Zigbee '.$i.' ********');
        //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$i."/0000/startNetwork", "StartNetwork");
        //     // log::add('Abeille', 'debug', 'deamon_start(): ***** Set Time réseau Zigbee '.$i.' ********');
        //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$i."/0000/setZgTimeServer", "");
        //     /* Get network state to get Zigate IEEE asap and confirm no port change */
        //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$i."/0000/getNetworkStatus", "getNetworkStatus");

        //     // Set the mode of the zigate, important from 3.1D.
        //     $version = "";
        //     $ruche = Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille');
        //     if ($ruche) {
        //         $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($ruche->getId(), 'SW-SDK');
        //         if ($cmdLogic) {
        //             $version = $cmdLogic->execCmd();
        //         }
        //     }
        //     if ($version == '031D') {
        //         log::add('Abeille', 'debug', 'deamon_start(): Configuring zigate '. $i.' in hybrid mode');
        //         // message::add("Abeille", "Demande de fonctionnement de la zigate en mode hybride (firmware >= 3.1D).");
        //         Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$i."/0000/setZgMode", "mode=hybrid");
        //     } else {
        //         log::add('Abeille', 'debug', 'deamon_start(): Configuring zigate '.$i.' in normal mode');
        //         // message::add("Abeille", "Demande de fonctionnement de la zigate en mode normal (firmware < 3.1D).");
        //         Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$i."/0000/setZgMode", "mode=normal");
        //     }
        // }

        // Tcharp38: Moved to main daemon (deamon())
        // Essaye de recuperer les etats des equipements
        // self::refreshCmd();

        // If debug mode, let's check there is at least 5000 lines for support needs
        if (logGetPluginLevel() == 4) {
            $jLines = log::getConfig('maxLineLog');
            if ($jLines < 5000)
                message::add("Abeille", "Vous êtes en mode debug mais le nombre de lignes est inférieur à 5000 (".$jLines."). Il est recommandé d'augmenter ce nombre pour tout besoin de support.");
        }

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
    //     $config = AbeilleTools::getParameters();

    //     for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
    //         if ($Abeille == "Abeille".$i) return basename($config['AbeilleSerialPort'.$i]);
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
    //     $config = AbeilleTools::getParameters();

    //     for ($i = 1; $i <= $GLOBALS['maxNbOfZigate']; $i++) {
    //         if ($port == $config['AbeilleSerialPort'.$i]) return "Abeille".$i;
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
        // Tcharp38 note: when all queues in $abQueues, we can delete $allQueues
        $abQueues = $GLOBALS['abQueues'];
        $allQueues = array(
            $abQueues["abeilleToAbeille"]["id"], /* queueKeyAbeilleToCmd, */ $abQueues["parserToAbeille"]["id"], $abQueues["parserToAbeille2"]["id"], $abQueues["cmdToAbeille"]["id"],
            $abQueues["cmdToMon"]["id"], $abQueues["parserToMon"]["id"], $abQueues["monToCmd"]["id"],
            $abQueues["assistToParser"]["id"], $abQueues["parserToAssist"]["id"], $abQueues["assistToCmd"]["id"],
            $abQueues["parserToLQI"]["id"], /* $abQueues["LQIToCmd"]["id"], */
            $abQueues["xmlToAbeille"]["id"], /* queueKeyXmlToCmd, queueKeyFormToCmd, */ $abQueues["parserToCli"]["id"],
            /* $abQueues["parserToCmd"]["id"], */ /* queueKeyCmdToCmd, */ $abQueues["serialToParser"]["id"], $abQueues["parserToCmdAck"]["id"], $abQueues["ctrlToParser"]["id"]
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
        global $abQueues;

        log::add('Abeille', 'debug', 'deamon(): Main daemon starting');

        /* Main daemon starting.
           This means that other daemons have started too. Abeille can communicate with them */

        // Send a message to Abeille to ask for behive creation/update.
        // Tcharp38: Moved from deamon_start()
        $config = AbeilleTools::getParameters();
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if (($config['AbeilleSerialPort'.$zgId] == 'none') or ($config['AbeilleActiver'.$zgId] != 'Y'))
                continue; // Undefined or disabled

            // Create/update beehive equipment on Jeedom side
            // Note: This will reset SW-SDK to '----' to mark FW version invalid.
            // Abeille::publishMosquitto($abQueues["abeilleToAbeille"]["id"], priorityInterrogation, "CmdRuche/0000/CreateRuche", "Abeille".$zgId);
            self::createRuche("Abeille".$zgId);

            // Configuring zigate: TODO: This should be done on Abeille startup or on new beehive creation.
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/startZgNetwork", "");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/setZgTimeServer", "");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/getZgVersion", "");

            // Set Zigate in 'hybrid' mode, (possible only since 3.1D).
            // Tcharp38: Need to get current FW version first so this part if moved to 'msgFromParser' on 'zigateVersion' recept.
            // $version = "0000";
            // $ruche = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            // if ($ruche) {
            //     $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($ruche->getId(), 'SW-SDK');
            //     if ($cmdLogic)
            //         $version = $cmdLogic->execCmd();
            //     else
            //         log::add('Abeille', 'debug', "deamon(): ERROR: Missing 'SW-SDK' cmd for 'Ruche".$zgId."'");
            // } else
            //     log::add('Abeille', 'debug', "deamon(): ERROR: Missing 'Ruche".$zgId."'");
            // if (hexdec($version) >= 0x031D) {
            //     log::add('Abeille', 'debug', 'deamon(): FW version >= 3.1D => Configuring zigate '.$zgId.' in hybrid mode');
            //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/setZgMode", "mode=hybrid");
            // } else {
            //     log::add('Abeille', 'debug', 'deamon(): Configuring zigate '.$zgId.' in normal mode');
            //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/setZgMode", "mode=normal");
            // }
        }

        // Essaye de recuperer les etats des equipements
        // Tcharp38: Moved from deamon_start()
        self::refreshCmd();

        try {
            $abQueues = $GLOBALS['abQueues'];
            // Tcharp38: Merge all the followings queues. Would be more efficient & more reactive.
            $queueAbeilleToAbeille = msg_get_queue($abQueues["abeilleToAbeille"]["id"]);
            $queueParserToAbeille = msg_get_queue($abQueues["parserToAbeille"]["id"]);
            $queueParserToAbeilleMax = $abQueues["parserToAbeille"]["max"];
            $queueParserToAbeille2 = msg_get_queue($abQueues["parserToAbeille2"]["id"]);
            $queueParserToAbeille2Max = $abQueues["parserToAbeille2"]["max"];
            $queueCmdToAbeille = msg_get_queue($abQueues["cmdToAbeille"]["id"]);
            $queueXmlToAbeille = msg_get_queue($abQueues["xmlToAbeille"]["id"]);

            $max_msg_size = 512;

            // https: github.com/torvalds/linux/blob/master/include/uapi/asm-generic/errno.h
            // const int EINVAL = 22;
            // const int ENOMSG = 42; /* No message of desired type */

            while (true) {
                if (msg_receive($queueAbeilleToAbeille, 0, $msgType, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errCode)) {
                    self::message($msg['topic'], $msg['payload']);
                } else { // Error
                    if ($errCode != 42)
                        log::add('Abeille', 'error', 'deamon(): msg_receive $abQueues["abeilleToAbeille"]["id"] error '.$errCode);
                }

                /* New path parser to Abeille */
                $msgMax = $queueParserToAbeille2Max;
                if (msg_receive($queueParserToAbeille2, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode)) {
                    self::msgFromParser(json_decode($msgJson, true));
                } else { // Error
                    if ($errCode == 7) {
                        msg_receive($queueParserToAbeille2, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        log::add('Abeille', 'debug', 'deamon(): msg_receive queueParserToAbeille2 ERROR: msg TOO BIG ignored.');
                    } else if ($errCode != 42)
                        log::add('Abeille', 'debug', 'deamon(): msg_receive queueParserToAbeille2 error '.$errCode);
                }

                /* Legacy path parser to Abeille. To be progressively removed */
                $msgMax = $queueParserToAbeilleMax;
                if (msg_receive($queueParserToAbeille, 0, $msgType, $msgMax, $msg, true, MSG_IPC_NOWAIT, $errCode)) {
                    self::message($msg['topic'], $msg['payload']);
                } else { // Error
                    if ($errCode == 7) {
                        msg_receive($queueParserToAbeille, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        log::add('Abeille', 'debug', 'deamon(): msg_receive queueParserToAbeille ERROR: msg TOO BIG ignored.');
                    } else if ($errCode != 42)
                        log::add('Abeille', 'debug', 'deamon(): msg_receive queueParserToAbeille error '.$errCode);
                }

                if (msg_receive($queueCmdToAbeille, 0, $msgType, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errCode)) {
                    self::message($msg['topic'], $msg['payload']);
                } else { // Error
                    if ($errCode != 42)
                        log::add('Abeille', 'debug', 'deamon(): msg_receive queueCmdToAbeille error '.$errCode);
                }

                if (msg_receive($queueXmlToAbeille, 0, $msgType, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errCode)) {
                    self::message($msg['topic'], $msg['payload']);
                } else { // Error
                    if ($errCode != 42)
                        log::add('Abeille', 'debug', 'deamon(): msg_receive queueXmlToAbeille error '.$errCode);
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
    //     $config = Abeille::getParameters();

    //     if ( !isset($GLOBALS['maxNbOfZigate']) ) { return 0; }
    //     if ( $GLOBALS['maxNbOfZigate'] < 1 ) { return 0; }
    //     if ( $GLOBALS['maxNbOfZigate'] > 9 ) { return 0; }

    //     // Testons la validité de la configuration
    //     $atLeastOneZigateActiveWithOnePortDefined = 0;
    //     for ( $i=1; $i<=$GLOBALS['maxNbOfZigate']; $i++ ) {
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
    //     for ( $i=1; $i<=$GLOBALS['maxNbOfZigate']; $i++ ) {
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
        log::add('Abeille', 'debug', 'postSave()');
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

        $config = AbeilleTools::getParameters();

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
                if ($config[$affichageType] == 'Y') {
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

    // Tcharp38: No longer required.
    // This part is directly handled by parser for better reactivity mandatory for battery powered devices.
    // public static function interrogateUnknowNE( $dest, $addr ) {
    //     if ( config::byKey( 'blocageRecuperationEquipement', 'Abeille', 'Oui', 1 ) == 'Oui' ) {
    //         log::add('Abeille', 'debug', "L equipement ".$dest."/".$addr." n existe pas dans Jeedom, je ne cherche pas a le recupérer, param: blocage recuperation equipment.");
    //         return;
    //     }

    //     if ($addr == "0000") return;

    //     log::add('Abeille', 'debug', "L equipement ".$dest."/".$addr." n existe pas dans Jeedom, j'essaye d interroger l equipement pour le créer.");

    //     // EP 01
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceManufacturer", "01");
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/Annonce", "Default");
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceProfalux", "Default");

    //     // EP 0B
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceManufacturer", "0B");
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/Annonce", "Hue");

    //     // EP 03
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/AnnonceManufacturer", "03");
    //     self::publishMosquitto($abQueues['xToCmd']['id'], priorityNeWokeUp, "Cmd".$dest."/".$addr."/Annonce", "OSRAM");

    //     return;
    // }

    public static function message($topic, $payload)
    {
        // KiwiHC16: Please leave this line log::add commented otherwise too many messages in log Abeille
        // and keep the 3 lines below which print all messages except Time-Time, Time-TimeStamp and Link-Quality that we get for every message.
        // Divide by 3 the log quantity and ease the log reading
        // log::add('Abeille', 'debug', "message(topic='".$topic."', payload='".$payload."')");

        $topicArray = explode("/", $topic);
        if (sizeof($topicArray) != 3) {
            log::add('Abeille', 'debug', "ERROR: Invalid message: topic=".$topic);
            return;
        }

        $config = AbeilleTools::getParameters();

        // if (!preg_match("(Time|Link-Quality)", $topic)) {
        //    log::add('Abeille', 'debug', "fct message Topic: ->".$topic."<- Value ->".$payload."<-");
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // demande de creation de ruche au cas ou elle n'est pas deja crée....
        // La ruche est aussi un objet Abeille
        if ($topic == "CmdRuche/0000/CreateRuche") {
            // log::add('Abeille', 'debug', "Topic: ->".$topic."<- Value ->".$payload."<-");
            self::createRuche($payload);
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // On ne prend en compte que les message Abeille|Ruche|CmdCreate/#/#
        // CmdCreate -> pour la creation des objets depuis la ruche par exemple pour tester les modeles
        if (!preg_match("(^Abeille|^Ruche|^CmdCreate|^ttyUSB|^zigate|^monitZigate)", $topic)) {
            // log::add('Abeille', 'debug', 'message: this is not a '.$Filter.' message: topic: '.$topic.' message: '.$payload);
            return;
        }

        /*----------------------------------------------------------------------------------------------------------------------------------------------*/
        // Analyse du message recu
        // [CmdAbeille:Abeille] / Address / Cluster-Parameter
        // [CmdAbeille:Abeille] / $addr / $cmdId => $value
        // $nodeId = [CmdAbeille:Abeille] / $addr

        list($Filter, $addr, $cmdId) = explode("/", $topic);
        // log::add('Abeille', 'debug', "message(): Filter=".$Filter.", addr=".$addr.", cmdId=".$cmdId);
        if (preg_match("(^CmdCreate)", $topic)) {
            $Filter = str_replace("CmdCreate", "", $Filter);
        }
        $net = $Filter; // Network (ex: 'Abeille1')
        $dest = $Filter;

        // log all messages except the one related to Time, which overload the log
        if (!in_array($cmdId, array("Time-Time", "Time-TimeStamp", "Link-Quality"))) {
            log::add('Abeille', 'debug', "message(topic='".$topic."', payload='".$payload."')");
        }

        $nodeid = $net.'/'.$addr;
        $value = $payload;
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
                $jsonId2 = $eqLogic->getConfiguration('ab::jsonId');
                if ($jsonId2 != "remotecontrol")
                    continue; // Not a remote
                if ($addr2 == '')
                    continue; // No addr for remote on '210607-STABLE-1' leading to 1 remote only per zigate.
                $addr2 = substr($addr2, 2); // Removing 'rc'
                if (hexdec($addr2) >= $max)
                    $max = hexdec($addr2) + 1;
            }

            /* Remote control short addr = 'rcXX' */
            $rcAddr = sprintf("rc%02X", $max);
            // Abeille::createDevice("create", $dest, $rcAddr, '', 'remotecontrol', 'Abeille');
            $dev = array(
                'net' => $dest,
                'addr' => $rcAddr,
                'jsonId' => 'remotecontrol',
                'jsonLocation' => 'Abeille',
            );
            Abeille::createDevice("create", $dev);

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

            // Abeille::createDevice("update", $dest, $addr);
            $dev = array(
                'net' => $dest,
                'addr' => $addr,
            );
            Abeille::createDevice("update", $dev);

            return;
        }

        /* Request to reset device from JSON. Useful to avoid reinclusion */
        if ($cmdId == "resetFromJson") {
            log::add('Abeille', 'debug', 'message(): resetFromJson, '.$net.'/'.$addr);

            $eqLogic = Abeille::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', '  ERROR: Unknown device');
                return;
            }

            /* Tcharp38 TODO (see #2211)
               If device is defaultUnknown and there is now a real model for it,
               it is not detected. Reset is done from defaultUnknown again.
               Need to store Zigbee modelId + manuf too for that purpose. */

            $dev = array(
                'net' => $dest,
                'addr' => $addr,
                'jsonId' => $eqLogic->getConfiguration('ab::jsonId'),
                'jsonLocation' => $eqLogic->getConfiguration('ab::jsonLocation', 'Abeille'),
                'ieee' => $eqLogic->getConfiguration('IEEE'),
            );
            // Abeille::createDevice("reset", $dest, $addr);
            Abeille::createDevice("reset", $dev);

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Cherche l objet par sa ref short Address et la commande
        $eqLogic = self::byLogicalId($nodeid, 'Abeille');
        if (is_object($eqLogic)) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdId);
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        /* If unknown eq and IEEE received, looking for eq with same IEEE to update logicalName & topic */
        // e.g. Short address change (Si l adresse a changé, on ne peut pas trouver l objet par son nodeId)
        if (!is_object($eqLogic) && ($cmdId == "IEEE-Addr")) {
            log::add('Abeille', 'debug', 'message(), !objet & IEEE: Recherche de l\'equipement correspondant');
            // 0 : Short Address is aligned with the one received
            // Short : Short Address is NOT aligned with the one received
            // -1 : Error Nothing found
            $ShortFound = Abeille::fetchShortFromIEEE($value, $addr);
            log::add('Abeille', 'debug', 'message(), !objet & IEEE: Trouvé='.$ShortFound);
            if ((strlen($ShortFound) == 4) && ($addr != "0000")) {

                $eqLogic = self::byLogicalId($dest."/".$ShortFound, 'Abeille');
                if (!is_object($eqLogic)) {
                    log::add('Abeille', 'debug', 'message(), !objet & IEEE: L\'équipement ne semble pas sur la bonne zigate. Abeille ne fait rien automatiquement. L\'utilisateur doit résoudre la situation.');
                    return;
                }

                // log::add('Abeille', 'debug', "message(), !objet & IEEE: Adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - ".$eqLogic->getName().", on fait la mise a jour automatique");
                log::add('Abeille', 'debug', "message(), !objet & IEEE: $value correspond à '".$eqLogic->getHumanName()."'. Mise-à-jour de l'adresse courte $ShortFound vers $addr.");
                // Comme c est automatique des que le retour d experience sera suffisant, on n alerte pas l utilisateur. Il n a pas besoin de savoir
                // message::add("Abeille", "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - ".$eqLogic->getName().", on fait la mise a jour automatique", '');
                message::add("Abeille", "Nouvelle adresse '".$addr."' pour '".$eqLogic->getHumanName()."'. Mise à jour automatique.");

                // Si on trouve l adresse dans le nom, on remplace par la nouvelle adresse
                // log::add('Abeille', 'debug', "!objet&IEEE --> IEEE-Addr; Ancien nom: ".$eqLogic->getName().", nouveau nom: ".str_replace($ShortFound, $addr, $eqLogic->getName()));
                // $eqLogic->setName(str_replace($ShortFound, $addr, $eqLogic->getName()));

                $eqLogic->setLogicalId($dest."/".$addr);
                $eqLogic->setConfiguration('topic', $dest."/".$addr);
                $eqLogic->save();

                // Il faut aussi mettre a jour la commande short address
                Abeille::publishMosquitto($abQueues["abeilleToAbeille"]["id"], priorityInterrogation, $dest."/".$addr."/Short-Addr", $addr);
            } else {
                log::add('Abeille', 'debug', 'message(), !objet & IEEE: Je n ai pas trouvé d Abeille qui corresponde.');
                // self::interrogateUnknowNE( $dest, $addr );
            }
            // log::add('Abeille', 'debug', '!objet&IEEE --> fin du traitement');
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande => je drop la cmd but I try to get the device into Abeille
        // e.g. un Equipement envoie des infos, mais l objet n existe pas dans Jeedom
        if (!is_object($eqLogic)) {

            // Je ne fais les demandes que si les commandes ne sont pas Time-Time, Time-Stamp et Link-Quality
            if (!preg_match("(Time|Link-Quality)", $topic)) {

                if (!Abeille::checkInclusionStatus($dest)) {
                    log::add('Abeille', 'info', 'Des informations remontent pour un equipement inconnu d Abeille avec pour adresse '.$addr.' et pour la commande '.$cmdId );
                }

                // self::interrogateUnknowNE( $dest, $addr );
            }

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet exist et on recoie une IEEE
        // e.g. Un NE renvoie son annonce
        if (is_object($eqLogic) && ($cmdId == "IEEE-Addr")) {

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
            if (!is_object($cmdLogic)) {
                log::add('Abeille', 'debug', 'IEEE-Addr commande n existe pas');
                return;
            }

            // $IEEE = $cmdLogic->execCmd();
            $IEEE = $eqLogic->getConfiguration('IEEE','None');
            if ( ($IEEE!=$value) && (strlen($IEEE)==16) ) {
                log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';Alerte changement de l adresse IEEE pour un equipement !!! '.$addr.": ".$IEEE." =>".$value."<=");
                message::add("Abeille", "Alerte changement de l adresse IEEE pour un equipement !!! ( $addr : $IEEE =>$value<= )", '');
            }

            $eqLogic->checkAndUpdateCmd($cmdLogic, $value);
            $eqLogic->setConfiguration('IEEE', $value);
            $eqLogic->save();
            $eqLogic->refresh();

            log::add('Abeille', 'debug', '  IEEE-Addr cmd and eq updated: '.$eqLogic->getName().' - '.$eqLogic->getConfiguration('IEEE', 'Unknown') );

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Tcharp38: No longer required. MACCap is collected during device announce and passed thru "eqAnnounce" msg (treated by msgFromParser())
        // // Si l objet exist et on recoie une MACCapa
        // // e.g. Un NE renvoie son annonce
        // if (is_object($eqLogic) && ($cmdId == "MACCapa-MACCapa")) {

        //     log::add('Abeille', 'debug', 'MACCapa-MACCapa: '.$value.' saving into eqlogic');

        //     $mc = hexdec($value);
        //     $rxOnWhenIdle = ($mc >> 3) & 0b1;
        //     $powerSource = ($mc >> 2) & 0b1;

        //     $eqLogic->setConfiguration('MACCapa', $value);
        //     if ($powerSource) // 1=mains-powererd
        //         $eqLogic->setConfiguration('AC_Power', 1);
        //     else
        //         $eqLogic->setConfiguration('AC_Power', 0);
        //     if ($rxOnWhenIdle) // 1=Receiver enabled when idle
        //         $eqLogic->setConfiguration('RxOnWhenIdle', 1);
        //     else
        //         $eqLogic->setConfiguration('RxOnWhenIdle', 0);
        //     $eqLogic->save();
        //     $eqLogic->refresh();

        //     return;
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si equipement et cmd existe alors on met la valeur a jour
        if (is_object($eqLogic) && is_object($cmdLogic)) {
            /* Traitement particulier pour les batteries */
            if ($cmdId == "Batterie-Volt") {
                /* Volt en milli V. Max a 3,1V Min a 2,7V, stockage en % batterie */
                // Tcharp38: To be removed. Should not be systematic. Device may report percent too */
                $eqLogic->setStatus('battery', self::volt2pourcent($value));
                $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }
            else if (($cmdId == "Battery-Percent") || ($cmdId == "Batterie-Pourcent")) {
                log::add('Abeille', 'debug', "  Battery % reporting: ".$cmdId.", val=".$value);
                $eqLogic->setStatus('battery', $value);
                $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }
            // Tcharp38: Correct % value directly reported by parser according to zigbee spec (raw value / 2)
            // if ($cmdId == "0001-01-0021") {
            //     /* en % batterie example Ikea Remote */
            //     // 10.10.2.1   BatteryPercentageRemaining Attribute Specifies the remaining battery life as a half integer percentage of the full battery capacity (e.g.
            //     // 34.5%, 45%, 68.5%, 90%) with a range between zero and 100%, with 0x00 = 0%, 0x64 = 50%, and 0xC8 = 100%. This is particularly suited for devices with
            //     // rechargeable batteries. The value 0xff indicates an invalid or unknown reading. This attribute SHALL be configurable for attribute reporting.
            //     // C8 is 200, so value/200*100
            //     $eqLogic->setStatus('battery', $value / 2);
            //     $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            // }
            else if (preg_match("/^0001-[0-9A-F]*-0021/", $cmdId)) {
                log::add('Abeille', 'debug', "  Battery % reporting: ".$cmdId.", val=".$value);
                $eqLogic->setStatus('battery', $value);
                $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }

            /* Traitement particulier pour la remontée de nom qui est utilisé pour les ping des routeurs */
            // if (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) {
            // if (preg_match("/^0000-[0-9A-F]*-*0005/", $cmdId) || preg_match("/^0000-[0-9A-F]*-*0010/", $cmdId)) {
            else if ($cmdId == "Time-TimeStamp") {
                // log::add('Abeille', 'debug', "  Updating 'online' status for '".$dest."/".$addr."'");
                $cmdLogicOnline = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'online');
                $eqLogic->checkAndUpdateCmd($cmdLogicOnline, 1);
            }

            // Traitement particulier pour rejeter certaines valeurs
            // exemple: le Xiaomi Wall Switch 2 Bouton envoie un On et un Off dans le même message donc Abeille recoit un ON/OFF consecutif et
            // ne sais pas vraiment le gérer donc ici on rejete le Off et on met un retour d'etat dans la commande Jeedom
            if ($cmdLogic->getConfiguration('AbeilleRejectValue', -9999.99) == $value) {
                log::add('Abeille', 'debug', 'Rejet de la valeur: '.$cmdLogic->getConfiguration('AbeilleRejectValue', -9999.99).' - '.$value);

                return;
            }

            $eqLogic->checkAndUpdateCmd($cmdLogic, $value);

            Abeille::infoCmdUpdate($eqLogic, $cmdLogic, $value);

            // // Polling to trigger based on this info cmd change: e.g. state moved to On, getPower value.
            // $cmds = AbeilleCmd::searchConfigurationEqLogic($eqLogic->getId(), 'PollingOnCmdChange', 'action');
            // foreach ($cmds as $key => $cmd) {
            //     if ($cmd->getConfiguration('PollingOnCmdChange') == $cmdId) {
            //         log::add('Abeille', 'debug', 'Cmd action execution: '.$cmd->getName());
            //         // $cmd->execute(); si j'envoie la demande immediatement le device n a pas le temps de refaire ses mesures et repond avec les valeurs d avant levenement
            //         // Je vais attendre qq secondes aveant de faire la demande
            //         // Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000" );
            //         Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$cmd->getEqLogic()->getLogicalId()."/".$cmd->getLogicalId()."&time=".(time() + $cmd->getConfiguration('PollingOnCmdChangeDelay')), $cmd->getConfiguration('request'));
            //     }
            // }
            return;
        }

        if (is_object($eqLogic) && !is_object($cmdLogic)) {
            log::add('Abeille', 'debug', "  L'objet '".$nodeid."' existe mais pas la cmde '".$cmdId."' => message ignoré");
            return;
        }

        log::add('Abeille', 'debug', "  WARNING: Unexpected state at end of message().");
        return;
    } // End message()

    /* Trig another command defined by 'trigLogicId'.
       The 'newValue' is computed with 'trigOffset' if required then applied to 'trigLogicId' */
    public static function trigCommand($eqLogic, $newValue, $trigLogicId, $trigOffset = null) {
        if ($trigOffset)
            $trigValue = jeedom::evaluateExpression(str_replace('#value#', $newValue, $trigOffset));
        else
            $trigValue = $newValue;

        $trigCmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $trigLogicId);
        if ($trigCmd) {
            log::add('Abeille', 'debug', "  Triggering cmd '".$trigLogicId."' with val=".$trigValue);
            $eqLogic->checkAndUpdateCmd($trigCmd, $trigValue);
        }

        if (preg_match("/^0001-[0-9A-F]*-0021/", $trigLogicId)) {
            log::add('Abeille', 'debug', "  Battery % reporting: ".$trigLogicId.", val=".$trigValue);
            $eqLogic->setStatus('battery', $trigValue);
            $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        }
    }

    /* Called on info cmd update (attribute report or attribute read) to see if any action cmd must be executed */
    public static function infoCmdUpdate($eqLogic, $cmdLogic, $value) {
        global $abQueues;

        // Trig another command ('ab::trigOut' eqLogic config) ?
        $trigLogicId = $cmdLogic->getConfiguration('ab::trigOut');
        if ($trigLogicId) {
            $trigOffset = $cmdLogic->getConfiguration('ab::trigOutOffset');
            Abeille::trigCommand($eqLogic, $cmdLogic->execCmd(), $trigLogicId, $trigOffset);
        }

        // Trig another command (PollingOnCmdChange keyword) ?
        $cmds = cmd::searchConfigurationEqLogic($eqLogic->getId(), 'PollingOnCmdChange', 'action');
        $cmdLogicId = $cmdLogic->getLogicalId();
        foreach ($cmds as $cmd) {
            if ($cmd->getConfiguration('PollingOnCmdChange', '') != $cmdLogicId)
                continue;
            $delay = $cmd->getConfiguration('PollingOnCmdChangeDelay', '');
            if ($delay != 0) {
                log::add('Abeille', 'debug', "  Triggering '".$cmd->getName()."' with delay ".$delay);
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".(time() + $delay), $cmd->getConfiguration('request'));
            } else {
                log::add('Abeille', 'debug', "  Triggering '".$cmd->getName()."'");
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".time(), $cmd->getConfiguration('request'));
            }
        }
    }

    /* Deal with messages coming from parser.
       Note: this is the new way to handle messages from parser, replacing progressively 'message()' */
    public static function msgFromParser($msg) {
        global $abQueues;

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
                    'modelId' =>
                    'manufId' =>
                    'jsonId' => $eq['jsonId'], // JSON identifier
                    'jsonLocation' => '', // 'Abeille' or 'local'
                    'capa' => $eq['capa'],
                    'time' => time()
                ); */

            $logicalId = $net.'/'.$addr;
            $jsonId = $msg['jsonId'];
            $jsonLocation = $msg['jsonLocation']; // 'Abeille' or 'local'
            log::add('Abeille', 'debug', "msgFromParser(): Eq announce received for ".$net.'/'.$addr.", jsonId='".$jsonId."'".", jsonLoc='".$jsonLocation."'");

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
            // Abeille::createDevice("create", $net, $addr, $ieee, $jsonId, $jsonLocation);
            $dev = array(
                'net' => $net,
                'addr' => $addr,
                'ieee' => $ieee,
                'modelId' => $msg['modelId'],
                'manufId' => $msg['manufId'],
                'jsonId' => $jsonId,
                'jsonLocation' => $jsonLocation,
            );
            Abeille::createDevice("create", $dev);

            $eqLogic = self::byLogicalId($logicalId, 'Abeille');

            /* MAC capa */
            $mc = hexdec($msg['capa']);
            $rxOnWhenIdle = ($mc >> 3) & 0b1;
            $mainsPowered = ($mc >> 2) & 0b1;
            $eqLogic->setConfiguration('MACCapa', $msg['capa']);
            if ($mainsPowered) // 1=mains-powererd
                $eqLogic->setConfiguration('AC_Power', 1);
            else
                $eqLogic->setConfiguration('AC_Power', 0);
            if ($rxOnWhenIdle) // 1=Receiver enabled when idle
                $eqLogic->setConfiguration('RxOnWhenIdle', 1);
            else
                $eqLogic->setConfiguration('RxOnWhenIdle', 0);
            $eqLogic->save();

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "Short-Addr");
            if (is_object($cmdLogic))
                $ret = $eqLogic->checkAndUpdateCmd($cmdLogic, $addr);
            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "IEEE-Addr");
            if (is_object($cmdLogic))
                $eqLogic->checkAndUpdateCmd($cmdLogic, $ieee);
            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "Mains-Powered");
            if (!is_object($cmdLogic))
                $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "Power-Source"); // Old name support
            if (is_object($cmdLogic))
                $eqLogic->checkAndUpdateCmd($cmdLogic, $mainsPowered);

            return;
        } // End 'eqAnnounce'

        /* Parser has received a "leave indication" */
        if ($msg['type'] == "leaveIndication") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'leaveIndication',
                'net' => $dest,
                'ieee' => $IEEE,
                'rejoin' => $RejoinStatus,
                'time' => time(),
                'lqi' => $lqi
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

                Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            } else
                log::add('Abeille', 'debug', 'msgFromParser(): WARNING: Device with IEEE '.$ieee.' NOT found in Jeedom');

            return;
        } // End 'leaveIndication'

        /* Attribute report (8100 & 8102 responses) */
        if ($msg['type'] == "attributeReport") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'attributeReport',
                'net' => $net,
                'addr' => $addr,
                'ep' => 'xx', // End point hex string
                'name' => 'xxx', // Attribut name = cmd logical ID
                'value' => false, // False = unsupported
                'time' => time(),
                'lqi' => $lqi
            */

            log::add('Abeille', 'debug', "msgFromParser(): Attribute report from '".$net."/".$addr."/".$ep."': Attr='".$msg['name']."', Val='".$msg['value']."'");
            $eqLogic = self::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  Unknown device '".$net."/".$addr."'");
                return; // Unknown device
            }

            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $msg['name']);
            if (!is_object($cmdLogic))
                log::add('Abeille', 'debug', "  Unknown Jeedom command '".$msg['name']."'");
            else {
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['value']);

                // Check if any action cmd must be executed triggered by this update
                Abeille::infoCmdUpdate($eqLogic, $cmdLogic, $msg['value']);
            }

            Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'attributeReport'

        // Grouped attributes (Jeedom logical name) reporting
        if ($msg['type'] == "attributesReportN") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'attributesReportN',
                'net' => $net,
                'addr' => $addr,
                'ep' => 'xx', // End point hex string
                'attributes' => [],
                    'name' => 'xxx', // Attribut name = cmd logical ID
                    'value' => false, // False = unsupported
                'time' => time(),
                'lqi' => $lqi
            */

            log::add('Abeille', 'debug', "msgFromParser(): Attributes report by name from '".$net."/".$addr."/".$ep);
            $eqLogic = self::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  Unknown device '".$net."/".$addr."'");
                return; // Unknown device
            }

            foreach ($msg['attributes'] as $attr) {
                $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $attr['name']);
                if (!is_object($cmdLogic)) {
                    log::add('Abeille', 'debug', "  Unknown Jeedom command '".$attr['name']."'");
                    return; // Unknown command
                }
                log::add('Abeille', 'debug', "  ".$attr['name']." => ".$attr['value']);
                $eqLogic->checkAndUpdateCmd($cmdLogic, $attr['value']);

                // Check if any action cmd must be executed triggered by this update
                Abeille::infoCmdUpdate($eqLogic, $cmdLogic, $attr['value']);
            }

            Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'attributesReportN'

        /* Grouped attributes read or report (8002 response, cmds 01 or 0A) */
        if (($msg['type'] == "reportAttributes") || ($msg['type'] == "readAttributesResponse")) {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'reportAttributes', // or 'readAttributesResponse'
                'net' => $dest,
                'addr' => $srcAddr,
                'ep' => $srcEp,
                'clustId' => $cluster,
                'attributes' => $attributes,
                'time' => time(),
                'lqi' => $lqi,
            */

            log::add('Abeille', 'debug', "msgFromParser(): Attributes report from '".$net."/".$addr."/".$ep);
            $eqLogic = self::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  Unknown device ".$net.'/'.$addr);
                return; // Unknown device
            }
            $clustId = $msg['clustId'];
            foreach ($msg['attributes'] as $attrId => $attr) {
                log::add('Abeille', 'debug', "  ClustId=".$clustId.", AttrId='".$attrId."', Val='".$attr['value']."'");

                $cmdName = $clustId.'-'.$ep.'-'.$attrId;
                $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdName);
                if (!is_object($cmdLogic)) {
                    log::add('Abeille', 'debug', "  Unknown command logicalid ".$cmdName);
                    return; // Unknown command
                }
                $eqLogic->checkAndUpdateCmd($cmdLogic, $attr['value']);

                // Check if any action cmd must be executed triggered by this update
                Abeille::infoCmdUpdate($eqLogic, $cmdLogic, $attr['value']);
            }

            Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'reportAttributes' or 'readAttributesResponse'

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
            if ($cmdLogic) {
                $curVersion = $cmdLogic->execCmd();
                if ($curVersion != $msg['minor'])
                    log::add('Abeille', 'debug', '  FW cur version: '.$curVersion);
                if ($curVersion == '----') {
                    $zgId = substr($net, 7);
                    if (hexdec($msg['minor']) >= 0x031D) {
                        log::add('Abeille', 'debug', '  FW version >= 3.1D => Configuring zigate '.$zgId.' in hybrid mode');
                        Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/setZgMode", "mode=hybrid");
                    } else {
                        log::add('Abeille', 'debug', '  Old FW. Configuring zigate '.$zgId.' in normal mode');
                        Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/setZgMode", "mode=normal");
                    }
                    if (hexdec($msg['minor']) < 0x031E)
                        message::add('Abeille', 'Attention: La zigate '.$zgId.' fonctionne avec un vieux FW. Une version >= 3.1E est requise pour un fonctionnement optimal d\'Abeille.');
                }
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['minor']);
            }

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        }

        /* Zigate time (8017 response) */
        if ($msg['type'] == "zigateTime") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'zigateTime',
                'net' => $dest,
                'timeServer' => $data,
                'time' => time()
             */

            log::add('Abeille', 'debug', "msgFromParser(): ".$net.", Zigate timeServer ".$msg['time']);
            $eqLogic = self::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'ZiGate-Time');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['timeServer']);

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        }

        /* Zigate TX power (8806/8807 responses) */
        if ($msg['type'] == "zigatePower") {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'zigatePower',
                'net' => $dest,
                'power' => $power,
                'time' => time()
             */

            log::add('Abeille', 'debug', "msgFromParser(): ".$net.", Zigate power ".$msg['power']);
            $eqLogic = self::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'ZiGate-Power');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['power']);

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
            // $ieee = $eqLogic->getConfiguration('IEEE', '');
            // if (($ieee != '') && ($ieee != $msg['ieee'])) {
            //     log::add('Abeille', 'debug', "  ERROR: IEEE mistmatch, got ".$msg['ieee']." while expecting ".$ieee);
            //     return;
            // }
            $eqLogic->setConfiguration('IEEE', $msg['ieee']);
            $eqLogic->save();

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

        /* Network started (8024 response) */
        if ($msg['type'] == "networkStarted") {
            /* Reminder
            $msg = array(
                'src' => 'parser',
                'type' => 'networkStarted',
                'net' => $dest,
                'status' => $status,
                'statusTxt' => $data,
                'addr' => $dataShort, // Should be always 0000
                'ieee' => $dataIEEE, // Zigate IEEE
                'chan' => $dataNetwork,
                'time' => time()
            ); */

            log::add('Abeille', 'debug', "msgFromParser(): ".$net.", network started, ieee=".$msg['ieee'].", chan=".$msg['chan']);
            $eqLogic = self::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $ieee = $eqLogic->getConfiguration('IEEE', '');
            if (($ieee != '') && ($ieee != $msg['ieee'])) {
                log::add('Abeille', 'debug', "  ERROR: IEEE mistmatch, got ".$msg['ieee']." while expecting ".$ieee);
                return;
            }
            $eqId = $eqLogic->getId();
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Network-Status');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['statusTxt']);
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

        /* Bind response (8030 response) */
        if ($msg['type'] == "bindResponse") {
            // 'src' => 'parser',
            // 'type' => 'bindResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'status' => $status,
            // 'time' => time(),
            // 'lqi' => $lqi,
            log::add('Abeille', 'debug', "msgFromParser(): ".$net."/".$addr.", Bind response, status=".$msg['status']);

            $eqLogic = self::byLogicalId($net.'/'.$addr, 'Abeille');
            if ($eqLogic)
                Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);

            return;
        }

        /* IEEE address response (8041 response) */
        if ($msg['type'] == "ieeeAddrResponse") {
            // 'type' => 'ieeeAddrResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ieee' => $ieee,
            // 'time' => time(),
            // 'lqi' => $lqi,
            log::add('Abeille', 'debug', "msgFromParser(): ".$net."/".$addr.", IEEE addr response, ieee=".$msg['ieee']);

            $eqLogic = self::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!$eqLogic) {
                log::add('Abeille', 'debug', "  WARNING: Unknown device");
                return;
            }
            $curIeee = $eqLogic->getConfiguration('IEEE', '');
            if ($curIeee == '') {
                $eqLogic->setConfiguration('IEEE', $msg['ieee']);
                $eqLogic->save();
                $eqLogic->refresh();
                log::add('Abeille', 'debug', "  Device IEEE updated.");
            } else if ($curIeee != $msg['ieee']) {
                log::add('Abeille', 'debug', "  WARNING: Device has a different IEEE => UNEXPECTED !!");
            }

            Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'ieeeAddrResponse'

        log::add('Abeille', 'debug', "msgFromParser(): Ignored msg type '".$msg['type']."'");
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

        // $config = AbeilleTools::getParameters();

        $msg = array();
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;

        if (msg_send($queue, $priority, $msg, true, false, $error_code)) {
            log::add('Abeille', 'debug', "  publishMosquitto(): Envoyé '".json_encode($msg)."' vers queue ".$queueId);
            $queueStatus[$queueId] = "ok"; // Status ok
        } else
            log::add('Abeille', 'warning', "publishMosquitto(): Impossible d'envoyer '".json_encode($msg)."' vers queue ".$queueId);
    } // End publishMosquitto()

    // Beehive creation/update function. Called on daemon startup or new beehive creation.
    public static function createRuche($dest)
    {
        $eqLogic = self::byLogicalId($dest."/0000", 'Abeille');
        if (!is_object($eqLogic)) {
            message::add("Abeille", "Création de l'équipement 'Ruche' en cours. Rafraichissez votre dashboard dans qq secondes.", '');
            log::add('Abeille', 'info', 'Ruche: Création de '.$dest."/0000");
            $eqLogic = new Abeille();
            //id
            $eqLogic->setName("Ruche-".$dest);
            $eqLogic->setLogicalId($dest."/0000");
            $config = AbeilleTools::getParameters();
            if ($config['AbeilleParentId'] > 0) {
                $eqLogic->setObject_id($config['AbeilleParentId']);
            } else {
                $eqLogic->setObject_id(jeeObject::rootObject()->getId());
            }
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setConfiguration('topic', $dest."/0000");
            // $eqLogic->setConfiguration('type', 'topic'); // Tcharp38: What for ?
            $eqLogic->setConfiguration('lastCommunicationTimeOut', '-1');
            $eqLogic->setIsVisible("0");
            $eqLogic->setConfiguration('icone', "Ruche");
            $eqLogic->setTimeout(5); // timeout en minutes
            $eqLogic->setIsEnable("1");
        } else {
            // TODO: If already exist, should we update commands if required ?
            log::add('Abeille', 'debug', "createRuche(): '".$eqLogic->getLogicalId()."' already exists");
        }

        $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $eqLogic->save();

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
                        "configuration" => array(
                            "topic" => "CmdCreate/".$objetId."/0000-0005",
                            "request" => $objetId,
                            "visibilityCategory" => "additionalCommand",
                            "visibiltyTemplate" => "0"
                        ),
                    );
                }
            }
            // print_r($rucheCommandList);
        }

        // Removing obsolete commands by their logical ID (unique)
        $cmds = Cmd::byEqLogicId($eqLogic->getId());
        foreach ($cmds as $cmdLogic) {
            $found = false;
            $cmdName = $cmdLogic->getName();
            $cmdLogicId = $cmdLogic->getLogicalId();
            foreach ($rucheCommandList as $cmdLogicId2 => $cmdValueDefaut) {
                if ($cmdLogicId == $cmdLogicId2) {
                    $found = true;
                    break; // Listed in JSON
                }
            }
            if ($found == false) {
                log::add('Abeille', 'debug', "  Removing cmd '".$cmdName."' => '".$cmdLogicId."'");
                $cmdLogic->remove(); // No longer required
            }
        }

        // Creating/updating beehive commands
        $order = 0;
        foreach ($rucheCommandList as $cmdLogicId => $cmdValueDefaut) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdLogicId);
            if (!$cmdLogic) {
                $cmdJName = $cmdValueDefaut["name"]; // Jeedom cmd name
                log::add('Abeille', 'debug', "  Adding cmd '".$cmdJName."' => '".$cmdLogicId."'");
                $cmdLogic = new AbeilleCmd();
                $cmdLogic->setEqLogic_id($eqLogic->getId());
                $cmdLogic->setEqType('Abeille');
                $cmdLogic->setLogicalId($cmdLogicId);
                $cmdLogic->setName($cmdJName);
                $newCmd = true;
            } else {
                $cmdJName = $cmdLogic->getName();
                log::add('Abeille', 'debug', "  Updating cmd '".$cmdJName."' => '".$cmdLogicId."'");
                $newCmd = false;
            }

            $cmdLogic->setOrder($order++); // New or update

            if ($cmdValueDefaut["Type"] == "action") {
                // $cmdLogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd);
                $cmdLogic->setConfiguration('topic', $cmdLogicId);

                // Tcharp38: work in progress. Adding support for linked commands
                // Note: Error if info cmd is not registered BEFORE action cmd.
                // if (isset($cmdValueDefaut["value"])) {
                //     // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                //     log::add('Abeille', 'debug', 'Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$cmdValueDefaut["value"]);

                //     $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $cmdValueDefaut["value"]);
                //     $cmdLogic->setValue($cmdPointeur_Value->getId());
                // }
            } else {
                // $cmdLogic->setConfiguration('topic', $nodeid.'/'.$cmd);
                $cmdLogic->setConfiguration('topic', $cmdLogicId);
            }
            // if ($cmdValueDefaut["Type"] == "action") {  // not needed as mosquitto is not used anymore
            //    $cmdLogic->setConfiguration('retain', '0');
            // }
            if (isset($cmdValueDefaut["configuration"])) {
                foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                    $cmdLogic->setConfiguration($confKey, $confValue);
                }
            }
            $cmdLogic->setType($cmdValueDefaut["Type"]);
            $cmdLogic->setSubType($cmdValueDefaut["subType"]);

            // Todo only if new command
            if ($newCmd) {
                if (isset($cmdValueDefaut["isHistorized"])) $cmdLogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                if (isset($cmdValueDefaut["template"])) $cmdLogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
                if (isset($cmdValueDefaut["template"])) $cmdLogic->setTemplate('mobile', $cmdValueDefaut["template"]);
                if (isset($cmdValueDefaut["invertBinary"])) $cmdLogic->setDisplay('invertBinary', '0');
                if (isset($cmdValueDefaut["isVisible"])) $cmdLogic->setIsVisible($cmdValueDefaut["isVisible"]);
                if (isset($cmdValueDefaut["display"])) {
                    foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                        // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                        $cmdLogic->setDisplay($confKey, $confValue);
                    }
                }
            }

            // Whatever existing or new beehive, it is key to reset the following points
            if ($cmdLogicId == 'SW-SDK')
                // $cmdLogic->setValue('----'); // Indicate FW version is invalid
                $cmdLogic->setCache('value', '----'); // Indicate FW version is invalid

            $cmdLogic->save();
        }
    } // End createRuche()

    /* Create or update Jeedom device based on its JSON config.
       Called in the following cases
       - On 'eqAnnounce' message from parser (device announce) => action = 'create'
       - To create a virtual 'remotecontrol' => action = 'create'
       - To reload JSON & update commands (EQ page/advanced/reload JSON) => action = 'update'
       - To reset from JSON (identical to new inclusion) => action = 'reset'
     */
    public static function createDevice($action, $dev) {
        log::add('Abeille', 'debug', 'createDevice('.$action.', dev='.json_encode($dev));

        $logicalId = $dev['net'].'/'.$dev['addr'];

        $jsonId = (isset($dev['jsonId']) ? $dev['jsonId']: '');
        $jsonLocation = (isset($dev['jsonLocation']) ? $dev['jsonLocation']: '');
        if ($jsonId != '' && $jsonLocation != '') {
            $deviceConfig = AbeilleTools::getDeviceConfig($jsonId, $jsonLocation);
            $eqType = $deviceConfig['type'];
        }

        $eqLogic = self::byLogicalId($logicalId, 'Abeille');
        if (!is_object($eqLogic)) {
            $newEq = true;

            if (($action == 'update') || ($action == 'reset')) { // Update or reset from JSON
                log::add('Abeille', 'debug', '  ERROR: Action='.$action.' but device '.$logicalId.' does not exist');
                return;
            }

            // $action == 'create'
            log::add('Abeille', 'debug', '  New device '.$logicalId);
            if ($jsonId != "defaultUnknown")
                message::add("Abeille", "Nouvel équipement identifié (".$eqType."). Création en cours. Rafraîchissez votre dashboard dans qq secondes.", '');
            else
                message::add("Abeille", "Nouvel équipement détecté mais non supporté. Création en cours avec la config par défaut (".$jsonId."). Rafraîchissez votre dashboard dans qq secondes.", '');

            $eqLogic = new Abeille();
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setName("newDevice-".$dev['addr']); // Temp name to have it non empty
            $eqLogic->save(); // Save to force Jeedom to assign an ID

            $eqName = $dev['net']."-".$eqLogic->getId(); // Default name (ex: 'Abeille1-12')
            $eqLogic->setName($eqName);
            $eqLogic->setLogicalId($logicalId);
            $abeilleConfig = AbeilleTools::getParameters();
            $eqLogic->setObject_id($abeilleConfig['AbeilleParentId']);
            $eqLogic->setConfiguration('IEEE', $dev['ieee']);
        } else {
            $newEq = false;
            $eqName = $eqLogic->getName();
            $eqHName = $eqLogic->getHumanName(); // Jeedom hierarchical name
            log::add('Abeille', 'debug', '  Already existing device '.$logicalId.' => '.$eqHName);

            if (($action == 'update') || ($action == 'reset')) { // Update or reset from JSON
                $jsonId = $eqLogic->getConfiguration('ab::jsonId');
                $jsonLocation = $eqLogic->getConfiguration('ab::jsonLocation', 'Abeille');
                $ieee = $eqLogic->getConfiguration('IEEE');
                if ($action == "update")
                    message::add("Abeille", "Mise-à-jour de '".$eqHName."' à partir de son modèle JSON");
                else
                    message::add("Abeille", "Réinitialisation de '".$eqHName."' à partir de son modèle JSON");
                $deviceConfig = AbeilleTools::getDeviceConfig($jsonId, $jsonLocation);
            } else { // action == create
                $eqCurJsonId = $eqLogic->getConfiguration('ab::jsonId'); // Current JSON ID
                if (($eqCurJsonId == 'defaultUnknown') && ($jsonId != 'defaultUnknown'))
                    message::add("Abeille", "'".$eqHName."' s'est réannoncé. Mise-à-jour de la config par défaut vers '".$eqType."'", '');
                else {
                    /* Tcharp38: Following https://github.com/KiwiHC16/Abeille/issues/2132#, device re-announce is just ignored here
                        to not generate plenty messages, unless device was disabled.
                        Other reasons to generate message ?
                    */
                    if ($eqLogic->getIsEnable() == 1) {
                        log::add('Abeille', 'debug', '  Device is already enabled. Doing nothing.');
                        return; // Doing nothing on re-announce
                    }
                    message::add("Abeille", "'".$eqHName."' s'est réannoncé. Mise-à-jour en cours.", '');
                }
            }
        }
        if ($jsonLocation != "Abeille") {
            $fullPath = __DIR__."/../config/devices/".$jsonId."/".$jsonId.".json";
            if (file_exists($fullPath))
                message::add("Abeille", "ATTENTION: Config locale (devices_local) utilisée alors qu'une config officielle existe.", '');
        }

        /* Whatever creation or update, common steps follows */
        $objetConfiguration = $deviceConfig["configuration"];
        log::add('Abeille', 'debug', '  config='.json_encode($objetConfiguration));

        /* mainEP: Used to define default end point to target, when undefined in command itself (use of '#EP#'). */
        if (isset($objetConfiguration['mainEP'])) {
            $mainEP = $objetConfiguration['mainEP'];
        } else {
            log::add('Abeille', 'debug', '  WARNING: Undefined mainEP => defaulting to 01');
            $mainEP = "01";
        }
        $eqLogic->setConfiguration('mainEP', $mainEP);

        if (isset($dev['modelId'])) {
            $sig = array(
                'modelId' => $dev['modelId'],
                'manufId' => $dev['manufId'],
            );
            $eqLogic->setConfiguration('ab::signature', $sig);
        }
        $eqLogic->setConfiguration('ab::jsonId', $jsonId);
        if ($jsonLocation != "Abeille")
            $eqLogic->setConfiguration('ab::jsonLocation', 'local');
        else
            $eqLogic->setConfiguration('ab::jsonLocation', null);

        // $eqLogic->setConfiguration('type', 'topic'); // ??, type = topic car pas json. Tcharp38: what for ?

        if (($action == 'reset') || $newEq) { // Update icon only if new device
            if (isset($objetConfiguration["icon"]))
                $icon = $objetConfiguration["icon"];
            else
                $icon = '';
            $eqLogic->setConfiguration('icone', $icon);
        }

        $lastCommTimeout = (array_key_exists("lastCommunicationTimeOut", $objetConfiguration) ? $objetConfiguration["lastCommunicationTimeOut"] : '-1');
        $eqLogic->setConfiguration('lastCommunicationTimeOut', $lastCommTimeout);

        if (isset($objetConfiguration['batteryType']))
            $eqLogic->setConfiguration('battery_type', $objetConfiguration['batteryType']);
        else
            $eqLogic->setConfiguration('battery_type', null);

        if (isset($objetConfiguration['paramType']))
            $eqLogic->setConfiguration('paramType', $objetConfiguration['paramType']);
        if (isset($objetConfiguration['Groupe'])) { // Tcharp38: What for ? Telecommande Innr - KiwiHC16: on doit pouvoir simplifier ce code. Mais comme c etait la premiere version j ai fait detaillé.
            $eqLogic->setConfiguration('Groupe', $objetConfiguration['Groupe']);
        }
        if (isset($objetConfiguration['GroupeEP1'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP1', $objetConfiguration['GroupeEP1']);
        }
        if (isset($objetConfiguration['GroupeEP3'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP3', $objetConfiguration['GroupeEP3']);
        }
        if (isset($objetConfiguration['GroupeEP4'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP4', $objetConfiguration['GroupeEP4']);
        }
        if (isset($objetConfiguration['GroupeEP5'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP5', $objetConfiguration['GroupeEP5']);
        }
        if (isset($objetConfiguration['GroupeEP6'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP6', $objetConfiguration['GroupeEP6']);
        }
        if (isset($objetConfiguration['GroupeEP7'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP7', $objetConfiguration['GroupeEP7']);
        }
        if (isset($objetConfiguration['GroupeEP8'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('GroupeEP8', $objetConfiguration['GroupeEP8']);
        }
        if (isset($objetConfiguration['onTime'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('onTime', $objetConfiguration['onTime']);
        }
        // if (isset($objetConfiguration['Zigate'])) { // Tcharp38: Seems to be unused
        //     $eqLogic->setConfiguration('Zigate', $objetConfiguration['Zigate']);
        // }
        // if (isset($objetConfiguration['protocol'])) { // Tcharp38: Seems to be unused
        //     $eqLogic->setConfiguration('protocol', $objetConfiguration['protocol']);
        // }
        if (isset($objetConfiguration['poll'])) {
            $eqLogic->setConfiguration('poll', $objetConfiguration['poll']);
        }

        if (($action == 'reset') || $newEq) { // Update visibility only if new device
            if (isset($deviceConfig["isVisible"]))
                $eqLogic->setIsVisible($deviceConfig["isVisible"]);
            else
                $eqLogic->setIsVisible(1);
        }
        $eqLogic->setIsEnable(1);
        if (isset($deviceConfig["timeout"]))
            $eqLogic->setTimeout($deviceConfig["timeout"]);

        if (($action == 'reset') || ($newEq && isset($deviceConfig["category"]))) { // Update category only if new device
            $categories = $deviceConfig["category"];
            // $eqLogic->setCategory(array_keys($deviceConfig["Categorie"])[0], $deviceConfig["Categorie"][array_keys($objetDefSpecific["Categorie"])[0]]);
            $allCat = ["heating", "security", "energy", "light", "opening", "automatism", "multimedia", "default"];
            foreach ($allCat as $cat) { // Clear all
                $eqLogic->setCategory($cat, "0");
            }
            foreach ($categories as $key => $value) {
                $eqLogic->setCategory($key, $value);
            }
        }

        // $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s')); // Tcharp38: Done by updateTimestamp()
        $eqLogic->save();

        /* During commands creation #EP# must be replaced by proper endpoint.
           If not already done, using default (mainEP) value */
        if (isset($deviceConfig['commands'])) {
            $jsonCmds = $deviceConfig['commands'];
            $jsonCmds2 = json_encode($jsonCmds);
            if (strstr($jsonCmds2, '#EP#') !== false) {
                if ($mainEP == "") {
                    message::add("Abeille", "'mainEP' est requis mais n'est pas défini dans '".$jsonId.".json'", '');
                    $mainEP = "01";
                }

                log::add('Abeille', 'debug', '  mainEP='.$mainEP);
                $jsonCmds2 = str_ireplace('#EP#', $mainEP, $jsonCmds2);
                $jsonCmds = json_decode($jsonCmds2, true);
                log::add('Abeille', 'debug', '  Updated commands='.json_encode($jsonCmds));
            }
        }

        /* Removing obsolete commands, not listed in JSON.
           Might be needed for ex if device was previously 'defaultUnknown'. */
        $cmds = Cmd::byEqLogicId($eqLogic->getId());
        foreach ($cmds as $cmdLogic) {
            $found = false;
            $cmdName = $cmdLogic->getName();
            foreach ($jsonCmds as $cmdKey => $cmdValueDefaut) {
                $cmdJName = $cmdKey; // Jeedom command name
                if ($cmdName == $cmdJName) {
                    $found = true;
                    break; // Listed in JSON
                }
            }
            if ($found == false) {
                log::add('Abeille', 'debug', "  Removing cmd '".$cmdName."'");
                $cmdLogic->remove(); // No longer required
            }
        }

        /* Creating or updating commands. */
        $order = 0;
        foreach ($jsonCmds as $cmdKey => $cmdValueDefaut) {
            $cmdJName = $cmdKey; // Jeedom command name
            if ($cmdValueDefaut["type"] == "info")
                $type = "info";
            else if ($cmdValueDefaut["type"] == "action")
                $type = "action";
            else {
                log::add('Abeille', 'error', "La commande '".$cmdJName."' (fichier ".$cmdKey.".json) n'a pas de type défini => ignorée");
                break;
            }

            // $cmdJName = $cmdValueDefaut["name"]; // Jeedom command name
            if ($type == "info")
                $cmdAName = $cmdValueDefaut["logicalId"]; // Abeille command name
            else
                $cmdAName = $cmdValueDefaut["configuration"]['topic']; // Abeille command name
            if (isset($cmdValueDefaut["configuration"]['request']))
                $cmdAParams = $cmdValueDefaut["configuration"]['request']; // Abeille command params
            else
                $cmdAParams = '';

            /* New or existing cmd ? */
            $cmdLogic = AbeilleCmd::byEqLogicIdCmdName($eqLogic->getId(), $cmdJName);
            if (!is_object($cmdLogic)) {
                $newCmd = true;
                log::add('Abeille', 'debug', "  Adding cmd '".$cmdJName."' => '".$cmdAName."', '".$cmdAParams."'");
                $cmdLogic = new AbeilleCmd();
            } else {
                $newCmd = false;
                log::add('Abeille', 'debug', "  Updating cmd '".$cmdJName."' => '".$cmdAName."', '".$cmdAParams."'");
            }

            $cmdLogic->setEqLogic_id($eqLogic->getId());
            $cmdLogic->setEqType('Abeille');
            // Tcharp38: Cmds now created in order of declarations in device JSON.
            // Does not make sense to be defined in cmd itself since can be reused by different device.
            // if (isset($cmdValueDefaut["order"]))
            //     $cmdLogic->setOrder($cmdValueDefaut["order"]);
            $cmdLogic->setOrder($order++);
            $cmdLogic->setName($cmdJName);

            if (isset($cmdValueDefaut["logicalId"])) // Mandatory for info cmds
                $cmdLogic->setLogicalId($cmdValueDefaut["logicalId"]);
            else
                $cmdLogic->setLogicalId($cmdKey);

            if ($type == "info") { // info cmd
            } else { // action cmd
                if (isset($cmdValueDefaut["value"])) {
                    // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                    log::add('Abeille', 'debug', '  Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$cmdValueDefaut["value"]);

                    $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $cmdValueDefaut["value"]);
                    if ($cmdPointeur_Value)
                        $cmdLogic->setValue($cmdPointeur_Value->getId());
                }
            }

            /* Updating command 'configuration' fields.
               In case of update, some fields may no longer be required ($unusedConfKey).
               They are removed if not defined in JSON model. */
            // Tcharp38: TODO: For best cleanup all accepted keys should be listed hereafter. Any other should be removed.
            $unusedConfKey = ['visibilityCategory', 'minValue', 'maxValue', 'historizeRound', 'calculValueOffset', 'execAtCreation', 'execAtCreationDelay', 'uniqId', 'repeatEventManagement', 'topic'];
            array_push($unusedConfKey, 'ab::trigOut', 'ab::trigOutOffset', 'PollingOnCmdChange', 'PollingOnCmdChangeDelay');
            if (isset($cmdValueDefaut["configuration"])) {
                $configuration = $cmdValueDefaut["configuration"];

                if (isset($configuration["trigOut"]))
                    $cmdLogic->setConfiguration('ab::trigOut', $configuration["trigOut"]);
                else
                    $cmdLogic->setConfiguration('ab::trigOut', null); // Removing config entry
                if (isset($configuration["trigOutOffset"]))
                    $cmdLogic->setConfiguration('ab::trigOutOffset', $configuration["trigOutOffset"]);
                else
                    $cmdLogic->setConfiguration('ab::trigOutOffset', null); // Removing config entry

                foreach ($configuration as $confKey => $confValue) {
                    // Trick for conversion 'key' => 'ab::key' for Abeille specifics
                    // Note: this is currently not applied to all Abeille specific fields.
                    if ($confKey == 'trigOut')
                        $confKey = "ab::trigOut";
                    else if ($confKey == 'trigOutOffset')
                        $confKey = "ab::trigOutOffset";

                    $cmdLogic->setConfiguration($confKey, $confValue);
                    foreach ($unusedConfKey as $uk => $uv) {
                        if ($uv != $confKey)
                            continue;
                        unset($unusedConfKey[$uk]);
                    }
                }
            }

            /* Removing any obsolete 'configuration' field */
            foreach ($unusedConfKey as $confKey) {
                // Tcharp38: Is it the proper way to know if entry exists ?
                if ($cmdLogic->getConfiguration($confKey) == null)
                    continue;
                // log::add('Abeille', 'debug', '  Removing obsolete configuration entry: '.$confKey);
                $cmdLogic->setConfiguration($confKey, null); // Removing config entry
            }

            // On conserve l info du template pour la visibility
            // Tcharp38: What for ? Not found where it is used
            if (isset($cmdValueDefaut["isVisible"]))
                $cmdLogic->setConfiguration("visibiltyTemplate", $cmdValueDefaut["isVisible"]);

            /* Command widget: can be defaulted with 'template'
               Updating only if new command to not overwrite user changes (see issue #2075) */
            if (($action == 'reset') || $newCmd) {
                // Don't touch anything if defined empty in JSON
                if (isset($cmdValueDefaut["template"]) && ($cmdValueDefaut["template"] != "")) {
                    $cmdLogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
                    $cmdLogic->setTemplate('mobile', $cmdValueDefaut["template"]);
                }
            }

            $cmdLogic->setType($cmdValueDefaut["type"]); // 'info' or 'action'
            $cmdLogic->setSubType($cmdValueDefaut["subType"]);
            if (array_key_exists("genericType", $cmdValueDefaut))
                $cmdLogic->setGeneric_type($cmdValueDefaut["genericType"]);
            else if (array_key_exists("generic_type", $cmdValueDefaut)) // Temporary backward compatibility
                $cmdLogic->setGeneric_type(null); // Clear generic type

            if (isset($cmdValueDefaut["unit"]))
                $cmdLogic->setUnite($cmdValueDefaut["unit"]);
            else if (isset($cmdValueDefaut["unite"])) // Temporary backward compatibility
                $cmdLogic->setUnite($cmdValueDefaut["unite"]);
            else
                $cmdLogic->setUnite(''); // Clear unit

            if (($action == 'reset') || $newCmd) { // Update only if new command
                if (isset($cmdValueDefaut["isHistorized"]))
                    $cmdLogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                else
                    $cmdLogic->setIsHistorized(0);
            }

            // Display stuff is updated only if new eq or new cmd to not overwrite user changes
            if (($action == 'reset') || $newCmd) { // Update only if new command
                if (isset($cmdValueDefaut["isVisible"]))
                    $cmdLogic->setIsVisible($cmdValueDefaut["isVisible"]);
                else
                    $cmdLogic->setIsVisible(0);
            }

            // Display stuff is updated only if new eq or new cmd to not overwrite user changes
            if (($action == 'reset') || $newCmd) {
                // TODO: Update all JSON to move "invertBinary" into "display" section
                if (isset($cmdValueDefaut["invertBinary"])) {
                    $cmdLogic->setDisplay('invertBinary', $cmdValueDefaut["invertBinary"]);
                }
                if (array_key_exists("display", $cmdValueDefaut))
                    foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                        $cmdLogic->setDisplay($confKey, $confValue);
                    }
                // TODO: Missing a way to remove obsolete entries
            }

            $cmdLogic->save();
        }
    } // End createDevice()

    /* Update all infos related to last communication time & LQI of given device.
       This is based on timestamp of last communication received from device itself. */
    public static function updateTimestamp($eqLogic, $timestamp, $lqi = null) {
        $eqLogicId = $eqLogic->getLogicalId();
        $eqId = $eqLogic->getId();

        // log::add('Abeille', 'debug', "  updateTimestamp(): Updating last comm. time for '".$eqLogicId."'");

        // Updating directly eqLogic/setStatus/'lastCommunication' & 'timeout' with real timestamp
        $eqLogic->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));

        /* Tcharp38 note:
           The cases hereafter could be removed. Using 'lastCommunication' allows to no longer
           use these 3 specific & redondant commands. To be discussed. */

        $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-TimeStamp");
        if (!is_object($cmdLogic))
            log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-TimeStamp'");
        else
            $eqLogic->checkAndUpdateCmd($cmdLogic, $timestamp);

        $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-Time");
        if (!is_object($cmdLogic))
            log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-Time'");
        else
            $eqLogic->checkAndUpdateCmd($cmdLogic, date("Y-m-d H:i:s", $timestamp));

        $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'online');
        if (is_object($cmdLogic))
        //     log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'online'");
        // else
            $eqLogic->checkAndUpdateCmd($cmdLogic, 1);

        if ($lqi != null) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Link-Quality');
            if (!is_object($cmdLogic))
                log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Link-Quality'");
            else
                $eqLogic->checkAndUpdateCmd($cmdLogic, $lqi);
        }
    }
}
