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

// console.log("LA1 eqId=", eqId);

// if (window.location.href.indexOf("id=") > -1) {
//     let params = new URL(document.location).searchParams;
//     eqId = params.get("id");
//     eqId2 = eqId;
//     refreshAdvEq();
// }

// Click generate on page reload too.
$(".eqLogicDisplayCard").on("click", function () {
    console.log("eqLogicDisplayCard click");
    if (!isset($(this).attr("data-eqLogic_id"))) {
        console.log("ERROR: 'data-eqLogic_id' is not defined");
        return;
    }
    curEqId = $(this).attr("data-eqLogic_id");
    console.log("curEqId=" + curEqId);
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

            // Updating advanced common infos
            document.getElementById("idEqName").value = eq.name;
            document.getElementById("idEqId").value = eqId;
            document.getElementById("idEqAddr").value = eq.addr;
            document.getElementById("idZgType").value = eq.zgType;
            document.getElementById("idZbModel").value = eq.zbModel;
            document.getElementById("idZbManuf").value = eq.zbManuf;
            document.getElementById("idModelName").value = eq.modelName;
            document.getElementById("idModelSource").value = eq.modelSource;
            if (typeof eq.zigbee.manufCode != "undefined")
                document.getElementById("idManufCode").value =
                    eq.zigbee.manufCode;

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

    xhttp.onreadystatechange = function () {};

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

/* Save given Abeille config (=> 'config' DB) */
function saveConfig(config) {
    console.log("saveConfig(): config=", config);

    $.ajax({
        type: 'POST',
        url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
        data: {
            action: 'saveConfig',
            config: JSON.stringify(config)
        },
        dataType: 'json',
        global: false,
        success: function (json_res) {
        }
    });
}

