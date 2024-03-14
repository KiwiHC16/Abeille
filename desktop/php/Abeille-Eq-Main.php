<!-- This file displays Jeedom equipment main infos.
     Included by 'Abeille-Eq.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<form class="form-horizontal">
    <fieldset>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Nom de l'équipement}}</label>
            <div class="col-lg-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"   />
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Objet parent}}</label>
            <div class="col-lg-3">
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
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Fabricant}}</label>
            <div class="col-lg-3">
                <input id="idModelManuf" readonly class="form-control" style="width:100%" title="{{Fabricant}}" value="" />
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Modèle}}</label>
            <div class="col-lg-3">
                <input id="idModelModel" readonly class="form-control" style="width:100%" title="{{Modèle d'équipement}}" value="" />
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Type}}</label>
            <div class="col-lg-3">
                <input id="idModelType" readonly class="form-control" style="width:100%" title="{{Type d'équipement}}" value="" />
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Catégorie}}</label>
            <div class="col-sm-8">
                <?php
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                        echo '<label class="checkbox-inline">';
                        echo        '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                        echo '</label>';
                    }
                ?>
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">.</label>
            <div class="col-lg-3">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Id}}</label>
            <div class="col-lg-3">
                <span class="eqLogicAttr" data-l1key="id"></span>
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Time Out (min)}}</label>
            <div class="col-lg-3">
                <input class="eqLogicAttr form-control" data-l1key="timeout" placeholder="{{En minutes}}"/>
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group" >
            <label class="col-lg-3 control-label">{{Source d'alimentation}}</label>
            <div class="col-lg-3">
                <input id="idBatteryType" readonly class="form-control" style="width:100%" title="{{Source d'alimentation}}" value="" />
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Qualité du lien}} (LQI, 1-255)</label>
            <div advInfo="Link-Quality" class="col-lg-3">
                <input type="text" id="tofill" value="tofill" readonly style="width:100%">
            </div>
            <!-- <div class="col-lg-3">
                < ?php
                $lqi = getCmdValueByLogicId($eqId, "Link-Quality");
                echo '<input type="text" value="'.$lqi.'" readonly>';
                ?>
            </div> -->
            <div class="col-lg-4">
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Note}}</label>
            <div class="col-lg-3">
                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="note" placeholder="{{Vos notes pour vous souvenir}}">Votre note</textarea>
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <hr>

        <div class="form-group">
            <label class="col-lg-3 control-label">{{Icone}}</label>
            <div class="col-lg-3">
                <select id="idEqIcon" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="ab::icon">
                    <option value="defaultUnknown">{{Inconnu}}</option>
                    <option value="Ruche">{{Ruche}}</option>
                    <?php
                        $items = AbeilleTools::getImagesList();
                        foreach ($items as $icon) {
                            echo "<option value=\"".$icon."\">{{".$icon."}}</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="col-lg-4">
            </div>
        </div>

        <style>
            .widthSet {
                max-width: 160px;
                width:auto;
            }
        </style>

        <div class="form-group">
            <div class="col-lg-3">
            </div>
            <div class="col-lg-3" style="text-align: center">
                <!-- <img name="icon_visu" src="" width="160" height="200"/> -->
                <img name="icon_visu" class="widthSet">
            </div>
            <div class="col-lg-4">
            </div>
        </div>

    </fieldset>
</form>
