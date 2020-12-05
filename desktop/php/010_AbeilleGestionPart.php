<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>

<div class="eqLogicThumbnailContainer">
							
	<div class="cursor eqLogicAction logoSecondary" style="color:#767676;" data-action="gotoPluginConf">
		<i class="fas fa-wrench"></i>
		<br/>
		<span>{{Configuration}}</span>
	</div>

		<?php
			foreach ( $outils as $key=>$outil ) {
				echo '<div class="cursor eqLogicAction logoSecondary" style="color:#767676;" id="'.$outil['bouton'].'">';
				echo '	<i class="fas '.$outil['icon'].'"></i>';
				echo '  <br/>';
				echo '  <span>'.$outil['text'].'</span>';
				echo '</div>';
			}
		?>

</div>

