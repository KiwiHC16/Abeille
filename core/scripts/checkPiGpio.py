# coding: utf-8

# TODO: pigpio-python seems to depend on a daemon. How to check it is running ?

print("Vérification de l'installation du package 'pigpio'")

try:
    import pigpio
except ImportError as e:
   print("= ERREUR: Le package PiGpio-python3 semble mal installé.")
   exit(1)

pi = pigpio.pi()       # specify host, default port
if not pi.connected:
   print("= ERREUR: Le package PiGpio semble mal installé ou démon pas démarré.")
   print("=         'sudo pigpiod' manquant ?")
   exit(1)

print("= Ok")
exit(0)