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

    include_once __DIR__.'/AbeilleLog.php';

    // Associative array to hex string
    function otaAArrayToHString($header) {
        $s = $header['otaUpgradeFileId'].$header['otaHeaderVersion'].$header['otaHeaderLength'].$header['otaHeaderFieldControl'];
        $s .= $header['manufCode'].$header['imageType'].$header['fileVersion'];
        $s .= $header['zigbeeStackVersion'].$header['otaHeaderString'];
        $s .= $header['totalSize'];
        // TODO: It is necessary to complete for zigate cmd 0500 ??
        return $s;
    }

    /* Check available firmwares and fills $GLOBALS['ota_fw'] */
    function otaReadFirmwares() {
        // Reading available OTA firmwares
        $otaDir = __DIR__.'/../../'.otaDir;
        if (is_dir($otaDir))
            $dh = opendir($otaDir);
        else
            $dh = false;
        if ($dh !== false) {
            while (($dirEntry = readdir($dh)) !== false) {
                if (in_array($dirEntry, array(".", "..")))
                    continue;
                // if ((substr($dirEntry, -4) != ".ota") && (substr($dirEntry, -11) != ".ota.signed"))
                //     continue;

                $fullPath = $otaDir."/".$dirEntry;
                $fh = fopen($fullPath, "rb");
                $fc = fread($fh, 69); // fc = File content
                // fclose($fh);

                logMessage('debug', 'OTA FW: '.$dirEntry);
                if (substr($fc, 0, 4) == "NGIS") {
                    logMessage('debug', '  Ikea style SIGNED image');
                    $arr = unpack('VstartOfHeader', substr($fc, 0x10));
                    // logMessage('debug', '  arr='.json_encode($arr));
                    $startOfHeader = $arr['startOfHeader'];
                    logMessage('debug', '  startOfHeader='.$startOfHeader);
                    fseek($fh, $startOfHeader, SEEK_SET);
                    $fc = fread($fh, 69); // fc = File content
                    fclose($fh);
                    $startIdx = $startOfHeader;
                } else {
                    // logMessage('debug', '  UNsigned image');
                    fclose($fh);
                    $startIdx = 0;
                }

                // otaHeaderString: replacing 00 by space
                for ($i = 20; $i < 52; $i++)
                    if ($fc[$i] == "\0")
                        $fc[$i] == " ";
                // Analyzing OTA file
                $format = 'VotaUpgradeFileId' // Expecting 0x0BEEF11E
                    .'/votaHeaderVersion'
                    .'/votaHeaderLength'
                    .'/votaHeaderFieldControl'
                    .'/vmanufCode'
                    .'/vimageType'
                    .'/VfileVersion'
                    .'/vzigbeeStackVersion'
                    .'/H64otaHeaderString' // 32 char to hexa
                    .'/VtotalSize';
                $header = unpack($format, $fc);
                if ($header['otaUpgradeFileId'] != 0x0BEEF11E) {
                    logMessage('debug', 'ERROR: Invalid OTA file: '.$dirEntry);
                    continue;
                }
                $header['otaUpgradeFileId'] = sprintf("%08X", $header['otaUpgradeFileId']);
                $header['otaHeaderVersion'] = sprintf("%04X", $header['otaHeaderVersion']);
                $header['otaHeaderLength'] = sprintf("%04X", $header['otaHeaderLength']);
                $header['otaHeaderFieldControl'] = sprintf("%04X", $header['otaHeaderFieldControl']);
                $header['manufCode'] = sprintf("%04X", $header['manufCode']);
                $header['imageType'] = sprintf("%04X", $header['imageType']);
                $header['fileVersion'] = sprintf("%08X", $header['fileVersion']);
                $header['zigbeeStackVersion'] = sprintf("%04X", $header['zigbeeStackVersion']);
                $header['totalSize'] = sprintf("%08X", $header['totalSize']);
                logMessage("debug", "  header=".json_encode($header));
                logMessage("debug", "  OTA FW for ".$header['manufCode'].': imgType='.$header['imageType'].', version='.$header['fileVersion']);
                $GLOBALS['ota_fw'][$header['manufCode']][$header['imageType']] = array(
                    'fileVersion' => $header['fileVersion'],
                    'fileName' => $dirEntry,
                    'fileSize' => $header['totalSize'],
                    'startIdx' => $startIdx,
                    'header' => $header
                );
            }
            closedir($dh);
        }
    }

    // Old code
    // } else if (substr($dirEntry, -5) == ".json") {
    //     // JSON file associated to signed OTA file
    //     $fc = json_decode(file_get_contents($fullPath), true);
    //     if ($fc === false) {
    //         logMessage('debug', 'ERROR: Corrupted JSON file: '.$dirEntry);
    //         continue;
    //     }
    //     // Expecting the following fields
    //     $required = ['fw_binary_url', 'fw_manufacturer_id', 'fw_image_type', 'fw_file_version_MSB', 'fw_file_version_LSB'];
    //     $missingParam = false;
    //     foreach ($required as $idx => $param) {
    //         if (isset($fc[$param]))
    //             continue;
    //         // $this->deamonlog('debug', "ERROR: Missing '".$param."'");
    //         $missingParam = true;
    //     }
    //     if ($missingParam) {
    //         logMessage('debug', 'ERROR: Invalid JSON file (missing fields): '.$dirEntry);
    //         continue;
    //     }

    //     $fileName = $fc['fw_binary_url'];
    //     $fileName = basename($fileName);
    //     if (!file_exists(otaDir.'/'.$fileName)) {
    //         logMessage('debug', 'ERROR: No binary file associated: '.$dirEntry);
    //         continue;
    //     }
    //     $manufCode = sprintf("%04X", $fc['fw_manufacturer_id']);
    //     $imageType = sprintf("%04X", $fc['fw_image_type']);
    //     $version = sprintf("%04X", $fc['fw_file_version_MSB']).sprintf("%04X", $fc['fw_file_version_LSB']);
    //     $fileSize = sprintf("%08X", $fc['fw_filesize']);
    //     logMessage("debug", "OTA FW for ".$manufCode.': imgType='.$imageType.', version='.$version);
    //     $GLOBALS['ota_fw'][$manufCode][$imageType] = array(
    //         'fileVersion' => $version,
    //         'fileName' => $fileName,
    //         'fileSize' => $fileSize,
    //     );
    // }
?>
