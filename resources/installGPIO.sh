echo "Lancement de l installation de GPIO"

sudo apt-get update
sudo apt-get upgrade
sudo apt-get install git-core
sudo apt-get install make
sudo apt-get install gcc

git clone git://git.drogon.net/wiringPi
cd wiringPi
git pull origin
./build
gpio -v


echo "Fin"
