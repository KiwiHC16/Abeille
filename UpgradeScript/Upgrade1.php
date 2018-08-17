<?php
    
    class my_db{
        
        private static $databases;
        private $connection;
        
        public function __construct($connDetails){
            if(!is_object(self::$databases[$connDetails])){
                list($host, $user, $pass, $dbname) = explode('|', $connDetails);
                $dsn = "mysql:host=$host;dbname=$dbname";
                self::$databases[$connDetails] = new PDO($dsn, $user, $pass);
            }
            $this->connection = self::$databases[$connDetails];
        }
        
        public function fetchAll($sql){
            $args = func_get_args();
            array_shift($args);
            $statement = $this->connection->prepare($sql);
            $statement->execute($args);
            return $statement->fetchAll(PDO::FETCH_OBJ);
        }
    }
    
    define('DB_MAIN', 'localhost|jeedom|bc7fb777df61e49|jeedom');
    
    $db = new my_db(DB_MAIN);
    
    // $sql = "SELECT concat('{\"topic\":\"',substr(configuration,26,length(configuration)-25)) FROM `cmd` WHERE `eqType` = 'Abeille' AND `configuration` LIKE '{\\\"topic\":\\\"Abeille%'";
    $sql = "SELECT id, logicalId, type, configuration FROM `cmd` WHERE `eqType` = 'Abeille' AND `configuration` LIKE '%topic%'";
    
    // $rows = $db->fetchAll('SELECT * FROM cmd' );
    $rows = $db->fetchAll($sql);
    
    // var_dump( $rows );
    // echo "\n\n\n+++++++++++++++++++++++++--------\n\n\n";
    foreach ($rows as $key => $row) {
        // var_dump(  $row );
        $rowArray = json_decode($row->configuration);
        // var_dump( $rowArray );
        // echo $rowArray->topic." => ";
        
        if ($row->type == 'Info') {
            $rowArray->topic = str_replace("Abeille/", "", $rowArray->topic );
            // echo $rowArray->topic." => ";
            $position = strpos($rowArray->topic,"/");
            if ( $position > 1 ) {
                $rowArray->topic = substr( $rowArray->topic, $position-strlen($rowArray->topic)+1 );
            }
        }
        
        if ($row->type == 'action') {
            if (strpos($rowArray->topic,"Ruche") > 1) {
                // Je ne change pas
            }
            elseif {
                (strpos($rowArray->topic,"Group") > 1) {
            }
            else {
                $rowArray->topic = str_replace("CmdAbeille/", "", $rowArray->topic );
                $position = strpos($rowArray->topic,"/");
                if ( $position > 1 ) {
                    $rowArray->topic = substr( $rowArray->topic, $position-strlen($rowArray->topic)+1 );
                }
            }
        }
        // echo $rowArray->topic."\n";
        // var_dump( $rowArray );
        
        $sql = "update cmd set logicalId='".$rowArray->topic."', configuration='".json_encode($rowArray)."' where id='".$row->id."'";
        echo $sql."\n";
        // $rows = $db->fetchAll($sql);
    }
    ?>
