<?php
    /*
     * Generate key infos for support page
     * - Output is a log file (AbeilleKeyInfos.log) in Jeedom TMP dir
     */

    include_once __DIR__.'/../config/Abeille.config.php';

    /* Developers options */
    if (file_exists(dbgFile)) {
        // $dbgConfig = json_decode(file_get_contents(dbgFile), true);

        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';

    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    */
    // $eqLogics = Abeille::byType('Abeille');
    $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory
    $logFile = $tmpDir.'/AbeilleKeyInfos.log';

    global $CONFIG;

    /**
     * Add/Replace LogFile with the string msg
     *
     * @param   logFile   file where to store the string
     * @param   msg       string to put in the log file
     * @param   append    Should we append or replace
     *
     * @return  none
     */
    function logIt($msg, $append = 1)
    {
        global $logFile;

        // echo $msg;
        if ($append == 1)
            file_put_contents($logFile, $msg, FILE_APPEND);
        else
            file_put_contents($logFile, $msg);
    }

    /**
     * Print title with underlines
     *
     * @param   logFile     file where to store the title
     * @param   title       Title to print
     *
     * @return  none
     */
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
        logIt($title."\n");
        logIt($line."\n");
    }

    function requestAndPrint($logFile, $link, $sql, $title) {
        echoTitle($logFile, $title);
        $i = 0;
        logIt("{\n");
        if ($result = mysqli_query($link, $sql)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['id'])) {
                    $id = $row['id'];
                    unset($row['id']);
                    logIt('    "'.$id.'": '.json_encode($row).",\n");
                } else if (isset($row['key'])) {
                    $key = $row['key'];
                    unset($row['key']);
                    logIt('    "'.$key.'": '.json_encode($row).",\n");
                } else {
                    logIt('    "'.$i.'": '.json_encode($row).",\n");
                    $i++;
                }
            }
            mysqli_free_result($result);
        }
        logIt("}\n\n");
    }

    function zigateInfos() {
        for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
            if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') != 'Y')
                continue; // Zigate disabled

            $eqLogic = Abeille::byLogicalId('Abeille'.$zgId.'/0000', 'Abeille');
            if (is_object($eqLogic)) {
                $beehiveId = $eqLogic->getId();

                $major = '????';
                $minor = '????';
                $cmdLogic = cmd::byEqLogicIdAndLogicalId($beehiveId, 'SW-Application');
                if ($cmdLogic)
                    $major = $cmdLogic->execCmd();
                $cmdLogic = cmd::byEqLogicIdAndLogicalId($beehiveId, 'SW-SDK');
                if ($cmdLogic)
                    $minor = $cmdLogic->execCmd();
                logIt("Zigate ".$zgId.": ".$major.'-'.$minor."\n");
            } else
                logIt("Zigate ".$zgId.": ERROR. No Jeedom registered device\n");
        }
        logIt("\n");
    }

    function jeedomInfos($logFile,$CONFIG) {
        /* Connect to DB */
        $link = mysqli_connect($CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']);

        /* check connection */
        if (mysqli_connect_errno()) {
            logIt("MySQL connection FAILED: ".json_encode(mysqli_connect_error())."\n");
            return;
        }

        requestAndPrint($logFile, $link, "SELECT * FROM `update`    WHERE `name` = 'Abeille'", "Table du plugin");
        requestAndPrint($logFile, $link, "SELECT * FROM `cron`      WHERE `class` = 'Abeille'", "Table 'cron'");
        requestAndPrint($logFile, $link, "SELECT * FROM `config`    WHERE `plugin` = 'Abeille'", "Table 'config'");
        requestAndPrint($logFile, $link, "SELECT * FROM `eqLogic`   WHERE `eqType_name` = 'Abeille'", "Table 'eqLogic'");
        requestAndPrint($logFile, $link, "SELECT * FROM `cmd`       WHERE `eqType` = 'Abeille'", "Table 'cmd'");

        mysqli_close($link);
    }

    //------------------------------------------------------------------------------------------
    // Main
    //------------------------------------------------------------------------------------------
    logIt("Informations clefs nécessaires au support.\n\n", 0);
    logIt('Quand <a href="https://github.com/KiwiHC16/Abeille/issues/new" target="_blank">vous ouvrez une "issue"</a> dans GitHub merci de copier/coller les 3 premiers chapitres ci dessous '."\n");
    logIt("Pour l'intégration d'un équipement non encore supporté ajoutez le chapitre 4.\n\n");

    /* Reading version */
    $file = fopen(__DIR__."/../../plugin_info/Abeille.version", "r");
    $line = fgets($file); // Should be a comment
    $abeilleVersion = trim(fgets($file)); // Should be Abeille's version
    fclose($file);
    logIt("Version Abeille: ".$abeilleVersion."\n\n");

    // Linux
    // exec('cat /etc/issue', $result1);
    // logIt("Kernel: ".json_encode($result1)."\n");
    exec('uname -a', $result2);
    logIt("Kernel: ".json_encode($result2)."\n\n");

    zigateInfos();

    jeedomInfos($logFile, $CONFIG);
?>
