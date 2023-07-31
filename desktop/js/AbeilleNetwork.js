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

$("#tab_nodes")
    .off("click")
    .on("click", function () {
        /* TODO: Tcharp38, display 1st active zigate instead of zigate 1 (might be disabled) */
        displayLinksTable(1);
    });

$("#tab_graph")
    .off("click")
    .on("click", function () {
        /* TODO: Tcharp38, display 1st active zigate instead of zigate 1 (might be disabled) */
        displayLinksGraph(1);
    });

$("#tab_summary")
    .off("click")
    .on("click", function () {});

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
    setTimeout(function () {
        trackLQICollectStatus(true, zgId);
    }, 1000);

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
            window.setTimeout(function () {
                $("#div_networkZigbeeAlert").hide();
            }, 10000);
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
                else displayLinksGraph(zgId);
                // trackLQICollectStatus(false, zgId);
            } else {
                $("#div_networkZigbeeAlert").showAlert({
                    message: data,
                    level: "danger",
                });
                window.setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000);
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
            file: "AbeilleLQI-Abeille" + zgId + ".json.lock",
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
            setTimeout(function () {
                $("#div_networkZigbeeAlert").hide();
            }, 10000);
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
                setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000);
            } else if (res.content == "") {
                $("#div_networkZigbeeAlert").showAlert({
                    message: "{{Fichier lock vide}}",
                    level: "danger",
                });
                setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000);
            } else {
                var data = res.content;
                console.log("Status='" + data + "'");
                var alertLevel = "success";
                if (data.toLowerCase().includes("oops")) {
                    alertLevel = "danger";
                    _autoUpdate = 0;
                } else if (data.toLowerCase().includes("done")) {
                    // Reminder: done/<timestamp>/<status
                    data = "Collecte terminée";
                    _autoUpdate = 0; // Stop status refresh
                }
                $("#div_networkZigbeeAlert").showAlert({
                    message: data,
                    level: alertLevel,
                });

                /* Collect status display stops when "done" found */
                // _autoUpdate = data.toLowerCase().includes("done")?0:1;
                if (_autoUpdate) {
                    // Next status update in 1s
                    setTimeout(function () {
                        trackLQICollectStatus(_autoUpdate, zgId);
                    }, 1000);
                } else {
                    // Keep last message 10sec then hide
                    setTimeout(function () {
                        $("#div_networkZigbeeAlert").hide();
                    }, 10000);
                }
            }
        },
    });
}

/* Display nodes table */
function displayLinksTable(zgId) {
    console.log("displayLinksTable(zgId=" + zgId + ")");

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

//TODO fix on click link color change, link color upon LQI quality, node name .....
function displayLinksGraph(zgId) {
    console.log("displayLinksGraph(" + zgId + ")");

    // Load JSON-encoded data from the server.
    var request = $.ajax({
        type: "POST",
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: "getTmpFile",
            file: "AbeilleLQI-Abeille" + zgId + ".json",
        },
        dataType: "json",
        cache: false,
    });

    request.done(function (json) {
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
                date.toLocaleDateString() + " " + date.toLocaleTimeString();
            $("#idCurrentDateLG").empty().append(out);

            if (lqiTable.routers.length == 0) {
                console.log("No routers");
                $("#idLinksGraphTab svg").remove(); // Remove previous one
                return;
            }

            var graph = Viva.Graph.graph();
            var showObject = document.getElementById("idShowObject").checked; // Checked to display parent object
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
                });

                for (nLogicId in router.neighbors) {
                    neighbor = router.neighbors[nLogicId];
                    console.log("neighbor=", neighbor);

                    if (showObject)
                        nodeName = neighbor.parentName + "/" + neighbor.name;
                    else nodeName = neighbor.name;

                    graph.addNode(nLogicId, {
                        name: nodeName,
                        type: neighbor["type"],
                        icon: neighbor["icon"],
                    });

                    console.log(
                        "Adding link from " + rLogicId + " to " + nLogicId
                    );
                    graph.addLink(rLogicId, nLogicId);
                }
            }

            console.log("Render the graph.");
            var graphics = Viva.Graph.View.svgGraphics();
            var nodeSize = 50; // Node 50x50
            var imgSize = 40; // Image 40x40 centered in node
            graphics
                .node(function (node) {
                    console.log("node=", node);

                    var nodeColor = "#E5E500";
                    var nodeName = "?";
                    var iconName = "defaultUnknown";

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
                            "/plugins/Abeille/images/node_" + iconName + ".png"
                        );

                    var ui = Viva.Graph.svg("g");
                    ui.append(svgText);
                    ui.append(img1);
                    ui.append(img2);

                    return ui;
                })
                .placeNode(function (nodeUI, pos) {
                    nodeUI.attr(
                        "transform",
                        "translate(" +
                            (pos.x - nodeSize / 3) +
                            "," +
                            (pos.y - nodeSize / 2.5) +
                            ")"
                    );
                });

            var idealLength = 150;
            var layout = Viva.Graph.Layout.forceDirected(graph, {
                springLength: idealLength,
                springCoeff: 0.0005,
                stableThreshold: 0.1,
                dragCoeff: 0.02,
                gravity: -0.5,
            });

            // $("#idLinksGraphTab svg").remove(); // Remove previous one
            $("#idLinksGraphTabSVG svg").remove(); // Remove previous one

            var renderer = Viva.Graph.View.renderer(graph, {
                layout: layout,
                graphics: graphics,
                prerender: 10,
                // container: document.getElementById("idLinksGraphTab"),
                container: document.getElementById("idLinksGraphTabSVG"),
            });
            renderer.run();
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

    request.fail(function () {
        var msg = "Données du réseau non trouvées. Forcez la réinterrogation.";
        $("#div_networkZigbeeAlert").showAlert({
            message: msg,
            level: "danger",
        });
        $("#graph_network svg").remove();
    });

    request.always(function (data) {
        window.setTimeout(function () {
            $("#div_networkZigbeeAlert").hide();
        }, 10000);
    });
}

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

/* Refresh display if node name changed */
$("#idShowObject").on("change", function (event) {
    console.log("idShowObject click");
    displayLinksGraph(currentZigateLG); // Refresh current links graph
});
