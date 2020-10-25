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

include_once __DIR__.'/../../../core/php/core.inc.php';

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

/* Check and update configuration DB if required.
   Note: This function can be called before daemons startup. Useful for
     GIT users & developpers. */
function updateConfigDB() {

    /* Version 20200225 changes:
       - Added multi-zigate support */
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
        config::save( 'zigateNb', '1', 'Abeille' );

        config::save( 'deamonAutoMode', '1', 'Abeille' );

        config::save( 'AbeilleActiver1', 'Y', 'Abeille' );
        config::save( 'AbeilleActiver2', 'N', 'Abeille' );
        config::save( 'AbeilleActiver3', 'N', 'Abeille' );
        config::save( 'AbeilleActiver4', 'N', 'Abeille' );
        config::save( 'AbeilleActiver5', 'N', 'Abeille' );
        config::save( 'AbeilleActiver6', 'N', 'Abeille' );
        config::save( 'AbeilleActiver7', 'N', 'Abeille' );
        config::save( 'AbeilleActiver8', 'N', 'Abeille' );
        config::save( 'AbeilleActiver9', 'N', 'Abeille' );
        config::save( 'AbeilleActiver10', 'N', 'Abeille' );

        $port1 = config::byKey('AbeilleSerialPort', 'Abeille', '');
        $addr1 = config::byKey('IpWifiZigate',      'Abeille', '');
        echo "port1: ".$port1;
        echo "addr1: ".$addr1;

        if ( ($port1 == '/tmp/zigate') || ($port1 == '/dev/zigate') ) {
            config::save( 'AbeilleSerialPort1', '/dev/zigate1', 'Abeille' );
            config::save( 'IpWifiZigate1', $addr1, 'Abeille' );
        }
        else {
            config::save( 'AbeilleSerialPort1', $port1, 'Abeille' );
        }
        config::save( 'DbVersion', '20200225', 'Abeille' );
    }

    /* Version 20200510 changes:
       Added 'AbeilleTypeX' (X=1 to 10): 'USB', 'WIFI', or 'PI' */
    $dbVersion = config::byKey('DbVersion', 'Abeille', '');
    if (($dbVersion == '') || (intval($dbVersion) < 20200510)) {
        for ($i = 1; $i <= 10; $i++) {
            if (config::byKey('AbeilleActiver'.$i, 'Abeille', '') != "Y")
                continue; // Disabled or undefined

            $sp = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '');
            if ($sp == "/dev/zigate".$i)
                config::save('AbeilleType'.$i, 'WIFI', 'Abeille' );
            else if ((substr($sp, 0, 9) == "/dev/ttyS") || (substr($sp, 0, 11) == "/dev/ttyAMA"))
                config::save('AbeilleType'.$i, 'PI', 'Abeille' );
            else
                config::save('AbeilleType'.$i, 'USB', 'Abeille' );
        }
        config::save('DbVersion', '20200510', 'Abeille');
    }

    /* Version 20201025 changes:
       All hexa values are now UPPER-CASE */
    if (intval($dbVersion) < 20201025) {
        /* Updating addresses in config */
        for ($i = 1; $i <= 10; $i++) {
            $ieee = config::byKey('AbeilleIEEE'.$i, 'Abeille', '');
            if ($ieee == "")
                continue; // Undefined
            $ieee_up = strtoupper($ieee);
            if ($ieee_up !== $ieee)
                config::save('AbeilleIEEE'.$i, $ieee_up, 'Abeille' );
        }

        /* Updating addresses for all equipments Jeedom knows */
        $eqLogics = eqLogic::byType('Abeille');
        foreach ($eqLogics as $eqLogic) {
            $ieee = $eqLogic->getConfiguration('IEEE', 'undefined');
log::add('Abeille','debug','LA IEEE='.$ieee);
            if ($ieee != "undefined") {
                $ieee_up = strtoupper($ieee);
                if ($ieee_up !== $ieee) {
                    $eqLogic->setConfiguration('IEEE', $ieee_up);
                    $eqLogic->save();
log::add('Abeille','debug','LA SAVED IEEE='.$ieee_up);
                }
            }
            $topic = $eqLogic->getConfiguration('topic', 'undefined');
log::add('Abeille','debug','LA topic='.$topic);
            if ($topic != "undefined") {
                $topicArray = explode("/", $topic);
                if (ctype_xdigit($topicArray[1])) { // Convert hexa only (ex: avoid 'Ruche')
                    $topicAddr_up = strtoupper($topicArray[1]);
log::add('Abeille','debug','LA topicAddr_up='.$topicAddr_up.', topicArray[1]='.$topicArray[1]);
                    if ($topicAddr_up !== $topicArray[1]) {
                        $eqLogic->setConfiguration('topic', $topicArray[0]."/".$topicAddr_up);
                        $eqLogic->save();
log::add('Abeille','debug','LA SAVED topic='.$topicArray[0]."/".$topicAddr_up);
                    }
                }
            }
            $logId = $eqLogic->getlogicalId();
log::add('Abeille','debug','LA logId='.$logId);
            if ($logId != "") {
                $logIdArray = explode("/", $logId);
                if (ctype_xdigit($logIdArray[1])) { // Convert hexa only (ex: avoid 'Ruche')
                    $logIdAddr_up = strtoupper($logIdArray[1]);
                    if ($logIdAddr_up !== $logIdArray[1]) {
                        $eqLogic->setLogicalId($logIdArray[0]."/".$logIdAddr_up);
                        $eqLogic->save();
log::add('Abeille','debug','LA SAVED logId='.$logIdArray[0]."/".$logIdAddr_up);
                    }
                }
            }
        }
        // config::save('DbVersion', '20201025', 'Abeille');
    }
}

function Abeille_update() {

    message::add('Abeille', 'Mise à jour en cours...', null, null);

    /* Updating config DB if required */
    updateConfigDB();

    // Clean Config
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

    message::removeAll('Abeille');
    message::add('Abeille', 'Mise à jour terminée', null, null);
}

function Abeille_remove() {
    $cron = cron::byClassAndFunction('Abeille', 'deamon');
    if (is_object($cron)) {
        $cron->stop();
        $cron->remove();
    }
    log::add('Abeille','info','Suppression extension');
    $resource_path = realpath(dirname(__FILE__) . '/../resources');
    passthru('sudo /bin/bash ' . $resource_path . '/remove.sh ' . $resource_path . ' > ' . log::getPathToLog('Abeille_removal') . ' 2>&1 &');
    message::removeAll("Abeille");
    message::add("Abeille","plugin désinstallé");
}

?>
