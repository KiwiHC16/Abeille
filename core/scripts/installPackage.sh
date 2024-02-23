#! /bin/bash

###
### Package installation script
###

### WiringPi case
### Note: This assumes standard plateform (ex: RPI) with supported package
### Note: WiringOPI is the equivalent for OrangePi

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)'"

if [ $# -lt 1 ]; then
    echo "= ERREUR: Nom du package manquant !"
    echo "=         installPackage.sh <packageName>"
    exit 1
fi
PACKAGE=$1
if [ "${PACKAGE}" != "WiringPi" ] && [ "${PACKAGE}" != "PiGpio" ] && [ "${PACKAGE}" != "socat" ]; then
    echo "= ERREUR: Package ${PACKAGE} non supporté !"
    echo "=         Choix: WiringPi, PiGpio ou socat"
    exit 1
fi


echo "Mise-à-jour de la liste des packages"
sudo apt-get -y update
if [ $? -ne 0 ]; then
    echo "= ERREUR: 'apt-get update'"
    echo "=         Problème de droits ?"
    exit 2
fi
echo "= Ok"

ERROR=0
if [ "${PACKAGE}" == "WiringPi" ]; then
    echo "Installation du package 'WiringPi'"
    sudo apt-get -y install wiringpi
    if [ $? -ne 0 ]; then
        echo "= ERREUR: L'installation n'a pas aboutie."
        echo "=         Il se peut que le package WiringPi ne soit pas supporté sur votre plateforme."
        exit 3
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
elif [ "${PACKAGE}" == "PiGpio" ]; then
    sudo apt-get -y install python3-pigpio python3-setuptools
    if [ $? -ne 0 ]; then
        echo "= ERREUR: L'installation n'a pas aboutie."
        echo "=         Il se peut que le package PiGpio ne soit pas supporté sur votre plateforme."
        exit 3
    fi
    echo "= Ok, package installé."
    # TODO: Any check ?
elif [ "${PACKAGE}" == "socat" ]; then
    echo "Installation de Socat"
    sudo apt-get install socat
    if [ $? -ne 0 ]; then
        echo "= ERREUR: L'installation n'a pas aboutie."
        echo "=         Il se peut que le package 'socat' ne soit pas supporté sur votre plateforme."
        exit 3
    fi
    echo "= Ok, package installé."

    echo "Vérification finale de 'socat'"
    command -v socat
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Commande 'socat' manquante !"
        echo "=         Le package 'socat' semble être mal installé."
        exit 3
    fi
    echo "= Ok"
fi

exit $ERROR
