for f in *.adoc; do echo "Processing $f file.."; asciidoc -n -a icons -a badge $f; done
mv *.html ../fr_FR/

echo "Processing listeCompatibilite file ..."
cd ../../core/config/devices
php listeCompatibilite.php 1 > listeCompatibilite.adoc
asciidoc -n -a icons -a badge listeCompatibilite.adoc
rm listeCompatibilite.adoc
mv listeCompatibilite.html ../../../docs/fr_FR
rm -f ../../../docs_locale/fr_FR/*
cp ../../../docs/fr_FR/* ../../../docs_locale/fr_FR/*
cd -
