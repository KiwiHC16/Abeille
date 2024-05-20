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
		cmdId = 0xFFFF

	return cmdId

ezspQueue = []

# Send EZSP cmd
# Returns: True=OK, False=ERROR
def ezspSend(serPort, cmdName, params = b''):
	print("ezspSend(%s)" % cmdName)

	# Initial checks
	frameId = cmdName2Id(cmdName)
	if (frameId == 0xFFFF):
		return False

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
	frmCtrl = (fcLow << 7) | fcHigh
	# frameId = cmdName2Id(cmdName)

	# Creating EZSP frame
	ezspFrame = bytes()
	ezspFrame += seq.to_bytes(1, "big")
	if (cmdName == "version"):
		ezspFrame += fcLow.to_bytes(1, "big")
		ezspFrame += frameId.to_bytes(1, "big") # '0000' => '00'
	else:
		ezspFrame += frmCtrl.to_bytes(2, "big")
		ezspFrame += frameId.to_bytes(2, "big")
	# Adding parameters if any
	if (len(params) != 0):
		ezspFrame += params
	print("  ezspFrame=%s" % ezspFrame.hex())

	# Storing cmd
	cmd = {"name":cmdName, "seq": seq}
	ezspQueue[seq] = cmd

	status = ashSend(serPort, "DATA", ezspFrame)
	if (status == True):
		ezspSeq += 1
		if (ezspSeq > 255):
			ezspSeq = 0

	return status

# Read msg from gateway
# Returns: status (True/False), cmd (dict)
def ezspRead(serPort):

	status, msg = ashRead(serPort)
	if (msg["name"] == "DATA"):
		data = cmd["data"]
		seq = data[0]
		# Frame control
		# bit7: 0=cmd, 1=response
		frmCtrlL = int.from_bytes(data[1], 'big')
		if (frmCtrlL >> 7): # Response ?
			cmd = ezspCmdBySeq(seq) # Identify corresponding cmd
		else:
			print("  NOT a response")
			pass # TODO
		frmCtrlH = int.from_bytes(data[2], 'big')
		frmId = int.from_bytes(data[3:4], 'big')
		print("  data=%s => seq=%d, frmCtrlL=0x%X, frmId=0x%X" % (data.hex(), seq, frmCtrlL, frmId))

	return status, cmd
