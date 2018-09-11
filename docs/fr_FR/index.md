Présentation de Abeille
=======================

(Tentative de portage sur le systeme de doc de jeedom).

Introduction
------------

Ce plugin Jeedom permet de connecter un réseau ZigBee au travers de la passerelle ZiGate. C’est une toute premiere version qui demande encore pas mal de travail.

Il permet déjà:

* de connecter les ampoules IKEA et la série Xiaomi Zigbee (Sensor presence, prise, temperature, humidité, pression, interrupteur, porte).

* de faire les inclusions des equipments zigbee depuis jeedom

* creation automatique des objets MQTT pour les remontées d’informations (ensuite il faut créer à la main les commande d’action)

* d’avoir l’état de l ampoule IKEA, son niveau de brillance, ses caractéristiques (nom, fabriquant, SW level).

* de commander les ampoules une par une (On/Off, niveau)

* d’avoir l’état de la prise Xiaomi avec la puissance et la consommation (Nom et Fabriquant)

* d’avoir les temperatures, humidité, pression Xiaomi, son nom, tension batterie

* d’avoir la remontée d’une presence (capteur infrarouge xiaomi)

* d’avoir la remontée d’ouverture de porte

* d’avoir les clics sur les interrupteurs (1, 2, 3 ou 4 clics)

* de définir des groupes comprenant des ampoules IKEA et prise xiaomi (Je peux donc avoir un mix dans le même groupe qui est commandé par une télécommande IKEA par exemple, ou faire un va et vient sur des ampoules IKEA avec 2 télécommandes IKEA (ce qui n’est pas possible avec la solution pure IKEA),…​)

Il peut être nécessaire de programmer la ZiGate comme expliqué sur le site ZiGate: http://zigate.fr/wiki/mise-a-jour-de-la-zigate/ avec le firmware Abeille.

Ce que l’on peut faire
-------------------

Exemples:
Si j’appuie sur l’interrupteur Xiaomi, avec un scenario Jeedom, j’allume l’ampoule IKEA.

Je contrôle chaque équipement depuis Jeedom. Et surtout, je profite du « mesh » ZigBee (des ampoules IKEA et prise Xiaomi) car je vous confirme que les prises Xiaomi et les ampoules IKEA font le routage des messages ZigBee.