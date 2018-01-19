# MQTT, un protocole de messages entre machines

## Présentation

Ce plugin permet de se connecter à un broker Mosquitto et de récupérer les messages publiés.

Il permet également de publier des messages sur ce même Mosquitto.

MQTT est un protocole standard de communication sur IP. Il est disponible sur les Arduino et ESP8266 mais également utilisé par les plus grosses structures (architectures de messagerie entre applications MQSeries, RabbitMQ ...)

Les produits capables de parler en MQTT sont ainsi connectés à Jeedom.

### Configuration du plugin

La configuration générale du plugin propose les paramètres :

  - Adresse du broker Mosquitto (peut être présent ailleurs sur le réseau)

  - Port du serveur Mosquitto (1883 par défaut)

  - Identifiant de Jeedom avec lequel il publiera sur le broker
  Il est possible d'ajouter un compte et mot de passe si la connexion le requiert.

  - QoS à utiliser (par défaut 1).

Toute sauvegarde de la configuration provoque une relance du cron du plugin (et donc un rechargement de la configuration)

Ensuite les équipements se retrouvent dans la section MQTT

Jeedom créera automatiquement toute information publiée sur Mosquitto. Le dernier élément du sujet MQTT est utilisé en tant qu'identifiant d'information. Le reste constitue un équipement.

  Exemple : le sujet MQTT sensors/salon2/temp deviendra un équipement sensors/salon2 et temp une information. La valeur lui sera associée.

  Si on publie ensuite sur sensors/salon2/hum l'information sera ajoutée

Si le payload est de type json, alors un équipement prenant le nom du topic et avec une commande info par élément du json sera créée.

  Exemple : le sujet MQTT sensors/salon2/capteur avec {"temp":"23","hum":"23"} deviendra un équipement sensors/salon2/capteur et avec les informations temp et hum.

Pour publier sur MQTT, il suffit d'ajouter une commande et de saisir le sujet et la valeur du message.

## FAQ

>Est-ce que ca rend Jeedom disponible comme broker MQTT ?

Non, Jeedom utilise Mosquitto en tant que broker. Si vous avez donner les droits root à Jeedom, l'installation est faite de Mosquitto et la lib PHP-Mosquitto.

>Est-ce que je peux utiliser des ESP8266 flashés avec ESPEasy avec ce plugin MQTT ?

Oui, il faut configurer les ESP pour utiliser un controleur configuré en "OpenHAB" dans ESPeasy. Ainsi il créera des topics par GPIO et valeur en payload

>Est-ce que je peux interconnecter d'autres systèmes en MQTT ?

Oui, il est possible par exemple d'utiliser un connecteur pour MPD ou Kodi, voir ce post avec un exemple :
https://www.jeedom.com/forum/viewtopic.php?f=96&t=15298&p=281455&hilit=MQTT+MPD#p280908

>Mosquitto n'est pas installé sur mon système

Vous pouvez l'installer avec :

  sudo apt-get install mosquitto

A noter qu'un Mosquitto installé sur un système différent suffit

>La lib PHP-Mosquitto n'est pas installée sur mon système

Vous pouvez l'installer avec :

  sudo apt-get install libmosquitto-dev

  sudo pecl install Mosquitto-alpha


## Changelog

[Voir la page dédiée](changelog.md)
