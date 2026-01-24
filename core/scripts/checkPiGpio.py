# coding: utf-8

import subprocess
import time

print("Vérification de l'installation du package 'pigpio'")

try:
    import pigpio
except ImportError as e:
   print("= ERREUR: Le package PiGpio-python3 semble mal installé.")
   exit(1)

pi = pigpio.pi()       # specify host, default port
if not pi.connected:
   print("= pigpiod non démarré, tentative de démarrage...")
   try:
      subprocess.run(['sudo', 'pigpiod'], check=True)
      time.sleep(1)  # Attendre que le démon démarre
      pi = pigpio.pi()  # Réessayer la connexion
      if not pi.connected:
         print("= ERREUR: Impossible de se connecter à pigpiod après démarrage.")
         exit(1)
      print("= pigpiod démarré avec succès")
   except subprocess.CalledProcessError:
      print("= ERREUR: Impossible de démarrer pigpiod.")
      print("=         Vérifiez que pigpio est installé: sudo apt-get install pigpio")
      exit(1)

print("= Ok")
exit(0)