<?php
    echo "<h1>Equipements</h1>";
    echo "<table>\n";
    echo "<tr><td>Nom</td><td>Nom Zigbee</td><td>Fonction</td></tr>\n";
    foreach ( glob( '*/*.json') as $file ) {
        if ( explode('/',$file)[0] == "Template" ) {
            continue;
        }
        
        $name = explode('/',$file)[0];
        $contentJSON = file_get_contents( $file );
        $content = JSON_decode( $contentJSON, true );
        foreach ( $content[$name]['Commandes'] as $include ) {
            $resultRaw[] = array( 'name'=>$content[$name]["nameJeedom"], 'nameZigbee'=>$name, 'fonction'=>$include );
            $result[] = "<tr><td>".$content[$name]["nameJeedom"]."</td><td>".$name."</td><td>".$include."</td></tr>";
        }
    }
    sort( $result );
    foreach ( $result as $line ) echo $line."\n";
    echo "</table>\n";
 
    echo "<h1>Fonctions utilisées</h1>";
    $includeList = array_column( $resultRaw, 'fonction');
    $includeList = array_unique($includeList);
    sort( $includeList );
    // var_dump( $includeList );
   //
    foreach ( $includeList as $value ) echo $value."<br>\n";
    
    
    
    ?>
