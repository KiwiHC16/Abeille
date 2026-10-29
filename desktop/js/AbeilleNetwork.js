/* This file is part of Plugin Abeille for jeedom.
 *
 * Plugin Abeille for jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Plugin Abeille for jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin Abeille for jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

var currentZigateLG = 1; // Current zigate displayed in links graph
var currentZigateLT = 1; // Current zigate displayed in links table
var currentLGRenderer = null; // Active Viva.Graph renderer (disposed before creating a new one)
var prevTrackingMsg = ""; // Previous tracking message

// Memory leak fix: store timeout IDs to allow cleanup on page navigation
var abeilleNetworkTimeouts = [];

// Memory leak fix: cleanup function to clear all pending timeouts
function clearAbeilleNetworkTimeouts() {
    abeilleNetworkTimeouts.forEach(function(timeoutId) {
        clearTimeout(timeoutId);
    });
    abeilleNetworkTimeouts = [];
}

// Memory leak fix: cleanup on page unload or navigation
window.addEventListener('beforeunload', clearAbeilleNetworkTimeouts);
$(document).on('jeeObject-postLoad jeeObject-preSave', clearAbeilleNetworkTimeouts);

// $("#tab_nodes")
$('a[href="#idLinksTableTab"]')
    .off("click")
    .on("click", function () {
        console.log("idLinksTableTab click");
        displayLinksTable(currentZigateLT);
    });

$('a[href="#idLinksGraphTab"]')
    .off("click")
    .on("click", function () {
        console.log("idLinksGraphTab click");
        displayLinksGraph(currentZigateLG);
    });

// $("#tab_summary")
//     .off("click")
//     .on("click", function () {});

$("#nodeFrom")
    .off()
    .change(function () {
        var value = $(this).val();
        filterColumnOnValue(value, 0);
    });

$("#nodeTo")
    .off()
    .change(function () {
        var value = $(this).val();
        filterColumnOnValue(value, 2);
    });

/* Launch AbeilleLQI.php to collect network informations.
   Progress is displayed in 'AlertDiv' */
function refreshLqiTable(zgId, page) {
    console.log("refreshLqiTable(zgId=" + zgId + ", page=" + page + ")");

    /* Collect status displayed every 1sec */
    // Memory leak fix: store timeout ID for cleanup
    abeilleNetworkTimeouts.push(setTimeout(function () {
        trackLQICollectStatus(true, zgId);
    }, 1000));

    $.ajax({
        url: "/plugins/Abeille/core/php/AbeilleLQI.php?zigate=" + zgId,
        async: true,
        error: function (jqXHR, status, error) {
            //console.log("refreshLqiTable error status: " + status);
            //console.log("refreshLqiTable error msg: " + error);
            if (page == "linksTable") $("#idLinksTable tbody").empty();
            else $("#idLinksTable tbody").empty();
            $("#div_networkZigbeeAlert").showAlert({
                message: "ERREUR ! Impossible de démarrer la collecte.",
                level: "danger",
            });
            // Memory leak fix: store timeout ID for cleanup
            abeilleNetworkTimeouts.push(window.setTimeout(function () {
                $("#div_networkZigbeeAlert").hide();
            }, 10000));
        },
        success: function (data, status, jqhr) {
            //console.log("refreshLqiTable success status: " + status);
            //console.log("refreshLqiTable success msg: " + data);
            // php file checks for write rights
            console.log("AbeilleLQI.php output=" + data);
            if (data.indexOf("successfully") >= 0) {
                // levelAlert = "info";
                // $("#idLinksTable").trigger("update");
                if (page == "linksTable") displayLinksTable(zgId);
                else if (page == "lqiPath") loadLqiPathData(zgId);
                else displayLinksGraph(zgId);
                // trackLQICollectStatus(false, zgId);
            } else {
                $("#div_networkZigbeeAlert").showAlert({
                    message: data,
                    level: "danger",
                });
                // Memory leak fix: store timeout ID for cleanup
                abeilleNetworkTimeouts.push(window.setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000));
            }
        },
    });
}

function refreshRoutes(device) {
    console.log("refreshRoutes(" + device + ")");
    // $.ajax({
    //     type: "POST",
    //     url:
    //         "/plugins/Abeille/core/ajax/AbeilleRoutes.ajax.php?device=" +
    //         device,
    // });
    $.ajax({
        type: "POST",
        url: "/plugins/Abeille/core/php/AbeilleRoutes.php",
    });
}

function refreshNoise(Device) {
    console.log("refreshNoise(${Device})");
    $.ajax({
        url: "/plugins/Abeille/core/php/AbeilleNoise.php?device=" + Device,
    });
}

// Tcharp38: Seems unused
// function getAbeilleLog(_autoUpdate, _log) {
//     $.ajax({
//         type: "POST",
//         url: "core/ajax/log.ajax.php",
//         data: {
//             action: "get",
//             log: _log,
//         },
//         dataType: "json",
//         global: false,
//         error: function (request, status, error) {
//             setTimeout(function () {
//                 getAbeilleLog(_autoUpdate, _log);
//             }, 1000);
//         },
//         success: function (data) {
//             if (data.state != "ok") {
//                 setTimeout(function () {
//                     getAbeilleLog(_autoUpdate, _log);
//                 }, 1000);
//                 return;
//             }
//             if ($.isArray(data.result)) {
//                 var aLog = data.result;
//                 //console.log(aLog.length);
//                 //console.log(aLog);
//                 log = aLog.filter(function (val) {
//                     return val.toLowerCase().indexOf("lqi") > -1;
//                 });
//                 //console.log('last log LQI: ' + log.reverse()[0]);
//                 //log = data.result[data.result.length-1];
//                 $("#div_networkZigbeeAlert").showAlert({
//                     message: log.reverse()[0],
//                     level: "success",
//                 });
//             }
//             if (init(_autoUpdate, 0) == 1) {
//                 setTimeout(function () {
//                     getAbeilleLog(_autoUpdate, _log);
//                 }, 1000);
//             }
//         },
//     });
// }

/* Read & display lock file content until "done" found */
function trackLQICollectStatus(_autoUpdate, zgId) {
    console.log("trackLQICollectStatus(zgId=" + zgId + ")");

    $.ajax({
        type: "POST",
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: "getTmpFile",
            file: "AbeilleLQI.lock",
        },
        dataType: "json",
        global: false,
        cache: false,
        error: function (request, status, error) {
            console.log("trackLQICollectStatus: error status=" + status);
            console.log("trackLQICollectStatus: error msg=" + error);
            $("#div_networkZigbeeAlert").showAlert({
                message: "ERREUR ! Problème du lecture du fichier de lock.",
                level: "danger",
            });
            _autoUpdate = 0;
            // Memory leak fix: store timeout ID for cleanup
            abeilleNetworkTimeouts.push(setTimeout(function () {
                $("#div_networkZigbeeAlert").hide();
            }, 10000));
        },
        success: function (json_res) {
            res = JSON.parse(json_res.result); // res.status, res.error, res.content
            console.log("res=" + JSON.stringify(res));
            if (res.status != 0) {
                var msg = "ERREUR ! " + res.error;
                $("#div_networkZigbeeAlert").showAlert({
                    message: msg,
                    level: "danger",
                });
                _autoUpdate = 0;
                // Memory leak fix: store timeout ID for cleanup
                abeilleNetworkTimeouts.push(setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000));
            } else if (res.content == "") {
                $("#div_networkZigbeeAlert").showAlert({
                    message: "{{Fichier lock vide}}",
                    level: "danger",
                });
                // Memory leak fix: store timeout ID for cleanup
                abeilleNetworkTimeouts.push(setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000));
            } else {
                var content = res.content;
                console.log("Status='" + content + "'");
                var alertLevel = "success";
                if (content.toLowerCase().includes("oops")) {
                    alertLevel = "danger";
                    _autoUpdate = 0;
                } else if (content.includes("= Collecte")) {
                    // Reminder: '= Collecte terminée: <timestamp>/<status>'
                    content = "Collecte terminée";
                    _autoUpdate = 0; // Stop status refresh
                }

                if (content != prevTrackingMsg) {
                    $("#div_networkZigbeeAlert").showAlert({
                        message: content,
                        level: alertLevel,
                    });
                    prevTrackingMsg = content;
                }

                /* Collect status display stops when "done" found */
                // _autoUpdate = content.toLowerCase().includes("done")?0:1;
                if (_autoUpdate) {
                    // Next status update in 1s
                    // Memory leak fix: store timeout ID for cleanup
                    abeilleNetworkTimeouts.push(setTimeout(function () {
                        trackLQICollectStatus(_autoUpdate, zgId);
                    }, 1000));
                } else {
                    // Keep last message 10sec then hide
                    // Memory leak fix: store timeout ID for cleanup
                    abeilleNetworkTimeouts.push(setTimeout(function () {
                        $("#div_networkZigbeeAlert").hide();
                    }, 10000));
                }
            }
        },
    });
}

/* Display nodes table */
function displayLinksTable(zgId) {
    console.log("displayLinksTable(zgId=" + zgId + ")");
    currentZigateLT = zgId;
    $("#idLinksTableSearch").val(""); // Reset search filter on network change

    var jqXHR = $.ajax({
        type: "POST",
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: "getTmpFile",
            file: "AbeilleLQI-Abeille" + zgId + ".json",
        },
        dataType: "json",
        cache: false,
    });

    jqXHR.done(function (json, textStatus, jqXHR) {
        res = JSON.parse(json.result);
        if (res.status != 0) {
            // -1 = Most probably file not found
            $("#div_networkZigbeeAlert").showAlert({
                message:
                    "{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}",
                level: "danger",
            });
        } else if (res.content == "") {
            $("#div_networkZigbeeAlert").showAlert({
                message:
                    "{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}",
                level: "danger",
            });
        } else {
            // console.log("res.content=", res.content);
            // var json = JSON.parse(res.content);
            // var nodes = json.data;
            // nodes.sort(function (a, b) {
            //     if (a.Voisin_Name == b.Voisin_Name) {
            //         return 0;
            //     }
            //     if (a.Voisin_Name > b.Voisin_Name) {
            //         return 1;
            //     }
            //     if (a.Voisin_Name < b.Voisin_Name) {
            //         return -1;
            //     }
            // });

            var lqiTable = JSON.parse(res.content);
            console.log("lqiTable=", lqiTable);

            $("#idCurrentNetworkLT").empty().append(lqiTable.net);

            const date = new Date(lqiTable.collectTime * 1000);
            var out =
                date.toLocaleDateString() + " " + date.toLocaleTimeString();
            $("#idCurrentDateLT").empty().append(out);

            if (lqiTable.routers.length == 0) {
                console.log("No routers");
                $("#idLinksTable tbody").empty(); // Clear table content
                return;
            }

            var nodesTo = new Object(),
                nodesFrom = new Object();
            var nodeJId, nodeLogicId;
            var tbody = "";
            for (rLogicId in lqiTable.routers) {
                router = lqiTable.routers[rLogicId];
                console.log("router " + rLogicId + "=", router);

                for (nLogicId in router.neighbors) {
                    neighbor = router.neighbors[nLogicId];
                    console.log("neighbor=", neighbor);

                    tbody += "<tr>";

                    // Router parent
                    tbody += '<td id="neObjet">';
                    tbody +=
                        '<div style="opacity:0.5"><i>' +
                        router["parentName"] +
                        "</i></div>";
                    tbody += "</td>";

                    // Router name
                    tbody += '<td id="neName">';
                    tbody +=
                        '<div style="opacity:0.5"><i>' +
                        router["name"] +
                        "</i></div>";
                    tbody += "</td>";

                    // Router address
                    tbody += '<td id="neId">';
                    tbody +=
                        '<span class="label label-primary" style="font-size : 1em;" >' +
                        router["addr"] +
                        "</span> ";
                    tbody += "</td>";

                    // Neighbor parent
                    tbody += '<td id="vObjet">';
                    tbody += neighbor["parentName"];
                    tbody += "</td>";

                    // Neighbor name
                    tbody += '<td id="vname">';
                    tbody += neighbor["name"];
                    tbody += "</td>";

                    // Neighbor address
                    tbody += '<td id="vid">';
                    tbody +=
                        '<span class="label label-primary" style="font-size : 1em;" >' +
                        neighbor["addr"] +
                        "</span>";
                    tbody += "</td>";

                    tbody += "<td>";
                    tbody +=
                        '<span class="label label-success" style="font-size : 1em;" >' +
                        neighbor["type"] +
                        "</span>";
                    tbody += "</td>";

                    tbody += "<td>";
                    tbody +=
                        '<span class="label label-success" style="font-size : 1em;">' +
                        neighbor["relationship"] +
                        "</span>";
                    tbody += "</td>";

                    tbody += "<td>";
                    tbody +=
                        '<span class="label label-success" style="font-size : 1em;">' +
                        neighbor["depth"] +
                        "</span>";
                    tbody += "</td>";

                    tbody += '<td id="lqi">';
                    tbody +=
                        neighbor["lqi"] <= 50
                            ? '<span class="label label-danger"  style="font-size : 1em;" >'
                            : "";
                    tbody +=
                        neighbor["lqi"] > 50 && neighbor["lqi"] <= 100
                            ? '<span class="label label-warning" style="font-size : 1em;">'
                            : "";
                    tbody +=
                        neighbor["lqi"] > 100
                            ? '<span class="label label-success" style="font-size : 1em;">'
                            : "";
                    tbody += neighbor["lqi"] + "</span></td>";

                    tbody += "</tr>";
                } // End neighbors
            } // End routers

            // for (var nodeIdx in nodes) {
            //     //Handle ZigBee name error
            //     if (nodes[nodeIdx].Voisine_Name == null) {
            //         nodes[nodeIdx].Voisine_Name = nodes[nodeIdx].IEEE_Address;
            //     }

            //     if (nodes[nodeIdx].NE == null) {
            //         nodes[nodeIdx].NE = nodes[nodeIdx].IEEE_Address;
            //     }

            //     //Populate selectBox options
            //     nodesTo[nodes[nodeIdx].Voisine] = nodes[nodeIdx].Voisine_Name;
            //     nodesFrom[nodes[nodeIdx].NE] = nodes[nodeIdx].NE_Name;

            //     //New Row
            //     // tbody += (nodes[nodeIdx].LinkQualityDec > 100) ? '<tr>' : '<tr class="active">';
            //     tbody += "<tr>";
            //     //process NE to jeedom id
            //     nodeLogicId = nodes[nodeIdx].NE;
            //     //zigbee LQI result is not null
            //     nodeJId =
            //         nodesFromJeedom[
            //             "Abeillex" + (nodeLogicId == 0 ? "Ruche" : nodeLogicId)
            //         ];
            //     //if no match to jeedom db
            //     if (nodeJId == "undefined" || nodeJId == null) {
            //         nodeJId = "not found in jeedom DB";
            //     }

            // // Object
            // tbody += '<td id="neObjet">';
            // tbody +=
            //     '<div style="opacity:0.5"><i>' +
            //     nodes[nodeIdx].NE_Objet +
            //     "</i></div>";
            // tbody += "</td>";

            // // Name
            // tbody += '<td id="neName">';
            // tbody +=
            //     '<div style="opacity:0.5"><i>' +
            //     nodes[nodeIdx].NE_Name +
            //     "</i></div>";
            // tbody += "</td>";

            // // Logic ID
            // tbody += '<td id="neId">';
            // tbody +=
            //     '<span class="label label-primary" style="font-size : 1em;" data-nodeid="' +
            //     nodeLogicId +
            //     '">' +
            //     nodeLogicId +
            //     "</span> ";
            // tbody += "</td>";

            // //Process Voisine to jeedom Id
            // nodeLogicId = nodes[nodeIdx].Voisine;
            // //zigbee LQI result is not null
            // nodeJId =
            //     nodesFromJeedom[
            //         "Abeillex" + (nodeLogicId == 0 ? "Ruche" : nodeLogicId)
            //     ];
            // //if no match to jeedom db
            // if (nodeJId == "undefined" || nodeJId == null) {
            //     nodeJId = "not found in jeedom DB";
            // }
            //console.log('nodeLogicId Voisine 2 (@ zigbee): ' + nodeLogicId + " , nodeJId: "+ nodeJId);

            // tbody += '<td id="vObjet">';
            // tbody += nodes[nodeIdx].Voisine_Objet;
            // tbody += "</td>";

            // tbody += '<td id="vname">';
            // tbody += nodes[nodeIdx].Voisine_Name;
            // tbody += "</td>";

            // tbody += '<td id="vid">';
            // tbody +=
            //     '<span class="label label-primary" style="font-size : 1em;" data-nodeid="' +
            //     nodes[nodeIdx].Voisine +
            //     '">' +
            //     nodes[nodeIdx].Voisine +
            //     "</span>";
            // tbody += "</td>";

            // tbody += "<td>";
            // tbody +=
            //     '<span class="label label-success" style="font-size : 1em;" >' +
            //     nodes[nodeIdx].Type +
            //     "</span>";
            // tbody += "</td>";

            // tbody += "<td>";
            // tbody +=
            //     '<span class="label label-success" style="font-size : 1em;">' +
            //     nodes[nodeIdx].Relationship +
            //     "</span>";
            // tbody += "</td>";

            // tbody += "<td>";
            // tbody +=
            //     '<span class="label label-success" style="font-size : 1em;">' +
            //     nodes[nodeIdx].Depth +
            //     "</span>";
            // tbody += "</td>";

            // tbody += '<td id="lqi">';
            // // tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[nodeIdx].LinkQualityDec + '  ';
            // tbody +=
            //     nodes[nodeIdx].LinkQualityDec <= 50
            //         ? '<span class="label label-danger"  style="font-size : 1em;" >'
            //         : "";
            // tbody +=
            //     nodes[nodeIdx].LinkQualityDec > 50 &&
            //     nodes[nodeIdx].LinkQualityDec <= 100
            //         ? '<span class="label label-warning" style="font-size : 1em;">'
            //         : "";
            // tbody +=
            //     nodes[nodeIdx].LinkQualityDec > 100
            //         ? '<span class="label label-success" style="font-size : 1em;">'
            //         : "";
            // tbody += nodes[nodeIdx].LinkQualityDec + "</span></td>";
            //     tbody += "<td></tr>";
            // }

            // if (nodes.length == 0) {
            //     console.log("No datas");
            //     $("#idCurrentNetworkLT")
            //         .empty()
            //         .append("Abeille" + zgId);
            //     $("#idCurrentDateLT")
            //         .empty()
            //         .append("AUCUNE DONNEE DISPONIBLE");
            //     $("#idLinksTable tbody").empty(); // Clear table content
            //     return;
            // }

            //construct table , append value to select button
            $("#idLinksTable tbody").empty().append(tbody);
            // var nodeFrom = $("#nodeFrom").empty(),
            //     nodeTo = $("#nodeTo").empty();

            // nodeFrom.append('<option value="All">{{Tous}}</option>');
            // $.each(nodesFrom, function (idx, item) {
            //     nodeFrom.append(new Option(item, idx));
            // });

            // nodeTo.append('<option value="All">{{Tous}}</option>');
            // $.each(nodesTo, function (idx, item) {
            //     nodeTo.append(new Option(item, idx));
            // });

            // $("#idLinksTable>tbody>tr>td:nth-child(1)")
            //     .off("click")
            //     .on("click", function () {
            //         var eqTypeId = $(this).children(1).attr("data-nodeid");
            //         //console.log("eqType: " + eqTypeId);
            //         if (eqTypeId.indexOf("not found") >= 0) {
            //             $("#div_networkZigbeeAlert").showAlert({
            //                 message:
            //                     "{{Pas de correspondance trouvée entre le noeud zigbee et jeedom. Ce noeud n'existe pas dans jeedom et/ou l'analyse de réseau n'est pas actualisée}}",
            //                 level: "info",
            //             });
            //             setTimeout(function () {
            //                 $("#div_networkZigbeeAlert").hide();
            //             }, 5000);
            //         } else {
            //             window.location.href = document.location.origin =
            //                 "/index.php?v=d&p=Abeille&m=Abeille&id=" + eqTypeId;
            //         }
            //     });

            // $("#idLinksTable>tbody>tr>td:nth-child(3)")
            //     .off("click")
            //     .on("click", function () {
            //         var eqTypeId = $(this).children(1).attr("data-nodeid");
            //         //console.log("eqType: " + eqTypeId);
            //         if (eqTypeId.indexOf("not found") >= 0) {
            //             $("#div_networkZigbeeAlert").showAlert({
            //                 message:
            //                     "{{Pas de correspondance trouvée entre le noeud zigbee et jeedom. Ce noeud n'existe pas dans jeedom et/ou l'analyse de réseau n'est pas actualisée}}",
            //                 level: "info",
            //             });
            //             setTimeout(function () {
            //                 $("#div_networkZigbeeAlert").hide();
            //             }, 5000);
            //         } else {
            //             window.location.href =
            //                 document.location.origin +
            //                 "/index.php?v=d&p=Abeille&m=Abeille&id=" +
            //                 eqTypeId;
            //         }
            //     });

            $("#idLinksTable").tablesorter({
                sortList: [
                    [0, 0],
                    [1, 0],
                ],
            });
            $("#idLinksTable").trigger("update");
            var nodes = json.data;

            /* Get network collect time */
            // $.ajax({
            //     type: "POST",
            //     url: "plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            //     data: {
            //         action: "getTmpFileModificationTime",
            //         file: "AbeilleLQI_MapDataAbeille" + zgId + ".json",
            //     },
            //     dataType: "json",
            //     global: false,
            //     cache: false,
            //     error: function (request, status, error) {
            //         bootbox.alert(
            //             "ERREUR 'getTmpFileModificationTime' !<br>" +
            //                 "status=" +
            //                 status +
            //                 "<br>error=" +
            //                 error
            //         );
            //     },
            //     success: function (json_res) {
            //         console.log(json_res);
            //         res = JSON.parse(json_res.result);
            //         if (res.status != 0) {
            //             var msg =
            //                 "ERREUR ! Quelque chose s'est mal passé.\n" +
            //                 res.error;
            //             alert(msg);
            //         } else {
            //             // window.location.reload();
            //             $("#idCurrentNetworkLT")
            //                 .empty()
            //                 .append("Abeille" + zgId);
            //             console.log(
            //                 "getTmpFileModificationTime() => " + res.mtime
            //             );
            //             const date = new Date(res.mtime * 1000);
            //             var out =
            //                 date.toLocaleDateString() +
            //                 " " +
            //                 date.toLocaleTimeString();
            //             $("#idCurrentDateLT").empty().append(out);
            //         }
            //     },
            // });
        }
    });

    jqXHR.fail(function (json, textStatus, jqXHR) {
        //console.log("network.js: displayLinksTable: fail: " + textStatus);
        var msg = "Données du réseau non trouvées. Forcez la réinterrogation.";
        $("#div_networkZigbeeAlert").showAlert({
            message: msg,
            level: "danger",
        });
        $("#idLinksTable tbody").empty();
    });

    jqXHR.always(function (json, textStatus, jqXHR) {
        setTimeout(function () {
            $("#div_networkZigbeeAlert").hide();
        }, 10000);
    });
}

// Find node by its logicId in 'nodes'. Returns null if not found
function getNodeByLogicId(logicId, nodes, newNodes) {
    for (nIdx in nodes) {
        n = nodes[nIdx];
        if (n.NE == logicId) return n;
    }
    for (nIdx in newNodes) {
        n = newNodes[nIdx];
        if (n.NE == logicId) return n;
    }
    return null;
}

// Retrieve list of equipments registered in Jeedom
function getEqList(gtwId) {
    var request = $.ajax({
        type: "POST",
        url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
        data: {
            action: "getEqList",
            gtwId: gtwId,
        },
        dataType: "json",
        cache: false,
    });

    return request;
}

// Retrieve LQI table for gateway 'gtwId'

// ============================================================
// ROUTES TAB
// ============================================================
function displayRoutesTable(zgId) {
    var container = $("#idRoutesTableContent");
    container.html("<div class='alert alert-info'><i class='fas fa-spinner fa-spin'></i> {{Chargement...}}</div>");

    var netName = "Abeille" + zgId;

    $.when(
        $.ajax({
            type: "POST",
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: { action: "getTmpFile", file: "AbeilleRoutes-" + netName + ".json" },
            dataType: "json",
            cache: false,
        }),
        getEqList(zgId)
    ).done(function (routesResp, eqResp) {
        var routesJson = routesResp[0];
        var eqJson     = eqResp[0];

        var outer;
        try { outer = JSON.parse(routesJson.result); } catch(e) {
            container.html("<div class='alert alert-danger'>{{Erreur de lecture du fichier JSON.}}</div>");
            return;
        }
        if (outer.status !== 0 || !outer.content) {
            container.html("<div class='alert alert-warning'>{{Aucune donnée pour " + netName + ". Cliquez sur \"Tout collecter\".}}</div>");
            return;
        }

        var parsed;
        try { parsed = JSON.parse(outer.content); } catch(e) {
            container.html("<div class='alert alert-danger'>{{Erreur de lecture du fichier JSON.}}</div>");
            return;
        }
        if (!parsed || !parsed.routers || Object.keys(parsed.routers).length === 0) {
            container.html("<div class='alert alert-warning'>{{Aucune route collectée pour " + netName + ".}}</div>");
            return;
        }

        // Build name lookup: logicalId => 'Parent-Name (addr)'
        var names = {};
        if (eqJson && eqJson.result) {
            var eqData = JSON.parse(eqJson.result);
            if (eqData && eqData.eqList) {
                for (var lid in eqData.eqList) {
                    var eq = eqData.eqList[lid];
                    var label = (eq.objectName ? eq.objectName + "-" : "") + eq.name + " (" + eq.addr + ")";
                    names[lid] = label;
                }
            }
        }
        function eqLabel(lid) { return names[lid] || lid; }

        var collectTime = parsed.collectTime ? new Date(parsed.collectTime * 1000).toLocaleString("fr-FR") : "?";
        var html = "<p><strong>" + netName + "</strong> &mdash; <small style='color:#888'>{{collecté le}} " + collectTime + "</small></p>";
        html += "<table class='table table-condensed table-bordered' style='width:auto'>";
        html += "<thead><tr><th>{{Source}}</th><th>{{Destination}}</th><th>{{Prochain saut}}</th><th>{{Statut}}</th></tr></thead><tbody>";

        var statusColors = {
            "Active":               "success",
            "Discovery_Underway":   "warning",
            "Discovery_Failed":     "danger",
            "Inactive":             "default",
            "Validation_Underway":  "info",
        };

        var rowCount = 0;
        for (var routerLid in parsed.routers) {
            var routerData = parsed.routers[routerLid];
            if (!routerData.table) continue;
            for (var dest in routerData.table) {
                var entry = routerData.table[dest];
                var nextHop, status;
                if (typeof entry === "object" && entry !== null) {
                    nextHop = entry.nextHop;
                    status  = entry.status || "";
                } else {
                    nextHop = entry; // legacy format (string)
                    status  = "Active";
                }
                var badgeClass = statusColors[status] || "default";
                html += "<tr>";
                html += "<td>" + $("<span>").text(eqLabel(routerLid)).html() + "</td>";
                html += "<td>" + $("<span>").text(eqLabel(netName + "/" + dest)).html() + "</td>";
                var nextHopLabel = (nextHop === dest) ? "{{Direct}}" : eqLabel(netName + "/" + nextHop);
                html += "<td>" + $("<span>").text(nextHopLabel).html() + "</td>";
                html += "<td><span class='label label-" + badgeClass + "'>" + status + "</span></td>";
                html += "</tr>";
                rowCount++;
            }
        }

        html += "</tbody></table>";
        if (rowCount === 0) {
            container.html("<div class='alert alert-warning'>{{Aucune route dans ce fichier.}}</div>");
        } else {
            container.html(html);
        }
    }).fail(function () {
        container.html("<div class='alert alert-danger'>{{Erreur lors du chargement des données.}}</div>");
    });
}

function getLqiTable(gtwId) {
    var request = $.ajax({
        type: "POST",
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: "getTmpFile",
            file: "AbeilleLQI-Abeille" + gtwId + ".json",
        },
        dataType: "json",
        cache: false,
    });

    return request;
}

//TODO fix on click link color change, link color upon LQI quality, node name .....
function displayLinksGraph(gtwId) {
    currentZigateLG = gtwId; // Track current network
    console.log("displayLinksGraph(" + gtwId + ")");

    // Get Jeedom equipements list => 'jeedomTable'
    jeedomTableReq = getEqList(gtwId);
    jeedomTableReq.fail(function () {
        $("#div_networkZigbeeAlert").showAlert({
            message:
                "ERREUR: Impossible de récupérer la liste des équipements Jeedom",
            level: "danger",
        });
    });

    jeedomTableReq.done(function (json) {
        res = JSON.parse(json.result);
        if (res.status != 0) {
            $("#div_networkZigbeeAlert").showAlert({
                message: "ERREUR: getEqList(" + gtwId + "): " + res.error,
                level: "danger",
            });
        } else {
            jeedomTable = res.eqList;
            console.log("jeedomTable=", jeedomTable);

            lqiTableReq = getLqiTable(gtwId);
            lqiTableReq.done(function (json) {
                res = JSON.parse(json.result);
                if (res.status != 0) {
                    // -1 = Most probably file not found
                    $("#div_networkZigbeeAlert").showAlert({
                        message:
                            "{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}",
                        level: "danger",
                    });
                } else if (res.content == "") {
                    $("#div_networkZigbeeAlert").showAlert({
                        message:
                            "{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}",
                        level: "danger",
                    });
                } else {
                    // console.log("res.content=", res.content);
                    var lqiTable = JSON.parse(res.content);
                    console.log("lqiTable=", lqiTable);

                    $("#idCurrentNetworkLG").empty().append(lqiTable.net);
                    const date = new Date(lqiTable.collectTime * 1000);
                    var out =
                        date.toLocaleDateString() +
                        " " +
                        date.toLocaleTimeString();
                    $("#idCurrentDateLG").empty().append(out);

                    if (lqiTable.routers.length == 0) {
                        console.log("No routers");
                        $("#idLinksGraphTab svg").remove(); // Remove previous one
                        return;
                    }

                    var graph = Viva.Graph.graph();
                    var showObject =
                        document.getElementById("idShowObject").checked; // Checked to display parent object

                    // console.log("routers=", lqiTable.routers);
                    for (rLogicId in lqiTable.routers) {
                        router = lqiTable.routers[rLogicId];
                        console.log("Adding router " + rLogicId + "=", router);

                        // console.log(
                        //     "Adding router " + rLogicId + " (" + router["name"] + ")"
                        // );
                        if (showObject)
                            nodeName = router.parentName + "/" + router.name;
                        else nodeName = router.name;
                        graph.addNode(rLogicId, {
                            name: nodeName,
                            type: router["type"],
                            icon: router["icon"],
                            dead:
                                typeof router["dead"] != "undefined"
                                    ? true
                                    : false,
                        });

                        for (nLogicId in router.neighbors) {
                            neighbor = router.neighbors[nLogicId];
                            console.log("neighbor=", neighbor);

                            if (showObject)
                                nodeName =
                                    neighbor.parentName + "/" + neighbor.name;
                            else nodeName = neighbor.name;

                            graph.addNode(nLogicId, {
                                name: nodeName,
                                type: neighbor["type"],
                                icon: neighbor["icon"],
                                dead: false,
                            });

                            console.log(
                                "Adding link from " +
                                    rLogicId +
                                    " to " +
                                    nLogicId
                            );
                            graph.addLink(rLogicId, nLogicId);
                        }
                    }

                    // Display Jeedom devices that are not visible on the network
                    for (devLogicId in jeedomTable) {
                        found = graph.getNode(devLogicId);
                        if (found != null) continue;

                        // Jeedom device not found on network
                        dev = jeedomTable[devLogicId];
                        if (dev.enabled == 0) continue; // Disabled

                        graph.addNode(devLogicId, {
                            name: dev.name,
                            type: dev.zbType,
                            icon: dev.icon,
                            dead: true, // Add death image
                        });
                    }

                    console.log("Render the graph.");
                    var graphics = Viva.Graph.View.svgGraphics();
                    var nodeSize = 50; // Node 50x50
                    var imgSize = 40; // Image 40x40 centered in node
                    var deathSize = 20; // Death image in bottom-right corner
                    graphics
                        .node(function (node) {
                            // console.log("LAAAAA node=", node);

                            var nodeColor = "#E5E500";
                            var nodeName = "?";
                            var iconName = "defaultUnknown";
                            let deadNode = false;

                            if (typeof node.data != "undefined") {
                                if (typeof node.data.type != "undefined") {
                                    if (node.data.type == "Coordinator") {
                                        nodeColor = "#a65ba6";
                                    } else if (node.data.type == "End Device") {
                                        nodeColor = "#7BCC7B";
                                    } else if (node.data.type == "Router") {
                                        nodeColor = "#00a2e8";
                                    }
                                }
                                if (typeof node.data.name != "undefined") {
                                    nodeName = node.data.name;
                                }
                                if (typeof node.data.icon != "undefined") {
                                    iconName = node.data.icon;
                                }
                                deadNode = node.data.dead;
                            }

                            svgText = Viva.Graph.svg("text")
                                .attr("y", "-3px")
                                .text(nodeName)
                                .attr("fill", nodeColor);
                            var img1 = Viva.Graph.svg("rect")
                                .attr("width", nodeSize)
                                .attr("height", nodeSize)
                                .attr("fill", nodeColor);
                            var img2 = Viva.Graph.svg("image")
                                .attr("x", 5)
                                .attr("y", 5)
                                .attr("width", imgSize)
                                .attr("height", imgSize)
                                .link(
                                    "/plugins/Abeille/core/config/devices_images/node_" +
                                        iconName +
                                        ".png"
                                );
                            var ui = Viva.Graph.svg("g");
                            ui.append(svgText);
                            ui.append(img1);
                            ui.append(img2);
                            if (deadNode) {
                                var img3 = Viva.Graph.svg("image")
                                    .attr("x", 31)
                                    .attr("y", 28)
                                    .attr("width", deathSize)
                                    .attr("height", deathSize)
                                    .link("/plugins/Abeille/images/death.png");
                                ui.append(img3);
                            }

                            return ui;
                        })
                        .placeNode(function (nodeUI, pos) {
                            // console.log("placeNode", nodeUI, pos);
                            nodeUI.attr(
                                "transform",
                                "translate(" +
                                    (pos.x - nodeSize / 3) +
                                    "," +
                                    (pos.y - nodeSize / 2.5) +
                                    ")"
                            );
                        });

                    var layout = Viva.Graph.Layout.forceDirected(graph, {
                        springLength: 150,
                        springCoeff: 0.0005,
                        stableThreshold: 0.1,
                        dragCoeff: 0.02,
                        gravity: -0.5,
                    });

                    if (currentLGRenderer) { currentLGRenderer.dispose(); currentLGRenderer = null; }
                    $("#idLinksGraphTabSVG svg").remove(); // Remove previous one

                    var renderer = Viva.Graph.View.renderer(graph, {
                        layout: layout,
                        graphics: graphics,
                        prerender: 10,
                        container:
                            document.getElementById("idLinksGraphTabSVG"),
                    });
                    renderer.run();
                    currentLGRenderer = renderer; // Store for disposal on next call
                    /*setTimeout(function () {
                            renderer.pause();
                            renderer.reset();
                        }, 200);
                        */

                    /* Get network collect time */
                    // $.ajax({
                    //     type: "POST",
                    //     url: "plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
                    //     data: {
                    //         action: "getTmpFileModificationTime",
                    //         file: "AbeilleLQI_MapDataAbeille" + zgId + ".json",
                    //     },
                    //     dataType: "json",
                    //     global: false,
                    //     cache: false,
                    //     error: function (request, status, error) {
                    //         bootbox.alert(
                    //             "ERREUR 'getTmpFileModificationTime' !<br>" +
                    //                 "status=" +
                    //                 status +
                    //                 "<br>error=" +
                    //                 error
                    //         );
                    //     },
                    //     success: function (json_res) {
                    //         console.log(json_res);
                    //         res = JSON.parse(json_res.result);
                    //         if (res.status != 0) {
                    //             var msg =
                    //                 "ERREUR ! Quelque chose s'est mal passé.\n" +
                    //                 res.error;
                    //             alert(msg);
                    //         } else {
                    //             // window.location.reload();
                    //             $("#idCurrentNetworkLG")
                    //                 .empty()
                    //                 .append("Abeille" + zgId);
                    //             console.log(
                    //                 "getTmpFileModificationTime() => " + res.mtime
                    //             );
                    //             const date = new Date(res.mtime * 1000);
                    //             var out =
                    //                 date.toLocaleDateString() +
                    //                 " " +
                    //                 date.toLocaleTimeString();
                    //             $("#idCurrentDateLG").empty().append(out);
                    //             currentZigateLG = zgId;
                    //         }
                    //     },
                    // });
                    // }
                }
            });

            lqiTableReq.fail(function () {
                var msg =
                    "Données du réseau non trouvées. Forcez la réinterrogation.";
                $("#div_networkZigbeeAlert").showAlert({
                    message: msg,
                    level: "danger",
                });
                $("#graph_network svg").remove();
            });

            lqiTableReq.always(function (data) {
                window.setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000);
            });
        }
    });
}


// ============================================================
// ROUTE A→B TAB
// ============================================================
var routeA2BGtwId = 1;
var routeA2BRouters = null; // parsed.routers from AbeilleRoutes JSON
var routeA2BNames  = null;  // logicalId → display label
var routeA2BLqi    = null;  // lqiTable.routers from AbeilleLQI JSON

// Find parent router of an End Device via Child relationship in LQI table
function routeA2BGetParent(netName, addr) {
    if (!routeA2BLqi) return null;
    for (var rid in routeA2BLqi) {
        var r = routeA2BLqi[rid];
        var nb = r.neighbors && r.neighbors[netName + "/" + addr];
        if (nb && nb.relationship === "Child") return r.addr;
    }
    var rSelf = routeA2BLqi[netName + "/" + addr];
    if (rSelf && rSelf.neighbors) {
        for (var nid in rSelf.neighbors) {
            if (rSelf.neighbors[nid].relationship === "Parent") return rSelf.neighbors[nid].addr;
        }
    }
    return null;
}

$('a[href="#idRouteA2BTab"]').off("click").on("click", function () {
    loadRouteA2BData(routeA2BGtwId);
});

$("#idRouteA2BCompute").off("click").on("click", function () {
    computeRouteA2B();
});

function loadRouteA2BData(gtwId) {
    routeA2BGtwId = gtwId;
    var netName = "Abeille" + gtwId;
    var fromSel = $("#idRouteA2BFrom");
    var toSel   = $("#idRouteA2BTo");
    var results = $("#idRouteA2BResults");

    fromSel.empty().append('<option value="">{{Chargement...}}</option>');
    toSel.empty().append('<option value="">{{Chargement...}}</option>');
    results.html("");

    $.when(
        $.ajax({
            type: "POST",
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: { action: "getTmpFile", file: "AbeilleRoutes-" + netName + ".json" },
            dataType: "json",
            cache: false,
        }),
        getEqList(gtwId),
        $.ajax({
            type: "POST",
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: { action: "getTmpFile", file: "AbeilleLQI-" + netName + ".json" },
            dataType: "json",
            cache: false,
        })
    ).done(function (routesResp, eqResp, lqiResp) {
        var outer;
        try { outer = JSON.parse(routesResp[0].result); } catch(e) {
            results.html("<div class='alert alert-danger'>{{Erreur de lecture du fichier JSON.}}</div>");
            return;
        }
        if (outer.status !== 0 || !outer.content) {
            fromSel.empty().append('<option value="">-- {{aucune donnée}} --</option>');
            toSel.empty().append('<option value="">-- {{aucune donnée}} --</option>');
            results.html("<div class='alert alert-warning'>{{Aucune donnée pour " + netName + ". Collectez d'abord les routes.}}</div>");
            return;
        }
        var parsed;
        try { parsed = JSON.parse(outer.content); } catch(e) {
            results.html("<div class='alert alert-danger'>{{Erreur de lecture.}}</div>"); return;
        }

        routeA2BRouters = parsed.routers || {};

        // Parse LQI data (non-blocking: ignore errors)
        routeA2BLqi = {};
        try {
            if (lqiResp && lqiResp[0] && lqiResp[0].result) {
                var lqiOuter = JSON.parse(lqiResp[0].result);
                if (lqiOuter.status === 0 && lqiOuter.content) {
                    var lqiParsed = JSON.parse(lqiOuter.content);
                    routeA2BLqi = lqiParsed.routers || {};
                }
            }
        } catch(e) {}

        // Build name lookup
        routeA2BNames = {};
        var eqResult = eqResp[0];
        if (eqResult && eqResult.result) {
            var eqData = JSON.parse(eqResult.result);
            if (eqData && eqData.eqList) {
                for (var lid in eqData.eqList) {
                    var eq = eqData.eqList[lid];
                    routeA2BNames[lid] = (eq.objectName ? eq.objectName + " - " : "") + eq.name + " (" + eq.addr + ")";
                }
            }
        }

        // Populate selects with ALL equipment from the network (not just routers with tables)
        var allEqAddrs = Object.keys(routeA2BNames)
            .filter(function(lid) { return lid.indexOf(netName + "/") === 0; })
            .sort(function(a, b) { return routeA2BNames[a].localeCompare(routeA2BNames[b]); })
            .map(function(lid) { return lid.split("/")[1]; });

        fromSel.empty().append('<option value="">-- {{sélectionner}} --</option>');
        toSel.empty().append('<option value="">-- {{sélectionner}} --</option>');
        allEqAddrs.forEach(function(addr) {
            var lid = netName + "/" + addr;
            var label = routeA2BNames[lid] || addr;
            var hasTable = !!routeA2BRouters[lid];
            var hasParent = !hasTable && !!routeA2BGetParent(netName, addr);
            var opt = '<option value="' + addr + '">' + label + (!hasTable && !hasParent ? ' ⚠' : '') + '</option>';
            fromSel.append(opt);
            toSel.append(opt);
        });
    }).fail(function () {
        results.html("<div class='alert alert-danger'>{{Erreur lors du chargement des données.}}</div>");
    });
}

// Trace hop-by-hop route from srcAddr to dstAddr using routing tables.
// Returns [{addr, status}, ...] or null if no route found.
function findRouteHops(routers, netName, srcAddr, dstAddr) {
    if (srcAddr === dstAddr) return [{addr: srcAddr, status: ""}];
    var path = [{addr: srcAddr, status: ""}];
    var visited = {};
    visited[srcAddr] = true;
    var current = srcAddr;
    for (var guard = 0; guard < 20; guard++) {
        var routerData = routers[netName + "/" + current];
        if (!routerData || !routerData.table) return null;
        var entry = routerData.table[dstAddr];
        if (!entry) return null;
        var nextHop = (typeof entry === "object") ? entry.nextHop : entry;
        var status  = (typeof entry === "object") ? (entry.status || "") : "Active";
        if (visited[nextHop]) return null; // loop
        path.push({addr: nextHop, status: status});
        visited[nextHop] = true;
        current = nextHop;
        if (current === dstAddr) return path;
    }
    return null; // max hops exceeded
}

function computeRouteA2B() {
    var srcAddr = $("#idRouteA2BFrom").val();
    var dstAddr = $("#idRouteA2BTo").val();
    var results = $("#idRouteA2BResults");

    if (!srcAddr || !dstAddr) {
        results.html("<div class='alert alert-warning'>{{Sélectionnez une source et une destination.}}</div>"); return;
    }
    if (srcAddr === dstAddr) {
        results.html("<div class='alert alert-warning'>{{Source et destination identiques.}}</div>"); return;
    }
    if (!routeA2BRouters) {
        results.html("<div class='alert alert-warning'>{{Données non chargées.}}</div>"); return;
    }

    var netName = "Abeille" + routeA2BGtwId;
    var srcHasTable = !!routeA2BRouters[netName + "/" + srcAddr];
    var dstHasTable = !!routeA2BRouters[netName + "/" + dstAddr];
    function addrLabel(addr) { return routeA2BNames[netName + "/" + addr] || addr; }
    function lqiBetween(addrA, addrB) {
        if (!routeA2BLqi) return null;
        var rA = routeA2BLqi[netName + "/" + addrA];
        if (rA && rA.neighbors && rA.neighbors[netName + "/" + addrB] !== undefined)
            return rA.neighbors[netName + "/" + addrB].lqi;
        var rB = routeA2BLqi[netName + "/" + addrB];
        if (rB && rB.neighbors && rB.neighbors[netName + "/" + addrA] !== undefined)
            return rB.neighbors[netName + "/" + addrA].lqi;
        return null;
    }

    var statusColors = {
        "Active": "success", "Discovery_Underway": "warning",
        "Discovery_Failed": "danger", "Inactive": "default",
        "Validation_Underway": "info",
    };

    function renderRoute(hops, title) {
        var html = "<div style='margin-bottom:20px'><strong>" + title + "</strong>";
        if (!hops) {
            return html + "<div class='alert alert-danger' style='margin-top:8px'>{{Route introuvable dans les tables collectées.}}</div></div>";
        }
        html += " <small style='color:#888'>(" + (hops.length - 1) + " {{saut(s)}})</small>";
        html += "<div style='display:flex; align-items:center; flex-wrap:wrap; gap:2px; margin-top:8px'>";
        hops.forEach(function(hop, idx) {
            if (idx > 0) {
                var bc = statusColors[hop.status] || "default";
                var lqi = lqiBetween(hops[idx - 1].addr, hop.addr);
                var lqiColor = lqi === null ? "#999" : (lqi <= 50 ? "#d9534f" : (lqi <= 100 ? "#f0ad4e" : "#5cb85c"));
                html += "<div style='text-align:center; padding:0 4px'>";
                html += "<div style='font-size:20px; line-height:1; color:#555'>&#8594;</div>";
                if (lqi !== null) html += "<div style='font-size:11px; font-weight:600; color:" + lqiColor + "'>LQI " + lqi + "</div>";
                if (hop.status) html += "<div><span class='label label-" + bc + "' style='font-size:10px; white-space:nowrap'>" + hop.status + "</span></div>";
                html += "</div>";
            }
            var isFirst = idx === 0, isLast = idx === hops.length - 1;
            var bg     = isFirst ? "#d4edda" : (isLast ? "#cce5ff" : "#fff3cd");
            var border = isFirst ? "#28a745" : (isLast ? "#004085" : "#856404");
            html += "<div style='border:2px solid " + border + "; border-radius:6px; padding:4px 10px; background:" + bg + "; text-align:center; min-width:80px'>";
            html += "<div style='font-family:monospace; font-size:11px; color:#666'>" + $("<span>").text(hop.addr).html() + "</div>";
            html += "<div style='font-size:12px; font-weight:600'>" + $("<span>").text(addrLabel(hop.addr)).html() + "</div>";
            html += "</div>";
        });
        html += "</div></div>";
        return html;
    }

    function getParentAddr(addr) { return routeA2BGetParent(netName, addr); }

    // Build full route: resolve End Devices to their parent router, then trace
    function buildRoute(fromAddr, toAddr) {
        var fromHasTable = !!routeA2BRouters[netName + "/" + fromAddr];
        var toHasTable   = !!routeA2BRouters[netName + "/" + toAddr];
        var fromParent   = fromHasTable ? null : getParentAddr(fromAddr);
        var toParent     = toHasTable   ? null : getParentAddr(toAddr);
        var fromEff = fromParent || fromAddr;
        var toEff   = toParent   || toAddr;
        if (!routeA2BRouters[netName + "/" + fromEff]) return null; // no table for effective src
        var hops;
        if (fromEff === toEff) {
            hops = [{addr: fromEff, status: ""}];
        } else {
            hops = findRouteHops(routeA2BRouters, netName, fromEff, toEff);
        }
        if (!hops) return null;
        if (fromParent) hops.unshift({addr: fromAddr, status: ""});
        if (toParent)   hops.push({addr: toAddr, status: ""});
        return hops;
    }

    // Determine routability (has table OR has a known parent router)
    function canRoute(addr) {
        return !!routeA2BRouters[netName + "/" + addr] || !!getParentAddr(addr);
    }

    var noTableMsg = "<div class='alert alert-warning'>{{Table de routage non collectée et aucune liaison Child trouvée pour cet équipement.}}</div>";
    var fwd = canRoute(srcAddr) ? buildRoute(srcAddr, dstAddr) : null;
    var ret = canRoute(dstAddr) ? buildRoute(dstAddr, srcAddr) : null;

    var fwdTitle = "&#8594; {{Aller}} : " + $("<span>").text(addrLabel(srcAddr)).html() + " &#8594; " + $("<span>").text(addrLabel(dstAddr)).html();
    var retTitle = "&#8592; {{Retour}} : " + $("<span>").text(addrLabel(dstAddr)).html() + " &#8594; " + $("<span>").text(addrLabel(srcAddr)).html();
    results.html(
        (canRoute(srcAddr) ? renderRoute(fwd, fwdTitle) : "<div style='margin-bottom:20px'><strong>" + fwdTitle + "</strong>" + noTableMsg + "</div>") +
        (canRoute(dstAddr) ? renderRoute(ret, retTitle) : "<div style='margin-bottom:20px'><strong>" + retTitle + "</strong>" + noTableMsg + "</div>")
    );
}


// ============================================================
// LQI PATH TAB
// ============================================================
var lqiPathGtwId = 1;
var lqiPathAdj   = null;  // { nodeId: { neighborId: lqi } }  (directed, from router's view)
var lqiPathNames = null;  // { nodeId: displayName }
var lqiPathTypes = null;  // { nodeId: "Router"|"Coordinator"|"End Device" }

$("#idLqiPathTab").off("click").on("click", function () {
    if (!lqiPathAdj) loadLqiPathData(1);
});

$("#idLqiPathCompute").off("click").on("click", function () {
    computeLqiPaths();
});

function loadLqiPathData(gtwId) {
    lqiPathGtwId = gtwId;
    $("#idLqiPathResults").empty();

    $.when(getLqiTable(gtwId), getEqList(gtwId)).done(function (json, eqResp) {
        var res = JSON.parse(json[0].result);
        if (res.status != 0 || res.content === "") {
            $("#div_lqiPathAlert").showAlert({
                message: "{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}",
                level: "danger"
            });
            return;
        }
        $("#div_lqiPathAlert").hide();

        var lqiTable = JSON.parse(res.content);
        var adj = {}, names = {}, types = {};

        for (var rId in lqiTable.routers) {
            var router = lqiTable.routers[rId];
            names[rId] = router.name;
            types[rId] = router.type || "Router";
            if (!adj[rId]) adj[rId] = {};
            var nbrs = router.neighbors;
            if (!nbrs || Array.isArray(nbrs)) continue;
            for (var nId in nbrs) {
                adj[rId][nId] = nbrs[nId].lqi;
                if (!names[nId]) names[nId] = nbrs[nId].name;
                if (!types[nId]) types[nId] = nbrs[nId].type || "Router";
                if (!adj[nId]) adj[nId] = {};
            }
        }

        // Override names with Objet - Nom from eqList
        try {
            var eqData = JSON.parse(eqResp[0].result);
            if (eqData && eqData.eqList) {
                for (var lid in eqData.eqList) {
                    var eq = eqData.eqList[lid];
                    names[lid] = (eq.objectName ? eq.objectName + " - " : "") + eq.name;
                }
            }
        } catch(e) {}

        lqiPathAdj   = adj;
        lqiPathNames = names;
        lqiPathTypes = types;

        var fromSel = $("#idLqiPathFrom").empty();
        var toSel   = $("#idLqiPathTo").empty();
        var coordId = "Abeille" + gtwId + "/0000";

        var nodeList = Object.keys(names).sort(function (a, b) {
            return (names[a] || a).localeCompare(names[b] || b);
        });
        nodeList.forEach(function (nodeId) {
            var addr = nodeId.split("/")[1];
            var label = (names[nodeId] || nodeId) + "  (" + addr + ")";
            fromSel.append(new Option(label, nodeId));
            toSel.append(new Option(label, nodeId));
        });
        fromSel.val(coordId);
        var nonCoord = nodeList.filter(function (n) { return n !== coordId; });
        if (nonCoord.length) toSel.val(nonCoord[0]);
    });
}

function lqiForHop(a, b) {
    if (lqiPathAdj[a] && lqiPathAdj[a][b] !== undefined) return lqiPathAdj[a][b];
    if (lqiPathAdj[b] && lqiPathAdj[b][a] !== undefined) return lqiPathAdj[b][a];
    return null;
}

function lqiColor(lqi) {
    if (lqi === null) return "#888";
    if (lqi > 100) return "#3c763d";
    if (lqi > 50)  return "#8a6d3b";
    return "#a94442";
}

function computeLqiPaths() {
    if (!lqiPathAdj) return;
    var start = $("#idLqiPathFrom").val();
    var end   = $("#idLqiPathTo").val();
    if (!start || !end || start === end) {
        $("#idLqiPathResults").html("<div class='alert alert-warning'>{{Sélectionnez deux équipements différents.}}</div>");
        return;
    }

    // Build undirected neighbor sets for traversal
    var undirected = {};
    for (var a in lqiPathAdj) {
        if (!undirected[a]) undirected[a] = [];
        for (var b in lqiPathAdj[a]) {
            if (!undirected[b]) undirected[b] = [];
            if (undirected[a].indexOf(b) < 0) undirected[a].push(b);
            if (undirected[b].indexOf(a) < 0) undirected[b].push(a);
        }
    }

    var allPaths = [];
    var MAX_PATHS = 100;

    function dfs(cur, path, visited) {
        if (allPaths.length >= MAX_PATHS) return;
        if (cur === end) { allPaths.push(path.slice()); return; }
        var nbrs = undirected[cur] || [];
        for (var i = 0; i < nbrs.length; i++) {
            var next = nbrs[i];
            if (visited[next]) continue;
            // End Devices cannot be intermediate hops (only start or end)
            if (next !== end && lqiPathTypes && lqiPathTypes[next] === "End Device") continue;
            visited[next] = true;
            path.push(next);
            dfs(next, path, visited);
            path.pop();
            visited[next] = false;
        }
    }

    var visited = {};
    visited[start] = true;
    dfs(start, [start], visited);

    if (!allPaths.length) {
        $("#idLqiPathResults").html("<div class='alert alert-warning'>{{Aucun chemin trouvé entre ces deux équipements.}}</div>");
        return;
    }

    var minLqiThreshold = parseInt($("#idLqiPathMinLqi").val()) || 0;

    // Sort: fewest hops first, then best minimum LQI first
    allPaths.sort(function (a, b) {
        if (a.length !== b.length) return a.length - b.length;
        var minA = 999, minB = 999;
        for (var i = 0; i < a.length - 1; i++) { var l = lqiForHop(a[i], a[i+1]); if (l !== null && l < minA) minA = l; }
        for (var i = 0; i < b.length - 1; i++) { var l = lqiForHop(b[i], b[i+1]); if (l !== null && l < minB) minB = l; }
        return minB - minA;
    });

    var limited = allPaths.length >= MAX_PATHS;
    var validPaths = [], invalidCount = 0;

    allPaths.forEach(function (path) {
        var minLqi = 999;
        for (var i = 0; i < path.length - 1; i++) {
            var l = lqiForHop(path[i], path[i+1]);
            if (l !== null && l < minLqi) minLqi = l;
        }
        if (minLqi === 999 || minLqi >= minLqiThreshold) validPaths.push({ path: path, minLqi: minLqi });
        else invalidCount++;
    });

    // "Most probable" = highest min LQI (best bottleneck), tie-break: fewest hops
    var bestIdx = -1;
    if (validPaths.length) {
        bestIdx = 0;
        for (var i = 1; i < validPaths.length; i++) {
            var cur = validPaths[i], best = validPaths[bestIdx];
            if (cur.minLqi > best.minLqi || (cur.minLqi === best.minLqi && cur.path.length < best.path.length))
                bestIdx = i;
        }
    }

    // Sort remaining: fewest hops first, then best min LQI
    var bestEntry = validPaths.splice(bestIdx, 1)[0];
    validPaths.sort(function (a, b) {
        if (a.path.length !== b.path.length) return a.path.length - b.path.length;
        return b.minLqi - a.minLqi;
    });
    if (bestEntry) validPaths.unshift(bestEntry);

    function renderPath(entry, idx, isBest) {
        var path = entry.path, minLqi = 999, parts = [];
        for (var i = 0; i < path.length; i++) {
            var nodeId = path[i];
            var name = lqiPathNames[nodeId] || nodeId;
            var addr = nodeId.split("/")[1];
            parts.push("<strong title='" + nodeId + "'>" + name + "</strong><small style='color:#777'>&nbsp;(" + addr + ")</small>");
            if (i < path.length - 1) {
                var lqi = lqiForHop(path[i], path[i+1]);
                if (lqi !== null && lqi < minLqi) minLqi = lqi;
                var lqiLabel = lqi !== null ? lqi : "?";
                parts.push("&nbsp;<span style='color:" + lqiColor(lqi) + "'>&rarr;&nbsp;LQI:" + lqiLabel + "&nbsp;&rarr;</span>&nbsp;");
            }
        }
        var minStr = minLqi < 999
            ? " &nbsp;<small>(min&nbsp;LQI:&nbsp;<span style='color:" + lqiColor(minLqi) + ";font-weight:bold'>" + minLqi + "</span>)</small>"
            : "";
        var border = isBest ? "2px solid #3c763d" : "1px solid #ddd";
        var bg     = isBest ? "background:#f4fff4;" : "";
        var badge  = isBest ? " &nbsp;<span class='label label-success'>{{Le plus probable}}</span>" : "";
        var label  = isBest
            ? "<strong style='color:#3c763d'>Chemin&nbsp;" + (idx+1) + " &mdash; " + (path.length-1) + " saut(s)</strong>"
            : "<span style='color:#777'>Chemin&nbsp;" + (idx+1) + " &mdash; " + (path.length-1) + " saut(s)</span>";
        return "<div style='margin-bottom:8px;padding:8px;border:" + border + ";border-radius:4px;" + bg + "'>"
            + label + minStr + badge + "<br/>"
            + parts.join("") + "</div>";
    }

    var html = "";
    validPaths.forEach(function (entry, idx) {
        html += renderPath(entry, idx, idx === 0 && bestEntry);
    });

    var summary = validPaths.length + " chemin(s) valide(s)";
    if (invalidCount) summary += ", " + invalidCount + " exclu(s) (LQI min &lt; " + minLqiThreshold + ")";
    if (limited) summary += " — limite " + MAX_PATHS + " atteinte";
    if (!validPaths.length) html = "<div class='alert alert-warning'>{{Aucun chemin valide avec LQI min ≥ " + minLqiThreshold + ".}}</div>";
    html = "<p><strong>" + summary + "</strong></p>" + html;

    $("#idLqiPathResults").html(html);
}

function filterLinksTable(value) {
    var v = value.toLowerCase();
    $("#idLinksTable > tbody > tr").each(function (idx, row) {
        if (v === "") {
            row.style.display = "";
        } else if (row.innerHTML.toLowerCase().includes(v)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

$("#idLinksTableSearch").off("input").on("input", function () {
    filterLinksTable($(this).val());
});

function filterColumnOnValue(data, col) {
    var filterValue = data;
    var filterColumn = col;
    //console.log('filtering col ' + filterColumn + ' on value ' + filterValue);
    $("#idLinksTable > tbody > tr").each(function (idx, val) {
        //console.log(val);
        switch (filterValue) {
            case "None":
                val.style.display = "none";
                break;
            case "All":
                val.style.display = "";
                break;
            default:
                if (
                    val.children[filterColumn].innerHTML.includes(filterValue)
                ) {
                    //console.log(val.innerHTML);
                    val.style.display = "";
                } else {
                    val.style.display = "none";
                }
                break;
        }
    });
}

// ============================================================
// BINDINGS TAB
// ============================================================
var bindingsZgId = null;
var bindingsObsolete = []; // Obsolete bindings of the displayed network, for 'remove all'

var bindingsClusters = {
    "0000": "Basic", "0001": "Batterie", "0003": "Identify", "0004": "Groupes", "0005": "Scènes",
    "0006": "On/Off", "0008": "Niveau", "0019": "OTA", "0020": "Poll control", "0102": "Volet",
    "0201": "Thermostat", "0300": "Couleur", "0400": "Luminosité", "0402": "Température",
    "0403": "Pression", "0405": "Humidité", "0406": "Présence", "0500": "Zone IAS",
    "0702": "Compteur", "0B04": "Mesure électrique", "1000": "Touchlink",
};

function bindingsAjax(data) {
    return $.ajax({
        type: "POST",
        url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
        data: data,
        dataType: "json",
        cache: false,
    });
}

// Reloads the table a few times while answers arrive
function bindingsReloadSoon() {
    var zgId = bindingsZgId;
    [4000, 12000, 25000].forEach(function (delay) {
        setTimeout(function () {
            if (bindingsZgId === zgId) displayBindingsTable(zgId);
        }, delay);
    });
}

function collectBindings(zgId, addrs) {
    if (bindingsZgId !== zgId) displayBindingsTable(zgId);
    bindingsAjax({ action: "getBindingTables", net: "Abeille" + zgId, addrs: addrs }).done(function (json) {
        var res = JSON.parse(json.result);
        if (res.status != 0) {
            $("#div_bindingsAlert").showAlert({ message: res.error, level: "danger" });
            return;
        }
        $("#div_bindingsAlert").showAlert({
            message: res.sent.length + " {{équipement(s) interrogé(s). Les réponses arrivent en quelques secondes.}}",
            level: "info",
        });
        bindingsReloadSoon();
    });
}

function removeBindings(list) {
    if (list.length === 0) return;
    var msg = list.length == 1
        ? "{{Supprimer cette liaison ?}}"
        : "{{Supprimer}} " + list.length + " {{liaisons obsolètes ?}}";
    bootbox.confirm(msg, function (result) {
        if (!result) return;
        list.forEach(function (b, i) {
            // Reread the table only after the last removal of each device
            var last = list.slice(i + 1).every(function (o) { return o.addr != b.addr; });
            var data = $.extend({ action: "removeBinding", net: "Abeille" + bindingsZgId, reread: last ? 1 : 0 }, b);
            bindingsAjax(data).done(function (json) {
                var res = JSON.parse(json.result);
                if (res.status != 0)
                    $("#div_bindingsAlert").showAlert({ message: res.error, level: "danger" });
            });
        });
        bindingsReloadSoon();
    });
}

function displayBindingsTable(zgId) {
    bindingsZgId = zgId;
    var netName = "Abeille" + zgId;
    var container = $("#idBindingsContent");

    $.when(
        $.ajax({
            type: "POST",
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: { action: "getTmpFile", file: "AbeilleBindings-" + netName + ".json" },
            dataType: "json",
            cache: false,
        }),
        getEqList(0) // All networks, to recognize the other coordinators
    ).done(function (bindResp, eqResp) {
        var eqData = JSON.parse(eqResp[0].result);
        var eqList = eqData && eqData.eqList ? eqData.eqList : {};

        var devices = {};
        var outer = JSON.parse(bindResp[0].result);
        if (outer.status === 0 && outer.content) {
            try { devices = JSON.parse(outer.content).devices || {}; } catch (e) { devices = {}; }
        }

        // IEEE => description of the destination
        var byIeee = {};
        for (var lid in eqList) {
            var eq = eqList[lid];
            if (!eq.ieee) continue;
            if (eq.addr == "0000")
                byIeee[eq.ieee] = { kind: eq.net == netName ? "coord" : "otherCoord", label: "{{Coordinateur}} " + eq.net };
            else if (!byIeee[eq.ieee] || eq.net == netName)
                byIeee[eq.ieee] = { kind: eq.net == netName ? "device" : "otherDevice", label: (eq.objectName ? eq.objectName + "-" : "") + eq.name + " (" + eq.net + ")" };
        }

        var eqs = [];
        for (var lid in eqList) {
            var eq = eqList[lid];
            if (eq.net == netName && eq.addr != "0000" && eq.enabled == 1) eqs.push(eq);
        }
        eqs.sort(function (a, b) {
            return ((a.objectName || "") + a.name).localeCompare((b.objectName || "") + b.name);
        });

        function esc(t) { return $("<span>").text(t).html(); }
        var verdicts = {
            coord:       ["success", "{{OK}}"],
            device:      ["info",    "{{Équipement}}"],
            group:       ["info",    "{{Groupe}}"],
            otherCoord:  ["danger",  "{{Obsolète : autre réseau}}"],
            otherDevice: ["danger",  "{{Obsolète : autre réseau}}"],
            unknown:     ["warning", "{{Inconnue}}"],
        };

        bindingsObsolete = [];
        var html = "<table class='table table-condensed table-bordered' style='width:auto'>";
        html += "<thead><tr><th>{{Équipement}}</th><th>{{EP}}</th><th>{{Cluster}}</th><th>{{Destination}}</th><th>{{État}}</th><th></th></tr></thead><tbody>";
        var nObsolete = 0;
        eqs.forEach(function (eq) {
            var dev = devices[eq.addr];
            var label = (eq.objectName ? eq.objectName + "-" : "") + eq.name + " (" + eq.addr + ")";
            var asleep = eq.rxOnWhenIdle === 0;
            var ask = "<a class='btn btn-default btn-xs' title='" + (asleep ? "{{Équipement endormi : réveillez-le (appui sur un bouton) juste avant}}" : "{{Interroger}}") +
                "' onclick='collectBindings(" + zgId + ", [\"" + eq.addr + "\"])'><i class='fas fa-sync'></i></a>";
            var entries = dev && dev.entries ? Object.values(dev.entries) : null;
            if (!entries) {
                html += "<tr><td>" + esc(label) + "</td><td colspan='4' style='color:#888'>" +
                    (asleep ? "{{Non interrogé (endormi)}}" : "{{Non interrogé}}") + "</td><td>" + ask + "</td></tr>";
                return;
            }
            var when = new Date(dev.collectTime * 1000).toLocaleString("fr-FR");
            if (entries.length === 0) {
                html += "<tr><td>" + esc(label) + "</td><td colspan='4' style='color:#888'>{{Aucune liaison}} &mdash; " + when + "</td><td>" + ask + "</td></tr>";
                return;
            }
            entries.forEach(function (b, i) {
                var kind, dest, params;
                if (b.group !== undefined) {
                    kind = "group";
                    dest = "{{Groupe}} " + b.group;
                    params = { addr: eq.addr, srcIeee: b.srcIeee, srcEp: b.srcEp, clustId: b.clustId, group: b.group };
                } else {
                    var known = byIeee[b.dstIeee];
                    kind = known ? known.kind : "unknown";
                    dest = (known ? known.label : b.dstIeee) + ", EP " + b.dstEp;
                    params = { addr: eq.addr, srcIeee: b.srcIeee, srcEp: b.srcEp, clustId: b.clustId, dstIeee: b.dstIeee, dstEp: b.dstEp };
                }
                var v = verdicts[kind];
                if (v[0] == "danger") {
                    nObsolete++;
                    bindingsObsolete.push(params);
                }
                var cl = bindingsClusters[b.clustId] ? b.clustId + " " + bindingsClusters[b.clustId] : b.clustId;
                html += "<tr>";
                if (i === 0)
                    html += "<td rowspan='" + entries.length + "'>" + esc(label) + "<br><small style='color:#888'>" + when + "</small> " + ask + "</td>";
                html += "<td>" + esc(b.srcEp) + "</td><td>" + esc(cl) + "</td>";
                html += "<td title='" + esc(b.dstIeee || b.group) + "'>" + esc(dest) + "</td>";
                html += "<td><span class='label label-" + v[0] + "'>" + v[1] + "</span></td>";
                html += "<td><a class='btn btn-" + (v[0] == "danger" ? "danger" : "default") + " btn-xs' title='{{Supprimer cette liaison}}' " +
                    "onclick='removeBindings([" + esc(JSON.stringify(params)) + "])'><i class='fas fa-trash'></i></a></td>";
                html += "</tr>";
            });
        });
        html += "</tbody></table>";

        var head = "<p><strong>" + netName + "</strong>";
        if (nObsolete > 0)
            head += " &mdash; <a class='btn btn-danger btn-sm' onclick='removeBindings(bindingsObsolete)'><i class='fas fa-trash'></i> {{Supprimer les}} " +
                nObsolete + " {{liaisons obsolètes}}</a>";
        head += "</p>";
        container.html(head + html);
    }).fail(function () {
        container.html("<div class='alert alert-danger'>{{Erreur lors du chargement des données.}}</div>");
    });
}

/* Refresh display if node name changed */
$("#idShowObject").on("change", function (event) {
    console.log("idShowObject click");
    displayLinksGraph(currentZigateLG); // Refresh current links graph
});

// Default display
// TODO: Should be set to first enabled gateway
displayLinksTable(1);
