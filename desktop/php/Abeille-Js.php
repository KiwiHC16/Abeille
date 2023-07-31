<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>
    $("#idEqIcon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#idEqIcon").val() + '.png';
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
        // var list = document.querySelectorAll('input[type=checkbox]');
        var list = document.querySelectorAll('beeChecked'); // class="beeChecked" on each Jeedom equipement
        for (i = 0; i < list.length; i++) {
            item = list[i];
            // console.log("item.id=", item.id);
            if (!item.checked)
                continue;

            // console.log(item.id+" CHECKED");
            console.log("CHECKED item=", item);
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
            msg = '{{Vous êtes sur le point de changer le canal Zigbee de la Zigate}}<b>'+zgId+'</b>';
            msg += '<br><br>{{Les équipements sur secteur devraient suivre mais pas forcément tous ceux sur batterie.}}'
            msg += '<br>{{Dans ce cas vous pourriez avoir à refaire une inclusion de ces équipements uniquement.}}'
            msg += '<br><br>{{Etes vous sur de vouloir continuer ?}}'
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

                    config = new Object();
                    config['ab::zgChan'+zgId] = chan;
                    console.log("config=", config);
                    saveConfig(config);

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
    }

	$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

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
