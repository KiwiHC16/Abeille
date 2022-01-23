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
    include_once __DIR__.'/../../core/config/Abeille.config.php';
    include_once __DIR__.'/../../core/class/AbeilleTools.class.php';

    // Exemple d appel: php AbeilleInterrogate.php Abeille1 49d6
    // Sur une demande sans réponse, le zigate en V3.1b envoie 4 demandes. Chaque demande est toutes les 1.6s.
    // Sur une demande Abeille, la zigate envoie une demande sur la radio à T0, T0+1.6s, T0+3.2s, T0+4.8s
    // Si mes souvenirs sont bons la reco est 7s de buffer sur les routeurs. Donc dernier message plus 7s = T0+4.8s+7s = T0+12s
    // Donc envoyer un message toutes les 12s max pour eviter l overflow.

    $timeOut = 60; // min
    $timeEnd = time() + $timeOut*60;

    $dest = $argv[1];
    $addressShort = $argv[2];

    while ( time() < $timeEnd ) {
        Abeille::publishMosquitto(queueKeyAbeilleToCmd, priorityInterrogation, "Cmd".$dest."/".$addressShort."/getIeeeAddress", "");
        echo ".";
        sleep( 12 );
    }
?>
