#!/bin/bash

if [ $# -lt 1 ]; then
    echo "ERROR: Missing IP address"
    exit 1
fi
ADDR_IP=$1

ABEILLE_DIR=/var/www/html/plugins/Abeille
SFTP_SCRIPT=../sftp.cmds
LSFILES=../git.lsfiles

git ls-files > ${LSFILES}
echo "cd ${ABEILLE_DIR}" > ${SFTP_SCRIPT}
PREVIOUS_DIR=""
while read -r; do
    # echo "Line=$REPLY"

    # TODO: Put this part in exclude file
    if [[ $REPLY = "core/config/commands/OBSOLETE"* ]]; then
        echo "IGN: ${REPLY}"
        continue
    fi
    if [[ $REPLY = "resources/archives"* ]]; then
        echo "IGN: ${REPLY}"
        continue
    fi
    # End TODO

    if [[ $REPLY = "."* ]]; then
        echo "IGN: ${REPLY}"
        continue
    fi
    REPLY_DIR=$(dirname ${REPLY})
    echo "ADD: ${REPLY}"
    if [ "${REPLY_DIR}" != "${PREVIOUS_DIR}" ]; then
        # Warning: Could not be sure that all path exists for creation
        echo "mkdir ${REPLY_DIR}" >> ${SFTP_SCRIPT}
        PREVIOUS_DIR=${REPLY_DIR}
    fi
    echo "put ${REPLY} ${REPLY}" >> ${SFTP_SCRIPT}
done < ${LSFILES}

sftp root@${ADDR_IP} < ${SFTP_SCRIPT}
