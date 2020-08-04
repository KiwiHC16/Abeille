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

    require_once dirname(__FILE__).'/../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect()) {
        include_file('desktop', '404', 'php');
        die();
    }

    $zigateNbMax = 10;
    $zigateNb = config::byKey('zigateNb', 'Abeille', 1);
    echo '<script>var js_NbMaxOfZigates = '.$zigateNbMax.';</script>'; // PHP to JS
    echo '<script>var js_NbOfZigates = '.$zigateNb.';</script>'; // PHP to JS
?>

<style>
    .confs1 {
      width: 200px;
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
            <p><i> Toutes les dernieres informations <a href="https://community.jeedom.com/t/news-de-la-ruche-par-abeille-plugin-abeille/26524"><strong>sur le forum</strong> </a>.</i></p>
            <p><i> Toutes les dernieres discussions <a href="https://community.jeedom.com/tags/plugin-abeille"><strong>au sujet d Abeille sur le forum</strong> </a>. Si vous postez un message assurez vous de mettre le flag "plugin-abeille".</i></p>
            <p><i> Note: si vous souhaitez de l aide, ne passer pas par les fonctions integrees de jeedom car je recois les requetes sans pouvoir contacter les personnes en retour. Passez par le forum.</i></p>
            <p><i> Note: Jeedom propose une fonction de Heartbeat dans la tuile "Logs et surveillance". Cette fonction n'est pas implémentée dans Abeille. Ne pas l'activer pour l'instant.</i></p>
            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Version d'Abeille.}}">{{Version Abeille : }}</label>
                <div class="col-lg-4">
                    <?php include dirname(__FILE__)."/AbeilleVersion.inc"; ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Objet parent (ex: une pièce de la maison) par défaut pour toute nouvelle zigate. Peut être changé plus tard via la page de gestion d'Abeille en cliquant sur la ruche correspondante}}.">{{Objet Parent : }}</label>
            <div class="col-lg-4">
                <select class="configKey form-control confs1" data-l1key="AbeilleParentId">
                    <?php
                        foreach (jeeObject::all() as $object) {
                            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <legend><i class="fa fa-list-alt"></i> {{Zigates}}</legend>
        <a class="btn btn-success" id="bt_Connection_hide"><i class="fa fa-refresh"></i> {{Cacher}}</a><a class="btn btn-danger" id="bt_Connection_show"><i class="fa fa-refresh"></i> {{Afficher}}</a>
        <div id="Connection">
            <div>
                <p><i>{{Ce plugin supporte 3 types de Zigates: USB, Wifi ou du type "PI"}}</i></p>
                <p><i>{{- Si USB ou Pi, le champ 'Port série' doit indiquer le port correspondant (ex: ttyUSB0 ou ttyS1)}}</i></p>
                <p><i>{{- Si Wifi, le champ 'Adresse IP' doit indiquer l'adresse de la Zigate et son port (ex: 192.168.4.1:9999)}}</i></p>
                <p><i>{{Les zigates non utilisées doivent être désactivées.}}</i></p>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Nombre de zigates.}}">{{Nombre de zigates : }}</label>
                <div class="col-sm-4 confs1">
                    <select id="nbOfZigates" class="configKey form-control col-sm-12 confs1" data-l1key="zigateNb">
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
                    <div class="col-lg-4">
                    <?php
                    echo '<label style="padding-top: 7px">------------ Zigate'.$i.' ------------</label>';
                    ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Nom de la zigate}}">{{Nom : }}</label>
                    <div class="col-lg-8">
                    <?php
                    if (Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille')) {
                        $zgName = Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille')->getName();
                        $networkName = Abeille::byLogicalId('Abeille'.$i.'/Ruche', 'Abeille')->getHumanName();
                    } else {
                        $zgName = "";
                        $networkName = "";
                    }
                    echo '<input type="text" class="eqLogicAttr form-control confs1" data-l1key="name" placeholder="'.$zgName.'" disabled title="{{Ce nom n\'est modifiable que via la page de gestion d\'Abeille après sauvegarde.}}">';
                    echo '<label class="control-label ml4px" data-toggle="tooltip" title="{{Chemin hiérarchique Jeedom}}">'.$networkName.'</label>';
                    echo '</div>';
                    ?>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Type de zigate}}">{{Type : }}</label>
                    <div class="col-lg-8">
                        <?php
                        echo '<select id="idSelZgType'.$i.'" class="configKey form-control col-sm-12 confs1" data-l1key="AbeilleType'.$i.'" onchange="checkZigateType('.$i.')" >';
                        ?>
                            <option value="USB" selected>{{USB}}</option>
                            <option value="WIFI" >{{WIFI}}</option>
                            <option value="PI" >{{PI}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Port série si zigate USB ou Pi.}}">{{Port série : }}</label>
                    <div class="col-lg-8">
                        <?php
                            echo '<select id="idSelSP'.$i.'" class="configKey form-control col-sm-12 confs1" data-l1key="AbeilleSerialPort'.$i.'" disabled>';
                            echo '<option value="none" selected>{{Aucun}}</option>';
                            echo '<option value="/dev/zigate'.$i.'" >{{WIFI'.$i.'}}</option>';
                            echo '<option value="/dev/monitZigate'.$i.'" >{{Monit'.$i.'}}</option>';

                            foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                echo '<option value="'.$value.'">'.$name.' ('.$value.')</option>';
                            }
                            foreach (ls('/dev/', 'tty*') as $value) {
                                echo '<option value="/dev/' . $value . '">' . $value . '</option>';
                            }
                        ?>
                        </select>
                    <?php
                    echo '<a id="idCheckSP'.$i.'" class="btn btn-warning ml4px" onclick="checkSerialPort('.$i.')" title="{{Test de communication}}"><i class="fa fa-refresh"></i> {{Tester}}</a>';
                    echo '<a class="serialPortStatus'.$i.' ml4px" title="{{Status de communication avec la zigate. Voir \'AbeilleConfig\' pour le détail.}}">';
                    ?>
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Adresse IP:Port si zigate Wifi. 9999 est le port par défaut d'une Zigate wifi. Mettre 23 si vous utilisez ESP-Link.}}">{{Adresse IP (IP:Port) : }}</label>
                    <div class="col-lg-8">
                        <?php
                        echo '<input id="idWifiAddr'.$i.'" class="configKey form-control col-sm-12 confs1" data-l1key="IpWifiZigate'.$i.'" placeholder="<adresse>:<port>"/>';
                        // echo '<a id="idCheckWifi'.$i.'" class="btn btn-warning ml4px" onclick="checkWifi('.$i.')" title="{{Test de communication}}"><i class="fa fa-refresh"></i> {{Tester}}</a>';
                        // echo '<a class="wifiStatus'.$i.' ml4px" title="{{Status de communication avec la zigate. Voir \'AbeilleConfig\' pour le détail.}}">';
                        ?>
                            <!-- <span class="label label-success" style="font-size:1em;">-?-</span> -->
                        <!-- </a> -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{Activer ou désactiver l'utilisation de cette zigate.}}">{{Status : }}</label>
                    <div class="col-lg-8">
                        <?php echo '<select id="idSelZgStatus'.$i.'" class="configKey form-control confs1" col-sm-10 data-l1key="AbeilleActiver'.$i.'">'; ?>
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
        <a class="btn btn-success" id="bt_zigatewifi_hide"><i class="fa fa-refresh"></i> {{Cacher}}</a><a class="btn btn-danger" id="bt_zigatewifi_show"><i class="fa fa-refresh"></i> {{Afficher}}</a>
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
                    <a class="btn btn-warning" id="bt_installSocat" title="Installation automatique du package"><i class="fa fa-refresh"></i>{{Installer}}</a>
                </div>
            </div>
        </div>
        <br>
        <br>

        <legend><i class="fa fa-list-alt"></i> {{PiZigate}}</legend>
        <a class="btn btn-success" id="bt_pizigate_hide"><i class="fa fa-refresh"></i> {{Cacher}}</a><a class="btn btn-danger" id="bt_pizigate_show"><i class="fa fa-refresh"></i> {{Afficher}}</a>
        <div id="PiZigate">
            <div>
                <p><i>{{La PiZiGate est controllée par un port TTY + 2x GPIO dépendant de votre plateforme.}}</i></p>
                <p><i>{{Le logiciel 'WiringPi' (ou équivalent) est nécessaire pour piloter ces GPIOs.}}</i></p>
                <p><i>{{Attention l'accès aux GPIOs ne se fait pas depuis un container sous Docker (si vous savez faire alors donnez moi la combine). Dans ce cas faites les manipulations à la main depuis le host.}}</i></p>
                <!-- TODO: Possible known ports should be moved to "Connection" section.
                <p><i>La PiZiGate est installée sur les pin GPIO du raspberry pi. Sur un RPI2, par defaut le port /dev/ttyAMA0 est disponible et utilisable. Sur un RPI3, il faut activer le port serie /dev/ttyS0.</i></p>
                <p><i>Il faut aussi avoir le logiciel Wiring Pi installé pour faire un reset de la PiZIGate ou la programmer.</i></p>
                <p><i>Attention l'accès au GPIO ne se fait pas depuis un container sous Docker (Si vous savez faire alors donnez moi la combine), dans ce cas faites les manipulations à la main depuis le host.</i></p>
                -->
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="{{WiringPI est nécéssaire pour controller les GPIO.}}">{{Installation de WiringPi : }}</label>
                <div class="col-lg-5">
                    <a class="WiringPiStatus" title="Status du package">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_checkWiringPi" title="{{Vérification de l'installation}}"><i class="fa fa-refresh"></i> {{Retester}}</a>
                    <a class="btn btn-warning" id="bt_installWiringPi" title="{{Installation du package}}"><i class="fa fa-refresh"></i> {{Installer}}</a>
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
                    <a class="TTYStatus" title="{{Status de communication sur ce port}}">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_checkTTY" title="{{Test de communication}}"><i class="fa fa-refresh"></i> {{Tester}}</a>
                    <a class="btn btn-warning" id="bt_installTTY" title="{{Tentative d'activation du port}}"><i class="fa fa-refresh"></i> {{Activer}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Firmware : }}</label>
                <div class="col-lg-5">
                    <a class="CurrentFirmware" title="{{Version du firmware actuel}}">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_readFW" title="{{Lecture de la version du FW}}">
                        <i class="fa fa-refresh"></i>
                        {{Lire}}
                    </a>
                    <select style="width:150px" id ="ZiGateFirmwareVersion" title="{{Firmwares disponibles}}">
                        <?php
                            foreach (ls('/var/www/html/plugins/Abeille/Zigate_Module/', '*.bin') as $value) {
                                if (preg_match("(3.1c)", $value))
                                    echo '<option value='.$value.' selected>'.$value.'</option>'; // Selecting default choice
                                else
                                    echo '<option value='.$value.'>'.$value.'</option>';
                            }
                        ?>
                    </select>
                    <a class="btn btn-warning" id="bt_updateFirmware" title="{{Programmation du FW selectionné}}"><i class="fa fa-refresh"></i> {{Mettre à jour}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Reset (HW) PiZigate : }}</label>
                <div class="col-lg-5">
                    <a class="btn btn-warning" id="bt_resetPiZigate" title="{{Reset HW de la PiZigate}}"><i class="fa fa-refresh"></i> {{Reset}}</a>
                </div>
            </div>
        </div>

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

    $('#bt_Connection_hide').on('click', function () {
        $("#Connection").hide();
        }
    );

    $('#bt_Connection_show').on('click', function () {
        $("#Connection").show();
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

    /* Called when Zigate type (USB, WIFI, PI) is changed */
    function checkZigateType(zgNb) {
        console.log("checkZigateType(zgNb=" + zgNb + ")");
        var zgType = $("#idSelZgType" + zgNb).val();
        var idSelSP = document.querySelector('#idSelSP' + zgNb);
        var idCheckSP = document.querySelector('#idCheckSP' + zgNb);
        var idWifiAddr = document.querySelector('#idWifiAddr' + zgNb);
        // var idCheckWifi = document.querySelector('#idCheckWifi' + zgNb);
        if (zgType == "WIFI") {
            console.log('Type changed to Wifi');
            $("#idSelSP" + zgNb).val('/dev/zigate' + zgNb);
            idSelSP.setAttribute('disabled', true);
            idCheckSP.setAttribute('disabled', true);
            idWifiAddr.removeAttribute('disabled');
            // idCheckWifi.removeAttribute('disabled');
        } else {
            console.log('Type changed to USB or PI');
            idWifiAddr.setAttribute('disabled', true);
            // idCheckWifi.setAttribute('disabled', true);
            idSelSP.removeAttribute('disabled');
            idCheckSP.removeAttribute('disabled');
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

    $('#bt_zigatewifi_show').on('click', function () {
            checkSocatInstallation();
            $("#zigatewifi").show();
        }
    );

    $('#bt_zigatewifi_hide').on('click', function () {
            $("#zigatewifi").hide();
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

    $('#bt_pizigate_hide').on('click', function () {
        $("#PiZigate").hide();
    });

    $('#bt_pizigate_show').on('click', function () {
        $("#bt_checkWiringPi").click(); // Force WiringPi check
        $("#PiZigate").show();
    });

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
                    $('.serialPortStatus' + zgNb).empty().append('<span class="label label-success" style="font-size:1em;">OK, FW ' + $fw + '</span>');
                } else {
                    $('.serialPortStatus' + zgNb).empty().append('<span class="label label-danger" style="font-size:1em;">NOK</span>');
                }
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

    $('#bt_updateFirmware').on('click',function(){
        bootbox.confirm('{{Vous êtes sur le point de (re)programmer la PiZigate<br> - port    : ' + document.getElementById("ZiGatePort").value + '<br> - firmware: ' + document.getElementById("ZiGateFirmwareVersion").value + '<br> Voulez vous continuer ?}}', function (result) {
            if (result) {
                $('#md_modal2').dialog({title: "{{Programmation de la PiZigate}}"});
                $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=updateFirmware.abeille&fwfile=\"' + document.getElementById("ZiGateFirmwareVersion").value + '\"&zgport=\"' + document.getElementById("ZiGatePort").value + '\"').dialog('open');
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
</script>
