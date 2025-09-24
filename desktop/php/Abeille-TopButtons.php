<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
<div class="eqLogicThumbnailContainer">

	<div class="cursor eqLogicAction logoSecondary" style="color:#767676;" data-action="gotoPluginConf">
		<i class="fas fa-wrench"></i>
		<br/>
		<span>{{Configuration}}</span>
	</div>

	<?php
		$tools = array(
			'health'    => array( 'bouton'=>'bt_healthAbeille',		'icon'=>'fa-medkit',        'text'=>'{{Santé}}' ),
			'netList'   => array( 'bouton'=>'bt_network',			'icon'=>'fa-sitemap',       'text'=>'{{Réseau}}' ),
			'net'       => array( 'bouton'=>'bt_networkMap',        'icon'=>'fa-map',           'text'=>'{{Placement réseau}}' ),
			'compat'    => array( 'bouton'=>'bt_supportedEqList',	'icon'=>'fa-align-left',    'text'=>'{{Compatibilité}}' ),
			'ota'       => array( 'bouton'=>'bt_Ota',				'icon'=>'fa-paperclip',     'text'=>'{{Mises-à-jour OTA}}' ),
			'support'   => array( 'bouton'=>'bt_maintenancePage',	'icon'=>'fa-medkit',        'text'=>'{{Maintenance}}' ),
		);
		// if (isset($dbgDeveloperMode)) {
			// No time to spent on next subject. No longer functional and useless.
			// $tools['graph'] = array( 'bouton'=>'bt_graph', 'icon'=>'fa-flask', 'text'=>'{{Graph}}' );
		// }

		foreach ($tools as $key => $tool) {
			echo '<div class="cursor eqLogicAction logoSecondary" style="color:#767676;" id="'.$tool['bouton'].'">';
			echo '	<i class="fas '.$tool['icon'].'"></i>';
			echo '  <br/>';
			echo '  <span>'.$tool['text'].'</span>';
			echo '</div>';
		}
	?>
</div> <!-- End class="eqLogicThumbnailContainer" -->

