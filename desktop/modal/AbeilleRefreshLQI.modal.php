<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';
?>

{{Analyse en cours. Veuillez patienter}}<br><br>

<div id="idLqiProgress">
</div>

<script>
    Ruche = "Abeille1";

    /* Start refresh status every 1sec */
    analysisText = "";
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
                    var content = res.content;
                    console.log("Status='"+content+"'");
                    if (content.toLowerCase().includes("oops")) {
                        textArea.innerHTML += content + "<br>";
                        clearInterval(analysisStatus);
                    } else if (content.includes("= Collecte")) {
                        // Reminder: '= Collecte terminée: <timestamp>/<status>'
                        textArea.innerHTML += "Collecte terminée<br>";
                        textArea.innerHTML += "<br>Si aucun équipement n'est listé<br>";
                        textArea.innerHTML += "- soit votre réseau est vide<br>";
                        textArea.innerHTML += "- soit ils sont tous en timeout depuis un moment.<br>";
                        clearInterval(analysisStatus);
                    } else {
                        if (content != analysisText) {
                            textArea.innerHTML += content + "<br>";
                            analysisText = content;
                        }
                    }
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