***
*** Abeille plugin
*** 'devices_local' directory for user/custom models
***

This directory allows to store "local" or user devices that are not yet officially supported by Abeille.
So this is the location where to put devices under creation.

This directory is NOT CLEANED during plugin update.

During inclusion phase, Abeille will
- first look in this 'devices_local'
- then in official directories ('devices', then 'devices_legacy')
- and finally if still not found, will use 'unknownDefault.json' model

This directory must follows the structure
    <signature>/<signature>.json

File format
===========

Exemple: BASICZBR3.json

  {
    "BASICZBR3": {
      "type": "Sonoff smart switch",
      "manufacturer": "Sonoff",
      "model": "BASICZBR3",
      "alternateIds": {
        "BASICZBR30": {
          "manufacturer": "if-different-from-top",
          "model": "if-different-from-top",
          "icon": "if-different-from-top",
          "type": "if-different-from-top"
        }
      },
      "timeout": "60",
      "category": {
        "automatism": "1"
      },
      "configuration": {
        "icon": "BASICZBR3",
        "mainEP": "01"
      },
      "commands": {
        "Groups": { "use": "Group-Membership" },

        "Status": { "use": "zb-0006-OnOff", "isVisible": 1, "nextLine": "after" },
        "On": { "use": "On", "isVisible": 1 },
        "Off": { "use": "Off", "isVisible": 1, "nextLine": "after" },
        "Get-Status": { "use": "zbReadAttribute", "params": "clustId=0006&attrId=0000" }
      }
    }
  }
