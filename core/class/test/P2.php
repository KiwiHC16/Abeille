<?php

    define('queueKey', 123);
    
    Class MsgAbeille {
        public $message = array(
                                'topic' => 'Coucou',
                                'payload' => 'me voici',
                                );
    }
    
    $queue = msg_get_queue(queueKey);
    
    $msg_type = NULL;
    $msg = NULL;
    $max_msg_size = 512;
    
    while (msg_receive($queue, 0, $msg_type, $max_msg_size, $msg, true)) {
        echo "Message pulled from queue : ".$msg->message['topic']." -> ".$msg->message['payload']." \n";
        var_dump($msg);
        
        $msg_type = NULL;
        $msg = NULL;
    }
?>

