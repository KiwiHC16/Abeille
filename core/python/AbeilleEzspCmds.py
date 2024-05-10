# Abeille plugin for Jeedom
# EmberZnet/EZSP commands
# Tcharp38

def sendCmd(serPort, cmd):
	print("send(%s)" % cmd)

	if (cmd == "RST"):
		reset = [0xC0, 0x38, 0xBC, 0x7E]
		dataBytes = bytes(reset)

	elif (cmd == "version"):
		version = [0x25, 0x00, 0x00, 0x00, 0x02, 0x1A, 0xAD, 0x7E]
		dataBytes = bytes(version)

	else:
		print("ERROR")

	serPort.write(dataBytes)
