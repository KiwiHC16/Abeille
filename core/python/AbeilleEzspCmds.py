# Abeille plugin for Jeedom
# EmberZnet/EZSP commands
# Tcharp38

from AbeilleEzspAsh import *

ezspSeq = 0 	# Sequence number
ezspQueue = [] 	# TX cmd queue

ezspCmdsList = {
	"version" : {
		"cmdId": 0x0,
		"response": {
			"protocolVersion": "uint8_t", # The EZSP version the NCP is using.
			"stackType": "uint8_t", # The type of stack running on the NCP (2).
			"stackVersion": "uint16_t"
		}
	}
}

def cmdName2Id(cmdName):
	if cmdName in ezspCmdsList:
		cmd = ezspCmdsList[cmdName]
		cmdId = cmd['cmdId']
	# if cmdName == "version":
	# 	cmdId = 0x0000
	# elif cmdName == "nop":
	# 	cmdId = 0x0005
	# elif cmdName == "getMfgToken":
	# 	cmdId = 0x000B
	else:
		print("ERROR: cmdName2Id(): cmdName %s is unknown" % cmdName)
		cmdId = 0xFFFF

	return cmdId

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
	cmd = {"seq": seq, "name":cmdName}
	global ezspQueue
	ezspQueue.append(cmd)

	status = ashSend(serPort, "DATA", ezspFrame)
	if (status == True):
		ezspSeq += 1
		if (ezspSeq > 255):
			ezspSeq = 0

	return status

# Check is seq is a sent command
# Returns: status (True/False), cmd
def ezspCmdBySeq(seq):
	for c in ezspQueue:
		# print("  ezspCmdBySeq(), c=", c)
		if (c["seq"] == seq):
			return True, c
	return False, {}

# Decode received cmd
def ezspDecode(cmd, params):
	if (params == b''):
		return cmd # Nothing to decode

	cmdName = cmd['name']
	cmdFmt = ezspCmdsList[cmdName]
	response = cmdFmt['response']
	pIdx = 0 # 'params' index
	for r in response:
		# print("  r=", r)
		t = response[r]
		if (t == "uint8_t"):
			cmd[r] = params[pIdx]
			pIdx += 1
		elif (t == "uint16_t"):
			cmd[r] = int.from_bytes(params[pIdx:pIdx+1], 'big')
			pIdx += 2
		else:
			print("  ERROR: ezspDecode(): Unsupported type '%s'" % t)

	return cmd

# Read msg from gateway
# Returns: status (True/False), cmd (dict)
def ezspRead(serPort):

	print("ezspRead()")

	status, msg = ashRead(serPort)
	if (status == False):
		return False, {}
	if (msg["name"] == "DATA"):
		data = msg["data"]
		seq = data[0]
		# Frame control
		# bit7: 0=cmd, 1=response
		frmCtrlL = data[1]
		versionCmd = False # 'version' cmd is a special case
		if (frmCtrlL >> 7): # Response ?
			status, cmd = ezspCmdBySeq(seq)
			if (status == True):
				# print("  cmd=", cmd)
				if (cmd["name"] == "version"):
					versionCmd = True
			else:
				print("  Unknown response cmd seq=%d => Ignoring" % seq)
		else:
			print("  NOT a response")
			pass # TODO
		if (versionCmd):
			frmCtrl = frmCtrlL
			frmId = data[2]
			params = data[3:]
		else:
			frmCtrl = (frmCtrlL << 8) | data[2] # Frame control LOW | HIGH bytes
			frmId = int.from_bytes(data[3:4], 'big')
			params = data[5:]
		# print("  data=%s => seq=%d, frmCtrl=0x%X, frmId=0x%X" % (data.hex(), seq, frmCtrl, frmId))
		cmd = ezspDecode(cmd, params)
		print("  EZSP-cmd=", cmd)
		return status, cmd

	print("  ERROR: ezspRead(): Unsupported msg")
	return False, {}
