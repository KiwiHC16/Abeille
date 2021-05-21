<?php if (isset($dbgConfig)) { ?>
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

		<!-- Misc -->
		<input type="submit" name="submitButton" value="Identify">
		<br>
		<a class="btn btn-danger eqLogicAction pull-left" data-action="removeAll"><i class="fas fa-minus-circle"></i>  {{Supprimer tous les objets}}</a>
	</div> <!-- End of developer area -->
	<br/><br/>
</div>
<?php } ?>
