echo "Lancement de l installation de GPIO"

sudo apt-get -y update
sudo apt-get -y upgrade
sudo apt-get -y install git-core
sudo apt-get -y install make
sudo apt-get -y install gcc

cd /var/www/html/plugins/Abeille/
mkdir tmp
cd tmp
git clone git://git.drogon.net/wiringPi
cd wiringPi
./build
cd /var/www/html/plugins/Abeille/
rm -rf tmp

gpio -v
gpio readall

DATE_WITH_TIME=`date +"%Y%m%d-%H%M%S"`

sudo cp /boot/config.txt /boot/config.txt.$DATE_WITH_TIME

sudo su - << END
sed "/^enable_uart=1/d" /boot/config.txt > /boot/config.txt.new
echo "" >> /boot/config.txt.new
echo "enable_uart=1" >> /boot/config.txt.new
mv /boot/config.txt.new /boot/config.txt
END

echo "RPI2"
sudo systemctl stop serial-getty@ttyAMA0.service
sudo systemctl disable serial-getty@ttyAMA0.service

echo "RPI3"
sudo systemctl stop serial-getty@ttyS0.service
sudo systemctl disable serial-getty@ttyS0.service


sudo cp /boot/cmdline.txt /boot/cmdline.txt.$DATE_WITH_TIME

sudo su - << END2
sed -e "s/console=serial[a-zA-Z]*[0-9]*,115200 //" /boot/cmdline.txt > /boot/cmdline.txt.new
mv /boot/cmdline.txt.new /boot/cmdline.txt
END2

echo "Vous devez redemarrer votre RPI"
sudo reboot

echo "Fin"
