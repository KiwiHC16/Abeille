</br>
<legend><i class="fas fa-cogs"></i> {{Migration d'équipements}}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	<label style="margin-right : 20px">Migration d'équipements</label>
	<?php
	// TODO: Full URL to point on eq replacement chapter
	// echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
	<!-- <form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post"> -->
		<br/>
		Equipement à migrer:
		<br/>
		<select id="idEq" style="width : 40%">
		<?php
			foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
				$eqId = $eqLogic->getId();
				list($net, $addr) = explode( "/", $eqLogic->getLogicalId());
				$zgId = substr($net, 7); // AbeilleX => X
				echo '<option value="'.$eqId.'">Zigate '.$zgId.': '.$eqLogic->getHumanName().'</option>';
			}
		?>
		</select>
		<br/>
		Vers la zigate:
		<br/>
		<select id="idDstZg" style="width : 40%">
		<?php
			for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
				if (config::byKey('AbeilleActiver'.$zgId, 'Abeille', 'N') != 'Y')
					continue; // Disabled
				echo '<option value="'.$zgId.'">Zigate '.$zgId.'</option>';
			}
		?>
		</select>
		</br><br/>
		<!-- <input type="submit" name="submitButton" value="ReHome"> -->
		<a class="btn btn-warning" onclick="migrateEq()">{{Migrer}}</a>
		</br><br/>
	<!-- </form> -->

</div>


