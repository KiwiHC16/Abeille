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
    <div class="form-group">
        <fieldset>
            <legend><i class="fa fa-list-alt"></i> {{Général}}</legend>
<div>
<p><i> En passant votre souris sur les titres des champs, vous pouvez obtenir des informations specifiques sur chaque champs.
</i></p>
<div class="form-group">
<label class="col-lg-4 control-label" data-toggle="tooltip" title="Version Abeille.">{{Version Abeille : }}</label>
<div class="col-sm-4">
Dev Master
</div>
</div>


<legend><i class="fa fa-list-alt"></i> {{Connection}}</legend>
            <div>
<p><I> La ZiGate possede deux types possibles de modules pour etre connecté au système Jeedom. Un module USB ou un module Wifi. Si vous êtes en wifi choisissez WIFI ici et indiquez dans le champ IP:Port les informations du module WIFI. Si vous êtes en USB, choisissez le port USB du type ttyUSBx sur lequel la ZiGate est branchée.
                </i></p>
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
<legend><i class="fa fa-list-alt"></i> {{Parametre}}</legend>
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
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Le plugin Abeille possede des objets Timer. Si vous ne voulez utiliser que les Timers sans avoir de Zigate connectée mettre Oui. Ce mode permet de ne pas lancer les demons pour la ZiGate et n avoir que les timers.">{{Mode Timer seulement}}</label>
                <div class="col-lg-4">
                    <select style="width:auto" class="configKey form-control" data-l1key="onlyTimer">
                        <option value="Y">{{Oui}}</option>
                        <option value="N">{{Non}}</option>
                    </select>
                </div>
            </div>


	<div class="form-group">
		<label class="col-lg-4 control-label">{{Options avancées}}</label>
		<div class="col-lg-5">
			<a class="btn btn-warning" id="bt_syncconfigAbeille"><i class="fa fa-refresh"></i> {{Configs modules}}</a>
		</div>
	</div>

<legend><i class="fa fa-list-alt"></i> {{Messages MQTT - Brocker Mosquitto}}</legend>
<p><I> Abeille utilise le protocol MQTT pour échanger des messages MQTT entre les parties logicielles d'Abeille. Par defaut le broker Mosquitto est installé. C'est lui qui gere la reception et distribution des messages MQTT. Vous pouvez utiliser celui qui est installé par défaut ou utiliser un autre. Si vous n'avez pas de broker MQTT spécifique alors laissez les parametres par défaut.</br>
    Tous les tests sont faits avec le broker mosquitto par defaut donc si vous voulez utiliser un autre broker vérifiez que tout fonctionne comme attendu.
</i>
</p>
</div>

<div class="form-group">
<label class="col-lg-4 control-label" data-toggle="tooltip" title="Mosquitto passe les messages entre les différents éléments du plugin, le plugin installera mosquitto sur jeedom si besoin.">{{IP de Mosquitto : }}</label>
<div class="col-sm-4">
<input class="configKey form-control" data-l1key="AbeilleAddress" style="margin-top:5px"
placeholder="127.0.0.1"/>
</div>
</div>

<div class="form-group">
<label class="col-lg-4 control-label data-toggle="tooltip" title="Adresse IP du Broker MQTT">{{Port de Mosquitto : }}</label>
<div class="col-sm-4">
<input class="configKey form-control" data-l1key="AbeillePort" style="margin-top:5px"
placeholder="1883">
</div>
</div>

<div class="form-group">
<label class="col-lg-4 control-label data-toggle="tooltip" title="Port du Broker MQTT">{{Identifiant de Connexion : }}</label>
<div class="col-sm-4">
<input class="configKey form-control" data-l1key="AbeilleConId" style="margin-top:5px"
placeholder="Jeedom"/>
</div>
</div>

<div class="form-group">
<label class="col-lg-4 control-label data-toggle="tooltip" title="Nom d'utilisateur pour se connecter au Broker MQTT">{{Compte de Connexion (non obligatoire) : }}</label>
<div class="col-lg-4">
<input class="configKey form-control" data-l1key="mqttUser" style="margin-top:5px"
placeholder="Jeedom"/>
</div>
</div>

<div class="form-group">
<label class="col-lg-4 control-label data-toggle="tooltip" title="Password de l'utilisateur pour se connecter au Broker MQTT">{{Mot de passe de Connexion (non obligatoire) : }}</label>
<div class="col-lg-4">
<input type="password" class="configKey form-control" data-l1key="mqttPass" style="margin-top:5px"
placeholder="Jeedom"/>
</div>
</div>

<div class="form-group">
<label class="col-lg-4 control-label data-toggle="tooltip" title="Racine dans l'arborescence MQTT des messages">{{Topic root (defaut: Tous): }}</label>
<div class="col-lg-4">
<input class="configKey form-control" data-l1key="mqttTopic" style="margin-top:5px"
placeholder="#"/>
</div>
</div>

<div class="form-group">
<label class="col-lg-4 control-label" data-toggle="tooltip" title="Le mode 0 est conseillé. Les trois mode ont été testés et fonctionnent.">{{Qos}}</label>
<div class="col-lg-4">
<select class="configKey form-control" data-l1key="mqttQos">
<option value="0">0</option>
<option value="1" >1</option>
<option value="2">2</option>
</select>
</div>
</div>

         </fieldset>
    </div>
</form>

<script>
    	$('#bt_syncconfigAbeille').on('click',function(){
		bootbox.confirm('{{Etes-vous sûr de vouloir télécharger les dernières configurations des modules ?<br>Si vous avez des modifications locales des fichier JSON, elles seront perdues.}}', function (result) {
				if (result) {
					$('#md_modal2').dialog({title: "{{Téléchargement des configurations}}"});
					$('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=syncconf.abeille').dialog('open');
					}
		});
	});
    </script>
