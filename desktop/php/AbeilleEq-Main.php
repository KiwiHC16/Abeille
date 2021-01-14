<!-- This file displays main equipment infos.
     Included by 'AbeilleEq.php' -->

<form class="form-horizontal">
    <fieldset>

        <div class="form-group">
            <div class="col-sm-3"></div>
            <h3 class="col-sm-3" style="text-align:center">{{Paramètres Jeedom}}</h1>
        </div>
        <hr>

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
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
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
            <label class="col-sm-3 control-label">{{Logical Id}}</label>
            <?php
                if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            ?>
                    <div class="col-sm-3">
                        <span class="eqLogicAttr" data-l1key="logicalId"></span>
                    </div>
            <?php
                }
            ?>
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
            <label class="col-sm-3 control-label">{{Icone}}</label>
            <div class="col-sm-3">
                <select id="sel_icon" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="icone">
                    <option value="Abeille">{{Abeille}}</option>
                    <option value="Ruche">{{Ruche}}</option>
                    <?php
                        require_once __DIR__.'/../../resources/AbeilleDeamon/lib/AbeilleTools.php';
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
                            echo "<option value=\"".$value."\">{{".$key."}}</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-3" style="text-align: center">
                <img name="icon_visu" src="" width="160" height="200"/>
            </div>
        </div>

    </fieldset>
</form>
