# Abeille

Plugin Jeedom pour le contrôle de réseaux ZigBee via passerelles ZiGate et EZSP (Silicon Labs).

## Fonctionnalités

- Support multi-passerelles (jusqu'à 6 simultanément)
- 418+ types d'équipements pré-configurés
- Mise à jour OTA des firmwares
- Cartographie réseau et diagnostic LQI
- Support des clusters privés Tuya, Xiaomi, Legrand, OSRAM, Philips

## Prérequis

- Jeedom 4.0 ou supérieur
- Python 3 avec pyserial
- Passerelle ZiGate ou compatible EZSP
- Pour PiZigate : package pigpio (`sudo apt-get install pigpio python3-pigpio`). Le démon `pigpiod` est démarré automatiquement si nécessaire.

## Documentation

- Documentation complète : https://kiwihc16.github.io/AbeilleDoc
- Équipements supportés : https://kiwihc16.github.io/AbeilleDoc/devices/CompatibilityList.html
- Forum : https://community.jeedom.com/tag/plugin-abeille

## Développement

### Outils de validation

```bash
# Valider les fichiers JSON de configuration
php .tools/check_json.php

# Mettre à jour et normaliser les fichiers JSON
php .tools/update_json.php

# Générer la liste de compatibilité des équipements
php .tools/gen_devices_list.php
```

### Gestion des versions

```bash
# Créer une branche beta (master → beta) ou stable (beta → stable)
.tools/create_branch.sh

# Mettre à jour les checksums MD5
.tools/update_md5.sh

# Mettre à jour le changelog
.tools/update_changelog.sh
```

### Hooks Git

Installer les hooks pour la gestion automatique des versions :

```bash
.hooks/install_hooks.sh
```

## Licence

GNU AGPL v3 - Voir [LICENSE.md](LICENSE.md)

## Auteurs

KiwiHC16 / Tcharp38
