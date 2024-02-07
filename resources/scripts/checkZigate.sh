#! /bin/bash

###
### Check TTY port and proper access to Zigate.
### WARNING: This script expects that daemon is stoppped to
###          not disturb Zigate answer.
###

# Usage
# checkZigate.sh [options] <portName>
# Possible options
# -k => kill process using port
# ex: checkZigate.sh /dev/ttyUSB0

# Args
PORT=""
TYPE="USB"
KILLIFUSED=0
while [[ $# -gt 0 ]]; do
    if [[ "$1" == "-"* ]]; then
        case $1 in
            '-k')
                KILLIFUSED=1
                ;;
            *)
                echo "ERROR: Unexpected option '$1'"
                exit 1
                ;;
        esac
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

    # Kill requested ?
    if [ ${KILLIFUSED} -eq 1 ]; then
        echo "- Killing process ${PID}"
        kill -9 ${PID}
        # TODO: While loop with timeout to wait for effective kill
    else
        echo "= ERROR: Port is used by process '${PID}'."

        PSOUT=`ps --pid ${PID} -o ppid,cmd | grep -v PPID`
        IFS=' '
        read -ra PSOUTA <<< "${PSOUT}"
        PPID2=${PSOUTA[0]}
        CMD=${PSOUTA[@]:1}
        echo "=        ${PID} process details:"
        echo "=        PPid=${PPID2}, cmd='${CMD}'"
        exit 4
    fi
fi

# Port is free, let's interrogate Zigate but python is required for that.
command -v python3 >/dev/null
if [ $? -ne 0 ]; then
    echo "= ERROR: Could not find 'python3' command."
    echo "         It is required for next steps"
    exit 5
fi

python3 core/python/AbeilleZigate.py

exit 0
