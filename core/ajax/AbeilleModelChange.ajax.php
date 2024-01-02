<?php

/* This file is part of Plugin abeille for jeedom.
*
* Plugin abeille for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin abeille for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin abeille for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * This file manages the change of equipment model at the user's initiative.
 * - Display existing JSON models
 * - Saves user choice
 */

require_once __DIR__ . '/../config/Abeille.config.php'; // dbgFile constant + queues
if (file_exists(dbgFile)) {
    /* Dev mode: enabling PHP errors logging */
    error_reporting(E_ALL);
    ini_set('error_log', __DIR__ . '/../../../../log/AbeillePHP.log');
    ini_set('log_errors', 'On');
}

// Authentication verification
require_once __DIR__ . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}

require_once __DIR__ . '/../php/AbeilleModels.php'; // Libray to deal with models

// Perform the requested action
$action = $_POST['action'];
if (function_exists($action)) {
    $action();
} else {
    die("L'action demandée n'existe pas");
}

/**
 * Returns in JSON the list of existing models (known by Abeille).
 * Includes built-in models and local models (if any).
 */
function getModelChoiceList() {
    // We allow the choice of local models and official models
    // Associative array modelID => model data
    $list = getModelsList("local");
    $list = array_merge($list, getModelsList("Abeille"));

    // Retrieve the current model of the equipment (if it has one)
    $eqId = (int) $_POST['eqId'];
    $eqLogic = eqLogic::byId($eqId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__) . ' ' . $eqId);
    }
    $currentModel = $eqLogic->getConfiguration('ab::eqModel', null);
    $currentModelID = null;
    if ($currentModel != null && isset($currentModel['modelName'])) {
        $currentModelID = $currentModel['modelName'];
    }

    // If we have a current model, we can pre-fill the HMI: we pass the information
    if ($currentModelID != null && isset($list[$currentModelID])) {
        $list[$currentModelID]['isCurrent'] = true;
    }

    // Send the list to browser
    die(json_encode($list));
}

/**
 * Saves the user's choice: changes the equipment model, and
 * forces it to 'manual' so that the model is no longer modified automatically
 * from zigbee signature at equipment announcement.
 */
function setModelToDevice() {
    // Retrieving equipment
    $eqId = (int) $_POST['eqId'];
    $eqLogic = eqLogic::byId($eqId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__) . ' ' . $eqId);
    }

    // Retrieving chose model, under the form "blabla (Abeille/id.json)" or "blabla (local/id.json)"
    $strSaisie = $_POST['modelChoice'];

    // Extracting json pathname
    $tmpPos = strrpos($strSaisie, ' (');
    if ($tmpPos === false) {
        throw new Exception(__('Saisie incorrecte', __FILE__) . ' ' . $eqId);
    }

    $tmpModelPath = mb_substr($strSaisie, $tmpPos + 2); // like "Abeille/id.json)"
    $tmpModelPath = mb_substr($tmpModelPath, 0, mb_strlen($tmpModelPath) - 1); // like "Abeille/id.json"

    $tmpNomModel = explode("/", $tmpModelPath);
    if (!is_array($tmpNomModel) || sizeof($tmpNomModel) != 2) {
        throw new Exception(__('Saisie incorrecte', __FILE__) . ' ' . $eqId);
    }
    $source = $tmpNomModel[0]; // Abeille or local
    $json = pathinfo(basename($tmpNomModel[1]), PATHINFO_FILENAME); // drops .json

    $modelPath = '';
    if ($source == 'Abeille') {
        $modelPath = devicesDir . $json . '/' . $json . '.json';
    } else {
        $modelPath = devicesLocalDir . $json . '/' . $json . '.json';
        $source = 'local'; // should already be ok
    }

    // We check that the model file exists (ultimate control) and we load it
    if (!is_readable($modelPath)) {
        throw new Exception(__('Saisie incorrecte - Fichier modèle introuvable', __FILE__) . ' ' . $eqId);
    }
    $jsonModelData = json_decode(file_get_contents($modelPath), true);
    if (!isset($jsonModelData[$json]['type'])) {
        // Weird, but there may be some old json incorrectly filled in
        $libelleType = '';
    } else {
        $libelleType = $jsonModelData[$json]['type'];
    }

    // Save new config
    $eqModelInfos = array(
        'modelName' => $json, // ID du json
        'modelSource' => $source, // Abeille ou local
        'modelForced' => true, // Prevents the model from being overwritten if the equipment is re-announced
        'type' => $libelleType,
        'lastUpdate' => time(), // Store last update from model
    );
    $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

    $eqLogic->save();

    message::add("Abeille", date('d/m/Y H:i:s') . " > Modèle enregistré. L'équipement va maintenant être reconfiguré...", '');
    die("true");

    // (The HMI will send a second ajax request to reconfigure the equipment from the new model)
}

/**
 * Restores normal (automatic) model selection for equipment.
 * (= the model can be detected again by Abeille at the next announcement of the equipment)
 */
function disableManualModelForDevice() {
    // Retrieving equipment
    $eqId = (int) $_POST['eqId'];
    $eqLogic = eqLogic::byId($eqId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__) . ' ' . $eqId);
    }

    // Restores automatic model selection (without actually triggering it for now)
    $currentModel = $eqLogic->getConfiguration('ab::eqModel', null);
    if ($currentModel == null || ! isset($currentModel['id'])) {
        // Should never happen
        throw new Exception(__('ab::eqModel non défini. Essayez de ré-inclure l\'équipement.', __FILE__) . ' ' . $eqId);
    }
    $currentModel['modelForced'] = false;
    $eqLogic->setConfiguration('ab::eqModel', $currentModel);
    $eqLogic->save();

    message::add("Abeille", date('d/m/Y H:i:s') . " > Choix automatique du modèle réactivé pour cet équipement. Vous pouvez maintenant le Mettre à jour pour le re-configurer.", '');


    die("true");
}


/** Code from .tools\gen_devices_list.php  */
// TODO refactor to avoid code duplication

/* Get list of supported devices ($from="Abeille"), or user/custom ones ($from="local")
        Returns: Associative array; $devicesList[$identifier] = array(), or false if error */
// function getModelsList($from = "Abeille") {
//     $devicesList = [];

//     if ($from == "Abeille")
//         $rootDir = modelsDir;
//     else if ($from == "local")
//         $rootDir = modelsLocalDir;
//     else {
//         echo ("ERROR: Emplacement JSON '" . $from . "' invalide\n");
//         return false;
//     }

//     $dh = opendir($rootDir);
//     if ($dh === false) {
//         echo ('ERROR: getModelsList(): opendir(' . $rootDir . ')\n');
//         return false;
//     }
//     while (($dirEntry = readdir($dh)) !== false) {
//         /* Ignoring some entries */
//         if (in_array($dirEntry, array(".", "..")))
//             continue;
//         $fullPath = $rootDir . $dirEntry;
//         if (!is_dir($fullPath))
//             continue;

//         $fullPath = $rootDir . $dirEntry . '/' . $dirEntry . ".json";
//         if (!file_exists($fullPath))
//             continue; // No local JSON model. Maybe just an auto-discovery ?

//         $dev = array(
//             'modelName' => $dirEntry,
//             'modelSource' => $from
//         );

//         /* Check if config is compliant with other device identification */
//         $content = file_get_contents($fullPath);
//         $devConf = json_decode($content, true);
//         $devConf = $devConf[$dirEntry]; // Removing top key
//         $dev['manufacturer'] = isset($devConf['manufacturer']) ? $devConf['manufacturer'] : '';
//         $dev['model'] = isset($devConf['model']) ? $devConf['model'] : '';
//         $dev['type'] = $devConf['type'];
//         $dev['icon'] = $devConf['configuration']['icon'];
//         $devicesList[$dirEntry] = $dev;

//         if (isset($devConf['alternateIds'])) {
//             $idList = explode(',', $devConf['alternateIds']);
//             foreach ($idList as $id) {
//                 echo ("getModelsList(): Alternate ID '" . $id . "' for '" . $dirEntry . "'\n");
//                 $dev = array(
//                     'modelName' => $dirEntry,
//                     'modelSource' => $from
//                 );
//                 $devicesList[$id] = $dev;
//             }
//         }
//     }
//     closedir($dh);

//     return $devicesList;
// }
