<?php

include_once __DIR__.'/Abeille.class.php';

class AbeilleTemplate
{
    /**
     * Will update an  eqLogic based on a new version of the template
     *
     * @return          Does not return anything as all action on in the DB
     */
    public static function updateEqlogicFromNewTemplate( $abeilleLogicalId ) {
        // uniqId
        $templateUnitId = Abeille::byTypeAndSearhConfiguration( 'Abeille', 'uniqId' );
        echo $templateUnitId."\n";
    }

    public static function getEqLogicsByTemplateUniqId( $uniqId ) {
                // uniqId
                $allAbeillesWithUniqId = Abeille::byTypeAndSearhConfiguration( 'Abeille', 'uniqId' );

                foreach ( $allAbeillesWithUniqId as $key=>$abeillesWithUniqId ) {
                    echo $abeillesWithUniqId->getName()." -> ".$abeillesWithUniqId->getConfiguration('uniqId', '')."\n";
                    if ( $abeillesWithUniqId->getConfiguration('uniqId', '') == $uniqId ) {
                        echo "--->".$abeillesWithUniqId->getName()."\n";
                    }
                }
                // var_dump( $templateUnitId );

    }
}

// '5c07c76620sdsfs8a7'
// Abeille1/3EFE

$test = new AbeilleTemplate();

//$test->updateEqlogicFromNewTemplate();
$test->getEqLogicsByTemplateUniqId('5c07c76620sdsfs8a7');



?>
