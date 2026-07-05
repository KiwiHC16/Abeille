<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Main.php' -->

<hr>

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
    <div class="col-sm-3">
        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"   />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Objet parent}}</label>
    <div class="col-sm-3">
        <select class="eqLogicAttr form-control" data-l1key="object_id">
            <option value="">{{Aucun}}</option>
            <?php
                foreach ((jeeObject::buildTree(null, false)) as $object) {
                    $decay = $object->getConfiguration('parentNumber');
                    // if ($object->getId() == $eqLogic->getObject()->getId())
                    //     echo '<option value="'.$object->getId().'" selected>'.str_repeat('&nbsp;&nbsp;', $decay).$object->getName().'</option>';
                    // else
                    echo '<option value="'.$object->getId().'">'.str_repeat('&nbsp;&nbsp;', $decay).$object->getName().'</option>';
                }
            ?>
        </select>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Catégorie}}</label>
    <div class="col-sm-8">
        <?php
            foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                echo '<label class="checkbox-inline">';
                echo        '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                echo '</label>';
            }
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">.</label>
    <div class="col-sm-3">
        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Id}}</label>
    <div class="col-sm-3">
        <span class="eqLogicAttr" data-l1key="id"></span>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Time Out (min)}}</label>
    <div class="col-sm-3">
        <input class="eqLogicAttr form-control" data-l1key="timeout" placeholder="{{En minutes}}"/>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Note}}</label>
    <div class="col-sm-3">
        <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="note" placeholder="{{Vos notes pour vous souvenir}}">Votre note</textarea>
    </div>
</div>

