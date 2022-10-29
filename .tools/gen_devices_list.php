<?php
    /* To update AbeilleDoc's compatibility list:
       - php .tools/gen_devices_list
       - then copy 'CompatibilityList.rst' to AbeilleDoc GIT repo
       - copy/update images/node_xx to AbeilleDoc source/devices/images
       - renegerate doc with './gen_docs.sh'
       - and finally create your Pull Request on GitHub
     */

    function addToFile($fileName, $text, $append = true) {
        if ($append)
            file_put_contents($fileName, $text, FILE_APPEND);
        else
            file_put_contents($fileName, $text);
    }

    /* Generate list in "Restructured" format for AbeilleDoc update */
    function genRst($eqList) {

        define('rstFile', 'CompatibilityList.rst');

        echo "***\n";
        echo "*** Generating equipments list to '".rstFile."'\n";
        echo "***\n\n";

        addToFile(rstFile, "Liste des équipements compatibles\n", false);
        addToFile(rstFile, "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n");
        addToFile(rstFile, "\n");
        addToFile(rstFile, "Dernière mise-à-jour le ".date('Y-m-d')."\n\n");

        foreach ( $eqList as $eq ) {
            echo "- ".$eq['jsonId']."\n";
            if (isset($eq['manufacturer']))
                echo "  manuf : ".$eq['manufacturer']."\n";
            if (isset($eq['model']))
                echo "  model : ".$eq['model']."\n";
            echo "  name  : ".$eq['type']."\n";

            addToFile(rstFile, $eq['manufacturer'].", ".$eq['model'].", ".$eq['type']."\n\n");
            addToFile(rstFile, ".. image:: ../images/node_".$eq['icon'].".png\n");
            addToFile(rstFile, "   :width: 200px\n\n");
        }
    }

    //----------------------------------------------------------------------------------------------------
    // Main
    //----------------------------------------------------------------------------------------------------

    define('devicesDir', __DIR__.'/../core/config/devices/'); // Abeille's supported devices
    define('devicesLocalDir', __DIR__.'/../core/config/devices_local/'); // Unsupported/user devices

    /* Get list of supported devices ($from="Abeille"), or user/custom ones ($from="local")
        Returns: Associative array; $devicesList[$identifier] = array(), or false if error */
    function getDevicesList($from = "Abeille") {
        $devicesList = [];

        if ($from == "Abeille")
            $rootDir = devicesDir;
        else if ($from == "local")
            $rootDir = devicesLocalDir;
        else {
            echo("ERROR: Emplacement JSON '".$from."' invalide\n");
            return false;
        }

        $dh = opendir($rootDir);
        if ($dh === false) {
            echo('ERROR: getDevicesList(): opendir('.$rootDir.')\n');
            return false;
        }
        while (($dirEntry = readdir($dh)) !== false) {
            /* Ignoring some entries */
            if (in_array($dirEntry, array(".", "..")))
                continue;
            $fullPath = $rootDir.$dirEntry;
            if (!is_dir($fullPath))
                continue;

            $fullPath = $rootDir.$dirEntry.'/'.$dirEntry.".json";
            if (!file_exists($fullPath))
                continue; // No local JSON model. Maybe just an auto-discovery ?

            $dev = array(
                'jsonId' => $dirEntry,
                'jsonLocation' => $from
            );

            /* Check if config is compliant with other device identification */
            $content = file_get_contents($fullPath);
            $devConf = json_decode($content, true);
            $devConf = $devConf[$dirEntry]; // Removing top key
            $dev['manufacturer'] = isset($devConf['manufacturer']) ? $devConf['manufacturer'] : '';
            $dev['model'] = isset($devConf['model']) ? $devConf['model']: '';
            $dev['type'] = $devConf['type'];
            $dev['icon'] = $devConf['configuration']['icon'];
            $devicesList[$dirEntry] = $dev;

            if (isset($devConf['alternateIds'])) {
                $idList = explode(',', $devConf['alternateIds']);
                foreach ($idList as $id) {
                    echo("getDevicesList(): Alternate ID '".$id."' for '".$dirEntry."'\n");
                    $dev = array(
                        'jsonId' => $dirEntry,
                        'jsonLocation' => $from
                    );
                    $devicesList[$id] = $dev;
                }
            }
        }
        closedir($dh);

        return $devicesList;
    }

    // Collecting list of supported devices (by their JSON)
    $devList = getDevicesList('Abeille');
    foreach ($devList as $jsonId => $dev) {

        $eqList[] = array(
            'manufacturer' => $dev["manufacturer"],
            'model' => $dev["model"],
            'type' => $dev["type"],
            'jsonId' => $jsonId,
            'icon' => $dev["icon"]
        );

        // Collect all information related to Command used by the products
        // $path = __DIR__.'/../../core/config/devices/'.$jsonId.'/'.$jsonId.'.json';
        // $contentJSON = file_get_contents($path);
        // $content = json_decode($contentJSON, true);
        // if (isset($content[$jsonId]['commands'])) {
        //     $commands = $content[$jsonId]['commands'];
        //     foreach ($commands as $include) {
        //         $resultRaw[] = array(
        //             'jsonId' => $jsonId,
        //             'type' => $content[$jsonId]["type"],
        //             'fonction' => $include
        //         );
        //         $result[] = "<tr><td>".$content[$jsonId]["type"]."</td><td>".$jsonId."</td><td>".$include."</td></tr>";
        //     }
        // }
    }

    genRst($eqList);
?>
