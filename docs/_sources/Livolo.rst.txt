######
Livolo
######


****************
Caracteristiques
****************

Visiblement Livolo utilise uniquement le canal radio 26. Cela implique de mettre la zigate sur ce canal. Pour définir le canal allez dans la page du plugin et suivez les instructions du chapitre "Channel Mask".

.. note::

  quand vous faites cela, la zigate va changer de canal mais si vous avez des équipements déjà sur le réseau ils ne seront pas au courant et vous allez les perdre.
  Vous devrez refaire une inclusion pour les retrouver.


**************************
Interrupteur 1 btn & 2 btn
**************************

Inclure
-------

Un appui long (> 6s) sur le bouton sensitif au centre de la face avant jusqu'à émission d'un bip doit provoquer l'association (Zigate en mode inclusion).

l'équipement doit se connecter et un objet doit apparaître dans Jeedom.


Retour d'état
-------------

Pour l'instant en version 3.1a de la zigate, le retour d'état envoyé par le bouton (si vous utilisez le bouton directement par exemple) n'est pas transmis par la zigate à Abeille. Sera certainement dans les versions futures.

Si vous controllez le bouton depuis Abeille, alors Abeille fait la demande d'état après envoie d'une commande.


.. note::

  * Les deux types d'interrupteurs s'annoncent sous le même nom "TI0001". Il n'y dans Abeille qu'un modèle car les deux types se comportent de la même facon.
  * Par defaut Abeille crée un modèle 2 boutons. Rendre non visible les commandes On et Off pour le second boutons si vous avez un modèle 1 bouton.

.. note::

  * Ils ne semblent pas aimer les commandes non supportées et se met en carafe et ne répond plus.

.. note::

  * Ces interrupteurs peuvent avoir un comportement ou ils ne repondent plus après quelques minutes. Il y a un trick Livolo pour qu'ils restent actifs. Si cela se produit refaite une inclusion pour qu'abeille applique le trick.
