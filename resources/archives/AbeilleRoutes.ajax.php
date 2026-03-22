<?php

    /***
     *
     *
     */

    include_once __DIR__."/../../../../core/php/core.inc.php";
    include_once("../class/AbeilleTools.class.php");
    include_once("../config/Abeille.config.php");

    $queueId = $abQueues["xToCmd"]['id'];

    // Je ne filtre pas sur les équipements sur batterie car je ne sais pas s'ils repondent. Je suppose que non mais on ne sait jamais.
    $eqLogics = Abeille::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        if ( ($_GET['device'] == $eqLogic->getLogicalId()) || ($_GET['device'] == "All") ) {
            if ( $eqLogic->getConfiguration('battery_type', 'none') == 'none' )
                continue; // on n interroge pas les equipements sur batterie

            list($net, $addr) = explode('/', $eqLogic->getLogicalId());
            Abeille::publishMosquitto($queueId, PRIO_NORM, "Cmd".$net."/".$addr."/getRoutingTable", "");
            sleep(3);
        }
    }
?>
