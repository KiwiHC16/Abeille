
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

$('#bt_networkAbeilleList').on('click', function () {
                               $('#md_modal').dialog({title: "{{Réseau Abeille}}"});
                               $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=network').dialog('open');
                               //window.open("plugins/Abeille/Network/AbeilleLQI_List.php");
                               });

$('#bt_networkAbeille').on('click', function () {
                           window.open("plugins/Abeille/Network/TestSVG/NetworkGraph.html");
                           });

$('#bt_graph').on('click', function () {
                               window.open("plugins/Abeille/Network/AbeilleLQI_Map.php?GraphType=LqiPerMeter&NE=All&NE2=None&Center=none&Cache=Cache&Data=LinkQualityDec&Hierarchy=All");
                               });

$('#bt_listeCompatibilite').on('click', function () {
                           window.open("plugins/Abeille/core/config/devices/listeCompatibilite.php");
                           });

$('#bt_Inconnu').on('click', function () {
                          window.open("plugins/Abeille/resources/AbeilleDeamon/Debug/inconnu.php");
                          });

$('#bt_networkAbeilleNew').on('click', function () {
                              $('#md_modal').dialog({title: "{{Graph Abeille}}"});
                              // $('#md_modal').load('plugins/Abeille/Network/TestSVG/NetworkGraph.html').dialog('open');
                              $('#md_modal').load('index.php?v=d&plugin=Abeille&modal=NetworkGraph').dialog('open');
                              });

$('#bt_setTimeServer').on('click', function () {
                          console.log("bt_setTimeServer");
                          var d = new Date();
                          var n = Math.round(d.getTime()/1000);
                          console.log(n);
                          var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                          xmlhttpMQTTSendInclude.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendIncludeResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_setTimeServer&payload=time="+n, true); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendInclude.send();
                          $('#div_alert').showAlert({message: '{{Envoie de l heure a la zigate fait.}}', level: 'success'});
                          });


$('#bt_getTimeServer').on('click', function () {
                          console.log("bt_getTimeServer");
                          var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                          xmlhttpMQTTSendInclude.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendIncludeResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_getTimeServer&payload=", true); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendInclude.send();
                          $('#div_alert').showAlert({message: '{{Envoie de l heure a la zigate fait.}}', level: 'success'});
                          });

$('#bt_setOnZigateLed').on('click', function () {
                          console.log("bt_setOnZigateLed");
                          var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                          xmlhttpMQTTSendInclude.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendIncludeResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_setOnZigateLed&payload=", true); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendInclude.send();
                          $('#div_alert').showAlert({message: '{{Demande Led On.}}', level: 'success'});
                          });

$('#bt_setOffZigateLed').on('click', function () {
                          console.log("bt_setOffZigateLed");
                          var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                          xmlhttpMQTTSendInclude.onreadystatechange = function() {
                          if (this.readyState == 4 && this.status == 200) {
                          xmlhttpMQTTSendIncludeResult = this.responseText;
                          }
                          };

                          xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_setOffZigateLed&payload=", true); // False pour bloquer sur la recuperation du fichier
                          xmlhttpMQTTSendInclude.send();
                          $('#div_alert').showAlert({message: '{{Demande Led Off.}}', level: 'success'});
                          });

$('#bt_setCertificationCE').on('click', function () {
                           console.log("bt_setCertificationCE");
                           var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                           xmlhttpMQTTSendInclude.onreadystatechange = function() {
                           if (this.readyState == 4 && this.status == 200) {
                           xmlhttpMQTTSendIncludeResult = this.responseText;
                           }
                           };

                           xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_setCertificationCE&payload=", true); // False pour bloquer sur la recuperation du fichier
                           xmlhttpMQTTSendInclude.send();
                           $('#div_alert').showAlert({message: '{{Demande Led On.}}', level: 'success'});
                           });

$('#bt_setCertificationFCC').on('click', function () {
                            console.log("bt_setCertificationFCC");
                            var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                            xmlhttpMQTTSendInclude.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                            xmlhttpMQTTSendIncludeResult = this.responseText;
                            }
                            };

                            xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_setCertificationFCC&payload=", true); // False pour bloquer sur la recuperation du fichier
                            xmlhttpMQTTSendInclude.send();
                            $('#div_alert').showAlert({message: '{{Demande Led Off.}}', level: 'success'});
                            });

$('#bt_startZigbee').on('click', function () {
                    console.log("bt_startZigbee");
                    var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                    xmlhttpMQTTSendInclude.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    xmlhttpMQTTSendIncludeResult = this.responseText;
                    }
                    };

                    xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille_Ruche_startNetwork&payload=StartNetwork", true); // False pour bloquer sur la recuperation du fichier
                    xmlhttpMQTTSendInclude.send();
                    $('#div_alert').showAlert({message: '{{Démarrage du réseau zigbee demandé.}}', level: 'success'});
                    }
                    );

$('#bt_include1').on('click', function () {
                    console.log("bt_include");
                    var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                    xmlhttpMQTTSendInclude.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                    xmlhttpMQTTSendIncludeResult = this.responseText;
                    }
                    };

                    xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille1_Ruche_SetPermit&payload=Inclusion", true); // False pour bloquer sur la recuperation du fichier
                    xmlhttpMQTTSendInclude.send();
                    $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate doit se mettre à clignoter.}}', level: 'success'});
                    }
                    );

$('#bt_include2').on('click', function () {
                      console.log("bt_include2");
                      var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                      xmlhttpMQTTSendInclude.onreadystatechange = function() {
                      if (this.readyState == 4 && this.status == 200) {
                      xmlhttpMQTTSendIncludeResult = this.responseText;
                      }
                      };

                      xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille2_Ruche_SetPermit&payload=Inclusion", true); // False pour bloquer sur la recuperation du fichier
                      xmlhttpMQTTSendInclude.send();
                      $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate doit se mettre à clignoter.}}', level: 'success'});
                      }
                      );
                      
$('#bt_include3').on('click', function () {
                      console.log("bt_include3");
                      var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                      xmlhttpMQTTSendInclude.onreadystatechange = function() {
                      if (this.readyState == 4 && this.status == 200) {
                      xmlhttpMQTTSendIncludeResult = this.responseText;
                      }
                      };

                      xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille3_Ruche_SetPermit&payload=Inclusion", true); // False pour bloquer sur la recuperation du fichier
                      xmlhttpMQTTSendInclude.send();
                      $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate doit se mettre à clignoter.}}', level: 'success'});
                      }
                      );
                      
$('#bt_include4').on('click', function () {
                      console.log("bt_include4");
                      var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                      xmlhttpMQTTSendInclude.onreadystatechange = function() {
                      if (this.readyState == 4 && this.status == 200) {
                      xmlhttpMQTTSendIncludeResult = this.responseText;
                      }
                      };

                      xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille4_Ruche_SetPermit&payload=Inclusion", true); // False pour bloquer sur la recuperation du fichier
                      xmlhttpMQTTSendInclude.send();
                      $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate doit se mettre à clignoter.}}', level: 'success'});
                      }
                      );
                      
$('#bt_include4').on('click', function () {
                      console.log("bt_include4");
                      var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                      xmlhttpMQTTSendInclude.onreadystatechange = function() {
                      if (this.readyState == 4 && this.status == 200) {
                      xmlhttpMQTTSendIncludeResult = this.responseText;
                      }
                      };

                      xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille5_Ruche_SetPermit&payload=Inclusion", true); // False pour bloquer sur la recuperation du fichier
                      xmlhttpMQTTSendInclude.send();
                      $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate doit se mettre à clignoter.}}', level: 'success'});
                      }
                      );
                          
                          
$('#bt_exclude').on('click', function () {
                    console.log("bt_exclude");
// To be defined
                    }
                    );

$('#bt_createTimer').on('click', function () {
                        console.log("bt_createTimer");
                        var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                        xmlhttpMQTTSendTimer.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                        xmlhttpMQTTSendTimerResult = this.responseText;
                        }
                        };

                        xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreate_Timer_0000-0005&payload=Timer", false); // False pour bloquer sur la recuperation du fichier
                        xmlhttpMQTTSendTimer.send();
                        // location.reload(true);
                        $('#div_alert').showAlert({message: '{{Un nouveau Timer est en création.}}', level: 'success'});
                        }
                        );

$('#bt_createRemote').on('click', function () {
                         console.log("bt_createRemote");
                         var xmlhttpMQTTSendTimer = new XMLHttpRequest();
                         xmlhttpMQTTSendTimer.onreadystatechange = function() {
                         if (this.readyState == 4 && this.status == 200) {
                         xmlhttpMQTTSendTimerResult = this.responseText;
                         }
                         };

                         xmlhttpMQTTSendTimer.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdCreate_zigate_0000-0005&payload=remotecontrol", false); // False pour bloquer sur la recuperation du fichier
                         xmlhttpMQTTSendTimer.send();
                         // location.reload(true);
                         $('#div_alert').showAlert({message: '{{Une nouvelle Telecommande est en création.}}', level: 'success'});
                         }
                         );

$("#bt_TimerActionStart").on('click', function () {
                                              jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
                                                 document.getElementById("idTimerActionStart").value = result.human;
                                               });
                                             });

$("#bt_TimerActionRamp").on('click', function () {
                                            jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
                                               document.getElementById("idTimerActionRamp").value = result.human;
                                             });
                                           });

$("#bt_TimerActionStop").on('click', function () {
                                             jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
                                                document.getElementById("idTimerActionStop").value = result.human;
                                              });
                                            });

$("#bt_TimerActionCancel").on('click', function () {
                                              jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
                                                 document.getElementById("idTimerActionCancel").value = result.human;
                                               });
                                             });


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
		tr += '<span class="cmdAttr" data-l1key="id"></span>';
		tr += '</td>';
		tr += '<td>';//2
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom de l\'info}}"></td>';
		tr += '<td>';//3
		tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
		tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
		tr += '</td><td>';//4
		tr += '<span class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" style="height : 33px;" ' + disabled + ' placeholder="{{Topic}}" readonly=true>';
		//tr += '</td><td>';//5
		tr += '</td><td>';//6
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unité}}"></td><td>';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary" checked/>{{Inverser}}</label></span> ';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"> ';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;">';
		tr += '</td>';
		tr += '<td>';//7
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
		tr += '<i class="fa fa-minus-circle cmdAction cursor" data-action="remove"></i></td>';
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
		tr += '<span class="cmdAttr" data-l1key="id"></span>';
		tr += '</td>';
		tr += '<td>';//2
		tr += '<div class="row">';
		tr += '<div class="col-lg-6">';
		tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
		tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
		tr += '</div>';
		tr += '<div class="col-lg-6">';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
		tr += '</div>';
		tr += '</div>';
		tr += '<select class="cmdAttr form-control tooltips input-sm" data-l1key="value" style="display : none;margin-top : 5px;margin-right : 10px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
		tr += '<option value="">Aucune</option>';
		tr += '</select>';
		tr += '</td>';
		tr += '<td>';//3
		tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
		tr += '<span class="subType" subType="' + init(_cmd.subType) + '" style=""></span>';
		//tr += '<input class="cmdAttr" data-l1key="configuration" data-l2key="virtualAction" value="1" style="display:none;" >';
		tr += '</td>';
		tr += '<td>';//4
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" style="height : 33px;" ' + disabled + ' placeholder="{{Topic}}"><br/>';
        tr += '</td>';
		tr += '<td>';//5
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" style="height : 33px;" ' + disabled + ' placeholder="{{Payload}}">';
		tr += '<a class="btn btn-default btn-sm cursor listEquipementInfo" data-input="request" style="margin-left : 5px;"><i class="fa fa-list-alt "></i> {{Rechercher équipement}}</a>';
		tr +='</select></span>';
		tr += '</td><td>';//6
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span><br> ';
		// tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="configuration" data-l2key="retain" />{{Retain flag}}</label></span><br> ';
		tr += '</td>';
		tr += '<td>';//7
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
		tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
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
