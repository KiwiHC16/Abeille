#! /bin/bash

###
### WiringPi installation script
### Note: This assumes standard plateform (ex: RPI) with supported package
### Note: WiringOPI is the equivalent for OrangePi
###

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)'"

echo "Mise-à-jour de la liste des packages"
sudo apt-get -y update
if [ $? -ne 0 ]; then
    echo "= ERREUR: 'apt-get update'"
    echo "=         Problème de droits ?"
    exit 1
fi
echo "= Ok"

# sudo apt-get -y upgrade

# echo "installation de git"
# sudo apt-get -y install git-core

# echo "installation de make"
# sudo apt-get -y install make

# echo "installation de gcc"
# sudo apt-get -y install gcc

echo "Installation du package 'WiringPi'"
sudo apt-get -y install wiringpi
if [ $? -ne 0 ]; then
    echo "= ERREUR: L'installation n'a pas aboutie."
    echo "=         Il se peut que le package WiringPi ne soit pas supporté sur votre plateforme."
    exit 2
fi
echo "= Ok"

echo "Vérification finale"
command -v gpio
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'gpio' manquante !"
    echo "=         Le package WiringPi semble être mal installé."
    exit 3
fi
echo "= Ok"

exit 0
