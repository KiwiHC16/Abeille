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
    <?php
        if (isset($_GET['zgport']))
            echo 'var zgport = '.$_GET['zgport'].';';
        else
            echo 'var zgport = "";';
    ?>
    var fwfile = "";
    if (cmd == "updateFW") {
        action = 'updateFirmwarePiZiGate';
        fwfile = <?php if (isset($_GET['fwfile'])) echo $_GET['fwfile']; else echo "none"; ?>;
    } else if (cmd == "resetPiZigate") {
        action = 'resetPiZigate';
    }

    $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/abeille.ajax.php',
            data: {
                action: action,
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
