<?php

include_once __DIR__.'/../../core/class/Abeille.class.php';
include_once __DIR__.'/../../core/class/AbeilleTemplateCommon.class.php';
include_once __DIR__.'/../../core/class/AbeilleTemplateEq.class.php';
include_once __DIR__.'/../../core/class/AbeilleTemplateCmd.class.php';




/**
 * File to test functions on class AbeilleTemplateCmd and AbeilleTemplateCommon
 * 
 * CLI: php AbeilleTemplateCmdTest.php 104
 * Web: http://abeille/plugins/Abeille/core/php/AbeilleTemplateCmdTest.php?testToRun=104
 */

 // get test to run from cli or http
if (isset($argv[1])) $testToRun = $argv[1];
if (isset($_GET['testToRun'])) $testToRun = $_GET['testToRun'];


// '5c07c76620sdsfs8a7'
// Abeille1/3EFE
// $logicalId = 'Abeille1/3EFE';

// $abeille = Abeille::byLogicalId( $logicalId, 'Abeille', false );

// echo $abeille->getTimeout() . "\n";
// var_dump( $abeille );

// $abeilleTemplate = new AbeilleTemplateEq;

// $uniqId = $abeilleTemplate->uniqIdUsedByAnAbeille($logicalId);
// var_dump( $abeilleTemplate->getEqLogicsByTemplateUniqId('5c07c76620sdsfs8a7') );
// var_dump( $abeilleTemplate->getJsonFileNameForUniqId('5c07c76620sdsfs8a7') );
// var_dump( $abeilleTemplate->getJsonForUniqId( '5c07c76620sdsfs8a7' ) );
// var_dump( $abeilleTemplate->getNameJeedomFromTemplate('5c07c76620sdsfs8a7') );

// echo $abeille->getTimeout() . ' <-> ' . $abeilleTemplate->getTimeOutFromTemplate($uniqId) . "\n";



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
switch ($testToRun) {
    // Generic answer
    case '-help':
    case '--help':    
        echo "File to test functions on class AbeilleTemplateCmd and AbeilleTemplateCommon\n";
        echo "php AbeilleTemplateCmdTest.php testId\n";
    break;

    // AbeilleTemplateCommon Tests Area
    case 010:
        echo "File for uniqId:\n";
        echo AbeilleTemplateCommon::getJsonFileNameForUniqId($uniqIdEq)."\n";
    break;
    case 011:
        echo "List all uniqId.\n";
        var_dump(AbeilleTemplateCommon::getJsonForUniqId());
    break;
    case 012:
        echo "List all uniqId.\n";
        var_dump(AbeilleTemplateCommon::getAllUniqId());
    break;
    case 013:
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
        // Ce cas est utilisé pour afficher le modale Modele.
        echo "{{Affiche les differences trouvées entre les modèles et les équipements dans Abeille.}}<br>";
        echo "{{Une différence peut provenir d une modification faite par l utilisateur, ou une evolution du modele.}}<br>";
        echo "{{Difference ne veut pas dire que cela ne fonctionne pas.}}<br>";
        echo "{{Pour l instant c est un audit pour les developpeurs. L idée a terme estU de pouvoir mettre a jour les equipements si le modele evolue.}}<br>";
        echo "{{Actuellement le meilleur moyen pour mettre à jour un equipement si son modele change est de le supprimer de le re-créer.}}<br>";
        AbeilleTemplateCommon::compareTemplateHtmlEntete();
        foreach ( Abeille::byType('Abeille') as $abeille ) {
            // Don't proceed with Ruche as specific template
            if (strpos($abeille->getLogicalId(), 'Ruche')>1) continue;
            AbeilleTemplateEq::compareAllParamWithTemplate($abeille);
            AbeilleTemplateCmd::compareAllCmdWithTemplate($abeille);
        }
        AbeilleTemplateCommon::compareTemplateHtmlPiedDePage();
        
    break;
}



// TODO compare topic et payload

?>
