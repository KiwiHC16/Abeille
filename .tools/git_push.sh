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

# Updating plugin version
.tools/update_version.sh ${REMOTE_BRANCH}
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 22
fi

# Updating MD5 file
.tools/update_md5.sh
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 23
fi

# Add + commit + push
echo "Updating GIT & pushing"
git add plugin_info/Abeille.version plugin_info/info.json plugin_info/Abeille.md5
if [ $? -ne 0 ]; then
    echo "= ERROR: git add failed."
    exit 10
fi
VERSION=`cat plugin_info/Abeille.version | tail -1`
git commit -m "Version ${VERSION}"
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
