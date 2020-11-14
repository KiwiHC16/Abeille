<?php

    /* This file is part of Plugin abeille for jeedom.
    *
    * Plugin abeille for jeedom is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * Plugin abeille for jeedom is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with Plugin abeille for jeedom. If not, see <http://www.gnu.org/licenses/>.
    */

    /*
    * Targets for AJAX's requests
    */

    /* Errors reporting: uncomment below lines for debug */
    error_reporting(E_ALL);
    ini_set('error_log', '/var/www/html/log/AbeillePHP');
    ini_set('log_errors', 'On');

    function logToFile($logFile = '', $logLevel = 'NONE', $msg = "")
    {
        if (AbeilleTools::getNumberFromLevel($logLevel) > AbeilleTools::getPluginLogLevel('Abeille'))
            return; // Nothing to do

        $logDir = __DIR__.'/../../../../log/';
        /* TODO: How to align logLevel width for better visual aspect ? */
        file_put_contents($logDir.$logFile, '['.date('Y-m-d H:i:s').']['.$logLevel.'] '.$msg."\n", FILE_APPEND);
    }

    try {

        require_once __DIR__.'/../../../../core/php/core.inc.php';
        require_once __DIR__.'/../class/Abeille.class.php';
        require_once __DIR__.'/../php/AbeilleZigate.php';
        include_once __DIR__.'/../../resources/AbeilleDeamon/lib/Tools.php'; // deamonlogFilter()

        include_file('core', 'authentification', 'php');
        if (!isConnect('admin')) {
            throw new Exception('401 Unauthorized');
        }

        ajax::init();

        /* Clean beehive */
        if (init('action') == 'cleanBees') {
            $zgPort = init('zgPort'); // Zigate port
            $zgNb = init('zgNb'); // Zigate number

            $status = 0;

            /* Creating temp dir & log */
            $tmp = __DIR__.'/../../tmp';
            if (file_exists($tmp) == FALSE)
                mkdir($tmp);
            $log = $tmp.'/cleanBees.log';

            file_put_contents($log, 'Nettoyage de la zigate '.$zgNb." (port ".$zgPort.")\n");
            file_put_contents($log, "Arret des démons\n", FILE_APPEND);
            abeille::deamon_stop(); // Stopping daemon

            zgSetConf($log); // Zigate lib init

            /* Listing devices known to Zigate */
            file_put_contents($log, "Récupération des périphs connus de la Zigate\n", FILE_APPEND);
            $status = zgGetDevicesList($zgPort, $zgDevices);
            if ($status != 0) {
                file_put_contents($log, "= ERREUR: Impossible de récupérer la liste.\n", FILE_APPEND);
            } else {
                foreach ($zgDevices as $dev) {
                    file_put_contents($log, "- adresse=".$dev['addr'].", ieee=".$dev['ieee']."\n", FILE_APPEND);
                }
            }

            /* Listing devices known to Jeedom */
            $jDevices = array(); // List of devices known to Jeedom for current zigate
            if ($status == 0) {
                file_put_contents($log, "Récupération des périphs connus de Jeedom\n", FILE_APPEND);
                $abeilleName = "Abeille".$zgNb;
                $s = strlen($abeilleName);
                $eqLogics = eqLogic::byType('Abeille');
                foreach ($eqLogics as $eq) {
                    $logicalId = $eq->getLogicalId();
                    if (substr($logicalId, 0, $s) != $abeilleName)
                        continue; // Not the right zigate
                    $addr = substr($logicalId, $s + 1); // Short addr = remove 'AbeilleX/'
                    if (($addr == "0000") || ($addr == "Ruche"))
                        continue; // Skip zigate itself
                    $jDev['addr'] = strtoupper($addr);
                    $jDev['ieee'] = strtoupper($eq->getConfiguration('IEEE', 'none'));
                    $jDev['eq'] = $eq;
                    $jDevices[] = $jDev;
                    file_put_contents($log, "- adresse=".$jDev['addr'].", ieee=".$jDev['ieee']."\n", FILE_APPEND);
                }
            }

            /* Comparing & attempting to resolve issues */
            file_put_contents($log, "Vérifications & corrections\n", FILE_APPEND);

            /* Any device in Zigate but not in Jeedom ? */
            $nbErrors = 0; // Number of errors
            $nbErrCorrected = 0; // Number of corrected errors
            foreach ($zgDevices as $zgDev) {
                $found = FALSE;
                foreach ($jDevices as $jDev) {
                    if ($jDev['addr'] == $zgDev['addr']) {
                        $found = TRUE;
                        break;
                    }
                }
                if ($found == TRUE) {
                    if ($jDev['ieee'] == 'NONE') {
                        $jDev['eq']->setConfiguration('IEEE', $zgDev['ieee']);
                        $jDev['eq']->save();
                        file_put_contents($log, "- ".$zgDev['addr']." n'a pas d'adresse IEEE côté Jeedom => corrigé\n", FILE_APPEND);
                        $nbErrors++;
                        $nbErrCorrected++;
                    }
                    continue;
                }

                /* Device with '$addr' is unknown to Jeedom.
                   But is the IEEE address known ? If yes means reinclusion appeared but Jeedom not updated */
                $nbErrors++;
                $found = FALSE;
                foreach ($jDevices as $jDev) {
                    if ($jDev['ieee'] == 'NONE')
                        continue;
                    if ($jDev['ieee'] == $zgDev['ieee']) {
                        $found = TRUE;
                        break;
                    }
                }
                if ($found == TRUE) {
                    /* WARNING: Could it be possible that Jeedom has 2 devices with same IEEE ? */
                    // $jDev['eq']->setConfiguration('IEEE', $zgDev['ieee']);
                    // $jDev['eq']->save();
                    file_put_contents($log, "- ".$zgDev['addr']." est connu de Jeedom avec une autre adresse courte.\n", FILE_APPEND);
                    // $nbErrCorrected++;
                } else
                    file_put_contents($log, "- ".$zgDev['addr']." n'est pas connu de Jeedom\n", FILE_APPEND);
            }

            /* Any device in Jeedom but not in Zigate ? */
            foreach ($jDevices as $jDev) {
                $found = FALSE;
                foreach ($zgDevices as $zgDev) {
                    if ($jDev['addr'] == $zgDev['addr']) {
                        $found = TRUE;
                        break;
                    }
                }
                if ($found == TRUE)
                    continue;
                file_put_contents($log, "- ".$jDev['addr']." n'est pas connu de la Zigate\n", FILE_APPEND);
                $nbErrors++;
            }
            if ($nbErrors == 0)
                file_put_contents($log, "= Aucune erreur trouvée.\n", FILE_APPEND);
            else
                file_put_contents($log, "= ".$nbErrors." erreur(s) trouvée(s), ".$nbErrCorrected." corrigée(s).\n", FILE_APPEND);

            file_put_contents($log, "Redémarrage des démons\n", FILE_APPEND);
            abeille::deamon_start(); // Restarting daemon

            ajax::success(json_encode(array('status' => $status)));
        }

        throw new Exception('Aucune methode correspondante');
    } catch (Exception $e) {
        ajax::error(displayExeption($e), $e->getCode());
    }
?>
