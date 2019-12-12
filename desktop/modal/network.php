<?php
    
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    
    // Last Demon start time
    $startTime = config::byKey('lastDeamonLaunchTime', 'Abeille', '{{Demon Jamais lancé}}');
    
    // Status demon
    $status = "<i class=\"fa fa-circle fa-lg rediconcolor\"></i> Plugin désactivé et démon non configuré";
    if (config::byKey('active', 'Abeille', '0') == 1) {
        if (Abeille::deamon_info()['state'] == 'ok') {
            $status = "<i class=\"fa fa-circle fa-lg greeniconcolor\"></i> Plugin activé et démon configuré";
        } else {
            $status = "<i class=\"fa fa-circle fa-lg rediconcolor\"></i> Plugin activé mais démon non configué";
        }
    }
    
    // Chemin vers les zigates
    $usbPath  = config::byKey('AbeilleSerialPort',  'Abeille', '{{Inconnu}}');
    $usbPath2 = config::byKey('AbeilleSerialPort2', 'Abeille', '{{Inconnu}}');
    $usbPath3 = config::byKey('AbeilleSerialPort3', 'Abeille', '{{Inconnu}}');
    $usbPath4 = config::byKey('AbeilleSerialPort4', 'Abeille', '{{Inconnu}}');
    $usbPath5 = config::byKey('AbeilleSerialPort5', 'Abeille', '{{Inconnu}}');
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
<link rel="stylesheet" href="/3rdparty/font-awesome5/css/font-awesome.min.css">
<link rel="stylesheet" href="/3rdparty/jquery.tablesorter/jquery.tablesorter.pager.min.css">
<script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/vivagraph/vivagraph.min.js"></script>
<!--script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/jquery.tablesorter/jquery.tablesorter.min.js"></script-->
<!--script type="text/javascript" src="/core/php/getResource.php?file=plugins/Abeille/3rdparty/vivagraph/vivagraph.min.js"></script-->


<div id='div_networkZigbeeAlert' style="display: none;"></div>

<div class='network' nid='' id="div_templateNetwork">
    <div class="container-fluid">
        <div id="content">
            <ul id="tabs_network" class="nav nav-tabs" data-tabs="tabs">
                <li class="active"> <a href="#summary_network"                          data-toggle="tab"> <i class="fa fa-tachometer">  </i> {{Résumé}}                </a></li>
                <li id="tab_graph"> <a href="#graph_network"                            data-toggle="tab"> <i class="fa fa-picture-o">   </i> {{Graphique du réseau}}   </a></li>
                <li id="tab_route"> <a href="#route_network"                            data-toggle="tab"> <i class="fa fa-table">       </i> {{Table des noeuds}}      </a></li>
            </ul>

            <div id="network-tab-content" class="tab-content">
                <div class="tab-pane active" id="summary_network">
                    <br>
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
                            <p>{{Chemin du contrôleur Zigbee :}} <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;"><?php echo $usbPath.", ".$usbPath2.", ".$usbPath3.", ".$usbPath4.", ".$usbPath5 ?></span> </p>
                            <p>{{Nombre de démons lancés :}} <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;"><?php echo $nbDaemons ?></span> </p>
                        </div>
                    </div>
                </div>





                <div id="graph_network" class="tab-pane">
                    <table class="table table-bordered table-condensed"
                           style="width: 700px;position:fixed;margin-top : 25px;">
                        <thead>
                        <tr>
                            <th colspan="2">{{Légende}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="typeCoordinator-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>Coordinateur</td>

                            <td class="typeAlert-color" style="width: 35px"><i class="fa fa-ambulance fa-2x"></i></td>
                            <td>Warning: chaque nom d objet doit être unique<a href="https://github.com/KiwiHC16/Abeille/issues/458">(voir ici)</a></td>
                        </tr>
                        <tr>
                            <td class="typeEndDevice-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>Bout de chaine</td>
                        </tr>
                        <tr>
                            <td class="typeRouter-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>Routeur</td>
                        </tr>
                        <tr>
                            <td class="typeUndefined-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td>
                            <td>Inconnu</td>
                        </tr>

                        </tbody>
                    </table>
                    <span id="graph-node-name" style="width: 100%;height: 100%"></span>
                </div>





                <div id="route_network" class="tab-pane">
                    <br/>
                    <div id="div_routingTable">
                        <span>
                            <span class="" style="padding: 3px 20px;">
                                <a data-action="refreshNetworkCache" class="btn btn-success refreshCache"><i class="fa fa-refresh" ></i>Get LQI</a>
                            </span>
                            Get LQI permet de lancer l interrogation des équipements pour avoir les information <a href="http://kiwihc16.free.fr/Radio.html" target="_blank">Link Quality Indicator (LQI)</a><br><hr>

                            <label class="control-label" data-toggle="tooltip" title="Filtre les nodes par emetteur">Source </label>
                            <select class="filterSource" id="nodeFrom"> </select>

                            <label class="control-label" data-toggle="tooltip" title="Filtre les nodes par destinataire">Destinataire </label>
                            <select class="filterRecipient" id="nodeTo"> </select>

                        </span>

                        <table class="table table-condensed tablesorter" id="table_routingTable">
                            <thead>
                            <tr>
                                <th class="header">{{ID}}</th>
                                <th class="header">{{Name}}</th>
                                <th class="header">{{Voisine}}</th>
                                <th class="header">{{Voisine_Name}}</th>
                                <th class="header">{{Relation}}</th>
                                <th class="header">{{Profondeur}}</th>
                                <th class="header">{{LQI }}</th>
                                <th class="header">{{Type }}</th>
                            </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'network', 'js', 'Abeille'); ?>
