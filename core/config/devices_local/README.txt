***
*** Abeille plugin
*** 'devices_local' directory for user/custom models
***

This directory allows to store "local" or user devices that are not yet officially supported by Abeille.
So this is the location where to put devices under creation.

This directory is NOT CLEANED during plugin update.

During inclusion phase, Abeille will
- look first in official directories ('devices', then 'devices_legacy')
- then in this 'devices_local'
- and finally if still not found, will use 'unknownDefault.json' devices

This directory must follows the structure
    <modelIdentifier>/<modelIdentifier>.json
