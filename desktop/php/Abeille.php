<?php
if(0){
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }
}

/* Display beehive or bee card */
function displayBeeCard($eqLogic, $files, $zgNb) {
    // find opacity
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');

    // Find icone
    $test = 'node_' . $eqLogic->getConfiguration('icone') . '.png';
    if (in_array($test, $files, 0)) {
        $path = 'node_' . $eqLogic->getConfiguration('icone');
    } else {
        $path = 'Abeille_icon';
    }

    // Affichage
    $id = $eqLogic->getId();
    echo '<div class="eqLogicDisplayCardB">';
    echo    '<input id="idBeeChecked'.$zgNb.'-'.$id.'" type="checkbox" name="eqSelected-'.$id.'" />';
    echo    '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $id . '" style="background-color : #ffffff ; width : 200px;' . $opacity . '" >';
    echo    '<table><tr><td><img src="plugins/Abeille/images/' . $path . '.png"  /></td><td>' . $eqLogic->getHumanName(true, true) . '</td></tr></table>';
    echo    '</div>';
    echo '</div>';
}

sendVarToJS('eqType', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');

$zigateNb = config::byKey('zigateNb', 'Abeille', '1');

$parametersAbeille = Abeille::getParameters();

$outils = array(
    'health'    => array( 'bouton'=>'bt_healthAbeille',         'icon'=>'fa-medkit',        'text'=>'{{Santé}}' ),
    'netList'   => array( 'bouton'=>'bt_networkAbeilleList',    'icon'=>'fa-sitemap',       'text'=>'{{Network List}}' ),
    'net'       => array( 'bouton'=>'bt_networkAbeille',        'icon'=>'fa-map',           'text'=>'{{Network Graph}}' ),
    'graph'     => array( 'bouton'=>'bt_graph',                 'icon'=>'fa-flask',         'text'=>'{{Graph}}' ),
    'compat'    => array( 'bouton'=>'bt_listeCompatibilite',    'icon'=>'fa-align-left',    'text'=>'{{Compatibilite}}' ),
    'inconnu'   => array( 'bouton'=>'bt_Inconnu',               'icon'=>'fa-paperclip',     'text'=>'{{Inconnu}}' ),
    'support'   => array( 'bouton'=>'bt_getCompleteAbeilleConf','icon'=>'fa-medkit',        'text'=>'{{Support}}' ),
    );

/* Developers debug features */
$dbgFile = __DIR__."/../../tmp/debug.php";
if (file_exists($dbgFile)) {
    // include_once $dbgFile;
    include $dbgFile;
    $dbgDeveloperMode = TRUE;
    echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
}
?>

<!-- For all modals on 'Abeilles' page. -->
<div id="abeilleModal" style="display:none;"></div>

    <!-- Barre verticale de recherche à gauche de la page  -->
    <div class="row row-overflow">
        <div class="col-lg-2 col-sm-3 col-sm-4">
            <div class="bs-sidebar">
                <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                    <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/>
                    </li>
<?php
                    foreach ($eqLogics as $eqLogic) {
                        echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                    }
?>
                </ul>
            </div>
        </div>

    <!-- Barre d outils horizontale  -->
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px; ">

        <!-- Icones de toutes les modales  -->
        <?php include '10_AbeilleGestionPart.php'; ?>

        <!-- Icones de toutes les abeilles  -->
        <?php include '20_AbeilleMesAbeillesPart.php'; ?>

        <legend><i class="fa fa-cogs"></i> {{Appliquer les commandes sur la selection}}</legend>

        <!-- Gestion des groupes et des scenes  -->
        <?php include '30_AbeilleGroupPart.php'; ?>

        <!-- Gestion des groupes et des scenes  -->
        <?php include '40_AbeilleScenePart.php'; ?>

        <!-- Gestion des ghosts  -->
        <?php include '50_AbeilleRemplacementPart.php'; ?>

        <!-- Gestion des parametres Zigate -->
        <?php include '60_AbeilleZigatePart.php'; ?>
<hr>

<!-- Affichage des informations reseau zigate  -->
        <legend><i class="fa fa-cog"></i> {{ZiGate}}</legend>
<br>
<i>Ces tableau ne sont pas automatiquement rafraichi, ils sont mis à jour à l ouverture de la page.</i>
<br>

<?php
    $params = array(
                   'Last'               =>'Time-Time',
                   'Last Stamps'        =>'Time-TimeStamp',
                   'SW'                 => 'SW-Application',
                   'SDK'                => 'SW-SDK',
                   'Network Status'     => 'Network-Status',
                   'Short address'      => 'Short-Addr',
                   'PAN Id'             => 'PAN-ID',
                   'Extended PAN Id'    => 'Ext_PAN-ID',
                   'IEEE address'       => 'IEEE-Addr',
                   'Network Channel'    => 'Network-Channel',
                   'Inclusion'          => 'permitJoin-Status',
                    'Time (Faites un getTime)' => 'ZiGate-Time',
                   );

    for ( $i=1; $i<=$zigateNb; $i++ ) {
        if ( is_object(Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')) ) {
            echo '<br>';
            echo 'ZiGate '.$i.'<br>';
            $rucheId = Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')->getId();
            echo '<table border="1" style="border:1px">';
            foreach ( $params as $key=>$param ){
                if ( is_object(AbeilleCmd::byEqLogicIdAndLogicalId($rucheId, $param)) ) {
                    $command = AbeilleCmd::byEqLogicIdAndLogicalId($rucheId, $param);
                    echo '<tr><td>'.$key.'</td>       <td align="center">' . $command->execCmd() . '</td></tr>';
                }
            }
            echo '</table>';
        }
        echo '</br>';
    }
?>

</form> <!-- Is this the right place to end form ? -->

    <br>
    <legend><i class="fa fa-cog"></i> {{Zone developpeurs}}</legend>
    <span style="font-weight:bold">Attention !! Cette partie est réservée aux developpeurs.</span> Ne pas s'y aventurer sauf sur leur demande expresse.<br>
    Elle ne contient que des fonctionalités de test ou en cours de developpement, pour lesquels il ne sera fourni <span style="font-weight:bold">aucun support</span>.<br>
    <br>
    <a id="idDevGrpShowHide" class="btn btn-success">Montrer</a>
    <div id="idDevGrp" style="display:none">
        <hr>
        <br>
        <label>Fonctionalités cachées:</label>
        <?php
            if (file_exists($dbgFile))
                echo '<input type="button" onclick="xableDevMode(0)" value="Désactiver" title="Supprime le fichier debug.php">';
            else    
                echo '<input type="button" onclick="xableDevMode(1)" value="Activer" title="Crée le fichier debug.php avec les valeurs par defaut.">';
        ?>
        <br>

        <!-- Following functionalities are visible only if 'tmp/debug.php' file exists (developer mode). -->
        <?php
            if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            }
        ?>

        <!-- Misc -->
        <input type="submit" name="submitButton" value="Identify">
        <table>
            <tr>
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
                </td>
                <!-- <td>
                    <a class="btn btn-danger  eqLogicAction pull-right" data-action="removeSelect"><i class="fa fa-minus-circle"></i>  {{Supprime les objets sélectionnés}}</a>
                </td> -->
                <td>
                    <a class="btn btn-danger  eqLogicAction pull-right" data-action="removeAll"><i class="fa fa-minus-circle"></i>  {{Supprimer tous les objets}}</a>
                </td>
            </tr><tr>
                <td>
                    <a class="btn btn-danger  eqLogicAction pull-right" data-action="exclusion"><i class="fa fa-sign-out"></i>  {{Exclusion}}</a>
                </td>
            </tr>
        </table>

    <!-- </form> Where is the start of form ? -->

    </div> <!-- End of developer area -->
</div> <!-- Where is this div start ? -->

<!-- Affichage des informations d un equipement specifique  -->
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px; display: none;">

        <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i>   {{Sauvegarder}}</a>
        <a class="btn btn-danger  eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i>  {{Supprimer}}</a>
        <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i>      {{Configuration avancée}}</a>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"                 ><a href="#"               aria-controls="home"    role="tab" data-toggle="tab" class="eqLogicAction" data-action="returnToThumbnailDisplay">       <i class="fa fa-arrow-circle-left"></i>                     </a></li>
            <li role="presentation" class="active"  ><a href="#eqlogictab"     aria-controls="home"    role="tab" data-toggle="tab">                                                                    <i class="fa fa-home"></i>                  {{Equipement}}  </a></li>
            <li role="presentation"                 ><a href="#paramtab"       aria-controls="home"    role="tab" data-toggle="tab">                                                                    <i class="fa fa-list-alt"></i>              {{Param}}       </a></li>
            <li role="presentation"                 ><a href="#commandtab"     aria-controls="profile" role="tab" data-toggle="tab">                                                                    <i class="fa fa-align-left"></i>            {{Commandes}}   </a></li>
        </ul>

<!-- Affichage des informations communes aux equipements  -->
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
            </div>

<!-- Affichage des informations specifiques a cet equipement: tab param  -->
            <div role="tabpanel" class="tab-pane" id="paramtab">
                <form class="form-horizontal">
                    <fieldset>
                                <hr>
                                Pour l'instant cette page consolide l'ensemble des parametres spécifiques à tous les équipments.<br>
                                L'idée est de ne faire apparaitre que les paragraphes en relation avec l'objet sélectionné.<br>
                                Par exemple si vous avez sélectionné une ampoule le paragraphe "Equipement sur piles" ne devrait pas apparaitre.<br>
                                Par defaut si cette partie n est pas definie dans le modele tout est affiché.<br>
                                Si cela est defini dans le modele alors que les parameteres necessaires sont affichés.<br>
                                <hr>

<?php
    /* Tcharp38: In which case this 'id' is supposed to be set ? <= quand on accede à la page de configuration d un equipement*/
    if (isset($_GET['id']) && ($_GET['id'] > 0)) {
        $eqLogic = eqLogic::byId($_GET['id']);
        if ( ($eqLogic->getConfiguration('paramBatterie', 'notDefined') == "true") || ($eqLogic->getConfiguration('paramBatterie', 'notDefined') == "notDefined") ) {
            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{Equipements sur piles.}}</label>';
            echo '</div>';

            echo '<div class="form-group" >';
            echo '<label class="col-sm-3 control-label" >{{Type de piles}}</label>';
            echo '<div class="col-sm-3">';
            echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_type"  placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>';
            echo '</div>';
            echo '</div>';

            echo '<hr>';
        }

        if ( ($eqLogic->getConfiguration('paramType', 'notDefined') == "telecommande") || ($eqLogic->getConfiguration('paramType', 'notDefined') == "notDefined") )  {

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{Telecommande}}</label>';
            echo '</div>';

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{Groupe}}</label>';
            echo '<div class="col-sm-3">';
            echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Groupe" placeholder="{{Adresse en hex sur 4 digits, ex:ae12}}"/>';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{on time (s)}}</label>';
            echo '<div class="col-sm-3">';
            echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="onTime" placeholder="{{Durée en secondes}}"/>';
            echo '</div>';
            echo '</div>';
        }

        if ( ($eqLogic->getConfiguration('paramType', 'notDefined') == "paramABC") || ($eqLogic->getConfiguration('paramType', 'notDefined') == "notDefined") )  {

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{Calibration (y=ax2+bx+c)}}</label>';
            echo '</div>';

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{parametre A}}</label>';
            echo '<div class="col-sm-3">';
            echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramA" placeholder="{{nombre}}"/>';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{parametre B}}</label>';
            echo '<div class="col-sm-3">';
            echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramB" placeholder="{{nombre}}"/>';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">{{parametre C}}</label>';
            echo '<div class="col-sm-3">';
            echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramC" placeholder="{{nombre}}"/>';
            echo '</div>';
            echo '</div>';
        }
    }
?>

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
<?php include_file('desktop', 'AbeilleDev', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>
    /* Show or hide developer area.
       If developer mode is enabled, default is to always expand this area. */
    $('#idDevGrpShowHide').on('click', function () {
        console.log("idDevGrpShowHide() click");
        var Label = document.getElementById("idDevGrpShowHide").innerText;
        if (Label == "Montrer") {
            document.getElementById("idDevGrpShowHide").innerText = "Cacher";
            document.getElementById("idDevGrpShowHide").className = "btn btn-danger";
            $("#idDevGrp").show();
        } else {
            document.getElementById("idDevGrpShowHide").innerText = "Montrer";
            document.getElementById("idDevGrpShowHide").className = "btn btn-success";
            $("#idDevGrp").hide();
        }
    });
    if ((typeof js_dbgDeveloperMode != 'undefined') && (js_dbgDeveloperMode == 1)) {
        var Label = document.getElementById("idDevGrpShowHide").innerText;
        document.querySelector('#idDevGrpShowHide').click(); 
    }

    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

<?php
for ($i = 1; $i <= 10; $i++) {
  ?>
  $('#bt_include<?php echo $i;?>').on('click', function ()  {
                                                console.log("bt_include<?php echo $i;?>");
                                                var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                                                xmlhttpMQTTSendInclude.onreadystatechange = function()  {
                                                                                                          if (this.readyState == 4 && this.status == 200) {
                                                                                                            xmlhttpMQTTSendIncludeResult = this.responseText;
                                                                                                          }
                                                                                                        };
                                                xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille<?php echo $i;?>_Ruche_SetPermit&payload=Inclusion", true);
                                                xmlhttpMQTTSendInclude.send();
                                                $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate <?php echo $i;?> doit se mettre à clignoter pour 4 minutes.}}', level: 'success'});
                                              }

                                      );
  <?php
}
?>

<?php
for ($i = 1; $i <= 10; $i++) {
 ?>
 $('#bt_include_stop<?php echo $i;?>').on('click', function ()  {
                                               console.log("bt_include_stop<?php echo $i;?>");
                                               var xmlhttpMQTTSendIncludeStop = new XMLHttpRequest();
                                               xmlhttpMQTTSendIncludeStop.onreadystatechange = function()  {
                                                                                                         if (this.readyState == 4 && this.status == 200) {
                                                                                                           xmlhttpMQTTSendIncludeResultStop = this.responseText;
                                                                                                         }
                                                                                                       };
                                               xmlhttpMQTTSendIncludeStop.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille<?php echo $i;?>_Ruche_SetPermit&payload=InclusionStop", true);
                                               xmlhttpMQTTSendIncludeStop.send();
                                               $('#div_alert').showAlert({message: '{{Arret mode inclusion demandé. La zigate <?php echo $i;?> doit arreter de clignoter.}}', level: 'success'});
                                             }

                                     );
 <?php
}
?>

</script>
