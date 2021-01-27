
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
                                          if(search == ''){
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
                          $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=health').dialog('open');
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

$('#bt_networkAbeilleList').on('click', function () {
                               $('#md_modal').dialog({title: "{{Réseau Abeille}}"});
                               $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=network').dialog('open');
                               //window.open("plugins/Abeille/Network/AbeilleLQI_List.php");
                               });

$('#bt_networkAbeille').on('click', function () {
    // window.open("plugins/Abeille/Network/TestSVG/NetworkGraph.php");
    window.open("index.php?v=d&m=Abeille&p=AbeilleNetworkGraph");
});

$('#bt_graph').on('click', function () {
    window.open("plugins/Abeille/Network/AbeilleLQI_Map.php?GraphType=LqiPerMeter&NE=All&NE2=None&Center=none&Cache=Cache&Data=LinkQualityDec&Hierarchy=All");
});

$('#bt_listeCompatibilite').on('click', function () {
                               $('#md_modal').dialog({title: "{{Liste Compatibilite}}"});
                               $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=listeCompatibilite').dialog('open');
                           });

$('#bt_Inconnu').on('click', function () {
                        $('#md_modal').dialog({title: "{{Inconnu}}"});
                        $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=inconnu').dialog('open');
                          });

$('#bt_networkAbeilleNew').on('click', function () {
                              $('#md_modal').dialog({title: "{{Graph Abeille}}"});
                              // $('#md_modal').load('plugins/Abeille/Network/TestSVG/NetworkGraph.html').dialog('open');
                              $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=NetworkGraph').dialog('open');
                              });


$('#bt_createRemote1').on('click', function () {
                         console.log("bt_createRemote1");
                         var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                         xmlhttpMQTTSendTimer.onreadystatechange = function() {
                         if (this.readyState == 4 && this.status == 200) {
                         xmlhttpMQTTSendTimerResult = this.responseText;
                         }
                         };

                         xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille1_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                         xmlhttpMQTTSendTimer.send();
                         // location.reload(true);
                         $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                         }
                         );

$('#bt_createRemote2').on('click', function () {
                          console.log("bt_createRemote2");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille2_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote3').on('click', function () {
                          console.log("bt_createRemote3");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille3_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote4').on('click', function () {
                          console.log("bt_createRemote4");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille4_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote5').on('click', function () {
                          console.log("bt_createRemote5");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille5_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote6').on('click', function () {
                          console.log("bt_createRemote6");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille6_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote7').on('click', function () {
                          console.log("bt_createRemote7");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille7_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote8').on('click', function () {
                          console.log("bt_createRemote8");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille8_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote9').on('click', function () {
                          console.log("bt_createRemote9");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille9_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

$('#bt_createRemote10').on('click', function () {
                          console.log("bt_createRemote");
                          var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                          xmlhttpMQTTSendTimer.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendTimerResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreateAbeille10_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendTimer.send();
                          // location.reload(true);
                          $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                          }
                          );

/* Check which equipements are selected for given zigate number (zgNb).
   Returns: object {zgNb:<zigateNb>, nb:<nbOfSelectedEq>, ids:[<arrayOfEqIds>]} */
function getSelectedEqs(zgNb) {
    console.log("getSelectedEqs("+zgNb+")");
    var selected = new Object;
    selected["zgNb"] = zgNb; // Zigate number
    selected["nb"] = 0; // Number of selected equipments
    selected["ids"] = new Array; // Array of eq IDs
    eval('var eqZigate = JSON.parse(js_eqZigate'+zgNb+');'); // List of eq IDs for current zigate
    for (var i = 0; i < eqZigate.length; i++) {
        var eqId = eqZigate[i];
        var checked = document.getElementById("idBeeChecked"+zgNb+"-"+eqId).checked;
        if (checked == false)
            continue;

        selected["nb"]++;
        selected["ids"].push(eqId);
    }
    console.log('selected["nb"]='+selected["nb"]);
    return selected;
}

/* Removes selected equipments for given zigate nb from Jeedom DB only. Zigate is untouched. */
function removeBeesJeedom(zgNb) {
    console.log("removeBeesJeedom(zgNb="+zgNb+")");

    /* Any selected ? */
    var sel = getSelectedEqs(zgNb);
    console.log(sel);
    if (sel["nb"] == 0) {
        alert("Aucun équipement sélectionné !")
        return;
    }
    var eqList = sel["ids"];
    console.log("eqList="+eqList);

    var msg = "{{Vous êtes sur le point de supprimer de Jeedom les équipements selectionnés.";
    msg += "<br>Cela n'affecte pas le réseau connu de la zigate.";
    msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
    bootbox.confirm(msg, function (result) {
        if (result == false)
            return

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
            data: {
                action: 'removeEqJeedom',
                eqList: eqList
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
                }
            }
        });
    });
}
