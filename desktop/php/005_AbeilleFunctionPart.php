<?php
/* Display beehive or bee card */
function displayBeeCard($eqLogic, $files, $zgNb) {
    // find opacity
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');

    // Find icone
    $test = 'node_' . $eqLogic->getConfiguration('icone') . '.png';
    if (in_array($test, $files, 0)) {
        $path = 'node_' . $eqLogic->getConfiguration('icone');
    } else {
        $path = 'Abeille_icon';
    }

    // Affichage
    $id = $eqLogic->getId();
    echo '<div class="eqLogicDisplayCardB">';
    echo    '<input id="idBeeChecked'.$zgNb.'-'.$id.'" type="checkbox" name="eqSelected-'.$id.'" />';
    echo    '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $id . '" style="background-color : #ffffff ; width : 200px;' . $opacity . '" >';
    echo    '<table><tr><td><img src="plugins/Abeille/images/' . $path . '.png"  /></td><td>' . $eqLogic->getHumanName(true, true) . '</td></tr></table>';
    echo    '</div>';
    echo '</div>';
}
?>