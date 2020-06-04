<?php
    
    /***
     * LQI
     *
     * Send LQI request to the network
     * Process 804E answer messages
     * to draw LQI (Link Quality Indicator)
     * to draw NE Hierarchy
     *
     */
    
    include_once dirname(__FILE__) . "/../../../core/php/core.inc.php";
    include_once("../resources/AbeilleDeamon/lib/Tools.php");
    include_once("../resources/AbeilleDeamon/includes/config.php");
    include_once("../resources/AbeilleDeamon/includes/fifo.php");
    include_once("../resources/AbeilleDeamon/includes/function.php");
        
    // Je ne filtre pas sur les équipements qur batterie car j ai eu une réponse d'un interrupteur mural xiaomi. En les reveillant ca se trouve ils repondent.
    $eqLogics = Abeille::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        if ( ($_GET['device'] == $eqLogic->getLogicalId()) || ($_GET['device'] == "All") ) {
            list($type, $address, $action) = explode('/', $eqLogic->getLogicalId());
            if ( strlen($address) == 4 ) {
                Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityUserCmd, "Cmd".$eqLogic->getLogicalId()."/managementNetworkUpdateRequest", "" );
                sleep(5);
            }
        }
    }
?>
