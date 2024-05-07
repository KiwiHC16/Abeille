<!-- This file displays advanced equipment/zigbee infos.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;

    function addZgButton($name, $class, $onclick, $onclick2 = "", $leftMargin = true, $title = "") {
        // echo '<a class="btn '.$class.'" style="width:80px" onclick="interrogate(\''.$interrogate.'\')">'.$name.'</a>';
        if ($leftMargin)
            $style = "margin-left:4px; width:80px";
        else
            $style = "width:80px";
        if ($title != "")
            $title = "title=\"${title}\"";
        echo '<a class="btn '.$class.'" style="'.$style.'" '.$title.' onclick="sendZigate(\''.$onclick.'\', \''.$onclick2.'\')">'.$name.'</a>';
    }

?>

<div class="form-group">
    <div class="col-sm-3"></div>
    <h3 class="col-sm-5" style="text-align:left">{{Zigate}}
        <?php
            addDocButton("Utilisation.html#mode-avance-pour-une-zigate");
        ?>
    </h3>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Type</label>
    <div class="col-sm-5">
        <input type="text" id="idZgType" value="" title="{{Type de Zigate}}" readonly />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Version du firmware}}</label>
    <div class="col-sm-5" advInfo="FW-Version">
        <input type="text" value="" readonly>
        <!-- <a class="btn btn-default" style="margin-left:4px" onclick="sendZigate('getZgVersion', '')">{{Lire}}</a> -->
        <?php addZgButton("{{Lire}}", "btn-default", 'getZgVersion'); ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Status réseau}}</label>
    <div class="col-sm-5" advInfo="Network-Status">
        <input type="text" value="" readonly>
        <?php
            if (isset($dbgDeveloperMode)) {
                // echo '<a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate(\'startNetwork\', \'\')">{{Démarrer}}</a>';
                addZgButton("{{Démarrer}}", "btn-warning", 'startNetwork');
            }
        ?>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">{{Canal Zigbee}}</label>
    </div>
    <div class="col-sm-5">
        <div advInfo="Network-Channel">
            <input type="text" id="idChannel" title="{{Canal actuel}}" value="YY" readonly>
            <select id="idZgChan" style="width:80px; margin-left:4px" title="{{Canal Zigbee choisi}}">
                <?php
                for ($i = 11; $i < 27; $i++) {
                    echo '<option value='.$i.'>'.$i.'</option>';
                }
                ?>
            </select>
            <!-- <a class="btn btn-danger" style="margin-left:4px" onclick="sendZigate('setChannel', '')">{{Appliquer}}</a> -->
            <?php addZgButton("{{Appliquer}}", "btn-danger", 'setChannel'); ?>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">{{Mode inclusion}}</label>
    </div>
    <div class="col-sm-5" advInfo="permitJoin-Status">
        <input type="text" id="idInclusionMode" value="" readonly>
        <!-- <a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate('setInclusion', 'start')">{{Démarrer}}</a> -->
        <?php addZgButton("{{Démarrer}}", "btn-warning", 'setInclusion', 'start'); ?>
        <!-- <a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate('setInclusion', 'stop')">{{Arrêter}}</a> -->
        <?php addZgButton("{{Arrêter}}", "btn-warning", 'setInclusion', 'stop'); ?>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">{{Heure Zigate}}</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-7" advInfo="ZiGate-Time">
        <input type="text" id="idZgTime" value="" readonly>
        <!-- <a class="btn btn-default" style="margin-left:4px" onclick="sendZigate('getTime', '')">{{Lire}}</a> -->
        <?php addZgButton("{{Lire}}", "btn-default", 'getTime', ''); ?>
        <!-- <a class="btn btn-warning" onclick="sendZigate('setTime', '')">{{Mettre à l'heure}}</a> -->
        <?php addZgButton("{{Régler}}", "btn-default", 'setTime', ''); ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">PAN ID</label>
    <div class="col-sm-5" advInfo="PAN-ID">
        <input type="text" id="idPanId" value="" readonly>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">Extended PAN ID</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-5" advInfo="Ext_PAN-ID">
        <input type="text" id="idExtPanId" value="" readonly>
        <!-- TODO <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
        <button type="button" onclick="sendZigate('setExtPANId', '')">Modifier</button> -->
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">TX power</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-5" advInfo="ZiGate-Power">
        <input type="text" value="" readonly>
        <!-- <a class="btn btn-default" onclick="sendZigate('getTXPower', '')">{{Lire}}</a> -->
        <?php addZgButton("{{Lire}}", "btn-default", 'getTXPower', ''); ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">LED</label>
    <div class="col-sm-5">
        <!-- <a class="btn btn-default" onclick="sendZigate('setLED', 'ON')">ON</a> -->
        <?php addZgButton("{{ON}}", "btn-default", 'setLED', 'ON', false); ?>
        <!-- <a class="btn btn-default" onclick="sendZigate('setLED', 'OFF')">OFF</a> -->
        <?php addZgButton("{{OFF}}", "btn-default", 'setLED', 'OFF'); ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">Certification</label>
    <div class="col-sm-5">
        <!-- <a class="btn btn-warning" onclick="sendZigate('setCertif', 'CE')">CE</a> -->
        <?php addZgButton("{{CE}}", "btn-warning", 'setCertif', 'CE', false); ?>
        <!-- <a class="btn btn-warning" onclick="sendZigate('setCertif', 'FCC')">FCC</a> -->
        <?php addZgButton("{{FCC}}", "btn-warning", 'setCertif', 'FCC'); ?>
    </div>
</div>

<style>
    .disabled-a {
        pointer-events: none;
    }
</style>

<?php if (isset($dbgDeveloperMode)) { ?>
    <div class="form-group">
        <label class="col-sm-3 control-label">DEV MODE: : {{Mode}}</label>
        <div class="col-sm-5">
            <a class="btn btn-danger disabled-a"  style="width:80px" onclick="sendZigate('setMode', 'Normal')">{{Normal}}</a>
            <a class="btn btn-danger" style="width:80px; margin-left:4px" onclick="sendZigate('setMode', 'Hybride')">{{Hybride}}</a>
            <a class="btn btn-default" style="width:80px; margin-left:4px" onclick="sendZigate('setMode', 'Raw')">{{Brut}}</a>
        </div>
    </div>
<?php } ?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Reset SW}}</label>
    <div class="col-sm-5">
        <!-- <a class="btn btn-warning" onclick="sendZigate('resetZigate', '')" title="{{Reset SW (Commande 0011)}}">{{Reset}}</a> -->
        <?php addZgButton("{{Reset}}", "btn-warning", 'resetZigate', '', false, "{{Reset SW (Commande 0011)}}"); ?>
    </div>
</div>

<!-- HW reset => Must be visible only if type PI v1/v2 -->
<div class="form-group" id="idAdvResetHw" style="display:none;">
    <label class="col-sm-3 control-label">{{Reset HW}}</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" style="width:80px" onclick="resetPiZigate()" title="{{Reset HW de la PiZigate}}">{{Reset}}</a>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Effacement PDM}}</label>
    <div class="col-sm-5">
        <a class="btn btn-danger" style="width:80px" onclick="sendZigate('zgErasePdm', '')" title="{{Efface la PDM. Tous les équipements devront être réinclus}}">{{Effacer}}</a>
    </div>
</div>

<!-- PDM dump => Only with Abeille firmwares (version 'ABxxyyyy') and currently in dev mode -->
<?php if (isset($dbgDeveloperMode)) { ?>
    <div class="form-group">
        <label class="col-sm-3 control-label">DEV MODE: {{Sauvegarde PDM}}</label>
        <div class="col-sm-5">
            <a class="btn btn-default" style="width:80px" onclick="sendZigate('zgDumpPdm', '')" title="{{Sauvegarde le contenu PDM de la Zigate}}">{{Sauver}}</a>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">DEV MODE: {{Restoration PDM}}</label>
        <div class="col-sm-5">
            <a class="btn btn-danger" style="width:80px" onclick="sendZigate('zgRestorePdm', '')" title="{{Restore le contenu PDM de la Zigate}}">{{Restorer}}</a>
        </div>
    </div>
<?php } ?>

<hr>