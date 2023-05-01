<!-- This file displays advanced equipment/zigbee infos.
     Included by 'Abeille-Eq-Advanced.php' -->

<hr>

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;
?>

<div class="form-group">
    <div class="col-sm-3">
    </div>
    <h3 class="col-sm-9" style="text-align:left">{{Interrogation de l'équipement.}}</h3>
    <div class="col-sm-3">
    </div>
    <h4 class="col-sm-9" style="text-align:left">{{Sortie dans 'AbeilleParser.log' si mode 'debug' actif.}}</h4>
</div>

<!-- Zigbee standard commands -->
<div class="form-group">
    <label class="col-sm-3 control-label" title="getRoutingTable (Mgmt_Rtg_req)">{{Table de routage}}</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getRoutingTable\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getRoutingTable");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getBindingTable (Mgmt_Bind_req)">Table de binding</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getBindingTable\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getBindingTable");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNeighborTable (Mgmt_Lqi_req)">Table de voisinage</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getNeighborTable\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getNeighborTable");
        ?>
        <input id="idStartIdx" title="{{Start index (ex: 00)}}" value="00" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getActiveEndPoints">Liste des 'end points'</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getActiveEndPoints\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getActiveEndPoints");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getSimpleDescriptor (Simple_Desc_req)">Simple descriptor request</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getSimpleDescriptor\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getSimpleDescriptor");
            addEpInput("idEpSDR");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNodeDescriptor (Node_Desc_req)">{{Descripteur de noeud}}</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getNodeDescriptor\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getNodeDescriptor");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getIeeeAddress (IEEE_addr_req)">Adresse IEEE</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'getIeeeAddress\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "getIeeeAddress");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Mgmt_NWK_Update_req">Mgmt network update request</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'mgmtNetworkUpdateReq\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "mgmtNetworkUpdateReq");
        ?>
        <input id="idMgmtNwkUpdReqSC" title="{{Scanned channels mask (ex: all=07FFF800)}}" />
        <input id="idMgmtNwkUpdReqSD" title="{{Scan duration (01-05, FE=channel change, or FF)}}" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Bind cet équipement vers un autre (Bind_req)">Bind to device</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'bindToDevice\')">{{Bind}}</a>';
            addButton("{{Bind}}", "btn-danger", "bindToDevice");
            addEpInput("idEpE");
            addClusterButton("idClustIdE");
            // <input id="idIeeeE" title="{{Adresse IEEE de destination (ex: 5C0272FFFE2857A3)}}" />
            echo ' TO';
            addIeeeListButton("idIeeeE", true);
            addEpInput("idEpE2");
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Unbind to device (Unbind_req)">Remove binding to device</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'unbindToDevice\')">{{Unbind}}</a>';
            addButton("{{Unbind}}", "btn-danger", "unbindToDevice");
            addEpInput("idEpSrc-UBD");
            addClusterButton("idClustId-UBD");
            echo ' TO';
            addIeeeListButton("idAddr-UBD", true);
            addEpInput("idEpDst-UBD");
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Bind cet équipement vers un groupe (Bind_req)">Bind to group</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'bindToGroup\')">{{Bind}}</a>';
            addButton("{{Bind}}", "btn-danger", "bindToGroup");
            addEpInput("idEpF");
            addClusterButton("idClustIdF");
            echo ' TO';
            addGroupInput('idGroupF', 'Destination group');
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Unbind to group (Unbind_req)">Remove binding to group</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Unbind}}", "btn-danger", "unbindToGroup");
            // echo '<a class="btn btn-danger" onclick="interrogate(\'unbindToGroup\')">{{Unbind}}</a>';
            addEpInput("idEpSrc-UBG");
            addClusterButton("idClustId-UBG");
            echo ' TO';
            addGroupInput('idGroup-UBG', 'Destination group');
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Leave request">Leave request</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'leaveRequest\')">{{Leave}}</a>';
            addButton("{{Leave}}", "btn-danger", "leaveRequest");
            // addIeeeListButton("idIeeeLR");
            // addClusterButton("idClustIdF");
            // echo ' TO';
        ?>
        <!-- <input id="idGroupF" title="{{Adresse du groupe de destination (ex: 0001)}}" /> -->
    </div>
</div>

<!-- ZCL commands -->
<br>
<div class="form-group">
    <label class="col-sm-3 control-label">ZCL: Lecture attribut</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'readAttribute\')">{{Lire}}</a>';
            addButton("{{Lire}}", "btn-warning", "readAttribute");
            // echo '<input id="idEpA" title="{{End Point (ex: 01)}}" value="'.$mainEP.'"/>';
            addEpInput("idEpA");
            addClusterButton("idClustIdA");
            addAttrInput("idAttrIdA");
            addManufCodeInput("idManufIdRA"); // Optional
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="writeAttribute()">ZCL: Ecriture attribut</label>
    <div class="col-sm-9">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute\')">{{Ecrire}}</a>';
            addButton("{{Ecrire}}", "btn-danger", "writeAttribute");
            addEpInput("idEpWA");
            addClusterButton("idClustIdWA");
            addAttrInput("idAttrIdWA");
            addTypeList("idAttrTypeWA");
        ?>
        <input id="idValueWA" title="{{Valeur à écrire. Format hex string}}"  placeholder="{{Data}}" />
        <?php
            addDirInput("idDirWA");
            addManufCodeInput("idManufIdWA");
        ?>
        <!-- <input id="idAttrTypeWA" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" /> -->
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="writeAttribute0530()">ZCL: Ecriture attribut via 0530</label>
    <div class="col-sm-9">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute0530\')">{{Ecrire}}</a>';
            addButton("{{Ecrire}}", "btn-danger", "writeAttribute0530");
            addEpInput("idEpWA2");
            addClusterButton("idClustIdWA2");
            addDirInput("idDirWA2");
            addAttrInput("idAttrIdWA2");
            addTypeList("idAttrTypeWA2");
        ?>
        <!-- <input id="idAttrTypeWA2" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" /> -->
        <input id="idValueWA2" title="{{Valeur à écrire. Format hex string}}"  placeholder="{{Data}}" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="readReportingConfig">ZCL: Lecture configuration de reporting</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'readReportingConfig\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "readReportingConfig");
            addEpInput("idEp");
            addClusterButton("idClustId");
            addAttrInput("idAttrId");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverCommandsReceived">ZCL: Découverte des commandes RX</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'discoverCommandsReceived\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "discoverCommandsReceived");
            addEpInput("idEpB");
            addClusterButton("idClustIdB");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributes">ZCL: Découverte des attributs</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributes\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "discoverAttributes");
            addEpInput("idEpD");
            addClusterButton("idClustIdD");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributesExt">ZCL: Découverte des attributs (extended)</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributesExt\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "discoverAttributesExt");
            addEpInput("idEpC");
            addClusterButton("idClustIdC");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Configure le reporting d'un attribut">ZCL: Configurer le reporting</label>
    <div class="col-sm-9">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'configureReporting\')">{{Configurer}}</a>';
            addButton("{{Configurer}}", "btn-danger", "configureReporting");
            addEpInput("idEpCR");
            addClusterButton("idClustIdCR");
            addAttrInput("idAttrIdCR");
            addTypeList("idAttrTypeCR");
        ?>
        <input id="idManufIdCR" title="{{Code fabricant. Format hex string 4 car (ex: 1241)}}" placeholder="{{Manuf code (ex: 1241)}}" />
        <!-- <input id="idAttrTypeCR" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" /> -->
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
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0000-ResetToFactory\')">{{Reset}}</a>';
            addButton("{{Reset}}", "btn-danger", "0000-ResetToFactory");
            addEpInput("idEpG");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/addGroup">ZCL: 0004 - Add group</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0004-AddGroup\')">{{Add}}</a>';
            addButton("{{Add}}", "btn-danger", "0004-AddGroup");
            addEpInput("idEp-AG");
            addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/Get group membership">ZCL: 0004 - Get group membership</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0004-GetGroupMembership\')">{{Get}}</a>';
            addButton("{{Get}}", "btn-danger", "0004-GetGroupMembership");
            addEpInput("idEp-GGM");
            // addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/Remove all groups">ZCL: 0004 - Remove all groups</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0004-RemoveAllGroups\')">{{Remove}}</a>';
            addButton("{{Remove}}", "btn-danger", "0004-RemoveAllGroups");
            addEpInput("idEp-RAG");
            // addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0201, Setpoint Raise/Lower (cmd 00)">ZCL: 0201 - Raise/lower</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0201-SetPoint\')">{{Modifier}}</a>';
            addButton("{{Modifier}}", "btn-danger", "0201-SetPoint");
            addEpInput("idEpC0201-00");
        ?>
        <input id="idAmountC0201-00" title="{{Step: format hex 2 car}}" placeholder="{{Amount}}" style="width:60px" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0300, Mote to color (cmd 07)">ZCL: 0300 - Move to color</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0300-MoveToColor\')">{{Appliquer}}</a>';
            addButton("{{Appliquer}}", "btn-danger", "0300-MoveToColor");
            addEpInput("idEp-MTC");
        ?>
        <input id="idX-MTC" title="{{X: format hex 4 car}}" placeholder="{{X}}" style="width:60px" />
        <input id="idY-MTC" title="{{Y: format hex 4 car}}" placeholder="{{Y}}" style="width:60px" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0502/IAS WD, Start warning (cmd 00)">ZCL: 0502 - Start warning</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0502-StartWarning\')">{{Appliquer}}</a>';
            addButton("{{Appliquer}}", "btn-danger", "0502-StartWarning");
            addEpInput("idEp-SW");
            addWarningModesList("idMode-SW");
            addCheckbox("idStrobe-SW", "Strobe");
            addSirenLevelList("idSirenL-SW");
            addInput("idDuration-SW", "Duration in sec", "Duration");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get group identifiers (cmd 41)">ZCL: 1000 - Groupes</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'1000-GetGroups\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "1000-GetGroups");
            addEpInput("idEpC1000-41");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get endpont list (cmd 42)">ZCL: 1000 - End points</label>
    <div class="col-sm-5">
        <?php
            // echo '<a class="btn btn-warning" onclick="interrogate(\'1000-GetEndpoints\')">{{Interroger}}</a>';
            addButton("{{Interroger}}", "btn-warning", "1000-GetEndpoints");
            addEpInput("idEpC1000-42");
        ?>
    </div>
</div>
