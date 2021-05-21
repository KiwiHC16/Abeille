#! /bin/bash
# PiZigate flash programmation script
# updateFirmware.sh <action> <zigateport> [fwfile]
#   where action = flash, check, eraseeeprom

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)' $@"
echo "Démarrage de '$(basename $0)' $@"

# Note: Startup directory is the one from the caller (ajax)
#       It is then '/var/www/html/plugins/Abeille/core/ajax'
PROG=${PWD}/../../resources/prog_jennic/JennicModuleProgrammerRPI3
FW_DIR=${PWD}/../../resources/fw_zigate

# Qq tests preliminaires
echo "Vérifications préliminaires"
error=0
if [ $# -lt 2 ]; then
    echo "= ERREUR: Argument(s) manquant(s) !"
    echo "=         updateFirmware.sh <action> <zigateport> [fwfile]"
    error=1
fi
ACTION=$1
ZGPORT=$2
FW=""
if [ ${ACTION} != "flash" ] && [ ${ACTION} != "check" ] && [ ${ACTION} != "eraseeeprom" ]; then
    echo "= ERREUR: Action '${ACTION}' non supportée."
    echo "=         Choix=flash/check/eraseeeprom"
    error=1
else
    if [ ${ACTION} == "flash" ]; then
        FW=$3
        if [ ! -e ${FW_DIR}/${FW} ]; then
            echo "= ERREUR: le FW choisi n'existe pas !"
            echo "=         FW: ${FW}"
            error=1
        fi
    fi
    if [ ! -e ${ZGPORT} ]; then
        echo "= ERREUR: le port tty choisi n'existe pas !"
        echo "=         Port: ${ZGPORT}"
        error=1
    fi
    command -v gpio >/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERREUR: Commande 'gpio' manquante ou non exécutable !"
        echo "=         Le package WiringPi est probablement mal installé."
        error=1
    fi
    if [ ! -e ${PROG} ]; then
        echo "= ERREUR: Programmateur Jennic manquant !"
        echo "=         ${PROG}"
        error=1
    fi
    if [ ! -x ${PROG} ]; then
        # Attempting to correct execution right
        sudo chmod +x ${PROG} >/dev/null
    fi
    if [ ! -x ${PROG} ]; then
        echo "= ERREUR: Le programmateur Jennic n'est pas exécutable !"
        echo "=         ${PROG}"
        error=1
    fi
fi
if [ $error != 0 ]; then
    exit 1
fi
echo "= Ok"

# Si check seulement on quitte ici
if [ ${ACTION} == "check" ]; then
    exit 0
elif [ ${ACTION} == "eraseeeprom" ]; then
    echo "Effacement de l'EEPROM"
    echo "- Port tty: ${ZGPORT}"
else
    echo "Lancement de la programmation du firmware"
    echo "- Port tty: ${ZGPORT}"
    echo "- Firmware: ${FW}"
fi

# Memo connexion PiZiGate
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1

gpio mode 0 out
gpio mode 2 out

# Passage en mode 'flash'
gpio write 2 0
sleep 1
gpio write 0 0
sleep 1
gpio write 0 1
sleep 1

# gpio write 2 1
# sleep 1

if [ ${ACTION} == "eraseeeprom" ]; then
    sudo ${PROG} -V 6 -P 115200 -v --eraseeeprom -s ${ZGPORT} 2>&1
    if [ $? != 0 ]; then
        echo "= ERREUR: Effacement impossible"
        status=2
    else
        echo "- Ok. Effacement terminé"
        echo "Redémarrage de la PiZiGate"
        status=0
    fi
else
    sudo ${PROG} -V 6 -P 115200 -v -f ${FW_DIR}/${FW} -s ${ZGPORT} 2>&1
    if [ $? != 0 ]; then
        echo "= ERREUR: Programmation impossible"
        status=2
    else
        echo "- Ok. Programmation faite"
        echo "Redémarrage de la PiZiGate"
        status=0
    fi
fi

# gpio mode 0 out
# gpio mode 2 out

# Passage en mode 'prod'
gpio write 2 1
sleep 1
gpio write 0 0
sleep 1
gpio write 0 1

if [ $status  -eq 0 ]; then
    echo "= Tout s'est bien passé. Vous pouvez fermer ce log."
else
    echo "= ATTENTION !!! "
    echo "= Quelque chose s'est mal passé."
    echo "= Veuillez vérifier le log ci-dessus."
fi
exit $status
