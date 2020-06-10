#! /bin/bash

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)'"

echo "Vérification de l'installation WiringPi"
hash gpio 2>/dev/null
if [ $? -ne 0 ]; then
    echo "= ERREUR: Commande 'gpio' manquante !"
    echo "=         Le package WiringPi est probablement mal installé."
    exit 1
fi
echo "= Ok"

echo "Redémarrage de la PiZiGate"

# Memo connexion PiZiGate
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1

gpio mode 0 out
gpio mode 2 out
gpio write 2 1

gpio write 0 0
sleep 1
gpio write 0 1

echo "= Ok. Vous pouvez fermer cette fenêtre de log."
exit 0
