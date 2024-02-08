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
def zgGetFwVersion(fR, fW):
    if (not fR) or (not fW):
        zgLog("ERROR: zgGetFwVersion(): no opened port")
        return ""

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

# Main
if __name__ == '__main__':

    # Checking arguments
    nbArgs = len(sys.argv) # arg0=script name
    if nbArgs < 2:
        print("ERROR: Missing port name")
        exit(1)
    port = sys.argv[1]
    zgDebug = 1

    err, f = zgOpenPort(port)
    fW = open(port, "wb")
    version = zgGetFwVersion(f, fW)
    f.close()
    fW.close()

