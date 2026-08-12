<!-- This file displays equipment commands.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom}}</label>
    <div class="col-sm-9">
        <input id="idEqName" type="text" value="" readonly style="width:240px">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{ID Jeedom}}</label>
    <div class="col-sm-9">
        <input id="idEqId" type="text" value="" readonly style="width:240px">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom logique}}</label>
    <div class="col-sm-5">
        <input type="text" class="eqLogicAttr" data-l1key="logicalId" style="width:240px"></input>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Dernière comm.}}</label>
    <div advInfo="Time-Time" class="col-sm-9">
        <input type="text" value="" readonly style="width:240px">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Documentation}}</label>
    <div class="col-sm-9">
        <a id="idDocUrl" href="tobefilled" target="_blank">{{Voir ici si présente}}</a>
    </div>
</div>
