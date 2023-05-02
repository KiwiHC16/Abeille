<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';

    /* Developers debug features & PHP errors */
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = true;
    }

    // require_once __DIR__.'/../../../../core/php/core.inc.php';
    // include_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    // include_once __DIR__.'/../../core/php/AbeilleLog.php'; // logDebug()
    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
     */

    // $eqLogics = Abeille::byType('Abeille');

    // eqLogic/configuration/ab::settings reminder
    // networkMap[zgId] = array(
    //     "level0" => "map-level0.png",
    //     "levelX" => "map-levelX.png"
    // )

    define('maxLevels', 10); // Number of levels
    sendVarToJS('maxLevels', maxLevels);

    function addSelect() {
        echo '<select>';
        echo '</select>';
    }
?>
{{Cette page vous permet de définir les niveaux et plans associés}}.<br><br>

<form>
    <div class="col-sm-6">
        <table>
            <tr>
                <th style="width:150px">{{Niveau}}</th>
                <th style="width:300px">{{Plan}}</th>
            </tr>
            <?php
            for ($t = 0; $t < maxLevels; $t++) {
                echo "<tr>";
                echo "<td>";
                addSelect("idSelect".$t);
                echo "</td>";
                echo "<td></td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
    <div class="col-sm-6">
        {{Ajouter niveau}}:
        <input id="idLevelNew" type="text" value="">
        <button id="idLevelPlus" class="btn btn-success btn-sm" onclick="levelAdd()">+</button>
    </div>
</form>

<form>
    <div class="form-group">
        <label class="col-sm-2 control-label">{{Niveau}}</label>
        <div class="col-sm-9">
            <!-- Drop down list to select Zigate -->
            <select id="idLevel" onchange="levelChanged()" style="width:100px">
            </select>
            <button id="idLevelMin" class="btn btn-danger btn-sm" onclick="levelRemove()">-</button>
            <br>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{Plan}}</label>
        <div class="col-sm-9">
            <button id="map" onclick="uploadMap()">{{Plan}}</button>
        </div>
    </div>
</form>

<script>
    // TEMP
    networkMap = {
        1: {
            rdc: 'map-rdc.png',
            premier: 'map-1er.png'
        }
    };
    // END TEMP

    refreshSelects();

    function refreshSelects() {
        console.log("refreshSelects(). networkMap=", networkMap);
        if (typeof networkMap[zgId] === 'undefined')
            return;

        nm = networkMap[zgId];
        console.log("nm=", nm);
        for (s = 0; s < maxLevels; s++) {
            select = document.getElementById("idSelect"+s);
            for (const [level, map] of Object.entries(nm)) {
                var el = document.createElement("option");
                el.textContent = level;
                el.value = map;
                select.appendChild(el);
            }
        }
    }

    function levelChanged() {
        console.log("levelChanged())");
        level = $("#idLevel").val();
        console.log("level=", level);
    }

    // Remove current selected level
    function levelRemove() {
        select = document.getElementById("idLevel");
        index = select.selectedIndex;
        console.log("levelRemove(index="+index+")");
        if (index < 0)
            return;
        select.remove(index);
    }

    // Add a new level
    function levelAdd() {
        value = document.getElementById("idLevelNew").value;
        console.log("levelAdd(value="+value+")");
        if (value == '')
            return;

        // TODO: Ignore if level already exists

        var el = document.createElement("option");
        el.textContent = value;
        el.value = value;
        select.appendChild(el);
    }
</script>
