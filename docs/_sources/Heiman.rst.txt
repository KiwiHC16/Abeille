******
Heiman
******

Detecteur de fumée
******************

Inclusion
=========

ZiGate en mode Inclusion
Trombone dans petit trou sur le côté pendant 4s, le device fait un Beacon, flash en vert en face avant, s'appaire et est créé dans Abeille.

.. image:: images/Capture_d_ecran_2019_07_06_a_11_40_01.png

Exclusion
=========

Trombone dans petit trou sur le côté pendant 8s, le device fait un Leave. Bouton face avant flash plusieurs fois en vert.

Dans le réseau
--------------

Le bouton en face avant flash une fois de temps en temps en rouge environ toutes les 45 secondes.

Batterie
--------

L'équipement remonte le niveau de sa batterie. Abeille le traite et l'affiche.

Alarme
------

L'information brute remonte dans Abeille. Par défaut sans alarme la valeur est à 20. Avec alarme l'information est à 21.

Un appui sur le bouton de la face avant provoque une alarme (un message remonte) et lors de la relache un autre message est envoyé pour indiquer fin d'alarme.

Vous pouvez décoder les informations remontant sur la base de :

.. image:: images/Capture_d_ecran_2019_07_09_a_15_51_27.png

.. note:: Pour tester, prendre un briquet, l'allumer et le placer sous le capteur. Le capteur active son alarme, attention aux oreilles, et le champ passe à 21 dans Abeille.
