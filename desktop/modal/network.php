<?php

if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}

$startTime = config::byKey('lastDeamonLaunchTime', 'Abeille', '{{Jamais lancé}}');
$usbPath= config::byKey('', 'Abeille', '{{Jamais lancé}}');
$status = "<i class=\"fa fa-circle fa-lg rediconcolor\"></i> Plugin désactivé et démon non configuré";
if (config::byKey('active', 'Abeille', '0')==1){
    if(config::byKey('state', 'Abeille', '0')){
        $status="<i class=\"fa fa-circle fa-lg greeniconcolor\"></i> Plugin activé et démon configuré";
    }
    else {
        $status="<i class=\"fa fa-circle fa-lg rediconcolor\"></i> Plugin activé mais démon non configué";
    }
}


$neighbors="";
$color = (Abeille::serviceMosquittoStatus()['launchable']=='ok')?"greeniconcolor":"redicolor";
$mosquitto="<i class=\"fa fa-circle fa-lg ".$color."\"></i>";
$usbPath=config::byKey('AbeilleSerialPort', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');
$nodes = count($eqLogics);
foreach ($eqLogics as $eqLogic) {
    $neighbors .= $eqLogic->getName().", ";
}
$neighbors = substr($neighbors,0,-2);

$nbProcess = count(system::ps("AbeilleDeamon","true"));
if ( config::byKey('onlyTimer', 'Abeille')=='N' ) {
    $color= ($nbProcess == 4)?"greeniconcolor":"redicolor";
    $nbDaemons= "<i class=\"fa fa-circle fa-lg ".$color."\"></i> ".$nbProcess."/4";
}
else {
    $color= ($nbProcess == 1)?"greeniconcolor":"redicolor";
    $nbDaemons= "<i class=\"fa fa-circle fa-lg ".$color."\"></i> ".$nbProcess."/1";
}

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
    .typeCoordinator-color{
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
<link rel="stylesheet" href="/3rdparty/font-awesome/css/font-awesome.min.css">
<!--script type="text/javascript" src="/core/php/getResource.php?file=3rdparty/vivagraph/vivagraph.min.js"></script-->
<script type="text/javascript" src="/core/php/getResource.php?file=plugins/Abeille/3rdparty/vivagraph/vivagraph.min.js"></script>


<div id='div_networkZigbeeAlert' style="display: none;"></div>
<div class='network' nid='' id="div_templateNetwork">
    <div class="container-fluid">
        <div id="content">
            <ul id="tabs_network" class="nav nav-tabs" data-tabs="tabs">
                <li class="active"><a href="#summary_network" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Résumé}}</a></li>
                <li><a href="#actions_network"  style="display:none;" data-toggle="tab"><i class="fa fa-sliders"></i> {{Actions}}</a></li>
                <li><a href="#statistics_network"  style="display:none;" data-toggle="tab"><i class="fa fa-bar-chart"></i> {{Statistiques}}</a></li>
                <li id="tab_graph"><a href="#graph_network" data-toggle="tab"><i class="fa fa-picture-o"></i> {{Graphique du réseau}}</a></li>
                <li id="tab_route" style="display:none;"><a href="#route_network" data-toggle="tab"><i class="fa fa-table"></i> {{Table de routage}}</a></li>
            </ul>
            <div id="network-tab-content" class="tab-content">
                <div class="tab-pane active" id="summary_network">
                    <br>
                    <div class="panel panel-primary">
                        <div class="panel-heading"><h4 class="panel-title">{{Informations}}</h4></div>
                        <div class="panel-body">
                            <p>{{Réseau démarré le}} <span class="zigBNetworkAttr label label-default" style="font-size : 1em;" data-l1key="startTime"><?php echo $startTime ?></span> <span class="zigBNetworkAttr label label-default" data-l1key="awakedDelay"  style="font-size : 1em;"></span></p>
                            <p>{{Le réseau contient}} <b><span class="zigBNetworkAttr" data-l1key="nodesCount"></span><?php echo $nodes ?></b> {{noeuds}}</p>
                            <p>{{Voisins :}}<span class="zigBNetworkAttr label label-default" data-l1key="neighbors" style="font-size : 1em;"><?php echo $neighbors ?></span></p>
                        </div>
                    </div>
                    <div class="panel panel-primary">
                        <div class="panel-heading"><h4 class="panel-title">{{Etat}}</h4></div>
                        <div class="panel-body">
                            <p><span class="zigBNetworkAttr" data-l1key="state"></span> {{Etat actuel :}} <span class="zigBNetworkAttr label label-default" data-l1key="stateDescription" style="font-size : 1em;"><?php echo $status ?></span></p>
                        </div>
                    </div>
                    <div class="panel panel-primary"  style="display:none;">
                        <div class="panel-heading"><h4 class="panel-title">{{Capacités}}</h4></div>
                        <div class="panel-body"><lu style="font-size : 1em;"><span class="zigBNetworkAttr" data-l1key="node_capabilities" style="font-size : 1em;"></span></lu></div>
                    </div>
                    <div class="panel panel-primary" >
                        <div class="panel-heading"><h4 class="panel-title">{{Système}}</h4></div>
                        <div class="panel-body">
                            <p>{{Chemin du contrôleur Zigbee :}} <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;"><?php echo $usbPath ?></span></p>
                            <p>{{Service mosquitto démarré :}} <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;"><?php echo $mosquitto ?></span></p>
                            <p>{{Nombre de démons lancés :}} <span class="zigBNetworkAttr label label-default" data-l1key="" style="font-size : 1em;"><?php echo $nbDaemons ?></span></p>

                        </div>
                    </div>
                </div>
                <div id="graph_network" class="tab-pane">
                    <table class="table table-bordered table-condensed" style="width: 350px;position:fixed;margin-top : 25px;">
                        <thead><tr><th colspan="2">{{Légende}}</th></tr></thead>
                        <tbody>
                        <tr>
                            <td class="typeCoordinator-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Coordinateur</td>
                        </tr>
                        <tr>
                            <td class="typeEndDevice-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Bout de chaine</td>
                        </tr>
                        <tr><td class="typeRouter-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Routeur</td>
                        </tr>
                        <tr>
                            <td class="typeUndefined-color" style="width: 35px"><i class="fa fa-square fa-2x"></i></td><td>Inconnu</td>
                        </tr>
                        </tbody>
                    </table>
                    <span id="graph-node-name" style="width: 100%;height: 100%"></span>
                </div>
                <div id="route_network" class="tab-pane" style="display:none">
                    <br/>
                    <div id="div_routingTable"></div>
                    <table class="table table-bordered table-condensed" style="width: 500px;">
                        <thead><tr><th colspan="2">{{Légende}}</th></tr></thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <div class="tab-pane" id="actions_network" style="display:none">
                    <table class="table">

                    </table>
                </div>
                <div class="tab-pane" id="statistics_network" style="display:none">
                    <table class="table table-condensed table-striped">
                        <tr>
                            <td><b>{{Nombre d'émissions lues :}}</b></td>
                            <td><span class="zigBNetworkAttr" data-l1key="controllerStatistics" data-l2key="broadcastReadCnt"></span></td>
                        </tr>
                        <tr>
                            <td><b>{{Nombre d'émissions envoyées :}}</b></td>
                            <td><span class="zigBNetworkAttr" data-l1key="controllerStatistics" data-l2key="broadcastWriteCnt"></span></td>
                        </tr>
                     </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include_file('desktop', 'network', 'js', 'Abeille');?>
