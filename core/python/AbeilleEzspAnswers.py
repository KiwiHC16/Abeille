# Abeille plugin for Jeedom
# EmberZnet/EZSP answer decoding
# Tcharp38

# Read then decode ASH frame
def ashRead(serPort):
	msg = bytes(0)
	while True:
		b = serPort.read(1)
		msg += b
		if (b[0] == 0x7e):
			break
	status, cmd = ashDecode(msg)
	return status, cmd

# Reminder: ASH reserved values
# 0x7E Flag Byte: Marks the end of a frame. When a Flag Byte is received, the data received since the last Flag Byte or CancelByte is tested to see whether it is a valid frame.
# 0x7D Escape Byte: Indicates that the following byte is escaped. If the byte after the Escape Byte is not a reserved byte, bit 5 ofthe byte is complemented to restore its original value. If the byte after the Escape Byte is a reserved value, the EscapeByte has no effect.
# 0x11 XON: Resume transmission. Used in XON/XOFF flow control. Always ignored if received by the NCP.
# 0x13 XOFF: Stop transmission. Used in XON/XOFF flow control. Always ignored if received by the NCP.
# 0x18 Substitute Byte: Replaces a byte received with a low-level communication error (e.g., framing error) from the UART. When a Substitute Byte is processed, the data between the previous and the next Flag Bytes is ignored.
# 0x1A Cancel Byte: Terminates a frame in progress. A Cancel Byte causes all data received since the previous Flag Byte to be ignored. Note that as a special case, RST and RSTACK frames are preceded by Cancel Bytes to ignore any link startup noise.

# Decode ASH message
# Returns: status (True/False) + cmd (dict)
def ashDecode(msg):
	print("ashDecode(%s)" % msg.hex())

	# print("frame=", msg.hex())

	fIdx = 0
	data = bytes(0)
	crc = bytes(0)
	crcSize = 2 # Always 2
	escaped = False
	reservedBytes = [0x11, 0x13, 0x18, 0x1A, 0x7D, 0x7E]
	for i in range(len(msg)):
		b = msg[i]
		if escaped:
			print("ESCAPED byte 0x%X" % b)
			b = b ^ (1 << 5)
			# print("ESCAPED byte after 0x%X" % b)
			escaped = False

		if (b == 0x11):
			print("XON")
		elif (b == 0x13):
			print("XOFF")
		elif (b == 0x18):
			print("SUBSTITUTE")
		elif (b == 0x1A):
			print("CANCEL byte")
		elif (b == 0x7D):
			unescaped = msg[i + 1] ^ (1 << 5)
			if not (unescaped in reservedBytes):
				escaped = True # Next byte is escaped
		elif (b == 0x7E):
			print("Flag byte")
		else:
			if (fIdx == 0): # Control byte
				ctrlByte = b
				fIdx += 1

				dataFieldSize = 0
				if (ctrlByte >> 5) == 0x4:
					# print("ctrlByte=0x%02X => ACK" % ctrlByte)
					pass
				elif (ctrlByte >> 5) == 0x5:
					# print("ctrlByte=0x%02X => NAK" % ctrlByte)
					pass
				elif ctrlByte == 0xC1:
					# print("ctrlByte=0x%02X => RSTACK" % ctrlByte)
					dataFieldSize = 2
				elif ctrlByte == 0xC2:
					# print("ctrlByte=0x%02X => ERROR" % ctrlByte)
					dataFieldSize = 2
				else:
					print("ctrlByte=0x%02X => DATA" % ctrlByte)
					dataFieldSize = len(msg) - 4 # Excluding CtrlByte + CRC + FlagByte
				if (dataFieldSize != 0):
					fIdx = 1 # Go to data field
				else:
					fIdx = 2 # Go to CRC
			elif (fIdx == 1): # Data field
				data += b.to_bytes(1, 'big')
				dataFieldSize -= 1
				if dataFieldSize == 0:
					print("Data field=", data)
					fIdx = 2 # Go to CRC
			elif (fIdx == 2): # CRC
				crc += b.to_bytes(1, 'big')
				crcSize -= 1
				if (crcSize == 0):
					print("CRC=%s" % crc.hex())

	status = True
	if (ctrlByte >> 7) == 0x0: # DATA
		frmNum = (ctrlByte >> 4) & 0x7
		reTx = (ctrlByte >> 3) & 0x1
		ackNum = (ctrlByte >> 0) & 0x7
		cmd = {"name":"DATA", "frmNum": frmNum, "ackNum":ackNum, "reTx":reTx}
	elif (ctrlByte >> 5) == 0x4:
		cmd = {"name":"ACK", "ackNum":ctrlByte & 0x7}
	elif (ctrlByte >> 5) == 0x5:
		cmd = {"name":"NAK", "ackNum":ctrlByte & 0x7}
	elif (ctrlByte == 0xC1):
		status, cmd = ashDecodeRSTACK(data)
	elif (ctrlByte == 0xC2):
		status, cmd = ashDecodeERROR(data)
	else:
		cmd = {"name":"?"}
	print("cmd=", cmd)
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
