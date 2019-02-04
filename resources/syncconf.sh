echo "Lancement du téléchargement des configurations"

cd /var/www/html/plugins/Abeille/core/config/
rm -rf devices > /dev/null 2>&1

cd /var/www/html/plugins/Abeille
rm -rf images > /dev/null 2>&1

mkdir tmp
cd tmp

echo "Récupération des sources (cette étape peut durer quelques minutes)"
wget https://github.com/KiwiHC16/Abeille/archive/master.zip
unzip master.zip

cd /var/www/html/plugins/Abeille/core/config/tmp/Abeille-master
mv images /var/www/html/plugins/Abeille

cd /var/www/html/plugins/Abeille/core/config/tmp/Abeille-master/core/config/
mv devices /var/www/html/plugins/Abeille/core/config/

cd /var/www/html/plugins/Abeille/core/config/
rm -rf tmp > /dev/null 2>&1

echo "Récupération faite."

