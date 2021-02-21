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

$("#tab_nodes").off("click").on("click", function () {
    /* TODO: Tcharp38, display 1st active zigate instead of zigate 1 (might be disabled) */
    displayLinksTable(1);
});

$("#tab_graph").off("click").on("click", function () {
    /* TODO: Tcharp38, display 1st active zigate instead of zigate 1 (might be disabled) */
    displayLinksGraph(1);
});

$("#tab_summary").off("click").on("click", function () {
});

$("#nodeFrom").off().change(function () {
    var value = $(this).val();
    filterColumnOnValue(value, 0);
});

$("#nodeTo").off().change(function () {
    var value = $(this).val();
    filterColumnOnValue(value, 2);
});

/* Launch AbeilleLQI.php to collect network informations.
   Progress is displayed in 'AlertDiv' */
function refreshLQICache(zigateX) {
    console.log("refreshLQICache(zgNb="+zigateX+")");

    /* Collect status displayed every 1sec */
    setTimeout(function () { trackLQICollectStatus(true, zigateX); }, 1000);

    $.ajax({
            url: "/plugins/Abeille/core/php/AbeilleLQI.php?zigate="+zigateX,
            async: true,
            error: function (jqXHR, status, error) {
                //console.log("refreshLQICache error status: " + status);
                //console.log("refreshLQICache error msg: " + error);
                $('#idLinksTable tbody').empty()
                $('#div_networkZigbeeAlert').showAlert({
                    message: 'ERREUR ! Impossible de démarrer la collecte.',
                    level: 'danger'
                });
                window.setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
            },
            success: function (data, status, jqhr) {
                //console.log("refreshLQICache success status: " + status);
                //console.log("refreshLQICache success msg: " + data);
                // php file checks for write rights
                console.log("AbeilleLQI.php output="+data);
                if (data.indexOf("successfully") >= 0) {
                    levelAlert = "info";
                    $('#idLinksTable').trigger("update");
                    displayLinksTable(zigateX);
                    // trackLQICollectStatus(false, zigateX);
                } else {
                    $('#div_networkZigbeeAlert').showAlert({message: data, level: "danger"});
                    window.setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
                }
            }
        }
    );
}

function refreshBruit(Device) {
    console.log("refreshBruit start");
    $.ajax({ url: "/plugins/Abeille/Network/refreshBruit.php?device="+Device });
    console.log("refreshBruit end");
}

function refreshRoutes(Device) {
    console.log("refreshRoutes start");
    $.ajax({ url: "/plugins/Abeille/Network/refreshRoutes.php?device="+Device });
    console.log("refreshRoutes end");
}

function getAbeilleLog(_autoUpdate, _log) {
    $.ajax({
        type: 'POST',
        url: 'core/ajax/log.ajax.php',
        data: {
            action: 'get',
            log: _log,
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            setTimeout(function () {
                getAbeilleLog(_autoUpdate, _log)
            }, 1000);
        },
        success: function (data) {
            if (data.state != 'ok') {
                setTimeout(function () {
                    getAbeilleLog(_autoUpdate, _log)
                }, 1000);
                return;
            }
            if ($.isArray(data.result)) {
                var aLog = data.result;
                //console.log(aLog.length);
                //console.log(aLog);
                log = aLog.filter(function (val) {
                    return val.toLowerCase().indexOf("lqi") > -1;
                });
                //console.log('last log LQI: ' + log.reverse()[0]);
                //log = data.result[data.result.length-1];
                $('#div_networkZigbeeAlert').showAlert({
                    message: log.reverse()[0],
                    level: 'success'
                });
            }
            if (init(_autoUpdate, 0) == 1) {
                setTimeout(function () {
                    getAbeilleLog(_autoUpdate, _log)
                }, 1000);
            }
        }
    });
}

/* Read & display lock file content until "done" found */
function trackLQICollectStatus(_autoUpdate, zigateX) {
    console.log("trackLQICollectStatus(zgNb="+zigateX+")");

    $.ajax({
        type: 'POST',
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: 'getTmpFile',
            file : "AbeilleLQI_MapDataAbeille"+zigateX+".json.lock",
        },
        dataType: "json",
        global: false,
        cache: false,
        error: function (request, status, error) {
            console.log("trackLQICollectStatus: error status=" + status);
            console.log("trackLQICollectStatus: error msg=" + error);
            $('#div_networkZigbeeAlert').showAlert({
                message: "ERREUR ! Problème du lecture du fichier de lock.",
                level: 'danger'
            });
            _autoUpdate = 0;
            setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
        },
        success: function (json_res) {
            res = JSON.parse(json_res.result); // res.status, res.error, res.content
            console.log("res="+JSON.stringify(res));
            if (res.status != 0) {
                var msg = "ERREUR ! "+res.error;
                $('#div_networkZigbeeAlert').showAlert({ message: msg, level: 'danger' });
                _autoUpdate = 0;
                setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
            } else if (res.content == "") {
                $('#div_networkZigbeeAlert').showAlert({message: '{{Fichier lock vide}}', level: 'danger'});
                setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
            } else {
                var data = res.content;
                console.log("Status='"+data+"'");
                var alertLevel = 'success';
                if (data.toLowerCase().includes("oops")) {
                    alertLevel = 'danger';
                    _autoUpdate = 0;
                } else if (data.toLowerCase().includes("done")) {
                    // Reminder: done/<timestamp>/<status
                    data = "Collecte terminée";
                    _autoUpdate = 0; // Stop status refresh
                }
                $('#div_networkZigbeeAlert').showAlert({
                    message: data,
                    level: alertLevel
                });

                /* Collect status display stops when "done" found */
                // _autoUpdate = data.toLowerCase().includes("done")?0:1;
                if (_autoUpdate) { // Next status update in 1s
                    setTimeout(function () { trackLQICollectStatus(_autoUpdate, zigateX); }, 1000);
                } else { // Keep last message 10sec then hide
                    setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
                }
            }
        }
    });
}

//TODO fix on click link color change, link color upon LQI quality, node name .....
function displayLinksGraph(zigateX) {
    console.log("displayLinksGraph("+zigateX+")");

    // Step 1. We create a graph object.
    var graph = Viva.Graph.graph();

    // Load JSON-encoded data from the server.
    var request = $.ajax({
        type: 'POST',
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: 'getTmpFile',
            file : "AbeilleLQI_MapDataAbeille"+zigateX+".json",
        },
        dataType: "json",
        cache: false
    });

    request.done(function (json) {
        res = JSON.parse(json.result);
        if (res.status != 0) {
            // -1 = Most probably file not found
            $('#div_networkZigbeeAlert').showAlert({message: '{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}', level: 'danger'});
        } else if (res.content == "") {
            $('#div_networkZigbeeAlert').showAlert({message: '{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}', level: 'danger'});
        } else {

            // On parcours le json ligne à ligne
            // On regarde la source et l'ajoute a la table nodes si pas deja existante
            // on regarde la voisine et l ajoute à la table nodes si pas deja existante
            // On prend la voisine et on la met dans la liste link des voisines: astuce: aTemp et push.

            console.log('Visiblement j ai un json a utiliser');
            var json = JSON.parse(res.content);
            json.data.sort(function (a, b) {
                if (a.Voisin_Name == b.Voisin_Name) {
                    return 0;
                }
                ;
                if (a.Voisin_Name > b.Voisin_Name) {
                    return 1;
                }
                ;
                if (a.Voisin_Name < b.Voisin_Name) {
                    return -1;
                }
            });

            var nodes = [], currentJsonNode, aTemp;

            for (var nodeFromJson in json.data) {
                currentJsonNode = json.data[nodeFromJson];
                console.log('current node: '+JSON.stringify(currentJsonNode));
                //console.log('Parsing: ' + currentJsonNode.NE_Name + '/' + currentJsonNode.Voisine_Name + ' * ' + currentJsonNode.Type); // this will show the info it in firebug console
                // Step 2. We add nodes and edges to the graph:
                //Add node if not already existing

                //Handle ZigBee name error
                if (currentJsonNode.Voisine_Name == null) {
                    currentJsonNode.Voisine_Name = currentJsonNode.IEEE_Address;
                    console.log('current node - voisine name empty - so I give IEEE as a name');
                }

                if (currentJsonNode.NE == null) {
                    currentJsonNode.NE = currentJsonNode.IEEE_Address;
                    console.log('current node - NE name empty - so I give IEEE as a name');
                }

                //Populate only undefined nodes as nodes a repeated if routers exist
                if ( typeof(nodes[currentJsonNode.NE]) == 'undefined' ) {
                    nodes[currentJsonNode.NE]       = {};
                    nodes[currentJsonNode.NE].NE    = currentJsonNode.NE;
                    nodes[currentJsonNode.NE].name  = currentJsonNode.NE_Name;
                    nodes[currentJsonNode.NE].object= currentJsonNode.NE_Objet;
                    nodes[currentJsonNode.NE].links = [];
                    // nodes[currentJsonNode.NE].route = currentJsonNode.NeighbourTableEntries;
                    console.log('current node - It s a new node so I create it in the table from source : '+JSON.stringify(nodes[currentJsonNode.NE]));
                    // console.log('nodes  tables is : '+JSON.stringify(nodes)); // String-keyed array elements are not enumerable and make no sense in JSON
                                                                                // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify
                }

                if ( typeof(nodes[currentJsonNode.Voisine]) == 'undefined' ) {
                    nodes[currentJsonNode.Voisine]          = {};
                    nodes[currentJsonNode.Voisine].NE       = currentJsonNode.Voisine;
                    nodes[currentJsonNode.Voisine].name     = currentJsonNode.Voisine_Name;
                    nodes[currentJsonNode.Voisine].object   = currentJsonNode.Voisine_Objet;
                    nodes[currentJsonNode.Voisine].links    = [];
                    nodes[currentJsonNode.Voisine].route    = 1;
                    nodes[currentJsonNode.Voisine].lqi      = currentJsonNode.LinkQualityDec;
                    nodes[currentJsonNode.Voisine].Type     = currentJsonNode.Type;
                    console.log('current node - It s a new node so I create it in the table from voisine : '+JSON.stringify(nodes[currentJsonNode.Voisine]));
                }

                //Add Voisine as link to NE
                aTemp = nodes[currentJsonNode.NE].links;
                aTemp.push(currentJsonNode.Voisine);
                console.log('Nouvelles voisines : '+JSON.stringify(aTemp));
            }

            // maintenant que l on a toutes les informations dans nodes on va créer le graph.
            // https://github.com/anvaka/VivaGraphJS

            // On defini le type par defaut de la zigate
            // nodes['Ruche'].Type = ('undefined' == typeof(nodes['Ruche'].Type) ? 'Coordinator' : nodes['Ruche'].Type);
            console.log('node ruche ('+zigateX+'): '+JSON.stringify(nodes['Abeille'+zigateX+'/Ruche']));
            nodes['Abeille'+zigateX+'/Ruche'].Type = 'Coordinator';

            var showObject = document.getElementById("idShowObject").checked; // Checked to display parent object
            for (node in nodes) {

                console.log('Adding node: '+node+' name: ' + nodes[node].name + ' route: ' + nodes[node].route + ', Quality: ' + nodes[node].lqi + ', Type: ' + nodes[node].Type);
                // graph.addNode( node, { name: nodes[node].name, route: nodes[node].route, Quality: nodes[node].lqi, Type: nodes[node].Type } );
                // graph.addNode( node, nodes[node].name ); // graph but all NE are yellow
                if (showObject)
                    var nodeName = nodes[node].object+"/"+nodes[node].name;
                else
                    var nodeName = nodes[node].name;
                graph.addNode( node, { name: nodeName, Type: nodes[node].Type } ); // nodeId, { node.data }

                for (link in nodes[node].links) {
                    if (nodes[node].name != null && nodes[node].links[link] != null) {
                        console.log('adding link: ' + nodes[node].name + ' <-> ' + nodes[node].links[link]);
                        graph.addLink(node, nodes[node].links[link]);
                    } else {
                        console.log('not adding link:' + nodes[node].name + ' <-> ' + nodes[node].links[link]);
                    }
                }

                // Step 3. Render the graph.
                console.log('Render the graph.');
                var graphics = Viva.Graph.View.svgGraphics();
                var nodeSize = 10;

                // Je ne sais pas ce que cela fait mais si pas present alors erreur dans log console Safari.
                 /*
                var highlightRelatedNodes = function (nodeId, isOn) {
                                                graph.forEachLinkedNode(nodeId, function (node, link) {
                                                                                    var linkUI = graphics.getLinkUI(link.id);
                                                                                    if (linkUI) {
                                                                                        linkUI.attr('stroke', isOn ? '#FF0000' : '#B7B7B7');
                                                                                    }
                                                                                                    });
                                                                    };
                  */

                graphics.node(function (node) {
                    var nodeSize = 10;

                    var nodecolor = '#E5E500';
                    if (typeof node.data != 'undefined') {
                        if (node.data.Type == 'Coordinator')    { nodecolor = '#a65ba6'; }
                        if (node.data.Type == 'End Device')     { nodecolor = '#7BCC7B'; }
                        if (node.data.Type == 'Router')         { nodecolor = '#00a2e8'; }
                    }

                    var ui = Viva.Graph.svg('g');
                    var img = Viva.Graph.svg('rect').attr("width", nodeSize).attr("height", nodeSize).attr("fill", nodecolor);

                    var svgText = Viva.Graph.svg('text').attr('y', '0px').text('?');
                    if (typeof node.data != 'undefined') {
                        if (typeof node.data.Type != 'undefined') {
                            console.log('Txt: '+JSON.stringify(node.data.Type));
                            svgText = Viva.Graph.svg('text').attr('y', '0px').text(node.data.name);
                        }
                    }

                    ui.append(svgText);
                    ui.append(img);

                              /*
                    $(ui).hover(function (node) {
                        var nodeText = 'name: ';
                        + node.data.name + ', route: ' + node.data.route +
                                                   ', Quality: ' + node.data.lqi + ', Type: ' + node.data.Type;

                        $('#nodeName').html(nodeText);

                        highlightRelatedNodes(node.id, true);
                    }, function () {
                        highlightRelatedNodes(node.id, false);
                    });
                 */

                    return ui;
                }).placeNode(function (nodeUI, pos) {
                    nodeUI.attr('transform',
                        'translate(' +
                        (pos.x - nodeSize / 3) + ',' + (pos.y - nodeSize / 2.5) +
                        ')');
                });

                var idealLength = 100;
                var layout = Viva.Graph.Layout.forceDirected(graph, { springLength: idealLength, springCoeff: 0.0005, stableThreshold: 0.1, dragCoeff: 0.02, gravity: -0.5 });

                //remove previous one
                $('#idLinksGraphTab svg').remove();

                var renderer = Viva.Graph.View.renderer(graph, { layout: layout, graphics: graphics, prerender: 10, container: document.getElementById('idLinksGraphTab') });
                renderer.run();
                /*setTimeout(function () {
                    renderer.pause();
                    renderer.reset();
                }, 200);
                */

                /* Get network collect time */
                $.ajax({
                    type: 'POST',
                    url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
                    data: {
                        action: 'getTmpFileModificationTime',
                        file: "AbeilleLQI_MapDataAbeille"+zigateX+".json"
                    },
                    dataType: 'json',
                    global: false,
                    cache: false,
                    error: function (request, status, error) {
                        bootbox.alert("ERREUR 'getTmpFileModificationTime' !<br>"+"status="+status+"<br>error="+error);
                    },
                    success: function (json_res) {
                        console.log(json_res);
                        res = JSON.parse(json_res.result);
                        if (res.status != 0) {
                            var msg = "ERREUR ! Quelque chose s'est mal passé.\n"+res.error;
                            alert(msg);
                        } else {
                            // window.location.reload();
                            $('#idCurrentNetworkLG').empty().append("Abeille"+zigateX);
                            console.log("getTmpFileModificationTime() => "+res.mtime);
                            const date = new Date(res.mtime * 1000);
                            var out = date.toLocaleDateString()+' '+date.toLocaleTimeString();
                            $('#idCurrentDateLG').empty().append(out);
                            currentZigateLG = zigateX;
                        }
                    }
                });
            }
        }
    });

    request.fail(function () {
        var msg = 'Données du réseau non trouvées. Forcez la réinterrogation.'
        $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
        $('#graph_network svg').remove();
    });

    request.always(function (data) {
        window.setTimeout(function () {
            $('#div_networkZigbeeAlert').hide()
        }, 10000);
    });
};

/* Display nodes table */
function displayLinksTable(zigateX) {
    console.log("displayLinksTable(zgNb="+zigateX+")");

    var jqXHR = $.ajax({
        type: 'POST',
        url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
        data: {
            action: 'getTmpFile',
            file : "AbeilleLQI_MapDataAbeille"+zigateX+".json",
        },
        dataType: "json",
        cache: false
    });

    jqXHR.done(function (json, textStatus, jqXHR) {
        res = JSON.parse(json.result);
        if (res.status != 0) {
            // -1 = Most probably file not found
            $('#div_networkZigbeeAlert').showAlert({message: '{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}', level: 'danger'});
        } else if (res.content == "") {
            $('#div_networkZigbeeAlert').showAlert({message: '{{Aucune donnée. Veuillez forcer la réinterrogation du réseau.}}', level: 'danger'});
        } else {
            var json = JSON.parse(res.content);
            var nodes = json.data;
            nodes.sort(function (a, b) {
                if (a.Voisin_Name == b.Voisin_Name) {
                    return 0;
                }
                ;
                if (a.Voisin_Name > b.Voisin_Name) {
                    return 1;
                }
                ;
                if (a.Voisin_Name < b.Voisin_Name) {
                    return -1;
                }
            });
            var tbody = "";
            var nodesTo = new Object(), nodesFrom = new Object();
            var nodeJId, nodeJName;
            //console.log(nodes);

            for (var nodeFromJson in nodes) {
                //Handle ZigBee name error
                if (nodes[nodeFromJson].Voisine_Name == null) {
                    nodes[nodeFromJson].Voisine_Name = nodes[nodeFromJson].IEEE_Address;
                }

                if (nodes[nodeFromJson].NE == null) {
                    nodes[nodeFromJson].NE = nodes[nodeFromJson].IEEE_Address;
                }

                //Populate selectBox options
                nodesTo[nodes[nodeFromJson].Voisine] = nodes[nodeFromJson].Voisine_Name;
                nodesFrom[nodes[nodeFromJson].NE] = nodes[nodeFromJson].NE_Name;

                //New Row
                // tbody += (nodes[nodeFromJson].LinkQualityDec > 100) ? '<tr>' : '<tr class="active">';
                tbody += '<tr>';
                //process NE to jeedom id
                nodeJName = nodes[nodeFromJson].NE;
                //zigbee LQI result is not null
                nodeJId = nodesFromJeedom["Abeillex" + (nodeJName == 0 ? 'Ruche' : nodeJName)];
                //if no match to jeedom db
                if (nodeJId == 'undefined' || nodeJId == null) {
                    nodeJId = 'not found in jeedom DB';
                }

                //console.log('nodeJName NE 2 (@ zigbee): ' + nodeJName + " , nodeJId: "+ nodeJId);
                tbody += '<td id="neId">';
                tbody += '<span  class="label label-primary" style="font-size : 1em;" data-nodeid="' + nodeJName + '">' + nodeJName + '</span> ';
                tbody += '</td>';

                tbody += '<td id="neName">';
                tbody += '<div style="opacity:0.5"><i>' + nodes[nodeFromJson].NE_Objet + '</i></div>';
                tbody += '</td>';

                tbody += '<td id="neObjet">';
                tbody += '<div style="opacity:0.5"><i>' + nodes[nodeFromJson].NE_Name + '</i></div>';
                tbody += '</td>';

                //Process Voisine to jeedom Id
                nodeJName = nodes[nodeFromJson].Voisine;
                //zigbee LQI result is not null
                nodeJId = nodesFromJeedom["Abeillex" + (nodeJName == 0 ? 'Ruche' : nodeJName)];
                //if no match to jeedom db
                if (nodeJId == 'undefined' || nodeJId == null) {
                    nodeJId = 'not found in jeedom DB';
                }
                //console.log('nodeJName Voisine 2 (@ zigbee): ' + nodeJName + " , nodeJId: "+ nodeJId);

                tbody += '<td id="vid">';
                tbody += '<span class="label label-primary" style="font-size : 1em;" data-nodeid="' + nodes[nodeFromJson].Voisine + '">' + nodes[nodeFromJson].Voisine + '</span>';
                tbody += '</td>';

                tbody += '<td id="vObjet">';
                tbody += nodes[nodeFromJson].Voisine_Objet;
                tbody += '</td>';

                tbody += '<td id="vname">';
                tbody += nodes[nodeFromJson].Voisine_Name;
                tbody += '</td>';

                tbody += '<td>';
                tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[nodeFromJson].Relationship + '</span>';
                tbody += '</td>';

                tbody += '<td>';
                tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[nodeFromJson].Depth + '</span>';
                tbody += '</td>';

                tbody += '<td id="lqi">';
                // tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[nodeFromJson].LinkQualityDec + '  ';
                tbody += (nodes[nodeFromJson].LinkQualityDec <=  50) ? '<span class="label label-danger"  style="font-size : 1em;" >' : '';
                tbody += ((nodes[nodeFromJson].LinkQualityDec > 50)&&(nodes[nodeFromJson].LinkQualityDec <= 100)) ? '<span class="label label-warning" style="font-size : 1em;">'  : '';
                tbody += (nodes[nodeFromJson].LinkQualityDec >  100) ? '<span class="label label-success" style="font-size : 1em;">'  : '';
                tbody += nodes[nodeFromJson].LinkQualityDec +'</span></td>';

                tbody += '<td>';
                tbody += '<span class="label label-success" style="font-size : 1em;" >' + nodes[nodeFromJson].Type + '</span>';
                tbody += '</td>';
                tbody += '<td></tr>';
            }

            //construct table , append value to select button
            $('#idLinksTable tbody').empty().append(tbody);
            var nodeFrom = $('#nodeFrom').empty(),
                nodeTo = $('#nodeTo').empty();

            nodeFrom.append('<option value="All">{{Tous}}</option>');
            nodeFrom.append('<option value="None">{{Aucun}}</option>');

            nodeTo.append('<option value="All">{{Tous}}</option>');
            nodeTo.append('<option value="None">{{Aucun}}</option>');

            $.each(nodesFrom, function (idx, item) {
                nodeFrom.append(new Option(item, idx));
            });
            $.each(nodesTo, function (idx, item) {
                nodeTo.append(new Option(item, idx));
            });

            $("#idLinksTable>tbody>tr>td:nth-child(1)").off("click").on("click", function () {
                var eqTypeId = $(this).children(1).attr('data-nodeid');
                //console.log("eqType: " + eqTypeId);
                if (eqTypeId.indexOf('not found') >= 0) {

                    $('#div_networkZigbeeAlert').showAlert({
                        message: '{{Pas de correspondance trouvée entre le noeud zigbee et jeedom. Ce noeud n\'existe pas dans jeedom et/ou l\'analyse de réseau n\'est pas actualisée}}',
                        level: 'info'
                    });
                    setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 5000);
                } else {
                    window.location.href = document.location.origin = '/index.php?v=d&p=Abeille&m=Abeille&id=' + eqTypeId;
                }
            });

            $("#idLinksTable>tbody>tr>td:nth-child(3)").off("click").on("click", function () {
                var eqTypeId = $(this).children(1).attr('data-nodeid');
                //console.log("eqType: " + eqTypeId);
                if (eqTypeId.indexOf('not found') >= 0) {
                    $('#div_networkZigbeeAlert').showAlert({
                        message: '{{Pas de correspondance trouvée entre le noeud zigbee et jeedom. Ce noeud n\'existe pas dans jeedom et/ou l\'analyse de réseau n\'est pas actualisée}}',
                        level: 'info'
                    });
                    setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 5000);
                } else {
                    window.location.href = document.location.origin + '/index.php?v=d&p=Abeille&m=Abeille&id=' + eqTypeId;
                }
            });
            $("#idLinksTable").tablesorter({
                sortList: [[0, 0], [1, 0]]
            });
            $('#idLinksTable').trigger('update');
            var nodes = json.data;

            /* Get network collect time */
            $.ajax({
                type: 'POST',
                url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
                data: {
                    action: 'getTmpFileModificationTime',
                    file: "AbeilleLQI_MapDataAbeille"+zigateX+".json"
                },
                dataType: 'json',
                global: false,
                cache: false,
                error: function (request, status, error) {
                    bootbox.alert("ERREUR 'getTmpFileModificationTime' !<br>"+"status="+status+"<br>error="+error);
                },
                success: function (json_res) {
                    console.log(json_res);
                    res = JSON.parse(json_res.result);
                    if (res.status != 0) {
                        var msg = "ERREUR ! Quelque chose s'est mal passé.\n"+res.error;
                        alert(msg);
                    } else {
                        // window.location.reload();
                        $('#idCurrentNetworkLT').empty().append("Abeille"+zigateX);
                        console.log("getTmpFileModificationTime() => "+res.mtime);
                        const date = new Date(res.mtime * 1000);
                        var out = date.toLocaleDateString()+' '+date.toLocaleTimeString();
                        $('#idCurrentDateLT').empty().append(out);
                    }
                }
            });
        }
    });

    jqXHR.fail(function (json, textStatus, jqXHR) {
        //console.log("network.js: displayLinksTable: fail: " + textStatus);
        var msg = 'Données du réseau non trouvées. Forcez la réinterrogation.';
        $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
        $('#idLinksTable tbody').empty()
    });

    jqXHR.always(function (json, textStatus, jqXHR) {
        setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
    })
}

function filterColumnOnValue(data, col) {
    var filterValue = data;
    var filterColumn = col;
    //console.log('filtering col ' + filterColumn + ' on value ' + filterValue);
    $('#idLinksTable > tbody > tr').each(function (idx, val) {
        //console.log(val);
        switch (filterValue) {
            case 'None':
                val.style.display = 'none';
                break;
            case 'All':
                val.style.display = '';
                break;
            default:
                if (val.children[filterColumn].innerHTML.includes(filterValue)) {
                    //console.log(val.innerHTML);
                    val.style.display = '';
                } else {
                    val.style.display = 'none';
                }
                break;
        }
    })
}

/* Refresh display if node name changed */
$("#idShowObject").on('change', function(event) {
    console.log("idShowObject click");
    displayLinksGraph(currentZigateLG); // Refresh current links graph
});
