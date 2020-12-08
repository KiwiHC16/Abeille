<?php

include_once __DIR__.'../class/Abeille.class.php';
include_once __DIR__.'../class/AbeilleTemplateCommon.class.php';
include_once __DIR__.'../class/AbeilleTemplateEq.class.php';
include_once __DIR__.'../class/AbeilleTemplateCmd.class.php';

// '5c07c76620sdsfs8a7'
// Abeille1/3EFE

$eqLogicalIdRef = 'Abeille1/3EFE';
$logicalId = '0000-01-0004';

// $abeille = Abeille::byLogicalId( $logicalId, 'Abeille', false );

// echo $abeille->getTimeout() . "\n";
// var_dump( $abeille );

// $abeilleCmdTemplate = new AbeilleCmdTemplate;

$uniqId = AbeilleTemplateCmd::uniqIdUsedByAnAbeilleCmd($eqLogicalIdRef, $logicalId);

echo "uniqId: ".$uniqId."\n";
// var_dump( AbeilleCmdTemplate::getCmdByTemplateUniqId($uniqId) );

var_dump( AbeilleTemplateCommon::getJsonFileNameForUniqId($uniqId) );

// var_dump( $abeilleTemplate->getJsonForUniqId( '5c07c76620sdsfs8a7' ) );
// var_dump( $abeilleTemplate->getNameJeedomFromTemplate('5c07c76620sdsfs8a7') );

// echo $abeille->getTimeout() . ' <-> ' . $abeilleTemplate->getTimeOutFromTemplate($uniqId) . "\n";


    


?>
