// Thanks to http://www.petercollingridge.co.uk/tutorials/svg/interactive/dragging/

console.log("Begin --------------");

var myVoisinesOrg;
var names;
var networkInformation = "";
var networkInformationProgress = "Workping";
var TopoSetReply = "";

var a = 10;
var centerJSON = '{ "X": 500, "Y": 500, "rayon": "400" }';
var center = JSON.parse(centerJSON);

var Source = "All";
var Destination = "All";
var Parameter = "LinkQualityDec";
var Hierarchy = "All";

var myJSON = '{ "0000": { "name": "Ruche", "x": 500, "y": 500, "color": "Red", "positionDefined":"Yes", "Type":"Coordinator" } }';

var myObjOrg = JSON.parse(myJSON);
var myObjNew = JSON.parse(myJSON);

function getVoisinesJSON() {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            myVoisinesOrg = JSON.parse(this.responseText);
        }
    };
    
    xmlhttp.open("GET", "/plugins/Abeille/Network/AbeilleLQI_MapData.json", false); // False pour bloquer sur la recuperation du fichier
    xmlhttp.send();
}

function getTopoJSON() {
    var xmlhttpGetTopo = new XMLHttpRequest();
    xmlhttpGetTopo.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            names = JSON.parse(this.responseText);
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
    // console.log(requestTopo);
    // console.log(Topo);
    // console.log(requestTopo+Topo);
    var xmlhttpSetTopo = new XMLHttpRequest();
    
    xmlhttpSetTopo.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200 ) {
            TopoSetReply = this.responseText;
            // console.log(TopoSetReply);
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
    xmlhttpRefreshNetworkInformationProgress.open("GET", "/plugins/Abeille/Network/AbeilleLQI_MapData.json.lock?"+d.getTime(), true);
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
            
            document.getElementById("lesVoisines").innerHTML = dessineLesVoisines(0,"No");
            document.getElementById("lesTextes").innerHTML = dessineLesTextes(10,"No");
            document.getElementById("lesAbeillesText").innerHTML =  dessineLesAbeillesText(myObjNew, 22, "No");
        }
        selectedElement = false;
    }
}

function dessineLaLegende(includeGroup) {
    var legende = "";
    
    if ( includeGroup=="Yes" ) { legende = legende + '<g id="legend">'; }
    
    legende = legende + '<circle cx="100" cy="875" r="10" fill="red" /></br>\n';
    legende = legende + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="875" fill="red" style="font-size: 8px;">Coordinator</text> </a></br>\n';
    
    legende = legende + '<circle cx="100" cy="900" r="10" fill="green" /></br>\n';
    legende = legende + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="900" fill="green" style="font-size: 8px;">End Equipment</text> </a></br>\n';
    
    legende = legende + '<circle cx="100" cy="925" r="10" fill="orange" />\n';
    legende = legende + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="925" fill="orange" style="font-size: 8px;">Routeur</a></br>\n';
    
    legende = legende + '<circle cx="100" cy="950" r="10" fill="grey" />\n';
    legende = legende + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="950" fill="grey" style="font-size: 8px;">Dans Jeedom mais pas dans l audit du reseau</text> </a></br>\n';
    
    if ( includeGroup=="Yes" ) { legende = legende + '</g>'; }
    
    return legende;
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

function dessineLesVoisines(offsetX,includeGroup) {
    var lesVoisines = "";
    
    if ( includeGroup=="Yes" ) { lesVoisines = lesVoisines + '<g id="lesVoisines">'; }
    
    for (voisines in myVoisinesOrg.data) {
        
        var NE = myVoisinesOrg.data[voisines].NE; var voisine = myVoisinesOrg.data[voisines].Voisine;
        
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
        if( (typeof names[0][shortAddress] === "object") && (names[0][shortAddress] !== null) ) {
            lesAbeillesText = lesAbeillesText + '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille&id='+names[0][shortAddress].logicalId+'" target="_blank"> <text x="'+X+'" y="'+Y+'" fill="black" style="font-size: 8px;">'+myObj[shortAddress].name+' ('+shortAddress+')</text> </a>';
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
            X = center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille);
            Y = center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille);
            
            myObjOrg[abeille].x = X;
            myObjOrg[abeille].y = Y;
            myObjNew[abeille].x = X;
            myObjNew[abeille].y = Y;
            
            myObjNew[abeille].positionDefined = "Yes";
            
            iAbeille++;
        }
        if ( mode=="AutoForce" ) {
            X = center.X + center.rayon * Math.cos(2*Math.PI*iAbeille/nbAbeille);
            Y = center.Y + center.rayon * Math.sin(2*Math.PI*iAbeille/nbAbeille);
            
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

// myObjOrg et myObjNew ne contiennent que la ruche au chargement du script
// On parcourt les abeilles de jeedom on l'ajoute à myObjOrg et myObjNew
// On affecte une position stupide, qu'on mettre à jour une fois les info disponibles.
// Idem pour la couleur
function myJSON_AddAbeillesFromJeedom() {
    // console.log("Names: "+JSON.stringify(names));
    for (name in names[0]) {
        
        if (name!="0000") {
            myObjOrg[name] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
            myObjNew[name] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
            
            // console.log("Name: "+name+" "+JSON.stringify(names[0][name]));
            
            myObjOrg[name].name = names[0][name].name;
            myObjNew[name].name = names[0][name].name;
        }
        
        myObjOrg[name].x = names[0][name].positionX;
        myObjNew[name].x = names[0][name].positionX;
        
        myObjOrg[name].y = names[0][name].positionY;
        myObjNew[name].y = names[0][name].positionY;
        
        if ( (names[0][name].positionX>0) && (names[0][name].positionY>0) ) {
            myObjOrg[name].positionDefined = "Yes";
            myObjNew[name].positionDefined = "Yes";
        }
        else {
            myObjOrg[name].positionDefined = "No";
            myObjNew[name].positionDefined = "No";
        }
    }
}

// myObjOrg et myObjNew ne contiennent que la ruche au chargement du script
// On parcourt las voisines et à chaque nouvelle abeille on l'ajoute à myObjOrg et myObjNew
// On affecte une position stupide, qu'on mettre à jour une fois les info disponibles.
// Idem pour la couleur
function myJSON_AddMissing() {
    var color = "";
    
    for (voisines in myVoisinesOrg.data) {
        // console.log("Voisine: "+myVoisinesOrg.data[voisines].NE+"->"+myVoisinesOrg.data[voisines].Voisine);
        
        if ( typeof myObjOrg[myVoisinesOrg.data[voisines].NE] === "undefined" ) {
            
            myObjOrg[myVoisinesOrg.data[voisines].NE] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
            myObjNew[myVoisinesOrg.data[voisines].NE] = { "name": "NoName", "x": 50, "y": 150, "color": "grey", "positionDefined":"No", "Type":"Inconnu" };
            
            if ( typeof names[0][myVoisinesOrg.data[voisines].NE] === "undefined" ) {
                myObjOrg[myVoisinesOrg.data[voisines].NE].name = "Pas dans Jeedom";
                myObjOrg[myVoisinesOrg.data[voisines].NE].name = "Pas dans Jeedom";
            }
            else {
                myObjOrg[myVoisinesOrg.data[voisines].NE].name = names[0][myVoisinesOrg.data[voisines].NE].name;
                myObjNew[myVoisinesOrg.data[voisines].NE].name = names[0][myVoisinesOrg.data[voisines].NE].name;
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
            
            if ( typeof names[0][myVoisinesOrg.data[voisines].Voisine] === "undefined" ) {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
            }
            else {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = names[0][myVoisinesOrg.data[voisines].Voisine].name;
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = names[0][myVoisinesOrg.data[voisines].Voisine].name;
            }
        }
        else {
            if ( myVoisinesOrg.data[voisines].Type == "End Device" ) { color="Green"; }
            if ( myVoisinesOrg.data[voisines].Type == "Router" ) { color="Orange"; }
            if ( myVoisinesOrg.data[voisines].Type == "Coordinator" ) { color="Red"; }
            myObjOrg[myVoisinesOrg.data[voisines].Voisine].color = color;
            myObjNew[myVoisinesOrg.data[voisines].Voisine].color = color;
            
            if ( typeof names[0][myVoisinesOrg.data[voisines].Voisine] === "undefined" ) {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = "Pas dans Jeedom";
            }
            else {
                myObjOrg[myVoisinesOrg.data[voisines].Voisine].name = names[0][myVoisinesOrg.data[voisines].Voisine].name;
                myObjNew[myVoisinesOrg.data[voisines].Voisine].name = names[0][myVoisinesOrg.data[voisines].Voisine].name;
            }
        }
    }
}

function refreshAll() {
    // console.log("function refreshAll: "+ Source + "-" + Destination + "-" + Parameter + "-" + Hierarchy );
    
    document.getElementById("legend").innerHTML = dessineLaLegende("No");
    document.getElementById("lesVoisines").innerHTML = dessineLesVoisines(0,"No");
    document.getElementById("lesTextes").innerHTML = dessineLesTextes(10,"No");
    document.getElementById("lesAbeillesText").innerHTML = dessineLesAbeillesText(myObjNew, 22,"No");
    document.getElementById("lesAbeilles").innerHTML = dessineLesAbeilles("No");
    // Je ne peux pas redessiner les abeilles car l'objet graphique contient la Transklation et les x, y d'origine
    // alors que dans myObjNew j'ai les nouvelles coordonnées mais pas la translation.
}

function rucheCentered() {
    myObjNew["0000"].x = center.X;
    myObjNew["0000"].y = center.Y;
    refreshAll("All");
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

function filtreSource() {
    console.log("Name: " + JSON.stringify(names[0]));
    filtreSourceVar  = '<FORM NAME="myformSource" ACTION="" METHOD="GET">';
    filtreSourceVar += '<SELECT NAME="list" >';
    filtreSourceVar += '<OPTION value="All" selected>All</OPTION>';
    filtreSourceVar += '<OPTION value="None" >None</OPTION>';
    
    for (shortAddress in names[0]) {
        filtreSourceVar += '<OPTION value="'+shortAddress+'" >'+names[0][shortAddress].name+'</OPTION>';
    }
    
    filtreSourceVar += '</SELECT>';
    filtreSourceVar += '</br>';
    filtreSourceVar += '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectSource(this.form)"/>';
    filtreSourceVar += '</FORM>';
    
    return filtreSourceVar;
}

function filtreDestination() {
    filtreDestinationVar  = '<FORM NAME="myformDestination" ACTION="" METHOD="GET">';
    filtreDestinationVar += '<SELECT NAME="list" >';
    filtreDestinationVar += '<OPTION value="All" selected>All</OPTION>';
    filtreDestinationVar += '<OPTION value="None" >None</OPTION>';
    for (shortAddress in names[0]) {
        filtreDestinationVar += '<OPTION value="'+shortAddress+'" >'+names[0][shortAddress].name+'</OPTION>';
    }
    filtreDestinationVar += '</SELECT>';
    filtreDestinationVar += '</br>';
    filtreDestinationVar += '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectDestination(this.form)"/>';
    filtreDestinationVar += '</FORM>';
    
    return filtreDestinationVar;
}

function filtreDetails() {
    var dataList = [ "LinkQualityDec", "Depth", "Voisine", "IEEE_Address", "Type", "Relationship", "Rx" ];
    
    filtreDetailsVar  = '<FORM NAME="myformDetails" ACTION="" METHOD="GET">';
    filtreDetailsVar += '<SELECT NAME="list" >';
    for (data in dataList) {
        filtreDetailsVar += '<option value="'+dataList[data]+'">'+dataList[data]+'</option>';
    }
    filtreDetailsVar += '</SELECT>';
    filtreDetailsVar += '</br>';
    filtreDetailsVar += '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectDetails(this.form)"/>';
    filtreDetailsVar += '</FORM>';
    
    return filtreDetailsVar;
}

function filtreParent() {
    var dataList = [ "All", "Sibling", "Child" ];
    
    filtreParentVar  = '<FORM NAME="myformParent" ACTION="" METHOD="GET">';
    filtreParentVar += '<SELECT NAME="list" >';
    for (data in dataList) {
        filtreParentVar += '<option value="'+dataList[data]+'">'+dataList[data]+'</option>';
    }
    filtreParentVar += '</SELECT>';
    filtreParentVar += '</br>';
    filtreParentVar += '<INPUT TYPE="button" NAME="button" Value="Test" onClick="selectParent(this.form)">';
    filtreParentVar += '</FORM>';
    
    return filtreParentVar;
}

function ReLoad() {
    // to be implemented
    location.reload(true);
}

function saveAbeilles() {
    // console.log("Debug - saveAbeilles function - "+JSON.stringify(myObjNew));
    setTopoJSON(JSON.stringify(myObjNew));
}


getVoisinesJSON();
getTopoJSON();

myJSON_AddAbeillesFromJeedom();
// console.log("myObjOrg: "+JSON.stringify(myObjOrg));
myJSON_AddMissing();
// console.log("myObjOrg: "+JSON.stringify(myObjOrg));

setPosition("Auto");

imageDeFond  = '<svg id="idDessin" xmlns="http://www.w3.org/2000/svg" width="1100px" height="1100px" onload="makeDraggable(evt)">';
//imageDeFond += '<image x="0" y="0" width="1100px" height="1100px" xlink:href="/plugins/Abeille/Network/TestSVG/images/AbeilleLQI_MapData.png" ></image>';
imageDeFond += '<g id="legend"></g>';
imageDeFond += '<g id="lesVoisines"></g>';
imageDeFond += '<g id="lesTextes"></g>';
imageDeFond += '<g id="lesAbeillesText"></g>';
imageDeFond += '<g id="lesAbeilles"></g>';
imageDeFond += '</svg>';
document.getElementById("imageDeFond").innerHTML = imageDeFond;

document.getElementById("legend").innerHTML = dessineLaLegende("No");
document.getElementById("lesVoisines").innerHTML = dessineLesVoisines(0,"No");
document.getElementById("lesTextes").innerHTML = dessineLesTextes(10,"No");
document.getElementById("lesAbeillesText").innerHTML = dessineLesAbeillesText(myObjNew, 22, "No");
document.getElementById("lesAbeilles").innerHTML = dessineLesAbeilles("No");

table1  = '<table>';
table1 += '<tr><td>Source</td><td>Destination</td><td>Parametre</td><td>Relation</td></tr>';
table1 += '<tr><td>'+filtreSource()+'</td><td>'+filtreDestination()+'</td><td>'+filtreDetails()+'</td><td>'+filtreParent()+'</td></tr>';
table1 += '</table>';
document.getElementById("idFiltre").innerHTML = table1;

table2  = '<table><tr><td>';
table2 += '<button id="rucheCentered" onclick="rucheCentered()"   >Ruche Centered</button>';
table2 += '<button id="placementAuto" onclick="placementAuto()"   >Placement Auto</button>';
table2 += '<button id="save"          onclick="save()"            >local save</button>';
table2 += '<button id="restore"       onclick="restore()"         >local restore</button>';
table2 += '<button id="save"          onclick="saveAbeilles()"    >abeilles save</button>';
table2 += '</td></tr></table>';
document.getElementById("idFeatures").innerHTML = table2;


table3  = '<table><tr><td>';
table3 += '<button id="Refresh" onclick="refreshNetworkInformation()">Refresh Network Information</button>';
table3 += '</td><td>';
table3 += '<p id="refreshInformation">'+networkInformationProgress+'</p>';
table3 += '</td><td>';
table3 += '<button id="ReLoadThePage" onclick="ReLoad()">ReLoadThePage</button>';
table3 += '</td></tr></table>';
document.getElementById("idRefresh").innerHTML = table3;

table4  = '<form action="upload.php" method="post" enctype="multipart/form-data">';
table4 += '<table><tr><td>';
table4 += 'Select image to upload:<br>';
table4 += '</td><td>';
table4 += '<input type="file" name="fileToUpload" id="fileToUpload"/>';
table4 += '</td><td>';
table4 += '<input type="submit" value="Upload Image" name="submit"/>';
table4 += '</td><td>';
table4 += '<p>(L image doit etre au format png)</p>';
table4 += '</td></tr></table>';
table4 += '</form>';
document.getElementById("idUpload").innerHTML = table4;

setInterval(function() {
            refreshNetworkCollectionProgress();
            // console.log("function refreshNetworkCollectionProgress");
            }, 1000); // ms

// console.log("Name list: "+JSON.stringify(myVoisinesOrg));
// console.log("Name list: "+JSON.stringify(names));
// console.log("Name 1: " + JSON.stringify(names[0]["0000"]));

console.log("End --------------");


