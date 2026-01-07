<legend><i class="fas fa-table"></i> {{Mes Abeilles}}</legend>
<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>

<style>
    /* .iconRatio {
        max-width: 130px;
        width:auto;
    } */
    /* .iconZ1
    {
        position:relative;
        top: 0px;
        / * left: 10px; * /
        z-index: 1;
    } */
    /* .iconZ2
    {
        position:relative;
        top: -99px; / * Why 99 ? * /
        left: 0px;
        z-index: 2;
    } */

    .eqLogicDisplayCard.cursor /* Not used */
    {
        height:180px!important;
    }
    .div.eqLogicDisplayCard.cursor /* Not used */
    {
        height:200px!important;
    }
</style>

<?php
    /* Display beehive or bee card */
    // function displayBeeCard($eqLogic, $zgId, $eqId) {
    function displayBeeCard($zgId, $eqId) {

        $eq = $GLOBALS['eqPerZigate'][$zgId][$eqId];

        // find opacity
        // $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
        $opacity = ($eq['isEnabled']) ? '' : 'disableCard';

        // Find icon
        // $icon = $eqLogic->getConfiguration('ab::icon', '');
        $icon = $eq['icon'];
        if ($icon == '')
            $icon = 'defaultUnknown';
        $icon = 'node_'.$icon.'.png';
        $iconPath = __DIR__.'/../../core/config/devices_images/'.$icon;
        if (!file_exists($iconPath))
            $icon = 'node_defaultUnknown.png';

        // $eqId = $eqLogic->getId();
        // Note: idBeeChecked needs a special code to be filtered out when using the search bar
        echo 	'<div>';
        echo        '<input id="idBeeChecked'.$zgId.'-'.$eqId.'" class="beeChecked" type="checkbox" name="eqSelected-'.$eqId.'" />';
        echo 	    '<br/>';
        echo 	    '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="'.$eqId.'">';
        echo 		    '<img src="plugins/Abeille/core/config/devices_images/'.$icon.'" />';
        echo 		    '<br/>';
        // echo            '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
        echo            '<span class="name">';
        echo                '<span class="label labelObjectHuman">'.$eq['pName'].'</span><br>';
        echo                '<strong>'.$eq['name'].'</strong>';
        echo            '</span>';
        // <span class="name"><span class="label labelObjectHuman"> C1</span><br><strong> Lumière C1</strong></span>
        echo            '<span class="hiddenAsCard displayTableRight hidden">';
        // echo                ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
        echo                ($eq['isVisible']) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
        echo            '</span>';
        echo 	    '</div>';
        echo 	'</div>';
    }

    $NbOfZigatesON = 0; // Number of enabled zigates
    for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {

        if ( config::byKey('ab::gtwEnabled'.$zgId, 'Abeille', 'N') != 'Y' )
            continue; // This Zigate is not enabled
        $NbOfZigatesON++;

        if ( Abeille::byLogicalId( 'Abeille'.$zgId.'/0000', 'Abeille') ) {
            echo '<label style="margin: 10px 0px 0px 10px">{{Réseau Abeille}}'.$zgId.'</label>';
        }
        echo "&nbsp&nbsp&nbsp";
        echo '<a class="fas fa-plus-circle" style="font-size:160%;color:green !important;" onclick="sendToCmd(\'startPermitJoin\','.$zgId.')" title="{{Activation du mode inclusion}}"></a>';
        echo "&nbsp&nbsp&nbsp";
        echo '<a class="fas fa-minus-circle" style="font-size:160%;color:red !important;" onclick="sendToCmd(\'stopPermitJoin\','.$zgId.')" title="{{Arret du mode inclusion}}"></a>';
        echo "&nbsp&nbsp&nbsp";
        echo '<span class="cursor" onclick="createRemote('.$zgId.')" title="Clic pour créer une télécommande virtuelle."><i class="fas fa-gamepad" style="font-size:160%;color:orange !important;"></i></span>';

        /* Remove equipments from Jeedom only */
        echo '<a onclick="removeSelectedEq('.$zgId.')" class="btn btn-warning btn-xs" style="margin-top: -10px; margin-left:15px" title="{{Supprime les équipements sélectionnés de Jeedom uniquement}}">{{Supprimer de Jeedom}}</a>';

        /* Exclude feature */
        echo '<a onclick="removeBees('.$zgId.')" class="btn btn-warning btn-xs" style="margin-top:-10px; margin-left:8px" title="Demande aux équipement(s) sélectionné(s) de sortir du réseau.">{{Exclure}}</a>';

        /* Monitoring feature */
        $port = config::byKey('ab::gtwPort'.$zgId, 'Abeille', '');
        echo '<a onclick="monitorIt('.$zgId.', \''.$port.'\')" class="btn btn-warning btn-xs" style="margin-top:-10px; margin-left:8px" title="Surveillance des messages vers/de l\'équipement sélectionné. Sortie dans \'AbeilleMonitor\'.">{{Surveiller}}</a>';

        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            // echo '<br>';

            /* Set timeout on selected equipements */
            // echo '<a onclick="setBeesTimeout('.$zgId.')" class="btn btn-primary btn-xs" style="margin-top:-10px; margin-left:8px" title="Permet de modifier le timeout pour les équipement(s) sélectionné(s).">{{Timeout}}</a>';
            echo '<a onclick="listView('.$zgId.')" class="btn btn-primary btn-xs" style="margin-top:-10px; margin-left:8px" title="Vue liste">{{Liste}}</a>';
        }

        /* Display list of equipments */
		// echo '<div class="eqLogicThumbnailContainer" style="height:180px">';
		echo '<div class="eqLogicThumbnailContainer">';

        // Display gateway first
		foreach ($eqPerZigate[$zgId] as $eq) {
            if ($eq['addr'] != '0000')
                continue; // It's not a zigate

            $eqId = $eq['id'];
            // $eqLogic = eqLogic::byId($eqId);
            // displayBeeCard($eqLogic, $zgId, $eqId);
            displayBeeCard($zgId, $eqId);
        }

        // Display attached equipments
		foreach ($eqPerZigate[$zgId] as $eq) {
            if ($eq['addr'] == '0000')
                continue; // It's a zigate

            $eqId = $eq['id'];
            // $eqLogic = eqLogic::byId($eqId);
            // displayBeeCard($eqLogic, $zgId, $eqId);
            displayBeeCard($zgId, $eqId);
        }
        echo ' </div>';
        echo '<script>var js_eqZigate'.$zgId.' = \''.json_encode($eqPerZigate[$zgId]).'\';</script>';
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