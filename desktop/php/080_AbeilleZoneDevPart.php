<br/>
<legend><i class="fa fa-cog"></i> {{Zone developpeurs}}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">

	<br/>
	<span style="font-weight:bold">Attention !! Cette partie est réservée aux developpeurs.</span> Ne pas s'y aventurer sauf sur leur demande expresse.<br>
	Elle ne contient que des fonctionalités de test ou en cours de developpement, pour lesquelles il ne sera fourni <span style="font-weight:bold">aucun support</span>.<br>
	<br/>
	<a id="idDevGrpShowHide" class="btn btn-success">Montrer</a>
	<div id="idDevGrp" style="display:none">
		<hr>
		<br/>
		<!-- <label>Fonctionalités cachées:</label>
		<?php
			if (file_exists($dbgFile))
				echo '<input type="button" onclick="xableDevMode(0)" value="Désactiver" title="Désactive le mode developpeur">';
			else
				echo '<input type="button" onclick="xableDevMode(1)" value="Activer" title="Active le mode developpeur">';
		?>
		<br/> -->

		<!-- Following functionalities are visible only if 'tmp/debug.json' file exists (developer mode). -->
		<?php if (isset($dbgConfig)) { ?>
		<label>Log parser. Désactiver:</label>
		<?php
			if (isset($dbgConfig['dbgParserLog'])) {
				$dbgParserLog = implode(" ", $dbgConfig['dbgParserLog']);
				echo '<input type="text" id="idParserLog" title="AbeilleParser messages type to disable (ex: 8000)" style="width:400px" value="'.$dbgParserLog.'">';
			} else
				echo '<input type="text" id="idParserLog" title="AbeilleParser messages type to disable (ex: 8000)" style="width:400px">';
		?>
		<input type="button" onclick="saveChanges()" value="Sauver" title="Sauve la config dans 'debug.json'">
		<br/>
		<?php } ?>

		<!-- Misc -->
		<input type="submit" name="submitButton" value="Identify">
		<table>
			<tr>
				<td>
					Widget Properties
				</td>
			</tr><tr>
				<td>
					Dimension
				</td><td>
					<label control-label" data-toggle="tooltip" title="Largeur par defaut du widget que vous souhaitez.">Largeur</label>
					<input type="text" name="Largeur">
				</td><td>
					<label control-label" data-toggle="tooltip" title="Hauteur par defaut du widget que vous souhaitez.">Largeur</label>
					<input type="text" name="Hauteur">
				</td><td>
					<input type="submit" name="submitButton" value="Set Size">
				</td>
			</tr><tr>
				</td>
				<!-- <td>
					<a class="btn btn-danger  eqLogicAction pull-right" data-action="removeSelect"><i class="fa fa-minus-circle"></i>  {{Supprime les objets sélectionnés}}</a>
				</td> -->
				<td>
					<a class="btn btn-danger  eqLogicAction pull-right" data-action="removeAll"><i class="fas fa-minus-circle"></i>  {{Supprimer tous les objets}}</a>
				</td>
			</tr><tr>
				<td>
					<a class="btn btn-danger  eqLogicAction pull-right" data-action="exclusion"><i class="fas fa-sign-out"></i>  {{Exclusion}}</a>
				</td>
			</tr>
		</table>

	</div> <!-- End of developer area -->
	<br/><br/>

</div>