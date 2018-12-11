# Présentation Abeille


(Portage en cours vers la doc jeedom, premiere version pour test. la doc est toujours à https://github.com/KiwiHC16/Abeille)


*Le plugin Abeille*  permet de mettre en place un réseau ZigBee avec des produits du marché et des réalisations personnelles (DIY) par l'intermédaire de la passerelle Zigate.

"ZiGate est une passerelle universelle compatible avec énormément de matériels radios ZigBee. Grâce à elle, vous offrez à votre domotique un large panel de possibilités. La ZiGate est modulable , performante et ouverte pour qu'elle puisse évoluer selon vos besoins.
"
Dixit son créateur.

Ce plugin est né de besoins personnels : capteur de température radio distant avec un réseau sécurisé, mesh,… 

Finalement, il intègre de plus en plus d’équipements :
[Compatibilité](https://github.com/KiwiHC16/Abeille/blob/master/Documentation/040_Compatibilite.adoc)

Mon réseau personnel fonctionne depuis plusieurs mois et possède actuellement 45 équipements et continue de grossir.

Ce plugin inclus les fonctions de base pour la gestions de équipements comme On/Off/Toggle/Up/Down/Detection/… mais aussi des fonctions avancées pour faciliter la gestion d’un gros réseau :
* Retour d'état des équipements,    
* Santé (Dernière communication,…)
* Niveau des batteries 
* Graphe du réseau
* Liste de tous les équipements du réseau
* Informations radio sur les liaisons entre les équipements
* En mode USB ou en mode Wifi
* Fonctionne avec Homebridge
-    …

J’ai aussi intégré un « sous-plugin » TIMER qui fonctionne à la seconde dans ce plugin. Il faudra peut être que je fasse un plugin dédié et indépendant.

Je passe beaucoup de temps a formaliser les sujets dans une documentation que vous trouverez à : 
https://github.com/KiwiHC16/Abeille/tree/master/Documentation

Pour ceux qui utiliseront ce plugin, je vous souhaite une bonne expérience. Pour ceux qui auraient des soucis, vous pouvez aller sur le forum ou ouvrir une « issue » dans github (Je ferai de mon mieux pour vous aider).

Essai d une table:

Col1 | Col2
-----|-----
A | B
Images | ![Alt texte](../images/Capture_d_ecran_2018_01_21_a_11_07_27.png)

# Installation

## Installation et activation du plugin Homebridge

### Installation des dépendances


### Fichiers LOG


Les fichiers log permettent d'analyser pas à pas l'activité interne du processus et ses interactions avec son environnement.

Ces fichiers peuvent être nécessaires en cas de dysfonctionnement du plugin.
Abeille:
AbeilleParser:
AbeilleMQTTCmd
AbeilleMQTTCmdTimer
AbeilleSerialRead
AbeilleSocat
Abeille_removal
Abeille_updateConfig


## Configuration du plugin Homebridge


# Troubleshooting

# Support
-------
Merci de passer par le forum, ...

