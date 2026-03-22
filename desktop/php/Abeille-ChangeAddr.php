</br>
<legend><i class="fas fa-cogs"></i> {{Changement d'adresse courte}}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	<label class="col-sm-2 control-label">{{Equipement}}:</label>
	<div class="col-sm-10">
		<select id="idChangeAddrEq" style="width : 40%">
		<?php
			foreach (Abeille::byType('Abeille', 1) as $eqLogic) {
				list($net, $addr) = explode("/", $eqLogic->getLogicalId());
				if ($addr == '0000')
					continue; // Ignoring zigates
				$zgId = substr($net, 7);
				echo '<option value="'.$eqLogic->getId().'">Zigate '.$zgId.': '.$eqLogic->getHumanName().' ('.$addr.')</option>';
			}
		?>
		</select>
	</div>

	<label class="col-sm-2 control-label">{{Nouvelle adresse courte}}:</label>
	<div class="col-sm-10">
		<input id="idChangeAddrNew" type="text" style="width:120px; display:inline-block;" class="form-control input-sm" placeholder="ex: A4C1" maxlength="4">
		<span style="margin-left:5px; color:grey;">{{4 caractères hexadécimaux (0-9, A-F)}}</span>
	</div>
	<a class="btn btn-warning" onclick="changeShortAddr()">{{Changer}}</a>
	</br><br/>
</div>

