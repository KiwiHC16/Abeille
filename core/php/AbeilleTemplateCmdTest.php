<?php

include_once __DIR__.'/../class/Abeille.class.php';
include_once __DIR__.'/../class/AbeilleTemplateCommon.class.php';
include_once __DIR__.'/../class/AbeilleTemplateEq.class.php';
include_once __DIR__.'/../class/AbeilleTemplateCmd.class.php';

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

// Take Abeile one by one and check if Template value is identical
foreach ( Abeille::byType('Abeille') as $abeille ) {
    // Don't proceed with Ruche as specific template
    if (strpos($abeille->getLogicalId(), 'Ruche')>1) continue;

    echo "\nAbeille: ".$abeille->getName()."\n";
    foreach ( $abeille->getCmd() as $cmd ) {
        $uniqId = $cmd->getConfiguration( 'uniqId', -1 );
        if ( $uniqId == -1 ) {
            echo "This cmd doesn t have a uniqId, I can t identify it s template !\n";
            continue;
        }

        $item = 'name';
        $templateValue = AbeilleTemplateCmd::getMainParamFromTemplate($uniqId, $item);
        if ( $templateValue == -1 ) {
            echo "    ".$cmd->getName().": Error template not found for this parameter (".$uniqId."->".$item.") !\n";
            continue;
        }
        if ($cmd->getName() != $templateValue) {
            echo "    ".$uniqId.": ".$cmd->getName().' <-> '.$templateValue."\n";
            var_dump(AbeilleTemplateCommon::getJsonFileNameForUniqId( $uniqId ));
        }
    }
    
}




?>
