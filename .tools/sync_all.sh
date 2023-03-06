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
while read -r; do
    # echo "Line=$REPLY"
    if [[ $REPLY = "."* ]]; then
        echo "IGN: ${REPLY}"
        continue
    fi
    echo "ADD: ${REPLY}"
    echo "put ${REPLY} ${REPLY}" >> ${SFTP_SCRIPT}
done < ${LSFILES}

sftp root@${ADDR_IP} < ${SFTP_SCRIPT}
