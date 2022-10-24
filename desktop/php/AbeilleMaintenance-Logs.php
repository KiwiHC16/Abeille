<?php
    /*
     * Maintenance - Logs part
     * Included from 'AbeilleMaintenance.php'
     *
     * Allows to
     * - display any log file, even internal (not visible thru Jeedom)
     * - download displayed log or a zip of all logs
     */

    $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory
    $pluginDir = __DIR__."/../../"; // Plugin root dir
    echo '<script>';
    echo 'let js_tmpDir = "'.$tmpDir.'";';
    echo 'let js_pluginDir = "'.$pluginDir.'";';
    echo 'let curDisplay = "";'; // Current displayed file/command
    echo 'let curDisplayType = "";'; // "JEEDOM-TMP", "JEEDOM-LOG", or "COMMAND"
    $maxLineLog = config::byKey('maxLineLog', 'core');
    echo 'let maxLineLog = '.$maxLineLog.';';
    require_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    $curLogLevel = AbeilleTools::getLogLevel();
    echo 'let curLogLevel = '.$curLogLevel.';';
    echo '</script>';
?>

<style>
    .bs-sidenav .list-group-item {
        padding : 2px 2px 2px 2px;
    }
    .topBar {
        height: 40px;
        margin-top: 10px;
        margin-bottom: 10px;
        margin-right: 5px;
    }
</style>
<div class="row" style="height:inherit">
    <div class="col-lg-2 col-md-3 col-sm-4" style="height:inherit;overflow-y:auto;overflow-x:hidden;">
        <div class="topBar">
        </div>

        <div class="bs-sidebar">

        <!-- Key infos -->
        <ul class="nav nav-list bs-sidenav list-group">
            Infos clefs
            <li class="cursor list-group-item list-group-item-success"><a class="btnKeyInfos">Infos clefs</a></li>
        </ul>

        <!-- Log files -->
        <ul class="nav nav-list bs-sidenav list-group">
            Logs
            <?php
                /* Listing log files from Jeedom env */
                foreach (glob(__DIR__."/../../../../log/Abeille*") as $path) {
                    $fileName = basename($path);
                    echo '<li class="cursor list-group-item list-group-item-success"><a class="btnDisplayLog" location="JEEDOM-LOG">'.$fileName.'</a></li>';
                }
                echo '<li class="cursor list-group-item list-group-item-success"><a class="btnDisplayLog" location="JEEDOM-LOG">http.error</a></li>';
                echo '<li class="cursor list-group-item list-group-item-success"><a class="btnDisplayLog" location="JEEDOM-LOG">update</a></li>';
                /* Listing log files from Jeedom temp directory */
                foreach (glob($tmpDir."/*.log") as $path) {
                    $fileName = basename($path);
                    if ($fileName == "AbeilleKeyInfos.log")
                        continue; // Avoid old log
                    echo '<li class="cursor list-group-item list-group-item-success"><a class="btnDisplayLog" location="JEEDOM-TMP">'.$fileName.'</a></li>';
                }
            ?>
            <a id="idDownloadAllLogs" class="btn btn-success" style="margin-top:8px"><i class="fas fa-cloud-download-alt"></i> {{Télécharger tout}}</a>
        </ul>

        <!-- Data (JSON) -->
        <ul class="nav nav-list bs-sidenav list-group">
            JSON
            <?php
                /* Listing json files from Jeedom tmp */
                foreach (glob($tmpDir."/Abeille*.json") as $path) {
                    $fileName = basename($path);
                    echo '<li class="cursor list-group-item list-group-item-success"><a class="btnDisplayLog" location="JEEDOM-TMP">'.$fileName.'</a></li>';
                }
            ?>
        </ul>

        <!-- Commands -->
        <ul class="nav nav-list bs-sidenav list-group">
            Commandes
            <li class="cursor list-group-item list-group-item-success"><a class="btnCommand" data-command="pgrep -a php | grep Abeille">Processus</a></li>
            <li class="cursor list-group-item list-group-item-success"><a class="btnCommand" data-command="ipcs -q">Queues</a></li>
        </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8" style="height:inherit;border-left:solid 1px #EEE;padding-left:25px;overflow-y:hidden;overflow-x:hidden;">
        <div class="topBar">
            <a id="idCurrentDisplay" style="width:90%">{{Selectionnez le log ou la commande à afficher.}}</a>

            <div class="input-group pull-right" style="display:inline;">
                <input type="hidden" id="brutlogcheck" autoswitch="0" checked />
                <i id="brutlogicon" class="fas fa-exclamation-circle icon_orange" hidden></i>

                <!-- <input class="input-sm roundedLeft" id="in_eventLogSearch" style="width : 200px;margin-left:4px;" placeholder="{{Rechercher}}" /> -->
                <!-- <a class="btn btn-warning btn-sm" data-state="1" id="bt_eventLogStopStart"><i class="fas fa-pause"></i> {{Pause}}</a> -->
                <a class="btn btn-success btn-sm" id="idDownloadCurrent"><i class="fas fa-cloud-download-alt"></i> {{Télécharger}}</a>
                <a class="btn btn-warning btn-sm" id="idClearLogFile"><i class="fas fa-times"></i> {{Vider}}</a>
                <a class="btn btn-danger roundedRight btn-sm" id="idRemoveLogFile"><i class="far fa-trash-alt"></i> {{Supprimer}}</a>
            </div>
        </div>
        <pre id="idPreResults" style="height:100%;width:100%;margin-top:5px;font-family:monospace"></pre>
    </div>
</div>

<script>
    function displayLog(logType, logFile) {
        console.log("displayLog("+logType+", "+logFile+")");
        let action = "";
        if (logType == "JEEDOM-LOG") {
            action = "getFile";
            logPath = "../../log/"+logFile;
        } else {
            action = "getTmpFile";
            logPath = logFile;
        }

        $.ajax({
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: action,
                file : logPath,
            },
            dataType: "json",
            global: false,
            cache: false,
            error: function (request, status, error) {
                $('#idCurrentDisplay').empty().append('{{Log : }}'+logFile+" => ERREUR");
            },
            success: function (json_res) {
                // console.log(json_res);
                res = JSON.parse(json_res.result); // res.status, res.error, res.content
                if (res.status != 0) {
                    $('#idCurrentDisplay').empty().append('{{Log : }}'+logFile+" => ERREUR");
                    $('#idPreResults').empty();
                    curDisplay = "";
                } else {
                    var log = res.content;
                    $('#idPreResults').empty();
                    $('#idCurrentDisplay').empty().append('{{Log : }}'+logFile);
                    $('#idPreResults').append(log);
                    curDisplay = logFile;
                    curDisplayType = logType;
                }
            }
        });
    }

    /* Collect & display key infos */
    $('.btnKeyInfos').off('click').on('click',function() {
        console.log("btnKeyInfos click");

        var genKeyInfos = new XMLHttpRequest();
        genKeyInfos.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                displayLog("JEEDOM-TMP", "AbeilleKeyInfos.log")
            }
        };

        genKeyInfos.open("GET", "/plugins/Abeille/core/php/AbeilleSupportKeyInfos.php", false);
        genKeyInfos.send();
    });

    var $rawLogCheck = $('#brutlogcheck')
    $('.btnDisplayLog').off('click').on('click',function() {
        var location = $(this).attr('location'); // "JEEDOM-LOG" or "JEEDOM-TMP"
        var logFile = $(this).text();
        console.log("btnDisplayLog click: File="+logFile+", location="+location);
        $('.btnDisplayLog').parent().removeClass("active")
        $(this).parent().addClass("active")
        displayLog(location, logFile)
        // jeedom.log.autoupdate({
        //     log: logFile,
        //     // default_search: log_default_search,
        //     display: $('#idPreResults'),
        //     search: $('#in_eventLogSearch'),
        //     control: $('#bt_eventLogStopStart')
        // })
    })

    function createLogsZipFile() {
        $.showLoading();
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'createLogsZipFile'
            },
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                $.hideLoading();
                bootbox.alert("ERREUR 'createLogsZipFile' !");
            },
            success: function (json_res) {
                $.hideLoading();
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    var msg = "ERREUR ! Quelque chose s'est mal passé.\n"+res.error;
                    alert(msg);
                } else {
                    window.open('plugins/Abeille/core/php/AbeilleDownload.php?pathfile='+js_tmpDir+'/'+res.zipFile, "_blank", null);
                }
            }
        });
    }

    /* Pack and download all logs at once */
    $('#idDownloadAllLogs').click(function() {
        console.log("idDownloadAllLogs click");

        msg = "";
        if (curLogLevel != 4) {
            msg += "ATTENTION !\n\n"
                + "Vous n'êtes pas en mode debug pour les logs.<br>"
                + "Voir: https://kiwihc16.github.io/AbeilleDoc/Debug.html#support<br><br>"
                + "Etes vous sur de vouloir continuer ?";
        } else if (maxLineLog < 5000) {
            msg += "ATTENTION !\n\n"
                + "Il est recommandé de configurer au moins 5000 lignes de logs pour les besoins de debug.<br>"
                + "La valeur actuelle est: "+maxLineLog+"<br>"
                + "Voir: https://kiwihc16.github.io/AbeilleDoc/Debug.html#support<br><br>"
                + "Etes vous sur de vouloir continuer ?";
        }
        if (msg != "") {
            bootbox.confirm(msg, function (result) {
                if (result)
                    createLogsZipFile();
                else
                    return;
            });
        } else
            createLogsZipFile();
    });

    /* Execute command & display result */
    $('.btnCommand').off('click').on('click', function() {
        var command = $(this).attr('data-command');
        var name = $(this).text();
        $('#idPreResults').empty();
        jeedom.ssh({
            command : command,
            success : function(log) {
                $('#idCurrentDisplay').empty().append('{{Commande : }}'+command);
                $('#idPreResults').append(log);
                curDisplay = name;
                curDisplayType = "COMMAND";
            }
        })
    })

    /* Save given 'text' to 'fileName' */
    function saveToFile(fileName, text) {
        let elem = window.document.createElement('a');
        elem.style = "display: none";
        elem.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        elem.setAttribute('download', fileName);
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }

    /* Download file corresponding to current display */
    $('#idDownloadCurrent').click(function() {
        if (curDisplay == "")
            return; // Nothing displayed

        console.log("idDownloadCurrent click: curDisplay="+curDisplay);
        if (curDisplayType == "COMMAND") {
            content = $('#idPreResults').text();
            var f = curDisplay.split(".");
            if ((f.length === 1) || (f[1] != ".log")) {
                fileName = curDisplay+".log";
            } else
                fileName = curDisplay;
            console.log("curDisplay="+curDisplay+" => fileName="+fileName);
            saveToFile(fileName, content);
        } else {
            let path;
            if (curDisplayType == "JEEDOM-TMP")
                path = js_tmpDir+"/"+curDisplay;
            else // "JEEDOM-LOG"
                path = js_pluginDir+"/../../log/"+curDisplay;
            window.open('plugins/Abeille/core/php/AbeilleDownload.php?pathfile='+path+'&addext=log', "_blank", null);
        }
    })

    // Clear selected log file
    $("#idClearLogFile").on('click', function(event) {
        console.log("idClearLogFile click: curDisplay="+curDisplay+", type="+curDisplayType);

        if (curDisplay == "")
            return;

        logFile = curDisplay;
        logLocation = curDisplayType; // JEEDOM-LOG or JEEDOM-TMP

        console.log("lf="+logFile+", loc="+logLocation);
        $.ajax({
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: "clearFile",
                file : logFile,
                location : logLocation,
            },
            dataType: "json",
            global: false,
            cache: false,
            error: function (request, status, error) {
            },
            success: function (json_res) {
            }
        });
    })

    // Remove selected log file
    $("#idRemoveLogFile").on('click', function(event) {
        console.log("idRemoveLogFile click: curDisplay="+curDisplay+", type="+curDisplayType);

        if (curDisplay == "")
            return;

        logFile = curDisplay;
        let action = "";
        if (curDisplayType == "JEEDOM-LOG") {
            action = "delFile";
            logPath = "../../log/"+logFile;
        } else { // curDisplayType == "JEEDOM-TMP"
            action = "delTmpFile";
            logPath = logFile;
        }

        $.ajax({
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: action,
                file : logPath,
            },
            dataType: "json",
            global: false,
            cache: false,
            error: function (request, status, error) {
            },
            success: function (json_res) {
            }
        });
    })

</script>
