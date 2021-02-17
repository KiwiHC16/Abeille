#!/bin/bash
# Git push script
# Do the following steps
# - Update version into 'Abeille.version'
# - Compute MD5 checksums into 'Abeille.md5'
# - Add+commit+push

OUT=plugin_info/Abeille.md5
FORCE=""

# Checks
# Ensure script is launched from Abeille's root
if [ ! -e ".git" ]; then
    echo "ERROR: This script must be launched from Abeille's root directory."
    exit 1
fi
if [ "$1" == "--force" ]; then
    FORCE="--force"
fi

# Remote branch identification
REMOTE_BRANCH_FULL=`git rev-parse --abbrev-ref --symbolic-full-name @{u}`
if [ "${REMOTE_BRANCH_FULL}" == "" ]; then
    echo "= ERROR: Could not identify remote branch."
    exit 2
fi
echo "- Remote branch: ${REMOTE_BRANCH_FULL}"
IFS='/'
read -ra R <<< "${REMOTE_BRANCH_FULL}"
REMOTE_REPO=${R[0]} # Ex: 'origin'
REMOTE_BRANCH=${R[1]} # Ex: 'master'
echo "  REMOTE_SOURCE=${REMOTE_REPO}"
echo "  REMOTE_BRANCH=${REMOTE_BRANCH}"

# Updating Abeille's version
# Selected format is compact but complete.
# Format: YYMMDD-REMOTE_BRANCH-X (ex: 210204-STABLE-1)
echo "Computing new version"
DATE_NEW=`date  +"%y%m%d"`
REM_BRANCH_UP=`echo "${REMOTE_BRANCH}" | tr '[:lower:]' '[:upper:]'`
VERSION_PREFIX="${DATE_NEW}-${REM_BRANCH_UP}-"
echo "  VERSION_PREFIX=${VERSION_PREFIX}"
VERSION_CURRENT=`cat plugin_info/Abeille.version | tail -1`
echo "- Current version=${VERSION_CURRENT}"
MINOR_CURRENT=0
if [[ "${VERSION_CURRENT}" == "${VERSION_PREFIX}"* ]]; then
    if [ "${VERSION_CURRENT}" != "" ]; then
        MINOR_CURRENT=${VERSION_CURRENT#${VERSION_PREFIX}}
    fi
fi
echo "  MINOR_CURRENT=${MINOR_CURRENT}"
MINOR_NEW=`expr ${MINOR_CURRENT} + 1`
VERSION_NEW="${VERSION_PREFIX}${MINOR_NEW}"
echo "- New version = ${VERSION_NEW}"
echo "# Auto-generated Abeille's version" >plugin_info/Abeille.version
echo "${VERSION_NEW}" >>plugin_info/Abeille.version

# Commit ready. Generating md5 for versionned files
.tools/gen_md5.sh

# Add + commit + push
echo "Updating GIT & pushing"
git add plugin_info/Abeille.version plugin_info/Abeille.md5
if [ $? -ne 0 ]; then
    echo "= ERROR: git add failed."
    exit 10
fi
git commit -m "Version ${VERSION_NEW}"
if [ $? -ne 0 ]; then
    echo "= ERROR: git commit failed."
    exit 11
fi
git push ${FORCE}
if [ $? -ne 0 ]; then
    echo "= ERROR: git push failed."
    echo "= You may need to do the push with"
    echo "= git push --force"
    exit 12
fi
echo "= Ok"
