#! /bin/bash

###
### Check TTY port and proper access to Zigate.
### WARNING: This script expects that daemon is stoppped to
###          not disturb Zigate answer.
###

# Usage
# checkTTY.sh <portName> <zigateType> <gpioLib> [prefix]
# ex: checkTTY.sh /dev/ttyS1 PI WiringPi "[2021-03-18 12:44:09] "

ABEILLEROOT=${PWD}/../..
PREFIX=$4

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)'"
echo "${PREFIX}Test de port"

if [ $# -lt 3 ]; then
    echo "${PREFIX}= ERREUR: Argument(s) manquant(s) !"
    echo "${PREFIX}= Usage: checkTTY.sh <portName> <zigateType> <gpioLib> [prefix]"
    exit 1
fi
PORT=$1
TYPE=$2
GPIOLIB=$3
GPIOREQUIRED=0
case $TYPE in
    PI | PIv2)
        GPIOREQUIRED=1
    ;;
    DIN | USB | USBv2)
    ;;
    *)
        echo "${PREFIX}= ERREUR: Type ${TYPE} invalide !"
        exit 1
    ;;
esac

if [ "${GPIOREQUIRED}" -eq 1 ]; then
    if [ "${GPIOLIB}" != "WiringPi" ] && [ "${GPIOLIB}" != "PiGpio" ]; then
        echo "${PREFIX}= ERREUR: Lib GPIO ${GPIOLIB} invalide !"
        exit 1
    fi
fi

# Port exists ?
echo "${PREFIX}- Vérifications du port '${PORT}'"
if [ ! -e ${PORT} ]; then
    echo "${PREFIX}= ERREUR: Le port ${PORT} n'existe pas !"
    exit 2
fi

# Is port already used ?
FIELDS=`sudo lsof -Fcn ${PORT}`
if [ "${FIELDS}" == "" ]; then
    echo "${PREFIX}= Ok, le port semble libre."
else
    PID=0
    for f in ${FIELDS};
    do
        if [[ "$f" == "p"* ]]; then
            PID=${f:1}
            break
        fi
    done

    echo "${PREFIX}= ERREUR: Le port est utilisé par le process '${PID}'."
    echo "${PREFIX}=         Il doit être libéré et n'être utilisé QUE par le plugin Abeille pour permettre le dialogue avec la Zigate."
    PSOUT=`ps --pid ${PID} -o ppid,cmd | grep -v PPID`
    IFS=' '
    read -ra PSOUTA <<< "${PSOUT}"
    PPID2=${PSOUTA[0]}
    CMD=${PSOUTA[@]:1}
    echo "${PREFIX}=         Details du process ${PID}:"
    echo "${PREFIX}=           PPid=${PPID2}, cmd='${CMD}'"

    echo
    echo "${PREFIX}= Additional infos I (dmesg | grep tty)"
    dmesg | grep tty

    echo
    echo "${PREFIX}= Additional infos II (ls -al /dev/serial*)"
    ls -al /dev/serial*

    # exit 3
    echo "- Arret forcé du processus ${PID}"
    kill -9 ${PID}
fi

# If Zigate type is "PI", let's check if "WiringPi" is properly installed
if [ "${TYPE}" == "PI" ] || [ "${TYPE}" == "PIv2" ]; then
    # # 'gpio' commands are provided from "WiringPi" package (or equivalent)
    # echo "${PREFIX}Vérification de l'installation du package 'WiringPi'"
    # command -v gpio >/dev/null 2>&1
    # if [ $? -ne 0 ]; then
    #     echo "${PREFIX}= ERREUR: Commande 'gpio' manquante !"
    #     echo "${PREFIX}=         Le package 'WiringPi' est probablement mal installé."
    #     exit 4
    # fi
    # echo "= Ok"

    # Note: Do not perform PiZigate reset unless you flush messages sent
    #       on startup.
    echo "${PREFIX}- Configuration des GPIOs"
    if [ ${GPIOLIB} == "WiringPi" ]; then
        gpio mode 0 out; gpio mode 2 out; gpio write 2 1; gpio write 0 1
        READ2=`gpio read 2`
        READ0=`gpio read 0`
        if [ ${READ2} -ne 1 ] || [ ${READ0} -ne 1 ]; then
            echo "${PREFIX}= ERREUR: Votre package WiringPi ne semble pas fonctionnel.";
            exit 5
        fi
        echo "${PREFIX}= Ok"
    elif [ ${GPIOLIB} == "PiGpio" ]; then
        python3 ${ABEILLEROOT}/core/scripts/resetPiZigate.py
    fi

    echo "${PREFIX}- Configuration du port série"
    stty -F ${PORT} speed 115200 cs8 -parenb -cstopb -echo raw >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "${PREFIX}= ERREUR: Etes vous sur que l'UART associée est active ?";
        sudo cat /proc/tty/driver/serial
        exit 6
    fi
    echo "${PREFIX}= Ok"

elif [ "${TYPE}" == "WIFI" ]; then
    echo "${PREFIX}= ERREUR: Type 'WIFI' inattendu ici."
    exit 6
fi

exit 0
