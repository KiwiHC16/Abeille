<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq.php' -->

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<form class="form-horizontal">
    
    <?php
        // TODO: Set to 0 for debug purposes.
        if (0) {
            require_once __DIR__.'/../../core/php/AbeilleZigbeeConst.php';
            include 'AbeilleEq-Advanced-fct.php';
            include 'AbeilleEq-Advanced-Common.php';

                    // If eq is a zigate (short addr=0000), display special parameters
            if ($eqAddr == "0000") {
                include 'AbeilleEq-Advanced-Zigate.php';
            }
            else {
                include 'AbeilleEq-Advanced-Device.php';
                include 'AbeilleEq-Advanced-Specific.php';
            } 

            include 'AbeilleEq-Advanced-Interrogations.php';
        }
        
    ?>

</form>
