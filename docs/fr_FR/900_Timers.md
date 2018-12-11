# Timers

Depuis pas mal de temps je souhaitais avoir des objets Timers à la seconde dans Jeedom.
Après plusieurs versions avec des scripts, des variables, des retours d'état automatique,... je me suis rendu compte que je pouvais sans trop de difficulté créer ces timers au seins d'Abeille.

Maintenant vous pouvez même installar Abeille en n'utilisant que les Timers sans la partie ZigBee. Pour cela dans la configuration du plugin choisissez "Mode Timer seulement" à "Oui".

## Fonctionnement

![i1](../images/Capture_d_ecran_2018_03_21_a_13_16_53.png)

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

Dans l'onglet Commandes, nous allons paramétrer les actions du Timer.

![i4](../images/Capture_d_ecran_2018_03_21_a_13_33_37.png)

### Start 

actionStart=\#put_the_cmd_here#&durationSeconde=300

Pour la commande il y a deux parametres.

* durationSeconde: par exemple ici 300s soit 5min.

* actionStart doit être de la forme \#[Objet Parent][Objet][Cmd]# par exemple: \#[Ruche][Abeille-89ff-AmpouleBureau][On]#.

### Cancel

actionCancel=\#put_the_cmd_here#

* actionCancel doit être de la forme \#[Objet Parent][Objet][Cmd]# par exemple: \#[Ruche][Abeille-89ff-AmpouleBureau][Off]#.

### Stop

actionStop=\#put_the_cmd_here#

* actionStop doit être de la forme \#[Objet Parent][Objet][Cmd]# par exemple: \#[Ruche][Abeille-89ff-AmpouleBureau][Off]#.

Exemple plus spécifique: Envoie d'un SMS

actionStop=\#[operation][SMS_Home][Telephone]#&message=Mettre votre message sms ici



## Commande ou Scenario

Par defaut l'objet Timer est créé avec des commande Start, Stop, Cancel qui font reférence à l'execution d'une commande: actionStart=\#put_the_cmd_here#, actionCancel=\#put_the_cmd_here#, actionStop=\#put_the_cmd_here#. 

Mais vous avez la possibilité d'appeler un scenario à la place d'une commande.

Cela vous permet beaucoup plus de flexibilité comme le lancement d'une série de commandes.

La syntaxe: scenarioStart=Id,scenarioCancel=Id, scenarioStop=Id, en remplacant Id pour l'identifiant du scenario que vous trouvez dqns la definition du scenario.

![i5](../images/Capture_d_ecran_2018_03_27_a_12_52_53.png)

Un exemple avec les commandes et les scenarii.

![i6](../images/Capture_d_ecran_2018_03_27_a_12_55_27.png)

Et ici vous pouvez voir l'ID 3 du scenario utilisé.

## Commande Start Complete

actionStart=\#put_the_cmd_here#&durationSeconde=300&RampUp=10&RampDown=10&actionRamp=\#put_the_cmd_here#

Le timer possede 4 phases:

T0->T1: RampUp de 0 a 100% => RampUp
T1->T2: Stable a 100% => durationSeconde
T2->T3: Ramp Down de 100% à 0% => RampDown
T3-> : n existe plus

Dans les phase de ramp la commande actionRamp/scenarioRamp est executée regulierement avec pour parametre la valeur en cours de RampUpDown.

Exemple d'application: allumage progressif d une ampoule, maintient allumé pendant x secondes puis extinction progressive.

## A prendre en compte

[quote,]
____
Il est important de noter que chaque phase fait au minimum 1s.
____


[quote,]
____
La rafraischissement du widget se fait toutes les 5s mais la mise a jour des valeurs se fait toutes les secondes.
____
