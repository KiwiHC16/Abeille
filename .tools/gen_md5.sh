#!/bin/bash

# Generate 'Abeille.md5' for key files, then add/commit.
# - Requires GIT.
# - Must be executed from Abeille root directory.
# - Must be the LAST commit before the push.

OUT=plugin_info/Abeille.md5

# Commit ready. Generating md5 for versionned files
echo "Generating MD5 checksum for key files..."
echo "# Auto-generated Abeille's MD5 file. DO NOT MODIFY !" >${OUT}
VERSION=`cat plugin_info/Abeille.version | tail -1`
echo "- Version: ${VERSION}"
echo "# VERSION=\"${VERSION}\"" >>${OUT}

# Note: take care about such following file name
#   'core/config/IAS ACE Cluster  IAS Zone & Diagnostics Only_ZHA.xml'

git ls-files |
while read -r F
do
    # Ignoring checksum file
    # - plugin_info/Abeille.md5
    # Ignoring the following files for checksum to speedup
    # - All 'xxx.png'
    # - 'resources/archives' content
    # - All '.xxx' files
    if [[ ${F} = *"Abeille.md5" ]]; then
        echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
        continue
    fi
    if [[ ${F} = *".png"* ]] || [[ ${F} = "resources/archives"* ]] || [[ ${F} = "."* ]]; then
        echo "xxxxxxxxx-md5-skipped-xxxxxxxxxx *${F}" >> ${OUT}
        continue
    fi

    # echo "F=$F"
    md5sum "${F}" >> ${OUT}
done

# REMINDER: 'Abeille.md5' is now updated but must be added/committed & pushed.

exit 0
