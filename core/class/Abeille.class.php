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

    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once(dirname(__FILE__).'/../../resources/AbeilleDaemon/lib/Tools.php');


    class Abeille extends eqLogic
    {

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

        public static function deamon_info()
        {
            $return = array();
            $return['log'] = 'Abeille_update';
            log::add('Abeille', 'debug', '**Daemon info: IN**');
            $return = array();
            $return['state'] = 'nok';
            $return['configuration'] = 'nok';
            $cron = cron::byClassAndFunction('Abeille', 'daemon');
            if (is_object($cron) && $cron->running()) {
                $return['state'] = 'ok';
            }
            //deps ok ?
            $dependancy_info = self::dependancy_info();
            if ($dependancy_info['state'] == 'ok') {
                $return['launchable'] = 'ok';
            } else {
                log::add('Abeille', 'debug', 'daemon_info: Daemon is not launchable ;-(');
                log::add('Abeille', 'warning', 'daemon_info: Daemon is not launchable due to dependancies missing');
                $return['launchable'] = 'nok';
                throw new Exception(__('Dépendances non installées, relancer l\'installation : ', __FILE__));
            }

            //Parameters OK
            $parameters_info = self::getParameters();
            if ( $parameters_info['state'] == 'ok'){
                $return['launchable'] = 'ok';
            }
                else {
                    log::add('Abeille', 'debug', 'daemon_info: Daemon is not launchable ;-(');
                    log::add('Abeille', 'warning', 'daemon_info: Daemon is not launchable due to parameters missing');
                    $return['launchable'] = 'nok';
                    throw new Exception(__('Problème de parametres, vérifier le port USB : '.$parameters_info['serialPort'].', state: '.$parameters_info['state'], __FILE__));
                }

            //check running daemon /!\ if using sudo nbprocess x2
            exec(
                "ps -eo pid,args --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd).php /' | cut -d' '  -f1",
                $output
            );
            log::add('Abeille', 'debug', 'daemon_info: implode output '.implode(" xx ", $output));
            $nbProcess = $output != '' ? sizeof($output) : "0";

            if ($nbProcess < 6) {
                log::add(
                    'Abeille',
                    'info',
                    'daemon_info: found '.($nbProcess / 3).'/3 running, at least one is missing'
                );
                $return['state'] = 'nok';
            }

            if ($nbProcess > 6) {
                log::add(
                    'Abeille',
                    'error',
                    'daemon_info: '.($nbProcess / 3).'/3 running, too many daemon running. Stopping daemons'
                );
                $return['state'] = 'nok';
                self::deamon_stop();
            }
            log::add(
                'Abeille',
                'debug',
                '**Daemon info: OUT**  Daemon launchable: '.$return['launchable'].' Daemon state: '.$return['state']
            );

            return $return;
        }

        public static function deamon_start($_debug = false)
        {
            log::add('Abeille', 'debug', 'daemon_start: IN');
            log::add('Abeille', 'debug', 'Test BEN CONFIG: '.config::byKey('abeilleSerialPort', 'Abeille', 'none'));

            self::deamon_stop();
            //no need as it seems to be on cron
            $deamon_info = self::deamon_info();
            if ($deamon_info['launchable'] != 'ok') {
                throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
            }

            $cron = cron::byClassAndFunction('Abeille', 'daemon');
            if (!is_object($cron)) {
                log::add('Abeille', 'error', 'daemon_start: Tache cron introuvable');
                throw new Exception(__('Tache cron introuvable', __FILE__));
            }
            $cron->run();

            sleep(3);

            $_id = "BEN_Start"; // JE ne sais pas alors je mets n importe quoi....
            $_subject = "CmdRuche/Ruche/CreateRuche";
            $_message = "";
            $_retain = 0;
            // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
            log::add('Abeille', 'debug', 'daemon_start: Envoi du message '.$_message.' vers '.$_subject);
            $publish = new Mosquitto\Client(config::byKey('AbeilleConId', 'Abeille', 'Jeedom').'_pub_'.$_id);
            if (config::byKey('mqttUser', 'Abeille', 'none') != 'none') {
                $publish->setCredentials(config::byKey('mqttUser', 'Abeille'), config::byKey('mqttPass', 'Abeille'));
            }

            $publish->connect(
                config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1'),
                config::byKey('AbeillePort', 'Abeille', '1883'),
                60
            );

            log::add('Abeille', 'debug', 'daemon_start: *****Envoi de la creation de ruche par défaut ********');
            log::add(
                'Abeille',
                'debug',
                'daemon_start: publish subject:'.$_subject.' message: '.$_message.'Qos: '.config::byKey(
                    'mqttQos',
                    'Abeille',
                    '1'
                ).' retain: '.$_retain
            );
            $publish->publish($_subject, $_message, config::byKey('mqttQos', 'Abeille', '1'), $_retain);


            for ($i = 0; $i < 100; $i++) {
                // Loop around to permit the library to do its work
                $publish->loop(1);
            }

            $publish->disconnect();
            unset($publish);

            // Start other daemons
            $nohup = "/usr/bin/nohup";
            $php = "/usr/bin/php";
            $dirDaemon = dirname(__FILE__)."/../../resources/AbeilleDaemon/";

            /**
             * getParameters
             */
            $parameters_info = self::getParameters();

            $daemon1 = "AbeilleSerialRead.php";
            $paramDaemon1 = $parameters_info['serialPort'].' '.time();
            $daemon2 = "AbeilleParser.php";
            $paramDaemon2 = $parameters_info['serialPort'].' '.$parameters_info['AbeilleAddress'].' '.$parameters_info['AbeillePort'].
                ' '.$parameters_info['AbeilleUser'].' '.$parameters_info['AbeillePass'].' '.$parameters_info['AbeilleQos'].' '.time();
            $daemon3 = "AbeilleMQTTCmd.php";
            $paramDaemon3 = $parameters_info['serialPort'].' '.$parameters_info['AbeilleAddress'].' '.$parameters_info['AbeillePort'].
                ' '.$parameters_info['AbeilleUser'].' '.$parameters_info['AbeillePass'].' '.$parameters_info['AbeilleQos'].' '.time();
            $log1 = " > /var/www/html/log/".substr($daemon1, 0, (strrpos($daemon1, ".")));;
            $log2 = " > /var/www/html/log/".substr($daemon2, 0, (strrpos($daemon2, ".")));;
            $log3 = " > /var/www/html/log/".substr($daemon3, 0, (strrpos($daemon3, ".")));;

            $cmd = $nohup." ".$php." ".$dirDaemon.$daemon1." ".$paramDaemon1.$log1;
            log::add('Abeille', 'debug', 'Start daemon SerialRead: '.$cmd);
            exec(system::getCmdSudo().$cmd.' 2>&1 &');
            //exec($cmd.' 2>&1 &');

            $cmd = $nohup." ".$php." ".$dirDaemon.$daemon2." ".$paramDaemon2.$log2;
            log::add('Abeille', 'debug', 'Start daemon Parser: '.$cmd);
            exec(system::getCmdSudo().$cmd.' 2>&1 &');
            //exec($cmd.' 2>&1 &');


            $cmd = $nohup." ".$php." ".$dirDaemon.$daemon3." ".$paramDaemon3.$log3;
            log::add('Abeille', 'debug', 'Start daemon MQTT: '.$cmd);
            exec(system::getCmdSudo().$cmd.' 2>&1 &');
            //exec($cmd.' 2>&1 &');
            $cmd = "";
            log::add('Abeille', 'debug', 'Daemon start: OUT');
        }

        public static function deamon_stop()
        {
            log::add('Abeille', 'debug', 'daemon stop: IN');
            // Stop other daemon
            exec("ps -eo pid,args --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd).php /'", $output);
            foreach ($output as $item => $itemValue) {
                log::add('Abeille', 'debug', 'daemon stop: Killing daemon: '.$item.' '.$itemValue);
                exec(system::getCmdSudo().'kill '.$itemValue.' 2>&1');
            }

            // Stop main daemon
            $cron = cron::byClassAndFunction('Abeille', 'daemon');
            if (!is_object($cron)) {
                log::add('Abeille', 'error', 'daemon stop: Abeille, Tache cron introuvable');
                throw new Exception(__('Tache cron introuvable', __FILE__));
            }
            $cron->halt();
            log::add('Abeille', 'debug', 'daemon stop: OUT');
        }


        public static function dependancy_info()
        {
            $return = array();
            $return['log'] = 'Abeille_dep';
            $return['state'] = 'nok';
            $return['launchable'] = 'nok';
            $return['configuration'] = 'nok';
            $cmd = "dpkg -l | grep mosquitto";
            exec($cmd, $output, $return_var);
            //lib PHP exist
            $libphp = extension_loaded('mosquitto');

          /////get Parameters and check

            foreach ($return as $item => $itemValue) {
                if (!isset($itemValue) || $itemValue == "") {
                    throw new Exception(__($item.' n\'est pas défini . ->'.$itemValue.'<-', __FILE__));
                }
            }

            if ($output[0] != "" && $libphp) {
                //$return['configuration'] = 'ok';
                $return['state'] = 'ok';
            } else {
                log::add(
                    'Abeille',
                    'warning',
                    'Impossible de trouver le package mosquitto et/ou la lib php pour mosquitto. Probleme d installation ? libphp ->'.$libphp.'<-'
                );
                log::add(
                    'Abeille',
                    'debug',
                    'Impossible de trouver le package mosquitto et/ou la lib php pour mosquitto. Probleme d installation ? libphp ->'.$libphp.'<-'
                );

            }

            return $return;
        }

        public static function dependancy_install()
        {
            log::add('Abeille', 'info', 'Installation des dépéndances');
            $resource_path = realpath(dirname(__FILE__).'/../../resources/');
            $cmd = system::getCmdSudo(
                ).' /bin/bash '.$resource_path.'/install.sh '.$resource_path.' > '.log::getPathToLog(
                    'Abeille_dep'
                ).' 2>&1 &';
            log::add('Abeille', 'debug', 'dependancy_install: cmd: '.$cmd);
            passthru($cmd);

            return true;
        }

        public static function daemon()
        {
            log::add(
                'Abeille',
                'debug',
                'Paramètres utilisés, Host : '.config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1').',
            Port : '.config::byKey('AbeillePort', 'Abeille', '1883').',
            AbeilleId : '.config::byKey('AbeilleId', 'Abeille', 'Jeedom').',
            AbeilleConId: '.config::byKey('AbeilleConId', 'Abeille').',
            mqttUser'.config::byKey('mqttUser', 'Abeille').',
            pass'.config::byKey('mqttPass', 'Abeille').',
            serialPort'.config::byKey('abeilleSerialPort', 'Abeille').',
            qos: '.config::byKey('mqttQos', 'Abeille')
            );

            $client = new Mosquitto\Client(config::byKey('AbeilleConId', 'Abeille', 'Jeedom'));
            $client->onConnect('Abeille::connect');
            $client->onDisconnect('Abeille::disconnect');
            $client->onSubscribe('Abeille::subscribe');
            $client->onMessage('Abeille::message');
            $client->onLog('Abeille::logmq');
            $client->setWill('/jeedom', "Client died :-(", 1, 0);

            try {
                if (config::byKey('mqttUser', 'Abeille', 'none') != 'none') {
                    $client->setCredentials(
                        config::byKey('mqttUser', 'Abeille'),
                        config::byKey('mqttPass', 'Abeille')
                    );
                }
                $client->connect(
                    config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1'),
                    config::byKey('AbeillePort', 'Abeille', '1883'),
                    60
                );
                $client->subscribe(
                    config::byKey('mqttTopic', 'Abeille', '#'),
                    config::byKey('mqttQos', 'Abeille', 1)

                ); // !auto: Subscribe to root topic
                log::add('Abeille', 'debug', 'Subscribe to topic '.config::byKey('mqttTopic', 'Abeille', '#'));
                //$client->loopForever();
                while (true) {
                    $client->loop();
                    //usleep(100);
                }

                $client->disconnect();
                unset($client);

            } catch (Exception $e) {
                log::add('Abeille', 'error', $e->getMessage());
            }
        }


        public static function getParameters()
        {
            $return = array();
            $return['state']='nok';

            //Commented value for non compulsory fields
            $return['AbeilleAddress'] = config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1');
            $return['AbeillePort'] = config::byKey('AbeillePort', 'Abeille', '1883');
            $return['AbeilleConId'] = config::byKey('AbeilleConId', 'Abeille', 'jeedom');
            $return['AbeilleUser'] = config::byKey('mqttUser', 'Abeille');
            $return['AbeillePass'] = config::byKey('mqttPass', 'Abeille');
            $return['AbeilleTopic'] = config::byKey('mqttTopic', 'Abeille', '#');
            $return['serialPort'] = config::byKey('AbeilleSerialPort', 'Abeille');
            $return['AbeilleQos'] = config::byKey('mqttQos', 'Abeille', '0');
            $return['AbeilleId'] = config::byKey('AbeilleId', 'Abeille', '1');
            $serialPort = config::byKey('AbeilleSerialPort', 'Abeille');

            log::add('Abeille', 'debug', 'serialPort value: ->'.$return['serialPort'].'<-');
            if ($return['serialPort'] != 'none') {
                $return['serialPort'] = jeedom::getUsbMapping($return['serialPort']);
                if (@!file_exists($return['serialPort'])) {
                    log::add('Abeille', 'debug', 'getParameters: serialPort n\'est pas défini. ->'.$return['serialPort'].'<-');
                    $return['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
                    throw new Exception(__('Le port n\'est pas configuré: '.$return['serialPort'], __FILE__));
                } else {
                    exec(system::getCmdSudo().'chmod 777 '.$return['serialPort'].' > /dev/null 2>&1');
                    $return['state']='ok';
                }
            } else {
                //if serialPort= none then nothing to check
                $return['state']='ok';
            }
            return $return;
        }

        public static function connect($r, $message)
        {
            log::add('Abeille', 'info', 'Connexion à Mosquitto avec code '.$r.' '.$message);
            config::save('state', '1', 'Abeille');
        }

        public static function disconnect($r)
        {
            log::add('Abeille', 'debug', 'Déconnexion de Mosquitto avec code '.$r);
            config::save('state', '0', 'Abeille');
        }

        public static function subscribe()
        {
            log::add('Abeille', 'debug', 'Subscribe to topics');
        }

        public static function logmq($code, $str)
        {
            if (strpos($str, 'PINGREQ') === false && strpos($str, 'PINGRESP') === false) {
                log::add('Abeille', 'debug', $code.' : '.$str);
            }
        }


        public static function message($message)
        {

            if ($GLOBALS['debugBEN']) {
                echo "Function message.\n";
            }
            if ($GLOBALS['debugBEN']) {
                print_r($message);
                echo "\n";
            }
            log::add('Abeille', 'debug', '--- process a new message -----------------------');
            log::add('Abeille', 'debug', 'Message ->'.$message->payload.'<- sur '.$message->topic);

            /*----------------------------------------------------------------------------------------------------------------------------------------------*/
            // Analyse du message recu et definition des variables en fonction de ce que l on trouve dans le message
            // $nodeid[/] / $cmdId / $value

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
            //$AbeilleObjetDefinition = Tools::getJsonConfigFiles($AbeilleJsonFileObjetDefinition);
            $AbeilleObjetDefinition = Tools::getJsonConfigFiles("AbeilleObjetDefinition.json");

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

            // demande de creation de ruche au cas ou elle n'est pas deja crée....
            // La ruche est aussi un objet Abeille
            if ($message->topic == "CmdRuche/Ruche/CreateRuche") {
                self::createRuche($message);

                return;
            }

            /*if ($message->topic == "CmdRuche/Ruche/CreateRuche") {
                self::createRuche($message);
                return ;
            }*/

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // On ne prend en compte que les message Abeille/#/#
            if (!preg_match("(Abeille|Ruche)", $Filter)) {
                log::add(
                    'Abeille',
                    'debug',
                    'message: this is not a '.$Filter.' message: topic: '.$message->topic.' message: '.$message->payload
                );

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

            // Est ce que cet equipement existe deja ? Sinon creation quand je recois son nom

            // Cherche l objet
            $elogic = self::byLogicalId($nodeid, 'Abeille');
            $objetConnu = 0;

            // Je viens de revoir son nom donc je créé l objet.
            if (!is_object($elogic) && ($cmdId == "0000-0005") && (config::byKey(
                        'creationObjectMode',
                        'Abeille',
                        'Automatique'
                    ) != "Manuel")) {

                log::add('Abeille', 'info', 'Recherche objet: '.$value.' dans les objets connus');
                if (array_key_exists($value, $AbeilleObjetDefinition)) {
                    $objetConnu = 1;
                    log::add('Abeille', 'info', 'objet: '.$value.' peut etre cree car je connais ce type d objet.');
                } else {
                    log::add(
                        'Abeille',
                        'info',
                        'objet: '.$value.' ne peut pas etre cree completement car je ne connais pas ce type d objet.'
                    );
                }

                /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
                // Creation de l objet Abeille (hors ruche)
                log::add('Abeille', 'info', 'objet: '.$value.' creation par model');
                $elogic = new Abeille();
                //id
                if ($objetConnu) {
                    $name = "Abeille-".$addr;
                } else {
                    $name = "Abeille-".$addr."-Type d objet inconnu (!JSON)";
                }
                $elogic->setName($name);
                $elogic->setLogicalId($nodeid);
                $elogic->setObject_id(config::byKey('abeilleId', 'Abeille', '1'));
                $elogic->setEqType_name('Abeille');

                $objetDefSpecific = $AbeilleObjetDefinition[$value];
                $objetConfiguration = $objetDefSpecific["configuration"];
                $elogic->setConfiguration('topic', $nodeid);
                $elogic->setConfiguration('type', $type);
                $elogic->setConfiguration('icone', $objetConfiguration["icone"]);

                $elogic->setIsVisible("1");
                // eqReal_id
                $elogic->setIsEnable("1");
                // status
                // timeout
                $elogic->setCategory(
                    array_keys($AbeilleObjetDefinition[$value]["Categorie"])[0],
                    $AbeilleObjetDefinition[$value]["Categorie"][array_keys(
                        $AbeilleObjetDefinition[$value]["Categorie"]
                    )[0]]
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
                    print_r($AbeilleObjetDefinition[$value]['Commandes']);
                }

                foreach ($AbeilleObjetDefinition[$value]['Commandes'] as $cmd => $cmdValueDefaut) {
                    log::add(
                        'Abeille',
                        'info',
                        'Creation de la commande: '.$nodeid.'/'.$cmd.' suivant model de l objet pour l objet: '.$name
                    );
                    $cmdlogic = new AbeilleCmd();
                    // id
                    $cmdlogic->setEqLogic_id($elogic->getId());
                    $cmdlogic->setEqType('Abeille');
                    $cmdlogic->setLogicalId($cmd);
                    echo "Order: ".$cmdValueDefaut["order"]."\n";
                    $cmdlogic->setOrder($cmdValueDefaut["order"]);
                    $cmdlogic->setName($cmdValueDefaut["name"]);

                    if ($cmdValueDefaut["Type"] == "info") {
                        $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd);
                    }

                    // La boucle est pour info et pour action
                    foreach ($cmdValueDefaut["configuration"] as $confKey => $confValue) {
                        // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                        $cmdlogic->setConfiguration($confKey, str_replace('#addr#', $addr, $confValue));
                    }
                    if ($cmdValueDefaut["Type"] == "action") {
                        $cmdlogic->setConfiguration('retain', '0');
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
                    foreach ($cmdValueDefaut["display"] as $confKey => $confValue) {
                        // Pour certaine Action on doit remplacer le #addr# par la vrai valeur
                        $cmdlogic->setDisplay($confKey, $confValue);
                    }

                    // isVisible
                    // value
                    if (isset($cmdValueDefaut["value"])) {
                        $cmdlogic->setConfiguration('value', $cmdValueDefaut["value"]);
                    }
                    // html
                    // alert

                    $cmdlogic->save();
                    $elogic->checkAndUpdateCmd($cmdId, $cmdValueDefaut["value"]);
                }

                // On defini le nom de l objet
                $elogic->checkAndUpdateCmd($cmdId, $value);
            } else {
                // Si je recois une commande IEEE pour un objet qui n'existe pas je vais créer un objet pour visualiser cet inconnu
                if (!is_object($elogic) && ($cmdId == "IEEE-Addr") && (config::byKey(
                            'creationObjectMode',
                            'Abeille',
                            'Automatique'
                        ) == "Semi Automatique")) {
                    // Creation de l objet Abeille (hors ruche)
                    log::add('Abeille', 'info', 'objet: '.$value.' creation sans model');
                    $elogic = new Abeille();
                    //id
                    if ($objetConnu) {
                        $name = "Abeille-".$addr;
                    } else {
                        $name = "Abeille-".$addr."-Type d objet inconnu (IEEE)";
                    }
                    $elogic->setName($name);
                    $elogic->setLogicalId($nodeid);
                    $elogic->setObject_id(config::byKey('AbeilleId', 'Abeille', '1'));
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
                            log::add('Abeille', 'debug', 'L objet n existe pas: '.$nodeid);
                            $_id = "BEN"; // JE ne sais pas alors je mets n importe quoi....
                            $_subject = "CmdAbeille/".$addr."/Annonce";
                            $_message = "";
                            $_retain = 0;
                            log::add('Abeille', 'debug', 'Envoi du message '.$_message.' vers '.$_subject);
                            $publish = new Mosquitto\Client(
                                config::byKey('AbeilleId', 'Abeille', 'Jeedom').'_pub_'.$_id
                            );
                            if (config::byKey('mqttUser', 'Abeille', 'none') != 'none') {
                                $publish->setCredentials(
                                    config::byKey('mqttUser', 'Abeille'),
                                    config::byKey('mqttPass', 'Abeille')
                                );
                            }
                            $publish->connect(
                                config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1'),
                                config::byKey('AbeillePort', 'Abeille', '1883'),
                                60
                            );
                            $publish->publish(
                                $_subject,
                                $_message,
                                config::byKey('AbeilleQos', 'Abeille', '1'),
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
                            // Créons les commandes inconnues sur la base des commandes qu on recoit.
                            log::add('Abeille', 'debug', 'L objet: '.$nodeid.' existe mais pas la commande: '.$cmdId);
                            if (config::byKey('creationObjectMode', 'Abeille', 'Automatique') == "Semi Automatique") {
                                // Crée la commande avec le peu d info que l on a
                                log::add('Abeille', 'info', 'Creation par defaut de la commande: '.$nodeid.'/'.$cmdId);
                                $cmdlogic = new AbeilleCmd();
                                // id
                                $cmdlogic->setEqLogic_id($elogic->getId());
                                $cmdlogic->setEqType('Abeille');
                                $cmdlogic->setLogicalId($cmdId);
                                // $cmdlogic->setOrder('0');
                                $cmdlogic->setName('Cmd de type inconnue - '.$cmdId);
                                $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmdId);

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
                            $elogic->checkAndUpdateCmd($cmdId, $value);
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


        public static function publishMosquitto($_id, $_subject, $_message, $_retain)
        {
            log::add('Abeille', 'debug', 'Envoi du message '.$_message.' vers '.$_subject);
            $publish = new Mosquitto\Client(config::byKey('AbeilleId', 'Abeille', 'Jeedom').'_pub_'.$_id);
            if (config::byKey('mqttUser', 'Abeille', 'none') != 'none') {
                $publish->setCredentials(
                    config::byKey('mqttUser', 'Abeille'),
                    config::byKey('mqttPass', 'Abeille')
                );
            }
            $publish->connect(
                config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1'),
                config::byKey('AbeillePort', 'Abeille', '1883'),
                60
            );
            $publish->publish($_subject, $_message, config::byKey('AbeilleQos', 'Abeille', '1'), $_retain);
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

            if (is_object($elogic)) {
                log::add('Abeille', 'debug', 'message: createRuche: objet: '.$elogic->getLogicalId().' existe deja');

                // La ruche existe deja so return
                return;
            }
            // Creation de la ruche
            //log::add('Abeille', 'info', 'objet: '.$elogic->getLogicalId().' creation par model');

            $cmdId = end($topicArray);
            $key = count($topicArray) - 1;
            unset($topicArray[$key]);
            $addr = end($topicArray);
            // nodeid est le topic sans le dernier champ
            $nodeid = implode($topicArray, '/');

            $elogic = new Abeille();
            //id
            $elogic->setName("Ruche");
            $elogic->setLogicalId("Abeille/Ruche");
            $elogic->setObject_id(config::byKey('AbeilleId', 'Abeille', '1'));
            $elogic->setEqType_name('Abeille');
            $elogic->setConfiguration('topic', "Abeille/Ruche");
            $elogic->setConfiguration('type', 'topic');
            $elogic->setIsVisible("1");
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

            /*$AbeilleObjetDefinition= Tools::getJSonConfigFiles($AbeilleJsonFileObjetDefinition);
            $rucheCommandList = Tools::getJSonConfigFiles($rucheJsonFileCommandList);*/
            $AbeilleObjetDefinition = Tools::getJSonConfigFiles('AbeilleObjetDefinition.json');
            $rucheCommandList = Tools::getJSonConfigFiles('rucheCommandList.json');

            $i = 0;

            // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
            foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
                $rucheCommandList[$objetId] = array(
                    "name" => $objetId,
                    "order" => $i++,
                    "isVisible" => "0",
                    "isHistorized" => "0",
                    "Type" => "action",
                    "subType" => "other",
                    "configuration" => array("topic" => "Abeille/".$objetId."/0000-0005", "request" => $objetId),
                );
            }

            foreach ($rucheCommandList as $cmd => $cmdValueDefaut) {
                $nomObjet = "Ruche";
                log::add(
                    'Abeille',
                    'info',
                    'Creation de la command: '.$nodeid.'/'.$cmd.' suivant model de l objet: '.$nomObjet
                );
                $cmdlogic = new AbeilleCmd();
                // id
                $cmdlogic->setEqLogic_id($elogic->getId());
                $cmdlogic->setEqType('Abeille');
                $cmdlogic->setLogicalId($cmd);
                $cmdlogic->setOrder($cmdValueDefaut["order"]);
                $cmdlogic->setName($cmdValueDefaut["name"]);
                if ($cmdValueDefaut["Type"] == "action") {
                    $cmdlogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd);
                } else {
                    $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd);
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
                    $request = $this->getConfiguration('request', '1');
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
    // "php Abeille.class.php 1" to run the script to create an object
    // "php Abeille.class.php" to parse the file and verify syntax issues.

    if (isset($argv[1])) {
        $debugBEN = $argv[1];
    } else {
        $debugBEN = 0;
    }
    if ($debugBEN) {
        echo "Debut\n";
        $message = new stdClass();

        // $message->topic="CmdRuche/Ruche/CreateRuche";
        // $message->payload="";

        $message->topic = "Abeille/lumi.plug/0000-0005";
        $message->payload = "lumi.plug";

        // print_r( $message->topic );

        Abeille::message($message);
        echo "Fin\n";
    }
    
    
