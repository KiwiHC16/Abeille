<?php
   /* This file is part of Jeedom.
    *
    * Jeedom is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * Jeedom is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
    */

    /* Developers debug features */
    require_once __DIR__.'/../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        $dbgDeveloperMode = true;
        $GLOBALS['dbgDeveloperMode'] = true;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        include_once __DIR__."/../core/php/AbeilleGit.php"; // For 'switchBranch' support

        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect()) {
        include_file('desktop', '404', 'php');
        die();
    }

    require_once __DIR__.'/../core/class/Abeille.class.php';
    include_once __DIR__."/../core/php/AbeilleInstall.php";

    /* Reading version */
    $file = fopen(__DIR__."/Abeille.version", "r");
    $line = fgets($file); // Should be a comment
    $abeilleVersion = trim(fgets($file)); // Should be Abeille's version
    fclose($file);

    echo '<script>var js_wifiLink = "'.wifiLink.'";</script>'; // PHP to JS
    echo '<script>var js_maxGateways = "'.maxGateways.'";</script>'; // PHP to JS

    /* Returns current cmd value identified by its Jeedom logical ID name */
    function getCmdValueByLogicId($eqId, $logicId) {
        $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, $logicId);
        if (!is_object($cmd))
            return "";
        return $cmd->execCmd();
    }

    /* Returns cmd ID identified by its Jeedom logical ID name */
    function getCmdIdByLogicId($eqId, $logicId) {
        $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, $logicId);
        if (!is_object($cmd))
            return "";
        return $cmd->getId();
    }

    function displayGateway($zgId) {
        global $dbgDeveloperMode;

        $eqLogic = eqLogic::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
        if ($eqLogic) {
            $eqId = $eqLogic->getId();
        }
        echo '<div class="form-group">';
            echo '<div class="col-lg-3">';
                echo '<h4>';
                    echo 'Passerelle '.$zgId;
                echo '</h4>';
            echo '</div>';
            echo '<div class="col-lg-9">';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Nom}} : </label>';
            echo '<div class="col-lg-4">';
                if (Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille')) {
                    $zgName = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille')->getName();
                } else {
                    $zgName = "";
                }
                echo '<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="'.$zgName.'" readonly title="{{Nom de la zigate; N\'est modifiable que via la page de gestion d\'Abeille après sauvegarde.}}">';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Type}} : </label>';
            echo '<div class="col-lg-4">';
                echo '<div class="form-group">';
                    echo '<div class="col-lg-5">';
                        echo '<select id="idSelGtwType'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwType'.$zgId.'" onchange="gtwTypeChanged('.$zgId.')"  title="{{Type de clef}}">';
                            echo '<option value="zigate" selected>{{Zigate}}</option>';
                            if (isset($dbgDeveloperMode))
                            echo '<option value="ezsp">{{EmberZnet/EZSP}}</option>';
                        echo '</select>';
                    echo '</div>';
                    echo '<div class="col-lg-7">';
                        echo '<select id="idZigateSubType'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwSubType'.$zgId.'" onchange="gtwSubTypeChanged('.$zgId.')"  title="{{Modèle de clef}}">';
                            echo '<option value="USB" selected>{{USB v1}}</option>';
                            echo '<option value="WIFI">{{WIFI/Ethernet}}</option>';
                            echo '<option value="PI">{{PI v1}}</option>';
                            echo '<option value="DIN">{{DIN v1}}</option>';
                            echo '<option value="USBv2">{{USB +/v2}}</option>';
                            echo '<option value="PIv2">{{PI +/v2}}</option>';
                            echo '<option value="DINv2">{{DIN +/v2}}</option>';
                        echo '</select>';
                        echo '<select id="idEzspSubType'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwSubType'.$zgId.'" onchange="gtwSubTypeChanged('.$zgId.')"  title="{{Modèle de clef}}" style="display:none">';
                            echo '<option value="DEFAULT" selected>{{Défaut}}</option>';
                        echo '</select>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Port série}} : </label>';
            echo '<div class="col-lg-4">';
                echo '<select id="idSelSP'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwPort'.$zgId.'" title="{{Port série si zigate USB, Pi ou DIN}}" disabled>';
                    echo '<option value="none" selected>{{Aucun}}</option>';
                    echo '<option value="'.wifiLink.$zgId.'" >{{WIFI'.$zgId.'}}</option>';
                    echo '<option value="/dev/monitZigate'.$zgId.'" >{{Monit'.$zgId.'}}</option>';
                    foreach (jeedom::getUsbMapping('', true) as $name => $value) {
                        $value2 = substr($value, 5); // Remove '/dev/'
                        if ($value2 == "ttyS1")
                            $name .= ", Orange Pi Zero";
                        echo '<option value="'.$value.'">'.$value2.' ('.$name.')</option>';
                    }
                    // KiwiHC16: 3 lignes suivantes necessaire pour KiwiHC16. Dans mon multi Zigate j ai des liens créés automatiquement pour ne pas les melanger lors d un reboot.
                    foreach (ls('/dev/', 'tty*') as $value) {
                         echo '<option value="/dev/'.$value.'">'.$value.'</option>';
                    }
                echo '</select>';
                // TODO: getUsbMapping={"Cubiboard":"\/dev\/ttyS0","Jeedom Luna Zwave":"\/dev\/ttyS1","Odroid C2":"\/dev\/ttyS1","Jeedom Atlas":"\/dev\/ttyS2","Orange PI":"\/dev\/ttyS3"}
                // echo "getUsbMapping=".json_encode(jeedom::getUsbMapping('', true))."<br>";
            echo '</div>';
            echo '<div class="col-lg-5">';
                echo '<div id="idCommTest'.$zgId.'" >';
                    echo '<a id="idCheckSP'.$zgId.'" class="btn btn-default" onclick="checkSerialPort('.$zgId.')" title="{{Test de lecture de la version du firmware}}"><i class="fas fa-sync"></i> {{Tester}}</a>';
                    echo '<a class="serialPortStatus'.$zgId.' ml4px" title="{{Status de communication avec la zigate. Voir \'AbeilleConfig.log\' si \'NOK\'.}}">';
                        echo '<span class="label label-success" style="font-size:1em">-?-</span>';
                    echo '</a>';
                    if (isset($dbgDeveloperMode))
                        echo '<a class="btn btn-danger ml4px" onclick="installTTY()" title="{{Tentative de libération du port qui pourrait être utilisé par la console}}"><i class="fas fa-sync"></i> {{Libérer}}</a>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Adresse IP (IP:Port)}} : </label>';
            echo '<div class="col-lg-4">';
                echo '<input id="idWifiAddr'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwIpAddr'.$zgId.'" placeholder="{{<adresse>:<port>}}" title="{{Adresse IP:Port si zigate Wifi. 9999 est le port par défaut d\'une Zigate WIFI. Mettre 23 si vous utilisez ESP-Link.}}" />';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Firmware}} : </label>';
            echo '<div class="col-lg-2">';
                // echo '<input id="idFwVersion'.$zgId.'" type="text" title="{{Version actuelle du firmware}}" disabled>';
                if (isset($eqId))
                    $fwVersion = getCmdValueByLogicId($eqId, "FW-Version");
                else
                    $fwVersion = "";
                echo '<input id="idFwVersion'.$zgId.'" value="'.$fwVersion.'" type="text" class="cmd" data-type="info" data-subtype="string" data-cmd_id="" title="{{Version actuelle du firmware}}" readonly="readonly">';
                // echo '<script>';
                // echo "jeedom.cmd.update['3408'] = function(_options) {";
                //     echo 'console.log("jeedom.cmd.update[3408], _options", _options);';
                //     echo 'var element = document.getElementById("idFwVersion'.$zgId.'");';
                //     echo 'console.log("element", element);';
                //     echo 'element.value = _options.display_value;';
                // echo '}';
                // echo '</script>';
            echo '</div>';
            echo '<div class="col-lg-2">';
                echo '<a id="idReadFwB'.$zgId.'" class="btn btn-default form-control" onclick="checkSerialPort('.$zgId.')" title="{{Lecture de la version du firmware}}"><i class="fas fa-sync"></i> {{Lire}}</a>';
            echo '</div>';
            echo '<div class="col-lg-5">';
                    echo '<div id="idUpdFw'.$zgId.'">';
                    echo '<a id="idupdateZigateFw'.$zgId.'" class="btn btn-danger" onclick="updateZigateFw('.$zgId.')" title="{{Programmation du FW selectionné}}"><i class="fas fa-sync"></i> {{Mettre-à-jour}}</a>';
                    echo '<select id="idFW'.$zgId.'" style="width:160px; margin-left:4px" title="{{Firmwares disponibles}}">';
                    foreach (ls(__DIR__.'/../resources/fw_zigate', '*.bin') as $fwName) {
                        $fwVers = substr($fwName, 0, -4); // Removing ".bin" suffix
                        if (substr($fwVers, -4) == "-dev") {
                            /* FW for developer mode only */
                            if (!isset($dbgDeveloperMode) || ($dbgDeveloperMode == FALSE))
                                continue; // Not in developer mode. Ignoring this FW
                            $fwVers = substr($fwVers, 0, -4); // Removing "-dev" suffix
                        }
                        $fwVers = substr($fwVers, 9); // Removing "zigatevX-" prefix
                        if ($fwVers == "0004-0323-opdm")
                            echo '<option value='.$fwName.' selected>'.$fwVers.'</option>'; // Selecting default choice
                        else
                            echo '<option value='.$fwName.'>'.$fwVers.'</option>';
                    }
                    if (isset($dbgDeveloperMode))
                        echo '<option value=CUSTOM>{{Autre}}</option>';
                    echo '</select>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Canal Zigbee}} : </label>';
            echo '<div class="col-lg-4">';
                echo '<select id="idSelChan'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwChan'.$zgId.'" title="{{Canal Zigbee}}" disabled>';
                    echo '<option value=0>auto</option>';
                    for ($i = 11; $i < 27; $i++) {
                        echo '<option value='.$i.'>'.$i.'</option>';
                    }
                    echo '</select>';
            echo '</div>';
        echo '</div>';

        echo '<div class="form-group">';
            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{Status : }}</label>';
            echo '<div class="col-lg-4">';
                echo '<select id="idSelGtwStatus'.$zgId.'" class="configKey form-control" data-l1key="ab::gtwEnabled'.$zgId.'" onchange="gtwStatusChanged('.$zgId.')" title="{{Activer ou désactiver l\'utilisation de cette zigate.}}">';
                    echo '<option value="N" selected>{{Désactivée}}</option>';
                    echo '<option value="Y">{{Activée}}</option>';
                echo '</select>';
            echo '</div>';
        echo '</div>';
    }
?>

<style>
    .confs1 {
        width: 300px;
        margin-right: 4px;
    }
    .ml4px {
        margin-left: 4px;
    }
</style>

<form class="form-horizontal">
    <fieldset>
        <legend><i class="fa fa-list-alt"></i><strong> {{Général}}</strong></legend>
        <div>
            <!-- <p><i> Suivez toutes les dernieres informations <a href="https://community.jeedom.com/t/news-de-la-ruche-par-abeille-plugin-abeille/26524"><strong>sur ce forum</strong> </a>.</i></p>
            <p><i> Les discussions et questions se feront via <a href="https://community.jeedom.com/tags/plugin-abeille"><strong>ce forum</strong> </a>. Pensez à mettre le flag "plugin-abeille" dans tous vos posts.</i></p> -->
            <!-- <p><i> Note: si vous souhaitez de l'aide, ne passez pas par les fonctions integrées de jeedom car je recois les requetes sans pouvoir contacter les personnes en retour. Passez par le forum.</i></p> -->
            <p><i> {{Note: Pour toute demande de support, passez par ce lien}}: https://github.com/KiwiHC16/Abeille/issues.</i></p>
            <p><i> {{Note: Ne pas activer la fonction 'Heartbeat' pour l'instant. Pas encore de support.}}</i></p>
        </div>
        <div class="form-group">
            <div class="col-lg-6">
                <div class="form-group">
                    <label class="col-lg-3 control-label" data-toggle="tooltip" title="{{Version interne du plugin Abeille}}">{{Version interne}} : </label>
                    <div class="col-lg-4">
                        <?php
                        echo '<input type="text" class="form-control" title="{{Version interne du plugin Abeille}}" value="'.$abeilleVersion.'" readonly>';
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-group">
                <!-- <div class="col-lg-5"> -->
                    <?php
                        /* Developers only: display current branch & allows to switch to another one */
                        if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == true)) {
                            echo '<label class="col-lg-3 control-label" data-toggle="tooltip">{{GIT : }}</label>';
                            /* TODO: Check if GIT repo & GIT present */
                            if (gitIsRepo() == FALSE) {
                                echo "Pas sous GIT";
                            } else {
                                $localChanges = gitHasLocalChanges();
                                if ($localChanges == true)
                                    echo '<div title="Branche courante. Contient des MODIFICATIONS LOCALES !!" class="label label-danger" style="font-size:1em">';
                                else
                                    echo '<div title="Branche courante" class="label label-success" style="font-size:1em">';
                                $localBranch = gitGetCurrentBranch();
                                echo '<script>var js_curBranch = "'.$localBranch.'";</script>'; // PHP to JS
                                echo $localBranch;
                                echo '</div>';
                                /* List branches */
                                gitFetchAll(1);
                                $Branches = gitGetAllBranches();
                                echo '<select id="idBranch" class="ml4px" style="width:140px" title="{{Branches dispos}}">';
                                foreach ($Branches as $b) {
                                    if ($b == '')
                                        continue;
                                    $b = substr($b, 2);
                                    if (substr($b, 0, 8) == "remotes/")
                                        $b2 = substr($b, 8); // Remove 'remotes/'
                                    else
                                        $b2 = $b;
                                    echo '<script>console.log("branch='.$b.'")</script>';
                                    if ($b == $localBranch)
                                        echo '<option value="'.$b.'" selected>'.$b2.'</option>';
                                    else
                                        echo '<option value="'.$b.'">'.$b2.'</option>';
                                }
                                echo '</select>';
                                echo '<a id="idSwitchBranch" class="btn btn-warning ml4px" title="{{Suppression des modifs locales, basculement sur la branche selectionnée, et redémarrage.}}">{{Mettre-à-jour}}</a>';
                            }
                        }
                    ?>
                <!-- </div> -->
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-6">
                <div class="form-group">
                    <label class="col-lg-3 control-label" data-toggle="tooltip" >{{Objet parent}} : </label>
                    <div class="col-lg-4" title="{{Objet parent (ex: une pièce de la maison) par défaut pour toute nouvelle zigate. Peut être changé plus tard via la page de gestion d'Abeille en cliquant sur la ruche correspondante}}.">
                        <select class="configKey form-control" data-l1key="ab::defaultParent">
                            <?php
                                foreach (jeeObject::all() as $object) {
                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-lg-5">
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
            </div>
        </div>

        <legend>
            <i class="fa fa-list-alt"></i><strong> {{Passerelles}}</strong>
            <?php
            echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/PageConfig.html"><i class="fas fa-book"></i>{{Documentation}}</a>';
            ?>
        </legend>
        <div id="idZigates">
            <!-- Display dependancies -->
            <div class="form-group">
                <div class="col-lg-3">
                    <h4>{{Dépendances optionnelles}}</h4>
                </div>
                <div class="col-lg-9">
                </div>
            </div>

            <div class="form-group">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-3 control-label" data-toggle="tooltip" title="Wiring PI est nécéssaire pour les Zigates de type PI">{{Wiring Pi}} : </label>
                        <div id="idWiringPi" class="col-lg-4">
                            <input id="idWiringPiStatus" type="text" class="form-control" title="{{Status d'installation du package Wiring PI}}" readonly>
                            <!-- <a class="WiringPiStatus" title="">
                                <span class="label label-success" style="font-size:1em;">-?-</span>
                            </a> -->
                        </div>
                        <div class="col-lg-5">
                            <a class="btn btn-default" id="bt_installWiringPi" title="{{Installation du package}}"><i class="fas fa-sync"></i> {{Installer}}</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-3 control-label" data-toggle="tooltip" title="Socat est nécéssaire pour les Zigates de type Wifi/Eth">{{Socat}} : </label>
                        <div class="col-lg-4">
                            <input id="idSocatStatus" type="text" class="form-control" title="{{Status d'installation du package socat}}" readonly>
                        </div>
                        <!-- <div id="idSocat'.$zgId.'" class="col-lg-6">
                            <a class="socatStatus" title="Status d'installation du package socat">
                                <span class="label label-success" style="font-size:1em;">-?-</span>
                            </a>
                            </div> -->
                        <div class="col-lg-5">
                            <a class="btn btn-default" id="bt_installSocat" title="{{Installation du package}}"><i class="fas fa-sync"></i> {{Installer}}</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-3 control-label" data-toggle="tooltip" title="PiGpio est nécéssaire pour les Zigates de type PI">{{PiGpio}} : </label>
                        <div id="idPiGpio" class="col-lg-4">
                            <input id="idPiGpioStatus" type="text" class="form-control" title="{{Status d'installation du package PiGpio}}" readonly>
                            <!-- <a class="PiGpioStatus" title="">
                                <span class="label label-success" style="font-size:1em;">-?-</span>
                            </a> -->
                        </div>
                        <div class="col-lg-5">
                            <a class="btn btn-default" id="bt_installPiGpio" title="{{Installation du package}}"><i class="fas fa-sync"></i> {{Installer}}</a>
                        </div>
                    </div>
                </div>

                <!-- <div class="form-group"> -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label" data-toggle="tooltip" >{{Lib GPIO à utiliser}} : </label>
                            <div class="col-lg-4" title="{{Librairie à utiliser pour le controle des GPIO pour la PiZigate.}}">
                                <select id="idGpioLib" class="configKey form-control" data-l1key="ab::defaultGpioLib">
                                    <option value="PiGpio">PiGpio</option>
                                    <option value="WiringPi">WiringPi</option>
                                </select>
                            </div>
                            <div class="col-lg-5">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                    </div>
                <!-- </div> -->
            </div>

            <!-- Display zigates 2 per line -->
            <?php
            // How many zigates to display ? (check if enabled or not)
            $maxId = 1;
            for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
                $status = config::byKey('ab::gtwEnabled'.$gtwId, 'Abeille', 'N');
                if ($status == "Y")
                    $maxId = $gtwId;
            }
            for ($gtwId = 1, $zgG = 1; $gtwId <= maxGateways; $gtwId++, $zgG++) {
                if ($gtwId > $maxId)
                    echo '<div id="idGtwGroup'.$zgG.'" class="form-group" style="display:none">';
                else {
                    echo '<div id="idGtwGroup'.$zgG.'" class="form-group">';
                    $lastDisplayedG = $zgG;
                }
                    echo '<div class="col-lg-6">';
                        displayGateway($gtwId);
                    echo '</div>';
                    $gtwId++;
                    if ($gtwId <= maxGateways) {
                        echo '<div class="col-lg-6">';
                            displayGateway($gtwId);
                        echo '</div>';
                    }
                echo '</div>';
            }
            echo '<script>lastDisplayedG = '.$lastDisplayedG.'</script>';
            echo '<script>maxGateways = '.maxGateways.'</script>';
            echo '<a id="idshowMoreGatewaysB" class="btn btn-success" onclick="showMoreGateways()">{{Plus de passerelles}}</a>';
            echo '<a id="idshowLessGatewaysB" class="btn btn-success" onclick="showLessGateways()" style="display:none;margin-left:8px">{{Moins de passerelles}}</a>';
        ?>
        </div>

        <!-- Tcharp38: Not reliable enough to bring useful info to end user (ex: it is always ok for me while it tells me not enough space for upgrade).
        To be revisited. Moreover, better to rely on integrity to know if potential space issues. -->
        <!-- <legend>
            <i class="fa fa-list-alt"></i> {{Mise à jour (Vérification)}}
            <a id="idUpdateCheckShowHide" class="btn btn-success" >{{Afficher}}</a>
        </legend>
        <div id="UpdateCheck">
            < ?php
                Abeille_pre_update_analysis(0, 1);
            ? >
        </div> -->

        <legend>
            <i class="fa fa-list-alt"></i><strong> {{Options avancées}}</strong>
            <a id="idAdvOptionsShowHide" class="btn btn-success" >{{Afficher}}</a>
        </legend>
        <div id="idAdvOptions">
            <div>
                <p><i>{{Attention ! Ne touchez des éléments de cette section que si vous savez parfaitement de quoi il en retourne.}}</i></p>
            </div>

            <?php if (validMd5Exists()) { ?>
            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Vérifie l'integrité du plugin}}">{{Test d'intégrité}} : </label>
                <div class="col-lg-5">
                    <input type="button" onclick="checkIntegrity()" value="{{Lancer}}" title="Lance le test d'intégrité">
                    <a class="integrityStatus ml4px" title="{{Status d'intégrité d'Abeille. Voir 'AbeilleConfig.log' si 'NOK'.}}">
                        <span class="label label-success" style="font-size:1em">-?-</span>
                    </a>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Supprime les fichiers locaux inutiles si test d'intégrité OK}}">{{Nettoyage des fichiers non requis : }}</label>
                <div class="col-lg-5">
                    <input id="idCleanUpB" type="button" onclick="cleanUp()" value="{{Lancer}}" " title="Lance le nettoyage si le test d'intégrité est OK" disabled>
                </div>
            </div>
            <?php } ?>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Blocage interrogation LQI à minuit}}">{{Blocage interrogation LQI à minuit}} : </label>
                <div class="col-lg-5">
                    <select class="configKey form-control" data-l1key="ab::preventLQIAutoUpdate" style="width:150px" data-toggle="tooltip" title="{{Si 'oui', empêche l'interrogation du réseau tout les jours à minuit}}">
                        <option value="yes">{{Oui}}</option>
                        <option value="no" selected>{{Non}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Empêche un cycle off/on sur les Zigates USB plantées}}">{{Blocage cycle OFF/ON Zigates USB}} : </label>
                <div class="col-lg-5">
                    <select class="configKey form-control" data-l1key="ab::preventUsbPowerCycle" style="width:150px" data-toggle="tooltip" title="{{Si 'Oui', empêche un cycle OFF/ON sur les Zigates USB plantées}}">
                        <option value="Y">{{Oui}}</option>
                        <option value="N" selected>{{Non}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Force le mode 'hybride' pour les Zigates}}">{{Zigates en mode 'hybride'}} : </label>
                <div class="col-lg-5">
                    <select class="configKey form-control" data-l1key="ab::forceZigateHybridMode" style="width:150px" data-toggle="tooltip" title="{{Si 'Oui', les Zigates fonctionnent en mode 'hybride' au lieu de 'brut'}}">
                        <option value="Y">{{Oui}}</option>
                        <option value="N" selected>{{Non}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Mode dédié aux développeurs}}">{{Mode developpeur}} : </label>
                <div class="col-lg-5">
                    <?php
                        if (file_exists(dbgFile))
                            echo '<input type="button" onclick="xableDevMode(0)" value="{{Désactiver}}" title="{{Désactive le mode développeur}}">';
                        else
                            echo '<input type="button" onclick="xableDevMode(1)" value="{{Activer}}" title="{{Active le mode développeur}}">';
                    ?>
                </div>
            </div>

		    <!-- Following functionalities are visible only if 'tmp/debug.json' file exists (developer mode). -->
            <?php if (isset($dbgConfig)) { ?>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Liste des messages désactivés dans 'AbeilleParser.log'}}">{{Parser; Messages désactivés}} : </label>
                    <div class="col-lg-5">
                    <?php if (isset($dbgConfig['dbgParserLog'])) {
                        $dbgParserLog = implode(" ", $dbgConfig['dbgParserLog']);
                        echo '<input type="text" id="idParserLog" title="AbeilleParser messages type to disable (ex: 8000)" style="width:400px" value="'.$dbgParserLog.'">';
                    } else
                        echo '<input type="text" id="idParserLog" title="AbeilleParser messages type to disable (ex: 8000)" style="width:400px">';
                    ?>
                    <input type="button" onclick="saveChanges()" value="{{Sauver}}" style="margin-left:8px">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Autres defines de dev/debug}}">{{Defines}} : </label>
                    <div class="col-lg-5">
                    <?php if (isset($dbgConfig['defines'])) {
                        $dbgDefines = implode(" ", $dbgConfig['defines']);
                        echo '<input type="text" id="idDefines" title="Defines (ex: Tcharp38)" style="width:400px" value="'.$dbgDefines.'">';
                    } else
                        echo '<input type="text" id="idDefines" title="Defines (ex: Tcharp38)" style="width:400px">';
                    ?>
                    <input type="button" onclick="saveChanges()" value="{{Sauver}}" style="margin-left:8px">
                    </div>
                </div>
            <?php } ?>
        </div>
    </fieldset>
</form>

<script>
    /* Show only groups in line with number of zigates (1 most of the time) */
    // for (z = 1; z <= js_NbOfZigates; z++) {
    //     console.log("Showing idGtwGroup"+z);
    //     $("#idGtwGroup"+z).show();
    // }

    // $("#idZigates").hide();
    // $("#zigatewifi").hide();
    // $("#PiZigate").hide();
    // $("#UpdateCheck").hide();
    $("#idAdvOptions").hide();

    // $('#idZigatesShowHide').on('click', function () {
    //     var Label = document.getElementById("idZigatesShowHide").innerText;
    //     console.log("ZigatesShowHide click: Label=" + Label);
    //     if (Label == "{{Afficher}}") {
    //         document.getElementById("idZigatesShowHide").innerText = "{{Cacher}}";
    //         document.getElementById("idZigatesShowHide").className = "btn btn-danger";
    //         $("#idZigates").show();
    //     } else {
    //         document.getElementById("idZigatesShowHide").innerText = "{{Afficher}}";
    //         document.getElementById("idZigatesShowHide").className = "btn btn-success";
    //         $("#idZigates").hide();
    //     }
    // });

    function showMoreGateways() {
        console.log("showMoreGateways(), lastDisplayedG="+lastDisplayedG);
        lastDisplayedG++;
        $("#idGtwGroup"+lastDisplayedG).show();
        if ((lastDisplayedG * 2) >= maxGateways)
            $("#idshowMoreGatewaysB").hide();
        $("#idshowLessGatewaysB").show();
    }

    function showLessGateways() {
        console.log("showLessGateways(), lastDisplayedG="+lastDisplayedG);
        $("#idGtwGroup"+lastDisplayedG).hide();
        lastDisplayedG--;
        $("#idshowMoreGatewaysB").show();
        if (lastDisplayedG == 1) {
            $("#idshowLessGatewaysB").hide();
        }
    }

    /* Number of zigates changed. Display more or less 'idGtwGroup'
       and be sure any new zigate is disabled by default */
    // $("#nbOfZigates").change(function() {
    //     var nbOfZigates = Number($("#nbOfZigates").val());
    //     console.log("Nb Zigates: now=" + nbOfZigates + ", prev=" + js_NbOfZigates);
    //     if (nbOfZigates > js_NbOfZigates) {
    //         for (z = js_NbOfZigates + 1; z <= nbOfZigates; z++) {
    //             $("#idSelGtwStatus" + z).val('N'); // Disabled
    //             $("#idSelGtwStatus" + z).change();
    //             $("#idGtwGroup" + z).show();
    //         }
    //     } else if (nbOfZigates < js_NbOfZigates) {
    //         for (z = nbOfZigates + 1; z <= js_NbOfZigates; z++) {
    //             $("#idSelGtwStatus" + z).val('N'); // To be saved as disabled
    //             $("#idGtwGroup" + z).hide();
    //         }
    //     }
    //     js_NbOfZigates = nbOfZigates;
    // });

    /* Called when gateway type (zigate, ezsp) is changed */
    function gtwTypeChanged(gtwId) {
        let gtwType = $("#idSelGtwType" + gtwId).val();
        console.log("gtwTypeChanged(gtwId="+gtwId+") => type="+gtwType);

        let zigateSubType = document.getElementById("idZigateSubType"+gtwId);
        let ezspSubType = document.getElementById("idEzspSubType"+gtwId);
        if (gtwType == 'zigate') {
            $("#idZigateSubType"+gtwId).show();
            zigateSubType.classList.add("configKey");

            $("#idEzspSubType"+gtwId).hide();
            ezspSubType.classList.remove("configKey");
        } else {
            $("#idEzspSubType"+gtwId).show();
            ezspSubType.classList.add("configKey");

            $("#idZigateSubType"+gtwId).hide();
            zigateSubType.classList.remove("configKey");
        }
    }

    /* Called when gateway sub type (USB, WIFI, PI, DIN) is changed */
    function gtwSubTypeChanged(gtwId) {
        console.log("gtwSubTypeChanged(gtwId="+gtwId+")");
        let gtwType = $("#idSelGtwType" + gtwId).val();

        if (gtwType == "zigate") {
            var gtwSubType = $("#idZigateSubType" + gtwId).val();
            console.log('Gtw '+gtwId+' ('+gtwType+') sub type changed to "'+gtwSubType+'"');

            var idSelSP = document.querySelector('#idSelSP'+gtwId);
            // var idCheckSP = document.querySelector('#idCheckSP' + gtwId);
            var idFW = document.querySelector('#idFW' + gtwId);
            // var idupdateZigateFw = document.querySelector('#idupdateZigateFw' + gtwId);
            var idWifiAddr = document.querySelector('#idWifiAddr'+gtwId);
            // var idCheckWifi = document.querySelector('#idCheckWifi' + gtwId);

            // Default: Type USBv1 => SelSP allowed, WifiAddr disallowed
            idSelSP.removeAttribute('disabled'); // Serial port selection allowed by default
            idWifiAddr.setAttribute('disabled', true); // Wifi addr disallowed by default
            $("#idUpdFw"+gtwId).hide(); // Update FW disallowed by default

            if (gtwSubType == "WIFI") {
                $("#idSelSP"+gtwId).val(js_wifiLink+gtwId);
                checkSocatInstallation();
            } else { // USB, PI or DIN
                // $("#idCommTest"+gtwId).show();

                if ((gtwSubType == "PI") || (gtwSubType == "PIv2")) {
                    // $("#idWiringPi"+gtwId).show();
                    let zgGpioLib = $("#idGpioLib").val();
                    if (zgGpioLib == "WiringPi")
                        checkWiringPi(); // Force WiringPi check
                    else
                        checkPiGpio();
                }
                //     $("#idUpdFw"+gtwId).show();
                // } else if (gtwSubType == "DIN") {
                //     $("#idUpdFw"+gtwId).show();
                // }
            }

            allowSelSP = true; // Serial port selection ALLOWED by default
            allowWifiAddr = false; // Wifi addr disallowed by default
            allowReadFwV = false; // Read FW version
            allowUpdFw = false; // Update FW disallowed by default
            switch (gtwSubType) {
            case "USB":
                allowReadFwV = true;
                break;
            case "USBv2":
                allowReadFwV = true;
                break;
            case "PI":
                allowReadFwV = true;
                allowUpdFw = true;
                break;
            case "PIv2":
                allowReadFwV = true;
                allowUpdFw = true;
                break;
            case "WIFI":
                allowSelSP = false;
                allowWifiAddr = true;
                break;
            case "DIN":
                allowReadFwV = true;
                allowUpdFw = true;
                break;
            case "DINv2":
                allowReadFwV = true;
                break;
            }
            if (allowSelSP == false)
                idSelSP.setAttribute('disabled', true);
            if (allowWifiAddr)
                idWifiAddr.removeAttribute('disabled');
            if (allowReadFwV) {
                $("#idReadFwB"+gtwId).show();
                $("#idCommTest"+gtwId).show();
            } else {
                $("#idReadFwB"+gtwId).hide();
                $("#idCommTest"+gtwId).hide();
            }
            if (allowUpdFw)
                $("#idUpdFw"+gtwId).show();
        } else {
            var gtwSubType = $("#idEzspSubType" + gtwId).val();
            console.log('Gtw '+gtwId+' ('+gtwType+') sub type changed to "'+gtwSubType+'"');


        }
    }

    /* Called when 'IP:port' test button is pressed (Wifi case) */
    // function checkWifi(zgId) {
        // console.log("checkWifi(zgId=" + zgId + ")");
        // /* Note. Onclick seems still active even if button is disabled (wifi case) */
        // var idCheckWifi = document.querySelector('#idCheckWifi' + zgId);
        // if (idCheckWifi.getAttribute('disabled') != null) {
            // console.log("=> Action ignored (diabled).");
            // return;
        // }
        // var wifiAddr = document.getElementById("idWifiAddr" + zgId).value;
        // if (wifiAddr == "") {
            // alert("Merci d'entrer une adresse valide au format <addr>:<port>.\nEx: 192.168.1.12:9999");
            // return;
        // }
        // var ssp = "/tmp/zigateWifi" + zgId; // Socat Serial Port
        // console.log("wifiAddr=" + wifiAddr + ", ssp=" + ssp);
        // $.ajax({
            // type: 'POST',
            // url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            // data: {
                // action: 'checkWifi',
                // zgport: wifiAddr,
                // ssp: ssp,
            // },
            // dataType: 'json',
            // global: false,
            // error: function (request, status, error) {
                // bootbox.alert("ERREUR 'checkWifi' !<br>Votre installation semble corrompue.");
            // },
            // success: function (json_res) {
                // $res = JSON.parse(json_res.result);
                // if ($res.status == 0) {
                    // $fw = $res.fw;
                    // $('.wifiStatus' + zgId).empty().append('<span class="label label-success" style="font-size:1em;">OK, FW ' + $fw + '</span>');
                    // $('.wifiStatus' + zgId).empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                // } else {
                    // $('.wifiStatus' + zgId).empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                // }
            // }
        // });
    // }

    // $('#idWifiShowHide').on('click', function () {
    //         var Label = document.getElementById("idWifiShowHide").innerText;
    //         console.log("WifiShowHide click: Label=" + Label);
    //         if (Label == "{{Afficher}}") {
    //             checkSocatInstallation();
    //             document.getElementById("idWifiShowHide").innerText = "{{Cacher}}";
    //             document.getElementById("idWifiShowHide").className = "btn btn-danger";
    //             $("#zigatewifi").show();
    //         } else {
    //             document.getElementById("idWifiShowHide").innerText = "{{Afficher}}";
    //             document.getElementById("idWifiShowHide").className = "btn btn-success";
    //             $("#zigatewifi").hide();
    //         }
    //     }
    // );

    function checkSocatInstallation() {
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'checkSocat',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkSocat' !<br>Votre installation semble corrompue.");
            },
            success: function (res) {
                if (res.result == 0) {
                    // $('.socatStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                    document.getElementById("idSocatStatus").value = "OK";
                } else {
                    // $('.socatStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                    document.getElementById("idSocatStatus").value = "NOK";
                }
            }
        });
    }

    $('#idUpdateCheckShowHide').on('click', function () {
        var Label = document.getElementById("idUpdateCheckShowHide").innerText;
        console.log("idUpdateCheckShowHide click: Label=" + Label);
        if (Label == "{{Afficher}}") {
            document.getElementById("idUpdateCheckShowHide").innerText = "{{Cacher}}";
            document.getElementById("idUpdateCheckShowHide").className = "btn btn-danger";
            $("#UpdateCheck").show();
        } else {
            document.getElementById("idUpdateCheckShowHide").innerText = "{{Afficher}}";
            document.getElementById("idUpdateCheckShowHide").className = "btn btn-success";
            $("#UpdateCheck").hide();
        }
    });

    $('#idAdvOptionsShowHide').on('click', function () {
        var Label = document.getElementById("idAdvOptionsShowHide").innerText;
        console.log("idAdvOptionsShowHide click: Label=" + Label);
        if (Label == "{{Afficher}}") {
            document.getElementById("idAdvOptionsShowHide").innerText = "{{Cacher}}";
            document.getElementById("idAdvOptionsShowHide").className = "btn btn-danger";
            $("#idAdvOptions").show();
        } else {
            document.getElementById("idAdvOptionsShowHide").innerText = "{{Afficher}}";
            document.getElementById("idAdvOptionsShowHide").className = "btn btn-success";
            $("#idAdvOptions").hide();
        }
    });

    function checkWiringPi() {
        if (window.checkWiringPiOngoing)
            return;
        window.checkWiringPiOngoing = true;

        console.log("checkWiringPi()");
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'checkWiringPi',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkWiringPi' !<br>Votre installation semble corrompue.");
                window.checkWiringPiOngoing = false;
            },
            success: function (res) {
                if (res.result == 0) {
                    // $('.WiringPiStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                    document.getElementById("idWiringPiStatus").value = "OK";
                } else {
                    // $('.WiringPiStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                    document.getElementById("idWiringPiStatus").value = "NOK";
                }
                window.checkWiringPiOngoing = false;
            }
        });
    }

    function checkPiGpio() {
        if (window.checkPiGpioOngoing)
            return;
        window.checkPiGpioOngoing = true;

        console.log("checkPiGpio()");
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'checkPiGpio',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkPiGpio' !<br>Votre installation semble corrompue.");
                window.checkPiGpioOngoing = false;
            },
            success: function (res) {
                if (res.result == 0) {
                    // $('.WiringPiStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                    document.getElementById("idPiGpioStatus").value = "OK";
                } else {
                    // $('.WiringPiStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                    document.getElementById("idPiGpioStatus").value = "NOK";
                }
                window.checkPiGpioOngoing = false;
            }
        });
    }

    $('#bt_installWiringPi').on('click', function() {
        msg = "{{Vous êtes sur le point d installer le package 'WiringPi' pour le contrôle d'une PiZigate}}<br>";
        msg += "{{Cela peut provoquer des conflits avec d'autres gestionnaires de GPIO}}<br><br>";
        msg += "{{Voulez vous continuer ?}}";
        bootbox.confirm(msg, function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Installation de WiringPi}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=AbeilleInstallPackage.modal&package=WiringPi').dialog('open');
            }
        });
    })

    $('#bt_installPiGpio').on('click', function() {
        msg = "{{Vous êtes sur le point d installer le package 'PiGpio' pour le contrôle d'une PiZigate}}<br>";
        msg += "{{Cela peut provoquer des conflits avec d'autres gestionnaires de GPIO}}<br><br>";
        msg += "{{Voulez vous continuer ?}}";
        bootbox.confirm(msg, function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Installation de PiGpio}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=AbeilleInstallPackage.modal&package=PiGpio').dialog('open');
            }
        });
    })

    $('#bt_installSocat').on('click',function(){
        msg = "{{Vous êtes sur le point d installer le package 'socat' pour le contrôle d'une Zigate WIFI}}<br><br>";
        msg += "{{Voulez vous continuer ?}}";
        bootbox.confirm(msg, function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Installation de socat}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=AbeilleInstallPackage.modal&package=socat').dialog('open');
            }
        });
    })

    // $('#bt_checkTTY').on('click', function() {
    //     let gtwType = $("#idSelGtwType" + gtwId).val();
    //     if (gtwType == 'zigate')
    //         gtwSubType = $("#idZigateSubType" + gtwId).val();
    //     else
    //         gtwSubType = $("#idEzspSubType" + gtwId).val();

    //     $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
    //         data: {
    //             action: 'checkTTY',
    //             zgport: document.getElementById("ZiGatePort").value,
    //             zgtype: $("#idSelZgType" + zgId).val(),
    //         },
    //         dataType: 'json',
    //         global: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'checkTTY' !<br>Votre installation semble corrompue.");
    //         },
    //         success: function (json_res) {
    //             $res = JSON.parse(json_res.result);
    //             if ($res.status == 0) {
    //                 $('.TTYStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
    //                 $fw = $res.fw;
    //                 $label = '<span class="label label-success" style="font-size:1em;">' + $fw + '</span>';
    //                 $('.CurrentFirmware').empty().append($label);
    //             } else {
    //                 $('.TTYStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
    //                 $('.CurrentFirmware').empty().append('<span class="label label-danger" style="font-size:1em;">?</span>');
    //             }
    //         }
    //     });
    // });
    // $('#bt_readFW').on('click', function() {
    //     /* Click on 'read FW' as same effect than 'check TTY' */
    //     $('#bt_checkTTY').click();
    // });

    /* Called when serial port test button is pressed */
    function checkSerialPort(gtwId) {
        console.log("checkSerialPort(gtwId=" + gtwId + ")");
        /* Note. Onclick seems still active even if button is disabled (wifi case) */
        // var idCheckSP = document.querySelector('#idCheckSP'+zgId);
        // if (idCheckSP.getAttribute('disabled') != null) {
        //     console.log("=> DISABLED");
        //     return;
        // }
        let gtwType = $("#idSelGtwType" + gtwId).val();
        if (gtwType == 'zigate')
            gtwSubType = $("#idZigateSubType" + gtwId).val();
        else
            gtwSubType = $("#idEzspSubType" + gtwId).val();

        $.showLoading()
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'checkTTY',
                zgport: $("#idSelSP" + gtwId).val(),
                zgtype: gtwSubType,
                zgGpioLib: $("#idGpioLib").val(),
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                $.hideLoading()
                bootbox.alert("ERREUR 'checkSerialPort' !<br>Votre installation semble corrompue.");
            },
            success: function (json_res) {
                $.hideLoading()
                res = JSON.parse(json_res.result);
                if (res.status == 0) {
                    fw = res.fw;
                    console.log("FW="+fw)
                    $('.serialPortStatus' + gtwId).empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                    // $('#idFwVersion'+gtwId).empty().append('<span class="label label-success" style="font-size:1em;">'+fw+'</span>');
                    document.getElementById("idFwVersion"+gtwId).value = fw;
                } else {
                    $('.serialPortStatus' + gtwId).empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                }
            }
        });
    }

    /* Called when FW update button is pressed */
    function updateZigateFw(gtwId) {
        console.log("updateZigateFw(gtwId="+gtwId+")");
        /* Note. Onclick seems still active even if button is disabled (wifi case) */
        // var idCheckSP = document.querySelector('#idCheckSP' + zgId);
        // if (idCheckSP.getAttribute('disabled') != null) {
        //     console.log("=> DISABLED");
        //     return;
        // }
        let zgType = $("#idZigateSubType"+gtwId).val();
        if ((zgType != "PI") && (zgType != "PIv2") &&(zgType != "DIN")) {
            console.log("=> Neither PI nor DIN type. UNEXPECTED !");
            return;
        }

        let zgFW = $("#idFW"+gtwId).val();
        if (zgFW == "CUSTOM") {
            uploadCustomFw().then(response => updateZigateFw2(gtwId, zgType, "/tmp/jeedom/Abeille/" + response), error => console.log("uploadCustomFw() ERROR", error));
        } else
            updateZigateFw2(gtwId, zgType, zgFW);
    }

    // Update FW
    function updateZigateFw2(zgId, zgType, zgFW) {
        let zgPort = $("#idSelSP"+zgId).val();
        let zgGpioLib = $("#idGpioLib").val();
        let curFw = document.getElementById("idFwVersion"+zgId).value;
        console.log("updateZigateFw2("+zgId+", "+zgType+", "+zgFW+"): curFw="+curFw);

        msg = '{{Vous êtes sur le point de mettre à jour le firmware de la Zigate}}';
        msg += '<br> - {{Type}}    : '+zgType;
        msg += '<br> - {{Port}}    : '+zgPort;
        msg += '<br> - {{Lib GPIO}}: '+zgGpioLib;
        msg += '<br> - {{Ancien firmware}}: '+curFw;
        msg += '<br> - {{Nouveau firmware}}: '+zgFW+'<br><br>';
        erasePdm = false;

        // Current FW type ?
        let curIsLegacy = false; // Assuming OPDM
        if (curFw != '') {
            // Format XXXX-YYYY: where
            // Offical FW => XXXX=0003 (Zigate v1, legacy), 0004 (Zigate v1, OPDM), ou 0005 (Zigate v2, OPDM)
            // Abeille FW => AB01=Zigate v1, OPDM
            major = curFw.substr(0, 4);
            console.log("major=", major);
            if (major == '0003')
                curIsLegacy = true;
        }

        // New FW type ?
        let newIsOpdm = false;
        if ((zgFW.indexOf("opdm") != -1) || (zgFW.indexOf("AB01-") != -1))
            newIsOpdm = true;

        if (curIsLegacy) {
            if (newIsOpdm) {
                msg += "{{Vous allez passer d'une version 'legacy' à 'OPDM'. La table des équipements connus de la Zigate doit être éffacée et vous allez devoir faire une réassocitaion complète.}}<br><br>";
                msg += '{{Etes vous sur de vouloir continuer ?}}';
                erasePdm = true;
            } else
                msg += '{{Attention !! La version Optimized PDM est FORTEMENT recommandée}}.<br><br>';
        } else {
            if (newIsOpdm == false) {
                msg += "{{Passer d'une version 'OPDM' vers 'legacy' n'est pas recommandé. Opération interdite.}}<br><br>";
                bootbox.confirm(msg, function (result) {});
                return;
            }
        }
        bootbox.confirm(msg, function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Mise-à-jour du FW de la Zigate}}"});
                url = 'index.php?v=d&plugin=Abeille&modal=AbeilleConfigPage.modal&cmd=updateZigateFw&zgtype=\"'+zgType+'\"&zgport=\"'+zgPort+'\"&zgGpioLib=\"'+zgGpioLib+'\"&fwfile=\"'+zgFW+'\"';
                if (erasePdm)
                    url += '&erasePdm=true&zgId='+zgId;
                $('#md_modal2').load(url).dialog('open');
            }
        });
    }

    // Select & upload custom firmware
    function uploadCustomFw() {
        console.log("uploadCustomFw()");

        return new Promise((resolve, reject) => {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = '.bin';
            // TODO: How to detect file selector is closed ?
            input.onchange = e => {

                var file = e.target.files[0];
                // file.name = the file's name including extension
                // file.size = the size in bytes
                // file.type = file type ex. 'application/pdf'
                console.log("file=", file);

                var formData = new FormData();
                formData.append("file", file);
                formData.append("destDir", "/tmp/jeedom/Abeille"); // Temp (non persistent) directory
                // formData.append("destName", "Level0.png");

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "plugins/Abeille/core/php/AbeilleUpload.php", true);
                xhr.onload = function (oEvent) {
                    console.log("oEvent=", oEvent);
                    if (xhr.status != 200) {
                        console.log("Error " + xhr.status + " occurred when trying to upload your file.");
                        reject();
                    } else {
                        console.log(file.name + " uploaded !");
                        resolve(file.name);
                    }
                };
                xhr.send(formData);
            }
            input.click();
        });
    }

    function installTTY() {
        var msg = '{{Vous êtes sur le point de tenter de libérer le port TTY.';
        msg += '<br>Cette procédure pourrait supprimer la console si attachée à ce port.'
        msg += '<br><br>Voulez vous continuer ?}}'
        bootbox.confirm(msg, function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Libération TTY}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=installTTY.abeille').dialog('open');
            }
        });
    }

    function gtwStatusChanged(gtwId) {
        let enabled = $("#idSelGtwStatus"+gtwId).val();
        console.log("gtwStatusChanged("+gtwId+"), enabled="+enabled)
        // document.getElementById('optionID').style.color = '#000';
        // How to change color to highlight it is DISABLED ?
    }

    /* Verify Abeille's integrity using 'Abeille.md5' file. */
    function checkIntegrity() {
        console.log("checkIntegrity()");

        $.showLoading()
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'checkIntegrity'
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                $.hideLoading()
                bootbox.alert("ERREUR 'checkIntegrity' !<br>Votre installation semble corrompue.<br>"+error);
            },
            success: function (json_res) {
                $res = JSON.parse(json_res.result);
                if ($res.status == true) {
                    $('.integrityStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                    var cleanUpB = document.querySelector('#idCleanUpB');
                    cleanUpB.removeAttribute('disabled');
                } else {
                    $('.integrityStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                }
                $.hideLoading()
            }
        });
    }

    /* Developers mode only */

    /* Called when 'developer mode' must be enabled or disabled.
    This means creating or deleting "tmp/debug.json" file. */
    function xableDevMode(enable) {
        console.log("xableDevMode(enable="+enable+")");
        if (enable == 1) {
            /* Enable developer mode by creating debug.json then restart */
            var devAction = 'writeDevConfig';
        } else {
            var devAction = 'deleteDevConfig';
        }

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
            data: {
                action: devAction,
                devConfig: ''
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR '"+devAction+"' !<br>status="+status+"<br>error="+error);
            },
            success: function (json_res) {
                console.log(json_res);
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                    alert(msg);
                } else
                    window.location.reload();
            }
        });
    }

    if ((typeof js_dbgDeveloperMode != 'undefined')) {
        console.log("Developer mode");

        /* Branch select changed. Updating button label. */
        $("#idBranch").change(function() {
            var branchName = $("#idBranch").val();
            console.log("Selected branch = '" + branchName + "', current = '" + js_curBranch + "'")
            if (branchName == js_curBranch) {
                document.getElementById("idSwitchBranch").innerText = "Mettre-à-jour";
            } else {
                document.getElementById("idSwitchBranch").innerText = "Basculer";
            }
        });

        $('#idSwitchBranch').on('click', function() {
            var branchName = $("#idBranch").val();
            console.log("switchBranch(branch="+branchName+", current="+js_curBranch+")")
            if (branchName == js_curBranch)
                var updateOnly = "update"
            else
                var updateOnly = ""
            $.ajax({
                type: 'POST',
                url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
                data: {
                    action: 'switchBranch',
                    branch: branchName,
                    updateOnly: updateOnly
                },
                dataType: 'json',
                global: false,
                error: function (request, status, error) {
                    bootbox.alert("ERREUR 'switchBranch' !<br>Votre installation semble corrompue.");
                },
                success: function (json_res) {
                }
            });

            /* Returns: 1=completed, else 0 */
            // function checkCompletion() {
            //     console.log("checkCompletion()");
            //     $.ajax({
            //         type: 'POST',
            //         url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            //         data: {
            //             action: 'fileExists',
            //             path: 'tmp/switchBranch.done'
            //         },
            //         dataType: 'json',
            //         global: false,
            //         error: function (request, status, error) {
            //             console.log("checkCompletion() => ERROR");
            //         },
            //         success: function (json_res) {
            //             console.log("checkCompletion() => SUCCESS");
            //             $res = JSON.parse(json_res.result);
            //             if ($res.status == 0) {
            //                 console.log('File tmp/switchBranch.done exists');
            //                 return 1;
            //             }
            //         }
            //     });
            //     console.log("checkCompletion() => SUCCESS, but not done");
            //     return 0;
            // }

            // function switchSleep(s) {
            //     console.log("switchSleep()");
            //     return new Promise(resolve => setTimeout(resolve, s * 1000));
            // }

            /* Wait until 'switchBranch.done' file exists in 'tmp' */
            // async function waitCompletion() {
            //     console.log("waitCompletion()");
            //     if (checkCompletion())
            //         return 0;
            //     await switchSleep(1);
                // var timeout = 10; // 10s timeout
                // var t;
                // var switchDone = 0;
                // for (t = 0; t < timeout; t++) {
                //     console.log("Before fileExists() t="+t)
                //     $.ajax({
                //         type: 'POST',
                //         url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
                //         data: {
                //             action: 'fileExists',
                //             path: 'tmp/switchBranch.done'
                //         },
                //         dataType: 'json',
                //         global: false,
                //         error: function (request, status, error) {
                //         },
                //         success: function (json_res) {
                //             $res = JSON.parse(json_res.result);
                //             if ($res.status == 0) {
                //                 console.log('File tmp/switchBranch.done exists');
                //                 switchDone = 1;
                //             }
                //         }
                //     })
                //     .then(() => {
                //         if (switchDone == 1)
                //             return 0;
                //         console.log("dodo 1sec, t="+t);
                //         await switchSleep(1);
                //     });
                // }
            //     return -1;
            // }

            // if (waitCompletion() != 0) {
            //     /* Error */
            //     bootbox.alert("ERREUR 'switchBranch' !<br>Le basculement s'est mal passé.");
            // } else {
            //     document.location.reload(true);
            //     // Note: reload does not reload config page but default page. How to solve ?
            // }
        });
    }

    /* For dev mode. Called to save debug config change in 'tmp/debug.json'. */
    function saveChanges() {
        console.log("saveChanges()");

        /* Get current config */
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
            data: {
                action: 'readDevConfig'
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'readDevConfig' !<br>status="+status+"<br>error="+error);
            },
            success: function (json_res) {
                console.log(json_res);
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                    alert(msg);
                    return;
                }

                /* Update config */
                devConfig = JSON.parse(res.config);

                var dbgParserDisableList = document.getElementById('idParserLog').value;
                console.log("dbgParserDisableList="+dbgParserDisableList);
                if (dbgParserDisableList != "") {
                    var dbgParserLog = [];
                    var res = dbgParserDisableList.split(" ");
                    res.forEach(function(value) {
                        console.log("value="+value);
                        dbgParserLog.push(value);
                    });
                    devConfig["dbgParserLog"] = dbgParserLog;
                } else
                    devConfig["dbgParserLog"] = [];

                var dbgDefinesList = document.getElementById('idDefines').value;
                console.log("dbgDefinesList="+dbgDefinesList);
                if (dbgDefinesList != "") {
                    var dbgDefines = [];
                    var res = dbgDefinesList.split(" ");
                    res.forEach(function(value) {
                        console.log("value="+value);
                        dbgDefines.push(value);
                    });
                    devConfig["defines"] = dbgDefines;
                } else
                    devConfig["defines"] = [];

                /* Save config */
                jsonConfig = JSON.stringify(devConfig);
                console.log("New devConfig="+jsonConfig);
                $.ajax({
                    type: 'POST',
                    url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
                    data: {
                        action: 'writeDevConfig',
                        devConfig: jsonConfig
                    },
                    dataType: 'json',
                    global: false,
                    error: function (request, status, error) {
                        bootbox.alert("ERREUR 'writeDevConfig' !<br>status="+status+"<br>error="+error);
                    },
                    success: function (json_res) {
                        console.log(json_res);
                        res = JSON.parse(json_res.result);
                        if (res.status != 0) {
                            var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                            alert(msg);
                            return;
                        } else {
                            // TODO: Restart daemons
                            window.location.reload();
                        }
                    }
                });
            }
        });
    }

    /* Cleanup Abeille plugin removing files no longer required.
       This is based on "Abeille.md5" reference file.
       WARNING: To be sure that MD5 file is aligned with current content, integrity check must return SUCCESS. */
    function cleanUp() {
        console.log("cleanUp() button click")

        $.showLoading()
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'doPostUpdateCleanup',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                $.hideLoading()
                bootbox.alert("ERREUR 'doPostUpdateCleanup' !<br>Votre installation semble corrompue.");
            },
            success: function (json_res) {
                $.hideLoading()
                console.log(json_res);
            }
        });
    }

    // Called when Abeille plugin config has been saved
    function Abeille_postSaveConfiguration() {
        console.log("Abeille_postSaveConfiguration()");

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'getConfig'
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                console.log("Ajax error: status=", status);
            },
            success: function (json_res) {
                // console.log("json_res=", json_res);
                res = JSON.parse(json_res.result);
                // Assuming status 0
                config = JSON.parse(res.config);
                // console.log("config=", config);

                errors = '';
                for (gtwId = 1; gtwId < js_maxGateways; gtwId++) {
                    if (config['ab::gtwEnabled'+gtwId] != 'Y')
                        continue; // Disabled

                    gtwType = config['ab::gtwType'+gtwId];
                    gtwSubType = config['ab::gtwSubType'+gtwId];
                    // console.log("gtwType "+gtwId+" subType "+gtwSubType);

                    if (gtwType == "zigate") {
                        if (gtwSubType == 'WIFI') {
                            if (config['ab::gtwIpAddr'+gtwId] == '') {
                                errors += "Zigate "+gtwId+": {{- Adresse IP manquante}}";
                            }
                        }
                    } else { // gtwType == "ezsp"

                    }
                    // TODO: Check type choice vs FW version
                }
                if (errors != '')
                    window.alert(errors);
                else {
                    // Restart daemons if already running
                    $.ajax({
                        type: 'POST',
                        url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
                        data: {
                            action: 'getRunningDaemons',
                        },
                        dataType: 'json',
                        global: false,
                        success: function (json_res) {
                            // console.log(json_res);
                            res = JSON.parse(json_res.result);
                            deamons = res.daemons;
                            running = deamons.runningNb;
                            if (running > 0) {
                                console.log(running+" daemons running => Restarting all");
                                $.ajax({
                                    type: 'POST',
                                    url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
                                    data: {
                                        action: 'restartDaemons',
                                    }
                                });
                            }
                        }
                    });
                }
            }
        });
    }

</script>
