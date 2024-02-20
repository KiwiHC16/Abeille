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
if [ ${ZGTYPE} != "PIv2" ]; then
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
if [ $ERROR -ne 0 ]; then
    exit $ERROR
fi

echo "Dumping Zigate content"
echo "  Port tty: ${ZGPORT}"
echo "  Type    : ${ZGTYPE}"
echo "  Lib GPIO: ${GPIOLIB}"
echo "  File    : ${DUMPFILE}"
#echo "  Prog    : ${PROG}"

# PiZigate reminder
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1
if [ ${ZGTYPE} == "PI" ] || [ ${ZGTYPE} == "PIv2" ]; then
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

if [ ${ZGTYPE} == "PIv2" ]; then
    ${DK6PROG} -V 3 -s ${ZGPORT} -P 500000 -d FLASH:0x9FFFF@0=${DUMPFILE}
fi
ERROR=$?

# Switch back to 'prod' mode
if [ ${ZGTYPE} == "PI" ] || [ ${ZGTYPE} == "PIv2" ]; then
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
