<?php
    
    define('queueKey', 221);
    
    Class MsgAbeille {
        public $message = array(
            'topic' => 'Coucou',
            'payload' => 'message de P1 pour test',
        );
    }
    
    $queue = msg_get_queue(queueKey);
    
    $msgAbeille = new MsgAbeille;
    
    if (msg_send($queue, 1, $msgAbeille)) {
        echo "added to queue  \n";
        print_r(msg_stat_queue($queue));
    }
    else {
        echo "could not add message to queue \n";
    }
?>
