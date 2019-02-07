# Mise à jour

= Mise a jour

== Version stable Mi Janvier 2019 => Version beta et stable du mi Fevrier 2019

Cette version est en ligne avec le firmware 3.0f
Vous pouvez utiliser un firmware plus vieux mais tout ne fonctionera pas, Probalblement 98% fonctionnera

Comment procéder:
- Mettre à jour le plugin Abeille
- Flasher la zigate avec le firmware 3.0f (bien faire un erase de l'EEPROM)
- Connecter la zigate et demarrer le demon abeille
- démarrer le réseau zigbee depuis abeille
- mettre la zigate en inclusion
- inclure vos routeurs en partant des plus proches au plus lointain
- Vérifier que tout fonctionne 
- Inclure les équipements sur piles (dans l'ordre que vous voulez)

== Version stable du 16/11/2018 (ou precedentes) => Version beta et stable jusqu'a Mi Janvier 2019

Cette mise à jour est importante et délicate. Pour facilité l'integration de nouveaux équipments par la suite une standardisation des modèles doit être faite.
Cela veut dire que tous les modèles changent et que le objets dans Abeille/Jeedom doivent être mis à jour.
Prevoir du temps, avoir bien fait les backup, et prevoir d'avoir à faire quelques manipulations à la main. Les situations rencontrées vont dépendre de l'historique des équipements dans Jeedom.

[red]#!!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!#

=== Solution pour les petits systemes

* Cela suppose que vous aller effacer (objets, historiques,...) toutes les données puis re-créer le réseau.
* supprimer le plug in Abeille
* Installer le plug in Abeille depuis le market (ou github)
* Activer et faire la configuration du plugin
* Demarrer le plugin
* Mettre en mode inclusion
* Appairer les devices.

=== Solution pour les gros systèmes

* Si la solution précédente demande trop de travail, on peut faire la mise à jour de la facon suivante. Attention, je ne peux pas tester toutes les combinaisons et des operations supplementaires seront certainement necessaires. 90% aura ete fait automatiquement. 
Il n'y a pas de moyen infaillible pour faire la correspondance entre une commande dans un modèle et une commande dans jeedom. Le lien est fait soit par le nom dans la commande nom ou quand pas dispo par le nom de l'image utilisée pour le device. De meme pour les commande le nom est le moyen de faire le lien. Si vous avez fait des changements de nom, les commandes sortiront en erreur et cela demandera de mettre le nom de la commande dans le modèle le temp de la conversion. 
Dans les versions suivantes, nous ne devrions plus avoir ce problème car les commandes auront un Id unique et specifique.

* Mettre à jour la plugin avec le market (ou github)
* Vérifier la configuration du plugin et demarrer le plugin en mode debug.
* Demander la mise à jour des objets depuis les templates, bouton: "Appliquer nouveaux modèles"
* 90% des objets devraient être à jour maintenant.
* Tester vos équipements.

* Si un équipement ne fonctionne pas, appliquer de nouveau la mise a jour sur cet équipements uniquement. Pour ce faire dans la page Plugin->Protocol Domotique->Abeille, selectionnez le device et clic sur bouton: "Apply Template". Ensuite regarder le log "Abeille_updateConfig" pour avoir le details des operations faites et eventuellement voir ce qui n'est pas mis à jour.
* vous allez trouver des messages: 
- "parameter identical, no change" qui indique que rien n'a été fait sur ce parametre (deja à jour).
- "parameter is not in the template, no change" qui indique que le parametre de l objet n'est pas trouvé dans le template. Soit il n'est plus necessaire et ne sera donc pas utilisé, soit vous l'avez changé et on le garde, soit jeedom a défini une valeur par defaut et c'est très bien ...
- "Cmd Name: nom ===================================> not found in template" qui indique qu'on ne trouve pas le template pour la commande et que donc la commande n'est pas mise à jour. Ca doit être les 10% à gérer manuellement. Dans ce cas, soit effacer l objet et le recreer soit me joindre sur le forum.
* Equipements qui sont passés sans soucis sur ma prod:
- Door Sensor V2 Xiaomi
- Xiaomi Smoke
- Telecommande Ikea 5 boutons
- Xiaomi Presence V2
- Xiaomi Bouton Carré V2
- Xiaomi Temperature Carré
- ...


* Cas rencontrés:
- plug xiaomi, une commande porte le nom "Manufacturer", doit être remplacé par "societe" et appliquer de nouveau "Apply Template"
- interrupteurs muraux Xiaomi: si la mise a jour ne se fait, il faut malheuresement, supprimer et recréer.
- door sensor xiaomi V2 / xiaomi presence V1: une commande porte le nom "Last", doit être remplacé par "Time-Time", et "Last Stamp" par "Time-Stamp"
- ...

=== Secours

* Si rien n'y fait, aucune des deux solutions précedentes ne résout le soucis, vous pouvez probablement executer la méthode suivante su r un équipement (je ne l'ai pas testée):
- supprimer la commande IEEE-Addr de votre objet.
- Zigate en mode inclusion et re-appariage de l'équipement
- un nouvel objet doit être créé.
- Transferer les commandes de l ancien objet vers le nouveau avec le bouton "Remplacer cette commande par la commande"
- Transferer l'historique des commandes avec le bouton "Copier l'historique de cette commande sur une autre commande"
- Vous testez le nouvel équipement
- si ok vous pouvez supprimer l ancien.

=== Bugs

Il est fort probable que des bugs soient découvers. Il y a tellement de changements dans cette mise à jour... Dans ce cas forum ou issue dans GitHub...








# Changelog


2019-02-06
-------------
- Recuperation des groues dans la zigate
- Configuration du groupe de la remote ikea On/off depuis abeille
- Formatting of Livolo Switch
- Groupe commande Chaleur ampoule
- GUI to set group to zigate
- TxPower Command
- Channel setMask and setExtendedPANID added
- Telecommande Ikea Bouton information to Abeille
- Certification configuration
- Led On/Off


2019-02-04
-------------
- Get Group Membership response modification avec source address for 3.0.f
- Fix Sur mise a jour des templates il manque la mise a jour des icônes
- OSRAM Spot LED dimmable connecté Smart+ - Culot GU5.3
- Now default zigbee object type could be used to create object in Abeille
- TRADFRIbulbE27WSopal1000lm
- MQTT loop improvement so Abeille should be more reactive
- nom du NE qui fait un Leave dans le message envoyé à la ruche
- Ampoule Hue Flame E14
- Info move from Ruche to Config page
- A bit more decoding of Xiaomi Fields
- channel mak and ExtPAN setting
- Ajout du Switch Livolo 2 boutons
- Affichage Commande au demarrage
- ClassiA60WClear second modele added
- setTimeServer / getTimeServer


2019-01-25
-------------
- Ajout commande scene
- Deux petites videos pour les docs
- Ajout des scenes et groupes de scenes
- Ajout ampoule LWB004
- Osram - flex led rgbw
- Osram - garden led rgbw
- GLEDOPTO Controller RGB+CCT
- Ajout de gestion du time server (cluster)


2019-01-15
-------------
- retrait de pause pour avoir un pluin plus reactif
- LCT001 modele ajouté
- LTW013 Philips Hue modele ajouté
- Ajout modele lightstripe philips hue plus modele ajouté
- doc telecommande Hue
- Ajout LTW010 ampoule Hue White Spectre
- Ajout de la liste des Abeille ayant un groupe avec leur groupe
- LCT015 Bulb Added
- Add Address IEEE in health table


2018-12-15
-------------
- Graph LQI par distance
- telecommande carré Ikea On/Off
- fix temperature carré xiaomi
- Telecommande Hue retour Boutons vers Abeille (scenario)


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

- Ajout d un parametre Groupe dans la configuration des devices pour avoir la groupe a commander. Il n'est plus besoin de changer les commandes une à une.


2018-12-04
-------------
- passage aux modeles standardisés (avec include)
- les modeles standardisés permettent de modifier les equipement dans jeedom sans les effacer et donc sans perdre historque, scenarions associés,...
- ajout des boutons pour appliquer de nouveau les modeles de device
- introduction d'Id unique dans les template pour ne pas confondre les devices par la suite.


2018-01-12
-------------
- Ampoule GLEDOPTO White intégrée


2018-11-30
-------------
- Prise Ikea intégrée
- Ajout des groupes aux devices selectionnés


2018-11-26
-------------
- Ikea Transformer 30W intégré


2018-11-24
-------------
- Correction TimeOut (en min)


2018-11-16
-------------
- Activation/Desactivation d'un équipement suivant qu'il joint le réseau ou le quitte.
- Rafraichi les informations de la page Health à l ouverture.


2018-11-05
-------------
- Ajout OSRAM GU10

...


2018-06-14:
-------------
- Ajout de la connectivité en Wifi.
- Ajout des LQI remontant des trames zigate


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
- Inclusion status dans le widget mis à jour en fonction de l’etat de la zigate


2018-05-28:
-------------
- Ajout des equipements DIY


2018-01-19
-------------
- first version posted on github
- inclus la creation des objets IKEA Bulb et Xiaomi Plug, Temperature Carre/rond, bouton et InfraRouge


