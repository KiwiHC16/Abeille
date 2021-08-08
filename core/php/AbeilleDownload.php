<?php
	try {
		require_once __DIR__ . '/../../../../core/php/core.inc.php';
		include_file('core', 'authentification', 'php');
		if (!isConnect() && !jeedom::apiAccess(init('apikey'))) {
			throw new Exception(__('401 - Accès non autorisé', __FILE__));
		}

		$pathfile = calculPath(urldecode(init('pathfile')));
		$pathfile = realpath($pathfile);
		if ($pathfile === false) {
			throw new Exception(__('401 - Accès non autorisé', __FILE__));
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
