Radio
===========

LQI
---

[[NetworkList]]
Network List
^^^^^^^^^^^^

Depuis le menu: Plugins->Protocole Domotique->Abeille->Network List vous allez arriver sur une page contenant 3 volets.

==== Résumé

Le résumé vous donne un appercu de l'état du plugin.

==== Graphique

Le graphique représente la situation de votre réseau

==== Table

La table contient les informations nécessaire à la representation du réseau plus des informations radios très iteressantes.

Abeille demande toutes les nuits aux équipement de fournir les informations sur les équipements qu'ils ont dans leur entourage radio.
Une ligne represente une relation radio entre l'équipement et un équipement voisin.
Vous allez y trouver le type de relation, le LQI, etc.
Le LQI est le "Link Quality Radio" qui represente la Qualité de la liaison radio entre les deux équipements. 2videment si cette qualité est mauvaise alors les mesages ne peuvent pas être échangés et le réseau ne fonctionne pas. Ou dison sle réseau ne peut utiliser ce lien. Pour des valeur inférieures à 50 (valeur empirique) il faut essayer d'améliorer les choses.


image:Capture_d_ecran_2019_03_15_a_09_32_16.png[]


=== Network List Old

(Ce chapitre est maintenant bien plus integré dans Abeille et de ce fait la doc doit être mise à jour).

Afin de comprendre la situation radio de votre réseau, vous pouvez utiliser ce script AbeilleLQI_Map.php et visualiser les résultats dans un browser web:

http://[adresse de votre Jeedom]/plugins/Abeille/Network/AbeilleLQI_Map.php

Vous pouvez vérifier que l'execution est en cours en monitorant le log AbeilleParser. Vous devriez voir passer des messages comme celui ci (Type 804E):

----
AbeilleParser 2018-04-13 09:43:24[DEBUG]Type: 804E: (Management LQI response)(Decoded but Not Processed); SQN: 11; status: 00; Neighbour Table Entries: 0A; Neighbour Table List Count: 02; Start Index: 00; NWK Address: df33; Extended PAN ID: 28d07615bb019209; IEEE Address: 00158d00019f9199; Depth: 1; Link Quality: 152; Bit map of attributes: 1a
----

Si cela ne fonctionne pas, vous pouvez faire la manip à la main:
----
cd /var/www/html/plugins/Abeille/Network
php AbeilleLQI_Map.php
----


C'est une version brute de fonderie alors il y a plein de bonnes raisons pour que cela ne fonctionne pas et demande votre expertise.


== Tableau

Le script va interroger tous les équipements qu'il détecte, un à un. Le procesus est assez long et pour l'instant la page reste blanche tant que la collect est en cours. Soyez donc patient. Si les résultats sont interessants, je verrai à faire une interface plus conviviale.

Lors de la premiere execution un tableau est généré avec toutes les informations collectées (les exemples ci dessous ne contiennent pas toutes les colonnes car depuis certaines ont été ajoutées.

=== Test

Sur mon systeme de test, après 40s, cela donne:

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

On peut y voir que le routeur d45e est "Sibling" avec le coordinateur (Zigate). Qu'il possède un équipement enfant qui est donc en 2ieme niveau.

=== Production

Sur mon système de prod

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

== Graphique Old

Graphique Vieille Version

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

définie la liste des équipements en mettant leur adresse Zigbee et leur nom.

Dans la deuxieme table vous definissez les positions des équipements et leur couleur:

$Abeilles = array(
'Ruche'    => array('position' => array( 'x'=>700, 'y'=>520), 'color'=>'red',),
// Abeille Prod JeedomZwave
// Terrasse
'T1'       => array('position' => array( 'x'=>300, 'y'=>450), 'color'=>'orange',),
'T2'       => array('position' => array( 'x'=>400, 'y'=>450), 'color'=>'orange',),
'T3'       => array('position' => array( 'x'=>450, 'y'=>350), 'color'=>'orange',),


=== Graphique

Une fois la configuration faite vous devrier avoir le schéma de votre réseau. Par exemple pour moi, j'ai fait une configuration comprenant les équipements de mon réseau de production mais aussi le réseau de test. Capture d'écran des données du réseau de test:

image:Capture_d_ecran_2018_04_30_a_23_45_51.png[width=800]

On peut voir toutes les voisines rapportées par les équipements.

Vous pouvez choisir ce qui est affiché à l'écran:

- premier menu permet de selectionner les équipements qui ont remontés des voisines.
- second menu permet de selectionner les équipements qui ont été mentionné comme étant un voisin d'un autre équipement
- le troisieme menu permet en mode cache d'utiliser les fichier json contenant les informations collectées, le mode refresh permet d'interroger le reseau
- le dernier menu permet de selectionner l information affiché sur les fleches

Par exemple, je veux toutes les relations de voisinages alors dans le premier menu je choisi all.

Par exemple, je veux voir tous les équipements rapportant vori un équipement xxxx, je choisi none dans le premier menu et xxxx dans le second.

Dans la capture ci dessus on peut voir que le noeud Detecteur Smoke est un fils de l'ampoule bois bureau, alors que tous les autres équipements rapportent à la Zigate en direct.

== Graphique New

=== Configuration

Normalement après 24h les informations sont disponibles. Si vous n'avez pas les 24h ou souhaiter rafraichier les données, il faut avoir fait un "Recalcul du cache" (Network List->Table des noeuds->Recalcul du cache).

Juste un clic sur "Network Graph":

image:Capture_d_ecran_2018_10_04_a_02_39_04.png[]

Juste ouvrir le graph et les Abeilles seront disposées sur un grand cercle. Vous pourrez déplacer les Abeilles (clic, deplacement, relache).

image:Capture_d_ecran_2018_10_04_a_02_24_10.png[width=800]



=== Filtre

image:Capture_d_ecran_2018_10_04_a_11_44_30.png[width=800]

Les Abeilles sont toujours representées. Vous pouvez appliquer des filtres sur les voisines.

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

* Je veux voir toutes les Abeilles vues par la ruche (Zigate). Je choisi Ruche dans la source et none dans destination.

* Je veux représenter qui voit la sonnette. Je choisi Sonnette dans la destination et none dans la source.

* Je veux voir toutes les relations Child. Je mets All dans Source et Destination, Child dans Relation.

* Je choisi la valeur affichée le long de la ligne avec le parametre. Le plus utilisé probablement est LinkQualityDec qui represente la qualité de la relation radio dans le sens Source - Destination. Le nombre est entre 0 et 250. Pour des équipments proches d'environ 20cm j'ai des valeurs autour de 180. Au dessus de 220, je me dis que la valeur est farfelue surtout quand elle vaut 255. Tous les équipements ne semblent pas remonter des infos pertinentes. En dessous de 50 la liaison est vraiment pas bonne, il faut probablement faire quelque chose comme ajouter un routeur.

=== Exemples

Exemple avec tout positionné à la main:


image:Capture_d_ecran_2018_10_04_a_02_23_17.png[width=800]

Exemple qu'avec les relations Child (Filter Child):

image:Capture_d_ecran_2018_10_04_a_02_23_37.png[width=800]

On peut voir ici que j'ai 4 End Device sur la ruche(Zigate), 5 sur la priseY,...

Vue interressante car elle permet de voir quels sont le équipements terminaux rattachés à quels routeurs.

Exemple en demandant la Ruche au centre:

image:Capture_d_ecran_2018_10_04_a_02_24_23.png[width=800]

Exemple avec l'upload d'une image en fond d'écran:

image:Capture_d_ecran_2018_10_04_a_11_15_34.png[width=800]

Vous pouvez aussi choisir votre fond d'écran pour positionner vos Abeilles.

== Couverture

Sur la base de la collecte de ces informations, j'ai fait quelques graphes pour comprendre ce qu'on espérer en terme de couverture radio.

Je n'ai pris que des routeurs dans cet exercice: prise xiaomi, prise ikea, ampoule ikea.
Comme tout est mélangé, type de routeur, types de murs (Fenetre, Bois, Pierre,...), Distances définies à vue d'oeil,.. cela permet d'avoir une vue d'un réseau réel.

Le premier graphe est le LQI rapporté par l'équipement en fonction du nombre de mur à traverser.
Le deuxieme graphe est le LQI en fonction de la distance à vol d'oiseau.

image:Capture_d_ecran_2018_12_14_a_10_45_20.png[width=800]

Si l'on considère qu'avec un LQI inférieur à 50 la liaison radio est compliquée (basé sur une expérience partagée mais en rien mesurée) il faut resté dans la mesure du possible au dessus.

Cela nous indique qu'en moyenne plus de 2 murs est très compliqué. Ce qui implique un routeur dans chaque pièce pour être tranquile.

On peut voir des écarts très important dans le LQI alors que les équipements sont dans la meme piece (Colonne 0 des graphes LQI/Wall).

Pour le LQI/m, on peut dire que jusqu'à 10m c'est jouable. Mais on peut trouver les extrèmes aussi. Exemple: la Zigate et une ampoule ikea à 16m pour un LQI de 117 alors que deux ampoules à 5 m on un LQI de 15.

Je suppose qu'en environnement ouvert on peut avoir des distances bien supérieures, avec des distances annoncées par les fabriquants jusqu'a 100m, mais ce type de situation sera des plus rares...

== Link Status

Afin de comprendre la situation radio de votre réseau, vous pouvez utiliser ce script RadioVoisinesMap.php et visualiser les résultats dans un browser web:

http://[Jeedom]/plugins/Abeille/Network/RadioVoisinesMap.php

Ce script va présenter graphiquement les informations échangées entre les routeurs dans les messages "Link Status".

Faites une capture du traffique avec wireshark, puis faites une sauvegarde JSON sous essai.json:

image:Capture_d_ecran_2018_05_10_a_23_33_32.png[width=800]

image:Capture_d_ecran_2018_05_10_a_23_33_48.png[width=800]

Une fois cela fait ouvrez la page: http://[Jeedom]/plugins/Abeille/Network/RadioVoisinesMap.php

Vous devriez avoir un résultat comme:

image:Capture_d_ecran_2018_05_10_a_23_43_31.png[width=800]

Dans le menu déroulent le premier champ permet de filtrer les enregistrement qui ont pour adresse de source la valeur selectionnée. Idem pour le deuxième champ mais pour l'adresse destination. Et enfin le dernier champ permet d'afficher la valeur du champ In ou du champ Out. La valeur In ou Out est la dernière valeur trouvée dans le fichier json lors de son analyse.

Evidement la configuration est celle de mon réseau de prod et de mon réseau de test donc il vous faut déclarer votre propre réseau dans le fichier NetworkDefinition.php.

Dans le tableau knowNE mettre l'adresse courte suiivie du nom de léquipement:

----
$knownNE = array(
"0000" => "Ruche",         // 00:15:8d:00:01:b2:2e:24 00158d0001b22e24 -> Production
// 00:01:58:d0:00:19:1b:22 000158d000191b22 -> Test
// Abeille Prod JeedomZwave
"dc15" => "T1",            // 00:0B:57:ff:fe:49:0D:bf 000B57fffe490Dbf
"1e8c" => "T2",
"174f" => "T3",            // 00:0b:57:ff:fe:49:10:ea
"6766" => "T4",
----

Puis dans le tableau Abeilles, définissez les coordonnées de chaque équipements:

----
$Abeilles = array(
'Ruche'    => array('position' => array( 'x'=>700, 'y'=>520), 'color'=>'red',),
// Abeille Prod JeedomZwave
// Terrasse
'T1'       => array('position' => array( 'x'=>300, 'y'=>450), 'color'=>'orange',),
'T2'       => array('position' => array( 'x'=>400, 'y'=>450), 'color'=>'orange',),
'T3'       => array('position' => array( 'x'=>450, 'y'=>350), 'color'=>'orange',),
'T4'       => array('position' => array( 'x'=>450, 'y'=>250), 'color'=>'orange',),
----


== Routage

Le ZigBee est un réseau Mesh qui permet de "router" les messages d'équipements en équipements pour rejoindre leur destination.

L'organisation du routage suit des règles définies dans la norme ZigBee. Chaque équipement contient dans la stack ZigBee les taches relatives au routage. Tout est automatique et rien n'est accéssible à l'utilisateur final. Une liaison radio en milieu ouvert va faire disons 20m maximum. Et sauf erreur un message est capable d'être routé 30 fois (il faudrait vérifier cette valeur). Ca permet de faire un réseau de 600m de rayon autour du coordinateur.

Sauf que...

Je me suis retrouvé avec des prises outdoor Osram qui refusaient de fonctionner correctement. Apres investigation il s'avere que le routage entre equipement de marque différentes ne se passe pas forcement tres bien. Voici un recap des sceanrii testés et les résultats:

ZiGate - Ampoule Ikea  - Ampoule Ikea: Ok
ZiGate - Ampoule Ikea - Prise Osram: NOK
ZiGate - Prise Xiaomi - Ampoule Ikea: Ok
ZiGate - Prise Xiaomi - Prise Osram: NOK
ZiGate - Prise Osram - Ampoule Ikea: Ok
ZiGate - Prise Osram - Prise Osram: Ok
ZiGate - Module GledOpto Ruban - Ampoule Ikea: Ok
ZiGate - Module GledOpto Ruban - Prise Osram: NOK
ZiGate - Ampoule Osram Couleur - Ampoule Ikea: Ok
ZiGate - Ampoule Osram Couleur - Prise Osram: Ok

Alors pour monter le mesh il faut vérifier que les équipements sont compatibles même si en théorie les routeurs routent, en pratique...



Enjoy
