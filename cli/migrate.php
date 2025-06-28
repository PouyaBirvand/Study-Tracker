#!/usr/bin/env php
<?php
/**
 * Migration Command
 * دستور مهاجرت دیتابیس
 */

require_once __DIR__ . '/../src/Config/DatabaseConfig.php';

class MigrateCommand 
{
    private PDO $db;
    private string $migrationsPath;
    
    public function __construct() 
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
        $this->migrationsPath = __DIR__ . '/../database/migrations/';
    }
    
    public function run(): void 
    {
        echo "🔄 شروع مهاجرت دیتابیس...\n\n";
        
        try {
            $this->createMigrationsTable();
            $this->runMigrations();
            
            echo "\n✅ مهاجرت با موفقیت تکمیل شد!\n";
            
        } catch (Exception $e) {
            echo "\n❌ خطا در مهاجرت: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function createMigrationsTable(): void 
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->exec($sql);
    }
    
    private function runMigrations(): void 
    {
        $migrationFiles = glob($this->migrationsPath . '*.sql');
        sort($migrationFiles);
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.sql');
            
            if ($this->isMigrationExecuted($migrationName)) {
                echo "⏭️  رد شد: {$migrationName}\n";
                continue;
            }
            
            echo "🔄 اجرای: {$migrationName}\n";
            
            $sql = file_get_contents($file);
            $this->db->exec($sql);
            
            $this->markMigrationAsExecuted($migrationName);
            
            echo "✅ تکمیل: {$migrationName}\n";
        }
    }
    
    private function isMigrationExecuted(string $migration): bool 
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    private function markMigrationAsExecuted(string $migration): void 
    {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }
}

// اجرای دستور
$migrate = new MigrateCommand();
$migrate->run();
