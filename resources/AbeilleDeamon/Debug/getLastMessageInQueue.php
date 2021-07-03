<?php

define('queueKeySerialToParser',        822);

$queueKeySerialToParser   = msg_get_queue(queueKeySerialToParser);
$max_msg_size = 3072;
$msg_type = NULL;

$err = msg_receive($queueKeySerialToParser, 0, $msg_type, $max_msg_size, $dataJson, false, MSG_IPC_NOWAIT);

var_dump($err);

if ($err) {
    $data = json_decode( $dataJson );
    var_dump($data);
}

?>


