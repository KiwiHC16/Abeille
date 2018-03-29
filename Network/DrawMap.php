<!DOCTYPE html>
<?php

include 'NetworkDefinition.php';

?>
<html>
	<body>

		<h1>Abeille Network</h1>

		<svg width="1000" height="1000">
            <defs>
                <marker id="markerCircle" markerWidth="8" markerHeight="8" refX="5" refY="5">
                    <circle cx="5" cy="5" r="3" style="stroke: none; fill:#000000;"/>
                </marker>
                
                <marker id="markerArrow" markerWidth="30" markerHeight="30" refX="30" refY="6" orient="auto">
                    On decine un triangle plein
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
        echo '<circle cx="'.$Abeille['position']['x'].'" cy="'.$Abeille['position']['y'].'" r="10" fill="green" />'."\n";
        echo '<a xlink:href="http://jeedomzwave/index.php?v=d&m=Abeille&p=Abeille" target="_blank"> <text x="'.($Abeille['position']['x']+10).'" y="'.$Abeille['position']['y'].'" fill="red">'.$AbeilleId .'</text> </a>'."\n";
        }
?>

Liaisons Radio

M pour move to
L pour Line to
<?php
    
    foreach ( $liaisonsRadio as $liaisonRadioId => $liaisonRadio ) {
        echo '<path d="M'.$Abeilles[$liaisonRadio['source']]['position']['x'].','.$Abeilles[$liaisonRadio['source']]['position']['y'].' L'.$Abeilles[$liaisonRadio['destination']]['position']['x'].','.$Abeilles[$liaisonRadio['destination']]['position']['y'].'" style="stroke: #6666ff; stroke-width: 1px; fill: none; marker-start: url(#markerCircle); marker-end: url(#markerArrow);" />'."\n";
    }
    ?>

               

               

               
               
               
               
               

               
               

               
              
               
               
               
			      Sorry, your browser does not support inline SVG.
		</svg> 
		 
	</body>
</html>

