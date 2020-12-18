<?php

    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    /* Add network display & refresh buttons for all active zigates */
    function displayButtons($nbOfZigates, $what="linksTable") {
        echo 'Afficher réseau :';
        for ($i = 1; $i <= $nbOfZigates; $i++) {
            if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y')
                continue; // Disabled

            if ($what == "linksTable")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayNodes('.$i.')">Abeille'.$i.'</a>';
            else // "linksGraph"
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="network_display('.$i.')">Abeille'.$i.'</a>';
            echo '<a class="btn btn-warning" title="Forçe la réinterrogation du réseau. Peut prendre plusieurs minutes en fonction du nombre d\'équipements." onclick="refreshLQICache('.$i.')"><i class="fas fa-sync"></i></a>';
            echo '&nbsp;&nbsp;';
        }
    }

    // Last Demon start time
    $startTime = config::byKey('lastDeamonLaunchTime', 'Abeille', '{{Demon Jamais lancé}}');

    // Status demon
    $status = "<i class=\"fa fa-circle fa-lg rediconcolor\"></i>{{Plugin désactivé et démon non configuré}}";
    if (config::byKey('active', 'Abeille', '0') == 1) {
        if (Abeille::deamon_info()['state'] == 'ok') {
            $status = "<i class=\"fa fa-circle fa-lg greeniconcolor\"></i>{{Plugin activé et démon configuré}}";
        } else {
            $status = "<i class=\"fa fa-circle fa-lg rediconcolor\"></i>{{Plugin activé mais démon non configué}}";
        }
    }

    $eqLogics = eqLogic::byType('Abeille');
    $nbOfZigates = config::byKey('zigateNb', 'Abeille', '1');

    // Node Count
    $nodesCount = count($eqLogics);
    $timerCount = 0;
    foreach ($eqLogics as $eqLogic) {
        if (preg_match("(Timer)", $eqLogic->getLogicalId())) $timerCount++;
    }

    // Liste des noeuds
    $neighbors = "";
    $nodes = array();
    foreach ($eqLogics as $eqLogic) {
        $neighbors .= $eqLogic->getName() . ", ";
        $nodes[str_replace("/", "x", $eqLogic->getLogicalId())] = $eqLogic->getId();
    }
    $neighbors = substr($neighbors, 0, -2); // enleve la virgule et l espace en fin de chaine

    // Nombre de process actifs
    $processes = Abeille::deamon_info();
    $color = "greeniconcolor";
    $color = ($processes['nbProcess'] == $processes['nbProcessExpected']) ? "greeniconcolor" : "rediconcolor";
    $nbDaemons = "<i class=\"fa fa-circle fa-lg " . $color . "\"></i> " . $processes['nbProcess'] . "/".$processes['nbProcessExpected'];

    sendVarToJS('nodesFromJeedom', $nodes);

?>

<style>
    #graph_network {
        height: 80%;
        width: 90%;
        position: absolute;
    }

    #graph_network > svg {
        height: 100%;
        width: 100%
    }

    .node-item {
        border: 1px solid;
    }

    .typeCoordinator-color {
        color: #a65ba6;
    }

    .typeEndDevice-color {
        color: #7BCC7B;
    }

    .typeRouter-color {
        color: #00a2e8;
    }

    .typeUndefined-color {
        color: #E5E500;
    }

    .typeAlert-color {
      color: ##FF4000;
    }

    .node-more-of-two-up-color {
        color: #FFAA00;
    }

    .node-interview-not-completed-color {
        color: #979797;
    }

    .node-no-neighbourhood-color {
        color: #d20606;
    }

    .node-na-color {
        color: white;
    }

    .greeniconcolor {
        color: green;
    }

    .yellowiconcolor {
        color: #FFD700;
    }

    .rediconcolor {
        color: red;
    }
</style>

<!-- <link rel="stylesheet" href="/3rdparty/font-awesome5/css/font-awesome.min.css"> -->
<link rel="stylesheet" href="/3rdparty/jquery.tablesorter/jquery.tablesorter.pager.min.css">
<script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/vivagraph/vivagraph.min.js"></script>
<!--script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/jquery.tablesorter/jquery.tablesorter.min.js"></script-->
<!--script type="text/javascript" src="/core/php/getResource.php?file=plugins/Abeille/3rdparty/vivagraph/vivagraph.min.js"></script-->

<!-- Area to display alert message -->
<div id='div_networkZigbeeAlert' style="display: none;"></div>

<!-- Affichage de tous les Tab. -->
<div class='network' nid='' id="div_templateNetwork">
    <div class="container-fluid">
        <div id="content">

            <ul id="tabs_network" class="nav nav-tabs" data-tabs="tabs">
                <li class="active"  id="tab_nodes">    <a href="#route_network"   data-toggle="tab"> <i class="fa fa-table">       </i> {{Table des noeuds}}      </a></li>
                <li                 id="tab_graph">    <a href="#graph_network"   data-toggle="tab"> <i class="fa fa-picture-o">   </i> {{Graphique du réseau}}   </a></li>
                <!-- <li                 id="tab_summary">  <a href="#summary_network" data-toggle="tab"> <i class="fa fa-tachometer">  </i> {{Résumé}}                </a></li> -->
                <li                 id="tab_test1">    <a href="#test1"           data-toggle="tab"> <i class="fa fa-tachometer">  </i> {{Bruit}}                 </a></li>
                <li                 id="tab_test2">    <a href="#test2"           data-toggle="tab"> <i class="fa fa-tachometer">  </i> {{Routes}}                 </a></li>
            </ul>

            <div id="network-tab-content" class="tab-content">

                <!-- tab "nodes table" -->
                <div id="route_network" class="tab-pane active">
                    <br />
                    {{Noeuds connus du réseau et LQI (<a href="http://kiwihc16.free.fr/Radio.html" target="_blank">Link Quality Indicator</a>) associé. Informations remises-à-jour une fois par jour.}}<br />
                    <br />
                    <div id="div_routingTable">
                        <?php
                            displayButtons($nbOfZigates, "linksTable");
                        ?>
                        <br />
                        <hr>
                        Informations affichées : <span id="idDisplayedNetwork" style="width:150px; font-weight:bold">-</span>, collecte du <span id="idDisplayedDate" style="width:150px; font-weight:bold">-</span>
                        <br />
                        <br />

                        <label class="control-label" data-toggle="tooltip" title="Filtre les noeuds par emetteur">{{Source}}</label>
                        <select class="filterSource" id="nodeFrom"> </select>

                        <label class="control-label" data-toggle="tooltip" title="Filtre les noeuds par destinataire">{{Destinataire}}</label>
                        <select class="filterRecipient" id="nodeTo"> </select>

                        <table class="table table-condensed tablesorter" id="table_routingTable">
                            <thead>
                                <tr>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{ID}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{NE_Objet}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Name}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Voisine}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Voisine_Objet}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Voisine_Name}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Relation}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Profondeur}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{LQI}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Type}}</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- tab Graphique du réseau -->
                <div id="graph_network" class="tab-pane">

                    <br />
                    <?php
                        displayButtons($nbOfZigates, "linksGraph");
                    ?>

                    <table class="table table-bordered table-condensed"
                           style="width: 150px;position:fixed;margin-top : 25px;">
                        <thead>
                        <tr>
                            <th colspan="2">{{Légende}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="typeCoordinator-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>{{Coordinateur}}</td>
                        </tr>
                        <tr>
                            <td class="typeEndDevice-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>{{Bout de chaine}}</td>
                        </tr>
                        <tr>
                            <td class="typeRouter-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>{{Routeur}}</td>
                        </tr>
                        <tr>
                            <td class="typeUndefined-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>{{Inconnu}}</td>
                        </tr>

                        </tbody>
                    </table>

                    <span id="graph-node-name" style="width: 100%;height: 100%"></span>
                </div>

<!-- tab Résumé -->
                <div id="summary_network" class="tab-pane" >
                    <br />

                    <div class="panel panel-primary">
                        <div class="panel-heading"><h4 class="panel-title">{{Informations}}</h4></div>
                        <div class="panel-body">
                            <p>{{Réseau démarré le}} <span class="zigBNetworkAttr label label-default" style="font-size : 1em;" data-l1key="startTime"><?php echo $startTime ?></span> <span class="zigBNetworkAttr label label-default" data-l1key="awakedDelay" style="font-size : 1em;"></span></p>
                            <p>{{Le réseau contient}} <b><span class="zigBNetworkAttr" data-l1key="nodesCount"></span><?php echo $nodesCount ?> </b> {{noeuds dont }} <?php echo $nodesCount-$timerCount ?> {{noeuds zigbee (zigate(s) incluse(s)) et }} <?php echo $timerCount ?> {{timers}}</p>
                            <p>{{Voisins :}}<span class="zigBNetworkAttr label-default" data-l1key="neighbors" style="font-size : 1em;"><?php echo $neighbors ?></span></p>
                        </div>
                    </div>

                    <div class="panel panel-primary">
                        <div class="panel-heading"><h4 class="panel-title">{{Etat}}</h4></div>
                        <div class="panel-body">
                            <p><span class="zigBNetworkAttr" data-l1key="state"></span> {{Etat actuel :}} <span class="zigBNetworkAttr label label-default" data-l1key="stateDescription" style="font-size : 1em;"><?php echo $status ?></span></p>
                        </div>
                    </div>

                    <div class="panel panel-primary">
                        <div class="panel-heading"><h4 class="panel-title">{{Système}}</h4></div>
                        <div class="panel-body">

                            <p>{{Chemin du contrôleur Zigbee :}}
                                <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;">
<?php                           for ( $i=1; $i<=config::byKey('zigateNb', 'Abeille', '1'); $i++ ) {
                                    echo config::byKey('AbeilleSerialPort'.$i, 'Abeille', '{{Inconnu}}').", ";
                                }
?>
                                </span>
                            </p>
                            <p>{{Nombre de démons lancés :}} <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;"><?php echo $nbDaemons ?></span> </p>
                        </div>
                    </div>




                </div>

<!-- tab Bruit -->

                <div id="test1" class="tab-pane" >
                                (Ce texte devra etre mis dans la doc)<br />
                                Cette page a pour objectif d essayer de comprendre le niveau de bruit radio que subit votre reseau zigbee.<br />
                                En utilisant le bouton refresh, Abeille va demander aux équipments de mesurer le bruit qu ils entendent sur les canaux 11 à 26.<br />
                                Ceux ci vont répondre avec une valeur pour chaque canal qui represente la puissance mesurée entre 0 et 255.<br />
                                A ma connaissance la méthode de mesure n est pas définie dans le standard Zigbee et donc chaque fabricant est libre de mesurer comme il le souhaite.<br />
                                C est une premiere version avant que je ne comprenne mieux les résultats.<br />
                                Les ampoules Ikea repondent aux demandes. Elles retournent une valeur entre 160 et 180 quand le canal est libre<br />
                                Par contre quand le canal est aucupé elles remontent des valeurs entre 200 et 210.<br />
                                Ces valeurs viennent du test suivant:
                                - libre: reseau en fonctionnement normal lors de mon test.
                                - occupé: camera wifi sur 2.4GHz Canal Wifi 1, avec un débit de l ordre de 2Mbps, posée sur l ampoule.
                                Avec ce test on voit clairement que les canaux zigbee 11, 12, 13 sont impactés.<br />
                                A noter: la mesure se fait sur une courte periode, il faut peut faire plusieures mesures pour avoir une bonne idée de la charge des canaux.<br />
                                A noter: Apres un appui sur Refresh All, il n y a pas encore d affichage d avancement du processus.<br />
                                Attendez et rafraichissez la page au bout d un certain temps. Il faut compter 5s par équipement...<br />
                                Si une mesure a ete faite pour un equipement le graph doit apparaitre.<br />
                                Attention l heure de mesure n est pas encore dispo. Donc vous pouvez avoir une vieille mesure a l ecran.<br />
                                <br />
                                Attention: Apres un "Collecter" ou "Tout Collecter" il faut rafraichir la page pour mettre a jour les graphiques.<br />

<?php
                                echo '<a class="btn btn-success refreshBruitAll"><i class="fa fa-refresh" ></i>{{Tout collecter}}</a><br /><br />';

                                function afficheGraph( $title, $logicalId, $values ) {
                                // Vertical
                                $hauteurGraph = 256;
                                $hauteurLegendX = 30;

                                // Horizontal
                                $margeLeft = 10;
                                $largeurGraph = 300;
                                $margeRight = $margeLeft;

                                $channelMin = 11;
                                $channelMax = 26;

                                // Objet
                                $cercleRayon = 5;
                                $lineBruitY = 256 - 180;

                                // As a result
                                $largeurCadre = $margeLeft + $largeurGraph + $margeRight;
                                $hauteurCadre = $hauteurGraph+$hauteurLegendX;

                                $positionVerticalLegendX = $hauteurCadre - $hauteurLegendX/2;

                                    echo $title.'<br /><br />';

                                    echo '<svg width="'.$largeurCadre.'" height="'.$hauteurCadre.'">';
                                    echo '<rect width="'.$largeurCadre.'" height="'.$hauteurGraph.'" style="fill:rgb(0,0,200);stroke-width:5;stroke:rgb(255,0,0)"/>';
                                    echo '<line x1 = "1" y1 = "'.$lineBruitY.'" x2 = "'.$largeurCadre.'" y2 = "'.$lineBruitY.'" style = "stroke:yellow; stroke-width:3"/>';
                                    foreach( $values as $key=>$value ) {
                                        $x = ($key-11)*($largeurGraph/($channelMax-$channelMin))+10; // Ch 11 a 26 vers X de 0 a 300 plus 10 pour centrer pour une largeur de 320 = 10(Marge)+300
                                        $y = $hauteurGraph - $value;
                                        echo '<circle cx= "'.$x.'" cy= "'.$y.'" r="'.$cercleRayon.'" stroke= "red" stroke-width="1" fill="red" />';
                                        $xText = $x - $cercleRayon;
                                        echo '<text x="'.$x.'" y="'.$positionVerticalLegendX.'" fill="purple">'.$key.'</text>';
                                    }
                                    echo '</svg>';
                                      if ( $logicalId != "" ) {
                                            echo '<br /><a data-action="refreshBruit_'.str_replace('/','',$logicalId).'" class="btn btn-success refreshBruit_'.str_replace('/','',$logicalId).'"><i class="fa fa-refresh" ></i>{{Collecter}}</a><br /><br />';

                                      }
                                    echo "<br /><br />";

                                }

                                // Exemple 1: Pas chargé, Exemple 2: Chargé
                                $localZigbeeChannelPowerExample1 = array( 11=>176, 12=>175, 13=>178, 14=>170, 15=>160, 16=>177, 17=>176, 18=>176, 19=>175, 20=>157, 21=>172, 22=>163, 23=>173, 24=>160, 25=>170, 26=>171 );
                                $localZigbeeChannelPowerExample2 = array( 11=>206, 12=>209, 13=>200, 14=>171, 15=>180, 16=>174, 17=>177, 18=>170, 19=>176, 20=>159, 21=>171, 22=>162, 23=>173, 24=>161, 25=>156, 26=>172 );
                                afficheGraph( "Exemple 1", "", $localZigbeeChannelPowerExample1 );
                                afficheGraph( "Exemple 2", "", $localZigbeeChannelPowerExample2 );

                                // Affichons toutes les Abeilles
                                $eqLogics = Abeille::byType('Abeille');
                                foreach ($eqLogics as $eqLogic) {
                                    if( $eqLogic->getConfiguration('localZigbeeChannelPower') ) {
                                        // $values = $eqLogic->getConfiguration('localZigbeeChannelPower');
                                        // var_dump($eqLogic->getConfiguration('localZigbeeChannelPower'));
                                        afficheGraph( $eqLogic->getName(), $eqLogic->getLogicalId(), $eqLogic->getConfiguration('localZigbeeChannelPower') );
                                    }
                                }

?>
                </div>

<!-- tab Route -->

                <div id="test2" class="tab-pane" >
                    <?php
                                echo '<a class="btn btn-success refreshRoutesAll"><i class="fa fa-refresh" ></i>{{Tout collecter}}</a><br /><br />';
                                echo 'Il faut un firmware zigate au moins en version 3.1d<br /><br />';

                                function afficheRouteTable( $routingTable ) {
                                    foreach ( $routingTable as $addr=>$route ) {
                                        list( $zigate, $addrShort ) = explode ( '/', $addr );
                                        if ( $addrShort == '0000' ) $addrShort = 'Ruche';
                                        foreach ( $route as $destination=>$nextHop ) {
                                            if ( $destination == '0000' ) $destination = 'Ruche';
                                            if ( $nextHop == '0000' )     $nextHop = 'Ruche';

                                            $sourceEq       = Abeille::byLogicalId($zigate.'/'.$addrShort, Abeille);
                                            $destinationEq  = Abeille::byLogicalId($zigate.'/'.$destination, Abeille);
                                            $nextHopEq      = Abeille::byLogicalId($zigate.'/'.$nextHop, Abeille);

					    if (!is_object($sourceEq)) 	continue;
					    if (!is_object($destinationEq)) 	continue;
					    if (!is_object($nextHopEq)) 	continue;

                                            echo 'Si ' . $sourceEq->getObject()->getName() . '-' .$sourceEq->getName() . ' ('.$sourceEq->getLogicalId()
                                                . ') veut joindre '.$destinationEq->getObject()->getName() . '-' .$destinationEq->getName() . ' ('.$destinationEq->getLogicalId()
                                                . ') passera par '.$nextHopEq->getObject()->getName() . '-' .$nextHopEq->getName() . ' ('.$nextHopEq->getLogicalId()
                                                . ')<br>';
                                            // echo ' ('.$sourceEq->getLogicalId().') veut joindre ('.$destinationEq->getName(); //.') passera par ('.$nextHopEq->getName().')<br />';
                                        }
                                    }
                                }

                                $routingTable = array();
                                $eqLogics = Abeille::byType('Abeille');
                                foreach ($eqLogics as $eqLogic) {
                                    $rt = $eqLogic->getConfiguration('routingTable', 'none');
                                    if ($rt == 'none')
                                        continue; // No routing info
                                    $rt2 = json_decode($rt); // JSON to array
                                    if (empty($rt2))
                                        continue; // Empty routing info
                                    $routingTable[$eqLogic->getLogicalId()] = $rt2[0];
                                }
				afficheRouteTable( $routingTable );
                    ?>
                </div>

<!-- tab fin -->



            </div> <!-- div id="network-tab-content" class="tab-content" -->
        </div> <!-- div id="content" -->
    </div> <!-- div class="container-fluid" -->
</div> <!-- div class='network' nid='' id="div_templateNetwork" -->

<script type="text/javascript">
    <?php
        // for ( $i=1; $i<=config::byKey('zigateNb', 'Abeille', '1'); $i++ ) {
        //     echo '$(".btn.displayNodes'.$i.'")       .off("click").on("click", function () { displayNodes('.$i.'); });'."\n";
        //     echo '$(".btn.refreshNetworkCache'.$i.'").off("click").on("click", function () { refreshLQICache('.$i.'); displayNodes('.$i.'); });'."\n";
        // }

        $eqLogics = Abeille::byType('Abeille');

        echo '$(".btn.refreshBruitAll").off("click").on("click", function () { refreshBruit("All"); });'."\n";
        foreach ($eqLogics as $eqLogic) {
            if( $eqLogic->getConfiguration('localZigbeeChannelPower') ) {
                echo '$(".btn.refreshBruit_'.str_replace('/','',$eqLogic->getLogicalId()).'").off("click").on("click", function () { refreshBruit("'.$eqLogic->getLogicalId().'"); });'."\n";
            }
        }

        echo '$(".btn.refreshRoutesAll").off("click").on("click", function () { refreshRoutes("All"); });'."\n";
        foreach ($eqLogics as $eqLogic) {
            if( $eqLogic->getConfiguration('routingTable') ) {
                echo '$(".btn.refreshRoutes_'.str_replace('/','',$eqLogic->getLogicalId()).'").off("click").on("click", function () { refreshRoutes("'.$eqLogic->getLogicalId().'"); });'."\n";
            }
        }
    ?>
</script>

<?php include_file('desktop', 'network', 'js', 'Abeille'); ?>
