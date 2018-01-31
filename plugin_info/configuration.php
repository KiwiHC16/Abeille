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
                <label class="col-lg-4 control-label">{{IP de Mosquitto : }}</label>
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

            <div class="form-group" id="mqtt_topic">
                <label class="col-lg-4 control-label">{{Topic root (defaut: Tous): }}</label>
                <div class="col-lg-4">
                    <input class="configKey form-control" data-l1key="mqttTopic" style="margin-top:5px"
                           placeholder="#"/>
                </div>
            </div>

            <div class="form-group" id="mqtt_qos">
                <label class="col-lg-4 control-label">{{Qos}}</label>
                <div class="col-lg-4">
                    <select class="configKey form-control" data-l1key="mqttQos">
                        <option value="0">0</option>
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-4 control-label">{{Abeille Serial Port : }}</label>
                <div class="col-lg-4">
                    <select class="configKey form-control col-sm-2" data-l1key="AbeilleSerialPort">
                        <option value="none" selected>{{Aucun}}</option>
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
                <label class="col-lg-4 control-label">{{Id de l objet de rattachement par defaut : }}</label>
                <div class="col-lg-4">
                    <input class="configKey form-control" data-l1key="AbeilleId" style="margin-top:5px"
                           placeholder="1"/>
                </div>
            </div>

            <div class="form-group" id="mqtt_creationObjectMode">
                <label class="col-lg-4 control-label">{{Mode de creation des objets}}</label>
                <div class="col-lg-4">
                    <select style="width:auto" class="configKey form-control" data-l1key="creationObjectMode">
                        <option value="Automatique" selected>Automatique</option>
                        <option value="Semi Automatique">Semi Automatique</option>
                        <option value="Manuel">Manuel</option>
                    </select>
                </div>
            </div>

        </fieldset>
</form>
