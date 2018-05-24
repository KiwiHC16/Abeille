<?php
    if ( strlen($argv[1]) == 0 ) {
        echo "usage: php sortCommand.php file.json\n";
        exit;
    }

    $handle = fopen($argv[1], "r");
    
    $order = 0;
    
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            // process the line read.
            if ( strpos( $line, "order" ) > 0) {
                echo '        "order": '.$order++.",\n";
            }
            else {
                echo $line;
            }
        }
        
        fclose($handle);
    } else {
        // error opening the file.
        echo "Can t open the file.\n";
    }
    
    echo "\n";
?>