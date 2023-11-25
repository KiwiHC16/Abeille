<!-- Maintenance page -->

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

    $eqLogics = eqLogic::byType('Abeille');
?>

<div style="padding-top: 5px">
    <ul class="nav nav-tabs" role="tablist">
        <li role="tab" class="active"><a href="#idLogs"> {{Logs}} </a></li>
        <?php
        if (isset($dbgDeveloperMode))
            echo '<li role="tab"><a href="#idPhantoms"> {{Fantômes}} </a></li>';
        ?>
        <!-- <li role="tab"               ><a href="#idModeles"> {{Modèles}} </a></li> -->
    </ul>

    <div class="tab-content" style="height:inherit;overflow-y:hidden;overflow-x:hidden;">

        <div role="tabpanel" class="tab-pane active" id="idLogs" style="height:inherit">
            <?php
            include_once "AbeilleMaintenance-Logs.php";
            ?>
        </div>

        <div role="tabpanel" class="tab-pane" id="idPhantoms" style="height:inherit">
            <?php
            include_once "AbeilleMaintenance-Phantoms.php";
            ?>
        </div>

        <div role="tabpanel" class="tab-pane" id="idModeles">
            <table id="idModelesTable">
                <tbody>
                    <tr><th>Objet</th><th>Nom</th><th>Modèle</th><th>Source</th><th>Date</th></tr>
                    <?php
                        foreach ($eqLogics as $eqLogic) {
                            $eqLogicId = $eqLogic->getLogicalId(); // Ex: 'Abeille1/0000'
                            list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
                            if ($eqAddr == "0000")
                                continue; // Ignoring zigates

                            $eqParent = $eqLogic->getObject();
                            if (!is_object($eqParent))
                                $eqParent = "";
                            else
                                $eqParent = $eqParent->getName();
                            $eqName = $eqLogic->getName();
                            $eqConfModel = $eqLogic->getConfiguration('ab::eqModel', []);
                            if (isset($eqConfModel['modelName']))
                                $eqModel = $eqConfModel['modelName'];
                            else
                                $eqModel = "";
                            $eqModelSrc = "Abeille";
                            if (isset($eqConfModel['modelSource']))
                                $eqModelSrc = $eqConfModel['modelSource'];
                            if ($eqModelSrc == "")
                                $eqModelSrc = "Abeille";

                            echo '<tr>';
                            echo '<td>'.$eqParent.'</td>';
                            echo '<td>'.$eqName.'</td>';
                            echo '<td>'.$eqModel.'</td>';
                            echo '<td>'.$eqModelSrc.'</td>';
                            echo '</tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

