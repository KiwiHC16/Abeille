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

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Mosquitto passe les messages entre les différents éléments du plugin, le plugin installera mosquitto sur jeedom si besoin.">{{IP de Mosquitto : }}</label>
                <div class="col-sm-4">
                    <input class="configKey form-control" data-l1key="AbeilleAddress" style="margin-top:5px"
                           placeholder="127.0.0.1"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Port de Mosquitto : }}</label>
                <div class="col-sm-4">
                    <input class="configKey form-control" data-l1key="AbeillePort" style="margin-top:5px"
                           placeholder="1883">
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Identifiant de Connexion : }}</label>
                <div class="col-sm-4">
                    <input class="configKey form-control" data-l1key="AbeilleConId" style="margin-top:5px"
                           placeholder="Jeedom"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Compte de Connexion (non obligatoire) : }}</label>
                <div class="col-lg-4">
                    <input class="configKey form-control" data-l1key="mqttUser" style="margin-top:5px"
                           placeholder="Jeedom"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Mot de passe de Connexion (non obligatoire) : }}</label>
                <div class="col-lg-4">
                    <input type="password" class="configKey form-control" data-l1key="mqttPass" style="margin-top:5px"
                           placeholder="Jeedom"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Topic root (defaut: Tous): }}</label>
                <div class="col-lg-4">
                    <input class="configKey form-control" data-l1key="mqttTopic" style="margin-top:5px"
                           placeholder="#"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Le mode 0 est conseillé. Le mode 1 rencontre des problèmes du à un bug dans mosquitto dans certaines distributions.">{{Qos}}</label>
                <div class="col-lg-4">
                    <select class="configKey form-control" data-l1key="mqttQos">
                        <option value="0">0</option>
                        <option value="1" >1</option>
                        <option value="2">2</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Abeille Serial Port : }}</label>
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
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Toutes les objets crées seront des enfants de l'id.">{{Objet Parent}}</label>
                <div class="col-lg-4">
                    <select class="configKey form-control" data-l1key="AbeilleParentId">
                        <option value="">{{Aucun}}</option>
                            <?php
                                foreach (object::all() as $object) {
                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                            ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Automatique: si l'objet zigbee est connu et reconnu, il est crée dans jeedom. Semi-auto:l'objet est crée a partir des infos communiquées par l'objet zigbee, manuel: il faut créer l'objet doit même.">{{Mode de creation des objets}}</label>
                <div class="col-lg-4">
                    <select style="width:auto" class="configKey form-control" data-l1key="creationObjectMode">
                        <option value="Automatique">{{Automatique}}</option>
                        <option value="Semi Automatique">{{Semi-Automatique}}</option>
                        <option value="Manuel">{{Manuel}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Ce mode active l'affichage de toutes les commandes et infos des péripériques zigbee. En mode d'utilisation normale, cela n'est pas nécessaire.">{{Affichage mode dévelopeur}}</label>
                <div class="col-lg-4">
                    <select style="width:auto" class="configKey form-control" data-l1key="showAllCommands">
                        <option value="Y">{{Oui}}</option>
                        <option value="N">{{Non}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Ce mode permet de ne pas lancer les demons pour la ZiGate et n avoir que les timers.">{{Mode Timer seulement}}</label>
                <div class="col-lg-4">
                    <select style="width:auto" class="configKey form-control" data-l1key="onlyTimer">
                        <option value="Y">{{Oui}}</option>
                        <option value="N">{{Non}}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label" data-toggle="tooltip" title="Adresse IP de la zigate.">{{IP de Zigate Wifi : }}</label>
                <div class="col-sm-4">
                    <input class="configKey form-control" data-l1key="IpWifiZigate" style="margin-top:5px" placeholder="192.168.4.1"/>
                </div>
            </div>



         </fieldset>
    </div>
</form>
