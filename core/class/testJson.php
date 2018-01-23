<?php

    $AbeilleObjetDefinition = json_decode(file_get_contents("AbeilleObjetDefintion.json"), true);
    
    $value = "lumi.sensor_magnet.aq2";
    
    print('Abeille info Recherche objet: '.$value.' dans les objets connus'); echo "\n";
    if ( array_key_exists( $value, $AbeilleObjetDefinition ) )
    {
        $objetConnu = 1;
        print('Abeille info objet: '.$value.' peut etre cree car je connais ce type d objet.'); echo "\n";
    }
    else
    {
        print('Abeille info objet: '.$value.' ne peut pas etre cree completement car je ne connais pas ce type d objet.'); echo "\n";
    }
    
    $objetDefSpecific = $AbeilleObjetDefinition[$value];
    $objetConfiguration = $objetDefSpecific["configuration"];
    
    print_r( $objetConfiguration);
    echo "\n".$objetConfiguration["icone"]."\n";
    
    
    echo array_keys($AbeilleObjetDefinition[$value]["Categorie"])[0]."\n";
    echo $AbeilleObjetDefinition[$value]["Categorie"][  array_keys($AbeilleObjetDefinition[$value]["Categorie"])[0] ];
    ?>
