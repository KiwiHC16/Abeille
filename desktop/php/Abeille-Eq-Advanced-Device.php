<!-- This file displays advanced equipment/zigbee infos.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Toujours en écoute}}</label>
    <div class="col-sm-5">
        <input id="idZbRxOnWhenIdle" readonly title="{{'Oui' si l'équipement est toujours en écoute}}" value="" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Code fabricant}} (ManufCode)</label>
    <div class="col-sm-5">
        <input id="idManufCode" readonly title="{{Code fabricant}}" value="" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Date fabrication}} (DateCode)</label>
    <div class="col-sm-5">
        <input type="text" id="idDateCode" title="{{Cluster 0000, attribut 0006/DateCode}}" value="" readonly>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Version SW}} (SWBuildID)</label>
    <div class="col-sm-5">
        <input type="text" id="idSwBuildId" title="{{Cluster 0000, attribut 4000/SWBuildID}}"value="" readonly>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Identifiant Zigbee}}</label>
    <div class="col-sm-5">
        <input id="idZbModel" readonly title="{{Identificant Zigbee du modèle}}" value="" />
        <input id="idZbManuf" readonly style="margin-left:4px;width:200px" title="{{Identificant Zigbee du fabricant}}" value="" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Modèle d'équipement}}</label>
    <div class="col-sm-9">
        <input id="idModelSig" readonly style="width:240px" title="{{Signature du modèle utilisé}}" value="" />
        <input id="idModelName" readonly style="width:240px" title="{{Nom du modèle utilisé}}" value="" />
        <input id="idModelSource" readonly style="width:80px" title="{{Source du modèle}}" value="" />
        <a class="btn btn-danger" id="idDelLocalBtn" title="{{Supprime la version locale du modèle}}" style="display:none"><i class="far fa-trash-alt"></i></a>
        <a class="btn btn-default" id="idUpdateBtn" style="margin-left:8px" title="{{Mise-à-jour à partir de son modèle et reconfiguration}}">{{Mise-à-jour}}</a>
        <a class="btn btn-danger" id="idReinitBtn" style="margin-left:8px" title="{{Réinitlialise les paramètres par défaut et reconfigure l'équipement comme s'il s'agissait d'une nouvelle inclusion}}">{{Réinitialiser}}</a>
        <a class="btn btn-danger" id="idModelChangeBtn" style="margin-left:8px" title="{{Utilisateurs avancés - Forcer un modèle particulier}}">{{Forcer modèle}}</a>

    </div>

    <!-- Forced model choice popup -->
    <div class="abeille-model-change-popup-content" style="display:none">
        <h3 style="margin-top:0">{{Choix du modèle d'équipement}}</h3>
        <p>{{Lors de l'ajout d'un équipement, Abeille identifie automatiquement le modèle à partir de sa signature Zigbee. L'équipement est ensuite automatiquement configuré.}}</p>
        <p>{{Toutefois, si le modèle identifié n'est pas le bon (rare), ou si votre équipement n'est pas encore connu d'Abeille, vous pouvez forcer ici l'utilisation d'un modèle de votre choix parmi tous les équipements connus.}}</p>

        <p class="alert alert-warning">{{Cette fonction est destinée aux utilisteurs avancés, ne l'utilisez que si vous comprenez parfaitement ce que vous faites.}}</p>

        <p>
            <label>{{Modèle actuel de votre équipement}}: </label>
            <br>
            <span class='current-model'>-- Aucun --</span>
        </p>

        <p>
            <label>{{Choisissez le modèle à appliquer}}: </label>
            <br>
            <input type="search" style="width:100%; box-sizing:border-box" list="abeille-all-models-list" placeholder="{{ Recherchez par fabricant, modèle, ou nom de fichier JSON }}">
        </p>

        <p style="text-align:center; margin-top:30px">
            <a class="btn btn-secondary">{{Annuler}}</a>
            <a class="btn btn-success" title="{{Enregistrer le modèle choisi et reconfigurer l'équipement}}">{{Appliquer le modèle}}</a>
        </p>
    </div>

    <!-- Abeille's models list if user wants to force a different one -->
    <datalist id="abeille-all-models-list"></datalist>
</div>


<div class="form-group">
    <label class="col-sm-3 control-label">{{Assistant de découverte}}</label>
    <div class="col-sm-5">
        <a class="btn btn-default" id="idEqAssistBtn">{{Ouvrir}}</a>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Etat de l'équipement}}</label>
    <div class="col-sm-5">
        <a class="btn btn-danger" id="idRepairBtn" title="{{Tente de corriger l'état interne de l'équipement}}">{{Réparer}}</a>
    </div>
</div>
