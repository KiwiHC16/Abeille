<!-- This file displays equipment commands.
     Included by 'AbeilleEq.php' -->

<script>
     /*
	 * Commands tab
	 */

    $("#bt_addAbeilleAction").on('click', function(event) {
        var _cmd = {type: 'action'};
        addCmdToTable(_cmd);
        $('.cmd:last .cmdAttr[data-l1key=type]').trigger('change')
        $('#div_alert').showAlert({message: 'Nouvelle commande action ajoutée en fin de tableau. A compléter et sauvegarder.', level: 'success'});
    });

    $("#bt_addAbeilleInfo").on('click', function(event) {
        var _cmd = {type: 'info'};
        addCmdToTable(_cmd);
        $('.cmd:last .cmdAttr[data-l1key=type]').trigger('change')
        $('#div_alert').showAlert({message: 'Nouvelle commande info ajoutée en fin de tableau. A compléter et sauvegarder.', level: 'success'});
    });

    function addCmdToTable(_cmd) {
        // console.log("addCmdToTable()");

        if (!isset(_cmd)) {
            var _cmd = {configuration: {}};
        }
        if (!isset(_cmd.configuration)) {
            _cmd.configuration = {};
        }
		console.log("addCmdToTable(typ="+_cmd.type+", jName="+_cmd.name+")");

        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

        tr += '<td class="hidden-xs">'; // Col 1 = Id
        tr += '     <span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';

        tr += '<td>'; // Col 2 = Jeedom cmd name
        // tr += '     <input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" style="width:180px;" placeholder="{{Cmde Jeedom}}" title="Nom de la commande vue par Jeedom">';
        tr += '     <input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Cmde Jeedom}}" title="Nom de la commande vue par Jeedom">';
        tr += '</td>';

        tr += '<td>'; // Col 3 = Type & sub-type
        if (init(_cmd.type) == 'info') {
            tr += '     <span class="cmdAttr form-control type input-sm" data-l1key="type" value="info" style="margin-bottom:5px;" /></span>';
        } else if (init(_cmd.type) == 'action') {
            tr += '     <span class="cmdAttr form-control type input-sm" data-l1key="type" value="action" style="margin-bottom:5px;" /></span>';
        } else { // New command
            tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
        }
        tr += '     <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';

        <?php if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == true)) { ?>
            tr += '<td>'; // Col 4 = Abeille command name
            tr += '     <input class="cmdAttr form-control input-sm" data-l1key="logicalId" placeholder="{{logicalId}}" style="width:100%;">';
            tr += '</td>';

            // Tcharp38: logicalId seems not really used so displaying 'topic' too
            tr += '<td>'; // Col 4.1 = Topic
            if (init(_cmd.type) == 'action') {
                tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" style="height : 33px;" placeholder="{{topic}}">';
            }
            tr += '</td>';

            tr += '<td>'; // Col 5 = Abeille cmd params
            if ((init(_cmd.type) == 'info') || (init(_cmd.type) == '')) { // Info or new cmd
            } else if (init(_cmd.type) == 'action') {
                tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" style="height : 33px;" placeholder="{{payload}}">';
            }
            tr += '</td>';
        <?php } ?>

        tr += '<td>'; // Col 6
        if ((init(_cmd.type) == 'info') || (init(_cmd.type) == '')) { // Info/new cmd => Col 6 = Unité
            tr += '     <input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unité}}">';
        } else if (init(_cmd.type) == 'action') { // Col 6 = Polling(cron) /
            tr += '     <select class="form-control cmdAttr input-sm" data-l1key="configuration" data-l2key="Polling" title="{{Si vous souhaitez forcer le recuperature periodique d une valeur choisissez la periode.}}" >';
            tr += '         <option value="">Aucun</option>';
            tr += '         <option value="cron">1 min</option>';
            tr += '         <option value="cron5">5 min</option>';
            tr += '         <option value="cron10">10 min</option>';
            tr += '         <option value="cron15">15 min</option>';
            tr += '         <option value="cron30">30 min</option>';
            tr += '         <option value="cronHourly">Heure</option>';
            tr += '         <option value="cronDaily">Jour</option>';
            tr += '     </select></br>';
            tr += '     <select class="form-control cmdAttr input-sm" data-l1key="configuration" data-l2key="PollingOnCmdChange" title="{{Si vous souhaitez forcer l\'execution de cette commande suite a une mise-à-jour d\'une commande info, choisissez cette dernière.}}" >';
            tr += '         <option value="">Aucun</option>';
            tr += '         <?php foreach ( $eqLogic->getCmd('info') as $cmd ) { echo "<option value=\"".$cmd->getLogicalId()."\">".$cmd->getName()."</option>"; } ?>';
            tr += '     </select></br>';
            tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="PollingOnCmdChangeDelay" style="height : 33px;" placeholder="{{en secondes}}" title="{{Temps souhaité entre Cmd Info Change et Execution de cette commande.}}" ><br/>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="configuration" data-l2key="RefreshData" title="{{Si vous souhaitez l execution de cette commande pour rafraichir l info par exemple au demarrage d abeille.}}" />{{Rafraichir}}</label></span><br> ';
        }
        tr += '</td>';

        tr += '<td>'; // Col 7
        if ((init(_cmd.type) == 'info') || (init(_cmd.type) == '')) { // Info or new command
            // Col 7 = Affiche + Hist + Invert + Min/Max
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible"/>{{Afficher}}</label></span>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized"/>{{Historiser}}</label></span>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span></br>';
            tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:40%; display:inline-block;"> - ';
            tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:40%; display:inline-block;">';
        } else if (init(_cmd.type) == 'action') {
            // Col 7 = Affiche
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible"/>{{Afficher}}</label></span><br>';
            // tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:40%; display:inline-block;"> - ';
            // tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:40%; display:inline-block;">';
        }
        tr += '</td>';

        tr += '<td>'; // Col 8 = Conf Adv / Tester
        if ((init(_cmd.type) == 'info') || (init(_cmd.type) == '')) { // Info or new command
            if (is_numeric(_cmd.id)) {
                tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
                tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
            }
        } else if (init(_cmd.type) == 'action') {
            if (is_numeric(_cmd.id)) {
                tr += ' <a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
                tr += ' <a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
            }
        }
        tr += '</td>';

        tr += '<td>'; // Col 9 = Remove
        tr += '     <i class="fa fa-minus-circle cmdAction cursor" data-action="remove"></i>';
        tr += '</td>';

        tr += '</tr>';
        $('#table_cmd tbody').append(tr);

        // if (init(_cmd.type) == 'info') {
        //     $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
        //     if (isset(_cmd.type)) {
        //         $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
        //     }
        //     jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
        // } else if (init(_cmd.type) == 'action') {
        //     //$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
        //     var tr = $('#table_cmd tbody tr:last');
        //     jeedom.eqLogic.builSelectCmd({
        //         id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        //         filter: {type: 'info'},
        //         error: function (error) {
        //             $('#div_alert').showAlert({message: error.message, level: 'danger'});
        //         },
        //         success: function (result) {
        //             tr.find('.cmdAttr[data-l1key=value]').append(result);
        //             tr.setValues(_cmd, '.cmdAttr');
        //             jeedom.cmd.changeType(tr, init(_cmd.subType));
        //         }
        //     });
        // }

        var tr = $('#table_cmd tbody tr').last();
        jeedom.eqLogic.builSelectCmd({
            id: $('.eqLogicAttr[data-l1key=id]').value(),
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

	$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

    /* Allows to select of cmd Json file & load it as a new command */
    $("#bt_loadCmdFromJson").on('click', function(event) {
        console.log("loadCmdFromJson()");

        $("#abeilleModal").dialog({
            title: "{{Charge cmd JSON}}",
            autoOpen: false,
            resizable: false,
            modal: true,
            height: 250,
            width: 400,
        });
        $('#abeilleModal').load('index.php?v=d&plugin=Abeille&modal=AbeilleLoadJsonCmd.modal').dialog('open');
    });

</script>