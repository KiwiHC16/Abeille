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


            <legend><i class="fa fa-list-alt"></i> {{Documentation}}</legend>


            <div align="center">
                <a class="btn btn-success" href="/plugins/Abeille/docs/fr_FR/changelog.html" target="_blank">{{ChangeLog (Locale)}}</a>
                <a class="btn btn-success" href="https://kiwihc16.github.io/Abeille/fr_FR/changelog.html" target="_blank">{{ChangeLog (Derniere)}}</a>
            
                <a class="btn btn-success" href="/plugins/Abeille/docs/fr_FR/index.html" target="_blank">{{Manuel utilisateur (Locale)}}</a>
                <a class="btn btn-success" href="https://kiwihc16.github.io/Abeille/fr_FR/index.html" target="_blank">{{Manuel utilisateur (Derniere)}}</a>
            </div>

            <p>
                <i>
                (Locale) => Version Locale, sur votre système, associée à la verison Abeille de votre systeme<br>
                (Derniere) => Derniere Version de la documentation disponible sur le net
                </i>
            </p>



            <legend><i class="fa fa-list-alt"></i> {{Général}}</legend>


            <div>
                <p><i> En passant votre souris sur les titres des champs, vous pouvez obtenir des informations specifiques sur chaque champs.</i></p>
                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Version Abeille.">{{Version Abeille : }}</label>
                    <div class="col-lg-4">
                        Mid March Release
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
                            <option value="/tmp/zigate" >{{WIFI}}</option>
                            <!--option value="auto">{{Auto}}</option-->
                            <?php
                                foreach (jeedom::getUsbMapping('', true) as $name => $value) {
                                    echo '<option value="'.$name.'">'.$name.' ('.$value.')</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate ainsi que le port (IP:Port(9999/23)). 9999 est le port du module wifi zigate par dafaut, mettre 23 si vous utilisez ESP-Link.">{{IP (IP:Port) de Zigate Wifi : }}</label>
                        <div class="col-sm-4">
                            <input class="configKey form-control" data-l1key="IpWifiZigate" style="margin-top:5px" placeholder="192.168.4.1:9999"/>
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
                                foreach (object::all() as $object) {
                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Abeille plusieurs mode de création des équipements dans Jeedom. Automatique: si l'objet zigbee est connu et reconnu alors qu'il se join au réseau, il est crée dans jeedom de facon completement automatique. Semi-auto:l'objet est crée a partir des infos communiquées par l'objet zigbee, manuel: il faut créer l'objet soit même.">{{Mode de creation des objets}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="creationObjectMode">
                            <option value="Automatique">{{Automatique}}</option>
                            <option value="Semi Automatique">{{Semi-Automatique}}</option>
                            <option value="Manuel">{{Manuel}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Dans le réseau ZigBee, les équipements ont une adresse dite courte qui peut variée au fil de la vie du réseau. Si celle si change le mode Automatique mettra à jour l objet. Manuel: Abeille ne fera rien sur changement d adresse courte d un équipement. Ce choix n'a pas vraiment de sens dans le fonctionnement normal, choisissez automatique et il est fort probable que le mode manuel disparaisse dans l avenir.">{{Mode de gestion des adresses courtes}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="adresseCourteMode">
                            <option value="Automatique">{{Automatique}}</option>
                            <option value="Manuel">{{Manuel}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Oui: Ce mode active l'affichage de toutes les commandes des équipements et infos des péripériques zigbee. En mode d'utilisation normale, cela n'est pas nécessaire. Par defaut choisissez plutot Non">{{Affichage mode dévelopeur}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="showAllCommands">
                            <option value="Y">{{Oui}}</option>
                            <option value="N">{{Non}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Oui: Ce mode active l'affichage des informations réseau sur les widgets. Par défaut c est desactivé car ces informations ne sont pas nécessaire pour l utilisateur. Si vous avez changé la visibilité de certaines commandes choisissez: Aucune Action">{{Affichage Information Réseau}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="affichageNetwork">
                            <option value="Y">{{Oui}}</option>
                            <option value="N">{{Non}}</option>
                            <option value="na">{{Aucune action}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Oui: Ce mode active l'affichage des informations de temps sur les widgets. En mode d'utilisation normale, cela n'est pas nécessaire. Par defaut choisissez plutot Non. Si vous avez changé la visibilité de certaines commandes choisissez: Aucune Action">{{Affichage Information Time}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="affichageTime">
                            <option value="Y">{{Oui}}</option>
                            <option value="N">{{Non}}</option>
                            <option value="na">{{Aucune action}}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Oui: Ce mode active l'affichage des commandes additionnelles sur les widgets. En mode d'utilisation normale, cela n'est pas nécessaire. Par defaut choisissez plutot Non. Si vous avez changé la visibilité de certaines commandes choisissez: Aucune Action">{{Affichage Commandes Additionnelles}}</label>
                    <div class="col-lg-4">
                        <select style="width:auto" class="configKey form-control" data-l1key="affichageCmdAdd">
                            <option value="Y">{{Oui}}</option>
                            <option value="N">{{Non}}</option>
                            <option value="na">{{Aucune action}}</option>
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


                <div class="form-group">
                    <p><i>Vous pouvez recupérer les derniers modeles a l aide de ces boutons. Mais surtout faire des backup de votre systeme avant. En effet le code qui tourne sur votre machine peut ne pas etre compatibles avec ces modeles. Ces boutons ne sont pas pour les utilisateurs classiques mais pour le dev et les utilisateurs très impatients qui savent ce qu ils font.</i></p>
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Permet de recuperer les derniers modeles des objets.">{{Options avancées}}</label>
                    <div class="col-lg-5">
                        <a class="btn btn-warning" id="bt_syncconfigAbeille"><i class="fa fa-refresh"></i> {{Mise a jour des modèles}}</a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label" data-toggle="tooltip" title="Permet d appliquer les derniers modeles des objets sur les objets existants.">{{Options avancées}}</label>
                    <div class="col-lg-5">
                        <a class="btn btn-warning" id="bt_updateConfigAbeille"><i class="fa fa-refresh"></i> {{Appliquer nouveaux modèles}}</a>
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
                        <a class="btn btn-warning" id="bt_updateFrimware"><i class="fa fa-refresh"></i> {{Programmer}}</a>
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

$('#bt_updateFrimware').on('click',function(){
                              bootbox.confirm('{{Vous êtes sur le point de programmer la zigate.<br> Voulez vous continuer ?}}', function (result) {
                                              if (result) {
                                              $('#md_modal2').dialog({title: "{{Programmation de la zigate}}"});
                                              $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=updateFrimware.abeille').dialog('open');
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

