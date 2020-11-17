<legend><i class="fa fa-table"></i> {{Mes Abeilles}}</legend>
    <form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post"> 

<?php


    $NbOfZigatesON = 0; // Number of enabled zigates
    $eqAll = array(); // All equipments, sorted per zigate
    for ( $i=1; $i<=$zigateNb; $i++ ) {
        if ( config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y' )
            continue; // This Zigate is not enabled

        $NbOfZigatesON++;
        if ( Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille') ) {
            echo 'Zigate'.$i .' - '. Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')->getHumanName();
        }
        echo "&nbsp&nbsp&nbsp";
        echo '<i id="bt_include'.$i.'" class="fa fa-plus-circle" style="font-size:160%;color:green" title="Inclusion: clic sur le plus pour mettre la zigate en inclusion."></i>';
        echo "&nbsp&nbsp&nbsp";
        echo '<i id="bt_include_stop'.$i.'" class="fa fa-minus-circle" style="font-size:160%;color:red" title="Inclusion: clic sur le moins pour arreter le mode inclusion."></i>';
        echo "&nbsp&nbsp&nbsp";
        echo '<i id="bt_createRemote'.$i.'"class="fa fa-gamepad" style="font-size:160%;color:orange" title="Clic pour créer une télécommande virtuelle."></i>';

        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            echo '<br>';
            $port = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '');

            /* Remove equipments from Jeedom only */
            echo '<a onclick="removeBeesJeedom('.$i.')" class="btn btn-primary btn-xs" title="Supprime les équipement(s) sélectionné(s) de Jeedom uniquement.">{{Supprimer de Jeedom}}</a>';

            /* Set timeout on selected equipements */
            echo '<a onclick="setBeesTimeout('.$i.')" class="btn btn-primary btn-xs" style="margin-left:8px" title="Permet de modifier le timeout pour les équipement(s) sélectionné(s).">{{Timeout}}</a>';
        }

        echo '<div class="eqLogicThumbnailContainer">';
        $dir = dirname(__FILE__) . '/../../images/';
        $files = scandir($dir);
        $eqPerZigate = array(); // All equipements linked to current zigate
        /* Display beehive card then bee cards */
        foreach ($eqLogics as $eqLogic) {
            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/Ruche'
            if ($eqLogicId != "Abeille".$i."/Ruche")
                continue;
            displayBeeCard($eqLogic, $files, $i);
            $eq = array();
            $eq['id'] = $eqLogic->getId();
            $eq['logicalId'] = $eqLogicId;
            $eqPerZigate[] = $eq;
        }
        foreach ($eqLogics as $eqLogic) {
            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/Ruche'
            list( $net, $addr ) = explode( "/", $eqLogicId);
            if ( $net != 'Abeille'. $i)
                continue;
            if ($eqLogicId == "Abeille".$i."/Ruche")
                continue; // Skipping beehive
            displayBeeCard($eqLogic, $files, $i);
            $eq = array();
            $eq['id'] = $eqLogic->getId();
            $eq['logicalId'] = $eqLogicId;
            $eqPerZigate[] = $eq;
        }
        echo '<script>var js_eqZigate'.$i.' = \''.json_encode($eqPerZigate).'\';</script>';
        $eqAll['zigate'.$i] = $eqPerZigate;
        echo ' </div>';
    }

    if ($NbOfZigatesON == 0) { // No Zigate to display. UNEXPECTED !
        echo "<div style=\"background: #e9e9e9; font-weight: bold; padding: .2em 2em;\"><br>";
        echo "   <span style=\"color:red\">";
        if ($zigateNb == 0)
            echo "Aucune Zigate n'est définie !";
        else
            echo "Aucune Zigate n'est activée !";
        echo "   </span><br>";
        echo "    Veuillez aller à la page de configuration pour corriger.<br><br>";
        echo "</div>";
    }
?>
