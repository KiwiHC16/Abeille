<?php
    include_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
?>

<script type="text/javascript">
    // Thanks to http://www.petercollingridge.co.uk/tutorials/svg/interactive/dragging/

console.log("Begin --------------");

//-----------------------------------------------------------------------
// Global Variables
//-----------------------------------------------------------------------
var myVoisinesOrg;
var topo;
var networkInformation = "";
var networkInformationProgress = "Processing";
var TopoSetReply = "";

var a = 10;
var centerJSON = '{ "X": 500, "Y": 500, "rayon": "400" }';
var center = JSON.parse(centerJSON);

var Ruche = "Abeille1";
var Source = "All";
var Destination = "All";
var Parameter = "LinkQualityDec";
var Hierarchy = "All";

var myJSON = '{}';

// myObjOrg et myObjNew ne contiennent que la ruche au chargement du script
// On parcourt les abeilles de jeedom on l'ajoute à myObjOrg et myObjNew
// On affecte une position stupide, qu'on mettre à jour une fois les info disponibles.
// Idem pour la couleur
var myObjOrg = JSON.parse(myJSON);
var myObjNew = JSON.parse(myJSON);

//-----------------------------------------------------------------------
// Functions
//-----------------------------------------------------------------------

function getVoisinesJSON() {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                myVoisinesOrg = JSON.parse(this.responseText);
            }
        };

        xmlhttp.open("GET", "/plugins/Abeille/Network/tmp/AbeilleLQI_MapData"+Ruche+".json", false); // False pour bloquer sur la recuperation du fichier
        xmlhttp.send();
    }

function getTopoJSON() {
    var xmlhttpGetTopo = new XMLHttpRequest();
    xmlhttpGetTopo.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            topo = JSON.parse(this.responseText);
        }
    };

    xmlhttpGetTopo.open("GET", "/plugins/Abeille/Network/TestSVG/TopoGet.php", false); // False pour bloquer sur la recuperation du fichier
    xmlhttpGetTopo.send();
}

function setTopoJSON(Topo) {
    // console.log("Coucou");
    // var requestTopo = "/plugins/Abeille/Network/TestSVG/TopoSet.php?TopoJSON=";
    var requestTopoURL = "/plugins/Abeille/Network/TestSVG/TopoSet.php";
    // var param = encodeURIComponent(Topo);
    var param = "TopoJSON="+Topo;
    // console.log(param);
    // console.log(Topo);
    // console.log(requestTopo+Topo);
    var xmlhttpSetTopo = new XMLHttpRequest();

    xmlhttpSetTopo.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200 ) {
            TopoSetReply = this.responseText;
            console.log(TopoSetReply);
        }
    }

    xmlhttpSetTopo.open("POST", requestTopoURL, true );

    // xmlhttpSetTopo.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");



    // console.log(requestTopo+Topo);
    // xmlhttpSetTopo.setRequestHeader('Content-Type: application/json; charset=utf-8');
    xmlhttpSetTopo.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // xmlhttpSetTopo.setRequestHeader('Content-Type','application/x-www-form-urlencoded;charset=utf-8');

    xmlhttpSetTopo.send(encodeURI(param));
    // xmlhttpSetTopo.send(encodeURI("TopoJSON=toto"));
    console.log("Params sent->"+param);

}

function refreshNetworkInformation() {
    var xmlhttpRefreshNetworkInformation = new XMLHttpRequest();
    xmlhttpRefreshNetworkInformation.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            networkInformation = this.responseText;
            console.log("Debug - refreshNetworkInformation function:"+networkInformation);
        }
    };
    xmlhttpRefreshNetworkInformation.open("GET", "/plugins/Abeille/Network/AbeilleLQI.php", true);
    xmlhttpRefreshNetworkInformation.send();
}

function refreshNetworkInformationProgress() {
    var d = new Date();
    var xmlhttpRefreshNetworkInformationProgress = new XMLHttpRequest();
    xmlhttpRefreshNetworkInformationProgress.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            networkInformationProgress = this.responseText;
            // console.log("Debug - Progress:"+networkInformationProgress);
        }
    };
    xmlhttpRefreshNetworkInformationProgress.open("GET", "/plugins/Abeille/Network/tmp/AbeilleLQI_MapData"+Ruche+".json.lock?"+d.getTime(), true);
    xmlhttpRefreshNetworkInformationProgress.send();
}

function makeDraggable(evt) {
    var svg = evt.target;
    svg.addEventListener('mousedown',   startDrag, false);
    svg.addEventListener('mousemove',   drag, false);
    svg.addEventListener('mouseup',     endDrag, false);
    svg.addEventListener('mouseleave',  endDrag);

    function getMousePosition(evt) {
        var CTM = svg.getScreenCTM();
        return {
            x: (evt.clientX - CTM.e) / CTM.a,
            y: (evt.clientY - CTM.f) / CTM.d
        };
    }

    var selectedElement, offset, transform;
    var positionX = "Position: X=";
    var positionY = " Y=";
    var X = 0;
    var Y = 0;

    function startDrag(evt) {

        console.log("Debug - Selection de : " + evt.target.nodeName + " - " + evt.target.classList + " - " + evt.target.id ); // circle, svg

        if (evt.target.classList.contains('draggable')) {
            selectedElement = evt.target;
            offset = getMousePosition(evt); // Position du clic de souris dans les coordonnées SVG.
            console.log("Debug - offset: "+JSON.stringify(offset));

            // Make sure the first transform on the element is a translate transform
            // Should not be needed as Abeille have a Translate
            var transforms = selectedElement.transform.baseVal;

            if (transforms.length === 0 || transforms.getItem(0).type !== SVGTransform.SVG_TRANSFORM_TRANSLATE) {
                // Create an transform that translates by (0, 0)
                console.log("Debug - Need a TRANSLATE - NOT expected");
                var translate = svg.createSVGTransform();
                translate.setTranslate(0, 0);
                selectedElement.transform.baseVal.insertItemBefore(translate, 0);
            }

            // Get initial translation from Abeille Object
            transform = transforms.getItem(0);
            offset.x -= transform.matrix.e;
            offset.y -= transform.matrix.f;

            console.log("Debug - offset with transform: "+JSON.stringify(offset));
        }
    }

    function drag(evt) {
        if (selectedElement) {
            evt.preventDefault();
            var coord = getMousePosition(evt);
            // On change les valeurs de Translation mais on ne touche pas aux coordonnées.
            transform.setTranslate(coord.x - offset.x, coord.y - offset.y);
        }
    }

    function endDrag(evt) {

        if (selectedElement) {
            evt.preventDefault();
            var coord = getMousePosition(evt);
            console.log("Debug - endDrag - coord (arrivée): "+JSON.stringify(coord));
            // Ne change pas les coordonnées de l objet mais sa translation
            transform.setTranslate(coord.x - offset.x, coord.y - offset.y);
            console.log("Debug - endDrag - offset (depart): "+JSON.stringify(offset));
            X = coord.x - offset.x;
            Y = coord.y - offset.y;
            console.log("Debug - endDrag - Dx: "+X+" Dy: "+Y);

            // var myJSONOrg = JSON.stringify(myObjOrg);

            if ( typeof myObjOrg[evt.target.id].x == "undefined" ) {
            }
            else {
                myObjNew[evt.target.id].x = myObjOrg[evt.target.id].x + X;
                myObjNew[evt.target.id].y = myObjOrg[evt.target.id].y + Y;
            }
            // var myJSONNew = JSON.stringify(myObjNew);

            document.getElementById("lesVoisines").innerHTML = dessineLesVoisinesV2(0,"No");
            document.getElementById("lesTextes").innerHTML = dessineLesTextes(10,"No");
            document.getElementById("lesAbeillesText").innerHTML =  dessineLesAbeillesText(myObjNew, 22, "No");
        }
        selectedElement = false;
    }
}

    /* Draw 'legend' area */
    function drawLegend(includeGroup) {
        var legend = "";

        if ( includeGroup=="Yes" ) { legend = legend + '<g id="legend">'; }

        legend = legend + '<circle cx="100" cy="875" r="10" fill="red" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="875" fill="red" style="font-size: 8px;">Coordinator</text> </a>\n';

        legend = legend + '<circle cx="100" cy="900" r="10" fill="green" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="900" fill="green" style="font-size: 8px;">End Equipment</text> </a>\n';

        legend = legend + '<circle cx="100" cy="925" r="10" fill="orange" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="925" fill="orange" style="font-size: 8px;">Routeur</a>\n';

        legend = legend + '<circle cx="100" cy="950" r="10" fill="grey" />\n';
        legend = legend + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="950" fill="grey" style="font-size: 8px;">Dans Jeedom mais pas dans l audit du reseau</text> </a>\n';

        if ( includeGroup=="Yes" ) { legend = legend + '</g>'; }

        return legend;
    }

function dessineLesTextes(offsetX, includeGroup) {
    var lesTextes = "";
    var info = "";

    if ( includeGroup=="Yes" ) { lesTextes = lesTextes + '<g id="lesTextes">'; }

    for (voisines in myVoisinesOrg.data) {

        // lesTextes = lesTextes + myVoisinesOrg.data[voisines].NE + "->" + myVoisinesOrg.data[voisines].Voisine + " / ";
        var NE = myVoisinesOrg.data[voisines].NE; var voisine = myVoisinesOrg.data[voisines].Voisine;
        var X1=0; var X2=0; var Y1=0; var Y2=0; var midX=0; var midY=0;

        if ( ( (Source=="All") || (Source==NE) || (Destination=="All")|| (Destination==voisine) ) && ( (Hierarchy==myVoisinesOrg.data[voisines].Relationship) || (Hierarchy=="All") ) ) {
            if ( typeof myObjNew[NE] == "undefined" ) {

            }
            else {
                if ( typeof myObjNew[NE].x == "undefined" )      {X1=0;} else { X1 = myObjNew[NE].x + offsetX; }
                if ( typeof myObjNew[NE].y == "undefined" )      {Y1=0;} else { Y1 = myObjNew[NE].y; }
            }
            if ( typeof myObjNew[voisine] == "undefined" ) {
            }
            else {
                if ( typeof myObjNew[voisine].x == "undefined" ) {X2=0;} else { X2 = myObjNew[voisine].x + offsetX; }
                if ( typeof myObjNew[voisine].y == "undefined" ) {Y2=0;} else { Y2 = myObjNew[voisine].y; }
            }

            midX=(X1+X2)/2; midY=(Y1+Y2)/2;

            if ( Parameter == "LinkQualityDec" )    { info = myVoisinesOrg.data[voisines].LinkQualityDec;   }
            if ( Parameter == "Depth" )             { info = myVoisinesOrg.data[voisines].Depth;            }
            if ( Parameter == "Voisine" )           { info = myVoisinesOrg.data[voisines].Voisine;          }
            if ( Parameter == "IEEE_Address" )      { info = myVoisinesOrg.data[voisines].IEEE_Address;     }
            if ( Parameter == "Type" )              { info = myVoisinesOrg.data[voisines].Type;             }
            if ( Parameter == "Relationship" )      { info = myVoisinesOrg.data[voisines].Relationship;     }
            if ( Parameter == "Rx" )                { info = myVoisinesOrg.data[voisines].Rx;               }

            // console.log("dessineLesTextes function: Parameter: " + Parameter );

            lesTextes = lesTextes + '<text x="'+midX+'" y="'+midY+'" fill="purple" style="font-size: 8px;">'+info+'</text>';
        }
    }

    if ( includeGroup=="Yes" ) { lesTextes = lesTextes + '</g>'; }

    return lesTextes;
}

function dessineLesVoisinesV2(offsetX,includeGroup) {
    var lesVoisines = "";

    if ( includeGroup=="Yes" ) { lesVoisines = lesVoisines + '<g id="lesVoisines">'; }

    for (voisines in myVoisinesOrg.data) {

        //var NE = myVoisinesOrg.data[voisines].NE;
        var NE = myVoisinesOrg.data[voisines].NE;
        var voisine = myVoisinesOrg.data[voisines].Voisine;

        if ( ( (Source=="All") || (Source==NE) || (Destination=="All")|| (Destination==voisine) ) && ( (Hierarchy==myVoisinesOrg.data[voisines].Relationship) || (Hierarchy=="All") ) ) {
            var X1=0; var X2=0; var Y1=0; var Y2=0;
            var color="orange";

            if ( typeof myObjNew[NE] == "undefined" ) {

            }
            else {
                if ( typeof myObjNew[NE].x == "undefined" )      {X1=0;} else { X1 = myObjNew[NE].x + offsetX; }
                if ( typeof myObjNew[NE].y == "undefined" )      {Y1=0;} else { Y1 = myObjNew[NE].y; }
            }
            if ( typeof myObjNew[voisine] == "undefined" ) {
            }
            else {
                if ( typeof myObjNew[voisine].x == "undefined" ) {X2=0;} else { X2 = myObjNew[voisine].x + offsetX; }
                if ( typeof myObjNew[voisine].y == "undefined" ) {Y2=0;} else { Y2 = myObjNew[voisine].y; }
            }

            if ( myVoisinesOrg.data[voisines].LinkQualityDec > 150 ) { color = "green"; }
            if ( myVoisinesOrg.data[voisines].LinkQualityDec <  50 ) { color = "red";}

            lesVoisines = lesVoisines + '<line class="zozo" x1="'+X1+'" y1="'+Y1+'" x2="'+X2+'" y2="'+Y2+'" style="stroke:'+color+';stroke-width:1"/>';
        }

    }

    if ( includeGroup=="Yes" ) { lesVoisines = lesVoisines + '</g>'; }

    return lesVoisines;
}

function dessineLesAbeilles(includeGroup) {
    var lesAbeilles = "";

    if ( includeGroup=="Yes" ) { lesAbeilles = lesAbeilles + '<g id="lesAbeilles">'; }
    for (shortAddress in myObjNew) {
        myObjOrg[shortAddress].x = myObjNew[shortAddress].x;
        myObjOrg[shortAddress].y = myObjNew[shortAddress].y;
        lesAbeilles = lesAbeilles + '<circle class="draggable" id="'+shortAddress+'"   cx="'+myObjNew[shortAddress].x+'"  cy="'+myObjNew[shortAddress].y+'"              r="10"           fill="'+myObjNew[shortAddress].color+'"  transform="translate(0, 0)"></circle>';
    }
    if ( includeGroup=="Yes" ) { lesAbeilles = lesAbeilles + '</g>'; }
    return lesAbeilles;
}

function dessineLesAbeillesText(myObj,offsetX,includeGroup) {
    var lesAbeillesText = "";
    var X = 0;
    var Y = 0;

    if ( includeGroup=="Yes" ) { lesAbeillesText = lesAbeillesText + '<g id="lesAbeillesText">'; }

    // console.log( JSON.stringify(myObj) );
    for (shortAddress in myObj) {
        X = myObj[shortAddress].x + offsetX;
        Y = myObj[shortAddress].y;
        if( (typeof topo[shortAddress] === "object") && (topo[shortAddress] !== null) ) {
            lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+topo[shortAddress].id+'" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].objectName+' - '+myObj[shortAddress].name+' - '+' ('+shortAddress+')</text> </a>';
        }
        else {
            lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].name+' ('+shortAddress+')</text> </a>';
        }
    }
    if ( includeGroup=="Yes" ) { lesAbeillesText = lesAbeillesText + '</g>'; }
    return lesAbeillesText;
}

function setPosition(mode) {
    var iAbeille = 0;
    var nbAbeille = Object.keys(myObjNew).length
    for (abeille in myObjNew) {
        if ( (myObjNew[abeille].positionDefined == "No") && (mode=="Auto") ) {
            X = eval('center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.
            Y = eval('center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.

            myObjOrg[abeille].x = X;
            myObjOrg[abeille].y = Y;
            myObjNew[abeille].x = X;
            myObjNew[abeille].y = Y;

            myObjNew[abeille].positionDefined = "Yes";

            iAbeille++;
        }
        if ( mode=="AutoForce" ) {
            X = eval('center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.
            Y = eval('center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille)');   // je passe par eval car le char '/' met la pagaille dans l indentation du fichier.

            myObjOrg[abeille].x = X;
            myObjOrg[abeille].y = Y;
            myObjNew[abeille].x = X;
            myObjNew[abeille].y = Y;

            myObjNew[abeille].positionDefined = "Yes";

            iAbeille++;
        }
    }
}

function refreshNetworkCollectionProgress() {
    refreshNetworkInformationProgress();
    console.log("Debug - function - refreshNetworkCollectionProgress - "+networkInformationProgress);
    document.getElementById("refreshInformation").innerHTML = networkInformationProgress;
}

function myJSON_AddAbeillesFromJeedom() {
    console.log("topo: "+JSON.stringify(topo));
    for (logicalId in topo) {
        console.log("logicalId: "+logicalId);

        myObjOrg[logicalId] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
        myObjNew[logicalId] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };

        console.log("logicalId: "+logicalId+" -> "+JSON.stringify(topo[logicalId]));
        myObjOrg[logicalId].objectName = topo[logicalId].objectName;
        myObjNew[logicalId].objectName = topo[logicalId].objectName;

        myObjOrg[logicalId].name = topo[logicalId].name;
        myObjNew[logicalId].name = topo[logicalId].name;

        console.log("logicalId: "+logicalId+" -> x: "+topo[logicalId].X);
        myObjOrg[logicalId].x = topo[logicalId].X;
        myObjNew[logicalId].x = topo[logicalId].X;

        myObjOrg[logicalId].y = topo[logicalId].Y;
        myObjNew[logicalId].y = topo[logicalId].Y;

        if ( (topo[logicalId].X>0) && (topo[logicalId].Y>0) ) {
            myObjOrg[logicalId].positionDefined = "Yes";
            myObjNew[logicalId].positionDefined = "Yes";
        }
        else {
            myObjOrg[logicalId].positionDefined = "No";
            myObjNew[logicalId].positionDefined = "No";
        }
    }

    console.log("myObjOrg: "+JSON.stringify(myObjOrg));
}

function myJSON_AddMissing() {
    var color = "";

    for (voisines in myVoisinesOrg.data) {
        // console.log("Voisine: "+myVoisinesOrg.data[voisines].NE+"->"+myVoisinesOrg.data[voisines].Voisine);

        if ( typeof myObjOrg[myVoisinesOrg.data[voisines].NE] === "undefined" ) {

            myObjOrg[myVoisinesOrg.data[voisines].NE] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
            myObjNew[myVoisinesOrg.data[voisines].NE] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };

            if ( typeof topo[myVoisinesOrg.data[voisines].NE] === "undefined" ) {
                myObjOrg[myVoisinesOrg.data[voisines].NE].name = "Pas dans Jeedom";
                myObjOrg[myVoisinesOrg.data[voisines].NE].name = "Pas dans Jeedom";
            }
            else {
                myObjOrg[myVoisinesOrg.data[voisines].NE].name = topo[myVoisinesOrg.data[voisines].NE].name;
                myObjNew[myVoisinesOrg.data[voisines].NE].name = topo[myVoisinesOrg.data[voisines].NE].name;
            }

        }

        if ( typeof myObjOrg[myVoisinesOrg.data[voisines].Voisine] === "undefined" ) {

            myObjOrg[myVoisinesOrg.data[voisines].Voisine] = { "name": "NoName", "x": 50, "y": 150, "color": "black", "positionDefined":"No", "Type":"Inconnu" };
            myObjNew[myVoisinesOrg.data[voisines].Voisine] = { "name": "NoName", "x": 50, "y": 150, "color": "black", "positionDefined":"No", "Type":"Inconnu" };

            if ( myVoisinesOrg.data[voisines].Type == "End Device" ) { color="Green"; }
            if ( myVoisinesOrg.data[voisines].Type == "Router" ) { color="Orange"; }
            if ( myVoisinesOrg.data[voisines].Type == "Coordinator" ) { color="Red"; }
            myObjOrg[myVoisinesOrg.data[voisines].Voisine].color = color;
            myObjNew[myVoisinesOrg.data[voisines].Voisine].color = color;

            if ( typeof topo[myVoisinesOrg.data[voisines].Voisine] === "undefined" ) {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
            }
            else {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = topo[myVoisinesOrg.data[voisines].Voisine].name;
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = topo[myVoisinesOrg.data[voisines].Voisine].name;
            }
        }
        else {
            if ( myVoisinesOrg.data[voisines].Type == "End Device" ) { color="Green"; }
            if ( myVoisinesOrg.data[voisines].Type == "Router" ) { color="Orange"; }
            if ( myVoisinesOrg.data[voisines].Type == "Coordinator" ) { color="Red"; }
            myObjOrg[myVoisinesOrg.data[voisines].Voisine].color = color;
            myObjNew[myVoisinesOrg.data[voisines].Voisine].color = color;

            if ( typeof topo[myVoisinesOrg.data[voisines].Voisine] === "undefined" ) {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
            }
            else {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = topo[myVoisinesOrg.data[voisines].Voisine].name;
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = topo[myVoisinesOrg.data[voisines].Voisine].name;
            }
        }
    }
}

function refreshAll(mode) {
    console.log("function refreshAll: "+ mode );

    if ( mode == "All" ) {
        getVoisinesJSON();
        getTopoJSON();

        myJSON_AddAbeillesFromJeedom();
        // console.log("myObjOrg: "+JSON.stringify(myObjOrg));
        myJSON_AddMissing();
        // console.log("myObjOrg: "+JSON.stringify(myObjOrg));
    }

    document.getElementById("legend").innerHTML = drawLegend("No");
    document.getElementById("lesVoisines").innerHTML = dessineLesVoisinesV2(0,"No");
    document.getElementById("lesTextes").innerHTML = dessineLesTextes(10,"No");
    document.getElementById("lesAbeillesText").innerHTML = dessineLesAbeillesText(myObjNew, 22,"No");
    document.getElementById("lesAbeilles").innerHTML = dessineLesAbeilles("No");
    // Je ne peux pas redessiner les abeilles car l'objet graphique contient la Transklation et les x, y d'origine
    // alors que dans myObjNew j'ai les nouvelles coordonnées mais pas la translation.
}

function rucheCentered() {
    myObjNew[Ruche+"/Ruche"].x = center.X;
    myObjNew[Ruche+"/Ruche"].y = center.Y;
    refreshAll();
}

function placementAuto() {
    setPosition("AutoForce");
    refreshAll();
}

function save() {
    // Thanks to https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify
    localStorage.setItem('myObjNew', JSON.stringify(myObjNew));
}

function restore() {
    // Thanks to https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify
    myObjOld = JSON.parse( localStorage.getItem('myObjNew') );
    myObjNew = JSON.parse( localStorage.getItem('myObjNew') );
    refreshAll();
}

function selectRuche(form) {
    Ruche = form.list.value;
    refreshAll("All");
}

function selectSource(form) {
    Source = form.list.value;
    refreshAll();
}

function selectDestination(form) {
    Destination = form.list.value;
    refreshAll();
}

function selectDetails(form) {
    Parameter = form.list.value;
    // console.log("selectDetails function: Parameter: " + Parameter );
    refreshAll();
}

function selectParent(form) {
    Hierarchy = form.list.value;
    refreshAll();
}

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

function filtreSource() {
    document.write( '<FORM NAME="myformSource" ACTION="" METHOD="GET">');
    document.write( '<SELECT NAME="list" >');
    document.write( '<OPTION value="All" selected>All</OPTION>');
    document.write( '<OPTION value="None" >None</OPTION>');

    for (shortAddress in topo) {
        document.write( '<OPTION value="'+shortAddress+'" >'+topo[shortAddress].name+'</OPTION>');
    }

    document.write( '</SELECT>');
    document.write( '</br>');
    document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectSource(this.form)"/>');
    document.write( '</FORM>');
}

function filtreDestination() {
    document.write( '<FORM NAME="myformDestination" ACTION="" METHOD="GET">');
    document.write( '<SELECT NAME="list" >');
    document.write( '<OPTION value="All" selected>All</OPTION>');
    document.write( '<OPTION value="None" >None</OPTION>');

    for (shortAddress in topo) {
        // console.log("Name: " + JSON.stringify(shortAddress));
        document.write( '<OPTION value="'+shortAddress+'" >'+topo[shortAddress].name+'</OPTION>');
    }

    document.write( '</SELECT>');
    document.write( '</br>');
    document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectDestination(this.form)"/>');
    document.write( '</FORM>');
}

function filtreDetails() {
    var dataList = [ "LinkQualityDec", "Depth", "Voisine", "IEEE_Address", "Type", "Relationship", "Rx" ];

    document.write( '<FORM NAME="myformDetails" ACTION="" METHOD="GET">');
    document.write( '<SELECT NAME="list" >');

    for (data in dataList) {
        document.write( '<option value="'+dataList[data]+'">'+dataList[data]+'</option>');
    }

    document.write( '</SELECT>');
    document.write( '</br>');
    document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectDetails(this.form)"/>');
    document.write( '</FORM>');
}

function filtreParent() {
    var dataList = [ "All", "Sibling", "Child" ];

    document.write( '<FORM NAME="myformParent" ACTION="" METHOD="GET">');
    document.write( '<SELECT NAME="list" >');

    for (data in dataList) {
        // if ( $Data==$item ) { $selected = " selected "; } else { $selected = " "; }
        document.write( '<option value="'+dataList[data]+'">'+dataList[data]+'</option>');
    }

    document.write( '</SELECT>');
    document.write( '</br>');
    document.write( '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectParent(this.form)">');
    document.write( '</FORM>');
}

function ReLoad() {
    // to be implemented
    location.reload(true);
}

function saveAbeilles() {
    // console.log("Debug - saveAbeilles function - "+JSON.stringify(myObjNew));
    setTopoJSON(JSON.stringify(myObjNew));
}

//-----------------------------------------------------------------------
// MAIN
//-----------------------------------------------------------------------

const queryString = window.location.search;
console.log(queryString);
res = queryString.substr(1);
console.log(res);
if (res.length>2) Ruche = res;


getVoisinesJSON();
getTopoJSON();

myJSON_AddAbeillesFromJeedom();
// console.log("myObjOrg: "+JSON.stringify(myObjOrg));
myJSON_AddMissing();
// console.log("myObjOrg: "+JSON.stringify(myObjOrg));

setPosition("Auto");
</script>

<html>
<head>
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
</head>

<body>
Zigate selectionnee: <script>document.write(res);</script>
<p id="demo"></p>
<svg id="dessin" xmlns="http://www.w3.org/2000/svg" width="1100px" height="1100px" onload="makeDraggable(evt)">

<?php
    if (file_exists("/var/www/html/plugins/Abeille/Network/TestSVG/images/AbeilleLQI_MapData_Perso.png")) {
        echo '<image x="0" y="0" width="1100px" height="1100px" xlink:href="/plugins/Abeille/Network/TestSVG/images/AbeilleLQI_MapData_Perso.png" ></image>';
    }
    else {
        echo '<image x="0" y="0" width="1100px" height="1100px" xlink:href="/plugins/Abeille/Network/TestSVG/images/AbeilleLQI_MapData.png" ></image>';
    }
?>
<script>
document.write( drawLegend("Yes") );
document.write( dessineLesVoisinesV2(0,"Yes") );
document.write( dessineLesTextes(10,"Yes") );
document.write( dessineLesAbeillesText(myObjNew, 22, "Yes") );
document.write( dessineLesAbeilles("Yes") );
</script>

</svg></br>

Selectionner la Zigate a afficher:</br>
<table><tr>
<?php
for ($i=1; $i<=10; $i++) {
    if ( config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') == 'Y' ) {
        ?>
        <td><input id="btntest" type="button" value="Zigate <?php echo $i; ?>" onclick="window.location.href = '/plugins/Abeille/Network/TestSVG/NetworkGraph.php?Abeille<?php echo $i; ?>'" /></td>
<?php
    }
}
?>

</tr></table>

<table><tr>
<tr><td>Source</td><td>Destination</td><td>Parametre</td><td>Relation</td></tr>
<td>   <script>filtreSource(); </script>        </td>
<td>   <script>filtreDestination();</script>    </td>
<td>   <script>filtreDetails();  </script>      </td>
<td>   <script>filtreParent();  </script>       </td>
</tr></table>

<table><tr><td>
<button id="rucheCentered" onclick="rucheCentered()"   >Ruche Centered</button>
<button id="placementAuto" onclick="placementAuto()"   >Placement Auto</button>
<button id="save"          onclick="save()"            >local save</button>
<button id="restore"       onclick="restore()"         >local restore</button>
<button id="save"          onclick="saveAbeilles()"    >save</button>
</td></tr></table>

<table><tr><td>
<button id="Refresh" onclick="refreshNetworkInformation()">Refresh Network Information</button>
</td><td>
<p id="refreshInformation">'+networkInformationProgress+'</p>
</td><td>
<button id="ReLoadThePage" onclick="ReLoad()">Rafraichir le graphe.</button>
</td></tr></table>

<br>

<form action="upload.php" method="post" enctype="multipart/form-data">
<table><tr><td>
Select image to upload:<br>
</td><td>
<input type="file" name="fileToUpload" id="fileToUpload"/>
</td><td>
<input type="submit" value="Upload Image" name="submit"/>
</td><td>
<p>(L image doit etre au format png)</p>
</td></tr></table>
</form>

</body>
</html>

<script>
setInterval(function() {
            refreshNetworkCollectionProgress();
            // console.log("function refreshNetworkCollectionProgress");
            }, 1000); // ms

            // console.log("Name list: "+JSON.stringify(myVoisinesOrg));
            // console.log("Name list: "+JSON.stringify(topo));
            // console.log("Name 1: " + JSON.stringify(topo["0000"]));

            console.log("End --------------");

</script>
