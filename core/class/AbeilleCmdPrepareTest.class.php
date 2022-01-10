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

}


?>

