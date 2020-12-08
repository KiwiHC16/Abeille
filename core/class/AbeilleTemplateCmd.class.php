<?php

include_once __DIR__.'/Abeille.class.php';

class AbeilleCmdTemplate
{

    /**
     * Will retour the Template (uniqId) used by an Abeille
     * @param           eqLogicalIdRef  logicalId of the Abeille
     * @param           logicalId       logicalId of the Cmd
     * 
     * @return          Return retour the Template (uniqId) used by an Abeille
     */
    public static function uniqIdUsedByAnAbeilleCmd( $eqLogicalIdRef, $logicalId ) {
        // return AbeilleCmd::byEqLogicIdAndLogicalId( $eqLogicalId, $logicalId, 'Abeille', false )->getConfiguration('uniqId', '');
        $eqLogicalId = Abeille::byLogicalId( $eqLogicalIdRef, 'Abeille', false )->getId();
        return AbeilleCmd::byEqLogicIdAndLogicalId( $eqLogicalId, $logicalId, false )->getConfiguration('uniqId', '');
    }

    /**
     * Will collect all Cmd with a specific template (uniqId)
     *
     * @return          Return all Cmd with a specific template (uniqId)
     */
    public static function getCmdByTemplateUniqId( $uniqId ) {
        $return = array();
        $allCmdWithUniqId = AbeilleCmd::searchConfiguration( 'uniqId' );

        foreach ( $allCmdWithUniqId as $key=>$cmdWithUniqId ) {
            if ( $cmdWithUniqId->getConfiguration('uniqId', '') == $uniqId ) {
                $return[] = $cmdWithUniqId;
            }
        }

        return $return;
    }

    /** TODO
     * Will return the json file name for a template uniqId
     *
     * @return          Return the json file name for a template uniqId or -1 if not found
     */
    public static function getJsonFileNameForUniqId( $uniqId ) {
        $templateFiles = glob('/var/www/html/plugins/Abeille/core/config/devices/*/*.json'); 
        foreach( $templateFiles as $templateFile ) {
            $json = file_get_contents( $templateFile );
            if ( strpos($json, $uniqId) > 1 ) {
                return $templateFile;
            }
        }
        return -1;
    }

    /** TODO
     * Will return the json array for a template uniqId
     *
     * @return          Return the json array for a template uniqId or -1 if not found
     */
    public static function getJsonForUniqId( $uniqId ) {
        $jsonText = file_get_contents( self::getJsonFileNameForUniqId( $uniqId ));
        $jsonArray = json_decode($jsonText, true);
        return $jsonArray;
    }

    /** TODO
     * Will return the name o fthe device store n the template
     *
     * @return          Return the name o fthe device store n the template
     */
    public static function getNameJeedomFromTemplate( $uniqId ) {
        $jsonArray = self::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            return $jsonArray[$key]["nameJeedom"];
        }
    }

    /** TODO
     * Will return the timeout for the device stored in the template
     *
     * @return          Return return the timeout for the device stored in the template
     */
    public static function getTimeOutFromTemplate( $uniqId ) {
        $jsonArray = self::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            return $jsonArray[$key]["timeout"];
        }
    }

    /* TODO 
    * Will return the categories for the device stored in the template
    *
    * @return          Return return the categories (array) for the device stored in the template
    */
   public static function getCategorieFromTemplate( $uniqId ) {
       $jsonArray = self::getJsonForUniqId( $uniqId );
       foreach ( $jsonArray as $key=>$data ) {
           return $jsonArray[$key]["Categorie"];
       }
   }

    /* TODO
    * Will return the configuration for the device stored in the template
    *
    * @return          Return return the configuration item for the device stored in the template if exist otherwise ''
    */
    public static function getConfigurationFromTemplate( $uniqId, $item ) {
        $jsonArray = self::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            if (isset($jsonArray[$key]["configuration"][$item])) {
                return $jsonArray[$key]["configuration"][$item];
            }
            else {
                return '';
            }
            
        }
    }

    /* TODO
    * Will return the Commandes for the device stored in the template
    *
    * @return          Return return the Commandes (array) for the device stored in the template
    */
    public static function getCommandesFromTemplate( $uniqId ) {
        $jsonArray = self::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            return $jsonArray[$key]["Commandes"];
        }
    }
    

}

// '5c07c76620sdsfs8a7'
// Abeille1/3EFE

$eqLogicalIdRef = 'Abeille1/3EFE';
$logicalId = '0000-01-0004';

// $abeille = Abeille::byLogicalId( $logicalId, 'Abeille', false );

// echo $abeille->getTimeout() . "\n";
// var_dump( $abeille );

// $abeilleCmdTemplate = new AbeilleCmdTemplate;

$uniqId = AbeilleCmdTemplate::uniqIdUsedByAnAbeilleCmd($eqLogicalIdRef, $logicalId);

echo "uniqId: ".$uniqId."\n";
var_dump( AbeilleCmdTemplate::getCmdByTemplateUniqId($uniqId) );

// var_dump( $abeilleTemplate->getJsonFileNameForUniqId('5c07c76620sdsfs8a7') );
// var_dump( $abeilleTemplate->getJsonForUniqId( '5c07c76620sdsfs8a7' ) );
// var_dump( $abeilleTemplate->getNameJeedomFromTemplate('5c07c76620sdsfs8a7') );

// echo $abeille->getTimeout() . ' <-> ' . $abeilleTemplate->getTimeOutFromTemplate($uniqId) . "\n";


    


?>
