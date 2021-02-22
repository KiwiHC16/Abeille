<!-- This is equipement page opened when clicking on it.
     Displays main infos + specific params + commands. -->

<?php
    /* Developers debug features & PHP errors */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        // include_once $dbgFile;
        // include $dbgFile;
        $dbgDeveloperMode = TRUE;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    if (!isset($_GET['id']))
        exit("ERROR: Missing 'id'");
    if (!is_numeric($_GET['id']))
        exit("ERROR: 'id' is not numeric");

    $eqId = $_GET['id'];
    $eqLogic = eqLogic::byId($eqId);
    $eqLogicId = $eqLogic->getLogicalid();
    list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
    if ($eqAddr == "Ruche")
        $eqAddr = "0000";
    $zgNb = substr($eqNet, 7); // Extracting zigate number from network
    echo '<script>var js_eqId = '.$eqId.';</script>'; // PHP to JS
    echo '<script>var js_zgNb = '.$zgNb.';</script>'; // PHP to JS

    require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
?>

<div class="col-xs-12 eqLogic" style="padding-top: 5px">
    <div class="input-group pull-right" style="display:inline-flex">
		<span class="input-group-btn">
			<a class="btn btn-success eqLogicAction btn-sm roundedLeft" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a><a class="btn btn-default eqLogicAction btn-sm roundedRight" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
		</span>
	</div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="tab"               ><a href="index.php?v=d&m=Abeille&p=Abeille"><i class="fas fa-arrow-circle-left"></i></a></li>
        <li role="tab" class="active"><a href="#eqlogictab"><i class="fas fa-home"></i> {{Equipement}}</a></li>
        <li role="tab"               ><a href="#paramtab"><i class="fas fa-list-alt"></i> {{Paramètres}}</a></li>
        <li role="tab"               ><a href="#commandtab"><i class="fas fa-align-left"></i> {{Commandes}}</a></li>
    </ul>

    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">

        <!-- Displays Jeedom specifics  -->
        <div role="tabpanel" class="tab-pane active" id="eqlogictab">
            <?php include 'AbeilleEq-Main.php'; ?>
        </div>

        <!-- Displays Zigbee/equipement specifics  -->
        <div role="tabpanel" class="tab-pane" id="paramtab">
            <?php include 'AbeilleEq-Params.php'; ?>
        </div>

        <!-- Displays Jeedom commands  -->
        <div role="tabpanel" class="tab-pane" id="commandtab">
            <?php include 'AbeilleEq-Cmds.php'; ?>
        </div>

    </div>
</div>

<?php include_file('core', 'plugin.template', 'js'); ?>
<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<script>
    /* AbeilleEq page opened. Let's update display content.
       From 'core/js/plugin.template.js' */
    jeedom.eqLogic.print({
        type: "Abeille",
        id: js_eqId,
        status : 1,
        error: function (error) {
            console.log("jeedom.eqLogic.print() error");
            // $.hideLoading();
            // $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (data) {
            console.log("jeedom.eqLogic.print() success");
            console.log("data follows:");
            console.log(data);

            // $('body .eqLogicAttr').value('');
            if(isset(data) && isset(data.timeout) && data.timeout == 0){
                data.timeout = '';
            }
            $('body').setValues(data, '.eqLogicAttr');
            // if ('function' == typeof (printEqLogic)) {
            //     printEqLogic(data);
            // }

            /* Commandes update */
            if ('function' == typeof (addCmdToTable)) {
                $('.cmd').remove();
                for (var i in data.cmd) {
                    addCmdToTable(data.cmd[i]);
                }
            }
            $('body').delegate('.cmd .cmdAttr[data-l1key=type]', 'change', function () {
                jeedom.cmd.changeType($(this).closest('.cmd'));
            });
            $('body').delegate('.cmd .cmdAttr[data-l1key=subType]', 'change', function () {
                jeedom.cmd.changeSubType($(this).closest('.cmd'));
            });

            // changeLeftMenuObjectOrEqLogicName = false;
            // $.hideLoading();
            modifyWithoutSave = false;
        }
    });

    /* Click on 'save' button.
       Save all changes of 'eqLogicAttr' or 'cmdAttr' to Jeedom DB.
       From 'core/js/plugin.template.js' */
    $(".eqLogicAction[data-action=save]").off("click"); // Remove default Jeedom behavior.
    $('.eqLogicAction[data-action=save]').on('click', function () {
        console.log("eqLogicAction[data-action=save], eqId="+js_eqId);
        var eqLogics = [];
        $('.eqLogic').each(function () {
            if ($(this).is(':visible')) {
                var eqLogic = $(this).getValues('.eqLogicAttr');
                eqLogic = eqLogic[0];
                eqLogic.cmd = $(this).find('.cmd').getValues('.cmdAttr');
                // console.log(eqLogic);
                if ('function' == typeof (saveEqLogic)) {
                    eqLogic = saveEqLogic(eqLogic);
                }
                eqLogics.push(eqLogic);
            }
        });
        console.log(eqLogics);
        jeedom.eqLogic.save({
            type: "Abeille",
            id: js_eqId,
            eqLogics: eqLogics,
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: function (data) {
                modifyWithoutSave = false;
                window.location.reload();
                // var vars = getUrlVars();
                // var url = 'index.php?';
                // for (var i in vars) {
                //     if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                //         url += i + '=' + vars[i].replace('#', '') + '&';
                //     }
                // }
                // url += 'id=' + data.id + '&saveSuccessFull=1';
                // if (document.location.toString().match('#')) {
                //     url += '#' + document.location.toString().split('#')[1];
                // }
                // loadPage(url);
                // modifyWithoutSave = false;
            }
        });
        return false;
    });

    /* Click on 'remove' button.
       Delete current equipment from Jeedom DB.
       From 'core/js/plugin.template.js' */
    $(".eqLogicAction[data-action=remove]").off("click"); // Remove default Jeedom behavior.
    $('.eqLogicAction[data-action=remove]').on('click', function () {
        console.log("eqLogicAction[data-action=remove], eqId="+js_eqId);
        eqType = "Abeille";
        // if ($('.eqLogicAttr[data-l1key=id]').value() != undefined) {
            msg = '{{Vous êtes sur le point de supprimer l\'équipement}} <b>'+$('.eqLogicAttr[data-l1key=name]').value()+'</b> de Jeedom';
            msg += '{{<br>Si il est toujours dans le réseau Zigbee il le restera et pourrait toujours renvoyer des messages.}}'
            msg += '{{<br><br>Etes vous sur de vouloir continuer ?}}'
            bootbox.confirm(msg, function (result) {
                if (result) {
                    jeedom.eqLogic.remove({
                        type: "Abeille", // isset($(this).attr('data-eqLogic_type')) ? $(this).attr('data-eqLogic_type') : eqType,
                        id: js_eqId, // $('.eqLogicAttr[data-l1key=id]').value(),
                        error: function (error) {
                            $('#div_alert').showAlert({message: error.message, level: 'danger'});
                        },
                        success: function () {
                            // var vars = getUrlVars();
                            // var url = 'index.php?';
                            var url = 'index.php?v=d&m=Abeille&p=Abeille';
                            // for (var i in vars) {
                            //     if (i != 'id' && i != 'removeSuccessFull' && i != 'saveSuccessFull') {
                            //         url += i + '=' + vars[i].replace('#', '') + '&';
                            //     }
                            // }
                            modifyWithoutSave = false;
                            // url += 'removeSuccessFull=1';
                            loadPage(url);
                        }
                    });
                }
            });
        // } else {
        //     $('#div_alert').showAlert({message: '{{Veuillez d\'abord sélectionner un}} ' + eqType, level: 'danger'});
        // }
    });

	/*
	 * Equipement tab
	 */

    /* Send a command to zigate thru 'AbeilleCmd' */
    function sendZigate(action, param) {
        console.log("sendZigate("+action+", "+param+")");

        function sendToZigate(topic, payload) {
            $.ajax({
                type: 'POST',
                url: 'plugins/Abeille/core/ajax/AbeilleZigate.ajax.php',
                data: {
                    action: 'sendMsgToCmd',
                    topic: topic,
                    payload: payload
                },
                dataType: 'json',
                global: false,
                error: function (request, status, error) {
                    bootbox.alert("ERREUR 'sendMsgToCmd' !<br>Votre installation semble corrompue.");
                },
                success: function (json_res) {
                    res = JSON.parse(json_res.result);
                    console.log("status="+res.status);
                    if (res.status != 0)
                        console.log("error="+res.error);
                }
            });
        }

        var topic = "";
        var payload = "";
        switch(action) {
        case "SetLED":
            if (param == "ON")
                topic = 'CmdAbeille'+js_zgNb+'/Ruche/setOnZigateLed';
            else
                topic = 'CmdAbeille'+js_zgNb+'/Ruche/setOffZigateLed';
            break;
        case "SetCertif":
            if (param == "CE")
                topic = 'CmdAbeille'+js_zgNb+'/Ruche/setCertificationCE';
            else
                topic = 'CmdAbeille'+js_zgNb+'/Ruche/setCertificationFCC';
            break;
        case "StartNetwork": // Not required for end user but for developper.
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/startNetwork';
            payload = 'StartNetwork';
            break;
        case "SetMode":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/setModeHybride';
            if (param == "Normal")
                payload = 'normal';
            else if (param == "Raw")
                payload = 'RAW';
            else
                payload = 'hybride';
            break;
        case "SetExtPANId":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/setExtendedPANID';
            payload = ""; // TODO Kiwi: Oui mais jamis rencontré le besoin, pas critique.
            break;
        case "SetChannelMask":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/setChannelMask';
            mask = document.getElementById("idChannelMask").value;
            console.log("mask="+mask);
            if (mask == "") {
                alert("Masque vide.\nVeuillez entrer une valeur entre 800 (canal 11) et 07FFF800 (canaux 11 à 26).");
                return; // Empty
            }
            function isHex(str) {
                return /^[A-F0-9]+$/i.test(str)
            }
            if (!isHex(mask)) {
                alert("Le masque doit être une valeur hexa");
                return;
            }
            var maskI = parseInt(mask, 16); // Convert hex string to number
            if ((maskI & 0x7fff800) == 0) {
                alert("Aucun canal actif entre 11 et 26.\nVeuillez entrer une valeur entre 800 (canal 11) et 07FFF800 (canaux 11 à 26).")
                return;
            }
            if ((maskI & ~0x7fff800) != 0) {
                alert("Les canaux inférieurs à 11 et supérieurs à 26 sont invalides.\nVeuillez entrer une valeur entre 800 (canal 11) et 07FFF800 (canaux 11 à 26).")
                return;
            }
            payload = mask;
            break;
        case "SetTXPower":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/TxPower';
            payload = "ff"; // TODO
            break;
        case "GetTime":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/getTimeServer';
            payload = "";
            break;
        case "SetTime":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/setTimeServer';
            payload = ""; // Using current time from host.
            break;
        case "ErasePersistantDatas": // Erase PDM
            msg = '{{Vous êtes sur le point de d\'effacer la PDM de la zigate}} <b>'+js_zgNb+'</b>';
            msg += '{{<br>Tous les équipements connus de la zigate seront perdus et devront être réinclus.}}'
            msg += '{{<br>Si ils existent encore côté Jeedom ils passeront vite en time-out.}}'
            msg += '{{<br><br>Etes vous sur de vouloir continuer ?}}'
            bootbox.confirm(msg, function (result) {
                if (result)
                    sendToZigate('CmdAbeille'+js_zgNb+'/Ruche/ErasePersistentData', 'ErasePersistentData');
                return;
            });
            break;
        default:
            console.log("ERROR: Unsupported action '"+action+"'");
            return; // Nothing to do
        }

        if (topic != '')
            sendToZigate(topic, payload);
    }

    /* Display equipment icone (AbeilleEq-Main) */
    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

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
        console.log("addCmdToTable()");

        if (!isset(_cmd)) {
            var _cmd = {configuration: {}};
        }
        if (!isset(_cmd.configuration)) {
            _cmd.configuration = {};
        }
		console.log(_cmd);

        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

        tr += '<td>'; // Col 1 = Id
        tr += '     <span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';

        tr += '<td>'; // Col 2 = Jeedom cmd name
        tr += '     <input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Cmde Jeedom}}" title="Nom de la commande vue par Jeedom">';
        tr += '</td>';

        tr += '<td>'; // Col 3 = Type & sub-type
        if (init(_cmd.type) == 'info') {
            tr += '     <span class="cmdAttr form-control type input-sm" data-l1key="type" value="info" style="margin-bottom : 5px;" /></span>';
        } else if (init(_cmd.type) == 'action') {
            tr += '     <span class="cmdAttr form-control type input-sm" data-l1key="type" value="action" style="margin-bottom : 5px;" /></span>';
        } else { // New command
            tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
        }
        tr += '     <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';

        <?php if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) { ?>
            tr += '<td>'; // Col 4 = Abeille command name
            tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="topic" >';
            tr += '</td>';

            tr += '<td>'; // Col 5 = Parameters Abeille cmd
            if ((init(_cmd.type) == 'info') || (init(_cmd.type) == '')) { // Info or new cmd
            } else if (init(_cmd.type) == 'action') {
                tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" style="height : 33px;" placeholder="{{Payload}}">';
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
            tr += '     <select class="form-control cmdAttr input-sm" data-l1key="configuration" data-l2key="PollingOnCmdChange" title="{{Si vous souhaitez forcer l execution de cette commande suite a une mise a jour d une commande info, choisissez la commande info.}}" >';
            tr += '         <option value="">Aucun</option>';
            tr += '         <?php foreach ( $eqLogic->getCmd('info') as $cmd ) { echo "<option value=\"".$cmd->getConfiguration("topic")."\">".$cmd->getName()."</option>"; } ?>';
            tr += '     </select></br>';
            tr += '     <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="PollingOnCmdChangeDelay" style="height : 33px;" placeholder="{{en secondes}}" title="{{Temps souhaité entre Cmd Info Change et Execution de cette commande.}}" ><br/>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="configuration" data-l2key="RefreshData" title="{{Si vous souhaitez l execution de cette commande pour rafraichir l info par exemple au demarrage d abeille.}}" />{{Rafraichir}}</label></span><br> ';
        }
        tr += '</td>';

        tr += '<td>'; // Col 7
        if ((init(_cmd.type) == 'info') || (init(_cmd.type) == '')) { // Info or new command
            // Col 7 = Affiche + Hist + Invert + Min/Max
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary" checked/>{{Inverser}}</label></span> </br>';
            tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"> - ';
            tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;">';
        } else if (init(_cmd.type) == 'action') {
            // Col 7 = Affiche
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span><br> ';
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
                tr += ' <a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
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

</script>
