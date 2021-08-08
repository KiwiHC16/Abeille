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
    $jsonName = $eqLogic->getConfiguration('modeleJson', '');
    $jsonPath = 'core/config/devices/'.$jsonName.'/'.$jsonName.'.json'; // Relative to Abeille's root
    if (!file_exists(__DIR__.'/../../'.$jsonPath))
        $jsonPath = 'core/config/devices_local/'.$jsonName.'/'.$jsonName.'.json';
    $eqIeee = $eqLogic->getConfiguration('IEEE', '');

    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_zgNb = '.$zgNb.';</script>'; // PHP to JS
    echo '<script>var js_eqId = '.$eqId.';</script>'; // PHP to JS
    echo '<script>var js_eqAddr = "'.$eqAddr.'";</script>'; // PHP to JS
    echo '<script>var js_eqIeee = "'.$eqIeee.'";</script>'; // PHP to JS
    echo '<script>var js_jsonName = "'.$jsonName.'";</script>'; // PHP to JS
    echo '<script>var js_jsonPath = "'.$jsonPath.'";</script>'; // PHP to JS
    echo '<script>var js_queueKeyXmlToCmd = "'.queueKeyXmlToCmd.'";</script>'; // PHP to JS
    echo '<script>var js_queueKeyCtrlToParser = "'.$abQueues['ctrlToParser']['id'].'";</script>'; // PHP to JS

    $pluginDir = __DIR__."/../../"; // Plugin root dir
    echo '<script>let js_pluginDir = "'.$pluginDir.'";</script>';

    // require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    define("MAXEP", 10); // Max number of End Points
?>

<!-- <div class="col-xs-12"> -->
    <h3>Assistant de découverte d'équipement (beta)</h3>
    <br>

    <style>
        .b {
            border: 1px solid black;
            border-radius: 10px;
        }
        .b h3 {
            text-align: center;
            /* height: 20px;
            line-height: 20px;
            font-size: 15px; */
        }
    </style>
    <form>
        <!-- <div class="row"> -->
        <div class="col-lg-6">
            <div class="b">
                <h3><span>Jeedom</span></h1>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">ID:</label>
                    <div class="col-lg-2">
                        <?php echo '<input type="text" value="'.$eqId.'" readonly>'; ?>
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Nom:</label>
                    <div class="col-lg-2">
                        <?php echo '<input type="text" value="'.$eqLogic->getName().'" readonly>'; ?>
                    </div>
                </div>
            <!-- </div> -->

            <!-- <div class="b">
                <h3><span>Fichier JSON</span></h1>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Fichier JSON:</label>
                    <div class="col-lg-10">
                        <?php
                            if ($jsonName == '')
                                echo '<input type="text" value="-- Non défini --" readonly>';
                            else if (!file_exists(__DIR__.'/../../core/config/devices/'.$jsonName.'/'.$jsonName.'.json'))
                                echo '<input type="text" value="'.$jsonName.' (n\'existe pas)" readonly>';
                            else
                                echo '<input type="text" value="'.$jsonName.'" readonly>';
                        ?>
                        <a class="btn btn-warning" title="(Re)lire" onclick="readJSON()">(Re)lire</a>
                        <a class="btn btn-warning" title="Mettre à jour" onclick="writeJSON()">Mettre à jour</a>
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
                        <input type="text" value="" id="idName">
                    </div>
                </div>

                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Timeout (min):</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idTimeout">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Catégorie:</label>
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
                    <label class="col-lg-2 control-label" for="fname">Icone:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idIcon">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Type batterie:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idBattery">
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-2 control-label" for="fname">Max batterie:</label>
                    <div class="col-lg-10">
                        <input type="text" value="" id="idBatteryMax">
                    </div>
                </div>

                <br>
                <div id="idCommands">
                </div>
            </div> -->
        <!-- </div> -->

        <!-- Colonne Zigbee -->
        <!-- <div class="col-lg-6 b"> -->
            <h3><span>Zigbee</span></h1>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Adresse:</label>
                <div class="col-lg-2">
                    <?php echo '<input id="idAddr" type="text" value="'.$eqAddr.'" readonly>'; ?>
                </div>
                <div class="col-lg-2">
                    <a class="btn btn-success pull-right" title="Télécharge 'discovery.json'" onclick="downloadInfos()"><i class="fas fa-cloud-download-alt"></i> Télécharger</a>
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
                <div class="col-lg-10">
                    <!-- <a class="btn btn-warning" title="Raffraichi la liste des End Points" onclick="refreshEPList()"><i class="fas fa-sync"></i></a> -->
                    <a class="btn btn-warning" title="Raffraichi la liste des End Points" onclick="requestInfos('epList')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idEPList" value="" readonly>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Fabricant:</label>
                <div class="col-lg-10">
                    <!-- <a class="btn btn-warning" title="Raffraichi le nom du fabricant" onclick="refreshXName('Manuf')"><i class="fas fa-sync"></i></a> -->
                    <a class="btn btn-warning" title="Raffraichi le nom du fabricant" onclick="requestInfos('manufacturer')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idZigbeeManuf" value="" readonly>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Modèle:</label>
                <div class="col-lg-10">
                    <!-- <a class="btn btn-warning" title="Raffraichi le nom du model" onclick="refreshXName('Model')"><i class="fas fa-sync"></i></a> -->
                    <a class="btn btn-warning" title="Raffraichi le nom du model" onclick="requestInfos('modelId')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idZigbeeModel" value="" readonly>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Localisation:</label>
                <div class="col-lg-10">
                    <!-- <a class="btn btn-warning" title="Raffraichi la localisation" onclick="refreshXName('Location')"><i class="fas fa-sync"></i></a> -->
                    <a class="btn btn-warning" title="Raffraichi la localisation" onclick="requestInfos('location')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idZigbeeLocation" value="" readonly>
                </div>
            </div>

            <style>
                table, td {
                    border: 1px solid black;
                }
            </style>

            <div class="row" id="idEndPoints">
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
    eq.epCount = 0; // Number of EP, number
    eq.epList = new Array(); // Array of objects
        // ep = eq.epList[epIdx] = new Object(); // End Point object
        // ep.id = 0; // EP id/number
        // ep.inClustCount = 0; // IN clusters count
        // ep.inClustList = new Array();
        // ep.outClustCount = 0; // OUT clusters count
        // ep.outClustList = new Array();
        //     clust = new Object();
        //     clust.id = "0000"; // Cluster id, hex string
        //     clust.attrList = new Array(); // Attributs for this cluster
        //         a = new Object(); // Attribut object
        //         a.id = "0000"; // Attribut id, hex string
        //         a.type = "00"; // Attribut type, hex string

    /* Read JSON if defined */
    if (js_jsonName != '')
        readJSON();

    // /* Attempt to detect main supported attributs */
    // function refreshAttributsList(epIdx, outClust, clustIdx) {
    //     console.log("refreshAttributsList(epIdx="+epIdx+", outClust="+outClust+", clustIdx="+clustIdx+")");

    //     ep = eq.epList[epIdx];
    //     epNb = ep.id;
    //     if (outClust)
    //         clust = ep.outClustList[clustIdx];
    //     else
    //         clust = ep.inClustList[clustIdx];
    //     clustId = clust.id;

    //     // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
    //     // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
    //     if (outClust) {
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
    // function refreshAttributsList0140(epIdx, outClust, clustIdx) {
    //     console.log("refreshAttributsList0140(epIdx="+epIdx+", outClust="+outClust+", clustIdx="+clustIdx+")");

    //     ep = eq.epList[epIdx];
    //     epNb = ep.id;
    //     if (outClust)
    //         clust = ep.outClustList[clustIdx];
    //     else
    //         clust = ep.inClustList[clustIdx];
    //     clustId = clust.id;

    //     // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
    //     // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
    //     if (outClust) {
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

    // function getAttributsList(epIdx, outClust, clustIdx) {
    //     console.log("getAttributsList(epIdx="+epIdx+", outClust="+outClust+", clustIdx="+clustIdx+")");

    //     ep = eq.epList[epIdx];
    //     epNb = ep.id;
    //     if (outClust)
    //         clust = ep.outClustList[clustIdx];
    //     else
    //         clust = ep.inClustList[clustIdx];
    //     clustId = clust.id;
    //     document.getElementById("idStatus").value = "EP"+epNb+"/Clust"+clustId+": recherche des 'Attributs'";

    //     // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
    //     // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
    //     if (outClust) {
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

//         ep = eq.epList[epIdx];
//         epNb = ep.id;
//         document.getElementById("idStatus").value = "EP"+epNb+": recherche des 'Clusters'";

//         // idServClustx => table of input clusters (col1=clustId, col2+=attribut)
//         // idCliClustx => table of output clusters (col1=clustId, col2+=attribut)
//         var inClustTable = document.getElementById("idServClust"+epIdx);
//         var outClustTable = document.getElementById("idCliClust"+epIdx);
//         /* Cleanup tables */
//         var rowCount = inClustTable.rows.length;
//         for (var i = rowCount - 1; i >= 0; i--) {
//             inClustTable.deleteRow(i);
//         }
//         rowCount = outClustTable.rows.length;
//         for (i = rowCount - 1; i >= 0; i--) {
//             outClustTable.deleteRow(i);
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
//                     ep.inClustCount = resp.InClustCount;
//                     ep.inClustList = []; // List of objects
//                     ep.outClustCount = resp.OutClustCount;
//                     ep.outClustList = []; // List of objects
//                     for (clustIdx = 0; clustIdx < resp.InClustCount; clustIdx++) {
//                         clust = new Object();
//                         clust.id = resp.InClustList[clustIdx];
//                         clust.attrList = new Array();
//                         ep.inClustList.push(clust);

//                         var newRow = inClustTable.insertRow(-1);
//                         var newCol = newRow.insertCell(0);
// 	                    newCol.innerHTML = resp.InClustList[clustIdx]
//                         // newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="refreshAttributsList('+epIdx+', 0, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
//                         newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs 0140" onclick="refreshAttributsList0140('+epIdx+', 0, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
//                     }
//                     for (clustIdx = 0; clustIdx < resp.OutClustCount; clustIdx++) {
//                         clust = new Object();
//                         clust.id = resp.OutClustList[clustIdx];
//                         clust.attrList = new Array();
//                         ep.outClustList.push(clust);

//                         var newRow = outClustTable.insertRow(-1);
//                         var newCol = newRow.insertCell(0);
// 	                    newCol.innerHTML = resp.OutClustList[clustIdx];
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

    // /* Request EP list from EQ.
    //    Returns: Ajax promise */
    // function refreshEPList_old() {
    //     console.log("refreshEPList()");

    //     document.getElementById("idStatus").value = "Recherche des 'End Points'";
    //     return $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
    //         data: {
    //             action: 'getEPList',
    //             zgNb: js_zgNb,
    //             eqAddr: js_eqAddr,
    //         },
    //         dataType: 'json',
    //         global: false,
    //         // async: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'refreshEPList' !<br>Votre installation semble corrompue.<br>"+error);
    //             document.getElementById("idEPList").value = "ERREUR";
    //             document.getElementById("idStatus").value = "ERREUR 'End Points'";
    //         },
    //         success: function (json_res) {
    //             res = JSON.parse(json_res.result);
    //             if (res.status != 0) {
    //                 console.log("error="+res.error);
    //                 document.getElementById("idEPList").value = "ERREUR";
    //                 document.getElementById("idStatus").value = "ERREUR 'End Points'";
    //             } else {
    //                 console.log("res.resp follows:");
    //                 console.log(res.resp);
    //                 var resp = res.resp;

    //                 eq.epCount = resp.EPCount;
    //                 eq.epList = []; // Array of objects
    //                 for (epIdx = 0; epIdx < resp.EPCount; epIdx++) {

    //                     ep = new Object();
    //                     ep.id = resp.EPList[epIdx];
    //                     eq.epList.push(ep);
    //                 }

    //                 /* Updating display */
    //                 var endpoints = "";
    //                 for (epIdx = 0; epIdx < resp.EPCount; epIdx++) {
    //                     if (endpoints != "")
    //                         enpoints += ", ";
    //                     endpoints += resp.EPList[epIdx];

    //                     document.getElementById("idClustEP"+epIdx).innerHTML = "Clusters EP"+resp.EPList[epIdx]+":";
    //                     $("#idEP"+epIdx).show();
    //                 }
    //                 for (; epIdx < resp.EPCount; epIdx++) {
    //                     $("#idEP"+epIdx).hide();
    //                 }
    //                 document.getElementById("idEPList").value = endpoints;
    //                 document.getElementById("idStatus").value = "";
    //             }
    //         }
    //     });
    // }

    // /* Request manufacturer or model name.
    //    Returns: Ajax promise */
    // function refreshXName(x) {
    //     console.log("refreshXName(x="+x+")");

    //     /* First of all, ensure eq is responding */
    //     // status = await waitAlive(10);
    //     // if ($status != 0)
    //     //     return;

    //     epNb = 1;
    //     if (x == "Manuf") {
    //         document.getElementById("idStatus").value = "Mise-à-jour du fabricant";
    //         var attrId = "0004";
    //         var field = document.getElementById("idZigbeeManuf");
    //     } else if (x == "Model") {
    //         document.getElementById("idStatus").value = "Mise-à-jour du modèle";
    //         var attrId = "0005";
    //         var field = document.getElementById("idZigbeeModel");
    //     } else {
    //         document.getElementById("idStatus").value = "Mise-à-jour de la localisation";
    //         var attrId = "0010";
    //         var field = document.getElementById("idZigbeeLocation");
    //     }

    //     // return new Promise((resolve, reject) => {
    //     return $.ajax({
    //             type: 'POST',
    //             url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
    //             data: {
    //                 action: 'readAttributResponse',
    //                 zgNb: js_zgNb,
    //                 eqAddr: js_eqAddr,
    //                 eqEP: epNb, // EP number
    //                 clustId: "0000", // Basic cluster
    //                 attrId: attrId,
    //             },
    //             dataType: 'json',
    //             global: false,
    //             // async: false,
    //             error: function (request, status, error) {
    //                 console.log("refreshXName(x="+x+") ERROR: "+error);
    //                 bootbox.alert("ERREUR 'readAttributResponse' !<br>Votre installation semble corrompue.<br>"+error);
    //                 document.getElementById("idStatus").value = "ERREUR fabricant/modèle";
    //                 // reject();
    //             },
    //             success: function (json_res) {
    //                 res = JSON.parse(json_res.result);
    //                 if (res.status != 0) {
    //                     console.log("refreshXName(x="+x+") ERROR: "+res.error);
    //                     document.getElementById("idStatus").value = "ERREUR modèle";
    //                     // reject();
    //                 } else {
    //                     console.log("res.resp follows:");
    //                     console.log(res.resp);
    //                     var resp = res.resp;
    //                     var attr = resp.Attributes[0];
    //                     if (attr.Status != "00")
    //                         field.value = "-- Non supporté --";
    //                     else
    //                         field.value = attr.Data;

    //                     document.getElementById("idStatus").value = "";
    //                     console.log("refreshXName(x="+x+") => "+field.value);
    //                     // resolve();
    //                 }
    //             }
    //         });
    //     // });
    // }

    // /* "ping" equipment by requesting ZCLVersion (clust 0000, attr 0000).
    //    Returns: 0=OK, -1=ERROR */
    // function pingEQ(x) {
    //     console.log("pingEQ(x="+x+")");

    //     epNb = 1;
    //     document.getElementById("idStatus").value = "Pinging";
    //     $.ajax({
    //         type: 'POST',
    //         url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
    //         data: {
    //             action: 'readAttributResponse',
    //             zgNb: js_zgNb,
    //             eqAddr: js_eqAddr,
    //             eqEP: epNb, // EP number
    //             clustId: "0000", // Basic cluster
    //             attrId: "0000", // ZCLVersion
    //         },
    //         dataType: 'json',
    //         global: false,
    //         async: false,
    //         error: function (request, status, error) {
    //             bootbox.alert("ERREUR 'readAttributResponse' !<br>Votre installation semble corrompue.<br>"+error);
    //             status = -1;
    //         },
    //         success: function (json_res) {
    //             res = JSON.parse(json_res.result);
    //             if (res.status != 0) {
    //                 console.log("error="+res.error);
    //             } else {
    //                 console.log("res.resp follows:");
    //                 console.log(res.resp);

    //                 status = 0;
    //             }
    //         }
    //     });
    //     return status;
    // }

    // /* Ping EQ until timeout is reached.
    //    Returns: 0=OK (alive), -1=ERROR (timeout) */
    // async function waitAlive(timeout) {
    //     status = await pingEQ();
    //     if (status != 0) {
    //         var interv = setInterval(function(){ t++; }, 1000);
    //         // TODO: Need an async popup
    //         // alert("Cet équipement ne répond pas. Veuillez le reveiller");
    //         for (var t = 0; (status != 0) && (t < timeout); ) {
    //             status = await pingEQ();
    //         }
    //         clearInterval(interv);
    //     }

    //     return status;
    // }

    // function printbidon() {
    //     console.log("printbidon()");
    // }

//     /* Refresh ALL fields */
//     async function openReturnChannelEq() {
//         console.log("openReturnChannelEq(), zgNb="+js_zgNb+", eqAddr="+js_eqAddr);

//         /* First of all, ensure eq is responding */
//         // status = await waitAlive(10);

//         $.when(refreshXName("Manuf"))
//         .then(refreshXName("Model"))
//         .then(refreshXName("Location"))
//         .then(refreshEPList())
//         .then(function(value) {
//     console.log("Successful ajax call, but no data was returned");
// });
        /* Refreshing everything in order */
        // refreshXName("Manuf")
        // .then(console.log("c est fini"));
        // refreshXName("Manuf").done(printbidon());
        // refreshXName("Manuf")
        // .then(refreshXName("Model"))
        // .then(refreshEPList());
        // promiseManuf.then(
        //     promiseModel = refreshXName("Model");
        //     promiseModel.then(
        //         promiseEPList = refreshEPList();
        //     );
        // );

//         if (status == 0)
//             status = await refreshEPList();
//         console.log("la status="+status);
//         console.log("la eq.EPCount="+eq.epCount);
//         if (status == 0) {
//             for (epIdx = 0; (status == 0) && (epIdx < eq.epCount); epIdx++) {
//                 $status = await refreshClustersList(epIdx);
//             }
//         }
//         console.log("la2 status="+status);
//         if (status == 0) {
//             for (epIdx = 0; (status == 0) && (epIdx < eq.epCount); epIdx++) {
//                 ep = eq.epList[epIdx];
//                 console.log("la3 ep.inClustCount="+ep.inClustCount);
// console.log("la4 ep follows");
// console.log(ep);
//                 for (clustIdx = 0; (status == 0) && (clustIdx < ep.inClustCount); clustIdx++) {
//                     $status = await refreshAttributsList(epIdx, 0, clustIdx, 0);
//                 }
//                 for (clustIdx = 0; (status == 0) && (clustIdx < ep.outClustCount); clustIdx++) {
//                     $status = await refreshAttributsList(epIdx, 1, clustIdx, 0);
//                 }
//             }
//         }
    // }


    /* Reminder
    var eq = new Object(); // Equipement details
    eq.zgNb = js_zgNb; // Zigate number, number
    eq.id = js_eqId; // Jeedom ID, number
    eq.addr = js_eqAddr; // Short addr, hex string
    eq.epCount = 0; // Number of EP, number
    eq.epList = new Array(); // Array of objects
        // ep = eq.epList[epIdx] = new Object(); // End Point object
        // ep.id = 0; // EP id/number
        // ep.inClustCount = 0; // IN clusters count
        // ep.inClustList = new Array();
        // ep.outClustCount = 0; // OUT clusters count
        // ep.outClustList = new Array();
        //     clust = new Object();
        //     clust.id = "0000"; // Cluster id, hex string
        //     clust.attrList = new Array(); // Attributs for this cluster
        //         a = new Object(); // Attribut object
        //         a.type = "00"; // Attribut type, hex string => ??? Data type ? What for ???
        //         a.id = "0000"; // Attribut id, hex string
        // TODO: Missing supported zigbee commands list
        //       Currently assuming all commands from the standard are supported
    */

    /* Read JSON.
       Called from JSON read/reload button. */
    function readJSON() {
        console.log("readJSON()");

        /* TODO: Check if there is any user modification and ask user to cancel them */

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'getFile',
                file: js_jsonPath
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'readJSON' !<br>Votre installation semble corrompue.<br>"+error);
                status = -1;
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                } else {
                    console.log(res.content);
                    jeq = JSON.parse(res.content);
                    console.log(jeq);
                    jeq2 = jeq[js_jsonName];

                    /* Let's refresh display */

                    document.getElementById("idManuf").value = jeq2.manufacturer;
                    document.getElementById("idModel").value = jeq2.model;
                    document.getElementById("idName").value = jeq2.nameJeedom;
                    document.getElementById("idTimeout").value = jeq2.timeout;
                    if ("category" in jeq2)
                        jcat = jeq2.category;
                    else if ("Categorie" in jeq2) // Old naming support
                        jcat = jeq2.Categorie;
                    if (typeof jcat !== 'undefined') { // No category defined
                        for (i = 0; i < js_categories.length; i++) {
                            cat = js_categories[i];
                            // console.log("cat="+cat);
                            if (cat in jcat)
                                document.getElementById("id"+cat).checked = true;
                        }
                    }
                    if ("icon" in jeq2)
                        document.getElementById("idIcon").value = jeq2.icon;
                    else if ("icone" in jeq2.configuration) // Old naming support
                        document.getElementById("idIcon").value = jeq2.configuration.icone;
                    if ("batteryType" in jeq2)
                        document.getElementById("idBattery").value = jeq2.batteryType;
                    else if ("battery_type" in jeq2) // Old naming support
                        document.getElementById("idBattery").value = jeq2.configuration.battery_type;
                    oldformat = false;
                    if ("commands" in jeq2)
                        jcmds = jeq2.commands;
                    else if ("Commandes" in jeq2) { // Old naming support
                        jcmds = jeq2.Commandes;
                        oldformat = true;
                    }
                    cmds = "";
                    if (typeof jcmds !== 'undefined') { // No commands defined
                        cmds = '<table>';
                        cmds += '<div class="row">';
                        cmds += '<thead><tr>';
                        cmds += '<th>Cmde Jeedom</th>';
                        cmds += '<th>Cmde Abeille</th>';
                        cmds += '<th>EP</th>';
                        cmds += '<th>ExecAtCreation</th>';
                        cmds += '</tr></thead>';
                        if (oldformat == false) {
                            for (const [key, value] of Object.entries(jcmds)) {
                                // "cmd": { "use":"toto" } => key="cmd", value="{ "use" ... }"
                                console.log(`${key}: ${value}`);

                                cmds += '<tr>';
                                cmds += '<td>'+key+'</td>';
                                cmds += '<td>'+value.use+'</td>';
                                if ("ep" in value)
                                    cmds += '<td>'+value.ep+'</td>';
                                else
                                    cmds += '<td>'+1+'</td>';
                                if ("execAtCreation" in value)
                                    cmds += '<td>'+value.execAtCreation+'</td>';
                                else
                                    cmds += '<td>no</td>';
                                cmds += '</tr>';
                            }
                        } else {
                            for (const [key, value] of Object.entries(jcmds)) {
                                // "include3":"cmd12" => key="include3", value="cmd12"
                                console.log(`${key}: ${value}`);

                                cmds += '<tr>';
                                cmds += '<td>'+key+'</td>';
                                cmds += '<td>'+value+'</td>';
                            }

                        }
                        cmds += '</table>';
                    }
                    $('#idCommands').empty().append(cmds);
                }
            }
        });
    }

    /* Check if clustId/attrId exist in another EP.
       Purpose is to give a unique Jeedom command name.
       Returns: true is exists, else false */
    function sameAttribInOtherEP(epId, clustId, attrId) {
        for (var epIdx = 0; epIdx < eq.epList.length; epIdx++) {
            ep = eq.epList[epIdx];
            if (ep.id == epId)
                continue; // Current EP
            for (var clustIdx = 0; clustIdx < ep.inClustList.length; clustIdx++) {
                clust = ep.inClustList[clustIdx];
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
        for (var epIdx = 0; epIdx < eq.epList.length; epIdx++) {
            ep = eq.epList[epIdx];
            if (ep.id == epId)
                continue; // Current EP

            for (var clustIdx = 0; clustIdx < ep.inClustList.length; clustIdx++) {
                clust = ep.inClustList[clustIdx];
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

    function prepareJson() {
        console.log("prepareJson()");

        /* Converting detected attributs to commands */
        var z = {
            "0000": { // Basic cluster
                // Attributes
                "0000" : { "name" : "ZCLVersion", "type" : "R" },
                "0004" : { "name" : "ManufacturerName", "type" : "R" },
                "0005" : { "name" : "ModelIdentifier", "type" : "R" },
                "0006" : { "name" : "DateCode", "type" : "R" },
                "0007" : { "name" : "PowerSource", "type" : "R" },
                // Cmds: none
            },
            "0003": { // Identify cluster
                // Attributes
                "0000" : { "name" : "IdentifyTime", "type" : "RW" },
                // Cmds
                "cmd1" : { "name" : "Identify" },
                "cmd2" : { "name" : "IdentifyQuery" },
                "cmd3" : { "name" : "TriggerEffect" },
            },
            "0004": { // Groups cluster
                // Attributes
                "0000" : { "name" : "NameSupport", "type" : "R" },
                // Cmds
                "cmd1" : { "name" : "AddGroup" },
                "cmd2" : { "name" : "ViewGroup" },
                "cmd3" : { "name" : "GetGroupMembership" },
                "cmd4" : { "name" : "RemoveGroup" },
                "cmd5" : { "name" : "RemoveAllGroups" },
                "cmd6" : { "name" : "AddGroupIfIdent" },
            },
            "0005": { // Scene cluster
                // Attributes
                "0000" : { "name" : "SceneCount", "type" : "R" },
                "0001" : { "name" : "CurrentScene", "type" : "R" },
                "0002" : { "name" : "CurrentGroup", "type" : "R" },
                "0003" : { "name" : "SceneValid", "type" : "R" },
                "0004" : { "name" : "NameSupport", "type" : "R" },
                "0005" : { "name" : "LastConfiguredBy", "type" : "R" },
                // Cmds
                "cmd0" : { "name" : "AddScene" },
                "cmd1" : { "name" : "ViewScene" },
                "cmd2" : { "name" : "RemoveScene" },
                "cmd3" : { "name" : "RemoveAllScenes" },
                "cmd4" : { "name" : "StoreScene" },
                "cmd5" : { "name" : "RecallScene" },
                "cmd6" : { "name" : "GetSceneMembership" },
                "cmd40" : { "name" : "EnhancedAddScene" },
                "cmd41" : { "name" : "EnhancedViewScene" },
                "cmd42" : { "name" : "CopyScene" },
            },
            "0006": { // On/Off cluster
                // Attributes
                "0000" : { "name" : "OnOff", "type" : "R" },
                "4000" : { "name" : "GlobalSceneControl", "type" : "R" },
                "4001" : { "name" : "OnTime", "type" : "RW" },
                "4002" : { "name" : "OffWaitTime", "type" : "RW" },
                // Cmds
                "cmd1" : { "name" : "Off" },
                "cmd2" : { "name" : "On" },
                "cmd3" : { "name" : "Toggle" },
            },
            "0007": { // On/Off switch config cluster
                // Attributes
                "0000" : { "name" : "SwitchType", "type" : "R" },
                "0010" : { "name" : "SwitchActions", "type" : "RW" },
            },
            "0008": { // Level control cluster
                // Attributes
                "0000" : { "name" : "CurrentLevel", "type" : "R" },
                "0001" : { "name" : "RemainingTime", "type" : "R" },
                "0010" : { "name" : "OnOffTransitionTime", "type" : "RW" },
                "0011" : { "name" : "OnLevel", "type" : "RW" },
                "0012" : { "name" : "OnTransitionTime", "type" : "RW" },
                "0013" : { "name" : "OffTransitionTime", "type" : "RW" },
                "0014" : { "name" : "DefaultMoveRate", "type" : "RW" },
                // Cmds
                "cmd1" : { "name" : "MoveToLevel" },
                "cmd2" : { "name" : "Move" },
                "cmd3" : { "name" : "Step" },
                "cmd4" : { "name" : "Stop" },
                "cmd5" : { "name" : "MoveToLevelWithOnOff" },
                "cmd6" : { "name" : "MoveWithOnOff" },
                "cmd7" : { "name" : "StepWithOnOff" },
                // "cmd8" : { "name" : "Stop" }, // Another "stop" (0x07) ?
            },
            "0009": { // Alarm cluster
                // Attributes
                "0000" : { "name" : "AlarmCount", "type" : "R" },
                // Cmds
                "cmd1" : { "name" : "ResetAlarm" },
                "cmd2" : { "name" : "ResetAllAlarms" },
                "cmd3" : { "name" : "GetAlarm" },
                "cmd4" : { "name" : "ResetAlarmLog" },
            },
            "000A": { // Time cluster
                // Attributes
                "0000" : { "name" : "Time", "type" : "RW" },
                "0001" : { "name" : "TimeStatus", "type" : "RW" },
                "0002" : { "name" : "TimeZone", "type" : "RW" },
                "0003" : { "name" : "DstStart", "type" : "RW" },
                "0004" : { "name" : "DstEnd", "type" : "RW" },
                "0005" : { "name" : "DstShift", "type" : "RW" },
                "0006" : { "name" : "StandardTime", "type" : "R" },
                "0007" : { "name" : "LocalTime", "type" : "R" },
                "0008" : { "name" : "LastSetTime", "type" : "R" },
                "0009" : { "name" : "ValidUntilTime", "type" : "RW" },
                // Cmds: none
            },
            "0014": { // Multistate Value cluster
                // Attributes
                "000E" : { "name" : "StateText", "type" : "RW" },
                "001C" : { "name" : "Description", "type" : "RW" },
                "004A" : { "name" : "NumberOfStates", "type" : "RW" },
                "0051" : { "name" : "OutOfService", "type" : "RW" },
                "0055" : { "name" : "PresentValue", "type" : "RW" },
                "0057" : { "name" : "PriorityArray", "type" : "RW" },
                "0067" : { "name" : "Reliability", "type" : "RW" },
                "0068" : { "name" : "RelinquishDefault", "type" : "RW" },
                "006F" : { "name" : "StatusFlags", "type" : "R" },
                "0100" : { "name" : "ApplicationType", "type" : "R" },
                // Cmds: none
            },
            "0020": { // Poll control cluster
                // Attributes
                "0000" : { "name" : "CheckInInterval", "type" : "RW" },
                "0001" : { "name" : "LongPollInterval", "type" : "R" },
                "0002" : { "name" : "ShortPollInterval", "type" : "R" },
                "0003" : { "name" : "FastPollTimeout", "type" : "RW" },
                "0004" : { "name" : "CheckInIntervalMin", "type" : "R" },
                "0005" : { "name" : "LongPollIntervalMin", "type" : "R" },
                "0006" : { "name" : "FastPollTimeoutMax", "type" : "R" },
                "cmd1" : { "name" : "CheckIn" },
            },
            "0100": { // Shade Configuration cluster
                // Attributes
                "0000" : { "name" : "PhysicalClosedLimit", "type" : "R" },
                "0001" : { "name" : "MotorStepSize", "type" : "R" },
                "0002" : { "name" : "Status", "type" : "RW" },
                "0010" : { "name" : "ClosedLimit", "type" : "RW" },
                "0011" : { "name" : "Mode", "type" : "RW" },
                // Cmds: none
            },
            "0102": { // Window covering cluster
                // Information attributes
                "0000" : { "name" : "WindowCoveringType", "type" : "R" },
                "0001" : { "name" : "PhysClosedLimitLift", "type" : "R" },
                "0002" : { "name" : "PhysClosedLimitTilt", "type" : "R" },
                "0003" : { "name" : "CurPosLift", "type" : "R" },
                "0004" : { "name" : "CurPosTilt", "type" : "R" },
                "0005" : { "name" : "NbOfActuationsLift", "type" : "R" },
                "0006" : { "name" : "NbOfActuationsTilt", "type" : "R" },
                "0007" : { "name" : "ConfigStatus", "type" : "R" },
                "0008" : { "name" : "CurPosLiftPercent", "type" : "R" },
                "0009" : { "name" : "CurPosTiltPercent", "type" : "R" },
                // Settings attributes
                "0010" : { "name" : "InstalledOpenLimitLift", "type" : "R" },
                "0011" : { "name" : "InstalledClosedLimitLift", "type" : "R" },
                "0012" : { "name" : "InstalledOpenLimitTilt", "type" : "R" },
                "0013" : { "name" : "InstalledClosedLimitTilt", "type" : "R" },
                "0014" : { "name" : "VelocityLift", "type" : "RW" },
                "0015" : { "name" : "AccelTimeLift", "type" : "RW" },
                "0016" : { "name" : "DecelTimeLift", "type" : "RW" },
                "0017" : { "name" : "Mode", "type" : "RW" },
                "0018" : { "name" : "IntermSetpointsLift", "type" : "RW" },
                "0019" : { "name" : "IntermSetpointsTilt", "type" : "RW" },
                // Cmds
                "cmd1" : { "name" : "UpOpen" },
                "cmd2" : { "name" : "DownClose" },
                "cmd3" : { "name" : "Stop" },
                "cmd4" : { "name" : "GotoLiftVal" },
                "cmd5" : { "name" : "GotoLiftPercent" },
                "cmd6" : { "name" : "GotoTiltVal" },
                "cmd7" : { "name" : "GotoTiltPercent" },
            },
            "0702": { // Metering (Smart Energy) cluster
                // Attributes
                "0000" : { "name" : "CurrentSummationDelivered", "type" : "R" },
                "0001" : { "name" : "CurrentSummationReceived", "type" : "R" },
                "0002" : { "name" : "CurrentMaxDemandDelivered", "type" : "R" },
                "0003" : { "name" : "CurrentMaxDemandReceived", "type" : "R" },
                // TO BE COMPLETED
                // Cmds
                "cmd1" : { "name" : "GetProfile" },
                "cmd2" : { "name" : "RequestMirrorResponse" },
                "cmd3" : { "name" : "MirrorRemoved" },
                "cmd4" : { "name" : "RequestFastPollMode" },
            },
            "1000": { // Touchlink commissioning cluster
                // Attributes: none
                // Cmds
                "cmd1" : { "name" : "ScanRequest" },
                "cmd2" : { "name" : "DevInfoReq" },
                "cmd3" : { "name" : "IdentifyReq" },
                "cmd4" : { "name" : "ResetToFactoryReq" },
                "cmd5" : { "name" : "NetworkStartReq" },
                "cmd6" : { "name" : "NetworkJoinRouterReq" },
                "cmd7" : { "name" : "NetworkJoinEndDeviceReq" },
                "cmd8" : { "name" : "NetworkUpdateReq" },
                "cmd9" : { "name" : "GetGroupIdReq" },
                "cmd10" : { "name" : "GetEPListReq" },
            }
        };

        /* Jeedom commands naming reminder:
           - for attributes: zb[EP]['Get'/'Set'/'']-<clustId>-<attribute_name>
           - for commands: zb[EP]Cmd-<clustId>-<cmd_name>
           EP is optional and must be set only if same
             attribute or command exists in another EP.
         */
        var cmds = new Object();
        var cmdNb = 0;
        for (var epIdx = 0; epIdx < eq.epList.length; epIdx++) {
            ep = eq.epList[epIdx];
            console.log("EP"+ep.id+" (idx="+epIdx+")");

            for (var clustIdx = 0; clustIdx < ep.inClustList.length; clustIdx++) {
                clust = ep.inClustList[clustIdx];
                // console.log("IN clustId="+clust.id);

                if (!(clust.id in z)) {
                    console.log("IN cluster ID "+clust.id+" unknown");
                    continue;
                }
                zClust = z[clust.id];
                console.log("clustId="+clust.id);

                // console.log("clust.attrList.length="+clust.attrList.length);
                for (var attrIdx = 0; attrIdx < clust.attrList.length; attrIdx++) {
                    attr = clust.attrList[attrIdx];
                    console.log("attrId="+attr.id);

                    if (attr.id in zClust) {
                        /* Adding attributes access commands */
                        if (sameAttribInOtherEP(ep.id, clust.id, attr.id))
                            epName = "EP"+ep.id;
                        else
                            epName = "";
                        zAttr = zClust[attr.id];
                        if ((zAttr["type"] == "R") || (zAttr["type"] == "RW")) {
                            cActionName = epName+"Get"+"-"+zAttr["name"];
                            cmds[cActionName] = new Object;
                            cmds[cActionName]['use'] = "zbGet-"+clust.id+"-"+zAttr["name"];
                            if (ep.id != 1)
                                cmds[cActionName]['ep'] = ep.id;

                            if (epName != "")
                                cInfoName = epName+"-"+zAttr["name"];
                            else
                                cInfoName = zAttr["name"];
                            cmds[cInfoName] = new Object;
                            cmds[cInfoName]['use'] = "zb-"+clust.id+"-"+zAttr["name"];
                            if (ep.id != 1)
                                cmds[cInfoName]['ep'] = ep.id;

                            cmds[cActionName]['update'] = cInfoName;
                        } else if ((zAttr["type"] == "W") || (zAttr["type"] == "RW")) {
                            cName = "Set"+"-"+zAttr["name"]; // Jeedom command name
                            cmds[cName] = new Object;
                            cmds[cName]['use'] = "zbSet-"+clust.id+"-"+zAttr["name"];
                            if (ep.id != 1)
                                cmds[cName]['ep'] = ep.id;
                        }
                    } else {
                        console.log("Attr ID "+attr.id+" unknown in cluster ID "+clust.id);
                    }

                    /* Adding cluster specific commands */
                    if ("cmd1" in zClust) {
                        zCmdNb = 1;
                        zCmd = "cmd1";
                        while(zCmd in zClust) {
                            if (sameZCmdInOtherEP(ep.id, clust.id, zClust[zCmd]["name"]))
                                epName = "EP"+ep.id;
                            else
                                epName = "";

                            if (epName != "")
                                cName = epName+"Cmd-"+clust.id+"-"+zClust[zCmd]["name"];
                            else
                                cName = "Cmd"+"-"+zClust[zCmd]["name"];
                            cmds[cName] = new Object;
                            cmds[cName]['use'] = "zbGet-"+clust.id+"-"+zClust[zCmd]["name"];

                            zCmdNb++;
                            zCmd = "cmd"+zCmdNb;
                        }
                    }
                }
            }
        }
        console.log(cmds);

        /* Format reminder:
            {
                "BASICZBR3": {
                    "name": "Sonoff BASICZBR3 smart switch",
                    "manufacturer": "Sonoff",
                    "model": "BASICZBR3",
                    "timeout": "60",
                    "category": {
                        "automatism": "1"
                    },
                    "icon": "BASICZBR3",
                    "batteryType": "1x3V CR2032",
                    "batteryVolt": "3",
                    "commands": {
                        "manufacturer": { "use": "societe" },
                        "modelIdentifier": { "use": "nom" },
                        "getEtatEp05": { "use": "etat", "ep": 5 },
                        "bindHumidity": { "use": "BindToZigateHumidity", "ep": 2, "execAtCreation": "yes" },
                        "setReportHumidity": { "use": "setReportHumidity", "ep": 2, "execAtCreation": "yes" }
                    }
                }
            }
         */

        var jeq2 = new Object();
        jeq2.manufacturer = document.getElementById("idManuf").value;
        jeq2.model = document.getElementById("idModel").value;
        jeq2.name = document.getElementById("idName").value;
        jeq2.timeout = document.getElementById("idTimeout").value;
        // jeq2.Comment = // Optional
        /* 'category' */
        var cat = new Object();
        cat.automatism = 1;
        jeq2.category = cat;
        icon = document.getElementById("idIcon").value;
        if (icon != "")
        jeq2.icon = icon;
        else
        jeq2.icon = "node_defaultUnknown";
        jeq2.batteryType = document.getElementById("idBattery").value;
        jeq2.batteryVolt = document.getElementById("idBatteryMax").value;
        /* 'commands' */
        jeq2.commands = cmds;
        var jeq = new Object();
        jeq[js_jsonName] = jeq2;

        return jeq;
    }

    /* Check that minimum infos are there before writing JSON.
       Returns: true if ok, false if missing infos */
    function checkMissingInfos() {
        var missing = "";
        if (document.getElementById("idManuf").value == "")
            missing += "- Nom du fabricant\n";
        if (document.getElementById("idModel").value == "")
            missing += "- Nom du modèle\n";
        if (document.getElementById("idName").value == "")
            missing += "- Description de l'équipement (ex: Smart curtain switch)\n";

        if (missing == "")
            return true; // Ok

        alert("Les informations suivantes sont manquantes\n"+missing);
        return false;
    }

    /* Update/create JSON file */
    function writeJSON() {
        console.log("writeJSON()");

        /* Check if mandatory infos are there */
        if (checkMissingInfos() == false)
            return;

        jeq = prepareJson();

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'writeConfigJson',
                jsonName: js_jsonName,
                eq: jeq,
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'writeConfigJson' !<br>Votre installation semble corrompue.<br>"+error);
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

    /* Create 'discovery.json' file */
    function writeDiscoveryInfos() {
        console.log("writeDiscoveryInfos()");

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'writeFile',
                path: 'tmp/discovery.log',
                content: JSON.stringify(eq)
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'writeDiscoveryInfos' !<br>Votre installation semble corrompue.<br>"+error);
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

    /* Save given 'text' to 'fileName' */
    function downloadInfos(fileName, text) {
        console.log("downloadInfos()");

        text = JSON.stringify(eq);
        let elem = window.document.createElement('a');
        elem.style = "display: none";
        elem.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        elem.setAttribute('download', "discovery.json");
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }


    /* Request device info
       clustType = '00' (server cluster) or '01' (client cluster)
     */
    function requestInfos(infoType, ep = "01", clustId = "0000", clustType = "00") {
        console.log("requestInfos("+infoType+")");

        // 'Cmd'.$device->getLogicalId().'/ReadAttributeRequest', 'EP=01&clusterId=0005&attributeId=0000'
        logicalId = "Abeille"+js_zgNb+"_"+js_eqAddr;
        if (infoType == "epList") {
            topic = "Cmd"+logicalId+"_ActiveEndPoint";
            payload = "address="+js_eqAddr;
        } else if (infoType == "manufacturer") {
            topic = "Cmd"+logicalId+"_ReadAttributeRequest";
            payload = "EP=01_clusterId=0000_attributeId=0004"; // Manufacturer
        } else if (infoType == "modelId") {
            topic = "Cmd"+logicalId+"_ReadAttributeRequest";
            payload = "EP=01_clusterId=0000_attributeId=0005"; // Model
        } else if (infoType == "location") {
            topic = "Cmd"+logicalId+"_ReadAttributeRequest";
            payload = "EP=01_clusterId=0000_attributeId=0010"; // Location
        } else if (infoType == "clustersList") {
            topic = "Cmd"+logicalId+"_SimpleDescriptorRequest";
            payload = "address="+js_eqAddr+"_endPoint="+ep;
        } else if (infoType == "attribList") {
            topic = "Cmd"+logicalId+"_DiscoverAttributesCommand";
            payload = "address="+js_eqAddr+"_EP="+ep+"_clusterId="+clustId+"_startAttributeId=0000_maxAttributeId=FF_direction="+clustType;
        } else {
            console.log("requestInfos("+infoType+") => UNEXPECTED type !");
            return;
        }

        var xhttp = new XMLHttpRequest();
        var url = "plugins/Abeille/core/php/AbeilleCliToQueue.php";
        xhttp.open("GET", url+"?action=sendMsg&queueId="+js_queueKeyXmlToCmd+"&topic="+topic+"&payload="+payload, true);
        xhttp.send();
    }

    /* Treat async infos received from server to display them. */
    function receiveInfos() {
        console.log("receiveInfos()");
        console.log("Got='"+this.responseText+"'");
        if (this.responseText == '') {
            openReturnChannel();
            return;
        }

        res = JSON.parse(this.responseText);
        if (res.type == "activeEndpoints") {
            // 'src' => 'parser',
            // 'type' => 'activeEndpoints',
            // 'net' => $dest,
            // 'addr' => $SrcAddr,
            // 'epList' => $endPointList

            epList = res.epList;
            epArr = epList.split('/');

            /* Updating internal datas */
            eq.epCount = epArr.length;
            endpoints = new Object; // Array of objects
            epArr.forEach((ep) => {
                endpoints[ep] = new Object();
            });
            eq.epList = endpoints;

            /* Updating display */
            document.getElementById("idEPList").value = res.epList;
            var endpoints = "";
            $("#idEndPoints").empty();
            epArr.forEach((ep) => {
                h = '<br><div id="idEP'+ep+'">';
                h += '<label id="idClustEP'+ep+'" class="col-lg-2 control-label"></label>';
                h += '<div class="col-lg-10">';
                h += '<a class="btn btn-warning" title="Raffraichi la liste des clusters" onclick="requestInfos(\'clustersList\', '+ep+')"><i class="fas fa-sync"></i></a>';
                h += '<br><br>';

                h += '</div>';

                h += '<div style="margin-left:30px">';

                h += '<label for="fname">Server clusters:</label>';
                h += '<br>';
                h += '<table id="idServClust'+ep+'">';
                h += '</table>';
                h += '<br>';

                h += '<label for="fname">Client clusters:</label>';
                h += '<table id="idCliClust'+ep+'">';
                h += '</table>';
                h += '<br>';
                h += '</div>';

                h += '</div>';
                $("#idEndPoints").append(h);

                if (endpoints != "")
                    endpoints += ", ";
                endpoints += ep;
                document.getElementById("idClustEP"+ep).innerHTML = "End point "+ep+":";
                $("#idEP"+ep).show();

                /* Requesting clusters list for each EP */
                requestInfos('clustersList', ep);
            });
        } else if (res.type == "attributeReport") {
            // 'src' => 'parser',
            // 'type' => 'attributeReport', // 8100 or 8102
            // 'net' => $dest,
            // 'addr' => $SrcAddr,
            // 'ep' => $EPoint,
            // 'clustId' => $ClusterId,
            // 'attrId' => $AttributId,
            // 'status' => "00", "86"
            // 'value' => $data

            if (res.clustId == "0000") {
                if (res.attrId == "0004") {
                    var field = document.getElementById("idZigbeeManuf");
                } else if (res.attrId == "0005") {
                    var field = document.getElementById("idZigbeeModel");
                } else if (res.attrId == "0010") {
                    var field = document.getElementById("idZigbeeLocation");
                }
            }
            if (typeof field !== 'undefined') {
                if (res.status != "00")
                    field.value = "-- Non supporté --";
                else
                    field.value = res.value;
            }
        } else if (res.type == "simpleDesc") {
            // 'src' => 'parser',
            // 'type' => 'simpleDesc',
            // 'net' => $dest,
            // 'addr' => $SrcAddr,
            // 'ep' => $EPoint,
            // 'inClustList' => $inputClusters, // Format: 'xxxx/yyyy/zzzz'
            // 'outClustList' => $outputClusters // Format: 'xxxx/yyyy/zzzz'
            inClustArr = res.inClustList.split('/');
            outClustArr = res.outClustList.split('/');

            /* Updating internal datas */
            ep = eq.epList[res.ep];
            ep.inClusters = new Object();
            inClustArr.forEach((clustId) => {
                ep.inClusters[clustId] = new Object();
            });
            ep.outClusters = new Object();
            outClustArr.forEach((clustId) => {
                ep.outClusters[clustId] = new Object();
            });

            /* Updating display */
            var inClustTable = document.getElementById("idServClust"+res.ep);
            var outClustTable = document.getElementById("idCliClust"+res.ep);
            /* Cleanup tables */
            var rowCount = inClustTable.rows.length;
            for (var i = rowCount - 1; i >= 0; i--) {
                inClustTable.deleteRow(i);
            }
            rowCount = outClustTable.rows.length;
            for (i = rowCount - 1; i >= 0; i--) {
                outClustTable.deleteRow(i);
            }
            inClustArr.forEach((clustId) => {
                console.log("inClust="+clustId);
                var newRow = inClustTable.insertRow(-1);
                var newCol = newRow.insertCell(0);
                newCol.innerHTML = clustId;
                newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="requestInfos(\'attribList\', '+res.ep+', \''+clustId+'\')"><i class="fas fa-sync"></i></a>';
                requestInfos('attribList', res.ep, clustId);
            });
            outClustArr.forEach((clustId) => {
                console.log("outClust="+clustId);
                var newRow = outClustTable.insertRow(-1);
                var newCol = newRow.insertCell(0);
                newCol.innerHTML = clustId;
                newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="requestInfos(\'attribList\', '+res.ep+', \''+clustId+'\', \'01\')"><i class="fas fa-sync"></i></a>';
                requestInfos('attribList', res.ep, clustId, '01');
            });
        } else if (res.type == "attributeDiscovery") {
            // 'src' => 'parser',
            // 'type' => 'attributeDiscovery',
            // 'net' => $dest,
            // 'addr' => $srcAddress,
            // 'ep' => $srcEndPoint,
            // 'clustId' => $cluster,
            // 'dir' => (hexdec($FCF) >> 3) & 1, // 1=server cluster, 0=client cluster
            // 'attributes' => $attributes

            sEp = res.ep;
            sDir = res.dir;
            sClustId = res.clustId;
            sAttributes = res.attributes;
            let sAttrCount = sAttributes.length;
            console.log("attributeDiscovery: clust="+sClustId+", nb of attr="+sAttrCount)

            if (sAttrCount == 0) {
                openReturnChannel();
                return;
            }

            /* Updating internal datas */
            ep = eq.epList[sEp];
            if (sDir)
                clust = ep.inClusters[sClustId];
            else
                clust = ep.outClusters[sClustId];
            for (attrIdx = 0; attrIdx < sAttrCount; attrIdx++) {
                sAttr = sAttributes[attrIdx];
                clust[sAttr.id] = 1;
            }

            /* Updating display */
            var row;
            if (sDir) { // Server to client = server clusters
                var clustTable = document.getElementById("idServClust"+sEp);
            } else {
                var clustTable = document.getElementById("idCliClust"+sEp);
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
            var colCount = row.cells.length;
            for (var i = colCount - 1; i >= 1; i--) {
                row.deleteCell(i);
            }
            // Fills row
            for (attrIdx = 0; attrIdx < sAttrCount; attrIdx++) {
                rattr = sAttributes[attrIdx];
                var newCol = row.insertCell(-1);
                newCol.innerHTML = rattr.id;
            }
        } else if (res.type == "deviceAnnounce") {
            // 'src' => 'parser',
            // 'type' => 'deviceAnnounce',
            // 'net' => $dest,
            // 'addr' => $Addr,
            // 'ieee' => $IEEE
            console.log("deviceAnnounce: new addr="+res.addr)
            js_eqAddr = res.addr;
            eq.addr = js_eqAddr;
            document.getElementById("idAddr").value = res.addr;
        }

        openReturnChannel();
    }

    function returnChannelStateChange() {
        console.log("returnChannelStateChange(): "+this.readyState);
    }

    function openReturnChannel() {
        console.log("openReturnChannel()");

        var url = 'plugins/Abeille/core/php/AbeilleQueueToCli.php';
        var request = new XMLHttpRequest();
        request.open('GET', url, true);
        request.responseType = 'text';
        request.onload = receiveInfos;
        request.onreadystatechange = returnChannelStateChange;
        request.send();
    }

    $(document).ready( function() {
        console.log("document.ready()");
        openReturnChannel();

        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?queueId="+js_queueKeyCtrlToParser+"&msg=type:sendToCli_net:Abeille"+js_zgNb+"_addr:"+js_eqAddr+"_ieee:"+js_eqIeee, true);
        xhttp.send();

        requestInfos('epList');
    });
</script>
