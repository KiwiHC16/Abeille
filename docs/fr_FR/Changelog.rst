ChangeLog
=========

- Osram classic B40TW: support préliminaire.
- Xiaomi Luminosite: Ajout pourcentage batterie basé sur retour tension (#1166).
- Interne: cron15 amélioré. Pas d'interrogation si eq appartient à zigate inactive.
- Inclusion: support revu pour périph qui quitte puis revient sur le réseau.
- Moniteur: Disponible pour tous et plus seulement en mode dev.
- Firmware 3.1e disponible pour tous.

210704-STABLE-1
----------

- Modifications syntaxe JSON équipement (avec support rétroactif temporaire):
  - 'commands' au lieu de 'Commandes'.
  - 'category' au lieu de 'Categorie'
  - 'icon' au lieu de 'icone'
  - 'batteryType' au lieu de 'battery_type'
- Support JSON equipement: correction pour support multiple categories.
- Page EQ/avancé: recharger JSON accessible à tous.
- Page EQ: type de batterie déplacé vers principale.
- Interne: plusieurs améliorations de robustesse.
- Interne: Parser: Support des messages longs.
- Zigate WIFI: Correction & amélioration arret/démarrage démon.
- Equipement/categories: correction. Effacement categorie résiduelle.
- Trafri motion sensor: Mise-à-jour JSON pour controle de groupe.
- Gestion des démons: correctif sur arret forcé (kill).
- Interne: Parser: affichage erreurs msg_receive().
- Interne: SerialRead: affichage erreur msg_send().
- Interne: Serial to parser: Messages trop grands ignores pour ne plus bloquer la pile + message d'erreur.
- Batterie %: Parser renvoi valeur correcte pour 0001-EPX-0021 + Abeille.class + update JSON (#2056).
  Peut nécessiter de recharger JSON.
- Batterie %: Report dans Jeedom de tous les "end points" et pas seulement 01.

210620-STABLE-1
----------

- SPLZB-132: Correction icone + ajout somme conso.
- Correction remontée cluster 0B04 (mauvais EP).
- Orvibo CM10ZW multi functional relay: mise-à-jour icone.
- Nettoyage de vieux fichiers log au démarrage.
- Socat: Amélioration pour avoir les messages d'erreur.
- Interne: Restauration option avancées de récupération des équipements inconnus.
- Xiaomi v1: Correction regression inclusion. Trick ajouté pour Xiaomi.
- Orvibo CM10ZW multi functional relay: support préliminaire.
- Page santé: Correction mauvais affichage du status des zigates.
- Page equipement/avancé: Possibilité de recharger dernier JSON (et mettre à jour les commandes) sans refaire d'inclusion.
- Interne: Suppression AbeilleDev.js (mergé dans Abeille.js).
- Page équipement: Correction rapport d'aspect image (icone).
- Interne: Parser: Support msg 9999/extended error.
- Interne: Parser: Qq améliorations découverte nouvel équipement.
- Interne: SerialRead: Boucle et attend si port disparait au lieu de quitter (#2040).
- Page gestion: Correction regression groupes.

210610-STABLE-3
----------

- Philips E27 single filament bulb: Ajout modele LWA004
- Interne: Correction ReadAttributRequest multi attributs
- Interne: Correction 'zgGetZCLStatus()'
- Frient SPLZB-132 Smart Plug Mini: Ajout support préliminaire.
- Interne: Correction eqLogic/configuration. Suppression des champs obsolètes lors de la mise-à-jour de l'équipement.
- Tuya 4 buttons light switch (ESW-0ZAA-EU): support préliminaire (#1991).
- Tuya smart socket: Ajout support modele générique 'TS0121__TZ3000_rdtixbnu'.
- Telecommande virtuelle: Correction regression. Plusieurs télécommandes par zigate à nouveau possible (#2025).
- Lancement de dépendances: correction erreur (#2026).
- Exclusion d'un équipement du réseau: En mode dev uniquement. Nouvelle version.
- Zigate wifi: correction regression.

210607-STABLE-1
----------

- ATTENTION: Regression sur les telecommandes virtuelles. Une seule possible avec cette version.
- ATTENTION: Faire un backup pour pouvoir revenir à la precedente "stable". Structure DB eqLogic modifiée: "Ruche" remplacé par "0000"
- Interne: Parser: revue params decodeX() + cleanup
- Zemismart ZW-EC-01 curtain switch: mise-à-jour modèle.
- Interne: Correction timeout.
- Reinclusion: L'equipement et ses commandes sont mis à jour. Seules les commandes obsolètes sont détruites.
  Ca permet de ne plus casser le chemin des scénaris et autres utilisateurs des commandes.
- Firmware: Suppression des FW 3.0f, 3.1a & 3.1b. 3.1d = FW suggéré.
- JennicModuleProgrammer: Mise-à-jour v0.7 + améliorations. Compilé avant utilisation.
- Zigate DIN: Ajout support pour mise-à-jour FW.
- Page equipement: section "avancé", mise à jour des champs en temps réel (#1839).
- Gestion des groupes: correction regression (#2011).
- Telecommande virtuelle: correction regression (#2011).
- Interne: Revue decode 8062 (Group membership).
- JSON: Correction setReportTemp (#1918).
- Innr RB285C: correction modele corrompu.
- Innr RB165: modele préliminaire.
- Tuya GU10 ZB-CL01: ajout support.
- Hue motion sensor: mise-à-jour JSON.
- Interne: correction message 8120.
- Page config: correction installation WiringPi (#1979).
- Introduction de "core/config/devices_local" pour les EQ non supportés par Abeille mais locaux/en cours de dev.
- Zemismart ZW-EC-01 curtain switch: ajout du modèle JSON
- Nouvelle procédure d'inclusion.
- Support des EQ avec identifiants 'communs'.
- Création du log 'AbeilleDiscover.log' si inclusion d'un équipement inconnu.
- Profalux volet: Revue modele JSON. Utilisation cluster 0008 au lieu de 0006 + report.
- Page EQ/commandes: pour mode developpeur, possibilité charger JSON.
- Ordre apparition des cmdes: Suit maintenant l'ordre d'utilisation dans JSON equipement.
- Un équipement peut maintenant être invisible par defaut ('"isVisible":0' dans le JSON).
- Profalux telecommande: S'annonce mais inutilisable côté Jeedom. Cachée à la création.

210510-STABLE-1
---------------

- Page compatibilité: revisitée + ajout du tri par colonne
- Page santé: ajout de l'état des zigate au top
- Sonoff SNZB-02: support corrigé + support 66666 (ModelIdentifier) (#1911)
- Xiaomi GZCGQ01LM: ajout support tension batterie + online (#1166)
- Page EQ/params: ajout de l'identifiant zigbee
- Correction "#1908: AbeilleCmd: Unknown command"
- Correction "#1951: pb affichage heure "Derniere comm."
- Correction blocage du parser dans certains cas de démarrage.
- Diverses modifications pour améliorer la robustesse et les messages d'erreurs.
- Monitor (pour developpeur seulement pour l'instant)
- Gestion des démons: revisitée pour éviter redémarrages concurrents.
- Correction "#1948: BSO, lift & tilt"
- JSON: Emplacement des commandes changé de "core/config/devices/Template" vers "core/config/commands"
- Innr RB285C support preliminaire

11/12/2020
----------

- Prise Xiaomi: fonctions de base dans le master (On/Off/Retour etat). En cours retour de W, Conso, V, A et T°.
- LQI collect revisited & enhanced #1526
- Ajout du modale Template pour afficher des differences entre les Abeilles dans Jeedom et leur Modele.
- Ajout d un chapitre Update dans la page de configuration pour verifier que certaines contraintes sont satisfaites avant de faire la mise a jour.
- Ajout Télécommande 3 boutons Loratap #1406
- Contactor 20AX Legrand : Pilotage sur ses fonctions ON/OFF et autre
- Prise Blitzwolf
- Detecteur de fumée HEIMAN HS1SA-E
- TRADFRIDriver30W IKEA

Beta 24/11/2010
---------------

Surtout faites un backup pour pouvoir revenir en arrière.

- Premiere version Abeille qui prend en charge le firmware Zigate 3.1d.
- J'ai passé toutes mes zigates en 3.1D (Je ne vais plus tester les evolutions d'Abeille avec les anciennes versions du firmware).
- Essayez de passer sur le firmware 3.1D quand vous pourrez. Perso je vous recommande de faire un erase EEPROM lors de la mise à jour puis de faire un re-inclusion de tout les modules. Attention c'est une operation assez lourde. Assurez vous d'avoir l'adresse IEEE dans la page santé avant de faire cette operation.
- Dans la page de configuration il y a un nouveau paramètre: "Blocage traitement Annonces". Par défaut il doit être sur Non. Il semble qui ai une certaine tendance à ce mettre sur Oui. Mettez le bien sur Non et redémarrer le Démon.
- si vous restez avec une zigate en firmware inférieur à 3.1d, ne surtout pas passer la zigate en mode hybride.

- Tous les details dans:
https://github.com/KiwiHC16/Abeille/commits/beta?after=61b027c84f673f484073d6f0a73ad0bad08fef0d+34&branch=beta

12/2019 => 03/2020
------------------

::

    !!!! Le plugin a été testé avec 1 ou 5 Zigate dans le panneau de configuration  !!!!
    !!!! Avec d'autres valeur 2, 3, 4, 6... il est for possible que tout            !!!!
    !!!! ne fonctionne pas comme prévu. Si vous avez 2 zigate, mettez le nombre de  !!!!
    !!!! zigate à 5 et activer uniquement les zigates presentent.                   !!!!

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

Attention: cette nouvelle version apporte:

* le multi-zigate
* la gestion de queue de message avec priorité
* quelques équipements supplémentaires
* corrections de bugs

Mais comme les changements sont importants et que j'ai pas beaucoup de temps pour tester il peut y avoir des soucis. Donc faites bien une sauvegarde pour revenir en arrière si besoin.

A noter:

* La partie graphs n'a pas été complètement vérifiée et il reste des soucis
* les timers ne sont plus dans le Plugin
* un bug critique si vous faites l inclusion d'un type d equipement inconnu par Abeille, il faut redemarrer le demon.


06/2019 => 11/2019
------------------

Rien de spécifique à faire. Juste a faire la mise à jour depuis jeedom.

03/2019 => 06/2019
------------------

Rien de spécifique à faire.

02/2019 => 03/2019
------------------

Rien de spécifique à faire. Pour les évolution voir le changelog ci dessous.

01/2019 => 02/2019
------------------

Cette version est en ligne avec le firmware 3.0f de la Zigate
Vous pouvez utiliser un firmware plus vieux mais tout ne fonctionnera pas. (98% fonctionnera)

Comment procéder:

* Mettre à jour le plugin Abeille
* Flasher la Zigate avec le firmware 3.0f (*bien faire un erase de l'EEPROM*)
* Connecter la Zigate et démarrer le deamon abeille
* démarrer le réseau Zigbee depuis abeille
* mettre la Zigate en inclusion
* inclure vos routeurs en partant des plus proches au plus lointain
* Vérifier que tout fonctionne
* Inclure les équipements sur piles (dans l'ordre que vous voulez)

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

11/2018 => 01/2019
------------------

Cette mise à jour est importante et délicate. Pour facilité l'intégration de nouveaux équipements par la suite une standardisation des modèles doit être faite.
Cela veut dire que tous les modèles changent et que le objets dans Abeille/Jeedom doivent être mis à jour.
Prévoir du temps, avoir bien fait les backup, et prévoir d'avoir à faire quelques manipulations à la main. Les situations rencontrées vont dépendre de l'historique des équipements dans Jeedom.

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

Solution pour les petits systèmes

* Cela suppose que vous aller effacer (objets, historiques,...) toutes les données puis re-créer le réseau.
* supprimer le plug in Abeille
* Installer le plug in Abeille depuis le market (ou github)
* Activer et faire la configuration du plugin
* Démarrer le plugin
* Mettre en mode inclusion
* Appairer les devices.

Solution pour les gros systèmes

Si la solution précédente demande trop de travail, on peut faire la mise à jour de la façon suivante. Attention, je ne peux pas tester toutes les combinaisons et des opérations supplémentaires seront certainement nécessaires. 90% aura été fait automatiquement.
Il n'y a pas de moyen infaillible pour faire la correspondance entre une commande dans un modèle et une commande dans Jeedom. Le lien est fait soit par le nom dans la commande nom ou quand pas disponible par le nom de l'image utilisée pour le device. De même pour les commande le nom est le moyen de faire le lien. Si vous avez fait des changements de nom, les commandes sortiront en erreur et cela demandera de mettre le nom de la commande dans le modèle le temps de la conversion.
Dans les versions suivantes, nous ne devrions plus avoir ce problème car les commandes auront un Id unique et spécifique.

* Mettre à jour la plugin avec le market (ou github)
* Vérifier la configuration du plugin et démarrer le plugin en mode debug.
* Demander la mise à jour des objets depuis les templates, bouton: "Appliquer nouveaux modèles"
* 90% des objets devraient être à jour maintenant.
* Tester vos équipements.

Si un équipement ne fonctionne pas, appliquer de nouveau la mise a jour sur cet équipements uniquement. Pour ce faire dans la page Plugin->Protocol Domotique->Abeille, sélectionnez le device et clic sur bouton: "Apply Template". Ensuite regarder le log "Abeille_updateConfig" pour avoir le détails des opérations faites et éventuellement voir ce qui n'est pas mis à jour.

vous allez trouver des messages:

* "parameter identical, no change" qui indique que rien n'a été fait sur ce paramètre (déjà à jour).
* "parameter is not in the template, no change" qui indique que le paramètre de l'objet n'est pas trouvé dans le template. Soit il n'est plus nécessaire et ne sera donc pas utilisé, soit vous l'avez changé et on le garde, soit Jeedom a défini une valeur par défaut et c'est très bien ...
* "Cmd Name: nom ===================================> not found in template" qui indique qu'on ne trouve pas le template pour la commande et que donc la commande n'est pas mise à jour. Ça doit être les 10% à gérer manuellement. Dans ce cas, soit effacer l'objet et le recréer soit me joindre sur le forum.

Équipements qui sont passés sans soucis sur ma prod:

  * Door Sensor V2 Xiaomi
  * Xiaomi Smoke
  * Télécommande Ikea 5 boutons
  * Xiaomi Présence V2
  * Xiaomi Bouton Carré V2
  * Xiaomi Température Carré
  * ...

Cas rencontrés:

* plug xiaomi, une commande porte le nom "Manufacturer", doit être remplacé par "societe" et appliquer de nouveau "Apply Template"
* interrupteurs muraux Xiaomi: si la mise a jour ne se fait, il faut malheureusement, supprimer et recréer.
* door sensor xiaomi V2 / xiaomi presence V1: une commande porte le nom "Last", doit être remplacé par "Time-Time", et "Last Stamp" par "Time-Stamp"
* ...

Secours

* Si rien n'y fait, aucune des deux solutions précédentes ne résout le soucis, vous pouvez probablement exécuter la méthode suivante sur un équipement (je ne l'ai pas testée):
* supprimer la commande IEEE-Addr de votre objet.
* Zigate en mode inclusion et re-appairage de l'équipement
* un nouvel objet doit être créé.
* Transférer les commandes de l'ancien objet vers le nouveau avec le bouton "Remplacer cette commande par la commande"
* Transférer l'historique des commandes avec le bouton "Copier l'historique de cette commande sur une autre commande"
* Vous testez le nouvel équipement
* si ok vous pouvez supprimer l'ancien.

Bugs
----

Il est fort probable que des bugs soient découverts.

Dans ce cas aller voir le forum: `FORUM <https://community.jeedom.com/tag/plugin-abeille>`_

ou issue dans GitHub: `ISSUE <https://github.com/KiwiHC16/Abeille/issues?utf8=✓&q=is%3Aissue+>`_

Changelog
---------

En fait le ChangeLog est dans GitHub alors je perds mon temps a essayer de la mettre a jour dans cette doc. Je ne fais plus de mise à jour ou que des principales choses quand j'ai le temps.

Voir directement dans `GitHub <https://github.com/KiwiHC16/Abeille/commits/master>`_


2019-11-25
----------

Ce dernières semaines le focus a été sur:
- Compatibilité avec Jeedom V4 et Buster (Debian 10)
- mise en place de la gestion des messages envoyés à la zigate avec creation de fil d'attente.
- Repetition d'un message vers la zigate si elle dit n'avoir pas réussi à le gérer
- Refonte de la détection de équipements lors de l inclusion
- Store et Télécommande Store Ikea
- Demarrage automatique du réseau Zigbee
- Iluminize Dimmable 511.201
- Iluminize 511.202
- Osram Smart+ Motion Sensor
- Télécommande OSRAM
- Ajout ampoules INNR RF263 et RF265
- Corrections de bugs
- .....

2019-03-19
----------

* Motion Hue Outdoor integration
* Doc Hue Motion
* Hue Motion Luminosite

2019-03-18
----------

* Plus de doc sur la radio
* Modification modele sur EP

2019-03-17
----------

* Resolution sur un systeme en espagnole

2019-03-16
----------

* start to track APS failures
* dependancy_info debut des modifications

2019-03-15
----------

* Moved all doc to asciidoc format
* Few correction around modele folder

2019-03-11
----------

* Ajout capteur IR Motion Hue Indoor

2019-03-01
----------

* Inclusion de la PiZiGate
* Possibilité de programmer le PiZiGate

2019-02-27
----------

* OSRAM SMART+ Outdoor Flex Multicolor
* Eurotronic Spirit

2019-02-15
----------

* Correction probleme volet profalux


2019-02-14
----------

* Amelioration de la doc
* Inclusion dans appli web mobile

2019-02-11
----------

* Amelioration de la doc.
* Reduction log sur annonce
* Prise Xiaomi Encastrée

2019-02-07
----------

* Mise en place de la cagnotte
* Correction de l affichage des icones sur filtre
* Amélioration retour Tele Ikea

2019-02-06
----------

* Récupération des groupes dans la Zigate
* Configuration du groupe de la remote ikea On/off depuis abeille
* Formatting of Livolo Switch
* Groupe commande Chaleur ampoule
* GUI to set group to Zigate
* TxPower Command
* Channel setMask and setExtendedPANID added
* Télécommande Ikea Bouton information to Abeille
* Certification configuration
* Led On/Off

2019-02-04
----------

* Get Group Membership response modification avec source address for 3.0.f
* Fix Sur mise a jour des templates il manque la mise a jour des icônes
* OSRAM Spot LED dimmable connecté Smart+ - Culot GU5.3
* Now default Zigbee object type could be used to create object in Abeille
* TRADFRIbulbE27WSopal1000lm
* MQTT loop improvement so Abeille should be more reactive
* nom du NE qui fait un Leave dans le message envoyé à la ruche
* Ampoule Hue Flame E14
* Info move from Ruche to Config page
* A bit more decoding of Xiaomi Fields
* channel mak and ExtPAN setting
* Ajout du Switch Livolo 2 boutons
* Affichage Commande au démarrage
* ClassiA60WClear second modèle added
* setTimeServer / getTimeServer

2019-01-25
----------

* Ajout commande scene
* Deux petites vidéos pour les docs
* Ajout des scènes et groupes de scènes
* Ajout ampoule LWB004
* Osram - flex led rgbw
* Osram - garden led rgbw
* GLEDOPTO Controller RGB+CCT
* Ajout de gestion du time server (cluster)

2019-01-15
----------

* retrait de pause pour avoir un plugin plus réactif
* LCT001 modèle ajouté
* LTW013 Philips Hue modèle ajouté
* Ajout modèle lightstripe philips hue plus modèle ajouté
* doc télécommande Hue
* Ajout LTW010 ampoule Hue White Spectre
* Ajout de la liste des Abeille ayant un groupe avec leur groupe
* LCT015 Bulb Added
* Add Address IEEE in health table

2018-12-15
----------

* Graph LQI par distance
* télécommande carré Ikea On/Off
* fix température carré xiaomi
* Télécommande Hue retour Boutons vers Abeille (scénario)

2018-12-11
----------

* Toute la doc sous le format Jeedom

2018-12-10
----------

* Ampoule Couleur Standard ZigBee
* Ampoule Dimmable Standard ZigBee

2018-12-09
----------

* Ampoule Spectre Blanc Standard ZigBee
* Blanche Ampoule GLEDOPTO GU10 Couleur/White GLEDOPTO avec hombridge
* Spectre Blanc Ampoule GLEDOPTO GU10 GL-S-004Z avec hombridge
* Retour des volets profalux en automatique
* Poll Automatique
* Ajout/Suppression/Get des groupes depuis l interface Abeille

2018-12-08
----------

* Couleur Ampoule GLEDOPTO GU10 Couleur/White GL-S-003Z avec hombridge

2018-12-07
----------

* Couleur Ampoule Ikea avec Homebridge
* Couleur Ampoule OSRAM avec Homebridge
* Couleur Ampoule Hue Go avec Homebridge

2018-12-05
----------

* Ajout d un paramètre Groupe dans la configuration des devices pour avoir la groupe a commander. Il n'est plus besoin de changer les commandes une à une.

2018-12-04
----------

* passage aux modèles standardisés (avec include)
* les modèles standardisés permettent de modifier les équipements dans Jeedom sans les effacer et donc sans perdre historique, scénarios associés,...
* ajout des boutons pour appliquer de nouveau les modèles de device
* introduction d'Id unique dans les template pour ne pas confondre les devices par la suite.

2018-01-12
----------

* Ampoule GLEDOPTO White intégrée

2018-11-30
----------

* Prise Ikea intégrée
* Ajout des groupes aux devices sélectionnés

2018-11-26
----------

* Ikea Transformer 30W intégré

2018-11-24
----------

* Correction TimeOut (en min)

2018-11-16
----------

* Activation/Désactivation d'un équipement suivant qu'il joint le réseau ou le quitte.
* Rafraichi les informations de la page Health à l'ouverture.

2018-11-05
----------

* Ajout OSRAM GU10

2018-06-14
----------

* Ajout de la connectivité en Wifi.
* Ajout des LQI remontant des trames Zigate

2018-06-12
----------

* Ajout du double interrupteur mural sur pile xiaomi.
* Network modal (graph automatique du reseau)
* Ajout aqara Cube

2018-06-11
----------

* Stop for Volet Profalux =253

2018-06-01
----------

* Profalux Volets Calibration

2018-05-30
----------

* Inclusion status dans le widget mis à jour en fonction de l’etat de la Zigate

2018-05-28
----------

* Ajout des equipements DIY

2018-01-19
----------

* first version posted on github
* inclus la création des objets IKEA Bulb et Xiaomi Plug, Température Carre/rond, bouton et InfraRouge
