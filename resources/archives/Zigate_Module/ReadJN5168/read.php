<?php
/*

 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 0; ID : 00; Short Addr : 873a (1 fois); IEEE Addr: 00158d0001215781 (1 fois); Power Source (0:battery - 1:AC): 00; Link Quality: 156
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 1; ID : 01; Short Addr : 8c09 (1 fois); IEEE Addr: 00158d0001d6c052 (8 fois); Power Source (0:battery - 1:AC): 00; Link Quality: 81
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 2; ID : 03; Short Addr : 404c (1 fois); IEEE Addr: 00158d0001d5c421 (5 fois); Power Source (0:battery - 1:AC): 00; Link Quality: 139
 
 Not found (Droite) => AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 3; ID : 32; Short Addr : 36a6; IEEE Addr: 000B57fffe952a69; Power Source (0:battery - 1:AC): 01; Link Quality: 67
 
 Short Not Found (T7) => AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 4; ID : 33; Short Addr : 2096; IEEE Addr: 000B57fffe490C2a (2 fois); Power Source (0:battery - 1:AC): 01; Link Quality: 36
 
 Short Not Found (Lampe Bureau) => AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 5; ID : 34; Short Addr : a714; IEEE Addr: 00158d0001dedc72 (3 fois); Power Source (0:battery - 1:AC): 01; Link Quality: 188
 
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 6; ID : 35; Short Addr : c9f9; IEEE Addr: 000B57fffe88af72; Power Source (0:battery - 1:AC): 01; Link Quality: 112
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 7; ID : 36; Short Addr : 70fb; IEEE Addr: 000B57fffe3a0E7c; Power Source (0:battery - 1:AC): 01; Link Quality: 44
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 8; ID : 37; Short Addr : e4c0; IEEE Addr: 000B57fffed2af6a; Power Source (0:battery - 1:AC): 01; Link Quality: 80
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 9; ID : 38; Short Addr : db83; IEEE Addr: 00158d000183afeb; Power Source (0:battery - 1:AC): 01; Link Quality: 68
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 10; ID : 39; Short Addr : a0da; IEEE Addr: 000B57fffe3a563b; Power Source (0:battery - 1:AC): 01; Link Quality: 47
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 11; ID : 3a; Short Addr : 41c0; IEEE Addr: 00158d000183af7b; Power Source (0:battery - 1:AC): 01; Link Quality: 76
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 12; ID : 3b; Short Addr : d204; IEEE Addr: 000B57fffec53819; Power Source (0:battery - 1:AC): 01; Link Quality: 42
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 13; ID : 3c; Short Addr : 82a6; IEEE Addr: 000B57fffe4bab6a; Power Source (0:battery - 1:AC): 01; Link Quality: 149
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 14; ID : 3d; Short Addr : 60fb; IEEE Addr: 000B57fffe8dbb1a; Power Source (0:battery - 1:AC): 01; Link Quality: 67
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 15; ID : 3e; Short Addr : 6766; IEEE Addr: 000B57fffe3025ad; Power Source (0:battery - 1:AC): 01; Link Quality: 36
 AbeilleParser 2018-09-21 16:13:57[DEBUG]Abeille i: 16; ID : 3f; Short Addr : b807; IEEE Addr: 000B57fffe8e083c; Power Source (0:battery - 1:AC): 01; Link Quality: 34
 */
    
$EEPROM = array();
    
$handle = @fopen("EEPROMRead6ProductionJeedomZwave.bin", "r");
if ($handle) {
    while (!feof($handle)) {
        $hex = bin2hex(fread ($handle , 1 ));
        array_push($EEPROM, $hex);
        print $hex."\n";
    }
    fclose($handle);

}
    echo "\n--------------------\n";
    echo "First Octet: "; echo $EEPROM[0x00]; echo "\n";
    
    $i = 0x00D8; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 404C
    
    $i = 0x00F0; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    $i = 0x0198; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 404C
    $i = 0x01B0; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    $i = 0x01B0; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    $i = 0x0418; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 404C
    $i = 0x0670; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    
    $i = 0x06B4; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 2096
    
    $i = 0x0768; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 873a
    $i = 0x06AC; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; //
    $i = 0x06B4; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; //
    $i = 0x0850; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 2096
    $i = 0x0C70; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    $i = 0x0D58; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 404C
    $i = 0x0D70; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    $i = 0x0F18; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 404C
    $i = 0x0F30; echo "IEEE: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo $EEPROM[$i++];  echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8c09
    
    $i = 0x0952; echo "Short: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 873A
    $i = 0x0962; echo "Short: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 8C09
    $i = 0x0A4E; echo "Short: "; echo $EEPROM[$i++]; echo $EEPROM[$i++]; echo "\n"; // 404C

    
?>

