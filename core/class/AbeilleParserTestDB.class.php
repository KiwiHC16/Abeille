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
        $abeilleParser = new AbeilleParser;

        $msg = array(
            'src' => 'parser',
            'type' => 'attributeReport',
            'net' => 'Abeille1',
            'addr' => '81F6',
            'ep' => '01',
            'name' => 'Group-Membership',
            'value' => '4321',
            'time' => time(),
            'lqi' => '00',
        );
        
        $abeilleParser->msgToAbeille2($msg);

        $cmdInfoGroup = cmd::byId(27303);

        $this->assertSame( "4321", $cmdInfoGroup->execCmd() );
    }

}


?>

