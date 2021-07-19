<?php
    /*
     * Abeille's static config file
     */

    $in = "/tmp/AbeilleDeamonInput";
    $resourcePath = realpath(__DIR__.'/../../resources');
    $WifiLink = "/dev/zigate";

    /* Inter-daemons queues */

    /* Top level */
    define('queueKeyAbeilleToAbeille',      121);
    define('queueKeyAbeilleToCmd',          123);
    define('queueKeyParserToAbeille',       221); // Obsolete path parser to Abeille.
    define('queueKeyParserToAbeille2',      222); // New path parser to Abeille
    define('queueKeyCmdToAbeille',          321); // Tcharp38: ??

    /* Monitoring queues */
    define('queueKeyCmdToMon',              130); // Messages to zigate (cmd to monitor)
    define('queueKeyParserToMon',           131); // Messages from zigate (parser to monitor)
    define('queueKeyMonToCmd',              132); // Messages to cmd (addr update)

    /* EQ assistant queues */
    define('queueKeyAssistToParser',        140); // Assistant to parser
    define('queueKeyParserToAssist',        141); // Parser to EQ assistant
    define('queueKeyAssistToCmd',           142); // Assistant to cmd

    /* LQI collect queues */
    // define('queueKeyParserToLQI',           225);
    define('queueKeyLQIToAbeille',          521);
    define('queueKeyLQIToCmd',              523);

    /* Client page to server or server to client */
    define('queueKeyXmlToAbeille',          621);
    define('queueKeyXmlToCmd',              623);
    define('queueKeyFormToCmd',             723);
    define('queueKeyParserToCli',           724);

    /* Misc */
    define('queueKeyParserToCmd',           223);
    define('queueKeyCmdToCmd',              323);
    define('queueKeySerialToParser',        822);  // 0x336
    define('queueKeyParserToCmdSemaphore',  999);  // Queue pour passer les messages Ack entre parcer et Cmd.
    define('queueKeyCtrlToParser', 0x337); // To pass control message to Parser

    /* New way to declare queues, allowing to define a max message size per queue.
       array['<queueName>'] = array("id" => queueId, "max" => maxMsgSize) */
    $abQueues = array();
    $abQueues["queueSerialToParser"] = array( "id" => 0x336, "max" => 2048 );
    $abQueues["queueCtrlToParser"] = array( "id" => 0x337, "max" => 2048 );
    $abQueues["parserToLQI"] = array( "id" => 225, "max" => 1024 );
    $GLOBALS['abQueues'] = $abQueues;

    define('priorityMin',           1);
    define('priorityUserCmd',       1); // Action utiliateur qui doit avoir une sensation de temps réel
    define('priorityNeWokeUp',      2); // Action si un NE est detecté reveillé et qu'on veut essayer de lui parler
    define('priorityInclusion',     3); // Message important car le temps est compté pour identifier certains équipements
    define('priorityInterrogation', 4); // Message pour recuperer des etats, valeurs
    define('priorityLostNE',        5); // Si le NE est en TimeOut il n'est pas prioritaire car il est peut etre off.
    define('priorityMax',           5); // est egale aux max des priorités définies.

    define('maxNbOfZigate',        10);

    define('maxRetryDefault',       3);

    /* URL to access documentations */
    define('urlProducts', "https://github.com/KiwiHC16/AbeilleDoc/blob/master/docs/products");
    define('urlUserMan', "https://kiwihc16.github.io/AbeilleDoc/");

    /* Developper config file */
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
    define('daemonSerialRead7', 1 << 8);
    define('daemonSerialRead8', 1 << 9);
    define('daemonSerialRead9', 1 << 10);
    define('daemonSerialRead10', 1 << 11);
    define('daemonSocat1', 1 << 12);
    define('daemonSocat2', 1 << 13);
    define('daemonSocat3', 1 << 14);
    define('daemonSocat4', 1 << 15);
    define('daemonSocat5', 1 << 16);
    define('daemonSocat6', 1 << 17);
    define('daemonSocat7', 1 << 18);
    define('daemonSocat8', 1 << 19);
    define('daemonSocat9', 1 << 20);
    define('daemonSocat10', 1 << 21);
    define('daemonMonitor', 1 << 22);
?>
