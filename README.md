= Abeille

(Version master en dev parmanent - Apres 16/02/2018 ).

== Abeille pour Jeedom (Gateway ZiGate)

Permet d'interfacer un réseau ZigBee par l'intermédiaire de la passerelle ZiGate à Jeedom.

Voir https://github.com/KiwiHC16/Abeille/blob/master/Documentation/010_Introduction.asciido

== Pour les developpeurs, j'essaye de comprendre GitHub et je souhaite avoir le fonctionnement suivant:

* branche master : pour tous les developpements en cours a condition que les pushs soient utilisables et "stabilisés" pour la phase de test.
* branche beta: pour figer un developpement et le mettre en test avant de passer en stable
* branche stable: version stable
* Dev en cours: autre branche

== Systeme Testés:

Jeedom fonctionne sur le systeme linux debian, de ce fait ce plugin est développé dans ce cadre. Le focus est fait sur les configurations suivantes:
* raspberry pi 3 (KiwiHC16 en prod)
* Machine virtuelle sous debian 9 en x86 (KiwiHC16 en dev)
* docker debian en x86 (edgd1er en dev)

Les autres envirroenements ne sont pas testés par défaut mais nous vous aiderons dans la mesure du possible.
En retour d'experience sur le forum:
- Windows ne fonctionne pas, car pas Linux
- Ubuntu fonctionne mais demande de mettre les mains dans le cambouit, l'installation même de Jeedom n'est pas immédiate
- Odroid/HardKernel devrait fonctionner
