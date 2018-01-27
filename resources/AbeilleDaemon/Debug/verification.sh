#!/usr/bin/env bash

MOSQUITTO_SUB=$(which mosquitto_sub)
MOSQUITTO_PUB=$(which mosquitto_pub)
MOSQUITTO_BIN=$(which mosquitto)
HOST=localhost
IDENTSUB="AbeilleSubVerif"
IDENTPUB="AbeillePubVerif"
PORT=1883
TOPIC='Abeille/Verif'
USER="jeedom1"
PASS="jeedom1"
MESSAGE="c est moi, timestamp: $(date +%s)"
FIFO=/var/www/html/plugins/Abeille/resources/AbeilleDaemon/input
MLOG=/var/log/mosquitto/mosquitto.log

[[ -f sub.log ]] && rm sub.log


KEY="OK, Zigate usb key found"
[[ -e $(ls -l /dev/ttyUSB* ) ]] && KEY="WARN: Missing Zigate USB Key"
echo $KEY


GRP="OK, www-data belongs to dialout"
[[ $(groups www-data | grep -c dialout ) -eq 0 ]] && GRP="ERROR: www-data does not belong to dial-out"
echo $GRP

MSQTT="OK, Mosquitto programm found"
[[ -z $(which mosquitto) ]] && MQSTT="ERROR: mosquitto programm not found"
echo $MSQTT

MSQTT="OK, Service mosquitto is running"
[[ $(/etc/init.d/mosquitto status | egrep -ic "not|fail") -ne 0 ]] && MQSTT="ERROR, mosquitto service is not running"
echo $MQSTT

#Verifier la presence du fichier pipe
PIPE="ERROR, File $FIFO was not found"
[[ -e ${FIFO} ]] && PIPE="OK, File $FIFO was found"
echo $PIPE


echo -e "\nChecking connection to mosquitto"


#Mosquitto
#mosquitto_sub -h localhost -i AbeilleSubVerif -p 1883 -t "Abeille/Verif" -u jeedom -P jeedom
nohup $MOSQUITTO_SUB -h $HOST -i $IDENTSUB -p $PORT -t $TOPIC -u $USER -P $PASS -q 1 -C 1 >sub.log 2>&1 &
SUBPID=$!

#mosquitto_pub -h localhost -i AbeillePubVerif -t "Abeille/Verif" -m "C est moi" -u jeedom -P jeedom
$MOSQUITTO_PUB -h $HOST -i $IDENTPUB -t $TOPIC -u $USER -P $PASS -m "$MESSAGE" -q 1 -d

n=5
while [[ $n -gt 0 ]]
    do
        cat sub.log
        sleep 1
        echo $n seconds
        let n--
        [[  $(wc -l < sub.log) -gt 0 ]] && break
    done;

MSG="ERROR, message was improperly transmitted or not at all"
[[ $(wc -l < sub.log) -eq 1 ]] && [[ $(cat sub.log) == "$MESSAGE" ]] && MSG="OK, message was properly transmitted"
echo "$MSG // expected: $MESSAGE //transmitted: $(cat sub.log) "

echo -e "\n Mosquitto log"
tail -5 /var/log/mosquitto/mosquitto.log

## As 1 msg should have received, the sub should have terminate itself.
MSG="OK, no remaining $MOSQUITTO_SUB to kill"
[[ ! -z $(ps h -o pid -p $SUBPID) ]] && MSG="ERROR, killing remaining $MOSQUITTO_SUB PID $SUBPID" && kill $SUBPID
echo $MSG

## COMMENT: transmission aleatoire du message, a investiguer.....

#
#fuser /dev/ttyUSB0 -> pid -> ps -ef | grep pid -> www-data  9744  8497  0 00:13 pts/0    00:00:00 grep 24098
#www-data 24098     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleSerialRead.php
#


#prwxr-xr-x 1 www-data www-data 0 janv. 22 00:15 /var/www/html/plugins/Abeille/resources/AbeilleDaemon/input
#
#fuser input
#fuser $FIFO
#/var/www/html/plugins/Abeille/resources/AbeilleDaemon/input: 16802 16804 24098 24100
#
#fuser input
#/var/www/html/plugins/Abeille/resources/AbeilleDaemon/input: 16802 16804 24098 24100
#www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 16802
#www-data 10046  8497  0 00:17 pts/0    00:00:00 grep 16802
#www-data 16802     1 99 janv.21 ?      05:59:53 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleSerialRead.php /dev/ttyUSB0 /dev/ttyUSB0.log
#www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 16804
#www-data 10048  8497  0 00:17 pts/0    00:00:00 grep 16804
#www-data 16804     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleParser.php
#www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$
#www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$
#www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 24098
#www-data 10050  8497  0 00:17 pts/0    00:00:00 grep 24098
#www-data 24098     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleSerialRead.php
#www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 24100
#www-data 10107  8497  0 00:18 pts/0    00:00:00 grep 24100
#www-data 24100     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleParser.php
#
#
#Envoyer une requete Version sur zigate au niveau /dev/ttyUSB0 et verifier la reponse sur AbeilleParser.php
#
#Faire generer à AbeilleParser un message MQTT et verifier qu'on le recoit
#
#Envoyer a Mosquitto un demande de version et verifier qu on a un reponse amosquitto
#
#Envoyer par jeedom un message a mosquitto et verifier qu on le recoit
#
#envoyer un messaege de jeedom a mosquitto et verifier qu il revient
#
#Envoyer une demande de version a aigate et verifier qu il revient
