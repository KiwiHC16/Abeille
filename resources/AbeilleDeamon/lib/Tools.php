<?php

// require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';

class Tools
{
    /**
     * Get Plugin Log Level.
     *
     * @param: pluginName: Nom du plugin
     * @return: int, niveau de log defini pour le plugin
     */
    public static function getPluginLogLevel( $pluginName ) {
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

    /**
     * Convert log level string to number to compare more easily.
     *
     * @param $loglevel
     * @return int
     */
     public static function getNumberFromLevel($loglevel) {

         $niveau = array(
                         "NONE" => 0,
                         "ERROR" => 1,
                         "WARNING" => 2,
                         "INFO" => 3,
                         "DEBUG" => 4
         );

       $upperString = strtoupper(trim($loglevel));
       if (array_search($upperString,$niveau,false)) {
           return $niveau[$upperString];
            }
       #if logLevel is not found, then no log is allowed
       return "0";
       }

    /***
     * if loglevel is lower/equal than the app requested level then message is written
     *
     * @param string $log level of the message
     * @param string plugin Name
     * @param string logger name = script qui envoie le message
     * @param string le message lui meme
     * @param string $message
     */
    public static function deamonlogFilter($loglevel = 'NONE', $pluginName, $loggerName = 'Tools', $message = '') {
        if (strlen($message) < 1) return;
        if (Tools::getNumberFromLevel($loglevel) <= Tools::getPluginLogLevel($pluginName)) {
            $loglevel = strtolower(trim($loglevel));
            if ($loglevel == "warning")
                $loglevel = "warn";
            /* Note: sprintf("%-5.5s", $loglevel) to have vertical alignment. Log level truncated to 5 chars => error/warn/info/debug */
            fwrite(STDOUT, '['.date('Y-m-d H:i:s').']['.sprintf("%-5.5s", $loglevel).'] '.$message.PHP_EOL);
        }
    }

    /**
     * Needed for Template Generation
     *
     *
     *
     */
    public static function getJSonConfigFilebyCmd($cmd) {

        $cmdFilename = dirname(__FILE__) . '/../../../core/config/devices/Template/' . $cmd . '.json';

        if (!is_file($cmdFilename)) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename is not a file: ' . $cmdFilename );
            return array();
        }

        $content = file_get_contents($cmdFilename);

        $cmdJson = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename content is not a json: ' . $content);
            return array();
        }

        return $cmdJson;
    }

    /**
     * Needed for Template Generation
     *
     *
     *
     */
    public static function getJSonConfigFilebyDevicesTemplate($device = 'none') {
        // log::add('Abeille', 'debug', 'getJSonConfigFilebyDevicesTemplate start');

        $deviceCmds = array();
        $deviceFilename = dirname(__FILE__) . '/../../../core/config/devices/' . $device . '/' . $device . '.json';

        if (!is_file($deviceFilename)) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename not found: ' . $deviceFilename . ' will send back default template.');
            $device = 'defaultUnknown';
            $deviceFilename = dirname(__FILE__) . '/../../../core/config/devices/' . $device . '/' . $device . '.json';
        }

        $content = file_get_contents($deviceFilename);

        // Recupere le template master
        $deviceTemplate = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename content is not a json: ' . $content);
            return;
        }

        // Basic Commands
        $deviceCmds += self::getJSonConfigFilebyCmd("IEEE-Addr");
        $deviceCmds += self::getJSonConfigFilebyCmd("Link-Quality");
        $deviceCmds += self::getJSonConfigFilebyCmd("Time-Time");
        $deviceCmds += self::getJSonConfigFilebyCmd("Time-TimeStamp");
        $deviceCmds += self::getJSonConfigFilebyCmd("Power-Source");
        $deviceCmds += self::getJSonConfigFilebyCmd("Short-Addr");
        $deviceCmds += self::getJSonConfigFilebyCmd("online");


        // Recupere les templates Cmd instanciées
        foreach ( $deviceTemplate[$device]['Commandes'] as $cmd=>$file ) {
            if ( substr($cmd, 0, 7) == "include" ) {
                $deviceCmds += self::getJSonConfigFilebyCmd($file);
            }
        }

        // Ajoute les commandes au master
        $deviceTemplate[$device]['Commandes'] = $deviceCmds;

        // log::add('Abeille', 'debug', 'getJSonConfigFilebyDevicesTemplate end');
        return $deviceTemplate;
    }

    /**
     * return the config list from a file located in core/config directory
     *
     * @param null $jsonFile
     * @return mixed|void
     */
    public static function getJSonConfigFiles($jsonFile = null) {

        $configDir = dirname(__FILE__) . '/../../../core/config/';

        // Tools::deamonlog("debug", "Tools: loading file " . $jsonFile . " in " . $configDir);
        $confFile = $configDir . $jsonFile;

        //file exists ?
        if (!is_file($confFile)) {
            Tools::deamonlog('error', $confFile . ' not found.');
            return;
        }
        // is valid json
        $content = file_get_contents($confFile);
        if (!is_json($content)) {
            Tools::deamonlog('error', $confFile . ' is not a valid json.');
            return;
        }

        $json = json_decode($content, true);

        // self::deamonlogFilter( "DEBUG", 'Abeille', 'Tools', "AbeilleTools: nb line " . strlen($content) );

        return $json;
    }

    /**
     * Get device config with device name located in core/config/devices/object.json
     *
     * @param null $device
     * @param Abeille logger name
     * @return bool|mixed|void
     */
    public static function getJSonConfigFilebyDevices($device = 'none', $logger = 'Abeille') {

        $deviceFilename = dirname(__FILE__) . '/../../../core/config/devices/' . $device . '/' . $device . '.json';
        // log::add('Abeille', 'debug', 'getJSonConfigFilebyDevices: devicefilename' . $deviceFilename);
        if (!is_file($deviceFilename)) {
            // log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: file not found devicefilename' . $deviceFilename);
            return;
        }

        $content = file_get_contents($deviceFilename);

        $deviceJson = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename content is not a json: ' . $content);
            return;
        }

        // log::add('Abeille', 'debug', 'getJSonConfigFilebyDevices: ' . $device . ' json found Tools: nb line ' . strlen($content));
        return $deviceJson;
    }

    /**
     * Return filename ready to be search in devices directories
     *
     * @param string $filename
     * @return mixed|string*
     */
    public static function getTrimmedValueForJsonFiles($filename = "") {
        //remove lumi. from name as all xiaomi devices have a lumi. name
        //remove all space in names for easier filename handling
        $trimmed = strlen($filename) > 1 ? str_replace(' ', '', str_replace('lumi.', '', $filename)) : "";
        return $trimmed;
    }

    /*
     * Scan config/devices directory to load devices name
     *
     * @param string $logger
     * @return array of json devices name
     */
    public static function getDeviceNameFromJson($logger = 'Abeille') {
        $return = array();
        $devicesDir = __DIR__.'/../../../core/config/devices/';
        if (file_exists($devicesDir) == FALSE) {
            log::add($logger, 'error', "Problème d'installation. Le chemin '...core/config/devices' n'existe pas.");
            return $return;
        }

        $dh = opendir($devicesDir);
        while (($dirEntry = readdir($dh)) !== false) {
            if (($dirEntry == ".") || ($dirEntry == ".."))
                continue;
            if (($dirEntry == "listeCompatibilite.php") || ($dirEntry == "Template"))
                continue;

            $file = $dirEntry.".json";
            $fullPath = $devicesDir.$dirEntry.DIRECTORY_SEPARATOR.$file;
            if (file_exists($fullPath) == FALSE) {
                log::add($logger, 'warning', "Fichier introuvable: ".$file);
                return $return;
            }
        
            try {
                $content = file_get_contents($fullPath);
                //echo("nomCourt : $file : type : " . filetype($dirEntry . $file) . " \n");
                //echo("fullName: " . $dirEntry . $file . DIRECTORY_SEPARATOR . $file . '.json' . " \n");
                $temp = explode(":", $content);
                $atemp = explode('"', str_replace(array("\r", "\n"), '', $temp[0]));
                $found = $atemp[1];
                if ($found != "" and  strlen($found)>1) {
                    //echo 'file:' .$file.' / nom: ' . $found . " \n";
                    array_push($return, $found);
                }
            } catch (Exception $e) {
                log::add($logger, 'error', 'Impossible de lire le contenu du fichier ' . $file);
            }
        }

        //filter out empty value
        return array_filter($return,function($value){return strlen($value)>1;});
    }
}

?>
