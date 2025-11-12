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
    <label class="col-sm-3 control-label" title="getRoutingTable (Mgmt_Rtg_req)">{{Routing table}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getRoutingTable");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getBindingTable (Mgmt_Bind_req)">{{Binding table}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getBindingTable");
            echo '<input id="idIdx-BT" title="{{Start index (ex: 00)}}" value="00" style="width:30px; margin-left:8px" />';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNeighborTable (Mgmt_Lqi_req)">{{Neighbor table}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getNeighborTable");
            echo '<input id="idStartIdx" title="{{Start index (ex: 00)}}" value="00" style="width:30px; margin-left:8px" />';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getActiveEndPoints">{{End points}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getActiveEndPoints");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getSimpleDescriptor (Simple_Desc_req)">{{Simple descriptor}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getSimpleDescriptor");
            addEpInput("idEpSDR");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNodeDescriptor (Node_Desc_req)">{{Node descriptor}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getNodeDescriptor");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getIeeeAddress (IEEE_addr_req)">{{IEEE address}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getIeeeAddress");
            echo '<select id="idMgmtIeeeReq-RT" style="width:90px; margin-left: 8px" title="{{Request type}}" />';
            echo '<option value="00" selected>Single dev</option>';
            echo '<option value="01">Extended</option>';
            echo '</select>';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNwkAddress (NWK_addr_req)">{{Network address}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "getNwkAddress");
            echo '<select id="idNwkAddrReq-RT" style="width:90px; margin-left: 8px" title="{{Request type}}" />';
            echo '<option value="00" selected>Single dev</option>';
            echo '<option value="01">Extended</option>';
            echo '</select>';
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Mgmt_NWK_Update_req">Mgmt network update request</label>
    <div class="col-sm-5">
        <?php
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
            addClusterList("idClustIdE");
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
            addClusterList("idClustId-UBD");
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
            addClusterList("idClustIdF");
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
            addClusterList("idClustId-UBG");
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
            // addClusterList("idClustIdF");
            // echo ' TO';
        ?>
        <!-- <input id="idGroupF" title="{{Adresse du groupe de destination (ex: 0001)}}" /> -->
    </div>
</div>

<!-- ZCL commands -->
<br>
<div class="form-group">
    <label class="col-sm-3 control-label">ZCL: {{Lecture attribut}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Lire}}", "btn-default", "readAttribute");
            addEpInput("idEpA");
            addClusterList("idClustIdA");
            addAttrInput("idAttrIdA");
            addManufCodeInput("idManufIdRA"); // Optional
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="writeAttribute()">ZCL: {{Ecriture attribut}}</label>
    <div class="col-sm-9">
        <?php
            // echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute\')">{{Ecrire}}</a>';
            addButton("{{Ecrire}}", "btn-danger", "writeAttribute");
            addEpInput("idEpWA");
            addClusterList("idClustIdWA");
            addAttrInput("idAttrIdWA");
            addTypeList("idAttrTypeWA");
        ?>
        <input id="idValueWA" title="{{Valeur (format naturel)}}"  placeholder="{{Data}}" />
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
            addClusterList("idClustIdWA2");
            addAttrInput("idAttrIdWA2");
            addTypeList("idAttrTypeWA2");
        ?>
        <!-- <input id="idAttrTypeWA2" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" /> -->
        <input id="idValueWA2" title="{{Valeur à écrire. Format hex string}}"  placeholder="{{Data}}" />
        <?php
            addDirInput("idDirWA2");
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="readReportingConfig">ZCL: Lecture configuration de reporting</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "readReportingConfig");
            addEpInput("idEp");
            addClusterList("idClustId");
            addAttrInput("idAttrId");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverCommandsReceived">ZCL: Découverte des commandes RX</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "discoverCommandsReceived");
            addEpInput("idEpB");
            addClusterList("idClustIdB");
            addDirList("idDir-DiscoverCmdRx");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributes">ZCL: Découverte des attributs</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "discoverAttributes");
            addEpInput("idEpD");
            addClusterList("idClustIdD");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributesExt">ZCL: Découverte des attributs (extended)</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "discoverAttributesExt");
            addEpInput("idEpC");
            addClusterList("idClustIdC");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Configure le reporting d'un attribut (BETA)">ZCL: {{Configurer le reporting}}</label>
    <div class="col-sm-9">
        <?php
            addButton("{{Configurer}}", "btn-danger", "configureReporting2");
            addEpInput("idEpCR2");
            addClusterList("idClustIdCR2");
            addAttrInput("idAttrIdCR2");
            addTypeList("idAttrTypeCR2");
            addManufCodeInput("idManufCodeCR2");
        ?>
        <input id="idMinCR2" title="{{Interval min. Format numérique}}" placeholder="{{Min}}" style="width:60px" />
        <input id="idMaxCR2" title="{{Interval max. Format numérique}}" placeholder="{{Max}}" style="width:60px" />
        <input id="idChangeCR2" title="{{Change. Format numérique dépendant de l'attribut}}" placeholder="{{Change}}" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Configure le reporting d'un attribut">ZCL:  {{Configurer le reporting}} (OBSOLETE)</label>
    <div class="col-sm-9">
        <?php
            addButton("{{Configurer}}", "btn-danger", "configureReporting");
            addEpInput("idEpCR");
            addClusterList("idClustIdCR");
            addAttrInput("idAttrIdCR");
            addTypeList("idAttrTypeCR");
            addManufCodeInput("idManufIdCR");
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
            // echo '<a class="btn btn-danger" onclick="interrogate(\'0000-ResetToFactory\')">{{Reset}}</a>';
            addButton("{{Reset}}", "btn-danger", "0000-ResetToFactory");
            addEpInput("idEpG");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0003, {{commande Identify}}">ZCL: 0003 - {{Identifier}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Identifier}}", "btn-default", "0003-Identify");
            addEpInput("idEp-IS");
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
            addButton("{{Get}}", "btn-default", "0004-GetGroupMembership");
            addEpInput("idEp-GGM");
            // addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/Remove all groups">ZCL: 0004 - Remove all groups</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Remove}}", "btn-danger", "0004-RemoveAllGroups");
            addEpInput("idEp-RAG");
            // addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0102/WindowCovering">ZCL: 0102 - Window covering</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Appliquer}}", "btn-danger", "0102-Apply");
            addEpInput("idEp-0102");
            add0102CmdsList("idCmd-0102");
        ?>
        <input id="idExtra-0102" title="{{Valeur}}" placeholder="{{Valeur}}" style="width:60px" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0201, Setpoint Raise/Lower (cmd 00)">ZCL: 0201 - Raise/lower</label>
    <div class="col-sm-5">
        <?php
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
            addButton("{{Appliquer}}", "btn-danger", "0502-StartWarning");
            addEpInput("idEp-SW");
            add0502WarningModesList("idMode-SW");
            addCheckbox("idStrobe-SW", "Strobe");
            add0502SirenLevelList("idSirenL-SW");
            addInput("idDuration-SW", "Duration in sec", "Duration");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get group identifiers (cmd 41) to SERVER">ZCL: 1000 - Groupes req</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "1000-GetGroups");
            addEpInput("idEpC1000-41");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get group identifiers response (cmd 41) to CLIENT">ZCL: 1000 - Groupes resp</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "1000-GetGroupsResp");
            addEpInput("idEpC1000-41-Resp");
            addGroupInput('idGrpC1000-41-Resp', 'Group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get endpont list (cmd 42) to SERVER">ZCL: 1000 - End points</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Interroger}}", "btn-default", "1000-GetEndpoints");
            addEpInput("idEpC1000-42");
        ?>
    </div>
</div>

<br>

<!-- Generic command -->
<div class="form-group">
    <label class="col-sm-3 control-label" title="{{Commande générique}}">{{Commande générique spécifique cluster}}</label>
    <div class="col-sm-5">
        <?php
            addButton("{{Envoyer}}", "btn-danger", "genericCmd");
            addEpInput("idEp-GC");
            addClusterList("idClustId-GC");
            addInput("idCmd-GC", "{{Commande (1B hexa)}}", "{{Commande}}");
            addInput("idData-GC", "{{Données}}", "{{Données}}");
            addManufCodeInput("idManufCode-GC");
        ?>
    </div>
</div>
