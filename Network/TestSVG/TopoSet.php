<?php
    
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
    
    $AbeilleTopoJSON = $_GET["TopoJSON"];
    
    // echo $AbeilleTopoJSON;
    
    // $AbeilleTopoJSON = '{"0000":{"name":"Ruche","x":333,"y":555,"color":"Red","positionDefined":"Yes","Type":"Coordinator"},"63d6":{"name":"IR 1","x":900,"y":500,"color":"Green","positionDefined":"Yes","Type":"Inconnu"},"7a8a":{"name":"IR 2","x":782.842712474619,"y":782.842712474619,"color":"Green","positionDefined":"Yes","Type":"Inconnu"},"030B":{"name":"IR 3","x":500,"y":900,"color":"Green","positionDefined":"Yes","Type":"Inconnu"},"0C9a":{"name":"Smoke","x":217.15728752538104,"y":782.842712474619,"color":"Green","positionDefined":"Yes","Type":"Inconnu"},"0A73":{"name":"Tp Carré","x":650,"y":370,"color":"Green","positionDefined":"Yes","Type":"Inconnu"},"7b0D":{"name":"Tp Rond","x":100,"y":500.00000000000006,"color":"Green","positionDefined":"Yes","Type":"Inconnu"},"3fcd":{"name":"Vibration","x":217.15728752538092,"y":217.15728752538104,"color":"Green","positionDefined":"Yes","Type":"Inconnu"}}';
    
    $AbeilleTopo = json_decode($AbeilleTopoJSON, false, 512, JSON_UNESCAPED_UNICODE);

    // var_dump( $AbeilleTopo );
    
    foreach ( $AbeilleTopo as $key=>$abeille ) {
        // echo "Key: " . $key . "->";
        if ( $key == "0000" ) {
            $id = "Abeille/Ruche";
        }
        else {
            $id = "Abeille/".$key;
        }
        // echo $id."->";
        
        // echo "\n";
        
        if (is_object(eqLogic::byLogicalId( $id, 'Abeille'))) {
            $eqLogic = eqLogic::byLogicalId($id, 'Abeille');
            // echo "Abeille->".$eqLogic->getConfiguration('positionX')."-".$eqLogic->getConfiguration('positionY')."\n";
            // echo "Graph->".$abeille->x.'-'.$abeille->y."\n";
            $eqLogic->setConfiguration('positionX',$abeille->x);
            $eqLogic->setConfiguration('positionY',$abeille->y);
            $eqLogic->save();
            
        }
        else {
            echo "Objet inconnu\n";
        }
     
       
    }
    
     echo "Done";
    
    ?>

