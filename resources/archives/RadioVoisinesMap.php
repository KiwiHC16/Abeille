<!DOCTYPE html>

<html>
<body>

<?php
    
    include 'RadioVoisines.php';
    



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

if ( isset( $_GET['InOut']) ) {
    $InOut = $_GET['InOut'];
}
else {
    $InOut = "In";
}
    ?>

<h1>Abeille Network</h1>

<form method="get">
<select name="NE">
<?php
    echo '<option value="All"'.$selected.'>All</option>'."\n";
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
<select name="InOut">
<?php
    $InOutList = array( 'In', 'Out' );
    foreach ($InOutList as $item) {
        if ( $InOut==$item ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$item.'"'.$selected.'>'.$item.'</option>'."\n";
    }
    ?>
</select>
<input type="submit" value="Submit">
</form>

<svg width="1000" height="1000">
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
    
    
    // $voisinesMap
    
    foreach ( $voisinesMap as $sourceId => $voisine ) {
        
        foreach ( $voisine as $targetId => $link ) {
            $sourceIdName = $knownNE[$sourceId];
            $targetIdName = $knownNE[$targetId];
            echo $sourceIdName . ' - ' . $targetIdName; echo "\n";
            if ($sourceIdName!=$targetIdName){
                if ( ($sourceIdName==$NE) || ("All"==$NE) || ($sourceIdName==$NE2) ) {
                    echo '<path d="M'.$Abeilles[$sourceIdName]['position']['x'].','.$Abeilles[$sourceIdName]['position']['y'].' L'.$Abeilles[$targetIdName]['position']['x'].','.$Abeilles[$targetIdName]['position']['y'].'" style="stroke: #6666ff; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
                    $midX = ( $Abeilles[$sourceIdName]['position']['x'] + $Abeilles[$targetIdName]['position']['x'] ) / 2;
                    $midY = ( $Abeilles[$sourceIdName]['position']['y'] + $Abeilles[$targetIdName]['position']['y'] ) / 2;
                    echo '<text x="'.$midX.'" y="'.$midY.'" fill="red" style="font-size: 8px;">'.$link[$InOut].'</text>'."\n";
                    
                    print_r( $InOut );
                    print_r( $link );
                }
            }
        }
    }
    ?>





Sorry, your browser does not support inline SVG.
</svg>

</body>
</html>

