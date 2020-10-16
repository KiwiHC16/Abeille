<?php
    // Pour mettre a jour la doc github:
    // php listeCompatibilite.php rst

    function equipementHtml( $resultIcone, $resultRaw, $result) {

        sort( $resultIcone );
        
        echo "<h1>{{Equipments}}</h1>";
        echo "<table>";
        echo "<th>Nom Zigbee</th><th>Nom Generique</th><th>Icone</th>";
        foreach ( $resultIcone as $values ) echo '<tr><td>'.$values['nameZigbee'].'</td><td>'.$values['nameDescription'].'</td><td><img src="/plugins/Abeille/images/node_'.$values['icone'].'.png" width="100" height="100"></td></tr>'."\n";
        echo "</table>";

        //---------------------------------------------------------------------
        echo "<h1>{{Equipements et fonctions associées}}</h1>";
        echo "<table>\n";
        echo "<tr><td>{{Nom}}</td><td>{{Nom Zigbee}}</td><td>{{Fonction}}</td></tr>\n";

        sort( $result );
        foreach ( $result as $line ) echo $line."\n";
        echo "</table>\n";

        //---------------------------------------------------------------------
        echo "<h1>{{Fonctions utilisées}}</h1>";
        $includeList = array_column( $resultRaw, 'fonction');
        $includeList = array_unique($includeList);
        sort( $includeList );
        foreach ( $includeList as $value ) echo $value."<br>\n";
    }

    function equipementAdoc( $resultIcone, $resultRaw, $result) {
        echo '= Compatibility'."\n";
        echo 'KiwiHC16'."\n";
        echo ':toc2:'."\n";
        echo ':toclevels: 4'."\n";
        echo ':toc-title: Table des matières'."\n";
        echo ':imagesdir: ../../images'."\n";
        echo ':iconsdir: ../images/icons'."\n";
        echo "\n";
        echo '== Home'."\n";
        echo ''."\n";
        echo 'Retour au link:index.html[document principal].'."\n";
        echo ''."\n";
        echo '== Liste'."\n";
        echo ''."\n";

        echo '[cols="<,^"]'."\n";
        echo "|======="."\n";;
        foreach ( $resultIcone as $values ) echo '| '.$values['name'].'| image:node_'.$values['icone'].'.png[height=200,width=200]'."\n";
        echo "|======="."\n";;
    }

    function equipementRst( $resultIcone, $resultRaw, $result) {

        echo "*********************************\n";
        echo "Liste des équipements compatibles\n";
        echo "*********************************\n";
        echo "\n\n\n";

        foreach ( $resultIcone as $values ) echo "\n\n.. image:: imagesDevices/node_".$values['icone'].".png\n   :width: 200px\n\n".$values['name']."\n";

    }

    //----------------------------------------------------------------------------------------------------
    // Main
    //----------------------------------------------------------------------------------------------------

    // Recupere les info.
    foreach ( glob( '/var/www/html/plugins/Abeille/core/config/devices/*/*.json') as $file ) {
        // echo $file."\n";
        // echo basename(dirname($file));
        if ( basename(dirname($file)) == "Template" ) {
            continue;
        }

        $name = basename($file, ".json");
        // echo $name."\n";
        $contentJSON = file_get_contents( $file );
        $content = JSON_decode( $contentJSON, true );
        $resultIcone[] = array( 'nameZigbee'=>$name, 'nameDescription'=>$content[$name]["nameJeedom"], 'icone'=>$content[$name]["configuration"]["icone"] );

        /*
        echo "File:\n";
        var_dump( $file );
        echo "Content:\n";
        var_dump( $content );
        echo "resultIcone:\n";
        var_dump( $resultIcone );
        */

        foreach ( $content[$name]['Commandes'] as $include ) {
            $resultRaw[] = array( 'nameZigbee'=>$name, 'nameDescription'=>$content[$name]["nameJeedom"], 'fonction'=>$include );
            $result[] = "<tr><td>".$content[$name]["nameJeedom"]."</td><td>".$name."</td><td>".$include."</td></tr>";
        }
    }

    // Met en forme.
    if (isset($argv[1])) {
        if ( $argv[1] == "adoc" ) {
            equipementAdoc( $resultIcone, $resultRaw, $result);
        }
        if ( $argv[1] == "rst" ) {
            equipementRst( $resultIcone, $resultRaw, $result);
        }
    } else {
        equipementHtml( $resultIcone, $resultRaw, $result);
    }

    ?>
