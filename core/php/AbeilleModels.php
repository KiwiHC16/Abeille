<?php
    /* Generic library to deal with models */

    require_once __DIR__ . '/../config/Abeille.config.php'; // dbgFile constant + queues
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__ . '/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__.'/../php/AbeilleLog.php'; // logMessage(), logDebug()

    /* Get list of supported devices ($from="Abeille"), or user/custom ones ($from="local")
    Returns: false if error
        or associative array $modelsList[$modelName] = array(
            'modelSig' => model signature
            'modelName' => model file name WITHOUT '.json' extension
            'modelSource' => model file location ('Abeille' or 'local')
            'modelPath' => OPTIONAL: Path relative to 'modelSource' (default='<modelName>/<modelName>.json')
            'manufacturer' => equipment manufacturer
            'model' => equipment model
            'type' => equipment short description
            'icon' => equipment associated icon
        ) */
    function getModelsList($from = "Abeille") {
        $devicesList = [];

        if ($from == "Abeille")
            $rootDir = modelsDir;
        else if ($from == "local")
            $rootDir = modelsLocalDir;
        else {
            log::add('Abeille', 'error', "getModelsList(): Emplacement JSON '".$from."' invalide");
            return false;
        }

        $dh = opendir($rootDir);
        if ($dh === false) {
            log::add('Abeille', 'error', 'getModelsList(): opendir('.$rootDir.')');
            return false;
        }
        while (($dirEntry = readdir($dh)) !== false) {
            /* Ignoring some entries */
            if (in_array($dirEntry, array(".", "..")))
                continue;
            $fullPath = $rootDir.$dirEntry;
            if (!is_dir($fullPath))
                continue;

            /* Supporting multiple variant of a model (rare case)
               <modelName>/<modelName>.json
               <modelName>/<modelName>-<variantX>.json
               <modelName>/<modelName>-<variantY>.json */
            $dh2 = opendir($fullPath);
            $deLen = strlen($dirEntry);
            while (($dirEntry2 = readdir($dh2)) !== false) {
                if (in_array($dirEntry2, array(".", "..")))
                    continue;
                logDebug("dirEntry2='${dirEntry2}'");
                // Model and its optional variants is named '<modelName>[-<variant>].json'
                if (substr($dirEntry2, 0, $deLen) != $dirEntry)
                    continue; // Discovery or other file but not a model

                $dirEntry2 = substr($dirEntry2, 0, -5); // Removing trailing '.json'
                $dev = array(
                    'modelSig' => $dirEntry,
                    'modelName' => $dirEntry2,
                    'modelSource' => $from
                );
                if ($dirEntry2 != $dirEntry)
                    $dev['modelPath'] = $dirEntry.'/'.$dirEntry2.'.json'; // It's a variant

                $fullPath2 = $rootDir.$dirEntry.'/'.$dirEntry2.'.json';
                $content = file_get_contents($fullPath2);
                $devMod = json_decode($content, true);
                $devMod = $devMod[$dirEntry2]; // Removing top key

                $dev['manufacturer'] = isset($devMod['manufacturer']) ? $devMod['manufacturer'] : '';
                $dev['model'] = isset($devMod['model']) ? $devMod['model']: '';
                $dev['type'] = isset($devMod['type']) ? $devMod['type'] : '';
                $dev['icon'] = isset($devMod['configuration']['icon']) ? $devMod['configuration']['icon'] : '';
                $devicesList[$dirEntry2] = $dev;

                /* Any other equipments using the same model ? */
                if (isset($devMod['alternateIds'])) {
                    $ai = $devMod['alternateIds'];
                    /* Reminder:
                        "alternateIds": {
                            "alternateSigX": {
                                "manufacturer": "manufX", // Optional
                                "model": "modelX", // Optional
                                "type": "typeX" // Optional
                                "icon": "iconX" // Optional
                            },
                            "alternateSigY": {},
                            "alternateSigZ": {}
                        } */
                    foreach ($ai as $aId => $aIdVal) {
                        log::add('Abeille', 'debug', "getModelsList(): Alternate ID '".$aId."' for '".$dirEntry2."'");
                        $devA = $dev; // modelName, modelSource & modelPath do not change
                        $devA['modelSig'] = $aId;

                        // manufacturer, model, type or icon can be overload
                        if (isset($aIdVal['manufacturer']))
                            $devA['manufacturer'] = $aIdVal['manufacturer'];
                        if (isset($aIdVal['model']))
                            $devA['model'] = $aIdVal['model'];
                        if (isset($aIdVal['type']))
                            $devA['type'] = $aIdVal['type'];
                        if (isset($aIdVal['icon']))
                            $devA['icon'] = $aIdVal['icon'];
                        $devicesList[$aId] = $devA;
                    }
                }
            }
            closedir($dh2);

            // $fullPath = $rootDir.$dirEntry.'/'.$dirEntry.".json";
            // if (!file_exists($fullPath))
            //     continue; // No model. Maybe just an auto-discovery ?

            // $dev = array(
            //     'modelSig' => $dirEntry,
            //     'modelName' => $dirEntry,
            //     'modelSource' => $from
            // );

            // $content = file_get_contents($fullPath);
            // $devMod = json_decode($content, true); // Device model
            // $devMod = $devMod[$dirEntry]; // Removing top key

            // $dev['manufacturer'] = isset($devMod['manufacturer']) ? $devMod['manufacturer'] : '';
            // $dev['model'] = isset($devMod['model']) ? $devMod['model']: '';
            // $dev['type'] = isset($devMod['type']) ? $devMod['type'] : '';
            // $dev['icon'] = isset($devMod['configuration']['icon']) ? $devMod['configuration']['icon'] : '';
            // $devicesList[$dirEntry] = $dev;

            // /* Any other equipments using the same model ? */
            // if (isset($devMod['alternateIds'])) {
            //     $ai = $devMod['alternateIds'];
            //     /* Reminder:
            //         "alternateIds": {
            //             "alternateSigX": {
            //                 "manufacturer": "manufX", // Optional
            //                 "model": "modelX", // Optional
            //                 "type": "typeX" // Optional
            //                 "icon": "iconX" // Optional
            //             },
            //             "alternateSigY": {},
            //             "alternateSigZ": {}
            //         } */
            //     foreach ($ai as $aId => $aIdVal) {
            //         log::add('Abeille', 'debug', "getModelsList(): Alternate ID '".$aId."' for '".$dirEntry."'");
            //         $devA = $dev; // modelName, modelSource & modelPath do not change
            //         $devA['modelSig'] = $aId;

            //         // manufacturer, model, type or icon can be overload
            //         if (isset($aIdVal['manufacturer']))
            //             $devA['manufacturer'] = $aIdVal['manufacturer'];
            //         if (isset($aIdVal['model']))
            //             $devA['model'] = $aIdVal['model'];
            //         if (isset($aIdVal['type']))
            //             $devA['type'] = $aIdVal['type'];
            //         if (isset($aIdVal['icon']))
            //             $devA['icon'] = $aIdVal['icon'];
            //         $devicesList[$aId] = $devA;
            //     }
            // }
        }
        closedir($dh);

        // Default sorting... by alphabetic order of manufacturers
        // TODO

        return $devicesList;
    } // End getModelsList()
?>
