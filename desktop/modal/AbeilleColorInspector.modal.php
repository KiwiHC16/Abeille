<?php
    // require_once __DIR__.'/../../core/config/Abeille.config.php';
    // include_once __DIR__.'/../../core/php/AbeilleOTA.php';

    // echo '<script>var js_otaDir = "'.otaDir.'";</script>'; // PHP to JS
    // $abQueues = $GLOBALS['abQueues'];
    // echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS
    // echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    // echo '<script>var js_queueCtrlToCmd = "'.$abQueues['ctrlToCmd']['id'].'";</script>'; // PHP to JS
?>

<div>
    <table id="idCiSteps">
        <tr>
            <th width="50px">Status</th>
            <th width="300px">Etape</th>
        </tr>
    </table>
</div>
<br>
<p style="text-align:center; margin-top:30px">
    <a id="idCiCloseBtn" class="btn btn-secondary">{{Annuler}}</a>
    <a id="idCiRetryBtn" class="btn btn-success" title="{{Continue le process}}" onclick="ci_openReturnChannel">{{Continuer}}</a>
</p>

<script>
    ci_steps = new Object();
    ci_openReturnChannel();

    // Retry button: Continue
    // popup.find("#idCiRetryBtn").on("click", function () {
    //     ci_openReturnChannel();
    // });

    // Close button
    // popup.find("#idCiCloseBtn").on("click", function () {
    //     myPopup.find(".bootbox-close-button").trigger("click");
    //     // Refresh the page
    //     location.reload();
    // });

    /* Open return channel */
    function ci_openReturnChannel() {
        console.log("ci_openReturnChannel(), curEqId=" + curEqId);

        var url = "plugins/Abeille/core/php/AbeilleColorInspector.php";
        var xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.responseType = "text";
        xhr.onload = ci_checkReturnChannel;
        // request.onreadystatechange = returnChannelStateChange;
        var data = new FormData();
        data.append("eqId", curEqId);
        xhr.send(data);
    }

    /* Color Inspector: Treat async infos received from server to display them. */
    function ci_checkReturnChannel() {
        // console.log("receiveInfos()");
        // console.log("Got='"+this.responseText+"'");
        if (this.responseText == "") {
            console.log("ci_checkReturnChannel() => EMPTY");
            // openRepairReturnChannel();
            return;
        }
        console.log("ci_checkReturnChannel(), resp=", this.responseText);

        // console.log("ci_steps=", ci_steps);
        resp = JSON.parse(this.responseText);
        // console.log("resp=", resp);
        resp.forEach((m) => {
            console.log("m=", m);
            if (m.type == "step") {
                stepName = m.name;
                stepStatus = m.status;

                let myTable = document.getElementById("idCiSteps");
                // console.log("myTable=", myTable);

                // New or already known step ?
                if (typeof ci_steps[stepName] == "undefined") {
                    console.log(
                        "new step '" + stepName + "' => status='" + stepStatus + "'"
                    );
                    ci_steps[stepName] = new Object();

                    let row = myTable.insertRow(-1); // We are adding at the end
                    rowIndex = row.rowIndex;
                    ci_steps[stepName]["rowIndex"] = rowIndex;
                    // console.log("rowIndex=" + rowIndex);
                    cell0 = row.insertCell(0);
                    cell1 = row.insertCell(1);
                    cell0.innerHTML = stepStatus;
                    cell1.innerHTML = stepName;
                    // console.log("myTable NOW=", myTable);
                } else {
                    step = ci_steps[stepName];
                    rowIndex = step.rowIndex;
                    console.log(
                        "known step '" +
                            stepName +
                            "' => index=" +
                            rowIndex +
                            ", status='" +
                            stepStatus +
                            "'"
                    );
                    cell0 = myTable.rows[rowIndex].cells[0];
                    cell0.innerHTML = stepStatus;
                }
                ci_steps[stepName]["status"] = stepStatus;
            }
        });

        // Do we still need to interrogate device ?
        missingInfo = false;
        for (const [stepName, step] of Object.entries(ci_steps)) {
            // console.log("toto stepName=" + stepName + " step=", step);
            if (step.status != "ok") {
                console.log("Still missing infos for step '" + stepName + "'");
                missingInfo = true;
                break;
            }
        }
        if (!missingInfo) {
            // Rename 'cancel' to 'close'
            document.getElementById("idCiCloseBtn").innerHTML = "{{Fermer}}";
            // Hide 'retry' button
            document.getElementById("idCiRetryBtn").style.display = "none";
        }
        // if (missingInfo) openRepairReturnChannel();
    }
</script>
