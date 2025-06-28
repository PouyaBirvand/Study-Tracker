<?php
/**
 * Application Entry Point
 * نقطه ورود اپلیکیشن
 */

// تنظیم timezone
date_default_timezone_set('Asia/Tehran');

// شروع session
session_start();

// تنظیم error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تنظیم encoding
mb_internal_encoding('UTF-8');

// بارگذاری autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// بارگذاری کلاس‌های اصلی
require_once __DIR__ . '/../src/Config/DatabaseConfig.php';
require_once __DIR__ . '/../src/Models/BaseModel.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Models/StudySession.php';
require_once __DIR__ . '/../src/Models/Subject.php';
require_once __DIR__ . '/../src/Models/Goal.php';
require_once __DIR__ . '/../src/Models/Achievement.php';
require_once __DIR__ . '/../src/Models/UserPoint.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/StatisticsService.php';
require_once __DIR__ . '/../src/Services/GamificationService.php';
require_once __DIR__ . '/../src/Controllers/BaseController.php';

// تنظیم هدرهای امنیتی
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// مدیریت خطاها
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای داخلی سرور',
        'error' => $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE);
});

try {
    // بررسی اتصال به دیتابیس
    DatabaseConfig::getInstance()->getConnection();
    
    // بارگذاری routes
    require_once __DIR__ . '/../routes/api.php';
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در راه‌اندازی اپلیکیشن',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
