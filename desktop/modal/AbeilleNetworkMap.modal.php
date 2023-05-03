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
    // networkMap[levelX] = "mapX.png"

    define('maxLevels', 10); // Number of levels
    sendVarToJS('maxLevels', maxLevels);
?>
{{Cette page vous permet de définir les niveaux et plans associés}}.<br><br>

<table>
    <tr>
        <th style="width:150px">{{Niveau}}</th>
        <th style="width:300px">{{Plan}}</th>
    </tr>
    <?php
    for ($t = 0; $t < maxLevels; $t++) {
        echo "<tr>";
        echo "<td>";
        echo '<input id="idLevel-'.$t.'" type="text" value="">';
        echo "</td>";
        echo "<td>";
        echo '<input id="idMap-'.$t.'" type="text" value="">';
        echo '<button onclick="addNewMap('.$t.')">+</button>';
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>

<br>
<button onclick="saveLevels()">{{Sauvegarder}}</button>

<script>
    // TEMP
    // networkMap = {
    //     rdc: 'map-rdc.png',
    //     premier: 'map-1er.png'
    // };
    // END TEMP

    refreshLevels();

    function refreshLevels() {
        console.log("refreshLevels(). networkMap=", networkMap);
        if (typeof networkMap === 'undefined')
            return;

        console.log("networkMap=", networkMap);
        s = 0
        for (const [level, map] of Object.entries(networkMap)) {
            elm = document.getElementById("idLevel-"+s);
            elm.value = level;
            elm = document.getElementById("idMap-"+s);
            elm.value = map;
            s++;
            if (s == maxLevels)
                break;
        }
    }

    // Check & save config
    function saveLevels() {
        console.log("saveLevels()");

        for (s = 0; s < maxLevels; s++) {
            elm = document.getElementById("idLevel-"+s);
            level = elm.value;
            elm = document.getElementById("idMap-"+s);
            map = elm.value;
            if ((level == '') && (map == ''))
                continue; // Empty line
            if ((level == '') || (map == '')) {
                alert("{{Ligne}} "+s+" {{invalide}}: {{Niveau ou plan vide}}");
                continue;
            }
        }
    }

    // Add new map to index 'idx'
    function addNewMap(idx) {
        console.log("addNewMap()");

        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.png';
        input.onchange = e => {

            var file = e.target.files[0];
            // file.name = the file's name including extension
            // file.size = the size in bytes
            // file.type = file type ex. 'application/pdf'
            console.log("file=", file);

            var formData = new FormData();
            formData.append("file", file);
            formData.append("destDir", "tmp/network_maps"); // Maps are stored in local 'tmp/network_maps' dir
            // formData.append("destName", "Level0.png");

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "plugins/Abeille/core/php/AbeilleUpload.php", true);
            xhr.onload = function (oEvent) {
                console.log("oEvent=", oEvent);
                if (xhr.status != 200) {
                    console.log("Error " + xhr.status + " occurred when trying to upload your file.");
                    return;
                }
                console.log("Uploaded !");
                // Updating config with ab::userMap
                userMap = "tmp/network_maps/" + file.name;
                // saveConfig();
                // location.reload(true);

                elm = document.getElementById("idMap-"+idx);
                elm.value = file.name;
            };
            xhr.send(formData);
        }
        input.click();
    }

    // function levelChanged() {
    //     console.log("levelChanged())");
    //     level = $("#idLevel").val();
    //     console.log("level=", level);
    // }

    // Remove current selected level
    // function levelRemove() {
    //     select = document.getElementById("idLevel");
    //     index = select.selectedIndex;
    //     console.log("levelRemove(index="+index+")");
    //     if (index < 0)
    //         return;
    //     select.remove(index);
    // }

    // Add a new level
    // function levelAdd() {
    //     value = document.getElementById("idLevelNew").value;
    //     console.log("levelAdd(value="+value+")");
    //     if (value == '')
    //         return;

        // TODO: Ignore if level already exists

    //     var el = document.createElement("option");
    //     el.textContent = value;
    //     el.value = value;
    //     select.appendChild(el);
    // }
</script>
