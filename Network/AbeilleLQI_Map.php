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
    
    if ( isset( $_GET['NE']) )      { $NE       = $_GET['NE']; }        else { $NE      = "All"; }
    if ( isset( $_GET['NE2']) )     { $NE2      = $_GET['NE2']; }       else { $NE2     = "None"; }
    if ( isset( $_GET['Center']) )  { $Center   = $_GET['Center']; }    else { $Center  = "None"; }
    if ( isset( $_GET['Cache']) )   { $Cache    = $_GET['Cache']; }     else { $Cache   = "Cache"; }
    if ( isset( $_GET['Data']) )    { $Data     = $_GET['Data']; }      else { $Data    = "None"; }
    
    // -----------------------------------------------------------------------------------------------------------
    $DataFile = "AbeilleLQI_MapData.json";
    
    if ( $Cache == "Refresh Cache" ) {
        // Ici on n'utilise pas le cache donc on lance la collecte
        require_once("AbeilleLQI.php");
    }
    
    // Maintenant on doit avoir le chier disponible avec les infos
    if ( file_exists($DataFile) ){
        $json = json_decode(file_get_contents($DataFile), true);
        $LQI = $json['data'];
    }
    else {
        echo "Le cache n existe pas, faites un refresh.<br>";
    }
    
    // On pre-rempli table avec les NE trouvés dans les voisines
    foreach ( $LQI as $row => $voisineList ) {
        $table[$voisineList['NE']]['name']=$voisineList['NE_Name'];
        $table[$voisineList['Voisine']]['name']=$voisineList['Voisine_Name'];
    }
    
    // -----------------------------------------------------------------------------------------------------------
    require_once dirname(__FILE__).'/../../../core/php/core.inc.php';
    
    $abeilles = Abeille::byType('Abeille');
    
    // On complete table avec le NE dans Jeedom
    foreach ( $abeilles as $abeilleIndex=>$abeille ) {
        // Il faut exclure les Timers
        if ( strpos($abeille->getLogicalId(), "Timer") > 0 ) {
            // C est un Timer, je ne fais rien
        }
        else {
            $shortAddress=substr($abeille->getLogicalId(),-4);
            if ( $shortAddress=="uche" ) { $shortAddress = "0000"; }
            $table[$shortAddress]['name'] = $abeille->getName();
        }
    }
    
    // Parametre pour positionner les points
    $centerX = 500;
    $centerY = 500;
    $angle = 0;
    $angleIncrement = 360/sizeof( $table );
    $rayon = 450;
    
    // On va positionner les points sur la base des infos qu'on a dans Jeedom
    foreach ( $abeilles as $abeilleIndex=>$abeille ) {
        
        if ( strpos($abeille->getLogicalId(), "Timer") > 0 ) {
            // C est un Timer, je ne fais rien
        }
        else {
            
            if ( ($abeille->getConfiguration()["positionX"]=="") || ($abeille->getConfiguration()["positionY"]=="")) {
                $X = $centerX + $rayon * cos($angle/180*3.14);
                $Y = $centerY + $rayon * sin($angle/180*3.14);
                $angle = $angle + $angleIncrement;
            }
            else {
                $X = $abeille->getConfiguration()["positionX"];
                $Y = $abeille->getConfiguration()["positionY"];
            }
            
            $shortAddress = substr($abeille->getLogicalId(),-4);
            if ( $shortAddress=="uche" ) { $shortAddress = "0000"; }
            
            $table[$shortAddress]['x'] = $X;
            $table[$shortAddress]['y'] = $Y;
            
            if ( $abeille->getConfiguration()["battery_type"]== "" ) {
                $table[$shortAddress]['color'] = "Orange";
            }
            else {
                $table[$shortAddress]['color'] = "Green";
            }
        }
    }
    
    $liste = "";
    // On va positionner les NE qui n'ont pu etre placé avec les infos dans jeedom
    foreach( $table as $shortAddress => $point ) {
        if ( !isset($point['x']) ) {
            $liste = $liste . " " . $shortAddress."</br>\n";
            $table[$shortAddress]['x'] = $centerX + $rayon * cos($angle/180*3.14);
            $table[$shortAddress]['y'] = $centerY + $rayon * sin($angle/180*3.14);
            $table[$shortAddress]['color'] = "red";
            $angle = $angle + $angleIncrement;
        }
    }
    if ( $liste!= "" ) { echo "Liste des équipements trouvés dans le réseau mais inconnus dans Jeedom: </br>\n".$liste; }
    

    // On centre la ruche
    if ( $Center == "Center" ) {
        $table['0000']['x'] = $centerX;
        $table['0000']['y'] = $centerY;
    }
    
// -----------------------------------------------------------------------------------------------------------
    
    
?>

<h1>Abeille Network Graph</h1>

<form method="get">
    <select name="NE">
    <?php
        if ( $NE=="All" ) { $selected = " selected "; } else { $selected = " "; } echo '<option value="All"'.$selected.'>All</option>'."\n";
        if ( $NE=="None" ) { $selected = " selected "; } else { $selected = " "; } echo '<option value="None"'.$selected.'>None</option>'."\n";
        foreach ($table as $shortAddress => $point) {
            if ( $NE==$shortAddress ) { $selected = " selected "; } else { $selected = " "; }
            echo '<option value="'.$shortAddress.'"'.$selected.'>'.$shortAddress.'-'.$table[$shortAddress]['name'].'</option>'."\n";
        }
        ?>
    </select>
    <select name="NE2">
    <?php
        echo '<option value="None"'.$selected.'>None</option>'."\n";
        foreach ($table as $shortAddress => $point) {
            if ( $NE2==$shortAddress ) { $selected = " selected "; } else { $selected = " "; }
            echo '<option value="'.$shortAddress.'"'.$selected.'>'.$shortAddress.'-'.$table[$shortAddress]['name'].'</option>'."\n";
        }
        ?>
    </select>
    <select name="Center">
    <?php
        if ( $Center=="none" )   { echo '<option value="none"   selected>Not Centered</option>'  ."\n"; }   else { echo '<option value="none">Not Centered</option>'."\n"; }
        if ( $Center=="Center" ) { echo '<option value="Center" selected>Center</option>'."\n"; }           else { echo '<option value="Center">Center</option>'."\n"; }
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


Abeilles

<?php
    // Dessine des points pour chaque equipement
    foreach ( $table as $id => $point ) {
        echo '<circle cx="'.$point['x'].'" cy="'.$point['y'].'" r="10" fill="'.$point['color'].'" />'."\n";
        echo '<a xlink:href="http://jeedomzwave/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'.($point['x']+10).'" y="'.$point['y'].'" fill="red" style="font-size: 8px;">'.$point['name'].' ('.$id.')</text> </a>'."\n";
    }
    // Dessine la legende
    echo '<circle cx="100" cy="900" r="10" fill="green" />'."\n";
    echo '<a xlink:href="http://jeedomzwave/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="900" fill="red" style="font-size: 8px;">Battery</text> </a>'."\n";
    
    echo '<circle cx="100" cy="925" r="10" fill="orange" />'."\n";
    echo '<a xlink:href="http://jeedomzwave/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="925" fill="red" style="font-size: 8px;">Routeur</a>'."\n";
    
    echo '<circle cx="100" cy="950" r="10" fill="red" />'."\n";
    echo '<a xlink:href="http://jeedomzwave/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="950" fill="red" style="font-size: 8px;">Inconnu par Jeedom</text> </a>'."\n";
?>

Liaisons Radio

M pour move to
L pour Line to
<?php
    // On reset $NE qui est utilisé pour different truc.
    if ( $Cache == "Refresh Cache" ) {
        $NE="All";
    }
    
    $error = "";
    
    // $voisinesMap
    foreach ( $LQI as $row => $voisineList ) {
        
        if ( ($NE==$voisineList['NE']) || ($NE=="All") || ($NE2==$voisineList['Voisine']) ) {
            if ( isset($table[$voisineList['NE']]) && isset($table[$voisineList['Voisine']]) ) {
                $midX = ( $table[$voisineList['NE']]['x'] + $table[$voisineList['Voisine']]['x'] ) / 2;
                $midY = ( $table[$voisineList['NE']]['y'] + $table[$voisineList['Voisine']]['y'] ) / 2;
                
                if ( $Data=="LinkQualityDec" ) {
                    if ( $voisineList[$Data]<70 )                                   { $colorLine = "#FF0000"; }
                    if ( ($voisineList[$Data]>=70) && ($voisineList[$Data]<150) )   { $colorLine = "#FF8C00"; }
                    if ( $voisineList[$Data]>=150 )                                 { $colorLine = "#008000"; }
                    echo "1";
                }
                else {
                    $colorLine = "#00BFFF";
                    echo "0";
                }
                
                echo '<path d="M'.$table[$voisineList['NE']]['x'].','.$table[$voisineList['NE']]['y'].' L'.$table[$voisineList['Voisine']]['x'].','.$table[$voisineList['Voisine']]['y'].'" style="stroke: '.$colorLine.'; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
                echo '<text x="'.$midX.'" y="'.$midY.'" fill="purple" style="font-size: 8px;">'.$voisineList[$Data].'</text>'."\n";
            }
        }
    }

?>

Sorry, your browser does not support inline SVG.
</svg>
<?php
    echo "</br>";
?>
</body>
</html>

