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
            if (config::byKey('ab::gtwEnabled'.$zgId, 'Abeille', 'N') != 'Y')
                continue; // Disabled

            if ($what == "noise")
                echo '<span style="margin-left:4px">Abeille'.$zgId.'</span>';
            else if ($what == "linksTable")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayLinksTable('.$zgId.')">Abeille'.$zgId.'</a>';
            else if ($what == "lqiPath")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="loadLqiPathData('.$zgId.')">Abeille'.$zgId.'</a>';
            else if ($what == "routeA2B")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="loadRouteA2BData('.$zgId.')">Abeille'.$zgId.'</a>';
            else if ($what == "routes")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayRoutesTable('.$zgId.')">Abeille'.$zgId.'</a>';
            else if ($what == "bindings")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayBindingsTable('.$zgId.')">Abeille'.$zgId.'</a>';
            else if ($what == "clusters")
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayClustersTable('.$zgId.')">Abeille'.$zgId.'</a>';
            else // "linksGraph"
                echo '<a class="btn btn-success" style="margin-left:4px" onclick="displayLinksGraph('.$zgId.')">Abeille'.$zgId.'</a>';
            if ($what == "bindings")
                echo '<a class="btn btn-warning" title="Interroge les tables de liaison de tous les équipements toujours à l\'écoute de ce réseau. Un équipement endormi doit être réveillé puis interrogé seul." onclick="collectBindings('.$zgId.', [])"><i class="fas fa-sync"></i></a>';
            else if ($what == "clusters")
                echo '<a class="btn btn-warning" title="Interroge les clusters de tous les équipements toujours à l\'écoute de ce réseau. Un équipement endormi doit être réveillé puis interrogé seul." style="margin-left:4px" onclick="readClusters('.$zgId.')"><i class="fas fa-sync"></i></a>';
            else if ($what == "routes" || $what == "routeA2B")
                echo '<a class="btn btn-warning" title="Interroge les tables de routage des routeurs de ce réseau. Peut prendre plusieurs minutes en fonction du nombre d\'équipements." onclick="refreshRoutes('.$zgId.', \''.$what.'\')"><i class="fas fa-sync"></i></a>';
            else if ($what == "noise")
                echo '<a class="btn btn-warning" title="Demande aux équipements de ce réseau de mesurer le bruit sur les canaux 11 à 26." style="margin-left:4px" onclick="refreshNoise(\'Abeille'.$zgId.'\')"><i class="fas fa-sync"></i></a>';
            else
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
    body, html {
        height: 100%;
    }



    /* #idLinksGraphTab {
        height: 80%;
        width: 90%;
        position: absolute;
    } */

    #idLinksGraphTabSVG svg {
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

    .ab-container {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .ab-container-top {
        display: flex;
    }
    .ab-container-bottom {
        display: flex;
        flex-basis: 100%;
        height: 100%;
        margin-top: 8px;
    }
    .tab-pane {
        display: none;
        flex-direction: row;
        flex-basis: 100%;
    }
    #idLinksTableTab {
        flex-direction: column;
    }
    .tab-content > .active {
        display: flex;
    }
    #idLinksGraphTabSVG {
        height: 100%;
        width: 100%;
    }
</style>

<link rel="stylesheet" href="/3rdparty/jquery.tablesorter/jquery.tablesorter.pager.min.css">
<script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/vivagraph/vivagraph.min.js"></script>

<!-- Area to display alert message -->
<div id='div_networkZigbeeAlert' style="display: none;"></div>

<div class="ab-container">
    <!-- Tabs entries -->
    <div class="ab-container-top">
        <ul class="nav nav-tabs">
            <li class="active">
                <a data-toggle="tab" href="#idLinksTableTab"> {{Table des liens}}</a>
            </li>
            <li>
                <a data-toggle="tab" href="#idLinksGraphTab"> {{Graphique des liens}}</a>
            </li>
            <li>
                <a data-toggle="tab" href="#idRoutesTab"> {{Routes}}</a>
            </li>
            <!-- <li>
                <a data-toggle="tab" href="#idNoise"> {{Bruit}}</a>
            </li> -->
            <li>
                <a data-toggle="tab" href="#idRouteA2BTab"> {{Route A→B}}</a>
            </li>
            <li>
                <a data-toggle="tab" href="#idLqiPathTab"> {{LQI Path}}</a>
            </li>
            <li>
                <a data-toggle="tab" href="#idBindingsTab"> {{Binding}}</a>
            </li>
            <li>
                <a data-toggle="tab" href="#idClustersTab"> {{Clusters}}</a>
            </li>
        </ul>
    </div>

    <div class="tab-content ab-container-bottom">
        <div class="tab-pane active" id="idLinksTableTab">
            <br />
            {{Noeuds connus du réseau et Link Quality Indicator}}. {{Informations remises-à-jour une fois par jour}}.
            <!-- < ?php
            echo '{{Noeuds connus du réseau et LQI (<a href="'.urlUserMan.'/Radio.html" target="_blank">Link Quality Indicator</a>) associé. Informations remises-à-jour une fois par jour.}}<br />';
            ?> -->
            <br />
            <br />
            <div id="div_routingTable">
                <?php
                    displayNetworks(maxGateways, "linksTable");
                ?>
                <br />
                <!-- <hr> -->
                Actuel : <span id="idCurrentNetworkLT" style="width:150px; font-weight:bold">-</span>, collecte du <span id="idCurrentDateLT" style="width:150px; font-weight:bold">-</span>
                <!-- <br />
                <br /> -->

                <!-- Tcharp38: Hidden since does not work and no time to spend on.
                <label class="control-label" data-toggle="tooltip" title="Filtre par routeur">{{Routeur}}</label>
                <select class="filter" id="nodeFrom"> </select>
                <label class="control-label" data-toggle="tooltip" title="Filtre par noeuds voisins">{{Voisin}}</label>
                <select class="filter" id="nodeTo"> </select> -->

                <br />
                <br />

                <div style="margin-bottom:8px;">
                    <input type="text" id="idLinksTableSearch" class="form-control" placeholder="{{Filtrer par équipement...}}" style="width:280px; display:inline-block;">
                    <button onclick="$('#idLinksTableSearch').val('').trigger('input');" class="btn btn-default btn-sm" style="margin-left:4px;" title="{{Effacer}}"><i class="fas fa-times"></i></button>
                </div>

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
        </div> <!-- End links table -->

        <div class="tab-pane" id="idLinksGraphTab">
            <div class="ab-container-left">
                <?php
                    displayNetworks(maxGateways, "linksGraph", "column");
                ?>
                <br>
                Actuel :
                <br />
                <span id="idCurrentNetworkLG" style="width:150px; font-weight:bold"></span>
                <br />
                <br />
                Collecte :
                <br />
                <span id="idCurrentDateLG" style="width:150px; font-weight:bold">-</span>

                <br />
                <br />
                Afficher :
                <label class="checkbox-inline"><input type="checkbox" id="idShowObject" checked/>Objet parent</label>
                <br />
                <br />

                <!-- <table class="table table-bordered table-condensed" style="width: 150px;position:fixed;margin-top : 25px;"> -->
                <table class="table table-bordered table-condensed" style="width: 150px">
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
                        <td>{{Type inconnu}}</td>
                    </tr>
                    <tr>
                        <td style="width: 35px"><img src="/plugins/Abeille/images/death.png" height="30px"></td>
                        <td>{{Sans vie}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div id="idLinksGraphTabSVG">
            </div>
        </div>

        <div class="tab-pane" id="idRoutesTab">
            <div style="padding:10px">
                <div style="margin-bottom:10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                    <?php displayNetworks(maxGateways, "routes"); ?>
                </div>
                <p style="color:#888; margin-bottom:10px;">{{Firmware Zigate ≥ 3.1d requis}}</p>
                <div id="idRoutesTableContent">
                    <div class="alert alert-info">{{Sélectionnez un réseau pour afficher ses routes.}}</div>
                </div>
            </div>
        </div>


        <div class="tab-pane" id="idRouteA2BTab">
            <div style="padding:10px">
                <div style="margin-bottom:10px">
                    <?php displayNetworks(maxGateways, "routeA2B"); ?>
                </div>
                <div style="margin-bottom:12px; display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                    <div>
                        <label class="control-label">{{De :}}</label><br>
                        <select id="idRouteA2BFrom" class="form-control" style="width:240px;"></select>
                    </div>
                    <div>
                        <label class="control-label">{{À :}}</label><br>
                        <select id="idRouteA2BTo" class="form-control" style="width:240px;"></select>
                    </div>
                    <div style="padding-top:20px">
                        <button id="idRouteA2BCompute" class="btn btn-primary"><i class="fas fa-route"></i> {{Calculer}}</button>
                    </div>
                </div>
                <div id="idRouteA2BResults"></div>
            </div>
        </div>
        <div class="tab-pane" id="idLqiPathTab">
            <div style="padding:10px">
                <div id="div_lqiPathAlert" style="display:none"></div>
                <div style="margin-bottom:10px">
                    <?php displayNetworks(maxGateways, "lqiPath"); ?>
                </div>
                <div style="margin-bottom:12px; display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                    <div>
                        <label class="control-label">{{De :}}</label>
                        <select id="idLqiPathFrom" class="form-control" style="width:220px; display:inline-block;"></select>
                    </div>
                    <div>
                        <label class="control-label">{{À :}}</label>
                        <select id="idLqiPathTo" class="form-control" style="width:220px; display:inline-block;"></select>
                    </div>
                    <div>
                        <label class="control-label">{{LQI min :}}</label>
                        <input type="number" id="idLqiPathMinLqi" class="form-control" value="50" min="0" max="255" style="width:80px; display:inline-block;">
                    </div>
                    <div style="padding-top:4px">
                        <button id="idLqiPathCompute" class="btn btn-primary"><i class="fas fa-route"></i> {{Calculer}}</button>
                    </div>
                </div>
                <div id="idLqiPathResults"></div>
            </div>
        </div>

        <div class="tab-pane" id="idBindingsTab">
            <div style="padding:10px">
                <div id="div_bindingsAlert" style="display:none"></div>
                <div style="margin-bottom:10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                    <?php displayNetworks(maxGateways, "bindings"); ?>
                </div>
                <p style="color:#888; margin-bottom:10px;">{{Table de liaison (binding) de chaque équipement : vers où il envoie ses rapports et commandes. Une liaison vers le coordinateur d'un autre réseau est obsolète (équipement déplacé) : l'équipement cherche cette destination par diffusion à chaque envoi, ce qui charge tout le réseau.}}</p>
                <div id="idBindingsContent">
                    <div class="alert alert-info">{{Sélectionnez un réseau pour afficher ses liaisons.}}</div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="idClustersTab">
            <div style="padding:10px; width:100%">
                <div id="div_clustersAlert" style="display:none"></div>
                <div style="margin-bottom:10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                    <a class="btn btn-success" onclick="displayClustersTable(0)">{{Tous les réseaux}}</a>
                    <?php displayNetworks(maxGateways, "clusters"); ?>
                    <input id="idClustersFilter" class="form-control" style="width:220px" placeholder="{{Filtrer les équipements}}" oninput="filterClustersTable()">
                </div>
                <p style="color:#888; margin-bottom:10px;">{{Services de chaque point d'accès, tels que l'équipement les déclare : S = serveur, C = client, ? = clients pas encore lus. Un équipement endormi doit être réveillé juste avant d'appuyer sur son bouton.}}</p>
                <div id="idClustersContent">
                    <div class="alert alert-info">{{Sélectionnez un réseau pour afficher ses clusters.}}</div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="idNoise">
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
            A noter: Apres un appui sur le bouton orange d un reseau, il n y a pas encore d affichage d avancement du processus.<br />
            Attendez et rafraichissez la page au bout d un certain temps. Il faut compter 5s par équipement...<br />
            Si une mesure a ete faite pour un equipement le graph doit apparaitre.<br />
            Attention l heure de mesure n est pas encore dispo. Donc vous pouvez avoir une vieille mesure a l ecran.<br />
            <br />
            Attention: Apres un "Collecter" ou un appui sur le bouton orange il faut rafraichir la page pour mettre a jour les graphiques.<br />

            <?php
                echo '<div style="margin-bottom:10px">'; displayNetworks(maxGateways, "noise"); echo '</div>';

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
                                echo '<br /><a data-action="refreshNoise_'.str_replace('/','',$logicalId).'" class="btn btn-success refreshNoise_'.str_replace('/','',$logicalId).'"><i class="fas fa-sync" ></i>{{Collecter}}</a><br /><br />';

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
    </div>
</div>

<script type="text/javascript">
    <?php
        // for ( $i=1; $i<=config::byKey('zigateNb', 'Abeille', '1'); $i++ ) {
        //     echo '$(".btn.displayLinksTable'.$i.'")       .off("click").on("click", function () { displayLinksTable('.$i.'); });'."\n";
        //     echo '$(".btn.refreshNetworkCache'.$i.'").off("click").on("click", function () { refreshLqiTable('.$i.'); displayLinksTable('.$i.'); });'."\n";
        // }

        $eqLogics = Abeille::byType('Abeille');

        foreach ($eqLogics as $eqLogic) {
            if( $eqLogic->getConfiguration('localZigbeeChannelPower') ) {
                echo '$(".btn.refreshNoise_'.str_replace('/','',$eqLogic->getLogicalId()).'").off("click").on("click", function () { refreshNoise("'.$eqLogic->getLogicalId().'"); });'."\n";
            }
        }
    ?>
</script>

<?php include_file('desktop', 'AbeilleNetwork', 'js', 'Abeille'); ?>
