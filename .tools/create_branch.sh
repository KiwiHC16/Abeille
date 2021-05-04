#!/bin/bash
# 'beta'/'stable' version generation & push script

# How to
#   Create 'beta' version from 'master'
#   .tools/create_branch.sh beta
#   Create 'stable' version from 'beta'
#   .tools/create_branch.sh stable

echo "***"
echo "*** Abeille's beta/stable branch creation script"
echo "***"
echo

TARG_REPO='origin'
TARG_BRANCH=''
FORCE=0 # Force creation from branch != 'master' & 'beta'

# Usage: create_branch.sh [-f] [target_branch]
#   where -f = force creation from current branch (not master or beta)
#   target_branch = 'beta' or 'stable'
if [ $# -gt 0 ]; then
    echo "Checking arguments"
    while (( "$#" )); do
        case "$1" in
            -f)
                FORCE=1
                echo "- Info: 'forced' source"
                shift
            ;;
            -h)
                echo
                echo "Usage: create_branch.sh [-f] <target>";
                echo "where";
                echo "  -f    : force branch creation even if not from master or beta"
                echo "  target: 'beta' or 'stable'"
                echo
                exit 0
            ;;
            beta)
                TARG_BRANCH=$1
                shift
            ;;
            stable)
                TARG_BRANCH=$1
                shift
            ;;
            *)    # unknown option
                echo "= ERROR: Only 'beta' or 'master' accepted as argument"
                exit 1
            ;;
        esac
    done
fi

# Check repo status
# - Ensure that local branch is 'master' (to beta) or 'beta' (to stable)
# TODO: How to check that current branch is in line with master ?
# - Stops if any uncommitted local modifs
echo "Checking current branch & status"
CUR_BRANCH=`git rev-parse --abbrev-ref HEAD`
if [ ${FORCE} -eq 0 ] && [ "${CUR_BRANCH}" != "master" ] && [ "${CUR_BRANCH}" != "beta" ]; then
    echo "= ERROR: Current branch must be either 'master' or 'beta'"
    exit 10
fi
git diff-index --quiet HEAD >/dev/null
if [ $? -ne 0 ]; then
    echo "= ERROR: Uncommitted local modifications found"
    exit 11
fi

# If TARG_BRANCH is undefined, let's gess
if [ "${TARG_BRANCH}" == "" ]; then
    if [ "${CUR_BRANCH}" == "master" ]; then
        TARG_BRANCH='beta'
    elif [ "${CUR_BRANCH}" == "beta" ]; then
        TARG_BRANCH='stable'
    else
        echo "= ERROR: Missing argument to guess target branch"
        exit 11
    fi
fi

# Final check with user before starting if unusual config
if [ "${CUR_BRANCH}" != "master" ] && [ "${CUR_BRANCH}" != "beta" ]; then
    echo
    echo "   *** !! WARNING !!"
    echo "   *** This is an unexpecting config."
    echo "   *** You are going to create a '${TARG_BRANCH}' version from '${CUR_BRANCH}'."
    echo "   *** Are you sure you want to do that ?"
    read -p "   *** Enter y/n: " ANSWER
    if [ "${ANSWER}" != "y" ]; then
        echo "= Canceling branch creation"
        exit 0
    fi
    echo
else
    echo "   *** You are going to create a '${TARG_BRANCH}' version from '${CUR_BRANCH}'."
    echo "   *** Are you sure you want to do that ?"
    read -p "   *** Enter y/n: " ANSWER
    if [ "${ANSWER}" != "y" ]; then
        echo "= Canceling branch creation"
        exit 0
    fi
    echo
fi

# echo "Generating '${TARG_BRANCH}' branch from current '${CUR_BRANCH}'"

# Create local temporary branch & switch to it
# Note: Local branch deleted if already exists
TODAY=`date +"%y%m%d"`
LOCAL_BRANCH="${TARG_BRANCH}-temp-${TODAY}"
git show-ref refs/heads/${LOCAL_BRANCH} >/dev/null
if [ $? -eq 0 ]; then
    # Note: -D to force delete
    echo "Deleting temp '${LOCAL_BRANCH}' branch"
    git branch -D ${LOCAL_BRANCH} >/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERROR"
        exit 20
    fi
fi
echo "Switching to ${LOCAL_BRANCH}"
git checkout -q -b ${LOCAL_BRANCH}
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 21
fi

# Updating plugin version
.tools/update_version.sh ${TARG_BRANCH}
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

# Update changelog if required & target is 'stable'
if [ "${TARG_BRANCH}" == "stable" ]; then
    .tools/update_changelog.sh
    if [ $? -ne 0 ]; then
        echo "= ERROR"
        exit 24
    fi
fi

# Add+commit
echo "Adding 'Abeille.version' & 'Abeille.md5'"
git add plugin_info/Abeille.version plugin_info/Abeille.md5 docs/fr_FR/changelog.md
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 30
fi
VERSION=`cat plugin_info/Abeille.version | tail -1`
echo "Committing"
if [ "${TARG_BRANCH}" == "beta" ]; then
    git commit -q -m "Beta ${VERSION}"
else
    git commit -q -m "Stable ${VERSION}"
fi
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 31
fi

# Delete target branch & push new one
REM=`git branch -a | grep remotes/${TARG_REPO}/${TARG_BRANCH}`
if [ $? -eq 0 ]; then
    echo "Deleting ${TARG_REPO}/${TARG_BRANCH} branch"
    git push -q ${TARG_REPO} --delete ${TARG_BRANCH}
    if [ $? -ne 0 ]; then
        echo "= ERROR"
        exit 32
    fi
fi

# Pushing branch
echo "Creating ${TARG_REPO}/${TARG_BRANCH} branch"
git push --force -q ${TARG_REPO} ${LOCAL_BRANCH}:${TARG_BRANCH}
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 33
fi

echo "Switching back to '${CUR_BRANCH}' branch"
git checkout -q ${CUR_BRANCH}
if [ $? -ne 0 ]; then
    echo "= ERROR"
    exit 34
else
    echo "= Ok"
fi

exit 0
