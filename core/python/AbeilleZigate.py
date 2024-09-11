### Python interface for Zigate
### Requires python3
# coding: utf-8

import os.path
# import json
from collections import OrderedDict
import sys
import time
import subprocess

# Global vars
zgDebug = 0 # Debug/verbose mode (0=none)

# Print function if debug mode enabled
def zgLog(txt):
    if zgDebug == 0:
        return
    print(txt)

# Wait for port to be available, configure it then open it
def zgWaitPort(serial):

    zgLog("zgWaitPort("+serial+")")
    while (True):
        # Wait for port
        zgLog("wait for port "+serial)
        while (True):
            if (os.path.exists(serial)):
                break
            time.sleep(0.5) # Sleep 500ms

        # Port exists. Let's try to configure
        # exec(str(str(str(system.getCmdSudo()) + 'sudo chmod 666 ') + str(serial)) + ' >/dev/null 2>&1');
        os.system("sudo chmod 666 " + str(serial) + ' >/dev/null 2>&1')
        # exec(str("stty -F " + str(serial)) + " sane >/dev/null 2>&1", out, status);
        zgLog("stty sane")
        proc = subprocess.Popen(['stty', '-F', str(serial), 'sane'],
           stdout=subprocess.PIPE,
           stderr=subprocess.STDOUT)
        status = proc.wait()
        if (status == 0):
            # exec(str("stty -F " + str(serial)) + " speed 115200 cs8 -parenb -cstopb -echo raw >/dev/null 2>&1", out, status);
            zgLog("stty speed...")
            proc = subprocess.Popen(['stty', '-F', str(serial), 'speed', '115200', 'cs8', '-parenb', '-cstopb', '-echo', 'raw'],
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT)
            status = proc.wait()
        if (status != 0):
            # Could not configure it properly
            # err = implode("/", out)
            # logMessage('debug', 'stty error: ' + str(err))
            zgLog("ERROR: stty config failed")
            continue

        # Config done. Opening
        try:
            f = open(serial, "rb")
            zgLog(str(serial)+" port opened")
            # logMessage('debug', str(serial) + ' port opened')
            # stream_set_blocking(f, True)
            # Should be blocking read but is it default ?
            # $firstFrame = true; // First frame might be corrupted
            return f
        except:
            zgLog("ERROR: open failed. Retrying ...")
            time.sleep(0.5) # Sleep 500ms

def zgOpenPort(serial):
    zgLog("zgOpenPort(" + serial + ")")
    f = zgWaitPort(serial)
    return 0, f

# Read message from given port handler
def zgReadMsg(f):

    zgLog("zgReadMsg()")

    transcode = False
    frame = ""  # Transcoded message from Zigate
    step = "WAITSTART"
    ecrc = 0    # Expected CRC
    ccrc = 0    # Calculated CRC
    byteIdx = 0 # Byte number

    # Protocol reminder:
    #       00   : 01 = start
    #       01-02: Msg Type
    #       03-04: Length => Payload size + 1 byte for LQI
    #       05   : crc
    #       xx   : payload
    #       last-1: LQI
    #       last : 03 = stop
    #
    #       CRC = 0x00 XOR MSG-TYPE XOR LENGTH XOR PAYLOAD XOR LQI

    while (True):

        # Check if port still there.
        #   Key for connection with Socat
        # if (not os.path.isfile(serial)):
        #     f.close()
        #     # logMessage('debug', str('ERROR: Serial port ' + str(serial)) + ' disappeared !');
        #     f = zgWaitPort(serial)
        #     print("%s port is back" % serial)

        dataBA = bytearray(f.read(1))
        # print("dataBA type=", type(dataBA))
        data = dataBA[0]
        # print("data=", data)
        byte = "%02X" % data
        # print("byte=", byte)
        if (step == "WAITSTART"):
            # Waiting for "01" start byte.
            #   Bytes outside 01..03 markers are unexpected.

            if (byte != "01"):
                # $frame .= $byte; // Unexpected outside 01..03 markers => error
                continue
                # Everything till '01' is ignored

            # "01" start found

            # if (($frame != "") && !$firstFrame)
            #     logMessage('debug', 'ERROR: Frame outside 01/02 markers: '.json_encode($frame));
            frame = ""
            # $firstFrame = false;
            step = "WAITEND"
            byteIdx = 1
            # Next byte is index 1
            ccrc = 0
        else:
            # Step = WAITEND
            # Waiting for "03" end byte

            if (byte == "03"):
                # logMessage('debug', 'Got ' + json.dumps(frame))
                if (ccrc != ecrc):
                    # CRC ERROR => no longer transmitted to Parser
                    zgLog('ERROR: CRC: got=0x%X, exp=0x%X, msg=%s' % (hex(ccrc), hex(ecrc), frame[0:0 + 12]))
                else:
                    # msgToSend = OrderedDict([('type','serialRead'),('net',net),('msg',frame)]);
                    # msgToParser(msgToSend)
                    zgLog("frame=" + frame)
                    return frame

                # $frame = "";
                step = "WAITSTART"
            else:
                if (byte == "02"):
                    transcode = True
                    # Next char to be transcoded
                else:
                    if (transcode):
                        byte = "%02X" % (int(byte,16) ^ 0x10)
                        transcode = False

                    frame += byte
                    if (byteIdx == 5):
                        ecrc = int(byte,16)
                    else:
                        ccrc = ccrc ^ int(byte,16)

                    byteIdx+=1

    # f.close()

def zgComposeMsg(msgType, *args):
    zgLog("zgComposeMsg(%s)" % msgType)

    # TODO: Ensure msgType is a 4 char string
    payload = "".join(args)
    payloadLen = len(payload) // 2
    # Computing checksum (msgType xor length xor payload)
    crc = 0
    for i in range(0, len(msgType), 2):
        crc = crc ^ int(msgType[i:i+2], 16)
    pl_len = "{:04X}".format(payloadLen)
    for i in range(0, len(pl_len), 2):
        crc = crc ^ int(pl_len[i:i+2], 16)
    for i in range(0, len(payload), 2):
        crc = crc ^ int(payload[i:i+2], 16)
    msg = ""
    msg += msgType  # Message type, 2 bytes
    msg += "{:04X}".format(payloadLen)  # Payload length, 2 bytes
    msg += "{:02X}".format(crc)  # Checksum, 1 byte
    msg += payload  # Payload
    # log_message('debug', 'msg={}'.format(msg))
    return msg

def zgMsgToFrame(msg):
    msgout = ""
    msgsize = len(msg)
    for i in range(0, msgsize, 2):
        byte = msg[i:i+2]
        if int(byte, 16) < 0x10:
            msgout += "02{:02X}".format((int(byte, 16) ^ 0x10))
        else:
            msgout += byte
    return "01" + msgout + "03"

def zgWrite(f, zgMsg):
    zgLog("zgWrite(%s)" % zgMsg)

    if not f:
        zgLog("zgWrite() END: fopen ERROR")
        return -1
    frame = zgMsgToFrame(zgMsg)
    status = f.write(bytes.fromhex(frame))
    f.flush()
    if not status:
        zgLog("zgWrite() END: fwrite ERROR")
        return -1
    # logging.debug("zgWrite() END")
    return 0

# Read FW version from current opened port
# Returns version as string ("MMMM-mmmm") or "" if error
def zgGetFwVersion(fR, fW, timeout=0):
    if (not fR) or (not fW):
        zgLog("ERROR: zgGetFwVersion(): no opened port")
        return ""

    if (timeout != 0):
        import signal
        signal.signal(signal.SIGALRM, zgTimeoutHandler)
        signal.alarm(timeout)

    try:
        zgMsg = zgComposeMsg("0010")
        zgLog("zgMsg=" + zgMsg)
        zgWrite(fW, zgMsg)
        while True:
            msg = zgReadMsg(fR)
            msgType = msg[0:4]
            zgLog("msgType=" + msgType)
            if (msgType == "8000"):
                break
        while True:
            msg = zgReadMsg(fR)
            msgType = msg[0:4]
            zgLog("msgType=" + msgType)
            if (msgType == "8010"):
                break
        major = msg[10:14]
        minor = msg[14:18]
        version = major + '-' + minor
        zgLog("Version " + version)
        return version
    except Exception:
        # print("Exception ", exc)
        signal.alarm(0)
        return ""

def zgTimeoutHandler():
    raise Exception("zgTimeout")

# Set 'flash' or 'prod' mode for PI Zigate
# gpioLib = WiringPi or PiGpio
# Returns: status (0/-1) + err msg
def zgSetPIMode(mode, gpioLib):
    zgLog("zgSetPIMode("+mode+", "+gpioLib+")")
    if (mode != "prod") and (mode != "flash"):
        return -1, "Wrong mode"
    if (gpioLib != "WiringPi") and (gpioLib != "PiGpio"):
        return -1, "Wrong GPIO lib"

    # PiZigate reminder
    # port 0 = RESET
    # port 2 = FLASH
    # Production mode: FLASH=1, RESET=0 then 1
    # Flash mode: FLASH=0, RESET=0 then 1

    if gpioLib == "WiringPi":
        import subprocess
        result = subprocess.run("gpio -v &>/dev/null", shell=True)
        if (result.returncode != 0):
            return -1, "zgSetPIMode: 'gpio' cmd not found. Is 'WiringPi' properly installed ?"
        if mode == "prod":
            result = subprocess.run("gpio mode 0 out; gpio mode 2 out; gpio write 2 1; gpio write 0 0; sleep 1; gpio write 0 1", shell=True)
        else: # "flash" mode
            result = subprocess.run("gpio mode 0 out; gpio mode 2 out; gpio write 2 0; gpio write 0 0; sleep 1; gpio write 0 1", shell=True)
        # print("result=", result)
        if (result.returncode != 0):
            return -1, "zgSetPIMode: gpio config failed with WiringPi"

        # TODO: We could check proper GPIO state with 'gpio read x'

    elif gpioLib == "PiGpio":
        # WARNING !!! This part has NOT been approved yet !
        try:
            import pigpio
        except ImportError as e:
            return -1, "zgSetPIMode: PiGpio-python3 installation missing"

        pi = pigpio.pi()
        if not pi.connected:
            return -1, "zgSetPIMode: PiGpio init failed. Is 'pigpiod' started ?"

        # GPIO 14 TXD
        # GPIO 15 RXD
        resetPort = 17
        flashPort = 27

        pi.set_mode(resetPort, pigpio.OUTPUT)
        pi.set_mode(flashPort, pigpio.OUTPUT)

        if mode == "prod":
            pi.write(flashPort, 1)
        else: # "flash" mode
            pi.write(flashPort, 0)
        pi.write(resetPort, 0)
        time.sleep(0.5)
        pi.write(resetPort, 1)
    else:
        return -1, "zgSetPIMode: Unsupported GPIO lib "+gpioLib

    return 0, ""

# Check if given port is free
def zgIsPortFree(port):
    # WARNING !!! This part has NOT been approved yet !
    result = subprocess.Popen("sudo lsof -Fcn %s" % port)
    print("result=", result)
    return 0, "" # Ok, free

# Main
if __name__ == '__main__':

    # Checking arguments
    # If first arg is a port name => port test mode

    nbArgs = len(sys.argv) # arg0=script name
    if (nbArgs < 2):
        print("ERROR: Missing argument(s)")
        exit(1)
    if os.path.exists(sys.argv[1]):
        port = sys.argv[1]
        print("Testing Zigate access on port "+port)
        zgDebug = 1
        err, f = zgOpenPort(port)
        fW = open(port, "wb")
        version = zgGetFwVersion(f, fW)
        f.close()
        fW.close()
    else:
        action = sys.argv[1]
        if (action == "zgSetPiMode") or (action == "setPiMode"):
            if (nbArgs < 3):
                print("ERROR: Missing mode")
                exit(1)
            mode = sys.argv[2]
            err, msg = zgSetPIMode(mode, sys.argv[3])
            if err != 0:
                print("ERROR: "+msg)
                exit(2)
        elif (action == "isPortFree"):
            if (nbArgs < 3):
                print("ERROR: Missing port name")
                exit(1)
            port = sys.argv[2]
            print("Checking if port %s is free " % port)
            err, msg = zgIsPortFree(port)
            if err != 0:
                print("ERROR: "+msg)
                exit(2)
        elif (action == "readFwVersion"):
            port = sys.argv[2]
            # print("Testing Zigate access on port "+port)
            zgDebug = 1
            err, f = zgOpenPort(port)
            if err != 0:
                print("ERROR: zgOpenPort() failed")
                exit(2)
            fW = open(port, "wb")
            version = zgGetFwVersion(f, fW, 5)
            f.close()
            fW.close()
            if (version == ""):
                print("ERROR: Timeout !")
                exit(2)
            exit(0)
        else:
            print("ERROR: Unsupported action "+action)
            exit(1)

    exit(0)
