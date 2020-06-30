#! /bin/bash

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)'"

# 'socat'
echo "Vérification de l'installation du package 'socat'"
hash socat 2>/dev/null
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'socat' manquante !"
    echo "=         Le package 'socat' semble mal installé."
    exit 1
fi
echo "= Ok"

exit 0
