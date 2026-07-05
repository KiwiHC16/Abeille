<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleParser.class.php';
require_once './AbeilleTools.class.php';

$log = array();
$msgToCmd = array();

function parserLog($logLevel,$message) {
    global $log;

    $log[] = $message;
}

class abeilleParserDebug extends AbeilleParser {
    function msgToCmd($topic, $payload) {
        global $msgToCmd;
        $msgToCmd[] = array($topic, $payload);
    }
}

class AbeilleParserTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php AbeilleParser.class.php', $output, $retval);
        $this->assertSame( 0, count($output) );
        $this->assertSame( 0, $retval );
    }

    // function test_deviceAnnounce() {
    //     fwrite(STDOUT, "\n\n" . __METHOD__ );

    //     global $log;

    //     $net = 'Abeille1';
    //     $addr = '81F6';
    //     $ieee = '14B457FFFE79EBA9';
    //     $capa = '8E';
    //     $rejoin = '00';

    //     $abeilleParser = new AbeilleParser;

    //     // Standard
    //     $log = [];
    //     $abeilleParser->deviceAnnounce($net, $addr, $ieee, $capa, $rejoin);

    //     $logExpected = '["  EQ new to parser","  Requesting active end points list"]';
    //     $this->assertSame( $logExpected, json_encode($log) );

    //     // Change Addr
    //     $addr = '8888';
    //     $log = [];
    //     $abeilleParser->deviceAnnounce($net, $addr, $ieee, $capa, $rejoin);

    //     $logExpected = '["  EQ already known: Addr updated from 81F6 to 8888","  EQ already known: Status=identifying","  Device identification already ongoing"]';
    //     $this->assertSame( $logExpected, json_encode($log) );

    //     // New ieee
    //     $ieee = 'FFB457FFFE79EBFF';
    //     $log = [];
    //     $abeilleParser->deviceAnnounce($net, $addr, $ieee, $capa, $rejoin);

    //     $logExpected = '["  ERROR: There is a different EQ (ieee=14B457FFFE79EBA9) for addr 8888"]';
    //     $this->assertSame( $logExpected, json_encode($log) );                 
    // }


    // function test_deviceUpdate() {
    //     fwrite(STDOUT, "\n\n" . __METHOD__ );

    //     global $log;
    //     global $msgToCmd;
    //     unset($GLOBALS['eqList']);

    //     $net = 'Abeille1';
    //     $addr = '81F6';
    //     $ep = '01';

    //     $abeilleParserDebug = new AbeilleParserDebug;

    //     // No update
    //     $log = []; $msgToCmd = [];
    //     $abeilleParserDebug->deviceUpdate($net, $addr, $ep, $updType = null, $value = null);
    //     $logExpected = '["  deviceUpdate(\'\', \'\'): Unknown device detected","  Requesting IEEE"]';
    //     $this->assertSame( $logExpected, json_encode($log) );
    //     $cmdExpected = '[["CmdAbeille1\/81F6\/getIeeeAddress","priority=5"]]';
    //     $this->assertSame( $cmdExpected, json_encode($msgToCmd) );

    //     $log = []; $msgToCmd = [];
    //     $abeilleParserDebug->deviceUpdate($net, $addr, $ep, $updType = null, $value = null);
    //     $logExpected = '["  Requesting IEEE"]';
    //     $this->assertSame( $logExpected, json_encode($log) );
    //     $cmdExpected = '[["CmdAbeille1\/81F6\/getIeeeAddress","priority=5"]]';
    //     $this->assertSame( $cmdExpected, json_encode($msgToCmd) );

    //     $GLOBALS['eqList'][$net][$addr]['ieee'] = '14B457FFFE79EBA9';

    //     // update Ep List
    //     $updType='epList';
    //     $value='07';
    //     $log = []; $msgToCmd = [];
    //     $abeilleParserDebug->deviceUpdate($net, $addr, $ep, $updType, $value);
    //     $this->assertSame(  $value, $GLOBALS['eqList'][$net][$addr]['epList'] );
    //     $cmdExpected = '[["CmdAbeille1\/81F6\/readAttribute","ep=07&clustId=0000&attrId=0004,0005,0010"]]';
    //     $this->assertSame(  $cmdExpected, json_encode($msgToCmd) );

    //     // Check status
    //     $GLOBALS['eqList'][$net][$addr]['status'] = "identifying";
    //     $log = []; $msgToCmd = [];
    //     $return = $abeilleParserDebug->deviceUpdate($net, $addr, $ep, $updType, $value);
    //     $this->assertSame(  false, $return );
        
    // }

}


?>

