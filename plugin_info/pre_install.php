<?php

include_once __DIR__.'/../../../core/php/core.inc.php';

/**
 * Function pour executer une commande shell et mettre le resultat dans le log
 * @param    commande shell a executer
 * @return  none
 */
function Abeille_pre_update_log($cmd) {
    exec( $cmd, $output);
    log::add('Abeille', 'debug', $cmd.' -> '.json_encode($output));
}

/**
 * Function call before doing the update of the plugin from the Market
 * Fonction exécutée automatiquement avant la mise à jour du plugin
 * https://github.com/jeedom/plugin-template/blob/master/plugin_info/pre_install.php
 * 
 * @param       none
 * @return      nothing
 */
function Abeille_pre_update() {

    log::add('Abeille', 'debug', 'Launch of Abeille_pre_update()');

    log::add('Abeille', 'debug', 'Abeille tmp dir : '.jeedom::getTmpFolder("Abeille"));
    Abeille_pre_update_log('df -h');
    Abeille_pre_update_log("df  --output=avail -BM ".jeedom::getTmpFolder());
    Abeille_pre_update_log("du -sh /var/www/html/plugins/Abeille");

    log::add('Abeille', 'debug', 'End of Abeille_pre_update()');

}

// Abeille_pre_update();
?>
