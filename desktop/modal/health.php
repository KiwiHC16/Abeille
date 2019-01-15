<?php
    /* This file is part of Jeedom.
     *
     * Jeedom is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * Jeedom is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
     */

    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
    $eqLogics = Abeille::byType('Abeille');
?>

<table class="table table-condensed tablesorter" id="table_healthAbeille">
    <thead>
    <tr>
        <th>{{Module}}</th>
        <th>{{Name}}</th>
        <th>{{ID}}</th>
        <th>{{Address}}</th>
        <th>{{IEEE}}</th>
        <th>{{Statut}}</th>
        <th>{{Dernière communication}}</th>
        <th>{{Depuis (h)}}</th>
        <th>{{Date création}}</th>
    </tr>
    </thead>
    <tbody>
    <?php
        foreach ($eqLogics as $eqLogic) {

            // Module
            echo '<tr><td><a href="'.$eqLogic->getLinkToConfiguration().'" style="text-decoration: none;">'.$eqLogic->getHumanName(true).'</a></td>';

            // Nom
            echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">'.$eqLogic->getConfiguration('icone').'</span></td>';
            
            // ID
            echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">'.$eqLogic->getId().'</span></td>';

            // Short Address
            echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">'.substr($eqLogic->getLogicalId(),8).'</span></td>';
            
            // IEEE
            $abeille = new Abeille();
            $commandIEEE = new AbeilleCmd();
            
            // Recupere IEEE de la Ruche/ZiGate
            $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();
            // log::add('Abeille', 'debug', 'Id pour abeille Ruche: ' . $rucheId);
            
            $addrIEEE = $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'IEEE-Addr')->execCmd();
            // log::add('Abeille', 'debug', 'IEEE pour abeille: ' . $addrIEEE);
            
            echo '<td>'.$addrIEEE.'</td>';

            // Status
            // Status Ok par defaut, apres on test et on met le status à la valeur voulue
            $status = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{OK}}</span>';
            if ( (time() - strtotime($eqLogic->getStatus('lastCommunication'))) > (60*$eqLogic->getTimeout()) ) {
                 $status = '<span class="label label-warning" style="font-size : 1em; cursor : default;">Time Out Last Communication</span>';
            }
            if ( (time() - strtotime($eqLogic->getStatus('lastCommunication'))) > ((2*60*$eqLogic->getTimeout())) ) {
                $status = '<span class="label label-danger" style="font-size : 1em; cursor : default;">Time Out Last Communication</span>';
            }

            if ($eqLogic->getStatus('state') == '-') {
                $status = '<span class="label label-success" style="font-size : 1em; cursor : default;">-</span>';
            }
            echo '<td>'.$status.'</td>';

            // Derniere Comm
            $lastComm = '<span class="label label-info" style="font-size : 1em; cursor : default;">'.$eqLogic->getStatus('lastCommunication').'</span>';
            //if ($eqLogic->getStatus('state') == '-') { $lastComm = '<span class="label label-info" style="font-size : 1em; cursor : default;">-</span>'; }
            echo '<td>'.$lastComm.'</td>';

            // Depuis
            $Depuis = '<span class="label label-info" style="font-size : 1em; cursor : default;">'.(floor((time() - strtotime($eqLogic->getStatus('lastCommunication'))) / 3600)).'</span>';
            //if ($eqLogic->getStatus('state') == '-') { $Depuis = '<span class="label label-info" style="font-size : 1em; cursor : default;">-</span>'; }
            echo '<td>'.$Depuis.'</td>';


            // Date Creation
            echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">'.$eqLogic->getConfiguration('createtime').'</span></td></tr>';
        }
    ?>
    </tbody>
</table>
