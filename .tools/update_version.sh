#!/bin/bash

# Args
# $1 = destination branch name (ex: 'beta' or 'stable')
if [ $# -lt 1 ]; then
    echo "ERROR: Missing branch name"
    exit 1
fi

BRANCH=$1

# Updating Abeille's version
# Selected format is compact but complete.
# Format: YYMMDD-REMOTE_BRANCH-X (ex: 210204-STABLE-1)
echo "Updating Abeille's version"
DATE_NEW=`date  +"%y%m%d"`
BRANCH_UP=`echo "${BRANCH}" | tr '[:lower:]' '[:upper:]'`
VERSION_PREFIX="${DATE_NEW}-${BRANCH_UP}-"
# echo "- VERSION_PREFIX=${VERSION_PREFIX}"
VERSION_CURRENT=`cat plugin_info/Abeille.version | tail -1`
echo "- Current version: ${VERSION_CURRENT}"
MINOR_CURRENT=0
if [[ "${VERSION_CURRENT}" == "${VERSION_PREFIX}"* ]]; then
    if [ "${VERSION_CURRENT}" != "" ]; then
        MINOR_CURRENT=${VERSION_CURRENT#${VERSION_PREFIX}}
    fi
fi
# echo "  MINOR_CURRENT=${MINOR_CURRENT}"
MINOR_NEW=`expr ${MINOR_CURRENT} + 1`
VERSION_NEW="${VERSION_PREFIX}${MINOR_NEW}"
echo "- New version = ${VERSION_NEW}"

# Updating plugin_info/Abeille.version
FILE=plugin_info/Abeille.version
echo "# Auto-generated Abeille's version" >${FILE}
echo "${VERSION_NEW}" >>${FILE}
echo "- ${FILE} updated"

# Updating plugin_info/info.json for Changelog
FILE=plugin_info/info.json
FILE_TMP=plugin_info/info.json.tmp
rm -f ${FILE_TMP} >/dev/null
cat ${FILE} |
while IFS= read -r L
do
    if [[ "${L}" == *"changelog"* ]]; then
        echo "  \"changelog\": \"https://kiwihc16.github.io/AbeilleDoc/Changelog.html#${VERSION_NEW}\"," >> ${FILE_TMP}
    else
        echo "${L}" >> ${FILE_TMP}
    fi
done
mv ${FILE_TMP} ${FILE}
echo "- ${FILE} updated"

exit 0
