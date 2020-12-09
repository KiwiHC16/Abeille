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

// Need to change those value base on your installation
$uniqIdEq           = '5c07c76620sdsfs8a7';   // device: WS2812_light_controller.json
$uniqIdCmd          = '5c07c76623554';        // Cmd: etat.json
$eqLogicalIdRef     = 'Abeille1/3EFE';
$logicalId          = '0000-01-0005';
$param              = 'name';
$item               = 'repeatEventManagement';

// Process the test requested
switch ($argv[1]) {
    // Generic answer
    case '-help':
    case '--help':    
        echo "File to test functions on class AbeilleTemplateCmd and AbeilleTemplateCommon\n";
        echo "php AbeilleTemplateCmdTest.php testId\n";
    break;

    // AbeilleTemplateCommon Tests Area
    case 10:
        echo "File for uniqId:\n";
        echo AbeilleTemplateCommon::getJsonFileNameForUniqId($uniqIdEq)."\n";
    break;
    case 11:
        echo "List all uniqId.\n";
        var_dump(AbeilleTemplateCommon::getJsonForUniqId());
    break;
    case 12:
        echo "List all uniqId.\n";
        var_dump(AbeilleTemplateCommon::getAllUniqId());
    break;
    case 12:
        echo "Check if we have uniqId doublon dans tous les templates JSON.\n";
        AbeilleTemplateCommon::duplicatedUniqId();
    break;

    // AbeilleTemplateCmd Tests Area
    case 100:
        echo "Return the Template (uniqId) used by a Cmd for specific Abeille:\n";
        echo AbeilleTemplateCmd::uniqIdUsedByAnAbeilleCmd($eqLogicalIdRef, $logicalId)."\n";
    break;
    case 101:
        echo "Return all Cmd with a specific template (uniqId):\n";
        var_dump(AbeilleTemplateCmd::getCmdByTemplateUniqId($uniqIdCmd));
    break;
    case 102:
        echo "Return the 'main' parameter for the device stored in the template:\n";
        var_dump(AbeilleTemplateCmd::getMainParamFromTemplate( $uniqIdCmd, $param) );
    break;
    case 103:
        echo "Return the ".$item." parameter for the device stored in the template:\n";
        var_dump(AbeilleTemplateCmd::getConfigurationFromTemplate( $uniqIdCmd, $item ) );
    break;
    case 104:
        echo "Will compare cmd from Abeille to their template and will echo the result during execution.\n";
        AbeilleTemplateCmd::compareAllCmdWithTemplate();
    break;
}



// TODO compare topic et payload

?>
