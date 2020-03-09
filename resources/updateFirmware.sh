
PROG=/var/www/html/plugins/Abeille/Zigate_Module/JennicModuleProgrammerRPI3
FW_DIR=/var/www/html/plugins/Abeille/Zigate_Module

# Qq tests preliminaires
error=0
if [ $# -lt 2 ]; then
    echo "ERREUR: Argument(s) manquant(s) !"
    echo "        updateFirmware.sh <fwfile> <zigateport>"
    error=1
fi
if [ ! -e ${FW_DIR}/$1 ]; then
    echo "ERREUR: le FW choisi n'existe pas !"
    echo "        FW: $1"
    error=1
fi
if [ ! -e $2 ]; then
    echo "ERREUR: le port tty choisi n'existe pas !"
    echo "        Port: $2"
    error=1
fi
hash gpio 2>/dev/null
if [ $? -ne 0 ]; then
    echo "ERREUR: Commande 'gpio' manquante !"
    echo "        Le package WiringPi est probablement mal install�."
    error=1
fi
if [ ! -e ${PROG} ]; then
    echo "ERREUR: Programmateur Jennic manquant !"
    echo "        ${PROG}"
    error=1
fi
if [ $error != 0 ]; then
    exit 1
fi

echo "Lancement de la programmation du firmware"
echo " - Firmware: $1"
echo " - Port tty: $2"

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
    echo " = ERREUR: Programmation abandonn�e"
    exit 2
fi
echo " - Programmation faite"

echo "Redemarrage de la PiZiGate"

# gpio mode 0 out
# gpio mode 2 out

# Passage en mode 'prod'
gpio write 2 1

sleep 1

gpio write 0 0

sleep 1

gpio write 0 1

echo " = Fin"
