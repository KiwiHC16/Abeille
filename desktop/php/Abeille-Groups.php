<?php
	function displayZigateGroups($zgId) {
		$abeille = new Abeille();
		$commandIEEE = new AbeilleCmd();

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
		echo '<th>{{Module}}</th>';
		echo '<th>{{Telecommande}}</th>';
		echo '<th>{{Membre}}</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		global $eqPerZigate;
		foreach ($eqPerZigate[$zgId] as $eqId => $eq) {
			// $eqId = $eq['id'];
			$eqLogic = eqLogic::byId($eqId);

			$name= "";
			$groupMember = "";
			$groupTele = "";
			$print=0;
			$abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();
			$name = $eqLogic->getHumanName(true);

			if ( $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Group-Membership') ) {
				if ( strlen($commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Group-Membership')->execCmd())>2 ) {
					$groupMember = str_replace('-',' ',$commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Group-Membership')->execCmd());
					$print = 1;
				}
			}

			if ( strlen($eqLogic->getConfiguration('Groupe'))>3 ) {
				$groupTele = $eqLogic->getConfiguration('Groupe');
				$print = 1;
			}

			if ( $print ) echo '<tr><td class="one">'.$name.'</td><td align="center" class="one">'.$groupTele.'</td><td align="center" class="one">'.$groupMember.'</td></tr>';
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
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'getGroups\')">Get groups</a>';
			echo '<input type="text" id="idGroup" style="width:60px;margin-left:10px" placeholder="{{Groupe}}" title="Numéro de groupe (hexa 4 caracteres)">';
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'addGroup\')">Add group</a>';
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'removeGroup\')">Remove group</a>';
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'setGroupRemote\')">Set group remote</a>';
			echo '<a class="btn btn-warning" onclick="sendToCmd(\'setGroupRemoteLegrand\')">Set group remote Legrand</a>';
			echo '<br>';

			for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
				if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') != 'Y')
					continue; // This Zigate is disabled

				displayZigateGroups($zgId);
			}
		?>
	</div>

	<!-- <div style="float: left; width:2%;">.
	</div>
	<div style="float: left; width:40%;">
		<br>
		<a class="btn btn-warning" onclick="sendToCmd('getGroups')">Get groups</a>
		<br>

		<input type="text" id="idGroup" style="width:60px" placeholder="{{Groupe}}" title="Numéro de groupe (hexa 4 caracteres)">
		<a class="btn btn-warning" onclick="sendToCmd('addGroup')">Add group</a>
		<a class="btn btn-warning" onclick="sendToCmd('removeGroup')">Remove group</a>
		<a class="btn btn-warning" onclick="sendToCmd('setGroupRemote')">Set group remote</a>
		<a class="btn btn-warning" onclick="sendToCmd('setGroupRemoteLegrand')">Set group remote Legrand</a>
	</div> -->

	<!-- <div id="rightMargin" style="float: left; width:2%;">.
	</div>
	<div id="rightThing" style="float: left; width:40%;">
		<table style="margin: 10px 10px;">
			<tr>
				<td align="center">
					<input type="submit" name="submitButton" value="Get Group">
				</td>
			</tr>
			<tr>
				<td><br/></td>
			</tr>
			<tr>
				<td align="center">
					<label control-label data-toggle="tooltip" title="en hex de 0000 a ffff, probablement celui que vous avez récuperé de votre télécommande.">Id</label>
					<input type="text" name="group" placeholder="XXXX">
					<br/>
					<input type="submit" name="submitButton" value="Add Group">
					<input type="submit" name="submitButton" value="Remove Group">
					<br/>
					<input type="submit" name="submitButton" value="Set Group Remote">
					<input type="submit" name="submitButton" value="Set Group Remote Legrand">
				</td>
			</tr>
		</table>
	</div> -->
</div>
<br/>

</div>
