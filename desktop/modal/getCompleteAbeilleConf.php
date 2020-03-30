<?php
    
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once(dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php');
    
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    
    $eqLogics = Abeille::byType('Abeille');
    
    global $CONFIG;
    
    function requestAndPrint( $link, $sql, $title) {
        echo "<br>\n<br>\n";
        echo "-----------------------<br>\n";
        echo $title."<br>\n";
        echo "-----------------------<br>\n";
        $i=0;
        echo "{";
        if ($result = mysqli_query($link, $sql)) {
            while ($row = $result->fetch_assoc()) {
                if ($i==0) { echo '"'.$i.'":'.json_encode($row); }
                else { echo ',"'.$i.'":'.json_encode($row); }
                $i++;
            }
            mysqli_free_result($result);
        }
        echo "}";
    }
    
    echo "Dans cette page nous allons extraire toutes les informations necessaires a l analyse du plugin.<br> <br>\n";
    
    $link = mysqli_connect( $CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']);
    
    /* check connection */
    if (mysqli_connect_errno()) {
        echo("Connect failed: ".json_encode(mysqli_connect_error()));
        exit();
    }
    
    requestAndPrint($link, "SELECT * FROM `update` WHERE `name` = 'Abeille'", "Version");
    requestAndPrint($link, "select * from config where plugin = 'Abeille'", "Configuration du plugin");
    requestAndPrint($link, "SELECT * FROM `cron` WHERE `class` = 'Abeille'", "Liste des cron");
    requestAndPrint($link, "select * from eqLogic where eqType_name = 'Abeille'", "Liste des abeilles");
    requestAndPrint($link, "SELECT * FROM cmd WHERE eqType = 'Abeille'", "Liste des commandes");
    
    mysqli_close($link);
    
    ?>
