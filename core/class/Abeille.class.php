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
    include_once __DIR__.'/AbeilleCmd.class.php'; // execute()
    include_once __DIR__.'/../../plugin_info/install.php'; // updateConfigDB()
    include_once __DIR__.'/../php/AbeilleLog.php'; // logGetLevelNumber()
    // include_once __DIR__.'/../php/AbeilleModels.php'; // library to deal with models => getModelsList()

    const shmSize = 50; // Shared memory max size

class Abeille extends eqLogic {
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
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            if (config::byKey('ab::gtwEnabled'.$gtwId, 'Abeille', 'N') == 'N')
                continue; // Disabled
            if (config::byKey('ab::gtwSubType'.$gtwId, 'Abeille', '') == 'WIFI')
                continue; // WIFI does not use a physical port

            if ($result != '')
                $result .= ", ";
            $result .= config::byKey('ab::gtwPort'.$gtwId, 'Abeille', '');
        }

        $return[] = array(
            'test' => 'Ports: ',            // title of the line
            'result' => $result,            // Text which be printed in the line
            'advice' => 'Ports utilisés',   // Text printed when mouse is on question mark icon
            'state' => true,                // Status du plugin: true line will be green, false line will be red.
        );

        return $return;
    }

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

    // /**
    //  * RefreshCmd
    //  * Execute all cmd to update cmd info (e.g: after a long stop of Abeille to  get all data)
    //  *
    //  * @param   none
    //  *
    //  * @return  Does not return anything as all action are triggered by sending messages in queues
    //  */
    // public static function refreshCmd() {
    //     global $abQueues;

    //     log::add('Abeille', 'debug', 'refreshCmd: start');
    //     $i=15;
    //     foreach (AbeilleCmd::searchConfiguration('RefreshData', 'Abeille') as $key => $cmd) {
    //         if ($cmd->getConfiguration('RefreshData',0)) {
    //             log::add('Abeille', 'debug', 'refreshCmd: '.$cmd->getHumanName().' ('.$cmd->getEqlogic()->getLogicalId().')' );
    //             // $cmd->execute(); le process ne sont pas tous demarrer donc on met une tempo.
    //             // $topic = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getLogicalId();
    //             $topic = $cmd->getEqlogic()->getLogicalId().'/'.$cmd->getConfiguration('topic');
    //             $request = $cmd->getConfiguration('request');
    //             Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$topic."&time=".(time()+$i), $request);
    //             $i++;
    //         }
    //     }
    //     log::add('Abeille', 'debug', 'refreshCmd: end');
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

        $pid = getmypid();
        log::add('Abeille', 'debug', "cron(): PID=$pid");

        /* Opening shared mem area (created by deamon_start()) */
        $shm = shmop_open(12, "w", 0644, 0);
        if ($shm === false) {
            log::add('Abeille', 'debug', 'cron(): FAILED to open shared mem');
            return;
        }
        $shmContent = rtrim(shmop_read($shm, 0, shmop_size($shm))); // rtrim mandatory for json_decode()
        log::add('Abeille', 'debug', 'cron(): shmContent='.$shmContent);
        $shmContent = json_decode($shmContent, true);
        $writeShm = false;
        if (!isset($shmContent['daemons'])) {
            $shmContent['daemons'] = [];
            $writeShm = true;
        }
    //     $smContent = [];
    //     if ($start)
    //         $smContent['daemonsPaused'] = true;
    //     else
    //         $smContent['daemonsPaused'] = false;
    //     shmop_write($smId, json_encode($smContent), 0);

        /* If main daemon is not running, cron must do nothing */
        // if (AbeilleTools::isAbeilleCronRunning() == false) {
        // if (AbeilleTools::isAbeilleMainRunning() == false) {
        //     log::add('Abeille', 'debug', 'cron(): Main daemon stopped => cron1 canceled');
        //     return;
        // }

        // log::add( 'Abeille', 'debug', 'cron(): Start ------------------------------------------------------------------------------------------------------------------------' );
        $config = AbeilleTools::getConfig();

        /* Check & restart missing daemons
           For debug purposes, display 'PID/daemonShortName' */
        $dStatus = AbeilleTools::checkAllDaemons2($config);
        $dTxt = "";
        foreach ($dStatus['running']['daemons'] as $daemonName => $daemon) {
            if ($dTxt != "")
                $dTxt .= ", ";
            $dTxt .= $daemon['pid'].'/'.$daemonName;
        }
        log::add('Abeille', 'debug', 'cron(): Daemons: '.$dTxt);
        if (!isset($shmContent['daemons']['state']) || ($dStatus['state'] != $shmContent['daemons']['state'])) {
            $shmContent['daemons']['state'] = $dStatus['state'];
            $writeShm = true;
        }

        if ($writeShm) {
            // TODO: Be sure no write conflict with other processses
            /* str_pad() required if 'shmString' is shorter than previous one */
            $shmString = str_pad(json_encode($shmContent, JSON_UNESCAPED_SLASHES), shmSize, "\0");
            shmop_write($shm, $shmString, 0);
        }

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
            if (msg_stat_queue($queue)["msg_qnum"] >= 50) {
                log::add('Abeille', 'error', "cron(): La queue '".$queueName."' (id ".dechex($queueId).") contient plus de 50 messages => redémarrage des démons.");
                self::deamon_start(); // Start is doing a stop first
            }
        }

        // https://github.com/jeelabs/esp-link
        // The ESP-Link connections on port 23 and 2323 have a 5 minute inactivity timeout.
        // so I need to create a minimum of traffic, so pull zigate every minutes
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            if ($config['ab::gtwEnabled'.$gtwId] != 'Y')
                continue; // Gateway disabled
            if ($config['ab::gtwPort'.$gtwId] == "none")
                continue; // Serial port undefined
            // TODO Tcharp38: Currently leads to PI zigate timeout. No sense since still alive.
            // if ($config['ab::gtwSubType'.$gtwId] != "WIFI")
            //     continue; // Not a WIFI zigate. No polling required

            // TODO: What to do for EZSP ?
            if ($config['ab::gtwType'.$gtwId] != "zigate")
                continue; // Not a Zigate

            // TODO: Better to read time to correct it if required, instead of version that rarely changes
            Abeille::msgToCmd(PRIO_NORM, "CmdAbeille".$gtwId."/0000/zgGetVersion");

            // Checking that Zigate is still alive
            $eqLogic = eqLogic::byLogicalId('Abeille'.$gtwId.'/0000', 'Abeille');
            if (!is_object($eqLogic)) {
                log::add('Abeille', 'error', "La ruche ".$gtwId." a été détruite. Veuillez redémarrer Abeille.");
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
                $zgType = $config['ab::gtwSubType'.$gtwId];
                $zgPort = $config['ab::gtwPort'.$gtwId];
                if (($zgType == "USB") || ($zgType == "USBv2")) {
                    if ($config['ab::preventUsbPowerCycle'] == 'Y')
                        log::add('Abeille', 'warning', "Pas de réponse de la Zigate ".$gtwId." depuis plus de 2min mais reset bloqué par utilisateur");
                        // log::add('Abeille', 'warning', 'Power cycle required for Zigate '.$gtwId.' but disabled by user (advanced options)');
                    else {
                        log::add('Abeille', 'info', "Pas de réponse de la Zigate ".$gtwId." depuis plus de 2min => reset");
                        $dir = __DIR__."/../scripts";
                        $cmd = "cd ".$dir."; ".system::getCmdSudo()." ./powerCycleUsb.sh ".$zgPort." 1>/tmp/jeedom/Abeille/powerCycleUsb.log 2>&1";
                        log::add('Abeille', 'debug', 'Performing power cycle on port \''.$zgPort.'\'');
                        exec($cmd, $output, $exitCode);
                        if ($exitCode != 0)
                            message::add("Abeille", "La Zigate ".$gtwId." semble plantée mais impossible de lui faire un cycle OFF/ON.");
                    }
                } else if (($zgType == "PI") || ($zgType == "PIv2")) {
                    log::add('Abeille', 'Debug', 'Performing HW reset on Zigate '.$gtwId);
                    exec("python /var/www/html/plugins/Abeille/core/scripts/resetPiZigate.py");
                } else if (($zgType == "WIFI") || ($zgType == "WIFIv2")) {
                    log::add('Abeille', 'Debug', 'Restarting socat for Zigate '.$gtwId);
                    AbeilleTools::restartDaemons($config, "Socat".$gtwId." socat".$gtwId);
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

            log::add('Abeille', 'debug', 'cron(): poll=1 found, interrogating addr='.$address);
            $mainEP = $eqLogic->getConfiguration('mainEP');
            // Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+($i*3)), "ep=".$mainEP."&clustId=0006&attrId=0000");
            // Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+($i*3)), "ep=".$mainEP."&clustId=0008&attrId=0000");
            Abeille::msgToCmd(PRIO_NORM, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+($i*3)), "ep=".$mainEP."&clustId=0006&attrId=0000");
            Abeille::msgToCmd(PRIO_NORM, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+($i*3)), "ep=".$mainEP."&clustId=0008&attrId=0000");
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

        // Checking how many gateways are in pairing mode
        $count = 0;
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            $incStatus = self::checkInclusionStatus("Abeille{$gtwId}");
            log::add('Abeille', 'debug', "cron(): Abeille{$gtwId} => inclusion status = {$incStatus}");
            if ($incStatus === 1) {
                // Abeille::publishMosquitto($abQueues['xToCmd']['id'], PRIO_NORM, "CmdAbeille{$gtwId}/0000/permitJoin", "Status");
                Abeille::msgToCmd(PRIO_NORM, "CmdAbeille{$gtwId}/0000/permitJoin", "Status");
                $count++;
            }
        }
        if ($count > 1)
            message::add("Abeille", "Attention !! Vous avez plusieurs passerelles en mode inclusion.");

        // log::add( 'Abeille', 'debug', 'cron(): Fin ------------------------------------------------------------------------------------------------------------------------' );
    } // End cron()

    /**
     * cron5
     * Called by Jeedom every 5 minutes.
     * executePollCmds
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron5() {
        /* If main daemon is not running, cron must do nothing */
        // if (AbeilleTools::isAbeilleCronRunning() == false) {
        if (AbeilleTools::isAbeilleMainRunning() == false) {
            log::add('Abeille', 'debug', 'cron5: Main daemon stopped => cron5 canceled');
            return;
        }

        // Poll Cmd
        self::executePollCmds("cron5");
    } // End cron5()

    /**
     * cron10
     * Called by Jeedom every 10 minutes.
     * executePollCmds
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron10() {
        /* If main daemon is not running, cron must do nothing */
        // if (AbeilleTools::isAbeilleCronRunning() == false) {
        if (AbeilleTools::isAbeilleMainRunning() == false) {
            log::add('Abeille', 'debug', 'cron10: Main daemon stopped => cron10 canceled');
            return;
        }

        // Poll Cmd
        self::executePollCmds("cron10");
    } // End cron10()

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
        // if (AbeilleTools::isAbeilleCronRunning() == false) {
        if (AbeilleTools::isAbeilleMainRunning() == false) {
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
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            $zigate = Abeille::byLogicalId('Abeille'.$gtwId.'/0000', 'Abeille');
            if (!is_object($zigate))
                continue; // Does not exist on Jeedom side.
            if (!$zigate->getIsEnable())
                continue; // Zigate disabled
            if ($config['ab::gtwEnabled'.$gtwId] != 'Y')
                continue; // Zigate disabled.

            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list($dest, $addr) = explode("/", $eqLogic->getLogicalId());
                if ($dest != 'Abeille'.$gtwId)
                    continue; // Not on current network
                if (!$eqLogic->getIsEnable())
                    continue; // Equipment disabled

                /* Special case: should ignore virtual remote */
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
                $modelName = $eqModel ? $eqModel['modelName'] : '';
                if ($modelName == "remotecontrol")
                    continue; // Abeille virtual remote

                $eqName = $eqLogic->getname();

                /* Checking if received some news in the last 15mins */
                $cmd = $eqLogic->getCmd('info', 'Time-Time');
                // $cmd = $eqLogic->getCmd('info', 'Time-TimeStamp');
                if (!is_object($cmd)) { // Cmd not found
                    log::add('Abeille', 'warning', "cron15(): Commande 'Time-Time' manquante pour ".$eqName);
                    continue; // No sense to interrogate EQ if Time-Time does not exists
                }
                $lastComm = $cmd->execCmd();
                $lastComm = strtotime($lastComm); // If 'Time-Time'
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
                    // Reading ZCLVersion attribute which should always be supported
                    // Abeille::publishMosquitto($abQueues['xToCmd']['id'], PRIO_NORM, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+($i*23)), "ep=".$mainEP."&clustId=0000&attrId=0000");
                    Abeille::msgToCmd(PRIO_NORM, "TempoCmd".$dest."/".$addr."/readAttribute&time=".(time()+($i*23)), "ep=".$mainEP."&clustId=0000&attrId=0000");
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
     * cron30
     * Called by Jeedom every 30 minutes.
     * executePollCmds
     *
     * @return          Does not return anything as all action are triggered by sending messages in queues
     */
    public static function cron30() {
        // if (AbeilleTools::isAbeilleCronRunning() == false) {
        if (AbeilleTools::isAbeilleMainRunning() == false) {
            log::add('Abeille', 'debug', 'cron30: Main daemon stopped => cron30 canceled');
            return;
        }

        // Poll Cmd
        self::executePollCmds("cron30");
    } // End cron30()

    /**
     * Jeedom required function: report plugin & config status
     * @param none
     * @return array with state, launchable, launchable_message
     */
    // Note: Seems to be called each from a different process ID
    public static function deamon_info() {

        /* Opening shared mem area */
        $shm = shmop_open(12, "a", 0644, shmSize);
        if ($shm === false) {
            log::add('Abeille', 'debug', 'deamon_info(): FAILED to open shared mem');
            return array(
                'log' => 'Abeille',
                'state' => 'nok',
                'launchable' => 'nok',
                'launchable_message' => "Unknown status"
            );
        }
        $shmContent = rtrim(shmop_read($shm, 0, shmop_size($shm)));
        log::add('Abeille', 'debug', "deamon_info(): shmContent='$shmContent'");
        $shmContent = json_decode($shmContent, true);

        /* Notes:
           Since Abeille has its own way to restart missing daemons, reporting only
           cron status as global Abeille status to avoid conflict between
           - Jeedom asking daemons restart (automatic management)
           - Abeille internal daemons restart mecanism */

        /* Init with valid status */
        $status = array(
            'log' => 'Abeille',
            'state' => 'ok',  // Assuming daemons are all running
            'launchable' => 'ok',  // Assuming config ok
            'launchable_message' => ""
        );

        /* Checking there is no error getting parameters and daemon can be started. */
        // TODO: Tcharp38. Can it be optimized ?. Each deamon_info() call leads to mysql DB interrogation.
        // $config = AbeilleTools::getConfig();
        // if ($config['configCheck'] != "ok") {
        //     $status['launchable'] = $config['configCheck'];
        //     // Tcharp38: Where is reported 'launchable_message' ?
        //     $status['launchable_message'] = $config['configCheckMessage'];
        //     log::add('Abeille', 'warning', 'deamon_info(): Config Abeille invalide');
        // }
        /* Config saved by 'deamon_start' but need a real status if not available to not block '(Re)Start'
           When launchable == nok, message is displayed closed to NOK configuration status */
        if (!isset($shmContent['config'])) {
            // This should not appear => restart required
            $config = AbeilleTools::getConfig();
            $status['launchable'] = isset($config['configCheck']) ? $config['configCheck'] : 'nok';
            $status['launchable_message'] = isset($config['configCheckMessage']) ? $config['configCheckMessage'] : '';
        } else {
            $status['launchable'] = isset($shmContent['config']['configCheck']) ? $shmContent['config']['configCheck'] : 'nok';
            $status['launchable_message'] = isset($shmContent['config']['configCheckMessage']) ? $shmContent['config']['configCheckMessage'] : '';
        }

        /* Checking main cron = main Abeille's daemon */
        // if (AbeilleTools::isAbeilleCronRunning() == false) {
        // if (AbeilleTools::isAbeilleMainRunning() == false) {
        //     $status['state'] = "nok";
        //     log::add('Abeille', 'warning', 'deamon_info(): Main daemon is not runnning.');
        // }
        // log::add('Abeille', 'debug', 'deamon_info4(): '.json_encode($shmContent));
        $status['state'] = isset($shmContent['daemons']['state']) ? $shmContent['daemons']['state'] : 'nok';

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
        $lockFile = jeedom::getTmpFolder('Abeille').'/AbeilleLQI.lock';
        if (file_exists($lockFile)) {
            unlink($lockFile);
            log::add('Abeille', 'debug', 'deamon_start_cleanup(): Removed '.$lockFile);
        }

        // Clear zigate IEEE status to detect any port switch.
        // ab::zgIeeeAddrOk=-1: Zigate IEEE is NOT the expected one (port switch ?)
        //     "         = 0: IEEE check to be done
        //     "         = 1: Zigate on the right port
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            config::save("ab::zgIeeeAddrOk".$gtwId, 0, 'Abeille');
        }

        /* Check & update configuration DB if required. */
        $dbVersion = config::byKey('ab::dbVersion', 'Abeille', '0');
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
    public static function deamon_start() {

        // $GLOBALS['toto'] = 12;
        // $pid = getmypid();
        // log::add('Abeille', 'debug', "deamon_start() PID={$pid} TOTO=".json_encode($GLOBALS['toto']));
        log::add('Abeille', 'debug', '>>> deamon_start()');

        $shm = @shmop_open(12, "c", 0, 50);
        if ($shm === false) {
            log::add('Abeille', 'debug', 'deamon_start(): FAILED to create shared mem');
            return false;
        }
        $shmContent = rtrim(shmop_read($shm, 0, shmop_size($shm)));
        log::add('Abeille', 'debug', "deamon_start(): Starting. shmContent='$shmContent'");
        $shmContent = json_decode($shmContent, true);

        if (isset($shmContent['daemonsPaused']) && ($shmContent['daemonsPaused'] == true)) {
            log::add('Abeille', 'debug', 'deamon_start(): IGNORED => daemons PAUSED');
            return false;
        }

        /* Some checks before starting daemons
               - Are dependancies ok ?
               - does Abeille cron exist ? */
        if (self::dependancy_info()['state'] != 'ok') {
            message::add("Abeille", "Tentative de démarrage alors qu'il y a un souci avec les dépendances");
            log::add('Abeille', 'debug', "Tentative de démarrage alors qu'il y a un souci avec les dépendances");
            return false;
        }
        // if (!is_object(cron::byClassAndFunction('Abeille', 'deamon'))) {
        //     log::add('Abeille', 'error', 'deamon_start(): Tache cron introuvable');
        //     message::add("Abeille", "deamon_start(): Tache cron introuvable", "Est ce un bug dans Abeille ?");
        //     throw new Exception(__('Tache cron introuvable', __FILE__));
        // }

        /* Stop all, in case not already the case */
        self::deamon_stop();

        /* Cleanup */
        self::deamon_start_cleanup();

        $config = AbeilleTools::getConfig();
        $shmContent['config'] = $config;

        /* Checking config */
        // TODO Tcharp38: Should be done during deamon_info() and report proper 'launchable'
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            if ($config['ab::gtwEnabled'.$gtwId] != 'Y')
                continue; // Disabled

            /* This zigate is enabled. Checking other parameters */
            $error = "";
            $sp = $config['ab::gtwPort'.$gtwId];
            if (($sp == 'none') || ($sp == "")) {
                $error = "Port série invalide pour la passerelle {$gtwId}";
            }
            if ($error == "") {
                if ($config['ab::gtwSubType'.$gtwId] == "WIFI") {
                    $wifiAddr = $config['ab::gtwIpAddr'.$gtwId];
                    if (($wifiAddr == 'none') || ($wifiAddr == "")) {
                        $error = "Adresse Wifi invalide pour la Zigate {$gtwId}";
                    }
                }
            }
            if ($error != "") {
                $config['ab::gtwEnabled'.$gtwId] = 'N';
                config::save('ab::gtwEnabled'.$gtwId, 'N', 'Abeille');
                log::add('Abeille', 'error', $error." => Passerelle désactivée.");
            } else if (($config['ab::gtwSubType'.$gtwId] == "PI") || ($config['ab::gtwSubType'.$gtwId] == "PIv2")) {
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
        $expected = constant("daemonMain"); // 1 bit per expected serial read daemon
        for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
            if (($config['ab::gtwPort'.$gtwId] == 'none') or ($config['ab::gtwEnabled'.$gtwId] != 'Y'))
                continue; // Undefined or disabled

            $expected |= constant("daemonSerialRead".$gtwId);
            if ($config['ab::gtwSubType'.$gtwId] == 'WIFI')
                $expected |= constant("daemonSocat".$gtwId);
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
        // cron::byClassAndFunction('Abeille', 'deamon')->run();

        // Tcharp38: Moved to main daemon (deamon())
        // Essaye de recuperer les etats des equipements
        // self::refreshCmd();

        // If debug mode, let's check there is at least 5000 lines for support needs
        if (logGetLevelNumber() == 4) {
            $jLines = log::getConfig('maxLineLog');
            if ($jLines < 5000)
                message::add("Abeille", "Vous êtes en mode debug mais le nombre de lignes est inférieur à 5000 (".$jLines."). Il est recommandé d'augmenter ce nombre pour tout besoin de support.");
        }

        /* Update shared mem */
        $shmString = str_pad(json_encode($shmContent, JSON_UNESCAPED_SLASHES), shmSize, "\0");
        $strSize = strlen($shmString);
        log::add('Abeille', 'debug', "deamon_start(): LA writing $strSize");
        $wSize = shmop_write($shm, $shmString, 0);
        if ($wSize != $strSize) {
            log::add('Abeille', 'error', "deamon_start(): Shared mem size too low (need $strSize)");
        }

        log::add('Abeille', 'debug', 'deamon_start(): Ended');
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
        foreach ($abQueues as $qName => $q) {
            $qKey = $q['id'];
            if (msg_queue_exists($qKey) === true) {
                if (msg_remove_queue(msg_get_queue($qKey)) === false)
                    log::add('Abeille', 'debug', "deamon_stop(): msg_remove_queue({$qName}) FAILED");
            }
        }
        // $ret = shell_exec("ipcs -q");
        // if ($ret !== false)
        //     log::add('Abeille', 'debug', "deamon_stop(): ipcs -q => ".$ret);

        log::add('Abeille', 'debug', 'deamon_stop(): Ended');
    }

    // Tcharp38: Moved to 'Abeille.ajax.php'. During Git update, auto-restart is disabled thru config/deamonAutoMode
    // /* Temporary stop daemons and prevent auto-restart from Jeedom */
    // public static function pauseDaemons($start) {
    //     $smId = shmop_open(12, "c", 0644, 50);
    //     $smContent = [];
    //     if ($start)
    //         $smContent['daemonsPaused'] = true;
    //     else
    //         $smContent['daemonsPaused'] = false;
    //     shmop_write($smId, json_encode($smContent), 0);

    //     log::add('Abeille', 'debug', 'pauseDaemons('.$start.')');
    //     if ($start) {
    //         $daemons = AbeilleTools::getRunningDaemons2();
    //         if ($daemons['runBits'] == 0)
    //             $GLOBALS['daemonsRunning'] = false; // No running daemon
    //         else
    //             $GLOBALS['daemonsRunning'] = true;
    //         log::add('Abeille', 'debug', 'Stopping daemons');
    //         self::deamon_stop(); // Stopping daemons
    //     } else {
    //         if ($GLOBALS['daemonsRunning']) {
    //             log::add('Abeille', 'debug', 'Restarting daemons');
    //             abeille::deamon_start(); // Restarting daemon
    //         }
    //     }
    // }

    /* Called from Jeedom to install dependencies.
       Tcharp38: No longer used since 'packages.json' arrival but which Jeedom version ? */
    // public static function dependancy_install() {
    //     log::add('Abeille', 'debug', 'dependancy_install()');

    //     message::add("Abeille", "Installation des dépendances en cours.", "N'oubliez pas de lire la documentation: https://kiwihc16.github.io/AbeilleDoc");
    //     log::remove(__CLASS__.'_update');
    //     $result = [
    //         'script' => __DIR__.'/../scripts/installDependencies.sh '.jeedom::getTmpFolder('Abeille').'/dependencies_progress',
    //         'log' => log::getPathToLog(__CLASS__.'_update')
    //     ];

    //     return $result;
    // }

    /* Called from Jeedom to display dependencies status.
       Tcharp38: Still required (but updated) using official 'packages.json' way */
    public static function dependancy_info() {

        log::add('Abeille', 'debug', 'dependancy_info()');
        $return = array();
        // $return['log'] = log::getPathToLog(__CLASS__ . '_update');
        // $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependencies_progress';
        $return['log'] = log::getPathToLog(__CLASS__ . '_packages');
        $return['progress_file'] = '/tmp/jeedom_install_in_progress_Abeille';
        if (file_exists($return['progress_file'])) {
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
        log::add('Abeille', 'debug', 'dependancy_info: '.json_encode($return, JSON_UNESCAPED_SLASHES));
        return $return;
    }

    // /* This is Abeille's main daemon, directly controlled by Jeedom itself. */
    // // TODO: This is currently launched as cron. Should be isolated as main Abeille's daemon and not depends on cron
    // public static function deamon() {
    //     global $abQueues;

    //     log::add('Abeille', 'debug', 'deamon(): Main daemon starting');

    //     // $pid = getmypid();
    //     // log::add('Abeille', 'debug', "deamon() PID={$pid} TOTO=".json_encode($GLOBALS['toto']));

    //     /* Main daemon starting.
    //        This means that other daemons have started too. Abeille can communicate with them */

    //     // Send a message to Abeille to ask for behive creation/update.
    //     // Tcharp38: Moved from deamon_start()
    //     $config = AbeilleTools::getConfig();
    //     $GLOBALS['config'] = $config;
    //     for ($gtwId = 1; $gtwId <= $GLOBALS['maxGateways']; $gtwId++) {
    //         if ($config['ab::gtwPort'.$gtwId] == 'none')
    //             continue; // Port undefined

    //         if ($config['ab::gtwEnabled'.$gtwId] == 'Y') {
    //             // Create/update beehive equipment on Jeedom side
    //             // Note: This will reset 'FW-Version' to '---------' to mark FW version invalid.
    //             if ($config['ab::gtwType'.$gtwId] == "zigate")
    //                 self::createRuche("Abeille{$gtwId}");
    //             else
    //                 self::createEzspGateway("Abeille{$gtwId}");
    //         } else {
    //             // Gateway disabled. Ensure equipment is disabled too
    //             $eqLogic = eqLogic::byLogicalId("Abeille{$gtwId}/0000", 'Abeille');
    //             if (is_object($eqLogic) && ($eqLogic->getIsEnable() != 0)) {
    //                 $eqLogic->setIsEnable(0);
    //                 $eqLogic->save();
    //             }
    //         }
    //     }

    //     // Essaye de recuperer les etats des equipements
    //     // Tcharp38: Moved from deamon_start()
    //     self::refreshCmd();

    //     try {
    //         $abQueues = $GLOBALS['abQueues'];
    //         while(true) {
    //             $queueXToAbeille = msg_get_queue($abQueues["xToAbeille"]["id"]);
    //             if ($queueXToAbeille !== false)
    //                 break;

    //             log::add('Abeille', 'debug', 'deamon(): msg_get_queue(xToAbeille) ERROR');
    //             usleep(500000); // Sleep 500ms
    //         }
    //         $queueXToAbeilleMax = $abQueues["xToAbeille"]["max"];

    //         // https: github.com/torvalds/linux/blob/master/include/uapi/asm-generic/errno.h
    //         // const int EINVAL = 22;
    //         // const int ENOMSG = 42; /* No message of desired type */

    //         // Blocking queue read
    //         log::add('Abeille', 'debug', 'deamon(): Infinite listening to queueXToAbeille');
    //         while (true) {
    //             log::add('Abeille', 'debug', 'deamon(): msg_receive, msg_qnum='.msg_stat_queue($queueXToAbeille)["msg_qnum"]);
    //             if (@msg_receive($queueXToAbeille, 0, $rxMsgType, $queueXToAbeilleMax, $msgJson, false, 0, $errCode) == false) {
    //                 if ($errCode == 7) {
    //                     msg_receive($queueXToAbeille, 0, $rxMsgType, $queueXToAbeilleMax, $msgJson, false, MSG_IPC_NOWAIT | MSG_NOERROR);
    //                     log::add('Abeille', 'error', "Message (xToAbeille) trop grand ignoré: ".$msgJson);
    //                     continue; // Continue without sleeping
    //                 }

    //                 log::add('Abeille', 'debug', 'deamon(): msg_receive(xToAbeille) erreur '.$errCode);
    //                 usleep(500000); // Sleep 500ms
    //                 continue;
    //             }

    //             $msg = json_decode($msgJson, true);
    //             if (isset($msg['topic']))
    //                 self::message($msg['topic'], $msg['payload']);
    //             else
    //                 self::msgFromParser($msg);
    //         }
    //     } catch (Exception $e) {
    //         log::add('Abeille', 'error', 'deamon(): Exception '.$e->getMessage());
    //     }

    //     log::add('Abeille', 'debug', 'deamon(): Main daemon stopped');
    // }

    // Jeedom optional function: called before saving (create or update) an equipment
    // public function preSave() {
    //     log::add('Abeille', 'debug', 'preSave()');
        // log::add('Abeille', 'debug', 'this='.json_encode($this));
    // }

    // Jeedom optional function: called after saving (create or update) an equipment
    // public static function postSave() {
    //     log::add('Abeille', 'debug', 'postSave()');
        /* Tcharp38: Strange. postSave() called when starting daemons.
           No sense to re-start main daemon from their then */
        // log::add('Abeille', 'debug', 'postSave()');
        // $cron = cron::byClassAndFunction('Abeille', 'deamon');
        // if (is_object($cron) && !$cron->running()) {
        //     $cron->run();
        // }
        // log::add('Abeille', 'debug', 'deamon_postSave: OUT');
    // }

    // // Trying to capture log level change event
    // public static function postConfig_log_level_Abeille($value) {
    //     log::add('Abeille', 'debug', 'postConfig_log_level_Abeille(): value='.json_encode($value));
    //     // NOT WORKING: Might be a Jeedom bug.
    // }

    /* Returns inclusion status: 1=include mode, 0=normal, -1=ERROR */
    public static function checkInclusionStatus($net) {
        $eqLogic = eqLogic::byLogicalId($net.'/0000', 'Abeille');
        if (!is_object($eqLogic) || ($eqLogic->getIsEnable() != 1))
            return -1;

        $cmdJoinStatus = $eqLogic->getCmd('info', 'permitJoin-Status');
        if (!is_object($cmdJoinStatus))
            return -1;

        $incStatus = $cmdJoinStatus->execCmd();
        // log::add('Abeille', 'debug', "incStatus=".$incStatus);
        if (($incStatus === 0) || ($incStatus === 1))
            return $incStatus;
        return -1;
    }

    // /* Trig another command defined by 'trigLogicId'.
    //    The 'newValue' is computed with 'trigOffset' if required then applied to 'trigLogicId' */
    // public static function trigCommand($eqLogic, $value, $trigLogicId, $trigOffset = '') {
    //     $trigCmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $trigLogicId);
    //     if (!is_object($trigCmd)) {
    //         log::add('Abeille', 'debug', "  trigCommand(): Unknown Jeedom command logicId='{$trigLogicId}'");
    //         return;
    //     }

    //     log::add('Abeille', 'debug', "  trigCommand(Val={$value}, TrigOffset='{$trigOffset}')");
    //     if ($trigOffset != '') {
    //         $vsPos = stripos($trigOffset, '#valueswitch-'); // Any #valueswitch-....# variable ?
    //         if ($vsPos !== false) {
    //             $vs = substr($trigOffset, $vsPos + 13);
    //             $vsPos2 = strpos($vs, '#');
    //             $varName = substr($vs, 0, $vsPos2);
    //             log::add('Abeille', 'debug', "  'valueswitch' detected: VarName='{$varName}'");

    //             $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
    //             $varUp = strtoupper($varName);
    //             if (!isset($eqModel['variables']) || !isset($eqModel['variables'][$varUp])) {
    //                 $eqHName = $eqLogic->getHumanName();
    //                 message::add("Abeille", "{$eqHName}: La variable '{$varUp}' n'est pas définie");
    //                 return;
    //             }
    //             $var = $eqModel['variables'][$varUp];
    //             log::add('Abeille', 'debug', "  Var=".json_encode($var, JSON_UNESCAPED_SLASHES));
    //             $varType = gettype($var);
    //             log::add('Abeille', 'debug', "  varType={$varType}");
    //             if ($varType == "array") {
    //                 // Variable is an array so keys are string. If value is int => convert to hex string.
    //                 log::add('Abeille', 'debug', "  valueType=".gettype($value));
    //                 if (gettype($value) != "string") {
    //                     $value2 = strval($value);
    //                     log::add('Abeille', 'debug', "  value2={$value2}");
    //                     $newValue = $var[$value2];
    //                 } else
    //                     $newValue = $var[$value];
    //             } else
    //                 $newValue = $var;
    //             log::add('Abeille', 'debug', "  newValue=".json_encode($newValue, JSON_UNESCAPED_SLASHES));
    //             $trigValue = jeedom::evaluateExpression(str_ireplace("#valueswitch-{$varName}#", $newValue, $trigOffset));
    //         } else
    //             $trigValue = jeedom::evaluateExpression(str_ireplace('#value#', $value, $trigOffset));
    //     } else
    //         $trigValue = $value;

    //     $trigName = $trigCmd->getName();
    //     log::add('Abeille', 'debug', "  Triggering cmd '{$trigName}' ({$trigLogicId}) with Val='{$trigValue}'");
    //     $eqLogic->checkAndUpdateCmd($trigCmd, $trigValue);

    //     // Is the triggered command a battery percent reporting ?
    //     if (preg_match("/^0001-[0-9A-F]*-0021/", $trigLogicId)) {
    //         $trigValue = round($trigValue, 0);
    //         log::add('Abeille', 'debug', "  Battery % reporting: {$trigLogicId}, Val={$trigValue}");
    //         $eqLogic->setStatus('battery', $trigValue);
    //         $eqLogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
    //     }
    // }

    // /* Called on info cmd update (attribute report or attribute read) to see if any action cmd must be executed */
    // public static function infoCmdUpdate($eqLogic, $cmdLogic, $value) {

    //     // Trig another command ('ab::trigOut' eqLogic config) ?
    //     // Syntax reminder
    //     // "trigOut": {
    //     //     "01-smokeAlarm": {
    //     //         "comment": "On receive we trig <EP>-smokeAlarm with extracted boolean/bit0 value",
    //     //         "valueOffset": "#value#&1"
    //     //     },
    //     //     "01-tamperAlarm": {
    //     //         "comment": "Bit 2 is tamper",
    //     //         "valueOffset": "(#value#>>2)&1"
    //     //     }
    //     // }
    //     $toList = $cmdLogic->getConfiguration('ab::trigOut', []);
    //     foreach ($toList as $trigLogicId => $to) {
    //         if (isset($to['valueOffset']))
    //             $trigOffset = $to['valueOffset'];
    //         else
    //             $trigOffset = '';
    //         Abeille::trigCommand($eqLogic, $cmdLogic->execCmd(), $trigLogicId, $trigOffset);
    //     }
    //     // if ($trigLogicId) {
    //     //     $trigOffset = $cmdLogic->getConfiguration('ab::trigOutOffset');
    //     //     Abeille::trigCommand($eqLogic, $cmdLogic->execCmd(), $trigLogicId, $trigOffset);
    //     // }

    //     // Trig another command (PollingOnCmdChange keyword) ?
    //     global $abQueues;
    //     $cmds = cmd::searchConfigurationEqLogic($eqLogic->getId(), 'PollingOnCmdChange', 'action');
    //     $cmdLogicId = $cmdLogic->getLogicalId();
    //     foreach ($cmds as $cmd) {
    //         if ($cmd->getConfiguration('PollingOnCmdChange', '') != $cmdLogicId)
    //             continue;
    //         $delay = $cmd->getConfiguration('PollingOnCmdChangeDelay', '');
    //         $cmdName = $cmd->getName();
    //         $cmdLogicId = $cmd->getLogicalId();
    //         if ($delay != 0) {
    //             log::add('Abeille', 'debug', "  Triggering '{$cmdName}' ({$cmdLogicId}) with delay ".$delay);
    //             Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".(time() + $delay), $cmd->getConfiguration('request'));
    //         } else {
    //             log::add('Abeille', 'debug', "  Triggering '{$cmdName}' ({$cmdLogicId})");
    //             Abeille::publishMosquitto($abQueues['xToCmd']['id'], priorityInterrogation, "TempoCmd".$eqLogic->getLogicalId()."/".$cmd->getConfiguration('topic')."&time=".time(), $cmd->getConfiguration('request'));
    //         }
    //     }
    // }

    // public static function checkZgIeee($net, $ieee) {
    //     $gtwId = substr($net, 7);
    //     $keyIeee = str_replace('Abeille', 'ab::zgIeeeAddr', $net); // AbeilleX => ab::zgIeeeAddrX
    //     $keyIeeeOk = str_replace('Abeille', 'ab::zgIeeeAddrOk', $net); // AbeilleX => ab::zgIeeeAddrOkX
    //     if (config::byKey($keyIeeeOk, 'Abeille', 0) == 0) {
    //         $ieeeConf = config::byKey($keyIeee, 'Abeille', '');
    //         if ($ieeeConf == "") {
    //             config::save($keyIeee, $ieee, 'Abeille');
    //             config::save($keyIeeeOk, 1, 'Abeille');
    //         } else if ($ieeeConf == $ieee) {
    //             config::save($keyIeeeOk, 1, 'Abeille');
    //         } else {
    //             config::save($keyIeeeOk, -1, 'Abeille');
    //             message::add("Abeille", "Attention: La zigate ".$gtwId." semble nouvelle ou il y a eu échange de ports. Tous ses messages sont ignorés par mesure de sécurité. Assurez vous que les zigates restent sur le meme port, même après reboot.", 'Abeille/Demon');
    //         }
    //     }
    // }

    // public static function publishMosquitto($queueId, $priority, $topic, $payload) {
    //     static $queueStatus = []; // "ok" or "error"

    //     $queue = msg_get_queue($queueId);
    //     if ($queue === false) {
    //         log::add('Abeille', 'error', "publishMosquitto(): La queue ".$queueId." n'existe pas. Message ignoré.");
    //         return;
    //     }
    //     if (($stat = msg_stat_queue($queue)) == false) {
    //         return; // Something wrong
    //     }

    //     /* To avoid plenty errors, checking if someone really reads the queue.
    //        If not, do nothing but a message to user first time.
    //        Note: Assuming potential pb if more than 50 pending messages. */
    //     $pendMsg = $stat['msg_qnum']; // Pending messages
    //     if ($pendMsg > 50) {
    //         if (file_exists("/proc/") && !file_exists("/proc/".$stat['msg_lrpid'])) {
    //             /* Receiver process seems down */
    //             if (isset($queueStatus[$queueId]) && ($queueStatus[$queueId] == "error"))
    //                 return; // Queue already marked "in error"
    //             message::add("Abeille", "Alerte ! Démon arrété ou planté. (Re)démarrage nécessaire.", '');
    //             $queueStatus[$queueId] = "error";
    //             return;
    //         }
    //     }

    //     $msg = array();
    //     $msg['priority'] = $priority;
    //     $msg['topic'] = $topic;
    //     $msg['payload'] = $payload;
    //     $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

    //     if (msg_send($queue, 1, $msgJson, false, false, $error_code)) {
    //         log::add('Abeille', 'debug', "  publishMosquitto(): Sent '".$msgJson."' to queue ".$queueId);
    //         $queueStatus[$queueId] = "ok"; // Status ok
    //     } else
    //         log::add('Abeille', 'warning', "publishMosquitto(): Impossible d'envoyer '".$msgJson."' vers queue ".$queueId);
    // } // End publishMosquitto()

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

        $msg = array();
        $msg['priority'] = $priority;
        $msg['topic'] = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg, JSON_UNESCAPED_SLASHES);

        if (msg_send($queue, 1, $msgJson, false, false, $error_code)) {
            log::add('Abeille', 'debug', "  msgToCmd(): Sent '$msgJson'");
            $queueStatus[$queueId] = "ok"; // Status ok
        } else
            log::add('Abeille', 'warning', "msgToCmd(): Impossible d'envoyer '".$msgJson."' vers queue ".$queueId);
    } // End msgToCmd()

    // // Zigate Jeedom equipment creation/update. Called on daemon startup or new beehive creation.
    // public static function createRuche($dest) {
    //     $gtwId = substr($dest, 7); // AbeilleX => X

    //     // $config = AbeilleTools::getConfig();
    //     $config = $GLOBALS['config']; // Present as global since main daemon
    //     $eqLogic = eqLogic::byLogicalId($dest."/0000", 'Abeille');
    //     if (!is_object($eqLogic)) {
    //         message::add("Abeille", "Création de l'équipement 'Ruche' en cours. Rafraichissez votre dashboard dans qq secondes.", '');
    //         log::add('Abeille', 'info', 'Ruche: Création de '.$dest."/0000");
    //         $eqLogic = new Abeille();
    //         //id
    //         $eqLogic->setName("Ruche-".$dest);
    //         $eqLogic->setLogicalId($dest."/0000");
    //         if ($config['ab::defaultParent'] > 0) {
    //             $eqLogic->setObject_id($config['ab::defaultParent']);
    //         } else {
    //             $eqLogic->setObject_id(jeeObject::rootObject()->getId());
    //         }
    //         $eqLogic->setEqType_name('Abeille');
    //         $eqLogic->setIsVisible("0");
    //         $eqLogic->setConfiguration('ab::icon', "Ruche");
    //         $eqLogic->setTimeout(5); // timeout en minutes
    //         $eqLogic->setIsEnable(1);
    //     } else {
    //         // TODO: If already exist, should we update commands if required ?
    //         log::add('Abeille', 'debug', "createRuche(): '".$eqLogic->getLogicalId()."' already exists");
    //     }

    //     $eqLogic->setConfiguration('mainEP', '01');

    //     // For future.. if required
    //     // // Zigate is a bridge: adding 'ab::bridge' array
    //     // $bridge = array(
    //     //     'type' => 'zigate',
    //     //     'model' => $config['ab::gtwSubType'.$gtwId],
    //     // );
    //     // $eqLogic->setConfiguration('ab::bridge', $bridge);

    //     // Zigate JSON model infos
    //     $eqModelInfos = array(
    //         'modelSig' => 'rucheCommand',
    //         'modelName' => 'rucheCommand', // Equipment model id
    //         'modelSource' => 'Abeille', // Equipment model location
    //         'type' => 'Zigate',
    //     );
    //     $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

    //     // Note: initializing 'groups' support. Simple descriptor response does not show cluster 0004 for EP01 (see https://github.com/fairecasoimeme/ZiGate/issues/409)
    //     $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
    //     if (!isset($zigbee['groups']))
    //         $zigbee['groups'] = [];
    //     if (!isset($zigbee['groups']['01']))
    //         $zigbee['groups']['01'] = '';
    //     $zigbee['mainsPowered'] = 1;
    //     $zigbee['rxOnWhenIdle'] = 1;
    //     $eqLogic->setConfiguration('ab::zigbee', $zigbee);

    //     $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
    //     $eqLogic->save();

    //     $rucheCommandList = AbeilleTools::getJSonConfigFiles('rucheCommand.json', 'Abeille');

    //     // // Only needed for debug and dev so by default it's not done.
    //     // if (0) {
    //     //     $i = 100;

    //     //     //Load all commandes from defined objects (except ruche), and create them hidden in Ruche to allow debug and research.
    //     //     $items = AbeilleTools::getDeviceNameFromJson('Abeille');

    //     //     foreach ($items as $item) {
    //     //         $AbeilleObjetDefinition = AbeilleTools::getJSonConfigFilebyDevices(AbeilleTools::getTrimmedValueForJsonFiles($item), 'Abeille');
    //     //         // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
    //     //         foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
    //     //             $rucheCommandList[$objetId] = array(
    //     //                 "name" => $objetId,
    //     //                 "order" => $i++,
    //     //                 "isVisible" => "0",
    //     //                 "isHistorized" => "0",
    //     //                 "Type" => "action",
    //     //                 "subType" => "other",
    //     //                 "configuration" => array(
    //     //                     "topic" => "CmdCreate/".$objetId."/0000-0005",
    //     //                     "request" => $objetId,
    //     //                     "visibilityCategory" => "additionalCommand",
    //     //                     "visibiltyTemplate" => "0"
    //     //                 ),
    //     //             );
    //     //         }
    //     //     }
    //     //     // print_r($rucheCommandList);
    //     // }

    //     // Removing obsolete commands by their logical ID (unique)
    //     $cmds = Cmd::byEqLogicId($eqLogic->getId());
    //     foreach ($cmds as $cmdLogic) {
    //         $found = false;
    //         $cmdName = $cmdLogic->getName();
    //         $cmdLogicId = $cmdLogic->getLogicalId();
    //         foreach ($rucheCommandList as $cmdLogicId2 => $mCmd) {
    //             if ($cmdLogicId == $cmdLogicId2) {
    //                 $found = true;
    //                 break; // Listed in JSON
    //             }
    //         }
    //         if ($found == false) {
    //             log::add('Abeille', 'debug', "  Removing cmd '".$cmdName."' => '".$cmdLogicId."'");
    //             $cmdLogic->remove(); // No longer required
    //         }
    //     }

    //     // Creating/updating beehive commands
    //     $order = 0;
    //     foreach ($rucheCommandList as $cmdLogicId => $mCmd) {
    //         $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), $cmdLogicId);
    //         if (!$cmdLogic) {
    //             $cmdJName = $mCmd["name"]; // Jeedom cmd name
    //             log::add('Abeille', 'debug', "  Adding cmd '".$cmdJName."' => '".$cmdLogicId."'");
    //             $cmdLogic = new AbeilleCmd();
    //             $cmdLogic->setEqLogic_id($eqLogic->getId());
    //             $cmdLogic->setEqType('Abeille');
    //             $cmdLogic->setLogicalId($cmdLogicId);
    //             $cmdLogic->setName($cmdJName);
    //             $newCmd = true;
    //         } else {
    //             $cmdJName = $cmdLogic->getName();
    //             log::add('Abeille', 'debug', "  Updating cmd '".$cmdJName."' => '".$cmdLogicId."'");
    //             $newCmd = false;
    //         }

    //         $cmdLogic->setOrder($order++); // New or update

    //         if ($mCmd["Type"] == "action") {
    //             // $cmdLogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd);
    //             $cmdLogic->setConfiguration('topic', $cmdLogicId);

    //             // Tcharp38: work in progress. Adding support for linked commands
    //             // Note: Error if info cmd is not registered BEFORE action cmd.
    //             // if (isset($mCmd["value"])) {
    //             //     // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
    //             //     log::add('Abeille', 'debug', 'Define cmd info pour cmd action: '.$eqLogic->getHumanName()." - ".$mCmd["value"]);

    //             //     $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $eqLogic->getName(), $mCmd["value"]);
    //             //     $cmdLogic->setValue($cmdPointeur_Value->getId());
    //             // }
    //         } else {
    //             // $cmdLogic->setConfiguration('topic', $nodeid.'/'.$cmd);
    //             $cmdLogic->setConfiguration('topic', $cmdLogicId);
    //         }
    //         // if ($mCmd["Type"] == "action") {  // not needed as mosquitto is not used anymore
    //         //    $cmdLogic->setConfiguration('retain', '0');
    //         // }
    //         if (isset($mCmd["configuration"])) {
    //             foreach ($mCmd["configuration"] as $confKey => $confValue) {
    //                 $cmdLogic->setConfiguration($confKey, $confValue);
    //             }
    //         }
    //         $cmdLogic->setType($mCmd["Type"]);
    //         $cmdLogic->setSubType($mCmd["subType"]);

    //         // Todo only if new command
    //         if ($newCmd) {
    //             if (isset($mCmd["isHistorized"])) $cmdLogic->setIsHistorized($mCmd["isHistorized"]);
    //             if (isset($mCmd["template"])) $cmdLogic->setTemplate('dashboard', $mCmd["template"]);
    //             if (isset($mCmd["template"])) $cmdLogic->setTemplate('mobile', $mCmd["template"]);
    //             if (isset($mCmd["invertBinary"])) $cmdLogic->setDisplay('invertBinary', '0');
    //             if (isset($mCmd["isVisible"])) $cmdLogic->setIsVisible($mCmd["isVisible"]);
    //             if (isset($mCmd["display"])) {
    //                 foreach ($mCmd["display"] as $confKey => $confValue) {
    //                     // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
    //                     $cmdLogic->setDisplay($confKey, $confValue);
    //                 }
    //             }
    //         }

    //         // Whatever existing or new beehive, it is key to reset the following points
    //         if ($cmdLogicId == 'FW-Version')
    //             // $cmdLogic->setValue('----'); // Indicate FW version is invalid
    //             $cmdLogic->setCache('value', '---------'); // Indicate FW version is invalid

    //         $cmdLogic->save();
    //     }
    // } // End createRuche()

    // // EZSP gateway Jeedom equipment creation/update. Called on daemon startup or new beehive creation.
    // public static function createEzspGateway($net) {
    //     $gtwId = substr($net, 7); // AbeilleX => X

    //     // $config = AbeilleTools::getConfig();
    //     $config = $GLOBALS['config']; // Present as global since main daemon
    //     $eqLogic = eqLogic::byLogicalId($net."/0000", 'Abeille');
    //     if (!is_object($eqLogic)) {
    //         message::add("Abeille", "Création de l'équipement 'EZSP' en cours. Rafraichissez votre dashboard dans qq secondes.", '');
    //         log::add('Abeille', 'info', 'Ruche: Création de '.$net."/0000");
    //         $eqLogic = new Abeille();
    //         //id
    //         $eqLogic->setName("EzspGtw-".$net);
    //         $eqLogic->setLogicalId($net."/0000");
    //         if ($config['ab::defaultParent'] > 0) {
    //             $eqLogic->setObject_id($config['ab::defaultParent']);
    //         } else {
    //             $eqLogic->setObject_id(jeeObject::rootObject()->getId());
    //         }
    //         $eqLogic->setEqType_name('Abeille');
    //         $eqLogic->setIsVisible("0"); // No need on dashboard
    //         $eqLogic->setConfiguration('ab::icon', "Ruche");
    //         $eqLogic->setTimeout(5); // timeout en minutes
    //         $eqLogic->setIsEnable(1);
    //     } else {
    //         // TODO: If already exist, should we update commands if required ?
    //         log::add('Abeille', 'debug', "createEzspGateway(): '".$eqLogic->getLogicalId()."' already exists");
    //     }

    //     // $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
    //     $eqLogic->save();
    // } // End createEzspGateway()

    // // Create a basic Jeedom device
    // public static function newJeedomDevice($net, $addr, $ieee) {
    //     log::add('Abeille', 'debug', '  newJeedomDevice('.$net.', addr='.$addr.')');

    //     $logicalId = $net.'/'.$addr;
    //     $eqLogic = new Abeille();
    //     $eqLogic->setEqType_name('Abeille');
    //     $eqLogic->setName("newDevice-".$addr); // Temp name to have it non empty
    //     $eqLogic->save(); // Save to force Jeedom to assign an ID

    //     $eqName = $net."-".$eqLogic->getId(); // Default name (ex: 'Abeille1-12')
    //     $eqLogic->setName($eqName);
    //     $eqLogic->setLogicalId($logicalId);
    //     // $config = AbeilleTools::getConfig();
    //     $config = $GLOBALS['config']; // Present as global since main daemon
    //     $eqLogic->setObject_id($config['ab::defaultParent']);
    //     $eqLogic->setConfiguration('IEEE', $ieee);
    //     $eqLogic->setIsVisible(0); // Hidden by default
    //     $eqLogic->setIsEnable(1);
    //     $eqLogic->save();

    //     // Inform cmd that new device has been created
    //     $msg = array(
    //         'type' => "eqUpdated",
    //         'id' => $eqLogic->getId()
    //     );
    //     Abeille::msgToCmd2($msg);

    // } // End newJeedomDevice()

    /* Create or update Jeedom device based on its JSON model.
       Called in the following cases
       - On 'eqAnnounce' message from parser (device announce) => action = 'update'
       - To create/update a virtual 'remotecontrol' => action = 'update'
       - To update from JSON (identical to re-inclusion) => action = 'update'
       - To force a different model than the one auto-detected => action = 'update'
     */
    // WARNING: This CAN'T be moved in 'AbeilleMainD.php' as it is used by 'install.php'too
    public static function createDevice($action, $dev) {
        log::add('Abeille', 'debug', '  createDevice('.$action.', dev='.json_encode($dev, JSON_UNESCAPED_SLASHES));

        /* $action reminder
              'update' => create or update device (device announce/update)
              'reset' => create or reset device from model (user request)
           $dev reminder
                $dev = array(
                    'net' =>
                    'addr' =>
                    'modelSource' => 'Abeille', // Model file location ('Abeille' or 'local')
                    'modelName' => 'remotecontrol', // Model file name
                    'modelSig' => 'remotecontrol', // Model signature
                );
         */

        if (!isset($dev['net']) || !isset($dev['addr'])) {
            log::add('Abeille', 'error', "  createDevice(): 'net' et/ou 'addr' non renseigné");
            return;
        }
        $net = $dev['net'];
        $addr = $dev['addr'];
        $eqLogicId = "{$net}/{$addr}";
        $eqLogic = eqLogic::byLogicalId($eqLogicId, 'Abeille');

        // Do we have all informations about model to use ?
        if (isset($dev['modelName'])) {
            if (isset($dev['modelSource']))
                $modelSource = $dev['modelSource'];
            $modelName = $dev['modelName'];
            if (isset($dev['modelPath']))
                $modelPath = $dev['modelPath'];
            $modelForced = isset($dev['modelForced']) ? $dev['modelForced']: false;

            if (isset($dev['modelSig']))
                $modelSig = $dev['modelSig'];
        }
        if ((!isset($modelName) || !isset($modelSig)) && is_object($eqLogic)) {
            // Missing modelName and/or modelSig => already stored in ab::eqModel ?
            $jEqModel = $eqLogic->getConfiguration('ab::eqModel', []); // Eq model from Jeedom DB
            if (!isset($modelName)) {
                $modelSource = isset($jEqModel['modelSource']) ? $jEqModel['modelSource']: '';
                $modelName = isset($jEqModel['modelName']) ? $jEqModel['modelName']: '';
                if (isset($jEqModel['modelPath']))
                    $modelPath = $jEqModel['modelPath'];
                $modelForced = isset($jEqModel['modelForced']) ? $jEqModel['modelForced']: false;
            }
            if (!isset($modelSig) && isset($jEqModel['modelSig']))
                $modelSig = $jEqModel['modelSig'];
        }
        if (!isset($modelName)) {
            log::add('Abeille', 'error', "  createDevice({$net}/{$addr}): 'modelName' non renseigné");
            return;
        }

        // Ok, 'modelName' is defined
        if ($modelSource == '')
            $modelSource = 'Abeille';
        if (!isset($modelPath))
            $modelPath = "{$modelName}/{$modelName}.json";
        if (!isset($modelSig))
            $modelSig = $modelName;

        if (($modelSource != '') && ($modelPath != '')) {
            // $model = AbeilleTools::getDeviceModel($modelSig, $modelName, $modelSource);
            $model = getDeviceModel($modelSource, $modelPath, $modelName, $modelSig);
            if ($model === false) {
                log::add('Abeille', 'debug', '  ERRRRRRRRR');
                return;
            }

            log::add('Abeille', 'debug', '  Model='.json_encode($model, JSON_UNESCAPED_SLASHES));
            $eqType = $model['type'];
        }

        if (!is_object($eqLogic)) {
            $newEq = true;

            // if ($action != 'create') {
            //     log::add('Abeille', 'debug', '  ERROR: Action='.$action.' but device '.$eqLogicId.' does not exist');
            //     return;
            // }

            // $action == 'create'
            log::add('Abeille', 'debug', '  New device '.$eqLogicId);
            if ($modelName != "defaultUnknown")
                message::add("Abeille", "Nouvel équipement identifié (".$eqType."). Création en cours. Rafraîchissez votre dashboard dans qq secondes.", '');
            else
                message::add("Abeille", "Nouvel équipement détecté mais non supporté. Création en cours avec la config par défaut (".$modelName."). Rafraîchissez votre dashboard dans qq secondes.", '');

            $eqLogic = new Abeille();
            $eqLogic->setEqType_name('Abeille');
            $eqLogic->setName("newDevice-".$dev['addr']); // Temp name to have it non empty
            $eqLogic->save(); // Save to force Jeedom to assign an ID

            $eqId = $eqLogic->getId();
            $eqName = $eqType." - ".$eqId; // Default name (ex: '<eqType> - 12')
            $eqLogic->setName($eqName);
            $eqLogic->setLogicalId($eqLogicId);
            // $config = AbeilleTools::getConfig();
            $config = $GLOBALS['config']; // Present as global since main daemon
            $eqLogic->setObject_id($config['ab::defaultParent']);
            if (isset($dev['ieee'])) $eqLogic->setConfiguration('IEEE', $dev['ieee']); // No IEEE for virtual remote
        } else {
            $newEq = false;

            $eqHName = $eqLogic->getHumanName(); // Jeedom hierarchical name
            log::add('Abeille', 'debug', '  Already existing device '.$eqLogicId.' => '.$eqHName);

            // Kept for safety but should already be assigned in 'special case' block
            $jEqModel = $eqLogic->getConfiguration('ab::eqModel', []); // Eq model from Jeedom DB
            $curEqModel = isset($jEqModel['modelName']) ? $jEqModel['modelName'] : ''; // Current JSON model
            $ieee = $eqLogic->getConfiguration('IEEE'); // IEEE from Jeedom DB
            $eqId = $eqLogic->getId();

            if ($curEqModel == '') { // Jeedom eq exists but init not completed
                $eqName = $eqType." - ".$eqId; // Default name (ex: '<eqType> - 12')
                $eqLogic->setName($eqName);
                message::add("Abeille", $eqHName.": Nouvel équipement identifié.", '');
                $action = 'reset';
            } else if (($curEqModel == 'defaultUnknown') && ($modelName != 'defaultUnknown')) {
                message::add("Abeille", $eqHName.": S'est réannoncé => Mise-à-jour du modèle par défaut vers '".$eqType."'", '');
                $action = 'reset'; // Update from defaultUnknown = reset to new model
            }
            // else if ($action == "update")
            //     message::add("Abeille", $eqHName.": Mise-à-jour à partir de son modèle (source=".$modelSource.")");
            else if ($action == "reset")
                message::add("Abeille", $eqHName.": Réinitialisation à partir de '".$modelName."' (source=".$modelSource.")");
            else { // action = create
                /* Tcharp38: Following https://github.com/KiwiHC16/Abeille/issues/2132#, device re-announce is just ignored here
                    to not generate plenty messages, unless device was disabled.
                    Other reasons to generate message ?
                */
                if ($eqLogic->getIsEnable() != 1)
                    message::add("Abeille", $eqHName.": S'est réannoncé => Mise-à-jour à partir de son modèle (source=".$modelSource.")");
            }
        }

        if ($modelSource == "local") {
            $fullPath = __DIR__."/../config/devices/{$modelName}/{$modelName}.json";
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

        // OBSOLETE: Moved to 'ab::zigbee'
        // if (isset($dev['modelId'])) {
        //     $sig = array(
        //         'modelId' => $dev['modelId'],
        //         'manufId' => $dev['manufId'],
        //     );
        //     $eqLogic->setConfiguration('ab::signature', $sig);
        // }

        // Icon updated if no-longer-exists/reset/undefined/defaultUnknown
        $curIcon = $eqLogic->getConfiguration('ab::icon', '');
        if ($curIcon != '') {
            $iconPath = __DIR__.'/../../core/config/devices_images/node_'.$curIcon.'.png';
            $iconExists = file_exists($iconPath);
        } else {
            $iconPath = '';
            $iconExists = false;
        }
        // log::add('Abeille', 'debug', 'LA iconExists='.$iconExists.', path='.$iconPath);
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

        // OBSOLETE: Replaced by use of 'variables' section
        // if (isset($modelConf['Groupe'])) { // Tcharp38: What for ? Telecommande Innr - KiwiHC16: on doit pouvoir simplifier ce code. Mais comme c etait la premiere version j ai fait detaillé.
        //     $eqLogic->setConfiguration('Groupe', $modelConf['Groupe']);
        // }

        // #GROUPEPx# variables now stored as generic vars in 'variables' section and replacement already done by getDeviceModel()
        // Temporary support for 'groupEPx' (to replace #GROUPEPx#)
        // Constant used to define remote control group per EP
        // for ($g = 1; $g <= 8; $g++) {
        //     if (isset($modelConf['groupEP'.$g]))
        //         $eqLogic->setConfiguration('groupEP'.$g, $modelConf['groupEP'.$g]);
        //     else
        //         $eqLogic->setConfiguration('groupEP'.$g, null);
        // }

        if (isset($modelConf['onTime'])) { // Tcharp38: What for ?
            $eqLogic->setConfiguration('onTime', $modelConf['onTime']);
        }
        if (isset($modelConf['poll']))
            $eqLogic->setConfiguration('poll', $modelConf['poll']);
        else
            $eqLogic->setConfiguration('poll', null);

        // Tuya specific infos: OBSOLETE ! Replaced by 'private' + 'EF00' + 'type=tuya'
        // if (isset($model['tuyaEF00']))
        //     $eqLogic->setConfiguration('ab::tuyaEF00', $model['tuyaEF00']);
        // else
        //     $eqLogic->setConfiguration('ab::tuyaEF00', null);

        // Xiaomi specific infos: OBSOLETE soon. Replaced by 'fromDevice'
        // if (isset($model['xiaomi']))
        //     $eqLogic->setConfiguration('ab::xiaomi', $model['xiaomi']);
        // else
        //     $eqLogic->setConfiguration('ab::xiaomi', null);

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
        if (isset($zigbee['macCapa']) && ($zigbee['macCapa'] != '')) {
            $mc = hexdec($zigbee['macCapa']);
            $zigbee['mainsPowered'] = ($mc >> 2) & 0b1; // 1=mains-powered
            $zigbee['rxOnWhenIdle'] = ($mc >> 3) & 0b1; // 1=Receiver enabled when idle
        }
        if (isset($dev['modelId'])) { // Was previously stored in 'ab::signature'
            $zigbee['modelId'] = $dev['modelId'];
            $zigbee['manufId'] = $dev['manufId'];
        }
        $eqLogic->setConfiguration('ab::zigbee', $zigbee);

        // JSON model infos => 'ab::eqModel'
        $eqModelInfos = array(
            // Model infos
            'modelSource' => $modelSource, // Equipment model file location
            'modelName' => $modelName, // Equipment model file name
            // 'modelPath' => $modelPath, // OPTIONAL: Model path if variant (<modelName>/<modelName>[-variantX].json)
            'modelForced' => $modelForced,

            'modelSig' => $modelSig, // Equipment model signature (!= modelName if alternate ID or forced model)

            // Equipment infos
            'manuf' => isset($model['manufacturer']) ? $model['manufacturer'] : '',
            'model' => isset($model['model']) ? $model['model'] : '',
            'type' => $model['type'],

            // 'lastUpdate' => time(), // Store last update from model. // Tcharp38: created for Abeille but not used
            // 'variables' // Optional
            // 'private' // Optional
        );
        if ($modelPath != "{$modelName}/{$modelName}.json")
            $eqModelInfos['modelPath'] = $modelPath;
        if (isset($model['private'])) // Private cluster or command specific infos
            $eqModelInfos['private'] = $model['private'];
        // else if (isset($model['fromDevice'])) // OBSOLETE soon => replaced by 'private'
        //     $eqModelInfos['fromDevice'] = $model['fromDevice'];
        if (isset($model['variables'])) // Optional variables
            $eqModelInfos['variables'] = $model['variables'];
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
                    message::add("Abeille", "'mainEP' est requis mais n'est pas défini dans '".$modelName.".json'", '');
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
                'obsolete' => (strpos($cmdLogicId, '::') === false) ? true : false // User cmd must not be removed
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
                log::add('Abeille', 'error', "{{La commande suivante n'a pas de type défini}}: '{$mCmdName}'");
                continue;
            }
            $mCmdType = $mCmd["type"];
            if ($mCmdType == 'info') {
                if (!isset($mCmd["logicalId"]) || ($mCmd["logicalId"] == '')) {
                    log::add('Abeille', 'error', "{{La commande suivante n'a pas de 'logicalId' défini}}: '{$mCmdName}'");
                    continue;
                }
            } else if ($mCmdType == 'action') {
                // Any checks ?
            } else {
                log::add('Abeille', 'error', "{{La commande suivante a un type invalide}}: '{$mCmdName}'");
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
                $jeedomCmds[$jCmdId]['obsolete'] = false;
                break; // Found
            }

            // Search by name if still not found
            if ($cmdId === null) {
                log::add('Abeille', 'debug', "  Searching by name: '".$mCmdName."'");
                foreach ($jeedomCmds as $jCmdId => $jCmd) {
                    if (($jCmd['name'] != '') && ($jCmd['name'] != $mCmdName))
                        continue;
                    $cmdId = $jCmdId;
                    $jeedomCmds[$jCmdId]['obsolete'] = false;
                    break; // Found
                }
            }

            // Search by topic/request if still not found & 'action'
            // DISABLED !! Does not work when adding new commands (then no name nor logicId match) but same topic/request
            // if (($cmdId === null) && ($mCmdType == 'action')) {
            //     $mTopic = $mCmd["configuration"]['topic'];
            //     $mRequest = $mCmd["configuration"]['request'];
            //     log::add('Abeille', 'debug', "  Searching by topic/request='".$mTopic."/".$mRequest."'");
            //     foreach ($jeedomCmds as $jCmdId => $jCmd) {
            //         if ($jCmd['topic'] != $mTopic)
            //             continue;
            //         if ($jCmd['request'] != $mRequest)
            //             continue;
            //         $cmdId = $jCmdId;
            //         $jeedomCmds[$jCmdId]['obsolete'] = false;
            //         break;
            //     }
            // }

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
                    $cmdLogic->setUnite(""); // Clear unit

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
            // log::add('Abeille', 'debug', '  curDashbTemplate='.$curDashbTemplate);
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
               $rmIfUnset = List of fields to remove if unset in model.
               $rmOnlyIfReset = List of fields to remove if unset in model AND if 'action' == 'reset'. */
            $rmIfUnset = ['visibilityCategory', 'historizeRound', 'execAtCreation', 'execAtCreationDelay', 'topic', 'Polling', 'RefreshData', 'listValue'];
            array_push($rmIfUnset, 'ab::trigOut', 'PollingOnCmdChange', 'PollingOnCmdChangeDelay', 'ab::notStandard');
            array_push($rmIfUnset, 'ab::valueOffset', 'ab::repeat');
            array_push($rmIfUnset, 'returnStateTime', 'returnStateValue');
            $rmOnlyIfReset = $rmIfUnset;
            array_push($rmOnlyIfReset, 'minValue', 'maxValue', 'calculValueOffset', 'repeatEventManagement');
            // Abeille specific keys must be renamed when taken from model (ex: trigOut => ab::trigOut)
            $toRename = ['trigOut', 'notStandard', 'valueOffset', 'repeat'];
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
                        $keyIdx = array_search($confKey, $rmIfUnset);
                        unset($rmIfUnset[$keyIdx]);
                    }
                }
            }

            /* Removing any obsolete 'configuration' fields (those remaining in 'unusedConfKeys') */
            if ($action == 'reset')
                $toRm = $rmOnlyIfReset;
            else
                $toRm = $rmIfUnset;
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

            $cmdLogic->save();
        }

        // Removing obsolete cmds
        foreach ($jeedomCmds as $jCmdId => $jCmd) {
            if ($jCmd['obsolete'] == false)
                continue;

            log::add('Abeille', 'debug', "  Removing '".$jCmd['name']."' (".$jCmd['logicalId'].")");
            $cmdLogic = cmd::byId($jCmdId);
            $cmdLogic->remove();
        }

        // $eqLogic->refreshWidget(); // Refresh equipment display ? Required ? Useful ?

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

    // /* Update all infos related to last communication time & LQI of given device.
    //    This is based on timestamp of last communication received from device itself. */
    // public static function updateTimestamp($eqLogic, $timestamp, $lqi = null) {
    //     $eqLogicId = $eqLogic->getLogicalId();
    //     $eqId = $eqLogic->getId();

    //     // log::add('Abeille', 'debug', "  updateTimestamp(): Updating last comm. time for '".$eqLogicId."'");

    //     // Updating directly eqLogic/setStatus/'lastCommunication' & 'timeout' with real timestamp
    //     $eqLogic->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));

    //     /* Tcharp38 note:
    //        The cases hereafter could be removed. Using 'lastCommunication' allows to no longer
    //        use these 3 specific & redondant commands. To be discussed. */

    //     // $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-TimeStamp");
    //     // if (!is_object($cmdLogic))
    //     //     log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-TimeStamp'");
    //     // else
    //     //     $eqLogic->checkAndUpdateCmd($cmdLogic, $timestamp);

    //     $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, "Time-Time");
    //     if (!is_object($cmdLogic))
    //         log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Time-Time'");
    //     else
    //         $eqLogic->checkAndUpdateCmd($cmdLogic, date("Y-m-d H:i:s", $timestamp));

    //     $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'online');
    //     if (is_object($cmdLogic))
    //     //     log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'online'");
    //     // else
    //         $eqLogic->checkAndUpdateCmd($cmdLogic, 1);

    //     list($net, $addr) = explode("/", $eqLogicId);
    //     if ($addr != "0000") { // Not a gateway
    //         if ($lqi !== null) {
    //             $cmdLogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Link-Quality');
    //             if (!is_object($cmdLogic))
    //                 log::add('Abeille', 'debug', '  updateTimestamp(): WARNING: '.$eqLogicId.", missing cmd 'Link-Quality'");
    //             else
    //                 $eqLogic->checkAndUpdateCmd($cmdLogic, $lqi);
    //         }

    //         // Updating corresponding Zigate alive status too
    //         $zigate = eqLogic::byLogicalId($net.'/0000', 'Abeille');
    //         $zigate->setStatus(array('lastCommunication' => date('Y-m-d H:i:s', $timestamp), 'timeout' => 0));
    //         // Warning: lastCommunication update is not transmitted to client as not an info cmd
    //     }
    // }
}
