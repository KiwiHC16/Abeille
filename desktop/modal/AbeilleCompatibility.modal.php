<?php
    // Pour mettre a jour la doc github:
    // php listeCompatibilite.php rst

    function equipementHtml($resultIcone, $resultRaw, $result) {

        sort( $resultIcone );

        echo "<h1>{{Equipements supportés}}</h1>";
        echo "Les docs stockées de chaque équipement sont accessibles via un clic sur le champ 'ID Zigbee'. Si pas de doc, vous tomberez sur une page du type 'erreur 404'<br><br>";
        echo "<table>";
        // echo "<caption>Equipements supportés</caption>";
        echo '<tr><th width="100px">Fabricant</th><th width="100px">Modèle</th><th>Nom</th><th>ID Zigbee</th><th>Icone</th></tr>';
        foreach ( $resultIcone as $values ) {
            echo '<tr>';
            echo '<td>'.$values['manufacturer'].'</td>';
            echo '<td>'.$values['model'].'</td>';
            echo '<td>'.$values['nameDescription'].'</td>';
            echo '<td>'.$values['zigbeeModelId'].'</td>';
            echo '<td><img src="/plugins/Abeille/images/node_'.$values['icone'].'.png" width="100" height="100"></td>';
            echo '</tr>'."\n";
        }
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

    require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';

    // Recupere les info.
    foreach (glob('/var/www/html/plugins/Abeille/core/config/devices/*/*.json') as $file) {

        if ( basename(dirname($file)) == "Template" ) {
            continue;
        }

        // Collect all core info of the product
        $name = basename($file, ".json");
        $contentJSON = file_get_contents( $file );
        $content = JSON_decode( $contentJSON, true );

        $resultIcone[] = array(
            'manufacturer' => $content[$name]["manufacturer"],
            'model' => $content[$name]["model"],
            'zigbeeModelId' => '<a href="'.urlProducts.'/'.$name.'">'.$name.'</a>',
            'nameDescription' => $content[$name]["nameJeedom"],
            'icone'=>$content[$name]["configuration"]["icone"]
        );

        // Collect all information related to Command used by the products
        foreach ( $content[$name]['Commandes'] as $include ) {
            $resultRaw[] = array( 'zigbeeModelId'=>$name, 'nameDescription'=>$content[$name]["nameJeedom"], 'fonction'=>$include );
            $result[] = "<tr><td>".$content[$name]["nameJeedom"]."</td><td>".$name."</td><td>".$include."</td></tr>";
        }
    }

    // Met en forme.
    if (isset($argv[1])) {
        if ( $argv[1] == "adoc" ) {
            equipementAdoc($resultIcone, $resultRaw, $result);
        }
        if ( $argv[1] == "rst" ) {
            equipementRst($resultIcone, $resultRaw, $result);
        }
    } else {
        equipementHtml($resultIcone, $resultRaw, $result);
    }
?>
