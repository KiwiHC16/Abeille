<!DOCTYPE html>

<html>
<head>
    <style>
        table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 90%;
        }

        td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #dddddd;
        }

        html, body, svg {
            width: 90%;
            height: 100%;
        }

        .typeRouter {
            color: #a65c21;
        }

        .typeCoordinator {
            color: #a65ba6;
        }

        .typeEndDevice {
            color: #296da6;
        }

        .typeUndefined {
            color: #7BCC7B
        }

        }
    </style>
    <link rel="stylesheet" href="/3rdparty/font-awesome/css/font-awesome.min.css">
    <script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/vivagraph/vivagraph.min.js"></script>
    <script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/jquery/jquery.min.js"></script>
    <script type="text/javascript">
        function makeGraph() {

            // Step 1. We create a graph object.
            var graph = Viva.Graph.graph();

            $.getJSON("AbeilleLQI_MapData.json", function (json) {
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

                var tblLegend = $('<table>');
                tblLegend.addClass("table table-bordered table-condensed").css("width: 350px;position:fixed;margin-top : 25px;");
                var row = '<tr><th colspan="2">Légende</th></tr>';
                tblLegend.append(row);
                row = '<tr><td class="typeCoordinator" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Coordinateur</td></tr>';
                tblLegend.append(row);
                row = '<tr><td class="typeEndDevice" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Bout de chaine</td></tr>';
                tblLegend.append(row);
                row = '<tr><td class="typeRouter" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Routeur</td></tr>';
                tblLegend.append(row);
                row = '<tr><td class="typeUndefined" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Inconnu</td></tr>';
                tblLegend.append(row);

                $('#netLegend').empty()
                    .append(tblLegend);

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
                    var nodecolor = '#7BCC7B', nodeSize = 10;
                    if (typeof node.data != 'undefined') {
                        nodecolor = (node.data.Type == 'Coordinator') ? '#a65ba6' : nodecolor;
                        nodecolor = (node.data.Type == 'End Device') ? '#296da6' : nodecolor;
                        nodecolor = (node.data.Type == 'Router') ? '#a65c21' : nodecolor;
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
                        (pos.x - nodeSize / 3) + ',' + (pos.y - nodeSize / 2.5) +
                        ')');
                });

                var layout = Viva.Graph.Layout.forceDirected(graph, {
                    springLength: 100,
                    springCoeff: 0.0005,
                    dragCoeff: 0.02,
                    gravity: -0.2
                });

                var renderer = Viva.Graph.View.renderer(graph, {
                    layout: layout,
                    graphics: graphics,
                    prerender: 10,
                    container: document.getElementById('netGraph')
                });
                renderer.run();


            });
        };

    </script>

</head>

<body onload="makeGraph()">
<h1>Abeille Network Table</h1>

<?php
require_once dirname(__FILE__) . "/../../../core/php/core.inc.php";

/*
 (
 [0] => Array
 (
 [NeighbourTableEntries] => 0A
 [Index] => 00
 [ExtendedPanId] => 28d07615bb019209
 [IEEE_Address] => 00158d00019f9199
 [Depth] => 01
 [LinkQuality] => 75
 [BitmapOfAttributes] => 1a
 [NE] => 0000
 [NE_Name] => Ruche
 [Voisine] => df33
 [Voisine_Name] => Test: Temperature Rond Bureau
 [Type] => End Device
 [Relationship] => Child
 [Rx] => Rx-Off
 [LinkQualityDec] => 117
 )
 */

if (isset($_GET['NE'])) {
    $NE = $_GET['NE'];
} else {
    $NE = "All";
}

if (isset($_GET['NE2'])) {
    $NE2 = $_GET['NE2'];
} else {
    $NE2 = "None";
}

if (isset($_GET['Cache'])) {
    $Cache = $_GET['Cache'];
} else {
    $Cache = "Cache";
}

//require_once("NetworkDefinition.php");

$DataFile = "AbeilleLQI_MapData.json";

if ($Cache == "Refresh Cache") {
    // Ici on n'utilise pas le cache donc on lance la collecte
    require_once(__DIR__."/../core/php/AbeilleLQI.php");
}

if (file_exists($DataFile)) {

    $json = json_decode(file_get_contents($DataFile), true);
    // $LQI = $json->data;
    $LQI = $json['data'];
    // print_r( $LQI );
    // exit;
} else {
    echo "Le cache n existe pas, faites un refresh.<br>";
}

$eqLogics = eqLogic::byType('Abeille');
foreach ($eqLogics as $eqLogic) {
    $name = $eqLogic->getName();
    $shortAddress = str_replace("Abeille/", "", $eqLogic->getLogicalId());
    $shortAddress = ($name == 'Ruche') ? "0000" : $shortAddress;
    $knownNE[$name] = $shortAddress;
}


?>


<form method="get">
    <select name="NE" id="selectNE">
        <?php
        $selected = ($NE == "All") ? " selected " : "";
        echo '<option value="All"' . $selected . '>All</option>' . "\n";

        $selected = ($NE == "None") ? " selected " : "";
        echo '      <option value="None"' . $selected . '>None</option>' . "\n";

        foreach ($knownNE as $name => $shortAddress) {
            $selected = ($NE == $name) ? " selected " : "";
            echo '      <option value="' . $name . '"' . $selected . '>' . $name . ' x ' . $shortAddress . '</option>' . "\n";
        }

        ?>
    </select>
    <select name="NE2" id="selectNE2">
        <?php
        $selected = ($NE2 == "All") ? " selected " : "";
        echo '<option value="All"' . $selected . '>All</option>' . "\n";

        $selected = ($NE2 == "None") ? " selected " : "";
        echo '      <option value="None"' . $selected . '>None</option>' . "\n";

        foreach ($knownNE as $name => $shortAddress) {
            $selected = ($NE2 == $name) ? " selected " : "";
            echo '      <option value="' . $name . '"' . $selected . '>' . $name . ' x ' . $shortAddress . '</option>' . "\n";
        }
        ?>
    </select>
    <select name="Cache">
        <?php
        $CacheList = array('Cache', 'Refresh Cache');
        foreach ($CacheList as $item) {
            $selected = ($Cache == $item) ? " selected " : "";
            echo '<option value="' . $item . '"' . $selected . '>' . $item . '</option>' . "\n";
        }
        ?>
    </select>
    <input type="submit" value="Submit">
</form>

<div id="listNetElement">

    <?php
    echo '<table id="tblNetElement">
    <tr>
        <th>NE</th>
        <th>NE Name</th>
        <th>Voisine</th>
        <th>Voisine Name</th>
        <th>Voisine IEEE</th>
        <th>Relation</th>
        <th>Profondeur</th>
        <th>LQI</th>
    </tr>';

    // echo "DEBUT: ".date(DATE_RFC2822)."<br>\n";
    // var_dump( $LQI );
    // var_dump( $NE );
    // $NE="All";

    // On reset $NE qui est utilisé pour different truc.
    if ($Cache == "Refresh Cache") {
        $NE = "All";
    }

    foreach ($LQI as $key => $voisine) {
        if (($voisine['NE_Name'] == $NE) || ("All" == $NE) || ($voisine['Voisine_Name'] == $NE2)) {
            echo "
     <tr>
          <td>" . $voisine['NE'] . "</td><td>" . $voisine['NE_Name'] . "</td><td>" . $voisine['Voisine'] . "</td><td>" . $voisine['Voisine_Name'] . "</td><td>" . $voisine['IEEE_Address'] . "</td><td>" . $voisine['Relationship'] . "</td><td>" . $voisine['Depth'] . "</td><td>" . $voisine['LinkQualityDec'] . "</td>
     </tr>\n";
        }
    }

    // Formating pour la doc asciidoc
    if (0) {
        // echo "<table>\n";
        // echo "<tr><td>NE</td><td>Voisine</td><td>Relation</td><td>Profondeur</td><td>LQI</td></tr>\n";
        echo "|NE|Voisine|Relation|Profondeur|LQI\n";

        foreach ($LQI as $key => $voisine) {
            // echo "<tr>";
            // echo "<td>".$voisine['NE']."</td><td>".$voisine['Voisine']."</td><td>".$voisine['Relationship']."</td><td>".$voisine['Depth']."</td><td>".$voisine['LinkQualityDec']."</td>";

            echo "|" . $voisine['NE'] . "|" . $voisine['NE_Name'] . "|" . $voisine['Voisine'] . "|" . "|" . $voisine['Voisine_Name'] . "|" . $voisine['Relationship'] . "|" . $voisine['Depth'] . "|" . $voisine['LinkQualityDec'] . "\n";

            // echo "</tr>\n";
        }
        // echo "</table>\n";
    }

    // print_r( $NE_All );
    // print_r( $voisine );
    // print_r( $LQI );

    // deamonlog('debug', 'sortie du loop');
    // echo "FIN: ".date(DATE_RFC2822)."<br>\n";
    echo '</table>';
    ?>

</div>
<br>
<span id="netLegend" style="width:200px;position:absolute;"></span>
<span id="nodeName" style="width:200px;position:absolute;"></span>
<br>
<div id="netGraph" style="width: 100%; height: 500px;border: aqua;border-style: dotted;"></div>
</body>
</html>
