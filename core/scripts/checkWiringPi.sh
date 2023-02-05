#! /bin/bash

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)'"
echo "Démarrage de '$(basename $0)'"

'gpio' commands are provided from "WiringPi" package (or equivalent)
echo "Vérification de l'installation du package 'WiringPi'"
command -v gpio >/dev/null
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'gpio' manquante ou non exécutable !"
    echo "=         Le package WiringPi est probablement mal installé."
    exit 1
fi
echo "= Ok"

exit 0
