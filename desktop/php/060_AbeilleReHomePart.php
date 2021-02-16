</br>
<legend><i class="fas fa-cogs"></i> {{Migration d equipements }}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	<label style="margin-right : 20px">Migration d equipement</label>
	<?php
	// TODO: Full URL to point on eq replacement chapter
	echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
	<form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">
		<br/>
		Equipement à migrer:
		<br/>
		<select name="beeId" style="width : 40%">
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
		Vers la zigate:
		<br/>
		<select name="zigateY" style="width : 40%">
		<?php
			for ( $i=1; $i<=config::byKey('zigateNb', 'Abeille', '0', 1); $i++ ) {
				echo '<option value="' . $i . '">Zigate' . $i . '</option>';
			}
		?>
		</select>
		</br><br/>
		<input type="submit" name="submitButton" value="Remplace">
		</br><br/>
	</form>

</div>


