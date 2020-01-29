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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

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

function Abeille_update() {

    message::add('Abeille', 'Mise à jour en cours...', null, null);

    // Clean Config
    config::remove('AbeilleSerialPort', 'Abeille');
    config::remove('affichageCmdAdd', 'Abeille');
    config::remove('affichageNetwork', 'Abeille');
    config::remove('affichageTime', 'Abeille');
    config::remove('IpWifiZigate', 'Abeille');
    
    
    // Passe les abeilles de "Abeille" à "Abeille1"
    foreach ( Abeille::byType('Abeille') as $abeilleNumber=>$abeille ) {
        if (preg_match("(Abeille/)", $abeille->getLogicalId())) {
            // echo $abeille->getLogicalId() . " -> " . str_replace("Abeille/", "Abeille1/", $abeille->getLogicalId()) . "\n";
            $abeille->setName(str_replace("Abeille-", "Abeille1-", $abeille->getName()));
            $abeille->setLogicalId(str_replace("Abeille/", "Abeille1/", $abeille->getLogicalId()));
            echo $abeille->getConfiguration('topic') . " -> " . str_replace('Abeille/', 'Abeille1/', $abeille->getConfiguration('topic')) . "\n";
            $abeille->setConfiguration( 'topic', str_replace('Abeille/', 'Abeille1/', $abeille->getConfiguration('topic')));
            $abeille->save();
        }
    }
    
    
    
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

    Abeille_update();
?>
