</br>
<legend>
	<i class="fas fa-cogs"></i>{{Transfert d'historique}}
	<?php
	echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'Utilisation.html#remplacement-d-equipements"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	<label class="col-sm-2 control-label">{{Equipement HS}}:</label>
    <div class="col-sm-10">
		<select id="idDeadEq" style="width : 40%">
		<?php
			foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
				list($net, $addr) = explode("/", $eqLogic->getLogicalId());
				if ($addr == '0000')
					continue; // Ignoring zigates
				$zgId = substr($net, 7);
				echo '<option value="'.$eqLogic->getId().'">Zigate '.$zgId.': '.$eqLogic->getHumanName().'</option>';
			}
		?>
		</select>
	</div>

	<label class="col-sm-2 control-label">{{Remplacé par}}:</label>
    <div class="col-sm-10">
		<select id="idNewEq" style="width : 40%">
		<?php
			foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
				list($net, $addr) = explode("/", $eqLogic->getLogicalId());
				if ($addr == '0000')
					continue; // Ignoring zigates
				$zgId = substr($net, 7);
				echo '<option value="'.$eqLogic->getId().'">Zigate '.$zgId.': '.$eqLogic->getHumanName().'</option>';
			}
		?>
		</select>
	</div>
	<a class="btn btn-warning" onclick="replaceEq()">{{Remplacer}}</a>
	</br><br/>
</div>


