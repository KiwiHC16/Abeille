#! /bin/bash

###
### An attempt to power cycle USB device
### Tcharp38
###

date

FULLDEV=$1
echo "FULLDEV=$FULLDEV"

# TODO: Removing "/dev/" prefix
DEV=${FULLDEV#/dev/}
echo "DEV=$DEV"

# Identifing corresponding port using dmesg
# [   11.037185] usb 3-1.2: cp210x converter now attached to ttyUSB0
DMESG=`dmesg | grep "now attached to ${DEV}" | sed 's/^.*\(usb .*\).*$/\1/'`
if [ "${DMESG}" == "" ]; then
    exit 3
fi
echo "DMESG=$DMESG"
DMESG2=`echo ${DMESG} | sed 's/:.*//'`
echo "DMESG2='$DMESG2'"
PORT=${DMESG2#usb }
echo "PORT='$PORT'"

# Port identified. Let's do power cycling
echo "$PORT" > /sys/bus/usb/drivers/usb/unbind
sleep 2
echo "$PORT" > /sys/bus/usb/drivers/usb/bind
