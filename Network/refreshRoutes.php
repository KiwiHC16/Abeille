<?php

    /***
     *
     *
     */

    include_once __DIR__."/../../../core/php/core.inc.php";
    include_once("../core/class/AbeilleTools.class.php");
    include_once("../core/config/Abeille.config.php");
    include_once("../resources/AbeilleDeamon/includes/fifo.php");
    include_once("../resources/AbeilleDeamon/includes/function.php");

    // Je ne filtre pas sur les équipements sur batterie car je ne sais pas s'ils repondent. Je suppose que non mais on ne sait jamais.
    $eqLogics = Abeille::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        if ( ($_GET['device'] == $eqLogic->getLogicalId()) || ($_GET['device'] == "All") ) {
            if ( $eqLogic->getConfiguration('battery_type', 'none') == 'none' ) { // on n interroge pas les equipements sur batterie
                list($type, $address, $action) = explode('/', $eqLogic->getLogicalId());
                if ( $address == "Ruche" ) $address = "0000"; // Ajout de la ruche.
                if ( strlen($address) == 4 ) {
                    Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityUserCmd, "Cmd".$type."/".$address."/Mgmt_Rtg_req", "" );
                    sleep(5);
                }
            }
        }
    }
?>
