<?php
    // Profalux specific commands (to device).
    // Included by 'AbeilleCmd.php'

    // Set tilt and/or lift. Replaces obsolete 'moveToLiftAndTiltBSO' cmd.
    // $command['cmd']: 00=Set Tilt, 01=Set Lift
    // $command['tilt']: Mandatory if cmd=00
    // $command['lift']: Mandatory if cmd=01
    // $command['duration']: Optional (default=10sec)
    function profaluxSetTiltLift($net, $addr, $ep, $command) {
        cmdLog2('debug', $addr, "  profaluxSetTiltLift(Net={$net}, Addr={$addr}, EP={$ep}, Command=".json_encode($command, JSON_UNESCAPED_SLASHES));

        $cmd = $command['cmdParams']['cmd'];

        # Option Parameter uint8   Bit0 Ask for lift action, Bit1 Ask fr a tilt action
        # Lift Parameter   uint8   Lift value between 1 to 254
        # Tilt Parameter   uint8   Tilt value between 0 and 90
        # Transition Time  uint16  Transition Time between current and asked position
        if ($cmd == "00") {
            $option = "02"; // Set tilt only
            if (!isset($command['cmdParams']['tilt'])) {
                cmdLog2('error', $addr, "  Valeur 'tilt' manquante pour profaluxSetTiltLift()");
                return;
            }
        } else {
            $option = "01"; // Set lift only
            if (!isset($command['cmdParams']['lift'])) {
                cmdLog2('error', $addr, "  Valeur 'lift' manquante pour profaluxSetTiltLift()");
                return;
            }
        }
        $lift = isset($command['cmdParams']['lift']) ? $command['cmdParams']['lift'] : 0;
        $tilt = isset($command['cmdParams']['tilt']) ? $command['cmdParams']['tilt'] : 0;
        $duration = isset($command['cmdParams']['duration']) ? $command['cmdParams']['duration'] : 10; // Default = 10sec

        cmdLog2('debug', $addr, "  Cmd={$cmd}, Option={$option}, Tilt={$tilt}, Lift={$lift}, Duration={$duration}");

        // Final formatting
        $lift = sprintf("%02X", $lift);
        $tilt = sprintf("%02X", $tilt);
        $duration = AbeilleTools::reverseHex(sprintf("%04X", $duration));
        $data = $option.$lift.$tilt.$duration;

        $header = array(
            'net' => $net,
            'addr' => $addr,
            'ep' => $ep,
            'clustId' => '0008',
            'clustSpecific' => true, // Cluster specific frame
            'manufCode' => "1110",
            'cmd' => '10' // Profalux specific command
        );
        AbeilleCmdProcess::sendRawMessage($header, $data);
    }
?>
