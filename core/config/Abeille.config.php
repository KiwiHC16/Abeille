<?php
    /*
     * Abeille's static config file
     */

    $in = "/tmp/AbeilleDeamonInput";
    $resourcePath = realpath(__DIR__.'/../../resources');
    $WifiLink = "/dev/zigate";

    // Il faut plusieures queues entre les process, on ne peut pas avoir un pot pourri pour tous comme avec Mosquitto.
    // 1: Abeille
    // 2: AbeilleParser -> Parser
    // 3: AbeilleCmd -> Cmd
    // 4:
    // 5: AbeilleLQI -> LQI
    // 6: xmlhttpMQTTSend -> xml
    // 7: queueKeyFormToCmd -> Form
    // 8: serie -> Serie
    // 9: Semaphore entre Parser et MQTTSend

    // 221: means AbeilleParser to(2) Abeille
    define('queueKeyAbeilleToAbeille',      121);
    define('queueKeyAbeilleToCmd',          123);

    /* Monitoring queues */
    define('queueKeyCmdToMon',              130); // Messages to zigate (cmd to monitor)
    define('queueKeyParserToMon',           131); // Messages from zigate (parser to monitor)
    define('queueKeyMonToCmd',              132); // Messages to cmd (addr update)

    /* EQ assistant queues */
    define('queueKeyAssistToParser',        140); // Assistant to parser
    define('queueKeyParserToAssist',        141); // Parser to EQ assistant
    define('queueKeyAssistToCmd',           142); // Assistant to cmd

    /* LQI collect queues */
    define('queueKeyParserToLQI',           225);
    define('queueKeyLQIToAbeille',          521);
    define('queueKeyLQIToCmd',              523);

    define('queueKeyParserToAbeille',       221); // Obsolete path parser to Abeille.
    define('queueKeyParserToAbeille2',      222); // New path parser to Abeille
    define('queueKeyParserToCmd',           223);
    define('queueKeyCmdToAbeille',          321);
    define('queueKeyCmdToCmd',              323);

    define('queueKeyXmlToAbeille',          621);
    define('queueKeyXmlToCmd',              623);
    define('queueKeyFormToCmd',             723);
    define('queueKeySerialToParser',        822);  // 0x336
    define('queueKeyParserToCmdSemaphore',  999);  // Queue pour passer les messages Ack entre parcer et Cmd.

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
