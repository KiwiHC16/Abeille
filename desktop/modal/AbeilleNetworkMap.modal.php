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
?>
{{Cette page vous permet de définir un plan et le nom (niveau) associé}}.<br><br>

Plan: <button id="map" onclick="uploadMap()">{{Plan}}</button>
<br>

Niveau:
<!-- Drop down list to select Zigate -->
<select id="idLevel" onchange="levelChanged()">
</select>

<script>
    // TEMP
    networkMap = {
        1: {
            rdc: 'map-rdc.png',
            premier: 'map-1er.png'
        }
    };
    // END TEMP

    buildLevelsList();

    function buildLevelsList() {
        console.log("buildLevelsList(). networkMap=", networkMap);
        if (typeof networkMap[zgId] === 'undefined')
            return;

        nm = networkMap[zgId];
        console.log("nm=", nm);
        select = document.getElementById("idLevel");
        for (const [level, map] of Object.entries(nm)) {
            var el = document.createElement("option");
            el.textContent = level;
            el.value = map;
            select.appendChild(el);
        }
    }

    function levelChanged() {
        console.log("levelChanged click");
        level = $("#idLevel").val();
        console.log("level=", level);
    }
</script>
