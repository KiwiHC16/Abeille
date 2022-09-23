<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Main.php' -->

     <hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group" >
    <label class="col-sm-3 control-label">{{Source d'alimentation}}</label>
    <div class="col-sm-3">
        <?php
        /* If battery powered eq. 'batteryType' is defined in device JSON file */
        if ($eqLogic->getConfiguration('battery_type', '') != "") {
            echo '<span>{{Batterie}}  </span>';
            echo '<input class="eqLogicAttr" data-l1key="configuration" data-l2key="battery_type" />';
        } else
            echo '<span>{{Secteur}}</span>';
        ?>
</div>
</div>

<hr>
<div class="form-group">
    <label class="col-sm-3 control-label">{{Position pour les representations graphiques.}}</label>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Position X}}</label>
    <div class="col-sm-3">
        <input class="eqLogicAttr form-control" data-l1key="configuration"
        data-l2key="positionX"
        placeholder="{{Position sur l axe horizontal (0 à gauche - 1000 à droite)}}"/>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Position Y}}</label>
    <div class="col-sm-3">
        <input class="eqLogicAttr form-control" data-l1key="configuration"
        data-l2key="positionY"
        placeholder="{{Position sur l axe vertical (0 en haut - 1000 en bas)}}"/>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Position Z}}</label>
    <div class="col-sm-3">
        <input class="eqLogicAttr form-control" data-l1key="configuration"
        data-l2key="positionZ"
        placeholder="{{Position en hauteur (0 en bas - 1000 en haut)}}"/>
    </div>
</div>