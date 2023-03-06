#!/bin/bash

# Save GIT changes to a given directory

if [ $# -lt 1 ]; then
    echo "ERROR: Missing target directory."
    exit 1
fi
TARGET_DIR=$1

GIT_PORCELAIN=git.porcelain

git status --porcelain > ${GIT_PORCELAIN}
REPO_DIR=$(pwd)
echo "Current dir: $PWD"
echo "Target dir : $TARGET_DIR"
mkdir -p ${TARGET_DIR}
cd ${TARGET_DIR}
while read -r; do
    # echo "Line=$REPLY"
    ARG2=`echo $REPLY | awk '{print $2}'`
    ARG3=`echo $REPLY | awk '{print $3}'`
    ARG4=`echo $REPLY | awk '{print $4}'`
    if [[ $REPLY = "A"* ]]; then
        echo "ADD: ${ARG2}"
        mkdir -p $(dirname ${ARG2})
        cp "${REPO_DIR}/${ARG2}" ${ARG2}
        continue
    fi
    if [[ $REPLY = "M"* ]] || [[ $REPLY = " M"* ]]; then
        echo "MOD: ${ARG2}"
        mkdir -p $(dirname ${ARG2})
        cp "${REPO_DIR}/${ARG2}" ${ARG2}
        continue
    fi
    if [[ $REPLY = "D"* ]] || [[ $REPLY = " D"* ]]; then
        echo "DEL: ${ARG2}"
        mkdir -p $(dirname ${ARG2})
        cp "${REPO_DIR}/${ARG2}" ${ARG2}".removed"
        continue
    fi
    if [[ $REPLY = "R"* ]]; then
        echo "REN: ${ARG2} to ${ARG4}"
        mkdir -p $(dirname ${ARG2})
        mkdir -p $(dirname "${ARG4}")
        cp "${REPO_DIR}/${ARG4}" ${ARG4}
        cp "${REPO_DIR}/${ARG2}" ${ARG2}".removed"
        continue
    fi
    if [[ $REPLY = "??"* ]]; then
        if [ "${ARG2}" = "git.porcelain" ] || [ "${ARG2}" = "sftp.cmds" ]; then
            echo "?? : '$REPLY' => IGNORED"
            continue;
        fi
        echo "?? : '$REPLY'"
        mkdir -p $(dirname ${ARG2})
        cp "${REPO_DIR}/${ARG2}" ${ARG2}
        continue
    fi
    echo "ERROR: Don't know what to do with the line"
    echo "       '$REPLY'"
done < "${REPO_DIR}/${GIT_PORCELAIN}"
