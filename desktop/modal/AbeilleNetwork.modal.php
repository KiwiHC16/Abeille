<?php
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    require_once __DIR__.'/../../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
    }

    /* Add network display & refresh buttons for all active zigates */
    function displayNetworks($nbOfZigates, $what="linksTable", $mode="") {
        echo 'Réseau :';
        if ($mode == "column")
            echo '<br>';
        for ($zgId = 1; $zgId <= $nbOfZigates; $zgId++) {
            if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') != 'Y')
                continue; // Disabled

            if ($what == "linksTable")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayLinksTable('.$zgId.')">Abeille'.$zgId.'</a>';
            else // "linksGraph"
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayLinksGraph('.$zgId.')">Abeille'.$zgId.'</a>';
            echo '<a class="btn btn-warning" title="Forçe la réinterrogation du réseau. Peut prendre plusieurs minutes en fonction du nombre d\'équipements." onclick="refreshLqiTable('.$zgId.', \''.$what.'\')"><i class="fas fa-sync"></i></a>';
            echo '&nbsp;&nbsp;';
            if ($mode == "column")
                echo '<br>';
        }
    }

    // Last Demon start time
    $startTime = config::byKey('lastDeamonLaunchTime', 'Abeille', '{{Demon Jamais lancé}}');

    // Status demon
    $status = "<i class=\"fas fa-circle fa-lg rediconcolor\"></i>{{Plugin désactivé et démon non configuré}}";
    if (config::byKey('active', 'Abeille', '0') == 1) {
        if (Abeille::deamon_info()['state'] == 'ok') {
            $status = "<i class=\"fas fa-circle fa-lg greeniconcolor\"></i>{{Plugin activé et démon configuré}}";
        } else {
            $status = "<i class=\"fas fa-circle fa-lg rediconcolor\"></i>{{Plugin activé mais démon non configué}}";
        }
    }

    $eqLogics = eqLogic::byType('Abeille');

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

    sendVarToJS('nodesFromJeedom', $nodes);
?>

<style>
    #idLinksGraphTab {
        height: 80%;
        width: 90%;
        position: absolute;
    }

    #idLinksGraphTabSVG >svg {
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

    .filter {
        width: 140px
    }
</style>

<link rel="stylesheet" href="/3rdparty/jquery.tablesorter/jquery.tablesorter.pager.min.css">
<script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/vivagraph/vivagraph.min.js"></script>

<!-- Area to display alert message -->
<div id='div_networkZigbeeAlert' style="display: none;"></div>

<div class='network' nid='' id="div_templateNetwork">
    <div class="container-fluid">
        <div id="content">

            <ul id="tabs_network" class="nav nav-tabs" data-tabs="tabs">
                <li class="active" id="tab_nodes"> <a href="#idLinksTableTab" data-toggle="tab"> <i class="fas fa-table">     </i> {{Table des liens}}    </a></li>
                <li                id="tab_graph"> <a href="#idLinksGraphTab" data-toggle="tab"> <i class="far fa-image"> </i> {{Graphique des liens}}</a></li>
                <li                id="tab_routes"><a href="#idRoutesTab"     data-toggle="tab"> <i class="fas fa-tachometer-alt"></i> {{Routes}}             </a></li>
                <?php
                if (isset($dbgDeveloperMode))
                    echo '<li                id="tab_test1"> <a href="#test1"        data-toggle="tab"> <i class="fas fa-tachometer-alt"></i> {{Bruit}}              </a></li>';
                ?>
            </ul>

            <div id="network-tab-content" class="tab-content">

                <!-- Links table -->
                <div id="idLinksTableTab" class="tab-pane active">
                    <br />
                    <?php
                    echo '{{Noeuds connus du réseau et LQI (<a href="'.urlUserMan.'/Radio.html" target="_blank">Link Quality Indicator</a>) associé. Informations remises-à-jour une fois par jour.}}<br />';
                    ?>
                    <br />
                    <div id="div_routingTable">
                        <?php
                            displayNetworks(maxNbOfZigate, "linksTable");
                        ?>
                        <br />
                        <hr>
                        Actuel : <span id="idCurrentNetworkLT" style="width:150px; font-weight:bold">-</span>, collecte du <span id="idCurrentDateLT" style="width:150px; font-weight:bold">-</span>
                        <br />
                        <br />

                        <!-- Tcharp38: Hidden since does not work and no time to spend on.
                        <label class="control-label" data-toggle="tooltip" title="Filtre par routeur">{{Routeur}}</label>
                        <select class="filter" id="nodeFrom"> </select>
                        <label class="control-label" data-toggle="tooltip" title="Filtre par noeuds voisins">{{Voisin}}</label>
                        <select class="filter" id="nodeTo"> </select> -->

                        <br />
                        <br />

                        <table class="table table-condensed tablesorter" id="idLinksTable">
                            <thead>
                                <tr>
                                    <th class="header" data-toggle="tooltip" colspan=3>{{Routeur}}</th>
                                    <th class="header" data-toggle="tooltip" colspan=4>{{Voisinage}}</th>
                                    <th class="header" data-toggle="tooltip" colspan=3>{{Relation}}</th>
                                </tr>
                                <tr>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Objet}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Nom}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Adresse}}</th>

                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Objet}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Nom}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Adresse}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Type}}</th>

                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Relation}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{Profondeur}}</th>
                                    <th class="header" data-toggle="tooltip" title="Trier par">{{LQI}}</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Links graph -->
                <div id="idLinksGraphTab" class="tab-pane">

                    <div class="col-lg-2" style="height:inherit;overflow-y:auto;overflow-x:hidden;">
                        <?php
                            displayNetworks(maxNbOfZigate, "linksGraph", "column");
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
                            <td class="typeCoordinator-color" style="width: 35px"><i class="fas fa-square fa-2x"></i></td>
                            <td>{{Coordinateur}}</td>
                        </tr>
                        <tr>
                            <td class="typeRouter-color" style="width: 35px"><i class="fas fa-square fa-2x"></i></td>
                            <td>{{Routeur}}</td>
                        </tr>
                        <tr>
                            <td class="typeEndDevice-color" style="width: 35px"><i class="fas fa-square fa-2x"></i></td>
                            <td>{{Bout de chaine}}</td>
                        </tr>
                        <tr>
                            <td class="typeUndefined-color" style="width: 35px"><i class="fas fa-square fa-2x"></i></td>
                            <td>{{Inconnu}}</td>
                        </tr>
                        </tbody>
                        </table>
                    </div>

                    <div class="col-lg-10" style="height:100%;overflow-y:auto;overflow-x:hidden;">
                        Actuel :<span id="idCurrentNetworkLG" style="width:150px; font-weight:bold">-</span>, collecte du <span id="idCurrentDateLG" style="width:150px; font-weight:bold">-</span>
                        <br />
                        Afficher :<label class="checkbox-inline"><input type="checkbox" id="idShowObject" checked/>Objet parent</label>
                        <div id="idLinksGraphTabSVG" style="height:inherit">
                        </div>
                    </div>
                </div>

                <!-- tab Route -->
                <div id="idRoutesTab" class="tab-pane" >
                    <?php
                        echo '<a class="btn btn-success refreshRoutesAll"><i class="fas fa-sync" ></i>{{Tout collecter}}</a><br /><br />';
                        echo 'Il faut un firmware zigate au moins en version 3.1d<br /><br />';

                        function afficheRouteTable( $routingTable ) {
                            foreach ( $routingTable as $addr=>$route ) {
                                list( $zigate, $addrShort ) = explode ( '/', $addr );
                                // if ( $addrShort == '0000' ) $addrShort = 'Ruche';
                                foreach ( $route as $destination=>$nextHop ) {
                                    // if ( $destination == '0000' ) $destination = 'Ruche';
                                    // if ( $nextHop == '0000' )     $nextHop = 'Ruche';

                                    $sourceEq       = Abeille::byLogicalId($zigate.'/'.$addrShort, 'Abeille');
                                    $destinationEq  = Abeille::byLogicalId($zigate.'/'.$destination, 'Abeille');
                                    $nextHopEq      = Abeille::byLogicalId($zigate.'/'.$nextHop, 'Abeille');

                                    if (!is_object($sourceEq)) continue;
                                    if (!is_object($destinationEq)) continue;
                                    if (!is_object($nextHopEq)) continue;

                                    $srcParent = $sourceEq->getObject();
                                    if ($srcParent)
                                        $srcParentName = $srcParent->getName();
                                    else
                                        $srcParentName = '';
                                    $dstParent = $destinationEq->getObject();
                                    if ($dstParent)
                                        $dstParentName = $dstParent->getName();
                                    else
                                        $dstParentName = '';
                                    $nxtHopeParent = $nextHopEq->getObject();
                                    if ($nxtHopeParent)
                                        $nxtHopeParentName = $nxtHopeParent->getName();
                                    else
                                        $nxtHopeParentName = '';
                                    echo 'Si '.$srcParentName.'-'.$sourceEq->getName().' ('.$sourceEq->getLogicalId()
                                        . ') veut joindre '.$dstParentName.'-'.$destinationEq->getName().' ('.$destinationEq->getLogicalId()
                                        . ') passera par '.$nxtHopeParentName.'-'.$nextHopEq->getName().' ('.$nextHopEq->getLogicalId()
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

                <!-- tab Bruit -->
                <div id="test1" class="tab-pane" >
                    VISIBLE EN MODE DEV UNIQUEMENT !<br><br>
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
                        echo '<a class="btn btn-success refreshBruitAll"><i class="fas fa-sync" ></i>{{Tout collecter}}</a><br /><br />';

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
                                        echo '<br /><a data-action="refreshBruit_'.str_replace('/','',$logicalId).'" class="btn btn-success refreshBruit_'.str_replace('/','',$logicalId).'"><i class="fas fa-sync" ></i>{{Collecter}}</a><br /><br />';

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
            </div> <!-- div id="network-tab-content" class="tab-content" -->
        </div> <!-- div id="content" -->
    </div> <!-- div class="container-fluid" -->
</div> <!-- div class='network' nid='' id="div_templateNetwork" -->

<script type="text/javascript">
    <?php
        // for ( $i=1; $i<=config::byKey('zigateNb', 'Abeille', '1'); $i++ ) {
        //     echo '$(".btn.displayLinksTable'.$i.'")       .off("click").on("click", function () { displayLinksTable('.$i.'); });'."\n";
        //     echo '$(".btn.refreshNetworkCache'.$i.'").off("click").on("click", function () { refreshLqiTable('.$i.'); displayLinksTable('.$i.'); });'."\n";
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

<?php include_file('desktop', 'AbeilleNetwork', 'js', 'Abeille'); ?>
