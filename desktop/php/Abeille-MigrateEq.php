</br>
<legend><i class="fas fa-cogs"></i> {{Migration d'équipements}}
	<?php
	echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'Utilisation.html#migration-d-equipements"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

		<br/>
		<label class="col-sm-2 control-label">{{Equipement à migrer}}:</label>
    	<div class="col-sm-10">
			<select id="idEq" style="width : 40%">
			<?php
				foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
					list($net, $addr) = explode("/", $eqLogic->getLogicalId());
					if ($addr == '0000')
						continue; // Excluding zigate

					$eqId = $eqLogic->getId();
					$zgId = substr($net, 7); // AbeilleX => X
					echo '<option value="'.$eqId.'">Zigate '.$zgId.': '.$eqLogic->getHumanName().'</option>';
				}
			?>
			</select>
		</div>
		<label class="col-sm-2 control-label">{{Vers la Zigate}}:</label>
    	<div class="col-sm-10">
			<select id="idDstZg" style="width : 40%">
			<?php
				for ($zgId = 1; $zgId <= maxNbOfZigate; $zgId++) {
					if (config::byKey('ab::zgEnabled'.$zgId, 'Abeille', 'N') != 'Y')
						continue; // Disabled
					echo '<option value="'.$zgId.'">Zigate '.$zgId.'</option>';
				}
			?>
			</select>
		</div>
		<a class="btn btn-warning" onclick="migrateEq()">{{Migrer}}</a>
		</br><br/>
</div>


