#!/bin/bash

# Generate 'Abeille.md5' for key files, then add/commit.
# - Requires GIT.
# - Must be executed from Abeille root directory.
# - Must be the LAST commit before the push.

OUT=plugin_info/Abeille.md5

# Commit ready. Generating md5 for versionned files
echo "Updating MD5 checksum..."
echo "# Auto-generated Abeille's MD5 file. DO NOT MODIFY !" >${OUT}
VERSION=`cat plugin_info/Abeille.version | tail -1`
echo "- Version: ${VERSION}"
echo "# VERSION=\"${VERSION}\"" >>${OUT}

# Note: take care about such following file name
#   'core/config/IAS ACE Cluster  IAS Zone & Diagnostics Only_ZHA.xml'

git ls-files |
while read -r F
do
    # Ignoring checksum for the following files
    # - plugin_info/Abeille.md5
    # - 'core/config/devices_local' content except README/LISZEMOI
    # - 'core/config/commands/OBSOLETE'
    # - All '.xxx' or '.bak' files
    if [[ "${F}" = *"Abeille.md5" ]] || [[ "${F}" = *"TODO.txt" ]]; then
        echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
        continue
    fi
    if [[ ${F} = "."* ]] || [[ ${F} = *".bak" ]]; then
        echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
        continue
    fi
    if [[ "${F}" = "tmp/"* ]] || [[ "${F}" = "docs/dev/"* ]]; then
        echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
        continue
    fi
    if [[ "${F}" = "resources/archives"* ]]; then
        echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
        continue
    fi
    # 'core/config/devices_local' fully excluded except the following files
    if [[ "${F}" = "core/config/devices_local/"* ]]; then
        if [[ "${F}" != *"LISEZMOI.txt" ]] && [[ "${F}" != *"README.txt" ]]; then
            echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
            continue
        fi
    fi

    # echo "F=$F"
    if [ ! -e "${F}" ]; then
        echo "- ERROR: File does not exist: ${F}"
        continue
    fi
    md5sum "${F}" >> ${OUT}
done

# REMINDER: 'Abeille.md5' is now updated but must be added/committed & pushed.

exit 0
