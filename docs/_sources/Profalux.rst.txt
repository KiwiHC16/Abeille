########
Profalux
########

*****
Volet
*****

Inclusion d'un volet
====================

Comme pour tous modules Zigbee et pour bien comprendre la procédure, il faut savoir que :

La Zigate est un coordinateur Zigbee qui permet de contrôler / créer un réseau. De la même manière que le couple télécommande / ampoule Zigbee, il est important que les deux matériels appartiennent et soient authentifiés sur le même réseau.

N’ayant pas de boutons ou d’interfaces, un volet Profalux Zigbee ne peux pas rentrer tout seul sur un réseau Zigbee. Il est indispensable d’avoir une télécommande maître qui jouera le rôle d’interface entre le volet et la Zigate.

A savoir tout au long de cette procédure : lorsque le volet fait un petit va et vient c'est le signe que la commande a bien été reçue.

Etape 1
-------

Étape 1: Remise à zéro de la télécommande et du volet.

* Retourner la télécommande.
* A l’aide d’un trombone, appuyer 5 fois sur le bouton R.

La télécommande va clignoter rouge puis vert.

.. image:: images/profalux_inclusion_etape1.png

Le volet va faire un va et vient (attendre un petit moment).

Attendre que la télécommande ne clignote plus.

* Dans la minute qui suit, appuyer sur STOP.

Le volet va faire un va et vient.

Pour tester le bon fonctionnement, vous devriez pouvoir piloter le volet avec la télécommande.

Si jamais les commandes de votre volet sont inversées, il suffit à l'aide d'un trombone d'appuyer sur fois sur F et ensuite une fois sur STOP.

* Fermer le volet complètement.

Étape 2
-------

Inclusion du volet

Mettre la ruche en mode inclusion

* Pour cela appuyer sur le bouton inclusion depuis le plugin Abeille (La Zigate doit clignoter bleue)

.. image:: images/inclusion.png

Une fois le réseau de la Zigate ouvert, il ne vous reste plus qu’à:

* Retourner votre télécommande
* Appuyer 1 fois sur R
* Appuyer ensuite sur la flèche du haut

Le moteur devrait faire plusieurs va et vient …
Attendre que la télécommande ne clignote plus !

* Pour finir, appuyer sur la touche STOP de la télécommande.

* Ouvrir et fermer votre volet complètement 2 fois.

A la fin de la 2ème fermeture, le volet fera un petit va et vient.

Faites un rafraichissement de votre dashboard et votre volet devrait apparaitre !!!


Résolution
==========

Résolution de problèmes


Le volet
--------

Le volet ne répond plus à la télécommande.

Si par une mauvaise manipulation votre volet ne répond plus à la télécommande, il est nécessaire de faire un reset de la télécommande et du volet.

* Retourner l’appareil
* A l’aide d’un trombone, appuyer 5 fois sur le bouton R

.. image:: images/profalux_inclusion_etape1.png

Attention c'est une manipulation dangereuse !

* Couper l'alimentation électrique
* Réunir les fils noir et marron puis les brancher sur la borne de phase

.. image:: images/profalux_inclusion_reset_volet2.png

* Remettre l'alimentation électrique pendant 5 secondes. Le volet devrait faire un petit mouvement.
* Couper l'alimentation électrique
* Séparer les fils noir et marron. Brancher le fils marron sur la phase. Si votre fils noir était brancher avec le bleu aupparavant, rebrancher le avec le bleu sinon laisser le fils noir seul en pensant à l'isoler(capuchon noir)

.. image:: images/profalux_inclusion_reset_volet3.png

* Remettre l'alimentation électrique et dans la minute appuyer sur le bouton stop

.. image:: images/profalux_inclusion_reset_volet4.png

Le volet devrait faire des mouvement de va-et-vient puis s'arrêter
* La télécommande devrait à nouveau fonctionner
* Recommencer à nouveau la procédure d'inclusion
