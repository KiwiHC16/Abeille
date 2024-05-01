<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleParser.class.php';
require_once './AbeilleTools.class.php';

///////////////////////////////////////////
// Dummy functions to be able to run others functions

function parserLog($logLevel,$message) {
}

///////////////////////////////////////////
// phpUnit Class to run all tests.

class AbeilleParserTestDB extends TestCase {

    // Test to send an info from AbeilleParser to an Eq.
    // Send a group to a bulb
    function test_msgToAbeille2() {

        $config = AbeilleTools::getParameters();
        AbeilleTools::startDaemons($config);
        sleep(2);
        cron::byClassAndFunction('Abeille', 'deamon')->run();
        sleep(2);
        $abeilleParser = new AbeilleParser;

        $group = '4343';

        $msg = array(
            'src'   => 'parser',
            'type'  => 'attributeReport',
            'net'   => 'Abeille1',
            'addr'  => '81F6',
            'ep'    => '01',
            'name'  => 'Group-Membership',
            'value' => $group,
            'time'  => time(),
            'lqi'   => '00',
        );

        $abeilleParser->msgToAbeille2($msg);

        sleep(2);
        $cmdInfoGroup = cmd::byId(27303);

        $this->assertSame( $group, $cmdInfoGroup->execCmd() );
    }

    // function test_deviceCreate() {
    //     fwrite(STDOUT, "\n\n" . __METHOD__ );

    //     global $log;
    //     global $msgToCmd;
    //     unset($GLOBALS['eqList']);

    //     $net = "Abeille1";
    //     $addr = "AAAA";
    //     $GLOBALS['eqList'][$net] = array();
    //     $GLOBALS['eqList'][$net][$addr] = array();

    //     $eq = &$GLOBALS['eqList'][$net][$addr];

    //     // $eq['ieee']             = "FFB457FFFE79EBFF";
    //     // $eq['epFirst']          = "01";
    //     // $eq['modelIdentifier']  = "";
    //     // $eq['manufacturer']     = "";
    //     // $eq['jsonId']           = "";
    //     // $eq['jsonLocation']     = "";
    //     // $eq['capa']             = "";

    //     $abeilleParser = new AbeilleParser;

    //     $net = "Abeille1";
    //     $addr = "AAAA";
    //     $abeilleParser->deviceCreate($net, $addr);

    //     sleep(2);
    //     $eq = Abeille::byLogicalId($net.'/'.$addr, 'Abeille');

    //     $this->assertSame( true, is_object($eq) );

    // }
}


?>

