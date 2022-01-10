<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleCmdPrepare.class.php';

class AbeilleCmdPrepareTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php AbeilleCmdPrepare.class.php', $output, $retval);
        $this->assertSame( 1, count($output) );
        $this->assertSame( 0, $retval );
    }

    function test_proper_parse_str() {
        $str = "X=x&Y=y&Z=z";
        $result = '{"X":"x","Y":"y","Z":"z"}';
        $this->assertSame(  $result, json_encode(AbeilleCmdPrepare::proper_parse_str($str)) );
    }

    function test_procmsg() {
        $message = (object) array( 
            "topic"      => "CmdAbeille3/3B02/OnOff",
            "payload"    => "Action=On&EP=01",
            "priority"   => 3,
        );

        $cmdPrepare = new AbeilleCmdPrepare;
        $result = '{"onoff":"1","dest":"Abeille3","priority":3,"addressMode":"02","address":"3B02","destinationEndpoint":"01","action":"01"}';
        
        $this->assertSame( $result, json_encode($cmdPrepare->procmsg($message, 1)) );
    }


}


?>

