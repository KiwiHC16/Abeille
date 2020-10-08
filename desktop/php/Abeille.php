<?php
    if(0){
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
    }

sendVarToJS('eqType', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');

$zigateNb = config::byKey('zigateNb', 'Abeille', '1');

$parametersAbeille = Abeille::getParameters();

    /* Developers debug features */
    $dbgFile = dirname(__FILE__)."/../../debug.php";
    if (file_exists($dbgFile)) {
        include_once $dbgFile;
        $dbgDeveloperMode = TRUE;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
    }
?>

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
<?php
$outils = array(
                'health'    => array( 'bouton'=>'bt_healthAbeille',         'icon'=>'fa-medkit',        'text'=>'{{Santé}}' ),
                'netList'   => array( 'bouton'=>'bt_networkAbeilleList',    'icon'=>'fa-sitemap',       'text'=>'{{Network List}}' ),
                'net'       => array( 'bouton'=>'bt_networkAbeille',        'icon'=>'fa-map',           'text'=>'{{Network Graph}}' ),
                'graph'     => array( 'bouton'=>'bt_graph',                 'icon'=>'fa-flask',         'text'=>'{{Graph}}' ),
                'compat'    => array( 'bouton'=>'bt_listeCompatibilite',    'icon'=>'fa-align-left',    'text'=>'{{Compatibilite}}' ),
                'inconnu'   => array( 'bouton'=>'bt_Inconnu',               'icon'=>'fa-paperclip',     'text'=>'{{Inconnu}}' ),
                'support'   => array( 'bouton'=>'bt_getCompleteAbeilleConf','icon'=>'fa-medkit',        'text'=>'{{Support}}' ),
                );
    ?>
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px; ">

        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>

        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction" data-action="gotoPluginConf"
                 style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
            </div>

<?php
    foreach ( $outils as $key=>$outil ) {
            echo '<div class="cursor" id="'.$outil['bouton'].'" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">';
            echo '  <center>';
            echo '      <i class="fa '.$outil['icon'].'" style="font-size : 6em;color:#767676;"></i>';
            echo '  </center>';
            echo '  <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>'.$outil['text'].'</center></span>';
            echo '</div>';
    }
?>

        </div>

    <!-- Icones de toutes les abeilles  -->
    <legend><i class="fa fa-table"></i> {{Mes Abeilles}}</legend>
    <form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">

<?php
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

    $NbOfZigatesON = 0; // Number of enabled zigates
    $eqAll = array(); // All equipments, sorted per zigate
    for ( $i=1; $i<=$zigateNb; $i++ ) {
        if ( config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y' )
            continue; // This Zigate is not enabled

        $NbOfZigatesON++;
        if ( Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille') ) {
            echo 'Zigate'.$i .' - '. Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')->getHumanName();
        }
        echo "&nbsp&nbsp&nbsp";
        echo '<i id="bt_include'.$i.'" class="fa fa-plus-circle" style="font-size:160%;color:green" title="Inclusion: clic sur le plus pour mettre la zigate en inclusion."></i>';
        echo "&nbsp&nbsp&nbsp";
        echo '<i id="bt_include_stop'.$i.'" class="fa fa-minus-circle" style="font-size:160%;color:red" title="Inclusion: clic sur le moins pour arreter le mode inclusion."></i>';
        echo "&nbsp&nbsp&nbsp";
        echo '<i id="bt_createRemote'.$i.'"class="fa fa-gamepad" style="font-size:160%;color:orange" title="Clic pour créer une télécommande virtuelle."></i>';

        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
            echo '<br>';
            $port = config::byKey('AbeilleSerialPort'.$i, 'Abeille', '');

            /* Remove equipments from Jeedom only */
            echo '<a onclick="removeBeesJeedom('.$i.')" class="btn btn-primary btn-xs" title="Supprime les équipement(s) sélectionné(s) de Jeedom uniquement.">{{Supprimer de Jeedom}}</a>';
        }

        echo '<div class="eqLogicThumbnailContainer">';
        $dir = dirname(__FILE__) . '/../../images/';
        $files = scandir($dir);
        $eqPerZigate = array(); // All equipements linked to current zigate
        /* Display beehive card then bee cards */
        foreach ($eqLogics as $eqLogic) {
            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/Ruche'
            if ($eqLogicId != "Abeille".$i."/Ruche")
                continue;
            displayBeeCard($eqLogic, $files, $i);
            $eq = array();
            $eq['id'] = $eqLogic->getId();
            $eq['logicalId'] = $eqLogicId;
            $eqPerZigate[] = $eq;
        }
        foreach ($eqLogics as $eqLogic) {
            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/Ruche'
            list( $net, $addr ) = explode( "/", $eqLogicId);
            if ( $net != 'Abeille'. $i)
                continue;
            if ($eqLogicId == "Abeille".$i."/Ruche")
                continue; // Skipping beehive
            displayBeeCard($eqLogic, $files, $i);
            $eq = array();
            $eq['id'] = $eqLogic->getId();
            $eq['logicalId'] = $eqLogicId;
            $eqPerZigate[] = $eq;
        }
        echo '<script>var js_eqZigate'.$i.' = \''.json_encode($eqPerZigate).'\';</script>';
        $eqAll['zigate'.$i] = $eqPerZigate;
        echo ' </div>';
    }

    if ($NbOfZigatesON == 0) { // No Zigate to display. UNEXPECTED !
        echo "<div style=\"background: #e9e9e9; font-weight: bold; padding: .2em 2em;\"><br>";
        echo "   <span style=\"color:red\">";
        if ($zigateNb == 0)
            echo "Aucune Zigate n'est définie !";
        else
            echo "Aucune Zigate n'est activée !";
        echo "   </span><br>";
        echo "    Veuillez aller à la page de configuration pour corriger.<br><br>";
        echo "</div>";
    }
?>

<!-- Gestion des groupes et des scenes  -->
        <legend><i class="fa fa-cogs"></i> {{Appliquer les commandes sur la selection}}</legend>

<label>Groupes</label>
<a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Groups.html"><i class="fas fa-book"></i>Documentation</a>

<div id="the whole thing" style="height:100%; width:100%; overflow: hidden;">
<div id="leftMargin" style="float: left; width:10%;">.
</div>
<div id="leftThing" style="float: left; width:40%;">
        <table border="1" style="border:1px">
          <thead>
            <tr>
                <th>{{Module}}</th>
                <th>{{Telecommande}}</th>
                <th>{{Membre}}</th>
            </tr>
          </thead>
          <tbody>

        <?php

        $abeille = new Abeille();
        $commandIEEE = new AbeilleCmd();

        foreach ($eqLogics as $key => $eqLogic) {

              $name= "";
              $groupMember = "";
              $groupTele = "";
              $print=0;

              $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();

              $name = $eqLogic->getHumanName(true);

              if ( $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Group-Membership') ) {
                if ( strlen($commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Group-Membership')->execCmd())>2 ) {

                  $groupMember = str_replace('-',' ',$commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Group-Membership')->execCmd());
                  $print = 1;
                }
              }

              if ( strlen($eqLogic->getConfiguration('Groupe'))>3 ) {
                $groupTele = $eqLogic->getConfiguration('Groupe');
                $print = 1;
              }

            if ( $print ) echo '<tr><td class="one">'.$name.'</td><td align="center" class="one">'.$groupTele.'</td><td align="center" class="one">'.$groupMember.'</td></tr>';

        }

        ?>
          </tbody>
        </table>
</div>


<div id="rightThing" style="float: left; width:40%;">
        <table>
            <tr>
                <td align="center">
                    <input type="submit" name="submitButton" value="Get Infos from NE">
                    <input type="submit" name="submitButton" value="Get Group">
                </td>
            </tr>
            <tr>
                <td>
                <hr>
                </td>
            </tr>
          <tr>
                <td>
                    <label control-label data-toggle="tooltip" title="en hex de 0000 a ffff, probablement celui que vous avez récuperé de votre télécommande.">Id</label>
                    <input type="text" name="group" placeholder="XXXX"><br>
                    <input type="submit" name="submitButton" value="Add Group">
                    <input type="submit" name="submitButton" value="Remove Group"></br>
                    <input type="submit" name="submitButton" value="Set Group Remote">
                </td>
            </tr>
          </table>
</div>

</div>

<hr>

<label>Scenes</label>
<a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Scenes.html"><i class="fas fa-book"></i>Documentation</a>


<div id="the whole thing" style="height:100%; width:100%; overflow: hidden;">
<div id="leftMargin" style="float: left; width:10%;">.
</div>
<div id="leftThing" style="float: left; width:40%;">
            <table>
            <thead>
            <tr>
            <th>{{Module}}</th>
            <th>{{Telecommande}}</th>
            <th>{{Membre}}</th>
            </tr>
            </thead>
            <tbody>

            <?php

                $abeille = new Abeille();
                $commandIEEE = new AbeilleCmd();

                foreach ($eqLogics as $key => $eqLogic) {

                    $name= "";
                    $sceneMember = "";
                    $sceneTele = "";
                    $print=0;

                    $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();

                    $name = $eqLogic->getHumanName(true);

                    if ( $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Scene-Membership') ) {
                        if ( strlen($commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Scene-Membership')->execCmd())>2 ) {

                            $sceneMember = str_replace('-',' ',$commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Scene-Membership')->execCmd());
                            $print = 1;
                        }
                    }

                    if ( strlen($eqLogic->getConfiguration('Scene'))>3 ) {
                        $sceneTele = $eqLogic->getConfiguration('Scene');
                        $print = 1;
                    }

                    if ( $print ) echo '<tr><td class="one">'.$name.'</td><td align="center" class="one">'.$sceneTele.'</td><td align="center" class="one">'.$sceneMember.'</td></tr>';

                }

                ?>
            </tbody>
            </table>
</div>
<div id="rightThing" style="float: left; width:40%;">
          <table>
            <tr>
                <td>
                    <label control-label data-toggle="tooltip" title="en hex de 0000 a ffff, probablement celui que vous avez récuperé de votre télécommande.">Group Id</label>
                    <input type="text" name="groupIdScene1" placeholder="XXXX">
                    <br>
                    <input type="submit" name="submitButton" value="Get Scene Membership">
                    <input type="submit" name="submitButton" value="Remove All Scene">
                </td>
            </tr><tr>
                </td><td>
                    <label>Group Id</label><input type="text" name="groupIdScene2" placeholder="XXXX">
                    <label>Scene Id</label><input type="text" name="sceneID" placeholder="YY">
                    <br>
                    <input type="submit" name="submitButton" value="View Scene">
                    <input type="submit" name="submitButton" value="Add Scene">
                    <input type="submit" name="submitButton" value="Remove Scene">
                    <input type="submit" name="submitButton" value="Store Scene">
                    <input type="submit" name="submitButton" value="Recall Scene">
                    <input type="submit" name="submitButton" value="scene Group Recall">
                </td>
            </tr>
          </table>
</div>
</div>

<br>

<hr>
<!-- Gestion des parametres Zigate -->
<legend><i class="fa fa-cogs"></i> {{Gestion des parametres radio Zigate}}</legend>

<label>Channel Mask</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i>Documentation</a></br>
Channel Mask:   <input type="text" name="channelMask"   placeholder="XXXXXXXX">
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
        if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
            echo '<input type="submit" name="submitButton" value="Set Channel Mask Z'.$i.'">';
        }
    }
    ?>
</br>
</br>

<label>Extended PANID</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Radio.html"><i class="fas fa-book"></i>Documentation</a></br>
Extended PANID: <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Extended PANID Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Tx Power</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Radio.html"><i class="fas fa-book"></i>Documentation</a></br>
Tx Power: <input type="text" name="TxPowerValue"  placeholder="XX">
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="TxPower Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Time</label> </br>
Set Time:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="SetTime Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Get Time</label> </br>
Get Time:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="getTime Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Led On</label> </br>
Set Led On:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="SetLedOn Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Led Off</label> </br>
Set Led Off:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="SetLedOff Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Certification CE</label> </br>
Set Certification CE:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Certification CE Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Certification FCC</label> </br>
Set Certification FCC:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Certification FCC Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Start Zigbee Network</label> </br>
Start Zigbee Network:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Start Network Z'.$i.'">';
    }
}
?>
</br>
</br>

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




            <br>

            <legend><i class="fa fa-cog"></i> {{Dev en cours}}</legend>

            <input type="submit" name="submitButton" value="Identify">

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

        </form>

    </div>

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
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>

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
