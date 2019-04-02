
echo "Lancement de l installation de Mosiquitto-MQTT from https://github.com/mgdm/Mosquitto-PHP Sur Debian 9 x86 en étant root";
echo "vi /etc/apt/sources.list pour enlever les sources indesirables";
cd
apt-get -y update
apt-get -y upgrade

apt-get -y install php
apt-get -y install mosquitto
apt-get -y install libmosquitto-dev
apt-get -y install git
apt-get -y install php7.0-dev

mkdir Mosquitto-PHP
cd Mosquitto-PHP
git clone https://github.com/mgdm/Mosquitto-PHP.git
cd Mosquitto-PHP
phpize
./configure
make
make install

echo "ajouter extension=mosquitto.so à la fin du fichier php.ini par exemple /etc/php/7.0/apache2/php.ini"

/etc/init.d/mosquitto restart
/etc/init.d/apapche2 restart

echo "Fin"
