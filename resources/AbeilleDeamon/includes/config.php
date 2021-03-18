<?php
    $in = "/tmp/AbeilleDeamonInput";
    $resourcePath = realpath(dirname(__FILE__).'/../../');
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

    define('queueKeyParserToAbeille',       221);
    define('queueKeyParserToCmd',           223);
    define('queueKeyParserToLQI',           225);
    define('queueKeyCmdToAbeille',          321);
    define('queueKeyCmdToCmd',              323);

    define('queueKeyLQIToAbeille',          521);
    define('queueKeyLQIToCmd',              523);
    define('queueKeyXmlToAbeille',          621);
    define('queueKeyXmlToCmd',              623);
    define('queueKeyFormToCmd',             723);
    define('queueKeySerieToParser',         822);  // 0x336 TO BE REMOVED when queueKeySerialToParser is the only used
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
?>
