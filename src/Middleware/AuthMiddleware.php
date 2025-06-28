<?php
/**
 * Auth Middleware
 * میدل‌ویر احراز هویت
 */
class AuthMiddleware 
{
    private AuthService $authService;
    
    public function __construct() 
    {
        $this->authService = new AuthService();
    }
    
    public function handle(): void 
    {
        $token = $this->getBearerToken();
        
        if (!$token) {
            $this->unauthorized('توکن احراز هویت ارسال نشده است');
        }
        
        $user = $this->authService->getCurrentUser($token);
        
        if (!$user) {
            $this->unauthorized('توکن نامعتبر یا منقضی شده است');
        }
        
        // ذخیره اطلاعات کاربر برای استفاده در کنترلرها
        $_SESSION['current_user'] = $user;
    }
    
    private function getBearerToken(): ?string 
    {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    private function unauthorized(string $message): void 
    {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
