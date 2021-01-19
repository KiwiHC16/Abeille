<?php
    /* Developers debug features */
    $dbgFile = __DIR__."/../tmp/debug.json";
    if (file_exists($dbgFile)) {
        // include_once $dbgFile;
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    include_once __DIR__."/../../../core/php/core.inc.php";

    echo '<!DOCTYPE html>';

    echo '<html>';
    echo '<head>';
    echo '<style type="text/css">';
    echo 'body {';
    echo 'width: 100%;';
    echo 'height: 100%;';
    echo 'zoom:200%;';
    echo '}';
    echo '</style>';
    echo '</head>';
    echo '<body>';

    if ( isset( $_GET['zigateId']) )    { $zigateId     = $_GET['zigateId']; }        else { $zigateId  = "1"; }
    if ( isset( $_GET['GraphType']) )   { $GraphType    = $_GET['GraphType']; }       else { $NE        = "Default"; }
    if ( isset( $_GET['NE']) )          { $NE           = $_GET['NE']; }              else { $NE        = "All"; }
    if ( isset( $_GET['NE2']) )         { $NE2          = $_GET['NE2']; }             else { $NE2       = "None"; }
    if ( isset( $_GET['Center']) )      { $Center       = $_GET['Center']; }          else { $Center    = "None"; }
    if ( isset( $_GET['Cache']) )       { $Cache        = $_GET['Cache']; }           else { $Cache     = "Cache"; }
    if ( isset( $_GET['Data']) )        { $Data         = $_GET['Data']; }            else { $Data      = "None"; }
    if ( isset( $_GET['Hierarchy']) )   { $Hierarchy    = $_GET['Hierarchy']; }       else { $Hierarchy = "All"; }

    // $Data = "Relationship";
    // $Hierarchy = "All";
    // $Hierarchy = "Child";

    /*
    echo "\n\n";
    echo $Data;
    echo "\n";
    echo $Hierarchy;
    echo "\n\n";
    */

    // -----------------------------------------------------------------------------------------------------------
    $tmpDir = jeedom::getTmpFolder("Abeille");
    $DataFile = $tmpDir."/AbeilleLQI_MapDataAbeille".$zigateId.".json";

    if ( $Cache == "Refresh Cache" ) {
        // Ici on n'utilise pas le cache donc on lance la collecte
        include_once("AbeilleLQI.php");
    }

    // Maintenant on doit avoir le chier disponible avec les infos
    if ( file_exists($DataFile) ){
        $json = json_decode(file_get_contents($DataFile), true);
        $LQI = $json['data'];
    } else {
        echo 'Le fichier contenant les information réseau n existe pas, faites un "Refresh Cache" puis "Submit".<br>';
    }

    // On pre-rempli table avec les NE trouvés dans les voisines
    foreach ( $LQI as $row => $voisineList ) {
        $table[$voisineList['NE']]['name']=$voisineList['NE_Name'];
        $table[$voisineList['Voisine']]['name']=$voisineList['Voisine_Name'];
    }

    // -----------------------------------------------------------------------------------------------------------
    require_once __DIR__.'/../../../core/php/core.inc.php';

    $abeilles = Abeille::byType('Abeille');

    // On complete table avec le NE dans Jeedom
    foreach ( $abeilles as $abeilleIndex=>$abeille ) {
        // Il faut exclure les Timers
        if ( strpos($abeille->getLogicalId(), "Timer") > 0 ) {
            // C est un Timer, je ne fais rien
        } else {
            $shortAddress=substr($abeille->getLogicalId(),-4);
            if ( $shortAddress=="Ruche" ) { $shortAddress = "0000"; }
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
        } else {

            if ( ($abeille->getConfiguration()["positionX"] == "") || ($abeille->getConfiguration()["positionY"] == "") ) {

                $X = $centerX + $rayon * cos($angle/180*3.14);
                $Y = $centerY + $rayon * sin($angle/180*3.14);
                $angle = $angle + $angleIncrement;

                $Z = 0;
            } else {
                $X = $abeille->getConfiguration()["positionX"];
                $Y = $abeille->getConfiguration()["positionY"];
                if ($abeille->getConfiguration()["positionZ"] == "") {
                    $Z = "0";
                } else {
                    $Z = $abeille->getConfiguration()["positionZ"];
                }
            }

            $shortAddress = substr($abeille->getLogicalId(),-4);
            if ( $shortAddress=="Ruche" ) { $shortAddress = "0000"; }

            $table[$shortAddress]['x'] = $X;
            $table[$shortAddress]['y'] = $Y;
            $table[$shortAddress]['z'] = $Z;

            if ( $abeille->getConfiguration()["battery_type"]== "" ) {
                $table[$shortAddress]['color'] = "Orange";
            } else {
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
            $table[$shortAddress]['z'] = 0; // Tcharp38: correct ?
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

    echo "<h1>Abeille Network Graph</h1>";

    echo "Attention: les équipements sans position seront positionnés sur le cercle par defaut donc les distances ne seront pas représentative des distances réelles.";

    echo '<form method="get">';

    echo '<select name="GraphType">';
    if ( $GraphType=="Default" )    { $selected = " selected "; } else { $selected = " "; } echo '<option value="Default"'      .$selected.'>Default</option>'."\n";
    if ( $GraphType=="LqiPerMeter" ){ $selected = " selected "; } else { $selected = " "; } echo '<option value="LqiPerMeter"'  .$selected.'>LqiPerMeter</option>'."\n";
    echo '</select>';

    echo '<select name="NE">';
    if ( $NE=="All" ) { $selected = " selected "; } else { $selected = " "; } echo '<option value="All"'.$selected.'>All</option>'."\n";
    if ( $NE=="None" ) { $selected = " selected "; } else { $selected = " "; } echo '<option value="None"'.$selected.'>None</option>'."\n";
    foreach ($table as $shortAddress => $point) {
        if ( $NE==$shortAddress ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$shortAddress.'"'.$selected.'>'.$shortAddress.'-'.$table[$shortAddress]['name'].'</option>'."\n";
    }
    echo '</select>';

    echo '<select name="NE2">';

    echo '<option value="None"'.$selected.'>None</option>'."\n";
    foreach ($table as $shortAddress => $point) {
        if ( $NE2==$shortAddress ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$shortAddress.'"'.$selected.'>'.$shortAddress.'-'.$table[$shortAddress]['name'].'</option>'."\n";
    }

    echo '</select>';
    echo '<select name="Center">';

    if ( $Center=="none" )   { echo '<option value="none"   selected>Not Centered</option>'  ."\n"; }   else { echo '<option value="none">Not Centered</option>'."\n"; }
    if ( $Center=="Center" ) { echo '<option value="Center" selected>Center</option>'."\n"; }           else { echo '<option value="Center">Center</option>'."\n"; }

    echo '</select>';
    echo '<select name="Cache">';

    $CacheList = array( 'Cache', 'Refresh Cache' );
    foreach ($CacheList as $item) {
        if ( $Cache==$item ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$item.'"'.$selected.'>'.$item.'</option>'."\n";
    }

    echo '</select>';
    echo '<select name="Data">';

    $DataList = array( 'Depth', 'LinkQualityDec', 'Voisine', 'IEEE_Address', 'Type', 'Relationship', 'Rx'  );
    foreach ($DataList as $item) {
        if ( $Data==$item ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$item.'"'.$selected.'>'.$item.'</option>'."\n";
    }

    echo "</select>";
    echo '<select name="Hierarchy">';

    $DataList = array( 'Sibling', 'Child', 'All' );
    foreach ($DataList as $item) {
        if ( $Hierarchy==$item ) { $selected = " selected "; } else { $selected = " "; }
        echo '<option value="'.$item.'"'.$selected.'>'.$item.'</option>'."\n";
    }

    echo "</select>";

    echo '<input type="submit" value="Submit">';
    echo '</form>';

    echo '<svg width="1100px" height="1100px">';
    echo '<defs>';

    echo '<marker id="markerCircle" markerWidth="8" markerHeight="8" refX="5" refY="5">';
    echo '<circle cx="5" cy="5" r="3" style="stroke: none; fill:#000000;"/>';
    echo '</marker>';

    echo '<marker id="markerArrow" markerWidth="30" markerHeight="30" refX="30" refY="6" orient="auto">';
    echo 'On dessine un triangle plein';
    echo '<path d="M2,2 L2,11 L20,6 L2,2" style="fill: #000000;" />';
    echo '</marker>';
    echo '</defs>';

    // Abeilles

    if ( $GraphType == 'Default' ) {
        // Dessine des points pour chaque equipement
        foreach ( $table as $id => $point ) {
            echo '<circle cx="'.$point['x'].'" cy="'.$point['y'].'" r="10" fill="'.$point['color'].'" />'."\n";
            echo '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'.($point['x']+10).'" y="'.$point['y'].'" fill="red" style="font-size: 8px;">'.$point['name'].' ('.$id.')</text> </a>'."\n";
        }

        // Dessine la legende
        echo '<circle cx="100" cy="900" r="10" fill="green" />'."\n";
        echo '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="900" fill="red" style="font-size: 8px;">Battery</text> </a>'."\n";

        echo '<circle cx="100" cy="925" r="10" fill="orange" />'."\n";
        echo '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="925" fill="red" style="font-size: 8px;">Routeur</a>'."\n";

        echo '<circle cx="100" cy="950" r="10" fill="red" />'."\n";
        echo '<a xlink:href="/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="110" y="950" fill="red" style="font-size: 8px;">Inconnu par Jeedom</text> </a>'."\n";

        echo "\n\n";
        // echo "Liaisons Radio";

        // echo "M pour move to";
        // echo "L pour Line to";

        // On reset $NE qui est utilisé pour different truc.
        if ( $Cache == "Refresh Cache" ) {
            $NE="All";
        }

        $error = "Message collector<br>";
        $i=0;

        // $voisinesMap
        foreach ( $LQI as $row => $voisineList ) {
            $error = $error . "\n" .$i++ . ": ->". $voisineList['Relationship'] . "<- ";

            if ( ($Data!="Relationship") || ($Hierarchy=="All") || (($Data=="Relationship") && ($Hierarchy==$voisineList['Relationship'])) )
            /*
             if (        ($Data!="Relationship")
             ||      ($Hierarchy=="All")
             ||  (   ($Data=="Relationship") && ($Hierarchy==$voisineList['Relationship']) )
             )
             */
            {
                if ( ($NE==$voisineList['NE']) || ($NE=="All") || ($NE2==$voisineList['Voisine']) ) {
                    if ( isset($table[$voisineList['NE']]) && isset($table[$voisineList['Voisine']]) ) {
                        $midX = ( $table[$voisineList['NE']]['x'] + $table[$voisineList['Voisine']]['x'] ) / 2;
                        $midY = ( $table[$voisineList['NE']]['y'] + $table[$voisineList['Voisine']]['y'] ) / 2;

                        if ( $Data=="LinkQualityDec" ) {
                            if ( $voisineList[$Data]<70 )                                   { $colorLine = "#FF0000"; }
                            if ( ($voisineList[$Data]>=70) && ($voisineList[$Data]<150) )   { $colorLine = "#FF8C00"; }
                            if ( $voisineList[$Data]>=150 )                                 { $colorLine = "#008000"; }
                            echo "1";
                        } else {
                            $colorLine = "#00BFFF";
                        }

                        echo '<path d="M'.$table[$voisineList['NE']]['x'].','.$table[$voisineList['NE']]['y'].' L'.$table[$voisineList['Voisine']]['x'].','.$table[$voisineList['Voisine']]['y'].'" style="stroke: '.$colorLine.'; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
                        echo '<text x="'.$midX.'" y="'.$midY.'" fill="purple" style="font-size: 8px;">'.$voisineList[$Data].'</text>'."\n";
                    }
                }
            } else {
                $error = $error . " - " . $Data . "/" . $Hierarchy . "<br>";
            }
        }
    }

    if ( $GraphType == 'LqiPerMeter' ) {
        $error = "Message collector<br><table><tr><td>X1</td><td>Y1</td><td>X2</td><td>Y2</td><td>Distance</td><td>Valeur</td></tr>";
        $i=0;

        echo '<path d="M10,1000 L10,0" style="stroke: black; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
        echo '<text x="20" y="10" fill="Black" style="font-size: 12px;">LQI (0-250)</text>';
        echo '<path d="M10,1000 L1000,1000" style="stroke: black; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
        echo '<text x="950" y="980" fill="Black" style="font-size: 12px;">Distance</text>';

        echo '<path d="M10,333 L1000,333" style="stroke: black; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
        echo '<path d="M10,666 L1000,666" style="stroke: black; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";

        // $voisinesMap
        foreach ( $LQI as $row => $voisineList ) {
            // $error = $error . "\n" .$i++ . ": ->". $voisineList['Relationship'] . "<- ";

            if ( ($Data!="Relationship") || ($Hierarchy=="All") || (($Data=="Relationship") && ($Hierarchy==$voisineList['Relationship'])) )
            /*
             if (        ($Data!="Relationship")
             ||      ($Hierarchy=="All")
             ||  (   ($Data=="Relationship") && ($Hierarchy==$voisineList['Relationship']) )
             )
             */
            {
                if ( ($NE==$voisineList['NE']) || ($NE=="All") || ($NE2==$voisineList['Voisine']) ) {
                    if ( isset($table[$voisineList['NE']]) && isset($table[$voisineList['Voisine']]) ) {
                        $midX = ( $table[$voisineList['NE']]['x'] + $table[$voisineList['Voisine']]['x'] ) / 2;
                        $midY = ( $table[$voisineList['NE']]['y'] + $table[$voisineList['Voisine']]['y'] ) / 2;
                        $dx = $table[$voisineList['Voisine']]['x'] - $table[$voisineList['NE']]['x'];
                        $dy = $table[$voisineList['Voisine']]['y'] - $table[$voisineList['NE']]['y'];
                        $dz = $table[$voisineList['Voisine']]['z'] - $table[$voisineList['NE']]['z'];

                        $distance = sqrt( pow($dx,2) + pow($dy,2) + pow($dz,2) );
                        // echo $distance.'<br>';
                        $error = $error . "<tr><td>".round($table[$voisineList['NE']]['x'])."</td><td>".round($table[$voisineList['NE']]['y'])."</td><td>".round($table[$voisineList['Voisine']]['x'])."</td><td>".round($table[$voisineList['Voisine']]['y'])."</td><td>".round($distance)."</td><td>".$voisineList[$Data]."</td></tr>";

                        if ( $Data=="LinkQualityDec" ) {
                            if ( $voisineList[$Data]<70 )                                   { $colorLine = "#FF0000"; }
                            if ( ($voisineList[$Data]>=70) && ($voisineList[$Data]<150) )   { $colorLine = "#FF8C00"; }
                            if ( $voisineList[$Data]>=150 )                                 { $colorLine = "#008000"; }
                            // echo "1";
                        } else {
                            $colorLine = "#00BFFF";
                        }

                        // echo '<path d="M'.$table[$voisineList['NE']]['x'].','.$table[$voisineList['NE']]['y'].' L'.$table[$voisineList['Voisine']]['x'].','.$table[$voisineList['Voisine']]['y'].'" style="stroke: '.$colorLine.'; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
                        // echo '<text x="'.$midX.'" y="'.$midY.'" fill="purple" style="font-size: 8px;">'.$voisineList[$Data].'</text>'."\n";
                        echo '<circle cx="'.($distance+10).'" cy="'.(1000-($voisineList[$Data]*3.9)).'" r="10" fill="'.$colorLine.'" />'."\n";
                        // echo '<circle cx="500" cy="100" r="10" fill="red" />'."\n";
                    }
                }
            } else {
                $error = $error . " - " . $Data . "/" . $Hierarchy . "<br>";
            }
        }
    }
    $error = $error . "</table>";

    // echo "<br>Sorry, your browser does not support inline SVG.<br>";
    echo "</svg>\n";

    // echo "<br>" . $error . "</br>\n\n";

    echo "</body>\n";
    echo "</html>\n";
?>
