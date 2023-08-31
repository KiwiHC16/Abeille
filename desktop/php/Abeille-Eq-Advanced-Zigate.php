<!-- This file displays advanced equipment/zigbee infos.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
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
        <a class="btn btn-default" style="margin-left:4px" onclick="sendZigate('getZgVersion', '')">{{Lire}}</a>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Status réseau}}</label>
    <div class="col-sm-5" advInfo="Network-Status">
        <input type="text" value="" readonly>
        <?php
            // $res = getCmdValueByLogicId($eqId, 'Network-Status');
            // echo '<input type="text" value="'.$res.'" readonly>';
            if (isset($dbgDeveloperMode)) {
                echo '<a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate(\'startNetwork\', \'\')">{{Démarrer}}</a>';
                // echo '<a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate(\'startNetworkScan\', \'\')">{{Démarrer scan}}</a>';
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
            <a class="btn btn-danger" style="margin-left:4px" onclick="sendZigate('setChannel', '')">{{Appliquer}}</a>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">{{Mode inclusion}}</label>
    </div>
    <div class="col-sm-5" advInfo="permitJoin-Status">
        <input type="text" id="idInclusionMode" value="" readonly>
        <a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate('setInclusion', 'start')">{{Démarrer}}</a>
        <a class="btn btn-warning" style="margin-left:4px" onclick="sendZigate('setInclusion', 'stop')">{{Arrêter}}</a>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-3 control-label">
        <label class="">{{Heure Zigate}}</label>
        <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
    </div>
    <div class="col-sm-7" advInfo="ZiGate-Time">
        <input type="text" id="idZgTime" value="" readonly>
        <a class="btn btn-default" style="margin-left:4px" onclick="sendZigate('getTime', '')">{{Lire}}</a>
        <a class="btn btn-warning" onclick="sendZigate('setTime', '')">{{Mettre à l'heure}}</a>
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
        <a class="btn btn-warning" onclick="sendZigate('getTXPower', '')">{{Lire}}</a>
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
        <a class="btn btn-danger" onclick="sendZigate('erasePersistantDatas', '')" title="{{Efface la PDM. Tous les équipements devront être réinclus}}">{{Effacer}}</a>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Reset SW}}</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="sendZigate('resetZigate', '')" title="{{Reset SW (Commande 0011)}}"><i class="fas fa-sync"></i> {{Reset}}</a>
    </div>
</div>

<!-- HW reset => Must be visible only if type PI v1/v2 -->
<div class="form-group" id="idAdvResetHw" style="display:none;">
    <label class="col-sm-3 control-label">{{Reset HW}}</label>
    <div class="col-sm-5">
        <a class="btn btn-warning" onclick="resetPiZigate()" title="{{Reset HW de la PiZigate}}"><i class="fas fa-sync"></i> {{Reset}}</a>
    </div>
</div>

<!-- PDM dump => Only with Abeille firmwares (version 'ABxxyyyy') and currently in dev mode -->
<?php if (isset($dbgDeveloperMode)) { ?>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Sauvegarde PDM}}</label>
        <div class="col-sm-5">
            <a class="btn btn-default" onclick="sendZigate('zgDumpPdm', '')" title="{{Sauvegarde le contenu PDM de la Zigate}}"><i class="fas fa-sync"></i> {{Sauver}}</a>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Restoration PDM}}</label>
        <div class="col-sm-5">
            <a class="btn btn-danger" onclick="sendZigate('zgRestorePdm', '')" title="{{Restore le contenu PDM de la Zigate}}"><i class="fas fa-sync"></i> {{Restorer}}</a>
        </div>
    </div>
<?php } ?>

<hr>