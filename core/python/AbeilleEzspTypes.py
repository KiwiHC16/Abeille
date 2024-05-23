# Abeille plugin for Jeedom
# EmberZnet/EZSP types
# Tcharp38

ezspComplexTypes = {
	"EmberNetworkParameters": {
		"extendedPanId": "uint8_t[8]",
		"panId": "uint16_t",
		"radioTxPower": "uint8_t",
		"radioChannel": "uint8_t",
		"joinMethod": "EmberJoinMethod",
		"nwkManagerId": "EmberNodeId",
		"nwkUpdateId": "uint8_t",
		"channels": "uint32_t"
	}
}

# 'pType' = Complex or basic type name (ex: uint8_t, EmberStatus, ...)
# 'pVal' = Parameter value
def ezspTypeToBytes(pType, pVal):
	print("    ezspTypeToBytes(): pType=", pType, ", pVal=", pVal)
	pBytes = bytes()
	if (pType == "uint8_t"):
		pBytes += pVal.to_bytes(1, "big")
	elif (pType == "uint16_t"):
		pBytes += pVal.to_bytes(2, "big")
	elif (pType in ezspComplexTypes):
		p = ezspComplexTypes[pType]
		for pN2 in p:
			pType2 = p[pN2]
			pB = ezspTypeToBytes(pType2, pVal)
			if (pB == b''):
				return b''
	else:
		print("    ERROR: ezspTypeToBytes(): Unsupported type '%s'" % pType)
		return b''
	return pBytes