<?php
    
    /***
     * CheckBattery
     * argv[1]: Batterie | Alive | Ping
     * argv[2]: Test | List | Nom Objet (Ping) e.g: AmpouleIkea pour une ampoule Ikea.
     *
     * Look at Batteries level and set an alarme if at least one is below the min.
     * Look at lastCommunication and set an alarme if at least one is too old.
     *
     */
    
    // Parametres
    $debug = 0;
    $minBattery = 50; // Taux d'usage de la batterie pour générer une alarme.
    $maxTime    = 24 * 60 * 60; // temps en seconde, temps max depuis la derniere remontée d'info de cet équipement
    
    
    // Liste des équipements à ignorer
    $excludeEq = array(
                       "[Ruche][Ruche]" => 1,
                       "[Ruche][CheckEquipementsWithBatteries]" => 1,  // L objet du script lui-meme
                       "[Ruche][Abeille-c576]" => 1,
                       );
    
    // Variables
    $Alarme = 0; // Passe a un si au moins une batterie est sous le seuil.
    $PingCommande = array(
                          "AmpouleIkea" => "getEtat",
                          );
    
    // Lib needed
    require_once dirname(__FILE__)."/../../../../core/php/core.inc.php";
    
    // Gestion des parametres fournis lors du lancement du script
    // argv[1]: Batterie | Alive | Ping
    // argv[1]: Test | List
    
    if ( !isset( $argv[1] ) ) return;
    if ( !isset( $argv[2] ) ) return;
    
    if ( $debug ) print_r( $argv );
    
    // -- récupère tous les équipements
    $eqLogics = new eqLogic();
    $eqLogics = eqLogic::all();
    
    if ($debug) echo "Début monitoring\n";
    
    // -- Parcours tous les équipements
    foreach($eqLogics as $eqLogic)
    {
        $battery = -1;
        $lastCommunication = -1;
        
        if ($debug) echo "\n";
        
        // -- Si l'équipement se trouve dans la liste des équipements à ignorer : on passe au suivant
        if ($excludeEq[$eqLogic->getHumanName()] == 1){
            if ($debug) echo "-- Equipement " . $eqLogic->getHumanName() . " ignoré (car dans la liste)" . "\n";
            continue;
        }
        
        $battery = $eqLogic->getStatus("battery");
        $lastCommunication = $eqLogic->getStatus("lastCommunication");
        if ($debug) echo 'Equipement: ' . $eqLogic->getHumanName()  . " - " . $lastCommunication . " - " . $battery . "\n";
        
        // -- Si l'équipement ne retourne pas de valeur de batterie alors qu on teste batterie : on passe au suivant
        if ( ($argv[1]=="Batterie") && ($battery == "") ) {
            if ($debug) echo '-- Equipement ' . $eqLogic->getHumanName() . ' ignoré car pas d info batterie' . "\n";
            continue;
        }
        
        // -- Si l'équipement ne retourne pas de valeur de last communcation : on passe au suivant
        if ( ($lastCommunication == "") ) {
            if ($debug) echo '-- Equipement ' . $eqLogic->getHumanName() . ' ignoré' . "\n";
            continue;
        }
        
        // -- On verifie le niveau de batterie
        if ( $argv[1]=="Batterie" ) {
            if ($battery <= $minBattery){
                // -- le niveau batterie est trop bas : alerte !!! (mail ? log ? sms?)
                // -- /!\alert
                if ($debug) echo "Alerte\n";
                $Alarme = 1;
                if ( $argv[2]=="List" ) {
                    echo " </br> " . $eqLogic->getHumanName() . " - " . $battery;
                }
            }
        }
        
        // -- Calcule le temps écoulé entre la date maximun et aujourd'hui (récuptat en minute)
        $elapsedTime = time() - strtotime($lastCommunication);
        
        if ( $argv[1]=="Alive" ) {
            if ($elapsedTime > $maxTime){
                // -- le temps est supérieur au temps spécifié : alerte !!! (mail ? log ? sms?)
                // -- /!\alert
                if ($debug) echo "Alerte\n";
                $Alarme = 1;
                if ( $argv[2]=="List" ) {
                    echo " </br> " . $eqLogic->getHumanName() ;
                }
            }
        }
        
        // -- On ping la valeur Etat de tous les équipements qui ne sont pas sur batteries
        if ( ($argv[1]=="Ping") && ($battery == "") ) {
            $cmdName = '#' . $eqLogic->getHumanName() . '['.$PingCommande[$argv[2]].']#';
            try { // on fait cmd::byString pour trouver une commande mais si elle n'est pas trouvée ca genere une exception et le execCmd n'est pas executé.
                $cmd = cmd::byString($cmdName);
                $cmd->execCmd();
            } catch (Exception $e) {
                if ($debug) echo 'Exception reçue car la commande n est pas trouvee: ',  $e->getMessage(), "\n";
            }
            
        }
    }
    
    
    // log fin de traitement
    if ($debug) echo 'fin monitoring' . "\n";
    
    if ( $argv[2]=="Test" ) { echo $Alarme; } else echo "\n";
    ?>