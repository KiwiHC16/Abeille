<?php
    /* Developers debug features & PHP errors */
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["defines"])) {
            $arr = $dbgConfig["defines"];
            foreach ($arr as $idx => $value) {
                if ($value == "Tcharp38")
                    $dbgTcharp38 = true;
            }
        }
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    sendVarToJS('eqType', 'Abeille');
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS

    $eqLogics = eqLogic::byType('Abeille');
    /* Creating a per Zigate list of eq ids.
       For each zigate, the first eq is the zigate.
       $eqPerZigate[$zgId][id1] => id for zigate
       $eqPerZigate[$zgId][id2] => id for next eq... */
    $eqPerZigate = array(); // All equipements id/addr per zigate
    foreach ($eqLogics as $eqLogic) {
        $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
        list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
        $zgId = hexdec(substr($eqNet, 7)); // Extracting zigate number from network
        $eqId = $eqLogic->getId();
        $eq = [];
        $eq['id'] = $eqId;
        $eq['addr'] = $eqAddr;
        $eq['mainEp'] = $eqLogic->getConfiguration('mainEP', '');
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
        $eq['jsonId'] = $eqModel ? $eqModel['id'] : '';
        if ($eqAddr == "0000") {
            if (isset($eqPerZigate[$zgId][$eqId]))
                array_unshift($eqPerZigate[$zgId][$eqId], $eq);
            else
                $eqPerZigate[$zgId][$eqId] = $eq;
        } else
            $eqPerZigate[$zgId][$eqId] = $eq;
    }
    $GLOBALS['eqPerZigate'] = $eqPerZigate;
    echo '<script>var js_eqPerZigate = \''.json_encode($eqPerZigate).'\';</script>';

    // logDebug("eqPerZigate=".json_encode($eqPerZigate)); // In dev mode only
    // $parametersAbeille = AbeilleTools::getParameters();
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div class="row row-overflow">
	<!-- <form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post"> -->

		<div class="col-xs-12 eqLogicThumbnailDisplay">

            <!-- Top level buttons  -->
            <?php include 'Abeille-ToolsButtons.php'; ?>

            <!-- Equipements -->
            <?php include 'Abeille-Bees.php'; ?>

            <!-- Groups management  -->
            <?php include 'Abeille-Groups.php'; ?>

            <!-- Replace equipment on Jeedom side -->
            <?php include 'Abeille-ReplaceEq.php'; ?>

            <!-- Gestion des ReHome / migration d equipements  -->
            <?php include 'Abeille-MigrateEq.php'; ?>

            <?php include 'Abeille-NewZigate.php'; ?>

            <?php if (isset($dbgDeveloperMode)) { ?>
            <legend><i class="fa fa-cogs"></i> {{Visible en MODE DEV UNIQUEMENT}}</legend>
            <div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

                <!-- Gestion des scenes  -->
                <?php include 'Abeille-Scenes.php'; ?>

            </div>
            <?php } ?>

        </div> <!-- End eqLogicThumbnailDisplay -->

        <!-- Hidden equipment detail page -->
        <?php include 'Abeille-Eq.php'; ?>

	<!-- </form> -->
</div>

<!-- Scripts -->
<?php include 'Abeille-Js.php'; ?>
