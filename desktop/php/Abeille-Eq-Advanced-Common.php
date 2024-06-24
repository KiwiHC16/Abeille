<!-- This file displays equipment commands.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom}}</label>
    <div class="col-sm-5">
    <!-- <span>< ?php echo $eqName; ?></span> -->
    <input id="idEqName" type="text" value="" readonly>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ID Jeedom}}</label>
    <div class="col-sm-5">
        <input id="idEqId" type="text" value="" readonly>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Dernière comm.}}</label>
    <div advInfo="Time-Time" class="col-sm-5">
        <input type="text" id="tofill" value="tofill" readonly>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom logique}}</label>
    <div class="col-sm-5">
        <input type="text" class="eqLogicAttr" data-l1key="logicalId"></input>
    </div>
</div>

<!-- <div class="form-group">
    <label class="col-sm-3 control-label">{{Adresse (courte/IEEE)}}</label>
    <div class="col-sm-5">
        <input id="idEqAddr" type="text" value="" readonly>
        /
        <span id="idEqIeee" class="eqLogicAttr" data-l1key="configuration" data-l2key="IEEE"></span>
    </div>
</div> -->

<div class="form-group">
    <label class="col-sm-3 control-label">{{Documentation}}</label>
    <div class="col-sm-5">
        <a id="idDocUrl" href="tobefilled" target="_blank">{{Voir ici si présente}}</a>
    </div>
</div>

<!-- <div class="form-group">
    <label class="col-sm-3 control-label" title="{{Groupes Zigbee auquels l'équipement appartient}}">{{Groupes}}</label>
    <div class="col-sm-5">
        < ?php
            $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Group-Membership');
            if (is_object($cmd))
                $groups = $cmd->execCmd();
            else
                $groups = "";
            echo '<span>'.$groups.'</span>';
        ?>
    </div>
</div> -->