<?php

require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';

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
        if (strlen($message) >= 1 && Tools::getNumberFromLevel($loglevel) <= Tools::getNumberFromLevel($GLOBALS["requestedlevel"])) {
            fwrite(STDOUT, $loggerName . ' ' . date("Y-m-d H:i:s") . '[' . strtoupper($GLOBALS["requestedlevel"]) . ']' . $message . PHP_EOL);;
        }
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

        Tools::deamonlog("debug", "Tools: loading file " . $jsonFile . " in " . $configDir);
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
        log::add('Abeille', 'debug', 'getJSonConfigFilebyDevices: devicefilename' . $deviceFilename);
        if (!is_file($deviceFilename)) {
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: file not found devicefilename' . $deviceFilename);
            return;
        }

        $content = file_get_contents($deviceFilename);

        $deviceJson = json_decode($content, true);
        if (json_last_error()!=JSON_ERROR_NONE){
            log::add('Abeille', 'error', 'getJSonConfigFilebyDevices: filename content is not a json: ' . $content);
            return;
        }

        log::add('Abeille', 'debug', 'getJSonConfigFilebyDevices: json found Tools: nb line ' . strlen($content));
        return $deviceJson;
    }

}

?>