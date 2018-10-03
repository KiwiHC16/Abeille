<?php
    
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
    
    $json = "[{ ";
    
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        $name = $eqLogic->getName();
        $shortAddress = str_replace("Abeille/", "", $eqLogic->getLogicalId());
        $shortAddress = ($name == 'Ruche') ? "0000" : $shortAddress;
        
        $json = $json . '"' . $shortAddress.'": { "name": "'.$name.'" }, ';
    }
    
    $json = substr($json,0,-2) . ' }] ';
    
    echo $json;
    
    
    ?>

