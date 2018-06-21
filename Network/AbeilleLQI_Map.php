<!DOCTYPE html>

<html>
<head>
<style type="text/css">
body {
width: 100%;
height: 100%;
zoom:200%;
}
</style>
</head>
<body>

<?php
    
    /*
     (
     [0] => Array
     (
     [NeighbourTableEntries] => 0A
     [Index] => 00
     [ExtendedPanId] => 28d07615bb019209
     [IEEE_Address] => 00158d00019f9199
     [Depth] => 01
     [LinkQuality] => 75
     [BitmapOfAttributes] => 1a
     [NE] => 0000
     [NE_Name] => Ruche
     [Voisine] => df33
     [Voisine_Name] => Test: Temperature Rond Bureau
     [Type] => End Device
     [Relationship] => Child
     [Rx] => Rx-Off
     [LinkQualityDec] => 117
     )
     */
    
    if ( isset( $_GET['NE']) ) {
        $NE = $_GET['NE'];
    }
    else {
        $NE = "All";
    }
    
    if ( isset( $_GET['NE2']) ) {
        $NE2 = $_GET['NE2'];
    }
    else {
        $NE2 = "None";
    }
    
    if ( isset( $_GET['Cache']) ) {
        $Cache = $_GET['Cache'];
    }
    else {
        $Cache = "Cache";
    }
    
    if ( isset( $_GET['Data']) ) {
        $Data = $_GET['Data'];
    }
    else {
        $Data = "None";
    }
    
    require_once("NetworkDefinition.php");
    
    $DataFile = "AbeilleLQI_MapData.json";
    
    if ( $Cache == "Refresh Cache" ) {
        // Ici on n'utilise pas le cache donc on lance la collecte
        require_once("AbeilleLQI.php");
    }
    
    if ( file_exists($DataFile) ){
        
        $json = json_decode(file_get_contents($DataFile), true);
        // $LQI = $json->data;
        $LQI = $json['data'];
        // print_r( $LQI );
        // exit;
    }
    else {
        echo "Le cache n existe pas, faites un refresh.<br>";
    }
    

    
    
    ?>

<h1>Abeille Network Graph</h1>

<form method="get">
<select name="NE">
<?php
    if ( $NE=="All" ) { $selected = " selected "; } else { $selected = " "; } echo '<option value="All"'.$selected.'>All</option>'."\n";
    if ( $NE=="None" ) { $selected = " selected "; } else { $selected = " "; } echo '<option value="None"'.$selected.'>None</option>'."\n";
    foreach ($knownNE as $shortAddress => $name) {
        if ( $NE==$name ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$name.'"'.$selected.'>'.$name.'-'.$shortAddress.'</option>'."\n";
    }
    ?>
</select>
<select name="NE2">
<?php
    echo '<option value="None"'.$selected.'>None</option>'."\n";
    foreach ($knownNE as $shortAddress => $name) {
        if ( $NE2==$name ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$name.'"'.$selected.'>'.$name.'-'.$shortAddress.'</option>'."\n";
    }
    ?>
</select>
<select name="Cache">
<?php
    $CacheList = array( 'Cache', 'Refresh Cache' );
    foreach ($CacheList as $item) {
        if ( $Cache==$item ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$item.'"'.$selected.'>'.$item.'</option>'."\n";
    }
    ?>
</select>
<select name="Data">
<?php
    $DataList = array( 'Depth', 'LinkQualityDec', 'Voisine', 'IEEE_Address', 'Type', 'Relationship', 'Rx'  );
    foreach ($DataList as $item) {
        if ( $Data==$item ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$item.'"'.$selected.'>'.$item.'</option>'."\n";
    }
    ?>
</select>

<input type="submit" value="Submit">
</form>

<svg width="1100px" height="1100px">
<defs>
<marker id="markerCircle" markerWidth="8" markerHeight="8" refX="5" refY="5">
<circle cx="5" cy="5" r="3" style="stroke: none; fill:#000000;"/>
</marker>

<marker id="markerArrow" markerWidth="30" markerHeight="30" refX="30" refY="6" orient="auto">
On dessine un triangle plein
<path d="M2,2 L2,11 L20,6 L2,2" style="fill: #000000;" />
</marker>
</defs>




L ordre d affichage est important

Maison


<path d="M625,270 L800,270, L800,800, L625,800, L625,600 L200,600, L200,500 L625,500 L625,270" style="stroke: #66ffff; stroke-width: 1px; fill: none; " />


Abeilles

<?php
    // Dessine des points pour chaque equipement
    foreach ( $Abeilles as $AbeilleId => $Abeille ) {
        echo $AbeilleId . "\n";
        echo '<circle cx="'.$Abeille['position']['x'].'" cy="'.$Abeille['position']['y'].'" r="10" fill="'.$Abeille['color'].'" />'."\n";
        echo '<a xlink:href="http://jeedomzwave/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'.($Abeille['position']['x']+10).'" y="'.$Abeille['position']['y'].'" fill="red" style="font-size: 8px;">'.$AbeilleId .'</text> </a>'."\n";
    }
    ?>

Liaisons Radio

M pour move to
L pour Line to
<?php
    
    
    //    foreach ( $liaisonsRadio as $liaisonRadioId => $liaisonRadio ) {
    //        echo '<path d="M'.$Abeilles[$liaisonRadio['source']]['position']['x'].','.$Abeilles[$liaisonRadio['source']]['position']['y'].' L'.$Abeilles[$liaisonRadio['destination']]['position']['x'].','.$Abeilles[$liaisonRadio['destination']]['position']['y'].'" style="stroke: #6666ff; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
    //    }
    
    
    // On reset $NE qui est utilisé pour different truc.
    if ( $Cache == "Refresh Cache" ) {
        $NE="All";
    }
    
    // $voisinesMap
    foreach ( $LQI as $row => $voisineList ) {
        $sourceId = $voisineList['NE'];
        $targetId= $voisineList['Voisine'];
        if ( isset($knownNE[$sourceId]) ) { $sourceIdName = $knownNE[$sourceId]; } else { $sourceIdName = "SourceInconnue"; }
        if ( isset($knownNE[$targetId]) ) { $targetIdName = $knownNE[$targetId]; } else { $targetIdName = "TargetInconnue"; }
        echo $sourceIdName . ' - ' . $targetIdName; echo "\n";
        if ($sourceIdName!=$targetIdName){
            if ( ($sourceIdName==$NE) || ("All"==$NE) || ($targetIdName==$NE2) ) {
                echo '<path d="M'.$Abeilles[$sourceIdName]['position']['x'].','.$Abeilles[$sourceIdName]['position']['y'].' L'.$Abeilles[$targetIdName]['position']['x'].','.$Abeilles[$targetIdName]['position']['y'].'" style="stroke: #6666ff; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
                $midX = ( $Abeilles[$sourceIdName]['position']['x'] + $Abeilles[$targetIdName]['position']['x'] ) / 2;
                $midY = ( $Abeilles[$sourceIdName]['position']['y'] + $Abeilles[$targetIdName]['position']['y'] ) / 2;
                echo '<text x="'.$midX.'" y="'.$midY.'" fill="red" style="font-size: 8px;">'.$voisineList[$Data].'</text>'."\n";
                
                print_r( $InOut );
                print_r( $link );
            }
        }
        
        
    }
    if (0) {
    // encode array to json
    $json = json_encode(array('data' => $LQI));
    
    //write json to file
    if ( file_put_contents( $DataFile, $json) )
    echo "JSON file created successfully...";
    else
    echo "Oops! Error creating json file...";
    }
    
    
    
    
    ?>





Sorry, your browser does not support inline SVG.
</svg>

</body>
</html>

