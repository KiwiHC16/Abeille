#! /bin/bash

###
### Check TTY port and proper access to Zigate.
### WARNING: This script expects that daemon is stoppped to
###          not disturb Zigate answer.
###

# Usage
# checkZigate.sh <portName>
# ex: checkZigate.sh /dev/ttyUSB0

# Args
PORT=""
TYPE="USB"
while [[ $# -gt 0 ]]; do
    if [[ "$1" == "-"* ]]; then
        echo "option $1"
    else
        if [ "${PORT}" != "" ]; then
            echo "ERROR: Unexpected arg '$1'"
            exit 1
        fi
        PORT=$1
    fi
    shift
done
if [ "${PORT}" == "" ]; then
    echo "ERROR: Missing port name (ex: /dev/ttyUSB0)"
    exit 1
fi

# Let's start
echo "Checking Zigate type ${TYPE} access on port ${PORT}"

# Port exists ?
if [ ! -e ${PORT} ]; then
    echo "= ERROR: Port ${PORT} does not exist !"
    exit 2
fi
echo "- ${PORT} port found"

command -v lsof >/dev/null
if [ $? -ne 0 ]; then
    echo "= ERROR: Could not find 'lsof' command"
    exit 3
fi

# Is port in use ?
FIELDS=`sudo lsof -Fcn ${PORT}`
if [ "${FIELDS}" == "" ]; then
    echo "- Port seems free"
else
    PID=0
    for f in ${FIELDS};
    do
        if [[ "$f" == "p"* ]]; then
            PID=${f:1}
            break
        fi
    done
    echo "= ERROR: Port is used by process '${PID}'."

    PSOUT=`ps --pid ${PID} -o ppid,cmd | grep -v PPID`
    IFS=' '
    read -ra PSOUTA <<< "${PSOUT}"
    PPID2=${PSOUTA[0]}
    CMD=${PSOUTA[@]:1}
    echo "=         Process details ${PID}:"
    echo "=           PPid=${PPID2}, cmd='${CMD}'"
    exit 3
fi

exit 0
