#! /bin/bash

# Switch GIT branch or update current branch only.
# For developer purposes.
# Args = branchname [update]
#   If 'update' is 2nd arg, then current branch is updated only

NOW=`date +"%Y-%m-%d %H:%M:%S"`
SCRIPT=$(basename $0)
echo "[${NOW}] Démarrage de '${SCRIPT}'"

# Arguments check
if [ $# -lt 1 ]; then
    echo "= ERREUR: Nom de la branche manquant !"
    exit 1
fi

NEW_BRANCH=$1
UPDATE_ONLY=0

if [ $# -eq 2 ]; then
    if [ "$2" == "update" ]; then
        UPDATE_ONLY=1
        echo "Info: Mise-à-jour à jour de la branche courante seulement"
    fi
fi

# Is remote branch identified ?
REMOTE_BRANCH=`git rev-parse --abbrev-ref --symbolic-full-name @{u}`
if [ "${REMOTE_BRANCH}" == "" ]; then
    echo "= ERREUR: La branche remote n'est pas identifée."
    exit 2
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

echo "Mise-à-jour (fetch) du repo git local"
sudo git fetch --all >/dev/null
if [ $? -ne 0 ]; then
    echo "= ERREUR"
    ERROR=2
else
    echo "= OK"
fi

echo "Info: Branche distante actuelle = '${REMOTE_BRANCH}'"

if [ ${ERROR} -eq 0 ]; then
    # Any local changes ?
    git diff-index --quiet HEAD
    if [ $? -ne 0 ]; then
        echo "Info: Modifications locales detectées !!"
        LOCAL_CHANGES=1
    else
        LOCAL_CHANGES=0
    fi

    if [ ${LOCAL_CHANGES} -ne 0 ]; then
        echo "Suppression des modifications locales"
        sudo git checkout .
        if [ $? -ne 0 ]; then
            echo "= ERREUR"
            ERROR=3
        else
            echo "= OK"
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

    echo "Suppression des fichiers non-suivis"
    sudo sudo git clean -f -d -e tmp/ >/dev/null
    if [ $? -ne 0 ]; then
        echo "= ERREUR: sudo git clean -f -d -e tmp/"
        ERROR=4
    else
        echo "= OK"
    fi
fi

if [ ${ERROR} -eq 0 ] && [ ${UPDATE_ONLY} -eq 0 ]; then
    echo "Basculement vers la branche '${NEW_BRANCH}'"
    if [[ "${NEW_BRANCH}" == "remotes/"* ]]; then
        IFS='/'
        read -ra R <<< "${NEW_BRANCH}"
        REMOTE_SOURCE=${R[1]} # Ex: 'origin'
        REMOTE_BRANCH=${R[2]}
        echo "- Branche remote = ${REMOTE_BRANCH}"
        # Does the local branch already exists ?
        git show-ref refs/heads/${REMOTE_BRANCH} >/dev/null
        if [ $? -ne 0 ]; then
            echo "- Info: La branche locale '${REMOTE_BRANCH}' n'existe pas"
            sudo git checkout -b ${REMOTE_BRANCH} ${REMOTE_SOURCE}/${REMOTE_BRANCH}
            if [ $? -ne 0 ]; then
                echo "= ERREUR: Pendant création de la branche ${REMOTE_BRANCH}"
                ERROR=5
            else
                echo "= OK"
            fi
        else
            echo "- Info: La branche '${REMOTE_BRANCH}' existe deja"
            # TODO: Ensure local branch has same remote
            sudo git checkout ${REMOTE_BRANCH}
            if [ $? -ne 0 ]; then
                echo "= ERREUR: sudo git checkout ${REMOTE_BRANCH}"
                ERROR=6
            else
                echo "= OK"
            fi
        fi
    else
        REMOTE_SOURCE=""
        REMOTE_BRANCH=""
        # TODO: Ensure local branch has same remote
        sudo git checkout ${NEW_BRANCH}
        if [ $? -ne 0 ]; then
            echo "= ERREUR: sudo git checkout ${NEW_BRANCH}"
            ERROR=7
        else
            echo "= OK"
        fi
        if [ ${ERROR} -eq 0 ]; then
            REMOTE_BRANCH=`git rev-parse --abbrev-ref --symbolic-full-name @{u}`
            if [ "${REMOTE_BRANCH}" == "" ]; then
                echo "= ERREUR: La branche remote n'est pas identifée."
                ERROR=7
            fi
        fi
    fi
fi

# Aligning on latest commit of current branch
if [ ${ERROR} -eq 0 ]; then
    echo "Alignement sur dernier commit"
    sudo git reset --hard ${REMOTE_BRANCH}
    if [ $? -ne 0 ]; then
        echo "= ERREUR: sudo git reset --hard ${REMOTE_BRANCH}"
        ERROR=8
    else
        echo "= OK"
    fi
fi

echo "Redémarrage du cron & apache2"
sudo systemctl restart cron apache2
if [ $? -ne 0 ]; then
    echo "= ERREUR: sudo systemctl restart cron apache2"
    exit 9
else
    echo "= OK"
fi

touch tmp/switchBranch.done
exit 0
