#! /bin/bash

# Switch GIT branch or update current branch only.
# For developper purposes.
# Args = <branchname> [prefix]
#   prefix = Optional line prefix

PREFIX=$2

# NOW=`date +"%Y-%m-%d %H:%M:%S"`
SCRIPT=$(basename $0)
# echo "[${NOW}] Démarrage de '${SCRIPT}'"
echo "${PREFIX}Démarrage de '${SCRIPT}'"

# Arguments check
if [ $# -lt 1 ]; then
    echo "${PREFIX}= ERREUR: Nom de la branche manquant !"
    exit 1
fi

NEW_BRANCH=$1

# CUR_BRANCH = Current local branch name (ex: 'master_tcharp38')
# CUR_TRACKING_BRANCH = Current tracking branch (ex: 'master')
# CUR_TRACKING_REPO = Current tracking repo (ex: 'origin')

# Identifying current branch
CUR_BRANCH=`git rev-parse --abbrev-ref HEAD`
CUR_TRACKING_BRANCH=`git rev-parse --abbrev-ref --symbolic-full-name @{u}`
if [ "${CUR_TRACKING_BRANCH}" == "" ]; then
    echo "${PREFIX}= ERREUR: La branche 'tracking' n'est pas identifée."
    exit 2
fi
IFS='/'
read -ra R <<< "${CUR_TRACKING_BRANCH}"
CUR_TRACKING_REPO=${R[0]} # Ex: 'origin'
CUR_TRACKING_BRANCH=${R[1]}
echo "${PREFIX}Info: Branche locale actuelle = '${CUR_BRANCH}' => '${CUR_TRACKING_REPO}/${CUR_TRACKING_BRANCH}'"

# Analyzing requested branch name
NEW_TRACKING_REPO="" # Ex: 'origin'
NEW_TRACKING_BRANCH=""
if [[ "${NEW_BRANCH}" == "remotes/"* ]]; then
    IFS='/'
    read -ra R <<< "${NEW_BRANCH}"
    NEW_TRACKING_REPO=${R[1]} # Ex: 'origin'
    NEW_TRACKING_BRANCH=${R[2]}
fi
echo "${PREFIX}Info: Branche demandée = '${NEW_BRANCH}'"

UPDATE_ONLY=0
if [ "${NEW_BRANCH}" == "${CUR_BRANCH}" ]; then
    UPDATE_ONLY=1
    NEW_TRACKING_REPO=${CUR_TRACKING_REPO}
    NEW_TRACKING_BRANCH=${CUR_TRACKING_BRANCH}
fi

# Note: It would be cleaner to stop apache2 first while updating code
#   but can't stop apache2 without stopping this script itself.
#   This leads to dead lock situation with script halted & apache2 too.

# echo "Arret du cron & apache2"
# sudo systemctl stop cron apache2
# if [ $? != 0 ]; then
    # echo "= ERREUR"
    # exit 2
# fi
# echo "= OK"

ERROR=0

# This script is started from core/ajax. Moving to repo root.
cd ../../

# Fetch ALL must be done prior to 'switchBranch.sh' call
# echo "${PREFIX}Mise-à-jour (fetch) du repo git local"
# # TODO: Instead of fetch --all might be faster to do fetch on target branch only
# sudo git fetch --all >/dev/null
# if [ $? -ne 0 ]; then
#     echo "${PREFIX}= ERREUR"
#     ERROR=2
# else
#     echo "${PREFIX}= OK"
# fi

if [ ${ERROR} -eq 0 ]; then
    # Any local changes ?
    git diff-index --quiet HEAD
    if [ $? -ne 0 ]; then
        # echo "${PREFIX}Info: Modifications locales detectées !!"
        LOCAL_CHANGES=1
    else
        LOCAL_CHANGES=0
    fi

    if [ ${LOCAL_CHANGES} -ne 0 ]; then
        echo "${PREFIX}Suppression des modifications locales"
        sudo git checkout .
        if [ $? -ne 0 ]; then
            echo "${PREFIX}= ERREUR"
            ERROR=3
        else
            echo "${PREFIX}= OK"
        fi
    fi
    # if [ ${LOCAL_CHANGES} -ne 0 ]; then
        # echo "Suppression des modifications locales"
        # sudo git reset --hard ${REMOTE_BRANCH}
        # if [ $? -ne 0 ]; then
            # echo "= ERREUR"
            # ERROR=3
        # else
            # echo "= OK"
        # fi
    # else
        # echo "Alignement sur dernier commit"
        # Note: no need to align on last commit here. Done in later step
    # fi

    # Removing untracked files except 2 locals directories:
    # 'tmp' & 'core/config/devices_local'
    echo "${PREFIX}Suppression des fichiers non-suivis"
    sudo sudo git clean -f -d -e tmp/ -e core/config/devices_local/ >/dev/null
    if [ $? -ne 0 ]; then
        echo "${PREFIX}= ERREUR: sudo git clean -f -d -e tmp/"
        ERROR=4
    else
        echo "${PREFIX}= OK"
    fi
fi

if [ ${ERROR} -eq 0 ] && [ ${UPDATE_ONLY} -eq 0 ]; then
    echo "${PREFIX}Basculement vers la branche '${NEW_BRANCH}'"

    # NEW_TRACKING_REPO/NEW_TRACKING_BRANCH != CUR_TRACKING_REPO/CUR_TRACKING_BRANCH
    if [[ "${NEW_BRANCH}" == "remotes/"* ]]; then
        # IFS='/'
        # read -ra R <<< "${NEW_BRANCH}"
        # REMOTE_SOURCE=${R[1]} # Ex: 'origin'
        # REMOTE_BRANCH=${R[2]}
        echo "${PREFIX}- Branche remote = ${NEW_TRACKING_BRANCH}"

        # Does the local branch already exists ?
        git show-ref refs/heads/${NEW_TRACKING_BRANCH} >/dev/null
        if [ $? -ne 0 ]; then
            echo "${PREFIX}- Info: La branche locale '${NEW_TRACKING_BRANCH}' n'existe pas"
            sudo git checkout -b ${NEW_TRACKING_BRANCH} ${NEW_TRACKING_REPO}/${NEW_TRACKING_BRANCH}
            if [ $? -ne 0 ]; then
                echo "${PREFIX}= ERREUR: Pendant création de la branche ${NEW_TRACKING_BRANCH}"
                ERROR=5
            else
                echo "${PREFIX}= OK"
            fi
        else
            echo "${PREFIX}- Info: La branche '${NEW_TRACKING_BRANCH}' existe deja"
            # TODO: Ensure local branch has same remote
            sudo git checkout ${NEW_TRACKING_BRANCH}
            if [ $? -ne 0 ]; then
                echo "${PREFIX}= ERREUR: sudo git checkout ${NEW_TRACKING_BRANCH}"
                ERROR=6
            else
                echo "${PREFIX}= OK"
            fi
        fi
    else
        NEW_TRACKING_REPO=""
        NEW_TRACKING_BRANCH=""
        # TODO: Ensure local branch has same remote
        sudo git checkout ${NEW_BRANCH}
        if [ $? -ne 0 ]; then
            echo "${PREFIX}= ERREUR: sudo git checkout ${NEW_BRANCH}"
            ERROR=7
        else
            echo "${PREFIX}= OK"
        fi
        if [ ${ERROR} -eq 0 ]; then
            CUR_TRACKING_BRANCH=`git rev-parse --abbrev-ref --symbolic-full-name @{u}`
            if [ "${CUR_TRACKING_BRANCH}" == "" ]; then
                echo "${PREFIX}= ERREUR: La branche remote n'est pas identifée."
                ERROR=7
            else
                IFS='/'
                read -ra R <<< "${CUR_TRACKING_BRANCH}"
                NEW_TRACKING_REPO=${R[0]} # Ex: 'origin'
                NEW_TRACKING_BRANCH=${R[1]}
            fi
        fi
    fi
fi

# Aligning on latest commit of current branch
if [ ${ERROR} -eq 0 ]; then
    echo "${PREFIX}Alignement sur dernier commit"
    sudo git reset --hard ${NEW_TRACKING_REPO}/${NEW_TRACKING_BRANCH}
    if [ $? -ne 0 ]; then
        echo "${PREFIX}= ERREUR: sudo git reset --hard ${NEW_TRACKING_REPO}/${NEW_TRACKING_BRANCH}"
        ERROR=8
    else
        echo "${PREFIX}= OK"
    fi
fi

echo "${PREFIX}Redémarrage du cron & apache2"
sudo systemctl restart cron apache2
if [ $? -ne 0 ]; then
    echo "${PREFIX}= ERREUR: sudo systemctl restart cron apache2"
    exit 9
else
    echo "${PREFIX}= OK"
fi

touch tmp/switchBranch.done
exit 0
