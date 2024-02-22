#! /bin/bash
# Note: This script is launched from Abeille/core/ajax directory
# Note: The port must NOT be used during this script execution. This is NOT CHECKED so far.

ABEILLEROOT=${PWD}/../..
DK6PROG=${ABEILLEROOT}/resources/DK6Programmer

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)'"
echo "Démarrage de '$(basename $0)'"

# Checking arguments
if [ $# -lt 4 ]; then
    echo "= ERREUR: Argument(s) manquant(s) !"
    echo "=         dumpZigate.sh <zigatePort> <zigateType> <gpioLib> <dumpFile>"
    exit 1
fi
ZGPORT=$1
ZGTYPE=$2
GPIOLIB=$3
DUMPFILE=$4
if [ ${ZGTYPE} != "PI" ] && [ ${ZGTYPE} != "PIv2" ]; then
    echo "= ERREUR: Type de Zigate invalide ! (PIv2 seulement)"
    echo "=         dumpZigate.sh <zigatePort> <zigateType> <gpioLib> <dumpFile>"
    exit 1
fi
ERROR=0
if [ ${GPIOLIB} == "WiringPi" ]; then
    command -v gpio >/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Commande 'gpio' manquante ou non exécutable !"
        echo "=         Le package WiringPi est probablement mal installé."
        ERROR=1
    fi
fi
if [ ! -e ${ZGPORT} ]; then
    echo "= ERREUR: Le port ${ZGPORT} n'existe pas !"
    ERROR=1
fi

if [ $ERROR -ne 0 ]; then
    exit $ERROR
fi

# Quick check that Zigate port is free
FIELDS=`sudo lsof -Fcn ${ZGPORT}`
if [ "${FIELDS}" != "" ]; then
    echo "= ERREUR: Le port ${ZGPORT} ne semble pas libre. Assurez vous que rien ne l'utilise !"
    exit 2
fi

# PiZigate reminder
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1
if [ ${ZGTYPE} == "PI" ] || [ ${ZGTYPE} == "PIv2" ]; then
    echo "Configuring PI Zigate GPIOs for flash mode"
    if [ ${GPIOLIB} == "WiringPi" ]; then
        gpio mode 0 out
        gpio mode 2 out

        # Passage en mode 'flash'
        gpio write 2 0
        sleep 1
        gpio write 0 0
        sleep 1
        gpio write 0 1
        sleep 1
    elif [ ${GPIOLIB} == "PiGpio" ]; then
        python3 ${ABEILLEROOT}/core/scripts/pizigateModeFlash.py
    fi
fi

echo "Dumping Zigate content"
echo "- Port tty: ${ZGPORT}"
echo "- Type    : ${ZGTYPE}"
echo "- Lib GPIO: ${GPIOLIB}"
echo "- File    : ${DUMPFILE}"
#echo "  Prog    : ${PROG}"
echo

if [ ${ZGTYPE} == "PI" ]; then
    # JN5168: Flash appli from 0x00080000 to 0x000C0000
    # For test only. DK6 seems to not support JN5168
    ${DK6PROG} -V 3 -s ${ZGPORT} -P 115200 -d FLASH:0xC0000@0x80000=${DUMPFILE}
elif [ ${ZGTYPE} == "PIv2" ]; then
    # JN5189: Flash area (640KB) from 0x0000_0000 to 0x0009_FFFF
    ${DK6PROG} -V 3 -s ${ZGPORT} -P 115200 -d FLASH:0x9FFFF@0=${DUMPFILE}
fi
ERROR=$?

# Switch back to 'prod' mode
if [ ${ZGTYPE} == "PI" ] || [ ${ZGTYPE} == "PIv2" ]; then
    echo "Restoring PI Zigate GPIOs config for prod mode"
    if [ ${GPIOLIB} == "WiringPi" ]; then
        gpio write 2 1
        sleep 1
        gpio write 0 0
        sleep 1
        gpio write 0 1
    elif [ ${GPIOLIB} == "PiGpio" ]; then
        python3 ${ABEILLEROOT}/core/scripts/resetPiZigate.py
    fi
fi

# Final status
if [ $ERROR -eq 0 ]; then
    echo "= Tout s'est bien passé. Vous pouvez fermer ce log."
else
    echo "= ATTENTION !!! "
    echo "= Quelque chose s'est mal passé. Veuillez vérifier le log ci-dessus."
fi
exit $ERROR
