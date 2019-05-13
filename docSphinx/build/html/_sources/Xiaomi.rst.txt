Xiaomi
======

Tous
----

Tous les périphériques classiques


=== Nouvelle inclusion

* Mettre la Zigate en mode inclusion (Bouton Inclusion), la Led bleue de la Zigate doit clignoter...
* Appui long de 7s sur le bouton du flanc de l'équipement, l'équipement doit se connecter et un objet doit apparaître dans Jeedom.

=== Déjà inclue

* Zigate en fonctionnement normale
* Appui court sur le bouton du flanc de l'équipement et l'objet Abeille doit être créé.


== Bouton Rond

Bouton Rond (lumi.sensor_switch)

Ce bouton envoie un message lors de l'appui mais aussi lors du relâchement. L'état dans Abeille/Jeedom reflète l'état du bouton.

== Bouton Carre

Bouton Carre (lumi.sensor_switch.aq2)

Contrairement au bouton rond ci dessus, le bouton carré n'envoie pas d'information sur l'appui. Il envoie l'information que sur la relache.

Afin d'avoir le visuel sur le dashboard, l'état passe à 1 sur la réception du message et Jeedom attend 1 minute avant de le remettre à 0.

=== Informations

Informations complémentaires

Du fait de ce fonctionnement, nous ne pouvons avoir une approche changement d'état. Il faut avoir une approche événement. De ce fait la gestion des scénarii est un peu différente du bouton rond.

Par défaut le bouton est configuré pour déclencher les scénarii à chaque appui (même si l'état était déjà à 1). Mais Jeedom va aussi provoquer un événement au bout d'une minute en passant la valeur à 0.

Lors de l'exécution du scénario, si vous testé l'état du bouton est qu'il est à un vous avez reçu un événement appui bouton, si l'état est 0, vous avez reçu un événement retour à zéro après une minute.

Par exemple pour commander une ampoule Ikea:

image:Capture_d_ecran_2018_09_04_a_13_05_49.png[]

image:Capture_d_ecran_2018_09_04_a_13_05_.36.png[]

== Multi

Pour l'information multi, celle ci remonte quand on fait plus d'un appui sur le bouton. Multi prend alors la valeur remontée. Le bouton n'envoie pas d'autre information et donc la valeur reste indéfiniment. Par défaut l'objet créé demande à Jeedom de faire un retour d'état à 0 après une minute. Cela peut être enlevé dans les paramètres de la commande.

Le fonctionnement de base va provoquer 2 événements, un lors de l'appui multiple, puis un second après 1 minute (généré par Jeedom pour le retour d'état). Si vous enlevez de la commande le retour d'état alors vous n'aurez que l'événement appui multiple.
Par défaut, en gros, le scénario se déclenche et si vous testez la valeur multi > 1, c'est un événement appui multiple et si valeur à 0 alors événement Jeedom de retour d'état.

== Inondation

Capteur Inondation (lumi.sensor_wleak.aq1)

* Appui court (<1s) sur le dessus

Remonte son nom et attribut ff01 (longueur 34)

== Porte V1

Capteur de Porte Ovale (lumi.sensor_magnet)

* Appui court (<1s) avec un trombone

Remonte un champ ff02 avec 6 éléments
Puis son nom lumi.sensor_magnet

== Porte V2

Capteur Porte Rectangle (lumi.sensor_magnet.aq2)

* Appui court (<1s) sur bouton latéral

Remonte son nom et ff01 (len 29)


* Appui Long (7s) sur bouton latéral

Inclusion
Remonte son nom et Application Version
Remonte ff01 (len 29)

* Appui court (<1s) avec trombone

* Appui long (7s) avec trombone

Inclusion
Remonte son nom
Remonte Appli Version
Remonte ff02 avec 6 éléments

* Double flash bleu sans action de notre part

Visiblement quand le capteur fait un rejoin après avoir perdu le réseau par exemple, il fait un double flash bleu.

== Présence V2

Capteur de Présence V2

* Appui court (<1s) sur bouton latéral

Remonte son nom et FF01 de temps en temps.

* Appui long (7s) sur bouton latéral

Inclusion
Remonte son nom et SW version
Remonte FF01 (len 33)

* Comportement

Il remonte une info a chaque détection de présence et remonte en même temps la luminosité. Sinon la luminosité ne remonte pas d'elle même. Ce n'est pas un capteur de luminosité qui remonte l'information périodiquement.

== Température V1

Capteur Température Rond (lumi.sensor_ht)

* Appui court (<1s) sur bouton latéral

Remonte son nom

* Appui long (7s) sur bouton latéral

Exclusion
Inclusion
Remonte son nom et appli version
Remonte ff01 (len 31)

== Température V2

Capteur Température Carré (lumi.weather)

* Appui court (<1s) sur bouton latéral

Si sur le réseau: Remonte son nom
Si hors réseau et Zigate pas en Inclusion: Un flash bleu puis un flash bleu unique
Si hors réseau et Zigate en Inclusion: Un flash bleu, pause 2s, 3 flash bleu

* Appui long (7s) sur bouton latéral

Exclusion
Inclusion
Remonte son nom et appli version
Remonte ff01 (len 37)

* Comportement

	* Si détection de petite variation de température ou humidité, rapport une fois par heure
	* Si variation de plus de 0,5°C ou de plus de 6% d'humidité, rapport immédiat

* Précision (Source Appli IOS MI FAQ Xiaomi)

	* Température +-0,3°C
	* Humidité +-3%

== Cube Aqara

image:../images/Capture_d_ecran_2018_06_12_a_22_00_03.png[]

== Wall Switch 1

Wall Switch Double Battery (lumi.sensor_86sw2)

* Appui long (7s) sur bouton de gauche

Inclusion
Remonte son nom et appli version
Remonte ff01 (len 37)

* getName

Il répond au getName sur EP 01 si on fait un appuie long sur l'interrupteur de droite (7s) et pendant cette période on fait un getName depuis la ruche.

* Appui très Long (>10s) sur bouton de gauche

Exclusion

== Wall Switch 2

Wall Switch Double 220V Sans Neutre (lumi.ctrl_neutral2)

* Appui long (7s) sur bouton de gauche

Inclusion
Remonte son nom et appli version
Remonte d'autres informations

* getName

Il répond au getName sur EP 01 s.

* Appui Tres Long (>8s) sur bouton de gauche

Exclusion

== Vibration

Capteur Vibration

* Appui long (7s) sur bouton de gauche

Inclusion
Remonte son nom et appli version
Remonte d'autres informations

* Attribute 0055

Il semblerai qu'une valeur:

* 1 indique une détection de vibration
* 2 indique un rotation
* 3 indique une chute

* Attribute 0503

Pourrait être la rotation après l'envoi de l'attribut 0055 à la valeur 2

* Attribute 0508

Inconnu, est envoyé après attribut 0055.

== Fumée

Capteur de fumée

* 3 appuis sur le bouton de façade

Inclusion ou Exclusion si la Zigate n'est pas en mode inclusion

* Sensibilité du capteur

Il est possible de définir le seuil de détection du capteur: 3 niveaux (En développement).

* Test du capteur

Avec le bouton tester, vous envoyez un message au capteur qui doit réagir avec un bip sonore (3 messages envoyés par Abeille, il doit y avoir entre 1 et 3 bips).

* Réveil

Le capteur se réveille toutes les 15s pour savoir si la Zigate à des infos pour lui.

== Gaz

Capteur Gaz

Ce capteur est un router.

* Paramètres

Vous pouvez choisir le niveau de sensibilité: Low - Moyen - High

* Tester la bonne connexion au réseau

Avec le bouton tester, vous envoyez un message au capteur qui doit réagir avec un bip sonore (3 messages envoyés par Abeille, il doit y avoir 3 bips à 5s d'intervalles).
