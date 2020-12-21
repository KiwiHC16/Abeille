<?php
    /*
     * Generate key infos for support page
     * - Output is a log file (AbeilleKeyInfos.log) in Jeedom TMP dir
     */

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    // include_once(__DIR__.'/../../resources/AbeilleDeamon/lib/AbeilleTools.php'); // What for ?
    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    */
    $eqLogics = Abeille::byType('Abeille');
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
    function echoAndLog($logFile, $msg, $append=1)
    {
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
        echoAndLog($logFile, $title."\n");
        echoAndLog($logFile, $line."\n");
    }

    /**
     * Print a file into the sreen(modal)/logFile with a title before
     *
     * @param   logFile     file where to store file
     * @param   file        file used as input to have data
     * @param   title       title to print before the file
     * @param   printModal  Shall we print to the screen (modal)
     * @param   logFile   Shall we print into the log
     *
     * @return  none
     */
    function getFileAndPrint($logFile,  $file, $title, $printModal, $printLog) {

        $contents = file_get_contents($file);

        if ( $printModal ) {
            echoTitle($logFile, $title);
            echoAndLog($logFile, $contents."\n");
        }
        // if ( $printLog ) {
        //     log::add('AbeilleDbConf', 'info', '');
        //     log::add('AbeilleDbConf', 'info', '-----------------------');
        //     log::add('AbeilleDbConf', 'info', $title);
        //     log::add('AbeilleDbConf', 'info', '-----------------------');
        //     log::add('AbeilleDbConf', 'info', $contents);
        //     log::add('AbeilleDbConf', 'info', '');
        // }
    }

    /**
     * Filter and print a file into the sreen(modal)/logFile with a title before
     *
     * @param   logFile     file where to store file
     * @param   file        file used as input to have data
     * @param   title       title to print before the file
     * @param   filter      Text which need to be in the line
     * @param   printModal  Shall we print to the screen (modal)
     * @param   logFile     Shall we print into the log
     *
     * @return  none
     */
    function getFileFilterAndPrint($logFile,  $file, $title, $filter, $printModal, $printLog) {

        if ( $printModal ) {
            echoTitle($logFile, $title);
            if ($file = fopen($file, "r")) {
                while(!feof($file)) {
                    $textperline = fgets($file);
                    if (strpos($textperline,'Modelisation'))
                        echoAndLog($logFile, $textperline."\n");
                }
                fclose($file);
            }
            echoAndLog($logFile, "\n");
        }
        // if ( $printLog ) {
        //     log::add('AbeilleDbConf', 'info', '');
        //     log::add('AbeilleDbConf', 'info', '-----------------------');
        //     log::add('AbeilleDbConf', 'info', $title );
        //     log::add('AbeilleDbConf', 'info', '-----------------------');
        //     if ($file = fopen($file, "r")) {
        //         while(!feof($file)) {
        //             $textperline = fgets($file);
        //             if (strpos($textperline,'Modelisation'))
        //                 log::add('AbeilleDbConf', 'info', $textperline );
        //         }
        //         fclose($file);
        //     }
        //     log::add('AbeilleDbConf', 'info', '' );
        // }

    }

    function requestAndPrint($logFile, $link, $sql, $title, $printModal, $printFile) {
        if ( $printModal ) {
            echoTitle($logFile, $title);
            echoAndLog($logFile, "{");
        }
        // if ( $printFile ) {
        //     log::add( 'AbeilleDbConf', 'info', '');
        //     log::add( 'AbeilleDbConf', 'info', '-----------------------');
        //     log::add( 'AbeilleDbConf', 'info', $title);
        //     log::add( 'AbeilleDbConf', 'info', '-----------------------');
        //     $i=0;
        //     log::add( 'AbeilleDbConf', 'info', '{');
        // }

        $i=0;
        if ($result = mysqli_query($link, $sql)) {
            while ($row = $result->fetch_assoc()) {
                if ( $printModal ) {
                    if ($i==0) { echoAndLog($logFile, '"'.$i.'":'.json_encode($row)); }
                    else { echoAndLog($logFile, ',"'.$i.'":'.json_encode($row)); }
                }
                // if ($printFile) {
                //     if ($i==0) {log::add( 'AbeilleDbConf', 'info', '"'.$i.'":'.json_encode($row)); }
                //     else { log::add( 'AbeilleDbConf', 'info', ',"'.$i.'":'.json_encode($row)); }
                // }
                $i++;
            }
            mysqli_free_result($result);
        }
        if ( $printModal ) { echoAndLog($logFile, "}\n\n"); }
        // if ( $printFile ) { log::add( 'AbeilleDbConf', 'info', '}'); }
    }

    function linuxDetails($logFile) {
        echoTitle($logFile, '2/ Linux');
        exec('cat /etc/issue', $result1);
        echoAndLog($logFile, json_encode($result1)."\n", 1);
        exec('uname -a', $result2);
        echoAndLog($logFile, json_encode($result2)."\n\n", 1);
    }

    function zigateDetails($logFile) {
        $space = '    ';
        echoTitle($logFile, '3/ Firmware');
        for ($i = 1; $i <= config::byKey('zigateNb', 'Abeille', '1', 1); $i++) {
            if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y') {
                continue; // Zigate disabled
            }

            if ( is_object(Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')) ) {
                $ruche = Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille');
                foreach ( $ruche->getCmd() as $cmd ) {
                    // if ($cmd->getLogicalId()=='SW-Application')
                    //     echoAndLog($logFile,$space.'SW-Application: '.$cmd->execCmd()."\n", 1);
                    if ($cmd->getLogicalId()=='SW-SDK')
                        echoAndLog($logFile,"Zigate ".$i.": ".$cmd->execCmd()."\n");
                }
            }
            // echoAndLog($logFile,"\n", 1);
        }
        echoAndLog($logFile,"\n", 1);
    }

    function dataInDdDetails($logFile,$CONFIG) {
        /* Connect to DB */
        $link = mysqli_connect($CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']);

        /* check connection */
        if (mysqli_connect_errno()) {
            echo("Connect failed: ".json_encode(mysqli_connect_error()));
            exit();
        }

        requestAndPrint($logFile, $link, "SELECT * FROM `update`    WHERE `name` = 'Abeille'",        "5/ {{Version (Jeedom DB)}}",      1, 1);
        requestAndPrint($logFile, $link, "SELECT * FROM `cron`      WHERE `class` = 'Abeille'",       "6/ {{Liste des cron}}",           1, 1);
        requestAndPrint($logFile, $link, "SELECT * FROM `config`    WHERE `plugin` = 'Abeille'",      "7/ {{Configuration du plugin}}",  1, 1);
        requestAndPrint($logFile, $link, "SELECT * FROM `eqLogic`   WHERE `eqType_name` = 'Abeille'", "8/ {{Liste des abeilles}}",       1, 1);
        requestAndPrint($logFile, $link, "SELECT * FROM `cmd`       WHERE `eqType` = 'Abeille'",      "9/ {{Liste des commandes}}",      0, 1);

        mysqli_close($link);
    }

    //------------------------------------------------------------------------------------------
    // Main
    //------------------------------------------------------------------------------------------
    echoAndLog($logFile, "Informations clefs nécessaires au support.\n\n", 0);
    echoAndLog($logFile, 'Quand <a href="https://github.com/KiwiHC16/Abeille/issues/new" target="_blank">vous ouvrez une "issue"</a> dans GitHub merci de copier/coller les 3 premiers chapitres ci dessous '."\n");
    echoAndLog($logFile, "Pour l'intégration d'un équipement non encore supporté ajoutez le chapitre 4.\n\n");

    getFileAndPrint($logFile, __DIR__.'/../../plugin_info/AbeilleVersion.inc', "1/ Version (AbeilleVersion.inc)", 1, 1);

    linuxDetails($logFile);

    zigateDetails($logFile);

    getFileFilterAndPrint($logFile, __DIR__.'/../../../../log/AbeilleParser.log', "4/ AbeilleParser / Modelisation", "Modelisation", 1, 1);

    dataInDdDetails($logFile, $CONFIG);
?>
