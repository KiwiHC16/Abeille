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


$("#tab_graph").off("click").on("click", function () {
    network_display();
});

$("#tab_route").off("click").on("click", function () {
    network_links();
});


$(".btn.refreshCache").off("click").on("click", function () {
    $('#div_networkZigbeeAlert').showAlert({message: "pas encore implementé", level: 'danger'});
    setTimeout(function () {
        $('#div_networkZigbeeAlert').hide()
    }, 5000);
});

$("#nodeFrom").off().change(function () {
    var value = $(this).val();
    filterColumnOnValue(value,0);
});

$("#nodeTo").off().change(function () {
    var value = $(this).val();
    filterColumnOnValue(value,2);
});



function network_display() {
    // Step 1. We create a graph object.
    var graph = Viva.Graph.graph();

    $.getJSON("/plugins/Abeille/Network/AbeilleLQI_MapData.json", function (json) {
        console.log(json);
        //Sort objetcs to have Voisin list array
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

        var nodes = [];

        for (var z in json.data) {
            console.log('Parsing: ' + json.data[z].NE_Name + '/' + json.data[z].Voisine_Name + ' * ' + json.data[z].Type); // this will show the info it in firebug console
            // Step 2. We add nodes and edges to the graph:
            //Add node if not already existing
            if ('undefined' == typeof(nodes[json.data[z].NE_Name])) {
                nodes[json.data[z].NE_Name] = {};
                nodes[json.data[z].NE_Name].name = json.data[z].NE_Name;
                nodes[json.data[z].NE_Name].links = [];
                nodes[json.data[z].NE_Name].route = json.data[z].NeighbourTableEntries;
            }
            var aTemp = nodes[json.data[z].NE_Name].links;
            aTemp.push(json.data[z].Voisine_Name);

            if ('undefined' == typeof(nodes[json.data[z].Voisine_Name])) {
                nodes[json.data[z].Voisine_Name] = {};
                nodes[json.data[z].Voisine_Name].links = [];
                nodes[json.data[z].Voisine_Name].route = 1;
                nodes[json.data[z].Voisine_Name].lqi = json.data[z].LinkQualityDec;
                nodes[json.data[z].Voisine_Name].name = json.data[z].Voisine_Name;
            }
            // voisine_name should have the Type of the node.
            nodes[json.data[z].Voisine_Name].Type = json.data[z].Type;

        }

        nodes['Ruche'].Type = ('undefined' == typeof(nodes['Ruche'].Type) ? 'Coordinator' : nodes['Ruche'].Type);

        for (node in nodes) {

            console.log('Adding node: name: ' + nodes[node].name + ' route: ' + nodes[node].route +
                ', Quality: ' + nodes[node].lqi + ', Type: ' + nodes[node].Type);

            graph.addNode(node, {
                    name: nodes[node].name, route: nodes[node].route,
                    Quality: nodes[node].lqi, Type: nodes[node].Type
                }
            );
            for (link in nodes[node].links) {

                console.log('addind link:' + nodes[node].name + ' <-> ' + nodes[node].links[link]);
                graph.addLink(node, nodes[node].links[link]);
            }
        }

        // Step 3. Render the graph.
        var graphics = Viva.Graph.View.svgGraphics(), nodeSize = 10,
            highlightRelatedNodes = function (nodeId, isOn) {
                graph.forEachLinkedNode(nodeId, function (node, link) {
                    var linkUI = graphics.getLinkUI(link.id);
                    if (linkUI) {
                        linkUI.attr('stroke', isOn ? '#FF0000' : '#B7B7B7');
                    }
                });
            };

        //
        graphics.node(function (node) {
            var nodeshape = 'rect';
            var nodecolor = '#E5E500', nodeSize = 10;
            if (typeof node.data != 'undefined') {
                nodecolor = (node.data.Type == 'Coordinator') ? '#a65ba6' : nodecolor;
                nodecolor = (node.data.Type == 'End Device') ? '#7BCC7B' : nodecolor;
                nodecolor = (node.data.Type == 'Router') ? '#00a2e8' : nodecolor;
            }
            var ui = Viva.Graph.svg('g'),
                svgText = Viva.Graph.svg('text').attr('y', '0px').text(node.id),
                img = Viva.Graph.svg(nodeshape)
                    .attr("width", nodeSize)
                    .attr("height", nodeSize)
                    .attr("fill", nodecolor);
            ui.append(svgText);
            ui.append(img);
            $(ui).hover(function (node) {
                var nodeText = 'name: '
                /*+ node.data.name + ', route: ' + node.data.route +
                                           ', Quality: ' + node.data.lqi + ', Type: ' + node.data.Type;
                                           */
                $('#nodeName').html(nodeText);

                highlightRelatedNodes(node.id, true);
            }, function () {
                highlightRelatedNodes(node.id, false);
            });
            return ui;
        }).placeNode(function (nodeUI, pos) {
            nodeUI.attr('transform',
                'translate(' +
                (pos.x - nodeSize / 3) + ',' + (pos.y - nodeSize / 2.5) +
                ')');
        });

        var idealLength = 200;
        var layout = Viva.Graph.Layout.forceDirected(graph, {
            springLength: idealLength,
            springCoeff: 0.0004,
            stableThreshold: 0.9,
            dragCoeff: 0.01,
            gravity: -20,
        });

        //remove previous one
        $('#graph_network svg').remove();

        var renderer = Viva.Graph.View.renderer(graph, {
            layout: layout,
            graphics: graphics,
            prerender: 10,
            container: document.getElementById('graph_network')
        });
        renderer.run();
        /*setTimeout(function () {
            renderer.pause();
            renderer.reset();
        }, 200);
        */


    })
        .done(function () {
            $('#div_networkZigbeeAlert').showAlert({message: '{{Action réalisée avec succès}}', level: 'success'});
            setTimeout(function () {
                $('#div_networkZigbeeAlert').hide()
            }, 2000);
        })
        .fail(function () {
            var msg = 'Données du réseau non trouvées, faites un cache-refresh sur la page Network List'
            $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
            setTimeout(function () {
                $('#div_networkZigbeeAlert').hide()
            }, 2000);
        })
};

function network_links() {

    $.getJSON("/plugins/Abeille/Network/AbeilleLQI_MapData.json", function (json) {
        var nodes = json.data;

        //Sort objects to have Voisin list array
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
        for (var node_id in nodes) {
            //console.log(nodes[node_id].Voisine + ":" + nodes[node_id].Voisine_Name + " ,");
            nodesTo[nodes[node_id].Voisine] = nodes[node_id].Voisine_Name;
            nodesFrom[nodes[node_id].NE] = nodes[node_id].NE_Name;
            tbody += (nodes[node_id].LinkQualityDec > 100) ? '<tr>' : '<tr class="active">';
            tbody += '<td>';
            if (nodes[node_id].NE != '') {
                tbody += '<span  class="label label-primary" style="font-size : 1em;">' + nodes[node_id].NE + '</span> ';
            } else {
                tbody += "{{N/A}}";
            }
            tbody += '</td>';
            tbody += '<td id="neName">';
            tbody += '<div style="opacity:0.5"><i>' + nodes[node_id].NE_Name + '</i></div>';
            tbody += '</td>';
            tbody += '<td id="vid">';
            tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[node_id].Voisine + '</span>';
            tbody += '</td>';
            tbody += '<td id="vname">';
            tbody += nodes[node_id].Voisine_Name;
            tbody += '</td>';
            tbody += '<td>';
            tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[node_id].Relationship + '</span>';
            tbody += '</td>';
            tbody += '<td>';
            tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[node_id].Depth + '</span>';
            tbody += '</td>';
            tbody += '<td id="lqi">';
            tbody += '<span class="label label-success" style="font-size : 1em;">' + nodes[node_id].LinkQualityDec + '</span>';
            tbody += '</td>';
            tbody += '<td>';
            tbody += '<span class="label label-warning" style="font-size : 1em;" >' + nodes[node_id].Type + '</span>';
            tbody += '</td>';
            tbody += '<td></tr>';
        }
        $('#table_routingTable tbody').empty().append(tbody);
        var nodeFrom = $('#nodeFrom'),
            nodeTo = $('#nodeTo');
        //console.log(optionAbNodes);
        $.each(nodesFrom, function (idx, item) {
            nodeFrom.append(new Option(item, idx));

        });
        $.each(nodesTo, function (idx, item) {
            nodeTo.append(new Option(item, idx));

        });
    })
        .done(function () {
            $('#div_networkZigbeeAlert').showAlert({message: '{{Action réalisée avec succès}}', level: 'success'});
            setTimeout(function () {
                $('#div_networkZigbeeAlert').hide()
            }, 2000);
        })
        .fail(function () {
            var msg = 'Données du réseau non trouvées, faites un cache-refresh sur la page Network List';
            $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
            setTimeout(function () {
                $('#div_networkZigbeeAlert').hide()
            }, 2000);
        })
};

function filterColumnOnValue(data,col) {
    var filterValue = data;
    var filterColumn= col;
    console.log('filtering col ' +filterColumn + ' on value ' + filterValue);
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
                    console.log(val.innerHTML);
                    val.style.display = '';
                } else {
                    val.style.display = 'none';
                }
                break;
        }
    })
}