<?php

    // function found at https://secure.php.net/manual/fr/function.parse-str.php
    // which translate a X=x&Y=y&Z=z en array X=>x Y=>y Z=>z
    function proper_parse_str($str) {
        # result array
        $arr = array();
        
        # split on outer delimiter
        $pairs = explode('&', $str);
        
        # loop through each pair
        foreach ($pairs as $i) {
            # split into name and value
            list($name,$value) = explode('=', $i, 2);
            
            # if name already exists
            if( isset($arr[$name]) ) {
                # stick multiple values into an array
                if( is_array($arr[$name]) ) {
                    $arr[$name][] = $value;
                }
                else {
                    $arr[$name] = array($arr[$name], $value);
                }
            }
            # otherwise, simply stick it in a scalar
            else {
                $arr[$name] = $value;
            }
        }
        
        # return result array
        return $arr;
    }

    // Inverse l ordre des des octets.
    function reverse_hex( $a ) {
        $reverse = "";
        
        for ($i = strlen($a)-2; $i >= 0; $i-=2) {
            // echo $i . " -> " . $a[$i] . $a[$i+1] . "\n";
            
            $reverse .= $a[$i].$a[$i+1];
            
            
        }
        return $reverse;
    }
?>
