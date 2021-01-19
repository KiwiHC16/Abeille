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
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
    <a class="btn btn-danger  eqLogicAction pull-right" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>

    <ul class="nav nav-tabs" role="tablist">
        <li role="tab"               ><a href="index.php?v=d&m=Abeille&p=Abeille"><i class="fas fa-arrow-circle-left"></i></a></li>
        <li role="tab" class="active"><a href="#eqlogictab"><i class="fas fa-home"></i>{{Equipement}}</a></li>
        <li role="tab"               ><a href="#paramtab"><i class="fas fa-list-alt"></i>{{Paramètres}}</a></li>
        <li role="tab"               ><a href="#commandtab"><i class="fas fa-align-left"></i>{{Commandes}}</a></li>
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

<!-- <?php include_file('core', 'plugin.template', 'js'); ?> -->
<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<script>
    /* Note: Why using different code than 'plugin.template' ?
             Eq detail page loaded (hidden) in main page by default. Now loaded only if detail required.
             If an 'eqLogicAttr' is displayed in different tab (ex logicalId) this may lead to bad result after saving changes.
             Globally much better control on what we want to do with the content. */

    /* AbeilleEq page opened. Let's update display.
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
            console.log("data="+data);
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
            // modifyWithoutSave = false;
        }
    });

    /* Click on 'save' button.
       Save all changes of 'eqLogicAttr' or 'cmdAttr' to Jeedom DB.
       From 'core/js/plugin.template.js' */
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
                // modifyWithoutSave = false;
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
    $('.eqLogicAction[data-action=remove]').on('click', function () {
        console.log("eqLogicAction[data-action=remove], eqId="+js_eqId);
        eqType = "Abeille";
        // if ($('.eqLogicAttr[data-l1key=id]').value() != undefined) {
            msg = '{{Vous êtes sur le point de supprimer l\'équipement}} <b>'+$('.eqLogicAttr[data-l1key=name]').value()+'</b> de Jeedom';
            msg += '{{<br>Si il es toujours dans le réseau Zigbee il le restera et pourrait toujours renvoyer des messages.}}'
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
                            // modifyWithoutSave = false;
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


    /* Click on 'advanced config' button.
       From 'core/js/plugin.template.js' */
    $('.eqLogic .eqLogicAction[data-action=configure]').off('click').on('click', function () {
        $('#md_modal').dialog({title: "{{Configuration de l'équipement}}"});
        $('#md_modal').load('index.php?v=d&modal=eqLogic.configure&eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
    });

    /* Click on command 'config' button.
       From 'core/js/plugin.template.js' */
    $('#div_pageContainer').on( 'click', '.cmd .cmdAction[data-action=configure]',function () {
        $('#md_modal').dialog({title: "{{Configuration commande}}"});
        $('#md_modal').load('index.php?v=d&modal=cmd.configure&cmd_id=' + $(this).closest('.cmd').attr('data-cmd_id')).dialog('open');
    });

    /* Click on command 'test' button.
       From 'core/js/plugin.template.js' */
    $('#div_pageContainer').on('click', '.cmd .cmdAction[data-action=test]',function (event) {
        $.hideAlert();
        if ($('.eqLogicAttr[data-l1key=isEnable]').is(':checked')) {
            var id = $(this).closest('.cmd').attr('data-cmd_id');
            jeedom.cmd.test({id: id});
        } else {
            $('#div_alert').showAlert({message: '{{Veuillez activer l\'équipement avant de tester une de ses commandes}}', level: 'warning'});
        }
    });

    /* Display equipment icone (AbeilleEq-Main) */
    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

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
        case "StartNetwork":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/startNetwork';
            payload = 'StartNetwork'; // Really required ?
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
            payload = ""; // TODO
            break;
        case "SetChannelMask":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/setChannelMask';
            mask = document.getElementById("idChannelMask").value;
            console.log("mask="+mask);
            if (mask == "")
                return; // Empty
            function isHex(h) {
                var a = parseInt(h,16);
                return (a.toString(16) === h)
            }
            if (!isHex(mask)) {
                alert("Le masque doit être une valeur hexa");
                return;
            }
            var maskI = parseInt(mask, 16); // Convert hex string to number
            if ((maskI & 0x7fff800) == 0) {
                alert("Aucun canal actif entre 11 et 26. Veuillez corriger.")
                return;
            }
            // TODO: More checks to add ?
            payload = mask;
            break;
        case "SetTXPower":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/TxPower';
            payload = "ff"; // TODO
            break;
        case "GetTime":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/getTimeServer';
            payload = ""; // TODO
            break;
        case "SetTime":
            topic = 'CmdAbeille'+js_zgNb+'/Ruche/setTimeServer';
            payload = ""; // TODO
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

</script>
