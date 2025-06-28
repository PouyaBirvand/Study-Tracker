#!/usr/bin/env php
<?php
/**
 * Health Check Script
 * اسکریپت بررسی سلامت سیستم
 */

require_once __DIR__ . '/../src/Config/DatabaseConfig.php';

class HealthCheck 
{
    private array $checks = [];
    private bool $allPassed = true;
    
    public function run(): void 
    {
        echo "🏥 بررسی سلامت سیستم...\n\n";
        
        $this->checkDatabase();
        $this->checkFilePermissions();
        $this->checkDiskSpace();
        $this->checkPHPVersion();
        $this->checkRequiredExtensions();
        
        $this->printResults();
        
        if (!$this->allPassed) {
            exit(1);
        }
    }
    
    private function checkDatabase(): void 
    {
        try {
            $db = DatabaseConfig::getInstance()->getConnection();
            $stmt = $db->query("SELECT 1");
            $result = $stmt->fetch();
            
            if ($result) {
                $this->addCheck('Database Connection', true, 'اتصال دیتابیس سالم است');
            } else {
                $this->addCheck('Database Connection', false, 'خطا در اتصال دیتابیس');
            }
        } catch (Exception $e) {
            $this->addCheck('Database Connection', false, 'خطا: ' . $e->getMessage());
        }
    }
    
    private function checkFilePermissions(): void 
    {
        $paths = [
            __DIR__ . '/../public/uploads',
            __DIR__ . '/../logs',
            __DIR__ . '/../cache'
        ];
        
        $allWritable = true;
        $errors = [];
        
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            
            if (!is_writable($path)) {
                $allWritable = false;
                $errors[] = $path;
            }
        }
        
        if ($allWritable) {
            $this->addCheck('File Permissions', true, 'مجوزهای فایل صحیح است');
        } else {
            $this->addCheck('File Permissions', false, 'مجوزهای فایل ناصحیح: ' . implode(', ', $errors));
        }
    }
    
    private function checkDiskSpace(): void 
    {
        $freeBytes = disk_free_space('/');
        $totalBytes = disk_total_space('/');
        $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
        
        if ($usedPercent < 90) {
            $this->addCheck('Disk Space', true, sprintf('فضای دیسک: %.1f%% استفاده شده', $usedPercent));
        } else {
            $this->addCheck('Disk Space', false, sprintf('فضای دیسک کم: %.1f%% استفاده شده', $usedPercent));
        }
    }
    
    private function checkPHPVersion(): void 
    {
        $version = PHP_VERSION;
        $minVersion = '7.4.0';
        
        if (version_compare($version, $minVersion, '>=')) {
            $this->addCheck('PHP Version', true, "نسخه PHP: {$version}");
        } else {
            $this->addCheck('PHP Version', false, "نسخه PHP قدیمی: {$version} (حداقل {$minVersion} نیاز است)");
        }
    }
    
    private function checkRequiredExtensions(): void 
    {
        $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (empty($missing)) {
            $this->addCheck('PHP Extensions', true, 'تمام افزونه‌های مورد نیاز موجود است');
        } else {
            $this->addCheck('PHP Extensions', false, 'افزونه‌های مفقود: ' . implode(', ', $missing));
        }
    }
    
    private function addCheck(string $name, bool $passed, string $message): void 
    {
        $this->checks[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message
        ];
        
        if (!$passed) {
            $this->allPassed = false;
        }
    }
    
    private function printResults(): void 
    {
        foreach ($this->checks as $check) {
            $status = $check['passed'] ? '✅' : '❌';
            echo "{$status} {$check['name']}: {$check['message']}\n";
        }
        
        echo "\n" . str_repeat('-', 50) . "\n";
        
        if ($this->allPassed) {
            echo "🎉 تمام بررسی‌ها موفقیت‌آمیز بود!\n";
        } else {
            echo "⚠️  برخی بررسی‌ها ناموفق بودند. لطفاً مشکلات را برطرف کنید.\n";
        }
    }
}

// اجرای بررسی سلامت
$healthCheck = new HealthCheck();
$healthCheck->run();
