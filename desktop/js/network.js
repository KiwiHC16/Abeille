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

 $('.controller_action').on('click',function(){
                $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
 });


 $("#tab_graph").off("click").on("click", function () {
     network_display();
     $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
});

 $("#tab_route").off("click").on("click", function () {
     $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
});

$(".bt_addDevice").off("click").on("click", function () {
        $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
});

$("#removeDevice").off("click").on("click", function () {
   $('#div_networkZigbeeAlert').showAlert({message: '{{Action réalisée avec succès}}', level: 'success'});
});

$("#replicationSend").off("click").on("click", function () {
    $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
});

$("#regenerateNodesCfgFile").off("click").on("click", function () {
});
$("body").off("click", ".requestNodeNeighboursUpdate").on("click", ".requestNodeNeighboursUpdate", function (e) {
          $('#div_networkZigbeeAlert').showAlert({message: '{{Action réalisée avec succès}}', level: 'success'});
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
        console.log(nodes);

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
                var nodeText = 'name: ' /*+ node.data.name + ', route: ' + node.data.route +
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
                (pos.x - nodeSize / 3)+',' + (pos.y - nodeSize / 2.5) +
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


    });
};



/*
function network_load_data(){
    $('#graph_network svg').remove();
    jeedom.openzwave.network.info({
        info:'getNeighbours',
        error: function (error) {
            $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (data) {
            nodes = data['devices'];
            var graph = Viva.Graph.graph();
            var controllerId = 1;
            for (z in nodes) {
                if (nodes[z].data.isPrimaryController.value == true){
                    controllerId = parseInt(z);
                    break;
                }
            }
            const queryStageNeighbors = 13;
            for (z in nodes) {
                if (nodes[z].data.name.value != '') {
                    if (isset(eqLogic_human_name[z])) {
                        graph.addNode(z, {
                            'name': eqLogic_human_name[z],
                            'neighbours': nodes[z].data.neighbours.value,
                            'enabled': nodes[z].data.neighbours.enabled,
                            'interview': parseInt(nodes[z].data.state.value)
                        });
                    } else {
                        graph.addNode(z, {
                            'name': '<span class="label label-primary">' + nodes[z].data.location.value + '</span> ' + nodes[z].data.name.value,
                            'neighbours': nodes[z].data.neighbours.value,
                            'enabled': nodes[z].data.neighbours.enabled,
                            'interview': parseInt(nodes[z].data.state.value)
                        });
                    }
                } else {
                    graph.addNode(z, {
                        'name': nodes[z].data.product_name.value,
                        'neighbours': nodes[z].data.neighbours.value,
                        'enabled': nodes[z].data.neighbours.enabled,
                        'interview': parseInt(nodes[z].data.state.value)
                    });
                }
                if (nodes[z].data.neighbours.value.length < 1 && nodes[z].data.neighbours.enabled != 1) {
                    if (typeof nodes[controllerId] != 'undefined') {
                        graph.addLink(z, controllerId, {isdash: 1, lengthfactor: 0.6});
                    }
                } else {
                    for (neighbour in nodes[z].data.neighbours.value) {
                        neighbourid = nodes[z].data.neighbours.value[neighbour];
                        if (typeof nodes[neighbourid] != 'undefined') {
                            graph.addLink(z, neighbourid, {isdash: 0, lengthfactor: 0});
                        }
                    }
                }
            }
            var graphics = Viva.Graph.View.svgGraphics(),
            nodeSize = 24,
            highlightRelatedNodes = function (nodeId, isOn) {
                graph.forEachLinkedNode(nodeId, function (node, link) {
                    var linkUI = graphics.getLinkUI(link.id);
                    if (linkUI) {
                        linkUI.attr('stroke', isOn ? '#FF0000' : '#B7B7B7');
                    }
                });
            };
            graphics.node(function (node) {
                if (typeof node.data == 'undefined') {
                    graph.removeNode(node.id);
                }
                nodecolor = '#7BCC7B';
                var nodesize = 10;
                const nodeshape = 'rect';
                if (node.id == controllerId) {
                    nodecolor = '#a65ba6'; 
                    nodesize = 16;
                } else if (node.data.enabled == false) {
                    nodecolor = '#00a2e8'; 
                } else if (node.data.neighbours.length < 1 && node.id != controllerId && node.data.interview >= queryStageNeighbors) {
                    nodecolor = '#d20606'; 
                } else if (node.data.neighbours.indexOf(controllerId) == -1 && node.id != controllerId && node.data.interview >= queryStageNeighbors) {
                    nodecolor = '#E5E500'; 
                } else if (node.data.interview < queryStageNeighbors) {
                    nodecolor = '#979797'; 
                }
                var ui = Viva.Graph.svg('g'),
                svgText = Viva.Graph.svg('text').attr('y', '0px').text(node.id),
                img = Viva.Graph.svg(nodeshape)
                .attr("width", nodesize)
                .attr("height", nodesize)
                .attr("fill", nodecolor);
                ui.append(svgText);
                ui.append(img);
                $(ui).hover(function () {
                    var link = 'index.php?v=d&p=openzwave&m=openzwave&logical_id=' + node.id;
                    numneighbours = node.data.neighbours.length;
                    interview = node.data.interview;
                    if (numneighbours < 1 && interview >= queryStageNeighbors) {
                        if (node.data.enabled) {
                            sentenceneighbours = '{{Pas de voisins}}';
                        } else {
                            sentenceneighbours = '{{Télécommande}}'
                        }
                    } else if (interview >= queryStageNeighbors) {
                        sentenceneighbours = numneighbours + ' {{voisins}} [' + node.data.neighbours + ']';
                    } else {
                        sentenceneighbours = '{{Interview incomplet}}';
                    }
                    if (node.id != controllerId) {
                        linkname = '<a href="' + link + '">' + node.data.name + '</a>'
                    } else {
                        linkname = node.data.name
                    }
                    $('#graph-node-name').html(linkname + ' : ' + sentenceneighbours);
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
            var middle = graph.getNode(controllerId);
            if (typeof middle !== 'undefined') {
                middle.isPinned = true;
            }
            var idealLength = 200;
            var layout = Viva.Graph.Layout.forceDirected(graph, {
                springLength: idealLength,
                stableThreshold: 0.9,
                dragCoeff: 0.01,
                springCoeff: 0.0004,
                gravity: -20,
                springTransform: function (link, spring) {
                    spring.length = idealLength * (1 - link.data.lengthfactor);
                }
            });
            graphics.link(function (link) {
                dashvalue = '5, 0';
                if (link.data.isdash == 1) {
                    dashvalue = '5, 2';
                }
                return Viva.Graph.svg('line').attr('stroke', '#B7B7B7').attr('stroke-dasharray', dashvalue).attr('stroke-width', '0.4px');
            });
            var renderer = Viva.Graph.View.renderer(graph, {
                layout: layout,
                graphics: graphics,
                prerender: 10,
                renderLinks: true,
                container: document.getElementById('graph_network')
            });
            renderer.run();
            setTimeout(function () {
                renderer.pause();
                renderer.reset();
            }, 200);
        }
    });
}


function network_load_info(){
    jeedom.openzwave.network.info({
        info : 'getStatus',
        global:false,
        error: function (error) {
            $('#div_networkZigbeeAlert').showAlert({message: error.message, level: 'danger'});
            if($('#div_templateNetwork').html() != undefined && $('#div_templateNetwork').is(':visible')){
                setTimeout(function(){  
                    network_load_data();
                    network_load_info(); 
                }, 2000);
            }
        },
        success: function (data) {
            data.startTime = jeedom.openzwave.timestampConverter(data.startTime);
            data.pollInterval = jeedom.openzwave.durationConvert(parseInt(data.pollInterval, 0) / 1000);
            data.awakedDelay = (data.awakedDelay != null) ?  'opérationnel en ' + data.awakedDelay + ' secondes':'';
            switch (data.state) {
                case 0:
                data.state = "<i class='fa fa-exclamation-circle rediconcolor'></i>";
                break;
                case 1:
                data.state = "<i class='fa fa-exclamation-circle rediconcolor'></i>";
                break;
                case 3:
                data.state = "<i class='fa fa-exclamation-circle rediconcolor'></i>";
                break;
                case 5:
                data.state = "<i class='fa fa-circle yellowiconcolor'></i>";
                break;
                case 7:
                data.state = "<i class='fa fa-bullseye greeniconcolor'></i>";
                break;
                case 10:
                data.state = "<i class='fa fa-circle greeniconcolor'></i>";
                break;
            }
            data.node_capabilities = '';
            if (data.controllerNodeCapabilities.indexOf('primaryController') != -1) {
                data.node_capabilities += '<li>{{Contrôleur Primaire}}</li>'
            }
            if (data.controllerNodeCapabilities.indexOf('staticUpdateController') != -1) {
                data.node_capabilities += '<li>{{Contrôleur statique de mise à jour (SUC)}}</li>'
            }
            if (data.controllerNodeCapabilities.indexOf('bridgeController') != -1) {
                data.node_capabilities += '<li>{{Contrôleur secondaire}}</li>'
            }
            if (data.controllerNodeCapabilities.indexOf('listening') != -1) {
                data.node_capabilities += '<li>{{Le noeud est alimenté et écoute en permanence}}</li>'
            }
            if (data.controllerNodeCapabilities.indexOf('beaming') != -1) {
                data.node_capabilities += '<li>{{Le noeud est capable d\'envoyer une trame réseaux}}</li>';
            }
            data.outgoingSendQueue = parseInt(data.outgoingSendQueue, 0);
            if (data.outgoingSendQueue == 0) {
                data.outgoingSendQueueDescription = "<i class='fa fa-circle fa-lg greeniconcolor'></i>";
            }else if (data.outgoingSendQueue <= 5) {
                data.outgoingSendQueueDescription = "<i class='fa fa-spinner fa-spin fa-lg greeniconcolor'></i>";
            }else if (data.outgoingSendQueue <= 15) {
                data.outgoingSendQueueDescription = "<i class='fa fa-spinner fa-spin fa-lg yellowiconcolor'></i>";
            }else {
                data.outgoingSendQueueDescription = "<i class='fa fa-spinner fa-spin fa-lg rediconcolor'></i>";
            }
            $('#div_templateNetwork').setValues(data, '.zwaveNetworkAttr');
            if($('#div_templateNetwork').html() != undefined && $('#div_templateNetwork').is(':visible')){
                setTimeout(function(){ network_load_info(); }, 2000);
            }
        }
    });
}

function getRoutesCount(nodeId) {
    var routesCount = {};
    $.each(getFarNeighbours(nodeId), function (index, nnode) {
        if (nnode.nodeId in routesCount) {
            if (nnode.hops in routesCount[nnode.nodeId]){
                routesCount[nnode.nodeId][nnode.hops]++;
            } else{
                routesCount[nnode.nodeId][nnode.hops] = 1;
            }
        } else {
            routesCount[nnode.nodeId] = new Array();
            routesCount[nnode.nodeId][nnode.hops] = 1;
        }
    });
    return routesCount;
}

function getFarNeighbours(nodeId, exludeNodeIds, hops) {
    if (hops === undefined) {
        var hops = 0;
        var exludeNodeIds = [nodeId];
    }
    if (hops > 2) 
        return [];
    var nodesList = [];
    $.each(devicesRouting[nodeId].data.neighbours.value, function (index, nnodeId) {
        if (!(nnodeId in devicesRouting)){
            return; 
        }
        if (!in_array(nnodeId, exludeNodeIds)) {
            nodesList.push({nodeId: nnodeId, hops: hops});
            if (devicesRouting[nnodeId].data.isListening.value && devicesRouting[nnodeId].data.isRouting.value){
                $.merge(nodesList, getFarNeighbours(nnodeId, $.merge([nnodeId], exludeNodeIds), hops + 1));
            }
        }
    });
    return nodesList;
}
*/
//makeGraph();