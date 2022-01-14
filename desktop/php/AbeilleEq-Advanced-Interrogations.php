<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Advanced.php' -->

<hr>

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<div class="form-group">
    <div class="col-sm-3">
    </div>
    <h3 class="col-sm-5" style="text-align:left">{{Interrogation de l'équipement. Sortie dans 'AbeilleParser.log'}}</h3>
</div>

<!-- Zigbee standard commands -->
<div class="form-group">
    <label class="col-sm-3 control-label" title="getRoutingTable (Mgmt_Rtg_req)">Table de routage</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getRoutingTable\', \''.$eqId.'\')">{{Interroger}}</a>';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getBindingTable (Mgmt_Bind_req)">Table de binding</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getBindingTable\', \''.$eqId.'\')">{{Interroger}}</a>';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNeighborTable (Mgmt_Lqi_req)">Table de voisinage</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getNeighborTable\', \''.$eqId.'\')">{{Interroger}}</a>';
        ?>
        <input id="idStartIdx" title="{{Start index (ex: 00)}}" value="00" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getActiveEndPoints">Liste des 'end points'</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getActiveEndPoints\', \''.$eqId.'\')">{{Interroger}}</a>';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getIeeeAddress (IEEE_addr_req)">Adresse IEEE</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getIeeeAddress\', \''.$eqId.'\')">{{Interroger}}</a>';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Bind cet équipement vers un autre (Bind_req)">Bind to device</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'bindToDevice\', \''.$eqId.'\')">{{Bind}}</a>';
            addEpButton("idEpE", $mainEP);
            addClusterButton("idClustIdE");
            // <input id="idIeeeE" title="{{Adresse IEEE de destination (ex: 5C0272FFFE2857A3)}}" />
            echo ' TO';
            addIeeeListButton("idIeeeE");
        ?>
        <?php addEpButton("idEpE2", $mainEP); ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Bind cet équipement vers un groupe (Bind_req)">Bind to group</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'bindToGroup\', \''.$eqId.'\')">{{Bind}}</a>';
            addEpButton("idEpF", $mainEP);
            addClusterButton("idClustIdF");
            echo ' TO';
        ?>
        <input id="idGroupF" title="{{Adresse du groupe de destination (ex: 0001)}}" />
    </div>
</div>

<!-- ZCL commands -->
<br>
<div class="form-group">
    <label class="col-sm-3 control-label">ZCL: Lecture attribut</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'readAttribute\', \''.$eqId.'\')">{{Lire}}</a>';
            // echo '<input id="idEpA" title="{{End Point (ex: 01)}}" value="'.$mainEP.'"/>';
            addEpButton("idEpA", $mainEP);
            addClusterButton("idClustIdA");
            addAttrInput("idAttrIdA");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="writeAttribute()">ZCL: Ecriture attribut</label>
    <div class="col-sm-9">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute\', \''.$eqId.'\')">{{Ecrire}}</a>';
            addEpButton("idEpWA", $mainEP);
            addClusterButton("idClustIdWA");
            addDirInput("idDirWA");
            addManufIdInput("idManufIdWA");
            addAttrInput("idAttrIdWA");
        ?>
        <input id="idAttrTypeWA" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" />
        <input id="idValueWA" title="{{Valeur à écrire. Format hex string}}"  placeholder="{{Data}}" />
    </div>
</div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="writeAttribute0530()">ZCL: Ecriture attribut via 0530</label>
            <div class="col-sm-9">
                <?php
                    echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute0530\', \''.$eqId.'\')">{{Ecrire}}</a>';
                    addEpButton("idEpWA2", $mainEP);
                    addClusterButton("idClustIdWA2");
                    addDirInput("idDirWA2");
                    addAttrInput("idAttrIdWA2");
                ?>
                <input id="idAttrTypeWA2" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" />
                <input id="idValueWA2" title="{{Valeur à écrire. Format hex string}}"  placeholder="{{Data}}" />
            </div>
        </div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="readReportingConfig">ZCL: Lecture configuration de reporting</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'readReportingConfig\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpButton("idEp", $mainEP);
            addClusterButton("idClustId");
            addAttrInput("idAttrId");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverCommandsReceived">ZCL: Découverte des commandes RX</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'discoverCommandsReceived\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpButton("idEpB", $mainEP);
            addClusterButton("idClustIdB");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributes">ZCL: Découverte des attributs</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributes\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpButton("idEpD", $mainEP);
            addClusterButton("idClustIdD");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributesExt">ZCL: Découverte des attributs (extended)</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributesExt\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpButton("idEpC", $mainEP);
            addClusterButton("idClustIdC");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Configure le reporting d'un attribut">ZCL: Configurer le reporting</label>
    <div class="col-sm-9">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'configureReporting\', \''.$eqId.'\')">{{Configurer}}</a>';
            addEpButton("idEpCR", $mainEP);
            addClusterButton("idClustIdCR");
            addAttrInput("idAttrIdCR");
        ?>
        <input id="idMinCR" title="{{Interval min. Format hex string 4 car}}" placeholder="{{Min}}" style="width:60px" />
        <input id="idMaxCR" title="{{Interval max. Format hex string 4 car}}" placeholder="{{Max}}" style="width:60px" />
        <input id="idChangeCR" title="{{Change. Format hex string dépendant de l'attribut}}" placeholder="{{Change}}" />
    </div>
</div>

<br>
<!-- ZCL cluster specific commands -->
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0000, commande ResetToFactory">ZCL: 0000 - Reset aux valeurs d'usine</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0000-ResetToFactory\', \''.$eqId.'\')">{{Reset}}</a>';
            addEpButton("idEpG", $mainEP);
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0201, Setpoint Raise/Lower (cmd 00)">ZCL: 0201 - Raise/lower</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0201-SetPoint\', \''.$eqId.'\')">{{Modifier}}</a>';
            addEpButton("idEpC0201-00", $mainEP);
        ?>
        <input id="idAmountC0201-00" title="{{Step: format hex 2 car}}" placeholder="{{Amount}}" style="width:60px" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get group identifiers (cmd 41)">ZCL: 1000 - Groupes</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'1000-GetGroups\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpButton("idEpC1000-41", $mainEP);
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get endpont list (cmd 42)">ZCL: 1000 - End points</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'1000-GetEndpoints\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpButton("idEpC1000-42", $mainEP);
        ?>
    </div>
</div>
