<br/>
<label style="margin-right : 20px">{{Groupes}}</label>
<?php
echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/Groups.html"><i class="fas fa-book"></i>{{Documentation}}</a>';
?>
<br/>

<div id="the whole thing" style="height:100%; width:100%; overflow: hidden;">
	<div id="leftMargin" style="float: left; width:2%;">.
	</div>
	<div id="leftThing" style="float: left; width:40%;">
		<table class="table-bordered table-condensed" style="width: 100%; margin: 10px 10px;">
			<thead>
				<tr style="background-color: grey !important; color: white !important;">
					<th>{{Module}}</th>
					<th>{{Telecommande}}</th>
					<th>{{Membre}}</th>
				</tr>
			</thead>
			<tbody>
				<?php
					$abeille = new Abeille();
					$commandIEEE = new AbeilleCmd();

					// foreach ($eqLogics as $key => $eqLogic) {
					for ($zgNb = 1; $zgNb <= $zigateNb; $zgNb++) {
						if (config::byKey('AbeilleActiver'.$i, 'Abeille', 'N') != 'Y')
							continue; // This Zigate is disabled

						foreach ($eqPerZigate[$zgNb] as $eqId) {
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
					} // For each active zigate
				?>
			</tbody>
		</table>
	</div>

	<div id="rightMargin" style="float: left; width:2%;">.
	</div>
	<div id="rightThing" style="float: left; width:40%;">
		<table style="margin: 10px 10px;">
			<tr>
				<td align="center">
				<input type="submit" name="submitButton" value="Get Infos from NE">
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
	</div>
</div>
<br/>
