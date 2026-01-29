import pigpio
import time
import sys

# Parse command line arguments for --host
host = '127.0.0.1'
for arg in sys.argv[1:]:
    if arg.startswith('--host='):
        host = arg.split('=')[1]

print(f"Connexion a pigpiod sur {host}")

pi = pigpio.pi(host)

if not pi.connected:
   print(f"= ERREUR: Impossible de se connecter a pigpiod sur {host}")
   print("=         Verifiez que pigpiod est demarre sur le host.")
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

pi.set_mode(portReset, pigpio.OUTPUT)
pi.set_mode(portFlash, pigpio.OUTPUT)

pi.write(portReset, 1)
pi.write(portFlash, 1)
time.sleep(1)
pi.write(portReset, 0)
time.sleep(1)
pi.write(portReset, 1)

pi.stop()

print("= Ok. Vous pouvez fermer cette fenetre de log.")
exit(0)
