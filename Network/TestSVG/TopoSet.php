<?php
    require_once __DIR__."/../../../../core/php/core.inc.php";

    echo "Php Begin - ";

    $AbeilleTopoJSON = $_POST['TopoJSON'];
    echo $AbeilleTopoJSON;

    $AbeilleTopo = json_decode($AbeilleTopoJSON, false, 512, JSON_UNESCAPED_UNICODE);

    foreach ( $AbeilleTopo as $id=>$abeille ) {

        if (is_object(eqLogic::byLogicalId( $id, 'Abeille'))) {
            $eqLogic = eqLogic::byLogicalId($id, 'Abeille');
            $eqLogic->setConfiguration('positionX',$abeille->x);
            $eqLogic->setConfiguration('positionY',$abeille->y);
            $eqLogic->save();
            echo "Position de l'abeille: ".$id." sauvegarde en BD\n";
        } else {
            echo "Objet inconnu: ".$id."\n";
        }
    }

     echo " - Php End";
?>
