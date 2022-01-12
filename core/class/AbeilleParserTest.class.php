<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleParser.class.php';
require_once './AbeilleTools.class.php';

$log = array();

function parserLog($logLevel,$message) {
    global $log;

    $log[] = $message;
}


class AbeilleParserTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php AbeilleParser.class.php', $output, $retval);
        $this->assertSame( 0, count($output) );
        $this->assertSame( 0, $retval );
    }

    function test_deviceAnnounce() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );

        global $log;

        $net = 'Abeille1';
        $addr = '81F6';
        $ieee = '14B457FFFE79EBA9';
        $capa = '8E';
        $rejoin = '00';

        $abeilleParser = new AbeilleParser;

        // Standard
        $log = [];
        $abeilleParser->deviceAnnounce($net, $addr, $ieee, $capa, $rejoin);

        $logExpected = '["  EQ new to parser","  Requesting active end points list"]';
        $this->assertSame( $logExpected, json_encode($log) );

        // Change Addr
        $addr = '8888';
        $log = [];
        $abeilleParser->deviceAnnounce($net, $addr, $ieee, $capa, $rejoin);

        $logExpected = '["  EQ already known: Addr updated from 81F6 to 8888","  EQ already known: Status=identifying","  Device identification already ongoing"]';
        $this->assertSame( $logExpected, json_encode($log) );

        // New ieee
        $ieee = 'FFB457FFFE79EBFF';
        $log = [];
        $abeilleParser->deviceAnnounce($net, $addr, $ieee, $capa, $rejoin);

        $logExpected = '["  ERROR: There is a different EQ (ieee=14B457FFFE79EBA9) for addr 8888"]';
        $this->assertSame( $logExpected, json_encode($log) );


    }

}


?>

