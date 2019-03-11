echo "Lancement de la programmation du firmware"


gpio mode 0 out

gpio mode 2 out

gpio write 2 0 
sleep 1

gpio write 0 1 
sleep 1

gpio write 0 0 
sleep 1

gpio write 0 1

sleep 1

gpio write 2 1

sleep 1

sudo ./JennicModuleProgrammerRPI3 -V 6 -P 115200 -v -f  ZiGate_v30f.bin  -s /dev/ttyS0

echo "Programmation faite. Redemarrage de la PiZiGate"

gpio mode 0 out

gpio mode 2 out

gpio write 2 1 
sleep 1

gpio write 0 1 
sleep 1

gpio write 0 0

sleep 1

gpio write 0 1

echo "Fin"
