<?php
    // Ce script va rechercher tous les fichiers json, remplacer un texte par un autre et sauvegarder le resultat
    // dans un fichier dans le meme folder avec l extension .new
    
    // exemple:
    // www-data@Abeille:~/html/plugins/Abeille/core/config/devices$ php addParameterToAllTemplates.php /var/www/html/plugins/Abeille/core/config/devices '"configuration": {' '"configuration": {\n\t"uniqId": "UNIQUEID",'
    
    // A noter:
    // - le \t pour tab et le \n pour retour à la ligne.
    // - le UNIQUEID pour faire appel a la fonction uniqid()
    
    // Si vous voulez effacer tous les resultat:
    // find . -name *.new -exec rm {} \;
    
    // Si vous voulez remplacer les anciens par les nouveaux
    // find . -name "*.json" -exec rename 's/\.json$/.json.old/' '{}' \;
    // find . -name "*.json.new" -exec rename 's/\.json.new$/.json/' '{}' \;
    // find . -name "*.json.old" -exec rm {} \;

    $fileList = array();
    
    // recursive directory scan
    function recursiveScan($dir) {
        global $fileList;
        $tree = glob(rtrim($dir, '/') . '/*');
        if (is_array($tree)) {
            foreach($tree as $file) {
                if (is_dir($file)) {
                    // echo $file . "\n";
                    $fileList[] = $file;
                    recursiveScan($file);
                } elseif (is_file($file)) {
                    // echo $file . "\n";
                    $fileList[] = $file;
                }
            }
        }
    }
    
    function processFiles( $folder, $textOrg, $textNew ) {
        global $fileList;
        
        $extensionFilter = ".json";
        
        // Cleanup
        $textNew = str_replace( '\n', "\n", $textNew);
        $textNew = str_replace( '\t', "\t", $textNew);
        
        recursiveScan( $folder );

        foreach ( $fileList as $file ) {
            if ( substr( $file, -strlen($extensionFilter)) == $extensionFilter ) {
                echo $file."\n";
                $templateJSON = file_get_contents($file);
                
                $textNewToUse = str_replace( 'UNIQUEID', uniqid(), $textNew);
                echo str_replace( $textOrg, $textNew, $templateJSON );
                
                file_put_contents( $file.".new", str_replace( $textOrg, $textNewToUse, $templateJSON ) );
            }
        }
    }
    
    // Arguments
    // folder
    // textOrg
    // textNew : text which will replace textOrg
    if ( !(isset($argv[1]) && isset($argv[2]) && isset($argv[3])) ) {
        echo "Parameters missing\n";
        return;
    }

    processFiles( $argv[1], $argv[2], $argv[3] );
