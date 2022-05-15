<!-- This file displays equipment commands.
     Included by 'AbeilleEq-Advanced.php' -->

<hr>

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom}}</label>
    <div class="col-sm-5">
    <span><?php echo $eqName; ?></span>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Id Jeedom}}</label>
    <div class="col-sm-5">
        <!-- 'eqLogicAttr' with data-l1key="id" must not be declared twice in same page -->
        <span><?php echo $eqId; ?></span>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Dernière comm.}}</label>
    <?php
    echo '<div class="col-sm-5 cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Last").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
        echo '<span id="idLastComm">'.getCmdValueByLogicId($eqId, "Time-Time").'</span>';
        addJsUpdateFunction($eqId, 'Time-Time', 'idLastComm');
    ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom logique}}</label>
    <div class="col-sm-5">
        <span class="eqLogicAttr" data-l1key="logicalId"></span>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Adresse (courte/IEEE)}}</label>
    <div class="col-sm-5">
        <span><?php echo $eqAddr; ?></span>
        /
        <span class="eqLogicAttr" data-l1key="configuration" data-l2key="IEEE"></span>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Documentation}}</label>
    <div class="col-sm-5">
        <?php
            $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
            $jsonId = $eqModel ? $eqModel['id'] : '';
            if ($jsonId != '') {
                echo '<a href="'.urlProducts.'/'.$jsonId.'" target="_blank">Voir ici si présente</a>';
            }
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="{{Groupes Zigbee auquels l'équipement appartient}}">{{Groupes}}</label>
    <div class="col-sm-5">
        <?php
            $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, 'Group-Membership');
            if (is_object($cmd))
                $groups = $cmd->execCmd();
            else
                $groups = "";
            echo '<span>'.$groups.'</span>';
        ?>
    </div>
</div>