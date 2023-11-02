<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Advanced.php' -->

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
    <label class="col-sm-3 control-label" title="getSimpleDescriptor (Simple_Desc_req)">Simple descriptor request</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getSimpleDescriptor\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpInput("idEpSDR", $mainEP);
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="getNodeDescriptor (Node_Desc_req)">{{Descripteur de noeud}}</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'getNodeDescriptor\', \''.$eqId.'\')">{{Interroger}}</a>';
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
    <label class="col-sm-3 control-label" title="Mgmt_NWK_Update_req">Mgmt network update request</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'mgmtNetworkUpdateReq\', \''.$eqId.'\')">{{Interroger}}</a>';
        ?>
        <input id="idMgmtNwkUpdReqSC" title="{{Scanned channels mask (ex: all=07FFF800)}}" />
        <input id="idMgmtNwkUpdReqSD" title="{{Scan duration (01-05, FE=channel change, or FF)}}" />
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Bind cet équipement vers un autre (Bind_req)">Bind to device</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'bindToDevice\', \''.$eqId.'\')">{{Bind}}</a>';
            addEpInput("idEpE", $mainEP);
            addClusterButton("idClustIdE");
            // <input id="idIeeeE" title="{{Adresse IEEE de destination (ex: 5C0272FFFE2857A3)}}" />
            echo ' TO';
            addIeeeListButton("idIeeeE", true);
            addEpInput("idEpE2", $mainEP);
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Unbind to device (Unbind_req)">Remove binding to device</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'unbindToDevice\', \''.$eqId.'\')">{{Unbind}}</a>';
            addEpInput("idEpSrc-UBD", $mainEP);
            addClusterButton("idClustId-UBD");
            echo ' TO';
            addIeeeListButton("idAddr-UBD", true);
            addEpInput("idEpDst-UBD", $mainEP);
        ?>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-3 control-label" title="Bind cet équipement vers un groupe (Bind_req)">Bind to group</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'bindToGroup\', \''.$eqId.'\')">{{Bind}}</a>';
            addEpInput("idEpF", $mainEP);
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
            echo '<a class="btn btn-danger" onclick="interrogate(\'unbindToGroup\', \''.$eqId.'\')">{{Unbind}}</a>';
            addEpInput("idEpSrc-UBG", $mainEP);
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
            echo '<a class="btn btn-danger" onclick="interrogate(\'leaveRequest\', \''.$eqId.'\')">{{Leave}}</a>';
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
            echo '<a class="btn btn-warning" onclick="interrogate(\'readAttribute\', \''.$eqId.'\')">{{Lire}}</a>';
            // echo '<input id="idEpA" title="{{End Point (ex: 01)}}" value="'.$mainEP.'"/>';
            addEpInput("idEpA", $mainEP);
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
            echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute\', \''.$eqId.'\')">{{Ecrire}}</a>';
            addEpInput("idEpWA", $mainEP);
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
                    echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute0530\', \''.$eqId.'\')">{{Ecrire}}</a>';
                    addEpInput("idEpWA2", $mainEP);
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
            echo '<a class="btn btn-warning" onclick="interrogate(\'readReportingConfig\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpInput("idEp", $mainEP);
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
            addEpInput("idEpB", $mainEP);
            addClusterButton("idClustIdB");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributes">ZCL: Découverte des attributs</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributes\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpInput("idEpD", $mainEP);
            addClusterButton("idClustIdD");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="discoverAttributesExt">ZCL: Découverte des attributs (extended)</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributesExt\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpInput("idEpC", $mainEP);
            addClusterButton("idClustIdC");
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Configure le reporting d'un attribut">ZCL: Configurer le reporting</label>
    <div class="col-sm-9">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'configureReporting\', \''.$eqId.'\')">{{Configurer}}</a>';
            addEpInput("idEpCR", $mainEP);
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
            echo '<a class="btn btn-danger" onclick="interrogate(\'0000-ResetToFactory\', \''.$eqId.'\')">{{Reset}}</a>';
            addEpInput("idEpG", $mainEP);
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/addGroup">ZCL: 0004 - Add group</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0004-AddGroup\', \''.$eqId.'\')">{{Add}}</a>';
            addEpInput("idEp-AG", $mainEP);
            addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/Get group membership">ZCL: 0004 - Get group membership</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0004-GetGroupMembership\', \''.$eqId.'\')">{{Get}}</a>';
            addEpInput("idEp-GGM", $mainEP);
            // addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0004/Remove all groups">ZCL: 0004 - Remove all groups</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0004-RemoveAllGroups\', \''.$eqId.'\')">{{Remove}}</a>';
            addEpInput("idEp-RAG", $mainEP);
            // addGroupInput('idGroup-AG', 'Destination group');
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0201, Setpoint Raise/Lower (cmd 00)">ZCL: 0201 - Raise/lower</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0201-SetPoint\', \''.$eqId.'\')">{{Modifier}}</a>';
            addEpInput("idEpC0201-00", $mainEP);
        ?>
        <input id="idAmountC0201-00" title="{{Step: format hex 2 car}}" placeholder="{{Amount}}" style="width:60px" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0300, Mote to color (cmd 07)">ZCL: 0300 - Move to color</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0300-MoveToColor\', \''.$eqId.'\')">{{Appliquer}}</a>';
            addEpInput("idEp-MTC", $mainEP);
        ?>
        <input id="idX-MTC" title="{{X: format hex 4 car}}" placeholder="{{X}}" style="width:60px" />
        <input id="idY-MTC" title="{{Y: format hex 4 car}}" placeholder="{{Y}}" style="width:60px" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 0502/IAS WD, Start warning (cmd 00)">ZCL: 0502 - Start warning</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-danger" onclick="interrogate(\'0502-StartWarning\', \''.$eqId.'\')">{{Appliquer}}</a>';
            addEpInput("idEp-SW", $mainEP);
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
            echo '<a class="btn btn-warning" onclick="interrogate(\'1000-GetGroups\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpInput("idEpC1000-41", $mainEP);
        ?>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label" title="Cluster 1000, Get endpont list (cmd 42)">ZCL: 1000 - End points</label>
    <div class="col-sm-5">
        <?php
            echo '<a class="btn btn-warning" onclick="interrogate(\'1000-GetEndpoints\', \''.$eqId.'\')">{{Interroger}}</a>';
            addEpInput("idEpC1000-42", $mainEP);
        ?>
    </div>
</div>
