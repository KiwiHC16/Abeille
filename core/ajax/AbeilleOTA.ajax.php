<?php

    /*
     * Targets for AJAX's requests related to OTA
     */

    require_once __DIR__.'/../config/Abeille.config.php';

    /* Developers debug features */
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect()) {
        ajax::error(json_encode(array('status' => -1, 'error' => "Non connecté ou session expirée.")));
    }

    include_once __DIR__.'/../php/AbeilleOTA.php';
    include_once __DIR__.'/../php/AbeilleLog.php'; // logDebug()

    try {
        ajax::init();

        /* Read list of OTA local firmwares */
        if (init('action') == 'getOtaList') {
            // logDebug("AbeilleOTA.ajax: action=getOtaList");

            otaReadFirmwares();
            if (isset($GLOBALS['ota_fw']))
                $fw_ota = $GLOBALS['ota_fw'];
            else
                $fw_ota = [];
            $status = 0;
            $error = "";

            ajax::success(json_encode(array('status' => $status, 'error' => $error, 'fw_ota' => $fw_ota)));
        }

        /* WARNING: ajax::error DOES NOT trig 'error' callback on client side.
           Instead 'success' callback is used. This means that
           - take care of error code returned
           - convert to JSON since dataType is set to 'json' */
        $error = "La méthode '".init('action')."' n'existe pas dans 'AbeilleOTA.ajax.php'";
        throw new Exception($error, -1);
    } catch (Exception $e) {
        /* Catch exeption */
        ajax::error(json_encode(array('status' => $e->getCode(), 'error' => $e->getMessage())));
    }
?>
