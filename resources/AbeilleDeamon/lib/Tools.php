<?php

// require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';

class Tools
{

    /**
     * Convert log level string to number to compare more easily.
     *
     * @param $loglevel
     * @return int
     */
    public static function getNumberFromLevel($loglevel)
    {
        if (strcasecmp($loglevel, "NONE") == 0) {
            $iloglevel = 0;
        }
        if (strcasecmp($loglevel, "ERROR") == 0) {
            $iloglevel = 1;
        }
        if (strcasecmp($loglevel, "WARNING") == 0) {
            $iloglevel = 2;
        }
        if (strcasecmp($loglevel, "INFO") == 0) {
            $iloglevel = 3;
        }
        if (strcasecmp($loglevel, "DEBUG") == 0) {
            $iloglevel = 4;
        }
        return $iloglevel;
    }

    /***
     * if loglevel is lower/equal than the app requested level then message is written
     *
     * @param string $loglevel
     * @param string $message
     */
    public static function deamonlog($loglevel = 'NONE', $loggerName = 'Tools', $message = '')
    {
        if (strlen($message) >= 1 && Tools::getNumberFromLevel($loglevel) <= Tools::getNumberFromLevel($GLOBALS['requestedlevel'])) {
            fwrite(STDOUT, $loggerName . ' ' . date('Y-m-d H:i:s') . '[' . strtoupper($GLOBALS['requestedlevel']) . ']' . $message . PHP_EOL);
        }
    }

    /**
     * Needed for Template Generation
     *
     *
     *
     */
    public static function getJSonConfigFilebyCmd($cmd)
    {
        
        $cmdFilename = dirname(__FILE__) . '/../../../core/config/devices/Template/' . $cmd . '.json';
        
        if (!is_file($cmdFilename)) { return; }
        
        $content = file_get_contents($cmdFilename);
        
        $cmdJson = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename content is not a json: ' . $content);
            return;
        }
        
        return $cmdJson;
    }
    
    /**
     * Needed for Template Generation
     *
     *
     *
     */
    public static function getJSonConfigFilebyDevicesTemplate($device = 'none')
    {
        $deviceCmds = array();
        $deviceFilename = dirname(__FILE__) . '/../../../core/config/devices/' . $device . '/' . $device . '.json';
        
        if (!is_file($deviceFilename)) { return; }
        
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
        
        return $deviceTemplate;
    }
    
    /**
     * return the config list from a file located in core/config directory
     *
     * @param null $jsonFile
     * @return mixed|void
     */
    public static function getJSonConfigFiles($jsonFile = null)
    {

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

        Tools::deamonlog("Tools: nb line " . strlen($content));

        return $json;
    }

    /**
     * Get device config with device name located in core/config/devices/object.json
     *
     * @param null $device
     * @param Abeille logger name
     * @return bool|mixed|void
     */
    public static function getJSonConfigFilebyDevices($device = 'none', $logger = 'Abeille')
    {

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
    public function getTrimmedValueForJsonFiles($filename = "")
    {
        //remove lumi. from name as all xiaomi devices have a lumi. name
        //remove all space in names for easier filename handling
        $trimmed = strlen($filename) > 1 ? str_replace(' ', '', str_replace('lumi.', '', $filename)) : "";
        return $trimmed;
    }


    /**
     * Scan config/devices directory to load devices name
     *
     * @param string $logger
     * @return array of json devices name
     */
    public function getDeviceNameFromJson($logger = 'Abeille')
    {
        $return = array();
        $deviceDir = dirname(__FILE__) . '/../../../core/config/devices/';
        echo 'ddir: ' . $deviceDir;
        if ($dh = opendir($deviceDir)) {
            while (($file = readdir($dh)) !== false) {

                try {
                    $content = file_get_contents($deviceDir . $file . DIRECTORY_SEPARATOR . $file . '.json');
                    //echo("nomCourt : $file : type : " . filetype($deviceDir . $file) . " \n");
                    //echo("fullName: " . $deviceDir . $file . DIRECTORY_SEPARATOR . $file . '.json' . " \n");
                    $temp = explode(":", $content);
                    $atemp = explode('"', str_replace(array("\r", "\n"), '', $temp[0]));
                    $found = $atemp[1];
                    if ($found != "" and  strlen($found)>1) {
                        //echo 'file:' .$file.' / nom: ' . $found . " \n";
                        array_push($return, $found);
                    }

                } catch (Exception $e) {
                    log::add($logger, 'error', 'Cannot read content of file ' . $file);
                }
            }
        }
        //filter out empty value
        return array_filter($return,function($value){return strlen($value)>1;});
    }
}

    
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // La suite is Used for test
    // en ligne de comande =>
    // "php Tools.class.php 1"
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    if (isset($argv[1])) {
        $debugBEN = $argv[1];
    } else {
        $debugBEN = 0;
    }
    if ($debugBEN != 0) {
        echo "Debut Tools.php test mode\n";
        $message = new stdClass();
        
        switch ($debugBEN) {
            case "1":
                $items = Tools::getJSonConfigFilebyDevicesTemplate('LLC020');
                echo json_encode($items);
                break;
        } // switch
    } // if debug
                                                                   
                                                                   
                


?>
