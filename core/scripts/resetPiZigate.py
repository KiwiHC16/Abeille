import pigpio
import time

print("Verification de l installation du package 'pigpio'")

pi = pigpio.pi()       # specify host, default port

if not pi.connected:
   print("= ERREUR: Commande 'gpio' manquante ou non executable !")
   print("=         Le package WiringPi est probablement mal installe.")
   exit(1)

print("= Ok")

print("Redemarrage de la PiZiGate")

# https://zigate.fr/documentation/parametrer-la-pizigate/
# Memo connexion PiZiGate
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1

# GPIO 14 TXD
# GPIO 15 RXD


portReset = 17
portFlash = 27

pi.set_mode( portReset, pigpio.OUTPUT) 
pi.set_mode( portFlash, pigpio.OUTPUT) 

pi.write( portReset, 1)
pi.write( portFlash, 1)
time.sleep(1)
pi.write( portReset, 0)
time.sleep(1)
pi.write( portReset, 1)

print("= Ok. Vous pouvez fermer cette fenetre de log.")
exit(0)


