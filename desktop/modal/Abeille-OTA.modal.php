<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    include_once __DIR__.'/../../core/php/AbeilleOTA.php';

    echo '<script>var js_otaDir = "'.otaDir.'";</script>'; // PHP to JS
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    // echo '<script>var js_queueCtrlToCmd = "'.$abQueues['ctrlToCmd']['id'].'";</script>'; // PHP to JS
?>
<div class="col-sm-8">
    Mise-à-jour des équipements "Over-The-Air".
    <?php
    echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/OTA.html"><i class="fas fa-book"></i> {{Documentation}}</a>';
    ?>
    <br>
    <h3>Firmwares disponibles</h3>
    <button type="button" onclick="uploadOta()" class="btn btn-secondary" title="Ajouter un fichier OTA local"><i class="fas fa-plus"></i> Ajouter</button>

    <?php
        // Reading available OTA firmwares
        logSetConf('Abeille');
        otaReadFirmwares();
        echo '<table id="idFwTable">';
        echo '<tbody>';
        echo '<tr><th></th><th>Fabricant</th><th>Type image</th><th>Version</th><th>Nom fichier</th></tr>';
        $rawIdx = 1;
        if (isset($GLOBALS['ota_fw'])) {
          foreach ($GLOBALS['ota_fw'] as $manufCode => $fw) {
            foreach ($fw as $imgType => $fw2) {
              $imgVersion = $fw2['fileVersion'];
              $imgName = $fw2['fileName'];
              echo '<tr>';
              echo '<td><input type="checkbox" id="idFwChecked'.$rawIdx.'"></td>';
              echo '<td>'.$manufCode.'</td>';
              echo '<td>'.$imgType.'</td>';
              echo '<td>'.$imgVersion.'</td>';
              echo '<td>'.$imgName.'</td>';
              echo '<td><button type="button" class="btn btn-secondary" onclick="removeFw(\''.$imgName.'\')"><i class="far fa-trash-alt"></i></button></td>';
              echo '</tr>';
              $rawIdx++;
            }
          }
        }
        echo '</tbody>';
        echo '</table>';
?>
</div>

<div class="col-sm-4">
    <h3>Equipements</h3>
    <table>
    <tbody>
    <tr><th>Nom</th><th>Addr</th></tr>
    <?php
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {
      $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
      list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
      if ($eqAddr == "0000")
        continue; // Ignoring zigates
      $eqName = $eqLogic->getName();
      // TODO: Dest EP should be the one that supports 0019 but currently no way to identify it so using mainEP
      $mainEP = $eqLogic->getConfiguration('mainEP', '01');

      echo '<tr>';
      echo '<td>'.$eqName.'</td><td>'.$eqAddr.'</td>';
      echo '<td><button type="button" class="btn btn-secondary" onclick="notifyDevice(\''.$eqLogicId.'\', \''.$mainEP.'\')">Notifier</button></td>';
      echo '</tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<script>
    function notifyDevice(eqLogicId, ep) {
        console.log('notifyDevice('+eqLogicId+', ep='+ep+')');

        // What firmware is selected ?
        table = document.getElementById("idFwTable");
        nbLines = table.rows.length;
        selected = 0;
        selectedIdx = 0;
        for (i = 1; i < nbLines; i++) { // Skipping header row
          checked = document.getElementById("idFwChecked"+i).checked;
          if (checked == false)
              continue;
          selected++;
          selectedIdx = i;
        }
        if (selected == 0) {
          alert('Aucun firmware sélectionné.');
          return;
        }
        if (selected != 1) {
          alert('Un seul firmware doit être sélectionné.');
          return;
        }

        console.log("selectedIdx="+selectedIdx);
        raw = table.rows[selectedIdx];
        manufCode = raw.cells[1].innerHTML;
        imgType = raw.cells[2].innerHTML;

        var xhr = new XMLHttpRequest();
        var url = "plugins/Abeille/core/php/AbeilleCliToQueue.php";
        topic = "Cmd"+eqLogicId+"_otaLoadImage";
        payload = "manufCode="+manufCode+"_imgType="+imgType;
        // topic = "Cmd"+eqLogicId+"_cmd-0019";
        // payload = "ep="+ep+"_cmd=00_manufCode="+manufCode+"_imgType="+imgType;
        xhr.open("GET", url+"?action=sendMsg&queueId="+js_queueXToCmd+"&topic="+topic+"&payload="+payload, true);
        xhr.send();
        xhr.onreadystatechange = function() { //Appelle une fonction au changement d'état.
          if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            // Requête finie, traitement ici.
            topic = "Cmd"+eqLogicId+"_otaImageNotify";
            payload = "ep="+ep+"_manufCode="+manufCode+"_imgType="+imgType;
            var xhr = new XMLHttpRequest();
            xhr.open("GET", url+"?action=sendMsg&queueId="+js_queueXToCmd+"&topic="+topic+"&payload="+payload, true);
            xhr.send();
          }
        }
    }

    /* Upload a local OTA firmware */
    function uploadOta() {
        console.log("uploadOta()");

        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.zigbee, .ota, .ota.signed';
        input.onchange = e => {

            var file = e.target.files[0];
            // file.name = the file's name including extension
            // file.size = the size in bytes
            // file.type = file type ex. 'application/pdf'
            console.log("file="+file.name);
            console.log(file);

            var formData = new FormData();
            formData.append("file", file);
            // TODO: NOT WORKING
            formData.append("destDir", js_otaDir); // OTA dest dir

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "plugins/Abeille/core/php/AbeilleUpload.php", true);
            xhr.onload = function (oEvent) {
                console.log(oEvent);
                if (xhr.status != 200) {
                    console.log("Error " + xhr.status + " occurred when trying to upload your file.");
                    return;
                }
                console.log("Uploaded !");
                refreshFirmwareTable();
            };
            xhr.send(formData);

            // var reader = new FileReader();
            // reader.onload = function() {
            //     var arraybuffer = reader.result;

            //     var xhr = new XMLHttpRequest();
            //     xhr.open("POST", "plugins/Abeille/core/php/AbeilleUpload.php", true);
            //     xhr.onload = function (oEvent) {
            //       // Uploaded.
            //     };
            //     xhr.send(arraybuffer);
            // }
            // reader.readAsArrayBuffer(file);
        }
        input.click();
    }

    // Delete firmware
    function removeFw(fileName) {
        console.log('removeFw('+fileName+')');
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'delFile',
                file: js_otaDir+'/'+fileName
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'removeFw' !<br>Votre installation semble corrompue.<br>"+error);
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("Remove error="+res.error);
                    return;
                }
                console.log("Remove successful");
                refreshFirmwareTable();
            }
        });
    }

    // Refresh available FW table
    function refreshFirmwareTable() {
        console.log("refreshFirmwareTable()");
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleOTA.ajax.php',
            data: {
                action: 'getOtaList',
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'refreshFirmwareTable' !<br>Votre installation semble corrompue.<br>"+error);
            },
            success: function (json_res) {
                console.log(json_res);
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("getOtaList ERROR: "+res.error);
                    return;
                }
                fw_ota = res.fw_ota;
                console.log(fw_ota);
                t = '<tr><th></th><th>Fabricant</th><th>Type image</th><th>Version</th><th>Nom fichier</th></tr>';
                $('#idFwTable tbody').empty();
                $('#idFwTable tbody').append(t);
                rawIdx = 1;
                for (const [manufCode, images] of Object.entries(fw_ota)) {
                    for (const [imgType, fw] of Object.entries(images)) {
                        imgVersion = fw['fileVersion'];
                        imgName = fw['fileName'];
                        t = '<tr>';
                        t += '<td><input type="checkbox" id="idFwChecked'+rawIdx+'"></td>';
                        t += '<td>'+manufCode+'</td>';
                        t += '<td>'+imgType+'</td>';
                        t += '<td>'+imgVersion+'</td>';
                        t += '<td>'+imgName+'</td>';
                        t += '<td><button type="button" class="btn btn-secondary" onclick="removeFw(\''+imgName+'\')"><i class="far fa-trash-alt"></i></button></td>';
                        t += '</tr>';
                        $('#idFwTable tbody').append(t);
                        rawIdx++;
                    }
                }

                /* Asking parser to refresh its firmwares list */
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueXToParser+"&msg=type:readOtaFirmwares", true);
                xhr.onload = function () {
                    /* Asking cmd to refresh its firmwares list */
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueXToCmd+"&msg=type:readOtaFirmwares", true);
                    xhr.send();
                };
                xhr.send();
            }
        });
    }
</script>
