<?php
    
    // Analyse faite sur les messages Link Status: filtre Wireshark: (zbee_nwk.cmd.id == 0x08)
    include 'NetworkDefinition.php';
    
    class packets
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
            
            // Tools::deamonlog("debug", "Tools: loading file " . $jsonFile . " in " . $configDir);
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
            
            echo "Tools: nb line " . strlen($content) . "\n";
            
            var_dump( $json );
            
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
    
    
    
    $packets = packets::getDataFromJson('RouteRecord.json');
    var_dump( $packets );
    
    foreach ($packets as $key => $packet){
        //commandes
        if ( isset( $packet['_source']['layers']['zbee_nwk']['Command Frame: Route Record'] ) )
        {
            echo $packet['_source']['layers']['zbee_nwk']['zbee_nwk.src']."\n";
            echo $packet['_source']['layers']['zbee_nwk']['zbee_nwk.dst']."\n";
            var_dump( $packet['_source']['layers']['zbee_nwk']['Command Frame: Route Record'] );
            echo "-------------------------------------\n";
            echo $packet['_source']['layers']['zbee_nwk']['zbee_nwk.src'] . "->";
            
            foreach 
            
            echo $packet['_source']['layers']['zbee_nwk']['zbee_nwk.dst']."\n";
            echo "-------------------------------------\n";
            echo "-------------------------------------\n";
            
        }
    }
    
  
    ?>



