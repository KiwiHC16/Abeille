#!/bin/bash

# Update changelog to add version

CL=docs/fr_FR/changelog.md
TMP=docs/fr_FR/changelog.md.tmp

echo "Updating changelog..."

VERSION=`cat plugin_info/Abeille.version | tail -1`
STEP=0
cat ${CL} |
while IFS= read -r L
do
    # # ChangeLog (title)
    #
    # - JSON: Correction setReportTemp (#1918).

    # # ChangeLog (title)
    #
    # ## VERSION X
    #
    # - JSON: Correction setReportTemp (#1918).

    # remove trailing whitespace characters
    L="${L%"${L##*[![:space:]]}"}"

    if [ ${STEP} -eq 0 ]; then
        echo "${L}" >> ${TMP}
        if [[ ${L} == "# "* ]]; then
            STEP=1 # Title found
        fi
    elif [ ${STEP} -eq 1 ]; then
        # Empty line ?
        if [ "${L}" == "" ]; then
            echo >> ${TMP}
            continue # Continue until version updated
        fi

        # Version line
        # Note: '## VERSION1' or '## VERSION2, VERSION1'
        if [[ "${L}" == "## "* ]]; then
            if [[ "${L}" == *"${VERSION}"* ]]; then
                echo "- Version is already correct in changelog. Doing nothing"
                echo
                rm ${TMP}
                break
            fi
            echo "- Updating version to ${VERSION}"
            VERSIONOLD=${L#\#\# }
            echo "## ${VERSION}, ${VERSIONOLD}" >> ${TMP}
            STEP=2 # Copy rest of the file
            continue
        fi

        # Anything but a version line
        echo "- Adding version ${VERSION}"
        # Adding current version
        echo "## ${VERSION}" >> ${TMP}
        # Adding current line
        echo "" >> ${TMP}
        echo "${L}" >> ${TMP}
        STEP=2 # Copy rest of the file

        # if [[ "${L}" == "- "* ]] || [[ "${L}" == " "* ]]; then
        #     # It's a list (starts with '- ') or something starting with space
        #     # => Need to add version title (# VERSION)
        #     echo "${VERSION}" >> ${TMP}
        #     S=${#VERSION}
        #     UNDER=""
        #     for (( c=1; c<=$S; c++ ))
        #     do
        #         UNDER="${UNDER}-"
        #     done
        #     echo ${UNDER} >> ${TMP}
        #     echo "" >> ${TMP}
        #     echo "${L}" >> ${TMP}
        # else
        #     if [ "${L}" == "" ]; then
        #         echo >> ${TMP}
        #         continue
        #     fi
        #     # Assuming 'VERSION' line + '------'
        #     if [[ "${L}" == "## ${VERSION}"* ]]; then
        #         echo "- There is already correct version in changelog. Doing nothing"
        #         echo
        #         rm ${TMP}
        #         break
        #     fi
        #     # Adding current version
        #     echo "## ${VERSION}" >> ${TMP}
        # fi
        # STEP=2
    else # STEP==2: Copy all remaining lines
        echo "${L}" >> ${TMP}
    fi
done

if [ -e ${TMP} ]; then
    rm ${CL}
    mv ${TMP} ${CL}
fi

exit 0
