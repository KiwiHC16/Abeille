# Changelog Abeille

- JSON: Correction setReportTemp (#1918).
- Innr RB285C: correction modele corrompu.
- Innr RB165: modele préliminaire.
- Tuya GU10 ZB-CL01: ajout support.
- Hue motion sensor: mise-à-jour JSON.
- Interne: correction message 8120.
- Page config: correction installation WiringPi (#1979).
- Introduction de "core/config/devices_local" pour les EQ non supportés par Abeille mais locaux/en cours de dev.
- Zemismart ZW-EC-01 curtain switch: ajout du modèle JSON

# 210510-STABLE-1
- Page compatibilité: revisitée + ajout du tri par colonne
- Page santé: ajout de l'état des zigate au top
- Sonoff SNZB-02: support corrigé + support 66666 (ModelIdentifier) (#1911)
- Xiaomi GZCGQ01LM: ajout support tension batterie + online (#1166)
- Page EQ/params: ajout de l'identifiant zigbee
- Correction "#1908: AbeilleCmd: Unknown command"
- Correction "#1951: pb affichage heure "Derniere comm."
- Correction blocage du parser dans certains cas de démarrage.
- Diverses modifications pour améliorer la robustesse et les messages d'erreurs.
- Monitor (pour developpeur seulement pour l'instant)
- Gestion des démons: revisitée pour éviter redémarrages concurrents.
- Correction "#1948: BSO, lift & tilt"
- JSON: Emplacement des commandes changé de "core/config/devices/Template" vers "core/config/commands"
- Innr RB285C support preliminaire

# 11/12/2020

- Prise Xiaomi: fonctions de base dans le master (On/Off/Retour etat). En cours retour de W, Conso, V, A et T°.
- LQI collect revisited & enhanced #1526

# 10/12/2020

- Ajout du modale Template pour afficher des differences entre les Abeilles dans Jeedom et leur Modele.

# 09/12/2020

- Ajout d un chapitre Update dans la page de configuration pour verifier que certaines contraintes sont satisfaites avant de faire la mise a jour.

# 08/12/2020

- Ajout Télécommande 3 boutons Loratap #1406

# 05/12/2020

- Contactor 20AX Legrand : Pilotage sur ses fonctions ON/OFF et autre

# 04/12/2020

- Prise Blitzwolf

# 03/12/2020

- Detecteur de fumée HEIMAN HS1SA-E

# 02/12/2020

- TRADFRIDriver30W IKEA
