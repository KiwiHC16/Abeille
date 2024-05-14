# Abeille plugin for Jeedom
# EmberZnet/EZSP commands
# Tcharp38

def sendCmd(serPort, cmdName):
	print("send(%s)" % cmdName)

	if (cmdName == "RST"):
		ezsp = [0xC0, 0x38, 0xBC, 0x7E]

	elif (cmdName == "version"):
		ezsp = [0x25, 0x00, 0x00, 0x00, 0x02, 0x1A, 0xAD, 0x7E]

	else:
		print("sendCmd() ERROR: Unknown cmd %s" % cmdName)
		return

	dataBytes = bytes(ezsp)
	serPort.write(dataBytes)
