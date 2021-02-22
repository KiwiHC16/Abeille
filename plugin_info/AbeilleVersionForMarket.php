<?php

$commands = array();

$i=0;
$today = date("Ymd");
$printOnlyCmd = 1;

// -----------------------------------------------------------------------------------
// Preparation des operations
//
$commands[$i  ]['txt'] = "\n------------------------------\n           Preparation \n------------------------------\n";
$commands[$i++]['cmd'] = "";

$commands[$i  ]['txt'] = "Recuperation de la branche master.";
$commands[$i++]['cmd'] = "git checkout master";

$commands[$i  ]['txt'] = "Rettoyage du repertoire pour être en ligne avec le serveur.";
$commands[$i++]['cmd'] = "git reset --hard HEAD";

$commands[$i  ]['txt'] = "Recuperation dernieres infos du serveur.";
$commands[$i++]['cmd'] = "git fetch";

$commands[$i  ]['txt'] = "Recuperation derniers fichiers du serveur.";
$commands[$i++]['cmd'] = "git pull";

// -----------------------------------------------------------------------------------
// Gestion de la version stable
//
$commands[$i  ]['txt'] = "\n------------------------------\n           Creation version stable \n------------------------------\n";
$commands[$i++]['cmd'] = "";

$commands[$i  ]['txt'] = "Recuperation de la branche stable.";
$commands[$i++]['cmd'] = "git checkout stable";

$commands[$i  ]['txt'] = "Recuperation derniers fichiers du serveur.";
$commands[$i++]['cmd'] = "git pull";

$commands[$i  ]['txt'] = "Creation d une copie de la branche stable.";
$commands[$i++]['cmd'] = "git checkout -b stable-".$today;

$commands[$i  ]['txt'] = "Envoi au serveur.";
$commands[$i++]['cmd'] = "git push --set-upstream origin stable-".$today; // < user/pass

$commands[$i  ]['txt'] = "Retour sur master.";
$commands[$i++]['cmd'] = "git checkout master";

$commands[$i  ]['txt'] = "Suppression de la branche stable localement.";
$commands[$i++]['cmd'] = "git branch -d stable";

$commands[$i  ]['txt'] = "Suppression de la branche stable sur le serveur.";
$commands[$i++]['cmd'] = "git push origin --delete stable";

$commands[$i  ]['txt'] = "Recuperation derniere infos du serveur.";
$commands[$i++]['cmd'] = "git fetch";

$commands[$i  ]['txt'] = "Recuperation des derniers fichiers master.";
$commands[$i++]['cmd'] = "git pull";

$commands[$i  ]['txt'] = "Retour sur beta.";
$commands[$i++]['cmd'] = "git checkout beta";

$commands[$i  ]['txt'] = "Creation d une copie de la branche beta en stable.";
$commands[$i++]['cmd'] = "git checkout -b stable";

$commands[$i  ]['txt'] = "Envoi au serveur.";
$commands[$i++]['cmd'] = "git push --set-upstream origin stable"; // < user/pass

$commands[$i  ]['txt'] = "Set version in the file.";
$commands[$i++]['cmd'] = "echo '".date('Ymd-')."stable' > Abeille.version";

$commands[$i  ]['txt'] = "Add version file.";
$commands[$i++]['cmd'] = "git add Abeille.version";

$commands[$i  ]['txt'] = "Add version file.";
$commands[$i++]['cmd'] = "git commit -m 'Mondays version' ";

$commands[$i  ]['txt'] = "Envoi au serveur.";
$commands[$i++]['cmd'] = "git push";

$commands[$i  ]['txt'] = "Recuperation de la branche master.";
$commands[$i++]['cmd'] = "git checkout master";

// -----------------------------------------------------------------------------------
// Gestion de la version beta
//
$commands[$i  ]['txt'] = "\n------------------------------\n           Creation version beta \n------------------------------\n";
$commands[$i++]['cmd'] = "";

$commands[$i  ]['txt'] = "Recuperation de la branche beta.";
$commands[$i++]['cmd'] = "git checkout beta";

$commands[$i  ]['txt'] = "Recuperation derniers fichiers du serveur.";
$commands[$i++]['cmd'] = "git pull";

$commands[$i  ]['txt'] = "Creation d une copie de la branche beta.";
$commands[$i++]['cmd'] = "git checkout -b beta-".$today;

$commands[$i  ]['txt'] = "Envoi au serveur.";
$commands[$i++]['cmd'] = "git push --set-upstream origin beta-".$today;

$commands[$i  ]['txt'] = "Retour sur master.";
$commands[$i++]['cmd'] = "git checkout master";

$commands[$i  ]['txt'] = "Suppression de la branche beta localement.";
$commands[$i++]['cmd'] = "git branch -d beta";

$commands[$i  ]['txt'] = "Suppression de la branche beta sur le serveur.";
$commands[$i++]['cmd'] = "git push origin --delete beta";

$commands[$i  ]['txt'] = "Recuperation derniere infos du serveur.";
$commands[$i++]['cmd'] = "git fetch";

$commands[$i  ]['txt'] = "Recuperation des derniers fichiers master.";
$commands[$i++]['cmd'] = "git pull";

$commands[$i  ]['txt'] = "Retour sur master.";
$commands[$i++]['cmd'] = "git checkout master";

$commands[$i  ]['txt'] = "Creation d une copie de la branche master en beta.";
$commands[$i++]['cmd'] = "git checkout -b beta";

$commands[$i  ]['txt'] = "Envoi au serveur.";
$commands[$i++]['cmd'] = "git push --set-upstream origin beta"; // < user/pass

$commands[$i  ]['txt'] = "Set version in the file.";
$commands[$i++]['cmd'] = "echo '".date('Ymd-')."beta' > Abeille.version";

$commands[$i  ]['txt'] = "Add version file.";
$commands[$i++]['cmd'] = "git add Abeille.version";

$commands[$i  ]['txt'] = "Add version file.";
$commands[$i++]['cmd'] = "git commit -m 'Mondays version' ";

$commands[$i  ]['txt'] = "Envoi au serveur.";
$commands[$i++]['cmd'] = "git push";

$commands[$i  ]['txt'] = "Recuperation de la branche master.";
$commands[$i++]['cmd'] = "git checkout master";

// -----------------------------------------------------------------------------------
// Gestion de la version master 
//

$cmdNb = 0;
foreach ( $commands as $key=>$cmd ) {
    if ($printOnlyCmd) {
        echo $cmd['cmd'] . "\n";
    }
    else {
        if ( $cmd['cmd']=="" ) {
            echo $cmd['txt'] . "\n";
        }
        else {
            echo $cmdNb++ . " -> " . $cmd['txt'] . " -> " . $cmd['cmd'] . "\n";
        }
    }

}



?>
