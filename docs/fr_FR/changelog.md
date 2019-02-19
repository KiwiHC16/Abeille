# Mise à jour

## Version stable Mi Janvier 2019 => Version beta et stable de mi Février 2019

Cette version est en ligne avec le firmware 3.0f de la Zigate
Vous pouvez utiliser un firmware plus vieux mais tout ne fonctionnera pas. (98% fonctionnera)

Comment procéder:
- Mettre à jour le plugin Abeille
- Flasher la Zigate avec le firmware 3.0f (bien faire un erase de l'EEPROM)
- Connecter la Zigate et démarrer le deamon abeille
- démarrer le réseau Zigbee depuis abeille
- mettre la Zigate en inclusion
- inclure vos routeurs en partant des plus proches au plus lointain
- Vérifier que tout fonctionne 
- Inclure les équipements sur piles (dans l'ordre que vous voulez)

## Version stable du 16/11/2018 (ou précédentes) => Version beta et stable jusqu'à Mi Janvier 2019

Cette mise à jour est importante et délicate. Pour facilité l'intégration de nouveaux équipements par la suite une standardisation des modèles doit être faite.
Cela veut dire que tous les modèles changent et que le objets dans Abeille/Jeedom doivent être mis à jour.
Prévoir du temps, avoir bien fait les backup, et prévoir d'avoir à faire quelques manipulations à la main. Les situations rencontrées vont dépendre de l'historique des équipements dans Jeedom.

[red]#!!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!#

### Solution pour les petits systèmes

* Cela suppose que vous aller effacer (objets, historiques,...) toutes les données puis re-créer le réseau.
* supprimer le plug in Abeille
* Installer le plug in Abeille depuis le market (ou github)
* Activer et faire la configuration du plugin
* Démarrer le plugin
* Mettre en mode inclusion
* Appairer les devices.

### Solution pour les gros systèmes

* Si la solution précédente demande trop de travail, on peut faire la mise à jour de la façon suivante. Attention, je ne peux pas tester toutes les combinaisons et des opérations supplémentaires seront certainement nécessaires. 90% aura été fait automatiquement. 
Il n'y a pas de moyen infaillible pour faire la correspondance entre une commande dans un modèle et une commande dans Jeedom. Le lien est fait soit par le nom dans la commande nom ou quand pas disponible par le nom de l'image utilisée pour le device. De même pour les commande le nom est le moyen de faire le lien. Si vous avez fait des changements de nom, les commandes sortiront en erreur et cela demandera de mettre le nom de la commande dans le modèle le temps de la conversion. 
Dans les versions suivantes, nous ne devrions plus avoir ce problème car les commandes auront un Id unique et spécifique.

* Mettre à jour la plugin avec le market (ou github)
* Vérifier la configuration du plugin et démarrer le plugin en mode debug.
* Demander la mise à jour des objets depuis les templates, bouton: "Appliquer nouveaux modèles"
* 90% des objets devraient être à jour maintenant.
* Tester vos équipements.

* Si un équipement ne fonctionne pas, appliquer de nouveau la mise a jour sur cet équipements uniquement. Pour ce faire dans la page Plugin->Protocol Domotique->Abeille, sélectionnez le device et clic sur bouton: "Apply Template". Ensuite regarder le log "Abeille_updateConfig" pour avoir le détails des opérations faites et éventuellement voir ce qui n'est pas mis à jour.
* vous allez trouver des messages: 
- "parameter identical, no change" qui indique que rien n'a été fait sur ce paramètre (déjà à jour).
- "parameter is not in the template, no change" qui indique que le paramètre de l'objet n'est pas trouvé dans le template. Soit il n'est plus nécessaire et ne sera donc pas utilisé, soit vous l'avez changé et on le garde, soit Jeedom a défini une valeur par défaut et c'est très bien ...
- "Cmd Name: nom ===================================> not found in template" qui indique qu'on ne trouve pas le template pour la commande et que donc la commande n'est pas mise à jour. Ça doit être les 10% à gérer manuellement. Dans ce cas, soit effacer l'objet et le recréer soit me joindre sur le forum.
* Équipements qui sont passés sans soucis sur ma prod:
  * Door Sensor V2 Xiaomi
  * Xiaomi Smoke
  * Télécommande Ikea 5 boutons
  * Xiaomi Présence V2
  * Xiaomi Bouton Carré V2
  * Xiaomi Température Carré
  * ...


* Cas rencontrés:
- plug xiaomi, une commande porte le nom "Manufacturer", doit être remplacé par "societe" et appliquer de nouveau "Apply Template"
- interrupteurs muraux Xiaomi: si la mise a jour ne se fait, il faut malheureusement, supprimer et recréer.
- door sensor xiaomi V2 / xiaomi presence V1: une commande porte le nom "Last", doit être remplacé par "Time-Time", et "Last Stamp" par "Time-Stamp"
- ...

### Secours

* Si rien n'y fait, aucune des deux solutions précédentes ne résout le soucis, vous pouvez probablement exécuter la méthode suivante sur un équipement (je ne l'ai pas testée):
- supprimer la commande IEEE-Addr de votre objet.
- Zigate en mode inclusion et re-appairage de l'équipement
- un nouvel objet doit être créé.
- Transférer les commandes de l'ancien objet vers le nouveau avec le bouton "Remplacer cette commande par la commande"
- Transférer l'historique des commandes avec le bouton "Copier l'historique de cette commande sur une autre commande"
- Vous testez le nouvel équipement
- si ok vous pouvez supprimer l'ancien.

### Bugs

Il est fort probable que des bugs soient découverts. Il y a tellement de changements dans cette mise à jour... Dans ce cas forum ou issue dans GitHub...

# Changelog


2019-02-06
-------------
- Récupération des groupes dans la Zigate
- Configuration du groupe de la remote ikea On/off depuis abeille
- Formatting of Livolo Switch
- Groupe commande Chaleur ampoule
- GUI to set group to Zigate
- TxPower Command
- Channel setMask and setExtendedPANID added
- Télécommande Ikea Bouton information to Abeille
- Certification configuration
- Led On/Off


2019-02-04
-------------
- Get Group Membership response modification avec source address for 3.0.f
- Fix Sur mise a jour des templates il manque la mise a jour des icônes
- OSRAM Spot LED dimmable connecté Smart+ - Culot GU5.3
- Now default Zigbee object type could be used to create object in Abeille
- TRADFRIbulbE27WSopal1000lm
- MQTT loop improvement so Abeille should be more reactive
- nom du NE qui fait un Leave dans le message envoyé à la ruche
- Ampoule Hue Flame E14
- Info move from Ruche to Config page
- A bit more decoding of Xiaomi Fields
- channel mak and ExtPAN setting
- Ajout du Switch Livolo 2 boutons
- Affichage Commande au démarrage
- ClassiA60WClear second modèle added
- setTimeServer / getTimeServer


2019-01-25
-------------
- Ajout commande scene
- Deux petites vidéos pour les docs
- Ajout des scènes et groupes de scènes
- Ajout ampoule LWB004
- Osram - flex led rgbw
- Osram - garden led rgbw
- GLEDOPTO Controller RGB+CCT
- Ajout de gestion du time server (cluster)


2019-01-15
-------------
- retrait de pause pour avoir un plugin plus réactif
- LCT001 modèle ajouté
- LTW013 Philips Hue modèle ajouté
- Ajout modèle lightstripe philips hue plus modèle ajouté
- doc télécommande Hue
- Ajout LTW010 ampoule Hue White Spectre
- Ajout de la liste des Abeille ayant un groupe avec leur groupe
- LCT015 Bulb Added
- Add Address IEEE in health table


2018-12-15
-------------
- Graph LQI par distance
- télécommande carré Ikea On/Off
- fix température carré xiaomi
- Télécommande Hue retour Boutons vers Abeille (scénario)


2018-12-11
-------------

- Toute la doc sous le format Jeedom


2018-12-10
-------------
- Ampoule Couleur Standard ZigBee
- Ampoule Dimmable Standard ZigBee


2018-12-09
-------------
- Ampoule Spectre Blanc Standard ZigBee
- Blanche Ampoule GLEDOPTO GU10 Couleur/White GLEDOPTO avec hombridge
- Spectre Blanc Ampoule GLEDOPTO GU10 GL-S-004Z avec hombridge
- Retour des volets profalux en automatique
- Poll Automatique
- Ajout/Suppression/Get des groupes depuis l interface Abeille


2018-12-08
-------------
- Couleur Ampoule GLEDOPTO GU10 Couleur/White GL-S-003Z avec hombridge


2018-12-07
-------------
- Couleur Ampoule Ikea avec Homebridge
- Couleur Ampoule OSRAM avec Homebridge
- Couleur Ampoule Hue Go avec Homebridge


2018-12-05
-------------
[red]#Pour les téméraires qui utilisent le master, pouvez vous remonter les soucis avec pour objectif de faire une beta, puis une version stable. Surtout bien lire le doc 120_Mise_a_jour.adoc#

- Ajout d un paramètre Groupe dans la configuration des devices pour avoir la groupe a commander. Il n'est plus besoin de changer les commandes une à une.


2018-12-04
-------------
- passage aux modèles standardisés (avec include)
- les modèles standardisés permettent de modifier les équipements dans Jeedom sans les effacer et donc sans perdre historique, scénarios associés,...
- ajout des boutons pour appliquer de nouveau les modèles de device
- introduction d'Id unique dans les template pour ne pas confondre les devices par la suite.


2018-01-12
-------------
- Ampoule GLEDOPTO White intégrée


2018-11-30
-------------
- Prise Ikea intégrée
- Ajout des groupes aux devices sélectionnés


2018-11-26
-------------
- Ikea Transformer 30W intégré


2018-11-24
-------------
- Correction TimeOut (en min)


2018-11-16
-------------
- Activation/Désactivation d'un équipement suivant qu'il joint le réseau ou le quitte.
- Rafraichi les informations de la page Health à l'ouverture.


2018-11-05
-------------
- Ajout OSRAM GU10

...


2018-06-14:
-------------
- Ajout de la connectivité en Wifi.
- Ajout des LQI remontant des trames Zigate


2018-06-12:
-------------
- Ajout du double interrupteur mural sur pile xiaomi.
- Network modal (graph automatique du reseau)
- Ajout aqara Cube


-------------
2018-06-11:
-------------
- Stop for Volet Profalux #253


2018-06-01:
-------------
- Profalux Volets Calibration


2018-05-30:
-------------
- Inclusion status dans le widget mis à jour en fonction de l’etat de la Zigate


2018-05-28:
-------------
- Ajout des equipements DIY


2018-01-19
-------------
- first version posted on github
- inclus la création des objets IKEA Bulb et Xiaomi Plug, Température Carre/rond, bouton et InfraRouge


