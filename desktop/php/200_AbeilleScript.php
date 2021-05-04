<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('desktop', 'AbeilleDev', 'js', 'Abeille'); ?>
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

    /* Show or hide developer area.
       If developer mode is enabled, default is to always expand this area. */
    $('#idDevGrpShowHide').on('click', function () {
        console.log("idDevGrpShowHide() click");
        var Label = document.getElementById("idDevGrpShowHide").innerText;
        if (Label == "Montrer") {
            document.getElementById("idDevGrpShowHide").innerText = "Cacher";
            document.getElementById("idDevGrpShowHide").className = "btn btn-danger";
            $("#idDevGrp").show();
        } else {
            document.getElementById("idDevGrpShowHide").innerText = "Montrer";
            document.getElementById("idDevGrpShowHide").className = "btn btn-success";
            $("#idDevGrp").hide();
        }
    });
    if ((typeof js_dbgDeveloperMode != 'undefined') && (js_dbgDeveloperMode == 1)) {
        var Label = document.getElementById("idDevGrpShowHide").innerText;
        document.querySelector('#idDevGrpShowHide').click();
    }

    $("#sel_icon").change(function () {
        var text = 'plugins/Abeille/images/node_' + $("#sel_icon").val() + '.png';
        //$("#icon_visu").attr('src',text);
        document.icon_visu.src = text;
    });

<?php
for ($i = 1; $i <= 10; $i++) {
  ?>
  $('#bt_include<?php echo $i;?>').on('click', function ()  {
                                                console.log("bt_include<?php echo $i;?>");
                                                var xmlhttpMQTTSendInclude = new XMLHttpRequest();
                                                xmlhttpMQTTSendInclude.onreadystatechange = function()  {
                                                                                                          if (this.readyState == 4 && this.status == 200) {
                                                                                                            xmlhttpMQTTSendIncludeResult = this.responseText;
                                                                                                          }
                                                                                                        };
                                                xmlhttpMQTTSendInclude.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille<?php echo $i;?>_0000_SetPermit&payload=Inclusion", true);
                                                xmlhttpMQTTSendInclude.send();
                                                $('#div_alert').showAlert({message: '{{Mode inclusion demandé. La zigate <?php echo $i;?> doit se mettre à clignoter pour 4 minutes.}}', level: 'success'});
                                              }

                                      );
  <?php
}
?>

<?php
for ($i = 1; $i <= 10; $i++) {
 ?>
 $('#bt_include_stop<?php echo $i;?>').on('click', function ()  {
                                               console.log("bt_include_stop<?php echo $i;?>");
                                               var xmlhttpMQTTSendIncludeStop = new XMLHttpRequest();
                                               xmlhttpMQTTSendIncludeStop.onreadystatechange = function()  {
                                                                                                         if (this.readyState == 4 && this.status == 200) {
                                                                                                           xmlhttpMQTTSendIncludeResultStop = this.responseText;
                                                                                                         }
                                                                                                       };
                                               xmlhttpMQTTSendIncludeStop.open("GET", "/plugins/Abeille/Network/TestSVG/xmlhttpMQTTSend.php?topic=CmdAbeille<?php echo $i;?>_0000_SetPermit&payload=InclusionStop", true);
                                               xmlhttpMQTTSendIncludeStop.send();
                                               $('#div_alert').showAlert({message: '{{Arret mode inclusion demandé. La zigate <?php echo $i;?> doit arreter de clignoter.}}', level: 'success'});
                                             }

                                     );
 <?php
}
?>
</script>
