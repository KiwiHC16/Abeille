# ChangeLog

-   Amélioration: Support préliminaire Sonoff SNZB-06P capteur de présence (2726).
-   Corrections: Interne parser.
-   Amélioration: Modèle Sonoff TH01 revu pour réduire fréquence reporting.
-   Sirene iAlarm: Mises-à-jour du modèle (2629).
-   Tuya detecteur fumée Tuya TS0205: Mise-à-jour du modèle (2658).

## 240823-BETA-1

-   Correction: Collecte LQI erreur ' msgToCmd() ERROR 22 in AbeilleLQI'.

## 240821-BETA-1

-   Amélioration: Xiaomi smoke sensor: Ajout 'Battery low' (2723).
-   Amélioration: Maintenance/infos clefs
-   Correction: Interne cmd 'setLevel': 'Level' => 'level'
-   Correction: Virtual remoten cmd on/off group (2724).
-   Correction: Xiaomi smoke sensor: Nettoyage modèle (2723).
-   Correction: Xiaomi gaz sensor: Nettoyage modèle.
-   Correction: Interne cmd 'setLevel': 'EP' => 'ep'

## 240819-BETA-1

-   Interne: Modifications pour future fusion cmd/parser:
    -   Parser: $GLOBALS['eqList'] => $GLOBALS['devices']
    -   Parser: ieee/macCapa/rxOnWhenIdle/endPoints déplacé dans 'eq[zigbee]'.
    -   Cmd: ieee/rxOnWhenIdle/txStatus déplacé dans 'eq[zigbee]'.
    -   Cmd: jsonId remplacé par 'eq[eqModel][modelName]'.
    -   Cmd: jsonLocation remplacé par 'eq[eqModel][modelSource]'.
    -   Cmd: modelForced/modelPath déplacé dans 'eq[eqModel]'.
-   Amélioration: Infos clefs: Ajout 'last LQI'.
-   Correction: Parser: Erreur interne.
-   Amélioration: Livolo TI0001: Amélioration modèle.
-   Amélioration: Xiaomi sensor smoke. Modèle revu (2723).
-   Amélioration: Cmd: Support valeur préfixée par '0x'.
-   Amélioration: Cmd: Ajout support 'repeat' pour repeter une action jusqu'a acquittement (Attention !! Limiter son usage).
-   Améliorations: Interne cmd.
-   Améliorations: Interne main daemon: Config sauvé en global.
-   Amélioration: Interne parser: Suppression warning si queue 'main' pleine.
-   Amélioration: Redémarrage de tous les démons si une des queues est saturée (>50 messages).
-   Amélioration: Interne cmd: setCertificationCE/FCC => 'zgSetCertification'.
-   Correction: Interrogation 'nwk_addr_req'.
-   Amélioration: Ajout type de requete pour 'nwk_addr_req'.
-   Amélioration: Interne: Clefs queues prefixées par '0xAB'.
-   Amélioration: Interne moniteur: 1 seule queue de lecture.
-   Correction: Cmde Zigate 'Set inclusion mode' de retour pour cas spécific Livolo TI0001 (2609).
-   Correction: Mauvaise permission pour queue 'xToAbeille'.

## 240808-BETA-1

-   Amélioration: Page santé. Satus des gateways raffraichi toutes les secondes.
-   Corrections: Modele Xiaomi detecteur fumée (2723).
-   Module volet roulant TS130F: Modèle en cours de revue (2719).
-   Amélioration: Plus besoin de redémarrer démons apres changement niveau de log.
-   Amélioration: Maintenance/infos clefs.
-   Correction: Cmd: Status TX corrigé en 'ok' meme si 'rx OFF when idle'.
-   Amélioration: Zigate: Tentative d'ajout possibilité de changer 'Extended PAN ID' pour cas Livolo. Ne fonctionne pas !
-   Amélioration: Cmd->Zigate: Cas Zigate 'busy'.
-   Amélioration: Cmd/config Zigate.
-   Correction: Parser: Suppression du message 'Requesting simple descriptor for EP '.

## 240802-BETA-1

-   Correction: 'Commande 0102-01- inconnue' (2719).
-   Sonoff SNZB-01P: Mise-à-jour du modèle (2716).
-   Correction collecte LQI: Code C1 n'est plus un timeout mais 'aucun eq en vie'.
-   Corrections Zigate/avancé:
    -   Affichage version FW Zigate (cmde 'FW-Version' manquante).
    -   Affichage canal Zigate (cmde 'Network-Channel' manquante).
    -   Affichage PAN-ID & Ext PAN-ID.
    -   Affichage status réseau.
    -   Affichage TX power.
-   Amélioration infos clefs: Affichage du mode de log courant.

## 240710-BETA-2

-   Amélioration: OWON THS-317-ET: Lecture température avant premier reporting (2706).
-   Profalux MAI-ZTP20F/C: Ajout modèle (2717).
-   Sonoff SNZB-01P: Support préliminaire (2716).
-   Ikea TRETAKTSmartplug: Support préliminaire (2718).

## 240704-BETA-1

-   Améliorations analyse réseau.
-   Améliorations placement réseau
    -   La télécommande virtuelle n'est pas affichée.
    -   Affichage des équipements pas trouvés dans le réseau sans tête de mort jusqu'a mieux.
-   Correction: Format padding base64 utilisé par télécommande universelle.

## 240630-BETA-2

-   Améliorations: Analyse réseau. Toujours pour contourner mauvaises infos remontées de certains routers.
-   Corrections: Identification: Pas d'interrogation modelId/manufId si pas de cluster 0000.
-   Améliorations: Analyse réseau: Indique si le routeur ne répond pas. Affichage par une tête de mort.
-   Améliorations: Page EQ/avancé: Support extended pour 'getIeeeAddress'.
-   Interne: Support 'Mgmt_ieee_req/rsp' pour type 'extended'.
-   Améliorations graphique des liens: Affiche les équipements connus d'Abeille mais pas dans le réseau Zigbee (ou sans vie).
-   Améliorations placement réseau
    -   Alignement des couleurs avec 'Graphique des liens'.
    -   Ajout légende.
    -   Ajout des équipements connus de Jeedom mais plus visibles sur le réseau (sans vie).
    -   Ajout tête de mort sur équipement sans vie.

## 240624-BETA-1

-   Corrections: Analyse réseau.
-   Corrections: Modèles d'équipement: 'Time-Time' est de retour mais 'Time-TimeStamp' définitivement supprimé.
-   Corrections: Status interne Zigate n'est pas 'rxOnWhenIdle'.
-   Améliorations: Processus de réparation.

## 240622-BETA-2

-   Analyse réseau: Corrections.
-   Interne: Correction sauvegarde config dev.
-   Mises-à-jour OTA: Amélioration interne.
-   Interne: Cmd: Suppression support obsolete 'identifySendHue'.
-   Modèle d'équipement: Surcharge possible de 'request'.
-   Interne: Cmd: execute() revu pour + de clareté.
-   NodOn SIN-4-RS-20: Inversion du 'level' (2709).
-   'Time-TimeStamp': Caché à la création.
-   Package de logs: Correction: Créé même si aucun 'json'.

## 240618-BETA-5

-   Modèles d'équipement: Réajout info 'Time-TimeStamp' si manquante.
-   Onglet EQ/avancé: Qq mises-à-jour.
-   Amélioration analyse réseau (Mgmt_lqi_req).

## 240618-BETA-2

-   GL-C-007P, GLEDOPTO: Ajout controle blanc chaud/froid (2710).
-   Assistant modèles:
    -   Amélioration cluster 0008.
    -   Amélioration nommage 'level' ou 'brightness' en fonction du type 'device ID'
-   Modèles d'équipement
    -   Suppression des commandes 'SWBuildID' & 'Get-SWBuildID' (dispo sur onglet 'avancé').
    -   Ajout support variable '#valueoffset#'
-   Modèles TRAFRIbulbxxx: Mises-à-jour.
-   Assistant de découverte: Ajout 'profile ID' + 'device ID'.
-   Réparation: Améliorations pour support 'profile ID' + 'device ID'.
-   Interne: 'AbeilleLQI-AbeilleX.json.lock' => 'AbeilleLQI.lock'
-   Interne: Zigbee const: Ajout 'device ID' pour lighting.
-   Interne: tools: Amélioration sync_all.sh
-   Interne: Zigbee consts: Mises-à-jour cluster 0102.
-   Interne: createDevice() revu.
-   Interne: Cmd 'info' n'est plus mise à jour systématiquement par valeur cmd 'action'.
-   Support EmberZnet/EZSP: Qq avancées mineures.
-   Onglet avancé: Ajout 'Type logique' & 'MAC capabilities'.
-   Réparation: Améliorations.
-   Carte réseau: Utilisation du 'logical type' du node descriptor au lieu de la réponse 'LQI mgmt rsp' souvent fausse.
-   Modèles d'équipement: Réajout info 'Time-TimeStamp' + suppression 'Time-Time'.

## 240610-BETA-1

-   Support EmberZnet/EZSP: Encore des tas de modifs
-   GL-D-002P, GLEDOPTO: Ajour support preliminaire (2708).
-   Assistant modèle: Améliorations mineures.
-   NodOn SIN-4-RS-20: Support préliminaire (2709).
-   Interne: Corrections pour changement d'un modèle local à officiel.
-   NodOn SIN-4-FP-21: Support préliminaire.
-   Interne: Améliorations 'check_json'.
-   Modèle d'équipement: Amélioration 'valueOffset': Ajout support '#valueswitch-XXXX#'
-   Assistant modèle: Ajout du type générique.
-   Modèles d'équipement: Suppression info 'Time-TimeStamp'.
-   Interne: Zigate cmd: Correction 'cmd-0102' pour 'set tilt percent'.
-   Interne: Parser: Correction mineure pour fin OTA.
-   Interne: Parser: Lecture attr 0006/DateCode & 4000/SWBuildId cluster 0000 si manquants.
-   Equipement/avancé: Ajout support cmd-0102/WindowCovering.
-   GL-C-007P, GLEDOPTO: Modèle revu.
-   Support: Fichiers JSON réseau ajoutés au package de logs.
-   Placement réseau: Améliorations & corrections analyse réseau.
-   Interne: Cmd: Taille queue 'xToCmd' augmentée à 1024.
-   Support Moes UFO-R11: Correction regression.

## 240519-BETA-1

-   Interne: Suppression fichiers obsoletes.
-   Zigates: Fonctionnement en mode 'brut' à partir de maintenant.
-   Interne: AbeilleTools::getParameters() => getConfig().
-   Support EmberZnet: C'est parti mais rien d'utile à ce stade.
-   DB config: évolutions:

    -   Ajout 'ab::gtwType' (zigate ou ezsp)
    -   'ab::zgEnabledX' => 'ab::gtwEnabledX'
    -   'ab::zgTypeX' => 'ab::gtwSubTypeX'
    -   'ab::zgPortX' => 'ab::gtwPortX'
    -   'ab::zgIpAddrX' => 'ab::gtwIpAddrX'

-   Page de config

    -   Ajout support prélim clef 'EZSP'.
    -   Améliorations support Zigate v2.

-   Onglet avancé Zigate

    -   Mode 'normal' désactivé. Mode 'brut' par défaut.
    -   Amélioration aspect visuel.

-   Support multi-passerelles: Des tas de modifs internes.
-   Interne: Cmd: Mise-à-jour 'getBindingTable()' (suppression champ 'address')
-   Dépendances nécessaires: Ajout 'pyserial'.
-   Interne: Cmd: 'TxPower' => 'zgSetTxPower'
-   Zigate/avancé: Ajout controle puissance TX.
-   Interne: Corrections: Cmd averti lors de toute mise-à-jour d'un équipement.

## 240501-STABLE-1

**Mise-à-jour de modèles**

    - Malgré la volonté de faire des évolutions les plus transparentes possibles, il se peut que certains équipements nécessitent d'être mis-à-jour à partir de leur dernier modèle pour à nouveau fonctionner correctement.

      - Si ils sont sur batterie, réinclusion nécessaire.
      - Si sur secteur, aller à la page 'avancé' et bouton 'mise-à-jour'.

**Zigates v2/+**

    - Malheureusement ce modèle montre de grosses instabilités et ne doit pas être considéré pour une solution robuste.
    - La maturité de la v2 n'est pas au niveau de la v1 et malgré ça, il n'y a pas eu de mises-à-jour depuis un moment. D'après differents retours le dernier FW dispo (3.A0) n'est pas le + stable mais le précédent (**3.22**). Nous vous conseillons de faire la mise-à-jour vers celui ci.
    - FW **v3.22 FORTEMENT RECOMMANDE** avec un **EFFACEMENT COMPLET** lors de la mise-à-jour.

**Zigates v1**

    - FW **v3.23 OPDM** recommandé. La version minimale est la '3.1E' mais ne sera bientot plus supportée.
    - Le dernier FW officiel est le v3.23. Il est recommandé de basculer dessus pour ne pas faire façe à des soucis déja corrigés.
    - D'autre part si vous n'êtes pas en version OPDM (Optimized PDM), il est fortement recommandé de basculer dessus dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

-   Interne: Parser: Suppression support message 8040 pour compatibilité mode raw.
-   Interne: Zigbee const: Ajout zbGetZDPStatus().
-   ZLinky: Mise-à-jour du modèle (2704).
-   Interne: Version DB config gelée => '20240430'.
-   Page maintenance: Suppression du fichier zippé après transfert DESACTIVE. A revoir.

## 240425-BETA-1

-   Page config: Corrections test de port.

## 240423-BETA-7

-   Moes ZM-105-M: Support préliminaire (2697).
-   Interne: Amélioration msg dbg OTA.
-   Interne: powerCycleUsb: Améliorations mineures.
-   Changelog: Migré au format markdown.
-   sensor_switch.aq3: Mise-à-jour du modèle avec ajout section 'private'.
-   Interne: Parser: Désactivation du support messages 8100 & 8102 (géré par 8002) pour migration mode 'raw'.
-   Page de config: Les démons sont automatiquement redémarrés lors de la sauvegarde de la configuration.
-   Page de gestion: 'Remplacement d'équipement' => 'Transfert d'historique'.
-   Page maintenance: Améliorations package de logs & suppression du fichier zippé apres transfert.
-   Interne: Correction 'update_changelog.sh'
-   Page de config: Correction test de port pour USBv2.

## 240409-BETA-1

-   Groupes: Améliorations ajout/suppression de groupe.
-   Page santé:

    -   Travaille en cours pour amélioration avec thème 'dark'.
    -   Ajout ID Jeedom.
    -   Corrections internes.

## 240403-BETA-2

-   Interne: Parser: Correction regression support Xiaomi.
-   Page santé: Amélioration robustesse.
-   Affichage des groupes: Equipement barré et pas d'action possible si désactivé.

## 240329-BETA-2

-   Interne: Support Tuya: Amélioration msg debug.
-   Tuya compteur d'énergie: Mise-à-jour du modèle (2691).
-   Philips LWA021_SignifyNetherlandsBV: Ajout support préliminaire (2693).
-   Interne: Abeille.js: Corrections erreur lors du changement de nom.
-   Ampoule E27 TS0505B\_\_TZ3210_mja6r5ix: Ajout support (2694).
-   Interne: Parser: Correction mineure.
-   Réparation d'équipement: Améliorations.
-   Equipement/avancé/interrogations: Améliorations cluster 1000.
-   Interne: Cmd: Ajout support vers client pour 'discoverCommandsReceived'.
-   Equipement/avancé/interrogations: Découverte des commandes reçues vers client possible.
-   powerCycleUsb: Améliorations du script de reboot USB.
-   Correction status équipement 'Zigate' si désactivé sur page de config.
-   Page santé: Correction: Equipements montrés désactivés si Zigate désactivée.
-   Dev: Améliorations 'switch_branch'.
-   Page santé: Une couleur par réseau pour plus de clareté.

## 240319-BETA-3

-   SonOff ZbminiL2: Modèle revu.
-   Profalux 2nd gen: Mise-à-jour mineure du modèle.
-   Syntaxe modèle d'équipement: Ajout section 'variables' pour support '#groupEPx#'.
-   Interne: Parser: Améliorations mineures.
-   Interne: 'check_json': Améliorations.
-   Interne: eqLogic/configuration: groupEPx => eqModel['variables'].
-   Page EQ/avancé: Ajout d'une section 'variables'. Suppression cas 'telecommande7groups'.
-   Tuya compteur d'énergie: Modèle préliminaire (2691).
-   Page EQuipement: 'variables' migrées sur onglet principal.
-   Interne: Configuration sur 'device announce' déplacée de Parser vers Cmd.
-   Réseau: Affichage table des liens Zigate 1 à l'ouverture.
-   SonOff ZbminiL2: Correction 'mac capa' incorrect => 'RxOnWhenIdle'.
-   Interne: Mises-à-jour 'check_json' + 'update_json' pour changement 'tuyaEF00/fromDevice' => 'private/EF00/type=tuya'.
-   Modèles d'équipement: Suppression 'tuyaEF00/fromDevice' => remplacé par 'private/EF00/type=tuya'.
-   Modèles d'équipement: Suppression 'fromDevice/ED00' => remplacé par 'private/ED00/type=tuya-zosung'.
-   Collecte LQI: Traitement messages 'Management LQI response' revu pour clareté.

## 240308-BETA-1

-   Script: Améliorations 'checkZigate'
-   Placement réseau: Améliorations support multi-niveaux.
-   Profalux BSO: Mise-à-jour du modèle pour retour level (2689).
-   SonOff SNZB-01: Modèle revu: 'Click-Middle' remplacé par 'Click' (valeur 'single', 'double' ou 'long').

## 240223-BETA-2

-   Ikea RODRET dimmer: Mise-à-jour du modèle par défaut pour ON/OFF (2684).
-   Mise-à-jour FW PiZiGate: Amélioration scripts & support préliminaire PIv2 (2638).
-   Dump Zigate: Support préliminaire.
-   Scripts: Utilisation de 'python3' et non pas 'python'.
-   Scripts/checkTTY: Arret forcé du processus si port utilisé.
-   Scripts: updateFirmware => updateZigate.
-   Scripts: Nettoyage. Suppression 'installWiringPi' + 'installPiGpio' => 'installPackage'
-   Page de config: Correction visuelle mineure.
-   Placement réseau: changements en cours.
-   Scripts: Correction droits 'installPackage.sh'
-   Interne: Abeille: setPIGpio().
-   Support PiGpio: Corrections.

## 240216-BETA-1

-   Modèle forcé: Correction.
-   Réparation d'équipement: Améliorations.
-   Interne: Zigbee const: Ajout FC20 + FC21 (Profalux).
-   Interne: Cmd: Amélioration 'configureReporting2'.
-   Profalux BSO: Mise-à-jour du modèle (2687).
-   Interne: Parser: Corrections pour support modèle avec variante (<modelName>[-variantX]).
-   Interne: AbeilleModels: Amélioration pour robustesse.
-   Affichage commandes: min/max affiché si slider. 'Inverser' affiché si info binaire.

## 240213-BETA-1

-   Interne: Cmd: Suppression 'Network_Address_request' => 'getNwkAddress'.
-   Page équipment/avancé: Ajout support 'Network_Address_request'.
-   Interne: Cmd: 'cmd-Private' revisité.
-   Modèle forcé: Corrections.
-   Profalux: Cmd: Corrections.
-   Réparation d'équipement: Améliorations.
-   Interne: Corrections pour meilleur support des modèles avec variante.
-   Interne: eqLogic: 'ab::signature' supprimé => 'ab::zigbee'.

## 240209-BETA-1

-   Modèles: Ajout fabricant & type generic sur certains modèles Philips.
-   Ikea RODRET dimmer: Ajout modèle variante 'direct' (2684).
-   Interne: Parser+Cmd: Messages en attente supprimés si équipement change de réseau.
-   Page config: Bouton 'libérer' visible en mode dev seulement. Pas robuste pour utilisateur final.
-   Outils: Script de test en ligne de cmd préliminaire (resources/scripts/checkZigate.sh)
-   Profalux BSO tilt: Correction regression (2687).
-   Interne: Cmd: Meilleur support '#cmdInfo_xxx'.
-   Interne: Cmd: Ajout 'AbeilleCmd-Profalux.php' pour nettoyage modeles.

## 240201-BETA-1

-   Interne: Amélioration latence. Si equipement sans réponse (NO-ACK), passe en basse priorité.
-   Interne: Qq corrections + ajout support 'readAttribute2()' pour test.
-   Ikea RODRET dimmer: Mise-à-jour du modèle (2684).
-   Ikea capteur qualité de l'air VINDSTYRKA: Mise-à-jour du modèle (2681).
-   Interne: Cmd ignorée si déja 'pending' qq soit la priorité.
-   Interne: Amélioration 'check_json'.

## 240124-BETA-1

-   Ikea capteur qualité de l'air VINDSTYRKA: Mise-à-jour du modèle (2681).
-   Interne: Cmd: Amélioration formatAttribute() pour type x39/single precision + int8/int24/int32.
-   Placement réseau: En cours de revue.
-   Interne: Zigbee: Ajout support clusters 040C/CO, 040D/CO2, 042A/PM2.5
-   Page config: Firmwares renommés.
-   Page config: Corrections test de port + mise-à-jour FW.
-   Page config: Correction canal Zigbee (? = auto) + correction erreur changement.
-   Assitant modèle: Ajout support clusters 040C/CO, 040D/CO2 & 042A/PM2.5
-   Ikea RODRET dimmer: Suppport préliminaire (2684).
-   Interne: Cmd: getSimpleDescriptor = commande avec ACK.
-   Interne: Parser: Correction mauvais EP enregistré lors d'une réponse 'simple descriptor'.
-   Remplacement d'équipement: Corrections (2683).

## 240116-BETA-1

-   Interne: Cmd: clearPending().
-   Zlinky: Mise-à-jour du modèle (2678).
-   Placement réseau: Corrections.
-   Interne: Suppression fichiers obsoletes => archives.
-   Capteur lumière AQARA GZCGQ11LM: Ajout support (2679).
-   Ikea Capteur qualité de l'air: Support préliminaire (2681).
-   Assistant modèles: Correction cluster 0008.
-   Ikea Trafri E27: Support préliminaire (2680).
-   Lidl detecteur de mouvement: Correction modèle (2674).
-   Page réseau: Revisitée.

## 240107-BETA-1

-   Réseau: Rework en cours 'graph des liens'.
-   Interne: Corrections suite debug 2675 (Modèle 'rucheCommand' inconnu).
-   Interne: Parser: Correction 2678 ('getSimpleDescriptor': Paramètre 'ep' vide !).
-   ZG-003-RF: Support préliminaire (2408).
-   Zlinky: Mise-à-jour du modèle (2678).
-   Interne: Cmd: Suppression des messages pending suite changement adresse.

## 240104-BETA-1

-   Interne: Cmd: Regulation revue.
-   Profalux BSO: Modèle revu. Suppression 'lift' (idem 'Set Level').
-   Interne: Cmd: Plus que 3 niveaux de priorités.
-   Owon-THS317-ET: Ajout d'un modèle pour les vieux équipements (EP=03 au lieu de 01, voir 2319).
-   Interne: Nouvelle lib 'AbeilleModels.php'.
-   Page compatibilité: Mise-à-jour.
-   Interne: Abeille.class: Utilisation lib 'AbeilleModels.php'.
-   Interne: AbeilleTools/getDevicesList() remplacé par AbeilleModels/getModelsList()

## 231231-STABLE-1

Abeille continue son petit chemin, même si exclusivement dédié aux Zigates aujourd'hui. Toujours dans l'idée de rendre son utilisation plus robuste et simple.

.. warning:: **Mise-à-jour de modèles**

    - Malgré la volonté de faire des évolutions les plus transparentes possibles, il se peut que certains équipements nécessitent d'être mis-à-jour à partir de leur dernier modèle pour à nouveau fonctionner correctement. Si ils sont sur batterie, réinclusion nécessaire. Si sur secteur, aller à la page 'avancé' et bouton 'mise-à-jour'.

.. warning:: **Zigates v2/+**

    - FW **v3.22** recommandé.
    - La maturité de la v2 n'est pas au niveau de la v1. Le FW qui semble le plus stable n'est PAS le dernier dispo (3.A0) mais le précédent (**3.22**). Nous vous conseillons de faire la mise-à-jour vers celui ci.

.. warning:: **Zigates v1**

    - FW **v3.23 OPDM** recommandé. La version minimale est la '3.1E' mais ne sera bientot plus supportée.
    - Le dernier FW officiel est le v3.23. Il est recommandé de basculer dessus pour ne pas faire façe à des soucis déja corrigés.
    - D'autre part si vous n'êtes pas en version OPDM (Optimized PDM), il est fortement recommandé de basculer dessus dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

## 231209-BETA-2

-   Interne: Correction NPDU regulation.

## 231208-BETA-1

-   Interne: Mise-à-jour powerCycleUsb pour récuperer sortie 'dmesg' si erreur.
-   Interne: AbeilleCmd: Pas de ACK sur requete LQI vers Zigate.
-   Placement réseau: Utilisation image par défaut si plan n'existe plus.
-   Interne: AbeilleCmd: Correction perte de commandes si trop dans la queue à la fois.
-   Interne: AbeilleCmd: Amélioration 'throughput regulation'.

## 231207-BETA-2

-   Modèles:

    -   Tous les modèles utilisent 'act_zbConfigureReporting2'.
    -   Correction de certains mauvais type d'attribut pour configureReporting2.
    -   Améliorations outil de check.

-   Page santé: Réactivation raffraichissement toutes les 2 sec.
-   Interne: Configuration des Zigates au démarrage revue. Déplacée dans AbeilleCmd.
-   Rappel: Le mode 'normal' de la Zigate n'est plus supporté donc FW >= 3.1E requis.
-   Timeout: Correction pouvant expliquer le passage en timeout de certains équipements.
-   Interne: Autre corrections du parser.
-   Support Xiaomi: Améliorations internes.

## 231205-BETA-6

-   Interne: Cmd: Réactivation régulation NPDU pour éviter erreurs x85.
-   Assistant modèle: Correction.
-   Tuya detecteur de présence ZG-205Z: Support préliminaire.
-   Page EQ/avancé: correction suppression modèle local.
-   Modèles de commandes: Normalisation noms.
-   Correction regression Tuya: 'Call to undefined function tuyaGenSqn()'
-   Tuya IH-K009: Mise-à-jour modèle 'TS0201\_\_TZ3000_dowj6gyi'.
-   Page santé: Amélioration: Equipements désactivités barrés.
-   Support de clusters privés:

    -   Syntaxe des modèles revue (mot clef 'private').
    -   Support limité à Xiaomi seulement.
    -   Tous les modèles Xiaomi mis-à-jour.

-   Xiaomi Aqara smart plug EU: Mise-à-jour du modèle (2665).
-   Tuya/Moes universal remote: Séparation des modèles pour versions batterie et USB.
-   Page maintenance/infos clefs: Amélioration affichage.
-   Modèles

    -   Plusieurs mis-à-jour pour utiliser 'configureReporting2' au lieu de 'configureReporting'.
    -   Plusieurs corrigés pour reporting cluster 0008. Mauvais type d'attribut.
    -   Plusieurs corrigés pour reporting cluster 0102. Mauvais type d'attribut ou mauvais attribut.

-   Multiprise Lidl HG6338-FR: Mise-à-jour du modèle.

## 231202-BETA-3

-   Page EQ/avancé: Traductions US.
-   Modèles: Nettoyage 'poll=0'
-   Modèles: Amélioration 'trigOut' pour support multiples actions.
-   Attribut 0000/LocalTemp du cluster 0201 divisé par 100 par défaut.
-   Interne: Cmd: Correction 'writeAttribute0530()'
-   Interne: Cmd: Corrections 'writeAttribute()'
-   Attribut 0012/OccupiedHeatingSetpoint du cluster 0201 divisé par 100 par défaut.
-   Danfoss eTRV010x: Modèle en cours de changements (2662).
-   Page EQ/avancé: Améliorations écriture attribut.
-   Danfoss eTRV010x: Qq commandes cachées par défaut car sans interet (2662).
-   Interne: Améliorations constantes Zigbee cluster 0201.

## 231130-BETA-2

-   Interne: Parser: decode8002, monitoring revu => parserLog2().
-   Danfoss eTRV010x: Modèle en cours de changements (2662).
-   Suppression de modèles de commandes obsoletes:

    -   spiritBatterie-Pourcent
    -   spiritTemperature
    -   spiritUnknown1
    -   spiritUnknown2

-   Interne: Cmd: execute() revue préliminaire.
-   Modele 'TS0601\_\_TZE200_e3oitdyu' (Moes MS105B): Ajout de signatures alternatives (2473).
-   Modele 'TS0601\_\_TZE200_e3oitdyu' (Moes MS105B): Mise-à-jour du modèle pour canal 2 (2473).
-   Support Tuya, commandes action:

    -   Ajout 'setEnum'
    -   Ajout support optionnel 'mult' & 'div' pour 'setValue'.
    -   'setValueMult' & 'setValueDiv' sont obsoletes => 'setValue' + mult/div
    -   Modeles obsoletes: act_tuyaEF00-Set-Setpoint, act_tuyaEF00-Set-Setpoint-Mult, act_tuyaEF00-SetThermostatMode

-   Page EQ/avancé: Ajout support 'manufCode' sur commande générique.

## 231128-BETA-1

-   Interne: Cmd: 'configureReporting2' completement revu.
-   Ikea Tradfri E27 LED2102G3: Correction image (2626).
-   Icones: Qq images renommées

    -   'eTRV0100' => 'Danfoss-Ally-Thermostat'
    -   'IkeaTradfriBulbE14WSOpal400lm' => 'Ikea-BulbE14-Globe'
    -   'ProfaluxLigthModule' => 'Profalux-LigthModule'
    -   'Ikea-BulbE14CandleWhite' => 'Ikea-BulbE14-Candle'
    -   'TRADFRIbulbE14Wopch400lm' => 'Ikea-BulbE14-Candle',
    -   'TRADFRIbulbE14WS470lm' => 'Ikea-BulbE14-Candle',
    -   'TRADFRIbulbE27WSopal1000lm' => 'Ikea-BulbE27',
    -   'TRADFRIbulbE27WW806lm' => 'Ikea-BulbE27',

-   Interne: Cmd: Mise-à-jour formatAttribute().
-   Interne: Cmd: Correction formatAtrtibute() pour type 0x30/enum8.
-   Page 'compatibilité' revue.

## 231127-BETA-3

-   Page EQ/avancé:

    -   Réparation équipement: Améliorations.
    -   Fonctionalité maintenant accessible à tous.

-   Interne: Normalisation infos modèle: 'modelSig'/'modelName'/'modelSource'
-   Interne: Normalisation infos modèle: forcedByUser => modelForced
-   eTRV0103: Mise-à-jour du modèle.
-   Legrand micromodule switch: Mise-à-jour du modèle (2663).
-   Tuya double dimmer module QS-Zigbee-D02-TRIAC-2C-LN: Ajout support préliminaire (2664).
-   Assistant modèle: Améliorations.
-   Xiaomi wall switch sensor_86sw1 & sensor_86sw2: Support+modeles revus.
-   Tuya detecteur fumée TS0205\_\_TZ3210_up3pngle: Mise-à-jour du modèle (2658).
-   Blitzwolf BW-SHP13: Mise-à-jour du modèle.
-   Modèles: Suppression commandes obsolètes:

    -   PuissanceVoltagePrise => inf_zbAttr-0B04-RMSVoltage
    -   PuissanceCurrentPrise => inf_zbAttr-0B04-RMSCurrent
    -   poll-0B04-0505-0508-050B => act_poll-0B04-0505-0508-050B
    -   poll-0702-0000 => act_poll-0702-0000

-   Mise-à-jour de qq modèles TS0121.
-   SilverCrest-HG08673-FR: Mises-à-jour du modèle (2635).
-   Interne: AbeilleCmd: Mises-à-jour 'cmd-0201'.
-   Interne: Constante Zigbee cluster 0201: Améliorations.
-   Interne: Cmd: configureReporting2, changeVal mis à 0 par défaut.

## 231124-BETA-2

-   Support commande utilisateur: Amélioration.
-   Moes télécommande universelle IR: Mise-à-jour du code (2607).
-   Page equipement: Traductions US.
-   Réparation équipement: Améliorations.

## 231123-BETA-1

-   Page équipement

    -   Ajout affichage fabricant & modèle.
    -   Correction affichage dernier LQI.
    -   Correction traductions US.
    -   Onglet commandes legèrement revu.
    -   Onglet commandes: ajout saut de ligne avant/apres.

-   Danfoss thermostat eTRV0103: Ajout support (2662).
-   Ajout support cmdes utilisateur (ID logique = xxxidxxx::parametres). Utile pour telecommande universelle.
-   Moes télécommande universelle IR: Mise-à-jour du code & modèle (2607).

## 231119-BETA-1

-   SilverCrest-HG08673-FR: Mises-à-jour du modèle (2635).
-   ORVIBO CM10ZW: Mises-à-jour du modèle (2024 & 2648).
-   Page EQ: Affichage signature du modèle pour support modèles multi-signatures.
-   Page EQ: Affichage 'DateCode' (cluster 0000, attrib 0006).
-   Ikea wireless dimmer (ICTC-G-1): Modèle revisité.
-   Interne: Cmd: 'getRoutingTable' + 'getBindingTable' attendent ACK.
-   Interne: Status 'NO-ACK' ne fait du sens que pour équipements en écoute permanente.
-   Page EQ/avancé: Traductions US.
-   Page EQ/avancé: Réparation de l'état de l'équipement... en cours de revue.
-   Interne: AbeilleCmd: Corrections autour de la mise-à-jour d'un équipement.
-   Tuya detecteur fumée TS0205\_\_TZ3210_up3pngle: Ajout support préliminaire (2658).
-   Page maintenance/infos clefs: Amélioration.
-   Interne: Abeille.class: Corrections 'deviceUpdates'.
-   Suppression équipement: Correction incompatibilité core '4.3.19' vs '4.4.0'
-   Ikea 'TRADFRIcontroloutlet': Mise-à-jour du modèle.

## 231110-BETA-1

-   Owon-THS317-ET: Mise-à-jour du modèle (2319).
-   Page réseau: Amélioration message durant collecte LQI.
-   Gledopto GL-SD-001: Mise-à-jour du modèle.
-   Tuya ZS08 télécommande universelle alimentée par USB: Ajout support.
-   Meilleur support des modèles avec signature alternative.
-   Abeille remote control: Commandes 'on all' & 'off all' cachées par défaut. Trop dangereux.
-   Interne: Mise-à-jour 'getDevicesList()' + 'getDeviceModel()'.
-   Reset de modele: Correction pour signatures alternatives.
-   Suppression d'un équipement: Correction permettant d'afficher comment il est utilisé avant suppression (2652).
-   Page EQ/avancé: Correction interrogations quand adresse IEEE nécessaire (2653).
-   Interne: Correction: AbeilleCmd informé si changement d'adresse via 'device announce' ou migration de réseau.

## 231107-BETA-2

-   Heiman water sensor (WaterSensor-EM): Mises-à-jour mineures du modèle mais toujours pas de retour d'alarme.
-   Interne: AbeilleCmd: Correction regression 'cmd-0006'.
-   Tuya 1ch switch module: Correction modèle.
-   Découverte d'équipements absents de Jeedom améliorée lors du raffraichissement réseau.

## 231106-BETA-5

-   IKEA TRADFRIbulbE27opal1000lm: Correction modèle (2644).
-   TRV06: Correction modèle.
-   Xiaomi Aqara Opple 4 boutons: Mises-à-jour du modèle (2636).
-   Modèles: Cmde 'onGroupBroadcast'/'offGroupBroadcast' remplacée (cmd-0006 + addrMode=04).
-   Télécommande virtuelle: Correction pour 'onGroupBroadcast'/'offGroupBroadcast'.
-   SilverCrest-HG08673-FR: Mises-à-jour du modèle (2635).
-   Gledopto Spectre Blanc & RGBW GU10: Correction modèles (2646).
-   Profalux volets gen2: Mises-à-jour du modèle MOT-C1Z06C.
-   Profalux BSO gen2: Mises-à-jour du modèle.
-   Trafri remote control: Mises-à-jour du modèle.
-   Modèles: Suppression commande 'toggleGroup' => 'act_zbCmdC-0006-ToggleGroup'.

## 231104-BETA-2

-   Interne: Cmd: Correction 'cmd-0006'.
-   Interne: Zigbee const: Améliorations cluster 0702.

## 231103-BETA-1

-   Interne: Zigbee const: Correction support cluster 0012, 13 & 14.
-   Modèles de cmdes: Ajout 'act_zbCmdC-0006-OffGroup' & 'act_zbCmdC-0006-OnGroup'
-   Télécommande virtuelle: revue pour utilisation 'OnOffGroup'.
-   Modèles: Cmde 'OnOffGroup' remplacée (cmd-0006 + addrMode=01).
-   Modèles d'équipements: Correction retour d'état (bind) sur de nombreux modeles.

## 231027-BETA-2

-   Interne: Tools/check_json: Améliorations.
-   Modèles d'équipement: Correction d'erreurs sur qq modèles.
-   Assistant modèles: Améliorations.
-   SilverCrest-HG08673-FR: Support préliminaire (2635).
-   Page EQ: Corrections pour forçage de modele.
-   Xiaomi Aqara Opple 4 boutons: Support préliminaire (2636).

## 231023-BETA-2

-   Loratap télécommande 6 boutons: Support préliminaire (2631).
-   Moes 3 boutons (TS0043\_\_TZ3000_gbm10jnj): Ajout support (2630).
-   Interne: Cmd: Ajout 'cmd-0006' pour suppression vieilles commandes 'OnOffX'.
-   Interne: Cmd: Suppression support cmds 'OnOff'/'OnOffRaw'/'OnOff2'/'OnOffOSRAM'/'OnOff3'/'OnOff4'/'OnOffHue'

## 231020-BETA-2

-   Heiman HS1SA: Mise-à-jour modèle. Ajout de signatures alternatives.
-   Interne: Mises-à-jour support Tuya-Zosung.
-   Sirene iAlarm: Mises-à-jour du modèle (2629).
-   Moniteur: Amélioration interne pour support gros messages.
-   Moes télécommande universelle IR: Mise-à-jour du code pour ce support particulier (2607).
-   Page de config: Vérification adresse IP remplie si type WIFI.

## 231012-BETA-1

-   Interne: Nettoyage DB 'config' pour clefs zigates 7 à 10.
-   Cmd info 'Online': Correction (passage par 0 inattendu).
-   Profalux BSO: Modèle revisité.

## 231010-BETA-2

-   Interne: Msg 8012 n'est plus transmis à 'AbeilleCmd'.
-   Interne: Cmd: Correction changement adresse lors d'une nouvelle annonce.
-   Interne: Correction status 'NO-ACK' + 'ab::noack' => 'ab::txAck'.
-   Interne: Cmd: Mise-à-jour 'ieeeOk' à revisiter.

## 231009-BETA-1

-   Interne: AbeilleCmd: Nouvelles modifs pour support Tuya Zosung (universal remote).
-   Interne: Tuya parser: Support cmd EF00-06 (TY_DATA_SEARCH ?) revue.
-   Interne: Cmd & parser: Amélioration fonction de 'monitoring.
-   Assistant modèle: Corrections suite changement noms modèles de commandes.
-   Sirene iAlarm: Support préliminaire (2629).
-   Tuya temp/humidité/afficheur: Mise-à-jour du modèle (2619).

## 231004-BETA-1

-   Aeotec range extender: Ajout support (2627).

## 231002-BETA-1

-   Moes télécommande universelle IR: Mise-à-jour du code pour ce support particulier (2607).
-   Interne: Parser: Message 'deviceUpdates' seulement si IEEE connue.
-   Logs: 'AbeilleSerialReadX.log' déplacé dans '/tmp'.
-   Page maintenance: Correction erreur à l'affichage.
-   Page maintenance: Amélioration infos clefs.
-   Interne: AbeilleSerialRead: Changements mineurs.
-   Modeles: Ajout support 'disableTitle' pour cmd action de type 'message'.
-   Interne: AbeilleCmd revisité. Variables 'zigates' sorties de la classe.
-   Interne: Cmd: 'getNetworkStatus' => 'zgGetNetworkStatus'.
-   Interne: Cmd: Suppression message erreur 'Unknown device'.

## 230919-BETA-1

-   Interne: Parser: Cluster FC00 traité par message 8002 pour future migration mode 'raw'.
-   Interne: Ajout surveillance 'NOACK' en plus de 'Timeout'.
-   Page santé: Status amélioré => 'Time-out', ''no-ack' ou 'time-out&no-ack'

## 230918-BETA-1

-   Philips Hue Candle WA: Support preliminaire (2622).

## 230915-BETA-1

-   Modèles commandes:

    -   Suppresssion 'act_zbCmdC-XXXX-Custom.json'
    -   Ajout 'act_zbCmdC-Generic.json'

-   Interne: Cmd: Correction 'cmd-Generic'/'manufCode'.
-   Interne: Parser: Generation d'une info 'inf_EP-CLUST-cmdXX' pour toute commande inconnue spécifique cluster.
-   Moes télécommande universelle IR: Mise-à-jour du modèle & code (2607).
-   Interne: Cmd: Mise-à-jour 'setTemperature'.
-   Interne: Parser: Correction mauvaise interprétation 'ColorTempMired' (2544).
-   Livarno Home floor lamp: Mise-à-jour du modèle (2544).
-   Page updates OTA: Améliorations aspect & traductions US.
-   Interne: Cmd+Parser: Améliorations pour support 'restore PDM'.
-   Interne: Install: Correction constante nbre de Zigates.
-   Nbre de Zigates: 6 supportées au max au lieu de 10.
-   Traductions US: Qq corrections concernant la programmation du FW Zigate.
-   Interne: Modeles & Parser: Support nouveau format cluster privés.
-   Interne: Amélioration support err 14 Zigate.

## 230907-BETA-2

-   Page maintenance: Amélioration affichage JSON.
-   Page maintenance: Affichage JSON du répertoire local 'tmp'.
-   Interne: Parser: Support dump/backup tables PDM (msg 'AB00' + 'AB01', FW 'AB01-0000').
-   Page Zigate/avancé: Correction 'Reset HW'.
-   Interne: Parser: Support restoration tables PDM (msg 'AB02' + 'AB03', FW 'AB01-0000').
-   Interne: Changement nommage firmwares (zigatevX-YY.ZZ-legacy/opdm.bin).
-   Tuya afficheur température & humidité: Support préliminaire (2619).

## 230830-BETA-2

-   Page EQ/avancé: Correction traductions US.
-   Page EQ/avancé: Ajout bouton lecture version FW.
-   FW Zigates v1: Suppression FW officiels autres que 3.23.
-   Page santé: Correction tri du tableau après raffraichissement.
-   Page santé: Corrections traduction US.

## 230822-BETA-1

-   Ledvance 'PlugValue': Ajout support (2610).
-   Modèle de commande:

    -   'inf_zbCmdR-XXXX-Yyyyy' => 'inf_zbCmdC-XXXX-Yyyyy'

-   Smart Switch ZG-005-RF: Mise-à-jour du modèle (2608).
-   Page EQ/avancé: Possibilité de forcer le modèle d'équipement.
-   Page de config: Correction mineure pour mode dev.
-   Page de config: Support préliminaire FW custom (mode dev uniquement).
-   Lexman LDSENK01F: Ajout support (2613).
-   Ajout support préliminaire pour FW Zigate v1 'Abeille'.
-   Interne: Pas de polling si équipement désactivé.
-   Interne: Amélioration collecte LQI.
-   Cmde manquante: Amélioration messages d'erreurs.
-   Page EQ/avancé: Correction regression bouton suppression modèle local.
-   Interne: Commandes Zigates prefixées par 'zg'.
-   Interne: Correction adresse commande 'identifySend'.
-   Interne: 'check_json' amélioré.
-   Interne: Parser: Correction mineure.
-   Page de gestion: Correction pb de selection pour les groupes.

## 230811-BETA-1

-   Assistant modèle: Mise-à-jour.
-   Page santé: Revue pour raffraichissement automatique toutes les 2sec.
-   Interne: Suppression 'health.js'.
-   Interne: Suppression lecture attrib 0006-0000 & 0008-0000 suite à 'setLevelRaw'/'onoff'/'OnOffTimed'.

## 230810-BETA-1

-   Interne: Parser: Nettoyage.
-   Page de l'équipement: Mise-à-jour traductions 'en_US'.
-   Smart Switch ZG-005-RF: Ajout support (2608).
-   Interne: Suppression 'Abeille-Js.php'
-   Page EQ: Correction mauvais raffraichissement des infos sur recharge de page.
-   Interne: Cmd 'identifySend' revisité.
-   Page EQ: Ajout support 'identifySend'.
-   Modèle de commande:

    -   ' Identify.json' => 'act_zbCmdC-Identify'
    -   'act_zbCmdG-XXXX-YYYY' => 'act_zbCmdC-XXXX-YYYY'

## 230804-BETA-1

-   Interne: AbeilleCmd: Ajout support 'cmd-Generic'.
-   Page EQ/avancé: Ajout support 'Commande générique'.

## 230803-BETA-2

-   Page de l'équipement: Corrections translation 'en_US'.
-   Interne: jeedom.eqLogic.builSelectCmd() => buildSelectCmd(). Core v4.0 min.
-   Interne: Mise-à-jour AbeilleNoise.
-   Interne: Ajout cmd 'configureReporting2' (minInterval/maxInterval/changeVal = nombres).
-   Page EQ/avancé: Ajout support 'configureReporting2'.
-   Nous smart socket A1Z: Mise-à-jour du modèle pour réduire reporting (2460).
-   Livarno Home floor lamp: Mise-à-jour du modèle (2544).
-   Assistant modèle: Amélioration pour cluster 0300/Color control.

## 230730-BETA-1

-   Interne: Correction redémarrage Zigate Wifi.
-   Maintenance/infos clefs: Ajout nb lignes de log.
-   Profalux volet: Template par défaut = shutter pour 'Current Level'.
-   Page de config: Corrections translation 'en_US'.
-   Page de gestion: Corrections translation 'en_US'.
-   Modèles de commandes:

    -   Suppression 'zb-CustomInfo.json' obsolète.
    -   Suppression cmde obsolète 'temperatureLight.json' => 'inf_zbAttr-0300-ColorTemperatureMireds'.
    -   Suppression cmde obsolète 'temperatureLight1.json' => 'inf_zbAttr-0300-ColorTemperatureMireds'.
    -   Suppression cmde obsolète 'temperatureLight2.json' => 'inf_zbAttr-0300-ColorTemperatureMireds'.
    -   Suppression cmde obsolète 'temperatureLightV2.json' => 'inf_zbAttr-0300-ColorTemperatureMireds'.
    -   attr-XXXX => inf_XXXX

-   Moes télécommande universelle IR: Support préliminaire (2607).
-   Interne: Suppression fichier 'inconnu.php' obsolète.

## 230721-BETA-2

-   Page maintenance: Infos clefs: Mise-à-jour.
-   Interne: Mise-à-jour 'info.json'. Version minimale du core = 4.0.
-   Dependances: Mise-à-jour du code. Unique dépendance de base = python3.
-   Zigate WIFI: Socat relancé au bout de 2 mins si pas de signe de la Zigate.
-   Affichage commandes: Correction possibilité de les réordonner par drag & drop (2602).

## 230718-BETA-1

-   Interne: Parser: Améliorations pour reconnaissance fantomes.
-   Page équipement: Corrections d'affichage 'type' & 'icone'.
-   Blitzwolf temp/humidité/display: Correction signature pour 'TS0201\_\_TZ2000_hjsgdkfl'.
-   Volet Profalux: Changement catégorie => 'ouvrant'.
-   Interne: AbeilleTools::getParameters() => getConfig().
-   Zigate USB/+: Cycle power off/on si sans réponse depuis plus de 2mins.
-   Interne: Supression de qq fichiers obsoletes.
-   Page de config: Ajout option avancée pour empecher cycle power OFF/ON sur Zigates USB plantées.
-   Page de config: Qq améliorations de traductions US.
-   Interne: Améliorations 'powerCycleUsb.sh'.
-   Zigate PI/+: HW reset si sans réponse depuis plus de 2mins.

## 230711-BETA-1

-   Interne: Parser: Corrections d'identification cas Profalux.
-   Aubess TS044 \_TZ3000_wkai4ga5: Mise-à-jour du modèle pour eviter annonces multiples (2594).
-   Controlleur d'arrosage WOX: Mise-à-jour du modèle pour remontée batterie (2599).
-   Page maintenance: Mise-à-jour infos clefs.
-   Interne: Parser: Support prélim. EF00 cmd 06/TY_DATA_SEARCH.
-   Schneider Wiser plug: Support préliminaire (2601).

## 230618-BETA-1

-   Modeles équipement: Suppression commande 'Xiaomi-ff01'.
-   Correction changement de canal Zigate.

## 230613-BETA-1

-   Modèle équipement: 'value' peut etre surchargé.
-   Profalux volet: Valeur par défaut 'Set Level' ajustée sur 'CurrentLevel'.

## 230528-STABLE-1

-   Tuya garage door controller: Mise-à-jour du modèle (2581).
-   Trafri shortcut: Mise-à-jour modèle pour 'Click-Middle'.

## 230521-BETA-1

.. important:: Zigates v2

    - La maturité de la v2 n'est pas au niveau de la v1. Il est donc recommandé de suivre autant que possible les mises-à-jour du firmware (v3.A0 à ce jour).

.. important:: Zigates v1

    - Le dernier FW officiel est le v3.23. Il est recommandé de basculer dessus pour ne pas faire façe à des soucis déja corrigés.
    - Dans tous les cas un FW >= 3.1e est nécéssaire.
    - D'autre part si vous n'êtes pas en version OPDM (Optimized PDM), il est fortement recommandé de basculer dessus dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

-   Placement réseau:

    -   Affichage de tous les réseaux avec possibilité de masquer.
    -   Corrections.
    -   Ajout mode config.

-   Interne: Parser: genZclHeader()
-   Interne: Cmd: genZclHeader() sur 'discoverCommandsReceived'/'discoverCommandsGenerated'/'discoverAttributesExt'.
-   Sonoff SNZB-02D: Support préliminaire (2592).

## 230511-BETA-1

-   Loratap telecommande 3 boutons: Ajout support (2589).
-   Réparation (beta): Améliorations.
-   Disjoncteur intelligent Tongou: Mise-à-jour image (2583).
-   Tuya: Support message 0x11.
-   Tuya garage door controller: Mise-à-jour du modèle (2581).
-   Page EQ avancé: Corrections affichage identifiant & modele.
-   Placement réseau: Corrections regressions.

## 230509-BETA-1

-   Page EQ/cmdes: Correction boutons ajouter cmde (mode dev).
-   Placement réseau: Ajout bouton d'analyse réseau.
-   Placement réseau: Choix du niveau à afficher.
-   Garage door controller: Mise-à-jour du modèle (2581).
-   Support Tuya: Ajout support message 0x25/INTERNET_STATUS.
-   Page de gestion: Correction regression passage en mode inclusion non fonctionnel.
-   Page avancé: Correction regression boutons 'Réparer'/'Mise-à-jour'/'Réinit'/'Assistant'.

## 230505-BETA-2

-   Interne: AbeilleUpload: Crée toute la hierarchie de destination.
-   Placement réseau: Possibilité de charger nouveau plan + divers.
-   Matsee Plus single phase power meter: Modèle préliminaire (2588).
-   Capteur temp & humidité: Modèle préliminaire (2579).

## 230503-BETA-2

-   OTA: Fichier non FW ignoré.
-   OTA: Amélioration msg de debug.
-   Page équipement: Correction absence boutons 'sauvegarder'...
-   Placement réseau:

    -   Améliorations choix de niveaux.
    -   Couleur des liens en fonction du LQI.

## 230502-BETA-1

-   Placement réseau:

-   Taille du texte passée à 12px.
-   Possibilité de ne pas afficher les liens pour faciliter le positionnement des équipements.
-   Les cartes sont stockées en interne dans 'Abeille/tmp/network_maps'.
-   Sauvegarde automatique de la position d'un équipement.
-   Support préliminaire d'un plan par étage.
-   Blitzwolf SHP15: Mise à jour modèle.
-   Zigate: Canal Zigbee configuré à chaque démarrage.
-   Garage door controller: Support préliminaire (2581).
-   Disjoncteur intelligent: Support préliminaire (2583).
-   Interne: Prelim. Organisation page 'desktop' revue + nettoyage pour compatibilité core v4.4.
-   Interne: Merge 'SW-SDK' + 'SW-Application' => 'FW-Version'.
-   Prise murale Tuya: Support préliminaire (2584).

## 230426-BETA-1

-   Ikea E14 WS globe 470lm: Mise-à-jour modèle (2578).
-   Xiaomi 'sensor_ht': Modification modele pour ajout section 'xiaomi'.
-   Module volet roulant LoraTap SC500ZB-v2: Mise-à-jour modèle (2552).
-   Trafri remote control: Mise-à-jour modele (2576).
-   Nouvelles commandes pour cluster 0008.
-   Interne: Parser: Correction bugs cluster 0006 & 0008.

## 230422-BETA-1

-   Network graph: Possibilité de sauver la position d'un équipement.
-   Network graph: Renommé en 'Placement réseau'.
-   Interne: Placement réseau: Ajout config 'ab::userMap'.
-   Plaement réseau: Limitation aux dimensions du plan.
-   Interne: Parser: Correction message cluster 0006 dupliqué (2574).
-   Placement réseau: Couleur de lien fonction du LQI.
-   Interne: Parser: Cmd Ikea cluster 0005 cmd 07 revue.
-   Trafri remote control: Mise-à-jour modele (2576).
-   Interne: Parser: Correction 'decodeDataType()' pour type 41/42.
-   Interne: Parser: Correction 'Attribut report'.
-   Ikea E14 WS globe 470lm: Support préliminaire (2578).
-   OTA: Correction regression.

## 230416-BETA-1

-   Modeles: Ajout/correction 'logicalId'.
-   Sonoff ZBMINIL2: Mise-à-jour du modèle & image (2569).
-   Blitzwolf BW-IS4: Correction type batterie & timeout.
-   Tuya temp & humidity display: Ajout support 'TS0201\_\_TZ2000_a476raq2' (2570).
-   Interne: Cmd: moveToLiftAndTiltBSO(), correction PHP warning.
-   Interne: Parser: Support type 4C pour Xiaomi.
-   PaulmannLichtGmbH 500.44: Ajout image. Modele non confirmé (2516).
-   Network graph: Modifications internes préliminaires.
-   Interne: Correction perte de cmdes lors de la mise-à-jour.
-   Interne: Parser: Correction decodeDataType().
-   Interne: Parser: Correction pour inclusion.
-   Interne: Suppression des 'comment' durant mise-à-jour des cmdes.
-   Interne: Correction 'ep manquant'.

## 230408-BETA-5

-   Bouton IP55 Moes: Mise-à-jour du modèle (2562).
-   Xiaomi sensor_switch.aq2/remote.b1acn01: Mise-à-jour des modèles.
-   Interne: Parser: Suppression decodeFF01().
-   Assistant découverte: Améliorations.
-   Xiomi plug: Mise-à-jour du modèle.
-   Sonoff ZBMINIL2: Ajout support préliminaire (2569).
-   Interne: Parser: Extension support attribut non standard.
-   TS201 (TS0201\_\_TZ3000_ywagc4rj): Modele specifique pour '%' non standard (2567).
-   Curtain module (TS130F\_\_TZ3210_dwytrmda): Ajout support (2568).
-   Modele cmd 'click' renommé en 'inf_click'.
-   Livolo TI0001: Correction modele (cmds logicalId).
-   Modeles: Ajout/correction 'logicalId'.

## 230405-BETA-2

-   Groupes: Ajout 'suppression de tous les groupes'.
-   Image: 'node_TRADFRIonoffswitch.png' => 'node_Ikea-OnOffSwitch.png'
-   Loratap roller shutter touch switch v2: Mise-à-jour image (2561).
-   Interne: Amélioration process de réparation.
-   RDM001: Mise-à-jour du modèle (2185).
-   Xiaomi vibration: Mise-à-jour du modèle.
-   Xiaomi smoke (sensor_smoke): Modele géré par section 'xiaomi'.
-   Interne: Parser: decodeDataType() ne s'arrete plus si erreur de taille.
-   Prise Aubess TS011F, \_TZ3000_gvn91tmx: Mise-à-jour du modèle (2558).
-   TS201: Ajout signature TS0201\_\_TZ3000_ywagc4rj (2567).
-   Assistant modele: Mise-à-jour.
-   Bouton IP55 Moes: Ajout support préliminaire (2562).
-   Fonction 'réparation' préliminaire accessible à tous.

## 230328-BETA-2

-   Interne: Parser: Correction detection support de groupes.
-   Page avancé/réparation: Support préliminaire.
-   Page avancé: Ajout 'localisation' pour identifiant Zigbee (cas Profalux).
-   Interne: Parser: Suppression support 8085/Level update pour compatibilité mode raw.
-   Page avancé: Affichage des differents identifiants Zigbee si plusieurs.
-   Loratap roller shutter touch switch v2: Mise-à-jour du modèle (2561).
-   Mise-à-jour modèle TS130F\_\_TZ3000_1dd0d5yi.
-   Interne: Cmd: Amélioration mesg d'erreurs.
-   Modèles:

    -   Ajout 'act_setLevel-Light' pour remplacer 'setLevel'.
    -   Remplacement 'setLevel' => 'act_setLevel-Light'
    -   Suppression des cmdes info 'Groups'

-   Ikea Trafri 470lm E27: Ajout support (2564).
-   Groupes: Amélioration pour suppression d'un groupe.
-   Page de config: Mise-à-jour des traductions anglaise.

## 230326-STABLE-1

## 230325-BETA-2

-   Legefirm repeteur zigbee: Ajout support (2560).

## 230324-BETA-2

-   Legrand shutter switch: Correction modèle (2559).
-   Interne: Constantes Zigbee, amélioration support cluster 0102.
-   Interne: Parser: Ajout support 'unbind response'.
-   Interne: createDevice(): Mise-à-jour pour éviter conflit de commandes.
-   TRADFRIonoffswitch: Mise-à-jour du modèle.
-   Modele de commandes: Suppression de cmde obsoletes.

    -   current_position_lift_percentage
    -   getcurrent_position_lift_percentage

-   Interne: Cmd: Changement msg debug.
-   Interne: Parser: Correction support 'Node Descriptor Response'.
-   Interne: Parser: Amélioration inclusion (ajout lecture 'manufCode').
-   Loratap roller shutter touch switch v2: Support préliminaire (2561).

## 230322-BETA-3

-   Girier curtain module: Mise-à-jour du modèle (2526).
-   Interne: Parser: Correction warning PHP 'Binding table response'.
-   Modele TS201 renommage automatique vers 'TS0201\_\_TYZB01_hjsgdkfl'.
-   Page avancé: Support préliminaire 'unbind'.
-   Interne: Cmd: Support préliminaire 'unbind0031'.
-   Interne: Cmd: Ajout support 'remove all groups'.
-   Gestion des groupes: Amélioration affichage mineure.
-   Groupes de la Zigate: Correction regression.
-   Prise Aubess TS011F, \_TZ3000_gvn91tmx: Mise-à-jour du modèle (2558).

## 230320-BETA-3

-   Image: 'Shutterswitchwithneutral' => 'Legrand-ShutterSwitch'.
-   Image: 'Xiaomiwleak_aq1' => 'Xiaomi-LeakSensor'.
-   Interne: Amélioration remplacement '#addrIEEE#', '#IEEE#' ou '#ZigateIEEE#'.
-   'sensor_wleak.aq1': Mise-à-jour du modèle.
-   Page maintenance: Amélioration infos clefs.
-   Package de logs: Ajout log 'event'.
-   Aqara Motion Sensor P1 RTCGQ14LM/MS-S02: Mise-à-jour modèle (2463).
-   Xiaomi 'plug': Mise-à-jour du modèle.

## 230319-BETA-1

-   Interne: Parser: Correction regression inclusion.
-   Interne: Plusieurs correctifs 'deviceUpdates'.
-   Page avancé: Ajout 'siren level' pour cmde 'Start Warning' (cluster 0502).
-   Sirène M0L0-HS2WD-TY: Ajout info pourcentage batterie (2550).
-   Support préliminaire 'pigiod' pour Pi-Zigates.
-   Interne: Parser: Amélioration support cmdes specifiques cluster 0008.
-   Modele TS201 renommé => TS0201\_\_TYZB01_hjsgdkfl.
-   Aubess prise TS011F: Ajout support préliminaire (2558).

## 230314-BETA-1

-   Loratap shutter: Nouveau modele: TS130F, \_TZ3000_femsaaua (2552)
-   MOES ZK-FR16M-WH: Mise-à-jour modèle 'TS011F\_\_TZ3000_cphmq0q7' (2554).
-   Interne: Cmd 0502: Amélioration pour support 'siren level'.
-   Sirène M0L0-HS2WD-TY: Mise-à-jour modèle (2550).
-   Commandes: Suppression 'VoltagePrise'.

## 230311-BETA-1

-   Xiaomi Aqara 2 way control module: Mise-à-jour modèle (2551).
-   Page avancé/Mise-à-jour: Amélioration correction icone si invalide.
-   Sirène M0L0-HS2WD-TY: Mise-à-jour modèle (2550).
-   Interne: parser: correction crash decode8002_MgmtRtgRsp().
-   Image: Renommage 'HS2WD' => 'Heinman-IndoorSiren'.

## 230308-BETA-1

-   Interne: Parser: Décodage single/double precision revu.
-   WarningDevice: Modèle supprimé. Supporté via 'WarningDevice-EF-3.0'.
-   Interne: AbeilleTools: Suppression des 'commentX'.
-   Page avancé: Cluster 0502/IAS WD, cmd 00/Start warning: Ajout 'duration'.

## 230306-BETA-1

-   Ikea telecommande 5 boutons: Mise-à-jour modèle (2547).
-   Affichage groupes: Petite mise-à-jour.
-   Interne: Cmd: Correction 'cmd-0502'.
-   Interne: Parser: Msg 8095 désactivé pour support mode 'raw'.
-   Page avancé: Support 'cluster 0502/IAS WD, cmd 00/Start warning'.
-   Xiaomi Aqara 2 way control module: Mise-à-jour modèle (2551).

## 230301-BETA-1

-   Interne: Parser: Erreur 'msgToLQICollector' masquée pour FW 0005-03A0 (2546).
-   Interne: Parser: Erreur 'msgToRoutingCollector' masquée pour FW 0005-03A0 (2546).

## 230228-BETA-1

-   Page maintenance/infos clefs: Amélioration mineure.
-   Interne: Parser: Amélioration mineure msg debug.
-   Nodon SIN-4-2-20: Mise-à-jour modele (2541).
-   Innr RC110: Mise-à-jour modèle + renommé 'RC110' => 'RC110_innr'.
-   Interne: Cmd: 'addGroup' revu.
-   Modèles: 'groupEPx' pour définir une constante de groupe par end point.
-   Interne: Configuration équipement faite par AbeilleCmd.
-   Interne: getGroupMembership() revu.
-   Interne: Parser: Ajout support 'addGroupResponse'/'removeGroupResponse' + zigbee['groups'].
-   Interne: Parser: Interrogation des groupes lors de l'inclusion.
-   Interne: Groupes: Utilisation eqLogic/config/zigbee/groups au lieu cmde info.

## 230219-BETA-1

-   Interne: Parser: Changement support clust 0000, attr 0004/5/10.
-   Nodon SIN-4-2-20: Correction image (2541).
-   Interne: Cmd: Timeout 8s si ACK.
-   Xiaomi switch (switch.n0agl1): Correction regression modele (2517).
-   Frient keypad: Mise-à-jour modele (2525).

## 230215-BETA-2

-   Interne: Parser: Améliorations mineures cluster 0004.
-   Interne: Abeille.class: 'repeatEventManagement' seulement si reset équipement.
-   Frient keypad: Mise-à-jour modele (2525).

## 230214-BETA-1

-   Interne: Cmd: Correction 'bind0030'.
-   Eurotronic SPZB0001: Mise-à-jour modèle.
-   Interne: Parser: Optimisation correction valeur suivant spec ZCL.
-   Interne: Parser: Correction regression clean location. Peut impacter Profalux.
-   Interne: Parser: Suppression progressive decode8100_8102().
-   Interne: Parser: Suppression support messages 80A0/80A3/80A4 pour compatibilité 'raw'.
-   Interne: Parser: Optimisations pour compatiblité futur mode 'raw'.
-   Frient keypad: Mise-à-jour modele (2525).
-   Nodon SIN-4-2-20: Support préliminaire (2541).
-   Interne: Parser: Optimisations.
-   Page maintenance/infos clefs: Amélioration mineure.
-   Interne: Abeille.class: Suppression 'volt2pourcent()'.
-   Interne: Parser: Revue decode 8001/logs.
-   Interne: SW reset si erreur 06 sur msg 8000 (2490).

## 230207-BETA-3

-   Interne: SerialRead: Filtrage des msgs de mauvaise longueur.
-   Livarno Home floor lamp: Support préliminaire (2544).
-   LoraTap Zigbee 3 gang remote: Ajout support (2542).
-   Interne: Page maintenance/logs. Correction mineure.
-   Interne: Cmd: 004E/LQI attend ACK.
-   Interne: Cmd: Timeout 7s si ACK.
-   Xiaomi relay (relay.c2acn01): Mise-à-jour modèle.
-   Frient keypad: Mise-à-jour modele (2525).
-   Interne: Parser: Améliorations support cluster 0501 pour 'Emergency'/'Fire'/'Panic'.
-   Interne: Parser: Support 0501/Arm code.
-   Page des équipements: Groupes par Zigate (967).

## 230204-BETA-2

-   Interne: Parser: Nettoyage 'ModelIdentifier' revue; 0 devient caractère de fin.
-   Interne: Cmd: Améliorations support clusters 0500 & 0501.
-   Interne: Parser: Msg 8401 désactivé.
-   Modèles: Correction nom 'inf_zbAttr-0500-ZoneStatus..' => 'inf_zbCmdS-0500-ZoneStatus...'.
-   Frient keypad: Mise-à-jour modele (2525).

## 230202-BETA-3

-   Interne: AbeilleCmd: Mise-à-jour 'getRoutingTable'.
-   Interne: Collecte des tables de routage revue.
-   Interne: Parser: Amélioration affichage cmds cluster.
-   Interne: Zigbee const: Amélioration support cluster 0501.
-   Interne: Parser: Amélioration 'getDevice()' si IEEE pas défini.
-   Interne: Parser: Amélioration 'cleanModelId()' pour caracteres speciaux.
-   Interne: Suppression 'routingTable' de la table 'eqLogic'.
-   Réseau/graph des liens: Mise-à-jour.

## 230130-BETA-2

-   Interne: Parser: Ajout infos debug pour support Xiaomi.
-   Modeles: Mise-à-jour 'sensor_cube' + 'sensor_cube.aqgl01'.
-   Reseau: Mise-à-jour graphique des liens.
-   Perte formule au redémarrage: Correction (2540).
-   Interne: Parser: Correction décodage 'routing table response'.

## 230126-BETA-2

-   Heiman water leakage sensor: Mise-à-jour modèle (2527).
-   Xiaomi water leak sensor: Mise-à-jour modèle.
-   Interne: Parser: Nettoyage support Xiaomi pour 'magnet.aq2', 'weather'.
-   Interne: Parser: Corrections decode 'Mgmt_NWK_Update_notify'.
-   Interne: DB config: Ajout 'ab::zgChan' pour sauver choix de canal Zigbee.
-   Page de config: Canal Zigbee affiché.
-   Interne: Sauvegarde choix du canal Zigbee (11 par défaut).
-   Interne: Parser: Correction crash Xiaomi.
-   Modele Xiaomi 'sensor_swith' revu.
-   Interne: AbeilleCmd: timeout passé de 3 à 4sec avant de déclarer cmd perdue.
-   Interne: Correction mise-à-jour (au lieu de reset) équipements au démarrage.
-   Interne: AbeilleCmd: Correction setChannel.
-   Interne: Changement de canal Zigbee revu (broadcast mgmtNwkUpdateRequest).
-   Interne: Parser: Correction erreurs PHP.

## 230124-BETA-1

-   Page avancé: Ajout version SW du device (clust 0000, attr 4000).
-   Interne: Parser: Supression utilisation msg OTA 8503 pour compatibilité raw.
-   Xiaomi Aqara QBKG26LM: Mise-à-jour modèle (2174).
-   Interne: Parser: Cluster 000C géré par decode8002().
-   Modèles: Suppression cmdes obsoletes 'puissance1', 'puissance', & 'puissanceEP15'.

## 230121-BETA-2

-   Interne: Parser: Support cluster 0500 cmd 00/Zone status change notif.
-   Interne: Parser: Correction regression.
-   Interne: Cmd: Changement cosmetique msg debug.

## 230120-BETA-1

-   Interne: Mise-à-jour équipement revue pour éviter la perte de commandes.
-   Réseau: Mise-à-jour graph réseau.
-   Aqara Motion Sensor P1 RTCGQ14LM/MS-S02: Mise-à-jour modèle (2463).
-   Owon PIR323: Mise-à-jour modèle (2533).
-   Interne: Reset SW de la Zigate si pas de réponse depuis plus de 2mins.
-   Sonoff ZBMini-L: Ajout support (2539).
-   Interne: Parser: Support préliminaire cluster 0020, cmde 'check-in'.
-   Interne: Cmd: Support préliminaire cluster 0500 zone enroll response.
-   Interne: Reinit à partir du modele revue pour ne pas perdre modifs utilisateur.

## 230113-BETA-2

-   Xiaomi RTCGQ11LM: Mise-à-jour modele et image.
-   Interne: Correction support Xiaomi.
-   Owon PIR323: Image (2533).
-   Frient keypad: Mise-à-jour modele (2525).

## 230112-BETA-3

-   Legrand micromodule switch: Mise-à-jour modele et image.
-   Interne: Parser: Amélioration mess monitor cas Xiaomi.
-   Modèles: Qq nettoyage + ajout logicalId sur certaines actions.
-   Lexman smart plug: Support préliminaire (2531).
-   Assistant modeles: Mise-à-jour suite renommage des commandes.
-   Tuya 1Ch switch module (TS0001\_\_TZ3000_tqlv4ug4): Mise-à-jour modèle.
-   Modeles pour Xiaomi: Amélioration syntaxe.
-   Page EQ/avancé: Liste pour les types possibles d'attribut.
-   Interne: Parser: rxOn n'est plus mis à jour par 'Mgmt_lqi_rsp' (pas fiable).
-   Owon PIR323: Ajout support (2533).
-   Interne: Parser: 'rxOnWhenIdle' peut etre mis-à-jour par 'node descriptor'.
-   Modeles: 'minValue', 'maxValue', 'calculValueOffset' mis a jour seulement si reset.

## 230106-BETA-2

-   Modeles EQ: Ajout prise en charge 'genericType'.
-   Modèles: Ajout du type generique sur qq modeles.

## 230106-BETA-1

-   Interne: Parser: Correction 'single precision'.
-   Modeles commandes: Normalisation de certains noms (inf_zbAttr-XXXX-YYYY).
-   Interne: Parser: Correction support Xiaomi.
-   Xiaomi Door Sensor MCCGQ11LM: Mise-à-jour du modèle pour restauration 'Battery-Volt'.
-   Xiaomi Temp-humidité-pression WSDCGQ11LM: Mise-à-jour du modèle pour restauration 'Battery-Volt'.

## 230103-BETA-5

-   Modèles: Suppression support ancienne syntaxe 'include'.
-   Girier curtain module: Support préliminaire (2526).
-   Assistant modèle: Mise-à-jour.
-   Maintenance/infos clefs: Amélioration.
-   Heiman water sensor: Support préliminaire (2527).
-   Interne: Parser: Support de certains devices Xiaomi via decode8002 pour compatibilité mode 'raw'.
-   Loratap roller shutter module: Support préliminaire (2528).
-   Moes thermostat BRT-100: Mise-à-jour modèle (2467).
-   Modèles commandes: 'Short-Addr' & 'IEEE-Addr' => 'inf_addr-Short'/'inf_addr-Ieee'.
-   Modèles commandes: 'Link-Quality'/'online' => 'inf_linkQuality'/'inf_online'.
-   Modèles commandes: 'Time-Time'/'Time-TimeStamp' => 'inf_time-String'/'inf_time-Timestamp'.
-   Modèles EQ: Surcharge possible de 'Polling'.
-   Tuya TV02: Mise-à-jour du modèle (2175).
-   Xiami RTCGQ11LM: Mise-à-jour du modèle.

## 230102-BETA-1

-   Interne: Constantes Zigbee: Améliorations.
-   Interne: Parser: Update mineure msg debug Xiaomi.
-   Interne: Parser: Suppression support 8041, 8043 & 8045 pour compatibilité mode 'raw'.
-   Interne: Parser: Suppression support 804A pour compatibilité mode 'raw'.
-   Interne: Parser: Suppression support 8030 pour compatibilité mode 'raw'.
-   Interne: Parser: Suppression support 8060, 8062 & 8063 pour compatibilité mode 'raw'.
-   Profalux shutter: Correction modele pour retour de 'Level'.
-   Interne: Parser: decodeDataType(), ajout support type 39/single.
-   Frient keypad: Support préliminaire (2525).

## 230102-STABLE-1

.. important:: Zigates v2

    - Doivent être à jour du dernier firmware disponible (v3.21 à ce jour).

.. important:: Zigates v1

    - Doivent avoir un firmware >= 3.1e pour un fonctionnement optimal mais la dernière en date (3.21) est fortement recommandée.
    - L'équipe Zigate recommande FORTEMENT d'utiliser un firmware **Optimized PDM** (OPDM) dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

## 221215-BETA-3

-   Interne: Amélioration infos en mode surveillance (AbeilleMonitor.log).
-   Zemismart ZW-EC-01 curtain switch: Modèle revu mais équipement déconseillé.
-   Interne: AbeilleCmd: Optimisation & nettoyage.
-   Heiman HS1HT: Mise-à-jour image (2520).
-   Heiman HS1MS-EF: Mise-à-jour image (2521).
-   Tuya 1Ch switch module: Ajout support préliminaire 'TS0001\_\_TZ3000_tqlv4ug4'.
-   Interne: Correction pour équipement inconnu pendant raffraichissement réseau.

## 221214-BETA-9

-   Interne: Cmd: 'setLevelVolet' utilise 'cmd-0008'.
-   Interne: Cmd: 'setLevel': Suppression 'readAttribute' consecutifs.
-   Modèle EQ: Surcharge possible de 'listValue'.
-   Interne: Support cmde action de type 'liste'.
-   Interne: Parser: Mise à jour 'node descriptor'.
-   Heiman HS1HT: Mise-à-jour modèle (2520).
-   Interne: Cmd: Mise-à-jour 'writeAttribute' pour '#select#'.
-   Moes thermostat BRT-100: Mise-à-jour modèle (2467).
-   Maintenance/infos clefs: Amélioration.
-   Heiman HS1MS-EF: Support préliminaire (2521).

## 221213-BETA-6

-   Modele EQ: Support 'trigOut' pour cmde action.
-   Tuya TV02: Mise-à-jour.
-   Philips SML004: Mise-à-jour modele (2437).
-   Moes thermostat BRT-100: Mise-à-jour modèle (2467).
-   Tuya: Amélioration support.
-   Assistant modèle: Améliorations.
-   Aubess 4 buttons switch: Support préliminaire (2512).
-   Aqara Motion Sensor P1 RTCGQ14LM/MS-S02: Mise-à-jour modèle (2463).

## 221212-BETA-2

-   Heiman HS1HT: Support préliminaire (2520).
-   Reinitialisation: Remise en cause du modele utilisé chaque fois.
-   Interne: Améliorations support Tuya.
-   Tuya TV02: Mise-à-jour du modèle (2175).
-   Interne: Support cluster FCC0 Xiaomi générique.
-   Moes temp/humidity sensor: Mise-à-jour du modèle (2500).
-   Tuya mini smart switch: Correction image (2438).
-   Aqara Motion Sensor P1 RTCGQ14LM/MS-S02: Mise-à-jour modèle (2463).
-   Paulmann 50044: Ajout support préliminaire (2516).

## 221209-BETA-4

-   Moes thermostat BRT-100: Mise-à-jour modèle (2467).
-   Assistant découverte: Améliorations.
-   Assistant découverte: Ajout suffixe identiant (ex: discovery-TS0121\_\_TZ3000_rdtixbnu.json)
-   Modele TS011F: Ajout de plusieurs marques blanches.
-   Icasa ICZB-IW11SW: Ajout support préliminaire (2515).
-   Icasa ICZB-IW11D: Ajout support préliminaire (2514).
-   Icasa ICZB-DC11: Ajout support préliminaire (2513).
-   Interne: Parser: Corrections regressions.

## 221208-BETA-1

-   Moes thermostat BRT-100: Mise-à-jour modèle (2467).
-   Interne: Parser: Amélioration mineure.
-   Interne: Abeille.class: Amélioration sur reception msg trop grand.
-   Suppression ancien log 'AbeilleConfig' (sans .log) au démarrage.
-   Interne: Taille queue xToAbeille étendue.
-   Interne: Parser: Affichage nPDU/aPDU avec extended error.

## 221204-BETA-1

-   Images: Normalisation de noms.
-   Interne: Abeille.class: Correction findModel (2509).

## 221202-BETA-2

-   Suppression chiffres après virgule sur pourcentage batterie.
-   Interne: Parser: Nettoyage fonctions obsoletes.
-   Interne: Cmd: Activation ACK pour 'setLevelRaw' + 'cmd-0008'.
-   Interne: Support préliminaire 'Mgmt Nwk Update Req'.
-   Modeles commandes: Amélioration 'valueOffset' pour support ID logique.
-   Moes curtain module: Mise-à-jour modèle (2464).
-   Aqara Motion Sensor P1 RTCGQ14LM/MS-S02: Mise-à-jour modèle (2463).

## 221130-BETA-1

-   Syntaxe cmdes: Ajout support 'valueOffset' pour cmde 'action'/'slider'.
-   Moes curtain module: Mise-à-jour modèle (2464).
-   Renitialiser: Amélioration si équipement etait inconnu mais qu'un modèle existe maintenant.
-   Moes BHT-002-GCLZBW: Ajout support préliminaire (2485).
-   Livolo TI0001: Mise-à-jour modèle (2476).
-   Interne: Améliorations AbeileCmd.
-   Interne: AbeilleCmd: Limitation de débit activé.
-   Interne: Améliorations préliminaires pour support générique Xiaomi.
-   Interne: Parser: Correction pour SW reset sur NDPU bloqué.
-   Interne: Collecte LQI: Améliorations mineures.
-   Xiaomi Door Sensor MCCGQ11LM: Mise-à-jour du modèle.
-   Interne: Parser: decodeDataType(): Ajout support 2B/int32.
-   Xiaomi Temp-humidité-pression WSDCGQ11LM: Mise-à-jour du modèle.

## 221122-BETA-1

-   Page Zigate/avancé: Reset HW possible sur Piv2.
-   Page Zigate/avancé: Amélioration selection du canal.
-   Modèles EQ: Support customization 'rxOn'.
-   Livolo TI0001: Mise-à-jour modèle (2476).
-   Moes BRT-100: Mise-à-jour modèle (2467).
-   Images: Qq mises-à-jour & renomages.
-   Interne: Parser/cleanManufId(): '.' ignoré.
-   Philips E27 LWA017: Ajout support (2503).
-   Interne: Cmd: Ajout support 'move to level' (cmd-0008).

## 221119-BETA-2

-   Zigate PI v2: Correction controle GPIO (rc.local n'est plus nécessaire).
-   Repeteur Loratap: Ajout support (2498).
-   Interne: AbeilleCmd: Pas de renvoi si message 8000 status 06.
-   Interne: Abeille.class: 'customization' & 'macCapa'. Encore une update.
-   Interne: Reinitialisation d'un équipement: delai interne avant relecture DB par parser.
-   Moes temp/humidity sensor: Ajout support (2500).
-   Interne: Petite update page santé.
-   Maintenance/infos clefs: Ajout status (timeout) de chaque équipement.
-   Interne: Abeille.class: Amélioration mineure msg debug executePollCmds().
-   Moes BRT-100: Mise-à-jour modèle (2467).
-   Interne: Support Tuya amélioré: Cmd: Ajout 'setValue', 'setValueMult' & 'setValueDiv'.
-   GLEDOPTO GL-FL-004P: Support préliminaire (2501).
-   Page Zigate/avancé: Modification choix canal zigbee.

## 221114-BETA-2

-   Interne: Parser: Optimisation.
-   Interne: Cmd: Modification gestion ACK. 8702 ignoré au profit de 8011.
-   Interne: Parser: Amélioration messages dbg Xiaomi.
-   Interne: Parser: Nettoyage 'manufacturer' revu (cleanManufId()).
-   Interne: Parser: Correction 'customization' + 'macCapa'.
-   Interne: Parser: Divers correctifs & améliorations.

## 221110-BETA-2

-   Interne: Parser: Surveillance NPDU, timeout 4mins.

## 221110-BETA-1

-   Interne: Install: Correction 'Batterie Volt' (0001-01-0020). Suppression 'calculValueOffset'.
-   Interne: Parser: Check NPDU and force SW reset if stuck for more than 3 mins.
-   Modèles: Ajout 'customization' optionnelle pour corriger/forcer infos.

## 221108-BETA-1

-   Modèles: Nettoyage. Suppression cmds obsoletes setLevelVoletUp/setLevelVoletDown.
-   Modèles: Correction 'sensor_86sw1' pour 'Battery-Percent'.
-   Modèles: Ajout qq 'manufacturer' manquants.
-   Interne: AbeilleCmd: Ajout support 'cmd-0102' + suppression 'WindowsCovering'.
-   Moes curtain module: Mise-à-jour modèle (2464).

## 221107-BETA-1

-   Interne: Parser: 'Batterie-Pourcent' => '0001-01-0021'.
-   Interne: install/DB cmd: 'Batterie-Pourcent' => '0001-01-0021'

## 221105-BETA-1

-   Interne: Parser: Xiaomi tags decode update.
-   Modèles: Historisation activée par défaut pour 0400/0402/0403 & 0405 clusters attr 0000.
-   Modèles: Surcharge possible de 'isHistorized'.

## 221104-BETA-1

-   Support: Infos clefs: Affichage type de Zigate.
-   Interne: Suppression 'uniqId' DB eqLogic/configuration.
-   Interne: Message zigate 804E plus utilisé. Pas assez robuste => decode8002().

## 221103-BETA-2

-   Equipements: Qq modeles revus.
-   Modèle de commandes: 'Batterie-Volt' remplacé par '0001-01-0020'.
-   Interne: Constantes Zigbee: Ajout data types cluster 0000.
-   Page EQ/avancé: Message d'erreur si champ manquant.
-   Interne: AbeilleCmd: Meilleur support type 'string' pour 'writeAttribute()'.
-   Interne: Parser: Cluster 0001 (batterie) traité par 'decode8002()'.
-   Modeles: Commandes obsoletes: 'Batterie-Hue', 'Batterie-Pourcent' & 'Batterie-Volt-Konke'.
-   Interne: Suppression 'bindShort' obsolete.
-   Modèle équipements: Syntaxe 'alternateIds' améliorée.
-   Ruche: Cmde 'Set inclusion mode' est de retour pour cas 2476 non résolu.
-   Page EQ/avancé pour Zigate: Correction 'Reset HW' pour PI-Zigate.
-   Interne: Qq changements autour du séquencement du démarrage des démons.
-   Interne: Remplacement cmd obsolete 'levelVoletStop' + nettoyage code.
-   Modeles: Support 'notStandard' pour les commandes 'illuminance' qui ne respectent pas la spec ZCL.

## 221029-BETA-1

-   Interne: Zigbee const: Ajout 0403/pressure.
-   Interne: Parser: Attribut '0403-xx-0000' (pressure) directement décodé par 8002.
-   Interne: Parser+install+modele: Attribut '0402-xx-0000' (temperature) directement décodé par parser/8002.
-   H1 dual wall switch: Mise-à-jour du modèle (2474).
-   Interne: Parser+install+modele: Attribut '0400-xx-0000' (illuminance) directement décodé par parser/8002.
-   Ajout des FW 3.23 pour les Zigates v1.
-   Interne: Optimisation suppression des queues lors de l'arret des démons.
-   Interne: Blocage relance des démons si mise-à-jour FW ou test de port.
-   Démons start & stop: Amélioration. Devrait limiter les cas 'port toujours utilisé'.

## 221024-BETA-1

-   Interne: Parser: Correction decodeDataType() impactant types longs (ex: uint48).
-   Interne: Parser: Types 18, 19, 28 & 29 maintenant traités par decodeDataType().
-   Interne: Parser+install+modele: Attribut '0405-xx-0000' (humidity) directement décodé par parser.
-   Interne: Parser: Cluster 0405 traité par decode8002().
-   Philips E27 white bulb: Mise-à-jour du modèle (2421).
-   Page EQ/avancé: amélioration mineure.
-   Page maintenance: Correction regression sur 'Telecharger tout'.
-   Interne: Parser: Améliorations support cluster 'FCC0' Xiaomi.

## 221023-BETA-1

-   Interne: Correction regression constantes Zigbee.

## 221022-BETA-1

-   Moes curtain module: Mise-à-jour modèle (2464).
-   Moes 2 gang dimmer module: Modele preliminaire (2473).
-   Interne: Premier fichier 'packages.json' pour installation de dependances.
-   H1 dual wall switch: Support preliminaire (2474).
-   Interne: Cmd: sliderToHex(), ajout support enum8 & 16.
-   Nous A1Z smart plug: Mise-à-jour du modèle (2460).
-   Page maintenance/logs: Message si pas en mode 'debug' lors du téléchargement.
-   Interne: Constantes Zigbee: Definitions des types.
-   Philips E14 white bulb: Modele preliminaire (2422).
-   Philips E27 white bulb: Modele preliminaire (2421).

## 221019-STABLE-1

.. important:: Les zigates v2 doivent être à jour du dernier firmware disponible (v3.21 à ce jour).
.. important:: Pour les zigates v1, l'équipe Zigate recommande FORTEMENT d'utiliser un firmware **Optimized PDM** (OPDM) dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

.. important:: Les zigates v1 doivent avoir un firmware >= 3.1e pour un fonctionnement optimal mais la dernière en date (3.21) est fortement recommandée.

## 221014-BETA-1

-   Aqara Motion Sensor P1 MS-S02: Support preliminaire (2463).
-   Silvercrest smart button: Mise-à-jour modèle (2468).
-   Page maintenance/logs: Correction ascenseur partie gauche.
-   Interne: Arret des démons: Correction mineure & améliorations.

## 221010-BETA-1

-   Xiaomi D1 wall switch single: Support préliminaire (2466).
-   Moes BRT-100: Support préliminaire (2467).
-   Silvercrest smart button: Support préliminaire (2468).
-   Interne: Cmd+Parser: Ajout support 'discoverCommandsGenerated'.
-   Assistant découverte: Amélioration: Ajout recherche commandes generées.
-   Network graph: Temporairement masqué. En cours de refonte.
-   Moes - Smart Brightness Thermometer: Support préliminaire (2469).
-   Assistant découverte: Correction pour support multi EP.

## 221007-BETA-1

-   Interne: Parser: Amélioration msg debug.
-   Network graph: Correction.
-   Network graph: Nombreux changements internes.. normalisation, nettoyage.
-   Interne: DB eqLogic: 'positionX' => 'ab::settings[physLocationX]'.
-   Interne: DB eqLogic: 'positionY' => 'ab::settings[physLocationY]'.
-   Interne: Nettoyage DB au démarrage revu.
-   Page EQ/avancé d'une Zigate: Ajout boutons 'démarrer/arrêter' pour inclusion.
-   Moes curtain module: Support preliminaire (2464).
-   Interne: DB config: Suppression clefs obsoletes 'blocageRecuperationEquipement' + 'blocageTraitementAnnonce'.
-   Interne: DB config: 'DbVersion' => 'ab::dbVersion'.

## 220930-BETA-1

-   Interne: Suppression queue 'ctrlToCmd' au profil de 'xToCmd' + améliorations 'CliToQueue'.
-   Interne: Mise à jour generation doc.
-   Nous A1Z smart plug: Ajout support préliminaire (2460).
-   Livarno Home HG07834B: Mise-à-jour modele (2448).
-   Philips SML004: Mise-à-jour modele (2437).
-   Maintenance/télécharger tout: Ajout alerte si moins de 5000 lignes de logs.
-   Interne: Parser: Clusters supportés par 8100/8102 revus à la baisse (=> 8002).

## 220928-BETA-1

-   Interne: Suppression queue 'assistToCmd' + nettoyage 'assistToParser'.
-   Interne: AbeilleCmd: Amélioration 'sliderToHex()'.
-   Tuya vibration sensor TS0210: Mise-à-jour modèle (2452).

## 220927-BETA-1

-   Orvibo CM10ZW: Ajout affichage 'Status X' (2024).
-   Livarno Home HG07834B: Mise-à-jour modele (2448).
-   Firmware zigate: Recommandation d'utiliser la v3.21.
-   Firmwares v1: Suppression des versions < '3.21'.
-   Page maintenance/infos clefs: Ajout canal.
-   Interne: DB config: Suppression clef obsolete 'agressifTraitementAnnonce'.
-   Interne: DB config: 'monitor' => 'ab::monitorId'.
-   Interne: Abeille.class: Le manque de déclaration de 'batteryType' ne permet plus de dire que le device est en écoute.
-   Interne: Nettoyage code obsolete 'SetPermit' + 'xmlhttpMQTTSend.php'.
-   Interne: ZigbeeConst: Mise à jour cluster 0500.

## 220924-BETA-1

-   Page équipement: Amélioration affichage.
-   Tuya vibration sensor TS0210: Mise-à-jour modèle (2452).
-   Syntaxe modele EQ: Ajout possibilité surcharge 'repeatEventManagement'.
-   Syntaxe modele EQ: Ajout possibilité surcharge 'returnStateTime' & 'returnStateValue'.

## 220923-BETA-1

-   Interne: Suppression cmde obsolete 'luminositeHue.json'.
-   Interne: Normalisation de qq icones Philips.
-   Interne: Normalisation de qq icones Iluminize.
-   Tuya vibration sensor TS0210: Ajout support préliminaire (2452).
-   Interne: DB config: Nettoyage clefs obsoletes.
-   Interne: Suppression erreurs PHP sur 'AbeilleEQ-xxx.php'
-   Commandes JSON: Suppression cmde obsolete 'PuissanceLegrandPrise' => 'zb-0B04-ActivePower'.
-   Assistant modèle: Mise-à-jour pour cluster 0500/IAS zone.
-   Assistant modèle: Correction génération 'category'.
-   Interne: DB config: 'preventLQIRequest' => 'ab::preventLQIAutoUpdate'.

## 220922-BETA-1

-   Page EQ/avancé: Affichage code fabricant.
-   Interne: Ajout fabricant dans qq modeles JSON.
-   Livarno Home: Ajout modele préliminaire (2448).
-   Philips SML004: Ajout 'Sensitivity' (2437).
-   Interne: Parser: Support cluster 1000 cmd 41 & 42.
-   Tuya PIR & illuminance: Mise-à-jour du modele (2409).
-   Interne: DB config: 'AbeilleIEEEX' => 'ab::zgIeeeAddrX'
-   Interne: DB config: 'AbeilleIEEE_OkX' => 'ab::zgIeeeAddrOkX'
-   Interne: Parser: isDuplicated() timeout = 2sec au lieu de 10sec.
-   Tuya PIR+illuminance: Mise-à-jour modèle pour 'Illuminance' (2409).

## 220916-BETA-1

-   Interne: Liste des 'end points' enregistrée dans DB eqLogic.
-   Interne: 'manufCode' enregistré dans DB eqLogic.
-   Page EQ/avancé: Ajout possibilité d'envoyer une 'Node descriptor request'.

## 220916-STABLE-1

.. important:: Les zigates v2 doivent être à jour du dernier firmware disponible (v3.21 à ce jour).
.. important:: Pour les zigates v1, l'équipe Zigate recommande FORTEMENT d'utiliser un firmware **Optimized PDM** (OPDM) dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

.. important:: Les zigates v1 doivent avoir un firmware >= 3.1e pour un fonctionnement optimal mais la dernière en date (3.21) est fortement recommandée.

## 220914-BETA-1

-   Philips SML003 motion sensor: Support préliminaire (2440).
-   Tuya smart plug: Support préliminaire (2443).
-   Interne: Normalisation du nom de qq icones.
-   Tuya iHSW02/WHD02 mini smart plug: Ajout modele (2438).
-   Electrovanne Saswell SAS980SWT: Correction modele (2388).
-   Silvercrest motion sensor: Support préliminaire (2445).

## 220906-BETA-1

-   Moes smart dimmer MS105Z: Mise-à-jour modèle pour partie dimmer (2363).
-   Interne: Parser: Ajout info msg 8139.
-   Interne: Abeille.class: Correction mise-à-jour cmde info (duplicate entry).
-   Thermostat Schneider Wiser: Support préliminaire (2436).
-   Philips HUE Smart plug LOM008: Mise-à-jour du modèle (2431).
-   Interne: Suppression ancienne syntaxe 'tuyaEF00' dans modèles JSON.
-   OSRAM Classic A60 TW: Support préliminaire (2435).
-   OSRAM Classic B40 TW: Mise-à-jour modèle (2023).
-   Philips SML004: Support préliminaire (2437).
-   Assistant modèle EQ: Ajout support cluster 0406 (Occupancy) + amélioration 0400.
-   Interne: Parser: Cluster 0406 supporté par decode8002() et non plus 8102().

## 220901-BETA-1

-   Interne: Suppression queue obsolete 'parserToAbeille'.
-   Interne: Optimisation queues 'xmlToAbeille'/'cmdToAbeille'/'abeilleToAbeille' => 'xToAbeille'.
-   Page config: Test de port: Amélioration mineure.
-   Interne: Optimisation queues dans deamon(): 'parserToAbeille2' => 'xToAbeille'.
-   Page santé: Affichage du type d'équipement au lieu de son icone.
-   Interne: Ajout type 'Zigate' à l'équipement 'Ruche'.
-   Interne: Format JSON eq: Mise-à-jour 'Identify' & 'Groups'.
-   Interne: Support Tuya: Amélioration 'transId' + 'setPercent1000'.

## 220829-BETA-1

-   Page maintenance: Récupération fantomes préliminaire, pour les eq sur secteur (mode dev).
-   Philips HUE Smart plug LOM008: Support préliminaire (2431).
-   Interne: Nettoyage images: 'LOM001'/'LOM002' => 'PhilipsSignify-Plug'

## 220824-BETA-1

-   Interne: Mise-à-jour page maintenance.
-   Aeotec Multi purpose sensor: Mise-à-jour du modèle pour 'vibration' (2376).
-   Page support: Remplacée par page 'maintenance' + améliorations.
-   Volet Profalux: Ajout cmde info 'Not Closed' (2429).
-   Ikea on/off switch: Correction modele pour batterie à mi valeur (2056).
-   JSON équipement: Ajout possibilité surcharge 'calculValueOffset'.
-   Interne: 'AbeilleLQI_MapDataAbeilleX.json.lock' => 'AbeilleLQI-AbeilleX.json.lock'.
-   Interne: Arret generation ancien format 'AbeilleLQI_MapDataAbeilleX.json'.
-   Interne: Recup équipements fantomes.

## 220817-BETA-1

-   Interne: Boutons 'vider' & 'supprimer' page support.
-   Interne: Modifications clefs DB 'config'

    -   'AbeilleActiverX' => 'ab::zgEnabledX'.
    -   'AbeilleTypeX' => 'ab::zgTypeX'.
    -   'AbeilleSerialPortX' => 'ab::zgPortX'.
    -   'IpWifiZigateX' => 'ab::zgIpAddrX'.
    -   'AbeilleParentId' => 'ab::defaultParent'

-   Gledopto GL-C-008P: Mise-à-jour icone.
-   Aubess detecteur de fumée: Ajout support préliminaire (2426).
-   Interne: Parser: Read Attributes Response, correction crash cluster ID 0005.

## 220810-BETA-2

-   Interne: Correction regression DB eqLogic pour 'icone' => 'ab::icon'.
-   Zlinky: Amélioration modèle.

## 220810-BETA-1

-   Orvibo ST30: Correction modèle pour humidité (2193).
-   Page de config: Changements mineurs.
-   Page zigate/avancé: Choix du canal Zigbee amélioré.
-   Page équipement/avancé: Améliorations visuelles mineures.
-   Aeotec Multi purpose sensor: Mise-à-jour du modèle pour 'vibration' (2376).
-   Gledopto GL-C-007P: Support préliminaire.
-   Interne: Nettoyage entrées 'Polling' + 'RefreshData' sur mise-à-jour d'une commande.
-   Zlinky: Mise-à-jour modèle (2418).
-   Interne: msg_send()/msg_receive() avec json_encode()/json_decode() partout.
-   Interne: DB eqLogic, 'icone' => 'ab::icon'.
-   INNR RC250: Support préliminaire (2420).

## 220714-STABLE-1

.. important:: Les zigates v2 doivent être à jour du dernier firmware disponible (3.21 à ce jour).
.. important:: Pour les zigates v1, l'équipe Zigate recommande FORTEMENT d'utiliser un firmware **Optimized PDM** (OPDM) dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

.. important:: Les zigates v1 doivent avoir un firmware >= 3.1e pour un fonctionnement optimal mais la dernière en date (3.21) est fortement recommandée.

## 220713-BETA-1

-   Profalux: Ajout support volet MOT-C1Z06F (2411).
-   Interne: Exclusion de 'resources/archives' des signatures MD5 (2413).

## 220707-BETA-1

-   Interne: Parser: Amélioration msg monitor si équipement Tuya.
-   Ampoule E27 Ledvance white: Mise-à-jour modèle (2400).

## 220628-BETA-1

-   Mhcozy ZG-0005-RF: Ajout support préliminaire (2408).
-   Gledopto GL-C-008P: Ajout support préliminaire (2402).
-   Tuya PIR+illuminance: Ajout support préliminaire (2409).

## 220625-BETA-1

-   Interne: Parser: Fix mineur msg debug.
-   Modèles d'équipements: Possibilité de surcharger 'historizeRound'.
-   Interne: Parser: Ajout support msg '8001/Log message'.

## 220622-BETA-1

-   Interne: 'Device Announce' filtré pour Zigate v2 seulement (2404).

## 220619-STABLE-1

-   Interne: Support Tuya amélioré (ajout 'rcvValueMult').
-   Tuya TV02: Mise-à-jour du modèle.
-   Page EQ: Suppression des boutons 'Recharger' & 'Reconfigurer' pour ne garder que 'Reinitialiser'.
-   Ampoule E27 Ledvance couleur: Ajout support préliminaire (2400).
-   Smart Air Box: Modèle revu pour utilisation commandes internes génériques (2329).

## 220606-BETA-1

-   Interne: Parser: Amélioration mineure.
-   Nom d'un nouvel équipement = type issu du modèle + Jeedom ID (ex: 'Tuya smoke sensor - 12') (2393).
-   Interne: Support Tuya amélioré.
-   Moes smart dimmer MS105Z: Mise-à-jour modèle pour partie dimmer (2363).
-   Interne: Ajout support préliminaire 'usbreset'.
-   Blitzwolf SHP13: Ajout support signature TS011F \_TZ3000_amdymr7l (2396).

## 220531-BETA-1

-   Interne: Suppression code obsolete (xmlhttpConfChange).
-   Page santé: Amélioration mineure.
-   Mise-à-jour OTA: Amélioration mineure & correction pour support FW Legrand.
-   Electrovanne Saswell SAS980SWT: Support préliminaire (2388).
-   Ikea Tredanson rideau occultant: Ajout support préliminaire (2392).
-   Nom d'un nouvel équipement = type issu du modèle + Jeedom ID (ex: 'Tuya smoke sensor - 12') (2393).
-   Réseau: Changement visuel mineur table des liens + utilisation 'AbeilleLQI-AbeilleX.json'.

## 220518-BETA-1

-   E27 RGB Eglo/Awox (id = TLSR82xx, AwoX): Mise-à-jour du modèle (2384).
-   Images: Nettoyage & standardisation des noms (ex: node_Generic-BulbXXX.png).
-   Page EQ/avancé: Ajout possibilité de changer la couleur (cluster 0300, move to color).
-   Interne: Support Tuya amélioré pour plus de flexibilité.
-   Moes smart dimmer MS105Z: Mise-à-jour modèle (2363).
-   Aeotec Multi purpose sensor: Mise-à-jour du modèle (2376).
-   Page des équipements: Affichage grisé si équipement désactivé.
-   Support OTA: Correction regression.
-   Page EQ/commandes: Amélioration mineure (2178).

## 220515-BETA-1

-   Legrand Cable outlet: Mise-à-jour du modèle (850).
-   Interne: Cmd: Revue 'commandLegrand'.
-   Moes smart dimmer MS105Z: Mise-à-jour modèle (2363).
-   Nom d'un nouvel équipement = type issu du modèle (ex: 'Tuya smoke sensor') plutot que 'AbeilleX-Y'.
-   Interne: Corrections utilisation obsolete de 'RxOnWhenIdle'.
-   E27 RGB Eglo/Awox (id = TLSR82xx, AwoX): Ajout support préliminaire (2384).
-   Lidl Dimmable HG07878C: Ajout support préliminaire (2383).
-   Interne: Parser: Support revu pour 8002/'configure reporting response'. 8120 n'est plus utilisé.
-   Interne: Constantes zigbee. Ajout clusters privés EF00, FC01 & FC40.
-   Images: Nettoyage & standardisation des noms (ex: node_Generic-BulbXXX.png).
-   Interne: Abeille.class: Optimisation.
-   Interne: AbeilleCmd: Ajout support 'manufId' pour 'configureReporting'.
-   Page EQ/avancé: 'Configure reporting': Ajout support code fabricant (manufId).
-   Page EQ/avancé: Affichage des groupes Zigbee auxquels l'équipement appartient (1713).
-   Woox controleur d'arrosage: Ajout support préliminaire (2385).
-   Interne: Parser: FC01/FC02 supporté par decode8002.
-   Interne: AbeilleCmd: Correction readAttribute() pour 'manufId' renseigné.
-   Page EQ/avancé: 'Read attribute': Ajout support code fabricant (manufId).

## 220509-BETA-1

-   Tuya smoke detector: Support préliminaire (2380).
-   Heiman COSensor EF-3.0: Mise-à-jour modèle (2312).
-   Interne: Parser: Support cmd 01 générée par cluster 0500 (#EP#-0500-cmd01).
-   Aeotec Multi purpose sensor: Mise-à-jour modèle pour vibration (2376).
-   Nettoyage cmdes JSON obsolètes:

    -   'etatSwitchLivolo' => 'zb-0006-OnOff'
    -   'etatVolet' => 'zb-0006-OnOff'

-   Interne: Nettoyage partiel du répertoire 'Network'.
-   Interne: Nettoyage 'Abeille.class'.
-   Reseau/bruit: Corrections.
-   Interne: Optimisation AbeilleCmd autour de 'managementNetworkUpdateRequest'.
-   Interne: Parser: Support type 'array'.
-   Interne: Parser: Decode 'write attribute response' pour cluster 'private'.

## 220428-BETA-1

-   Interne: check_json: Améliorations.
-   Aeotec Multi purpose sensor: Mise-à-jour modèle (2376) & correction cmde 'zb-0500-ZoneStatus'.
-   Modèles de commandes JSON: Mise-à-jour cosmetique.
-   Interne: Ajout date derniere mise-à-jour à partir du modèle (ab::eqModel['lastUpdate']).
-   Interne: Parser: Optimisation lecture DB 'config'.
-   Interne: Parser: Mise-à-jour support cluster 0005/scenes (peut etre cassé).
-   Interne: Parser: Corrections regressions.
-   Owon multi-sensor THS317-ET: Ajout support.
-   Xiaomi sqare sensor: Mise-à-jour modèle pour ne garder qu'une info 'Pressure' = '0403-01-0000' (2370).
-   Moes smart dimmer MS105Z: Mise-à-jour modèle (2363).
-   Interne: Parser: Correction 'read attribute' pour 'Time cluster'.

## 220425-BETA-1

-   Interne: Mise-à-jour DB eqLogic

    -   'ab::jsonId' + 'ab::jsonLocation' => 'ab::eqModel['id'/'location]'
    -   'MACCapa' => 'ab::zigbee['macCapa']'
    -   'RxOnWhenIdle' => 'ab::zigbee['rxOnWhenIdle']'
    -   'AC_Power' => 'ab::zigbee['mainsPowered']'

-   Interne: Parser: decode8002() monitoring migré en fin de fonction.
-   Interne: Parser: Suppression fonction obsolete msgToAbeille().
-   Page EQ/avancé: Correction regressions.
-   Aeotec Multi purpose sensor: Ajout support préliminaire (2376).
-   Page EQ/avancé: Corrections 'Réinitialiser'.
-   Assistant EQ/modèle: Améliorations pour clusters 0402, 0405 & 0500.

## 220421-BETA-1

-   Interne: Version DB, date = 20220407.
-   Analyse/santé: Correction affichage ports utilisés.
-   Interne: Nettoyage fonctions obsolètes.
-   Interne: Suppression de plusieurs commandes obsolètes (dispos sur page avancée) 'Ruche':

    -   'replaceEquipement'
    -   'Get Time'
    -   'SystemMessage' (provoque mise à jour erronnée date de communication Zigate)

-   Interne: Page EQ/avancé. Qq optimisations.
-   Interne: Mise-à-jour controle/redémarrage des démons.
-   Zigate v2/apparition équipements inconnus: Ajout verrue (2368).
-   Interne: Plus qu'une seule queue d'entrée au parser.
-   Interne: Tuya: Support préliminaire TV02.
-   Interne: Grosse mise-à-jour pour meilleur support des équipements Tuya.
-   Philips LOM007 smart plug: Ajout support (2374).
-   JSON commandes: 'forceReturnLineAfter/Before' is obsolete. Replaced by 'nextLine' = 'after/before'.
-   Interne: Sauvegarde des infos du modele dans la DB eqLogic => 'ab::eqModel'.
-   Page EQ: Ajout affichage type d'équipement.

## 220407-BETA-1

-   Parser: Amélioration decode routing table.
-   WarningDevice-EL-3.0: Mise-à-jour modèle + merge 'WarningDevice'.
-   SML002: Mise-à-jour modèle (2309).
-   Affichage 'Humidity': Suppression du chiffre apres la virgule.
-   Page EQ/avancé: Amélioration (mineure) affichage modèle
-   Commande interne IAS WD ('cmd-0502') revue pour flash seul.
-   Interne: Constantes Zigbee: Ajout support cluster 0402.
-   Nettoyage cmdes JSON obsolètes:

    -   'etatEpXXout' => 'zb-0006-OnOff' + 'ep=XX'
    -   'etatEpXXin' => 'zb-0006-OnOff' + 'ep=XX'
    -   'etatEp08' => 'zb-0006-OnOff' + 'ep=08'

## 220406-STABLE-1

-   Moes MS105Z: Ajout support préliminaire (2363).
-   Legrand switch 067723: Mise-à-jour modèle (2361).
-   Lidl ampoule livarno lux led gu10 HG08131A: Mise-à-jour modèle (2356).
-   Tuya TS011F\_\_TZ3000_cphmq0q7: Mise-à-jour modèle pour support autres signatures.
-   Page des équipements: Changement icone si non définie.
-   Interne: Mise-à-jour des commandes suite reinclusion/reinit revue.
-   Page équipement: Choix icone revu. Affichage du nom du PNG selectionné et non plus une interprétation de ce que c'est.
-   Interne: Grosse mise-à-jour des commandes, et suppression des 'info' en doublon.
-   Tuya IH-K009: Ajout image.
-   Modèles équipement: Correction regression config reporting 0008-0000 (mauvais type).

## 220329-BETA-2

-   Legrand switch 067723: Mise-à-jour modèle (2361).
-   Page EQ/avancé: Amélioration mineure affichage ID Zigbee.
-   Interne: Amélioration récupération équipements fantomes.
-   Interne: Timeout n'est plus écrasé si réannonce de l'équipement.
-   Package de logs: Masquage de la clef 'api' de la table 'config'.
-   Package de logs: Masquage des URL.

## 220324-BETA-1

-   Network graph: Corrections diverses (1820).
-   Page de gestion: Suppression du 'Changement de zigate' en double.
-   Interne: Cmd 00 cluster 0502/IAS-WD: corrections.
-   Frient smoke alarm (SMSZB-120, frientAS): Mise-à-jour modèle (2242).
-   Interne: Mise-à-jour à partir du modèle revu pour éviter de créer des cmdes orphelines.
-   Interne: Collecte LQI génère nouveau format (AbeilleLQI-AbeilleX.json).
-   Réseau/graph des liens: Revu et utilise nouveau format interne + ajout icone équipement.
-   Interne: Parser: Amélioration filtrage mauvais paquets LQI/804E.
-   Page support: Affiche tout fichier JSON du repertoire temporaire.
-   Interne: network.js => AbeilleNetwork.js

## 220320-BETA-1

-   Interne: Mise-à-jour script de generation de la liste des eq supportés.
-   Interne: Mise-à-jour support Tuya EF00 cmd 01.
-   Lidl ampoule livarno lux led gu10 HG08131A: Ajout support (2356).
-   Interne: Ajout support cmde 00 cluster 0502/IAS WD pour controle sirene.
-   Frient smoke alarm (SMSZB-120, frientAS): Mise-à-jour modele (2242).

## 220316-BETA-1

-   Interne: AbeilleCmdPrepare: nettoyage code obsolete.
-   Interne: AbeilleCmdQueue: Timeout étendu à 3sec.
-   Philips RWL021: Mise-à-jour modèle pour report battery (1243).
-   Evology 4 buttons (3450-Geu_CentraLite): Mise-à-jour modèle (2318).
-   Tuya Smart Air Box: Ajout support préliminaire (2329).
-   Interne: Parser: Suppression car '/' pour identifiant fabricant (ex 'frient A/S', 2242).
-   Correction DB pour erreur getPlugVAW, mauvaise taille d'attribut (508 au lieu de 0508).
-   Interne: Ajout support cluster EF00/cmd 02 pour Smart Air Box.
-   Frient smoke alarm (SMSZB-120, frientAS): Ajout support (2242).
-   JSON équipements: Suppression mots clef obsoletes: 'lastCommunicationTimeOut' & 'type'
-   Interne: AbeilleCmd: Akout support cmd 00 pour cluster 0502/IAS WD.
-   Interne: Code specifique Tuya isolé.
-   Interne: Qq fixes.

## 220310-BETA-3

-   Interne: SerialRead: Suppression warning fopen().
-   JSON équipements: Suppression cmdes obsoletes

    -   'etatLight' => 'zb-0006-OnOff'
    -   'WindowsCoveringUp' => 'zbCmd-0102-UpOpen'
    -   'WindowsCoveringDown' => 'zbCmd-0102-DownClose'
    -   'WindowsCoveringStop' => 'zbCmd-0102-Stop'

-   Philips RWL021: Mise-à-jour modèle (1243).
-   Evology 4 buttons (3450-Geu_CentraLite): Ajout support (2318).
-   Interne: Parser: Clust 0007 supporté par decode8002().
-   Tradfri GU10 340lm White, LED2005R5: Ajout support (2344).
-   Ampoule Lexman Gu10 460lm (ZBEK-4, Adeo): Ajout support (2348).
-   Interne: AbeilleCmd: Corrections & améliorations.
-   Page EQ/avancé: Configurer le reporting: Ajout type attribut.

## 220307-BETA-1

-   Interne: AbeilleCmd: Mises-à-jour, corrections, améliorations (dont vitesse) & nettoyage.
-   Interne: AbeilleCmd: Gestion mode ACK etendu aux commandes internes suivantes:

    -   bind0030
    -   configureReporting
    -   getActiveEnpoints
    -   writeAttibute
    -   writeAttribute0530
    -   sendReadAttributesResponse
    -   readReportingConfig

-   Legrand double switch (NLIS-Doublelightswitch_Legrand): Ajout support (2343).
-   Interne: AbeilleCmd: Suppression cmds obsoletes 'ReadAttributeRequestXX'.
-   JSON équipements: Suppression cmdes obsoletes

    -   'getBattery' => 'readAttribute' + 'attrId=0021'
    -   'getBatteryVolt' => 'readAttribute' + 'attrId=0020'
    -   'getPlugA' => 'poll-0B04-0508'
    -   'getPlugPower' => 'poll-0B04-050B'
    -   'getPlugV' => 'poll-0B04-0505'

-   Interne: AbeilleCmd: Correction génération SQN pour cmds 0530.
-   Liste compatibilité: Correction pour suppression affichage 'discovery'.
-   Mise à jour OTA: Correction queue.
-   Hue outdoor motion sensor SML002: Mise-à-jour modèle (2309).
-   Volets Profalux: Correction types génériques pour appli mobiles.
-   Analyse équipements/niveau batterie: Correction regression (2345).
-   Interne: Parser->Abeille: optimisation msg

    -   attributeReport => attributesReportN.
    -   reportAttributes => attributesReportN.
    -   readAttributesResponse => readAttributesResponseN.

## 220228-BETA-2

-   Remplacement d'équipements: Nouvelle mise-à-jour + doc (2337).
-   Interne: Parser: Certains messages dupliqués sont ignorés.
-   Récupération des fantômes: Amélioration.
-   Interne: Migration codes obsoletes vers 'archives': LqiStorage.x, RouteRecord.x, Jennic binary.
-   Mini smart socket (TS011F\_\_TZ3000_5f43h46b): Mise-à-jour modèle (2334).
-   Interne: Amélioration analyse réseau (collecte LQI).
-   Remplacement d'équipements: Correction fonctionalité (2337).

## 220223-BETA-1

-   Interne: Parser: Correction erreur PHP decode8043().
-   Moes 4 boutons, scene switch, TS004F\_\_TZ3000_xabckq1v: Mise-à-jour modèle (2278).
-   Mini smart socket (TS011F\_\_TZ3000_5f43h46b): Ajout support préliminaire (2334).
-   JSON équipements: Suppression cmdes obsoletes

    -   'etatSW1', 'etatSW2', 'etatSW3'
    -   'etatSwitch'
    -   'etatSwitchKonke'

-   JSON équipements: Mise-à-jour 'zb-0702-CurrentSummationDelivered'.
-   Aqara TVOC moniteur d'air AAQS-S01 (airmonitor.acn01): Mise-à-jour modele (2279).

## 220223-STABLE-1

-   Page EQ/avancé: Ajout bouton 'leave request'.
-   JSON commandes: Remplacement 'ReadAttributeRequest' => 'readAttribute'.
-   Interne: AbeilleCmd/readAttribute(): Ajout support 'manufId'.
-   Tuya capteur rond temp & humidité (TS0201\_\_TZ3000_dowj6gyi): Ajout support.
-   Ikea E27 bulb (TRADFRIbulbE27CWS806lm): Ajout support (2328).
-   Migration d'équipements: Mise-à-jour séquence + ajout doc.
-   Réseau/routes: Correction regression fonctionnement.

## 220215-BETA-1

-   Interne: Amélioration msg debug.
-   Interne: Changement gestion cas nouvelle zigate/échange de port.
-   Interne: Lecture version zigate (bouton tester) améliorée.
-   Mise-à-jour FW zigate: Effacement automatique PDM si passage 'legacy' vers 'OPDM'.
-   Page gestion: Mise-à-jour remplacement de zigate suite Jeedom 2.4.X.
-   Page zigate/avancé: Selection du canal/masque revue (1683).
-   Interne: AbeilleCmd: setChannelMask => setZgChannelMask + améliorations.

## 220211-BETA-1

-   Profalux v2: Amélioration support.
-   Auto-découverte équipement inconnu: Correction format json & améliorations.
-   Lexman E27 RGB bulb: Ajout support préliminaire (2295).
-   Heiman COSensor EF-3.0: Ajout support (2312).
-   Suppression des repertoires vides au démarrage dans 'devices_local'.
-   Erreur sur 'exclusion' d'équipement: Nouvelle correction (2305)
-   Interne: SerialRead: Message corrompu (err CRC) n'est plus transmis au parser.
-   Page équipements/migration: Revu & corrigé pour Jeedom 4.2.x (2322).

## 220206-BETA-1

-   Erreur sur 'exclusion' d'équipement: Correction (2305)
-   Interne: AbeilleSerialRead: msg erreurs masqués (2306).
-   Regression controle de 'level' (setLevel): Corrrection (1994).

## 220204-BETA-1

-   Interne: Correction erreur 'prepareCmd(): Mauvais format de message' (2302).
-   Aucun équipement sélectionné: correction (2305).

## 220202-BETA-1

-   Page config: Changement mineur. Type 'WIFI' => 'WIFI/ETH'.
-   Page config: Liste des ports revue + info 'Orange Pi Zero'.
-   Aqara TVOC moniteur d'air AAQS-S01: Mise-à-jour modèle (2279).
-   Assistant JSON: mise-à-jour.
-   Modèle commande JSON: 'getPlugVAW' => 'poll-0B04-0505-0508-050B'.
-   Interne: AbeilleCmd: Message debug & améliorations controle de flux envoie.
-   Message d'erreur remonté à l'utilisateur si erreur dans log.
-   Page gestion: Controle des groupes revu suite core 2.4.7 (2284).
-   Legrand 20AX: Mise-à-jour modèle (2213).
-   Interne: Correction AbeilleTools sendMessageToRuche().
-   Interne: SerialRead: Suppression mess d'err sur première trame corrompue.
-   Mauvaise taille de modale parfois: correction (2177).

## 220130-BETA-1

-   LivarnoLux applique murale HG06701: Correction modèle (2256).
-   Blitzwolf SHP15: Support preliminaire (2277).
-   Assistant EQ/JSON: Update.
-   Interne: AbeilleCmd: Correction priorité getActiveEndpoints.
-   Interne: Parser: Interrogation de tous les EP pour support des eq qui s'identifient via un EP different du premier.
-   Interne: Nettoyage config cmdes 'PollingOnCmdChange' & 'PollingOnCmdChangeDelay' lors mise-à-jour équipement.
-   Interne: AbeilleCmd: Suppression 'Management_LQI_request' obsolete.
-   Tuya 4 buttons (TS004F\_\_TZ3000_xabckq1v): Mise-à-jour modèle (2155).
-   Aqara TVOC moniteur d'air AAQS-S01: Mise-à-jour modèle (2279).
-   Modeles commandes (JSON): modifications syntaxe

    -   'unite' obsolete => 'unit'
    -   'generic_type' obsolete => 'genericType'
    -   'trig' obsolete => 'trigOut'
    -   'trigOffset' obsolete => 'trigOutOffset'

-   Modèles équipements (JSON): améliorations

    -   Surcharge possible de 'logicalId'
    -   Surcharge possible de 'trigOut'
    -   Surcharge possible de 'trigOutOffset'
    -   Surcharge possible de 'invertBinary'

-   Interne: DB eqLogic, config, ab::trig ou trigOffset => ab::trigOut ou trigOutOffset.
-   Xiaomi Aqara MCCGQ14LM (magnet.acn001): Correction modèle (2257).
-   Interne: checkGpio() revu pour suppression faux message 'PiZigate inutilisable'.
-   Page de config: Ajout bouton vers doc & doc préliminaire correspondante.
-   Page de config: Bouton 'activer' renommé en 'libérer'. Trompeur. N'active pas la zigate.
-   Xiaomi door: Correction etat inversé (regression 220110-BETA-1).
-   Interne: CmdQueue: erreur si message trop gros dans queue 'ParserToCmdAck'.
-   Interne: AbeilleCmd: Correction regression suite mise-à-jour 'setLevel'.
-   Tuya GU10 color bulb (TS0505B\_\_TZ3210_it1u8ahz): Ajout support (2280).

## 220123-BETA-1

-   Gledopto GU10 buld GL-S-007Z: Ajout support préliminaire (2270).
-   Interne: AbeilleCmd: SimpleDescriptorRequest => getSimpleDescriptor.
-   Page EQ/avancé: Ajout support 'Simple descriptor request'.
-   Interne: AbeilleCmd: IEEE_Address_request => getIeeeAddress.
-   Equipement sur secteur en time-out: Correction.
-   Interne: Correction msg debug 'IEEE addr mismatch' au démarrage.
-   Orvibo CM10ZW: Support signature alternative (2275).
-   Interne: AbeilleCmd: Correction pour espace dans valeur slider.
-   Interne: AbeilleCmd: Suppression prepare 'setLevel'.

## 220122-BETA-1

-   Interne: format message queues vers AbeilleCmd modifié.
-   Interne: Fusion de plusieurs queues vers AbeilleCmd.
-   Erreur getLevel/getEtat inattendue: Correction (2239).
-   Xiaomi Aqara MCCGQ14LM (magnet.acn001): Correction modèle (2257).
-   Interne: Parser vers Abeille. Attributs groupés pour optimisation.
-   Interne: Qq améliorations page EQ/avancé/Zigate.
-   Page de config: Amélioration messages mise-à-jour FW.
-   Page support/infos clefs: Affichage revu.
-   Interne: Parser: Optimisations & nettoyage.
-   Interne: Queues revues.
-   Page EQ/avancé: possibilité de télécharger discovery 'automatique'.
-   Interne: Abeille.class: Vérification de l'état des queues amélioré.
-   Xiaomi H1 double rocker: Mise-à-jour modèle + image (2253).
-   Interne: Abeille.class: Suppression interrogateUnknowNE().
-   Page EQ/avancé: Correction regression bouton "Réinitialiser".
-   Page EQ/avancé: Réinit 'defaultUnknown' si modèle officiel existe.
-   Interne: Commande 'setColor' (cluster 0300) revue.

## 220114-BETA-1

-   Interne: Ajout support cmd 00/Setpoint, cluster 0201/thermostat.
-   Acova Alcantara: Mise à jour modele pour controle temp (2180).
-   'Graph' visible seulement en mode dev.
-   Interne: Gestion des queues: log & suppression msg trop gros. A completer.
-   Interne: Gestion des queues en cas de msg trop gros.

## 220113-BETA-1

-   Xiaomi Aqara wall switch D1 (switch.b1nacn02): Mise-à-jour modèle (2262).
-   Profalux Zoe: Identifiant 'TG1' = 'TS' (1066).
-   Réseau/bruit: fonctionalité masquée sauf mode dev.
-   Interne: Parser: 8401/IAS zone status change revisité.
-   RH3040 PIR sensor: Mise-à-jour modèle (2252).
-   Gledopto GL-SD-001 AC dimmer: Ajout support (2258).
-   Tuya télécommande 4 boutons (TS0044): Ajout support (2251).

## 220110-BETA-1

-   Interne: Début refonte/nettoyage AbeilleCmd pour amélioration controle de flux.
-   Interne: Parser: Support nPDU/aPDU sur messages 8000/8012 & 8702 (FW>=3.1e).
-   Interne: Cmd: Ajout support optionnel 'manufId' pour 'writeAttribute'.
-   Page EQ/avancé: Ecriture attribut améliorée. Ajout support 'direction' & 'manufId'.
-   Xiaomi H1 double rocker: Ajout support (2253).
-   JSON équipements: Nettoyage commandes obsolètes

    -   'etat' => 'zb-0006-OnOff'
    -   'etatCharge0' => 'zb-0006-OnOff' + 'ep=01'
    -   'etatCharge1' => 'zb-0006-OnOff' + 'ep=02'
    -   'etatCharge2' => 'zb-0006-OnOff' + 'ep=03'
    -   'etatCharge6' => 'zb-0006-OnOff' + 'ep=07'
    -   Ajout surcharge de 'genericType'
    -   'etatInter0' => 'zb-0006-OnOff' + 'ep=01'
    -   'etatInter1' => 'zb-0006-OnOff' + 'ep=02'
    -   'etatInter2' => 'zb-0006-OnOff' + 'ep=03'
    -   'etatDoor' => 'zb-0006-OnOff'

-   TRADFRIbulbE14WScandleopal470lm LED1949C5: Mise-à-jour modèle (2250).
-   Interne: AbeilleCmd: Suppression prepare readReportingConfig() + getBindingTable().
-   Package support: ajout du log 'update'.
-   LivarnoLux applique murale HG06701, TS0505A, \_TZ3000_5bsf8vaj: Ajout support preliminaire (2256).
-   Assistant modèle JSON: Améliorations.
-   Interne: Abeille.class: Nettoyage fonctionalités obsolètes.
-   Xiaomi Aqara MCCGQ14LM (magnet.acn001): Ajout support préliminaire (2257).
-   Lidl HG07878A TS0502A: Correction modèle (2198).
-   Interne: Suppression des cmdes Ruche obsolètes au démarrage des démons.
-   QS-zigbee-C01 nouvelle version: ajout support (2260).
-   Xiaomi Aqara wall switch (switch.b1nacn02): Ajout support (2262).

## 220108-STABLE-1

-   Tuya TV02: Ajout image (2175).
-   JSON équipements: Correction support params optionnels.
-   TRADFRIbulbE14WScandleopal470lm LED1949C5: Ajout support (2250).
-   Tuya RH3040 PIR: Ajout support (2252).
-   ZBMini: Ajout polling toutes les 15mins pour vérifier toujours en vie.
-   Sixwgh WH025/TS011F\_\_TZ3000_cphmq0q7: Ajout polling 0006 + 0702 (2211).
-   Interne: Gestion 'PollingOnCmdChange' revue.
-   Interne + page EQ/avancé: Ajout support writeAttribute via cmd 0530.
-   Page de config: Affichage version connue du firmware.
-   Page EQ/avancé: Affichage version complète FW (ex: 0004-0320).

## 211214-BETA-3

-   dOOwifi DWF-0205ZB-PN-2: Ajout PNG (2241).
-   JSON équipements: Nettoyage commandes obsolètes

    -   'spiritSetReportBatterie' => 'zbConfigureReporting' + 'clustId=0001&attrType=20&attrId=0021'
    -   'setReportIlluminance' => 'zbConfigureReporting' + 'clustId=0400&attrType=21&attrId=0000'
    -   'setReportTemperature' => 'zbConfigureReporting' + 'clustId=0402&attrType=29&attrId=0000'
    -   'setReportOccupancy' => 'zbConfigureReporting' + 'clustId=0406&attrType=18&attrId=0000'

-   QS-Zigbee-C01: Correction modele pour cmde 'Position'.
-   Ajout support Module volet Roulant dOOwifi DWF-0205ZB-PN-2 (2241).
-   Firmware: Ajout version 3.21 OPDM+legacy. Suppression versions antérieures à 3.1d.
-   Xiaomi Aqara QBKG26LM: Mise-à-jour modèle (2174).

## 211210-BETA-1

-   Réseau Abeille/routes: Correction erreur si équipement sans parent.
-   Support: Mise-à-jour infos clefs.
-   JSON équipements

    -   Fin de support noms obsoletes: nameJeedom/Categorie/icone/battery_type/Commandes.
    -   Support surcharge de parametres optionnels.

## 211209-BETA-1

-   Interne: Création/mise-à-jour ruche revue.
-   Interne: Suppression mode 'hybride' forcé.
-   Message si FW plus vieux que 3.1D (nécessaire pour certains équipements).

## 211208-BETA-2

-   Ruche: page équipement/avancé: Correction regression bouton 'setMode'.
-   SPLZB-131: RMSVoltage, reporting si variation >= 2V (2109).
-   Xiaomi Aqara SSM-U01: Ajout support 'ActivePower' (2234).
-   JSON équipements: Nettoyage commandes obsolètes

    -   'setReportBatterie' => 'zbConfigureReporting' + 'clustId=0001&attrType=20&attrId=0021'
    -   'setReportBatterieVolt' => 'zbConfigureReporting' + 'clustId=0001&attrType=20&attrId=0020'
    -   'setReportEtat' => 'zbConfigureReporting' + 'clustId=0006&attrType=10&attrId=0000'
    -   'setReportLevel' => 'zbConfigureReporting' + 'clustId=0008&attrType=10&attrId=0000'
    -   'setReportCurrent_Position_Lift_Percentage' => 'zbConfigureReporting' + 'clustId=0102&attrType=10&attrId=0008'
    -   'setReportHumidity' => 'zbConfigureReporting' + 'clustId=0405&attrType=20&attrId=0000'

-   Récupération équipements fantomes (toujours sur le réseau mais plus dans Jeedom): Améliorations.

## 211208-BETA-1

-   Interne: AbeilleDebug.log déplacé dans répertoire temporaire Jeedom.
-   Support: Generation infos clefs pour support à la création du package.
-   Identification équipement: Interrogation EP01 en plus du premier.
-   Sonoff S26R2ZB: Ajout support (2221).

## 211207-BETA-3

-   Acova Alcantara: Version temporaire 'Set-OccupiedHeatingPoint' (2180).
-   Tuya/Sixwgh TS011F\_\_TZ3000_cphmq0q7: Cluster 0B04 migré en polling (2211).

## 211207-BETA-2

-   Interne: Amélioration création ruche vs démarrage. Mode forcé en 'hybride' qq soit FW.
-   Philips wall switch module/RDM001: Mise-à-jour modèle & support cluster FC00 (2185).
-   JSON équipements: Nettoyage commandes obsolètes

    -   'BindToPowerConfig' => 'zbBindToZigate' + 'clustId=0001'
    -   'BindToZigateTemperature' => 'zbBindToZigate' + 'clustId=0402'
    -   'BindToZigateRadiateur' => 'zbBindToZigate' + 'clustId=0201'
    -   'BindToZigateEtatLegrand' => 'zbBindToZigate' + 'clustId=FC41'
    -   'BindToZigatePuissanceLegrand' => 'zbBindToZigate' + 'clustId=0B04'
    -   'BindToZigateLightColor' => 'zbBindToZigate' + 'clustId=0300'
    -   'BindToZigateOccupancy' => 'zbBindToZigate' + 'clustId=0406'
    -   'BindToZigateCurrent_Position_Lift_Percentage' => 'zbBindToZigate' + 'clustId=0102'
    -   'BindShortToSmokeHeiman' => 'zbBindToZigate' + 'clustId=0500'
    -   'BindShortToZigateBatterie' => 'zbBindToZigate' + 'clustId=0001'

-   Interne: AbeilleCmd: Traitement status 8000 groupé + ...
-   Xiaomi Aqara SSM-U01: Ajout support (2227).
-   Interne: AbeilleCmd: Ajout support cmd 0201/Thermostat.
-   Interne: AbeilleCmd: writeAttribute(): Correction direction.
-   Interne: Parser: Requetes lecture attributs groupées lors d'une annonce.
-   Effacement PDM: Correction regression interne.

## 211205-BETA-1

-   Orvibo ST30: Mise-à-jour modèle + icone (2193).
-   Tuya/Sixwgh TS011F\_\_TZ3000_cphmq0q7: Mise-à-jour modèle + icone (2211).
-   Récupération équipements fantomes (toujours sur le réseau mais plus dans Jeedom): Mise-à-jour
-   Aqara Smart Wall Switch H1 EU (No Neutral, Double Rocker) (WS-EUK02): Support préliminaire (2224).

## 211205-STABLE-1

-   Page EQ/avancé: Ajout bouton reset SW zigate (2176).
-   Appairage équipement: correction regression.

## 211202-BETA-1

-   Récupération équipements fantomes (toujours sur le réseau mais plus dans Jeedom): Partiel.
-   Tuya/Sixwgh TS011F\_\_TZ3000_cphmq0q7: Ajout support (2211).
-   Page EQ/avancé: Ajout bouton récupération adresse IEEE.
-   Message si mode debug et moins de 5000 lignes de log.

## 211130-BETA-2

-   Dimmer-Switch-ZB3.0_HZC: Mise-à-jour reporting CurrentLevel (2200).
-   Philips wall switch module/RDM001: Mise-à-jour modèle (2185).
-   Zigate WIFI: Amélioration serial read pour meilleur support coupures de connexion.
-   Interne: AbeilleCmd: Nouveau support #slider# appliqué à 'writeAttibute'.

## 211129-BETA-2

-   Interne: Zigbee const: Ajout cluster 0406.
-   Dimmer-Switch-ZB3.0_HZC: Ajout image PNG (2200).
-   Interne: Zigbee const: Mise à jour attributs cluster 0300.
-   Livarno HG07834C E27 bulb: Ajout support préliminaire (2203).
-   Profalux MAI-ZTS: Ajout support telecommande gen 2 (2205).
-   Profalux volets 2nd gen: Meme config pour MOT-C1Z06C & MOT-C1Z10C.
-   JSON équipements: Nettoyage commandes obsolètes

    -   'xxxxK' => 'zbCmd-0300-MoveToColorTemp'
    -   'dateCode' => cmde supprimée
    -   'BasicApplicationVersion' => cmde supprimée
    -   'Rouge' => 'zbCmd-0300-MoveToColor'
    -   'Blanc' => 'zbCmd-0300-MoveToColor'
    -   'Bleu' => 'zbCmd-0300-MoveToColor'
    -   'Vert' => 'zbCmd-0300-MoveToColor'

## 211126-BETA-2

-   Interne: Améliorations assistant JSON.
-   JSON équipements: Nettoyage commandes obsoletes

    -   'colorX' => 'zb-0300-CurrentX'
    -   'colorY' => 'zb-0300-CurrentY'
    -   'location' => cmde supprimée
    -   'Get-ColorX' => 'zbReadAttribute' + 'clustId=0300&attrId=0003'
    -   'Get-ColorY' => 'zbReadAttribute' + 'clustId=0300&attrId=0004'
    -   'Level' => 'zb-0008-CurrentLevel'

-   Interne: Parser: Data type 30/enum8 décodé comme nombre au lieu de string hex.
-   Port interne Zigate Wifi déplacé de /dev/zigateX => /tmp/zigateWifiX pour contourner pb de "read-only file system".

## 211125-BETA-1

-   Assistant de découverte: Texte de rappel si batterie.
-   Tuya RH3001 door sensor: Mise-à-jour JSON (1226).
-   Lidl HG07878A TS0502A: Ajout support préliminaire (2198).
-   JSON équipements: Nettoyage commandes obsoletes

    -   'BindToZigateEtat' => 'zbBindToZigate'
    -   'BindToZigateLevel' => 'zbBindToZigate'
    -   'BindToZigateButton' => 'zbBindToZigate'
    -   'BindToZigateIlluminance' => 'zbBindToZigate'
    -   'levelLight' => 'zb-0008-CurrentLevel'
    -   'getLevel' => 'zbReadAttribute' + 'clustId=0008&attrId=0000'

-   Démarrage sans Zigate active: Ajout message + démarrage démons annulé.
-   Page de config: Zigate Wifi: Correction message 'Port série de la zigate X INVALIDE ! Zigate désactivée'.
-   Tuya TS0501B Led controller: Ajout support préliminaire (2199).
-   Dimmer-Switch-ZB3.0_HZC: Support préliminaire (2200).

## 211122-BETA-1

-   Illuminance: Correction cmde JSON 'zb-0400-MeasuredValue.json'.
-   Mise-à-jour OTA: Support préliminaire.
-   zb-0400/0402/0405-MeasuredValue.json: Correction calcul valeur.
-   Philips Hue Wall switch: Ajout support préliminaire (2185).
-   Equipements inconnus: Generation d'un "discovery.json" pendant l'inclusion. Suppression d'AbeilleDiscover.log.
-   Programmateur Zigate: Correction: Compilation echoue si "tmp" n'existe pas.
-   Orvibo ST30: Ajout support préliminaire (2193).
-   Acova Alcantara: Mise-à-jour JSON pour 'Set-OccupiedHeatingPoint' (2180).
-   JSON équipements: Nettoyage commandes obsoletes

    -   'temperature' => 'zb-0402-MeasuredValue'
    -   'bindToZigate' => 'zbBindToZigate'
    -   'luminositeXiaomi' => 'zb-0400-MeasuredValue'
    -   'getEtat' => 'zbReadAttribute'
    -   'humidite' => 'zb-0405-MeasuredValue'
    -   'on' => 'zbCmd-0006-On'
    -   'off' => 'zbCmd-0006-Off'

-   JSON équipements: Ajout possibilité de surcharger 'minValue' & 'maxValue' pour widget slider.
-   1 chan switch module (TS0011, \_TZ3000_ji4araar): Ajout JSON sur base TS0011 (2196).

## 211121-STABLE-1

-   Acova Alcantara: Ajout support préliminaire (2180).
-   Interne: Nettoyage AbeilleZigateConst.
-   Interne: Correction CmdPrepare/WriteAttributeRequestGeneric. Impacte Danfoss Ally (1881).
-   Ikea bulb E27 White Spectre opal 1055lm: Ajout support (2187).
-   Moes ZSS-ZK-THL-C: Ajout support (2191).

## 211115-BETA-2

-   Moniteur: Suppression message sur équipement inexistant (2186).
-   Moniteur: Correction lancement démon.

## 211115-BETA-1

-   Page de config: Correction bug écriture impossible adresse Wifi.

## 211107-BETA-1

-   Page Abeilles: Fonctionalité 'scenes' cachée. Scénaris offrent l'équivalent.
-   Identification modèles Tuya: Correction.
-   Interne: AbeilleCmd, bind0030: Supression fonction prepare.
-   Interne: AbeilleCmdPrepare: Correctif pour nmbre de params impair.
-   Interne: getVersion => getZgVersion.
-   JSON équipements: Amélioration syntaxe permettant de surcharger 'execAtCreationDelay'.
-   Sonoff SNZB-02: JSON revu. 'TH01.json' supporte identifiants 'TH01' & '66666'.
-   JSON équipements: Correction valeur minInterval & maxInterval (décimal => hexa).
-   Page EQ/avancé: Support préliminaire cmds 41 & 42, cluster 1000/Commissioning.
-   Silvercrest HG06106C light bulb: Ajout support (2050).
-   Legrand 16AX: Mise-à-jour icone.

## 211030-BETA-1

-   Tuya ZM-CG205 door sensor: Mise-à-jour JSON. Ajout 'ZoneStatus' (2165).
-   Interne: Parser: Support réponse cluster 000A/Time, attrib 0007 + ...
-   Xiaomi Aqara QBKG26LM: Ajout support (2174).
-   Interne:

    -   setTimeServer => setZgTimeServer.
    -   getTimeServer => getZgTimeServer.
    -   zgSetMode => setZgMode.

-   Prise connectée TS0121 \_TZ3000_8nkb7mof: Mise-à-jour JSON (2167).
-   Interne: Parser:

    -   Msg 0006-FD, msgAbeille() supprimé.
    -   Msg 8030/bind response: revu.

-   Tuya QS-Zigbee-C01 volet roulant: Correction image (2169).
-   Identification modeles Tuya: modifié. Fabricant/vendeur obligatoire pour éviter de prendre mauvais JSON identifié par modèle seul.
-   Silvercrest HG06337-FR: Mise-à-jour JSON pour groups & identify.

## 211030-STABLE-2

-   JSON équipement: Amélioration syntaxe permettant de surcharger 'subType' ou 'unite'.
-   Zlinky TIC: Diverses corrections dont lecture option tarifaire.
-   Tuya repeteur zigbee RP280: Ajout support.
-   Page de config

    -   Options avancées: Nettoyage autorisé si test d'intégrité ok.
    -   Partie mise-à-jour (vérification) caché. Pas assez fiable. A revoir.
    -   Partie 'zigates' revue.

-   Tuya ZM-CG205 door sensor: Mise-à-jour JSON (2165).
-   Interne: Suppression entrée 'zigateNb' de la DB config.
-   eWeLink ZB-SW01: Support préliminaire (2172).

## 211022-BETA-1

-   **ATTENTION**: Format DB interne modifié. Restaurer sauvegarde si besoin de revenir à une version antérieure.
-   Interne: DB équipement: 'modeleJson' => 'ab::jsonId'.
-   Interne: Suppression 'archives'.
-   Page config: Affichage version firmware complète (ex: 0004-0320).
-   Commandes JSON: Suppression 'zbWriteAttribute-Temp'.
-   Interne: Parser vers cmd: queues revisitées.
-   Silvercrest HG06337: Mise-à-jour JSON (2168).
-   Assistant de découverte: Améliorations.
-   Tuya QS-Zigbee-C01 volet roulant: Ajout support (2169).
-   JSON commandes: Suppression commandes obsoletes 'OnEpXX' & 'OffEpXX'.
-   Prise Tuya TS0121\_\_TZ3000_rdtixbnu: Correction RMS Voltage.
-   Interne: 'configureReporting' revu pour support 'reportableChange'.
-   Page EQ/avancé: Amélioration configureReporting pour support min, max & changeVal.
-   Prise NIKO: JSON revisité pour réduire le nombre de reporting RMSvoltage (2003).
-   Prise TS0121 \_TZ3000_8nkb7mof: Mise-à-jour JSON (2167).

## 211019-BETA-1

-   JSON commandes: Nettoyage. Suppression commandes obsolètes.
-   Interne: decodeDataType(): ajout support enum8/enum16 + ieee.
-   Interne: Parser: Support read attributes clusters 0015 & 0100.
-   Tuya ZM-CG205 door sensor: Ajout support (2165).
-   Test d'intégrité et nettoyage automatique à la mise-à-jour.

## 211019-STABLE-1

-   Interne: Correction 'writeAttribute' + mise-à-jour reponse 8110.
-   Tuya 4 buttons (TS004F\_\_TZ3000_xabckq1v): Mise-à-jour support (2155).
-   Commandes JSON: Suppression 'binToZigate-EPXX-0006' => obsolètes.
-   JSON équipements: Ajout support multiple identifiants (ex: 'signalrepeater' & 'SignalRepeater').
-   UseeLink prise SM-SO306: Mise-à-jour (2160).
-   Zigate: plusieurs commandes supprimées => supportées dans page équipement/avancé.
-   Interne: Parser: Améliorations decodeDataType().
-   UseeLink prise SM-SO306: Ajout support (2160).
-   Syntaxe JSON équipement: Ajout possibilité surcharge 'template'.
-   Zlinky TIC: Mise-à-jour JSON + icone.
-   Niko connected socket outlet: Mise-à-jour image.
-   Page EQ/avancé: Ajout possibilité de configurer le reporting.
-   Interne: Parser: Support 'configure reporting response' pour 0B04.
-   TRADFRI bulb GU10 CWS 345lm: Correction icone E14 => GU10 (2137).
-   Ikea Tradfri LED 470lm E14: Mise-à-jour config JSON (2111).
-   Tuya 4 boutons: Mise-à-jour (2155).
-   Interne: AbeilleCmd: Correction bin0030 vers groupe.
-   SPLZB-131: Mise-à-jour JSON. Reporting activé. (2109).
-   Page EQ/avancé: Ajout possibilité d'écrire un attribut.
-   Interne: AbeilleCmd: Message d'erreur si pb lecture queues.
-   Interne: Commandes JSON 'getEtatEpXX' deviennent obsoletes.
-   Page EQ/avancé: correction bouton 'reconfiguer' + amélioration message.
-   Interne: Zigbee const: corrections pour éviter warning PHP.
-   Page équipements: Suppression zone developpeur (bas de page).
-   Page EQ/avancé: Ajout interrogation des 'Active end points'.
-   Interne: Parser: decodeDataType() revu pour types 41 & 42.
-   Interne: Procedure d'inclusion avec support "cas speciaux".
-   Suppression log obsolete AbeilleParser (sans '.log') lors de la mise-à-jour/installation.
-   Page équipement: Message si équipement à disparu depuis l'ouverture de la page.
-   Interne: Parser: Support remontée commandes du cluster 0300 en provenance d'un équipement.
-   Osram smart switch mini: Mise-à-jour. Ne supporte que le controle vers Jeedom.

## 211004-STABLE-1

-   Correction mauvaise config lors de l'inclusion si #IEEE# ou #ZigateIEEE# utilisé.
-   Page santé: correction IEEE manquante pour Ruche.
-   Inclusion: Correction pour vieux équipements type Xiaomi (ne repond pas à lecture attribut manufacturer).
-   Interne: Identification périph revisitée.
-   Assistant Zigbee: Correction & amélioration.
-   Interne: Modification astuce identification vieux modeles Xiaomi basé sur IEEE. Non compatible ZLinky.
-   Correction regression 'setModeHybride' + mise-à-jour commande interne.
-   Assistant Zigbee: Ajout forcage EP01.
-   Commandes JSON: nettoyage.
-   Assistant Zigbee: Améliorations: ajout découverte etendue des attributs.
-   ZLinky TIC: Support préliminaire.
-   Interne: Parser: Nettoyage.
-   Aqara SSM-U02: Correction icone.
-   Xiaomi Mijia Honeywell Détecteur de Fumée: Tentative correction bouton test (2143).
-   Ruban LED Lidl: Mise à jour JSON (1737).
-   TRADFRIbulbGU10CWS345lm: Mise a jour (2137).
-   Interne: Correction erreur PHP: Undefined index: battery_type in AbeilleLQI_Map.php
-   Interne: Correction crash inclusion dans cas ou "value" pointe sur commande inexistante.
-   Xiaomi smoke detector: bouton test genere crash d'AbeilleCmd (2143).
-   Aqara TVOC moniteur d'air AAQS-S01: Support préliminaire (2135).

## 210922-STABLE-1

-   Interne: Correction requete "discover attributes extended".
-   Loratap 3 boutons: Correction regression (2138).
-   Interne: Restoration support historique cmd 0006-FD special Tuya.
-   TRADFRIbulbGU10CWS345lm support preliminaire.
-   TRADFRIbulbE14CWS470lm support preliminaire.
-   SPLZB-132: Correction EP.
-   SPLZB-131: Correction RMSVoltage (2109).
-   Interne: Tools: check_json amélioré.
-   Interne: Parser: Support prélim data type 41, 42, E0, E1, E2.
-   Suppression des messages de "réannonce" si équipement connu et activé.
    Attention. Si l'équipement quitte (leave) puis revient, le message est toujours présent.
-   Legrand dimmer: Mise à jour JSON (983).
-   Monitor: Correction bug (Device announce loupé).
-   JSON équipements: Mise-à-jour setReport-EPxx => zbConfigureReporting.
-   JSON équipements: Nettoyage setReport-EPxx => zbConfigureReporting.
-   Assistant de découverte: améliorations et mise à jour doc.
-   Interne: Parser: Amélioration pour découverte cluster 0005/Scenes.
-   Interne: Correction warning pour "bind to group".
-   Page EQ/avancé: ajout bouton "reset to factory".
-   Interne: AbeilleCmdPrepare + Process updates.
-   JSON équipements: Mise à jour des commandes du type 'toggle'.
-   Page EQ/avancé: ajout possibilité de faire un "bind" vers équipement ou groupe.
-   Parser: Correction table de binding.

## 210916-STABLE-1

-   JSON commandes: 'trig' revu + 'trigOffset' ajouté.
-   Niko connected socket: ajout support (2003).
-   JSON commandes: suppression de qq cmds obsoletes.
-   Interne: Optimisation parser: transfert groupé d'attributs.
-   Interne: Link-Quality mis à jour sur attribut report.
-   JSON équipements: Mise-à-jour commandes pourcentage batterie.
-   Tuya inter 4 buttons: mise-à-jour support 'click' (2122).
-   Interne: Améliorations parser + robustesse.
-   Correction regression: crash pendant l'inclusion (2125).
-   Zemismart TS0042 2 buttons (1272).
-   Interne: Parser: Modificaiton support custom single/double/long click pour Tuya.
-   Tuya 4 buttons scene switch: Mise-à-jour modèle (2122).
-   Ajout FW 3.1e Optimized PDM + 3.20 legacy + 3.20 Optimized PDM
-   Osram CLA60 TW: Correction end point par défaut (2117).
-   Tuya contact sensor TS0203: Ajout reporting batterie (1270).
-   Tuya 4 buttons scene switch: Ajout support (2122).
-   Correction mauvais message: "ATTENTION: config locale utilisée ...".
-   Interne: Améliorations parser.

## 210905-STABLE-1

-   Améliorations assistant EQ.
-   Interne: Séquence de démarrage revisitée pour #2093.
-   Améliorations assistant EQ.
-   Interne: SerialRead, mise-à-jour pour "permission denied".
-   Frient SPLZB-131: Support préliminaire.
-   Legrand Cable outlet: Ajout support préliminaire (850). Manque controle fil pilote.
-   All JSON: 'configuration:battery_type' => 'configuration:batteryType'.
-   Assistant: Ajout doc préliminaire pour découverte Zigbee.
-   Interne: Séquence de démarrage revisitée pour #2093.
-   Page config/options avancées: possibilité de bloquer requetes LQI journalieres.
-   Assistant de découverte: améliorations.
-   Interne: Collecte LQI: Mise-à-jour mineure.
-   Interne: Parser: Amélioration robustesse.
-   Ajout support Controleur Tuya LED DC5-24V (2082).
-   Ajout Ampoule YANDHI E27 (2087)
-   JSON équipements: tous modifiés
    -   SW & getSWBuild => SWBuildID & Get-SWBuildID
-   Interne: Ajout commande générique 'configureReporting'.
-   Page gestion: Bouton 'exclure' pour tous.
-   JSON commandes: suppression de commandes obsolètes.
-   GL-S-003Z: Fichier JSON. Correction end point + divers (2104).
-   Page EQ/avancé: ajout possibilité interroger LQI (Mgmt_Lqi_req).

## 210824-STABLE-1

-   Xiaomi plug EU: JSON revisité (1578).
-   Interne: SerialRead: Ouverture de port améliorée.
-   Silvercrest HG06337-FR: Ajout prise Lidl (2053).
-   JSON équipements: amélioration nouvelle syntaxe (ajout 'isVisible' & 'nextLine').
-   Page EQ/avancé: mise-à-jour de l'assistant de découverte.
-   Page config/options avancées: Support defines.
-   Commande JSON 'temperatureLight': correction EP.
-   Aqara Opple 6 boutons (2048).
-   JSON équipements: tous mis à jour.
    -   'Categorie' remplacé par 'category'.
    -   'nameJeedom' remplacé par 'type'.
    -   'configuration:icone' remplacé par 'configuration:icon'.
    -   mainEP #EP# remplacé par '01'.
    -   'uniqId' supprimé.
    -   'Commandes' => 'commands'
-   Profalux: Ajout support nouvelle génération volet (id=MOT-C1Z06C/10C, #2091).
-   Aqara WS-EUK01 H1 wall switch: ajout support préliminaire (2054).
-   Interne: optimisations AbeilleCmdQueue.
-   Page santé: ajout dernier niveau batterie.
-   Gledopto GL-B-008Z: Correction main EP dans modèle JSON (2096).
-   Gledopto GL-C-006: Modèle préliminaire (2092).
-   Interne: Taille queue parser->LQI augmentée à 2048.
-   Interne: Nettoyage: suppression queue obsolete 'queueKeyLQIToAbeille'.
-   Interne: cron15: Lecture ZCLVersion au lieu d'un attribut pas toujours supporté.
-   Page EQ/avancé: ajout bouton lecture attribut.
-   Interne: tools: Amélioration check_json + update_json
-   Modèle JSON telecommande profalux: correction syntaxe type batterie.
-   Collecte LQI: Correctif parser pour routeur avec plus de 10 childs.
-   Interne: Nettoyage: suppression fichier obsolète 'function.php'.
-   JSON: Correction nom 'Konke multi-function button' pour id 3AFE170100510001.
-   Ajout support 'Konke multi-function button' avec id 3AFE280100510001.

## 210719-STABLE-1

-   ATTENTION: Format JSON des fichiers de commande modifié !
-   Osram classic B40TW: support préliminaire.
-   Xiaomi Luminosite: Ajout pourcentage batterie basé sur retour tension (1166).
-   Interne: cron15 amélioré. Pas d'interrogation si eq appartient à zigate inactive.
-   Inclusion: support revu pour périph qui quitte puis revient sur le réseau.
-   Moniteur: Disponible pour tous et plus seulement en mode dev.
-   Firmware 3.1e disponible pour tous.
-   JSON commandes: Mise-à-jour syntaxe fichier de commande
    -   'order' supprimé (obsolète)
    -   'uniqId' supprimé (obsolète)
    -   'Type' renommé en 'type'
    -   Ajout 'topic' si manquant pour commandes 'info'.
    -   Correction clef top commandes 'info' (clef = nom de fichier).
-   JSON équipements: Support préliminaire pour directive "use"
    -   Ex: "cmd_jeedom_name": { "use": "cmd_file_name", "params": "EP=01" }
-   Page EQ/avancé: ajout du bouton "reconfigurer".
-   Page gestion: suppression du bouton "Apply Settings to NE".
-   Page EQ/avancé: version préliminaire de l'assistant de découverte.
-   Correction ecrasement widget commande (2075).
-   Interne: plusieurs améliorations pour robustesse et support d'erreurs.
-   Page EQ/avancé: Ajout bouton "interroger table routage"
-   Page EQ/avancé: Ajout bouton "interroger table binding"
-   Page EQ/avancé: Ajout bouton "interroger config reporting"
-   JSON: Syntaxe commandes modifiée. Type 'execAtCreationDelay' changé en 'nombre' et non plus 'string'.
    Devrait corriger le pb de config de certains equipements à l'inclusion.
-   Correction perte categorie & icone si equipement se réannonce.
-   Correction perte état historisation & affichage des commandes si equipement se réannonce.
-   Correction mise-à-jour commandes IEEE-Addr, Short-Addr & Power-Source sur réannonce.
-   Page santé: Ajout "dernier LQI" à la place de "Date de création".
-   Interne: Meilleur support des JSON avec mainEP=#EP#.

## 210704-STABLE-1

-   Modifications syntaxe JSON équipement (avec support rétroactif temporaire):
    -   'commands' au lieu de 'Commandes'.
    -   'category' au lieu de 'Categorie'
    -   'icon' au lieu de 'icone'
    -   'batteryType' au lieu de 'battery_type'
-   Support JSON equipement: correction pour support multiple categories.
-   Page EQ/avancé: recharger JSON accessible à tous.
-   Page EQ: type de batterie déplacé vers principale.
-   Interne: plusieurs améliorations de robustesse.
-   Interne: Parser: Support des messages longs.
-   Zigate WIFI: Correction & amélioration arret/démarrage démon.
-   Equipement/categories: correction. Effacement categorie résiduelle.
-   Trafri motion sensor: Mise-à-jour JSON pour controle de groupe.
-   Gestion des démons: correctif sur arret forcé (kill).
-   Interne: Parser: affichage erreurs msg_receive().
-   Interne: SerialRead: affichage erreur msg_send().
-   Interne: Serial to parser: Messages trop grands ignores pour ne plus bloquer la pile + message d'erreur.
-   Batterie %: Parser renvoi valeur correcte pour 0001-EPX-0021 + Abeille.class + update JSON (2056).
    Peut nécessiter de recharger JSON.
-   Batterie %: Report dans Jeedom de tous les "end points" et pas seulement 01.

## 210620-STABLE-1

-   SPLZB-132: Correction icone + ajout somme conso.
-   Correction remontée cluster 0B04 (mauvais EP).
-   Orvibo CM10ZW multi functional relay: mise-à-jour icone.
-   Nettoyage de vieux fichiers log au démarrage.
-   Socat: Amélioration pour avoir les messages d'erreur.
-   Interne: Restauration option avancées de récupération des équipements inconnus.
-   Xiaomi v1: Correction regression inclusion. Trick ajouté pour Xiaomi.
-   Orvibo CM10ZW multi functional relay: support préliminaire.
-   Page santé: Correction mauvais affichage du status des zigates.
-   Page equipement/avancé: Possibilité de recharger dernier JSON (et mettre à jour les commandes) sans refaire d'inclusion.
-   Interne: Suppression AbeilleDev.js (mergé dans Abeille.js).
-   Page équipement: Correction rapport d'aspect image (icone).
-   Interne: Parser: Support msg 9999/extended error.
-   Interne: Parser: Qq améliorations découverte nouvel équipement.
-   Interne: SerialRead: Boucle et attend si port disparait au lieu de quitter (2040).
-   Page gestion: Correction regression groupes.

## 210610-STABLE-3

-   Philips E27 single filament bulb: Ajout modele LWA004
-   Interne: Correction ReadAttributRequest multi attributs
-   Interne: Correction 'zgGetZCLStatus()'
-   Frient SPLZB-132 Smart Plug Mini: Ajout support préliminaire.
-   Interne: Correction eqLogic/configuration. Suppression des champs obsolètes lors de la mise-à-jour de l'équipement.
-   Tuya 4 buttons light switch (ESW-0ZAA-EU): support préliminaire (1991).
-   Tuya smart socket: Ajout support modele générique 'TS0121\_\_TZ3000_rdtixbnu'.
-   Telecommande virtuelle: Correction regression. Plusieurs télécommandes par zigate à nouveau possible (2025).
-   Lancement de dépendances: correction erreur (2026).
-   Exclusion d'un équipement du réseau: En mode dev uniquement. Nouvelle version.
-   Zigate wifi: correction regression.

## 210607-STABLE-1

-   ATTENTION: Regression sur les telecommandes virtuelles. Une seule possible avec cette version.
-   ATTENTION: Faire un backup pour pouvoir revenir à la precedente "stable". Structure DB eqLogic modifiée: "Ruche" remplacé par "0000"
-   Interne: Parser: revue params decodeX() + cleanup
-   Zemismart ZW-EC-01 curtain switch: mise-à-jour modèle.
-   Interne: Correction timeout.
-   Reinclusion: L'equipement et ses commandes sont mis à jour. Seules les commandes obsolètes sont détruites.
    Ca permet de ne plus casser le chemin des scénaris et autres utilisateurs des commandes.
-   Firmware: Suppression des FW 3.0f, 3.1a & 3.1b. 3.1d = FW suggéré.
-   JennicModuleProgrammer: Mise-à-jour v0.7 + améliorations. Compilé avant utilisation.
-   Zigate DIN: Ajout support pour mise-à-jour FW.
-   Page equipement: section "avancé", mise à jour des champs en temps réel (1839).
-   Gestion des groupes: correction regression (2011).
-   Telecommande virtuelle: correction regression (2011).
-   Interne: Revue decode 8062 (Group membership).
-   JSON: Correction setReportTemp (1918).
-   Innr RB285C: correction modele corrompu.
-   Innr RB165: modele préliminaire.
-   Tuya GU10 ZB-CL01: ajout support.
-   Hue motion sensor: mise-à-jour JSON.
-   Interne: correction message 8120.
-   Page config: correction installation WiringPi (1979).
-   Introduction de "core/config/devices_local" pour les EQ non supportés par Abeille mais locaux/en cours de dev.
-   Zemismart ZW-EC-01 curtain switch: ajout du modèle JSON
-   Nouvelle procédure d'inclusion.
-   Support des EQ avec identifiants 'communs'.
-   Création du log 'AbeilleDiscover.log' si inclusion d'un équipement inconnu.
-   Profalux volet: Revue modele JSON. Utilisation cluster 0008 au lieu de 0006 + report.
-   Page EQ/commandes: pour mode developpeur, possibilité charger JSON.
-   Ordre apparition des cmdes: Suit maintenant l'ordre d'utilisation dans JSON equipement.
-   Un équipement peut maintenant être invisible par defaut ('"isVisible":0' dans le JSON).
-   Profalux telecommande: S'annonce mais inutilisable côté Jeedom. Cachée à la création.

## 210510-STABLE-1

-   Page compatibilité: revisitée + ajout du tri par colonne
-   Page santé: ajout de l'état des zigate au top
-   Sonoff SNZB-02: support corrigé + support 66666 (ModelIdentifier) (1911)
-   Xiaomi GZCGQ01LM: ajout support tension batterie + online (1166)
-   Page EQ/params: ajout de l'identifiant zigbee
-   Correction "#1908: AbeilleCmd: Unknown command"
-   Correction "#1951: pb affichage heure "Derniere comm."
-   Correction blocage du parser dans certains cas de démarrage.
-   Diverses modifications pour améliorer la robustesse et les messages d'erreurs.
-   Monitor (pour developpeur seulement pour l'instant)
-   Gestion des démons: revisitée pour éviter redémarrages concurrents.
-   Correction "#1948: BSO, lift & tilt"
-   JSON: Emplacement des commandes changé de "core/config/devices/Template" vers "core/config/commands"
-   Innr RB285C support preliminaire

## 11/12/2020

-   Prise Xiaomi: fonctions de base dans le master (On/Off/Retour etat). En cours retour de W, Conso, V, A et T°.
-   LQI collect revisited & enhanced #1526
-   Ajout du modale Template pour afficher des differences entre les Abeilles dans Jeedom et leur Modele.
-   Ajout d un chapitre Update dans la page de configuration pour verifier que certaines contraintes sont satisfaites avant de faire la mise a jour.
-   Ajout Télécommande 3 boutons Loratap #1406
-   Contactor 20AX Legrand : Pilotage sur ses fonctions ON/OFF et autre
-   Prise Blitzwolf
-   Detecteur de fumée HEIMAN HS1SA-E
-   TRADFRIDriver30W IKEA

## Beta 24/11/2010

Surtout faites un backup pour pouvoir revenir en arrière.

-   Premiere version Abeille qui prend en charge le firmware Zigate 3.1d.
-   J'ai passé toutes mes zigates en 3.1D (Je ne vais plus tester les evolutions d'Abeille avec les anciennes versions du firmware).
-   Essayez de passer sur le firmware 3.1D quand vous pourrez. Perso je vous recommande de faire un erase EEPROM lors de la mise à jour puis de faire un re-inclusion de tout les modules. Attention c'est une operation assez lourde. Assurez vous d'avoir l'adresse IEEE dans la page santé avant de faire cette operation.
-   Dans la page de configuration il y a un nouveau paramètre: "Blocage traitement Annonces". Par défaut il doit être sur Non. Il semble qui ai une certaine tendance à ce mettre sur Oui. Mettez le bien sur Non et redémarrer le Démon.
-   si vous restez avec une zigate en firmware inférieur à 3.1d, ne surtout pas passer la zigate en mode hybride.

-   Tous les details dans:
    https://github.com/KiwiHC16/Abeille/commits/beta?after=61b027c84f673f484073d6f0a73ad0bad08fef0d+34&branch=beta

## 12/2019 => 03/2020

::

    !!!! Le plugin a été testé avec 1 ou 5 Zigate dans le panneau de configuration  !!!!
    !!!! Avec d'autres valeur 2, 3, 4, 6... il est for possible que tout            !!!!
    !!!! ne fonctionne pas comme prévu. Si vous avez 2 zigate, mettez le nombre de  !!!!
    !!!! zigate à 5 et activer uniquement les zigates presentent.                   !!!!

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

Attention: cette nouvelle version apporte:

-   le multi-zigate
-   la gestion de queue de message avec priorité
-   quelques équipements supplémentaires
-   corrections de bugs

Mais comme les changements sont importants et que j'ai pas beaucoup de temps pour tester il peut y avoir des soucis. Donc faites bien une sauvegarde pour revenir en arrière si besoin.

A noter:

-   La partie graphs n'a pas été complètement vérifiée et il reste des soucis
-   les timers ne sont plus dans le Plugin
-   un bug critique si vous faites l inclusion d'un type d equipement inconnu par Abeille, il faut redemarrer le demon.

## 06/2019 => 11/2019

Rien de spécifique à faire. Juste a faire la mise à jour depuis jeedom.

## 03/2019 => 06/2019

Rien de spécifique à faire.

## 02/2019 => 03/2019

Rien de spécifique à faire. Pour les évolution voir le changelog ci dessous.

## 01/2019 => 02/2019

Cette version est en ligne avec le firmware 3.0f de la Zigate
Vous pouvez utiliser un firmware plus vieux mais tout ne fonctionnera pas. (98% fonctionnera)

Comment procéder:

-   Mettre à jour le plugin Abeille
-   Flasher la Zigate avec le firmware 3.0f (_bien faire un erase de l'EEPROM_)
-   Connecter la Zigate et démarrer le deamon abeille
-   démarrer le réseau Zigbee depuis abeille
-   mettre la Zigate en inclusion
-   inclure vos routeurs en partant des plus proches au plus lointain
-   Vérifier que tout fonctionne
-   Inclure les équipements sur piles (dans l'ordre que vous voulez)

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

## 11/2018 => 01/2019

Cette mise à jour est importante et délicate. Pour facilité l'intégration de nouveaux équipements par la suite une standardisation des modèles doit être faite.
Cela veut dire que tous les modèles changent et que le objets dans Abeille/Jeedom doivent être mis à jour.
Prévoir du temps, avoir bien fait les backup, et prévoir d'avoir à faire quelques manipulations à la main. Les situations rencontrées vont dépendre de l'historique des équipements dans Jeedom.

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

Solution pour les petits systèmes

-   Cela suppose que vous aller effacer (objets, historiques,...) toutes les données puis re-créer le réseau.
-   supprimer le plug in Abeille
-   Installer le plug in Abeille depuis le market (ou github)
-   Activer et faire la configuration du plugin
-   Démarrer le plugin
-   Mettre en mode inclusion
-   Appairer les devices.

Solution pour les gros systèmes

Si la solution précédente demande trop de travail, on peut faire la mise à jour de la façon suivante. Attention, je ne peux pas tester toutes les combinaisons et des opérations supplémentaires seront certainement nécessaires. 90% aura été fait automatiquement.
Il n'y a pas de moyen infaillible pour faire la correspondance entre une commande dans un modèle et une commande dans Jeedom. Le lien est fait soit par le nom dans la commande nom ou quand pas disponible par le nom de l'image utilisée pour le device. De même pour les commande le nom est le moyen de faire le lien. Si vous avez fait des changements de nom, les commandes sortiront en erreur et cela demandera de mettre le nom de la commande dans le modèle le temps de la conversion.
Dans les versions suivantes, nous ne devrions plus avoir ce problème car les commandes auront un Id unique et spécifique.

-   Mettre à jour la plugin avec le market (ou github)
-   Vérifier la configuration du plugin et démarrer le plugin en mode debug.
-   Demander la mise à jour des objets depuis les templates, bouton: "Appliquer nouveaux modèles"
-   90% des objets devraient être à jour maintenant.
-   Tester vos équipements.

Si un équipement ne fonctionne pas, appliquer de nouveau la mise a jour sur cet équipements uniquement. Pour ce faire dans la page Plugin->Protocol Domotique->Abeille, sélectionnez le device et clic sur bouton: "Apply Template". Ensuite regarder le log "Abeille_updateConfig" pour avoir le détails des opérations faites et éventuellement voir ce qui n'est pas mis à jour.

vous allez trouver des messages:

-   "parameter identical, no change" qui indique que rien n'a été fait sur ce paramètre (déjà à jour).
-   "parameter is not in the template, no change" qui indique que le paramètre de l'objet n'est pas trouvé dans le template. Soit il n'est plus nécessaire et ne sera donc pas utilisé, soit vous l'avez changé et on le garde, soit Jeedom a défini une valeur par défaut et c'est très bien ...
-   "Cmd Name: nom ===================================> not found in template" qui indique qu'on ne trouve pas le template pour la commande et que donc la commande n'est pas mise à jour. Ça doit être les 10% à gérer manuellement. Dans ce cas, soit effacer l'objet et le recréer soit me joindre sur le forum.

Équipements qui sont passés sans soucis sur ma prod:

-   Door Sensor V2 Xiaomi
-   Xiaomi Smoke
-   Télécommande Ikea 5 boutons
-   Xiaomi Présence V2
-   Xiaomi Bouton Carré V2
-   Xiaomi Température Carré
-   ...

Cas rencontrés:

-   plug xiaomi, une commande porte le nom "Manufacturer", doit être remplacé par "societe" et appliquer de nouveau "Apply Template"
-   interrupteurs muraux Xiaomi: si la mise a jour ne se fait, il faut malheureusement, supprimer et recréer.
-   door sensor xiaomi V2 / xiaomi presence V1: une commande porte le nom "Last", doit être remplacé par "Time-Time", et "Last Stamp" par "Time-Stamp"
-   ...

Secours

-   Si rien n'y fait, aucune des deux solutions précédentes ne résout le soucis, vous pouvez probablement exécuter la méthode suivante sur un équipement (je ne l'ai pas testée):
-   supprimer la commande IEEE-Addr de votre objet.
-   Zigate en mode inclusion et re-appairage de l'équipement
-   un nouvel objet doit être créé.
-   Transférer les commandes de l'ancien objet vers le nouveau avec le bouton "Remplacer cette commande par la commande"
-   Transférer l'historique des commandes avec le bouton "Copier l'historique de cette commande sur une autre commande"
-   Vous testez le nouvel équipement
-   si ok vous pouvez supprimer l'ancien.

## 2019-11-25

Ce dernières semaines le focus a été sur:

-   Compatibilité avec Jeedom V4 et Buster (Debian 10)
-   mise en place de la gestion des messages envoyés à la zigate avec creation de fil d'attente.
-   Repetition d'un message vers la zigate si elle dit n'avoir pas réussi à le gérer
-   Refonte de la détection de équipements lors de l inclusion
-   Store et Télécommande Store Ikea
-   Demarrage automatique du réseau Zigbee
-   Iluminize Dimmable 511.201
-   Iluminize 511.202
-   Osram Smart+ Motion Sensor
-   Télécommande OSRAM
-   Ajout ampoules INNR RF263 et RF265
-   Corrections de bugs

## 2019-03-19

-   Motion Hue Outdoor integration
-   Doc Hue Motion
-   Hue Motion Luminosite

## 2019-03-18

-   Plus de doc sur la radio
-   Modification modele sur EP

## 2019-03-17

-   Resolution sur un systeme en espagnole

## 2019-03-16

-   start to track APS failures
-   dependancy_info debut des modifications

## 2019-03-15

-   Moved all doc to asciidoc format
-   Few correction around modele folder

## 2019-03-11

-   Ajout capteur IR Motion Hue Indoor

## 2019-03-01

-   Inclusion de la PiZiGate
-   Possibilité de programmer le PiZiGate

## 2019-02-27

-   OSRAM SMART+ Outdoor Flex Multicolor
-   Eurotronic Spirit

## 2019-02-15

-   Correction probleme volet profalux

## 2019-02-14

-   Amelioration de la doc
-   Inclusion dans appli web mobile

## 2019-02-11

-   Amelioration de la doc.
-   Reduction log sur annonce
-   Prise Xiaomi Encastrée

## 2019-02-07

-   Mise en place de la cagnotte
-   Correction de l affichage des icones sur filtre
-   Amélioration retour Tele Ikea

## 2019-02-06

-   Récupération des groupes dans la Zigate
-   Configuration du groupe de la remote ikea On/off depuis abeille
-   Formatting of Livolo Switch
-   Groupe commande Chaleur ampoule
-   GUI to set group to Zigate
-   TxPower Command
-   Channel setMask and setExtendedPANID added
-   Télécommande Ikea Bouton information to Abeille
-   Certification configuration
-   Led On/Off

## 2019-02-04

-   Get Group Membership response modification avec source address for 3.0.f
-   Fix Sur mise a jour des templates il manque la mise a jour des icônes
-   OSRAM Spot LED dimmable connecté Smart+ - Culot GU5.3
-   Now default Zigbee object type could be used to create object in Abeille
-   TRADFRIbulbE27WSopal1000lm
-   MQTT loop improvement so Abeille should be more reactive
-   nom du NE qui fait un Leave dans le message envoyé à la ruche
-   Ampoule Hue Flame E14
-   Info move from Ruche to Config page
-   A bit more decoding of Xiaomi Fields
-   channel mak and ExtPAN setting
-   Ajout du Switch Livolo 2 boutons
-   Affichage Commande au démarrage
-   ClassiA60WClear second modèle added
-   setTimeServer / getTimeServer

## 2019-01-25

-   Ajout commande scene
-   Deux petites vidéos pour les docs
-   Ajout des scènes et groupes de scènes
-   Ajout ampoule LWB004
-   Osram - flex led rgbw
-   Osram - garden led rgbw
-   GLEDOPTO Controller RGB+CCT
-   Ajout de gestion du time server (cluster)

## 2019-01-15

-   retrait de pause pour avoir un plugin plus réactif
-   LCT001 modèle ajouté
-   LTW013 Philips Hue modèle ajouté
-   Ajout modèle lightstripe philips hue plus modèle ajouté
-   doc télécommande Hue
-   Ajout LTW010 ampoule Hue White Spectre
-   Ajout de la liste des Abeille ayant un groupe avec leur groupe
-   LCT015 Bulb Added
-   Add Address IEEE in health table

## 2018-12-15

-   Graph LQI par distance
-   télécommande carré Ikea On/Off
-   fix température carré xiaomi
-   Télécommande Hue retour Boutons vers Abeille (scénario)

## 2018-12-11

-   Toute la doc sous le format Jeedom

## 2018-12-10

-   Ampoule Couleur Standard ZigBee
-   Ampoule Dimmable Standard ZigBee

## 2018-12-09

-   Ampoule Spectre Blanc Standard ZigBee
-   Blanche Ampoule GLEDOPTO GU10 Couleur/White GLEDOPTO avec hombridge
-   Spectre Blanc Ampoule GLEDOPTO GU10 GL-S-004Z avec hombridge
-   Retour des volets profalux en automatique
-   Poll Automatique
-   Ajout/Suppression/Get des groupes depuis l interface Abeille

## 2018-12-08

-   Couleur Ampoule GLEDOPTO GU10 Couleur/White GL-S-003Z avec hombridge

## 2018-12-07

-   Couleur Ampoule Ikea avec Homebridge
-   Couleur Ampoule OSRAM avec Homebridge
-   Couleur Ampoule Hue Go avec Homebridge

## 2018-12-05

-   Ajout d un paramètre Groupe dans la configuration des devices pour avoir la groupe a commander. Il n'est plus besoin de changer les commandes une à une.

## 2018-12-04

-   passage aux modèles standardisés (avec include)
-   les modèles standardisés permettent de modifier les équipements dans Jeedom sans les effacer et donc sans perdre historique, scénarios associés,...
-   ajout des boutons pour appliquer de nouveau les modèles de device
-   introduction d'Id unique dans les template pour ne pas confondre les devices par la suite.

## 2018-01-12

-   Ampoule GLEDOPTO White intégrée

## 2018-11-30

-   Prise Ikea intégrée
-   Ajout des groupes aux devices sélectionnés

## 2018-11-26

-   Ikea Transformer 30W intégré

## 2018-11-24

-   Correction TimeOut (en min)

## 2018-11-16

-   Activation/Désactivation d'un équipement suivant qu'il joint le réseau ou le quitte.
-   Rafraichi les informations de la page Health à l'ouverture.

## 2018-11-05

-   Ajout OSRAM GU10

## 2018-06-14

-   Ajout de la connectivité en Wifi.
-   Ajout des LQI remontant des trames Zigate

## 2018-06-12

-   Ajout du double interrupteur mural sur pile xiaomi.
-   Network modal (graph automatique du reseau)
-   Ajout aqara Cube

## 2018-06-11

-   Stop for Volet Profalux =253

## 2018-06-01

-   Profalux Volets Calibration

## 2018-05-30

-   Inclusion status dans le widget mis à jour en fonction de l’etat de la Zigate

## 2018-05-28

-   Ajout des equipements DIY

## 2018-01-19

-   first version posted on github
-   inclus la création des objets IKEA Bulb et Xiaomi Plug, Température Carre/rond, bouton et InfraRouge
