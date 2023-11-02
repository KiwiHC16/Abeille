#! /bin/bash
#set -x

###
### MAIN
###

PROGRESS_FILE=/tmp/jeedom/Abeille/dependencies_progress
if [ ! -z $1 ]; then
    PROGRESS_FILE=$1
fi

touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo $(date)
echo "***"
echo "*** Updating packages list"
echo "***"
echo 5 > ${PROGRESS_FILE}
apt-get clean
echo 10 > ${PROGRESS_FILE}
apt-get update
echo 20 > ${PROGRESS_FILE}

echo "***"
echo "*** Installing required packages"
echo "***"
apt-get install -y python3
echo 60 > ${PROGRESS_FILE}

# Tcharp38: is this part still required ?
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
echo $(date)
echo "***"
echo "*** Dependencies installation ended"
echo "***"
rm ${PROGRESS_FILE}
