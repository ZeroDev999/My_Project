<?php
// Database configuration
// Production hosting credentials for thsv25.hostatom.com
define('DB_HOST', getenv('DB_HOST') ?: 'localhost:3306');
define('DB_NAME', getenv('DB_NAME') ?: 'ncitproj_ikkyuz');
define('DB_USER', getenv('DB_USER') ?: 'ncitproj_ikkyuz');
define('DB_PASS', getenv('DB_PASS') ?: 'it2_ikkyuz');

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
?>
