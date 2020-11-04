#####################
Installation PiZiGate
#####################

L'installation de la PiZiGate demande des actions un peu spécifiques.

****
GPIO
****

Il faut installer le software qui va piloter les GPIO. Pour se faire vous avez le bouton: "Installation de Wiring Pi".
Cela va permettre plusieurs choses. Reset par le HW de la PiZiGate, programmation de la PiZiGate directement sur le RPI (ssans PC externe et autre).


**********
Port Serie
**********

Il faut rendre disponible le port série du RPI, si vous souhaitez le faire manuellement suivre les instructions suivantes : https://spellfoundry.com/2016/05/29/configuring-gpio-serial-port-raspbian-jessie-including-pi-3/
Sinon Abeille inclus des scripts pour le faire.
Il y a des differences entre les version de RPI sur le hardware qui controle les ports série.
Pour le RPI2, le port est /dev/ttyAMA0 qui doit être dispo sans action particulière.
Pour le RPI3, le port est /dev/ttyS0 qui n'est pas dispo par defaut. D'ou la necessité de l'activer (le rendre dispo pour la PiZiGate).

Ces actions demandent un reboot du RPI.

*********
Lancement
*********

Une fois les deux étapes précédentes réalisées avec succes, il faut redemarrer le RPI si pas déjà fait apres l'étape "Port Série", choisir le port "/dev/ttyS0" dans la configuration Abeille et relancer le demon.

**********
Programmer
**********

Il est possible de programmer la PiZiGate avec le dernier firmware directement depuis Jeedom sans toucher à la PiZiGate. Pour ce faire utiliser le bouton: Programmer.

*****
Reset
*****

Il est possible de faire un reset "forcé" depuis Jeedom de la ZiGate si celle ci ne repond plus aux commandes avec le bouton "Reset" (on peut aussi demander un reset avec le commandes: bouton reset sur l équipement ruche).


**************************
Super doc faite par @Gnaag
**************************

https://jeedomiser.fr/article/pilotez-vos-appareils-zigbee-avec-la-pizigate/
