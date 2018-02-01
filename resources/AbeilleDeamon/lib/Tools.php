<?php

    class Tools{

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