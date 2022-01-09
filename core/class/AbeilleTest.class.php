<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './Abeille.class.php';

class AbeilleTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php Abeille.class.php', $output, $retval);
        $this->assertSame( 0, count($output) );
        $this->assertSame( 0, $retval );
    }

    function test_volt2pourcent() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $this->assertSame(    0, Abeille::volt2pourcent(2700) );
        $this->assertSame( 60.0, Abeille::volt2pourcent(3000) );
        $this->assertSame(  100, Abeille::volt2pourcent(4000) );
    }

    function test_health() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $result = '[{"test":"Ports: ","result":"\/dev\/ttyUSB0nonenonenonenonenonenonenonenonenone","advice":"Ports utilis\u00e9s","state":true}]';
        $this->assertSame( $result, json_encode(Abeille::health()) );
    }

    function test_deamon_info() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $result = '{"state":"nok","launchable":"ok","launchable_message":""}';
        $this->assertSame( $result, json_encode(Abeille::deamon_info()) );
    }

}


?>

