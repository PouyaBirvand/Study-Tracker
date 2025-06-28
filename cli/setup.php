#!/usr/bin/env php
<?php
/**
 * Database Setup Command
 * دستور راه‌اندازی دیتابیس
 */

require_once __DIR__ . '/../src/Config/DatabaseConfig.php';
require_once __DIR__ . '/../database/seeders/UserSeeder.php';
require_once __DIR__ . '/../database/seeders/SubjectSeeder.php';
require_once __DIR__ . '/../database/seeders/AchievementSeeder.php';

class SetupCommand 
{
    private PDO $db;
    
    public function __construct() 
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    public function run(): void 
    {
        echo "🚀 شروع راه‌اندازی دیتابیس...\n\n";
        
        try {
            $this->createTables();
            $this->seedData();
            
            echo "\n✅ راه‌اندازی با موفقیت تکمیل شد!\n";
            echo "📧 ایمیل تست: test@example.com\n";
            echo "🔑 رمز عبور تست: 123456\n";
            
        } catch (Exception $e) {
            echo "\n❌ خطا در راه‌اندازی: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function createTables(): void 
    {
        echo "📋 ایجاد جداول...\n";
        
        // خواندن فایل SQL
        $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
        
        if (!$sql) {
            throw new Exception('فایل schema.sql یافت نشد');
        }
        
        // اجرای دستورات SQL
        $this->db->exec($sql);
        
        echo "✅ جداول ایجاد شدند.\n";
    }
    
    private function seedData(): void 
    {
        echo "🌱 درج داده‌های اولیه...\n";
        
        // User Seeder
        $userSeeder = new UserSeeder();
        $userSeeder->run();
        
        // Subject Seeder
        $subjectSeeder = new SubjectSeeder();
        $subjectSeeder->run();
        
        // Achievement Seeder
        $achievementSeeder = new AchievementSeeder();
        $achievementSeeder->run();
        
        echo "✅ داده‌های اولیه درج شدند.\n";
    }
}

// اجرای دستور
$setup = new SetupCommand();
$setup->run();
