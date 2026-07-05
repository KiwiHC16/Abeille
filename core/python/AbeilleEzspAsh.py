# Abeille plugin for Jeedom
# EmberZnet/EZSP commands
# Tcharp38

ashFrmNum = 0 # Next ASH frame to be transmitted (0->7)
ashAckNum = 1 # Acknowledges receipt of DATA frames up to, but not including, ackNum (0->7)
ashReservedBytes = [0x11, 0x13, 0x18, 0x1A, 0x7D, 0x7E]
ashCmdsList = ["DATA", "ACK", "NAK", "RST", "RSTACK", "ERROR"]

# Reminder: ASH reserved values
# 0x11 XON: Resume transmission. Used in XON/XOFF flow control. Always ignored if received by the NCP.
# 0x13 XOFF: Stop transmission. Used in XON/XOFF flow control. Always ignored if received by the NCP.
# 0x18 Substitute Byte: Replaces a byte received with a low-level communication error (e.g., framing error) from the UART. When a Substitute Byte is processed, the data between the previous and the next Flag Bytes is ignored.
# 0x1A Cancel Byte: Terminates a frame in progress. A Cancel Byte causes all data received since the previous Flag Byte to be ignored. Note that as a special case, RST and RSTACK frames are preceded by Cancel Bytes to ignore any link startup noise.
# 0x7D Escape Byte: Indicates that the following byte is escaped. If the byte after the Escape Byte is not a reserved byte, bit 5 ofthe byte is complemented to restore its original value. If the byte after the Escape Byte is a reserved value, the EscapeByte has no effect.
# 0x7E Flag Byte: Marks the end of a frame. When a Flag Byte is received, the data received since the last Flag Byte or CancelByte is tested to see whether it is a valid frame.
XONBYTE = 0x11
XOFFBYTE = 0x13
CANCELBYTE = 0x1A
FLAGBYTE = 0x7E
ESCAPEBYTE = 0x7D

# Pseudo random sequence generation
def ashGenRandom() -> bytes:

	# • rand0 = 0x42
	# • if bit 0 of randi is 0, randi+1 = randi >> 1
	# • if bit 0 of randi is 1, randi+1 = (randi >> 1) ^ 0xB8

    seq = bytearray()
    rand = 0x42

    for i in range(256):
        seq.append(rand)
        if (rand & 0x1) == 0:
            rand = rand >> 1
        else:
            rand = (rand >> 1) ^ 0xB8

    return seq

ashPseudoRandomSeq = ashGenRandom()

# Randomize given data field
def ashRandomize(data: bytes) -> bytes:
	out = bytes()
	for i in range(len(data)):
		# TODO: What if data len > 256 ?
		b = data[i] ^ ashPseudoRandomSeq[i]
		out += b.to_bytes(1, "big")
	return out

# Compute CRC on given data 'bytes' string
# Return: CRC as int
def ashCrc(data):
    crc = 0xFFFF
    POLYNOMIAL = 0x1021
    for c in data:
        crc = crc ^ (c<<8)
        for j in range(8):
            crc= (crc << 1) ^ POLYNOMIAL if crc & 0x8000 else crc << 1
    crc = crc & 0xFFFF
    return crc

# Encapsulate EZSP data into ASH protocol
# Returns: ashFrame (bytes) or b'' if ERROR
def ashFormat(ashCmdName, data = b''):
	if (ashCmdName == "DATA"):
		frmNum = ashFrmNum # Last transmitted frame + 1
		reTx = 0 # set to 1 in a retransmitted DATA frame
		ackNum = ashFrmNum
		ctrlByte = (frmNum << 4) | (reTx << 3) | ackNum
	elif (ashCmdName == "ACK"):
		nRdy = 0 # ?
		ackNum = data[0] # Expecting 'ackNum' as data[0]
		data = b''
		ctrlByte = 0x80 | (nRdy << 3) | ackNum
	elif (ashCmdName == "RST"):
		ctrlByte = 0xC0
	elif (ashCmdName == "RSTACK"):
		ctrlByte = 0xC1
	else:
		print("    ERROR: ashFormat(): Unknown cmd '%s'" % ashCmdName)
		return b''

	ashFrame = bytes()
	ashFrame += ctrlByte.to_bytes(1, "big")
	if (len(data) > 0):
		# TODO: Randomization
		ashFrame += ashRandomize(data)
	crc = ashCrc(ashFrame)
	ashFrame += crc.to_bytes(2, "big")

	# Performing byte stuffing
	ashFrame2 = bytes()
	for c in ashFrame:
		if c in ashReservedBytes:
			int_val = ESCAPEBYTE # Escape byte
			ashFrame2 += int_val.to_bytes(1, "big")
			int_val = c ^ (1 << 5)
			ashFrame2 += int_val.to_bytes(1, "big")
		else:
			ashFrame2 += c.to_bytes(1, "big")
	flagByte = FLAGBYTE
	ashFrame2 += flagByte.to_bytes(1, "big")

	if (ashCmdName == "DATA"):
		print("    ASH-frame-Frm%u-Ack%u=%s" % (frmNum, ackNum, ashFrame2.hex()))
	elif (ashCmdName == "ACK") or  (ashCmdName == "NAK"):
		print("    ASH-frame-Ack%u=%s" % (ackNum, ashFrame2.hex()))
	else:
		print("    ASH-frame=%s" % (ashFrame2.hex()))
	return ashFrame2

# Send ASH cmd
# Returns: True=ok, False=error
def ashSend(serPort, cmdName, data = b''):
	print("  ashSend(%s, data='%s')" % (cmdName, data.hex()))

	if (cmdName in ashCmdsList):
		ashFrame = ashFormat(cmdName, data)
	else:
		print("    ERROR: ashSend(): Unknown cmd '%s'" % cmdName)
		return False

	if (len(ashFrame) == 0):
		return False

	dataBytes = bytes(ashFrame)
	serPort.write(dataBytes)

	# If send successful
	if (cmdName == "DATA"):
		global ashFrmNum
		ashFrmNum += 1
		if (ashFrmNum > 7):
			ashFrmNum = 0
	return True

# Read then decode ASH frame
def ashRead(serPort):
	print("  ashRead()")

	msg = bytes(0)
	while True:
		b = serPort.read(1)
		msg += b
		if (b[0] == FLAGBYTE):
			break
	status, cmd = ashDecode(msg)
	if (cmd["name"] == "DATA"):
		ackNum = cmd['frmNum'] + 1
		ashSend(serPort, "ACK", ackNum.to_bytes(1, "big"))

	return status, cmd

# Decode ASH message
# Returns: status (True/False) + cmd (dict)
def ashDecode(msg):
	print("  ashDecode(%s)" % msg.hex())

	# Removing flag byte first
	if (msg[-1] != FLAGBYTE):
		print("    ERROR: ashDecode(): Missing 'flag byte'")
		return False

	msg2 = msg[0:-1] # Removing flag byte
	# print("  msg2=%s" % msg2.hex())

	# Performing byte unstuffing
	msg3 = bytes()
	escapedByte = False
	for b in msg2:
		if (escapedByte):
			b = b ^ (1 << 5)
			msg3 += b.to_bytes(1, "big")
			escapedByte = False
		elif (b == ESCAPEBYTE):
			escapedByte = True
		elif (b == XONBYTE):
			print("    XON byte")
		elif (b == CANCELBYTE):
			print("    CANCEL byte (0x%X)" % CANCELBYTE)
			msg3 = bytes() # Clear
		elif (b in ashReservedBytes):
			print("    ERROR: ashDecode(): Missing 0x%x case support" % b)
			return False
		else:
			msg3 += b.to_bytes(1, "big")
	# print("  msg3=%s" % msg3.hex())

	# Checking CRC
	msg4 = msg3[0:-2] # Removing CRC
	crcGot = int.from_bytes(msg3[-2:], 'big')
	crcExp = ashCrc(msg4)
	# print("CRC: Got=0x%x Exp=0x%x" % (crcGot, crcExp))
	if (crcGot != crcExp):
		print("    ERROR: ashDecode(): Invalid CRC")
		return False

	# print("  msg4=%s" % msg4.hex())
	ctrlByte = msg4[0]

	status = True
	if (ctrlByte >> 7) == 0x0: # DATA
		frmNum = (ctrlByte >> 4) & 0x7
		reTx = (ctrlByte >> 3) & 0x1
		ackNum = (ctrlByte >> 0) & 0x7
		data = ashRandomize(msg4[1:]) # Reverse randomization
		cmd = {"name":"DATA", "frmNum": frmNum, "ackNum":ackNum, "reTx":reTx, "data": data}
	elif (ctrlByte >> 5) == 0x4: # ACK
		ackNum = (ctrlByte >> 0) & 0x7
		cmd = {"name":"ACK", "ackNum":ackNum}
	elif (ctrlByte >> 5) == 0x5: # NAK
		ackNum = (ctrlByte >> 0) & 0x7
		nRdy = (ctrlByte >> 3) & 0x1
		cmd = {"name":"NAK", "nRdy":nRdy, "ackNum":ackNum}
	elif (ctrlByte == 0xC1):
		status, cmd = ashDecodeRSTACK(msg4[1:])
	elif (ctrlByte == 0xC2):
		status, cmd = ashDecodeERROR(msg4[1:])
	else:
		status = False
		print("    ERROR: ashDecode(): Invalid CTRL byte (0x%X)" % ctrlByte)
		cmd = {"name":"?"}
	print("    ASH-cmd=", cmd)
	return status, cmd

def ashDecodeRSTACK(data):
	# data[0]: Supposed to be 0x2
	# data[1]: Reset code
	# 	0x00 Reset: Unknown reason
	# 	0x01 Reset: External
	# 	0x02 Reset: Power-on
	# 	0x03 Reset: Watchdog
	# 	0x06 Reset: Assert
	# 	0x09 Reset: Boot loader
	# 	0x0B Reset: Software
	# 	0x51 Error: Exceeded maximum ACK timeout count
	# 	0x80 Chip-specific error reset code
	version = data[0]
	resetCode = data[1]
	cmd = {"name":"RSTACK", "version":version, "resetCode":resetCode}
	# print("RSTACK: ResetCode=0x%02X" % resetCode)

	global ashFrmNum, ashAckNum
	ashFrmNum = 0
	ashAckNum = 0
	return True, cmd

def ashDecodeERROR(data):
	# data[0]: Supposed to be 0x2
	# data[1]: Error code
	# 	0x00 Reset: Unknown reason
	# 	0x01 Reset: External
	# 	0x02 Reset: Power-on
	# 	0x03 Reset: Watchdog
	# 	0x06 Reset: Assert
	# 	0x09 Reset: Boot loader
	# 	0x0B Reset: Software
	# 	0x51 Error: Exceeded maximum ACK timeout count
	# 	0x80 Chip-specific error reset code
	cmd = {"name":"ERROR", "errCode":data[1]}
	# print("ERROR: ErrCode=0x%02X" % data[1])
	return True, cmd
