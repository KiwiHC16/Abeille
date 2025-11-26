<!-- This is equipement discovery page.
     Allows to openReturnChannel eq to get useful infos
     - list of EP
     - list of clusters
     - list of attributes
     URL of this page should contain 'id' of EQ
     -->

<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers mode & PHP errors */
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = TRUE;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["defines"])) {
            $arr = $dbgConfig["defines"];
            foreach ($arr as $idx => $value) {
                if ($value == "Tcharp38")
                    $dbgTcharp38 = true;
            }
        }

        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    if (!isset($_GET['id']))
        exit("INTERNAL ERROR: Missing 'id'");
    if (!is_numeric($_GET['id']))
        exit("INTERNAL ERROR: 'id' is not numeric");

    $eqId = $_GET['id'];
    $eqLogic = eqLogic::byId($eqId);
    $eqLogicId = $eqLogic->getLogicalid();
    list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
    $zgNb = substr($eqNet, 7); // Extracting zigate number from network
    $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
    $jsonName = $eqModel ? $eqModel['modelName'] : '';
    $jsonLocation = $eqModel ? $eqModel['modelSource'] : 'Abeille';
    $eqIeee = $eqLogic->getConfiguration('IEEE', '');

    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_zgNb = '.$zgNb.';</script>'; // PHP to JS
    echo '<script>var js_eqId = '.$eqId.';</script>'; // PHP to JS
    echo '<script>var js_eqAddr = "'.$eqAddr.'";</script>'; // PHP to JS
    echo '<script>var js_eqIeee = "'.$eqIeee.'";</script>'; // PHP to JS
    echo '<script>var js_jsonName = "'.$jsonName.'";</script>'; // PHP to JS
    echo '<script>var js_jsonLocation = "'.$jsonLocation.'";</script>'; // PHP to JS
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS

    $pluginDir = __DIR__."/../../"; // Plugin root dir
    echo '<script>let js_pluginDir = "'.$pluginDir.'";</script>';

    // require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    define("MAXEP", 10); // Max number of End Points
?>

<!-- <div class="col-xs-12"> -->
    <h3>Assistant de découverte d'équipement</h3>
    <br>

    <style>
        .b {
            border: 1px solid black;
            border-radius: 10px;
            padding: 8px;
        }
        .b h3 {
            text-align: center;
            /* height: 20px;
            line-height: 20px;
            font-size: 15px; */
        }
    </style>

    <div class="b">
        <h3><span>Jeedom</span></h1>
        <div class="row">
            <label class="col-lg-2 control-label" for="fname">ID:</label>
            <div class="col-lg-2">
                <?php echo '<input type="text" value="'.$eqId.'" readonly>'; ?>
            </div>
            <label class="col-lg-2 control-label" for="fname">Nom:</label>
            <div class="col-lg-2">
                <?php echo '<input type="text" value="'.$eqLogic->getName().'" readonly>'; ?>
            </div>
            <?php if (isset($dbgTcharp38)) { ?>
            <a class="btn btn-warning" title="Met à jour Jeedom à partir du fichier JSON" onclick="updateJeedom()">Mettre à jour</a>
            <?php } ?>
        </div>
    </div>
    <br>

    <?php if (isset($dbgTcharp38)) { ?>
    <a class="btn btn-default" title="Interrogation zigbee" onclick="showTab('zigbee')">Zigbee</a>
    <a class="btn btn-default" title="Création/update JSON" onclick="showTab('json')">Modèle</a>
    <?php } ?>

    <!-- <form> -->
        <!-- Colonne Zigbee -->
        <!-- <div class="col-lg-6 b"> -->
        <div id="idZigbee" class="form b">
            <h3>
                <span>Interrogation Zigbee</span>
                <?php
	            echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/AjoutNouvelEquipement.html#assistant-de-decouverte-zigbee"><i class="fas fa-book"></i>{{Documentation}}</a>';
	            ?>
            </h3>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Adresse:</label>
                <div class="col-lg-2">
                    <?php echo '<input id="idAddr" type="text" value="'.$eqAddr.'" readonly>'; ?>
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">IEEE:</label>
                <div class="col-lg-2">
                    <?php echo '<input type="text" value="'.$eqIeee.'" readonly>'; ?>
                </div>
            </div>

            <br>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">End points:</label>
                <div class="col-lg-4">
                    <a id="idEPListRB" class="btn btn-warning" title="Demande la liste des End Points" onclick="requestInfos('epList')"><i class="fas fa-sync"></i></a>
                    <a id="idEPListRB2" class="btn btn-warning" title="Force le End Point 01" onclick="forceEP01()"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idEPList" value="" readonly>
                </div>
                <div class="col-lg-4">
                    <b>RAPPEL !!</b>
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Source d'alim:</label>
                <div class="col-lg-4">
                    <input type="text" id="idZbPowerSource" value="" readonly>
                </div>
                <div class="col-lg-4">
                    <b>Si votre équipement fonctionne sur batterie vous DEVEZ le reveiller.</b>
                </div>
            </div>

            <style>
                table, td {
                    border: 1px solid black;
                }
                table.clustTable {
                    border: 1px solid black;
                }
                table.clustTable td {
                    padding: 4px;
                }
            </style>

            <div class="row" id="idEndPoints">
            </div>

            <div class="row" style="margin-left:10px">
                <br>
                <a class="btn btn-success pull-left" title="Télécharge 'discovery.json'" onclick="downloadDiscovery()"><i class="fas fa-cloud-download-alt"></i> Télécharger</a>
                <?php if (isset($dbgTcharp38)) { ?>
                <a class="btn btn-success pull-left" title="Genère le modèle JSON" onclick="zigbeeToModel()"><i class="fas fa-cloud-download-alt"></i> Mettre à jour modèle</a>
                <input type="file" id="files" name="files[]" multiple />
                <output id="list"></output>
                <?php } ?>
                <br>
                <br>
            </div>
        </div>

        <div id="idJson" style="display:none;">
            <div class="b">
                <h3><span>Fichier JSON</span></h1>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Nom de fichier:</label>
                    <div class="col-lg-10">
                        <?php
                            // if ($jsonName == '')
                            //     echo '<input id="idJsonName" type="text" value="-- Non défini --">';
                            // else if (!file_exists(__DIR__.'/../../core/config/devices/'.$jsonName.'/'.$jsonName.'.json'))
                            //     echo '<input id="idJsonName" type="text" value="'.$jsonName.' (n\'existe pas)">';
                            // else
                                echo '<input id="idJsonName" type="text" value="'.$jsonName.'">';
                        ?>
                        <a class="btn btn-default" title="(Re)lire" onclick="readConfig()">(Re)lire</a>
                        <a class="btn btn-alert" title="Créer/mettre à jour le modèle JSON" onclick="writeModel()">Ecrire modèle</a>
                        <a class="btn btn-default" title="Télécharger le modèle JSON" onclick="downloadConfig()"><i class="fas fa-file-download"></i> Télécharger modèle</a>
                        <a class="btn btn-default" title="Importer un 'discovery.json'" onclick="importDiscovery()"><i class="fas fa-file-upload"></i> Importer 'discovery'</a>
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Fabricant:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idManuf">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Modèle/ref:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idModel">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Type:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idType">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">{{Type générique}}:</label>
                    <div class="col-lg-10">
                    <select id="idGenericType" style="width:178px" title="{{Type générique}}">
                        <option value="Other" selected>{{Autre}}</option>
                        <option value="Battery">{{Batterie}}</option>
                        <option value="Camera">{{Caméra}}</option>
                        <option value="Heating">{{Chauffage}}</option>
                        <option value="ac">{{Climatisation}}</option>
                        <option value="Electricity">{{Electricité}}</option>
                        <option value="Environment">{{Environnement}}</option>
                        <option value="Generic">{{Générique}}</option>
                        <option value="tracking">{{Géolocalisation}}</option>
                        <option value="Light">{{Lumière}}</option>
                        <option value="Multimedia">{{Multimédia}}</option>
                        <option value="Weather">{{Météo}}</option>
                        <option value="Opening">{{Ouvrant}}</option>
                        <option value="Outlet">{{Prise}}</option>
                        <option value="Robot">{{Robot}}</option>
                        <option value="Security">{{Sécurité}}</option>
                        <option value="Thermostat">{{Thermostat}}</option>
                        <option value="Fan">{{Ventilateur}}</option>
                        <option value="Shutter">{{Volet}}</option>
                        <option value="Shutter">{{Volet}}</option>
                    </select>
                    </div>
                </div>

                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Timeout (min):</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idTimeout">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Catégories:</label>
                    <div class="col-lg-10">
                    <?php
                        /* See jeedom.config.php */
                        $categories = "";
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline">';
                            echo '<input type="checkbox" id="id'.$key.'" />'.$value['name'];
                            echo '</label>';
                            if ($categories != "")
                                $categories .= ", ";
                            $categories .= "'".$key."'";
                        }
                        echo "<script>var js_categories=[".$categories."];</script>";
                    ?>
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname" tile="Format: Fabricant-Modele">Icone:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idIcon">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname" title="ex: 1x3V CR2032">Type batterie:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idBattery">
                    </div>
                </div>
                <!-- <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Max batterie:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idBatteryMax">
                    </div>
                </div> -->

                <br>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname" title="End Point par défaut">End Point:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idMainEP">
                    </div>
                </div>
                <br>
                <div id="idCommands">
                </div>
            </div>
        </div>

        <!-- </div> -->
    </form>

<!-- </div> -->

<script>
    var eq = new Object(); // Equipement details
    eq.zgNb = js_zgNb; // Zigate number, number
    eq.jId = js_eqId; // Jeedom ID, number
    eq.addr = js_eqAddr; // Short addr, hex string
    eq.ieee = js_eqIeee; // Short addr, hex string
    zigbee = new Object(); // Zigbee interrogation datas
        // zigbee.epCount = 0; // Number of EP, number
        // zigbee.endPoints = new Array(); // Array of objects
            // ep = eq.endPoints[epIdx] = new Object(); // End Point object
            // ep.id = 0; // EP id/number
            // ep.servClustCount = 0; // IN clusters count
            // ep.servClustList = new Array();
            // ep.cliClustCount = 0; // OUT clusters count
            // ep.cliClustList = new Array();
            //     clust = new Object();
            //     clust.id = "0000"; // Cluster id, hex string
            //     clust.attrList = new Array(); // Attributs for this cluster
            //         a = new Object(); // Attribut object
            //         a.id = "0000"; // Attribut id, hex string
            //         a.type = "00"; // Attribut type, hex string

    /* Read JSON if defined */
    if (js_jsonName != '')
        readConfig();

    // /* Attempt to detect main supported attributs */
    // function refreshAttributsList(epIdx, cliClust, clustIdx) {
    //     console.log("refreshAttributsList(epIdx="+epIdx+", cliClust="+cliClust+", clustIdx="+clustIdx+")");

    //     ep = eq.endPoints[epIdx];
    //     epNb = ep.id;
    //     if (cliClust)
    //         clust = ep.cliClustList[clustIdx];
    //     else
    //         clust = ep.servClustList[clustIdx];
    //     clustId = clust.id;

    //     // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
    //     // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
    //     if (cliClust) {
    //         var clustTable = document.getElementById("idCliClust"+epIdx);
    //         var line = clustTable.rows[clustIdx];
    //     } else {
    //         var clustTable = document.getElementById("idServClust"+epIdx);
    //         var line = clustTable.rows[clustIdx];
    //     }
    //     /* Cleanup tables: remove all columns except first one (cluster ID) */
    //     var colCount = line.cells.length;
    //     for (var i = colCount - 1; i >= 1; i--) {
    //         line.deleteCell(i);
    //     }

    //     $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
    //         data: {
    //             action: 'detectAttributs',
    //             zgNb: js_zgNb,
    //             eqAddr: js_eqAddr,
    //             eqEP: epNb, // EP number
    //             clustId: clustId,
    //         },
    //         dataType: 'json',
    //         global: false,
    //         async: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'detectAttributs' !<br>Votre installation semble corrompue.<br>"+error);
    //         },
    //         success: function (json_res) {
    //             res = JSON.parse(json_res.result);
    //             if (res.status != 0)
    //                 console.log("error="+res.error);
    //             else {
    //                 console.log("res.resp follows:");
    //                 console.log(res.resp);
    //                 var resp = res.resp;

    //                 attributes = resp.Attributes;
    //                 let attrCount = attributes.length;
    //                 console.log("nb of attr="+attrCount)
    //                 for (attrIdx = 0; attrIdx < attrCount; attrIdx++) {
    //                     rattr = attributes[attrIdx];
    //                     if (rattr.Status != "00")
    //                         continue;

    //                     a = new Object();
    //                     a.id = rattr.Id;
    //                     // a.type = rattr.Type;
    //                     clust.attrList.push(a);

    //                     var newCol = line.insertCell(-1);
    //                     newCol.innerHTML = rattr.Id;
    //                 }
    //             }
    //         }
    //     });
    // }

    // /* Use 0140+8002 cmd to get supported attributs list */
    // function refreshAttributsList0140(epIdx, cliClust, clustIdx) {
    //     console.log("refreshAttributsList0140(epIdx="+epIdx+", cliClust="+cliClust+", clustIdx="+clustIdx+")");

    //     ep = eq.endPoints[epIdx];
    //     epNb = ep.id;
    //     if (cliClust)
    //         clust = ep.cliClustList[clustIdx];
    //     else
    //         clust = ep.servClustList[clustIdx];
    //     clustId = clust.id;

    //     // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
    //     // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
    //     if (cliClust) {
    //         var clustTable = document.getElementById("idCliClust"+epIdx);
    //         var line = clustTable.rows[clustIdx];
    //     } else {
    //         var clustTable = document.getElementById("idServClust"+epIdx);
    //         var line = clustTable.rows[clustIdx];
    //     }
    //     /* Cleanup tables: remove all columns except first one (cluster ID) */
    //     var colCount = line.cells.length;
    //     for (var i = colCount - 1; i >= 1; i--) {
    //         line.deleteCell(i);
    //     }

    //     $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
    //         data: {
    //             action: 'getAttrDiscResp',
    //             zgNb: js_zgNb,
    //             eqAddr: js_eqAddr,
    //             eqEP: epNb, // EP number
    //             clustId: clustId,
    //         },
    //         dataType: 'json',
    //         global: false,
    //         async: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'getAttrDiscResp' !<br>Votre installation semble corrompue.<br>"+error);
    //         },
    //         success: function (json_res) {
    //             res = JSON.parse(json_res.result);
    //             if (res.status != 0)
    //                 console.log("error="+res.error);
    //             else {
    //                 console.log("res.resp follows:");
    //                 console.log(res.resp);
    //                 var resp = res.resp;

    //                 attributes = resp.Attributes;
    //                 let attrCount = attributes.length;
    //                 console.log("nb of attr="+attrCount)
    //                 for (attrIdx = 0; attrIdx < attrCount; attrIdx++) {
    //                     rattr = attributes[attrIdx];

    //                     a = new Object();
    //                     a.id = rattr.Id;
    //                     // a.type = rattr.Type;
    //                     clust.attrList.push(a);

    //                     var newCol = line.insertCell(-1);
    //                     newCol.innerHTML = rattr.Id;
    //                 }
    //             }
    //         }
    //     });
    // }

    // function getAttributsList(epIdx, cliClust, clustIdx) {
    //     console.log("getAttributsList(epIdx="+epIdx+", cliClust="+cliClust+", clustIdx="+clustIdx+")");

    //     ep = eq.endPoints[epIdx];
    //     epNb = ep.id;
    //     if (cliClust)
    //         clust = ep.cliClustList[clustIdx];
    //     else
    //         clust = ep.servClustList[clustIdx];
    //     clustId = clust.id;
    //     document.getElementById("idStatus").value = "EP"+epNb+"/Clust"+clustId+": recherche des 'Attributs'";

    //     // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
    //     // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
    //     if (cliClust) {
    //         var clustTable = document.getElementById("idCliClust"+epIdx);
    //         var line = clustTable.rows[clustIdx];
    //     } else {
    //         var clustTable = document.getElementById("idServClust"+epIdx);
    //         var line = clustTable.rows[clustIdx];
    //     }
    //     $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
    //         data: {
    //             action: 'getAttrDiscResp',
    //             zgNb: js_zgNb,
    //             eqAddr: js_eqAddr,
    //             eqEP: epNb, // EP number
    //             clustId: clustId,
    //         },
    //         dataType: 'json',
    //         global: false,
    //         async: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'getAttrDiscResp' !<br>Votre installation semble corrompue.<br>"+error);
    //         },
    //         success: function (json_res) {
    //             res = JSON.parse(json_res.result);
    //             if (res.status != 0)
    //                 console.log("error="+res.error);
    //             else {
    //                 console.log("res.resp follows:");
    //                 console.log(res.resp);
    //                 var resp = res.resp;

    //                 a = new Object();
    //                 a.type = resp.AttrType;
    //                 a.id = resp.AttrId;
    //                 clust.attrList.push(a);

    //                 var newCol = line.insertCell(-1);
    //                 newCol.innerHTML += a.id+"/"+a.type;

    //                 document.getElementById("idStatus").value = "";
    //             }
    //         }
    //     });
    // }

//     /* openReturnChannel EQ to get clusters list. */
//     function refreshClustersList(epIdx) {
//         console.log("refreshClustersList(epIdx="+epIdx+")");

//         ep = eq.endPoints[epIdx];
//         epNb = ep.id;
//         document.getElementById("idStatus").value = "EP"+epNb+": recherche des 'Clusters'";

//         // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
//         // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
//         var servClustTable = document.getElementById("idServClust"+epIdx);
//         var cliClustTable = document.getElementById("idCliClust"+epIdx);
//         /* Cleanup tables */
//         var rowCount = servClustTable.rows.length;
//         for (var i = rowCount - 1; i >= 0; i--) {
//             servClustTable.deleteRow(i);
//         }
//         rowCount = cliClustTable.rows.length;
//         for (i = rowCount - 1; i >= 0; i--) {
//             cliClustTable.deleteRow(i);
//         }

//         /* Do the request to EQ */
//         $.ajax({
//             type: 'POST',
//             url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
//             data: {
//                 action: 'getSingleDescResp',
//                 zgNb: js_zgNb,
//                 eqAddr: js_eqAddr,
//                 eqEP: epNb, // EP number
//             },
//             dataType: 'json',
//             global: false,
//             async: false,
//             error: function (request, status, error) {
//                 bootbox.alert("ERREUR 'getSingleDescResp' !<br>Votre installation semble corrompue.<br>"+error);
//                 document.getElementById("idStatus").value = "ERROR clusters";
//             },
//             success: function (json_res) {
//                 res = JSON.parse(json_res.result);
//                 if (res.status != 0) {
//                     console.log("error="+res.error);
//                     status = -1;
//                     document.getElementById("idStatus").value = "ERROR clusters";
//                 } else {
//                     console.log("res.resp follows:");
//                     console.log(res.resp);
//                     var resp = res.resp;

// console.log("eq follows:");
// console.log(eq);
//                     ep.servClustCount = resp.servClustCount;
//                     ep.servClustList = []; // List of objects
//                     ep.cliClustCount = resp.cliClustCount;
//                     ep.cliClustList = []; // List of objects
//                     for (clustIdx = 0; clustIdx < resp.servClustCount; clustIdx++) {
//                         clust = new Object();
//                         clust.id = resp.servClustList[clustIdx];
//                         clust.attrList = new Array();
//                         ep.servClustList.push(clust);

//                         var newRow = servClustTable.insertRow(-1);
//                         var newCol = newRow.insertCell(0);
// 	                    newCol.innerHTML = resp.servClustList[clustIdx]
//                         // newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="refreshAttributsList('+epIdx+', 0, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
//                         newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs 0140" onclick="refreshAttributsList0140('+epIdx+', 0, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
//                     }
//                     for (clustIdx = 0; clustIdx < resp.cliClustCount; clustIdx++) {
//                         clust = new Object();
//                         clust.id = resp.cliClustList[clustIdx];
//                         clust.attrList = new Array();
//                         ep.cliClustList.push(clust);

//                         var newRow = cliClustTable.insertRow(-1);
//                         var newCol = newRow.insertCell(0);
// 	                    newCol.innerHTML = resp.cliClustList[clustIdx];
//                         // newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="refreshAttributsList('+epIdx+', 1, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
//                         newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs 0140" onclick="refreshAttributsList0140('+epIdx+', 1, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
//                    }
//                     status = 0;
//                     document.getElementById("idStatus").value = "";
//                 }
//             }
//         });
//         console.log("refreshClustersList() END, status="+status);
//         return status;
//     }



    /* Reminder
    var eq = new Object(); // Equipement details
    eq.zgNb = js_zgNb; // Zigate number, number
    eq.id = js_eqId; // Jeedom ID, number
    eq.addr = js_eqAddr; // Short addr, hex string
    eq.epCount = 0; // Number of EP, number
    eq.endPoints = new Array(); // Array of objects
        // ep = eq.endPoints[epIdx] = new Object(); // End Point object
        // ep.id = 0; // EP id/number
        // ep.servClustCount = 0; // IN clusters count
        // ep.servClustList = new Array();
        // ep.cliClustCount = 0; // OUT clusters count
        // ep.cliClustList = new Array();
        //     clust = new Object();
        //     clust.id = "0000"; // Cluster id, hex string
        //     clust.attrList = new Array(); // Attributs for this cluster
        //         a = new Object(); // Attribut object
        //         a.type = "00"; // Attribut type, hex string => ??? Data type ? What for ???
        //         a.id = "0000"; // Attribut id, hex string
        // TODO: Missing supported zigbee commands list
        //       Currently assuming all commands from the standard are supported
    eq.commands = new Object(); // Commands list
    */

    /* Read JSON.
       Called from JSON read/reload button. */
    function readConfig() {
        console.log("readConfig()");

        js_jsonName = document.getElementById("idJsonName").value;

        /* TODO: Check if there is any user modification and ask user to cancel them */

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'readDeviceConfig',
                jsonId: js_jsonName,
                jsonLocation: js_jsonLocation,
                mode: 1 // 1=Do not merge commands & command files
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'readConfig' !<br>Votre installation semble corrompue.<br>"+error);
                status = -1;
            },
            success: function (json_res) {
                // console.log("json_res="+json_res);
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                } else {
                    // console.log(res.content);
                    jeq2 = JSON.parse(res.content);
                    console.log(jeq2);

                    /* Let's refresh display */

                    if (typeof jeq2.manufacturer !== 'undefined')
                        document.getElementById("idManuf").value = jeq2.manufacturer;
                    if (typeof jeq2.model !== 'undefined')
                        document.getElementById("idModel").value = jeq2.model;
                    if (typeof jeq2.type !== 'undefined')
                        document.getElementById("idType").value = jeq2.type;
                    if (typeof jeq2.timeout !== 'undefined')
                        document.getElementById("idTimeout").value = jeq2.timeout;

                    if ("category" in jeq2)
                        jcat = jeq2.category;
                    if (typeof jcat !== 'undefined') { // No category defined
                        for (i = 0; i < js_categories.length; i++) {
                            cat = js_categories[i];
                            // console.log("cat="+cat);
                            if (cat in jcat)
                                document.getElementById("id"+cat).checked = true;
                            else
                                document.getElementById("id"+cat).checked = false;
                        }
                    }

                    if (typeof jeq2.configuration !== 'undefined') {
                        config = jeq2.configuration;
                        if (typeof config.mainEP !== 'undefined')
                            eq.defaultEp = config.mainEP;
                        if (typeof config.icon !== 'undefined')
                            eq.icon = config.icon;
                        if (typeof config.batteryType !== 'undefined')
                            eq.batteryType = config.batteryType;
                    }
                    displayDevice();

                    if ("commands" in jeq2)
                        eq.commands = jeq2.commands;
                    displayCommands();
                }
            }
        });
    }

    /* Display device infos in JSON area. */
    function displayDevice() {
        console.log("displayDevice()");

        if (typeof eq.icon !== 'undefined')
            document.getElementById("idIcon").value = eq.icon;
        if (typeof eq.batteryType !== 'undefined')
            document.getElementById("idBattery").value = eq.batteryType;
        if (typeof eq.defaultEp !== 'undefined')
            document.getElementById("idMainEP").value = eq.defaultEp;
    }

    /* Display commands in JSON column (coming either from JSON file or Zigbee discovery). */
    function displayCommands() {
        console.log("displayCommands()");

        if (typeof eq.commands === 'undefined') { // No commands defined
            $('#idCommands').empty();
            console.log("=> No cmds defined");
            return;
        }

        cmds = eq.commands;
        console.log(cmds);
        hcmds = '<table>';
        hcmds += '<div class="row">';
        hcmds += '<thead><tr>';
        hcmds += '<th>Cmde Jeedom</th>';
        hcmds += '<th>Fichier cmde</th>';
        hcmds += '<th>Params</th>';
        hcmds += '<th>Init</th>';
        hcmds += '<th>Visible</th>';
        hcmds += '<th>Next Line</th>';
        hcmds += '</tr></thead>';
        for (const [key, value] of Object.entries(cmds)) {
            console.log(`${key}: ${value}`);
            if (key.substr(0, 7) == "include")
                newSyntax = false;
            else
                newSyntax = true;
            if (newSyntax == true) {
                // "cmd": { "use":"toto" } => key="cmd", value="{ "use" ... }"

                hcmds += '<tr>';
                hcmds += '<td>'+key+'</td>';
                hcmds += '<td>'+value.use+'</td>';
                if ("params" in value)
                    hcmds += '<td>'+value.params+'</td>';
                else
                    hcmds += '<td></td>';
                if (("execAtCreation" in value) && (value.execAtCreation == "yes"))
                    hcmds += '<td><input type="checkbox" checked></td>';
                else
                    hcmds += '<td><input type="checkbox"></td>';
                if (("isVisible" in value) && ((value.isVisible == "yes") || (value.isVisible == 1)))
                    hcmds += '<td><input type="checkbox" checked></td>';
                else
                    hcmds += '<td><input type="checkbox"></td>';
                if (("nextLine" in value) && (value.nextLine == "after"))
                    hcmds += '<td><input type="checkbox" checked></td>';
                else
                    hcmds += '<td><input type="checkbox"></td>';
                hcmds += '</tr>';
            } else {
                // "include3":"cmd12" => key="include3", value="cmd12"

                hcmds += '<tr>';
                hcmds += '<td>'+key+'</td>';
                hcmds += '<td>'+value+'</td>';
                hcmds += '<td>'+'</td>';
                hcmds += '<td>'+'</td>';
            }
        };
        hcmds += '</table>';
        $('#idCommands').empty().append(hcmds);
    }

    /* Check if clustId/attrId exist in another EP.
       Purpose is to give a unique Jeedom command name.
       Returns: true is exists, else false */
    function sameAttribInOtherEP(epId, clustId, attrId) {
        for (var epIdx = 0; epIdx < zigbee.endPoints.length; epIdx++) {
            ep = zigbee.endPoints[epIdx];
            if (ep.id == epId)
                continue; // Current EP
            for (var clustIdx = 0; clustIdx < ep.servClustList.length; clustIdx++) {
                clust = ep.servClustList[clustIdx];
                if (clust.id != clustId)
                    continue;

                /* Coresponding cluster in a different EP found. */
                for (var attrIdx = 0; attrIdx < clust.attrList.length; attrIdx++) {
                    attr = clust.attrList[attrIdx];
                    if (attr.id == attrId)
                        return true; // FOUND !
                }
            }
        }
        return false;
    }

    /* Check if clustId/zigbeeCmdName exist in another EP.
       Purpose is to give a unique Jeedom command name.
       Returns: true is exists, else false */
    function sameZCmdInOtherEP(epId, clustId, cmdName) {
        for (var epIdx = 0; epIdx < zigbee.endPoints.length; epIdx++) {
            ep = zigbee.endPoints[epIdx];
            if (ep.id == epId)
                continue; // Current EP

            for (var clustIdx = 0; clustIdx < ep.servClustList.length; clustIdx++) {
                clust = ep.servClustList[clustIdx];
                if (clust.id != clustId)
                    continue; // Not the correct cluster

                /* TODO: Currently can't check against supported commands list
                         because this does not exist.
                         Assuming commands exist as soon as cluster is supported.
                         To be revisited !! */
                return true;
            }
        }
        return false;
    }

    /* Check if clustId exists in several EP.
       Purpose is to give a unique Jeedom command name.
       Returns: true is exists, else false */
    function sameClustInSeveralEP(clustId) {
        foundNb = 0;
        endPoints = zigbee.endPoints;
        for (var epId in endPoints) {
            // console.log("EP "+epId);
            ep = endPoints[epId];

            if (!isset(ep.servClusters))
                continue;

            if (isset(ep.servClusters[clustId]))
                foundNb++;
        }
        if (foundNb > 1)
            return true;
        else
            return false;
    }

    function newCmd($use, $params = "", $exec = "") {
        cmd = new Object;
        cmd['use'] = $use;
        if ($params != "")
            cmd['params'] = $params;
        if ($exec != "")
            cmd["execAtCreation"] = $exec;
        return cmd;
    }

    // Returns true if lighting device
    function isALightingDevice(ep) {
        // If device ID is defined let's check if lighting device (see devicesTable in Zigbee consts)
        profId = -1;
        devId = -1;
        if (typeof ep.profId !== "undefined")
            profId = parseInt(ep.profId, 16);
        if (typeof ep.devId !== "undefined")
            devId = parseInt(ep.devId, 16);
        if ((profId == -1) || (devId == -1))
            return false;

        if (profId == 0x104) {
            if ((devId >= 0x0100) && (devId <= 0x010E))
                return true;
            if ((devId >= 0x0800) && (devId <= 0x850))
                return true;
        } else if (profId == 0xC05E) { // ZLL ?
            return true;
        }

        return false;
    }

    /* Generate JSON model based on zigbee discovery datas */
    function zigbeeToModel() {
        console.log("zigbeeToModel()");
        console.log("zigbee", zigbee);

        /* Jeedom commands naming reminder:
           - for attributes: ['Get-'/'Set-'/''][EP]-<clustId>-<attribute_name>
           - for commands: Cmd-[EP-]<clustId>-<cmd_name>
           EP is optional and must be set only if same
             attribute or command exists in another EP.
         */
        var cmds = new Object();
        var cmdNb = 0;
        endPoints = zigbee.endPoints;
        mainEp = -1;
        minTimeout = 60; // Min timeout = 60min

        // 0000/Basic cluster on all EP
        for (var epId in endPoints) {
            // console.log("EP "+epId);
            ep = endPoints[epId];

            if (isALightingDevice(ep)) {
                document.getElementById("idGenericType").value = "Light";
                document.getElementById("idlight").checked = true;
            }

            if (!isset(ep.servClusters))
                continue;

            if (isset(ep.servClusters["0000"]) && isset(ep.servClusters["0000"]['attributes'])) {
                attributes = ep.servClusters["0000"]['attributes'];

                mainEp = epId;

                /* Only attribute 4000 is converted to user command.
                   No sense for others */
                if (isset(attributes['4000'])) {
                    // The following cmds are useless. Already part of EQ advanced infos.
                    // cmds["SWBuildID"] = newCmd("inf_zbAttr-0000-SWBuildID");
                    // cmds["Get SWBuildID"] = newCmd("act_zbReadAttribute", "clustId=0000&attrId=4000");
                }
                if (typeof zigbee.signature === "undefined")
                    zigbee.signature = new Object();
                if (isset(attributes['0004'])) {
                    zigbee.signature['manufacturer'] = attributes['0004']['value'];
                }
                if (isset(attributes['0005'])) {
                    zigbee.signature['model'] = attributes['0005']['value'];
                }
            }
        }

        for (var epId in endPoints) {
            // console.log("EP "+epId);
            ep = endPoints[epId];

            if (!isset(ep.servClusters))
                continue;

            /* 0001/Power configuration cluster */
            if (isset(ep.servClusters["0001"]) && isset(ep.servClusters["0001"]['attributes'])) {
                attributes = ep.servClusters['0001']['attributes'];
                if (isset(attributes['0021'])) {
                    cmds["Battery-Percent"] = newCmd("inf_zbAttr-0001-BatteryPercent", "ep="+epId);
                    cmds["SetReporting "+epId+"-0001-0021"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0001&attrId=0021&attrType=20&minInterval=3600&maxInterval=3600", "yes");
                } else if (isset(attributes['0020'])) {
                    cmds["Battery-Volt"] = newCmd("inf_zbAttr-0001-BatteryVolt", "ep="+epId);
                    // cmds["Battery-Volt2Percent"] = newCmd("battery-Volt2Percent-3", "ep="+epId);
                    cmds["SetReporting "+epId+"-0001-0020"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0001&attrId=0020&attrType=20&minInterval=3600&maxInterval=3600", "yes");
                }
                cmds["Bind "+epId+"-0001-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0001", "yes");
            }

            /* 0003/Identify cluster */
            if (typeof ep.servClusters["0003"] !== "undefined") {
                cmds["Identify"] = newCmd("act_zbCmdC-Identify");
                // cmds["Identify"]["isVisible"] = 1; // Hidden by default. Rarely used
                cmds["Identify"]["nextLine"] = "after";
            }

            /* 0004/Groups cluster */
            if (typeof ep.servClusters["0004"] !== "undefined") {
                // Cmd info 'Grousp' no longer required
                // cmds["Groups"] = newCmd("Group-Membership");
            }
        }

        /* 0006/OnOff cluster on all EP */
        onOffIdx = 1; // Used if multiple 0006 clusters
        multipleCluster = sameClustInSeveralEP("0006");
        clustIdx = '';
        for (var epId in endPoints) {
            ep = endPoints[epId];
            // console.log("EP"+epId+"=", ep);

            if (!isset(ep.servClusters))
                continue;

            if (multipleCluster) // If multiple clusters adding index in name
                clustIdx = " "+onOffIdx;
            if (isset(ep.servClusters["0006"]) && isset(ep.servClusters["0006"]['attributes'])) {
                attributes = ep.servClusters["0006"]['attributes'];
                if (isset(attributes['0000'])) {
                    // Adding on/off & toggle commands but assuming all supported
                    cmds["On"+clustIdx] = newCmd("act_zbCmdC-0006-On", "ep="+epId);
                    cmds["On"+clustIdx]["isVisible"] = 1;
                    cmds["Off"+clustIdx] = newCmd("act_zbCmdC-0006-Off", "ep="+epId);
                    cmds["Off"+clustIdx]["isVisible"] = 1;
                    cmds["Toggle"+clustIdx] = newCmd("act_zbCmdC-0006-Toggle", "ep="+epId);
                    cmds["Get Status"+clustIdx] = newCmd("act_zbReadAttribute", "ep="+epId+"&clustId=0006&attrId=0000");
                    cmds["Status"+clustIdx] = newCmd("inf_zbAttr-0006-OnOff", "ep="+epId);
                    cmds["Status"+clustIdx]["isVisible"] = 1;
                    cmds["Status"+clustIdx]["nextLine"] = "after";
                    // Adding bind + configureReporting but assuming supported
                    cmds["Bind "+epId+"-0006-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0006", "yes");
                    cmds["SetReporting "+epId+"-0006-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0006&attrId=0000&attrType=10", "yes");
                }
            }
            onOffIdx++;
        }

        /* 0008/Level cluster on all EP */
        levelIdx = 1; // Used if multiple 0008 clusters
        multipleCluster = sameClustInSeveralEP("0008");
        clustIdx = '';
        for (var epId in endPoints) {
            // console.log("EP "+epId);
            ep = endPoints[epId];
            // console.log("Cluster 0008 EP "+epId+": ", ep);

            if (!isset(ep.servClusters))
                continue;

            // If device ID is defined let's check if lighting device (see devicesTable in Zigbee consts)
            if (isALightingDevice(ep))
                cmdName = "Brightness";
            else
                cmdName = "Level";
            if (multipleCluster) // If multiple clusters adding index in name
                cmdName += " "+levelIdx;

            if (isset(ep.servClusters["0008"]) && isset(ep.servClusters["0008"]['attributes'])) {
                attributes = ep.servClusters["0008"]['attributes'];
                // Note: If device is a light bulb, need to use 'act_setLevel-Light'
                cmds["Set "+cmdName] = newCmd("act_setLevel-Light", "ep="+epId);
                cmds["Set "+cmdName]["isVisible"] = 1;
                cmds["Set "+cmdName]["value"] = cmdName; // Slider default value
                cmds["Get "+cmdName] = newCmd("act_zbReadAttribute", "ep="+epId+"&clustId=0008&attrId=0000");

                cmds[cmdName] = newCmd("inf_zbAttr-0008-CurrentLevel", "ep="+epId);
                cmds[cmdName]["isVisible"] = 1;
                cmds[cmdName]["nextLine"] = "after";
                cmds["Bind "+epId+"-0008-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0008", "yes");
                cmds["SetReporting "+epId+"-0008-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0008&attrId=0000&attrType=20", "yes");
            }
            levelIdx++;
        }

        for (var epId in endPoints) {
            // console.log("EP "+epId);
            ep = endPoints[epId];

            if (!isset(ep.servClusters))
                continue;

            /* Analog Input */
            if (isset(ep.servClusters["000C"]) && isset(ep.servClusters["000C"]['attributes'])) {
                attributes = ep.servClusters["000C"]['attributes'];
                if (isset(attributes['0051'])) {
                    cmds["OutOfService"] = newCmd("inf_zbAttr-000C-OutOfService");
                    // cmds["OutOfService"]["isVisible"] = 1;
                    cmds["Get OutOfService"] = newCmd("act_zbReadAttribute", "clustId=000C&attrId=0051");
                }
                if (isset(attributes['0055'])) {
                    cmds["PresentValue"] = newCmd("inf_zbAttr-000C-PresentValue");
                    cmds["PresentValue"]["isVisible"] = 1;
                    cmds["Get PresentValue"] = newCmd("act_zbReadAttribute", "clustId=000C&attrId=0055");

                    cmds["SetReporting 000C-PresentValue"] = newCmd("act_zbConfigureReporting2", "clustId=000C&attrId=0055&attrType=39&minInterval=300&maxInterval=600", "yes");
                    cmds["SetReporting 000C-PresentValue"]["comment"] = "Reporting every 5 to 10min";
                }
                if (isset(attributes['006F'])) {
                    cmds["StatusFlags"] = newCmd("inf_zbAttr-000C-StatusFlags");
                    // cmds["StatusFlags"]["isVisible"] = 1;
                    cmds["Get StatusFlags"] = newCmd("act_zbReadAttribute", "clustId=000C&attrId=006F");
                }

                cmds["Bind 000C-ToZigate"] = newCmd("act_zbBindToZigate", "clustId=000C", "yes");
            }

            /* 0102/Window covering */
            if (isset(ep.servClusters["0102"]) && isset(ep.servClusters["0102"]['attributes'])) {
                attributes = ep.servClusters["0102"]['attributes'];
                cmds["Up"] = newCmd("act_zbCmdC-0102-UpOpen", "ep="+epId);
                cmds["Up"]["isVisible"] = 1;
                cmds["Stop"] = newCmd("act_zbCmdC-0102-Stop", "ep="+epId);
                cmds["Stop"]["isVisible"] = 1;
                cmds["Down"] = newCmd("act_zbCmdC-0102-DownClose", "ep="+epId);
                cmds["Down"]["isVisible"] = 1;
                if (isset(attributes['0008'])) {
                    // TODO: Take care if 'Level' already used (ex: by cluster 0008)
                    cmds["Get Level"] = newCmd("act_zbReadAttribute", "ep="+epId+"&clustId=0102&attrId=0008");
                    cmds["Level"] = newCmd("inf_zbAttr-0102-CurPosLiftPercent", "ep="+epId);
                    cmds["Level"]["isVisible"] = 1;
                    cmds["SetReporting "+epId+"-0102-0008"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0102&attrId=0008&attrType=20", "yes");
                }
                cmds["Bind "+epId+"-0102-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0102", "yes");
            }

            /* 0300/Color cluster */
            if (isset(ep.servClusters["0300"]) && isset(ep.servClusters["0300"]['attributes'])) {
                attributes = ep.servClusters["0300"]['attributes'];
                currentMode = 1; // Default = CurrentX & CurrentY
                if (isset(attributes['0008']) && isset(attributes['0008']['value']))
                    currentMode = attributes['0008']['value'];

                // Color mode
                if (isset(attributes['0008'])) {
                    cmds["Color Mode"] = newCmd("inf_zbAttr-0300-ColorMode");
                    // cmds["Color Mode"]["isVisible"] = 1;
                    cmds["Get Color Mode"] = newCmd("act_zbReadAttribute", "clustId=0300&attrId=0008");
                }

                // Hue/saturation mode
                if (isset(attributes['0000'])) {
                    cmds["Current HUE"] = newCmd("inf_zbAttr-0300-CurrentHue");
                    // if (currentMode == 0) // If HUE + saturation
                    //     cmds["Current HUE"]["isVisible"] = 1;
                    cmds["Get Current HUE"] = newCmd("act_zbReadAttribute", "clustId=0300&attrId=0000");
                }
                if (isset(attributes['0001'])) {
                    cmds["Current Saturation"] = newCmd("inf_zbAttr-0300-CurrentSaturation");
                    // if (currentMode == 0) // If HUE + saturation
                    //     cmds["Current Saturation"]["isVisible"] = 1;
                    cmds["Get Current Saturation"] = newCmd("act_zbReadAttribute", "clustId=0300&attrId=0001");
                }

                // X/Y mode
                if (isset(attributes['0003'])) {
                    cmds["Current X"] = newCmd("inf_zbAttr-0300-CurrentX");
                    // if (currentMode == 1) // If X + Y
                    //     cmds["Current X"]["isVisible"] = 1;
                    cmds["Get Current X"] = newCmd("act_zbReadAttribute", "clustId=0300&attrId=0003");
                }
                if (isset(attributes['0004'])) {
                    cmds["Current Y"] = newCmd("inf_zbAttr-0300-CurrentY");
                    // if (currentMode == 1) // If X + Y
                    //     cmds["Current Y"]["isVisible"] = 1;
                    cmds["Get Current Y"] = newCmd("act_zbReadAttribute", "clustId=0300&attrId=0004");
                }
                if (isset(attributes['0003']) || isset(attributes['0004'])) {
                    // Associated commands to X/Y mode:
                    // Move to color (cmd 0x07)
                    // Move color (cmd 0x08)
                    // Step color (cmd 0x09)
                    // Stop move step (cmd 0x47)
                    cmds["White"] = newCmd("act_zbCmdC-0300-MoveToColor", "X=6000&Y=6000");
                    cmds["White"]["isVisible"] = 1;
                    cmds["White"]["logicalId"] = "SetWhite";

                    cmds["Blue"] = newCmd("act_zbCmdC-0300-MoveToColor", "X=228F&Y=228F");
                    cmds["Blue"]["isVisible"] = 1;
                    cmds["Blue"]["logicalId"] = "SetBlue";

                    cmds["Red"] = newCmd("act_zbCmdC-0300-MoveToColor", "X=AE13&Y=51EB");
                    cmds["Red"]["isVisible"] = 1;
                    cmds["Red"]["logicalId"] = "SetRed";

                    cmds["Green"] = newCmd("act_zbCmdC-0300-MoveToColor", "X=147A&Y=D709");
                    cmds["Green"]["isVisible"] = 1;
                    cmds["Green"]["logicalId"] = "SetGreen";

                    cmds["RGB"] = newCmd("setRGB");
                    cmds["RGB"]["isVisible"] = 1;
                    cmds["RGB"]["nextLine"] = "after";
                }

                // ColorTemperatureMireds
                if (isset(attributes['0007'])) {
                    // cmds["Set 2700K"] = newCmd("act_zbCmdC-0300-MoveToColorTemp", "slider=2700");
                    // if (currentMode == 2) // ColorTemperatureMireds
                    //     cmds["Set 2700K"]["isVisible"] = 1;
                    // cmds["Set 4000K"] = newCmd("act_zbCmdC-0300-MoveToColorTemp", "slider=4000");
                    // if (currentMode == 2) // ColorTemperatureMireds
                    //     cmds["Set 4000K"]["isVisible"] = 1;
                    cmds["Set Color Temp"] = newCmd("act_zbCmdC-0300-MoveToColorTemp");
                    cmds["Set Color Temp"]["isVisible"] = 1;

                    cmds["Color Temp"] = newCmd("inf_zbAttr-0300-ColorTemperatureMireds");
                    cmds["Color Temp"]["isVisible"] = 1;
                    // if (currentMode == 2) // ColorTemperatureMireds
                    //     cmds["ColorTemperature"]["isVisible"] = 1;
                    cmds["Get ColorTemperature"] = newCmd("act_zbReadAttribute", "clustId=0300&attrId=0007");
                }

                cmds["Bind "+epId+"-0300-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0300", "yes");
            }

            /* 0400/Illuminance cluster */
            if (isset(ep.servClusters["0400"]) && isset(ep.servClusters["0400"]['attributes'])) {
                attributes = ep.servClusters["0400"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["Illuminance"] = newCmd("inf_zbAttr-0400-MeasuredValue", "ep="+epId);
                    cmds["Illuminance"]["isVisible"] = 1;
                    cmds["Get Illuminance"] = newCmd("act_zbReadAttribute", "ep="+epId+"&clustId=0400&attrId=0000");
                    cmds["Bind "+epId+"-0400-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0400", "yes");
                    cmds["SetReporting "+epId+"-0400-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0400&attrType=21&attrId=0000&minInterval=300&maxInterval=600&changeVal=0", "yes");
                    cmds["SetReporting "+epId+"-0400-0000"]["comment"] = "Reporting every 5 to 10mins";
                }
            }

            /* 0402/Temperature cluster */
            if (isset(ep.servClusters["0402"]) && isset(ep.servClusters["0402"]['attributes'])) {
                attributes = ep.servClusters["0402"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["Temperature"] = newCmd("inf_zbAttr-0402-MeasuredValue", "ep="+epId);
                    cmds["Temperature"]["isVisible"] = 1;
                    cmds["Get Temperature"] = newCmd("act_zbReadAttribute", "ep="+epId+"&clustId=0402&attrId=0000");
                    cmds["SetReporting "+epId+"-0402-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0402&attrId=0000&attrType=29&minInterval=540&maxInterval=600", "yes");
                    cmds["SetReporting "+epId+"-0402-0000"]["comment"] = "Reporting every 9 to 10mins";
                    cmds["Bind "+epId+"-0402-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0402", "yes");
                    if (minTimeout > 10)
                        minTimeout = 10;
                }
            }

            /* 0405/Humidity cluster */
            if (isset(ep.servClusters["0405"]) && isset(ep.servClusters["0405"]['attributes'])) {
                attributes = ep.servClusters["0405"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["Humidity"] = newCmd("inf_zbAttr-0405-MeasuredValue", "ep="+epId);
                    cmds["Humidity"]["isVisible"] = 1;
                    cmds["Get Humidity"] = newCmd("act_zbReadAttribute", "ep="+epId+"&clustId=0405&attrId=0000");
                    cmds["SetReporting "+epId+"-0405-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0405&attrId=0000&attrType=21&minInterval=540&maxInterval=600", "yes");
                    cmds["SetReporting "+epId+"-0405-0000"]["comment"] = "Reporting every 9 to 10mins";
                    cmds["Bind "+epId+"-0405-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0405", "yes");
                    if (minTimeout > 10)
                        minTimeout = 10;
                }
            }

            /* 0406/Occupancy Sensing cluster */
            if (isset(ep.servClusters["0406"]) && isset(ep.servClusters["0406"]['attributes'])) {
                attributes = ep.servClusters["0406"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["Occupancy"] = newCmd("inf_zbAttr-0406-Occupancy");
                    cmds["Occupancy"]["isVisible"] = 1;
                    // cmds["Get Humidity"] = newCmd("act_zbReadAttribute", "clustId=0405&attrId=0000");
                    cmds["Bind "+epId+"-0406-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0406", "yes");
                    cmds["SetReporting "+epId+"-0406-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=0406&attrId=0000&attrType=18", "yes");
                    // if (minTimeout > 10)
                    //     minTimeout = 10;
                }
            }

            /* 040C/Carbon Monoxide (CO) cluster */
            if (isset(ep.servClusters["040C"]) && isset(ep.servClusters["042040CA"]['attributes'])) {
                attributes = ep.servClusters["040C"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["CO"] = newCmd("inf_zbAttr-040C-MeasuredValue");
                    cmds["CO"]["isVisible"] = 1;
                    cmds["Bind "+epId+"-040C-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=040C", "yes");
                    cmds["SetReporting "+epId+"-040C-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=040C&attrId=0000&attrType=39", "yes");
                }
            }

            /* 040D/Carbon Dioxide (CO2) cluster */
            if (isset(ep.servClusters["040D"]) && isset(ep.servClusters["040D"]['attributes'])) {
                attributes = ep.servClusters["040D"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["CO2"] = newCmd("inf_zbAttr-040D-MeasuredValue");
                    cmds["CO2"]["isVisible"] = 1;
                    cmds["Bind "+epId+"-040D-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=040D", "yes");
                    cmds["SetReporting "+epId+"-040D-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=040D&attrId=0000&attrType=39", "yes");
                }
            }

            /* 042A/PM2.5 cluster */
            if (isset(ep.servClusters["042A"]) && isset(ep.servClusters["042A"]['attributes'])) {
                attributes = ep.servClusters["042A"]['attributes'];
                if (isset(attributes['0000'])) {
                    cmds["PM2.5"] = newCmd("inf_zbAttr-042A-MeasuredValue");
                    cmds["PM2.5"]["isVisible"] = 1;
                    cmds["Bind "+epId+"-042A-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=042A", "yes");
                    cmds["SetReporting "+epId+"-042A-0000"] = newCmd("act_zbConfigureReporting2", "ep="+epId+"&clustId=042A&attrId=0000&attrType=39", "yes");
                }
            }

            /* 0500/IAS Zone */
            if (isset(ep.servClusters["0500"]) && isset(ep.servClusters["0500"]['attributes'])) {
                attributes = ep.servClusters["0500"]['attributes'];
                // if (isset(attributes['0002'])) {
                //     cmds["Zone Status"] = newCmd("inf_zbAttr-0500-ZoneStatus");
                //     // cmds["Zone Status"]["isVisible"] = 1;
                //     cmds["Zone Status"]["comment"] = "This should trig 'Zone Alarm1'";
                //     cmds["Get Zone Status"] = newCmd("act_zbReadAttribute", "clustId=0500&attrId=0002");
                //     cmds["Zone Alarm1"] = newCmd("inf_zbAttr-0500-ZoneStatus-Alarm1");
                //     cmds["Zone Alarm1"]["isVisible"] = 1;
                //     cmds["SetReporting 0500-0002"] = newCmd("act_zbConfigureReporting2", "clustId=0500&attrId=0002&attrType=19", "yes");
                // }

                // Extracting zone type for door/window case (zoneType=='0016')
                if (isset(ep.servClusters["0500"]['attributes']['0001']))
                    zoneType = ep.servClusters["0500"]['attributes']['0001'];
                else
                    zoneType = '';

                // Generated cmd 00 seems to be mandatory. Using it by default
                cmds["ZoneStatus-ChangeNotification"] = newCmd("inf_zbCmdS-0500-ZoneStatus-ChangeNotification", "ep="+epId);
                trigOut = new Object();
                cmds["ZoneStatus-ChangeNotification"]["trigOut"] = new Object();
                if (zoneType == '0016') {
                    trigOut.comment = "On receive we trig <EP>-doorStatus with extracted boolean bit1 value";
                    trigOut.valueOffset = "#value#&2";
                    cmds["ZoneStatus-ChangeNotification"]["trigOut"][epId+"-doorStatus"] = trigOut;
                } else {
                    trigOut.comment = "On receive we trig <EP>-0500-alarm1 with extracted boolean bit0 value";
                    trigOut.valueOffset = "#value#&1";
                    cmds["ZoneStatus-ChangeNotification"]["trigOut"][epId+"-0500-alarm1"] = trigOut;
                }

                if (zoneType == '0016') {
                    cmds["Status"] = newCmd("inf_door-Status", "ep="+epId);
                    cmds["Status"]["isVisible"] = 1;
                } else {
                    cmds["Zone Alarm1"] = newCmd("inf_zone-Alarm1", "ep="+epId);
                    cmds["Zone Alarm1"]["isVisible"] = 1;
                }

                // Tamper generation if required
                // cmds["Tamper"] = newCmd("inf_zbAttr-0500-ZoneStatus-Tamper", "ep="+epId);
                // cmds["Tamper"]["isVisible"] = 1;

                cmds["Bind "+epId+"-0500-ToZigate"] = newCmd("act_zbBindToZigate", "ep="+epId+"&clustId=0500", "yes");
            }

            /* 0502/IAS WD */
            // if (isset(ep.servClusters["0502"]) && isset(ep.servClusters["0502"]['attributes'])) {
            //     attributes = ep.servClusters["0502"]['attributes'];
            // }

            /* 0702/Metering (Smart Energy) */
            if (isset(ep.servClusters["0702"]) && isset(ep.servClusters["0702"]['attributes'])) {
                attributes = ep.servClusters["0702"]['attributes'];
                cmdName = "Total power"; // Default cmd name
                unit = "KWh"; // Default unit
                div = 1; // Default div
                // TO BE COMPLETED if required
                // if (isset(attributes['0300'])) { // UnitofMeasure
                //     switch (attributes['0303']['value']) {
                //     case 0x00: "KWh"; break; // Pure binary format
                //     case 0x80: "KWh"; break; // BCD format
                //     }
                // }
                // Note: Summation = Summation received * Multiplier / Divisor (formatted using SummationFormatting)
                if (isset(attributes['0301'])) { // Multiplier
                    val = attributes['0301']['value'];
                }
                if (isset(attributes['0302'])) { // Divisor
                    val = attributes['0302']['value'];
                    if (val != 0)
                        div = val;
                }
                if (isset(attributes['0303'])) { // SummationFormatting
                    val = attributes['0303']['value'];
                    if (val != 0) {
                        val = val & 7; // Keep bits 2:0 only => right digits
                        div = Math.pow(10, val);
                    }
                }
                if (isset(attributes['0000'])) {
                    cmds["Get "+cmdName] = newCmd("act_zbReadAttribute", "clustId=0702&attrId=0000");
                    cmds[cmdName] = newCmd("inf_zbAttr-0702-CurrentSummationDelivered", "div="+div);
                    cmds[cmdName]["unit"] = unit;
                    cmds[cmdName]["isVisible"] = 1;
                }
            }

            /* Electrical Measurement cluster */
            if (isset(ep.servClusters["0B04"]) && isset(ep.servClusters["0B04"]['attributes'])) {
                attributes = ep.servClusters["0B04"]['attributes'];
                if (isset(attributes['0505'])) { // RMS Voltage
                    cmdName = "RMS Voltage"; // Default cmd name
                    cmds["Get "+cmdName] = newCmd("act_zbReadAttribute", "clustId=0B04&attrId=0505");
                    cmds[cmdName] = newCmd("inf_zbAttr-0B04-RMSVoltage", "mult=1&div=1");
                    cmds[cmdName]["isVisible"] = 0;

                    // Reporting changeVal=10 V
                    cmds["SetReporting 0B04-RMSVoltage"] = newCmd("act_zbConfigureReporting2", "clustId=0B04&attrId=0505&attrType=21&changeVal=10", "yes");
                }
                if (isset(attributes['0508'])) { // RMS Current
                    cmdName = "RMS Current"; // Default cmd name
                    cmds["Get "+cmdName] = newCmd("act_zbReadAttribute", "clustId=0B04&attrId=0508");
                    cmds[cmdName] = newCmd("inf_zbAttr-0B04-RMSCurrent", "mult=1&div=1");
                    cmds[cmdName]["isVisible"] = 1;

                    // Reporting changeVal=50mA assuming div by 1000
                    cmds["SetReporting 0B04-RMSCurrent"] = newCmd("act_zbConfigureReporting2", "clustId=0B04&attrId=0508&attrType=21&changeVal=50", "yes");
                }
                if (isset(attributes['050B'])) { // Active power
                    cmdName = "Active Power"; // Default cmd name
                    cmds["Get "+cmdName] = newCmd("act_zbReadAttribute", "clustId=0B04&attrId=050B");
                    cmds[cmdName] = newCmd("inf_zbAttr-0B04-ActivePower", "mult=1&div=1");
                    cmds[cmdName]["isVisible"] = 1;

                    cmds["SetReporting 0B04-ActivePower"] = newCmd("act_zbConfigureReporting2", "clustId=0B04&attrId=050B&attrType=29&changeVal=10", "yes");
                }
                cmds["Bind 0B04-ToZigate"] = newCmd("act_zbBindToZigate", "clustId=0B04", "yes");
            }

            // commandsReceived = ep.servClusters[clustId]['commandsReceived'];
            // for (cmd in commandsReceived) {
            //     if (clustId == "0300") {
            //         /* Color cluster */
            //     }
            // }
        }
        console.log(cmds);
        eq.commands = cmds;
        eq.defaultEp = mainEp;
        eq.timeout = minTimeout;

        // Refresh JSON display
        if (typeof zigbee.signature !== "undefined") {
            zbManuf = zigbee.signature['manufacturer'];
            zbModel = zigbee.signature['model'];
        } else {
            zbManuf = "?";
            zbModel = "?";
        }
        js_jsonName = zbModel+"_"+zbManuf;
        document.getElementById("idJsonName").value = js_jsonName;
        document.getElementById("idMainEP").value = eq.defaultEp;
        if (zigbee.powerSource == "battery")
            document.getElementById("idBattery").value = "?";
        document.getElementById("idIcon").value = "?";
        displayCommands();
    } // End zigbeeToModel()

    // Take displayed infos to update internal JSON model
    function display2model() {
        console.log("display2model()");

        /* Format reminder:
            {
                "BASICZBR3": {
                    "type": "Sonoff BASICZBR3 smart switch",
                    "manufacturer": "Sonoff",
                    "model": "BASICZBR3",
                    "timeout": "60",
                    "category": {
                        "automatism": "1"
                    },
                    "configuration": {
                        "icon": "BASICZBR3",
                        "mainEP": "01",
                        "batteryType": "1x3V CR2032"
                    }
                    "commands": {
                        "manufacturer": { "use": "societe" },
                        "modelIdentifier": { "use": "nom" },
                        "getEtatEp05": { "use": "etat", "params": "ep=5" },
                        "bindHumidity": { "use": "BindToZigateHumidity", "params": "ep=2", "execAtCreation": "yes" },
                        "setReportHumidity": { "use": "setReportHumidity", "params": "ep=2", "execAtCreation": "yes" }
                    }
                }
            }
         */

        var jeq2 = new Object();
        jeq2.manufacturer = document.getElementById("idManuf").value;
        jeq2.model = document.getElementById("idModel").value;
        jeq2.type = document.getElementById("idType").value;
        jeq2.genericType = document.getElementById("idGenericType").value;
        timeout = document.getElementById("idTimeout").value;
        if (timeout != '')
            jeq2.timeout = timeout;
        // jeq2.comment = // Optional

        // 'category'
        var category = new Object();
        for (i = 0; i < js_categories.length; i++) {
            cat = js_categories[i];
            checked = document.getElementById("id"+cat).checked;
            if (checked)
                category[cat] = 1;
            // Only generate categories that are set to 1
            // else
            //     category[cat] = 0;
        }
        jeq2.category = category;

        // 'configuration'
        var conf = new Object();
        conf.icon = document.getElementById("idIcon").value;
        conf.mainEP = document.getElementById("idMainEP").value;
        batteryType = document.getElementById("idBattery").value;
        if (batteryType != '')
            conf.batteryType = batteryType;
        // batteryVolt = document.getElementById("idBatteryMax").value;
        // if (batteryVolt != '')
        //     conf.batteryVolt = batteryVolt;
        jeq2.configuration = conf;

        // 'commands'
        jeq2.commands = eq.commands;

        var jeq = new Object();
        jeq[js_jsonName] = jeq2;

        return jeq;
    } // End prepareJSON()

    /* Check that minimum infos are there before writing JSON.
       Returns: true if ok, false if missing infos */
    function checkMissingInfos() {
        var missing = "";
        if (document.getElementById("idManuf").value == "")
            missing += "- Nom du fabricant\n";
        if (document.getElementById("idModel").value == "")
            missing += "- Nom du modèle\n";
        if (document.getElementById("idType").value == "")
            missing += "- Type d'équipement (ex: Smart curtain switch)\n";
        // TODO: category is mandatory

        if (missing == "")
            return true; // Ok

        alert("Les informations suivantes sont manquantes\n"+missing);
        return false;
    }

    /* Update/create JSON file.
       Destination is always "devices_local" */
    function writeModel() {
        console.log("writeModel()");

        /* Check if mandatory infos are there */
        if (checkMissingInfos() == false)
            return;

        js_jsonName = document.getElementById("idJsonName").value;
        js_jsonPath = 'core/config/devices_local/'+js_jsonName+'/'+js_jsonName+'.json';
        js_jsonLocation = "local";

        jeq = display2model();
        console.log("model", jeq);

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'writeDeviceConfig',
                jsonId: js_jsonName,
                jsonLocation: js_jsonLocation,
                devConfig: jeq
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'writeDeviceConfig' !<br>Votre installation semble corrompue.<br>"+error);
                status = -1;
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                } else {
                }
            }
        });
    }

    // /* Create 'discovery.json' file */
    // function writeDiscoveryInfos() {
    //     console.log("writeDiscoveryInfos()");

    //     $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
    //         data: {
    //             action: 'writeFile',
    //             path: 'tmp/discovery.log',
    //             content: JSON.stringify(eq)
    //         },
    //         dataType: 'json',
    //         global: false,
    //         // async: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'writeDiscoveryInfos' !<br>Votre installation semble corrompue.<br>"+error);
    //             status = -1;
    //         },
    //         success: function (json_res) {
    //             res = JSON.parse(json_res.result);
    //             if (res.status != 0) {
    //                 console.log("error="+res.error);
    //             } else {
    //             }
    //         }
    //     });
    // }

    /* Zigbee to 'discovery-xxx.json' */
    function downloadDiscovery() {
        console.log("downloadDiscovery()");
        // console.log("zigbee=", zigbee);

        // Looking for signature
        endPoints = zigbee.endPoints;
        signature = '';
        for (var epId in endPoints) {
            // console.log("EP "+epId);
            ep = endPoints[epId];
            if (!isset(ep.servClusters))
                continue;
            if (!isset(ep.servClusters["0000"]) || !isset(ep.servClusters["0000"]['attributes']))
                continue;

            attributes = ep.servClusters["0000"]['attributes'];
            if (!isset(attributes['0005']))
                break;
            signature = attributes['0005']['value'];
            if (isset(attributes['0004']))
                signature += '_'+attributes['0004']['value'];
            break;
        }

        zigbee.fileSignature = "Abeille discovery file";
        text = JSON.stringify(zigbee);
        let elem = window.document.createElement('a');
        elem.style = "display: none";
        elem.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        if (signature != '')
            elem.setAttribute('download', "discovery-"+signature+".json");
        else
            elem.setAttribute('download', "discovery.json");
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);

        // Tcharp38: Attempt to solve "Not allowed to navigate top frame to data URL" error.
        // TO BE TESTED
        // var hiddenElement = document.createElement('a');
        // hiddenElement.href = 'data:text/plain;'+'charset=utf-8,' + encodeURI(text);
        // hiddenElement.target = '_blank';
        // hiddenElement.download = "discovery.json";
        // hiddenElement.click();
    }

    /* Download JSON */
    function downloadConfig() {
        console.log("downloadConfig()");

        js_jsonName = document.getElementById("idJsonName").value;
        // TODO: Search in following order: devices_local then devices
        js_jsonPath = 'core/config/devices_local/'+js_jsonName+'/'+js_jsonName+'.json';

        window.open('plugins/Abeille/core/php/AbeilleDownload.php?pathfile='+js_jsonPath, "_blank", null);
    }

    /* Force End Point 01. This is a work-around for devices that do not respond to "active endpoints" request */
    function forceEP01() {
        console.log("forceEP01()");

        /* Updating internal datas */
        zigbee.epCount = 1;
        endPoints = new Object;
        endPoints['01'] = new Object();
        zigbee.endPoints = endPoints;

        /* Updating display */
        updateZigbeeDisplay("epList", true);
    }

    /* Request device info */
    function requestInfos(infoType, epId = "01", clustId = "0000", option = "") {
        console.log("requestInfos("+infoType+", ep="+epId+")");

        // 'Cmd'.$device->getLogicalId().'/readAttribute', 'ep=01&clustId=0005&attrId=0000'
        logicalId = "Abeille"+js_zgNb+"_"+js_eqAddr;
        if (infoType == "epList") {
            topic = "Cmd"+logicalId+"_getActiveEndpoints";
            payload = "addr="+js_eqAddr;
        } else if (infoType == "manufacturer") {
            topic = "Cmd"+logicalId+"_readAttribute";
            payload = "ep="+epId+"_clustId=0000_attrId=0004"; // Manufacturer
        } else if (infoType == "modelId") {
            topic = "Cmd"+logicalId+"_readAttribute";
            payload = "ep="+epId+"_clustId=0000_attrId=0005"; // Model
        } else if (infoType == "powerSource") {
            topic = "Cmd"+logicalId+"_readAttribute";
            payload = "ep="+epId+"_clustId=0000_attrId=0007"; // PowerSource
        } else if (infoType == "location") {
            topic = "Cmd"+logicalId+"_readAttribute";
            payload = "ep="+epId+"_clustId=0000_attrId=0010"; // Location
        } else if (infoType == "clustersList") {
            topic = "Cmd"+logicalId+"_getSimpleDescriptor";
            payload = "ep="+epId;
        } else if (infoType == "attribList") {
            topic = "Cmd"+logicalId+"_discoverAttributes";
            payload = "ep="+epId+"_clustId="+clustId+"_startAttrId=0000_maxAttrId=FF_dir="+option;
        } else if (infoType == "attribExtList") {
            topic = "Cmd"+logicalId+"_discoverAttributesExt";
            payload = "ep="+epId+"_clustId="+clustId+"_startAttrId=0000_maxAttrId=FF_dir="+option;
        } else if (infoType == "attribValue") {
            topic = "Cmd"+logicalId+"_readAttribute";
            payload = "ep="+epId+"_clustId="+clustId+"_attrId="+option;
        } else if (infoType == "discoverCommandsReceived") {
            topic = "Cmd"+logicalId+"_discoverCommandsReceived";
            payload = "ep="+epId+"_clustId="+clustId;
        } else if (infoType == "discoverCommandsGenerated") {
            topic = "Cmd"+logicalId+"_discoverCommandsGenerated";
            payload = "ep="+epId+"_clustId="+clustId;
        } else {
            console.log("requestInfos("+infoType+") => UNEXPECTED type !");
            return;
        }

        var xhttp = new XMLHttpRequest();
        var url = "plugins/Abeille/core/php/AbeilleCliToQueue.php";
        xhttp.open("GET", url+"?action=sendMsg&queueId="+js_queueXToCmd+"&topic="+topic+"&payload="+payload, true);
        xhttp.send();
    }

    /* Request read of missing attribute values for given ep-clustId */
    function requestAttribValues(epId = "01", clustId = "0000") {
        console.log("requestAttribValues("+epId+", "+clustId+")");

        ep = zigbee.endPoints[epId];
        clust = ep.servClusters[clustId];
        attributes = clust.attributes;
console.log(attributes);
        for (const [attrId, attr] of Object.entries(attributes)) {
            if (typeof attr.value !== "undefined")
                continue;
console.log("missing value for "+attrId);
            requestInfos("attribValue", epId, clustId, attrId);
        }
    }

    /* Replace a particular class for given element id */
    function changeClass(id, oldClass, newClass) {
        // document.getElementById(id).className = "btn btn-success tooltipstered";
        if ($('#'+id).hasClass(oldClass))
            $('#'+id).removeClass(oldClass);
        $('#'+id).addClass(newClass);
    }

    /* Refresh display from 'zigbee' informations */
    function updateZigbeeDisplay($type, $requestNext = false) {
        console.log('updateZigbeeDisplay('+$type+')');

        if ($type == "epList") {
            zEndPoints = zigbee.endPoints;
            console.log(zEndPoints);

            changeClass("idEPListRB", "btn-warning", "btn-success");
            changeClass("idEPListRB2", "btn-warning", "btn-success");
            var endPoints = "";
            $("#idEndPoints").empty();
            for (const [zEpId, zEp] of Object.entries(zEndPoints)) {
                h = '<br><div id="idEP'+zEpId+'">';
                h += '<label id="idClustEP'+zEpId+'" class="col-lg-2 control-label"></label>';

                h += '<div class="col-lg-10">';
                h += '<a id="idEP'+zEpId+'-RB1" class="btn btn-warning" title="Raffraichi la liste des clusters" onclick="requestInfos(\'clustersList\', \''+zEpId+'\')"><i class="fas fa-sync"></i></a>';
                h += '<br><br>';
                h += '</div>';

                h += '<div style="margin-left:30px">';
                h += '  <div class="row">';
                h += '      <label class="col-lg-2 control-label" for="fname">Profile ID:</label>';
                h += '      <div class="col-lg-10">';
                h += '          <a id="idZbProfId'+zEpId+'-RB" class="btn btn-warning" title="{{Relecture}}" onclick="requestInfos(\'clustersList\', \''+zEpId+'\')"><i class="fas fa-sync"></i></a>';
                h += '          <input type="text" id="idZbProfId'+zEpId+'" value="" readonly>';
                h += '      </div>';
                h += '  </div>';
                h += '  <div class="row">';
                h += '      <label class="col-lg-2 control-label" for="fname">Device ID:</label>';
                h += '      <div class="col-lg-10">';
                h += '          <a id="idZbDevId'+zEpId+'-RB" class="btn btn-warning" title="{{Relecture}}" onclick="requestInfos(\'clustersList\', \''+zEpId+'\')"><i class="fas fa-sync"></i></a>';
                h += '          <input type="text" id="idZbDevId'+zEpId+'" value="" readonly>';
                h += '      </div>';
                h += '  </div>';
                h += '</div>';

                /* Display manufacturer/modelId & location if cluster 0000 is supported */
                h += '<div id="idEP'+zEpId+'Model" style="margin-left:30px; display: none">';
                h += '  <div class="row">';
                h += '      <label class="col-lg-2 control-label" for="fname">Fabricant:</label>';
                h += '      <div class="col-lg-10">';
                h += '          <a id="idZbManuf'+zEpId+'RB" class="btn btn-warning" title="Raffraichi le nom du fabricant" onclick="requestInfos(\'manufacturer\', \''+zEpId+'\')"><i class="fas fa-sync"></i></a>';
                h += '          <input type="text" id="idZbManuf'+zEpId+'" value="" readonly>';
                h += '      </div>';
                h += '  </div>';
                h += '<div class="row">';
                h += '<label class="col-lg-2 control-label" for="fname">Modèle:</label>';
                h += '<div class="col-lg-10">';
                h += '<a id="idZbModel'+zEpId+'RB" class="btn btn-warning" title="Raffraichi le nom du modèle" onclick="requestInfos(\'modelId\', \''+zEpId+'\')"><i class="fas fa-sync"></i></a>';
                h += '<input type="text" id="idZbModel'+zEpId+'" value="" readonly>';
                h += '</div>';
                h += '</div>';
                h += '<div class="row">';
                h += '<label class="col-lg-2 control-label" for="fname">Localisation:</label>';
                h += '<div class="col-lg-10">';
                h += '<a id="idZbLocation'+zEpId+'RB" class="btn btn-warning" title="Raffraichi la localisation" onclick="requestInfos(\'location\', \''+zEpId+'\')"><i class="fas fa-sync"></i></a>';
                h += '<input type="text" id="idZbLocation'+zEpId+'" value="" readonly>';
                h += '</div>';
                h += '</div>';
                h += '</div>';

                /* Display server clusters */
                h += '<div style="margin-left:30px">';
                h += '<label for="fname">Server/input clusters:</label>';
                // h += '<br>';
                h += '<table id="idEp'+zEpId+'-ServClust" class="clustTable">';
                h += '</table>';
                h += '<br>';

                /* Display client clusters */
                h += '<label for="fname">Client/output clusters:</label>';
                h += '<table id="idEp'+zEpId+'-CliClust" class="clustTable">';
                h += '</table>';
                h += '<br>';

                h += '</div>';
                h += '</div>';
                $("#idEndPoints").append(h);
                document.getElementById("idClustEP"+zEpId).innerHTML = "End point "+zEpId+":";
                $("#idEP"+zEpId).show();

                if (endPoints != "")
                    endPoints += ", ";
                endPoints += zEpId;

                if ($requestNext)
                    requestInfos('clustersList', zEpId);
            }
            document.getElementById("idEPList").value = endPoints;
        }
    }

    /* Treat async infos received from server to display them. */
    function receiveInfos() {
        // console.log("receiveInfos()");
        // console.log("Got='"+this.responseText+"'");
        if (this.responseText == '') {
            console.log("receiveInfos() => EMPTY");
            openReturnChannel();
            return;
        }

        res = JSON.parse(this.responseText);
        console.log("receiveInfos()=", res);
        for (const [idx, msg] of Object.entries(res)) {
            // console.log("msg="+msg.type);

            if (msg.type == "activeEndpoints") {
                // 'src' => 'parser',
                // 'type' => 'activeEndpoints',
                // 'net' => $dest,
                // 'addr' => $SrcAddr,
                // 'epList' => $endPointList
                sEpList = msg.epList;
                sEpArr = sEpList.split('/');

                /* Updating internal datas */
                zigbee.epCount = sEpArr.length;
                endPoints = new Object;
                sEpArr.forEach((ep) => {
                    endPoints[ep] = new Object();
                });
                zigbee.endPoints = endPoints;

                /* Updating display & request clusters list*/
                updateZigbeeDisplay("epList", true);
            } else if (msg.type == "simpleDesc") {
                // 'src' => 'parser',
                // 'type' => 'simpleDesc',
                // 'net' => $dest,
                // 'addr' => $SrcAddr,
                // 'ep' => $EPoint,
                // 'profId' => $profId,
                // 'devId' => $deviceId,
                // 'servClustList' => $inputClusters, // Format: 'xxxx/yyyy/zzzz'
                // 'cliClustList' => $outputClusters // Format: 'xxxx/yyyy/zzzz'
                sEp = msg.ep;
                servClustArr = msg.inClustList.split('/');
                cliClustArr = msg.outClustList.split('/');

                /* Updating internal datas */
                if (zigbee.epCount == 0) {
                    // EP list not received yet
                    console.log("EP list not received yet => simpleDesc ignored.")
                    return;
                }
                ep = zigbee.endPoints[msg.ep];
                ep.profId = msg.profId; // Application profile ID
                ep.devId = msg.devId; // Device profile ID
                ep.servClusters = new Object();
                servClustArr.forEach((clustId) => {
                    ep.servClusters[clustId] = new Object();
                });
                ep.cliClusters = new Object();
                cliClustArr.forEach((clustId) => {
                    ep.cliClusters[clustId] = new Object();
                });

                /* Updating display */
                field = document.getElementById("idZbProfId"+sEp);
                field.value = ep.profId;
                changeClass("idZbProfId"+sEp+"-RB", "btn-warning", "btn-success");
                field = document.getElementById("idZbDevId"+sEp);
                field.value = ep.devId;
                changeClass("idZbDevId"+sEp+"-RB", "btn-warning", "btn-success");
                var servClustTable = document.getElementById("idEp"+sEp+"-ServClust");
                var cliClustTable = document.getElementById("idEp"+sEp+"-CliClust");
                /* Cleanup tables */
                var rowCount = servClustTable.rows.length;
                for (var i = rowCount - 1; i >= 0; i--) {
                    servClustTable.deleteRow(i);
                }
                rowCount = cliClustTable.rows.length;
                for (i = rowCount - 1; i >= 0; i--) {
                    cliClustTable.deleteRow(i);
                }
                servClustArr.forEach((clustId) => {
                    console.log("servClust="+clustId);
                    if (clustId == "")
                        return; // Empty => exit foreach()
                    if (clustId == "0000") // Basic cluster supported on this EP
                        $("#idEP"+sEp+"Model").show();
                    var newRow = servClustTable.insertRow(-1);

                    // 1st col = cluster ID
                    cell0 = newRow.insertCell(0);
                    // cell0.style.width = '40px';
                    cell0.innerHTML = clustId;

                    // 2nd col = Buttons discover attributes
                    // cell1 = newRow.insertCell(-1);
                    cell1 = cell0; // TO BE CONTINUED
                    // newCell.setAttribute('title', 'toto'); // Tcharp38 TODO: How to retrive cluster name ?
                    // newCell.innerHTML += '<a id="idServClust'+sEp+'-'+clustId+'RBEx" class="btn btn-warning" title="{{Découverte étendue des attributs}}" onclick="requestInfos(\'attribExtList\', \''+msg.ep+'\', \''+clustId+'\', \'00\')"><i class="fas fa-sync"></i></a>';
                    cell1.innerHTML += '<a id="id'+sEp+'-S'+clustId+'-DAE" class="btn btn-warning" title="{{Découverte étendue des attributs}}" onclick="requestInfos(\'attribExtList\', \''+msg.ep+'\', \''+clustId+'\', \'00\')"><i class="fas fa-sync"></i></a>';
                    // newCell.innerHTML += '<a id="idServClust'+sEp+'-'+clustId+'RB" class="btn btn-warning" title="{{Découverte des attributs}}" onclick="requestInfos(\'attribList\', \''+msg.ep+'\', \''+clustId+'\', \'00\')"><i class="fas fa-sync"></i></a>';
                    cell1.innerHTML += '<a id="id'+sEp+'-S'+clustId+'-DA" class="btn btn-warning" title="{{Découverte des attributs}}" onclick="requestInfos(\'attribList\', \''+msg.ep+'\', \''+clustId+'\', \'00\')"><i class="fas fa-sync"></i></a>';

                    if (clustId == "0000") { // Basic cluster supported on this EP
                        requestInfos('manufacturer', sEp, clustId);
                        requestInfos('modelId', sEp, clustId);
                        requestInfos('location', sEp, clustId);
                    }
                    requestInfos('attribExtList', sEp, clustId, '00');
                    // requestInfos('discoverCommandsReceived', sEp, clustId);
                });
                cliClustArr.forEach((clustId) => {
                    console.log("cliClust="+clustId);
                    var newRow = cliClustTable.insertRow(-1);
                    var newCell = newRow.insertCell(0);
                    newCell.innerHTML = clustId;
                    newCell.innerHTML += '<a id="id'+sEp+'-C'+clustId+'-DA" class="btn btn-warning" title="{{Découverte des attributs}}" onclick="requestInfos(\'attribList\', \''+msg.ep+'\', \''+clustId+'\', \'01\')"><i class="fas fa-sync"></i></a>';
                    requestInfos('attribList', sEp, clustId, '01');
                });

                changeClass("idEP"+sEp+"-RB1", "btn-warning", "btn-success");
            } else if ((msg.type == "discoverAttributesResponse") || (msg.type == "discoverAttributesExtendedResponse")) {
                // 'src' => 'parser',
                // 'type' => 'discoverAttributesResponse' or 'discoverAttributesExtendedResponse'
                // 'net' => $dest,
                // 'addr' => $srcAddress,
                // 'ep' => $srcEndPoint,
                // 'clustId' => $cluster,
                // 'dir' => (hexdec($FCF) >> 3) & 1, // 1=server cluster, 0=client cluster
                // 'attributes' => $attributes
                sEp = msg.ep;
                sDir = msg.dir;
                sClustId = msg.clustId;
                sAttributes = msg.attributes;
                let sAttrCount = sAttributes.length;
                if (msg.type == "discoverAttributesResponse") {
                    console.log("discoverAttributesResponse: clustId="+sClustId+", attrCount="+sAttrCount);
                    extended = false;
                } else {
                    console.log("discoverAttributesExtendedResponse: clustId="+sClustId+", attrCount="+sAttrCount);
                    extended = true;
                }

                // if (sAttrCount == 0) {
                //     openReturnChannel();
                //     return;
                // }

                /* Updating internal datas */
                ep = zigbee.endPoints[sEp];
                if (sDir)
                    clust = ep.servClusters[sClustId];
                else {
                    if (typeof ep.cliClusters === "undefined") {
                        console.log("FIXME: It is a bug ep.cliClusters === undefined for " + sEp);
                        ep.cliClusters = new Object();
                    }
                    if (typeof ep.cliClusters[sClustId] === "undefined") {
                        console.log("FIXME: It is a bug ep.cliClusters[sClustId] === undefined for " + sEp + "/" + sClustId);
                        ep.cliClusters[sClustId] = new Object();
                    }
                    clust = ep.cliClusters[sClustId];
                }

                if (typeof clust.attributes === "undefined")
                    clust.attributes = new Object();
                attributes = clust.attributes;
                // for (attrIdx = 0; attrIdx < sAttrCount; attrIdx++) {
                //     sAttr = sAttributes[attrIdx];
                //     if (typeof attributes[sAttr.id] === "undefined")
                //         attributes[sAttr.id] = new Object();
                //     if (typeof attributes[sAttr.value] === "undefined")
                //         requestInfos('attribValue', sEp, sClustId, sAttr.id); // Read attribute current value
                // }
                for (const [sAttrId, sAttr] of Object.entries(sAttributes)) {
                    if (typeof attributes[sAttrId] === "undefined")
                        attributes[sAttrId] = new Object();
                    if (extended) {
                        attributes[sAttrId]['dataType'] = sAttr['dataType'];
                        attributes[sAttrId]['access'] = sAttr['access'];
                    }
                    if (typeof attributes[sAttrId]['value'] === "undefined")
                        requestInfos('attribValue', sEp, sClustId, sAttrId); // Read attribute current value
                }

                /* Updating display */
                var row;
                if (sDir) { // Server to client = server clusters
                    var clustTable = document.getElementById("idEp"+sEp+"-ServClust");
                } else {
                    var clustTable = document.getElementById("idEp"+sEp+"-CliClust");
                }

                if (typeof clustTable === 'undefined') {
                    console.log("Probably not received EP list yet (=> no cluster table)");
                    openReturnChannel();
                    return;
                }
                // Find proper line
                for (var i = 0; row = clustTable.rows[i]; i++) {
                    cellStr = row.cells[0].innerHTML;
                    // console.log("i="+i+", cell[0]="+cellStr.substring(0, 4));
                    if (cellStr.substring(0, 4) == sClustId) {
                        break;
                    }
                }
                // Empty row
                if (typeof row !== 'undefined') {
                    var colCount = row.cells.length;
                    for (var i = colCount - 1; i >= 1; i--) {
                        row.deleteCell(i);
                    }
                } else {
                    console.log("FIXME: It is a bug row === undefined for " + sEp + "/" + sClustId);
                }
                // Fills row
                // for (attrIdx = 0; attrIdx < sAttrCount; attrIdx++) {
                //     rattr = sAttributes[attrIdx];
                //     var newCell = row.insertCell(-1);
                //     newCell.innerHTML = rattr.id;
                //     if (sDir && (attrIdx == sAttrCount - 1)) // Server attributes only
                //         newCell.innerHTML += '<a id="idServClust'+sEp+'-'+sClustId+'RB2" class="btn btn-warning" title="Lecture des valeurs des attributs" onclick="requestAttribValues(\''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                // }
                for (const [sAttrId, sAttr] of Object.entries(sAttributes)) {
                    // rattr = sAttributes[sAttrId];
                    var newCell = row.insertCell(-1);
                    newCell.innerHTML = sAttrId;
                    // if (sDir && (attrIdx == sAttrCount - 1)) // Server attributes only
                    //     newCell.innerHTML += '<a id="idServClust'+sEp+'-'+sClustId+'RB2" class="btn btn-warning" title="Lecture des valeurs des attributs" onclick="requestAttribValues(\''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                }
                if (sDir) {
                    if (sAttrCount != 0) {
                        var newCell = row.insertCell(-1);
                        newCell.innerHTML = '<a id="id'+sEp+'-Serv'+sClustId+'-RB2" class="btn btn-warning" title="Lecture des valeurs des attributs" onclick="requestAttribValues(\''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                        newCell.innerHTML += '<a id="idEP'+sEp+'-Serv'+sClustId+'-RB4" class="btn btn-warning" title="Interrogation des commandes reçues" onclick="requestInfos(\'discoverCommandsReceived\', \''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                        newCell.innerHTML += '<a id="idEP'+sEp+'-Serv'+sClustId+'-RB5" class="btn btn-warning" title="Interrogation des commandes générées" onclick="requestInfos(\'discoverCommandsGenerated\', \''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                    }
                    requestInfos('discoverCommandsReceived', sEp, sClustId);
                    requestInfos('discoverCommandsGenerated', sEp, sClustId);
                    // changeClass("idServClust"+sEp+"-"+sClustId+"RBEx", "btn-warning", "btn-success");
                    changeClass("id"+sEp+"-S"+sClustId+"-DAE", "btn-warning", "btn-success");
                    // changeClass("idServClust"+sEp+"-"+sClustId+"RB", "btn-warning", "btn-success");
                    changeClass("id"+sEp+"-S"+sClustId+"-DA", "btn-warning", "btn-success");
                } else {
                    changeClass("id"+sEp+"-C"+sClustId+"-DA", "btn-warning", "btn-success");
                }
            } else if (msg.type == "attributeReport") {
                // 'src' => 'parser',
                // 'type' => 'attributeReport', // 8100 or 8102
                // 'net' => $dest,
                // 'addr' => $SrcAddr,
                // 'ep' => $EPoint,
                // 'clustId' => $ClusterId,
                // 'attrId' => $AttributId,
                // 'status' => "00", "86"
                // 'value' => $data
                sEp = msg.ep;
                sClustId = msg.clustId;
                sAttrId = msg.attrId;
                sStatus = msg.status;
                sValue = msg.value;

                // Updating internal infos
                if (sStatus == "00") {
                    if (typeof zigbee.endPoints === "undefined")
                        zigbee.endPoints = new Object();
                    if (typeof zigbee.endPoints[sEp] === "undefined")
                        zigbee.endPoints[sEp] = new Object();
                    ep = zigbee.endPoints[sEp];
                    if (typeof ep.servClusters === "undefined")
                        ep.servClusters = new Object();
                    if (typeof ep.servClusters[sClustId] === "undefined")
                        ep.servClusters[sClustId] = new Object();
                    clust = ep.servClusters[sClustId];
                    if (typeof clust.attributes === "undefined")
                        clust.attributes = new Object();
                    attributes = clust.attributes;
                    if (typeof attributes[sAttrId] === 'undefined')
                        attr = new Object();
                    else
                        attr = attributes[sAttrId];
                    attr['value'] = sValue;
                    attributes[sAttrId] = attr;
                    ep.servClusters[sClustId]['attributes'] = attributes;

                    /* Checking Cluster-0000/PowerSource */
                    if ((sClustId == "0000") && (sAttrId == "0007")) {
                        if (sValue == "03")
                            zigbee.powerSource = "battery";
                        else
                            zigbee.powerSource = "mains";
                    }
                } else
                    attributes = null;

                // Updating display
                field = null;
                if (sClustId == "0000") {
                    if (sAttrId == "0004") {
                        field = document.getElementById("idZbManuf"+sEp);
                        idRB = "idZbManuf"+sEp+"RB"; // Refresh button
                    } else if (sAttrId == "0005") {
                        field = document.getElementById("idZbModel"+sEp);
                        idRB = "idZbModel"+sEp+"RB"; // Refresh button
                    } else if (sAttrId == "0007") {
                        field = document.getElementById("idZbPowerSource");
                        idRB = null; // No refresh button
                        sValue = zigbee.powerSource;
                    } else if (sAttrId == "0010") {
                        field = document.getElementById("idZbLocation"+sEp);
                        idRB = "idZbLocation"+sEp+"RB"; // Refresh button
                    }
                }
                if (field !== null) {
                    if (msg.status != "00")
                        field.value = "-- Non supporté --";
                    else
                        field.value = sValue;
                    if (idRB)
                        changeClass(idRB, "btn-warning", "btn-success");
                }

                // If all attributes values are known, change button class
                if (attributes !== null) {
                    allDone = true;
                    for (const [attrId, attr] of Object.entries(attributes)) {
                        if (typeof attr.value === "undefined") {
                            allDone = false;
                            break;
                        }
                    }
                    if (allDone)
                        changeClass("id"+sEp+"-Serv"+sClustId+"-RB2", "btn-warning", "btn-success");
                }
            } else if (msg.type == "readAttributesResponse") {
                // 'src' => 'parser',
                // 'type' => 'readAttributesResponse',
                // 'net' => $dest,
                // 'addr' => $srcAddr,
                // 'ep' => $srcEp,
                // 'clustId' => $cluster,
                // 'attributes' => $attributes
                sEp = msg.ep;
                sClustId = msg.clustId;
                sAttributes = msg.attributes;

                if (typeof zigbee.endPoints === "undefined")
                    zigbee.endPoints = new Object();
                if (typeof zigbee.endPoints[sEp] === "undefined")
                    zigbee.endPoints[sEp] = new Object();
                ep = zigbee.endPoints[sEp];
                if (typeof ep.servClusters === "undefined")
                    ep.servClusters = new Object();
                if (typeof ep.servClusters[sClustId] === "undefined")
                    ep.servClusters[sClustId] = new Object();
                clust = ep.servClusters[sClustId];
                if (typeof clust.attributes === "undefined")
                    clust.attributes = new Object();
                attributes = clust.attributes;

                for (const [sAttrId, sAttr] of Object.entries(sAttributes)) {
                    sStatus = sAttr.status;
                    sValue = sAttr.value;

                    // Updating internal infos
                    if (sStatus == "00") {
                        if (typeof attributes[sAttrId] === 'undefined')
                            attr = new Object();
                        else
                            attr = attributes[sAttrId];
                        attr['value'] = sValue;
                        attributes[sAttrId] = attr;
                        ep.servClusters[sClustId]['attributes'] = attributes;

                        /* Checking Cluster-000/PowerSource */
                        if ((sClustId == "0000") && (sAttrId == "0007")) {
                            if (sValue == "03")
                                zigbee.powerSource = "battery";
                            else
                                zigbee.powerSource = "mains";
                        }
                    }

                    // Updating display
                    field = null;
                    if (sClustId == "0000") {
                        if (sAttrId == "0004") {
                            field = document.getElementById("idZbManuf"+sEp);
                            idRB = "idZbManuf"+sEp+"RB"; // Refresh button
                        } else if (sAttrId == "0005") {
                            field = document.getElementById("idZbModel"+sEp);
                            idRB = "idZbModel"+sEp+"RB"; // Refresh button
                        } else if (sAttrId == "0007") {
                            field = document.getElementById("idZbPowerSource");
                            idRB = null; // No refresh button
                            sValue = zigbee.powerSource;
                        } else if (sAttrId == "0010") {
                            field = document.getElementById("idZbLocation"+sEp);
                            idRB = "idZbLocation"+sEp+"RB"; // Refresh button
                        }
                    }
                    if (field !== null) {
                        if (sStatus != "00")
                            field.value = "-- Non supporté --";
                        else
                            field.value = sValue;
                        if (idRB)
                            changeClass(idRB, "btn-warning", "btn-success");
                    }
                }

                // If all attributes values are known, change button class
                if (attributes !== null) {
                    allDone = true;
                    for (const [attrId, attr] of Object.entries(attributes)) {
                        if (typeof attr.value === "undefined") {
                            allDone = false;
                            break;
                        }
                    }
                    if (allDone)
                        changeClass("id"+sEp+"-Serv"+sClustId+"-RB2", "btn-warning", "btn-success");
                }
            } else if (msg.type == "deviceAnnounce") {
                // 'src' => 'parser',
                // 'type' => 'deviceAnnounce',
                // 'net' => $dest,
                // 'addr' => $Addr,
                // 'ieee' => $IEEE
                console.log("deviceAnnounce: new addr="+msg.addr)
                js_eqAddr = msg.addr;

                // Updating internal infos
                eq.addr = js_eqAddr;

                // Updating display
                document.getElementById("idAddr").value = msg.addr;
            } else if (msg.type == "discoverCommandsReceivedResponse") {
                // 'src' => 'parser',
                // 'type' => 'discoverCommandsReceivedResponse',
                // 'net' => $dest,
                // 'addr' => $srcAddress,
                // 'ep' => $srcEndPoint,
                // 'clustId' => $cluster,
                // 'commands' => $commands
                sEp = msg.ep;
                sClustId = msg.clustId;
                sCommands = msg.commands;
                // console.log("commandsReceived: clust="+sClustId);
                // console.log(sCommands);

                /* Updating internal datas */
                if (typeof zigbee.endPoints[sEp] === "undefined") {
                    openReturnChannel();
                    return;
                }

                ep = zigbee.endPoints[sEp];
                clust = ep.servClusters[sClustId];
                if (typeof clust.commandsReceived === "undefined")
                    clust.commandsReceived = new Object();
                clust.commandsReceived = sCommands;

                /* Updating display: button moved to green */
                id='idEP'+sEp+'-Serv'+sClustId+'-RB4';
                changeClass(id, "btn-warning", "btn-success");
            } else if (msg.type == "discoverCommandsGeneratedResponse") {
                // 'src' => 'parser',
                // 'type' => 'discoverCommandsGeneratedResponse',
                // 'net' => $dest,
                // 'addr' => $srcAddress,
                // 'ep' => $srcEndPoint,
                // 'clustId' => $cluster,
                // 'commands' => $commands
                sEp = msg.ep;
                sClustId = msg.clustId;
                sCommands = msg.commands;
                // console.log("commandsReceived: clust="+sClustId);
                // console.log(sCommands);

                /* Updating internal datas */
                if (typeof zigbee.endPoints[sEp] === "undefined") {
                    openReturnChannel();
                    return;
                }

                ep = zigbee.endPoints[sEp];
                clust = ep.servClusters[sClustId];
                if (typeof clust.commandsGenerated === "undefined")
                    clust.commandsGenerated = new Object();
                clust.commandsGenerated = sCommands;

                /* Updating display: button moved to green */
                id='idEP'+sEp+'-Serv'+sClustId+'-RB5';
                changeClass(id, "btn-warning", "btn-success");
            } else if (msg.type == "defaultResponse") {
                // 'src' => 'parser',
                // 'type' => 'defaultResponse',
                // 'net' => $dest,
                // 'addr' => $srcAddr,
                // 'ep' => $srcEp,
                // 'clustId' => $cluster,
                // 'cmd' => $cmdId,
                // 'status' => $status
                sEp = msg.ep;
                sClustId = msg.clustId;
                sCmd = msg.cmd;

                /* Updating internal datas & display */
                if ((typeof zigbee.endPoints === "undefined") || (typeof zigbee.endPoints[sEp] === "undefined"))
                    zigbee.endPoints[sEp] = new Object();
                ep = zigbee.endPoints[sEp];
                clust = ep.servClusters[sClustId];
                if (sCmd == "11") { // Discover Commands Received
                    if (typeof clust.commandsReceived === "undefined")
                        clust.commandsReceived = new Object();
                    clust.commandsReceived = "UNSUPPORTED";

                    id='idEP'+sEp+'-Serv'+sClustId+'-RB4';
                    changeClass(id, "btn-warning", "btn-success");
                    button = document.getElementById(id);
                    if (button)
                        button.setAttribute('disabled', true);
                } else if (sCmd == "13") { // Discover Commands Generated
                    if (typeof clust.commandsGenerated === "undefined")
                        clust.commandsGenerated = new Object();
                    clust.commandsGenerated = "UNSUPPORTED";

                    id='idEP'+sEp+'-Serv'+sClustId+'-RB5';
                    changeClass(id, "btn-warning", "btn-success");
                    button = document.getElementById(id);
                    if (button)
                        button.setAttribute('disabled', true);
                } else if (sCmd == "15") { // Discover Attributes Extended
                    // From server or client ?
                    if (msg.fromServer)
                        id = 'id'+sEp+'-S'+sClustId+'-DAE';
                    else
                        id = 'id'+sEp+'-C'+sClustId+'-DAE';

                    changeClass(id, "btn-warning", "btn-success");
                    button = document.getElementById(id);
                    if (button)
                        button.setAttribute('disabled', true);
                } else if (sCmd == "0C") { // Discover Attributes
                    // From server or client ?
                    if (msg.fromServer)
                        id = 'id'+sEp+'-S'+sClustId+'-DA';
                    else
                        id = 'id'+sEp+'-C'+sClustId+'-DA';

                    changeClass(id, "btn-warning", "btn-success");
                    button = document.getElementById(id);
                    if (button)
                        button.setAttribute('disabled', true);
                }
            }
        }

        openReturnChannel();
    }

    // function returnChannelStateChange() {
    //     console.log("returnChannelStateChange(): "+this.readyState);
    // }

    function openReturnChannel() {
        console.log("openReturnChannel()");

        var url = 'plugins/Abeille/core/php/AbeilleQueueToCli.php';
        var request = new XMLHttpRequest();
        request.open('GET', url, true);
        request.responseType = 'text';
        request.onload = receiveInfos;
        // request.onreadystatechange = returnChannelStateChange;
        request.send();
    }

    $(document).ready( function() {
        console.log("document.ready()");
        openReturnChannel();

        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?queueId="+js_queueXToParser+"&msg=type:sendToCli_net:Abeille"+js_zgNb+"_addr:"+js_eqAddr+"_ieee:"+js_eqIeee, true);
        xhttp.send();

        requestInfos('epList');
    });

    /* Update Jeedom infos based on current JSON part */
    function updateJeedom() {
        /* TODO: To be updated
            - configuration:ab::eqModel
            - reload JSON to update commands
         */
    }

    /* 'discovery.json' file upload */
    function handleFileSelect(evt) {
        console.log('handleFileSelect()');
        var files = evt.target.files; // FileList object
        if (files.length > 1) {
            alert("Un seul fichier doit être selectionné.");
            return;
        }

        f = files[0];
        var reader = new FileReader();
        reader.onload = function(){
            zigbee = JSON.parse(reader.result);
            console.log(reader.result.substring(0, 200));
        };
        reader.readAsText(f);
    }
    /*document.getElementById('files').addEventListener('change', handleFileSelect, false);*/

    function showTab(tabName) {
        console.log("showTab("+tabName+")");
        if (tabName == "zigbee") {
            $('#idZigbee').show();
            $('#idJson').hide();
        } else {
            $('#idZigbee').hide();
            $('#idJson').show();
        }
    }

    /* Load a 'discovery.json' */
    function importDiscovery() {
        console.log("importDiscovery()");

        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        input.onchange = e => {

            var file = e.target.files[0];
            // file.name = the file's name including extension
            // file.size = the size in bytes
            // file.type = file type ex. 'application/pdf'
            console.log("file="+file.name);
            console.log( );

            var reader = new FileReader();
            reader.onload = function(e) {
                var contents = e.target.result;
                // console.log("contents="+contents);
                zigbee = JSON.parse(contents);
                console.log("zigbee=", zigbee);

                // Reset general values
                document.getElementById("idManuf").value = "";
                document.getElementById("idModel").value = "";
                document.getElementById("idType").value = "";
                document.getElementById("idGenericType").value = "";
                document.getElementById("idIcon").value = "";
                document.getElementById("idBattery").value = "";
                document.getElementById("idMainEP").value = "";

                // Generate & refresh commands
                zigbeeToModel();
            };
            reader.readAsText(file);
        }
        input.click();
    }

</script>
