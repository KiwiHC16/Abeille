ChangeLog
=========

- Erreur sur 'exclusion' d'équipement: Correction (2305)
- Interne: AbeilleSerialRead: msg erreurs masqués (2306).

220204-BETA-1
-------------

- Interne: Correction erreur 'prepareCmd(): Mauvais format de message' (2302).
- Aucun équipement sélectionné: correction (2305).

220202-BETA-1
-------------

  .. important:: Pour les zigates v1, l'équipe Zigate recommande FORTEMENT d'utiliser un firmware **Optimized PDM** (OPDM) dans les cas suivants:

      - Toute nouvelle installation.
      - Dès lors qu'un réappairage complet est nécéssaire.
      - La version OPDM corrige bon nombre de potentielles corruptions et supporte un plus grand nombre d'équipements.
      - Les firmwares avant 3.1e sont forcement 'legacy'.
      - Mais **ATTENTION** si vous migrez d'une version 'legacy' vers 'OPDM' il vous faudra **effacer la PDM et réapparairer tous vos équipements**.

  .. important:: Les zigates v1 doivent avoir un firmware >= 3.1e pour un fonctionnement optimal.
  .. important:: Les zigates v2 doivent être à jour du dernier firmware disponible.

- Page config: Changement mineur. Type 'WIFI' => 'WIFI/ETH'.
- Page config: Liste des ports revue + info 'Orange Pi Zero'.
- Aqara TVOC moniteur d'air AAQS-S01: Mise-à-jour modèle (2279).
- Assistant JSON: mise-à-jour.
- Modèle commande JSON: 'getPlugVAW' => 'poll-0B04-0505-0508-050B'.
- Interne: AbeilleCmd: Message debug & améliorations controle de flux envoie.
- Message d'erreur remonté à l'utilisateur si erreur dans log.
- Page gestion: Controle des groupes revu suite core 2.4.7 (2284).
- Legrand 20AX: Mise-à-jour modèle (2213).
- Interne: Correction AbeilleTools sendMessageToRuche().
- Interne: SerialRead: Suppression mess d'err sur première trame corrompue.
- Mauvaise taille de modale parfois: correction (2177).

220130-BETA-1
-------------

- LivarnoLux applique murale HG06701: Correction modèle (2256).
- Blitzwolf SHP15: Support preliminaire (2277).
- Assistant EQ/JSON: Update.
- Interne: AbeilleCmd: Correction priorité getActiveEndpoints.
- Interne: Parser: Interrogation de tous les EP pour support des eq qui s'identifient via un EP different du premier.
- Interne: Nettoyage config cmdes 'PollingOnCmdChange' & 'PollingOnCmdChangeDelay' lors mise-à-jour équipement.
- Interne: AbeilleCmd: Suppression 'Management_LQI_request' obsolete.
- Tuya 4 buttons (TS004F__TZ3000_xabckq1v): Mise-à-jour modèle (2155).
- Aqara TVOC moniteur d'air AAQS-S01: Mise-à-jour modèle (2279).
- Modeles commandes (JSON): modifications syntaxe

  - 'unite' obsolete => 'unit'
  - 'generic_type' obsolete => 'genericType'
  - 'trig' obsolete => 'trigOut'
  - 'trigOffset' obsolete => 'trigOutOffset'
- Modèles équipements (JSON): améliorations

  - Surcharge possible de 'logicalId'
  - Surcharge possible de 'trigOut'
  - Surcharge possible de 'trigOutOffset'
  - Surcharge possible de 'invertBinary'
- Interne: DB eqLogic, config, ab::trig ou trigOffset => ab::trigOut ou trigOutOffset.
- Xiaomi Aqara MCCGQ14LM (magnet.acn001): Correction modèle (2257).
- Interne: checkGpio() revu pour suppression faux message 'PiZigate inutilisable'.
- Page de config: Ajout bouton vers doc & doc préliminaire correspondante.
- Page de config: Bouton 'activer' renommé en 'libérer'. Trompeur. N'active pas la zigate.
- Xiaomi door: Correction etat inversé (regression 220110-BETA-1).
- Interne: CmdQueue: erreur si message trop gros dans queue 'ParserToCmdAck'.
- Interne: AbeilleCmd: Correction regression suite mise-à-jour 'setLevel'.
- Tuya GU10 color bulb (TS0505B__TZ3210_it1u8ahz): Ajout support (2280).

220123-BETA-1
-------------

- Gledopto GU10 buld GL-S-007Z: Ajout support préliminaire (2270).
- Interne: AbeilleCmd: SimpleDescriptorRequest => getSimpleDescriptor.
- Page EQ/avancé: Ajout support 'Simple descriptor request'.
- Interne: AbeilleCmd: IEEE_Address_request => getIeeeAddress.
- Equipement sur secteur en time-out: Correction.
- Interne: Correction msg debug 'IEEE addr mismatch' au démarrage.
- Orvibo CM10ZW: Support signature alternative (2275).
- Interne: AbeilleCmd: Correction pour espace dans valeur slider.
- Interne: AbeilleCmd: Suppression prepare 'setLevel'.

220122-BETA-1
-------------

- Interne: format message queues vers AbeilleCmd modifié.
- Interne: Fusion de plusieurs queues vers AbeilleCmd.
- Erreur getLevel/getEtat inattendue: Correction (2239).
- Xiaomi Aqara MCCGQ14LM (magnet.acn001): Correction modèle (2257).
- Interne: Parser vers Abeille. Attributs groupés pour optimisation.
- Interne: Qq améliorations page EQ/avancé/Zigate.
- Page de config: Amélioration messages mise-à-jour FW.
- Page support/infos clefs: Affichage revu.
- Interne: Parser: Optimisations & nettoyage.
- Interne: Queues revues.
- Page EQ/avancé: possibilité de télécharger discovery 'automatique'.
- Interne: Abeille.class: Vérification de l'état des queues amélioré.
- Xiaomi H1 double rocker: Mise-à-jour modèle + image (2253).
- Interne: Abeille.class: Suppression interrogateUnknowNE().
- Page EQ/avancé: Correction regression bouton "Réinitialiser".
- Page EQ/avancé: Réinit 'defaultUnknown' si modèle officiel existe.
- Interne: Commande 'setColor' (cluster 0300) revue.

220114-BETA-1
-------------

- Interne: Ajout support cmd 00/Setpoint, cluster 0201/thermostat.
- Acova Alcantara: Mise à jour modele pour controle temp (2180).
- 'Graph' visible seulement en mode dev.
- Interne: Gestion des queues: log & suppression msg trop gros. A completer.
- Interne: Gestion des queues en cas de msg trop gros.

220113-BETA-1
-------------

- Xiaomi Aqara wall switch D1 (switch.b1nacn02): Mise-à-jour modèle (2262).
- Profalux Zoe: Identifiant 'TG1' = 'TS' (1066).
- Réseau/bruit: fonctionalité masquée sauf mode dev.
- Interne: Parser: 8401/IAS zone status change revisité.
- RH3040 PIR sensor: Mise-à-jour modèle (2252).
- Gledopto GL-SD-001 AC dimmer: Ajout support (2258).
- Tuya télécommande 4 boutons (TS0044): Ajout support (2251).

220110-BETA-1
-------------

- Interne: Début refonte/nettoyage AbeilleCmd pour amélioration controle de flux.
- Interne: Parser: Support nPDU/aPDU sur messages 8000/8012 & 8702 (FW>=3.1e).
- Interne: Cmd: Ajout support optionnel 'manufId' pour 'writeAttribute'.
- Page EQ/avancé: Ecriture attribut améliorée. Ajout support 'direction' & 'manufId'.
- Xiaomi H1 double rocker: Ajout support (2253).
- JSON équipements: Nettoyage commandes obsolètes

  - 'etat' => 'zb-0006-OnOff'
  - 'etatCharge0' => 'zb-0006-OnOff' + 'ep=01'
  - 'etatCharge1' => 'zb-0006-OnOff' + 'ep=02'
  - 'etatCharge2' => 'zb-0006-OnOff' + 'ep=03'
  - 'etatCharge6' => 'zb-0006-OnOff' + 'ep=07'
  - Ajout surcharge de 'genericType'
  - 'etatInter0' => 'zb-0006-OnOff' + 'ep=01'
  - 'etatInter1' => 'zb-0006-OnOff' + 'ep=02'
  - 'etatInter2' => 'zb-0006-OnOff' + 'ep=03'
  - 'etatDoor' => 'zb-0006-OnOff'
- TRADFRIbulbE14WScandleopal470lm LED1949C5: Mise-à-jour modèle (2250).
- Interne: AbeilleCmd: Suppression prepare readReportingConfig() + getBindingTable().
- Package support: ajout du log 'update'.
- LivarnoLux applique murale HG06701, TS0505A, _TZ3000_5bsf8vaj: Ajout support preliminaire (2256).
- Assistant modèle JSON: Améliorations.
- Interne: Abeille.class: Nettoyage fonctionalités obsolètes.
- Xiaomi Aqara MCCGQ14LM (magnet.acn001): Ajout support préliminaire (2257).
- Lidl HG07878A TS0502A: Correction modèle (2198).
- Interne: Suppression des cmdes Ruche obsolètes au démarrage des démons.
- QS-zigbee-C01 nouvelle version: ajout support (2260).
- Xiaomi Aqara wall switch (switch.b1nacn02): Ajout support (2262).

220108-STABLE-1
---------------

- Tuya TV02: Ajout image (2175).
- JSON équipements: Correction support params optionnels.
- TRADFRIbulbE14WScandleopal470lm LED1949C5: Ajout support (2250).
- Tuya RH3040 PIR: Ajout support (2252).
- ZBMini: Ajout polling toutes les 15mins pour vérifier toujours en vie.
- Sixwgh WH025/TS011F__TZ3000_cphmq0q7: Ajout polling 0006 + 0702 (2211).
- Interne: Gestion 'PollingOnCmdChange' revue.
- Interne + page EQ/avancé: Ajout support writeAttribute via cmd 0530.
- Page de config: Affichage version connue du firmware.
- Page EQ/avancé: Affichage version complète FW (ex: 0004-0320).

211214-BETA-3
-------------

- dOOwifi DWF-0205ZB-PN-2: Ajout PNG (2241).
- JSON équipements: Nettoyage commandes obsolètes

  - 'spiritSetReportBatterie' => 'zbConfigureReporting' + 'clustId=0001&attrType=20&attrId=0021'
  - 'setReportIlluminance' => 'zbConfigureReporting' + 'clustId=0400&attrType=21&attrId=0000'
  - 'setReportTemperature' => 'zbConfigureReporting' + 'clustId=0402&attrType=29&attrId=0000'
  - 'setReportOccupancy' => 'zbConfigureReporting' + 'clustId=0406&attrType=18&attrId=0000'
- QS-Zigbee-C01: Correction modele pour cmde 'Position'.
- Ajout support Module volet Roulant dOOwifi DWF-0205ZB-PN-2 (2241).
- Firmware: Ajout version 3.21 OPDM+legacy. Suppression versions antérieures à 3.1d.
- Xiaomi Aqara QBKG26LM: Mise-à-jour modèle (2174).

211210-BETA-1
-------------

- Réseau Abeille/routes: Correction erreur si équipement sans parent.
- Support: Mise-à-jour infos clefs.
- JSON équipements

  - Fin de support noms obsoletes: nameJeedom/Categorie/icone/battery_type/Commandes.
  - Support surcharge de parametres optionnels.

211209-BETA-1
-------------

- Interne: Création/mise-à-jour ruche revue.
- Interne: Suppression mode 'hybride' forcé.
- Message si FW plus vieux que 3.1D (nécessaire pour certains équipements).

211208-BETA-2
-------------

- Ruche: page équipement/avancé: Correction regression bouton 'setMode'.
- SPLZB-131: RMSVoltage, reporting si variation >= 2V (2109).
- Xiaomi Aqara SSM-U01: Ajout support 'ActivePower' (2234).
- JSON équipements: Nettoyage commandes obsolètes

  - 'setReportBatterie' => 'zbConfigureReporting' + 'clustId=0001&attrType=20&attrId=0021'
  - 'setReportBatterieVolt' => 'zbConfigureReporting' + 'clustId=0001&attrType=20&attrId=0020'
  - 'setReportEtat' => 'zbConfigureReporting' + 'clustId=0006&attrType=10&attrId=0000'
  - 'setReportLevel' => 'zbConfigureReporting' + 'clustId=0008&attrType=10&attrId=0000'
  - 'setReportCurrent_Position_Lift_Percentage' => 'zbConfigureReporting' + 'clustId=0102&attrType=10&attrId=0008'
  - 'setReportHumidity' => 'zbConfigureReporting' + 'clustId=0405&attrType=20&attrId=0000'
- Récupération équipements fantomes (toujours sur le réseau mais plus dans Jeedom): Améliorations.

211208-BETA-1
-------------

- Interne: AbeilleDebug.log déplacé dans répertoire temporaire Jeedom.
- Support: Generation infos clefs pour support à la création du package.
- Identification équipement: Interrogation EP01 en plus du premier.
- Sonoff S26R2ZB: Ajout support (2221).

211207-BETA-3
-------------

- Acova Alcantara: Version temporaire 'Set-OccupiedHeatingPoint' (2180).
- Tuya/Sixwgh TS011F__TZ3000_cphmq0q7: Cluster 0B04 migré en polling (2211).

211207-BETA-2
-------------

- Interne: Amélioration création ruche vs démarrage. Mode forcé en 'hybride' qq soit FW.
- Philips wall switch module/RDM001: Mise-à-jour modèle & support cluster FC00 (2185).
- JSON équipements: Nettoyage commandes obsolètes

  - 'BindToPowerConfig' => 'zbBindToZigate' + 'clustId=0001'
  - 'BindToZigateTemperature' => 'zbBindToZigate' + 'clustId=0402'
  - 'BindToZigateRadiateur' => 'zbBindToZigate' + 'clustId=0201'
  - 'BindToZigateEtatLegrand' => 'zbBindToZigate' + 'clustId=FC41'
  - 'BindToZigatePuissanceLegrand' => 'zbBindToZigate' + 'clustId=0B04'
  - 'BindToZigateLightColor' => 'zbBindToZigate' + 'clustId=0300'
  - 'BindToZigateOccupancy' => 'zbBindToZigate' + 'clustId=0406'
  - 'BindToZigateCurrent_Position_Lift_Percentage' => 'zbBindToZigate' + 'clustId=0102'
  - 'BindShortToSmokeHeiman' => 'zbBindToZigate' + 'clustId=0500'
  - 'BindShortToZigateBatterie' => 'zbBindToZigate' + 'clustId=0001'
- Interne: AbeilleCmd: Traitement status 8000 groupé + ...
- Xiaomi Aqara SSM-U01: Ajout support (2227).
- Interne: AbeilleCmd: Ajout support cmd 0201/Thermostat.
- Interne: AbeilleCmd: writeAttribute(): Correction direction.
- Interne: Parser: Requetes lecture attributs groupées lors d'une annonce.
- Effacement PDM: Correction regression interne.

211205-BETA-1
-------------

- Orvibo ST30: Mise-à-jour modèle + icone (2193).
- Tuya/Sixwgh TS011F__TZ3000_cphmq0q7: Mise-à-jour modèle + icone (2211).
- Récupération équipements fantomes (toujours sur le réseau mais plus dans Jeedom): Mise-à-jour
- Aqara Smart Wall Switch H1 EU (No Neutral, Double Rocker) (WS-EUK02): Support préliminaire (2224).

211205-STABLE-1
---------------

- Page EQ/avancé: Ajout bouton reset SW zigate (2176).
- Appairage équipement: correction regression.

211202-BETA-1
-------------

- Récupération équipements fantomes (toujours sur le réseau mais plus dans Jeedom): Partiel.
- Tuya/Sixwgh TS011F__TZ3000_cphmq0q7: Ajout support (2211).
- Page EQ/avancé: Ajout bouton récupération adresse IEEE.
- Message si mode debug et moins de 5000 lignes de log.

211130-BETA-2
-------------

- Dimmer-Switch-ZB3.0_HZC: Mise-à-jour reporting CurrentLevel (2200).
- Philips wall switch module/RDM001: Mise-à-jour modèle (2185).
- Zigate WIFI: Amélioration serial read pour meilleur support coupures de connexion.
- Interne: AbeilleCmd: Nouveau support #slider# appliqué à 'writeAttibute'.

211129-BETA-2
-------------

- Interne: Zigbee const: Ajout cluster 0406.
- Dimmer-Switch-ZB3.0_HZC: Ajout image PNG (2200).
- Interne: Zigbee const: Mise à jour attributs cluster 0300.
- Livarno HG07834C E27 bulb: Ajout support préliminaire (2203).
- Profalux MAI-ZTS: Ajout support telecommande gen 2 (2205).
- Profalux volets 2nd gen: Meme config pour MOT-C1Z06C & MOT-C1Z10C.
- JSON équipements: Nettoyage commandes obsolètes

  - 'xxxxK' => 'zbCmd-0300-MoveToColorTemp'
  - 'dateCode' => cmde supprimée
  - 'BasicApplicationVersion' => cmde supprimée
  - 'Rouge' => 'zbCmd-0300-MoveToColor'
  - 'Blanc' => 'zbCmd-0300-MoveToColor'
  - 'Bleu' => 'zbCmd-0300-MoveToColor'
  - 'Vert' => 'zbCmd-0300-MoveToColor'

211126-BETA-2
-------------

- Interne: Améliorations assistant JSON.
- JSON équipements: Nettoyage commandes obsoletes

  - 'colorX' => 'zb-0300-CurrentX'
  - 'colorY' => 'zb-0300-CurrentY'
  - 'location' => cmde supprimée
  - 'Get-ColorX' => 'zbReadAttribute' + 'clustId=0300&attrId=0003'
  - 'Get-ColorY' => 'zbReadAttribute' + 'clustId=0300&attrId=0004'
  - 'Level' => 'zb-0008-CurrentLevel'
- Interne: Parser: Data type 30/enum8 décodé comme nombre au lieu de string hex.
- Port interne Zigate Wifi déplacé de /dev/zigateX => /tmp/zigateWifiX pour contourner pb de "read-only file system".

211125-BETA-1
-------------

- Assistant de découverte: Texte de rappel si batterie.
- Tuya RH3001 door sensor: Mise-à-jour JSON (1226).
- Lidl HG07878A TS0502A: Ajout support préliminaire (2198).
- JSON équipements: Nettoyage commandes obsoletes

  - 'BindToZigateEtat' => 'zbBindToZigate'
  - 'BindToZigateLevel' => 'zbBindToZigate'
  - 'BindToZigateButton' => 'zbBindToZigate'
  - 'BindToZigateIlluminance' => 'zbBindToZigate'
  - 'levelLight' => 'zb-0008-CurrentLevel'
  - 'getLevel' => 'zbReadAttribute' + 'clustId=0008&attrId=0000'
- Démarrage sans Zigate active: Ajout message + démarrage démons annulé.
- Page de config: Zigate Wifi: Correction message 'Port série de la zigate X INVALIDE ! Zigate désactivée'.
- Tuya TS0501B Led controller: Ajout support préliminaire (2199).
- Dimmer-Switch-ZB3.0_HZC: Support préliminaire (2200).

211122-BETA-1
-------------

- Illuminance: Correction cmde JSON 'zb-0400-MeasuredValue.json'.
- Mise-à-jour OTA: Support préliminaire.
- zb-0400/0402/0405-MeasuredValue.json: Correction calcul valeur.
- Philips Hue Wall switch: Ajout support préliminaire (2185).
- Equipements inconnus: Generation d'un "discovery.json" pendant l'inclusion. Suppression d'AbeilleDiscover.log.
- Programmateur Zigate: Correction: Compilation echoue si "tmp" n'existe pas.
- Orvibo ST30: Ajout support préliminaire (2193).
- Acova Alcantara: Mise-à-jour JSON pour 'Set-OccupiedHeatingPoint' (2180).
- JSON équipements: Nettoyage commandes obsoletes

  - 'temperature' => 'zb-0402-MeasuredValue'
  - 'bindToZigate' => 'zbBindToZigate'
  - 'luminositeXiaomi' => 'zb-0400-MeasuredValue'
  - 'getEtat' => 'zbReadAttribute'
  - 'humidite' => 'zb-0405-MeasuredValue'
  - 'on' => 'zbCmd-0006-On'
  - 'off' => 'zbCmd-0006-Off'
- JSON équipements: Ajout possibilité de surcharger 'minValue' & 'maxValue' pour widget slider.
- 1 chan switch module (TS0011, _TZ3000_ji4araar): Ajout JSON sur base TS0011 (2196).

211121-STABLE-1
---------------

- Acova Alcantara: Ajout support préliminaire (2180).
- Interne: Nettoyage AbeilleZigateConst.
- Interne: Correction CmdPrepare/WriteAttributeRequestGeneric. Impacte Danfoss Ally (1881).
- Ikea bulb E27 White Spectre opal 1055lm: Ajout support (2187).
- Moes ZSS-ZK-THL-C: Ajout support (2191).

211115-BETA-2
-------------

- Moniteur: Suppression message sur équipement inexistant (2186).
- Moniteur: Correction lancement démon.

211115-BETA-1
-------------

- Page de config: Correction bug écriture impossible adresse Wifi.

211107-BETA-1
-------------

- Page Abeilles: Fonctionalité 'scenes' cachée. Scénaris offrent l'équivalent.
- Identification modèles Tuya: Correction.
- Interne: AbeilleCmd, bind0030: Supression fonction prepare.
- Interne: AbeilleCmdPrepare: Correctif pour nmbre de params impair.
- Interne: getVersion => getZgVersion.
- JSON équipements: Amélioration syntaxe permettant de surcharger 'execAtCreationDelay'.
- Sonoff SNZB-02: JSON revu. 'TH01.json' supporte identifiants 'TH01' & '66666'.
- JSON équipements: Correction valeur minInterval & maxInterval (décimal => hexa).
- Page EQ/avancé: Support préliminaire cmds 41 & 42, cluster 1000/Commissioning.
- Silvercrest HG06106C light bulb: Ajout support (2050).
- Legrand 16AX: Mise-à-jour icone.

211030-BETA-1
-------------

- Tuya ZM-CG205 door sensor: Mise-à-jour JSON. Ajout 'ZoneStatus' (2165).
- Interne: Parser: Support réponse cluster 000A/Time, attrib 0007 + ...
- Xiaomi Aqara QBKG26LM: Ajout support (2174).
- Interne:

  - setTimeServer => setZgTimeServer.
  - getTimeServer => getZgTimeServer.
  - zgSetMode => setZgMode.
- Prise connectée TS0121 _TZ3000_8nkb7mof: Mise-à-jour JSON (2167).
- Interne: Parser:

  - Msg 0006-FD, msgAbeille() supprimé.
  - Msg 8030/bind response: revu.
- Tuya QS-Zigbee-C01 volet roulant: Correction image (2169).
- Identification modeles Tuya: modifié. Fabricant/vendeur obligatoire pour éviter de prendre mauvais JSON identifié par modèle seul.
- Silvercrest HG06337-FR: Mise-à-jour JSON pour groups & identify.

211030-STABLE-2
---------------

- JSON équipement: Amélioration syntaxe permettant de surcharger 'subType' ou 'unite'.
- Zlinky TIC: Diverses corrections dont lecture option tarifaire.
- Tuya repeteur zigbee RP280: Ajout support.
- Page de config

  - Options avancées: Nettoyage autorisé si test d'intégrité ok.
  - Partie mise-à-jour (vérification) caché. Pas assez fiable. A revoir.
  - Partie 'zigates' revue.
- Tuya ZM-CG205 door sensor: Mise-à-jour JSON (2165).
- Interne: Suppression entrée 'zigateNb' de la DB config.
- eWeLink ZB-SW01: Support préliminaire (2172).

211022-BETA-1
-------------

- **ATTENTION**: Format DB interne modifié. Restaurer sauvegarde si besoin de revenir à une version antérieure.
- Interne: DB équipement: 'modeleJson' => 'ab::jsonId'.
- Interne: Suppression 'archives'.
- Page config: Affichage version firmware complète (ex: 0004-0320).
- Commandes JSON: Suppression 'zbWriteAttribute-Temp'.
- Interne: Parser vers cmd: queues revisitées.
- Silvercrest HG06337: Mise-à-jour JSON (2168).
- Assistant de découverte: Améliorations.
- Tuya QS-Zigbee-C01 volet roulant: Ajout support (2169).
- JSON commandes: Suppression commandes obsoletes 'OnEpXX' & 'OffEpXX'.
- Prise Tuya TS0121__TZ3000_rdtixbnu: Correction RMS Voltage.
- Interne: 'configureReporting' revu pour support 'reportableChange'.
- Page EQ/avancé: Amélioration configureReporting pour support min, max & changeVal.
- Prise NIKO: JSON revisité pour réduire le nombre de reporting RMSvoltage (2003).
- Prise TS0121 _TZ3000_8nkb7mof: Mise-à-jour JSON (2167).

211019-BETA-1
-------------

- JSON commandes: Nettoyage. Suppression commandes obsolètes.
- Interne: decodeDataType(): ajout support enum8/enum16 + ieee.
- Interne: Parser: Support read attributes clusters 0015 & 0100.
- Tuya ZM-CG205 door sensor: Ajout support (2165).
- Test d'intégrité et nettoyage automatique à la mise-à-jour.

211019-STABLE-1
---------------

- Interne: Correction 'writeAttribute' + mise-à-jour reponse 8110.
- Tuya 4 buttons (TS004F__TZ3000_xabckq1v): Mise-à-jour support (2155).
- Commandes JSON: Suppression 'binToZigate-EPXX-0006' => obsolètes.
- JSON équipements: Ajout support multiple identifiants (ex: 'signalrepeater' & 'SignalRepeater').
- UseeLink prise SM-SO306: Mise-à-jour (2160).
- Zigate: plusieurs commandes supprimées => supportées dans page équipement/avancé.
- Interne: Parser: Améliorations decodeDataType().
- UseeLink prise SM-SO306: Ajout support (2160).
- Syntaxe JSON équipement: Ajout possibilité surcharge 'template'.
- Zlinky TIC: Mise-à-jour JSON + icone.
- Niko connected socket outlet: Mise-à-jour image.
- Page EQ/avancé: Ajout possibilité de configurer le reporting.
- Interne: Parser: Support 'configure reporting response' pour 0B04.
- TRADFRI bulb GU10 CWS 345lm: Correction icone E14 => GU10 (2137).
- Ikea Tradfri LED 470lm E14: Mise-à-jour config JSON (2111).
- Tuya 4 boutons: Mise-à-jour (2155).
- Interne: AbeilleCmd: Correction bin0030 vers groupe.
- SPLZB-131: Mise-à-jour JSON. Reporting activé. (2109).
- Page EQ/avancé: Ajout possibilité d'écrire un attribut.
- Interne: AbeilleCmd: Message d'erreur si pb lecture queues.
- Interne: Commandes JSON 'getEtatEpXX' deviennent obsoletes.
- Page EQ/avancé: correction bouton 'reconfiguer' + amélioration message.
- Interne: Zigbee const: corrections pour éviter warning PHP.
- Page équipements: Suppression zone developpeur (bas de page).
- Page EQ/avancé: Ajout interrogation des 'Active end points'.
- Interne: Parser: decodeDataType() revu pour types 41 & 42.
- Interne: Procedure d'inclusion avec support "cas speciaux".
- Suppression log obsolete AbeilleParser (sans '.log') lors de la mise-à-jour/installation.
- Page équipement: Message si équipement à disparu depuis l'ouverture de la page.
- Interne: Parser: Support remontée commandes du cluster 0300 en provenance d'un équipement.
- Osram smart switch mini: Mise-à-jour. Ne supporte que le controle vers Jeedom.

211004-STABLE-1
---------------

- Correction mauvaise config lors de l'inclusion si #IEEE# ou #ZigateIEEE# utilisé.
- Page santé: correction IEEE manquante pour Ruche.
- Inclusion: Correction pour vieux équipements type Xiaomi (ne repond pas à lecture attribut manufacturer).
- Interne: Identification périph revisitée.
- Assistant Zigbee: Correction & amélioration.
- Interne: Modification astuce identification vieux modeles Xiaomi basé sur IEEE. Non compatible ZLinky.
- Correction regression 'setModeHybride' + mise-à-jour commande interne.
- Assistant Zigbee: Ajout forcage EP01.
- Commandes JSON: nettoyage.
- Assistant Zigbee: Améliorations: ajout découverte etendue des attributs.
- ZLinky TIC: Support préliminaire.
- Interne: Parser: Nettoyage.
- Aqara SSM-U02: Correction icone.
- Xiaomi Mijia Honeywell Détecteur de Fumée: Tentative correction bouton test (2143).
- Ruban LED Lidl: Mise à jour JSON (1737).
- TRADFRIbulbGU10CWS345lm: Mise a jour (2137).
- Interne: Correction erreur PHP: Undefined index: battery_type in AbeilleLQI_Map.php
- Interne: Correction crash inclusion dans cas ou "value" pointe sur commande inexistante.
- Xiaomi smoke detector: bouton test genere crash d'AbeilleCmd (2143).
- Aqara TVOC moniteur d'air AAQS-S01: Support préliminaire (2135).

210922-STABLE-1
---------------

- Interne: Correction requete "discover attributes extended".
- Loratap 3 boutons: Correction regression (2138).
- Interne: Restoration support historique cmd 0006-FD special Tuya.
- TRADFRIbulbGU10CWS345lm support preliminaire.
- TRADFRIbulbE14CWS470lm support preliminaire.
- SPLZB-132: Correction EP.
- SPLZB-131: Correction RMSVoltage (2109).
- Interne: Tools: check_json amélioré.
- Interne: Parser: Support prélim data type 41, 42, E0, E1, E2.
- Suppression des messages de "réannonce" si équipement connu et activé.
   Attention. Si l'équipement quitte (leave) puis revient, le message est toujours présent.
- Legrand dimmer: Mise à jour JSON (983).
- Monitor: Correction bug (Device announce loupé).
- JSON équipements: Mise-à-jour setReport-EPxx => zbConfigureReporting.
- JSON équipements: Nettoyage setReport-EPxx => zbConfigureReporting.
- Assistant de découverte: améliorations et mise à jour doc.
- Interne: Parser: Amélioration pour découverte cluster 0005/Scenes.
- Interne: Correction warning pour "bind to group".
- Page EQ/avancé: ajout bouton "reset to factory".
- Interne: AbeilleCmdPrepare + Process updates.
- JSON équipements: Mise à jour des commandes du type 'toggle'.
- Page EQ/avancé: ajout possibilité de faire un "bind" vers équipement ou groupe.
- Parser: Correction table de binding.

210916-STABLE-1
---------------

- JSON commandes: 'trig' revu + 'trigOffset' ajouté.
- Niko connected socket: ajout support (2003).
- JSON commandes: suppression de qq cmds obsoletes.
- Interne: Optimisation parser: transfert groupé d'attributs.
- Interne: Link-Quality mis à jour sur attribut report.
- JSON équipements: Mise-à-jour commandes pourcentage batterie.
- Tuya inter 4 buttons: mise-à-jour support 'click' (2122).
- Interne: Améliorations parser + robustesse.
- Correction regression: crash pendant l'inclusion (2125).
- Zemismart TS0042 2 buttons (1272).
- Interne: Parser: Modificaiton support custom single/double/long click pour Tuya.
- Tuya 4 buttons scene switch: Mise-à-jour modèle (2122).
- Ajout FW 3.1e Optimized PDM + 3.20 legacy + 3.20 Optimized PDM
- Osram CLA60 TW: Correction end point par défaut (2117).
- Tuya contact sensor TS0203: Ajout reporting batterie (1270).
- Tuya 4 buttons scene switch: Ajout support (2122).
- Correction mauvais message: "ATTENTION: config locale utilisée ...".
- Interne: Améliorations parser.

210905-STABLE-1
---------------

- Améliorations assistant EQ.
- Interne: Séquence de démarrage revisitée pour #2093.
- Améliorations assistant EQ.
- Interne: SerialRead, mise-à-jour pour "permission denied".
- Frient SPLZB-131: Support préliminaire.
- Legrand Cable outlet: Ajout support préliminaire (850). Manque controle fil pilote.
- All JSON: 'configuration:battery_type' => 'configuration:batteryType'.
- Assistant: Ajout doc préliminaire pour découverte Zigbee.
- Interne: Séquence de démarrage revisitée pour #2093.
- Page config/options avancées: possibilité de bloquer requetes LQI journalieres.
- Assistant de découverte: améliorations.
- Interne: Collecte LQI: Mise-à-jour mineure.
- Interne: Parser: Amélioration robustesse.
- Ajout support Controleur Tuya LED DC5-24V (2082).
- Ajout Ampoule YANDHI E27 (2087)
- JSON équipements: tous modifiés
  - SW & getSWBuild => SWBuildID & Get-SWBuildID
- Interne: Ajout commande générique 'configureReporting'.
- Page gestion: Bouton 'exclure' pour tous.
- JSON commandes: suppression de commandes obsolètes.
- GL-S-003Z: Fichier JSON. Correction end point + divers (2104).
- Page EQ/avancé: ajout possibilité interroger LQI (Mgmt_Lqi_req).

210824-STABLE-1
---------------

- Xiaomi plug EU: JSON revisité (1578).
- Interne: SerialRead: Ouverture de port améliorée.
- Silvercrest HG06337-FR: Ajout prise Lidl (2053).
- JSON équipements: amélioration nouvelle syntaxe (ajout 'isVisible' & 'nextLine').
- Page EQ/avancé: mise-à-jour de l'assistant de découverte.
- Page config/options avancées: Support defines.
- Commande JSON 'temperatureLight': correction EP.
- Aqara Opple 6 boutons (2048).
- JSON équipements: tous mis à jour.
  - 'Categorie' remplacé par 'category'.
  - 'nameJeedom' remplacé par 'type'.
  - 'configuration:icone' remplacé par 'configuration:icon'.
  - mainEP #EP# remplacé par '01'.
  - 'uniqId' supprimé.
  - 'Commandes' => 'commands'
- Profalux: Ajout support nouvelle génération volet (id=MOT-C1Z06C/10C, #2091).
- Aqara WS-EUK01 H1 wall switch: ajout support préliminaire (2054).
- Interne: optimisations AbeilleCmdQueue.
- Page santé: ajout dernier niveau batterie.
- Gledopto GL-B-008Z: Correction main EP dans modèle JSON (2096).
- Gledopto GL-C-006: Modèle préliminaire (2092).
- Interne: Taille queue parser->LQI augmentée à 2048.
- Interne: Nettoyage: suppression queue obsolete 'queueKeyLQIToAbeille'.
- Interne: cron15: Lecture ZCLVersion au lieu d'un attribut pas toujours supporté.
- Page EQ/avancé: ajout bouton lecture attribut.
- Interne: tools: Amélioration check_json + update_json
- Modèle JSON telecommande profalux: correction syntaxe type batterie.
- Collecte LQI: Correctif parser pour routeur avec plus de 10 childs.
- Interne: Nettoyage: suppression fichier obsolète 'function.php'.
- JSON: Correction nom 'Konke multi-function button' pour id 3AFE170100510001.
- Ajout support 'Konke multi-function button' avec id 3AFE280100510001.

210719-STABLE-1
---------------

- ATTENTION: Format JSON des fichiers de commande modifié !
- Osram classic B40TW: support préliminaire.
- Xiaomi Luminosite: Ajout pourcentage batterie basé sur retour tension (1166).
- Interne: cron15 amélioré. Pas d'interrogation si eq appartient à zigate inactive.
- Inclusion: support revu pour périph qui quitte puis revient sur le réseau.
- Moniteur: Disponible pour tous et plus seulement en mode dev.
- Firmware 3.1e disponible pour tous.
- JSON commandes: Mise-à-jour syntaxe fichier de commande
  - 'order' supprimé (obsolète)
  - 'uniqId' supprimé (obsolète)
  - 'Type' renommé en 'type'
  - Ajout 'topic' si manquant pour commandes 'info'.
  - Correction clef top commandes 'info' (clef = nom de fichier).
- JSON équipements: Support préliminaire pour directive "use"
  - Ex: "cmd_jeedom_name": { "use": "cmd_file_name", "params": "EP=01" }
- Page EQ/avancé: ajout du bouton "reconfigurer".
- Page gestion: suppression du bouton "Apply Settings to NE".
- Page EQ/avancé: version préliminaire de l'assistant de découverte.
- Correction ecrasement widget commande (2075).
- Interne: plusieurs améliorations pour robustesse et support d'erreurs.
- Page EQ/avancé: Ajout bouton "interroger table routage"
- Page EQ/avancé: Ajout bouton "interroger table binding"
- Page EQ/avancé: Ajout bouton "interroger config reporting"
- JSON: Syntaxe commandes modifiée. Type 'execAtCreationDelay' changé en 'nombre' et non plus 'string'.
  Devrait corriger le pb de config de certains equipements à l'inclusion.
- Correction perte categorie & icone si equipement se réannonce.
- Correction perte état historisation & affichage des commandes si equipement se réannonce.
- Correction mise-à-jour commandes IEEE-Addr, Short-Addr & Power-Source sur réannonce.
- Page santé: Ajout "dernier LQI" à la place de "Date de création".
- Interne: Meilleur support des JSON avec mainEP=#EP#.

210704-STABLE-1
---------------

- Modifications syntaxe JSON équipement (avec support rétroactif temporaire):
  - 'commands' au lieu de 'Commandes'.
  - 'category' au lieu de 'Categorie'
  - 'icon' au lieu de 'icone'
  - 'batteryType' au lieu de 'battery_type'
- Support JSON equipement: correction pour support multiple categories.
- Page EQ/avancé: recharger JSON accessible à tous.
- Page EQ: type de batterie déplacé vers principale.
- Interne: plusieurs améliorations de robustesse.
- Interne: Parser: Support des messages longs.
- Zigate WIFI: Correction & amélioration arret/démarrage démon.
- Equipement/categories: correction. Effacement categorie résiduelle.
- Trafri motion sensor: Mise-à-jour JSON pour controle de groupe.
- Gestion des démons: correctif sur arret forcé (kill).
- Interne: Parser: affichage erreurs msg_receive().
- Interne: SerialRead: affichage erreur msg_send().
- Interne: Serial to parser: Messages trop grands ignores pour ne plus bloquer la pile + message d'erreur.
- Batterie %: Parser renvoi valeur correcte pour 0001-EPX-0021 + Abeille.class + update JSON (2056).
  Peut nécessiter de recharger JSON.
- Batterie %: Report dans Jeedom de tous les "end points" et pas seulement 01.

210620-STABLE-1
---------------

- SPLZB-132: Correction icone + ajout somme conso.
- Correction remontée cluster 0B04 (mauvais EP).
- Orvibo CM10ZW multi functional relay: mise-à-jour icone.
- Nettoyage de vieux fichiers log au démarrage.
- Socat: Amélioration pour avoir les messages d'erreur.
- Interne: Restauration option avancées de récupération des équipements inconnus.
- Xiaomi v1: Correction regression inclusion. Trick ajouté pour Xiaomi.
- Orvibo CM10ZW multi functional relay: support préliminaire.
- Page santé: Correction mauvais affichage du status des zigates.
- Page equipement/avancé: Possibilité de recharger dernier JSON (et mettre à jour les commandes) sans refaire d'inclusion.
- Interne: Suppression AbeilleDev.js (mergé dans Abeille.js).
- Page équipement: Correction rapport d'aspect image (icone).
- Interne: Parser: Support msg 9999/extended error.
- Interne: Parser: Qq améliorations découverte nouvel équipement.
- Interne: SerialRead: Boucle et attend si port disparait au lieu de quitter (2040).
- Page gestion: Correction regression groupes.

210610-STABLE-3
---------------

- Philips E27 single filament bulb: Ajout modele LWA004
- Interne: Correction ReadAttributRequest multi attributs
- Interne: Correction 'zgGetZCLStatus()'
- Frient SPLZB-132 Smart Plug Mini: Ajout support préliminaire.
- Interne: Correction eqLogic/configuration. Suppression des champs obsolètes lors de la mise-à-jour de l'équipement.
- Tuya 4 buttons light switch (ESW-0ZAA-EU): support préliminaire (1991).
- Tuya smart socket: Ajout support modele générique 'TS0121__TZ3000_rdtixbnu'.
- Telecommande virtuelle: Correction regression. Plusieurs télécommandes par zigate à nouveau possible (2025).
- Lancement de dépendances: correction erreur (2026).
- Exclusion d'un équipement du réseau: En mode dev uniquement. Nouvelle version.
- Zigate wifi: correction regression.

210607-STABLE-1
---------------

- ATTENTION: Regression sur les telecommandes virtuelles. Une seule possible avec cette version.
- ATTENTION: Faire un backup pour pouvoir revenir à la precedente "stable". Structure DB eqLogic modifiée: "Ruche" remplacé par "0000"
- Interne: Parser: revue params decodeX() + cleanup
- Zemismart ZW-EC-01 curtain switch: mise-à-jour modèle.
- Interne: Correction timeout.
- Reinclusion: L'equipement et ses commandes sont mis à jour. Seules les commandes obsolètes sont détruites.
  Ca permet de ne plus casser le chemin des scénaris et autres utilisateurs des commandes.
- Firmware: Suppression des FW 3.0f, 3.1a & 3.1b. 3.1d = FW suggéré.
- JennicModuleProgrammer: Mise-à-jour v0.7 + améliorations. Compilé avant utilisation.
- Zigate DIN: Ajout support pour mise-à-jour FW.
- Page equipement: section "avancé", mise à jour des champs en temps réel (1839).
- Gestion des groupes: correction regression (2011).
- Telecommande virtuelle: correction regression (2011).
- Interne: Revue decode 8062 (Group membership).
- JSON: Correction setReportTemp (1918).
- Innr RB285C: correction modele corrompu.
- Innr RB165: modele préliminaire.
- Tuya GU10 ZB-CL01: ajout support.
- Hue motion sensor: mise-à-jour JSON.
- Interne: correction message 8120.
- Page config: correction installation WiringPi (1979).
- Introduction de "core/config/devices_local" pour les EQ non supportés par Abeille mais locaux/en cours de dev.
- Zemismart ZW-EC-01 curtain switch: ajout du modèle JSON
- Nouvelle procédure d'inclusion.
- Support des EQ avec identifiants 'communs'.
- Création du log 'AbeilleDiscover.log' si inclusion d'un équipement inconnu.
- Profalux volet: Revue modele JSON. Utilisation cluster 0008 au lieu de 0006 + report.
- Page EQ/commandes: pour mode developpeur, possibilité charger JSON.
- Ordre apparition des cmdes: Suit maintenant l'ordre d'utilisation dans JSON equipement.
- Un équipement peut maintenant être invisible par defaut ('"isVisible":0' dans le JSON).
- Profalux telecommande: S'annonce mais inutilisable côté Jeedom. Cachée à la création.

210510-STABLE-1
---------------

- Page compatibilité: revisitée + ajout du tri par colonne
- Page santé: ajout de l'état des zigate au top
- Sonoff SNZB-02: support corrigé + support 66666 (ModelIdentifier) (1911)
- Xiaomi GZCGQ01LM: ajout support tension batterie + online (1166)
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

11/12/2020
----------

- Prise Xiaomi: fonctions de base dans le master (On/Off/Retour etat). En cours retour de W, Conso, V, A et T°.
- LQI collect revisited & enhanced #1526
- Ajout du modale Template pour afficher des differences entre les Abeilles dans Jeedom et leur Modele.
- Ajout d un chapitre Update dans la page de configuration pour verifier que certaines contraintes sont satisfaites avant de faire la mise a jour.
- Ajout Télécommande 3 boutons Loratap #1406
- Contactor 20AX Legrand : Pilotage sur ses fonctions ON/OFF et autre
- Prise Blitzwolf
- Detecteur de fumée HEIMAN HS1SA-E
- TRADFRIDriver30W IKEA

Beta 24/11/2010
---------------

Surtout faites un backup pour pouvoir revenir en arrière.

- Premiere version Abeille qui prend en charge le firmware Zigate 3.1d.
- J'ai passé toutes mes zigates en 3.1D (Je ne vais plus tester les evolutions d'Abeille avec les anciennes versions du firmware).
- Essayez de passer sur le firmware 3.1D quand vous pourrez. Perso je vous recommande de faire un erase EEPROM lors de la mise à jour puis de faire un re-inclusion de tout les modules. Attention c'est une operation assez lourde. Assurez vous d'avoir l'adresse IEEE dans la page santé avant de faire cette operation.
- Dans la page de configuration il y a un nouveau paramètre: "Blocage traitement Annonces". Par défaut il doit être sur Non. Il semble qui ai une certaine tendance à ce mettre sur Oui. Mettez le bien sur Non et redémarrer le Démon.
- si vous restez avec une zigate en firmware inférieur à 3.1d, ne surtout pas passer la zigate en mode hybride.

- Tous les details dans:
https://github.com/KiwiHC16/Abeille/commits/beta?after=61b027c84f673f484073d6f0a73ad0bad08fef0d+34&branch=beta

12/2019 => 03/2020
------------------

::

    !!!! Le plugin a été testé avec 1 ou 5 Zigate dans le panneau de configuration  !!!!
    !!!! Avec d'autres valeur 2, 3, 4, 6... il est for possible que tout            !!!!
    !!!! ne fonctionne pas comme prévu. Si vous avez 2 zigate, mettez le nombre de  !!!!
    !!!! zigate à 5 et activer uniquement les zigates presentent.                   !!!!

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

Attention: cette nouvelle version apporte:

* le multi-zigate
* la gestion de queue de message avec priorité
* quelques équipements supplémentaires
* corrections de bugs

Mais comme les changements sont importants et que j'ai pas beaucoup de temps pour tester il peut y avoir des soucis. Donc faites bien une sauvegarde pour revenir en arrière si besoin.

A noter:

* La partie graphs n'a pas été complètement vérifiée et il reste des soucis
* les timers ne sont plus dans le Plugin
* un bug critique si vous faites l inclusion d'un type d equipement inconnu par Abeille, il faut redemarrer le demon.


06/2019 => 11/2019
------------------

Rien de spécifique à faire. Juste a faire la mise à jour depuis jeedom.

03/2019 => 06/2019
------------------

Rien de spécifique à faire.

02/2019 => 03/2019
------------------

Rien de spécifique à faire. Pour les évolution voir le changelog ci dessous.

01/2019 => 02/2019
------------------

Cette version est en ligne avec le firmware 3.0f de la Zigate
Vous pouvez utiliser un firmware plus vieux mais tout ne fonctionnera pas. (98% fonctionnera)

Comment procéder:

* Mettre à jour le plugin Abeille
* Flasher la Zigate avec le firmware 3.0f (*bien faire un erase de l'EEPROM*)
* Connecter la Zigate et démarrer le deamon abeille
* démarrer le réseau Zigbee depuis abeille
* mettre la Zigate en inclusion
* inclure vos routeurs en partant des plus proches au plus lointain
* Vérifier que tout fonctionne
* Inclure les équipements sur piles (dans l'ordre que vous voulez)

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

11/2018 => 01/2019
------------------

Cette mise à jour est importante et délicate. Pour facilité l'intégration de nouveaux équipements par la suite une standardisation des modèles doit être faite.
Cela veut dire que tous les modèles changent et que le objets dans Abeille/Jeedom doivent être mis à jour.
Prévoir du temps, avoir bien fait les backup, et prévoir d'avoir à faire quelques manipulations à la main. Les situations rencontrées vont dépendre de l'historique des équipements dans Jeedom.

::

    !!!! Ne pas faire ces manipulations sans avoir fait de backup !!!!

Solution pour les petits systèmes

* Cela suppose que vous aller effacer (objets, historiques,...) toutes les données puis re-créer le réseau.
* supprimer le plug in Abeille
* Installer le plug in Abeille depuis le market (ou github)
* Activer et faire la configuration du plugin
* Démarrer le plugin
* Mettre en mode inclusion
* Appairer les devices.

Solution pour les gros systèmes

Si la solution précédente demande trop de travail, on peut faire la mise à jour de la façon suivante. Attention, je ne peux pas tester toutes les combinaisons et des opérations supplémentaires seront certainement nécessaires. 90% aura été fait automatiquement.
Il n'y a pas de moyen infaillible pour faire la correspondance entre une commande dans un modèle et une commande dans Jeedom. Le lien est fait soit par le nom dans la commande nom ou quand pas disponible par le nom de l'image utilisée pour le device. De même pour les commande le nom est le moyen de faire le lien. Si vous avez fait des changements de nom, les commandes sortiront en erreur et cela demandera de mettre le nom de la commande dans le modèle le temps de la conversion.
Dans les versions suivantes, nous ne devrions plus avoir ce problème car les commandes auront un Id unique et spécifique.

* Mettre à jour la plugin avec le market (ou github)
* Vérifier la configuration du plugin et démarrer le plugin en mode debug.
* Demander la mise à jour des objets depuis les templates, bouton: "Appliquer nouveaux modèles"
* 90% des objets devraient être à jour maintenant.
* Tester vos équipements.

Si un équipement ne fonctionne pas, appliquer de nouveau la mise a jour sur cet équipements uniquement. Pour ce faire dans la page Plugin->Protocol Domotique->Abeille, sélectionnez le device et clic sur bouton: "Apply Template". Ensuite regarder le log "Abeille_updateConfig" pour avoir le détails des opérations faites et éventuellement voir ce qui n'est pas mis à jour.

vous allez trouver des messages:

* "parameter identical, no change" qui indique que rien n'a été fait sur ce paramètre (déjà à jour).
* "parameter is not in the template, no change" qui indique que le paramètre de l'objet n'est pas trouvé dans le template. Soit il n'est plus nécessaire et ne sera donc pas utilisé, soit vous l'avez changé et on le garde, soit Jeedom a défini une valeur par défaut et c'est très bien ...
* "Cmd Name: nom ===================================> not found in template" qui indique qu'on ne trouve pas le template pour la commande et que donc la commande n'est pas mise à jour. Ça doit être les 10% à gérer manuellement. Dans ce cas, soit effacer l'objet et le recréer soit me joindre sur le forum.

Équipements qui sont passés sans soucis sur ma prod:

  * Door Sensor V2 Xiaomi
  * Xiaomi Smoke
  * Télécommande Ikea 5 boutons
  * Xiaomi Présence V2
  * Xiaomi Bouton Carré V2
  * Xiaomi Température Carré
  * ...

Cas rencontrés:

* plug xiaomi, une commande porte le nom "Manufacturer", doit être remplacé par "societe" et appliquer de nouveau "Apply Template"
* interrupteurs muraux Xiaomi: si la mise a jour ne se fait, il faut malheureusement, supprimer et recréer.
* door sensor xiaomi V2 / xiaomi presence V1: une commande porte le nom "Last", doit être remplacé par "Time-Time", et "Last Stamp" par "Time-Stamp"
* ...

Secours

* Si rien n'y fait, aucune des deux solutions précédentes ne résout le soucis, vous pouvez probablement exécuter la méthode suivante sur un équipement (je ne l'ai pas testée):
* supprimer la commande IEEE-Addr de votre objet.
* Zigate en mode inclusion et re-appairage de l'équipement
* un nouvel objet doit être créé.
* Transférer les commandes de l'ancien objet vers le nouveau avec le bouton "Remplacer cette commande par la commande"
* Transférer l'historique des commandes avec le bouton "Copier l'historique de cette commande sur une autre commande"
* Vous testez le nouvel équipement
* si ok vous pouvez supprimer l'ancien.

Bugs
----

Il est fort probable que des bugs soient découverts.

Dans ce cas aller voir le forum: `FORUM <https://community.jeedom.com/tag/plugin-abeille>`_

ou issue dans GitHub: `ISSUE <https://github.com/KiwiHC16/Abeille/issues?utf8=✓&q=is%3Aissue+>`_

Changelog
---------

En fait le ChangeLog est dans GitHub alors je perds mon temps a essayer de la mettre a jour dans cette doc. Je ne fais plus de mise à jour ou que des principales choses quand j'ai le temps.

Voir directement dans `GitHub <https://github.com/KiwiHC16/Abeille/commits/master>`_


2019-11-25
----------

Ce dernières semaines le focus a été sur:
- Compatibilité avec Jeedom V4 et Buster (Debian 10)
- mise en place de la gestion des messages envoyés à la zigate avec creation de fil d'attente.
- Repetition d'un message vers la zigate si elle dit n'avoir pas réussi à le gérer
- Refonte de la détection de équipements lors de l inclusion
- Store et Télécommande Store Ikea
- Demarrage automatique du réseau Zigbee
- Iluminize Dimmable 511.201
- Iluminize 511.202
- Osram Smart+ Motion Sensor
- Télécommande OSRAM
- Ajout ampoules INNR RF263 et RF265
- Corrections de bugs
- .....

2019-03-19
----------

* Motion Hue Outdoor integration
* Doc Hue Motion
* Hue Motion Luminosite

2019-03-18
----------

* Plus de doc sur la radio
* Modification modele sur EP

2019-03-17
----------

* Resolution sur un systeme en espagnole

2019-03-16
----------

* start to track APS failures
* dependancy_info debut des modifications

2019-03-15
----------

* Moved all doc to asciidoc format
* Few correction around modele folder

2019-03-11
----------

* Ajout capteur IR Motion Hue Indoor

2019-03-01
----------

* Inclusion de la PiZiGate
* Possibilité de programmer le PiZiGate

2019-02-27
----------

* OSRAM SMART+ Outdoor Flex Multicolor
* Eurotronic Spirit

2019-02-15
----------

* Correction probleme volet profalux


2019-02-14
----------

* Amelioration de la doc
* Inclusion dans appli web mobile

2019-02-11
----------

* Amelioration de la doc.
* Reduction log sur annonce
* Prise Xiaomi Encastrée

2019-02-07
----------

* Mise en place de la cagnotte
* Correction de l affichage des icones sur filtre
* Amélioration retour Tele Ikea

2019-02-06
----------

* Récupération des groupes dans la Zigate
* Configuration du groupe de la remote ikea On/off depuis abeille
* Formatting of Livolo Switch
* Groupe commande Chaleur ampoule
* GUI to set group to Zigate
* TxPower Command
* Channel setMask and setExtendedPANID added
* Télécommande Ikea Bouton information to Abeille
* Certification configuration
* Led On/Off

2019-02-04
----------

* Get Group Membership response modification avec source address for 3.0.f
* Fix Sur mise a jour des templates il manque la mise a jour des icônes
* OSRAM Spot LED dimmable connecté Smart+ - Culot GU5.3
* Now default Zigbee object type could be used to create object in Abeille
* TRADFRIbulbE27WSopal1000lm
* MQTT loop improvement so Abeille should be more reactive
* nom du NE qui fait un Leave dans le message envoyé à la ruche
* Ampoule Hue Flame E14
* Info move from Ruche to Config page
* A bit more decoding of Xiaomi Fields
* channel mak and ExtPAN setting
* Ajout du Switch Livolo 2 boutons
* Affichage Commande au démarrage
* ClassiA60WClear second modèle added
* setTimeServer / getTimeServer

2019-01-25
----------

* Ajout commande scene
* Deux petites vidéos pour les docs
* Ajout des scènes et groupes de scènes
* Ajout ampoule LWB004
* Osram - flex led rgbw
* Osram - garden led rgbw
* GLEDOPTO Controller RGB+CCT
* Ajout de gestion du time server (cluster)

2019-01-15
----------

* retrait de pause pour avoir un plugin plus réactif
* LCT001 modèle ajouté
* LTW013 Philips Hue modèle ajouté
* Ajout modèle lightstripe philips hue plus modèle ajouté
* doc télécommande Hue
* Ajout LTW010 ampoule Hue White Spectre
* Ajout de la liste des Abeille ayant un groupe avec leur groupe
* LCT015 Bulb Added
* Add Address IEEE in health table

2018-12-15
----------

* Graph LQI par distance
* télécommande carré Ikea On/Off
* fix température carré xiaomi
* Télécommande Hue retour Boutons vers Abeille (scénario)

2018-12-11
----------

* Toute la doc sous le format Jeedom

2018-12-10
----------

* Ampoule Couleur Standard ZigBee
* Ampoule Dimmable Standard ZigBee

2018-12-09
----------

* Ampoule Spectre Blanc Standard ZigBee
* Blanche Ampoule GLEDOPTO GU10 Couleur/White GLEDOPTO avec hombridge
* Spectre Blanc Ampoule GLEDOPTO GU10 GL-S-004Z avec hombridge
* Retour des volets profalux en automatique
* Poll Automatique
* Ajout/Suppression/Get des groupes depuis l interface Abeille

2018-12-08
----------

* Couleur Ampoule GLEDOPTO GU10 Couleur/White GL-S-003Z avec hombridge

2018-12-07
----------

* Couleur Ampoule Ikea avec Homebridge
* Couleur Ampoule OSRAM avec Homebridge
* Couleur Ampoule Hue Go avec Homebridge

2018-12-05
----------

* Ajout d un paramètre Groupe dans la configuration des devices pour avoir la groupe a commander. Il n'est plus besoin de changer les commandes une à une.

2018-12-04
----------

* passage aux modèles standardisés (avec include)
* les modèles standardisés permettent de modifier les équipements dans Jeedom sans les effacer et donc sans perdre historique, scénarios associés,...
* ajout des boutons pour appliquer de nouveau les modèles de device
* introduction d'Id unique dans les template pour ne pas confondre les devices par la suite.

2018-01-12
----------

* Ampoule GLEDOPTO White intégrée

2018-11-30
----------

* Prise Ikea intégrée
* Ajout des groupes aux devices sélectionnés

2018-11-26
----------

* Ikea Transformer 30W intégré

2018-11-24
----------

* Correction TimeOut (en min)

2018-11-16
----------

* Activation/Désactivation d'un équipement suivant qu'il joint le réseau ou le quitte.
* Rafraichi les informations de la page Health à l'ouverture.

2018-11-05
----------

* Ajout OSRAM GU10

2018-06-14
----------

* Ajout de la connectivité en Wifi.
* Ajout des LQI remontant des trames Zigate

2018-06-12
----------

* Ajout du double interrupteur mural sur pile xiaomi.
* Network modal (graph automatique du reseau)
* Ajout aqara Cube

2018-06-11
----------

* Stop for Volet Profalux =253

2018-06-01
----------

* Profalux Volets Calibration

2018-05-30
----------

* Inclusion status dans le widget mis à jour en fonction de l’etat de la Zigate

2018-05-28
----------

* Ajout des equipements DIY

2018-01-19
----------

* first version posted on github
* inclus la création des objets IKEA Bulb et Xiaomi Plug, Température Carre/rond, bouton et InfraRouge
