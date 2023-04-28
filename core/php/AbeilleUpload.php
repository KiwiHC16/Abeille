<?php
    // Move tmp file after upload via HTTP POST
    // Used for OTA files upload

    require_once __DIR__.'/../config/Abeille.config.php'; // otaDir
    include_once __DIR__.'/AbeilleLog.php'; // logDebug()

    /* Expecting
        $_FILES['file']
            $_FILES['file']['error']: Should be 0
            $_FILES['file']['name']: Destination file name
            $_FILES['file']['tmp_name']: Source tmp file name
            $_FILES['destDir']: Dest dir relative to Abeille's root
        $_POST['destDir'] => OPTIONAL
            /xxx => absolut path for destination directory
            xxx/yy => relative path to Abeille's root
        $_POST['destName'] => OPTIONAL
     */
    logDebug('AbeilleUpload: _FILES='.json_encode($_FILES));
    logDebug('AbeilleUpload: _POST='.json_encode($_POST));

    $tmpFile = $_FILES['file']['tmp_name'];
    logDebug('AbeilleUpload: tmpFile='.$tmpFile);

    if (!file_exists($tmpFile)) {
        logDebug('AbeilleUpload: ERROR: tmp file not found');
        return;
    }

    if (!isset($_POST['destDir']))
        $destDir = __DIR__.'/../../'.otaDir; // Defaulting to OTA dir
    else if (substr($_POST['destDir'], 0, 1) == '/') // Absolut path ?
        $destDir = $_POST['destDir'];
    else // Relative to Abeille's root path
        $destDir = __DIR__.'/../../'.$_POST['destDir'];

    if (!isset($_POST['destName']))
        $destName = $_FILES['file']['name'];
    else
        $destName = $_POST['destName'];

    /* Checking if destination dir exists */
    if (!file_exists($destDir)) {
        mkdir($destDir, 0744);
        if (!file_exists($destDir)) {
            logDebug('AbeilleUpload: ERROR: Can\'t create destination dir');
            return;
        }
    } else {
        $cmd = "sudo chown -R www-data ".$destDir;
        $cmd .= "; sudo chgrp -R www-data ".$destDir;
        exec($cmd);
    }

    $destFile = $destDir.'/'.$destName;
    logDebug('AbeilleUpload: destFile='.$destFile);
    logDebug('AbeilleUpload: tmp file size='.filesize($tmpFile));
    if (move_uploaded_file($tmpFile, $destFile)) {
        logDebug("AbeilleUpload: File properly uploaded");
    } else {
        logDebug("AbeilleUpload: ERROR: Unexpected");
    }
?>