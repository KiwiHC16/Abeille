<!-- This is equipement page opened when clicking on it.
     Displays main infos + specific params + commands. -->

<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers debug features & PHP errors */
    if (file_exists(dbgFile)) {
        // include_once $dbgFile;
        // include $dbgFile;
        $dbgDeveloperMode = true;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    if (!isset($_GET['id']))
        exit("ERROR: Missing 'id'");
    if (!is_numeric($_GET['id']))
        exit("ERROR: 'id' is not numeric");

    $eqId = $_GET['id'];
    $eqLogic = eqLogic::byId($eqId);
    if (!$eqLogic)
        exit("L'équipement dont l'ID est ".$eqId." n'existe plus. Merci de raffraichir votre page.");

    $eqLogicId = $eqLogic->getLogicalid();
    list($eqNet, $eqAddr) = explode("/", $eqLogicId);
    $zgId = substr($eqNet, 7); // Extracting zigate number from network
    $zgType = config::byKey('AbeilleType'.$zgId, 'Abeille', '', 1); // USB, WIFI, PIN, DIN
    $mainEP = $eqLogic->getConfiguration('mainEP', '01');
    $eqIeee = $eqLogic->getConfiguration('IEEE', '');
    $batteryType = $eqLogic->getConfiguration('battery_type', '');
    $eqName = $eqLogic->getName();

    $eqModel = $eqLogic->getConfiguration('ab::eqModel', []);

    echo '<script>var js_eqId = '.$eqId.';</script>'; // PHP to JS
    echo '<script>var js_eqAddr = "'.$eqAddr.'";</script>'; // PHP to JS
    echo '<script>var js_eqIeee = "'.$eqIeee.'";</script>'; // PHP to JS
    echo '<script>var js_zgId = '.$zgId.';</script>'; // PHP to JS
    echo '<script>var js_batteryType = "'.$batteryType.'";</script>'; // PHP to JS
    $abQueues = $GLOBALS['abQueues'];
    echo '<script>var js_queueXToCmd = '.$abQueues['xToCmd']['id'].';</script>'; // PHP to JS
    echo '<script>var js_queueXToParser = "'.$abQueues['xToParser']['id'].'";</script>'; // PHP to JS
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div class="col-xs-12 eqLogic" style="padding-top: 5px">
    <div class="input-group pull-right" style="display:inline-flex">
		<span class="input-group-btn">
			<a class="btn btn-success eqLogicAction btn-sm roundedLeft" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a><a class="btn btn-default eqLogicAction btn-sm roundedRight" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
		</span>
	</div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="tab"               ><a href="index.php?v=d&m=Abeille&p=Abeille" class="eqLogicAction"><i class="fas fa-arrow-circle-left"></i>                </a></li>
        <li role="tab" class="active"><a href="#idMain">                                                <i class="fas fa-home">             </i> {{Equipement}} </a></li>
        <li role="tab"               ><a href="#idAdvanced">                                            <i class="fas fa-list-alt">         </i> {{Avancé}}     </a></li>
        <li role="tab"               ><a href="#idCommands">                                            <i class="fas fa-align-left">       </i> {{Commandes}}  </a></li>
    </ul>

    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">

        <!-- Displays Jeedom specifics  -->
        <div role="tabpanel" class="tab-pane active" id="idMain">
            <form class="form-horizontal">
                <?php
                    include 'AbeilleEq-Main-Generic.php';
                    include 'AbeilleEq-Main-Icon.php';
                    include 'AbeilleEq-Main-Others.php';
                ?>
            </form>
        </div>

        <!-- Displays advanced & Zigbee specifics  -->
        <div role="tabpanel" class="tab-pane" id="idAdvanced">
            <?php include 'AbeilleEq-Advanced.php'; ?>
        </div>

        <!-- Displays Jeedom commands  -->
        <div role="tabpanel" class="tab-pane" id="idCommands">
            <?php include 'AbeilleEq-Cmds.php'; ?>
        </div>

    </div>
</div>

<?php include 'AbeilleEq-Js.php'; ?>
<?php include 'AbeilleEq-Js-Cmds.php'; ?>
