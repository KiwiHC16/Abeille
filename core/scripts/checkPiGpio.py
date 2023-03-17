import pigpio

print("Verification de l installation du package 'pigpio'")

pi = pigpio.pi()       # specify host, default port

if not pi.connected:
   print("= ERREUR: Commande 'gpio' manquante ou non executable !")
   print("=         Le package WiringPi est probablement mal installe.")
   exit(1)

print("= Ok")
exit(0)