<?php
    /*
     * Abeille's static config file
     */

    define('lastDbVersion', 20250911);
    $in = "/tmp/AbeilleDeamonInput";
    $resourcePath = realpath(__DIR__.'/../../resources');
    define('wifiLink', '/tmp/zigateWifi'); // For WIFI: Socat output
    define('otaDir', 'tmp/fw_ota'); // OTA FW location relative to Abeille's root
    define('modelsDir', __DIR__ . '/devices/'); // Abeille's supported devices
    define('modelsLocalDir', __DIR__ . '/devices_local/'); // Unsupported/user devices
    define('corePhpDir', __DIR__.'/../php/');
    define('corePythonDir', __DIR__.'/../python/');
    define('logsDir', __DIR__.'/../../../../log/');

    /* Inter-daemons queues:
       array['<queueName>'] = array("id" => queueId, "max" => maxMsgSize)
       Note: Prefix = 0xAB xxxx to identify Abeille's queues */
    $abQueues = array();
    $abQueues["xToParser"] = array( "id" => 0xAB0336, "max" => 2048 );
    $abQueues["parserToLQI"] = array( "id" => 0xAB00E1, "max" => 2048 );
    $abQueues["parserToRoutes"] = array( "id" => 0xAB00E2, "max" => 2048 );
    $abQueues["parserToCli"] = array( "id" => 0xAB02D4, "max" => 1024 );
    $abQueues["parserToCmdAck"] = array( "id" => 0xAB03E7, "max" => 512 ); // Parser to cmd for 8000/8012/8702 statuses
    $abQueues["xToCmd"] = array( "id" => 0xAB04BC, "max" => 1024 ); // AbeilleCmd inputs
    // $abQueues["cmdToMon"] = array( "id" => 0xAB0082, "max" => 512 ); // Messages to zigate (cmd to monitor)
    // $abQueues["parserToMon"] = array( "id" => 0xAB0083, "max" => 1024 ); // Messages from zigate (parser to monitor)
    $abQueues["xToMon"] = array( "id" => 0xAB0081, "max" => 1024 ); // Messages to monitor (cmd/parser to monitor)
    // $abQueues["monToCmd"] = array( "id" => 0xAB0084, "max" => 1024 ); // Messages to cmd (addr update)
    $abQueues["parserToAssist"] = array( "id" => 0xAB008D, "max" => 512 ); // Parser to EQ assistant
    $abQueues["xToAbeille"] = array( "id" => 0xAB026D, "max" => 1024 ); // All messages to main daemon (AbeilleMainD). TO BE RENAMED to 'xToMain'
    $GLOBALS['abQueues'] = $abQueues;

    // 3 priorities only: 1=MAX, 2=normal, 3=MIN
    define('priorityMax',           1);
    define('priorityUserCmd',       1); // Action utiliateur qui doit avoir une sensation de temps réel
    define('priorityInterrogation', 2); // Message pour recuperer des etats, valeurs
    define('priorityMin',           3); // est egale aux max des priorités définies.

    // New priorities model, will be replaced by old one again after code review (KiwiHC16)
    define('PRIO_LOW', priorityMin);
    define('PRIO_NORM', priorityInterrogation); // Normal
    define('PRIO_HIGH', priorityMax); // High priority (ex: parser to cmd to react on wakeup)

    define('maxNbOfZigate', 6); // Number of supported zigates
    $GLOBALS['maxNbOfZigate'] = maxNbOfZigate;
    define('maxGateways', 6); // Number of supported gateways (zigate/ezsp)
    $GLOBALS['maxGateways'] = maxGateways;

    define('maxRetryDefault', 3);

    /* URL to access documentations */
    define('urlProducts', "https://github.com/KiwiHC16/AbeilleDoc/blob/master/docs/products");
    define('urlUserMan', "https://kiwihc16.github.io/AbeilleDoc/");

    /* Developer config file */
    define('dbgFile', __DIR__.'/../../tmp/debug.json');

    /* A bit per daemon */
    define('daemonCmd', 1 << 0);
    define('daemonParser', 1 << 1);
    define('daemonSerialRead1', 1 << 2);
    define('daemonSerialRead2', 1 << 3);
    define('daemonSerialRead3', 1 << 4);
    define('daemonSerialRead4', 1 << 5);
    define('daemonSerialRead5', 1 << 6);
    define('daemonSerialRead6', 1 << 7);
    // define('daemonSerialRead7', 1 << 8);
    // define('daemonSerialRead8', 1 << 9);
    // define('daemonSerialRead9', 1 << 10);
    // define('daemonSerialRead10', 1 << 11);
    define('daemonSocat1', 1 << 12);
    define('daemonSocat2', 1 << 13);
    define('daemonSocat3', 1 << 14);
    define('daemonSocat4', 1 << 15);
    define('daemonSocat5', 1 << 16);
    define('daemonSocat6', 1 << 17);
    // define('daemonSocat7', 1 << 18);
    // define('daemonSocat8', 1 << 19);
    // define('daemonSocat9', 1 << 20);
    // define('daemonSocat10', 1 << 21);
    define('daemonMonitor', 1 << 22);
    define('daemonMain', 1 << 23);

    define ("daemonStopTimeout", 2000); // 2sec
?>
