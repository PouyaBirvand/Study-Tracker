<?php
/**
 * CORS Middleware
 * میدل‌ویر CORS
 */
class CorsMiddleware 
{
    public function handle(): void 
    {
        // تنظیم هدرهای CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400'); // 24 ساعت
        
        // پاسخ به درخواست preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
