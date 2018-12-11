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


# Raccourcis

[Premiers pas](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/010_Introduction.adoc)

[Pour Tous](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/)

[Equipements supportés](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/040_Compatibilite.adoc)

[Pour les développeurs](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/012_Dev.adoc)

[Systèmes Testés](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/015_Systemes_Testes.adoc)

[Change Log](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/075_version.adoc)

[links](900_Timers.md)


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

# Equipements

## Ikea

### Ampoule

#### Bouton Identify

Ce bouton est créé au moment de la création de l'objet. Celui ci permet de demander à l'ampoule de se manifester. Elle se met à changer d'intensité ce qui nous permet de la repérer dans une groupe d'ampoules par exemple.

#### Creation objet

- Si l'ampoule n'est pas associée à la zigate, avec Abeille en mode Automatique, une association doit provoquer la création de l'obet dans Abeille

- Si l'ampoule est déjà associée à la zigate, avec Abeille en mode Automatique, 
* l'allumage électrique doit provoquer l'envoie par l'ampoule de sa présence (annonce) et la création par Abeille de l'objet associé.
* l'extinction électrique pendant 15s puis allumage électrique doit provoquer l'envoie par l'ampoule de sa présence (son nom) et la création par Abeille de l'objet associé. 
* Vous pouvez aussi Utiliser la commande getName dans la ruche, mettre l’adresse courte dans le titre et rien dans le message. Puis rafraichir le dashboard et la l’ampoule doit être présente.

#### Retour d'état

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


#### Bind specifique:

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


#### Telecommnande Ronde 5 boutons

#### Télécommande réelle

(Pour l'instant c'est aux équipements qui recevoient les demandes de la telecommande reelle de renvoyer leur etat vers jeedom, sur un appui bouton telecommande, la ZiGate ne transmet rien au plugin Abeille).

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


#### Télécommande Virtuelle

La télécommande virtuelle est un objet Jeedom qui envoies les commandes ZigBee comme si c'était une vrai télécommande IKEA.

Utiliser les commandes cachées dans la ruche:

* Ouvrir la page commande de la ruche et trouver la commande "TRADFRI remote control".

![](../images/Capture_d_ecran_2018_03_02_a_10_34_40.png)

Remplacez "/TRADFRI remote control/" l'adresse du groupe que vous voulez controler. Par exemple AAAA.

![](../images/Capture_d_ecran_2018_03_02_a_10_35_08.png)

Sauvegardez et faites "Tester".

Vous avez maintenant une télécommande pour controler le groupe AAAA.

![](../images/Capture_d_ecran_2018_03_02_a_10_35_28.png)


### Gradateur

#### Un clic sur OO

Un clic sur OO envoie un Beacon Request. Même si la zigate est en inclusion, il n'y a pas d'association (Probablement le cas si deja associé à una utre reseau).

#### 4 clics sur OO

Message Leave, puis Beacon Requets puis association si réseau en mode inclusion. Une fois associé, un getName avec un reveil du gradateur permet de recuperer le nom.

Voir la telecommande 5 boutons pour avoir plus de details sur le controle de groupe,...

## Philips Hue

###  Creation objet dans Abeille

#### Association

- Ampoule neuve Hue White, Abeille en mode Inclusion, branchement de l'ampoule. L'ampoule s'associe et envoie des messages "annonce" mais pas son nom. Si vous faites un getName avec son adresse courte dans le champ Titre et 0B (destinationEndPoint) dans le champ Message, alors elle doit répondre avec son nom, ce qui va créer l'objet dans le dashboard (rafraichir).


#### Si deja associé

- Si l’ampoule est déjà associée à la zigate, avec Abeille en mode Automatique,

* l’extinction électrique pendant 15s puis allumage électrique doit provoquer l’envoie par l’ampoule de sa présence et la création par Abeille de l’objet associé.

* Utiliser la commande getName dans la ruche, mettre l'adresse courte dans le titre et 03 (destinationndPoint) dans le message. Puis rafraichir le dashboard et la l'ampoule doit être présente.

### Philips Hue Go

#### Association

#### Si deja associé

* tres long appui sur le bouton arriere de l ampoule plus de 40s probablement avec la zigate qui n'est pas en mode inclusion. La lampe se met a flasher. Elle s'est deconnectée du réseau. Mettre la zigate en Inclusion et la lampe envoie des messages "annonce" et elle doit se créer dans Abeille.

#### Colour Control

Sur un objet ampoule vous pourrez trouver la commande Colour:

image::images/Capture_d_ecran_2018_02_13_a_23_07_50.png[]

Dans le premier champ indiquez la valeur X et dans le deuxième champ la valeur Y.

Par exemple:

* 0000-0000 -> Bleu
* FFFF-0000 -> Rouge
* 0000-FFFF -> Vert

#### Group Control

image::images/Capture-d_ecran_2018_02_14_a_11_15_18.png[]

Avec ca je commande la Philips Hue depuis télécommande Ikea ronde 5 boutons ...

### Telecommande / Philips Hue Dimmer Switch

#### Association

Appuie avec un trombone longtemps sur le bouton en face arriere "setup" avec la zigate en mode Inclusion. Un objet télécommande doit être créé dans Abeille.


#### Récupérer le groupe utilisé

Approcher la telecommande d'une ampoule de test qui est sur le reseau. Faire un appui long >10s sur le I de la télécommande. Attendre le clignotement de l'ampoule. Ca doit être bon. Si vous appuyé sur I ou O, elle doit s'allumer et s'éteindre. Et les bouton lumière plus et moins doivent changer l'intensité. Ensuite vous pouvez récupérer le groupe en interrogeant l'ampoule depuis la ruche avec un getGroupMembership. 

#### Reset d une ampoule

Si vous appuyez, sur I et O en même temps à moins de quelques centimetres, l'ampoule doit faire un reset et essayer de joindre un réseau. Si la zigate est en mode inclusion alors vous devez récurerer votre ampoule. Ca marche sur des ampoules Hue et Ikea, probablement pour d autres aussi.


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
