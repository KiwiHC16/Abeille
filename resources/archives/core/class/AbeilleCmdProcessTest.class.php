<?php

// Depuis le shell
// phpunit delestageTest.class.php

use PHPUnit\Framework\TestCase;

require_once './AbeilleCmdProcess.class.php';

// Dummy function to avoid to find the right one which is not needed for those tests
function cmdLog($loglevel = 'NONE', $message = "", $isEnable = 1) {
}

class AbeilleCmdProcessKiwi extends AbeilleCmdProcess{
    function addCmdToQueue2($priority = PRIO_NORM, $net = '', $cmd = '', $payload = '', $addr = '', $addrMode = null) {
        $this->result =  array( $priority, $net, $cmd, $payload, $addr, $addrMode);
    }
}



class AbeilleCmdProcessTest extends TestCase {

    function test_phpSyntax() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        exec('php AbeilleCmdProcess.class.php', $output, $retval);
        $this->assertSame( 1, count($output) );
        $this->assertSame( 0, $retval );
    }

    function test_checkRequiredParams() {
        fwrite(STDOUT, "\n\n" . __METHOD__ );
        $required = ['addr'];
        $Command_Ok  = array( 'addr'=>'1234' );
        $Command_NOk = array( 'zozo'=>'1234' );

        $this->assertSame( true , AbeilleCmdProcess::checkRequiredParams($required, $Command_Ok) );
        $this->assertSame( false, AbeilleCmdProcess::checkRequiredParams($required, $Command_NOk) );
    }

    function test_sliderToHex() {
        // Called to convert '#sliderXX#'
        $sliderVal = "#slider34#";
        $type = '28';
        $abeilleCmdProcess = new AbeilleCmdProcess;
        $this->assertSame( '22', $abeilleCmdProcess->sliderToHex($sliderVal, $type));
    }

    function test_processCmd() {
        $test = new AbeilleCmdProcessKiwi;

        // Test initial checks
        $Command = null;
        $this->assertSame( null, $test->processCmd($Command));

        $Command = "Why not";
        $this->assertSame( null, $test->processCmd($Command));

        $Command = array();

        // Test commands
        $Command = array( "dest" => "Abeille1", 'PDM' =>"", 'req' => "E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE");
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","8300","00","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'PDM' =>"", 'req' => "E_SL_MSG_PDM_EXISTENCE_RESPONSE", 'recordId'=>"" );
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","8208","000000","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'abeilleList' => "");
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","0015","","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'name' => 'setZgLed', 'value' => 1) ;
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","0018","01","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'name' => 'setZgLed', 'value' => 0) ;
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","0018","00","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'setCertificationCE' => "") ;
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","0019","01","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'setCertificationFCC' => "") ;
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","0019","02","",null]', json_encode($test->result) );

        $Command = array( "dest" => "Abeille1", 'TxPower' => "11") ;
        $test->processCmd($Command);
        $this->assertSame( '[4,"Abeille1","0806","11","",null]', json_encode($test->result) );

        // ..... need to continue .....

    }
}


?>

