<?php

    /*
     * AbeilleOTA
     *
     * - OTA specific functions
     */

    include_once __DIR__.'/../config/Abeille.config.php';

    /* Developers options */
    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    /* Check available firmwares and fills $GLOBALS['ota_fw'] */
    function otaReadFirmwares() {
        // Reading available OTA firmwares
        // Tcharp38: WORK ONGOING !!
        if (is_dir(otaDir))
            $dh = opendir(otaDir);
        else
            $dh = false;
        if ($dh !== false) {
            while (($dirEntry = readdir($dh)) !== false) {
                if (in_array($dirEntry, array(".", "..")))
                    continue;
                $fullPath = otaDir."/".$dirEntry;

                if (substr($dirEntry, -4) == ".ota") {
                    // Standard OTA file
                    $fh = fopen($fullPath, "rb");
                    $fc = fread($fh, 56); // fc = File content
                    fclose($fh);
                    $format = 'VotaUpgradeFileId' // Expecting 0x0BEEF11E
                        .'/votaHeaderVersion'
                        .'/votaHeaderLength'
                        .'/votaHeaderFieldControl'
                        .'/vmanufCode'
                        .'/vimageType'
                        .'/VfileVersion'
                        .'/vzigbeeStackVersion'
                        .'/C32otaHeaderString'
                        .'/VtotalSize';
                    $header = unpack($format, $fc);
                    $header['otaUpgradeFileId'] = sprintf("%08X", $header['otaUpgradeFileId']);
                    $header['otaHeaderVersion'] = sprintf("%04X", $header['otaHeaderVersion']);
                    $header['manufCode'] = sprintf("%04X", $header['manufCode']);
                    $header['fileVersion'] = sprintf("%08X", $header['fileVersion']);
                    $header['totalSize'] = sprintf("%08X", $header['totalSize']);
                    logMessage("debug", "OTA ".json_encode($header));
                    if ($header['otaUpgradeFileId'] != '0BEEF11E') {
                        parserLog('debug', 'ERROR: Invalid OTA file: '.$dirEntry);
                        continue;
                    }
                    logMessage("debug", "OTA FW for ".$header['manufCode'].': imgType='.$header['imageType'].', version='.$header['fileVersion']);
                    $GLOBALS['ota_fw'][$header['manufCode']][$header['imageType']] = array(
                        'fileVersion' => $header['fileVersion'],
                        'fileName' => $dirEntry,
                        'fileSize' => $header['totalSize'],
                    );
                } else if (substr($dirEntry, -5) == ".json") {
                    // JSON file associated to signed OTA file
                    $fc = json_decode(file_get_contents($fullPath), true);
                    if ($fc === false) {
                        parserLog('debug', 'ERROR: Corrupted JSON file: '.$dirEntry);
                        continue;
                    }
                    // Expecting the following fields
                    $required = ['fw_binary_url', 'fw_manufacturer_id', 'fw_image_type', 'fw_file_version_MSB', 'fw_file_version_LSB'];
                    $missingParam = false;
                    foreach ($required as $idx => $param) {
                        if (isset($fc[$param]))
                            continue;
                        // $this->deamonlog('debug', "ERROR: Missing '".$param."'");
                        $missingParam = true;
                    }
                    if ($missingParam) {
                        parserLog('debug', 'ERROR: Invalid JSON file (missing fields): '.$dirEntry);
                        continue;
                    }

                    $fileName = $fc['fw_binary_url'];
                    $fileName = basename($fileName);
                    if (!file_exists(otaDir.'/'.$fileName)) {
                        parserLog('debug', 'ERROR: No binary file associated: '.$dirEntry);
                        continue;
                    }
                    $manufCode = sprintf("%04X", $fc['fw_manufacturer_id']);
                    $imageType = sprintf("%04X", $fc['fw_image_type']);
                    $version = sprintf("%04X", $fc['fw_file_version_MSB']).sprintf("%04X", $fc['fw_file_version_LSB']);
                    $fileSize = sprintf("%08X", $fc['fw_filesize']);
                    logMessage("debug", "OTA FW for ".$manufCode.': imgType='.$imageType.', version='.$version);
                    $GLOBALS['ota_fw'][$manufCode][$imageType] = array(
                        'fileVersion' => $version,
                        'fileName' => $fileName,
                        'fileSize' => $fileSize,
                    );
                }
            }
            closedir($dh);
        }
    }
?>
