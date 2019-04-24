echo "========================"
echo "Processing doc files ..."
echo "========================"
rm ../../docs_locale/fr_FR/*.html
rm ../fr_FR/*.html
for f in *.adoc; do echo "Processing $f file.."; asciidoc -n -a icons -a badge $f; done
cp *.html ../../docs_locale/fr_FR/
mv *.html ../fr_FR/

echo "======================================"
echo "Processing listeCompatibilite file ..."
echo "======================================"
echo "Generating listeCompatibilite.adoc file..."
cd ../../core/config/devices
php listeCompatibilite.php 1 > listeCompatibilite.adoc
asciidoc -n -a icons -a badge listeCompatibilite.adoc
rm listeCompatibilite.adoc
cp listeCompatibilite.html ../../../docs_locale/fr_FR
mv listeCompatibilite.html ../../../docs/fr_FR

echo "========"
echo "All done"
echo "========"
