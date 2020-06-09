#! /bin/bash

# Usage: ?

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)' $@"

echo "Mise-à-jour de la liste des packages"
sudo apt-get -y update

echo "Installation de Socat"
sudo apt-get install socat
if [ $? -ne 0 ]; then
    echo "= ERREUR: L'installation n'a pas aboutie."
    echo "=         Il se peut que le package 'socat' ne soit pas supporté sur votre plateforme."
    exit 2
fi
echo "= Ok, package installé."

echo "Vérification finale de 'socat'"
hash socat 2>/dev/null
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'socat' manquante !"
    echo "=         Le package 'socat' semble être mal installé."
    exit 3
fi
echo "= Ok"

exit 0
