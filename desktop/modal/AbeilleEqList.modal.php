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
    // TODO: Need to update page on modal exit => window.location.reload();

    function updateDevicesTable() {

        console.log("updateDevicesTable()");

        eval("let eqPerZigate = JSON.parse(js_eqPerZigate);");
        console.log ("eqPerZigate=", eqPerZigate);
        
        tr = "";
        for (const [gtwId, eqId] of Object.entries(eqPerZigate)) {

            eqName = eqPerZigate[gtwId][eqId]["name"];
            eqAddr = eqPerZigate[gtwId][eqId]["addr"];

            tr += "<tr>";
            tr += "<td>" + eqName + "</td>";
            tr += "<td>" + eqAddr + "</td>";
            tr += '<td><button type="button" class="btn btn-secondary" onclick="removeDevice(\'' + eqId + '\')">{{Supprimer}}</button></td>';
            tr += "</tr>";
        }

        $("#idDevicesTable tbody").append(tr);
    }

    // Remove device whose Jeedom ID is 'eqId', then update 'devicesTable'
    function removeDevice(eqId) {

        console.log("removeDevice(eqId=" + eqId + ")");

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
                // Update devices table
            },
        });
    }

    updateDevicesTable();
</script>
