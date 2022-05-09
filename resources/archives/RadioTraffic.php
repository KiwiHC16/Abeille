<?php
    
    // Analyse faite sur les messages Link Status: filtre Wireshark: (zbee_nwk.cmd.id == 0x08)
    include 'NetworkDefinition.php';
    
    class Traffics
    {
        
        
        /**
         * return the config list from a file located in core/config directory
         *
         * @param null $jsonFile
         * @return mixed|void
         */
        public static function getDataFromJson($jsonFile = null)
        {
            
            // $configDir = dirname(__FILE__) . '/../../../core/config/';
            $fileDir = './';
            
            // AbeilleTools::deamonlog("debug", "Tools: loading file " . $jsonFile . " in " . $configDir);
            $confFile = $fileDir . $jsonFile;
            
            //file exists ?
            if (!is_file($confFile)) {
                echo 'file not found.';
                return;
            }
            // is valid json
            $content = file_get_contents($confFile);
            /*
             if (!is_json($content)) {
             echo 'file is not a valid json.';
             return;
             }
             */
            $json = json_decode($content, true);
            
            // echo "Tools: nb line " . strlen($content) . "\n";
            
            return $json;
        }
    }
    
    function printTable($arr) {
        foreach ($arr as $key => $subarr) {
            foreach ($subarr as $subkey => $subvalue) {
                echo $subvalue." ";
            }
            echo "\n";
        }
        return $out;
    }
    
    $listOfNE = array();
    $trafficsMap = array();
    $listOfMACTraffic[] = array();
    $listOfNWKTraffic[] = array();
    
    
    
    $traffics = Traffics::getDataFromJson('essai.json');
    
    // echo "items:\n";
    // print_r($traffics);
    
    foreach ($traffics as $key => $NE){
        //commandes
        if ( isset( $NE['_source']['layers']['wpan']['wpan.dst16'] ) )
        {
            if ( $NE['_source']['layers']['wpan']['wpan.dst16']!="0x0000ffff" )
            {
                // print_r($NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']);
                
                // echo "src: " . $NE['_source']['layers']['zbee_nwk']['zbee_nwk.src'] . "\n";
                $srcMAC = substr( $NE['_source']['layers']['wpan']['wpan.src16'], -4 );
                $dstMAC = substr( $NE['_source']['layers']['wpan']['wpan.dst16'], -4 );
                
                $listOfMACTraffic[] = array( $srcMAC => $dstMAC );
                
                if ( isset($NE['_source']['layers']['zbee_nwk']['zbee_nwk.dst']) ) {
                    if ( $NE['_source']['layers']['zbee_nwk']['zbee_nwk.dst']!="0x0000fffc" ){
                        $srcNWK = substr( $NE['_source']['layers']['zbee_nwk']['zbee_nwk.src'], -4 );
                        $dstNWK = substr( $NE['_source']['layers']['zbee_nwk']['zbee_nwk.dst'], -4 );
                        
                        $listOfNWKTraffic[] = array( $srcNWK => $dstNWK );
                        
                        $Packet[] = array( array( $srcMAC, $dstMAC ), array( $srcMAC, $dstNWK ) );
                    }
                }
                
                
                
                if (0) {
                    // echo "src IEEE: " . $NE['_source']['layers']['zbee_nwk']['zbee_nwk.src64'] . "\n";
                    
                    // echo "dst: " . $NE['_source']['layers']['zbee_nwk']['zbee_nwk.dst'] . "\n";
                    
                    // print_r($NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']);
                    
                    $link_status = $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status'];
                    
                    // echo "Link_Status: \n";
                    // print_r( $link_status );
                    
                    foreach ($link_status as $key => $link){
                        
                        if ( substr( $key, 0, 4) == "Link" )
                        {
                            //echo "Target: " . $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']['zbee_nwk.cmd.link.address'] . "\n";
                            
                            // $target = substr( $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']['zbee_nwk.cmd.link.address'], -4 );
                            $target = substr( $link['zbee_nwk.cmd.link.address'], -4 );
                            $listOfNE[] = $target;
                            
                            // echo "->".substr( $key, 0, 4)."<-\n";
                            //echo "Incoming Cost: " . $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']['zbee_nwk.cmd.link.incoming_cost'] . "\n";
                            // echo "Incoming Cost: " . $link['zbee_nwk.cmd.link.incoming_cost'] . "\n";
                            //echo "Outgoing Cost: " . $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']['zbee_nwk.cmd.link.outgoing_cost'] . "\n";
                            // echo "Incoming Cost: " . $link['zbee_nwk.cmd.link.outgoing_cost'] . "\n";
                            
                            // $trafficsMap[$src][$target]['In'] = $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']['zbee_nwk.cmd.link.incoming_cost'];
                            $trafficsMap[$src][$target]['In'] = $link['zbee_nwk.cmd.link.incoming_cost'];
                            // $trafficsMap[$src][$target]['Out'] = $NE['_source']['layers']['zbee_nwk']['Command Frame: Link Status']['Link 1']['zbee_nwk.cmd.link.outgoing_cost'];
                            $trafficsMap[$src][$target]['Out'] = $link['zbee_nwk.cmd.link.outgoing_cost'];
                        }
                    }
                }
            }
        }
    }
    
    // print_r( $listOfMACTraffic );
    
    // print_r( $listOfNWKTraffic );
    
    if (0) {
        // print_r( $trafficsMap );
        // print_r( $listOfNE );
        $listOfNE = array_unique( $listOfNE );
        sort( $listOfNE );
        // print_r( $listOfNE );
        
        echo "\n";
        
        echo "In   ";
        foreach ( $listOfNE as $NE_Trg )
        {
            echo $NE_Trg." ";
        }
        echo "\n";
        foreach ( $listOfNE as $NE_Src )
        {
            echo $NE_Src."";
            foreach ( $listOfNE as $NE_Trg )
            {
                if ( isset($trafficsMap[$NE_Src][$NE_Trg]['In']) ) { echo "    ".$trafficsMap[$NE_Src][$NE_Trg]['In']; } else { echo "     "; }
            }
            echo " ".$knownNE[$NE_Src]."\n";
        }
        
        echo "\n";
        
        
        echo "Out  ";
        foreach ( $listOfNE as $NE_Trg )
        {
            echo $NE_Trg." ";
        }
        echo "\n";
        foreach ( $listOfNE as $NE_Src )
        {
            echo $NE_Src."";
            foreach ( $listOfNE as $NE_Trg )
            {
                if ( isset($trafficsMap[$NE_Src][$NE_Trg]['Out']) ) { echo "    ".$trafficsMap[$NE_Src][$NE_Trg]['Out']; } else { echo "     "; }
            }
            echo " ".$knownNE[$NE_Src]."\n";
        }
        
        print_r( $trafficsMap ); echo "\n";
    }
    ?>



