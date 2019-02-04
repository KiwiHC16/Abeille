<?php
    // Pour mettre a jour la doc github:
    // php listeCompatibilite.php 1 > ../../../Documentation/040_Compatibilite.adoc

    
    function equipementHtml( $resultIcone, $resultRaw, $result) {
    //---------------------------------------------------------------------
    echo "<h1>Equipments</h1>";

    echo "<table>";
    sort( $resultIcone );
    foreach ( $resultIcone as $values ) echo '<tr><td>'.$values['name'].'</td><td><img src="/plugins/Abeille/images/node_'.$values['icone'].'.png" width="100" height="100"></td></tr>'."\n";
    echo "</table>";
        
        //---------------------------------------------------------------------
        echo "<h1>Equipements et fonctions associées</h1>";
        echo "<table>\n";
        echo "<tr><td>Nom</td><td>Nom Zigbee</td><td>Fonction</td></tr>\n";
        
        sort( $result );
        foreach ( $result as $line ) echo $line."\n";
        echo "</table>\n";
        
        //---------------------------------------------------------------------
        echo "<h1>Fonctions utilisées</h1>";
        $includeList = array_column( $resultRaw, 'fonction');
        $includeList = array_unique($includeList);
        sort( $includeList );
        foreach ( $includeList as $value ) echo $value."<br>\n";
    }
    
    function equipementAdoc( $resultIcone, $resultRaw, $result) {
    // asciidoc version
    echo '[cols="a"]'."\n";
    echo "|======="."\n";;
    foreach ( $resultIcone as $values ) echo '|'.$values['name'].'|image::../images/node_'.$values['icone'].'.png[200,200]'."\n";
    echo "|======="."\n";;
    }
    
    // Recupere les info.
    foreach ( glob( '*/*.json') as $file ) {
        if ( explode('/',$file)[0] == "Template" ) {
            continue;
        }
        
        $name = explode('/',$file)[0];
        $contentJSON = file_get_contents( $file );
        $content = JSON_decode( $contentJSON, true );
        $resultIcone[] = array( 'name'=>$content[$name]["nameJeedom"], 'icone'=>$content[$name]["configuration"]["icone"] );
        foreach ( $content[$name]['Commandes'] as $include ) {
            $resultRaw[] = array( 'name'=>$content[$name]["nameJeedom"], 'nameZigbee'=>$name, 'fonction'=>$include );
            $result[] = "<tr><td>".$content[$name]["nameJeedom"]."</td><td>".$name."</td><td>".$include."</td></tr>";
        }
    }
    
    if (isset($argv[1])) {
        equipementAdoc( $resultIcone, $resultRaw, $result);
    } else {
        equipementHtml( $resultIcone, $resultRaw, $result);
    }
    
    ?>
