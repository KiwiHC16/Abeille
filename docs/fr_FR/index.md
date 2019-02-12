# Présentation

(Portage en cours de la documentation vers le format officiel jeedom, le texte que vous lisez est en cours d'écriture. La doc originale est toujours à [cet emplacement](https://github.com/KiwiHC16/Abeille)


## Abeille

![Abeille Icone](../images/Abeille_icon.png)

*Le plugin Abeille*  permet de mettre en place un réseau ZigBee avec des produits du marché et des réalisations personnelles (DIY) par l'intermédaire de la passerelle Zigate.

"ZiGate est une passerelle universelle compatible avec énormément de matériels radios ZigBee. Grâce à elle, vous offrez à votre domotique un large panel de possibilités. La ZiGate est modulable , performante et ouverte pour qu'elle puisse évoluer selon vos besoins.
"
Dixit son créateur.

Ce plugin est né de besoins personnels : capteur de température radio distant avec un réseau sécurisé, mesh,…

Finalement, il intègre de plus en plus d’équipements :
[Compatibilité](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/040_Compatibilite.adoc)

Mon réseau personnel fonctionne depuis plusieurs mois et possède actuellement 45 équipements et continue de grossir.

Ce plugin inclus les fonctions de base pour la gestions de équipements comme On/Off/Toggle/Up/Down/Detection/… mais aussi des fonctions avancées pour faciliter la gestion d’un gros réseau :
* Retour d'état des équipements,    
* Santé (Dernière communication,…)
* Niveau des batteries
* Graphe du réseau
* Liste de tous les équipements du réseau
* Informations radio sur les liaisons entre les équipements
* En mode USB ou en mode Wifi
* Fonctionne avec Homebridge
-    …





## Timer

![Timer Icone](../images/node_Timer.png)

J’ai aussi intégré un « sous-plugin » [TIMER](#tocAnchor-1-6) qui fonctionne à la seconde dans ce plugin. Il faudra peut être que je fasse un plugin dédié et indépendant.


## Enjoy

Pour ceux qui utiliseront ce plugin, je vous souhaite une bonne expérience. Pour ceux qui auraient des soucis, vous pouvez aller sur le forum ou ouvrir une « issue » dans github (Je ferai de mon mieux pour vous aider).

# Plus de détails

Ce plugin Jeedom permet de connecter un réseau ZigBee au travers de la passerelle ZiGate.
Il est en permanente évolution.

## Il permet

- de connecter les ampoules IKEA
- de connecté toute la famille Xiaomi Zigbee (Sensor presence, prise, temperature, humidité, pression, interrupteur, porte).
- de faire les inclusions des equipments zigbee depuis jeedom
- d'avoir l'état de l ampoule IKEA, son niveau de brillance, ses caractéristiques (nom, fabriquant, SW level).
- de commander les ampoules une par une (On/Off, niveau, couleur,...)
- de commander les ampoules et autre par groupe (On/Off, niveau)
- d'avoir l'état de la prise Xiaomi avec la puissance et la consommation (Nom et Fabriquant)
- d'avoir les temperatures, humidité, pression Xiaomi, son nom, tension batterie
- d'avoir la remontée d'une presence (capteur infrarouge xiaomi)
- d'avoir la remontée d'ouverture de porte
- d'avoir les clics sur les interrupteurs (1, 2, 3 ou 4 clics)
- de définir des groupes comprenant des ampoules IKEA et prise xiaomi (Je peux donc avoir un mix dans le même groupe qui est commandé par une télécommande IKEA par exemple, ou faire un va et vient sur des ampoules IKEA avec 2 télécommandes IKEA (ce qui n'est pas possible avec la solution pure IKEA),...)

## Ce qu'on peut faire

Exemples:
- Si j’appuie sur l’interrupteur Xiaomi, avec un scenario Jeedom, j'allume l’ampoule IKEA.
- Avec une télecommande Ikea je commande ampoule Ikea, Hue, OSRAM,... prise ... tout en même temps
- Avec deux, trois, quatre,... télécommandes Ikea je fais un va et vient
- Je contrôle chaque équipement depuis Jeedom.

Et surtout, je profite du « mesh » ZigBee (des ampoules IKEA et prise Xiaomi) car je vous confirme que les prises Xiaomi et les ampoules IKEA font le routage des messages ZigBee.


# Installation

## ZiGate

- La ZiGate est avec le bon firmware et connectée au port USB ou sur son module wifi (le firmware actuellement testé est la version 30e: https://github.com/fairecasoimeme/ZiGate/tree/master/Module%20Radio/Firmware )

## Widget

> Je vous propose d' installer des widgets avant d'installer Abeille pour avoir une interface graphique plus sympa mais ce n'ai pas obligatoire.

- Installer quelques widgets (plugin officiel) qui seront utilisés lors de la création des objets. Ce n'est pas obligatoire mais le résultat est plus joli.
* baromètre pour le capteur Xiaomi Carré (dashboard.info.numeric.barometre )
* thermomètre pour les capteurs Xiaomi ronds et carrés (dashboard.info.numeric.tempIMG)
* humidité pour les capteurs Xiaomi ronds et carrés (dashboard.info.numeric.hydro3IMG)

![](../images/Capture_d_ecran_2018_01_21_a_11_30_2.png)

## Objet de référence

> Afin de trouver rapidement les nouveaux équipements, il est nécessaire de créer une pièce (un objet jeedom) auquel seront rattachés par défaut.

- Créez un objet sur lequel les nouveaux objets seront rattachés automatiquement. Menu Outils->Objet->"+ vert" (Objet Id=1, pour l'instant codé en dur).

![](../images/Capture_d_ecran_2018_01_21_a_10_53_59.png)

![](../images/Capture_d_ecran_2018_01_21_a_10_54_13.png)

Récupérez sont ID en sélectionnant "Vue d'ensemble"

![](../images/Capture_d_ecran_2018_01_21_a_17_27_54.png)

![](../images/Capture_d_ecran_2018_01_21_a_17_28_01.png)

##  Installation du plugin

### Depuis Github

- Créer un répertoire Abeille dans le repertoire des plugins et installer les fichiers.
* ssh sur votre jeedom
* cd /var/www/html/plugins/

- si vous prenez le zip file
```
* mkdir Abeille
* cd Abeille
* unzip le fichier téléchargé de GitHub dans le répertoire
* cd ..
````

- Si vous allez directement avec git
```
* git clone https://github.com/KiwiHC16/Abeille.git Abeille
```

Et pour le développeurs, voici une info très utile:

>Merci @lukebr

Pour une mise à jour à partir de github :
```
cd ../../var/www/html/plugins/Abeille
sudo git pull https://github.com/KiwiHC16/Abeille
```

Et si il y a eu des bidouilles en local pour écraser avec dernière mise à jour :
```
cd /var/www/html/plugins/Abeille
sudo git reset --hard HEAD
sudo git pull https://github.com/KiwiHC16/Abeille
```

- Et pour finir
```
* chmod -R 777 /var/www/html/plugins/Abeille
* chown -R www-data:www-data /var/www/html/plugins/Abeille
```



Si vous voulez aller a un commit specifique:
```
git reset --hard dd7fa0a
```

### Depuis le market

* Rien de spécifique. Suivre la procédure classique. Pour l'instant il ne doit y avoir qu'une version en beta.

### Alternative : Installation du github depuis le market

- Aller sur configuration puis l'onglet mise à jour, selectionner en dessous l'onglet Github cocher activer . On enregistre.
- Aller sur l'onglet plugin clic et gestion des plugin. Une fenetre s'ouvre que vous connaissez mais sur la gauche il y a une petite fleche pointant vers la droite (clic dessus)
- Faire ajouter à partir d'une autre source et sélectionner GITHUB
- Rentrer la paramètres suivants dans l'ordre :
* ID logique du plugin: Abeille
* Utilisateur ou organisateur: KiwiHC16
* Nom du dépôt: Abeille
* Branche: master

## Activation

- Activation du plugin
* Allez sur l'interface http Jeedom
* Menu Plugin, Gestion des plugin
* sélectionner Abeille

![](../images/Capture_d_ecran_2018_01_21_a_10_53_37.png)

* Activer

![](../images/Capture_d_ecran_2018_01_21_a_11_05_58.png)

* Choisir le niveau de log et Sauvegarder
* Lancer l'installation des dépendances, bouton Relancer et patienter (vous pouvez suivre l'avancement dans le fichier log: Abeille_dep)

![](../images/Capture_d_ecran_2018_01_21_a_11_06_33.png)

* Quand le statut Dépendance passe à Ok en vert (Patientez 2 ou 3 minutes), définir l objet ID et le port serie puis Démarrer les Démons.

Puis:
> Si vous avez un zigate USB, choisissez le bon port /dev/ttyUSBx.
> Si vous avez une zigate Wifi, choisissez le port "WIFI" dans la liste et indiquer son adresse IP.


![](../images/Capture_d_ecran_2018_01_21_a_11_07_14.png)

* Si vous rafraîchissez la page vous devez voir les fichiers de logs.

![](../images/Capture_d_ecran_2018_01_21_a_11_07_38.png)

A noter: Toute sauvegarde de la configuration provoque une relance du cron du plugin et donc un rechargement de la configuration

- Creation des objets
* Allez dans la page de gestion des objets en sélectionnant le menu plugins, puis protocole domotique, puis Abeille
* Vous devriez voir un premier objet "Ruche" (et éventuellement les objets abeille).

![](../images/Capture_d_ecran_2018_01_21_a_11_55_44.png)

* Si vous allez sur le dashboard

![](../images/Capture_d_ecran_2018_01_21_a_11_07_55.png)

* Tous les autres objets seront créés automatiquement dès détection.

## Utilisation de Jeedom
* Allez sur la page principale et vous devriez voir tous les objets détectés. A cette étape probablement uniquement l'objet Ruche si vous démarrez votre réseau ZigBee de zéro.
* Le nom de l objet est "Abeille-" suivi de son adresse courte zigbee.

*A noter: rafraichir la page si vous voyez pas de changement après une action, par exemple après l'ajout d'un équipement.*


# Tuto

## Presence->Ampoule

Pilotage d une ampoule à partir d'un capteur de présence.

### Inclusion Capteur presence

Aller à la page de configuration du plugin Abeille et clic sur fleche verte pour l inclusion:

![](../images/Capture_d_ecran_2018_10_30_a_10_09_21.png)

Prendre le capteur de presence Xiaomi et faire un appui long (>6s) sur le bouton lateral. Le capteur doit se mettre à flasher et un message d'information doit apparaitre dans jeedom:

![](../images/Capture_d_ecran_2018_10_30_a_10_16_27.png)

Rafraichissez la page pour voir votre capteur:

![](../images/Capture_d_ecran_2018_10_30_a_10_18_23.png)

Vous pouvez changer son nom, je vais lui donner comme nom "Presence" pour la suite.

### Inclusion Ampoule Ikea

Mettre sous tension votre ampoule. Elle doit être allumée pour commencer la manipulation.

Aller à la page de configuration du plugin Abeille et clic sur fleche verte pour l inclusion:

![](../images/Capture_d_ecran_2018_10_30_a_10_09_21.png)

Prendre le capteur de presence Xiomi et faire un appui long (>6s) sur le bouton lateral. Le capteur doit se mettre à flasher et un message d'information doit apparaitre dans jeedom:

En partant de l'ampoule allumée, l'éteindre et la rallumer 6 fois de suite. A la fin de cette opération l'ampoule doit être allumée. Puis elle doit se mettre à clignoter et un message d'information doit apparaitre dans jeedom:

![](../images/Capture_d_ecran_2018_10_30_a_10_27_35.png)

Rafraichissez la page pour avoir votre ampoule:

![](../images/Capture_d_ecran_2018_10_30_a_10_29_10.png)

Vous pouvez changer son nom, je vais lui donner comme nom "Ampoule" pour la suite.

### Pilotage de l ampoule

Nous allons utiliser les scénarios:

![](../images/Capture_d_ecran_2018_10_30_a_10_33_20.png)

Créons un scénario "test" avec pour déclencheur "Presence".

![](..//Capture_d_ecran_2018_10_30_a_10_38_29.png)

Et les actions:

![](../images/Capture_d_ecran_2018_10_30_a_10_40_48.png)

Ici, quand une présence est détectée, on allume l ampoule et quand la présence n'est pas présente on eteint l ampoule.


## Presence->Ampoule<-Telecommande

Dans la configuration précedente, nous allons ajouter une télécommande pour controler l'ampoule.

### Inclusion télécommande

Aller à la page de configuration du plugin Abeille et clic sur fleche verte pour l inclusion:

![](../images/Capture_d_ecran_2018_10_30_a_10_09_21.png)

Prendre la telecommande Ikea et faire 4 appuis sur le bouton OO au dos de la télécommande. La télécommande doit se mettre à flasher rouge en face avant et un message d'information doit apparaitre dans jeedom:

![](../images/Capture_d_ecran_2018_10_30_a_10_58_53.png)

Rafraichissez la page pour voir votre capteur:

![](../images/Capture_d_ecran_2018_10_30_a_10_59_04.png)

Je vais lui donner le nom "Tele" par la suite. A cette étape cet objet Tele dans Jeedom ne peut ête utilisé. Il faut executer les étapes de du chapitre "Simuler la télécommande".

Continuons en configurant l ampoule depuis la Tele:

* Allumer l'ampoule.
* Approcher la télécommande à quelques centimetres de l ampoule
* Appuyer plus de 10s sur le bouton OO au dos de la télécommande: la led rouge sur la face avant de la télécommande doit clignoter et l ampoule doit se mettre à à clignoter.

Ca y est la télécommande pilote l'ampoule et l'ampoule remonte son état à Jeedom.

### Simuler la télécommande

Cette opération est un peu délicate mais doit permettre de récupérer l'adresse de groupe utilisée par la télécommande suite aux opérations ci dessus. Dans le futur j'espere rendre cela automatique.

Aller dans la page de configuration du plugin et clic sur "Network" icon pour faire apparaitre les parametres dans l'Ampoule:

![](../images/Capture_d_ecran_2018_10_30_a_11_30_24.png)

Sur l objet Ampoule vous devez vous le champ "Groups" apparaitre sans information:

![](../images/Capture_d_ecran_2018_10_30_a_11_36_43.png)

Recuperons l'adresse de l ampoule, en ouvrant la page de configuration de l ampoule:

![](../images/Capture_d_ecran_2018_10_30_a_11_42_09.png)

Le champ "Topic Abeille" coontient l adresse, ici "9252".

Interrogeons maintenant l'ampoule, avec un getGroupMemberShip depuis l objet Ruche:

![](../images/Capture_d_ecran_2018_10_30_a_11_45_23.png)

indiquez l'adresse de l ampoule.

Maintenant le champ "Groups" de l ampoule doit contenir l'adresse de groupe:

![](../images/Capture_d_ecran_2018_10_30_a_11_47_24.png)

ici le groupe utilisé par la télécommande est "f65d".

Maintenant nous pouvons mettre à jour la télécommande dans jeedom. Ouvrez les commandes de la Telecommande:

![](../images/Capture_d_ecran_2018_10_30_a_11_50_17.png)

Dans le champ "Topic" des commandes vous pouvez voir le texte \#addrGroup# qu'il faut remplacer par la valeur du groupe, ici "f65d" et sauvegarder.

Cela donne:

![](../images/Capture_d_ecran_2018_10_30_a_11_54_51.png)

Mainteant vous pouvez commander votre ampoule depuis la Télécommande physique et depuis la Télécommande Jeedom.

![](../images/Capture_d_ecran_2018_10_30_a_11_58_42.png)

PS: Les scénarios ne sont pas implémentés pour l'instant (30/10/2018):

* Sc1, Sc2, SC3 sur la télécommande dans Jeedom,
* et les boutons "Fleche Gauche", "Fleche Droite" de la télécommande physique.


# Ajout d'équipement en général

Cette page présente une introduction à l'ajout d'équipement. Sinon pour chaque équipement, aller voir la page dédiée.

=== Ajout d'équipements (S'ils ne sont pas déjà dans le réseau ZigBee)

* Mettre la gateway en inclusion (Bouton Inclusion), la Led bleue de la zigate doit clignoter,

![](../images/Capture_d_ecran_2018_01_21_a_11_07_55.png)

puis:

* Ampoule IKEA: faire un reset de l'ampoule en partant de la position allumée, puis 6 fois, éteindre-allumée. Il n'est pas facile d'avoir le reset... et après mainte tentatives, vous devriez récupérer l'ampoule dans Jeedom. Autre solution bien plus simple utiliser une télécommande Philips (Hue Dimmer Switch) et forcer le reset par un bouton I + bouton O appuyés suffisamment longtemps. Une fois associée, il est possible d'avoir besoin d'éteindre, attendre 10 secondes et allumer.

![](../images/Capture_d_ecran_2018_01_21_a_11_13_44.png)

* Capteur de porte, prise, capteur de temperature rond/carre, bouton et capteur infrarouge Xiaomi, un appuie long (plus de 6s, led flash, attendre plusieurs flash avant de lacher) sur le bouton sur le côté. Et vous devriez récupérer l'objet dans Jeedom. Un appuie long provoque l'association/join network si l objet n est pas déjà sur un réseau, si déjà sur réseau provoque un "leave" (a faire quand le réseau est fermé sinon juste après le leave l object refait une association et reste sur le réseau.).

Porte

![](../images/Capture_d_ecran_2018_01_21_a_11_11_38.png)

Temperature rond

![](../images/Capture_d_ecran_2018_01_21_a_11_12_43.png)

Temperature Carre

![](../images/Capture_d_ecran_2018_01_21_a_11_12_15.png)

Bouton

![](../images/Capture_d_ecran_2018_01_21_a_11_13_15.png)

Etat: passe à 1 quand vous appuyez sur le bouton. Deux, Trois et Quatres appuies apparaissent dans le champ multi.

Capteur Presence InfraRouge

![](../images/Capture_d_ecran_2018_01_21_a_12_45_22.png)

* Objet inconnu: Si le type d'objet n'est pas connu, Abeille va créer un objet vide.

![](..//Capture_d_ecran_2018_01_21_a_12_49_06.png)

=== Ajout d'équipements (S'ils sont déjà dans le réseau ZigBee)

* Ampoule IKEA: éteindre, attendre 15 secondes et allumer électriquement l'ampoule et elle doit apparaître dans Jeedom.
* Capteur de porte, capteur de temperature rond/carre et bouton Xiaomi: un appuie rapide sur le bouton latérale et il doit apparaître dans Jeedom.
* Capteur InfraRouge Xiaomi: pas implémenté.



# Integration d'un nouvel objet

== Ikea

=== Ampoule

==== Nouvelle

Faire un reset (6s On/Off-On ou reset avec télécommande Philips) de l'ampoule alors que la zigate est en mode inclusion. L'ampoule doit s'associer. Elle n'envoie pas son nom automatiquement et jeedom ne peut pas créer l'objet associé. Pour créer l'objet suivre le paragraphe "Déjà" associée.

==== Deja associé

Eteindre l'ampoule 15s puis la rallumer (Zigate en fonctionnement normale).

=== Telecommande 5 boutons

==== Nouvelle

Plugin en mode semi-automatique, appui 4 fois sur bouton arrière télécommande et un objet "Type inconnu" est créé.

==== Déjà associée

Plugin en mode semi auto et "Liste Equipement" sur Ruche.

== Philips Hue

=== Hue Go

Impossible de faire émettre le moindre message ZigBee par le Hue Go en utilisant le bouton à l'arrière. Seulement ce qui ressemble à un reset en appuyant très longtemps (>30s).

==== Nouvelle

a

==== Déjà associée

Utiliser la fonction "Get Name" de la Ruche. Mettre en Titre l'adresse de la Hue Go et en Message le EndPoint qui est "0B". Clic Get Name et la apres un refresh dashboard, l ampoule doit apparaitre.

== Xiaomi

=== Nouvel Equipement

Zigate en mode inclusion, Plugin en automatique, appui long de 7s sur le bouton du flanc de l'équipement, l'équipement doit se connecter et un objet doit apparaître dans le dashboard.

=== Déjà associé

Zigate en mode normal, plugin en mode automatique, un appui court sur le bouton du flanc de l'équipement et l'objet abeille doit être créé.

== OSRAM (Prise Smart +)

=== Nouvel Equipement

Zigate en mode inclusion, Plugin en automatique, appui long sur le bouton du flanc de la prise, la prise switche rapidement On/Off, lâcher le bouton, l'équipement doit se connecter et un objet doit apparaître dans le dashboard.

![](../images/plug01_new.png)

=== Déjà associé

non disponible actuellement - nouvelle inclusion à faire

=== Ajout dans un groupe (avec Ampoules Hue ou Ikea par exemple)
Utiliser la Fonction Add Group de la Ruche
Titre : Adresse courte de la prise
Message : DestinationEndPoint=03&groupAddress=aaaa
avec "aaaa" le nom du groupe souhaité.

![](../images/plug01_addgroup.png)

Video:

![](../videos/GroupeAjoutSupprime.mov)



# Ikea

## Ampoule

### Bouton Identify

Ce bouton est créé au moment de la création de l'objet. Celui ci permet de demander à l'ampoule de se manifester. Elle se met à changer d'intensité ce qui nous permet de la repérer dans une groupe d'ampoules par exemple.

### Creation objet

- Si l'ampoule n'est pas associée à la zigate, avec Abeille en mode Automatique, une association doit provoquer la création de l'obet dans Abeille

- Si l'ampoule est déjà associée à la zigate, avec Abeille en mode Automatique,
* l'allumage électrique doit provoquer l'envoie par l'ampoule de sa présence (annonce) et la création par Abeille de l'objet associé.
* l'extinction électrique pendant 15s puis allumage électrique doit provoquer l'envoie par l'ampoule de sa présence (son nom) et la création par Abeille de l'objet associé.
* Vous pouvez aussi Utiliser la commande getName dans la ruche, mettre l’adresse courte dans le titre et rien dans le message. Puis rafraichir le dashboard et la l’ampoule doit être présente.

### Retour d'état

Pour que l'ampoule puisse remonter automatiquement son état à Jeedom, il faut mettre en place un "bind" et un "set report".

Maintenant c'est automatique mais si cela ne fonctionnait pas il y a toujours la vieille methode.

Pour se faire, il faut utiliser les commandes bind et setReport sur l'objet Ampoule.

Le widget ampoule doit être plus ou moins comme cela:

![](../images/Capture_d_ecran_2018_10_12_a_16_51_39.png)

Il faut faire apparaite les commandes de configuration. Aller dans la page de configuration du plugin et selectionner "Network" dans le chapitre "Affichage Commandes". Maintenant le widget doit ressembler à:

![](../images/Capture_d_ecran_2018_10_12_a_16_58_44.png)

il suffit de faire un BindShortToZigateEtat, setReportEtat. Si votre ampoule supporte la variation d'intensité, faites un BindShortToZigateLevel, setReportLevel.

ur que cela fonctionne il est important que le champ IEEE soit rempli. Si tel n'est pas le cas faites un Liste Equipement sur la ruche et si cela ne suffit pas faire un "Recalcul du Cache" dans Network List de la page de conf du plug in.

> Y a encore du travail en cours pour simplifier cette partie.


### Bind specifique:

Identifiez l'ampoule que vous voulez parametrer:

![](../images/Capture_d_ecran_2018-02_21_a_23_26_56.png)

Récuperer son adresse IEEE, son adress courte (ici 6766).

De même, dans l'objet Ruche récupérez l'adresse IEEE (Si l'info n'est pas dispo, un reset de la zigate depuis l objet ruche doit faire remonter l'information).

Mettre dans le champ:

- Titre, l'adresse IEEE de l'ampoule que vous voulez parametrer
- Message, le cluster qui doit être rapporté, et l adresse IEEE de la zigate.

![](../images/Capture_d_ecran_2018_02_21_a_23_26_49.png)

Attention a capture d'écran n'est pas à jour pour le deuxieme champs.

Dans message mettre:
```
targetExtendedAddress=XXXXXXXXXXXXXXXX&targetEndpoint=YY&ClusterId=ZZZZ&reportToAddress=AAAAAAAAAAAAAAAA
````

Exemple avec tous les parametres:
````
targetExtendedAddress=90fd9ffffe69131d&targetEndpoint=01&ClusterId=0006&reportToAddress=00158d00019a1b22
````


Après clic sur Bind, vous devriez voir passer dans le log AbeilleParse (en mode debug) un message comme:

![](../images/Capture_d_ecran_2018_02_21_a_23_27_29.png)

qui confirme la prise en compte par l'ampoule. Status 00 si Ok.


#### Rapport Manuel:

Ensuite parametrer l'envoie de rapport:

- Titre, l adresse courte de l'ampoule
- Message, le cluster et le parametre dans le cluster

![](../images/Capture_d_ecran_2018_02_21_a_23_29_11.png)

Attention a capture d'écran n'est pas à jour pour le deuxieme champs.

````
targetEndpoint=01&ClusterId=0006&AttributeType=10&AttributeId=0000 pour retour d'état ampoule Ikea

targetEndpoint=01&ClusterId=0008&AttributeType=20&AttributeId=0000 pour retour de niveau ampoule Ikea
````


De même vous devriez voir passer dans le log AbeilleParse (en mode debug) un message comme:

![](../images/Capture_d_ecran_2018_02_21_a_23_29_49.png)

qui confirme la prise en compte par l'ampoule. Status 00 si Ok.

Après sur un changement d'état l'ampoule doit remonter l'info vers Abeille, avec des messages comme:

![](../images/Capture_d_ecran_2018_02_21_a_23_31_11.png)

pour un retour Off de l'ampoule.

=== Gestion des groupes

Vous pouvez adresser un groupes d'ampoules (ou d'équipements) pour qu'ils agissent en même temps.

Pour se faire sur l'objet ruche vous avez 3 commandes:

![](../images/Capture_d_ecran_2018_03_07_a_11_32_21.png)

* Add Group: permet d'ajouter un groupe à l'ampoule. Celle ci peut avoir plusieurs groupes et réagira si elle recoit un message sur l'un de ces groupes.

![](../images/Capture_d_ecran_2018_03_07_a_11_38_19.png)

Le DestinatioEndPoint pour une ampoule Ikea est 01. Pour le groupe vous pouvez choisir. Il faut 4 caractères hexa (0-9 et a-f).

* Remove Group: permet d'enlever l'ampoule d'un groupe pour qu'elle ne réagisse plus à ces commandes.

![](../images/Capture_d_ecran_2018_03_07_a_11_44_50.png)

*getGroupMembership: permet d'avoir la liste des groupes pour lesquels l'ampoule réagira. Cette liste s'affiche au niveau de l'ampoule, exemple avec cette ampoule qui va repondre au groupe aaaa et au groupe bbbb.

![](../images/Capture_d_ecran_2018_03_07_a_11_43_14.png)
![](../images/Capture_d_ecran_2018_03_07_a_11_41_21.png)


## Telecommnande Ronde 5 boutons

### Télécommande réelle

(Pour l'instant c'est aux équipements qui recevoient les demandes de la telecommande reelle de renvoyer leur etat vers jeedom, sur un appui bouton telecommande, la ZiGate ne transmet rien au plugin Abeille, à partir du firmware 3.0f on peut récupérer des appuis sur les boutons de la télécommande avec une configuration spécifique, voir ci dessous).

Pour créer l'objet Abeille Automatiquement,

[line-through]#- Premiere solution: faire une inclusion de la télécommande et un objet doit être créé.
Ensuite paramétrer l'adresse du groupe comme indiqué ci dessous (voir deuxieme solution).#


- Deuxieme solution, il faut connaitre l'adresse de la telecommande (voir mode semi automatique pour récupérer l'adresse).

Puis dans la ruche demander son nom. Par exemple pour la telecommande à l'adress ec15

![](../images/Capture_d_ecran_2018_02_28_a_13_59_31.png)

et immédiatement apres appuyez sur un des boutons de la télécommande pour la réveiller (pas sur le bouton arriere).

Et apres un rafraichissement de l'écran vous devez avoir un objet

![](../images/Capture_d_ecran_2018_02_28_a_14_00_58.png)

Il faut ensuite editer les commandes en remplacant l'adresse de la télécommande par le groupe que l on veut controler

La configuration

![](../images/Capture_d_ecran_2018_02_28_a_14_03_26.png)

va devenir

![](../images/Capture_d_ecran_2018_02_28_a_14_03_47.png)

pour le groupe 5FBD.

##### 4x sur bouton arriere provoque association

Association
Device annonce
Mais rien d'autre ne remonte, il faut interroger le nom pour créer l objet.

##### 4x sur bouton arriere provoque Leave

Si la telecommande est associée, 4x sur bouton OO provoque un leave.

##### Recuperer le group utilisé par une télécommande

Avoir une télécommande et une ampoule Ikea sur le même réseau ZigBee. Attention l'ampoule va perdre sa configuration. Approcher à 2 cm la télécommande de l'ampoule et appuyez pendant 10s sur le bouton à l'arriere de la telecommande avec le symbole 'OO'. L'ampoule doit clignoter, et relacher le bouton. Voilà la télécommande à affecté son groupe à l'ampoule Il suffit maintenant de faire un getGroupMemberShip depuis la ruche sur l'ampoule pour récupérer le groupe. Merci a @rkhadro pour sa trouvaille.


>Il existe un bouton « link » à côté de la pile bouton de la télécommande. 4 clicks pour appairer la télécommande à la ZiGate. Un appuie long près de l’ampoule pour le touchlink.


### Télécommande Virtuelle

La télécommande virtuelle est un objet Jeedom qui envoies les commandes ZigBee comme si c'était une vrai télécommande IKEA.

Utiliser les commandes cachées dans la ruche:

* Ouvrir la page commande de la ruche et trouver la commande "TRADFRI remote control".

![](../images/Capture_d_ecran_2018_03_02_a_10_34_40.png)

Remplacez "/TRADFRI remote control/" l'adresse du groupe que vous voulez controler. Par exemple AAAA.

![](../images/Capture_d_ecran_2018_03_02_a_10_35_08.png)

Sauvegardez et faites "Tester".

Vous avez maintenant une télécommande pour controler le groupe AAAA.

![](../images/Capture_d_ecran_2018_03_02_a_10_35_28.png)

### Récupération des appuis Télécommande Ikea dans Abeille

Après avoir récupéré le groupe utilisé par la télécommande, vous pouvez ajouter la ZiGate à ce groupe ainsi abeille recevra les demandes de la télécommande. Attention la zigate est limitée à 5 groupes soit disons 5 télécommandes.

Pour ce faire dans Abeille, ajouter les groupes à l'objet "Ruche" qui représente la zigate.

Vous pouvez aussi forcer le groupe utilisé par la télécommande en selectionnant la télécommande ikea, en mettant le groupe dans le champ Id puis clic sur le bouton "Set Group Remote" et dans la seconde qui suis en appuyant sur un bouton de la télécommande pour la réveiller. Il peut être nécessaire de le faire plusieurs fois du fait du timing un peu spécifique.

C'est aussi valide pour le bouton On/Off Ikea.

https://github.com/fairecasoimeme/ZiGate/issues/6


|Button   |Pres-stype  |Response  |command       |attr|
|---------|------------|----------|--------------|---------------------------------------|
|down     |click       |0x8085    |0x02          |None|
|down     |hold        |0x8085    |0x01          |None|
|down     |release     |0x8085    |0x03          |None|
|up       |click       |0x8085    |0x06          |None|
|up       |hold        |0x8085    |0x05          |None|
|up       |release     |0x8085    |0x07          |None|
|middle   |click       |0x8095    |0x02          |None|
|left     |click       |0x80A7    |0x07          |direction: 1|
|left     |hold        |0x80A7    |0x08          |direction: 1    => can t get that one|
|right    |click       |0x80A7    |0x07          |direction: 0|
|right    |hold        |0x80A7    |0x08          |direction: 0    => can t get that one|
|left/right |release   |0x80A7    |0x09          |None            => can t get that one|

down = brightness down, up = brightness up,
middle = Power button,
left and right = when brightness up is up left is left and right is right.
Holding down power button for ~10 sec will result multiple commands sent, but it wont send any hold command only release.
Remote won't tell which button was released left or right, but it will be same button that was last hold.
Remote is unable to send other button commands at least when left or right is hold down.

Reponse 0x8085 correspond à l'info Up-Down dans le widget.
Reponse 0x8095 correspond à l'info Click-Middle dans le widget.
Reponse 0x80A7 correspond à l'info Left-Right-Cmd et Left-Right-Direction dans le widget.

A partir de la vous pouvez déclancher des scénarii dans Jeedom. Attention lors de l utiliation de la telecommande, la telecommande dans abeille sera mis a jour et vos scenarii déclanchés mais si vour avez des équiements zigbee sur ce groupe ils seront aussi activés. Par exemple vous pouvez avoir une Ampoule Ikea sur le groupe de la télecommande qui reagira aux demandes de la télécommande directement en zigbee (meme si jeedom est HS) et avoir un scenario qui se déclenche en même temps pour ouvrir les volets en zwave ou autre.


## Gradateur

### Un clic sur OO

Un clic sur OO envoie un Beacon Request. Même si la zigate est en inclusion, il n'y a pas d'association (Probablement le cas si deja associé à una utre reseau).

### 4 clics sur OO

Message Leave, puis Beacon Requets puis association si réseau en mode inclusion. Une fois associé, un getName avec un reveil du gradateur permet de recuperer le nom.

Voir la telecommande 5 boutons pour avoir plus de details sur le controle de groupe,...

# Philips Hue

##  Philips Hue White

### Association

- Ampoule neuve Hue White, Abeille en mode Inclusion, branchement de l'ampoule. L'ampoule s'associe et envoie des messages "annonce" mais pas son nom. Si vous faites un getName avec son adresse courte dans le champ Titre et 0B (destinationEndPoint) dans le champ Message, alors elle doit répondre avec son nom, ce qui va créer l'objet dans le dashboard (rafraichir).


### Si deja associé sur ce réseau

- Si l’ampoule est déjà associée à la zigate, avec Abeille en mode Automatique,

* l’extinction électrique pendant 15s puis allumage électrique doit provoquer l’envoie par l’ampoule de sa présence et la création par Abeille de l’objet associé.

* Utiliser la commande getName dans la ruche, mettre l'adresse courte dans le titre et 03 (destinationndPoint) dans le message. Puis rafraichir le dashboard et la l'ampoule doit être présente.

### Si déjà aassociée sur un autre réseau

- Télécommande Hue, Bouton "I" et "0", pour reset factory new de  l'ampoule avec la zigate en mode inclusion. L'objet dans Abeille doit se créer tout seul.

## Philips Hue Go

### Association

### Si deja associé

* tres long appui sur le bouton arriere de l ampoule plus de 40s probablement avec la zigate qui n'est pas en mode inclusion. La lampe se met a flasher. Elle s'est deconnectée du réseau. Mettre la zigate en Inclusion et la lampe envoie des messages "annonce" et elle doit se créer dans Abeille.

### Colour Control

Sur un objet ampoule vous pourrez trouver la commande Colour:

![](../Capture_d_ecran_2018_02_13_a_23_07_50.png)

Dans le premier champ indiquez la valeur X et dans le deuxième champ la valeur Y.

Par exemple:

* 0000-0000 -> Bleu
* FFFF-0000 -> Rouge
* 0000-FFFF -> Vert

### Group Control

![](../Capture-d_ecran_2018_02_14_a_11_15_18.png)

Avec ca je commande la Philips Hue depuis télécommande Ikea ronde 5 boutons ...

## Telecommande / Philips Hue Dimmer Switch

### Association

Appuie avec un trombone 5x sur le bouton "setup" en face arriere "setup" avec la zigate en mode Inclusion. Un objet télécommande doit être créé dans Abeille.


### Récupérer le groupe utilisé

Approcher la telecommande d'une ampoule de test qui est sur le reseau. Faire un appui long >10s sur le I de la télécommande. Attendre le clignotement de l'ampoule. Ca doit être bon. Si vous appuyé sur I ou O, elle doit s'allumer et s'éteindre. Et les bouton lumière plus et moins doivent changer l'intensité. Ensuite vous pouvez récupérer le groupe en interrogeant l'ampoule depuis la ruche avec un getGroupMembership.

### Reset d une ampoule

Si vous appuyez, sur I et O en même temps à moins de quelques centimetres, l'ampoule doit faire un reset et essayer de joindre un réseau. Si la zigate est en mode inclusion alors vous devez récurerer votre ampoule. Ca marche sur des ampoules Hue et Ikea, probablement pour d autres aussi.

### Bouton de la telecommande dans Abeille

5 appuis sur bouton "setup" au dos avec un trombone et la télécommande s'appaire si la zigate en mode inclusion.
Si attachée au réseau et un appui long sur "I", elle s'annonce et donc si elle n'existe pas dans abeille elle est crée.

Dans l objet Abeille vous allez trouver:

8 informations. 4 boutons x 2 infos (event, duree). Ce sont les informations qui remontent de a telecommande quand vous l'utilisez. Cela permet à Jeedom de savoir qu'un bouton a été utilisé et vous pouvez créer les scénario que vous voulez.
* 4 Boutons: "I", "LumPlus", "LumMoins", "O".
* 4 types events: "Appui Court = 0", "Appui Long = 1", "Relache appui court = 3", "Relache Appui Long = 4"
* Durée, indique le temps d'appui d'un bouton (Il n'y pas de temps de nom appui).

5 icones (On,Off,Toggle,Lumiere plus, Lumiere moins) pour simuler la telecommande depuis Jeedom. C'est Jeedom qui envoie les commandes à la place de la telecommande. Pour se faire renseigner le champ "Groupe" dans la configuration.

#### Prise de controle d'une ampoule

- Ampoule Hue White deja associé au reseau, idem pour la telecommande. Mettre la telecommande proche de l ampoule et appui sur "I" assez longtmeps. L ampoule clignote et est configurée. Apres l ampoule est pilotable par la telecommande. On peut recuperer le groupe utilisé sur l ampoule dans jeedom.
- Ampoule Ikea. La configuration depuis la telecommande et le bouton 'I'ne semble pas fonctionner. L'ampoule clignote mais configuration ne se produit pas. Mais si on recupere le groupe comme indiqué au paragraphe preceent et qu'on defini ce groupe dans l ampoule Ikea, alors l ampoule repond aux commandes des la telecommande. J'ai un doute sur le fait que la telecommande est un groupe par defaut. J'ai l impression quelle a commence a utiliser un groupe quand elle a programmer la premiere ampoule Hue.

# Profalux

## Inclusion d'un volet

Comme pour tous modules ZigBee et pour bien comprendre la procédure, il faut savoir que :

La ZiGate est un coordinateur ZigBee qui permet de contrôler / créer un réseau. De la même manière que le couple télécommande / ampoule ZigBee, il est important que les deux matériels appartiennent et soient authentifiés sur le même réseau.

N’ayant pas de boutons ou d’interfaces, un volet Profalux Zigbee ne peux pas rentrer tout seul sur un réseau ZigBee. Il est indispensable d’avoir une télécommande maître qui jouera le rôle d’interface entre le volet et la ZiGate.

A savoir tout au long de cette procédure : lorsque le volet fait un petit va et vient c'est le signe que la commande a bien été reçue.

### Étape 1: Remise à zéro de la télécommande et du volet.

* Retourner la télécommande.
* A l’aide d’un trombone, appuyer 5 fois sur le bouton R.

La télécommande va clignoter rouge puis vert.

![](../images/profalux_inclusion_etape1.png)

Le volet va faire un va et vient (attendre un petit moment).

Attendre que la télécommande ne clignote plus.

* Dans la minute qui suit, appuyer sur STOP.

Le volet va faire un va et vient.

Pour tester le bon fonctionnement, vous devriez pouvoir piloter le volet avec la télécommande.

Si jamais les commandes de votre volet sont inversées, il suffit à l'aide d'un trombone d'appuyer sur fois sur F et ensuite une fois sur STOP.

* Fermer le volet complètement.

### Étape 2 : Inclusion du volet

Mettre la ruche en mode inclusion

* Pour cela appuyer sur le bouton inclusion depuis le plugin Abeille (La Zigate doit clignoter bleue)

![](../images/inclusion.PNG)

Une fois le réseau de la ZiGate ouvert, il ne vous reste plus qu’à:

* Retourner votre télécommande
* Appuyer 1 fois sur R
* Appuyer ensuite sur la flèche du haut
Le moteur devrait faire plusieurs va et vient …
Attendre que la télécommande ne clignotte plus !

* Pour finir, appuyer sur la touche STOP de la télécommande.

* Ouvrir et fermer votre volet complètement 2 fois.

A la fin de la 2ème fermeture, le volet fera un petit va et vient.

Faites un rafraichissement de votre dashboard et votre volet devrait apparaitre !!!



## Résolution de problèmes:

### Le volet ne répond plus à la télécommande.

Si par une mauvaise manipulation votre volet ne répond plus à la télécommande, il est nécessaire de faire un reset de la télécommande et du volet.

* Retourner l’appareil
* A l’aide d’un trombone, appuyer 5 fois sur le bouton R

![](..//profalux_inclusion_etape1.png)

Attention c'est une manipulation dangereuse !

* Couper l'alimentation électrique
* Réunir les fils noir et marron puis les brancher sur la borne de phase

![](../images/profalux_inclusion_reset_volet2.png)

* Remettre l'alimentation électrique pendant 5 secondes. Le volet devrait faire un petit mouvement.
* Couper l'alimentation électrique
* Séparer les fils noir et marron. Brancher le fils marron sur la phase. Si votre fils noir était brancher avec le bleu aupparavant, rebrancher le avec le bleu sinon laisser le fils noir seul en pensant à l'isoler(capuchon noir)

![](../images/profalux_inclusion_reset_volet3.pn)

* Remmettre l'alimentation électrique et dans la minute appuyer sur le bouton stop

![](../images/profalux_inclusion_reset_volet4.png)

Le volet devrait faire des mouvement de va-et-vient puis s'arrêter
* La télécommande devrait à nouveau fonctionner
* Recommencer à nouveau la procédure d'inclusion


# Xiaomi

## Temperature Carré, Temperature Rond, Bouton Carré, Sensor Door, Presence IR

### Creation objet

Si l'équipement n'est pas associée à la zigate, avec Abeille en mode Automatique, une association doit provoquer la création de l'obet dans Abeille

### Si deja associé

Si l'équipement est déjà associée à la zigate, avec Abeille en mode Automatique, un appui court sur le bouton latéral doit provoquer l'envoie d'un message pour signaler sa présence et la création par Abeille de l'objet associé.

## Prise

### Creation objet

Si l'équipement n'est pas associée à la zigate, avec Abeille en mode Automatique, une Inclusion doit provoquer la création de l'obet dans Abeille


### Si deja associé

Si l'équipement est déjà associée à la zigate, avec Abeille en mode Automatique, un appui long (7s) sur le bouton latéral doit provoquer l'envoie d'un message Leave (la prise se deconnecte du reseau) puis la prise envoie des messages "annonce" pour signaler sa présence. Mettre en mode Inclusion la Zigate et la création par Abeille de l'objet associé doit se faire.

## Bouton Rond (lumi.sensor_switch)

### Appui court (<1s) sur bouton arriere avec trombone

Remonte un champ ff02 avec 6 elements (Pas recu par le parser, Remontée batterie sensor presence Xiaomi #141, devrait être dans ZiGate, Fixed in next version (3.0e) )
Puis son nom lumi.sensor_switch

### Fonctionnement

Ce bouton envoie un message lors de l'appui mais aussi lors du relachement. L'état dans Abeille/Jeedom reflete l'état du bouton.

### Scenario

Sur reception d'un changement l'état, un scénario peut être lancé et la valeur de l'état peut être testée lors du déroulement du scénario.

## Bouton Carre (lumi.sensor_switch.aq2)

### Appui court (<1s) sur bouton lateral

Remonte son nom et attribut ff01 (longueur 26) qui est décodé par le parser.

### Fonctionnement

#### Etat

Contrairement au bouton rond ci dessus, le bouton carré n'envoie pas d'information sur l'appui. Il envoie l'information que sur la relache.

Afin d'avoir le visuel sur le dashboard, l'état passe à 1 sur la reception du message et jeedom attend 1 minute avant de le remettre à 0.

#### Multi

Pour l'information multi, celle ci remonte quand on fait plus d'un appui sur le bouton. Multi prend alors la valeur remontée. Le bouton n'envoie pas d'autre information et donc la valeur reste indéfiniment. Par defaut l'objet créé demande à jeedom de faire un retour d'état à 0 apres une minute. Cela peut être enlevé dans les parametres de la commande.

### Scenario

### Etat

Du fait de ce fonctionnement, nous ne pouvons avoir une approche changement d'état. Il faut avoir une approche evenement. De ce fait la gestion des scenariis est un peu differente du bouton rond.

Par défaut le bouton est configuré pour déclencher les scenariis à chaque appui (même si l'etat était déjà à 1). Mais Jeedom va aussi provoquer un evenement au bout d'une minute en passant la valeur à 0.

Lors de l'execution du scenario, si vous testé l'état du bouton est qu'il est à un vous avez recu un evenement appui bouton, si l'état est 0, vous avez recu un evenement retour à zero apres une minute.

Par exemple pour commander une ampoule Ikea:

![](../images/Capture_d_ecran_2018_09_04_a_13_05_49.png)

![](../images/Capture_d_ecran_2018_09_04_a_13_05_.36.png)

### Multi

Le fonctionnement de base va provoquer 2 événements, un lors de l'appui multiple, puis un second après 1 minute (généré par Jeedom pour le retour d'état). Si vous enlevez de la commande le retour d'état alors vous n'aurez que l'événement appui multiple.
Par defaut, en gros, le scenario se declenche et si vous testez la valeur multi > 1, c'est un evenement appui multiple et si valeur à 0 alors evenement jeedom de retour d etat.

## Capteur Inondation (lumi.sensor_wleak.aq1)

### Appui court (<1s) sur le dessus

Remonte son nom et attribut ff01 (longueur 34)

## Capteur de Porte Ovale (lumi.sensor_magnet)

### Appui court (<1s) avec un trombone

Remonte un champ ff02 avec 6 elements (Pas recu par le parser, Remontée batterie sensor presence Xiaomi #141, devrait être dans ZiGate, Fixed in next version (3.0e) )
Puis on son nom lumi.sensor_magnet

## Capteur Porte Rectangle (lumi.sensor_magnet.aq2)

### Appui court (<1s) sur bouton lateral

Remonte son nom et ff01 (len 29)

### Appui Long (7s) sur bouton lateral

Apparaige
Remonte son nom et Application Version
Remonte ff01 (len 29)


## Capteur Presence V1 (lumi.sensor_motion)

### Appui court (<1s) avec trombone

#### Appui long (7s) avec trombone

Appairage
Remonte son nom
Remonet Appli Version
Remonte ff02 avec 6 elements (Pas recu par le parser, Remontée batterie sensor presence Xiaomi #141, devrait être dans ZiGate, Fixed in next version (3.0e) )

#### Double flash bleu sans action de notre part

Visiblement quand le sensor fait un rejoin apres avoir perdu le reseau par exemple, il fait un double flah bleu.

## Capteur de Presence V2

### Appui court (<1s) sur bouton lateral

Remonte son nom et FF01 de temps en temps.

### Appui long (7s) sur bouton lateral

Leave message
Appairage
Remonte son nom et SW version
Remonte FF01 (len 33)

### Comportement

Il remonte une info a chaque detection de presence et remonte en même temps la luminosité. Sinon la luminosité ne remonte pas d'elle même. Ce n'est pas un capteur de luminosité qui remonte l info périodiquement.

## Capteur Temperature Rond (lumi.sensor_ht)

### Appui court (<1s) sur bouton lateral

Remonte son nom

### Appui long (7s) sur bouton lateral

Apparaige
Remonte son nom et appli version
Remonte ff01 (len 31)


## Capteur Temperature Carré (lumi.weather)

#### Appui court (<1s) sur bouton lateral

Si sur le réseau: Remonte son nom
Si hors réseau et Zigate pas en Inclusion: Un flash bleu puis un flash bleu unique
Si hors réseau et Zigate en Inclusion: Un flash bleu, pause 2s, 3 flash bleu

### Appui long (7s) sur bouton lateral

Leave
Apparaige
Remonte son nom et appli version
Remonte ff01 (len 37)

### Info

Rapport:

- petite variation de temperature ou humidité, rapport one fois par heure
- Si variation de plus de 0,5°C ou de plus de 6% d'humidité aors rapport immédiat

Précision (Source Appli IOS MI FAQ Xiaomi)

- Temperature +-0,3°C
- Humidité +-3%

## Xiaomi Cube Aqara

![](../images/Capture_d_ecran_2018_06_12_a_22_00_03.png)

## Wall Switch Double Battery (lumi.sensor_86sw2)

### Appui long (7s) sur bouton de gauche

Apparaige
Remonte son nom et appli version
Remonte ff01 (len 37)

### getName

Il repond au getName sur EP 01 si on fait un appuie long sur l'interrupteur de droite (7s) et pendant cette periode on fait un getName depuis la ruche.

### Appui Tres Long (>10s) sur bouton de gauche

Leave


## Wall Switch Double 220V Sans Neutre (lumi.ctrl_neutral2)

### Appui long (7s) sur bouton de gauche

Apparaige
Remonte son nom et appli version
Remonte d autres trucs mais je ne sais plus ...

### getName

Il repond au getName sur EP 01 s.

### Appui Tres Long (>8s) sur bouton de gauche

Leave

## Capteur Vibration

### Appui long (7s) sur bouton de gauche

Apparaige
Remonte son nom et appli version
Remonte d autres trucs mais je ne sais plus ...

### Attribute 0055

Il semblerai qu'une valeur:

* 1 indique une detection de vibration
* 2 indique un rotation
* 3 indique une chute

### Attribute 0503

Pourrait être la rotation apres l envoie de l'attribut 0055 à la valeur 2

### Attribut 0508

Inconnu, est envoyé après attribut 0055.

## Capteur Smoke

### 3 appuis sur le bouton de facade

Après avoir mis la zigate en mode inclusion, 3 appuis sur le bouton en facade permet de joindre le réseau.

La même action, 3 appuis, alors que la zigate n'est pas en mode inclusion permet de quitter le réseau.

### Sensibilité du capteur

Il est possible de définir le seuil de détection du capteur: 3 niveaux (En dev).

### Test du capteur

Avec le bouton tester, vous envoyez un message au capteur qui doit réagir avec un bip sonnore (3 messages envoyés par abeille, il doit y avoir entre 1 et 3 bips).

### Réveil

Le capteur se réveille toutes les 15s pour savoir si la zigate à des infos pour lui.

## Capteur Gaz

### Appairage

### Routeur

Ce capteur est un router.

### Parametres

Vous pouvez choisir le niveau de sensibilié: Low - Moyen - High

### Tester la bonne connection au réseau

Avec le bouton tester, vous envoyez un message au capteur qui doit réagir avec un bip sonnore (3 messages envoyés par abeille, il doit y avoir 3 bips à 5s d'intervalles).

# OSRAM

## Plug (Smart+)

A) Prise neuve: Mettre en Inclusion la zigate et brancher la prise OSRAM. Elle devrait joindre le réseau immédiatement et un objet doit être créé dans Abeille.

B) Prise associée à un autre réseau: Si la prise était déjà associé à un réseau, un appui long (> 20s) sur le bouton lateral doit provoquer l'association (Zigate en mode inclusion) et la création de l objet dans Abeille/Jeedom.

C) Prise associée à la zigate mais pas dobjet dans Abeille: voir méthode B).

### Retour d etat

Afin de configurer le retour d'état il faut avoir:
- l'adresse IEEE sur l objet prise OSRAM
- et sur l'objet ruche

Si ce n'est pas le cas vous pouvez faire un "liste Equipements" sur la ruche. Si cela ne suffit pas il faut faire "menu->Plugins->Protocol domotique->Abeille->Network List->Table de noeuds->Recalcul du cache" (Soyez patient).

Ensuite utilisez de préférence "BindShortToZigateEtat" puis "setReportEtat". Maintenant un changement d'état doit remonter tout seul et mettre à jour l'icone.

![](../images/Capture_d_ecran_2018_06_27_a_11_24_09.png)


> Le retour d'état ne remonte que si l'état change. Donc si l'icone n'est pas synchro avec la prise vous pouvez avoir l'impression que cela ne fonctionne pas. Ex: la prise est Off et l'icone est on. Vous faites Off et rien ne se passe. Pour éviter cela un double Toggle doit réaligner tout le monde.


## Ampoule E27 CLA 60 RGBW OSRAM (Classic E27 Multicolor)

Ampoule Neuve:
Mettre en Inclusion la zigate et brancher l'ampoule OSRAM. Elle devrait joindre le réseau immédiatement et un objet doit être créé dans Abeille.

Ampoule déjà connectée à un réseau:
Physically reset your light by either the lamp switch (as long as not a three way switch) or by unplugging your power cord 5 times & plugging it back in as so the light turns on (waiting 3 sec in between cycles). After the 5th time your light will confirm reset by blinking & changing colors. Thanks to: https://community.smartthings.com/t/how-to-reset-a-osram-lightify-a19-bulb-without-deleting-from-your-st-hub/40691

Partir de l ampoule allumer, (Eteindre/Allumer) 5 fois toutes les 3 secondes et elle doit essayer de joindre le réseau et fair une flash.
https://www.youtube.com/watch?v=PaA0DV5BXH0

Ne semble pas fonctionner avec la Télécommande Hue (Hue Dimmer Switch).


# Groupes

== Intro

Les équipements peuvent être adressés par au moins deux façons:

* Adresse Zigbee courte: Les adresses courtes permettent de contacter un équipement spécifiques.
* Adresse Zigbee de groupe: Les adresses de groupes permettent de joindre un ensembles d'équipement en même temps.

Les adresses de groupe sont typiquement utilisées pas les télécommandes zigbee: Hue, Ikea,...

L'interêt est qu'un seul message sera envoyé sur le réseau zigbee, répété par tous les routeurs et tous les équipements ayant cette adresse de groupe réagiront donnant un impression d'execution simultanée.

Astuce: Ca peut aussi vous sortir d'une situation ou la couverture radio n'est pas bonne et vous avez du mal a joindre un équipement. Si vous l'adressez avec son adresse courte, le mesage doit être routé jusqu'a sa destination par une route specifique. Si vous l'adressez avec une adresse de groupe, le message va être répété par tous les routeurs et vous augmentez la chance que l'équipement recoive l'information.

Vous pouvez aussi utiliser un scenario dans jeedom pour adresser un groupe d'équipements, en envoyant à chacun d'eux une commande. Cette solution ne permet pas d'avoir l'impression d'instantanéité mais est très flexible si vous avez des produit zwave et zigbee par exemple.

Les groupes Zigbee sont nécessaires pour la gestion des scenes.

La gestion des groupes se fait depuis la ruche avec 3 commandes:

* Ajout
* Retrait
* Consultation

A chaque que fois que vous faites un ajout ou retrait, faites une Consultation pour mettre à jour les objets abeille.

Un équipement peut avoir plusieures adresses de groupes. Permet de répondre à plusieures télécommandes par exemple.

== Ajout d un groupe à un équipement

* Premier champ: adresse de l'équipement
* Deuxieme champ: End Point de l'équipement
* Troisiemen champ: l'adresse de groupe a ajouter

== Retrait d un groupe à un équipement

* Premier champ: adresse de l'équipement
* Deuxieme champ: End Point de l'équipement
* Troisiemen champ: l'adresse de groupe a retirer

== Récupérer les groupes d'un équipement

* Premier champ: adresse de l'équipement
* Deuxieme champ: End Point de l'équipement

L'information groupe doit remonter dans le champ groupe de l'équipement (peut etre invisible par defaut, le rendre visible).


# Scenes

== Intro

Les scenes permettent d'envoyer un seul mesage zigbee et d'avoir multiple équipement qui se mette en position automatiquement.

Une scene peut être: "Scéance TV", qui allumera la TV, fermera les volets et mettra une lumière tamisée en place.

Pour ce faire chaque équipement doit savoir ce qu'il doit faire lorsqu'il recoit la commande. Il doit donc avoir été paramétré avant.

Pour l'instant tout le parametrage se fait depuis l'objet Ruche.

== Ajout d une scene à un équipement

*

== Retrait d une scene à un équipement

*

== Récupérer les scenes d'un équipement

*


# Timers

Depuis pas mal de temps je souhaitais avoir des objets Timers à la seconde dans Jeedom.
Après plusieurs versions avec des scripts, des variables, des retours d'état automatique,... je me suis rendu compte que je pouvais sans trop de difficulté créer ces timers au seins d'Abeille.

Maintenant vous pouvez même installar Abeille en n'utilisant que les Timers sans la partie ZigBee. Pour cela dans la configuration du plugin choisissez "Mode Timer seulement" à "Oui".

## Fonctionnement

![i1](../images/Capture_d_ecran_2018_03_21_a_13_16_53.png)

Le timer possede 4 phases:

T0->T1: RampUp de 0 a 100% => RampUp

T1->T2: Stable a 100% => durationSeconde

T2->T3: Ramp Down de 100% à 0% => RampDown

T3-> : n existe plus

Dans les phase de ramp la commande actionRamp/scenarioRamp est executée regulierement avec pour parametre la valeur en cours de RampUpDown.

Exemple d'application: allumage progressif d une ampoule, maintient allumé pendant x secondes puis extinction progressive.

## A prendre en compte


> Il est important de noter que chaque phase fait au minimum 1s.

> La rafraischissement du widget se fait toutes les 5s mais la mise a jour des valeurs se fait toutes les secondes.

### Trois commandes "Start", "Cancel" et "Stop".

* Start: permet d'executer une commande et de démarrer le Timer.
* Cancel: permet d'executer une commande et d'annuler le Timer.
* Stop: permet d'executer une commande, d'annuler le Timer et cette commande qui est executée lors de l'expiration du Timer.

### Retour d'information

* Time-Time: Date de la derniere action sur le Timer
* Time-TimeStamp: Heure systeme de la derniere action
* Duration: Temps restant avant expiration du Timer en secondes
* ExpiryTime: Heure d'expiration du Timer
* RampUpDown: Pourcentage entre 0 et 100 (Ramp Up 0->100, Ramp Down 100->0)

Elles ne sont pas forcement toutes visibles, a vous de choisir.

## Creation d un Timers

Pour créer un objet Timer, clic sur le bouton "Timer" dans la configuration du plugin.

Un message doit apparaitre pour annoncer la creation du Timer avec un Id Abeille-NombreAléatoire.

![i2](../images/Capture_d_ecran_2018_03_21_a_13_14_36.png)

Apres avoir rafraichi l'écran vous devriez avoir l objet:

![i3](../images/Capture_d_ecran_2018_03_21_a_13_16_53.png)

## Configuration du Timer

Comme pour tous les objets, dans l onglet Equipement, vous pouvez changer son nom, le rattacher à un objet Parent, etc...

### Ancienne méthode

Dans l'onglet Commandes, nous allons paramétrer les actions du Timer.

![i4](../images/Capture_d_ecran_2018_03_21_a_13_33_37.png)

#### Start

actionStart=\#put_the_cmd_here#&durationSeconde=300

Pour la commande il y a deux parametres.

* durationSeconde: par exemple ici 300s soit 5min.

* actionStart doit être de la forme \#[Objet Parent][Objet][Cmd]# par exemple: \#[Ruche][Abeille-89ff-AmpouleBureau][On]#.

#### Cancel

actionCancel=\#put_the_cmd_here#

* actionCancel doit être de la forme \#[Objet Parent][Objet][Cmd]# par exemple: \#[Ruche][Abeille-89ff-AmpouleBureau][Off]#.

#### Stop

actionStop=\#put_the_cmd_here#

* actionStop doit être de la forme \#[Objet Parent][Objet][Cmd]# par exemple: \#[Ruche][Abeille-89ff-AmpouleBureau][Off]#.

Exemple plus spécifique: Envoie d'un SMS

actionStop=\#[operation][SMS_Home][Telephone]#&message=Mettre votre message sms ici

### Nouvelle méthode

Allez dans la page configuration, tab Param du Timer et remplissez les champs.

## Commande ou Scenario

Par defaut l'objet Timer est créé avec des commande Start, Stop, Cancel qui font reférence à l'execution d'une commande: actionStart=\#put_the_cmd_here#, actionCancel=\#put_the_cmd_here#, actionStop=\#put_the_cmd_here#.

Mais vous avez la possibilité d'appeler un scenario à la place d'une commande.

Cela vous permet beaucoup plus de flexibilité comme le lancement d'une série de commandes.

La syntaxe: scenarioStart=Id,scenarioCancel=Id, scenarioStop=Id, en remplacant Id pour l'identifiant du scenario que vous trouvez dqns la definition du scenario.

![i5](../images/Capture_d_ecran_2018_03_27_a_12_52_53.png)

Un exemple avec les commandes et les scenarii.

![i6](../images/Capture_d_ecran_2018_03_27_a_12_55_27.png)

Et ici vous pouvez voir l'ID 3 du scenario utilisé.

Commande Start Complete

actionStart=\#put_the_cmd_here#&durationSeconde=300&RampUp=10&RampDown=10&actionRamp=\#put_the_cmd_here#


# Remplacement d'un équipement

Si vous voulez remplacer un équipement par un autre (identique) par exemple parce que le premier est en panne sans perdre toutes les informations (Historique, Scenarios,...), voici la méthode à suivre.


> Attention, cette manipulation n'est pas sans risque car je n'ai pas la maitrise de tout.

Prenons l'exemple du remplassement d'un bouton carre Xiaomi ayant pour adresse 21ce remplacé par un nouveau bouton.

![](../images/Capture_d_ecran_2018_03_01_a_16_53_29.png)

Première opération, Inclure le nouveau bouton dans Abeille.

![](../images/Capture_d_ecran_2018_03_01_a_16_48_35.png)

Le nouveau bouton a pour adresse 8818.

Renseigner les champs dans la commande "Replace Equipment" dans l'objet Ruche.
Pour le champ Titre mettre l'adresse de l'ancien équipement.
Pour le champ Message mettre l'adresse du nouvel équipement.

![](../images/Capture_d_ecran_2018_03_01_a_16_57_02.png)
Puis clic sur "Replace Equipement".

Ouvrez l'ancien équipement qui porte toujours le nom "Abeille-21ce".
Vous devez voir le nouveau nom:

![](../images/Capture_d_ecran_2018_03_01_a_17_01_04.png)

Sauvegardez le nouvel objet.

Vous devez avoir deux équipements:

![](../images/Capture_d_ecran_2018_03_01_a_17_04_30.png)

Il ne vous reste plus qu'a ouvrir l'objet "Abeille-8818" et à le supprimer.

Vous pouvez maintenant changer le nom de l'objet "Abeille-8818-New" à la valeur que vous voulez.

![](../images/Capture_d_ecran_2018_03_01_a_17_09_46.png)


= Remplacement d'une commande

Vous pouvez remplacer une commande A par une autre commande B à l'aide des boutons oranges:

![](../images/Capture_d_ecran_2018_10_01_a_12_32_20.png)

Cela permet de mettre à jour les scénarios, les autres objets,... faisant référence à cette commande. C'est très pratique et rapide.

Mais car il y a un mais, ou plutôt n'oubliez pas qu'une commande est attachée à un objet, un historique et éventeullement un autre Jeedom par JeeLink. A vous de gérer ces aspects.

Si vous aviez une mesure de temperature A que vous avez remplacé par une mesure B et que vous voulez aussi transferer l'historique de A vers B:

![](../images/Capture_d_ecran_2018_10_01_a_12_31_57.png)



# Polling

## Ping toutes les 15 minutes

Par defaut le cron, toutes les 15 minutes, fait un ping des equipements qui n'ont pas de batterie definie. On suppose qu'ils sont sur secteur et que donc ils écoutent et qu'ils repondent à la réquete.

## Etat toutes les minutes

Récupère les infos que ne remonte pas par défaut toutes les minutes si défini dans l 'equipement.

# Santé des équipements

Il y a probablement deux informations qu'il est interessant de monitorer pour vérifier que tout fonctionne:

* le niveau des batteries
* et le fait que des messages sont echangés.

Je vous propose 2 méthodes.

== Health

=== Communication

==== Vue générale

Un cron tourne [underline]##toutes les minutes## (il faut donc attendre une minute et rafraichir la page) pour vérifier la date du dernier message recu pour chaque équipement. Pour visialiser le résultat ouvrir Plugins->Protocoles Domotique->Abeille et clic sur l'icone Santé. Vous devriez avoir un résultat comme:

![](../images/Capture_d_ecran_2018_05_11_a_13_46_17.png)

Actuellement il existe 4 statuts:

- Un carré vert avec un "-": Pas de test fait. Par exemple Abeille ne recoit pas de message venant d'une telecommande Ikea.
- Un carré vert avec Ok, soit l'équipement à un timeout de defini et le dernier message est arrivé dans cette période, soit il n'y en a pas et un message à été recu dans le 24 dernieres heures.
- un carré orange, l'equipement n'a pas de time out defini et le dernier message est plus vieux que 24h et moins que 7 jours
- un carré rouge, soit le capteur à un time out et le dernier message est plus vieux que ce time out, soit il n'a pas de time out et le dernier message est plus que 7 jours.

==== Alerte sur communications

Si un équipement possède un timeout défini alors des alertes peuvent être définies.

Il faut par exemple dans le fichier json d'avoir:
```
{
"Classic A60 RGBW": {
"nameJeedom": "Classic A60 RGB W",
"timeout": "60",
"Categorie": {
"automatism": "1"
},
````

Les 60 sont en minutes. Dans ce cas, l'équipement qui n'a pas eu de communications depuis plus de 60 générera des alarmes.

Pour cela il faut aussi avoir selectionné le champ "Ajouter un message à chaque Timeout" (voir capture d'écran ci dessous).

Vous pouvez aussi ajouter une action dans le champ "Commande sur Timeout".

La vérification est faite par le core de Jeedom toutes les 5 minutes.

=== Batterie

En utilisant le menu Analyse->Equipements, vous trouverez l'état des batteries. Ici un exemple avec des objets Zwave et Abeille/Zigbee.

![](../images/Capture_d_ecran_2018_05_11_a_15_47_55.png)

==== Seuil d'alerte

Menu->Roues crantées->Configuration->Equipements.

Mettez les valeurs qui vous conviennent:

![](../images/Capture_d_ecran_2018_07_16_a_11_28_07.png)

==== Alerte

Menu->Roues crantées->Configuration->Logs

![](../images/Capture_d_ecran_2018_07_16_a_11_29_52.png)

par exemple ici, une alarme est envoyée sur mon tél.


== Script / Widget

Vous aurez un widget comme celui ci:

![](../images/Capture_d_ecran_2018_03_27_a_10_05_02.png)

qui vous permettra d'avoir une alarme sur le niveau de batterie et sur la remontée de message ainsi que la liste des équipements en défaut.

Pour se faire un script est en cours de dev et de test dont voici les détails.

Vous pouvez le faire tourner en manuellement en ssh ou l'intégré dans Jeedom à l'aide du plugin script (Solution présentée ci dessous).

![](..//Capture_d_ecran_2018_03_27_a_09_42_11.png)

Vous créez un équipement avec une Auto-Actualisation à la fréquence que vous souhaitez, ici toutes les heures.

![](..//Capture_d_ecran_2018_03_27_a_09_44_59.png)

=== Script

Le script dont vous aurez besoin est https://github.com/KiwiHC16/Abeille/blob/master/resources/AbeilleDeamon/CheckBattery.php

Faites un copy/paste dans le plugin script de Jeedom.

=== Parametres internes au script

Lorsque vous allez éditer le script dans les étapes suivantes, vous trouverez les lignes suivanted en début de script:

```
$minBattery = 30; // Taux d'usage de la batterie pour générer une alarme.
$maxTime    = 24 * 60 * 60; // temps en seconde, temps max depuis la derniere remontée d'info de cet équipement
````

A vous de mettre, les valeurs qui conviennent à votre systeme.

Juste après vous trouverez:

````
// Liste des équipements à ignorer
$excludeEq = array(
"[Abeille][Ruche]" => 1,
"[Abeille][CheckEquipementsWithBatteries]" => 1,  // L objet du script lui-meme

);
````

C'est le tableau qui contient la liste des Equipements qu'il ne faut pas prendre en compte. Par exemple ici l'objet ruche et l'objet script (c'est à dire lui-même).

=== Batterie

Créez deux commandes scripts:

![](../images/Capture_d_ecran_2018_03_27_a_10_00_01.png)

Donnez un nom à la commande, faites Nouveau, donnez le nom du script "CheckBatteries.php", dans l'éditeur faites un paste du code, Enregistrer, ajoutez les parametres à la commande et sauvegardez.

Le premier parametre est "Batterie" car nous sommes dans le test des batteries.

Le second paramètre est "Test" pour la première commande pour avoir un retour binaire. 0: pas de Batterie en défaut, 1: au moins une Batterie sous le niveau minimum.

Le second paramètre est "List" pour la seconde commande pour avoir la liste des équipements avec un niveau de Batterie inférieure au  niveau miniCheckBatteries.phpmum.


=== Messages échangés

La même chose que pour Batterie avec pour paramètre Alive.

![](../images/Capture_d_ecran_2018_03_27_a_10_15_40.png)

=== Ping

Certains équipements ne remontent pas forcement des informations de facon régulière, comme une ampoule qu'on allume une fois par semaine. Donc pour forcer l'échange de message et vérifier la présence d'un équipement, il y a une fonction "Ping".

Pour l'instant elle fonctionne pour les ampoules Ikea.

Faites un commande:

![](../images/Capture_d_ecran_2018_03_27_a_10_18_37.png)

En appuyant sur le bouton du widget, les équipements doivent être interrogé et repondre. Ensuite si vous faites un refresh du widget, ils ne doivent plus apparaitre dans la liste Alive s'ils y étaient.

# Modele et Fichier JSON

(Cette partie doit être revue et mie a jour sur la base des dernieres evolutions)

== Configuration  des objets

Losqu'un objet ZigBee remonte son nom à Jeedom, le plugin Abeille utilise celui-ci pour créer un nouvel équipement dans Jeedom. Le nom permet de déterminer un type d'équipement. Chaque type d'équipement possède sa configuration, ses informations et ses actions. Tour cela est stocké dans un repertoire au nom du périphérique dans lequel se trouve le fichier JSON au nom du périphérique aussi (plugins/Abeille/core/class/devices/name/name.json). ou name = la valeur du message 0000-01-0005 (avec qq traitements pour enlever les espaces ou les "lumi" qui se repetent).

Actuellement nous y trouvons les Xiaomi temperature rond et carré, capteur présence, interrupteurs, prise et un type de lampe IKEA et la liste continue à s'allonger.

L'idée est de pourvoir étendre au fur et à mesure la listes de objets connus avec le retour des utilisateurs (voir aussi le mode semi automatique pour collecter des informations: https://github.com/KiwiHC16/Abeille/blob/master/Documentation/Debug.asciidoc#creation-des-objets).

== Interface Jeedom

Penons un exemple: Capteur de porte Xiaomi.

Dans Jeedom, il apparaîtra sous le widget:

![](../images/Screen_Shot_2018_01_29_at_19_39_52.png)

Son nom est pour l'occasion "lumi.sensor_magnet.aq2". C'est à partir de là que tout le reste a été déduit, par exemple le symbole de porte,...

Si vous sélectionnez, l'objet vous arrivez dans la page suivante:

![](../images/Screen_Shot_2018_01_29_at_19_40_30.png)

En sélectionnant "Configuration Avancée":

![](../images/Screen_Shot_2018_01_29_at_20_47_13.png)

Vous pouvez voir tous les paramètres associés à l'équipement et vous en servir d'exemple pour définir les paramètres de configuration à mettre dans le fichier JSON.

Attention tous les paramètres ne sont pas encore pris en compte.

Puis si vous sélectionnez "Commandes", puis une commande spécifique à l'aide du symbole engrenage:

![](../images/Screen_Shot_2018_01_29_at_19_42_20.png)

Puis si vous sélectionnez une commande spécifique comme l'état:

![](../images/Screen_Shot_2018_01_29_at_19_42_44.png)

Vous pouvez voir tous les paramètres associés à une commande et vous en servir d'exemple pour définir les paramatres de configuration à mettre dans le fichier JSON.

Si vous modifiez à l'aide de Jeedom la présentation de la commande cela vous permet de savoir ce qu'il faut mettre dans le fichier de conf.

Mais attention car il y a un mais, tous les paramètres ne sont pas encore gérés par Abeille, mais c'est prévu.


== Editer JSON

(Cette partie doit être mise à jour car les fichiers JSON ont beaucoup changés)

Vous avez plusieurs façons pour éditer le fichier JSON.
* La premiere est d'éditer le fichier sous format texte mais je ne vous le conseille pas car ce n'est pas facile à lire et à modifier (beaucoup de parentheses ouvrantes et fermantes qu'il faut absolument respecter)
* Utiliser un éditeur JSON (il y en a plein sur internet).

Une fois ouvert le fichier peut ressembler à quelque chose comme ca:

![](../images/Capture_d_ecran_2018_01_29_a_21_15_55.png)

Vous retrouvez les même informations que celles vues ci dessus. Comparez les différents équipements entre eux cela vous aidera à comprendre les paramètres. Vous pouvez faire des copier / coller avec vos informations et sauvegarder. Le fichier est lu à chaque nouvel équipement donc vous pouvez rapidement voir le résultat. Pour cela supprimez l'équipement dans Jeedom et provoquez l'envoie du nom par l'objet.

Les fichiers JSON ont évolués et intègrent des include. Les fichiers JSON include sont dans le répertoire Template. Ces fichiers permettent de définir les commandes individuellement et de ne pas avoir à tout réécrire à chaque fois.

PS: si vous supprimez un équipement, n'oubliez pas que cela supprime aussi l'historique des valeurs.

== Ajout des icônes pour les objets crées

Lorsqu'un objet est crée, une icône lui est associée. Lorsqu'un nouvel objet est ajouté dans le fichier JSON _plugins/Abeille/core/class/devices/objet.json_, il est possible de lui attribuer une icône personnalisée. Le nom affichée est celui du champ nameJeedom, l'icone utilisée celle de configuration->icone

Le fichier image au format png nommé node_objet.png est a déposer dans le répertoire _plugins/Abeille/images/_ (500x500 px semble correct)

![](../images/Device_icone01.png)

== Mise a jour des fichiers JSON

Vous pouvez mettre à jour les fichiers JSON depuis la page de configuration du plugin: menu->PLugin->Gestion des plugin->Abeille.
Pour se faire, clic sur le bouton: "Mise a jour des modeles". Cela va télécharger les dernières versions sur votre systeme. Attention: si vous avez des JSON perso, ils seront effacés lors de cette opération. Les sauvegarder et les réinstallé après.

Ensuite vous n'avez plus qu'a appliquer ces nouveau modèles en utilisant le bouton "Appliquer nouveaux modeles".

Vous pouvez aussi appliquer les nouveaux modeles que sur certains équipements en allant dans menu->plugin->Protocole domotique->Abeille, selectionnez les devices et clic qur "Apply Template".

== Widget

=== Power Source

Si vous souhaitez avoir un icon en lieu et place de 00 ou 01 pour le paramètre Power Source sur les widgets, vous pouvez faire les opérations suivantes.

Ajoutez le plugin "Widget" depuis l market:

![](../images/Capture_d_ecran_2018_02_14_a_08_32_35.png)

Une fois fait, allez dans le menu widget:

![](../images/Capture_d_ecran_2018_02_14_a_08_32_47.png)

Choisissez "Mode Creation Facile":

![](../images/Capture_d_ecran_2018_02_14_a_08_32_48.png)

Puis widget simple état:

![](../images/Capture_d_ecran_2018_02_14_a_08_32_49.png)

Choisissez vos icônes, par exemple une prise pour symboliser les équipements sur le secteur et une batterie pour les équipements sur pile.

Le résultat devrait ressembler à quelque chose comme cela:

![](../images/Capture_d_ecran_2018_02_14_a_08_32_50.png)

Une fois cela terminé, vous devez voir votre nouveau widget dans la page principale des widgets avec le nombre d'allocation.

![](../images/Capture_d_ecran_2018_02_14_a_08_32_51.png)

Dans cette capture vous pouvez voir AbeillePower avec 2 instances car j'ai deux objets actuellement. De même, il y a AbeillePower2 qui était un test et qui n'est pas utilisé.

* Vérifiez bien l'orthographe "AbeillePower" car c'est celui utilisé par défaut par Abeille lors de la creation des objets.

Sur votre dashboard, vos objets doivent se mettre à jour automatiquement. Cela donne par exemple pour une ampoule et pour un capteur de temperature:

![](../images/Capture_d_ecran_2018_02_14_a_09_09_30.png)

Vous pouvez configurer à votre goût ... A vous de jouer ....




# Parametrage des équipements

Certains équipements possèdent des paramètres qu'il est possible de changer.

== Location / Lieux

=== Récupérer le champ Lieux/Location

Depuis la ruche, utiliser la commande GetLocation

![](..//Capture_d_ecran_2018_05_24_a_11_47_02.png)

Mettre l'adresse de l objet dans le premier champ et laisser vide le second sauf cas particulier.

=== Définir le lieux/location

Depuis la ruche, utiliser la commande SetLocation

![](..//Capture_d_ecran_2018_05_24_a_11_47_17.png)

Mettre l'adresse de l'objet dans le premier champ et la nom du lieux que vous souhaitez dans le second.




# Systèmes / Plateforme testés

Jeedom fonctionne sur le systeme linux debian, de ce fait ce plugin est développé dans ce cadre.

Le focus est fait sur les configurations suivantes:

- raspberry pi 3 (KiwiHC16 en prod)
- Machine virtuelle sous debian 9 en x86 (KiwiHC16 en dev)
- docker debian en x86 (edgd1er en dev)
- raspberry Pi2 (edgd1er en prod)

Les autres envirronements

Les autres environnements ne sont pas testés par défaut mais nous vous aiderons dans la mesure du possible.

En retour d'experience sur le forum:

- Windows ne fonctionne pas, car pas Linux (fichier fifo)
- Ubuntu fonctionne mais demande de mettre les mains dans le cambouis, l'installation même de Jeedom n'est pas immédiate (https://github.com/KiwiHC16/Abeille/blob/master/Documentation/024_Installation_VM_Ubuntu.adoc @KiwiHC16)
- Odroid/HardKernel devrait fonctionner
-- U3 sous debian: install classique (@KiwiHC16)
-- XU4 sous ubuntu: https://github.com/KiwiHC16/Abeille/blob/master/Documentation/026_Installation_Odroid_XU4_Ubuntu.adoc (@KiwiHC16)

Equipements

La liste des équipements testés est consolidé dans le fichier excel: https://github.com/KiwiHC16/Abeille/blob/master/resources/AbeilleDeamon/documentsDeDev/AbeilleEquipmentFunctionSupported.xlsx?raw=true
(Le contenu du fichier est souvent en retard par rapport à la réalité)

# Developpement

Grandes lignes

* branche master : pour tous les développements en cours a condition que les pushs soient utilisables et "stabilisés" pour la phase de test.
* branche beta: pour figer un développement et le mettre en test avant de passer en stable
* branche stable: version stable
* Dev en cours: autre branche

# Pour les curieux

=== Vue générale de la solution

![](../images/Capture_d_ecran_2018_01_21_a_13_13_26.png)

                    +------------+
                    |   Jeedom   |
                    +------------+
                    +------------+
                    |  Abeille   |
                    +-+-----+----+
CmdAbeille/Addr/Action     |         ^               Abeille/#
                                            v         |              CmdRuche/Ruche/CreateRuche
                                            +-------+----+
                                     +-----+ Mosquitto  + <----+
cmdAbeille/#                 |        +-------------+           |   Abeille/Addr/xxxx
                                        v                         |   CmdAbeille/Addr/xxx
            +--------------+---+                +----+----------------+
            |AbeilleMQTTCmd.php|                |AbeilleParser.php    |
            |CmdToAbeille.php  |                |AbeilleSerialRead.php|
            +----+-------------+                +----+----------------+
                    |                                                        ^
                    |                   +--------------+             |
                    +---------->  + /deb/ttyUSBX +------+
                                        +--------------+
                                        +-------------+
                                        |   Zigate    |
                                        X+-------------+X
                                    X                            X
                +---------+X                                X+---------+
                | Abeille |                                      | Abeille |
                |---------+X                                X+---------+
                                    X                             X
                                        X+------------+X
                                        |   Abeille  |
                                        +------------+





# WIFI

== Adafruit

Comme je voulais avoir l'option Zigate Wifi dans Abeille et un petit soucis avec le module proposé par Akila, j'ai fait quelques investigations.

Pour ceux qui connaissent Adafruit, il y a un module que j'avais en stock: https://www.adafruit.com/product/3046

![](../images/Capture_d_ecran_2018_06_20_a_23_54_30.png)

Ce montage possede un ESP8266, un étage de "puissance" avec batterie, un CP2104 USB-Serial, ... et est programmable facilement avec l'IDE Arduino.

J'ai aussi ma zigate version bidouille:

![](../images/IMG_6207.jpg)

Restait à les connecter.

Voici un petit schéma du cablage:

![](../images/Capture_d_ecran_2018_06_21_a_00_02_11.png)

Restait que le SW à faire et à téléchargé dans l'ESP8266. Le soft: https://github.com/KiwiHC16/Abeille/blob/master/WIfi_Module/WIfi_Module.ino

Pour télécharger, compiler avec l'IDE Arduino et télécharger avec le cable USB. Il est necessaire ne déconnecter le TX/RX de la Zigate.

Maintenant j'ai une Zigate autonome sur batterie en Wifi !!!

![](../images/IMG_6208.jpg)

Batterie est égale à:

* Je peux mettre la zigate ou je veux
* si le cable USB est branché sur un charger, je suis autonome en cas de coupure de courant

Vous trouverez le source et le bin à la page: https://github.com/KiwiHC16/Abeille/tree/master/WIfi_Module



# Debug / Troubleshooting / Investigations

Je vais essayer de consolider ici tous les retours d'expériences et les vérifications à faire pour résoudre un éventuel problème.

=== Forum

* le forum: https://www.jeedom.com/forum/viewtopic.php?f=59&t=33573&hilit=Abeille

== Attention - Danger

=== "retain" dans les objets

* Ce plugin utilise un broker MQTT qui a une fonction spécifique "retain".
* Le plugin [underline]#n'utilise pas# ce mode de fonctionnement.
- [underline]#Il est fortement conseillé de ne pas choisir "retain"# si vous ne comprenez pas les conséquences.
- L'option reste accessible pour les pros de MQTT. Si jamais vous voulez l'utiliser alors allez voir https://www.hivemq.com/blog/mqtt-essentials-part-8-retained-messages .
- Si vous avez par erreur activé un "retain" et que le comportement du plugin est impacté, vous pouvez faire la manipulation suivante:

```
rm /var/lib/mosquitto/mosquitto.db
apt-get remove mosquitto
apt-get install mosquitto
```

== Problèmes / Issues

Si vous trouvez un problème qui demande une correction dans le plugin, merci d ouvrir une "issue" dans GitHub à l'adresse avec un "Labels" "Bug": https://github.com/KiwiHC16/Abeille/issues

Si vous ouvrez une "issue" merci de fournir le plus d'information possible et en particulier:

- Votre configuration Jeedom:
* Le HW sur lequel vous faite tourner le plugin,
* la Version de l'OS,
* la version de Jeedom

- Votre configuration Gateway
* Zigate et quel firmware
* ...

- Les logs
* aussi nombreux que possibles
- Description
* ce que vous cherchez à faire
* les résultats

== Evolution

Si vous souhaitez une évolution dans le plugin, merci d ouvrir une "issue" dans GitHub à l'adresse avec un "Labels" "enhancement": https://github.com/KiwiHC16/Abeille/issues


== Debug

=== Configuration

* Verifier la configuration réseau et en particulier /hostname, /etc/hosts
* Vérifier la configuration du plugin. Par exemple le message suivant indique très probablement que l'objet de rattachement de l'équipement Ruche n'est pas défini.
````
[MySQL] Error code : 23000 (1452). Cannot add or update a child row: a foreign key constraint fails (`jeedom`.`eqLogic`, CONSTRAINT `fk_eqLogic_object1` FOREIGN KEY (`object_id`) REFERENCES `object` (`id`) ON DELETE SET NULL ON UPDATE CASCADE)
````

=== Connection avec la Zigate

* Dans l objet ruche, appuyez sur le bouton "Version", vous devez récupérer la version logicielle dans le champ SW, la version de dev dans le champ SDK et les dates Last et Lasts Stamps doivent se mettre à jour à chaque fois.

* Tester la ZiGate en ligne de commande

* Vérifiez bien que vous n'avez pas plusieurs Plugins essayant d'utiliser le même port série (/dev/ttyUSBx).

** Jeedom vers ZiGate

On envoie
```
stty -F/dev/ttyUSB0 115200
echo -ne '\x01\x02\x10\x49\x02\x10\x02\x14\xb0\xff\xfc\xfe\x02\x10\x03' > /dev/ttyUSB0
```
(Cela peut être fait alors que le plugin est ZiGate fonctionnent).

Cette commande demande à la ZiGate de se mettre en Inclusion, vous devriez voir la LED bleu se mettre à clignoter et dans le log AbeilleParser vous devriez voir passer un message comme:

```
AbeilleParser 2018-02-28 04:21:32[DEBUG]-------------- 2018-02-28 04:21:32: protocolData size(20) message > 12 char
AbeilleParser 2018-02-28 04:21:32[DEBUG]Type: 8000 quality: 00
AbeilleParser 2018-02-28 04:21:32[DEBUG]type: 8000 (Status)(Not Processed)
AbeilleParser 2018-02-28 04:21:32[DEBUG]Length: 5
AbeilleParser 2018-02-28 04:21:32[DEBUG]Status: 00-(Success)
AbeilleParser 2018-02-28 04:21:32[DEBUG]SQN: b8
```

PS: la configuration du port peu varier d'un système à l'autre donc il peut être nécesaire de jouer avec stty en rajoutant les arguments raw, cs8, -parenb et autres.

** ZiGate vers Jeedom

Arretez le plugin Abeille. Lancer la commande dans un terminal (Ecoute):

```
cat /dev/ttyUSB0 | hexdump -vC
```

Dans un second terminal envoiyez la commande
```
stty -F/dev/ttyUSB0 115200
echo -ne '\x01\x02\x10\x49\x02\x10\x02\x14\xb0\xff\xfc\xfe\x02\x10\x03' > /dev/ttyUSB0
```

Dans le premier terminal (Ecoute) vous devriez voir passer du traffic comme:
```
www-data@Abeille:~/html/log$ cat /dev/ttyUSB0 | hexdump -vC
00000000  01 80 02 10 02 10 02 15  77 02 10 bb 02 10 49 02  |........w.....I.|
00000010  10 03 01 80 02 10 02 10  02 15 70 02 10 bc 02 10  |..........p.....|
```



=== Mosquitto

* Abeille utilise un broker mosquitto pour échanger des messages entre les modules logicielles.
* mosquitto est installé sur la machine par défaut lors de l'installation des dépendances, vous pouvez utiliser un autre broker, sur une autre machine si vous le souhaitez (pas testé)
* La configuration générale du plugin propose les paramètres :
- Adresse du broker Mosquitto (peut être présent ailleurs sur le réseau)
- Port du serveur Mosquitto (1883 par défaut)
- Identifiant de Jeedom avec lequel il publiera sur le broker
- Il est possible d'ajouter un compte et mot de passe si la connexion le requiert.
- QoS à utiliser (par défaut 1).
* Dans santé vous avez le plugin en alerte car mosquitto ne repond pas.
- Faites un 'ps -ef | grep mosquitto' pour voir si le process tourne.
- Lancez à la main mosquitto; Juste 'mosquitto' en ligne de commande.
- Lancez à la main mosquitto avec votre fichier de configuration en ligne de commande: 'mosquitto -c /etc/mosquitto/mosquitto.conf' (Corrigez les erreurs si il y a).
- Experience: après coupure de courant:
```
mosquitto -c /etc/mosquitto/mosquitto.conf
1516788158: Error: Success.
1516788158: Error: Couldn't open database.
```

la solution a été de supprimer la base de donnée et de réinstaller mosquitto:

```
rm /var/lib/mosquitto/mosquitto.db
apt-get remove mosquitto
apt-get install mosquitto
```

* Debian 8 sur VM
- Je viens d'installer le plugin Abeille sur une Debian 8 en VM x86 64. Impossible de lancer le demon.
- Même un /etc/init.d/mosquitto start à la main ne fonctionne pas.
- Après des recherches infructueuse je suis passé par synaptic (ssh root@machine -Y) et fait "reinstallé" de tous les modules mosquitto. Et maintenant cela fonctionne.



=== Creation des objets

* Les modèles des objets sont dans un fichier JSON, ce fichier peut être éditer pour modifier les configurations pas défaut et ajouter de nouveaux modèles par exemple.

* L'appareil Ruche contient une commande cachée par type d'objet (identifié das le fichier JSON). Chaque commande cachée permet la création d'objets fictifs pour vérifier la bonne création de l'objet dans jeedom. Pour avoir les commandes, il faut regénerer l'objet Ruche pour prendre en compte les modifications éventuelles du fichier json. Pour ce faire supprimer Ruche et relancer le démon. Puis un clic sur le bouton pour créer l'objet.

![](../images/Capture_d_ecran_2018_01_23_a_22_31_19.png)

* Si vour rendez visible ces commandes cachées cela donne:

![](../images/Capture_d_ecran_2018_01_23_a_22_31_43.png)

* En cliquant sur l'un de ces boutons vous vérifier vous testez la bonne création des objets mais aussi que le chemin Jeedom->Mosquitto->Jeedom fonctionne.

* Pas recommandé: Vous pouvez tester la création pure des objets en ligne de commande avec: "php Abeille.class.php 1" en ayant mis les bon paramètres en fin de fichier "Abeille.class.php" (A faire que par ceux qui comprennent ce qu'ils font)

* L'objet obtenu ressemble à cela pour un Xiaomi Temperature Rond:

![](../images/Capture_d_ecran_2018_01_23_a_22_53_24.png)

* Si un objet type Xiaomi Plug, Ampoule IKEA (Il faut que l objet soit en reception radio) a été effacé de Jeedom vous pouvez l'interroger depuis la Ruche et cela devrait le recréer. Mettre dans le champ "Titre" de Get Name, l'adresse (ici example 7c54)  et faites Get Name. Rafraîchir la page et vous devriez avoir l'objet.

![](../images/Capture_d_ecran_2018_01_25_a_14_59_34.png)
![](../images/Capture_d_ecran_2018_01_25_a_14_59_43.png)

* Pour un objet qui n'est pas un routeur, exemple Xiaomi IR Presence, qui donc s'endort 99% du temps, il n'est pas possible de l'interroger pour qu'il provoque la création de l objet dans Jeedom. Mais vous pouvez créer l objet en allant dans les commandes de la ruche.

* Ouvrir la page commande de la ruche et trouver la commande "lumi.sensor_motion".

![](..//Capture_d_ecran_2018_03_02_a_11_09_04.png)

Remplacez "/lumi.sensor_motion/" l'adresse du groupe que vous voulez controler. Par exemple AAAA.

![](..//Capture_d_ecran_2018_03_02_a_11_09_47.png)

Sauvegardez et faites "Tester".

Vous avez maintenant une capteur.

![](..//Capture_d_ecran_2018_03_02_a_11_11_02.png)


* Vous avez aussi la possibilité de lire des attributs de certains équipements en mettant l'adresse dans le titre et les paramètres de l attribut dans le Message comme dans la capture d'écran ci dessous. Regardez dans les logs si vous récupérez des infos (Attention il faut que l'équipement soit à l'écoute):

![](../images/Capture_d_ecran_2018_01_25_a_16_12_32.png)

* Vous avez la possibilité de demander la liste des équipements dans la base interne de la Zigate. Pour ce faire vous avez le bouton "Liste Equipements" sur la ruche. Si vous êtes en mode automatique, les valeurs des objets existants vont se mettre à jour (IEEE, Link Quality et Power-Source). Si vous êtes en mode semi-automatique de même et si l'objet n'existe pas un objet "inconnu" sera créé avec les informations.

![](../images/Capture_d_ecran_2018_01_26_a_10_46_04.png)
![](../images/Capture_d_ecran_2018_01_26_a_10_46_13.png)

* Il peut être nécessaire de faire la demande de la liste pour que les valeurs remontent dans les objets inconnus. Et en attendant un peu on peut avoir un objet avec une longue liste de paramètres (Voir objet 9156 ci dessous).

![](../images/Capture_d_ecran_2018_01_26_a_10_52_58.png)

* Avec la liste des équipements vous avez la liste connue par zigate dans sa base de données. Vous avez aussi la possibilité de voir la liste des equipments qui se sont déconnectés du réseau. Pour cela, il faut qu'ils aient envoyé une commande "leave" à zigate et qu'Abeille soit actif pour enregistrer l'information. Le dernier ayant quitté peut être visualisé sur l'objet ruche:

![](../images/Capture_d_ecran_2018_02_07_a_12_54_55.png)

Nous pouvons voir que l objet ayant pour adresse complete IEEE: 00158d00016d8d4f s'est déconnecté (Leave) avec l'information 00 (Pas décodé pour l'instant).

Si vous souhaitez avoir l'historique alors allez dans le menu:

![](../images/Capture_d_ecran_2018_02_07_a_12_49_42.png)

Puis choisissez Ruche-joinLeave:

![](../images/Capture_d_ecran_2018_02_07_a_12_49_56.png)

et là vous devez avoir toutes les informations:

![](../images/Capture_d_ecran_2018_02_07_a_12_50_09.png)




=== Investigate Equipements

La ruche possede deux commandes pour interoger les objets: ActiveEndPoint et SingleDescriptorRequest.

![](..//Capture_d_ecran_2018_02_06_a_17_33_19.png)

Dans ActiveEndPoint mettre l'adresse de l'équipement dans le titre puis clic sur le bouton ActiveEndPoint.

Regardez dans la log AbeilleParser, vous devez voir passer la réponse. Par exemple pour une ampoule IKEA:
```
AbeilleParser: 2018-02-06 17:40:16[DEBUG]-------------- 2018-02-06 17:40:16: protocolData
AbeilleParser: 2018-02-06 17:40:16[DEBUG]message > 12 char
AbeilleParser: 2018-02-06 17:40:16[DEBUG]Type: 8045 quality: 93
AbeilleParser: 2018-02-06 17:40:16[DEBUG]type: 8045 (Active Endpoints Response)(Not Processed)
AbeilleParser: 2018-02-06 17:40:16[DEBUG]SQN : da
AbeilleParser: 2018-02-06 17:40:16[DEBUG]Status : 00
AbeilleParser: 2018-02-06 17:40:16[DEBUG]Short Address : 6e1b
AbeilleParser: 2018-02-06 17:40:16[DEBUG]Endpoint Count : 01
AbeilleParser: 2018-02-06 17:40:16[DEBUG]Endpoint List :
AbeilleParser: 2018-02-06 17:40:16[DEBUG]Endpoint : 01
```

Il y a donc une seul EndPoint à l'adresse "01" (Donné par les lignes suivant "Endpoint List".

Faire de même pour SingleDescriptorRequest en ajoutant le EndPoint voulu dans le champ Message.

```
AbeilleParser: 2018-02-06 17:42:25[DEBUG]-------------- 2018-02-06 17:42:25: protocolData
AbeilleParser: 2018-02-06 17:42:25[DEBUG]message > 12 char
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Type: 8000 quality: 00
AbeilleParser: 2018-02-06 17:42:25[DEBUG]type: 8000 (Status)(Not Processed)
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Length: 5
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Status: 00-(Success)
AbeilleParser: 2018-02-06 17:42:25[DEBUG]SQN: db
AbeilleParser: 2018-02-06 17:42:25[DEBUG]-------------- 2018-02-06 17:42:25: protocolData
AbeilleParser: 2018-02-06 17:42:25[DEBUG]message > 12 char
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Type: 8043 quality: 93
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Type: 8043 (Simple Descriptor Response)(Not Processed)
AbeilleParser: 2018-02-06 17:42:25[DEBUG]SQN : db
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Status : 00
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Short Address : 6e1b
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Length : 20
AbeilleParser: 2018-02-06 17:42:25[DEBUG]endpoint : 01
AbeilleParser: 2018-02-06 17:42:25[DEBUG]profile : c05e
AbeilleParser: 2018-02-06 17:42:25[DEBUG]deviceId : 0100
AbeilleParser: 2018-02-06 17:42:25[DEBUG]bitField : 02
AbeilleParser: 2018-02-06 17:42:25[DEBUG]InClusterCount : 08
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0000 - General: Basic
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0003 - General: Identify
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0004 - General: Groups
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0005 - General: Scenes
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0006 - General: On/Off
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0008 - General: Level Control
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 0B05 - Misc: Diagnostics
AbeilleParser: 2018-02-06 17:42:25[DEBUG]In cluster: 1000 - ZLL: Commissioning
AbeilleParser: 2018-02-06 17:42:25[DEBUG]OutClusterCount : 04
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Out cluster: 0000 - General: Basic
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Out cluster: 0003 - General: Identify
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Out cluster: 0004 - General: Groups
AbeilleParser: 2018-02-06 17:42:25[DEBUG]Out cluster: 0005 - General: Scenes
```

Nous avons maintenant les clusters supportés par cet objet sur son endpoint 01.

...


== Monitorer les messages

mosquitto_sub -t "#" -v

== Script de test et vérifications

Dans Abeille/resources/AbeilleDeamon/Debug, vous trouverez le script verification.sh. L'execution permet de tester, vérifier et donner des infos qui sont souvent interessantes pour des problème de base.

# Histore d une installation

== Une VM sous parallel OSX: debian 9.3.0 / Jeedom 3.1.7 / Abeille 2018-06-19 01:01:07

=== VM

Configuration: choisissez un réseau ponté pour avoir un IP à vous.

=== Debian

Installation de Debian des plus classique (Un gros 1/4 d'heure depuis un ISO sur disque).

Pas d'environnement de bureau, juste un serveur ssh et les utilitaires usuels système.

Une mise a jour en fin d'installation:


```
su -
vi /etc/apt/sources.list
deb cdrom:[Debian GNU/Linux 9.3.0 _Stretch_ - Official amd64 DVD Binary-1 20171209-12:11]/ stretch contrib main
apt-get update
apt-get upgrade
```
=== Jeedom

La documentation Jeedom est à la page https://jeedom.github.io/documentation/installation/fr_FR/index

Perso j'utilise le dernier chapitre (Chapitre 10 - Autres) (Un gros 1/4 d'heure)

Connectez-vous en SSH à votre système et faites :

```
su -
wget https://raw.githubusercontent.com/jeedom/core/stable/install/install.sh
chmod +x install.sh
./install.sh
./install.sh -w /var/www/html  -m Jeedom
reboot
```

=== Web Browser

Ouvrir la page de votre Jeedom: http://Mon_IP_JEEDOM

admin/admin

Ne plus afficher et cloture fenetre du dessus.

Creation d'un Objet Abeille pour accueillir tous les futures équipement zigbee:

Menu->Outils->Objets->'+', Sauvegarder et retour sur la page principale(Dashboard)

=== Ajout Plugin Abeille

Menu->Plugins->Gestion des plugins

Market

Recherche Abeille

Selectionner Abeille

Installer stable (Version 2018-06-19 01:01:07)

Voulez vous aller sur la .... -> Ok

=== Configuration du plugin

Activer

Dependances -> Relancer (ou vous attendez et elles devraient s'installer automatiquement).

Deux messages doivent s'afficher pour confirmer le lancement et le lien vers la doc.

Une fois les dépendances installées, la date de derniere installation doit apparaitre.

Configuration:

* Choisissez le port serie (on suppose que vous avez une zigate ttl sur un port USB déjà branchée, sinon branchez la et rafraichissez la page)
* Choisissez l'Objet Parent: Abeille
* Sauvegarder

Le demon doit démarrer et passer au vert.

Dans mon cas mosquitto fait encore des siennes et il n'a pas démarré. Un reboot du systeme résoud le problème.

Rafraichir la page et vérifier que le demon est passé au vert: Statut Ok et Configuration: Ok.

Et maintenant tout est pret. Retour sur Dashboard. Vous devriez y touver l'équipement Ruche.


=== Demarrage du reseau

Si vous selectionnez "Version" alors les champs 'Last', 'Last Stamps', 'SW', 'SDK' doivent se mettre à jour. Cela confirme que cela fonctionne.

Vous pouvez démarrer le réseau "Start Network".

Et faire un "get Network Status", d'autres champs vont se mettre à jour.

Voilà l'installation d'Abeille dans Jeedom est finie. Vous pouvez intégrer vo équipements.

En tout 1h pour faire une installation from scratch (et écrire cette doc).

# Installation dans un conteneur depuis Ubuntu

== Introduction

Debian supporte nativement jeedom et le support est assuré par l'équipe de développement. Toute autre demande a propos d'une distribution est ignorée.   https://jeedom.github.io/documentation/installation/fr_FR/index

docker permet d'installer un système invité minimal dans une partie virtualisée du système hôte, tout ajout/suppression/modification du conteneur laisse tel quel le système hôte. L'interet de docker est que n'est installé que le minimum nécéssaire au fonctionnement dans l'image. ( une image éxecutée est un conteneur.) Le but ici est de faire tourner un conteneur jeedom sur un système Ubuntu, cependant ce n'est pas limité à ce système.

== Prérequis

Avoir docker disponible dans les dépôts de la distribution.


== Installation de docker

apt-get install docker docker.io

== Fonctionnement

Loïc, un des créateurs de jeedom maintient image jeedom. Cette image appelé jeedom-server utilise une image jeedom-mysql pour stocker les données dans une base de données mysql. Il faudra donc a chaque fois lancer le conteneur jeedom-mysql puis le jeedom-server. Les réglages restent d'une fois sur l'autre.

== Récupération des images et Création des conteneurs

Ces deux lignes vont récupérer les images, créer les conteneurs et les configurer. Le port USB est a adapter selon le besoin ( `ls /dev/ttyUSB*` pour avoir la liste )

[source,bash]
docker run --name jeedom-mysql -e MYSQL_ROOT_PASSWORD=Mjeedom96 -d mysql:latest
docker run --name jeedom-server -e ROOT_PASSWORD=Mjeedom96 --link jeedom-mysql:mysql -p 9180:80 -p 9443:443 -p 9022:22 --device=/dev/ttyUSB0 jeedom/jeedom

A ce stade, l'installation de jeedom commence dans le conteneur jeedom-server. il faut compter 5 a 10 minutes selon la connexion et la puissance du système hôte.

jeedom sera disponible après quelques instants à l'adresse http://0.0.0.0:9180

TIP: Le ssh est accessible via le port 9022. (root/Mjeedom96)

== Configuration de Jeedom

Dans les champs indiqués entrer la valeur surlignée.

[width="40%",frame="topbot",options="header,footer"]
|==================================
|Database hostname| jeedom-mysql
|Database port    | 3306
|Database username| root
|Database password| Mjeedom96
|Database name    | jeedom
|Erase database   | checked
|==================================

Une fois, le texte `[END INSTALL SUCCESS]` affiché en bas. Aller à l 'adresse http://0.0.0.0:9180 la page de login de jeedom devrait apparaître. Les login et mot de passe sont admin admin.

== start/stop des conteneurs

les conteneurs peuvent être arrêtés et relancés à la demande en gardant l'ordre mysql jeedom au lancement, jeedom mysql à  l'arrêt.

Arrêt `docker stop jeedom-server && docker stop jeedom-mysql`

Démarrage `docker start jeedom-mysql && docker start jeedom-server`

== Repartir de zéro

il est possible de supprimer les conteneurs et de repartir d'un jeedom tout neuf.

`docker rm jeedom-server && docker rm jeedom-mysql`

puis aller vers link:[Récupération des images et Création des conteneurs]


== Portainer

Pour ceux que ne sont pas à l'aise avec la ligne de commande, portainer propose une interface graphique pour gérer les conteneurs et les images.
C'est un conteneur à démarrage automatique qui pourra relancer les conteneurs crées.

docker run -d -p 9000:9000 --name portainer --restart always -v /var/run/docker.sock:/var/run/docker.sock portainer/portainer

le site sera disponible à l'adresse http://0.0.0.0:9000


== Installation du Plugin Abeille

voir la doc :)


# Docker



Installation d'abeille dans docker
(Il y a certainement plus simple mais je ne suis pas expert en Docker et cette méthode semble bien fonctionner).

== Preparation du docker

=== Preparation sous Raspbian

* installer 2018-06-27-raspbian-stretch-lite.zip sur une SD
* demarrer le RPI3
* se logger pi/raspberry (atttention au clavier US par defaut)
* lancer raspi-config (faire la conf que vous souhaitez): sshd, all memory space, clavier, locales,...
* Vérifier la conf réseau
* Vous connecter en ssh pour la suite:
```
ssh pi@IP
```
* La suite se fait entant que root: sudo su -
```
sudo su -
```
* une classique mise a jour du systeme:
```
apt-get update, apt-get upgrade
```
* Restart du RPI
```
reboot
ssh pi@IP
sudo su -
```
* Installation de docker:
```
apt-get install docker
apt-get install docker.io
```
* Vérifier que cela fonctionne, un docker ps -a pour voir les images:
```
docker ps -a
```

On voit ici qu’il n’y a pas d’image, il faut en créer une. Flasher la SD. Demarrer le PI et une commande:

```
docker ps
```

Permet de voir que docker fonctionne.

=== Preparation sous hypriot

La version officielle raspbian est un peu vieille et nous n'avons pas toutes les nouveautés. Hypriot a une version bien plus recente et nous facilite la vie (pas de config manuelle tout est prêt). Elle permet aussi de faire tourner le plugin Homebridge (macvlan).
http://blog.hypriot.com

Telecharger leur image à l adresse: http://blog.hypriot.com/downloads/

On voit ici qu'il n'y a pas d'image, il faut en créer une.

== Créons un system pour le docker.

http://www.guoyiang.com/2016/11/04/Build-My-Own-Raspbian-Docker-Image/

Ici je ne cherche pas à faire une image la plus petite possible mais la plus proche possible d'une install classique sur un HW RPI3. De ce fait l'image fait presque 1G.

```
mkdir DockerAbeille
cd DockerAbeille
```
Recuperer le fichier 2018-06-27-raspbian-stretch-lite.zip par scp par exemple. Puis:
```
unzip 2018-06-27-raspbian-stretch-lite.zip
losetup -Pr /dev/loop0 2018-06-27-raspbian-stretch-lite.img
mkdir rpi
mount -o ro /dev/loop0p2 ./rpi
tar -C ./rpi -czpf 2018-06-27-raspbian-stretch-lite.tar.gz --numeric-owner .
umount ./rpi
losetup -d /dev/loop0
rmdir rpi
rm 2018-06-27-raspbian-stretch-lite.img
rm 2018-06-27-raspbian-stretch-lite.zip

echo 'FROM scratch' > Dockerfile
echo 'ADD ./2018-06-27-raspbian-stretch-lite.tar.gz /' >> Dockerfile
echo 'CMD ["/bin/bash"]' >> Dockerfile
```

Maintenant on lance la creation du docker:
```
docker build -t jeedomabeille .
```
Bien mettre le . a la fin de la ligne.

Le résultat doit ressembler à:
```
root@docker:~/DockerAbeille# docker build -t jeedomabeille .
Sending build context to Docker daemon 348.4 MB
Step 0 : FROM scratch
--->
Step 1 : ADD ./2018-06-27-raspbian-stretch-lite.tar.gz /
---> f7009768b966
Removing intermediate container ef5668638536
Step 2 : CMD /bin/bash
---> Running in d95d0e65bbb4
---> 286ea5048dfd
Removing intermediate container d95d0e65bbb4
Successfully built 286ea5048dfd
```

Et si vous demandez les images:
```
root@docker:~/DockerAbeille# docker images
REPOSITORY          TAG                 IMAGE ID            CREATED             VIRTUAL SIZE
jeedomabeille       latest              286ea5048dfd        12 minutes ago      900.9 MB
```

Démarrons le container:
```
docker run -it jeedomabeille
```

Le shell vous donne la main dans le docker:
```
root@52b658b7d8f8:/#
```
Vous pouvez arreter le docker depuis un shell sur le host:
```
root@docker:~/DockerAbeille# docker ps
CONTAINER ID        IMAGE               COMMAND             CREATED             STATUS              PORTS               NAMES
52b658b7d8f8        jeedomabeille       "/bin/bash"         3 minutes ago       Up 3 minutes                            sad_stallman
root@docker:~/DockerAbeille# docker stop 52b658b7d8f8
52b658b7d8f8
```

Vous pouvez demarrer de docker depuis un shell sur le host:
[source,]
----
root@docker:~/DockerAbeille# docker ps -a
CONTAINER ID        IMAGE               COMMAND             CREATED             STATUS                       PORTS               NAMES
52b658b7d8f8        jeedomabeille       "/bin/bash"         7 minutes ago       Exited (127) 3 minutes ago                       sad_stallman
root@docker:~/DockerAbeille# docker start 52b658b7d8f8
52b658b7d8f8

----

Vous pouvez vous connecter au docker:
```
root@docker:~/DockerAbeille# docker attach 52b658b7d8f8

root@52b658b7d8f8:/#

```
Faites plusieur "enter" pour avoir le prompt.


Maintenant que le docker fonctionne on va faire l installation de jeedom et abeille.


> To stop a container, use CTRL-c. This key sequence sends SIGKILL to the container. If --sig-proxy is true (the default),CTRL-c sends a SIGINT to the container. You can detach from a container and leave it running using the [underline]#*CTRL-p suivi de CTRL-q*# key sequence.


== Service dans le docker

Les services ne demarrent pas tout seuls dans le docker, il aurait probablement du le faire dans Dockfile.

Donc j'ajoute quelques lignes à /etc/rc.local pour Raspbian:

```
docker start jeedomabeille
(docker exec -u root jeedomabeille dpkg-reconfigure openssh-server)
docker exec -u root jeedomabeille /etc/init.d/ssh start
docker exec -u root jeedomabeille /etc/init.d/mysql start
docker exec -u root jeedomabeille /etc/init.d/apache2 start
docker exec -u root jeedomabeille /etc/init.d/cron start
```

que je mets sur le host dans /root sous le nom startJeedomAbeileDocker.sh.
et un bon vieux:  chmod u+x startJeedomAbeileDocker.sh

et pour hypriot qui n'a pas de rc.local, je fait un script:

```
sudo su -
cd /etc/init.d
vi startDockers
```

Je mets dedans:
```-
#! /bin/sh
# /etc/init.d/startDockers

### BEGIN INIT INFO
# Provides:          startDockers
# Required-Start:    $remote_fs $syslog
# Required-Stop:     $remote_fs $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Simple script to start a program at boot
# Description:       A simple script from www.stuffaboutcode.com which will start / stop a program a boot / shutdown.
### END INIT INFO

# If you want a command to always run, put it here

# Carry out specific functions when asked to by the system
case "$1" in
start)
echo "Starting startDockers"
# run application you want to start
docker start jeedomgite
docker exec -u root jeedomgite /etc/init.d/ssh start
docker exec -u root jeedomgite /etc/init.d/mysql start
docker exec -u root jeedomgite /etc/init.d/apache2 start
docker exec -u root jeedomgite /etc/init.d/cron start
;;
stop)
echo "Stopping startDockers"
# kill application you want to stop
docker stop jeedomgite
;;
*)
echo "Usage: /etc/init.d/startDockers {start|stop}"
exit 1
;;
esac

exit 0
```

Je sauvegarde.

```
chmod 755 /etc/init.d/startDockers
/etc/init.d/startDockers start
update-rc.d startDockers defaults
```

Ajouter la ligne
```
* * * * * su --shell=/bin/bash - www-data -c '/usr/bin/php /var/www/html/core/php/jeeCron.php' >> /dev/null
```
dans le cron root.

Thanks to https://www.stuffaboutcode.com/2012/06/raspberry-pi-run-program-at-start-up.html

== Installation Jeedom

Dans le container precedent nous n'avons pas pris en compte les besoins réseaux et port série.
Effaçons l'ancien container.
```
docker rm 52b658b7d8f8
```

Créons en un nouveau avec les ports mysql, apache, ssh et le port serie ttyUSB0 (la zigate).

```
docker run --name=jeedomabeille --device=/dev/ttyUSB0 -p 2222:22 -p 80:80 -p 3306:3306 -it jeedomabeille
docker run --name=jeedomgite --device=/dev/ttyACM0 -p 51826:51826 -p 5353:5353 -p 2222:22 -p 80:80 -p 3306:3306 -it jeedomgite
```

Si vous êtes sur hyprio et voulez exposer la machine completement, créé le Networks puis le Containers:
```
docker network create -d macvlan --subnet=192.168.4.0/24 --gateway=192.168.4.2 -o parent=eth0 pub_net
docker run --name=jeedomgite --device=/dev/ttyACM0 --network pub_net --ip=192.168.4.38 --hostname=jeedomgite -it jeedomgite /bin/bash
```

> Attention de ne pas vous prendre les pieds dans le tapis entre les adresses du "HW" rpi et les addresses des containers.


Donc Jeedom sera accessible sur le port 80 à l'adresse IP du host. 2222 pour ssh et 3306 pour mysql.
J'ai mis un nom pour être plus sympas à gérer.

Vous pourrez le demarrer/arreter par:
```
docker stop jeedomabeille
docker start jeedomabeille
```

Passons a l installation des services:
```
docker attach jeedomabeille
apt-get update
apt-get upgrade
apt-get install openssh-server
dpkg-reconfigure openssh-server
/etc/init.d/ssh start
apt-get install mariadb-server
apt-get install apache2
```

Maintenant le systeme doit être prêt pour l installation de jeedom lui-meme.
(https://jeedom.github.io/documentation/installation/fr_FR/index => Chap 10)

```
wget https://raw.githubusercontent.com/jeedom/core/stable/install/install.sh
chmod +x install.sh
./install.sh -w /var/www/html -m Jeedom
```

L installation va se dérouler en 11 grandes étapes.



```
étape 11 vérification de jeedom réussie
/!\ IMPORTANT /!\ Le mot de passe root MySQL est Jeedom
Installation finie. Un redémarrage devrait être effectué
```

avec un ps -ef, vous devriez voir apache, ssh et mysql fonctionner.

Puis vous vous connecter à Jeedom avec l adresse http://IP_Host:80/
Connectez vous avec admin/admin.
Sauf que cela ne fonctionne pas !! ->Mot de passe ou nom d'utilisateur incorrect<-

Il demande un reboot donc allons y:

```
docker stop jeedomabeille
docker start jeedomabeille
docker attach jeedomabeille
/etc/init.d/ssh start
/etc/init.d/mysql start
/etc/init.d/apache2 start
```

On ne peut toujours pas se connecter, je ne sais pas pourquoi....

Donc on va passer par une autre solution: https://jeedom.github.io/documentation/howto/fr_FR/reset.password

Problement de "Could not reliably determine the server's fully qualified domain name, using 172.17.0.14. Set the 'ServerName' directive globally to suppress this message":
mettre en debut de fichier /etc/apache2/apache2.conf la line :
```
Global configuration

ServerName 2b8faafb19a4
```
root@2b8faafb19a4:/etc/apache2# apachectl configtest
Syntax OK

```
# Global configuration
#
ServerName 2b8faafb19a4
```
Puis tester:
```
root@2b8faafb19a4:/etc/apache2# apachectl configtest
Syntax OK
```

```
root@2b8faafb19a4:/etc/apache2# cat /etc/hosts
127.0.0.1    localhost
::1    localhost ip6-localhost ip6-loopback
fe00::0    ip6-localnet
ff00::0    ip6-mcastprefix
ff02::1    ip6-allnodes
ff02::2    ip6-allrouters
172.17.0.14    2b8faafb19a4    jeedomabeille
172.17.0.14    jeedomabeille.bridge
```

```
cat /var/www/html/core/config/common.config.php
mysql -ujeedom -p
use jeedom;
REPLACE INTO user SET `login`='adminTmp',password='c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec',profils='admin', enable='1';
exit
```

Et maintenant on peut se connecter en adminTmp/admin.

Aller dans la conf reseau et mettre l adresse du host dans les adresses http.

Maintenant on peut se connecter en admin/admin donc on peut effacer l utilisateur adminTmp.

== Installation du plugin Abeille

* Créer un objet Abeille.
* Installer le plugin Abeille depuis le market.
* L'activer.
* Lancer l installation des dépendances.
* Definissez les bons parametres du demon.
* Lancer le demon
* L objet Ruche doit être créé.
* un petit getVersion et vous devriez avoir le champ SW et SDK qui se mettent à jour.

Enjoy !!!


[quote,Me]
____
Vous allez certainement avoir le message:
"Jeedom est en cours de démarrage, veuillez patienter. La page se rechargera automatiquement une fois le démarrage terminé."

Aller dans le "Moteur de taches" et lancer "Jeedom-cron".
____

= Backup du Docker

Plusieures solutions s'offrent à nous. Il est interessant de comprende ce qui se passe. Un bon article à lire: https://tuhrig.de/difference-between-save-and-export-in-docker/

Toutes les operations suivantes se font depuis le host.

== Commit / Save / Load

Permet de garder tout l'historique.

=== Commit

Pour avoir les docker en fonctionnement :
```
docker ps
```

Pour avoir les docker en stock:
```
docker ps -a
```

Créons un image du docker en prod: jeedomabeille et appelons cette image jeedomabeille_backup

```
docker commit -p jeedomabeille jeedomabeille_backup
```

Attention: avec le -p le container est en pause donc jeedom ne fonctionne plus le temps de faire la capture.

Par exemple: faites cette operation avant de faire des opérations irréversibles qui risquent de planter votre jeedom.


Pour voir les images crées et disponiqbles:
```
docker images
```

=== Save
```
docker save -o ~/jeedomabeille_backup.tar jeedomabeille_backup
ls -l ~/jeedomabeille_backup.tar
```

soyez patient le tar fait 3G.

=== Load

If we have transferred our "container1.tar" backup file to another docker host system we first need to load backed up tar file into a docker's local image repository:


```
docker load -i /root/jeedomabeille_backup.tar
docker images
```

== Export / Import

Garde que la derniere version.

=== Export

```
docker ps -a
docker export <CONTAINER ID> > /home/export.tar
```

=== Import

```
cat /home/export.tar | sudo docker import - NameYouWant:latest
```

== Conclusion

Plus besoin d'aller chercher les cartes SD dans les differents RPI3 pour en faire de images. Tout va se faire à distance maintenant !!! YaaahhhOOOOUUU !!!!!


Vous pouvez effacer de vieilles images par:
```
docker rmi jeedomabeille_backup
```

= Docker GUI

== Sur la raspbian

Thanks to:
* http://blog.hypriot.com/post/new-docker-ui-portainer/
* https://portainer.readthedocs.io/en/latest/deployment.html

Il semble qu'on puisse utiliser une interface graphique "portainer.io" sur le rpi, saisir:
```
docker run -d -p 9000:9000 --name portainer --restart always -v /var/run/docker.sock:/var/run/docker.sock portainer/portainer:arm -H unix:///var/run/docker.sock
```

Puis se logger sur http://IP_Host:9000
Tout ne fonctionne pas mais c'est plus sympas que la ligne de commande.

Il semble que la version rpi par defaut est un peu ancienne et certaine feature comme volume ne sont pas dispo.

== Sur la hypriot

https://hub.docker.com/r/hypriot/rpi-portainer/

```
docker run -d -p 9000:9000 -v /var/run/docker.sock:/var/run/docker.sock hypriot/rpi-portainer
```

Puis se logger sur http://IP_Host:9000.
Tout fonctionne bien mieux que sur la version raspbian.

= Plugins

== Zwave

Sur ma machine jeedomprorpi, le repertoire /tmp/jeedom/openzwave n'a pas les bons droits et le demon est toujours en erreur. Je viens de faire un chmod 777 /tmp/jeedom/openzwave et tout est ok maintenant.

== homebridge

Comme il faut que le docker soit exposé au sous réseau, il faut utiliser macvlan et affecter une adresse spécifique.



# Installation sur une VM Ubuntu

== Installation de l'OS

Fichier ISO: ubuntu-16.04.1-server-amd64.iso

Installation classique de l'OS (Je ne détaille pas car cela dépend de votre envirroement de virtualisation).

== Preparation de l'OS

login: (user créé pendant l install avec son password associé).

```
sudo su -

apt-get update
apt-get upgrade
apt-get autoremove
````

== Installation de la base mysql

installation à la main de mysql (car l instanllation par jeedom ne fonctionne pas)

````
apt-get install mysql-server
apt-get install mysql-client
````

== Installation de Jeedom

````
wget https://raw.githubusercontent.com/jeedom/core/stable/install/install.sh
chmod +x install.sh
````

Enlever le php7.0-ssh2 du fichier install.sh

````
./install.sh -m motDePasse
````

A cette étape vous devoir pourvoir ouvrir un browser et utiliser Jeedom.

== Installation du Plugin Abeille

```
./install.sh -m motDePasse

cd /var/www/html/plugins/

git clone https://github.com/KiwiHC16/Abeille.git Abeille

chmod -R 777 /var/www/html/plugins/Abeille
chown -R www-data:www-data /var/www/html/plugins/Abeille
```

== Utilisation de Jeedom

Il ne vous reste plus qu'à vous connecter à Jeedom...


# Installation sur une machine Odroid XU4 avec Ubuntu

== Installation de l'OS

Fichier img: ubuntu-14.04lts-server-odroid-xu3-20150725.img
que l on trouve sur le server odroid: https://odroid.in/ubuntu_14.04lts/

Installation classique odroid de l'OS : https://wiki.odroid.com/odroid-xu4/odroid-xu4

== Preparation de l'OS

login: (root/odroid).

```
apt-get update
apt-get upgrade
apt-get autoremove
```

== Installation de la base mysql

installation à la main de mysql (car l instanllation par jeedom ne fonctionne pas)

```
apt-get install mysql-server
apt-get install mysql-client
```

== Installation de Jeedom

```
wget https://raw.githubusercontent.com/jeedom/core/stable/install/install.sh
chmod +x install.sh
```

Enlever le php7.0-ssh2 du fichier install.sh

```
./install.sh -m motDePasse
```

A cette étape vous devoir pourvoir ouvrir un browser et utiliser Jeedom.

== Installation du Plugin Abeille

```
./install.sh -m motDePasse

cd /var/www/html/plugins/

git clone https://github.com/KiwiHC16/Abeille.git Abeille

chmod -R 777 /var/www/html/plugins/Abeille
chown -R www-data:www-data /var/www/html/plugins/Abeille
```

== Utilisation de Jeedom

Il ne vous reste plus qu'à vous connecter à Jeedom...

# De-installation

Le plugin Abeille utilise:
- le code du plugin lui-même et
- un broker MQTT mosquitto.

Par défaut, lors de l'installation de Abeille, le code du plugin est installé depuis le market et le broker est installé lors de l installation des dépendances.

Le broker MQTT peux être utilisé par d'autres logiciels comme par d'autres plugins.

C'est pourquoi lors de la desinstallation d'Abeille, mosquitto n'est pas desintallé, ni sa configuration.

Si vous souhaitez le desinstaller, vous avez le script "manual_remove_of_mosquito.sh" qui peut vous aider à enlever les déclaraitons faites dans apaches.

Pour la désinstallation de mosquitto, cela depend de votre système et il y a plein de doc sur le net (je manque de temps pour faire la doc...).


# ZiGate Backup/Restore

```
Info dans le doc JN-UG-3007 (confirmed in doc JN-SW -4141)
````

> ! Caution: For a JN516x device, entering a new MAC address is a 'one-time programmable’ option and care should be taken to ensure that the MAC address specified is correct before programming, as it cannot be modified after programming.


Tout se fait depuis NXP Beyond Studio

== Backup

Branchez la ZiGate sur le port USB en appuyant sur le bouton de la ZiGate puis relacher.

Récupérer les informations de la ZiGate

Menu -> Devices -> Device Info

![](../images/Capture_d_ecran_2018_02_28_a_09_59_50.png)

Ensuite, faire un "Read" de la Flash et de l'EEPROM.

> Le restore de la Flash ne fonctionne pas pour moi, alors bien noter la version de ZiGate utilisée pour re-installer le bin ZiGate et pas la copie de la flash. En esperant comprendre plus tard pourquoi cela ne fonctionne pas. Quelqu'un a une idée ?


![](../images/Capture_d_ecran_2018_02_28_a_10_17_19.png)

Si tout se déroule comme prévu vous devez avoir une information de progression sous la forme d'une fenêtre ou dans l'onglet "Progress".

![](..//Capture_d_ecran_2018_02_28_a_10_17_28.png)

Voilà le backup est fait. Débranchez la ZiGate du port USB.

== Restore

Branchez la ZiGate (ou une nouvelle zigate) sur le port USB en appuyant sur le bouton de la ZiGate puis relacher.  

Vérifiez les informations de cette ZiGate depuis le menu "Menu -> Devices -> Device Info". Si c'est la même tout doit être identique, si c'est une nouvelle alors l'adresse MAC doit être différente.

Allez dans le menu "Menu -> Devices -> Program Device". Selectionner vos fichiers de Backup et mettez l'adresse MAC à la bonne valeur (MAC: cf note haut de page).

> Le restore du backup de la Flash ne fonctionne pas dans mon cas. Je n'ai pas trouvé pourquoi. Donc je selectionne le bin de la ZiGate. De même le changement de la MAC ne fonctionne pas donc je garde celle en place. Ce qui revient à ne reprogrammer que l'EEPROM...


![](../images/Capture_d_ecran_2018_02_28_a_10_32_14.png)

Voilà le restore est fait. Débranchez la ZiGate du port USB.

Vous avez une nouvelle ZiGate identique à l'originale (Sauf peut être l'adresse MAC). Si vous perdez la première (crash HW par exemple), il vous suffi de la remplacer par la nouvelle.

= Remplacer la ZiGate

Si pour une raison ou une autre vous devez/voulez remplacer la ZiGate alors il faut faire les actions suivantes:
(On part de l'hypotheses que Abeille/Jeedom est à jour).

* Remplacer la ZiGate par une nouvelle vide

* Redemarrer Abeille

* Depuis la ruche démarrer le réseau zigbee (La ZiGate doit être prête)

* Passer en mode inclusion

* Appairer tous les équipements du réseau

* Abeille mettra a jours les informtions dans Jeedom.

= A noter

=== Adresse MAC

Si l'adresse MAC change, il y a certainement des conséquences. La première que j'ai en tête est relative aux "Bind" qui utilisent les adresses MAC pour les adresses IEEE. Donc il doit falloir refaire les Bind. Tout ceci doit être vérifié et investigué.

# Link Status

Afin de comprendre la situation radio de votre réseau, vous pouvez utiliser ce script RadioVoisinesMap.php et visualiser les résultats dans un browser web:

http://[jeedom]/plugins/Abeille/Network/RadioVoisinesMap.php

Ce script va présenter graphiquement les informations échangées entre les routeurs dans les messages "Link Status".

Faites une capture du traffique avec wireshark, puis faites une sauvegarde JSON sous essai.json:

![](../images/Capture_d_ecran_2018_05_10_a_23_33_32.png)

![](../images/Capture_d_ecran_2018_05_10_a_23_33_48.png)

Une fois cela fait ouvrez la page: http://[jeedom]/plugins/Abeille/Network/RadioVoisinesMap.php

Vous devriez avoir un résultat comme:

![](../images/Capture_d_ecran_2018_05_10_a_23_43_31.png)

Dans le menu déroulent le premier champ permet de filtrer les enregistrement qui ont pour adresse de source la valeur selectionnée. Idem pour le deuxième champ mais pour l'adresse destination. Et enfin le dernier champ permet d'afficher la valeur du champ In ou du champ Out. La valeur In ou Out est la dernière valeur trouvée dans le fichier json lors de son analyse.

Evidement la configuration est celle de mon réseau de prod et de mon réseau de test donc il vous faut déclarer votre propre réseau dans le fichier NetworkDefinition.php.

Dans le tableau knowNE mettre l'adresse courte suiivie du nom de léquipement:

```
$knownNE = array(
"0000" => "Ruche",         // 00:15:8d:00:01:b2:2e:24 00158d0001b22e24 -> Production
// 00:01:58:d0:00:19:1b:22 000158d000191b22 -> Test
// Abeille Prod JeedomZwave
"dc15" => "T1",            // 00:0B:57:ff:fe:49:0D:bf 000B57fffe490Dbf
"1e8c" => "T2",
"174f" => "T3",            // 00:0b:57:ff:fe:49:10:ea
"6766" => "T4",

````

Puis dans le tableau Abeilles, définissez les coordonnées de chaque équipements:

````
$Abeilles = array(
'Ruche'    => array('position' => array( 'x'=>700, 'y'=>520), 'color'=>'red',),
// Abeille Prod JeedomZwave
// Terrasse
'T1'       => array('position' => array( 'x'=>300, 'y'=>450), 'color'=>'orange',),
'T2'       => array('position' => array( 'x'=>400, 'y'=>450), 'color'=>'orange',),
'T3'       => array('position' => array( 'x'=>450, 'y'=>350), 'color'=>'orange',),
'T4'       => array('position' => array( 'x'=>450, 'y'=>250), 'color'=>'orange',),
````

# LQI


Afin de comprendre la situation radio de votre réseau, vous pouvez utiliser ce script AbeilleLQI_Map.php et visualiser les résultats dans un browser web:

http://[adresse de votre jeedom]/plugins/Abeille/Network/AbeilleLQI_Map.php

Vous pouvez vérifier que l'execution est en cours en monitorant le log AbeilleParser. Vous devriez voir passer des messages comme celui ci (Type 804E):

```
AbeilleParser 2018-04-13 09:43:24[DEBUG]Type: 804E: (Management LQI response)(Decoded but Not Processed); SQN: 11; status: 00; Neighbour Table Entries: 0A; Neighbour Table List Count: 02; Start Index: 00; NWK Address: df33; Extended PAN ID: 28d07615bb019209; IEEE Address: 00158d00019f9199; Depth: 1; Link Quality: 152; Bit map of attributes: 1a
````

Si cela ne fonctionne pas, vous pouvez faire la manip à la main:
````
cd /var/www/html/plugins/Abeille/Network
php AbeilleLQI_Map.php
````


> C'est une version brute de fonderie alors il y a plein de bonnes raisons pour que cela ne fonctionne pas et demande votre expertise.


== Tableau

Le script va interroger tous les équipements qu'il détecte, un à un. Le procesus est assez long et pour l'instant la page reste blanche tant que la collect est en cours. Soyez donc patient. Si les résultats sont interessants, je verrai à faire une interface plus conviviale.

Lors de la premiere execution un tableau est généré avec toutes les informations collectées (les exemples ci dessous ne contiennent pas toutes les colonnes car depuis certaines ont été ajoutées.

=== Sur mon systeme de test, après 40s, cela donne:

.LQI Systeme de test
[width="100%",options="header,footer"]
|====================
|NE|Voisine|Relation|Profondeur|LQI
|0000|df33|Child|01|152
|0000|a008|Child|01|141
|0000|7bd5|Child|01|169
|0000|dcd9|Child|01|175
|0000|3950|Child|01|167
|0000|5dea|Child|01|144
|0000|4ebd|Child|01|161
|0000|633e|Child|01|219
|0000|c7c0|Child|01|174
|0000|d45e|Sibling|00|158
|d45e|0000|Sibling|00|186
|d45e|2389|Child|02|255
|====================

La première colonne contient l'adresse de l'équipement qui a été interrogé.
La deuxieme colonne continet l'adresse de l'équipement voisin connu
La troisieme colonne contient le type de relation entre les deux équipements.
La quatrieme colonne contient la profondeur de l'équipement dans l'arborescence du réseau.
La cinquième colonne contient le LQI (Link Quality Indicator), la qualité de la liaison radio.

On peut voir que le coordinateur "0000" a 9 enfants (des capteurs Xiaomi) et un "Sibling" qui est un routeur (Ampoule Ikea dans ce cas).

On peut y voir que le routeur d45e est "Sibling" avec le coordinateur (ZiGate). Qu'il possède un équipement enfant qui est donc en 2ieme niveau.

=== Sur mon système de prod

Celui ci contient au moins 8 routeurs (Ampoules Ikea et Prises Xiaomi).

Petites interrogations/Observation:
- des "Relation" sont "Unknown" : bug ou valeur remontée inconnue, uniquement sur ma HueGo actuellement.
- des "Profondeur" ont des valeurs "0F" qu'il faut que je comprenne.
- Aucun des routeurs ne possède de "Child".


Après 4 minutes, cela donne:

.LQI Systeme de production
[width="100%",options="header,footer"]
|====================
|NE|Voisine|Relation|Profondeur|LQI
|0000|1be0|Child|01|189
|0000|5571|Child|01|212
|0000|b774|Child|01|146
|0000|873a|Child|01|197
|0000|4260|Child|01|48
|0000|d43e|Child|01|151
|0000|6c0B|Child|01|51
|0000|0F7e|Child|01|194
|0000|f984|Child|01|59
|0000|2349|Child|01|81
|0000|345f|Child|01|94
|0000|28f2|Child|01|137
|0000|a728|Sibling|00|81
|0000|41c0|Sibling|00|167
|0000|174f|Sibling|00|51
|0000|46d9|Sibling|00|105
|0000|60fb|Sibling|00|80
|0000|a0da|Sibling|00|85
|0000|498d|Sibling|00|135
|0000|e4c0|Sibling|00|84
|a728|0000|Sibling|00|145
|a728|174f|Sibling|0F|27
|a728|41c0|Sibling|0F|76
|a728|46d9|Sibling|0F|90
|a728|498d|Sibling|0F|47
|a728|60fb|Sibling|0F|87
|a728|a0da|Sibling|0F|86
|a728|db83|Sibling|0F|63
|41c0|0000|Parent|00|171
|41c0|e4c0|Sibling|01|59
|41c0|db83|Sibling|01|169
|41c0|7714|Sibling|01|110
|41c0|498d|Sibling|01|146
|174f|0000|Sibling|00|97
|174f|1b7b|Sibling|0F|34
|174f|46d9|Sibling|0F|29
|174f|498d|Sibling|0F|21
|174f|60fb|Sibling|0F|29
|174f|6766|Sibling|0F|26
|174f|7714|Sibling|0F|45
|174f|8ffe|Sibling|0F|45
|174f|a728|Sibling|0F|29
|174f|db83|Sibling|0F|45
|174f|e4c0|Sibling|0F|20
|46d9|0000|Sibling|00|179
|46d9|174f|Sibling|0F|33
|46d9|41c0|Sibling|0F|61
|46d9|498d|Sibling|0F|119
|46d9|498d|Sibling|0F|119
|46d9|7714|Sibling|0F|83
|46d9|a0da|Sibling|0F|111
|46d9|a728|Sibling|0F|97
|46d9|c551|Sibling|0F|22
|46d9|db83|Sibling|0F|145
|46d9|e4c0|Sibling|0F|68
|60fb|0000|Parent|00|145
|60fb|174f|Sibling|0F|32
|60fb|41c0|Sibling|0F|63
|60fb|46d9|Sibling|0F|129
|60fb|498d|Sibling|0F|91
|60fb|6766|Sibling|0F|16
|60fb|7714|Sibling|0F|31
|60fb|8ffe|Sibling|0F|16
|60fb|a0da|Sibling|0F|85
|60fb|a728|Sibling|0F|93
|60fb|db83|Sibling|0F|112
|60fb|e4c0|Sibling|0F|30
|a0da|0000|Sibling|00|152
|a0da|41c0|Sibling|0F|70
|a0da|46d9|Sibling|0F|106
|a0da|498d|Sibling|0F|41
|a0da|60fb|Sibling|0F|81
|a0da|6766|Sibling|0F|17
|a0da|7714|Sibling|0F|46
|a0da|a728|Sibling|0F|91
|a0da|db83|Sibling|0F|63
|a0da|e4c0|Sibling|0F|50
|498d|db83|Parent|01|247
|498d|0000|Unknown|00|252
|498d|41c0|Unknown|02|252
|498d|7714|Unknown|02|247
|498d|46d9|Unknown|02|247
|498d|a728|Unknown|02|247
|498d|c551|Unknown|02|252
|498d|174f|Unknown|02|252
|498d|a0da|Unknown|02|252
|498d|60fb|Unknown|02|247
|498d|6766|Unknown|02|238
|498d|e4c0|Unknown|02|247
|498d|1b7b|Unknown|02|0
|498d|dc15|Unknown|02|0
|498d|8ffe|Unknown|02|0
|498d|8ffe|Unknown|02|0
|e4c0|0000|Sibling|00|152
|e4c0|41c0|Sibling|0F|106
|e4c0|174f|Sibling|0F|23
|e4c0|46d9|Sibling|0F|69
|e4c0|498d|Sibling|0F|80
|e4c0|60fb|Sibling|0F|31
|e4c0|7714|Sibling|0F|42
|e4c0|a0da|Sibling|0F|51
|e4c0|c551|Sibling|0F|20
|e4c0|db83|Sibling|0F|59
|====================

== Graphique Vieille Version

=== Configuration

Afin de visualiser les données, il vous faut modifier le fichier NetworkDefinition.php dans le repertoire Abeille/Network car celui-ci contient les équipements, leur nom et positions.

la premiere table:

$knownNE = array(
"0000" => "Ruche",         // 00:15:8d:00:01:b2:2e:24
// Abeille Prod JeedomZwave
"dc15" => "T1",
"1e8c" => "T2",
"174f" => "T3",            // 00:0b:57:ff:fe:49:10:ea
...

définie la liste des équipements en mettant leur adresse ZigBee et leur nom.

Dans la deuxieme table vous definissez les positions des équipements et leur couleur:

$Abeilles = array(
'Ruche'    => array('position' => array( 'x'=>700, 'y'=>520), 'color'=>'red',),
// Abeille Prod JeedomZwave
// Terrasse
'T1'       => array('position' => array( 'x'=>300, 'y'=>450), 'color'=>'orange',),
'T2'       => array('position' => array( 'x'=>400, 'y'=>450), 'color'=>'orange',),
'T3'       => array('position' => array( 'x'=>450, 'y'=>350), 'color'=>'orange',),


=== Graphique (Old Version)

Une fois la configuration faite vous devrier avoir le schéma de votre réseau. Par exemple pour moi, j'ai fait une configuration comprenant les équipements de mon réseau de production mais aussi le réseau de test. Capture d'écran des données du réseau de test:

![](..//Capture_d_ecran_2018_04_30_a_23_45_51.png)

On peut voir toutes les voisines rapportées par les équipements.

Vous pouvez choisir ce qui est affiché à l'écran:

- premier menu permet de selectionner les équipements qui ont remontés des voisines.
- second menu permet de selectionner les équipements qui ont été mentionné comme étant un voisin d'un autre équipement
- le troisieme menu permet en mode cache d'utiliser les fichier json contenant les informations collectées, le mode refresh permet d'interroger le reseau
- le dernier menu permet de selectionner l information affiché sur les fleches

Par exemple, je veux toutes les relations de voisinages alors dans le premier menu je choisi all.

Par exemple, je veux voir tous les équipements rapportant vori un équipement xxxx, je choisi none dans le premier menu et xxxx dans le second.

Dans la capture ci dessus on peut voir que le noeud Detecteur Smoke est un fils de l'ampoule bois bureau, alors que tous les autres équipements rapportent à la zigate en direct.

== Graphique (Nouvelle Version)

=== Configuration

Normalement après 24h les informations sont disponibles. Si vous n'avez pas les 24h ou souhaiter rafraichier les données, il faut avoir fait un "Recalcul du cache" (Network List->Table des noeuds->Recalcul du cache).

Juste un clic sur "Network Graph":

![](..//Capture_d_ecran_2018_10_04_a_02_39_04.png)

Juste ouvrir le graph et les abeilles seront disposées sur un grand cercle. Vous pourrez déplacer les abeilles (clic, deplacement, relache).

![](..//Capture_d_ecran_2018_10_04_a_02_24_10.png)



=== Filtre

![](..//Capture_d_ecran_2018_10_04_a_11_44_30.png)

Les abeilles sont toujours representées. Vous pouvez appliquer des filtres sur les voisines.

[quote,Kiwi]
____
Pour qu'une valeur soit prise en compte, clic sur le bouton Test associé en dessous.
____

* Source: La relation de voisinage qui a pour source la valeur selectionnée sera dessinée. All pour toutes et None pour aucune.

* Destination: La relation de voisinage qui a pour destination la valeur selectionnée sera dessinée. All pour toutes et None pour aucune.

* Parametre: permet de selectionner la valeurs associée à la relation qui sera imprimer le long du lien. Si le parametre choisi est le LinkQualityDec alors le code couleur est vert LQI bon, orange LQI moyen , rouge LQI pas bon.

* Relation: permet de choisir les relations hirarchique que l'on veut afficher.

* Save: permet de sauvegarder en local sur le PC CLient un graph.

* Restore: permet de recupérer un graph sauvegardé

Utilisation du filtre par l'exemple:

* Je veux voir toutes les abeilles vues par la ruche (zigate). Je choisi Ruche dans la source et none dans destination.

* Je veux représenter qui voit la sonnette. Je choisi Sonnette dans la destination et none dans la source.

* Je veux voir toutes les relations Child. Je mets All dans Source et Destination, Child dans Relation.

* Je choisi la valeur affichée le long de la ligne avec le parametre. Le plus utilisé probablement est LinkQualityDec qui represente la qualité de la relation radio dans le sens Source - Destination. Le nombre est entre 0 et 250. Pour des équipments proches d'environ 20cm j'ai des valeurs autour de 180. Au dessus de 220, je me dis que la valeur est farfelue surtout quand elle vaut 255. Tous les équipements ne semblent pas remonter des infos pertinentes. En dessous de 50 la liaison est vraiment pas bonne, il faut probablement faire quelque chose comme ajouter un routeur.

=== Exemples

Exemple avec tout positionné à la main:


![](..//Capture_d_ecran_2018_10_04_a_02_23_17.png)

Exemple qu'avec les relations Child (Filter Child):

![](..//Capture_d_ecran_2018_10_04_a_02_23_37.png)

On peut voir ici que j'ai 4 End Device sur la ruche(zigate), 5 sur la priseY,...

Vue interressante car elle permet de voir quels sont le équipements terminaux rattachés à quels routeurs.

Exemple en demandant la Ruche au centre:

![](..//Capture_d_ecran_2018_10_04_a_02_24_23.png)

Exemple avec l'upload d'une image en fond d'écran:

![](..//Capture_d_ecran_2018_10_04_a_11_15_34.png)

Vous pouvez aussi choisir votre fond d'écran pour positionner vos abeilles.

# Radio

Sur la base de la collecte de ces informations, j'ai fait quelques graphes pour comprendre ce qu'on espérer en terme de couverture radio.

Je n'ai pris que des routeurs dans cet exercice: prise xiaomi, prise ikea, ampoule ikea.
Comme tout est mélangé, type de routeur, types de murs (Fenetre, Bois, Pierre,...), Distances définies à vue d'oeil,.. cela permet d'avoir une vue d'un réseau réel.

Le premier graphe est le LQI rapporté par l'équipement en fonction du nombre de mur à traverser.
Le deuxieme graphe est le LQI en fonction de la distance à vol d'oiseau.

![](../images/Capture_d_ecran_2018_12_14_a_10_45_20.png)

Si l'on considère qu'avec un LQI inférieur à 50 la liaison radio est compliquée (basé sur une expérience partagée mais en rien mesurée) il faut resté dans la mesure du possible au dessus.

Cela nous indique qu'en moyenne plus de 2 murs est très compliqué. Ce qui implique un routeur dans chaque pièce pour être tranquile.

On peut voir des écarts très important dans le LQI alors que les équipements sont dans la meme piece (Colonne 0 des graphes LQI/Wall).

Pour le LQI/m, on peut dire que jusqu'à 10m c'est jouable. Mais on peut trouver les extrèmes aussi. Exemple: la zigate et une ampoule ikea à 16m pour un LQI de 117 alors que deux ampoules à 5 m on un LQI de 15.

Je suppose qu'en environnement ouvert on peut avoir des distances bien supérieures, avec des distances annoncées par les fabriquants jusqu'a 100m, mais ce type de situation sera des plus rares...

# Enjoy
