<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Advanced.php' -->

<hr>

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">Identifiant Zigbee (modelId, manufId)</label>
    <div class="col-sm-5">
        <?php
            $sig = $eqLogic->getConfiguration('ab::signature');
            if ($sig) {
                $model = $sig['modelId'];
                $manuf = $sig['manufId'];
                // echo '<span>'.$model.', '.$manuf.'</span>';
            } else {
                $model = '';
                $manuf = '';
            }
            echo '<input readonly title="{{Identifiant Zigbee du modèle}}" value="'.$model.'" />';
            echo '<input readonly style="margin-left: 8px" title="{{Identifiant Zigbee du fabricant}}" value="'.$manuf.'" />';
    ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label">Identifiant du modèle</label>
    <div class="col-sm-5">
        <?php
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
                        echo '<a class="btn btn-warning" onclick="reinit(\''.$eqId.'\')" title="Réinitlialisation avec le modèle officiel commme s\'il s\'agissait d\'une nouvelle inclusion">Utiliser</a>';
                    } else if (file_exists($officialP.$id1."/".$id1.".json") ||
                        file_exists($officialP.$id2."/".$id2.".json")) {
                        echo '<span style="background-color:red;color:black" title=""> INFO: Modèle officiel disponible.</span>';
                        echo '<a class="btn btn-warning" onclick="reinit(\''.$eqId.'\')" title="Réinitlialisation avec le modèle officiel commme s\'il s\'agissait d\'une nouvelle inclusion">Utiliser</a>';
                    }
                }
            } else {
                if ($jsonLocation != 'Abeille') {
                    echo '<span style="background-color:red;color:black" title="La configuration vient d\'un fichier local (core/config/devices_local)"> ATTENTION: Issu d\'un modèle local </span>';
                    if (file_exists(__DIR__."/../../core/config/devices_local/".$jsonId."/".$jsonId.".json"))
                        echo '<a class="btn btn-warning" onclick="removeLocalJSON(\''.$jsonId.'\')" title="Supprime la version locale du fichier de config JSON">Supprimer version locale</a>';
                }
            }
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label">Configuration</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="reinit(\''.$eqId.'\')" title="Réinitlialise les paramètres par défaut et reconfigure l\'équipement comme s\'il s\'agissait d\'une nouvelle inclusion">Réinitialiser</a>';
            // Tcharp38: Simplifcation for end users. Moreover no sense to do commands updates without device config since might be closely linked.
            // echo ' OU ';
            // echo '<a class="btn btn-warning" onclick="updateFromJSON(\''.$eqNet.'\', \''.$eqAddr.'\')" title="Mets à jour les commandes Jeedom">Recharger</a>';
            // echo ' ';
            // echo '<a class="btn btn-warning" onclick="reconfigure(\''.$eqId.'\')" title="Reconfigure l\'équipement">Reconfigurer</a>';
        ?>
    </div>
</div>
<?php
    if ($sig) {
        $path = __DIR__.'/../../core/config/devices_local/'.$sig['modelId'].'_'.$sig['manufId'].'/discovery.json';
        if (file_exists($path)) {
?>
<div class="form-group">
    <label class="col-sm-3 control-label">'Discovery' local</label>
    <div class="col-sm-5">
        <?php
        $model = $sig['modelId'];
        $manuf = $sig['manufId'];
        echo '<a class="btn btn-success" title="Télécharge le \'discovery.json\' local" onclick="downloadLocalDiscovery(\''.$model.'\', \''.$manuf.'\')"><i class="fas fa-cloud-download-alt"></i> Télécharger</a>';
        ?>
    </div>
</div>
<?php } } ?>
<div class="form-group">
    <label class="col-sm-3 control-label">Assistant de découverte</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="window.location.href=\'index.php?v=d&m=Abeille&p=AbeilleEqAssist&id='.$eqId.'\'">Ouvrir</a>';
        ?>
    </div>
</div>

