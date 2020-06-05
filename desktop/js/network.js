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

$("#tab_route").off("click").on("click", function () {
    network_links(1);
});

$("#tab_graph").off("click").on("click", function () {
    network_display(1);
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

function updateZigBeeJsonCache(zigateX) {
    // Lance le script de recuperation des LQI aupres des Abeilles routeurs.
    // Show progress in AlertDiv
    setTimeout(function () {
        updateAlertFromZigBeeJsonLog(true, zigateX);
    }, 2000);
    $.ajax({
            url: "/plugins/Abeille/Network/AbeilleLQI.php?zigate="+zigateX,
            async: true,
            error: function (jqXHR, status, error) {
                //console.log("updateZigBeeJsonCache error status: " + status);
                //console.log("updateZigBeeJsonCache error msg: " + error);
                $('#table_routingTable tbody').empty()
                $('#div_networkZigbeeAlert').showAlert({
                    message: 'Error, while processing zigbee network information, please see logs',
                    level: 'danger'
                });
                window.setTimeout(function () {
                    $('#div_networkZigbeeAlert').hide()
                }, 10000);

            },
            success: function (data, status, jqhr) {
                //console.log("updateZigBeeJsonCache success status: " + status);
                //console.log("updateZigBeeJsonCache success msg: " + data);
                // php file checks for write rights

                var levelAlert = "danger";
                var timeAlert = 5000;
                var messageAlert = data;
                if (data.indexOf("successfully") >= 0) {
                    levelAlert = "info";
                    $('#table_routingTable').trigger("update");
                    network_links(zigateX);
                    updateAlertFromZigBeeJsonLog(false, zigateX);
                }
                $('#div_networkZigbeeAlert').showAlert({message: messageAlert, level: levelAlert});
                window.setTimeout(function () {
                    $('#div_networkZigbeeAlert').hide()
                }, 3000);
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

function updateAlertFromZigBeeJsonLog(_autoUpdate, zigateX) {
    $.ajax({
        type: 'GET',
        url: '/plugins/Abeille/Network/tmp/AbeilleLQI_MapDataAbeille'+zigateX+'.json.lock',
        dataType: 'html',
        global: false,
        cache: false,
        error: function (request, status, error) {
            console.log("updateAlertFromZigBeeJsonLog url: " + url);
            console.log("updateAlertFromZigBeeJsonLog error status: " + status);
            console.log("updateAlertFromZigBeeJsonLog error msg: " + error);
            $('#div_networkZigbeeAlert').showAlert({
                message: "Error, cannot read status file, please refresh cache.",
                level: 'danger'
            });
            _autoUpdate = 0;
            setTimeout(function () {
                $('#div_networkZigbeeAlert').hide();
            }, 5000);
        },
        success: function (data) {
            //console.log("updateAlertFromZigBeeJsonLog success data: " + data);
            //console.log("updateAlertFromZigBeeJsonLog success data includes oops: " + data.toLowerCase().includes("oops"));
            //error in treatment
            if (data.toLowerCase().includes("oops")) {
                $('#div_networkZigbeeAlert').showAlert({
                    message: "Error, cannot read status file, please refresh cache.",
                    level: 'danger'});
                _autoUpdate = 0;
                setTimeout(function () {
                    $('#div_networkZigbeeAlert').hide();
                }, 3000);
            }
            else {
                $('#div_networkZigbeeAlert').showAlert({
                    message: data ,
                    level: 'success'
                });
                //if LQI log contains done, then loop display of log is disabled
                _autoUpdate = data.toLowerCase().includes("done")?0:1;
                if (_autoUpdate) {
                    setTimeout(function () {
                        updateAlertFromZigBeeJsonLog(_autoUpdate, zigateX);
                    }, 1000);
                }
                // when LQI request is done, alertDiv is hidden.
                else {
                    setTimeout(function () {
                        $('#div_networkZigbeeAlert').hide();
                    }, 5000);
                }
            }
        }
    })
    ;
}



//TODO fix on click link color change, link color upon LQI quality, node name .....
function network_display(zigateX) {
    // Step 1. We create a graph object.
    var graph = Viva.Graph.graph();

    // Load JSON-encoded data from the server using a GET HTTP request.
    var request = $.ajax({
        url: "/plugins/Abeille/Network/tmp/AbeilleLQI_MapDataAbeille"+zigateX+".json",
        dataType: "json",
        cache: false
    });

    request.done(function (json) {
        //Sort objects to have list array sorted on Voisin values
        // empty array ?
        //console.log(json);
        if (typeof json == 'undefined' || json.length < 1 || json.data.length < 1) {
            //console.log('Fichier vide, rien a traiter.');
            $('#div_networkZigbeeAlert').showAlert({message: '{{Fichier vide, rien a traiter}}', level: 'danger'});
        }
        else {
                 
            // On parcours le json ligne à ligne
            // On regarde la source et l'ajoute a la table nodes si pas deja existante
            // on regarde la voisine et l ajoute à la table nodes si pas deja existante
            // On prend la voisine et on la met dans la liste link des voisines: astuce: aTemp et push.
                 
            console.log('Visiblement j ai un json a utiliser');
            //process the json array
            $('#div_networkZigbeeAlert').showAlert({message: '{{Action réalisée avec succès}}', level: 'success'})
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


            for (node in nodes) {

                console.log('Adding node: '+node+' name: ' + nodes[node].name + ' route: ' + nodes[node].route + ', Quality: ' + nodes[node].lqi + ', Type: ' + nodes[node].Type);
                // graph.addNode( node, { name: nodes[node].name, route: nodes[node].route, Quality: nodes[node].lqi, Type: nodes[node].Type } );
                // graph.addNode( node, nodes[node].name ); // graph but all NE are yellow
                 graph.addNode( node, { name: nodes[node].name, Type: nodes[node].Type } ); // nodeId, { node.data }
                 
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
                  

                //
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
                var layout = Viva.Graph.Layout.forceDirected(graph, { springLength: idealLength, springCoeff: 0.0008, stableThreshold: 0.9, dragCoeff: 0.009, gravity: -1.2, thetaCoeff: 0.8 });

                //remove previous one
                $('#graph_network svg').remove();

                var renderer = Viva.Graph.View.renderer(graph, { layout: layout, graphics: graphics, prerender: 10, container: document.getElementById('graph_network') });
                renderer.run();
                /*setTimeout(function () {
                    renderer.pause();
                    renderer.reset();
                }, 200);
                */
            }

        }
    });

    request.fail(function () {
        var msg = 'Données du réseau non trouvées, faites un cache-refresh sur la page Network List'
        $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
        $('#graph_network svg').remove();

    });

    request.always(function (data) {
        window.setTimeout(function () {
            $('#div_networkZigbeeAlert').hide()
        }, 10000);
    });
};

function network_links(zigateX) {
    // Generate la table qui est affichée dans le modale tab "Table des noeuds".
    var jqXHR = $.ajax({
        url: "/plugins/Abeille/Network/tmp/AbeilleLQI_MapDataAbeille"+zigateX+".json",
        dataType: "json",
        cache: false
    });

    jqXHR.done(function (json, textStatus, jqXHR) {
        // empty array ?
        if (typeof json == 'undefined' || json.length < 1 || json.data.length < 1 || json.data.includes('OOPS')) {
            //console.log('Fichier vide, rien a traiter.');
            $('#div_networkZigbeeAlert').showAlert({message: '{{Fichier vide, rien a traiter}}', level: 'danger'});
        }
        else {
            $('#div_networkZigbeeAlert').showAlert({
                message: '{{Données récupérées avec succès}}',
                level: 'success'
            });
            setTimeout(function () {
                $('#div_networkZigbeeAlert').hide()
            }, 2000);
            //Sort objects to have Voisin list array
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
            $('#table_routingTable tbody').empty().append(tbody);
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

            $("#table_routingTable>tbody>tr>td:nth-child(1)").off("click").on("click", function () {
                var eqTypeId = $(this).children(1).attr('data-nodeid');
                //console.log("eqType: " + eqTypeId);
                if (eqTypeId.indexOf('not found') >= 0) {

                    $('#div_networkZigbeeAlert').showAlert({
                        message: '{{Pas de correspondance trouvée entre le noeud zigbee et jeedom. Ce noeud n\'existe pas dans jeedom et/ou l\'analyse de réseau n\'est pas actualisée}}',
                        level: 'info'
                    });
                    setTimeout(function () {
                        $('#div_networkZigbeeAlert').hide()
                    }, 4000);
                } else {
                    window.location.href = document.location.origin = '/index.php?v=d&p=Abeille&m=Abeille&id=' + eqTypeId;
                }
            });

            $("#table_routingTable>tbody>tr>td:nth-child(3)").off("click").on("click", function () {
                var eqTypeId = $(this).children(1).attr('data-nodeid');
                //console.log("eqType: " + eqTypeId);
                if (eqTypeId.indexOf('not found') >= 0) {
                    $('#div_networkZigbeeAlert').showAlert({
                        message: '{{Pas de correspondance trouvée entre le noeud zigbee et jeedom. Ce noeud n\'existe pas dans jeedom et/ou l\'analyse de réseau n\'est pas actualisée}}',
                        level: 'info'
                    });
                    setTimeout(function () {
                        $('#div_networkZigbeeAlert').hide()
                    }, 4000);
                } else {
                    window.location.href = document.location.origin + '/index.php?v=d&p=Abeille&m=Abeille&id=' + eqTypeId;
                }
            });
            $("#table_routingTable").tablesorter({
                sortList: [[0, 0], [1, 0]]
            });
            $('#table_routingTable').trigger('update');
            var nodes = json.data;
        }
    });


    jqXHR.fail(function (json, textStatus, jqXHR) {
        //console.log("network.js: network_links: fail: " + textStatus);
        var msg = 'Données du réseau non trouvées, faites un cache-refresh sur la page Network List';
        $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
        $('#table_routingTable tbody').empty()
    });

    jqXHR.always(function (json, textStatus, jqXHR) {
        setTimeout(function () {
            $('#div_networkZigbeeAlert').hide()
        }, 10000);
    })

}

function filterColumnOnValue(data, col) {
    var filterValue = data;
    var filterColumn = col;
    //console.log('filtering col ' + filterColumn + ' on value ' + filterValue);
    $('#table_routingTable > tbody > tr').each(function (idx, val) {
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
