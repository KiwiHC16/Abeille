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
    
    class Abeille extends eqLogic {
        public static function health() {
            $return = array();
            $socket = socket_create(AF_INET, SOCK_STREAM, 0);
            $server = socket_connect ($socket , config::byKey('AbeilleAdress', 'Abeille', '127.0.0.1'), config::byKey('AbeillePort', 'Abeille', '1883'));
            $return[] = array(
                              'test' => __('Mosquitto', __FILE__),
                              'result' => ($server) ? __('OK', __FILE__) : __('NOK', __FILE__),
                              'advice' => ($server) ? '' : __('Indique si Mosquitto est disponible', __FILE__),
                              'state' => $server,
                              );
            return $return;
        }
        
        public static function deamon_info() {
            $return = array();
            $return['log'] = '';
            $return['state'] = 'nok';
            $cron = cron::byClassAndFunction('Abeille', 'daemon');
            if (is_object($cron) && $cron->running()) {
                $return['state'] = 'ok';
            }
            $dependancy_info = self::dependancy_info();
            if ($dependancy_info['state'] == 'ok') {
                $return['launchable'] = 'ok';
            }
            return $return;
        }
        
        public static function deamon_start($_debug = false) {
            self::deamon_stop();
            $deamon_info = self::deamon_info();
            if ($deamon_info['launchable'] != 'ok') {
                throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
            }
            $cron = cron::byClassAndFunction('Abeille', 'daemon');
            if (!is_object($cron)) {
                throw new Exception(__('Tache cron introuvable', __FILE__));
            }
            $cron->run();
            
            sleep(3);
            
            log::add('Abeille', 'debug', 'L objet n existe pas: '.$nodeid );
            $_id = "BEN_Start"; // JE ne sais pas alors je mets n importe quoi....
            $_subject = "CmdRuche/Ruche/CreateRuche";
            $_message = "";
            $_retain = 0;
            // Send a message to Abeille to ask for Abaille Object creation: inclusion, ...
            log::add('Abeille', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
            $publish = new Mosquitto\Client(config::byKey('AbeilleId', 'Abeille', 'Jeedom') . '_pub_' . $_id);
            if (config::byKey('AbeilleUser', 'Abeille', 'none') != 'none') {
                $publish->setCredentials(config::byKey('AbeilleUser', 'Abeille'), config::byKey('AbeillePass', 'Abeille'));
            }
            $publish->connect(config::byKey('AbeilleAdress', 'Abeille', '127.0.0.1'), config::byKey('AbeillePort', 'Abeille', '1883'), 60);
            $publish->publish($_subject, $_message, config::byKey('AbeilleQos', 'Abeille', '1'), $_retain);
            for ($i = 0; $i < 100; $i++) {
                // Loop around to permit the library to do its work
                $publish->loop(1);
            }
            $publish->disconnect();
            unset($publish);
            
            // Start other daemons
            $nohup = "/usr/bin/nohup";
            $php = "/usr/bin/php";
            $dirDaemon = "/var/www/html/plugins/Abeille/resources/AbeilleDaemon/";
            $daemon1 = "AbeilleSerialRead.php";
            $daemon2 = "AbeilleParser.php";
            $daemon3 = "AbeilleMQTTCmd.php";
            $log1 = " > /var/www/html/log/".$daemon1.".log";
            $log2 = " > /var/www/html/log/".$daemon2.".log";
            $log3 = " > /var/www/html/log/".$daemon3.".log";
            $end = " &";
            
            $cmd = $nohup." ".$php." ".$dirDaemon.$daemon1.$log1.$end;
            log::add('Abeille', 'debug', 'Start daemon: '.$cmd);
            exec($cmd);
            
            $cmd = $nohup." ".$php." ".$dirDaemon.$daemon2.$log2.$end;
            log::add('Abeille', 'debug', 'Start daemon: '.$cmd);
            exec($cmd);
            
            $cmd = $nohup." ".$php." ".$dirDaemon.$daemon3.$log3.$end;
            log::add('Abeille', 'debug', 'Start daemon: '.$cmd);
            exec($cmd);

            
            
        }
        
        public static function deamon_stop() {
            // Stop other daemon
          
            $kill = "kill `ps -eo pid,args --cols=10000 | awk '/AbeilleSerialRead.php/   && $1 != PROCINFO[\"pid\"] { print $1 }'`"; exec($kill);
            $kill = "kill `ps -eo pid,args --cols=10000 | awk '/AbeilleParser.php/       && $1 != PROCINFO[\"pid\"] { print $1 }'`"; exec($kill);
            $kill = "kill `ps -eo pid,args --cols=10000 | awk '/AbeilleMQTTCmd.php/      && $1 != PROCINFO[\"pid\"] { print $1 }'`"; exec($kill);
            
            // Stop main daemon
            $cron = cron::byClassAndFunction('Abeille', 'daemon');
            if (!is_object($cron)) {
                throw new Exception(__('Tache cron introuvable', __FILE__));
            }
            $cron->halt();
        }
        
        public static function dependancy_info() {
            $return = array();
            $return['log'] = 'Abeille_dep';
            $return['state'] = 'nok';
            $cmd = "dpkg -l | grep mosquitto";
            exec($cmd, $output, $return_var);
            //lib PHP exist
            $libphp = extension_loaded('mosquitto');
            if ($output[0] != "" && $libphp) {
                $return['state'] = 'ok';
            }
            return $return;
        }
        
        public static function dependancy_install() {
            log::add('Abeille','info','Installation des dépéndances');
            $resource_path = realpath(dirname(__FILE__) . '/../../resources');
            passthru('sudo /bin/bash ' . $resource_path . '/install.sh ' . $resource_path . ' > ' . log::getPathToLog('Abeille_dep') . ' 2>&1 &');
            return true;
        }
        
        public static function daemon() {
            log::add('Abeille', 'info', 'Paramètres utilisés, Host : ' . config::byKey('AbeilleAdress', 'Abeille', '127.0.0.1') . ', Port : ' . config::byKey('AbeillePort', 'Abeille', '1883') . ', ID : ' . config::byKey('AbeilleId', 'Abeille', 'Jeedom'));
            $client = new Mosquitto\Client(config::byKey('AbeilleId', 'Abeille', 'Jeedom'));
            $client->onConnect('Abeille::connect');
            $client->onDisconnect('Abeille::disconnect');
            $client->onSubscribe('Abeille::subscribe');
            $client->onMessage('Abeille::message');
            $client->onLog('Abeille::logmq');
            $client->setWill('/jeedom', "Client died :-(", 1, 0);
            
            try {
                if (config::byKey('AbeilleUser', 'Abeille', 'none') != 'none') {
                    $client->setCredentials(config::byKey('AbeilleUser', 'Abeille'), config::byKey('AbeillePass', 'Abeille'));
                }
                $client->connect(config::byKey('AbeilleAdress', 'Abeille', '127.0.0.1'), config::byKey('AbeillePort', 'Abeille', '1883'), 60);
                $client->subscribe(config::byKey('AbeilleTopic', 'Abeille', '#'), config::byKey('AbeilleQos', 'Abeille', 1)); // !auto: Subscribe to root topic
                log::add('Abeille', 'debug', 'Subscribe to topic ' . config::byKey('AbeilleTopic', 'Abeille', '#'));
                //$client->loopForever();
                while (true) { $client->loop(); }
            }
            catch (Exception $e){
                log::add('Abeille', 'error', $e->getMessage());
            }
        }
        
        public static function connect( $r, $message ) {
            log::add('Abeille', 'info', 'Connexion à Mosquitto avec code ' . $r . ' ' . $message);
            config::save('status', '1',  'Abeille');
        }
        
        public static function disconnect( $r ) {
            log::add('Abeille', 'debug', 'Déconnexion de Mosquitto avec code ' . $r);
            config::save('status', '0',  'Abeille');
        }
        
        public static function subscribe( ) {
            log::add('Abeille', 'debug', 'Subscribe to topics');
        }
        
        public static function logmq( $code, $str ) {
            if (strpos($str,'PINGREQ') === false && strpos($str,'PINGRESP') === false) {
                log::add('Abeille', 'debug', $code . ' : ' . $str);
            }
        }
        
        public static function message( $message )
        {
            log::add('Abeille', 'debug', '');
            log::add('Abeille', 'debug', '--- process a new message -----------------------');
            log::add('Abeille', 'debug', 'Message ' . $message->payload . ' sur ' . $message->topic);
            // Analyse du message recu et definition des variables en fonction de ce que l on trouve dans le message
            if (is_string($message->payload) && is_array(json_decode($message->payload, true)) && (json_last_error() == JSON_ERROR_NONE))
            {
                //json message
                $nodeid = $message->topic;
                $value = $message->payload;
                // type = json
                $type = 'json';
                log::add('Abeille', 'info', 'Message json : ' . $value . ' pour information sur : ' . $nodeid);
            }
            else
            {
                // pas un json message
                // $nodeid[/] / $cmdId / $value
                
                $topicArray = explode("/", $message->topic);
                $Filter = $topicArray[0];
                // cmdId est le dernier champ du topic
                $cmdId = end($topicArray);
                $key = count($topicArray) - 1;
                unset($topicArray[$key]);
                $addr = end($topicArray);
                // nodeid est le topic sans le dernier champ
                $nodeid = implode($topicArray,'/');
                $value = $message->payload;
                // type = topic car pas json
                $type = 'topic';
                // log::add('Abeille', 'info', 'Message texte : ' . $value . ' pour information : ' . $cmdId . ' sur : ' . $nodeid);
            }

            
            // La ruche est aussi un abjet Abeille
            if ( $message->topic == "CmdRuche/Ruche/CreateRuche" )
                {
                    $elogic = self::byLogicalId("Ruche", 'Abeille');
                    if (is_object($elogic) )
                    {
                    // La ruche existe deja so return
                    return;
                    }
                    // Creation de la ruche
                    log::add('Abeille', 'info', 'objet: '.$value.' creation par defaut');
                    $elogic = new Abeille();
                    //id
                    $elogic->setName("Ruche");
                    $elogic->setLogicalId("Ruche");
                    $elogic->setObject_id('1');
                    $elogic->setEqType_name('Abeille');
                    $elogic->setConfiguration('topic', "Ruche"); $elogic->setConfiguration('type', 'topic');
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
                    
                    
                    $rucheCommandList = array(
                                              
                                                            "Version"        =>array( "name"=>"Version",       "order"=>0, "isHistorized"=>"0", "Type"=>"action",   "subType"=>"other",    "configuration"=>array("topic"=>"CmdAbeille/Ruche/getVersion","request"=>"Version") ),
                                                            "Start Network"  =>array( "name"=>"Start Network", "order"=>1, "isHistorized"=>"0", "Type"=>"action",   "subType"=>"other",    "configuration"=>array("topic"=>"CmdAbeille/Ruche/startNetwork","request"=>"StartNetwork") ),
                                                            "Inclusion"      =>array( "name"=>"Inclusion",     "order"=>2, "isHistorized"=>"0", "Type"=>"action",   "subType"=>"other",    "configuration"=>array("topic"=>"CmdAbeille/Ruche/SetPermit","request"=>"Inclusion") ),
                                                            "Reset"          =>array( "name"=>"Reset",         "order"=>3, "isHistorized"=>"0", "Type"=>"action",   "subType"=>"other",    "configuration"=>array("topic"=>"CmdAbeille/Ruche/reset","request"=>"reset") ),
                                                            "addGroup"       =>array( "name"=>"Add Group",     "order"=>4, "isHistorized"=>"0", "Type"=>"action",   "subType"=>"message",  "configuration"=>array("topic"=>"CmdAbeille/Ruche/addGroup","request"=>"address=#title#&groupAddress=#message#") ),
                                                            "removeGroup"    =>array( "name"=>"Remove Group",  "order"=>5, "isHistorized"=>"0", "Type"=>"action",   "subType"=>"message",  "configuration"=>array("topic"=>"CmdAbeille/Ruche/removeGroup","request"=>"address=#title#&groupAddress=#message#") ),
                                                            // getEtat  clusterId=0006&attributeId=0000
                                                            // getLevel clusterId=0008&attributeId=0000
                                                            // getManufacturerName  clusterId=0000&attributeId=0004
                                                            // getModelIdentifier   clusterId=0000&attributeId=0005
                                                            // getSWBuild   clusterId=0000&attributeId=4000
                                              );
                    
                    foreach ( $rucheCommandList as $cmd => $cmdValueDefaut )
                    {
                        $nomObjet = "Ruche";
                        log::add('Abeille', 'info', 'Creation par defaut de la command: '.$nodeid.'/'.$cmd.' suivant model de l objet: '.$nomObjet);
                        $cmdlogic = new AbeilleCmd();
                        // id
                        $cmdlogic->setEqLogic_id($elogic->getId());
                        $cmdlogic->setEqType('Abeille');
                        $cmdlogic->setLogicalId($cmd);
                        $cmdlogic->setOrder($cmdValueDefaut["order"]);
                        $cmdlogic->setName( $cmdValueDefaut["name"] );
                        if ( $cmdValueDefaut["Type"]=="action" ) { $cmdlogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd); } else { $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd); }
                        if ( $cmdValueDefaut["Type"]=="action" ) { $cmdlogic->setConfiguration('retain','0'); }
                        foreach ( $cmdValueDefaut["configuration"] as $confKey => $confValue )
                        {
                            $cmdlogic->setConfiguration($confKey,$confValue);
                        }
                        // template
                        $cmdlogic->setTemplate('dashboard',$cmdValueDefaut["template"]); $cmdlogic->setTemplate('mobile',$cmdValueDefaut["template"]);
                        $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                        $cmdlogic->setType($cmdValueDefaut["Type"]);
                        $cmdlogic->setSubType($cmdValueDefaut["subType"]);
                        // unite
                        $cmdlogic->setDisplay('invertBinary','0');
                        // isVisible
                        // value
                        // html
                        // alert
                        
                        $cmdlogic->save();
                        $elogic->checkAndUpdateCmd($cmdId,$cmdValueDefaut["value"] );
                    }
                    
                }
            
            // On ne prend en compte que les messahe Abeille/#/#
            if ( $Filter!="Abeille" )  { return; }
            
            // Est ce que cet equipement existe deja ? Sinon creation que si je connais son nom
            $elogic = self::byLogicalId($nodeid, 'Abeille');
            if (!is_object($elogic) && ($cmdId=="0000-0005") )
            {
                $objetDefinition = array(
                                        "lumi.sensor_magnet.aq" => array("security"=>"1"),
                                         "lumi.weathe"=> array("heating"=>"1"),
                                         "lumi.sensor_h"=> array("heating"=>"1"),
                                         "lumi.sensor_switch.aq" => array("automatism"=>"1"),
                                         "lumi.plug" => array("automatism"=>"1"),
                                         "TRADFRI bulb E27 opal 1000lm" => array("light"=>"1"),
                );
                log::add('Abeille', 'info', 'objet: '.$value.' creation par defaut');
                $elogic = new Abeille();
                //id
                $elogic->setName($nodeid);
                $elogic->setLogicalId($nodeid);
                $elogic->setObject_id('1');
                $elogic->setEqType_name('Abeille');
                $elogic->setConfiguration('topic', $nodeid); $elogic->setConfiguration('type', $type);
                $elogic->setIsVisible("1");
                // eqReal_id
                $elogic->setIsEnable("1");
                // status
                // timeout
                $elogic->setCategory(array_keys($objetDefinition[$value])[0],$objetDefinition[$value][array_keys($objetDefinition[$value])[0]]);
                // display
                // order
                // comment
                
                //log::add('Abeille', 'info', 'Saving device ' . $nodeid);
                //$elogic->save();
                $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
                $elogic->save();
                
                /*
                 if ($type == 'topic') {
                 $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
                 if (!is_object($cmdlogic)) {
                 */
                
                // Definition des cmd pour les objets, devra etre dans un fichier specifique a therme
                // objetDefinition        nom objet                       cmdList/cmd          cmdValueDefaut
                $objetCmdDefinition = array(
                                         "lumi.sensor_magnet.aq" => array(
                                                                          "0000-0005"       =>array( "name"=>"nom",         "order"=>0, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>"" ),
                                                                          "0006-0000"       =>array( "name"=>"etat",        "order"=>1, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"binary",  "invertBinary"=>"1", "template"=>"door"),
                                                                          "Time-Time"       =>array( "name"=>"Last",        "order"=>2, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                          "Time-TimeStamp"  =>array( "name"=>"Last Stamp",  "order"=>3, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                          ),
                                            
                                            "lumi.weathe" => array(
                                                                    "0000-0005"       =>array( "name"=>"nom",           "order"=>0, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>"" ),
                                                                    "0402-0000"       =>array( "name"=>"Temperature",   "order"=>1, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"tempIMG", "configuration"=>array("calculValueOffset"=>"#value#/100", "historizeRound"=>"1") ),
                                                                    "0405-0000"       =>array( "name"=>"Humidite",      "order"=>2, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"hydro3IMG", "configuration"=>array("calculValueOffset"=>"#value#/100", "historizeRound"=>"0") ),
                                                                    "0403-0000"       =>array( "name"=>"Pression1",     "order"=>3, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge" ),
                                                                    "0403-0010"       =>array( "name"=>"Pression",      "order"=>4, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"barometre", "configuration"=>array("calculValueOffset"=>"#value#/100", "historizeRound"=>"1") ),
                                                                    "Batterie-Volt"   =>array( "name"=>"Batterie",      "order"=>5, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"vuMeter", "configuration"=>array("minValue"=>"0","maxValue"=>"3.3") ),
                                                                    "0000-ff01"       =>array( "name"=>"Specific",      "order"=>6, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>"" ),
                                                                    "Time-Time"       =>array( "name"=>"Last",          "order"=>7, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                    "Time-TimeStamp"  =>array( "name"=>"Last Stamp",    "order"=>8, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                    ),
                                            
                                            "lumi.sensor_h" => array(
                                                                     "0000-0005"       =>array( "name"=>"nom",           "order"=>0, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>"" ),
                                                                     "0402-0000"       =>array( "name"=>" Temperature",  "order"=>1, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"tempIMG", "configuration"=>array("calculValueOffset"=>"#value#/100", "historizeRound"=>"1") ),
                                                                     "0405-0000"       =>array( "name"=>" Humidite",     "order"=>2, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"hydro3IMG", "configuration"=>array("calculValueOffset"=>"#value#/100", "historizeRound"=>"0") ),
                                                                     "Batterie-Volt"   =>array( "name"=>" Batterie",     "order"=>3, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"vuMeter", "configuration"=>array("minValue"=>"0","maxValue"=>"3.3") ),
                                                                     "0000-ff01"       =>array( "name"=>" Specific",     "order"=>4, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>"" ),
                                                                     "Time-Time"       =>array( "name"=>"Last",          "order"=>5, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                     "Time-TimeStamp"  =>array( "name"=>"Last Stamp",    "order"=>6, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                   ),
                                            
                                         "lumi.sensor_switch.aq" => array(
                                                                          "0000-0005"       =>array( "name"=>"nom",                    "order"=>0, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string", "invertBinary"=>"0", "template"=>""),
                                                                          "0006-0000"       =>array( "name"=>"etat",                   "order"=>1, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"binary", "invertBinary"=>"0", "template"=>"", "configuration"=>array("returnStateValue"=>"0","returnStateTime"=>"1") ),
                                                                          "0006-8000"       =>array( "name"=>"multi",                  "order"=>2, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                          "Time-Time"       =>array( "name"=>"Time-Time",              "order"=>3, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string", "invertBinary"=>"0", "template"=>""),
                                                                          "Time-TimeStamp"  =>array( "name"=>"Time-TimeStamp",         "order"=>4, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                          ),
                                         "lumi.plug" => array(
                                                                      "0000-0005"           =>array( "name"=>"nom",                 "order"=>0, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                      "0006-0000"           =>array( "name"=>"etat",                "order"=>1, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"binary",  "invertBinary"=>"0", "template"=>"prise"),
                                                                      "tbd---puissance--"   =>array( "name"=>"Puissance",           "order"=>2, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"", "configuration"=>array("minValue"=>"0","maxValue"=>"2500","historizeRound"=>"0") ),
                                                                      "tbd---conso--"       =>array( "name"=>"Conso",               "order"=>3, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge", "configuration"=>array("historizeRound"=>"3") ),
                                                                      "Time-Time"           =>array( "name"=>"Time-Time",           "order"=>4, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                      "Time-TimeStamp"      =>array( "name"=>"Time-TimeStamp",      "order"=>5, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                      "On"                  =>array( "name"=>"On",                  "order"=>6, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"other",   "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/OnOff","request"=>"On") ),
                                                                      "Off"                 =>array( "name"=>"Off",                 "order"=>7, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"other",   "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/OnOff","request"=>"Off") ),
                                                                      "Toggle"              =>array( "name"=>"Toggle",              "order"=>8, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"other",   "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/OnOff","request"=>"Toggle") ),
                                                              // getEtat  clusterId=0006&attributeId=0000
                                                              // getManufacturerName  clusterId=0000&attributeId=0004
                                                              // getModelIdentifier   clusterId=0000&attributeId=0005
                                                                      ),
                                 
                                           "TRADFRI bulb E27 opal 1000lm" => array(
                                                                                "0000-0005"       =>array( "name"=>"nom",           "order"=>0, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                                "0000-0004"       =>array( "name"=>"societe",       "order"=>1, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                                "0000-4000"       =>array( "name"=>"SW",            "order"=>2, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                                "0006-0000"       =>array( "name"=>"etat",          "order"=>3, "isHistorized"=>"1",    "Type"=>"info",     "subType"=>"binary",  "invertBinary"=>"0", "template"=>"light"),
                                                                                "Time-Time"       =>array( "name"=>"Time-Time",     "order"=>4, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"string",  "invertBinary"=>"0", "template"=>""),
                                                                                "Time-TimeStamp"  =>array( "name"=>"Time-TimeStamp","order"=>5, "isHistorized"=>"0",    "Type"=>"info",     "subType"=>"numeric", "invertBinary"=>"0", "template"=>"badge"),
                                                                                "On"              =>array( "name"=>"On",            "order"=>6, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"other",   "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/OnOff","request"=>"On") ),
                                                                                "Off"             =>array( "name"=>"Off",           "order"=>7, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"other",   "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/OnOff","request"=>"Off") ),
                                                                                "Toggle"          =>array( "name"=>"Toggle",        "order"=>8, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"other",   "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/OnOff","request"=>"Toggle") ),
                                                                                "Toggle"          =>array( "name"=>"Level",         "order"=>9, "isHistorized"=>"0",    "Type"=>"action",   "subType"=>"slider",  "invertBinary"=>"0", "template"=>"", "configuration"=>array("topic"=>"CmdAbeille/".$addr."/setLevel","request"=>"Level=#slider#&duration=01") ),
                                                                                // getEtat  clusterId=0006&attributeId=0000
                                                                                // getLevel clusterId=0008&attributeId=0000
                                                                                // getManufacturerName  clusterId=0000&attributeId=0004
                                                                                // getModelIdentifier   clusterId=0000&attributeId=0005
                                                                                // getSWBuild   clusterId=0000&attributeId=4000
                                                                                ),
                                         );
                
                // On créé toutes les commandes par defaut.
                foreach ( $objetCmdDefinition as $nomObjet => $cmdList )
                {
                    log::add('Abeille', 'info', 'foreach nomObjet: '.$nomObjet);
                    if ( $nomObjet==$value )
                    {
                        foreach ( $cmdList as $cmd => $cmdValueDefaut )
                        {
                            log::add('Abeille', 'info', 'Creation par defaut de la command:'.$nodeid.'/'.$cmd.' suivant model de l objet: '.$nomObjet);
                            $cmdlogic = new AbeilleCmd();
                            // id
                            $cmdlogic->setEqLogic_id($elogic->getId());
                            $cmdlogic->setEqType('Abeille');
                            $cmdlogic->setLogicalId($cmd);
                            $cmdlogic->setOrder($cmdValueDefaut["order"]);
                            $cmdlogic->setName( $cmdValueDefaut["name"] );
                            if ( $cmdValueDefaut["Type"]=="action" ) { $cmdlogic->setConfiguration('topic', 'Cmd'.$nodeid.'/'.$cmd); } else { $cmdlogic->setConfiguration('topic', $nodeid.'/'.$cmd); }
                            if ( $cmdValueDefaut["Type"]=="action" ) { $cmdlogic->setConfiguration('retain','0'); }
                            foreach ( $cmdValueDefaut["configuration"] as $confKey => $confValue )
                            {
                                $cmdlogic->setConfiguration($confKey,$confValue);
                            }
                            // template
                            $cmdlogic->setTemplate('dashboard',$cmdValueDefaut["template"]); $cmdlogic->setTemplate('mobile',$cmdValueDefaut["template"]);
                            $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                            $cmdlogic->setType($cmdValueDefaut["Type"]);
                            $cmdlogic->setSubType($cmdValueDefaut["subType"]);
                            // unite
                            $cmdlogic->setDisplay('invertBinary',$cmdValueDefaut["invertBinary"]);
                            // isVisible
                            // value
                            // html
                            // alert
                            
                            $cmdlogic->save();
                            $elogic->checkAndUpdateCmd($cmdId,$cmdValueDefaut["value"] );
                        }
                    }
                }
                
                // On defini le nom de l objet
                $elogic->checkAndUpdateCmd($cmdId,$value);
            }
            else
            {
                // Si equipement et cmd existe alors on met la valeur a jour
                $elogic = self::byLogicalId($nodeid, 'Abeille');
                if ( !is_object($elogic) )
                {
                    log::add('Abeille', 'debug', 'L objet n existe pas: '.$nodeid );
                    $_id = "BEN"; // JE ne sais pas alors je mets n importe quoi....
                    $_subject = "CmdAbeille/".$addr."/Annonce";
                    $_message = "";
                    $_retain = 0;
                    log::add('Abeille', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
                    $publish = new Mosquitto\Client(config::byKey('AbeilleId', 'Abeille', 'Jeedom') . '_pub_' . $_id);
                    if (config::byKey('AbeilleUser', 'Abeille', 'none') != 'none') {
                        $publish->setCredentials(config::byKey('AbeilleUser', 'Abeille'), config::byKey('AbeillePass', 'Abeille'));
                    }
                    $publish->connect(config::byKey('AbeilleAdress', 'Abeille', '127.0.0.1'), config::byKey('AbeillePort', 'Abeille', '1883'), 60);
                    $publish->publish($_subject, $_message, config::byKey('AbeilleQos', 'Abeille', '1'), $_retain);
                    for ($i = 0; $i < 100; $i++) {
                        // Loop around to permit the library to do its work
                        $publish->loop(1);
                    }
                    $publish->disconnect();
                    unset($publish);
                }
                else {
                    $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(),$cmdId);
                    if ( !is_object($cmdlogic) )
                    {
                        log::add('Abeille', 'debug', 'L objet: '.$nodeid.' existe mais pas la commande: '.$cmdId );
                    }
                    else
                    {
                        $elogic->checkAndUpdateCmd($cmdId,$value);
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
        
        public static function publishMosquitto($_id, $_subject, $_message, $_retain) {
            log::add('Abeille', 'debug', 'Envoi du message ' . $_message . ' vers ' . $_subject);
            $publish = new Mosquitto\Client(config::byKey('AbeilleId', 'Abeille', 'Jeedom') . '_pub_' . $_id);
            if (config::byKey('AbeilleUser', 'Abeille', 'none') != 'none') {
                $publish->setCredentials(config::byKey('AbeilleUser', 'Abeille'), config::byKey('AbeillePass', 'Abeille'));
            }
            $publish->connect(config::byKey('AbeilleAdress', 'Abeille', '127.0.0.1'), config::byKey('AbeillePort', 'Abeille', '1883'), 60);
            $publish->publish($_subject, $_message, config::byKey('AbeilleQos', 'Abeille', '1'), $_retain);
            for ($i = 0; $i < 100; $i++) {
                // Loop around to permit the library to do its work
                $publish->loop(1);
            }
            $publish->disconnect();
            unset($publish);
        }
        
    }
    
    class AbeilleCmd extends cmd {
        public function execute($_options = null) {
            switch ($this->getType()) {
                case 'action' :
                    $request = $this->getConfiguration('request','1');
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
                    Abeille::publishMosquitto($this->getId(), $topic, $request, $this->getConfiguration('retain','0'));
            }
            return true;
        }
    }
