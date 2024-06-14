<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';
?>

{{Analyse en cours. Veuillez patienter}}<br><br>

<div id="idLqiProgress">
</div>

<script>
    Ruche = "Abeille1";

    /* Start refresh status every 1sec */
    analysisStatus = setInterval(function() { analysisProgress(); }, 1000);  // ms
    textArea = document.getElementById('idLqiProgress');

    function analysisProgress() {
        console.log("analysisProgress("+Ruche+")");

        // var d = new Date();
        // var xhrProgress = new XMLHttpRequest();
        // xhrProgress.onreadystatechange = function() {
        //     if (this.readyState == 4 && this.status == 200) {
        //         networkInformationProgress = this.responseText;
        //         // console.log("Debug - Progress:"+networkInformationProgress);
        //     }
        // };
        // xhrProgress.open("GET", "/plugins/Abeille/tmp/AbeilleLQI_MapData"+Ruche+".json.lock?"+d.getTime(), true);
        // xhrProgress.send();

        $.ajax({
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: 'getTmpFile',
                file : "AbeilleLQI.lock",
            },
            dataType: "json",
            global: false,
            cache: false,
            error: function (request, status, error) {
                console.log("ERROR: Call to getTmpFile failed");
                // $('#div_networkZigbeeAlert').showAlert({
                //     message: "ERREUR ! Problème du lecture du fichier de lock.",
                //     level: 'danger'
                // });
                // _autoUpdate = 0;
                // setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
            },
            success: function (json_res) {
                console.log("json_res=", json_res);
                res = JSON.parse(json_res.result); // res.status, res.error, res.content
                if (res.status != 0) {
                    // var msg = "ERREUR ! Quelque chose s'est mal passé ("+res.error+")";
                    // $('#div_networkZigbeeAlert').showAlert({ message: msg, level: 'danger' });
                    // _autoUpdate = 0;
                    // setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
                } else {
                    var data = res.content;
                    console.log("Status='"+data+"'");
                    if (data.toLowerCase().includes("oops")) {
                        // networkInformationProgress = data;
                        // document.getElementById("refreshInformation").value = data;
                        textArea.innerHTML += data + "<br>";
                        clearInterval(analysisStatus);
                    } else if (data.toLowerCase().includes("done")) {
                        // Reminder: done/<timestamp>/<status
                        // networkInformationProgress = "Collecte terminée";
                        // document.getElementById("refreshInformation").value = "Collecte terminée";
                        textArea.innerHTML += "Collecte terminée<br>";
                        clearInterval(analysisStatus);
                    } else
                        networkInformationProgress = data;
                        // document.getElementById("refreshInformation").value = data;
                        textArea.innerHTML += data + "<br>";
                }
            }
        });
    }

    // var xhr = new XMLHttpRequest();
    // xhr.onreadystatechange = function() {
    //     if (this.readyState == 4 && this.status == 200) {
    //         networkInformation = this.responseText;
    //         console.log("analysis() ended: ", networkInformation);
    //         clearInterval(analysisStatus);
    //     }
    // };
    // xhr.open("GET", "/plugins/Abeille/core/php/AbeilleLQI.php", true); // Updating all zigates
    // xhr.send();
</script>