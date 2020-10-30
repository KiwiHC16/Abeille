Système
=======

Jeedom fonctionne sur le systeme linux debian, de ce fait ce plugin est développé dans ce cadre.

Le focus est fait sur les configurations suivantes:

- raspberry pi 3 (KiwiHC16 en prod)
- Machine virtuelle sous debian 9 en x86 (KiwiHC16 en dev)
- docker debian en x86 (edgd1er en dev)
- raspberry Pi2 (edgd1er en prod)

== Les autres envirronements

Les autres environnements ne sont pas testés par défaut mais nous vous aiderons dans la mesure du possible.

En retour d'experience sur le forum:

- Windows ne fonctionne pas, car pas Linux (fichier fifo)
- link:Docker.html#Ubuntu[Ubuntu fonctionne mais demande de mettre les mains dans le cambouis], l'installation même de Jeedom n'est pas immédiate.
- Odroid/HardKernel devrait fonctionner
- U3 sous debian: install classique
- link:Docker.html#XU4[XU4 sous ubuntu]

== Equipements

La liste des équipement supportés est consolidée dans link:https://github.com/KiwiHC16/Abeille/blob/master/Documentation/040_Compatibilite.adoc[la page GitHub].

La liste très détaillée des équipements testés est consolidé dans le link:https://github.com/KiwiHC16/Abeille/blob/master/resources/AbeilleDeamon/documentsDeDev/AbeilleEquipmentFunctionSupported.xlsx?raw=true[fichier excel]

[NOTE]
Le contenu du fichier excel est souvent en retard par rapport à la réalité. Il est surtout interessant pour le dev.
