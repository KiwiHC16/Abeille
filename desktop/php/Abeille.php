<?php
    /* Developers debug features & PHP errors */
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["defines"])) {
            $arr = $dbgConfig["defines"];
            foreach ($arr as $idx => $value) {
                if ($value == "Tcharp38")
                    $dbgTcharp38 = true;
            }
        }
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    sendVarToJS('eqType', 'Abeille');
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS

    $eqLogics = eqLogic::byType('Abeille');
    /* Creating a per Zigate list of eq ids.
       For each zigate, the first eq is the zigate.
       $eqPerZigate[$zgId][id1] => id for zigate
       $eqPerZigate[$zgId][id2] => id for next eq... */
    $eqPerZigate = array(); // All equipements id/addr per zigate
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
        list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
        $zgId = hexdec(substr($eqNet, 7)); // Extracting zigate number from network
        $eqId = $eqLogic->getId();
        $eq = [];
        $eq['id'] = $eqId;
        $eq['addr'] = $eqAddr;
        $eq['mainEp'] = $eqLogic->getConfiguration('mainEP', '');
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
        $eq['jsonId'] = $eqModel ? $eqModel['id'] : '';
        $eq['name'] = $eqLogic->getName();
        if ($eqAddr == "0000") {
            if (isset($eqPerZigate[$zgId][$eqId]))
                array_unshift($eqPerZigate[$zgId][$eqId], $eq);
            else
                $eqPerZigate[$zgId][$eqId] = $eq;
        } else
            $eqPerZigate[$zgId][$eqId] = $eq;
    }
    $GLOBALS['eqPerZigate'] = $eqPerZigate;
    echo '<script>var js_eqPerZigate = \''.json_encode($eqPerZigate).'\';</script>';
    echo '<script>var js_urlProducts = "'.urlProducts.'";</script>';

    // logDebug("eqPerZigate=".json_encode($eqPerZigate)); // In dev mode only
    // $parametersAbeille = AbeilleTools::getParameters();
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div class="row row-overflow">
	<!-- <form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post"> -->

		<div class="col-xs-12 eqLogicThumbnailDisplay">

            <!-- Top level buttons  -->
            <?php include 'Abeille-TopButtons.php'; ?>

            <!-- Equipements -->
            <?php include 'Abeille-Bees.php'; ?>

            <!-- Groups management  -->
            <?php include 'Abeille-Groups.php'; ?>

            <!-- Replace equipment on Jeedom side -->
            <?php include 'Abeille-ReplaceEq.php'; ?>

            <!-- Gestion des ReHome / migration d equipements  -->
            <?php include 'Abeille-MigrateEq.php'; ?>

            <?php include 'Abeille-NewZigate.php'; ?>

            <?php if (isset($dbgDeveloperMode)) { ?>
            <legend><i class="fa fa-cogs"></i> {{Visible en MODE DEV UNIQUEMENT}}</legend>
            <div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

                <!-- Gestion des scenes  -->
                <?php include 'Abeille-Scenes.php'; ?>

            </div>
            <?php } ?>

        </div> <!-- End eqLogicThumbnailDisplay -->

        <!-- Hidden equipment detail page -->
        <?php include 'Abeille-Eq.php'; ?>

	<!-- </form> -->
</div>

<!-- Scripts -->
<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<script>
    // TODO: addCmdToTable() to be moved to Abeille.js but need to fix remaining PHP code in it.
    function addCmdToTable(_cmd) {
        if (!isset(_cmd)) {
            var _cmd = {configuration: {}};
        }
        if (!isset(_cmd.configuration)) {
            _cmd.configuration = {};
        }
		console.log("addCmdToTable(typ="+_cmd.type+", jName="+_cmd.name+")");

        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

        tr += '<td class="hidden-xs">'; // Col 1 = Id
        tr += '     <span class="cmdAttr" data-l1key="id" title="{{Identifiant Jeedom}}"></span>';
        tr += '</td>';

        tr += '<td>'; // Jeedom cmd name
        tr += '     <input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom}}" title="{{Nom de la commande vue par Jeedom}}">';
        tr += '</td>';

        tr += '<td>'; // Type & sub-type
        if (init(_cmd.type) == 'info') {
            tr += '     <span class="cmdAttr form-control type input-sm" data-l1key="type" value="info" style="margin-bottom:5px;" /></span>';
        } else if (init(_cmd.type) == 'action') {
            tr += '     <span class="cmdAttr form-control type input-sm" data-l1key="type" value="action" style="margin-bottom:5px;" /></span>';
        } else { // New command
            tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
        }
        tr += '     <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';

        tr += '<td>'; // Logical ID
        tr += '     <input class="cmdAttr form-control input-sm" data-l1key="logicalId" placeholder="{{ID logique}}" style="width:100%;">';
        tr += '</td>';

        tr += '<td>'; // Options
        tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible"/>{{Afficher}}</label></span>';
        tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="forceReturnLineBefore"/>{{Break avant}}</label></span>';
        tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="forceReturnLineAfter"/>{{Break après}}</label></span>';
        if (init(_cmd.type) == 'info') { // Info
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized"/>{{Historiser}}</label></span>';
            tr += '     <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span><br>';
            tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:40%; display:inline-block;"> - ';
            tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:40%; display:inline-block;">';
        } else if (init(_cmd.type) == 'action') {
            // tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:40%; display:inline-block;"> - ';
            // tr += '     <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:40%; display:inline-block;">';
        }
        tr += '</td>';

        if (typeof js_dbgDeveloperMode != 'undefined') {

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
        }

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
        //     jeedom.eqLogic.buildSelectCmd({
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
        jeedom.eqLogic.buildSelectCmd({
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
    } // End addCmdToTable()
</script>
<?php include_file('core', 'plugin.template', 'js'); ?>
<script>
    // test
    // $(".eqLogicAction[data-action=remove]")
    // .off("click")
    // .on("click", function (evt) {
    //     console.log("AFTER eqLogicAction[data-action=remove] click: evt=", evt);
    // });
</script>
