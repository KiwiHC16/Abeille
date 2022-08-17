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

    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once(dirname(__FILE__).'/../../core/class/AbeilleTools.class.php');
    include_once(dirname(__FILE__).'/../../desktop/php/200_AbeilleScript.php');
    include_once __DIR__.'/../../core/config/Abeille.config.php';
    /*
    if (!isConnect('admin')) {
        throw new Exception('401 Unauthorized');
    }
     */
    $eqLogics = Abeille::byType('Abeille');

    function displayDaemonStatus($diff, $name, &$oneMissing) {
        $nameLow = strtolower(substr($name, 0, 1)).substr($name, 1); // First char lower case
        if ($diff[$nameLow] == 1)
            echo '<span class="label label-success" style="font-size:1em; margin-left:4px">'.$name.'</span>';
        else {
            echo '<span class="label label-danger" style="font-size:1em; margin-left:4px">'.$name.'</span>';
            $oneMissing = TRUE;
        }
    }

    $parameters = AbeilleTools::getParameters();
// logToFile("parameters=".json_encode($parameters));
    $running = AbeilleTools::getRunningDaemons();
    $diff = AbeilleTools::diffExpectedRunningDaemons($parameters, $running);
// logToFile("diff=".json_encode($diff));
    $oneMissing = FALSE;
    displayDaemonStatus($diff, "Cmd", $oneMissing);
    displayDaemonStatus($diff, "Parser", $oneMissing);
    for ($zgNb = 1; $zgNb <= maxNbOfZigate; $zgNb++) {
        if ($parameters['ab::zgEnabled'.$zgNb] != "Y")
            continue; // Zigate disabled
        displayDaemonStatus($diff, "SerialRead".$zgNb, $oneMissing);
        if ($parameters['ab::zgType'.$zgNb] == "WIFI")
            displayDaemonStatus($diff, "Socat".$zgNb, $oneMissing);
    }

    echo "<hr>";

    for ($i = 1; $i <= maxNbOfZigate; $i++) {
        if (config::byKey('ab::zgEnabled'.$i, 'Abeille', 'N', 1) == "Y") {
?>
        <div class="ui-block-a">

			    <a id="bt_include<?php echo $i;?>" href="#" class="ui-btn ui-btn-raised clr-primary waves-effect waves-button changeIncludeState" data-mode="1" data-state="1" style="margin: 5px;">
				    <i class="fa fa-link" style="font-size: 6em;"></i>{{Inclusion Z<?php echo $i;?>}}
			    </a>

			    <a id="bt_include_stop<?php echo $i;?>" href="#" class="ui-btn ui-btn-raised clr-primary waves-effect waves-button changeIncludeState" data-mode="1" data-state="1" style="margin: 5px;">
				    <i class="fa fa-unlink" style="font-size: 6em;"></i>{{Stop Z<?php echo $i;?>}}
			    </a>

        </div>

<?php
        }
    }

?>


<hr>

<table border="1">
    <thead>
    <tr>
        <th>{{Module}}</th>
        <th>{{Statut}}</th>
        <th>{{Depuis (h)}}</th>
    </tr>
    </thead>
    <tbody>
    <?php

        foreach ($eqLogics as $eqLogic) {
            $alert = 0;

            // Module
            echo "\n\n\n\n<tr>".'<td><a href="'.$eqLogic->getLinkToConfiguration().'" style="text-decoration: none;">'.$eqLogic->getHumanName(true).'</a></td>';

            // Status
            // Status Ok par defaut, apres on test et on met le status à la valeur voulue
            $status = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{OK}}</span>';
            if ( (time() - strtotime($eqLogic->getStatus('lastCommunication'))) > (60*$eqLogic->getTimeout()) ) {
                 $status = '<span class="label label-warning" style="font-size : 1em; cursor : default;">{{Time Out Last Communication}}</span>';
                $alert = 1;
            }
            if ( (time() - strtotime($eqLogic->getStatus('lastCommunication'))) > ((2*60*$eqLogic->getTimeout())) ) {
                $status = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{Time Out Last Communication}}</span>';
                $alert = 2;
            }

            if ($eqLogic->getStatus('state') == '-') {
                $status = '<span class="label label-success" style="font-size : 1em; cursor : default;">-</span>';
                $alert = 0;
            }

            if ($alert==0) { echo '<td bgcolor="green">'    .$status.'</td>'; }
            if ($alert==1) { echo '<td bgcolor="orange">'   .$status.'</td>'; }
            if ($alert==2) { echo '<td bgcolor="red">'      .$status.'</td>'; }

            // Depuis
            $Depuis = '<span class="label label-info" style="font-size : 1em; cursor : default;">'.(floor((time() - strtotime($eqLogic->getStatus('lastCommunication'))) / 3600)).'</span>';
            //if ($eqLogic->getStatus('state') == '-') { $Depuis = '<span class="label label-info" style="font-size : 1em; cursor : default;">-</span>'; }
            echo '<td>'.$Depuis.'</td>';

        }
    ?>
    </tbody>
</table>

<?php
foreach ($IEEE_Table as $IEEE=>$IEEE_Device) {
    if ($IEEE_Device>1) { echo "{{L'adresse $IEEE est dupliqué ce n'est pas normal. On ne doit avoir qu'un équipment par adresse IEEE}}</br>"; }
}
    ?>
