# coding: utf-8

# WORK IN PROGRESS - UNUSED so far
# Trials to migrate some parts in Python.

import os.path
import json
from collections import OrderedDict
import sys
import time
import subprocess

#
# * AbeilleSerialReadX
# *
# * - Read Zigate messages from selected port (ex: /dev/ttyXX)
# * - Transcode data from binary to hex (note: ALL HEX are converted UPPERCASE)
# * - and send message to parser thru queue.
# *
# * Usage:
# * /usr/bin/php /var/www/html/plugins/Abeille/core/php/AbeilleSerialRead.php <AbeilleX> <ZigatePort> <DebugLevel>
# *
#

# include_once str(__DIR__) + '/../config/Abeille.config.php';
# Developers mode ?

# if (os.path.isfile(dbgFile)):
    # include_once dbgFile;
    # Dev mode: enabling PHP errors logging

    # error_reporting(32767);
    # ini_set('error_log', str(__DIR__) + '/../../../../log/AbeillePHP.log');
    # ini_set('log_errors', 'On');

# include_once str(__DIR__) + '/../../../../core/php/core.inc.php';
# include_once str(__DIR__) + '/AbeilleLog.php';
# include_once str(__DIR__) + '/../class/AbeilleTools.class.php';
# Tcharp38: Ouahhh. How can it handle multi-zigate ? Who is
#   dealing with concurrent msg_send() on the same queue ?

def msgToParser(msgToSend):

    # global queueXToParser
    # jsonMsg = json.dumps(msgToSend)
    # # Note: '@' to suppress PHP warning message.
    # if (@msg_send(queueXToParser, 1, jsonMsg, False, False, errCode) == False):
    #     logMessage('debug', 'msg_send(queueXToParser): ERROR ' + str(errCode))
    #     logMessage('debug', '  msg=' + json.dumps(msgToSend))
    #     return False

    return True

# Wait for port to be available, configure it then open it
def waitPort(serial):

    print("waitPort("+serial+")")
    while (True):
        # Wait for port
        print("wait for port "+serial)
        while (True):
            if (os.path.exists(serial)):
                break
            time.sleep(0.5) # Sleep 500ms

        # Port exists. Let's try to configure
        # exec(str(str(str(system.getCmdSudo()) + 'sudo chmod 666 ') + str(serial)) + ' >/dev/null 2>&1');
        os.system("sudo chmod 666 " + str(serial) + ' >/dev/null 2>&1')
        # exec(str("stty -F " + str(serial)) + " sane >/dev/null 2>&1", out, status);
        print("stty sane")
        proc = subprocess.Popen(['stty', '-F', str(serial), 'sane'],
           stdout=subprocess.PIPE,
           stderr=subprocess.STDOUT)
        status = proc.wait()
        if (status == 0):
            # exec(str("stty -F " + str(serial)) + " speed 115200 cs8 -parenb -cstopb -echo raw >/dev/null 2>&1", out, status);
            print("stty speed...")
            proc = subprocess.Popen(['stty', '-F', str(serial), 'speed', '115200', 'cs8', '-parenb', '-cstopb', '-echo', 'raw'],
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT)
            status = proc.wait()
        if (status != 0):
            # Could not configure it properly
            # err = implode("/", out)
            # logMessage('debug', 'stty error: ' + str(err))
            print("ERROR: stty config failed")
            continue

        # Config done. Opening
        try:
            f = open(serial, "r")
            print(str(serial)+" port opened")
            # logMessage('debug', str(serial) + ' port opened')
            # stream_set_blocking(f, True)
            # Should be blocking read but is it default ?
            # $firstFrame = true; // First frame might be corrupted
            return f
        except:
            print("ERROR: open")
            time.sleep(0.5) # Sleep 500ms


# logSetConf('', True);
# Log to STDOUT until log name fully known (need Zigate number)
# logMessage('info', '>>> Demarrage d \  ' AbeilleSerialRead sur port ' + str(argv[2]));
# Checking parameters

argc = len(sys.argv)
if (argc < 3):
    # Currently expecting AbeilleSerialRead.py <AbeilleX> <ZigatePort>
    # logMessage('error', 'Argument(s) manquant(s)');
    print("ERROR: Missing args: 3 expected, got "+str(argc))
    exit(1)

net = sys.argv[1]
if (net[0:7] != "Abeille"):
    # logMessage('error', 'Argument 1 incorrect (devrait etre \'AbeilleX\')');
    print("ERROR: arg1 != AbeilleX")
    exit(2)

# Network name (ex: 'Abeille1')
serial = sys.argv[2]
# Zigate port (ex: '/dev/ttyUSB0')
# requestedlevel = sys.argv[3]
# Currently unused
zgId = int(net[7: ])
# Zigate number (ex: 1)
# logSetConf(str("AbeilleSerialRead" + str(zgId)) + ".log", True);
# Log to file with line nb check
# Check if already running
# config = AbeilleTools.getParameters();
# running = AbeilleTools.getRunningDaemons();
# daemons = AbeilleTools.diffExpectedRunningDaemons(config, running);
# logMessage('debug', 'Daemons=' + json.dumps(daemons));
# if (daemons["serialRead" + str(zgId)] > 1):
#     logMessage('error', str('Un demon AbeilleSerialRead' + str(zgId)) + ' est deja lance.');
#     exit(4);

# declare (ticks=1);
# pcntl_signal(15, 'signalHandler', False);
# def signalHandler(signal):

#     global f, zgId;
#     f.close();
#     logMessage('info', '<<< Arret du demon AbeilleSerialRead' + str(zgId));
#     exit;

# if (serial == 'none'):
#     serial = str(resourcePath) + '/COM';
#     logMessage('info', 'Main: com file (experiment): ' + str(serial));
#     exec(str(str(str(system.getCmdSudo()) + 'touch ') + str(serial)) + ' > /dev/null 2>&1');

# $firstFrame = true; // To indicate that first frame might be corrupted
# TODO Tcharp38: May make sense to wait for port to be ready
# to cover socat > serialread case if socat starts later.
# if (!file_exists($serial)) {
#     logMessage('error', 'Le port '.$serial.' n\'existe pas ! Arret du démon');
#     exit(3);
# }
f = waitPort(serial)

# function shutdown($sig, $sigInfos) {
#     pcntl_signal($sig, SIG_IGN);
#     logMessage("info", "<<< Arret d'AbeilleSerialRead".$zgId);
#     exit(0);
# }
# declare(ticks = 1);
# if (pcntl_signal(SIGTERM, "shutdown", false) != true)
#     logMessage("error", "Erreur pcntl_signal()");

# queueXToParser = msg_get_queue(abQueues["xToParser"]["id"])
# queueMax = abQueues["xToParser"]["max"]
# Inform others that i'm ready to process zigate messages

import queue
q = queue
while True:
    print("in q")
    time.sleep(1)


# $msgToSend = array(
#     'src' => 'serialRead',
#     'net' => $net,
#     'type' => 'status',
#     'status' => 'ready',
# );
# msgToParser($msgToSend);
transcode = False
frame = ""
# Transcoded message from Zigate
step = "WAITSTART"
ecrc = 0
# Expected CRC
ccrc = 0
# Calculated CRC
byteIdx = 0
# Byte number
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
#

while (True):
    # Check if port still there.
        #   Key for connection with Socat

    if (not os.path.isfile(serial)):
        f.close()
        # logMessage('debug', str('ERROR: Serial port ' + str(serial)) + ' disappeared !');
        f = waitPort(serial)
        # logMessage('debug', $serial.' port is back');

    byte = fread(f, 01)
    byte = hex(byte).upper()
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
            logMessage('debug', 'Got ' + json.dumps(frame))
            if (ccrc != ecrc):
                # CRC ERROR => no longer transmitted to Parser
                logMessage('debug', str(str(str(str(str(str('ERROR: CRC: got=0x' + hex(ccrc)) + ', exp=0x') + hex(ecrc)) + ', msg=') + frame[0:0 + 12]) + '...') + frame[-2:-2 + 2]);
            else:
                msgToSend = OrderedDict([('type','serialRead'),('net',net),('msg',frame)]);
                msgToParser(msgToSend)

            # $frame = "";
            step = "WAITSTART"
        else:
            if (byte == "02"):
                transcode = True
                # Next char to be transcoded
            else:
                if (transcode):
                    byte = sprintf("%02X", int(byte,16) ^ 0x10)
                    transcode = False

                frame += byte
                if (byteIdx == 5):
                    ecrc = int(byte,16)
                else:
                    ccrc = ccrc ^ int(byte,16)

                byteIdx+=1

f.close()
# logMessage('info', '<<< Fin du démon AbeilleSerialRead' + str(zgId));