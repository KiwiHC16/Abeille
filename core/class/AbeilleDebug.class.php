<?php
    include_once __DIR__.'/../../core/class/AbeilleTools.class.php';

    class AbeilleDebug extends AbeilleTools {
        public $debug = array(
            "cli"                    => 0, // commande line mode or jeedom
            "Checksum"                => 0, // Debug checksum calculation
            "tempo"                   => 0, // Debug tempo queue
            "prepareCmd"                 => 1, // Debug fct procmsg
            "prepareCmd1"                => 0, // Debug fct procmsg avec un seul msg
            "prepareCmd2"                => 0, // Debug fct procmsg avec un seul msg
            "prepareCmd3"                => 0, // Debug fct procmsg avec un seul msg
            "processCmd"              => 1, // Debug fct processCmd
            "sendCmd"                 => 1, // Debug fct sendCmd
            "sendCmd2"                => 0, // Debug fct sendCmd
            "cmdQueue"                => 0, // Debug cmdQueue
            "sendCmdAck"              => 1, // Debug fct sendCmdAck
            "transcode"               => 0, // Debug transcode fct
            "AbeilleCmdClass"         => 1,
            "sendCmdToZigate"         => 1,
            "addCmdToQueue2"         => 1,
            "processAcks"       => 0,
            "processCmdQueues" => 1,
        );

        function deamonlog($loglevel = 'NONE', $message = "", $isEnable = 1)
        {
            if ($isEnable == 0) {
                return;
            }
            if ($this->debug["cli"]) {
                echo "[".date("Y-m-d H:i:s").'][AbeilleCmd][DEBUG.KIWI] '.$message."\n";
            } else {
                logMessage($loglevel, $message);
            }
        }
    }
?>
