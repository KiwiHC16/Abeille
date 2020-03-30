echo "Execution de '$(basename $0)'"

echo "Test préliminaire"
if [ ! -e /boot/config.txt ]; then
    echo "= ERREUR: Ce script ne semble pas compatible avec votre plateforme."
    exit 1
fi
echo "= Ok"

echo "Mise a jour du systeme Raspbian"
sudo apt-get -y update
sudo apt-get -y upgrade

DATE_WITH_TIME=`date +"%Y%m%d-%H%M%S"`

echo "Sauvegarde du fichier /boot/config.txt"
sudo cp /boot/config.txt /boot/config.txt.$DATE_WITH_TIME

echo "Modification du fichier /boot/config.txt"
sudo su - << END
sed "/^enable_uart=1/d" /boot/config.txt > /boot/config.txt.new
echo "" >> /boot/config.txt.new
echo "enable_uart=1" >> /boot/config.txt.new
mv /boot/config.txt.new /boot/config.txt
END

# TODO: To be enhanced. Should focus on used TTY only
echo "Arret des processus console sur port serie"
echo "RPI2"
sudo systemctl stop serial-getty@ttyAMA0.service
sudo systemctl disable serial-getty@ttyAMA0.service

echo "RPI3"
sudo systemctl stop serial-getty@ttyS0.service
sudo systemctl disable serial-getty@ttyS0.service

echo "Sauvegarde du fichier /boot/cmdline.txt"
sudo cp /boot/cmdline.txt /boot/cmdline.txt.$DATE_WITH_TIME

echo "Retrait de la console au boot"
sudo su - << END2
sed -e "s/console=serial[a-zA-Z]*[0-9]*,115200 //" /boot/cmdline.txt > /boot/cmdline.txt.new
mv /boot/cmdline.txt.new /boot/cmdline.txt
END2

echo "= Ok"
echo "= Redémarrez votre RPI pour que les modifications soient prises en compte."
exit 0
