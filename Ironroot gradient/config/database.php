<?php
// Database configuration and PDO factory
require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $pdo;
    
    public function __construct() {
        $this->host = env('DB_HOST', '127.0.0.1');
        $this->port = env('DB_PORT', '3307');
        $this->dbname = env('DB_DATABASE', 'construction_management');
        $this->username = env('DB_USERNAME', 'construction_user');
        $this->password = env('DB_PASSWORD', 'secure_password');
    }
    
    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    // Singleton pattern to get database instance
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }
    
    // Get PDO connection
    public static function getConnection() {
        return static::getInstance()->connect();
    }
}