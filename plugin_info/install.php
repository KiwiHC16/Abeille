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

    /* Developers debug features */
    require_once __DIR__.'/../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../core/php/core.inc.php';
    require_once __DIR__.'/../core/php/AbeilleInstall.php';

    /**
     * Fonction exécutée automatiquement après l'installation du plugin
     * https://github.com/jeedom/plugin-template/blob/master/plugin_info/install.php
     *
     * @param   none
     * @return none
     */
    function Abeille_install() {
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (!is_object($cron)) {
            $cron = new cron();
            $cron->setClass('Abeille');
            $cron->setFunction('deamon');
            $cron->setEnable(1);
            $cron->setDeamon(1);
            $cron->setSchedule('* * * * *');
            $cron->setTimeout('1440');
            $cron->save();
        }
    }

    // Config DB: replace 'key1' by 'key2'
    function replaceConfigDB($key1, $key2) {
        $val = config::byKey($key1, 'Abeille', 'nada', true);
        if ($val != 'nada') {
            config::save($key2, $val, 'Abeille');
            config::remove($key1, 'Abeille');
            log::add('Abeille', 'debug', "  config DB: '".$key1."' changed to '".$key2."'");
        }
    }

    /* Check and update configuration DB if required.
       This function can update other informations in Jeedom DB.
       Note: This function can be called before daemons startup. Useful for
             GIT users & developpers. */
    function updateConfigDB() {

        /* Version 20200225 changes:
           - Added multi-zigate support
         */
        if (config::byKey('DbVersion', 'Abeille', '') == '') {

            // ******************************************************************************************************************
            // Update Abeille instance from previous version from Abeille/ to Abeille1/
            // Ruche
            $from   = "zigate";
            $to     = "Abeille1";
            $abeilles = Abeille::byType('Abeille');
            foreach ( $abeilles as $abeilleId=>$abeille) {
                if ( preg_match("/^".$from."\//", $abeille->getLogicalId() )) {
                    $abeille->setLogicalId( str_replace($from,$to,$abeille->getLogicalId()) );
                    $abeille->setName(str_replace( $from, $to, $abeille->getName()) );
                    $abeille->setConfiguration('topic', str_replace( $from, $to, $abeille->getConfiguration('topic') ) );
                    $abeille->save();
                }
            }
            // Abeille
            $from   = "Abeille";
            $to     = "Abeille1";
            $abeilles = Abeille::byType('Abeille');
            foreach ( $abeilles as $abeilleId=>$abeille) {
                if ( preg_match("/^".$from."\//", $abeille->getLogicalId() )) {
                    $abeille->setLogicalId( str_replace($from,$to,$abeille->getLogicalId()) );
                    $abeille->setName(str_replace( $from, $to, $abeille->getName()) );
                    $abeille->setConfiguration('topic', str_replace( $from, $to, $abeille->getConfiguration('topic') ) );
                    $abeille->save();
                }
            }
            config::save('zigateNb', '1', 'Abeille');

            config::save('deamonAutoMode', '1', 'Abeille');

            config::save('AbeilleActiver1', 'Y', 'Abeille');
            config::save('AbeilleActiver2', 'N', 'Abeille');
            config::save('AbeilleActiver3', 'N', 'Abeille');
            config::save('AbeilleActiver4', 'N', 'Abeille');
            config::save('AbeilleActiver5', 'N', 'Abeille');
            config::save('AbeilleActiver6', 'N', 'Abeille');
            config::save('AbeilleActiver7', 'N', 'Abeille');
            config::save('AbeilleActiver8', 'N', 'Abeille');
            config::save('AbeilleActiver9', 'N', 'Abeille');
            config::save('AbeilleActiver10', 'N', 'Abeille');

            $port1 = config::byKey('AbeilleSerialPort', 'Abeille', '');
            $addr1 = config::byKey('IpWifiZigate',      'Abeille', '');
            echo "port1: ".$port1;
            echo "addr1: ".$addr1;

            if (($port1 == '/tmp/zigate') || ($port1 == '/dev/zigate')) {
                config::save('AbeilleSerialPort1', '/dev/zigate1', 'Abeille');
                config::save('IpWifiZigate1', $addr1, 'Abeille');
            }
            else {
                config::save('AbeilleSerialPort1', $port1, 'Abeille');
            }
            config::save('DbVersion', '20200225', 'Abeille');
        }

        /* Version 20200510 changes:
           - Added 'AbeilleTypeX' (X=1 to 10): 'USB', 'WIFI', or 'PI'
         */
        $dbVersion = config::byKey('DbVersion', 'Abeille', '');
        if (($dbVersion == '') || (intval($dbVersion) < 20200510)) {
            for ($i = 1; $i <= 10; $i++) {
                if (config::byKey('AbeilleActiver'.$i, 'Abeille', '') != "Y")
                    continue; // Disabled or undefined

                $sp = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '');
                if ($sp == "/dev/zigate".$i)
                    config::save('AbeilleType'.$i, 'WIFI', 'Abeille');
                else if ((substr($sp, 0, 9) == "/dev/ttyS") || (substr($sp, 0, 11) == "/dev/ttyAMA"))
                    config::save('AbeilleType'.$i, 'PI', 'Abeille');
                else
                    config::save('AbeilleType'.$i, 'USB', 'Abeille');
            }
            config::save('DbVersion', '20200510', 'Abeille');
        }

        /* Version 20201025 changes:
           - All hexa values are now UPPER-CASE
         */
        if (intval($dbVersion) < 20201025) {
            /* Updating addresses in config */
            for ($i = 1; $i <= 10; $i++) {
                $ieee = config::byKey('AbeilleIEEE'.$i, 'Abeille', '');
                if ($ieee == "")
                    continue; // Undefined
                $ieee_up = strtoupper($ieee);
                if ($ieee_up !== $ieee)
                    config::save('AbeilleIEEE'.$i, $ieee_up, 'Abeille');
            }

            /* Updating addresses for all equipments Jeedom knows */
            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $ieee = $eqLogic->getConfiguration('IEEE', 'undefined');
                if ($ieee != "undefined") {
                    $ieee_up = strtoupper($ieee);
                    if ($ieee_up !== $ieee) {
                        $eqLogic->setConfiguration('IEEE', $ieee_up);
                        $eqLogic->save();
                    }
                }
                $topic = $eqLogic->getConfiguration('topic', 'undefined');
                if ($topic != "undefined") {
                    $topicArray = explode("/", $topic);
                    if (ctype_xdigit($topicArray[1])) { // Convert hexa only (ex: avoid 'Ruche')
                        $topicAddr_up = strtoupper($topicArray[1]);
                        if ($topicAddr_up !== $topicArray[1]) {
                            $eqLogic->setConfiguration('topic', $topicArray[0]."/".$topicAddr_up);
                            $eqLogic->save();
                        }
                    }
                }
                $logId = $eqLogic->getLogicalId();
                if ($logId != "") {
                    $logIdArray = explode("/", $logId);
                    if (ctype_xdigit($logIdArray[1])) { // Convert hexa only (ex: avoid 'Ruche')
                        $logIdAddr_up = strtoupper($logIdArray[1]);
                        if ($logIdAddr_up !== $logIdArray[1]) {
                            $eqLogic->setLogicalId($logIdArray[0]."/".$logIdAddr_up);
                            $eqLogic->save();
                        }
                    }
                }
            }

            config::save('DbVersion', '20201025', 'Abeille');
        }

        /* Version 20220407 changes:
           - Config DB: blocageTraitementAnnonce defaulted to "Non"
           - Config DB: agressifTraitementAnnonce defaulted to 4
           - Rename all eq names that use short addr to use Jeedom ID instead.
           - Eq DB: LogicalId 'AbeilleX/Ruche' updated to 'AbeilleX/0000'
           - Eq DB: Topic 'AbeilleX/Ruche' updated to 'AbeilleX/0000'
           - Config DB: Removing obsolete entries.
           - 'mainEP' fix: '#EP#' => '01'
           - Removing several zigate cmds now handled directly in equipement advanced page.
           - Eq DB: 'modeleJson' => 'ab::jsonId'
           - Config DB: Removing 'zigateNb' (obsolete) from config DB.
           - Updating WIFI serial port (/dev/zigateX => constant wifiLink)
           - AbeilleDebug.log moved to /tmp/jeedom
           - Cmd DB: 'ab::trig' => 'ab::trigOut'
           - Cmd DB: 'ab::trigOffset' => 'ab::trigOutOffset'
           - Cmd DB: 'ReadAttributeRequest' => 'readAttribute'
           - Cmd DB: Correcting wrong attribute size for 'getPlugVAW' (attrId=0505,508,050B)
           - Eq DB: Removing obsolete 'lastCommunicationTimeOut', 'type'.
         */
        if (intval($dbVersion) < 20220407) {
            if (config::byKey('blocageTraitementAnnonce', 'Abeille', 'none', 1) == "none") {
                config::save('blocageTraitementAnnonce', 'Non', 'Abeille');
            }
            if (config::byKey('blocageRecuperationEquipement', 'Abeille', 'none', 1) == "none") {
                config::save('blocageRecuperationEquipement', 'Oui', 'Abeille');
            }
            if (config::byKey('agressifTraitementAnnonce', 'Abeille', 'none', 1) == "none") {
                config::save('agressifTraitementAnnonce', '4', 'Abeille');
            }

            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $saveEq = false; // true if EQ has been updated (must be saved)

                $logicId = $eqLogic->getLogicalId();
                $logicIdArray = explode("/", $logicId);
                $network = $logicIdArray[0]; // 'AbeilleX'
                $addr = $logicIdArray[1]; // Get short addr

                // Correcting zigate address: 'Ruche' => '0000'
                if ($addr == "Ruche") {
                    $eqLogic->setLogicalId($network.'/0000');
                    $topic = $eqLogic->getConfiguration('topic', '');
                    if ($topic != '') {
                        $topicArr = explode("/", $topic); // Split 'AbeilleX/Ruche'
                        if ($topicArr[1] == 'Ruche')
                            $eqLogic->setConfiguration('topic', $topicArr[0].'/0000');
                    }
                    $saveEq = true;
                }

                // Correcting mainEP: '#EP#' => '01'
                $mainEP = $eqLogic->getConfiguration('mainEP', '');
                if (($mainEP == '') || ($mainEP == '#EP#')) {
                    $eqLogic->setConfiguration('mainEP', '01');
                    $saveEq = true;
                }

                // Correcting name based on short addr => using eq ID now
                $eqName = $eqLogic->getName();
                $id = $eqLogic->getId(); // Get Jeedom ID for this equipment
                $pos = stripos($eqName, $addr); // Any short addr in the name ?
                if ($pos == false) {
                    /* Current short addr no found in name but could it be
                    one that missed an update so keeping an old short addr ?
                    So updating only if format is "AbeilleX-YYYY..." which
                    sounds like the previous default naming. */
                    $l = strlen($network);
                    if (!substr_compare($eqName, $network, 0, $l)) {
                        /* Name starts with "AbeilleX" */
                        $a = substr($eqName, $l + 1, 4);
                        if ((strlen($a) == 4) && ctype_xdigit($a)) {
                            /* 4 hexa digits found. Assuming an old short addr */
                            $pos = $l + 1;
                        }
                    }
                }
                if ($pos != false) {
                    $eqName = substr_replace($eqName, $id, $pos, 4);
                    $eqLogic->setName($eqName);
                    $saveEq = true;
                }

                // Updating 'modeleJson' to 'ab::jsonId'
                $jsonId = $eqLogic->getConfiguration('modeleJson', '');
                if ($jsonId != '') {
                    $eqLogic->setConfiguration('ab::jsonId', $jsonId);
                    $eqLogic->setConfiguration('modeleJson', null);
                    log::add('Abeille', 'debug', '  '.$eqName.": 'modeleJson' updated to 'ab::jsonId'");
                    $saveEq = true;
                }

                // Removing obsolete 'lastCommunicationTimeOut' & 'type'
                $obsolete = $eqLogic->getConfiguration('lastCommunicationTimeOut', '');
                if ($obsolete != '') {
                    $eqLogic->setConfiguration('lastCommunicationTimeOut', null);
                    log::add('Abeille', 'debug', '  '.$eqName.": 'lastCommunicationTimeOut' removed");
                    $saveEq = true;
                }
                $obsolete = $eqLogic->getConfiguration('type', '');
                if ($obsolete != '') {
                    $eqLogic->setConfiguration('type', null);
                    log::add('Abeille', 'debug', '  '.$eqName.": 'type' removed");
                    $saveEq = true;
                }

                $cmds = Cmd::byEqLogicId($eqLogic->getId());
                $saveCmd = false;
                foreach ($cmds as $cmdLogic) {
                    // Updating 'ab::trig' to 'ab::trigOut'
                    $confVal = $cmdLogic->getConfiguration('ab::trig', '');
                    if ($confVal != '') {
                        $cmdLogic->setConfiguration('ab::trigOut', $confVal);
                        $cmdLogic->setConfiguration('ab::trig', null);
                        log::add('Abeille', 'debug', '  '.$eqName.": 'ab::trig' updated to 'ab::trigOut'");
                        $saveCmd = true;
                    }
                    // Updating 'ab::trigOffset' to 'ab::trigOutOffset'
                    $confVal = $cmdLogic->getConfiguration('ab::trigOffset', '');
                    if ($confVal != '') {
                        $cmdLogic->setConfiguration('ab::trigOutOffset', $confVal);
                        $cmdLogic->setConfiguration('ab::trigOffset', null);
                        log::add('Abeille', 'debug', '  '.$eqName.": 'ab::trigOffset' updated to 'ab::trigOutOffset'");
                        $saveCmd = true;
                    }
                    $topic = $cmdLogic->getConfiguration('topic', '');
                    $request = $cmdLogic->getConfiguration('request', '');
                    // Updating ReadAttributeRequest to readAttribute
                    if ($topic == "ReadAttributeRequest") {
                        $cmdLogic->setConfiguration('topic', 'readAttribute');
                        $request = str_replace("clusterId=", "clustId=", $request);
                        $request = str_replace("attributeId=", "attrId=", $request);
                        $request = str_replace("EP=", "ep=", $request);
                        $request = str_replace("Proprio=", "manufId=", $request);
                        $cmdLogic->setConfiguration('request', $request);
                        log::add('Abeille', 'debug', '  '.$eqName.": 'ReadAttributeRequest' updated to 'readAttribute'");
                        $saveCmd = true;
                    }
                    // Correcting wrong attribute size for 'getPlugVAW' (attrId=0505,508,050B)
                    $cmdLogicalId = $cmdLogic->getLogicalId();
                    if (($cmdLogicalId == "getPlugVAW") && (strpos($request, "attrId=0505,508,050B") !== false)) {
                        $request = str_replace("attrId=0505,508,050B", "attrId=0505,0508,050B", $request);
                        $cmdLogic->setConfiguration('request', $request);
                        log::add('Abeille', 'debug', '  '.$eqName.": 'getPlugVAW' updated for wrong attr 508 size");
                        $saveCmd = true;
                    }
                    if ($saveCmd)
                        $cmdLogic->save();
                }

                if ($saveEq)
                    $eqLogic->save();
            }

            // Removing obsolete entries from config table
            config::remove('affichageCmdAdd', 'Abeille');
            config::remove('affichageNetwork', 'Abeille');
            config::remove('affichageTime', 'Abeille');
            config::remove('AbeilleSerialPort', 'Abeille');
            config::remove('IpWifiZigate', 'Abeille');
            config::remove('AbeilleAddress', 'Abeille');
            config::remove('AbeilleConId', 'Abeille');
            config::remove('AbeillePort', 'Abeille');
            config::remove('mqttPass', 'Abeille');
            config::remove('mqttTopic', 'Abeille');
            config::remove('mqttUser', 'Abeille');
            config::remove('onlyTimer', 'Abeille');
            config::remove('zigateNb', 'Abeille');

            // Remove obsolete log files
            $obsolete = ['AbeilleCmd', 'AbeilleMQTTCmd', 'AbeilleMQTTCmdTimer', 'AbeilleSocat', 'AbeilleSerialRead', 'AbeilleParser', 'AbeilleDebug.log'];
            for ($z = 1; $z <= 10; $z++) {
                $obsolete[] = 'AbeilleSocat'.$z;
                $obsolete[] = 'AbeilleSerialRead'.$z;
            }
            foreach ($obsolete as $file) {
                $path = __DIR__."/../../../log/".$file;
                if (!file_exists($path))
                    continue;
                log::add('Abeille', 'debug', "  Removing obsolete log '".$file."'");
                unlink($path);
            }

            // Removing obsolete commands from existing Zigates (now handled in equipement advanced page)
            // Tcharp38: No longer required. Obsolete cmds are removed at daemon startup in 'Abeille.class'.
            // for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            //     $zg = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            //     if (!is_object($zg))
            //         continue;

            //     $eqId = $zg->getId();
            //     $obsoletes = ['Bind', 'BindShort', 'setReport', 'Get Name', 'Get Location', 'Set Location', 'Write Attribut', 'Get Attribut', 'ActiveEndPoint', 'SimpleDescriptorRequest'];
            //     foreach ($obsoletes as $cmdJName) {
            //         $cmd = AbeilleCmd::byEqLogicIdCmdName($eqId, $cmdJName);
            //         if (!is_object($cmd))
            //             continue;
            //         log::add('Abeille', 'debug', '  Zigate '.$zgId.": Removing obsolete cmd '".$cmdJName."'");
            //         $cmd->remove();
            //     }
            // }

            // Updating WIFI serial port (/dev/zigateX => constant wifiLink)
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                $port = config::byKey('AbeilleSerialPort'.$zgId, 'Abeille', '');
                if ($port == "")
                    continue;
                if (substr($port, 0, 11) != "/dev/zigate")
                    continue;
                config::save('AbeilleSerialPort'.$zgId, wifiLink.$zgId, 'Abeille');
                log::add('Abeille', 'debug', '  Zigate '.$zgId.": Updated 'AbeilleSerialPort".$zgId."'");
            }

            config::save('DbVersion', '20220407', 'Abeille');
        } // End 'intval($dbVersion) < 20220407'

        /* Version 20220421 changes:
           - eqLogic DB: 'ab::jsonId' + 'ab::jsonLocation' => 'ab::eqModel['id'/'location]'
           - eqLogic DB: 'MACCapa' => 'ab::zigbee['macCapa']'
           - eqLogic DB: 'RxOnWhenIdle' => 'ab::zigbee['rxOnWhenIdle']'
           - eqLogic DB: 'AC_Power' => 'ab::zigbee['mainsPowered']'
           - eqLogic DB: 'icone' => 'ab::icon'
           - config DB: 'AbeilleActiver' => 'ab::zgEnabled'
           - config DB: 'AbeilleType' => 'ab::zgType'
           - config DB: 'AbeilleSerialPort' => 'ab::zgPort'
           - config DB: 'IpWifiZigate' => 'ab::zgIpAddr'
           - config DB: 'AbeilleParentId' => 'ab::defaultParent'
         */
        if (intval($dbVersion) < 20220421) {
            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $saveEq = false; // true if EQ has been updated and therefore must be saved
                $eqHName = $eqLogic->getHumanName();
                $toRemove = []; // Configration keys to remove

                // Updating 'ab::eqModel'
                // 'ab::jsonId' + 'ab::jsonLocation' => 'ab::eqModel['id'/'location]'
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                $newEqModel = $eqModel;
                if (!isset($eqModel['id']))
                    $newEqModel['id'] = $eqLogic->getConfiguration('ab::jsonId', '');
                if (!isset($eqModel['location']))
                    $newEqModel['location'] = $eqLogic->getConfiguration('ab::jsonLocation', '');
                if ($newEqModel != $eqModel) {
                    $eqLogic->setConfiguration('ab::eqModel', $newEqModel);
                    log::add('Abeille', 'debug', '  '.$eqHName.": Updated configuration key 'ab::eqModel'");
                    $saveEq = true;
                }
                array_push($toRemove, 'ab::jsonId', 'ab::jsonLocation');

                // Updating 'ab::zigbee'
                // - 'MACCapa' => 'ab::zigbee['macCapa']'
                // - 'RxOnWhenIdle' => 'ab::zigbee['rxOnWhenIdle']'
                // - 'AC_Power' => 'ab::zigbee['mainsPowered']'
                $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                $newZigbee = $zigbee;
                if (!isset($zigbee['macCapa']))
                    $newZigbee['macCapa'] = $eqLogic->getConfiguration('MACCapa', '');
                if (!isset($zigbee['rxOnWhenIdle']))
                    $newZigbee['rxOnWhenIdle'] = $eqLogic->getConfiguration('RxOnWhenIdle', '');
                if (!isset($zigbee['mainsPowered']))
                    $newZigbee['mainsPowered'] = $eqLogic->getConfiguration('AC_Power', '');
                if ($newZigbee != $zigbee) {
                    $eqLogic->setConfiguration('ab::zigbee', $newZigbee);
                    log::add('Abeille', 'debug', '  '.$eqHName.": Updated configuration key 'ab::zigbee'");
                    $saveEq = true;
                }
                array_push($toRemove, 'MACCapa', 'RxOnWhenIdle', 'AC_Power');

                // 'icone' => 'ab::icon'
                $icone = $eqLogic->getConfiguration('icone', null);
                $icon = $eqLogic->getConfiguration('ab::icon', null);
                if (!$icon && $icone) {
                    $eqLogic->setConfiguration('ab::icon', $icone);
                    $eqLogic->setConfiguration('icone', null);
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'icone' key renamed to 'ab::icon'");
                    $saveEq = true;
                }

                // Removing obsolete keys
                foreach ($toRemove as $key) {
                    if ($eqLogic->getConfiguration($key, null) === null)
                        continue;
                    $eqLogic->setConfiguration($key, null);
                    log::add('Abeille', 'debug', '  '.$eqHName.": Removed configuration key '".$key."'");
                    $saveEq = true;
                }

                // Renaming icons if required
                $iList = array(
                    "Blitzwolf-BW-SHP13" => "Blitzwolf-SmartPlug",
                    "BulbE27" => "Generic-BulbE27",
                    "Generic-E14" => "Generic-BulbE14",
                    "Generic-E27-Color" => "Generic-BulbE27-Color",
                    "Generic-E27" => "Generic-BulbE27",
                    "Generic-GU10" => "Generic-BulbGU10",
                    "ProfaluxTelecommande" => "Profalux-Remote",
                    "voletProFalux" => "Profalux-Shutter",
                    "node_bsoProFalux" => "Profalux-BSO",
                    "BASICZBR3" => "Sonoff-BASICZBR3",
                    "SNZB-01" => "Sonoff-SNZB-01",
                    "SNZB-02" => "Sonoff-SNZB-02",
                    "SNZB-03" => "Sonoff-SNZB-03",
                    "SNZB-04" => "Sonoff-SNZB-04",
                    "01MINIZB" => "Sonoff-01MINIZB",
                    "TuyaSmartSocket" => "Tuya-SmartSocket",
                    "Tuya4ButtonsSceneSwitch" => "Tuya-4ButtonsSwitch-Gray",
                    "Tuya4ButtonsSwitch" => "Tuya-4ButtonsSwitch-White",
                    "PlugZ3" => "Ledvance-PlugZ3",
                    "LegrandRemoteSwitch" => "Legrand-RemoteSwitch",
                    "Contactor" => "Legrand-Contactor",
                    "Ikea-GU10" => "Ikea-BulbGU10",
                    "Ikea-E27" => "Ikea-BulbE27",
                    "IkeaTradfriBulbE27Opal1000lm" => "Ikea-BulbE27",
                    "IkeaTradfriBulbE27WOpal1000lm2" => "Ikea-BulbE27",
                    "IkeaTRADFRIbulbE27WSopal980lm" => "Ikea-BulbE27",
                    "JR-ZDS01" => "Girier-JR-ZDS01",
                    "GL-C-008" => "Gledopto-RGBCCTLedController",
                    "GL-S-003Z" => "Gledopto-BulbGU10-Color",
                    "GL-S-004Z" => "Gledopto-BulbGU10",
                    "GLEDOPTO" => "Gledopto-BulbGU10",
                    "plug.maeu01" => "Xiaomi-SmartPlug",
                );
                $curIcon = $eqLogic->getConfiguration('ab::icon', '');
                if (($curIcon != '') && isset($iList[$curIcon])) {
                    $newIcon = $iList[$curIcon];
                    $eqLogic->setConfiguration('ab::icon', $newIcon);
                    log::add('Abeille', 'debug', '  '.$eqHName.": Icon '".$curIcon."' changed to '".$newIcon."'");
                    $saveEq = true;
                }

                if ($saveEq)
                    $eqLogic->save();
            }

            // Config DB updates
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                replaceConfigDB('AbeilleActiver'.$zgId, 'ab::zgEnabled'.$zgId);
                replaceConfigDB('AbeilleType'.$zgId, 'ab::zgType'.$zgId);
                replaceConfigDB('AbeilleSerialPort'.$zgId, 'ab::zgPort'.$zgId);
                replaceConfigDB('IpWifiZigate'.$zgId, 'ab::zgIpAddr'.$zgId);
            }
            replaceConfigDB('AbeilleParentId', 'ab::defaultParent');

            // config::save('DbVersion', '20220421', 'Abeille'); // OT FROZEN YET
        }
    }

    /**
     * Automatically called by Jeedom after plugin update.
     * https://github.com/jeedom/plugin-template/blob/master/plugin_info/install.php
     *
     * @param none
     * @return none
     */
    function Abeille_update() {

        $error = false;

        if (validMd5Exists($version)) {
            if (checkIntegrity() != true) {
                message::add('Abeille', 'Fichiers corrompus detectés ! La version \''.$version.'\' est mal installée. Problème de place ?', null, null);
                $error = true;
            } else
                doPostUpdateCleanup();
        }

        /* Updating config DB if required */
        updateConfigDB();

        if ($error == false) {
            message::removeAll('Abeille');
            message::add('Abeille', 'Mise à jour '.$version.' terminée avec succès.', null, null);
        }
    }

    /**
     * Fonction exécutée automatiquement après la suppression du plugin
     * https://github.com/jeedom/plugin-template/blob/master/plugin_info/install.php
     *
     * @param   none
     * @return none
     */
    function Abeille_remove() {
        $cron = cron::byClassAndFunction('Abeille', 'deamon');
        if (is_object($cron)) {
            $cron->stop();
            $cron->remove();
        }
        log::add('Abeille', 'info', 'Suppression extension');
        // Tcharp38: remove.sh currently does nothing
        // $path = realpath(__DIR__.'/../core/scripts');
        // passthru('sudo /bin/bash '.$path.'/remove.sh '.$path.' >'.log::getPathToLog('AbeilleRemoval.log').' 2>&1 &');
        message::removeAll("Abeille");
        // message::add("Abeille","plugin désinstallé");
    }
?>
