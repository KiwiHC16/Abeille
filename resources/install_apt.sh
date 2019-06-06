#!/bin/bash
set -x

# Variables
PROGRESS_FILE=/tmp/jeedom/Abeille/dependancy_abeille_in_progress
#Nombre d'essai pour dl les paquets
tries=3


# Functions

function arret
{
  echo
  echo "Avancement: 99% ---------------------------------------------------------------------------------------------------> ;-) "
  echo

  echo "Fin installation des dépendances"

  echo
  echo "Avancement: 100% ---------------------------------------------------------------------------------------------------> FIN"
  echo

  echo 100 > ${PROGRESS_FILE}
  sleep 3
  #suppression du decompte d'installation
  rm ${PROGRESS_FILE}

}

function arretSiErreur
{
  echo
  echo "***************"
  echo $1
  echo "***************"
  echo
  arret
  exit 1
}

# MAIN
echo "Début d'installation des dépendances"

### INIT
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}

echo 0 > ${PROGRESS_FILE}
echo
echo "Avancement: 0% ---------------------------------------------------------------------------------------------------> Environnement "
echo

cmd=`id`
echo "id: "$cmd

cmd=`pwd`
echo "pwd: "$cmd

cmd=`uname -a`
echo "uname -a: "$cmd

echo 10 > ${PROGRESS_FILE}
echo
echo "Avancement: 10% ---------------------------------------------------------------------------------------------------> Update list package"
echo

apt-get update
[[ $? -ne 0 ]] && arretSiErreur "Erreur lors de la mise ajour des dépots via apt-get update. Pb réseau ?"

echo 20 > ${PROGRESS_FILE}
echo
echo "Avancement: 20% ---------------------------------------------------------------------------------------------------> install socat packages"
echo

apt-get -y install socat

echo 90 > ${PROGRESS_FILE}
echo
echo "Avancement: 90% ---------------------------------------------------------------------------------------------------> Demarrage des services."
echo


echo "**Ajout du user www-data dans le groupe dialout (accès à la zigate)**"
if [[ `groups www-data | grep -c dialout` -ne 1 ]]; then
    useradd -g dialout www-data
    if [ $? -ne 0 ]; then
            echo "Erreur lors de l'ajout de l utilisateur www-data au groupe dialout"
        else
            echo "OK, utilisateur www-data ajouté dans le groupe dialout"
    fi
    else
        echo "OK, utilisateur www-data est déja dans le group dialout"
 fi

echo 100 > ${PROGRESS_FILE}
echo
echo "Avancement: 99% ---------------------------------------------------------------------------------------------------> ;-) "
echo
echo "Fin installation des dépendances"
echo
echo "Avancement: 100% ---------------------------------------------------------------------------------------------------> FIN"
echo

rm ${PROGRESS_FILE}

