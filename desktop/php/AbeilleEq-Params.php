<!-- This file displays equipment specifics/zigbee infos.
     Included by 'AbeilleEq.php' -->

<?php
    function addDocButton($chapter) {
        // $urlUserMan = "https://kiwihc16.github.io/AbeilleDoc"; // Constant defined in Abeille config
        echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/'.$chapter.'"><i class="fas fa-book"></i> ?</a>';
    }

    function getCmdResult($eqId, $cmdName) {
        $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, $cmdName);
        if (!is_object($cmd))
            return "";
        return $cmd->execCmd();
    }
?>

<form class="form-horizontal">
    <div class="form-group">
        <div class="col-sm-3"></div>
        <h3 class="col-sm-3" style="text-align:center">{{Paramètres zigbee}}</h1>
    </div>
    <hr>

    <div class="form-group">
    <label class="col-sm-3 control-label">{{Dernière comm.}}</label>
    <div class="col-sm-3">
        <?php
            $res = getCmdResult($eqId, 'Time-Time');
            echo '<span>'.$res.'</span>';
        ?>
    </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Adresse (courte/IEEE)}}</label>
        <div class="col-sm-3">
            <?php
            echo '<span>'.$eqAddr.'</span>';
            ?>
            /
            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="IEEE"></span>
        </div>
        <?php
            if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
                echo '<div class="col-sm-3">';
                echo 'config->logicalId=<span>'.$eqLogicId.'</span>';
                echo ', config->topic=<span class="eqLogicAttr" data-l1key="configuration" data-l2key="topic"></span>';
                echo '</div>';
            }
        ?>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Documentation}}</label>
        <div class="col-sm-3">
            <?php
                $model = $eqLogic->getConfiguration('modeleJson', '');
                if ($model != '') {
                    echo '<a href="'.urlProducts.'/'.$model.'" target="_blank">Voir ici si présente</a>';
                }
            ?>
        </div>
    </div>

    <hr>

    <?php
        // If eq is a zigate (short addr=0000 or Ruche), display special parameters
        if ($eqAddr == "0000") {
    ?>
            <div class="form-group">
            <label class="col-sm-3 control-label">Paramètres zigate</label>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Status réseau</label>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'Network-Status');
                        echo '<span>'.$res.'</span>';
                    ?>
                    <button type="button" onclick="sendZigate('StartNetwork', '')">Démarrer</button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Firmware</label>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'SW-SDK');
                        echo '<span>'.$res.'</span>';
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">PAN ID</label>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'PAN-ID');
                        echo '<span>'.$res.'</span>';
                    ?>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Extended PAN ID</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'Ext_PAN-ID');
                        echo '<span>'.$res.'</span>';
                    ?>
                    <!-- TODO <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
                    <button type="button" onclick="sendZigate('SetExtPANId', '')">Modifier</button> -->
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Canal</label>
                    <?php
                        addDocButton("Radio.html#zigate-channel-selection");
                    ?>
                </div>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'Network-Channel');
                        echo '<span title="{{Canal actuel}}">'.$res.'</span>';
                    ?>
                    <input type="text" id="idChannelMask" placeholder="07FFF800" title="{{Masque des canaux autorisés (en hexa). 1 bit par canal.}}" style="margin-left:10px; width:100px">
                    <button type="button" onclick="sendZigate('SetChannelMask', '')">Modifier</button>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Mode inclusion</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'permitJoin-Status');
                        echo '<span>'.$res.'</span>';
                    ?>
                    <!-- <input type="text" name="channelMask" placeholder="XXXXXXXX">
                    <button type="button" onclick="sendZigate('SetChannelMask', '')">Modifier</button> -->
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Heure zigate</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-3">
                    <?php
                        $res = getCmdResult($eqId, 'ZiGate-Time');
                        echo '<span>'.$res.'</span>';
                    ?>
                    <button type="button" onclick="sendZigate('GetTime', '')">Lire</button>
                    <button type="button" onclick="sendZigate('SetTime', '')">Modifier</button>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">TX power</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-3">
                    <input type="text" name="TxPowerValue" placeholder="XX">
                    <button type="button" onclick="sendZigate('SetTXPower', '')">Modifier</button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">LED</label>
                <div class="col-sm-3">
                    <button type="button" onclick="sendZigate('SetLED', 'ON')">ON</button>
                    <button type="button" onclick="sendZigate('SetLED', 'OFF')">OFF</button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Certification</label>
                <div class="col-sm-3">
                    <button type="button" onclick="sendZigate('SetCertif', 'CE')">CE</button>
                    <button type="button" onclick="sendZigate('SetCertif', 'FCC')">FCC</button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Mode</label>
                <div class="col-sm-3">
                    <button type="button" onclick="sendZigate('SetMode', 'Normal')">Normal</button>
                    <button type="button" onclick="sendZigate('SetMode', 'Raw')">Raw</button>
                    <button type="button" onclick="sendZigate('SetMode', 'Hybride')">Hybride</button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">PDM</label>
                <div class="col-sm-3">
                    <button type="button" onclick="sendZigate('ErasePersistantDatas', '')" title="{{Efface la PDM. Tous les équipements devront être réinclus}}">Effacer</button>
                </div>
            </div>
            <!-- </fieldset>
        </form> -->
        <hr>

        <?php
            } // End zigate case

            if (($eqLogic->getConfiguration('battery_type', '') != "")) {
                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Equipement sur piles.}}</label>';
                echo '</div>';

                echo '<div class="form-group" >';
                echo '<label class="col-sm-3 control-label" >{{Type de piles}}</label>';
                echo '<div class="col-sm-3">';
                echo '<input id="CeciEstImportant" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_type" placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<hr>';
            }

            // Tcharp38: Is 'telecommande' really stored in config today ?
            // if (($eqLogic->getConfiguration('paramType', 'notDefined') == "telecommande") || ($eqLogic->getConfiguration('paramType', 'notDefined') == "notDefined"))  {

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Telecommande}}</label>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Groupe}}</label>';
                echo '<div class="col-sm-3">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Groupe" placeholder="{{Adresse courte en hex sur 4 digits, ex:AE12}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{on time (s)}}</label>';
                echo '<div class="col-sm-3">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="onTime" placeholder="{{Durée en secondes}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<hr>';
            //}

            // Tcharp38: Is 'paramABC' really stored in config today ?
            //if ( ($eqLogic->getConfiguration('paramType', 'notDefined') == "paramABC") || ($eqLogic->getConfiguration('paramType', 'notDefined') == "notDefined") )  {

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Calibration (y=ax2+bx+c)}}</label>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre A}}</label>';
                echo '<div class="col-sm-3">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramA" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre B}}</label>';
                echo '<div class="col-sm-3">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramB" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre C}}</label>';
                echo '<div class="col-sm-3">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramC" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';
            //}
        ?>

</form>
