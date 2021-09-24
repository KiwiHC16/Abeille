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
    $jsonName = $eqLogic->getConfiguration('modeleJson', ''); // TODO: rename to 'ab::jsonId'
    $jsonLocation = $eqLogic->getConfiguration('ab::jsonLocation', 'Abeille');
    $eqIeee = $eqLogic->getConfiguration('IEEE', '');

    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_zgNb = '.$zgNb.';</script>'; // PHP to JS
    echo '<script>var js_eqId = '.$eqId.';</script>'; // PHP to JS
    echo '<script>var js_eqAddr = "'.$eqAddr.'";</script>'; // PHP to JS
    echo '<script>var js_eqIeee = "'.$eqIeee.'";</script>'; // PHP to JS
    echo '<script>var js_jsonName = "'.$jsonName.'";</script>'; // PHP to JS
    echo '<script>var js_jsonLocation = "'.$jsonLocation.'";</script>'; // PHP to JS
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

        <!-- Colonne Zigbee -->
        <div class="col-lg-6 b">
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
                <div class="col-lg-10">
                    <a id="idEPListRB" class="btn btn-warning" title="Raffraichi la liste des End Points" onclick="requestInfos('epList')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idEPList" value="" readonly>
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Source d'alim:</label>
                <div class="col-lg-10">
                    <input type="text" id="idZbPowerSource" value="" readonly>
                </div>
            </div>

            <style>
                table, td {
                    border: 1px solid black;
                }
            </style>

            <div class="row" id="idendPoints">
            </div>

            <div class="row">
                <br>
                <a class="btn btn-success pull-left" title="Télécharge 'discovery.json'" onclick="downloadDiscovery()"><i class="fas fa-cloud-download-alt"></i> Télécharger</a>
                <?php if (isset($dbgTcharp38)) { ?>
                <a class="btn btn-success pull-left" title="Genère les commandes Jeedom" onclick="zigbeeToCommands()"><i class="fas fa-cloud-download-alt"></i> Mettre à jour JSON</a>
                <?php } ?>
                <br>
                <br>
            </div>
        </div>

        <!-- <div class="row"> -->
        <?php if (isset($dbgTcharp38)) { ?>
        <div class="col-lg-6">
        <?php } else { ?>
        <div class="col-lg-6" style="display:none;">
        <?php } ?>
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
                        <a class="btn btn-warning" title="(Re)lire" onclick="readJSON()">(Re)lire</a>
                        <a class="btn btn-warning" title="Mettre à jour le fichier" onclick="writeJSON()">Ecrire</a>
                        <a class="btn btn-warning" title="Télécharger le JSON" onclick="download2()"><i class="fas fa-cloud-download-alt"></i>Télécharger</a>
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
    eq.discovery = new Object(); // Zigbee interrogation datas
        // discovery.epCount = 0; // Number of EP, number
        // discovery.endPoints = new Array(); // Array of objects
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
        readJSON();

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
    function readJSON() {
        console.log("readJSON()");

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
                bootbox.alert("ERREUR 'readJSON' !<br>Votre installation semble corrompue.<br>"+error);
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
        hcmds += '<th>ExecAtCreation</th>';
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
        discovery = eq.discovery;
        for (var epIdx = 0; epIdx < discovery.endPoints.length; epIdx++) {
            ep = discovery.endPoints[epIdx];
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
        discovery = eq.discovery;
        for (var epIdx = 0; epIdx < discovery.endPoints.length; epIdx++) {
            ep = discovery.endPoints[epIdx];
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

    function newCmd($use, $params = "", $exec = "") {
        cmd = new Object;
        cmd['use'] = $use;
        if ($params != "")
            cmd['params'] = $params;
        if ($exec != "")
            cmd["execAtCreation"] = $exec;
        return cmd;
    }

    /* Generate Jeedom commands using zigbee discovery datas */
    function zigbeeToCommands() {
        console.log("zigbeeToCommands()");

        /* Jeedom commands naming reminder:
           - for attributes: ['Get-'/'Set-'/''][EP]-<clustId>-<attribute_name>
           - for commands: Cmd-[EP-]<clustId>-<cmd_name>
           EP is optional and must be set only if same
             attribute or command exists in another EP.
         */
        var cmds = new Object();
        var cmdNb = 0;
        discovery = eq.discovery;
        endPoints = discovery.endPoints;
        console.log(endPoints);
        for (var epId in endPoints) {
            console.log("EP "+epId);
            ep = endPoints[epId];

            for (var clustId in ep.servClusters) {
                attributes = ep.servClusters[clustId]['attributes'];
                for (const [attrId, attr] of Object.entries(attributes)) {
                    if (clustId == "0000") {
                        /* Basic cluster.
                        Only attribute 4000 is converted to user command.
                        No sense for others */
                        if (isset(attributes['4000'])) {
                            cmds["SWBuildID"] = newCmd("zb-0000-SWBuildID");
                            cmds["Get-SWBuildID"] = newCmd("zbReadAttribute", "clustId=0000&attrId=4000");
                        }
                    } else if (clustId == "0001") {
                        /* Power configuration */
                        if (isset(attributes['0021'])) {
                            cmds["Battery-Percent"] = newCmd("zb-0001-BatteryPercent");
                            cmds["Battery-Percent"]["isVisible"] = 1;
                            cmds["Set-BatteryPercentReporting"] = newCmd("zbConfigureReporting", "clustId=0001&attrType=20&attrId=0021&minInterval=1800&maxInterval=3600", "yes");
                        } else if (isset(attributes['0020'])) {
                            cmds["Set-BatteryVoltReporting"] = newCmd("zbConfigureReporting", "clustId=0001&attrType=20&attrId=0020&minInterval=1800&maxInterval=3600", "yes");
                        }
                        cmds["BindToZigate-Power"] = newCmd("bindToZigate", "clustId=0001", "yes");
                    } else if (clustId == "0004") {
                        /* Groups cluster */
                        cmds["Groups"] = newCmd("Group-Membership");
                    } else if (clustId == "0006") {
                        /* OnOff cluster */
                        if (isset(attributes['0000'])) {
                            cmds["Status"] = newCmd("zb-0006-OnOff");
                            cmds["Status"]["isVisible"] = 1;
                            cmds["Get-Status"] = newCmd("zbReadAttribute", "clustId=0006&attrId=0000");
                            // Adding on/off & toggle commands but assuming all supported
                            cmds["On"] = newCmd("zbCmd-0006-On");
                            cmds["On"]["isVisible"] = 1;
                            cmds["Off"] = newCmd("zbCmd-0006-Off");
                            cmds["Off"]["isVisible"] = 1;
                            cmds["Toggle"] = newCmd("zbCmd-0006-Toggle");
                            // Adding bind + configureReporting but assuming supported
                            cmds["BindToZigate-OnOff"] = newCmd("zbBindToZigate", "clustId=0006", "yes");
                            cmds["Set-OnOffReporting"] = newCmd("zbConfigureReporting", "clustId=0006&attrType=10&attrId=0000&minInterval=0000&maxInterval=0000", "yes");
                        }
                    } else if (clustId == "0300") {
                        /* Color cluster */
                        if (isset(attributes['0000'])) {
                            cmds["Current HUE"] = newCmd("zb-0300-CurrentHue");
                            cmds["Current HUE"]["isVisible"] = 1;
                            cmds["Get-Current HUE"] = newCmd("zbReadAttribute", "clustId=0300&attrId=0000");
                        }
                        else if (isset(attributes['0001'])) {
                            cmds["Current Saturation"] = newCmd("zb-0300-CurrentSaturation");
                            cmds["Current SaturationX"]["isVisible"] = 1;
                            cmds["Get-Current Saturation"] = newCmd("zbReadAttribute", "clustId=0300&attrId=0001");
                        }
                        else if (isset(attributes['0003'])) {
                            cmds["Current X"] = newCmd("zb-0300-CurrentX");
                            cmds["Current X"]["isVisible"] = 1;
                            cmds["Get-Current X"] = newCmd("zbReadAttribute", "clustId=0300&attrId=0003");
                        }
                        else if (isset(attributes['0004'])) {
                            cmds["Current Y"] = newCmd("zb-0300-CurrentY");
                            cmds["Current Y"]["isVisible"] = 1;
                            cmds["Get-Current Y"] = newCmd("zbReadAttribute", "clustId=0300&attrId=0004");
                        }
                        else if (isset(attributes['0008'])) {
                            cmds["Color mode"] = newCmd("zb-0300-ColorMode");
                            cmds["Color mode"]["isVisible"] = 1;
                            cmds["Color mode"] = newCmd("zbReadAttribute", "clustId=0300&attrId=0008");
                        }
                    } else if (clustId == "0B04") {
                        /* Electrical Measurement cluster */
                    }
                }

                commandsReceived = ep.servClusters[clustId]['commandsReceived'];
                for (cmd in commandsReceived) {
                    if (clustId == "0300") {
                        /* Color cluster */
                    }
                }
            }
        }
        console.log(cmds);
        eq.commands = cmds;

        // Refresh display
        displayCommands();
        zbManuf = document.getElementById("idZbManuf"+epId).value;
        zbModel = document.getElementById("idZbModel"+epId).value;
        js_jsonName = zbModel+"_"+zbManuf;
        document.getElementById("idJsonName").value = js_jsonName;
    } // End zigbeeToCommands()

    function prepareJson() {
        console.log("prepareJson()");

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
                    }
                    "batteryType": "1x3V CR2032",
                    "batteryVolt": "3",
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
        timeout = document.getElementById("idTimeout").value;
        if (timeout != '')
            jeq2.timeout = timeout;
        // jeq2.comment = // Optional

        /* 'category' */
        var cat = new Object();
        cat.automatism = 1;
        jeq2.category = cat;

        // 'configuration'
        var conf = new Object();
        icon = document.getElementById("idIcon").value;
        if (icon != "")
            conf.icon = icon;
        else
            conf.icon = "defaultUnknown";
        jeq2.configuration = conf;

        batteryType = document.getElementById("idBattery").value;
        if (batteryType != '')
            jeq2.batteryType = batteryType;

        batteryVolt = document.getElementById("idBatteryMax").value;
        if (batteryVolt != '')
            jeq2.batteryVolt = batteryVolt;

        /* 'commands' */
        jeq2.commands = eq.commands;

        /* Zigbee discovery if any */
        jeq2.discovery = eq.discovery;

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
    function writeJSON() {
        console.log("writeJSON()");

        /* Check if mandatory infos are there */
        if (checkMissingInfos() == false)
            return;

        js_jsonName = document.getElementById("idJsonName").value;
        js_jsonPath = 'core/config/devices_local/'+js_jsonName+'/'+js_jsonName+'.json';
        js_jsonLocation = "local";

        jeq = prepareJson();
console.log(jeq);

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
    function downloadDiscovery() {
        console.log("downloadDiscovery()");

        text = JSON.stringify(eq.discovery);
        let elem = window.document.createElement('a');
        elem.style = "display: none";
        elem.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        elem.setAttribute('download', "discovery.json");
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }

    function download2() {
        console.log("download2()");

        js_jsonName = document.getElementById("idJsonName").value;
        // TODO: Search in following order: devices_local then devices
        js_jsonPath = js_pluginDir+'/core/config/devices_local/'+js_jsonName+'/'+js_jsonName+'.json';

        window.open('plugins/Abeille/core/php/AbeilleDownload.php?pathfile='+js_jsonPath, "_blank", null);
    }

    /* Request device info
     */
    function requestInfos(infoType, epId = "01", clustId = "0000", option = "") {
        console.log("requestInfos("+infoType+", ep="+epId+")");

        // 'Cmd'.$device->getLogicalId().'/readAttribute', 'ep=01&clustId=0005&attrId=0000'
        logicalId = "Abeille"+js_zgNb+"_"+js_eqAddr;
        if (infoType == "epList") {
            topic = "Cmd"+logicalId+"_ActiveEndPoint";
            payload = "address="+js_eqAddr;
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
            topic = "Cmd"+logicalId+"_SimpleDescriptorRequest";
            payload = "address="+js_eqAddr+"_endPoint="+epId;
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
        } else {
            console.log("requestInfos("+infoType+") => UNEXPECTED type !");
            return;
        }

        var xhttp = new XMLHttpRequest();
        var url = "plugins/Abeille/core/php/AbeilleCliToQueue.php";
        xhttp.open("GET", url+"?action=sendMsg&queueId="+js_queueKeyXmlToCmd+"&topic="+topic+"&payload="+payload, true);
        xhttp.send();
    }

    /* Request read of missing attribute values for given ep-clustId */
    function requestAttribValues(epId = "01", clustId = "0000") {
        console.log("requestAttribValues("+epId+", "+clustId+")");

        ep = eq.discovery.endPoints[epId];
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
            sEpList = res.epList;
            sEpArr = sEpList.split('/');

            /* Updating internal datas */
            eq.discovery.epCount = sEpArr.length;
            endPoints = new Object;
            sEpArr.forEach((ep) => {
                endPoints[ep] = new Object();
            });
            eq.discovery.endPoints = endPoints;

            /* Updating display */
            document.getElementById("idEPList").value = sEpList;
            changeClass("idEPListRB", "btn-warning", "btn-success");
            var endPoints = "";
            $("#idendPoints").empty();
            sEpArr.forEach((ep) => {
                h = '<br><div id="idEP'+ep+'">';
                h += '<label id="idClustEP'+ep+'" class="col-lg-2 control-label"></label>';

                h += '<div class="col-lg-10">';
                // h += '<a class="btn btn-warning" title="Raffraichi la liste des clusters" onclick="requestInfos(\'clustersList\', \''+ep+'\')"><i class="fas fa-sync"></i></a>';
                h += '<br><br>';
                h += '</div>';

                /* Display manufacturer/modelId & location if cluster 0000 is supported */
                h += '<div id="idEP'+ep+'Model" style="margin-left:30px; display: none">';
                h += '<div class="row">';
                h += '<label class="col-lg-2 control-label" for="fname">Fabricant:</label>';
                h += '<div class="col-lg-10">';
                h += '<a id="idZbManuf'+ep+'RB" class="btn btn-warning" title="Raffraichi le nom du fabricant" onclick="requestInfos(\'manufacturer\', \''+ep+'\')"><i class="fas fa-sync"></i></a>';
                h += '<input type="text" id="idZbManuf'+ep+'" value="" readonly>';
                h += '</div>';
                h += '</div>';
                h += '<div class="row">';
                h += '<label class="col-lg-2 control-label" for="fname">Modèle:</label>';
                h += '<div class="col-lg-10">';
                h += '<a id="idZbModel'+ep+'RB" class="btn btn-warning" title="Raffraichi le nom du modèle" onclick="requestInfos(\'modelId\', \''+ep+'\')"><i class="fas fa-sync"></i></a>';
                h += '<input type="text" id="idZbModel'+ep+'" value="" readonly>';
                h += '</div>';
                h += '</div>';
                h += '<div class="row">';
                h += '<label class="col-lg-2 control-label" for="fname">Localisation:</label>';
                h += '<div class="col-lg-10">';
                h += '<a id="idZbLocation'+ep+'RB" class="btn btn-warning" title="Raffraichi la localisation" onclick="requestInfos(\'location\', \''+ep+'\')"><i class="fas fa-sync"></i></a>';
                h += '<input type="text" id="idZbLocation'+ep+'" value="" readonly>';
                h += '</div>';
                h += '</div>';
                h += '</div>';

                /* Display server clusters */
                h += '<div style="margin-left:30px">';
                h += '<label for="fname">Server/input clusters:</label>';
                h += '<br>';
                h += '<table id="idServClust'+ep+'">';
                h += '</table>';
                h += '<br>';

                /* Display client clusters */
                h += '<label for="fname">Client/output clusters:</label>';
                h += '<table id="idCliClust'+ep+'">';
                h += '</table>';
                h += '<br>';
                h += '</div>';
                h += '</div>';
                $("#idendPoints").append(h);

                if (endPoints != "")
                    endPoints += ", ";
                endPoints += ep;
                document.getElementById("idClustEP"+ep).innerHTML = "End point "+ep+":";
                $("#idEP"+ep).show();

                /* Requesting clusters list for each EP */
                requestInfos('clustersList', ep);
            });
        } else if (res.type == "simpleDesc") {
            // 'src' => 'parser',
            // 'type' => 'simpleDesc',
            // 'net' => $dest,
            // 'addr' => $SrcAddr,
            // 'ep' => $EPoint,
            // 'servClustList' => $inputClusters, // Format: 'xxxx/yyyy/zzzz'
            // 'cliClustList' => $outputClusters // Format: 'xxxx/yyyy/zzzz'
            sEp = res.ep;
            servClustArr = res.inClustList.split('/');
            cliClustArr = res.outClustList.split('/');

            /* Updating internal datas */
            discovery = eq.discovery;
            if (discovery.epCount == 0) {
                // EP list not received yet
                console.log("EP list not received yet => simpleDesc ignored.")
                return;
            }
            ep = discovery.endPoints[res.ep];
            ep.servClusters = new Object();
            servClustArr.forEach((clustId) => {
                ep.servClusters[clustId] = new Object();
            });
            ep.cliClusters = new Object();
            cliClustArr.forEach((clustId) => {
                ep.cliClusters[clustId] = new Object();
            });

            /* Updating display */
            var servClustTable = document.getElementById("idServClust"+sEp);
            var cliClustTable = document.getElementById("idCliClust"+sEp);
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
                var newCell = newRow.insertCell(0);
                newCell.innerHTML = clustId;
                // newCell.setAttribute('title', 'toto'); // Tcharp38 TODO: How to retrive cluster name ?
                newCell.innerHTML += '<a id="idServClust'+sEp+'-'+clustId+'RBEx" class="btn btn-warning" title="Découverte étendue des attributs" onclick="requestInfos(\'attribExtList\', \''+res.ep+'\', \''+clustId+'\', \'00\')"><i class="fas fa-sync"></i></a>';
                newCell.innerHTML += '<a id="idServClust'+sEp+'-'+clustId+'RB" class="btn btn-warning" title="Découverte des attributs" onclick="requestInfos(\'attribList\', \''+res.ep+'\', \''+clustId+'\', \'00\')"><i class="fas fa-sync"></i></a>';
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
                newCell.innerHTML += '<a id="idCliClust'+sEp+'-'+clustId+'RB" class="btn btn-warning" title="Découverte des attributs" onclick="requestInfos(\'attribList\', \''+res.ep+'\', \''+clustId+'\', \'01\')"><i class="fas fa-sync"></i></a>';
                requestInfos('attribList', sEp, clustId, '01');
            });
        } else if ((res.type == "discoverAttributesResponse") || (res.type == "discoverAttributesExtendedResponse")) {
            // 'src' => 'parser',
            // 'type' => 'discoverAttributesResponse' or 'discoverAttributesExtendedResponse'
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
            if (res.type == "discoverAttributesResponse") {
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
            ep = eq.discovery.endPoints[sEp];
            if (sDir)
                clust = ep.servClusters[sClustId];
            else
                clust = ep.cliClusters[sClustId];
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
                var newCell = row.insertCell(-1);
                newCell.innerHTML = '<a id="idServClust'+sEp+'-'+sClustId+'RB2" class="btn btn-warning" title="Lecture des valeurs des attributs" onclick="requestAttribValues(\''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                newCell.innerHTML += '<a id="idServClust'+sEp+'-'+sClustId+'RB3" class="btn btn-warning" title="Interrogation des commandes reçues" onclick="requestInfos(\'discoverCommandsReceived\', \''+sEp+'\', \''+sClustId+'\')"><i class="fas fa-sync"></i></a>';
                requestInfos('discoverCommandsReceived', sEp, sClustId);
                changeClass("idServClust"+sEp+"-"+sClustId+"RBEx", "btn-warning", "btn-success");
                changeClass("idServClust"+sEp+"-"+sClustId+"RB", "btn-warning", "btn-success");
            } else
                changeClass("idCliClust"+sEp+"-"+sClustId+"RB", "btn-warning", "btn-success");
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
            sEp = res.ep;
            sClustId = res.clustId;
            sAttrId = res.attrId;
            sStatus = res.status;
            sValue = res.value;

            // Updating internal infos
            if (sStatus == "00") {
                discovery = eq.discovery;
                if (typeof discovery.endPoints === "undefined")
                    discovery.endPoints = new Object();
                if (typeof discovery.endPoints[sEp] === "undefined")
                    discovery.endPoints[sEp] = new Object();
                ep = discovery.endPoints[sEp];
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

                /* Checking Cluster-000/PowerSource */
                if ((sClustId == "0000") && (sAttrId == "0007")) {
                    if (sValue == "03")
                        discovery.powerSource = "battery";
                    else
                        discovery.powerSource = "mains";
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
                    sValue = discovery.powerSource;
                } else if (sAttrId == "0010") {
                    field = document.getElementById("idZbLocation"+sEp);
                    idRB = "idZbLocation"+sEp+"RB"; // Refresh button
                }
            }
            if (field !== null) {
                if (res.status != "00")
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
                    changeClass("idServClust"+sEp+"-"+sClustId+"RB2", "btn-warning", "btn-success");
            }
        } else if (res.type == "readAttributesResponse") {
            // 'src' => 'parser',
            // 'type' => 'readAttributesResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ep' => $srcEp,
            // 'clustId' => $cluster,
            // 'attributes' => $attributes
            sEp = res.ep;
            sClustId = res.clustId;
            sAttributes = res.attributes;

            discovery = eq.discovery;
            if (typeof discovery.endPoints === "undefined")
                discovery.endPoints = new Object();
            if (typeof discovery.endPoints[sEp] === "undefined")
                discovery.endPoints[sEp] = new Object();
            ep = discovery.endPoints[sEp];
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
                            discovery.powerSource = "battery";
                        else
                            discovery.powerSource = "mains";
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
                        sValue = discovery.powerSource;
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
                    changeClass("idServClust"+sEp+"-"+sClustId+"RB2", "btn-warning", "btn-success");
            }
        } else if (res.type == "deviceAnnounce") {
            // 'src' => 'parser',
            // 'type' => 'deviceAnnounce',
            // 'net' => $dest,
            // 'addr' => $Addr,
            // 'ieee' => $IEEE
            console.log("deviceAnnounce: new addr="+res.addr)
            js_eqAddr = res.addr;

            // Updating internal infos
            eq.addr = js_eqAddr;

            // Updating display
            document.getElementById("idAddr").value = res.addr;
        } else if (res.type == "commandsReceived") {
            // 'src' => 'parser',
            // 'type' => 'commandsReceived',
            // 'net' => $dest,
            // 'addr' => $srcAddress,
            // 'ep' => $srcEndPoint,
            // 'clustId' => $cluster,
            // 'commands' => $commands
            sEp = res.ep;
            sClustId = res.clustId;
            sCommands = res.commands;
            console.log("commandsReceived: clust="+sClustId);
            // console.log(sCommands);

            /* Updating internal datas */
            ep = eq.discovery.endPoints[sEp];
            clust = ep.servClusters[sClustId];
            if (typeof clust.commandsReceived === "undefined")
                clust.commandsReceived = new Object();
            clust.commandsReceived = sCommands;
        } else if (res.type == "defaultResponse") {
            // 'src' => 'parser',
            // 'type' => 'defaultResponse',
            // 'net' => $dest,
            // 'addr' => $srcAddr,
            // 'ep' => $srcEp,
            // 'clustId' => $cluster,
            // 'cmd' => $cmdId,
            // 'status' => $status
            sEp = res.ep;
            sClustId = res.clustId;
            sCmd = res.cmd;

            /* Updating internal datas & display */
            ep = eq.discovery.endPoints[sEp];
            clust = ep.servClusters[sClustId];
            if (sCmd == "11") { // Discover Commands Received
                if (typeof clust.commandsReceived === "undefined")
                    clust.commandsReceived = new Object();
                clust.commandsReceived = "UNSUPPORTED";

                id='idServClust'+sEp+'-'+sClustId+'RB3';
                changeClass(id, "btn-warning", "btn-success");
                button = document.getElementById(id);
                button.setAttribute('disabled', true);
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
        xhttp.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?queueId="+js_queueKeyCtrlToParser+"&msg=type:sendToCli_net:Abeille"+js_zgNb+"_addr:"+js_eqAddr+"_ieee:"+js_eqIeee, true);
        xhttp.send();

        requestInfos('epList');
    });

    /* Update Jeedom infos based on current JSON part */
    function updateJeedom() {
        /* TODO: To be updated
            - configuration:modeleJson
            - reload JSON to update commands
         */
    }

</script>
