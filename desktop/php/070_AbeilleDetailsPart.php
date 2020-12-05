<br/>
<legend><i class="fas fa-cog"></i> {{ZiGate}}</legend>
<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">
	
	<br/>
	<i>Ces tableau ne sont pas automatiquement rafraichi, ils sont mis à jour à l ouverture de la page.</i>
	<br/>

	<?php
		$params = array(
						'Last'               =>'Time-Time',
						'Last Stamps'        =>'Time-TimeStamp',
						'SW'                 => 'SW-Application',
						'SDK'                => 'SW-SDK',
						'Network Status'     => 'Network-Status',
						'Short address'      => 'Short-Addr',
						'PAN Id'             => 'PAN-ID',
						'Extended PAN Id'    => 'Ext_PAN-ID',
						'IEEE address'       => 'IEEE-Addr',
						'Network Channel'    => 'Network-Channel',
						'Inclusion'          => 'permitJoin-Status',
							'Time (Faites un getTime)' => 'ZiGate-Time',
		);

		for ( $i=1; $i<=$zigateNb; $i++ ) {
			if ( is_object(Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')) ) {
				echo '<br>';
				echo '<label>ZiGate '.$i.'</label><br>';
				$rucheId = Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')->getId();
				echo '<table class="table-bordered table-condensed" style="width: 25%; margin: 10px 30px;">';
				echo '<thead>';
				echo '	<tr style="background-color: grey !important; color: white !important;">';
				echo '		<th>{{Information}}</th>';
				echo '		<th>{{Donnée}}</th>';
				echo '	</tr>';
				echo '</thead>';
				foreach ( $params as $key=>$param ){
					if ( is_object(AbeilleCmd::byEqLogicIdAndLogicalId($rucheId, $param)) ) {
						$command = AbeilleCmd::byEqLogicIdAndLogicalId($rucheId, $param);
						echo '<tr><td>'.$key.'</td><td>'.$command->execCmd().'</td></tr>';
					}
				}
				echo '</table>';
			}
			echo '</br>';
		}
	?>

</div>
