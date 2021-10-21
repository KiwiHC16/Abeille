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

    /*
     * Targets for AJAX's requests
     */

    require_once __DIR__.'/../config/Abeille.config.php';

    /* Developers debug features */
    // $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    try {
        require_once __DIR__.'/../../../../core/php/core.inc.php';
        require_once __DIR__.'/../php/AbeilleZigate.php';
        include_once __DIR__.'/../php/AbeilleLog.php'; // logDebug()

        include_file('core', 'authentification', 'php');
        if (!isConnect('admin')) {
            throw new Exception('401 Unauthorized');
        }

        ajax::init();

        logDebug('Abeille.ajax.php: action='.init('action'));

        if (init('action') == 'getEPList') {
            logDebug('action == getEPList');

            $zgNb = init('zgNb'); // Ex: '1'
            $eqAddr = init('eqAddr'); // Ex: '0A23'

            // $zgPort = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '');
            $status = 0;
            $error = "";

            /* Request list of end points */
            logSetConf('AbeilleDebug.log', true);
            $status = zgGetEPList($zgNb, $eqAddr, $resp);
            logDebug("Got ".json_encode($resp));

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'resp' => $resp)));
        }

        if (init('action') == 'getSingleDescResp') {
            logDebug('action == getSingleDescResp');

            $zgNb = init('zgNb'); // Number, ex: 1
            $eqAddr = init('eqAddr'); // String, ex: '0A23'
            $eqEP = init('eqEP'); // Number, ex: 1

            // $zgPort = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '');
            $status = 0;
            $error = "";

            /* Request list of end points */
            logSetConf('AbeilleDebug.log', true);
            $status = zgGetSingleDescResp($zgNb, $eqAddr, $eqEP, $resp);
            logDebug("Got ".json_encode($resp));

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'resp' => $resp)));
        }

        /* Attribute Discovery Request & Response */
        if (init('action') == 'getAttrDiscResp') {
            logDebug('action == getAttrDiscResp');

            $zgNb = init('zgNb'); // Number, ex: 1
            $eqAddr = init('eqAddr'); // Hex string, ex: '0A23'
            $eqEP = init('eqEP'); // Number, ex: 1
            $clustId = init('clustId'); // Hex string, ex: '0102'

            // $zgPort = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '');
            $status = 0;
            $error = "";

            /* Request list of end points */
            logSetConf('AbeilleDebug.log', true);
            $status = zgGetAttrDiscResp($zgNb, $eqAddr, $eqEP, $clustId, $resp);
            logDebug("Got ".json_encode($resp));

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'resp' => $resp)));
        }

        /* Detect main supported attributs */
        if (init('action') == 'detectAttributs') {
            logDebug('action == detectAttributs');

            $zgNb = init('zgNb'); // Number, ex: 1
            $eqAddr = init('eqAddr'); // Hex string, ex: '0A23'
            $eqEP = init('eqEP'); // Number, ex: 1
            $clustId = init('clustId'); // Hex string, ex: '0102'

            // $zgPort = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '');
            $status = 0;
            $error = "";

            /* Request list of end points */
            logSetConf('AbeilleDebug.log', true);
            $status = zgDetectAttributs($zgNb, $eqAddr, $eqEP, $clustId, $resp);
            logDebug("Got ".json_encode($resp));

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'resp' => $resp)));
        }

        /* Send "Read Attribute Request" and return "Read Attribute Response" */
        if (init('action') == 'readAttributResponse') {
            logDebug('action == readAttributResponse');

            $zgNb = init('zgNb'); // Number, ex: 1
            $eqAddr = init('eqAddr'); // Hex string, ex: '0A23'
            $eqEP = init('eqEP'); // Number, ex: 1
            $clustId = init('clustId'); // Hex string, ex: '0102'
            $attrId = init('attrId'); // Hex string, ex: '0002'

            // $zgPort = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '');
            $status = 0;
            $error = "";

            /* Request list of end points */
            logSetConf('AbeilleDebug.log', true);
            $status = zgReadAttributeResponse($zgNb, $eqAddr, $eqEP, $clustId, $attrId, $resp);
            if ($status != 0)
                $error = "zgReadAttributeResponse() failed";
            else
                logDebug("Got ".json_encode($resp));

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'resp' => $resp)));
        }

//         /* Write device config in JSON format */
//         if (init('action') == 'writeConfigJson') {
//             logDebug('action == writeConfigJson');

//             $jsonName = init('jsonName'); // File name = Compresssed Zigbee model identifier
//             $eq = init('eq'); //

//             // $zgPort = config::byKey('AbeilleSerialPort'.$zgNb, 'Abeille', '');
//             $status = 0;
//             $error = "";

//             /* Request list of end points */
//             logSetConf('AbeilleDebug.log', true);
//             $json = json_encode($eq, JSON_PRETTY_PRINT);
// logDebug("json=".$json);
//             $jsonDir = __DIR__.'/../config/devices_local/'.$jsonName;
// logDebug("jsonDir=".$jsonDir);
//             if (!file_exists($jsonDir)) {
//                 if (mkdir($jsonDir) == false) {
//                     $status = -1;
//                     $error = "Can't create dir '".$jsonDir;
//                 }
//             }
//             if ($status == 0) {
//                 $jsonPath = $jsonDir.'/'.$jsonName.'.json';
// logDebug("jsonPath=".$jsonPath);
//                 file_put_contents($jsonPath, $json);
//             }

//             ajax::success(json_encode(array('status' => $status, 'error' => $error, 'resp' => $resp)));
//         }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
            Instead 'success' callback is used. This means that
            - take care of error code returned
            - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'AbeilleEqAssist.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
