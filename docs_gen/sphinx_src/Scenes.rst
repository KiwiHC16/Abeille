######
Scènes
######

*****
Intro
*****

Les scènes permettent d'envoyer un seul message Zigbee et d'avoir multiple équipement qui se mette en position automatiquement.

Une scène peut être: "Séance TV", qui allumera la TV, fermera les volets et mettra une lumière tamisée en place.

Pour ce faire chaque équipement doit savoir ce qu'il doit faire lorsqu'il reçoit la commande. Il doit donc avoir été paramétré avant.

Pour l'instant tout le paramétrage se fait depuis l'objet Ruche.


*****
Video
*****

`Scene et telecommande.  <https://youtu.be/SKYQxPAb9W0>`_

`Gestion des scenes sur une ampoule IKEA.  <https://youtu.be/yzhu3Hu_ibs>`_


*****
Ajout
*****

Ajout d une scène à un équipement

* en cours ...

*******
Retrait
*******

Retrait d une scène à un équipement

* en cours ...

*********
Récupérer
*********

Récupérer les scènes d'un équipement

* en cours ...

*********
Remarques
*********

* "Get Scene Membership" interroge l équipement pour avoir les scenes associées à un groupe mais Abeille ne peut traiter la réponse incomplète. La modification est en cours de dev cote firmware zigate et ne fonctionnent pas a ce stade. Par contre en attendant vous pouvez voir passer la réponse dans le log AbeilleParser message "Scene Membership"
* "View Scene" interroge l équipement pour avoir les détails d une scene mais Abeille ne peut traiter la réponse incomplète. La modification est en cours de dev cote firmware zigate et ne fonctionnent pas a ce stade. Par contre en attendant vous pouvez voir passer la réponse dans le log AbeilleParser message "Scene View"
* "scene Group Recal" n est pas encore fonctionnelle.
