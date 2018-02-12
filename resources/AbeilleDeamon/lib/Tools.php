<?php

    class Tools{

        /**
         * Convert log level string to number to compare more easily.
         *
         * @param $loglevel
         * @return int
         */
        public static function getNumberFromLevel($loglevel){
            if (strcasecmp($loglevel, "NONE")==0){$iloglevel=0;}
            if (strcasecmp($loglevel,"ERROR")==0){$iloglevel=1;}
            if (strcasecmp($loglevel,"WARNING")==0){$iloglevel=2;}
            if (strcasecmp($loglevel,"INFO")==0){$iloglevel=3;}
            if (strcasecmp($loglevel,"DEBUG")==0){$iloglevel=4;}
            return $iloglevel;
        }

        /***
         * if loglevel is lower/equal than the app requested level then message is written
         *
         * @param string $loglevel
         * @param string $message
         */
        public static function deamonlog($loglevel='NONE',$loggerName='Tools',$message =''){
            if (strlen($message)>=1  &&  Tools::getNumberFromLevel($loglevel) <= Tools::getNumberFromLevel($GLOBALS["requestedlevel"]) ) {
                fwrite(STDOUT, $loggerName.' '.date("Y-m-d H:i:s").'['.strtoupper($GLOBALS["requestedlevel"]).']'.$message . PHP_EOL); ;
            }
        }

        public static function getJSonConfigFiles($jsonFile = null){

            $configDir=dirname(__FILE__) . '/../../../core/config/';

            log::add("Abeille","debug","Tools: loading file ".$jsonFile." in ".$configDir);
            $confFile=$configDir.$jsonFile;
            //$confFile=$configDir."AbeilleObjetDefinition.json";

            //file exists ?
            if (!is_file( $confFile)) {
                log::add('Abeille','error',$confFile.' not found.' );
                return;
            }
            // is valid json
            $content = file_get_contents($confFile);
            if (!is_json($content)) {
                log::add('Abeille','error',$confFile.' is not a valid json.' );
                return;
            }

            $json = json_decode($content,true);

            log::add("Abeille","debug","Tools: nb line ".strlen($content));

            return $json;
        }

    }

    ?>