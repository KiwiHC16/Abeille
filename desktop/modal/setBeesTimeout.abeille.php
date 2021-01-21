<?php
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }
?>

<div style="margin: 10px 10px">
    Entrez la valeur de timeout (en min) à appliquer aux équipements selectionnés.
    <br>
    <br>
    <label>Timeout</label>
    <input id="idTimeoutVal" class="form-control" style="width:60px;"/>
    <br>
    <br>
    <br>
    <a id="idApplyTimeout" class="btn btn-danger pull-left" >{{Appliquer}}</a>
</div>

<script>
    $('#idApplyTimeout').on('click', function () {
        console.log("idApplyTimeout() click");
        var zgNb = <?php echo $_GET['zgNb']; ?>;
        var selEq = getSelectedEqs(zgNb);
        var timeout = document.getElementById("idTimeoutVal").value;

        for (var eqNb = 0; eqNb < selEq["nb"]; eqNb++) {
            console.log("Eq"+eqNb+": "+selEq["ids"][eqNb]);
        }

        var eqList = selEq["ids"];
        console.log("eqList="+eqList);
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
            data: {
                action: 'setEqTimeout',
                eqList: eqList,
                timeout: timeout
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'setEqTimeout' !");
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                    alert(msg);
                } else
                    window.location.reload();
            }
        });
    });
</script>
