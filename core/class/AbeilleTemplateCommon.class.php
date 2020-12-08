<?php

class AbeilleTemplateCommon
{

    /**
     * Will return the json file name for a template uniqId
     *
     * @return          Return the json file name for a template uniqId or -1 if not found
     */
    public static function getJsonFileNameForUniqId( $uniqId ) {
        $templateFiles = glob('/var/www/html/plugins/Abeille/core/config/devices/*/*.json'); 
        foreach( $templateFiles as $templateFile ) {
            $json = file_get_contents( $templateFile );
            if ( strpos($json, '"'.$uniqId.'"') > 1 ) {
                return $templateFile;
            }
        }
        return -1;
    }

    /**
     * Will return the json array for a template uniqId
     *
     * @return          Return the json array for a template uniqId or -1 if not found
     */
    public static function getJsonForUniqId( $uniqId ) {
        $file = self::getJsonFileNameForUniqId( $uniqId );
        if ( $file == -1 ) return -1;
        $jsonText = file_get_contents( $file );
        $jsonArray = json_decode($jsonText, true);
        return $jsonArray;
    }

    /**
     * Will return all uniqId
     *
     * @return          Return return all uniqId
     */
    public static function getAllUniqId( ) {
        $uniqIdList = array();
        $templateFiles = glob('/var/www/html/plugins/Abeille/core/config/devices/*/*.json');
        foreach( $templateFiles as $templateFile ) {
            $jsonText = file_get_contents( $templateFile );
            $jsonArray = json_decode($jsonText, true);
            if (!isset($jsonArray)) continue;
            $keys = array_keys ( $jsonArray );
            if (count($keys)!=1) { 
                echo "Skipping file: ".$templateFile." as can t read it.\n";
                continue;
            }
            $key = $keys[0];
            if (isset($jsonArray[$key]['configuration'])) {
                if (isset($jsonArray[$key]['configuration']['uniqId'])) 
                    $uniqIdList[] = $jsonArray[$key]['configuration']['uniqId'];
                else 
                    echo "File: ".$templateFile." has no uniqId.\n";
            }
            else {
                echo "Configuration chapter not set in json.\n";
            }
        }
        return $uniqIdList;
    }

    /**
    * Will return all uniqId duplicated in the template
    *
    * @return          Return return all uniqId duplicated
    */
    public static function duplicatedUniqId() {
        $doublon = array_count_values (AbeilleTemplateCommon::getAllUniqId());
        foreach ($doublon as $uniqId=>$nb) {
            if ($nb>1) {
                echo $uniqId." -> ".$nb."\n";
            }
        }
    }
}

?>
