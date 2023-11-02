<?php

    include_once __DIR__.'/../../../core/php/core.inc.php';
    include_once __DIR__."/../core/php/AbeilleInstall.php";

    /**
     * Function call by jeedom core before doing the update of the plugin from the Market
     * Fonction exécutée automatiquement avant la mise à jour du plugin
     * https://github.com/jeedom/plugin-template/blob/master/plugin_info/pre_install.php
     *
     * @param       none
     * @return      nothing
     */
    function Abeille_pre_update() {

        log::add('Abeille', 'debug', 'Abeille_pre_update() starting');

        Abeille_pre_update_analysis(1, 1);

        log::add('Abeille', 'debug', 'Abeille_pre_update() ended');
    }

    // Cli test
    // Abeille_pre_update();
?>
