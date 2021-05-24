#! /bin/bash
# Zigate-DIN flash programmation script
# updateFirmwareDIN.sh <action> <zigateport> [fwfile]
#   where action = flash, check, eraseeeprom

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)' $@"
echo "Démarrage de '$(basename $0)' $@"

# Note: Startup directory is the one from the caller (ajax)
#       It is then '/var/www/html/plugins/Abeille/core/ajax'
PROG=${PWD}/../../tmp/JennicModuleProgrammer
BUILD_DIR=${PWD}/../../resources/prog_jennic-0.7/build
FW_DIR=${PWD}/../../resources/fw_zigate
DIN_SCRIPT=${PWD}/../scripts/flash_ZiGate-DIN.py

# Qq tests preliminaires
echo "Vérifications préliminaires"
error=0
if [ $# -lt 2 ]; then
    echo "= ERREUR: Argument(s) manquant(s) !"
    echo "=         updateFirmwareDIN.sh <action> <zigateport> [fwfile]"
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
    command -v python3 >/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERREUR: 'python3' manquant ou non exécutable !"
        echo "=         Le package 'python3' est probablement mal installé."
        error=1
    fi
    if [ ! -e ${PROG} ]; then
        # Compiling Jennic programmer since v0.7
        echo "Compilation du programmateur"
        pushd ${BUILD_DIR} >/dev/null
        sudo make
        if [ $? -ne 0 ]; then
            echo "= ERREUR: Compilation ratée !"
            error=1
        fi
        # echo "= ERREUR: Programmateur Jennic manquant !"
        # echo "=         ${PROG}"
        # error=1
        popd >/dev/null
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
# elif [ ${ACTION} == "eraseeeprom" ]; then
#     echo "Effacement de l'EEPROM"
#     echo "- Port tty: ${ZGPORT}"
else
    echo "Lancement de la programmation du firmware"
    echo "- Port tty: ${ZGPORT}"
    echo "- Firmware: ${FW}"
fi

sudo python3 ${DIN_SCRIPT} -s ${ZGPORT} -b 250000 -f ${FW_DIR}/${FW} 2>&1
if [ $status  -eq 0 ]; then
    echo "= Tout s'est bien passé. Vous pouvez fermer ce log."
else
    echo "= ATTENTION !!! "
    echo "= Quelque chose s'est mal passé."
    echo "= Veuillez vérifier le log ci-dessus."
fi
exit $status
