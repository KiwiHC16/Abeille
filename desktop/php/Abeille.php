<?php
    if(0){
        if (!isConnect('admin')) {
            throw new Exception('{{401 - Accès non autorisé}}');
        }
    }

    /* Log function for debug purposes */
    function logToFile($msg = "")
    {
        $logFile = 'AbeilleDebug.log';
        $logDir = __DIR__.'/../../../../log/';
        file_put_contents($logDir.$logFile, '['.date('Y-m-d H:i:s').'] '.$msg."\n", FILE_APPEND);
    }

    /* Developers debug features & PHP errors */
    $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists($dbgFile)) {
        $dbgConfig = json_decode(file_get_contents($dbgFile), TRUE);
        $dbgDeveloperMode = TRUE;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    if (isset($_GET['id']) && is_numeric($_GET['id'])) { // If 'id' is set to number, let's redirect to 'AbeilleEq' page
        $uri = parse_url($_SERVER['REQUEST_URI']);
        // Replace "p=Abeille" by "p=AbeilleEq"
        $newuri = str_replace("p=Abeille", "p=AbeilleEq", $uri['query']);
        header("Location: index.php?".$newuri);
        exit();
    }

    /* The following part is executed only if no equipment selected (no id) */
    include '005_AbeilleFunctionPart.php';

    sendVarToJS('eqType', 'Abeille');
    $eqLogics = eqLogic::byType('Abeille');
    /* Creating a per Zigate list of eq ids.
       For each zigate, the first eq is the zigate.
       $eqPerZigate[zgNb][0] => id for zigate
       $eqPerZigate[zgNb][1] => id for next eq... */
    $eqPerZigate = array(); // All equipements id per zigate
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/Ruche'
        list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
        $zgNb = hexdec(substr($eqNet, 7)); // Extracting zigate number from network
        $eqId = $eqLogic->getId();
        if (($eqAddr == "0000") || ($eqAddr == "Ruche")) {
            if (isset($eqPerZigate[$zgNb]))
                array_unshift($eqPerZigate[$zgNb], $eqId);
            else
                $eqPerZigate[$zgNb][] = $eqId;
        } else
            $eqPerZigate[$zgNb][] = $eqId;
    }
    if (isset($dbgConfig))
        logToFile("eqPerZigate=".json_encode($eqPerZigate)); // In dev mode only
    $zigateNb = config::byKey('zigateNb', 'Abeille', '1');
    $parametersAbeille = AbeilleTools::getParameters();

    $outils = array(
        'health'    => array( 'bouton'=>'bt_healthAbeille',         'icon'=>'fa-medkit',        'text'=>'{{Santé}}' ),
        'netList'   => array( 'bouton'=>'bt_networkAbeilleList',    'icon'=>'fa-sitemap',       'text'=>'{{Network List}}' ),
        'net'       => array( 'bouton'=>'bt_networkAbeille',        'icon'=>'fa-map',           'text'=>'{{Network Graph}}' ),
        'graph'     => array( 'bouton'=>'bt_graph',                 'icon'=>'fa-flask',         'text'=>'{{Graph}}' ),
        'compat'    => array( 'bouton'=>'bt_listeCompatibilite',    'icon'=>'fa-align-left',    'text'=>'{{Compatibilite}}' ),
        'inconnu'   => array( 'bouton'=>'bt_Inconnu',               'icon'=>'fa-paperclip',     'text'=>'{{Inconnu}}' ),
        'support'   => array( 'bouton'=>'bt_supportPage',           'icon'=>'fa-medkit',        'text'=>'{{Support}}' ),
        );
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div>
	<form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">

        <!-- Barre d outils horizontale  -->
		<div class="col-xs-12 eqLogicThumbnailDisplay">

        <!-- Icones de toutes les modales  -->
        <?php include '010_AbeilleGestionPart.php'; ?>

        <!-- Icones de toutes les abeilles  -->
        <?php include '020_AbeilleMesAbeillesPart.php'; ?>

        <legend><i class="fa fa-cogs"></i> {{Appliquer les commandes sur la selection}}</legend>
        <div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

            <!-- Gestion des groupes et des scenes  -->
            <?php include '030_AbeilleGroupPart.php'; ?>

            <!-- Gestion des groupes et des scenes  -->
            <?php include '040_AbeilleScenePart.php'; ?>

        </div>

        <!-- Gestion des ghosts / remplacement d equipements  -->
        <?php include '050_AbeilleRemplacementPart.php'; ?>

        <!-- Affichage de la zone developpeur  -->
        <?php include '080_AbeilleZoneDevPart.php'; ?>

    </div> <!-- Fin - Barre d outils horizontale  -->

	</form>
</div>

<!-- Scripts -->
<?php include '200_AbeilleScript.php'; ?>
