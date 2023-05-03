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
    if (isset($_GET['zigate']))
        $zgId = $_GET['zigate'];
    else
        $zgId = 1; // Default = zigate1. TODO: Should be the 1st enabled, not the 1
    sendVarToJS('zgId', $zgId);

    // Selecting background map
    // ab::userMap => path to user map, relative to Abeille's root
    $userMap = config::byKey('ab::userMap', 'Abeille', '', true);
    echo "<script>console.log(\"userMap=" . $userMap . "\")</script>";
    if (($userMap == '') || !file_exists(__DIR__."/../../".$userMap)) {
        // echo '<image x="0" y="0" width="1100px" height="1100px" xlink:href="/plugins/Abeille/Network/TestSVG/images/AbeilleLQI_MapData.png"></image>';
        $userMap = "images/AbeilleNetworkMap-1200.png";
    }
    echo "<script>console.log(\"userMap2=" . $userMap . "\")</script>";
    sendVarToJS('userMap', $userMap);

    // eqLogic/configuration/ab::settings reminder
    // networkMap[zgId] = array(
    //     "level0" => "map-level0.png",
    //     "levelX" => "map-levelX.png"
    // )
    $networkMap = config::byKey('ab::networkMap', 'Abeille', [], true);
    sendVarToJS('networkMap', $networkMap);

    $iSize = getimagesize(__DIR__."/../../".$userMap);
    $width = $iSize[0];
    $height = $iSize[1];
    $image = Array(
        "path" => $userMap, // Path relative to Abeille's root
        "width" => $width,
        "height" => $height
    );
    sendVarToJS('maxX', $width);
    sendVarToJS('maxY', $height);
?>

<html>
<head>
    <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
</head>

<body>
    <div style="background: #e9e9e9; font-weight: bold; padding: .4em 1em;">
        Placement réseau (BETA)
    </div>

    <!-- <style>
    td {
        border: 1px solid black;
    }
    </style> -->

    <style>
        #idGraph {
        <?php
            echo 'background-image: url("/plugins/Abeille/'.$image['path'].'");';
            echo 'width: '.$image['width'].'px;';
            echo 'height: '.$image['height'].'px;';
        ?>
            background-size: contain;
        }
        .column {
            float: left;
            width: 50%;
        }
    </style>

    <!-- <div class="row"> -->
    <div>
        <div id="idLeftBar" class="column" style="width:100px">
            <!-- Drop down list to select Zigate -->
            <select name="idZigate">
            <?php
                for ($z = 1; $z <= maxNbOfZigate; $z++ ) {
                    if (config::byKey('ab::zgEnabled'.$z, 'Abeille', 'N') != 'Y')
                        continue;

                    $selected = '';
                    echo '<option value="'.$z.'" '.$selected.'>Zigate '.$z.'</option>'."\n";
                }
            ?>
            </select>
            <!-- View options -->
            <input type="checkbox" id="idViewLinks" checked>{{Liens}}
            <!-- TODO: Text size (to be stored in DB) -->

            </br>
            </br>
            Configuration</br>

            <!-- <button id="save" onclick="saveCoordinates()" style="width:100%;margin-top:4px">{{Sauver}}</button> -->
            <!-- <button id="map" onclick="uploadMap()" style="width:100%;margin-top:4px">{{Plan}}</button> -->
            <button id="idMap" style="width:100%;margin-top:4px">{{Plans}}</button>
        </div>

        <div id="idGraph" class="column">
            <svg id="devices" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" onload="makeDraggable(evt)">
            </svg>
        </div>
    </div>
</body>
</html>

<script type="text/javascript">
    var networkInformation = "";
    var networkInformationProgress = "Processing";
    var TopoSetReply = "";
    var refreshStatus; // Result of setInterval()

    var a = 10;
    var centerJSON = '{ "X": 500, "Y": 500, "rayon": "400" }';
    var center = JSON.parse(centerJSON);

    var Ruche = "Abeille1";
    var Source = "All";
    var Destination = "All";
    var Parameter = "LinkQualityDec";
    var Hierarchy = "All";

    // var myJSON = '{}';

    // myObjOrg et myObjNew ne contiennent que la ruche au chargement du script
    // On parcourt les abeilles de jeedom on l'ajoute à myObjOrg et myObjNew
    // On affecte une position stupide, qu'on mettre à jour une fois les info disponibles.
    // Idem pour la couleur
    // var myObjOrg = JSON.parse(myJSON);
    // var myObjNew = JSON.parse(myJSON);

    //-----------------------------------------------------------------------
    // Functions
    //-----------------------------------------------------------------------

    // function setTopoJSON(Topo) {
    //     // console.log("Coucou");
    //     // var requestTopo = "/plugins/Abeille/Network/TestSVG/TopoSet.php?TopoJSON=";
    //     var requestTopoURL = "/plugins/Abeille/core/php/AbeilleSetEq.php";
    //     // var param = encodeURIComponent(Topo);
    //     var param = "TopoJSON="+Topo;
    //     // console.log(param);
    //     // console.log(Topo);
    //     // console.log(requestTopo+Topo);
    //     var xhr = new XMLHttpRequest();

    //     xhr.onreadystatechange = function() {
    //         if (this.readyState == 4 && this.status == 200 ) {
    //             TopoSetReply = this.responseText;
    //             console.log(TopoSetReply);
    //         }
    //     }

    //     xhr.open("POST", requestTopoURL, true );

    //     // xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    //     // console.log(requestTopo+Topo);
    //     // xhr.setRequestHeader('Content-Type: application/json; charset=utf-8');
    //     xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    //     // xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded;charset=utf-8');

    //     xhr.send(encodeURI(param));
    //     // xhr.send(encodeURI("TopoJSON=toto"));
    //     console.log("Params sent->"+param);
    // }

    /* Launch network scan for current Zigate */
    function refreshNetworkInformation() {
        console.log("refreshNetworkInformation("+Ruche+")");

        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                networkInformation = this.responseText;
                console.log("refreshNetworkInformation(): " + networkInformation);
            }
        };
        xhr.open("GET", "/plugins/Abeille/core/php/AbeilleLQI.php?zigate="+zgId, true);
        xhr.send();

        /* Start refresh status every 1sec */
        refreshStatus = setInterval(function() { refreshNetworkCollectionProgress(); }, 1000);  // ms
    }

    function refreshNetworkInformationProgress() {
        console.log("refreshNetworkInformationProgress("+Ruche+")");

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
                // file : "AbeilleLQI_MapData"+Ruche+".json.lock",
                file : "AbeilleLQI-"+Ruche+".json.lock",
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
                        clearInterval(refreshStatus);
                    } else
                        networkInformationProgress = data;
                        // document.getElementById("refreshInformation").value = data;
                }
            }
        });
    }

    // Ensure that device coordinates are within background map size
    function checkLimits(grpX, grpY) {
        if (grpX < 0) grpX = 0;
            // console.log("  maxX=", maxX);
        if ((grpX + 50) > maxX)
            grpX = maxX - 50;
        if (grpY < 0) grpY = 0;
        // console.log("  maxY=", maxY);
        if ((grpY + 50) > maxY)
            grpY = maxY - 50;
        // console.log("  maxY2=", maxY);

        return {
            x: grpX,
            y: grpY
        };
    }

    // Thanks to http://www.petercollingridge.co.uk/tutorials/svg/interactive/dragging/
    function makeDraggable(evt) {
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

            parentG = evt.target.parentNode;
            if (!parentG.classList.contains('draggable'))
                return;

            console.log("startDrag(), evt=", evt);

            selectedElement = parentG;
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
            grpCoord = checkLimits(grpX, grpY);
            transform.setTranslate(grpCoord['x'], grpCoord['y']);

            // Moving connected lines
            devLogicId = selectedElement.id;
            dev = devList[devLogicId];
            lineX = grpX + 25; // Center in 50x50 square
            lineY = grpY + 25; // Center in 50x50 square
            for (linkId in dev['links']) {
                link = linksList[linkId];
                var line = document.getElementById("idLine"+linkId);
                if (devLogicId == link.src) {
                    line.x1.baseVal.value = lineX;
                    line.y1.baseVal.value = lineY;
                } else {
                    line.x2.baseVal.value = lineX;
                    line.y2.baseVal.value = lineY;
                }
            }

            if (dragEnd) {
                // Update coordinates
                devLogicId = selectedElement.id;
                devList[devLogicId]['posX'] = grpX + 25;
                devList[devLogicId]['posY'] = grpY + 25;
                devList[devLogicId]['posChanged'] = true;

                saveCoordinates(devLogicId);

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

    /* Draw 'legend' area */
    function drawLegend(includeGroup) {
        var legend = "";

        if ( includeGroup ) { legend = legend + '<g id="legend">'; }

        legend = legend + '<circle cx="100" cy="875" r="10" fill="red" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="875" fill="black" style="font-size: 8px;">Coordinator</text> </a>\n';

        legend = legend + '<circle cx="100" cy="900" r="10" fill="blue" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="900" fill="black" style="font-size: 8px;">Routeur</a>\n';

        legend = legend + '<circle cx="100" cy="925" r="10" fill="green" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="925" fill="black" style="font-size: 8px;">End Equipment</text> </a>\n';

        legend = legend + '<circle cx="100" cy="950" r="10" fill="yellow" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="950" fill="black" style="font-size: 8px;">Dans Jeedom mais pas dans l audit du reseau</text> </a>\n';

        if ( includeGroup ) { legend = legend + '</g>'; }

        return legend;
    }

    // function dessineLesTextes(offsetX, includeGroup) {
    //     console.log("dessineLesTextes()");

    //     if (typeof lqiTable === "undefined") {
    //         console.log("=> lqiTable is UNDEFINED")
    //         return;
    //     }

    //     var lesTextes = "";
    //     var info = "";

    //     if ( includeGroup=="Yes" ) { lesTextes = lesTextes + '<g id="lesTextes">'; }

    //     for (voisines in lqiTable.data) {

    //         // lesTextes = lesTextes + lqiTable.data[voisines].NE + "->" + lqiTable.data[voisines].Voisine + " / ";
    //         var NE = lqiTable.data[voisines].NE; var voisine = lqiTable.data[voisines].Voisine;
    //         var X1=0; var X2=0; var Y1=0; var Y2=0; var midX=0; var midY=0;

    //         if ( ( (Source=="All") || (Source==NE) || (Destination=="All")|| (Destination==voisine) ) && ( (Hierarchy==lqiTable.data[voisines].Relationship) || (Hierarchy=="All") ) ) {
    //             if ( typeof myObjNew[NE] == "undefined" ) {

    //             }
    //             else {
    //                 if ( typeof myObjNew[NE].x == "undefined" )      {X1=0;} else { X1 = myObjNew[NE].x + offsetX; }
    //                 if ( typeof myObjNew[NE].y == "undefined" )      {Y1=0;} else { Y1 = myObjNew[NE].y; }
    //             }
    //             if ( typeof myObjNew[voisine] == "undefined" ) {
    //             }
    //             else {
    //                 if ( typeof myObjNew[voisine].x == "undefined" ) {X2=0;} else { X2 = myObjNew[voisine].x + offsetX; }
    //                 if ( typeof myObjNew[voisine].y == "undefined" ) {Y2=0;} else { Y2 = myObjNew[voisine].y; }
    //             }

    //             midX=(X1+X2)/2; midY=(Y1+Y2)/2;

    //             if ( Parameter == "LinkQualityDec" )    { info = lqiTable.data[voisines].LinkQualityDec;   }
    //             if ( Parameter == "Depth" )             { info = lqiTable.data[voisines].Depth;            }
    //             if ( Parameter == "Voisine" )           { info = lqiTable.data[voisines].Voisine;          }
    //             if ( Parameter == "IEEE_Address" )      { info = lqiTable.data[voisines].IEEE_Address;     }
    //             if ( Parameter == "Type" )              { info = lqiTable.data[voisines].Type;             }
    //             if ( Parameter == "Relationship" )      { info = lqiTable.data[voisines].Relationship;     }
    //             if ( Parameter == "Rx" )                { info = lqiTable.data[voisines].Rx;               }

    //             // console.log("dessineLesTextes function: Parameter: " + Parameter );

    //             lesTextes = lesTextes + '<text x="'+midX+'" y="'+midY+'" fill="purple" style="font-size: 8px;">'+info+'</text>';
    //         }
    //     }

    //     if ( includeGroup=="Yes" ) { lesTextes = lesTextes + '</g>'; }

    //     return lesTextes;
    // }

    // function dessineLesVoisinesV2(offsetX, includeGroup) {
    //     console.log("dessineLesVoisinesV2()");

    //     if (typeof lqiTable === "undefined") {
    //         console.log("=> lqiTable is UNDEFINED")
    //         return;
    //     }

    //     var lesVoisines = "";

    //     if ( includeGroup=="Yes" ) { lesVoisines = lesVoisines + '<g id="lesVoisines">'; }

    //     for (voisines in lqiTable.data) {

    //         //var NE = lqiTable.data[voisines].NE;
    //         var NE = lqiTable.data[voisines].NE;
    //         var voisine = lqiTable.data[voisines].Voisine;

    //         if ( ( (Source=="All") || (Source==NE) || (Destination=="All")|| (Destination==voisine) ) && ( (Hierarchy==lqiTable.data[voisines].Relationship) || (Hierarchy=="All") ) ) {
    //             var X1=0; var X2=0; var Y1=0; var Y2=0;
    //             var color="orange";

    //             if ( typeof myObjNew[NE] == "undefined" ) {

    //             }
    //             else {
    //                 if ( typeof myObjNew[NE].x == "undefined" )      {X1=0;} else { X1 = myObjNew[NE].x + offsetX; }
    //                 if ( typeof myObjNew[NE].y == "undefined" )      {Y1=0;} else { Y1 = myObjNew[NE].y; }
    //             }
    //             if ( typeof myObjNew[voisine] == "undefined" ) {
    //             }
    //             else {
    //                 if ( typeof myObjNew[voisine].x == "undefined" ) {X2=0;} else { X2 = myObjNew[voisine].x + offsetX; }
    //                 if ( typeof myObjNew[voisine].y == "undefined" ) {Y2=0;} else { Y2 = myObjNew[voisine].y; }
    //             }

    //             if ( lqiTable.data[voisines].LinkQualityDec > 150 ) { color = "green"; }
    //             if ( lqiTable.data[voisines].LinkQualityDec <  50 ) { color = "red";}

    //             lesVoisines = lesVoisines + '<line class="zozo" x1="'+X1+'" y1="'+Y1+'" x2="'+X2+'" y2="'+Y2+'" style="stroke:'+color+';stroke-width:1"/>';
    //         }
    //     }

    //     if ( includeGroup=="Yes" ) { lesVoisines = lesVoisines + '</g>'; }

    //     return lesVoisines;
    // }

    // function dessineLesAbeilles(includeGroup) {
    //     var lesAbeilles = "";

    //     if ( includeGroup=="Yes" ) { lesAbeilles = lesAbeilles + '<g id="lesAbeilles">'; }
    //     for (shortAddress in myObjNew) {
    //         myObjOrg[shortAddress].x = myObjNew[shortAddress].x;
    //         myObjOrg[shortAddress].y = myObjNew[shortAddress].y;
    //         lesAbeilles = lesAbeilles + '<circle class="draggable" id="'+shortAddress+'"   cx="'+myObjNew[shortAddress].x+'"  cy="'+myObjNew[shortAddress].y+'"              r="10"           fill="'+myObjNew[shortAddress].color+'"  transform="translate(0, 0)"></circle>';
    //     }
    //     if ( includeGroup=="Yes" ) { lesAbeilles = lesAbeilles + '</g>'; }
    //     return lesAbeilles;
    // }

    // function dessineLesAbeillesText(myObj,offsetX,includeGroup) {
    //     var lesAbeillesText = "";
    //     var X = 0;
    //     var Y = 0;

    //     if ( includeGroup=="Yes" ) { lesAbeillesText = lesAbeillesText + '<g id="lesAbeillesText">'; }

    //     // console.log( JSON.stringify(myObj) );
    //     for (shortAddress in myObj) {
    //         X = myObj[shortAddress].x + offsetX;
    //         Y = myObj[shortAddress].y;
    //         if( (typeof jeedomDevices[shortAddress] === "object") && (jeedomDevices[shortAddress] !== null) ) {
    //             lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+jeedomDevices[shortAddress].id+'" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].objectName+' - '+myObj[shortAddress].name+' - '+' ('+shortAddress+')</text> </a>';
    //         }
    //         else {
    //             lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].name+' ('+shortAddress+')</text> </a>';
    //         }
    //     }
    //     if ( includeGroup=="Yes" ) { lesAbeillesText = lesAbeillesText + '</g>'; }
    //     return lesAbeillesText;
    // }

    // function setPosition(mode) {
    //     var iAbeille = 0;
    //     var nbAbeille = Object.keys(myObjNew).length
    //     for (abeille in myObjNew) {
    //         if ( (myObjNew[abeille].positionDefined == "No") && (mode=="Auto") ) {
    //             X = eval('center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.
    //             Y = eval('center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.

    //             myObjOrg[abeille].x = X;
    //             myObjOrg[abeille].y = Y;
    //             myObjNew[abeille].x = X;
    //             myObjNew[abeille].y = Y;

    //             myObjNew[abeille].positionDefined = "Yes";

    //             iAbeille++;
    //         }
    //         if ( mode=="AutoForce" ) {
    //             X = eval('center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.
    //             Y = eval('center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.

    //             myObjOrg[abeille].x = X;
    //             myObjOrg[abeille].y = Y;
    //             myObjNew[abeille].x = X;
    //             myObjNew[abeille].y = Y;

    //             myObjNew[abeille].positionDefined = "Yes";

    //             iAbeille++;
    //         }
    //     }
    // }

    /* Refresh status of ongoing network scan */
    function refreshNetworkCollectionProgress() {
        refreshNetworkInformationProgress();

        console.log("refreshNetworkCollectionProgress(): " + networkInformationProgress);
        // document.getElementById("refreshInformation").innerHTML = networkInformationProgress;
        document.getElementById("refreshInformation").value = networkInformationProgress;
    }

    // function myJSON_AddAbeillesFromJeedom() {
    //     // console.log("jeedomDevices: "+JSON.stringify(jeedomDevices));
    //     for (logicalId in jeedomDevices) {
    //         console.log("logicalId: "+logicalId);

    //         myObjOrg[logicalId] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
    //         myObjNew[logicalId] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };

    //         console.log("logicalId: "+logicalId+" -> "+JSON.stringify(jeedomDevices[logicalId]));
    //         myObjOrg[logicalId].objectName = jeedomDevices[logicalId].objectName;
    //         myObjNew[logicalId].objectName = jeedomDevices[logicalId].objectName;

    //         myObjOrg[logicalId].name = jeedomDevices[logicalId].name;
    //         myObjNew[logicalId].name = jeedomDevices[logicalId].name;

    //         console.log("logicalId: "+logicalId+" -> x: "+jeedomDevices[logicalId].X);
    //         myObjOrg[logicalId].x = jeedomDevices[logicalId].X;
    //         myObjNew[logicalId].x = jeedomDevices[logicalId].X;

    //         myObjOrg[logicalId].y = jeedomDevices[logicalId].Y;
    //         myObjNew[logicalId].y = jeedomDevices[logicalId].Y;

    //         if ( (jeedomDevices[logicalId].X>0) && (jeedomDevices[logicalId].Y>0) ) {
    //             myObjOrg[logicalId].positionDefined = "Yes";
    //             myObjNew[logicalId].positionDefined = "Yes";
    //         } else {
    //             myObjOrg[logicalId].positionDefined = "No";
    //             myObjNew[logicalId].positionDefined = "No";
    //         }
    //     }

    //     console.log("myObjOrg: "+JSON.stringify(myObjOrg));
    // }

    // function myJSON_AddMissing() {
    //     console.log("myJSON_AddMissing()");

    //     if (typeof lqiTable === "undefined") {
    //         console.log("=> lqiTable is UNDEFINED")
    //         return;
    //     }

    //     var color = "";

    //     console.log("lqiTableLA2="+lqiTable);
    //     for (voisines in lqiTable.data) {
    //         // console.log("Voisine: "+lqiTable.data[voisines].NE+"->"+lqiTable.data[voisines].Voisine);

    //         if ( typeof myObjOrg[lqiTable.data[voisines].NE] === "undefined" ) {

    //             myObjOrg[lqiTable.data[voisines].NE] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
    //             myObjNew[lqiTable.data[voisines].NE] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };

    //             if ( typeof jeedomDevices[lqiTable.data[voisines].NE] === "undefined" ) {
    //                 myObjOrg[lqiTable.data[voisines].NE].name = "Pas dans Jeedom";
    //                 myObjOrg[lqiTable.data[voisines].NE].name = "Pas dans Jeedom";
    //             } else {
    //                 myObjOrg[lqiTable.data[voisines].NE].name = jeedomDevices[lqiTable.data[voisines].NE].name;
    //                 myObjNew[lqiTable.data[voisines].NE].name = jeedomDevices[lqiTable.data[voisines].NE].name;
    //             }
    //         }

    //         if ( typeof myObjOrg[lqiTable.data[voisines].Voisine] === "undefined" ) {

    //             myObjOrg[lqiTable.data[voisines].Voisine] = { "name": "NoName", "x": 50, "y": 150, "color": "black", "positionDefined":"No", "Type":"Inconnu" };
    //             myObjNew[lqiTable.data[voisines].Voisine] = { "name": "NoName", "x": 50, "y": 150, "color": "black", "positionDefined":"No", "Type":"Inconnu" };

    //             if ( lqiTable.data[voisines].Type == "End Device" ) { color="Green"; }
    //             if ( lqiTable.data[voisines].Type == "Router" ) { color="Orange"; }
    //             if ( lqiTable.data[voisines].Type == "Coordinator" ) { color="Red"; }
    //             myObjOrg[lqiTable.data[voisines].Voisine].color = color;
    //             myObjNew[lqiTable.data[voisines].Voisine].color = color;

    //             if ( typeof jeedomDevices[lqiTable.data[voisines].Voisine] === "undefined" ) {
    //                 myObjOrg[lqiTable.data[voisines].Voisine].name = "Pas dans Jeedom";
    //                 myObjNew[lqiTable.data[voisines].Voisine].name = "Pas dans Jeedom";
    //             }
    //             else {
    //                 myObjOrg[lqiTable.data[voisines].Voisine].name = jeedomDevices[lqiTable.data[voisines].Voisine].name;
    //                 myObjNew[lqiTable.data[voisines].Voisine].name = jeedomDevices[lqiTable.data[voisines].Voisine].name;
    //             }
    //         } else {
    //             if ( lqiTable.data[voisines].Type == "End Device" ) { color="Green"; }
    //             if ( lqiTable.data[voisines].Type == "Router" ) { color="Orange"; }
    //             if ( lqiTable.data[voisines].Type == "Coordinator" ) { color="Red"; }
    //             myObjOrg[lqiTable.data[voisines].Voisine].color = color;
    //             myObjNew[lqiTable.data[voisines].Voisine].color = color;

    //             if ( typeof jeedomDevices[lqiTable.data[voisines].Voisine] === "undefined" ) {
    //                 myObjOrg[lqiTable.data[voisines].Voisine].name = "Pas dans Jeedom";
    //                 myObjNew[lqiTable.data[voisines].Voisine].name = "Pas dans Jeedom";
    //             }
    //             else {
    //                 myObjOrg[lqiTable.data[voisines].Voisine].name = jeedomDevices[lqiTable.data[voisines].Voisine].name;
    //                 myObjNew[lqiTable.data[voisines].Voisine].name = jeedomDevices[lqiTable.data[voisines].Voisine].name;
    //             }
    //         }
    //     }
    // }

    // function refreshAll(mode) {
    //     console.log("function refreshAll: "+ mode );

    //     if ( mode == "All" ) {
    //         getLqiTable();
    //         getJeedomDevices();

    //         myJSON_AddAbeillesFromJeedom();
    //         // console.log("myObjOrg: "+JSON.stringify(myObjOrg));
    //         myJSON_AddMissing();
    //         // console.log("myObjOrg: "+JSON.stringify(myObjOrg));
    //     }

    //     document.getElementById("legend").innerHTML = drawLegend(false);
    //     document.getElementById("lesVoisines").innerHTML = dessineLesVoisinesV2(0,"No");
    //     document.getElementById("lesTextes").innerHTML = dessineLesTextes(10,"No");
    //     document.getElementById("lesAbeillesText").innerHTML = dessineLesAbeillesText(myObjNew, 22,"No");
    //     document.getElementById("lesAbeilles").innerHTML = dessineLesAbeilles("No");
    //     // Je ne peux pas redessiner les abeilles car l'objet graphique contient la Transklation et les x, y d'origine
    //     // alors que dans myObjNew j'ai les nouvelles coordonnées mais pas la translation.
    // }

    // function rucheCentered() {
    //     console.log("rucheCentered("+Ruche+", X="+center.X+", Y="+center.Y+")");

    //     myObjNew[Ruche+"/0000"].x = center.X;
    //     myObjNew[Ruche+"/0000"].y = center.Y;
    //     refreshAll();
    // }

    // function placementAuto() {
    //     // setPosition("AutoForce");
    //     refreshAll();
    // }

    // function save() {
    //     // Thanks to https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify
    //     localStorage.setItem('myObjNew', JSON.stringify(myObjNew));
    // }

    // function restore() {
    //     // Thanks to https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify
    //     myObjOld = JSON.parse( localStorage.getItem('myObjNew') );
    //     myObjNew = JSON.parse( localStorage.getItem('myObjNew') );
    //     refreshAll();
    // }

    // function selectRuche(form) {
    //     Ruche = form.list.value;
    //     refreshAll("All");
    // }

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

    /* Really used ?
    function filtreRuche() {
        document.write( '<FORM NAME="myformRuche" ACTION="" METHOD="GET">');
        document.write( '<SELECT NAME="list" >');

        document.write( '<OPTION value="Abeille1" >Ruche 1</OPTION>');
        document.write( '<OPTION value="Abeille2" >Ruche 2</OPTION>');

        document.write( '</SELECT>');
        document.write( '</br>');
        document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectRuche(this.form)"/>');
        document.write( '</FORM>');
    }
    */

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

    // function saveAbeilles() {
    //     // console.log("Debug - saveAbeilles function - "+JSON.stringify(myObjNew));
    //     setTopoJSON(JSON.stringify(myObjNew));
    // }

    function refreshNetwork(newZgId) {
        // window.open("index.php?v=d&m=Abeille&p=AbeilleSupport");
        // window.open("plugins/Abeille/desktop/php/AbeilleNetworkGraph.php?zigate="+zgId);

        var url = window.location.href;
        console.log("url="+url);
        idx = url.indexOf('zigate='+zgId);
        if (idx === -1) {
            url += '&zigate='+newZgId
        } else {
            console.log("idx="+idx);
            url = url.replace('zigate='+zgId, 'zigate='+newZgId);
            // url += '?param=1'
        }
        // location.reload(true);
        window.location.href = url;
    };

    function getLqiTable() {
        console.log("getLqiTable("+Ruche+")");

        // var xmlhttp = new XMLHttpRequest();
        // xmlhttp.onreadystatechange = function() {
        //     if (this.readyState == 4 && this.status == 200) {
        //         lqiTable = JSON.parse(this.responseText);
        //     }
        // };

        // xmlhttp.open("GET", "/plugins/Abeille/tmp/AbeilleLQI_MapData"+Ruche+".json", false); // False pour bloquer sur la recuperation du fichier
        // xmlhttp.send();

        $.ajax({
            type: 'POST',
            url: "/plugins/Abeille/core/ajax/AbeilleFiles.ajax.php",
            data: {
                action: 'getTmpFile',
                file : "AbeilleLQI-"+Ruche+".json",
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
                    bootbox.alert("Pas de données du réseau.<br>La box a probablement redémarré depuis hier.<br><br>Veuillez interroger le réseau pour corriger.")
                } else if (res.content == "") {
                    // $('#div_networkZigbeeAlert').showAlert({message: '{{Fichier vide. Rien à traiter}}', level: 'danger'});
                    console.log("ERROR: empty content");
                } else {
                    lqiTable = JSON.parse(res.content);
                    console.log("lqiTable=", lqiTable);
                }
            },
        });
    }

    function getJeedomDevices() {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                jeedomDevices = JSON.parse(this.responseText);
                console.log("jeedomDevices=", jeedomDevices);
            }
        };

        xhr.open("GET", "/plugins/Abeille/core/php/AbeilleGetEq.php", false); // False pour bloquer sur la recuperation du fichier
        xhr.send();
    }

    /* eqLogic/configuration settings (ab::settings) update */
    function saveCoordinates(devLogicId) {
        console.log("saveCoordinates("+devLogicId+")");

        dev = devList[devLogicId];
        if (typeof dev['posChanged'] === "undefined")
            return; // Unknown to Jeedom

        if (!dev['posChanged'])
            return; // No change

        console.log("Saving coordinates for '"+dev['name']+"'");
        eqId = dev['jeedomId'];
        settings = {
            physLocationX: dev['posX'],
            physLocationY: dev['posY'],
        };
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/Abeille.ajax.php',
            data: {
                action: 'saveSettings',
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
        console.log("saveConfig()");

        config = new Object();
        config['ab::userMap'] =  userMap;

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

    // Combine LQI + Jeedom infos
    function buildDevList() {
        console.log("buildDevList()");

        if (typeof lqiTable === "undefined") {
            console.log("NO LQI table");
            return;
        }

        devList = new Object();
        devListNb = 0;
        linksList = new Object();
        lineId = 0; // To identify lines connecting nodes
        for (rLogicId in lqiTable.routers) {
            router = lqiTable.routers[rLogicId];
            // console.log("router " + rLogicId + "=", router);
            if (typeof devList[rLogicId] !== "undefined") {
                // Already registered
                devR = devList[rLogicId];
            } else {
                devR = new Object();
                devR['addr'] = router['addr'];
                devR['name'] = router['name'];
                devR['icon'] = router['icon'];
                if (devR['addr'] == '0000')
                    devR['color'] = "Red"; // Coordinator
                else
                    devR['color'] = "Blue"; // Router
                if (typeof jeedomDevices[rLogicId] !== "undefined") {
                    devR['posX'] = jeedomDevices[rLogicId].X;
                    devR['posY'] = jeedomDevices[rLogicId].Y;
                    devR['jeedomId'] = jeedomDevices[rLogicId].id;
                    devR['posChanged'] = false;
                }
                devR['links'] = new Object();

                devList[rLogicId] = devR;
                devListNb++;
            }

            for (nLogicId in router.neighbors) {
                neighbor = router.neighbors[nLogicId];
                console.log("neighbor=", neighbor);
                if (typeof devList[nLogicId] !== "undefined") {
                    // Already registered
                    devN = devList[nLogicId];
                } else {
                    devN = new Object();
                    devN['addr'] = neighbor['addr'];
                    devN['name'] = neighbor['name'];
                    devN['icon'] = neighbor['icon'];
                    if ( neighbor['type'] == "End Device" ) { devN['color'] = "Green"; }
                    else if ( neighbor['type'] == "Router" ) { devN['color'] = "Blue"; }
                    else if ( neighbor['type'] == "Coordinator" ) { devN['color'] = "Red"; }
                    else devN['color'] = "Yellow";
                    if (typeof jeedomDevices[nLogicId] !== "undefined") {
                        devN['posX'] = jeedomDevices[nLogicId].X;
                        devN['posY'] = jeedomDevices[nLogicId].Y;
                        devN['jeedomId'] = jeedomDevices[nLogicId].id;
                        devN['posChanged'] = false;
                    }
                    devN['links'] = new Object();

                    devList[nLogicId] = devN;
                    devListNb++;
                } // End for (nLogicId in router.neighbors)

                devN['links'][lineId] = rLogicId;
                devR['links'][lineId] = nLogicId;
                linksList[lineId] = { 'src': rLogicId, 'dst': nLogicId, 'lqi': neighbor['lqi'] };
                lineId++;
            }
        } // End 'for (rLogicId in lqiTable.routers)'
    }

    /* Upload a user map */
    function uploadMap() {
        console.log("uploadMap()");

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
            formData.append("destName", "Level0.png");

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
                userMap = "tmp/network_maps/" + "Level0.png";
                saveConfig();
                location.reload(true);
            };
            xhr.send(formData);
        }
        input.click();
    }

    // Redraw full page
    function refreshPage() {

        lesAbeilles = "";
        for (devLogicId in devList) {
            lesAbeilles += drawDevice(devLogicId);
        }
        if (viewLinks)
            drawLinks();
        document.getElementById("devices").innerHTML = lesAbeilles;
    }

    /* Refresh display if node name changed */
    $("#idViewLinks").on("change", function (event) {
        console.log("idViewLinks click");
        viewLinks = document.getElementById("idViewLinks").checked;
        refreshPage();
    });

    $("#idMap").on("click", function () {
        $("#md_modal").dialog({ title: "{{Plan par niveau}}" });
        $("#md_modal")
            .load("index.php?v=d&plugin=Abeille&modal=AbeilleNetworkMap.modal")
            .dialog("open");
    });

    //-----------------------------------------------------------------------
    // MAIN
    //-----------------------------------------------------------------------

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

    var lqiTable; // Network topology coming from LQI collect.
    var jeedomDevices; // Jeedom known devices
    var devList; // List of devices with combined infos from LQI + Jeedom
    var devListNb;
    var linksList;

    getLqiTable();
    getJeedomDevices();
    // myJSON_AddAbeillesFromJeedom();
    // console.log("myObjOrg: "+JSON.stringify(myObjOrg));
    // myJSON_AddMissing();
    // console.log("myObjOrg: "+JSON.stringify(myObjOrg));


    // FCh temp disable
    // refreshStatus = setInterval(
    //     function() {
    //         refreshNetworkCollectionProgress();
    //     },
    //     1000  // ms
    // );

    // console.log("Name list: "+JSON.stringify(lqiTable));
    // console.log("Name list: "+JSON.stringify(jeedomDevices));
    // console.log("Name 1: " + JSON.stringify(jeedomDevices["0000"]));

    // Combine LQI + Jeedom infos
    buildDevList();
    console.log("devList=", devList);



    // Display options
    var viewLinks = true; // Set to false to hide links

    // setPosition("Auto");

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

    // X = eval('center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille)');
    // Y = eval('center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille)');
    function setAutoX(isZigate) {
        if (isZigate == true)
            return centerX;
        posX = centerX + centerR * Math.cos(2 * Math.PI * autoXIdx / (devListNb - 1));
        autoXIdx++;
        return posX;
    }

    function setAutoY(isZigate) {
        if (isZigate == true)
            return centerY;
        posY = centerY + centerR * Math.sin(2 * Math.PI * autoYIdx / (devListNb - 1));
        autoYIdx++;
        return posY;
    }

    function drawDevice(devLogicId) {
        console.log("drawDevice("+devLogicId+")");
        dev = devList[devLogicId];
        console.log("dev=", dev);

        addr = dev['addr'];
        if (addr == '0000')
            isZigate = true;
        else
            isZigate = false;
        if (dev['posX'] == 0) // 0 is forbidden
            dev['posX'] = setAutoX(isZigate);
        if (dev['posY'] == 0) // 0 is forbidden
            dev['posY'] = setAutoY(isZigate);
        nodeColor = dev['color'];

        // dev['posX'] = 25;
        // dev['posY'] = 25; // TEMP

        // Computing positions based on node central coordinates
        posX = dev['posX'];
        posY = dev['posY'];
        // txtX = posX + 22;
        // txtY = posY + 0;
        txtX = 25;
        txtY = -5; // Placed on top of group
        grpX = posX - 25;
        grpY = posY - 25;
        // imgX = posX - 20;
        // imgY = posY - 20;
        imgX = 5;
        imgY = 5;

        // Checking limits
        grpCoord = checkLimits(grpX, grpY);
        grpX = grpCoord['x'];
        grpY = grpCoord['y'];

        newG = '<g id="'+devLogicId+'" class="draggable" transform="translate('+grpX+', '+grpY+')">';
        newG += '<rect rx="10" ry="10" width="50" height="50" style="fill:'+nodeColor+'" />';
        newG += '<image xlink:href="/plugins/Abeille/images/node_' + dev['icon'] + '.png" x="'+imgX+'" y="'+imgY+'" height="40" width="40" />';
        newG += '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+dev['jeedomId']+'" target="_blank"><text x="'+txtX+'" y="'+txtY+'" fill="black" style="font-size: 12px;">'+dev['name']+'</text></a>';
        newG += '</g>';

        // newG = '<g id="'+devLogicId+'" class="draggable">';
        // newG += '<rect x="'+rectX+'" y="'+rectY+'" rx="10" ry="10" width="50" height="50" style="fill:'+nodeColor+'" />';
        // newG += '<image xlink:href="/plugins/Abeille/images/node_' + dev['icon'] + '.png" x="'+imgX+'" y="'+imgY+'" height="40" width="40" />';
        // newG += '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+dev['jeedomId']+'" target="_blank"><text x="'+txtX+'" y="'+txtY+'" fill="black" style="font-size: 8px;">'+dev['name']+'</text></a>';
        // newG += '</g>';

        // newG += '<circle cx="'+posX+'" cy="'+posY+'" r="10" fill="'+dev['color']+'" transform="translate(0, 0)"></circle>';
        // newG += '<img x="'+posX+'" y="'+posY+'" width="40" height="40" src="/plugins/Abeille/images/node_' + dev['icon'] + '.png">';
        // if( (typeof jeedomDevices[shortAddress] === "object") && (jeedomDevices[shortAddress] !== null) ) {
        //     lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+jeedomDevices[shortAddress].id+'" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].objectName+' - '+myObj[shortAddress].name+' - '+' ('+shortAddress+')</text> </a>';
        // }
        // else {
        //     lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].name+' ('+shortAddress+')</text> </a>';

        console.log("newG=", newG);
        return newG;
    }

    function drawLinks() {
        console.log("drawLinks()");

        console.log('linksList=', linksList);
        for (linkId in linksList) {
            // Reminder: linksList[linkId] = { "src": ss, "dst": ddd }
            link = linksList[linkId];
            console.log('link['+linkId+']=', link);
            // linkId = link.id;

            srcDev = devList[link.src];
            x1 = srcDev['posX'];
            y1 = srcDev['posY'];

            dstDev = devList[link.dst];
            x2 = dstDev['posX'];
            y2 = dstDev['posY'];

            if (typeof link['lqi'] === 'undefined')
                linkColor = "green";
            else if (link['lqi'] > 150)
                linkColor = "green";
            else if (link['lqi'] > 50)
                linkColor = "orange";
            else
                linkColor = "red";

            lesAbeilles += '<line id="idLine'+linkId+'" x1="'+x1+'" y1="'+y1+'" x2="'+x2+'" y2="'+y2+'" style="stroke:'+linkColor+';stroke-width:2"/>';
        }
    }

    refreshPage();
</script>
