#!/bin/bash

# Update changelog to add version

CL=docs/fr_FR/changelog.md
TMP=docs/fr_FR/changelog.md.tmp

echo "Updating changelog..."

VERSION=`cat plugin_info/Abeille.version | tail -1`
STEP=0
cat ${CL} |
while read -r L
do
    # Checking if any changes added.
    #   If "# xxx" is followed by "# yyy" then no change
    #   If "# xxx" is followed by "- yyy" then version to be added
    
    if [[ ${L} != "# "* ]] && [[ ${L} != "- "* ]]; then
        echo "${L}" >> ${TMP}
        continue
    fi
    
    if [ ${STEP} -eq 0 ]; then
        echo "${L}" >> ${TMP}
        STEP=1
    elif [ ${STEP} -eq 1 ]; then
        if [[ ${L} == "# "* ]]; then
            echo "- There is already a version in changelog. Doing nothing"
            echo
            rm ${TMP}
            break
        fi
        # It's a list. Need to add version title (# VERSION)
        echo "# ${VERSION}" >> ${TMP}
        echo "" >> ${TMP}
        echo "${L}" >> ${TMP}
        STEP=2
    else
        echo "${L}" >> ${TMP}
    fi
done

if [ -e ${TMP} ]; then
    rm ${CL}
    mv ${TMP} ${CL}
fi

exit 0
