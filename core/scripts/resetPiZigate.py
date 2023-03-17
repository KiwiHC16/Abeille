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

# Memo connexion PiZiGate
# port 0 = RESET
# port 2 = FLASH
# Mode production: FLASH=1, RESET=0 puis 1
# Mode flash: FLASH=0, RESET=0 puis 1

pi.set_mode( 0, pigpio.OUTPUT) # GPIO 17 as output
pi.set_mode( 2, pigpio.OUTPUT) # GPIO 17 as output

pi.write( 2, 1)
pi.write( 0, 0)
time.sleep(1)
pi.write( 0, 1)

print("= Ok. Vous pouvez fermer cette fenetre de log.")
exit(0)


