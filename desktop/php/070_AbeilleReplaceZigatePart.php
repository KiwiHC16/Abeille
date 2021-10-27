</br>
<legend><i class="fas fa-cogs"></i> {{Zigate}}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	<label style="margin-right : 20px">Remplaement de zigate</label>
	<?php
	// TODO: Full URL to point on eq replacement chapter
	echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'"><i class="fas fa-book"></i>{{Documentation}}</a>';
	?>
	<form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">
		Remplacement d une zigate:
		<br/>
		<select name="zigateZ" style="width : 40%">
		<?php
			for ( $i=1; $i<= maxNbOfZigate; $i++ ) {
				echo '<option value="' . $i . '">Zigate' . $i . '</option>';
			}
		?>
		</select>
		</br><br/>
		<input type="submit" name="submitButton" value="ReplaceZigate">
		</br><br/>
	</form>

</div>


