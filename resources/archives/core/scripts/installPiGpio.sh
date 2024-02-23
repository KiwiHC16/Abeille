#! /bin/bash

###
### PiGpio installation script
### Note: This assumes standard plateform (ex: RPI) with supported package
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

echo "Installation du package 'PiGpio'"
sudo apt-get -y install python-pigpio python3-pigpio python-setuptools python3-setuptools
if [ $? -ne 0 ]; then
    echo "= ERREUR: L'installation n'a pas aboutie."
    echo "=         Il se peut que le package PiGpio ne soit pas supporté sur votre plateforme."
    exit 2
fi
echo "= Ok"

exit 0