<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');

    $ruche = new Abeille();
    $commandIEEE = new AbeilleCmd();
    
    $dest = Abeille::mapPortAbeille(Abeille::getParameters()['AbeilleSerialPort']);
    if ( $ruche->byLogicalId( $dest.'/Ruche', 'Abeille') ) { $rucheId  = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId(); }
    
    $dest2 = Abeille::mapPortAbeille(Abeille::getParameters()['AbeilleSerialPort2']);
    if ( $dest2 != "none" ) {
        if ( $ruche->byLogicalId( $dest.'/Ruche', 'Abeille') ) { $rucheId2 = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId(); }
    }
    
    $dest3 = Abeille::mapPortAbeille(Abeille::getParameters()['AbeilleSerialPort3']);
    if ( $dest3 != "none" ) {
        if ( $ruche->byLogicalId( $dest.'/Ruche', 'Abeille') ) { $rucheId3 = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId(); }
    }
        
    $dest4 = Abeille::mapPortAbeille(Abeille::getParameters()['AbeilleSerialPort4']);
    if ( $dest4 != "none" ) {
        if ( $ruche->byLogicalId( $dest.'/Ruche', 'Abeille') ) { $rucheId4 = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId(); }
    }
    
    $dest5 = Abeille::mapPortAbeille(Abeille::getParameters()['AbeilleSerialPort5']);
    if ( $dest5 != "none" ) {
        if ( $ruche->byLogicalId( $dest.'/Ruche', 'Abeille') ) { $rucheId5 = $ruche->byLogicalId( $dest.'/Ruche', 'Abeille')->getId(); }
    }

$parameters_info = Abeille::getParameters();

?>

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

<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px; ">

        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>

        <div class="eqLogicThumbnailContainer">

            <div class="cursor changeIncludeState card" id="bt_include" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-link" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Inclusion Z1}}</center></span>
            </div>

            <div class="cursor changeIncludeState card" id="bt_include2" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-link" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Inclusion Z2}}</center></span>
            </div>

            <div class="cursor changeIncludeState card" id="bt_include3" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-link" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Inclusion Z3}}</center></span>
            </div>

            <div class="cursor changeIncludeState card" id="bt_include4" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-link" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Inclusion Z4}}</center></span>
            </div>

            <div class="cursor changeIncludeState card" id="bt_include5" data-mode="1" data-state="1" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 0px; top: 0px;">
                <center>
                    <i class="fa fa-link" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Inclusion Z5}}</center></span>
            </div>


            <div class="cursor" id="bt_createTimer" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">
                <center>
                    <i class="fa fa-hourglass-half" style="font-size : 6em;color:#767676;"></i>
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
                    <i class="fa fa-map" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Network Graph</center></span>
            </div>

            <div class="cursor" id="bt_graph"
                style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-flask" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Graph</center></span>
            </div>

            <div class="cursor" id="bt_listeCompatibilite" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-align-left" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Compatibilite</center></span>
            </div>

            <div class="cursor" id="bt_Inconnu" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-paperclip" style="font-size : 5em;color:#767676;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>Inconnu</center></span>
            </div>


        </div>

        <legend><i class="fa fa-table"></i> {{Mes Abeilles}}</legend>


        <form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">
        <input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchEqlogicB" />

        <div class="eqLogicThumbnailContainer">

            <?php
            $dir = dirname(__FILE__) . '/../../images/';
            $files = scandir($dir);
            foreach ($eqLogics as $eqLogic) {
                
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
                echo '<div class="eqLogicDisplayCardB">';
                    echo '<input type="checkbox" name="eqSelected-'.$eqLogic->getId().'" />';
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff ; width : 200px;' . $opacity . '" >';
                        echo '<table><tr><td><img src="plugins/Abeille/images/' . $path . '.png"  /></td><td>' . $eqLogic->getHumanName(true, true) . '</td<</tr></table>';
                    echo '</div>';
                echo '</div>';
            }
            ?>

        </div>

        <legend><i class="fa fa-cogs"></i> {{Appliquer les commandes sur la selection}}</legend>

<style>
table.one {
  border: 1px solid black;
  color: black;
}

th.one {
  padding: 10px;
}



td.one {
  border: 1px solid black;
  padding: 3px;
}

td.two {
    border: 1px solid black;
    padding: 10px;
}

</style>

<label>Groupes</label>
<a class="btn btn-primary btn-xs" target="_blank" href="https://abeilledocsphinx.readthedocs.io/fr/latest/Groups.html"><i class="fas fa-book"></i>Documentation</a>
<table><tr><td class="two">

        <table class="one">
          <thead>
            <tr>
                <th class="one">{{Module}}</th>
                <th class="one">{{Telecommande}}</th>
                <th class="one">{{Membre}}</th>
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

</td><td class="two">

        <table>
            <tr>
                <td align="center">
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

</td></tr></table>

<hr>

<label>Scenes</label>
<a class="btn btn-primary btn-xs" target="_blank" href="https://abeilledocsphinx.readthedocs.io/fr/latest/Scenes.html"><i class="fas fa-book"></i>Documentation</a>
<table class="one"><tr><td class="two">

            <table class="one">
            <thead>
            <tr>
            <th class="one">{{Module}}</th>
            <th class="one">{{Telecommande}}</th>
            <th class="one">{{Membre}}</th>
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

      </td><td class="two">
          <table class="one">
            <tr>
                <td class="one">
                    <label control-label data-toggle="tooltip" title="en hex de 0000 a ffff, probablement celui que vous avez récuperé de votre télécommande.">Group Id</label>
                    <input type="text" name="groupIdScene1" placeholder="XXXX">
                    <br>
                    <input type="submit" name="submitButton" value="Get Scene Membership">
                    <input type="submit" name="submitButton" value="Remove All Scene">
                </td>
            </tr><tr class="one">
                </td><td class="one">
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

</td></tr></table>
<br>
<ol>
    <li>
    "Get Scene Membership" interroge l équipement pour avoir les scenes associées à un groupe mais Abeille ne peut traiter la réponse incomplète. La modification est en cours de dev cote firmware zigate et ne fonctionnent pas a ce stade. Par contre en attendant vous pouvez voir passer la réponse dans le log AbeilleParser message "Scene Membership"
    </li>
    <li>
    "View Scene" interroge l équipement pour avoir les détails d une scene mais Abeille ne peut traiter la réponse incomplète. La modification est en cours de dev cote firmware zigate et ne fonctionnent pas a ce stade. Par contre en attendant vous pouvez voir passer la réponse dans le log AbeilleParser message "Scene View"
    </li>
    <li>
    "scene Group Recal" n est pas encore fonctionnelle.
    </li>
</ol>

<hr>

<table>
<tr>
    <td>
        Modele
    </td>
</tr><tr>
    <td>
        <input type="submit" name="submitButton" value="Apply Template">
    </td>
  </tr><tr>
      <td>
        <input type="submit" name="submitButton" value="Get Infos from NE">
      </td>
  </tr><tr>
    <td>
      <label>Channel Mask</label>   <input type="text" name="channelMask"   placeholder="XXXXXXXX">
            <input type="submit" name="submitButton" value="Set Channel Mask Z1">
            <input type="submit" name="submitButton" value="Set Channel Mask Z2">
            <input type="submit" name="submitButton" value="Set Channel Mask Z3">
            <input type="submit" name="submitButton" value="Set Channel Mask Z4">
            <input type="submit" name="submitButton" value="Set Channel Mask Z5"></br>
        <i>Pour l instant le mask doit être défini à la main, je verrai comment faire une belle interface plus tard. Vous devez definir les canaux qui sont authorisés pour la zigate. Si 1 canal authorisé, si 0 canal pas authorisé.</br>
        Au démarrage la zigate choisira parmi ces canaux en fonction de sa mesure d occupation du canal.</br>
        Dans le zigbee vous avez les canaux 11 à 26 disponibles.</br>
        Le mask couvre les canaux 0 à 31. De ce fait il faut positionner à 0 les canaux 0 à 10 et les canaux 27 à 31, on commence par canal 31 et on fini par canal 0 => 00000xxxxxxxxxxxxxxxx00000000000</br>
        Les x étant les canaux 26 à 11. Si vous voulez tous les activer alors le mask vaut: 00000111111111111111100000000000 (en Hexa: 0x07FFF800)</br>
        Si vous ne voulez que le canal 26 alors ca donne: 00000100000000000000000000000000 (en Hexa: 0x04000000)</br>
        Si vous ne voulez que le canal 20 alors ca donne: 00000000000100000000000000000000 (en Hexa: 0x00100000)</br>
        Si vous ne voulez que le canal 15 alors ca donne: 00000000000000001000000000000000 (en Hexa: 0x00008000)</br>
        Dans le champ il faut mettre la valeur en hexa. Il faut bien 8 digit sans le 0x devant.</br>
        Pour convertir le binaire en hexa vous avez: https://www.binaryhexconverter.com/binary-to-hex-converter</br>
        </br>
        </i>
      <label>Extended PANID</label> <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
        <input type="submit" name="submitButton" value="Set Extended PANID Z1">
        <input type="submit" name="submitButton" value="Set Extended PANID Z2">
        <input type="submit" name="submitButton" value="Set Extended PANID Z3">
        <input type="submit" name="submitButton" value="Set Extended PANID Z4">
        <input type="submit" name="submitButton" value="Set Extended PANID Z5"></br>

      <label>Tx Power</label>
        <input type="text" name="TxPowerValue"  placeholder="XX">
        <input type="submit" name="submitButton" value="TxPower Z1">
        <input type="submit" name="submitButton" value="TxPower Z2">
        <input type="submit" name="submitButton" value="TxPower Z3">
        <input type="submit" name="submitButton" value="TxPower Z4">
        <input type="submit" name="submitButton" value="TxPower Z5"></br>
    </td>
  </tr>
</table>

<hr>


            <legend><i class="fa fa-cog"></i> {{ZiGate}}</legend>
            <br>
            ZiGate 1<br>
            <table class="one">
                <tr><td class="one">Last</td>           <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Time-Time'))        { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Time-Time')->execCmd();} ?>         </td></tr>
                <tr><td class="one">Last Stamps</td>    <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Time-TimeStamp'))   { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Time-TimeStamp')->execCmd();} ?>    </td></tr>
                <tr><td class="one">SW</td>             <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'SW-Application'))   { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'SW-Application')->execCmd();} ?></td></tr>
                <tr><td class="one">SDK</td>            <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'SW-SDK'))           { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'SW-SDK')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Status</td> <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Network-Status'))   { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Network-Status')->execCmd();} ?></td></tr>
                <tr><td class="one">Short address</td>  <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Short-Addr'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Short-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">PAN Id</td>         <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'PAN-ID'))           { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">Extended PAN Id</td><td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Ext_PAN-ID'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Ext_PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">IEEE address</td>   <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr'))        { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'IEEE-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Channel</td><td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Network-Channel'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'Network-Channel')->execCmd();} ?></td></tr>
                <tr><td class="one">Inclusion</td>      <td class="one"><?php if ($rucheId && $commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'permitJoin-Status')){  if ($commandIEEE->byEqLogicIdAndLogicalId($rucheId, 'permitJoin-Status')->execCmd()) echo "Oui"; else echo "Non";} ?></td></tr>
            </table>
            <br>
            ZiGate 2<br>
            <table class="one">
                <tr><td class="one">Last</td>           <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Time-Time'))        { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Time-Time')->execCmd();} ?>         </td></tr>
                <tr><td class="one">Last Stamps</td>    <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Time-TimeStamp'))   { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Time-TimeStamp')->execCmd();} ?>    </td></tr>
                <tr><td class="one">SW</td>             <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'SW-Application'))   { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'SW-Application')->execCmd();} ?></td></tr>
                <tr><td class="one">SDK</td>            <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'SW-SDK'))           { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'SW-SDK')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Status</td> <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Network-Status'))   { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Network-Status')->execCmd();} ?></td></tr>
                <tr><td class="one">Short address</td>  <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Short-Addr'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Short-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">PAN Id</td>         <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'PAN-ID'))           { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">Extended PAN Id</td><td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Ext_PAN-ID'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Ext_PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">IEEE address</td>   <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'IEEE-Addr'))        { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'IEEE-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Channel</td><td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Network-Channel'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'Network-Channel')->execCmd();} ?></td></tr>
                <tr><td class="one">Inclusion</td>      <td class="one"><?php if ($rucheId2 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'permitJoin-Status')){  if ($commandIEEE->byEqLogicIdAndLogicalId($rucheId2, 'permitJoin-Status')->execCmd()) echo "Oui"; else echo "Non";} ?></td></tr>
            </table>
            <br>
            ZiGate 3<br>
            <table class="one">
                <tr><td class="one">Last</td>           <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Time-Time'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Time-Time')->execCmd();} ?>         </td></tr>
                <tr><td class="one">Last Stamps</td>    <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Time-TimeStamp'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Time-TimeStamp')->execCmd();} ?>    </td></tr>
                <tr><td class="one">SW</td>             <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'SW-Application'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'SW-Application')->execCmd();} ?></td></tr>
                <tr><td class="one">SDK</td>            <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'SW-SDK'))          { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'SW-SDK')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Status</td> <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Network-Status'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Network-Status')->execCmd();} ?></td></tr>
                <tr><td class="one">Short address</td>  <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Short-Addr'))      { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Short-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">PAN Id</td>         <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'PAN-ID'))          { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">Extended PAN Id</td><td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Ext_PAN-ID'))      { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Ext_PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">IEEE address</td>   <td class="one"><?php if ($rucheId3 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'IEEE-Addr'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'IEEE-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Channel</td><td class="one"><?php if ($rucheId3 &&$commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Network-Channel'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'Network-Channel')->execCmd();} ?></td></tr>
                <tr><td class="one">Inclusion</td>      <td class="one"><?php if ($rucheId3 &&$commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'permitJoin-Status')){ if ($commandIEEE->byEqLogicIdAndLogicalId($rucheId3, 'permitJoin-Status')->execCmd()) echo "Oui"; else echo "Non";} ?></td></tr>
            </table>
            <br>
            ZiGate 4<br>
            <table class="one">
                <tr><td class="one">Last</td>           <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Time-Time'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Time-Time')->execCmd();} ?>         </td></tr>
                <tr><td class="one">Last Stamps</td>    <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Time-TimeStamp'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Time-TimeStamp')->execCmd();} ?>    </td></tr>
                <tr><td class="one">SW</td>             <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'SW-Application'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'SW-Application')->execCmd();} ?></td></tr>
                <tr><td class="one">SDK</td>            <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'SW-SDK'))          { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'SW-SDK')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Status</td> <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Network-Status'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Network-Status')->execCmd();} ?></td></tr>
                <tr><td class="one">Short address</td>  <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Short-Addr'))      { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Short-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">PAN Id</td>         <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'PAN-ID'))          { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">Extended PAN Id</td><td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Ext_PAN-ID'))      { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Ext_PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">IEEE address</td>   <td class="one"><?php if ($rucheId4 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'IEEE-Addr'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'IEEE-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Channel</td><td class="one"><?php if ($rucheId4 &&$commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Network-Channel'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'Network-Channel')->execCmd();} ?></td></tr>
                <tr><td class="one">Inclusion</td>      <td class="one"><?php if ($rucheId4 &&$commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'permitJoin-Status')){ if ($commandIEEE->byEqLogicIdAndLogicalId($rucheId4, 'permitJoin-Status')->execCmd()) echo "Oui"; else echo "Non";} ?></td></tr>
            </table>
            <br>
            ZiGate 5<br>
            <table class="one">
                <tr><td class="one">Last</td>           <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Time-Time'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Time-Time')->execCmd();} ?>         </td></tr>
                <tr><td class="one">Last Stamps</td>    <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Time-TimeStamp'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Time-TimeStamp')->execCmd();} ?>    </td></tr>
                <tr><td class="one">SW</td>             <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'SW-Application'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'SW-Application')->execCmd();} ?></td></tr>
                <tr><td class="one">SDK</td>            <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'SW-SDK'))          { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'SW-SDK')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Status</td> <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Network-Status'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Network-Status')->execCmd();} ?></td></tr>
                <tr><td class="one">Short address</td>  <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Short-Addr'))      { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Short-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">PAN Id</td>         <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'PAN-ID'))          { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">Extended PAN Id</td><td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Ext_PAN-ID'))      { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Ext_PAN-ID')->execCmd();} ?></td></tr>
                <tr><td class="one">IEEE address</td>   <td class="one"><?php if ($rucheId5 && $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'IEEE-Addr'))       { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'IEEE-Addr')->execCmd();} ?></td></tr>
                <tr><td class="one">Network Channel</td><td class="one"><?php if ($rucheId5 &&$commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Network-Channel'))  { echo $commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'Network-Channel')->execCmd();} ?></td></tr>
                <tr><td class="one">Inclusion</td>      <td class="one"><?php if ($rucheId5 &&$commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'permitJoin-Status')){ if ($commandIEEE->byEqLogicIdAndLogicalId($rucheId5, 'permitJoin-Status')->execCmd()) echo "Oui"; else echo "Non";} ?></td></tr>
            </table>

            <i>Ces tableau ne sont pas automatiquement rafraichi, il est mis à jour à l ouverture de la page.</i>
            <br>
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

            <tr>
                <td>
                    <div id="bt_setTimeServer">
                        <a class="btn btn-success" data-action="setTimeServer"><i class="fa fa-sign-in"></i>{{Set Time}}</a>
                    </div>
                </td>
                <td>
                    <div id="bt_getTimeServer">
                        <a class="btn btn-success" data-action="getTimeServer"><i class="fa fa-sign-out"></i>{{Get Time}}</a>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div id="bt_setOnZigateLed">
                        <a class="btn btn-success" data-action="setOnZigateLed"><i class="fa fa-sign-in"></i>{{Allume Led}}</a>
                    </div>
                </td>
                <td>
                    <div id="bt_setOffZigateLed">
                        <a class="btn btn-success" data-action="setOffZigateLed"><i class="fa fa-sign-out"></i>{{Eteint Led}}</a>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div id="bt_setCertificationCE">
                        <a class="btn btn-success" data-action="setCertificationCE"><i class="fa fa-sign-in"></i>{{Certification CE}}</a>
                    </div>
                </td>
                <td>
                    <div id="bt_setCertificationFCC">
                        <a class="btn btn-success" data-action="setCertificationFCC"><i class="fa fa-sign-out"></i>{{Certification FCC}}</a>
                    </div>
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
            <li role="presentation"                 ><a href="#"               aria-controls="home"    role="tab" data-toggle="tab" class="eqLogicAction" data-action="returnToThumbnailDisplay">       <i class="fa fa-arrow-circle-left"></i>                     </a></li>
            <li role="presentation" class="active"  ><a href="#eqlogictab"     aria-controls="home"    role="tab" data-toggle="tab">                                                                    <i class="fa fa-home"></i>                  {{Equipement}}  </a></li>
            <li role="presentation"                 ><a href="#paramtab"       aria-controls="home"    role="tab" data-toggle="tab">                                                                    <i class="fa fa-list-alt"></i>              {{Param}}       </a></li>
            <li role="presentation"                 ><a href="#commandtab"     aria-controls="profile" role="tab" data-toggle="tab">                                                                    <i class="fa fa-align-left"></i>            {{Commandes}}   </a></li>
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
                                <label class="col-sm-3 control-label">{{Action sur demarrage}}</label>
                                <div class="col-sm-3">
                                <input id="idTimerActionStart" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionStart" placeholder="#cmd#" style="margin-bottom : 5px;width : 70%; display : inline-block;">
                                <a class="btn btn-default btn-sm cursor" id="bt_TimerActionStart" data-input="infoName" style="margin-left : 5px;">
                          	        Rechercher équipement
                                </a>
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
                                <input id="idTimerActionRamp" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionRamp" placeholder="#cmd#" style="margin-bottom : 5px;width : 70%; display : inline-block;">
                                <a class="btn btn-default btn-sm cursor" id="bt_TimerActionRamp" data-input="infoName" style="margin-left : 5px;">
                                    Rechercher équipement
                                </a>
                                </div>
                                </div>

                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Action sur arret}}</label>
                                <div class="col-sm-3">
                                <input id="idTimerActionStop" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionStop" placeholder="#cmd#" style="margin-bottom : 5px;width : 70%; display : inline-block;">
                                <a class="btn btn-default btn-sm cursor" id="bt_TimerActionStop" data-input="infoName" style="margin-left : 5px;">
                                    Rechercher équipement
                                </a>
                                </div>
                                </div>

                                <div class="form-group">
                                <label class="col-sm-3 control-label">{{Action sur annulation}}</label>
                                <div class="col-sm-3">
                                <input id="idTimerActionCancel" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TimerActionCancel" placeholder="#cmd#" style="margin-bottom : 5px;width : 70%; display : inline-block;">
                                <a class="btn btn-default btn-sm cursor" id="bt_TimerActionCancel" data-input="infoName" style="margin-left : 5px;">
                                    Rechercher équipement
                                </a>
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
