<?php

    /*
     * Abeille log functionalities
     */

    include_once __DIR__.'/../../../../core/php/core.inc.php';

    $curLogLevelNb = 0; // Abeille log level number: 0=none, 1=error/default, 2=warning, 3=info, 4=debug
    $logFile = ''; // Log file name (ex: 'AbeilleSerialRead1')
    $logMaxLines = 2000; // Default max number of lines
    $logNbOfLines = 0; // Current number of lines

    /* Get Abeille current log level as a number:
       0=none, 1=error/default, 2=warning, 3=info, 4=debug */
    function logGetPluginLevel() {
        // var_dump( config::getLogLevelPlugin()["log::level::Abeille"] );
        // si debug:  {"100":"1","200":"0","300":"0","400":"0","1000":"0","default":"0"}
        // si info:   {"100":"0","200":"1","300":"0","400":"0","1000":"0","default":"0"}
        // si warning:{"100":"0","200":"0","300":"1","400":"0","1000":"0","default":"0"}
        // si error:  {"100":"0","200":"0","300":"0","400":"1","1000":"0","default":"0"}
        // si aucun:  {"100":"0","200":"0","300":"0","400":"0","1000":"1","default":"0"}
        // si defaut: {"100":"0","200":"0","300":"0","400":"0","1000":"0","default":"1"}
        $logLevelPluginJson = config::getLogLevelPlugin()["log::level::Abeille"];
        if ( $logLevelPluginJson[    '100'] ) return 4;
        if ( $logLevelPluginJson[    '200'] ) return 3;
        if ( $logLevelPluginJson[    '300'] ) return 2;
        if ( $logLevelPluginJson[    '400'] ) return 1;
        if ( $logLevelPluginJson[   '1000'] ) return 0;
        if ( $logLevelPluginJson['default'] ) return 1; // This one is set to 1 but should be found from conf
    }

    /* Convert logLevel from string to number:
       0=none, 1=error/default, 2=warning, 3=info, 4=debug */
    function logGetLevelNumber($logLevel) {

        $levels = array(
            "NONE" => 0,
            "ERROR" => 1,
            "WARNING" => 2,
            "INFO" => 3,
            "DEBUG" => 4
        );

       $upperString = strtoupper(trim($logLevel));
       if (array_search($upperString, $levels, false))
           return $levels[$upperString];

       /* If logLevel is unknown then no log is allowed */
       return "0";
    }

    /* Library config for logs */
    function logSetConf($lFile = '')
    {
        $GLOBALS["curLogLevelNb"] = logGetPluginLevel();
        $GLOBALS["logFile"] = $lFile;
        $GLOBALS["logNbOfLines"] = 0;

        $jeedomMaxLines = log::getConfig('maxLineLog');
        if ($jeedomMaxLines < $GLOBALS["logMaxLines"]) {
            /* Taking Jeedom config with a margin to be sure AbeilleLog will be able to
               move previous log to tmp before Jeedom truncate it */
            $GLOBALS["logMaxLines"] = $jeedomMaxLines - 2;
            // WARNING: Don't output UTF8 encoded text thru fwrite(). It kills Jeedom display. 
            // To be understood.
            // logMessage("warning", "Le log est limité à ".$jeedomMaxLines." lignes par la config Jeedom");
            logMessage("warning", "Le log est limite a ".$jeedomMaxLines." lignes par la config Jeedom");
        }

        if ($lFile == "")
            return; // Output of STDOUT

        /* What is number of lines in current log ? */
        $logPath = __DIR__."/../../../../log/".$lFile;
        if (file_exists($logPath) == FALSE)
            return;
        $cmd = "wc -l ".$logPath." | awk '{print $1}'";
        exec($cmd, $out, $status);
        if ($status != 0) {
            logMessage("warning", "AbeilleLog: Impossible de mesurer la taille du log.");
            return;
        }
        $GLOBALS["logNbOfLines"] = intval($out[0]);
   }

    /* Log given message to '$logFile' defined thru 'logSetConf()'.
       '\n' is automatically added at end of line.
       WARNING: A call to 'logSetConf()' is expected once prior to 'logMessage()'. */
    function logMessage($logLevel, $msg)
    {
        if (logGetLevelNumber($logLevel) > $GLOBALS["curLogLevelNb"])
            return; // Nothing to do for current log level

        $logLevel = strtolower(trim($logLevel));
        if ($logLevel == "warning")
            $logLevel = "warn";
        /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */

        if ($GLOBALS["logFile"] == '') {
            fwrite(STDOUT, '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] '.$msg."\n");
            fflush(STDOUT);
        } else {
            $lFile = __DIR__.'/../../../../log/'.$GLOBALS["logFile"];
            file_put_contents($lFile, '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] '.$msg."\n", FILE_APPEND);
            $GLOBALS["logNbOfLines"]++;

            if ($GLOBALS["logNbOfLines"] > $GLOBALS["logMaxLines"]) {
                $tmpDir = __DIR__.'/../../tmp';
                if (file_exists($tmpDir) == FALSE)
                    mkdir($tmpDir);
                $lFileTmp = $tmpDir."/".$GLOBALS["logFile"]."-prev";
                rename($lFile, $lFileTmp);
                $GLOBALS["logNbOfLines"] = 0;
            }
        }
    }
?>
