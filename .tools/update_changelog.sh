#!/bin/bash

# Update changelog to add version

CL=docs/fr_FR/changelog.rst
TMP=docs/fr_FR/changelog.rst.tmp

echo "Updating changelog..."

VERSION=`cat plugin_info/Abeille.version | tail -1`
STEP=0
cat ${CL} |
while IFS= read -r L
do
    # ChangeLog
    # =========
    #
    # - JSON: Correction setReportTemp (#1918).

    # ChangeLog
    # =========
    #
    # VERSION X
    # ---------
    #
    # - JSON: Correction setReportTemp (#1918).

    if [ ${STEP} -eq 0 ]; then
        echo "${L}" >> ${TMP}
        if [[ ${L} == "==="* ]]; then
            STEP=1 # Title found
        fi
    elif [ ${STEP} -eq 1 ]; then
        if [[ "${L}" == "- "* ]]; then
            # It's a list (starts with '- '). Need to add version title (# VERSION)
            echo "${VERSION}" >> ${TMP}
            echo "----------" >> ${TMP}
            echo "" >> ${TMP}
            echo "${L}" >> ${TMP}
        else
            # remove trailing whitespace characters
            L="${L%"${L##*[![:space:]]}"}"
            if [ "${L}" == "" ]; then
                echo >> ${TMP}
                continue
            fi
            # Assuming 'VERSION' line + '------'
            if [[ "${L}" == *"${VERSION}"* ]]; then
                echo "- There is already correct version in changelog. Doing nothing"
                echo
                rm ${TMP}
                break
            fi
            # Adding current version
            echo "${VERSION}, ${L}" >> ${TMP}
        fi
        STEP=2 # Copy all remaining lines
    else # STEP==2
        echo "${L}" >> ${TMP}
    fi
done

if [ -e ${TMP} ]; then
    rm ${CL}
    mv ${TMP} ${CL}
fi

exit 0
