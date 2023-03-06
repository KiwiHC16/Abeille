<?php
    
    /***
     * CheckAlive
     *
     * Look at lastCommunication and set an alarme if is too old.
     *
     */
    
    // Parametres
    $debug = 0;
    $maxTime = 3600; // temps en seconde, temps max depuis la derniere remontée d'info de cet équipement
    
    // Liste des équipements à ignorer
    $excludeEq = array(
                       "[Ruche][Ruche]" => 1,
                       );
    
    // Variables
    $Alarme = 0; // Passe a un si au moins une batterie est sous le seuil.
    
    // Lib needed
    require_once dirname(__FILE__)."/../../../../core/php/core.inc.php";
    


    
    // -- récupère tous les équipements
    $eqLogics = new eqLogic();
    $eqLogics = eqLogic::all();
    
    if ($debug) echo "Début monitoring\n";
    
    // -- Parcours tous les équipements
    foreach($eqLogics as $eqLogic)
    {
        // -- Si l'équipement se trouve dans la liste des équipements à ignorer : on passe au suivant
        if ($excludeEq[$eqLogic->getHumanName()] == 1){
            if ($debug) echo '-- Equipement ' . $eqLogic->getHumanName() . ' ignoré' . "\n";
            continue;
        }
        
        $collectBattery = $eqLogic->getStatus("battery");
        $collectDate = $eqLogic->getStatus("lastCommunication");
        if ($debug) echo 'Equipement: ' . $eqLogic->getHumanName()  . " - " . $collectDate . ' - ' . $collectBattery . "\n";
        if ($debug) echo "TimeStamp: " . strtotime($collectDate) . "\n";
        if ($debug) echo "TimeStamp: " . time() . "\n";
        
        // -- Calcule le temps écoulé entre la date maximun et aujourd'hui (récuptat en minute)
        $elapsedTime = time() - strtotime($collectDate);
        
        if ($elapsedTime > $maxTime){
            // -- le temps est supérieur au temps spécifié : alerte !!! (mail ? log ? sms?)
            // -- /!\alert
            if ($debug) echo "Alerte\n";
            $Alarme = 1;
        }
    }
    
    
    // log fin de traitement
    if ($debug) echo 'fin monitoring' . "\n";
    
    echo $Alarme;
    ?>