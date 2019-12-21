<?php
    if ( strlen($argv[1]) == 0 ) {
        echo "usage: php sortCommand.php file.json\n";
        echo "Commande are ordered by order of appearance in the file\n";
        echo "JSON file should have one parametre/value per row.\n";
        echo 'Pour faire tous les fichier: find . -name "[!.]*.json" -print -exec bash -c "eval php sortCommand.php {} > {}.new" \;'."\n";
        echo 'Pour remplacer les anciens par les nouveaux:find . -name "[!.]*.json" -print -exec mv {}.new {} \;'."\n";
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
    

?>
