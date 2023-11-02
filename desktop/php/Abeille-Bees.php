<legend><i class="fas fa-table"></i> {{Mes Abeilles}}</legend>
<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>

<style>
    .iconRatio {
        max-width: 130px;
        width:auto;
    }
    .iconZ1
    {
      position:relative;
      top: 0px;
      /* left: 10px; */
      z-index: 1;
    }
    .iconZ2
    {
        position:relative;
        /* align: top; */
      top: -99px; /* Why 99 ? */
      left: 0px;
      z-index: 2;
    }
</style>

<?php
    /* Display beehive or bee card */
    function displayBeeCard($eqLogic, $files, $zgId) {
        // find opacity
        $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';

        // Find icon
        $icon = $eqLogic->getConfiguration('ab::icon', '');
        if ($icon == '')
            $icon = 'defaultUnknown';
        $icon = 'node_'.$icon.'.png';
        $iconPath = __DIR__.'/../../images/'.$icon;
        if (!file_exists($iconPath))
            $icon = 'node_defaultUnknown.png';
        // $test = 'node_' . $eqLogic->getConfiguration('ab::icon') . '.png';
        // if (in_array($test, $files, 0)) {
        //     $path = 'node_' . $eqLogic->getConfiguration('ab::icon');
        // } else {
        //     $path = 'Abeille_icon';
        // }

        // Affichage
        $id = $eqLogic->getId();
        echo '<div>';
        echo    '<input id="idBeeChecked'.$zgId.'-'.$id.'" class="beeChecked" type="checkbox" name="eqSelected-'.$id.'" />';
        echo 	'<br/>';
        echo 	'<div class="eqLogicDisplayCard cursor '.$opacity.'" style="width: 130px" data-eqLogic_id="'.$id.'">';
        echo 		'<img src="plugins/Abeille/images/'.$icon.'" />';
        // echo 		'<img src="plugins/Abeille/images/'.$icon.'" class="iconRatio iconZ1" />';
        // echo 		'<img src="plugins/Abeille/images/disabled.png" align=top class="iconZ2" />';
        echo 		'<br/>';
        // echo 		'<span class="name">'. $eqLogic->getHumanName(true, true) .'</span>';
        echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
        echo '<span class="hiddenAsCard displayTableRight hidden">';
        echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
        echo '</span>';
        echo 	'</div>';
        echo '</div>';
    }

    $NbOfZigatesON = 0; // Number of enabled zigates
    // $eqAll = array(); // All equipments, sorted per zigate
    for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
        if ( config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') != 'Y' )
            continue; // This Zigate is not enabled

        $NbOfZigatesON++;
        if ( Abeille::byLogicalId( 'Abeille'.$zgId.'/0000', 'Abeille') ) {
            echo '<label style="margin: 10px 0px 0px 10px">{{Réseau Abeille}}'.$zgId.'</label>';
        }
        echo "&nbsp&nbsp&nbsp";
        // echo '<span class="cursor" id="bt_include'.$zgId.'" title="Inclusion: clic sur le plus pour mettre la zigate en inclusion."><i class="fas fa-plus-circle" style="font-size:160%;color:green !important;"></i></span>';
        echo '<a class="fas fa-plus-circle" style="font-size:160%;color:green !important;" onclick="sendToCmd(\'startPermitJoin\','.$zgId.')" title="{{Activation du mode inclusion}}"></a>';
        echo "&nbsp&nbsp&nbsp";
        // echo '<span class="cursor" id="bt_include_stop'.$zgId.'" title="Inclusion: clic sur le moins pour arreter le mode inclusion."><i class="fas fa-minus-circle" style="font-size:160%;color:red !important;"></i></span>';
        echo '<a class="fas fa-minus-circle" style="font-size:160%;color:red !important;" onclick="sendToCmd(\'stopPermitJoin\','.$zgId.')" title="{{Arret du mode inclusion}}"></a>';
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" onclick="createRemote('.$zgId.')" title="Clic pour créer une télécommande virtuelle."><i class="fas fa-gamepad" style="font-size:160%;color:orange !important;"></i></span>';

        /* Remove equipments from Jeedom only */
        echo '<a onclick="removeBeesJeedom('.$zgId.')" class="btn btn-warning btn-xs" style="margin-top: -10px; margin-left:15px" title="Supprime les équipement(s) sélectionné(s) de Jeedom uniquement.">{{Supprimer de Jeedom}}</a>';

        /* Exclude feature */
        echo '<a onclick="removeBees('.$zgId.')" class="btn btn-warning btn-xs" style="margin-top:-10px; margin-left:8px" title="Demande aux équipement(s) sélectionné(s) de sortir du réseau.">{{Exclure}}</a>';

        $port = config::byKey('ab::zgPort'.$zgId, 'Abeille', '');

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
                echo "{{Aucune Zigate n'est activée !}}";
            echo "</span><br>";
            echo "{{Veuillez aller à la page de configuration pour corriger.}}<br><br>";
        echo "</div>";
    }
?>

<script>
</script>