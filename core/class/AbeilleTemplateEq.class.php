<?php

class AbeilleTemplateEq {
    /**
     * Will retour the Template (uniqId) used by an Abeille
     * @param           logicalId of an Abeille
     *
     * @return          Return the Template (uniqId) used by an Abeille
     */
    public static function uniqIdUsedByAnAbeille( $logicalId ) {
        return Abeille::byLogicalId( $logicalId, 'Abeille', false )->getConfiguration('uniqId', '');
    }

    /**
     * Will collect all Abeille with a specific template (uniqId)
     * @param           uniqId identify the json template
     *
     * @return          Return all abeille with a specific template (uniqId)
     */
    public static function getEqLogicsByTemplateUniqId( $uniqId ) {

        $allAbeillesWithUniqId = Abeille::byTypeAndSearhConfiguration( 'Abeille', 'uniqId' );

        foreach ( $allAbeillesWithUniqId as $key=>$abeillesWithUniqId ) {
            if ( $abeillesWithUniqId->getConfiguration('uniqId', '') == $uniqId ) {
                $return[] = $abeillesWithUniqId;
            }
        }

        return $return;
    }

    /**
     * Will return the name o fthe device store n the template
     * @param           uniqId identify the json template
     *
     * @return          Return the name o fthe device store n the template
     */
    public static function getNameJeedomFromTemplate( $uniqId ) {
        $jsonArray = self::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            return $jsonArray[$key]["type"];
        }
    }

    /**
     * Will return the timeout for the device stored in the template
     * @param            uniqId identify the json template
     *
     * @return          Return return the timeout for the device stored in the template
     */
    public static function getTimeOutFromTemplate( $uniqId ) {
        $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            return $jsonArray[$key]["timeout"];
        }
    }

    /**
     * Will return the categories for the device stored in the template
     * @param           uniqId identify the json template
     *
     * @return          Return the categories (array) for the device stored in the template
     */
   public static function getCategorieFromTemplate( $uniqId ) {
       $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
       foreach ( $jsonArray as $key=>$data ) {
           return $jsonArray[$key]["category"];
       }
   }

    /**
     * Will return the configuration for the device stored in the template
     * @param           uniqId identify the json template
     * @param           item field defined in configuration part of the template
     *
     * @return          Return the configuration item for the device stored in the template if exist otherwise ''
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

    /**
     *
     *
     */
    public static function compareAllParamWithTemplateHtmlEnteteTable() {
        echo "<table>\n";
        echo '<tr><th style="text-align:center;">{{Paramètre}}</th><th style="text-align:center;">Jeedom</th><th style="text-align:center;">{{Modèle}}</th></tr>'."\n";
    }

    /**
     *
     *
     */
    public static function compareAllParamWithTemplateHtmlPiedTable() {
        echo "</table><br>\n";
    }

    /**
     * Will return the Commandes for the device stored in the template
     * @param           uniqId identify the json template
     *
     * @return          Return the Commandes (array) for the device stored in the template
     */
    public static function getCommandesFromTemplate( $uniqId ) {
        $jsonArray = AbeilleTemplateCommon::getJsonForUniqId( $uniqId );
        foreach ( $jsonArray as $key=>$data ) {
            return $jsonArray[$key]["Commandes"];
        }
    }

    public static function compareAllParamWithTemplate($abeille) {
        echo "<hr>\n";
        echo "<br>\n";
        echo "<h1>".$abeille->getHumanName()."</h1>\n";

        $logicalId = $abeille->getLogicalId();
        $uniqId = AbeilleTemplateEq::uniqIdUsedByAnAbeille($logicalId);

        self::compareAllParamWithTemplateHtmlEnteteTable();

        if ( $uniqId!='') {
            $items = array(
                'TimeOut'   => array( 'getTimeout', 'getTimeOutFromTemplate' ),
                'icone'     => array( 'getConfiguration', 'getConfigurationFromTemplate' ),
                'mainEP'    => array( 'getConfiguration', 'getConfigurationFromTemplate' ),
                'poll'      => array( 'getConfiguration', 'getConfigurationFromTemplate' ),
            );

            foreach ( $items as $item=>$fcts ) {
                $fct0 = $fcts[0];
                $fct1 = $fcts[1];

                if ($abeille->$fct0($item)==AbeilleTemplateEq::$fct1($uniqId, $item)) $color="green"; else $color="red";

                echo '<tr><td><span style="color:'.$color.'">'.$item.'<span></td>    <td style="text-align:center;">'    .$abeille->$fct0($item) . '</td><td style="text-align:center;">'    . AbeilleTemplateEq::$fct1($uniqId, $item)     . "</td></tr>\n";
            }
        }

        self::compareAllParamWithTemplateHtmlPiedTable();
    }

}

?>
