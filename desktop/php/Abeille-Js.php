<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>
    /* Remove default Jeedom 'onclick' event for 'eqLogicDisplayCard' class
       and replace it by a new one. */
    // $(".eqLogicDisplayCard").off("click");
    // $(".eqLogicDisplayCard").on('click', function () {
    //     console.log("eqLogicDisplayCard click");
    //     if (!isset($(this).attr('data-eqLogic_id'))) {
    //       console.log("ERROR: 'data-eqLogic_id' is not defined");
    //       return;
    //     }
    //     var eqId = $(this).attr('data-eqLogic_id');
    //     console.log("eqId="+eqId);
    //     window.location.href = "index.php?v=d&m=Abeille&p=AbeilleEq&id="+eqId;
    // });
    $(".eqLogicDisplayCard").on('click', function () {
        console.log("eqLogicDisplayCard click");
        if (!isset($(this).attr('data-eqLogic_id'))) {
          console.log("ERROR: 'data-eqLogic_id' is not defined");
          return;
        }
        var eqId = $(this).attr('data-eqLogic_id');
        console.log("eqId="+eqId);

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

                // Updating advanced common infos
                document.getElementById('idEqName').value = eq.name;
                document.getElementById('idEqId').value = eqId;
                document.getElementById('idEqAddr').value = eq.addr;
                document.getElementById('idZgType').value = eq.zgType;

                // Show/hide zigate or devices part
                zgPart = document.getElementById('idAdvZigate');
                devPart = document.getElementById('idAdvDevices');
                if (eq.addr == "0000") {
                    zgPart.style.display = "block";
                    devPart.style.display = "none";
                } else {
                    zgPart.style.display = "none";
                    devPart.style.display = "block";
                }

                // Updating info cmds
                const advInfoCmds = document.querySelectorAll('[advInfo]'); // All with attribute named "advInfo"
                for (let i = 0; i < advInfoCmds.length; i++) {
                    elm = advInfoCmds[i];
                    console.log("advInfoCmd=", advInfoCmds[i]);
                    // elm.classList.add('col-sm-5');
                    // elm.classList.add('cmd');
                    // elm.setAttribute('data-eqlogic_id', eqId);
                    cmdLogicId = elm.getAttribute('advInfo');
                    console.log("cmdLogicId=", cmdLogicId);
                    if (typeof eq.cmds[cmdLogicId] != 'undefined') {
                        cmd = eq.cmds[cmdLogicId];
                        console.log("cmd=", cmd);
                        cmdId = cmd.id;
                        cmdVal = cmd.val;
                        console.log("cmdVal=", cmdVal);
                        // elm.setAttribute('data-cmd_id', cmdId);
                        child = elm.firstElementChild;
                        if (child != null) {
                            console.log("child=", child);
                            child.id = 'cmdId-'+cmdId;
                            child.setAttribute('value', cmdVal);
                        }

                        jeedom.cmd.addUpdateFunction(cmdId, updateInfoCmd);
                        // console.log("jeedom.cmd.update=", jeedom.cmd.update);
                    }
                }

                // Settings default EP
                var items = document.getElementsByClassName("advEp");
                for (var i=0; i < items.length; i++) {
                    items[i].value = eq.defaultEp;
                }

                // Reset HW visible is type "PI"
                if ((eq.zgType == "PI") || (eq.zgType == "PIv2")) {
                    resetHw = document.getElementById('idAdvResetHw');
                    resetHw.style.display = "block";
                }

                // Zigbee channel user choice
                if (eq.zgChan != '') {
                    select = document.getElementById('idZgChan');
                    select.value = eq.zgChan;
                }
            }
        });
    });

    // This function is called each time a corresponding info cmd has a value update.
    // Reminder: jeedom.cmd.update[cmdId] = updateInfoCmd()
    function updateInfoCmd(_options) {
        console.log("updateInfoCmd(): options=", _options);
        cmdId = _options.cmd_id;
        // var elm2 = document.getElementById('cmdId-9999');
        // console.log('elm2=', elm2);
        var elm = document.getElementById('cmdId-'+cmdId);
        if (elm == null) {
            console.log("ERROR: Cannot find elm 'cmdId-"+cmdId+"'");
            return;
        }
        console.log('elm=', elm);
        if (true /*$isInput*/)
            elm.value = _options.display_value;
        else // Not <input>. Assuming <span>
            elm.textContent = _options.display_value;
    }

    // /* Show or hide developer area.
    //    If developer mode is enabled, default is to always expand this area. */
    // $('#idDevGrpShowHide').on('click', function () {
    //     console.log("idDevGrpShowHide() click");
    //     var Label = document.getElementById("idDevGrpShowHide").innerText;
    //     if (Label == "Montrer") {
    //         document.getElementById("idDevGrpShowHide").innerText = "Cacher";
    //         document.getElementById("idDevGrpShowHide").className = "btn btn-danger";
    //         $("#idDevGrp").show();
    //     } else {
    //         document.getElementById("idDevGrpShowHide").innerText = "Montrer";
    //         document.getElementById("idDevGrpShowHide").className = "btn btn-success";
    //         $("#idDevGrp").hide();
    //     }
    // });
    // if ((typeof js_dbgDeveloperMode != 'undefined') && (js_dbgDeveloperMode == 1)) {
    //     var Label = document.getElementById("idDevGrpShowHide").innerText;
    //     document.querySelector('#idDevGrpShowHide').click();
    // }

    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

    /* Check which equipements are selected.
        Returns: array of objects {zgId, addr} */
    function getSelected() {
        console.log("getSelected()");

        // Get all eq infos
        eval('var eqPerZigate = JSON.parse(js_eqPerZigate);');
        console.log("eqPerZigate=", eqPerZigate);

        var selected = new Array();
        var list = document.querySelectorAll('input[type=checkbox]');
        for (i = 0; i < list.length; i++) {
            item = list[i];
            // console.log("item.id=", item.id);
            if (!item.checked)
                continue;

            // console.log(item.id+" CHECKED");
            // console.log("item=", item);
            idSplit = item.id.split('-'); // idBeeCheckedX-Y => [idBeeCheckedX, Y]
            id = idSplit[1];
            // console.log("id=", id);
            zgId = idSplit[0].substring(12);
            // console.log("CHECKED: zgId="+zgId+", id="+id);

            var eq = new Object;
            eq['id'] = id;
            eq['zgId'] = zgId;
            eq['addr'] = eqPerZigate[zgId][id]['addr'];
            eq['mainEp'] = eqPerZigate[zgId][id]['mainEp'];
            selected.push(eq);
        }
        return selected;
    }

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
            topic = 'CmdAbeille'+zgId+'/0000/setZgLed';
            if (param == "ON")
                payload = "value=1";
            else
                payload = "value=0";
            break;
        case "setCertif":
            if (param == "CE")
                topic = 'CmdAbeille'+zgId+'/0000/setCertificationCE';
            else
                topic = 'CmdAbeille'+zgId+'/0000/setCertificationFCC';
            break;
        case "startNetwork": // Not required for end user but for developper.
            topic = 'CmdAbeille'+zgId+'/0000/startZgNetwork';
            payload = '';
            break;
        case "startNetworkScan": // Not required for end user but for developper.
            topic = 'CmdAbeille'+zgId+'/0000/startZgNetworkScan';
            payload = '';
            break;
        case "setInclusion":
            topic = 'CmdAbeille'+zgId+'/0000/setZgPermitMode';
            payload = 'mode='+param;
            break;
        case "setMode":
            topic = 'CmdAbeille'+zgId+'/0000/setZgMode';
            if (param == "Normal")
                payload = 'mode=normal';
            else if (param == "Raw")
                payload = 'mode=raw';
            else
                payload = 'mode=hybrid';
            break;
        case "setExtPANId": // Not critical. No need so far.
            topic = 'CmdAbeille'+zgId+'/0000/setExtendedPANID';
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
        //     topic = 'CmdAbeille'+zgId+'/0000/setZgChannelMask';
        //     payload = 'mask='+mask;
        //     break;
        case 'setChannel':
            msg = '{{Vous êtes sur le point de changer le canal Zigbee de la Zigate}} <b>'+zgId+'</b>';
            msg += '{{<br>Les équipements sur secteur devraient suivre mais pas forcément tous ceux sur batterie.}}'
            msg += '{{<br>Dans ce cas vous pourriez avoir à refaire une inclusion.}}'
            msg += '{{<br><br>Etes vous sur de vouloir continuer ?}}'
            bootbox.confirm(msg, function (result) {
                if (result) {
                    var chan = $("#idZgChan").val();
                    if (chan == 0)
                        mask = 0x7fff800; // All channels = auto
                    else
                        mask = 1 << chan;
                    mask = mask.toString(16);
                    // Note: missing leading 0 completed in AbeilleCmdProcess.
                    // while (mask.length < 8) { // Adding missing leading 0
                    //     mask = "0" + mask;
                    // }
                    console.log("  Channel=" + chan + " => mask=" + mask);

                    // Request ALL devices to change channel
                    // But WARNING.. those who are not RxONWhenIdle may not receive it.
                    topic = 'CmdAbeille'+zgId+'/FFFF/mgmtNetworkUpdateReq';
                    payload = 'scanChan='+mask+'&scanDuration=FE';
                    sendToZigate(topic, payload);

                    topic = 'CmdAbeille'+zgId+'/0000/setZgChannelMask';
                    payload = 'mask='+mask;
                    sendToZigate(topic, payload);

                    console.log("config=", js_config);
                    js_config['ab::zgChan'+zgId] = chan;
                    console.log("config2=", js_config);
                    saveConfig();

                    topic = 'CmdAbeille'+zgId+'/0000/startZgNetwork';
                    payload = '';
                    sendToZigate(topic, payload);
                }
                return;
            });

            break;
        case "getTXPower":
            topic = 'CmdAbeille'+zgId+'/0000/getZgTxPower';
            payload = "";
            break;
        case "setTXPower":
            topic = 'CmdAbeille'+zgId+'/0000/TxPower';
            payload = "ff"; // TODO
            break;
        case "getTime":
            topic = 'CmdAbeille'+zgId+'/0000/getZgTimeServer';
            payload = "";
            break;
        case "setTime":
            topic = 'CmdAbeille'+zgId+'/0000/setZgTimeServer';
            payload = ""; // Using current time from host.
            break;
        case "erasePersistantDatas": // Erase PDM
            msg = '{{Vous êtes sur le point de d\'effacer la PDM de la zigate}} <b>'+zgId+'</b>';
            msg += '{{<br>Tous les équipements connus de la zigate seront perdus et devront être réinclus.}}'
            msg += '{{<br>Si ils existent encore côté Jeedom ils passeront vite en time-out.}}'
            msg += '{{<br><br>Etes vous sur de vouloir continuer ?}}'
            bootbox.confirm(msg, function (result) {
                if (result)
                    sendToZigate('CmdAbeille'+zgId+'/0000/ErasePersistentData', 'ErasePersistentData');
                return;
            });
            break;
        case "getInclusionStatus":
            topic = 'CmdAbeille'+zgId+'/0000/permitJoin';
            payload = "Status";
            break;
        case "getZgVersion":
            topic = 'CmdAbeille'+zgId+'/0000/getZgVersion';
            payload = "";
            break;
        case "resetZigate":
            topic = 'CmdAbeille'+zgId+'/0000/resetZg';
            payload = "";
            break;
        default:
            console.log("ERROR: Unsupported action '"+action+"'");
            return; // Nothing to do
        }

        if (topic != '')
            sendToZigate(topic, payload);
    }

    // Send msg to AbeilleCmd
    function sendToCmd(action, param1 = '', param2 = '', param3 = '', param4 = '') {
        console.log("sendToCmd("+action+")");

        selected = getSelected();
        console.log("selected=", selected);

        function sendCmd(topic, payload) {
            var xhr = new XMLHttpRequest();
            msg = "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueXToCmd;
            topic = topic.replaceAll('&', '_');
            payload = payload.replaceAll('&', '_');
            if (payload != '')
                xhr.open("GET", msg+"&topic="+topic+"&payload="+payload, false);
            else
                xhr.open("GET", msg+"&topic="+topic, false);
            xhr.send();
        }

        switch(action) {
        case "getGroups":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            selected.forEach((eq) => {
                sendCmd('CmdAbeille'+eq['zgId']+'/'+eq['addr']+'/getGroupMembership', 'ep='+eq['mainEp']);
                setTimeout(function () {
                    location.reload(true);
                    },
                    1000
                );
            });
            break;
        case "addGroup":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == '')
                return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd('CmdAbeille'+eq['zgId']+'/'+eq['addr']+'/addGroup', 'ep='+eq['mainEp']+'&group='+group);
                setTimeout(function () {
                    sendCmd('CmdAbeille'+eq['zgId']+'/'+eq['addr']+'/getGroupMembership', 'ep='+eq['mainEp']);
                    location.reload(true);
                    },
                    1000
                );
            });
            break;
        case "removeGroup":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == '')
                return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd('CmdAbeille'+eq['zgId']+'/0000/removeGroup', 'address='+eq['addr']+'&DestinationEndPoint='+eq['mainEp']+'&groupAddress='+group);
                setTimeout(function () {
                    sendCmd('CmdAbeille'+eq['zgId']+'/'+eq['addr']+'/getGroupMembership', 'ep='+eq['mainEp']);
                    location.reload(true);
                    },
                    1000
                );
            });
            break;
        case "getGroups2":
            zgId = param1;
            addr = param2;
            ep = param3;
            sendCmd('CmdAbeille'+zgId+'/'+addr+'/getGroupMembership', 'ep='+ep);
            setTimeout(function () {
                location.reload(true);
                },
                1000
            );
            break;
        case "removeGroup2":
            zgId = param1;
            addr = param2;
            ep = param3;
            group = param4;
            sendCmd('CmdAbeille'+zgId+'/'+addr+'/removeGroup', 'address='+addr+'&DestinationEndPoint='+ep+'&groupAddress='+group);
            setTimeout(function () {
                location.reload(true);
                },
                1000
            );
            break;
        case "removeAllGroups":
            zgId = param1;
            addr = param2;
            ep = param3;
            sendCmd('CmdAbeille'+zgId+'/'+addr+'/removeAllGroups', 'ep='+ep);
            sendCmd('CmdAbeille'+zgId+'/'+addr+'/getGroupMembership', 'ep='+ep);
            setTimeout(function () {
                location.reload(true);
                },
                1000
            );
            break;
        case "setGroupRemote":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == '')
                return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd('CmdAbeille'+eq['zgId']+'/0000/commissioningGroupAPS', 'address='+eq['addr']+'&groupId='+group);
                setTimeout(function () {
                    sendCmd('CmdAbeille'+eq['zgId']+'/'+eq['addr']+'/getGroupMembership', 'ep='+eq['mainEp']);
                    location.reload(true);
                    },
                    1000
                );
            });
            break;
        case "setGroupRemoteLegrand":
            if (selected.length == 0)
                return alert("Aucun équipement sélectionné");
            group = document.getElementById("idGroup").value;
            if (group == '')
                return alert("Groupe non renseigné");
            selected.forEach((eq) => {
                console.log("eq=", eq);
                sendCmd('CmdAbeille'+eq['zgId']+'/0000/commissioningGroupAPSLegrand', 'address='+eq['addr']+'&groupId='+group);
                setTimeout(function () {
                    sendCmd('CmdAbeille'+eq['zgId']+'/'+eq['addr']+'/getGroupMembership', 'ep='+eq['mainEp']);
                    location.reload(true);
                    },
                    1000
                );
            });
            break;
        case "startPermitJoin":
            zgId = param1;
            sendCmd('CmdAbeille'+zgId+'/0000/setZgPermitMode', 'mode=start');
            location.reload(true);
            $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate '+zgId+' clignoter pendant 4 minutes.}}', level: 'success'});
            break;
        case "stopPermitJoin":
            zgId = param1;
            sendCmd('CmdAbeille'+zgId+'/0000/setZgPermitMode', 'mode=stop');
            location.reload(true);
            $('#div_alert').showAlert({message: '{{Arret mode inclusion demandé. La zigate '+zgId+' doit arreter de clignoter.}}', level: 'success'});
            break;
        }
    }

    function interrogate(request) {
        console.log("interrogate("+request+")");

        logicalId = "Abeille"+zgId+"_"+eqAddr;
        if (request == "getRoutingTable") {
            topic = "Cmd"+logicalId+"_getRoutingTable";
            payload = "";
        } else if (request == "getBindingTable") {
            topic = "Cmd"+logicalId+"_getBindingTable";
            payload = "address="+eqAddr;
        } else if (request == "getNeighborTable") {
            topic = "Cmd"+logicalId+"_getNeighborTable";
            startIdx = document.getElementById("idStartIdx").value;
            payload = "startIndex="+startIdx;
        } else if (request == "getActiveEndPoints") {
            topic = "Cmd"+logicalId+"_getActiveEndpoints";
            payload = "addr="+eqAddr;
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
            topic = "Cmd"+logicalId+"_mgmtNetworkUpdateReq";
            scanChan = document.getElementById("idMgmtNwkUpdReqSC").value;
            scanDuration = document.getElementById("idMgmtNwkUpdReqSD").value;
            payload = "";
            if (scanChan != '')
                payload += "scanChan="+scanChan;
            if (scanDuration != '') {
                if (payload != '')
                    payload += "_"
                payload += "scanDuration="+scanDuration;
            }
        } else if (request == "leaveRequest") {
            topic = "Cmd"+logicalId+"_LeaveRequest";
            payload = "IEEE="+js_eqIeee;
        }

        else if (request == "readReportingConfig") {
            topic = "Cmd"+logicalId+"_readReportingConfig";
            ep = document.getElementById("idEp").value;
            clustId = document.getElementById("idClustId").value;
            attrId = document.getElementById("idAttrId").value;
            payload = "addr="+eqAddr+"_ep="+ep+"_clustId="+clustId+"_attrId="+attrId;
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
            payload = "addr="+eqAddr+"_ep="+ep+"_clustId="+clustId+"_start="+start;
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
            payload = "addr="+eqAddr+"_ep="+ep+"_clustId="+clustId+"_start="+start;
        } else if (request == "bindToDevice") {
            topic = "Cmd"+logicalId+"_bind0030";
            ep = document.getElementById("idEpE").value;
            clustId = document.getElementById("idClustIdE").value;
            destIeee = document.getElementById("idIeeeE").value;
            destEp = document.getElementById("idEpE2").value;
            payload = "addr="+js_eqIeee+"_ep="+ep+"_clustId="+clustId+"_destAddr="+destIeee+"_destEp="+destEp;
        } else if (request == "unbindToDevice") {
            topic = "Cmd"+logicalId+"_unbind0031";
            ep = document.getElementById("idEpSrc-UBD").value;
            clustId = document.getElementById("idClustId-UBD").value;
            destIeee = document.getElementById("idAddr-UBD").value;
            destEp = document.getElementById("idEpDst-UBD").value;
            payload = "addr="+js_eqIeee+"_ep="+ep+"_clustId="+clustId+"_destAddr="+destIeee+"_destEp="+destEp;
        } else if (request == "bindToGroup") {
            topic = "Cmd"+logicalId+"_bind0030";
            ep = document.getElementById("idEpF").value;
            clustId = document.getElementById("idClustIdF").value;
            destGroup = document.getElementById("idGroupF").value;
            payload = "addr="+js_eqIeee+"_ep="+ep+"_clustId="+clustId+"_destAddr="+destGroup;
        } else if (request == "unbindToGroup") {
            topic = "Cmd"+logicalId+"_unbind0031";
            ep = document.getElementById("idEpSrc-UBG").value;
            clustId = document.getElementById("idClustId-UBG").value;
            destGroup = document.getElementById("idGroup-UBG").value;
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
        } else if (request == "0004-AddGroup") {
            topic = "Cmd"+logicalId+"_addGroup";
            ep = document.getElementById("idEp-AG").value;
            group = document.getElementById("idGroup-AG").value;
            payload = "ep="+ep+"_group="+group;
        } else if (request == "0004-GetGroupMembership") {
            topic = "Cmd"+logicalId+"_getGroupMembership";
            ep = document.getElementById("idEp-GGM").value;
            payload = "ep="+ep;
        } else if (request == "0004-RemoveAllGroups") {
            topic = "Cmd"+logicalId+"_removeAllGroups";
            ep = document.getElementById("idEp-RAG").value;
            payload = "ep="+ep;
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
        } else if (request == "0502-StartWarning") {
            topic = "Cmd"+logicalId+"_cmd-0502";
            ep = document.getElementById("idEp-SW").value;
            mode = document.getElementById("idMode-SW").value;
            strobe = document.getElementById("idStrobe-SW").checked;
            sirenl = document.getElementById("idSirenL-SW").value;
            duration = document.getElementById("idDuration-SW").value;
            if (strobe)
                strobe = 'on';
            else
                strobe = 'off';
            payload = "ep="+ep+"_cmd=00_mode="+mode+"_strobe="+strobe;
            if (duration != '')
                payload += "_duration="+duration;
            if (sirenl != '')
                payload += "_sirenl="+sirenl;
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

        console.log("topic="+topic+", pl="+payload);
        var xhttp = new XMLHttpRequest();
        xhttp.open("GET", "/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg&queueId="+js_queueXToCmd+"&topic="+topic+"&payload="+payload, false);
        xhttp.send();

        xhttp.onreadystatechange = function() {
        };
    }

</script>
