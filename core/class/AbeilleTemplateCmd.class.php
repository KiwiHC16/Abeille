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
     * Will return the 'main' parameter for the device stored in the template
     * @param           uniqId Id du template que l selectionne
     * @param           param un des paramatres principaux comme: name, isVisible, order, type, subtype, invertBinary, template...
     *
     * @return          Return return the 'main' parameter for the device stored in the template
     */
    public static function getMainParamFromTemplate( $uniqId, $param ) {
        $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
        $keys = array_keys ( $jsonArray );
        if (count($keys)!=1) return 0;
        if (!isset($jsonArray[$keys[0]][$param])) return 0;
        return $jsonArray[$keys[0]][$param];
    }

    /**
    * Will return the configuration item for the device stored in the template
    * @param            uniqId of the template that we want to use
    * @param            item in the configuration that we want 
    *
    * @return          Return return the configuration item for the device stored in the template if exist otherwise ''
    */
    public static function getConfigurationFromTemplate( $uniqId, $item ) {
        $configurationArray = self::getMainParamFromTemplate( $uniqId, 'configuration' );
        
        if (isset($configurationArray[$item])) return $configurationArray[$item];
        else return '';
            
        
    }

}


?>
