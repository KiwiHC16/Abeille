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

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_once(dirname(__FILE__) . '/../../resources/AbeilleDeamon/lib/Tools.php');


class Abeille extends eqLogic
{
    // Is it the health of the plugin level menu Analyse->santé ? A verifier.
    public static function health() {
        $return = array();
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        $server = socket_connect(
            $socket,
            config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1'),
            config::byKey('AbeillePort', 'Abeille', '1883')
        );
        $return[] = array(
            'test' => __('Mosquitto', __FILE__),
            'result' => ($server) ? __('OK', __FILE__) : __('NOK', __FILE__),
            'advice' => ($server) ? '' : __('Indique si Mosquitto est disponible', __FILE__),
            'state' => $server,
        );

        return $return;
    }

    public static function cronDaily() {
        log::add('Abeille', 'debug', 'Starting cronDaily ------------------------------------------------------------------------------------------------------------------------');
        /**
         * Refresh LQI once a day to get IEEE in prevision of futur changes, to get network topo as fresh as possible in json
         */
        log::add('Abeille', 'debug', 'Launching AbeilleLQI.php' );
        $cmd = "cd /var/www/html/plugins/Abeille/Network/; nohup /usr/bin/php AbeilleLQI.php >/dev/null 2>/dev/null &";
        log::add('Abeille', 'debug', $cmd );
        exec( $cmd );
    }
    
    public static function cronHourly () {
        log::add('Abeille', 'debug', 'Starting cronHourly ------------------------------------------------------------------------------------------------------------------------');
        
        log::add('Abeille', 'debug', 'Refresh Ampoule Ikea Bind et set Report');
        
        $ruche = new Abeille();
        $commandIEEE = new AbeilleCmd();
        
        // Recupere IEEE de la Ruche/ZiGate
        $rucheId = $ruche->byLogicalId('Abeille/Ruche', 'Abeille')->getId();
        // log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);
        
        $ZiGateIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
        // log::add('Abeille', 'debug', 'IEEE pour  Ruche: ' . $ZiGateIEEE);
        
        // $eqLogics = Abeille::byType('Abeille');
        $eqLogics = Abeille::byType('Abeille');
        foreach ($eqLogics as $eqLogic) {
            // log::add('Abeille', 'debug', 'Icone: '.$eqLogic->getConfiguration("icone"));
            if ( strpos("_".$eqLogic->getConfiguration("icone"),"IkeaTradfriBulb") > 0 ) {
                $topicArray = explode( "/", $eqLogic->getLogicalId() );
                $addr = $topicArray[1];
                // log::add('Abeille', 'debug', 'Short: '.$addr);
                
                /*
                 if ( is_object($elogic) ) { $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "IEEE-Addr"); }
                 $addrIEEE = $cmdlogic->execCmd();
                 log::add('Abeille', 'debug', 'IEEE: '.$addrIEEE);
                 */
                
                $abeille = new Abeille();
                $commandIEEE = new AbeilleCmd();
                
                // Recupere IEEE de la Ruche/ZiGate
                $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();
                // log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);
                
                $addrIEEE = $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'IEEE-Addr')->execCmd();
                // log::add('Abeille', 'debug', 'IEEE pour abeille: ' . $addrIEEE);
                
                
                log::add('Abeille', 'debug', 'Refresh bind and report for Ikea Bulb: '.$addr );
                Abeille::publishMosquitto( null, "CmdAbeille/Ruche/bindShort", "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0006&reportToAddress=".$ZiGateIEEE, '0' );
                sleep(5);
                Abeille::publishMosquitto( null, "CmdAbeille/Ruche/bindShort", "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0008&reportToAddress=".$ZiGateIEEE, '0' );
                sleep(5);
                Abeille::publishMosquitto( null, "CmdAbeille/Ruche/setReport", "address=".$addr."&ClusterId=0006&AttributeId=0000&AttributeType=10", '0' );
                sleep(5);
                Abeille::publishMosquitto( null, "CmdAbeille/Ruche/setReport", "address=".$addr."&ClusterId=0008&AttributeId=0000&AttributeType=20", '0' );
                
                sleep(5);
            }
        }
        
        log::add('Abeille', 'debug', 'Ending cronHourly ------------------------------------------------------------------------------------------------------------------------');
    }
    
    public static function cron15() {
        log::add('Abeille', 'debug', 'Starting cron15 ------------------------------------------------------------------------------------------------------------------------');
        /**
         * Look every 15 minutes if the kernel driver is not in error
         */
        log::add('Abeille', 'debug', 'Check USB driver potential crash' );
        $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
        $output = array();
        exec(system::getCmdSudo() . $cmd, $output);
        $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
        if ($usbZigateStatus != '0') {
            message::add("Abeille", "Erreur, le pilote pl2303 est en erreur, impossible de communiquer avec la zigate. Il faut débrancher/rebrancher la zigate et relancer le demon.");
            log::add('Abeille', 'debug', 'Ending cron15 ------------------------------------------------------------------------------------------------------------------------');
            
        }

        
        log::add('Abeille', 'debug', 'Ping NE with 220V to check Online status' );
        $eqLogics = Abeille::byType('Abeille');
        foreach ($eqLogics as $eqLogic) {
            if ( strlen($eqLogic->getConfiguration("battery_type")) == 0 ) {
                $topicArray = explode( "/", $eqLogic->getLogicalId() );
                $addr = $topicArray[1];
                if ( strlen($addr) == 4 ) {
                    // echo "Short: " . $topicArray[1];
                    log::add('Abeille', 'debug', 'Ping: '.$addr );
                    if ( $eqLogic->getConfiguration("protocol") == "" )         { Abeille::publishMosquitto( null, "CmdAbeille/" . $addr . "/Annonce", "Default",           '0' ); }
                    if ( $eqLogic->getConfiguration("protocol") == "Hue" )      { Abeille::publishMosquitto( null, "CmdAbeille/" . $addr . "/Annonce", "Hue",               '0' ); }
                    if ( $eqLogic->getConfiguration("protocol") == "OSRAM" )    { Abeille::publishMosquitto( null, "CmdAbeille/" . $addr . "/Annonce", "OSRAM",             '0' ); }
                    if ( $eqLogic->getConfiguration("protocol") == "Profalux" ) { Abeille::publishMosquitto( null, "CmdAbeille/" . $addr . "/Annonce", "AnnonceProfalux",   '0' ); }
                    if ( $eqLogic->getConfiguration("protocol") == "LEGRAND" )  { Abeille::publishMosquitto( null, "CmdAbeille/" . $addr . "/Annonce", "Default",           '0' ); }
                    
                    sleep(5);
                }
            }
        }
        log::add('Abeille', 'debug', 'Ending cron15 ------------------------------------------------------------------------------------------------------------------------');
        
        return;
    }
    
    public static function cron() {
        // Cron tourne toutes les minutes
        log::add('Abeille', 'debug', '----------- Starting cron ------------------------------------------------------------------------------------------------------------------------');
        
        
        log::add('Abeille', 'debug', '----------- Ping Zigate to check Online status' );
        Abeille::publishMosquitto( null, "CmdAbeille/Ruche/getVersion",         "Version",           '0' );
        Abeille::publishMosquitto( null, "CmdAbeille/Ruche/getNetworkStatus",   "getNetworkStatus",  '0' );
        
        /**
         * Refresh health information
         */
        log::add('Abeille', 'debug', '----------- Refresh health information' );
        $eqLogics = self::byType('Abeille');
        
        foreach ($eqLogics as $eqLogic) {
            
            if ($eqLogic->getTimeout() > 0) {
                if ( strtotime($eqLogic->getStatus('lastCommunication')) > 0 ) {
                    $last = strtotime($eqLogic->getStatus('lastCommunication'));
                }
                else {
                    $last = 0;
                }
                
                log::add('Abeille', 'debug', '--' );
                log::add('Abeille', 'debug', 'Name: '.$eqLogic->getName().' Last: '.$last.' Timeout: '.$eqLogic->getTimeout().'s - Last+TimeOut: '.($last+$eqLogic->getTimeout()).' now: '.time().' Delta: '.(time()-($last+$eqLogic->getTimeout())) );
                
                // Alerte sur TimeOut Defini
                
                if ( ($last + $eqLogic->getTimeout()) > time() ) {
                    // Ok
                    $eqLogic->setStatus('state', 'ok');
                    $eqLogic->setStatus('timeout', 0);
                }
                else {
                    // NOK
                    $eqLogic->setStatus('state', 'Time Out Last Communication');
                    $eqLogic->setStatus('timeout', 1);
                    
                }
                
                // ===============================================================================================================================
                
                log::add('Abeille', 'debug', 'Name: '.$eqLogic->getName().' lastCommunication: '.$eqLogic->getStatus("lastCommunication").' timeout value: '.$eqLogic->getTimeout().' timeout status: '.$eqLogic->getStatus('timeout').' state: '.$eqLogic->getStatus('state') );
                
            }
            else {
                $eqLogic->setStatus('state', '-');
                $eqLogic->setStatus('timeout', 0);
            }
        }
        
        // Si Inclusion status est à 1 on demande un Refresh de l information
        if ( self::checkInclusionStatus() == "01" ) {
            log::add('Abeille', 'debug', 'Inclusion Status est a 01 donc on demande de rafraichir l info.');
            self::publishMosquitto( null, "CmdAbeille/Ruche/permitJoin", "Status", '0' );
        }
        else {
            log::add('Abeille', 'debug', 'Inclusion Status est a 00 donc on ne demande pas de rafraichir l info.');
        }
        
        log::add('Abeille', 'debug', 'Ending cron ------------------------------------------------------------------------------------------------------------------------');
        
    }

    public static function deamon_info() {
        $debug_deamon_info = 0;
        if ( $debug_deamon_info ) log::add('Abeille', 'debug', '-');
        if ( $debug_deamon_info ) log::add('Abeille', 'debug', '**deamon info: IN**');
        $return = array();
        $return['log'] = 'Abeille_update';
        $return['state'] = 'nok';
        $return['configuration'] = 'nok';

        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (is_object($cron) && $cron->running()) {
            //$return['state'] = 'ok';
            if ( $debug_deamon_info ) log::add('Abeille', 'debug', 'deamon_info: J ai trouve le cron');
        }
        //deps ok ?
        $dependancy_info = self::dependancy_info();
        if ($dependancy_info['state'] == 'ok') {
            if ( $debug_deamon_info ) log::add('Abeille', 'debug', 'deamon_info: les dependances sont Ok');
            $return['launchable'] = 'ok';
        } else {
            if ( $debug_deamon_info ) log::add('Abeille', 'debug', 'deamon_info: deamon is not launchable ;-(');
            if ( $debug_deamon_info ) log::add('Abeille', 'warning', 'deamon_info: deamon is not launchable due to dependancies missing');
            $return['launchable'] = 'nok';
            //throw new Exception(__('Dépendances non installées, (re)lancer l\'installation : ', __FILE__));
            $return['launchable_message'] = 'Dépendances non installées, (re)lancer l\'installation';
            return $return;
        }

        //Parameters OK
        $parameters_info = self::getParameters();
        if ($parameters_info['state'] == 'ok') {
            if ( $debug_deamon_info ) log::add('Abeille', 'debug', 'deamon_info: J ai les parametres');
            $return['launchable'] = 'ok';
        } else {
            if ( $debug_deamon_info ) log::add('Abeille', 'debug', 'deamon_info: deamon is not launchable ;-(');
            if ( $debug_deamon_info ) log::add('Abeille', 'warning', 'deamon_info: deamon is not launchable due to parameters missing');
            $return['launchable'] = 'nok';
            throw new Exception(__('Problème de parametres, vérifier le port USB : ' . $parameters_info['AbeilleSerialPort'] . ', state: ' . $parameters_info['state'], __FILE__));
        }


        //Check for running mosquitto service
        $deamon_info = self::serviceMosquittoStart();
        if ($deamon_info['launchable'] != 'ok') {
            message::add("Abeille", "Le service mosquitto n'a pas pu être démarré.");
            throw new Exception(__('Vérifier l\'installation de mosquitto, le service ne démarre pas. Essayer de supprimer le fichier /var/log/mosquitto/mosquitto.log', __FILE__));
        }


        //check running deamon /!\ if using sudo nbprocess x2
        if ( $parameters_info['onlyTimer']=='N' ) {
            if ($parameters_info['AbeilleSerialPort']=='/tmp/zigate') {
                $nbProcessExpected = 5;
            }
            else {
                $nbProcessExpected = 4;
            }
        }
        else {
            $nbProcessExpected = 1;
        }
            
        
            // no sudo to run deamon
        exec(
            "ps -e -o '%p;%a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer|Socat).php /' | cut -d ';'  -f 1 | wc -l",
            $output
        );

        if ( $debug_deamon_info ) log::add('Abeille', 'debug', 'deamon_info, nombre de demons: ' . $output[0]);
        $nbProcess = $output[0];

        if ($nbProcess < $nbProcessExpected) {
            if ( $debug_deamon_info ) log::add(
                'Abeille',
                'info',
                'deamon_info: found ' . $nbProcess . '/' . $nbProcessExpected . ' running, at least one is missing'
            );
            $return['state'] = 'nok';
        }

        if ($nbProcess > $nbProcessExpected) {
            if ( $debug_deamon_info ) log::add(
                'Abeille',
                'error',
                'deamon_info: ' . $nbProcess . '/' . $nbProcessExpected . ' running, too many deamon running. Stopping deamons'
            );
            $return['state'] = 'nok';
            self::deamon_stop();
        }

        if ($nbProcess == $nbProcessExpected) {
            if ( $debug_deamon_info ) log::add(
                'Abeille',
                'Info',
                'deamon_info: ' . $nbProcess . '/' . $nbProcessExpected . ' running, c est ce qu on veut.'
            );
            $return['state'] = 'ok';
        }


        if ( $debug_deamon_info ) log::add(
            'Abeille',
            'debug',
            '**deamon info: OUT**  deamon launchable: ' . $return['launchable'] . ' deamon state: ' . $return['state']
        );

        return $return;
    }
    
    public static function deamon_start_cleanup($message = null) {
        // This function is used to run some cleanup before the demon start, or update the database du to data change needed.
        $debug = 0;
        $restartNeeded = 0;
        
        log::add('Abeille', 'debug', 'deamon_start_cleanup: Debut des modifications si nécessaire' );
        
        // ******************************************************************************************************************
        // Suite à la modification permettant de mettre a jour les objets sur changement de short address, il faut modifier les configuraitons des commandes en base de données.
        log::add('Abeille', 'debug', 'deamon_start_cleanup: mise a niveau pour la modification necessaire a la gestion des changements d adresse courte' );
        
        $sql = "SELECT id, logicalId, type, configuration FROM `cmd` WHERE `eqType` = 'Abeille' AND `configuration` LIKE '%topic%'";
        $rows = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
        
        foreach ($rows as $key => $row) {
            $sqlRequest = 0;
            if ( $debug ) {
                echo "-------------------------------\n";
                echo "row: \n"; var_dump(  $row );
                echo "row id: ".$row['id']."\n";
            }
            // $rowArray = json_decode($row->configuration);
            $rowArray = json_decode( $row['configuration'] );
            if ( $debug ) {
                echo "rowArray: \n"; var_dump( $rowArray );
                echo "Position: ".strpos("_".$rowArray->topic,"Abeille/")."\n";
                
                if ( $row['type'] == "info" ) { echo "test1: ok\n"; }
                if (strpos("_".$rowArray->topic,"Abeille/")==1) { echo "test2: ok\n"; }
            }
            
            if ( ($row['type'] == 'info') && (strpos("_".$rowArray->topic,"Abeille/")==1) ) {
                $rowArray->topic = str_replace("Abeille/", "", $rowArray->topic );
                $position = strpos($rowArray->topic,"/");
                if ( $position > 1 ) {
                    $rowArray->topic = substr( $rowArray->topic, $position-strlen($rowArray->topic)+1 );
                }
                $sqlRequest = 1;
            }
            
            if ($row['type'] == 'action') {
                if (strpos($rowArray->topic,"Ruche") > 1) {
                    // Je ne change pas
                }
                elseif (strpos($rowArray->topic,"Group") > 1) {
                    // Je ne change pas
                }
                else {
                    if (strpos("_".$rowArray->topic,"CmdAbeille/")==1) {
                        $rowArray->topic = str_replace("CmdAbeille/", "", $rowArray->topic );
                        $position = strpos($rowArray->topic,"/");
                        if ( $position > 1 ) {
                            $rowArray->topic = substr( $rowArray->topic, $position-strlen($rowArray->topic)+1 );
                        }
                        $sqlRequest = 1;
                    }
                }
            }
            // echo $rowArray->topic."\n";
            // var_dump( $rowArray );
            
            if ( $sqlRequest==1 ) {
                $restartNeeded = 1;
                $sql = "update cmd set logicalId='".$rowArray->topic."', configuration='".json_encode($rowArray)."' where id='".$row['id']."'";
                log::add('Abeille', 'debug', 'deamon_start_cleanup: '.$sql );
                if ( $debug ) { echo $sql."\n"; }
                // $rows = $db->fetchAll($sql);
                $rows = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
            }
        }
        

        
        // ******************************************************************************************************************
        // Espace pour les prochaines mise a niveau
        //
        //
        //
       log::add('Abeille', 'debug', 'deamon_start_cleanup: Fin des modifications si nécessaire' );
        
        if (restartNeeded == 1 ) {
            // afficher un message utilisateur pour qu il reboot le bousain.
            message::add("Abeille", "La base de données a ete mise a jour suite a la mise a jour, il faut faire un restart de jeedom.");
        }
        
        return;
    }
    
    public static function deamon_start($_debug = false) {
        log::add('Abeille', 'debug', 'deamon_start: IN');

        self::deamon_stop();
        sleep(5);
        $parameters_info = self::getParameters();

        //no need as it seems to be on cron
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            message::add("Abeille", "Vérifier la configuration, un parametre manque");
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (!is_object($cron)) {
            log::add('Abeille', 'error', 'deamon_start: Tache cron introuvable');
            message::add("Abeille", "deamon_start: Tache cron introuvable");
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->run();

        sleep(3);
        
        self::deamon_start_cleanup();
        
        sleep(3);

        $_subject = "CmdRuche/Ruche/CreateRuche";
        $_message = "";
        $_retain = 0;
        // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
        $publish = new Mosquitto\Client($parameters_info['AbeilleConId'] . '_pub_deamon_start' );
        $publish->setCredentials(
            $parameters_info['AbeilleUser'],
            $parameters_info['AbeillePass']
        );

        $publish->connect(
            $parameters_info['AbeilleAddress'],
            $parameters_info['AbeillePort'],
            60
        );

        log::add('Abeille', 'debug', 'deamon_start: *****Envoi de la creation de ruche par défaut ********');
        log::add('Abeille', 'debug', 'deamon_start: publish subject:' . $_subject . ' message: ' . $_message . 'Qos: ' . $parameters_info['AbeilleQos'] . ' retain: ' . $_retain
        );

        $publish->publish($_subject, $_message, $parameters_info['AbeilleQos'], $_retain);

        for ($i = 0; $i < 100; $i++) {
            // Loop around to permit the library to do its work
            $publish->loop(1);
        }

        $publish->disconnect();
        unset($publish);

        // Start other deamons
        $nohup = "/usr/bin/nohup";
        $php = "/usr/bin/php";
        $dirdeamon = dirname(__FILE__) . "/../../resources/AbeilleDeamon/";

        $parameters_info = self::getParameters();

        if ( $parameters_info['onlyTimer']!='Y' ) {
            $deamon1 = "AbeilleSerialRead.php";
            $paramdeamon1 = $parameters_info['AbeilleSerialPort'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));
            $log1 = " > /var/www/html/log/" . substr($deamon1, 0, (strrpos($deamon1, ".")));
            
            $deamon2 = "AbeilleParser.php";
            $paramdeamon2 = $parameters_info['AbeilleSerialPort'] . ' ' . $parameters_info['AbeilleAddress'] . ' ' . $parameters_info['AbeillePort'] .
            ' ' . $parameters_info['AbeilleUser'] . ' ' . $parameters_info['AbeillePass'] . ' ' . $parameters_info['AbeilleQos'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));
            $log2 = " > /var/www/html/log/" . substr($deamon2, 0, (strrpos($deamon2, ".")));
            
            $deamon3 = "AbeilleMQTTCmd.php";
            $paramdeamon3 = $parameters_info['AbeilleSerialPort'] . ' ' . $parameters_info['AbeilleAddress'] . ' ' . $parameters_info['AbeillePort'] .
            ' ' . $parameters_info['AbeilleUser'] . ' ' . $parameters_info['AbeillePass'] . ' ' . $parameters_info['AbeilleQos'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));
            $log3 = " > /var/www/html/log/" . substr($deamon3, 0, (strrpos($deamon3, ".")));
            
            $deamon5 = "AbeilleSocat.php";
            $paramdeamon5 = $parameters_info['AbeilleSerialPort'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille')).' '.$parameters_info['IpWifiZigate'];
            $log5 = " > /var/www/html/log/" . substr($deamon5, 0, (strrpos($deamon5, ".")));
            
            
            
            $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon5 . " " . $paramdeamon5 . $log5;
            log::add('Abeille', 'debug', 'Start deamon socat: ' . $cmd);
            exec($cmd . ' 2>&1 &');
            
            sleep(5);
            
            $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon1 . " " . $paramdeamon1 . $log1;
            log::add('Abeille', 'debug', 'Start deamon SerialRead: ' . $cmd);
            exec($cmd . ' 2>&1 &');
            
            $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon2 . " " . $paramdeamon2 . $log2;
            log::add('Abeille', 'debug', 'Start deamon Parser: ' . $cmd);
            exec($cmd . ' 2>&1 &');
            
            
            $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon3 . " " . $paramdeamon3 . $log3;
            log::add('Abeille', 'debug', 'Start deamon MQTT: ' . $cmd);
            exec($cmd . ' 2>&1 &');

            
        }
        
        $deamon4 = "AbeilleMQTTCmdTimer.php";
        $paramdeamon4 = $parameters_info['AbeilleSerialPort'] . ' ' . $parameters_info['AbeilleAddress'] . ' ' . $parameters_info['AbeillePort'] .
        ' ' . $parameters_info['AbeilleUser'] . ' ' . $parameters_info['AbeillePass'] . ' ' . $parameters_info['AbeilleQos'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));
        $log4 = " > /var/www/html/log/" . substr($deamon4, 0, (strrpos($deamon4, ".")));
        
        $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon4 . " " . $paramdeamon4 . $log4;
        log::add('Abeille', 'debug', 'Start deamon Timer: ' . $cmd);
        exec($cmd . ' 2>&1 &');


        $cmd = "";
        log::add('Abeille', 'debug', 'deamon start: OUT');
        message::removeAll('Abeille', 'unableStartDeamon');

        return true;
    }

    public static function deamon_stop() {
        log::add('Abeille', 'debug', 'deamon stop: IN');
        // Stop socat if exist
        exec("ps -e -o '%p %a' --cols=10000 | awk '/socat /' | awk '{print $1}' | tr  '\n' ' '", $output);
        log::add('Abeille', 'debug', 'deamon stop: Killing deamons: ' . implode($output,'!'));
        system::kill($output, true);
        exec(system::getCmdSudo() . "kill -15 ".implode($output,' ')." 2>&1");
        exec(system::getCmdSudo() . "kill -9 ".implode($output,' ')." 2>&1");
        
        // Stop other deamon
        exec("ps -e -o '%p %a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer|Socat).php /' | awk '{print $1}' | tr  '\n' ' '", $output);
        log::add('Abeille', 'debug', 'deamon stop: Killing deamons: ' . implode($output,'!'));
        system::kill($output, true);
        exec(system::getCmdSudo() . "kill -9 ".implode($output,' ')." 2>&1");

        // Stop main deamon
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (!is_object($cron)) {
            log::add('Abeille', 'error', 'deamon stop: Abeille, Tache cron introuvable');
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->halt();

        log::add('Abeille', 'debug', 'deamon stop: OUT');
        message::removeAll('Abeille', 'stopDeamon');
    }

    public static function dependancy_info() {
        $debug_dependancy_info = 0;
        
        $return = array();
        $return['state'] = 'nok';
        $return['progress_file'] = jeedom::getTmpFolder('Abeille') . '/dependance';
        $cmd = "dpkg -l | grep mosquitto";
        exec($cmd, $output_dpkg, $return_var);
        //lib PHP exist
        $libphp = extension_loaded('mosquitto');

        //Log debug only info
        self::serviceMosquittoStatus();

        if ($output_dpkg[0] != "" && $libphp) {
            //$return['configuration'] = 'ok';
            $return['state'] = 'ok';
        } else {
            if ($output_dpkg[0] == "") {
                log::add(
                    'Abeille',
                    'warning',
                    'Impossible de trouver le package mosquitto . Probleme d installation ?'
                );
            }
            if (!$libphp) {
                log::add(
                    'Abeille',
                    'warning',
                    'Impossible de trouver la lib php pour mosquitto.'
                );
            }

        }
        if ( $debug_dependancy_info ) log::add('Abeille', 'debug', 'dependancy_info: ' . $return['state']);
        return $return;
    }

    public static function dependancy_install() {
        log::add('Abeille', 'debug', 'Installation des dépendances: IN');
        message::add("Abeille", "L installation des dependances est en cours, n oubliez pas de lire la documentation: https://github.com/KiwiHC16/Abeille/tree/master/Documentation");
        log::remove(__CLASS__ . '_update');
        $result = array('script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder('Abeille') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
        if ($result['state'] == 'ok') {
            $result['launchable'] = 'ok';
        }
        log::add('Abeille', 'debug', 'Installation des dépendances: OUT: ' . implode($result, ' X '));
        return $result;
    }

    public static function deamon() {
        //use verified parameters
        $parameters_info = self::getParameters();

        log::add(
            'Abeille',
            'debug',
            'Parametres utilises, Host : ' . $parameters_info['AbeilleAddress'] . ',
            Port : ' . $parameters_info['AbeillePort'] . ',
            AbeilleParentId : ' . $parameters_info['AbeilleParentId'] . ',
            AbeilleConId: ' . $parameters_info['AbeilleConId'] . ',
            AbeilleUser: ' . $parameters_info['AbeilleUser'] . ',
            Abeillepass: ' . $parameters_info['AbeillePass'] . ',
            AbeilleSerialPort: ' . $parameters_info['AbeilleSerialPort'] . ',
            qos: ' . $parameters_info['AbeilleQos'] . ',
            showAllCommands: ' . $parameters_info['showAllCommands'] . ',
            ModeCreation: ' . $parameters_info['creationObjectMode'] . ',
            adresseCourteMode: ' . $parameters_info['adresseCourteMode'] . ',
            onlyTimer: ' . $parameters_info['onlyTimer'] . ',
            IpWifiZigate : '. $parameters_info['IpWifiZigate']
        );

        // https://github.com/mgdm/Mosquitto-PHP
        // http://mosquitto-php.readthedocs.io/en/latest/client.html
        $client = new Mosquitto\Client($parameters_info['AbeilleConId'] . '_pub_deamon_Loop_ForEver');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
        $client->onConnect('Abeille::connect');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
        $client->onDisconnect('Abeille::disconnect');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
        $client->onSubscribe('Abeille::subscribe');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
        $client->onMessage('Abeille::message');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onLog
        $client->onLog('Abeille::logmq');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setWill
        $client->setWill('/jeedom', "Client died :-(", $parameters_info['AbeilleQos'], 0);

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::setReconnectDelay
        $client->setReconnectDelay(1, 120, 1);

        try {
            $client->setCredentials(
                $parameters_info['AbeilleUser'],
                $parameters_info['AbeillePass']
            );

            $client->connect(
                $parameters_info['AbeilleAddress'],
                $parameters_info['AbeillePort'],
                60
            );
            $client->subscribe(
                $parameters_info['AbeilleTopic'],
                $parameters_info['AbeilleQos']
            ); // !auto: Subscribe to root topic

            log::add('Abeille', 'debug', 'Subscribe to topic ' . $parameters_info['AbeilleTopic']);

            // 1 to use loopForever et 0 to use while loop
            if (1) {
                // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
                $client->loopForever();
            } else {
                while (true) {
                    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                    $client->loop();
                    //usleep(100);
                }
            }

            $client->disconnect();
            unset($client);

        } catch (Exception $e) {
            log::add('Abeille', 'error', $e->getMessage());
        }
    }

    public static function serviceMosquittoStatus() {
        $debug_serviceMosquittoStatus = 0;

        $outputSvc = array();
        $return = array();
        $return['launchable'] = 'nok';
        $return['launchable_message'] = 'Service not running yet.';

        $cmdSvc = "expr  `service mosquitto status 2>&1 | grep -Eicv 'fail|unrecognized'` + `systemctl is-active mosquitto 2>&1 | grep -c ^active`";
        exec(system::getCmdSudo() . $cmdSvc, $outputSvc);
        $logmsg = 'Status du service mosquitto : ' . ($outputSvc[0] > 0 ? 'OK' : 'Probleme') . '   (' . implode($outputSvc, '!') . ')';
        if ( $debug_serviceMosquittoStatus ) log::add('Abeille', 'debug', $logmsg);
        //Docker workaround as service will not write a pid file for mosquitto (status will always fail)
        if (file_exists("/.dockerenv")== true ){
            $outputSvc= array();
            exec(system::getCmdSudo() . "pgrep mosquitto", $outputSvc);
            if ( $debug_serviceMosquittoStatus ) log::add('Abeille','debug','docker test: pid of mosquitto: ' . $outputSvc[0]);
        }
        if ($outputSvc[0] > 0) {
            $return['launchable'] = 'ok';
            $return['launchable_message'] = 'Service mosquitto is running.';
        }
        unset($outputSvc);
        return $return;
    }

    public static function serviceMosquittoStart() {
        $outputSvc = array();
        $return = self::serviceMosquittoStatus();
        //try to start mosquitto service if not already started.
        if ($return['launchable'] != 'ok') {
            unset($outputSvc);
            $cmdSvc = "kill `pgrep -f /usr/sbin/mosquitto` 2>&1";
            exec(system::getCmdSudo() . $cmdSvc, $outputSvc);
            log::add('Abeille', 'debug', 'kill du service mosquitto: ' . $cmdSvc . ' ' . implode($outputSvc, '!'));
            unset($outputSvc);

            $cmdSvc = "service mosquitto start 2>&1 ;systemctl start mosquitto 2>&1";
            exec(system::getCmdSudo() . $cmdSvc, $outputSvc);
            log::add('Abeille', 'debug', 'Start du service mosquitto: ' . $cmdSvc . ' ' . implode($outputSvc, '!'));
            sleep(3);
            $return = self::serviceMosquittoStatus();
        }
        return $return;
    }

    public static function getParameters() {
        $return = array();
        $return['state'] = 'nok';

        //Most Fields are defined with default values
        $return['AbeilleAddress'] = config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1');
        $return['AbeillePort'] = config::byKey('AbeillePort', 'Abeille', '1883');
        $return['AbeilleConId'] = config::byKey('AbeilleConId', 'Abeille', 'jeedom');
        $return['AbeilleUser'] = config::byKey('mqttUser', 'Abeille', 'jeedom');
        $return['AbeillePass'] = config::byKey('mqttPass', 'Abeille', 'jeedom');
        $return['AbeilleTopic'] = config::byKey('mqttTopic', 'Abeille', '#');
        $return['AbeilleSerialPort'] = config::byKey('AbeilleSerialPort', 'Abeille');
        $return['AbeilleQos'] = config::byKey('mqttQos', 'Abeille', '0');
        $return['AbeilleParentId'] = config::byKey('AbeilleParentId', 'Abeille', '1');
        $return['AbeilleSerialPort'] = config::byKey('AbeilleSerialPort', 'Abeille');
        $return['creationObjectMode'] = config::byKey('creationObjectMode', 'Abeille', 'Automatique');
        $return['adresseCourteMode'] = config::byKey('adresseCourteMode', 'Abeille', 'Automatique');
        $return['showAllCommands'] = config::byKey('showAllCommands', 'Abeille', 'N');
        $return['onlyTimer'] = config::byKey('onlyTimer', 'Abeille', 'N');
        $return['IpWifiZigate'] = config::byKey('IpWifiZigate', 'Abeille', '192.168.4.1');

        // log::add('Abeille', 'debug', 'serialPort value: ->' . $return['AbeilleSerialPort'] . '<-');
        if ($return['AbeilleSerialPort'] == "/tmp/zigate") {
            $return['state'] = 'ok';
            return $return;
        }
        if ( ($return['AbeilleSerialPort'] != 'none') || ($return['onlyTimer']!="Y") ) {
            $return['AbeilleSerialPort'] = jeedom::getUsbMapping($return['AbeilleSerialPort']);
            if (@!file_exists($return['AbeilleSerialPort'])) {
                log::add(
                    'Abeille',
                    'debug',
                    'getParameters: serialPort n\'est pas défini. ->' . $return['AbeilleSerialPort'] . '<-'
                );
                $return['launchable_message'] = __('Le port n\'est pas configuré ou zigate déconnectée', __FILE__);
                throw new Exception(__('Le port n\'est pas configuré ou zigate déconnectée : ' . $return['AbeilleSerialPort'], __FILE__));
            } else {
                if (substr(decoct(fileperms($return['AbeilleSerialPort'])),-4) != "0777") {
                    exec(system::getCmdSudo() . 'chmod 777 ' . $return['AbeilleSerialPort'] . ' > /dev/null 2>&1');
                }
                $return['state'] = 'ok';
            }
        } else {
            //if serialPort= none then nothing to check
            $return['state'] = 'ok';
        }
            
        $return['state'] = 'ok';
        return $return;
    }

    public static function postSave() {
        log::add('Abeille', 'debug', 'deamon_postSave: IN');
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (is_object($cron) && !$cron->running()) {
            $cron->run();
        }
        log::add('Abeille', 'debug', 'deamon_postSave: OUT');

    }

    public static function connect($r, $message) {
        log::add('Abeille', 'info', 'Mosquitto: Connexion à Mosquitto avec code ' . $r . ' ' . $message);
        config::save('state', '1', 'Abeille');
    }

    public static function disconnect($r) {
        log::add('Abeille', 'debug', 'Mosquitto: Déconnexion de Mosquitto avec code ' . $r);
        config::save('state', '0', 'Abeille');
    }

    public static function subscribe() {
        log::add('Abeille', 'debug', 'Mosquitto: Subscribe to topics');
    }

    public static function logmq($code, $str) {
        
           // log::add('Abeille', 'debug', 'Mosquitto: Log level: ' . $code . ' Message: ' . $str);
     
    }

    public static function fetchShortFromIEEE( $IEEE, $checkShort ) {
        // Return:
        // 0 : Short Address is aligned with the one received
        // Short : Short Address is NOT aligned with the one received
        // -1 : Error Nothing found
        
        // $lookForIEEE = "000B57fffe490C2a";
        // $checkShort = "2006";
        // log::add('Abeille', 'debug', 'BEN: start function fetchShortFromIEEE');
        $abeilles = Abeille::byType('Abeille');
        
        foreach ( $abeilles as $abeille ) {

            $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr' );
            if ( is_object($cmdIEEE) ) {
                
                if ( $cmdIEEE->execCmd() == $IEEE ) {
                    
                    $cmdShort = $abeille->getCmd('Info', 'Short-Addr' );
                    if ( $cmdShort ) {
                        if ( $cmdShort->execCmd() == $checkShort ) {
                            // echo "Success ";
                            // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return 0');
                            return 0;
                        }
                        else {
                            // echo "Pas success du tout ";
                            // La cmd short n est pas forcement à jour alors on va essayer avec le nodeId.
                            // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return Short: '.$cmdShort->execCmd() );
                            // return $cmdShort->execCmd();
                            // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return Short: '.substr($abeille->getlogicalId(),-4) );
                            return substr($abeille->getlogicalId(),-4);
                        }
                        return $return;
                    }
                }
            }
        }
        // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return -1');
        return -1;
    }

    public static function checkInclusionStatus( ) {
        // Return: Inclusion status or -1 if error
        $ruche = Abeille::byLogicalId('Abeille/Ruche', 'Abeille');
      
        // echo "Join status collection\n";
        $cmdJoinStatus = $ruche->getCmd('Info', 'permitJoin-Status' );
        if ( $cmdJoinStatus ) {
            return $cmdJoinStatus->execCmd();
        }
        
        return -1;
    }
    
    public static function message($message)
    {
        
        $parameters_info = self::getParameters();
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // demande de creation de ruche au cas ou elle n'est pas deja crée....
        // La ruche est aussi un objet Abeille
        if ($message->topic == "CmdRuche/Ruche/CreateRuche") {
            log::add('Abeille', 'debug', "Topic: ->". $message->topic . "<- Value ->" . $message->payload . "<-");
            self::createRuche($message);
            return;
        }
      
 
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // On ne prend en compte que les message Abeille|Ruche|CmdCreate/#/#
        
        // CmdCreate -> pour la creation des objets depuis la ruche par exemple pour tester les modeles
        if (!preg_match("(^Abeille|^Ruche|^CmdCreate)", $message->topic)) {
            // log::add('Abeille', 'debug', 'message: this is not a ' . $Filter . ' message: topic: ' . $message->topic . ' message: ' . $message->payload);
            return;
        }
        log::add('Abeille', 'debug', "Topic: ->". $message->topic . "<- Value ->" . $message->payload . "<-");
        

        /*----------------------------------------------------------------------------------------------------------------------------------------------*/
        // Analyse du message recu
        // [CmdAbeille:Abeille] / Address / Cluster-Parameter
        // [CmdAbeille:Abeille] / $addr / $cmdId => $value
        // $nodeId = [CmdAbeille:Abeille] / $addr
        
        $topicArray = explode("/", $message->topic);
        if ( sizeof( $topicArray ) !=3 ) {
            return ;
        }
        
        $Filter = $topicArray[0];
        if ( $Filter == "CmdCreate" ) { $Filter = "Abeille"; }
        $addr = $topicArray[1];
        $cmdId = $topicArray[2];
        $nodeid = $Filter.'/'.$addr;
        
        $value = $message->payload;
        
        $type = 'topic';         // type = topic car pas json




        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Cherche l objet par sa ref short Address et la commande
        $elogic = self::byLogicalId( $nodeid, 'Abeille');
        if ( is_object($elogic) ) { $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmdId); }
        $objetConnu = 0;

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie son nom => je créé l objet.
        if ( !is_object($elogic) && (preg_match ( "/^0000-[0-9A-F]*-*0005/", $cmdId ) || preg_match ( "/^0000-[0-9A-F]*-*0010/", $cmdId ) ) && (config::byKey('creationObjectMode', 'Abeille', 'Automatique') != "Manuel")) {
            
            log::add('Abeille', 'info', 'Recherche objet: ' . $value . ' dans les objets connus');
            //remove lumi. from name as all xiaomi devices have a lumi. name
            //remove all space in names for easier filename handling
            $trimmedValue = str_replace(' ', '', str_replace('lumi.', '', $value));
            
            // On enleve le / comme par exemple le nom des equipements Legrand
            $trimmedValue = str_replace('/', '', $trimmedValue);
            
            // On enleve les 0x00 comme par exemple le nom des equipements Legrand
            $trimmedValue = str_replace( "\0", '', $trimmedValue);
            
            log::add('Abeille', 'debug', 'value:' . $value . ' / trimmed value: ' . $trimmedValue);
            $AbeilleObjetDefinition = Tools::getJSonConfigFilebyDevices($trimmedValue, 'Abeille');
            
            //Due to various kind of naming of devices, json object is either named as value or $trimmedvalue. We need to know which one to use.
            if (array_key_exists($value, $AbeilleObjetDefinition) || array_key_exists($trimmedValue, $AbeilleObjetDefinition)) {
                $objetConnu = 1;
                $jsonName = array_key_exists($value, $AbeilleObjetDefinition) ? $value : $trimmedValue;
                log::add('Abeille', 'info', 'objet: ' . $value . ' recherché comme ' . $trimmedValue . ' peut etre cree car je connais ce type d objet.');
            } else {
                log::add('Abeille', 'info', 'objet: ' . $value . ' recherché comme ' . $trimmedValue . ' ne peut pas etre cree completement car je ne connais pas ce type d objet.');
                log::add('Abeille', 'debug', 'objet: ' . json_encode($AbeilleObjetDefinition));
            }
            
            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Creation de l objet Abeille
            // Exemple pour les objets créés par les commandes Ruche, e.g. Timer
            if (strlen($addr) != 4) {
                $index = rand(1000, 9999);
                $addr = $addr . "-" . $index;
                $nodeid = $nodeid . "-" . $index;
            }
            
            message::add("Abeille", "Création d un nouvel objet Abeille (" . $addr . ") en cours, dans quelques secondes rafraîchissez votre dashboard pour le voir.");
            $elogic = new Abeille();
            //id
            if ($objetConnu) {
                $name = "Abeille-" . $addr;
                // $name = "Abeille-" . $addr . '-' . $jsonName;
            } else {
                $name = "Abeille-" . $addr . '-' . $jsonName . "-Type d objet inconnu (!JSON)";
            }
            $elogic->setName($name);
            $elogic->setLogicalId($nodeid);
            $elogic->setObject_id($parameters_info['AbeilleParentId']);
            $elogic->setEqType_name('Abeille');
            
            $objetDefSpecific = $AbeilleObjetDefinition[$jsonName];
            
            $objetConfiguration = $objetDefSpecific["configuration"];
            $elogic->setConfiguration('topic', $nodeid);
            $elogic->setConfiguration('type', $type);
            $elogic->setConfiguration('icone', $objetConfiguration["icone"]);
            $elogic->setConfiguration('lastCommunicationTimeOut', $objetConfiguration["lastCommunicationTimeOut"]);
            $elogic->setConfiguration('type', $type);
            if (isset($objetConfiguration['battery_type'])) { $elogic->setConfiguration('battery_type', $objetConfiguration['battery_type']); }
            if (isset($objetConfiguration['protocol']))     { $elogic->setConfiguration('protocol', $objetConfiguration['protocol']); }
            $elogic->setIsVisible("1");
            
            
            // eqReal_id
            $elogic->setIsEnable("1");
            // status
            // timeout
            $elogic->setTimeout( $objetDefSpecific["timeout"] );
            // message::add("Abeille", "TimeOut in JSON:".$objetDefSpecific["timeout"] );
            
            $elogic->setCategory(
                                 array_keys($objetDefSpecific["Categorie"])[0], $objetDefSpecific["Categorie"][array_keys($objetDefSpecific["Categorie"])[0]]
                                 );
            // display
            // order
            // comment
            
            //log::add('Abeille', 'info', 'Saving device ' . $nodeid);
            //$elogic->save();
            $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
            $elogic->save();
            
            
            // Creation des commandes pour l objet Abeille juste créé.
            if ($GLOBALS['debugBEN']) {
                echo "On va creer les commandes.\n";
                print_r($objetDefSpecific['Commandes']);
            }
            
            foreach ($objetDefSpecific['Commandes'] as $cmd => $cmdValueDefaut) {
                log::add(
                         'Abeille',
                         'info',
                         // 'Creation de la commande: ' . $nodeid . '/' . $cmd . ' suivant model de l objet pour l objet: ' . $name
                         'Creation de la commande: ' . $cmd . ' suivant model de l objet pour l objet: ' . $name
                         );
                $cmdlogic = new AbeilleCmd();
                // id
                $cmdlogic->setEqLogic_id($elogic->getId());
                $cmdlogic->setEqType('Abeille');
                $cmdlogic->setLogicalId($cmd);
                $cmdlogic->setOrder($cmdValueDefaut["order"]);
                $cmdlogic->setName($cmdValueDefaut["name"]);
                // value
                
                if ($cmdValueDefaut["Type"] == "info") {
                    // $cmdlogic->setConfiguration('topic', $nodeid . '/' . $cmd);
                    $cmdlogic->setConfiguration('topic', $cmd);
                }
                if ($cmdValueDefaut["Type"] == "action") {
                    $cmdlogic->setConfiguration('retain', '0');
                    
                    if (isset($cmdValueDefaut["value"])) {
                        // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                        log::add('Abeille', 'debug', 'Define cmd info pour cmd action: ' . $elogic->getName() . " - " . $cmdValueDefaut["value"]);
                        
                        $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $elogic->getName(), $cmdValueDefaut["value"]);
                        $cmdlogic->setValue($cmdPointeur_Value->getId());
                        
                    }
                    
                }
                
                // La boucle est pour info et pour action
                foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                    // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                    $cmdlogic->setConfiguration($confKey, str_replace('#addr#', $addr, $confValue));
                    
                    // Ne pas effacer, en cours de dev.
                    // $cmdlogic->setConfiguration($confKey, str_replace('#addrIEEE#',     '#addrIEEE#',   $confValue));
                    // $cmdlogic->setConfiguration($confKey, str_replace('#ZiGateIEEE#',   '#ZiGateIEEE#', $confValue));
                    
                }
                
                // template
                $cmdlogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
                $cmdlogic->setTemplate('mobile', $cmdValueDefaut["template"]);
                $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                $cmdlogic->setType($cmdValueDefaut["Type"]);
                $cmdlogic->setSubType($cmdValueDefaut["subType"]);
                $cmdlogic->setGeneric_type($cmdValueDefaut["generic_type"]);
                // unite
                if (isset($cmdValueDefaut["units"])) {
                    $cmdlogic->setUnite($cmdValueDefaut["units"]);
                }
                
                if (isset($cmdValueDefaut["invertBinary"])) {
                    $cmdlogic->setDisplay('invertBinary', $cmdValueDefaut["invertBinary"]);
                }
                // La boucle est pour info et pour action
                // isVisible
                $parameters_info = self::getParameters();
                $isVisible = $parameters_info['showAllCommands'] == 'Y' ? "1" : $cmdValueDefaut["isVisible"];
                
                foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                    // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                    $cmdlogic->setDisplay($confKey, $confValue);
                }
                
                $cmdlogic->setIsVisible($isVisible);
                
                $cmdlogic->save();
                
                
                
                // html
                // alert
                
                $cmdlogic->save();
                
                // $elogic->checkAndUpdateCmd( $cmdlogic, $cmdValueDefaut["value"] );
                
                if ($cmdlogic->getName() == "Short-Addr") {
                    $elogic->checkAndUpdateCmd($cmdlogic, $addr);
                }
                
            }
            
            // On defini le nom de l objet
            $elogic->checkAndUpdateCmd($cmdId, $value);
            
            return;
        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande IEEE => je vais chercher l objet avec cette IEEE (si mode automatique)
        // e.g. Short address change (Si l adresse a changé, on ne peut pas trouver l objet par son nodeId)
        if (!is_object($elogic) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Manuel")) {
            log::add('Abeille', 'debug', "Mode Manuel donc je ne fais rien");
            return;
        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande IEEE => je vais créer un objet pour visualiser cet inconnu (si mode semi-automatique)
        // e.g. annonce recue en mode semi-automatique
        if (!is_object($elogic) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Semi Automatique")) {
            // On peux recevoir l'IEEE lorsqu'un equipement s'annonce. Dans ce cas on a sa ShortAddress et son IEEE. Je ne sais pas si il y a d autres scenarios
            // Soit on ne le connais pas car il est nouveau ou sa shortAddress a changée. Mais dans les deux cas il n'est pas connu sous la ref de sa shortAddress
            
            // Creation de l objet Abeille
            log::add('Abeille', 'info', 'objet: ' . $value . ' creation sans model');
            message::add("Abeille", "Création d un nouvel objet INCONNU Abeille (" . $addr . ") en cours, dans quelques secondes rafraichissez votre dashboard pour le voir.");
            $elogic = new Abeille();
            //id
            if ($objetConnu) {
                // ici pour moi on ne devrait jamais être dans cas de figure. Ce code ne sert a rien je pense.
                $name = "Abeille-" . $addr;
            } else {
                $name = "Abeille-" . $addr . "-Type d objet inconnu (IEEE)";
            }
            $elogic->setName($name);
            $elogic->setLogicalId($nodeid);
            $elogic->setObject_id($parameters_info['AbeilleParentId']);
            $elogic->setEqType_name('Abeille');

            // $objetDefSpecific = $AbeilleObjetDefinition[$value];
            // $objetConfiguration = $objetDefSpecific["configuration"];
            $elogic->setConfiguration('topic',$nodeid);
            // $elogic->setConfiguration('type', $type); $elogic->setConfiguration('icone', $objetConfiguration["icone"]);
            $elogic->setIsVisible("1");
            // eqReal_id
            $elogic->setIsEnable("1");
            // status
            // timeout
            // $elogic->setCategory(array_keys($AbeilleObjetDefinition[$value]["Categorie"])[0],$AbeilleObjetDefinition[$value]["Categorie"][  array_keys($AbeilleObjetDefinition[$value]["Categorie"])[0] ] );
            // display
            // order
            // comment

            //log::add('Abeille', 'info', 'Saving device ' . $nodeid);
            //$elogic->save();
            $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
            $elogic->save();
            
            return;

        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande IEEE => je vais chercher l objet avec cette IEEE (si mode automatique)
        // e.g. Short address change (Si l adresse a changé, on ne peut pas trouver l objet par son nodeId)
        if (!is_object($elogic) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Automatique")) {
            $ShortFound = Abeille::fetchShortFromIEEE( $value, $addr );
            if ( (strlen($ShortFound) == 4) && ($addr != "Ruche") ) {
                // Short Address has changed
                // log::add('Abeille', 'debug', "IEEE-Addr; Alerte l adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, est ce que l objet aurait changé d adresse courte" );
                // message::add("Abeille", "Alerte l adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, est ce que l objet aurait changé d adresse courte" );
                
                if ( config::byKey('adresseCourteMode', 'Abeille', 'Automatique') == "Automatique" ) {
                    log::add('Abeille', 'debug', "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, on fait la mise a jour automatique" );
                    message::add("Abeille", "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, on fait la mise a jour automatique" );
                                 
                    $elogic = self::byLogicalId("Abeille/".$ShortFound, 'Abeille');
                    
                    // Si on trouve l adresse dans le nom, on remplace par la nouvelle adresse
                    log::add('Abeille', 'debug', "IEEE-Addr; Ancien nom: ".$elogic->getName().", nouveau nom: ".str_replace($ShortFound,$addr,$elogic->getName()) );
                    $elogic->setName(str_replace($ShortFound,$addr,$elogic->getName()));
                    
                    $elogic->setLogicalId("Abeille/".$addr);
                    
                    $elogic->setConfiguration('topic', "Abeille/".$addr);
                    
                    $elogic->save();
                    
                    // Il faut aussi mettre a jour la commande short address
                    self::publishMosquitto( null, "Abeille/".$addr."/Short-Addr", $addr, '0');
                }
                else {
                    log::add('Abeille', 'debug', "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, on ne fait pas la mise a jour car pas mode automatique.");
                    message::add("Abeille", "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, on ne fait pas la mise a jour car pas mode automatique." );
                }
            }
            return;
        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet n existe pas et je recoie une commande => je drop la cmd
        // e.g. un Equipement envoie des infos, mais l objet n existe pas dans Jeedom
        if (!is_object($elogic)) {
            log::add('Abeille', 'debug', "L equipement $addr n existe pas dans Jeedom, je ne process pas la commande.");
            return;
        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si l objet exist et on recoie une IEEE
        // e.g. Un NE renvoie son annonce
        if ( is_object($elogic) && ($cmdId == "IEEE-Addr") ) {
            // Update IEEE cmd
            $IEEE = $cmdlogic->execCmd();
            if ( $IEEE == $value ) {
                log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';Ok pas de changement de l adresse IEEE, je ne fais rien.');
                return;
            }
            
            // Je ne sais pas pourquoi des fois on recoit des IEEE null
            if ( $value == "0000000000000000") {
                log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';IEEE recue est null, je ne fais rien.');
                return;
            }
            
            log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';Alerte changement de l adresse IEEE pour un equipement !!! ' . $addr . ": ".$IEEE." => ".$value);
            message::add("Abeille", "Alerte changement de l adresse IEEE pour un equipement !!! ( $addr : $IEEE => $value)" );
            $elogic->checkAndUpdateCmd($cmdlogic, $value);
        
            return;
        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Objet existe et cmd n existe pas
        if ( is_object($elogic) && !is_object($cmdlogic) ) {
            // Creons les commandes inconnues sur la base des commandes qu on recoit.
            log::add('Abeille', 'debug', 'L objet: ' . $nodeid . ' existe mais pas la commande: ' . $cmdId);
            if ($parameters_info['creationObjectMode'] == "Semi Automatique") {
                // Cree la commande avec le peu d info que l on a
                log::add('Abeille', 'info', 'Creation par defaut de la commande: ' . $nodeid . '/' . $cmdId);
                $cmdlogic = new AbeilleCmd();
                // id
                $cmdlogic->setEqLogic_id($elogic->getId());
                $cmdlogic->setEqType('Abeille');
                $cmdlogic->setLogicalId($cmdId);
                // $cmdlogic->setOrder('0');
                $cmdlogic->setName('Cmd de type inconnue - ' . $cmdId);
                $cmdlogic->setConfiguration('topic', $nodeid . '/' . $cmdId);
                
                if (isset($cmdValueDefaut["instance"])) {
                    $cmdlogic->setConfiguration('instance', $cmdValueDefaut["instance"]);
                }
                if (isset($cmdValueDefaut["class"])) {
                    $cmdlogic->setConfiguration('class', $cmdValueDefaut["class"]);
                }
                if (isset($cmdValueDefaut["index"])) {
                    $cmdlogic->setConfiguration('index', $cmdValueDefaut["index"]);
                }
                
                // if ( $cmdValueDefaut["Type"]=="action" ) { $cmdlogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd); } else { $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd); }
                // if ( $cmdValueDefaut["Type"]=="action" ) { $cmdlogic->setConfiguration('retain','0'); }
                foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                    $cmdlogic->setConfiguration($confKey, $confValue);
                }
                // template
                // $cmdlogic->setTemplate('dashboard',$cmdValueDefaut["template"]); $cmdlogic->setTemplate('mobile',$cmdValueDefaut["template"]);
                // $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                // $cmdlogic->setType($cmdValueDefaut["Type"]);
                $cmdlogic->setType('info');
                // $cmdlogic->setSubType($cmdValueDefaut["subType"]);
                $cmdlogic->setSubType("string");
                // unite
                if (isset($cmdValueDefaut["units"])) {
                    $cmdlogic->setUnite($cmdValueDefaut["units"]);
                }
                // $cmdlogic->setDisplay('invertBinary',$cmdValueDefaut["invertBinary"]);
                // isVisible
                // value
                // html
                // alert
                //$cmd->setTemplate('dashboard', 'light');
                //$cmd->setTemplate('mobile', 'light');
                //$cmd_info->setIsVisible(0);
                
                $cmdlogic->save();
                $elogic->checkAndUpdateCmd($cmdId, $cmdValueDefaut["value"]);
            }
            return;
        }
        
        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // Si equipement et cmd existe alors on met la valeur a jour
        if ( is_object($elogic) && is_object($cmdlogic) ) {
            /* Traitement particulier pour les batteries */
            if ($cmdId == "Batterie-Volt") {
                /* Volt en milli V. Max a 3,1V Min a 2,7V, stockage en % batterie */
                $elogic->setStatus('battery', ($value / 1000 - 2.7) / (3.1 - 2.7) * 100);
                $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
            }
            
            /* Traitement particulier pour la remontée de nom qui est utilisé pour les ping des routeurs */
            // if (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) {
            if ( preg_match( "/^0000-[0-9A-F]*-*0005/", $cmdId ) || preg_match( "/^0000-[0-9A-F]*-*0010/", $cmdId ) ) {
                log::add('Abeille', 'debug', 'Update ONLINE Status');
                $cmdlogicOnline = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), 'online' );
                $elogic->checkAndUpdateCmd($cmdlogicOnline, 1 );
            }
            
            // Traitement particulier pour rejeter certaines valeurs
            // exemple: le Xiaomi Wall Switch 2 Bouton envoie un On et un Off dans le même message donc Abeille recoit un ON/OFF consecutif et
            // ne sais pas vraiment le gérer donc ici on rejete le Off et on met un retour d'etat dans la commande Jeedom
            if ( $cmdlogic->getConfiguration('AbeilleRejectValue') == $value ) {
                log::add('Abeille', 'debug', 'Rejet de la valeur: '.$value);
                return;
            }
            
            $elogic->checkAndUpdateCmd($cmdlogic, $value);
            return;
        }

        log::add('Abeille', 'debug', "Tres bizarre, Message non traité, il manque probablement du code.");
        return; // function message
    }


    public static function publishMosquitto( $_id, $_subject, $_message, $_retain) {
        $parameters_info = self::getParameters();
        log::add('Abeille', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
        $publish = new Mosquitto\Client();

        $publish->setCredentials( $parameters_info['AbeilleUser'], $parameters_info['AbeillePass'] );

        $publish->connect( $parameters_info['AbeilleAddress'], $parameters_info['AbeillePort'], 60 );
        $publish->publish($_subject, $_message, $parameters_info['AbeilleQos'], $_retain);
        for ($i = 0; $i < 100; $i++) {
            // Loop around to permit the library to do its work
            $publish->loop(1);
        }
        $publish->disconnect();
        unset($publish);
    }


    public function createRuche($message = null) {
        $elogic = self::byLogicalId("Abeille/Ruche", 'Abeille');
        $parameters_info = self::getParameters();

        if (is_object($elogic)) {
            log::add('Abeille', 'debug', 'message: createRuche: objet: ' . $elogic->getLogicalId() . ' existe deja');

            // La ruche existe deja so return
            return;
        }
        // Creation de la ruche
        log::add('Abeille', 'info', 'objet ruche : creation par model');

        $cmdId = end($topicArray);
        $key = count($topicArray) - 1;
        unset($topicArray[$key]);
        $addr = end($topicArray);
        // nodeid est le topic sans le dernier champ
        $nodeid = implode($topicArray, '/');

        message::add("Abeille", "Création de l objet Ruche en cours, dans quelques secondes rafraichissez votre dashboard pour le voir.");
        $elogic = new Abeille();
        //id
        $elogic->setName("Ruche");
        $elogic->setLogicalId("Abeille/Ruche");
        if ($parameters_info['AbeilleParentId']>0) { $elogic->setObject_id($parameters_info['AbeilleParentId']); } else {  $elogic->setObject_id(null); }
        $elogic->setEqType_name('Abeille');
        $elogic->setConfiguration('topic', "Abeille/Ruche");
        $elogic->setConfiguration('type', 'topic');
        $elogic->setConfiguration('lastCommunicationTimeOut', '-1');
        $elogic->setIsVisible("1");
        $elogic->setConfiguration('icone', "Ruche");
        // eqReal_id
        $elogic->setIsEnable("1");
        // status
        $elogic->setTimeout( 300 ); // timeout
        // $elogic->setCategory();
        // display
        // order
        // comment

        //log::add('Abeille', 'info', 'Saving device ' . $nodeid);
        //$elogic->save();
        $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
        $elogic->save();

        $rucheCommandList = Tools::getJSonConfigFiles('rucheCommand.json', 'Abeille');
        $i = 100;

        //Load all commandes from defined objects (except ruche), and create them hidden in Ruche to allow debug and research.
        $items = Tools::getDeviceNameFromJson('Abeille');

        foreach ($items as $item) {
            $AbeilleObjetDefinition = Tools::getJSonConfigFilebyDevices(Tools::getTrimmedValueForJsonFiles($item), 'Abeille');
            // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
            foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
                $rucheCommandList[$objetId] = array(
                    "name" => $objetId,
                    "order" => $i++,
                    "isVisible" => "0",
                    "isHistorized" => "0",
                    "Type" => "action",
                    "subType" => "other",
                    "configuration" => array("topic" => "CmdCreate/" . $objetId . "/0000-0005", "request" => $objetId),
                );
            }
        }
        // print_r($rucheCommandList);

        //Create ruche object and commands
        foreach ($rucheCommandList as $cmd => $cmdValueDefaut) {
            $nomObjet = "Ruche";
            log::add(
                'Abeille',
                'info',
                // 'Creation de la command: ' . $nodeid . '/' . $cmd . ' suivant model de l objet: ' . $nomObjet
               'Creation de la command: ' . $cmd . ' suivant model de l objet: ' . $nomObjet
               );
            $cmdlogic = new AbeilleCmd();
            // id
            $cmdlogic->setEqLogic_id($elogic->getId());
            $cmdlogic->setEqType('Abeille');
            $cmdlogic->setLogicalId($cmd);
            $cmdlogic->setOrder($cmdValueDefaut["order"]);
            $cmdlogic->setName($cmdValueDefaut["name"]);
            if ($cmdValueDefaut["Type"] == "action") {
                // $cmdlogic->setConfiguration('topic', 'Cmd' . $nodeid . '/' . $cmd);
                $cmdlogic->setConfiguration('topic', $cmd);
            } else {
                // $cmdlogic->setConfiguration('topic', $nodeid . '/' . $cmd);
                $cmdlogic->setConfiguration('topic', $cmd);
            }
            if ($cmdValueDefaut["Type"] == "action") {
                $cmdlogic->setConfiguration('retain', '0');
            }
            foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                $cmdlogic->setConfiguration($confKey, $confValue);
            }
            // template
            $cmdlogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
            $cmdlogic->setTemplate('mobile', $cmdValueDefaut["template"]);
            $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
            $cmdlogic->setType($cmdValueDefaut["Type"]);
            $cmdlogic->setSubType($cmdValueDefaut["subType"]);
            // unite
            $cmdlogic->setDisplay('invertBinary', '0');
            // La boucle est pour info et pour action
            foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                $cmdlogic->setDisplay($confKey, $confValue);
            }
            $cmdlogic->setIsVisible($cmdValueDefaut["isVisible"]);
            // value
            // html
            // alert

            $cmdlogic->save();
            $elogic->checkAndUpdateCmd($cmdId, $cmdValueDefaut["value"]);
        }
    }
}

class AbeilleCmd extends cmd
{
    public function execute($_options = null)
    {
        // log::add('Abeille', 'Debug', 'execute function');
        switch ($this->getType()) {
            case 'action' :
                //
                /* ------------------------------ */
                // Topic est l arborescence MQTT: La fonction Zigate
                
                $Abeilles = new Abeille();
                
                $NE_Id = $this->getEqLogic_id();
                $NE = $Abeilles->byId( $NE_Id );
                
                // ob_start();
                // var_dump($NE);
                // $result = ob_get_clean();
                // log::add('Abeille', 'Debug', $result);
                
                // log::add('Abeille', 'Debug', $this->getConfiguration('topic') );
                
                if ( strpos( "_".$this->getConfiguration('topic'), "CmdAbeille" ) == 1 ) {
                    $topic = $this->getConfiguration('topic');
                }
                else if (strpos( "_".$this->getConfiguration('topic'), "CmdTimer" ) == 1 ) {
                    $topic = $this->getConfiguration('topic');
                }
                else if (strpos( "_".$this->getConfiguration('topic'), "CmdCreate" ) == 1 ) {
                    $topic = $this->getConfiguration('topic');
                }
                else {
                    $topic = "Cmd".$NE->getConfiguration('topic')."/".$this->getConfiguration('topic');
                    // $topic = $this->getConfiguration('topic');

                }
                    
                /* ------------------------------ */
                // Je fais les remplacement dans les parametres
                
                // request: c'est le payload dans la page de configuration pour une commande
                // C est les parametres de la commande pour la zigate
                $request = $this->getConfiguration('request', '1');
                
                if (strpos($request, '#addrIEEE#') > 0) {
                    $ruche = new Abeille();
                    $commandIEEE = new AbeilleCmd();

                    // Recupere IEEE de la Ruche/ZiGate
                    $rucheId = $ruche->byLogicalId('Abeille/Ruche', 'Abeille')->getId();
                    log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);

                    $rucheIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
                    log::add('Abeille', 'debug', 'IEEE pour  Ruche: ' . $rucheIEEE);

                    $currentCommandId = $this->getId();
                    $currentObjectId = $this->getEqLogic_id();
                    log::add('Abeille', 'debug', 'Id pour current abeille: ' . $currentObjectId);

                    // ne semble pas rendre la main si l'objet n'a pas de champ "IEEE-Addr"
                    $commandIEEE = $commandIEEE->byEqLogicIdAndLogicalId($currentObjectId, 'IEEE-Addr')->execCmd();

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
                Abeille::publishMosquitto($this->getId(), $topic, $request, $this->getConfiguration('retain', '0'));
        }

        return true;
    }
}
    
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// La suite is Used for test
// en ligne de comande =>
// "php Abeille.class.php 1" to run the script to create any of the item listed in array L1057
// "php Abeille.class.php 2" to run the script to create a ruche object
// "php Abeille.class.php" to parse the file and verify syntax issues.
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
if (isset($argv[1])) {
    $debugBEN = $argv[1];
} else {
    $debugBEN = 0;
}
if ($debugBEN != 0) {
    echo "Debut\n";
    $message = new stdClass();


    switch ($debugBEN) {

        // Creation des objets sur la base des modeles pour verifier la bonne creation dans Abeille
        case "1":
            $items = Tools::getDeviceNameFromJson('Abeille');
            //problem icon creation
            foreach ($items as $item) {
                $name = str_replace(' ', '.', $item);
                $message->topic = "Abeille/$name/0000-0005";
                $message->payload = $item;
                Abeille::message($message);
                sleep(2);
            }
            break;
            
        // Demande la creation de la ruche
        case "2":
            $message->topic = "CmdRuche/Ruche/CreateRuche";
            $message->payload = "";
            Abeille::message($message);
            break;

        // Verifie qu on recupere les IEEE pour les remplacer dans les commandes
        case "3":
            $ruche = new Abeille();
            $commandIEEE = new AbeilleCmd();

            // Recupere IEEE de la Ruche/ZiGate
            $rucheId = $ruche->byLogicalId('Abeille/Ruche', 'Abeille')->getId();
            echo 'Id pour abeille Ruche: ' . $rucheId . "\n";

            $rucheIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
            echo 'IEEE pour  Ruche: ' . $rucheIEEE . "\n";

            // $currentCommandId = $this->getId();
            // $currentObjectId = $this->getEqLogic_id();
            $currentObjectId = 284;
            echo 'Id pour current abeille: ' . $currentObjectId . "\n";

            $commandIEEE = $commandIEEE->byEqLogicIdAndLogicalId($currentObjectId, 'IEEE-Addr')->execCmd();
            // print_r( $command->execCmd() );
            echo 'IEEE pour current abeille: ' . $commandIEEE . "\n";

            // $elogic->byLogicalId( 'Abeille/b528', 'Abeille' );
            // print_r( $objet->byLogicalId( 'Abeille/b528', 'Abeille' )->getId() );
            // echo "\n";
            // print_r( $command->byEqLogicIdAndLogicalId( $objetId, "IEEE-Addr" )->getLastValue() );

            $request = str_replace('#addrIEEE#', "'" . $commandIEEE . "'", $request);
            $request = str_replace('#ZiGateIEEE#', "'" . $rucheIEEE . "'", $request);

            break;
            
        // Test la verification des last communication dans la page health
        case "4":
            $eqLogics=Abeille::byType('Abeille');
            
            //var_dump( $eqLogics );
            
            foreach ($eqLogics as $eqLogic) {
                // var_dump( $eqLogic );
                
                // $eqLogic->setStatus('lastCommunication', '2018-05-01 00:00:01');
                // $eqLogic->setStatus('state', 'unknown');
                
                // Pour tester. Force une data.
                // if ( $eqLogic->getName()=="Abeille-d09c") { $eqLogic->setStatus('lastCommunication', '2018-05-10 12:48:01'); }
                
                // Default Time Out : 24 heures
                $lastCommunicationTimeOut = 24 * 60 * 60;
                // $lastCommunicationTimeOut = 0; // Pour test
// =================================================
                // Si equipement a un TimeOut Specifique, -1 pour ne pas tester
                if ($eqLogic->getConfiguration("lastCommunicationTimeOut")) {
                    $lastCommunicationTimeOut = $eqLogic->getConfiguration("lastCommunicationTimeOut");
                    if ( $lastCommunicationTimeOut != -1 ){
                        if ( strtotime($eqLogic->getStatus('lastCommunication')) + $lastCommunicationTimeOut > time() ) {
                            // Ok
                            $eqLogic->setStatus('state', 'ok');
                        }
                        else {
                            // NOK
                            $eqLogic->setStatus('state', 'Time Out Last Communication');
                        }
                    }
                    else{
                        $eqLogic->setStatus('state', '-');
                    }
                }
                // Si pas de Timer specifique, 24h et 7jours comme seuil d'alerte
                else {
                    $last = strtotime($eqLogic->getStatus('lastCommunication'));
                    if ( $last > time() - $lastCommunicationTimeOut ) {
                        // Ok
                        $eqLogic->setStatus('state', 'ok');
                    }
                    elseif ( $last < time() - $lastCommunicationTimeOut*7 ) {
                        // NOK 7d
                        $eqLogic->setStatus('state', 'Very Old Last Communication (>7days)');
                    }
                    else {
                        // NOK 24h
                        $eqLogic->setStatus('state', 'Old Last Communication (>24h)');
                    }
                }
// =================================================
                echo $eqLogic->getName() . "<->" . (strtotime($eqLogic->getStatus('lastCommunication'))-time()) . "<->" . $eqLogic->getStatus('state') . "\n";
                
            }
            break;
            
        // Cherche l objet qui a une IEEE specifique
        case "5":
            // Info ampoue T7
            $lookForIEEE = "000B57fffe490C2a";
            $checkShort = "2096";
            
            if (0) {
            $abeilles = Abeille::byType('Abeille');
            // var_dump( $abeilles );
            
            foreach ( $abeilles as $num => $abeille ) {
                //var_dump( $abeille );
                // var_dump( $abeille->getCmd('Info', 'IEEE-Addr' ) );
                $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr' );
                if ( $cmdIEEE ) {
                    // var_dump( $cmd );
                    
                    if ( $cmdIEEE->execCmd() == $lookFor ) {
                        echo "Found it\n";
                        $cmdShort = $abeille->getCmd('Info', 'Short-Addr' );
                        if ( $cmdShort ) {
                            echo $cmdShort->execCmd()." ";
                            if ( $cmdShort->execCmd() == $check ) {
                                echo "Success ";
                                return 1;
                            }
                            else {
                                echo "Pas success du tout ";
                                return 0;
                            }
                        }
                    }
                    echo $cmdIEEE->execCmd()."\n-----\n";
                }
            }
            return $cmd;
            }
            else {
                Abeille::fetchShortFromIEEE( $lookForIEEE, $checkShort );
            }
            
            break;
        
        // Ask Model Identifier to all equipement without battery info, those equipement should be awake
        case "6":
            log::add('Abeille', 'debug', 'Ping routers to check Online status' );
            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                // echo "Battery: ".$collectBattery = $eqLogic->getStatus("battery")."\n";
                // echo "Battery: ".$collectBattery = $eqLogic->getConfiguration("battery_type")." - ";
                if ( strlen($eqLogic->getConfiguration("battery_type")) == 0 ) {
                    $topicArray = explode( "/", $eqLogic->getLogicalId() );
                    $addr = $topicArray[1];
                    if ( strlen($addr) == 4 ) {
                        echo "Short: " . $topicArray[1];
                        Abeille::publishMosquitto( null, "CmdAbeille/" . $addr . "/Annonce", "Default", '0' );
                    }
                    
                }
                echo "\n";
            }
            break;
            
        // Check Inclusion status
        case "7":
            echo "Check inclusion status\n";
            echo Abeille::checkInclusionStatus();

            break;
            
        case "8":
            echo "Check cleanup\n";
            Abeille::deamon_start_cleanup();
            break;
            
    } // switch

    echo "Fin\n";
}
    
    
