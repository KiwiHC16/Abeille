<?php
    $debug = 0;
    
    if ( strlen($argv[1]) == 0 ) {
        echo "usage: php setAffichage.php file.json\n";
        echo "set one info in the configuration of each command:\n";
        echo ' - "visibilityCategory":"Network"'."\n";
        echo ' - "visibilityCategory":"toogleAffichageTime"'."\n";
        echo ' - "visibilityCategory":"toogleAffichageAdditionalCommand"'."\n";
        
        echo 'Pour faire tous les fichier: find . -name "[!.]*.json" -print -exec bash -c "eval php setAffichage.php {} > {}.new" \;'."\n";
        echo 'Pour remplacer les anciens par les nouveaux:find . -name "[!.]*.json" -print -exec mv {}.new {} \;'."\n";
        exit;
    }

    // $NE="LWB010";
    // $NE="LWB006";
    $file=$argv[1];
    
    if ( $debug ) echo "File to process: ".$file."\n";
    

$commands = array(
                  "0000-0000"=>"All",
                  "0000-0001"=>"Network", // SW
                  "0000-0004"=>"Network",
                  "0000-0005"=>"Network",
                  "0000-0006"=>"All",
                  "0000-0010"=>"All",
                  "0000-03-0001"=>"All",
                  "0000-03-0004"=>"All",
                  "0000-03-0005"=>"All",
                  "0000-03-4000"=>"All",
                  "0000-0B-0004"=>"Network",
                  "0000-0B-0005"=>"Network",
                  "0000-0B-4000"=>"Network",
                  "0000-4000"=>"Network",
                  "0000-ff01"=>"additionalCommand",
                  "0001-0021"=>"All",
                  "0006-0000"=>"All",
                  "0006-02-0000"=>"All",
                  "0006-03-0000"=>"All",
                  "0006-04-0000"=>"All",
                  "0006-05-0000"=>"All",
                  "0006-0B-0000"=>"All",
                  "0006-8000"=>"All",
                  "0008-0000"=>"All",
                  "0008-03-0000"=>"All",
                  "0008-0B-0000"=>"All",
                  "000C-03-0055"=>"All",
                  "000C-03-ff05"=>"All",
                  "000c-ff05"=>"All",
                  "0012-0055"=>"All",
                  "0012-02-0055"=>"All",
                  "0101-0055"=>"All",
                  "0101-0503"=>"All",
                  "0300-0003"=>"All",
                  "0300-0004"=>"All",
                  "0400-0000"=>"All",
                  "0402-0000"=>"All",
                  "0403-0000"=>"All",
                  "0403-0010"=>"All",
                  "0403-0014"=>"All",
                  "0405-0000"=>"All",
                  "0406-0000"=>"All",
                  "0500-0000"=>"All",
                  "2200K"=>"All",
                  "2700K"=>"All",
                  "4000K"=>"All",
                  "Batterie-Pourcent"=>"Network",
                  "Batterie-Volt"=>"Network",
                  "BindShortToZigateEtat"=>"Network",
                  "BindShortToZigateLevel"=>"Network",
                  "BindToZigateEtat"=>"Network",
                  "BindToZigateLevel"=>"Network",
                  "Blanc"=>"All",
                  "Bleu"=>"All",
                  "Cancel"=>"All",
                  "Colour"=>"All",
                  "DownGroup"=>"All",
                  "getBattery"=>"All",
                  "getColorX"=>"additionalCommand",
                  "getColorY"=>"additionalCommand",
                  "getEtat"=>"additionalCommand",
                  "getLevel"=>"additionalCommand",
                  "getManufacturerName"=>"additionalCommand",
                  "getModelIdentifier"=>"additionalCommand",
                  "getSWBuild"=>"additionalCommand",
                  "Group-Membership"=>"Network",
                  "High"=>"Network",
                  "Identify"=>"additionalCommand",
                  "IEEE-Addr"=>"Network",
                  "Level Stop"=>"All",
                  "Level"=>"All",
                  "Link-Quality"=>"Network",
                  "Low"=>"Network",
                  "Medium"=>"Network",
                  "Off"=>"All",
                  "Off1"=>"All",
                  "Off2"=>"All",
                  "On"=>"All",
                  "On1"=>"All",
                  "On2"=>"All",
                  "online"=>"Network",
                  "Power-Source"=>"Network",
                  "Rouge"=>"All",
                  "Sc1"=>"All",
                  "Sc2"=>"All",
                  "Sc3"=>"All",
                  "Sensivity High"=>"Network",
                  "Sensivity Low"=>"Network",
                  "Sensivity Medium"=>"Network",
                  "setReportEtat"=>"Network",
                  "setReportLevel"=>"Network",
                  "Short-Addr"=>"Network",
                  "Start"=>"All",
                  "Stop"=>"All",
                  "tbd---conso--"=>"All",
                  "tbd---puissance--"=>"All",
                  "tbd---puissance-"=>"All",
                  "Temperature"=>"All",
                  "Test"=>"Network",
                  "Time-Time"=>"Time",
                  "Time-TimeStamp"=>"Time",
                  "Toggle"=>"All",
                  "Toggle1"=>"All",
                  "Toggle2"=>"All",
                  "UpGroup"=>"All",
                  "Var-Duration"=>"All",
                  "Var-ExpiryTime"=>"All",
                  "Var-RampUpDown"=>"All",
                  "Vert"=>"All",
    );
    
    
    // $string = file_get_contents("./".$NE."/".$NE.".json");
    $string = file_get_contents($file);
    $json = json_decode($string, true);
    
    $fileExploded = explode("/",$file);
    // var_dump( $fileExploded );
    $NE = $fileExploded[1];
    // var_dump( $json[$NE]["Commandes"] );
    
    foreach ( $json as $id=>$equipment ) {
        if ( $debug ) var_dump( $id );
        foreach ( $equipment["Commandes"] as $key=>$command ) {
            // var_dump($command["configuration"]);
            // var_dump($command["configuration"]["visibilityCategory"]);
            if ( isset( $command["configuration"] ) ) {
                if ( isset( $command["configuration"]["visibilityCategory"] ) ) {
                    if ( $debug ) echo $key.'->'.$command["configuration"]["visibilityCategory"]."\n";
                }
                else {
                    if ( $debug ) echo $key . "\n";
                    $json[$id]["Commandes"][$key]["configuration"]["visibilityCategory"] = $commands[$key];
                }
            }
            else {
                if ( $debug ) echo $key . "\n";
                $json[$id]["Commandes"][$key]["configuration"] = array( "visibilityCategory" => $commands[$key] );
                // $json[$NE]["Commandes"][$key]["configuration"]["visibilityCategory"] = $commands[$key];
            }
        }
    }
    
     
    if ( $debug==0 )  echo json_encode($json);
?>
