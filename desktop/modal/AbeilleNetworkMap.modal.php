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

    // config/ab::networkMap reminder
    // networkMap[idx] = array(
    //     "level" =>
    //     "mapDir" =>
    //     "mapFile" => "mapX.png"
    // )

    define('maxLevels', 5); // Number of levels
    sendVarToJS('maxLevels', maxLevels);
?>
{{Cette page vous permet de définir les niveaux et plans associés}}.<br><br>

<div class="center">
    <table>
        <tr>
            <!-- <th style="width:20px">{{Index}}</th> -->
            <th style="width:150px">{{Niveau}}</th>
            <th style="width:300px">{{Plan}}</th>
        </tr>
        <?php
        for ($t = maxLevels - 1; $t >= 0; $t--) {
            echo "<tr>";
            // echo "<td>";
            // echo '<input id="idLevelIdx-'.$t.'" type="text" value="">';
            // echo "</td>";
            echo "<td>";
            echo '<input id="idLevel-'.$t.'" type="text" value="">';
            echo "</td>";
            echo "<td>";
            echo '<input id="idMap-'.$t.'" style="width:90%" type="text" value="">';
            echo '<button onclick="addNewMap('.$t.')">+</button>';
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

<br>
<br>
<button onclick="saveLevels()">{{Sauvegarder}}</button>

<script>
    refreshLevels();

    function refreshLevels() {
        console.log("refreshLevels(): networkMap=", networkMap);
        if (typeof networkMap === 'undefined')
            return;

        // config/ab::networkMap reminder
        // networkMap = array(
        //     'levels' => [], array(
        //         "level" =>
        //         "mapDir" =>
        //         "mapFile" => "mapX.png"
        //     ),
        //     'levelChoice' => idx
        // )
        // console.log("networkMap=", networkMap);
        for (const [idx, level] of Object.entries(networkMap.levels)) {
            // elm = document.getElementById("idLevelIdx-"+idx);
            // elm.value = idx;
            elm = document.getElementById("idLevel-"+idx);
            elm.value = level.levelName;
            elm = document.getElementById("idMap-"+idx);
            elm.value = level.mapFile;
            if (idx == maxLevels)
                break;
        }
        newNetworkMap = Object.assign({}, networkMap); // Clone networkMap
    }

    // Check & save config
    function saveLevels() {
        console.log("saveLevels()");

        // Checks & save
        console.log("newNetworkMap=", newNetworkMap);
        levels = newNetworkMap.levels;
        console.log("levels=", levels);
        for (idx = 0; idx < maxLevels; idx++) {
            elm = document.getElementById("idLevel-"+idx);
            level = elm.value;
            elm = document.getElementById("idMap-"+idx);
            map = elm.value;
            if ((level == '') && (map == ''))
                continue; // Empty line
            if ((level == '') || (map == '')) {
                l = idx + 1;
                alert("{{Ligne}} "+l+" {{invalide}}: {{Niveau ou plan vide}}");
                return;
            }

            if (typeof levels[idx] == 'undefined')
                levels[idx] = new Object();
            levels[idx].levelName = level; // Updating level in case changed
        }
        newNetworkMap.levels = levels;
        console.log("newNetworkMap=", newNetworkMap);
        if (newNetworkMap != networkMap) {
            console.log("Saving new networkMap=", newNetworkMap);
            config = new Object();
            config['ab::networkMap'] = newNetworkMap;
            saveConfig(config);
            networkMap = Object.assign(networkMap, newNetworkMap); // Update networkMap
        }
        $('#md_modal').dialog('close')

        location.reload(true); // Reload page
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
                // saveConfig();
                // location.reload(true);

                elm = document.getElementById("idMap-"+idx);
                elm.value = file.name;

                // Updating config with ab::userMap

                // userMap = "tmp/network_maps/" + file.name;
                elm = document.getElementById("idLevel-"+idx);
                levelName = elm.value;
                if (levelName == '') {
                    level = 'Level ' + idx;
                    elm.value = levelName;
                }
                newNetworkMap.levels[idx] = {
                    levelName: levelName,
                    mapDir: "tmp/network_maps",
                    mapFile: file.name
                };
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
