echo "Lancement de l installation de GPIO"

echo "Mise a jour du systeme Raspbian"
sudo apt-get -y update
sudo apt-get -y upgrade

echo "installation de git"
sudo apt-get -y install git-core

echo "installation de make"
sudo apt-get -y install make

echo "installation de gcc"
sudo apt-get -y install gcc

echo "installation de wiringPi"
sudo apt-get -y install wiringpi

echo "Verification que wiringPi est installé."
gpio -v
gpio readall

echo "Fin"
