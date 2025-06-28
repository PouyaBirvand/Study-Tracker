<?php
/**
 * Base Controller
 * کنترلر پایه
 */
abstract class BaseController 
{
    protected AuthService $authService;
    
    public function __construct() 
    {
        $this->authService = new AuthService();
        $this->setHeaders();
    }
    
    /**
     * تنظیم هدرهای HTTP
     */
    private function setHeaders(): void 
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // پاسخ به درخواست OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * دریافت ورودی JSON
     */
    protected function getJsonInput(): array 
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('فرمت JSON نامعتبر است');
        }
        
        return $data ?? [];
    }
    
    /**
     * ارسال پاسخ JSON
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void 
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * دریافت کاربر فعلی
     */
    protected function getCurrentUser(): array 
    {
        $token = $this->getBearerToken();
        
        if (!$token) {
            throw new Exception('توکن احراز هویت ارسال نشده است');
        }
        
        $user = $this->authService->getCurrentUser($token);
        
        if (!$user) {
            throw new Exception('توکن نامعتبر یا منقضی شده است');
        }
        
        return $user;
    }
    
    /**
     * دریافت توکن Bearer از هدر
     */
    private function getBearerToken(): ?string 
    {
        $headers = $this->getAuthorizationHeader();
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * دریافت هدر Authorization
     */
    private function getAuthorizationHeader(): ?string 
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
        
        return $headers;
    }
    
    /**
     * دریافت پارامتر مسیر
     */
    protected function getRouteParam(string $param): ?string 
    {
        // این متد باید بر اساس سیستم routing شما پیاده‌سازی شود
        // در اینجا فرض می‌کنیم که پارامترها در $_GET موجود هستند
        return $_GET[$param] ?? null;
    }
    
    /**
     * اعتبارسنجی ورودی
     */
    protected function validate(array $data, array $rules): void 
    {
        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $singleRule) {
                $this->validateField($data, $field, $singleRule);
            }
        }
    }
    
    /**
     * اعتبارسنجی فیلد
     */
    private function validateField(array $data, string $field, string $rule): void 
    {
        $value = $data[$field] ?? null;
        
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    throw new Exception("فیلد {$field} الزامی است");
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("فرمت ایمیل نامعتبر است");
                }
                break;
                
                case 'numeric':
                    if ($value && !is_numeric($value)) {
                        throw new Exception("فیلد {$field} باید عددی باشد");
                    }
                    break;
                    
                case 'integer':
                    if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                        throw new Exception("فیلد {$field} باید عدد صحیح باشد");
                    }
                    break;
                    
                default:
                    if (strpos($rule, 'min:') === 0) {
                        $min = (int) substr($rule, 4);
                        if ($value && strlen($value) < $min) {
                            throw new Exception("فیلد {$field} باید حداقل {$min} کاراکتر باشد");
                        }
                    } elseif (strpos($rule, 'max:') === 0) {
                        $max = (int) substr($rule, 4);
                        if ($value && strlen($value) > $max) {
                            throw new Exception("فیلد {$field} باید حداکثر {$max} کاراکتر باشد");
                        }
                    }
                    break;
            }
        }
        
        /**
         * بررسی دسترسی
         */
        protected function requireAuth(): void 
        {
            $this->getCurrentUser(); // اگر کاربر معتبر نباشد، exception پرتاب می‌کند
        }
        
        /**
         * لاگ خطا
         */
        protected function logError(Exception $e, array $context = []): void 
        {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'context' => $context
            ];
            
            error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
        }
    }
    
