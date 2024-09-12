#! /bin/bash

###
### PI Zigate v1 flasher script
### Tcharp38
###

V1PROG=build/JennicModuleProgrammer

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
FWPATH=""
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
        elif [ "${FWPATH}" == "" ]; then
            FWPATH=$1
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
    echo "ERROR: Missing Zigate type (PI)"
    exit 1
fi
if [ ${TYPE} != "PI" ]; then
    echo "= ERREUR: Invalid Zigate type ! (PI supported only)"
    exit 1
fi
if [ "${TYPE}" == "PI" ] || [ ${TYPE} == "PIv2" ]; then
    if [ "${GPIOLIB}" == "" ]; then
        echo "ERROR: Missing GPIO lib (PiGpio or WiringPi)"
        exit 1
    elif [ "${GPIOLIB}" != "PiGpio" ] && [ "${GPIOLIB}" != "WiringPi" ]; then
        echo "ERROR: Invalid GPIO lib (PiGpio or WiringPi)"
        exit 1
    fi
fi
if [ "${FWPATH}" == "" ]; then
    echo "ERROR: Missing FW path"
    exit 1
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

if [ "${TYPE}" == "PI" ] || [ ${TYPE} == "PIv2" ]; then
    echo "Configuring PI Zigate for 'flash' mode (lib=${GPIOLIB})"
    if [ "${GPIOLIB}" == "WiringPi" ]; then
        gpio mode 0 out
        gpio mode 2 out
        gpio write 2 0
        gpio write 0 0
        gpio write 0 1
    else
        echo "ERROR: Unsupported GPIO lib ${GPIOLIB}"
        exit 6
    fi
fi

echo "Flashing Zigate"
echo "- Port tty: ${PORT}"
echo "- Type    : ${TYPE}"
echo "- Lib GPIO: ${GPIOLIB}"
echo "- File    : ${FWPATH}"
#echo "  Prog    : ${PROG}"
echo

if [ ${TYPE} == "PI" ]; then
    # JN5168: Flash appli from 0x00080000 to 0x000C0000
    ${V1PROG} -V 6 -P 115200 -v -f ${FWPATH} -s ${PORT} 2>&1
    ERROR=$?
elif [ ${TYPE} == "PIv2" ]; then
    # JN5189: Flash area (640KB) from 0x0000_0000 to 0x0009_FFFF
    ${V2PROG} -V 3 -s ${PORT} -P 115200 -Y -p ${FWPATH} 2>&1
    ERROR=$?
else
    echo "ERROR: Unexpected type '$TYPE'"
    ERROR=12
fi

# Switch back to 'prod' mode
if [ ${TYPE} == "PI" ] || [ ${TYPE} == "PIv2" ]; then
    echo "Restoring PI Zigate GPIOs config for prod mode"
    if [ "${GPIOLIB}" == "WiringPi" ]; then
        gpio mode 0 out
        gpio mode 2 out
        gpio write 2 1
        gpio write 0 0
        gpio write 0 1
    else
        echo "ERROR: Unsupported GPIO lib ${GPIOLIB}"
        ERROR=6
    fi
fi

# Final status
if [ $ERROR -eq 0 ]; then
    echo "= Tout s'est bien passé. Vous pouvez fermer ce log."
else
    echo "= ATTENTION !!! "
    echo "= Quelque chose s'est mal passé. Veuillez vérifier le log ci-dessus."
fi
exit $ERROR
