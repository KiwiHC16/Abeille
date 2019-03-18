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
    include_once(dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php');


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

        public static function syncconfAbeille($_background = true) {
            if ($GLOBALS['debugBEN']) echo "syncconfAbeille start\n";
            log::add('Abeille', 'debug', 'Starting syncconfAbeille');
            log::remove('Abeille_syncconf');
            log::add('Abeille_syncconf', 'info', 'syncconfAbeille Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugBEN']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_syncconf', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_syncconf', 'info', 'syncconfAbeille End');
            if ($GLOBALS['debugBEN']) echo "syncconfAbeille end\n";
        }

        public static function installGPIO($_background = true) {
            if ($GLOBALS['debugBEN']) echo "installGPIO start\n";
            log::add('Abeille', 'debug', 'Starting installGPIO');
            log::remove('Abeille_installGPIO');
            log::add('Abeille_installGPIO', 'info', 'installGPIO Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/installGPIO.sh >> ' . log::getPathToLog('Abeille_installGPIO') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugBEN']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_installGPIO', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_installGPIO', 'info', 'installGPIO End');
            if ($GLOBALS['debugBEN']) echo "installGPIO end\n";
        }

        public static function updateFirmwarePiZiGate($_background = true) {
            if ($GLOBALS['debugBEN']) echo "updateFirmwarePiZiGate start\n";
            log::add('Abeille', 'debug', 'Starting updateFirmwarePiZiGate');
            log::remove('Abeille_updateFirmwarePiZiGate');
            log::add('Abeille_updateFirmwarePiZiGate', 'info', 'updateFirmwarePiZiGate Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/updateFrimware.sh >> ' . log::getPathToLog('Abeille_updateFirmwarePiZiGate') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugBEN']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_updateFirmwarePiZiGate', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_updateFirmwarePiZiGate', 'info', 'updateFirmwarePiZiGate End');
            if ($GLOBALS['debugBEN']) echo "updateFirmwarePiZiGate end\n";
        }

        public static function resetPiZiGate($_background = true) {
            if ($GLOBALS['debugBEN']) echo "resetPiZiGate start\n";
            log::add('Abeille', 'debug', 'Starting resetPiZiGate');
            log::remove('Abeille_resetPiZiGate');
            log::add('Abeille_resetPiZiGate', 'info', 'resetPiZiGate Start');
            // $cmd = system::getCmdSudo() .' /bin/bash ' . dirname(__FILE__) . '/../../resources/syncconf.sh >> ' . log::getPathToLog('Abeille_syncconf') . ' 2>&1';
            $cmd = '/bin/bash ' . dirname(__FILE__) . '/../../resources/resetPiZigate.sh >> ' . log::getPathToLog('Abeille_resetPiZiGate') . ' 2>&1';
            if ($_background) {
                $cmd .= ' &';
            }
            if ($GLOBALS['debugBEN']) echo "cmd: ".$cmd . "\n";
            log::add('Abeille_resetPiZiGate', 'info', $cmd);
            shell_exec($cmd);
            log::add('Abeille_resetPiZiGate', 'info', 'resetPiZiGate End');
            if ($GLOBALS['debugBEN']) echo "resetPiZiGate end\n";
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
            if ($GLOBALS['debugBEN']) echo "updateConfigAbeille start\n";
            log::add('Abeille', 'debug', 'Starting updateConfigAbeille');
            // log::remove('updateConfigAbeille');
            // log::add('Abeille_test', 'info', 'updateConfigAbeille Start');
            $fp = fopen(log::getPathToLog('Abeille_updateConfig'), 'w');
            fwrite($fp, "Starting updateConfigAbeille\n");
            if (isset($abeilleId)) {
                fwrite($fp, "Device Id: ".$abeilleId."\n");
            }

            // $cmds = cmd::all();
            // foreach ( $cmds as $cmdId=>$cmd ) {
            $abeilles = Abeille::byType('Abeille');
            foreach ( $abeilles as $abeilleNumber=>$abeille ) {
                if ( (($abeilleIdFilter==false) || ($abeilleIdFilter==$abeille->getId())) && ($abeille->getName()!='Ruche') ) { // $cmd->getEqLogic_id()
                    fwrite($fp, "Device Id en cours: ".$abeille->getId()."\n");
                    // $templateName = $cmd->execCmd();
                    // $templateName = str_replace("lumi.","",$templateName);
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

                    if ( !isset($templateName)) {
                        $iconeToTemplate = array (
                                                  "CLA60RGBWOSRAM"=>"CLA60RGBWOSRAM",
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
                                                  // "XiaomiButtonSW861"=>"remote.b286acn01",
                                                  "RWL021"=>"RWL021",
                                                  // "XiaomiButtonSW861"=>"sensor_86sw1",
                                                  // "XiaomiButtonSW861"=>"sensor_86sw2",
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
                                                  "ZLO-OccupancySensor"=>"ZLO-OccupancySensor"
                                                  );
                        $templateName = $iconeToTemplate[$abeille->getConfiguration( 'icone' )];

                    }

                    $templateName = str_replace("lumi.","",$templateName);
                    $templateName = str_replace(" ","",$templateName);

                    // $abeille = abeille::byId( $cmd->getEqLogic_id() );
                    $template = tools::getJSonConfigFilebyDevicesTemplate( $templateName );
                    $templateJSON = json_encode( $template );
                    if ( isset($template[$templateName]['configuration']["defaultEP"]) ) {
                        $templateJSON = str_replace("#EP#", $template[$templateName]['configuration']["defaultEP"], $templateJSON);
                    }
                    else {
                        $templateJSON = str_replace("#EP#", "01", $templateJSON);
                    }
                    $template = json_decode( $templateJSON, true );
                    $templateMain = $template[$templateName]; // passe par une variable intermediaire pour simplifier l ecriture
                    $templateMainConfig = $template[$templateName]['configuration']; // passe par une variable intermediaire pour simplifier l ecriture

                    if ($GLOBALS['debugBEN']) echo "Abeille Id: ".$abeille->getId()." - Abeille Name: ".$abeille->getName()." template: ->".$templateName."<-\n";
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
                    // defaultEP
                    if ( self::testUpdateCommand($fp, "defaultEP", $templateMainConfig["defaultEP"], $abeille->getConfiguration("defaultEP") ) ) { $abeille->setConfiguration( "defaultEP", $templateMainConfig["defaultEP"] ); }
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

                        if ($GLOBALS['debugBEN']) {
                            echo "\n".$templateCmdId."\n";
                            var_dump($templateCmd);
                            var_dump($templateCmdConfig);
                        }

                        if (isset($templateCmd)) {
                            // if ($GLOBALS['debugBEN']) echo "Abeille Name: ".$abeille->getName()." - Cmd Name: ".$cmd->getName()."\n";
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
                            if ( self::testUpdateCommand($fp, "topic",                  $templateCmdConfig['topic'],                                 $cmd->getConfiguration('topic') ) )                 { $cmd->setConfiguration( 'topic',                  $templateCmdConfig['topic']);
                                                                                                                                                                                                                                                $cmd->setLogicalId($templateCmdId);
                                                                                                                                                                                                                                                                                               }
                            // request
                            if ( self::testUpdateCommand($fp, "request",                $templateCmdConfig['request'],                  $cmd->getConfiguration('request') ) )               { $cmd->setConfiguration( 'request',     $templateCmdConfig['request']);     }
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
                            // $elogic->checkAndUpdateCmd($cmd, $value);


                        }
                        else {
                            // if ($GLOBALS['debugBEN']) echo "Abeille Name: ".$abeille->getName()." - Cmd Name: ".$cmd->getName()." not found in template\n";
                            fwrite($fp, " \n---\nCmd Name: ".$cmd->getName()." ===================================> not found in template\n" );
                            log::add('Abeille', 'debug', "Abeille Name: ".$abeille->getName()." - Cmd Name: ".$cmd->getName()." not found in template");
                            // $cmd->setName("Cmd_not_in_template_".$cmd->getName());
                        } // if (isset($templateCmd))
                    } // foreach ( $abeille->getCmd() as $cmdId=>$cmd ) {
                } // if ( $cmd->getName() == "nom" ) {
            } // foreach ( $cmds as $cmdId=>$cmd ) {

            // log::add('updateConfigAbeille', 'info', 'updateConfigAbeille End');
            fwrite($fp, "Ending updateConfigAbeille\n");
            log::add('Abeille', 'debug', 'updateConfigAbeille end');
            if ($GLOBALS['debugBEN']) echo "updateConfigAbeille end\n";

            fclose($fp);

        }

        public static function cronDaily()
        {
            log::add(
                'Abeille',
                'debug',
                'Starting cronDaily ------------------------------------------------------------------------------------------------------------------------'
            );
            /**
             * Refresh LQI once a day to get IEEE in prevision of futur changes, to get network topo as fresh as possible in json
             */
            log::add('Abeille', 'debug', 'Launching AbeilleLQI.php');
            $DOMROOT=(null!=NEXTDOM_ROOT)?NEXTDOM_ROOT:JEEDOM_ROOT;
            $cmd = "cd $DOMROOT/plugins/Abeille/Network/; nohup /usr/bin/php AbeilleLQI.php >/dev/null 2>/dev/null &";
            log::add('Abeille', 'debug', $cmd);
            exec($cmd);
        }

        public static function cronHourly()
        {
            log::add(
                'Abeille',
                'debug',
                'Starting cronHourly ------------------------------------------------------------------------------------------------------------------------'
            );

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
            $i=0;
            foreach ($eqLogics as $eqLogic) {
                // log::add('Abeille', 'debug', 'Icone: '.$eqLogic->getConfiguration("icone"));
                if (strpos("_".$eqLogic->getConfiguration("icone"), "IkeaTradfriBulb") > 0) {
                    $topicArray = explode("/", $eqLogic->getLogicalId());
                    $addr = $topicArray[1];
                    $i=$i+1;
                    // log::add('Abeille', 'debug', 'Short: '.$addr);

                    /*
                     if ( is_object($elogic) ) { $cmdlogic = AbeilleCmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "IEEE-Addr"); }
                     $addrIEEE = $cmdlogic->execCmd();
                     log::add('Abeille', 'debug', 'IEEE: '.$addrIEEE);
                     */

                    $abeille = new Abeille();
                    $abeille = new Abeille();
                    $commandIEEE = new AbeilleCmd();

                    // Recupere IEEE de la Ruche/ZiGate
                    $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();
                    // log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);

                    $addrIEEE = $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'IEEE-Addr')->execCmd();
                    // log::add('Abeille', 'debug', 'IEEE pour abeille: ' . $addrIEEE);


                    log::add('Abeille', 'debug', 'Refresh bind and report for Ikea Bulb: '.$addr);
                    Abeille::publishMosquitto(
                        null,
                        "TempoCmdAbeille/Ruche/bindShort&time=".(time()+(($i*33)+1)),
                        "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0006&reportToAddress=".$ZiGateIEEE,
                        '0'
                    );

                    Abeille::publishMosquitto(
                        null,
                        "TempoCmdAbeille/Ruche/bindShort&time=".(time()+(($i*33)+2)),
                        "address=".$addr."&targetExtendedAddress=".$addrIEEE."&targetEndpoint=01&ClusterId=0008&reportToAddress=".$ZiGateIEEE,
                        '0'
                    );

                    Abeille::publishMosquitto(
                        null,
                        "TempoCmdAbeille/Ruche/setReport&time=".(time()+(($i*33)+3)),
                        "address=".$addr."&ClusterId=0006&AttributeId=0000&AttributeType=10",
                        '0'
                    );

                    Abeille::publishMosquitto(
                        null,
                        "TempoCmdAbeille/Ruche/setReport&time=".(time()+(($i*33)+4)),
                        "address=".$addr."&ClusterId=0008&AttributeId=0000&AttributeType=20",
                        '0'
                    );
                }
            }
            if ( ($i*33) > (3600) ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 1 heure." );
            }

            log::add(
                'Abeille',
                'debug',
                'Ending cronHourly ------------------------------------------------------------------------------------------------------------------------'
            );
        }

        public static function cron15()
        {
            log::add(
                'Abeille',
                'debug',
                'Starting cron15 ------------------------------------------------------------------------------------------------------------------------'
            );
            /**
             * Look every 15 minutes if the kernel driver is not in error
             */
            log::add('Abeille', 'debug', 'Check USB driver potential crash');
            $cmd = "egrep 'pl2303' /var/log/syslog | tail -1 | egrep -c 'failed|stopped'";
            $output = array();
            exec(system::getCmdSudo().$cmd, $output);
            $usbZigateStatus = !is_null($output) ? (is_numeric($output[0]) ? $output[0] : '-1') : '-1';
            if ($usbZigateStatus != '0') {
                message::add(
                    "Abeille",
                    "Erreur, le pilote pl2303 est en erreur, impossible de communiquer avec la zigate. Il faut débrancher/rebrancher la zigate et relancer le demon."
                );
                log::add(
                    'Abeille',
                    'debug',
                    'Ending cron15 ------------------------------------------------------------------------------------------------------------------------'
                );

            }


            log::add('Abeille', 'debug', 'Ping NE with 220V to check Online status');
            $eqLogics = Abeille::byType('Abeille');
            $i=0;
            foreach ($eqLogics as $eqLogic) {
                if (strlen($eqLogic->getConfiguration("battery_type")) == 0) {
                    $topicArray = explode("/", $eqLogic->getLogicalId());
                    $addr = $topicArray[1];
                    if (strlen($addr) == 4) {
                        // echo "Short: " . $topicArray[1];
                        log::add('Abeille', 'debug', 'Ping: '.$addr);
                        $i=$i+1;
                        // Ca devrait être le fonctionnement normal
                        if (strlen($eqLogic->getConfiguration("defaultEP"))>1) {
                            Abeille::publishMosquitto(null, "TempoCmdAbeille/".$addr."/Annonce&time=".(time()+($i*23)), $eqLogic->getConfiguration("defaultEP"), '0');
                        }
                        // Cette partie devrait disparaitre, elle existe depuis le debut car je ne comprenais pas bien le fonctionnement
                        else {
                            if ($eqLogic->getConfiguration("protocol") == "") {
                                Abeille::publishMosquitto(null, "TempoCmdAbeille/".$addr."/Annonce&time=".(time()+($i*23)), "Default", '0');
                            }
                            if ($eqLogic->getConfiguration("protocol") == "Hue") {
                                Abeille::publishMosquitto(null, "TempoCmdAbeille/".$addr."/Annonce&time=".(time()+($i*23)), "Hue", '0');
                            }
                            if ($eqLogic->getConfiguration("protocol") == "OSRAM") {
                                Abeille::publishMosquitto(null, "TempoCmdAbeille/".$addr."/Annonce&time=".(time()+($i*23)), "OSRAM", '0');
                            }
                            if ($eqLogic->getConfiguration("protocol") == "Profalux") {
                                Abeille::publishMosquitto(null, "TempoCmdAbeille/".$addr."/Annonce&time=".(time()+($i*23)), "AnnonceProfalux", '0');
                            }
                            if ($eqLogic->getConfiguration("protocol") == "LEGRAND") {
                                Abeille::publishMosquitto(null, "TempoCmdAbeille/".$addr."/Annonce&time=".(time()+($i*23)), "Default", '0');
                            }
                        }
                    }
                }
            }
            if ( ($i*23) > (60*15) ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 15 minutes. Cas A." );
            }

            // Rafraichie l etat poll = 15
            $i=0;
            log::add('Abeille', 'debug', 'Get etat and Level des ampoules');
            foreach ($eqLogics as $eqLogic) {
                $address = explode("/", $eqLogic->getLogicalId())[1];
                if (strlen($address) == 4) {
                    if ($eqLogic->getConfiguration("poll") == "15") {
                        log::add('Abeille', 'debug', 'GetEtat/GetLevel: '.$addr);
                        $i=$i+1;
                        Abeille::publishMosquitto(null, "TempoCmdAbeille/".$address."/ReadAttributeRequest&time=".(time()+($i*13)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000", '0');
                        Abeille::publishMosquitto(null, "TempoCmdAbeille/".$address."/ReadAttributeRequest&time=".(time()+($i*13)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0008&attributeId=0000", '0');
                    }

                }
            }
            if ( ($i*13) > (60*15) ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 15 minutes. Cas B." );
            }

            log::add(
                'Abeille',
                'debug',
                'Ending cron15 ------------------------------------------------------------------------------------------------------------------------'
            );

            return;
        }

        public static function cron()
        {
            // Cron tourne toutes les minutes
            // log::add( 'Abeille', 'debug', '----------- Starting cron ------------------------------------------------------------------------------------------------------------------------' );
            $eqLogics = self::byType('Abeille');

            // Debug
            // Abeille::publishMosquitto(null, "CmdAbeille/Ruche/abeilleList", "abeilleListAll", '0');

            // log::add('Abeille', 'debug', '----------- Ping Zigate to check Online status');
            $parameters_info = self::getParameters();
            if ($parameters_info['onlyTimer'] == 'N') {
                Abeille::publishMosquitto(null, "TempoCmdAbeille/Ruche/getVersion&time="      .(time()+20), "Version",          '0');
                Abeille::publishMosquitto(null, "TempoCmdAbeille/Ruche/getNetworkStatus&time=".(time()+24), "getNetworkStatus", '0');
            } else {
                Abeille::publishMosquitto(null, "Abeille/Ruche/SW-SDK", "TimerMode", '0');
                Abeille::publishMosquitto(null, "Abeille/Ruche/Time-TimeStamp", time(), '0');         // TimeStamp
                Abeille::publishMosquitto(
                    null,
                    "Abeille/Ruche/Time-Time",
                    date("Y-m-d H:i:s"),
                    '0'
                ); // 2018-10-06 03:13:31
            }

            // Rafraichie l etat poll = 1
            log::add('Abeille', 'debug', 'Get etat and Level des ampoules');
            $i = 0;
            foreach ($eqLogics as $eqLogic) {
                $address = explode("/", $eqLogic->getLogicalId())[1];
                if (strlen($address) == 4) {
                    if ($eqLogic->getConfiguration("poll") == "1") {
                        log::add('Abeille', 'debug', 'GetEtat/GetLevel: '.$address);
                        $i=$i+1;
                        Abeille::publishMosquitto(null, "TempoCmdAbeille/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0006&attributeId=0000", '0');
                        Abeille::publishMosquitto(null, "TempoCmdAbeille/".$address."/ReadAttributeRequest&time=".(time()+($i*3)), "EP=".$eqLogic->getConfiguration('mainEP')."&clusterId=0008&attributeId=0000", '0');
                    }
                }
            }
            if ( ($i*3) > 60 ) {
                message::add("Abeille","Danger il y a trop de message a envoyer dans le cron 1 minute." );
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
            if (self::checkInclusionStatus() == "01") {
                // log::add('Abeille', 'debug', 'Inclusion Status est a 01 donc on demande de rafraichir l info.');
                self::publishMosquitto(null, "CmdAbeille/Ruche/permitJoin", "Status", '0');
            } else {
                // log::add('Abeille', 'debug', 'Inclusion Status est a 00 donc on ne demande pas de rafraichir l info.');
            }

            // log::add( 'Abeille', 'debug', 'Ending cron ------------------------------------------------------------------------------------------------------------------------' );

        }

        public static function deamon_info()
        {
            $debug_deamon_info = 0;
            if ($debug_deamon_info) {
                log::add('Abeille', 'debug', '-');
            }
            if ($debug_deamon_info) {
                log::add('Abeille', 'debug', '**deamon info: IN**');
            }
            $return = array();
            $return['log'] = 'Abeille_update';
            $return['state'] = 'nok';
            $return['configuration'] = 'nok';

            $cron = cron::byClassAndFunction('Abeille', 'deamon');
            if (is_object($cron) && $cron->running()) {
                //$return['state'] = 'ok';
                if ($debug_deamon_info) {
                    log::add('Abeille', 'debug', 'deamon_info: J ai trouve le cron');
                }
            }
            //deps ok ?
            $dependancy_info = self::getDependencyInfo();
            if ($dependancy_info['state'] == 'ok') {
                if ($debug_deamon_info) {
                    log::add('Abeille', 'debug', 'deamon_info: les dependances sont Ok');
                }
                $return['launchable'] = 'ok';
            } else {
                if ($debug_deamon_info) {
                    log::add('Abeille', 'debug', 'deamon_info: deamon is not launchable ;-(');
                }
                if ($debug_deamon_info) {
                    log::add('Abeille', 'warning', 'deamon_info: deamon is not launchable due to dependancies missing');
                }
                $return['launchable'] = 'nok';
                //throw new Exception(__('Dépendances non installées, (re)lancer l\'installation : ', __FILE__));
                $return['launchable_message'] = 'Dépendances non installées, (re)lancer l\'installation';

                return $return;
            }

            //Parameters OK
            $parameters_info = self::getParameters();
            if ($parameters_info['state'] == 'ok') {
                if ($debug_deamon_info) {
                    log::add('Abeille', 'debug', 'deamon_info: J ai les parametres');
                }
                $return['launchable'] = 'ok';
            } else {
                if ($debug_deamon_info) {
                    log::add('Abeille', 'debug', 'deamon_info: deamon is not launchable ;-(');
                }
                if ($debug_deamon_info) {
                    log::add('Abeille', 'warning', 'deamon_info: deamon is not launchable due to parameters missing');
                }
                $return['launchable'] = 'nok';
                throw new Exception(
                    __(
                        'Problème de parametres, vérifier le port USB : '.$parameters_info['AbeilleSerialPort'].', state: '.$parameters_info['state'],
                        __FILE__
                    )
                );
            }


            //Check for running mosquitto service
            $deamon_info = self::serviceMosquittoStart();
            if ($deamon_info['launchable'] != 'ok') {
                message::add("Abeille", "Le service mosquitto n'a pas pu être démarré.");
                throw new Exception(
                    __(
                        'Vérifier l\'installation de mosquitto, le service ne démarre pas. Essayer de supprimer le fichier /var/log/mosquitto/mosquitto.log',
                        __FILE__
                    )
                );
            }


            //check running deamon /!\ if using sudo nbprocess x2
            if ($parameters_info['onlyTimer'] == 'N') {
                if ($parameters_info['AbeilleSerialPort'] == '/tmp/zigate') {
                    $nbProcessExpected = 5;
                } else {
                    $nbProcessExpected = 4;
                }
            } else {
                $nbProcessExpected = 1;
            }


            // no sudo to run deamon
            exec(
                "ps -e -o '%p;%a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer|Socat).php /' | cut -d ';'  -f 1 | wc -l",
                $output
            );

            if ($debug_deamon_info) {
                log::add('Abeille', 'debug', 'deamon_info, nombre de demons: '.$output[0]);
            }
            $nbProcess = $output[0];

            if ($nbProcess < $nbProcessExpected) {
                if ($debug_deamon_info) {
                    log::add(
                        'Abeille',
                        'info',
                        'deamon_info: found '.$nbProcess.'/'.$nbProcessExpected.' running, at least one is missing'
                    );
                }
                $return['state'] = 'nok';
            }

            if ($nbProcess > $nbProcessExpected) {
                if ($debug_deamon_info) {
                    log::add(
                        'Abeille',
                        'error',
                        'deamon_info: '.$nbProcess.'/'.$nbProcessExpected.' running, too many deamon running. Stopping deamons'
                    );
                }
                $return['state'] = 'nok';
                self::deamon_stop();
            }

            if ($nbProcess == $nbProcessExpected) {
                if ($debug_deamon_info) {
                    log::add(
                        'Abeille',
                        'Info',
                        'deamon_info: '.$nbProcess.'/'.$nbProcessExpected.' running, c est ce qu on veut.'
                    );
                }
                $return['state'] = 'ok';
            }


            if ($debug_deamon_info) {
                log::add(
                    'Abeille',
                    'debug',
                    '**deamon info: OUT**  deamon launchable: '.$return['launchable'].' deamon state: '.$return['state']
                );
            }

            return $return;
        }

        public static function deamon_start_cleanup($message = null)
        {
            // This function is used to run some cleanup before the demon start, or update the database du to data change needed.
            $debug = 0;
            $restartNeeded = 0;

            log::add('Abeille', 'debug', 'deamon_start_cleanup: Debut des modifications si nécessaire');

            // ******************************************************************************************************************
            // Remove temporary files
            $DataFile = dirname(__FILE__).'/../../AbeilleLQI_MapData.json';
            $FileLock = $DataFile . ".lock";

            if (file_exists($FileLock)) {
                $content = file_get_contents($FileLock);
                log::add('Abeille', 'debug', $DataFile.' content: '.$content);
                if (strpos("_".$content, "done") != 1) {
                    unlink( $FileLock );
                    log::add('Abeille', 'debug', 'Deleting '.$FileLock );
                }
            }

            // ******************************************************************************************************************
            // Suite à la modification permettant de mettre a jour les objets sur changement de short address, il faut modifier les configurations des commandes en base de données.
            log::add(
                'Abeille',
                'debug',
                'deamon_start_cleanup: mise a niveau pour la modification necessaire a la gestion des changements d adresse courte'
            );

            $sql = "SELECT id, logicalId, type, configuration FROM `cmd` WHERE `eqType` = 'Abeille' AND `configuration` LIKE '%topic%'";
            $rows = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);

            foreach ($rows as $key => $row) {
                $sqlRequest = 0;
                if ($debug) {
                    echo "-------------------------------\n";
                    echo "row: \n";
                    var_dump($row);
                    echo "row id: ".$row['id']."\n";
                }
                // $rowArray = json_decode($row->configuration);
                $rowArray = json_decode($row['configuration']);
                if ($debug) {
                    echo "rowArray: \n";
                    var_dump($rowArray);
                    echo "Position: ".strpos("_".$rowArray->topic, "Abeille/")."\n";

                    if ($row['type'] == "info") {
                        echo "test1: ok\n";
                    }
                    if (strpos("_".$rowArray->topic, "Abeille/") == 1) {
                        echo "test2: ok\n";
                    }
                }

                if (($row['type'] == 'info') && (strpos("_".$rowArray->topic, "Abeille/") == 1)) {
                    $rowArray->topic = str_replace("Abeille/", "", $rowArray->topic);
                    $position = strpos($rowArray->topic, "/");
                    if ($position > 1) {
                        $rowArray->topic = substr($rowArray->topic, $position - strlen($rowArray->topic) + 1);
                    }
                    $sqlRequest = 1;
                }

                if ($row['type'] == 'action') {
                    if (strpos($rowArray->topic, "Ruche") > 1) {
                        // Je ne change pas
                    } elseif (strpos($rowArray->topic, "Group") > 1) {
                        // Je ne change pas
                    } else {
                        if (strpos("_".$rowArray->topic, "CmdAbeille/") == 1) {
                            $rowArray->topic = str_replace("CmdAbeille/", "", $rowArray->topic);
                            $position = strpos($rowArray->topic, "/");
                            if ($position > 1) {
                                $rowArray->topic = substr($rowArray->topic, $position - strlen($rowArray->topic) + 1);
                            }
                            $sqlRequest = 1;
                        }
                    }
                }

                if ($sqlRequest == 1) {
                    $restartNeeded = 1;
                    $sql = "update cmd set logicalId='".$rowArray->topic."', configuration='".json_encode(
                            $rowArray
                        )."' where id='".$row['id']."'";
                    log::add('Abeille', 'debug', 'deamon_start_cleanup: '.$sql);
                    if ($debug) {
                        echo $sql."\n";
                    }
                    // $rows = $db->fetchAll($sql);
                    $rows = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
                }
            }


            // ******************************************************************************************************************
            // Espace pour les prochaines mise a niveau
            //
            //
            //
            log::add('Abeille', 'debug', 'deamon_start_cleanup: Fin des modifications si nécessaire');

            if ($restartNeeded == 1) {
                // afficher un message utilisateur pour qu il reboot le bousain.
                message::add(
                    "Abeille",
                    "La base de données a ete mise a jour suite a la mise a jour, il faut faire un restart de jeedom."
                );
            }

            return;
        }

        public static function deamon_start($_debug = false)
        {
            log::add('Abeille', 'debug', 'deamon_start: IN');

            self::deamon_stop();
            sleep(5);
            $parameters_info = self::getParameters();

            //no need as it seems to be on cron
            $deamon_info = self::getDependencyInfo();
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
            $publish = new Mosquitto\Client($parameters_info['AbeilleConId'].'_pub_deamon_start');
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
            log::add(
                'Abeille',
                'debug',
                'deamon_start: publish subject:'.$_subject.' message: '.$_message.'Qos: '.$parameters_info['AbeilleQos'].' retain: '.$_retain
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
            $dirdeamon = dirname(__FILE__)."/../../resources/AbeilleDeamon/";

            // $parameters_info = self::getParameters();

            if ($parameters_info['onlyTimer'] != 'Y') {
                $deamon1 = "AbeilleSerialRead.php";
                $paramdeamon1 = $parameters_info['AbeilleSerialPort'].' '.log::convertLogLevel(
                        log::getLogLevel('Abeille')
                    );
                $log1 = " > ".log::getPathToLog(substr($deamon1, 0, (strrpos($deamon1, "."))));

                $deamon2 = "AbeilleParser.php";
                $paramdeamon2 = $parameters_info['AbeilleSerialPort'].' '.$parameters_info['AbeilleAddress'].' '.$parameters_info['AbeillePort'].
                    ' '.$parameters_info['AbeilleUser'].' '.$parameters_info['AbeillePass'].' '.$parameters_info['AbeilleQos'].' '.log::convertLogLevel(
                        log::getLogLevel('Abeille')
                    );
                $log2 = " > ".log::getPathToLog(substr($deamon2, 0, (strrpos($deamon2, "."))));

                $deamon3 = "AbeilleMQTTCmd.php";
                $paramdeamon3 = $parameters_info['AbeilleSerialPort'].' '.$parameters_info['AbeilleAddress'].' '.$parameters_info['AbeillePort'].
                    ' '.$parameters_info['AbeilleUser'].' '.$parameters_info['AbeillePass'].' '.$parameters_info['AbeilleQos'].' '.log::convertLogLevel(
                        log::getLogLevel('Abeille')
                    );
                $log3 = " > ".log::getPathToLog(substr($deamon3, 0, (strrpos($deamon3, "."))));

                $deamon5 = "AbeilleSocat.php";
                $paramdeamon5 = $parameters_info['AbeilleSerialPort'].' '.log::convertLogLevel(
                        log::getLogLevel('Abeille')
                    ).' '.$parameters_info['IpWifiZigate'];
                $log5 = " > ".log::getPathToLog(substr($deamon5, 0, (strrpos($deamon5, "."))));


                $cmd = $nohup." ".$php." ".$dirdeamon.$deamon5." ".$paramdeamon5.$log5;
                log::add('Abeille', 'debug', 'Start deamon socat: '.$cmd);
                exec($cmd.' 2>&1 &');

                sleep(5);

                $cmd = $nohup." ".$php." ".$dirdeamon.$deamon1." ".$paramdeamon1.$log1;
                log::add('Abeille', 'debug', 'Start deamon SerialRead: '.$cmd);
                exec($cmd.' 2>&1 &');

                $cmd = $nohup." ".$php." ".$dirdeamon.$deamon2." ".$paramdeamon2.$log2;
                log::add('Abeille', 'debug', 'Start deamon Parser: '.$cmd);
                exec($cmd.' 2>&1 &');


                $cmd = $nohup." ".$php." ".$dirdeamon.$deamon3." ".$paramdeamon3.$log3;
                log::add('Abeille', 'debug', 'Start deamon MQTT: '.$cmd);
                exec($cmd.' 2>&1 &');


            }

            $deamon4 = "AbeilleMQTTCmdTimer.php";
            $paramdeamon4 = $parameters_info['AbeilleSerialPort'].' '.$parameters_info['AbeilleAddress'].' '.$parameters_info['AbeillePort'].
                ' '.$parameters_info['AbeilleUser'].' '.$parameters_info['AbeillePass'].' '.$parameters_info['AbeilleQos'].' '.log::convertLogLevel(
                    log::getLogLevel('Abeille')
                );
            $log4 = " > ".log::getPathToLog(substr($deamon4, 0, (strrpos($deamon4, "."))));

            $cmd = $nohup." ".$php." ".$dirdeamon.$deamon4." ".$paramdeamon4.$log4;
            log::add('Abeille', 'debug', 'Start deamon Timer: '.$cmd);
            exec($cmd.' 2>&1 &');

            // affichage Widget
            self::CmdAffichage('affichageNetwork',  $parameters_info['affichageNetwork']);
            self::CmdAffichage('affichageTime',     $parameters_info['affichageTime']   );
            self::CmdAffichage('affichageCmdAdd',   $parameters_info['affichageCmdAdd'] );

            // $cmd = "";
            log::add('Abeille', 'debug', 'deamon start: OUT');
            message::removeAll('Abeille', 'unableStartDeamon');

            return true;
        }

        public static function deamon_stop()
        {
            log::add('Abeille', 'debug', 'deamon stop: IN');
            // Stop socat if exist
            // exec("ps -e -o '%p %a' --cols=10000 | awk '/socat /' | awk '{print $1}' | tr  '\n' ' '", $output);
            exec("ps -e -o '%p %a' --cols=10000 | awk '/socat /' | awk '/\/tmp\/zigate/' | awk '{print $1}' | tr  '\n' ' '", $output);
            log::add('Abeille', 'debug', 'deamon stop: Killing deamons: '.implode($output, '!'));
            system::kill($output, true);
            exec(system::getCmdSudo()."kill -15 ".implode($output, ' ')." 2>&1");
            exec(system::getCmdSudo()."kill -9 ".implode($output, ' ')." 2>&1");

            // Stop other deamon
            exec(
                "ps -e -o '%p %a' --cols=10000 | awk '/Abeille(Parser|SerialRead|MQTTCmd|MQTTCmdTimer|Socat).php /' | awk '{print $1}' | tr  '\n' ' '",
                $output
            );
            log::add('Abeille', 'debug', 'deamon stop: Killing deamons: '.implode($output, '!'));
            system::kill($output, true);
            exec(system::getCmdSudo()."kill -9 ".implode($output, ' ')." 2>&1");

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

        public static function dependancy_info()
        {
          return self::getDependencyInfo();
        }

        public static function getDependencyInfo()
        {
            $debug_dependancy_info = 0;

            $return = array();
            $return['state'] = 'nok';
            $return['launchable'] = 'nok';
            $return['progress_file'] = jeedom::getTmpFolder('Abeille').'/dependance';
            $cmd = "dpkg -l | grep mosquitto";
            exec($cmd, $output_dpkg, $return_var);
            //lib PHP exist
            $libphp = extension_loaded('mosquitto');

            //Log debug only info
            self::serviceMosquittoStatus();

            if ($output_dpkg[0] != "" && $libphp) {
                //$return['configuration'] = 'ok';
                $return['launchable'] = 'ok';
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
            if ($debug_dependancy_info) {
                log::add('Abeille', 'debug', 'dependancy_info: '.$return['state']);
            }

            return $return;
        }

        public static function dependancy_install()
        {
            log::add('Abeille', 'debug', 'Installation des dépendances: IN');
            message::add(
                "Abeille",
                "L installation des dependances est en cours, n oubliez pas de lire la documentation: https://github.com/KiwiHC16/Abeille/tree/master/Documentation"
            );
            log::remove(__CLASS__.'_update');
            $result = array(
                'script' => dirname(__FILE__).'/../../resources/install.sh '.jeedom::getTmpFolder(
                        'Abeille'
                    ).'/dependance',
                'log' => log::getPathToLog(__CLASS__.'_update'),
            );
            if ($result['state'] == 'ok') {
                $result['launchable'] = 'ok';
            }
            log::add('Abeille', 'debug', 'Installation des dépendances: OUT: '.implode($result, ' X '));

            return $result;
        }

        public static function deamon()
        {
            //use verified parameters
            $parameters_info = self::getParameters();

            log::add(
                'Abeille',
                'debug',
                'Parametres utilises, Host : '.$parameters_info['AbeilleAddress'].',
            Port : '.$parameters_info['AbeillePort'].',
            AbeilleParentId : '.$parameters_info['AbeilleParentId'].',
            AbeilleConId: '.$parameters_info['AbeilleConId'].',
            AbeilleUser: '.$parameters_info['AbeilleUser'].',
            Abeillepass: '.$parameters_info['AbeillePass'].',
            AbeilleSerialPort: '.$parameters_info['AbeilleSerialPort'].',
            qos: '.$parameters_info['AbeilleQos'].',
            showAllCommands: '.$parameters_info['showAllCommands'].',
            affichageNetwork: '.$parameters_info['affichageNetwork'].',
            affichageTime: '.$parameters_info['affichageTime'].',
            affichageCmdAdd: '.$parameters_info['affichageCmdAdd'].',
            ModeCreation: '.$parameters_info['creationObjectMode'].',
            adresseCourteMode: '.$parameters_info['adresseCourteMode'].',
            onlyTimer: '.$parameters_info['onlyTimer'].',
            IpWifiZigate : '.$parameters_info['IpWifiZigate']
            );

            // https://github.com/mgdm/Mosquitto-PHP
            // http://mosquitto-php.readthedocs.io/en/latest/client.html
            $client = new Mosquitto\Client($parameters_info['AbeilleConId'].'_pub_deamon_Loop_ForEver');

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
            $client->setWill('/jeedom', "Client Abeille died :-(", $parameters_info['AbeilleQos'], 0);

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

                log::add('Abeille', 'debug', 'Subscribe to topic '.$parameters_info['AbeilleTopic']);

                // 1 to use loopForever et 0 to use while loop
                if (0) {
                    // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loopForever
                    $client->loopForever();
                } else {
                    while (true) {
                        // http://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::loop
                        $client->loop(0);
                        time_nanosleep( 0, 10000000 ); // 1/100s
                    }
                }

                $client->disconnect();
                unset($client);

            } catch (Exception $e) {
                log::add('Abeille', 'error', $e->getMessage());
            }
        }

        public static function serviceMosquittoStatus()
        {
            $debug_serviceMosquittoStatus = 0;

            $outputSvc = array();
            $return = array();
            $return['launchable'] = 'nok';
            $return['launchable_message'] = 'Service not running yet.';

            $cmdSvc = "expr  `service mosquitto status 2>&1 | grep -Eicv 'fail|unrecognized'` + `systemctl is-active mosquitto 2>&1 | grep -c ^active`";
            exec(system::getCmdSudo().$cmdSvc, $outputSvc);
            $logmsg = 'Status du service mosquitto : '.($outputSvc[0] > 0 ? 'OK' : 'Probleme').'   ('.implode(
                    $outputSvc,
                    '!'
                ).')';
            if ($debug_serviceMosquittoStatus) {
                log::add('Abeille', 'debug', $logmsg);
            }
            //Docker workaround as service will not write a pid file for mosquitto (status will always fail)
            if (file_exists("/.dockerenv") == true) {
                $outputSvc = array();
                exec(system::getCmdSudo()."pgrep mosquitto", $outputSvc);
                if ($debug_serviceMosquittoStatus) {
                    log::add('Abeille', 'debug', 'docker test: pid of mosquitto: '.$outputSvc[0]);
                }
            }
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
                exec(system::getCmdSudo().$cmdSvc, $outputSvc);
                log::add('Abeille', 'debug', 'kill du service mosquitto: '.$cmdSvc.' '.implode($outputSvc, '!'));
                unset($outputSvc);

                $cmdSvc = "service mosquitto start 2>&1 ;systemctl start mosquitto 2>&1";
                exec(system::getCmdSudo().$cmdSvc, $outputSvc);
                log::add('Abeille', 'debug', 'Start du service mosquitto: '.$cmdSvc.' '.implode($outputSvc, '!'));
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
            $return['adresseCourteMode'] = config::byKey('adresseCourteMode', 'Abeille', 'Automatique');
            $return['showAllCommands'] = config::byKey('showAllCommands', 'Abeille', 'N');
            $return['affichageNetwork'] = config::byKey('affichageNetwork', 'Abeille', 'N');
            $return['affichageTime'] = config::byKey('affichageTime', 'Abeille', 'N');
            $return['affichageCmdAdd'] = config::byKey('affichageCmdAdd', 'Abeille', 'N');
            $return['onlyTimer'] = config::byKey('onlyTimer', 'Abeille', 'N');
            $return['IpWifiZigate'] = config::byKey('IpWifiZigate', 'Abeille', '192.168.4.1');

            // log::add('Abeille', 'debug', 'serialPort value: ->' . $return['AbeilleSerialPort'] . '<-');
            if ($return['AbeilleSerialPort'] == "/tmp/zigate") {
                $return['state'] = 'ok';

                return $return;
            }
            if (($return['AbeilleSerialPort'] != 'none') ||  ($return['onlyTimer'] != "Y")) {
                $return['AbeilleSerialPort'] = jeedom::getUsbMapping($return['AbeilleSerialPort']);
                if (@!file_exists($return['AbeilleSerialPort'])) {
                    log::add(
                        'Abeille',
                        'debug',
                        'getParameters: serialPort n\'est pas défini. ->'.$return['AbeilleSerialPort'].'<-'
                    );
                    $return['launchable_message'] = __('Le port n\'est pas configuré ou zigate déconnectée', __FILE__);
                    throw new Exception(
                        __(
                            'Le port n\'est pas configuré ou zigate déconnectée : '.$return['AbeilleSerialPort'],
                            __FILE__
                        )
                    );
                } else {
                    if (substr(decoct(fileperms($return['AbeilleSerialPort'])), -4) != "0777") {
                        exec(system::getCmdSudo().'chmod 777 '.$return['AbeilleSerialPort'].' > /dev/null 2>&1');
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

        public static function postSave()
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
            log::add('Abeille', 'info', 'Mosquitto: Connexion à Mosquitto avec code '.$r.' '.$message);
            config::save('state', '1', 'Abeille');
        }

        public static function disconnect($r)
        {
            log::add('Abeille', 'debug', 'Mosquitto: Déconnexion de Mosquitto avec code '.$r);
            config::save('state', '0', 'Abeille');
        }

        public static function subscribe()
        {
            log::add('Abeille', 'debug', 'Mosquitto: Subscribe to topics');
        }

        public static function logmq($code, $str)
        {

            // log::add('Abeille', 'debug', 'Mosquitto: Log level: ' . $code . ' Message: ' . $str);

        }

        public static function fetchShortFromIEEE($IEEE, $checkShort)
        {
            // Return:
            // 0 : Short Address is aligned with the one received
            // Short : Short Address is NOT aligned with the one received
            // -1 : Error Nothing found

            // $lookForIEEE = "000B57fffe490C2a";
            // $checkShort = "2006";
            // log::add('Abeille', 'debug', 'BEN: start function fetchShortFromIEEE');
            $abeilles = Abeille::byType('Abeille');

            foreach ($abeilles as $abeille) {

                $cmdIEEE = $abeille->getCmd('Info', 'IEEE-Addr');
                if (is_object($cmdIEEE)) {

                    if ($cmdIEEE->execCmd() == $IEEE) {

                        $cmdShort = $abeille->getCmd('Info', 'Short-Addr');
                        if ($cmdShort) {
                            if ($cmdShort->execCmd() == $checkShort) {
                                // echo "Success ";
                                // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return 0');
                                return 0;
                            } else {
                                // echo "Pas success du tout ";
                                // La cmd short n est pas forcement à jour alors on va essayer avec le nodeId.
                                // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return Short: '.$cmdShort->execCmd() );
                                // return $cmdShort->execCmd();
                                // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return Short: '.substr($abeille->getlogicalId(),-4) );
                                return substr($abeille->getlogicalId(), -4);
                            }

                            return $return;
                        }
                    }
                }
            }

            // log::add('Abeille', 'debug', 'BEN: function fetchShortFromIEEE return -1');
            return -1;
        }

        public static function checkInclusionStatus()
        {
            // Return: Inclusion status or -1 if error
            $ruche = Abeille::byLogicalId('Abeille/Ruche', 'Abeille');

            // echo "Join status collection\n";
            $cmdJoinStatus = $ruche->getCmd('Info', 'permitJoin-Status');
            if ($cmdJoinStatus) {
                return $cmdJoinStatus->execCmd();
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

        public static function message($message)
        {

            $parameters_info = self::getParameters();

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
            if (!preg_match("(^Abeille|^Ruche|^CmdCreate|^CmdAffichage)", $message->topic)) {
                // log::add('Abeille', 'debug', 'message: this is not a ' . $Filter . ' message: topic: ' . $message->topic . ' message: ' . $message->payload);
                return;
            }


            /*----------------------------------------------------------------------------------------------------------------------------------------------*/
            // Analyse du message recu
            // [CmdAbeille:Abeille] / Address / Cluster-Parameter
            // [CmdAbeille:Abeille] / $addr / $cmdId => $value
            // $nodeId = [CmdAbeille:Abeille] / $addr

            $topicArray = explode("/", $message->topic);
            if (sizeof($topicArray) != 3) {
                return;
            }

            $Filter = $topicArray[0];
            if ($Filter == "CmdCreate") {
                $Filter = "Abeille";
            }
            $addr = $topicArray[1];
            $cmdId = $topicArray[2];
            $nodeid = $Filter.'/'.$addr;

            $value = $message->payload;
            // Le capteur de temperature rond V1 xiaomi envoie spontanement son nom: ->lumi.sensor_ht<- mais envoie ->lumi.sens<- sur un getName
            if ( $value=="lumi.sens" ) { $value = "lumi.sensor_ht"; }

            $type = 'topic';         // type = topic car pas json

            if ( strpos("_".$cmdId, "Time")>0 ) {
                log::add('Abeille', 'debug','-');
            }
            else {
                log::add('Abeille', 'debug', "Topic: ->".$message->topic."<- Value ->".$message->payload."<-");
            }

            // Si cmd Affichage
            if ( $Filter == "CmdAffichage") {
                log::add('Abeille', 'debug', 'Call CmdAffichage' );
                self::CmdAffichage( $cmdId, 'toggle' );
                return;
            }

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
                && ( config::byKey('creationObjectMode', 'Abeille', 'Automatique') != "Manuel") ) {

                log::add('Abeille', 'info', 'Recherche objet: '.$value.' dans les objets connus');
                //remove lumi. from name as all xiaomi devices have a lumi. name
                //remove all space in names for easier filename handling
                $trimmedValue = str_replace(' ', '', str_replace('lumi.', '', $value));

                // On enleve le / comme par exemple le nom des equipements Legrand
                $trimmedValue = str_replace('/', '', $trimmedValue);

                // On enleve les 0x00 comme par exemple le nom des equipements Legrand
                $trimmedValue = str_replace("\0", '', $trimmedValue);

                log::add('Abeille', 'debug', 'value:'.$value.' / trimmed value: '.$trimmedValue);
                $AbeilleObjetDefinition = Tools::getJSonConfigFilebyDevicesTemplate($trimmedValue);
                log::add('Abeille', 'debug', 'Template : '.json_encode($AbeilleObjetDefinition));

                // On recupere le EP
                // $EP = substr($cmdId,5,2);
                $EP = explode('-', $cmdId)[1];
                log::add('Abeille', 'debug', 'EP: '.$EP);
                $AbeilleObjetDefinitionJson = json_encode($AbeilleObjetDefinition);
                $AbeilleObjetDefinitionJson = str_replace('#EP#', $EP, $AbeilleObjetDefinitionJson);
                $AbeilleObjetDefinition = json_decode($AbeilleObjetDefinitionJson, true);
                log::add('Abeille', 'debug', 'Template : '.json_encode($AbeilleObjetDefinition));

                //Due to various kind of naming of devices, json object is either named as value or $trimmedvalue. We need to know which one to use.
                if (array_key_exists($value, $AbeilleObjetDefinition) || array_key_exists(
                        $trimmedValue,
                        $AbeilleObjetDefinition
                    )) {
                    $objetConnu = 1;
                    $jsonName = array_key_exists($value, $AbeilleObjetDefinition) ? $value : $trimmedValue;
                    log::add(
                        'Abeille',
                        'info',
                        'objet: '.$value.' recherché comme '.$trimmedValue.' peut etre cree car je connais ce type d objet.'
                    );
                } else {
                    log::add(
                        'Abeille',
                        'info',
                        'objet: '.$value.' recherché comme '.$trimmedValue.' ne peut pas etre creer car je ne connais pas ce type d objet.'
                    );
                    log::add('Abeille', 'debug', 'objet: '.json_encode($AbeilleObjetDefinition));
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

                message::add( "Abeille", "Création d un nouvel objet Abeille (".$addr.") en cours, dans quelques secondes rafraîchissez votre dashboard pour le voir." );
                $elogic = new Abeille();
                //id
                if ($objetConnu) {
                    $name = "Abeille-".$addr;
                    // $name = "Abeille-" . $addr . '-' . $jsonName;
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
                // message::add("Abeille", "TimeOut in JSON:".$objetDefSpecific["timeout"] );

                $elogic->setCategory(
                    array_keys($objetDefSpecific["Categorie"])[0],
                    $objetDefSpecific["Categorie"][array_keys($objetDefSpecific["Categorie"])[0]]
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
                        'Creation de la commande: '.$cmd.' suivant model de l objet pour l objet: '.$name
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
                            log::add(
                                'Abeille',
                                'debug',
                                'Define cmd info pour cmd action: '.$elogic->getName()." - ".$cmdValueDefaut["value"]
                            );

                            $cmdPointeur_Value = cmd::byTypeEqLogicNameCmdName(
                                "Abeille",
                                $elogic->getName(),
                                $cmdValueDefaut["value"]
                            );
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
            if (!is_object(
                    $elogic
                ) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Manuel")) {
                log::add('Abeille', 'debug', "Mode Manuel donc je ne fais rien");

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si l objet n existe pas et je recoie une commande IEEE => je vais créer un objet pour visualiser cet inconnu (si mode semi-automatique)
            // e.g. annonce recue en mode semi-automatique
            if (!is_object(
                    $elogic
                ) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Semi Automatique")) {
                // On peux recevoir l'IEEE lorsqu'un equipement s'annonce. Dans ce cas on a sa ShortAddress et son IEEE. Je ne sais pas si il y a d autres scenarios
                // Soit on ne le connais pas car il est nouveau ou sa shortAddress a changée. Mais dans les deux cas il n'est pas connu sous la ref de sa shortAddress

                // Creation de l objet Abeille
                log::add('Abeille', 'info', 'objet: '.$value.' creation sans model');
                message::add(
                    "Abeille",
                    "Création d un nouvel objet INCONNU Abeille (".$addr.") en cours, dans quelques secondes rafraichissez votre dashboard pour le voir."
                );
                $elogic = new Abeille();
                //id
                if ($objetConnu) {
                    // ici pour moi on ne devrait jamais être dans cas de figure. Ce code ne sert a rien je pense.
                    $name = "Abeille-".$addr;
                } else {
                    $name = "Abeille-".$addr."-Type d objet inconnu (IEEE)";
                }
                $elogic->setName($name);
                $elogic->setLogicalId($nodeid);
                $elogic->setObject_id($parameters_info['AbeilleParentId']);
                $elogic->setEqType_name('Abeille');

                // $objetDefSpecific = $AbeilleObjetDefinition[$value];
                // $objetConfiguration = $objetDefSpecific["configuration"];
                $elogic->setConfiguration('topic', $nodeid);
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
            if (!is_object(
                    $elogic
                ) && ($cmdId == "IEEE-Addr") && ($parameters_info['creationObjectMode'] == "Automatique")) {
                $ShortFound = Abeille::fetchShortFromIEEE($value, $addr);
                if ((strlen($ShortFound) == 4) && ($addr != "Ruche")) {
                    // Short Address has changed
                    // log::add('Abeille', 'debug', "IEEE-Addr; Alerte l adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, est ce que l objet aurait changé d adresse courte" );
                    // message::add("Abeille", "Alerte l adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound, est ce que l objet aurait changé d adresse courte" );

                    if (config::byKey('adresseCourteMode', 'Abeille', 'Automatique') == "Automatique") {

                        $elogic = self::byLogicalId("Abeille/".$ShortFound, 'Abeille');

                        log::add(
                            'Abeille',
                            'debug',
                            "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - " .$elogic->getName().", on fait la mise a jour automatique"
                        );
                        // Comme c est automatique des que le retour d experience sera suffisant, on n alerte pas l utilisateur. Il n a pas besoin de savoir
                        message::add(
                            "Abeille",
                            "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - " .$elogic->getName().", on fait la mise a jour automatique"
                        );



                        // Si on trouve l adresse dans le nom, on remplace par la nouvelle adresse
                        log::add(
                            'Abeille',
                            'debug',
                            "IEEE-Addr; Ancien nom: ".$elogic->getName().", nouveau nom: ".str_replace(
                                $ShortFound,
                                $addr,
                                $elogic->getName()
                            )
                        );
                        $elogic->setName(str_replace($ShortFound, $addr, $elogic->getName()));

                        $elogic->setLogicalId("Abeille/".$addr);

                        $elogic->setConfiguration('topic', "Abeille/".$addr);

                        $elogic->save();

                        // Il faut aussi mettre a jour la commande short address
                        self::publishMosquitto(null, "Abeille/".$addr."/Short-Addr", $addr, '0');
                    } else {
                        log::add(
                            'Abeille',
                            'debug',
                            "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - " .$elogic->getName().", on ne fait pas la mise a jour car pas mode automatique."
                        );
                        message::add(
                            "Abeille",
                            "IEEE-Addr; adresse IEEE $value pour $addr qui remonte est deja dans l objet $ShortFound - " .$elogic->getName().", on ne fait pas la mise a jour car pas mode automatique."
                        );
                    }
                }

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si l objet n existe pas et je recoie une commande => je drop la cmd
            // e.g. un Equipement envoie des infos, mais l objet n existe pas dans Jeedom
            if (!is_object($elogic)) {
                log::add(
                    'Abeille',
                    'debug',
                    "L equipement $addr n existe pas dans Jeedom, je ne process pas la commande."
                );

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
                    message::add( "Abeille", "Alerte changement de l adresse IEEE pour un equipement !!! ( $addr : $IEEE =>$value<= )" );
                }

                $elogic->checkAndUpdateCmd($cmdlogic, $value);

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Objet existe et cmd n existe pas
            if (is_object($elogic) && !is_object($cmdlogic)) {
                // Creons les commandes inconnues sur la base des commandes qu on recoit.
                log::add('Abeille', 'debug', 'L objet: '.$nodeid.' existe mais pas la commande: '.$cmdId);
                if ($parameters_info['creationObjectMode'] == "Semi Automatique") {
                    // Cree la commande avec le peu d info que l on a
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

                return;
            }

            /*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
            // Si equipement et cmd existe alors on met la valeur a jour
            if (is_object($elogic) && is_object($cmdlogic)) {
                /* Traitement particulier pour les batteries */
                if ($cmdId == "Batterie-Volt") {
                    /* Volt en milli V. Max a 3,1V Min a 2,7V, stockage en % batterie */
                    $elogic->setStatus('battery', ($value / 1000 - 2.7) / (3.1 - 2.7) * 100);
                    $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                }
                if ($cmdId == "0001-01-0021") {
                    /* en % batterie example Ikea Remote */
                    $elogic->setStatus('battery', $value);
                    $elogic->setStatus('batteryDatetime', date('Y-m-d H:i:s'));
                }

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

            log::add('Abeille', 'debug', "Tres bizarre, Message non traité, il manque probablement du code.");

            return; // function message
        }


        public static function publishMosquitto($_id, $_subject, $_message, $_retain)
        {
            $parameters_info = self::getParameters();
            log::add('Abeille', 'debug', 'Envoi du message: '.$_message.' vers '.$_subject);
            $publish = new Mosquitto\Client();

            $publish->setCredentials($parameters_info['AbeilleUser'], $parameters_info['AbeillePass']);

            $publish->connect($parameters_info['AbeilleAddress'], $parameters_info['AbeillePort'], 60);
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
                log::add('Abeille', 'debug', 'message: createRuche: objet: '.$elogic->getLogicalId().' existe deja');

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

            message::add(
                "Abeille",
                "Création de l objet Ruche en cours, dans quelques secondes rafraichissez votre dashboard pour le voir."
            );
            $elogic = new Abeille();
            //id
            $elogic->setName("Ruche");
            $elogic->setLogicalId("Abeille/Ruche");
            if ($parameters_info['AbeilleParentId'] > 0) {
                $elogic->setObject_id($parameters_info['AbeilleParentId']);
            } else {
                $elogic->setObject_id(object::rootObject()->getId());
            }
            $elogic->setEqType_name('Abeille');
            $elogic->setConfiguration('topic', "Abeille/Ruche");
            $elogic->setConfiguration('type', 'topic');
            $elogic->setConfiguration('lastCommunicationTimeOut', '-1');
            $elogic->setIsVisible("0");
            $elogic->setConfiguration('icone', "Ruche");
            // eqReal_id
            $elogic->setIsEnable("1");
            // status
            $elogic->setTimeout(15); // timeout en minutes
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

    class AbeilleCmd extends cmd
    {
        public function execute($_options = null)
        {
            log::add('Abeille', 'Debug', 'execute function with options ->'.json_encode($_options).'<-');
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
                        $rucheId = $ruche->byLogicalId('Abeille/Ruche', 'Abeille')->getId();
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
        echo "Debut Abeille.class.php test mode\n";
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

            // Free for a new test
            case "4":
                echo "Testing Dependancy info\n";
                $ruche = new Abeille();
                $dependancy_info = $ruche::getDependencyInfo();
                var_dump( $dependancy_info );
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
                            Abeille::publishMosquitto(null, "CmdAbeille/".$addr."/Annonce", "Default", '0');
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

            case "9":
                echo "Test Affichage\n";
                //  toggleAffichageNetwork
                //  toggleAffichageTime
                //  toggleAffichageAdditionalCommand
                Abeille::CmdAffichage( "affichageNetwork", "toggle" );
                break;

            case "10":
                // $object = object::rootObject()->getId();
                $object = object::all();
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

        } // switch

        echo "Fin Abeille.class.php test mode\n";
    }
