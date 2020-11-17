<legend><i class="fa fa-cogs"></i> {{Gestion des parametres radio Zigate}}</legend>

<label>Channel Mask</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i>Documentation</a></br>
Channel Mask:   <input type="text" name="channelMask"   placeholder="XXXXXXXX">
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
        if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
            echo '<input type="submit" name="submitButton" value="Set Channel Mask Z'.$i.'">';
        }
    }
    ?>
</br>
</br>

<label>Extended PANID</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Radio.html"><i class="fas fa-book"></i>Documentation</a></br>
Extended PANID: <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Extended PANID Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Tx Power</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Radio.html"><i class="fas fa-book"></i>Documentation</a></br>
Tx Power: <input type="text" name="TxPowerValue"  placeholder="XX">
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="TxPower Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Time</label> </br>
Set Time:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="SetTime Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Get Time</label> </br>
Get Time:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="getTime Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Led On</label> </br>
Set Led On:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="SetLedOn Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Led Off</label> </br>
Set Led Off:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="SetLedOff Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Certification CE</label> </br>
Set Certification CE:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Certification CE Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Certification FCC</label> </br>
Set Certification FCC:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Certification FCC Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Start Zigbee Network</label> </br>
Start Zigbee Network:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Start Network Z'.$i.'">';
    }
}
?>
</br>
</br>

<label>Set Mode</label> </br>
Normal:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Normal Mode Z'.$i.'">';
    }
}
?>
</br>

Raw:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Raw Mode Z'.$i.'">';
    }
}
?>
</br>

Hybride:
<?php
    for ( $i=1; $i<=$zigateNb; $i++ ) {
    if ( $parametersAbeille['AbeilleActiver'.$i] == 'Y' ) {
        echo '<input type="submit" name="submitButton" value="Set Hybride Mode Z'.$i.'">';
    }
}
?>
</br>
</br>
