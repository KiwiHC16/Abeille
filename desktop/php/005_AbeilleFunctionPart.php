<?php
    /* Display beehive or bee card */
    function displayBeeCard($eqLogic, $files, $zgNb) {
        // find opacity
        $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';

        // Find icone
        $test = 'node_' . $eqLogic->getConfiguration('icone') . '.png';
        if (in_array($test, $files, 0)) {
            $path = 'node_' . $eqLogic->getConfiguration('icone');
        } else {
            $path = 'Abeille_icon';
        }

        // Affichage
        $id = $eqLogic->getId();
        echo '<div>';
        echo    '<input id="idBeeChecked'.$zgNb.'-'.$id.'" type="checkbox" name="eqSelected-'.$id.'" />';
        echo 	'<br/>';
        echo 	'<div class="eqLogicDisplayCard cursor'.$opacity.'" style="width: 130px" data-eqLogic_id="' .$id .'">';
        echo 		'<img src="plugins/Abeille/images/' . $path . '.png"/>';
        echo 		'<br/>';
        echo 		'<span class="name">'. $eqLogic->getHumanName(true, true) .'</span>';
        echo 	'</div>';
        echo '</div>';
    }
?>