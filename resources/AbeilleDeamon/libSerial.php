<?php
    
    
    
    function _exec($cmd, &$out = null)
    {
        $desc = array(
                      1 => array("pipe", "w"),
                      2 => array("pipe", "w")
                      );
        
        $proc = proc_open($cmd, $desc, $pipes);
        
        $ret = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $retVal = proc_close($proc);
        
        if (func_num_args() == 2) {
            $out = array($ret, $err);
        }
        
        return $retVal;
    }
    
    
    
    ?>

