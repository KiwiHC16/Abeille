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

    $parameters = AbeilleTools::getConfig();
// logToFile("parameters=".json_encode($parameters));
    $running = AbeilleTools::getRunningDaemons();
    $diff = AbeilleTools::diffExpectedRunningDaemons($parameters, $running);
// logToFile("diff=".json_encode($diff));
    $oneMissing = FALSE;
    displayDaemonStatus($diff, "Cmd", $oneMissing);
    displayDaemonStatus($diff, "Parser", $oneMissing);
    for ($zgNb = 1; $zgNb <= maxNbOfZigate; $zgNb++) {
        if ($parameters['ab::gtwEnabled'.$zgNb] != "Y")
            continue; // Zigate disabled
        displayDaemonStatus($diff, "SerialRead".$zgNb, $oneMissing);
        if ($parameters['ab::gtwSubType'.$zgNb] == "WIFI")
            displayDaemonStatus($diff, "Socat".$zgNb, $oneMissing);
    }

    echo "<hr>";

?>
<script>var js_queueXToCmd = <?php echo $abQueues['xToCmd']['id']; ?>;</script>
<div id="inclusionButtons" style="display:flex; flex-wrap:wrap; gap:5px;">
<?php
    for ($i = 1; $i <= maxNbOfZigate; $i++) {
        if (config::byKey('ab::gtwEnabled'.$i, 'Abeille', 'N', 1) == "Y") {
?>
			    <a id="bt_include<?php echo $i;?>" href="#" class="btn-inclusion ui-btn ui-btn-raised clr-primary waves-effect waves-button" data-zgid="<?php echo $i;?>" style="margin:0;">
				    <i class="fa fa-link"></i> {{Inclusion Z<?php echo $i;?>}}
			    </a>
<?php
        }
    }
?>
</div>
<?php

?>


<hr>

<style>
.btn-inclusion.including {
    background: #f57c00 !important;
    color: #fff !important;
    animation: pulse-inclusion 1.5s infinite;
}
.btn-inclusion.including i {
    animation: spin-icon 1s linear infinite;
}
@keyframes pulse-inclusion {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
@keyframes spin-icon {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
#healthTable {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
#healthTable thead th {
    background: #455a64;
    color: #fff;
    padding: 8px 10px;
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
    border-bottom: 2px solid #37474f;
}
#healthTable thead th:hover {
    background: #546e7a;
}
#healthTable thead th .sortIcon {
    font-size: 0.75em;
    opacity: 0.8;
}
#healthTable tbody td {
    padding: 6px 10px;
    border-bottom: 1px solid rgba(128,128,128,0.2);
}
#healthTable tbody tr:nth-child(even) {
    background: rgba(128,128,128,0.06);
}
#healthTable tbody tr:hover {
    background: rgba(128,128,128,0.15);
}
#healthTable tbody td a {
    font-size: inherit !important;
}
#healthTable td.status-ok    { background: #388e3c; color: #fff; text-align: center; }
#healthTable td.status-warn  { background: #f57c00; color: #fff; text-align: center; }
#healthTable td.status-alert { background: #d32f2f; color: #fff; text-align: center; }
#healthTable td.status-ok .label,
#healthTable td.status-warn .label,
#healthTable td.status-alert .label {
    color: #fff;
    background: none;
    padding: 0;
}
#healthTable td.col-depuis {
    text-align: right;
}
</style>

<table id="healthTable">
    <thead>
    <tr>
        <th>{{Module}} <span class="sortIcon"></span></th>
        <th>{{Statut}} <span class="sortIcon"></span></th>
        <th style="text-align:right">{{Depuis (h)}} <span class="sortIcon"></span></th>
    </tr>
    </thead>
    <tbody>
    <?php

        foreach ($eqLogics as $eqLogic) {
            $alert = 0;

            // Module
            echo "\n<tr>".'<td><a href="'.$eqLogic->getLinkToConfiguration().'" style="text-decoration: none;">'.$eqLogic->getHumanName(true).'</a></td>';

            // Status
            $status = '<span class="label">{{OK}}</span>';
            if ( (time() - strtotime($eqLogic->getStatus('lastCommunication'))) > (60*$eqLogic->getTimeout()) ) {
                 $status = '<span class="label">{{Time Out}}</span>';
                $alert = 1;
            }
            if ( (time() - strtotime($eqLogic->getStatus('lastCommunication'))) > ((2*60*$eqLogic->getTimeout())) ) {
                $status = '<span class="label">{{Time Out}}</span>';
                $alert = 2;
            }

            if ($eqLogic->getStatus('state') == '-') {
                $status = '<span class="label">-</span>';
                $alert = 0;
            }

            $statusClass = ['status-ok', 'status-warn', 'status-alert'][$alert];
            echo '<td class="'.$statusClass.'" data-sort="'.$alert.'">'.$status.'</td>';

            // Depuis
            $depuisVal = floor((time() - strtotime($eqLogic->getStatus('lastCommunication'))) / 3600);
            echo '<td class="col-depuis" data-sort="'.$depuisVal.'">'.$depuisVal.'</td>';

        }
    ?>
    </tbody>
</table>

<?php
foreach ($IEEE_Table as $IEEE=>$IEEE_Device) {
    if ($IEEE_Device>1) { echo "{{L'adresse $IEEE est dupliqué ce n'est pas normal. On ne doit avoir qu'un équipment par adresse IEEE}}</br>"; }
}
?>

<script>
(function() {
    var table = document.getElementById('healthTable');
    if (!table) return;
    var headers = table.querySelectorAll('thead th');
    var sortState = {};

    headers.forEach(function(th, colIdx) {
        th.addEventListener('click', function() {
            var asc = sortState[colIdx] === 'asc' ? 'desc' : 'asc';
            sortState = {};
            sortState[colIdx] = asc;

            // Update sort icons
            headers.forEach(function(h) { h.querySelector('.sortIcon').textContent = ''; });
            th.querySelector('.sortIcon').textContent = asc === 'asc' ? ' \u25B2' : ' \u25BC';

            var tbody = table.querySelector('tbody');
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

            rows.sort(function(a, b) {
                var cellA = a.cells[colIdx];
                var cellB = b.cells[colIdx];
                var valA = cellA.getAttribute('data-sort');
                var valB = cellB.getAttribute('data-sort');

                if (valA !== null && valB !== null) {
                    // Numeric sort
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                } else {
                    // Text sort
                    valA = (cellA.textContent || '').trim().toLowerCase();
                    valB = (cellB.textContent || '').trim().toLowerCase();
                }

                if (valA < valB) return asc === 'asc' ? -1 : 1;
                if (valA > valB) return asc === 'asc' ? 1 : -1;
                return 0;
            });

            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    });
})();

// Inclusion buttons
(function() {
    function sendInclusionCmd(zgId, mode) {
        var xhr = new XMLHttpRequest();
        var topic = 'CmdAbeille' + zgId + '/0000/zgSetPermitMode';
        var payload = 'mode=' + mode;
        topic = topic.replace(/&/g, '_');
        payload = payload.replace(/&/g, '_');
        var url = '/plugins/Abeille/core/php/AbeilleCliToQueue.php?action=sendMsg'
            + '&queueId=' + js_queueXToCmd
            + '&topic=' + topic
            + '&payload=' + payload;
        xhr.open('GET', url, true);
        xhr.send();
    }

    var buttons = document.querySelectorAll('.btn-inclusion');
    buttons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var zgId = this.getAttribute('data-zgid');
            var wasActive = this.classList.contains('including');

            // Stop inclusion on all zigates
            buttons.forEach(function(b) {
                if (b.classList.contains('including')) {
                    sendInclusionCmd(b.getAttribute('data-zgid'), 'stop');
                    b.classList.remove('including');
                    b.querySelector('i').className = 'fa fa-link';
                    b.childNodes[b.childNodes.length - 1].textContent = ' Inclusion Z' + b.getAttribute('data-zgid');
                }
            });

            // If it wasn't active, start inclusion on this one
            if (!wasActive) {
                sendInclusionCmd(zgId, 'start');
                this.classList.add('including');
                this.querySelector('i').className = 'fa fa-spinner';
                this.childNodes[this.childNodes.length - 1].textContent = ' Inclusion Z' + zgId + ' (active)';
            }
        });
    });
})();
</script>
