<?php
    /*
     * Support page
     * Output all required datas for support on window and in "tmp/supportPage.log".
     */

    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once(dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php');
    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    */
    $eqLogics = Abeille::byType('Abeille');
    $tmp = __DIR__.'/../../tmp';
    if (file_exists($tmp) == FALSE)
        mkdir($tmp);
    $logFile = $tmp.'/supportPage.log';
    echo '<script>';
    echo 'var js_logFile = "'.$logFile.'";';
    echo '</script>';
?>

<a class="btn btn-success pull-right" id="bt_DownloadSupportPage"><i class="fas fa-cloud-download-alt"></i> Télécharger</a>
<br/><br/><br/>
<pre style='overflow: auto; height: 90%;with:90%;'>

<?php

    global $CONFIG;

    function echoAndLog($logFile, $msg, $append=1)
    {
        echo $msg;
        if ($append == 1)
            file_put_contents($logFile, $msg, FILE_APPEND);
        else
            file_put_contents($logFile, $msg);
    }

    /* Print title with underlines */
    function echoTitle($logFile, $title)
    {
        $line = "";
        if (substr($title, 0, 2) == '{{') {
            $title = substr($title, 2);
            $title = substr($title, 0, -2);
        }
        $len = strlen($title);
        for ($i = 0; $i < $len; $i++)
            $line .= '=';

        // echo $line."\n";
        echoAndLog($logFile, $title."\n");
        echoAndLog($logFile, $line."\n");
    }

    function getFileAndPrint($logFile,  $file, $title, $printModal, $printFile) {
        
        $contents = file_get_contents($file);
        
        if ( $printModal ) {
            echoTitle($logFile, $title);
            echoAndLog($logFile, $contents."\n");
        }
        if ( $printFile ) {
            log::add('AbeilleDbConf', 'info', '');
            log::add('AbeilleDbConf', 'info', '-----------------------');
            log::add('AbeilleDbConf', 'info', $title);
            log::add('AbeilleDbConf', 'info', '-----------------------');
            log::add('AbeilleDbConf', 'info', $contents);
            log::add('AbeilleDbConf', 'info', '');
        }
    }
    
    function requestAndPrint($logFile, $link, $sql, $title, $printModal, $printFile) {
        if ( $printModal ) {
            echoTitle($logFile, $title);
            echoAndLog($logFile, "{");
        }
        if ( $printFile ) {
            log::add( 'AbeilleDbConf', 'info', '');
            log::add( 'AbeilleDbConf', 'info', '-----------------------');
            log::add( 'AbeilleDbConf', 'info', $title);
            log::add( 'AbeilleDbConf', 'info', '-----------------------');
            $i=0;
            log::add( 'AbeilleDbConf', 'info', '{');
        }

        $i=0;
        if ($result = mysqli_query($link, $sql)) {
            while ($row = $result->fetch_assoc()) {
                if ( $printModal ) {
                    if ($i==0) { echoAndLog($logFile, '"'.$i.'":'.json_encode($row)); }
                    else { echoAndLog($logFile, ',"'.$i.'":'.json_encode($row)); }
                }
                if ($printFile) {
                    if ($i==0) {log::add( 'AbeilleDbConf', 'info', '"'.$i.'":'.json_encode($row)); }
                    else { log::add( 'AbeilleDbConf', 'info', ',"'.$i.'":'.json_encode($row)); }
                }
                $i++;
            }
            mysqli_free_result($result);
        }
        if ( $printModal ) { echoAndLog($logFile, "}\n\n"); }
        if ( $printFile ) { log::add( 'AbeilleDbConf', 'info', '}'); }
    }

    //------------------------------------------------------------------------------------------
    // Main
    //------------------------------------------------------------------------------------------
    echoAndLog($logFile, "Extraction des informations nécessaires au support.\n\n", 0);

    getFileAndPrint($logFile, '/var/www/html/plugins/Abeille/plugin_info/AbeilleVersion.inc', "{{Version (AbeilleVersion.inc)}}", 1, 1);

    /* Connect to DB */
    $link = mysqli_connect($CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']);

    /* check connection */
    if (mysqli_connect_errno()) {
        echo("Connect failed: ".json_encode(mysqli_connect_error()));
        exit();
    }

    requestAndPrint($logFile, $link, "SELECT * FROM `update` WHERE `name` = 'Abeille'", "{{Version (Jeedom DB)}}", 1, 1);
    requestAndPrint($logFile, $link, "SELECT * FROM `cron` WHERE `class` = 'Abeille'", "{{Liste des cron}}",          1, 1);
    requestAndPrint($logFile, $link, "select * from config where plugin = 'Abeille'", "{{Configuration du plugin}}",  1, 1);
    requestAndPrint($logFile, $link, "select * from eqLogic where eqType_name = 'Abeille'", "{{Liste des abeilles}}", 1, 1);
    requestAndPrint($logFile, $link, "SELECT * FROM cmd WHERE eqType = 'Abeille'", "{{Liste des commandes}}",         0, 1);

    mysqli_close($link);
?>
</pre>

<script>
    $('#bt_DownloadSupportPage').click(function() {
        window.open('core/php/downloadFile.php?pathfile=' + js_logFile, "_blank", null);
    });
</script>
