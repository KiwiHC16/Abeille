<form class="form-horizontal">
    <fieldset>

        <hr>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
            <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="name"                               placeholder="{{Nom de l'équipement Abeille}}"   />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Objet parent}}</label>
            <div class="col-sm-3">
                <select class="eqLogicAttr form-control"            data-l1key="object_id">
                    <option value="">{{Aucun}}</option>
                    <?php
                    foreach (jeeObject::all() as $object) {
                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
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
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                    echo '</label>';
                }
                ?>

            </div>
        </div>

        <div class="form-group">
                <label class="col-sm-3 control-label">.</label>
            <div class="col-sm-3">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable"     checked/>{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible"    checked/>{{Visible}}</label>
            </div>
        </div>

        <br>

        <hr>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Note}}</label>
            <div class="col-sm-3">
                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="note" placeholder="{{Vos notes pour vous souvenir}}">Votre note</textarea>
            </div>
        </div>

        <br>

        <hr>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Time Out (min)}}</label>
            <div class="col-sm-3">
                <input class="eqLogicAttr form-control" data-l1key="timeout" placeholder="{{En minutes}}"/>
            </div>
        </div>

        <br>
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

        <hr>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Id}}</label>
            <div class="col-sm-3">
                <span class="eqLogicAttr" data-l1key="id"></span>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{Topic Abeille}}</label>
            <div class="col-sm-3">
                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="topic"></span>
            </div>
        </div>


        <hr>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Icone}}</label>
            <div class="col-sm-3">
                <select id="sel_icon" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="icone">
                    <option value="Abeille">{{Abeille}}</option>
                    <option value="Ruche">{{Ruche}}</option>
                    <?php
                    require_once dirname(__FILE__) . '/../../resources/AbeilleDeamon/lib/Tools.php';
                    $items = AbeilleTools::getDeviceNameFromJson('Abeille');

                    $selectBox = array();
                    foreach ($items as $item) {
                        $AbeilleObjetDefinition = AbeilleTools::getJSonConfigFilebyDevices(AbeilleTools::getTrimmedValueForJsonFiles($item), 'Abeille');
                        $name = $AbeilleObjetDefinition[$item]['nameJeedom'];
                        $icone = $AbeilleObjetDefinition[$item]['configuration']['icone'];
                        $selectBox[ucwords($name)] = $icone;
                    }
                    ksort($selectBox);
                    foreach ($selectBox as $key => $value) {
                        echo "                     <option value=\"" . $value . "\">{{" . $key . "}}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <div style="text-align: center">
                <img name="icon_visu" src="" width="160" height="200"/>
            </div>
        </div>
        
        <hr>
        <div class="form-group">
                <label class="col-sm-3 control-label">{{Documentation}}</label><br>
                <div style="text-align: left">
<?php
                    if (isset($_GET['id']) && ($_GET['id'] > 0)) {
                        $eqLogic = eqLogic::byId($_GET['id']);
                        if ( $eqLogic->getConfiguration('modeleJson', 'notDefined') != 'notDefined' ) {
                            $fileList = glob('plugins/Abeille/core/config/devices/'.$eqLogic->getConfiguration('modeleJson', 'notDefined').'/doc/*');
                            foreach($fileList as $filename){
                                echo '<label class="col-sm-3 control-label">{{.}}</label><a href="'.$filename, '" target="_blank">'.basename($filename).'</a><br>';
                            }
                        }
                    }
                    else {
                        echo '<label class="col-sm-3 control-label">{{.}}</label>.<br>';
                    }
?>
                </div>
        </div>

    </fieldset>
</form>