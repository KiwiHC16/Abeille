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

?>


<form class="form-horizontal">
    <fieldset>
        <legend><i class="fa fa-list-alt"></i> {{Général}}</legend>


            <div>
                <p><i> En passant votre souris sur les titres des champs, vous pouvez obtenir des informations specifiques sur chaque champs.</i></p>
                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Version Abeille.">{{Version Abeille : }}</label>
                    <div class="col-lg-4">
                        Master
                    </div>
                </div>
            </div>

            <legend><i class="fa fa-list-alt"></i> {{Connection}}</legend>
            <a class="btn btn-success" id="bt_Connection_hide"><i class="fa fa-refresh"></i> {{Cache}}</a><a class="btn btn-danger" id="bt_Connection_show"><i class="fa fa-refresh"></i> {{Affiche}}</a>

            <div id="Connection">
                <div>
                    <p><i>La ZiGate possede trois types possibles de modules pour etre connecté au système Jeedom.</i></p>
                    <p><i>Un module USB / PiZigate(GPIO) ou un module Wifi. Si vous êtes en wifi choisissez WIFI ici et indiquez dans le champ IP:Port les informations du module WIFI.</i></p>
                    <p><i>Si vous êtes en USB, choisissez le port USB du type ttyUSBx sur lequel la ZiGate est branchée. Pour PiZiGate choisissez le port série /dev/ttyS0.</i></p>
                    <p><i>Il faut au minimum une zigate sur le premier port.</i></p>
                    <p><i>Bien mettre les zigates non utilisées sur la valeur Aucun sinon le demon risque de ne pas demarrer.</i></p>
                </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Nombre de zigate (si vous changez cette valeur, sauvegarder et recharger la page.">{{Nombre de zigate : }}</label>
                <div class="col-sm-4">
                    <select class="configKey form-control col-sm-12" data-l1key="zigateNb">
                    <?php
                        for ( $i=1; $i<=$zigateNbMax; $i++ ) {
                            echo '<option value="'.$i.'" selected>'.$i.'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    ?>
                <div class="form-group"><label class="col-lg-4 control-label">-----</label>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Nom donné à la ruche qui controle ce réseau zigbee">{{Nom du réseau zigbee : }}</label>
                    <?php if (Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')) {echo Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')->getHumanName();} ?>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Choisissez le port serie ou le mode WIFI">{{Abeille Serial Port : }}</label>
                    <div class="col-lg-4">
                            <?php
                                echo '<select class="configKey form-control col-sm-12" data-l1key="AbeilleSerialPort'.$i.'">';
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
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi : }}</label>
                    <div class="col-sm-4">
                    <?php echo '<input class="configKey form-control" data-l1key="IpWifiZigate'.$i.'" style="margin-top:5px" placeholder="192.168.4.1:9999"/>'; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Activer ou desactiver l utilisation de cette zigate.">{{Activer}}</label>
                    <div class="col-lg-4">
                        <?php echo '<select class="configKey form-control" col-sm-10 data-l1key="AbeilleActiver'.$i.'">'; ?>
                            <option value="N">{{Desactiver}}</option>
                            <option value="Y">{{Activer}}</option>
                        </select>
                    </div>
                </div>
<?php
}
?>
             </div>
<hr>
            <legend><i class="fa fa-list-alt"></i> {{Parametre}}</legend>
            <a class="btn btn-success" id="bt_parametre_hide"><i class="fa fa-refresh"></i> {{Cache}}</a><a class="btn btn-danger" id="bt_parametre_show"><i class="fa fa-refresh"></i> {{Affiche}}</a>

            <div id="Parametre">
                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Tous les objets crées seront des enfants de l'objet selectionné. Les objets sont définis dans le menu principal de Jeedom: Outils->Objets. Les objets representent les pieces de la maison par exemple.">{{Objet Parent}}</label>
                    <div class="col-lg-4">
                        <select class="configKey form-control" data-l1key="AbeilleParentId">
                            <?php
                                foreach (jeeObject::all() as $object) {
                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>

            </div>

            <hr>
            <legend><i class="fa fa-list-alt"></i> {{Zigate Wifi}}</legend>
            <a class="btn btn-success" id="bt_zigatewifi_hide"><i class="fa fa-refresh"></i> {{Cache}}</a><a class="btn btn-danger" id="bt_zigatewifi_show"><i class="fa fa-refresh"></i> {{Affiche}}</a>
            <div id="zigatewifi">
                <div>
                    <p><i>La zigate wifi néccéssite l'installation de l'utilitaire socat pour lier un fichier fifo a une ip:port.</i></p>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Installation de socat qui permet les échanges avec la zigate wifi.">{{Installation de socat}}</label>
                    <div class="col-lg-5">
                        <a class="btn btn-warning" id="bt_installSocat"><i class="fa fa-refresh"></i> {{Installer}}</a>
                    </div>
                </div>


            </div>

<hr>


        <legend><i class="fa fa-list-alt"></i> {{PiZigate}}</legend>
        <a class="btn btn-success" id="bt_pizigate_hide"><i class="fa fa-refresh"></i> {{Cache}}</a><a class="btn btn-danger" id="bt_pizigate_show"><i class="fa fa-refresh"></i> {{Affiche}}</a>
        <div id="PiZigate">
            <div>
                <p><i>La PiZiGate est controllée par un port TTY + 2x GPIO dépendant de votre plateforme.</i></p>
                <p><i>Le logiciel 'WiringPi' (ou équivalent) est nécessaire pour piloter ces GPIOs.</i></p>
                <p><i>Attention l'accès aux GPIOs ne se fait pas depuis un container sous Docker (si vous savez faire alors donnez moi la combine). Dans ce cas faites les manipulations à la main depuis le host.</i></p>
                <!-- TODO: Possible known ports should be moved to "Connection" section.
                <p><i>La PiZiGate est installée sur les pin GPIO du raspberry pi. Sur un RPI2, par defaut le port /dev/ttyAMA0 est disponible et utilisable. Sur un RPI3, il faut activer le port serie /dev/ttyS0.</i></p>
                <p><i>Il faut aussi avoir le logiciel Wiring Pi installé pour faire un reset de la PiZIGate ou la programmer.</i></p>
                <p><i>Attention l'accès au GPIO ne se fait pas depuis un container sous Docker (Si vous savez faire alors donnez moi la combine), dans ce cas faites les manipulations à la main depuis le host.</i></p>
                -->
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="WiringPI est nécéssaire pour controller les GPIO.">{{Installation de WiringPi}}</label>
                <div class="col-lg-5">
                    <a class="WiringPiStatus" title="Status du package">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_checkWiringPi" title="Vérification de l'installation"><i class="fa fa-refresh"></i> {{Retester}}</a>
                    <a class="btn btn-warning" id="bt_installWiringPi" title="Installation du package"><i class="fa fa-refresh"></i> {{Installer}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Port TTY}}</label>
                <div class="col-lg-5">
                    <select style="width:150px" id="ZiGatePort" data-toggle="tooltip" title="Port de communication avec la PiZigate">
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
                    <a class="TTYStatus" title="Status de communication sur ce port">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_checkTTY" title="Test de communication"><i class="fa fa-refresh"></i> {{Tester}}</a>
                    <a class="btn btn-warning" id="bt_installTTY" title="Tentative d'activation du port"><i class="fa fa-refresh"></i> {{Activer}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Firmware}}</label>
                <div class="col-lg-5">
                    <a class="CurrentFirmware" title="Version du firmware actuel">
                        <span class="label label-success" style="font-size:1em;">-?-</span>
                    </a>
                    <a class="btn btn-warning" id="bt_readFW" title="Lecture de la version du FW">
                        <i class="fa fa-refresh"></i>
                        {{Lire}}
                    </a>
                    <select style="width:150px" id ="ZiGateFirmwareVersion" title="Firmwares disponibles">
                        <?php
                            foreach (ls('/var/www/html/plugins/Abeille/Zigate_Module/', '*.bin') as $value) {
                                echo '<option value=' . $value . '>' . $value . '</option>';
                            }
                        ?>
                    </select>
                    <a class="btn btn-warning" id="bt_updateFirmware" title="Programmation du FW selectionné"><i class="fa fa-refresh"></i> {{Mettre à jour}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Reset (HW) PiZigate}}</label>
                <div class="col-lg-5">
                    <a class="btn btn-warning" id="bt_resetPiZigate" title="Reset HW de la PiZigate"><i class="fa fa-refresh"></i> {{Reset}}</a>
                </div>
            </div>
        </div>

        <hr>
    </fieldset>
</form>

<script>

$("#paramMosquitto").hide();
$("#PiZigate").hide();
$("#Parametre").hide();
$("#Connection").hide();
$("#zigatewifi").hide();

$('#bt_hide').on('click', function () {
                    // console.log("bt_hide");
                    // To be defined
                 $("#paramMosquitto").hide();
                    }
                    );

$('#bt_show').on('click', function () {
                 // console.log("bt_test");
                 // To be defined
                 $("#paramMosquitto").show();
                 }
                 );

$('#bt_zigatewifi_show').on('click', function () {
        // console.log("bt_test");
        // To be defined
        $("#zigatewifi").show();
    }
);

$('#bt_zigatewifi_hide').on('click', function () {
        // console.log("bt_hide");
        // To be defined
        $("#zigatewifi").hide();
    }
);

$('#bt_parametre_hide').on('click', function () {
                          // console.log("bt_hide");
                          // To be defined
                          $("#Parametre").hide();
                          }
                          );

$('#bt_parametre_show').on('click', function () {
                          // console.log("bt_test");
                          // To be defined
                          $("#Parametre").show();
                          }
                          );

$('#bt_Connection_hide').on('click', function () {
                           // console.log("bt_hide");
                           // To be defined
                           $("#Connection").hide();
                           }
                           );

$('#bt_Connection_show').on('click', function () {
                           // console.log("bt_test");
                           // To be defined
                           $("#Connection").show();
                           }
                           );

$('#bt_syncconfigAbeille').on('click',function(){
        bootbox.confirm('{{Etes-vous sûr de vouloir télécharger les dernières configurations des modules ?<br>Si vous avez des modifications locales des fichier JSON, elles seront perdues.}}', function (result) {
                if (result) {
                    $('#md_modal2').dialog({title: "{{Téléchargement des configurations}}"});
                    $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=syncconf.abeille').dialog('open');
                    }
        });
    });

$('#bt_updateConfigAbeille').on('click',function(){
                              bootbox.confirm('{{Etes-vous sûr de vouloir mettre à jour les équipements avec les dernières configurations des modules ?<br>Si vous avez des modifications locales, il est possible qu elles seront perdues.}}', function (result) {
                                              if (result) {
                                              $('#md_modal2').dialog({title: "{{Application des configurations}}"});
                                              $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=updateConfig.abeille').dialog('open');
                                              }
                                              });
                              });

$('#bt_installSocat').on('click',function(){
    bootbox.confirm('{{Vous êtes sur le point d\'installer socat pour le module wifi de la zigate. <br> Voulez vous continuer ?}}', function (result) {
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
