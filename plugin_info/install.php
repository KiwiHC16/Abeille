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
    $dbgFile = __DIR__."/../tmp/debug.json";
    if (file_exists($dbgFile)) {
        $dbgDeveloperMode = TRUE;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../../../core/php/core.inc.php';
    require_once __DIR__.'/../core/php/AbeillePreInstall.php';

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
            $logId = $eqLogic->getlogicalId();
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

    /* Version 20201122 changes:
       - blocageTraitementAnnonce defaulted to "Non"
       - agressifTraitementAnnonce defaulted to 4
       - Rename all eq names that use short addr to use Jeedom ID instead. */
    if (intval($dbVersion) < 20201122) {
        if (config::byKey('blocageTraitementAnnonce', 'Abeille', 'none', 1) == "none") {
            config::save('blocageTraitementAnnonce', 'Non', 'Abeille') ;
        }
        if ( config::byKey( 'blocageRecuperationEquipement', 'Abeille', 'none', 1 ) == "none" ) {
            config::save( 'blocageRecuperationEquipement', 'Oui', 'Abeille' ) ;
        }
        if ( config::byKey( 'agressifTraitementAnnonce', 'Abeille', 'none', 1 ) == "none" ) {
            config::save( 'agressifTraitementAnnonce', '4', 'Abeille' ) ;
        }

        $eqLogics = eqLogic::byType('Abeille');
        foreach ($eqLogics as $eqLogic) {
            $logicId = $eqLogic->getlogicalId();
            $logicIdArray = explode("/", $logicId);
            if (!ctype_xdigit($logicIdArray[1]))
                continue; // Not an hexa string so might be "Ruche"
            $network = $logicIdArray[0]; // 'AbeilleX'
            $addr = $logicIdArray[1]; // Get short addr
            $name = $eqLogic->getName();
            $id = $eqLogic->getId(); // Get Jeedom ID for this equipment
            $pos = stripos($name, $addr); // Any short addr in the name ?
            if ($pos == FALSE) {
                /* Current short addr no found in name but could it be
                   one that missed an update so keeping an old short addr ?
                   So updating only if format is "AbeilleX-YYYY..." which
                   sounds like the previous default naming. */
                $l = strlen($network);
                if (!substr_compare($name, $network, 0, $l)) {
                    /* Name starts with "AbeilleX" */
                    $a = substr($name, $l + 1, 4);
                    if ((strlen($a) == 4) && ctype_xdigit($a)) {
                        /* 4 hexa digits found. Assuming an old short addr */
                        $pos = $l + 1;
                    }
                }
            }
            if ($pos != FALSE) {
                $name = substr_replace($name, $id, $pos, 4);
                $eqLogic->setName($name);
                $eqLogic->save();
            }
        }

    //    config::save('DbVersion', '20201122', 'Abeille');
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

        $error = FALSE;

        /* Collect Abeille's version */
        $file = fopen(__DIR__."/Abeille.version", "r");
        $line = fgets($file); // Should be a comment
        $version = trim(fgets($file));
        fclose($file);

        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            if (validMd5Exists() == 0) {
                if (checkIntegrity() != 0) {
                    message::add('Abeille', 'Fichiers corrompus detectés ! La version \''.$version.'\' est mal installée. Problème de place ?', null, null);
                    $error = TRUE;
                } else
                    doPostUpdateCleanup();
            }
        }

        /* Updating config DB if required */
        updateConfigDB();

        // Clean Config
        // TODO Tcharp38: Should be moved to updateConfigDB()
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

        if ($error == FALSE) {
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
    log::add('Abeille','info','Suppression extension');
    $resource_path = realpath(dirname(__FILE__) . '/../resources');
    passthru('sudo /bin/bash ' . $resource_path . '/remove.sh ' . $resource_path . ' > ' . log::getPathToLog('Abeille_removal') . ' 2>&1 &');
    message::removeAll("Abeille");
    // message::add("Abeille","plugin désinstallé");
}
?>
