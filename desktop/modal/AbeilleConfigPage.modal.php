<?php
    /*
     * Common modal for config page
     */
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    if (!isset($_GET['cmd'])) {
        echo "ERROR: Missing 'cmd'";
        return;
    }
    $cmd = $_GET['cmd'];
    if ($cmd == "updateFw") {
        $action = 'updateFirmware';
    } else if ($cmd == "resetPiZigate") {
        $action = 'resetPiZigate';
    } else {
        echo "ERROR: Invalid cmd '${cmd}'";
        return;
    }
    sendVarToJS('action', $action);

    if (isset($_GET['zgType']))
        $zgType = $_GET['zgType'];
    else
        $zgType = "";
    // sendVarToJS('zgType', $zgType);

    if (isset($_GET['zgPort']))
        $zgPort = $_GET['zgPort'];
    else
        $zgPort = "";
    // sendVarToJS('zgPort', $zgPort);

    if (isset($_GET['zgGpioLib']))
        $zgGpioLib = $_GET['zgGpioLib'];
    else
        $zgGpioLib = "";
    // sendVarToJS('zgGpioLib', $zgGpioLib);

    if (isset($_GET['fwFile']))
        $fwFile = $_GET['fwFile'];
    else
        $fwFile = "";
    // sendVarToJS('fwFile', $fwFile);

    if (isset($_GET['erasePdm']))
        $erasePdm = $_GET['erasePdm'];
    else
        $erasePdm = "";
    // sendVarToJS('erasePdm', $erasePdm);

    if (isset($_GET['zgId']))
        $erasePdm = $_GET['zgId'];
    else
        $erasePdm = "";
    // sendVarToJS('zgId', $zgId);

    // PHP to JS
    echo '<script>';
    echo "var zgType = '${zgType}';";
    echo "var zgPort = '${zgPort}';";
    echo "var zgGpioLib = '${zgGpioLib}';";
    echo "var fwFile = '${fwFile}';";
    echo "var erasePdm = '${erasePdm}';";
    echo "var zgId = '${zgId}';";
    echo '</script>';
?>

<div id='configPageModalAlert' style="display: none;"></div>
<a class="btn btn-warning pull-right" data-state="1" id="bt_CPMLogStoStart"><i class="fa fa-pause"></i> {{Pause}}</a>
<input class="form-control pull-right" id="bt_CPMLogSearch" style="width : 300px;" placeholder="{{Rechercher}}"/>
<br/><br/><br/>
<pre id='pre_CPM' style='overflow: auto; height: 90%;with:90%;'>
</pre>

<script>
    $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: action,
                zgType: zgType,
                zgPort: zgPort,
                zgGpioLib: zgGpioLib,
                fwFile: fwFile,
                erasePdm: erasePdm,
                zgId: zgId,
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#configPageModalAlert'));
            },
            success: function () {
            }
        });

    function updatelog(){
        jeedom.log.autoupdate({
            log: 'AbeilleConfig.log',
            display: $('#pre_CPM'),
            search: $('#bt_CPMLogSearch'),
            control: $('#bt_CPMLogStoStart'),
        });
    }
    setTimeout(updatelog, 1000);
</script>
