<?php

/* This file is part of Plugin abeille for jeedom.
 *
 * Plugin abeille for jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Plugin abeille for jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin abeille for jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {

	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__) . '/../class/Abeille.class.php';

	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception('401 Unauthorized');
	}

	ajax::init();
    
	if (init('action') == 'syncconfAbeille') {
		abeille::syncconfAbeille(false);
		ajax::success();
	}
    
    if (init('action') == 'updateConfigAbeille') {
        abeille::updateConfigAbeille(false);
        ajax::success();
    }

    if (init('action') == 'installGPIO') {
        abeille::installGPIO(false);
        ajax::success();
    }
    
    if (init('action') == 'installS0') {
        abeille::installS0(false);
        ajax::success();
    }

    if (init('action') == 'installSocat') {
        abeille::installSocat(false);
        ajax::success();
    }

    if (init('action') == 'updateFirmwarePiZiGate') {
        abeille::updateFirmwarePiZiGate(false,init('fwfile'));
        ajax::success();
    }
    
    if (init('action') == 'resetPiZiGate') {
        abeille::resetPiZiGate(false);
        ajax::success();
    }
    
	throw new Exception('Aucune methode correspondante');
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}
?>

