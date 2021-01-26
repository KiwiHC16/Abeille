#! /bin/bash

###
### Check Wifi proper communication with given port to Zigate.
### WARNING: This script expects that daemon is stoppped to
###          not disturb Zigate answer.
###

# Usage
# checkWifi.sh <addr:port>
# ex: checkWifi.sh 192.168.1.52:9999

NOW=`date +"%Y-%m-%d %H:%M:%S"`
echo "[${NOW}] Démarrage de '$(basename $0)' $@"

if [ $# -lt 1 ]; then
    echo "= ERREUR: 'Addr:Port' manquant !"
    exit 1
fi
ADDRPORT=$1
ARR_IN=(${ADDRPORT//:/ })
ADDR=${ARR_IN[0]}
PORT=${ARR_IN[1]}
ERROR=0

# Checking IP address validity
echo "Vérification de l'adresse IP '${ADDR}'"
ping -c1 ${ADDR}
if [ $? -ne 0 ]; then
    echo "= ERREUR: Cette adresse ne répond pas."
    ERROR=1
else
    echo "= Ok, une réponse reçue au ping."
fi

echo "Vérification de l'installation du package 'socat'"
hash socat 2>/dev/null
if [ $? -ne 0 ]; then
    echo "= 'socat' ne semble pas installé."

    # Attempting to perform automatic installation
    echo "Installation automatique de 'socat'."
    sudo apt-get -y update
    sudo apt install socat
    if [ $? -ne 0 ]; then
        echo "= ERREUR: L'installation n'a pas aboutie."
        echo "=         Il se peut que le package 'socat' ne soit pas supporté sur votre plateforme."
        ERROR=1
    else
        echo "= Ok, package installé."

        # Retesting 'socat' install
        echo "Vérification finale de 'socat'"
        hash socat 2>/dev/null
        if [ $? -ne 0 ]; then
            echo "= ERREUR: Commande 'socat' manquante !"
            echo "=         Le package 'socat' est probablement mal installé."
            ERROR=1
        else
            echo "= Ok"
        fi
    fi
else
    echo "= Ok"
fi

if [ $ERROR -ne 0 ]; then
    echo "= ERREURS détectées. Voir le log ci-dessus."
    exit 10
fi

echo "= ATTENTION: Test préliminaire !"
echo "=            Ne couvre pas le dialogue jusqu'a la zigate via socat,"
echo "=            ni la lecture de la version firmware."
exit 0
