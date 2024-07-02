<?php
    require_once __DIR__.'/../../core/config/Abeille.config.php';

    if (file_exists(dbgFile)) {
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    require_once __DIR__.'/../../../../core/php/core.inc.php';

    // Reading URL parameter: "...?zigate=X", where X is zigate number
    // if (isset($_GET['zigate']))
    //     $zgId = $_GET['zigate'];
    // else
    //     $zgId = 1; // Default = zigate1. TODO: Should be the 1st enabled, not the 1
    // sendVarToJS('zgId', $zgId);

    // Filling 'networks'
    $networks = [];
    for ($zgId = 1; $zgId < maxNbOfZigate; $zgId++) {
        if (config::byKey('ab::gtwEnabled'.$zgId, 'Abeille', 'N') != 'Y')
            continue;
        $networks[] = array(
            'zgId' => $zgId
        );
    }
    sendVarToJS('networks', $networks);

    // Selecting background map
    // config/ab::networkMap reminder
    // networkMap = array(
    //     'levels' => [], array(
    //         "levelName" =>
    //         "mapDir" =>
    //         "mapFile" => "mapX.png"
    //     ),
    //     'levelChoice' => idx
    // )
    // Note: Peek only few fields allows to cleanup format
    // $nm = config::byKey('ab::networkMap', 'Abeille', [], true);
    // logDebug('nm='.json_encode($nm));
    // $networkMap = [];
    // $networkMap['levels'] = [];
    // if (isset($nm['levels'])) {
    //     foreach ($nm['levels'] as $nml) {
    //         if (!isset($nml['levelName']) || !isset($nml['mapDir']) || !isset($nml['mapFile']))
    //             continue;
    //         $networkMap['levels'][] = $nml;
    //     }
    // }
    $networkMap = config::byKey('ab::networkMap', 'Abeille', [], true);
    logDebug('networkMap='.json_encode($networkMap, JSON_UNESCAPED_UNICODE));
    if (!isset($networkMap['levels']))
        $networkMap['levels'] = [];
    // Set default level & image if none
    if (count($networkMap['levels']) == 0) {
        $networkMap['levels'][] = array(
            'levelName' => 'Level 0',
            'mapDir' => 'images',
            'mapFile' => 'AbeilleNetworkMap-1200.png'
        );
    }
    if (!isset($networkMap['levelChoice']))
        $networkMap['levelChoice'] = 0;
    else if (gettype($networkMap['levelChoice']) == "string")
        $networkMap['levelChoice'] = intval($networkMap['levelChoice']); // In case stored as string

    // Checking that image exists & fills $viewImages[]
    $viewImages = [];
    foreach ($networkMap['levels'] as $levIdx => $lev) {
        $relPath = (isset($lev['mapDir']) ? $lev['mapDir'] : 'images');
        $relPath .= '/';
        $relPath .= (isset($lev['mapFile']) ? $lev['mapFile'] : 'AbeilleNetworkMap-1200.png');
        $path = __DIR__.'/../../'.$relPath;
        logDebug("path=${path}");
        if (!file_exists($path)) {
            $networkMap['levels'][$levIdx]['mapDir'] = 'images';
            $networkMap['levels'][$levIdx]['mapFile'] = 'AbeilleNetworkMap-1200.png';
        }

        $iSize = getimagesize(__DIR__."/../../".$relPath);
        $width = $iSize[0];
        $height = $iSize[1];
        $viewImages[] = Array(
            "path" => $relPath, // Path relative to Abeille's root
            "width" => $width,
            "height" => $height
        );
    }
    logDebug('networkMap='.json_encode($networkMap, JSON_UNESCAPED_UNICODE));

    sendVarToJS('networkMap', $networkMap);
    sendVarToJS('viewLevel', $networkMap['levelChoice']);
    sendVarToJS('viewImages', $viewImages);
?>

<html>
<head>
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
</head>

<body>
    <style>
        html, body{
            height: 100%;
        }
        .ab-container {
            height: 100%;
            display: block;
        }
        .ab-top {
            background: #e9e9e9;
            font-weight: bold;
            padding: .4em 1em;
            margin-bottom: 8px;
        }
        .ab-bottom {
            height: 100%;
        }
        .ab-left-column, .ab-right-column{
        }
        .ab-left-column {
            float: left;
            height: 100%;
            width: 110px;
            margin-right:8px;
        }
        .ab-right-column {
            width: auto;
            height: 100%;
            background-size: contain;
            background-repeat: no-repeat;
        }
        .disabledDiv {
            pointer-events: none;
            opacity: 0.4;
        }
        .draggable {
            cursor: move;
        }
        #idNodeMenuSelect {
            position: absolute;
            top: 60px;
            left: 10px;
        }
        .typeCoordinator-color {
        color: #a65ba6;
        }
        .typeEndDevice-color {
            color: #7BCC7B;
        }
        .typeRouter-color {
            color: #00a2e8;
        }
        .typeUndefined-color {
            color: #E5E500;
        }
</style>

    <!-- <div class="row"> -->
    <div class="ab-container">
        <div class="ab-top">
            Placement réseau
        </div>
        <div class="ab-bottom">
            <div class="column ab-left-column">
                <div id="idDisplayPart">
                    <label>{{Affichage}}</label><br>
                    <!-- Level choice if more than 1 level -->
                    <?php
                        $count = count($networkMap['levels']);
                        if ($count > 1) {
                            echo '<select id="idSelectLevel">';
                            for ($l = 0; $l < $count; $l++ ) {
                                $level = $networkMap['levels'][$l];
                                if ($l == $networkMap['levelChoice'])
                                    $selected = "selected";
                                else
                                    $selected = "";
                                echo '<option value='.$l.' '.$selected.'>'.$level['levelName'].'</option>'."\n";
                            }
                            echo '</select>';
                        }

                        for ($n = 0; $n < count($networks); $n++ ) {
                            $zgId = $networks[$n]['zgId'];

                            echo '<input type="checkbox" class="viewNet" id="idViewNet-'.$n.'" checked><label>Abeille '.$zgId.'</label><br>';
                        }
                    ?>

                    <!-- View options -->
                    <input type="checkbox" id="idViewLinks" checked title="{{Affiche les liens entre équipements}}"><label>{{Liens}}</label>
                    <button id="idRefreshLqi" style="width:100%;margin-right:7px" title="{{Force l'analyse du réseau}}">{{Analyser}}</button>
                </div>

                </br>
                <table class="table table-bordered table-condensed">
                    <thead>
                    <tr>
                        <th colspan="2">{{Légende}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="typeCoordinator-color"><i class="fas fa-square"></i></td>
                        <td>{{Coordinateur}}</td>
                    </tr>
                    <tr>
                        <td class="typeRouter-color"><i class="fas fa-square"></i></td>
                        <td>{{Routeur}}</td>
                    </tr>
                    <tr>
                        <td class="typeEndDevice-color"><i class="fas fa-square"></i></td>
                        <td>{{Bout de chaine}}</td>
                    </tr>
                    <tr>
                        <td class="typeUndefined-color"><i class="fas fa-square"></i></td>
                        <td>{{Type inconnu}}</td>
                    </tr>
                    <tr>
                        <td><img src="/plugins/Abeille/images/death.png" height="20px"></td>
                        <td>{{Sans vie}}</td>
                    </tr>
                    </tbody>
                </table>

            </br>
                <button id="idConfigMode" style="width:100%;margin-top:4px;margin-right:7px">{{Mode config}}</button>
                </br>
                </br>
                </br>
                <div id="idConfigPart" class="disabledDiv">
                    <label>{{Configuration}}</label></br>
                    <!-- <button id="save" onclick="saveCoordinates()" style="width:100%;margin-top:4px">{{Sauver}}</button> -->
                    <!-- <button id="map" onclick="uploadMap()" style="width:100%;margin-top:4px">{{Plan}}</button> -->
                    <button id="idMapsBtn" style="width:100%;margin-top:4px;margin-right:7px">{{Plans}}</button>
                    <!-- TODO: Text size (to be stored in DB) -->
                </div>
            </div>

            <!-- SVG reminder
            viewPort = visible area of the SVG image. So should allow to see the full SVG.
                Controlled with 'width' and 'height' attributes.
                <svg width="500" height="300"></svg>
            viewBox = zoom in/out part of the viewPort
            -->

            <div class="column ab-right-column">
                <!-- <svg id="idDevices" class="ab-right-column" xmlns="http://www.w3.org/2000/svg" width="100%" height="auto" onload="makeDraggable(evt)"> -->
                <!-- Useful doc https://www.petercollingridge.co.uk/tutorials/svg/interactive/dragging/ -->
                <svg id="idGraph" xmlns="http://www.w3.org/2000/svg" width="100%" height="auto" onload="makeDraggable(evt)">
                </svg>

                <!-- Node menu visible in config mode on nodeMenuBtn click -->
                <select id="idNodeMenuSelect" style="width:100px" hidden>
                <?php
                    foreach ($networkMap['levels'] as $levIdx => $lev) {
                        $levelName = $lev['levelName'];
                        echo "<option value=\"${levIdx}\">${levelName}</option>";
                    }
                ?>
                </select>
            </div>
        </div>
    </div>
</body>
</html>

<script type="text/javascript">

    console.log("networkMap=", networkMap);
    console.log("viewLevel=", viewLevel);
    console.log("viewImages=", viewImages);

    // Edition mode
    $("#idConfigMode").on("click", function() {
        console.log("idConfigMode click: configMode=", configMode);
        configModeBtn = document.getElementById("idConfigMode");
        displayPart = document.getElementById("idDisplayPart");
        configPart = document.getElementById("idConfigPart");
        if (configMode) {
            displayPart.classList.remove("disabledDiv"); // Reenable display part
            configPart.classList.add("disabledDiv");
            viewLinks = document.getElementById("idViewLinks").checked;
            configMode = false;
            configModeBtn.innerHTML = "Mode config";

            let nodeMenuSelect = document.getElementById('idNodeMenuSelect');
            nodeMenuSelect.style.display = 'none';
        } else {
            displayPart.classList.add("disabledDiv");
            configPart.classList.remove("disabledDiv"); // Reenable config part
            viewLinks = false;
            configMode = true;
            configModeBtn.innerHTML = "Mode normal";
        }
        refreshPage();
    });

    // $("#idDevices").on("load", function() {
    //     console.log("idDevices click");
    //     if (configMode)
    //         makeDraggable(evt);
    // });

    // Display given 'viewLevel'
    function refreshBackgroundMap(viewLevel) {
        // lev = networkMap.levels[viewLevel];
        console.log("refreshBackgroundMap("+viewLevel+")");
        // viewImage.path = lev.mapDir + "/" + lev.mapFile;
        console.log("viewImages=", viewImages);

        elm = document.getElementById("idGraph");
        elm.style.backgroundImage = 'url("/plugins/Abeille/'+viewImages[viewLevel].path+'")';
        console.log("idGraph elm=", elm);

        w = viewImages[viewLevel].width;
        h = viewImages[viewLevel].height;
        // elm.setAttribute("style", "width:"+w+"px;height:"+h+"px");
        elm.style.width = w+"px";
        elm.style.height = h+"px";

        // Get size of 'div' #idGraph to get image size
        var rectangle = elm.getBoundingClientRect();
        // x = rectangle.left;
        // y = rectangle.top;
        w = rectangle.width;
        h = rectangle.height;
        viewImageMaxX = w;
        viewImageMaxY = h;
        console.log("width=" + w + ", height=" + h);

        // elm.setAttribute("viewBox", "0 0 "+w+" "+h);
    };

    $("#idSelectLevel").on("change", function() {
        viewLevel = document.getElementById("idSelectLevel").value;
        console.log("View level changed to "+viewLevel);

        // Refresh page to display equipments on this level
        refreshPage();

        // Saving user choice
        networkMap.levelChoice = viewLevel;
        config = new Object();
        config['ab::networkMap'] =  networkMap;
        saveConfig(config);
    });

    /* Network display changed */
    $(".viewNet").on("change", function (event) {
        console.log("viewNet click");
        // viewLinks = document.getElementById("idViewLinks").checked;
        refreshPage();
    });

    /* Links display option changed */
    $("#idViewLinks").on("change", function (event) {
        console.log("idViewLinks click");
        viewLinks = document.getElementById("idViewLinks").checked;
        refreshPage();
    });

    // Click on 'Refesh LQI' button
    $("#idRefreshLqi").on("click", refreshLqi);

    /* Launch network scan for current Zigate */
    function refreshLqi() {
        console.log("refreshLqi()");

        $("#md_modal").dialog({ title: "{{Analyse du réseau}}" });
        $("#md_modal")
            .load("index.php?v=d&plugin=Abeille&modal=AbeilleRefreshLQI.modal")
            .dialog("open")
            .dialog("option", "width", 700)
            .dialog("option", "height", 350);

        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                networkInformation = this.responseText;
                console.log("refreshLqi() ended: ", networkInformation);
                clearInterval(refreshLqiStatus);

                getLqiTables(); // Retrieve all LQI tables
                buildDevList();
                button = document.getElementById("idRefreshLqi");
                button.removeAttribute('disabled'); // Reenable button

                refreshPage();
            }
        };
        // xhr.open("GET", "/plugins/Abeille/core/php/AbeilleLQI.php?zigate="+zgId, true);
        xhr.open("GET", "/plugins/Abeille/core/php/AbeilleLQI.php", true); // Updating all zigates
        xhr.send();
        button = document.getElementById("idRefreshLqi");
        button.setAttribute('disabled', true); // Disable button. Value is don't care

        /* Start refresh status every 1sec */
        refreshLqiStatus = setInterval(function() { refreshLqiProgress(); }, 1000);  // ms
    }

    function refreshLqiProgress() {
        console.log("refreshLqiProgress("+Ruche+")");

        // var d = new Date();
        // var xhrProgress = new XMLHttpRequest();
        // xhrProgress.onreadystatechange = function() {
        //     if (this.readyState == 4 && this.status == 200) {
        //         networkInformationProgress = this.responseText;
        //         // console.log("Debug - Progress:"+networkInformationProgress);
        //     }
        // };
        // xhrProgress.open("GET", "/plugins/Abeille/tmp/AbeilleLQI_MapData"+Ruche+".json.lock?"+d.getTime(), true);
        // xhrProgress.send();

        $.ajax({
            // type: 'GET',
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: 'getTmpFile',
                file : "AbeilleLQI.lock",
            },
            dataType: "json",
            global: false,
            cache: false,
            error: function (request, status, error) {
                console.log("ERROR: Call to getTmpFile failed");
                // $('#div_networkZigbeeAlert').showAlert({
                //     message: "ERREUR ! Problème du lecture du fichier de lock.",
                //     level: 'danger'
                // });
                // _autoUpdate = 0;
                // setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
            },
            success: function (json_res) {
                console.log("json_res=", json_res);
                res = JSON.parse(json_res.result); // res.status, res.error, res.content
                if (res.status != 0) {
                    // var msg = "ERREUR ! Quelque chose s'est mal passé ("+res.error+")";
                    // $('#div_networkZigbeeAlert').showAlert({ message: msg, level: 'danger' });
                    // _autoUpdate = 0;
                    // setTimeout(function () { $('#div_networkZigbeeAlert').hide(); }, 10000);
                } else {
                    var data = res.content;
                    console.log("Status='"+data+"'");
                    if (data.toLowerCase().includes("oops")) {
                        // networkInformationProgress = data;
                        document.getElementById("refreshInformation").value = data;
                    } else if (data.toLowerCase().includes("done")) {
                        // Reminder: done/<timestamp>/<status
                        // networkInformationProgress = "Collecte terminée";
                        document.getElementById("refreshInformation").value = "Collecte terminée";
                        clearInterval(refreshLqiStatus);
                    } else
                        networkInformationProgress = data;
                        // document.getElementById("refreshInformation").value = data;
                }
            }
        });
    }

    // Ensure that device coordinates are within background map size
    function checkGrpLimits(grpX, grpY) {
        if (grpX < 0) grpX = 0;
        // console.log("  viewImageMaxX=", viewImageMaxX);
        if ((grpX + 50) > viewImageMaxX)
            grpX = viewImageMaxX - 50;
        if (grpY < 0) grpY = 0;
        // console.log("  viewImageMaxY=", viewImageMaxY);
        if ((grpY + 50) > viewImageMaxY)
            grpY = viewImageMaxY - 50;

        console.log("  grpX="+grpX+", grpY="+grpY);
        return {
            x: grpX,
            y: grpY
        };
    }

    // Ensure that device coordinates are within background map size
    // Note: Requires 'viewImageMaxX' set by 'refreshBackgroundMap()'
    function checkPosLimits(devLogicId) {
        dev = devList[devLogicId];
        posX = dev['posX'];
        posY = dev['posY'];
        grpX = posX - 25;
        grpY = posY - 25;

        if (grpX < 0) grpX = 0;
        // console.log("  viewImageMaxX=", viewImageMaxX);
        if ((grpX + 50) > viewImageMaxX)
            grpX = viewImageMaxX - 50;
        if (grpY < 0) grpY = 0;
        // console.log("  viewImageMaxY=", viewImageMaxY);
        if ((grpY + 50) > viewImageMaxY)
            grpY = viewImageMaxY - 50;

        // console.log("  grpX="+grpX+", grpY="+grpY);
        dev['posX'] = grpX + 25;
        dev['posY'] = grpY + 25;
    }

    // Identify network index from 'devLogicId'
    function devLogicId2Net(devLogicId) {
        net = devLogicId.split('/');
        netName = net[0]; // Ex: 'AbeilleX'
        zgId = netName.substring(7);
        for (n = 0; n < networks.length; n++) {
            if (networks[n].zgId == zgId)
                return n;
        }

        return 0; // Should return an error
    }

    // Thanks to http://www.petercollingridge.co.uk/tutorials/svg/interactive/dragging/
    function makeDraggable(evt) {
        console.log("makeDraggable(), configMode="+configMode);

        var svg = evt.target;
        svg.addEventListener('mousedown', startDrag, false);
        svg.addEventListener('mousemove', drag, false);
        svg.addEventListener('mouseup', endDrag, false);
        svg.addEventListener('mouseleave', endDrag);

        // Get mouse coordinates relative to the svg viewport and not the screen
        function getMousePosition(evt) {
            var CTM = svg.getScreenCTM();
            return {
                x: (evt.clientX - CTM.e) / CTM.a,
                y: (evt.clientY - CTM.f) / CTM.d
            };
        }

        // Called on 'mousedown' event
        function startDrag(evt) {

            // Icon image should be draggable in config mode
            if (!evt.target.classList.contains('draggable'))
                return;

            // parentG = evt.target.parentNode;
            // if (!parentG.classList.contains('draggable'))
            //     return;

            console.log("startDrag(), evt=", evt);

            parentG = evt.target.parentNode;
            selectedElement = parentG; // Element to drag
            console.log("  selectedElement= ", selectedElement);

            mousePos = getMousePosition(evt);
            console.log("  Mouse pos: "+JSON.stringify(mousePos));
            // rect = selectedElement.childNodes.rect;
            // rectCoord = selectedElement.getBoundingClientRect();
            // console.log("  rectCoord=", rectCoord);

            // offset = getMousePosition(evt);
            // console.log("  Mouse pos: "+JSON.stringify(offset));
            // elmCoord = selectedElement.getBoundingClientRect();
            // console.log("  elmCoord=", elmCoord);
            // offset.x -= elmCoord.x;
            // offset.y -= elmCoord.y;
            // console.log("  Mouse offset: "+JSON.stringify(offset));

            // Get all the transforms currently on this element
            var transforms = selectedElement.transform.baseVal;
            console.log("  transforms=", transforms);
            // Ensure the first transform is a translate transform
            if (transforms.length === 0 ||
                transforms.getItem(0).type !== SVGTransform.SVG_TRANSFORM_TRANSLATE) {
                // Create an transform that translates by (0, 0)
                var translate = svg.createSVGTransform();
                translate.setTranslate(0, 0);
                // Add the translation to the front of the transforms list
                selectedElement.transform.baseVal.insertItemBefore(translate, 0);
                // console.log("  transforms bis=", transforms);
            }

            // Get initial translation amount
            transform = transforms.getItem(0); // Note: 1st item is supposed to be 'translate'
            console.log("  transform=", transform);
            offset = getMousePosition(evt);
            console.log("  Mouse pos: "+JSON.stringify(offset));
            offset.x -= transform.matrix.e;
            offset.y -= transform.matrix.f;
            // offset.x -= 10;
            // offset.y -= 10;
            // elmCoord = selectedElement.getBoundingClientRect();
            // console.log("  elmCoord=", elmCoord);
            // offset.x -= elmCoord.x;
            // offset.y -= elmCoord.y;
            console.log("  Mouse offset: "+JSON.stringify(offset));

            // mouseCoord = getMousePosition(evt);
            // offset.x = mouseCoord.x - parseFloat(selectedElement.getAttributeNS(null, "x"));
            // offset.y = mouseCoord.y - parseFloat(selectedElement.getAttributeNS(null, "y"));

            // parentG = evt.target.parentNode;
            // if (parentG.classList.contains('draggable')) {
            //     offset = getMousePosition(evt); // Position du clic de souris dans les coordonnées SVG.
            //     console.log("Current position: "+JSON.stringify(offset));

            //     // Make sure the first transform on the element is a translate transform
            //     // Should not be needed as Abeille have a Translate
            //     selectedElement = parentG;
            //     var transforms = selectedElement.transform.baseVal;

            //     if (transforms.length === 0 || transforms.getItem(0).type !== SVGTransform.SVG_TRANSFORM_TRANSLATE) {
            //         // Create an transform that translates by (0, 0)
            //         console.log("ERROR: Need a TRANSLATE - NOT expected");
            //         var translate = svg.createSVGTransform();
            //         translate.setTranslate(0, 0);
            //         selectedElement.transform.baseVal.insertItemBefore(translate, 0);
            //     }

            //     // Get initial translation from Abeille Object
            //     transform = transforms.getItem(0);
            //     offset.x -= transform.matrix.e;
            //     offset.y -= transform.matrix.f;

            //     console.log("New position: "+JSON.stringify(offset));
            // }
        }

        function moveIt(evt, dragEnd) {
            // rect = selectedElement.target.parentNode.childNodes.rect;
            // rectCoord = selectedElement.getBoundingClientRect();
            // console.log("  rectCoord=", elmCoord);

            evt.preventDefault();
            // Updates the translation transform to the mouse position minus the offset
            var mousePos = getMousePosition(evt);
            console.log("  Mouse pos=", mousePos);
            grpX = mousePos.x - offset.x;
            grpY = mousePos.y - offset.y;
            // Check limits
            grpCoord = checkGrpLimits(grpX, grpY);
            transform.setTranslate(grpCoord['x'], grpCoord['y']);

            devLogicId = selectedElement.id; // Ex: 'AbeilleX/yyyy'
            // console.log("TOTO devLogicId=", devLogicId);
            netIdx = devLogicId2Net(devLogicId);
            // console.log("TOTO netIdx=", netIdx);
            devList = networks[netIdx].devList;
            // console.log("TOTO devList=", devList);

            // Moving connected lines no longer required since not displayed during config mode
            // // Moving connected lines
            // dev = devList[devLogicId];
            // console.log("TOTO dev=", dev);
            // lineX = grpX + 25; // Center in 50x50 square
            // lineY = grpY + 25; // Center in 50x50 square
            // for (linkId in dev['links']) {
            //     link = linksList[linkId];
            //     var line = document.getElementById("idLink-"+linkId);
            //     if (line == null) {
            //         console.log("INTERNAL ERROR: 'idLink-"+linkId+"' not found.");
            //         return;
            //     }
            //     if (devLogicId == link.src) {
            //         line.x1.baseVal.value = lineX;
            //         line.y1.baseVal.value = lineY;
            //     } else {
            //         line.x2.baseVal.value = lineX;
            //         line.y2.baseVal.value = lineY;
            //     }
            // }

            if (dragEnd) {
                // Update coordinates
                // devLogicId = selectedElement.id;
                devList[devLogicId]['posX'] = grpX + 25;
                devList[devLogicId]['posY'] = grpY + 25;
                // devList[devLogicId]['posChanged'] = true;

                saveCoordinates(netIdx, devLogicId);

                selectedElement = false;
            }
        }

        // 'mousemove'
        function drag(evt) {
            // console.log("drag(), evt=", evt);
            // var coord = getMousePosition(evt);
            // console.log("  Mouse pos=", coord);
            // elmCoord = selectedElement.getBoundingClientRect();
            // console.log("drag(), elmCoord=", elmCoord);

            // return;

            if (!selectedElement)
                return;

            console.log("drag(), evt=", evt);
            moveIt(evt, false);
        }

        // 'mouseup' or 'mouseleave'
        function endDrag(evt) {
            if (!selectedElement)
                return;

            console.log("endDrag(), evt=", evt);
            moveIt(evt, true);
        }
    }

    // function selectSource(form) {
    //     Source = form.list.value;
    //     refreshAll();
    // }

    // function selectDestination(form) {
    //     Destination = form.list.value;
    //     refreshAll();
    // }

    // function selectDetails(form) {
    //     Parameter = form.list.value;
    //     // console.log("selectDetails function: Parameter: " + Parameter );
    //     refreshAll();
    // }

    // function selectParent(form) {
    //     Hierarchy = form.list.value;
    //     refreshAll();
    // }

    // function filtreSource() {
    //     document.write( '<FORM NAME="myformSource" ACTION="" METHOD="GET">');
    //     document.write( '<SELECT NAME="list" >');
    //     document.write( '<OPTION value="All" selected>All</OPTION>');
    //     document.write( '<OPTION value="None" >None</OPTION>');

    //     for (shortAddress in jeedomDevices) {
    //         document.write( '<OPTION value="'+shortAddress+'" >'+jeedomDevices[shortAddress].name+'</OPTION>');
    //     }

    //     document.write( '</SELECT>');
    //     // document.write( '</br>');
    //     document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectSource(this.form)"/>');
    //     document.write( '</FORM>');
    // }

    // function filtreDestination() {
    //     document.write( '<FORM NAME="myformDestination" ACTION="" METHOD="GET">');
    //     document.write( '<SELECT NAME="list" >');
    //     document.write( '<OPTION value="All" selected>All</OPTION>');
    //     document.write( '<OPTION value="None" >None</OPTION>');

    //     for (shortAddress in jeedomDevices) {
    //         // console.log("Name: " + JSON.stringify(shortAddress));
    //         document.write( '<OPTION value="'+shortAddress+'" >'+jeedomDevices[shortAddress].name+'</OPTION>');
    //     }

    //     document.write( '</SELECT>');
    //     // document.write( '</br>');
    //     document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectDestination(this.form)"/>');
    //     document.write( '</FORM>');
    // }

    // function filtreDetails() {
    //     var dataList = [ "LinkQualityDec", "Depth", "Voisine", "IEEE_Address", "Type", "Relationship", "Rx" ];

    //     document.write( '<FORM NAME="myformDetails" ACTION="" METHOD="GET">');
    //     document.write( '<SELECT NAME="list" >');

    //     for (data in dataList) {
    //         document.write( '<option value="'+dataList[data]+'">'+dataList[data]+'</option>');
    //     }

    //     document.write( '</SELECT>');
    //     // document.write( '</br>');
    //     document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectDetails(this.form)"/>');
    //     document.write( '</FORM>');
    // }

    // function filtreParent() {
    //     var dataList = [ "All", "Sibling", "Child" ];

    //     document.write( '<FORM NAME="myformParent" ACTION="" METHOD="GET">');
    //     document.write( '<SELECT NAME="list" >');

    //     for (data in dataList) {
    //         // if ( $Data==$item ) { $selected = " selected "; } else { $selected = " "; }
    //         document.write( '<option value="'+dataList[data]+'">'+dataList[data]+'</option>');
    //     }

    //     document.write( '</SELECT>');
    //     // document.write( '</br>');
    //     document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectParent(this.form)">');
    //     document.write( '</FORM>');
    // }

    // function ReLoad() {
    //     // to be implemented
    //     location.reload(true);
    // }

    // function refreshNetwork(newZgId) {
    //     // window.open("index.php?v=d&m=Abeille&p=AbeilleSupport");
    //     // window.open("plugins/Abeille/desktop/php/AbeilleNetworkGraph.php?zigate="+zgId);

    //     var url = window.location.href;
    //     console.log("url="+url);
    //     idx = url.indexOf('zigate='+zgId);
    //     if (idx === -1) {
    //         url += '&zigate='+newZgId
    //     } else {
    //         console.log("idx="+idx);
    //         url = url.replace('zigate='+zgId, 'zigate='+newZgId);
    //         // url += '?param=1'
    //     }
    //     // location.reload(true);
    //     window.location.href = url;
    // };

    function getLqiTables() {
        console.log("getLqiTables()");

        // var xmlhttp = new XMLHttpRequest();
        // xmlhttp.onreadystatechange = function() {
        //     if (this.readyState == 4 && this.status == 200) {
        //         lqiTable = JSON.parse(this.responseText);
        //     }
        // };

        // xmlhttp.open("GET", "/plugins/Abeille/tmp/AbeilleLQI_MapData"+Ruche+".json", false); // False pour bloquer sur la recuperation du fichier
        // xmlhttp.send();

        for (n = 0; n < networks.length; n++) {
            netw = networks[n];
            zgId = netw.zgId;
            $.ajax({
                type: 'POST',
                url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
                data: {
                    action: 'getTmpFile',
                    file : "AbeilleLQI-Abeille"+zgId+".json",
                },
                dataType: "json",
                global: false,
                cache: false,
                async: false,
                error: function (request, status, error) {
                    console.log("ERROR: Call to getTmpFile failed ("+error+").");
                },
                success: function (json_res) {
                    res = JSON.parse(json_res.result);
                    if (res.status != 0) {
                        // var msg = "ERREUR ! Qqch s'est mal passé.\n"+res.error;
                        // $('#div_networkZigbeeAlert').showAlert({message: msg, level: 'danger'});
                        console.log("ERROR: returned status="+res.status);
                        bootbox.alert("Pas de données du réseau.<br>Votre boxe a probablement redémarré depuis hier.<br><br>Veuillez forcer l'analyse du réseau.")
                    } else if (res.content == "") {
                        // $('#div_networkZigbeeAlert').showAlert({message: '{{Fichier vide. Rien à traiter}}', level: 'danger'});
                        console.log("ERROR: empty content");
                    } else {
                        netw.lqiTable = JSON.parse(res.content);
                        console.log("Net "+n+" lqiTable=", netw.lqiTable);
                    }
                },
            });
        }
    }

    // Retrieve list of devices registerd under Jeedom
    // Note: BLOCKING function
    function getJeedomDevices() {
        // var xhr = new XMLHttpRequest();
        // xhr.onreadystatechange = function() {
        //     if (this.readyState == 4 && this.status == 200) {
        //         jeedomDevices = JSON.parse(this.responseText);
        //         console.log("jeedomDevices=", jeedomDevices);
        //     }
        // };
        // xhr.open("GET", "/plugins/Abeille/core/php/AbeilleGetEq.php", false);
        // xhr.send();

        $.ajax({
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/Abeille.ajax.php",
            data: {
                action: 'getEqList'
            },
            dataType: "json",
            global: false,
            cache: false,
            async: false, // Blocking function
            error: function (request, status, error) {
                console.log("ERROR: Call to getEqList failed");
            },
            success: function (json_res) {
                console.log("json_res=", json_res);
                res = JSON.parse(json_res.result); // res.status, res.error, res.eqList
                if (res.status != 0) {
                } else {
                    jeedomDevices = res.eqList;
                    console.log("jeedomDevices=", jeedomDevices);
                }
            }
        });
    }

    /* eqLogic/configuration settings (ab::settings) update */
    function saveCoordinates(netIdx, devLogicId) {
        console.log("saveCoordinates("+netIdx+", "+devLogicId+")");

        devList = networks[netIdx].devList;
        dev = devList[devLogicId];
        // if (typeof dev['posChanged'] === "undefined")
        //     return; // Unknown to Jeedom

        // if (!dev['posChanged'])
        //     return; // No change

        console.log("Saving coordinates for '"+dev['name']+"'");
        eqId = dev['jeedomId'];
        settings = {
            physLocationX: dev['posX'],
            physLocationY: dev['posY'],
            physLocationZ: dev['posZ'], // Level index
        };
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'saveSettings', // TODO: Use saveEqConfig instead
                eqId: eqId,
                settings: JSON.stringify(settings)
            },
            dataType: 'json',
            global: false,
            success: function (json_res) {
            }
        });
    }

    /* 'config' DB update */
    function saveConfig() {
        console.log("saveConfig(): config=", config);

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'saveConfig',
                config: JSON.stringify(config)
            },
            dataType: 'json',
            global: false,
            success: function (json_res) {
            }
        });
    }

    // Returns color according to Zigbee device type
    function getDevColor(zbType) {
        if (zbType == "Coordinator")
            color = "#a65ba6";
        else if (zbType == "Router")
            color = "#00a2e8";
        else if (zbType == "End Device")
            color = "#7BCC7B";
        else
            color = "#E5E500";
        return color;
    }

    // Build a device list per network, combining LQI table + Jeedom infos
    function buildDevList() {
        console.log("buildDevList()");

        // if (typeof lqiTable === "undefined") {
        //     console.log("NO LQI table");
        //     return;
        // }

        for (n = 0; n < networks.length; n++) {
            netw = networks[n];
            netw.devList = new Object();
            netw.devListNb = 0;
            netw.linksList = new Object();

            lineId = 0; // To identify lines connecting nodes

            // Listing devices on the network (from lqiTable)
            for (rLogicId in netw.lqiTable.routers) {
                router = netw.lqiTable.routers[rLogicId];
                // console.log("router " + rLogicId + "=", router);
                if (typeof netw.devList[rLogicId] !== "undefined") {
                    // Already registered
                    devR = netw.devList[rLogicId];
                } else {
                    devR = new Object();
                    devR['addr'] = router['addr'];
                    devR['name'] = router['name'];
                    devR['icon'] = router['icon'];
                    if (devR['addr'] == '0000') {
                        devR['color'] = getDevColor("Coordinator"); // Coordinator
                    } else {
                        devR['color'] = getDevColor("Router"); // Router
                    }
                    if (typeof jeedomDevices[rLogicId] !== "undefined") {
                        devR['posX'] = jeedomDevices[rLogicId].x;
                        devR['posY'] = jeedomDevices[rLogicId].y;
                        devR['posZ'] = (typeof jeedomDevices[rLogicId].z !== "undefined") ? jeedomDevices[rLogicId].z : 0;
                        devR['jeedomId'] = jeedomDevices[rLogicId].id;
                        // devR['posChanged'] = false;
                    } else {
                        devR['posX'] = 0;
                        devR['posY'] = 0;
                        devR['posZ'] = 0;
                    }
                    devR['links'] = new Object();

                    netw.devList[rLogicId] = devR;
                    netw.devListNb++;
                }

                if (typeof router['dead'] !== "undefined")
                    devR['dead'] = true;

                for (nLogicId in router.neighbors) {
                    neighbor = router.neighbors[nLogicId];
                    // console.log("neighbor=", neighbor);
                    if (typeof netw.devList[nLogicId] !== "undefined") {
                        // Already registered
                        devN = netw.devList[nLogicId];
                    } else {
                        devN = new Object();
                        devN['addr'] = neighbor['addr'];
                        devN['name'] = neighbor['name'];
                        devN['icon'] = neighbor['icon'];
                        devN['color'] = getDevColor(neighbor['type'])
                        if (typeof jeedomDevices[nLogicId] !== "undefined") {
                            devN['posX'] = jeedomDevices[nLogicId].x;
                            devN['posY'] = jeedomDevices[nLogicId].y;
                            devN['posZ'] = (typeof jeedomDevices[nLogicId].z !== "undefined") ? jeedomDevices[nLogicId].z : 0;
                            devN['jeedomId'] = jeedomDevices[nLogicId].id;
                            // devN['posChanged'] = false;
                        } else {
                            devN['posX'] = 0;
                            devN['posY'] = 0;
                            devN['posZ'] = 0;
                        }
                        devN['links'] = new Object();

                        netw.devList[nLogicId] = devN;
                        netw.devListNb++;
                    } // End for (nLogicId in router.neighbors)

                    if (typeof neighbor['dead'] !== "undefined")
                        devN['dead'] = true;

                    devN['links'][lineId] = rLogicId;
                    devR['links'][lineId] = nLogicId;
                    netw.linksList[lineId] = { 'src': rLogicId, 'dst': nLogicId, 'lqi': neighbor['lqi'] };
                    lineId++;
                }

            } // End 'for (rLogicId in lqiTable.routers)'

            // Checking devices in Jeedom but not visible on the network
            for (jLogicId in jeedomDevices) {
                jDev = jeedomDevices[jLogicId];
                if (jDev.enabled == 0)
                    continue; // Disabled => ignoring
                if (typeof netw.devList[jLogicId] !== "undefined")
                    continue; // Already in the list
                if (jDev.addr.substring(0, 2) == "rc")
                    continue; // Virtual remote control not displayed

                // Adding dead device
                devJ = new Object();
                devJ['addr'] = jDev.addr;
                devJ['name'] = jDev.name;
                devJ['icon'] = jDev.icon;
                devJ['color'] = getDevColor(jDev.zbType);
                devJ['posX'] = jDev.x;
                devJ['posY'] = jDev.y;
                devJ['posZ'] = jDev.z;
                devJ['jeedomId'] = jDev.id;
                devJ['dead'] = true;

                netw.devList[jLogicId] = devJ;
                netw.devListNb++;
            }

            // Checking & updating devices positions
            devListNb = netw.devListNb;
            for (devLogicId in netw.devList) {
                dev = netw.devList[devLogicId];
                if (dev['addr'] == '0000')
                    isCoordinator = true;
                else
                    isCoordinator = false;

                if (dev['posX'] == 0) // 0 is forbidden
                    netw.devList[devLogicId]['posX'] = setAutoX(isCoordinator, devListNb);
                if (dev['posY'] == 0) // 0 is forbidden
                    netw.devList[devLogicId]['posY'] = setAutoY(isCoordinator, devListNb);
                if (typeof dev['posZ'] === "undefined")
                    netw.devList[devLogicId]['posZ'] = 0;

                // Can't do it there since refreshBackgroundMap() is not executed yet
                // devList = netw.devList;
                // checkPosLimits(devLogicId);
            }

            console.log("Net "+n+" devList=", netw.devList);
        }
    }

    // Redraw full page
    function refreshPage() {
        console.log("refreshPage(): viewLevel="+viewLevel);
        // if (typeof devList === "undefined") {
        //     console.log("refreshPage(): UNDEFINED devList");
        //     return;
        // }

        refreshBackgroundMap(viewLevel);

        lesAbeilles = "";
        for (netIdx = 0; netIdx < networks.length; netIdx++) {
            netw = networks[netIdx];

            // Display of this network is enabled ?
            elm = document.getElementById("idViewNet-"+netIdx);
            if (!elm.checked)
                continue;

            // Displaying links ?
            if (viewLinks)
                drawLinks(netIdx);

            // Drawing devices after links to have them behind devices
            lesAbeilles += drawDevices(netIdx);
        }

        document.getElementById("idGraph").innerHTML = lesAbeilles;

        // If config mode, add event listener on node menu
        if (configMode) {
            const buttons = document.querySelectorAll(".nodeMenuBtn");
            buttons.forEach(function(button) {
                // console.log("la");
                button.addEventListener("click", nodeMenuBtnCb);
            });
        }
    }

    $("#idMapsBtn").on("click", function () {
        $("#md_modal").dialog({ title: "{{Plan par niveau}}" });
        $("#md_modal")
            .load("index.php?v=d&plugin=Abeille&modal=AbeilleNetworkMap.modal")
            .dialog("open")
            .dialog("option", "width", 700)
            .dialog("option", "height", 350);
    });

    // X = eval('center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille)');
    // Y = eval('center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille)');
    function setAutoX(isCoordinator, devListNb) {
        if (isCoordinator == true)
            return centerX;
        posX = centerX + centerR * Math.cos(2 * Math.PI * autoXIdx / (devListNb - 1));
        autoXIdx++;
        return posX;
    }

    function setAutoY(isCoordinator, devListNb) {
        if (isCoordinator == true)
            return centerY;
        posY = centerY + centerR * Math.sin(2 * Math.PI * autoYIdx / (devListNb - 1));
        autoYIdx++;
        return posY;
    }

    function drawDevices(netIdx) {
        console.log("drawDevices(netIdx="+netIdx+")");

        netw = networks[netIdx];
        devList = netw.devList;
        allDevices = "";
        for (devLogicId in devList) {
            // Set by buildDevList()
            // if (typeof devList[devLogicId]['posZ'] !== "undefined")
            //     posZ = devList[devLogicId]['posZ'];
            // else
            //     posZ = 0;
            if (devList[devLogicId]['posZ'] != viewLevel) {
                console.log(devLogicId+" is not part of current level "+viewLevel);
                continue;
            }
            dev = devList[devLogicId];
            console.log("dev=", dev);

            nodeColor = dev['color'];
            // addr = dev['addr'];
            // if (addr == '0000')
            //     isZigate = true;
            // else
            //     isZigate = false;
            // Set by buildDevList()
            // if (dev['posX'] == 0) // 0 is forbidden
            //     dev['posX'] = setAutoX(isZigate);
            // if (dev['posY'] == 0) // 0 is forbidden
            //     dev['posY'] = setAutoY(isZigate);

            // dev['posX'] = 25;
            // dev['posY'] = 25; // TEMP

            // Checking limits vs map size
            checkPosLimits(devLogicId);

            // Computing positions based on node central coordinates
            posX = dev['posX'];
            posY = dev['posY'];
            grpX = posX - 25;
            grpY = posY - 25;
            // imgX = posX - 20;
            // imgY = posY - 20;
            imgX = 5;
            imgY = 5;
            // Text is on top of the group
            // txtX = posX + 22;
            // txtY = posY + 0;
            // txtX = 25;
            // txtY = -5; // Placed on top of group
            txtX = grpX;
            txtY = grpY - 10;

            // if (configMode)
            //     newG = '<g id="'+devLogicId+'" class="draggable" transform="translate('+grpX+', '+grpY+')">';
            // else
            //     newG = '<g id="'+devLogicId+'" transform="translate('+grpX+', '+grpY+')">';
            // newG += '<rect rx="10" ry="10" width="50" height="50" style="fill:'+nodeColor+'" />';
            newG = '<g id="'+devLogicId+'" transform="translate('+grpX+', '+grpY+')">';
            newG += '<rect rx="10" ry="10" width="50" height="50" style="fill:'+nodeColor+'" />';
            // if (configMode) {
            //     // Add a button to select level
            //     newG += '<rect x="50" rx="5" ry="5" width="25" height="25" onclick="nodeMenu(\''+devLogicId+'\')" />';
            //     // newG += '<rect x="50" rx="5" ry="5" width="25" height="25" onclick="nodeMenu()" />';
            // }
            if (configMode)
                newG += '<image class="draggable" xlink:href="/plugins/Abeille/images/node_' + dev['icon'] + '.png" x="'+imgX+'" y="'+imgY+'" height="40" width="40" />';
            else
                newG += '<image xlink:href="/plugins/Abeille/images/node_' + dev['icon'] + '.png" x="'+imgX+'" y="'+imgY+'" height="40" width="40" />';
            // newG += '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+dev['jeedomId']+'" target="_blank"><text x="'+txtX+'" y="'+txtY+'" fill="black" style="font-size: 12px;">'+dev['name']+'</text></a>';
            // newG += '</g>';

            if (typeof dev['dead'] !== 'undefined') {
                deadX = 32;
                deadY = 28;
                newG += '<image xlink:href="/plugins/Abeille/images/death.png" x="'+deadX+'" y="'+deadY+'" height="20" />';
            }

            // newG += '<g id="'+devLogicId+'-txt" transform="translate('+txtX+', '+txtY+')">';
            // newG += '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+dev['jeedomId']+'" target="_blank"><text x="'+txtX+'" y="'+txtY+'" fill="black" style="font-size: 12px;">'+dev['name']+'</text></a>';
            newG += '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+dev['jeedomId']+'" target="_blank"><text fill="black" style="font-size: 12px;">'+dev['name']+'</text></a>';
            // newG += '</g>';

            // If config mode, add a button to select level
            if (configMode) {
                // btnX = grpX + 25;
                // btnY = grpY;
                // newG += '<g id="'+devLogicId+'-btn" transform="translate('+btnX+', '+btnY+')">';
                // newG += '<rect x="50" rx="5" ry="5" width="25" height="25" onclick="nodeMenu(this, \''+devLogicId+'\')" />';
                newG += '<rect x="50" rx="5" ry="5" width="25" height="25" class="nodeMenuBtn" />';

                // newG += '</g>';
            }
            newG += '</g>';
            console.log("newG=", newG);

            allDevices += newG;
        }

        return allDevices;
    }

    function drawLinks(n) {
        console.log("drawLinks(netIdx="+n+")");

        netw = networks[n];
        linksList = netw.linksList;
        devList = netw.devList;

        console.log('Net '+n+' linksList=', linksList);
        for (linkId in linksList) {
            // Reminder: linksList[linkId] = { "src": ss, "dst": ddd }
            link = linksList[linkId];
            // console.log('link['+linkId+']=', link);

            srcDev = devList[link.src];
            x1 = srcDev['posX'];
            y1 = srcDev['posY'];
            z1 = srcDev['posZ'];

            dstDev = devList[link.dst];
            x2 = dstDev['posX'];
            y2 = dstDev['posY'];
            z2 = dstDev['posZ'];

            if (typeof link['lqi'] === 'undefined')
                linkColor = "green";
            else if (link['lqi'] > 150)
                linkColor = "green";
            else if (link['lqi'] > 50)
                linkColor = "orange";
            else
                linkColor = "red";

            if (z2 != z1) {
                console.log("z2 != z1 => Link thru levels")
                // Computing x3/y3 in the middle on the line to change line type
                x3 = x1 + (x2 - x1) / 2;
                y3 = y1 + (y2 - y1) / 2;
                if (z1 == viewLevel) {
                    lesAbeilles += '<line id="idLink-'+linkId+'" x1="'+x1+'" y1="'+y1+'" x2="'+x3+'" y2="'+y3+'" style="stroke:'+linkColor+';stroke-width:2"/>';
                    lesAbeilles += '<line id="idLink-'+linkId+'" x1="'+x3+'" y1="'+y3+'" x2="'+x2+'" y2="'+y2+'" stroke-dasharray="8" style="stroke:'+linkColor+';stroke-width:2"/>';
                } else {
                    lesAbeilles += '<line id="idLink-'+linkId+'" x1="'+x1+'" y1="'+y1+'" x2="'+x3+'" y2="'+y3+'" stroke-dasharray="8" style="stroke:'+linkColor+';stroke-width:2"/>';
                    lesAbeilles += '<line id="idLink-'+linkId+'" x1="'+x3+'" y1="'+y3+'" x2="'+x2+'" y2="'+y2+'" style="stroke:'+linkColor+';stroke-width:2"/>';
                }
            } else {
                if (z1 != viewLevel) {
                    console.log("Not corresponding view level => Link not displayed")
                    continue;
                }

                lesAbeilles += '<line id="idLink-'+linkId+'" x1="'+x1+'" y1="'+y1+'" x2="'+x2+'" y2="'+y2+'" style="stroke:'+linkColor+';stroke-width:2"/>';
            }
        }
    }

    // Display node menu in config mode
    function nodeMenuBtnCb(evt) {
        console.log("nodeMenuBtnCb(), evt=", evt);

        // TODO: Get mouse position
        // var mousePos = getMousePosition(evt);
        // console.log("  Mouse pos=", mousePos);
        svg = document.getElementById("idGraph");
        let CTM = svg.getScreenCTM();
        x = (evt.clientX - CTM.e) / CTM.a;
        y = (evt.clientY - CTM.f) / CTM.d;
        console.log("x="+x+", y="+y)

        currentGroup = evt.target.parentNode; // Parent group

        // TODO: Move 'nodeMenu' to position
        const nmElm = document.getElementById('idNodeMenuSelect');
        nmElm.style.top = y+'px';
        nmElm.style.left = x+'px';

        nmElm.style.display = 'inline';
    }

    // Node menu select callback
    $("#idNodeMenuSelect").on("change", function() {
        posZ = document.getElementById("idNodeMenuSelect").value;
        console.log("idNodeMenuSelect changed to '"+posZ+"'");

        // Move corresponding node to proper level
        devLogicId = currentGroup.id; // Ex: 'AbeilleX/yyyy'
        console.log("Select change for devLogicId="+devLogicId);
        netIdx = devLogicId2Net(devLogicId);
        devList = networks[netIdx].devList;
        devList[devLogicId]['posZ'] = posZ;
        saveCoordinates(netIdx, devLogicId);

        // Hide nodeMenuSelect
        let nodeMenuSelect = document.getElementById('idNodeMenuSelect');
        nodeMenuSelect.style.display = 'none';

        // Refresh display
        refreshPage();
    });

    //-----------------------------------------------------------------------
    // MAIN
    //-----------------------------------------------------------------------

    // viewLevel = 0; // Level idx to display. Updated from 'networkMap['levelChoice']
    viewLinks = true; // Display links
    // $viewImage = Array(
    //     "path" => $userMapPath, // Path relative to Abeille's root
    //     "width" => $width,
    //     "height" => $height
    // );
    // networks = [] of Object(
    //     'zgId' =>
    //     'lqiTable' => // Network topology coming from LQI collect.
    //     'devList' => new Object();
    //     'devListNb' => x;
    //     'linksList' => new Object();
    // );
    var jeedomDevices; // Jeedom known devices
    let configMode = false;

    var networkInformation = "";
    var networkInformationProgress = "Processing";
    // var TopoSetReply = "";
    var refreshLqiStatus; // Result of setInterval()

    var a = 10;
    var centerJSON = '{ "X": 500, "Y": 500, "rayon": "400" }';
    var center = JSON.parse(centerJSON);

    var Ruche = "Abeille1";
    // var Source = "All";
    // var Destination = "All";
    // var Parameter = "LinkQualityDec";
    // var Hierarchy = "All";

    const queryString = window.location.search;
    console.log("URL params=" + queryString);
    const urlParams = new URLSearchParams(queryString);
    if (urlParams.has('zigate')) {
        zgId = urlParams.get('zigate');
        Ruche = "Abeille" + zgId;
    } else {
        zgId = 1;
        Ruche = "Abeille1";
    }
    // res = queryString.substr(1);
    // console.log("res=" + res);
    // if (res.length > 2) Ruche = res;
    // console.log("Ruche=" + Ruche);

    getLqiTables();
    getJeedomDevices();

    // FCh temp disable
    // refreshLqiStatus = setInterval(
    //     function() {
    //         refreshNetworkCollectionProgress();
    //     },
    //     1000  // ms
    // );

    // Display options
    var viewLinks = true; // Set to false to hide links

    var selectedElement, transform;
    var offset; // Mouse offset vs rect top left corner
    // var positionX = "Position: X=";
    // var positionY = " Y=";
    var X = 0;
    var Y = 0;

    // Compute auto-placement when position is undefined
    // If 'isZigate' is true, it is placed at center.
    centerX = 500;
    centerY = 500;
    centerR = 400; // Radius
    autoXIdx = 0;
    autoYIdx = 0;

    // Combine LQI + Jeedom infos
    buildDevList();

    refreshPage();
    // console.log("End of script");
</script>
