
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Javascript developer features
 */

/* Called when 'setTimeout' button is pressed
   Allows to modify timeout of selected equipements. */
function setBeesTimeout(zgNb) {
    console.log("setBeesTimeout(zgNb="+zgNb+")");

    var sel = getSelectedEqs(zgNb);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !")
        return;
    }
    $("#abeilleModal").dialog({
        title: "{{Modification du timeout}}",
        autoOpen: false,
        resizable: false,
        modal: true,
        height: 300,
        width: 400,
        closeText: "",
    });
    $('#abeilleModal').load('index.php?v=d&plugin=Abeille&modal=setBeesTimeout.abeille&zgNb='+zgNb).dialog('open');
}

/* Called when 'monitor' button is pressed */
function monitorIt(zgNb, zgPort) {
    console.log("monitorIt(zgNb="+zgNb+", zpPort="+zgPort+")");

    var sel = getSelectedEqs(zgNb);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !")
        return;
    }
    if (sel["nb"] != 1) {
        alert("Un seul équipement peut être surveillé à la fois !")
        return;
    }
    var eqId = sel["ids"][0];
    console.log("idToMonitor="+eqId);

    $.ajax({
        type: 'POST',
        url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
        data: {
            action: 'monitor',
            eqId: eqId
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            bootbox.alert("ERREUR 'monitor' !<br>"+"status="+status+"<br>error="+error);
        },
        success: function (json_res) {
            console.log(json_res);
            res = JSON.parse(json_res.result);
            if (res.status != 0) {
                var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                alert(msg);
            } else
                window.location.reload();
    }
    });
}

/* For dev mode. Called to save debug config change in 'tmp/debug.json'. */
function saveChanges() {
    console.log("saveChanges()");

    /* Get current config */
    $.ajax({
        type: 'POST',
        url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
        data: {
            action: 'readDevConfig'
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            bootbox.alert("ERREUR 'readDevConfig' !<br>status="+status+"<br>error="+error);
        },
        success: function (json_res) {
            console.log(json_res);
            res = JSON.parse(json_res.result);
            if (res.status != 0) {
                var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                alert(msg);
                return;
            }

            /* Update config */
            devConfig = JSON.parse(res.config);

            var dbgParserDisableList = document.getElementById('idParserLog').value;
            console.log("dbgParserDisableList="+dbgParserDisableList);
            if (dbgParserDisableList != "") {
                var dbgParserLog = [];
                var res = dbgParserDisableList.split(" ");
                res.forEach(function(value) {
                    console.log("value="+value);
                    dbgParserLog.push(value);
                });
                devConfig["dbgParserLog"] = dbgParserLog;
            } else
                devConfig["dbgParserLog"] = [];

            /* Save config */
            jsonConfig = JSON.stringify(devConfig);
            console.log("New devConfig="+jsonConfig);
            $.ajax({
                type: 'POST',
                url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
                data: {
                    action: 'writeDevConfig',
                    devConfig: jsonConfig
                },
                dataType: 'json',
                global: false,
                error: function (request, status, error) {
                    bootbox.alert("ERREUR 'writeDevConfig' !<br>status="+status+"<br>error="+error);
                },
                success: function (json_res) {
                    console.log(json_res);
                    res = JSON.parse(json_res.result);
                    if (res.status != 0) {
                        var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                        alert(msg);
                        return;
                    }
                }
            });
        }
    });
}
