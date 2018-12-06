<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');

$parameters_info = Abeille::getParameters();

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

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">

        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>

        <div class="eqLogicThumbnailContainer">

            <div class="cursor changeIncludeState card" id="bt_include" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-sign-in fa-rotate-90" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Inclusion}}</center></span>
            </div>

            <div class="cursor" id="bt_createTimer" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">
                <center>
                    <img src="plugins/Abeille/images/node_Timer.png" height="105" width="95">
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Timer}}</center></span>
            </div>

            <div class="cursor" id="bt_createRemote" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">
                <center>
                    <i class="fa fa-gamepad" style="font-size : 6em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Télécommande}}</center></span>
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

            <div class="cursor" id="bt_listeCompatibilite" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-address-card-o" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Compatibilite</center></span>
            </div>


        </div>



        <legend><i class="fa fa-commenting-o"></i> {{Affichage Widget}}</legend>

        <div class="eqLogicThumbnailContainer">
                <div class="cursor" id="bt_toggleAffichageNetwork" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                    <center>
                        <?php
                            if ($parameters_info["affichageNetwork"] == 'Y') { echo '<i class="fa fa-check-square-o" style="font-size : 3em;color:#767676;"></i>'; }
                            else { echo '<div affichageNetwork><i class="fa fa-square-o" style="font-size : 3em;color:#767676;"></i></div>'; }
                            ?>
                    </center>
                    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Network</center></span>
                </div>

                <div class="cursor" id="bt_toggleAffichageTime" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                    <center>
                        <?php
                            if ($parameters_info["affichageTime"] == 'Y') { echo '<i class="fa fa-check-square-o" style="font-size : 3em;color:#767676;"></i>'; }
                            else { echo '<i class="fa fa-square-o" style="font-size : 3em;color:#767676;"></i>'; }
                            ?>
                    </center>
                    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Time</center></span>
                </div>

                <div class="cursor" id="bt_toggleAffichageAdditionalCommand" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                    <center>
                        <?php
                            if ($parameters_info["affichageCmdAdd"] == 'Y') {  echo '<i class="fa fa-check-square-o" style="font-size : 3em;color:#767676;"></i>'; }
                            else { echo '<i class="fa fa-square-o" style="font-size : 3em;color:#767676;"></i>'; }
                            ?>
                    </center>
                    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Cmd Add</center></span>
                </div>
        </div>


        <legend><i class="fa fa-table"></i> {{Mes Abeilles}}</legend>


        <form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">
        <input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchEqlogic" />

        <div class="eqLogicThumbnailContainer">

            <?php
            $dir = dirname(__FILE__) . '/../../images/';
            $files = scandir($dir);
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                echo '<div>';
                    echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<input type="checkbox" name="eqSelected-'.$eqLogic->getId().'" /><br>';
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
                    echo '</div>';
                
                echo '</div>';
            }
            ?>

        </div>

        <legend><i class="fa fa-cogs"></i> {{Appliquer les commandes sur la selection}}</legend>

        <table>
            <tr>
                <td>
                    Groupe
                </td>
            </tr><tr>
                <td>
                    <label control-label" data-toggle="tooltip" title="en hex de 0000 a ffff, probablement celui que vous avez récuperé de votre télécommande.">Id</label>
                    <input type="text" name="group">
                </td><td>
                    <input type="submit" name="submitButton" value="Add Group">
                </td><td>
                    <input type="submit" name="submitButton" value="Remove Group">
                </td><td>
                    <input type="submit" name="submitButton" value="Get Group">
                </td>
            </tr><tr>
                <td>
                    Modele
                </td>
            </tr><tr>
                <td>
                    <input type="submit" name="submitButton" value="Apply Template">
                </td>
            </tr>
            </tr>
            </table>
            
            <legend><i class="fa fa-cog"></i> {{Dev en cours}}</legend>
            <table>
            </tr><tr>
                <td>
                    Widget Properties
                </td>
            </tr><tr>
                <td>
                    Dimension
                </td><td>
                    <label control-label" data-toggle="tooltip" title="Largeur par defaut du widget que vous souhaitez.">Largeur</label>
                    <input type="text" name="Largeur">
                </td><td>
                    <label control-label" data-toggle="tooltip" title="Hauteur par defaut du widget que vous souhaitez.">Largeur</label>
                    <input type="text" name="Hauteur">
                </td><td>
                    <input type="submit" name="submitButton" value="Set Size">
                </td>
            </tr><tr>
                </td><td>
                    <a class="btn btn-danger  eqLogicAction pull-right" data-action="removeSelect"><i class="fa fa-minus-circle"></i>  {{Supprime les objets sélectionnés}}</a>
                </td><td>
                    <a class="btn btn-danger  eqLogicAction pull-right" data-action="removeAll"><i class="fa fa-minus-circle"></i>  {{Supprimer tous les objets}}</a>
                </td>
            </tr><tr>
                <td>
                    <a class="btn btn-danger  eqLogicAction pull-right" data-action="exclusion"><i class="fa fa-sign-out"></i>  {{Exclusion}}</a>
                </td>
            </tr>

        </table>

        </form>

    </div>


    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px; display: none;">

        <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i>   {{Sauvegarder}}</a>
        <a class="btn btn-danger  eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i>  {{Supprimer}}</a>
        <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i>      {{Configuration avancée}}</a>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"                 ><a href="#"               aria-controls="home"    role="tab" data-toggle="tab" class="eqLogicAction" data-action="returnToThumbnailDisplay">       <i class="fa fa-arrow-circle-left"></i>                 </a></li>
            <li role="presentation" class="active"  ><a href="#eqlogictab"     aria-controls="home"    role="tab" data-toggle="tab">                                                                    <i class="fa fa-tachometer"></i>        {{Equipement}}  </a></li>
            <li role="presentation"                 ><a href="#paramtab"       aria-controls="home"    role="tab" data-toggle="tab">                                                                    <i class="fa fa-list-alt"></i>          {{Param}}       </a></li>
            <li role="presentation"                 ><a href="#commandtab"     aria-controls="profile" role="tab" data-toggle="tab">                                                                    <i class="fa fa-list-alt"></i>          {{Commandes}}   </a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
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
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable"     checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible"    checked/>{{Visible}}</label>
                            </div>
                        </div>

                                <hr>
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Time Out (min)}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="timeout" placeholder="{{En minutes}}"/>
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
            
            <div role="tabpanel" class="tab-pane" id="paramtab">
                <form class="form-horizontal">
                    <fieldset>
                                <hr>
                                Pour l'instant cette page consolide l'ensemble des parametres spécifiques à tous les équipments. L'idée est de ne faire apparaitre (dès que je trouve la solution) que les paragraphes en relation avec l'objet sélectionné. Par exemple si vous avez sélectionné une ampoule le paragraphe "Equipement sur piles" ne devrait pas apparaitre. (Si quelqu'un sait comment faire, je suis preneur).
                                <hr>
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Equipements sur piles.}}</label>
                                </div>
                                
                                <div class="form-group" >
                                <label class="col-sm-3 control-label" >{{Type de piles}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_type"  placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>
                                </div>
                                </div>
                                
                                
                                <hr>
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Telecommande}}</label>
                                </div>

                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Groupe}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Groupe" placeholder="{{Adresse en hex sur 4 digits, ex:ae12}}"/>
                                </div>
                                </div>
                                
                                <div id="timer">
                                <hr>
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Timer}}</label>
                                </div>
                                
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{action sur demarrage}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionStart" placeholder="#cmd#"/>
                                </div>
                                </div>
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Duration}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerDuration" placeholder="secondes"/>
                                </div>
                                </div>
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Ramp Up duration}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerRampUp" placeholder="secondes"/>
                                </div>
                                </div>
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Ramp Down duration}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerRampDown" placeholder="secondes"/>
                                </div>
                                </div>
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Ramp Action}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionRamp" placeholder="#cmd#"/>
                                </div>
                                </div>
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Action sur arret}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionStop" placeholder="#cmd#"/>
                                </div>
                                </div>
                                
                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Action sur annulation}}</label>
                                <div class="col-sm-3">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionCancel" placeholder="#cmd#"/>
                                </div>
                                </div>
                                </div>
                    </fieldset>
                </form>
            </div>
                                
            <div role="tabpanel" class="tab-pane" id="commandtab">

                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-actions">
                            <a class="btn btn-success btn-sm cmdAction" id="bt_addAbeilleAction">   <i class="fa fa-plus-circle"></i>  {{Ajouter une commande action}}</a>
                            <a class="btn btn-success btn-sm cmdAction" id="bt_addAbeilleInfo">     <i class="fa fa-plus-circle"></i>  {{Ajouter une commande info (dev en cours) }}</a>
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
