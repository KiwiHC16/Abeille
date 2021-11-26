<?php
	include_once __DIR__."/AbeilleLog.php"; // logDebug()

	try {
		require_once __DIR__ . '/../../../../core/php/core.inc.php';
		include_file('core', 'authentification', 'php');
		if (!isConnect() && !jeedom::apiAccess(init('apikey'))) {
			throw new Exception(__('401 - Accès non autorisé', __FILE__));
		}

		$pathfile = init('pathfile');
		if (substr($pathfile, 0, 1) != "/")
			$pathfile = __DIR__."/../../".$pathfile;
		// $pathfile = calculPath(urldecode($pathfile));
		// $pathfile = realpath($pathfile);
		// if ($pathfile === false) {
		// 	throw new Exception(__('401 - Accès non autorisé', __FILE__));
		// }
		logDebug("pathfile=".$pathfile);
		if (!file_exists($pathfile)) {
			echo "ERREUR interne: Fichier inexistant<br>";
			echo "Chemin: ".$pathfile;
			exit;
		}
		$path_parts = pathinfo($pathfile);
		$filename = $path_parts['basename'];
		/* If 'addext' is defined, checking if file has the correct extension.
		   If not, extension added */
		if (isset($_GET['addext'])) {
			$ext = ".".$_GET['addext'];
			if (substr($filename, -strlen($ext)) != $ext)
				$filename .= $ext;
		}

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$filename);
		readfile($pathfile);
		exit;
	} catch (Exception $e) {
		echo $e->getMessage();
	}
?>
