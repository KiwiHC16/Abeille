<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>
    /* Remove default Jeedom 'onclick' event for 'eqLogicDisplayCard' class
       and replace it by a new one. */
    $(".eqLogicDisplayCard").off("click");
    $(".eqLogicDisplayCard").on('click', function () {
        console.log("eqLogicDisplayCard click");
        if (!isset($(this).attr('data-eqLogic_id'))) {
          console.log("ERROR: 'data-eqLogic_id' is not defined");
          return;
        }
        var eqId = $(this).attr('data-eqLogic_id');
        console.log("eqId="+eqId);
        window.location.href = "index.php?v=d&m=Abeille&p=AbeilleEq&id="+eqId;
    });

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

    <?php
    for ($zgId = 1; $zgId <= 10; $zgId++) {
    ?>
    $('#bt_include<?php echo $zgId;?>').on('click', function ()  {
        console.log("bt_include<?php echo $zgId;?>");
        sendToCmd("startPermitJoin", <?php echo $zgId;?>);
    });
    <?php } ?>

    <?php
    for ($zgId = 1; $zgId <= 10; $zgId++) {
    ?>
    $('#bt_include_stop<?php echo $zgId;?>').on('click', function () {
        console.log("bt_include_stop<?php echo $zgId;?>");
        sendToCmd("stopPermitJoin", <?php echo $zgId;?>);
    });
    <?php } ?>

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

    // Send msg to AbeilleCmd
    function sendToCmd(action, params = '') {
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
            zgId = params;
            sendCmd('CmdAbeille'+zgId+'/0000/setZgPermitMode', 'mode=start');
            location.reload(true);
            $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate '+zgId+' clignoter pendant 4 minutes.}}', level: 'success'});
            break;
        case "stopPermitJoin":
            zgId = params;
            sendCmd('CmdAbeille'+zgId+'/0000/setZgPermitMode', 'mode=stop');
            location.reload(true);
            $('#div_alert').showAlert({message: '{{Arret mode inclusion demandé. La zigate '+zgId+' doit arreter de clignoter.}}', level: 'success'});
            break;
        }
    }
</script>
