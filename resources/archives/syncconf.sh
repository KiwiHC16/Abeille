#!/usr/bin/env bash

# This script will download all templates with their images from GitHub master to install it user system.
# Should be used in conjonction with AbeilleTemplate*.class.php
# 20201210: Probably not visible to end user at this stage as dev on going.
# Should not be removed.
# Idea is to allow user to download latest template and test on his system.

echo "Lancement du téléchargement des configurations"

cd /var/www/html/plugins/Abeille/core/config/
rm -rf devices > /dev/null 2>&1

cd /var/www/html/plugins/Abeille
rm -rf images > /dev/null 2>&1

mkdir tmp
cd tmp

echo "Récupération des sources (cette étape peut durer quelques minutes)"
wget https://github.com/KiwiHC16/Abeille/archive/master.zip

# Should try :
# unzip /path/to/archive.zip 'in/archive/folder/*' -d /path/to/unzip/to
# to simply all and reduce size used on disk.

unzip master.zip

cd /var/www/html/plugins/Abeille/tmp/Abeille-master
mv images /var/www/html/plugins/Abeille
chmod -R 755 /var/www/html/plugins/Abeille/images

cd /var/www/html/plugins/Abeille/tmp/Abeille-master/core/config/
mv devices /var/www/html/plugins/Abeille/core/config/
chmod -R 755 /var/www/html/plugins/Abeille/core/config/devices

cd /var/www/html/plugins/Abeille/
rm -rf tmp > /dev/null 2>&1

echo "Récupération faite."

