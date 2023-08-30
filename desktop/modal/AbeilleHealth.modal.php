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
Démons:
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
    echo "  Zigates: ";
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
            <th class="header" data-toggle="tooltip" title="Trier par">{{Réseau}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{Equipement}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{Type}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{Adresse}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{IEEE}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{Status}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{Dernière comm.}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par">{{Depuis}} (H)</th>
            <th class="header" data-toggle="tooltip" title="Trier par dernier LQI">{{LQI}}</th>
            <th class="header" data-toggle="tooltip" title="Trier par niveau batterie">{{Batterie}}</th>
        </tr>
    </thead>
    <tbody>
    <!-- < ?php
        // To identify duplicated objet with same IEEE
        $IEEE_Table = array();

        foreach ($eqLogics as $eqLogic) {
            list($net, $addr) = explode("/", $eqLogic->getLogicalId());

            echo "\n\n\n<tr>";

            // Network (AbeilleX)
            echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$net.'</span></td>';

            // Device name
            echo '<td><a href="'.$eqLogic->getLinkToConfiguration().'" style="text-decoration: none;">'.$eqLogic->getHumanName(true).'</a></td>';

            // Device type
            $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);
            $type = isset($eqModel['type']) ? $eqModel['type'] : '?';
            echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$type.'</span></td>';

            // ID
            // echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$eqLogic->getId().'</span></td>';

            // Short Address
            echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$addr.'</span></td>';

            /* Extended address/IEEE
               If present in config, taking it.
               If not, asking IEEE adress */
            if ($eqLogic->getConfiguration('ab::icon') == "remotecontrol") {
                $addrIEEE = "-";
            } else {
                $addrIEEE = $eqLogic->getConfiguration('IEEE', 'none');
                if ($addrIEEE == "none") {
                    /* Get IEEE address from zigate */
                    $commandIEEE = $eqLogic->getCmd('info', 'IEEE-Addr');
                    if ($commandIEEE) {
                        $addrIEEE = strtoupper($commandIEEE->execCmd());
                    }
                }
                if (strlen($addrIEEE) > 2) {
                    if (array_key_exists($addrIEEE, $IEEE_Table)) {
                        $IEEE_Table[$addrIEEE] += 1;
                    }
                    else {
                        $IEEE_Table[$addrIEEE] = 1;
                    }
                }
            }
            if ((strlen($addrIEEE) == 16) || ($addrIEEE=="-")) {
                echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$addrIEEE.'</span></td>';
            } else {
                echo '<td><span class="label label-warning" style="font-size: 1em; cursor: default;">Manquante</span></td>';
            }

            // Status: Updated every minutes by cron() (see Abeille.class.php)
            if ($eqLogic->getIsEnable() == 0) // Disabled ?
                $status = '<span class="label label-default" style="font-size: 1em; cursor: default;">{{Désactivé}}</span>';
            // else if ($eqLogic->getStatus('state') == '-')
            //     $status = '<span class="label label-success" style="font-size: 1em; cursor: default;">-</span>';
            else if ($eqLogic->getConfiguration('ab::icon') == "remotecontrol")
                $status = '<span class="label label-success" style="font-size: 1em; cursor: default;">-</span>';
            // else if ((time() - strtotime($eqLogic->getStatus('lastCommunication'))) > ((2*60*$eqLogic->getTimeout())))
            //     $status = '<span class="label label-danger" style="font-size: 1em; cursor: default;">Time-out</span>';
            // else if ((time() - strtotime($eqLogic->getStatus('lastCommunication'))) > (60*$eqLogic->getTimeout()))
            //     $status = '<span class="label label-warning" style="font-size: 1em; cursor: default;">Time-out</span>';
            else if ($eqLogic->getStatus('timeout') == 1)
                $status = '<span class="label label-danger" style="font-size: 1em; cursor: default;">{{Time-out}}</span>';
            else
                $status = '<span class="label label-success" style="font-size: 1em; cursor: default;">{{OK}}</span>';
            echo '<td>'.$status.'</td>';

            // Status APS_ACK
            // Tcharp38: Unreliable so far & no time to work on it.
            // if (isset($dbgDeveloperMode)) {
            //     if ($eqLogic->getIsEnable() == 0) // Disabled ?
            //         $APS_ACK = '<span class="label label-default" style="font-size: 1em; cursor: default;">{{Désactivé}}</span>';
            //     else if ($eqLogic->getConfiguration('ab::icon') == "remotecontrol")
            //         $status = '<span class="label label-success" style="font-size: 1em; cursor: default;">-</span>';
            //     else if ($eqLogic->getStatus('APS_ACK') == '0')
            //         $APS_ACK = $status = '<span class="label label-danger" style="font-size: 1em; cursor: default;">{{NOK}}</span>';
            //     else if ($eqLogic->getStatus('APS_ACK') == '1')
            //         $APS_ACK = '<span class="label label-success" style="font-size: 1em; cursor: default;">{{OK}}</span>';
            //     else
            //         $APS_ACK = '<span class="label label-success" style="font-size: 1em; cursor: default;">-</span>';
            //     echo '<td>'.$APS_ACK.'</td>';
            // }

            // Last comm.
            if ($eqLogic->getConfiguration('ab::icon') == "remotecontrol") {
                $lastComm = '<span class="label label-info" style="font-size: 1em; cursor: default;">-</span>';
            } else if (strlen($eqLogic->getStatus('lastCommunication'))>2) {
                $lastComm = '<span class="label label-info" style="font-size: 1em; cursor: default;">'.$eqLogic->getStatus('lastCommunication').'</span>';
            } else
                $lastComm = '<span class="label label-warning" style="font-size: 1em; cursor: default;">No message received !!</span>';
            echo '<td>'.$lastComm.'</td>';

            // Time in H since last comm.
            $since = '<span class="label label-info" style="font-size: 1em; cursor: default;">'.(floor((time() - strtotime($eqLogic->getStatus('lastCommunication'))) / 3600)).'</span>';
            if ($eqLogic->getConfiguration('ab::icon') == "remotecontrol") {
                $since = '<span class="label label-info" style="font-size: 1em; cursor: default;">-</span>';
            }
            //if ($eqLogic->getStatus('state') == '-') { $since = '<span class="label label-info" style="font-size: 1em; cursor: default;">-</span>'; }
            echo '<td>'.$since.'</td>';

            // Last LQI
            if ($addr == "0000")
                $lastLqi = "-";
            else {
                $lqiCmd = $eqLogic->getCmd('info', 'Link-Quality');
                if (is_object($lqiCmd))
                    $lastLqi = $lqiCmd->execCmd();
                else
                    $lastLqi = "?";
            }
            echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$lastLqi.'</span></td>';

            // Last battery status
            $bat = $eqLogic->getStatus('battery', '');
            if ($bat == '')
                $bat = '-';
            else
                $bat = $bat.'%';
            echo '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'.$bat.'</span></td>';

            echo '</tr>';
        }
    ? > -->
    </tbody>
</table>

<script>
    function refreshHealth() {
        console.log("refreshHealth()");

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
                equipements = res.eq;

                let tr = '';
                for (net in equipements) {
                    // console.log("LA net=", net);
                    n = equipements[net];
                    for (addr in n) {
                        // console.log("LA2 addr=", addr);
                        e = n[addr];

                        tr += '<tr>';

                        // Network (AbeilleX)
                        tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+net+'</span></td>';

                        // Device name
                        tr += '<td><a href="'+e.link+'" style="text-decoration: none;">'+e.hName+'</a></td>';

                        // Device type
                        tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+e.type+'</span></td>';

                        // Short address
                        tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+addr+'</span></td>';

                        // IEEE address
                        tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+e.ieee+'</span></td>';

                        // Status: Updated every minutes by cron() (see Abeille.class.php)
                        if (e.isEnabled == 0) // Disabled ?
                            status = '<span class="label label-default" style="font-size: 1em; cursor: default;">{{Désactivé}}</span>';
                        else if (addr.substr(2) == "rc") // Remote control ?
                            status = '<span class="label label-success" style="font-size: 1em; cursor: default;">-</span>';
                        else if (e.timeout == 1)
                            status = '<span class="label label-danger" style="font-size: 1em; cursor: default;">{{Time-out}}</span>';
                        else
                            status = '<span class="label label-success" style="font-size: 1em; cursor: default;">{{OK}}</span>';
                        tr += '<td>'+status+'</td>';

                        // Last comm.
                        if (addr.substr(2) == "rc") // Remote control ?
                            lastComm = '<span class="label label-info" style="font-size: 1em; cursor: default;">-</span>';
                        else
                            lastComm = '<span class="label label-info" style="font-size: 1em; cursor: default;">'+e.lastComm+'</span>';
                        tr += '<td>'+lastComm+'</td>';

                        // Time in H since last comm.
                        since = '<span class="label label-info" style="font-size: 1em; cursor: default;">'+e.since+'</span>';
                        tr += '<td>'+since+'</td>';

                        tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+e.lastLqi+'</span></td>';

                        tr += '<td><span class="label label-info" style="font-size: 1em; cursor: default;">'+e.lastBat+'</span></td>';

                        tr += '</tr>';
                    }
                }

                $('#table_healthAbeille tbody').empty().append(tr);

                $("#table_healthAbeille").tablesorter().trigger('update');
            }
        });
    }

    refreshHealth();

    setInterval(function () {
        refreshHealth();
    }, 2000);

    // $(function() {
    //     $("#table_healthAbeille").tablesorter();
    // });

</script>
