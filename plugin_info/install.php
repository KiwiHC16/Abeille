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
        log::add('Abeille', 'debug', 'Abeille_install() starting');

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

    // Config DB: replace 'oldKey' by 'newKey'
    function replaceConfigDB($oldKey, $newKey) {
        $val = config::byKey($oldKey, 'Abeille', 'nada', true);
        if ($val != 'nada') {
            config::save($newKey, $val, 'Abeille');
            log::add('Abeille', 'debug', "  config DB: '".$oldKey."' changed to '".$newKey."'");
        }
        // Note: If old key is defined to null then can't detect it so force remove anyway.
        config::remove($oldKey, 'Abeille');
    }

    // Remove log files listed in '$obsolete'
    function removeLogs($obsolete) {
        foreach ($obsolete as $file) {
            $path = __DIR__."/../../../log/".$file;
            if (!file_exists($path))
                continue;
            log::add('Abeille', 'debug', "  Removing obsolete log '".$file."'");
            unlink($path);
        }
    }

    /* Check and update configuration DB if required.
       This function can update other informations in Jeedom DB.
       Note: This function can be called before daemons startup. Useful for
             GIT users & developpers. */
    function updateConfigDB() {

        $dbVersion = config::byKey('ab::dbVersion', 'Abeille', '');
        if ($dbVersion == '')
            $dbVersion = config::byKey('DbVersion', 'Abeille', '');

        /* Version 20200225 changes:
           - Added multi-zigate support
         */
        if ($dbVersion == '') {

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
            config::save('ab::dbVersion', '20200225', 'Abeille');
            $dbVersion = '20200225';
        }

        /* Version 20200510 changes:
           - Added 'AbeilleTypeX' (X=1 to 10): 'USB', 'WIFI', or 'PI'
         */
        if (intval($dbVersion) < 20200510) {
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', '') != "Y")
                    continue; // Disabled or undefined

                $sp = config::byKey('AbeilleSerialPort'.$zgId, 'Abeille', '');
                if ($sp == "/dev/zigate".$zgId)
                    config::save('AbeilleType'.$zgId, 'WIFI', 'Abeille');
                else if ((substr($sp, 0, 9) == "/dev/ttyS") || (substr($sp, 0, 11) == "/dev/ttyAMA"))
                    config::save('AbeilleType'.$zgId, 'PI', 'Abeille');
                else
                    config::save('AbeilleType'.$zgId, 'USB', 'Abeille');
            }
            config::save('ab::dbVersion', '20200510', 'Abeille');
            $dbVersion = '20200510';
        }

        /* Version 20201025 changes:
           - All hexa values are now UPPER-CASE
         */
        if (intval($dbVersion) < 20201025) {
            /* Updating addresses in config */
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                $ieee = config::byKey('AbeilleIEEE'.$zgId, 'Abeille', '');
                if ($ieee == "")
                    continue; // Undefined
                $ieee_up = strtoupper($ieee);
                if ($ieee_up !== $ieee)
                    config::save('AbeilleIEEE'.$zgId, $ieee_up, 'Abeille');
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

            config::save('ab::dbVersion', '20201025', 'Abeille');
            $dbVersion = '20201025';
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
            // The following is now obsolete
            // if (config::byKey('blocageTraitementAnnonce', 'Abeille', 'none', 1) == "none") {
            //     config::save('blocageTraitementAnnonce', 'Non', 'Abeille');
            // }
            // if (config::byKey('blocageRecuperationEquipement', 'Abeille', 'none', 1) == "none") {
            //     config::save('blocageRecuperationEquipement', 'Oui', 'Abeille');
            // }
            // if (config::byKey('agressifTraitementAnnonce', 'Abeille', 'none', 1) == "none") {
            //     config::save('agressifTraitementAnnonce', '4', 'Abeille');
            // }

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
            $obsolete = ['AbeilleCmd', 'AbeilleMQTTCmd', 'AbeilleMQTTCmdTimer', 'AbeilleSocat', 'AbeilleSerialRead', 'AbeilleParser', 'AbeilleDebug.log', 'AbeilleConfig'];
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                $obsolete[] = 'AbeilleSocat'.$zgId;
                $obsolete[] = 'AbeilleSerialRead'.$zgId;
            }
            removeLogs($obsolete);

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

            config::save('ab::dbVersion', '20220407', 'Abeille');
            $dbVersion = '20220407';
        } // End 'intval($dbVersion) < 20220407'

        /* Version 20220421 changes:
           - eqLogic DB: 'ab::jsonId' + 'ab::jsonLocation' => 'ab::eqModel['id'/'location]'
           - eqLogic DB: 'MACCapa' => 'ab::zigbee['macCapa']'
           - eqLogic DB: 'RxOnWhenIdle' => 'ab::zigbee['rxOnWhenIdle']'
           - eqLogic DB: 'AC_Power' => 'ab::zigbee['mainsPowered']'
           - eqLogic DB: 'icone' => 'ab::icon'
           - eqLogic DB: 'positionX' => 'ab::settings[physLocationX]'
           - eqLogic DB: 'positionY' => 'ab::settings[physLocationY]'
           - eqLogic DB: 'uniqId' is obsolete
           - eqLogic DB: 'routingTable' is obsolete
           - config DB: 'AbeilleActiverX' => 'ab::zgEnabledX'
           - config DB: 'AbeilleTypeX' => 'ab::zgTypeX'
           - config DB: 'AbeilleSerialPortX' => 'ab::zgPortX'
           - config DB: 'IpWifiZigateX' => 'ab::zgIpAddrX'
           - config DB: 'AbeilleParentId' => 'ab::defaultParent'
           - config DB: 'AbeilleIEEEX' => 'ab::zgIeeeAddrX'
           - config DB: 'AbeilleIEEE_OkX' => 'ab::zgIeeeAddrOkX'
           - config DB: 'preventLQIRequest' => 'ab::preventLQIAutoUpdate'
           - config DB: 'monitor' => 'ab::monitorId'
           - config DB: Removed obsolete 'agressifTraitementAnnonce'.
           - config DB: Removed obsolete 'blocageRecuperationEquipement'.
           - config DB: Removed obsolete 'blocageTraitementAnnonce'.
           - config DB: 'DbVersion' => 'ab::dbVersion'.
           - config DB: Added 'ab::zgChan' for Zigbee channel.
           - config DB: Added 'ab::userMap' for user map (Network map).
           - cmd DB: 0405-XX-0000: Removed 'calculValueOffset'.
           - cmd DB: 0402-XX-0000: Removed 'calculValueOffset'.
           - cmd DB: 0400-XX-0000: Removed 'calculValueOffset'.
           - cmd DB: '0001-#EP#-0020': Updating trigOutOffset: '#value#*100/30' => '#value#*100/3'
           - cmd DB: 'Batterie-Pourcent' => '0001-01-0021'
           - cmd DB: 'WindowsCovering' => 'cmd-0102' + 'cmd=XX'
           - Removing 'AbeilleDebug.log'. Moved to Jeedom tmp dir.
           - Forcing some models reload (Xiaomi devices).
         */
        if (intval($dbVersion) < 20230521) {
            // 'eqLogic' DB updates
            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $saveEq = false; // true if EQ has been updated and therefore must be saved
                $eqHName = $eqLogic->getHumanName();
                $toRemove = []; // eqLogic 'configuration' keys to remove

                // Updating 'ab::eqModel'
                // 'ab::jsonId' + 'ab::jsonLocation' => 'ab::eqModel['id'/'location]'
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                $newEqModel = $eqModel;
                if (!isset($eqModel['id']))
                    $newEqModel['id'] = $eqLogic->getConfiguration('ab::jsonId', '');
                else if ($eqModel['id'] == 'RC110') // Model 'RC110' => 'RC110_innr'
                    $newEqModel['id'] = 'RC110_innr';
                else if ($eqModel['id'] == 'TS0201') // Model 'TS0201' => 'TS0201__TYZB01_hjsgdkfl'
                    $newEqModel['id'] = 'TS0201__TYZB01_hjsgdkfl';
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

                // 'positionX' => 'ab::settings[physLocationX]'
                // 'positionY' => 'ab::settings[physLocationY]'
                $settings = $eqLogic->getConfiguration('ab::settings', []);
                $saveSettings = false;
                $posX = $eqLogic->getConfiguration('positionX', 'nada');
                if ($posX !== 'nada') {
                    $settings['physLocationX'] = $posX;
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'positionX' key renamed to 'ab::settings[physLocationX]'");
                    $saveSettings = true;
                }
                $posY = $eqLogic->getConfiguration('positionY', 'nada');
                if ($posY !== 'nada') {
                    $settings['physLocationY'] = $posY;
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'positionY' key renamed to 'ab::settings[physLocationY]'");
                    $saveSettings = true;
                }
                if ($saveSettings) {
                    $eqLogic->setConfiguration('ab::settings', $settings);
                    $saveEq = true;
                }
                array_push($toRemove, 'positionX', 'positionY');
                array_push($toRemove, 'uniqId', 'routingTable');

                // Removing obsolete configuration keys
                foreach ($toRemove as $key) {
                    // if ($eqLogic->getConfiguration($key, 'nada') === 'nada')
                    //     continue; // Really undefined ??
                    // No way to detect all obsolete keys. If set to empty it is the same as does not exist.
                    $eqLogic->setConfiguration($key, null);
                    // log::add('Abeille', 'debug', '  '.$eqHName.": Removed configuration key '".$key."'");
                    $saveEq = true;
                }

                // Renaming icons if required
                $iList = array(
                    "Blitzwolf-BW-SHP13" => "Blitzwolf-SmartPlug",
                    "TS0201" => "Blitzwolf-Display",
                    "FNB54-THM17ML1.1" => "Blitzwolf-Display",

                    "BulbE27" => "Generic-BulbE27",
                    "Generic-E14" => "Generic-BulbE14",
                    "Generic-E27-Color" => "Generic-BulbE27-Color",
                    "Generic-E27" => "Generic-BulbE27",
                    "Generic-GU10" => "Generic-BulbGU10",

                    "JR-ZDS01" => "Girier-JR-ZDS01",

                    "GL-C-008" => "Gledopto-RGBCCTLedController",
                    "GL-S-003Z" => "Gledopto-BulbGU10-Color",
                    "GL-S-004Z" => "Gledopto-BulbGU10",
                    "GLEDOPTO" => "Gledopto-BulbGU10",

                    "HS2WD" => "Heiman-IndoorSiren",

                    "Ikea-GU10" => "Ikea-BulbGU10",
                    "Ikea-E27" => "Ikea-BulbE27",
                    "IkeaTradfriBulbE27Opal1000lm" => "Ikea-BulbE27",
                    "IkeaTradfriBulbE27WOpal1000lm2" => "Ikea-BulbE27",
                    "IkeaTRADFRIbulbE27WSopal980lm" => "Ikea-BulbE27",
                    "TRADFRItransformer10W" => "Ikea-Transformer",
                    "TRADFRItransformer30W" => "Ikea-Transformer",
                    "TRADFRIsignalrepeater" => "Ikea-SignalRepeater",
                    "IkeaTradfri5BtnRond" => "Ikea-Remote-5buttons",
                    "TRADFRIonoffswitch" => "Ikea-OnOffSwitch",
                    "node_Ikea-OnOffSwitch" => "Ikea-OnOffSwitch",

                    "511.201" => "Iluminize-511201",
                    "511.202" => "Iluminize-511202",

                    "RC110" => "Innr-RC110",

                    "PlugZ3" => "Ledvance-PlugZ3",
                    "A60TWZ3" => "Ledvance-SmartP-E27Bulb",

                    "LegrandRemoteSwitch" => "Legrand-RemoteSwitch",
                    "Contactor" => "Legrand-Contactor",
                    "Connectedoutlet" => "Legrand-Connectedoutlet",
                    "Micromoduleswitch" => "Legrand-MicromoduleSwitch",
                    "Shutterswitchwithneutral" => "Legrand-ShutterSwitch",

                    "LoraTap3GangRemote" => "LoraTap-3GangRemote",

                    "Moes-ZSS-ZK-THL" => "Moes-Thermometer",

                    "MR16TWOSRAM" => "Osram-SmartP-MR16Bulb",
                    "OsramLightify" => "Osram-SmartP-Plug",
                    "PAR1650TW" => "Osram-Lightify-GU10Bulb",

                    "LOM001" => "PhilipsSignify-Plug",
                    "LOM002" => "PhilipsSignify-Plug",
                    "LTW001" => "Philips-Bulb-E27-White",
                    "Philips-MotionSensor-SML003" => "Philips-MotionSensor-1",
                    "SML001" => "Philips-MotionSensor-1",
                    "Philips-OutdoorSensor" => "Philips-MotionSensor-2",
                    "RB285C" => "Innr-E27Bulb-Colour",

                    "ProfaluxTelecommande" => "Profalux-Remote",
                    "voletProFalux" => "Profalux-Shutter",
                    "bsoProFalux" => "Profalux-BSO",

                    "BASICZBR3" => "Sonoff-BASICZBR3",
                    "SNZB-01" => "Sonoff-SNZB-01",
                    "SNZB-02" => "Sonoff-SNZB-02",
                    "SNZB-03" => "Sonoff-SNZB-03",
                    "SNZB-04" => "Sonoff-SNZB-04",
                    "01MINIZB" => "Sonoff-01MINIZB",

                    "TuyaSmartSocket" => "Tuya-SmartSocket",
                    "Tuya-TS011F" => "Tuya-SmartSocket-2",
                    "Tuya4ButtonsSceneSwitch" => "Tuya-4ButtonsSwitch-Gray",
                    "Tuya4ButtonsSwitch" => "Tuya-4ButtonsSwitch-White",

                    "XiaomiPorte" => "Xiaomi-DoorSensor",
                    "XiaomiPorte1" => "Xiaomi-DoorSensor-2",
                    "XiaomiTemperatureCarre" => "Xiaomi-TempSensor-2",
                    "XiaomiTemperatureRond" => "Xiaomi-TempSensor-1",
                    "XiaomiPriseEU" => "Xiaomi-Plug-EU",
                    "sen_ill_mgl01" => "Xiaomi-LightSensor-1",
                    "XiaomiInfraRouge" => "Xiaomi-MotionSensor",
                    "XiaomiInfraRouge2" => "Xiaomi-MotionSensor",
                    "sensor_cube" => "Xiaomi-Cube",
                    "XiaomiPrise" => "Xiaomi-Plug",
                    "Xiaomiwleak_aq1" => "Xiaomi-LeakSensor",
                    "XiaomiVibration" => "Xiaomi-Vibration",
                    'XiaomiSensorSmoke' => 'Xiaomi-SmokeSensor',
                    'XiaomiBouton' => 'Xiaomi-Button-1',

                    "LXX60-CS27LX1.0" => "Zemismart-LXX60-CS27LX1.0",
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
            } // End 'eqLogic' updates

            // 'config' DB updates
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                replaceConfigDB('AbeilleActiver'.$zgId, 'ab::zgEnabled'.$zgId);
                replaceConfigDB('AbeilleType'.$zgId, 'ab::zgType'.$zgId);
                replaceConfigDB('AbeilleSerialPort'.$zgId, 'ab::zgPort'.$zgId);
                replaceConfigDB('IpWifiZigate'.$zgId, 'ab::zgIpAddr'.$zgId);
                replaceConfigDB('AbeilleIEEE'.$zgId, 'ab::zgIeeeAddr'.$zgId);
                replaceConfigDB('AbeilleIEEE_Ok'.$zgId, 'ab::zgIeeeAddrOk'.$zgId);
            }
            replaceConfigDB('AbeilleParentId', 'ab::defaultParent');
            replaceConfigDB('preventLQIRequest', 'ab::preventLQIAutoUpdate');
            replaceConfigDB('monitor', 'ab::monitorId');
            config::remove('agressifTraitementAnnonce', 'Abeille');
            config::remove('blocageRecuperationEquipement', 'Abeille');
            config::remove('blocageTraitementAnnonce', 'Abeille');
            replaceConfigDB('DbVersion', 'ab::dbVersion');
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') != 'Y')
                    continue;
                if (config::byKey('ab::zgChan'.$zgId, 'Abeille', 0) != 0)
                    continue; // Channel already defined

                // What was last used channel ?
                $eqLogic = eqLogic::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
                $chan = 11; // Defaulting to channel 11
                if (is_object($eqLogic)) {
                    $cmdLogic = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'Network-Channel');
                    if (is_object($cmdLogic))
                        $chan = $cmdLogic->execCmd();
                }
                config::save('ab::zgChan'.$zgId, $chan, 'Abeille');
                log::add('Abeille', 'debug', '  Zigate '.$zgId.": Set 'ab::zgChan' to ".$chan);
            }
            // Moving user 'Network map' background image
            $nm = config::byKey('ab::networkMap', 'Abeille', 'nada');
            if ($nm == 'nada') {
                // ab::userMap=tmp/network_maps/Level0.png => ab::networkMap
                $um = config::byKey('ab::userMap', 'Abeille', 'nada');
                if ($um != 'nada') {
                    $networkMap = [];
                    $networkMap['levels'] = [];
                    $networkMap['levels'][] = array(
                        'levelName' => 'Level 0',
                        'mapDir' => dirname($um),
                        'mapFile' => basename($um)
                    );
                    $networkMap['levelChoice'] = 0;
                    config::save('ab::networkMap', $networkMap, 'Abeille');
                    log::add('Abeille', 'debug', '  Updated ab::userMap to ab::networkMap');
                } else {
                    // Neither ab::networkMap nor ab::userMap
                    $from = __DIR__."/../Network/TestSVG/images/AbeilleLQI_MapData_Perso.png";
                    if (file_exists($from)) {
                        mkdir(__DIR__."/../tmp/network_maps", 0744, true);
                        $to = __DIR__."/../tmp/network_maps/userMap.png";
                        rename($from, $to);
                        log::add('Abeille', 'debug', '  Renamed '.$from." to ".$to);
                        $networkMap = [];
                        $networkMap['levels'] = [];
                        $networkMap['levels'][] = array(
                            'levelName' => 'Level 0',
                            'mapDir' => 'tmp/network_maps',
                            'mapFile' => 'userMap.png'
                        );
                        $networkMap['levelChoice'] = 0;
                        config::save('ab::networkMap', $networkMap, 'Abeille');
                    }
                }
            }
            config::remove('ab::userMap', 'Abeille');

            // 'cmd' DB updates
            foreach ($eqLogics as $eqLogic) {
                $eqId = $eqLogic->getId();
                $cmds = Cmd::byEqLogicId($eqId);
                foreach ($cmds as $cmdLogic) {
                    $saveCmd = false;
                    $cmdLogicId = $cmdLogic->getLogicalId();
                    $cmdTopic = $cmdLogic->getConfiguration('topic', '');

                    // Removing 'calculValueOffset' for cmds '0402-XX-0000' (temperature)
                    // Removing 'calculValueOffset' for cmds '0405-XX-0000' (humidity)
                    if (preg_match("/^0402-[0-9A-F]*-0000/", $cmdLogicId) ||
                        preg_match("/^0405-[0-9A-F]*-0000/", $cmdLogicId)) {
                        $confVal = $cmdLogic->getConfiguration('calculValueOffset', '');
                        if (($confVal == "#value#\/100") || ($confVal == "#value#/100")) {
                            $cmdLogic->setConfiguration('calculValueOffset', null);
                            log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": Removed 'calculValueOffset'");
                            $saveCmd = true;
                        }
                    }
                    // Removing 'calculValueOffset' for cmds '0400-XX-0000' (illuminance)
                    if (preg_match("/^0400-[0-9A-F]*-0000/", $cmdLogicId)) {
                        $confVal = $cmdLogic->getConfiguration('calculValueOffset', 'nada');
                        if ($confVal != 'nada') {
                            $cmdLogic->setConfiguration('calculValueOffset', null);
                            log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": Removed 'calculValueOffset'");
                            $saveCmd = true;
                        }
                    }
                    // 'Batterie-Volt' => '0001-01-0020'
                    else if ($cmdLogicId == 'Batterie-Volt') {
                        $cmdLogic->setConfiguration('calculValueOffset', null);
                        $cmdLogic->setConfiguration('minValue', null);
                        $cmdLogic->setConfiguration('maxValue', null);
                        $cmdLogic->setConfiguration('visibilityCategory', 'All');
                        $cmdLogic->setLogicalId('0001-01-0020');
                        log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": Updated to '0001-01-0020'");
                        $saveCmd = true;
                    }
                    // 'Batterie-Pourcent' => '0001-01-0021'
                    else if ($cmdLogicId == 'Batterie-Pourcent') {
                        $cmdLogic->setConfiguration('calculValueOffset', null);
                        $cmdLogic->setConfiguration('minValue', null);
                        $cmdLogic->setConfiguration('maxValue', null);
                        $cmdLogic->setConfiguration('visibilityCategory', 'All');
                        $cmdLogic->setLogicalId('0001-01-0021');
                        log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": Updated to '0001-01-0021'");
                        $saveCmd = true;
                    }
                    // '0001-#EP#-0020': Updating trigOutOffset: '#value#*100\/30' => '#value#*100\/3'
                    else if (preg_match("/^0001-[0-9A-F]*-0020/", $cmdLogicId)) {
                        $cmdLogic->setConfiguration('calculValueOffset', null);
                        $trigOutOffset = $cmdLogic->getConfiguration('trigOutOffset', '');
                        if (($trigOutOffset != '') && ($trigOutOffset != '#value#*100/3')) {
                            $trigOutOffset = $cmdLogic->setConfiguration('trigOutOffset', '#value#*100/3');
                            log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": Updated trigOutOffset to '#value#*100/3'");
                            $saveCmd = true;
                        }
                    }
                    // 'setLevelStop' => 'cmd-0008'
                    else if ($cmdTopic == 'setLevelStop') {
                        $cmdLogic->setConfiguration('topic', 'cmd-0008');
                        $cmdLogic->setConfiguration('request', 'ep=01&cmd=07');
                        log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": 'setLevelStop' => 'cmd-0008'");
                        $saveCmd = true;
                    }
                    // 'WindowsCovering' => 'cmd-0102' + 'cmd=XX'
                    else if ($cmdTopic == 'WindowsCovering') {
                        $cmdLogic->setConfiguration('topic', 'cmd-0102');
                        $c = $cmdLogic->setConfiguration('request', ''); // Expecting something like 'clusterCommand=02'
                        if ($c != '') {
                            $c = substr($c, 15); // Removing 'clusterCommand='
                            $cmdLogic->setConfiguration('request', 'ep=01&cmd='.$c);
                            log::add('Abeille', 'debug', '  '.$eqId.'/'.$cmdLogicId.": 'WindowsCovering' => 'cmd-0102' + 'cmd='".$c);
                            $saveCmd = true;
                        }
                    }

                    if ($saveCmd)
                        $cmdLogic->save();
                }
            } // End 'cmd' DB updates

            // Remove obsolete log files
            $obsolete = ['AbeilleDebug.log'];
            removeLogs($obsolete);

            // Force some equipments update if model has changed.
            // Some Xiaomi devices are now managed thru 'xiaomi' JSON model structure.
            $toReload = array(
                'sensor_magnet.aq2' => '2023-01-25 14:00:00',
                'sensor_motion.aq2' => '2023-01-25 14:00:00',
                'weather' => '2023-01-25 14:00:00',
                'motion.ac02' => '2023-01-25 14:00:00',
                'switch.n0agl1' => '2023-01-25 14:00:00',
                'sensor_wleak.aq1' => '2023-01-25 14:00:00',
                'sensor_switch' => '2023-01-26 11:18:00',
                "sensor_cube" => '2023-01-30 12:38:00',
                "sensor_cube.aqgl01" => '2023-01-30 12:38:00',
                "relay.c2acn01" => '2023-02-07 12:29:00',
                "RC110_innr" => '2023-02-25 16:47:00',
                "plug" => '2023-04-07 13:20:00',
                "vibration.aq1" => '2023-03-31 14:10:00',
                "sensor_smoke" => '2023-04-04 23:07:00',
                "sensor_switch.aq2" => '2023-04-06 22:14:00',
                "remote.b1acn01" => '2023-04-06 22:14:00',
                "sensor_ht" => '2023-04-23 18:30:00',
            );
            foreach ($eqLogics as $eqLogic) {
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                if (!isset($eqModel['id']))
                    continue; // Unexpected or too old
                if (!array_key_exists($eqModel['id'], $toReload))
                    continue;

                // Check last update from model
                $modelTime = $toReload[$eqModel['id']];
                $updateTime = $eqLogic->getConfiguration('updatetime', "");
                log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": modelTime=".$modelTime.", updateTime=".$updateTime);
                if ($updateTime != '') {
                    if (strtotime($updateTime) > strtotime($modelTime))
                        continue;
                }

                list($net, $addr) = explode("/", $eqLogic->getLogicalId());
                $dev = array(
                    'net' => $net,
                    'addr' => $addr,
                    'jsonId' => $eqModel['id'],
                    'jsonLocation' => 'Abeille',
                    'ieee' => $eqLogic->getConfiguration('IEEE'),
                );
                Abeille::createDevice("update", $dev);
                log::add('Abeille', 'debug', '  '.$eqLogic->getHumanName().": Updating Jeedom equipment from model");
            }

            config::save('ab::dbVersion', '20230521', 'Abeille');
        }

        /* Internal changes
         * - Logs: AbeilleSerialReadX logs moved to /tmp/jeedom/Abeille
         * - Config DB: Removing keys for Zigates 7 to 10.
         * - eqLogic DB: Several icons renamed for normalizations purposes
         * - eqLogic DB: Removed 'ab::txAck' for devices not always listening
         * - eqLogic DB: 'ab::eqModel', sig/id/location renamed to modelSig/modelName/modelSource
         * - eqLogic DB: 'ab::eqModel', 'forcedByUser' => 'modelForced'
         * - eqLogic DB: 'ab::xiaomi' replaced by 'ab::eqModel['private']' + type=xiaomi
         * - eqLogic DB: 'groupEPx' => 'ab::eqModel['variables']'
         * - Cmds DB: For 'Online' adding 'repeatEventManagement=always'
         * - Cmds DB: 'OnOff' cmd replaced by 'cmd-0006'
         * - Cmds DB: 'OnOffGroup' replaced by 'cmd-0006'
         * - Cmds DB: 'onGroupBroadcast'/'offGroupBroadcast' replaced by 'cmd-0006'
         * - Cmds DB: 'ab::trigOut' syntax updated to associative array
         */
        if (intval($dbVersion) < 20240430) {
            // 'eqLogic' + 'cmd' DB updates
            $eqLogics = eqLogic::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                $eqId = $eqLogic->getId();
                $eqHName = $eqLogic->getHumanName();
                $zigbee = $eqLogic->getConfiguration('ab::zigbee', []);
                $rwOnWhenIdle = isset($zigbee['rwOnWhenIdle']) ? $zigbee['rwOnWhenIdle'] : 0;
                $cmds = Cmd::byEqLogicId($eqId);
                $saveEq = false;

                // eqLogic: Renaming icons if required
                $iconsList = array(
                    'eTRV0100' => 'Danfoss-Ally-Thermostat',

                    "IkeaTradfriDimmer" => "Ikea-Tradfri-Dimmer",
                    'IkeaTradfriBulbE14WSOpal400lm' => 'Ikea-BulbE14-Globe',
                    'Ikea-BulbE14CandleWhite' => 'Ikea-BulbE14-Candle',
                    'TRADFRIbulbE14Wopch400lm' => 'Ikea-BulbE14-Candle',
                    'TRADFRIbulbE14WS470lm' => 'Ikea-BulbE14-Candle',
                    'TRADFRIbulbE27WSopal1000lm' => 'Ikea-BulbE27',
                    'TRADFRIbulbE27WW806lm' => 'Ikea-BulbE27',

                    'Mhcozy-ZG-0005-RF'=> 'Mhcozy-ZG-005-RF',

                    'ProfaluxLigthModule' => 'Profalux-LigthModule',

                    'XiaomiBouton1' => 'Xiaomi-Button-3',
                    'XiaomiBouton3' => 'Xiaomi-Button-2',
                );
                $curIcon = $eqLogic->getConfiguration('ab::icon', '');
                if (($curIcon != '') && isset($iconsList[$curIcon])) {
                    $newIcon = $iconsList[$curIcon];
                    $eqLogic->setConfiguration('ab::icon', $newIcon);
                    log::add('Abeille', 'debug', '  '.$eqHName.": Icon '".$curIcon."' changed to '".$newIcon."'");
                    $saveEq = true;
                }
                // eqLogic: Removing 'ab::txAck' for devices not always listening
                if ($rwOnWhenIdle == 0) {
                    $txStatus = $eqLogic->getStatus('ab::txAck', '');
                    if ($txStatus != '') {
                        $eqLogic->setStatus('ab::txAck', NULL);
                        log::add('Abeille', 'debug', '  '.$eqHName.": Removed 'ab::txAck'");
                        $saveEq = true;
                    }
                }
                // Rename some 'ab::eqModel' keys
                $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
                $saveEqZigbee = false; // true if 'ab::zigbee' must be updated
                $saveEqModel = false; // true if 'ab::eqModel' must be updated
                $eqModelRename = array(
                    'sig' => 'modelSig',
                    'id' => 'modelName',
                    'location' => 'modelSource',
                    'forcedByUser' => 'modelForced'
                );
                foreach ($eqModelRename as $oldK => $newK) {
                    if (!isset($eqModel[$oldK]))
                        continue;
                    $eqModel[$newK] = $eqModel[$oldK];
                    unset($eqModel[$oldK]);
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'ab::eqModel' update '${oldK}' to '${newK}");
                    $saveEqModel = true;
                }
                // ab::xiaomi replaced by ab::eqModel['private'] + type=xiaomi
                $xiaomi = $eqLogic->getConfiguration('ab::xiaomi', null);
                if ($xiaomi !== null) {
                    $eqModel['private'] = [];
                    foreach ($xiaomi['fromDevice'] as $pKey => $pVal) {
                        $pVal['type'] = "xiaomi";
                        $eqModel['private'][$pKey] = $pVal;
                    }
                    $saveEqModel = true;
                    $eqLogic->setConfiguration('ab::xiaomi', null);
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'ab::xiaomi' replaced by 'private' entries");
                }
                // eqLogic DB: 'ab::tuyaEF00' => 'ab::eqModel['private']['EF00']'
                /* Reminder
                   Old syntax
                    "tuyaEF00": {
                        "fromDevice": {
                            "01": { "function": "rcvValueDiv", "info": "0006-01-0000", "div": 1 }
                        }
                    }
                   New syntax
                    "private": {
                        "EF00": {
                            "type": "tuya",
                            "01": { "function": "rcvValueDiv", "info": "0006-01-0000", "div": 1 }
                        }
                    }
                 */
                $tuyaEF00 = $eqLogic->getConfiguration('ab::tuyaEF00', 'nada');
                if ($tuyaEF00 != 'nada') {
                    $eqModel['private']['EF00'] = $tuyaEF00['fromDevice'];
                    $eqModel['private']['EF00']['type'] = "tuya";
                    $saveEqModel = true;
                    $eqLogic->setConfiguration('ab::tuyaEF00', null);
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'ab::tuyaEF00' moved to 'ab::eqModel[private][EF00]'");
                }
                // eqLogic DB: 'ab::eqModel['fromDevice'} => 'ab::eqModel['private']['ED00']'
                if (isset($eqModel['fromDevice']) && isset($eqModel['fromDevice']['ED00'])) {
                    $eqModel['private']['ED00'] = $fromDevice['ED00'];
                    $eqModel['private']['ED00']['type'] = "tuya-zosung";
                    unset($eqModel['fromDevice']);
                    $saveEqModel = true;
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'ab::eqModel[fromDevice]' moved to 'ab::eqModel[private][ED00]'");
                }
                // ab::signature content moved into 'ab::zigbee'
                $sig = $eqLogic->getConfiguration('ab::signature', null);
                if ($sig !== null) {
                    $zigbee['modelId'] = isset($sig['modelId']) ? $sig['modelId'] : '';
                    $zigbee['manufId'] = isset($sig['manufId']) ? $sig['manufId'] : '';
                    $saveEqZigbee = true;
                    $eqLogic->setConfiguration('ab::signature', null);
                    log::add('Abeille', 'debug', '  '.$eqHName.": 'ab::signature' content moved in 'ab::zigbee'");
                }
                // eqLogic DB: 'groupEPx' => 'ab::eqModel['variables']'
                for ($g = 1; $g <= 8; $g++) {
                    $tmp = $eqLogic->getConfiguration("groupEP${g}", 'nada');
                    if ($tmp == '') { // 'groupEPx' defined to ''
                        $eqLogic->setConfiguration("groupEP${g}", null);
                        $saveEq = true;
                        log::add('Abeille', 'debug', '  '.$eqHName.": 'groupEP${g}' removed (empty)");
                    } else if ($tmp != 'nada') {
                        if (!isset($eqModel['variables']))
                            $eqModel['variables'] = [];
                        $eqModel['variables']["groupEP${g}"] = $tmp;
                        $saveEqModel = true;
                        $eqLogic->setConfiguration("groupEP${g}", null);
                        $saveEq = true;
                        log::add('Abeille', 'debug', '  '.$eqHName.": 'groupEP${g}' moved to 'ab::eqModel[variables]'");
                    }
                }
                // for ($g = 1; $g <= 8; $g++) { // Removing GroupeEPx keys
                //     $tmp = $eqLogic->getConfiguration("GroupeEP${g}", 'nada');
                // }

                if ($saveEqZigbee) {
                    $eqLogic->setConfiguration('ab::zigbee', $zigbee);
                    $saveEq = true;
                }
                if ($saveEqModel) {
                    $eqLogic->setConfiguration('ab::eqModel', $eqModel);
                    $saveEq = true;
                }
                if ($saveEq)
                    $eqLogic->save();

                // cmd
                foreach ($cmds as $cmdLogic) {
                    $cmdLogicId = $cmdLogic->getLogicalId();
                    $cmdHName = $cmdLogic->getHumanName();

                    $topic = $cmdLogic->getConfiguration('topic', '');
                    if (substr($topic, 0, 10) == "CmdAbeille") {
                        $ar = explode("/", $topic);
                        $topic = $ar[2];
                    }

                    $saveCmd = false;
                    if ($cmdLogicId == 'online') {
                        if ($cmdLogic->getConfiguration('repeatEventManagement', '') == '') {
                            $cmdLogic->setConfiguration('repeatEventManagement', "always");
                            log::add('Abeille', 'debug', "  ${cmdHName}: Added 'repeatEventManagement'='always'");
                            $saveCmd = true;
                        }
                    // '0201-#EP#-0000': Removing 'calculValueOffset'
                    } else if (preg_match("/^0201-[0-9A-F]*-0000/", $cmdLogicId)) {
                        if ($cmdLogic->getConfiguration('calculValueOffset', null) !== null) {
                            $cmdLogic->setConfiguration('calculValueOffset', null);
                            log::add('Abeille', 'debug', "  ${cmdHName}: Removed 'calculValueOffset'");
                            $saveCmd = true;
                        }
                    // '0201-#EP#-0012': Removing 'calculValueOffset'
                    } else if (preg_match("/^0201-[0-9A-F]*-0012/", $cmdLogicId)) {
                        if ($cmdLogic->getConfiguration('calculValueOffset', null) !== null) {
                            $cmdLogic->setConfiguration('calculValueOffset', null);
                            log::add('Abeille', 'debug', "  ${cmdHName}: Removed 'calculValueOffset'");
                            $saveCmd = true;
                        }
                    } else if ($topic == 'OnOff') {
                        $request = $cmdLogic->getConfiguration('request', '');
                        $request = str_replace("Action=Off", "cmd=00", $request);
                        $request = str_replace("Action=On", "cmd=01", $request);
                        $request = str_replace("Action=Toggle", "cmd=02", $request);
                        $request = str_replace("EP=", "ep=", $request);
                        $cmdLogic->setConfiguration('topic', 'cmd-0006');
                        $cmdLogic->setConfiguration('request', $request);
                        log::add('Abeille', 'debug', "  ${cmdHName}: Replaced 'OnOff' by 'cmd-0006'");
                        $saveCmd = true;
                    } else if ($topic == 'OnOffGroup') {
                        $request = $cmdLogic->getConfiguration('request', '');
                        if ($request == 'Off') {
                            $request = "cmd=00&addrMode=01&addrGroup=#addrGroup#";
                            $lId = "0006-CmdOffGroup";
                        } else if ($request == 'On') {
                            $request = "cmd=01&addrMode=01&addrGroup=#addrGroup#";
                            $lId = "0006-CmdOnGroup";
                        } else { // assuming toggle
                            $request = "cmd=02&addrMode=01&addrGroup=#addrGroup#";
                            $lId = "0006-CmdToggleGroup";
                        }
                        $cmdLogic->setConfiguration('topic', 'cmd-0006');
                        $cmdLogic->setConfiguration('request', $request);
                        $cmdLogic->setLogicalId($lId);
                        log::add('Abeille', 'debug', "  ${cmdHName}: Replaced 'OnOffGroup' by 'cmd-0006'");
                        $saveCmd = true;
                    } else if (($topic == 'onGroupBroadcast') || ($topic == 'offGroupBroadcast')) {
                        $request = $cmdLogic->getConfiguration('request', '');
                        if ($request == 'Off') {
                            $request = "cmd=00&addrMode=04";
                            $lId = "0006-CmdOffGroup";
                        } else if ($request == 'On') {
                            $request = "cmd=01&addrMode=04";
                            $lId = "0006-CmdOnGroup";
                        } else { // assuming toggle
                            log::add('Abeille', 'debug', "  ${cmdHName}: ERROR: topic=${topic}, req=${request}");
                            continue;
                        }
                        $cmdLogic->setConfiguration('topic', 'cmd-0006');
                        $cmdLogic->setConfiguration('request', $request);
                        $cmdLogic->setLogicalId($lId);
                        log::add('Abeille', 'debug', "  ${cmdHName}: Replaced '${topic}' by 'cmd-0006'");
                        $saveCmd = true;
                    }

                    // Updating 'ab::trigOut' syntax to associative array
                    $confVal = $cmdLogic->getConfiguration('ab::trigOut', null);
                    if (($confVal !== null) && (gettype($confVal) == "string")) {
                        $toLogicId = $confVal;
                        $to = [];
                        $to[$toLogicId] = [];
                        $toOffset = $cmdLogic->getConfiguration('ab::trigOutOffset', '');
                        if ($toOffset != '') {
                            $to[$toLogicId]['valueOffset'] = $toOffset;
                            $cmdLogic->setConfiguration('ab::trigOutOffset', null);
                        }
                        $cmdLogic->setConfiguration('ab::trigOut', $to);
                        log::add('Abeille', 'debug', "  ${cmdHName}: 'ab::trigOut' updated to associative array");
                        $saveCmd = true;
                    }
                    $cmdLogic->setConfiguration('trigOut', null); // Removing obsolete
                    $cmdLogic->setConfiguration('trigOutOffset', null); // Removing obsolete

                    if ($saveCmd)
                        $cmdLogic->save();
                }
            }

            // 'config' DB updates
            for ($zgId = 7; $zgId <= 10; $zgId++) {
                config::remove("ab::zgChan${zgId}", 'Abeille');
                config::remove("ab::zgEnabled${zgId}", 'Abeille');
                config::remove("ab::zgIeeeAddr${zgId}", 'Abeille');
                config::remove("ab::zgIeeeAddrOk${zgId}", 'Abeille');
                config::remove("ab::zgPort${zgId}", 'Abeille');
                config::remove("ab::zgType${zgId}", 'Abeille');
                config::remove("ab::zgIpAddr${zgId}", 'Abeille');
            }

            // Remove obsolete logs
            $obsolete = [];
            for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
                $obsolete[] = "AbeilleSerialRead${zgId}.log";
            }
            removeLogs($obsolete);

            config::save('ab::dbVersion', '20240430', 'Abeille');
        }

        /* Internal changes
         * - Config DB: 'ab::zgEnabledX' => 'ab::gtwEnabledX'.
         * - Config DB: 'ab::zgTypeX' => 'ab::gtwSubTypeX'.
         * - Config DB: 'ab::zgPortX' => 'ab::gtwPortX'.
         * - Config DB: 'ab::zgIpAddrX' => 'ab::gtwIpAddrX'
         * - Config DB: 'ab::zgChanX' => 'ab::gtwChanX'
         */
        if (intval($dbVersion) < 20240503) {
            // 'config' DB updates
            for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
                replaceConfigDB("ab::zgEnabled${gtwId}", "ab::gtwEnabled${gtwId}");

                $val = config::byKey("ab::zgType${gtwId}", 'Abeille', 'nada', true);
                if ($val !== 'nada')
                    replaceConfigDB("ab::zgType${gtwId}", "ab::gtwSubType${gtwId}");

                $val = config::byKey("ab::gtwType${gtwId}", 'Abeille', 'nada', true);
                if ($val === 'nada')
                    config::save("ab::gtwType${gtwId}", "zigate", 'Abeille');

                replaceConfigDB("ab::zgPort${gtwId}", "ab::gtwPort${gtwId}");

                replaceConfigDB("ab::zgIpAddr${gtwId}", "ab::gtwIpAddr${gtwId}");

                replaceConfigDB("ab::zgChan${gtwId}", "ab::gtwChan${gtwId}");
            }

            // config::save('ab::dbVersion', '20240503', 'Abeille');
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
        log::add('Abeille', 'debug', 'Abeille_update() starting');

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
        log::add('Abeille', 'debug', 'Abeille_remove() starting');

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
