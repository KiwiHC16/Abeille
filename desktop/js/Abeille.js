
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

$('#in_searchEqlogicB').off('keyup').keyup(function () {
    var search = $(this).value();
    if(search == '') {
        $('.eqLogicDisplayCardB').show();
        $('.eqLogicThumbnailContainer').packery();
        return;
    }
    $('.eqLogicDisplayCardB').hide();
    $('.eqLogicDisplayCardB .name').each(function(){
                                        var text = $(this).text().toLowerCase();
                                        if(text.indexOf(search.toLowerCase()) >= 0){
                                        $(this)
                                        $(this).closest('.eqLogicDisplayCardB').show();
                                        }
                                        });
    $('.eqLogicThumbnailContainer').packery();
});

$('#bt_healthAbeille').on('click', function () {
    $('#md_modal').dialog({title: "{{Santé Abeille}}"});
    $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=AbeilleHealth.modal').dialog('open');
});

$('#bt_supportPage').on('click', function () {
    // $('#md_modal').dialog({title: "{{Support}}"});
    // $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=supportPage').dialog('open');
    window.open("index.php?v=d&m=Abeille&p=AbeilleSupport");
});

$('#bt_template').on('click', function () {
    $('#md_modal').dialog({title: "{{Modeles}}"});
    $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=modelesPage&testToRun=104').dialog('open');
});

$('#bt_network').on('click', function () {
    $('#md_modal').dialog({title: "{{Réseau Abeille}}"});
    $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=AbeilleNetwork.modal').dialog('open');
});

$('#bt_networkAbeille').on('click', function () {
    // window.open("plugins/Abeille/Network/TestSVG/NetworkGraph.php");
    window.open("index.php?v=d&m=Abeille&p=AbeilleNetworkGraph");
});

$('#bt_graph').on('click', function () {
    window.open("plugins/Abeille/desktop/php/AbeilleGraph.php?GraphType=LqiPerMeter&NE=All&NE2=None&Center=none&Cache=Cache&Data=LinkQualityDec&Hierarchy=All");
});

$('#bt_supportedEqList').on('click', function () {
    $('#md_modal').dialog({title: "{{Liste de compatibilité}}"});
    $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=AbeilleCompatibility.modal').dialog('open');
});

// $('#bt_Inconnu').on('click', function () {
//                         $('#md_modal').dialog({title: "{{Inconnu}}"});
//                         $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=inconnu').dialog('open');
//                           });

$('#bt_Ota').on('click', function () {
    $('#md_modal').dialog({title: "{{Mises-à-jour OTA}}"});
    $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=Abeille-OTA.modal').dialog('open');
});

$('#bt_networkAbeilleNew').on('click', function () {
                              $('#md_modal').dialog({title: "{{Graph Abeille}}"});
                              // $('#md_modal').load('plugins/Abeille/Network/TestSVG/NetworkGraph.html').dialog('open');
                              $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=NetworkGraph').dialog('open');
                              });

/* Add a virtual remote control to given zigate number */
function createRemote(zgId) {
    console.log("createRemote("+zgId+")");
    var xmlhttpMQTTSendTimer = new XMLHttpRequest();
    xmlhttpMQTTSendTimer.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            xmlhttpMQTTSendTimerResult = this.responseText;
        }
    };

    // xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille1_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
    // wget "http://localhost/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&topic=CmdCreateAbeille1_zigate_createRemote"
    xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&topic=CmdCreateAbeille"+zgId+"_zigate_createRemote", false); // False pour bloquer sur la recuperation du fichier
    xmlhttpMQTTSendTimer.send();
    // location.reload(true);
    $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
}

/* Check which equipements are selected for given zigate number (zgId).
   Returns: object {zgId:<zigateNb>, nb:<nbOfSelectedEq>, ids:[<arrayOfEqIds>]} */
function getSelectedEqs(zgId) {
    console.log("getSelectedEqs("+zgId+")");
    var selected = new Object;
    selected["zgId"] = zgId; // Zigate number
    selected["nb"] = 0; // Number of selected equipments
    selected["ids"] = new Array; // Array of eq IDs
    selected["addrs"] = new Array; // Array of eq short addresses
    eval('var eqZigate = JSON.parse(js_eqZigate'+zgId+');'); // List of eq IDs for current zigate
    for (const [eqId, eq] of Object.entries(eqZigate)) {
        var checked = document.getElementById("idBeeChecked"+zgId+"-"+eq.id).checked;
        if (checked == false)
            continue;

        selected["nb"]++;
        selected["ids"].push(eq.id);
        selected["addrs"].push(eq.addr);
    }
    console.log('selected["nb"]='+selected["nb"]);
    return selected;
}

/* Removes selected equipments for given zigate nb from Jeedom DB only. Zigate is untouched. */
function removeBeesJeedom(zgId) {
    console.log("removeBeesJeedom(zgId="+zgId+")");

    /* Any selected ? */
    var sel = getSelectedEqs(zgId);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !")
        return;
    }
    var eqIdList = sel["ids"];
    console.log("eqIdList="+eqIdList);

    var msg = "{{Vous êtes sur le point de supprimer les équipements selectionnés de Jeedom.";
    msg += "<br><br>Si ils sont toujours dans le réseau, ils deviendront 'fantomes' et devraient être réinclus automatiquement au fur et à mesure de leur reveil et ce, tant qu'on ne les force pas à quitter le réseau.";
    msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false)
            return

        // Collecting addresses before EQ is removed
        var eqAddrList = sel["addrs"];
        console.log("eqAddrList="+eqAddrList);

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'removeEqJeedom',
                eqList: eqIdList
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'removeEqJeedom' !");
            },
            success: function (json_res) {
                window.location.reload();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg = "ERREUR ! Quelque chose s'est mal passé.\n"+res.errors;
                    alert(msg);
                } else {
                    // Informing parser that some equipements have to be considered "phantom"
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueCtrlToParser+"&msg=type:eqRemoved_net:Abeille"+zgId+"_eqList:"+eqAddrList, true);
                    xhr.send();
                }
            }
        });
    });
}

/* Called when 'exclude' button is pressed
   Request device to leave the network (if battery powered, must be wake up). */
function removeBees(zgId) {
    console.log("removeBees(zgId="+zgId+")");

    eval('var eqZigate = JSON.parse(js_eqZigate'+zgId+');'); // List of eq IDs for current zigate

    /* Any selected ? */
    var sel = getSelectedEqs(zgId);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !")
        return;
    }
    var eqIdList = sel["ids"];
    console.log("eqIdList="+eqIdList);

    var msg = "{{Vous êtes sur le point de demander aux équipements selectionnés de quitter le réseau.";
    msg += "<br>- Si alimenté par secteur, cela devrait être immédiat.";
    msg += "<br>- Si alimenté par pile, vous devez reveiller l'équipement immédiatement apres la requète mais cela n'est pas toujours possible.";
    msg += "<br><br>D'autre part la plupart des équipements signalent qu'ils quittent le réseau (message \"xxx a quitté le réseau\") mais pas tous.";
    msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false)
            return

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'removeEqZigbee',
                eqIdList: eqIdList
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'removeEqZigbee' !");
            },
            success: function (json_res) {
                window.location.reload();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg = "ERREUR ! Quelque chose s'est mal passé.\n"+res.errors;
                    alert(msg);
                }
            }
        });
    });
}

// /* Called when clean button is pressed */
// function cleanBees(zgId, zgPort) {
//     console.log("cleanBees(zgId="+zgId+", zpPort="+zgPort+")");

//     $('#md_modal').dialog({title: "{{Nettoyage du réseau}} 'Abeille"+zgId+"'"});
//     $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=cleanBees&zgPort='+zgPort+'&zgId='+zgId).dialog('open');
// }

/* Called when 'setTimeout' button is pressed
   Allows to modify timeout of selected equipements. */
function setBeesTimeout(zgId) {
    console.log("setBeesTimeout(zgId="+zgId+")");

    var sel = getSelectedEqs(zgId);
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
    });
    $('#abeilleModal').load('index.php?v=d&plugin=Abeille&modal=setBeesTimeout.abeille&zgId='+zgId).dialog('open');
}

/* Called when 'monitor' button is pressed */
function monitorIt(zgId, zgPort) {
    console.log("monitorIt(zgId="+zgId+", zpPort="+zgPort+")");

    var sel = getSelectedEqs(zgId);
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
        // url: 'plugins/Abeille/core/ajax/AbeilleDev.ajax.php',
        url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
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
