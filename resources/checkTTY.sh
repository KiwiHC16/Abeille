#! /bin/bash

###
### Check TTY port and proper access to Zigate.
### WARNING: This script expects that daemon is stoppped to
###          not disturb Zigate answer.
###

# Usage
# checkTTY.sh <portname> <zigatetype>
# ex: checkTTY.sh /dev/ttyS1 PI

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)'"

if [ $# -lt 2 ]; then
    echo "= ERREUR: Port et/ou type manquant !"
    exit 1
fi
PORT=$1
TYPE=$2

echo "Vérifications du port '${PORT}'"

# Port exists ?
if [ ! -e ${PORT} ]; then
    echo "= ERREUR: Le port ${PORT} n'existe pas !"
    exit 2
fi

# Is port already used ?
FIELDS=`sudo lsof -Fcn ${PORT}`
if [ "${FIELDS}" == "" ]; then
    echo "= Ok, le port semble libre."
else
    CMD=""
    for f in ${FIELDS};
    do
        if [[ "$f" != "c"* ]]; then
            continue
        fi
        CMD=${f:1}
    done
    echo "= ERREUR: Port utilisé par la commande '${CMD}'."
    echo "=         Le port doit être libéré pour permettre le dialogue avec la Zigate."
    exit 3
fi

# If Zigate type is "PI", let's check if "WiringPi" is properly installed
if [ "${TYPE}" == "PI" ]; then
    # 'gpio' commands are provided from "WiringPi" package (or equivalent)
    echo "Vérification de l'installation du package 'WiringPi'"
    hash gpio 2>/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Commande 'gpio' manquante !"
        echo "=         Le package 'WiringPi' est probablement mal installé."
        exit 4
    fi
    echo "= Ok"
elif [ "${TYPE}" == "WIFI" ]; then
    echo "= ERREUR: Type 'WIFI' inattendu ici."
    exit 5
fi

exit 0
