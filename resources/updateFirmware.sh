#! /bin/bash

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)'"

PROG=/var/www/html/plugins/Abeille/Zigate_Module/JennicModuleProgrammerRPI3
FW_DIR=/var/www/html/plugins/Abeille/Zigate_Module

# Qq tests preliminaires
echo "Vérifications préliminaires"
error=0
if [ $# -lt 2 ]; then
    echo "= ERREUR: Argument(s) manquant(s) !"
    echo "=         updateFirmware.sh <fwfile> <zigateport>"
    error=1
fi
if [ ! -e ${FW_DIR}/$1 ]; then
    echo "= ERREUR: le FW choisi n'existe pas !"
    echo "=         FW: $1"
    error=1
fi
if [ ! -e $2 ]; then
    echo "= ERREUR: le port tty choisi n'existe pas !"
    echo "=         Port: $2"
    error=1
fi
hash gpio 2>/dev/null
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'gpio' manquante !"
    echo "=         Le package WiringPi est probablement mal installé."
    error=1
fi
if [ ! -e ${PROG} ]; then
    echo "= ERREUR: Programmateur Jennic manquant !"
    echo "=         ${PROG}"
    error=1
fi
if [ ! -x ${PROG} ]; then
    echo "= ERREUR: Le programmateur Jennic n'est pas exécutable !"
    echo "=         ${PROG}"
    error=1
fi
if [ $error != 0 ]; then
    exit 1
fi
echo "= Ok"

# Si check seulement on quitte ici
if [ $# -gt 2 ] && [ $3 == "-check" ]; then
    exit 0
fi

echo "Lancement de la programmation du firmware"
echo "- Firmware: $1"
echo "- Port tty: $2"

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

sudo ${PROG} -V 6 -P 115200 -v -f ${FW_DIR}/$1 -s $2 2>&1
if [ $? != 0 ]; then
    echo "- ERREUR: Programmation impossible"
    status=2
else
    echo "- Ok. Programmation faite"
    echo "Redémarrage de la PiZiGate"
    status=0
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
