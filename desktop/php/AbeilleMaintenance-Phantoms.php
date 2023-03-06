<?php
    /*
     * Maintenance - Phantoms part
     * Included from 'AbeilleMaintenance.php'
     *
     * Allows to list phantom devices and attempt to recover those who are always listening.
     */

    // Creating list of Jeedom registered devices
    $eqLogics = eqLogic::byType('Abeille');
    $jeedomDevices = array();
    foreach ($eqLogics as $eqLogic) {
        $jeedomDevices[] = $eqLogic->getLogicalId();
    }
    sendVarToJS('jeedomDevices', $jeedomDevices);
?>

<div style="height:inherit">
    MODE DEVELOPPEUR SEULEMENT<br>
    Cette procèdure n'apporte actuellement RIEN DE PLUS que le 'refresh' réseau<br>
    <br>
    Cette procédure permet de tenter de récupérer certains équipements 'fantômes'.<br>
    <br>
    Les étapes sont les suivantes:
    <br>- Intérrogation du réseau pour lister les équipements Zigbee.
    <br>- Interrogation de chaque équipement inconnu pour tenter de l'identifier.
    <br><br>ATTENTION ! Cette procédure ne peut fonctionner sur les équipements sur pile qui, étant en vieille quasiment tout le temps, ne répondront pas.
    <br><br>On tente l'experience ?

    <a class="btn btn-warning" title="Interrogation du réseau, puis des équipements fantômes. Peut prendre plusieurs minutes en fonction du nombre d\'équipements." onclick="goRecovery()"><i class="fas fa-sync"></i> Go !</a>
    <br>
    <br>
    Interrogation du réseau: <span id="idNetworkLqiScan" style="width:150px; font-weight:bold">?</span><br>
    Nombre d'équipements fantômes: <span id="idNbPhantoms" style="width:150px; font-weight:bold">?</span><br>
    Interrogation des équipements fantômes: <span id="idRecoverPhantoms" style="width:150px; font-weight:bold">?</span><br>
</div>

<script>
    // Launch AbeilleLQI.php to collect network informations.
    function refreshLqiCache() {
        $("#idNetworkLqiScan").empty().append("En cours");

        /* Collect status displayed every 1sec */
        // setTimeout(function () {
        //     trackLQICollectStatus(true, zigateX);
        // }, 1000);

        $.ajax({
            url: "/plugins/Abeille/core/php/AbeilleLQI.php",
            async: true,
            error: function (xhr, status, error) {
                // $("#idLinksTable tbody").empty();
                $("#div_networkZigbeeAlert").showAlert({
                    message: "ERREUR ! Impossible de démarrer la collecte.",
                    level: "danger",
                });
                window.setTimeout(function () {
                    $("#div_networkZigbeeAlert").hide();
                }, 10000);
            },
            success: function (data, status, jqhr) {
                console.log("AbeilleLQI.php output=", data);
                if (data.indexOf("successfully") >= 0) {
                    levelAlert = "info";
                    // $("#idLinksTable").trigger("update");
                    // displayLinksTable(zigateX);
                    // trackLQICollectStatus(false, zigateX);
                } else {
                    $("#div_networkZigbeeAlert").showAlert({
                        message: data,
                        level: "danger",
                    });
                    window.setTimeout(function () {
                        $("#div_networkZigbeeAlert").hide();
                    }, 10000);
                }
                $("#idNetworkLqiScan").empty().append("Terminé");
            },
        });
    }

    function goRecovery() {
        console.log("goRecovery()");

        refreshLqiCache();
        console.log("refreshTerminated"); // really ?

        console.log('Reading network infos');
        zigateX = 1;
        var xhr = $.ajax({
            type: "POST",
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: "getTmpFile",
                file: "AbeilleLQI-Abeille" + zigateX + ".json",
            },
            dataType: "json",
            cache: false,
        });
        xhr.done(function (json, textStatus, jqXHR) {
            res = JSON.parse(json.result);
            if (res.status != 0) {
                // -1 = Most probably file not found
            } else if (res.content == "") {
                // No datas
            } else {
                // Ok
                var json = JSON.parse(res.content);
                console.log('json=', json);
                var routers = json.routers;
                console.log('jeedomDevices=', jeedomDevices);
                phantoms = new Array;
                nbPhantoms = 0
                for (var routerLogicId in routers) {
                    router = routers[routerLogicId];
                    console.log('router=', router);
                    // if (routerLogicId in jeedomDevices)
                    //     continue; // Known
                    if (jeedomDevices.includes(routerLogicId) == false) {
                        console.log('Phantom='+routerLogicId);
                        nbPhantoms = phantoms.push(routerLogicId);
                        continue; // Unknown
                    }
                    for (var neighborLogicId in router.neighbors) {
                        console.log('neighbor=', router.neighbors[neighborLogicId]);
                        if (jeedomDevices.includes(neighborLogicId) == false) {
                            if (phantoms.includes(neighborLogicId) == false) {
                                console.log('Phantom='+neighborLogicId);
                                nbPhantoms = phantoms.push(neighborLogicId);
                            }
                            continue; // Unknown
                        }
                    }
                }

                $("#idNbPhantoms").empty().append(nbPhantoms);
                $("#idRecoverPhantoms").empty().append("En cours");
                for (var i = 0; i < phantoms.length; i++) {
                    console.log("Need to interrogate "+phantoms[i]);
                }
                $("#idRecoverPhantoms").empty().append("Terminé");
            }
        });
    }

</script>
