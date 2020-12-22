<?php
/* Old Abeille.class code in message() to treat enable & disable cmds from parser */

    // Si cmd activate/desactivate NE based on IEEE Leaving/Joining
    if (($cmdId == "enable") || ($cmdId == "disable")) {
        log::add('Abeille', 'debug', 'Entering enable/disable: '.$cmdId );

        $abeilles = self::byType('Abeille');
        foreach ($abeilles as $key=>$abeille) {
            $done = 0;

            if ($abeille->getConfiguration('IEEE', 'none') == $value) {
                if ($cmdId == "enable") {
                    $abeille->setIsEnable(1);
                } else {
                    $abeille->setIsEnable(0);
                    message::add("Abeille", "Equipement '".$abeille->getName()."' désactivé. Il a probablement quitté le réseau.", '');
                }
                $abeille->save();
                $abeille->refresh();

                $done = 1;
            }

            if (!$done) {
                $cmds = Cmd::byLogicalId('IEEE-Addr');
                foreach( $cmds as $cmd ) {
                    if ( $cmd->execCmd() == $value ) {
                        $abeille = $cmd->getEqLogic();
                        if ($cmdId == "enable") {
                            $abeille->setIsEnable(1);
                        } else {
                            $abeille->setIsEnable(0);
                        }
                        $abeille->save();
                        $abeille->refresh();
                    }
                    echo "\n";
                }
            }
        }
        return;
    }

?>
