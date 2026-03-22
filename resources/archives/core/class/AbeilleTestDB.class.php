<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './Abeille.class.php';

class AbeilleTestDB extends TestCase {

 
    function test_health() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $result = '[{"test":"Ports: ","result":"\/dev\/ttyUSB0nonenonenonenonenonenonenonenonenone","advice":"Ports utilis\u00e9s","state":true}]';
        $this->assertSame( $result, json_encode(Abeille::health()) );
    }

    function test_deamon_info() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $result = '{"state":"ok","launchable":"ok","launchable_message":""}';
        $this->assertSame( $result, json_encode(Abeille::deamon_info()) );
    }

    function test_getIEEE() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );

        $address = "Abeille1/81F6";
        $IEEE_Result  = "14B457FFFE79EBA9";

        $abeille = new Abeille;

        $this->assertSame( $IEEE_Result, $abeille->getIEEE($address) );
    }

    function test_getEqFromIEEE() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );

        $IEEE_Ok  = "14B457FFFE79EBA9";
        $IEEE_NOk = "0000000000000000";

        $abeille = new Abeille;

        $this->assertSame( "Ampoule1", $abeille->getEqFromIEEE($IEEE_Ok)->getName() );
        $this->assertSame( null, $abeille->getEqFromIEEE($IEEE_NOk) );
    }

    function test_checkInclusionStatus() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $destOk = "Abeille1";
        $detNok = "AbeilleY";

        $abeille = new Abeille;

        $this->assertSame( 0, $abeille->checkInclusionStatus($destOk) );
        $this->assertSame(-1, $abeille->checkInclusionStatus($detNok) );
    }

    function test_createRuche() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $dest = "AbeilleX";

        $abeille = new Abeille;

        $this->assertSame( "1303", $abeille->byLogicalId("Abeille1/0000","Abeille")->getId() );
        $this->assertSame( false, $abeille->byLogicalId($dest."/0000","Abeille") );
        $abeille->createRuche($dest);
        $this->assertSame( "Ruche-".$dest, $abeille->byLogicalId($dest."/0000","Abeille")->getName() );
    }
}


?>

