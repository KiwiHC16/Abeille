<?php
    
    /***
     * CheckAlive
     *
     * Look at lastCommunication and set an alarme if is too old.
     *
     */
    
    // Lib needed
    require_once dirname(__FILE__)."/../../../../core/php/core.inc.php";
    
    // The reverse transformation
    // https://en.wikipedia.org/wiki/SRGB
    if (0) {
    $R=0;
    $G=0;
    $B=255;
    
    $a = 0.055;
    
    // are in the range 0 to 1. (A range of 0 to 255 can simply be divided by 255.0).
    $Rsrgb = $R / 255;
    $Gsrgb = $G / 255;
    $Bsrgb = $B / 255;
    
    if ( $Rsrgb <= 0.04045 ) { $Rlin = $Rsrgb/12.92; } else { $Rlin = pow( ($Rsrgb+$a)/(1+$a), 2.4); }
    if ( $Gsrgb <= 0.04045 ) { $Glin = $Gsrgb/12.92; } else { $Glin = pow( ($Gsrgb+$a)/(1+$a), 2.4); }
    if ( $Bsrgb <= 0.04045 ) { $Blin = $Bsrgb/12.92; } else { $Blin = pow( ($Bsrgb+$a)/(1+$a), 2.4); }
    
    $X = 0.4124 * $Rlin + 0.3576 * $Glin + 0.1805 *$Blin;
    $Y = 0.2126 * $Rlin + 0.7152 * $Glin + 0.0722 *$Blin;
    $Z = 0.0193 * $Rlin + 0.1192 * $Glin + 0.9505 *$Blin;
    
    if ( ($X + $Y + $Z)!=0 ) {
        $x = $X / ( $X + $Y + $Z );
        $y = $Y / ( $X + $Y + $Z );
    }
    else {
        echo "Can t do the convertion.";
    }
    
    $x = $x*255*255;
    $y = $y*255*255;
    
    echo "x: ".dechex($x)." y: ".dechex($y)."\n";
    }
    
    $address="799e";
    $abeille = Abeille::byLogicalId('Abeille/'.$address,'Abeille');
    // var_dump( $abeille );
    
    $xCmd = $abeille->getCmd('info', '0300-0003');
    // var_dump( $xCmd );
    
    $yCmd = $abeille->getCmd('info', '0300-0004');
    // var_dump( $yCmd );
    
    $Ycmd = $abeille->getCmd('info', '0008-0000');
    
    $x = $xCmd->execCmd();
    $y = $yCmd->execCmd();
    $Y = $Ycmd->execCmd();
    
    echo "x: ".$x." y: ".$y."\n";
    
    // The forward transformation
    // https://en.wikipedia.org/wiki/SRGB
    
    // xyZ, il faut d'abord les transformer en valeurs CIE XYZ
    $x = $x / 255 /255;
    $y = $y / 255 /255;
    $Y = $Y / 100;
    echo "x: ".$x." y: ".$y."\n";
    
    $Y = $Y;
    $X = $Y*$x/$y;
    $Z = $Y*(1-$x-$y)/$y;
    echo "X: ".$X." Y: ".$Y." Z: ".$Z."\n";
    
    $Rlin =  3.2406 * $X - 1.5372 * $Y - 0.4986 *$Z;
    $Glin = -0.9689 * $X + 1.8758 * $Y + 0.0415 *$Z;
    $Blin =  0.0557 * $X - 0.2040 * $Y + 1.0570 *$Z;
    
    $a = 0.055;
    
    if ( $Rlin <= 0.0031308 ) { $Rsrgb = $Rlin*12.92; } else { $Rsrgb = (1+$a)*pow($Rlin, 1/2.4); }
    if ( $Glin <= 0.0031308 ) { $Gsrgb = $Glin*12.92; } else { $Gsrgb = (1+$a)*pow($Glin, 1/2.4); }
    if ( $Blin <= 0.0031308 ) { $Bsrgb = $Blin*12.92; } else { $Bsrgb = (1+$a)*pow($Blin, 1/2.4); }
    
    // are in the range 0 to 1. (A range of 0 to 255 can simply be divided by 255.0).
    $Rsrgb = $Rsrgb * 255;
    $Gsrgb = $Gsrgb * 255;
    $Bsrgb = $Bsrgb * 255;
    
    echo "Rsrgb: ".$Rsrgb." Gsrgb: ".$Gsrgb." Bsrgb: ".$Bsrgb."\n";
    
    // deamonlog('debug', 'setRouge '.$Rsrgb.' - '.$Gsrgb.' - '.$Bsrgb);
    
    /*
    $cmdVert = $abeille->getCmd('info', 'colorVert');
    $cmdBleu = $abeille->getCmd('info', 'colorBleu');
    
    $vertLevel = $cmdVert->execCmd();
    $bleuLevel = $cmdBleu->execCmd();
    */
    ?>
