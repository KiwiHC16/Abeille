<?php

include_once __DIR__.'../class/Abeille.class.php';
include_once __DIR__.'../class/AbeilleTemplateCommon.class.php';
include_once __DIR__.'../class/AbeilleTemplateEq.class.php';
include_once __DIR__.'../class/AbeilleTemplateCmd.class.php';


// '5c07c76620sdsfs8a7'
// Abeille1/3EFE
// $logicalId = 'Abeille1/3EFE';

// $abeille = Abeille::byLogicalId( $logicalId, 'Abeille', false );

// echo $abeille->getTimeout() . "\n";
// var_dump( $abeille );

$abeilleTemplate = new AbeilleTemplateEq;

// $uniqId = $abeilleTemplate->uniqIdUsedByAnAbeille($logicalId);
// var_dump( $abeilleTemplate->getEqLogicsByTemplateUniqId('5c07c76620sdsfs8a7') );
// var_dump( $abeilleTemplate->getJsonFileNameForUniqId('5c07c76620sdsfs8a7') );
// var_dump( $abeilleTemplate->getJsonForUniqId( '5c07c76620sdsfs8a7' ) );
// var_dump( $abeilleTemplate->getNameJeedomFromTemplate('5c07c76620sdsfs8a7') );

// echo $abeille->getTimeout() . ' <-> ' . $abeilleTemplate->getTimeOutFromTemplate($uniqId) . "\n";

$abeilles = Abeille::byType('Abeille');
foreach ( $abeilles as $abeille ) {
    echo $abeille->getName(). " : "; 
    $logicalId = $abeille->getLogicalId();

    $uniqId = $abeilleTemplate->uniqIdUsedByAnAbeille($logicalId);

    if ( $uniqId!='') {
        echo '   '."TimeOut :".$abeille->getTimeout() . ' <-> ' . $abeilleTemplate->getTimeOutFromTemplate($uniqId) . "\n";
        echo '   '."icone   :".$abeille->getConfiguration('icone') . ' <-> ' . $abeilleTemplate->getConfigurationFromTemplate($uniqId, 'icone') . "\n";
        echo '   '."mainEP  :".$abeille->getConfiguration('mainEP') . ' <-> ' . $abeilleTemplate->getConfigurationFromTemplate($uniqId, 'mainEP') . "\n";
        echo '   '."poll    :".$abeille->getConfiguration('poll') . ' <-> ' . $abeilleTemplate->getConfigurationFromTemplate($uniqId, 'poll') . "\n";
    }
    else {
        // echo $abeille->getTimeout() . ' <-> ' . "\n";
    }
    

}
?>
