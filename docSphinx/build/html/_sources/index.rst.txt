.. Abeille documentation master file, created by
   sphinx-quickstart on Sat Apr 27 11:38:14 2019.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

Welcome to Abeille's documentation!
===================================

Le manuel de l'utilisateur possède plusieurs chapitre essayant de détailler chaque sujet.
En tant qu'utilisateur final, le chapitre link:Tuto.html[tuto] vous sera probablement le plus intéressant, mais noubliez pas les autres surtout si vous débuteez avec Abeille et Zigate.

::

    Cette documentation est en cours de "ré-écriture".

::

    Plugin en développement continu. Le développement n est pas terminé. De nombreux ajustements sont en cours ...

::

    Cette documentation est la derniere version disponible. Elle ne dépend pas de la verion Abeille sur votre système. Cette doc peut d écrire des fonctionalité pas encore disponibles dans votre système. Et inversement cette doc peut ne pas être à jour si des fonctions sont dans Abeille mais je n ai pas fait la  doc.

Cagnotte
========

`Cagnotte pour acheter des équipements pour supporter le développement. <https://paypal.me/KiwiHC16>`_

.. image:: images/Cagnote.png

`Participer à la Cagnotte <https://paypal.me/KiwiHC16>`_

Le budget depuis le début est assez conséquent, surtout quand j'investis dans des équipements que je n'utiliserai pas moi même.
Alors j'ouvre une cagnotte pour financer l'achat d'équipements pour faciliter l'intégration dans Abeille.
L'idée est d'avoir au moins un exemplaire de ce que les utilisateur d'Abeille utilisent.
Je n'ai aucune idée de votre participation à cette cagnotte mais comme tout est open source depuis le début, la cagnotte sera aussi `complètement transparente (Tous les détails) <https://github.com/KiwiHC16/Abeille/blob/master/README.md>`_.

Abeille
========

Le chapitre link:presentation.html[Présentation] contient les premières informations nécessaires à l'utilisation du plugin Abeille.

image:Abeille_icon.png[]

Timer
========

Le plugin contient une fonctionalité link:timer.html[Timer] qui peut être utilisé avec ou sans la zigate et le réseau zigbee associé. Ces link:timer.html[timers] fonctionnent à la seconde ce qui n'existe pas à ma connaissance dans Jeedom.

.. image:: images/node_Timer.png

Installation
========

Pre-requis
----------

Avant de vous lancer dans l'installation d'Abeille, vérifiez que vous avez un link:Systeme.html[systeme Jeedom] qui est dans le cadre de ce developpement.

Installation
------------

La première étape est l'link:Installation.html[installation] du plugin dans Jeedom.

Parametrage
-----------

Une fois l'link:Installation.html[installation] faite, il est nécessaire de vérifier la configuration du plugin et de l'adapter à votre situation, pour se faire il est nécessaire de faire le link:Parametrage.html[parametrage] du plugin.

Quand tout est en place, vous allez ajouter des équipements. Afin d'avoir une interface graphique jolie dans Jeedom vous pouvez adapter les link:Widget.html[Widgets] qui seront utilisés. C'est optionel. C'est juste pour faire beau.



Utilisation
===========

Inclusion
=========

La première étape pour pouvoir utiliser un équipement est de l'link:Inclusion.html[inclure] dans le réseau zigbee géré par la zigate. Il n'existe pas de méthode universelle car chaque fabriquant est libre de procéder de la façon qu'il souhaite. Le grand principe est de mettre la zigate en mode link:Inclusion.html[inclusion] et de faire des manipulations sur l'équipement pour qu'il s'link:Inclusion.html[inclus] et rejoigne le réseau.

Groups
======

Un fois l'équipement dans le réseau, la zigate lui attribue une adresse pour qu'il puisse dialoguer. La zigate peut lui envoyer des messages directement à cette adresse.
Maintenant si la ZiGate souhaite envoyer un même message à plusieurs équipements en même temps, elle peut utiliser une adresse de link:Groups.html[groupe]. Cela permet d'avoir par exemple des équipements qui réagissent simultanément.

Scenes
======

Le link:Groups.html[groupe] permet d'adresser des équipements en même temps pour une même action mais ne permet pas d'envoyer des demandes differentes. Ce point est résolu par les link:Scenes.html[scenes]. Vous pouvez preconfigurer une ensemble d'équipments dans des configurations spécifiques est les associer à des link:Scenes.html[scenes]. Ensuite il vous suffit de rappeler une link:Scenes.html[scene] pour remettre tout le monde dans la configuration désirée.

Santé
=====

Le réseau est constitué de nombreux équipements qui vivent au rythme et aléa du réseau radio, des coupures de courant, etc. Afin de monitorer le réseau Abeille propose plusieurs outils: link:Health.html[Health]

Polling
=======

Certains équipements communiquent naturellement et échangent des messages avec la Zigate, ce qui nous permet de s'assurer qu'ils sont en vie. D'autres restent silencieux et nous devons les interroger pour savoir s'ils sont toujours dans le réseau ou simplement connaitre leur état. Abeille contient une fonction de link:Polling.html[Polling] interrogeant régulièrement les équipements.

Cron
====

Abeille de façon régulière fait link:cron.html[un certain nombre de taches] pour maintenir/monitorer le système.

Radio
=====

Le ZigBee fonctionne en link:Radio.html[Radio]. La link:Radio.html[radio] est sujette à divers problemes. Même si la norme Zigbee inclus plein de fonction pour nous faciliter la vie, il arrive que cela ne fonctionne pas aussi bien qu'attendu. Pour avoir une meilleur comprehension de ce qui se passe, Abeille inclus des informations, graphes representant les informations link:Radio.html[radio] récupérées par les équipements.

Tuto
====

Dans la mesure ou Abeille à pour objectif d'exploité le réseau, je vous propose quelques link:Tuto.html[Tuto] permettant de mettre en application et répondre à vos besoins.

Trucs et Astuces
================

D'autres fonctions de Jeedom sont bien pratiques, par exemple si vous devez link:Remplacement.html#Remplacement-Equipement[remplacer des équipements par d'autres], ou link:Remplacement.html#Remplacement-Commande[des commandes par d'autres].


Equipements
===========

Ikea
----

Ce chapithre regroupe les informations sur les équipements link:Ikea.html[Ikea]

Osram
-----

Ce chapithre regroupe les informations sur les équipements link:OSRAM.html[OSRAM]

Philips Hue
-----------

Ce chapithre regroupe les informations sur les équipements link:PhilipsHue.html[Philips Hue]

Profalux
--------

Ce chapithre regroupe les informations sur les équipements link:Profalux.html[Profalux]

Xiaomi
------

Ce chapithre regroupe les informations sur les équipements link:Xiaomi.html[Xiaomi]

Eurotronics
-----------

Ce chapithre regroupe les informations sur les équipements link:Eurotronics.html[Eurotronics]

Livolo
------

Ce chapithre regroupe les informations sur les équipements link:Livolo.html[Livolo]


Changelog
=========

Afin de vous donner de la visibilité sur l'évolution du plugin vous trouverez des informations génériques dans link:changelog.html[ChangeLog].
Bien évidement pour les personnes qui savent développer toutes les informations sont disponibles dans link:https://github.com/KiwiHC16/Abeille/commits/master[Abeille GitHub]


Avancé
======

Debug
=====

Si vous rencontrez des soucis, je vous propose ce chapitre link:Debug.html[Debug] vous donnant les méthodes à suivre pour vérifier les points de bon fonctionnement.

Developement
============

Si certains d'entre vous souhaitent comprendre ou modifier le code, vous des informations de base pour comprendre la structure de link:Developpement.html[developpement].

Modèles
=======

Les équipements ZigBee sont representés dans Abeille par des fichiers de configuration appelés: link:ModeleJson.html[Modeles Json]. Ces fichiers peuvent être modifiés et d'autres peuvent être créer pour ajouter de nouveau équipements.

Docker / VM
===========

Ce chapitre est très spécifique et pour les utilisateurs ayant de bonnes connaissances en informatique. Je partage ici mon installation link:Docker.html[docker/VM] qui me permet de s'implifier mon developpement et de faire des backup de mes Jeedom distant et difficilement accessibles.

Backup/Restore
==============

Ce chapitre ne devrait être utilisé que pour les personnes qui developpe le firmware ZiGate et qui comprennent ce qu'ils font. Cela permet de faire un link:ZiGateBackupRestore.html[Backup-Restore] de l'EEPROM Zigate. Pour les autres SVP ne jouez pas avec cette méthode cela vous amenera plus de soucis de de biens.

Wifi
====

J'ai developpé mon propre module link:Wifi.html[Wifi] du fait de petits soucis sur le premier module disponible. Finalement il fonctionne tellement bien et fourni une protection coupure electrique avec une batterie que je l'utilise dans toutes configuration et mêm avec mon sniffer ZigBee ...

.. toctree::
   :maxdepth: 2
   :caption: Contents:

   help
   Ikea
   changelog


Indices and tables
==================

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`
