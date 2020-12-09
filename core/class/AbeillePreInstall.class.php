<?php

class AbeillePreInstall {

    /**
     * Function pour executer une commande shell et mettre le resultat dans le log
     * @param    commande shell a executer
     * @return  none
     */
    function Abeille_pre_update_log($cmd, $comment, $log, $echo) {
        exec( $cmd, $output);
        if ($log) log::add('Abeille', 'debug', $cmd.' -> '.json_encode($output));
        if ($echo) {
            echo $comment.' -> <br>';
            foreach ($output as $line) {
                echo "....".$line."<br>\n";
            }
            echo "<br>";
        }
    }

    /**
     * Function call before doing the update of the plugin from the Market
     * Fonction exécutée automatiquement avant la mise à jour du plugin
     * https://github.com/jeedom/plugin-template/blob/master/plugin_info/pre_install.php
     * 
     * @param       none
     * @return      nothing
     */
    function Abeille_pre_update_analysis($log, $echo) {

        log::add('Abeille', 'debug', 'Launch of Abeille_pre_update_analysis()');

        $tmpDir = jeedom::getTmpFolder('Abeille');
        if ($echo) echo '{{Repertoire Temporaire}}: '.$tmpDir.'<br><br>';
        if ($log) log::add('Abeille', 'debug', '{{Repertoire Temporaire}}: '.$tmpDir );

        self::Abeille_pre_update_log('df -h', '{{Espaces disques}}:', $log, $echo);
        self::Abeille_pre_update_log("df  --output=avail -BM ".jeedom::getTmpFolder(), '{{Espace disponible}}:', $log, $echo);
        self::Abeille_pre_update_log("du -sh /var/www/html/plugins/Abeille", '{{Taille Abeille}}:', $log, $echo);

        exec ( 'df  --output=avail -BM '.$tmpDir, $output );
        $tmpSpaceAvailable = str_replace( 'M', '', $output[1] );
        unset( $output );

        exec ( 'du -sh /var/www/html/plugins/Abeille', $output );
        list($AbeilleSize, $folder) = explode('M',$output[0]);
        unset( $output ); 

        if ( $tmpSpaceAvailable < $AbeilleSize*3 ) {
            if ($echo) echo '<span style="color:orange">{{L espace disponible est trop faible pour faire une mise a jour.}}<br>';
            if ($log) log::add('Abeille', 'debug', 'L espace disponible est trop faible pour faire une mise a jour.');
        }
        else {
            if ($echo) echo '<span style="color:green">{{Pas de soucis détecté.}}<br>';
            if ($log) log::add('Abeille', 'debug', 'Pas de soucis détecté.' );
        }

        log::add('Abeille', 'debug', 'End of Abeille_pre_update()');

    }

}

// Abeille_pre_update();
?>
