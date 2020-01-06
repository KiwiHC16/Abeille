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


<hr>
            <legend><i class="fa fa-list-alt"></i> {{Connection}}</legend>
            <a class="btn btn-success" id="bt_Connection_hide"><i class="fa fa-refresh"></i> {{Cache}}</a><a class="btn btn-danger" id="bt_Connection_show"><i class="fa fa-refresh"></i> {{Affiche}}</a>

            <div id="Connection">
                <div>
                    <p><i>La ZiGate possede trois types possibles de modules pour etre connecté au système Jeedom.</i></p>
                    <p><i>Un module USB / PiZigate(GPIO) ou un module Wifi. Si vous êtes en wifi choisissez WIFI ici et indiquez dans le champ IP:Port les informations du module WIFI.</i></p>
                    <p><i>Si vous êtes en USB, choisissez le port USB du type ttyUSBx sur lequel la ZiGate est branchée. Pour PiZiGate choisissez le port série /dev/ttyS0.</i></p>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label data-toggle="tooltip" title="Choisissez le port serie ou le mode WIFI">{{Abeille Serial Port : }}</label>
                    <div class="col-lg-4">
                        <select class="configKey form-control col-sm-2" data-l1key="AbeilleSerialPort">
                            <option value="none" >{{Aucun}}</option>
                            <option value="/dev/zigate" >{{WIFI1}}</option>
                            <option value="/dev/monitZigate1" >{{Monit1}}</option>
                            <!--option value="auto">{{Auto}}</option-->
                            <?php
                                foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                    echo '<option value="'.$value.'">'.$name.' ('.$value.')</option>';
                                }
                                foreach (ls('/dev/', 'tty*') as $value) {
                                    echo '<option value="/dev/' . $value . '">/dev/' . $value . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi 1: }}</label>
                    <div class="col-sm-4">
                        <input class="configKey form-control" data-l1key="IpWifiZigate" style="margin-top:5px" placeholder="192.168.4.1:9999"/>
                    </div>
                </div>
                

                <div class="form-group">
                    <label class="col-lg-4 control-label data-toggle="tooltip" title="Choisissez le port serie ou le mode WIFI">{{Abeille Serial Port 2: }}</label>
                    <div class="col-lg-4">
                        <select class="configKey form-control col-sm-2" data-l1key="AbeilleSerialPort2">
                            <option value="none" >{{Aucun}}</option>
                            <option value="/dev/zigate2" >{{WIFI2}}</option>
                            <option value="/dev/monitZigate2" >{{Monit2}}</option>
                            <!--option value="auto">{{Auto}}</option-->
                            <?php
                                foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                    echo '<option value="'.$value.'">'.$name.' ('.$value.')</option>';
                                }
                                foreach (ls('/dev/', 'tty*') as $value) {
                                    echo '<option value="/dev/' . $value . '">/dev/' . $value . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi 2: }}</label>
                        <div class="col-sm-4">
                            <input class="configKey form-control" data-l1key="IpWifiZigate2" style="margin-top:5px" placeholder="192.168.4.2:9999"/>
                        </div>
                </div>

                <div class="form-group">
                   <label class="col-lg-4 control-label data-toggle="tooltip" title="Choisissez le port serie ou le mode WIFI">{{Abeille Serial Port 3: }}</label>
                   <div class="col-lg-4">
                       <select class="configKey form-control col-sm-2" data-l1key="AbeilleSerialPort3">
                           <option value="none" >{{Aucun}}</option>
                           <option value="/dev/zigate3" >{{WIFI3}}</option>
                           <option value="/dev/monitZigate3" >{{Monit3}}</option>
                           <!--option value="auto">{{Auto}}</option-->
                           <?php
                               foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                   echo '<option value="'.$value.'">'.$name.' ('.$value.')</option>';
                               }
                               foreach (ls('/dev/', 'tty*') as $value) {
                                   echo '<option value="/dev/' . $value . '">/dev/' . $value . '</option>';
                               }
                           ?>
                       </select>
                   </div>
               </div>

               <div class="form-group">
                   <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi 3: }}</label>
                       <div class="col-sm-4">
                           <input class="configKey form-control" data-l1key="IpWifiZigate3" style="margin-top:5px" placeholder="192.168.4.3:9999"/>
                       </div>
               </div>

              <div class="form-group">
                   <label class="col-lg-4 control-label data-toggle="tooltip" title="Choisissez le port serie ou le mode WIFI">{{Abeille Serial Port 4: }}</label>
                   <div class="col-lg-4">
                       <select class="configKey form-control col-sm-2" data-l1key="AbeilleSerialPort4">
                           <option value="none" >{{Aucun}}</option>
                           <option value="/dev/zigate4" >{{WIFI4}}</option>
                           <option value="/dev/monitZigate4" >{{Monit4}}</option>
                           <!--option value="auto">{{Auto}}</option-->
                           <?php
                               foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                   echo '<option value="'.$value.'">'.$name.' ('.$value.')</option>';
                               }
                               foreach (ls('/dev/', 'tty*') as $value) {
                                   echo '<option value="/dev/' . $value . '">/dev/' . $value . '</option>';
                               }
                           ?>
                       </select>
                   </div>
               </div>

               <div class="form-group">
                   <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi 4: }}</label>
                       <div class="col-sm-4">
                           <input class="configKey form-control" data-l1key="IpWifiZigate4" style="margin-top:5px" placeholder="192.168.4.4:9999"/>
                       </div>
               </div>

               <div class="form-group">
                   <label class="col-lg-4 control-label data-toggle="tooltip" title="Choisissez le port serie ou le mode WIFI">{{Abeille Serial Port 5: }}</label>
                   <div class="col-lg-4">
                       <select class="configKey form-control col-sm-2" data-l1key="AbeilleSerialPort5">
                           <option value="none" >{{Aucun}}</option>
                           <option value="/dev/zigate5" >{{WIFI5}}</option>
                           <option value="/dev/monitZigate5" >{{Monit5}}</option>
                           <!--option value="auto">{{Auto}}</option-->
                           <?php
                               foreach (jeedom::getUsbMapping('', false) as $name => $value) {
                                   echo '<option value="'.$value.'">'.$name.' ('.$value.')</option>';
                               }
                               foreach (ls('/dev/', 'tty*') as $value) {
                                   echo '<option value="/dev/' . $value . '">/dev/' . $value . '</option>';
                               }
                           ?>
                       </select>
                   </div>
               </div>

               <div class="form-group">
                   <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi 5: }}</label>
                       <div class="col-sm-4">
                           <input class="configKey form-control" data-l1key="IpWifiZigate5" style="margin-top:5px" placeholder="192.168.4.5:9999"/>
                       </div>
               </div>

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


                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Le plugin Abeille possede des objets Timer. Si vous ne voulez utiliser que les Timers sans avoir de Zigate connectée mettre Oui. Ce mode permet de ne pas lancer les demons pour la ZiGate et n avoir que les timers.">{{Mode Timer seulement}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="onlyTimer">
                            <option value="Y">{{Oui}}</option>
                            <option value="N">{{Non}}</option>
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
                    <p><i>La PiZiGate est installée sur les pin GPIO du raspberry pi. Sur un RPI2, par defaut le port /dev/ttyAMA0 est disponible et utilisable. Sur un RPI3, il faut activer le port serie /dev/ttyS0.</i></p>
                    <p><i>Il faut aussi avoir le logiciel Wiring Pi installé pour faire un reset de la PiZIGate ou la programmer.</i></p>
                    <p><i>Attention l'accès au GPIO ne se fait pas depuis un container sous Docker (Si vous savez faire alors donnez moi la combine), dans ce cas faites les manipulations à la main depuis le host.</i></p>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Installation de WiringPI qui permet de controller les GPIO du RPI.">{{Installation de Wiring Pi}}</label>
                    <div class="col-lg-5">
                        <a class="btn btn-warning" id="bt_installGPIO"><i class="fa fa-refresh"></i> {{Installer}}</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Configuration du RPI pour avoir acces au port serie ttyS0 (Reboot a faire apres execution).">{{Activation ttyS0}}</label>
                    <div class="col-lg-5">
                        <a class="btn btn-warning" id="bt_installS0"><i class="fa fa-refresh"></i> {{Activer}}</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Permet de programmer la PiZiGate.">{{Programmer la PiZiGate}}</label>
                    <div class="col-lg-5">
                        <select style="width:150px" id ="ZiGateFirmwareVersion">
                            <?php
                                foreach (ls('/var/www/html/plugins/Abeille/Zigate_Module/', '*.bin') as $value) {
                                    echo '<option value=' . $value . '>' . $value . '</option>';
                                }
                            ?>
                        </select>
                        <a class="btn btn-warning" id="bt_updateFirmware"><i class="fa fa-refresh"></i> {{Programmer}}</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Permet de faire un reset (HW) de la PiZigate.">{{Reset (HW) PiZiGate}}</label>
                    <div class="col-lg-5">
                        <a class="btn btn-warning" id="bt_resetPiZigate"><i class="fa fa-refresh"></i> {{Reset}}</a>
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

$('#bt_pizigate_hide').on('click', function () {
                 // console.log("bt_hide");
                 // To be defined
                 $("#PiZigate").hide();
                 }
                 );

$('#bt_pizigate_show').on('click', function () {
                 // console.log("bt_test");
                 // To be defined
                 $("#PiZigate").show();
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


$('#bt_installGPIO').on('click',function(){
                           bootbox.confirm('{{Vous êtes sur le point d installer Wiring Pi (http://wiringpi.com), cela peut provoquer des conflits avec d autres gestionnaires des pin GPIO.<br> Voulez vous continuer ?}}', function (result) {
                                           if (result) {
                                           $('#md_modal2').dialog({title: "{{Installation Wiring Pi}}"});
                                           $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=installGPIO.abeille').dialog('open');
                                           }
                                           });
                           })

$('#bt_installS0').on('click',function(){
                        bootbox.confirm('{{Vous êtes sur le point d activer le port serie pour le RPI3. Cela va modifier le mode par defaut avec la console sur ce port. <br> Voulez vous continuer ?}}', function (result) {
                                        if (result) {
                                        $('#md_modal2').dialog({title: "{{Activation ttyS0}}"});
                                        $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=installS0.abeille').dialog('open');
                                        }
                                        });
                        })

$('#bt_updateFirmware').on('click',function(){
                              bootbox.confirm('{{Vous êtes sur le point de programmer la PiZigate avec le firmware ' + document.getElementById("ZiGateFirmwareVersion").value + '.<br> Voulez vous continuer ?}}', function (result) {
                                              if (result) {
                                              $('#md_modal2').dialog({title: "{{Programmation de la PiZigate}}"});
                                              $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=updateFrimware.abeille&fwfile=\"' + document.getElementById("ZiGateFirmwareVersion").value +  '\"').dialog('open');
                                              }
                                              });
                              })

$('#bt_resetPiZigate').on('click',function(){
                           bootbox.confirm('{{Vous êtes sur le point de reset (HW) la zigate.<br> Voulez vous continuer ?}}', function (result) {
                                           if (result) {
                                           $('#md_modal2').dialog({title: "{{Reset (HW) de la zigate}}"});
                                           $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=resetPiZigate.abeille').dialog('open');
                                           }
                                           });
                           })



</script>

