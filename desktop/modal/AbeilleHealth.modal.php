<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers debug features & PHP errors */
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    include_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    include_once __DIR__.'/../../core/php/AbeilleLog.php'; // logDebug()
    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
     */

    $eqLogics = Abeille::byType('Abeille');
?>
{{Démons}}:
<?php
    function displayDaemonStatus($diff, $name, &$oneMissing) {
        $nameLow = strtolower(substr($name, 0, 1)).substr($name, 1); // First char lower case
        if ($diff[$nameLow] == 1)
            echo '<span class="label label-success" style="font-size:1em; margin-left:4px">'.$name.'</span>';
        else {
            echo '<span class="label label-danger" style="font-size:1em; margin-left:4px">'.$name.'</span>';
            $oneMissing = true;
        }
    }

    $config = AbeilleTools::getParameters();
// logDebug("parameters=".json_encode($config));
    $running = AbeilleTools::getRunningDaemons();
    $diff = AbeilleTools::diffExpectedRunningDaemons($config, $running);
// logDebug("diff=".json_encode($diff));
    $oneMissing = false;
    displayDaemonStatus($diff, "Cmd", $oneMissing);
    displayDaemonStatus($diff, "Parser", $oneMissing);
    for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
        if ($config['ab::zgEnabled'.$zgId] != "Y")
            continue; // Zigate disabled
        displayDaemonStatus($diff, "SerialRead".$zgId, $oneMissing);
        if ($config['ab::zgType'.$zgId] == "WIFI")
            displayDaemonStatus($diff, "Socat".$zgId, $oneMissing);
    }
    if ($oneMissing)
        echo " Attention: Un ou plusieurs démons ne tournent pas !";

    /* Checking if active Zigates are not in timeout */
    echo "    Zigates: ";
    for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
        if ($config['ab::zgEnabled'.$zgId] != "Y")
            continue; // Zigate disabled

        $eqLogic = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
        if (!is_object($eqLogic))
            continue; // Abnormal
        if ((strtotime($eqLogic->getStatus('lastCommunication')) + (60 * $eqLogic->getTimeout())) > time()) {
            echo '<span class="label label-success" style="font-size:1em; margin-left:4px">Zigate'.$zgId.'</span>';
        } else {
            echo '<span class="label label-danger" style="font-size:1em; margin-left:4px">Zigate '.$zgId.'</span>';
        }
    }
    echo '<br><br>';

    // TODO Tcharp38: Display a popup for few sec as soon as ther is
    // an issue with daemons or zigate

    // if (Abeille::deamon_info()['state'] == 'ok') {
    //     echo "<span class=\"label label-success\" style=\"font-size:1em;\">OK</span>";
    // } else {
    //     echo "<span class=\"label label-danger\" style=\"font-size:1em;\">NOK</span>";
    //     echo "  Attention ! Un ou plusieurs démons ne tournent pas.";
    // }
?>

<table class="table table-condensed tablesorter" id="table_healthAbeille">
    <thead>
        <tr>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Réseau}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Equipement}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Type}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Adresse}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{IEEE}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Status}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Dernière comm.}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Depuis}} (H)</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{LQI}}</th>
            <th class="header" data-toggle="tooltip" title="{{Cliquez pour trier}}">{{Batterie}}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<script>
    function refreshHealth() {
        console.log("refreshHealth()");

        colors = ['lightcyan', 'lightblue', 'lightskyblue', 'gainsboro', 'ghostwhite', 'lightgrey	'];
        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
            data: {
                action: "getHealthDatas",
            },
            dataType: "json",
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'getHealthDatas' !");
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                // console.log("res=", res);
                equipments = res.eq; // equipments[net][addr] = object()
                console.log("equipments=", equipments);

                let tr = '';
                for (net in equipments) {
                    if (net == "") { // Just in case. Seen with bad Jeedom equipment
                        console.log("WARNING: Unexpected empty net ignored.");
                        continue;
                    }

                    zgId = parseInt(net.substring(7, 8)); // AbeilleX => X (integer)
                    netColor = colors[zgId];
                    console.log("net="+net+" => zgId="+zgId+", color="+netColor);
                    n = equipments[net];
                    parentBridge = equipments[net]['0000'];
                    bridgeEnabled = parentBridge.isEnabled;
                    for (addr in n) {
                        // console.log("LA2 addr=", addr);
                        e = n[addr];
                        if ((e.isEnabled == 0) || (bridgeEnabled == 0)) { // Equipment disabled or its parent bridge ?
                            dis1 = '<s>';
                            dis2 = '</s>';
                        } else {
                            dis1 = '';
                            dis2 = '';
                        }

                        tr += '<tr>';

                        // Network (AbeilleX)
                        // tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+net+dis2+'</span></td>';
                        tr += '<td><span style="font-size:1em;cursor:default;background-color:'+netColor+'";">'+dis1+net+dis2+'</span></td>';

                        // Device name
                        tr += '<td><a href="'+e.link+'" style="text-decoration: none;">'+dis1+e.hName+dis2+'</a></td>';

                        // Device type
                        // tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+e.type+dis2+'</span></td>';
                        tr += '<td><span style="font-size:1em;cursor:default;background-color:'+netColor+'">'+dis1+e.type+dis2+'</span></td>';

                        // Short address
                        // tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+addr+dis2+'</span></td>';
                        tr += '<td style="background-color:'+netColor+'"><span style="font-size:1em;cursor:default;">'+dis1+addr+dis2+'</span></td>';

                        // IEEE address
                        // tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+e.ieee+dis2+'</span></td>';
                        tr += '<td style="background-color:'+netColor+'"><span style="font-size:1em;cursor:default;">'+dis1+e.ieee+dis2+'</span></td>';

                        // Status: Updated every minutes by cron() (see Abeille.class.php)
                        if (e.isEnabled == 0) // Disabled ?
                            status = '<span class="label label-default" style="font-size: 1em; cursor: default;">{{Désactivé}}</span>';
                        else if (bridgeEnabled == 0) // Parent bridge disabled ?
                            status = '<span class="label label-default" style="font-size: 1em; cursor: default;">{{BR désactivé}}</span>';
                        else if (addr.substr(2) == "rc") // Remote control ?
                            status = '<span class="label label-success" style="font-size: 1em; cursor: default;">-</span>';
                        else if (e.timeout || (e.txAck == 'noack')) {
                            if (e.timeout && !e.noack)
                                s = "{{Time-out}}";
                            else if (!e.timeout && (e.txAck == 'noack'))
                                s = "{{No-ACK}}";
                            else
                                s = "{{Time-out}}/{{No-ACK}}";
                            status = '<span class="label label-danger" style="font-size: 1em; cursor: default;">' + s + '</span>';
                        } else
                            status = '<span class="label label-success" style="font-size: 1em; cursor: default;">{{OK}}</span>';
                        tr += '<td>'+status+'</td>';

                        // Last comm.
                        // lastComm = '<span class="label label-info" style="font-size: 1em; cursor: default;">';
                        lastComm = '<span style="font-size:1em;cursor:default;background-color:'+netColor+'">';
                        if (addr.substr(2) == "rc") // Remote control ?
                            lastComm += '-</span>';
                        else
                            lastComm += dis1+e.lastComm+dis2+'</span>';
                        tr += '<td>'+lastComm+'</td>';

                        // Time in H since last comm.
                        // since = '<span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+e.since+dis2+'</span>';
                        since = '<span style="font-size:1em;cursor:default;background-color:'+netColor+'">'+dis1+e.since+dis2+'</span>';
                        tr += '<td>'+since+'</td>';

                        // tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+e.lastLqi+dis2+'</span></td>';
                        tr += '<td><span style="font-size:1em;cursor:default;background-color:'+netColor+'">'+dis1+e.lastLqi+dis2+'</span></td>';

                        // tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+dis1+e.lastBat+dis2+'</span></td>';
                        tr += '<td style="background-color:'+netColor+'"><span style="font-size:1em;cursor:default">'+dis1+e.lastBat+dis2+'</span></td>';

                        tr += '</tr>';
                    }
                }

                $('#table_healthAbeille tbody').empty().append(tr);

                $("#table_healthAbeille").tablesorter().trigger('update');
            }
        });
    }

    refreshHealth();

    // setInterval(function () {
    //     refreshHealth();
    // }, 2000);

    // $(function() {
    //     $("#table_healthAbeille").tablesorter();
    // });

</script>
