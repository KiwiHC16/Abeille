
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

/* Check which equipements are selected for given zigate number (zgNb).
   Returns: object {zgNb:<zigateNb>, nb:<nbOfSelectedEq>, ids:[<arrayOfEqIds>]} */
function checkSelected(zgNb) {
    var selected = new Object;
    selected["zgNb"] = zgNb; // Zigate number
    selected["nb"] = 0; // Number of selected equipments
    selected["ids"] = []; // Array of eq IDs
    eval('var eqZigate = JSON.parse(js_eqZigate'+zgNb+');'); // List of eq for current zigate
    for (var i = 0; i < eqZigate.length; i++) {
        var eqId = eqZigate[i].id;
        var checked = document.getElementById("idBeeChecked"+zgNb+"-"+eqId).checked;
        // console.log("eqId="+eqId+", checked="+checked);
        if (checked == false)
            continue;

        selected["nb"]++;
        selected["ids"].push(eqId);
    }
    console.log('selected["nb"]='+selected["nb"]);
    return selected;
}

/* Called when 'setTimeout' button is pressed */
function setBeesTimeout(zgNb) {
    console.log("setBeesTimeout(zgNb="+zgNb+")");
    
    var sel = checkSelected(zgNb);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !")
        return;
    }
    $('#md_modal').dialog({title: "{{Modification du timeout}}"});
    $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=setBeesTimeout.abeille').dialog('open');
}

/* Called when 'monitor' button is pressed */
function monitorIt(zgNb, zgPort) {
    console.log("monitorIt(zgNb="+zgNb+", zpPort="+zgPort+")");
    
    var sel = checkSelected(zgNb);
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

/* Called when 'developer mode' must be enabled or disabled.
   This means creating or deleting "tmp/debug.php" file. */
function xableDevMode(enable) {
    console.log("xableDevMode(enable=" + enable + ")");
    if (enable == 1) {
        /* Enable developer mode by creating debug.php then restart */
        var devAction = 'createDevConfig';
    } else {
        var devAction = 'deleteDevConfig';
    }

    $.ajax({
        type: 'POST',
        url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
        data: {
            action: devAction
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            bootbox.alert("ERREUR '"+devAction+"' !<br>status="+status+"<br>error="+error);
        },
        success: function (json_res) {
            console.log(json_res);
            res = JSON.parse(json_res.result);
            if (res.status != 0) {
                var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                alert(msg);
            } else
                window.location.reload(true);
    }
    });
}

/* Called when 'developer mode' must be enabled or disabled.
   This means creating or deleting "tmp/debug.php" file. */
function xableAbeillePHP(enable) {
    console.log("xableAbeillePHP(enable=" + enable + ")");
    var devConfig = new Object;
    if (enable == 1)
        devConfig["dbgAbeillePHP"] = true;
    else
        devConfig["dbgAbeillePHP"] = false;
    $.ajax({
        type: 'POST',
        url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
        data: {
            action: 'updateDevConfig',
            devConfig: devConfig
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            bootbox.alert("ERREUR 'updateDevConfig' !<br>status="+status+"<br>error="+error);
        },
        success: function (json_res) {
            console.log(json_res);
            res = JSON.parse(json_res.result);
            if (res.status != 0) {
                var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                alert(msg);
            } else
                window.location.reload(true);
        }
    });
}
