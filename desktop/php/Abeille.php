<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');

?>

<div class="row row-overflow">
    <div class="col-lg-2 col-sm-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">

                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm"
                                                                      placeholder="{{Rechercher}}" style="width: 100%"/>
                </li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay"
         style="border-left: solid 1px #EEE; padding-left: 25px;">


        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>

        <div class="eqLogicThumbnailContainer">

            <div class="cursor changeIncludeState card" id="bt_include" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-sign-in fa-rotate-90" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Mode inclusion}}</center></span>
            </div>

            <div class="cursor changeIncludeState card" id="bt_exclude" data-mode="0" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">
                <center>
                    <i class="fa fa-sign-out fa-rotate-90" style="font-size : 6em;color:#FA5858;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#FA5858"><center>{{Mode exclusion (not implemented yet)}}</center></span>
            </div>

            <div class="cursor" id="bt_createTimer" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">
                <center>
                    <img src="plugins/Abeille/images/node_Timer.png" height="105" width="95">
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Create Timer}}</center></span>
            </div>

            <div class="cursor eqLogicAction" data-action="gotoPluginConf"
                 style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
            </div>
            <div class="cursor" id="bt_healthAbeille"
                 style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-medkit" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Santé}}</center></span>
            </div>
            <div class="cursor" id="bt_networkAbeilleList"
                style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-sitemap" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Network List</center></span>
            </div>
            <div class="cursor" id="bt_networkAbeille"
                style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-sitemap" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Network Graph</center></span>
            </div>
            <div class="cursor" id="bt_networkAbeilleNew"
                style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-sitemap" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Network Graph New</center></span>
            </div>
        </div>


        <legend><i class="fa fa-table"></i> {{Affichage Widget}}</legend>

<?php
    $parameters_info = Abeille::getParameters();
?>

    <div class="eqLogicThumbnailContainer">
            <div class="cursor" id="bt_toggleAffichageNetwork" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <?php
                        if ($parameters_info["affichageNetwork"] == 'Y') { echo '<i class="fa fa-check-square-o" style="font-size : 5em;color:#767676;"></i>'; }
                        else { echo '<i class="fa fa-square-o" style="font-size : 5em;color:#767676;"></i>'; }
                        ?>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Network</center></span>
            </div>

            <div class="cursor" id="bt_toggleAffichageTime" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <?php
                        if ($parameters_info["affichageTime"] == 'Y') { echo '<i class="fa fa-check-square-o" style="font-size : 5em;color:#767676;"></i>'; }
                        else { echo '<i class="fa fa-square-o" style="font-size : 5em;color:#767676;"></i>'; }
                        ?>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Time</center></span>
            </div>

            <div class="cursor" id="bt_toggleAffichageAdditionalCommand" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <?php
                        if ($parameters_info["affichageCmdAdd"] == 'Y') {  echo '<i class="fa fa-check-square-o" style="font-size : 5em;color:#767676;"></i>'; }
                        else { echo '<i class="fa fa-square-o" style="font-size : 5em;color:#767676;"></i>'; }
                        ?>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Cmd Add</center></span>
            </div>
        </div>


        <legend><i class="fa fa-table"></i> {{Mes Abeilles}}</legend>
        <input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchEqlogic" />
        <div class="eqLogicThumbnailContainer">
            <?php
            $dir = dirname(__FILE__) . '/../../images/';
            $files = scandir($dir);
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff ; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                echo "<center>";
                $test = 'node_' . $eqLogic->getConfiguration('icone') . '.png';
                if (in_array($test, $files, 0)) {
                    $path = 'node_' . $eqLogic->getConfiguration('icone');
                } else {
                    $path = 'Abeille_icon';
                }
                echo '<img src="plugins/Abeille/images/' . $path . '.png" height="105" width="95" />';
                echo "</center>";
                echo '<span class="name" style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span><BR>';
                echo '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" /><center>{{Select (na yet)}}</label></center></span>';
                echo '</div>';
            }
            ?>
        </div>

        <div id="Set Group">
        Group setting to be developped here.
        <input class="form-control" placeholder="Group" style="margin-bottom:4px;" id="in_searchEqlogictytytytyttyty">
        </div>

    </div>








    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic"
         style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i>
            {{Sauvegarder}}</a>
        <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i>
            {{Supprimer}}</a>
        <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i>
            {{Configuration avancée}}</a>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab"
                                       data-action="returnToThumbnailDisplay"><i
                            class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab"
                                                      data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a>
            </li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i
                            class="fa fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id"
                                       style="display : none;"/>
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name"
                                       placeholder="{{Nom de l'équipement Abeille}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (object::all() as $object) {
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
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr"
                                                                      data-l1key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr"
                                                                      data-l1key="isVisible"
                                                                      checked/>{{Visible}}</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Type de piles}}</label>
                            <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration"
                                       data-l2key="battery_type"
                                       placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>
                            </div>
                        </div>
                         
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Time Out (min)}}</label>
                            <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="timeout"
                                placeholder="{{En minutes}}"/>
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
                                
                        <hr>
                                
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Topic Abeille}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="topic"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Commandes par topic ou json}}</label>
                            <div class="col-sm-3">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="type"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Icone du topic}}</label>
                            <div class="col-sm-3">
                                <select id="sel_icon" class="form-control eqLogicAttr" data-l1key="configuration"
                                        data-l2key="icone">
                                    <option value="Abeille">{{Abeille}}</option>
                                    <option value="Ruche">{{Ruche}}</option>
                                    <?php
                                    require_once dirname(__FILE__) . '/../../resources/AbeilleDeamon/lib/Tools.php';
                                    $items = Tools::getDeviceNameFromJson('Abeille');
                                    $selectBox = array();
                                    foreach ($items as $item) {
                                        $AbeilleObjetDefinition = Tools::getJSonConfigFilebyDevices(Tools::getTrimmedValueForJsonFiles($item), 'Abeille');
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

                    </fieldset>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">

                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-actions">
                            <a class="btn btn-success btn-sm cmdAction" id="bt_addAbeilleAction"><i
                                        class="fa fa-plus-circle"></i> {{Ajouter une commande action}}</a>
                        </div>
                    </fieldset>
                </form>
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 300px;">{{Nom}}</th>
                        <th style="width: 120px;">{{Sous-Type}}</th>
                        <th style="width: 400px;">{{Topic}}</th>
                        <th style="width: 600px;">{{Payload}}</th>
                        <th style="width: 150px;">{{Paramètres}}</th>
                        <th style="width: 80px;"></th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>

    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

                                

</script>
