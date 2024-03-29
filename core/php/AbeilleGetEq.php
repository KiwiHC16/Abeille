<?php
    require_once __DIR__."/../../../../core/php/core.inc.php";

    $table = array();

    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
        $eqParent = $eqLogic->getObject();
        if (!is_object($eqParent))
            $objName = "";
        else
            $objName = $eqParent->getName();
        $device['objectName']   = $objName;
        $device['name']         = $eqLogic->getName();
        $device['logicalId']    = $eqLogic->getLogicalId();
        $device['id']           = $eqLogic->getId();
        $settings = $eqLogic->getConfiguration('ab::settings', []);
        $device['x']            = isset($settings['physLocationX']) ? $settings['physLocationX'] : 0;
        $device['y']            = isset($settings['physLocationY']) ? $settings['physLocationY'] : 0;
        $device['z']            = isset($settings['physLocationZ']) ? $settings['physLocationZ'] : 0; // Level

        $table[$eqLogic->getLogicalId()] = $device;
        unset( $device );
    }

    echo json_encode($table);
?>
