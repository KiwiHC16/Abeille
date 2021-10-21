<?php
    /*
     * Common modal for config page
     */
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }
?>
<div id='configPageModalAlert' style="display: none;"></div>
<a class="btn btn-warning pull-right" data-state="1" id="bt_CPMLogStoStart"><i class="fa fa-pause"></i> {{Pause}}</a>
<input class="form-control pull-right" id="bt_CPMLogSearch" style="width : 300px;" placeholder="{{Rechercher}}"/>
<br/><br/><br/>
<pre id='pre_CPM' style='overflow: auto; height: 90%;with:90%;'>
Lancement des operations.
</pre>

<script>
    var cmd = "<?php echo $_GET['cmd']; ?>";
    var action = "";
    if (cmd == "updateFW") {
        action = 'updateFirmware';
    } else if (cmd == "resetPiZigate") {
        action = 'resetPiZigate';
    }
    <?php
        if (isset($_GET['zgtype']))
            echo 'var zgtype = '.$_GET['zgtype'].';';
        else
            echo 'var zgtype = "";';
        if (isset($_GET['zgport']))
            echo 'var zgport = '.$_GET['zgport'].';';
        else
            echo 'var zgport = "";';
        if (isset($_GET['fwfile']))
            echo 'var fwfile = '.$_GET['fwfile'].';';
        else
            echo 'var fwfile = "";';
    ?>

    $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: action,
                zgtype: zgtype,
                zgport: zgport,
                fwfile: fwfile,
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
