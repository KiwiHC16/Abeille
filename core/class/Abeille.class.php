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
    public static function health()
    {
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


    public static function cron15()
    {
        log::add('Abeille', 'debug', 'Starting cron15 ------------------------------------------------------------------------------------------------------------------------');
        /**
         * Look every 15 minutes if the kernel driver is not in error
         */
        
        $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
        $output = array();
        exec(system::getCmdSudo() . $cmd, $output);
        $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
        if ($usbZigateStatus != '0') {
            message::add("Abeille", "Erreur, le pilote pl2303 est en erreur, impossible de communiquer avec la zigate. Il faut débrancher/rebrancher la zigate et relancer le demon.");
            log::add('Abeille', 'debug', 'Ending cron15 ------------------------------------------------------------------------------------------------------------------------');
            return;
            
        }
        log::add('Abeille', 'debug', 'Ending cron15 ------------------------------------------------------------------------------------------------------------------------');
    }
    
    public static function cron()
    {
        log::add('Abeille', 'debug', 'Starting cron ------------------------------------------------------------------------------------------------------------------------');
        /**
         * Refresh health information
         */
        // $eqLogics=Abeille::byType('Abeille');
        $eqLogics = self::byType('Abeille');
        
        //var_dump( $eqLogics );
        
        foreach ($eqLogics as $eqLogic) {
            // var_dump( $eqLogic );
            
            // $eqLogic->setStatus('lastCommunication', '2018-05-12 00:44:17');
            // $eqLogic->setStatus('state', 'unknown');
            
            // Default Time Out : 24 heures => Warning
            $lastCommunicationTimeOut = 24 * 60 * 60;
            // $lastCommunicationTimeOut = 0; // Pour test
            
            // ===============================================================================================================================
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
            // ===============================================================================================================================
            
            log::add('Abeille', 'debug', 'Name: '.$eqLogic->getName().' lastCommunication: '.$eqLogic->getStatus("lastCommunication"));
            
            // echo $eqLogic->getStatus('state');
            
        }
        
        log::add('Abeille', 'debug', 'Ending cron ------------------------------------------------------------------------------------------------------------------------');
        
    }


    public static function deamon_info()
    {
        log::add('Abeille', 'debug', '-');
        log::add('Abeille', 'debug', '**deamon info: IN**');
        $return = array();
        $return['log'] = 'Abeille_update';
        $return['state'] = 'nok';
        $return['configuration'] = 'nok';

        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (is_object($cron) && $cron->running()) {
            //$return['state'] = 'ok';
            log::add('Abeille', 'debug', 'deamon_info: J ai trouve le cron');
        }
        //deps ok ?
        $dependancy_info = self::dependancy_info();
        if ($dependancy_info['state'] == 'ok') {
            log::add('Abeille', 'debug', 'deamon_info: les dependances sont Ok');
            $return['launchable'] = 'ok';
        } else {
            log::add('Abeille', 'debug', 'deamon_info: deamon is not launchable ;-(');
            log::add('Abeille', 'warning', 'deamon_info: deamon is not launchable due to dependancies missing');
            $return['launchable'] = 'nok';
            //throw new Exception(__('Dépendances non installées, (re)lancer l\'installation : ', __FILE__));
            $return['launchable_message'] = 'Dépendances non installées, (re)lancer l\'installation';
            return $return;
        }

        //Parameters OK
        $parameters_info = self::getParameters();
        if ($parameters_info['state'] == 'ok') {
            log::add('Abeille', 'debug', 'deamon_info: J ai les parametres');
            $return['launchable'] = 'ok';
        } else {
            log::add('Abeille', 'debug', 'deamon_info: deamon is not launchable ;-(');
            log::add('Abeille', 'warning', 'deamon_info: deamon is not launchable due to parameters missing');
            $return['launchable'] = 'nok';
            throw new Exception(__('Problème de parametres, vérifier le port USB : ' . $parameters_info['AbeilleSerialPort'] . ', state: ' . $parameters_info['state'], __FILE__));
        }


        //Check for running mosquitto service
        $deamon_info = self::serviceMosquittoStart();
        if ($deamon_info['launchable'] != 'ok') {
            message::add("Abeille", "Le service mosquitto n'a pas pu être démarré");
            throw new Exception(__('Vérifier l\'installation de mosquitto, le service ne démarre pas', __FILE__));
        }


        //check running deamon /!\ if using sudo nbprocess x2
        $nbProcessExpected = 4; // no sudo to run deamon
        exec(
            "ps -e -o '%p;%a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer).php /' | cut -d ';'  -f 1 | wc -l",
            $output
        );

        log::add('Abeille', 'debug', 'deamon_info, nombre de demons: ' . $output[0]);
        $nbProcess = $output[0];

        if ($nbProcess < $nbProcessExpected) {
            log::add(
                'Abeille',
                'info',
                'deamon_info: found ' . $nbProcess . '/' . $nbProcessExpected . ' running, at least one is missing'
            );
            $return['state'] = 'nok';
        }

        if ($nbProcess > $nbProcessExpected) {
            log::add(
                'Abeille',
                'error',
                'deamon_info: ' . $nbProcess . '/' . $nbProcessExpected . ' running, too many deamon running. Stopping deamons'
            );
            $return['state'] = 'nok';
            self::deamon_stop();
        }

        if ($nbProcess == $nbProcessExpected) {
            log::add(
                'Abeille',
                'Info',
                'deamon_info: ' . $nbProcess . '/' . $nbProcessExpected . ' running, c est ce qu on veut.'
            );
            $return['state'] = 'ok';
        }


        log::add(
            'Abeille',
            'debug',
            '**deamon info: OUT**  deamon launchable: ' . $return['launchable'] . ' deamon state: ' . $return['state']
        );

        return $return;
    }

    public static function deamon_start($_debug = false)
    {
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

        $_id = "BEN_Start"; // JE ne sais pas alors je mets n importe quoi....
        $_subject = "CmdRuche/Ruche/CreateRuche";
        $_message = "";
        $_retain = 0;
        // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
        $publish = new Mosquitto\Client($parameters_info['AbeilleConId'] . '_pub_' . $_id);
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

        $deamon1 = "AbeilleSerialRead.php";
        $paramdeamon1 = $parameters_info['AbeilleSerialPort'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));

        $deamon2 = "AbeilleParser.php";
        $paramdeamon2 = $parameters_info['AbeilleSerialPort'] . ' ' . $parameters_info['AbeilleAddress'] . ' ' . $parameters_info['AbeillePort'] .
            ' ' . $parameters_info['AbeilleUser'] . ' ' . $parameters_info['AbeillePass'] . ' ' . $parameters_info['AbeilleQos'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));

        $deamon3 = "AbeilleMQTTCmd.php";
        $paramdeamon3 = $parameters_info['AbeilleSerialPort'] . ' ' . $parameters_info['AbeilleAddress'] . ' ' . $parameters_info['AbeillePort'] .
            ' ' . $parameters_info['AbeilleUser'] . ' ' . $parameters_info['AbeillePass'] . ' ' . $parameters_info['AbeilleQos'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));

        $deamon4 = "AbeilleMQTTCmdTimer.php";
        $paramdeamon4 = $parameters_info['AbeilleSerialPort'] . ' ' . $parameters_info['AbeilleAddress'] . ' ' . $parameters_info['AbeillePort'] .
            ' ' . $parameters_info['AbeilleUser'] . ' ' . $parameters_info['AbeillePass'] . ' ' . $parameters_info['AbeilleQos'] . ' ' . log::convertLogLevel(log::getLogLevel('Abeille'));

        $log1 = " > /var/www/html/log/" . substr($deamon1, 0, (strrpos($deamon1, ".")));
        $log2 = " > /var/www/html/log/" . substr($deamon2, 0, (strrpos($deamon2, ".")));
        $log3 = " > /var/www/html/log/" . substr($deamon3, 0, (strrpos($deamon3, ".")));
        $log4 = " > /var/www/html/log/" . substr($deamon4, 0, (strrpos($deamon4, ".")));

        $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon1 . " " . $paramdeamon1 . $log1;
        log::add('Abeille', 'debug', 'Start deamon SerialRead: ' . $cmd);
        exec($cmd . ' 2>&1 &');

        $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon2 . " " . $paramdeamon2 . $log2;
        log::add('Abeille', 'debug', 'Start deamon Parser: ' . $cmd);
        exec($cmd . ' 2>&1 &');


        $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon3 . " " . $paramdeamon3 . $log3;
        log::add('Abeille', 'debug', 'Start deamon MQTT: ' . $cmd);
        exec($cmd . ' 2>&1 &');

        $cmd = $nohup . " " . $php . " " . $dirdeamon . $deamon4 . " " . $paramdeamon4 . $log4;
        log::add('Abeille', 'debug', 'Start deamon Timer: ' . $cmd);
        exec($cmd . ' 2>&1 &');


        $cmd = "";
        log::add('Abeille', 'debug', 'deamon start: OUT');
        message::removeAll('Abeille', 'unableStartDeamon');

        return true;
    }

    public
    static function deamon_stop()
    {
        log::add('Abeille', 'debug', 'deamon stop: IN');
        // Stop other deamon
        exec("ps -e -o '%p %a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer).php /' | awk '{print $1}' | tr  '\n' ' '", $output);
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


    public
    static function dependancy_info()
    {
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
        log::add('Abeille', 'debug', 'dependancy_info: ' . $return['state']);
        return $return;
    }

    public static function dependancy_install()
    {
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

    public static function deamon()
    {
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
            ModeCreation: ' . $parameters_info['creationObjectMode']
        );

        // https://github.com/mgdm/Mosquitto-PHP
        // http://mosquitto-php.readthedocs.io/en/latest/client.html
        $client = new Mosquitto\Client($parameters_info['AbeilleConId']);

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
        $client->onConnect('Abeille::connect');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onDisconnect
        $client->onDisconnect('Abeille::disconnect');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onSubscribe
        $client->onSubscribe('Abeille::subscribe');

        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onMessage
        $client->onMessage('Abeille::message');

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


    public function serviceMosquittoStatus()
    {

        $outputSvc = array();
        $outputStl = array();
        $return = array();
        $return['launchable'] = 'nok';
        $return['launchable_message'] = 'Service not running yet.';

        $cmdSvc = "expr  `service mosquitto status 2>&1 | grep -c 'running'` + `systemctl is-active mosquitto 2>&1 | grep -c ^active`";
        exec(system::getCmdSudo() . $cmdSvc, $outputSvc);
        $logmsg = 'Status du service mosquitto : ' . ($outputSvc[0] > 0 ? 'OK' : 'Probleme') . '   (' . implode($outputSvc, '!') . ')';
        log::add('Abeille', 'debug', $logmsg);
        if ($outputSvc[0] > 0) {
            $return['launchable'] = 'ok';
            $return['launchable_message'] = 'Service mosquitto is running.';
        }
        unset($outputSvc);
        return $return;
    }

    public static function serviceMosquittoStart()
    {
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

    public static function getParameters()
    {
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
        $return['showAllCommands'] = config::byKey('showAllCommands', 'Abeille', 'N');

        // log::add('Abeille', 'debug', 'serialPort value: ->' . $return['AbeilleSerialPort'] . '<-');
        if ($return['AbeilleSerialPort'] != 'none') {
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

    public function postSave()
    {
        log::add('Abeille', 'debug', 'deamon_postSave: IN');
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (is_object($cron) && !$cron->running()) {
            $cron->run();
        }
        log::add('Abeille', 'debug', 'deamon_postSave: OUT');

    }

    public static function connect($r, $message)
    {
        log::add('Abeille', 'info', 'Connexion à Mosquitto avec code ' . $r . ' ' . $message);
        config::save('state', '1', 'Abeille');
    }

    public static function disconnect($r)
    {
        log::add('Abeille', 'debug', 'Déconnexion de Mosquitto avec code ' . $r);
        config::save('state', '0', 'Abeille');
    }

    public
    static function subscribe()
    {
        log::add('Abeille', 'debug', 'Subscribe to topics');
    }

    public static function logmq($code, $str)
    {
        if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
            log::add('Abeille', 'debug', $code . ' : ' . $str);
        }
    }


    public
    static function message($message)
    {

        if ($GLOBALS['debugBEN']) {
            echo "Function message.\n";
        }
        if ($GLOBALS['debugBEN']) {
            print_r($message);
            echo "\n";
        }
        log::add('Abeille', 'debug', '--- process a new message -----------------------');
        log::add('Abeille', 'debug', 'Message ->' . $message->payload . '<- sur ' . $message->topic);
        $parameters_info = self::getParameters();
        /*----------------------------------------------------------------------------------------------------------------------------------------------*/
        // Analyse du message recu et definition des variables en fonction de ce que l on trouve dans le message
        // [CmdAbeille:Abeille] / Address / Cluster-Parameter
        // [CmdAbeille:Abeille] / $addr / $cmdId => $value
        // $nodeId = [CmdAbeille:Abeille] / $addr

        $topicArray = explode("/", $message->topic);

        // cmdId est le dernier champ du topic
        $cmdId = end($topicArray);
        $key = count($topicArray) - 1;
        unset($topicArray[$key]);
        $addr = end($topicArray);
        // nodeid est le topic sans le dernier champ
        $nodeid = implode($topicArray, '/');
        $value = $message->payload;
        // type = topic car pas json
        $type = 'topic';
        $Filter = $topicArray[0];

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

        // demande de creation de ruche au cas ou elle n'est pas deja crée....
        // La ruche est aussi un objet Abeille
        if ($message->topic == "CmdRuche/Ruche/CreateRuche") {
            self::createRuche($message);
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
        // On ne prend en compte que les message Abeille/#/#
        if (!preg_match("(Abeille|Ruche)", $Filter)) {
            log::add('Abeille', 'debug', 'message: this is not a ' . $Filter . ' message: topic: ' . $message->topic . ' message: ' . $message->payload);
            return;
        }

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

        // Est ce que cet equipement existe deja ? Sinon creation quand je recois son nom
        // Cherche l objet
        $elogic = self::byLogicalId($nodeid, 'Abeille');
        $objetConnu = 0;


        // Je viens de revoir son nom donc je créé l objet.
        if (!is_object($elogic) && (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) && (config::byKey('creationObjectMode', 'Abeille', 'Automatique') != "Manuel")) {

            log::add('Abeille', 'info', 'Recherche objet: ' . $value . ' dans les objets connus');
            //remove lumi. from name as all xiaomi devices have a lumi. name
            //remove all space in names for easier filename handling
            $trimmedValue = str_replace(' ', '', str_replace('lumi.', '', $value));

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
            // Creation de l objet Abeille (hors ruche)
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
            $elogic->setIsVisible("1");
            $elogic->setConfiguration('type', $type);
            if (isset($objetConfiguration['battery_type'])) {
                log::add('Abeille', 'debug', 'define battery: ' . $objetConfiguration['battery_type']);
                $elogic->setConfiguration('battery_type', $objetConfiguration['battery_type']);
            }

            // eqReal_id
            $elogic->setIsEnable("1");
            // status
            // timeout
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
                    'Creation de la commande: ' . $nodeid . '/' . $cmd . ' suivant model de l objet pour l objet: ' . $name
                );
                $cmdlogic = new AbeilleCmd();
                // id
                $cmdlogic->setEqLogic_id($elogic->getId());
                $cmdlogic->setEqType('Abeille');
                $cmdlogic->setLogicalId($cmd);
                $cmdlogic->setOrder($cmdValueDefaut["order"]);
                $cmdlogic->setName($cmdValueDefaut["name"]);

                if ($cmdValueDefaut["Type"] == "info") {
                    $cmdlogic->setConfiguration('topic', $nodeid . '/' . $cmd);
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

                // value
                if ($cmdValueDefaut["Type"] == "action") {
                    $cmdlogic->setConfiguration('retain', '0');

                    if (isset($cmdValueDefaut["value"])) {
                        // value: pour les commandes action, contient la commande info qui est la valeur actuel de la variable controlée.
                        log::add('Abeille', 'debug', 'Define cmd info pour cmd action: ' . $elogic->getName() . " - " . $cmdValueDefaut["value"]);

                        $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName("Abeille", $elogic->getName(), $cmdValueDefaut["value"]);
                        $cmdlogic->setValue($cmdPointeur_Value->getId());

                    }

                } elseif ($cmdValueDefaut["Type"] == "info") {
                    if (isset($cmdValueDefaut["value"])) {
                        // Commanted as not sure what to do with this parameter in info cmd case.
                        //        $cmdlogic->setConfiguration('value', $cmdValueDefaut["value"]);
                    }
                } else {
                    log::add('Abeille', 'debug', 'Define cmd de type inconnu');
                }

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

        } else {
            // Si je recois une commande IEEE pour un objet qui n'existe pas je vais créer un objet pour visualiser cet inconnu
            if (!is_object(
                    $elogic
                ) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Semi Automatique")) {
                // Creation de l objet Abeille (hors ruche)
                log::add('Abeille', 'info', 'objet: ' . $value . ' creation sans model');
                message::add("Abeille", "Création d un nouvel objet INCONNU Abeille (" . $addr . ") en cours, dans quelques secondes rafraichissez votre dashboard pour le voir.");
                $elogic = new Abeille();
                //id
                if ($objetConnu) {
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
                $elogic->setConfiguration(
                    'topic',
                    $nodeid
                ); // $elogic->setConfiguration('type', $type); $elogic->setConfiguration('icone', $objetConfiguration["icone"]);

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

            } else {
                // Si l objet dans Jeedom n existe pas on va interroger l objet pour en savoir plus, s il repond on pourra le construire.
                if (!is_object($elogic)) {
                    if (0) {
                        log::add('Abeille', 'debug', 'L objet n existe pas: ' . $nodeid);
                        $_id = "BEN"; // JE ne sais pas alors je mets n importe quoi....
                        $_subject = "CmdAbeille/" . $addr . "/Annonce";
                        $_message = "";
                        $_retain = 0;
                        log::add('Abeille', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
                        $publish = new Mosquitto\Client(
                            $parameters_info['AbeilleConId'] . '_pub_' . $_id
                        );

                        $publish->setCredentials(
                            $parameters_info['AbeilleUser'],
                            $parameters_info['AbeillePass']
                        );

                        $publish->connect(
                            $parameters_info['AbeilleAddress'],
                            $parameters_info['AbeillePort'],
                            60
                        );
                        $publish->publish(
                            $_subject,
                            $_message,
                            $parameters_info['AbeilleQos'],
                            $_retain
                        );
                        for ($i = 0; $i < 100; $i++) {
                            // Loop around to permit the library to do its work
                            $publish->loop(1);
                        }
                        $publish->disconnect();
                        unset($publish);
                    }
                } else {
                    $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmdId);
                    if (!is_object($cmdlogic)) {
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
                    } else // Si equipement et cmd existe alors on met la valeur a jour
                    {
                        // $elogic->checkAndUpdateCmd($cmdId, $value);
                        $elogic->checkAndUpdateCmd($cmdlogic, $value);
                        $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
                        // $elogic->setStatus('state', 'toto');

                        /* Traitement particulier pour les batteries */
                        if ($cmdId == "Batterie-Volt") {
                            /* Volt en milli V. Max a 3,1V Min a 2,7V, stockage en % batterie */
                            $elogic->setStatus('battery', ($value / 1000 - 2.7) / (3.1 - 2.7) * 100);
                            $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                        }
                    }
                }
            }
        }


        /*
         else {
         // payload is json
         $json = json_decode($value, true);
         foreach ($json as $cmdId => $value) {
         $topicjson = $nodeid . '{' . $cmdId . '}';
         log::add('Abeille', 'info', 'Message json : ' . $value . ' pour information : ' . $cmdId);
         $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
         if (!is_object($cmdlogic)) {
         log::add('Abeille', 'info', 'Cmdlogic n existe pas, creation');
         $cmdlogic = new AbeilleCmd();
         $cmdlogic->setEqLogic_id($elogic->getId());
         $cmdlogic->setEqType('Abeille');
         $cmdlogic->setSubType('string');
         $cmdlogic->setLogicalId($cmdId);
         $cmdlogic->setType('info');
         $cmdlogic->setName( $cmdId );
         $cmdlogic->setConfiguration('topic', $topicjson);
         $cmdlogic->save();
         }
         $elogic->checkAndUpdateCmd($cmdId,$value);
         }
         */
    }


    public
    static function publishMosquitto(
        $_id,
        $_subject,
        $_message,
        $_retain
    )
    {
        $parameters_info = self::getParameters();
        log::add('Abeille', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
        $publish = new Mosquitto\Client($parameters_info['AbeilleConId'] . '_pub_' . $_id);

        $publish->setCredentials(
            $parameters_info['AbeilleUser'],
            $parameters_info['AbeillePass']
        );

        $publish->connect(
            $parameters_info['AbeilleAddress'],
            $parameters_info['AbeillePort'],
            60
        );
        $publish->publish($_subject, $_message, $parameters_info['AbeilleQos'], $_retain);
        for ($i = 0; $i < 100; $i++) {
            // Loop around to permit the library to do its work
            $publish->loop(1);
        }
        $publish->disconnect();
        unset($publish);
    }


    public function createRuche($message = null)
    {
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
        $elogic->setObject_id($parameters_info['AbeilleParentId']);
        $elogic->setEqType_name('Abeille');
        $elogic->setConfiguration('topic', "Abeille/Ruche");
        $elogic->setConfiguration('type', 'topic');
        $elogic->setConfiguration('lastCommunicationTimeOut', '-1');
        $elogic->setIsVisible("1");
        $elogic->setConfiguration('icone', "Ruche");
        // eqReal_id
        $elogic->setIsEnable("1");
        // status
        // timeout
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
                    "configuration" => array("topic" => "Abeille/" . $objetId . "/0000-0005", "request" => $objetId),
                );
            }
        }
        print_r($rucheCommandList);

        //Create ruche object and commands
        foreach ($rucheCommandList as $cmd => $cmdValueDefaut) {
            $nomObjet = "Ruche";
            log::add(
                'Abeille',
                'info',
                'Creation de la command: ' . $nodeid . '/' . $cmd . ' suivant model de l objet: ' . $nomObjet
            );
            $cmdlogic = new AbeilleCmd();
            // id
            $cmdlogic->setEqLogic_id($elogic->getId());
            $cmdlogic->setEqType('Abeille');
            $cmdlogic->setLogicalId($cmd);
            $cmdlogic->setOrder($cmdValueDefaut["order"]);
            $cmdlogic->setName($cmdValueDefaut["name"]);
            if ($cmdValueDefaut["Type"] == "action") {
                $cmdlogic->setConfiguration('topic', 'Cmd' . $nodeid . '/' . $cmd);
            } else {
                $cmdlogic->setConfiguration('topic', $nodeid . '/' . $cmd);
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
        switch ($this->getType()) {
            case 'action' :
                // request c'est le payload dans la page de configuration
                $request = $this->getConfiguration('request', '1');

                /* ------------------------------ */
                /* En cours de dev by KiwiHC16 pour bind */
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
                /* ------------------------------ */
                // Topic est l arborescence MQTT
                $topic = $this->getConfiguration('topic');

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

// Used for test
// en ligne de comande =>
// "php Abeille.class.php 1" to run the script to create any of the item listed in array L1057
// "php Abeille.class.php 2" to run the script to create a ruche object
// "php Abeille.class.php" to parse the file and verify syntax issues.

if (isset($argv[1])) {
    $debugBEN = $argv[1];
} else {
    $debugBEN = 0;
}
if ($debugBEN != 0) {
    echo "Debut\n";
    $message = new stdClass();


    switch ($debugBEN) {

        case "2":
            $message->topic = "CmdRuche/Ruche/CreateRuche";
            $message->payload = "";
            Abeille::message($message);
            break;

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
            

    }

    echo "\nFin\n";
}
    
    
