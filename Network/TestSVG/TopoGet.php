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
        $device['X']            = max( 0, $eqLogic->getConfiguration('positionX'));
        $device['Y']            = max( 0, $eqLogic->getConfiguration('positionY'));

        $table[$eqLogic->getLogicalId()] = $device;
        unset( $device );
    }

    echo json_encode($table);
?>
