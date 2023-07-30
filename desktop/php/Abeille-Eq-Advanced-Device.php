<!-- This file displays advanced equipment/zigbee infos.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Identifiant Zigbee}}</label>
    <div class="col-sm-5">
        <input id="idZbModel" readonly title="{{Modèle}}" value="" />
        <input id="idZbManuf" readonly style="margin-left: 8px" title="{{Fabricant}}" value="" />
    </div>
</div>

<!-- < ?php   TODO: MUST NE IMPLEMENTED in javascript
    // Tcharp38 note: Most of the devices have only 1 signature but some have several
    if (isset($eqZigbee['endPoints'])) {
        $endPoints = $eqZigbee['endPoints'];
        $manuf = '';
        $model = '';
        $location = '';
        foreach ($endPoints as $epId2 => $ep2) {
            if (!isset($ep2['servClusters']))
                continue;
            if (strpos($ep2['servClusters'], '0000') === false)
                continue; // Basic cluster not supported

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">Identifiant Zigbee EP '.$epId2.'</label>';
            echo '<div class="col-sm-5">';

                $model2 = isset($ep2['modelId']) ? $ep2['modelId'] : '';
                $manuf2 = isset($ep2['manufId']) ? $ep2['manufId'] : '';
                $location2 = isset($ep2['location']) ? $ep2['location'] : '';
                if (($manuf2 != $manuf) || ($model2 != $model) || ($location2 != $location) ) {
                    echo '<input readonly title="{{Modèle}}" value="'.$model2.'" />';
                    echo '<input readonly style="margin-left: 8px" title="{{Fabricant}}" value="'.$manuf2.'" />';
                    echo '<input readonly style="margin-left: 8px" title="{{Localisation}}" value="'.$location2.'" />';
                    if ($manuf == '')
                        $manuf = $manuf2; // Saving 1st
                    if ($model == '')
                        $model = $model2; // Saving 1st
                    if ($location == '')
                        $location = $location2; // Saving 1st
                }

            echo '</div>';
            echo '</div>';
        }
    }
?> -->

<div class="form-group">
    <label class="col-sm-3 control-label">{{Modèle d'équipement}}</label>
    <div class="col-sm-5">
        <!-- < ?php
            if (isset($eqModel['id']))
                $jsonId = $eqModel['id'];
            else
                $jsonId = '';
            if (isset($eqModel['id']) && ($eqModel['location'] != ''))
                $jsonLocation = $eqModel['location'];
            else
                $jsonLocation = 'Abeille';

            echo '<input readonly style="width: 200px" title="{{Nom du modèle utilisé}}" value="'.$jsonId.'" />';
            echo '    Source:';
            if (($jsonLocation == '') || ($jsonLocation == "Abeille"))
                $title = "Le modèle utilisé est celui fourni par Abeille";
            else
                $title = "Le modèle utilisé est un modèle local/custom";
            echo '<input readonly style="width:86px" title="{{'.$title.'}}" value="'.$jsonLocation.'" />';

            if ($jsonId == "defaultUnknown") {
                if ($sig) {
                    $id1 = $sig['modelId'].'_'.$sig['manufId'];
                    $id2 = $sig['modelId'];
                    $customP = __DIR__."/../../core/config/devices_local/";
                    $officialP = __DIR__."/../../core/config/devices/";
                    if (file_exists($customP.$id1."/".$id1.".json") ||
                        file_exists($customP.$id2."/".$id2.".json")) {
                        echo '<span style="background-color:red;color:black" title=""> INFO: Modèle local/custom disponible.</span>';
                        // echo '<a class="btn btn-warning" onclick="reinit(\''.$eqId.'\')" title="Réinitlialisation avec le modèle officiel commme s\'il s\'agissait d\'une nouvelle inclusion">Utiliser</a>';
                    } else if (file_exists($officialP.$id1."/".$id1.".json") ||
                        file_exists($officialP.$id2."/".$id2.".json")) {
                        echo '<span style="background-color:red;color:black" title=""> INFO: Modèle officiel disponible.</span>';
                        // echo '<a class="btn btn-warning" onclick="reinit(\''.$eqId.'\')" title="Réinitlialisation avec le modèle officiel commme s\'il s\'agissait d\'une nouvelle inclusion">Utiliser</a>';
                    }
                }
            } else {
                if ($jsonLocation != 'Abeille') {
                    echo '<span style="background-color:red;color:black" title="La configuration vient d\'un fichier local (core/config/devices_local)"> ATTENTION: Issu d\'un modèle local </span>';
                    if (file_exists(__DIR__."/../../core/config/devices_local/".$jsonId."/".$jsonId.".json"))
                        echo '<a class="btn btn-warning" onclick="removeLocalJSON(\''.$jsonId.'\')" title="Supprime la version locale du fichier de config JSON">Supprimer version locale</a>';
                }
            }

            ?> -->
            <input id="idModelName" readonly style="width: 200px" title="{{Nom du modèle utilisé}}" value="" />
            <input id="idModelSource" readonly style="width:86px" title="{{Origine du modèle}}" value="" />
            <a class="btn btn-warning" id="idUpdateBtn" style="margin-left:8px" title="{{Mise-à-jour à partir de son modèle et reconfiguration}}">{{Mise-à-jour}}</a>
            <a class="btn btn-danger" id="idReinitBtn" style="margin-left:8px" title="{{Réinitlialise les paramètres par défaut et reconfigure l\'équipement comme s\'il s\'agissait d\'une nouvelle inclusion}}">{{Réinitialiser}}</a>
    </div>
</div>

<!-- <div class="form-group">
    <label class="col-sm-3 control-label">Configuration</label>
    <div class="col-sm-5">
        < ?php
            echo '<a class="btn btn-warning" onclick="update(\''.$eqId.'\')" title="Mise-à-jour à partir de son modèle et reconfiguration">Mise-à-jour</a>';
            echo '<a class="btn btn-danger" onclick="reinit(\''.$eqId.'\')" style="margin-left:8px" title="Réinitlialise les paramètres par défaut et reconfigure l\'équipement comme s\'il s\'agissait d\'une nouvelle inclusion">Réinitialiser</a>';
            // Tcharp38: Simplifcation for end users. Moreover no sense to do commands updates without device config since might be closely linked.
            // echo ' OU ';
            // echo '<a class="btn btn-warning" onclick="updateFromJSON(\''.$eqNet.'\', \''.$eqAddr.'\')" title="Mets à jour les commandes Jeedom">Recharger</a>';
            // echo ' ';
            // echo '<a class="btn btn-warning" onclick="reconfigure(\''.$eqId.'\')" title="Reconfigure l\'équipement">Reconfigurer</a>';
        ?>
    </div>
</div> -->

<!-- < ?php
    if ($sig) {
        $path = __DIR__.'/../../core/config/devices_local/'.$sig['modelId'].'_'.$sig['manufId'].'/discovery.json';
        if (file_exists($path)) {
?>
<div class="form-group">
    <label class="col-sm-3 control-label">'Discovery' local</label>
    <div class="col-sm-5">
        < ?php
        $model = $sig['modelId'];
        $manuf = $sig['manufId'];
        echo '<a class="btn btn-success" title="Télécharge le \'discovery.json\' local" onclick="downloadLocalDiscovery(\''.$model.'\', \''.$manuf.'\')"><i class="fas fa-cloud-download-alt"></i> Télécharger</a>';
        ?>
    </div>
</div>
< ?php } } ?> -->

<div class="form-group">
    <label class="col-sm-3 control-label">{{Assistant de découverte}}</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" id="idEqAssistBtn">{{Ouvrir}}</a>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Code fabricant (manufCode)}}</label>
    <div class="col-sm-5">
        <!-- < ?php
            if (isset($eqZigbee['manufCode']))
                $manufCode = $eqZigbee['manufCode'];
            else
                $manufCode = '';
            echo '<input readonly title="{{Code fabricant}}" value="'.$manufCode.'" />';
        ?> -->
        <input id="idManufCode" readonly title="{{Code fabricant}}" value="" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Version SW}} (SWBuildID)</label>
    <div class="col-sm-5" advInfo="0000-01-4000">
        <input type="text" id="" value="" readonly>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Etat de l'équipement}} (BETA)</label>
    <div class="col-sm-5">
        <a class="btn btn-danger" id="idRepairBtn" title="{{Tente de corriger l\'état de l\'équipement}}">{{Réparer}}</a>
    </div>
</div>
