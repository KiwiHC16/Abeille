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
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';

    // Il faut plusieures queues entre les process, on ne peut pas avoir un pot pourri pour tous comme avec Mosquitto.
    // 1: Abeille
    // 2: AbeilleParser -> Parser
    // 3: AbeilleMQTTCmd -> Cmd
    // 4: AbeilleTimer  -> Timer
    // 5: AbeilleLQI -> LQI
    // 6: xmlhttpMQTTSend -> xml
    // 7: queueKeyFormToCmd -> Form
    // 8: serie -> Serie
    // 9: Semaphore entre Parser et MQTTSend

    // 221: means AbeilleParser to(2) Abeille
    define('queueKeyAbeilleToAbeille',      121);
    define('queueKeyAbeilleToCmd',          123);
    define('queueKeyAbeilleToTimer',        124);
    define('queueKeyParserToAbeille',       221);
    define('queueKeyParserToCmd',           223);
    define('queueKeyParserToLQI',           225);
    define('queueKeyCmdToAbeille',          321);
    define('queueKeyCmdToCmd',              323);
    define('queueKeyTimerToAbeille',        421);
    define('queueKeyLQIToAbeille',          521);
    define('queueKeyLQIToCmd',              523);
    define('queueKeyXmlToAbeille',          621);
    define('queueKeyXmlToCmd',              623);
    define('queueKeyFormToCmd',             723);
    define('queueKeySerieToParser',         822);
    define('queueKeyParserToCmdSemaphore',  999);
    
    define('priorityUserCmd',       1); // Action utiliateur qui doit avoir une sensation de temps réel
    define('priorityInclusion',     2); // Message important car le temps est compté pour identifier certains équipements
    define('priorityInterrogation', 3); // Message pour recuperer des etats, valeurs
    

    Class MsgAbeille {
        /*
        public $message = array(
                                // 'topic' => 'Coucou class Abeille',
                                // 'payload' => 'me voici creation message',
                                );
         */
    }

    class Abeille extends eqLogic {

        // Is it the health of the plugin level menu Analyse->santé ? A verifier.
        public static function health() {
            $return = array();

            $return[] = array(
                              'test' => 'OK',
                              'result' => 'OK',
                              'advice' => 'OK',
                              'state' => 'OK',
                              );

            return $return;
        }

        public static function syncconfAbeille($_background = true) {
            if ($GLOBALS['debugKIWI']) echo "syncconfAbeille start\n";
            log::add('Abeille', 'debug', 'Starting syncconfAbeille');
            log::remove('Abeille_syncconf');
            log::add('Abeille_syncconf', 'info', 'syncconfAbeille Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            if ($_background) $cmd .= ' &';
            if ($GLOBALS['debugKIWI']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_syncconf', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_syncconf', 'info', 'syncconfAbeille End');
            if ($GLOBALS['debugKIWI']) echo "syncconfAbeille end\n";
        }

        public static function installGPIO($_background = true) {
            if ($GLOBALS['debugKIWI']) echo "installGPIO start\n";
            log::add('Abeille', 'debug', 'Starting installGPIO');
            log::remove('Abeille_installGPIO');
            log::add('Abeille_installGPIO', 'info', 'installGPIO Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/installGPIO.sh >> ' . log::getPathToLog('Abeille_installGPIO') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugKIWI']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_installGPIO', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_installGPIO', 'info', 'installGPIO End');
            if ($GLOBALS['debugKIWI']) echo "installGPIO end\n";
        }

        public static function installS0($_background = true) {
            if ($GLOBALS['debugKIWI']) echo "installS0 start\n";
            log::add('Abeille', 'debug', 'Starting installS0');
            log::remove('Abeille_installS0');
            log::add('Abeille_installS0', 'info', 'installS0 Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/installS0.sh >> ' . log::getPathToLog('Abeille_installS0') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugKIWI']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_installS0', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_installS0', 'info', 'installS0 End');
            if ($GLOBALS['debugKIWI']) echo "installS0 end\n";
        }

        public static function installSocat($_background = true) {
            if ($GLOBALS['debugKIWI']) echo "installS0 start\n";
            log::add('Abeille', 'debug', 'Starting installSocat');
            log::remove('Abeille_installSocat');
            log::add('Abeille_installSocat', 'info', 'installSocat Start');
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/installSocat.sh >> ' . log::getPathToLog('Abeille_installSocat') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugKIWI']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_installSocat', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_installSocat', 'info', 'installSocat End');
            if ($GLOBALS['debugKIWI']) echo "installSocat end\n";
        }

        public static function updateFirmwarePiZiGate($_background = true) {
            if ($GLOBALS['debugKIWI']) echo "updateFirmwarePiZiGate start\n";
            log::add('Abeille', 'debug', 'Starting updateFirmwarePiZiGate');
            log::remove('Abeille_updateFirmwarePiZiGate');
            log::add('Abeille_updateFirmwarePiZiGate', 'info', 'updateFirmwarePiZiGate Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/updateFrimware.sh >> ' . log::getPathToLog('Abeille_updateFirmwarePiZiGate') . ' 2>&1';
            if ($_background) $cmd .= ' &';
            if ($GLOBALS['debugKIWI']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_updateFirmwarePiZiGate', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_updateFirmwarePiZiGate', 'info', 'updateFirmwarePiZiGate End');
            if ($GLOBALS['debugKIWI']) echo "updateFirmwarePiZiGate end\n";
        }

        public static function resetPiZiGate($_background = true) {
            if ($GLOBALS['debugKIWI']) echo "resetPiZiGate start\n";
            log::add('Abeille', 'debug', 'Starting resetPiZiGate');
            log::remove('Abeille_resetPiZiGate');
            log::add('Abeille_resetPiZiGate', 'info', 'resetPiZiGate Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/resetPiZigate.sh >> ' . log::getPathToLog('Abeille_resetPiZiGate') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugKIWI']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_resetPiZiGate', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_resetPiZiGate', 'info', 'resetPiZiGate End');
            if ($GLOBALS['debugKIWI']) echo "resetPiZiGate end\n";
        }

        public static function testUpdateCommand( $fp, $parameter, $template, $NE ) {
            if ( isset($template) ) {
                if ( $template==$NE ) {
                    fwrite($fp, " - parameter identical, no change: \t\t\t'".$parameter."'\n" );
                    return 0;
                }
                else {
                    fwrite($fp, " ---------> parameter different, will be updated \t'".$parameter."': '".$NE."' -> '".$template. "'\n" );
                    return 1;
                }
            }
            else {
                fwrite($fp, " - parameter is not in the template, no change : \t'".$parameter."'\n" );
                return 0;
            }
            return 0;
        }

        public static function updateConfigAbeille($abeilleIdFilter = false) {
            if ($GLOBALS['debugKIWI']) echo "updateConfigAbeille start\n";
            log::add('Abeille', 'debug', 'Starting updateConfigAbeille');

            $fp = fopen(log::getPathToLog('Abeille_updateConfig'), 'w');
            fwrite($fp, "Starting updateConfigAbeille\n");
            if (isset($abeilleId)) fwrite($fp, "Device Id: ".$abeilleId."\n");

            $abeilles = Abeille::byType('Abeille');
            foreach ( $abeilles as $abeilleNumber=>$abeille ) {
                if ( (($abeilleIdFilter==false) || ($abeilleIdFilter==$abeille->getId())) && ($abeille->getName()!='Ruche') ) {
                    fwrite($fp, "Device Id en cours: ".$abeille->getId()."\n");

                    unset( $templateName );

                    if ( $abeille->getConfiguration('uniqId') ) {
                        $templateId = $abeille->getConfiguration('uniqId');
                        // TODO : from $templateId identify $templateName
                    }

                    if ( !isset($templateName)) {
                        foreach ( $abeille->getCmd() as $cmdNumber=>$cmd ) {
                            if ( $cmd->getName() == 'nom' ) {
                                if (strlen($cmd->execCmd())>1) {
                                    $templateName = $cmd->execCmd();
                                }
                            }
                        }
                    }

                    if ( strpos($templateName, "sensor_86sw2")>2 ) { $templateName="lumi.sensor_86sw2"; }

                    if ( !isset($templateName) ) {
                        $iconeToTemplate = array ( "CLA60RGBWOSRAM"=>"CLA60RGBWOSRAM",
                                                  "OSRAMClassicA60RGBW"=>"ClassicA60RGBW",
                                                  "OSRAMClassicA60Wclear-LIGHTIFY"=>"ClassicA60Wclear-LIGHTIFY",
                                                  "Connectedoutlet"=>"Connectedoutlet",
                                                  "ctrl_neutral1"=>"ctrl_neutral1",
                                                  "ctrl_neutral2"=>"ctrl_neutral2",
                                                  "Dimmerswitchwoneutral"=>"Dimmerswitchwoneutral",
                                                  "GLEDOPTO"=>"GLEDOPTO",
                                                  "GLEDOPTODualWhiteAndColor"=>"GL-S-004Z",
                                                  "HueGo"=>"LLC020",
                                                  "HueWhite"=>"LWB006",
                                                  "HueWhite"=>"LWB010",
                                                  "PAR1650TW"=>"PAR1650TW",
                                                  "XiaomiPrise"=>"plug",
                                                  "OsramLightify"=>"Plug01",
                                                  "OsramLightifyplug01OutDoor"=>"plug01OutDoor",
                                                  "XiaomiBouton"=>"remote.b1acn01",
                                                  "RWL021"=>"RWL021",
                                                  "XiaomiButtonSW861"=>"sensor_86sw2",
                                                  "sensor_cube"=>"sensor_cube",
                                                  "sensor_cube"=>"sensor_cube.aqgl01",
                                                  "XiaomiTemperatureRond"=>"sensor_ht",
                                                  "XiaomiPorte1"=>"sensor_magnet",
                                                  "XiaomiPorte"=>"sensor_magnet.aq2",
                                                  "XiaomiInfraRouge"=>"sensor_motion",
                                                  "XiaomiInfraRouge2"=>"sensor_motion.aq2",
                                                  "XiaomiSensorGaz"=>"sensor_natgas",
                                                  "XiaomiSensorSmoke"=>"sensor_smoke",
                                                  "XiaomiBouton1"=>"sensor_switch",
                                                  "XiaomiBouton"=>"sensor_switch.aq2",
                                                  "Xiaomiwleak_aq1"=>"sensor_wleak.aq1",
                                                  "Timer"=>"Timer",
                                                  "TRADFRIbulbE14Wopch400lm"=>"TRADFRIbulbE14Wopch400lm",
                                                  "IkeaTradfriBulbE14WSOpal400lm"=>"TRADFRIbulbE14WSopal400lm",
                                                  "TRADFRIbulbE27CWSopal600lm"=>"TRADFRIbulbE27CWSopal600lm",
                                                  "IkeaTradfriBulbE27Opal1000lm"=>"TRADFRIbulbE27opal1000lm",
                                                  "IkeaTradfriBulbE27Opal1000lm"=>"TRADFRIbulbE27Wopal1000lm",
                                                  "IkeaTradfriBulbE27WOpal1000lm2"=>"TRADFRIbulbE27Wopal1000lm2",
                                                  "IkeaTRADFRIbulbE27WSopal980lm"=>"TRADFRIbulbE27WSopal980lm",
                                                  "IkeaTradfriBulbGU10W400lm"=>"TRADFRIbulbGU10W400lm",
                                                  "IkeaTRADFRIbulbGU10WS400lm"=>"TRADFRIbulbGU10WS400lm",
                                                  "TRADFRIcontroloutlet"=>"TRADFRIcontroloutlet",
                                                  "IkeaTradfriMotionSensor"=>"TRADFRImotionsensor",
                                                  "IkeaTradfri5BtnRond"=>"TRADFRIremotecontrol",
                                                  "TRADFRItransformer10W"=>"TRADFRItransformer10W",
                                                  "TRADFRItransformer30W"=>"TRADFRItransformer30W",
                                                  "IkeaTradfriDimmer"=>"TRADFRIwirelessdimmer",
                                                  "XiaomiVibration"=>"vibration.aq1",
                                                  "voletProFalux"=>"volet",
                                                  "XiaomiTemperatureCarre"=>"weather",
                                                  "ZLO-DimmableLight"=>"ZLO-DimmableLight",
                                                  "ZLO-ExtendedColor"=>"ZLO-ExtendedColor",
                                                  "ZLO-LTOSensor"=>"ZLO-LTOSensor",
                                                  "ZLO-OccupancySensor"=>"ZLO-OccupancySensor" );

                        $templateName = $iconeToTemplate[$abeille->getConfiguration( 'icone' )];
                    }

                    $templateName = str_replace("lumi.","",$templateName);
                    $templateName = str_replace(" ","",$templateName);

                    // $abeille = abeille::byId( $cmd->getEqLogic_id() );
                    $template = tools::getJSonConfigFilebyDevicesTemplate( $templateName );
                    $templateJSON = json_encode( $template );
                    if ( isset($template[$templateName]['configuration']["mainEP"]) ) {
                        $templateJSON = str_replace("#EP#", $template[$templateName]['configuration']["mainEP"], $templateJSON);
                    }
                    else {
                        $templateJSON = str_replace("#EP#", "01", $templateJSON);
                    }
                    $template = json_decode( $templateJSON, true );
                    $templateMain = $template[$templateName]; // passe par une variable intermediaire pour simplifier l ecriture
                    $templateMainConfig = $template[$templateName]['configuration']; // passe par une variable intermediaire pour simplifier l ecriture

                    if ($GLOBALS['debugKIWI']) echo "Abeille Id: ".$abeille->getId()." - Abeille Name: ".$abeille->getName()." template: ->".$templateName."<-\n";
                    fwrite($fp, "-------------------------------------------------------------------\n");
                    fwrite($fp, "Abeille Id: ".$abeille->getId()." - Abeille Name: ".$abeille->getName()." template: ".$templateName."\n" );


                    // id
                    // name: don't touch the name which could have been define by user
                    // logicalId
                    // generic_type
                    // object_id
                    // eqType_name
                    // eqReal_id
                    // isVisible
                    // isEnable

                    // configuration
                    // topic
                    // type
                    // uniqId
                    if ( self::testUpdateCommand($fp, "uniqId", $templateMainConfig["uniqId"], $abeille->getConfiguration("uniqId") ) ) { $abeille->setConfiguration( "uniqId", $templateMainConfig["uniqId"] ); }
                    // icone
                    if ( self::testUpdateCommand($fp, "icone", $templateMainConfig["icone"], $abeille->getConfiguration("icone") ) ) { $abeille->setConfiguration( "icone", $templateMainConfig["icone"] ); }
                    // battery_type
                    if ( self::testUpdateCommand($fp, "battery_type", $templateMainConfig["battery_type"], $abeille->getConfiguration("battery_type") ) ) { $abeille->setConfiguration( "battery_type", $templateMainConfig["battery_type"] ); }
                    // Groupe
                    // mainEP
                    if ( self::testUpdateCommand($fp, "mainEP", $templateMainConfig["mainEP"], $abeille->getConfiguration("mainEP") ) ) { $abeille->setConfiguration( "mainEP", $templateMainConfig["mainEP"] ); }
                    // createtime
                    // positionX
                    // positionY
                    // updatetime
                    // poll
                    if ( self::testUpdateCommand($fp, "poll", $templateMainConfig["poll"], $abeille->getConfiguration("poll") ) ) { $abeille->setConfiguration( "poll", $templateMainConfig["poll"] ); }

                    // timeout
                    if ( self::testUpdateCommand($fp, "timeout", $templateMain["timeout"], $abeille->getTimeout("timeout") ) ) { $abeille->setTimeout( $templateMain["timeout"] ); }

                    // category
                    // display
                    // order
                    // if ( self::testUpdateCommand($fp, "order", $templateMain["order"], $abeille->getOrder("order") ) ) { $abeille->setOrder( $templateMain["order"] ); }
                    // comment
                    // status

                    $abeille->save();
                    // -----------------------------------------------------------------------------------------------------------------------
                    foreach ( $abeille->getCmd() as $cmdNumber=>$cmd ) {
                        // Je cherche la commande correspondante dans le template sur la base du nom donné à la commande
                        unset($templateCmd);
                        foreach ($template[$templateName]["Commandes"] as $cmdClusterFormat=>$templateCmdTmp) {
                            if ( $cmd->getName()==$templateCmdTmp['name'] ) {
                                $templateCmdId = $cmdClusterFormat;
                                $templateCmd = $template[$templateName]["Commandes"][$cmdClusterFormat];
                                $templateCmdConfig = $templateCmd['configuration'];
                            }
                        }

                        if ($GLOBALS['debugKIWI']) {
                            echo "\n".$templateCmdId."\n";
                            var_dump($templateCmd);
                            var_dump($templateCmdConfig);
                        }

                        if (isset($templateCmd)) {
                            // if ($GLOBALS['debugKIWI']) echo "Abeille Name: ".$abeille->getName()." - Cmd Name: ".$cmd->getName()."\n";
                            fwrite($fp, " \n---\nCmd Name: ".$cmd->getName()."\n" );
                            // id
                            // logicalId: sujet convert par les commande topic.
                            // if ( self::testUpdateCommand($fp, "logicalId",              $templateCmd['logicalId'],                      $cmd->getLogicalId() ) )                            { $cmd->setLogicalId($templateCmd['logicalId']);        }
                            // generic_type: type for homebridge
                            if ( self::testUpdateCommand($fp, "generic_type",           $templateCmd['generic_type'],                   $cmd->getGeneric_type() ) )                         { $cmd->setGeneric_type($templateCmd['generic_type']);  }
                            // eqType: 'Abeille' so no change
                            // name: comme c est le critere de comparaison, ca reste le meme.
                            // order
                            if ( self::testUpdateCommand($fp, "order",                  $templateCmd['order'],                          $cmd->getOrder() ) )                                { $cmd->setOrder($templateCmd['order']);                }
                            // type: on va considere que ca ne change pas
                            // subType: on va considere que ca ne change pas
                            // eqLogic_id: on va considere que ca ne change pas
                            // isHistorized
                            if ( self::testUpdateCommand($fp, "isHistorized",           $templateCmd['isHistorized'],                   $cmd->getIsHistorized() ) )                         { $cmd->setIsHistorized($templateCmd['isHistorized']);  }
                            // unite
                            if ( self::testUpdateCommand($fp, "unite",                  $templateCmd['unite'],                          $cmd->getUnite() ) )                                { $cmd->setUnite($templateCmd['unite']);                }
                            // configuration
                            // topic
                            if ( self::testUpdateCommand($fp, "topic",                  $templateCmdConfig['topic'],                   $cmd->getConfiguration('topic') ) )                 { $cmd->setConfiguration( 'topic',                  $templateCmdConfig['topic']); $cmd->setLogicalId($templateCmdId); }
                            // request
                            if ( self::testUpdateCommand($fp, "request",                $templateCmdConfig['request'],                  $cmd->getConfiguration('request') ) )               { $cmd->setConfiguration( 'request',                $templateCmdConfig['request']);     }
                            // AbeilleRejectValue
                            if ( self::testUpdateCommand($fp, "AbeilleRejectValue",     $templateCmdConfig['AbeilleRejectValue'],       $cmd->getConfiguration('AbeilleRejectValue') ) )    { $cmd->setConfiguration( 'AbeilleRejectValue',     $templateCmdConfig['AbeilleRejectValue']);     }
                            // returnStateValue
                            if ( self::testUpdateCommand($fp, "returnStateValue",       $templateCmdConfig['returnStateValue'],         $cmd->getConfiguration('returnStateValue') ) )      { $cmd->setConfiguration( 'returnStateValue',       $templateCmdConfig['returnStateValue']);       }
                            // returnStateTime
                            if ( self::testUpdateCommand($fp, "returnStateTime",        $templateCmdConfig['returnStateTime'],          $cmd->getConfiguration('returnStateTime') ) )       { $cmd->setConfiguration( 'returnStateTime',        $templateCmdConfig['returnStateTime']);        }
                            // repeatEventManagement
                            if ( self::testUpdateCommand($fp, "repeatEventManagement",  $templateCmdConfig['repeatEventManagement'],    $cmd->getConfiguration('repeatEventManagement') ) ) { $cmd->setConfiguration( 'repeatEventManagement',  $templateCmdConfig['repeatEventManagement']);  }
                            // visibilityCategory
                            if ( self::testUpdateCommand($fp, "visibilityCategory",     $templateCmdConfig['visibilityCategory'],       $cmd->getConfiguration('visibilityCategory') ) )    { $cmd->setConfiguration( 'visibilityCategory',     $templateCmdConfig['visibilityCategory']);     }
                            // visibiltyTemplate
                            if ( self::testUpdateCommand($fp, "visibiltyTemplate",      $templateCmdConfig['visibiltyTemplate'],        $cmd->getConfiguration('visibiltyTemplate') ) )     { $cmd->setConfiguration( 'visibiltyTemplate',      $templateCmdConfig['visibiltyTemplate']);      }
                            // minValue
                            if ( self::testUpdateCommand($fp, "minValue",               $templateCmdConfig['minValue'],                 $cmd->getConfiguration('minValue') ) )              { $cmd->setConfiguration( 'minValue',               $templateCmdConfig['minValue']);               }
                            // maxValue
                            if ( self::testUpdateCommand($fp, "maxValue",               $templateCmdConfig['maxValue'],                 $cmd->getConfiguration('maxValue') ) )              { $cmd->setConfiguration( 'maxValue',               $templateCmdConfig['maxValue']);               }
                            // calculValueOffset
                            if ( self::testUpdateCommand($fp, "calculValueOffset",      $templateCmdConfig['calculValueOffset'],        $cmd->getConfiguration('calculValueOffset') ) )     { $cmd->setConfiguration( 'calculValueOffset',      $templateCmdConfig['calculValueOffset']);      }
                            // template
                            // display
                            // html
                            // value
                            // isVisible
                            if ( self::testUpdateCommand($fp, "isVisible",              $templateCmd['isVisible'],                      $cmd->getIsVisible() ) )                            { $cmd->setIsVisible($templateCmd['isVisible']);        }
                            // alert

                            $cmd->save();
                        }
                        else {
                            // if ($GLOBALS['debugKIWI']) echo "Abeille Name: ".$abeille->getName()." - Cmd Name: ".$cmd->getName()." not found in template\n";
                            fwrite($fp, " \n---\nCmd Name: ".$cmd->getName()." ===================================> not found in template\n" );
                            log::add('Abeille', 'debug', "Abeille Name: ".$abeille->getName()." - Cmd Name: ".$cmd->getName()." not found in template");
                            // $cmd->setName("Cmd_not_in_template_".$cmd->getName());
                        } // if (isset($templateCmd))

                    } // foreach ( $abeille->getCmd() as $cmdId=>$cmd )
                } // if ( $cmd->getName() == "nom" )
            } // foreach ( $cmds as $cmdId=>$cmd )

            // log::add('updateConfigAbeille', 'info', 'updateConfigAbeille End');
            fwrite($fp, "Ending updateConfigAbeille\n");
            log::add('Abeille', 'debug', 'updateConfigAbeille end');
            if ($GLOBALS['debugKIWI']) echo "updateConfigAbeille end\n";

            fclose($fp);
        }

        public static function cronDaily() {


            log::add( 'Abeille', 'debug', 'Starting cronDaily ------------------------------------------------------------------------------------------------------------------------' );
            /**
             * Refresh LQI once a day to get IEEE in prevision of futur changes, to get network topo as fresh as possible in json
             */
            log::add('Abeille', 'debug', 'Launching AbeilleLQI.php');
            $ROOT= "/var/www/html/plugins/Abeille/Network" ;// getcwd();
            log::add('Abeille', 'debug', 'ROOT: '.$ROOT);
            $cmd = "cd ".$ROOT."; nohup /usr/bin/php AbeilleLQI.php >/dev/null 2>/dev/null &";
            log::add('Abeille', 'debug', $cmd);
            exec($cmd);
        }

        public static function cronHourly() {

            log::add( 'Abeille', 'debug', 'Starting cronHourly ------------------------------------------------------------------------------------------------------------------------' );

            //--------------------------------------------------------
            // Check Zigate Presence

            log::add('Abeille', 'debug', 'Check Zigate Presence');

            $param = self::getParameters();

            //--------------------------------------------------------
            // Refresh Ampoule Ikea Bind et set Report

            log::add('Abeille', 'debug', 'Refresh Ampoule Ikea Bind et set Report');

            $eqLogics = Abeille::byType('Abeille');
            $i=0;
            foreach ($eqLogics as $eqLogic) {
                // log::add('Abeille', 'debug', 'Icone: '.$eqLogic->getConfiguration("icone"));
                if (strpos("_".$eqLogic->getConfiguration("icone"), "IkeaTradfriBulb") > 0) {
                    $topicArray = explode("/", $eqLogic->getLogicalId());
                    $dest = $topicArray[0];
                    $addr = $topicArray[1];
                    $i=$i+1;

                    $ruche = new Abeille();
                    $commandIEEE = new AbeilleCmd();

                    // Recupere IEEE de la Ruche/ZiGate
                    $rucheId = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId();
                    // log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);

                    $ZiGateIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
                    // log::add('Abeille', 'debug', 'IEEE pour  Ruche: ' . $ZiGateIEEE);

                    $abeille = new Abeille();
                    $commandIEEE = new AbeilleCmd();

                    // Recupere IEEE de la Ruche/ZiGate
                    $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();
                    // log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);

                    $addrIEEE = $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'IEEE-Addr')->execCmd();
                    // log::add('Abeille', 'debug', 'IEEE pour abeille: ' . $addrIEEE);

                    log::add('Abeille', 'debug', 'Refresh bind and report for Ikea Bulb: '.$addr);
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/Ruche/bindShort&time=".(time()+(($i*33)+1)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0006&reportToAddress=".$ZiGateIEEE );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/Ruche/bindShort&time=".(time()+(($i*33)+2)), "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0008&reportToAddress=".$ZiGateIEEE );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/Ruche/setReport&time=".(time()+(($i*33)+3)), "address=".$addr."&ClusterId=0006&AttributeId=0000&AttributeType=10" );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/Ruche/setReport&time=".(time()+(($i*33)+4)), "address=".$addr."&ClusterId=0008&AttributeId=0000&AttributeType=20" );
                }
            }
            if ( ($i*33) > (3600) ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 1 heure.","Contactez KiwiHC16 sur le Forum.","Abeille/cron" );
            }

            log::add( 'Abeille', 'debug', 'Ending cronHourly ------------------------------------------------------------------------------------------------------------------------' );
        }

        public static function cron15() {

            log::add( 'Abeille', 'debug', 'Starting cron15 ------------------------------------------------------------------------------------------------------------------------' );
            /**
             * Look every 15 minutes if the kernel driver is not in error
             */
            $param = self::getParameters();

            log::add('Abeille', 'debug', 'Check USB driver potential crash');
            $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
            $output = array();
            exec(system::getCmdSudo().$cmd, $output);
            $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
            if ($usbZigateStatus != '0') {
                message::add( "Abeille", "Erreur, le pilote pl2303 est en erreur, impossible de communiquer avec la zigate.", "Il faut débrancher/rebrancher la zigate et relancer le demon.","Abeille/cron" );
                log::add( 'Abeille', 'debug', 'Ending cron15 ------------------------------------------------------------------------------------------------------------------------' );
            }

            log::add('Abeille', 'debug', 'Ping NE with 220V to check Online status');
            $eqLogics = Abeille::byType('Abeille');
            $i=0;
            foreach ($eqLogics as $eqLogic) {
                if (strlen($eqLogic->getConfiguration("battery_type")) == 0) {
                    $topicArray = explode("/", $eqLogic->getLogicalId());
                    $dest = $topicArray[0];
                    $addr = $topicArray[1];
                    if (strlen($addr) == 4) {
                        // echo "Short: " . $topicArray[1];
                        log::add('Abeille', 'debug', 'Ping: '.$addr);
                        $i=$i+1;
                        // Ca devrait être le fonctionnement normal
                        if (strlen($eqLogic->getConfiguration("mainEP"))>1) {
                            Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), $eqLogic->getConfiguration("mainEP") );
                        }
                        // Cette partie devrait disparaitre, elle existe depuis le debut car je ne comprenais pas bien le fonctionnement
                        else {
                            if ($eqLogic->getConfiguration("protocol") == "") {
                                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), "Default" );
                            }
                            if ($eqLogic->getConfiguration("protocol") == "Hue") {
                                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), "Hue" );
                            }
                            if ($eqLogic->getConfiguration("protocol") == "OSRAM") {
                                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), "OSRAM" );
                            }
                            if ($eqLogic->getConfiguration("protocol") == "Profalux") {
                                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), "AnnonceProfalux" );
                            }
                            if ($eqLogic->getConfiguration("protocol") == "LEGRAND") {
                                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$addr."/Annonce&time=".(time()+($i*23)), "Default" );
                            }
                        }
                    }
                }
            }
            if ( ($i*23) > (60*15) ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 15 minutes. Cas A.","Contacter KiwiHC15 sur le Forum","Abeille/cron" );
            }

            // Rafraichie l etat poll = 15
            $i=0;
            log::add('Abeille', 'debug', 'Get etat and Level des ampoules');
            foreach ($eqLogics as $eqLogic) {
                $dest = explode("/", $eqLogic->getLogicalId())[0];
                $address = explode("/", $eqLogic->getLogicalId())[1];
                if (strlen($address) == 4) {
                    if ($eqLogic->getConfiguration("poll") == "15") {
                        log::add('Abeille', 'debug', 'GetEtat/GetLevel: '.$addr);
                        $i=$i+1;
                        Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*13)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000" );
                    }

                }
            }
            if ( ($i*13) > (60*15) ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 15 minutes. Cas B.","Contacter KiwiHC16 sur le Forum","Abeille/cron" );
            }

            log::add(
                     'Abeille',
                     'debug',
                     'Ending cron15 ------------------------------------------------------------------------------------------------------------------------'
                     );

            return;
        }

        public static function cron() {

            // Cron tourne toutes les minutes
            // log::add( 'Abeille', 'debug', '----------- Starting cron ------------------------------------------------------------------------------------------------------------------------' );
            $param = self::getParameters();

            // https://github.com/jeelabs/esp-link
            // The ESP-Link connections on port 23 and 2323 have a 5 minute inactivity timeout.
            // so I need to create a minimum of traffic, so pull zigate every minutes
            if ($param['onlyTimer'] == 'N') {
                if ($param['AbeilleSerialPort']!="none") {
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille/Ruche/getVersion&time="      .(time()+20), "Version"          );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille/Ruche/getNetworkStatus&time=".(time()+24), "getNetworkStatus" );
                }
                if ($param['AbeilleSerialPort2']!="none") {
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille2/Ruche/getVersion&time="      .(time()+20), "Version"          );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille2/Ruche/getNetworkStatus&time=".(time()+24), "getNetworkStatus" );
                }
                if ($param['AbeilleSerialPort3']!="none") {
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille3/Ruche/getVersion&time="      .(time()+20), "Version"          );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille3/Ruche/getNetworkStatus&time=".(time()+24), "getNetworkStatus" );
                }
                if ($param['AbeilleSerialPort4']!="none") {
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille3/Ruche/getVersion&time="      .(time()+20), "Version"          );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille4/Ruche/getNetworkStatus&time=".(time()+24), "getNetworkStatus" );
                }
                if ($param['AbeilleSerialPort5']!="none") {
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmdAbeille5/Ruche/getVersion&time="      .(time()+20), "Version"          );
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, "TempoCmdAbeille5/Ruche/getNetworkStatus&time=".(time()+24), "getNetworkStatus" );
                }

            } else {
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille/Ruche/SW-SDK", "TimerMode" );
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille/Ruche/Time-TimeStamp", time() );         // TimeStamp
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "Abeille/Ruche/Time-Time", date("Y-m-d H:i:s") ); // 2018-10-06 03:13:31
            }

            $eqLogics = self::byType('Abeille');

            // Rafraichie l etat poll = 1
            // log::add('Abeille', 'debug', 'Get etat and Level des ampoules');
            $i = 0;
            foreach ($eqLogics as $eqLogic) {
                $dest = explode("/", $eqLogic->getLogicalId())[1];
                $address = explode("/", $eqLogic->getLogicalId())[1];
                if (strlen($address) == 4) {
                    if ($eqLogic->getConfiguration("poll") == "1") {
                        log::add('Abeille', 'debug', 'GetEtat/GetLevel: '.$address);
                        $i=$i+1;
                        Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000" );
                        Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0008&attributeId=0000" );
                    }
                }
            }
            if ( ($i*3) > 60 ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 1 minute.","Contacter KiwiHC15 sur le Forum","Abeille/cron" );
            }

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

                    // log::add('Abeille', 'debug', '--');
                    // log::add( 'Abeille', 'debug', 'Name: '.$eqLogic->getName().' Last: '.$last.' Timeout: '.$eqLogic->getTimeout( ).'s - Last+TimeOut: '.($last + $eqLogic->getTimeout()).' now: '.time().' Delta: '.(time( ) - ($last + $eqLogic->getTimeout())) );

                    // Alerte sur TimeOut Defini
                    if (($last + (60*$eqLogic->getTimeout())) > time()) {
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
            if (self::checkInclusionStatus( "Abeille"  ) == "01") Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille/Ruche/permitJoin", "Status" );
            if (self::checkInclusionStatus( "Abeille2" ) == "01") Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille2/Ruche/permitJoin", "Status" );
            if (self::checkInclusionStatus( "Abeille3" ) == "01") Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille3/Ruche/permitJoin", "Status" );
            if (self::checkInclusionStatus( "Abeille4" ) == "01") Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille4/Ruche/permitJoin", "Status" );
            if (self::checkInclusionStatus( "Abeille5" ) == "01") Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille5/Ruche/permitJoin", "Status" );


            // log::add( 'Abeille', 'debug', 'Ending cron ------------------------------------------------------------------------------------------------------------------------' );

        }

        public static function deamon_info() {
            $debug_deamon_info = 0;

            if ($debug_deamon_info) log::add('Abeille', 'debug', '**deamon info: IN**');

            // On suppose que tout est bon et on cherche les problemes.
            $return = array( 'state'                 => 'ok',  // On couvre le fait que le process tourne en tache de fond
                             'launchable'            => 'ok',  // On couvre la configuration de plugin
                             'launchable_message'    => "", );

            // On vérifie que le demon est demarable
            // On verifie qu'on n'a pas d erreur dans la recuperation des parametres
            $parameters = self::getParameters();
            if ( $parameters['parametersCheck'] != "ok" ) {
                log::add('Abeille', 'warning', 'deamon_info: parametersCheck not ok');
                $return['launchable'] = $parameters['parametersCheck'];
                $return['launchable_message'] = $parameters['parametersCheck_message'];
            }

            // On regarde si tout tourne already
            // On verifie que le cron tourne
            if (is_object(cron::byClassAndFunction('Abeille', 'deamon'))) {
                if ( !cron::byClassAndFunction('Abeille', 'deamon')->running() ) {
                    log::add('Abeille', 'warning', 'deamon_info: cron not running');
                    message::add('Abeille', 'Warning: deamon_info: cron not running','','Abeille/Demon');
                    $return['state'] = "nok";
                }
            }

            // Nb de demon devant tourner: mosquitto plus les demons
            //check running deamon /!\ if using sudo nbprocess x2
            $nbProcessExpected = 0; // Comptons les process prevus.
            $nbProcessExpected++;   // Process AbeilleTimer quoi qu'il arrive
            if (self::getParameters()['onlyTimer'] == 'N') { $nbProcessExpected += 2; } // Parser + MQTTCmd
            if ( (self::getParameters()['AbeilleSerialPort']  == '/dev/zigate' ) && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected+=2; } // Socat + SerialRead
            if ( (self::getParameters()['AbeilleSerialPort2'] == '/dev/zigate2') && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected+=2; } // Socat + SerialRead
            if ( (self::getParameters()['AbeilleSerialPort3'] == '/dev/zigate3') && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected+=2; } // Socat + SerialRead
            if ( (self::getParameters()['AbeilleSerialPort4'] == '/dev/zigate4') && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected+=2; } // Socat + SerialRead
            if ( (self::getParameters()['AbeilleSerialPort5'] == '/dev/zigate5') && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected+=2; } // Socat + SerialRead

            if ( (preg_match("(tty|monit)", self::getParameters()['AbeilleSerialPort' ])) && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected++; } // SerialRead
            if ( (preg_match("(tty|monit)", self::getParameters()['AbeilleSerialPort2'])) && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected++; } // SerialRead
            if ( (preg_match("(tty|monit)", self::getParameters()['AbeilleSerialPort3'])) && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected++; } // SerialRead
            if ( (preg_match("(tty|monit)", self::getParameters()['AbeilleSerialPort4'])) && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected++; } // SerialRead
            if ( (preg_match("(tty|monit)", self::getParameters()['AbeilleSerialPort5'])) && (self::getParameters()['onlyTimer'] == 'N') ) { $nbProcessExpected++; } // SerialRead
            $return['nbProcessExpected'] = $nbProcessExpected;


            // Combien de demons tournent ?
            exec("ps -e -o '%p;%a' --cols=10000 | grep -v awk | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer|Socat).php /' | cut -d ';'  -f 1 | wc -l", $output1 );

            $nbProcess = $output1[0];
            $return['nbProcess'] = $nbProcess;

            if ( ($nbProcess != $nbProcessExpected) ) {
                if ($debug_deamon_info) log::add('Abeille', 'debug', 'deamon_info, nombre de demons: '.$output1[0]);
                if ($debug_deamon_info) log::add( 'Abeille', 'info', 'deamon_info: found '.$nbProcess.'/'.$nbProcessExpected.' running.' );
                if ($debug_deamon_info) message::add( 'Abeille', 'Warning: deamon_info: found '.$nbProcess.'/'.$nbProcessExpected.' running.','','Abeille/Demon' );
                $return['state'] = "nok";
            }

            if ($debug_deamon_info) log::add( 'Abeille', 'debug', '**deamon info: OUT**  deamon deamon_info: '.json_encode($return) );

            return $return;
        }

        public static function deamon_start_cleanup($message = null) {
            // This function is used to run some cleanup before the demon start, or update the database du to data change needed.
            $debug = 0;

            log::add('Abeille', 'debug', 'deamon_start_cleanup: Debut des modifications si nécessaire');

            // ******************************************************************************************************************
            // Remove temporary files
            $FileLock = dirname(__FILE__).'/../../AbeilleLQI_MapData.json.lock';
            if (file_exists($FileLock)) {
                $content = file_get_contents($FileLock);
                log::add('Abeille', 'debug', $DataFile.' content: '.$content);
                if (strpos("_".$content, "done") != 1) {
                    unlink( $FileLock );
                    log::add('Abeille', 'debug', 'Deleting '.$FileLock );
                }
            }

            return;
        }

        public static function deamon_start($_debug = false) {
            // bt_startDeamon -> jeedom.plugin.deamonStart -> plugin.class.js: deamonStart -> ore/ajax/plugin.ajax.php(deamonStart) -> $plugin->deamon_start(init('forceRestart', 0))
            log::add('Abeille', 'debug', 'deamon start: IN -----------Starting --------------------');

            self::deamon_stop();

            log::add('Abeille', 'debug', "Attention du lien");
            // sleep(30); // leave time for socat to restart in case of monit setup.

            message::removeAll('Abeille', '');
            message::removeAll('Abeille', 'Abeille/Demon');
            message::removeAll('Abeille', 'Abeille/cron');
            message::removeAll('Abeille', 'Abeille/Abeille');

            $param = self::getParameters();

            if (self::dependancy_info()['state'] != 'ok') {
                message::add("Abeille", "Tentative de demarrage alors qu il y a un soucis avec les dependances", "Avez vous installée les dépendances.", 'Abeille/Demon');
                log::add('Abeille', 'debug', "Tentative de demarrage alors qu il y a un soucis avec les dependances");
                return false;
            }

            if (!is_object(cron::byClassAndFunction('Abeille', 'deamon'))) {
                log::add('Abeille', 'error', 'deamon_start: Tache cron introuvable');
                message::add("Abeille", "deamon_start: Tache cron introuvable", "Est un  bug dans Abeille ?", "Abeille/Demon");
                throw new Exception(__('Tache cron introuvable', __FILE__));
            }

            cron::byClassAndFunction('Abeille', 'deamon')->run();

            self::deamon_start_cleanup();

            // Start other deamons

            $nohup = "/usr/bin/nohup";
            $php = "/usr/bin/php";
            $dirdeamon = dirname(__FILE__)."/../../core/class/";

            if ($param['onlyTimer'] != 'Y') {

                log::add('Abeille','debug','deamon_start: Port serie 1 defini dans la configuration. ->'.$param['AbeilleSerialPort'].'<-');
                log::add('Abeille','debug','deamon_start: Port serie 2 defini dans la configuration. ->'.$param['AbeilleSerialPort2'].'<-');
                log::add('Abeille','debug','deamon_start: Port serie 3 defini dans la configuration. ->'.$param['AbeilleSerialPort3'].'<-');
                log::add('Abeille','debug','deamon_start: Port serie 4 defini dans la configuration. ->'.$param['AbeilleSerialPort4'].'<-');
                log::add('Abeille','debug','deamon_start: Port serie 5 defini dans la configuration. ->'.$param['AbeilleSerialPort5'].'<-');

                $deamon21 = "AbeilleSocat.php";
                $paramdeamon21 = $param['AbeilleSerialPort'].' '.log::convertLogLevel(log::getLogLevel('Abeille')).' '.$param['IpWifiZigate'];
                $log21 = " > ".log::getPathToLog(substr($deamon21, 0, (strrpos($deamon21, "."))))."1";

                $deamon22 = "AbeilleSocat.php";
                $paramdeamon22 = $param['AbeilleSerialPort2'].' '.log::convertLogLevel(log::getLogLevel('Abeille')).' '.$param['IpWifiZigate2'];
                $log22 = " > ".log::getPathToLog(substr($deamon22, 0, (strrpos($deamon22, "."))))."2";

                $deamon23 = "AbeilleSocat.php";
                $paramdeamon23 = $param['AbeilleSerialPort3'].' '.log::convertLogLevel(log::getLogLevel('Abeille')).' '.$param['IpWifiZigate3'];
                $log23 = " > ".log::getPathToLog(substr($deamon23, 0, (strrpos($deamon23, "."))))."3";

                $deamon24 = "AbeilleSocat.php";
                $paramdeamon24 = $param['AbeilleSerialPort4'].' '.log::convertLogLevel(log::getLogLevel('Abeille')).' '.$param['IpWifiZigate4'];
                $log24 = " > ".log::getPathToLog(substr($deamon24, 0, (strrpos($deamon24, "."))))."4";

                $deamon25 = "AbeilleSocat.php";
                $paramdeamon25 = $param['AbeilleSerialPort5'].' '.log::convertLogLevel(log::getLogLevel('Abeille')).' '.$param['IpWifiZigate5'];
                $log25 = " > ".log::getPathToLog(substr($deamon25, 0, (strrpos($deamon25, "."))))."5";


                $deamon11 = "AbeilleSerialRead.php";
                $paramdeamon11 = 'Abeille '.$param['AbeilleSerialPort'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                // $paramdeamon0 = '/dev/Abeille'.' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $log11 = " > ".log::getPathToLog(substr($deamon11, 0, (strrpos($deamon11, "."))))."1";
                // exec(system::getCmdSudo().'ln -s '.$param['AbeilleSerialPort'].' /dev/Abeille');

                $deamon12 = "AbeilleSerialRead.php";
                $paramdeamon12 = 'Abeille2 '.$param['AbeilleSerialPort2'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $log12 = " > ".log::getPathToLog(substr($deamon12, 0, (strrpos($deamon12, "."))))."2";

                $deamon13 = "AbeilleSerialRead.php";
                $paramdeamon13 = 'Abeille3 '.$param['AbeilleSerialPort3'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $log13 = " > ".log::getPathToLog(substr($deamon13, 0, (strrpos($deamon13, "."))))."3";

                $deamon14 = "AbeilleSerialRead.php";
                $paramdeamon14 = 'Abeille4 '.$param['AbeilleSerialPort4'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $log14 = " > ".log::getPathToLog(substr($deamon14, 0, (strrpos($deamon14, "."))))."4";

                $deamon15 = "AbeilleSerialRead.php";
                $paramdeamon15 = 'Abeille5 '.$param['AbeilleSerialPort5'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $log15 = " > ".log::getPathToLog(substr($deamon15, 0, (strrpos($deamon15, "."))))."5";



                $deamon2 = "AbeilleParser.php";
                // $paramdeamon2 = $param['AbeilleSerialPort'].' '.$param['AbeilleAddress'].' '.$param['AbeillePort'].' '.$param['AbeilleUser'].' '.$param['AbeillePass'].' '.$param['AbeilleQos'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $paramdeamon2 = log::convertLogLevel(log::getLogLevel('Abeille'));
                $log2 = " > ".log::getPathToLog(substr($deamon2, 0, (strrpos($deamon2, "."))));

                $deamon3 = "AbeilleMQTTCmd.php";
                // $paramdeamon3 = $param['AbeilleSerialPort'].' '.$param['AbeilleAddress'].' '.$param['AbeillePort'].' '.$param['AbeilleUser'].' '.$param['AbeillePass'].' '.$param['AbeilleQos'].' '.log::convertLogLevel(log::getLogLevel('Abeille'));
                $paramdeamon3 = log::convertLogLevel(log::getLogLevel('Abeille'));
                $log3 = " > ".log::getPathToLog(substr($deamon3, 0, (strrpos($deamon3, "."))));

                // ----------------

                if ( $param['AbeilleSerialPort'] == "/dev/zigate" ) {
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon21." ".$paramdeamon21.$log21;
                    log::add('Abeille', 'debug', 'Start deamon socat: '.$cmd);
                    exec($cmd.' 2>&1 &');
                    sleep(5);
                }

                if ( $param['AbeilleSerialPort2'] == "/dev/zigate2" ) {
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon22." ".$paramdeamon22.$log22;
                    log::add('Abeille', 'debug', 'Start deamon socat: '.$cmd);
                    exec($cmd.' 2>&1 &');
                    sleep(5);
                }


                if ( $param['AbeilleSerialPort3'] == "/dev/zigate3" ) {
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon23." ".$paramdeamon23.$log23;
                    log::add('Abeille', 'debug', 'Start deamon socat: '.$cmd);
                    exec($cmd.' 2>&1 &');
                    sleep(5);
                }

                if ( $param['AbeilleSerialPort4'] == "/dev/zigate4" ) {
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon24." ".$paramdeamon24.$log24;
                    log::add('Abeille', 'debug', 'Start deamon socat: '.$cmd);
                    exec($cmd.' 2>&1 &');
                    sleep(5);
                }


                if ( $param['AbeilleSerialPort5'] == "/dev/zigate5" ) {
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon25." ".$paramdeamon25.$log25;
                    log::add('Abeille', 'debug', 'Start deamon socat: '.$cmd);
                    exec($cmd.' 2>&1 &');
                    sleep(5);
                }


                if ( $param['AbeilleSerialPort'] != 'none' ) {
                    if (@!file_exists($param['AbeilleSerialPort'])) {
                        log::add('Abeille','warning','deamon_start: serialPort n existe pas: '.$param['AbeilleSerialPort'] );
                        message::add('Abeille','Warning: le port serie vers la zigate n existe pas: '.$param['AbeilleSerialPort'], "Vérifier la connection de la zigate, verifier l adresse IP:port pour la version Wifi.", "Abeille/Demon" );
                        $return['parametersCheck']="nok";
                        $return['parametersCheck_message'] = __('Le port n existe pas (zigate déconnectée ?)', __FILE__);
                        return false;
                    }
                }

                if ( $param['AbeilleSerialPort2'] != 'none' ) {
                    if (@!file_exists($param['AbeilleSerialPort2'])) {
                        log::add('Abeille','warning','deamon_start: serialPort n existe pas: '.$param['AbeilleSerialPort2'] );
                        message::add('Abeille','Warning: le port serie vers la zigate n existe pas: '.$param['AbeilleSerialPort2'], "Vérifier la connection de la zigate, verifier l adresse IP:port pour la version Wifi.", "Abeille/Demon" );
                        $return['parametersCheck']="nok";
                        $return['parametersCheck_message'] = __('Le port n existe pas (zigate déconnectée ?)', __FILE__);
                        return false;
                    }
                }

                if ( $param['AbeilleSerialPort3'] != 'none' ) {
                    if (@!file_exists($param['AbeilleSerialPort3'])) {
                        log::add('Abeille','warning','deamon_start: serialPort n existe pas: '.$param['AbeilleSerialPort3'] );
                        message::add('Abeille','Warning: le port serie vers la zigate n existe pas: '.$param['AbeilleSerialPort3'], "Vérifier la connection de la zigate, verifier l adresse IP:port pour la version Wifi.", "Abeille/Demon" );
                        $return['parametersCheck']="nok";
                        $return['parametersCheck_message'] = __('Le port n existe pas (zigate déconnectée ?)', __FILE__);
                        return false;
                    }
                }

                if ( $param['AbeilleSerialPort4'] != 'none' ) {
                    if (@!file_exists($param['AbeilleSerialPort4'])) {
                        log::add('Abeille','warning','deamon_start: serialPort n existe pas: '.$param['AbeilleSerialPort4'] );
                        message::add('Abeille','Warning: le port serie vers la zigate n existe pas: '.$param['AbeilleSerialPort4'], "Vérifier la connection de la zigate, verifier l adresse IP:port pour la version Wifi.", "Abeille/Demon" );
                        $return['parametersCheck']="nok";
                        $return['parametersCheck_message'] = __('Le port n existe pas (zigate déconnectée ?)', __FILE__);
                        return false;
                    }
                }

                if ( $param['AbeilleSerialPort5'] != 'none' ) {
                    if (@!file_exists($param['AbeilleSerialPort5'])) {
                        log::add('Abeille','warning','deamon_start: serialPort n existe pas: '.$param['AbeilleSerialPort5'] );
                        message::add('Abeille','Warning: le port serie vers la zigate n existe pas: '.$param['AbeilleSerialPort5'], "Vérifier la connection de la zigate, verifier l adresse IP:port pour la version Wifi.", "Abeille/Demon" );
                        $return['parametersCheck']="nok";
                        $return['parametersCheck_message'] = __('Le port n existe pas (zigate déconnectée ?)', __FILE__);
                        return false;
                    }
                }



                if ( $param['AbeilleSerialPort'] != "none" ) {
                    exec(system::getCmdSudo().'chmod 777 '.$param['AbeilleSerialPort'].' > /dev/null 2>&1');
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon11." ".$paramdeamon11.$log11;
                    log::add('Abeille', 'debug', 'Start deamon SerialRead: '.$cmd);
                    exec($cmd.' 2>&1 &');
                }


                if ( $param['AbeilleSerialPort2'] != "none" ) {
                    exec(system::getCmdSudo().'chmod 777 '.$param['AbeilleSerialPort2'].' > /dev/null 2>&1');
                    $cmd = $nohup." ".$php." ".$dirdeamon.$deamon12." ".$paramdeamon12.$log12;
                    log::add('Abeille', 'debug', 'Start deamon SerialRead: '.$cmd);
                    exec($cmd.' 2>&1 &');
                }

                if ( $param['AbeilleSerialPort3'] != "none" ) {
                     exec(system::getCmdSudo().'chmod 777 '.$param['AbeilleSerialPort3'].' > /dev/null 2>&1');
                     $cmd = $nohup." ".$php." ".$dirdeamon.$deamon13." ".$paramdeamon13.$log13;
                     log::add('Abeille', 'debug', 'Start deamon SerialRead: '.$cmd);
                     exec($cmd.' 2>&1 &');
                 }

                if ( $param['AbeilleSerialPort4'] != "none" ) {
                     exec(system::getCmdSudo().'chmod 777 '.$param['AbeilleSerialPort4'].' > /dev/null 2>&1');
                     $cmd = $nohup." ".$php." ".$dirdeamon.$deamon14." ".$paramdeamon14.$log14;
                     log::add('Abeille', 'debug', 'Start deamon SerialRead: '.$cmd);
                     exec($cmd.' 2>&1 &');
                 }

                if ( $param['AbeilleSerialPort4'] != "none" ) {
                     exec(system::getCmdSudo().'chmod 777 '.$param['AbeilleSerialPort5'].' > /dev/null 2>&1');
                     $cmd = $nohup." ".$php." ".$dirdeamon.$deamon15." ".$paramdeamon15.$log15;
                     log::add('Abeille', 'debug', 'Start deamon SerialRead: '.$cmd);
                     exec($cmd.' 2>&1 &');
                 }


                $cmd = $nohup." ".$php." ".$dirdeamon.$deamon2." ".$paramdeamon2.$log2;
                log::add('Abeille', 'debug', 'Start deamon Parser: '.$cmd);
                exec($cmd.' 2>&1 &');


                $cmd = $nohup." ".$php." ".$dirdeamon.$deamon3." ".$paramdeamon3.$log3;
                log::add('Abeille', 'debug', 'Start deamon MQTT: '.$cmd);
                exec($cmd.' 2>&1 &');

            }

            $deamon4 = "AbeilleMQTTCmdTimer.php";
            // $paramdeamon4 = $param['AbeilleSerialPort'].' '.$param['AbeilleAddress'].' '.$param['AbeillePort']. ' '.$param['AbeilleUser'].' '.$param['AbeillePass'].' '.$param['AbeilleQos'].' '.log::convertLogLevel( log::getLogLevel('Abeille') );
            $paramdeamon4 = log::convertLogLevel( log::getLogLevel('Abeille') );
            $log4 = " > ".log::getPathToLog(substr($deamon4, 0, (strrpos($deamon4, "."))));

            $cmd = $nohup." ".$php." ".$dirdeamon.$deamon4." ".$paramdeamon4.$log4;
            log::add('Abeille', 'debug', 'Start deamon Timer: '.$cmd);
            exec($cmd.' 2>&1 &');

            sleep(2);

            // Send a message to Abeille to ask for Abeille Object creation: inclusion, ...
            log::add('Abeille', 'debug', 'deamon_start: ***** Envoi de la creation de ruche par défaut ********');
            if ( $param['AbeilleSerialPort'] != "none" ) {
                log::add('Abeille', 'debug', 'deamon_start: ***** ruche 1 (Abeille): '.basename($param['AbeilleSerialPort']));
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/Ruche/CreateRuche", "Abeille" );
                log::add('Abeille', 'debug', 'deamon_start: ***** Demarrage du réseau Zigbee 1 ********');
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille/Ruche/startNetwork", "StartNetwork" );
            }

            if ( $param['AbeilleSerialPort2'] != "none" ) {
                log::add('Abeille', 'debug', 'deamon_start: ***** ruche 2 (Abeille2): '.basename($param['AbeilleSerialPort2']));
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/Ruche/CreateRuche", "Abeille2" );
                log::add('Abeille', 'debug', 'deamon_start: ***** Demarrage du réseau Zigbee 2 ********');
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille2/Ruche/startNetwork", "StartNetwork" );
            }

            if ( $param['AbeilleSerialPort3'] != "none" ) {
                log::add('Abeille', 'debug', 'deamon_start: ***** ruche 3 (Abeille3): '.basename($param['AbeilleSerialPort3']));
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/Ruche/CreateRuche", "Abeille3" );
                log::add('Abeille', 'debug', 'deamon_start: ***** Demarrage du réseau Zigbee 3 ********');
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille3/Ruche/startNetwork", "StartNetwork" );
            }

            if ( $param['AbeilleSerialPort4'] != "none" ) {
                log::add('Abeille', 'debug', 'deamon_start: ***** ruche 4 (Abeille4): '.basename($param['AbeilleSerialPort4']));
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/Ruche/CreateRuche", "Abeille4" );
                log::add('Abeille', 'debug', 'deamon_start: ***** Demarrage du réseau Zigbee 4 ********');
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille4/Ruche/startNetwork", "StartNetwork" );
            }

            if ( $param['AbeilleSerialPort5'] != "none" ) {
                log::add('Abeille', 'debug', 'deamon_start: ***** ruche 5 (Abeille5): '.basename($param['AbeilleSerialPort5']));
                Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, "CmdRuche/Ruche/CreateRuche", "Abeille5" );
                log::add('Abeille', 'debug', 'deamon_start: ***** Demarrage du réseau Zigbee 5 ********');
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "CmdAbeille5/Ruche/startNetwork", "StartNetwork" );
            }

            log::add('Abeille', 'debug', 'deamon start: OUT --------------- all done ----------------');

            return true;
        }

        public static function mapAbeillePort( $Abeille ) {
            
            $param = self::getParameters();
            
            if ( $Abeille == "Abeille"  )  return basename($param['AbeilleSerialPort' ]);
            if ( $Abeille == "Abeille2" )  return basename($param['AbeilleSerialPort2']);
            if ( $Abeille == "Abeille3" )  return basename($param['AbeilleSerialPort3']);
            if ( $Abeille == "Abeille4" )  return basename($param['AbeilleSerialPort4']);
            if ( $Abeille == "Abeille5" )  return basename($param['AbeilleSerialPort5']);
            
        }
        
        public static function mapPortAbeille ( $port ) {
            
            $param = self::getParameters();
            
            if ( $port == $param['AbeilleSerialPort' ] )  return "Abeille";
            if ( $port == $param['AbeilleSerialPort2'] )  return "Abeille2";
            if ( $port == $param['AbeilleSerialPort3'] )  return "Abeille3";
            if ( $port == $param['AbeilleSerialPort4'] )  return "Abeille4";
            if ( $port == $param['AbeilleSerialPort5'] )  return "Abeille5";
        }
        
        public static function deamon_stop() {
            log::add('Abeille', 'debug', 'deamon stop: IN -------------KIWI------------------');
            // Stop socat if exist
            exec("ps -e -o '%p %a' --cols=10000 | awk '/socat /' | awk '/\/dev\/zigate/' | awk '{print $1}' | tr  '\n' ' '", $output);
            log::add('Abeille', 'debug', 'deamon stop: Killing deamons socat: '.implode($output, '!'));
            system::kill($output, true);
            exec(system::getCmdSudo()."kill -15 ".implode($output, ' ')." 2>&1");
            exec(system::getCmdSudo()."kill -9 ".implode($output, ' ')." 2>&1");

            // Stop other deamon
            exec("ps -e -o '%p %a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer|Socat).php /' | awk '{print $1}' | tr  '\n' ' '", $output);
            log::add('Abeille', 'debug', 'deamon stop: Killing deamons: '.implode($output, '!'));
            system::kill($output, true);
            exec(system::getCmdSudo()."kill -15 ".implode($output, ' ')." 2>&1");
            exec(system::getCmdSudo()."kill -9 ".implode($output, ' ')." 2>&1");

            // Stop main deamon
            log::add('Abeille', 'debug', 'deamon stop: Stopping cron');
            $cron = cron::byClassAndFunction('Abeille', 'deamon');
            if (is_object($cron)) {
                $cron->halt();
                // log::add('Abeille', 'error', 'deamon stop: demande d arret du cron faite');
            }
            else {
                log::add('Abeille', 'error', 'deamon stop: Abeille, Tache cron introuvable');
            }

            msg_remove_queue ( msg_get_queue(queueKeyAbeilleToAbeille) );
            msg_remove_queue ( msg_get_queue(queueKeyAbeilleToCmd) );
            msg_remove_queue ( msg_get_queue(queueKeyAbeilleToTimer) );
            msg_remove_queue ( msg_get_queue(queueKeyParserToAbeille) );
            msg_remove_queue ( msg_get_queue(queueKeyParserToCmd) );
            msg_remove_queue ( msg_get_queue(queueKeyParserToLQI) );
            msg_remove_queue ( msg_get_queue(queueKeyCmdToAbeille) );
            msg_remove_queue ( msg_get_queue(queueKeyCmdToCmd) );
            msg_remove_queue ( msg_get_queue(queueKeyTimerToAbeille) );
            msg_remove_queue ( msg_get_queue(queueKeyLQIToAbeille) );
            msg_remove_queue ( msg_get_queue(queueKeyLQIToCmd) );
            msg_remove_queue ( msg_get_queue(queueKeyXmlToAbeille) );
            msg_remove_queue ( msg_get_queue(queueKeyXmlToCmd) );
            msg_remove_queue ( msg_get_queue(queueKeyFormToCmd) );
            msg_remove_queue ( msg_get_queue(queueKeySerieToParser) );
            msg_remove_queue ( msg_get_queue(queueKeyParserToCmdSemaphore) );

            log::add('Abeille', 'debug', 'deamon stop: OUT -------------------------------');

        }

        public static function dependancy_info() {

            log::add( 'Abeille', 'warning', '-------------------------------------> dependancy_info()' );

            // Called by js dans plugin.class.js(getDependancyInfo) -> plugin.ajax.php(dependancy_info())
            // $dependancy_info['state'] pour affichage
            // state = [ok / nok / in_progress (progression/duration)] / state
            // il n ' y plus de dépendance hotmis pour la zigate wifi (socat) qui est installé par un script a part.
            $debug_dependancy_info = 1;

            $return = array();
            $return['state'] = 'ok';
            $return['progress_file'] = jeedom::getTmpFolder('Abeille').'/dependance';

            // Check package socat
            $cmd = "dpkg -l socat";
            exec($cmd, $output_dpkg, $return_var);
            if ($output_dpkg[0] == "") {
                message::add( "Abeille", "Le package socat est nécéssaire pour l'utilisation de la zigate Wifi. Si vous avez la zigate usb, vous pouvez ignorer ce message");
                log::add( 'Abeille', 'warning', 'Le package socat est nécéssaire pour l\'utilisation de la zigate Wifi.' );
            }
            // log::add( 'Abeille', 'debug', ' state: '.$return['state'] );
            // log::add( 'Abeille', 'debug', ' progress: '.file_get_contents($return['progress_file'] ));  <= Provoque une erreur dans le log http.
            // log::add( 'Abeille', 'debug', '-------------------------------------> dependancy_info() END' );

            if ($debug_dependancy_info) log::add('Abeille', 'debug', 'dependancy_info: '.json_encode($return) );

            return $return;
        }

        public static function dependancy_install() {
            log::add('Abeille', 'debug', 'Installation des dépendances: IN');
            message::add( "Abeille", "L installation des dependances est en cours", "N oubliez pas de lire la documentation: https://github.com/KiwiHC16/Abeille/tree/master/Documentation", "Abeille/Dependances" );
            log::remove(__CLASS__.'_update');
            $result = [ 'script' => dirname(__FILE__).'/../../resources/install_#stype#.sh '.jeedom::getTmpFolder( 'Abeille' ).'/dependance',
                'log' => log::getPathToLog(__CLASS__.'_update')
            ];
            log::add('Abeille', 'debug', 'Installation des dépendances: OUT: '.implode($result, ' X '));

            return $result;
        }

        public static function deamon() {

            try {
                $queueKeyAbeilleToAbeille   = msg_get_queue(queueKeyAbeilleToAbeille);
                $queueKeyParserToAbeille    = msg_get_queue(queueKeyParserToAbeille);
                $queueKeyTimerToAbeille     = msg_get_queue(queueKeyTimerToAbeille);
                $queueKeyCmdToAbeille       = msg_get_queue(queueKeyCmdToAbeille);
                $queueKeyXmlToAbeille       = msg_get_queue(queueKeyXmlToAbeille);

                $msg_type = NULL;
                $msg = NULL;
                $max_msg_size = 512;

                // https: github.com/torvalds/linux/blob/master/include/uapi/asm-generic/errno.h
                // #define    ENOMSG        42    /* No message of desired type */
                $errorcodeMsg = 0;

                while ( true ) {
                    if (msg_receive( $queueKeyAbeilleToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                        $message->topic = $msg->message['topic'];
                        $message->payload = $msg->message['payload'];
                        self::message($message);
                        $msg_type = NULL;
                        $msg = NULL;
                    }
                    if ( ($errorcodeMsg!=42) and ($errorcodeMsg!=0) ) {
                        log::add('Abeille', 'debug', 'deamon fct: msg_receive queueKeyAbeilleToAbeille issue: '.$errorcodeMsg);
                    }

                    if (msg_receive( $queueKeyParserToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                        $message->topic = $msg->message['topic'];
                        $message->payload = $msg->message['payload'];
                        self::message($message);
                        $msg_type = NULL;
                        $msg = NULL;
                    }
                    if ( ($errorcodeMsg!=42) and ($errorcodeMsg!=0) ) {
                        log::add('Abeille', 'debug', 'deamon fct: msg_receive queueKeyParserToAbeille issue: '.$errorcodeMsg);
                    }

                    if (msg_receive( $queueKeyTimerToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                        $message->topic = $msg->message['topic'];
                        $message->payload = $msg->message['payload'];
                        self::message($message);
                        $msg_type = NULL;
                        $msg = NULL;
                    }
                    if ( ($errorcodeMsg!=42) and ($errorcodeMsg!=0) ) {
                        log::add('Abeille', 'debug', 'deamon fct: msg_receive queueKeyTimerToAbeille issue: '.$errorcodeMsg);
                    }

                    if (msg_receive( $queueKeyCmdToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                        $message->topic = $msg->message['topic'];
                        $message->payload = $msg->message['payload'];
                        self::message($message);
                        $msg_type = NULL;
                        $msg = NULL;
                    }
                    if ( ($errorcodeMsg!=42) and ($errorcodeMsg!=0) ) {
                        log::add('Abeille', 'debug', 'deamon fct: msg_receive queueKeyCmdToAbeille issue: '.$errorcodeMsg);
                    }

                    if (msg_receive( $queueKeyXmlToAbeille, 0, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT, $errorcodeMsg)) {
                        $message->topic = $msg->message['topic'];
                        $message->payload = $msg->message['payload'];
                        self::message($message);
                        $msg_type = NULL;
                        $msg = NULL;
                    }
                    if ( ($errorcodeMsg!=42) and ($errorcodeMsg!=0) ) {
                        log::add('Abeille', 'debug', 'deamon fct: msg_receive queueKeyXmlToAbeille issue: '.$errorcodeMsg);
                    }

                    time_nanosleep( 0, 10000000 ); // 1/100s
                }

            } catch (Exception $e) {
                log::add('Abeille', 'error', 'Gestion erreur dans boucle try: '.$e->getMessage());
            }

        }

        public static function getParameters() {
            $return = array();
            $return['parametersCheck'] = 'ok';                  // Ces deux variables permettent d'indiquer la validité des données.
            $return['parametersCheck_message'] = "";

            //Most Fields are defined with default values
            $return['AbeilleAddress']       = config::byKey('AbeilleAddress', 'Abeille', '127.0.0.1');
            $return['AbeillePort']          = config::byKey('AbeillePort', 'Abeille', '1883');
            $return['AbeilleConId']         = config::byKey('AbeilleConId', 'Abeille', 'jeedom');
            $return['AbeilleUser']          = config::byKey('mqttUser', 'Abeille', 'jeedom');
            $return['AbeillePass']          = config::byKey('mqttPass', 'Abeille', 'jeedom');
            $return['AbeilleTopic']         = config::byKey('mqttTopic', 'Abeille', '#');
            $return['AbeilleSerialPort']    = config::byKey('AbeilleSerialPort', 'Abeille');
            $return['AbeilleSerialPort2']   = config::byKey('AbeilleSerialPort2', 'Abeille');
            $return['AbeilleSerialPort3']   = config::byKey('AbeilleSerialPort3', 'Abeille');
            $return['AbeilleSerialPort4']   = config::byKey('AbeilleSerialPort4', 'Abeille');
            $return['AbeilleSerialPort5']   = config::byKey('AbeilleSerialPort5', 'Abeille');
            $return['AbeilleQos']           = config::byKey('mqttQos', 'Abeille', '0');
            $return['AbeilleParentId']      = config::byKey('AbeilleParentId', 'Abeille', '1');
            $return['onlyTimer']            = config::byKey('onlyTimer', 'Abeille', 'N');
            $return['IpWifiZigate']         = config::byKey('IpWifiZigate', 'Abeille', '192.168.4.1');
            $return['IpWifiZigate2']        = config::byKey('IpWifiZigate2', 'Abeille', '192.168.4.2');
            $return['IpWifiZigate3']        = config::byKey('IpWifiZigate2', 'Abeille', '192.168.4.3');
            $return['IpWifiZigate4']        = config::byKey('IpWifiZigate2', 'Abeille', '192.168.4.4');
            $return['IpWifiZigate5']        = config::byKey('IpWifiZigate2', 'Abeille', '192.168.4.5');

            // Testons la validité de la configuration
            // Pas de port alors que je ne suis pas en mode timer only.
            if ( ($return['AbeilleSerialPort'] == 'none') && ($return['onlyTimer'] != "Y") ) {
                log::add('Abeille','debug','getParameters: serialPort n est pas défini: '.$return['AbeilleSerialPort']);
                message::add('Abeille','Warning: Le port série n est pas défini dans la configuration: '.$return['AbeilleSerialPort'],'','Abeille/Demon');
                $return['parametersCheck']="nok";
                $return['parametersCheck_message'] = __('Le port n est pas configuré', __FILE__);
                return $return;
            }

            // Port Zigate en Wifi et pas en mode Timer
            if ( ($return['AbeilleSerialPort'] == "/dev/zigate") && ($return['onlyTimer'] != "Y") ) {
                return $return;
            }

            // if ( $return['AbeilleSerialPort'] != "/dev/zigate" ) $return['AbeilleSerialPort'] = jeedom::getUsbMapping($return['AbeilleSerialPort']);

            // J ai un port et je ne suis pas en mode Timer
            if ( ($return['AbeilleSerialPort'] != 'none') && ($return['onlyTimer'] != "Y") ) {
                if (@!file_exists($return['AbeilleSerialPort'])) {
                    log::add('Abeille','debug','getParameters: Le port série choisi n existe pas: '.$return['AbeilleSerialPort']);
                    message::add('Abeille','Warning: Le port série choisi n existe pas: '.$return['AbeilleSerialPort'],'','Abeille/Demon');
                    $return['parametersCheck']="nok";
                    $return['parametersCheck_message'] = __('Le port série choisi n existe pas (zigate déconnectée ?)', __FILE__);
                    return $return;
                } else {
                    if (substr(decoct(fileperms($return['AbeilleSerialPort'])), -4) != "0777") {
                        exec(system::getCmdSudo().'chmod 777 '.$return['AbeilleSerialPort'].' > /dev/null 2>&1');
                    }
                }
            }

            return $return;
        }

        public static function postSave() {
            // log::add('Abeille', 'debug', 'deamon_postSave: IN');
            $cron = cron::byClassAndFunction('Abeille', 'deamon');
            if (is_object($cron) && !$cron->running()) {
                $cron->run();
            }
            // log::add('Abeille', 'debug', 'deamon_postSave: OUT');

        }

        public static function fetchShortFromIEEE($IEEE, $checkShort) {
            // Return:
            // 0 : Short Address is aligned with the one received
            // Short : Short Address is NOT aligned with the one received
            // -1 : Error Nothing found

            // $lookForIEEE = "000B57fffe490C2a";
            // $checkShort = "2006";
            // log::add('Abeille', 'debug', 'KIWI: start function fetchShortFromIEEE');
            $abeilles = Abeille::byType('Abeille');

            foreach ($abeilles as $abeille) {

                $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr');
                if (is_object($cmdIEEE)) {

                    if ($cmdIEEE->execCmd() == $IEEE) {

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
            }

            // log::add('Abeille', 'debug', 'KIWI: function fetchShortFromIEEE return -1');
            return -1;
        }

        public static function checkInclusionStatus($dest) {
            // Return: Inclusion status or -1 if error
            $ruche = Abeille::byLogicalId( $dest.'/Ruche', 'Abeille');

            if ( $ruche ) {
                // echo "Join status collection\n";
                $cmdJoinStatus = $ruche->getCmd('Info', 'permitJoin-Status');
                if ($cmdJoinStatus) {
                    return $cmdJoinStatus->execCmd();
                }
            }

            return -1;
        }

        public static function CmdAffichage( $affichageType, $Visibility = "na" ) {
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

            $parameters_info = self::getParameters();

            $convert = array(
                             "affichageNetwork"=>"Network",
                             "affichageTime"=>"Time",
                             "affichageCmdAdd"=>"additionalCommand"
                             );

            log::add('Abeille', 'debug', 'Entering CmdAffichage with affichageType: '.$affichageType.' - Visibility: '.$Visibility );
            echo 'Entering CmdAffichage with affichageType: '.$affichageType.' - Visibility: '.$Visibility ;

            switch ($Visibility) {
                case 'Y':
                    break;
                case 'N':
                    break;
                case 'toggle':
                    if ( $parameters_info[$affichageType] == 'Y' ) { $Visibility = 'N'; } else { $Visibility = 'Y'; }
                    break;
            }
            config::save( $affichageType, $Visibility,   'Abeille');

            $abeilles = self::byType('Abeille');
            foreach ($abeilles as $key=>$abeille) {
                $cmds = $abeille->getCmd();
                foreach ( $cmds as $keyCmd=>$cmd ){
                    if ( $cmd->getConfiguration("visibilityCategory")==$convert[$affichageType] ) {
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

            log::add('Abeille', 'debug', 'Leaving CmdAffichage' );
            return;
        }

        public static function message($message) {

            $parameters_info = self::getParameters();

            if (!preg_match("(Time|Link-Quality)", $message->topic)) {
                log::add('Abeille', 'debug', "fct message Topic: ->".$message->topic."<- Value ->".$message->payload."<-");
            }
            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // demande de creation de ruche au cas ou elle n'est pas deja crée....
            // La ruche est aussi un objet Abeille
            if ($message->topic == "CmdRuche/Ruche/CreateRuche") {
                log::add('Abeille', 'debug', "Topic: ->".$message->topic."<- Value ->".$message->payload."<-");
                self::createRuche($message);
                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // On ne prend en compte que les message Abeille|Ruche|CmdCreate/#/#
            // CmdCreate -> pour la creation des objets depuis la ruche par exemple pour tester les modeles
            if (!preg_match("(^Abeille|^Ruche|^CmdCreate|^ttyUSB|^zigate|^monitZigate)", $message->topic)) {
                // log::add('Abeille', 'debug', 'message: this is not a ' . $Filter . ' message: topic: ' . $message->topic . ' message: ' . $message->payload);
                return;
            }

            /*----------------------------------------------------------------------------------------------------------------------------------------------*/
            // Analyse du message recu
            // [CmdAbeille:Abeille] / Address / Cluster-Parameter
            // [CmdAbeille:Abeille] / $addr / $cmdId => $value
            // $nodeId = [CmdAbeille:Abeille] / $addr

            $topicArray = explode("/", $message->topic);
            if (sizeof($topicArray) != 3) return;

            $Filter = $topicArray[0];
            if ($Filter == "CmdCreate") $Filter = "Abeille";
            $dest = $Filter;

            $addr = $topicArray[1];
            if ( $addr == "0000" ) $addr = "Ruche";

            $cmdId = $topicArray[2];

            $nodeid = $Filter.'/'.$addr;

            $value = $message->payload;

            // Si le message est pour 0000 alors on change en Ruche


            // Le capteur de temperature rond V1 xiaomi envoie spontanement son nom: ->lumi.sensor_ht<- mais envoie ->lumi.sens<- sur un getName
            if ( $value=="lumi.sens" ) $value = "lumi.sensor_ht";
            if ( $value=="lumi.sensor_swit" ) $value = "lumi.sensor_switch.aq3";

            $type = 'topic';         // type = topic car pas json

            // Si cmd activate/desactivate NE based on IEEE Leaving/Joining
            if ( ($cmdId == "enable") || ($cmdId == "disable") ) {
                log::add('Abeille', 'debug', 'Entering enable/disable: '.$cmdId );
                $cmds = Cmd::byLogicalId('IEEE-Addr');
                foreach( $cmds as $cmd ) {
                    if ( $cmd->execCmd() == $value ) {
                        $abeille = $cmd->getEqLogic();
                        if ($cmdId == "enable") {
                            $abeille->setIsEnable(1);
                        }
                        else {
                            $abeille->setIsEnable(0);
                        }
                        $abeille->save();
                        $abeille->refresh();
                    }
                    echo "\n";
                }

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Cherche l objet par sa ref short Address et la commande
            $elogic = self::byLogicalId($nodeid, 'Abeille');
            if (is_object($elogic)) {
                $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), $cmdId);
            }
            $objetConnu = 0;

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si l objet n existe pas et je recoie son nom => je créé l objet.
            if ( !is_object($elogic)
                && (    preg_match("/^0000-[0-9A-F]*-*0005/", $cmdId)
                    ||  preg_match( "/^0000-[0-9A-F]*-*0010/", $cmdId )
                    ||  preg_match( "/^SimpleDesc-[0-9A-F]*-*DeviceDescription/", $cmdId )
                    )
                ) {

                log::add('Abeille', 'info', 'Recherche objet: '.$value.' dans les objets connus');
                //remove lumi. from name as all xiaomi devices have a lumi. name
                $trimmedValue = str_replace('lumi.', '', $value);

                //remove all space in names for easier filename handling
                $trimmedValue = str_replace(' ', '', $trimmedValue);

                // On enleve le / comme par exemple le nom des equipements Legrand
                $trimmedValue = str_replace('/', '', $trimmedValue);

                // On enleve les 0x00 comme par exemple le nom des equipements Legrand
                $trimmedValue = str_replace("\0", '', $trimmedValue);

                log::add('Abeille', 'debug', 'value:'.$value.' / trimmed value: ->'.$trimmedValue.'<-');
                $AbeilleObjetDefinition = Tools::getJSonConfigFilebyDevicesTemplate($trimmedValue);
                log::add('Abeille', 'debug', 'Template initial: '.json_encode($AbeilleObjetDefinition));

                // On recupere le EP
                // $EP = substr($cmdId,5,2);
                $EP = explode('-', $cmdId)[1];
                log::add('Abeille', 'debug', 'EP: '.$EP);
                $AbeilleObjetDefinitionJson = json_encode($AbeilleObjetDefinition);
                $AbeilleObjetDefinitionJson = str_replace('#EP#', $EP, $AbeilleObjetDefinitionJson);
                $AbeilleObjetDefinition = json_decode($AbeilleObjetDefinitionJson, true);
                log::add('Abeille', 'debug', 'Template mis a jour avec EP: '.json_encode($AbeilleObjetDefinition));

                //Due to various kind of naming of devices, json object is either named as value or $trimmedvalue. We need to know which one to use.
                if (array_key_exists($value, $AbeilleObjetDefinition) || array_key_exists( $trimmedValue, $AbeilleObjetDefinition )) {
                    $objetConnu = 1;
                    $jsonName = array_key_exists($value, $AbeilleObjetDefinition) ? $value : $trimmedValue;
                    log::add( 'Abeille', 'info', 'objet: '.$value.' recherché comme '.$trimmedValue.' peut etre cree car je connais ce type d objet.' );
                } else {
                    log::add( 'Abeille', 'info', 'objet: '.$value.' recherché comme '.$trimmedValue.' ne peut pas etre creer car je ne connais pas ce type d objet.' );
                    // log::add('Abeille', 'debug', 'objet: '.json_encode($AbeilleObjetDefinition));
                    return;
                }

                /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
                // Creation de l objet Abeille
                // Exemple pour les objets créés par les commandes Ruche, e.g. Timer
                if (strlen($addr) != 4) {
                    $index = rand(1000, 9999);
                    $addr = $addr."-".$index;
                    $nodeid = $nodeid."-".$index;
                }

                message::add( "Abeille", "Création d un nouvel objet Abeille (".$nodeid.") en cours, dans quelques secondes rafraîchissez votre dashboard pour le voir.",'','Abeille/Abeille' );
                $elogic = new Abeille();
                //id
                if ($objetConnu) {
                    // $name = "Abeille-".$addr;
                    // $name = "Abeille-" . $addr . '-' . $jsonName;
                    $name = $Filter."-".$addr;
                } else {
                    $name = "Abeille-".$addr.'-'.$jsonName."-Type d objet inconnu (!JSON)";
                }
                $elogic->setName($name);
                $elogic->setLogicalId($nodeid);
                $elogic->setObject_id($parameters_info['AbeilleParentId']);
                $elogic->setEqType_name('Abeille');

                $objetDefSpecific = $AbeilleObjetDefinition[$jsonName];

                $objetConfiguration = $objetDefSpecific["configuration"];
                $elogic->setConfiguration('topic', $nodeid);
                $elogic->setConfiguration('type', $type);
                $elogic->setConfiguration('uniqId', $objetConfiguration["uniqId"]);
                $elogic->setConfiguration('icone', $objetConfiguration["icone"]);
                $elogic->setConfiguration('mainEP', $objetConfiguration["mainEP"]);
                $elogic->setConfiguration('lastCommunicationTimeOut', $objetConfiguration["lastCommunicationTimeOut"]);
                $elogic->setConfiguration('type', $type);
                if (isset($objetConfiguration['battery_type'])) {
                    $elogic->setConfiguration('battery_type', $objetConfiguration['battery_type']);
                }
                if (isset($objetConfiguration['Groupe'])) {
                    $elogic->setConfiguration('Groupe', $objetConfiguration['Groupe']);
                }
                if (isset($objetConfiguration['protocol'])) {
                    $elogic->setConfiguration('protocol', $objetConfiguration['protocol']);
                }
                if (isset($objetConfiguration['poll'])) {
                    $elogic->setConfiguration('poll', $objetConfiguration['poll']);
                }
                $elogic->setIsVisible("1");


                // eqReal_id
                $elogic->setIsEnable("1");
                // status
                // timeout
                $elogic->setTimeout($objetDefSpecific["timeout"]);

                $elogic->setCategory( array_keys($objetDefSpecific["Categorie"])[0], $objetDefSpecific["Categorie"][array_keys($objetDefSpecific["Categorie"])[0]] );
                // display
                // order
                // comment

                //log::add('Abeille', 'info', 'Saving device ' . $nodeid);
                //$elogic->save();
                $elogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));
                $elogic->save();


                // Creation des commandes pour l objet Abeille juste créé.
                if ($GLOBALS['debugKIWI']) {
                    echo "On va creer les commandes.\n";
                    print_r($objetDefSpecific['Commandes']);
                }

                foreach ($objetDefSpecific['Commandes'] as $cmd => $cmdValueDefaut) {
                    log::add( 'Abeille', 'info', 'Creation de la commande: '.$cmd.' suivant model de l objet pour l objet: '.$name );
                             // 'Creation de la commande: ' . $nodeid . '/' . $cmd . ' suivant model de l objet pour l objet: ' . $name

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
                            log::add( 'Abeille',  'debug',  'Define cmd info pour cmd action: '.$elogic->getName()." - ".$cmdValueDefaut["value"]  );

                            $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName( "Abeille", $elogic->getName(), $cmdValueDefaut["value"] );
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
                    // On conserve l info du template pour la visibility
                    $cmdlogic->setConfiguration( "visibiltyTemplate", $cmdValueDefaut["isVisible"]);

                    // template
                    $cmdlogic->setTemplate('dashboard', $cmdValueDefaut["template"]);
                    $cmdlogic->setTemplate('mobile', $cmdValueDefaut["template"]);
                    $cmdlogic->setIsHistorized($cmdValueDefaut["isHistorized"]);
                    $cmdlogic->setType($cmdValueDefaut["Type"]);
                    $cmdlogic->setSubType($cmdValueDefaut["subType"]);
                    $cmdlogic->setGeneric_type($cmdValueDefaut["generic_type"]);
                    // unite
                    if (isset($cmdValueDefaut["unite"])) {
                        $cmdlogic->setUnite($cmdValueDefaut["unite"]);
                    }

                    if (isset($cmdValueDefaut["invertBinary"])) {
                        $cmdlogic->setDisplay('invertBinary', $cmdValueDefaut["invertBinary"]);
                    }
                    // La boucle est pour info et pour action
                    // isVisible
                    $parameters_info = self::getParameters();
                    $isVisible = $cmdValueDefaut["isVisible"];

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
            // Si l objet n existe pas et je recoie une commande IEEE => je vais chercher l objet avec cette IEEE
            // e.g. Short address change (Si l adresse a changé, on ne peut pas trouver l objet par son nodeId)
            if (!is_object( $elogic ) && ($cmdId == "IEEE-Addr") ) {
                $ShortFound = Abeille::fetchShortFromIEEE($value, $addr);
                if ((strlen($ShortFound) == 4) && ($addr != "Ruche")) {

                    $elogic = self::byLogicalId( $dest."/".$ShortFound, 'Abeille');

                        log::add( 'Abeille', 'debug', "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - " .$elogic->getName().", on fait la mise a jour automatique" );
                        // Comme c est automatique des que le retour d experience sera suffisant, on n alerte pas l utilisateur. Il n a pas besoin de savoir
                        message::add( "Abeille",   "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - " .$elogic->getName().", on fait la mise a jour automatique", '', 'Abeille/Abeille' );

                        // Si on trouve l adresse dans le nom, on remplace par la nouvelle adresse
                        log::add( 'Abeille', 'debug', "IEEE-Addr; Ancien nom: ".$elogic->getName().", nouveau nom: ".str_replace( $ShortFound, $addr, $elogic->getName()   ) );
                        $elogic->setName(str_replace($ShortFound, $addr, $elogic->getName()));

                        $elogic->setLogicalId( $dest."/".$addr );

                        $elogic->setConfiguration('topic', $dest."/".$addr);

                        $elogic->save();

                        // Il faut aussi mettre a jour la commande short address
                    Abeille::publishMosquitto( queueKeyAbeilleToAbeille, priorityInterrogation, $dest."/".$addr."/Short-Addr", $addr );

                }

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si l objet n existe pas et je recoie une commande => je drop la cmd
            // e.g. un Equipement envoie des infos, mais l objet n existe pas dans Jeedom
            if (!is_object($elogic)) {
                log::add( 'Abeille', 'debug', "L equipement $addr n existe pas dans Jeedom, je ne process pas la commande." );

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si l objet exist et on recoie une IEEE
            // e.g. Un NE renvoie son annonce
            if (is_object($elogic) && ($cmdId == "IEEE-Addr")) {

                // Je rejete les valeur null (arrive avec les equipement xiaomi qui envoie leur nom spontanement alors que l IEEE n est pas recue.
                if (strlen($value)<2) {
                    log::add( 'Abeille', 'debug', 'IEEE-Addr; =>'.$value.'<= ; IEEE non valable pour un equipement, valeur rejetée: '.$addr.": IEEE =>".$value."<=" );
                    return;
                }

                // ffffffffffffffff remonte avec les mesures LQI si nouveau equipements.
                if ($value == "ffffffffffffffff") {
                    log::add( 'Abeille', 'debug', 'IEEE-Addr; =>'.$value.'<= ; IEEE non valable pour un equipement, valeur rejetée: '.$addr.": IEEE =>".$value."<=" );
                    return;
                }

                // Update IEEE cmd
                if ( !is_object($cmdlogic) ){
                    log::add('Abeille', 'debug', 'IEEE-Addr commande n existe pas' );
                    return;
                }

                $IEEE = $cmdlogic->execCmd();
                if ($IEEE == $value) {
                    log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';Ok pas de changement de l adresse IEEE, je ne fais rien.' );
                    return;
                }

                // Je ne sais pas pourquoi des fois on recoit des IEEE null
                if ($value == "0000000000000000") {
                    log::add('Abeille', 'debug', 'IEEE-Addr;'.$value.';IEEE recue est null, je ne fais rien.');
                    return;
                }

                // Je ne fais pas d alerte dans le cas ou IEEE est null car pas encore recupere du réseau.
                if (strlen($IEEE)>2) {
                    log::add( 'Abeille', 'debug', 'IEEE-Addr;'.$value.';Alerte changement de l adresse IEEE pour un equipement !!! '.$addr.": ".$IEEE." =>".$value."<=" );
                    message::add( "Abeille", "Alerte changement de l adresse IEEE pour un equipement !!! ( $addr : $IEEE =>$value<= )", '', 'Abeille/Abeille' );
                }

                $elogic->checkAndUpdateCmd($cmdlogic, $value);

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si equipement et cmd existe alors on met la valeur a jour
            if (is_object($elogic) && is_object($cmdlogic)) {
                /* Traitement particulier pour les batteries */
                if ($cmdId == "Batterie-Volt") {
                    /* Volt en milli V. Max a 3,1V Min a 2,7V, stockage en % batterie */
                    $elogic->setStatus('battery', $value );
                    $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                }
                if ($cmdId == "Batterie-Pourcent") {
                    $elogic->setStatus('battery', $value);
                    $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                }
                if ($cmdId == "0001-01-0021") {
                    /* en % batterie example Ikea Remote */
                    $elogic->setStatus('battery', $value);
                    $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                }

                /*
                if ( ($cmdId == "Zigate-8000") && (substr($value,0,2)!="00") ) {
                    message::add( "Abeille", "La Zigate semble ne pas pouvoir traiter toutes les demandes.",'KiwiHC16: Investigations en cours pour mieux traiter ce sujet.','Abeille/Abeille');
                }
                */

                /* Traitement particulier pour la remontée de nom qui est utilisé pour les ping des routeurs */
                // if (($cmdId == "0000-0005") || ($cmdId == "0000-0010")) {
                if (preg_match("/^0000-[0-9A-F]*-*0005/", $cmdId) || preg_match("/^0000-[0-9A-F]*-*0010/", $cmdId)) {
                    log::add('Abeille', 'debug', 'Update ONLINE Status');
                    $cmdlogicOnline = AbeilleCmd::byEqLogicIdAndLogicalId($elogic->getId(), 'online');
                    $elogic->checkAndUpdateCmd($cmdlogicOnline, 1);
                }

                // Traitement particulier pour rejeter certaines valeurs
                // exemple: le Xiaomi Wall Switch 2 Bouton envoie un On et un Off dans le même message donc Abeille recoit un ON/OFF consecutif et
                // ne sais pas vraiment le gérer donc ici on rejete le Off et on met un retour d'etat dans la commande Jeedom
                if ($cmdlogic->getConfiguration('AbeilleRejectValue') == $value) {
                    log::add('Abeille', 'debug', 'Rejet de la valeur: '.$value);

                    return;
                }

                $elogic->checkAndUpdateCmd($cmdlogic, $value);

                return;
            }

            if (is_object($elogic) && !is_object($cmdlogic)) {
                log::add('Abeille', 'debug', "Objet existe mais pas la commande, je passe.");
                return;
            }
            log::add('Abeille', 'debug', "Tres bizarre, Message non traité, il manque probablement du code.");

            return; // function message
        }

        public static function publishMosquitto( $queueId, $priority, $topic, $payload ) {
            $parameters_info = Abeille::getParameters();
            log::add('Abeille', 'debug', 'Envoi du message topic: '.$topic.' payload: '.$payload.' vers '. $queueId);

            $queue = msg_get_queue( $queueId );

            $msgAbeille = new MsgAbeille;

            $msgAbeille->message['topic'] = $topic;
            $msgAbeille->message['payload'] = $payload;

            if (msg_send( $queue, $priority, $msgAbeille, true, false)) {
                log::add('Abeille', 'debug', 'Msg sent: '.json_encode($msgAbeille).' on queue: '.$queueId);
            }
            else {
                log::add('Abeille', 'debug', 'Could not send Msg: '.json_encode($msgAbeille).' on queue: '.$queueId);
            }
        }

        public function createRuche($message = null) {

            $dest = $message->payload;
            $elogic = self::byLogicalId( $dest."/Ruche", 'Abeille');
            if (is_object($elogic)) {
                log::add('Abeille', 'debug', 'message: createRuche: objet: '.$elogic->getLogicalId().' existe deja');
                return;
            }
            // Creation de la ruche
            log::add('Abeille', 'info', 'objet ruche : creation par model de '.$dest."/Ruche" );


            /*
            $cmdId = end($topicArray);
            $key = count($topicArray) - 1;
            unset($topicArray[$key]);
            $addr = end($topicArray);
            // nodeid est le topic sans le dernier champ
            $nodeid = implode($topicArray, '/');
            */

            message::add( "Abeille", "Création de l objet Ruche en cours, dans quelques secondes rafraichissez votre dashboard pour le voir.", '', 'Abeille/Abeille' );
            $parameters_info = self::getParameters();
            $elogic = new Abeille();
            //id
            $elogic->setName("Ruche-".$dest);
            $elogic->setLogicalId($dest."/Ruche");
            if ($parameters_info['AbeilleParentId'] > 0) {
                $elogic->setObject_id($parameters_info['AbeilleParentId']);
            } else {
                $elogic->setObject_id(jeeObject::rootObject()->getId());
            }
            $elogic->setEqType_name('Abeille');
            $elogic->setConfiguration('topic', $dest."/Ruche");
            $elogic->setConfiguration('type', 'topic');
            $elogic->setConfiguration('lastCommunicationTimeOut', '-1');
            $elogic->setIsVisible("0");
            $elogic->setConfiguration('icone', "Ruche");
            // eqReal_id
            $elogic->setIsEnable("1");
            // status
            $elogic->setTimeout(60); // timeout en minutes
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
                $AbeilleObjetDefinition = Tools::getJSonConfigFilebyDevices(
                                                                            Tools::getTrimmedValueForJsonFiles($item),
                                                                            'Abeille'
                                                                            );
                // Creation des commandes au niveau de la ruche pour tester la creations des objets (Boutons par defaut pas visibles).
                foreach ($AbeilleObjetDefinition as $objetId => $objetType) {
                    $rucheCommandList[$objetId] = array(
                                                        "name" => $objetId,
                                                        "order" => $i++,
                                                        "isVisible" => "0",
                                                        "isHistorized" => "0",
                                                        "Type" => "action",
                                                        "subType" => "other",
                                                        "configuration" => array("topic" => "CmdCreate/".$objetId."/0000-0005", "request" => $objetId, "visibilityCategory" => "additionalCommand", "visibiltyTemplate"=>"0" ),
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

    class AbeilleCmd extends cmd {
        public function execute($_options = null) {
            log::add('Abeille', 'Debug', 'execute ->'.$this->getType().'<- function with options ->'.json_encode($_options).'<-');
            switch ($this->getType()) {
                case 'action' :
                    //
                    /* ------------------------------ */
                    // Topic est l arborescence MQTT: La fonction Zigate

                    $Abeilles = new Abeille();

                    $NE_Id = $this->getEqLogic_id();
                    $NE = $Abeilles->byId($NE_Id);

                    if (strpos("_".$this->getConfiguration('topic'), "CmdAbeille") == 1) {
                        $topic = $this->getConfiguration('topic');
                    } else {
                        if (strpos("_".$this->getConfiguration('topic'), "CmdTimer") == 1) {
                            $topic = $this->getConfiguration('topic');
                        } else {
                            if (strpos("_".$this->getConfiguration('topic'), "CmdCreate") == 1) {
                                $topic = $this->getConfiguration('topic');
                            } else {
                                $topic = "Cmd".$NE->getConfiguration('topic')."/".$this->getConfiguration('topic');
                                // $topic = $this->getConfiguration('topic');

                            }
                        }
                    }
                    log::add('Abeille', 'Debug', 'topic: '.$topic);

                    $topicArray = explode("/", $topic );
                    $dest = substr($topicArray[0],3);


                    /* ------------------------------ */
                    // Je fais les remplacement dans la commande (ex: addGroup pour telecommande Ikea 5 btn)
                    if ( strpos( $topic,"#addrGroup#" )>0 ) {
                        $topic = str_replace( "#addrGroup#", $NE->getConfiguration("Groupe"), $topic );
                    }

                    // -------------------------------------------------------------------------
                    // Process Request
                    $request = $this->getConfiguration('request', '1');
                    // request: c'est le payload dans la page de configuration pour une commande
                    // C est les parametres de la commande pour la zigate
                    log::add('Abeille', 'Debug', 'request: '.$request);

                    /* ------------------------------ */
                    // Je fais les remplacement dans la commande (ex: addGroup pour telecommande Ikea 5 btn)
                    if ( strpos( $request,"#addrGroup#" )>0 ) {
                        $request = str_replace( "#addrGroup#", $NE->getConfiguration("Groupe"), $request );
                    }

                    /* ------------------------------ */
                    // Je fais les remplacement pour le timer
                    if ( strpos( $request,"#TimerActionStart#" )>0 ) {
                        $request = str_replace( "#TimerActionStart#", $NE->getConfiguration("TimerActionStart"), $request );
                    }

                    if ( strpos( $request,"#TimerDuration#" )>0 ) {
                        if ( $NE->getConfiguration("TimerDuration") ) {
                            $request = str_replace( "#TimerDuration#", $NE->getConfiguration("TimerDuration"), $request );
                        }
                        else {
                            $request = str_replace( "#TimerDuration#", "60", $request );
                        }
                    }

                    if ( strpos( $request,"#TimerRampUp#" )>0 ) {
                        if ( $NE->getConfiguration("TimerRampUp") ) {
                            $request = str_replace( "#TimerRampUp#", $NE->getConfiguration("TimerRampUp"), $request );
                        }
                        else {
                            $request = str_replace( "#TimerRampUp#", "1", $request  );
                        }
                    }

                    if ( strpos( $request,"#TimerRampDown#" )>0 ) {
                        if ( $NE->getConfiguration("TimerRampDown") ) {
                            $request = str_replace( "#TimerRampDown#", $NE->getConfiguration("TimerRampDown"), $request );
                        }
                        else {
                            $request = str_replace( "#TimerRampDown#", "1", $request );
                        }
                    }

                    if ( strpos( $request,"#TimerActionRamp#" )>0 ) {
                        $request = str_replace( "#TimerActionRamp#", $NE->getConfiguration("TimerActionRamp"), $request );
                    }

                    if ( strpos( $request,"#TimerActionStop#" )>0 ) {
                        $request = str_replace( "#TimerActionStop#", $NE->getConfiguration("TimerActionStop"), $request );
                    }

                    if ( strpos( $request,"#TimerActionCancel#" )>0 ) {
                        $request = str_replace( "#TimerActionCancel#", $NE->getConfiguration("TimerActionCancel"), $request );
                    }

                    /* ------------------------------ */
                    // Je fais les remplacement dans les parametres
                    if (strpos($request, '#addrIEEE#') > 0) {
                        $ruche = new Abeille();
                        $commandIEEE = new AbeilleCmd();

                        // Recupere IEEE de la Ruche/ZiGate
                        $rucheId = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId();
                        log::add('Abeille', 'debug', 'Id pour abeille Ruche: '.$rucheId);

                        $rucheIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
                        log::add('Abeille', 'debug', 'IEEE pour  Ruche: '.$rucheIEEE);

                        $currentCommandId = $this->getId();
                        $currentObjectId = $this->getEqLogic_id();
                        log::add('Abeille', 'debug', 'Id pour current abeille: '.$currentObjectId);

                        // ne semble pas rendre la main si l'objet n'a pas de champ "IEEE-Addr"
                        $commandIEEE = $commandIEEE->byEqLogicIdAndLogicalId($currentObjectId, 'IEEE-Addr')->execCmd();

                        // print_r( $command->execCmd() );
                        log::add('Abeille', 'debug', 'IEEE pour current abeille: '.$commandIEEE);

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

                    log::add('Abeille', 'Debug', 'topic: '.$topic.' request: '.$request);

                    if ( strpos( $topic, "CmdTimer" ) === 0 ) {
                        $queueKeyAbeilleToTimer = msg_get_queue(queueKeyAbeilleToTimer);
                        if (msg_send( $queueKeyAbeilleToTimer, 1, $msgAbeille, true, false)) {
                            log::add('Abeille', 'debug', '(CmdTimer) Msg sent: '.json_encode($msgAbeille));
                        }
                        else {
                            log::add('Abeille', 'debug', '(CmdTimer) Could not send Msg');
                        }
                    }
                    else if ( strpos( $topic, "CmdCreate" ) === 0 ) {
                        $queueKeyAbeilleToAbeille = msg_get_queue(queueKeyAbeilleToAbeille);
                        if (msg_send( $queueKeyAbeilleToAbeille, 1, $msgAbeille, true, false)) {
                            log::add('Abeille', 'debug', '(CmdCreate) Msg sent: '.json_encode($msgAbeille));
                        }
                        else {
                            log::add('Abeille', 'debug', '(CmdCreate) Could not send Msg');
                        }
                    }
                    else {
                        $queueKeyAbeilleToCmd   = msg_get_queue(queueKeyAbeilleToCmd);
                        if (msg_send( $queueKeyAbeilleToCmd, priorityUserCmd, $msgAbeille, true, false)) {
                            log::add('Abeille', 'debug', '(All) Msg sent: '.json_encode($msgAbeille));
                        }
                        else {
                            log::add('Abeille', 'debug', '(All) Could not send Msg');
                        }
                    }
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
        $debugKIWI = $argv[1];
    } else {
        $debugKIWI = 0;
    }

    if ($debugKIWI != 0) {
        echo "Debut Abeille.class.php test mode\n";
        $message = new stdClass();

        switch ($debugKIWI) {

                // Creation des objets sur la base des modeles pour verifier la bonne creation dans Abeille
            case "1":
                $items = Tools::getDeviceNameFromJson('Abeille');
                $parameters_info = Abeille::getParameters();
                //problem icon creation
                foreach ($items as $item) {
                    $name = str_replace(' ', '.', $item);
                    $message->topic = substr($parameters_info['AbeilleTopic'],0,-1)."Abeille/$name/0000-0005";
                    $message->payload = $item;
                    Abeille::message($message);
                    sleep(2);
                }
                break;

                // Demande la creation de la ruche
            case "2":
                $message->topic = "CmdRuche/Ruche/CreateRuche";
                $message->payload = "ttyUSB0";
                Abeille::message($message);
                break;

                // Verifie qu on recupere les IEEE pour les remplacer dans les commandes
            case "3":
                $ruche = new Abeille();
                $commandIEEE = new AbeilleCmd();

                // Recupere IEEE de la Ruche/ZiGate
                $rucheId = $ruche->byLogicalId('Abeille/Ruche', 'Abeille')->getId();
                echo 'Id pour abeille Ruche: '.$rucheId."\n";

                $rucheIEEE = $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();
                echo 'IEEE pour  Ruche: '.$rucheIEEE."\n";

                // $currentCommandId = $this->getId();
                // $currentObjectId = $this->getEqLogic_id();
                $currentObjectId = 284;
                echo 'Id pour current abeille: '.$currentObjectId."\n";

                $commandIEEE = $commandIEEE->byEqLogicIdAndLogicalId($currentObjectId, 'IEEE-Addr')->execCmd();
                // print_r( $command->execCmd() );
                echo 'IEEE pour current abeille: '.$commandIEEE."\n";

                // $elogic->byLogicalId( 'Abeille/b528', 'Abeille' );
                // print_r( $objet->byLogicalId( 'Abeille/b528', 'Abeille' )->getId() );
                // echo "\n";
                // print_r( $command->byEqLogicIdAndLogicalId( $objetId, "IEEE-Addr" )->getLastValue() );

                $request = str_replace('#addrIEEE#', "'".$commandIEEE."'", $request);
                $request = str_replace('#ZiGateIEEE#', "'".$rucheIEEE."'", $request);

                break;

                // Testing Dependancy info
            case "4":
                $ruche = new Abeille();

                // echo "Testing Dependancy info\n";
                // var_dump( $ruche::getDependencyInfo() );

                echo "Testing deamon info\n";
                var_dump( $ruche::deamon_info() );
                break;


                // Cherche l objet qui a une IEEE specifique
            case "5":
                // Info ampoue T7
                $lookForIEEE = "000B57fffe490C2a";
                $checkShort = "2096";

                if (0) {
                    $abeilles = Abeille::byType('Abeille');
                    // var_dump( $abeilles );

                    foreach ($abeilles as $num => $abeille) {
                        //var_dump( $abeille );
                        // var_dump( $abeille->getCmd('Info', 'IEEE-Addr' ) );
                        $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr');
                        if ($cmdIEEE) {
                            // var_dump( $cmd );

                            if ($cmdIEEE->execCmd() == $lookFor) {
                                echo "Found it\n";
                                $cmdShort = $abeille->getCmd('Info', 'Short-Addr');
                                if ($cmdShort) {
                                    echo $cmdShort->execCmd()." ";
                                    if ($cmdShort->execCmd() == $check) {
                                        echo "Success ";

                                        return 1;
                                    } else {
                                        echo "Pas success du tout ";

                                        return 0;
                                    }
                                }
                            }
                            echo $cmdIEEE->execCmd()."\n-----\n";
                        }
                    }

                    return $cmd;
                } else {
                    Abeille::fetchShortFromIEEE($lookForIEEE, $checkShort);
                }

                break;

                // Ask Model Identifier to all equipement without battery info, those equipement should be awake
            case "6":
                log::add('Abeille', 'debug', 'Ping routers to check Online status');
                $eqLogics = Abeille::byType('Abeille');
                foreach ($eqLogics as $eqLogic) {
                    // echo "Battery: ".$collectBattery = $eqLogic->getStatus("battery")."\n";
                    // echo "Battery: ".$collectBattery = $eqLogic->getConfiguration("battery_type")." - ";
                    if (strlen($eqLogic->getConfiguration("battery_type")) == 0) {
                        $topicArray = explode("/", $eqLogic->getLogicalId());
                        $addr = $topicArray[1];
                        if (strlen($addr) == 4) {
                            echo "Short: ".$topicArray[1];
                            Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityUserCmd, "CmdAbeille/".$addr."/Annonce", "Default" );
                        }

                    }
                    echo "\n";
                }
                break;

                // Check Inclusion status
            case "7":
                echo "Check inclusion status\n";
                echo Abeille::checkInclusionStatus(Abeille::getParameters()['AbeilleSerialPort']);

                break;

                // Check cleanup
            case "8":
                echo "Check cleanup\n";
                Abeille::deamon_start_cleanup();
                break;

                // Affichage Commandes
            case "9":
                // echo "Test Affichage\n";
                //  toggleAffichageNetwork
                //  toggleAffichageTime
                //  toggleAffichageAdditionalCommand

                // Abeille::CmdAffichage( "affichageNetwork", "toggle" );
                Abeille::CmdAffichage( "affichageNetwork", "N" );
                Abeille::CmdAffichage( "affichageTime", "N" );
                Abeille::CmdAffichage( "affichageCmdAdd", "N" );

                break;

                // Test jeeObject::all() function
            case "10":
                // $object = jeeObject::rootObject()->getId();
                $object = jeeObject::all();
                print_r($object);
                break;

            case "11":
                Abeille::syncconfAbeille(false);
                break;

            case "12":
                $cmds = Cmd::byLogicalId('IEEE-Addr');
                foreach( $cmds as $cmd ) {
                    if ( $cmd->execCmd() == '00158d0001a66ca3' ) {
                        $abeille = $cmd->getEqLogic();
                        $abeille->setIsEnable(0);
                        $abeille->save();
                        $abeille->refresh();
                    }
                    echo "\n";
                }
                break;

            case "13":
                $message->topic = "Abeille/Ruche/Time-Time";
                $message->payload = "2018-11-28 12:19:03";
                Abeille::message($message);
                break;

            case "14":
                Abeille::updateConfigAbeille();
                break;

            case "15":
                $abeilles = Abeille::byType('Abeille');
                foreach ( $abeilles as $abeilleId=>$abeille) {
                    // var_dump($abeille->getCmd());
                    // var_dump($abeille);
                    echo $abeille->getId();
                    return;
                }
                break;

            case "16":
                Abeille::cron15();
                break;

            case "17":
                // On verifie le cron
                var_dump( cron::byClassAndFunction('Abeille', 'deamon') );
                echo "Is Object: ".is_object(cron::byClassAndFunction('Abeille', 'deamon'))."\n";
                echo "running: ".cron::byClassAndFunction('Abeille', 'deamon')->running()."\n";
                break;

            case "18":
                Abeille::deamon();
                break;

            case "19":
                Abeille::deamon_start();
                break;

            case "20":
                Abeille::cronDaily();
                break;

            case "21":
                var_dump( Abeille::getParameters() );
                break;

            case "22":
                echo "Debut case 22\n";
                $from = "Abeille";
                $to = "monitZigate1";
                $abeilles = Abeille::byType('Abeille');
                foreach ( $abeilles as $abeilleId=>$abeille) {
                    // var_dump($abeille->getCmd());
                    // var_dump($abeille);
                    echo $abeille->getId()."-> ".$abeille->getLogicalId()."\n";
                    if ( !preg_match("(^".$from."/T)", $abeille->getLogicalId() )) {
                        echo "to process: ".str_replace($from,$to,$abeille->getLogicalId())."\n";
                        $abeille->setLogicalId( str_replace($from,$to,$abeille->getLogicalId()) );

                        echo "Conf: ".$abeille->getConfiguration('topic')."\n";
                        $abeille->setConfiguration('topic', str_replace( $from, $to, $abeille->getConfiguration('topic') ) );
                        echo "Conf: ".$abeille->getConfiguration('topic')."\n";

                        $abeille->save();
                    }
                    //return;
                }
                echo "\n";
                break;

        } // switch

        echo "Fin Abeille.class.php test mode\n";
    }
