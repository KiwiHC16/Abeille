for f in *.adoc; do echo "Processing $f file.."; asciidoc -n -a icons -a badge $f; done
mv *.html ../fr_FR_html/

