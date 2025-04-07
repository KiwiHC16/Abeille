<?php
    // Supported devices list generation is now done by '.tools/gen_devices_list.php'

     /* Developers debug features & PHP errors */
     require_once __DIR__.'/../../core/config/Abeille.config.php';
     if (file_exists(dbgFile)) {
         $dbgDeveloperMode = true;

         /* Dev mode: enabling PHP errors logging */
         error_reporting(E_ALL);
         ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
         ini_set('log_errors', 'On');
    }

    function genHtml($eqList, $resultRaw = [], $result = []) {

        sort($eqList);

        echo "<h1>{{Equipements supportés}}</h1>";
        echo "Les docs stockées de chaque équipement sont accessibles via un clic sur le champ 'ID Zigbee'. Si pas de doc, vous tomberez sur une page du type 'erreur 404'<br><br>";
        echo '<table class="tablesorter" id="idEqTable">';
        // echo "<caption>Equipements supportés</caption>";
        echo '<thead><tr>';
        echo '<th class="header">Icone</th>';
        echo '<th class="header" width="100px" title="Trier par fabricant">Fabricant</th>';
        echo '<th class="header" width="100px" title="Trier par modèle">Modèle</th>';
        echo '<th class="header" width="100px" title="Trier par type">Type</th>';
        echo '<th class="header" title="Trier par ID Zigbee">ID Zigbee</th>';
        echo '</tr></thead>';
        foreach ($eqList as $eq) {
            echo '<tr>';
            echo '<td><img src="/plugins/Abeille/core/config/devices_images/node_'.$eq['icon'].'.png" width="100" height="100"></td>';
            echo '<td>'.$eq['manufacturer'].'</td>';
            echo '<td>'.$eq['model'].'</td>';
            echo '<td>'.$eq['type'].'</td>';
            echo '<td><a href="'.urlProducts.'/'.$eq['modelSig'].'">'.$eq['modelSig'].'</a></td>';
            echo '</tr>'."\n";
        }
        echo "</table>";

        //---------------------------------------------------------------------
        // echo "<h1>{{Equipements et fonctions associées}}</h1>";
        // echo "<table>\n";
        // echo "<tr><td>{{Nom}}</td><td>{{Nom Zigbee}}</td><td>{{Fonction}}</td></tr>\n";

        // sort( $result );
        // foreach ( $result as $line ) echo $line."\n";
        // echo "</table>\n";

        //---------------------------------------------------------------------
        // echo "<h1>{{Fonctions utilisées}}</h1>";
        // $includeList = array_column($resultRaw, 'fonction');
        // $includeList = array_unique($includeList);
        // sort( $includeList );
        // foreach ( $includeList as $value ) echo $value."<br>\n";
    }

    function equipementAdoc( $eqList, $resultRaw, $result) {
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
        foreach ( $eqList as $values ) echo '| '.$values['type'].'| image:node_'.$values['icon'].'.png[height=200,width=200]'."\n";
        echo "|======="."\n";;
    }

    function addToFile($fileName, $text, $append = true) {
        if ($append)
            file_put_contents($fileName, $text, FILE_APPEND);
        else
            file_put_contents($fileName, $text);
    }

    // /* Generate list in "Restructured" format for AbeilleDoc update */
    // function genRst($eqList) {

    //     define('rstFile', 'CompatibilityList.rst');

    //     echo "***\n";
    //     echo "*** Generating equipments list to '".rstFile."'\n";
    //     echo "***\n\n";

    //     addToFile(rstFile, "Liste des équipements compatibles\n", false);
    //     addToFile(rstFile, "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n");
    //     addToFile(rstFile, "\n");
    //     addToFile(rstFile, "Dernière mise-à-jour le ".date('Y-m-d')."\n\n");

    //     foreach ( $eqList as $eq ) {
    //         echo "- ".$eq['modelName']."\n";
    //         if (isset($eq['manufacturer']))
    //             echo "  manuf : ".$eq['manufacturer']."\n";
    //         if (isset($eq['model']))
    //             echo "  model : ".$eq['model']."\n";
    //         echo "  name  : ".$eq['type']."\n";

    //         addToFile(rstFile, $eq['manufacturer'].", ".$eq['model'].", ".$eq['type']."\n\n");
    //         addToFile(rstFile, ".. image:: images/node_".$eq['icon'].".png\n");
    //         addToFile(rstFile, "   :width: 200px\n\n");
    //     }
    // }

    //----------------------------------------------------------------------------------------------------
    // Main
    //----------------------------------------------------------------------------------------------------

    require_once __DIR__.'/../../core/config/Abeille.config.php';
    // require_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    require_once __DIR__.'/../../core/php/AbeilleModels.php';

    // Collecting list of supported devices (by their JSON)
    // $modelsList = AbeilleTools::getDevicesList('Abeille');
    $modelsList = getModelsList('Abeille');
    // logDebug("modelsList=".json_encode($modelsList));
    $eqList = [];
    $resultRaw = [];
    $result = [];
    foreach ($modelsList as $modelSigV => $model) {
        // logDebug("LA model=".json_encode($model));

        $modelName = $model['modelName'];
        // $path = __DIR__.'/../../core/config/devices/'.$modelName.'/'.$modelName.'.json';
        // if (!file_exists($path)) {
        //     logDebug("ERROR: Path does not exists: ${path}");
        //     continue;
        // }
        // $contentJSON = file_get_contents($path);
        // $content = json_decode($contentJSON, true);
        // $content = $content[$modelName]; // Skip top 'modelName' key
        // logDebug("LA2 content=".json_encode($content));

        $eqList[] = array(
            'modelSig' => $model['modelSig'],
            'modelName' => $model['modelName'],
            'manufacturer' => $model["manufacturer"],
            'model' => $model["model"],
            'type' => $model["type"],
            'icon' => $model["icon"],
        );

        // Collect all information related to Command used by the products
        if (isset($model['modelPath']))
            $path = __DIR__.'/../../core/config/devices/'.$model['modelPath'];
        else
            $path = __DIR__.'/../../core/config/devices/'.$modelName.'/'.$modelName.'.json';
        $contentJSON = file_get_contents($path);
        $content = json_decode($contentJSON, true);
        $content = $content[$modelName]; // Skip top 'modelName' key
        if (isset($content['commands'])) {
            $commands = $content['commands'];
            // logDebug("LA commands=".json_encode($commands));
            foreach ($commands as $cmdName => $cmd) {
                $resultRaw[] = array(
                    'modelName' => $modelName,
                    'type' => $content["type"],
                    'fonction' => $cmdName
                );
                $result[] = "<tr><td>".$content["type"]."</td><td>".$modelName."</td><td>".$cmdName."</td></tr>";
            }
        }
    }
    // logDebug("eqList=".json_encode($eqList));

    // Met en forme.
    if (isset($argv[1])) {
        if ( $argv[1] == "adoc" ) {
            equipementAdoc($eqList, $resultRaw, $result);
        }
        // if ( $argv[1] == "rst" ) {
        //     genRst($eqList);
        // }
    } else {
        genHtml($eqList, $resultRaw, $result);
    }
?>

<script>
    $("#idEqTable").tablesorter();
</script>
