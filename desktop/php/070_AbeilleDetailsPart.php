<legend><i class="fa fa-cog"></i> {{Details ZiGate}}</legend>
<br>
<i>Ces tableau ne sont pas automatiquement rafraichi, ils sont mis à jour à l ouverture de la page.</i>
<br>

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
            echo 'ZiGate '.$i.'<br>';
            $rucheId = Abeille::byLogicalId( 'Abeille'.$i.'/Ruche', 'Abeille')->getId();
            echo '<table border="1" style="border:1px">';
            foreach ( $params as $key=>$param ){
                if ( is_object(AbeilleCmd::byEqLogicIdAndLogicalId($rucheId, $param)) ) {
                    $command = AbeilleCmd::byEqLogicIdAndLogicalId($rucheId, $param);
                    echo '<tr><td>'.$key.'</td>       <td align="center">' . $command->execCmd() . '</td></tr>';
                }
            }
            echo '</table>';
        }
        echo '</br>';
    }
?>