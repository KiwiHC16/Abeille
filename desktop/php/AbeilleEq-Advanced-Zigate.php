<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group">
    <div class="col-sm-3"></div>
    <h3 class="col-sm-5" style="text-align:left">{{Zigate}}</h3>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Type</label>
    <div class="col-sm-5">
        <?php
            echo '<input type="text" value="'.$zgType.'" title="{{Type de Zigate}}" readonly />';
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
            echo '<input type="text" id="idFWVersion" value="'.$fwVersion.'" readonly>';
            echo "<script>";
            $spanId = 'idFWVersion';
            $cmdLogicId = 'SW-Application';
            echo "jeedom.cmd.update['".getCmdIdByLogicId($eqId, $cmdLogicId)."'] = function(_options) {";
                echo "console.log('jeedom.cmd.update[".$cmdLogicId."]');";
                // console.log(_options);
                echo "var element = document.getElementById('".$spanId."');";
                echo "minor = element.textContent.substr(5, 4);";
                echo "element.textContent = _options.display_value+minor;";
            echo "};";
            $cmdLogicId = 'SW-SDK';
            echo "jeedom.cmd.update['".getCmdIdByLogicId($eqId, $cmdLogicId)."'] = function(_options) {";
                echo "console.log('jeedom.cmd.update[".$cmdLogicId."]');";
                // console.log(_options);
                echo "var element = document.getElementById('".$spanId."');";
                echo "major = element.textContent.substr(0, 5);";
                echo "element.textContent = major+_options.display_value;";
            echo "};";
            echo "</script>";
        echo '</div>';
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Status réseau</label>
    <div class="col-sm-5">
        <?php
            $res = getCmdValueByName($eqId, 'Network Status');
            echo '<input type="text" value="'.$res.'" readonly>';
            if (isset($dbgDeveloperMode))
                echo '<a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate(\'startNetwork\', \'\')">Démarrer</a>';
        ?>
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
            echo '<input type="text" id="idChannel" title="{{Canal actuel}}" value="'.getCmdValueByName($eqId, 'Network Channel').'" readonly>';
            addJsUpdateFunction($eqId, 'Network-Channel', 'idChannel');
        ?>
            <!-- <input type="text" id="idChannelMask" placeholder="ex: 07FFF800" title="{{Masque des canaux autorisés (en hexa, 1 bit par canal, 800=canal 11, 07FFF800=tous les canaux de 11 à 26)}}" style="margin-left:10px; width:100px">
            <a class="btn btn-warning" onclick="sendZigate('setChannelMask', '')">Modifier</a> -->
            <select id="idZgChan" style="width:80px; margin-left:4px" title="{{Canal Zigbee choisi}}" onchange="sendZigate('setChannel', '')">
                <option value=0>{{Auto}}</option>
                <?php
                for ($i = 11; $i < 27; $i++) {
                    echo '<option value='.$i.'>'.$i.'</option>';
                }
                ?>
            </select>
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
            echo '<input type="text" id="idInclusionMode" value="'.getCmdValueByName($eqId, 'Inclusion Status').'" readonly>';
            addJsUpdateFunction($eqId, 'permitJoin-Status', 'idInclusionMode');
            ?>
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
            <?php
            echo '<input type="text" id="idZgTime" value="" readonly>';
            addJsUpdateFunction($eqId, 'ZiGate-Time', 'idZgTime', true);
            ?>
            <a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate('getTime', '')">Lire</a>
            <a class="btn btn-warning" onclick="sendZigate('setTime', '')">Mettre à l'heure</a>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">PAN ID</label>
    <div class="col-sm-5">
        <?php
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "PAN ID").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<input type="text" id="idPanId" value="'.getCmdValueByName($eqId, 'PAN ID').'" readonly>';
            addJsUpdateFunction($eqId, 'PAN-ID', 'idPanId');
        echo '</div>';
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
        echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Ext PAN ID").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<input type="text" id="idExtPanId" value="'.getCmdValueByName($eqId, 'Ext PAN ID').'" readonly>';
            addJsUpdateFunction($eqId, 'Ext_PAN-ID', 'idExtPanId');
        ?>
        </div>
        <!-- TODO <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
        <button type="button" onclick="sendZigate('setExtPANId', '')">Modifier</button> -->
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">TX power</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="sendZigate('getTXPower', '')">Lire</a>
        <?php
        $txPower = getCmdValueByLogicId($eqId, "ZiGate-Power"); // Getting initial value is important !
        echo '<span id="idZgPower">'.$txPower.'</span>';
        addJsUpdateFunction($eqId, 'ZiGate-Power', 'idZgPower');
        ?>
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