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

function addMosquittoRepoKey
{
if [ -f mosquitto-repo.gpg.key ]; then
    echo "Efface ancien mosquitto-repo.gpg.key"
    rm mosquitto-repo.gpg.key
fi

wget --tries=${tries} http://repo.mosquitto.org/debian/mosquitto-repo.gpg.key
[[ $? -ne 0 ]] && arretSiErreur "Erreur lors de la récupération de la clé du dépot moquitto. Pb réseau ?"
apt-key add mosquitto-repo.gpg.key

if [ -f mosquitto-repo.gpg.key ]; then
    echo "Efface la clé importée"
    rm mosquitto-repo.gpg.key
fi

}

function addMosquittoRepo
{

    [[ $# != 3 ]] && arretSiErreur "Appel a la fonction addMosquittoRepo avec parametres incorrects var1=$1 var2=$2 var3=$3"

    archi=$1
    distrib=$2
    version=$3

    echo "distrib: $archi Release trouvée: ${distrib} Version trouvée: ${version}"

    [[  "x86_64i686armv7larmv6laarch64" != *${archi}* ]] && arretSiErreur "Erreur critique: je ne connais pas cette archi: ${archi}"

    #Nothing to do for Ubuntu
    [[ "Ubuntu" == ${distrib} ]] && return 1

    #Debian Raspbian same file location
    [[  "DebianRaspbian" !=  *${distrib}* ]] && arretSiErreur "Erreur critique: je ne connais pas cette distribution: ${distrib}, les connues sont Ubuntu, Debian et Raspbian"

    #the old stable jessie and the newest stable stretch
    [[  "jessiestretch" != *${version}* ]] && arretSiErreur "Erreur critique: les versions connues sont Jessie et Stretch.ici, on a ${version}"


    addMosquittoRepoKey

    if [ -f /etc/apt/sources.list.d/mosquitto-${version}.list ]; then
      echo "Efface ancien /etc/apt/sources.list.d/mosquitto-${version}.list"
      rm /etc/apt/sources.list.d/mosquitto-${version}.list
    fi

    wget --tries=${tries} http://repo.mosquitto.org/debian/mosquitto-${version}.list -O /etc/apt/sources.list.d/mosquitto-${version}.list
    [[ $? -ne 0 ]] && arretSiErreur "Erreur lors de la mise ajour des dépots mosquitto pour la ${distrib}-${version}. Pb réseau ?"
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

if [ -d "/etc/php5/fpm/" ]; then
  echo "system tourne avec fpm et php5"
  SERVICE="php5-fpm"
elif [ -d "/etc/php5/apache2/" ]; then
  echo "system tourne avec apache2 et php5"
  SERVICE="apache2"
elif [ -d "/etc/php/7.0/fpm/" ]; then
  echo "system tourne avec fpm et php7"
  SERVICE="php7-fpm"
elif [ -d "/etc/php/7.0/apache2" ]; then
  echo "system tourne avec apache2 et php7"
  SERVICE="apache2"
else
  arretSiErreur "Erreur critique, je ne reconnais pas le system (apache, php,...)"
fi

echo 5 > ${PROGRESS_FILE}
echo
echo "Avancement: 5% ---------------------------------------------------------------------------------------------------> Install lsb-release php-pear"
echo

apt-get -y install dpkg

apt-get -y install lsb-release php-pear make locales
[[ $? -ne 0 ]] && arretSiErreur "Erreur lors de l'installation de pear et lsb-release. Pb réseau ?"

echo 8 > ${PROGRESS_FILE}
echo
echo "Avancement: 8% ---------------------------------------------------------------------------------------------------> Ajout repo mosquitto"
echo

# Test sur l archi mais en fait on fait la meme chose, je garde le test si on devait en avoir besoin.
addMosquittoRepo `lscpu | grep Architecture | awk '{ print $2 }'` `lsb_release -i -s` `lsb_release -c -s`

echo 10 > ${PROGRESS_FILE}
echo
echo "Avancement: 10% ---------------------------------------------------------------------------------------------------> Update list package"
echo

apt-get update
[[ $? -ne 0 ]] && arretSiErreur "Erreur lors de la mise ajour des dépots via apt-get update. Pb réseau ?"

echo 30 > ${PROGRESS_FILE}
echo
echo "Avancement: 30% ---------------------------------------------------------------------------------------------------> install mosquiito packages"
echo

apt-get -y install mosquitto mosquitto-clients libmosquitto-dev
[[ $? -ne 0 ]] && arretSiErreur "Erreur lors de l'installation de mosquitto. Pb réseau ?"

PHPVAR=""
[[ -d "/etc/php5/" ]] && PHPVER=5 && PHPETC=/etc/php5
[[ -d "/etc/php/7.0" ]] && PHPVER=7.0 && PHPETC=/etc/php/7.0
[[ -d "/etc/php/7.2" ]] && PHPVER=7.2 && PHPETC=/etc/php/7.2

if [[ ! -z ${PHPVER} ]]; then
  echo 70 > ${PROGRESS_FILE}
  echo
  echo "Avancement: 70% ---------------------------------------------------------------------------------------------------> php deja present on installe php-dev et les librairies mosquitto"
  echo

  apt-get -y install php${PHPVER}-dev
  pecl update-channels
  pecl channel-update pecl.php.net
  echo "" | pecl install Mosquitto-beta

  for phpdir in "${PHPETC}/cli/" "${PHPETC}/fpm/"  "${PHPETC}/apache2/"; do
    if [[ -d ${phpdir} && $(grep -c mosquitto ${phpdir}/php.ini ) -eq 0 ]]; then
        echo "extension=mosquitto.so" | tee -a ${phpdir}/php.ini
    fi
  done
fi

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

[[ ! -e /var/log/mosquitto ]] && mkdir -p /var/log/mosquitto
# Set service start at boot the oldway
update-rc.d mosquitto defaults
update-rc.d mosquitto enable
# the new way
systemctl enable mosquitto


# Docker detection, may be useful to add RPI detection here.
if [[ $(grep -c docker /proc/1/cgroup) -gt 0 ]]; then
  echo "I'm running on docker".
  /etc/init.d/mosquitto start

  if [[ "apache2" == ${SERVICE} ]]; then
    apache2ctl restart &
  else
    /etc/init.d/${SERVICE} restart
  fi
# Pour tous les autres systemes/
else
  /etc/init.d/mosquitto restart &
  sleep 5
  /etc/init.d/${SERVICE} restart &

fi
