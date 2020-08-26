<?php

    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once(dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php');
    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    */
    $eqLogics = Abeille::byType('Abeille');

    global $CONFIG;

    function getFileAndPrint( $file, $title, $printModal, $printFile ) {
        
        $contents = file_get_contents($file);
        
        if ( $printModal ) {
            echo "<br>\n<br>\n";
            echo "-----------------------<br>\n";
            echo $title."<br>\n";
            echo "-----------------------<br>\n";

            echo $contents."<br><br>";
        }
        if ( $printFile ) {
               log::add( 'AbeilleDbConf', 'info', '');
               log::add( 'AbeilleDbConf', 'info', '-----------------------');
               log::add( 'AbeilleDbConf', 'info', $title);
               log::add( 'AbeilleDbConf', 'info', '-----------------------');
                log::add( 'AbeilleDbConf', 'info', $contents);
                log::add( 'AbeilleDbConf', 'info', '');
           }
        
    }
    
    function requestAndPrint( $link, $sql, $title, $printModal, $printFile) {
        if ( $printModal ) {
            echo "<br>\n<br>\n";
            echo "-----------------------<br>\n";
            echo $title."<br>\n";
            echo "-----------------------<br>\n";

            echo "{";
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
                    if ($i==0) { echo '"'.$i.'":'.json_encode($row); }
                    else { echo ',"'.$i.'":'.json_encode($row); }
                }
                if ($printFile) {
                    if ($i==0) {log::add( 'AbeilleDbConf', 'info', '"'.$i.'":'.json_encode($row)); }
                    else { log::add( 'AbeilleDbConf', 'info', ',"'.$i.'":'.json_encode($row)); }
                }
                $i++;
            }
            mysqli_free_result($result);
        }
        if ( $printModal ) { echo "}"; }
        if ( $printFile ) { log::add( 'AbeilleDbConf', 'info', '}'); }
    }

    //------------------------------------------------------------------------------------------
    // Main
    //------------------------------------------------------------------------------------------
    echo "{{Extraction de toutes les informations necessaires à l'analyse du plugin.}}<br>\n";

    getFileAndPrint('/var/www/html/plugins/Abeille/plugin_info/AbeilleVersion.inc', "{{Version from AbeilleVersion.inc}}", 1, 1);
    
    $link = mysqli_connect( $CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']);

    /* check connection */
    if (mysqli_connect_errno()) {
        echo("Connect failed: ".json_encode(mysqli_connect_error()));
        exit();
    }

    requestAndPrint($link, "SELECT * FROM `update` WHERE `name` = 'Abeille'", "{{Version from Jeedom DB}}", 1, 1);
    requestAndPrint($link, "select * from config where plugin = 'Abeille'", "{{Configuration du plugin}}",  1, 1);
    requestAndPrint($link, "SELECT * FROM `cron` WHERE `class` = 'Abeille'", "{{Liste des cron}}",          1, 1);
    requestAndPrint($link, "select * from eqLogic where eqType_name = 'Abeille'", "{{Liste des abeilles}}", 1, 1);
    requestAndPrint($link, "SELECT * FROM cmd WHERE eqType = 'Abeille'", "{{Liste des commandes}}",         0, 1);

    mysqli_close($link);

    ?>
