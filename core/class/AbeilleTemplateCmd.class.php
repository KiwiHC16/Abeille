<?php

class AbeilleTemplateCmd
{

    /**
     * Will retour the Template (uniqId) used by a Cmd for specific Abeille
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

    /**
     * Will return the name of the cmd stored in the template
     * @param           uniqId Id du template
     * 
     * @return          Return return the name of the cmd stored in the template
     */
    public static function getNameFromTemplate( $uniqId ) {
        $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
        $keys = array_keys ( $jsonArray );
        if (count($keys)!=1) return 0;
        else return $jsonArray[$keys[0]]['name'];
    }

    /** TODO
     * Will return the timeout for the device stored in the template
     *
     * @return          Return return the timeout for the device stored in the template
     */
    public static function getTimeOutFromTemplate( $uniqId ) {
        $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
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
       $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
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
        $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
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


?>
