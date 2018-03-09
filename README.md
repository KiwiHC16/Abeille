= Abeille

(Version master en dev permanent - Après 16/02/2018 ).

== Abeille pour Jeedom (Gateway ZiGate)

Permet de connecter un réseau ZigBee par l'intermédiaire de la passerelle ZiGate à Jeedom.

Voir https://github.com/KiwiHC16/Abeille/blob/master/Documentation/010_Introduction.asciido

== Pour les développeurs, j'essaye de comprendre GitHub et je souhaite avoir le fonctionnement suivant:

* branche master : pour tous les développements en cours a condition que les pushs soient utilisables et "stabilisés" pour la phase de test.
* branche beta: pour figer un développement et le mettre en test avant de passer en stable
* branche stable: version stable
* Dev en cours: autre branche

== Système Testés:

Jeedom fonctionne sur le système linux Debian, de ce fait ce plugin est développé dans ce cadre. Le focus est fait sur les configurations suivantes:
* raspberry pi 3 (KiwiHC16 en prod)
* Machine virtuelle sous debian 9 en x86 (KiwiHC16 en dev)
* docker debian en x86 (edgd1er en dev)
* raspberry Pi2 (edgd1er en prod) 

Les autres environnements ne sont pas testés par défaut mais nous vous aiderons dans la mesure du possible.
En retour d'experience sur le forum:
- Windows ne fonctionne pas, car pas Linux (fichier fifo)
- Ubuntu fonctionne mais demande de mettre les mains dans le cambouis, l'installation même de Jeedom n'est pas immédiate (https://github.com/KiwiHC16/Abeille/blob/master/Documentation/024_Installation_VM_Ubuntu.adoc @KiwiHC16)
- Odroid/HardKernel devrait fonctionner
-- U3 sous debian: install classique (@KiwiHC16)
-- XU4 sous ubuntu: https://github.com/KiwiHC16/Abeille/blob/master/Documentation/026_Installation_Odroid_XU4_Ubuntu.adoc (@KiwiHC16)
