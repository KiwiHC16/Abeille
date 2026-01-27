#!/bin/bash

###
### Find USB serial device by its unique serial number
### Useful in Docker where /dev/serial/by-id/ may not exist
### (C) 2026
###

# Usage: findUsbBySerial.sh <serial_number>
# Returns: /dev/ttyUSBx path if found, empty string if not found
# Exit code: 0 if found, 1 if not found

if [ $# -lt 1 ]; then
    echo "Usage: $0 <serial_number>" >&2
    exit 1
fi

SEARCH_SERIAL="$1"

# Search through all ttyUSB devices
for tty in /sys/class/tty/ttyUSB*; do
    [ -e "$tty" ] || continue

    # Try to read serial number from sysfs
    SERIAL_FILE="$tty/device/../../serial"
    if [ -e "$SERIAL_FILE" ]; then
        SERIAL=$(cat "$SERIAL_FILE" 2>/dev/null)
        if [ "$SERIAL" = "$SEARCH_SERIAL" ]; then
            DEV=$(basename "$tty")
            echo "/dev/$DEV"
            exit 0
        fi
    fi
done

# Not found
exit 1
