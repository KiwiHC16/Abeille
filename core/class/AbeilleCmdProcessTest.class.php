<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleCmdProcess.class.php';

class AbeilleCmdPrepareTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php AbeilleCmdProcess.class.php', $output, $retval);
        $this->assertSame( 1, count($output) );
        $this->assertSame( 0, $retval );
    }

}


?>

