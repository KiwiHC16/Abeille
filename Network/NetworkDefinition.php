<?php
    

    $knownNE = array(
                     "0000" => "Ruche",         // 00:15:8d:00:01:b2:2e:24
 // Abeille Prod JeedomZwave
                     "dc15" => "T1",
                     "1e8c" => "T2",
                     "174f" => "T3",            // 00:0b:57:ff:fe:49:10:ea
                     "6766" => "T4",
                     "1b7b" => "T5",
                     "8ffe" => "T6",
                     "2096" => "T7",
                     "2cd5" => "T8",
                     
                     "a0da" => "Gauche",
                     // "0cd5" => "Milieu",
                     "60fb" => "Milieu",
                     "46d9" => "Droite",        // 00:0b:57:ff:fe:95:2a:69
                     "a728" => "Piano",         // 00:0b:57:ff:fe:3a:0e:7c
                     
                     "345f" => "P-D",
                     "2349" => "P-G",		// 00:15:8d:00:01:b1:49:a8
                     "f984" => "P-C",
                     
                     "8f28" => "P-Entree",
                     
                     
                     "41c0" => "PriseX1",       // 00:15:8d:00:01:83:af:7b
                     "db83" => "PriseX2",       // 00:15:8d:00:01:83:af:eb
                     
                     "498d" => "HueGo",         // 00:17:88:01:02:14:ff:6b
                     
                     "7714" => "Bois",          // 00:0b:57:ff:fe:4b:ab:6a
                     
                     "873a" => "IR",
                     
                     "b774" => "Porte Bureau",
                     "1be0" => "Temperature Bureau",
                   
                     "d43e" => "Fenetre SdB G",
                     "28f2" => "Fenetre SdB D",
                     
                     "5571" => "Grad-Bureau",
                     
                     "e4c0" => "Ch Parent Ben",
                     
                     "eb79" => "Sonnette",

                     "0F7e" => "Inconnu1", 		// 90:fd:9f:ff:fe:16:5c:2c
                     
                     "137f" => "Velux-SdB-Tour",
                     "4c3a" => "Velux-Lulu",

                     "c551" => "Old-Off-Network-1", 	// 00:0B:57:ff:fe:8d:70:73
                     
                     
                     
  // Abeille Test abeille
                     "df33" => "Test: Temperature Rond Bureau",
                     "a008" => "Test: Door V1 Bureau",
                     "7bd5" => "Test: Inondation Bureau",
                     "dcd9" => "Test: Bouton Carre V2 Bureau",
                     "3950" => "Test: Door V0 Bureau",
                     "5dea" => "Test: IR Pied Bureau",
                     "4ebd" => "Test: Interrupteur Rond",
                     "633e" => "Test: xxxxxx",
                     "c7c0" => "Test: RI V0",
                     "d45e" => "Test: Ampoule Bois Bureau",
                     "2389" => "Test: Detecteur Smoke",
                     
                     );

    
    
    $Abeilles = array(
                      'Ruche'    => array('position' => array( 'x'=>700, 'y'=>520), 'color'=>'red',),
    // Abeille Prod JeedomZwave
	// Terrasse
                      'T1'       => array('position' => array( 'x'=>300, 'y'=>450), 'color'=>'orange',),
                      'T2'       => array('position' => array( 'x'=>400, 'y'=>450), 'color'=>'orange',),
                      'T3'       => array('position' => array( 'x'=>450, 'y'=>350), 'color'=>'orange',),
                      'T4'       => array('position' => array( 'x'=>450, 'y'=>250), 'color'=>'orange',),
                      'T5'       => array('position' => array( 'x'=>500, 'y'=>200), 'color'=>'orange',),
                      'T6'       => array('position' => array( 'x'=>600, 'y'=>200), 'color'=>'orange',),
                      'T7'       => array('position' => array( 'x'=>625, 'y'=>450), 'color'=>'orange',),
                      'T8'       => array('position' => array( 'x'=>450, 'y'=>500), 'color'=>'orange',),
        // Salon              
                      'Gauche'    => array('position' => array( 'x'=>700, 'y'=>300), 'color'=>'orange',),
                      'Milieu'    => array('position' => array( 'x'=>650, 'y'=>300), 'color'=>'orange',),
                      'Droite'    => array('position' => array( 'x'=>650, 'y'=>350), 'color'=>'orange',),
                      'Piano'     => array('position' => array( 'x'=>720, 'y'=>400), 'color'=>'orange',),
                      
                      'P-D' => array('position' => array( 'x'=>625, 'y'=>300), 'color'=>'grey',),
                      'P-G' => array('position' => array( 'x'=>625, 'y'=>350), 'color'=>'grey',),
                      'P-C' => array('position' => array( 'x'=>500, 'y'=>500), 'color'=>'grey',),
                      
                      'P-Entree' => array('position' => array( 'x'=>625, 'y'=>700), 'color'=>'grey',),
                      
                      'HueGo' 			=> array('position' => array( 'x'=>650, 'y'=>480), 'color'=>'orange',),
                      'PriseX1' 		=> array('position' => array( 'x'=>650, 'y'=>500), 'color'=>'orange',),
                      'PriseX2' 		=> array('position' => array( 'x'=>650, 'y'=>520), 'color'=>'orange',),
                      
                      'Bois' 			=> array('position' => array( 'x'=>700, 'y'=>480), 'color'=>'orange',),
                      'Grad-Bureau' 		=> array('position' => array( 'x'=>700, 'y'=>500), 'color'=>'green',),
                    
                      'IR' 			=> array('position' => array( 'x'=>750, 'y'=>480), 'color'=>'grey',),
                      'Temperature Bureau' 	=> array('position' => array( 'x'=>750, 'y'=>500), 'color'=>'grey',),
                      'Porte Bureau' 		=> array('position' => array( 'x'=>750, 'y'=>520), 'color'=>'grey',),
                      
                      
                      'Fenetre SdB G' => array('position' => array( 'x'=>800, 'y'=>400), 'color'=>'grey',),
                      'Fenetre SdB D' => array('position' => array( 'x'=>800, 'y'=>425), 'color'=>'grey',),
                      
                      
                      
                      'Ch Parent Ben' => array('position' => array( 'x'=>650, 'y'=>275), 'color'=>'yellow',),
                      
                      'Sonnette' => array('position' => array( 'x'=>625, 'y'=>650), 'color'=>'grey',),
                      
                      'Velux-SdB-Tour'  => array('position' => array( 'x'=>300, 'y'=>525), 'color'=>'grey',),
                      'Velux-Lulu'      => array('position' => array( 'x'=>300, 'y'=>575), 'color'=>'grey',),
                      
                      'Inconnu1' 			=> array('position' => array( 'x'=>900, 'y'=>75), 'color'=>'purple',),
                      'Old-Off-Network-1'            	=> array('position' => array( 'x'=>900, 'y'=>100), 'color'=>'purple',),

                      
                      // Abeille Test abeille
                      "Test: Temperature Rond Bureau"   => array('position' => array( 'x'=>100, 'y'=>100), 'color'=>'grey',),
                      "Test: Door V1 Bureau"            => array('position' => array( 'x'=>100, 'y'=>150), 'color'=>'grey',),
                      "Test: Inondation Bureau"         => array('position' => array( 'x'=>100, 'y'=>200), 'color'=>'grey',),
                      "Test: Bouton Carre V2 Bureau"    => array('position' => array( 'x'=>100, 'y'=>250), 'color'=>'grey',),
                      "Test: Door V0 Bureau"            => array('position' => array( 'x'=>100, 'y'=>300), 'color'=>'grey',),
                      "Test: IR Pied Bureau"            => array('position' => array( 'x'=>100, 'y'=>350), 'color'=>'grey',),
                      "Test: Interrupteur Rond"         => array('position' => array( 'x'=>100, 'y'=>400), 'color'=>'grey',),
                      "Test: xxxxxx"                    => array('position' => array( 'x'=>100, 'y'=>450), 'color'=>'grey',),
                      "Test: RI V0"                     => array('position' => array( 'x'=>100, 'y'=>500), 'color'=>'grey',),
                      "Test: Ampoule Bois Bureau"       => array('position' => array( 'x'=>100, 'y'=>550), 'color'=>'orange',),
                      "Test: Detecteur Smoke"           => array('position' => array( 'x'=>100, 'y'=>600), 'color'=>'grey',),
                      
                      );
    
    // $liaisonsRadio = array(
                           // 'Ruche-T3'   => array( 'source'=>'Ruche', 'destination'=>'T3'    ),
                           // 'T3-T5'      => array( 'source'=>'T3', 'destination'=>'T5'       ),
                           // 'T3-Ruche'   => array( 'source'=>'T3', 'destination'=>'Ruche'    ),
                           // );
    ?>



