<?php
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    require_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    $jsonCmdsList = AbeilleTools::getCommandsList();
?>

<div style="margin: 10px 10px">
    <div id="idModalText">
        Selectionner la commande à ajouter à partir du fichier JSON.
        <br>
        <br>
        <select id="idCmdSelect" class="form-control input-sm" title="{{Commandes internes Abeille}}">
            <?php
            foreach ($jsonCmdsList as $file) {
                echo "<option value=\"".$file."\">".$file."</option>";
            }
            ?>
        </select>
        <br>
        <br>
    </div>
    <div class="text-center">
        <a id="idLoadCmdJson" class="btn btn-danger">{{Charger}}</a>
    </div>
</div>

<script>
    $('#idLoadCmdJson').on('click', function () {
        console.log("idLoadCmdJson click");

        var file = document.getElementById("idCmdSelect").value;

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'getFile',
                file: "core/config/commands/"+file+".json"
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'idLoadCmdJson' !<br>Votre installation semble corrompue.");
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                console.log("status="+res.status);
                if (res.status != 0)
                    console.log("error="+res.error);
                else {
                    console.log("content="+res.content);
                    cmd = JSON.parse(res.content);
                    console.log(cmd);
                    var key = Object.keys(cmd)[0];
                    cmd2 = cmd[key];
                    console.log(key);
                    console.log(cmd2);
                    var _cmd = {};
                    _cmd.name = cmd2["name"];
                    _cmd.subType = cmd2["subType"];
                    _cmd.logicalId = key;
                    _cmd.isVisible = cmd2["isVisible"];
                    _cmd.configuration = {};
                    _cmd.configuration.topic = cmd2["configuration"]["topic"];
                    _cmd.configuration.request = cmd2["configuration"]["request"];
                    if (cmd2["Type"] == "action") {
                        _cmd.type = 'action';
                    } else {
                        _cmd.type = 'info';
                        _cmd.isHistorized = cmd2["isHistorized"];
                        _cmd.invertBinary = cmd2["invertBinary"];
                    }
                    addCmdToTable(_cmd);
                    $('.cmd:last .cmdAttr[data-l1key=type]').trigger('change')

                    $('#idModalText').empty().append("Ok.<br />Commande ajoutée en fin de tableau.<br /><br />Pensez à mettre à jour, entre autre, #EP# is besoin et sauvegarder.<br /><br />");
                }
            }
        });
    });
</script>
