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
                  "0000-0000"=>"Network", // ZCL Version
                  "0000-0001"=>"Network", // SW / Appli Version
                  "0000-0002"=>"Network", // Stack Version
                  "0000-0003"=>"Network", // HW Version
                  "0000-0004"=>"Network", // Manufacturer Name
                  "0000-0005"=>"Network", // Model Identifier
                  "0000-0006"=>"Network", // Date Code
                  "0000-0010"=>"Network", // Location Description
                  "0000-03-0001"=>"Network",    // SW / Appli Version
                  "0000-03-0004"=>"Network",    // Manufacturer Name
                  "0000-03-0005"=>"Network",    // Model Identifier
                  "0000-03-4000"=>"All",
                  "0000-0B-0004"=>"Network",    // Manufacturer Name
                  "0000-0B-0005"=>"Network",    // Model Identifier
                  "0000-0B-4000"=>"Network",
                  "0000-4000"=>"Network",

                  "0000-ff01"=>"additionalCommand", // Proprio Xiaomi

                  "0001-0021"=>"Network", // Batterie % based on Ikea sniff

                  "0006-0000"=>"All",       // On/Off Cluster - OnOff
                  "0006-02-0000"=>"All",    // On/Off Cluster - OnOff
                  "0006-03-0000"=>"All",    // On/Off Cluster - OnOff
                  "0006-04-0000"=>"All",    // On/Off Cluster - OnOff
                  "0006-05-0000"=>"All",    // On/Off Cluster - OnOff
                  "0006-0B-0000"=>"All",    // On/Off Cluster - OnOff
                  "0006-8000"=>"All",       // Multi Press on Xiaomi Switch
                  "0008-0000"=>"All",       // CurrentLevel
                  "0008-03-0000"=>"All",    // CurrentLevel
                  "0008-0B-0000"=>"All",    // CurrentLevel
                  "000C-03-0055"=>"All",    // Rotation angle cube ?
                  "000C-03-ff05"=>"All",    // Rotation  cube ?
                  "000c-ff05"=>"All",       // Rotation  cube ?
                  "0012-0055"=>"All",       // Mouvement Cube
                  "0012-02-0055"=>"All",    // Mouvement Cube
                  "0101-0055"=>"All",       // Sensor Vibration: 1: Vibration, 2: rotation, 3: chute
                  "0101-0503"=>"All",       // Sensor Vibration: angle
                  "0300-0003"=>"All",       // colorX: Ampoule Ikea
                  "0300-0004"=>"All",       // colorY: Ampoule Ikea
                  "0400-0000"=>"All",       // Luminosite
                  "0402-0000"=>"All",       // Temperature
                  "0403-0000"=>"All",       // Pression
                  "0403-0010"=>"Network",       // Pression
                  "0403-0014"=>"Network",       // Pression Scale
                  "0405-0000"=>"All",       // Humidite
                  "0406-0000"=>"All",       // Presence
                  "0500-0000"=>"All",       // Smoke

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
        foreach ( $equipment["commands"] as $key=>$command ) {
            // var_dump($command["configuration"]);
            // var_dump($command["configuration"]["visibilityCategory"]);
            if ( isset( $command["configuration"] ) ) {
                if ( isset( $command["configuration"]["visibilityCategory"] ) ) {
                    if ( $debug ) echo $key.'->'.$command["configuration"]["visibilityCategory"]."\n";
                }
                else {
                    if ( $debug ) echo $key . "\n";
                    $json[$id]["commands"][$key]["configuration"]["visibilityCategory"] = $commands[$key];
                }
            }
            else {
                if ( $debug ) echo $key . "\n";
                $json[$id]["commands"][$key]["configuration"] = array( "visibilityCategory" => $commands[$key] );
                // $json[$NE]["Commandes"][$key]["configuration"]["visibilityCategory"] = $commands[$key];
            }
        }
    }


    if ( $debug==0 )  echo json_encode($json);
?>
