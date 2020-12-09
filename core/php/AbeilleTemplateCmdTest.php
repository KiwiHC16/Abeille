<?php

include_once __DIR__.'/../class/Abeille.class.php';
include_once __DIR__.'/../class/AbeilleTemplateCommon.class.php';
include_once __DIR__.'/../class/AbeilleTemplateEq.class.php';
include_once __DIR__.'/../class/AbeilleTemplateCmd.class.php';

/**
 * File to test functions on class AbeilleTemplateCmd and AbeilleTemplateCommon
 */

// '5c07c76620sdsfs8a7'
// Abeille1/3EFE

// $eqLogicalIdRef = 'Abeille1/3EFE';
// $logicalId = '0000-01-0004';

// $abeille = Abeille::byLogicalId( $logicalId, 'Abeille', false );

// echo $abeille->getTimeout() . "\n";
// var_dump( $abeille );

// $abeilleCmdTemplate = new AbeilleCmdTemplate;

// $uniqId = AbeilleTemplateCmd::uniqIdUsedByAnAbeilleCmd($eqLogicalIdRef, $logicalId);

// echo "uniqId: ".$uniqId."\n";
// var_dump( AbeilleCmdTemplate::getCmdByTemplateUniqId($uniqId) );

// var_dump( AbeilleTemplateCommon::getJsonFileNameForUniqId($uniqId) );

// var_dump( $abeilleTemplate->getJsonForUniqId( '5c07c76620sdsfs8a7' ) );
// var_dump( $abeilleTemplate->getNameJeedomFromTemplate('5c07c76620sdsfs8a7') );

// echo $abeille->getTimeout() . ' <-> ' . $abeilleTemplate->getTimeOutFromTemplate($uniqId) . "\n";

if (0) {
    var_dump(AbeilleTemplateCmd::getMainParamFromTemplate($uniqId, 'name'));
    var_dump(AbeilleTemplateCmd::getMainParamFromTemplate($uniqId, 'configuration'));
        
    var_dump(AbeilleTemplateCmd::getConfigurationFromTemplate($uniqId, 'visibilityCategory'));
    
}

$uniqId = '5c07c76620sdsfs8a7';

switch ($argv[1]) {
    case '-help':
    case '--help':    
        echo "File to test functions on class AbeilleTemplateCmd and AbeilleTemplateCommon\n";
        echo "php AbeilleTemplateCmdTest.php testId\n";
    break;

    case  8:
        echo "File for uniqId:\n";
        echo AbeilleTemplateCommon::getJsonFileNameForUniqId($uniqId)."\n";
    break;
    case  9:
        echo "List all uniqId.\n";
        var_dump(AbeilleTemplateCommon::getJsonForUniqId());
    break;
    case 10:
        echo "List all uniqId.\n";
        var_dump(AbeilleTemplateCommon::getAllUniqId());
    break;
    case 11:
        echo "Check if we have uniqId doublon dans tous les templates JSON.\n";
        AbeilleTemplateCommon::duplicatedUniqId();
    break;

}


// TODO compare topic et payload

?>
