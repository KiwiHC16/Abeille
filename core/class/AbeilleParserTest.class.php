<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleParser.class.php';

class AbeilleParserTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php AbeilleParser.class.php', $output, $retval);
        $this->assertSame( 0, count($output) );
        $this->assertSame( 0, $retval );
    }

}


?>

