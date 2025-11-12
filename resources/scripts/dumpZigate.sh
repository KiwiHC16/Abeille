#! /bin/bash
# Note: This script is launched from Abeille/core/ajax directory
# Note: The port must NOT be used during this script execution. This is NOT CHECKED so far.

ABEILLEROOT=${PWD}
V2PROG=${ABEILLEROOT}/resources/DK6Programmer
V1PROG=${ABEILLEROOT}/tmp/JennicModuleProgrammer

# Display process ($1=Process ID)
displayProcess() {
    local PID=$1

    PSOUT=`ps --pid ${PID} -o ppid,cmd | grep -v PPID`
    IFS=' '
    read -ra PSOUTA <<< "${PSOUT}"
    PPID2=${PSOUTA[0]}
    CMD=${PSOUTA[@]:1}
    echo "= Infos:"
    echo "=   Process ${PID} details:"
    echo "=   PPid=${PPID2}, cmd='${CMD}'"
}

# Check if port is free
# $1=port
checkPort() {
    local PORT=$1

    local FIELDS=`sudo lsof -Fcn ${PORT}`
    if [ "${FIELDS}" == "" ]; then
        echo "- Port seems free"
        PORTISFREE=1
        return
    fi

    # Port is used
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
        echo "- Port is used by process ${PID} => killing process ${PID}"
        displayProcess ${PID}
        kill -9 ${PID}
        # TODO: While loop with timeout to wait for effective kill
        PORTISFREE=1
    else
        echo "= ERROR: Port is used by process ${PID}."
        echo "=        You can add '-k' option to further tests anyway."
        PORTISFREE=0

        displayProcess ${PID}

        echo
        echo "= Additional infos I:"
        dmesg | grep tty

        echo
        echo "= Additional infos II:"
        ls -al /dev/serial*
    fi
}

# Checking arguments
PORT=""
TYPE=""
GPIOLIB=""
DUMPFILE=""
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
        if [ "${PORT}" == "" ]; then
            PORT=$1
        elif [ "${TYPE}" == "" ]; then
            TYPE=$1
        elif [ "${GPIOLIB}" == "" ]; then
            GPIOLIB=$1
        elif [ "${DUMPFILE}" == "" ]; then
            DUMPFILE=$1
        else
            echo "ERROR: Unexpected arg '$1'"
            exit 1
        fi
    fi
    shift
done
if [ "${PORT}" == "" ]; then
    echo "ERROR: Missing port name (ex: /dev/ttyS1)"
    exit 1
fi
if [ "${TYPE}" == "" ]; then
    echo "ERROR: Missing Zigate type (PIv2 only so far)"
    exit 1
fi
if [ ${TYPE} != "PIv2" ]; then
    echo "= ERREUR: Type de Zigate invalide ! (PIv2 seulement)"
    echo "=         dumpZigate.sh <zigatePort> <zigateType> <gpioLib> <dumpFile>"
    exit 1
fi
if [ "${TYPE}" == "PI" ] || [ "${TYPE}" == "PIv2" ]; then
    if [ "${GPIOLIB}" == "" ]; then
        echo "ERROR: Missing GPIO lib (PiGpio or WiringPi)"
        exit 1
    elif [ "${GPIOLIB}" != "PiGpio" ] && [ "${GPIOLIB}" != "WiringPi" ]; then
        echo "ERROR: Invalid GPIO lib (PiGpio or WiringPi)"
        exit 1
    fi
fi

# Let's start
echo "Checking Zigate type ${TYPE} access on port ${PORT}"

# Global variables
PORTISFREE=0


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
        echo "- Port is used by process ${PID} => killing process ${PID}"
        displayProcess ${PID}
        kill -9 ${PID}
        # TODO: While loop with timeout to wait for effective kill
    else
        echo "= ERROR: Port is used by process '${PID}'."
        echo "=        You can add '-k' option to further tests anyway."

        displayProcess ${PID}

        echo
        echo "= Additional infos I:"
        dmesg | grep tty

        echo
        echo "= Additional infos II:"
        ls -al /dev/serial*

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

if [ "${TYPE}" == "PI" ] || [ "${TYPE}" == "PIv2" ]; then
    echo "Configuring PI Zigate for 'flash' mode (lib=${GPIOLIB})"
    python3 core/python/AbeilleZigate.py zgSetPiMode flash ${GPIOLIB}
    if [ $? -ne 0 ]; then
        exit 6
    fi
fi

echo "Dumping Zigate content"
echo "- Port tty: ${PORT}"
echo "- Type    : ${TYPE}"
echo "- Lib GPIO: ${GPIOLIB}"
echo "- File    : ${DUMPFILE}"
#echo "  Prog    : ${PROG}"
echo

if [ ${TYPE} == "PI" ]; then
    # JN5168: Flash appli from 0x00080000 to 0x000C0000
    # NOTE: Current version DOES NOT support 'dump' option
    ${V1PROG} -V 3 -s ${PORT} -P 115200 -d FLASH:0xC0000@0x80000=${DUMPFILE}
elif [ ${TYPE} == "PIv2" ]; then
    # JN5189: Flash area (640KB) from 0x0000_0000 to 0x0009_FFFF
    ${V2PROG} -V 3 -s ${PORT} -P 115200 -d FLASH:0x9FFFF@0=${DUMPFILE}
fi
ERROR=$?

# Switch back to 'prod' mode
if [ ${TYPE} == "PI" ] || [ ${TYPE} == "PIv2" ]; then
    echo "Restoring PI Zigate GPIOs config for prod mode"
    python3 core/python/AbeilleZigate.py zgSetPiMode prod ${GPIOLIB}
    ERROR=$?
fi

# Final status
if [ $ERROR -eq 0 ]; then
    echo "= Tout s'est bien passé. Vous pouvez fermer ce log."
else
    echo "= ATTENTION !!! "
    echo "= Quelque chose s'est mal passé. Veuillez vérifier le log ci-dessus."
fi
exit $ERROR
