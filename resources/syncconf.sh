echo "Lancement du téléchargement des configurations"
cd /var/www/html/plugins/Abeille/core/config/
rm -rf devices > /dev/null 2>&1
mkdir tmp
cd tmp
echo "Récupération des sources (cette étape peut durer quelques minutes)"
wget https://github.com/KiwiHC16/Abeille/archive/master.zip
unzip master.zip
cd Abeille-master/core/config/
mv devices ../../../../
cd ../../../..
rm -rf tmp > /dev/null 2>&1
echo "Récupération faite."

