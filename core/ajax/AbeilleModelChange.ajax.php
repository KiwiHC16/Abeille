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
 * Ce fichier gère le changement de modèle d'un équipement à l'initiative de l'utilisateur.
 * - Permet d'afficher des JSON existants
 * - Enregistre le choix de l'utilisateur
 * 
 * @author JB Romain 16/08/2023
 */

require_once __DIR__ . '/../config/Abeille.config.php'; // dbgFile constant + queues
if (file_exists(dbgFile)) {
    /* Dev mode: enabling PHP errors logging */
    error_reporting(E_ALL);
    ini_set('error_log', __DIR__ . '/../../../../log/AbeillePHP.log');
    ini_set('log_errors', 'On');
}

// Vérification d'authentification
require_once __DIR__ . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}

define('devicesDir', __DIR__ . '/../config/devices/'); // Abeille's supported devices
define('devicesLocalDir', __DIR__ . '/../config/devices_local/'); // Unsupported/user devices

// Exécution de l'action demandée
$action = $_POST['action'];

if (function_exists($action)) {
    $action();
} else {
    die("L'action demandée n'existe pas");
}

/**
 * Retourne en JSON la liste des modèles possibles (connus d'abeille).
 * Inclut les modèles d'origine et les modèles locaux s'il en existe.
 */
function getModelChoiceList()
{
    // On permet le choix des modèles locaux et des modèles officiels
    // Tableau associatif modelID => model data
    $list = getDevicesList("local");
    $list = array_merge($list, getDevicesList("Abeille"));

    // On récupère le modèle actuel de l'équipement (s'il en a un)
    $eqId = (int) $_POST['eqId'];
    $eqLogic = eqLogic::byId($eqId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__) . ' ' . $eqId);
    }
    $currentModel = $eqLogic->getConfiguration('ab::eqModel', null);
    $currentModelID = null;
    if ($currentModel != null && isset($currentModel['id'])) {
        $currentModelID = $currentModel['id'];
    }

    // Si on a un modèle actuel, on pourra pré-remplir l'IHM: on passe l'info
    if ($currentModelID != null && isset($list[$currentModelID])) {
        $list[$currentModelID]['isCurrent'] = true;
    }

    // On envoie la liste
    die(json_encode($list));
}

/**
 * Enregistre le choix de l'utilisateur: change le modèle de l'équipement, et 
 * le force en 'manuel' afin que le modèle ne soit plus modifié automatiquement
 * à partir de la signature zigbee à la prochaine annonce de l'équipement.
 */
function setModelToDevice()
{
    // Récupération de l'équipement
    $eqId = (int) $_POST['eqId'];
    $eqLogic = eqLogic::byId($eqId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__) . ' ' . $eqId);
    }

    // Récupération du modèle choisi, sous la forme "blabla (Abeille/id.json)" ou "blabla (local/id.json)"
    $strSaisie = $_POST['modelChoice'];

    // Extraction du chemin réel du modèle JSON à utiliser
    $tmpPos = strrpos($strSaisie, ' (');
    if ($tmpPos === false) {
        throw new Exception(__('Saisie incorrecte', __FILE__) . ' ' . $eqId);
    }

    $tmpModelPath = mb_substr($strSaisie, $tmpPos + 2); // "Abeille/id.json)" 
    $tmpModelPath = mb_substr($tmpModelPath, 0, mb_strlen($tmpModelPath) - 1); // "Abeille/id.json"

    $tmpNomModel = explode("/", $tmpModelPath);
    if (!is_array($tmpNomModel) || sizeof($tmpNomModel) != 2) {
        throw new Exception(__('Saisie incorrecte', __FILE__) . ' ' . $eqId);
    }
    $source = $tmpNomModel[0]; // Abeille ou local
    $json = pathinfo(basename($tmpNomModel[1]), PATHINFO_FILENAME); // retire le .json

    $modelPath = '';
    if ($source == 'Abeille') {
        $modelPath = devicesDir . $json . '/' . $json . '.json';
    } else {
        $modelPath = devicesLocalDir . $json . '/' . $json . '.json';
        $source = 'local'; // normalement c'est déjà le cas
    }

    // On vérifie que le fichier modèle existe (sécurité ultime) et on le charge
    if (!is_readable($modelPath)) {
        throw new Exception(__('Saisie incorrecte - Fichier modèle introuvable', __FILE__) . ' ' . $eqId);
    }
    $jsonModelData = json_decode(file_get_contents($modelPath), true);
    if (!isset($jsonModelData[$json]['type'])) {
        // Bizarre, mais il y a peut-être des vieux json mal remplis
        $libelleType = '';
    } else {
        $libelleType = $jsonModelData[$json]['type'];
    }

    // Enregistrement de la nouvelle configuration
    $eqModelInfos = array(
        'id' => $json, // ID du json
        'location' => $source, // Abeille ou local
        'type' => $libelleType,
        'lastUpdate' => time() // Store last update from model
    );
    $eqLogic->setConfiguration('ab::eqModel', $eqModelInfos);

    // Le modèle a été choisi manuellement, il ne sera pas écrasé en cas de ré-annonce de l'équipement
    $eqLogic->setConfiguration('ab::isManualModel', true);

    $eqLogic->save();

    message::add("Abeille", date('d/m/Y H:i:s') . " > Modèle enregistré. L'équipement va maintenant être reconfiguré...", '');
    die("true");

    // (L'IHM enverra une deuxième requête ajax pour reconfigurer l'équipement à partir du nouveau modèle)
}

/**
 * Rétablit le fonctionnement normal de la sélection de modèle pour un équipement.
 * (= le modèle pourra à nouveau être détecté par Abeille à la prochaine annonce de l'équipement)
 */
function disableManualModelForDevice()
{
    // Récupération de l'équipement
    $eqId = (int) $_POST['eqId'];
    $eqLogic = eqLogic::byId($eqId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('EqLogic inconnu. Vérifiez l\'ID', __FILE__) . ' ' . $eqId);
    }

    // On restaure le fonctionnement normal
    $eqLogic->setConfiguration('ab::isManualModel', false);
    $eqLogic->save();

    message::add("Abeille", date('d/m/Y H:i:s') . " > Choix automatique du modèle réactivé pour cet équipement. Vous pouvez maintenant le Mettre à jour pour le re-configurer.", '');


    die("true");
}



/** Code repris de .tools\gen_devices_list.php  */


/* Get list of supported devices ($from="Abeille"), or user/custom ones ($from="local")
        Returns: Associative array; $devicesList[$identifier] = array(), or false if error */
function getDevicesList($from = "Abeille")
{
    $devicesList = [];

    if ($from == "Abeille")
        $rootDir = devicesDir;
    else if ($from == "local")
        $rootDir = devicesLocalDir;
    else {
        echo ("ERROR: Emplacement JSON '" . $from . "' invalide\n");
        return false;
    }

    $dh = opendir($rootDir);
    if ($dh === false) {
        echo ('ERROR: getDevicesList(): opendir(' . $rootDir . ')\n');
        return false;
    }
    while (($dirEntry = readdir($dh)) !== false) {
        /* Ignoring some entries */
        if (in_array($dirEntry, array(".", "..")))
            continue;
        $fullPath = $rootDir . $dirEntry;
        if (!is_dir($fullPath))
            continue;

        $fullPath = $rootDir . $dirEntry . '/' . $dirEntry . ".json";
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
        $dev['model'] = isset($devConf['model']) ? $devConf['model'] : '';
        $dev['type'] = $devConf['type'];
        $dev['icon'] = $devConf['configuration']['icon'];
        $devicesList[$dirEntry] = $dev;

        if (isset($devConf['alternateIds'])) {
            $idList = explode(',', $devConf['alternateIds']);
            foreach ($idList as $id) {
                echo ("getDevicesList(): Alternate ID '" . $id . "' for '" . $dirEntry . "'\n");
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
