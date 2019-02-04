<?php
    // Thanks to https://www.tutorialspoint.com/sqlite/sqlite_php.htm
    
    class MyDB extends SQLite3 {
        function __construct() {
            $this->open('LqiStorage.db');
        }
    }
    
    function createDB($db) {
        // $sql = "CREATE TABLE COMPANY (ID INT PRIMARY KEY     NOT NULL, NAME           TEXT    NOT NULL, AGE            INT     NOT NULL, ADDRESS        CHAR(50), SALARY         REAL);";
        $sql = "CREATE TABLE voisines (";
        $sql .= "TimeStamp              TEXT, ";
        $sql .= "NeighbourTableEntries  TEXT, ";
        $sql .= "Row                    TEXT, ";
        $sql .= "ExtendedPanId          TEXT, ";
        $sql .= "IEEE_Address           TEXT, ";
        $sql .= "Depth                  TEXT, ";
        $sql .= "LinkQuality            TEXT, ";
        $sql .= "BitmapOfAttributes     TEXT, ";
        $sql .= "NE                     TEXT, ";
        $sql .= "NE_Name                TEXT, ";
        $sql .= "Voisine                TEXT, ";
        $sql .= "Voisine_Name           TEXT, ";
        $sql .= "Type                   TEXT, ";
        $sql .= "Relationship           TEXT, ";
        $sql .= "Rx                     TEXT, ";
        $sql .= "LinkQualityDec         TEXT  ";
        $sql .= ");";
        echo "SQL: ".$sql."\n";
        
        $ret = $db->exec($sql);
        if(!$ret){
            echo $db->lastErrorMsg();
        } else {
            echo "Table created successfully\n";
        }
    }
    
    function insertData( $db, $fields, $data){
        // $sql = "INSERT INTO COMPANY (ID,NAME,AGE,ADDRESS,SALARY) VALUES (1, 'Paul', 32, 'California', 20000.00 );";
        // INSERT INTO COMPANY (ID,NAME,AGE,ADDRESS,SALARY) VALUES (2, 'Allen', 25, 'Texas', 15000.00 );
        // INSERT INTO COMPANY (ID,NAME,AGE,ADDRESS,SALARY) VALUES (3, 'Teddy', 23, 'Norway', 20000.00 );
        // INSERT INTO COMPANY (ID,NAME,AGE,ADDRESS,SALARY) VALUES (4, 'Mark', 25, 'Rich-Mond ', 65000.00 );
        $sql = "insert into voisines ( ".$fields.") values (".$data.");";
        echo "sql insert: ".$sql;
        
        $ret = $db->exec($sql);
        if(!$ret) {
            echo $db->lastErrorMsg();
        } else {
            echo "Records created successfully\n";
        }
    }
    
    function listData($db) {
        $sql = "SELECT * from voisines;";
        
        $ret = $db->query($sql);
        while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
            foreach ($row as $key=>$data) {
                echo $key." = ". $data . " / ";
            }
            echo "\n";
        }
        echo "Operation done successfully\n";
    }
    
    function deleteData() {
        $sql = "DELETE from COMPANY where ID = 2;";
        
        $ret = $db->exec($sql);
        if(!$ret){
            echo $db->lastErrorMsg();
        } else {
            echo $db->changes(), " Record deleted successfully\n";
        }
    }
    
    function updateData() {
        $sql = "UPDATE COMPANY set SALARY = 25000.00 where ID=1;";
        $ret = $db->exec($sql);
        if(!$ret) {
            echo $db->lastErrorMsg();
        } else {
            echo $db->changes(), " Record updated successfully\n";
        }
        
    }
    
    function getJson($db) {
        $DataFile = "AbeilleLQI_MapData.json";
        
        if ( file_exists($DataFile) ){
            $json = json_decode(file_get_contents($DataFile), true);
            $LQI = $json['data'];
        }
        
        $TimeStamp = time();
        
        foreach ( $LQI as $row => $voisineList ) {
            echo $voisineList['NE_Name']."-".$voisineList['Voisine_Name']."\n";
            $fields = 'TimeStamp, ExtendedPanId, IEEE_Address, Depth, LinkQuality, BitmapOfAttributes, NE, NE_Name, Voisine, Voisine_Name, Type, Relationship, Rx, LinkQualityDec';
            
            $data = '"'.$TimeStamp.'", "'.$voisineList['ExtendedPanId'].'", "'.$voisineList['IEEE_Address'].'", "'.$voisineList['Depth'].'", "'.$voisineList['LinkQuality'].'", "'.$voisineList['BitmapOfAttributes'].'", "'.$voisineList['NE'].'", "'.$voisineList['NE_Name'].'", "'.$voisineList['Voisine'].'", "'.$voisineList['Voisine_Name'].'", "'.$voisineList['Type'].'", "'.$voisineList['Relationship'].'", "'.$voisineList['Rx'].'", "'.$voisineList['LinkQualityDec'].'"';
            
            insertData( $db, $fields, $data);
        }
    }
    
    $db = new MyDB();
    if(!$db) {
        echo $db->lastErrorMsg();
    } else {
        echo "Opened database successfully\n";
    }
    
    createDB($db);
    // listData($db);
    getJson($db);
    
    $db->close();
    ?>
