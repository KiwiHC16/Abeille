#! /bin/bash

###
### An attempt to power cycle USB device
### Tcharp38
###

# Note: https://www.kernel.org/doc/Documentation/usb/power-management.txt

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

# TODO: Removing "/dev/" prefix
DEV=${FULLDEV#/dev/}
#echo "DEV=$DEV"

# Identifing corresponding port using dmesg
# [   11.037185] usb 3-1.2: cp210x converter now attached to ttyUSB0
#DMESG=`dmesg | grep "now attached to ${DEV}" | sed 's/^.*\(usb .*\).*$/\1/'`
DMESG=$(dmesg 2>/dev/null | grep "now attached to ${DEV}" | sed 's/^.*\(usb .*\).*$/\1/')
if [ "${DMESG}" == "" ]; then
    echo "ERROR: Grep on dmesg failed. 'dmesg' output follows"
    dmesg
    exit 3
fi
#echo "DMESG=$DMESG"
DMESG2=`echo ${DMESG} | sed 's/:.*//'`
echo "DMESG2='$DMESG2'"
PORT=${DMESG2#usb }
echo "PORT='$PORT'"

# Port identified. Let's do power cycling
if [ -e "/sys/bus/usb/drivers/usb/unbind" ]; then
    echo "Disconnecting ${PORT}"
    echo "$PORT" > /sys/bus/usb/drivers/usb/unbind
    ERR=$?
    if [ $ERR -ne 0 ]; then
        sudo echo "= ERROR: 'unbind' failed ! Are you root ?"
    else
        sleep 2
        echo "Reconnecting ${PORT}"
        sudo echo "$PORT" > /sys/bus/usb/drivers/usb/bind
    fi
else
    echo "ERROR: No solution found to power cycle ${PORT}"
    exit 4
fi

exit 0
