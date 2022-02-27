</br>
<legend>
	<i class="fas fa-cogs"></i>{{Remplacement d'équipements}}
	<?php
	echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	Equipement HS à remplacer:
	<select id="idDeadEq" style="width : 40%">
	<?php
		foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
			list($net, $addr) = explode("/", $eqLogic->getLogicalId());
			if ($addr == '0000')
				continue; // Ignoring zigates
			echo '<option value="'.$eqLogic->getId().'">'.$eqLogic->getHumanName().'</option>';
		}
	?>
	</select>
	<br/>
	Remplacé par:
	<select id="idNewEq" style="width : 40%">
	<?php
		foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
			list($net, $addr) = explode("/", $eqLogic->getLogicalId());
			if ($addr == '0000')
				continue; // Ignoring zigates
			echo '<option value="'.$eqLogic->getId().'">'.$eqLogic->getHumanName().'</option>';
		}
	?>
	</select>
	</br><br/>
	<a class="btn btn-warning" onclick="replaceEq()">{{Remplacer}}</a>
	</br><br/>
</div>


