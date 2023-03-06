#! /bin/bash

###
### Wait for given port to be free.
###

# Usage
# waitPortFree.sh <portname> <timeout>
# ex: waitPortFree.sh /dev/ttyS1 2

PREFIX=""

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
# echo "[${NOW}] Démarrage de '$(basename $0)'"
echo "${PREFIX}Démarrage de '$(basename $0)'"

if [ $# -lt 2 ]; then
    echo "${PREFIX}= ERREUR: Port et/ou timeout manquant !"
    exit 1
fi
PORT=$1
TIMEOUT=$2

echo "${PREFIX}Vérifications du port '${PORT}'"

# Port exists ?
if [ ! -e ${PORT} ]; then
    echo "${PREFIX}= ERREUR: Le port ${PORT} n'existe pas !"
    exit 2
fi

# Is port already used ?
ISFREE=0
for (( T=0; T<${TIMEOUT}; T=$((T+1)) ))
do
    FIELDS=`sudo lsof -Fcn ${PORT}`
    if [ "${FIELDS}" == "" ]; then
        echo "${PREFIX}= Ok, le port semble libre."
        ISFREE=1
        break
    fi
    echo "- IN USE: ${FIELDS}"
    sleep 1
done

if [ ${ISFREE} -ne 1 ]; then
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
    exit 3
fi

exit 0
