<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Advanced.php' -->

<hr>

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<div class="form-group">
    <div class="col-sm-3"></div>
    <h3 class="col-sm-5" style="text-align:left">{{Zigate}}</h3>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Type</label>
    <div class="col-sm-5">
        <?php
            echo '<input class="form-control" value="'.$zgType.'" title="{{Type de Zigate}}" readonly />';
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Status réseau</label>
    <div class="col-sm-5">
        <?php
            $res = getCmdValueByName($eqId, 'Network Status');
            echo '<span>'.$res.'</span>';
            if (isset($dbgDeveloperMode))
                echo '<a class="btn btn-warning" onclick="sendZigate(\'startNetwork\', \'\')">Démarrer</a>';
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Firmware</label>
    <div class="col-sm-5">
        <?php
        $fwVersion = getCmdValueByLogicId($eqId, "SW-Application").'-'.getCmdValueByLogicId($eqId, "SW-SDK");
        // TODO: Currently updated only if SW-SDK change. Better to merge major & minor and have only 1 jeedom info.
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByLogicId($eqId, "SW-SDK").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
        echo '<span id="idFWVersion">'.$fwVersion.'</span>';
        ?>
            <script>
                <?php echo "jeedom.cmd.update['".getCmdIdByLogicId($eqId, "SW-SDK")."'] = function(_options){"; ?>
                    console.log("jeedom.cmd.update[SDK]");
                    // console.log(_options);
                    var element = document.getElementById('idFWVersion');
                    element.textContent = _options.display_value;
                }
            </script>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">PAN ID</label>
    <div class="col-sm-5">
        <?php
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "PAN ID").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<span id="idPanId">'.getCmdValueByName($eqId, 'PAN ID').'</span>';
        ?>
            <script>
                <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "PAN ID")."'] = function(_options){"; ?>
                    console.log("jeedom.cmd.update[PAN ID]");
                    var element = document.getElementById('idPanId');
                    element.textContent = _options.display_value;
                }
            </script>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">Extended PAN ID</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-5">
        <?php
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Ext PAN ID").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<span id="idExtPanId">'.getCmdValueByName($eqId, 'Ext PAN ID').'</span>';
        ?>
            <script>
                <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "Ext PAN ID")."'] = function(_options){"; ?>
                    console.log("jeedom.cmd.update[Ext PAN ID]");
                    var element = document.getElementById('idExtPanId');
                    element.textContent = _options.display_value;
                }
            </script>
        </div>
        <!-- TODO <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
        <button type="button" onclick="sendZigate('setExtPANId', '')">Modifier</button> -->
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
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Network Channel").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<span id="idChannel" title="{{Canal actuel}}">'.getCmdValueByName($eqId, 'Network Channel').'</span>';
        ?>
            <script>
                <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "Network Channel")."'] = function(_options){"; ?>
                    console.log("jeedom.cmd.update[Network Channel]");
                    var element = document.getElementById('idChannel');
                    element.textContent = _options.display_value;
                }
            </script>
            <input type="text" id="idChannelMask" placeholder="ex: 07FFF800" title="{{Masque des canaux autorisés (en hexa, 1 bit par canal, 800=canal 11, 07FFF800=tous les canaux de 11 à 26)}}" style="margin-left:10px; width:100px">
            <a class="btn btn-warning" onclick="sendZigate('setChannelMask', '')">Modifier</a>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">Mode inclusion</label>
    </div>
    <div class="col-sm-5">
        <?php
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Inclusion Status").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<span id="idInclusionMode">'.getCmdValueByName($eqId, 'Inclusion Status').'</span>';
            ?>
            <script>
                <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "Inclusion Status")."'] = function(_options){"; ?>
                    console.log("jeedom.cmd.update[Inclusion Status]");
                    var element = document.getElementById('idInclusionMode');
                    element.textContent = _options.display_value;
                }
            </script>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">Heure zigate</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-7">
        <?php
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "ZiGate-Time").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
        ?>
            <a class="btn btn-warning" onclick="sendZigate('getTime', '')">Lire</a>
            <span id="idZgTime">- ? -</span>
            <script>
                <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "ZiGate-Time")."'] = function(_options){"; ?>
                    console.log("jeedom.cmd.update[Zigate-Time]");
                    var element = document.getElementById('idZgTime');
                    element.textContent = _options.display_value;
                }
                // jeedom.cmd.update['233']({display_value:'#state#'});
            </script>
            <a class="btn btn-warning" onclick="sendZigate('setTime', '')">Mettre à l'heure</a>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">TX power</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="sendZigate('getTXPower', '')">Lire</a>
        <span id="idZgPower">- ? -</span>
        <script>
            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "ZiGate-Power")."'] = function(_options){"; ?>
                console.log("jeedom.cmd.update[ZiGate-Power]");
                var element = document.getElementById('idZgPower');
                element.textContent = _options.display_value;
            }
            // jeedom.cmd.update['233']({display_value:'#state#'});
        </script>
        <!-- <a class="btn btn-warning" onclick="sendZigate('setTXPower', '')">Modifier</a> -->
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">LED</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="sendZigate('setLED', 'ON')">ON</a>
        <a class="btn btn-warning" onclick="sendZigate('setLED', 'OFF')">OFF</a>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Certification</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="sendZigate('setCertif', 'CE')">CE</a>
        <a class="btn btn-warning" onclick="sendZigate('setCertif', 'FCC')">FCC</a>
    </div>
</div>

<?php if (isset($dbgDeveloperMode)) { ?>
    <div class="form-group">
        <label class="col-sm-3 control-label">Mode</label>
        <div class="col-sm-5">
            <a class="btn btn-danger" onclick="sendZigate('setMode', 'Normal')">Normal</a>
            <a class="btn btn-warning" onclick="sendZigate('setMode', 'Hybride')">Hybride</a>
            <a class="btn btn-danger" onclick="sendZigate('setMode', 'Raw')">Raw</a>
        </div>
    </div>
<?php } ?>

<div class="form-group">
    <label class="col-sm-3 control-label">PDM</label>
    <div class="col-sm-5">
        <a class="btn btn-danger" onclick="sendZigate('erasePersistantDatas', '')" title="{{Efface la PDM. Tous les équipements devront être réinclus}}">Effacer</a>
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

<div class="form-group">
    <label class="col-sm-3 control-label">Reset SW</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="sendZigate('resetZigate', '')" title="{{Reset SW (Commande 0011)}}"><i class="fas fa-sync"></i> {{Reset}}</a>
    </div>
</div>

<hr>