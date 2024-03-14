<!-- This file displays advanced equipment/zigbee infos.
     Included by 'Abeille-Eq.php' -->

<?php
    if (isset($dbgDeveloperMode)) echo __FILE__;

    function addDocButton($chapter) {
        // $urlUserMan = "https://kiwihc16.github.io/AbeilleDoc"; // Constant defined in Abeille config
        echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/'.$chapter.'"><i class="fas fa-book"></i> ?</a>';
    }

    function addButton($name, $class, $interrogate) {
        echo '<a class="btn '.$class.'" style="width:80px" onclick="interrogate(\''.$interrogate.'\')">'.$name.'</a>';
    }

    // Add end point input
    function addEpInput($id) {
        echo '<input id="'.$id.'" class="advEp" title="{{End Point (Hexa 2 car; ex: 01)}}" value="XX" style="width:30px; margin-left: 8px" />';
    }

    function addClusterList($id) {
        global $zbClusters;
        echo '<select id="'.$id.'" title="{{Cluster}}" style="width:140px; margin-left: 8px">';
        foreach ($zbClusters as $clustId => $clust) {
            echo '<option value="'.$clustId.'">'.$clustId.' / '.$clust['name'].'</option>';
        }
        echo '</select>';
    }

    // Add direction input
    function addDirInput($id) {
        echo '<input id="'.$id.'" style="width:80px; margin-left: 8px" title="{{Direction (Hexa 2 car: 00=vers serveur, 01=vers client)}}" placeholder="{{Dir}}" />';
    }

    // Add manufacturer code input
    function addManufCodeInput($id) {
        echo '<input id="'.$id.'" style="width:60px; margin-left: 8px" title="{{Code fabricant (Hexa 4 car; ex: 12AC)}}" placeholder="{{Code fabricant}}" />';
    }

    // Create drop down list of IEEE addresses, excluding zigate by default
    function addIeeeListButton($id, $withZigate = false) {
        echo '<select id="'.$id.'" title="{{Equipements}}" style="width:140px; margin-left: 8px">';
        $eqLogics = eqLogic::byType('Abeille');
        foreach ($eqLogics as $eqLogic) {
            list($net, $addr) = explode( "/", $eqLogic->getLogicalId());
            if (($addr == '0000') && !$withZigate)
                continue; // Excluding zigates

            $ieee = $eqLogic->getConfiguration('IEEE', '');
            if ($ieee == '')
                continue;
            $eqName = $eqLogic->getName();
            echo '<option value="'.$ieee.'">'.$ieee.' / '.$eqName.'</option>';
        }
        echo '</select>';
    }

    function addAttrInput($id) {
        echo '<input id="'.$id.'" style="width:60px; margin-left: 8px" placeholder="{{Attribut}}" title="Attribut (Hexa 4 car; ex: 050B)"/>';
    }

    // Create drop down list of Zigbee possible attribute types
    function addTypeList($id) {
        echo '<select id="'.$id.'" style="width:90px; margin-left: 8px" title="{{Type d\'attribut}}" />';
        global $zbDataTypes;
        foreach ($zbDataTypes as $typeId => $type) {
            $typeId = strtoupper($typeId);
            if ($typeId == 'FF')
                echo '<option value="'.$typeId.'" selected></option>';
            else
                echo '<option value="'.$typeId.'">'.$typeId.' / '.$type['short'].'</option>';
        }
        echo '</select>';
    }

    // Add a group address input
    function addGroupInput($id, $title = 'Group') {
        echo '<input id="'.$id.'" style="width:60px; margin-left: 8px" placeholder="{{Group}}" title="{{'.$title.' (4 chars hex, ex: 0001)}}" />';
    }

    // Create drop down list of 'Warning modes' (cluster 0502/Start Warning)
    function addWarningModesList($id) {
        echo '<select id="'.$id.'" style="width:90px; margin-left: 8px" title="{{Warning mode}}" />';
        echo '<option value="stop">Stop</option>';
        echo '<option value="burglar">Burglar</option>';
        echo '<option value="fire">Fire</option>';
        echo '<option value="emergency" selected>Emergency</option>';
        echo '<option value="policepanic">Police panic</option>';
        echo '<option value="firepanic">Fire panic</option>';
        echo '<option value="emergencypanic">Emergency panic</option>';
        echo '</select>';
    }

    // Create drop down list of 'Siren levels' (cluster 0502/Start Warning)
    function addSirenLevelList($id) {
        echo '<select id="'.$id.'" style="width:90px; margin-left: 8px" title="{{Siren level}}" />';
        echo '<option value="low">Low</option>';
        echo '<option value="medium">Medium</option>';
        echo '<option value="high" selected>High</option>';
        echo '<option value="veryhigh">Very high</option>';
        echo '</select>';
    }

    function addCheckbox($id, $title = '', $ph = '') {
        // Note: CHECKED by default
        echo '<input type="checkbox" id="'.$id.'" checked style="width:120px; margin-left: 8px" title="'.$title.'" placeholder="'.$ph.'"/>';
    }

    function addInput($id, $title = '', $ph = '') {
        echo '<input id="'.$id.'" title="'.$title.'" placeholder="'.$ph.'" style="width:60px; margin-left:8px" />';
    }

    // function addJsUpdateFunction($eqId, $cmdLogicId, $spanId, $isInput = false) {
    //     echo "<script>";
    //     echo "jeedom.cmd.update['".getCmdIdByLogicId($eqId, $cmdLogicId)."'] = function(_options) {";
    //         echo "console.log('jeedom.cmd.update[".$cmdLogicId."] <= ' + _options.display_value);";
    //         // console.log(_options);
    //         echo "var element = document.getElementById('".$spanId."');";
    //         echo "console.log('element=', element);";
    //         if ($isInput)
    //             echo "element.value = _options.display_value;";
    //         else // Not <input>. Assuming <span>
    //             echo "element.textContent = _options.display_value;";
    //     echo "}";
    //     echo "</script>";
    // }
?>

<form class="form-horizontal">
    <fieldset>
        <?php
            require_once __DIR__.'/../../core/php/AbeilleZigbeeConst.php';
            include 'Abeille-Eq-Advanced-Common.php';

            echo '<div id="idAdvZigate" style="display:none;">';
            include 'Abeille-Eq-Advanced-Zigate.php'; // Hidden by default
            echo '</div>';

            echo '<div id="idAdvDevices" style="display:none;">';
            include 'Abeille-Eq-Advanced-Device.php';
            include 'Abeille-Eq-Advanced-Specific.php';
            echo '</div>';

            include 'Abeille-Eq-Advanced-Interrogations.php';
        ?>
    </fieldset>
</form>
