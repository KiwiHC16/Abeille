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
        <h3 class="col-sm-5" style="text-align:left">{{Général}}</h3>
    </div>
    <hr>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Dernière comm.}}</label>
        <div class="col-sm-5">
            <?php
                $res = getCmdResult($eqId, 'Time-Time');
                echo '<span>'.$res.'</span>';
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Id Jeedom}}</label>
        <div class="col-sm-5">
            <!-- 'eqLogicAttr' with data-l1key="id" must not be declared twice in same page -->
            <?php echo '<span>'.$eqId.'</span>'; ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Id logique}}</label>
        <div class="col-sm-5">
            <span class="eqLogicAttr" data-l1key="logicalId"></span>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Adresse (courte/IEEE)}}</label>
        <div class="col-sm-5">
            <span> <?php echo $eqAddr; ?> </span>
            /
            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="IEEE"></span>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Documentation}}</label>
        <div class="col-sm-5">
            <?php
                $model = $eqLogic->getConfiguration('modeleJson', '');
                if ($model != '') {
                    echo '<a href="'.urlProducts.'/'.$model.'" target="_blank">Voir ici si présente</a>';
                }
            ?>
        </div>
    </div>

    <?php
        // If eq is a zigate (short addr=0000 or Ruche), display special parameters
        if ($eqAddr == "0000") {
    ?>
            <hr>

            <div class="form-group">
                <div class="col-sm-3"></div>
                <h3 class="col-sm-5" style="text-align:left">{{Zigate}}</h3>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Type</label>
                <div class="col-sm-5">
                    <?php
                        echo '<input class="form-control" value="'.$zgType.'" title="{{Type de Zigate}}" />';
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Status réseau</label>
                <div class="col-sm-5">
                    <?php
                        $res = getCmdResult($eqId, 'Network-Status');
                        echo '<span>'.$res.'</span>';
                    ?>
                    <a class="btn btn-warning" onclick="sendZigate('StartNetwork', '')">Démarrer</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Firmware</label>
                <div class="col-sm-5">
                    <?php
                        $res = getCmdResult($eqId, 'SW-SDK');
                        echo '<span>'.$res.'</span>';
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">PAN ID</label>
                <div class="col-sm-5">
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
                <div class="col-sm-5">
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
                <div class="col-sm-5">
                    <?php
                        $res = getCmdResult($eqId, 'Network-Channel');
                        echo '<span title="{{Canal actuel}}">'.$res.'</span>';
                    ?>
                    <input type="text" id="idChannelMask" placeholder="ex: 07FFF800" title="{{Masque des canaux autorisés (en hexa, 1 bit par canal, 800=canal 11, 07FFF800=tous les canaux de 11 à 26)}}" style="margin-left:10px; width:100px">
                    <a class="btn btn-warning" onclick="sendZigate('SetChannelMask', '')">Modifier</a>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Mode inclusion</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-5">
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
                <div class="col-sm-5">
                    <?php
                        $res = getCmdResult($eqId, 'ZiGate-Time');
                        echo '<span>'.$res.'</span>';
                    ?>
                    <a class="btn btn-warning" onclick="sendZigate('GetTime', '')">Lire</a>
                    <a class="btn btn-warning" onclick="sendZigate('SetTime', '')">Mettre à l'heure</a>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">TX power</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-5">
                    <input type="text" name="TxPowerValue" placeholder="XX">
                    <a class="btn btn-warning" onclick="sendZigate('SetTXPower', '')">Modifier</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">LED</label>
                <div class="col-sm-5">
                    <a class="btn btn-warning" onclick="sendZigate('SetLED', 'ON')">ON</a>
                    <a class="btn btn-warning" onclick="sendZigate('SetLED', 'OFF')">OFF</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Certification</label>
                <div class="col-sm-5">
                    <a class="btn btn-warning" onclick="sendZigate('SetCertif', 'CE')">CE</a>
                    <a class="btn btn-warning" onclick="sendZigate('SetCertif', 'FCC')">FCC</a>
                </div>
            </div>

            <?php
                if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">Mode</label>';
                    echo '<div class="col-sm-5">';
                    echo '<a class="btn btn-warning" onclick="sendZigate(\'SetMode\', \'Normal\')">Normal</a>';
                    echo '<a class="btn btn-warning" onclick="sendZigate(\'SetMode\', \'Raw\')">Raw</a>';
                    echo '<a class="btn btn-warning" onclick="sendZigate(\'SetMode\', \'Hybride\')">Hybride</a>';
                    echo '</div>';
                    echo '</div>';
                }
            ?>

            <div class="form-group">
                <label class="col-sm-3 control-label">PDM</label>
                <div class="col-sm-5">
                    <a class="btn btn-warning" onclick="sendZigate('ErasePersistantDatas', '')" title="{{Efface la PDM. Tous les équipements devront être réinclus}}">Effacer</a>
                </div>
            </div>

            <?php
                if ($zgType == "PI") { ?>
                    <div class="form-group">
                    <label class="col-sm-3 control-label">Reset HW</label>
                    <div class="col-sm-5">
                        <a class="btn btn-warning" onclick="resetPiZigate()" title="{{Reset HW de la PiZigate}}"><i class="fas fa-sync"></i> {{Reset}}</a>
                    </div>
                </div>
            <?php } ?>
            <!-- </fieldset>
        </form> -->
        <hr>

        <?php
            } // End zigate case

            /* If battery powered eq. 'battery_type' is defined in original JSON file */
            if ($eqLogic->getConfiguration('battery_type', '') != "") {
                echo '<hr>';

                echo '<div class="form-group">';
                echo '<div class="col-sm-3"></div>';
                echo '<h3 class="col-sm-5" style="text-align:left">{{Equipement sur piles}}</h3>';
                echo '</div>';

                echo '<div class="form-group" >';
                echo '<label class="col-sm-3 control-label" >{{Type de piles}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input id="CeciEstImportant" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_type" placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>';
                echo '</div>';
                echo '</div>';
            }

            /* If eq is a remote control. 'paramType' is defined in original JSON file */
            if ($eqLogic->getConfiguration('paramType', '') == "telecommande") {
                echo '<hr>';

                echo '<div class="form-group">';
                echo '<div class="col-sm-3"></div>';
                echo '<h3 class="col-sm-5" style="text-align:center">{{Télécommande}}</h3>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Adresse groupe}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Groupe" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Durée (s)}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="onTime" placeholder="{{Durée en secondes}}"/>';
                echo '</div>';
                echo '</div>';
            }

            /* If eq is a Innr Tele. 'paramType' is defined in original JSON file */
            if ($eqLogic->getConfiguration('paramType', '') == "telecommande7groups") {
?>
                <hr>

                <div class="form-group">
                    <div class="col-sm-3">
                    </div>
                    <h3 class="col-sm-5" style="text-align:left">{{Télécommande}}</h3>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe Tous}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP1" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                        <label class="col-sm-3 control-label">{{Adresse groupe 1}}</label>
                        <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP3" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                        </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 2}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP4" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 3}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP5" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 4}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP6" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 5}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP7" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 6}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP8" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>
<?php
            }

            /* If eq is ?. 'paramType' is defined in original JSON file */
            if ($eqLogic->getConfiguration('paramType', '') == "paramABC") {
                echo '<hr>';

                echo '<div class="form-group">';
                echo '<div class="col-sm-3"></div>';
                echo '<h3 class="col-sm-5" style="text-align:left">{{Calibration (y=ax2+bx+c)}}</h3>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre A}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramA" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre B}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramB" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre C}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramC" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';
            }
        ?>

</form>
