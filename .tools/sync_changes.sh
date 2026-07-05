#!/bin/bash

if [ $# -lt 1 ]; then
    echo "ERROR: Missing IP address"
    exit 1
fi
ADDR_IP=$1

ABEILLE_DIR=/var/www/html/plugins/Abeille
SFTP_SCRIPT=../sftp.cmds
GIT_PORCELAIN=../git.porcelain

git status --porcelain > ${GIT_PORCELAIN}
echo "cd ${ABEILLE_DIR}" > ${SFTP_SCRIPT}
while read -r; do
    # echo "Line=$REPLY"
    ARG2=`echo $REPLY | awk '{print $2}'`
    ARG3=`echo $REPLY | awk '{print $3}'`
    ARG4=`echo $REPLY | awk '{print $4}'`
    if [[ $REPLY = "A"* ]]; then
        echo "ADD: ${ARG2}"
        echo "put ${ARG2} ${ARG2}" >> ${SFTP_SCRIPT}
        continue
    fi
    if [[ $REPLY = "M"* ]] || [[ $REPLY = " M"* ]]; then
        echo "MOD: ${ARG2}"
        echo "put ${ARG2} ${ARG2}" >> ${SFTP_SCRIPT}
        continue
    fi
    if [[ $REPLY = "U"* ]] || [[ $REPLY = " U"* ]]; then
        echo "UPD: ${ARG2}"
        echo "put ${ARG2} ${ARG2}" >> ${SFTP_SCRIPT}
        continue
    fi
    if [[ $REPLY = "D"* ]] || [[ $REPLY = " D"* ]]; then
        echo "DEL: ${ARG2}"
        echo "rm ${ARG2}" >> ${SFTP_SCRIPT}
        continue
    fi
    if [[ $REPLY = "R"* ]]; then
        echo "REN: ${ARG2} to ${ARG4}"
        echo "put ${ARG4} ${ARG4}" >> ${SFTP_SCRIPT}
        echo "rm ${ARG2}" >> ${SFTP_SCRIPT}
        continue
    fi
    if [[ $REPLY = "??"* ]]; then
        if [ "${ARG2}" = "git.porcelain" ] || [ "${ARG2}" = "sftp.cmds" ]; then
            echo "?? : '$REPLY' => IGNORED"
            continue;
        fi
        echo "?? : '$REPLY'"
        if [ -d ${ARG2} ]; then
            FILES=`find ${ARG2}`
            for F in ${FILES}; do
                echo "     $F"
            done

            echo "     Creating and transfering directory"
            echo "mkdir ${ARG2}" >> ${SFTP_SCRIPT}
            for F in ${FILES}; do
                echo "put $F $F" >> ${SFTP_SCRIPT}
            done
        else
            echo "put ${ARG2} ${ARG2}" >> ${SFTP_SCRIPT}
        fi
        continue
    fi
    echo "ERROR: Don't know what to do with the line"
    echo "       '$REPLY'"
done < ${GIT_PORCELAIN}

sftp root@${ADDR_IP} < ${SFTP_SCRIPT}
