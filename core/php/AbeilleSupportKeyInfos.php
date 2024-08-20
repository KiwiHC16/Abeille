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
    $outputFile = $tmpDir.'/AbeilleKeyInfos.log';

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
    function logIt($msg = "\n", $append = true) {
        global $outputFile;

        // echo $msg;
        if ($append)
            file_put_contents($outputFile, $msg, FILE_APPEND);
        else
            file_put_contents($outputFile, $msg);
    }

    /**
     * Print title with underlines
     *
     * @param   title       Title to print
     *
     * @return  none
     */
    function logTitle($title, $append = 1) {
        $line = "";
        if (substr($title, 0, 2) == '{{') {
            $title = substr($title, 2);
            $title = substr($title, 0, -2);
        }
        $len = strlen($title);
        for ($i = 0; $i < $len; $i++)
            $line .= '=';

        // echo $line."\n";
        logIt($title."\n", $append);
        logIt($line."\n");
    }

    function requestAndPrint($link, $table, $title) {
        logTitle($title);

        switch ($table) {
        case "update": $sql = "SELECT * FROM `update` WHERE `name` = 'Abeille'"; break;
        case "cron": $sql = "SELECT * FROM `cron` WHERE `class` = 'Abeille'"; break;
        case "config": $sql = "SELECT * FROM `config` WHERE `plugin` = 'Abeille'"; break;
        case "eqLogic": $sql = "SELECT * FROM `eqLogic` WHERE `eqType_name` = 'Abeille'"; break;
        case "cmd": $sql = "SELECT * FROM `cmd` WHERE `eqType` = 'Abeille'"; break;
        }
        $i = 0;
        logIt("{\n");
        if ($result = mysqli_query($link, $sql)) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['id'])) {
                    $id = $row['id'];
                    unset($row['id']);
                    logIt('    "'.$id.'": '.json_encode($row, JSON_UNESCAPED_SLASHES).",\n");
                } else if (isset($row['key'])) {
                    $key = $row['key'];
                    unset($row['key']);
                    if (($table == 'config') && ($key == 'api'))
                        logIt('    "'.$key."\": FILTERED,\n");
                    else
                        logIt('    "'.$key.'": '.json_encode($row, JSON_UNESCAPED_SLASHES).",\n");
                } else {
                    logIt('    "'.$i.'": '.json_encode($row, JSON_UNESCAPED_SLASHES).",\n");
                    $i++;
                }
            }
            mysqli_free_result($result);
        }
        logIt("}\n\n");
    }

    // Display gateways informations
    function gatewaysInfos() {
        logTitle("Gateways");

        for ($gtwId = 1; $gtwId <= maxGateways; $gtwId++) {
            if (config::byKey('ab::gtwEnabled'.$gtwId, 'Abeille', 'N') != 'Y')
                continue; // Disabled

            $gtwType = config::byKey('ab::gtwType'.$gtwId, 'Abeille', 'zigate'); // 'zigate', 'ezsp'
            $gtwSubType = config::byKey('ab::gtwSubType'.$gtwId, 'Abeille', '?');

            $eqLogic = Abeille::byLogicalId('Abeille'.$gtwId.'/0000', 'Abeille');
            if (is_object($eqLogic)) {
                $beehiveId = $eqLogic->getId();

                if ($gtwType == 'zigate') {
                    $fwVersion = '????-????';
                    $channel = "?";
                    $cmdLogic = cmd::byEqLogicIdAndLogicalId($beehiveId, 'FW-Version');
                    if ($cmdLogic)
                        $fwVersion = $cmdLogic->execCmd();
                    $cmdLogic = cmd::byEqLogicIdAndLogicalId($beehiveId, 'Network-Channel');
                    if ($cmdLogic)
                        $channel = $cmdLogic->execCmd();

                    logIt("Zigate ".$gtwId."\n");
                    logIt("- FW version: ".$fwVersion."\n");
                    logIt("- Channel   : ".$channel."\n");
                    logIt("- Type      : ".$gtwSubType."\n");
                } else if ($gtwType == 'ezsp') {
                    logIt("EZSP ".$gtwId."\n");
                    logIt("- Channel   : ".$channel."\n");
                    logIt("- Type      : ".$gtwSubType."\n");
                }
            } else
                logIt("Gateway ${gtwId} (type ${gtwType}): ERROR. No Jeedom registered device\n");
        }
        logIt("\n");
    }

    function getDeviceInfos($eqLogic, $addr) {

        $eq = [];
        $eq['addr'] = $addr;
        $eq['hName'] = $eqLogic->getHumanName();
        $eq['id'] = $eqLogic->getId();
        $eq['eqModel'] = $eqLogic->getConfiguration('ab::eqModel', []);
        $eq['isEnabled'] = $eqLogic->getIsEnable();
        $eq['timeout'] = $eqLogic->getStatus('timeout');
        $eq['lastCom'] = $eqLogic->getStatus('lastCommunication');
        $eq['txAck'] = $eqLogic->getStatus('ab::txAck', 'ok');
        $lqiCmd = $eqLogic->getCmd('info', 'Link-Quality');
        if (is_object($lqiCmd))
            $eq['lastLqi'] = $lqiCmd->execCmd();
        else
            $eq['lastLqi'] = "?";

        return $eq;

        // $eqModelId = isset($eqModel['modelName']) ? $eqModel['modelName'] : '';
        // $eqType = isset($eqModel['type']) ? $eqModel['type'] : '';
        // $extra = '';
        // $status = 'ok ';
        // if (!$eqLogic->getIsEnable())
        //     $status = "DIS";
        // else if ($eqLogic->getStatus('timeout') == 1) {
        //     $lastComm = $eqLogic->getStatus('lastCommunication');
        //     $extra = ", TIMEOUT (last comm ".$lastComm.")";
        //     $status = "TO ";
        // } else if ($eqLogic->getStatus('ab::txAck', 'ok') != 'ok') {
        //     $extra = ", NO-ACK";
        //     $status = "NA ";
        // }

        // logIt("- ${status}: ${eqHName}, Id=${eqId}${extra}\n");
        // logIt("       Addr=${addr}, Model='${eqModelId}', Type='${eqType}'\n");
    }

    function printDeviceInfos($eq) {
        $addr = $eq['addr'];
        $eqHName = $eq['hName'];
        $eqId = $eq['id'];
        $eqModel = $eq['eqModel'];
        $eqModelId = isset($eqModel['modelName']) ? $eqModel['modelName'] : '';
        $eqType = isset($eqModel['type']) ? $eqModel['type'] : '';
        $extra = '';
        $status = 'ok ';
        if (!$eq['isEnabled'])
            $status = "DIS";
        else if ($eq['timeout'] == 1) {
            $lastComm = $eq['lastCom'];
            $since =  floor((time() - strtotime($lastComm)) / 3600);
            $extra = ", TIMEOUT (since ${since}H, last comm ".$lastComm.")";
            $status = "TO ";
        } else if ($eq['txAck'] != 'ok') {
            $extra = ", NO-ACK";
            $status = "NA ";
        }
        $lastLqi = isset($eq['lastLqi']) ? $eq['lastLqi'] : '?';

        logIt("- ${status}: ${eqHName}, Id=${eqId}${extra}\n");
        logIt("       Addr=${addr}, LastLQI=${lastLqi}, Model='${eqModelId}', Type='${eqType}'\n");
    }

    // Display devices status
    function devicesInfos() {
        logTitle("Devices");
        logIt("Reminder: TO=timeout, NA=no-ack, DIS=disabled\n");

        for ($gtwId = 1; $gtwId <= maxNbOfZigate; $gtwId++) {
            if (config::byKey('ab::gtwEnabled'.$gtwId, 'Abeille', 'N') != 'Y')
                continue; // Zigate disabled

            $devices = [];
            $nbOk = 0;
            $nbTimeout = 0;
            $nbNoAck = 0;

            // Gateway first
            $eqLogic = Abeille::byLogicalId("Abeille${gtwId}/0000", 'Abeille');
            // printDeviceInfos("0000", $eqLogic);
            $dev = getDeviceInfos($eqLogic, "0000");
            if ($dev['timeout'])
                $nbTimeout++;
            if ($dev['txAck'] != 'ok')
                $nbNoAck++;
            if (($dev['timeout'] == 0) && ($dev['txAck'] == 'ok'))
                $nbOk++;
            $devices[] = $dev;

            // Then equipments
            $eqLogics = Abeille::byType('Abeille');
            foreach ($eqLogics as $eqLogic) {
                list($net, $addr) = explode("/", $eqLogic->getLogicalId());
                if ($addr == "0000")
                    continue; // It's a gateway

                $gtwId2 = substr($net, 7); // AbeilleX => X
                if ($gtwId2 != $gtwId)
                    continue;

                // printDeviceInfos($addr, $eqLogic);
                $dev = getDeviceInfos($eqLogic, $addr);
                if ($dev['timeout'])
                    $nbTimeout++;
                if ($dev['txAck'] != 'ok')
                    $nbNoAck++;
                if (($dev['timeout'] == 0) && ($dev['txAck'] == 'ok'))
                    $nbOk++;
                $devices[] = $dev;
            }

            // Print
            $gtwType = config::byKey('ab::gtwType'.$gtwId, 'Abeille', 'zigate');
            logIt("Abeille${gtwId} (${gtwType}): Ok=${nbOk}, TO=${nbTimeout}, NA=${nbNoAck}\n");

            foreach ($devices as $dev) {
                printDeviceInfos($dev);
            }
        }
        logIt("\n");
    }

    function jeedomInfos($CONFIG) {
        /* Connect to DB */
        $link = mysqli_connect($CONFIG['db']['host'], $CONFIG['db']['username'], $CONFIG['db']['password'], $CONFIG['db']['dbname']);

        /* check connection */
        if (mysqli_connect_errno()) {
            logIt("MySQL connection FAILED: ".json_encode(mysqli_connect_error())."\n");
            return;
        }

        // Key tables
        requestAndPrint($link, 'config', "Table 'config'");
        requestAndPrint($link, 'eqLogic', "Table 'eqLogic'");
        requestAndPrint($link, 'cmd', "Table 'cmd'");
        // Other tables
        requestAndPrint($link, 'update', "Table 'update'");
        requestAndPrint($link, 'cron', "Table 'cron'");

        mysqli_close($link);
    }

    //------------------------------------------------------------------------------------------
    // Main
    //------------------------------------------------------------------------------------------
    // logIt("Informations clefs nécessaires au support.\n\n", 0);
    // logIt('Quand <a href="https://github.com/KiwiHC16/Abeille/issues/new" target="_blank">vous ouvrez une "issue"</a> dans GitHub merci de copier/coller les 3 premiers chapitres ci dessous '."\n");
    // logIt("Pour l'intégration d'un équipement non encore supporté ajoutez le chapitre 4.\n\n");

    logTitle("General", 0);
    $date = date('Y/m/d H:i:s');
    logIt("Date   : ".$date."\n");
    $jLines = log::getConfig('maxLineLog');
    include_once __DIR__.'/AbeilleLog.php'; // logGetLevelName()
    $jLogLevel = logGetLevelName();
    logIt("Logs   : ".$jLines." lines (mode=${jLogLevel})\n");

    // Plateform infos
    exec('uname -a', $result2);
    logIt("Kernel : ".json_encode($result2, JSON_UNESCAPED_SLASHES)."\n");

    // Abeille's version
    $file = fopen(__DIR__."/../../plugin_info/Abeille.version", "r");
    $line = fgets($file); // Should be a comment
    $abeilleVersion = trim(fgets($file)); // Should be Abeille's version
    fclose($file);
    logIt("Abeille: Version ".$abeilleVersion."\n\n");

    // Zigate infos
    gatewaysInfos();

    // Devices status
    devicesInfos();

    jeedomInfos($CONFIG);
?>
