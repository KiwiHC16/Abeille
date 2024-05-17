# Abeille plugin for Jeedom
# EmberZnet/EZSP commands
# Tcharp38

ezspSeq = 0 # Sequence number
ashFrmNum = 0 # Last transmitted ASH frame

# Encapsulate EZSP data into ASH protocol
def ashFormat(ashCmdName, data):
	ashFrame = bytes()
	if (ashCmdName == "DATA"):
		frmNum = ashFrmNum + 1 # Last transmitted frame + 1
		if (frmNum > 255):
			frmNum = 0
		reTx = 0 # set to 1 in a retransmitted DATA frame
		ackNum = 12
		ctrlByte = (frmNum << 4) | (reTx << 3) | ackNum
		crc = 12
		flagByte = 0x7E

		ashFrame += ctrlByte.to_bytes(1, "big")
		ashFrame += data
		ashFrame += crc.to_bytes(2, "big")
		ashFrame += flagByte.to_bytes(1, "big")
	else:
		print("ERROR:")
		return

	return ashFrame

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

def ezspSend(serPort, cmdName):
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
	# TODO: Add parameters if any
	print("ezspFrame=", ezspFrame)

	# Creating ASH frame
	ashFrame = ashFormat("DATA", ezspFrame)

	dataBytes = bytes(ashFrame)
	serPort.write(dataBytes)

	# If SENT
	ezspSeq += 1
	if (ezspSeq > 255):
		ezspSeq = 0
	global ashFrmNum
	ashFrmNum += 1
	if (ashFrmNum > 255):
		ashFrmNum = 0

def sendCmd(serPort, cmdName):
	print("send(%s)" % cmdName)

	if (cmdName == "RST"):
		ezsp = [0xC0, 0x38, 0xBC, 0x7E]

	elif (cmdName == "version"):
		ezsp = [0x25, 0x00, 0x00, 0x00, 0x02, 0x1A, 0xAD, 0x7E]

	elif (cmdName == "getMfgToken"):
		ezspSend("getMfgToken")
		return

	else:
		print("sendCmd() ERROR: Unknown cmd %s" % cmdName)
		return

	dataBytes = bytes(ezsp)
	serPort.write(dataBytes)
