</br>
<legend><i class="fas fa-cogs"></i> {{Remplacement d equipements }}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">
	
	<br/>
	<label style="margin-right : 20px">Remplacement d equipement</label>
	<a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/"><i class="fas fa-book"></i>Documentation</a>
	
	<form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">
		<br/>
		Ghost / Equipement Cassé:
		<br/>
		<select name="ghost" style="width : 40%">
		<?php
			foreach (Abeille::byType('Abeille',1) as $eq) {
				if (is_object($eq->getObject())) {
					echo '<option value="' . $eq->getId() . '">' . $eq->getObject()->getName() . ' -> ' . $eq->getName() . '</option>';
				}
				else {
					echo '<option value="' . $eq->getId() . '">' . '{{Aucun}}' . ' -> ' . $eq->getName() . '</option>';
				}
			}
		?>
		</select>
		<br/>
		Remplacé par:
		<br/>
		<select name="real" style="width : 40%">
		<?php
			foreach (Abeille::byType('Abeille',1) as $eq) {
				if (is_object($eq->getObject())) {
					echo '<option value="' . $eq->getId() . '">' . $eq->getObject()->getName() . ' -> ' . $eq->getName() . '</option>';
				}
				else {
					echo '<option value="' . $eq->getId() . '">' . '{{Aucun}}' . ' -> ' . $eq->getName() . '</option>';
				}
			}
		?>
		</select>
		</br><br/>
		<input type="submit" name="submitButton" value="Remplace">
		</br><br/>
	</form>

</div>	


