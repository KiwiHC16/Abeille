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
    PID=0
    for f in ${FIELDS};
    do
        if [[ "$f" == "p"* ]]; then
            PID=${f:1}
            break
        fi
    done
    echo "= ERREUR: Le port est utilisé par le process '${PID}'."
    echo "=         Il doit être libéré et n'être utilisé QUE par le plugin Abeille pour permettre le dialogue avec la Zigate."
    PSOUT=`ps --pid ${PID} -o ppid,cmd | grep -v PPID`
    IFS=' '
    read -ra PSOUTA <<< "${PSOUT}"
    PPID2=${PSOUTA[0]}
    CMD=${PSOUTA[@]:1}
    echo "=         Details du process ${PID}:"
    echo "=           PPid=${PPID2}, cmd='${CMD}'"
    exit 3
fi

# If Zigate type is "PI", let's check if "WiringPi" is properly installed
if [ "${TYPE}" == "PI" ]; then
    # 'gpio' commands are provided from "WiringPi" package (or equivalent)
    echo "Vérification de l'installation du package 'WiringPi'"
    command -v gpio >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Commande 'gpio' manquante !"
        echo "=         Le package 'WiringPi' est probablement mal installé."
        exit 4
    fi
    echo "= Ok"

    # Note: Do not perform PiZigate reset unless you flush messages sent
    #       on startup.
    echo "Configuration des GPIOs"
    gpio mode 0 out; gpio mode 2 out; gpio write 2 1; gpio write 0 1
    READ2=`gpio read 2`
    READ0=`gpio read 0`
    if [ ${READ2} -ne 1 ] || [ ${READ0} -ne 1 ]; then
        echo "= ERREUR: Votre package WiringPi ne semble pas fonctionnel.";
        exit 5
    fi
    echo "= Ok"

    echo "Configuration du port série"
    stty -F ${PORT} speed 115200 cs8 -parenb -cstopb -echo raw >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Etes vous sur que l'UART associée est active ?";
        sudo cat /proc/tty/driver/serial
        exit 6
    fi
    echo "= Ok"

elif [ "${TYPE}" == "WIFI" ]; then
    echo "= ERREUR: Type 'WIFI' inattendu ici."
    exit 6
fi

exit 0
