#!/usr/bin/env bash

MOSQUITTO_SUB=$(which mosquitto_sub)
MOSQUITTO_PUB=$(which mosquitto_pub)
MOSQUITTO_BIN=$(which mosquitto)
HOST=localhost
IDENTSUB="AbeilleSubVerif"
IDENTPUB="AbeillePubVerif"
PORT=1883

USER="jeedom"
PASS="jeedom"

FIFO=/tmp/AbeilleDeamonInput
MLOG=/var/log/mosquitto/mosquitto.log




## Zigate tests
KEY="OK, au moins un port USB found sur lequel peut être la zigate."
[[ -e $(ls -l /dev/ttyUSB* ) ]] && KEY="WARN: Missing Zigate USB Key"
echo $KEY
ls -l /dev/ttyUSB*

GRP="OK, www-data belongs to dialout"
[[ $(groups www-data | grep -c dialout ) -eq 0 ]] && GRP="ERROR: www-data does not belong to dial-out"
echo $GRP


## Up Link
#Verifier la presence du fichier pipe
PIPE="ERROR, File $FIFO was not found"
[[ -e ${FIFO} ]] && PIPE="OK, File $FIFO was found"
echo $PIPE

## les demons qui tournent
DEMON_NB="Ok, 4 demons tournent"
[[  $(ps -ef | grep "plugins/Abeille" | grep -v grep | wc -l) -ne 4 ]] && DEMON_NB="Error, nous n'avons pas nos 4 demons qui tournent"
echo $DEMON


## MQTT
MSQTT="OK, mosquitto_sub programm found"
[[ -z $(which mosquitto_sub) ]] && MQSTT="ERROR: mosquitto_sub programm not found"
echo $MSQTT

MSQTT="OK, mosquitto_pub programm found"
[[ -z $(which mosquitto_pub) ]] && MQSTT="ERROR: mosquitto_pub programm not found"
echo $MSQTT



MSQTT="OK, Service mosquitto is running"
[[ $(/etc/init.d/mosquitto status | egrep -ic "not|fail") -ne 0 ]] && MQSTT="ERROR, mosquitto service is not running"
echo $MQSTT



##---------------------------------------------------------------------------------------------
echo -e "\nChecking connection to mosquitto"

TOPIC="Abeille/Verif"
MESSAGE="c est moi, timestamp: $(date +%s)"

[[ -f sub1.log ]] && rm sub1.log

## nohup $MOSQUITTO_SUB -h $HOST -i $IDENTSUB -p $PORT -t $TOPIC -u $USER -P $PASS -q 0 -C 1 >sub1.log 2>&1 &
nohup  mosquitto_sub -h localhost -i tutu -p 1883 -t $TOPIC -u jeedom -P jeedom -q 0 -C 1 >sub1.log 2>&1 &
SUBPID=$!

sleep 3

$MOSQUITTO_PUB -h $HOST -i $IDENTPUB -t $TOPIC -u $USER -P $PASS -m "$MESSAGE" -q 0 -d

n=5
while [[ $n -gt 0 ]]
    do
        cat sub1.log
        sleep 1
        echo $n seconds
        let n--
        [[  $(wc -l < sub1.log) -gt 0 ]] && break
    done;

MSG="ERROR, message was improperly transmitted or not at all"
[[ $(wc -l < sub1.log) -eq 1 ]] && [[ $(cat sub1.log) == "$MESSAGE" ]] && MSG="OK, message was properly transmitted"
echo "$MSG // expected: $MESSAGE //transmitted: $(cat sub1.log) "

##---------------------------------------------------------------------------------------------
## Test lien mosquitto->zigate->mosquitto

echo -e "\nChecking connection mosquitto->zigate->mosquitto"
[[ -f sub2.log ]] && rm sub2.log

TOPIC="Abeille/0000/SW-SDK"
##nohup $MOSQUITTO_SUB -h $HOST -i $IDENTSUB -p $PORT -t $TOPIC -u $USER -P $PASS -q 0 -C 1 >sub.log 2>&1 &
nohup  mosquitto_sub -h localhost -i toto -p 1883 -t $TOPIC -u jeedom -P jeedom -q 0 -C 1 >sub2.log 2>&1 &
SUBPID=$!

sleep 3

TOPIC="CmdAbeille/0000/getZgVersion"
MESSAGE="Version"
$MOSQUITTO_PUB -h $HOST -i $IDENTPUB -t $TOPIC -u $USER -P $PASS -m "$MESSAGE" -q 0 -d

n=5
while [[ $n -gt 0 ]]
    do
        cat sub2.log
        sleep 1
        echo $n seconds
        let n--
        [[  $(wc -l < sub2.log) -gt 0 ]] && break
    done;

MSG="ERROR, on ne parvient pas a dialoguer avec la zigate"
[[ $(wc -l < sub2.log) -eq 1 ]] && [[ $(cat sub2.log) == "030D" ]] && MSG="OK, la zigate repond"
echo "$MSG // zigate version expected: 030D // zigate version received: $(cat sub2.log) "

##---------------------------------------------------------------------------------------------


## On ne peut pas faire le tail sur le log, on n'a pas les droits
##echo -e "\n Mosquitto log"
##tail -5 /var/log/mosquitto/mosquitto.log

##---------------------------------------------------------------------------------------------
## As 1 msg should have received, the sub should have terminate itself.
##MSG="OK, no remaining $MOSQUITTO_SUB to kill"
##[[ ! -z $(ps h -o pid -p $SUBPID) ]] && MSG="ERROR, killing remaining $MOSQUITTO_SUB PID $SUBPID" && kill $SUBPID
##echo $MSG

## COMMENT: transmission aleatoire du message, a investiguer.....
##---------------------------------------------------------------------------------------------

##
##
echo "Qui utilise les ports ttyUSB*"
fuser /dev/ttyUSB*
echo "Qui utilise les pipe"
fuser $FIFO
echo "Liste des processus appartenant a www-data"
ps -ef | grep www-data



#
#Envoyer a Mosquitto un demande de version et verifier qu on a un reponse amosquitto
#
#Envoyer par jeedom un message a mosquitto et verifier qu on le recoit
#
#envoyer un messaege de jeedom a mosquitto et verifier qu il revient
