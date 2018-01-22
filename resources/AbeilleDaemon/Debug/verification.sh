
ls -l /dev/ttyUSB0
crw-rw---- 1 root dialout 188, 0 janv. 22 00:00 /dev/ttyUSB0

cat /etc/group | grep dialout | grep www-data
dialout:x:20:www-data


Mosquitto
mosquitto_sub -h localhost -i AbeilleSubVerif -p 1883 -t "Abeille/Verif" -u jeedom -P jeedom
mosquitto_pub -h localhost -i AbeillePubVerif -t "Abeille/Verif" -m "C est moi" -u jeedom -P jeedom

fuser /dev/ttyUSB0 -> pid -> ps -ef | grep pid -> www-data  9744  8497  0 00:13 pts/0    00:00:00 grep 24098
www-data 24098     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleSerialRead.php

Verifier la presence du fichier pipe
ls -l /var/www/html/plugins/Abeille/resources/AbeilleDaemon/input
prwxr-xr-x 1 www-data www-data 0 janv. 22 00:15 /var/www/html/plugins/Abeille/resources/AbeilleDaemon/input

fuser input
/var/www/html/plugins/Abeille/resources/AbeilleDaemon/input: 16802 16804 24098 24100

fuser input
/var/www/html/plugins/Abeille/resources/AbeilleDaemon/input: 16802 16804 24098 24100
www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 16802
www-data 10046  8497  0 00:17 pts/0    00:00:00 grep 16802
www-data 16802     1 99 janv.21 ?      05:59:53 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleSerialRead.php /dev/ttyUSB0 /dev/ttyUSB0.log
www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 16804
www-data 10048  8497  0 00:17 pts/0    00:00:00 grep 16804
www-data 16804     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleParser.php
www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$
www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$
www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 24098
www-data 10050  8497  0 00:17 pts/0    00:00:00 grep 24098
www-data 24098     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleSerialRead.php
www-data@Abeille:~/html/plugins/Abeille/resources/AbeilleDaemon$ ps -ef | grep 24100
www-data 10107  8497  0 00:18 pts/0    00:00:00 grep 24100
www-data 24100     1  0 janv.21 ?      00:00:00 /usr/bin/php /var/www/html/plugins/Abeille/resources/AbeilleDaemon/AbeilleParser.php


Envoyer une requete Version sur zigate au niveau /dev/ttyUSB0 et verifier la reponse sur AbeilleParser.php

Faire generer à AbeilleParser un message MQTT et verifier qu'on le recoit

Envoyer a Mosquitto un demande de version et verifier qu on a un reponse amosquitto

Envoyer par jeedom un message a mosquitto et verifier qu on le recoit

envoyer un messaege de jeedom a mosquitto et verifier qu il revient

Envoyer une demande de version a aigate et verifier qu il revient

'