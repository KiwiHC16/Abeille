<?php
    /* Developers debug features & PHP errors */
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        $dbgConfig = json_decode(file_get_contents(dbgFile), true);
        if (isset($dbgConfig["defines"])) {
            $arr = $dbgConfig["defines"];
            foreach ($arr as $idx => $value) {
                if ($value == "Tcharp38")
                    $dbgTcharp38 = true;
            }
        }
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    sendVarToJS('eqType', 'Abeille');
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
    echo '<script>var js_queueXToCmd = "'.$abQueues['xToCmd']['id'].'";</script>'; // PHP to JS

    /* Creating a per Zigate list of eq ids.
       For each zigate, the first eq is the zigate.
       $eqPerZigate[$zgId][id1] => id for zigate
       $eqPerZigate[$zgId][id2] => id for next eq... */
    $eqPerZigate = array(); // All equipements id/addr per zigate
    $activeGateways = 0; // Number of active networks
    $eqLogics = eqLogic::byType('Abeille');
    foreach ($eqLogics as $eqLogic) {

        $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
        list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
        $zgId = hexdec(substr($eqNet, 7)); // Extracting zigate number from network
        $eqId = $eqLogic->getId();
        $eqParent = $eqLogic->getObject();
        if (!is_object($eqParent))
            $pName = "";
        else
            $pName = $eqParent->getName();

        $eq = [];
        $eq['id'] = $eqId;
        $eq['addr'] = $eqAddr;
        $eq['isEnabled'] = $eqLogic->getIsEnable();
        $eq['isVisible'] = $eqLogic->getIsVisible();
        $eq['mainEp'] = $eqLogic->getConfiguration('mainEP', '');
        $eqModel = $eqLogic->getConfiguration('ab::eqModel', null);
        $eq['jsonId'] = $eqModel ? $eqModel['modelName'] : '';
        $eq['name'] = $eqLogic->getName();
        $eq['pName'] = $pName; // Parent name
        $eq['icon'] = $eqLogic->getConfiguration('ab::icon', '');
        if (isset($eqModel['variables']))
            $eq['variables'] = $eqModel['variables'];

        if ($eqAddr == "0000") {
            if (isset($eqPerZigate[$zgId][$eqId])) // Tcharp38: Why would it be the case ? Can't remind that.
                array_unshift($eqPerZigate[$zgId][$eqId], $eq);
            else
                $eqPerZigate[$zgId][$eqId] = $eq;
            if ($eq['isEnabled'])
                $activeGateways++;
        } else
            $eqPerZigate[$zgId][$eqId] = $eq;
    }
    $GLOBALS['eqPerZigate'] = $eqPerZigate; // TODO: To align on daemons, replace to $GLOBALS['devices'][net][addr]

    echo '<script>var js_eqPerZigate = \''.json_encode($eqPerZigate).'\';</script>';
    echo '<script>var js_urlProducts = "'.urlProducts.'";</script>';
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div class="row row-overflow">
	<!-- <form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post"> -->

		<div class="col-xs-12 eqLogicThumbnailDisplay">

            <!-- Top level buttons  -->
            <?php include 'Abeille-TopButtons.php'; ?>

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
<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
<script>
    // test
    // $(".eqLogicAction[data-action=remove]")
    // .off("click")
    // .on("click", function (evt) {
    //     console.log("AFTER eqLogicAction[data-action=remove] click: evt=", evt);
    // });
</script>
