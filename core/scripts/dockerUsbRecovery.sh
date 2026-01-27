#!/bin/bash

###
### USB Recovery script for Abeille in Docker privileged mode
### Handles stale device nodes when udev doesn't work properly
### (C) 2026 - Based on diagnostic from Claude Code
###

LOG_FILE="/tmp/jeedom/Abeille/dockerUsbRecovery.log"
DISCONNECT_TIME=2
WAIT_DEVICE_TIME=3

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=========================================="
log "dockerUsbRecovery.sh starting"
log "=========================================="

# Check arguments
if [ $# -lt 1 ]; then
    log "ERROR: Usage: $0 <port> [gateway_id]"
    exit 1
fi

FULLDEV=$1
GATEWAY_ID=${2:-""}
USB_SERIAL=${3:-""}  # Optional: USB device serial number for reliable identification
log "Port: $FULLDEV, Gateway: $GATEWAY_ID, USB Serial: $USB_SERIAL"

# Function to find device by USB serial number
find_device_by_serial() {
    local search_serial="$1"
    for tty in /sys/class/tty/ttyUSB*; do
        [ -e "$tty" ] || continue
        local serial_file="$tty/device/../../serial"
        if [ -e "$serial_file" ]; then
            local serial=$(cat "$serial_file" 2>/dev/null)
            if [ "$serial" = "$search_serial" ]; then
                basename "$tty"
                return 0
            fi
        fi
    done
    return 1
}

# Function to get USB serial from current device
get_device_serial() {
    local dev="$1"
    local serial_file="/sys/class/tty/$dev/device/../../serial"
    if [ -e "$serial_file" ]; then
        cat "$serial_file" 2>/dev/null
    fi
}

# Resolve symlinks (e.g., /dev/serial/by-id/...)
if [[ "${FULLDEV}" == "/dev/serial/by-id"* ]]; then
    REALDEV=$(realpath "${FULLDEV}" 2>/dev/null)
    if [ -n "$REALDEV" ]; then
        log "Resolved symlink to: $REALDEV"
        DEV=${REALDEV#/dev/}
    else
        DEV=${FULLDEV#/dev/serial/by-id/}
        DEV="ttyUSB0"  # Default fallback
        log "Could not resolve symlink, defaulting to $DEV"
    fi
else
    DEV=${FULLDEV#/dev/}
fi

# Try to get USB serial number if not provided
if [ -z "$USB_SERIAL" ] && [ -e "/sys/class/tty/$DEV" ]; then
    USB_SERIAL=$(get_device_serial "$DEV")
    if [ -n "$USB_SERIAL" ]; then
        log "Detected USB serial number: $USB_SERIAL"
    fi
fi

log "Device name: $DEV, USB Serial: $USB_SERIAL"

# Step 1: Kill Abeille daemons to release file descriptors
log "Step 1: Stopping Abeille daemons..."
pkill -f 'AbeilleSerialRead.php' 2>/dev/null
pkill -f 'AbeilleParser.php' 2>/dev/null
pkill -f 'AbeilleCmd.php' 2>/dev/null
pkill -f 'AbeilleMainD.php' 2>/dev/null
sleep 1

# Verify daemons stopped
REMAINING=$(pgrep -f 'Abeille.*\.php' | wc -l)
if [ "$REMAINING" -gt 0 ]; then
    log "WARNING: $REMAINING daemon(s) still running, force killing..."
    pkill -9 -f 'AbeilleSerialRead.php' 2>/dev/null
    pkill -9 -f 'AbeilleParser.php' 2>/dev/null
    pkill -9 -f 'AbeilleCmd.php' 2>/dev/null
    pkill -9 -f 'AbeilleMainD.php' 2>/dev/null
    sleep 1
fi
log "Daemons stopped"

# Step 2: Check if device node exists and remove if stale
log "Step 2: Checking device nodes..."
if [ -e "/dev/$DEV" ]; then
    # Check if device is actually connected via sysfs
    if [ ! -e "/sys/class/tty/$DEV/device" ]; then
        log "Device node /dev/$DEV is stale (no sysfs entry), removing..."
        rm -f "/dev/$DEV"
    else
        log "Device node /dev/$DEV exists with valid sysfs entry"
    fi
fi

# Step 3: Find USB port for power cycling
log "Step 3: Identifying USB port..."
USB_PORT=""

# Try dmesg first
DMESG_LINE=$(dmesg 2>/dev/null | grep "now attached to $DEV" | tail -1)
if [ -n "$DMESG_LINE" ]; then
    USB_PORT=$(echo "$DMESG_LINE" | sed 's/^.*\(usb [0-9.-]*\):.*$/\1/' | sed 's/usb //')
    log "Found USB port from dmesg: $USB_PORT"
fi

# If dmesg failed, try sysfs
if [ -z "$USB_PORT" ] && [ -e "/sys/class/tty/$DEV" ]; then
    DEVICE_PATH=$(readlink -f "/sys/class/tty/$DEV/device" 2>/dev/null)
    if [ -n "$DEVICE_PATH" ]; then
        # Extract USB port from path like /sys/devices/platform/soc/3f980000.usb/usb1/1-1/1-1.3/1-1.3:1.0/ttyUSB0
        USB_PORT=$(echo "$DEVICE_PATH" | grep -oE '[0-9]+-[0-9]+(\.[0-9]+)*' | head -1)
        log "Found USB port from sysfs: $USB_PORT"
    fi
fi

# Step 4: Power cycle USB device
if [ -n "$USB_PORT" ]; then
    log "Step 4: Power cycling USB port $USB_PORT..."

    if [ -e "/sys/bus/usb/drivers/usb/unbind" ]; then
        # Unbind
        echo "$USB_PORT" > /sys/bus/usb/drivers/usb/unbind 2>/dev/null
        if [ $? -eq 0 ]; then
            log "USB unbind successful, waiting ${DISCONNECT_TIME}s..."
            sleep $DISCONNECT_TIME

            # Bind
            echo "$USB_PORT" > /sys/bus/usb/drivers/usb/bind 2>/dev/null
            if [ $? -eq 0 ]; then
                log "USB bind successful, waiting ${WAIT_DEVICE_TIME}s for device..."
                sleep $WAIT_DEVICE_TIME
            else
                log "WARNING: USB bind failed"
            fi
        else
            log "WARNING: USB unbind failed, trying alternative method..."
            # Try authorized toggle
            if [ -e "/sys/bus/usb/devices/$USB_PORT/authorized" ]; then
                echo 0 > "/sys/bus/usb/devices/$USB_PORT/authorized"
                sleep $DISCONNECT_TIME
                echo 1 > "/sys/bus/usb/devices/$USB_PORT/authorized"
                sleep $WAIT_DEVICE_TIME
                log "Authorized toggle completed"
            fi
        fi
    else
        log "WARNING: unbind interface not available"
    fi
else
    log "WARNING: Could not identify USB port, skipping power cycle"
fi

# Step 5: Find device by USB serial number (most reliable method)
log "Step 5: Locating device after power cycle..."
sleep 1

FOUND_DEV=""

# First, try to find by USB serial number (most reliable)
if [ -n "$USB_SERIAL" ]; then
    log "Searching for device with USB serial: $USB_SERIAL"
    FOUND_DEV=$(find_device_by_serial "$USB_SERIAL")
    if [ -n "$FOUND_DEV" ]; then
        log "Found device by serial number: $FOUND_DEV"
        DEV="$FOUND_DEV"
    else
        log "Device with serial $USB_SERIAL not found in sysfs yet"
    fi
fi

# Fallback: check what devices exist
if [ -z "$FOUND_DEV" ]; then
    for d in /sys/class/tty/ttyUSB*; do
        if [ -e "$d" ]; then
            FOUND_DEV=$(basename "$d")
            FOUND_SERIAL=$(get_device_serial "$FOUND_DEV")
            log "Found device in sysfs: $FOUND_DEV (serial: $FOUND_SERIAL)"
            # Use first found device if no serial match
            if [ -z "$USB_SERIAL" ] || [ "$FOUND_SERIAL" = "$USB_SERIAL" ]; then
                DEV="$FOUND_DEV"
                break
            fi
        fi
    done
fi

# Step 6: Create device node manually if needed (Docker/udev issue)
if [ ! -e "/dev/$DEV" ]; then
    log "Step 6: Device node /dev/$DEV not found, checking sysfs..."

    # Check if device exists in sysfs
    if [ -e "/sys/class/tty/$DEV/dev" ]; then
        MAJOR_MINOR=$(cat "/sys/class/tty/$DEV/dev")
        MAJOR=${MAJOR_MINOR%:*}
        MINOR=${MAJOR_MINOR#*:}
        log "Creating device node /dev/$DEV (major:$MAJOR, minor:$MINOR)"
        mknod "/dev/$DEV" c "$MAJOR" "$MINOR"
        chmod 666 "/dev/$DEV"

        if [ -e "/dev/$DEV" ]; then
            log "Device node created successfully"
        else
            log "ERROR: Failed to create device node"
            exit 2
        fi
    else
        # Try to find any ttyUSB in sysfs
        for sysdev in /sys/class/tty/ttyUSB*; do
            if [ -e "$sysdev/dev" ]; then
                FOUND_DEV=$(basename "$sysdev")
                MAJOR_MINOR=$(cat "$sysdev/dev")
                MAJOR=${MAJOR_MINOR%:*}
                MINOR=${MAJOR_MINOR#*:}

                if [ ! -e "/dev/$FOUND_DEV" ]; then
                    log "Creating device node /dev/$FOUND_DEV (major:$MAJOR, minor:$MINOR)"
                    mknod "/dev/$FOUND_DEV" c "$MAJOR" "$MINOR"
                    chmod 666 "/dev/$FOUND_DEV"
                fi

                # If original device was ttyUSB0 but we found ttyUSB1, create symlink or adjust
                if [ "$FOUND_DEV" != "$DEV" ] && [ ! -e "/dev/$DEV" ]; then
                    log "NOTE: Device appeared as $FOUND_DEV instead of $DEV"
                    # Create the expected device too if minor matches expected
                    if [ "$DEV" = "ttyUSB0" ] && [ "$FOUND_DEV" = "ttyUSB0" ]; then
                        log "Device is already at expected location"
                    fi
                fi
            fi
        done
    fi
else
    log "Step 6: Device node /dev/$DEV exists"
fi

# Step 7: Verify device is accessible
log "Step 7: Verifying device..."
if [ -e "/dev/$DEV" ]; then
    # Try to configure the serial port
    stty -F "/dev/$DEV" 115200 raw -echo 2>/dev/null
    if [ $? -eq 0 ]; then
        log "Device /dev/$DEV configured successfully (115200 baud)"
    else
        log "WARNING: Could not configure /dev/$DEV"
    fi
else
    log "ERROR: Device /dev/$DEV still not available"
    # List what we have
    log "Available devices:"
    ls -la /dev/ttyUSB* 2>/dev/null | tee -a "$LOG_FILE"
    ls -la /sys/class/tty/ttyUSB*/dev 2>/dev/null | tee -a "$LOG_FILE"
    exit 3
fi

# Step 8: Daemons will be restarted by Jeedom cron
log "Step 8: Recovery complete"
log "Daemons will be restarted automatically by Jeedom cron (within 1 minute)"
log "=========================================="

exit 0
