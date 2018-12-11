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


