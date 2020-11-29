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
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $path_parts['basename']);
		readfile($pathfile);
		exit;
	} catch (Exception $e) {
		echo $e->getMessage();
	}
?>
