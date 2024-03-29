#! /bin/bash

echo "Démarrage de '$(basename $0)'"

# 'socat'
echo "Vérification de l'installation du package 'socat'"
command -v socat
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'socat' manquante !"
    echo "=         Le package 'socat' semble mal installé."
    exit 1
fi
echo "= Ok"

exit 0
