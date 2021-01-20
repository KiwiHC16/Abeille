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
    $dbgFile = __DIR__."/../tmp/debug.json";
    if (file_exists($dbgFile)) {
        $dbgConfig = json_decode(file_get_contents($dbgFile), TRUE);
        $dbgDeveloperMode = TRUE;
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
    include_once __DIR__."/../core/php/AbeillePreInstall.php";

    $zigateNbMax = 10;
    $zigateNb = config::byKey('zigateNb', 'Abeille', 1);
    echo '<script>var js_NbMaxOfZigates = '.$zigateNbMax.';</script>'; // PHP to JS
    echo '<script>var js_NbOfZigates = '.$zigateNb.';</script>'; // PHP to JS
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
        <legend><i class="fa fa-list-alt"></i> {{Général}}</legend>
        <div>
            <p><i> Suivez toutes les dernieres informations <a href="https://community.jeedom.com/t/news-de-la-ruche-par-abeille-plugin-abeille/26524"><strong>sur ce forum</strong> </a>.</i></p>
            <p><i> Les discussions et questions se feront via <a href="https://community.jeedom.com/tags/plugin-abeille"><strong>ce forum</strong> </a>. Pensez à mettre le flag "plugin-abeille" dans tous vos posts.</i></p>
            <p><i> Note: si vous souhaitez de l aide, ne passez pas par les fonctions integrées de jeedom car je recois les requetes sans pouvoir contacter les personnes en retour. Passez par le forum.</i></p>
            <p><i> Note: Jeedom propose une fonction de Heartbeat dans la tuile "Logs et surveillance". Cette fonction n'est pas implémentée dans Abeille. Ne pas l'activer pour l'instant.</i></p>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label" data-toggle="tooltip">{{Version Abeille : }}</label>
            <div class="col-lg-4 confs1" title="{{Version du plugin Abeille.}}">
                <?php include __DIR__."/AbeilleVersion.inc"; ?>
            </div>
            <div class="col-lg-4">
                <?php
                    /* Developers only: display current branch & allows to switch to another one */
                    if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
                        /* TODO: Check if GIT repo & GIT present */
                        if (gitIsRepo() == FALSE) {
                            echo "Pas sous GIT";
                        } else {
                            $localChanges = gitHasLocalChanges();
                            if ($localChanges == TRUE)
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
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label" data-toggle="tooltip" >{{Objet Parent : }}</label>
            <div class="col-lg-4 confs1" title="{{Objet parent (ex: une pièce de la maison) par défaut pour toute nouvelle zigate. Peut être changé plus tard via la page de gestion d'Abeille en cliquant sur la ruche correspondante}}.">
                <select class="configKey form-control" data-l1key="AbeilleParentId">
                    <?php
                        foreach (jeeObject::all() as $object) {
                            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <legend><i class="fa fa-list-alt"></i> {{Zigates}}</legend>
        <a id="idZigatesShowHide" class="btn btn-success" >{{Afficher}}</a>
        <div id="Connection">
            <div>
                <p><i>{{Ce plugin supporte 4 types de Zigates: USB, Wifi, "PI" ou DIN}}</i></p>
                <p><i>{{- Si USB, Pi ou DIN, le champ 'Port série' doit indiquer le port correspondant (ex: ttyUSB0 ou ttyS1)}}</i></p>
                <p><i>{{- Si Wifi, le champ 'Adresse IP' doit indiquer l'adresse de la Zigate et son port (ex: 192.168.4.1:9999)}}</i></p>
                <p><i>{{Les zigates non utilisées doivent être désactivées.}}</i></p>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip">{{Nombre de zigates : }}</label>
                <div class="col-lg-4 confs1">
                    <select id="nbOfZigates" class="configKey form-control" data-l1key="zigateNb" title="{{Nombre de zigates.}}">
                        <option value="1" selected>1</option>
                        <?php
                        for ( $i=2; $i<=$zigateNbMax; $i++ ) {
                            echo '<option value="'.$i.'">'.$i.'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <?php
            for ( $i=1; $i<=$zigateNbMax; $i++ ) {
                echo '<div id="grp_Zigate'.$i.'">';
            ?>
                <div class="form-group">
                    <label class="col-lg-4 control-label">-----</label>
                    <div class="col-lg-4 confs1">
                    <?php
                    echo '<label style="padding-top: 7px">------------ Zigate'.$i.' ------------</label>';
                    ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip">{{Nom : }}</label>
                    <div class="col-lg-4 confs1">
                        <?php
                        if (Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille')) {
                            $zgName = Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille')->getName();
                            $networkName = Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille')->getHumanName();
                        } else {
                            $zgName = "";
                            $networkName = "";
                        }
                        echo '<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="'.$zgName.'" disabled title="{{Nom de la zigate; N\'est modifiable que via la page de gestion d\'Abeille après sauvegarde.}}">';
                    echo '</div>';
                    echo '<div>';
                        echo '<label class="control-label ml4px" data-toggle="tooltip" title="{{Chemin hiérarchique Jeedom}}">'.$networkName.'</label>';
                    echo '</div>';
                    ?>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip">{{Type : }}</label>
                    <div class="col-lg-4 confs1">
                        <?php
                        echo '<select id="idSelZgType'.$i.'" class="configKey form-control" data-l1key="AbeilleType'.$i.'" onchange="checkZigateType('.$i.')"  title="{{Type de zigate}}">';
                        ?>
                            <option value="USB" selected>{{USB}}</option>
                            <option value="WIFI" >{{WIFI}}</option>
                            <option value="PI" >{{PI}}</option>
                            <option value="DIN" >{{DIN}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip">{{Port série : }}</label>
                    <div class="col-lg-4 confs1">
                        <?php
                            echo '<select id="idSelSP'.$i.'" class="configKey form-control" data-l1key="AbeilleSerialPort'.$i.'" title="{{Port série si zigate USB ou Pi.}}" disabled>';
                            echo '<option value="none" selected>{{Aucun}}</option>';
                            echo '<option value="/dev/zigate'.$i.'" >{{WIFI'.$i.'}}</option>';
                            echo '<option value="/dev/monitZigate'.$i.'" >{{Monit'.$i.'}}</option>';

                            foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                $value2 = substr($value, 5); // Remove '/dev/'
                                echo '<option value="'.$value.'">'.$value2.' ('.$name.')</option>';
                            }
                            foreach (ls('/dev/', 'tty*') as $value) {
                                echo '<option value="/dev/'.$value.'">'.$value.'</option>';
                            }
                        ?>
                        </select>
                    </div>
                    <div>
                        <?php
                            echo '<a id="idCheckSP'.$i.'" class="btn btn-warning ml4px" onclick="checkSerialPort('.$i.')" title="{{Test de communication: Arret des démons, interrogation de la zigate, et redémarrage.}}"><i class="fas fa-sync"></i> {{Tester}}</a>';
                            echo '<a class="serialPortStatus'.$i.' ml4px" title="{{Status de communication avec la zigate. Voir \'AbeilleConfig.log\' si \'NOK\'.}}">';
                        ?>
                        <span class="label label-success" style="font-size:1em">-?-</span>
                        </a>
                        <?php
                            echo '<select id="idFW'.$i.'" style="width:55px" title="{{Firmwares disponibles}}">';
                            foreach (ls('/var/www/html/plugins/Abeille/Zigate_Module/', '*.bin') as $fwName) {
                                $fwVers = substr($fwName, 0, -4); // Removing ".bin" suffix
                                if (substr($fwVers, -4) == ".dev") {
                                    /* FW for developer mode only */
                                    if (!isset($dbgDeveloperMode) || ($dbgDeveloperMode == FALSE))
                                        continue; // Not in developer mode. Ignoring this FW
                                    $fwVers = substr($fwVers, 0, -4); // Removing ".dev" suffix
                                }
                                $fwVers = substr($fwVers, 8); // Removing "ZiGate_v" prefix
                                if (preg_match("(3.1c)", $fwVers))
                                    echo '<option value='.$fwName.' selected>'.$fwVers.'</option>'; // Selecting default choice
                                else
                                    echo '<option value='.$fwName.'>'.$fwVers.'</option>';
                            }
                        ?>
                        </select>
                        <?php
                            echo '<a id="idUpdateFW'.$i.'" class="btn btn-warning" onclick="updateFW('.$i.')" title="{{Programmation du FW selectionné}}"><i class="fas fa-sync"></i> {{Mettre à jour}}</a>';
                            // echo '<a id="idResetE2P'.$i.'" class="btn btn-warning ml4px" onclick="resetE2P('.$i.')" title="{{Effacement de l\'EEPROM}}"><i class="fas fa-sync"></i> {{Effacer E2P}}</a>';
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip">{{Adresse IP (IP:Port) : }}</label>
                    <div class="col-lg-4 confs1">
                        <?php
                        echo '<input id="idWifiAddr'.$i.'" class="configKey form-control" data-l1key="IpWifiZigate'.$i.'" placeholder="<adresse>:<port>" title="{{Adresse IP:Port si zigate Wifi. 9999 est le port par défaut d\'une Zigate WIFI. Mettre 23 si vous utilisez ESP-Link.}}" />';
                        // echo '<a id="idCheckWifi'.$i.'" class="btn btn-warning ml4px" onclick="checkWifi('.$i.')" title="{{Test de communication}}"><i class="fas fa-sync"></i> {{Tester}}</a>';
                        // echo '<a class="wifiStatus'.$i.' ml4px" title="{{Status de communication avec la zigate. Voir \'AbeilleConfig.log\' si \'NOK\'.}}">';
                        ?>
                            <!-- <span class="label label-success" style="font-size:1em;">-?-</span> -->
                        <!-- </a> -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip">{{Status : }}</label>
                    <div class="col-lg-4 confs1">
                        <?php echo '<select id="idSelZgStatus'.$i.'" class="configKey form-control" data-l1key="AbeilleActiver'.$i.'" title="{{Activer ou désactiver l\'utilisation de cette zigate.}}">'; ?>
                            <option value="N" selected>{{Désactivée}}</option>
                            <option value="Y">{{Activée}}</option>
                        </select>
                    </div>
                </div>
                </div>
            <?php
            }
            ?>
        </div>
        <br>
        <br>

        <legend><i class="fa fa-list-alt"></i> {{Zigate Wifi}}</legend>
        <a id="idWifiShowHide" class="btn btn-success" >{{Afficher}}</a>
        <div id="zigatewifi">
            <div>
                <p><i>{{La zigate wifi néccéssite l'installation de l'utilitaire socat pour lier un fichier fifo a une ip:port.}}</i></p>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Installation de socat qui permet les échanges avec la zigate wifi.}}">{{Installation de socat : }}</label>
                <div class="col-lg-5">
                    <a class="socatStatus" title="Status d'installation du package">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_installSocat" title="Installation automatique du package"><i class="fas fa-sync"></i>{{Installer}}</a>
                </div>
            </div>
        </div>
        <br>
        <br>

        <legend><i class="fa fa-list-alt"></i> {{PiZigate}}</legend>
        <a id="idPiShowHide" class="btn btn-success" >{{Afficher}}</a>
        <div id="PiZigate">
            <div>
                <p><i>{{La PiZiGate est controllée par un port TTY + 2x GPIO dépendant de votre plateforme.}}</i></p>
                <p><i>{{Le logiciel 'WiringPi' (ou équivalent) est nécessaire pour piloter ces GPIOs.}}</i></p>
                <p><i>{{L'accès aux GPIOs peut se faire depuis un container sous Docker, il faut ajouter --device /dev/mem et --cap_add SYS_RAWIO). Dans ce cas, faites les manipulations au lancement du conteneur.}}</i></p>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{WiringPI est nécéssaire pour controller les GPIO.}}">{{Installation de WiringPi : }}</label>
                <div class="col-lg-5">
                    <a class="WiringPiStatus" title="Status du package">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_checkWiringPi" title="{{Vérification de l'installation}}"><i class="fas fa-sync"></i> {{Retester}}</a>
                    <a class="btn btn-warning" id="bt_installWiringPi" title="{{Installation du package}}"><i class="fas fa-sync"></i> {{Installer}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Port TTY : }}</label>
                <div class="col-lg-5">
                    <select style="width:150px" id="ZiGatePort" data-toggle="tooltip" title="{{Port de communication avec la PiZigate}}">
                        <?php
                            /* Selecting default port.
                               TODO: Currently choosing first port but missing a way to confirm it is PiZigate */
                            if ($zigateNb == 0)
                                echo '<option value="none" selected>{{aucun}}</option>';
                            else {
                                for ($i=1; $i<=$zigateNb; $i++) {
                                    $port = config::byKey('AbeilleSerialPort' . $i, 'Abeille', '');
                                    if ($i == 1)
                                        echo '<option value="' . $port . '" selected>' . $port . '</option>';
                                    else
                                        echo '<option value="' . $port . '">' . $port . '</option>';
                                }
                            }
                        ?>
                    </select>
                    <a class="btn btn-warning" id="bt_installTTY" title="{{Tentative d'activation du port}}"><i class="fas fa-sync"></i> {{Activer}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Reset (HW) PiZigate : }}</label>
                <div class="col-lg-5">
                    <a class="btn btn-warning" id="bt_resetPiZigate" title="{{Reset HW de la PiZigate}}"><i class="fas fa-sync"></i> {{Reset}}</a>
                </div>
            </div>
        </div>

        <br />
        <br />
        <legend><i class="fa fa-list-alt"></i> {{Mise à jour (Vérification)}}</legend>
        <a id="idUpdateCheckShowHide" class="btn btn-success" >{{Afficher}}</a>
        <div id="UpdateCheck">
            <?php
                Abeille_pre_update_analysis(0, 1);
            ?>
        </div>

        <br />
        <br />
        <legend><i class="fa fa-list-alt"></i> {{Options avancées}}</legend>
        <div id="optionsAvancees">
            <div>
                <p><i>{{Dans certains cas, le fonctionnement du plugin doit être adapté.}}</i></p>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Blocage du traitement des annonces, par defaut le laisser sur Non.}}">{{Blocage traitement Annonces : }}</label>
                <div class="col-lg-5">
                    <select class="configKey form-control" data-l1key="blocageTraitementAnnonce" style="width:150px" data-toggle="tooltip" title="{{Bloque le traitement des annonces. Peut être necessaire pour certains equipements. Ce rapporter à la documentation Abeille de l'équipement pour voir si cela est nécessaire.}}">
                        <option value="Non" >{{Non}}</option>
                        <option value="Oui"  >{{Oui}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Blocage du de la recuperation d equipement inconnus, par defaut le laisser sur Oui.}}">{{Blocage recuperation equipement : }}</label>
                <div class="col-lg-5">
                    <select class="configKey form-control" data-l1key="blocageRecuperationEquipement" style="width:150px" data-toggle="tooltip" title="{{Si un equipement se manifeste mais n est pas connu par Abeille, Abeille peut essayer de le recuperer. Dans certaine situation cela peut rendre Abeille instalble.}}">
                        <option value="Oui">{{Oui}}</option>
                        <option value="Non">{{Non}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Agressivité du traitement des annonces, par defaut le laisser sur 4.}}">{{Agressivité traitement Annonces : }}</label>
                <div class="col-lg-5">
                    <select class="configKey form-control" data-l1key="agressifTraitementAnnonce" style="width:150px" data-toggle="tooltip" title="{{Agressivité sur le traitement des annonces venant des équipements. Nombre de fois qu'Abeille interroge un équipement.}}">
                        <option value="4">{{4}}</option>
                        <option value="3">{{3}}</option>
                        <option value="2">{{2}}</option>
                        <option value="1">{{1}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Mode dédié aux développeurs}}">{{Mode developpeur : }}</label>
                <div class="col-lg-5">
                    <?php
                        if (file_exists($dbgFile))
                            echo '<input type="button" onclick="xableDevMode(0)" value="Désactiver" title="Désactive le mode developpeur">';
                        else
                            echo '<input type="button" onclick="xableDevMode(1)" value="Activer" title="Active le mode developpeur">';
                    ?>
                </div>
            </div>

		    <!-- Following functionalities are visible only if 'tmp/debug.json' file exists (developer mode). -->
            <?php
            if (isset($dbgConfig)) {
                echo '<div class="form-group">';
                echo '<label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Liste des messages désactivés dans AbeilleParser.log}}">{{Parser. Messages désactivés : }}</label>';
                echo '<div class="col-lg-5">';
                if (isset($dbgConfig['dbgParserLog'])) {
                    $dbgParserLog = implode(" ", $dbgConfig['dbgParserLog']);
                    echo '<input type="text" id="idParserLog" title="AbeilleParser messages type to disable (ex: 8000)" style="width:400px" value="'.$dbgParserLog.'">';
                } else
                    echo '<input type="text" id="idParserLog" title="AbeilleParser messages type to disable (ex: 8000)" style="width:400px">';
                echo '<input type="button" onclick="saveChanges()" value="Sauver" style="margin-left:8px">';
                echo '</div>';
                echo '</div>';
                echo '<br/>';
            }
            ?>

        </div>
        <br>
        <br>

        <hr>
    </fieldset>
</form>

<script>
    /* Hiding unused zigate groups */
    for (z = js_NbOfZigates + 1; z <= js_NbMaxOfZigates; z++) {
        console.log("Hiding grp_Zigate" + z);
        $("#grp_Zigate" + z).hide();
    }

    $("#Connection").hide();
    $("#zigatewifi").hide();
    $("#PiZigate").hide();
    $("#UpdateCheck").hide();

    $('#idZigatesShowHide').on('click', function () {
            var Label = document.getElementById("idZigatesShowHide").innerText;
            console.log("ZigatesShowHide click: Label=" + Label);
            if (Label == "Afficher") {
                document.getElementById("idZigatesShowHide").innerText = "Cacher";
                document.getElementById("idZigatesShowHide").className = "btn btn-danger";
                $("#Connection").show();
            } else {
                document.getElementById("idZigatesShowHide").innerText = "Afficher";
                document.getElementById("idZigatesShowHide").className = "btn btn-success";
                $("#Connection").hide();
            }
        }
    );

    /* Number of zigates changed. Display more or less 'grp_Zigate'
       and be sure any new zigate is disabled by default */
    $("#nbOfZigates").change(function() {
        var nbOfZigates = Number($("#nbOfZigates").val());
        console.log("Nb Zigates: now=" + nbOfZigates + ", prev=" + js_NbOfZigates);
        if (nbOfZigates > js_NbOfZigates) {
            for (z = js_NbOfZigates + 1; z <= nbOfZigates; z++) {
                $("#idSelZgStatus" + z).val('N');
                $("#idSelZgStatus" + z).change();
                $("#grp_Zigate" + z).show();
            }
        } else if (nbOfZigates < js_NbOfZigates) {
            for (z = nbOfZigates + 1; z <= js_NbOfZigates; z++) {
                $("#grp_Zigate" + z).hide();
            }
        }
        js_NbOfZigates = nbOfZigates;
    });

    /* Called when Zigate type (USB, WIFI, PI, DIN) is changed */
    function checkZigateType(zgNb) {
        console.log("checkZigateType(zgNb=" + zgNb + ")");
        var zgType = $("#idSelZgType" + zgNb).val();
        var idSelSP = document.querySelector('#idSelSP' + zgNb);
        var idCheckSP = document.querySelector('#idCheckSP' + zgNb);
        var idFW = document.querySelector('#idFW' + zgNb);
        var idUpdateFW = document.querySelector('#idUpdateFW' + zgNb);
        // var idResetE2P = document.querySelector('#idResetE2P' + zgNb);
        var idWifiAddr = document.querySelector('#idWifiAddr' + zgNb);
        // var idCheckWifi = document.querySelector('#idCheckWifi' + zgNb);
        if (zgType == "WIFI") {
            console.log('Type changed to Wifi');
            $("#idSelSP" + zgNb).val('/dev/zigate' + zgNb);
            idSelSP.setAttribute('disabled', true);
            idCheckSP.setAttribute('disabled', true);
            idFW.setAttribute('disabled', true);
            idUpdateFW.setAttribute('disabled', true);
            // idResetE2P.setAttribute('disabled', true);
            idWifiAddr.removeAttribute('disabled');
            // idCheckWifi.removeAttribute('disabled');
        } else {
            console.log('Type changed to USB, PI or DIN');
            idWifiAddr.setAttribute('disabled', true);
            // idCheckWifi.setAttribute('disabled', true);
            idSelSP.removeAttribute('disabled');
            idCheckSP.removeAttribute('disabled');
            if (zgType == "PI") { // FW update is supported for PI only
                idFW.removeAttribute('disabled');
                idUpdateFW.removeAttribute('disabled');
                // idResetE2P.removeAttribute('disabled');
            } else {
                idFW.setAttribute('disabled', true);
                idUpdateFW.setAttribute('disabled', true);
                // idResetE2P.setAttribute('disabled', true);
            }
        }
    }

    /* Called when 'IP:port' test button is pressed (Wifi case) */
    // function checkWifi(zgNb) {
        // console.log("checkWifi(zgNb=" + zgNb + ")");
        // /* Note. Onclick seems still active even if button is disabled (wifi case) */
        // var idCheckWifi = document.querySelector('#idCheckWifi' + zgNb);
        // if (idCheckWifi.getAttribute('disabled') != null) {
            // console.log("=> Action ignored (diabled).");
            // return;
        // }
        // var wifiAddr = document.getElementById("idWifiAddr" + zgNb).value;
        // if (wifiAddr == "") {
            // alert("Merci d'entrer une adresse valide au format <addr>:<port>.\nEx: 192.168.1.12:9999");
            // return;
        // }
        // var ssp = "/dev/zigate" + zgNb; // Socat Serial Port
        // console.log("wifiAddr=" + wifiAddr + ", ssp=" + ssp);
        // $.ajax({
            // type: 'POST',
            // url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
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
                    // $('.wifiStatus' + zgNb).empty().append('<span class="label label-success" style="font-size:1em;">OK, FW ' + $fw + '</span>');
                    // $('.wifiStatus' + zgNb).empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                // } else {
                    // $('.wifiStatus' + zgNb).empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                // }
            // }
        // });
    // }

    $('#idWifiShowHide').on('click', function () {
            var Label = document.getElementById("idWifiShowHide").innerText;
            console.log("WifiShowHide click: Label=" + Label);
            if (Label == "Afficher") {
                checkSocatInstallation();
                document.getElementById("idWifiShowHide").innerText = "Cacher";
                document.getElementById("idWifiShowHide").className = "btn btn-danger";
                $("#zigatewifi").show();
            } else {
                document.getElementById("idWifiShowHide").innerText = "Afficher";
                document.getElementById("idWifiShowHide").className = "btn btn-success";
                $("#zigatewifi").hide();
            }
        }
    );

    function checkSocatInstallation() {
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
            data: {
                action: 'checkSocat',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkSocat' !<br>Votre installation semble corrompue.");
            },
            success: function (res) {
                if (res.result == 0)
                    $('.socatStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                else
                    $('.socatStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
            }
        });
    }

    $('#bt_installSocat').on('click',function(){
        bootbox.confirm('{{Vous êtes sur le point d\'installer le package \'socat\' pour les zigates Wifi.<br>Voulez vous continuer ?}}', function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Installation de socat}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=installSocat.abeille').dialog('open');
            }
        });
    })

    $('#idPiShowHide').on('click', function () {
            var Label = document.getElementById("idPiShowHide").innerText;
            console.log("PiShowHide click: Label=" + Label);
            if (Label == "Afficher") {
                $("#bt_checkWiringPi").click(); // Force WiringPi check
                document.getElementById("idPiShowHide").innerText = "Cacher";
                document.getElementById("idPiShowHide").className = "btn btn-danger";
                $("#PiZigate").show();
            } else {
                document.getElementById("idPiShowHide").innerText = "Afficher";
                document.getElementById("idPiShowHide").className = "btn btn-success";
                $("#PiZigate").hide();
            }
        }
    );

    $('#idUpdateCheckShowHide').on('click', function () {
            var Label = document.getElementById("idUpdateCheckShowHide").innerText;
            console.log("idUpdateCheckShowHide click: Label=" + Label);
            if (Label == "Afficher") {
                document.getElementById("idUpdateCheckShowHide").innerText = "Cacher";
                document.getElementById("idUpdateCheckShowHide").className = "btn btn-danger";
                $("#UpdateCheck").show();
            } else {
                document.getElementById("idUpdateCheckShowHide").innerText = "Afficher";
                document.getElementById("idUpdateCheckShowHide").className = "btn btn-success";
                $("#UpdateCheck").hide();
            }
        }
    );

    $('#bt_checkWiringPi').on('click', function() {
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
            data: {
                action: 'checkWiringPi',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkWiringPi' !<br>Votre installation semble corrompue.");
            },
            success: function (res) {
                if (res.result == 0)
                    $('.WiringPiStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                else
                    $('.WiringPiStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
            }
        });
    })

    $('#bt_installWiringPi').on('click', function() {
        bootbox.confirm('{{Vous êtes sur le point d installer WiringPi (http://wiringpi.com) et cela peut provoquer des conflits avec d\'autres gestionnaires de GPIO.<br> Voulez vous continuer ?}}', function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Installation de WiringPi}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=installWiringPi.abeille').dialog('open');
            }
        });
    })

    $('#bt_checkTTY').on('click', function() {
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
            data: {
                action: 'checkTTY',
                zgport: document.getElementById("ZiGatePort").value,
                zgtype: 'PI',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkTTY' !<br>Votre installation semble corrompue.");
            },
            success: function (json_res) {
                $res = JSON.parse(json_res.result);
                if ($res.status == 0) {
                    $('.TTYStatus').empty().append('<span class="label label-success" style="font-size:1em;">OK</span>');
                    $fw = $res.fw;
                    $label = '<span class="label label-success" style="font-size:1em;">' + $fw + '</span>';
                    $('.CurrentFirmware').empty().append($label);
                } else {
                    $('.TTYStatus').empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                    $('.CurrentFirmware').empty().append('<span class="label label-danger" style="font-size:1em;">?</span>');
                }
            }
        });
    });
    $('#bt_readFW').on('click', function() {
        /* Click on 'read FW' as same effect than 'check TTY' */
        $('#bt_checkTTY').click();
    });

    /* Called when serial port test button is pressed */
    function checkSerialPort(zgNb) {
        console.log("checkSerialPort(zgNb=" + zgNb + ")");
        /* Note. Onclick seems still active even if button is disabled (wifi case) */
        var idCheckSP = document.querySelector('#idCheckSP' + zgNb);
        if (idCheckSP.getAttribute('disabled') != null) {
            console.log("=> DISABLED");
            return;
        }
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
            data: {
                action: 'checkTTY',
                zgport: $("#idSelSP" + zgNb).val(),
                zgtype: $("#idSelZgType" + zgNb).val(),
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'checkSerialPort' !<br>Votre installation semble corrompue.");
            },
            success: function (json_res) {
                $res = JSON.parse(json_res.result);
                if ($res.status == 0) {
                    $fw = $res.fw;
                    $('.serialPortStatus' + zgNb).empty().append('<span class="label label-success" style="font-size:1em;">FW ' + $fw + '</span>');
                } else {
                    $('.serialPortStatus' + zgNb).empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                }
            }
        });
    }

    /* Called when FW update button is pressed */
    function updateFW(zgNb) {
        console.log("updateFW(zgNb=" + zgNb + ")");
        /* Note. Onclick seems still active even if button is disabled (wifi case) */
        var idCheckSP = document.querySelector('#idCheckSP' + zgNb);
        if (idCheckSP.getAttribute('disabled') != null) {
            console.log("=> DISABLED");
            return;
        }
        var zgType = $("#idSelZgType" + zgNb).val();
        if (zgType != "PI") {
            console.log("=> Not PI type. UNEXPECTED !");
            return;
        }
        var zgPort = $("#idSelSP" + zgNb).val();
        var zgFW = $("#idFW" + zgNb).val();
        bootbox.confirm('{{Vous êtes sur le point de (re)programmer la PiZigate<br> - port    : '+zgPort+'<br> - firmware: '+zgFW+'<br> Voulez vous continuer ?}}', function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Programmation de la PiZigate}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=configPageModal.abeille&cmd=updateFW&zgport=\"'+zgPort+'\"&fwfile=\"'+zgFW+'\"').dialog('open');
            }
        });
    }

    /* Called when "reset E2P" button is pressed */
    function resetE2P(zgNb) {
        console.log("resetE2P(zgNb=" + zgNb + ")");
        /* Note. Onclick seems still active even if button is disabled (wifi case) */
        var idCheckSP = document.querySelector('#idCheckSP' + zgNb);
        if (idCheckSP.getAttribute('disabled') != null) {
            console.log("=> DISABLED");
            return;
        }
        var zgType = $("#idSelZgType" + zgNb).val();
        if (zgType != "PI") {
            console.log("=> Not PI type. UNEXPECTED !");
            return;
        }
        var zgPort = $("#idSelSP" + zgNb).val();
        var zgFW = $("#idFW" + zgNb).val();
        bootbox.confirm("{{Attention !! Vous êtes sur le point d'effacer l'EEPROM de votre PiZigate.<br>Tous les équipements devront être réinclus.<br> - port    : "+zgPort+"<br>Etes vous sur de vouloir continuer ?}}", function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Effacement EEPROM}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=configPageModal.abeille&cmd=resetE2P&zgport=\"'+zgPort+'\"').dialog('open');
            }
        });
    }

    $('#bt_installTTY').on('click',function(){
        bootbox.confirm('{{Vous êtes sur le point d\'activer le port ' + document.getElementById("ZiGatePort").value + '.<br>Cela pourrait supprimer la console sur ce port.<br> Voulez vous continuer ?}}', function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Activation TTY}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=installTTY.abeille').dialog('open');
            }
        });
    })

    $('#bt_resetPiZigate').on('click',function(){
        bootbox.confirm('{{Vous êtes sur le point de faire un reset HW de la PiZigate.<br>Voulez vous continuer ?}}', function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Reset HW de la PiZigate}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=resetPiZigate.abeille').dialog('open');
            }
       });
    })

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
                url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
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
            //         url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
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
                //         url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
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
</script>
