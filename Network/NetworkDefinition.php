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
                     "d43e" => "P-G",
                     "f984" => "P-C",
                     
                     "4260" => "P-Entree",
                     
                     
                     "41c0" => "PriseX1",       // 00:15:8d:00:01:83:af:7b
                     "db83" => "PriseX2",       // 00:15:8d:00:01:83:af:eb
                     
                     "498d" => "HueGo",         // 00:17:88:01:02:14:ff:6b
                     
                     "7714" => "Bois",          // 00:0b:57:ff:fe:4b:ab:6a
                     
                     "873a" => "IR",
                     
                     "b774" => "Porte Bureau",
                     "1be0" => "Temperature Bureau",
                   
                     "28f2" => "Fenetre SdB",
                     
                     "5571" => "Grad-Bureau",
                     
                     "9573" => "Inconnu",
                     
                     "e4c0" => "Ch Parent Ben",
                     
                     "6c0B" => "Sonnette",

                     "0F7e" => "Inconnu1",
                     
                     "137f" => "Velux-SdB-Tour",
                     "4c3a" => "Velux-Lulu",
                     
                     
                     
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
                      'Ruche'    => array('position' => array( 'x'=>670, 'y'=>500), 'color'=>'red',),
    // Abeille Prod JeedomZwave
                      'T1'       => array('position' => array( 'x'=>300, 'y'=>450), 'color'=>'orange',),
                      'T2'       => array('position' => array( 'x'=>400, 'y'=>450), 'color'=>'orange',),
                      'T3'       => array('position' => array( 'x'=>450, 'y'=>350), 'color'=>'orange',),
                      'T4'       => array('position' => array( 'x'=>450, 'y'=>250), 'color'=>'orange',),
                      'T5'       => array('position' => array( 'x'=>500, 'y'=>200), 'color'=>'orange',),
                      'T6'       => array('position' => array( 'x'=>600, 'y'=>200), 'color'=>'orange',),
                      'T7'       => array('position' => array( 'x'=>625, 'y'=>450), 'color'=>'orange',),
                      'T8'       => array('position' => array( 'x'=>450, 'y'=>500), 'color'=>'orange',),
                      
                      'Gauche'    => array('position' => array( 'x'=>700, 'y'=>300), 'color'=>'orange',),
                      'Milieu'    => array('position' => array( 'x'=>650, 'y'=>300), 'color'=>'orange',),
                      'Droite'    => array('position' => array( 'x'=>650, 'y'=>350), 'color'=>'orange',),
                      'Piano'     => array('position' => array( 'x'=>720, 'y'=>400), 'color'=>'orange',),
                      
                      'P-D' => array('position' => array( 'x'=>625, 'y'=>300), 'color'=>'grey',),
                      'P-G' => array('position' => array( 'x'=>625, 'y'=>350), 'color'=>'grey',),
                      'P-C' => array('position' => array( 'x'=>500, 'y'=>500), 'color'=>'grey',),
                      
                      'P-Entree' => array('position' => array( 'x'=>625, 'y'=>700), 'color'=>'grey',),
                      
                      'PriseX1' => array('position' => array( 'x'=>650, 'y'=>500), 'color'=>'orange',),
                      'PriseX2' => array('position' => array( 'x'=>650, 'y'=>520), 'color'=>'orange',),
                      
                      'HueGo' => array('position' => array( 'x'=>650, 'y'=>480), 'color'=>'orange',),
                      
                      'Bois' => array('position' => array( 'x'=>670, 'y'=>480), 'color'=>'orange',),
                    
                      'IR' => array('position' => array( 'x'=>690, 'y'=>480), 'color'=>'grey',),
                      
                      'Porte Bureau' => array('position' => array( 'x'=>725, 'y'=>480), 'color'=>'grey',),
                      'Temperature Bureau' => array('position' => array( 'x'=>710, 'y'=>480), 'color'=>'grey',),
                      
                      'Fenetre SdB' => array('position' => array( 'x'=>800, 'y'=>480), 'color'=>'grey',),
                      
                      'Grad-Bureau' => array('position' => array( 'x'=>700, 'y'=>500), 'color'=>'green',),
                      
                      'Inconnu' => array('position' => array( 'x'=>720, 'y'=>500), 'color'=>'yellow',),
                      
                      'Ch Parent Ben' => array('position' => array( 'x'=>650, 'y'=>275), 'color'=>'yellow',),
                      
                      'Sonnette' => array('position' => array( 'x'=>625, 'y'=>650), 'color'=>'grey',),
                      
                      'Inconnu1' => array('position' => array( 'x'=>425, 'y'=>650), 'color'=>'grey',),
                      
                      'Velux-SdB-Tour'  => array('position' => array( 'x'=>300, 'y'=>500), 'color'=>'grey',),
                      'Velux-Lulu'      => array('position' => array( 'x'=>300, 'y'=>550), 'color'=>'grey',),
                      

                      
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
    
    $liaisonsRadio = array(
                           'Ruche-T3'   => array( 'source'=>'Ruche', 'destination'=>'T3'    ),
                           'T3-T5'      => array( 'source'=>'T3', 'destination'=>'T5'       ),
                           'T3-Ruche'   => array( 'source'=>'T3', 'destination'=>'Ruche'    ),
                           );
    ?>



