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
        
    $eqLogics = Abeille::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        if ( ($_GET['device'] == $eqLogic->getLogicalId()) || ($_GET['device'] == "All") ) {
        Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityUserCmd, "Cmd".$eqLogic->getLogicalId()."/managementNetworkUpdateRequest", "" );
        sleep(5);
        }
    }
?>
