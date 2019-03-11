
echo "Redemarrage de la PiZiGate"

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
