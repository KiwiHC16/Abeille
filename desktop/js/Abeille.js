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

// Note: 'eqId' seems overwritten somewhere.
var curEqId = -1;

// console.log("LA1 eqId=", curEqId);

// Executed on page load/refresh
if (window.location.href.indexOf("id=") > -1) {
    let params = new URL(document.location).searchParams;
    curEqId = params.get("id");
    refreshAdvEq();
}

// console.log("LA2 eqId=", curEqId);

// Click generate on page reload too.
$(".eqLogicDisplayCard").on("click", function () {
    console.log("eqLogicDisplayCard click");
    if (!isset($(this).attr("data-eqLogic_id"))) {
        console.log("ERROR: 'data-eqLogic_id' is not defined");
        return;
    }
    curEqId = $(this).attr("data-eqLogic_id");
    refreshAdvEq();
});

function refreshAdvEq() {
    console.log("refreshAdvEq(" + curEqId + ")");
    eqId = curEqId;

    // Collect eq & update advanced infos
    $.ajax({
        type: "POST",
        url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
        data: {
            action: "getEq",
            eqId: eqId,
        },
        dataType: "json",
        global: false,
        error: function (request, status, error) {
            bootbox.alert("ERREUR 'getEq' !");
        },
        success: function (json_res) {
            console.log("json_res=", json_res);
            res = JSON.parse(json_res.result);
            eq = res.eq;

            zgId = eq.zgId;
            eqAddr = eq.addr;
            eqBatteryType = eq.batteryType;

            console.log("eq=", eq);
            // console.log("idEqName=", document.getElementById("idEqName"));
            // console.log("idEqId=", document.getElementById("idEqId"));

            // Updating device related infos on main and advanced
            document.getElementById("idEqName").value = eq.name;
            document.getElementById("idEqId").value = eqId;
            document.getElementById("idEqAddr").value = eq.addr;
            document.getElementById("idZgType").value = eq.zgType;
            document.getElementById("idZbModel").value = eq.zbModel;
            document.getElementById("idZbManuf").value = eq.zbManuf;
            document.getElementById("idModelName").value = eq.modelName;
            document.getElementById("idModelSource").value = eq.modelSource;
            document.getElementById("idModelType").value = eq.modelType;
            if (eq.batteryType == "")
                document.getElementById("idBatteryType").value = "{{Secteur}}";
            else
                document.getElementById("idBatteryType").value =
                    "{{Batterie}} " + eq.batteryType;

            if (typeof eq.zigbee.manufCode != "undefined")
                document.getElementById("idManufCode").value =
                    eq.zigbee.manufCode;

            // Info + lien pour rétablir le fonctionnement normal si l'utilisateur a forcé le model
            if (eq.modelForced) {
                var $pRestoreModelAuto = $("<p><stron>{{Vous avez forcé le modèle de cet équipement. }}</strong></p>");
                var $aRestoreModelAuto = $("<a href=\"#\" id=\"linkRestoreAutoModel\" style=\"text-decoration:underline;\">{{ Rétablir le fonctionnement normal (modèle auto)}}</a>");
                $pRestoreModelAuto.append($aRestoreModelAuto).insertAfter("#idModelChangeBtn");
            }

            // Show/hide zigate or devices part
            zgPart = document.getElementById("idAdvZigate");
            devPart = document.getElementById("idAdvDevices");
            if (eq.addr == "0000") {
                zgPart.style.display = "block";
                devPart.style.display = "none";
            } else {
                zgPart.style.display = "none";
                devPart.style.display = "block";
            }

            // Updating info cmds
            const advInfoCmds = document.querySelectorAll("[advInfo]"); // All with attribute named "advInfo"
            for (let i = 0; i < advInfoCmds.length; i++) {
                elm = advInfoCmds[i];
                console.log("advInfoCmd=", advInfoCmds[i]);
                // elm.classList.add('col-sm-5');
                // elm.classList.add('cmd');
                // elm.setAttribute('data-eqlogic_id', eqId);
                cmdLogicId = elm.getAttribute("advInfo");
                console.log("cmdLogicId=", cmdLogicId);
                if (typeof eq.cmds[cmdLogicId] != "undefined") {
                    cmd = eq.cmds[cmdLogicId];
                    console.log("cmd=", cmd);
                    cmdId = cmd.id;
                    cmdVal = cmd.val;
                    console.log("cmdVal=", cmdVal);
                    // elm.setAttribute('data-cmd_id', cmdId);
                    child = elm.firstElementChild;
                    if (child != null) {
                        console.log("child=", child);
                        child.id = "cmdId-" + cmdId;
                        child.setAttribute("value", cmdVal);
                    }

                    // jeedom.cmd.addUpdateFunction(cmdId, updateInfoCmd);
                    // Warning: addUpdateFunction() seems only available since v4.4 core
                    if (!isset(jeedom.cmd.update)) jeedom.cmd.update = [];
                    jeedom.cmd.update[cmdId] = updateInfoCmd;
                    console.log("jeedom.cmd.update=", jeedom.cmd.update);
                }
            }

            // Settings default EP
            var items = document.getElementsByClassName("advEp");
            for (var i = 0; i < items.length; i++) {
                items[i].value = eq.defaultEp;
            }

            // Reset HW visible is type "PI"
            if (eq.zgType == "PI" || eq.zgType == "PIv2") {
                resetHw = document.getElementById("idAdvResetHw");
                resetHw.style.display = "block";
            }

            // Zigbee channel user choice
            if (eq.zgChan != "") {
                select = document.getElementById("idZgChan");
                select.value = eq.zgChan;
            }
        },
    });
}

// This function is called each time a corresponding info cmd has a value update.
// Reminder: jeedom.cmd.update[cmdId] = updateInfoCmd()
function updateInfoCmd(_options) {
    console.log("updateInfoCmd(): options=", _options);
    cmdId = _options.cmd_id;
    // var elm2 = document.getElementById('cmdId-9999');
    // console.log('elm2=', elm2);
    var elm = document.getElementById("cmdId-" + cmdId);
    if (elm == null) {
        console.log("ERROR: Cannot find elm 'cmdId-" + cmdId + "'");
        return;
    }
    console.log("elm=", elm);
    if (true /*$isInput*/) elm.value = _options.display_value;
    // Not <input>. Assuming <span>
    else elm.textContent = _options.display_value;
}

$("#in_searchEqlogicB")
    .off("keyup")
    .keyup(function () {
        var search = $(this).value();
        if (search == "") {
            $(".eqLogicDisplayCardB").show();
            $(".eqLogicThumbnailContainer").packery();
            return;
        }
        $(".eqLogicDisplayCardB").hide();
        $(".eqLogicDisplayCardB .name").each(function () {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(search.toLowerCase()) >= 0) {
                $(this);
                $(this).closest(".eqLogicDisplayCardB").show();
            }
        });
        $(".eqLogicThumbnailContainer").packery();
    });

$("#bt_healthAbeille").on("click", function () {
    $("#md_modal").dialog({ title: "{{Santé Abeille}}" });
    $("#md_modal")
        .load("index.php?v=d&plugin=Abeille&modal=AbeilleHealth.modal")
        .dialog("open");
});

$("#bt_network").on("click", function () {
    $("#md_modal").dialog({ title: "{{Réseau Abeille}}" });
    $("#md_modal")
        .load("index.php?v=d&plugin=Abeille&modal=AbeilleNetwork.modal")
        .dialog("open");
});

$("#bt_networkMap").on("click", function () {
    window.open("index.php?v=d&m=Abeille&p=AbeilleNetworkMap");
});

$("#bt_supportedEqList").on("click", function () {
    $("#md_modal").dialog({ title: "{{Liste de compatibilité}}" });
    $("#md_modal")
        .load("index.php?v=d&plugin=Abeille&modal=AbeilleCompatibility.modal")
        .dialog("open");
});

$("#bt_Ota").on("click", function () {
    $("#md_modal").dialog({ title: "{{Mises-à-jour OTA}}" });
    $("#md_modal")
        .load("index.php?v=d&plugin=Abeille&modal=Abeille-OTA.modal")
        .dialog("open");
});

$("#bt_maintenancePage").on("click", function () {
    window.open("index.php?v=d&m=Abeille&p=AbeilleMaintenance");
});

$("#bt_graph").on("click", function () {
    window.open(
        "plugins/Abeille/desktop/php/AbeilleGraph.php?GraphType=LqiPerMeter&NE=All&NE2=None&Center=none&Cache=Cache&Data=LinkQualityDec&Hierarchy=All"
    );
});

/* Add a virtual remote control to given zigate number */
function createRemote(zgId) {
    console.log("createRemote(" + zgId + ")");
    var xmlhttpMQTTSendTimer = new XMLHttpRequest();
    xmlhttpMQTTSendTimer.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            xmlhttpMQTTSendTimerResult = this.responseText;
        }
    };

    xmlhttpMQTTSendTimer.open(
        "GET",
        "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&topic=CmdCreateAbeille" +
        zgId +
        "_zigate_createRemote",
        false
    ); // False pour bloquer sur la recuperation du fichier
    xmlhttpMQTTSendTimer.send();
    // location.reload(true);
    $("#div_alert").showAlert({
        message: "{{Une nouvelle Telecommande est en création.}}",
        level: "success",
    });
}

/* Check which equipements are selected for given zigate number (zgId).
   Returns: object {zgId:<zigateNb>, nb:<nbOfSelectedEq>, ids:[<arrayOfEqIds>]} */
function getSelectedEqs(zgId) {
    console.log("getSelectedEqs(" + zgId + ")");
    var selected = new Object();
    selected["zgId"] = zgId; // Zigate number
    selected["nb"] = 0; // Number of selected equipments
    selected["ids"] = new Array(); // Array of eq IDs
    selected["addrs"] = new Array(); // Array of eq short addresses
    eval("var eqZigate = JSON.parse(js_eqZigate" + zgId + ");"); // List of eq IDs for current zigate
    for (const [eqId2, eq] of Object.entries(eqZigate)) {
        var checked = document.getElementById(
            "idBeeChecked" + zgId + "-" + eq.id
        ).checked;
        if (checked == false) continue;

        selected["nb"]++;
        selected["ids"].push(eq.id);
        selected["addrs"].push(eq.addr);
    }
    console.log('selected["nb"]=' + selected["nb"]);
    return selected;
}

/* Removes selected equipments for given zigate nb from Jeedom DB only. Zigate is untouched. */
function removeBeesJeedom(zgId) {
    console.log("removeBeesJeedom(zgId=" + zgId + ")");

    /* Any selected ? */
    var sel = getSelectedEqs(zgId);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !");
        return;
    }
    var eqIdList = sel["ids"];
    console.log("eqIdList=" + eqIdList);

    var msg =
        "{{Vous êtes sur le point de supprimer les équipements selectionnés de Jeedom.";
    msg +=
        "<br><br>Si ils sont toujours dans le réseau, ils deviendront 'fantomes' et devraient être réinclus automatiquement au fur et à mesure de leur reveil et ce, tant qu'on ne les force pas à quitter le réseau.";
    msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false) return;

        // Collecting addresses before EQ is removed
        var eqAddrList = sel["addrs"];
        console.log("eqAddrList=" + eqAddrList);

        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
            data: {
                action: "removeEqJeedom",
                eqList: eqIdList,
            },
            dataType: "json",
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'removeEqJeedom' !");
            },
            success: function (json_res) {
                window.location.reload();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg =
                        "ERREUR ! Quelque chose s'est mal passé.\n" +
                        res.errors;
                    alert(msg);
                } else {
                    // Informing parser that some equipements have to be considered "phantom"
                    var xhr = new XMLHttpRequest();
                    xhr.open(
                        "GET",
                        "plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId=" +
                        js_queueXToParser +
                        "&msg=type:eqRemoved_net:Abeille" +
                        zgId +
                        "_eqList:" +
                        eqAddrList,
                        true
                    );
                    xhr.send();
                }
            },
        });
    });
}

/* Called when 'exclude' button is pressed
   Request device to leave the network (if battery powered, must be wake up). */
function removeBees(zgId) {
    console.log("removeBees(zgId=" + zgId + ")");

    eval("var eqZigate = JSON.parse(js_eqZigate" + zgId + ");"); // List of eq IDs for current zigate

    /* Any selected ? */
    var sel = getSelectedEqs(zgId);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !");
        return;
    }
    var eqIdList = sel["ids"];
    console.log("eqIdList=" + eqIdList);

    var msg =
        "{{Vous êtes sur le point de demander aux équipements selectionnés de quitter le réseau.";
    msg += "<br>- Si alimenté par secteur, cela devrait être immédiat.";
    msg +=
        "<br>- Si alimenté par pile, vous devez reveiller l'équipement immédiatement apres la requète mais cela n'est pas toujours possible.";
    msg +=
        "<br><br>D'autre part la plupart des équipements signalent qu'ils quittent le réseau (message \"xxx a quitté le réseau\") mais pas tous.";
    msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false) return;

        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
            data: {
                action: "removeEqZigbee",
                eqIdList: eqIdList,
            },
            dataType: "json",
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'removeEqZigbee' !");
            },
            success: function (json_res) {
                window.location.reload();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg =
                        "ERREUR ! Quelque chose s'est mal passé.\n" +
                        res.errors;
                    alert(msg);
                }
            },
        });
    });
}

/* Called when 'setTimeout' button is pressed
   Allows to modify timeout of selected equipements. */
function setBeesTimeout(zgId) {
    console.log("setBeesTimeout(zgId=" + zgId + ")");

    var sel = getSelectedEqs(zgId);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !");
        return;
    }
    $("#abeilleModal").dialog({
        title: "{{Modification du timeout}}",
        autoOpen: false,
        resizable: false,
        modal: true,
        height: 300,
        width: 400,
    });
    $("#abeilleModal")
        .load(
            "index.php?v=d&plugin=Abeille&modal=setBeesTimeout.abeille&zgId=" +
            zgId
        )
        .dialog("open");
}

/* Called when 'monitor' button is pressed */
function monitorIt(zgId, zgPort) {
    console.log("monitorIt(zgId=" + zgId + ", zpPort=" + zgPort + ")");

    var sel = getSelectedEqs(zgId);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !");
        return;
    }
    if (sel["nb"] != 1) {
        alert("Un seul équipement peut être surveillé à la fois !");
        return;
    }
    let eqId = sel["ids"][0];
    console.log("idToMonitor=" + eqId);

    $.ajax({
        type: "POST",
        // url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
        url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
        data: {
            action: "monitor",
            eqId: eqId,
        },
        dataType: "json",
        global: false,
        error: function (request, status, error) {
            bootbox.alert(
                "ERREUR 'monitor' !<br>" +
                "status=" +
                status +
                "<br>error=" +
                error
            );
        },
        success: function (json_res) {
            console.log(json_res);
            res = JSON.parse(json_res.result);
            if (res.status != 0) {
                var msg = "ERREUR ! Qqch s'est mal passé.\n" + res.error;
                alert(msg);
            } else window.location.reload();
        },
    });
}

//
function replaceEq() {
    console.log("replaceEq()");

    var deadId = $("#idDeadEq").val();
    const selectedDeadEq = document.querySelector("#idDeadEq");
    deadIdx = selectedDeadEq.selectedIndex;
    let deadSelectedOption = selectedDeadEq.options[deadIdx];
    split = deadSelectedOption.text.split(":");
    deadZgId = split[0].substring(7); // Zigate X => X
    deadHName = split[1];

    var newId = $("#idNewEq").val();
    const selectedNewEq = document.querySelector("#idNewEq");
    newIdx = selectedNewEq.selectedIndex;
    let newSelectedOption = selectedNewEq.options[newIdx];
    split = newSelectedOption.text.split(":");
    newZgId = split[0].substring(7); // Zigate X => X
    newHName = split[1];

    if (newId == deadId) {
        alert("Un équipement ne peut être remplacé par lui même.");
        return;
    }
    // Ensure same jsonId
    eval("var eqPerZigate = JSON.parse(js_eqPerZigate);");
    deadJsonId = eqPerZigate[deadZgId][deadId]["jsonId"];
    newJsonId = eqPerZigate[newZgId][newId]["jsonId"];
    if (deadJsonId != newJsonId) {
        msg = "Les équipements ne sont pas du même modèle.";
        msg += "\n- Equipement à remplacer: " + deadJsonId;
        msg += "\n- Remplacant: " + newJsonId;
        alert(msg);
        return;
    }

    var msg =
        "{{Vous souhaitez remplacer '" + deadHName + "' par " + newHName + ".";

    // if (dstZgId == srcZgId) {
    //     msg += "\n\nMais.. ca ne fait aucun sens.";
    //     msg += "\nCet équipement est déja sur la bonne zigate.";
    //     return alert(msg);
    // }

    msg +=
        "<br><br>L'équipement mort va recevoir les adresses du nouvel équipement.";
    msg += "<br>Tout l'historique de l'équipement mort sera ainsi préservé.";
    msg += "<br><br>On y va ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false) return;

        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
            data: {
                action: "replaceEq",
                deadEqId: deadId,
                newEqId: newId,
            },
            dataType: "json",
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'replaceEq' !");
            },
            success: function (json_res) {
                window.location.reload();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg =
                        "ERREUR ! Quelque chose s'est mal passé (replaceEq).\n" +
                        res.errors;
                    alert(msg);
                }
            },
        });
    });
}

/* Migrate an equipment to another zigate. */
function migrateEq() {
    console.log("migrateEq()");

    const selectedEq = document.querySelector("#idEq");
    index = selectedEq.selectedIndex;
    let eqId = $("#idEq").val();
    let selectedOption = selectedEq.options[index];
    const selectedText = selectedOption.text;
    txtArr = selectedText.split(":");
    srcZgId = txtArr[0].substr(7);
    eqHName = txtArr[1];

    var dstZgId = $("#idDstZg").val();

    var msg =
        "{{Vous souhaitez migrer '" +
        eqHName +
        "' vers la zigate " +
        dstZgId +
        ".";

    if (dstZgId == srcZgId) {
        msg += "\n\nMais.. ca ne fait aucun sens.";
        msg += "\nCet équipement est déja sur la bonne zigate.";
        return alert(msg);
    }

    msg += "<br><br>La procédure est la suivante:";
    msg += "<br>- Activation du mode inclusion pour la zigate " + dstZgId + ".";
    msg +=
        "<br>- Demande de sortie du réseau à l'équipement. Il devrait alors rechercher un nouveau réseau avec qui s'associer et donc rejoindre celui de la zigate " +
        dstZgId +
        ".";
    msg +=
        "<br><br>Si l'équipement est alimenté par pile, vous devez le reveiller immédiatement apres la requète mais cela n'est pas toujours possible.";
    msg +=
        "<br><br>Certains équipements ne quittent pas ou rejoignent pas automatiquement un réseau même si on en fait la demande. Dans ce cas une réinclusion sera nécéssaire.";
    msg += "<br><br>On tente l'experience ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false) return;

        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
            data: {
                action: "migrate",
                eqId: eqId,
                dstZgId: dstZgId,
            },
            dataType: "json",
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'migrateEq' !");
            },
            success: function (json_res) {
                window.location.reload();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg =
                        "ERREUR ! Quelque chose s'est mal passé (migrateEq).\n" +
                        res.errors;
                    alert(msg);
                }
            },
        });
    });
}

/* Attempt to detect devices on network but unknown to Jeedom. */
function recoverDevices() {
    console.log("recoverDevices()");

    $("#md_modal").dialog({ title: "{{Récupération d'équipements fantômes}}" });
    $("#md_modal")
        .load("index.php?v=d&plugin=Abeille&modal=AbeilleRecovery.modal")
        .dialog("open");
}

/* Confirm unknown zigate must be accepted. */
function acceptNewZigate() {
    console.log("acceptNewZigate()");

    var zgId = $("#idNewZigate").val();
    $.ajax({
        type: "POST",
        url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
        data: {
            action: "acceptNewZigate",
            zgId: zgId,
        },
        dataType: "json",
        global: false,
        error: function (request, status, error) {
            bootbox.alert("ERREUR 'acceptNewZigate' !");
        },
        success: function (json_res) {
            window.location.reload();
            res = JSON.parse(json_res.result);
            if (res.status != 0) {
                var msg =
                    "ERREUR ! Quelque chose s'est mal passé (acceptNewZigate).\n" +
                    res.errors;
                alert(msg);
            }
        },
    });
}

$("#idEqAssistBtn").on("click", function () {
    window.open("index.php?v=d&m=Abeille&p=AbeilleEqAssist&id=" + curEqId);
});

/* Launch AbeilleRepair */
$("#idRepairBtn").on("click", function () {
    console.log("repair(eqId=" + eqId + ")");

    var xhttp = new XMLHttpRequest();
    xhttp.open(
        "GET",
        "/plugins/Abeille/core/php/AbeilleRepair.php?eqId=" + curEqId,
        false
    );
    xhttp.send();

    xhttp.onreadystatechange = function () { };

    // $.ajax({
    //     url: "/plugins/Abeille/core/php/AbeilleRepair.php?eqId=" + eqId,
    //     async: true,
    //     error: function (jqXHR, status, error) {
    //         console.log("repair() error status: " + status);
    //         console.log("repair() error msg: " + error);
    //     },
    //     success: function (data, status, jqhr) {
    //         //console.log("refreshLqiTable success status: " + status);
    //         //console.log("refreshLqiTable success msg: " + data);
    //     },
    // });
});

// Update Jeedom equipement from model
$("#idUpdateBtn").on("click", function () {
    console.log("update(" + curEqId + ")");
    eqId = curEqId;

    var msg = "{{Vous êtes sur le point de:<br>";
    msg += "- Mettre à jour l'équipement Jeedom à partir de son modèle<br>";
    msg += "- Et reconfigurer l'équipement<br>";
    msg += "<br>Les noms et ID sont conservés, ainsi que vos customisations.}}";
    if (eqBatteryType != "") {
        msg +=
            "<br><br>{{ATTENTION! Comme il fonctionne sur batterie, il vous faut le réveiller immédiatement après avoir cliqué sur 'Ok'.}}";
    }
    msg += "<br><br>{{Cliquez 'Ok' continuer}}";
    bootbox.confirm(msg, function (result) {
        if (result == false) return;

        var xhttp = new XMLHttpRequest();
        xhttp.open(
            "GET",
            "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=update&eqId=" +
            eqId,
            false
        );
        xhttp.send();
    });
});

/* Reinit Jeedom device & reconfigure.
    WARNING: If battery powered, device must be wake up. */
$("#idReinitBtn").on("click", function () {
    console.log("reinit(" + curEqId + ")");
    eqId = curEqId;

    var msg = "{{Vous êtes sur le point de:}}<br>";
    msg +=
        "- Réinitialiser cet équipement (équivalent à une nouvelle inclusion).<br>";
    msg += "- Et le reconfigurer.<br>";
    msg +=
        "<br>Tout sera remis à jour à partir du modèle JSON excepté le nom, l'ID Jeedom ainsi que ses adresses.<br>";
    msg +=
        "Le nom des commandes peut avoir changé et vous serez obligé de revoir les scénaris utilisant cet équipement.<br>";

    if (eqBatteryType != "") {
        msg +=
            "<br>{{ATTENTION! Comme il fonctionne sur batterie, il vous faut le réveiller immédiatement après avoir cliqué sur 'Ok'.}}<br>";
    }
    msg += "<br>{{Etes vous sur de vouloir continuer ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false) return;

        var xhttp = new XMLHttpRequest();
        xhttp.open(
            "GET",
            "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=reinit&eqId=" +
            eqId,
            false
        );
        xhttp.send();
    });
});


/**
 * Changement de modèle (choix manuel du modèle dans la liste des JSON).
 * @author JB Romain 16/08/2023
 */
$("#idModelChangeBtn").on("click", function () {
    console.log("Demande changement de modèle", curEqId);

    // Ouverture dialog
    var myPopup = jeeDialog.dialog({
        id: 'abeille_modelChangePopup',
        title: '{{Choisir le modèle de votre équipement}}',
        width: 500,
        height: 'auto',
        contentUrl: ''
    });

    // Le template de contenu est dans Abeille-Eq-Advanced-Device.php
    var $content = $(myPopup).find(".jeeDialogContent");
    $content.append($(".abeille-model-change-popup-content").clone().show());
    var $datalist = $("#abeille-all-models-list");

    // Requete ajax pour remplir la liste des options (= liste des modèles connus)
    $.ajax({
        type: "POST",
        url: "plugins/Abeille/core/ajax/AbeilleModelChange.ajax.php",
        data: {
            action: "getModelChoiceList",
            eqId: curEqId
        },
        dataType: "json",
        global: false,
        success: function (lstModels) {
            console.log("réponse ajax getModelChoiceList", lstModels);

            // On remplit la liste de choix (datalist html5)

            Object.values(lstModels).forEach(model => {
                var str = '';
                // Signature Zigbee
                if (typeof (model.manufacturer) == 'string' && model.manufacturer != '') {
                    str += '[' + model.manufacturer + '] ';
                }

                if (typeof (model.model) == 'string' && model.model != '' && model.model != '?') {
                    str += model.model + ' ';
                }

                // Libellé
                if (str != '') {
                    str += '> ';
                }
                str += model.type;

                // Identifiant JSON (incluant l'emplacement)
                str += ' (' + model.jsonLocation + '/' + model.jsonId + '.json)';

                // Ajout à la liste
                var $opt = $("<option></option>");
                $opt.attr("value", str);
                $datalist.append($opt);

                // Remplissage info s'il s'agit du modèle actuellement en vigueur pour l'équipement
                if (typeof (model.isCurrent) == 'boolean' && model.isCurrent) {
                    $content.find("span.current-model").html(str);
                }

            });
        },
    });

    // Bouton annuler
    $content.find(".btn-secondary").on("click", function () {
        jeeDialog.get('#abeille_modelChangePopup').destroy();
    });

    // Bouton enregistrer
    $content.find(".btn-success").on("click", function () {
        // On vérifie que l'utilisateur a bien choisi un modèle à appliquer
        var strSaisie = $content.find("input[type=search]").val();
        if ($datalist.find("option[value=\"" + strSaisie + "\"]").length == 0) {
            jeeDialog.alert('{{Erreur: vous devez choisir un modèle dans la liste.}}');
            return;
        }

        // Demande de confirmation (+ injonction à réveiller l'équipement s'il est sur batterie)
        var strSuppBatterie = '';
        if (eqBatteryType != '') {
            strSuppBatterie = "<br><br><strong>Attention: </strong>{{Comme cet équipement fonctionne sur batterie, vous devez le réveiller immédiatement après avoir cliqué sur OK.}}";
        }
        jeeDialog.confirm("{{L'équipement sera reconfiguré à partir du modèle choisi. Souhaitez-vous vraiment appliquer ce modèle ?}}" + strSuppBatterie, function (result) {
            if (result) {
                // On ferme la boite de dialog
                jeeDialog.get('#abeille_modelChangePopup').destroy();

                // Première requête: enregistrer la configuration de l'équipement (choix modèle)
                $.ajax({
                    type: "POST",
                    url: "plugins/Abeille/core/ajax/AbeilleModelChange.ajax.php",
                    data: {
                        action: "setModelToDevice",
                        eqId: curEqId,
                        modelChoice: strSaisie
                    },
                    dataType: "json",
                    global: false,
                    success: function () {
                        // Deuxième requête: réinitialisation de l'équipement à partir de son (nouveau) modèle
                        // (comme si on avait cliqué sur le bouton mise à jour)
                        console.log("Simulation clic sur Mise à jour...");
                        $("#idUpdateBtn").trigger("click");
                    }
                });
            }
        })

    });


});

/**
 * Lien pour restaurer le modèle "automatique"
 */
$("body").on("click", "a#linkRestoreAutoModel", function () {
    jeeDialog.confirm("{{Actuellement, le modèle utilisé pour configuré l'équipement est celui que vous avez choisi manuellement. Cette action permet de rétablir le fonctionnement normal d'Abeille: le modèle prédéfini sera utilisé pour reconfigurer l'équipement la prochaine fois qu'il se réannoncera.<br><br>Cette action, en elle-même, ne modifie pas la configuration de l'équipement: après avoir cliqué sur OK, patientez quelques secondes, puis forcez l'équipement à se réannoncer (en le débranchant/rebranchant par exemple), ou utilisez la fonction 'Mise à jour'.<br><br>Etes-vous sûr de vouloir annuler le choix manuel du modèle ?}}", function (result) {
        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/AbeilleModelChange.ajax.php",
            data: {
                action: "disableManualModelForDevice",
                eqId: curEqId
            },
            dataType: "json",
            global: false,
            success: function () {
                // On laisse le temps à l'utilisateur de lire le message avant d'actualiser la page
                console.log("disableManualModelForDevice OK");
                setTimeout(function () {
                    document.location.reload();
                }, 3000);
            }
        });
    });

    return false; // prevent default
});

/* Save given Abeille config (=> 'config' DB) */
function saveConfig(config) {
    console.log("saveConfig(): config=", config);

    $.ajax({
        type: "POST",
        url: "plugins/Abeille/core/ajax/Abeille.ajax.php",
        data: {
            action: "saveConfig",
            config: JSON.stringify(config),
        },
        dataType: "json",
        global: false,
        success: function (json_res) { },
    });
}

$("#idEqIcon").change(function () {
    var text = "plugins/Abeille/images/node_" + $("#idEqIcon").val() + ".png";
    //$("#icon_visu").attr('src',text);
    document.icon_visu.src = text;
});

/* Check which equipements are selected.
    Returns: array of objects {zgId, addr} */
function getSelected() {
    console.log("getSelected()");

    // Get all eq infos
    eval("var eqPerZigate = JSON.parse(js_eqPerZigate);");
    console.log("eqPerZigate=", eqPerZigate);

    var selected = new Array();
    // var list = document.querySelectorAll('input[type=checkbox]');
    var list = document.querySelectorAll("beeChecked"); // class="beeChecked" on each Jeedom equipement
    for (i = 0; i < list.length; i++) {
        item = list[i];
        // console.log("item.id=", item.id);
        if (!item.checked) continue;

        // console.log(item.id+" CHECKED");
        console.log("CHECKED item=", item);
        idSplit = item.id.split("-"); // idBeeCheckedX-Y => [idBeeCheckedX, Y]
        id = idSplit[1];
        // console.log("id=", id);
        zgId = idSplit[0].substring(12);
        // console.log("CHECKED: zgId="+zgId+", id="+id);

        var eq = new Object();
        eq["id"] = id;
        eq["zgId"] = zgId;
        eq["addr"] = eqPerZigate[zgId][id]["addr"];
        eq["mainEp"] = eqPerZigate[zgId][id]["mainEp"];
        selected.push(eq);
    }
    return selected;
}

/* Send a command to zigate thru 'AbeilleCmd' */
function sendZigate(action, param) {
    console.log("sendZigate(" + action + ", " + param + ")");

    function sendToZigate(topic, payload) {
        $.ajax({
            type: "POST",
            url: "plugins/Abeille/core/ajax/AbeilleZigate.ajax.php",
            data: {
                action: "sendMsgToCmd",
                topic: topic,
                payload: payload,
            },
            dataType: "json",
            global: false,
            error: function (request, status, error) {
                bootbox.alert(
                    "ERREUR 'sendMsgToCmd' !<br>Votre installation semble corrompue."
                );
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                console.log("status=" + res.status);
                if (res.status != 0) console.log("error=" + res.error);
            },
        });
    }

    var topic = "";
    var payload = "";

    switch (action) {
        case "setLED":
            topic = "CmdAbeille" + zgId + "/0000/setZgLed";
            if (param == "ON") payload = "value=1";
            else payload = "value=0";
            break;
        case "setCertif":
            if (param == "CE")
                topic = "CmdAbeille" + zgId + "/0000/setCertificationCE";
            else topic = "CmdAbeille" + zgId + "/0000/setCertificationFCC";
            break;
        case "startNetwork": // Not required for end user but for developper.
            topic = "CmdAbeille" + zgId + "/0000/startZgNetwork";
            payload = "";
            break;
        case "startNetworkScan": // Not required for end user but for developper.
            topic = "CmdAbeille" + zgId + "/0000/startZgNetworkScan";
            payload = "";
            break;
        case "setInclusion":
            topic = "CmdAbeille" + zgId + "/0000/setZgPermitMode";
            payload = "mode=" + param;
            break;
        case "setMode":
            topic = "CmdAbeille" + zgId + "/0000/setZgMode";
            if (param == "Normal") payload = "mode=normal";
            else if (param == "Raw") payload = "mode=raw";
            else payload = "mode=hybrid";
            break;
        case "setExtPANId": // Not critical. No need so far.
            topic = "CmdAbeille" + zgId + "/0000/setExtendedPANID";
            payload = "";
            break;
        // case "setChannelMask":
        //     mask = document.getElementById("idChannelMask").value;
        //     console.log("mask="+mask);
        //     if (mask == "") {
        //         alert("Masque vide.\nVeuillez entrer une valeur entre 800 (canal 11) et 07FFF800 (canaux 11 à 26).");
        //         return; // Empty
        //     }
        //     if (mask.length > 1)
        //         mask = mask.replace(/^0+/, ''); // Remove leading zeros
        //     function isHex(h) {
        //         // return /^[A-F0-9]+$/i.test(h)
        //         var a = parseInt(h,16);
        //         return (a.toString(16) ===h.toLowerCase())
        //     }
        //     if (!isHex(mask)) {
        //         alert("Le masque doit être une valeur hexa");
        //         return;
        //     }
        //     var maskI = parseInt(mask, 16); // Convert hex string to number
        //     if ((maskI & 0x7fff800) == 0) {
        //         alert("Aucun canal actif entre 11 et 26.\nVeuillez entrer une valeur entre 800 (canal 11) et 07FFF800 (canaux 11 à 26).")
        //         return;
        //     }
        //     if ((maskI & ~0x7fff800) != 0) {
        //         alert("Les canaux inférieurs à 11 et supérieurs à 26 sont invalides.\nVeuillez entrer une valeur entre 800 (canal 11) et 07FFF800 (canaux 11 à 26).")
        //         return;
        //     }
        //     mask = mask.toString(16);
        //     topic = 'CmdAbeille'+zgId+'/0000/setZgChannelMask';
        //     payload = 'mask='+mask;
        //     break;
        case "setChannel":
            msg =
                "{{Vous êtes sur le point de changer le canal Zigbee de la Zigate}}<b>" +
                zgId +
                "</b>";
            msg +=
                "<br><br>{{Les équipements sur secteur devraient suivre mais pas forcément tous ceux sur batterie.}}";
            msg +=
                "<br>{{Dans ce cas vous pourriez avoir à refaire une inclusion de ces équipements uniquement.}}";
            msg += "<br><br>{{Etes vous sur de vouloir continuer ?}}";
            bootbox.confirm(msg, function (result) {
                if (result) {
                    var chan = $("#idZgChan").val();
                    if (chan == 0) mask = 0x7fff800; // All channels = auto
                    else mask = 1 << chan;
                    mask = mask.toString(16);
                    // Note: missing leading 0 completed in AbeilleCmdProcess.
                    // while (mask.length < 8) { // Adding missing leading 0
                    //     mask = "0" + mask;
                    // }
                    console.log("  Channel=" + chan + " => mask=" + mask);

                    // Request ALL devices to change channel
                    // But WARNING.. those who are not RxONWhenIdle may not receive it.
                    topic = "CmdAbeille" + zgId + "/FFFF/mgmtNetworkUpdateReq";
                    payload = "scanChan=" + mask + "&scanDuration=FE";
                    sendToZigate(topic, payload);

                    topic = "CmdAbeille" + zgId + "/0000/setZgChannelMask";
                    payload = "mask=" + mask;
                    sendToZigate(topic, payload);

                    config = new Object();
                    config["ab::zgChan" + zgId] = chan;
                    console.log("config=", config);
                    saveConfig(config);

                    topic = "CmdAbeille" + zgId + "/0000/startZgNetwork";
                    payload = "";
                    sendToZigate(topic, payload);
                }
                return;
            });

            break;
        case "getTXPower":
            topic = "CmdAbeille" + zgId + "/0000/getZgTxPower";
            payload = "";
            break;
        case "setTXPower":
            topic = "CmdAbeille" + zgId + "/0000/TxPower";
            payload = "ff"; // TODO
            break;
        case "getTime":
            topic = "CmdAbeille" + zgId + "/0000/getZgTimeServer";
            payload = "";
            break;
        case "setTime":
            topic = "CmdAbeille" + zgId + "/0000/setZgTimeServer";
            payload = ""; // Using current time from host.
            break;
        case "erasePersistantDatas": // Erase PDM
            msg =
                "{{Vous êtes sur le point de d'effacer la PDM de la zigate}} <b>" +
                zgId +
                "</b>";
            msg +=
                "{{<br>Tous les équipements connus de la zigate seront perdus et devront être réinclus.}}";
            msg +=
                "{{<br>Si ils existent encore côté Jeedom ils passeront vite en time-out.}}";
            msg += "{{<br><br>Etes vous sur de vouloir continuer ?}}";
            bootbox.confirm(msg, function (result) {
                if (result)
                    sendToZigate(
                        "CmdAbeille" + zgId + "/0000/ErasePersistentData",
                        "ErasePersistentData"
                    );
                return;
            });
            break;
        case "getInclusionStatus":
            topic = "CmdAbeille" + zgId + "/0000/permitJoin";
            payload = "Status";
            break;
        case "getZgVersion":
            topic = "CmdAbeille" + zgId + "/0000/getZgVersion";
            payload = "";
            break;
        case "resetZigate":
            topic = "CmdAbeille" + zgId + "/0000/resetZg";
            payload = "";
            break;
        default:
            console.log("ERROR: Unsupported action '" + action + "'");
            return; // Nothing to do
    }

    if (topic != "") sendToZigate(topic, payload);
}

// Send msg to AbeilleCmd
function sendToCmd(action, param1 = "", param2 = "", param3 = "", param4 = "") {
    console.log("sendToCmd(" + action + ")");

    selected = getSelected();
    console.log("selected=", selected);

    function sendCmd(topic, payload) {
        var xhr = new XMLHttpRequest();
        msg =
            "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId=" +
            js_queueXToCmd;
        topic = topic.replaceAll("&", "_");
        payload = payload.replaceAll("&", "_");
        if (payload != "")
            xhr.open(
                "GET",
                msg + "&topic=" + topic + "&payload=" + payload,
                false
            );
        else xhr.open("GET", msg + "&topic=" + topic, false);
        xhr.send();
    }

    switch (action) {
        case "getGroups":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            selected.forEach((eq) => {
                sendCmd(
                    "CmdAbeille" +
                    eq["zgId"] +
                    "/" +
                    eq["addr"] +
                    "/getGroupMembership",
                    "ep=" + eq["mainEp"]
                );
                setTimeout(function () {
                    location.reload(true);
                }, 1000);
            });
            break;
        case "addGroup":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == "") return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd(
                    "CmdAbeille" + eq["zgId"] + "/" + eq["addr"] + "/addGroup",
                    "ep=" + eq["mainEp"] + "&group=" + group
                );
                setTimeout(function () {
                    sendCmd(
                        "CmdAbeille" +
                        eq["zgId"] +
                        "/" +
                        eq["addr"] +
                        "/getGroupMembership",
                        "ep=" + eq["mainEp"]
                    );
                    location.reload(true);
                }, 1000);
            });
            break;
        case "removeGroup":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == "") return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd(
                    "CmdAbeille" + eq["zgId"] + "/0000/removeGroup",
                    "address=" +
                    eq["addr"] +
                    "&DestinationEndPoint=" +
                    eq["mainEp"] +
                    "&groupAddress=" +
                    group
                );
                setTimeout(function () {
                    sendCmd(
                        "CmdAbeille" +
                        eq["zgId"] +
                        "/" +
                        eq["addr"] +
                        "/getGroupMembership",
                        "ep=" + eq["mainEp"]
                    );
                    location.reload(true);
                }, 1000);
            });
            break;
        case "getGroups2":
            zgId = param1;
            addr = param2;
            ep = param3;
            sendCmd(
                "CmdAbeille" + zgId + "/" + addr + "/getGroupMembership",
                "ep=" + ep
            );
            setTimeout(function () {
                location.reload(true);
            }, 1000);
            break;
        case "removeGroup2":
            zgId = param1;
            addr = param2;
            ep = param3;
            group = param4;
            sendCmd(
                "CmdAbeille" + zgId + "/" + addr + "/removeGroup",
                "address=" +
                addr +
                "&DestinationEndPoint=" +
                ep +
                "&groupAddress=" +
                group
            );
            setTimeout(function () {
                location.reload(true);
            }, 1000);
            break;
        case "removeAllGroups":
            zgId = param1;
            addr = param2;
            ep = param3;
            sendCmd(
                "CmdAbeille" + zgId + "/" + addr + "/removeAllGroups",
                "ep=" + ep
            );
            sendCmd(
                "CmdAbeille" + zgId + "/" + addr + "/getGroupMembership",
                "ep=" + ep
            );
            setTimeout(function () {
                location.reload(true);
            }, 1000);
            break;
        case "setGroupRemote":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == "") return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd(
                    "CmdAbeille" + eq["zgId"] + "/0000/commissioningGroupAPS",
                    "address=" + eq["addr"] + "&groupId=" + group
                );
                setTimeout(function () {
                    sendCmd(
                        "CmdAbeille" +
                        eq["zgId"] +
                        "/" +
                        eq["addr"] +
                        "/getGroupMembership",
                        "ep=" + eq["mainEp"]
                    );
                    location.reload(true);
                }, 1000);
            });
            break;
        case "setGroupRemoteLegrand":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == "") return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd(
                    "CmdAbeille" +
                    eq["zgId"] +
                    "/0000/commissioningGroupAPSLegrand",
                    "address=" + eq["addr"] + "&groupId=" + group
                );
                setTimeout(function () {
                    sendCmd(
                        "CmdAbeille" +
                        eq["zgId"] +
                        "/" +
                        eq["addr"] +
                        "/getGroupMembership",
                        "ep=" + eq["mainEp"]
                    );
                    location.reload(true);
                }, 1000);
            });
            break;
        case "startPermitJoin":
            zgId = param1;
            sendCmd(
                "CmdAbeille" + zgId + "/0000/setZgPermitMode",
                "mode=start"
            );
            location.reload(true);
            $("#div_alert").showAlert({
                message:
                    "{{Mode inclusion demandé. La zigate " +
                    zgId +
                    " clignoter pendant 4 minutes.}}",
                level: "success",
            });
            break;
        case "stopPermitJoin":
            zgId = param1;
            sendCmd("CmdAbeille" + zgId + "/0000/setZgPermitMode", "mode=stop");
            location.reload(true);
            $("#div_alert").showAlert({
                message:
                    "{{Arret mode inclusion demandé. La zigate " +
                    zgId +
                    " doit arreter de clignoter.}}",
                level: "success",
            });
            break;
    }
}

function interrogate(request) {
    console.log("interrogate(" + request + ")");

    logicalId = "Abeille" + zgId + "_" + eqAddr;
    if (request == "getRoutingTable") {
        topic = "Cmd" + logicalId + "_getRoutingTable";
        payload = "";
    } else if (request == "getBindingTable") {
        topic = "Cmd" + logicalId + "_getBindingTable";
        payload = "address=" + eqAddr;
    } else if (request == "getNeighborTable") {
        topic = "Cmd" + logicalId + "_getNeighborTable";
        startIdx = document.getElementById("idStartIdx").value;
        payload = "startIndex=" + startIdx;
    } else if (request == "getActiveEndPoints") {
        topic = "Cmd" + logicalId + "_getActiveEndpoints";
        payload = "addr=" + eqAddr;
    } else if (request == "getSimpleDescriptor") {
        topic = "Cmd" + logicalId + "_getSimpleDescriptor";
        ep = document.getElementById("idEpSDR").value;
        payload = "ep=" + ep;
    } else if (request == "getNodeDescriptor") {
        topic = "Cmd" + logicalId + "_getNodeDescriptor";
        payload = "";
    } else if (request == "getIeeeAddress") {
        topic = "Cmd" + logicalId + "_getIeeeAddress";
        payload = "";
    } else if (request == "mgmtNetworkUpdateReq") {
        topic = "Cmd" + logicalId + "_mgmtNetworkUpdateReq";
        scanChan = document.getElementById("idMgmtNwkUpdReqSC").value;
        scanDuration = document.getElementById("idMgmtNwkUpdReqSD").value;
        payload = "";
        if (scanChan != "") payload += "scanChan=" + scanChan;
        if (scanDuration != "") {
            if (payload != "") payload += "_";
            payload += "scanDuration=" + scanDuration;
        }
    } else if (request == "leaveRequest") {
        topic = "Cmd" + logicalId + "_LeaveRequest";
        payload = "IEEE=" + js_eqIeee;
    } else if (request == "readReportingConfig") {
        topic = "Cmd" + logicalId + "_readReportingConfig";
        ep = document.getElementById("idEp").value;
        clustId = document.getElementById("idClustId").value;
        attrId = document.getElementById("idAttrId").value;
        payload =
            "addr=" +
            eqAddr +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_attrId=" +
            attrId;
    } else if (request == "readAttribute") {
        topic = "Cmd" + logicalId + "_readAttribute";
        ep = document.getElementById("idEpA").value;
        clustId = document.getElementById("idClustIdA").value;
        attrId = document.getElementById("idAttrIdA").value;
        payload = "ep=" + ep + "_clustId=" + clustId + "_attrId=" + attrId;
        manufId = document.getElementById("idManufIdRA").value;
        if (manufId != "") payload += "_manufId=" + manufId;
    } else if (request == "writeAttribute") {
        topic = "Cmd" + logicalId + "_writeAttribute";
        ep = document.getElementById("idEpWA").value;
        clustId = document.getElementById("idClustIdWA").value;
        attrId = document.getElementById("idAttrIdWA").value;
        value = document.getElementById("idValueWA").value;
        payload =
            "ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_attrId=" +
            attrId +
            "_attrVal=" +
            value;
        attrType = document.getElementById("idAttrTypeWA").value;
        if (attrType != "FF") payload += "_attrType=" + attrType;
        dir = document.getElementById("idDirWA").value;
        if (dir != "") payload += "_dir=" + dir;
        manufId = document.getElementById("idManufIdWA").value;
        if (manufId != "") payload += "_manufId=" + manufId;
    } else if (request == "writeAttribute0530") {
        topic = "Cmd" + logicalId + "_writeAttribute0530";
        ep = document.getElementById("idEpWA2").value;
        clustId = document.getElementById("idClustIdWA2").value;
        attrId = document.getElementById("idAttrIdWA2").value;
        value = document.getElementById("idValueWA2").value;
        payload =
            "ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_attrId=" +
            attrId +
            "_attrVal=" +
            value;
        attrType = document.getElementById("idAttrTypeWA2").value;
        if (attrType != "FF") payload += "_attrType=" + attrType;
        dir = document.getElementById("idDirWA2").value;
        if (dir != "") payload += "_dir=" + dir;
    } else if (request == "discoverCommandsReceived") {
        topic = "Cmd" + logicalId + "_discoverCommandsReceived";
        ep = document.getElementById("idEpB").value;
        clustId = document.getElementById("idClustIdB").value;
        // start = document.getElementById("idStartB").value;
        start = "00";
        payload =
            "addr=" +
            eqAddr +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_start=" +
            start;
    } else if (request == "discoverAttributesExt") {
        topic = "Cmd" + logicalId + "_discoverAttributesExt";
        ep = document.getElementById("idEpC").value;
        clustId = document.getElementById("idClustIdC").value;
        // start = document.getElementById("idStartC").value;
        startId = "0000";
        payload = "ep=" + ep + "_clustId=" + clustId + "_startId=" + startId;
    } else if (request == "discoverAttributes") {
        topic = "Cmd" + logicalId + "_discoverAttributes";
        ep = document.getElementById("idEpD").value;
        clustId = document.getElementById("idClustIdD").value;
        // start = document.getElementById("idStartD").value;
        start = "00";
        payload =
            "addr=" +
            eqAddr +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_start=" +
            start;
    } else if (request == "bindToDevice") {
        topic = "Cmd" + logicalId + "_bind0030";
        ep = document.getElementById("idEpE").value;
        clustId = document.getElementById("idClustIdE").value;
        destIeee = document.getElementById("idIeeeE").value;
        destEp = document.getElementById("idEpE2").value;
        payload =
            "addr=" +
            js_eqIeee +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_destAddr=" +
            destIeee +
            "_destEp=" +
            destEp;
    } else if (request == "unbindToDevice") {
        topic = "Cmd" + logicalId + "_unbind0031";
        ep = document.getElementById("idEpSrc-UBD").value;
        clustId = document.getElementById("idClustId-UBD").value;
        destIeee = document.getElementById("idAddr-UBD").value;
        destEp = document.getElementById("idEpDst-UBD").value;
        payload =
            "addr=" +
            js_eqIeee +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_destAddr=" +
            destIeee +
            "_destEp=" +
            destEp;
    } else if (request == "bindToGroup") {
        topic = "Cmd" + logicalId + "_bind0030";
        ep = document.getElementById("idEpF").value;
        clustId = document.getElementById("idClustIdF").value;
        destGroup = document.getElementById("idGroupF").value;
        payload =
            "addr=" +
            js_eqIeee +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_destAddr=" +
            destGroup;
    } else if (request == "unbindToGroup") {
        topic = "Cmd" + logicalId + "_unbind0031";
        ep = document.getElementById("idEpSrc-UBG").value;
        clustId = document.getElementById("idClustId-UBG").value;
        destGroup = document.getElementById("idGroup-UBG").value;
        payload =
            "addr=" +
            js_eqIeee +
            "_ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_destAddr=" +
            destGroup;
    } else if (request == "configureReporting") {
        topic = "Cmd" + logicalId + "_configureReporting";
        ep = document.getElementById("idEpCR").value;
        clustId = document.getElementById("idClustIdCR").value;
        attrId = document.getElementById("idAttrIdCR").value;
        manufId = document.getElementById("idManufIdCR").value;
        attrType = document.getElementById("idAttrTypeCR").value;
        min = document.getElementById("idMinCR").value;
        max = document.getElementById("idMaxCR").value;
        change = document.getElementById("idChangeCR").value;
        payload = "ep=" + ep + "_clustId=" + clustId + "_attrId=" + attrId;
        if (min != "") payload += "_minInterval=" + min;
        if (max != "") payload += "_maxInterval=" + max;
        if (change != "") payload += "_changeVal=" + change;
        if (attrType != "FF") payload += "_attrType=" + attrType;
        if (manufId != "") payload += "_manufId=" + manufId;
    } else if (request == "configureReporting2") {
        // minInterval/maxInterval & changeVal as numeric, no longer hex string
        topic = "Cmd" + logicalId + "_configureReporting2";
        ep = document.getElementById("idEpCR2").value;
        clustId = document.getElementById("idClustIdCR2").value;
        attrId = document.getElementById("idAttrIdCR2").value;
        manufCode = document.getElementById("idManufCodeCR2").value;
        attrType = document.getElementById("idAttrTypeCR2").value;
        min = document.getElementById("idMinCR2").value;
        max = document.getElementById("idMaxCR2").value;
        change = document.getElementById("idChangeCR2").value;
        payload = "ep=" + ep + "_clustId=" + clustId + "_attrId=" + attrId;
        if (min != "") payload += "_minInterval=" + min;
        if (max != "") payload += "_maxInterval=" + max;
        if (change != "") payload += "_changeVal=" + change;
        if (attrType != "FF") payload += "_attrType=" + attrType;
        if (manufCode != "") payload += "_manufCode=" + manufCode;
    } else if (request == "0000-ResetToFactory") {
        /* Cluster specific commands */
        topic = "Cmd" + logicalId + "_cmd-0000";
        ep = document.getElementById("idEpG").value;
        payload = "ep=" + ep + "_cmd=00";
    } else if (request == "0003-Identify") {
        topic = "Cmd" + logicalId + "_identifySend";
        ep = document.getElementById("idEp-IS").value;
        payload = "address=" + eq["addr"] + "_EP=" + ep;
    } else if (request == "0004-AddGroup") {
        topic = "Cmd" + logicalId + "_addGroup";
        ep = document.getElementById("idEp-AG").value;
        group = document.getElementById("idGroup-AG").value;
        payload = "ep=" + ep + "_group=" + group;
    } else if (request == "0004-GetGroupMembership") {
        topic = "Cmd" + logicalId + "_getGroupMembership";
        ep = document.getElementById("idEp-GGM").value;
        payload = "ep=" + ep;
    } else if (request == "0004-RemoveAllGroups") {
        topic = "Cmd" + logicalId + "_removeAllGroups";
        ep = document.getElementById("idEp-RAG").value;
        payload = "ep=" + ep;
    } else if (request == "0201-SetPoint") {
        topic = "Cmd" + logicalId + "_cmd-0201";
        ep = document.getElementById("idEpC0201-00").value;
        payload = "ep=" + ep + "_cmd=00";
        amount = document.getElementById("idAmountC0201-00").value;
        if (amount != "") payload += "_amount=" + amount;
    } else if (request == "0300-MoveToColor") {
        topic = "Cmd" + logicalId + "_setColour";
        ep = document.getElementById("idEp-MTC").value;
        X = document.getElementById("idX-MTC").value;
        Y = document.getElementById("idX-MTC").value;
        payload = "EP=" + ep + "_X=" + X + "_Y=" + Y;
    } else if (request == "0502-StartWarning") {
        topic = "Cmd" + logicalId + "_cmd-0502";
        ep = document.getElementById("idEp-SW").value;
        mode = document.getElementById("idMode-SW").value;
        strobe = document.getElementById("idStrobe-SW").checked;
        sirenl = document.getElementById("idSirenL-SW").value;
        duration = document.getElementById("idDuration-SW").value;
        if (strobe) strobe = "on";
        else strobe = "off";
        payload = "ep=" + ep + "_cmd=00_mode=" + mode + "_strobe=" + strobe;
        if (duration != "") payload += "_duration=" + duration;
        if (sirenl != "") payload += "_sirenl=" + sirenl;
    } else if (request == "1000-GetGroups") {
        topic = "Cmd" + logicalId + "_cmd-1000";
        ep = document.getElementById("idEpC1000-41").value;
        payload = "ep=" + ep + "_cmd=41_startIdx=00";
    } else if (request == "1000-GetEndpoints") {
        topic = "Cmd" + logicalId + "_cmd-1000";
        ep = document.getElementById("idEpC1000-42").value;
        payload = "ep=" + ep + "_cmd=42_startIdx=00";
    } else if (request == "genericCmd") {
        topic = "Cmd" + logicalId + "_cmd-Generic";
        ep = document.getElementById("idEp-GC").value;
        clustId = document.getElementById("idClustId-GC").value;
        cmd = document.getElementById("idCmd-GC").value;
        data = document.getElementById("idData-GC").value;

        payload =
            "ep=" +
            ep +
            "_clustId=" +
            clustId +
            "_cmd=" +
            cmd +
            "_data=" +
            data;
    } else {
        console.log("Unknown request " + request);
        return;
    }

    console.log("topic=" + topic + ", pl=" + payload);
    var xhttp = new XMLHttpRequest();
    xhttp.open(
        "GET",
        "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId=" +
        js_queueXToCmd +
        "&topic=" +
        topic +
        "&payload=" +
        payload,
        false
    );
    xhttp.send();

    xhttp.onreadystatechange = function () { };
}

// addCmdToTable() to be moved there when compliant

$("#table_cmd").sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true,
});

$("#bt_addAbeilleAction").on("click", function (event) {
    var _cmd = { type: "action" };
    addCmdToTable(_cmd);
    $(".cmd:last .cmdAttr[data-l1key=type]").trigger("change");
    $("#div_alert").showAlert({
        message:
            "Nouvelle commande action ajoutée en fin de tableau. A compléter et sauvegarder.",
        level: "success",
    });
});

$("#bt_addAbeilleInfo").on("click", function (event) {
    var _cmd = { type: "info" };
    addCmdToTable(_cmd);
    $(".cmd:last .cmdAttr[data-l1key=type]").trigger("change");
    $("#div_alert").showAlert({
        message:
            "Nouvelle commande info ajoutée en fin de tableau. A compléter et sauvegarder.",
        level: "success",
    });
});

/* Allows to select of cmd Json file & load it as a new command */
$("#bt_loadCmdFromJson").on("click", function (event) {
    console.log("loadCmdFromJson()");

    $("#abeilleModal").dialog({
        title: "{{Charge cmd JSON}}",
        autoOpen: false,
        resizable: false,
        modal: true,
        height: 250,
        width: 400,
    });
    $("#abeilleModal")
        .load("index.php?v=d&plugin=Abeille&modal=AbeilleLoadJsonCmd.modal")
        .dialog("open");
});
