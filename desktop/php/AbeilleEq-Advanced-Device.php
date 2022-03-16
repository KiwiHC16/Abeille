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
                echo '<span>'.$model.', '.$manuf.'</span>';
            }
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label">Identifiant modèle JSON</label>
    <div class="col-sm-5">
        <span class="eqLogicAttr" data-l1key="configuration" data-l2key="ab::jsonId" title="Nom du fichier de config JSON utilisé"></span>
        Source:
        <?php
            $jsonLocation = $eqLogic->getConfiguration('ab::jsonLocation', 'Abeille');
            if (($jsonLocation == '') || ($jsonLocation == "Abeille"))
                echo '<span title="Le modèle utilisé est celui fourni par Abeille">Abeille</span>';
            else
                echo '<span title="Le modèle utilisé est un modèle local/custom">Modèle local</span>';

            $jsonId = $eqLogic->getConfiguration('ab::jsonId', '');
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
                $jsonLocation = $eqLogic->getConfiguration('ab::jsonLocation', 'Abeille');
                if ($jsonLocation != 'Abeille') {
                    echo '<span style="background-color:red;color:black" title="La configuration vient d\'un fichier local (core/config/devices_local)"> ATTENTION: Issu d\'un modèle local </span>';
                    $jsonId = $eqLogic->getConfiguration('ab::jsonId', '');
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
            echo ' OU ';
            echo '<a class="btn btn-warning" onclick="updateFromJSON(\''.$eqNet.'\', \''.$eqAddr.'\')" title="Mets à jour les commandes Jeedom">Recharger</a>';
            echo ' ';
            echo '<a class="btn btn-warning" onclick="reconfigure(\''.$eqId.'\')" title="Reconfigure l\'équipement">Reconfigurer</a>';
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

