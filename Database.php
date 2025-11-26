<?php
class Database {    
    public function dbConnect() {        
        static $DBH = null;      
        if (is_null($DBH)) {              
            // Include config.php to get $host, $username, $password, $database
            include(__DIR__ . '/../config.php'); // adjust path if needed
            
            $connection = new mysqli($host, $username, $password, $database);
            if($connection->connect_error){
                die("Error failed to connect to MySQL: " . $connection->connect_error);
            } else {
                $DBH = $connection;
            }         
        }
        return $DBH;    
    }     
}
