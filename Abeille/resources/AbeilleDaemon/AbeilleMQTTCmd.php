<?php
    
    include("CmdToAbeille.php");  // contient processCmd()
    include("lib/phpMQTT.php");
    
    function procmsg($topic, $msg){
        echo "Msg Recieved: " . date("r") . " Topic: {$topic} =>\t$msg\n";
        
	// list($type, $address, $action) = split('[/.-]', $topic); split ne fonctionne plus avec php 7
	list($type, $address, $action) = explode('/', $topic); 
	//
        // echo "Type: ".$type."\n";
        // echo "Address: ".$address."\n";
        // echo "Action: ".$action."\n";
        
        if ( $type == "CmdAbeille" )
        {
            if ( $action == "Annonce" )
            {
                $Command = array("ReadAttributeRequest"=>"1",
                                 "address"=>$address,
                                 "clusterId"=>"0000",
                                 "attributeId"=>"0005"
                                 );
            }
            if ( $action == "OnOff" )
            {
                if ( $msg=="On" )       { $actionId="01"; }
                if ( $msg=="Off" )      { $actionId="00"; }
                if ( $msg=="Toggle" )   { $actionId="02"; }
                $Command = array("onoff"=>"1",
                                 "address"=>$address,
                                 "action"=>$actionId,
                                 "clusterId"=>"0006"
                                 );
            }
            if ( $action == "ReadAttributeRequest" )
            {
                $keywords = preg_split("/[=&]+/", $msg );
                $Command = array("ReadAttributeRequest"=>"1",
                                 "address"=>$address,
                                 "clusterId"=>$keywords[1],
                                 "attributeId"=>$keywords[3]
                                 );
            }
            if ( $action == "setLevel" )
            {
                $keywords = preg_split("/[=&]+/", $msg );
                $Command = array("setLevel"=>"1",
                                 "address"=>$address,
                                 "clusterId"=>"0008",
                                 "Level"=>intval($keywords[1]*255/100),
                                 "duration"=>$keywords[3]
                                 );
            }
            if ( $address == "Ruche" )
            {
                $keywords = preg_split("/[=&]+/", $msg );
                if ( count($keywords) == 1 )
                {
                $Command = array( $action=>$msg);
                }
                else{
                    $Command = array( $action=>$action,
                                     $keywords[0]=>$keywords[1],
                                     $keywords[2]=>$keywords[3],
                                     );
                }
            }
            
            print_r( $Command );
            processCmd( $Command );
        }
    }
    
    
    $server = "127.0.0.1";     // change if necessary
    $port = 1883;                     // change if necessary
    $username = "jeedom";                   // set your username
    $password = "jeedom";                   // set your password
    $client_id = "MQTTCmd"; // make sure this is unique for connecting to sever - you could use uniqid()
    $mqtt = new phpMQTT($server, $port, $client_id);
    
    
    if(!$mqtt->connect(true, NULL, $username, $password)) { exit(1); }
    
    $topics['CmdAbeille/#'] = array("qos" => 0, "function" => "procmsg");
    
    $mqtt->subscribe($topics, 0);
    
    while($mqtt->proc()){
        
    }
    
    $mqtt->close();
    
    
    ?>
