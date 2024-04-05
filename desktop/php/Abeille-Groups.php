<?php
	function displayGroups($zgId) {

		echo '<br>';
		// echo '<a class="btn btn-warning" onclick="sendToCmd(\'getGroups\')">Get groups</a>';
		// echo '<input type="text" id="idGroup" style="width:60px;margin-left:8px" placeholder="{{Groupe}}" title="Numéro de groupe (hexa 4 caracteres)">';
		// echo '<a class="btn btn-warning" onclick="sendToCmd(\'addGroup\')">Add group</a>';
		// echo '<a class="btn btn-warning" onclick="sendToCmd(\'removeGroup\')">Remove group</a>';
		// echo '<a class="btn btn-warning" onclick="sendToCmd(\'setGroupRemote\')">Set group remote</a>';
		// echo '<a class="btn btn-warning" onclick="sendToCmd(\'setGroupRemoteLegrand\')">Set group remote Legrand</a>';

		// echo '<table class="table-bordered table-condensed" style="width: 100%; margin: 10px 10px;">';
		echo '<table class="table-bordered table-condensed" style="width:100%">';
		echo '<thead>';
		echo '<tr style="background-color: grey !important; color: white !important;">';
		echo '<th align="left" style="width:400px">{{Module}}</th>';
		echo '<th align="left" style="width:100px">{{End point}}</th>';
		echo '<th align="left" style="width:100px"></th>';
		echo '<th align="left">{{Groupes}}</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		global $eqPerZigate;
		// $abeille = new Abeille();
		// $commandIEEE = new AbeilleCmd();
		foreach ($eqPerZigate[$zgId] as $eqId => $eq) {
			$eqLogic = eqLogic::byId($eqId);
			$eqHName = $eqLogic->getHumanName(true);

			/* ab::zigbee reminder
			   zigbee = array(
					"ep1" => "1234/DEAD",
					"ep2" => "4545/1234"
			   ) */
			$zigbee = $eqLogic->getConfiguration("ab::zigbee", []);
			if (!isset($zigbee['groups']))
				continue; // No 'groups' => not supported

			if ($eq['isEnabled']) {
				$dis1 = '';
				$dis2 = '';
			} else {
				$dis1 = '<s>';
				$dis2 = '</s>';
			}

			$groups = $zigbee['groups'];
			foreach ($groups as $epId => $grps) {
				echo '<tr><td>'.$dis1.$eqHName.$dis2.'</td>';
				echo '<td >'.$epId.'</td>';
				echo '<td >';
					if ($eq['isEnabled']) {
						echo '<a class="btn btn-default" onclick="sendToCmd(\'getGroups2\', \''.$zgId.'\', \''.$eq['addr'].'\', \''.$epId.'\')" title="{{Raffraichir la liste des groupes}}"><i class="fas fa-sync"></i></a>';
						echo '<a class="btn btn-default" onclick="sendToCmd(\'addGroup2\', \''.$zgId.'\', \''.$eq['addr'].'\', \''.$epId.'\')" title="{{Ajouter un groupe}}"><i class="fas fa-plus"></i></a>';
						echo '<a class="btn btn-default" onclick="sendToCmd(\'removeAllGroups\', \''.$zgId.'\', \''.$eq['addr'].'\', \''.$epId.'\')" title="{{Supprimer tout les groupes}}"><i class="fas fa-trash-alt"></i></a>';
					}
				echo '</td>';
				echo '<td >';
				if ($grps != '') {
					$gArr = explode("/", $grps);
					foreach ($gArr as $grp) {
						echo '<a class="btn btn-default" onclick="sendToCmd(\'removeGroup2\', \''.$zgId.'\', \''.$eq['addr'].'\', \''.$epId.'\', \''.$grp.'\')" title="{{Suppression du groupe}}"><i class="fas fa-trash-alt"></i></a> '.$grp.' ';
					}
				}
				echo '</td></tr>';
			}
		} // For each eqPerZigate[]
		echo '</tbody>';
		echo '</table>';
	}
?>

<legend><i class="fa fa-cogs"></i> {{Groupes}}
	<?php
	echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'Groups.html"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

<!-- <br/>
<label style="margin-right : 20px">{{Groupes}}</label>
<br/> -->

<div id="the whole thing" style="height:100%; width:100%; overflow: hidden;">
	<!-- <div id="leftMargin" style="float: left; width:2%;">.
	</div> -->
	<div id="leftThing" style="float: left; width:80%;">
		<?php
			echo '<br>';
			// echo '<a class="btn btn-warning" onclick="sendToCmd(\'getGroups\')">Get groups</a>';
			echo '<input type="text" id="idGroup" style="width:60px;margin-left:10px" placeholder="{{Groupe}}" title="Numéro de groupe (hexa 4 caracteres)">';
			// echo '<a class="btn btn-warning" onclick="sendToCmd(\'addGroup\')">Add group</a>';
			// echo '<a class="btn btn-warning" onclick="sendToCmd(\'removeGroup\')">Remove group</a>';
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'setGroupRemote\')">Set group remote</a>';
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'setGroupRemoteLegrand\')">Set group remote Legrand</a>';
			echo '<br>';

			for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
				if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') != 'Y')
					continue; // This Zigate is disabled

				displayGroups($zgId);
			}
		?>
	</div>

    <!-- Group choice popup (will be cloned to bootbox.dialog) -->
    <div id="idGroupChoicePopup" style="display:none">
		<p>{{Entrez le groupe (format hexa 4 caractères) que vous souhaitez ajouter et cliquez OK}}</p>
		<input type="text" id="idGroup2" style="width:60px;margin-left:10px" placeholder="{{Groupe}}" title="Numéro de groupe (hexa 4 caracteres)">
        <p style="text-align:center; margin-top:30px">
            <a class="btn btn-secondary">{{Annuler}}</a>
            <a class="btn btn-success">{{OK}}</a>
        </p>
    </div>
</div>
<br/>

</div>
