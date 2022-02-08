<?php

    /*
     * Abeille log functionalities
     */

    include_once __DIR__.'/../../../../core/php/core.inc.php';

    $curLogLevelNb = 0; // Abeille log level number: 0=none, 1=error/default, 2=warning, 3=info, 4=debug
    $logDir = ''; // Log directory
    $logFile = ''; // Log file name (ex: 'AbeilleSerialRead1', configured thru logSetConf())
    $logMaxLines = 2000; // Default max number of lines before Jeedom one is taken
    $logNbOfLines = 0; // Current number of lines
    $hideLevel = FALSE; // Display log level if FALSE (configured thru logSetConf())
    $GLOBALS['tmpDir'] = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory

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

       /* If logLevel is unknown then default = error level */
       return 1;
    }

    /* Library config for logs.
       'lFile' = log file name or absolut path.
         If not absolut path, log dir = Jeedom default (/var/www/html/log)
         If absolut path, this is the taken destination.
       'hideLevel' = Allows to disable display of log level if TRUE. */
    function logSetConf($lFile = '', $hideLevel = FALSE)
    {
        $GLOBALS["curLogLevelNb"] = logGetPluginLevel();
        $GLOBALS["logDir"] = "";
        $GLOBALS["logFile"] = "";
        $GLOBALS["logNbOfLines"] = 0;
        $GLOBALS["hideLevel"] = $hideLevel;

        /* Taking Jeedom config with a margin to avoid Jeedom truncates
           the log itself before it is saved */
        $GLOBALS["logMaxLines"] = log::getConfig('maxLineLog') - 10;

        if ($lFile == "")
            return; // Output of STDOUT
        if (substr($lFile, 0, 1) == "/") {
            $GLOBALS["logDir"] = dirname($lFile);
            $GLOBALS["logFile"] = basename($lFile);
        } else {
            $GLOBALS["logDir"] = __DIR__."/../../../../log/"; // Jeedom default log dir
            $GLOBALS["logFile"] = $lFile;
        }

        /* What is number of lines in current log ? */
        $logPath = $GLOBALS["logDir"]."/".$GLOBALS["logFile"];
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
       If 'logLevel' is empty, always log and do not print log level.
       WARNING: A call to 'logSetConf()' is expected once prior to 'logMessage()'. */
    function logMessage($logLevel, $msg)
    {
        if ($logLevel != "") {
            if (!isset($GLOBALS["curLogLevelNb"]))
                $GLOBALS["curLogLevelNb"] = 0;
            if (logGetLevelNumber($logLevel) > $GLOBALS["curLogLevelNb"])
                return; // Nothing to do for current log level

            $logLevel = strtolower(trim($logLevel));
            if ($logLevel == "warning")
                $logLevel = "warn";
            /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
        }

        /* Computing line prefix */
        if (($logLevel == "") || ($GLOBALS["hideLevel"] == TRUE))
            $prefix = '['.date('Y-m-d H:i:s').'] ';
        else
            $prefix = '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] ';

        if ($GLOBALS["logFile"] == '') {
            echo ($prefix.$msg."\n");
            fflush(STDOUT);
        } else {
            $lFile = $GLOBALS["logDir"]."/".$GLOBALS["logFile"];
            if (strcasecmp($logLevel, "error") == 0)
                file_put_contents($lFile, $prefix."ERROR: ".$msg."\n", FILE_APPEND);
            else
                file_put_contents($lFile, $prefix.$msg."\n", FILE_APPEND);
            $GLOBALS["logNbOfLines"]++;
            if (strcasecmp($logLevel, "error") == 0)
                // Tcharp38: Trim may be required to remove spaces before (log indent)
                message::add("Abeille", "ERREUR: ".$msg, '');

            if ($GLOBALS["logNbOfLines"] > $GLOBALS["logMaxLines"]) {
                $tmpDir = jeedom::getTmpFolder("Abeille"); // Jeedom temp directory
                $tmpLogFile = $GLOBALS["logFile"];
                if (substr($tmpLogFile, -4) == ".log")
                    $tmpLogFile = substr($tmpLogFile, 0, -4); // Removing extension
                $tmpLogFile .= "-prev.log";
                $lFileTmp = $tmpDir."/".$tmpLogFile;
                rename($lFile, $lFileTmp);

                file_put_contents($lFile, $prefix."Max reached (".$GLOBALS["logMaxLines"]." lignes) Previous log saved as '".$tmpDir."/".$tmpLogFile."'\n", FILE_APPEND);
                $GLOBALS["logNbOfLines"] = 1;
            }
        }
    }

    /* Log function for development purposes.
       Output message to "AbeilleDebug.log" */
    function logDebug($msg = "")
    {
        global $tmpDir;
        // $logDir = __DIR__.'/../../../../log/';
        $logFile = "AbeilleDebug.log";
        file_put_contents($tmpDir.'/'.$logFile, '['.date('Y-m-d H:i:s').'] '.$msg."\n", FILE_APPEND);
    }

    /* Return proper prefix to use with scripts outputs */
    function logGetPrefix($logLevel) {
        if (($logLevel == "") || ($GLOBALS["hideLevel"] == TRUE)) {
            return '['.date('Y-m-d H:i:s').'] ';
        }

        $logLevel = strtolower(trim($logLevel));
        if ($logLevel == "warning")
            $logLevel = "warn";
        /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
        $pref = '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $logLevel).'] ';
        return $pref;
    }
?>
