#! /bin/bash

###
### An attempt to power cycle USB Zigate device
### (C) Tcharp38
###

# Note: https://www.kernel.org/doc/Documentation/usb/power-management.txt
# Note: This code does not work properly with device is like "/dev/serial/by-id/usb-Prolific_Technology_Inc._USB-Serial_Controller-if00-port0"

DISCONNECT_TIME=2

echo -n "powerCycleUsb.sh starting: "
date

NBARGS=$#
if [ ${NBARGS} -ne 1 ]; then
    echo "ERROR: Missing USB port"
    exit 1
fi

FULLDEV=$1
echo "USB port=$FULLDEV"

if [ ! -e "${FULLDEV}" ]; then
    echo "ERROR: Invalid USB port ${FULLDEV}. Does not exist."
    exit 2
fi

# '/dev/ttyXXX' case: Removing "/dev/" prefix
DEV=${FULLDEV#/dev/}
#echo "DEV=$DEV"

# Identifing corresponding port using dmesg
# [   11.037185] usb 3-1.2: cp210x converter now attached to ttyUSB0
#DMESG=`dmesg | grep "now attached to ${DEV}" | sed 's/^.*\(usb .*\).*$/\1/'`
DMESG=$(dmesg 2>/dev/null | grep "now attached to ${DEV}" | sed 's/^.*\(usb .*\).*$/\1/')
if [ "${DMESG}" == "" ]; then
    echo "ERROR: Grep on dmesg failed."
    echo
    echo "'dmesg' output"
    echo "=============="
    dmesg
    echo
    echo "'lsusb' output"
    echo "=============="
    lsusb
    echo
    echo "'lsusb -t' output"
    echo "=============="
    lsusb -t
    if [[ "${FULLDEV}" == "/dev/serial/by-id"* ]]; then
        echo
        echo "'ls -l /dev/serial/by-id' output"
        echo "================================"
        ls -l /dev/serial/by-id
    fi
    exit 3
fi

# TODO: DMESG may contains a list of "now attached to". How to keep most recent/last one ?

#echo "DMESG=$DMESG"
DMESG2=`echo ${DMESG} | sed 's/:.*//'` # Remove everything after ':'
CONVERTOR=`echo ${DMESG} | awk '{ print $3 }'`
# echo "DMESG2='$DMESG2'"
PORT=${DMESG2#usb }
echo "CONVERTOR=${CONVERTOR}, PORT='${PORT}'"

# Port identified. Let's do power cycling
if [ -e "/sys/bus/usb/drivers/usb/unbind" ]; then
    echo "Disconnecting '${PORT}' ${DISCONNECT_TIME} sec"
    echo "$PORT" > /sys/bus/usb/drivers/usb/unbind
    ERR=$?
    if [ $ERR -ne 0 ]; then
        sudo echo "= ERROR: 'unbind' failed ! Are you root ?"
    else
        sleep ${DISCONNECT_TIME}
        echo "Reconnecting '${PORT}'"
        sudo echo "$PORT" > /sys/bus/usb/drivers/usb/bind
    fi
else
    echo "ERROR: No solution found to power cycle ${PORT}"
    exit 4
fi

exit 0
