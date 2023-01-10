<!-- This file displays equipment commands.
     Included by 'AbeilleEq.php' -->

<?php
    include_file('core', 'plugin.template', 'js');
    include_file('desktop', 'Abeille', 'js', 'Abeille');
?>

<script>
    /* AbeilleEq page opened. Let's update display content.
       From 'core/js/plugin.template.js' */
    $.showLoading();
    jeedom.eqLogic.print({
        type: "Abeille",
        id: js_eqId,
        status : 1,
        error: function (error) {
            console.log("jeedom.eqLogic.print() error");
            $.hideLoading();
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

            /* Commands update */
            if ('function' == typeof (addCmdToTable)) {
                // Commented to not remove '.cmd' from Abeille/Zigbee tab
                // $('.cmd').remove();
                // $('#table_cmd tbody .cmd').remove();
                /* Reminder: Remove '.cmd' from command tab */
                $('#table_cmd tbody').empty();
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
            $.hideLoading();
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
                /* Reminder: Take only '.cmdAttr' from command tab */
                eqLogic.cmd = $(this).find('#table_cmd .cmd').getValues('.cmdAttr');
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
            var msg = "{{Vous êtes sur le point de supprimer <b>"+$('.eqLogicAttr[data-l1key=name]').value()+"</b> de Jeedom.";
            msg += "<br><br>Si il est toujours dans le réseau, il deviendra 'fantome' et devrait être réinclu automatiquement au fur et à mesure de son reveil et ce, tant qu'on ne le force pas à quitter le réseau.";
            msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
            bootbox.confirm(msg, function (result) {
                if (result) {
                    eqAddr =
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

                            // Informing parser that some equipements have to be considered "phantom"
                            var xhr = new XMLHttpRequest();
                            xhr.open("GET", "plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueXToParser+"&msg=type:eqRemoved_net:Abeille"+js_zgId+"_eqList:"+js_eqAddr, true);
                            xhr.send();
                        }
                    });
                }
            });
        // } else {
        //     $('#div_alert').showAlert({message: '{{Veuillez d\'abord sélectionner un}} ' + eqType, level: 'danger'});
        // }
    });

	/*
	 * Abeille/Zigbee tab
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
        case "setLED":
            topic = 'CmdAbeille'+js_zgId+'/0000/setZgLed';
            if (param == "ON")
                payload = "value=1";
            else
                payload = "value=0";
            break;
        case "setCertif":
            if (param == "CE")
                topic = 'CmdAbeille'+js_zgId+'/0000/setCertificationCE';
            else
                topic = 'CmdAbeille'+js_zgId+'/0000/setCertificationFCC';
            break;
        case "startNetwork": // Not required for end user but for developper.
            topic = 'CmdAbeille'+js_zgId+'/0000/startZgNetwork';
            payload = '';
            break;
        case "startNetworkScan": // Not required for end user but for developper.
            topic = 'CmdAbeille'+js_zgId+'/0000/startZgNetworkScan';
            payload = '';
            break;
        case "setInclusion":
            topic = 'CmdAbeille'+js_zgId+'/0000/setZgPermitMode';
            payload = 'mode='+param;
            break;
        case "setMode":
            topic = 'CmdAbeille'+js_zgId+'/0000/setZgMode';
            if (param == "Normal")
                payload = 'mode=normal';
            else if (param == "Raw")
                payload = 'mode=raw';
            else
                payload = 'mode=hybrid';
            break;
        case "setExtPANId": // Not critical. No need so far.
            topic = 'CmdAbeille'+js_zgId+'/0000/setExtendedPANID';
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
        //     topic = 'CmdAbeille'+js_zgId+'/0000/setZgChannelMask';
        //     payload = 'mask='+mask;
        //     break;
        case 'setChannel':
            var chan = $("#idZgChan").val();
            if (chan == 0)
                mask = 0x7fff800; // All channels = auto
            else
                mask = 1 << chan;
            mask = mask.toString(16);
            console.log("  Channel=" + chan + " => mask=" + mask);
            topic = 'CmdAbeille'+js_zgId+'/0000/setZgChannelMask';
            payload = 'mask='+mask;
            sendToZigate(topic, payload);

            console.log("settings1=", js_eqSettings);
            js_eqSettings.channel = chan;
            console.log("settings2=", js_eqSettings);
            saveSettings();

            topic = 'CmdAbeille'+js_zgId+'/0000/startZgNetwork';
            payload = '';
            break;
        case "getTXPower":
            topic = 'CmdAbeille'+js_zgId+'/0000/getZgTxPower';
            payload = "";
            break;
        case "setTXPower":
            topic = 'CmdAbeille'+js_zgId+'/0000/TxPower';
            payload = "ff"; // TODO
            break;
        case "getTime":
            topic = 'CmdAbeille'+js_zgId+'/0000/getZgTimeServer';
            payload = "";
            break;
        case "setTime":
            topic = 'CmdAbeille'+js_zgId+'/0000/setZgTimeServer';
            payload = ""; // Using current time from host.
            break;
        case "erasePersistantDatas": // Erase PDM
            msg = '{{Vous êtes sur le point de d\'effacer la PDM de la zigate}} <b>'+js_zgId+'</b>';
            msg += '{{<br>Tous les équipements connus de la zigate seront perdus et devront être réinclus.}}'
            msg += '{{<br>Si ils existent encore côté Jeedom ils passeront vite en time-out.}}'
            msg += '{{<br><br>Etes vous sur de vouloir continuer ?}}'
            bootbox.confirm(msg, function (result) {
                if (result)
                    sendToZigate('CmdAbeille'+js_zgId+'/0000/ErasePersistentData', 'ErasePersistentData');
                return;
            });
            break;
        case "getInclusionStatus":
            topic = 'CmdAbeille'+js_zgId+'/0000/permitJoin';
            payload = "Status";
            break;
        case "getZgVersion":
            topic = 'CmdAbeille'+js_zgId+'/0000/getZgVersion';
            payload = "";
            break;
        case "resetZigate":
            topic = 'CmdAbeille'+js_zgId+'/0000/resetZg';
            payload = "";
            break;
        default:
            console.log("ERROR: Unsupported action '"+action+"'");
            return; // Nothing to do
        }

        if (topic != '')
            sendToZigate(topic, payload);
    }

    /* Force update of some dynamic fields */
    sendZigate('getTime', ''); // Will update last comm too
    sendZigate('getInclusionStatus', ''); // Will update last comm too
    sendZigate('getZgVersion', ''); // Will update last comm too

    /* Display equipment icon (AbeilleEq-Main) */
    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

    /* PiZigate HW reset */
    function resetPiZigate() {
        console.log("resetPiZigate()");

        // $('#md_modal2').dialog({title: "{{Reset HW de la PiZigate}}"});
        // $('#md_modal2').load('index.php?v=d&plugin=Abeille&modal=AbeilleConfigPage.modal&cmd=resetPiZigate').dialog('open');
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'resetPiZigate'
            },
            dataType: 'json',
            global: false,
            success: function (json_res) {
            }
        });
    }

    /* Remove given local JSON file */
    function removeLocalJSON(jsonId) {
        console.log("removeLocalJSON("+jsonId+")");

        path = "core/config/devices_local/"+jsonId+"/"+jsonId+".json";
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'delFile',
                file: path
            },
            dataType: 'json',
            global: false,
            success: function (json_res) {
                var msg = "{{Le fichier de configuration local a été supprimé.<br>";
                msg += "L'équipement ayant été inclu avec ce fichier vous devez peut-être refaire une inclusion ou recharger le JSON & reconfigurer pour être à jour.}}";
                bootbox.confirm(msg, function (result) {
                    window.location.reload();
                });
            }
        });
    }

    /* Reinit Jeedom device & reconfigure.
       WARNING: If battery powered, device must be wake up. */
    function reinit(eqId) {
        console.log("reinit("+eqId+")");

        var msg = "{{Vous êtes sur le point de réinitialiser cet équipement (équivalent à une nouvelle inclusion).";
        msg += "<br><br>Tout sera remis à jour à partir du modèle JSON excepté le nom, l'ID Jeedom ainsi que ses adresses.";
        if (js_batteryType != '') {
            msg += "<br>Comme il fonctionne sur batterie, il vous faut le réveiller immédiatement après avoir cliqué sur 'Ok'.";
        }
        msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
        bootbox.confirm(msg, function (result) {
            if (result == false)
                return

            var xhttp = new XMLHttpRequest();
            xhttp.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=reinit&eqId="+eqId, false);
            xhttp.send();
        });
    }

    /* Update device from JSON (reload JSON) */
    // function updateFromJSON(eqNet, eqAddr) {
    //     console.log("updateFromJSON("+eqNet+","+eqAddr+")");

    //     var xhttp = new XMLHttpRequest();
    //     xhttp.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&topic=CmdCreate"+eqNet+"_"+eqAddr+"_updateFromJson", false);
    //     xhttp.send();

    //     xhttp.onreadystatechange = function() {
    //     };
    // }

    /* Reconfigure device by sending 'execAtCreation' commands.
       WARNING: If battery powered, device must be wake up. */
    // function reconfigure(eqId) {
    //     console.log("reconfigure("+eqId+")");

    //     if (js_batteryType != '') {
    //         var msg = "{{Vous êtes sur le point de reconfigurer cet équipement.";
    //         msg += "<br>Comme il fonctionne sur batterie, il vous faut le réveiller immédiatement après avoir cliqué sur 'Ok'.";
    //         msg += "<br><br>Etes vous sur de vouloir continuer ?}}";
    //         bootbox.confirm(msg, function (result) {
    //             if (result == false)
    //                 return
    //             var xhttp = new XMLHttpRequest();
    //             xhttp.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=reconfigure&eqId="+eqId, false);
    //             xhttp.send();
    //         });
    //     } else {
    //         var xhttp = new XMLHttpRequest();
    //         xhttp.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=reconfigure&eqId="+eqId, false);
    //         xhttp.send();
    //     }
    // }

    function interrogate(request) {
        console.log("interrogate("+request+")");

        logicalId = "Abeille"+js_zgId+"_"+js_eqAddr;
        if (request == "getRoutingTable") {
            topic = "Cmd"+logicalId+"_getRoutingTable";
            payload = "address="+js_eqAddr;
        } else if (request == "getBindingTable") {
            topic = "Cmd"+logicalId+"_getBindingTable";
            payload = "address="+js_eqAddr;
        } else if (request == "getNeighborTable") {
            topic = "Cmd"+logicalId+"_getNeighborTable";
            startIdx = document.getElementById("idStartIdx").value;
            payload = "startIndex="+startIdx;
        } else if (request == "getActiveEndPoints") {
            topic = "Cmd"+logicalId+"_getActiveEndpoints";
            payload = "addr="+js_eqAddr;
        } else if (request == "getSimpleDescriptor") {
            topic = "Cmd"+logicalId+"_getSimpleDescriptor";
            ep = document.getElementById("idEpSDR").value;
            payload = "ep="+ep;
        } else if (request == "getNodeDescriptor") {
            topic = "Cmd"+logicalId+"_getNodeDescriptor";
            payload = "";
        } else if (request == "getIeeeAddress") {
            topic = "Cmd"+logicalId+"_getIeeeAddress";
            payload = "";
        } else if (request == "mgmtNetworkUpdateReq") {
            topic = "Cmd"+logicalId+"_managementNetworkUpdateRequest";
            payload = "";
        } else if (request == "leaveRequest") {
            topic = "Cmd"+logicalId+"_LeaveRequest";
            payload = "IEEE="+js_eqIeee;
        }

        else if (request == "readReportingConfig") {
            topic = "Cmd"+logicalId+"_readReportingConfig";
            ep = document.getElementById("idEp").value;
            clustId = document.getElementById("idClustId").value;
            attrId = document.getElementById("idAttrId").value;
            payload = "addr="+js_eqAddr+"_ep="+ep+"_clustId="+clustId+"_attrId="+attrId;
        } else if (request == "readAttribute") {
            topic = "Cmd"+logicalId+"_readAttribute";
            ep = document.getElementById("idEpA").value;
            clustId = document.getElementById("idClustIdA").value;
            attrId = document.getElementById("idAttrIdA").value;
            payload = "ep="+ep+"_clustId="+clustId+"_attrId="+attrId;
            manufId = document.getElementById("idManufIdRA").value;
            if (manufId != '')
                payload += "_manufId="+manufId;
        } else if (request == "writeAttribute") {
            topic = "Cmd"+logicalId+"_writeAttribute";
            ep = document.getElementById("idEpWA").value;
            clustId = document.getElementById("idClustIdWA").value;
            attrId = document.getElementById("idAttrIdWA").value;
            value = document.getElementById("idValueWA").value;
            payload = "ep="+ep+"_clustId="+clustId+"_attrId="+attrId+"_attrVal="+value;
            attrType = document.getElementById("idAttrTypeWA").value;
            if (attrType != 'FF')
                payload += "_attrType="+attrType;
            dir = document.getElementById("idDirWA").value;
            if (dir != '')
                payload += "_dir="+dir;
            manufId = document.getElementById("idManufIdWA").value;
            if (manufId != '')
                payload += "_manufId="+manufId;
        } else if (request == "writeAttribute0530") {
            topic = "Cmd"+logicalId+"_writeAttribute0530";
            ep = document.getElementById("idEpWA2").value;
            clustId = document.getElementById("idClustIdWA2").value;
            attrId = document.getElementById("idAttrIdWA2").value;
            value = document.getElementById("idValueWA2").value;
            payload = "ep="+ep+"_clustId="+clustId+"_attrId="+attrId+"_attrVal="+value;
            attrType = document.getElementById("idAttrTypeWA2").value;
            if (attrType != 'FF')
                payload += "_attrType="+attrType;
            dir = document.getElementById("idDirWA2").value;
            if (dir != '')
                payload += "_dir="+dir;
        } else if (request == "discoverCommandsReceived") {
            topic = "Cmd"+logicalId+"_discoverCommandsReceived";
            ep = document.getElementById("idEpB").value;
            clustId = document.getElementById("idClustIdB").value;
            // start = document.getElementById("idStartB").value;
            start = "00";
            payload = "addr="+js_eqAddr+"_ep="+ep+"_clustId="+clustId+"_start="+start;
        } else if (request == "discoverAttributesExt") {
            topic = "Cmd"+logicalId+"_discoverAttributesExt";
            ep = document.getElementById("idEpC").value;
            clustId = document.getElementById("idClustIdC").value;
            // start = document.getElementById("idStartC").value;
            startId = "0000";
            payload = "ep="+ep+"_clustId="+clustId+"_startId="+startId;
        } else if (request == "discoverAttributes") {
            topic = "Cmd"+logicalId+"_discoverAttributes";
            ep = document.getElementById("idEpD").value;
            clustId = document.getElementById("idClustIdD").value;
            // start = document.getElementById("idStartD").value;
            start = "00";
            payload = "addr="+js_eqAddr+"_ep="+ep+"_clustId="+clustId+"_start="+start;
        } else if (request == "bindToDevice") {
            topic = "Cmd"+logicalId+"_bind0030";
            ep = document.getElementById("idEpE").value;
            clustId = document.getElementById("idClustIdE").value;
            destIeee = document.getElementById("idIeeeE").value;
            destEp = document.getElementById("idEpE2").value;
            payload = "addr="+js_eqIeee+"_ep="+ep+"_clustId="+clustId+"_destAddr="+destIeee+"_destEp="+destEp;
        } else if (request == "bindToGroup") {
            topic = "Cmd"+logicalId+"_bind0030";
            ep = document.getElementById("idEpF").value;
            clustId = document.getElementById("idClustIdF").value;
            destGroup = document.getElementById("idGroupF").value;
            payload = "addr="+js_eqIeee+"_ep="+ep+"_clustId="+clustId+"_destAddr="+destGroup;
        } else if (request == "configureReporting") {
            topic = "Cmd"+logicalId+"_configureReporting";
            ep = document.getElementById("idEpCR").value;
            clustId = document.getElementById("idClustIdCR").value;
            attrId = document.getElementById("idAttrIdCR").value;
            manufId = document.getElementById("idManufIdCR").value;
            attrType = document.getElementById("idAttrTypeCR").value;
            min = document.getElementById("idMinCR").value;
            max = document.getElementById("idMaxCR").value;
            change = document.getElementById("idChangeCR").value;
            payload = "ep="+ep+"_clustId="+clustId+"_attrId="+attrId;
            if (min != '')
                payload += "_minInterval="+min;
            if (max != '')
                payload += "_maxInterval="+max;
            if (change != '')
                payload += "_changeVal="+change;
            if (attrType != 'FF')
                payload += "_attrType="+attrType;
            if (manufId != '')
                payload += "_manufId="+manufId;
        }

        /* Cluster specific commands */
        else if (request == "0000-ResetToFactory") {
            topic = "Cmd"+logicalId+"_cmd-0000";
            ep = document.getElementById("idEpG").value;
            payload = "ep="+ep+"_cmd=00";
        } else if (request == "0201-SetPoint") {
            topic = "Cmd"+logicalId+"_cmd-0201";
            ep = document.getElementById("idEpC0201-00").value;
            payload = "ep="+ep+"_cmd=00";
            amount = document.getElementById("idAmountC0201-00").value;
            if (amount != '')
                payload += "_amount="+amount;
        } else if (request == "0300-MoveToColor") {
            topic = "Cmd"+logicalId+"_setColour";
            ep = document.getElementById("idEp-MTC").value;
            X = document.getElementById("idX-MTC").value;
            Y = document.getElementById("idX-MTC").value;
            payload = "EP="+ep+"_X="+X+"_Y="+Y;
        } else if (request == "1000-GetGroups") {
            topic = "Cmd"+logicalId+"_cmd-1000";
            ep = document.getElementById("idEpC1000-41").value;
            payload = "ep="+ep+"_cmd=41_startIdx=00";
        } else if (request == "1000-GetEndpoints") {
            topic = "Cmd"+logicalId+"_cmd-1000";
            ep = document.getElementById("idEpC1000-42").value;
            payload = "ep="+ep+"_cmd=42_startIdx=00";
        }

        else {
            console.log("Unknown request "+request);
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueXToCmd+"&topic="+topic+"&payload="+payload, false);
        xhttp.send();

        xhttp.onreadystatechange = function() {
        };
    }

    /* Download local discovery */
    function downloadLocalDiscovery(modelId, manufId) {
        console.log("downloadLocalDiscovery()");

        path = 'core/config/devices_local/'+modelId+'_'+manufId+'/discovery.json';

        window.open('plugins/Abeille/core/php/AbeilleDownload.php?pathfile='+path, "_blank", null);
    }

    /* eqLogic/configuration settings (ab::settings) read */
    function getSettings(eqId) {
        console.log("getSettings()");

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'getSettings',
                edId: eqId
            },
            dataType: 'json',
            global: false,
            success: function (json_res) {
            }
        });
    }

    /* eqLogic/configuration settings (ab::settings) update */
    function saveSettings() {
        console.log("saveSettings(): settings=", js_eqSettings);
        console.log("  js_eqSettings type=", typeof(js_eqSettings));

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'saveSettings',
                eqId: js_eqId,
                settings: JSON.stringify(js_eqSettings)
            },
            dataType: 'json',
            global: false,
            success: function (json_res) {
            }
        });
    }

</script>