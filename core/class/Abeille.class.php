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

class Abeille extends eqLogic {
    // // Fonction dupliquée dans AbeilleParser.
    // public static function volt2pourcent($voltage) {
    //     $max = 3.135;
    //     $min = 2.8;
    //     if ($voltage / 1000 > $max) {
    //         log::add('Abeille', 'debug', 'Voltage remonte par le device a plus de '.$max.'V. Je retourne 100%.');
    //         return 100;
    //     }
    //     if ($voltage / 1000 < $min) {
    //         log::add('Abeille', 'debug', 'Voltage remonte par le device a moins de '.$min.'V. Je retourne 0%.');
    //         return 0;
    //     }
    //     return round(100 - ((($max - ($voltage / 1000)) / ($max - $min)) * 100));
    // }

    /**
     * Jeedom requirement: returns health status.
     *
     * @param none
     *
     * @return test   title/decription of the test
     * @return result test result
     * @return advice comment by question mark icon
     * @return state  if the test was successful or not
     */
    public static function health() {
        $result = '';
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') == 'N')
                continue; // Disabled
            if (config::byKey('ab::zgType'.$zgId, 'Abeille', '') == 'WIFI')
                continue; // WIFI does not use a physical port

            if ($result != '')
                $result .= ", ";
            $result .= config::byKey('ab::zgPort'.$zgId, 'Abeille', '');
        }

        $return[] = array(
            'test' => 'Ports: ',            // title of the line
            'result' => $result,            // Text which be printed in the line
            'advice' => 'Ports utilisés',   // Text printed when mouse is on question mark icon
            'state' => true,                // Status du plugin: true line will be green, false line will be red.
        );

        return $return;
    }

    // public static function updateConfigAbeille($abeilleIdFilter = false)
    // {

    // }

    /**
     * executePollCmds
     * Execute commands with "Polling" flag according to given "period".
     *
     * @param $period One of the crons: 'cron', 'cron15', 'cronHourly' ....
     *
     * @return Does not return anything as all action are triggered by sending messages in queues
     */
    public static function executePollCmds($period) {
        $cmds = cmd::searchConfiguration('Polling', 'Abeille');
        foreach ($cmds as $cmd) {
            if ($cmd->getConfiguration('Polling') != $period)
                continue;
            $eqLogic = $cmd->getEqLogic();
            $eqHName = $eqLogic->getHumanName();
            $cmdName = $cmd->getName();
            if (!$eqLogic->getIsEnable()) {
                log::add('Abeille', 'debug', "executePollCmds(".$period."): ".$eqHName.", cmd='".$cmdName."' => IGNORED (device disabled)");
            } else {
                log::add('Abeille', 'debug', "executePollCmds(".$period."): ".$eqHName.", cmd='".$cmdName."' (".$cmd->getLogicalId().")");
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
    public static function refreshCmd() {
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

    // /**
    //  * getIEEE
    //  * get IEEE from the eqLogic
    //  *
    //  * @param   $address    logicalId of the eqLogic
    //  *
    //  * @return              Does not return anything as all action are triggered by sending messages in queues
    //  */
    // public static function getIEEE($address) {
    //     if (strlen(eqLogic::byLogicalId($address, 'Abeille')->getConfiguration('IEEE', 'none')) == 16) {
    //         return eqLogic::byLogicalId($address, 'Abeille')->getConfiguration('IEEE', 'none');
    //     } else {
    //         return AbeilleCmd::byEqLogicIdAndLogicalId(eqLogic::byLogicalId($address, 'Abeille')->getId(), 'IEEE-Addr')->execCmd();
    //     }
    // }

    /**
     * getEqFromIEEE
     * get eqLogic from IEEE
     *
     * @param   $IEEE    IEEE of the device
     *
     * @return           eq with this IEEE or Null if not found
     */
    // public static function getEqFromIEEE($IEEE)
    // {
    //     foreach (self::searchConfiguration('IEEE', 'Abeille') as $eq) {
    //         if ($eq->getConfiguration('IEEE') == $IEEE) {
    //             return $eq;
    //         }
    //     }
    //     return null;
    // }

    /**
     * cronDaily
     * Called by Jeedom every days.
     * Refresh LQI
     * Poll Cmd cronDaily
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cronDaily() {
        log::add('Abeille', 'debug', 'cronDaily() starting');

        $preventLQIRequest = config::byKey('ab::preventLQIAutoUpdate', 'Abeille', 'no');
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
    public static function cronHourly() {
        log::add('Abeille', 'debug', 'cronHourly() starting');

        // log::add('Abeille', 'debug', 'Check Zigate Presence');

        // $config = AbeilleTools::getConfig();
// if (0) {
//         //--------------------------------------------------------
//         // Refresh Ampoule Ikea Bind et set Report
//         log::add('Abeille', 'debug', 'Refresh Ampoule Ikea Bind et set Report');

//         $eqLogics = Abeille::byType('Abeille');
//         $i = 0;
//         foreach ($eqLogics as $eqLogic) {
//             // Filtre sur Ikea
//             if (strpos("_".$eqLogic->getConfiguration("ab::icon"), "IkeaTradfriBulb") > 0) {
//                 list($dest, $addr) = explode("/", $eqLogic->getLogicalId());
//                 $i = $i + 1;

//                 // Recupere IEEE de la Ruche/ZiGate
//                 $ZiGateIEEE = self::getIEEE($dest.'/0000');

//                 // Recupere IEEE de l Abeille
//                 $addrIEEE = self::getIEEE($dest.'/'.$addr);

//                 log::add('Abeille', 'debug', 'Refresh bind and report for Ikea Bulb: '.$addr);
//                 Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/bindShort&time=".(time() + (($i * 33) + 1)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0006&reportToAddress=".$ZiGateIEEE);
//                 Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/bindShort&time=".(time() + (($i * 33) + 2)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0008&reportToAddress=".$ZiGateIEEE);
//                 Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/setReport&time=".(time() + (($i * 33) + 3)), "address=".$addr."&ClusterId=0006&AttributeId=0000&AttributeType=10");
//                 Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/0000/setReport&time=".(time() + (($i * 33) + 4)), "address=".$addr."&ClusterId=0008&AttributeId=0000&AttributeType=20");
//             }
//         }
//         if (($i * 33) > (3600)) {
//             message::add("Abeille", "Danger il y a trop de message a envoyer dans le cron 1 heure.", "Contactez KiwiHC16 sur le Forum.");
//         }
//     }
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
    public static function cron15() {
        global $abQueues;

        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron15(): Main daemon stopped => cron15 canceled');
            return;
        }

        log::add('Abeille', 'debug', 'cron15(): Starting --------------------------------');

        /* Look every 15 minutes if the kernel driver is not in error */
        // Disabled. Now power cycling USB/USBv2 zigate if lastComm > 2mins
        // log::add('Abeille', 'debug', 'cron15(): Check USB driver potential crash');
        // $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
        // $output = array();
        // exec(system::getCmdSudo().$cmd, $output);
        // $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
        // if ($usbZigateStatus != '0') {
        //     message::add("Abeille", "ERREUR: le pilote pl2303 semble en erreur, impossible de communiquer avec la zigate.", "Il faut débrancher/rebrancher la zigate et relancer le démon.");
        //     // log::add('Abeille', 'debug', 'cron15(): Fin --------------------------------');
        // }

        log::add('Abeille', 'debug', 'cron15(): Interrogating devices silent for more than 15mins.');
        $config = AbeilleTools::getConfig();
        $i = 0;
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            $zigate = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            if (!is_object($zigate))
                continue; // Does not exist on Jeedom side.
            if (!$zigate->getIsEnable())
                continue; // Zigate disabled
            if ($config['ab::zgEnabled'.$zgId] != 'Y')
                continue; // Zigate disabled.

            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list($dest, $addr) = explode("/", $eqLogic->getLogicalId());
                if ($dest != 'Abeille'.$zgId)
                    continue; // Not on current network
                if (!$eqLogic->getIsEnable())
                    continue; // Equipment disabled

                /* Special case: should ignore virtual remote */
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
                $jsonId = $eqModel ? $eqModel['id'] : '';
                if ($jsonId == "remotecontrol")
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
                $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                if (isset($zigbee['rxOnWhenIdle']) && ($zigbee['rxOnWhenIdle'] == 1))
                    $poll = 1;

                // Absence of 'battery_type' does not mean device can receive something
                // if (strlen($eqLogic->getConfiguration("battery_type", '')) == 0)
                //     $poll += 10;

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
    public static function cron() {
        /* If main daemon is not running, cron must do nothing */
        if (AbeilleTools::isAbeilleCronRunning() == false) {
            log::add('Abeille', 'debug', 'cron(): Main daemon stopped => cron1 canceled');
            return;
        }

        // log::add( 'Abeille', 'debug', 'cron(): Start ------------------------------------------------------------------------------------------------------------------------' );
        $config = AbeilleTools::getConfig();

        // Check & restart missing daemons
        $dStatus = AbeilleTools::checkAllDaemons2($config);

        /* For debug purposes, display 'PID/daemonShortName' */
        // $running = AbeilleTools::getRunningDaemons2();
        $dTxt = "";
        foreach ($dStatus['running']['daemons'] as $daemonName => $daemon) {
            if ($dTxt != "")
                $dTxt .= ", ";
            $dTxt .= $daemon['pid'].'/'.$daemonName;
        }
        log::add('Abeille', 'debug', 'cron(): Daemons: '.$dTxt);

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
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if ($config['ab::zgEnabled'.$zgId] != 'Y')
                continue; // Zigate disabled
            if ($config['ab::zgPort'.$zgId] == "none")
                continue; // Serial port undefined
            // TODO Tcharp38: Currently leads to PI zigate timeout. No sense since still alive.
            // if ($config['ab::zgType'.$zgId] != "WIFI")
            //     continue; // Not a WIFI zigate. No polling required

            // TODO: Better to read time to correct it if required, instead of version that rarely changes
            Abeille::msgToCmd(PRIO_NORM, "CmdAbeille".$zgId."/0000/zgGetVersion");

            // Checking that Zigate is still alive
            $eqLogic = eqLogic::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'error', "La ruche ".$zgId." a été détruite. Veuillez redémarrer Abeille.");
                continue;
            }
            $lastComm = $eqLogic->getStatus('lastCommunication', '');
            // log::add('Abeille', 'info', "lastComm1=".$lastComm);
            if ($lastComm == '')
                $lastComm = 0;
            else
                $lastComm = strtotime($lastComm);
            // log::add('Abeille', 'info', "lastComm2=".$lastComm);
            if ((time() - $lastComm) > (2 * 60)) {
                log::add('Abeille', 'info', "Pas de réponse de la Zigate ".$zgId." depuis plus de 2min");
                $zgType = $config['ab::zgType'.$zgId];
                $zgPort = $config['ab::zgPort'.$zgId];
                if (($zgType == "USB") || ($zgType == "USBv2")) {
                    if ($config['ab::preventUsbPowerCycle'] == 'Y')
                        log::add('Abeille', 'Debug', 'Power cycle required for Zigate '.$zgId.' but disabled');
                    else {
                        $dir = __DIR__."/../scripts";
                        $cmd = "cd ".$dir."; ".system::getCmdSudo()." ./powerCycleUsb.sh ".$zgPort." 1>/tmp/jeedom/Abeille/powerCycleUsb.log 2>&1";
                        log::add('Abeille', 'debug', 'Performing power cycle on port \''.$zgPort.'\'');
                        exec($cmd, $output, $exitCode);
                        if ($exitCode != 0)
                            message::add("Abeille", "La Zigate ".$zgId." semble plantée mais impossible de lui faire un cycle OFF/ON.");
                    }
                } else if (($zgType == "PI") || ($zgType == "PIv2")) {
                    log::add('Abeille', 'Debug', 'Performing HW reset on Zigate '.$zgId);
                    exec("python /var/www/html/plugins/Abeille/core/scripts/resetPiZigate.py");
                } else if (($zgType == "WIFI") || ($zgType == "WIFIv2")) {
                    log::add('Abeille', 'Debug', 'Restarting socat for Zigate '.$zgId);
                    AbeilleTools::restartDaemons($config, "Socat".$zgId." socat".$zgId);
                }
            }
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
         * Reminder:
         * eqLogic->xxStatus/lastCommunication: Used by Jeedom too. Format = string 'Y-m-d H:i:s'. Updated by checkAndUpdateCmd().
         * eqLogic->xxStatus/timeout: Used by Jeedom too. Format = number (0 or 1). Set to 1 if device is in timeout.
         */
        foreach ($eqLogics as $eqLogic) {
            $timeout = $eqLogic->getTimeout(0);
            $timeoutS = $eqLogic->getStatus('timeout', 0); // Timeout status
            if ($timeout == 0) {
                $newTimeoutS = 0;
                // $newState = '-';
            } else {
                // Tcharp38: If no comm, should we take Abeille start time ? Something else ?
                $lastComm = $eqLogic->getStatus('lastCommunication', '');
                if ($lastComm == '')
                    $lastComm = 0;
                else
                    $lastComm = strtotime($lastComm);

                // Checking timeout
                if (($lastComm + (60 * $timeout)) > time()) {
                    // Ok
                    $newTimeoutS = 0;
                    // $newState = 'ok';
                } else {
                    // NOK
                    $newTimeoutS = 1;
                    // $newState = 'Time Out Last Communication';
                }
            }

            if ($newTimeoutS != $timeoutS) {
                log::add('Abeille', 'debug', 'cron(): '.$eqLogic->getName().': timeout status changed to '.$newTimeoutS);
                $newStatus = array(
                    'timeout' => $newTimeoutS,
                    // 'state' => $newState, // Tcharp38: Only used by Abeille. Really required ?
                );
                $eqLogic->setStatus($newStatus);
            }
        }

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
    } // End cron()

    /**
     * Jeedom required function: report plugin & config status
     * @param none
     * @return array with state, launchable, launchable_message
     */
    public static function deamon_info() {
        // $smId = @shmop_open(12, "a", 0, 0);
        // if ($smId !== false) {
        //     $smContent = shmop_read($smId, 0, shmop_size($smId));
        //     log::add('Abeille', 'debug', 'deamon_info(): smContent='.json_encode($smContent));
        //     shmop_close($smId);
        // }

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
        $config = AbeilleTools::getConfig();
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
    public static function deamon_start_cleanup() {
        // log::add('Abeille', 'debug', 'deamon_start_cleanup(): Démarrage');

        // Remove Abeille's user messages
        message::removeAll('Abeille');

        // Remove any remaining temporary files
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            $lockFile = jeedom::getTmpFolder('Abeille').'/AbeilleLQI-Abeille'.$zgId.'.json.lock';
            if (file_exists($lockFile)) {
                unlink($lockFile);
                log::add('Abeille', 'debug', 'deamon_start_cleanup(): Removed '.$lockFile);
            }
        }

        // Clear zigate IEEE status to detect any port switch.
        // ab::zgIeeeAddrOk=-1: Zigate IEEE is NOT the expected one (port switch ?)
        //     "         = 0: IEEE check to be done
        //     "         = 1: Zigate on the right port
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            config::save("ab::zgIeeeAddrOk".$zgId, 0, 'Abeille');
        }

        /* Check & update configuration DB if required. */
        $dbVersion = config::byKey('ab::dbVersion', 'Abeille', '');
        $dbVersionLast = lastDbVersion;
        if (($dbVersion == '') || (intval($dbVersion) < $dbVersionLast)) {
            log::add('Abeille', 'debug', 'deamon_start_cleanup(): DB config v'.$dbVersion.' < v'.$dbVersionLast.' => Update required.');
            updateConfigDB();
        } else
            log::add('Abeille', 'debug', 'deamon_start_cleanup(): DB config v'.$dbVersion.' is up-to-date.');

        // Removing empty dir in "devices_local"
        AbeilleTools::cleanDevices();

        // log::add('Abeille', 'debug', 'deamon_start_cleanup(): Terminé');
        return;
    }

    /* Jeedom required function.
       Starts all daemons.
       Note: incorrect naming 'deamon' instead of 'daemon' due to Jeedom mistake. */
    public static function deamon_start($_debug = false) {
        $smId = @shmop_open(12, "a", 0, 0);
        if ($smId !== false) {
            $smContent = shmop_read($smId, 0, shmop_size($smId));
            log::add('Abeille', 'debug', 'deamon_start(): starting. smContent='.$smContent);
            shmop_close($smId);
            $smContent = json_decode($smContent, true);
            if (isset($smContent['daemonsPaused']) && ($smContent['daemonsPaused'] == true)) {
                log::add('Abeille', 'debug', 'deamon_start(): IGNORED => daemons PAUSED');
                return;
            }
        } else
            log::add('Abeille', 'debug', 'deamon_start(): starting. No shared mem');

        /* Some checks before starting daemons
               - Are dependancies ok ?
               - does Abeille cron exist ? */
        if (self::dependancy_info()['state'] != 'ok') {
            message::add("Abeille", "Tentative de demarrage alors qu\'il y a un soucis avec les dépendances");
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

        $config = AbeilleTools::getConfig();

        /* Checking config */
        // TODO Tcharp38: Should be done during deamon_info() and report proper 'launchable'
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if ($config['ab::zgEnabled'.$zgId] != 'Y')
                continue; // Disabled

            /* This zigate is enabled. Checking other parameters */
            $error = "";
            $sp = $config['ab::zgPort'.$zgId];
            if (($sp == 'none') || ($sp == "")) {
                $error = "Port série de la zigate ".$zgId." INVALIDE";
            }
            if ($error == "") {
                if ($config['ab::zgType'.$zgId] == "WIFI") {
                    $wifiAddr = $config['ab::zgIpAddr'.$zgId];
                    if (($wifiAddr == 'none') || ($wifiAddr == "")) {
                        $error = "Adresse Wifi de la zigate ".$zgId." INVALIDE";
                    }
                }
            }
            if ($error != "") {
                $config['ab::zgEnabled'.$zgId] = 'N';
                config::save('ab::zgEnabled'.$zgId, 'N', 'Abeille');
                log::add('Abeille', 'error', $error." => Zigate désactivée.");
            } else if (($config['ab::zgType'.$zgId] == "PI") || ($config['ab::zgType'.$zgId] == "PIv2")) {
                /* Configuring GPIO for PiZigate if one active found.
                    PiZigate reminder (using 'WiringPi'):
                    - port 0 = RESET
                    - port 2 = FLASH
                    - Production mode: FLASH=1, RESET=0 then 1 */
                AbeilleTools::setPIGpio(); // Found an active PI Zigate. Configure GPIO (needed once).
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
            if (($config['ab::zgPort'.$zgId] == 'none') or ($config['ab::zgEnabled'.$zgId] != 'Y'))
                continue; // Undefined or disabled
            $expected |= constant("daemonSerialRead".$zgId);
            if ($config['ab::zgType'.$zgId] == 'WIFI')
                $expected |= constant("daemonSocat".$zgId);
        }
        $timeout = 10;
        for ($t = 0; $t < $timeout; $t++) {
            $runArr = AbeilleTools::getRunningDaemons2();
            if (($runArr['runBits'] & $expected) == $expected)
                break;
            sleep(1);
        }
        if ($t == $timeout)
            log::add('Abeille', 'debug', 'deamon_start(): ERROR, still some missing daemons after timeout');

        // Starting main daemon; this will start to treat received messages
        cron::byClassAndFunction('Abeille', 'deamon')->run();

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

    /* Jeedom required function.
       Stopping all daemons and removing queues */
    public static function deamon_stop() {
        log::add('Abeille', 'debug', 'deamon_stop(): Starting');

        /* Stopping cron */
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (!is_object($cron))
            log::add('Abeille', 'error', 'deamon_stop(): Tache cron introuvable');
        else if ($cron->running()) {
            log::add('Abeille', 'debug', 'deamon_stop(): Stopping cron');
            $cron->halt();
            while ($cron->running()) {
                usleep(500000);
                log::add('Abeille', 'debug', 'deamon_stop(): cron STILL running');
            }
        } else
            log::add('Abeille', 'debug', 'deamon_stop(): cron already stopped');

        /* Stopping all 'Abeille' daemons */
        AbeilleTools::stopDaemons();

        /* Removing all queues */
        $abQueues = $GLOBALS['abQueues'];
        foreach ($abQueues as $q) {
            $queueId = $q['id'];
            $queue = msg_get_queue($queueId);
            if ($queue !== false)
                msg_remove_queue($queue);
        }

        log::add('Abeille', 'debug', 'deamon_stop(): Ended');
    }

    /* Temporary stop daemons and prevent auto-restart from Jeedom */
    public static function pauseDaemons($start) {
        $smId = shmop_open(12, "c", 0644, 50);
        $smContent = [];
        if ($start)
            $smContent['daemonsPaused'] = true;
        else
            $smContent['daemonsPaused'] = false;
        shmop_write($smId, json_encode($smContent), 0);
        shmop_close($smId);

        log::add('Abeille', 'debug', 'pauseDaemons('.$start.')');
        if ($start) {
            $daemons = AbeilleTools::getRunningDaemons2();
            if ($daemons['runBits'] == 0)
                $GLOBALS['daemonsRunning'] = false; // No running daemon
            else
                $GLOBALS['daemonsRunning'] = true;
            log::add('Abeille', 'debug', 'Stopping daemons');
            self::deamon_stop(); // Stopping daemons
        } else {
            if ($GLOBALS['daemonsRunning']) {
                log::add('Abeille', 'debug', 'Restarting daemons');
                abeille::deamon_start(); // Restarting daemon
            }
        }
    }

    /* Called from Jeedom to install dependencies */
    public static function dependancy_install() {
        log::add('Abeille', 'debug', 'dependancy_install()');

        message::add("Abeille", "Installation des dépendances en cours.", "N'oubliez pas de lire la documentation: https://kiwihc16.github.io/AbeilleDoc");
        log::remove(__CLASS__.'_update');
        $result = [
            'script' => __DIR__.'/../scripts/installDependencies.sh '.jeedom::getTmpFolder('Abeille').'/dependencies_progress',
            'log' => log::getPathToLog(__CLASS__.'_update')
        ];

        return $result;
    }

    /* Called from Jeedom to display dependencies status */
    public static function dependancy_info() {
        log::add('Abeille', 'debug', 'dependancy_info()');

        // // Called by js dans plugin.class.js(getDependancyInfo) -> plugin.ajax.php(dependancy_info())
        // // $dependancy_info['state'] pour affichage
        // // state = [ok / nok / in_progress (progression/duration)] / state
        // // il n ' y plus de dépendance hotmis pour la zigate wifi (socat) qui est installé par un script a part.
        // $debug_dependancy_info = 1;

        // $return = array();
        // $return['state'] = 'ok';
        // $return['progress_file'] = jeedom::getTmpFolder('Abeille').'/dependencies_progress';

        // // Check package socat
        // // Tcharp38: Wrong. This dependancy is required only if wifi zigate. Should not impact those using USB or PI
        // $cmd = "command -v socat";
        // exec($cmd, $output_dpkg, $return_var);
        // if ($return_var == 1) {
        //     message::add("Abeille", "Le package socat est nécéssaire pour l'utilisation de la zigate Wifi. Si vous avez la zigate usb, vous pouvez ignorer ce message");
        //     log::add('Abeille', 'warning', 'Le package socat est nécéssaire pour l\'utilisation de la zigate Wifi.');
        // }

        // if ($debug_dependancy_info) log::add('Abeille', 'debug', 'dependancy_info: '.json_encode($return));

        // return $return;

        $return = array();
        $return['log'] = log::getPathToLog(__CLASS__ . '_update');
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependencies_progress';
        if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependencies_progress')) {
            $return['state'] = 'in_progress';
        } else {
            // python3 is the only base dependency but may need socat for Wifi Zigates, or a GPIO lib for PI Zigates
            exec("command -v python3", $output, $exitCode);
            if ($exitCode != 0) {
                $return['state'] = 'nok';
            } else {
                $return['state'] = 'ok';
            }
        }
        log::add('Abeille', 'debug', 'dependancy_info: '.json_encode($return));
        return $return;
    }

    /* This is Abeille's main daemon, directly controlled by Jeedom itself. */
    public static function deamon() {
        global $abQueues;

        log::add('Abeille', 'debug', 'deamon(): Main daemon starting');

        /* Main daemon starting.
           This means that other daemons have started too. Abeille can communicate with them */

        // Send a message to Abeille to ask for behive creation/update.
        // Tcharp38: Moved from deamon_start()
        $config = AbeilleTools::getConfig();
        for ($zgId = 1; $zgId <= $GLOBALS['maxNbOfZigate']; $zgId++) {
            if (($config['ab::zgPort'.$zgId] == 'none') or ($config['ab::zgEnabled'.$zgId] != 'Y'))
                continue; // Undefined or disabled

            // Create/update beehive equipment on Jeedom side
            // Note: This will reset 'FW-Version' to '---------' to mark FW version invalid.
            // Abeille::publishMosquitto($abQueues["xToAbeille"]["id"], priorityInterrogation, "CmdRuche/0000/CreateRuche", "Abeille".$zgId);
            self::createRuche("Abeille".$zgId);

            // Configuring zigate: TODO: This should be done on Abeille startup or on new beehive creation.
            if (isset($config['ab::zgChan'.$zgId])) {
                $chan = $config['ab::zgChan'.$zgId];
                if ($chan == 0)
                    $mask = 0x7fff800; // All channels = auto
                else
                    $mask = 1 << $chan;
                $mask = sprintf("%08X", $mask);
                log::add('Abeille', 'debug', "deamon(): Settings chan ".$chan." (mask=".$mask.") for zigate ".$zgId);
                Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetChannelMask", "mask=".$mask);
            }
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSoftReset", "");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgStartNetwork", "");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetTimeServer", "");
            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgGetVersion", "");

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
            //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetMode", "mode=hybrid");
            // } else {
            //     log::add('Abeille', 'debug', 'deamon(): Configuring zigate '.$zgId.' in normal mode');
            //     Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetMode", "mode=normal");
            // }
        }

        // Essaye de recuperer les etats des equipements
        // Tcharp38: Moved from deamon_start()
        self::refreshCmd();

        try {
            $abQueues = $GLOBALS['abQueues'];
            // Tcharp38: Merge all the followings queues. Would be more efficient & more reactive.
            // $queueParserToAbeille2 = msg_get_queue($abQueues["parserToAbeille2"]["id"]);
            // $queueParserToAbeille2Max = $abQueues["parserToAbeille2"]["max"];
            $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
            $queueXToAbeilleMax = $abQueues["xToAbeille"]["max"];

            $max_msg_size = 512;

            // https: github.com/torvalds/linux/blob/master/include/uapi/asm-generic/errno.h
            // const int EINVAL = 22;
            // const int ENOMSG = 42; /* No message of desired type */

            // while (true) {
            //     /* New path parser to Abeille */
            //     $msgMax = $queueParserToAbeille2Max;
            //     if (msg_receive($queueParserToAbeille2, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT, $errCode)) {
            //         self::msgFromParser(json_decode($msgJson, true));
            //     } else { // Error
            //         if ($errCode == 7) {
            //             msg_receive($queueParserToAbeille2, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
            //             log::add('Abeille', 'debug', 'deamon(): msg_receive queueParserToAbeille2 ERROR: msg TOO BIG ignored.');
            //         } else if ($errCode != 42)
            //             log::add('Abeille', 'debug', 'deamon(): msg_receive queueParserToAbeille2 error '.$errCode);
            //     }

            //     if (msg_receive($queueXToAbeille, 0, $msgType, $max_msg_size, $msgJson, false, MSG_IPC_NOWAIT, $errCode)) {
            //         $msg = json_decode($msgJson, true);
            //         self::message($msg['topic'], $msg['payload']);
            //     } else { // Error
            //         if ($errCode != 42)
            //             log::add('Abeille', 'debug', 'deamon(): msg_receive queueXToAbeille error '.$errCode);
            //     }

            //     time_nanosleep(0, 10000000); // 1/100s
            // }

            // Blocking queue read
            $msgMax = $queueXToAbeilleMax;
            while (true) {
                if (msg_receive($queueXToAbeille, 0, $msgType, $msgMax, $msgJson, false, 0, $errCode)) {
                    $msg = json_decode($msgJson, true);
                    if (isset($msg['topic']))
                        self::message($msg['topic'], $msg['payload']);
                    else
                        self::msgFromParser($msg);
                } else { // Error
                    if ($errCode == 7) {
                        msg_receive($queueXToAbeille, 0, $msgType, $msgMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
                        log::add('Abeille', 'error', "Message (xToAbeille) trop grand ignoré: ".$msgJson);
                    } else if ($errCode != 42)
                        log::add('Abeille', 'error', 'deamon(): msg_receive(xToAbeille) erreur '.$errCode.', msg='.$msgJson);
                }
            }
        } catch (Exception $e) {
            log::add('Abeille', 'error', 'deamon(): Exception '.$e->getMessage());
        }

        log::add('Abeille', 'debug', 'deamon(): Main daemon stopped');
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
    //         if ($return['ab::zgEnabled'.$i ]=='Y') {
    //             if ($return['ab::zgPort'.$i]!='none') {
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
    //         if ($return['ab::zgEnabled'.$i ]=='Y') {
    //             if ($return['ab::zgPort'.$i] != 'none') {
    //                 if (@!file_exists($return['ab::zgPort'.$i])) {
    //                     log::add('Abeille','debug','checkParameters: Le port série n existe pas: '.$return['ab::zgPort'.$i]);
    //                     message::add('Abeille','Warning: Le port série n existe pas: '.$return['ab::zgPort'.$i],'' );
    //                     $return['parametersCheck']="nok";
    //                     $return['parametersCheck_message'] = __('Le port série '.$return['ab::zgPort'.$i].' n existe pas (zigate déconnectée ?)', __FILE__);
    //                     return 0;
    //                 } else {
    //                     if (substr(decoct(fileperms($return['ab::zgPort'.$i])), -4) != "0777") {
    //                         exec(system::getCmdSudo().'chmod 777 '.$return['ab::zgPort'.$i].' > /dev/null 2>&1');
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

    // Tcharp38: Seems useless now.
    // public static function fetchShortFromIEEE($IEEE, $checkShort)
    // {
    //     // Return:
    //     // 0 : Short Address is aligned with the one received
    //     // Short : Short Address is NOT aligned with the one received
    //     // -1 : Error Nothing found

    //     // $lookForIEEE = "000B57fffe490C2a";
    //     // $checkShort = "2006";
    //     // log::add('Abeille', 'debug', 'KIWI: start function fetchShortFromIEEE');
    //     $abeilles = Abeille::byType('Abeille');

    //     foreach ($abeilles as $abeille) {

    //         if (strlen($abeille->getConfiguration('IEEE', 'none')) == 16) {
    //             $IEEE_abeille = $abeille->getConfiguration('IEEE', 'none');
    //         } else {
    //             $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr');
    //             if (is_object($cmdIEEE)) {
    //                 $IEEE_abeille = $cmdIEEE->execCmd();
    //                 if (strlen($IEEE_abeille) == 16) {
    //                     $abeille->setConfiguration('IEEE', $IEEE_abeille); // si j ai l IEEE dans la cmd et pas dans le conf, je transfer, retro compatibility
    //                     $abeille->save();
    //                     $abeille->refresh();
    //                 }
    //             }
    //         }

    //         if ($IEEE_abeille == $IEEE) {

    //             $cmdShort = $abeille->getCmd('Info', 'Short-Addr');
    //             if ($cmdShort) {
    //                 if ($cmdShort->execCmd() == $checkShort) {
    //                     // echo "Success ";
    //                     // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return 0');
    //                     return 0;
    //                 } else {
    //                     // echo "Pas success du tout ";
    //                     // La cmd short n est pas forcement à jour alors on va essayer avec le nodeId.
    //                     // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return Short: '.$cmdShort->execCmd() );
    //                     // return $cmdShort->execCmd();
    //                     // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return Short: '.substr($abeille->getlogicalId(),-4) );
    //                     return substr($abeille->getlogicalId(), -4);
    //                 }

    //                 return $return;
    //             }
    //         }
    //     }

    //     // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return -1');
    //     return -1;
    // }

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

    // Tcharp38: Seems no longer used
    // public static function CmdAffichage($affichageType, $Visibility = "na")
    // {
    //     // $affichageType could be:
    //     //  affichageNetwork
    //     //  affichageTime
    //     //  affichageCmdAdd
    //     // $Visibilty command could be
    //     // Y
    //     // N
    //     // toggle
    //     // na

    //     if ($Visibility == "na") {
    //         return;
    //     }

    //     $config = AbeilleTools::getConfig();

    //     $convert = array(
    //         "affichageNetwork" => "Network",
    //         "affichageTime" => "Time",
    //         "affichageCmdAdd" => "additionalCommand"
    //     );

    //     log::add('Abeille', 'debug', 'Entering CmdAffichage with affichageType: '.$affichageType.' - Visibility: '.$Visibility);
    //     echo 'Entering CmdAffichage with affichageType: '.$affichageType.' - Visibility: '.$Visibility;

    //     switch ($Visibility) {
    //         case 'Y':
    //             break;
    //         case 'N':
    //             break;
    //         case 'toggle':
    //             if ($config[$affichageType] == 'Y') {
    //                 $Visibility = 'N';
    //             } else {
    //                 $Visibility = 'Y';
    //             }
    //             break;
    //     }
    //     config::save($affichageType, $Visibility, 'Abeille');

    //     $abeilles = self::byType('Abeille');
    //     foreach ($abeilles as $key => $abeille) {
    //         $cmds = $abeille->getCmd();
    //         foreach ($cmds as $keyCmd => $cmd) {
    //             if ($cmd->getConfiguration("visibilityCategory") == $convert[$affichageType]) {
    //                 switch ($Visibility) {
    //                     case 'Y':
    //                         $cmd->setIsVisible(1);
    //                         break;
    //                     case 'N':
    //                         $cmd->setIsVisible(0);
    //                         break;
    //                 }
    //             }
    //             $cmd->save();
    //         }
    //         $abeille->save();
    //         $abeille->refresh();
    //     }

    //     log::add('Abeille', 'debug', 'Leaving CmdAffichage');
    //     return;
    // }

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

    // TODO: To be moved in AbeilleTools. Could be used by parser too
    // Attempt to find model corresponding to given zigbee signature.
    // Returns: associative array('jsonId', 'jsonLocation') or false
    public static function findModel($zbModelId, $zbManufId) {

        $identifier1 = $zbModelId.'_'.$zbManufId;
        $identifier2 = $zbModelId;

        // Search by <zbModelId>_<zbManufId>, starting from local models list
        $localModels = AbeilleTools::getDevicesList('local');
        foreach ($localModels as $modelId => $model) {
            if ($modelId == $identifier1) {
                $identifier = $identifier1;
                break;
            }
        }
        if (!isset($identifier)) {
            // Search by <zbModelId>_<zbManufId>, starting from offical models list
            $officialModels = AbeilleTools::getDevicesList('Abeille');
            foreach ($officialModels as $modelId => $model) {
                if ($modelId == $identifier1) {
                    $identifier = $identifier1;
                    break;
                }
            }
        }
        if (!isset($identifier)) {
            // Search by <zbModelId> in local models
            foreach ($localModels as $modelId => $model) {
                if ($modelId == $identifier2) {
                    $identifier = $identifier2;
                    break;
                }
            }
        }
        if (!isset($identifier)) {
            // Search by <zbModelId> in offical models
            foreach ($officialModels as $modelId => $model) {
                if ($modelId == $identifier2) {
                    $identifier = $identifier2;
                    break;
                }
            }
        }
        if (!isset($identifier))
            return false; // No model found

        return $model;
    }

    public static function message($topic, $payload) {
        // KiwiHC16: Please leave this line log::add commented otherwise too many messages in log Abeille
        // and keep the 3 lines below which print all messages except Time-Time, Time-TimeStamp and Link-Quality that we get for every message.
        // Divide by 3 the log quantity and ease the log reading
        // log::add('Abeille', 'debug', "message(topic='".$topic."', payload='".$payload."')");

        $topicArray = explode("/", $topic);
        if (sizeof($topicArray) != 3) {
            log::add('Abeille', 'debug', "ERROR: Invalid message: topic=".$topic);
            return;
        }

        $config = AbeilleTools::getConfig();

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
        // if ($cmdId == "0000-01-0005") {
        //     if ($value == "lumi.sens") {
        //         $value = "lumi.sensor_ht";
        //         message::add("Abeille", "lumi.sensor_ht case tracking: ".json_encode($message), '');
        //     }
        //     if ($value == "lumi.sensor_swit") $value = "lumi.sensor_switch.aq3";
        //     if ($value == "TRADFRI Signal Repeater") $value = "TRADFRI signal repeater";
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
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
                $jsonId2 = $eqModel ? $eqModel['id'] : '';
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
            $dev = array(
                'net' => $dest,
                'addr' => $rcAddr,
                'jsonId' => 'remotecontrol',
                'jsonLocation' => 'Abeille',
            );
            Abeille::createDevice("update", $dev);

            return;
        }

        // /* Request to update device from JSON. Useful to avoid reinclusion */
        // // No longer be used
        // if ($cmdId == "updateFromJson") {
        //     log::add('Abeille', 'debug', 'message(): updateFromJson, '.$net.'/'.$addr);

        //     $eqLogic = Abeille::byLogicalId($net.'/'.$addr, 'Abeille');
        //     if (!is_object($eqLogic)) {
        //         log::add('Abeille', 'debug', '  ERROR: Unknown device');
        //         return;
        //     }

        //     // Abeille::createDevice("update", $dest, $addr);
        //     $dev = array(
        //         'net' => $dest,
        //         'addr' => $addr,
        //     );
        //     Abeille::createDevice("update", $dev);

        //     return;
        // }

        /* Request to update or reset device from JSON. Useful to avoid reinclusion */
        if (($cmdId == "updateFromModel") || ($cmdId == "resetFromModel")) {
            if ($cmdId == 'updateFromModel')
                $action = 'update';
            else
                $action = 'reset';
            log::add('Abeille', 'debug', 'message(): '.$cmdId.', '.$net.'/'.$addr);

            $eqLogic = Abeille::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'error', '  '.$cmdId.': Equipement inconnu: '.$net.'/'.$addr);
                return;
            }

            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            $jsonId = isset($eqModel['id']) ? $eqModel['id'] : '';
            $jsonLocation = isset($eqModel['location']) ? $eqModel['location'] : 'Abeille';
            $isForcedModel = isset($eqModel['forcedByUser']) ? $eqModel['forcedByUser'] : false;
            $jsonId1 = $jsonId;

            // Checking if model is defaultUnknown and there is now a real model for it (see #2211).
            // Also rechecking if model is still the correct one (ex: TS011F => TS011F__TZ3000_2putqrmw)
            $eqSig = $eqLogic->getConfiguration('ab::signature', []);

            if (($eqSig != []) && ($eqSig['modelId'] != "") && !$isForcedModel) {
                // Any user or official model ?
                $modelInfos = self::findModel($eqSig['modelId'], $eqSig['manufId']);
                if ($modelInfos !== false) {
                    $modelSig = $modelInfos['modelSig'];
                    $jsonId = $modelInfos['jsonId'];
                    $jsonLocation = $modelInfos['location']; // TODO: rename to jsonLocation
                    $eqHName = $eqLogic->getHumanName();
                }
            }
            if ($jsonId != $jsonId1)
                message::add("Abeille", $eqHName.": Nouveau modèle trouvé. Mise-à-jour en cours.", '');

            $dev = array(
                'net' => $dest,
                'addr' => $addr,
                'modelSig' => $modelSig, // Model signature
                'jsonId' => $jsonId, // Model file name
                'jsonLocation' => $jsonLocation, // Model file location
                'ieee' => $eqLogic->getConfiguration('IEEE'),
            );
            Abeille::createDevice($action, $dev);

            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Cherche l objet par sa ref short Address et la commande
        $eqLogic = eqLogic::byLogicalId($nodeid, 'Abeille');
        if (is_object($eqLogic)) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdId);
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        /* If unknown eq and IEEE received, looking for eq with same IEEE to update logicalName & topic */
        // e.g. Short address change (Si l adresse a changé, on ne peut pas trouver l objet par son nodeId)
        // if (!is_object($eqLogic) && ($cmdId == "IEEE-Addr")) {
        //     log::add('Abeille', 'debug', 'message(), !objet & IEEE: Recherche de l\'equipement correspondant');
        //     // 0 : Short Address is aligned with the one received
        //     // Short : Short Address is NOT aligned with the one received
        //     // -1 : Error Nothing found
        //     $ShortFound = Abeille::fetchShortFromIEEE($value, $addr);
        //     log::add('Abeille', 'debug', 'message(), !objet & IEEE: Trouvé='.$ShortFound);
        //     if ((strlen($ShortFound) == 4) && ($addr != "0000")) {

        //         $eqLogic = eqLogic::byLogicalId($dest."/".$ShortFound, 'Abeille');
        //         if (!is_object($eqLogic)) {
        //             log::add('Abeille', 'debug', 'message(), !objet & IEEE: L\'équipement ne semble pas sur la bonne zigate. Abeille ne fait rien automatiquement. L\'utilisateur doit résoudre la situation.');
        //             return;
        //         }

        //         // log::add('Abeille', 'debug', "message(), !objet & IEEE: Adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - ".$eqLogic->getName().", on fait la mise a jour automatique");
        //         log::add('Abeille', 'debug', "message(), !objet & IEEE: $value correspond à '".$eqLogic->getHumanName()."'. Mise-à-jour de l'adresse courte $ShortFound vers $addr.");
        //         // Comme c est automatique des que le retour d experience sera suffisant, on n alerte pas l utilisateur. Il n a pas besoin de savoir
        //         // message::add("Abeille", "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - ".$eqLogic->getName().", on fait la mise a jour automatique", '');
        //         message::add("Abeille", "Nouvelle adresse '".$addr."' pour '".$eqLogic->getHumanName()."'. Mise à jour automatique.");

        //         // Si on trouve l adresse dans le nom, on remplace par la nouvelle adresse
        //         // log::add('Abeille', 'debug', "!objet&IEEE --> IEEE-Addr; Ancien nom: ".$eqLogic->getName().", nouveau nom: ".str_replace($ShortFound, $addr, $eqLogic->getName()));
        //         // $eqLogic->setName(str_replace($ShortFound, $addr, $eqLogic->getName()));

        //         $eqLogic->setLogicalId($dest."/".$addr);
        //         $eqLogic->setConfiguration('topic', $dest."/".$addr);
        //         $eqLogic->save();

        //         // Il faut aussi mettre a jour la commande short address
        //         Abeille::publishMosquitto($abQueues["xToAbeille"]["id"], priorityInterrogation, $dest."/".$addr."/Short-Addr", $addr);
        //     } else {
        //         log::add('Abeille', 'debug', 'message(), !objet & IEEE: Je n ai pas trouvé d Abeille qui corresponde.');
        //         // self::interrogateUnknowNE( $dest, $addr );
        //     }
        //     // log::add('Abeille', 'debug', '!objet&IEEE --> fin du traitement');
        //     return;
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande => je drop la cmd but I try to get the device into Abeille
        // e.g. un Equipement envoie des infos, mais l objet n existe pas dans Jeedom
        // if (!is_object($eqLogic)) {

        //     // Je ne fais les demandes que si les commandes ne sont pas Time-Time, Time-Stamp et Link-Quality
        //     if (!preg_match("(Time|Link-Quality)", $topic)) {

        //         if (!Abeille::checkInclusionStatus($dest)) {
        //             log::add('Abeille', 'info', 'Des informations remontent pour un equipement inconnu d Abeille avec pour adresse '.$addr.' et pour la commande '.$cmdId );
        //         }

        //         // self::interrogateUnknowNE( $dest, $addr );
        //     }

        //     return;
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet exist et on recoie une IEEE
        // e.g. Un NE renvoie son annonce
        // if (is_object($eqLogic) && ($cmdId == "IEEE-Addr")) {

        //     // Je rejete les valeur null (arrive avec les equipement xiaomi qui envoie leur nom spontanement alors que l IEEE n est pas recue.
        //     if (strlen($value) < 2) {
        //         log::add('Abeille', 'debug', 'IEEE-Addr; =>'.$value.'<= ; IEEE non valable pour un equipement, valeur rejetée: '.$addr.": IEEE =>".$value."<=");
        //         return;
        //     }

        //     // Je ne sais pas pourquoi des fois on recoit des IEEE null
        //     if ($value == "0000000000000000") {
        //         log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';IEEE recue est null, je ne fais rien.');
        //         return;
        //     }

        //     // ffffffffffffffff remonte avec les mesures LQI si nouveau equipements.
        //     if ($value == "FFFFFFFFFFFFFFFF") {
        //         log::add('Abeille', 'debug', 'IEEE-Addr; =>'.$value.'<= ; IEEE non valable pour un equipement, valeur rejetée: '.$addr.": IEEE =>".$value."<=");
        //         return;
        //     }

        //     // Update IEEE cmd
        //     if (!is_object($cmdLogic)) {
        //         log::add('Abeille', 'debug', 'IEEE-Addr commande n existe pas');
        //         return;
        //     }

        //     // $IEEE = $cmdLogic->execCmd();
        //     $IEEE = $eqLogic->getConfiguration('IEEE','None');
        //     if ( ($IEEE!=$value) && (strlen($IEEE)==16) ) {
        //         log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';Alerte changement de l adresse IEEE pour un equipement !!! '.$addr.": ".$IEEE." =>".$value."<=");
        //         message::add("Abeille", "Alerte changement de l adresse IEEE pour un equipement !!! ( $addr : $IEEE =>$value<= )", '');
        //     }

        //     $eqLogic->checkAndUpdateCmd($cmdLogic, $value);
        //     $eqLogic->setConfiguration('IEEE', $value);
        //     $eqLogic->save();
        //     $eqLogic->refresh();

        //     log::add('Abeille', 'debug', '  IEEE-Addr cmd and eq updated: '.$eqLogic->getName().' - '.$eqLogic->getConfiguration('IEEE', 'Unknown') );

        //     return;
        // }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si equipement et cmd existe alors on met la valeur a jour
        if (is_object($eqLogic) && is_object($cmdLogic)) {
            /* Traitement particulier pour la remontée de nom qui est utilisé pour les ping des routeurs */
            // if (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) {
            // if (preg_match("/^0000-[0-9A-F]*-*0005/", $cmdId) || preg_match("/^0000-[0-9A-F]*-*0010/", $cmdId)) {
            // else
            if ($cmdId == "Time-TimeStamp") {
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

            // Abeille::infoCmdUpdate($eqLogic, $cmdLogic, $value);

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

        // if (is_object($eqLogic) && !is_object($cmdLogic)) {
        //     log::add('Abeille', 'debug', "  L'objet '".$nodeid."' existe mais pas la cmde '".$cmdId."' => message ignoré");
        //     return;
        // }

        log::add('Abeille', 'debug', "  WARNING: Unexpected or no longer supported message.");
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
            $trigName = $trigCmd->getName();
            log::add('Abeille', 'debug', "  Triggering cmd '".$trigName."' (".$trigLogicId.") with val=".$trigValue);
            $eqLogic->checkAndUpdateCmd($trigCmd, $trigValue);
        }

        if (preg_match("/^0001-[0-9A-F]*-0021/", $trigLogicId)) {
            $trigValue = round($trigValue, 0);
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

    public static function checkZgIeee($net, $ieee) {
        $zgId = substr($net, 7);
        $keyIeee = str_replace('Abeille', 'ab::zgIeeeAddr', $net); // AbeilleX => ab::zgIeeeAddrX
        $keyIeeeOk = str_replace('Abeille', 'ab::zgIeeeAddrOk', $net); // AbeilleX => ab::zgIeeeAddrOkX
        if (config::byKey($keyIeeeOk, 'Abeille', 0) == 0) {
            $ieeeConf = config::byKey($keyIeee, 'Abeille', '');
            if ($ieeeConf == "") {
                config::save($keyIeee, $ieee, 'Abeille');
                config::save($keyIeeeOk, 1, 'Abeille');
            } else if ($ieeeConf == $ieee) {
                config::save($keyIeeeOk, 1, 'Abeille');
            } else {
                config::save($keyIeeeOk, -1, 'Abeille');
                message::add("Abeille", "Attention: La zigate ".$zgId." semble nouvelle ou il y a eu échange de ports. Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
            }
        }
    }

    // Check if received attribute is a battery information
    public static function checkIfBatteryInfo($eqLogic, $attrName, $attrVal) {
        // if ($attrName == "Battery-Volt") { // Obsolete
        //     $attrVal = round($attrVal, 0);
        //     $eqLogic->setStatus('battery', self::volt2pourcent($attrVal));
        //     $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        // } else
        if (($attrName == "Battery-Percent") || ($attrName == "Batterie-Pourcent")) {  // Obsolete
            $attrVal = round($attrVal, 0);
            log::add('Abeille', 'debug', "  Battery % reporting: ".$attrName.", val=".$attrVal);
            $eqLogic->setStatus('battery', $attrVal);
            $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        }  else if (preg_match("/^0001-[0-9A-F]*-0021/", $attrName)) {
            $attrVal = round($attrVal, 0);
            log::add('Abeille', 'debug', "  Battery % reporting: ".$attrName.", val=".$attrVal);
            $eqLogic->setStatus('battery', $attrVal);
            $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
        }
    }

    /* Deal with messages coming from parser or cmd processes.
       Note: this is the new way to handle messages from parser, replacing progressively 'message()' */
    public static function msgFromParser($msg) {
        global $abQueues;

        $net = $msg['net'];
        if (isset($msg['addr']))
            $addr = $msg['addr'];
        if (isset($msg['ep']))
            $ep = $msg['ep'];
        else
            $ep = '';

        /* Parser has found a new device. Basic Jeedom entry to be created. */
        if ($msg['type'] == "newDevice") {
            $ieee = $msg['ieee'];
            log::add('Abeille', 'debug', "msgFromParser(): New device: ".$net.'/'.$addr.", ieee=".$ieee);

            Abeille::newJeedomDevice($net, $addr, $ieee);
            return;
        } // End 'newDevice'

        /* Transmit status has changed. */
        if ($msg['type'] == "eqTxStatusUpdate") {
            log::add('Abeille', 'debug', "msgFromParser(): TX status update: ".$net.'/'.$addr.", status=".$msg['txStatus']);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (is_object($eqLogic)) {
                $eqLogic->setStatus('ab::txAck', $msg['txStatus']); // ab::txAck == 'ok' or 'noack'
                $eqLogic->save();
            } else {
                log::add('Abeille', 'error', "msgFromParser(eqTxStatusUpdate): Equipement inconnu: ".$net.'/'.$addr);
            }

            return;
        } // End 'eqTxStatusUpdate'

        /* Parser has found device infos to update. */
        if ($msg['type'] == "deviceUpdates") {
            log::add('Abeille', 'debug', "msgFromParser(): ".$net.'/'.$addr.'/'.$ep.", Device updates, ".json_encode($msg, JSON_UNESCAPED_SLASHES));

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            $eqChanged = false;
            foreach ($msg['updates'] as $updKey => $updVal) {
                if ($updKey == 'ieee') {
                    if (!is_object($eqLogic)) {
                        $all = eqLogic::byType('Abeille');
                        foreach ($all as $eqLogic) {
                            $ieee2 = $eqLogic->getConfiguration('IEEE', '');
                            if ($ieee2 != $updVal)
                                continue;
                            $eqLogic->setLogicalId($net.'/'.$addr);
                            log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'addr' updated to ".$addr);
                            $eqLogic->setIsEnable(1);
                            $eqChanged = true;
                            break;
                        }
                    }
                } else if ($updKey == 'macCapa') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        $zigbee['macCapa'] = $updVal;
                        $mc = hexdec($zigbee['macCapa']);
                        $zigbee['mainsPowered'] = ($mc >> 2) & 0b1; // 1=mains-powered
                        $zigbee['rxOnWhenIdle'] = ($mc >> 3) & 0b1;
                        $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                        log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[macCapa]' updated to ".$updVal);
                        $eqChanged = true;
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'rxOnWhenIdle') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        $zigbee['rxOnWhenIdle'] = $updVal;
                        $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                        log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[rxOnWhenIdle]' updated to ".$updVal);
                        $eqChanged = true;
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'endPoints') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        $zigbee['endPoints'] = $updVal;
                        $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                        log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints]' updated to ".json_encode($updVal));
                        $eqChanged = true;
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'servClusters') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        $zigbee['endPoints'][$ep]['servClusters'] = $updVal;
                        if (strpos($updVal, '0004') !== false)
                            $zigbee['groups'][$ep] = '';
                        $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                        log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints]['.$ep.']['servClusters']' updated to ".json_encode($updVal));
                        $eqChanged = true;
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'manufId') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        if (!isset($zigbee['endPoints'][$ep]['manufId'])) {
                            $zigbee['endPoints'][$ep]['manufId'] = $updVal;
                            $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                            log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints]['.$ep.']['manufId']' updated to ".json_encode($updVal));
                            $eqChanged = true;
                        }
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'modelId') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        if (!isset($zigbee['endPoints'][$ep]['modelId'])) {
                            $zigbee['endPoints'][$ep]['modelId'] = $updVal;
                            $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                            log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints]['.$ep.']['modelId']' updated to ".json_encode($updVal));
                            $eqChanged = true;
                        }
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'location') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        if (!isset($zigbee['endPoints'][$ep]['location'])) {
                            $zigbee['endPoints'][$ep]['location'] = $updVal;
                            $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                            log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[endPoints]['.$ep.']['location']' updated to ".json_encode($updVal));
                            $eqChanged = true;
                        }
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                } else if ($updKey == 'manufCode') {
                    if (is_object($eqLogic)) {
                        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                        $zigbee['manufCode'] = $updVal;
                        $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                        log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": 'ab::zigbee[manufCode]' updated to ".$updVal);
                        $eqChanged = true;
                    } else
                        log::add('Abeille', 'debug', '  ERROR: Unknown '.$net.'/'.$addr." device");
                }
            }
            if ($eqChanged)
                $eqLogic->save();
            return;
        } // End 'deviceUpdates'

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
                    'macCapa' => $eq['macCapa'],
                    'time' => time()
                ); */

            $logicalId = $net.'/'.$addr;
            $jsonId = $msg['jsonId'];
            $jsonLocation = $msg['jsonLocation']; // 'Abeille' or 'local'
            log::add('Abeille', 'debug', "msgFromParser(): Eq announce received for ".$net.'/'.$addr.", jsonId='".$jsonId."'".", jsonLoc='".$jsonLocation."'");

            $ieee = $msg['ieee'];

            $eqLogic = eqLogic::byLogicalId($logicalId, 'Abeille');
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
            $dev = array(
                'net' => $net,
                'addr' => $addr,
                'ieee' => $ieee,
                'modelId' => $msg['modelId'],
                'manufId' => $msg['manufId'],
                'jsonId' => $jsonId,
                'jsonLocation' => $jsonLocation,
                'macCapa' => $msg['macCapa']
            );
            Abeille::createDevice("update", $dev);

            $eqLogic = eqLogic::byLogicalId($logicalId, 'Abeille');
            $eqId = $eqLogic->getId();

            // /* MAC capa from 004D/Device announce message */
            // $mc = hexdec($msg['macCapa']);
            // $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            // $zigbee['macCapa'] = $msg['macCapa'];
            // $rxOnWhenIdle = ($mc >> 3) & 0b1;
            // $mainsPowered = ($mc >> 2) & 0b1;
            // if ($mainsPowered) // 1=mains-powererd
            //     $zigbee['mainsPowered'] = 1;
            // else
            //     $zigbee['mainsPowered'] = 0;
            // if ($rxOnWhenIdle) // 1=Receiver enabled when idle
            //     $zigbee['rxOnWhenIdle'] = 1;
            // else
            //     $zigbee['rxOnWhenIdle'] = 0;
            // $eqLogic->setConfiguration('ab::zigbee', $zigbee);
            // $eqLogic->save();

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "Short-Addr");
            if (is_object($cmdLogic))
                $ret = $eqLogic->checkAndUpdateCmd($cmdLogic, $addr);

            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "IEEE-Addr");
            if (is_object($cmdLogic))
                $eqLogic->checkAndUpdateCmd($cmdLogic, $ieee);

            $mc = hexdec($msg['macCapa']);
            $mainsPowered = ($mc >> 2) & 0b1;
            $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "Mains-Powered");
            if (!is_object($cmdLogic))
                $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqId, "Power-Source"); // Old name support
            if (is_object($cmdLogic))
                $eqLogic->checkAndUpdateCmd($cmdLogic, $mainsPowered);

            return;
        } // End 'eqAnnounce'

        /* Parser has found that a device has changed network. */
        if ($msg['type'] == "eqMigrated") {
            // Msg reminder:
            // 'type' => 'eqMigrated',
            // 'net' => $net,
            // 'addr' => $addr,
            // 'srcNet' => $oldNet,
            // 'srcAddr' => $oldAddr,

            $oldLogicId = $msg['srcNet'].'/'.$msg['srcAddr'];
            log::add('Abeille', 'debug', "msgFromParser(): Device migration from ".$oldLogicId." to ".$net."/".$addr);

            $eqLogic = eqLogic::byLogicalId($oldLogicId, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: ".$oldLogicId." is unknown");
                return;
            }

            // Moving eq to new network
            $eqLogic->setLogicalId($net.'/'.$addr);
            $eqLogic->setIsEnable(1);
            $eqLogic->save();
            message::add("Abeille", $eqLogic->getHumanName().": a migré vers le réseau ".$net, '');

            return;
        } // End 'eqMigrated'

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
            log::add('Abeille', 'debug', "msgFromParser(): Leave indication for ".$net."/".$ieee.", rejoin=".$msg['rejoin']);

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
                    message::add("Abeille", $eqLogic->getHumanName().": a quitté le réseau => désactivé.", '');
                $eqLogic->save();
                $eqLogic->refresh();
                log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().' has left the network => DISABLED');

                Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            } else
                log::add('Abeille', 'debug', 'msgFromParser(): WARNING: Device with IEEE '.$ieee.' NOT found in Jeedom');

            return;
        } // End 'leaveIndication'

        // Grouped attributes reporting (by Jeedom logical name)
        // Grouped read attribute responses (by Jeedom logical name)
        if (($msg['type'] == "attributesReportN") || ($msg['type'] == "readAttributesResponseN")) {
            /* $msg reminder
                'src' => 'parser',
                'type' => 'attributesReportN'/'readAttributesResponseN',
                'net' => $net,
                'addr' => $addr,
                'ep' => 'xx', // End point hex string
                'clustId' => $clustId,
                'attributes' => [],
                    'name' => 'xxx', // Attribut name = cmd logical ID (ex: 'clustId-ep-attrId')
                    'value' => false/xx, // False = unsupported
                'time' => time(),
                'lqi' => $lqi
            */

            if ($msg['type'] == "attributesReportN")
                log::add('Abeille', 'debug', "msgFromParser(): Attributes report by name from '".$net."/".$addr."/".$ep);
            else
                log::add('Abeille', 'debug', "msgFromParser(): Read attributes response by name from '".$net."/".$addr."/".$ep);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  Unknown device '".$net."/".$addr."'");
                return; // Unknown device
            }

            foreach ($msg['attributes'] as $attr) {
                $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $attr['name']);
                if (!is_object($cmdLogic)) {
                    log::add('Abeille', 'debug', "  Unknown Jeedom command logicId='".$attr['name']."'");
                } else {
                    $cmdName = $cmdLogic->getName();
                    $unit = $cmdLogic->getUnite();
                    if ($unit === null)
                        $unit = '';
                    // WARNING: value might not be the final one if 'calculValueOffset' is used
                    $cvo = $cmdLogic->getConfiguration('calculValueOffset', '');
                    if ($cvo == '')
                        log::add('Abeille', 'debug', "  '".$cmdName."' (".$attr['name'].") => ".$attr['value']." ".$unit);
                    else
                        log::add('Abeille', 'debug', "  '".$cmdName."' (".$attr['name'].") => ".$attr['value']." (calculValueOffset=".$cvo.")");
                    $eqLogic->checkAndUpdateCmd($cmdLogic, $attr['value']);

                    // Check if any action cmd must be executed triggered by this update
                    Abeille::infoCmdUpdate($eqLogic, $cmdLogic, $attr['value']);

                    // Checking if battery info, only if registered command
                    Abeille::checkIfBatteryInfo($eqLogic, $attr['name'], $attr['value']);
                }
            }

            Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'attributesReportN' or 'readAttributesResponseN'

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
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }

            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'FW-Version');
            if ($cmdLogic) {
                $savedVersion = $cmdLogic->execCmd(); // MMMM-mmmm
                $version = $msg['major'].'-'.$msg['minor'];
                if ($savedVersion != $version)
                    log::add('Abeille', 'debug', '  FW saved version: '.$savedVersion);
                if ($savedVersion == '---------') {
                    $zgId = substr($net, 7);
                    if ($msg['major'] == 'AB01') { // Abeille's FW for Zigate v1
                        log::add('Abeille', 'debug', '  FW version AB01 => Configuring zigate '.$zgId.' in hybrid mode');
                        Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetMode", "mode=hybrid");
                    } else {
                        if (hexdec($msg['minor']) >= 0x031D) {
                            log::add('Abeille', 'debug', '  FW version >= 3.1D => Configuring zigate '.$zgId.' in hybrid mode');
                            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetMode", "mode=hybrid");
                        } else {
                            log::add('Abeille', 'debug', '  Old FW. Configuring zigate '.$zgId.' in normal mode');
                            Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "CmdAbeille".$zgId."/0000/zgSetMode", "mode=normal");
                        }
                        // TODO: Different msg according to v1 or v2
                        if (hexdec($msg['minor']) < 0x0321) {
                            if (hexdec($msg['minor']) < 0x031E)
                                message::add('Abeille', 'Attention: La zigate '.$zgId.' fonctionne avec un trop vieux FW incompatible avec Abeille. Merci de faire une mise-à-jour en 3.21 ou supérieur.');
                            else
                                message::add('Abeille', "Il est recommandé de mettre à jour votre Zigate avec la version '3.21'.");
                        }
                    }
                }
                $eqLogic->checkAndUpdateCmd($cmdLogic, $version);
            }

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'zigateVersion'

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
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'ZiGate-Time');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['timeServer']);

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'zigateTime'

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
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'ZiGate-Power');
            if ($cmdLogic)
                $eqLogic->checkAndUpdateCmd($cmdLogic, $msg['power']);

            Abeille::updateTimestamp($eqLogic, $msg['time']);

            return;
        } // End 'zigatePower'

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

            Abeille::checkZgIeee($net, $msg['ieee']);

            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  ERROR: No zigate for network ".$net);
                return;
            }
            // $ieee = $eqLogic->getConfiguration('IEEE', '');
            // if (($ieee != '') && ($ieee != $msg['ieee'])) {
            //     log::add('Abeille', 'debug', "  ERROR: IEEE mistmatch, got ".$msg['ieee']." while expecting ".$ieee);
            //     return;
            // }
            $curIeee = $eqLogic->getConfiguration('IEEE', '');
            if ($curIeee != $msg['ieee']) {
                $eqLogic->setConfiguration('IEEE', $msg['ieee']);
                $eqLogic->save();
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
        } // End 'networkState'

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

            Abeille::checkZgIeee($net, $msg['ieee']);

            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
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
        } // End 'networkStarted'

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
            $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
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
        } // End 'permitJoin'

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

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if ($eqLogic)
                Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);

            return;
        } // End 'bindResponse'

        /* IEEE address response (8041 response) */
        if ($msg['type'] == "ieeeAddrResponse") {
            // 'type' => 'ieeeAddrResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ieee' => $ieee,
            // 'time' => time(),
            // 'lqi' => $lqi,
            log::add('Abeille', 'debug', "msgFromParser(): ".$net."/".$addr.", IEEE addr response, ieee=".$msg['ieee']);

            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
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

        /* AddGroup/removeGroup/getGroupMembership responses */
        if (($msg['type'] == "addGroupResponse") || ($msg['type'] == "removeGroupResponse") || ($msg['type'] == "getGroupMembershipResponse")) {
            // // 'src' => 'parser',
            // 'type' => 'addGroupResponse'/'removeGroupResponse'/'getGroupMembershipResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ep' => $srcEp,
            // 'groups' => $grp
            // 'time' => time(),
            // 'lqi' => $lqi
            log::add('Abeille', 'debug', "msgFromParser(): ".$net."/".$addr."/".$ep.", ".$msg['type'].", groups=".$msg['groups']);
            $eqLogic = eqLogic::byLogicalId($net.'/'.$addr, 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'debug', "  Unknown device '".$net."/".$addr."'");
                return; // Unknown device
            }

            $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
            $newZigbee = $zigbee;
            if (!isset($newZigbee['groups']))
                $newZigbee['groups'] = [];
            if (!isset($newZigbee['groups'][$ep]))
                $newZigbee['groups'][$ep] = '';
            $groups = $newZigbee['groups'][$ep];
            if ($msg['type'] == 'getGroupMembershipResponse') {
                $groups = $msg['groups'];
            } else if ($msg['type'] == 'addGroupResponse') {
                if ($groups == '')
                    $groups = $msg['groups'];
                else
                    $groups .= '/'.$msg['groups'];
            } else { // removeGroupResponse
                $groupsArr = explode("/", $groups);
                foreach ($groupsArr as $gIdx => $g) {
                    if ($g == $msg['groups'])
                        unset($groupsArr[$gIdx]);
                }
                $groups = implode("/", $groupsArr);
            }
            $newZigbee['groups'][$ep] = $groups;
            if ($newZigbee != $zigbee) {
                $eqLogic->setConfiguration('ab::zigbee', $newZigbee);
                $eqLogic->save();
            }

            Abeille::updateTimestamp($eqLogic, $msg['time'], $msg['lqi']);
            return;
        } // End 'addGroupResponse'/'removeGroupResponse'/'getGroupMembershipResponse'

        log::add('Abeille', 'debug', "msgFromParser(): Ignored msg ".json_encode($msg));
    } // End msgFromParser()

    public static function publishMosquitto($queueId, $priority, $topic, $payload) {
        static $queueStatus = []; // "ok" or "error"

        $queue = msg_get_queue($queueId);
        if ($queue === false) {
            log::add('Abeille', 'error', "publishMosquitto(): La queue ".$queueId." n'existe pas. Message ignoré.");
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

        // $config = AbeilleTools::getConfig();

        $msg = array();
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg);

        if (msg_send($queue, $priority, $msgJson, false, false, $error_code)) {
            log::add('Abeille', 'debug', "  publishMosquitto(): Envoyé '".$msgJson."' vers queue ".$queueId);
            $queueStatus[$queueId] = "ok"; // Status ok
        } else
            log::add('Abeille', 'warning', "publishMosquitto(): Impossible d'envoyer '".$msgJson."' vers queue ".$queueId);
    } // End publishMosquitto()

    public static function msgToCmd($priority, $topic, $payload = "") {
        static $queueStatus = []; // "ok" or "error"

        $abQueues = $GLOBALS['abQueues'];
        $queueId = $abQueues['xToCmd']['id'];
        $queue = msg_get_queue($queueId);
        if ($queue === false) {
            log::add('Abeille', 'error', "msgToCmd(): La queue ".$queueId." n'existe pas => Message ignoré.");
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

        // $config = AbeilleTools::getConfig();

        $msg = array();
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg);

        if (msg_send($queue, $priority, $msgJson, false, false, $error_code)) {
            log::add('Abeille', 'debug', "  msgToCmd(): Envoyé '".$msgJson."' vers queue ".$queueId);
            $queueStatus[$queueId] = "ok"; // Status ok
        } else
            log::add('Abeille', 'warning', "msgToCmd(): Impossible d'envoyer '".$msgJson."' vers queue ".$queueId);
    } // End msgToCmd()

    // Beehive creation/update function. Called on daemon startup or new beehive creation.
    public static function createRuche($dest) {
        $eqLogic = eqLogic::byLogicalId($dest."/0000", 'Abeille');
        if (!is_object($eqLogic)) {
            message::add("Abeille", "Création de l'équipement 'Ruche' en cours. Rafraichissez votre dashboard dans qq secondes.", '');
            log::add('Abeille', 'info', 'Ruche: Création de '.$dest."/0000");
            $eqLogic = new Abeille();
            //id
            $eqLogic->setName("Ruche-".$dest);
            $eqLogic->setLogicalId($dest."/0000");
            $config = AbeilleTools::getConfig();
            if ($config['ab::defaultParent'] > 0) {
                $eqLogic->setObject_id($config['ab::defaultParent']);
            } else {
                $eqLogic->setObject_id(jeeObject::rootObject()->getId());
            }
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setConfiguration('topic', $dest."/0000");
            // $eqLogic->setConfiguration('type', 'topic'); // Tcharp38: What for ?
            // $eqLogic->setConfiguration('lastCommunicationTimeOut', '-1');
            $eqLogic->setIsVisible("0");
            $eqLogic->setConfiguration('ab::icon', "Ruche");
            $eqLogic->setTimeout(5); // timeout en minutes
            $eqLogic->setIsEnable("1");
        } else {
            // TODO: If already exist, should we update commands if required ?
            log::add('Abeille', 'debug', "createRuche(): '".$eqLogic->getLogicalId()."' already exists");
        }

        $eqLogic->setConfiguration('mainEP', '01');

        // JSON model infos
        $eqModelInfos = array(
            'id' => 'rucheCommand', // Equipment model id
            'location' => 'Abeille', // Equipment model location
            'type' => 'Zigate',
            'lastUpdate' => time() // Store last update from model
        );
        $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

        // Note: initializing 'groups' support. Simple descriptor response does not show cluster 0004 for EP01 (see https://github.com/fairecasoimeme/ZiGate/issues/409)
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        if (!isset($zigbee['groups']))
            $zigbee['groups'] = [];
        if (!isset($zigbee['groups']['01']))
                $zigbee['groups']['01'] = '';
        $eqLogic->setConfiguration('ab::zigbee', $zigbee);

        $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $eqLogic->save();

        $rucheCommandList = AbeilleTools::getJSonConfigFiles('rucheCommand.json', 'Abeille');

        // // Only needed for debug and dev so by default it's not done.
        // if (0) {
        //     $i = 100;

        //     //Load all commandes from defined objects (except ruche), and create them hidden in Ruche to allow debug and research.
        //     $items = AbeilleTools::getDeviceNameFromJson('Abeille');

        //     foreach ($items as $item) {
        //         $AbeilleObjetDefinition = AbeilleTools::getJSonConfigFilebyDevices(AbeilleTools::getTrimmedValueForJsonFiles($item), 'Abeille');
        //         // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
        //         foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
        //             $rucheCommandList[$objetId] = array(
        //                 "name" => $objetId,
        //                 "order" => $i++,
        //                 "isVisible" => "0",
        //                 "isHistorized" => "0",
        //                 "Type" => "action",
        //                 "subType" => "other",
        //                 "configuration" => array(
        //                     "topic" => "CmdCreate/".$objetId."/0000-0005",
        //                     "request" => $objetId,
        //                     "visibilityCategory" => "additionalCommand",
        //                     "visibiltyTemplate" => "0"
        //                 ),
        //             );
        //         }
        //     }
        //     // print_r($rucheCommandList);
        // }

        // Removing obsolete commands by their logical ID (unique)
        $cmds = Cmd::byEqLogicId($eqLogic->getId());
        foreach ($cmds as $cmdLogic) {
            $found = false;
            $cmdName = $cmdLogic->getName();
            $cmdLogicId = $cmdLogic->getLogicalId();
            foreach ($rucheCommandList as $cmdLogicId2 => $mCmd) {
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
        foreach ($rucheCommandList as $cmdLogicId => $mCmd) {
            $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdLogicId);
            if (!$cmdLogic) {
                $cmdJName = $mCmd["name"]; // Jeedom cmd name
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

            if ($mCmd["Type"] == "action") {
                // $cmdLogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd);
                $cmdLogic->setConfiguration('topic', $cmdLogicId);

                // Tcharp38: work in progress. Adding support for linked commands
                // Note: Error if info cmd is not registered BEFORE action cmd.
                // if (isset($mCmd["value"])) {
                //     // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                //     log::add('Abeille', 'debug', 'Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$mCmd["value"]);

                //     $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $mCmd["value"]);
                //     $cmdLogic->setValue($cmdPointeur_Value->getId());
                // }
            } else {
                // $cmdLogic->setConfiguration('topic', $nodeid.'/'.$cmd);
                $cmdLogic->setConfiguration('topic', $cmdLogicId);
            }
            // if ($mCmd["Type"] == "action") {  // not needed as mosquitto is not used anymore
            //    $cmdLogic->setConfiguration('retain', '0');
            // }
            if (isset($mCmd["configuration"])) {
                foreach ($mCmd["configuration"] as $confKey => $confValue) {
                    $cmdLogic->setConfiguration($confKey, $confValue);
                }
            }
            $cmdLogic->setType($mCmd["Type"]);
            $cmdLogic->setSubType($mCmd["subType"]);

            // Todo only if new command
            if ($newCmd) {
                if (isset($mCmd["isHistorized"])) $cmdLogic->setIsHistorized($mCmd["isHistorized"]);
                if (isset($mCmd["template"])) $cmdLogic->setTemplate('dashboard', $mCmd["template"]);
                if (isset($mCmd["template"])) $cmdLogic->setTemplate('mobile', $mCmd["template"]);
                if (isset($mCmd["invertBinary"])) $cmdLogic->setDisplay('invertBinary', '0');
                if (isset($mCmd["isVisible"])) $cmdLogic->setIsVisible($mCmd["isVisible"]);
                if (isset($mCmd["display"])) {
                    foreach ($mCmd["display"] as $confKey => $confValue) {
                        // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                        $cmdLogic->setDisplay($confKey, $confValue);
                    }
                }
            }

            // Whatever existing or new beehive, it is key to reset the following points
            if ($cmdLogicId == 'FW-Version')
                // $cmdLogic->setValue('----'); // Indicate FW version is invalid
                $cmdLogic->setCache('value', '---------'); // Indicate FW version is invalid

            $cmdLogic->save();
        }
    } // End createRuche()

    // Create a basic Jeedom device
    public static function newJeedomDevice($net, $addr, $ieee) {
        log::add('Abeille', 'debug', '  newJeedomDevice('.$net.', addr='.$addr.')');

        $logicalId = $net.'/'.$addr;
        $eqLogic = new Abeille();
        $eqLogic->setEqType_name('Abeille');
        $eqLogic->setName("newDevice-".$addr); // Temp name to have it non empty
        $eqLogic->save(); // Save to force Jeedom to assign an ID
        $eqName = $net."-".$eqLogic->getId(); // Default name (ex: 'Abeille1-12')
        $eqLogic->setName($eqName);
        $eqLogic->setLogicalId($logicalId);
        $abeilleConfig = AbeilleTools::getConfig();
        $eqLogic->setObject_id($abeilleConfig['ab::defaultParent']);
        $eqLogic->setConfiguration('IEEE', $ieee);
        $eqLogic->setIsVisible(0); // Hidden by default
        $eqLogic->setIsEnable(1);
        $eqLogic->save();
    } // End newJeedomDevice()

    /* Create or update Jeedom device based on its JSON model.
       Called in the following cases
       - On 'eqAnnounce' message from parser (device announce) => action = 'update'
       - To create/update a virtual 'remotecontrol' => action = 'update'
       - To update from JSON (identical to re-inclusion) => action = 'update'
     */
    public static function createDevice($action, $dev) {
        log::add('Abeille', 'debug', 'createDevice('.$action.', dev='.json_encode($dev));

        /* $action reminder
              'update' => create or update device (device announce/update)
              'reset' => create or reset device from model (user request)
           $dev reminder
                $dev = array(
                    'net' =>
                    'addr' =>
                    'jsonId' => 'remotecontrol',
                    'jsonLocation' => 'Abeille',
                );
         */

        $modelSig = isset($dev['modelSig']) ? $dev['modelSig']: '';
        $jsonId = isset($dev['jsonId']) ? $dev['jsonId']: '';
        $jsonLocation = isset($dev['jsonLocation']) ? $dev['jsonLocation']: '';
        if ($jsonLocation == '')
            $jsonLocation = 'Abeille';

        $eqLogicId = $dev['net'].'/'.$dev['addr'];
        $eqLogic = eqLogic::byLogicalId($eqLogicId, 'Abeille');

        // Special case: if the equipment already exists, and the user has forced the model,
        // we keep the current model and ignore the zigbee signature (case of re-announcement)
        $isModelForcedByUser = false;
        if(is_object($eqLogic)){
            $jEqModel = $eqLogic->getConfiguration('ab::eqModel', []); // Eq model from Jeedom DB
            if(isset($jEqModel['id']) && isset($jEqModel['location']) && isset($jEqModel['forcedByUser']) && ($jEqModel['forcedByUser'] == true)){
                $jsonLocation = $jEqModel['location'];
                $jsonId = $jEqModel['id'];
                $isModelForcedByUser = true;
            }
        }

        if ($jsonId != '' && $jsonLocation != '') {
            $model = AbeilleTools::getDeviceModel($modelSig, $jsonId, $jsonLocation);
            if ($model === false) {
                // log::add('Abeille', 'error', "  createDevice(jsonId=".$jsonId.", location=".$jsonLocation."): Unknown model");
                return;
            }
            log::add('Abeille', 'debug', '  Model='.json_encode($model, JSON_UNESCAPED_SLASHES));
            $modelType = $model['type'];
        }

        if (!is_object($eqLogic)) {
            $newEq = true;

            // if ($action != 'create') {
            //     log::add('Abeille', 'debug', '  ERROR: Action='.$action.' but device '.$eqLogicId.' does not exist');
            //     return;
            // }

            // $action == 'create'
            log::add('Abeille', 'debug', '  New device '.$eqLogicId);
            if ($jsonId != "defaultUnknown")
                message::add("Abeille", "Nouvel équipement identifié (".$modelType."). Création en cours. Rafraîchissez votre dashboard dans qq secondes.", '');
            else
                message::add("Abeille", "Nouvel équipement détecté mais non supporté. Création en cours avec la config par défaut (".$jsonId."). Rafraîchissez votre dashboard dans qq secondes.", '');

            $eqLogic = new Abeille();
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setName("newDevice-".$dev['addr']); // Temp name to have it non empty
            $eqLogic->save(); // Save to force Jeedom to assign an ID

            $eqId = $eqLogic->getId();
            $eqName = $modelType." - ".$eqId; // Default name (ex: '<modeltype> - 12')
            $eqLogic->setName($eqName);
            $eqLogic->setLogicalId($eqLogicId);
            $abeilleConfig = AbeilleTools::getConfig();
            $eqLogic->setObject_id($abeilleConfig['ab::defaultParent']);
            if (isset($dev['ieee'])) $eqLogic->setConfiguration('IEEE', $dev['ieee']); // No IEEE for virtual remote
        } else {
            $newEq = false;

            $eqHName = $eqLogic->getHumanName(); // Jeedom hierarchical name
            log::add('Abeille', 'debug', '  Already existing device '.$eqLogicId.' => '.$eqHName);

            // Kept for safety but should already be assigned in 'special case' block
            $jEqModel = $eqLogic->getConfiguration('ab::eqModel', []); // Eq model from Jeedom DB
            $curEqModel = isset($jEqModel['id']) ? $jEqModel['id'] : ''; // Current JSON model
            $ieee = $eqLogic->getConfiguration('IEEE'); // IEEE from Jeedom DB
            $eqId = $eqLogic->getId();

            if ($curEqModel == '') { // Jeedom eq exists but init not completed
                $eqName = $modelType." - ".$eqId; // Default name (ex: '<modeltype> - 12')
                $eqLogic->setName($eqName);
                message::add("Abeille", $eqHName.": Nouvel équipement identifié.", '');
                $action = 'reset';
            } else if (($curEqModel == 'defaultUnknown') && ($jsonId != 'defaultUnknown')) {
                message::add("Abeille", $eqHName.": S'est réannoncé. Mise-à-jour du modèle par défaut vers '".$modelType."'", '');
                $action = 'reset'; // Update from defaultUnknown = reset to new model
            }
            // else if ($action == "update")
            //     message::add("Abeille", $eqHName.": Mise-à-jour à partir de son modèle (source=".$jsonLocation.")");
            else if ($action == "reset")
                message::add("Abeille", $eqHName.": Réinitialisation à partir de '".$jsonId."' (source=".$jsonLocation.")");
            else { // action = create
                /* Tcharp38: Following https://github.com/KiwiHC16/Abeille/issues/2132#, device re-announce is just ignored here
                    to not generate plenty messages, unless device was disabled.
                    Other reasons to generate message ?
                */
                if ($eqLogic->getIsEnable() != 1)
                    message::add("Abeille", $eqHName.": S'est réannoncé. Mise-à-jour à partir de son modèle (source=".$jsonLocation.")");
            }

            // $eqModel = $eqLogic->getConfiguration('ab::eqModel', '');
            // $jsonId = $eqModel ? $eqModel['id'] : '';
            // $jsonLocation = $eqModel ? $eqModel['location'] : 'Abeille';
            // $model = AbeilleTools::getDeviceModel($jsonId, $jsonLocation);

            // if (($action == 'update') || ($action == 'reset')) { // Update or reset from JSON
            // } else { // action == create

            //     if (($curEqModel == 'defaultUnknown') && ($jsonId != 'defaultUnknown')) {
            //         message::add("Abeille", $eqHName.": S'est réannoncé. Mise-à-jour du modèle par défaut vers '".$modelType."'", '');
            //         $action = 'reset'; // Update from defaultUnknown = reset to new model
            //     } else {
            //         if ($eqLogic->getIsEnable() == 1) {
            //             // log::add('Abeille', 'debug', '  Device is already enabled. Doing nothing.');
            //             log::add('Abeille', 'debug', '  Device is already enabled.');
            //             // return; // Doing nothing on re-announce
            //         } else
            //         message::add("Abeille", $eqHName.": S'est réannoncé. Mise-à-jour en cours.", '');
            //     }
            // }
        }

        if ($jsonLocation == "local") {
            $fullPath = __DIR__."/../config/devices/".$jsonId."/".$jsonId.".json";
            if (file_exists($fullPath))
                message::add("Abeille", $eqHName.": Attention ! Modèle local (devices_local) utilisé alors qu'un modèle officiel existe.", '');
        }

        /* Whatever creation or update, common steps follows */
        $modelConf = $model["configuration"];
        log::add('Abeille', 'debug', '  modelConfig='.json_encode($modelConf));

        /* mainEP: Used to define default end point to target, when undefined in command itself (use of '#EP#'). */
        if (isset($modelConf['mainEP'])) {
            $mainEP = $modelConf['mainEP'];
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

        // Icon updated if no-longer-exists/reset/undefined/defaultUnknown
        $curIcon = $eqLogic->getConfiguration('ab::icon', '');
        if ($curIcon != '') {
            $iconPath = __DIR__.'/../../images/node_'.$curIcon.'.png';
            $iconExists = file_exists($iconPath);
        } else {
            $iconPath = '';
            $iconExists = false;
        }
        log::add('Abeille', 'debug', 'LA iconExists='.$iconExists.', path='.$iconPath);
        if (!$iconExists || ($action == 'reset') || ($curIcon == '') || ($curIcon == 'defaultUnknown')) {
            if (isset($modelConf["icon"]))
                $icon = $modelConf["icon"];
            else
                $icon = '';
            $eqLogic->setConfiguration('ab::icon', $icon);
        }

        // Update only if new device (missing info) or reinit
        $curTimeout = $eqLogic->getTimeout(null);
        if (($action == 'reset') || ($curTimeout === null)) {
            if (isset($model["timeout"]))
                $eqLogic->setTimeout($model["timeout"]);
            else
                $eqLogic->setTimeout(null);
        }
        $curCats = $eqLogic->getCategory();
        if (($action == 'reset') || (count($curCats) == 0)) {
            if (isset($model["category"])) {
                $categories = $model["category"];
                $allCat = ["heating", "security", "energy", "light", "opening", "automatism", "multimedia", "default"];
                foreach ($allCat as $cat) { // Clear all
                    $eqLogic->setCategory($cat, "0");
                }
                foreach ($categories as $key => $value) {
                    $eqLogic->setCategory($key, $value);
                }
            }
            // TODO: If no category defined, default value to be set
        }

        // Update only if new device or reinit
        if (($action == 'reset') || $newEq) {
            // isVisible: Reseted when leaving network (ex: reset). Must be set when rejoin unless defined in model.
            if (isset($model["isVisible"]))
                $eqLogic->setIsVisible($model["isVisible"]);
            else
                $eqLogic->setIsVisible(1);
        }

        // Tcharp38: Seems no longer used
        // $lastCommTimeout = (array_key_exists("lastCommunicationTimeOut", $modelConf) ? $modelConf["lastCommunicationTimeOut"] : '-1');
        // $eqLogic->setConfiguration('lastCommunicationTimeOut', $lastCommTimeout);

        if (isset($modelConf['batteryType']))
            $eqLogic->setConfiguration('battery_type', $modelConf['batteryType']);
        else
            $eqLogic->setConfiguration('battery_type', null);

        if (isset($modelConf['paramType']))
            $eqLogic->setConfiguration('paramType', $modelConf['paramType']);
        if (isset($modelConf['Groupe'])) { // Tcharp38: What for ? Telecommande Innr - KiwiHC16: on doit pouvoir simplifier ce code. Mais comme c etait la premiere version j ai fait detaillé.
            $eqLogic->setConfiguration('Groupe', $modelConf['Groupe']);
        }

        // Temporary support for 'groupEPx' (to replace #GROUPEPx#)
        // Constant used to define remote control group per EP
        for ($g = 1; $g <= 8; $g++) {
            if (isset($modelConf['groupEP'.$g]))
                $eqLogic->setConfiguration('groupEP'.$g, $modelConf['groupEP'.$g]);
            else
                $eqLogic->setConfiguration('groupEP'.$g, null);
        }

        if (isset($modelConf['onTime'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('onTime', $modelConf['onTime']);
        }
        if (isset($modelConf['poll']))
            $eqLogic->setConfiguration('poll', $modelConf['poll']);
        else
            $eqLogic->setConfiguration('poll', null);

        // Tuya specific infos: OBSOLETE soon. Replaced by 'fromDevice'
        if (isset($model['tuyaEF00']))
            $eqLogic->setConfiguration('ab::tuyaEF00', $model['tuyaEF00']);
        else
            $eqLogic->setConfiguration('ab::tuyaEF00', null);

        // Xiaomi specific infos: OBSOLETE soon. Replaced by 'fromDevice'
        if (isset($model['xiaomi']))
            $eqLogic->setConfiguration('ab::xiaomi', $model['xiaomi']);
        else
            $eqLogic->setConfiguration('ab::xiaomi', null);

        // Zigbee & customization from model
        $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
        if (isset($model['customization'])) {
            $eqLogic->setConfiguration('ab::customization', $model['customization']);
            if (isset($model['customization']['macCapa'])) {
                $zigbee['macCapa'] = $model['customization']['macCapa'];
                log::add('Abeille', 'debug', "  'macCapa' forced to ".$zigbee['macCapa']);
            }
        } else {
            $eqLogic->setConfiguration('ab::customization', null);
            if (isset($dev['macCapa'])) {
                $zigbee['macCapa'] = $dev['macCapa'];
            }
        }
        if (isset($zigbee['macCapa'])) {
            $mc = hexdec($zigbee['macCapa']);
            $zigbee['mainsPowered'] = ($mc >> 2) & 0b1; // 1=mains-powered
            $zigbee['rxOnWhenIdle'] = ($mc >> 3) & 0b1; // 1=Receiver enabled when idle
        }
        $eqLogic->setConfiguration('ab::zigbee', $zigbee);

        // JSON model infos
        $eqModelInfos = array(
            'id' => $jsonId, // Equipment model id
            'location' => $jsonLocation, // Equipment model location
            'type' => $model['type'],
            'lastUpdate' => time(), // Store last update from model
            'forcedByUser' => $isModelForcedByUser
        );
        if (isset($model['fromDevice'])) // Private cluster or command specific infos
            $eqModelInfos['fromDevice'] = $model['fromDevice'];
        $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

        // generic_type
        if (isset($model['genericType']))
            $eqLogic->setGenericType($model['genericType']);
        else
            $eqLogic->setGenericType(null);

        $eqLogic->setIsEnable(1);
        $eqLogic->save();

        /* During commands creation #EP# must be replaced by proper endpoint.
           If not already done, using default (mainEP) value */
        if (isset($model['commands'])) {
            $modelCmds = $model['commands'];
            $modelCmds2 = json_encode($modelCmds, JSON_UNESCAPED_SLASHES);
            if (strstr($modelCmds2, '#EP#') !== false) {
                if ($mainEP == "") {
                    message::add("Abeille", "'mainEP' est requis mais n'est pas défini dans '".$jsonId.".json'", '');
                    $mainEP = "01";
                }

                log::add('Abeille', 'debug', '  mainEP='.$mainEP);
                $modelCmds2 = str_ireplace('#EP#', $mainEP, $modelCmds2);
                $modelCmds = json_decode($modelCmds2, true);
                log::add('Abeille', 'debug', '  Updated commands='.json_encode($modelCmds));
            }
        }

        /* Creating list of current Jeedom commands.
           jeedomCmds[jCmdId] = array(
                'name' =>
                'logicalId' =>
                'topic' =>
                'request' =>
                'obsolete' =>
           )
           Note: DO NOT split commands by info/action. It's key to be sure that both name & logicalId are UNIQUE */
        $jCmds = Cmd::byEqLogicId($eqId);
        $jeedomCmds = []; // List of current Jeedom commands
        foreach ($jCmds as $cmdLogic) {
            $cmdType = $cmdLogic->getType();
            $cmdName = $cmdLogic->getName(); // == jCmdName (Jeedom cmd name)
            $cmdLogicId = $cmdLogic->getLogicalId('');
            $cmdId = $cmdLogic->getId();
            $cmdTopic = $cmdLogic->getConfiguration('topic', '');
            $cmdReq = $cmdLogic->getConfiguration('request', '');
            if ($cmdType == 'info')
                log::add('Abeille', 'debug', "  Jeedom ".$cmdType.": name='".$cmdName."' ('".$cmdLogicId."'), id=".$cmdId);
            else
                log::add('Abeille', 'debug', "  Jeedom ".$cmdType.": name='".$cmdName."' ('".$cmdLogicId."'), id=".$cmdId.", topic='".$cmdTopic."', req='".$cmdReq."'");
            $c = array(
                'name' => $cmdName,
                'logicalId' => $cmdLogicId,
                'topic' => $cmdTopic, // action only
                'request' => $cmdReq, // action only
                'obsolete' => True
            );
            $jeedomCmds[$cmdId] = $c;
        }

        // Creating or updating commands based on model content.
        // Tcharp38: WARNING: Faced an issue with a command whose logicalId changed (SWBuildID, logicId=0000-01-4000 newLogicId=0000-03-4000)
        // How to handle such case ?
        $order = 0;
        foreach ($modelCmds as $mCmdName => $mCmd) {
            // Initial checks
            if (!isset($mCmd["type"])) {
                log::add('Abeille', 'error', "La commande '".$mCmdName."' n'a pas de 'type' défini => ignorée");
                continue;
            }
            $mCmdType = $mCmd["type"];
            if ($mCmdType == 'info') {
                if (!isset($mCmd["logicalId"]) || ($mCmd["logicalId"] == '')) {
                    log::add('Abeille', 'error', "La commande '".$mCmdName."' n'a pas de 'logicalId' défini => ignorée");
                    continue;
                }
            } else if ($mCmdType == 'action') {
                // Any checks ?
            } else {
                log::add('Abeille', 'error', "La commande '".$mCmdName."' a un type invalide (".$mCmdType.") => ignorée");
                continue;
            }

            if ($mCmdType == "action") {
                $mCmdTopic = isset($mCmd["configuration"]['topic']) ? $mCmd["configuration"]['topic'] : ''; // Abeille command name
                $mCmdReq = isset($mCmd["configuration"]['request']) ? $mCmd["configuration"]['request'] : ''; // Abeille command parameters
            }
            $mCmdLogicId = isset($mCmd["logicalId"]) ? $mCmd["logicalId"] : '';

            /* Looking for corresponding cmd in Jeedom.
               New or existing cmd ?
               Note that 'info' cmds are uniq thanks to their logicalId. Not the case so far for 'action' which may lead
               to cmd deleted and recreated if cmd name has changed, and therefore lead to orpheline cmd if deleted one
               was used somewhere in Jeedom.
             */
            // $cmdLogic = null;
            $cmdId = null;

            // Search by logical ID
            log::add('Abeille', 'debug', "  Searching by logical ID: '".$mCmdLogicId."'");
            foreach ($jeedomCmds as $jCmdId => $jCmd) {
                // Note: Cmd logical ID & names have to be unique
                // if (($jCmd['logicalId'] != $mCmdLogicId) && ($jCmd['name'] != $mCmdName))
                //     continue;
                if ($jCmd['logicalId'] != $mCmdLogicId)
                    continue;
                $cmdId = $jCmdId;
                $jeedomCmds[$jCmdId]['obsolete'] = False;
                break; // Found
            }

            // Search by name if still not found
            if ($cmdId === null) {
                log::add('Abeille', 'debug', "  Searching by name: '".$mCmdName."'");
                foreach ($jeedomCmds as $jCmdId => $jCmd) {
                    if (($jCmd['name'] != '') && ($jCmd['name'] != $mCmdName))
                        continue;
                    $cmdId = $jCmdId;
                    $jeedomCmds[$jCmdId]['obsolete'] = False;
                    break; // Found
                }
            }

            // Search by topic/request if still not found & 'action'
            if (($cmdId === null) && ($mCmdType == 'action')) {
                $mTopic = $mCmd["configuration"]['topic'];
                $mRequest = $mCmd["configuration"]['request'];
                log::add('Abeille', 'debug', "  Searching by topic/request='".$mTopic."/".$mRequest."'");
                foreach ($jeedomCmds as $jCmdId => $jCmd) {
                    if ($jCmd['topic'] != $mTopic)
                        continue;
                    if ($jCmd['request'] != $mRequest)
                        continue;
                    $cmdId = $jCmdId;
                    $jeedomCmds[$jCmdId]['obsolete'] = False;
                    break;
                }
            }

            if ($cmdId === null) { // Not found => new command
                $newCmd = true;
                if ($mCmdType == 'info')
                    log::add('Abeille', 'debug', "  Adding ".$mCmdType." '".$mCmdName."' (".$mCmdLogicId.")");
                else
                    log::add('Abeille', 'debug', "  Adding ".$mCmdType." '".$mCmdName."' (".$mCmdLogicId."), topic='".$mCmdTopic."', request='".$mCmdReq."'");
                $cmdLogic = new cmd();
                $cmdLogic->setEqLogic_id($eqId);
                $cmdLogic->setEqType('Abeille');
            } else {
                $newCmd = false;
                log::add('Abeille', 'debug', '  found: id='.$cmdId);
                $cmdLogic = cmd::byId($cmdId);
                $jCmdName = $cmdLogic->getName();
                $jCmdLogicId = $cmdLogic->getLogicalId();
                if ($mCmdType == 'info')
                    log::add('Abeille', 'debug', "  Updating ".$mCmdType." '".$jCmdName."' (".$jCmdLogicId.")");
                else {
                    log::add('Abeille', 'debug', "  Updating ".$mCmdType." '".$jCmdName."' (".$jCmdLogicId.") => logicId='".$mCmdLogicId."', topic='".$mCmdTopic."', request='".$mCmdReq."'");
                    $jeedomCmds[$cmdId]['topic'] = $mCmdTopic;
                    $jeedomCmds[$cmdId]['request'] = $mCmdReq;
                }
            }

            $cmdLogic->setType($mCmdType); // 'info' or 'action': Always updated in case type change for same name
            $cmdLogic->setSubType($mCmd["subType"]);
            $cmdLogic->setOrder($order++);
            $cmdLogic->setLogicalId($mCmdLogicId);
            if ($cmdId !== null)
                $jeedomCmds[$cmdId]['logicalId'] = $mCmdLogicId;

            // Updates only if reset or new command
            if (($action == 'reset') || $newCmd) {
                if (isset($mCmd["unit"]))
                    $cmdLogic->setUnite($mCmd["unit"]);
                else
                    $cmdLogic->setUnite(null); // Clear unit

                $cmdLogic->setName($mCmdName);

                if (isset($mCmd["isHistorized"]))
                    $cmdLogic->setIsHistorized($mCmd["isHistorized"]);
                else
                    $cmdLogic->setIsHistorized(0);

                if (isset($mCmd["isVisible"]))
                    $cmdLogic->setIsVisible($mCmd["isVisible"]);
                else
                    $cmdLogic->setIsVisible(0);
            }

            // Updates only if new command or reinit or missing entry
            $curGenericType = $cmdLogic->getGeneric_type();
            if ($curGenericType === null)
                $curGenericType = '';
            if (($action == 'reset') || $newCmd || ($curGenericType == '')) {
                if (isset($mCmd["genericType"]))
                    $cmdLogic->setGeneric_type($mCmd["genericType"]);
                else
                    $cmdLogic->setGeneric_type(null); // Clear generic type
            }
            $curDashbTemplate = $cmdLogic->getTemplate('dashboard', '');
            log::add('Abeille', 'debug', '  curDashbTemplate='.$curDashbTemplate);
            if (($action == 'reset') || $newCmd || ($curDashbTemplate == '')) {
                if (isset($mCmd["template"]) && ($mCmd["template"] != "")) {
                    log::add('Abeille', 'debug', '  Set dashboard template='.$mCmd["template"]);
                    $cmdLogic->setTemplate('dashboard', $mCmd["template"]);
                }
            }
            $curMobTemplate = $cmdLogic->getTemplate('mobile', '');
            if (($action == 'reset') || $newCmd || ($curMobTemplate == '')) {
                if (isset($mCmd["template"]) && ($mCmd["template"] != "")) {
                    $cmdLogic->setTemplate('mobile', $mCmd["template"]);
                }
            }

            if ($mCmdType == "info") { // info cmd
            } else { // action cmd
                if (isset($mCmd["value"])) {
                    // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                    log::add('Abeille', 'debug', '  Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$mCmd["value"]);

                    $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $mCmd["value"]);
                    if ($cmdPointeur_Value)
                        $cmdLogic->setValue($cmdPointeur_Value->getId());
                }
            }

            /* Updating command 'configuration' fields.
               In case of update, some fields may no longer be required ($unusedConfKeys).
               They are removed if not defined in JSON model. */
            $toRemove = ['visibilityCategory', 'historizeRound', 'execAtCreation', 'execAtCreationDelay', 'topic', 'Polling', 'RefreshData', 'listValue'];
            array_push($toRemove, 'ab::trigOut', 'ab::trigOutOffset', 'PollingOnCmdChange', 'PollingOnCmdChangeDelay', 'ab::notStandard');
            array_push($toRemove, 'ab::valueOffset');
            $rmOnlyIfReset = $toRemove;
            array_push($rmOnlyIfReset, 'minValue', 'maxValue', 'calculValueOffset', 'repeatEventManagement');
            // Abeille specific keys must be renamed when taken from model (ex: trigOut => ab::trigOut)
            $toRename = ['trigOut', 'trigOutOffset', 'notStandard', 'valueOffset'];
            if (isset($mCmd["configuration"])) {
                $mCmdConf = $mCmd["configuration"];

                foreach ($mCmdConf as $confKey => $confValue) {
                    // Trick for conversion 'key' => 'ab::key' for Abeille specifics
                    // Note: this is currently not applied to all Abeille specific fields.
                    if (in_array($confKey, $toRename))
                        $confKey = "ab::".$confKey;

                    $cmdLogic->setConfiguration($confKey, $confValue);

                    // $confKey is used => no cleanup required
                    if ($action == 'reset') {
                        $keyIdx = array_search($confKey, $rmOnlyIfReset);
                        unset($rmOnlyIfReset[$keyIdx]);
                    } else {
                        $keyIdx = array_search($confKey, $toRemove);
                        unset($toRemove[$keyIdx]);
                    }
                }
            }

            /* Removing any obsolete 'configuration' fields (those remaining in 'unusedConfKeys') */
            if ($action == 'reset')
                $toRm = $rmOnlyIfReset;
            else
                $toRm = $toRemove;
            foreach ($toRm as $confKey) {
                // If key is defined but set to null, no way to detect this. So force remove all the time.
                // if ($cmdLogic->getConfiguration($confKey) == null)
                //     continue;
                // log::add('Abeille', 'debug', '  Removing obsolete configuration key: '.$confKey);
                $cmdLogic->setConfiguration($confKey, null); // Removing config entry
            }

            // On conserve l info du template pour la visibility
            // Tcharp38: What for ? Not found where it is used
            // if (isset($mCmd["isVisible"]))
            //     $cmdLogic->setConfiguration("visibiltyTemplate", $mCmd["isVisible"]);

            // Display stuff is updated only if new eq or new cmd to not overwrite user changes
            if (($action == 'reset') || $newCmd) {
                if (isset($mCmd["invertBinary"]))
                    $cmdLogic->setDisplay('invertBinary', $mCmd["invertBinary"]);
                else
                    $cmdLogic->setDisplay('invertBinary', null);

                if (isset($mCmd["nextLine"])) {
                    if ($mCmd["nextLine"] == "after")
                        $cmdLogic->setDisplay('forceReturnLineAfter', 1);
                    else
                        $cmdLogic->setDisplay('forceReturnLineBefore', 1);
                } else {
                    $cmdLogic->setDisplay('forceReturnLineAfter', null);
                    $cmdLogic->setDisplay('forceReturnLineBefore', null);
                }
            }
            if (isset($mCmd["disableTitle"])) // Disable title part of a 'message' action cmd
                $cmdLogic->setDisplay('title_disable', $mCmd["disableTitle"]);
            else
                $cmdLogic->setDisplay('title_disable', null);

            // log::add('Abeille', 'debug', '  LA='.$cmdLogic->getName()." - ".$cmdLogic->getLogicalId());
            $cmdLogic->save();
            // if (isset($jCmdId) && isset($jeedomCmds[$jCmdId])) // Mark updated if cmd was already existing
            //     $jeedomCmds[$jCmdId]['obsolete'] = 0;
        }

        // Removing obsolete cmds
        // foreach ($jeedomCmdsInf as $jCmdId => $jCmd) {
        foreach ($jeedomCmds as $jCmdId => $jCmd) {
            if ($jCmd['obsolete'] == False)
                continue;
            log::add('Abeille', 'debug', "  Removing info '".$jCmd['name']."' (".$jCmd['logicalId'].")");
            $cmdLogic = cmd::byId($jCmdId);
            $cmdLogic->remove();
        }
        // foreach ($jeedomCmdsAct as $jCmdId => $jCmd) {
        //     if ($jCmd['obsolete'] == False)
        //         continue;
        //     log::add('Abeille', 'debug', "  Removing action '".$jCmd['name']."' (".$jCmd['logicalId'].")");
        //     $cmdLogic = cmd::byId($jCmdId);
        //     $cmdLogic->remove();
        // }

        // Inform cmd & parser that EQ config has changed
        $msg = array(
            'type' => "eqUpdated",
            'id' => $eqId,
        );
        Abeille::msgToCmd2($msg);
        Abeille::msgToParser($msg);

    } // End createDevice()

    public static function msgToParser($msg) {
        global $abQueues;
        $queue = msg_get_queue($abQueues['xToParser']['id']);
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
        msg_send($queue, 1, $msgJson, false, false);
        log::add('Abeille', 'debug', "  Msg to Parser: ".$msgJson);
    }

    public static function msgToCmd2($msg) {
        global $abQueues;
        $queue = msg_get_queue($abQueues['xToCmd']['id']);
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);
        msg_send($queue, 1, $msgJson, false, false);
        log::add('Abeille', 'debug', "  Msg to Cmd: ".$msgJson);
    }

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

        // Updating corresponding Zigate alive status too
        list($net, $addr) = explode("/", $eqLogicId);
        $zigate = eqLogic::byLogicalId($net.'/0000', 'Abeille');
        $zigate->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));
        // Warning: lastCommunication update is not transmitted not client as not an info cmd
    }
}
