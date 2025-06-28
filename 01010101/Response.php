<?php
/**
 * Response Handler
 * مدیریت پاسخ‌های API
 */
class Response 
{
    private int $statusCode;
    private array $data;
    private ?string $message;
    private bool $success;
    
    public function __construct(int $statusCode = 200, array $data = [], ?string $message = null, bool $success = true) 
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->message = $message;
        $this->success = $success;
    }
    
    public static function success(array $data = [], string $message = null, int $statusCode = 200): self 
    {
        return new self($statusCode, $data, $message, true);
    }
    
    public static function error(string $message, int $statusCode = 400, array $data = []): self 
    {
        return new self($statusCode, $data, $message, false);
    }
    
    public function send(): void 
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        exit;
    }
}
