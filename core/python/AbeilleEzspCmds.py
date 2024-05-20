# Abeille plugin for Jeedom
# EmberZnet/EZSP commands
# Tcharp38

from AbeilleEzspAsh import *

ezspSeq = 0 # Sequence number

def cmdName2Id(cmdName):
	if cmdName == "version":
		cmdId = 0x0000
	elif cmdName == "nop":
		cmdId = 0x0005
	elif cmdName == "getMfgToken":
		cmdId = 0x000B
	else:
		print("ERROR: cmdName2Id(): cmdName %s is unknown" % cmdName)
		cmdId = 0x0000

	return cmdId

# Send EZSP cmd
# Returns: True=OK, False=ERROR
def ezspSend(serPort, cmdName, params = b''):
	print("ezspSend(%s)" % cmdName)

	# Reminder: Seq | Frame control (2B) | Frame ID (2B) | Parameters
	global ezspSeq
	seq = ezspSeq
	nwkIdx = 0 # ?
	sleepMode = 0 # ?
	fcLow = (nwkIdx << 5) | sleepMode
	secEnabled = 0 # ?
	padEnabled = 0 # ?
	frmFormatVersion = 0 # ?
	fcHigh = (secEnabled << 7) | (padEnabled << 6) | frmFormatVersion
	fc = (fcHigh << 7) | fcLow
	frameId = cmdName2Id(cmdName)

	# Creating EZSP frame
	ezspFrame = bytes()
	ezspFrame += seq.to_bytes(1, "big")
	ezspFrame += fc.to_bytes(2, "big")
	ezspFrame += frameId.to_bytes(2, "big")
	# Adding parameters if any
	if (len(params) != 0):
		ezspFrame += params
	print("  ezspFrame=%s" % ezspFrame.hex())

	status = ashSend(serPort, "DATA", ezspFrame)
	if (status == True):
		ezspSeq += 1
		if (ezspSeq > 255):
			ezspSeq = 0

	return status

# def sendCmd(serPort, cmdName):
# 	print("send(%s)" % cmdName)

# 	if (cmdName == "RST"):
# 		ezsp = [0xC0, 0x38, 0xBC, 0x7E]
# 		test = ashFormat("RST")
# 		test = ashFormat("RSTACK", bytes([0x02, 0x02]))

# 	elif (cmdName == "version"):
# 		ezsp = [0x25, 0x00, 0x00, 0x00, 0x02, 0x1A, 0xAD, 0x7E]

# 	elif (cmdName == "getMfgToken"):
# 		ezspSend("getMfgToken")
# 		return

# 	else:
# 		print("sendCmd() ERROR: Unknown cmd %s" % cmdName)
# 		return

# 	dataBytes = bytes(ezsp)
# 	serPort.write(dataBytes)
