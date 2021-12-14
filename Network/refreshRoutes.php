<?php

    /***
     *
     *
     */

    include_once __DIR__."/../../../core/php/core.inc.php";
    include_once("../core/class/AbeilleTools.class.php");
    include_once("../core/config/Abeille.config.php");

    // Je ne filtre pas sur les équipements sur batterie car je ne sais pas s'ils repondent. Je suppose que non mais on ne sait jamais.
    $eqLogics = Abeille::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        if ( ($_GET['device'] == $eqLogic->getLogicalId()) || ($_GET['device'] == "All") ) {
            if ( $eqLogic->getConfiguration('battery_type', 'none') == 'none' )
                continue; // on n interroge pas les equipements sur batterie

            list($net, $addr) = explode('/', $eqLogic->getLogicalId());
            if ( strlen($addr) == 4 ) {
                // Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityUserCmd, "Cmd".$net."/".$addr."/getRoutingTable", "");
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, PRIO_NORM, "Cmd".$net."/".$addr."/getRoutingTable", "");
                sleep(3);
            }
        }
    }
?>
