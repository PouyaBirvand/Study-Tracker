<?php
/**
 * Database Configuration
 * تنظیمات دیتابیس
 */
class DatabaseConfig 
{
    private static ?DatabaseConfig $instance = null;
    private ?PDO $connection = null;
    
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private int $port;
    
    private function __construct() 
    {
        $this->loadConfig();
    }
    
    public static function getInstance(): DatabaseConfig 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function loadConfig(): void 
    {
        // Load from environment or config file
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->database = $_ENV['DB_DATABASE'] ?? 'study_tracker';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->port = (int)($_ENV['DB_PORT'] ?? 3306);
    }
    
    public function getConnection(): PDO 
    {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                throw new Exception("خطا در اتصال به دیتابیس: " . $e->getMessage());
            }
        }
        
        return $this->connection;
    }
    
    public function closeConnection(): void 
    {
        $this->connection = null;
    }
    
    public function testConnection(): bool 
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}
