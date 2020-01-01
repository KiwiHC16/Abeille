echo "Lancement de la programmation du firmware"
echo "Fichier : " $1

gpio mode 0 out

gpio mode 2 out

gpio write 2 0 
sleep 1

gpio write 0 0 
sleep 1

gpio write 0 1
sleep 1

gpio write 2 1
sleep 1

sudo /var/www/html/plugins/Abeille/Zigate_Module/JennicModuleProgrammerRPI3 -V 6 -P 115200 -v -f  /var/www/html/plugins/Abeille/Zigate_Module/$1  -s /dev/ttyS0

echo "Programmation faite. Redemarrage de la PiZiGate"

gpio mode 0 out

gpio mode 2 out

gpio write 2 1 
sleep 1

gpio write 0 0
sleep 1

gpio write 0 1

echo "Fin"
