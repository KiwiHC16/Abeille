#! /bin/bash

###
### Check TTY port and proper access to Zigate.
### WARNING: This script expects that daemon is stoppped to
###          not disturb Zigate answer.
###

echo "Execution de '$(basename $0)'"

if [ $# -lt 1 ]; then
    echo "= ERREUR: Port manquant !"
    exit 1
fi
PORT=$1

echo "Vérifications du port '${PORT}'"

# Port exists ?
if [ ! -e ${PORT} ]; then
    echo "= ERREUR: Le port ${PORT} n'existe pas !"
    exit 2
fi

# Is port already used ?
FIELDS=`lsof -Fcn ${PORT}`
if [ "${FIELDS}" == "" ]; then
    echo "= Ok, le port semble libre."
else
    CMD=""
    for f in ${FIELDS};
    do
        if [[ "$f" != "c"* ]]; then
            continue
        fi
        CMD=${f:1}
    done
    echo "= ERREUR: Port utilisé par la commande '${CMD}'."
    echo "=         Le port doit être libéré pour permettre le dialogue avec la Zigate."
    exit 3
fi

# Zigate communication check now done from PHP
exit 0

# echo "Configuration du port ${PORT}"
# stty -F ${PORT} 115200 -parity cs8 -cstopb
# if [ $? -ne 0 ]; then
    # echo "= ERREUR: Mauvais port ?"
    # exit 2
# fi
# echo "= Ok"

# Zigate reminder: START=0x01 MsgType2B Len2B Chksm Data LQI STOP=0x03
#   Get version: code 0x0010
#   Trame  : 0x01   0010     0000 10 03
#   Encoded: 0x01 021010 02100210 10 03
GETVERSION='\x01\x02\x10\x10\x02\x10\x02\x10\x10\x03'
GETVERSIONEXPECTED='\x01\x80\x02\x10\x02\x10\x02\x15'

echo "Contact de la Zigate"
exec 99<>${PORT}
echo -ne ${GETVERSION} >&99
read -n 8 -t 2 ANSWER <&99
if [ $? -ne 0 ]; then
    echo "= ERREUR: Pas de réponse. Mauvais port ?"
    exit 3
fi
ANSWER2=`echo -n "${ANSWER}" | od -An -t x1`
echo "- Reçu ${#ANSWER} bytes:${ANSWER2}"
ANSWER3=""
for C in ${ANSWER2}
do
    ANSWER3="${ANSWER3}\\x$C"
done
if [ "${ANSWER3}" != "${GETVERSIONEXPECTED}" ]; then
    echo "= ERROR: Mauvaise réponse reçue de la Zigate."
    exit 4
fi
echo "= Ok. Bonne réponse reçue de la Zigate"

exit 0


# echo -ne '\x01\x02\x10\x10\x02\x10\x02\x10\x10\x03' > /dev/ttyS1
# orangepi@TestsBox:$ od -t x1 /dev/ttyS1
# 0000000 01 80 02 10 02 10 02 15 95 02 10 02 10 02 10 10
# 0000020 02 10 03 01 80 10 02 10 02 15 89 02 10 02 13 02
# 0000040 13 1c 02 10 03
#
#     01
#     80 02 10 => 80 00, STATUS sucess
#     02 10 02 15 => 00 05
#     95 => 95, CHKSM
#     02 10 02 10 02 10 10 => 00000010
#     02 10 => 00 RSSI
#     03
#
#     01
#     80 10 => 8010,
#     02 10 02 15 => 0005
#     89 => 89, CHKSM
#     02 10 02 13 => 0003
#     02 13 1c => 031c
#     02 10 => 00, RSSI
#     03
