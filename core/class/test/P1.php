<?php
    
    define('queueKeyParserToAbeille',   221);
    define('queueKeyParserToCmd',       223);
    define('queueKeyAbeilleToCmd',      123);
    define('queueKeyCmdToCmd',          323);
    define('queueKeyCmdToAbeille',      321);
    
    Class MsgAbeille {
        public $message = array(
            'topic' => 'Abeille/Ruche/Network-Channel',
            'payload' => '09',
        );
    }
    
    $queue = msg_get_queue(queueKeyAbeilleToCmd);
    
    $msgAbeille = new MsgAbeille;
    
    if (msg_send($queue, 1, $msgAbeille)) {
        echo "added to queue  \n";
        print_r(msg_stat_queue($queue));
    }
    else {
        echo "could not add message to queue \n";
    }
?>
