<?php
/**
 * Response Helper
 * کلاس کمکی پاسخ
 */
class Response 
{
    /**
     * پاسخ موفق
     */
    public static function success($data = null, string $message = 'عملیات با موفقیت انجام شد', int $statusCode = 200): void 
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * پاسخ خطا
     */
    public static function error(string $message = 'خطایی رخ داده است', $errors = null, int $statusCode = 400): void 
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        self::json($response, $statusCode);
    }
    
    /**
     * پاسخ JSON
     */
    public static function json(array $data, int $statusCode = 200): void 
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * پاسخ صفحه‌بندی
     */
    public static function paginated(array $items, int $total, int $page, int $limit): void 
    {
        self::success([
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit),
                'has_next' => $page < ceil($total / $limit),
                'has_prev' => $page > 1
            ]
        ]);
    }
}
