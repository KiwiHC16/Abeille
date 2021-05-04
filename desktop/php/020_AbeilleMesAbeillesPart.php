<legend><i class="fas fa-table"></i> {{Mes Abeilles}}</legend>
<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>

<?php
    $NbOfZigatesON = 0; // Number of enabled zigates
    // $eqAll = array(); // All equipments, sorted per zigate
    for ( $i=1; $i<=$zigateNb; $i++ ) {
        if ( config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y' )
            continue; // This Zigate is not enabled

        $NbOfZigatesON++;
        if ( Abeille::byLogicalId( 'Abeille'.$i.'/0000', 'Abeille') ) {
            echo '<label style="margin: 10px 0px 0px 10px">Zigate'.$i .' - '. Abeille::byLogicalId('Abeille'.$i.'/0000', 'Abeille')->getHumanName().'</label>';
        }
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" id="bt_include'.$i.'" title="Inclusion: clic sur le plus pour mettre la zigate en inclusion."><i class="fas fa-plus-circle" style="font-size:160%;color:green !important;"></i></span>';
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" id="bt_include_stop'.$i.'" title="Inclusion: clic sur le moins pour arreter le mode inclusion."><i class="fas fa-minus-circle" style="font-size:160%;color:red !important;"></i></span>';
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" id="bt_createRemote'.$i.'" title="Clic pour créer une télécommande virtuelle."><i class="fas fa-gamepad" style="font-size:160%;color:orange !important;"></i></span>';

        /* Remove equipments from Jeedom only */
        echo '<a onclick="removeBeesJeedom('.$i.')" class="btn btn-warning btn-xs" style="margin-top: -10px; margin-left:15px" title="Supprime les équipement(s) sélectionné(s) de Jeedom uniquement.">{{Supprimer de Jeedom}}</a>';

        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            echo '<br>';
            $port = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '');

            /* Set timeout on selected equipements */
            echo '<a onclick="setBeesTimeout('.$i.')" class="btn btn-primary btn-xs" title="Permet de modifier le timeout pour les équipement(s) sélectionné(s).">{{Timeout}}</a>';

            /* Exclude feature */
            echo '<a onclick="removeBees('.$i.')" class="btn btn-primary btn-xs" style="margin-left:8px" title="Supprime les équipement(s) sélectionné(s) si fonctionnent SUR SECTEUR.">{{Exclure}}</a>';

            /* Monitoring feature */
            echo '<a onclick="monitorIt('.$i.', \''.$port.'\')" class="btn btn-primary btn-xs" style="margin-left:8px" title="Surveillance des messages vers/de l\'équipement sélectionné. Sortie dans \'AbeilleMonitor\'.">{{Surveiller}}</a>';
        }

		echo '<div class="eqLogicThumbnailContainer">';
        $dir = dirname(__FILE__) . '/../../images/';
        $files = scandir($dir);
		foreach ($eqPerZigate[$i] as $eqId) {
            $eqLogic = eqLogic::byId($eqId);
            displayBeeCard($eqLogic, $files, $i);
            // $eq = array();
            // $eq['id'] = $eqLogic->getId();
            // $eqIdList[] = $eq;
        }
        echo '<script>var js_eqZigate'.$i.' = \''.json_encode($eqPerZigate[$i]).'\';</script>';

        // $eqPerZigate = array(); // All equipements linked to current zigate
        // /* Display beehive card then bee cards */

		// foreach ($eqLogics as $eqLogic) {
        //     $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
        //     if ($eqLogicId != "Abeille".$i."/0000")
        //         continue;
        //     displayBeeCard($eqLogic, $files, $i);
        //     $eq = array();
        //     $eq['id'] = $eqLogic->getId();
        //     $eq['logicalId'] = $eqLogicId;
        //     $eqPerZigate[] = $eq;
        // }

		// foreach ($eqLogics as $eqLogic) {
        //     $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
        //     list( $net, $addr ) = explode( "/", $eqLogicId);
        //     if ( $net != 'Abeille'. $i)
        //         continue;
        //     if ($eqLogicId == "Abeille".$i."/0000")
        //         continue; // Skipping beehive
        //     displayBeeCard($eqLogic, $files, $i);
        //     $eq = array();
        //     $eq['id'] = $eqLogic->getId();
        //     $eq['logicalId'] = $eqLogicId;
        //     $eqPerZigate[] = $eq;
        // }
        // echo '<script>var js_eqZigate'.$i.' = \''.json_encode($eqPerZigate).'\';</script>';
        // $eqAll['zigate'.$i] = $eqPerZigate;
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

