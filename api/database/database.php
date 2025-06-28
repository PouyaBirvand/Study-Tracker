<?php
/**
 * Database Configuration
 * تنظیمات پایگاه داده
 */
class DatabaseConfig 
{
    private const HOST = 'localhost';
    private const DB_NAME = 'study_system';
    private const USERNAME = 'root';
    private const PASSWORD = '';
    private const CHARSET = 'utf8mb4';
    
    private static $instance = null;
    private $connection;
    
    private function __construct() 
    {
        try {
            $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, self::USERNAME, self::PASSWORD, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO 
    {
        return $this->connection;
    }
    
    // جلوگیری از کلون کردن
    private function __clone() {}
    
    // جلوگیری از unserialize
    public function __wakeup() 
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
