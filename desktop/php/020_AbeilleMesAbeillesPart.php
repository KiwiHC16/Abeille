<legend><i class="fas fa-table"></i> {{Mes Abeilles}}</legend>
<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>

<?php
    /* Display beehive or bee card */
    function displayBeeCard($eqLogic, $files, $zgId) {
        // find opacity
        $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';

        // Find icon
        $icon = $eqLogic->getConfiguration('icone', '');
        if ($icon == '')
            $icon = 'defaultUnknown';
        $icon = 'node_'.$icon.'.png';
        $iconPath = __DIR__.'/../../images/'.$icon;
        if (!file_exists($iconPath))
            $icon = 'node_defaultUnknown.png';
        // $test = 'node_' . $eqLogic->getConfiguration('icone') . '.png';
        // if (in_array($test, $files, 0)) {
        //     $path = 'node_' . $eqLogic->getConfiguration('icone');
        // } else {
        //     $path = 'Abeille_icon';
        // }

        // Affichage
        $id = $eqLogic->getId();
        echo '<div>';
        echo    '<input id="idBeeChecked'.$zgId.'-'.$id.'" type="checkbox" name="eqSelected-'.$id.'" />';
        echo 	'<br/>';
        echo 	'<div class="eqLogicDisplayCard cursor'.$opacity.'" style="width: 130px" data-eqLogic_id="' .$id .'">';
        echo 		'<img src="plugins/Abeille/images/'.$icon.'" />';
        echo 		'<br/>';
        echo 		'<span class="name">'. $eqLogic->getHumanName(true, true) .'</span>';
        echo 	'</div>';
        echo '</div>';
    }

    $NbOfZigatesON = 0; // Number of enabled zigates
    // $eqAll = array(); // All equipments, sorted per zigate
    for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
        if ( config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') != 'Y' )
            continue; // This Zigate is not enabled

        $NbOfZigatesON++;
        if ( Abeille::byLogicalId( 'Abeille'.$zgId.'/0000', 'Abeille') ) {
            echo '<label style="margin: 10px 0px 0px 10px">Réseau Abeille'.$zgId.'</label>';
        }
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" id="bt_include'.$zgId.'" title="Inclusion: clic sur le plus pour mettre la zigate en inclusion."><i class="fas fa-plus-circle" style="font-size:160%;color:green !important;"></i></span>';
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" id="bt_include_stop'.$zgId.'" title="Inclusion: clic sur le moins pour arreter le mode inclusion."><i class="fas fa-minus-circle" style="font-size:160%;color:red !important;"></i></span>';
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" onclick="createRemote('.$zgId.')" title="Clic pour créer une télécommande virtuelle."><i class="fas fa-gamepad" style="font-size:160%;color:orange !important;"></i></span>';

        /* Remove equipments from Jeedom only */
        echo '<a onclick="removeBeesJeedom('.$zgId.')" class="btn btn-warning btn-xs" style="margin-top: -10px; margin-left:15px" title="Supprime les équipement(s) sélectionné(s) de Jeedom uniquement.">{{Supprimer de Jeedom}}</a>';

        /* Exclude feature */
        echo '<a onclick="removeBees('.$zgId.')" class="btn btn-warning btn-xs" style="margin-top:-10px; margin-left:8px" title="Demande aux équipement(s) sélectionné(s) de sortir du réseau.">{{Exclure}}</a>';

        $port = config::byKey('AbeilleSerialPort'.$zgId, 'Abeille', '');

        /* Monitoring feature */
        echo '<a onclick="monitorIt('.$zgId.', \''.$port.'\')" class="btn btn-warning btn-xs" style="margin-top:-10px; margin-left:8px" title="Surveillance des messages vers/de l\'équipement sélectionné. Sortie dans \'AbeilleMonitor\'.">{{Surveiller}}</a>';

        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            // echo '<br>';

            /* Set timeout on selected equipements */
            echo '<a onclick="setBeesTimeout('.$zgId.')" class="btn btn-primary btn-xs" style="margin-top:-10px; margin-left:8px" title="Permet de modifier le timeout pour les équipement(s) sélectionné(s).">{{Timeout}}</a>';
        }

		echo '<div class="eqLogicThumbnailContainer">';
        $dir = dirname(__FILE__) . '/../../images/';
        $files = scandir($dir);
        // Display beehive first
		foreach ($eqPerZigate[$zgId] as $eq) {
            if ($eq['addr'] != '0000')
                continue; // It's not a zigate
            $eqId = $eq['id'];
            $eqLogic = eqLogic::byId($eqId);
            displayBeeCard($eqLogic, $files, $zgId);
        }
        // Display attached equipments
		foreach ($eqPerZigate[$zgId] as $eq) {
            if ($eq['addr'] == '0000')
                continue; // It's a zigate
            $eqId = $eq['id'];
            $eqLogic = eqLogic::byId($eqId);
            displayBeeCard($eqLogic, $files, $zgId);
        }
        echo '<script>var js_eqZigate'.$zgId.' = \''.json_encode($eqPerZigate[$zgId]).'\';</script>';
        echo ' </div>';
    }

    if ($NbOfZigatesON == 0) { // No Zigate to display. UNEXPECTED !
        echo "<div style=\"background: #e9e9e9; font-weight: bold; padding: .2em 2em;\"><br>";
            echo "<span style=\"color:red\">";
                echo "Aucune Zigate n'est activée !";
            echo "</span><br>";
            echo "Veuillez aller à la page de configuration pour corriger.<br><br>";
        echo "</div>";
    }
?>

