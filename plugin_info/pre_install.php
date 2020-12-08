<?php

include_once __DIR__.'/../../../core/php/core.inc.php';

/**
 * Function call before doing the update of the plugin from the Market
 * @param       none
 * @return      nothing
 */
function Abeille_pre_update() {

    log::add('Abeille', 'debug', 'Launch of Abeille_pre_update()');

    log::add('Abeille', 'debug', 'End of Abeille_pre_update()');

}

?>
