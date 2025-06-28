<?php
/**
 * Application Configuration
 * تنظیمات کلی اپلیکیشن
 */
class AppConfig 
{
    public const APP_NAME = 'سامانه هوشمند مطالعه';
    public const APP_VERSION = '1.0.0';
    public const TIMEZONE = 'Asia/Tehran';
    public const LANGUAGE = 'fa';
    
    // Security Settings
    public const JWT_SECRET = 'your-super-secret-key-change-in-production';
    public const JWT_EXPIRE = 3600; // 1 hour
    public const PASSWORD_MIN_LENGTH = 6;
    
    // Pagination
    public const DEFAULT_PAGE_SIZE = 10;
    public const MAX_PAGE_SIZE = 100;
    
    // File Upload
    public const MAX_FILE_SIZE = 5242880; // 5MB
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
    
    public static function init(): void 
    {
        // تنظیم timezone
        date_default_timezone_set(self::TIMEZONE);
        
        // تنظیم encoding
        mb_internal_encoding('UTF-8');
        
        // تنظیم error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        
        // تنظیم session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
