
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

$("#table_cmd").delegate(".listEquipementInfo", 'click', function () {
    var el = $(this);
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
        var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=' + el.data('input') + ']');
        calcul.atCaret('insert', result.human);
    });
});

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


$("#bt_addAbeilleAction").on('click', function(event) {
                             var _cmd = {type: 'action'};
                             addCmdToTable(_cmd);
                             $('#div_alert').showAlert({message: 'Affichage des commandes additionnelles mis en place', level: 'success'});
                             });

$("#bt_addAbeilleInfo").on('click', function(event) {
                           var _cmd = {type: 'info'};
                           addCmdToTable(_cmd);
                           $('#div_alert').showAlert({message: 'Affichage des commandes additionnelles mis en place', level: 'success'});
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


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}

	if (init(_cmd.type) == 'info') {
		var disabled = (init(_cmd.configuration.virtualAction) == '1') ? 'disabled' : '';
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<td>';//1
		tr += '     <span class="cmdAttr" data-l1key="id"></span>';
		tr += '</td>';
		tr += '<td>';//2
        tr += '     <input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom de l\'info}}">';
        tr += '</td>';
		tr += '<td>';//3
		tr += '     <input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
		tr += '     <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';
        tr += '<td>';//4
		tr += '     <span class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" style="height : 33px;" ' + disabled + ' placeholder="{{Topic}}" readonly=true>';
        //tr += '</td>';
        tr += '<td>';//5
        tr += '</td>';
        tr += '<td>';//6
        tr += '     <input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unité}}">';
        tr += '</td>';
        tr += '<td>';
		tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary" checked/>{{Inverser}}</label></span> ';
		tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"> ';
        tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;">';
		tr += '</td>';
		tr += '<td>';//7
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
        tr += '     <i class="fa fa-minus-circle cmdAction cursor" data-action="remove"></i>';
        tr += '</td>';
        tr += '</tr>';
        
		$('#table_cmd tbody').append(tr);
		$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
		if (isset(_cmd.type)) {
			$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
		}
		jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
	}

	if (init(_cmd.type) == 'action') {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<td>';//1
		tr += ' <span class="cmdAttr" data-l1key="id"></span>';
		tr += '</td>';
		tr += '<td>';//2
		// tr += '     <div class="row">';
		// tr += '         <div class="col-lg-6">';
		// tr += '             <a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
		// tr += '             <span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
		// tr += '         </div>';
		//tr += '         <div class="col-lg-6">';
		tr += '             <input class="cmdAttr form-control input-sm" data-l1key="name"  style="width : 140px;" placeholder="{{Nom de l\'info}}">';
		//tr += '         </div>';
		// tr += '     </div>';
		// tr += '     <select class="cmdAttr form-control tooltips input-sm" data-l1key="value" style="display : none;margin-top : 5px;margin-right : 10px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
		// tr += '         <option value="">Aucune</option>';
		// tr += '     </select>';
		tr += '</td>';
		tr += '<td>';//3
		tr += '     <input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
		tr += '     <span class="subType" subType="' + init(_cmd.subType) + '" style=""></span>';
		//tr += '<input class="cmdAttr" data-l1key="configuration" data-l2key="virtualAction" value="1" style="display:none;" >';
		tr += '</td>';
		tr += '<td>';//4
		tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" style="height : 33px;" ' + disabled + ' placeholder="{{Topic}}"><br/>';
        tr += '</td>';
		tr += '<td>';//5
		tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" style="height : 33px;" ' + disabled + ' placeholder="{{Payload}}">';
        tr += '</td>';
        tr += '<td>';   
        // 
        tr += '     <select class="form-control cmdAttr input-sm" data-l1key="configuration" data-l2key="Polling" title="{{Si vous souhaitez forcer le recuperature periodique d une valeur choisissez la periode.}}" >';
        tr += '         <option value="">Aucun</option>';
        tr += '         <option value="cron">1 min</option>';
        tr += '         <option value="cron5">5 min</option>';
        tr += '         <option value="cron10">10 min</option>';
        tr += '         <option value="cron15">15 min</option>';
        tr += '         <option value="cron30">30 min</option>';
        tr += '         <option value="cronHourly">Heure</option>';
        tr += '         <option value="cronDaily">Jour</option>';
		tr += '     </select>';     
        // tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="Polling"  style="width : 90px;" placeholder="{{Cron}}">';
        tr += '</td>';
		// tr += '</select></span>';
        // tr += '</td>';
        tr += '<td>';//6
		tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span><br> ';
		// tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="configuration" data-l2key="retain" />{{Retain flag}}</label></span><br> ';
		tr += '</td>';
		tr += '<td>';//7
		if (is_numeric(_cmd.id)) {
			tr += ' <a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += ' <a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
        tr += '     <i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
        tr += '</td>';
		tr += '</tr>';

		$('#table_cmd tbody').append(tr);
		//$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
		var tr = $('#table_cmd tbody tr:last');
		jeedom.eqLogic.builSelectCmd({
			id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
			filter: {type: 'info'},
			error: function (error) {
				$('#div_alert').showAlert({message: error.message, level: 'danger'});
			},
			success: function (result) {
				tr.find('.cmdAttr[data-l1key=value]').append(result);
				tr.setValues(_cmd, '.cmdAttr');
				jeedom.cmd.changeType(tr, init(_cmd.subType));
			}
		});
	}
}
