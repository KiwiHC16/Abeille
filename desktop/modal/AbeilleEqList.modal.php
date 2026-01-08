<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    // include_once __DIR__.'/../../core/php/AbeilleOTA.php';

    echo '<script>var js_otaDir = "'.otaDir.'";</script>'; // PHP to JS
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    // echo '<script>var js_queueCtrlToCmd = "'.$abQueues['ctrlToCmd']['id'].'";</script>'; // PHP to JS
?>

<h3>{{Equipements}}</h3>
<table id="idDevicesTable">
<tbody>
<tr><th>{{Nom}}</th><th width="40px">Addr</th></tr>
<?php
    // $eqLogics = eqLogic::byType('Abeille');
    // foreach ($eqLogics as $eqLogic) {
    //     $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
    //     list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
    //     if ($eqAddr == "0000")
    //         continue; // Ignoring gateways
    //     $eqName = $eqLogic->getName();
    //     $eqId = $eqLogic->getId();

    //     echo '<tr>';
    //     echo '<td>'.$eqName.'</td><td>'.$eqAddr.'</td>';
    //     echo '<td><button type="button" class="btn btn-secondary" onclick="removeDevice(\''.$eqId.'\')">{{Supprimer}}</button></td>';
    //     echo '</tr>';
    // }
?>
</tbody>
</table>

<script>
    // console.log ("js_eqPerZigate=", js_eqPerZigate);
    eval("var eqPerZigate = JSON.parse(js_eqPerZigate);");
    console.log ("eqPerZigate=", eqPerZigate);

    updateDevicesTable();

    function updateDevicesTable() {

        console.log("updateDevicesTable()");

        tr = "";
        for (const [gtwId, devicesById] of Object.entries(eqPerZigate)) {

            // console.log("gtwId="+gtwId+", devicesById=", devicesById);
            for (const [eqId, dev] of Object.entries(devicesById)) {

                // console.log ("eqId="+eqId+", dev=", dev);
                eqName = dev["name"];
                eqAddr = dev["addr"];

                tr += "<tr>";
                tr += "<td>" + eqName + "</td>";
                tr += "<td>" + eqAddr + "</td>";
                tr += '<td><button type="button" class="btn btn-secondary" onclick="removeDevice(\'' + gtwId + '\', \'' + eqId + '\')">{{Supprimer}}</button></td>';
                tr += "</tr>";
            }
        }

        $("#idDevicesTable tbody").empty().append(tr);
    }

    // Remove device whose Jeedom ID is 'eqId', then update 'devicesTable'
    function removeDevice(gtwId, eqId) {

        console.log("removeDevice(gtwId="+ gtwId + ", eqId=" + eqId + ")");

        removed = {};
        // Excluded local devices (ex: Abeille remote control with addr 'rcXX')
        addr = eqPerZigate[gtwId][eqId]['addr'];
        if (addr.substring(0, 2) != "rc") {
            removed[gtwId] = []; // Addresses list
            removed[gtwId].push(addr);
        }
        console.log("removed=", removed);

        jeedom.eqLogic.remove({
            type: "Abeille",
            id: eqId,
            error: function (error) {
                $.fn.showAlert({
                    message: error.message,
                    level: "danger",
                });
            },
            success: function () {
                delete eqPerZigate[gtwId][eqId];
                updateDevicesTable(); // Update devices table

                if (removed.length == 0)
                    return; // Possible if Abeille remote control removed

                domUtils.ajax({ // Inform daemons that device removed
                    type: "POST",
                    url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
                    data: {
                        action: "eqRemoved",
                        removed:  JSON.stringify(removed),
                    },
                    dataType: 'json',
                    global: false,
                    error: function(error) {
                        jeedomUtils.showAlert({
                            message: error.message,
                            level: 'danger'
                        })
                    },
                    success: function(data) {
                        //Do stuff
                        // jeedomUtils.showAlert({
                        //     message: 'All good dude!',
                        //     level: 'success'
                        // })
                    }
                })
            },
        });
    }
</script>
